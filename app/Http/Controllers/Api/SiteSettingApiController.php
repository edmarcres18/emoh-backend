<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SiteSettingApiController extends Controller
{
    /**
     * Get site name
     */
    public function getSiteName(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('site_name')
        ]);
    }

    /**
     * Update site name
     */
    public function updateSiteName(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['site_name' => $request->site_name]);

        return response()->json([
            'success' => true,
            'message' => 'Site name updated successfully'
        ]);
    }

    /**
     * Get site logo
     */
    public function getSiteLogo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('site_logo')
        ]);
    }

    /**
     * Update site logo
     */
    public function updateSiteLogo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_logo' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['site_logo' => $request->site_logo]);

        return response()->json([
            'success' => true,
            'message' => 'Site logo updated successfully'
        ]);
    }

    /**
     * Get site favicon
     */
    public function getSiteFavicon(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('site_favicon')
        ]);
    }

    /**
     * Update site favicon
     */
    public function updateSiteFavicon(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_favicon' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['site_favicon' => $request->site_favicon]);

        return response()->json([
            'success' => true,
            'message' => 'Site favicon updated successfully'
        ]);
    }

    /**
     * Get site description
     */
    public function getSiteDescription(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('site_description')
        ]);
    }

    /**
     * Update site description
     */
    public function updateSiteDescription(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'site_description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['site_description' => $request->site_description]);

        return response()->json([
            'success' => true,
            'message' => 'Site description updated successfully'
        ]);
    }

    /**
     * Get maintenance mode
     */
    public function getMaintenanceMode(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('maintenance_mode', false)
        ]);
    }

    /**
     * Update maintenance mode
     */
    public function updateMaintenanceMode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'maintenance_mode' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['maintenance_mode' => $request->maintenance_mode]);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance mode updated successfully'
        ]);
    }

    /**
     * Get contact email
     */
    public function getContactEmail(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('contact_email')
        ]);
    }

    /**
     * Update contact email
     */
    public function updateContactEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_email' => 'required|email|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['contact_email' => $request->contact_email]);

        return response()->json([
            'success' => true,
            'message' => 'Contact email updated successfully'
        ]);
    }

    /**
     * Get contact phone
     */
    public function getContactPhone(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('contact_phone')
        ]);
    }

    /**
     * Update contact phone
     */
    public function updateContactPhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_phone' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['contact_phone' => $request->contact_phone]);

        return response()->json([
            'success' => true,
            'message' => 'Contact phone updated successfully'
        ]);
    }

    /**
     * Get phone number
     */
    public function getPhoneNumber(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('phone_number')
        ]);
    }

    /**
     * Update phone number
     */
    public function updatePhoneNumber(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['phone_number' => $request->phone_number]);

        return response()->json([
            'success' => true,
            'message' => 'Phone number updated successfully'
        ]);
    }

    /**
     * Get address
     */
    public function getAddress(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('address')
        ]);
    }

    /**
     * Update address
     */
    public function updateAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['address' => $request->address]);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully'
        ]);
    }

    /**
     * Get social Facebook
     */
    public function getSocialFacebook(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('social_facebook')
        ]);
    }

    /**
     * Update social Facebook
     */
    public function updateSocialFacebook(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'social_facebook' => 'nullable|url|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['social_facebook' => $request->social_facebook]);

        return response()->json([
            'success' => true,
            'message' => 'Facebook URL updated successfully'
        ]);
    }

    /**
     * Get social Twitter
     */
    public function getSocialTwitter(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('social_twitter')
        ]);
    }

    /**
     * Update social Twitter
     */
    public function updateSocialTwitter(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'social_twitter' => 'nullable|url|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['social_twitter' => $request->social_twitter]);

        return response()->json([
            'success' => true,
            'message' => 'Twitter URL updated successfully'
        ]);
    }

    /**
     * Get social Instagram
     */
    public function getSocialInstagram(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('social_instagram')
        ]);
    }

    /**
     * Update social Instagram
     */
    public function updateSocialInstagram(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'social_instagram' => 'nullable|url|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['social_instagram' => $request->social_instagram]);

        return response()->json([
            'success' => true,
            'message' => 'Instagram URL updated successfully'
        ]);
    }

    /**
     * Get social LinkedIn
     */
    public function getSocialLinkedin(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('social_linkedin')
        ]);
    }

    /**
     * Update social LinkedIn
     */
    public function updateSocialLinkedin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'social_linkedin' => 'nullable|url|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['social_linkedin' => $request->social_linkedin]);

        return response()->json([
            'success' => true,
            'message' => 'LinkedIn URL updated successfully'
        ]);
    }

    /**
     * Get social Telegram
     */
    public function getSocialTelegram(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('social_telegram')
        ]);
    }

    /**
     * Update social Telegram
     */
    public function updateSocialTelegram(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'social_telegram' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['social_telegram' => $request->social_telegram]);

        return response()->json([
            'success' => true,
            'message' => 'Telegram updated successfully'
        ]);
    }

    /**
     * Get social Viber
     */
    public function getSocialViber(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('social_viber')
        ]);
    }

    /**
     * Update social Viber
     */
    public function updateSocialViber(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'social_viber' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['social_viber' => $request->social_viber]);

        return response()->json([
            'success' => true,
            'message' => 'Viber updated successfully'
        ]);
    }

    /**
     * Get social WhatsApp
     */
    public function getSocialWhatsapp(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('social_whatsapp')
        ]);
    }

    /**
     * Update social WhatsApp
     */
    public function updateSocialWhatsapp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'social_whatsapp' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['social_whatsapp' => $request->social_whatsapp]);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp updated successfully'
        ]);
    }

    /**
     * Get Google Analytics ID
     */
    public function getGoogleAnalyticsId(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::get('google_analytics_id')
        ]);
    }

    /**
     * Update Google Analytics ID
     */
    public function updateGoogleAnalyticsId(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'google_analytics_id' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        SiteSetting::updateSettings(['google_analytics_id' => $request->google_analytics_id]);

        return response()->json([
            'success' => true,
            'message' => 'Google Analytics ID updated successfully'
        ]);
    }

    /**
     * Get all settings
     */
    public function getAllSettings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => SiteSetting::getSettings()
        ]);
    }
}
