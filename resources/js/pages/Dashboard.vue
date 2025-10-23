<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import Icon from '@/components/Icon.vue';
import AnalyticsModal from '@/components/AnalyticsModal.vue';
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { formatCurrency, formatNumber, formatRelativeTime } from '@/utils/formatters';

// Props from the controller
interface Props {
    user: any;
    stats: any;
    recentActivities: any[];
    quickActions: any[];
    adminStats?: any;
    propertyPerformance?: any[];
    locationStats?: any[];
    categoryStats?: any[];
    systemStats?: any;
    systemLogs?: any[];
    backupStatus?: any;
    siteSettingsOverview?: any;
    rentedStats?: any;
    recentRentals?: any[];
    clientStats?: any;
    recentClients?: any[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// Computed properties for role-based access
const isSystemAdmin = computed(() =>
    props.user?.roles?.some((role: any) => role.name === 'System Admin')
);

const isAdmin = computed(() =>
    props.user?.roles?.some((role: any) => ['System Admin', 'Admin'].includes(role.name))
);

// Loading states
const loading = ref(false);
const error = ref<string | null>(null);
const showAnalyticsModal = ref(false);
const autoRefresh = ref(true);
let refreshInterval: NodeJS.Timeout | null = null;

// Chart data processing
const chartData = computed(() => {
    if (!props.propertyPerformance) return [];
    return props.propertyPerformance.map(item => ({
        ...item,
        revenue: Number(item.revenue) || 0,
        properties: Number(item.properties) || 0
    }));
});

// Enhanced stats processing with better categorization
const propertyStats = computed(() => {
    const propertyGrowth = props.stats?.property_growth || 0;
    const featuredGrowth = props.stats?.featured_growth || 0;
    const availableCount = (props.stats?.total_properties || 0) - (props.rentedStats?.active_rentals || 0);
    
    return [
        {
            title: 'Total Properties',
            value: formatNumber(props.stats?.total_properties || 0),
            icon: 'home',
            description: 'Active listings',
            color: 'bg-blue-500',
            trend: `${propertyGrowth > 0 ? '+' : ''}${propertyGrowth}%`,
            trendUp: propertyGrowth > 0 ? true : propertyGrowth < 0 ? false : null
        },
        {
            title: 'Featured Properties',
            value: formatNumber(props.stats?.featured_properties || 0),
            icon: 'star',
            description: 'Premium listings',
            color: 'bg-yellow-500',
            trend: `${featuredGrowth > 0 ? '+' : ''}${featuredGrowth}%`,
            trendUp: featuredGrowth > 0 ? true : featuredGrowth < 0 ? false : null
        },
        {
            title: 'Available Properties',
            value: formatNumber(availableCount),
            icon: 'home',
            description: 'Ready to rent',
            color: 'bg-green-500',
            trend: `${availableCount} available`,
            trendUp: availableCount > 0
        },
        {
            title: 'Categories',
            value: formatNumber(props.stats?.total_categories || 0),
            icon: 'tag',
            description: 'Property types',
            color: 'bg-purple-500',
            trend: `${props.stats?.total_locations || 0} locations`,
            trendUp: null
        }
    ];
});

const locationStats = computed(() => props.locationStats || []);

const adminStats = computed(() => {
    if (!isAdmin.value || !props.adminStats) return [];

    const revenueGrowth = props.adminStats.revenue_growth || 0;
    const monthlyGrowth = props.adminStats.monthly_growth || 0;
    const occupancyRate = props.adminStats.occupancy_rate || 0;

    return [
        {
            title: 'Total Revenue',
            value: formatCurrency(props.adminStats.total_revenue || 0),
            icon: 'dollarSign',
            description: 'Monthly potential',
            color: 'bg-emerald-500',
            trend: `${revenueGrowth > 0 ? '+' : ''}${revenueGrowth}%`,
            trendUp: revenueGrowth > 0 ? true : revenueGrowth < 0 ? false : null
        },
        {
            title: 'Average Price',
            value: formatCurrency(props.adminStats.average_price || 0),
            icon: 'trendingUp',
            description: 'Per property',
            color: 'bg-orange-500',
            trend: `${props.adminStats.properties_this_month || 0} this month`,
            trendUp: (props.adminStats.properties_this_month || 0) > 0
        },
        {
            title: 'Monthly Growth',
            value: `${monthlyGrowth > 0 ? '+' : ''}${monthlyGrowth}%`,
            icon: 'barChart3',
            description: 'This month',
            color: monthlyGrowth >= 0 ? 'bg-green-500' : 'bg-red-500',
            trend: monthlyGrowth >= 0 ? 'Growing' : 'Declining',
            trendUp: monthlyGrowth >= 0
        },
        {
            title: 'Occupancy Rate',
            value: `${occupancyRate}%`,
            icon: 'users',
            description: 'Current rate',
            color: 'bg-indigo-500',
            trend: occupancyRate > 80 ? 'Excellent' : occupancyRate > 60 ? 'Good' : 'Needs attention',
            trendUp: occupancyRate > 60
        }
    ];
});

const systemStats = computed(() => {
    if (!isSystemAdmin.value || !props.systemStats) return [];

    const usersThisMonth = props.systemStats.users_this_month || 0;
    const clientsThisMonth = props.systemStats.clients_this_month || 0;

    return [
        {
            title: 'Total Users',
            value: formatNumber(props.systemStats.total_users || 0),
            icon: 'users',
            description: 'Registered users',
            color: 'bg-cyan-500',
            trend: `${usersThisMonth} this month`,
            trendUp: usersThisMonth > 0
        },
        {
            title: 'Active Users',
            value: formatNumber(props.systemStats.active_users || 0),
            icon: 'userCheck',
            description: 'Verified accounts',
            color: 'bg-teal-500',
            trend: `${props.systemStats.admins || 0} admins`,
            trendUp: true
        },
        {
            title: 'Total Clients',
            value: formatNumber(props.systemStats.total_clients || 0),
            icon: 'users',
            description: 'Client accounts',
            color: 'bg-blue-500',
            trend: `${clientsThisMonth} this month`,
            trendUp: clientsThisMonth > 0
        },
        {
            title: 'Database Size',
            value: props.systemStats.database_size || 'N/A',
            icon: 'database',
            description: 'Storage used',
            color: 'bg-gray-500',
            trend: props.systemStats.storage_usage || 'N/A',
            trendUp: null
        }
    ];
});

// Rental management stats
const rentalStats = computed(() => {
    if (!props.rentedStats) return [];

    const activeRentals = props.rentedStats.active_rentals || 0;
    const totalRentals = props.rentedStats.total_rentals || 0;
    const expiringSoon = props.rentedStats.expiring_soon || 0;
    const occupancyRate = props.rentedStats.occupancy_rate || 0;

    return [
        {
            title: 'Active Rentals',
            value: formatNumber(activeRentals),
            icon: 'home',
            description: 'Currently occupied',
            color: 'bg-green-500',
            trend: `${totalRentals} total`,
            trendUp: activeRentals > 0
        },
        {
            title: 'Monthly Revenue',
            value: formatCurrency(props.rentedStats.monthly_revenue || 0),
            icon: 'dollarSign',
            description: 'From active rentals',
            color: 'bg-emerald-500',
            trend: `${activeRentals} active`,
            trendUp: true
        },
        {
            title: 'Expiring Soon',
            value: formatNumber(expiringSoon),
            icon: 'clock',
            description: 'Next 30 days',
            color: expiringSoon > 0 ? 'bg-orange-500' : 'bg-green-500',
            trend: expiringSoon > 0 ? 'Action needed' : 'All good',
            trendUp: expiringSoon === 0
        },
        {
            title: 'Occupancy Rate',
            value: `${occupancyRate}%`,
            icon: 'percent',
            description: 'Properties occupied',
            color: 'bg-blue-500',
            trend: occupancyRate > 80 ? 'Excellent' : occupancyRate > 60 ? 'Good' : 'Needs attention',
            trendUp: occupancyRate > 60
        }
    ];
});

// Client management stats
const clientStats = computed(() => {
    if (!props.clientStats) return [];

    const totalClients = props.clientStats.total_clients || 0;
    const verifiedClients = props.clientStats.verified_clients || 0;
    const activeRenters = props.clientStats.active_renters || 0;
    const newThisMonth = props.clientStats.new_this_month || 0;

    return [
        {
            title: 'Total Clients',
            value: formatNumber(totalClients),
            icon: 'users',
            description: 'Registered clients',
            color: 'bg-blue-500',
            trend: `${newThisMonth} this month`,
            trendUp: newThisMonth > 0
        },
        {
            title: 'Verified Clients',
            value: formatNumber(verifiedClients),
            icon: 'userCheck',
            description: 'Email verified',
            color: 'bg-green-500',
            trend: `${totalClients > 0 ? Math.round((verifiedClients / totalClients) * 100) : 0}% verified`,
            trendUp: verifiedClients > 0
        },
        {
            title: 'Active Renters',
            value: formatNumber(activeRenters),
            icon: 'home',
            description: 'Currently renting',
            color: 'bg-purple-500',
            trend: `${totalClients > 0 ? Math.round((activeRenters / totalClients) * 100) : 0}% renting`,
            trendUp: activeRenters > 0
        },
        {
            title: 'New This Month',
            value: formatNumber(newThisMonth),
            icon: 'userPlus',
            description: 'Recent signups',
            color: 'bg-orange-500',
            trend: newThisMonth > 1 ? 'Growing' : newThisMonth === 1 ? 'Steady' : 'None',
            trendUp: newThisMonth > 0
        }
    ];
});

// Status color helpers
const getStatusColor = (status: string) => {
    switch (status) {
        case 'success': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'warning': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'info': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'error': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

const getLogLevelColor = (level: string) => {
    switch (level) {
        case 'error': return 'text-red-600 bg-red-50 dark:bg-red-900/20';
        case 'warning': return 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20';
        case 'info': return 'text-blue-600 bg-blue-50 dark:bg-blue-900/20';
        default: return 'text-gray-600 bg-gray-50 dark:bg-gray-900/20';
    }
};

const getRentalStatusColor = (status: string) => {
    switch (status) {
        case 'active': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'expired': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        case 'terminated': return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

// Utility functions
const refreshData = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await fetch('/dashboard/refresh', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to refresh data');
        }

        const data = await response.json();

        // Update the props with fresh data
        Object.assign(props, data);

    } catch (err) {
        error.value = 'Failed to refresh data. Please try again.';
        console.error('Refresh error:', err);
    } finally {
        loading.value = false;
    }
};

// Auto-refresh functionality
const startAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
    
    // Refresh every 5 minutes (300000 ms)
    refreshInterval = setInterval(() => {
        if (autoRefresh.value && !loading.value) {
            refreshData();
        }
    }, 300000);
};

const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value;
    if (autoRefresh.value) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
};

const getVerificationProgress = () => {
    if (!props.clientStats) return 0;
    const total = props.clientStats.total_clients || 1;
    const verified = props.clientStats.verified_clients || 0;
    return Math.round((verified / total) * 100);
};

const getActiveClientProgress = () => {
    if (!props.clientStats) return 0;
    const total = props.clientStats.total_clients || 1;
    const active = props.clientStats.active_clients || 0;
    return Math.round((active / total) * 100);
};

onMounted(() => {
    // Start auto-refresh on mount
    if (autoRefresh.value) {
        startAutoRefresh();
    }
});

onBeforeUnmount(() => {
    stopAutoRefresh();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 sm:p-6">
            <!-- Welcome Section -->
            <div class="flex flex-col gap-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Welcome back, {{ user.name }}!</h1>
                        <p class="text-muted-foreground mt-1 text-sm sm:text-base">
                            Here's your comprehensive real estate management overview
                        </p>
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <Button variant="outline" size="sm" @click="toggleAutoRefresh" class="flex-1 sm:flex-none">
                            <Icon :name="autoRefresh ? 'pause' : 'play'" class="h-4 w-4 mr-2" />
                            <span class="hidden sm:inline">Auto-refresh {{ autoRefresh ? 'ON' : 'OFF' }}</span>
                            <span class="sm:hidden">Auto</span>
                        </Button>
                        <Button variant="outline" size="sm" @click="refreshData" :disabled="loading" class="flex-1 sm:flex-none">
                            <Icon name="refreshCw" :class="loading ? 'h-4 w-4 mr-2 animate-spin' : 'h-4 w-4 mr-2'" />
                            <span class="hidden sm:inline">Refresh</span>
                            <span class="sm:hidden">Sync</span>
                        </Button>
                    </div>
                </div>

                <!-- User Role Badge -->
                <div class="flex items-center gap-2">
                    <Badge variant="outline" class="text-xs">
                        <Icon name="user" class="h-3 w-3 mr-1" />
                        {{ user.roles?.[0]?.name || 'User' }}
                    </Badge>
                    <span class="text-xs text-muted-foreground">
                        Last login: {{ formatRelativeTime(user.updated_at) }}
                    </span>
                </div>
            </div>

            <!-- Status Indicators -->
            <div v-if="loading && !error" class="rounded-lg border border-blue-200 bg-blue-50 p-3 sm:p-4 dark:border-blue-800 dark:bg-blue-900/20">
                <div class="flex items-center">
                    <Icon name="refreshCw" class="h-5 w-5 text-blue-500 animate-spin" />
                    <div class="ml-3">
                        <p class="text-sm text-blue-800 dark:text-blue-200">Updating dashboard data...</p>
                    </div>
                </div>
            </div>

            <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-3 sm:p-4 dark:border-red-800 dark:bg-red-900/20">
                <div class="flex items-start">
                    <Icon name="alertCircle" class="h-5 w-5 text-red-400 flex-shrink-0" />
                    <div class="ml-3 flex-1">
                        <p class="text-sm text-red-800 dark:text-red-200">{{ error }}</p>
                        <Button variant="link" size="sm" @click="refreshData" class="mt-1 p-0 h-auto text-red-700">
                            Try again
                        </Button>
                    </div>
                </div>
            </div>

            <div v-if="autoRefresh" class="rounded-lg border border-green-200 bg-green-50 p-2 dark:border-green-800 dark:bg-green-900/20">
                <div class="flex items-center justify-center">
                    <Icon name="checkCircle" class="h-4 w-4 text-green-600 mr-2" />
                    <p class="text-xs text-green-800 dark:text-green-200">Auto-refresh enabled - Updates every 5 minutes</p>
                </div>
            </div>

            <!-- Property Management Overview -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                        <Icon name="home" class="h-5 w-5" />
                        Property Management
                    </h2>
                    <Link href="/properties" class="text-sm text-primary hover:underline">
                        View all properties →
                    </Link>
                </div>

                <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="stat in propertyStats" :key="stat.title" class="relative overflow-hidden hover:shadow-md transition-shadow cursor-pointer">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                            <div :class="[stat.color, 'rounded-full p-2 text-white']">
                                <Icon :name="stat.icon" class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stat.value }}</div>
                            <p class="text-xs text-muted-foreground">{{ stat.description }}</p>
                            <div class="flex items-center gap-1 mt-2">
                                <Icon
                                    :name="stat.trendUp === true ? 'trendingUp' : stat.trendUp === false ? 'trendingDown' : 'minus'"
                                    :class="`h-3 w-3 ${stat.trendUp === true ? 'text-green-500' : stat.trendUp === false ? 'text-red-500' : 'text-gray-500'}`"
                                />
                                <span                                 :class="`text-xs font-medium ${stat.trendUp === true ? 'text-green-600' : stat.trendUp === false ? 'text-red-600' : 'text-gray-600'}`">
                                    {{ stat.trend }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Location Overview -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold tracking-tight flex items-center gap-2">
                    <Icon name="mapPin" class="h-4 w-4" />
                    Coverage Areas
                </h2>

                <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="stat in locationStats" :key="stat.title" class="relative overflow-hidden hover:shadow-md transition-shadow cursor-pointer">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                            <div :class="[stat.color, 'rounded-full p-2 text-white']">
                                <Icon :name="stat.icon" class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stat.value }}</div>
                            <p class="text-xs text-muted-foreground">{{ stat.description }}</p>
                            <div class="flex items-center gap-1 mt-2">
                                <Icon name="trendingUp" class="h-3 w-3 text-green-500" />
                                <span class="text-xs font-medium text-green-600">{{ stat.trend }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Rental Management Section -->
            <div v-if="isAdmin && (rentedStats || clientStats)" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                        <Icon name="fileText" class="h-5 w-5" />
                        Rental Management
                    </h2>
                    <Link href="/admin/rented" class="text-sm text-primary hover:underline">
                        Manage rentals →
                    </Link>
                </div>

                <!-- Rental Statistics -->
                <div v-if="rentedStats" class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="stat in rentalStats" :key="stat.title" class="relative overflow-hidden hover:shadow-md transition-shadow">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                            <div :class="[stat.color, 'rounded-full p-2 text-white']">
                                <Icon :name="stat.icon" class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stat.value }}</div>
                            <p class="text-xs text-muted-foreground">{{ stat.description }}</p>
                            <div class="flex items-center gap-1 mt-2">
                                <Icon
                                    :name="stat.trendUp === true ? 'trendingUp' : stat.trendUp === false ? 'trendingDown' : 'minus'"
                                    :class="`h-3 w-3 ${stat.trendUp === true ? 'text-green-500' : stat.trendUp === false ? 'text-red-500' : 'text-gray-500'}`"
                                />
                                <span                                 :class="`text-xs font-medium ${stat.trendUp === true ? 'text-green-600' : stat.trendUp === false ? 'text-red-600' : 'text-gray-600'}`">
                                    {{ stat.trend }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Recent Rentals and Client Stats Grid -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Recent Rentals -->
                    <Card v-if="recentRentals" class="h-fit">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="fileText" class="h-5 w-5" />
                                Recent Rentals
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div v-for="rental in recentRentals.slice(0, 5)" :key="rental.id"
                                     class="flex items-start gap-3 p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                    <div class="rounded-full p-2 flex-shrink-0"
                                         :class="getRentalStatusColor(rental.status)">
                                        <Icon :name="rental.status === 'active' ? 'checkCircle' :
                                                   rental.status === 'pending' ? 'clock' :
                                                   rental.status === 'expired' ? 'xCircle' : 'home'"
                                              class="h-4 w-4" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <p class="text-sm font-medium truncate">{{ rental.property?.title || 'Property' }}</p>
                                            <Badge :variant="rental.status === 'active' ? 'default' :
                                                           rental.status === 'pending' ? 'secondary' :
                                                           rental.status === 'expired' ? 'destructive' : 'outline'"
                                                   class="text-xs">
                                                {{ rental.status }}
                                            </Badge>
                                        </div>
                                        <p class="text-sm text-muted-foreground truncate mb-2">{{ rental.client?.name || 'Client' }}</p>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-green-600">{{ formatCurrency(rental.monthly_rent) }}/mo</span>
                                            <span class="text-xs text-muted-foreground">
                                                {{ rental.remaining_days ? `${rental.remaining_days} days left` : formatRelativeTime(rental.start_date) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="recentRentals.length === 0" class="text-center py-8">
                                    <Icon name="home" class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                    <p class="text-muted-foreground">No recent rentals</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Client Statistics -->
                    <Card v-if="clientStats" class="h-fit">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="users" class="h-5 w-5" />
                                Client Overview
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- Client Stats Grid -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div v-for="stat in clientStats" :key="stat.title"
                                         class="text-center p-3 rounded-lg bg-muted/50 hover:bg-muted transition-colors">
                                        <div class="text-xl font-bold" :class="stat.color.replace('bg-', 'text-')">{{ stat.value }}</div>
                                        <p class="text-xs text-muted-foreground">{{ stat.description }}</p>
                                        <div class="flex items-center justify-center gap-1 mt-1">
                                            <Icon
                                                :name="stat.trendUp === true ? 'trendingUp' : stat.trendUp === false ? 'trendingDown' : 'minus'"
                                    :class="`h-3 w-3 ${stat.trendUp === true ? 'text-green-500' : stat.trendUp === false ? 'text-red-500' : 'text-gray-500'}`"
                                            />
                                            <span                                 :class="`text-xs font-medium ${stat.trendUp === true ? 'text-green-600' : stat.trendUp === false ? 'text-red-600' : 'text-gray-600'}`">
                                                {{ stat.trend }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Verification Progress -->
                                <div class="space-y-3">
                                    <h4 class="text-sm font-medium">Verification Progress</h4>
                                    <div class="space-y-3">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-sm text-muted-foreground">Email Verified</span>
                                                <span class="text-sm font-medium">{{ getVerificationProgress() }}%</span>
                                            </div>
                                            <Progress :value="getVerificationProgress()" class="h-2" />
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-sm text-muted-foreground">Active Clients</span>
                                                <span class="text-sm font-medium">{{ getActiveClientProgress() }}%</span>
                                            </div>
                                            <Progress :value="getActiveClientProgress()" class="h-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Recent Clients -->
                <Card v-if="recentClients">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="userPlus" class="h-5 w-5" />
                            Recent Clients
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="client in recentClients.slice(0, 6)" :key="client.id"
                                 class="flex items-center gap-3 p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                <div class="relative">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-medium">
                                        {{ client.name?.charAt(0)?.toUpperCase() || 'C' }}
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white flex items-center justify-center"
                                         :class="client.email_verified_at ? 'bg-green-500' :
                                                 client.is_active ? 'bg-yellow-500' : 'bg-gray-400'">
                                        <Icon :name="client.email_verified_at ? 'check' :
                                                   client.is_active ? 'clock' : 'x'"
                                              class="h-2 w-2 text-white" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ client.name }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ client.email }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <Badge :variant="client.email_verified_at ? 'default' : 'secondary'" class="text-xs">
                                            {{ client.email_verified_at ? 'Verified' : 'Pending' }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">
                                            {{ client.total_rentals || 0 }} rentals
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="recentClients.length === 0" class="col-span-full text-center py-8">
                                <Icon name="users" class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                <p class="text-muted-foreground">No recent clients</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Admin Analytics Section -->
            <div v-if="isAdmin" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                        <Icon name="barChart3" class="h-5 w-5" />
                        Analytics & Performance
                    </h2>
                    <Button variant="outline" size="sm" @click="showAnalyticsModal = true">
                        <Icon name="barChart3" class="h-4 w-4 mr-2" />
                        View Analytics
                    </Button>
                </div>

                <!-- Admin Stats Grid -->
                <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="stat in adminStats" :key="stat.title" class="relative overflow-hidden hover:shadow-md transition-shadow">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                            <div :class="[stat.color, 'rounded-full p-2 text-white']">
                                <Icon :name="stat.icon" class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stat.value }}</div>
                            <p class="text-xs text-muted-foreground">{{ stat.description }}</p>
                            <div class="flex items-center gap-1 mt-2">
                                <Icon
                                    :name="stat.trendUp === true ? 'trendingUp' : stat.trendUp === false ? 'trendingDown' : 'minus'"
                                    :class="`h-3 w-3 ${stat.trendUp === true ? 'text-green-500' : stat.trendUp === false ? 'text-red-500' : 'text-gray-500'}`"
                                />
                                <span                                 :class="`text-xs font-medium ${stat.trendUp === true ? 'text-green-600' : stat.trendUp === false ? 'text-red-600' : 'text-gray-600'}`">
                                    {{ stat.trend }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Analytics Charts Grid -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Property Performance Chart -->
                    <Card v-if="propertyPerformance">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="trendingUp" class="h-5 w-5" />
                                Property Performance (6 Months)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div v-for="item in chartData" :key="item.month"
                                     class="flex items-center justify-between p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                        <span class="text-sm font-medium">{{ item.month }} {{ item.year }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm">
                                        <span class="text-muted-foreground">{{ item.properties }} properties</span>
                                        <span class="font-medium text-green-600">{{ formatCurrency(item.revenue) }}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Location Stats -->
                    <Card v-if="locationStats">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="mapPin" class="h-5 w-5" />
                                Top Performing Locations
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div v-for="(location, index) in (locationStats || []).slice(0, 5)" :key="location.name"
                                     class="flex items-center justify-between p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold">
                                            {{ index + 1 }}
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ location.name }}</p>
                                            <p class="text-sm text-muted-foreground">{{ location.code }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium">{{ location.properties_count }} properties</p>
                                        <p class="text-sm text-muted-foreground">{{ formatCurrency(location.avg_price) }} avg</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Category Stats -->
                <Card v-if="categoryStats">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="tag" class="h-5 w-5" />
                            Property Categories Performance
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="category in categoryStats" :key="category.name"
                                 class="rounded-lg border p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                    <h4 class="font-medium">{{ category.name }}</h4>
                                </div>
                                <p class="text-sm text-muted-foreground mb-3">{{ category.description }}</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">Properties</span>
                                        <span class="font-medium">{{ category.properties_count }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">Total Revenue</span>
                                        <span class="font-medium text-green-600">{{ formatCurrency(category.total_revenue) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">Avg Price</span>
                                        <span class="font-medium">{{ formatCurrency(category.avg_price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- System Admin Section -->
            <div v-if="isSystemAdmin" class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                        <Icon name="shield" class="h-5 w-5" />
                        System Administration
                    </h2>
                    <Link href="/admin" class="text-sm text-primary hover:underline">
                        Admin panel →
                    </Link>
                </div>

                <!-- System Stats -->
                <div v-if="systemStats" class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="stat in systemStats" :key="stat.title" class="relative overflow-hidden hover:shadow-md transition-shadow">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                            <div :class="[stat.color, 'rounded-full p-2 text-white']">
                                <Icon :name="stat.icon" class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stat.value }}</div>
                            <p class="text-xs text-muted-foreground">{{ stat.description }}</p>
                            <div class="flex items-center gap-1 mt-2">
                                <Icon
                                    :name="stat.trendUp === true ? 'trendingUp' : stat.trendUp === false ? 'trendingDown' : 'minus'"
                                    :class="`h-3 w-3 ${stat.trendUp === true ? 'text-green-500' : stat.trendUp === false ? 'text-red-500' : 'text-gray-500'}`"
                                />
                                <span                                 :class="`text-xs font-medium ${stat.trendUp === true ? 'text-green-600' : stat.trendUp === false ? 'text-red-600' : 'text-gray-600'}`">
                                    {{ stat.trend }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- System Monitoring Grid -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- System Logs -->
                    <Card v-if="systemLogs">
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="fileText" class="h-5 w-5" />
                                Recent System Logs
                            </CardTitle>
                            <Button variant="outline" size="sm" @click="refreshData" :disabled="loading">
                                <Icon name="refreshCw" :class="loading ? 'h-4 w-4 mr-2 animate-spin' : 'h-4 w-4 mr-2'" />
                                Refresh
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div v-for="log in systemLogs.slice(0, 8)" :key="log.message"
                                     class="flex items-start gap-3 rounded-lg p-3 border"
                                     :class="getLogLevelColor(log.level)">
                                    <Badge :variant="log.level === 'error' ? 'destructive' : log.level === 'warning' ? 'secondary' : 'default'"
                                           class="text-xs">
                                        {{ log.level.toUpperCase() }}
                                    </Badge>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium">{{ log.message }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatRelativeTime(log.timestamp) }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Backup Status -->
                    <Card v-if="backupStatus">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="shield" class="h-5 w-5" />
                                Backup Status
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-center p-3 rounded-lg bg-muted/50">
                                        <p class="text-sm font-medium text-muted-foreground">Last Backup</p>
                                        <p class="text-lg font-bold">{{ formatRelativeTime(backupStatus.last_backup) }}</p>
                                    </div>
                                    <div class="text-center p-3 rounded-lg bg-muted/50">
                                        <p class="text-sm font-medium text-muted-foreground">Backup Size</p>
                                        <p class="text-lg font-bold">{{ backupStatus.backup_size }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 rounded-lg border">
                                    <span class="text-sm font-medium">Status</span>
                                    <Badge :variant="backupStatus.status === 'success' ? 'default' : 'destructive'">
                                        {{ backupStatus.status.toUpperCase() }}
                                    </Badge>
                                </div>

                                <div class="flex items-center justify-between p-3 rounded-lg border">
                                    <span class="text-sm font-medium">Next Scheduled</span>
                                    <span class="text-sm text-muted-foreground">{{ formatRelativeTime(backupStatus.next_scheduled) }}</span>
                                </div>

                                <div class="flex items-center justify-between p-3 rounded-lg border">
                                    <span class="text-sm font-medium">Retention</span>
                                    <span class="text-sm text-muted-foreground">{{ backupStatus.retention_days }} days</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Site Settings Overview -->
                <Card v-if="siteSettingsOverview">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="settings" class="h-5 w-5" />
                            Site Configuration
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div class="flex items-center justify-between p-3 rounded-lg border">
                                <span class="text-sm font-medium">Site Name</span>
                                <span class="text-sm text-muted-foreground">{{ siteSettingsOverview.site_name }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg border">
                                <span class="text-sm font-medium">Maintenance Mode</span>
                                <Badge :variant="siteSettingsOverview.maintenance_mode ? 'destructive' : 'default'">
                                    {{ siteSettingsOverview.maintenance_mode ? 'ON' : 'OFF' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg border">
                                <span class="text-sm font-medium">Analytics</span>
                                <Badge :variant="siteSettingsOverview.analytics_enabled ? 'default' : 'secondary'">
                                    {{ siteSettingsOverview.analytics_enabled ? 'Enabled' : 'Disabled' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg border">
                                <span class="text-sm font-medium">Logo</span>
                                <Badge :variant="siteSettingsOverview.logo_configured ? 'default' : 'secondary'">
                                    {{ siteSettingsOverview.logo_configured ? 'Set' : 'Not Set' }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <div class="space-y-6">
                <h2 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                    <Icon name="zap" class="h-5 w-5" />
                    Quick Actions
                </h2>

                <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Link v-for="action in quickActions" :key="action.title" :href="action.href"
                          class="group relative overflow-hidden rounded-lg border p-4 hover:shadow-md transition-all hover:border-primary/20">
                        <div class="flex items-center gap-3">
                            <div :class="[action.color, 'rounded-lg p-2 text-white transition-colors group-hover:scale-110']">
                                <Icon :name="action.icon" class="h-5 w-5" />
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium group-hover:text-primary transition-colors">{{ action.title }}</h4>
                                <p class="text-sm text-muted-foreground">{{ action.description }}</p>
                            </div>
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-r from-primary/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </Link>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="space-y-6">
                <h2 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                    <Icon name="activity" class="h-5 w-5" />
                    Recent Activities
                </h2>

                <Card>
                    <CardContent class="p-6">
                        <div class="space-y-4">
                            <div v-for="activity in recentActivities" :key="activity.id"
                                 class="flex items-start gap-3 p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                <div class="rounded-full p-2 flex-shrink-0" :class="getStatusColor(activity.status)">
                                    <Icon :name="activity.icon" class="h-4 w-4" />
                                </div>
                                <div class="flex-1 space-y-1">
                                    <p class="text-sm font-medium">{{ activity.title }}</p>
                                    <p class="text-sm text-muted-foreground">{{ activity.description }}</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-muted-foreground">{{ activity.time }}</span>
                                        <Badge :variant="activity.status === 'success' ? 'default' :
                                                       activity.status === 'warning' ? 'secondary' :
                                                       activity.status === 'error' ? 'destructive' : 'outline'"
                                               class="text-xs">
                                            {{ activity.status }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <div v-if="recentActivities.length === 0" class="text-center py-12">
                                <Icon name="inbox" class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                                <p class="text-muted-foreground text-lg">No recent activities</p>
                                <p class="text-sm text-muted-foreground mt-1">Activities will appear here as they happen</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Analytics Modal -->
        <AnalyticsModal
            :is-open="showAnalyticsModal"
            :property-performance="propertyPerformance"
            :location-stats="locationStats"
            :category-stats="categoryStats"
            :rented-stats="rentedStats"
            :client-stats="clientStats"
            :admin-stats="adminStats"
            :system-stats="systemStats"
            @close="showAnalyticsModal = false"
        />
    </AppLayout>
</template>

