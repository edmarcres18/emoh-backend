<?php

namespace Tests\Feature\Api;

use App\Models\Property;
use App\Models\Category;
use App\Models\Locations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $category = Category::factory()->create(['name' => 'Test Category']);
        $location = Locations::factory()->create(['name' => 'Test Location']);
        
        // Create properties with different statuses
        Property::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'status' => 'Available',
            'is_featured' => true,
            'property_name' => 'Featured Available Property',
            'estimated_monthly' => 1000.00
        ]);
        
        Property::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'status' => 'Rented',
            'is_featured' => false,
            'property_name' => 'Rented Property',
            'estimated_monthly' => 1500.00
        ]);
        
        Property::factory()->create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'status' => 'Available',
            'is_featured' => true,
            'property_name' => 'Another Featured Property',
            'estimated_monthly' => 2000.00
        ]);
    }

    public function test_get_properties_by_status_success()
    {
        $response = $this->getJson('/api/properties/by-status?status=Available');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'properties' => [
                            '*' => [
                                'id',
                                'property_name',
                                'status',
                                'estimated_monthly',
                                'is_featured',
                                'category',
                                'location'
                            ]
                        ],
                        'pagination'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Properties retrieved successfully'
                ]);
    }

    public function test_get_properties_by_status_validation_error()
    {
        $response = $this->getJson('/api/properties/by-status?status=InvalidStatus');

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);
    }

    public function test_get_featured_properties_success()
    {
        $response = $this->getJson('/api/properties/featured');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'properties' => [
                            '*' => [
                                'id',
                                'property_name',
                                'status',
                                'estimated_monthly',
                                'is_featured',
                                'category',
                                'location'
                            ]
                        ],
                        'pagination'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Featured properties retrieved successfully'
                ]);

        // Verify all returned properties are featured
        $properties = $response->json('data.properties');
        foreach ($properties as $property) {
            $this->assertTrue($property['is_featured']);
        }
    }

    public function test_get_property_stats_success()
    {
        $response = $this->getJson('/api/properties/stats');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'total_properties',
                        'available_properties',
                        'rented_properties',
                        'featured_properties',
                        'maintenance_properties',
                        'sold_properties',
                        'average_monthly_rate',
                        'total_estimated_value'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Property statistics retrieved successfully'
                ]);
    }

    public function test_get_available_statuses_success()
    {
        $response = $this->getJson('/api/properties/statuses');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Available statuses retrieved successfully',
                    'data' => ['Available', 'Rented', 'Under Maintenance', 'Sold']
                ]);
    }

    public function test_properties_by_status_with_filters()
    {
        $response = $this->getJson('/api/properties/by-status?status=Available&search=Featured&min_price=500&max_price=1500');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
    }

    public function test_featured_properties_with_filters()
    {
        $response = $this->getJson('/api/properties/featured?status=Available&sort_by=estimated_monthly&sort_order=asc');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
    }

    public function test_properties_pagination()
    {
        $response = $this->getJson('/api/properties/by-status?status=Available&per_page=1&page=1');

        $response->assertStatus(200)
                ->assertJsonPath('data.pagination.per_page', 1)
                ->assertJsonPath('data.pagination.current_page', 1);
    }
}
