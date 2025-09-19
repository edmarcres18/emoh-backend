<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RentedRequest;
use App\Models\Rented;
use App\Models\Client;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RentedController extends Controller
{
    /**
     * Display a listing of the resource (API).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Rented::with(['client', 'property', 'property.category', 'property.location']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('property', function ($propertyQuery) use ($search) {
                    $propertyQuery->where('property_name', 'like', "%{$search}%");
                })
                ->orWhere('status', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->has('client_id') && $request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by property
        if ($request->has('property_id') && $request->property_id) {
            $query->where('property_id', $request->property_id);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $rented = $query->paginate($request->get('per_page', 15));

        // Update remarks for all items in the collection
        $rented->getCollection()->each(function ($item) {
            $item->updateRemarks();
        });

        return response()->json($rented);
    }

    /**
     * Display a listing of the resource (Inertia).
     */
    public function indexPage(Request $request): Response
    {
        $query = Rented::with(['client', 'property', 'property.category', 'property.location']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', function ($clientQuery) use ($search) {
                    $clientQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('property', function ($propertyQuery) use ($search) {
                    $propertyQuery->where('property_name', 'like', "%{$search}%");
                })
                ->orWhere('status', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $rented = $query->paginate(15)->withQueryString();

        // Update remarks for all items in the collection
        $rented->getCollection()->each(function ($item) {
            $item->updateRemarks();
        });

        // Get clients and properties for filters
        $clients = Client::select('id', 'name', 'email')->orderBy('name')->get();
        $properties = Property::with(['category', 'location'])
            ->select('id', 'property_name', 'category_id', 'location_id')
            ->orderBy('property_name')
            ->get();

        // Get statistics
        $stats = [
            'total' => Rented::count(),
            'active' => Rented::where('status', 'active')->count(),
            'pending' => Rented::where('status', 'pending')->count(),
            'expired' => Rented::where('status', 'expired')->count(),
            'terminated' => Rented::where('status', 'terminated')->count(),
        ];

        return Inertia::render('Admin/Rented/Index', [
            'rented' => $rented,
            'clients' => $clients,
            'properties' => $properties,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $clients = Client::select('id', 'name', 'email')->orderBy('name')->get();
        $properties = Property::with(['category', 'location'])
            ->whereNotIn('id', function ($query) {
                $query->select('property_id')
                      ->from('rented')
                      ->where('status', 'active');
            })
            ->select('id', 'property_name', 'category_id', 'location_id', 'estimated_monthly')
            ->orderBy('property_name')
            ->get();

        return Inertia::render('Admin/Rented/Create', [
            'clients' => $clients,
            'properties' => $properties,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RentedRequest $request): RedirectResponse
    {
        try {
            $validatedData = $request->validated();

            // Ensure monthly_rent is set from property's estimated_monthly
            if (isset($validatedData['property_id'])) {
                $property = Property::find($validatedData['property_id']);
                if ($property && $property->estimated_monthly) {
                    $validatedData['monthly_rent'] = $property->estimated_monthly;
                }
            }

            $rented = Rented::create($validatedData);
            $rented->load(['client', 'property', 'property.category', 'property.location']);

            return redirect()->route('admin.rented.index')
                ->with('success', 'Rental record created successfully. Monthly rent automatically set to ₱' . number_format($rented->monthly_rent, 2) . ' based on property rate.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create rental record. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Rented $rented): JsonResponse
    {
        $rented->load(['client', 'property', 'property.category', 'property.location']);

        // Ensure remarks are up to date
        $rented->updateRemarks();

        return response()->json([
            'data' => $rented,
        ]);
    }

    /**
     * Display the specified resource (Inertia).
     */
    public function showPage(Rented $rented): Response
    {
        $rented->load(['client', 'property', 'property.category', 'property.location']);

        // Ensure remarks are up to date
        $rented->updateRemarks();

        return Inertia::render('Admin/Rented/Show', [
            'rented' => $rented,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rented $rented): Response
    {
        $rented->load(['client', 'property', 'property.category', 'property.location']);

        // Prevent editing ended rentals
        if ($rented->status === 'ended') {
            return redirect()->route('admin.rented.index')
                ->with('error', 'Ended rentals cannot be edited.');
        }

        $clients = Client::select('id', 'name', 'email')->orderBy('name')->get();
        $properties = Property::with(['category', 'location'])
            ->where(function ($query) use ($rented) {
                $query->whereNotIn('id', function ($subQuery) {
                    $subQuery->select('property_id')
                            ->from('rented')
                            ->where('status', 'active');
                })
                ->orWhere('id', $rented->property_id);
            })
            ->select('id', 'property_name', 'category_id', 'location_id', 'estimated_monthly')
            ->orderBy('property_name')
            ->get();

        return Inertia::render('Admin/Rented/Edit', [
            'rented' => $rented,
            'clients' => $clients,
            'properties' => $properties,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RentedRequest $request, Rented $rented): RedirectResponse
    {
        // Prevent updating ended rentals
        if ($rented->status === 'ended') {
            return redirect()->route('admin.rented.index')
                ->with('error', 'Ended rentals cannot be updated.');
        }
        try {
            $validatedData = $request->validated();

            // Ensure monthly_rent matches property's estimated_monthly
            if (isset($validatedData['property_id'])) {
                $property = Property::find($validatedData['property_id']);
                if ($property && $property->estimated_monthly) {
                    $validatedData['monthly_rent'] = $property->estimated_monthly;
                }
            }

            // Handle date formatting - ensure dates are properly formatted
            if (isset($validatedData['start_date'])) {
                $validatedData['start_date'] = date('Y-m-d', strtotime($validatedData['start_date']));
            }
            if (isset($validatedData['end_date']) && $validatedData['end_date']) {
                $validatedData['end_date'] = date('Y-m-d', strtotime($validatedData['end_date']));
            }
            if (isset($validatedData['contract_signed_at']) && $validatedData['contract_signed_at']) {
                $validatedData['contract_signed_at'] = date('Y-m-d', strtotime($validatedData['contract_signed_at']));
            }

            // Store original values for comparison
            $originalPropertyId = $rented->property_id;
            $originalStatus = $rented->status;

            // Update the rental record
            $rented->update($validatedData);
            $rented->refresh();
            $rented->load(['client', 'property', 'property.category', 'property.location']);

            return redirect()->route('admin.rented.index')
                ->with('success', 'Rental record updated successfully. Monthly rent set to ₱' . number_format($rented->monthly_rent, 2) . ' based on property rate.');
        } catch (\InvalidArgumentException $e) {
            \Log::error('Rental update validation error: ' . $e->getMessage(), [
                'rental_id' => $rented->id,
                'request_data' => $request->all()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Rental update database error: ' . $e->getMessage(), [
                'rental_id' => $rented->id,
                'request_data' => $request->all()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Database error occurred while updating rental record. Please check your data and try again.');
        } catch (\Exception $e) {
            \Log::error('Rental update general error: ' . $e->getMessage(), [
                'rental_id' => $rented->id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update rental record: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Rented $rented): JsonResponse|RedirectResponse
    {
        $rented->delete();

        // If this is an API/AJAX request, return JSON. Otherwise, redirect back to Inertia page.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Rental record deleted successfully.',
            ]);
        }

        return redirect()->route('admin.rented.index')
            ->with('success', 'Rental record deleted successfully.');
    }

    /**
     * Activate a rental contract.
     */
    public function activate(Rented $rented): JsonResponse
    {
        if ($rented->status === 'active') {
            return response()->json([
                'message' => 'Rental is already active.',
            ], 400);
        }

        $rented->activate();
        $rented->load(['client', 'property', 'property.category', 'property.location']);

        // Property status will be automatically updated by model observers

        return response()->json([
            'message' => 'Rental activated successfully. Property status updated to Rented.',
            'data' => $rented,
        ]);
    }

    /**
     * Terminate a rental contract.
     */
    public function terminate(Request $request, Rented $rented): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ($rented->status === 'terminated') {
            return response()->json([
                'message' => 'Rental is already terminated.',
            ], 400);
        }

        $rented->terminate($request->reason);
        $rented->load(['client', 'property', 'property.category', 'property.location']);

        // Property status will be automatically updated by model observers

        return response()->json([
            'message' => 'Rental terminated successfully. Property status updated to Available.',
            'data' => $rented,
        ]);
    }

    /**
     * Mark rental as expired.
     */
    public function markExpired(Rented $rented): JsonResponse
    {
        if ($rented->status === 'expired') {
            return response()->json([
                'message' => 'Rental is already expired.',
            ], 400);
        }

        $rented->markAsExpired();
        $rented->load(['client', 'property', 'property.category', 'property.location']);

        // Property status will be automatically updated by model observers

        return response()->json([
            'message' => 'Rental marked as expired successfully. Property status updated to Available.',
            'data' => $rented,
        ]);
    }

    /**
     * Renew a rental by updating its end_date and optional remarks note.
     */
    public function renew(Request $request, Rented $rented): JsonResponse|RedirectResponse
    {
        // Disallow renew for terminated or ended rentals
        if (in_array($rented->status, ['terminated', 'ended'])) {
            return response()->json([
                'message' => 'This rental cannot be renewed because it is already ' . $rented->status . '.',
            ], 400);
        }

        $startDate = $rented->start_date ? \Carbon\Carbon::parse($rented->start_date)->format('Y-m-d') : null;

        $validator = \Validator::make($request->all(), [
            'end_date' => ['required', 'date', $startDate ? ('after:' . $startDate) : 'after:today'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Ensure new end date is not earlier than current end date if set
        if ($rented->end_date && strtotime($validated['end_date']) <= strtotime($rented->end_date->format('Y-m-d'))) {
            $message = 'The new end date must be later than the current end date.';
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['end_date' => [$message]],
                ], 422);
            }
            return redirect()->back()->withErrors(['end_date' => $message])->withInput();
        }

        try {
            $oldEndDate = $rented->end_date ? $rented->end_date->format('Y-m-d') : null;
            $rented->end_date = date('Y-m-d', strtotime($validated['end_date']));

            // Append a note for audit trail
            $noteParts = [];
            $noteParts[] = 'Renewed until ' . $rented->end_date;
            if ($oldEndDate) {
                $noteParts[] = '(previously ' . $oldEndDate . ')';
            }
            if (!empty($validated['remarks'])) {
                $noteParts[] = 'Remarks: ' . $validated['remarks'];
            }
            $note = implode(' ', $noteParts);
            $rented->notes = trim(($rented->notes ? $rented->notes . "\n\n" : '') . $note);

            // If previously expired and new end date is now or future, restore to active
            if ($rented->status === 'expired' && strtotime($rented->end_date) >= strtotime(date('Y-m-d'))) {
                $rented->status = 'active';
            }

            // Keep monthly_rent synced with property's estimated_monthly (enforced by model observer)
            if ($rented->property_id) {
                $property = $rented->property ?? \App\Models\Property::find($rented->property_id);
                if ($property && $property->estimated_monthly) {
                    $rented->monthly_rent = $property->estimated_monthly;
                }
            }

            $rented->save();
            $rented->load(['client', 'property', 'property.category', 'property.location']);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Rental renewed successfully.',
                    'data' => $rented,
                ]);
            }

            return redirect()->back()->with('success', 'Rental renewed successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to renew rental: ' . $e->getMessage(), [
                'rental_id' => $rented->id,
                'request_data' => $request->all(),
            ]);
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to renew rental. Please try again.',
                    'error' => $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withInput()->with('error', 'Failed to renew rental: ' . $e->getMessage());
        }
    }

    /**
     * Mark a rental as not renewed (ended).
     */
    public function end(Request $request, Rented $rented): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($rented->status === 'ended') {
            return response()->json([
                'message' => 'Rental is already ended.',
            ], 400);
        }

        try {
            $rented->end($validated['reason'] ?? null);
            $rented->load(['client', 'property', 'property.category', 'property.location']);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Rental marked as ended successfully.',
                    'data' => $rented,
                ]);
            }

            return redirect()->back()->with('success', 'Rental marked as ended successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to end rental: ' . $e->getMessage(), [
                'rental_id' => $rented->id,
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'message' => 'Failed to mark rental as ended. Please try again.',
            ], 500);
        }
    }

    /**
     * Get rental statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => Rented::count(),
            'active' => Rented::active()->count(),
            'pending' => Rented::pending()->count(),
            'expired' => Rented::expired()->count(),
            'terminated' => Rented::terminated()->count(),
            'total_monthly_revenue' => Rented::active()->sum('monthly_rent'),
            'average_rent' => Rented::active()->avg('monthly_rent'),
        ];

        return response()->json($stats);
    }

    /**
     * Get property estimated monthly rate for AJAX requests.
     */
    public function getPropertyRate(Property $property): JsonResponse
    {
        return response()->json([
            'estimated_monthly' => $property->estimated_monthly,
            'formatted_rate' => '₱' . number_format($property->estimated_monthly, 2),
        ]);
    }

    /**
     * Validate rental data before saving.
     */
    public function validateRental(Request $request): JsonResponse
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'monthly_rent' => 'required|numeric|min:0',
        ]);

        $property = Property::find($request->property_id);

        if (!$property) {
            return response()->json([
                'valid' => false,
                'message' => 'Property not found.',
            ], 404);
        }

        if (!$property->estimated_monthly) {
            return response()->json([
                'valid' => false,
                'message' => 'Property does not have an estimated monthly rate set.',
            ], 400);
        }

        $monthlyRent = (float) $request->monthly_rent;
        $estimatedMonthly = (float) $property->estimated_monthly;

        // Allow small floating point differences (0.01)
        if (abs($monthlyRent - $estimatedMonthly) > 0.01) {
            return response()->json([
                'valid' => false,
                'message' => "Monthly rent must match the property's estimated monthly rate of ₱" . number_format($estimatedMonthly, 2) . ".",
                'expected_rate' => $estimatedMonthly,
                'provided_rate' => $monthlyRent,
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Rental data is valid.',
            'property_rate' => $estimatedMonthly,
        ]);
    }

    /**
     * Sync all property statuses with their rental states.
     */
    public function syncPropertyStatuses(): JsonResponse
    {
        try {
            $properties = Property::with('rentals')->get();
            $updatedCount = 0;

            foreach ($properties as $property) {
                $oldStatus = $property->status;
                $property->syncStatusWithRentals();

                if ($property->status !== $oldStatus) {
                    $updatedCount++;
                }
            }

            return response()->json([
                'message' => "Successfully synced property statuses. {$updatedCount} properties updated.",
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to sync property statuses.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
