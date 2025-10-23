<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { debounce } from 'lodash';
import DeleteModal from '@/components/DeleteModal.vue';
import { useAuth } from '@/composables/useAuth';
import { formatCurrency } from '@/utils/currency';

interface Property {
    id: number;
    property_name: string;
    category: { id: number; name: string };
    location: { id: number; name: string };
    status: 'Available' | 'Rented' | 'Renovation';
    estimated_monthly: number | null;
    floor_area: number | null;
    is_featured: boolean;
    created_at: string;
    images?: string[];
}

interface PaginatedProperties {
    data: Property[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    properties: PaginatedProperties;
    filters: {
        search?: string;
        sort?: string;
        category_id?: string;
        location_id?: string;
        status?: string;
        is_featured?: string;
        per_page?: number;
    };
    categories: Array<{ id: number; name: string }>;
    locations: Array<{ id: number; name: string }>;
    statusOptions: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

// Auth composable for permission checks
const { 
    canViewProperties, 
    canCreateProperties, 
    canEditProperties, 
    canDeleteProperties,
    fetchUser 
} = useAuth();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Properties',
        href: '/properties',
    },
];

// Search and filter functionality
const searchQuery = ref(props.filters.search || '');
const validSortOptions = ['latest', 'oldest', 'name_asc', 'name_desc', 'price_asc', 'price_desc', 'featured'];
const initialSort = props.filters.sort && validSortOptions.includes(props.filters.sort) 
    ? props.filters.sort 
    : 'latest';
const sortBy = ref(initialSort);
const categoryFilter = ref(props.filters.category_id || '');
const locationFilter = ref(props.filters.location_id || '');
const statusFilter = ref(props.filters.status || '');
const featuredFilter = ref(props.filters.is_featured || '');
const perPage = ref(props.filters.per_page || 10);

// Debounced search
const debouncedSearch = debounce(() => {
    const filters = {
        search: searchQuery.value,
        sort: sortBy.value,
        category_id: categoryFilter.value,
        location_id: locationFilter.value,
        status: statusFilter.value,
        is_featured: featuredFilter.value,
        per_page: perPage.value,
    };
    
    router.get('/properties', filters, {
        preserveState: true,
        replace: true,
    });
}, 300);

// Watch for changes
watch([searchQuery, sortBy, categoryFilter, locationFilter, statusFilter, featuredFilter, perPage], () => {
    debouncedSearch();
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
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

// Delete modal state
const showDeleteModal = ref(false);
const propertyToDelete = ref<Property | null>(null);
const isDeleting = ref(false);

const openDeleteModal = (property: Property) => {
    propertyToDelete.value = property;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        showDeleteModal.value = false;
        propertyToDelete.value = null;
    }
};

const confirmDelete = () => {
    if (propertyToDelete.value && !isDeleting.value) {
        isDeleting.value = true;
        router.delete(`/properties/${propertyToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                propertyToDelete.value = null;
            },
            onError: () => {
                // Error will be handled by toast notification
            },
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    }
};

const clearFilters = () => {
    searchQuery.value = '';
    categoryFilter.value = '';
    locationFilter.value = '';
    statusFilter.value = '';
    featuredFilter.value = '';
    sortBy.value = 'latest';
};

// Initialize auth on component mount
onMounted(() => {
    fetchUser();
});
</script>

<template>
    <Head title="Properties" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Properties</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your property listings and rentals</p>
                </div>
                <button 
                    v-if="canCreateProperties"
                    @click="router.get('/properties/create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Property
                </button>
            </div>

            <!-- Search and Filters Section -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 space-y-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Search -->
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search properties..."
                                class="block w-full pl-10 pr-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                            />
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-3">
                        <select v-model="categoryFilter" class="px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                        
                        <select v-model="locationFilter" class="px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Locations</option>
                            <option v-for="location in locations" :key="location.id" :value="location.id">{{ location.name }}</option>
                        </select>
                        
                        <select v-model="statusFilter" class="px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Status</option>
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                        
                        <select v-model="featuredFilter" class="px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Properties</option>
                            <option value="1">Featured Only</option>
                        </select>
                        
                        <select v-model="sortBy" class="px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="latest">Latest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                            <option value="price_asc">Price Low-High</option>
                            <option value="price_desc">Price High-Low</option>
                            <option value="featured">Featured First</option>
                        </select>
                        
                        <button @click="clearFilters" class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            Clear
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                        <select
                            v-model.number="perPage"
                            class="block px-3 py-2 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-900 px-3 py-2 rounded-lg shadow-sm">
                        <span class="font-medium">{{ properties.from || 0 }}</span>-<span class="font-medium">{{ properties.to || 0 }}</span> of <span class="font-medium">{{ properties.total }}</span> properties
                    </div>
                </div>
            </div>

            <!-- Properties Table -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Property</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Category</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Location</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Monthly Rent</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Area</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="property in properties.data" :key="property.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <!-- Property Image Thumbnail -->
                                        <div class="flex-shrink-0 h-12 w-12 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800">
                                            <img 
                                                v-if="property.images && property.images.length > 0"
                                                :src="`/storage/${property.images[0]}`" 
                                                :alt="property.property_name"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            />
                                            <div v-else class="h-full w-full flex items-center justify-center">
                                                <svg class="h-6 w-6 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center gap-2">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ property.property_name }}</div>
                                                <div v-if="property.is_featured" class="inline-flex items-center px-2 py-1 text-xs font-medium text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/20 rounded-full">
                                                    <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                    Featured
                                                </div>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                ID: {{ property.id }} • {{ (property.images?.length || 0) }} {{ (property.images?.length === 1) ? 'image' : 'images' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ property.category.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ property.location.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusColor(property.status)]">
                                        {{ property.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(property.estimated_monthly) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ property.floor_area ? `${property.floor_area} sq ft` : 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            v-if="canViewProperties"
                                            @click="router.get(`/properties/${property.id}`)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-md transition-colors duration-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                        <button 
                                            v-if="canEditProperties"
                                            @click="router.get(`/properties/${property.id}/edit`)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 rounded-md transition-colors duration-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button 
                                            v-if="canDeleteProperties"
                                            @click="openDeleteModal(property)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md transition-colors duration-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Empty State -->
                            <tr v-if="properties.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                                        </svg>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                            {{ searchQuery ? 'No properties found' : 'No properties yet' }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ searchQuery ? 'Try adjusting your search terms or filters.' : 'Get started by creating your first property.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="properties.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Showing {{ properties.from }} to {{ properties.to }} of {{ properties.total }} results
                        </div>
                        <div class="flex items-center gap-2">
                            <template v-for="link in properties.links" :key="link.label">
                                <button
                                    v-if="link.url"
                                    @click="router.get(link.url)"
                                    :class="[
                                        'px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                                        link.active 
                                            ? 'bg-indigo-600 text-white' 
                                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    :class="[
                                        'px-3 py-2 text-sm font-medium rounded-md',
                                        'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            :show="showDeleteModal"
            :title="`Delete ${propertyToDelete?.property_name}`"
            :message="`Are you sure you want to delete '${propertyToDelete?.property_name}'? This action cannot be undone and will permanently remove this property from your system.`"
            :is-loading="isDeleting"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
