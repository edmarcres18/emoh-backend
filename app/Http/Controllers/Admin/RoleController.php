<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::with('permissions');

        // If user is not System Admin, exclude System Admin role from the list
        if (!auth()->user()->hasRole('System Admin')) {
            $query->where('name', '!=', 'System Admin');
        }

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $roles = $query->paginate($request->get('per_page', 15));

        return response()->json($roles);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        // If user is not System Admin, validate that they can only assign permissions they have
        if (!auth()->user()->hasRole('System Admin') && $request->has('permissions')) {
            $allowedPermissions = $this->getAllowedPermissionsForAdmin();
            $requestedPermissions = $request->permissions;
            
            $invalidPermissions = array_diff($requestedPermissions, $allowedPermissions);
            if (!empty($invalidPermissions)) {
                return response()->json([
                    'message' => 'You can only assign permissions that you have access to'
                ], 403);
            }
        }

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->load('permissions')
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        // Prevent Admin users from viewing System Admin role
        if ($role->name === 'System Admin' && !auth()->user()->hasRole('System Admin')) {
            return response()->json([
                'message' => 'You do not have permission to view System Admin role'
            ], 403);
        }

        return response()->json($role->load('permissions'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        // Check if user has edit role permission
        if (!auth()->user()->can('edit role')) {
            return response()->json([
                'message' => 'You do not have permission to edit roles'
            ], 403);
        }

        // Prevent Admin users from editing System Admin role
        if ($role->name === 'System Admin' && !auth()->user()->hasRole('System Admin')) {
            return response()->json([
                'message' => 'You do not have permission to edit System Admin role'
            ], 403);
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id)
            ],
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        // If user is not System Admin, validate that they can only assign permissions they have
        if (!auth()->user()->hasRole('System Admin') && $request->has('permissions')) {
            $allowedPermissions = $this->getAllowedPermissionsForAdmin();
            $requestedPermissions = $request->permissions;
            
            $invalidPermissions = array_diff($requestedPermissions, $allowedPermissions);
            if (!empty($invalidPermissions)) {
                return response()->json([
                    'message' => 'You can only assign permissions that you have access to'
                ], 403);
            }
        }

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role->load('permissions')
        ]);
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): JsonResponse
    {
        // Check if user has delete role permission
        if (!auth()->user()->can('delete role')) {
            return response()->json([
                'message' => 'You do not have permission to delete roles'
            ], 403);
        }

        // Prevent deletion of System Admin role
        if ($role->name === 'System Admin') {
            return response()->json([
                'message' => 'System Admin role cannot be deleted'
            ], 403);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Get all permissions for role assignment.
     */
    public function permissions(): JsonResponse
    {
        // If user is System Admin, return all permissions
        if (auth()->user()->hasRole('System Admin')) {
            $permissions = Permission::all();
        } else {
            // If user is Admin, only return permissions that Admin role has
            $allowedPermissionIds = $this->getAllowedPermissionsForAdmin();
            $permissions = Permission::whereIn('id', $allowedPermissionIds)->get();
        }
        
        return response()->json($permissions);
    }

    /**
     * Get permissions that Admin role has access to.
     */
    private function getAllowedPermissionsForAdmin(): array
    {
        $adminRole = Role::where('name', 'Admin')->first();
        if (!$adminRole) {
            return [];
        }
        
        return $adminRole->permissions->pluck('id')->toArray();
    }
}
