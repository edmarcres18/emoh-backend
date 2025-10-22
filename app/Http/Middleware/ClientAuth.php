<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClientAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('client')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login as a client.'
            ], 401);
        }

        $client = Auth::guard('client')->user();

        // Check if session has timed out (1 day of inactivity)
        if ($client && $client->hasSessionTimedOut()) {
            // Delete current token to force re-authentication
            $client->currentAccessToken()?->delete();

            return response()->json([
                'success' => false,
                'message' => 'Your session has expired due to inactivity. Please login again.',
                'error_code' => 'SESSION_TIMEOUT'
            ], 440); // 440 Login Time-out (non-standard but descriptive)
        }

        // Update last activity timestamp
        if ($client) {
            $client->updateLastActivity();
        }

        return $next($request);
    }
}
