<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
    {
        title: 'Edit',
        href: `/locations/${props.location.id}/edit`,
    },
];

const form = useForm({
    name: props.location.name,
    code: props.location.code,
    description: props.location.description || '',
});

const isSubmitting = ref(false);

const submit = () => {
    isSubmitting.value = true;
    form.put(`/locations/${props.location.id}`, {
        onSuccess: () => {
            // Form will redirect automatically on success
        },
        onError: () => {
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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="`Edit ${location.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Location</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update the details for "{{ location.name }}" ({{ location.code }})</p>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <div>Created: {{ formatDate(location.created_at) }}</div>
                    <div v-if="location.updated_at !== location.created_at">
                        Updated: {{ formatDate(location.updated_at) }}
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- Location Name Field -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Location Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Enter location name..."
                            :class="[
                                'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                form.errors.name 
                                    ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                            ]"
                            :disabled="isSubmitting"
                            required
                        />
                        <div v-if="form.errors.name" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Location Code Field -->
                    <div>
                        <label for="code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Location Code <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="code"
                            v-model="form.code"
                            type="text"
                            placeholder="Enter unique location code..."
                            :class="[
                                'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                form.errors.code 
                                    ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                            ]"
                            :disabled="isSubmitting"
                            required
                        />
                        <div v-if="form.errors.code" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ form.errors.code }}
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Use a unique identifier like "NYC", "LON", or "WAREHOUSE-01". This code must be unique across all locations.
                        </p>
                    </div>

                    <!-- Description Field -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Enter location description (optional)..."
                            :class="[
                                'block w-full px-4 py-3 border rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none',
                                form.errors.description 
                                    ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/10 text-red-900 dark:text-red-100 placeholder-red-400' 
                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:border-indigo-500'
                            ]"
                            :disabled="isSubmitting"
                        />
                        <div v-if="form.errors.description" class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ form.errors.description }}
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Provide additional details about this location, such as address, purpose, or special notes.
                        </p>
                    </div>

                    <!-- Change Detection -->
                    <div v-if="form.isDirty" class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3 border border-amber-200 dark:border-amber-800">
                        <div class="flex items-center gap-2 text-sm text-amber-800 dark:text-amber-200">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            You have unsaved changes
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
                            :disabled="isSubmitting || !form.name.trim() || !form.code.trim() || !form.isDirty"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:cursor-not-allowed"
                        >
                            <svg v-if="isSubmitting" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ isSubmitting ? 'Updating...' : 'Update Location' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Location Info -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">Location Information</h3>
                        <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                            <div><span class="font-medium">ID:</span> {{ location.id }}</div>
                            <div><span class="font-medium">Current Name:</span> {{ location.name }}</div>
                            <div><span class="font-medium">Current Code:</span> {{ location.code }}</div>
                            <div v-if="location.description"><span class="font-medium">Current Description:</span> {{ location.description }}</div>
                            <div v-else class="text-gray-500 dark:text-gray-400 italic">No description set</div>
                        </div>
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
                        <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">Editing Guidelines</h3>
                        <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                            <li>• Changes will be saved immediately when you click "Update Location"</li>
                            <li>• Location codes must still be unique across your system</li>
                            <li>• Existing data using this location will automatically reflect the changes</li>
                            <li>• Consider the impact on users who may be familiar with the current code</li>
                            <li>• Changing the code may affect integrations or reports that reference it</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
