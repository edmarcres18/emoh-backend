<template>
    <Head :title="`Edit Client: ${client.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Client</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update client information and settings</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Edit Form -->
                <div class="lg:col-span-2">
                    <form @submit.prevent="updateClient" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Client Information</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Avatar Display -->
                            <div class="flex items-center space-x-6">
                                <div class="flex-shrink-0">
                                    <img v-if="client.avatar" class="h-16 w-16 rounded-full" :src="client.avatar" :alt="client.name" />
                                    <div v-else class="h-16 w-16 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                        <span class="text-xl font-medium text-gray-700 dark:text-gray-300">{{ form.name.charAt(0).toUpperCase() }}</span>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ form.name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Client ID: {{ client.id }}</p>
                                </div>
                            </div>

                            <!-- Name Field -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="block w-full px-4 py-3 border-0 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                                    :class="{ 'ring-red-500 focus:ring-red-500': errors.name }"
                                />
                                <p v-if="errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.name }}</p>
                            </div>

                            <!-- Email Field -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="block w-full px-4 py-3 border-0 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                                    :class="{ 'ring-red-500 focus:ring-red-500': errors.email }"
                                />
                                <p v-if="errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.email }}</p>
                            </div>

                            <!-- Phone Field -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone Number</label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="block w-full px-4 py-3 border-0 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                                    :class="{ 'ring-red-500 focus:ring-red-500': errors.phone }"
                                />
                                <p v-if="errors.phone" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.phone }}</p>
                            </div>

                            <!-- Email Verification -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Verification</label>
                                <div class="flex items-center">
                                    <input
                                        id="email_verified"
                                        v-model="form.email_verified_at"
                                        type="checkbox"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 rounded"
                                    />
                                    <label for="email_verified" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                        Email is verified
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Check this to mark the email as verified</p>
                            </div>

                            <!-- Password Section -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                                <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Change Password</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Leave blank to keep current password</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Password</label>
                                        <input
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            class="block w-full px-4 py-3 border-0 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                                            :class="{ 'ring-red-500 focus:ring-red-500': errors.password }"
                                        />
                                        <p v-if="errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ errors.password }}</p>
                                    </div>
                                    <div>
                                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Confirm Password</label>
                                        <input
                                            id="password_confirmation"
                                            v-model="form.password_confirmation"
                                            type="password"
                                            class="block w-full px-4 py-3 border-0 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                                <Link href="/admin/clients" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors duration-200">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="inline-flex items-center gap-2 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                                >
                                    <svg v-if="processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ processing ? 'Updating...' : 'Update Client' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Client Info Sidebar -->
                <div class="space-y-6">
                    <!-- Current Status -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Current Status</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Email Status</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="client.email_verified_at ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300'">
                                    {{ client.email_verified_at ? 'Verified' : 'Unverified' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Account Type</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="client.google_id ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300'">
                                    {{ client.google_id ? 'Google' : 'Regular' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Joined</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatDate(client.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div v-if="can.delete_clients" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-red-200 dark:ring-red-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-red-200 dark:border-red-800">
                            <h3 class="text-lg font-semibold text-red-900 dark:text-red-100">Danger Zone</h3>
                        </div>
                        <div class="p-6">
                            <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Delete Client</h3>
                                        <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                                            <p>Once you delete this client, there is no going back. This will permanently delete the client account and revoke all associated tokens.</p>
                                        </div>
                                        <div class="mt-4">
                                            <button @click="deleteClient" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors duration-200">
                                                Delete Client
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    client: Object,
    can: Object,
    errors: Object,
})

const processing = ref(false)

const form = useForm({
    name: props.client.name,
    email: props.client.email,
    phone: props.client.phone || '',
    email_verified_at: !!props.client.email_verified_at,
    password: '',
    password_confirmation: '',
})

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin', href: '/admin' },
    { title: 'Clients', href: '/admin/clients' },
    { title: props.client.name, href: `/admin/clients/${props.client.id}` },
    { title: 'Edit', href: `/admin/clients/${props.client.id}/edit` },
]

const updateClient = () => {
    form.put(`/admin/clients/${props.client.id}`, {
        onStart: () => processing.value = true,
        onFinish: () => processing.value = false,
    })
}

const deleteClient = () => {
    if (confirm('Are you sure you want to delete this client? This action cannot be undone.')) {
        router.delete(`/admin/clients/${props.client.id}`)
    }
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}
</script>
