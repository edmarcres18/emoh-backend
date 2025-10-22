<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import Icon from '@/components/Icon.vue';
import axios from 'axios';

// Props
interface Props {
    slug?: string;
}

const props = withDefaults(defineProps<Props>(), {
    slug: 'overview'
});

// Navigation state
const activeSection = ref(props.slug || 'overview');
const sidebarOpen = ref(false);

// Navigation items with slugs
const navigationItems = [
    { id: 'overview', slug: 'overview', title: 'Overview', icon: 'home' },
    { id: 'features', slug: 'features', title: 'Features', icon: 'star' },
    { id: 'architecture', slug: 'architecture', title: 'Architecture', icon: 'layers' },
    { id: 'data-models', slug: 'data-models', title: 'Data Models', icon: 'database' },
    { id: 'api-endpoints', slug: 'api-endpoints', title: 'API Endpoints', icon: 'code' },
    { id: 'vue-components', slug: 'vue-components', title: 'Vue Components', icon: 'component' },
    { id: 'deployment', slug: 'deployment', title: 'Deployment', icon: 'rocket' },
    { id: 'support', slug: 'support', title: 'Support', icon: 'help-circle' }
];

// Features data
const features = [
    {
        title: 'Property Management',
        description: 'Complete property listing and management system with categories, locations, and status tracking.',
        icon: 'home',
        color: 'bg-blue-500',
        details: [
            'Property CRUD operations',
            'Image gallery management',
            'Featured property system',
            'Status automation (Available/Rented/Renovation)',
            'Bulk operations support'
        ]
    },
    {
        title: 'Rental Management',
        description: 'Comprehensive rental agreement management with client tracking and automated status updates.',
        icon: 'fileText',
        color: 'bg-green-500',
        details: [
            'Rental contract creation and management',
            'Client-property relationship tracking',
            'Automated status synchronization',
            'Rental history and analytics',
            'Contract termination and renewal'
        ]
    },
    {
        title: 'User Management',
        description: 'Role-based access control with admin, system admin, and client user types.',
        icon: 'users',
        color: 'bg-purple-500',
        details: [
            'Multi-role user system (System Admin, Admin, Client)',
            'Email verification with OTP',
            'Google OAuth integration',
            'Permission-based access control',
            'User activity tracking'
        ]
    },
    {
        title: 'Dashboard & Analytics',
        description: 'Comprehensive dashboard with real-time statistics and performance metrics.',
        icon: 'barChart3',
        color: 'bg-orange-500',
        details: [
            'Role-based dashboard views',
            'Real-time statistics',
            'Property performance analytics',
            'Revenue tracking and reporting',
            'System monitoring'
        ]
    },
    {
        title: 'Database Management',
        description: 'Automated database backup and restore functionality with retention policies.',
        icon: 'database',
        color: 'bg-red-500',
        details: [
            'Automated database backups',
            'Backup restoration',
            'Retention policy management',
            'Backup status monitoring',
            'Export functionality'
        ]
    },
    {
        title: 'Site Configuration',
        description: 'Comprehensive site settings management including branding and system configuration.',
        icon: 'settings',
        color: 'bg-gray-500',
        details: [
            'Site branding (logo, favicon)',
            'Contact information management',
            'System configuration',
            'Maintenance mode control',
            'Cache management'
        ]
    }
];

// Technology stack
const techStack = [
    { name: 'Laravel 12', description: 'Backend framework', icon: 'server' },
    { name: 'Vue 3', description: 'Frontend framework', icon: 'component' },
    { name: 'Inertia.js', description: 'SPA bridge', icon: 'link' },
    { name: 'Tailwind CSS', description: 'Styling framework', icon: 'palette' },
    { name: 'TypeScript', description: 'Type safety', icon: 'code' },
    { name: 'SQLite/MySQL', description: 'Database', icon: 'database' },
    { name: 'Laravel Sanctum', description: 'API authentication', icon: 'shield' },
    { name: 'Spatie Permissions', description: 'Role management', icon: 'users' }
];

// Data models
const dataModels = [
    {
        name: 'Property',
        description: 'Core property entity with location, category, and rental information',
        fields: [
            'id, property_name, estimated_monthly',
            'lot_area, floor_area, details',
            'status, is_featured, images',
            'category_id, location_id'
        ],
        relationships: ['belongsTo Category', 'belongsTo Location', 'hasMany Rentals']
    },
    {
        name: 'Client',
        description: 'Client/tenant management with authentication and rental history',
        fields: [
            'id, name, email, phone',
            'google_id, avatar',
            'email_verified_at, is_active',
            'otp fields for verification'
        ],
        relationships: ['hasMany Rentals', 'hasMany activeRentals']
    },
    {
        name: 'Rented',
        description: 'Rental agreement management with status tracking',
        fields: [
            'id, client_id, property_id',
            'monthly_rent, security_deposit',
            'start_date, end_date, status',
            'terms_conditions, documents'
        ],
        relationships: ['belongsTo Client', 'belongsTo Property']
    },
    {
        name: 'Category',
        description: 'Property categorization system',
        fields: ['id, name, description'],
        relationships: ['hasMany Properties']
    },
    {
        name: 'Locations',
        description: 'Geographic location management',
        fields: ['id, name, code, description'],
        relationships: ['hasMany Properties']
    },
    {
        name: 'User',
        description: 'System user management with roles',
        fields: ['id, name, email, password'],
        relationships: ['hasMany Roles (Spatie)']
    }
];

