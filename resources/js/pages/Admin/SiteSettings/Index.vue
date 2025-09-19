<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
import { 
    Upload, 
    Trash2, 
    Save, 
    RefreshCw, 
    Globe, 
    Image, 
    Mail, 
    Phone, 
    MapPin, 
    Facebook, 
    Twitter, 
    Instagram, 
    Linkedin,
    MessageCircle,
    BarChart3,
    Settings,
    AlertTriangle
} from 'lucide-vue-next';

// Production-ready toast implementation - only one toast at a time
let currentToast: HTMLElement | null = null;
let toastTimeout: ReturnType<typeof setTimeout> | null = null;

const showToast = (message: string, type: 'success' | 'error' = 'success') => {
    // Clear any existing toast
    if (currentToast) {
        currentToast.classList.add('translate-x-full');
        setTimeout(() => {
            if (currentToast && document.body.contains(currentToast)) {
                document.body.removeChild(currentToast);
            }
            currentToast = null;
        }, 300);
    }
    
    // Clear any existing timeout
    if (toastTimeout) {
        clearTimeout(toastTimeout);
        toastTimeout = null;
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
        type === 'error' 
            ? 'bg-red-500 text-white' 
            : 'bg-green-500 text-white'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    currentToast = toast;
    
    // Show toast
    setTimeout(() => toast.classList.remove('translate-x-full'), 100);
    
    // Auto-hide toast after 3 seconds
    toastTimeout = setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
            if (currentToast === toast) {
                currentToast = null;
            }
            toastTimeout = null;
        }, 300);
    }, 3000);
};

interface Props {
    settings: {
        site_name: string;
        site_logo?: string;
        site_favicon?: string;
        site_description?: string;
        maintenance_mode: boolean;
        contact_email?: string;
        contact_phone?: string;
        phone_number?: string;
        address?: string;
        social_facebook?: string;
        social_twitter?: string;
        social_instagram?: string;
        social_linkedin?: string;
        social_telegram?: string;
        social_viber?: string;
        social_whatsapp?: string;
        google_analytics_id?: string;
    };
}

const props = defineProps<Props>();

// Reactive variables
const activeTab = ref('general');
const logoInput = ref<HTMLInputElement>();
const faviconInput = ref<HTMLInputElement>();
const logoPreview = ref<string | null>(null);
const faviconPreview = ref<string | null>(null);

// Breadcrumbs
const breadcrumbs = [
    { title: 'Admin', href: '/admin' },
    { title: 'Site Settings', href: '/admin/site-settings' }
];

// Tab configuration
const tabs = [
    { id: 'general', label: 'General', icon: Globe },
    { id: 'branding', label: 'Branding', icon: Image },
    { id: 'contact', label: 'Contact', icon: Mail },
    { id: 'social', label: 'Social', icon: Facebook },
    { id: 'advanced', label: 'Advanced', icon: Settings }
];

// Form setup
const form = useForm({
    site_name: props.settings.site_name || '',
    site_logo: null as File | null,
    site_favicon: null as File | null,
    site_description: props.settings.site_description || '',
    maintenance_mode: props.settings.maintenance_mode || false,
    contact_email: props.settings.contact_email || '',
    contact_phone: props.settings.contact_phone || '',
    phone_number: props.settings.phone_number || '',
    address: props.settings.address || '',
    social_facebook: props.settings.social_facebook || '',
    social_twitter: props.settings.social_twitter || '',
    social_instagram: props.settings.social_instagram || '',
    social_linkedin: props.settings.social_linkedin || '',
    social_telegram: props.settings.social_telegram || '',
    social_viber: props.settings.social_viber || '',
    social_whatsapp: props.settings.social_whatsapp || '',
    google_analytics_id: props.settings.google_analytics_id || '',
});

// Check if form has changes
const hasChanges = computed(() => {
    return form.isDirty || form.site_logo !== null || form.site_favicon !== null;
});

const currentLogo = computed(() => {
    if (logoPreview.value) return logoPreview.value;
    if (props.settings.site_logo) return props.settings.site_logo;
    return null;
});

const currentFavicon = computed(() => {
    if (faviconPreview.value) return faviconPreview.value;
    if (props.settings.site_favicon) return props.settings.site_favicon;
    return null;
});

const handleLogoUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        // Validate file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            showToast('Logo file size must be less than 2MB', 'error');
            return;
        }
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            showToast('Please select a valid image file', 'error');
            return;
        }
        
        form.site_logo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const handleFaviconUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        // Validate file size (1MB max)
        if (file.size > 1024 * 1024) {
            showToast('Favicon file size must be less than 1MB', 'error');
            return;
        }
        
        // Validate file type
        const validTypes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/jpeg', 'image/gif'];
        if (!validTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.ico')) {
            showToast('Please select a valid favicon file (ICO, PNG, JPG, GIF)', 'error');
            return;
        }
        
        form.site_favicon = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            faviconPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeLogo = () => {
    if (props.settings.site_logo) {
        router.delete('/admin/site-settings/logo', {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Logo removed successfully');
            },
            onError: () => {
                showToast('Failed to remove logo', 'error');
            },
        });
    }
    form.site_logo = null;
    logoPreview.value = null;
    if (logoInput.value) logoInput.value.value = '';
};

const removeFavicon = () => {
    if (props.settings.site_favicon) {
        router.delete('/admin/site-settings/favicon', {
            preserveScroll: true,
            onSuccess: () => {
                showToast('Favicon removed successfully');
            },
            onError: () => {
                showToast('Failed to remove favicon', 'error');
            },
        });
    }
    form.site_favicon = null;
    faviconPreview.value = null;
    if (faviconInput.value) faviconInput.value.value = '';
};

const clearCache = () => {
    router.post('/admin/site-settings/clear-cache', {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Cache cleared successfully');
        },
        onError: () => {
            showToast('Failed to clear cache', 'error');
        },
    });
};

const submit = () => {
    // Add _method field for Laravel method spoofing
    form.transform((data) => ({
        ...data,
        _method: 'PUT'
    })).post('/admin/site-settings', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showToast('Site settings updated successfully');
            logoPreview.value = null;
            faviconPreview.value = null;
        },
        onError: (errors) => {
            console.error('Form errors:', errors);
            showToast('Failed to update site settings. Please check the form for errors.', 'error');
        },
    });
};
</script>

