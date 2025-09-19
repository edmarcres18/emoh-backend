import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface SiteSettings {
    site_name: string;
    site_logo: string | null;
    site_favicon: string | null;
    site_description: string | null;
    site_keywords: string | null;
    maintenance_mode: boolean;
    contact_email: string | null;
    contact_phone: string | null;
    address: string | null;
    social_facebook: string | null;
    social_twitter: string | null;
    social_instagram: string | null;
    social_linkedin: string | null;
    google_analytics_id: string | null;
    meta_title: string | null;
    meta_description: string | null;
}

export function useSiteSettings() {
    const page = usePage();
    
    const siteSettings = computed((): SiteSettings => {
        return page.props.siteSettings as SiteSettings;
    });

    const siteName = computed(() => siteSettings.value?.site_name || 'Laravel Starter Kit');
    
    const siteLogo = computed(() => {
        if (siteSettings.value?.site_logo) {
            return `/storage/${siteSettings.value.site_logo}`;
        }
        return null;
    });
    
    const siteFavicon = computed(() => {
        if (siteSettings.value?.site_favicon) {
            return `/storage/${siteSettings.value.site_favicon}`;
        }
        return null;
    });
    
    const siteDescription = computed(() => 
        siteSettings.value?.site_description || 'A modern Laravel application'
    );
    
    const metaTitle = computed(() => 
        siteSettings.value?.meta_title || siteSettings.value?.site_name || 'Laravel Starter Kit'
    );
    
    const metaDescription = computed(() => 
        siteSettings.value?.meta_description || siteSettings.value?.site_description || 'A modern Laravel application'
    );
    
    const contactEmail = computed(() => siteSettings.value?.contact_email);
    const contactPhone = computed(() => siteSettings.value?.contact_phone);
    const address = computed(() => siteSettings.value?.address);
    
    const socialLinks = computed(() => ({
        facebook: siteSettings.value?.social_facebook,
        twitter: siteSettings.value?.social_twitter,
        instagram: siteSettings.value?.social_instagram,
        linkedin: siteSettings.value?.social_linkedin,
    }));
    
    const isMaintenanceMode = computed(() => siteSettings.value?.maintenance_mode || false);
    const googleAnalyticsId = computed(() => siteSettings.value?.google_analytics_id);

    return {
        siteSettings,
        siteName,
        siteLogo,
        siteFavicon,
        siteDescription,
        metaTitle,
        metaDescription,
        contactEmail,
        contactPhone,
        address,
        socialLinks,
        isMaintenanceMode,
        googleAnalyticsId,
    };
}