// API endpoints
const apiEndpoints = [
    {
        category: 'Authentication',
        endpoints: [
            { method: 'POST', path: '/api/client/register', description: 'Client registration' },
            { method: 'POST', path: '/api/client/login', description: 'Client login' },
            { method: 'GET', path: '/api/client/auth/google', description: 'Google OAuth redirect' },
            { method: 'POST', path: '/api/client/logout', description: 'Client logout' }
        ]
    },
    {
        category: 'Properties',
        endpoints: [
            { method: 'GET', path: '/api/properties/by-status-properties', description: 'Get properties by status' },
            { method: 'GET', path: '/api/properties/featured-properties', description: 'Get featured properties' },
            { method: 'GET', path: '/api/properties/stats-properties', description: 'Get property statistics' },
            { method: 'GET', path: '/api/properties/statuses-properties', description: 'Get available statuses' }
        ]
    },
    {
        category: 'Admin Management',
        endpoints: [
            { method: 'GET', path: '/admin/api/users', description: 'Get all users' },
            { method: 'GET', path: '/admin/api/clients', description: 'Get all clients' },
            { method: 'GET', path: '/admin/api/rented', description: 'Get all rentals' },
            { method: 'POST', path: '/admin/api/rented', description: 'Create new rental' },
            { method: 'PUT', path: '/admin/api/rented/{id}', description: 'Update rental' }
        ]
    }
];

// Computed properties
const currentSection = computed(() => {
    return navigationItems.find(item => item.id === activeSection.value);
});

// Dynamic documentation data
const loading = ref(true);
const error = ref<string | null>(null);
const dynamic = ref<{
    techStack?: any[];
    dataModels?: any[];
    apiEndpoints?: any[];
    counts?: Record<string, number>;
} | null>(null);

const techStackData = computed(() => (dynamic.value?.techStack?.length ? dynamic.value.techStack : techStack));
const dataModelsData = computed(() => (dynamic.value?.dataModels?.length ? dynamic.value.dataModels : dataModels));
const apiEndpointsData = computed(() => (dynamic.value?.apiEndpoints?.length ? dynamic.value.apiEndpoints : apiEndpoints));
const counts = computed(() => {
    if (dynamic.value?.counts) return dynamic.value.counts as Record<string, number>;
    const endpointsCount = apiEndpoints.reduce((sum, c) => sum + c.endpoints.length, 0);
    return {
        features: features.length,
        models: dataModels.length,
        endpoints: endpointsCount,
        components: 50,
    } as Record<string, number>;
});

// Methods
const scrollToSection = (sectionId: string) => {
    const navItem = navigationItems.find(item => item.id === sectionId);
    if (navItem) {
        activeSection.value = sectionId;

        // Update URL with slug
        router.visit(`/documentation/${navItem.slug}`, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                // Scroll to section after navigation
                setTimeout(() => {
                    const element = document.getElementById(sectionId);
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth' });
                    }
                }, 100);
            }
        });
    }
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

// Watch for slug changes from URL
watch(() => props.slug, (newSlug) => {
    if (newSlug) {
        const navItem = navigationItems.find(item => item.slug === newSlug);
        if (navItem) {
            activeSection.value = navItem.id;
            // Scroll to section after a short delay
            setTimeout(() => {
                const element = document.getElementById(navItem.id);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth' });
                }
            }, 100);
        }
    }
});

// Initialize section based on slug
onMounted(() => {
    if (props.slug) {
        const navItem = navigationItems.find(item => item.slug === props.slug);
        if (navItem) {
            activeSection.value = navItem.id;
            // Scroll to section after component is mounted
            setTimeout(() => {
                const element = document.getElementById(navItem.id);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth' });
                }
            }, 200);
        }
    }
});

