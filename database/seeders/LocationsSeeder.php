<?php

namespace Database\Seeders;

use App\Models\Locations;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Cebu',
                'code' => 'CEB',
                'description' => 'Cebu City and surrounding areas in Central Visayas, known for its vibrant business district and historical significance.',
            ],
            [
                'name' => 'Manila',
                'code' => 'MNL',
                'description' => 'Metro Manila, the National Capital Region and primary business hub of the Philippines.',
            ],
            [
                'name' => 'Davao',
                'code' => 'DVO',
                'description' => 'Davao City and surrounding areas in Mindanao, known as the largest city in the Philippines by land area.',
            ],
        ];

        foreach ($locations as $location) {
            Locations::firstOrCreate(
                ['name' => $location['name']],
                [
                    'code' => $location['code'],
                    'description' => $location['description']
                ]
            );
        }
    }
}
