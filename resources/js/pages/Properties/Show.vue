<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DeleteModal from '@/components/DeleteModal.vue';
import { formatCurrency } from '@/utils/currency';

interface Property {
    id: number;
    property_name: string;
    category: { id: number; name: string };
    location: { id: number; name: string };
    estimated_monthly: number | null;
    lot_area: number | null;
    floor_area: number | null;
    details: string | null;
    status: string;
    is_featured: boolean;
    images: string[] | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    property: Property;
    similarProperties: Property[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Properties', href: '/properties' },
    { title: props.property.property_name, href: `/properties/${props.property.id}` },
];

const isDeleting = ref(false);
const showDeleteModal = ref(false);

const editProperty = () => {
    router.get(`/properties/${props.property.id}/edit`);
};

const openDeleteModal = () => {
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        showDeleteModal.value = false;
    }
};

const confirmDelete = () => {
    if (!isDeleting.value) {
        isDeleting.value = true;
        router.delete(`/properties/${props.property.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
            },
            onError: () => {},
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    }
};

const goBack = () => {
    router.get('/properties');
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const formatDateRelative = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMs = now.getTime() - date.getTime();
    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));
    
    if (diffInDays === 0) return 'Today';
    if (diffInDays === 1) return 'Yesterday';
    if (diffInDays < 7) return `${diffInDays} days ago`;
    if (diffInDays < 30) return `${Math.floor(diffInDays / 7)} weeks ago`;
    if (diffInDays < 365) return `${Math.floor(diffInDays / 30)} months ago`;
    return `${Math.floor(diffInDays / 365)} years ago`;
};

// Currency formatting is now handled by the imported utility function

const getStatusColor = (status: string) => {
    switch (status) {
        case 'Available': return 'text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/20';
        case 'Rented': return 'text-blue-700 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/20';
        case 'Renovation': return 'text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/20';
        default: return 'text-gray-700 dark:text-gray-400 bg-gray-100 dark:bg-gray-900/20';
    }
};

const toggleFeatured = () => {
    router.patch(`/properties/${props.property.id}/toggle-featured`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success toast will be handled by the backend response
        },
        onError: () => {
            // Error toast will be handled by the backend response
        },
    });
};
</script>

<template>
    <Head :title="property.property_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="flex-shrink-0 h-12 w-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ property.property_name }}</h1>
                                <div v-if="property.is_featured" class="inline-flex items-center px-3 py-1 text-sm font-medium text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/20 rounded-full">
                                    <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    Featured
                                </div>
                                <span :class="['inline-flex items-center px-3 py-1 text-sm font-medium rounded-full', getStatusColor(property.status)]">
                                    {{ property.status }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Property Details</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="goBack"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors duration-200"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Properties
                    </button>
                    <button
                        @click="editProperty"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Property
                    </button>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Property Details Card -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Images Gallery -->
                    <div v-if="property.images && property.images.length > 0" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Property Images</h2>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ property.images.length }} {{ property.images.length === 1 ? 'image' : 'images' }}</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <!-- Main Image Display -->
                            <div v-if="property.images.length === 1" class="aspect-[16/10] mb-4">
                                <img 
                                    :src="`/storage/${property.images[0]}`" 
                                    :alt="property.property_name" 
                                    class="w-full h-full object-cover rounded-xl shadow-lg"
                                    loading="lazy"
                                >
                            </div>
                            
                            <!-- Multiple Images Grid -->
                            <div v-else-if="property.images.length <= 4" class="grid gap-4" :class="{
                                'grid-cols-1': property.images.length === 1,
                                'grid-cols-1 md:grid-cols-2': property.images.length === 2,
                                'grid-cols-1 md:grid-cols-2 lg:grid-cols-3': property.images.length === 3,
                                'grid-cols-1 md:grid-cols-2 lg:grid-cols-2': property.images.length === 4
                            }">
                                <div v-for="(image, index) in property.images" :key="index" 
                                     class="group relative overflow-hidden rounded-xl shadow-md hover:shadow-lg transition-all duration-300"
                                     :class="{
                                         'aspect-[16/10]': property.images.length <= 2,
                                         'aspect-[4/3]': property.images.length > 2
                                     }">
                                    <img 
                                        :src="`/storage/${image}`" 
                                        :alt="`${property.property_name} - Image ${index + 1}`" 
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                    >
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300"></div>
                                </div>
                            </div>
                            
                            <!-- Many Images Layout (5+) -->
                            <div v-else class="space-y-4">
                                <!-- Featured Image -->
                                <div class="aspect-[16/9] rounded-xl overflow-hidden shadow-lg">
                                    <img 
                                        :src="`/storage/${property.images[0]}`" 
                                        :alt="`${property.property_name} - Main Image`" 
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    >
                                </div>
                                
                                <!-- Thumbnail Grid -->
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                    <div v-for="(image, index) in property.images.slice(1)" :key="index + 1" 
                                         class="group relative aspect-square overflow-hidden rounded-lg shadow-sm hover:shadow-md transition-all duration-300">
                                        <img 
                                            :src="`/storage/${image}`" 
                                            :alt="`${property.property_name} - Image ${index + 2}`" 
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                            loading="lazy"
                                        >
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- No Images Placeholder -->
                    <div v-else class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Property Images</h2>
                        </div>
                        <div class="p-6">
                            <div class="text-center py-12">
                                <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No images uploaded</h3>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Add images to showcase this property</p>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Property Information</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Property Name</label>
                                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ property.property_name }}</div>
                            </div>

                            <!-- Category and Location -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Category</label>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ property.category.name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Location</label>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ property.location.name }}</div>
                                </div>
                            </div>

                            <!-- Financial Information -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Monthly Rent</label>
                                    <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(property.estimated_monthly) }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Lot Area</label>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ property.lot_area ? `${property.lot_area} sq ft` : 'Not specified' }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Floor Area</label>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ property.floor_area ? `${property.floor_area} sq ft` : 'Not specified' }}</div>
                                </div>
                            </div>

                            <!-- Details -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Details</label>
                                <div v-if="property.details" class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                                    {{ property.details }}
                                </div>
                                <div v-else class="text-gray-500 dark:text-gray-400 italic">
                                    No details provided
                                </div>
                            </div>

                            <!-- Metadata -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Created</label>
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatDate(property.created_at) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDateRelative(property.created_at) }}
                                    </div>
                                </div>
                                <div v-if="property.updated_at !== property.created_at">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Last Updated</label>
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatDate(property.updated_at) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDateRelative(property.updated_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Similar Properties -->
                    <div v-if="similarProperties.length > 0" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Similar Properties</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="similar in similarProperties" :key="similar.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer" @click="router.get(`/properties/${similar.id}`)">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ similar.property_name }}</h3>
                                        <span :class="['inline-flex items-center px-2 py-1 text-xs font-medium rounded-full', getStatusColor(similar.status)]">
                                            {{ similar.status }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <div>{{ similar.category.name }} • {{ similar.location.name }}</div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100 mt-1">{{ formatCurrency(similar.estimated_monthly) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions & Stats Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <button
                                @click="editProperty"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Property
                            </button>
                            <button
                                @click="toggleFeatured"
                                :class="[
                                    'w-full inline-flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900',
                                    property.is_featured 
                                        ? 'bg-yellow-600 hover:bg-yellow-700 text-white focus:ring-yellow-500' 
                                        : 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500'
                                ]"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                {{ property.is_featured ? 'Remove Featured' : 'Mark as Featured' }}
                            </button>
                            <button
                                @click="openDeleteModal"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Property
                            </button>
                        </div>
                    </div>

                    <!-- Property Stats -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Property Stats</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Property ID</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">#{{ property.id }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</span>
                                <span :class="['inline-flex items-center px-2 py-1 text-xs font-medium rounded-full', getStatusColor(property.status)]">
                                    {{ property.status }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Featured</span>
                                <span :class="[
                                    'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full',
                                    property.is_featured 
                                        ? 'text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/20' 
                                        : 'text-gray-700 dark:text-gray-400 bg-gray-100 dark:bg-gray-900/20'
                                ]">
                                    <div :class="['h-1.5 w-1.5 rounded-full', property.is_featured ? 'bg-yellow-500' : 'bg-gray-400']"></div>
                                    {{ property.is_featured ? 'Yes' : 'No' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Images</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ property.images ? property.images.length : 0 }} uploaded
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Help Section -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">Property Management</h4>
                                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                                    <li>• Use "Edit" to modify property details</li>
                                    <li>• Toggle featured status to highlight properties</li>
                                    <li>• Deleting a property is permanent</li>
                                    <li>• Check similar properties for market insights</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            :show="showDeleteModal"
            :title="`Delete ${property.property_name}`"
            :message="`Are you sure you want to delete '${property.property_name}'? This action cannot be undone and will permanently remove this property from your system.`"
            :is-loading="isDeleting"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
