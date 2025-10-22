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

            $query = Property::with(['category', 'location'])
                ->where('status', $request->status);

            // Apply comprehensive search filter
            // Search across property name, details, location name, and category name
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('property_name', 'like', "%{$search}%")
                      ->orWhere('details', 'like', "%{$search}%")
                      ->orWhereHas('location', function ($locationQuery) use ($search) {
                          $locationQuery->where('name', 'like', "%{$search}%")
                                       ->orWhere('address', 'like', "%{$search}%");
                      })
                      ->orWhereHas('category', function ($categoryQuery) use ($search) {
                          $categoryQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Apply category filter (by ID or by name)
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            } elseif ($category) {
                $query->whereHas('category', function ($categoryQuery) use ($category) {
                    $categoryQuery->where('name', 'like', "%{$category}%");
                });
            }

            // Apply location filter
            if ($locationId) {
                $query->where('location_id', $locationId);
            }

            // Apply price range filter
            if ($minPrice !== null) {
                $query->where('estimated_monthly', '>=', $minPrice);
            }

            if ($maxPrice !== null) {
                $query->where('estimated_monthly', '<=', $maxPrice);
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortOrder);

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

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve properties',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
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

            $query = Property::with(['category', 'location'])
                ->where('is_featured', true);

            // Apply comprehensive search filter
            // Search across property name, details, location name, and category name
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('property_name', 'like', "%{$search}%")
                      ->orWhere('details', 'like', "%{$search}%")
                      ->orWhereHas('location', function ($locationQuery) use ($search) {
                          $locationQuery->where('name', 'like', "%{$search}%")
                                       ->orWhere('address', 'like', "%{$search}%");
                      })
                      ->orWhereHas('category', function ($categoryQuery) use ($search) {
                          $categoryQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Apply category filter (by ID or by name)
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            } elseif ($category) {
                $query->whereHas('category', function ($categoryQuery) use ($category) {
                    $categoryQuery->where('name', 'like', "%{$category}%");
                });
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
            if ($minPrice !== null) {
                $query->where('estimated_monthly', '>=', $minPrice);
            }

            if ($maxPrice !== null) {
                $query->where('estimated_monthly', '<=', $maxPrice);
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortOrder);

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

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured properties',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
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

            $query = Property::with(['category', 'location']);

            // Apply comprehensive search filter
            // Search across property name, details, location name, and category name
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('property_name', 'like', "%{$search}%")
                      ->orWhere('details', 'like', "%{$search}%")
                      ->orWhereHas('location', function ($locationQuery) use ($search) {
                          $locationQuery->where('name', 'like', "%{$search}%")
                                       ->orWhere('address', 'like', "%{$search}%");
                      })
                      ->orWhereHas('category', function ($categoryQuery) use ($search) {
                          $categoryQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Apply category filter (by ID or by name)
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            } elseif ($category) {
                $query->whereHas('category', function ($categoryQuery) use ($category) {
                    $categoryQuery->where('name', 'like', "%{$category}%");
                });
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
            if ($minPrice !== null) {
                $query->where('estimated_monthly', '>=', $minPrice);
            }

            if ($maxPrice !== null) {
                $query->where('estimated_monthly', '<=', $maxPrice);
            }

            // Apply sorting (default: latest first)
            $query->orderBy($sortBy, $sortOrder);

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

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve all properties',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get a single property by ID with detailed information
     */
    public function getPropertyById(Request $request, $id): JsonResponse
    {
        try {
            $property = Property::with(['category', 'location'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Property retrieved successfully',
                'data' => $property
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
                'error' => 'The requested property does not exist'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve property',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get related properties by category ID
     * Returns properties in the same category, excluding the current property
     */
    public function getRelatedPropertiesByCategory(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string|in:Available,Rented,Under Maintenance,Sold',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // First, get the property to find its category_id
            $property = Property::findOrFail($id);
            
            if (!$property->category_id) {
                return response()->json([
                    'success' => true,
                    'message' => 'No category assigned to this property',
                    'data' => [
                        'properties' => [],
                        'pagination' => [
                            'current_page' => 1,
                            'per_page' => 0,
                            'total' => 0,
                            'last_page' => 1,
                            'from' => null,
                            'to' => null,
                        ]
                    ]
                ], 200);
            }

            $perPage = $request->get('per_page', 6);
            $status = $request->get('status', 'Available'); // Default to Available properties

            // Query for related properties in the same category
            $query = Property::with(['category', 'location'])
                ->where('category_id', $property->category_id)
                ->where('id', '!=', $id); // Exclude current property

            // Apply status filter if provided
            if ($status) {
                $query->where('status', $status);
            }

            // Order by featured first, then by latest
            $query->orderBy('is_featured', 'desc')
                  ->orderBy('created_at', 'desc');

            $relatedProperties = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Related properties retrieved successfully',
                'data' => [
                    'properties' => $relatedProperties->items(),
                    'category' => $property->category,
                    'pagination' => [
                        'current_page' => $relatedProperties->currentPage(),
                        'per_page' => $relatedProperties->perPage(),
                        'total' => $relatedProperties->total(),
                        'last_page' => $relatedProperties->lastPage(),
                        'from' => $relatedProperties->firstItem(),
                        'to' => $relatedProperties->lastItem(),
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
                'error' => 'The requested property does not exist'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve related properties',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
