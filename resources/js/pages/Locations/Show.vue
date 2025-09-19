<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DeleteModal from '@/components/DeleteModal.vue';

interface Location {
    id: number;
    name: string;
    code: string;
    description: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    location: Location;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Locations',
        href: '/locations',
    },
    {
        title: props.location.name,
        href: `/locations/${props.location.id}`,
    },
];

const isDeleting = ref(false);
const showDeleteModal = ref(false);

const editLocation = () => {
    router.get(`/locations/${props.location.id}/edit`);
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
        router.delete(`/locations/${props.location.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
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

const goBack = () => {
    router.get('/locations');
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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
</script>

<template>
    <Head :title="location.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="flex-shrink-0 h-12 w-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ location.name }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Location Details • Code: {{ location.code }}</p>
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
                        Back to Locations
                    </button>
                    <button
                        @click="editLocation"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Location
                    </button>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Location Details Card -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Location Information</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Location Name</label>
                                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ location.name }}</div>
                            </div>

                            <!-- Code -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Location Code</label>
                                <div class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    {{ location.code }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Description</label>
                                <div v-if="location.description" class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                    {{ location.description }}
                                </div>
                                <div v-else class="text-gray-500 dark:text-gray-400 italic">
                                    No description provided
                                </div>
                            </div>

                            <!-- Metadata -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Created</label>
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatDate(location.created_at) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDateRelative(location.created_at) }}
                                    </div>
                                </div>
                                <div v-if="location.updated_at !== location.created_at">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Last Updated</label>
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatDate(location.updated_at) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatDateRelative(location.updated_at) }}
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
                                @click="editLocation"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Location
                            </button>
                            <button
                                @click="openDeleteModal"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Location
                            </button>
                        </div>
                    </div>

                    <!-- Location Stats -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Location Stats</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Location ID</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">#{{ location.id }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Location Code</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ location.code }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Name Length</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ location.name.length }} characters</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ location.description ? `${location.description.length} characters` : 'Not set' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</span>
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/20 rounded-full">
                                    <div class="h-1.5 w-1.5 bg-green-500 rounded-full"></div>
                                    Active
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
                                <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">Location Management</h4>
                                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                                    <li>• Use "Edit" to modify location details</li>
                                    <li>• Location codes should remain consistent for data integrity</li>
                                    <li>• Deleting a location is permanent and may affect related data</li>
                                    <li>• Consider the impact on existing records that reference this location</li>
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
            :title="`Delete ${location.name}`"
            :message="`Are you sure you want to delete '${location.name}' (${location.code})? This action cannot be undone and will permanently remove this location from your system.`"
            :is-loading="isDeleting"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
