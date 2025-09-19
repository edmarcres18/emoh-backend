<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Condo',
                'description' => 'Condominium units for residential living with shared amenities and facilities.',
            ],
            [
                'name' => 'House & Lot',
                'description' => 'Complete residential properties including both house structure and land ownership.',
            ],
            [
                'name' => 'Commercial',
                'description' => 'Properties designed for business and commercial activities such as offices, retail spaces, and mixed-use buildings.',
            ],
            [
                'name' => 'Warehouse',
                'description' => 'Industrial properties used for storage, distribution, and logistics operations.',
            ],
            [
                'name' => 'Beach Lot',
                'description' => 'Waterfront properties and beachside lots ideal for vacation homes or resort development.',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}
