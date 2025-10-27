<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Category;
use App\Models\Locations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

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
            try {
                // Handle image uploads if present
                $uploadedImages = [];
                if (isset($data['images']) && is_array($data['images']) && count($data['images']) > 0) {
                    $uploadedImages = $this->handleImageUploads($data['images']);
                    $data['images'] = $uploadedImages;
                } else {
                    // Set empty array if no images
                    $data['images'] = [];
                }

                // Normalize features
                if (isset($data['features']) && is_array($data['features'])) {
                    $data['features'] = $this->sanitizeFeatures($data['features']);
                } else {
                    $data['features'] = [];
                }

                // Create the property
                $property = Property::create($data);
                
                Log::info('Property created in database', [
                    'property_id' => $property->id,
                    'images_count' => count($uploadedImages),
                ]);
                
                return $property;
                
            } catch (\Exception $e) {
                // If property creation fails, clean up uploaded images
                if (!empty($uploadedImages)) {
                    Log::warning('Rolling back image uploads due to property creation failure');
                    $this->deleteImages($uploadedImages);
                }
                
                throw $e;
            }
        });
    }

    /**
     * Update property with image handling
     */
    public function updateProperty(Property $property, array $data): Property
    {
        return DB::transaction(function () use ($property, $data) {
            try {
                $imagesToDelete = [];
                $uploadedImages = [];
                
                // Handle image uploads if present
                if (isset($data['images']) && is_array($data['images']) && count($data['images']) > 0) {
                    $existingImages = $property->images ?? [];
                    
                    // Delete old images if replacing
                    if (isset($data['replace_images']) && $data['replace_images']) {
                        if ($existingImages) {
                            $imagesToDelete = $existingImages;
                        }
                        $existingImages = [];
                    }
                    
                    // Upload new images and merge with existing
                    $uploadedImages = $this->handleImageUploads($data['images'], $existingImages);
                    $data['images'] = $uploadedImages;
                } else {
                    // Remove images key if no new images to avoid overwriting existing ones
                    unset($data['images']);
                }
                
                // Remove replace_images from data as it's not a model field
                unset($data['replace_images']);

                // Normalize features if provided; otherwise leave unchanged
                if (array_key_exists('features', $data)) {
                    if (is_array($data['features'])) {
                        $data['features'] = $this->sanitizeFeatures($data['features']);
                    } else {
                        unset($data['features']);
                    }
                }

                // Update the property
                $property->update($data);
                
                // Only delete old images after successful update
                if (!empty($imagesToDelete)) {
                    $this->deleteImages($imagesToDelete);
                    Log::info('Deleted replaced images', ['count' => count($imagesToDelete)]);
                }
                
                Log::info('Property updated in database', [
                    'property_id' => $property->id,
                    'new_images_count' => count($uploadedImages),
                ]);
                
                return $property->fresh(['category', 'location']);
                
            } catch (\Exception $e) {
                // If update fails, clean up newly uploaded images
                if (!empty($uploadedImages)) {
                    Log::warning('Rolling back new image uploads due to property update failure');
                    $this->deleteImages($uploadedImages);
                }
                
                throw $e;
            }
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
        $uploadedPaths = [];

        try {
            foreach ($images as $index => $image) {
                // Only process valid uploaded files
                if ($image instanceof UploadedFile && $image->isValid()) {
                    // Generate a unique filename with timestamp
                    $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    
                    // Store with a custom filename for better organization
                    $path = $image->storeAs('properties', $filename, 'public');
                    
                    if ($path) {
                        $uploadedImages[] = $path;
                        $uploadedPaths[] = $path;
                        
                        Log::debug('Image uploaded successfully', [
                            'index' => $index,
                            'path' => $path,
                            'original_name' => $image->getClientOriginalName(),
                            'size' => $image->getSize(),
                        ]);
                    } else {
                        throw new \RuntimeException("Failed to upload image: {$image->getClientOriginalName()}");
                    }
                } elseif ($image instanceof UploadedFile) {
                    Log::warning('Invalid uploaded file', [
                        'index' => $index,
                        'error' => $image->getError(),
                        'error_message' => $image->getErrorMessage(),
                    ]);
                }
            }
            
            return $uploadedImages;
            
        } catch (\Exception $e) {
            // Clean up any images that were uploaded before the error
            if (!empty($uploadedPaths)) {
                Log::warning('Cleaning up partial image uploads due to error');
                $this->deleteImages($uploadedPaths);
            }
            
            throw new \RuntimeException('Image upload failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete images from storage
     */
    private function deleteImages(array $images): void
    {
        foreach ($images as $image) {
            try {
                if (Storage::disk('public')->exists($image)) {
                    Storage::disk('public')->delete($image);
                    Log::debug('Image deleted', ['path' => $image]);
                }
            } catch (\Exception $e) {
                // Log but don't throw - deletion failures shouldn't stop the process
                Log::error('Failed to delete image', [
                    'path' => $image,
                    'error' => $e->getMessage(),
                ]);
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

    // Sanitize features: trim, remove empties, de-duplicate
    private function sanitizeFeatures(array $features): array
    {
        $clean = [];
        foreach ($features as $f) {
            if (is_string($f)) {
                $trimmed = trim($f);
                if ($trimmed !== '' && !in_array($trimmed, $clean, true)) {
                    $clean[] = $trimmed;
                }
            }
        }
        return array_values($clean);
    }
}
