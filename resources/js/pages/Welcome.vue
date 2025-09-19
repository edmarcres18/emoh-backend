<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { useSiteSettings } from '@/composables/useSiteSettings'
import { useAuth } from '@/composables/useAuth'
import { Building2, ArrowRight } from 'lucide-vue-next'

const { siteSettings } = useSiteSettings()
const { user } = useAuth()
</script>

<template>
    <Head title="Welcome" />
    
    <div class="min-h-screen bg-gradient-to-br from-orange-50 via-white to-yellow-50 dark:from-black dark:via-gray-900 dark:to-gray-800">
        <!-- Hero Section -->
        <div class="relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(249, 115, 22, 0.3) 1px, transparent 0); background-size: 20px 20px;"></div>
            </div>
            
            <!-- Navigation -->
            <nav class="relative z-10 bg-white/80 dark:bg-black/80 backdrop-blur-sm border-b border-gray-200/50 dark:border-gray-700/50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-orange-600 rounded-lg flex items-center justify-center">
                                <Building2 class="w-5 h-5 text-white" />
                            </div>
                            <span class="text-xl font-bold text-black dark:text-white">
                                {{ siteSettings?.site_name || 'EMOH' }}
                            </span>
                        </div>
                        <div v-if="user" class="flex items-center space-x-4">
                            <Link
                                href="/dashboard"
                                class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors duration-200"
                            >
                                Dashboard
                                <ArrowRight class="w-4 h-4 ml-2" />
                            </Link>
                        </div>
                        <div v-else class="flex items-center space-x-4">
                            <Link
                                href="/login"
                                class="text-gray-600 hover:text-black dark:text-gray-300 dark:hover:text-white font-medium transition-colors duration-200"
                            >
                                Sign In
                            </Link>
                            <Link
                                href="/register"
                                class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors duration-200"
                            >
                                Get Started
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Hero Content -->
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-32">
                <div class="text-center">
                    <!-- Logo/Icon -->
                    <div class="flex justify-center mb-8">
                        <div class="w-24 h-24 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-xl">
                            <Building2 class="w-12 h-12 text-white" />
                        </div>
                    </div>

                    <!-- Main Heading -->
                    <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold text-black dark:text-white mb-6">
                        Welcome to
                        <span class="bg-gradient-to-r from-orange-600 to-orange-500 bg-clip-text text-transparent">
                            {{ siteSettings?.site_name || 'EMOH' }}
                        </span>
                    </h1>

                    <!-- Subtitle -->
                    <p class="text-xl md:text-2xl text-gray-600 dark:text-gray-300 mb-12 max-w-3xl mx-auto leading-relaxed">
                        Your comprehensive real estate management platform. Streamline property listings, manage users, and grow your business with our powerful tools.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <Link
                            v-if="user"
                            href="/dashboard"
                            class="inline-flex items-center px-8 py-4 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-1"
                        >
                            Go to Dashboard
                            <ArrowRight class="w-5 h-5 ml-2" />
                        </Link>
                        <template v-else>
                            <Link
                                href="/register"
                                class="inline-flex items-center px-8 py-4 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-1"
                            >
                                Get Started Free
                                <ArrowRight class="w-5 h-5 ml-2" />
                            </Link>
                            <Link
                                href="/login"
                                class="inline-flex items-center px-8 py-4 bg-white hover:bg-gray-50 text-black font-semibold rounded-xl shadow-lg hover:shadow-xl border border-gray-200 transition-all duration-200 transform hover:-translate-y-1 dark:bg-black dark:text-white dark:border-gray-600 dark:hover:bg-gray-800"
                            >
                                Sign In
                            </Link>
                        </template>
                    </div>

                    <!-- Features Preview -->
                    <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                        <div class="text-center p-6 bg-white/60 dark:bg-black/60 backdrop-blur-sm rounded-2xl border border-gray-200/50 dark:border-gray-700/50">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <Building2 class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Property Management</h3>
                            <p class="text-gray-600 dark:text-gray-400">Easily manage your property listings, photos, and details in one place.</p>
                        </div>
                        
                        <div class="text-center p-6 bg-white/60 dark:bg-black/60 backdrop-blur-sm rounded-2xl border border-gray-200/50 dark:border-gray-700/50">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <Building2 class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">User Management</h3>
                            <p class="text-gray-600 dark:text-gray-400">Control user access and permissions with advanced role-based system.</p>
                        </div>
                        
                        <div class="text-center p-6 bg-white/60 dark:bg-black/60 backdrop-blur-sm rounded-2xl border border-gray-200/50 dark:border-gray-700/50">
                            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <Building2 class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            </div>
                            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Analytics & Reports</h3>
                            <p class="text-gray-600 dark:text-gray-400">Track performance and generate insights with comprehensive reporting.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
