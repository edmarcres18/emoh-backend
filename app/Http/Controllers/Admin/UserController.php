<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the users (API endpoint for existing Vue component).
     */
    public function index(Request $request): JsonResponse
    {
        // Check if user has permission to view users
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            return response()->json([
                'message' => 'You do not have permission to view users'
            ], 403);
        }

        $query = User::with('roles');

        // If user is not System Admin, exclude System Admin users from the list
        if (!auth()->user()->hasRole('System Admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'System Admin');
            });
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * Display a listing of the users (Inertia page).
     */
    public function indexPage(Request $request): Response
    {
        // Check if user has permission to view users
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            abort(403, 'You do not have permission to view users');
        }

        $query = User::with('roles');

        // If user is not System Admin, exclude System Admin users from the list
        if (!auth()->user()->hasRole('System Admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'System Admin');
            });
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->paginate($request->get('per_page', 15));

        // Get available roles for filtering
        $roles = Role::all();
        if (!auth()->user()->hasRole('System Admin')) {
            $roles = $roles->where('name', '!=', 'System Admin');
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles->values(),
            'filters' => $request->only(['search', 'role']),
            'can' => [
                'create_users' => auth()->user()->hasAnyRole(['System Admin', 'Admin']),
                'delete_users' => auth()->user()->hasRole('System Admin'),
            ]
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        // Check if user has permission to create users
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            abort(403, 'You do not have permission to create users');
        }

        $roles = Role::all();
        if (!auth()->user()->hasRole('System Admin')) {
            $roles = $roles->where('name', '!=', 'System Admin');
        }

        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles->values()
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(UserRequest $request)
    {
        // Check if user has permission to create users
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to create users'], 403);
            }
            abort(403, 'You do not have permission to create users');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->filled('role')) {
            // Prevent Admin users from assigning System Admin role
            if (!auth()->user()->hasRole('System Admin')) {
                $systemAdminRole = Role::where('name', 'System Admin')->first();
                if ($systemAdminRole && $systemAdminRole->id == $request->role) {
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'You do not have permission to assign System Admin role'], 403);
                    }
                    return back()->withErrors(['role' => 'You do not have permission to assign System Admin role']);
                }
            }
            
            $user->syncRoles([$request->role]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'User created successfully',
                'user' => $user->load('roles')
            ], 201);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        // Check if user has permission to view users
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to view users'], 403);
            }
            abort(403, 'You do not have permission to view users');
        }

        // Prevent Admin users from viewing System Admin users
        if ($user->hasRole('System Admin') && !auth()->user()->hasRole('System Admin')) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to view System Admin users'], 403);
            }
            abort(403, 'You do not have permission to view System Admin users');
        }

        if (request()->expectsJson()) {
            return response()->json($user->load('roles'));
        }

        return Inertia::render('Admin/Users/Show', [
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        // Check if user has permission to edit users
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            abort(403, 'You do not have permission to edit users');
        }

        // Prevent non-System Admin users from editing System Admin users
        if ($user->hasRole('System Admin') && !auth()->user()->hasRole('System Admin')) {
            abort(403, 'You do not have permission to edit System Admin users');
        }

        $roles = Role::all();
        if (!auth()->user()->hasRole('System Admin')) {
            $roles = $roles->where('name', '!=', 'System Admin');
        }

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user->load('roles'),
            'roles' => $roles->values()
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        // Check if user has permission to edit users
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to edit users'], 403);
            }
            abort(403, 'You do not have permission to edit users');
        }

        // Prevent non-System Admin users from editing System Admin users
        if ($user->hasRole('System Admin') && !auth()->user()->hasRole('System Admin')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to edit System Admin users'], 403);
            }
            abort(403, 'You do not have permission to edit System Admin users');
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        if ($request->filled('role')) {
            // Prevent Admin users from assigning System Admin role
            if (!auth()->user()->hasRole('System Admin')) {
                $systemAdminRole = Role::where('name', 'System Admin')->first();
                if ($systemAdminRole && $systemAdminRole->id == $request->role) {
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'You do not have permission to assign System Admin role'], 403);
                    }
                    return back()->withErrors(['role' => 'You do not have permission to assign System Admin role']);
                }
            }
            
            $user->syncRoles([$request->role]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user->load('roles')
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Only System Admin can delete users
        if (!auth()->user()->hasRole('System Admin')) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Only System Admin can delete users'], 403);
            }
            abort(403, 'Only System Admin can delete users');
        }

        // Prevent deletion of the current authenticated user
        if ($user->id === auth()->id()) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'You cannot delete your own account'], 403);
            }
            return back()->withErrors(['delete' => 'You cannot delete your own account']);
        }

        // Prevent deletion of System Admin users by checking if they have the role
        if ($user->hasRole('System Admin')) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'System Admin users cannot be deleted'], 403);
            }
            return back()->withErrors(['delete' => 'System Admin users cannot be deleted']);
        }

        $user->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'User deleted successfully']);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully');
    }

    /**
     * Get all roles for user assignment (API endpoint).
     */
    public function roles(): JsonResponse
    {
        // Check if user has permission to view roles
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            return response()->json([
                'message' => 'You do not have permission to view roles'
            ], 403);
        }

        $roles = Role::all();

        // If user is not System Admin, exclude System Admin role from the list
        if (!auth()->user()->hasRole('System Admin')) {
            $roles = $roles->where('name', '!=', 'System Admin');
        }

        return response()->json($roles->values());
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        // Check if user has permission to reset passwords
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            abort(403, 'You do not have permission to reset passwords');
        }

        // Prevent non-System Admin users from resetting System Admin passwords
        if ($user->hasRole('System Admin') && !auth()->user()->hasRole('System Admin')) {
            abort(403, 'You do not have permission to reset System Admin passwords');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Password reset successfully');
    }
}