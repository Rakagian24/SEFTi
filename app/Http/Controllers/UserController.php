<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'departments']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%") ;
            });
        }
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Handle activeDepartment filter
        if ($request->filled('activeDepartment')) {
            $activeDept = $request->activeDepartment;
            $query->whereHas('departments', function($q) use ($activeDept) {
                $q->where('departments.id', $activeDept);
            });
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $users = $query->orderByDesc('created_at')->paginate($perPage);

        // Cache master data for better performance
        $roles = cache()->remember('roles_active', 3600, function() {
            return \App\Models\Role::where('status', 'active')->get(['id','name']);
        });

        $departments = cache()->remember('departments_active_users', 3600, function() {
            return \App\Models\Department::where('status', 'active')->get(['id','name']);
        });

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->search,
                'per_page' => $perPage,
                'role_id' => $request->role_id,
                'department_id' => $request->department_id,
                'activeDepartment' => $request->activeDepartment,
            ],
            'roles' => $roles,
            'departments' => $departments,
        ]);
    }

    public function edit($id)
    {
        $user = User::with(['role', 'departments'])->findOrFail($id);
        $roles = Role::where('status', 'active')->get();
        $departments = Department::where('status', 'active')->get();

        return Inertia::render('users/Detail', [
            'user' => $user,
            'roles' => $roles,
            'departments' => $departments,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $id,
            'role_id' => 'required|exists:roles,id',
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
        ]);
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role_id' => $validated['role_id'],
        ]);
        $user->departments()->sync($validated['department_ids']);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui');
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();
        return redirect()->route('users.index')->with('success', 'Status user berhasil diperbarui');
    }
}
