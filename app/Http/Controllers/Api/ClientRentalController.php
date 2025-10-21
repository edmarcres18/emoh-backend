<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rented;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ClientRentalController extends Controller
{
    /**
     * Get authenticated client's rental properties with detailed information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyRentals(Request $request): JsonResponse
    {
        try {
            $client = $request->user();

            // Check if client account is still active
            if (!$client->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support for assistance.'
                ], 403);
            }

            // Build query with eager loading for optimal performance
            $query = Rented::with([
                'property' => function ($query) {
                    $query->select([
                        'id',
                        'property_name',
                        'estimated_monthly',
                        'images',
                        'details',
                        'status',
                        'lot_area',
                        'floor_area',
                        'category_id',
                        'location_id'
                    ]);
                },
                'property.category' => function ($query) {
                    $query->select('id', 'name', 'description');
                },
                'property.location' => function ($query) {
                    $query->select('id', 'name', 'address');
                }
            ])->where('client_id', $client->id);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('property', function ($propertyQuery) use ($search) {
                        $propertyQuery->where('property_name', 'like', "%{$search}%")
                                    ->orWhere('details', 'like', "%{$search}%");
                    })
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('terms_conditions', 'like', "%{$search}%");
                });
            }

            // Date range filter
            if ($request->has('date_from') && $request->date_from) {
                $query->where('start_date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && $request->date_to) {
                $query->where('start_date', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            // Handle property name sorting (requires join)
            if ($sortBy === 'property_name') {
                $query->join('properties', 'rented.property_id', '=', 'properties.id')
                      ->select('rented.*')
                      ->orderBy('properties.property_name', $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 12);
            $rentals = $query->paginate($perPage);

            // Update remarks for all items in the collection
            $rentals->getCollection()->each(function ($item) {
                $item->updateRemarks();
            });

            // Get statistics for this specific client
            $statistics = $this->getClientRentalStatistics($client->id);

            // Transform data to match frontend expectations
            $transformedRentals = $rentals->getCollection()->map(function ($rental) {
                return $this->transformRental($rental);
            });

            return response()->json([
                'success' => true,
                'message' => 'Rentals retrieved successfully',
                'data' => [
                    'rentals' => $transformedRentals,
                    'statistics' => $statistics,
                    'pagination' => [
                        'current_page' => $rentals->currentPage(),
                        'per_page' => $rentals->perPage(),
                        'total' => $rentals->total(),
                        'last_page' => $rentals->lastPage(),
                        'from' => $rentals->firstItem(),
                        'to' => $rentals->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching client rentals: ' . $e->getMessage(), [
                'client_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rentals. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get detailed statistics for a specific client's rentals
     * 
     * @param int $clientId
     * @return array
     */
    private function getClientRentalStatistics(int $clientId): array
    {
        $baseQuery = Rented::where('client_id', $clientId);

        return [
            'total_rentals' => (clone $baseQuery)->count(),
            'active_rentals' => (clone $baseQuery)->where('status', 'active')->count(),
            'pending_rentals' => (clone $baseQuery)->where('status', 'pending')->count(),
            'expired_rentals' => (clone $baseQuery)->where('status', 'expired')->count(),
            'terminated_rentals' => (clone $baseQuery)->where('status', 'terminated')->count(),
        ];
    }

    /**
     * Transform rental data to match frontend expectations
     * 
     * @param Rented $rental
     * @return array
     */
    private function transformRental(Rented $rental): array
    {
        // Ensure property exists
        if (!$rental->property) {
            return [
                'id' => $rental->id,
                'property' => null,
                'rental_details' => $this->transformRentalDetails($rental),
                'terms_conditions' => $rental->terms_conditions,
                'notes' => $rental->notes,
                'documents' => $rental->documents ?? [],
                'created_at' => $rental->created_at?->toIso8601String(),
                'updated_at' => $rental->updated_at?->toIso8601String(),
            ];
        }

        return [
            'id' => $rental->id,
            'property' => [
                'id' => $rental->property->id,
                'name' => $rental->property->property_name,
                'estimated_monthly' => $rental->property->estimated_monthly,
                'images' => $rental->property->images ?? [],
                'details' => $rental->property->details,
                'status' => $rental->property->status,
                'lot_area' => $rental->property->lot_area,
                'floor_area' => $rental->property->floor_area,
                'category' => $rental->property->category ? [
                    'id' => $rental->property->category->id,
                    'name' => $rental->property->category->name,
                    'description' => $rental->property->category->description,
                ] : null,
                'location' => $rental->property->location ? [
                    'id' => $rental->property->location->id,
                    'name' => $rental->property->location->name,
                    'address' => $rental->property->location->address,
                ] : null,
            ],
            'rental_details' => $this->transformRentalDetails($rental),
            'terms_conditions' => $rental->terms_conditions,
            'notes' => $rental->notes,
            'documents' => $rental->documents ?? [],
            'created_at' => $rental->created_at?->toIso8601String(),
            'updated_at' => $rental->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Transform rental details section
     * 
     * @param Rented $rental
     * @return array
     */
    private function transformRentalDetails(Rented $rental): array
    {
        return [
            'monthly_rent' => (float) $rental->monthly_rent,
            'formatted_monthly_rent' => '₱' . number_format($rental->monthly_rent, 2),
            'security_deposit' => $rental->security_deposit ? (float) $rental->security_deposit : null,
            'formatted_security_deposit' => $rental->security_deposit 
                ? '₱' . number_format($rental->security_deposit, 2) 
                : 'N/A',
            'start_date' => $rental->start_date?->format('Y-m-d'),
            'end_date' => $rental->end_date?->format('Y-m-d'),
            'status' => $rental->status,
            'remarks' => $rental->remarks,
            'is_active' => $rental->isActive(),
            'is_expired' => $rental->isExpired(),
            'remaining_days' => $rental->remaining_days,
            'total_duration_days' => $rental->total_duration,
            'contract_signed_at' => $rental->contract_signed_at?->toIso8601String(),
        ];
    }

    /**
     * Get a single rental detail
     * 
     * @param Request $request
     * @param int $rentalId
     * @return JsonResponse
     */
    public function getRentalDetail(Request $request, int $rentalId): JsonResponse
    {
        try {
            $client = $request->user();

            // Check if client account is still active
            if (!$client->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support for assistance.'
                ], 403);
            }

            $rental = Rented::with([
                'property.category',
                'property.location'
            ])
            ->where('client_id', $client->id)
            ->where('id', $rentalId)
            ->first();

            if (!$rental) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rental not found or you do not have permission to view it.'
                ], 404);
            }

            // Update remarks
            $rental->updateRemarks();

            return response()->json([
                'success' => true,
                'message' => 'Rental details retrieved successfully',
                'data' => [
                    'rental' => $this->transformRental($rental)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching rental detail: ' . $e->getMessage(), [
                'client_id' => $request->user()?->id,
                'rental_id' => $rentalId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rental details. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get active rentals only
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getActiveRentals(Request $request): JsonResponse
    {
        $request->merge(['status' => 'active']);
        return $this->getMyRentals($request);
    }

    /**
     * Get rental history (all non-active statuses)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getRentalHistory(Request $request): JsonResponse
    {
        try {
            $client = $request->user();

            // Check if client account is still active
            if (!$client->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support for assistance.'
                ], 403);
            }

            $query = Rented::with([
                'property.category',
                'property.location'
            ])
            ->where('client_id', $client->id)
            ->whereIn('status', ['expired', 'terminated', 'ended']);

            // Sorting
            $sortBy = $request->get('sort_by', 'end_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 12);
            $rentals = $query->paginate($perPage);

            // Update remarks for all items
            $rentals->getCollection()->each(function ($item) {
                $item->updateRemarks();
            });

            // Transform data
            $transformedRentals = $rentals->getCollection()->map(function ($rental) {
                return $this->transformRental($rental);
            });

            return response()->json([
                'success' => true,
                'message' => 'Rental history retrieved successfully',
                'data' => [
                    'rentals' => $transformedRentals,
                    'pagination' => [
                        'current_page' => $rentals->currentPage(),
                        'per_page' => $rentals->perPage(),
                        'total' => $rentals->total(),
                        'last_page' => $rentals->lastPage(),
                        'from' => $rentals->firstItem(),
                        'to' => $rentals->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching rental history: ' . $e->getMessage(), [
                'client_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rental history. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
