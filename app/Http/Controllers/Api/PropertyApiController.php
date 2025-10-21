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
            $categoryId = $request->get('category_id');
            $locationId = $request->get('location_id');
            $minPrice = $request->get('min_price');
            $maxPrice = $request->get('max_price');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = Property::with(['category', 'location'])
                ->where('status', $request->status);

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('property_name', 'like', "%{$search}%")
                      ->orWhere('details', 'like', "%{$search}%");
                });
            }

            // Apply category filter
            if ($categoryId) {
                $query->where('category_id', $categoryId);
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
            $categoryId = $request->get('category_id');
            $locationId = $request->get('location_id');
            $status = $request->get('status');
            $minPrice = $request->get('min_price');
            $maxPrice = $request->get('max_price');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = Property::with(['category', 'location'])
                ->where('is_featured', true);

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('property_name', 'like', "%{$search}%")
                      ->orWhere('details', 'like', "%{$search}%");
                });
            }

            // Apply category filter
            if ($categoryId) {
                $query->where('category_id', $categoryId);
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
     * Get client's rented properties (existing and recent)
     * 
     * This endpoint allows authenticated clients to view all their rental properties
     * including active rentals and rental history with comprehensive filtering and pagination.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getClientRentedProperties(Request $request): JsonResponse
    {
        try {
            // Get authenticated client
            $client = $request->user();

            // Check if client account is active
            if (!$client || !$client->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support for assistance.'
                ], 403);
            }

            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|string|in:active,pending,expired,terminated,ended,all',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
                'sort_by' => 'nullable|string|in:start_date,end_date,monthly_rent,created_at,property_name',
                'sort_order' => 'nullable|string|in:asc,desc',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get request parameters with defaults
            $status = $request->get('status', 'all');
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            // Build query with eager loading for performance
            $query = Rented::with([
                'property' => function ($query) {
                    $query->select('id', 'property_name', 'estimated_monthly', 'images', 'details', 'status', 'category_id', 'location_id', 'lot_area', 'floor_area');
                },
                'property.category' => function ($query) {
                    $query->select('id', 'category_name', 'description');
                },
                'property.location' => function ($query) {
                    $query->select('id', 'location_name', 'location_address');
                }
            ])
            ->where('client_id', $client->id);

            // Apply status filter
            if ($status !== 'all') {
                $query->where('status', $status);
            }

            // Apply search filter (search in property name, notes, terms)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('notes', 'like', "%{$search}%")
                      ->orWhere('terms_conditions', 'like', "%{$search}%")
                      ->orWhereHas('property', function ($propertyQuery) use ($search) {
                          $propertyQuery->where('property_name', 'like', "%{$search}%")
                                       ->orWhere('details', 'like', "%{$search}%");
                      });
                });
            }

            // Apply date range filter
            if ($dateFrom) {
                $query->where('start_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('start_date', '<=', $dateTo);
            }

            // Apply sorting
            if ($sortBy === 'property_name') {
                // Join with properties table for sorting by property name
                $query->join('properties', 'rented.property_id', '=', 'properties.id')
                      ->select('rented.*')
                      ->orderBy('properties.property_name', $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Execute query with pagination
            $rentals = $query->paginate($perPage);

            // Transform rental data for response with proper null safety
            $transformedRentals = $rentals->map(function ($rental) {
                // Check if property exists, if not, skip or provide default data
                if (!$rental->property) {
                    \Log::warning('Rental record found without associated property', [
                        'rental_id' => $rental->id,
                        'property_id' => $rental->property_id
                    ]);
                    
                    return [
                        'id' => $rental->id,
                        'property' => null,
                        'rental_details' => [
                            'monthly_rent' => (float) $rental->monthly_rent,
                            'formatted_monthly_rent' => $rental->formatted_monthly_rent ?? '₱0.00',
                            'security_deposit' => $rental->security_deposit ? (float) $rental->security_deposit : null,
                            'formatted_security_deposit' => $rental->formatted_security_deposit ?? 'N/A',
                            'start_date' => $rental->start_date?->format('Y-m-d'),
                            'end_date' => $rental->end_date?->format('Y-m-d'),
                            'status' => $rental->status,
                            'remarks' => $rental->remarks ?? 'No remarks',
                            'is_active' => false,
                            'is_expired' => true,
                            'remaining_days' => null,
                            'total_duration_days' => 0,
                            'contract_signed_at' => $rental->contract_signed_at?->format('Y-m-d H:i:s'),
                        ],
                        'terms_conditions' => $rental->terms_conditions,
                        'notes' => $rental->notes,
                        'documents' => $rental->documents ?? [],
                        'created_at' => $rental->created_at?->format('Y-m-d H:i:s'),
                        'updated_at' => $rental->updated_at?->format('Y-m-d H:i:s'),
                    ];
                }

                return [
                    'id' => $rental->id,
                    'property' => [
                        'id' => $rental->property->id ?? null,
                        'name' => $rental->property->property_name ?? 'N/A',
                        'estimated_monthly' => $rental->property->estimated_monthly ? (float) $rental->property->estimated_monthly : null,
                        'images' => $rental->property->images ?? [],
                        'details' => $rental->property->details ?? null,
                        'status' => $rental->property->status ?? null,
                        'lot_area' => $rental->property->lot_area ? (float) $rental->property->lot_area : null,
                        'floor_area' => $rental->property->floor_area ? (float) $rental->property->floor_area : null,
                        'category' => [
                            'id' => $rental->property->category->id ?? null,
                            'name' => $rental->property->category->category_name ?? null,
                            'description' => $rental->property->category->description ?? null,
                        ],
                        'location' => [
                            'id' => $rental->property->location->id ?? null,
                            'name' => $rental->property->location->location_name ?? null,
                            'address' => $rental->property->location->location_address ?? null,
                        ],
                    ],
                    'rental_details' => [
                        'monthly_rent' => (float) $rental->monthly_rent,
                        'formatted_monthly_rent' => $rental->formatted_monthly_rent ?? '₱0.00',
                        'security_deposit' => $rental->security_deposit ? (float) $rental->security_deposit : null,
                        'formatted_security_deposit' => $rental->formatted_security_deposit ?? 'N/A',
                        'start_date' => $rental->start_date?->format('Y-m-d'),
                        'end_date' => $rental->end_date?->format('Y-m-d'),
                        'status' => $rental->status,
                        'remarks' => $rental->remarks ?? 'No remarks',
                        'is_active' => $rental->isActive(),
                        'is_expired' => $rental->isExpired(),
                        'remaining_days' => $rental->remaining_days,
                        'total_duration_days' => $rental->total_duration ?? 0,
                        'contract_signed_at' => $rental->contract_signed_at?->format('Y-m-d H:i:s'),
                    ],
                    'terms_conditions' => $rental->terms_conditions,
                    'notes' => $rental->notes,
                    'documents' => $rental->documents ?? [],
                    'created_at' => $rental->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $rental->updated_at?->format('Y-m-d H:i:s'),
                ];
            });

            // Get rental statistics for the client
            $stats = [
                'total_rentals' => $client->rentals()->count(),
                'active_rentals' => $client->activeRentals()->count(),
                'pending_rentals' => $client->rentals()->where('status', 'pending')->count(),
                'expired_rentals' => $client->rentals()->whereIn('status', ['expired', 'ended'])->count(),
                'terminated_rentals' => $client->rentals()->where('status', 'terminated')->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Rented properties retrieved successfully',
                'data' => [
                    'rentals' => $transformedRentals,
                    'statistics' => $stats,
                    'pagination' => [
                        'current_page' => $rentals->currentPage(),
                        'per_page' => $rentals->perPage(),
                        'total' => $rentals->total(),
                        'last_page' => $rentals->lastPage(),
                        'from' => $rentals->firstItem(),
                        'to' => $rentals->lastItem(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            // Log the error for debugging in production
            \Log::error('Failed to retrieve client rented properties', [
                'client_id' => $request->user()?->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rented properties. Please try again later.',
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ] : 'Internal server error'
            ], 500);
        }
    }
}
