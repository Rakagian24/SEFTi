<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        // Cache roles data for better performance
        $roles = cache()->remember('roles_all_list', 3600, function() {
            return Role::orderBy('name')->get();
        });

        return Inertia::render('roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('roles/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'status' => 'required|in:active,inactive',
        ]);

        Role::create($request->only(['name', 'description', 'permissions', 'status']));

        return redirect()->route('roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): Response
    {
        return Inertia::render('roles/Show', [
            'role' => $role,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): Response
    {
        return Inertia::render('roles/Edit', [
            'role' => $role,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'status' => 'required|in:active,inactive',
        ]);

        $role->update($request->only(['name', 'description', 'permissions', 'status']));

        return redirect()->route('roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Check if role has users
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Role tidak dapat dihapus karena masih memiliki user.');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    /**
     * Toggle status (active/inactive) for the specified role.
     */
    public function toggleStatus($id)
    {
        $role = Role::findOrFail($id);
        $role->status = $role->status === 'active' ? 'inactive' : 'active';
        $role->save();
        return redirect()->route('roles.index')->with('success', 'Status role berhasil diperbarui');
    }
}
