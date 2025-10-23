<template>
    <Head :title="`Client: ${client.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Client Details</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">View and manage client information</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link v-if="can.edit_clients" :href="`/admin/clients/${client.id}/edit`" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Client
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Client Information Card -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Client Information</h3>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center mb-6">
                                <div class="flex-shrink-0">
                                    <img v-if="client.avatar" class="h-20 w-20 rounded-full" :src="client.avatar" :alt="client.name" />
                                    <div v-else class="h-20 w-20 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                        <span class="text-2xl font-medium text-gray-700 dark:text-gray-300">{{ client.name.charAt(0).toUpperCase() }}</span>
                                    </div>
                                </div>
                                <div class="ml-6">
                                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ client.name }}</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Client ID: {{ client.id }}</p>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <span v-if="client.email_verified_at" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Email Verified
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Email Unverified
                                        </span>
                                        <span v-if="client.google_id" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                            </svg>
                                            Google Account
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded-lg">{{ client.email }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone Number</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded-lg">{{ client.phone || 'Not provided' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Verified At</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded-lg">
                                        {{ client.email_verified_at ? formatDateTime(client.email_verified_at) : 'Not verified' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Google ID</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded-lg">{{ client.google_id || 'Not linked' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Joined Date</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded-lg">{{ formatDateTime(client.created_at) }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Updated</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800 px-3 py-2 rounded-lg">{{ formatDateTime(client.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions and Stats Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <button v-if="!client.email_verified_at" @click="openVerifyEmailModal" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Verify Email
                            </button>
                            <button v-else @click="openUnverifyEmailModal" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                Unverify Email
                            </button>
                            <button @click="openRevokeTokensModal" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Revoke All Tokens
                            </button>
                            <button v-if="can.delete_clients" @click="openDeleteModal" class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Client
                            </button>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Statistics</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Active Tokens</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ tokensCount }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Account Type</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ client.google_id ? 'Google' : 'Regular' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Status</span>
                                <span class="text-sm font-semibold" :class="client.email_verified_at ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'">
                                    {{ client.email_verified_at ? 'Verified' : 'Unverified' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rental Statistics Section -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Total Rentals</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ rentalStats?.total || 0 }}</p>
                        </div>
                        <div class="bg-indigo-100 dark:bg-indigo-900/30 rounded-lg p-2">
                            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Active</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ rentalStats?.active || 0 }}</p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-2">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Pending</p>
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ rentalStats?.pending || 0 }}</p>
                        </div>
                        <div class="bg-yellow-100 dark:bg-yellow-900/30 rounded-lg p-2">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Terminated</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ rentalStats?.terminated || 0 }}</p>
                        </div>
                        <div class="bg-red-100 dark:bg-red-900/30 rounded-lg p-2">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Expired</p>
                            <p class="text-2xl font-bold text-gray-600 dark:text-gray-400 mt-1">{{ rentalStats?.expired || 0 }}</p>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700/30 rounded-lg p-2">
                            <svg class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Rentals Section -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Rentals</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Showing {{ recentRentals?.length || 0 }} most recent rental records</p>
                    </div>
                    <Link v-if="recentRentals && recentRentals.length > 0" href="/admin/rented" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                        View All Rentals
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden lg:block overflow-x-auto">
                    <table v-if="recentRentals && recentRentals.length > 0" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Property</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monthly Rent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Remarks</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="rental in recentRentals" :key="rental.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ rental.property_name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ rental.category }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ rental.location }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ rental.formatted_monthly_rent }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs text-gray-900 dark:text-gray-100">
                                        <div>{{ formatDate(rental.start_date) }}</div>
                                        <div class="text-gray-500 dark:text-gray-400">to {{ formatDate(rental.end_date) }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getStatusColor(rental.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                        {{ rental.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getRemarksColor(rental.remarks)" class="text-xs">
                                        {{ rental.remarks }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="`/admin/rented/${rental.id}`" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No rentals</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This client hasn't rented any properties yet.</p>
                    </div>
                </div>

                <!-- Mobile Card View -->
                <div class="lg:hidden divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-if="recentRentals && recentRentals.length > 0" v-for="rental in recentRentals" :key="rental.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <div class="flex flex-col gap-3">
                            <!-- Property Info -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ rental.property_name }}</h4>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400">
                                            <svg class="h-3.5 w-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ rental.location }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            {{ rental.category }}
                                        </span>
                                    </div>
                                </div>
                                <span :class="getStatusColor(rental.status)" class="flex-shrink-0 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium capitalize">
                                    {{ rental.status }}
                                </span>
                            </div>

                            <!-- Rental Details -->
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Monthly Rent</p>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ rental.formatted_monthly_rent }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Security Deposit</p>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ rental.formatted_security_deposit }}</p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Rental Period</p>
                                    <p class="text-xs text-gray-900 dark:text-gray-100">
                                        {{ formatDate(rental.start_date) }} - {{ formatDate(rental.end_date) }}
                                    </p>
                                </div>
                                <div class="col-span-2" v-if="rental.remarks">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Remarks</p>
                                    <p :class="getRemarksColor(rental.remarks)" class="text-xs">
                                        {{ rental.remarks }}
                                    </p>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <Link :href="`/admin/rented/${rental.id}`" class="inline-flex items-center justify-center gap-2 px-3 py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 text-xs font-medium rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors">
                                View Details
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                    <div v-else class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No rentals</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This client hasn't rented any properties yet.</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Verify Email Confirmation Modal -->
        <VerifyModal
            :show="showVerifyEmailModal"
            title="Verify Email"
            message="Are you sure you want to verify this client's email?"
            :is-loading="isVerifyingEmail"
            @close="closeVerifyEmailModal"
            @confirm="confirmVerifyEmail"
        />

        <!-- Unverify Email Confirmation Modal -->
        <UnverifyModal
            :show="showUnverifyEmailModal"
            title="Unverify Email"
            message="Are you sure you want to unverify this client's email?"
            :is-loading="isUnverifyingEmail"
            @close="closeUnverifyEmailModal"
            @confirm="confirmUnverifyEmail"
        />

        <!-- Revoke Tokens Confirmation Modal -->
        <RevokeModal
            :show="showRevokeTokensModal"
            title="Revoke All Tokens"
            message="Are you sure you want to revoke all tokens for this client? This will log them out of all devices."
            :is-loading="isRevokingTokens"
            @close="closeRevokeTokensModal"
            @confirm="confirmRevokeTokens"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            :show="showDeleteModal"
            :title="`Delete Client: ${client.name}`"
            :message="`Are you sure you want to delete this client? This action cannot be undone and will permanently remove the client record.`"
            :is-loading="isDeleting"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import DeleteModal from '@/components/DeleteModal.vue'
