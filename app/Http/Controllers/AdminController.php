<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return Inertia::render('admin/Admin', [
            'admins' => Admin::all()
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
        $validated = $request->validate([
            'employee' => ['required', 'email', new \App\Rules\ValidDomainEmail, 'unique:admins,employee'],
            'super' => ['nullable', 'boolean'],
        ]);

        Admin::create($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Administrator succesvol toegevoegd.'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        //
        $validated = $request->validate([
            'super' => 'required|boolean',
        ]);

        if (Admin::query()->whereNotNull('super')->count() == 1 && $admin->super && !$validated['super'])
            throw ValidationException::withMessages(['super_delete' => 'Super kan niet worden verwijderd, er moet minimaal 1 super administrator bestaan.']);

        $admin->update($validated);

        if ($admin->employee === $request->session()->get('magic.email') && !$admin->super) {
            session()->flush();
            session()->regenerate();
            session()->regenerateToken();
            session()->invalidate();
        }
//        if ($admin->)

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Administrator succesvol aangepast.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        //
        if (Admin::query()->whereNotNull('super')->count() == 1 && $admin->super)
            throw ValidationException::withMessages(['super_delete' => 'Administrator kan niet worden verwijderd, er moet minimaal 1 super administrator bestaan.']);

        $admin->delete();

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Administrator succesvol verwijderd.'
        ]);
    }
}
