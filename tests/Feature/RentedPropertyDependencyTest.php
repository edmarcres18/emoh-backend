<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Rented;
use App\Models\Client;
use App\Models\Category;
use App\Models\Locations;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class RentedPropertyDependencyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $property;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'System Admin']);
        Role::create(['name' => 'Admin']);

        // Create admin user
        $this->user = User::factory()->create();
        $this->user->assignRole('System Admin');

        // Create category and location
        $category = Category::create(['name' => 'Test Category']);
        $location = Locations::create(['name' => 'Test Location']);

        // Create property with estimated monthly rate
        $this->property = Property::create([
            'category_id' => $category->id,
            'location_id' => $location->id,
            'property_name' => 'Test Property',
            'estimated_monthly' => 25000.00,
            'lot_area' => 100.00,
            'floor_area' => 80.00,
            'status' => 'Available',
            'is_featured' => false,
        ]);

        // Create client
        $this->client = Client::create([
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'password' => bcrypt('password'),
            'phone' => '1234567890',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function rental_automatically_sets_monthly_rent_from_property_estimated_monthly()
    {
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'start_date' => now()->addDay(),
            'status' => 'pending',
        ]);

        $this->assertEquals(25000.00, $rental->monthly_rent);
    }

    /** @test */
    public function rental_validates_monthly_rent_matches_property_estimated_monthly()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Monthly rent (₱30,000.00) must match the property\'s estimated monthly rate (₱25,000.00).');

        Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 30000.00, // Different from property's estimated_monthly
            'start_date' => now()->addDay(),
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function rental_allows_small_floating_point_differences()
    {
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.01, // Small difference (0.01)
            'start_date' => now()->addDay(),
            'status' => 'pending',
        ]);

        $this->assertEquals(25000.01, $rental->monthly_rent);
    }

    /** @test */
    public function rental_prevents_multiple_active_rentals_for_same_property()
    {
        // Create first active rental
        Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        // Try to create second active rental for same property
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This property already has an active rental contract.');

        $secondClient = Client::create([
            'name' => 'Second Client',
            'email' => 'client2@test.com',
            'password' => bcrypt('password'),
            'phone' => '0987654321',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Rented::create([
            'client_id' => $secondClient->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now()->addDay(),
            'status' => 'active',
        ]);
    }

    /** @test */
    public function property_cannot_be_deleted_with_active_rentals()
    {
        // Create active rental
        Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete property with active rental contracts. Please terminate the rental first.');

        $this->property->delete();
    }

    /** @test */
    public function property_estimated_monthly_cannot_be_changed_with_active_rentals()
    {
        // Create active rental
        Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot change estimated monthly rate while property has active rental contracts.');

        $this->property->update(['estimated_monthly' => 30000.00]);
    }

    /** @test */
    public function rental_controller_automatically_sets_monthly_rent()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('admin.rented.store'), [
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('admin.rented.index'));
        $response->assertSessionHas('success');

        $rental = Rented::latest()->first();
        $this->assertEquals(25000.00, $rental->monthly_rent);
    }

    /** @test */
    public function rental_request_validates_monthly_rent_against_property_rate()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('admin.rented.store'), [
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 30000.00, // Different from property's estimated_monthly
            'start_date' => now()->addDay()->format('Y-m-d'),
            'status' => 'pending',
        ]);

        $response->assertSessionHasErrors(['monthly_rent']);
    }

    /** @test */
    public function get_property_rate_api_returns_correct_data()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('admin.api.properties.rate', $this->property));

        $response->assertOk();
        $response->assertJson([
            'estimated_monthly' => 25000.00,
            'formatted_rate' => '₱25,000.00',
        ]);
    }

    /** @test */
    public function validate_rental_api_validates_correctly()
    {
        $this->actingAs($this->user);

        // Valid rental data
        $response = $this->post(route('admin.api.rented.validate'), [
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
        ]);

        $response->assertOk();
        $response->assertJson([
            'valid' => true,
            'message' => 'Rental data is valid.',
            'property_rate' => 25000.00,
        ]);

        // Invalid rental data
        $response = $this->post(route('admin.api.rented.validate'), [
            'property_id' => $this->property->id,
            'monthly_rent' => 30000.00,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'valid' => false,
            'expected_rate' => 25000.00,
            'provided_rate' => 30000.00,
        ]);
    }

    /** @test */
    public function rental_model_helper_methods_work_correctly()
    {
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        // Test validateMonthlyRentMatchesProperty
        $this->assertTrue($rental->validateMonthlyRentMatchesProperty());

        // Test getPropertyEstimatedMonthlyAttribute
        $this->assertEquals(25000.00, $rental->property_estimated_monthly);

        // Test setMonthlyRentFromProperty
        $rental->monthly_rent = 0;
        $rental->setMonthlyRentFromProperty();
        $this->assertEquals(25000.00, $rental->monthly_rent);
    }

    /** @test */
    public function property_model_helper_methods_work_correctly()
    {
        // Test canBeUpdated (no active rentals)
        $this->assertTrue($this->property->canBeUpdated());

        // Test getCurrentMonthlyRate (no active rental)
        $this->assertEquals(25000.00, $this->property->getCurrentMonthlyRate());

        // Test canChangeEstimatedMonthly (no active rentals)
        $this->assertTrue($this->property->canChangeEstimatedMonthly());

        // Create active rental
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        // Refresh property to get updated relationships
        $this->property->refresh();

        // Test with active rental
        $this->assertFalse($this->property->canBeUpdated());
        $this->assertEquals(25000.00, $this->property->getCurrentMonthlyRate());
        $this->assertFalse($this->property->canChangeEstimatedMonthly());
        $this->assertTrue($this->property->isRented());
    }
}
