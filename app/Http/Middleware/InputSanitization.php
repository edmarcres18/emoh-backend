<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InputSanitization
{
    /**
     * Handle an incoming request with input sanitization
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize string inputs
        $this->sanitizeInputs($request);

        // Log suspicious inputs
        $this->logSuspiciousInputs($request);

        return $next($request);
    }

    /**
     * Sanitize input data
     */
    private function sanitizeInputs(Request $request): void
    {
        $inputs = $request->all();

        foreach ($inputs as $key => $value) {
            if (is_string($value)) {
                // Remove null bytes and control characters
                $value = str_replace(["\0", "\r"], '', $value);

                // Trim whitespace
                $value = trim($value);

                // Update the request with sanitized value
                $request->merge([$key => $value]);
            }
        }
    }

    /**
     * Log suspicious inputs for security monitoring
     */
    private function logSuspiciousInputs(Request $request): void
    {
        $suspiciousPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/eval\(/i',
            '/expression\(/i',
            '/union\s+select/i',
            '/drop\s+table/i',
            '/delete\s+from/i',
            '/insert\s+into/i',
            '/update\s+set/i',
        ];

        $inputs = $request->all();
        $suspiciousFound = false;
        $suspiciousData = [];

        foreach ($inputs as $key => $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $suspiciousFound = true;
                        $suspiciousData[$key] = $value;
                        break;
                    }
                }
            }
        }

        if ($suspiciousFound) {
            Log::warning('Suspicious input detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'suspicious_data' => $suspiciousData,
                'user_id' => $request->user()?->id,
            ]);
        }
    }
}
