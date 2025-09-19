<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Display a listing of the permissions.
     */
    public function index(Request $request): JsonResponse
    {
        // Only System Admin can access permissions management
        if (!auth()->user()->hasRole('System Admin')) {
            return response()->json([
                'message' => 'Only System Admin can access permissions management'
            ], 403);
        }

        $query = Permission::query();

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $permissions = $query->paginate($request->get('per_page', 15));

        return response()->json($permissions);
    }

    /**
     * Store a newly created permission in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Only System Admin can create permissions
        if (!auth()->user()->hasRole('System Admin')) {
            return response()->json([
                'message' => 'Only System Admin can create permissions'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        $permission = Permission::create(['name' => $request->name]);

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission
        ], 201);
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission): JsonResponse
    {
        // Only System Admin can view permissions
        if (!auth()->user()->hasRole('System Admin')) {
            return response()->json([
                'message' => 'Only System Admin can view permissions'
            ], 403);
        }

        return response()->json($permission);
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(Request $request, Permission $permission): JsonResponse
    {
        // Only System Admin can edit permissions
        if (!auth()->user()->hasRole('System Admin')) {
            return response()->json([
                'message' => 'Only System Admin can edit permissions'
            ], 403);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions')->ignore($permission->id)
            ],
        ]);

        $permission->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Permission updated successfully',
            'permission' => $permission
        ]);
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroy(Permission $permission): JsonResponse
    {
        // Only System Admin can delete permissions
        if (!auth()->user()->hasRole('System Admin')) {
            return response()->json([
                'message' => 'Only System Admin can delete permissions'
            ], 403);
        }

        // Check if permission is assigned to any roles
        if ($permission->roles()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete permission that is assigned to roles'
            ], 403);
        }

        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully'
        ]);
    }
}
