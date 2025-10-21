<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { getCurrencySymbol } from '@/utils/currency';
import SearchableSelect from '@/components/SearchableSelect.vue';

interface Category {
    id: number;
    name: string;
}

interface Location {
    id: number;
    name: string;
}

interface StatusOption {
    value: string;
    label: string;
}

interface Props {
    categories: Category[];
    locations: Location[];
    statusOptions: StatusOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Properties',
        href: '/properties',
    },
    {
        title: 'Create Property',
        href: '/properties/create',
    },
];

const form = useForm({
    category_id: '',
    location_id: '',
    property_name: '',
    estimated_monthly: '',
    lot_area: '',
    floor_area: '',
    details: '',
    status: 'Available',
    is_featured: false,
    images: [] as File[],
});

const isSubmitting = ref(false);
const imagePreview = ref<string[]>([]);

const submit = () => {
    if (isSubmitting.value) return;
    
    isSubmitting.value = true;
    
    // Create FormData for multipart/form-data submission
    const formData = new FormData();
    
    // Add all form fields
    formData.append('category_id', form.category_id.toString());
    formData.append('location_id', form.location_id.toString());
    formData.append('property_name', form.property_name);
    formData.append('status', form.status);
    formData.append('is_featured', form.is_featured ? '1' : '0');
    
    // Add optional numeric fields only if they have values
    if (form.estimated_monthly && form.estimated_monthly !== '') {
        formData.append('estimated_monthly', form.estimated_monthly.toString());
    }
    if (form.lot_area && form.lot_area !== '') {
        formData.append('lot_area', form.lot_area.toString());
    }
    if (form.floor_area && form.floor_area !== '') {
        formData.append('floor_area', form.floor_area.toString());
    }
    
    // Add details if present
    if (form.details && form.details.trim() !== '') {
        formData.append('details', form.details);
    }
    
    // Add images
    if (form.images && form.images.length > 0) {
        form.images.forEach((image, index) => {
            formData.append(`images[${index}]`, image);
        });
    }
    
    // Use router.post for FormData submission
    router.post('/properties', formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by redirect
            console.log('Property created successfully');
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const cancel = () => {
    window.history.back();
};

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = target.files;
    
    if (files) {
        const newFiles = Array.from(files);
        
        // Validate file count (max 10 images)
        const totalImages = form.images.length + newFiles.length;
        if (totalImages > 10) {
            alert(`You can only upload a maximum of 10 images. You are trying to add ${newFiles.length} more images to ${form.images.length} existing images.`);
            return;
        }
        
        // Validate file size (100MB max per image)
        const maxSize = 100 * 1024 * 1024; // 100MB in bytes
        const invalidFiles = newFiles.filter(file => file.size > maxSize);
        if (invalidFiles.length > 0) {
            alert(`Some images exceed the 100MB size limit. Please choose smaller images.`);
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const invalidTypes = newFiles.filter(file => !allowedTypes.includes(file.type));
        if (invalidTypes.length > 0) {
            alert('Only JPEG, PNG, GIF, and WebP image formats are allowed.');
            return;
        }
        
        form.images = [...form.images, ...newFiles];
        
        // Generate previews
        newFiles.forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (e.target?.result) {
                    imagePreview.value.push(e.target.result as string);
                }
            };
            reader.readAsDataURL(file);
        });
        
        // Reset input to allow re-uploading same file
        target.value = '';
    }
};

const removeImage = (index: number) => {
    form.images.splice(index, 1);
    imagePreview.value.splice(index, 1);
};

// Computed property to check if form is valid
const isFormValid = computed(() => {
    return (
        form.property_name.trim() !== '' &&
        form.category_id !== '' &&
        form.location_id !== '' &&
        form.status !== ''
    );
});

// Computed property for image upload limit
const canAddMoreImages = computed(() => {
    return form.images.length < 10;
});
</script>

