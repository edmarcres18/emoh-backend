<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingsController extends Controller
{
    // Middleware will be applied via routes

    /**
     * Display the site settings form.
     */
    public function index(): Response
    {
        $settings = SiteSetting::getSettings();
        
        return Inertia::render('Admin/SiteSettings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the site settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:1000',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'social_facebook' => 'nullable|string|max:255',
            'social_twitter' => 'nullable|string|max:255',
            'social_instagram' => 'nullable|string|max:255',
            'social_linkedin' => 'nullable|string|max:255',
            'social_telegram' => 'nullable|string|max:255',
            'social_viber' => 'nullable|string|max:255',
            'social_whatsapp' => 'nullable|string|max:20',
            'google_analytics_id' => 'nullable|string|max:50',
            'maintenance_mode' => 'boolean',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'site_favicon' => 'nullable|image|mimes:ico,png,jpg,gif,svg|max:1024',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->except(['site_logo', 'site_favicon']);
        
        // Handle logo upload
        if ($request->hasFile('site_logo')) {
            // Delete old logo if exists
            $currentSettings = SiteSetting::first();
            if ($currentSettings && $currentSettings->site_logo) {
                Storage::disk('public')->delete($currentSettings->site_logo);
            }
            
            $logoPath = $request->file('site_logo')->store('site-assets', 'public');
            $data['site_logo'] = $logoPath;
        }

        // Handle favicon upload
        if ($request->hasFile('site_favicon')) {
            // Delete old favicon if exists
            $currentSettings = SiteSetting::first();
            if ($currentSettings && $currentSettings->site_favicon) {
                Storage::disk('public')->delete($currentSettings->site_favicon);
            }
            
            $faviconPath = $request->file('site_favicon')->store('site-assets', 'public');
            $data['site_favicon'] = $faviconPath;
        }

        // Ensure maintenance_mode is boolean
        $data['maintenance_mode'] = $request->boolean('maintenance_mode');

        try {
            SiteSetting::updateSettings($data);
            
            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['general' => 'Failed to update site settings. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Remove uploaded logo.
     */
    public function removeLogo(): RedirectResponse
    {
        try {
            $settings = SiteSetting::first();
            
            if ($settings && $settings->site_logo) {
                Storage::disk('public')->delete($settings->site_logo);
                $settings->update(['site_logo' => null]);
                SiteSetting::clearCache();
            }
            
            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['general' => 'Failed to remove logo.']);
        }
    }

    /**
     * Remove uploaded favicon.
     */
    public function removeFavicon(): RedirectResponse
    {
        try {
            $settings = SiteSetting::first();
            
            if ($settings && $settings->site_favicon) {
                Storage::disk('public')->delete($settings->site_favicon);
                $settings->update(['site_favicon' => null]);
                SiteSetting::clearCache();
            }
            
            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['general' => 'Failed to remove favicon.']);
        }
    }

    /**
     * Clear site settings cache.
     */
    public function clearCache(): RedirectResponse
    {
        try {
            SiteSetting::clearCache();
            
            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['general' => 'Failed to clear cache. Please try again.']);
        }
    }

    /**
     * Get contact and social media information for API purposes.
     */
    public function getContactInfo()
    {
        $settings = SiteSetting::getSettings();
        
        return response()->json([
            'contact_email' => $settings['contact_email'],
            'contact_phone' => $settings['contact_phone'],
            'phone_number' => $settings['phone_number'],
            'address' => $settings['address'],
            'social_facebook' => $settings['social_facebook'],
            'social_twitter' => $settings['social_twitter'],
            'social_instagram' => $settings['social_instagram'],
            'social_linkedin' => $settings['social_linkedin'],
            'social_telegram' => $settings['social_telegram'],
            'social_viber' => $settings['social_viber'],
            'social_whatsapp' => $settings['social_whatsapp'],
        ]);
    }
}
