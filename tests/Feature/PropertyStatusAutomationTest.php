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

class PropertyStatusAutomationTest extends TestCase
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

        // Create property with Available status
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
    public function property_status_changes_to_rented_when_rental_becomes_active()
    {
        // Initially property should be Available
        $this->assertEquals('Available', $this->property->status);

        // Create active rental
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        // Refresh property to get updated status
        $this->property->refresh();

        // Property status should now be 'Rented'
        $this->assertEquals('Rented', $this->property->status);
    }

    /** @test */
    public function property_status_changes_to_available_when_rental_is_terminated()
    {
        // Create active rental first
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->property->refresh();
        $this->assertEquals('Rented', $this->property->status);

        // Terminate the rental
        $rental->terminate('Test termination');

        $this->property->refresh();
        $this->assertEquals('Available', $this->property->status);
    }

    /** @test */
    public function property_status_changes_to_available_when_rental_expires()
    {
        // Create active rental first
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->property->refresh();
        $this->assertEquals('Rented', $this->property->status);

        // Mark rental as expired
        $rental->markAsExpired();

        $this->property->refresh();
        $this->assertEquals('Available', $this->property->status);
    }

    /** @test */
    public function property_status_changes_when_rental_status_is_updated()
    {
        // Create pending rental
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now()->addDay(),
            'status' => 'pending',
        ]);

        // Property should still be Available
        $this->property->refresh();
        $this->assertEquals('Available', $this->property->status);

        // Update rental to active
        $rental->update(['status' => 'active']);

        // Property should now be Rented
        $this->property->refresh();
        $this->assertEquals('Rented', $this->property->status);

        // Update rental back to pending
        $rental->update(['status' => 'pending']);

        // Property should be Available again
        $this->property->refresh();
        $this->assertEquals('Available', $this->property->status);
    }

    /** @test */
    public function property_status_changes_when_rental_is_deleted()
    {
        // Create active rental
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->property->refresh();
        $this->assertEquals('Rented', $this->property->status);

        // Delete the rental
        $rental->delete();

        $this->property->refresh();
        $this->assertEquals('Available', $this->property->status);
    }

    /** @test */
    public function property_status_does_not_change_from_renovation_to_available_when_no_active_rentals()
    {
        // Set property status to Renovation
        $this->property->update(['status' => 'Renovation']);

        // Create and delete a rental (no active rentals)
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'pending',
        ]);

        $rental->delete();

        $this->property->refresh();
        // Status should remain 'Renovation', not change to 'Available'
        $this->assertEquals('Renovation', $this->property->status);
    }

    /** @test */
    public function property_status_changes_from_renovation_to_rented_when_rental_becomes_active()
    {
        // Set property status to Renovation
        $this->property->update(['status' => 'Renovation']);

        // Create active rental
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->property->refresh();
        // Status should change to 'Rented'
        $this->assertEquals('Rented', $this->property->status);
    }

    /** @test */
    public function property_helper_methods_work_correctly()
    {
        // Test getAppropriateStatus when Available and no rentals
        $this->assertEquals('Available', $this->property->getAppropriateStatus());

        // Create active rental
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->property->refresh();
        $this->assertEquals('Rented', $this->property->getAppropriateStatus());

        // Test syncStatusWithRentals
        $this->property->update(['status' => 'Available']); // Force wrong status
        $this->assertTrue($this->property->syncStatusWithRentals());
        $this->property->refresh();
        $this->assertEquals('Rented', $this->property->status);
    }

    /** @test */
    public function controller_activate_method_updates_property_status()
    {
        $this->actingAs($this->user);

        // Create pending rental
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'pending',
        ]);

        $this->assertEquals('Available', $this->property->status);

        // Activate rental via API
        $response = $this->post(route('admin.api.rented.activate', $rental));

        $response->assertOk();
        $response->assertJson([
            'message' => 'Rental activated successfully. Property status updated to Rented.',
        ]);

        $this->property->refresh();
        $this->assertEquals('Rented', $this->property->status);
    }

    /** @test */
    public function controller_terminate_method_updates_property_status()
    {
        $this->actingAs($this->user);

        // Create active rental
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->property->refresh();
        $this->assertEquals('Rented', $this->property->status);

        // Terminate rental via API
        $response = $this->post(route('admin.api.rented.terminate', $rental), [
            'reason' => 'Test termination'
        ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Rental terminated successfully. Property status updated to Available.',
        ]);

        $this->property->refresh();
        $this->assertEquals('Available', $this->property->status);
    }

    /** @test */
    public function controller_mark_expired_method_updates_property_status()
    {
        $this->actingAs($this->user);

        // Create active rental
        $rental = Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        $this->property->refresh();
        $this->assertEquals('Rented', $this->property->status);

        // Mark rental as expired via API
        $response = $this->post(route('admin.api.rented.mark-expired', $rental));

        $response->assertOk();
        $response->assertJson([
            'message' => 'Rental marked as expired successfully. Property status updated to Available.',
        ]);

        $this->property->refresh();
        $this->assertEquals('Available', $this->property->status);
    }

    /** @test */
    public function sync_property_statuses_api_works_correctly()
    {
        $this->actingAs($this->user);

        // Create another property
        $property2 = Property::create([
            'category_id' => $this->property->category_id,
            'location_id' => $this->property->location_id,
            'property_name' => 'Test Property 2',
            'estimated_monthly' => 30000.00,
            'lot_area' => 120.00,
            'floor_area' => 90.00,
            'status' => 'Available',
            'is_featured' => false,
        ]);

        // Create active rentals
        Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $property2->id,
            'monthly_rent' => 30000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        // Manually set wrong statuses
        $this->property->update(['status' => 'Available']);
        $property2->update(['status' => 'Available']);

        // Call sync API
        $response = $this->post(route('admin.api.properties.sync-statuses'));

        $response->assertOk();
        $response->assertJson([
            'updated_count' => 2,
        ]);

        // Check that statuses were corrected
        $this->property->refresh();
        $property2->refresh();
        $this->assertEquals('Rented', $this->property->status);
        $this->assertEquals('Rented', $property2->status);
    }

    /** @test */
    public function multiple_rentals_only_active_ones_affect_property_status()
    {
        // Create multiple rentals with different statuses
        Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now()->subMonth(),
            'end_date' => now()->subWeek(),
            'status' => 'expired',
        ]);

        Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now()->addWeek(),
            'status' => 'pending',
        ]);

        // Property should still be Available (no active rentals)
        $this->property->refresh();
        $this->assertEquals('Available', $this->property->status);

        // Add an active rental
        Rented::create([
            'client_id' => $this->client->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 25000.00,
            'start_date' => now(),
            'status' => 'active',
        ]);

        // Now property should be Rented
        $this->property->refresh();
        $this->assertEquals('Rented', $this->property->status);
    }
}