<template>
    <Head title="Create Property" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Create Property</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a new property to your listings</p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Property Name -->
                        <div class="md:col-span-2">
                            <label for="property_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Property Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="property_name"
                                v-model="form.property_name"
                                type="text"
                                placeholder="Enter property name..."
                                :class="[
                                    'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.property_name 
                                        ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                        : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                                ]"
                                :disabled="isSubmitting"
                                required
                            />
                            <div v-if="form.errors.property_name" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.property_name }}
                            </div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <SearchableSelect
                                v-model="form.category_id"
                                :options="categories"
                                primary-key="name"
                                value-key="id"
                                placeholder="Select a category..."
                                :error="form.errors.category_id"
                                :disabled="isSubmitting"
                            />
                            <div v-if="form.errors.category_id" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.category_id }}
                            </div>
                        </div>

                        <!-- Location -->
                        <div>
                            <label for="location_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Location <span class="text-red-500">*</span>
                            </label>
                            <SearchableSelect
                                v-model="form.location_id"
                                :options="locations"
                                primary-key="name"
                                value-key="id"
                                placeholder="Select a location..."
                                :error="form.errors.location_id"
                                :disabled="isSubmitting"
                            />
                            <div v-if="form.errors.location_id" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.location_id }}
                            </div>
                        </div>
                    </div>

                    <!-- Financial & Area Information -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Estimated Monthly Rent -->
                        <div>
                            <label for="estimated_monthly" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Monthly Rent (₱)
                            </label>
                            <input
                                id="estimated_monthly"
                                v-model="form.estimated_monthly"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                :class="[
                                    'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.estimated_monthly 
                                        ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                        : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                                ]"
                                :disabled="isSubmitting"
                            />
                            <div v-if="form.errors.estimated_monthly" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.estimated_monthly }}
                            </div>
                        </div>

                        <!-- Lot Area -->
                        <div>
                            <label for="lot_area" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Lot Area (sq ft)
                            </label>
                            <input
                                id="lot_area"
                                v-model="form.lot_area"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                :class="[
                                    'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.lot_area 
                                        ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                        : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                                ]"
                                :disabled="isSubmitting"
                            />
                            <div v-if="form.errors.lot_area" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.lot_area }}
                            </div>
                        </div>

                        <!-- Floor Area -->
                        <div>
                            <label for="floor_area" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Floor Area (sq ft)
                            </label>
                            <input
                                id="floor_area"
                                v-model="form.floor_area"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                :class="[
                                    'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                    form.errors.floor_area 
                                        ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                        : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                                ]"
                                :disabled="isSubmitting"
                            />
                            <div v-if="form.errors.floor_area" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.floor_area }}
                            </div>
                        </div>
                    </div>

                    <!-- Status and Featured -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <SearchableSelect
                                v-model="form.status"
                                :options="statusOptions"
                                primary-key="label"
                                value-key="value"
                                placeholder="Select status..."
                                :error="form.errors.status"
                                :disabled="isSubmitting"
                            />
                            <div v-if="form.errors.status" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.status }}
                            </div>
                        </div>

                        <!-- Featured -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Featured Property
                            </label>
                            <div class="flex items-center">
                                <input
                                    id="is_featured"
                                    v-model="form.is_featured"
                                    type="checkbox"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded"
                                    :disabled="isSubmitting"
                                />
                                <label for="is_featured" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                    Mark this property as featured
                                </label>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Featured properties will be highlighted in listings and search results.
                            </p>
                        </div>
                    </div>

                    <!-- Details -->
                    <div>
                        <label for="details" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Details (For Lease)
                        </label>
                        <textarea
                            id="details"
                            v-model="form.details"
                            rows="4"
                            placeholder="Enter property details, lease terms, amenities, etc..."
                            :class="[
                                'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none',
                                form.errors.details 
                                    ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                            ]"
                            :disabled="isSubmitting"
                        />
                        <div v-if="form.errors.details" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ form.errors.details }}
                        </div>
                    </div>

                    <!-- Images Upload -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Property Images
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                    <label for="images" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                        <span>Upload images</span>
                                        <input 
                                            id="images" 
                                            type="file" 
                                            class="sr-only" 
                                            multiple 
                                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" 
                                            @change="handleImageUpload" 
                                            :disabled="isSubmitting || !canAddMoreImages"
                                        >
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF, WebP up to 100MB each</p>
                            </div>
                        </div>
                        
                        <!-- Image Previews -->
                        <div v-if="imagePreview.length > 0" class="mt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Image Previews</h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ imagePreview.length }} of 10 images</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                <div v-for="(preview, index) in imagePreview" :key="index" class="relative group">
                                    <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                                        <img 
                                            :src="preview" 
                                            :alt="`Preview ${index + 1}`" 
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        >
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeImage(index)"
                                        class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 shadow-lg opacity-0 group-hover:opacity-100 transition-all duration-200 transform hover:scale-110"
                                        :disabled="isSubmitting"
                                        title="Remove image"
                                    >
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <div class="absolute bottom-2 left-2 bg-black/50 text-white text-xs px-2 py-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        {{ index + 1 }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.images" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ form.errors.images }}
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            @click="cancel"
                            :disabled="isSubmitting"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting || !isFormValid"
                            :title="!isFormValid ? 'Please fill in all required fields' : ''"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:cursor-not-allowed"
                        >
                            <svg v-if="isSubmitting" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ isSubmitting ? 'Creating...' : 'Create Property' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Help Section -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">Property Guidelines</h3>
                        <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                            <li>• Provide clear, descriptive property names</li>
                            <li>• Select appropriate category and location for better discoverability</li>
                            <li>• Include detailed lease information in the details section</li>
                            <li>• Upload high-quality images to attract potential tenants</li>
                            <li>• Set accurate pricing and area measurements</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
