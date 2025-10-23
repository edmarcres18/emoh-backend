<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Property;
use App\Models\Category;
use App\Models\Locations;
use App\Models\SiteSetting;
use App\Models\Rented;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard with comprehensive data.
     */
    public function index()
    {
        $user = Auth::user();
        $isSystemAdmin = $user->hasRole('System Admin');
        $isAdmin = $user->hasRole(['System Admin', 'Admin']);

        // Base dashboard data available to all authenticated users
        $dashboardData = [
            'user' => $user->load('roles'),
            'stats' => $this->getBasicStats(),
            'recentActivities' => $this->getRecentActivities(),
            'quickActions' => $this->getQuickActions($user),
        ];

        // Add admin-specific data
        if ($isAdmin) {
            $dashboardData['adminStats'] = $this->getAdminStats();
            $dashboardData['propertyPerformance'] = $this->getPropertyPerformance();
            $dashboardData['locationStats'] = $this->getLocationStats();
            $dashboardData['categoryStats'] = $this->getCategoryStats();
            $dashboardData['rentedStats'] = $this->getRentedStats();
            $dashboardData['recentRentals'] = $this->getRecentRentals();
            $dashboardData['clientStats'] = $this->getClientStats();
            $dashboardData['recentClients'] = $this->getRecentClients();
        }

        // Add system admin-specific data
        if ($isSystemAdmin) {
            $dashboardData['systemStats'] = $this->getSystemStats();
            $dashboardData['systemLogs'] = $this->getSystemLogs();
            $dashboardData['backupStatus'] = $this->getBackupStatus();
            $dashboardData['siteSettingsOverview'] = $this->getSiteSettingsOverview();
        }

        return Inertia::render('Dashboard', $dashboardData);
    }

    /**
     * API endpoint to refresh dashboard data.
     */
    public function refresh(Request $request)
    {
        $user = Auth::user();
        $isSystemAdmin = $user->hasRole('System Admin');
        $isAdmin = $user->hasRole(['System Admin', 'Admin']);

        // Clear relevant caches
        Cache::forget('dashboard.basic_stats');
        Cache::forget('dashboard.admin_stats');
        Cache::forget('dashboard.system_stats');
        Cache::forget('dashboard.property_performance');
        Cache::forget('dashboard.location_stats');
        Cache::forget('dashboard.category_stats');
        Cache::forget('dashboard.rented_stats');
        Cache::forget('dashboard.recent_rentals');
        Cache::forget('dashboard.client_stats');
        Cache::forget('dashboard.recent_clients');
        Cache::forget('dashboard.site_settings_overview');

        $responseData = [
            'stats' => $this->getBasicStats(),
            'recentActivities' => $this->getRecentActivities(),
        ];

        if ($isAdmin) {
            $responseData['adminStats'] = $this->getAdminStats();
            $responseData['propertyPerformance'] = $this->getPropertyPerformance();
            $responseData['locationStats'] = $this->getLocationStats();
            $responseData['categoryStats'] = $this->getCategoryStats();
            $responseData['rentedStats'] = $this->getRentedStats();
            $responseData['recentRentals'] = $this->getRecentRentals();
            $responseData['clientStats'] = $this->getClientStats();
            $responseData['recentClients'] = $this->getRecentClients();
        }

        if ($isSystemAdmin) {
            $responseData['systemStats'] = $this->getSystemStats();
            $responseData['systemLogs'] = $this->getSystemLogs();
            $responseData['backupStatus'] = $this->getBackupStatus();
            $responseData['siteSettingsOverview'] = $this->getSiteSettingsOverview();
        }

        return response()->json($responseData);
    }

    /**
     * Export dashboard data as JSON.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $isSystemAdmin = $user->hasRole('System Admin');
        $isAdmin = $user->hasRole(['System Admin', 'Admin']);

        if (!$isAdmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $exportData = [
            'exported_at' => Carbon::now()->toISOString(),
            'exported_by' => $user->name,
            'basic_stats' => $this->getBasicStats(),
            'admin_stats' => $isAdmin ? $this->getAdminStats() : null,
            'property_performance' => $isAdmin ? $this->getPropertyPerformance() : null,
            'location_stats' => $isAdmin ? $this->getLocationStats() : null,
            'category_stats' => $isAdmin ? $this->getCategoryStats() : null,
            'rented_stats' => $isAdmin ? $this->getRentedStats() : null,
            'client_stats' => $isAdmin ? $this->getClientStats() : null,
            'system_stats' => $isSystemAdmin ? $this->getSystemStats() : null,
        ];

        $filename = 'dashboard-export-' . Carbon::now()->format('Y-m-d-H-i-s') . '.json';

        return response()->json($exportData)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    private function getBasicStats()
    {
        return Cache::remember('dashboard.basic_stats', 300, function () {
            $totalProperties = Property::count();
            $featuredProperties = Property::where('is_featured', true)->count();
            $availableProperties = Property::where('status', 'Available')->count();
            $rentedProperties = Property::where('status', 'Rented')->count();
            $totalCategories = Category::count();
            $totalLocations = Locations::count();

            // Calculate trends
            $propertiesThisMonth = Property::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();
            $propertiesLastMonth = Property::whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->count();
            
            $propertyGrowth = $propertiesLastMonth > 0 
                ? round((($propertiesThisMonth - $propertiesLastMonth) / $propertiesLastMonth) * 100, 2) 
                : 0;

            $featuredThisMonth = Property::where('is_featured', true)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();
            $featuredLastMonth = Property::where('is_featured', true)
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->count();
            
            $featuredGrowth = $featuredLastMonth > 0 
                ? round((($featuredThisMonth - $featuredLastMonth) / $featuredLastMonth) * 100, 2) 
                : ($featuredThisMonth > 0 ? 100 : 0);

            return [
                'total_properties' => $totalProperties,
                'featured_properties' => $featuredProperties,
                'available_properties' => $availableProperties,
                'rented_properties' => $rentedProperties,
                'total_categories' => $totalCategories,
                'total_locations' => $totalLocations,
                'properties_this_month' => $propertiesThisMonth,
                'properties_last_month' => $propertiesLastMonth,
                'property_growth' => $propertyGrowth,
                'featured_growth' => $featuredGrowth,
            ];
        });
    }

    private function getAdminStats()
    {
        return Cache::remember('dashboard.admin_stats', 300, function () {
            $totalRevenue = Property::sum('estimated_monthly');
            $averagePrice = Property::avg('estimated_monthly');
            $propertiesThisMonth = Property::whereMonth('created_at', Carbon::now()->month)->count();
            $propertiesLastMonth = Property::whereMonth('created_at', Carbon::now()->subMonth()->month)->count();

            $monthlyGrowth = $propertiesLastMonth > 0
                ? (($propertiesThisMonth - $propertiesLastMonth) / $propertiesLastMonth) * 100
                : 0;

            // Calculate revenue growth
            $revenueThisMonth = Property::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('estimated_monthly');
            $revenueLastMonth = Property::whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->sum('estimated_monthly');

            $revenueGrowth = $revenueLastMonth > 0
                ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100
                : 0;

            return [
                'total_revenue' => $totalRevenue,
                'average_price' => $averagePrice,
                'properties_this_month' => $propertiesThisMonth,
                'monthly_growth' => round($monthlyGrowth, 2),
                'revenue_growth' => round($revenueGrowth, 2),
                'occupancy_rate' => $this->calculateOccupancyRate(),
                'top_performing_locations' => $this->getTopPerformingLocations(),
                'featured_properties' => Property::where('is_featured', true)->count(),
                'available_properties' => Property::where('status', 'Available')->count(),
                'rented_properties' => Property::where('status', 'Rented')->count(),
                'total_categories' => Category::count(),
                'total_locations' => Locations::count(),
            ];
        });
    }

    private function getSystemStats()
    {
        return Cache::remember('dashboard.system_stats', 300, function () {
            $totalUsers = User::count();
            $activeUsers = User::whereNotNull('email_verified_at')->count();
            $systemAdmins = User::role('System Admin')->count();
            $admins = User::role('Admin')->count();
            $totalClients = Client::count();
            $activeClients = Client::where('is_active', true)->count();

            return [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'system_admins' => $systemAdmins,
                'admins' => $admins,
                'total_clients' => $totalClients,
                'active_clients' => $activeClients,
                'database_size' => $this->getDatabaseSize(),
                'storage_usage' => $this->getStorageUsage(),
                'cache_size' => $this->getCacheSize(),
                'last_backup' => $this->getLastBackupDate(),
                'users_this_month' => User::whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count(),
                'clients_this_month' => Client::whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count(),
                'system_uptime' => $this->getSystemUptime(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ];
        });
    }

    private function getRecentActivities()
    {
        $activities = collect();

        // Recent properties
        $recentProperties = Property::with(['category', 'location'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($property) {
                return [
                    'id' => $property->id,
                    'type' => 'property_added',
                    'title' => 'New property listed',
                    'description' => $property->property_name,
                    'time' => $property->created_at->diffForHumans(),
                    'icon' => 'home',
                    'status' => 'success',
                    'metadata' => [
                        'category' => $property->category->name ?? 'N/A',
                        'location' => $property->location->name ?? 'N/A',
                        'price' => $property->estimated_monthly,
                    ]
                ];
            });

        $activities = $activities->concat($recentProperties);

        // Recent rentals (if admin)
        if (Auth::user()->hasRole(['System Admin', 'Admin'])) {
            $recentRentals = Rented::with(['client', 'property'])
                ->latest()
                ->take(3)
                ->get()
                ->map(function ($rental) {
                    return [
                        'id' => $rental->id,
                        'type' => 'rental_created',
                        'title' => 'New rental agreement',
                        'description' => $rental->property->property_name ?? 'Property',
                        'time' => $rental->created_at->diffForHumans(),
                        'icon' => 'fileText',
                        'status' => $rental->status === 'active' ? 'success' : 'warning',
                        'metadata' => [
                            'client' => $rental->client->name ?? 'Unknown',
                            'monthly_rent' => $rental->monthly_rent,
                            'status' => $rental->status,
                        ]
                    ];
                });

            $activities = $activities->concat($recentRentals);

            // Recent users
            $recentUsers = User::latest()
                ->take(2)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'type' => 'user_registered',
                        'title' => 'New user registered',
                        'description' => $user->name,
                        'time' => $user->created_at->diffForHumans(),
                        'icon' => 'userPlus',
                        'status' => 'info',
                        'metadata' => [
                            'email' => $user->email,
                            'verified' => $user->email_verified_at ? 'Yes' : 'No',
                        ]
                    ];
                });

            $activities = $activities->concat($recentUsers);

            // Recent clients
            $recentClients = Client::latest()
                ->take(2)
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'type' => 'client_registered',
                        'title' => 'New client registered',
                        'description' => $client->name,
                        'time' => $client->created_at->diffForHumans(),
                        'icon' => 'users',
                        'status' => $client->email_verified_at ? 'success' : 'warning',
                        'metadata' => [
                            'email' => $client->email,
                            'verified' => $client->email_verified_at ? 'Yes' : 'No',
                        ]
                    ];
                });

            $activities = $activities->concat($recentClients);
        }

        return $activities->sortByDesc('time')->take(10)->values();
    }

    private function getQuickActions($user)
    {
        $actions = [
            [
                'title' => 'Add Property',
                'description' => 'List a new property',
                'icon' => 'plus',
                'color' => 'bg-blue-500 hover:bg-blue-600',
                'href' => '/properties/create',
                'permission' => 'create properties'
            ],
            [
                'title' => 'View Properties',
                'description' => 'Browse all listings',
                'icon' => 'home',
                'color' => 'bg-green-500 hover:bg-green-600',
                'href' => '/properties',
                'permission' => 'view properties'
            ],
            [
                'title' => 'Categories',
                'description' => 'Manage property categories',
                'icon' => 'tag',
                'color' => 'bg-purple-500 hover:bg-purple-600',
                'href' => '/categories',
                'permission' => 'view categories'
            ],
            [
                'title' => 'Locations',
                'description' => 'Manage locations',
                'icon' => 'mapPin',
                'color' => 'bg-indigo-500 hover:bg-indigo-600',
                'href' => '/locations',
                'permission' => 'view locations'
            ],
        ];

        if ($user->hasRole(['System Admin', 'Admin'])) {
            $actions[] = [
                'title' => 'Manage Rentals',
                'description' => 'Rental agreements',
                'icon' => 'fileText',
                'color' => 'bg-emerald-500 hover:bg-emerald-600',
                'href' => '/admin/rented',
                'permission' => 'manage rentals'
            ];

            $actions[] = [
                'title' => 'Manage Clients',
                'description' => 'Client administration',
                'icon' => 'users',
                'color' => 'bg-orange-500 hover:bg-orange-600',
                'href' => '/admin/clients',
                'permission' => 'manage clients'
            ];

            $actions[] = [
                'title' => 'Manage Users',
                'description' => 'User administration',
                'icon' => 'userCog',
                'color' => 'bg-cyan-500 hover:bg-cyan-600',
                'href' => '/admin/users',
                'permission' => 'manage users'
            ];
        }

        if ($user->hasRole('System Admin')) {
            $actions[] = [
                'title' => 'Site Settings',
                'description' => 'System configuration',
                'icon' => 'settings',
                'color' => 'bg-gray-500 hover:bg-gray-600',
                'href' => '/admin/site-settings',
                'permission' => 'manage site settings'
            ];

            $actions[] = [
                'title' => 'Database Backup',
                'description' => 'Backup management',
                'icon' => 'database',
                'color' => 'bg-red-500 hover:bg-red-600',
                'href' => '/admin/database-backup',
                'permission' => 'manage backups'
            ];

            $actions[] = [
                'title' => 'Roles & Permissions',
                'description' => 'Access control',
                'icon' => 'shield',
                'color' => 'bg-yellow-500 hover:bg-yellow-600',
                'href' => '/admin/roles',
                'permission' => 'manage roles'
            ];
        }

        return $actions;
    }

    private function getPropertyPerformance()
    {
        return Cache::remember('dashboard.property_performance', 600, function () {
            $months = collect();
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $monthData = [
                    'month' => $date->format('M'),
                    'year' => $date->format('Y'),
                    'properties' => Property::whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->count(),
                    'revenue' => Property::whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->sum('estimated_monthly'),
                    'featured' => Property::whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->where('is_featured', true)
                        ->count(),
                ];
                $months->push($monthData);
            }
            return $months;
        });
    }

    private function getLocationStats()
    {
        return Cache::remember('dashboard.location_stats', 600, function () {
            return Locations::withCount('properties')
                ->get()
                ->map(function ($location) {
                    $avgPrice = Property::where('location_id', $location->id)
                        ->avg('estimated_monthly') ?? 0;
                    $totalRevenue = Property::where('location_id', $location->id)
                        ->sum('estimated_monthly') ?? 0;
                    
                    return [
                        'name' => $location->name,
                        'code' => $location->code,
                        'properties_count' => $location->properties_count,
                        'avg_price' => round($avgPrice, 2),
                        'total_revenue' => round($totalRevenue, 2),
                    ];
                })
                ->sortByDesc('properties_count')
                ->take(10)
                ->values();
        });
    }

    private function getCategoryStats()
    {
        return Cache::remember('dashboard.category_stats', 600, function () {
            return Category::withCount('properties')
                ->orderByDesc('properties_count')
                ->get()
                ->map(function ($category) {
                    $avgPrice = Property::where('category_id', $category->id)
                        ->avg('estimated_monthly') ?? 0;
                    $totalRevenue = Property::where('category_id', $category->id)
                        ->sum('estimated_monthly') ?? 0;
                    
                    return [
                        'name' => $category->name,
                        'description' => $category->description,
                        'properties_count' => $category->properties_count,
                        'avg_price' => round($avgPrice, 2),
                        'total_revenue' => round($totalRevenue, 2),
                    ];
                });
        });
    }

    private function getSystemLogs()
    {
        // This would typically read from Laravel's log files
        // For now, returning mock data - in production, you'd parse actual log files
        return [
            [
                'level' => 'info',
                'message' => 'User login successful',
                'context' => ['user_id' => Auth::id()],
                'timestamp' => Carbon::now()->subMinutes(5),
            ],
            [
                'level' => 'warning',
                'message' => 'High memory usage detected',
                'context' => ['memory_usage' => '85%'],
                'timestamp' => Carbon::now()->subHours(2),
            ],
            [
                'level' => 'error',
                'message' => 'Database connection timeout',
                'context' => ['connection' => 'mysql'],
                'timestamp' => Carbon::now()->subHours(6),
            ],
        ];
    }

    private function getBackupStatus()
    {
        $backupPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'database');

        if (!File::exists($backupPath)) {
            return [
                'last_backup' => null,
                'backup_size' => '0 MB',
                'status' => 'no_backups',
                'next_scheduled' => Carbon::now()->addHours(18),
                'retention_days' => 30,
            ];
        }

        $files = File::files($backupPath);
        $backupFiles = collect($files)->filter(function ($file) {
            return str_ends_with($file->getFilename(), '.sql');
        });

        if ($backupFiles->isEmpty()) {
            return [
                'last_backup' => null,
                'backup_size' => '0 MB',
                'status' => 'no_backups',
                'next_scheduled' => Carbon::now()->addHours(18),
                'retention_days' => 30,
            ];
        }

        $latestBackup = $backupFiles->sortByDesc(function ($file) {
            return $file->getMTime();
        })->first();

        $totalSize = $backupFiles->sum(function ($file) {
            return $file->getSize();
        });

        return [
            'last_backup' => Carbon::createFromTimestamp($latestBackup->getMTime()),
            'backup_size' => $this->formatBytes($totalSize),
            'status' => 'success',
            'next_scheduled' => Carbon::now()->addHours(18),
            'retention_days' => 30,
            'backup_count' => $backupFiles->count(),
            'latest_filename' => $latestBackup->getFilename(),
        ];
    }

    private function getSiteSettingsOverview()
    {
        return Cache::remember('dashboard.site_settings_overview', 1800, function () {
            $settings = SiteSetting::first();
            return [
                'site_name' => $settings->site_name ?? 'EMOH',
                'maintenance_mode' => $settings->maintenance_mode ?? false,
                'analytics_enabled' => !empty($settings->google_analytics_id),
                'logo_configured' => !empty($settings->logo),
                'favicon_configured' => !empty($settings->favicon),
            ];
        });
    }

    // Helper methods
    private function calculateOccupancyRate()
    {
        $totalProperties = Property::count();
        $occupiedProperties = Property::where('status', 'occupied')->count();
        return $totalProperties > 0 ? round(($occupiedProperties / $totalProperties) * 100, 2) : 0;
    }

    private function getTopPerformingLocations()
    {
        return Locations::withCount('properties')
            ->orderByDesc('properties_count')
            ->take(5)
            ->pluck('name', 'properties_count');
    }

    private function getDatabaseSize()
    {
        try {
            $size = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = ?", [config('database.connections.mysql.database')]);
            return $size[0]->{'DB Size in MB'} . ' MB';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function getStorageUsage()
    {
        $path = storage_path();
        $bytes = disk_total_space($path) - disk_free_space($path);
        return $this->formatBytes($bytes);
    }

    private function getCacheSize()
    {
        try {
            return Cache::get('cache_size', '0 MB');
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function getLastBackupDate()
    {
        // This would check your actual backup system
        return Carbon::now()->subHours(6);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function getSystemUptime()
    {
        try {
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                return 'Load: ' . round($load[0], 2);
            }
            return 'N/A';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function getRentedStats()
    {
        return Cache::remember('dashboard.rented_stats', 300, function () {
            $activeRentals = Rented::active()->count();
            $totalProperties = Property::count();
            $monthlyRevenue = Rented::active()->sum('monthly_rent');
            $expiringSoon = Rented::active()
                ->where('end_date', '<=', Carbon::now()->addDays(30))
                ->where('end_date', '>=', Carbon::now())
                ->count();

            $occupancyRate = $totalProperties > 0 ? round(($activeRentals / $totalProperties) * 100, 2) : 0;

            return [
                'active_rentals' => $activeRentals,
                'monthly_revenue' => $monthlyRevenue,
                'expiring_soon' => $expiringSoon,
                'occupancy_rate' => $occupancyRate,
                'total_rentals' => Rented::count(),
                'pending_rentals' => Rented::pending()->count(),
                'expired_rentals' => Rented::expired()->count(),
                'terminated_rentals' => Rented::terminated()->count(),
            ];
        });
    }

    private function getRecentRentals()
    {
        return Cache::remember('dashboard.recent_rentals', 300, function () {
            return Rented::with(['client', 'property'])
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($rental) {
                    return [
                        'id' => $rental->id,
                        'status' => $rental->status,
                        'monthly_rent' => $rental->monthly_rent,
                        'start_date' => $rental->start_date,
                        'end_date' => $rental->end_date,
                        'remaining_days' => $rental->remaining_days,
                        'client' => [
                            'id' => $rental->client->id ?? null,
                            'name' => $rental->client->name ?? 'Unknown Client',
                            'email' => $rental->client->email ?? null,
                        ],
                        'property' => [
                            'id' => $rental->property->id ?? null,
                            'title' => $rental->property->property_name ?? 'Unknown Property',
                            'location' => $rental->property->location->name ?? null,
                        ],
                        'created_at' => $rental->created_at,
                    ];
                });
        });
    }

    private function getClientStats()
    {
        return Cache::remember('dashboard.client_stats', 300, function () {
            $totalClients = Client::count();
            $verifiedClients = Client::whereNotNull('email_verified_at')->count();
            $activeClients = Client::where('is_active', true)->count();
            $activeRenters = Client::whereHas('activeRentals')->count();
            $newThisMonth = Client::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();

            return [
                'total_clients' => $totalClients,
                'verified_clients' => $verifiedClients,
                'active_clients' => $activeClients,
                'active_renters' => $activeRenters,
                'new_this_month' => $newThisMonth,
                'pending_verification' => $totalClients - $verifiedClients,
                'google_oauth_users' => Client::whereNotNull('google_id')->count(),
            ];
        });
    }

    private function getRecentClients()
    {
        return Cache::remember('dashboard.recent_clients', 300, function () {
            return Client::withCount('rentals')
                ->latest()
                ->take(12)
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'email_verified_at' => $client->email_verified_at,
                        'is_active' => $client->is_active,
                        'google_id' => $client->google_id,
                        'total_rentals' => $client->rentals_count,
                        'has_active_rentals' => $client->hasActiveRentals(),
                        'created_at' => $client->created_at,
                    ];
                });
        });
    }
}
