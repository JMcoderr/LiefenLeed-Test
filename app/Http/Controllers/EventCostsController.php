<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCost;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class EventCostsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Event $event)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Event $event)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/', 'gt:0'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:now', 'after:start_date'],
        ]);

        $eventCost = $event->eventCosts()->first();

        $validator->after(function ($validator) use ($request, $event, $eventCost) {
            if ($request->filled('end_date')) {
                $startDate = Carbon::parse($request->input('start_date'));
                $endDate = Carbon::parse($request->input('end_date'));

                if ($startDate >= $endDate)
                    $validator->errors()->add('start_date', 'Het start datum moet voor het eind datum zijn.');
            }

            if ($eventCost) {
                $costStartDate = Carbon::parse($eventCost->start_date);
                $costEndDate = $eventCost->end_date ? Carbon::parse($eventCost->end_date) : null;
                $eventStartDate = Carbon::parse($request->input('start_date'));

                if ($costEndDate === null && $costStartDate > $eventStartDate)
                    $validator->errors()->add('start_date', 'Het nieuwe start datum moet na het laatste start datum.');
                else if ($costEndDate !== null && $costEndDate >= $eventStartDate)
                    $validator->errors()->add('end_date', 'Het nieuwe start datum moet na het laatste eind datum.');
            }
        });

        if ($validator->fails())
            return back()
                ->withErrors($validator)
                ->withInput();

        if ($eventCost && $eventCost->end_date === null)
            $eventCost->update(['end_date' => $request->input('start_date')]);
        $event->eventCosts()->create([
            'amount' => $request->input('amount'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date') ?? null,
        ]);

        return redirect()->route('admin.events.index')->with(
            'toast', [
                'type' => 'success',
                'message' => 'Gebeurtenis bedrag succesvol aangepast.',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event, EventCost $eventCost)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event, EventCost $eventCost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * TODO: make the stopping of a cost be a update and not a delete functionality
     */
    public function update(Request $request, Event $event, EventCost $eventCost)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event, EventCost $eventCost)
    {
        //
        if ($event->id != $eventCost->event_id)
            return back()->with('toast',
                [
                    'type' => 'error',
                    'message' => 'Gebeurtenis bedrag is niet geassocieerd met Gebeurtenis.'
                ]);

        $start_date = Carbon::parse($eventCost->start_date);

        if (Carbon::now() > $start_date)
        {
            $eventCost->update(['end_date' => Carbon::now()]);
            return redirect()->route('admin.events.index')->with('toast', [
                'type' => 'success',
                'message' => 'Gebeurtenis bedrag succesvol gestopt.',
            ]);
        }

        $eventCost->delete();
        return redirect()->route('admin.events.index')->with('toast', [
            'type' => 'success',
            'message' => 'Gebeurtenis bedrag succesvol verwijderd.'
        ]);
    }
}
