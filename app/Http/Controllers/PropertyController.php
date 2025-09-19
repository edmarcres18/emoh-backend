<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropertyRequest;
use App\Models\Property;
use App\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    protected PropertyService $propertyService;

    public function __construct(PropertyService $propertyService)
    {
        $this->propertyService = $propertyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search', 'sort', 'category_id', 'location_id', 'status', 
            'is_featured', 'min_price', 'max_price', 'min_area', 'max_area'
        ]);

        // Ensure the sort filter is always passed to the frontend
        if (!isset($filters['sort'])) {
            $filters['sort'] = 'latest';
        }

        $properties = $this->propertyService->getPaginatedProperties($filters, 10);
        $formData = $this->propertyService->getFormData();

        return Inertia::render('Properties/Index', [
            'properties' => $properties,
            'filters' => $filters,
            'categories' => $formData['categories'],
            'locations' => $formData['locations'],
            'statusOptions' => $formData['status_options'],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $formData = $this->propertyService->getFormData();

        return Inertia::render('Properties/Create', [
            'categories' => $formData['categories'],
            'locations' => $formData['locations'],
            'statusOptions' => $formData['status_options'],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PropertyRequest $request): RedirectResponse
    {
        try {
            $this->propertyService->createProperty($request->validated());

            return redirect()->route('properties.index')
                ->with('success', 'Property created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create property. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property): Response
    {
        $property->load(['category', 'location']);
        $similarProperties = $this->propertyService->getSimilarProperties($property);

        return Inertia::render('Properties/Show', [
            'property' => $property,
            'similarProperties' => $similarProperties,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Property $property): Response
    {
        $property->load(['category', 'location']);
        $formData = $this->propertyService->getFormData();

        return Inertia::render('Properties/Edit', [
            'property' => $property,
            'categories' => $formData['categories'],
            'locations' => $formData['locations'],
            'statusOptions' => $formData['status_options'],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PropertyRequest $request, Property $property): RedirectResponse
    {
        try {
            $this->propertyService->updateProperty($property, $request->validated());

            return redirect()->route('properties.index')
                ->with('success', 'Property updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update property. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property): RedirectResponse
    {
        try {
            $this->propertyService->deleteProperty($property);

            return redirect()->route('properties.index')
                ->with('success', 'Property deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete property. Please try again.');
        }
    }

    /**
     * Toggle featured status of a property.
     */
    public function toggleFeatured(Property $property): RedirectResponse
    {
        try {
            $updatedProperty = $this->propertyService->toggleFeatured($property);

            $message = $updatedProperty->is_featured 
                ? 'Property marked as featured successfully.' 
                : 'Property removed from featured successfully.';

            return redirect()->back()
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update featured status. Please try again.');
        }
    }

    /**
     * Get property statistics for dashboard.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->propertyService->getPropertyStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch property statistics.',
            ], 500);
        }
    }

    /**
     * Get featured properties for homepage or dashboard.
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 6);
            $featuredProperties = $this->propertyService->getFeaturedProperties($limit);

            return response()->json([
                'success' => true,
                'data' => $featuredProperties,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch featured properties.',
            ], 500);
        }
    }

    /**
     * Bulk update properties status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'property_ids' => 'required|array',
            'property_ids.*' => 'exists:properties,id',
            'status' => 'required|in:Available,Rented,Renovation',
        ]);

        try {
            Property::whereIn('id', $request->property_ids)
                ->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Properties status updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update properties status.',
            ], 500);
        }
    }

    /**
     * Bulk delete properties.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'property_ids' => 'required|array',
            'property_ids.*' => 'exists:properties,id',
        ]);

        try {
            $properties = Property::whereIn('id', $request->property_ids)->get();
            
            foreach ($properties as $property) {
                $this->propertyService->deleteProperty($property);
            }

            return response()->json([
                'success' => true,
                'message' => 'Properties deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete properties.',
            ], 500);
        }
    }

    /**
     * Export properties data.
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'category_id', 'location_id', 'status', 'is_featured'
            ]);

            // Get all properties matching filters (without pagination)
            $properties = $this->propertyService->getPaginatedProperties($filters, PHP_INT_MAX);

            // Format data for export
            $exportData = $properties->map(function ($property) {
                return [
                    'ID' => $property->id,
                    'Property Name' => $property->property_name,
                    'Category' => $property->category->name ?? '',
                    'Location' => $property->location->name ?? '',
                    'Status' => $property->status,
                    'Monthly Rent' => $property->estimated_monthly,
                    'Lot Area' => $property->lot_area,
                    'Floor Area' => $property->floor_area,
                    'Featured' => $property->is_featured ? 'Yes' : 'No',
                    'Created At' => $property->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'filename' => 'properties_export_' . now()->format('Y_m_d_H_i_s') . '.csv',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export properties data.',
            ], 500);
        }
    }
}
