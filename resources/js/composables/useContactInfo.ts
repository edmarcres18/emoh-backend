import { ref, computed } from 'vue';

interface ContactInfo {
    contact_email: string | null;
    contact_phone: string | null;
    phone_number: string | null;
    address: string | null;
    social_facebook: string | null;
    social_twitter: string | null;
    social_instagram: string | null;
    social_linkedin: string | null;
    social_telegram: string | null;
    social_viber: string | null;
    social_whatsapp: string | null;
}

const contactInfo = ref<ContactInfo | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

export function useContactInfo() {
    const fetchContactInfo = async () => {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await fetch('/api/contact-info');
            if (!response.ok) {
                throw new Error('Failed to fetch contact info');
            }
            contactInfo.value = await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    };

    // Computed properties for easy access to specific social media
    const facebookUrl = computed(() => contactInfo.value?.social_facebook || null);
    const twitterUrl = computed(() => contactInfo.value?.social_twitter || null);
    const instagramUrl = computed(() => contactInfo.value?.social_instagram || null);
    const linkedinUrl = computed(() => contactInfo.value?.social_linkedin || null);
    const telegramUrl = computed(() => contactInfo.value?.social_telegram || null);
    const viberUrl = computed(() => contactInfo.value?.social_viber || null);
    const whatsappUrl = computed(() => contactInfo.value?.social_whatsapp || null);
    
    // Contact info computed properties
    const email = computed(() => contactInfo.value?.contact_email || null);
    const phone = computed(() => contactInfo.value?.contact_phone || null);
    const phoneNumber = computed(() => contactInfo.value?.phone_number || null);
    const address = computed(() => contactInfo.value?.address || null);

    return {
        // Data
        contactInfo,
        loading,
        error,
        
        // Methods
        fetchContactInfo,
        
        // Computed properties for easy access
        facebookUrl,
        twitterUrl,
        instagramUrl,
        linkedinUrl,
        telegramUrl,
        viberUrl,
        whatsappUrl,
        email,
        phone,
        phoneNumber,
        address,
    };
}
