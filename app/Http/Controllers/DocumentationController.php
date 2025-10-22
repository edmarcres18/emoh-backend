<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DocumentationController extends Controller
{
    public function data(): JsonResponse
    {
        $dbDriver = DB::getDriverName();

        // Detect tables depending on driver
        $tables = [];
        try {
            if ($dbDriver === 'mysql') {
                $tables = DB::table('information_schema.tables')
                    ->select('table_name as name')
                    ->where('table_schema', DB::getDatabaseName())
                    ->pluck('name')
                    ->map(fn ($t) => (string) $t)
                    ->toArray();
            } elseif ($dbDriver === 'sqlite') {
                $tables = DB::table('sqlite_master')
                    ->select('name')
                    ->where('type', 'table')
                    ->pluck('name')
                    ->map(fn ($t) => (string) $t)
                    ->toArray();
            }
        } catch (\Throwable $e) {
            // Fallback to common tables we expect if metadata query fails
            $tables = array_filter([
                'properties', 'clients', 'rented', 'categories', 'locations', 'users'
            ], fn ($t) => Schema::hasTable($t));
        }

        // Build data models: name (Studly), description placeholder, fields from Schema
        $models = [];
        foreach ($tables as $table) {
            try {
                $columns = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
            } catch (\Throwable $e) {
                $columns = [];
            }

            $models[] = [
                'name' => self::studlyFromTable($table),
                'table' => $table,
                'description' => 'Auto-generated from database schema',
                'fields' => $columns,
                'relationships' => [],
            ];
        }

        // Collect API endpoints from router
        $routes = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (str_starts_with($uri, 'api/') || str_starts_with($uri, 'admin/api')) {
                $routes[] = [
                    'method' => implode('|', $route->methods()),
                    'path' => '/' . ltrim($uri, '/'),
                    'name' => $route->getName(),
                    'action' => is_string($route->getActionName()) ? $route->getActionName() : null,
                ];
            }
        }

        // Group routes by top-level category
        $grouped = [];
        foreach ($routes as $r) {
            $category = str_starts_with($r['path'], '/admin/api') ? 'Admin' : 'Public API';
            $grouped[$category]['category'] = $category;
            $grouped[$category]['endpoints'][] = [
                'method' => $this->normalizeMethod($r['method']),
                'path' => $r['path'],
                'description' => $r['name'] ?? $r['action'] ?? ''
            ];
        }

        $api = array_values($grouped);

        return response()->json([
            'techStack' => [
                ['name' => 'Laravel', 'description' => 'Backend framework', 'icon' => 'server'],
                ['name' => 'Vue 3', 'description' => 'Frontend framework', 'icon' => 'component'],
                ['name' => 'Inertia.js', 'description' => 'SPA bridge', 'icon' => 'link'],
                ['name' => 'Tailwind CSS', 'description' => 'Styling framework', 'icon' => 'palette'],
                ['name' => 'TypeScript', 'description' => 'Type safety', 'icon' => 'code'],
            ],
            'dataModels' => $models,
            'apiEndpoints' => $api,
            'counts' => [
                'features' => 6,
                'models' => count($models),
                'endpoints' => array_sum(array_map(fn ($c) => count($c['endpoints']), $api)),
                'components' => 50,
            ],
        ]);
    }

    private static function studlyFromTable(string $table): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $table)));
    }

    private function normalizeMethod(string $method): string
    {
        // Route::methods() can include HEAD; prefer primary method
        $parts = explode('|', $method);
        foreach (['GET','POST','PUT','PATCH','DELETE'] as $m) {
            if (in_array($m, $parts, true)) return $m;
        }
        return $parts[0] ?? 'GET';
    }
}
