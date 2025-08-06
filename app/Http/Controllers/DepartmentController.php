<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        // Cache departments data for better performance
        $departments = cache()->remember('departments_all_list', 3600, function() {
            return Department::orderBy('name')->get();
        });

        return Inertia::render('departments/Index', [
            'departments' => $departments,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('departments/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments',
            'alias' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        Department::create($request->only(['name', 'alias', 'status']));

        // Clear cache to ensure fresh data
        cache()->forget('departments_all_list');

        return redirect()->route('departments.index')
            ->with('success', 'Department berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department): Response
    {
        return Inertia::render('departments/Show', [
            'department' => $department,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department): Response
    {
        return Inertia::render('departments/Edit', [
            'department' => $department,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'alias' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $department->update($request->only(['name', 'alias', 'status']));

        // Clear cache to ensure fresh data
        cache()->forget('departments_all_list');

        return redirect()->route('departments.index')
            ->with('success', 'Department berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        // Check if department has users
        if ($department->users()->count() > 0) {
            return back()->with('error', 'Department tidak dapat dihapus karena masih memiliki user.');
        }

        $department->delete();

        // Clear cache to ensure fresh data
        cache()->forget('departments_all_list');

        return redirect()->route('departments.index')
            ->with('success', 'Department berhasil dihapus.');
    }

    /**
     * Toggle status (active/inactive) for the specified department.
     */
    public function toggleStatus($id)
    {
        $department = Department::findOrFail($id);
        $department->status = $department->status === 'active' ? 'inactive' : 'active';
        $department->save();

        // Clear cache to ensure fresh data
        cache()->forget('departments_all_list');

        return redirect()->route('departments.index')->with('success', 'Status department berhasil diperbarui');
    }
}
