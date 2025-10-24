<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Rented;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PropertyApiController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Force JSON responses for all methods
        request()->headers->set('Accept', 'application/json');
    }
    /**
     * Get properties by status with pagination and filtering
     */
    public function getPropertiesByStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Available,Rented,Under Maintenance,Sold',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'location_id' => 'nullable|integer|exists:locations,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|string|in:property_name,estimated_monthly,created_at,updated_at',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $category = $request->get('category');
            $categoryId = $request->get('category_id');
            $locationId = $request->get('location_id');
            $minPrice = $request->get('min_price');
            $maxPrice = $request->get('max_price');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            // Build query with eager loading to prevent N+1 issues
            $query = Property::query();

            // Apply status filter
            $query->where('status', $request->status);

            // Apply comprehensive search filter
            // Search across property name, details, location name, and category name
            if ($search && !empty(trim($search))) {
                $searchTerm = trim($search);
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('property_name', 'like', "%{$searchTerm}%")
                      ->orWhere('details', 'like', "%{$searchTerm}%");

                    // Only search relationships if they exist
                    if (method_exists(Property::class, 'location')) {
                        $q->orWhereHas('location', function ($locationQuery) use ($searchTerm) {
                            $locationQuery->where('name', 'like', "%{$searchTerm}%")
                                         ->orWhere('description', 'like', "%{$searchTerm}%");
                        });
                    }

                    if (method_exists(Property::class, 'category')) {
                        $q->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                            $categoryQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                    }
                });
            }

            // Apply category filter (by ID or by name)
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            } elseif ($category && !empty(trim($category))) {
                $categoryName = trim($category);
                if (method_exists(Property::class, 'category')) {
                    $query->whereHas('category', function ($categoryQuery) use ($categoryName) {
                        $categoryQuery->where('name', 'like', "%{$categoryName}%");
                    });
                }
            }

            // Apply location filter
            if ($locationId) {
                $query->where('location_id', $locationId);
            }

            // Apply price range filter
            if ($minPrice !== null && is_numeric($minPrice)) {
                $query->where('estimated_monthly', '>=', $minPrice);
            }

            if ($maxPrice !== null && is_numeric($maxPrice)) {
                $query->where('estimated_monthly', '<=', $maxPrice);
            }

            // Apply sorting with validation
            $query->orderBy($sortBy, $sortOrder);

            // Load relationships only if they exist
            $with = [];
            if (method_exists(Property::class, 'category')) {
                $with[] = 'category';
            }
            if (method_exists(Property::class, 'location')) {
                $with[] = 'location';
            }
            if (!empty($with)) {
                $query->with($with);
            }

            $properties = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Properties retrieved successfully',
                'data' => [
                    'properties' => $properties->items(),
                    'pagination' => [
                        'current_page' => $properties->currentPage(),
                        'per_page' => $properties->perPage(),
                        'total' => $properties->total(),
                        'last_page' => $properties->lastPage(),
                        'from' => $properties->firstItem(),
                        'to' => $properties->lastItem(),
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error in getPropertiesByStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while retrieving properties',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later',
                'data' => [
                    'properties' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $request->get('per_page', 15),
                        'total' => 0,
                        'last_page' => 1,
                        'from' => null,
                        'to' => null,
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error in getPropertiesByStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve properties',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
                'data' => [
                    'properties' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $request->get('per_page', 15),
                        'total' => 0,
                        'last_page' => 1,
                        'from' => null,
                        'to' => null,
                    ]
                ]
            ], 200);
        }
    }

    /**
     * Get featured properties with pagination and filtering
     */
    public function getFeaturedProperties(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'location_id' => 'nullable|integer|exists:locations,id',
            'status' => 'nullable|string|in:Available,Rented,Under Maintenance,Sold',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|string|in:property_name,estimated_monthly,created_at,updated_at',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $category = $request->get('category');
            $categoryId = $request->get('category_id');
            $locationId = $request->get('location_id');
            $status = $request->get('status');
            $minPrice = $request->get('min_price');
            $maxPrice = $request->get('max_price');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            // Build query with eager loading to prevent N+1 issues
            $query = Property::query();

            // Apply featured filter
            $query->where('is_featured', true);

            // Apply comprehensive search filter
            // Search across property name, details, location name, and category name
            if ($search && !empty(trim($search))) {
                $searchTerm = trim($search);
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('property_name', 'like', "%{$searchTerm}%")
                      ->orWhere('details', 'like', "%{$searchTerm}%");

                    // Only search relationships if they exist
                    if (method_exists(Property::class, 'location')) {
                        $q->orWhereHas('location', function ($locationQuery) use ($searchTerm) {
                            $locationQuery->where('name', 'like', "%{$searchTerm}%")
                                         ->orWhere('description', 'like', "%{$searchTerm}%");
                        });
                    }

                    if (method_exists(Property::class, 'category')) {
                        $q->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                            $categoryQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                    }
                });
            }

            // Apply category filter (by ID or by name)
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            } elseif ($category && !empty(trim($category))) {
                $categoryName = trim($category);
                if (method_exists(Property::class, 'category')) {
                    $query->whereHas('category', function ($categoryQuery) use ($categoryName) {
                        $categoryQuery->where('name', 'like', "%{$categoryName}%");
                    });
                }
            }

            // Apply location filter
            if ($locationId) {
                $query->where('location_id', $locationId);
            }

            // Apply status filter
            if ($status) {
                $query->where('status', $status);
            }

            // Apply price range filter
            if ($minPrice !== null && is_numeric($minPrice)) {
                $query->where('estimated_monthly', '>=', $minPrice);
            }

            if ($maxPrice !== null && is_numeric($maxPrice)) {
                $query->where('estimated_monthly', '<=', $maxPrice);
            }

            // Apply sorting with validation
            $query->orderBy($sortBy, $sortOrder);

            // Load relationships only if they exist
            $with = [];
            if (method_exists(Property::class, 'category')) {
                $with[] = 'category';
            }
            if (method_exists(Property::class, 'location')) {
                $with[] = 'location';
            }
            if (!empty($with)) {
                $query->with($with);
            }

            $properties = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Featured properties retrieved successfully',
                'data' => [
                    'properties' => $properties->items(),
                    'pagination' => [
                        'current_page' => $properties->currentPage(),
                        'per_page' => $properties->perPage(),
                        'total' => $properties->total(),
                        'last_page' => $properties->lastPage(),
                        'from' => $properties->firstItem(),
                        'to' => $properties->lastItem(),
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error in getFeaturedProperties: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while retrieving featured properties',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later',
                'data' => [
                    'properties' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $request->get('per_page', 15),
                        'total' => 0,
                        'last_page' => 1,
                        'from' => null,
                        'to' => null,
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error in getFeaturedProperties: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured properties',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
                'data' => [
                    'properties' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $request->get('per_page', 15),
                        'total' => 0,
                        'last_page' => 1,
                        'from' => null,
                        'to' => null,
                    ]
                ]
            ], 200);
        }
    }

    /**
     * Get property statistics
     */
    public function getPropertyStats(): JsonResponse
    {
        try {
            $stats = [
                'total_properties' => Property::count(),
                'available_properties' => Property::where('status', 'Available')->count(),
                'rented_properties' => Property::where('status', 'Rented')->count(),
                'featured_properties' => Property::where('is_featured', true)->count(),
                'maintenance_properties' => Property::where('status', 'Under Maintenance')->count(),
                'sold_properties' => Property::where('status', 'Sold')->count(),
                'average_monthly_rate' => Property::avg('estimated_monthly'),
                'total_estimated_value' => Property::sum('estimated_monthly'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Property statistics retrieved successfully',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve property statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available property statuses
     */
    public function getAvailableStatuses(): JsonResponse
    {
        try {
            $statuses = ['Available', 'Rented', 'Under Maintenance', 'Sold'];

            return response()->json([
                'success' => true,
                'message' => 'Available statuses retrieved successfully',
                'data' => $statuses
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available statuses',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all properties ordered by latest with pagination and filtering
     */
    public function getAllProperties(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'location_id' => 'nullable|integer|exists:locations,id',
            'status' => 'nullable|string|in:Available,Rented,Under Maintenance,Sold',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|string|in:property_name,estimated_monthly,created_at,updated_at',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $category = $request->get('category');
            $categoryId = $request->get('category_id');
            $locationId = $request->get('location_id');
            $status = $request->get('status');
            $minPrice = $request->get('min_price');
            $maxPrice = $request->get('max_price');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            // Build query with eager loading to prevent N+1 issues
            $query = Property::query();

            // Apply comprehensive search filter
            // Search across property name, details, location name, and category name
            if ($search && !empty(trim($search))) {
                $searchTerm = trim($search);
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('property_name', 'like', "%{$searchTerm}%")
                      ->orWhere('details', 'like', "%{$searchTerm}%");

                    // Only search relationships if they exist
                    if (method_exists(Property::class, 'location')) {
                        $q->orWhereHas('location', function ($locationQuery) use ($searchTerm) {
                            $locationQuery->where('name', 'like', "%{$searchTerm}%")
                                         ->orWhere('description', 'like', "%{$searchTerm}%");
                        });
                    }

                    if (method_exists(Property::class, 'category')) {
                        $q->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                            $categoryQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                    }
                });
            }

            // Apply category filter (by ID or by name)
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            } elseif ($category && !empty(trim($category))) {
                $categoryName = trim($category);
                if (method_exists(Property::class, 'category')) {
                    $query->whereHas('category', function ($categoryQuery) use ($categoryName) {
                        $categoryQuery->where('name', 'like', "%{$categoryName}%");
                    });
                }
            }

            // Apply location filter
            if ($locationId) {
                $query->where('location_id', $locationId);
            }

            // Apply status filter
            if ($status) {
                $query->where('status', $status);
            }

            // Apply price range filter
            if ($minPrice !== null && is_numeric($minPrice)) {
                $query->where('estimated_monthly', '>=', $minPrice);
            }

            if ($maxPrice !== null && is_numeric($maxPrice)) {
                $query->where('estimated_monthly', '<=', $maxPrice);
            }

            // Apply sorting with validation (default: latest first)
            $query->orderBy($sortBy, $sortOrder);

            // Load relationships only if they exist
            $with = [];
            if (method_exists(Property::class, 'category')) {
                $with[] = 'category';
            }
            if (method_exists(Property::class, 'location')) {
                $with[] = 'location';
            }
            if (!empty($with)) {
                $query->with($with);
            }

            $properties = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'All properties retrieved successfully',
                'data' => [
                    'properties' => $properties->items(),
                    'pagination' => [
                        'current_page' => $properties->currentPage(),
                        'per_page' => $properties->perPage(),
                        'total' => $properties->total(),
                        'last_page' => $properties->lastPage(),
                        'from' => $properties->firstItem(),
                        'to' => $properties->lastItem(),
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error in getAllProperties: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while retrieving properties',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later',
                'data' => [
                    'properties' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $request->get('per_page', 15),
                        'total' => 0,
                        'last_page' => 1,
                        'from' => null,
                        'to' => null,
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error in getAllProperties: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve all properties',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
                'data' => [
                    'properties' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $request->get('per_page', 15),
                        'total' => 0,
                        'last_page' => 1,
                        'from' => null,
                        'to' => null,
                    ]
                ]
            ], 200);
        }
    }

    /**
     * Get a single property by ID
     */
    public function getProperty(Request $request, $id): JsonResponse
    {
        try {
            // Validate the ID parameter
            $validator = Validator::make(['id' => $id], [
                'id' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid property ID',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find the property with relationships
            $property = Property::with(['category', 'location', 'images'])
                ->where('id', $id)
                ->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Property retrieved successfully',
                'data' => $property
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching property: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve property',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                'data' => null
            ], 500);
        }
    }
}
