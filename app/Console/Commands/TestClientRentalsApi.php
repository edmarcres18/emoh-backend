<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Rented;
use App\Models\Property;

class TestClientRentalsApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:client-rentals {client_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the client rentals API endpoint and check for data issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('Client Rentals API Test');
        $this->info('========================================');
        $this->newLine();

        // Get client ID from argument or use first client
        $clientId = $this->argument('client_id');
        
        if ($clientId) {
            $client = Client::find($clientId);
            if (!$client) {
                $this->error("Client with ID {$clientId} not found!");
                return 1;
            }
        } else {
            $client = Client::first();
            if (!$client) {
                $this->error('No clients found in database!');
                return 1;
            }
        }

        $this->info("Testing with Client ID: {$client->id}");
        $this->info("Client Email: {$client->email}");
        $this->info("Client Active: " . ($client->is_active ? 'Yes' : 'No'));
        $this->newLine();

        // Check rentals
        $rentals = Rented::where('client_id', $client->id)->get();
        $this->info("Total Rentals: " . $rentals->count());
        $this->newLine();

        if ($rentals->isEmpty()) {
            $this->warn('No rentals found for this client.');
            return 0;
        }

        // Check each rental for issues
        $issues = [];
        $this->info('Checking rentals for data integrity...');
        
        foreach ($rentals as $rental) {
            $rentalIssues = [];
            
            // Check property
            if (!$rental->property) {
                $rentalIssues[] = "Missing property (ID: {$rental->property_id})";
            } else {
                // Check category
                if (!$rental->property->category) {
                    $rentalIssues[] = "Property {$rental->property->property_name} missing category";
                }
                
                // Check location
                if (!$rental->property->location) {
                    $rentalIssues[] = "Property {$rental->property->property_name} missing location";
                }
            }
            
            if (!empty($rentalIssues)) {
                $issues[] = [
                    'rental_id' => $rental->id,
                    'issues' => $rentalIssues
                ];
            }
        }

        if (empty($issues)) {
            $this->info('✓ All rentals have valid data!');
        } else {
            $this->error('✗ Found data integrity issues:');
            $this->newLine();
            
            foreach ($issues as $issue) {
                $this->warn("Rental ID {$issue['rental_id']}:");
                foreach ($issue['issues'] as $problemDesc) {
                    $this->line("  - {$problemDesc}");
                }
            }
            
            $this->newLine();
            $this->info('FIX RECOMMENDATIONS:');
            $this->line('1. Delete orphaned rentals:');
            $this->line('   DELETE FROM rented WHERE property_id NOT IN (SELECT id FROM properties);');
            $this->newLine();
            $this->line('2. Fix missing categories/locations:');
            $this->line('   UPDATE properties SET category_id = 1 WHERE category_id IS NULL;');
            $this->line('   UPDATE properties SET location_id = 1 WHERE location_id IS NULL;');
        }

        $this->newLine();
        
        // Test API transformation
        $this->info('Testing API transformation...');
        try {
            $testRentals = Rented::with([
                'property.category',
                'property.location'
            ])->where('client_id', $client->id)->limit(1)->get();
            
            $transformed = $testRentals->map(function ($rental) {
                $property = $rental->property;
                
                if (!$property) {
                    return null;
                }
                
                return [
                    'id' => $rental->id,
                    'property' => [
                        'name' => $property->property_name ?? 'Unknown',
                        'category' => $property->category->name ?? 'Uncategorized',
                        'location' => $property->location->name ?? 'Unknown Location',
                    ],
                    'status' => $rental->status,
                ];
            })->filter();
            
            $this->info('✓ Transformation successful!');
            $this->line('Sample: ' . json_encode($transformed->first(), JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->error('✗ Transformation failed!');
            $this->error($e->getMessage());
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('Test Complete');
        $this->info('========================================');

        return 0;
    }
}
