<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Category;
use App\Models\Locations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PropertyService
{
    /**
     * Get paginated properties with filters and relationships
     */
    public function getPaginatedProperties(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Property::with(['category', 'location']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('property_name', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%")
                  ->orWhereHas('category', function (Builder $categoryQuery) use ($search) {
                      $categoryQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('location', function (Builder $locationQuery) use ($search) {
                      $locationQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply location filter
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply featured filter
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        // Apply price range filter
        if (!empty($filters['min_price'])) {
            $query->where('estimated_monthly', '>=', $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $query->where('estimated_monthly', '<=', $filters['max_price']);
        }

        // Apply area range filter
        if (!empty($filters['min_area'])) {
            $query->where('floor_area', '>=', $filters['min_area']);
        }
        if (!empty($filters['max_area'])) {
            $query->where('floor_area', '<=', $filters['max_area']);
        }

        // Apply sorting
        $sortBy = $filters['sort'] ?? 'latest';
        switch ($sortBy) {
            case 'latest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('property_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('property_name', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('estimated_monthly', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('estimated_monthly', 'desc');
                break;
            case 'area_asc':
                $query->orderBy('floor_area', 'asc');
                break;
            case 'area_desc':
                $query->orderBy('floor_area', 'desc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new property with image handling
     */
    public function createProperty(array $data): Property
    {
        return DB::transaction(function () use ($data) {
            // Handle image uploads if present
            if (isset($data['images']) && is_array($data['images'])) {
                $data['images'] = $this->handleImageUploads($data['images']);
            }

            return Property::create($data);
        });
    }

    /**
     * Update property with image handling
     */
    public function updateProperty(Property $property, array $data): Property
    {
        return DB::transaction(function () use ($property, $data) {
            // Handle image uploads if present
            if (isset($data['images']) && is_array($data['images']) && count($data['images']) > 0) {
                $existingImages = $property->images ?? [];
                
                // Delete old images if replacing
                if (isset($data['replace_images']) && $data['replace_images']) {
                    if ($existingImages) {
                        $this->deleteImages($existingImages);
                    }
                    $existingImages = [];
                }
                
                // Upload new images and merge with existing
                $newImages = $this->handleImageUploads($data['images'], $existingImages);
                $data['images'] = $newImages;
            } else {
                // Remove images key if no new images to avoid overwriting existing ones
                unset($data['images']);
            }
            
            // Remove replace_images from data as it's not a model field
            unset($data['replace_images']);

            $property->update($data);
            return $property->fresh(['category', 'location']);
        });
    }

    /**
     * Delete property and associated images
     */
    public function deleteProperty(Property $property): bool
    {
        return DB::transaction(function () use ($property) {
            // Delete associated images
            if ($property->images) {
                $this->deleteImages($property->images);
            }

            return $property->delete();
        });
    }

    /**
     * Get property statistics
     */
    public function getPropertyStats(): array
    {
        return [
            'total_properties' => Property::count(),
            'available_properties' => Property::where('status', 'Available')->count(),
            'rented_properties' => Property::where('status', 'Rented')->count(),
            'renovation_properties' => Property::where('status', 'Renovation')->count(),
            'featured_properties' => Property::where('is_featured', true)->count(),
            'average_monthly_rent' => Property::whereNotNull('estimated_monthly')->avg('estimated_monthly'),
            'total_floor_area' => Property::whereNotNull('floor_area')->sum('floor_area'),
            'properties_by_category' => Property::with('category')
                ->select('category_id', DB::raw('count(*) as count'))
                ->groupBy('category_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'category' => $item->category->name ?? 'Unknown',
                        'count' => $item->count
                    ];
                }),
            'properties_by_location' => Property::with('location')
                ->select('location_id', DB::raw('count(*) as count'))
                ->groupBy('location_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'location' => $item->location->name ?? 'Unknown',
                        'count' => $item->count
                    ];
                }),
        ];
    }

    /**
     * Get featured properties
     */
    public function getFeaturedProperties(int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {
        return Property::with(['category', 'location'])
            ->where('is_featured', true)
            ->where('status', 'Available')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get similar properties based on category and location
     */
    public function getSimilarProperties(Property $property, int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        return Property::with(['category', 'location'])
            ->where('id', '!=', $property->id)
            ->where(function (Builder $query) use ($property) {
                $query->where('category_id', $property->category_id)
                      ->orWhere('location_id', $property->location_id);
            })
            ->where('status', 'Available')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Property $property): Property
    {
        $property->update(['is_featured' => !$property->is_featured]);
        return $property->fresh();
    }

    /**
     * Handle image uploads
     */
    private function handleImageUploads(array $images, array $existingImages = []): array
    {
        $uploadedImages = $existingImages;

        foreach ($images as $image) {
            if ($image instanceof \Illuminate\Http\UploadedFile && $image->isValid()) {
                $path = Storage::disk('public')->put('properties', $image);
                $uploadedImages[] = $path;
            }
        }

        return $uploadedImages;
    }

    /**
     * Delete images from storage
     */
    private function deleteImages(array $images): void
    {
        foreach ($images as $image) {
            if (Storage::disk('public')->exists($image)) {
                Storage::disk('public')->delete($image);
            }
        }
    }

    /**
     * Get dropdown data for forms
     */
    public function getFormData(): array
    {
        return [
            'categories' => Category::select('id', 'name')->orderBy('name')->get(),
            'locations' => Locations::select('id', 'name')->orderBy('name')->get(),
            'status_options' => [
                ['value' => 'Available', 'label' => 'Available'],
                ['value' => 'Rented', 'label' => 'Rented'],
                ['value' => 'Renovation', 'label' => 'Renovation'],
            ],
        ];
    }
}