<template>
    <Head title="Site Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Site Settings</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure your site's basic information, branding, and social media links</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button 
                        variant="outline" 
                        @click="clearCache"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200"
                    >
                        <RefreshCw :class="['h-4 w-4', form.processing ? 'animate-spin' : '']" />
                        Clear Cache
                    </Button>
                    <Button 
                        @click="submit"
                        :disabled="form.processing || !hasChanges"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200"
                    >
                        <Save :class="['h-4 w-4', form.processing ? 'animate-pulse' : '']" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </div>

            <!-- Maintenance Mode Alert -->
            <div v-if="form.maintenance_mode" class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <AlertTriangle class="h-5 w-5 text-orange-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <h3 class="text-sm font-medium text-orange-800 dark:text-orange-200">Maintenance Mode Active</h3>
                        <p class="mt-1 text-sm text-orange-700 dark:text-orange-300">Your site is currently showing a maintenance page to visitors.</p>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Navigation Tabs -->
                <div class="lg:col-span-1">
                    <nav class="space-y-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'w-full flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
                                activeTab === tab.id
                                    ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-100'
                            ]"
                        >
                            <component :is="tab.icon" class="h-4 w-4 flex-shrink-0" />
                            <span>{{ tab.label }}</span>
                        </button>
                    </nav>
                </div>

                <!-- Content Area -->
                <div class="lg:col-span-3">
                    <!-- General Settings -->
                    <div v-if="activeTab === 'general'" class="space-y-6">
                        <Card class="border-0 shadow-sm bg-white dark:bg-gray-900">
                            <CardHeader class="pb-4">
                                <CardTitle class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    <Globe class="h-5 w-5" />
                                    General Settings
                                </CardTitle>
                                <CardDescription class="text-gray-500 dark:text-gray-400">
                                    Basic site information and maintenance settings
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Site Name -->
                                <div class="space-y-2">
                                    <Label for="site_name" class="text-sm font-medium text-gray-700 dark:text-gray-300">Site Name *</Label>
                                    <Input
                                        id="site_name"
                                        v-model="form.site_name"
                                        type="text"
                                        placeholder="Enter your site name"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.site_name }"
                                    />
                                    <p v-if="form.errors.site_name" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.site_name }}</p>
                                </div>

                                <!-- Site Description -->
                                <div class="space-y-2">
                                    <Label for="site_description" class="text-sm font-medium text-gray-700 dark:text-gray-300">Site Description</Label>
                                    <textarea
                                        id="site_description"
                                        v-model="form.site_description"
                                        rows="3"
                                        placeholder="Brief description of your site"
                                        class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 resize-none"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.site_description }"
                                    ></textarea>
                                    <p v-if="form.errors.site_description" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.site_description }}</p>
                                </div>


                                <!-- Maintenance Mode -->
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                    <div class="flex-1">
                                        <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Maintenance Mode</Label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Enable to show maintenance page to visitors</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Checkbox
                                            id="maintenance_mode"
                                            v-model:checked="form.maintenance_mode"
                                            class="data-[state=checked]:bg-indigo-600 data-[state=checked]:border-indigo-600"
                                        />
                                        <Label for="maintenance_mode" class="text-sm cursor-pointer">
                                            {{ form.maintenance_mode ? 'Enabled' : 'Disabled' }}
                                        </Label>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Branding Settings -->
                    <div v-if="activeTab === 'branding'" class="space-y-6">
                        <Card class="border-0 shadow-sm bg-white dark:bg-gray-900">
                            <CardHeader class="pb-4">
                                <CardTitle class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    <Image class="h-5 w-5" />
                                    Branding & Assets
                                </CardTitle>
                                <CardDescription class="text-gray-500 dark:text-gray-400">
                                    Upload and manage your site's visual identity
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-8">
                                <!-- Logo Upload -->
                                <div class="space-y-4">
                                    <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Site Logo</Label>
                                    <div class="flex flex-col sm:flex-row gap-4">
                                        <!-- Logo Preview -->
                                        <div class="flex-shrink-0">
                                            <div class="w-32 h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center bg-gray-50 dark:bg-gray-800/50">
                                                <img
                                                    v-if="logoPreview || settings.site_logo"
                                                    :src="logoPreview || settings.site_logo"
                                                    alt="Logo preview"
                                                    class="max-w-full max-h-full object-contain rounded"
                                                />
                                                <div v-else class="text-center">
                                                    <Image class="h-8 w-8 mx-auto text-gray-400" />
                                                    <p class="mt-1 text-xs text-gray-500">No logo</p>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Logo Controls -->
                                        <div class="flex-1 space-y-3">
                                            <div class="flex flex-wrap gap-2">
                                                <Button
                                                    variant="outline"
                                                    @click="() => logoInput?.click()"
                                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm"
                                                >
                                                    <Upload class="h-4 w-4" />
                                                    Upload Logo
                                                </Button>
                                                <Button
                                                    v-if="logoPreview || settings.site_logo"
                                                    variant="outline"
                                                    @click="removeLogo"
                                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:text-red-700"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                    Remove
                                                </Button>
                                            </div>
                                            <input
                                                ref="logoInput"
                                                type="file"
                                                accept="image/*"
                                                @change="handleLogoUpload"
                                                class="hidden"
                                            />
                                            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                                <p>• Recommended size: 200x60px</p>
                                                <p>• Max file size: 2MB</p>
                                                <p>• Formats: JPG, PNG, SVG</p>
                                            </div>
                                            <p v-if="form.errors.site_logo" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.site_logo }}</p>
                                        </div>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Favicon Upload -->
                                <div class="space-y-4">
                                    <Label class="text-sm font-medium text-gray-700 dark:text-gray-300">Favicon</Label>
                                    <div class="flex flex-col sm:flex-row gap-4">
                                        <!-- Favicon Preview -->
                                        <div class="flex-shrink-0">
                                            <div class="w-16 h-16 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center bg-gray-50 dark:bg-gray-800/50">
                                                <img
                                                    v-if="faviconPreview || settings.site_favicon"
                                                    :src="faviconPreview || settings.site_favicon"
                                                    alt="Favicon preview"
                                                    class="w-8 h-8 object-contain"
                                                />
                                                <div v-else class="text-center">
                                                    <Globe class="h-4 w-4 mx-auto text-gray-400" />
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Favicon Controls -->
                                        <div class="flex-1 space-y-3">
                                            <div class="flex flex-wrap gap-2">
                                                <Button
                                                    variant="outline"
                                                    @click="() => faviconInput?.click()"
                                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm"
                                                >
                                                    <Upload class="h-4 w-4" />
                                                    Upload Favicon
                                                </Button>
                                                <Button
                                                    v-if="faviconPreview || settings.site_favicon"
                                                    variant="outline"
                                                    @click="removeFavicon"
                                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:text-red-700"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                    Remove
                                                </Button>
                                            </div>
                                            <input
                                                ref="faviconInput"
                                                type="file"
                                                accept=".ico,.png,.jpg,.jpeg,.gif"
                                                @change="handleFaviconUpload"
                                                class="hidden"
                                            />
                                            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                                <p>• Recommended size: 32x32px</p>
                                                <p>• Max file size: 1MB</p>
                                                <p>• Formats: ICO, PNG, JPG, GIF</p>
                                            </div>
                                            <p v-if="form.errors.site_favicon" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.site_favicon }}</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Contact Settings -->
                    <div v-if="activeTab === 'contact'" class="space-y-6">
                        <Card class="border-0 shadow-sm bg-white dark:bg-gray-900">
                            <CardHeader class="pb-4">
                                <CardTitle class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    <Mail class="h-5 w-5" />
                                    Contact Information
                                </CardTitle>
                                <CardDescription class="text-gray-500 dark:text-gray-400">
                                    Manage your site's contact details
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Contact Email -->
                                <div class="space-y-2">
                                    <Label for="contact_email" class="text-sm font-medium text-gray-700 dark:text-gray-300">Contact Email</Label>
                                    <Input
                                        id="contact_email"
                                        v-model="form.contact_email"
                                        type="email"
                                        placeholder="contact@example.com"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.contact_email }"
                                    />
                                    <p v-if="form.errors.contact_email" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.contact_email }}</p>
                                </div>

                                <!-- Contact Phone -->
                                <div class="space-y-2">
                                    <Label for="contact_phone" class="text-sm font-medium text-gray-700 dark:text-gray-300">Contact Phone</Label>
                                    <Input
                                        id="contact_phone"
                                        v-model="form.contact_phone"
                                        type="tel"
                                        placeholder="+1 (555) 123-4567"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.contact_phone }"
                                    />
                                    <p v-if="form.errors.contact_phone" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.contact_phone }}</p>
                                </div>

                                <!-- Phone Number -->
                                <div class="space-y-2">
                                    <Label for="phone_number" class="text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</Label>
                                    <Input
                                        id="phone_number"
                                        v-model="form.phone_number"
                                        type="tel"
                                        placeholder="+1 (555) 987-6543"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.phone_number }"
                                    />
                                    <p v-if="form.errors.phone_number" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.phone_number }}</p>
                                </div>

                                <!-- Address -->
                                <div class="space-y-2">
                                    <Label for="address" class="text-sm font-medium text-gray-700 dark:text-gray-300">Address</Label>
                                    <textarea
                                        id="address"
                                        v-model="form.address"
                                        rows="3"
                                        placeholder="123 Main St, City, State 12345"
                                        class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 resize-none"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.address }"
                                    ></textarea>
                                    <p v-if="form.errors.address" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.address }}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Social Media Settings -->
                    <div v-if="activeTab === 'social'" class="space-y-6">
                        <Card class="border-0 shadow-sm bg-white dark:bg-gray-900">
                            <CardHeader class="pb-4">
                                <CardTitle class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    <Facebook class="h-5 w-5" />
                                    Social Media Links
                                </CardTitle>
                                <CardDescription class="text-gray-500 dark:text-gray-400">
                                    Connect your social media profiles
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Facebook -->
                                <div class="space-y-2">
                                    <Label for="social_facebook" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <Facebook class="h-4 w-4" />
                                        Facebook URL
                                    </Label>
                                    <Input
                                        id="social_facebook"
                                        v-model="form.social_facebook"
                                        type="url"
                                        placeholder="https://facebook.com/yourpage"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.social_facebook }"
                                    />
                                    <p v-if="form.errors.social_facebook" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.social_facebook }}</p>
                                </div>

                                <!-- Twitter -->
                                <div class="space-y-2">
                                    <Label for="social_twitter" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <Twitter class="h-4 w-4" />
                                        Twitter URL
                                    </Label>
                                    <Input
                                        id="social_twitter"
                                        v-model="form.social_twitter"
                                        type="url"
                                        placeholder="https://twitter.com/yourhandle"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.social_twitter }"
                                    />
                                    <p v-if="form.errors.social_twitter" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.social_twitter }}</p>
                                </div>

                                <!-- Instagram -->
                                <div class="space-y-2">
                                    <Label for="social_instagram" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <Instagram class="h-4 w-4" />
                                        Instagram URL
                                    </Label>
                                    <Input
                                        id="social_instagram"
                                        v-model="form.social_instagram"
                                        type="url"
                                        placeholder="https://instagram.com/yourhandle"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.social_instagram }"
                                    />
                                    <p v-if="form.errors.social_instagram" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.social_instagram }}</p>
                                </div>

                                <!-- LinkedIn -->
                                <div class="space-y-2">
                                    <Label for="social_linkedin" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <Linkedin class="h-4 w-4" />
                                        LinkedIn URL
                                    </Label>
                                    <Input
                                        id="social_linkedin"
                                        v-model="form.social_linkedin"
                                        type="url"
                                        placeholder="https://linkedin.com/company/yourcompany"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.social_linkedin }"
                                    />
                                    <p v-if="form.errors.social_linkedin" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.social_linkedin }}</p>
                                </div>

                                <!-- Telegram -->
                                <div class="space-y-2">
                                    <Label for="social_telegram" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <MessageCircle class="h-4 w-4" />
                                        Telegram URL
                                    </Label>
                                    <Input
                                        id="social_telegram"
                                        v-model="form.social_telegram"
                                        type="url"
                                        placeholder="https://t.me/yourchannel"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.social_telegram }"
                                    />
                                    <p v-if="form.errors.social_telegram" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.social_telegram }}</p>
                                </div>

                                <!-- Viber -->
                                <div class="space-y-2">
                                    <Label for="social_viber" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <Phone class="h-4 w-4" />
                                        Viber URL
                                    </Label>
                                    <Input
                                        id="social_viber"
                                        v-model="form.social_viber"
                                        type="url"
                                        placeholder="viber://chat?number=+1234567890"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.social_viber }"
                                    />
                                    <p v-if="form.errors.social_viber" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.social_viber }}</p>
                                </div>

                                <!-- WhatsApp -->
                                <div class="space-y-2">
                                    <Label for="social_whatsapp" class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <MessageCircle class="h-4 w-4" />
                                        WhatsApp Number
                                    </Label>
                                    <Input
                                        id="social_whatsapp"
                                        v-model="form.social_whatsapp"
                                        type="tel"
                                        placeholder="+1234567890"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.social_whatsapp }"
                                    />
                                    <p v-if="form.errors.social_whatsapp" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.social_whatsapp }}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Advanced Settings -->
                    <div v-if="activeTab === 'advanced'" class="space-y-6">
                        <Card class="border-0 shadow-sm bg-white dark:bg-gray-900">
                            <CardHeader class="pb-4">
                                <CardTitle class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    <Settings class="h-5 w-5" />
                                    Advanced Settings
                                </CardTitle>
                                <CardDescription class="text-gray-500 dark:text-gray-400">
                                    Analytics configuration
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">

                                <!-- Google Analytics -->
                                <div class="space-y-2">
                                    <Label for="google_analytics_id" class="text-sm font-medium text-gray-700 dark:text-gray-300">Google Analytics ID</Label>
                                    <Input
                                        id="google_analytics_id"
                                        v-model="form.google_analytics_id"
                                        type="text"
                                        placeholder="G-XXXXXXXXXX"
                                        class="w-full"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.google_analytics_id }"
                                    />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Enter your Google Analytics tracking ID</p>
                                    <p v-if="form.errors.google_analytics_id" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.google_analytics_id }}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
