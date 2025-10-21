<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseApiController extends Controller
{
    /**
     * Success response format
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response format
     */
    protected function errorResponse(
        string $message = 'An error occurred',
        int $statusCode = 400,
        string $errorCode = null,
        array $errors = [],
        array $debug = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($debug) && config('app.debug')) {
            $response['debug'] = $debug;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Paginated response format
     */
    protected function paginatedResponse(
        $data,
        string $message = 'Data retrieved successfully',
        array $meta = []
    ): JsonResponse {
        $pagination = [
            'current_page' => $data->currentPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
            'last_page' => $data->lastPage(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),
            'has_more_pages' => $data->hasMorePages(),
        ];

        return $this->successResponse(
            $data->items(),
            $message,
            200,
            array_merge(['pagination' => $pagination], $meta)
        );
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->errorResponse($message, 422, 'VALIDATION_ERROR', $errors);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->errorResponse($message, 404, 'NOT_FOUND');
    }

    /**
     * Unauthorized response
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return $this->errorResponse($message, 401, 'UNAUTHORIZED');
    }

    /**
     * Forbidden response
     */
    protected function forbiddenResponse(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return $this->errorResponse($message, 403, 'FORBIDDEN');
    }

    /**
     * Rate limit response
     */
    protected function rateLimitResponse(
        int $retryAfter,
        string $message = 'Too many requests'
    ): JsonResponse {
        return $this->errorResponse($message, 429, 'RATE_LIMIT_EXCEEDED', [], [
            'retry_after' => $retryAfter
        ]);
    }
}