onMounted(async () => {
    try {
        const { data } = await axios.get('/documentation/data');
        dynamic.value = data ?? {};
    } catch (e: any) {
        error.value = e?.message || 'Failed to load documentation data';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Head title="EMOH Property Management - Documentation" />

    <div class="min-h-screen bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50 dark:from-slate-900 dark:via-orange-900/20 dark:to-amber-900/20">
        <!-- Header -->
        <header class="sticky top-0 z-50 w-full border-b border-orange-200/50 bg-gradient-to-r from-white/95 via-orange-50/95 to-amber-50/95 backdrop-blur-sm dark:from-slate-900/95 dark:via-orange-900/20 dark:to-amber-900/20">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Button variant="ghost" size="sm" @click="toggleSidebar" class="lg:hidden hover:bg-orange-100 dark:hover:bg-orange-900/30">
                            <Icon name="menu" class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                        </Button>
                        <Link href="/" class="flex items-center gap-2 group">
                            <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-orange-500 via-amber-500 to-yellow-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shadow-lg">
                                <Icon name="home" class="h-5 w-5 text-white" />
                            </div>
                            <span class="text-xl font-bold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">EMOH</span>
                        </Link>
                    </div>

                    <div class="flex items-center gap-4">
                        <Badge variant="outline" class="hidden sm:flex border-orange-200 text-orange-700 bg-orange-50 dark:border-orange-800 dark:text-orange-300 dark:bg-orange-900/30">
                            <Icon name="book" class="h-3 w-3 mr-1" />
                            Documentation
                        </Badge>
                        <Link href="/login" class="text-sm text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300 font-medium transition-colors">
                            Login to System
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <div v-if="error" class="mx-auto max-w-5xl mt-4 px-4">
            <div class="rounded-lg border border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 px-4 py-3 text-sm">
                <strong class="font-semibold">Note:</strong> Showing static documentation. Live data failed to load ({{ error }}).
            </div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex gap-8">
                <!-- Sidebar -->
                <aside class="hidden lg:block w-64 flex-shrink-0">
                    <div class="sticky top-24 space-y-2">
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-orange-700 dark:text-orange-300 mb-3 px-3">Documentation</h3>
                            <nav class="space-y-1">
                                <Link
                                    v-for="item in navigationItems"
                                    :key="item.id"
                                    :href="`/documentation/${item.slug}`"
                                    :class="[
                                        'group w-full flex items-center gap-3 px-3 py-3 text-sm rounded-xl transition-all duration-200 relative overflow-hidden',
                                        activeSection === item.id
                                            ? 'bg-gradient-to-r from-orange-100 to-amber-100 text-orange-700 dark:from-orange-900/50 dark:to-amber-900/50 dark:text-orange-300 shadow-md border border-orange-200 dark:border-orange-800'
                                            : 'text-slate-600 hover:text-orange-700 hover:bg-gradient-to-r hover:from-orange-50 hover:to-amber-50 dark:text-slate-400 dark:hover:text-orange-300 dark:hover:from-orange-900/20 dark:hover:to-amber-900/20'
                                    ]"
                                >
                                    <div :class="[
                                        'p-1.5 rounded-lg transition-all duration-200',
                                        activeSection === item.id
                                            ? 'bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-lg'
                                            : 'bg-slate-100 group-hover:bg-gradient-to-br group-hover:from-orange-400 group-hover:to-amber-400 group-hover:text-white dark:bg-slate-800'
                                    ]">
                                        <Icon :name="item.icon" class="h-4 w-4" />
                                    </div>
                                    <span class="font-medium">{{ item.title }}</span>
                                    <div v-if="activeSection === item.id" class="absolute right-2 w-2 h-2 bg-orange-500 rounded-full animate-pulse"></div>
                                </Link>
                            </nav>
                        </div>

                        <!-- Quick Stats -->
                        <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl p-4 border border-orange-200 dark:border-orange-800">
                            <h4 class="text-xs font-semibold text-orange-700 dark:text-orange-300 mb-3">Quick Stats</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-600 dark:text-slate-400">Sections</span>
                                    <span class="font-medium text-orange-600 dark:text-orange-400">8</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-600 dark:text-slate-400">Features</span>
                                    <span class="font-medium text-orange-600 dark:text-orange-400">{{ counts.features }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-600 dark:text-slate-400">API Endpoints</span>
                                    <span class="font-medium text-orange-600 dark:text-orange-400">{{ counts.endpoints }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Mobile Sidebar -->
                <div v-if="sidebarOpen" class="fixed inset-0 z-50 lg:hidden">
                    <div class="fixed inset-0 bg-black/50" @click="toggleSidebar"></div>
                    <div class="fixed left-0 top-0 h-full w-64 bg-gradient-to-b from-white to-orange-50 dark:from-slate-900 dark:to-orange-900/20 border-r border-orange-200 dark:border-orange-800">
                        <div class="flex items-center justify-between p-4 border-b border-orange-200 dark:border-orange-800">
                            <h2 class="text-lg font-semibold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">Navigation</h2>
                            <Button variant="ghost" size="sm" @click="toggleSidebar" class="hover:bg-orange-100 dark:hover:bg-orange-900/30">
                                <Icon name="x" class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                            </Button>
                        </div>
                        <nav class="p-4 space-y-1">
                            <Link
                                v-for="item in navigationItems"
                                :key="item.id"
                                :href="`/documentation/${item.slug}`"
                                @click="toggleSidebar()"
                                :class="[
                                    'group w-full flex items-center gap-3 px-3 py-3 text-sm rounded-xl transition-all duration-200',
                                    activeSection === item.id
                                        ? 'bg-gradient-to-r from-orange-100 to-amber-100 text-orange-700 dark:from-orange-900/50 dark:to-amber-900/50 dark:text-orange-300 shadow-md border border-orange-200 dark:border-orange-800'
                                        : 'text-slate-600 hover:text-orange-700 hover:bg-gradient-to-r hover:from-orange-50 hover:to-amber-50 dark:text-slate-400 dark:hover:text-orange-300 dark:hover:from-orange-900/20 dark:hover:to-amber-900/20'
                                ]"
                            >
                                <div :class="[
                                    'p-1.5 rounded-lg transition-all duration-200',
                                    activeSection === item.id
                                        ? 'bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-lg'
                                        : 'bg-slate-100 group-hover:bg-gradient-to-br group-hover:from-orange-400 group-hover:to-amber-400 group-hover:text-white dark:bg-slate-800'
                                ]">
                                    <Icon :name="item.icon" class="h-4 w-4" />
                                </div>
                                <span class="font-medium">{{ item.title }}</span>
                            </Link>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <main class="flex-1 min-w-0">
                    <!-- Hero Section -->
                    <section id="overview" class="mb-20">
                        <div class="text-center mb-16">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 border border-orange-200 dark:border-orange-800 mb-6">
                                <Icon name="sparkles" class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                                <span class="text-sm font-medium text-orange-700 dark:text-orange-300">Comprehensive Documentation</span>
                            </div>

                            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold tracking-tight mb-6">
                                <span class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent">
                                    EMOH Property Management
                                </span>
                                <span class="block text-3xl sm:text-4xl lg:text-5xl text-slate-700 dark:text-slate-300 mt-2">
                                    Documentation
                                </span>
                            </h1>

                            <p class="text-xl lg:text-2xl text-slate-600 dark:text-slate-400 max-w-4xl mx-auto mb-10 leading-relaxed">
                                A comprehensive property management system built with modern technologies.
                                Manage properties, rentals, clients, and analytics with powerful features and intuitive design.
                            </p>

                            <div class="flex flex-wrap justify-center gap-3 mb-12">
                                <Badge variant="secondary" class="text-sm px-4 py-2 bg-gradient-to-r from-orange-100 to-amber-100 text-orange-700 border border-orange-200 dark:from-orange-900/30 dark:to-amber-900/30 dark:text-orange-300 dark:border-orange-800">
                                    <Icon name="server" class="h-4 w-4 mr-2" />
                                    Laravel 12
                                </Badge>
                                <Badge variant="secondary" class="text-sm px-4 py-2 bg-gradient-to-r from-orange-100 to-amber-100 text-orange-700 border border-orange-200 dark:from-orange-900/30 dark:to-amber-900/30 dark:text-orange-300 dark:border-orange-800">
                                    <Icon name="component" class="h-4 w-4 mr-2" />
                                    Vue 3
                                </Badge>
                                <Badge variant="secondary" class="text-sm px-4 py-2 bg-gradient-to-r from-orange-100 to-amber-100 text-orange-700 border border-orange-200 dark:from-orange-900/30 dark:to-amber-900/30 dark:text-orange-300 dark:border-orange-800">
                                    <Icon name="link" class="h-4 w-4 mr-2" />
                                    Inertia.js
                                </Badge>
                                <Badge variant="secondary" class="text-sm px-4 py-2 bg-gradient-to-r from-orange-100 to-amber-100 text-orange-700 border border-orange-200 dark:from-orange-900/30 dark:to-amber-900/30 dark:text-orange-300 dark:border-orange-800">
                                    <Icon name="palette" class="h-4 w-4 mr-2" />
                                    Tailwind CSS
                                </Badge>
                            </div>
                        </div>

                        <!-- Enhanced Quick Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
                            <Card class="group hover:shadow-xl transition-all duration-300 border-0 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20">
                                <CardContent class="pt-6 text-center">
                                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                        <Icon name="star" class="h-8 w-8 text-white" />
                                    </div>
                                    <div class="text-3xl font-bold text-orange-600 dark:text-orange-400 mb-2">{{ counts.features }}</div>
                                    <div class="text-sm font-medium text-slate-600 dark:text-slate-400">Core Features</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-500 mt-1">Comprehensive functionality</div>
                                </CardContent>
                            </Card>

                            <Card class="group hover:shadow-xl transition-all duration-300 border-0 bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20">
                                <CardContent class="pt-6 text-center">
                                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-amber-500 to-yellow-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                        <Icon name="database" class="h-8 w-8 text-white" />
                                    </div>
                                    <div class="text-3xl font-bold text-amber-600 dark:text-amber-400 mb-2">{{ counts.models }}</div>
                                    <div class="text-sm font-medium text-slate-600 dark:text-slate-400">Data Models</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-500 mt-1">Well-structured entities</div>
                                </CardContent>
                            </Card>

                            <Card class="group hover:shadow-xl transition-all duration-300 border-0 bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20">
                                <CardContent class="pt-6 text-center">
                                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                        <Icon name="code" class="h-8 w-8 text-white" />
                                    </div>
                                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mb-2">{{ counts.endpoints }}</div>
                                    <div class="text-sm font-medium text-slate-600 dark:text-slate-400">API Endpoints</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-500 mt-1">RESTful services</div>
                                </CardContent>
                            </Card>

                            <Card class="group hover:shadow-xl transition-all duration-300 border-0 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20">
                                <CardContent class="pt-6 text-center">
                                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                        <Icon name="component" class="h-8 w-8 text-white" />
                                    </div>
                                    <div class="text-3xl font-bold text-red-600 dark:text-red-400 mb-2">{{ counts.components }}</div>
                                    <div class="text-sm font-medium text-slate-600 dark:text-slate-400">Components</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-500 mt-1">Reusable UI elements</div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- System Overview -->
                        <div class="bg-gradient-to-r from-orange-50 via-amber-50 to-yellow-50 dark:from-orange-900/20 dark:via-amber-900/20 dark:to-yellow-900/20 rounded-2xl p-8 border border-orange-200 dark:border-orange-800">
                            <div class="text-center mb-8">
                                <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-4">System Overview</h2>
                                <p class="text-lg text-slate-600 dark:text-slate-400 max-w-3xl mx-auto">
                                    EMOH Property Management is a modern, full-stack application designed to streamline property rental operations.
                                    Built with cutting-edge technologies and best practices.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="text-center">
                                    <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center">
                                        <Icon name="shield" class="h-6 w-6 text-white" />
                                    </div>
                                    <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-2">Secure & Reliable</h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Built with security best practices and robust error handling</p>
                                </div>

                                <div class="text-center">
                                    <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-gradient-to-br from-amber-500 to-yellow-500 flex items-center justify-center">
                                        <Icon name="smartphone" class="h-6 w-6 text-white" />
                                    </div>
                                    <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-2">Mobile First</h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Fully responsive design that works on all devices</p>
                                </div>

                                <div class="text-center">
                                    <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
                                        <Icon name="zap" class="h-6 w-6 text-white" />
                                    </div>
                                    <h3 class="font-semibold text-slate-800 dark:text-slate-200 mb-2">High Performance</h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Optimized for speed and efficiency</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Features Section -->
                    <section id="features" class="mb-20">
                        <div class="text-center mb-16">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 border border-orange-200 dark:border-orange-800 mb-6">
                                <Icon name="star" class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                                <span class="text-sm font-medium text-orange-700 dark:text-orange-300">Core Features</span>
                            </div>

                            <h2 class="text-4xl lg:text-5xl font-bold tracking-tight mb-6">
                                <span class="bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">
                                    Powerful Features
                                </span>
                            </h2>
                            <p class="text-xl text-slate-600 dark:text-slate-400 max-w-3xl mx-auto leading-relaxed">
                                Comprehensive property management capabilities designed for modern real estate operations.
                                Built with scalability, security, and user experience in mind.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <Card v-for="(feature, index) in features" :key="feature.title"
                                  class="group hover:shadow-2xl transition-all duration-500 border-0 bg-gradient-to-br from-white to-orange-50/50 dark:from-slate-800 dark:to-orange-900/10 hover:scale-105">
                                <CardHeader class="pb-4">
                                    <div class="flex items-center gap-4 mb-4">
                                        <div class="relative">
                                            <div :class="[
                                                'w-16 h-16 rounded-2xl flex items-center justify-center text-white group-hover:scale-110 transition-all duration-300 shadow-lg',
                                                feature.color
                                            ]">
                                                <Icon :name="feature.icon" class="h-8 w-8" />
                                            </div>
                                            <div class="absolute -top-2 -right-2 w-6 h-6 bg-gradient-to-br from-orange-500 to-amber-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                                {{ index + 1 }}
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <CardTitle class="text-xl font-bold text-slate-800 dark:text-slate-200 group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">
                                                {{ feature.title }}
                                            </CardTitle>
                                            <div class="w-12 h-1 bg-gradient-to-r from-orange-500 to-amber-500 rounded-full mt-2"></div>
                                        </div>
                                    </div>
                                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">{{ feature.description }}</p>
                                </CardHeader>
                                <CardContent class="pt-0">
                                    <div class="space-y-3">
                                        <h4 class="font-semibold text-slate-700 dark:text-slate-300 text-sm mb-3">Key Capabilities:</h4>
                                        <ul class="space-y-3">
                                            <li v-for="detail in feature.details" :key="detail" class="flex items-start gap-3 text-sm">
                                                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                    <Icon name="check" class="h-3 w-3 text-white" />
                                                </div>
                                                <span class="text-slate-600 dark:text-slate-400">{{ detail }}</span>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Feature Benefits -->
                                    <div class="mt-6 pt-4 border-t border-orange-200 dark:border-orange-800">
                                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-500">
                                            <span>Complexity</span>
                                            <div class="flex gap-1">
                                                <div v-for="i in 5" :key="i"
                                                     :class="[
                                                         'w-2 h-2 rounded-full',
                                                         i <= 3 ? 'bg-gradient-to-r from-orange-500 to-amber-500' : 'bg-slate-200 dark:bg-slate-700'
                                                     ]"></div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Feature Highlights -->
                        <div class="mt-16 bg-gradient-to-r from-orange-50 via-amber-50 to-yellow-50 dark:from-orange-900/20 dark:via-amber-900/20 dark:to-yellow-900/20 rounded-2xl p-8 border border-orange-200 dark:border-orange-800">
                            <div class="text-center mb-8">
                                <h3 class="text-2xl font-bold text-slate-800 dark:text-slate-200 mb-4">Why Choose EMOH?</h3>
                                <p class="text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                                    Our property management system combines powerful features with intuitive design to deliver exceptional results.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="text-center group">
                                    <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                        <Icon name="trending-up" class="h-6 w-6 text-white" />
                                    </div>
                                    <h4 class="font-semibold text-slate-800 dark:text-slate-200 mb-2">Scalable</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Grows with your business needs</p>
                                </div>

                                <div class="text-center group">
                                    <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-gradient-to-br from-amber-500 to-yellow-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                        <Icon name="shield-check" class="h-6 w-6 text-white" />
                                    </div>
                                    <h4 class="font-semibold text-slate-800 dark:text-slate-200 mb-2">Secure</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Enterprise-grade security</p>
                                </div>

                                <div class="text-center group">
                                    <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                        <Icon name="users" class="h-6 w-6 text-white" />
                                    </div>
                                    <h4 class="font-semibold text-slate-800 dark:text-slate-200 mb-2">User-Friendly</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Intuitive interface design</p>
                                </div>

                                <div class="text-center group">
                                    <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                        <Icon name="zap" class="h-6 w-6 text-white" />
                                    </div>
                                    <h4 class="font-semibold text-slate-800 dark:text-slate-200 mb-2">Fast</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Optimized performance</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Architecture Section -->
                    <section id="architecture" class="mb-16">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl font-bold tracking-tight mb-4">System Architecture</h2>
                            <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
                                Modern full-stack architecture with Laravel backend and Vue.js frontend
                            </p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Technology Stack -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Icon name="layers" class="h-5 w-5" />
                                        Technology Stack
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div v-for="tech in techStackData" :key="tech.name" class="text-center p-4 rounded-lg border hover:bg-muted/50 transition-colors">
                                            <Icon :name="tech.icon" class="h-8 w-8 mx-auto mb-2 text-blue-600" />
                                            <div class="font-medium text-sm">{{ tech.name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ tech.description }}</div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- System Flow -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Icon name="workflow" class="h-5 w-5" />
                                        System Flow
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div class="flex items-center gap-3 p-3 rounded-lg border">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                                                <Icon name="user" class="h-4 w-4 text-blue-600" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-sm">User Authentication</div>
                                                <div class="text-xs text-muted-foreground">Laravel Fortify + Sanctum</div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 p-3 rounded-lg border">
                                            <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                                                <Icon name="server" class="h-4 w-4 text-green-600" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-sm">API Layer</div>
                                                <div class="text-xs text-muted-foreground">Laravel Controllers + Services</div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 p-3 rounded-lg border">
                                            <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                                                <Icon name="database" class="h-4 w-4 text-purple-600" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-sm">Data Layer</div>
                                                <div class="text-xs text-muted-foreground">Eloquent ORM + MySQL/SQLite</div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3 p-3 rounded-lg border">
                                            <div class="w-8 h-8 rounded-full bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center">
                                                <Icon name="component" class="h-4 w-4 text-orange-600" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-sm">Frontend</div>
                                                <div class="text-xs text-muted-foreground">Vue 3 + Inertia.js</div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <!-- Data Models Section -->
                    <section id="data-models" class="mb-16">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl font-bold tracking-tight mb-4">Data Models</h2>
                            <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
                                Well-structured data models with proper relationships and business logic
                            </p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <Card v-for="model in dataModelsData" :key="model.name" class="hover:shadow-lg transition-shadow">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Icon name="database" class="h-5 w-5 text-blue-600" />
                                        {{ model.name }}
                                    </CardTitle>
                                    <p class="text-sm text-muted-foreground">{{ model.description }}</p>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div>
                                            <h4 class="font-medium text-sm mb-2">Fields</h4>
                                            <div class="text-xs text-muted-foreground font-mono bg-muted/50 p-2 rounded">
                                                {{ Array.isArray(model.fields) ? model.fields.join(', ') : model.fields }}
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-sm mb-2">Relationships</h4>
                                            <div class="flex flex-wrap gap-1">
                                                <Badge v-for="rel in model.relationships" :key="rel" variant="outline" class="text-xs">
                                                    {{ rel }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <!-- API Endpoints Section -->
                    <section id="api-endpoints" class="mb-16">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl font-bold tracking-tight mb-4">API Endpoints</h2>
                            <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
                                RESTful API endpoints for all system functionality
                            </p>
                        </div>

                        <div class="space-y-8">
                            <div v-for="category in apiEndpointsData" :key="category.category">
                                <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                                    <Icon name="code" class="h-5 w-5" />
                                    {{ category.category }}
                                </h3>
                                <div class="space-y-2">
                                    <div v-for="endpoint in category.endpoints" :key="endpoint.path"
                                         class="flex items-center gap-4 p-4 rounded-lg border hover:bg-muted/50 transition-colors">
                                        <Badge :variant="endpoint.method === 'GET' ? 'default' :
                                                       endpoint.method === 'POST' ? 'secondary' :
                                                       endpoint.method === 'PUT' || endpoint.method === 'PATCH' ? 'outline' : 'destructive'"
                                               class="font-mono text-xs">
                                            {{ endpoint.method }}
                                        </Badge>
                                        <code class="flex-1 text-sm font-mono">{{ endpoint.path }}</code>
                                        <span class="text-sm text-muted-foreground">{{ endpoint.description }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Vue Components Section -->
                    <section id="vue-components" class="mb-16">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl font-bold tracking-tight mb-4">Vue Components</h2>
                            <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
                                Reusable Vue 3 components with TypeScript support
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <Card class="hover:shadow-lg transition-shadow">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Icon name="layout" class="h-5 w-5 text-blue-600" />
                                        Layout Components
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2 text-sm">
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            AppLayout
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            AppShell
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            AppSidebar
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            AppHeader
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>

                            <Card class="hover:shadow-lg transition-shadow">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Icon name="component" class="h-5 w-5 text-green-600" />
                                        UI Components
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2 text-sm">
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            Button, Card, Badge
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            Dialog, Sheet, Tooltip
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            Table, Form, Input
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            Navigation, Breadcrumbs
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>

                            <Card class="hover:shadow-lg transition-shadow">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Icon name="settings" class="h-5 w-5 text-purple-600" />
                                        Feature Components
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2 text-sm">
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            DataTable
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            SearchableSelect
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            AnalyticsModal
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <Icon name="check" class="h-4 w-4 text-green-500" />
                                            Icon System
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <!-- Deployment Section -->
                    <section id="deployment" class="mb-16">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl font-bold tracking-tight mb-4">Deployment</h2>
                            <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
                                Production-ready deployment with Docker and comprehensive guides
                            </p>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Icon name="docker" class="h-5 w-5" />
                                        Docker Deployment
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div class="p-4 rounded-lg bg-muted/50">
                                            <h4 class="font-medium mb-2">Quick Start</h4>
                                            <code class="text-sm block">docker-compose up -d</code>
                                        </div>
                                        <div class="p-4 rounded-lg bg-muted/50">
                                            <h4 class="font-medium mb-2">Production</h4>
                                            <code class="text-sm block">docker-compose -f docker-compose.prod.yml up -d</code>
                                        </div>
                                        <ul class="space-y-2 text-sm">
                                            <li class="flex items-center gap-2">
                                                <Icon name="check" class="h-4 w-4 text-green-500" />
                                                Nginx reverse proxy
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <Icon name="check" class="h-4 w-4 text-green-500" />
                                                PHP 8.3 with extensions
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <Icon name="check" class="h-4 w-4 text-green-500" />
                                                MySQL/SQLite database
                                            </li>
                                        </ul>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Icon name="server" class="h-5 w-5" />
                                        Server Requirements
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="text-center p-3 rounded-lg border">
                                                <div class="text-2xl font-bold text-blue-600">PHP 8.3+</div>
                                                <div class="text-xs text-muted-foreground">Backend</div>
                                            </div>
                                            <div class="text-center p-3 rounded-lg border">
                                                <div class="text-2xl font-bold text-green-600">Node.js 18+</div>
                                                <div class="text-xs text-muted-foreground">Frontend</div>
                                            </div>
                                        </div>
                                        <ul class="space-y-2 text-sm">
                                            <li class="flex items-center gap-2">
                                                <Icon name="check" class="h-4 w-4 text-green-500" />
                                                Composer for PHP dependencies
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <Icon name="check" class="h-4 w-4 text-green-500" />
                                                NPM/Yarn for frontend build
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <Icon name="check" class="h-4 w-4 text-green-500" />
                                                MySQL 8.0+ or SQLite 3
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <Icon name="check" class="h-4 w-4 text-green-500" />
                                                Redis (optional, for caching)
                                            </li>
                                        </ul>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <!-- Support Section -->
                    <section id="support" class="mb-16">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl font-bold tracking-tight mb-4">Support & Resources</h2>
                            <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
                                Get help and learn more about the system
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <Card class="text-center hover:shadow-lg transition-shadow">
                                <CardContent class="pt-6">
                                    <Icon name="book" class="h-12 w-12 mx-auto text-blue-600 mb-4" />
                                    <h3 class="text-lg font-semibold mb-2">Documentation</h3>
                                    <p class="text-sm text-muted-foreground mb-4">
                                        Comprehensive guides and API references
                                    </p>
                                    <Button variant="outline" size="sm">
                                        <Icon name="external-link" class="h-4 w-4 mr-2" />
                                        View Docs
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card class="text-center hover:shadow-lg transition-shadow">
                                <CardContent class="pt-6">
                                    <Icon name="github" class="h-12 w-12 mx-auto text-gray-600 mb-4" />
                                    <h3 class="text-lg font-semibold mb-2">Source Code</h3>
                                    <p class="text-sm text-muted-foreground mb-4">
                                        Open source codebase on GitHub
                                    </p>
                                    <Button variant="outline" size="sm">
                                        <Icon name="github" class="h-4 w-4 mr-2" />
                                        View Source
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card class="text-center hover:shadow-lg transition-shadow">
                                <CardContent class="pt-6">
                                    <Icon name="help-circle" class="h-12 w-12 mx-auto text-green-600 mb-4" />
                                    <h3 class="text-lg font-semibold mb-2">Support</h3>
                                    <p class="text-sm text-muted-foreground mb-4">
                                        Get help from the community
                                    </p>
                                    <Button variant="outline" size="sm">
                                        <Icon name="message-circle" class="h-4 w-4 mr-2" />
                                        Contact Support
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                    </section>
                </main>
            </div>
        </div>

        <!-- Footer -->
        <footer class="border-t border-orange-200 dark:border-orange-800 bg-gradient-to-r from-white via-orange-50 to-amber-50 dark:from-slate-900 dark:via-orange-900/20 dark:to-amber-900/20">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                    <!-- Brand -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-orange-500 via-amber-500 to-yellow-500 flex items-center justify-center shadow-lg">
                                <Icon name="home" class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold bg-gradient-to-r from-orange-600 to-amber-600 bg-clip-text text-transparent">EMOH</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Property Management</p>
                            </div>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            A comprehensive property management system built with modern technologies and best practices.
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-slate-800 dark:text-slate-200">Quick Links</h4>
                        <div class="space-y-2">
                            <Link href="/documentation/overview" class="block text-sm text-slate-600 hover:text-orange-600 dark:text-slate-400 dark:hover:text-orange-400 transition-colors">
                                Overview
                            </Link>
                            <Link href="/documentation/features" class="block text-sm text-slate-600 hover:text-orange-600 dark:text-slate-400 dark:hover:text-orange-400 transition-colors">
                                Features
                            </Link>
                            <Link href="/documentation/api-endpoints" class="block text-sm text-slate-600 hover:text-orange-600 dark:text-slate-400 dark:hover:text-orange-400 transition-colors">
                                API Documentation
                            </Link>
                            <Link href="/documentation/deployment" class="block text-sm text-slate-600 hover:text-orange-600 dark:text-slate-400 dark:hover:text-orange-400 transition-colors">
                                Deployment Guide
                            </Link>
                        </div>
                    </div>

                    <!-- Technology Stack -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-slate-800 dark:text-slate-200">Built With</h4>
                        <div class="flex flex-wrap gap-2">
                            <Badge variant="outline" class="text-xs border-orange-200 text-orange-700 dark:border-orange-800 dark:text-orange-300">
                                Laravel 12
                            </Badge>
                            <Badge variant="outline" class="text-xs border-orange-200 text-orange-700 dark:border-orange-800 dark:text-orange-300">
                                Vue 3
                            </Badge>
                            <Badge variant="outline" class="text-xs border-orange-200 text-orange-700 dark:border-orange-800 dark:text-orange-300">
                                Inertia.js
                            </Badge>
                            <Badge variant="outline" class="text-xs border-orange-200 text-orange-700 dark:border-orange-800 dark:text-orange-300">
                                Tailwind CSS
                            </Badge>
                        </div>
                    </div>
                </div>

                <div class="border-t border-orange-200 dark:border-orange-800 pt-8">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            © 2024 EMOH Property Management. All rights reserved.
                        </div>
                        <div class="flex items-center gap-4 text-sm text-slate-600 dark:text-slate-400">
                            <span>Built with ❤️ using modern web technologies</span>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Custom scrollbar for better UX */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #f97316, #f59e0b);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ea580c, #d97706);
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Custom animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.8s ease-out;
}

.animate-slide-in-left {
    animation: slideInLeft 0.8s ease-out;
}

.animate-slide-in-right {
    animation: slideInRight 0.8s ease-out;
}

.animate-scale-in {
    animation: scaleIn 0.6s ease-out;
}

.animate-pulse-slow {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Gradient text animation */
@keyframes gradient-shift {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

.gradient-text-animated {
    background: linear-gradient(-45deg, #f97316, #f59e0b, #eab308, #f97316);
    background-size: 400% 400%;
    animation: gradient-shift 3s ease infinite;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Hover effects */
.hover-lift {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.hover-lift:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Responsive design enhancements */
@media (max-width: 640px) {
    .text-5xl {
        font-size: 2.5rem;
    }

    .text-6xl {
        font-size: 3rem;
    }

    .text-7xl {
        font-size: 3.5rem;
    }
}

@media (max-width: 768px) {
    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}

/* Dark mode enhancements */
@media (prefers-color-scheme: dark) {
    .gradient-text-animated {
        background: linear-gradient(-45deg, #fb923c, #fbbf24, #fde047, #fb923c);
        background-size: 400% 400%;
        animation: gradient-shift 3s ease infinite;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
}

/* Focus states for accessibility */
.focus-visible:focus {
    outline: 2px solid #f97316;
    outline-offset: 2px;
}

/* Loading states */
.loading-shimmer {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }

    body {
        background: white !important;
        color: black !important;
    }
}
</style>
