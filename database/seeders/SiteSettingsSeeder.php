<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SiteSetting::updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'EMOH Real Estate',
                'site_description' => 'Your trusted partner in real estate. Find your dream property with EMOH.',
                'contact_email' => 'info@emoh.com',
                'contact_phone' => '+1 (555) 123-4567',
                'phone_number' => '+1 (555) 987-6543',
                'address' => '123 Real Estate Ave, Property City, PC 12345',
                'social_facebook' => 'https://facebook.com/emoh',
                'social_twitter' => 'https://twitter.com/emoh',
                'social_instagram' => 'https://instagram.com/emoh',
                'social_linkedin' => 'https://linkedin.com/company/emoh',
                'social_telegram' => 'https://t.me/emoh',
                'social_viber' => 'viber://chat?number=+15559876543',
                'social_whatsapp' => '+15559876543',
                'maintenance_mode' => false,
                'google_analytics_id' => null,
                'site_logo' => null,
                'site_favicon' => null,
            ]
        );
    }
}
