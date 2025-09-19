<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_name',
        'site_logo',
        'site_favicon',
        'site_description',
        'maintenance_mode',
        'contact_email',
        'contact_phone',
        'phone_number',
        'address',
        'social_facebook',
        'social_twitter',
        'social_instagram',
        'social_linkedin',
        'social_telegram',
        'social_viber',
        'social_whatsapp',
        'google_analytics_id',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
    ];

    /**
     * Get site settings with caching
     */
    public static function getSettings(): array
    {
        return Cache::remember('site_settings', 3600, function () {
            $settings = self::first();
            
            if (!$settings) {
                // Return default values if no settings exist
                return [
                    'site_name' => 'EMOH Real Estate',
                    'site_logo' => null,
                    'site_favicon' => null,
                    'site_description' => 'Your trusted real estate partner',
                    'maintenance_mode' => false,
                    'contact_email' => 'info@emoh.com',
                    'contact_phone' => null,
                    'phone_number' => null,
                    'address' => null,
                    'social_facebook' => null,
                    'social_twitter' => null,
                    'social_instagram' => null,
                    'social_linkedin' => null,
                    'social_telegram' => null,
                    'social_viber' => null,
                    'social_whatsapp' => null,
                    'google_analytics_id' => null,
                ];
            }

            return $settings->toArray();
        });
    }

    /**
     * Get a specific setting value
     */
    public static function get(string $key, $default = null)
    {
        $settings = self::getSettings();
        return $settings[$key] ?? $default;
    }

    /**
     * Update site settings and clear cache
     */
    public static function updateSettings(array $data): bool
    {
        $settings = self::first();
        
        if (!$settings) {
            $settings = self::create($data);
        } else {
            $settings->update($data);
        }

        // Clear cache
        Cache::forget('site_settings');
        
        return true;
    }

    /**
     * Clear settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('site_settings');
    }
}
