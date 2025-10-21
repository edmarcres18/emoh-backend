<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiErrorHandler
{
    /**
     * Handle an incoming request and provide comprehensive error handling
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * Handle exceptions with proper logging and user-friendly responses
     */
    private function handleException(Request $request, Throwable $e): JsonResponse
    {
        $isProduction = config('app.env') === 'production';
        $shouldLog = $this->shouldLogException($e);

        if ($shouldLog) {
            Log::error('API Exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => $request->user()?->id,
            ]);
        }

        // Handle specific exception types
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'AUTHENTICATION_REQUIRED'
            ], 401);
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'error_code' => 'INSUFFICIENT_PERMISSIONS'
            ], 403);
        }

        if ($e instanceof \Illuminate\Database\QueryException) {
            Log::error('Database Query Exception', [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $isProduction ? 'Database error occurred' : $e->getMessage(),
                'error_code' => 'DATABASE_ERROR'
            ], 500);
        }

        // Generic error response
        return response()->json([
            'success' => false,
            'message' => $isProduction ? 'An unexpected error occurred' : $e->getMessage(),
            'error_code' => 'INTERNAL_SERVER_ERROR',
            'debug' => $isProduction ? null : [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]
        ], 500);
    }

    /**
     * Determine if an exception should be logged
     */
    private function shouldLogException(Throwable $e): bool
    {
        // Don't log validation exceptions
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return false;
        }

        // Don't log authentication exceptions
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return false;
        }

        // Log all other exceptions
        return true;
    }
}
