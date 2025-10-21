<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Basic health check endpoint
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ]);
    }

    /**
     * Detailed health check with system status
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        // Only check Redis if it's configured
        if (config('cache.default') === 'redis' || config('queue.default') === 'redis') {
            $checks['redis'] = $this->checkRedis();
        }

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $queryTime = $this->measureQueryTime(function() {
                DB::select('SELECT 1');
            });

            return [
                'status' => 'healthy',
                'response_time_ms' => $queryTime,
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'connection' => config('database.default'),
            ];
        }
    }

    /**
     * Check cache connectivity
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = 'health_check_' . uniqid();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            $responseTime = (microtime(true) - $start) * 1000;

            return [
                'status' => $value === 'test' ? 'healthy' : 'unhealthy',
                'response_time_ms' => round($responseTime, 2),
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'driver' => config('cache.default'),
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            $key = 'health_check_redis_' . uniqid();
            Redis::setex($key, 10, 'test');
            $value = Redis::get($key);
            Redis::del($key);
            $responseTime = (microtime(true) - $start) * 1000;

            return [
                'status' => $value === 'test' ? 'healthy' : 'unhealthy',
                'response_time_ms' => round($responseTime, 2),
                'host' => config('database.redis.default.host', 'localhost'),
                'port' => config('database.redis.default.port', 6379),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'host' => config('database.redis.default.host', 'localhost'),
                'port' => config('database.redis.default.port', 6379),
            ];
        }
    }

    /**
     * Measure query execution time
     */
    private function measureQueryTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }
}
