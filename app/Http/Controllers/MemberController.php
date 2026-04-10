<?php

namespace App\Http\Controllers;

use App\Imports\MemberImport;
use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $deleted = $request->input('deleted');

        $members = match ($deleted) {
            '1' => Member::query(),
            '2' => Member::query()->onlyTrashed('deleted_at'),
            '3' => Member::query()->withTrashed(),
            default => Member::query()->allowedMembers(),
        };

        return Inertia::render('admin/Members', [
            'members' => $members
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('id', '=', $search)
                            ->orWhere('email', 'like', "%$search%");
                    });
                })
                ->with(['requests.eventCost.event'])
                ->paginate(10)
                ->onEachSide(1)
                ->withQueryString(),
            'filters' => $request->only(['search', 'deleted']),
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
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:2048'
        ]);

        Excel::import(new MemberImport, $request->file('file'));

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Leden successful opgeslagen'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $member)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Member $member)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member)
    {
        //
    }
}
