<?php

namespace App\Http\Controllers;

use App\Enums\EventCostStatus;
use App\Enums\RequestStatus;
use App\Models\Event;
use App\Models\Request as RequestModel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = Event::with('EventCosts')->get();
        return Inertia::render('admin/Events', [
            'events' => $events,
            'cost_statuses' => EventCostStatus::values(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validated = $request->validate([
            'title' => 'required|string|max:50',
        ]);

        Event::create($validated);

        return redirect()->route('admin.events.index')->with('toast',[
            'type' => 'success',
            'message' => 'Gebeurtenis succesvol toegevoegd.',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        //
    }
}