import VerifyModal from '@/components/VerifyModal.vue'
import UnverifyModal from '@/components/UnverifyModal.vue'
import RevokeModal from '@/components/RevokeModal.vue'
import { ref } from 'vue'

const props = defineProps({
    client: Object,
    tokensCount: Number,
    recentRentals: Array,
    rentalStats: Object,
    can: Object,
})

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin', href: '/admin' },
    { title: 'Clients', href: '/admin/clients' },
    { title: props.client.name, href: `/admin/clients/${props.client.id}` },
]

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const getStatusColor = (status) => {
    const colors = {
        'active': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'terminated': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
        'expired': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
        'ended': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
    }
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300'
}

const getRemarksColor = (remarks) => {
    if (!remarks) return 'text-gray-600 dark:text-gray-400'
    if (remarks.includes('Over Due')) return 'text-red-600 dark:text-red-400 font-semibold'
    if (remarks.includes('Due Date Today')) return 'text-orange-600 dark:text-orange-400 font-semibold'
    if (remarks.includes('Almost Due Date')) return 'text-yellow-600 dark:text-yellow-400 font-semibold'
    return 'text-green-600 dark:text-green-400'
}

// Verify Email modal state and handlers
const showVerifyEmailModal = ref(false)
const isVerifyingEmail = ref(false)
const openVerifyEmailModal = () => { showVerifyEmailModal.value = true }
const closeVerifyEmailModal = () => { if (!isVerifyingEmail.value) showVerifyEmailModal.value = false }
const confirmVerifyEmail = () => {
    if (isVerifyingEmail.value) return
    isVerifyingEmail.value = true
    router.post(`/admin/clients/${props.client.id}/verify-email`, {}, {
        preserveScroll: true,
        onSuccess: () => { showVerifyEmailModal.value = false },
        onFinish: () => { isVerifyingEmail.value = false },
    })
}

// Unverify Email modal state and handlers
const showUnverifyEmailModal = ref(false)
const isUnverifyingEmail = ref(false)
const openUnverifyEmailModal = () => { showUnverifyEmailModal.value = true }
const closeUnverifyEmailModal = () => { if (!isUnverifyingEmail.value) showUnverifyEmailModal.value = false }
const confirmUnverifyEmail = () => {
    if (isUnverifyingEmail.value) return
    isUnverifyingEmail.value = true
    router.post(`/admin/clients/${props.client.id}/unverify-email`, {}, {
        preserveScroll: true,
        onSuccess: () => { showUnverifyEmailModal.value = false },
        onFinish: () => { isUnverifyingEmail.value = false },
    })
}

// Revoke Tokens modal state and handlers
const showRevokeTokensModal = ref(false)
const isRevokingTokens = ref(false)
const openRevokeTokensModal = () => { showRevokeTokensModal.value = true }
const closeRevokeTokensModal = () => { if (!isRevokingTokens.value) showRevokeTokensModal.value = false }
const confirmRevokeTokens = () => {
    if (isRevokingTokens.value) return
    isRevokingTokens.value = true
    router.post(`/admin/clients/${props.client.id}/revoke-tokens`, {}, {
        preserveScroll: true,
        onSuccess: () => { showRevokeTokensModal.value = false },
        onFinish: () => { isRevokingTokens.value = false },
    })
}

// Delete modal state and handlers
const showDeleteModal = ref(false)
const isDeleting = ref(false)

const openDeleteModal = () => {
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        showDeleteModal.value = false
    }
}

const confirmDelete = () => {
    if (isDeleting.value) return
    isDeleting.value = true
    router.delete(`/admin/clients/${props.client.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
        },
        onFinish: () => {
            isDeleting.value = false
        }
    })
}
</script>
