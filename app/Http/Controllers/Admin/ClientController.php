<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Display a listing of the clients (API endpoint for existing Vue component).
     */
    public function index(Request $request): JsonResponse
    {
        // Check if user has permission to view clients
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            return response()->json([
                'message' => 'You do not have permission to view clients'
            ], 403);
        }

        $query = Client::query();

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status') && $request->status) {
            if ($request->status === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'unverified') {
                $query->whereNull('email_verified_at');
            } elseif ($request->status === 'google') {
                $query->whereNotNull('google_id');
            } elseif ($request->status === 'regular') {
                $query->whereNull('google_id');
            } elseif ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $clients = $query->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 15));

        return response()->json($clients);
    }

    /**
     * Display a listing of the clients (Inertia page).
     */
    public function indexPage(Request $request): Response
    {
        // Check if user has permission to view clients
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            abort(403, 'You do not have permission to view clients');
        }

        $query = Client::query();

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status') && $request->status) {
            if ($request->status === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'unverified') {
                $query->whereNull('email_verified_at');
            } elseif ($request->status === 'google') {
                $query->whereNotNull('google_id');
            } elseif ($request->status === 'regular') {
                $query->whereNull('google_id');
            } elseif ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $clients = $query->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 15));

        // Get statistics
        $stats = [
            'total' => Client::count(),
            'verified' => Client::whereNotNull('email_verified_at')->count(),
            'unverified' => Client::whereNull('email_verified_at')->count(),
            'google_users' => Client::whereNotNull('google_id')->count(),
            'regular_users' => Client::whereNull('google_id')->count(),
            'active' => Client::where('is_active', true)->count(),
            'inactive' => Client::where('is_active', false)->count(),
        ];

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
            'can' => [
                'view_clients' => auth()->user()->hasAnyRole(['System Admin', 'Admin']),
                'edit_clients' => auth()->user()->hasAnyRole(['System Admin', 'Admin']),
                'delete_clients' => auth()->user()->hasRole('System Admin'),
            ]
        ]);
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client)
    {
        // Check if user has permission to view clients
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to view clients'], 403);
            }
            abort(403, 'You do not have permission to view clients');
        }

        if (request()->expectsJson()) {
            return response()->json($client);
        }

        // Get client's API tokens count
        $tokensCount = $client->tokens()->count();

        return Inertia::render('Admin/Clients/Show', [
            'client' => $client,
            'tokensCount' => $tokensCount,
            'can' => [
                'edit_clients' => auth()->user()->hasAnyRole(['System Admin', 'Admin']),
                'delete_clients' => auth()->user()->hasRole('System Admin'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Client $client): Response
    {
        // Check if user has permission to edit clients
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            abort(403, 'You do not have permission to edit clients');
        }

        return Inertia::render('Admin/Clients/Edit', [
            'client' => $client,
            'can' => [
                'delete_clients' => auth()->user()->hasRole('System Admin'),
            ]
        ]);
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, Client $client)
    {
        // Check if user has permission to edit clients
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to edit clients'], 403);
            }
            abort(403, 'You do not have permission to edit clients');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('clients')->ignore($client->id)],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'email_verified_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if ($request->has('email_verified_at')) {
            $updateData['email_verified_at'] = $request->email_verified_at ? now() : null;
        }

        $client->update($updateData);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Client updated successfully',
                'client' => $client->fresh()
            ]);
        }

        return redirect()->route('admin.clients.index')
            ->with('success', 'Client updated successfully');
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(Client $client)
    {
        // Only System Admin can delete clients
        if (!auth()->user()->hasRole('System Admin')) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Only System Admin can delete clients'], 403);
            }
            abort(403, 'Only System Admin can delete clients');
        }

        // Revoke all tokens before deletion
        $client->tokens()->delete();
        
        $client->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Client deleted successfully']);
        }

        return redirect()->route('admin.clients.index')
            ->with('success', 'Client deleted successfully');
    }

    /**
     * Verify client email.
     */
    public function verifyEmail(Client $client)
    {
        // Check if user has permission to verify clients
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to verify clients'], 403);
            }
            abort(403, 'You do not have permission to verify clients');
        }

        $client->update([
            'email_verified_at' => now()
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Client email verified successfully',
                'client' => $client->fresh()
            ]);
        }

        return back()->with('success', 'Client email verified successfully');
    }

    /**
     * Unverify client email.
     */
    public function unverifyEmail(Client $client)
    {
        // Check if user has permission to unverify clients
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to unverify clients'], 403);
            }
            abort(403, 'You do not have permission to unverify clients');
        }

        $client->update([
            'email_verified_at' => null
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Client email unverified successfully',
                'client' => $client->fresh()
            ]);
        }

        return back()->with('success', 'Client email unverified successfully');
    }

    /**
     * Revoke all client tokens.
     */
    public function revokeTokens(Client $client)
    {
        // Check if user has permission to revoke tokens
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to revoke tokens'], 403);
            }
            abort(403, 'You do not have permission to revoke tokens');
        }

        $tokensCount = $client->tokens()->count();
        $client->tokens()->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Successfully revoked {$tokensCount} token(s)",
                'tokens_revoked' => $tokensCount
            ]);
        }

        return back()->with('success', "Successfully revoked {$tokensCount} token(s)");
    }

    /**
     * Reset client password.
     */
    public function resetPassword(Request $request, Client $client)
    {
        // Check if user has permission to reset passwords
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            abort(403, 'You do not have permission to reset passwords');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed'
        ]);

        $client->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Password reset successfully');
    }

    /**
     * Toggle client active status.
     */
    public function toggleActive(Client $client)
    {
        // Check if user has permission to toggle client status
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to toggle client status'], 403);
            }
            abort(403, 'You do not have permission to toggle client status');
        }

        $client->update([
            'is_active' => !$client->is_active
        ]);

        $status = $client->is_active ? 'activated' : 'deactivated';

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Client {$status} successfully",
                'client' => $client->fresh()
            ]);
        }

        return back()->with('success', "Client {$status} successfully");
    }

    /**
     * Activate client.
     */
    public function activate(Client $client)
    {
        // Check if user has permission to activate clients
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to activate clients'], 403);
            }
            abort(403, 'You do not have permission to activate clients');
        }

        $client->update(['is_active' => true]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Client activated successfully',
                'client' => $client->fresh()
            ]);
        }

        return back()->with('success', 'Client activated successfully');
    }

    /**
     * Deactivate client.
     */
    public function deactivate(Client $client)
    {
        // Check if user has permission to deactivate clients
        if (!auth()->user()->hasAnyRole(['System Admin', 'Admin'])) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to deactivate clients'], 403);
            }
            abort(403, 'You do not have permission to deactivate clients');
        }

        $client->update(['is_active' => false]);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Client deactivated successfully',
                'client' => $client->fresh()
            ]);
        }

        return back()->with('success', 'Client deactivated successfully');
    }
}
