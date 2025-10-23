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
import { computed, ref, onMounted } from 'vue';
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
    const totalGrowth = props.stats?.property_growth ?? 0;
    const featuredGrowth = props.stats?.featured_growth ?? 0;
    const availableProps = props.stats?.available_properties ?? 0;
    const totalProps = props.stats?.total_properties ?? 0;
    
    return [
        {
            title: 'Total Properties',
            value: formatNumber(totalProps),
            icon: 'home',
            description: 'Active listings',
            color: 'bg-blue-500',
            trend: totalGrowth !== 0 ? `${totalGrowth > 0 ? '+' : ''}${totalGrowth}%` : 'No change',
            trendUp: totalGrowth > 0 ? true : totalGrowth < 0 ? false : null
        },
        {
            title: 'Featured Properties',
            value: formatNumber(props.stats?.featured_properties || 0),
            icon: 'star',
            description: 'Premium listings',
            color: 'bg-yellow-500',
            trend: featuredGrowth !== 0 ? `${featuredGrowth > 0 ? '+' : ''}${featuredGrowth}%` : 'No change',
            trendUp: featuredGrowth > 0 ? true : featuredGrowth < 0 ? false : null
        },
        {
            title: 'Available Properties',
            value: formatNumber(availableProps),
            icon: 'home',
            description: 'Ready to rent',
            color: 'bg-green-500',
            trend: `${Math.round((availableProps / (totalProps || 1)) * 100)}% of total`,
            trendUp: availableProps > 0 ? true : null
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

    const revenueGrowth = props.adminStats.revenue_growth ?? 0;
    const monthlyGrowth = props.adminStats.monthly_growth ?? 0;
    const occupancyRate = props.adminStats.occupancy_rate ?? 0;

    return [
        {
            title: 'Total Revenue',
            value: formatCurrency(props.adminStats.total_revenue || 0),
            icon: 'dollarSign',
            description: 'Monthly potential',
            color: 'bg-emerald-500',
            trend: revenueGrowth !== 0 ? `${revenueGrowth > 0 ? '+' : ''}${revenueGrowth}%` : 'No change',
            trendUp: revenueGrowth > 0 ? true : revenueGrowth < 0 ? false : null
        },
        {
            title: 'Average Price',
            value: formatCurrency(props.adminStats.average_price || 0),
            icon: 'trendingUp',
            description: 'Per property',
            color: 'bg-orange-500',
            trend: `${props.adminStats.total_categories || 0} categories`,
            trendUp: null
        },
        {
            title: 'Monthly Growth',
            value: `${monthlyGrowth}%`,
            icon: 'barChart3',
            description: 'Property growth',
            color: monthlyGrowth >= 0 ? 'bg-green-500' : 'bg-red-500',
            trend: monthlyGrowth >= 0 ? 'Growing' : 'Declining',
            trendUp: monthlyGrowth >= 0
        },
        {
            title: 'Occupancy Rate',
            value: `${occupancyRate}%`,
            icon: 'users',
            description: 'Properties rented',
            color: occupancyRate > 80 ? 'bg-green-500' : occupancyRate > 60 ? 'bg-blue-500' : 'bg-orange-500',
            trend: occupancyRate > 80 ? 'Excellent' : occupancyRate > 60 ? 'Good' : occupancyRate > 40 ? 'Fair' : 'Low',
            trendUp: occupancyRate > 60
        }
    ];
});

const systemStats = computed(() => {
    if (!isSystemAdmin.value || !props.systemStats) return [];

    const totalUsers = props.systemStats.total_users || 0;
    const activeUsers = props.systemStats.active_users || 0;
    const usersThisMonth = props.systemStats.users_this_month || 0;
    const clientsThisMonth = props.systemStats.clients_this_month || 0;
    const activeRate = totalUsers > 0 ? Math.round((activeUsers / totalUsers) * 100) : 0;

    return [
        {
            title: 'Total Users',
            value: formatNumber(totalUsers),
            icon: 'users',
            description: 'Registered users',
            color: 'bg-cyan-500',
            trend: `+${usersThisMonth} this month`,
            trendUp: usersThisMonth > 0 ? true : null
        },
        {
            title: 'Active Users',
            value: formatNumber(activeUsers),
            icon: 'userCheck',
            description: 'Verified accounts',
            color: 'bg-teal-500',
            trend: `${activeRate}% verified`,
            trendUp: activeRate > 80 ? true : activeRate > 50 ? null : false
        },
        {
            title: 'Total Clients',
            value: formatNumber(props.systemStats.total_clients || 0),
            icon: 'users',
            description: 'Client accounts',
            color: 'bg-blue-500',
            trend: `+${clientsThisMonth} this month`,
            trendUp: clientsThisMonth > 0 ? true : null
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
    const pendingRentals = props.rentedStats.pending_rentals || 0;

    return [
        {
            title: 'Active Rentals',
            value: formatNumber(activeRentals),
            icon: 'home',
            description: 'Currently occupied',
            color: 'bg-green-500',
            trend: `${totalRentals} total`,
            trendUp: activeRentals > 0 ? true : null
        },
        {
            title: 'Monthly Revenue',
            value: formatCurrency(props.rentedStats.monthly_revenue || 0),
            icon: 'dollarSign',
            description: 'From active rentals',
            color: 'bg-emerald-500',
            trend: `${activeRentals} sources`,
            trendUp: true
        },
        {
            title: 'Expiring Soon',
            value: formatNumber(expiringSoon),
            icon: 'clock',
            description: 'Next 30 days',
            color: expiringSoon > 5 ? 'bg-red-500' : expiringSoon > 0 ? 'bg-orange-500' : 'bg-green-500',
            trend: expiringSoon > 0 ? 'Action needed' : 'All good',
            trendUp: expiringSoon === 0
        },
        {
            title: 'Pending Approvals',
            value: formatNumber(pendingRentals),
            icon: 'clock',
            description: 'Awaiting approval',
            color: 'bg-yellow-500',
            trend: pendingRentals > 0 ? 'Review needed' : 'All clear',
            trendUp: pendingRentals === 0
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
    const verificationRate = totalClients > 0 ? Math.round((verifiedClients / totalClients) * 100) : 0;

    return [
        {
            title: 'Total Clients',
            value: formatNumber(totalClients),
            icon: 'users',
            description: 'Registered clients',
            color: 'bg-blue-500',
            trend: `+${newThisMonth} this month`,
            trendUp: newThisMonth > 0 ? true : null
        },
        {
            title: 'Verified Clients',
            value: formatNumber(verifiedClients),
            icon: 'userCheck',
            description: 'Email verified',
            color: 'bg-green-500',
            trend: `${verificationRate}% verified`,
            trendUp: verificationRate > 80 ? true : verificationRate > 50 ? null : false
        },
        {
            title: 'Active Renters',
            value: formatNumber(activeRenters),
            icon: 'home',
            description: 'Currently renting',
            color: 'bg-purple-500',
            trend: totalClients > 0 ? `${Math.round((activeRenters / totalClients) * 100)}% of clients` : 'No clients',
            trendUp: activeRenters > 0 ? true : null
        },
        {
            title: 'New This Month',
            value: formatNumber(newThisMonth),
            icon: 'userPlus',
            description: 'Recent signups',
            color: 'bg-orange-500',
            trend: newThisMonth > 0 ? 'Growing' : 'No growth',
            trendUp: newThisMonth > 0 ? true : null
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
        error.value = 'Failed to refresh data';
        console.error('Refresh error:', err);
    } finally {
        loading.value = false;
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
    // Any initialization logic
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 sm:p-6">
            <!-- Welcome Section -->
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Welcome back, {{ user.name }}!</h1>
                        <p class="text-muted-foreground mt-1">
                            Here's your comprehensive real estate management overview
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" @click="refreshData" :disabled="loading">
                            <Icon name="refreshCw" :class="loading ? 'h-4 w-4 mr-2 animate-spin' : 'h-4 w-4 mr-2'" />
                            Refresh
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

            <!-- Error Alert -->
            <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                <div class="flex">
                    <Icon name="alertCircle" class="h-5 w-5 text-red-400" />
                    <div class="ml-3">
                        <p class="text-sm text-red-800 dark:text-red-200">{{ error }}</p>
                    </div>
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

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card v-for="stat in propertyStats" :key="stat.title" class="relative overflow-hidden hover:shadow-md transition-shadow">
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
            <div v-if="locationStats && locationStats.length > 0" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold tracking-tight flex items-center gap-2">
                        <Icon name="mapPin" class="h-4 w-4" />
                        Top Locations
                    </h2>
                    <Link href="/locations" class="text-sm text-primary hover:underline">
                        View all locations →
                    </Link>
                </div>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="(location, index) in locationStats.slice(0, 6)" :key="location.name" class="relative overflow-hidden hover:shadow-md transition-shadow">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium truncate">{{ location.name }}</CardTitle>
                            <div class="rounded-full p-2 text-white bg-blue-500">
                                <Icon name="mapPin" class="h-4 w-4" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">Code</span>
                                    <Badge variant="outline" class="text-xs">{{ location.code }}</Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">Properties</span>
                                    <span class="text-sm font-semibold">{{ formatNumber(location.properties_count) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-muted-foreground">Avg Price</span>
                                    <span class="text-sm font-semibold text-green-600">{{ formatCurrency(location.avg_price) }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t">
                                    <span class="text-xs text-muted-foreground">Total Revenue</span>
                                    <span class="text-sm font-bold text-emerald-600">{{ formatCurrency(location.total_revenue) }}</span>
                                </div>
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
                <div v-if="rentedStats" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
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
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
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
                </div>

                <!-- Admin Stats Grid -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
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
                    <Card v-if="propertyPerformance && propertyPerformance.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="trendingUp" class="h-5 w-5" />
                                Property Performance (6 Months)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="chartData.length > 0" class="space-y-4">
                                <div v-for="item in chartData" :key="item.month"
                                     class="flex items-center justify-between p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                        <span class="text-sm font-medium">{{ item.month }} {{ item.year }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm">
                                        <span class="text-muted-foreground">{{ formatNumber(item.properties) }} properties</span>
                                        <span class="font-medium text-green-600">{{ formatCurrency(item.revenue) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-12">
                                <Icon name="trendingUp" class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                                <p class="text-muted-foreground text-lg">No performance data available</p>
                                <p class="text-sm text-muted-foreground mt-1">Data will appear as properties are added</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Location Stats -->
                    <Card v-if="locationStats && locationStats.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="mapPin" class="h-5 w-5" />
                                Top Performing Locations
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div v-for="(location, index) in locationStats.slice(0, 5)" :key="location.name"
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
                                        <p class="font-medium">{{ formatNumber(location.properties_count) }} properties</p>
                                        <p class="text-sm text-muted-foreground">{{ formatCurrency(location.avg_price) }} avg</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="locationStats.length === 0" class="text-center py-12">
                                <Icon name="mapPin" class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                                <p class="text-muted-foreground text-lg">No location data available</p>
                                <p class="text-sm text-muted-foreground mt-1">Add properties to see location statistics</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Category Stats -->
                <Card v-if="categoryStats && categoryStats.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="tag" class="h-5 w-5" />
                            Property Categories Performance
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div v-for="category in categoryStats" :key="category.name"
                                 class="rounded-lg border p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                    <h4 class="font-medium">{{ category.name }}</h4>
                                </div>
                                <p class="text-sm text-muted-foreground mb-3 line-clamp-2">{{ category.description || 'No description available' }}</p>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-muted-foreground">Properties</span>
                                        <span class="font-medium">{{ formatNumber(category.properties_count) }}</span>
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

                        <div v-if="categoryStats.length === 0" class="text-center py-12">
                            <Icon name="tag" class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                            <p class="text-muted-foreground text-lg">No categories found</p>
                            <p class="text-sm text-muted-foreground mt-1">Create categories to organize your properties</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- System Admin Section -->
            <div v-if="isSystemAdmin" class="space-y-6">
                <!-- Header with Action Buttons -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                            <div class="p-2 rounded-lg bg-red-100 dark:bg-red-900/20">
                                <Icon name="shield" class="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                            System Administration
                        </h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            Monitor and manage your system resources and configurations
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" @click="refreshData" :disabled="loading">
                            <Icon name="refreshCw" :class="loading ? 'h-4 w-4 mr-2 animate-spin' : 'h-4 w-4 mr-2'" />
                            Refresh Stats
                        </Button>
                        <Link href="/admin">
                            <Button size="sm">
                                <Icon name="settings" class="h-4 w-4 mr-2" />
                                Admin Panel
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- System Overview Stats -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <Icon name="activity" class="h-5 w-5 text-primary" />
                        System Overview
                    </h3>
                    <div v-if="systemStats && systemStats.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Card v-for="stat in systemStats" :key="stat.title" 
                              class="relative overflow-hidden hover:shadow-lg transition-all duration-300 border-l-4"
                              :class="stat.trendUp === true ? 'border-l-green-500' : stat.trendUp === false ? 'border-l-red-500' : 'border-l-gray-400'">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium text-muted-foreground">{{ stat.title }}</CardTitle>
                                <div :class="[stat.color, 'rounded-lg p-2.5 text-white shadow-sm']">
                                    <Icon :name="stat.icon" class="h-5 w-5" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-3xl font-bold mb-1">{{ stat.value }}</div>
                                <p class="text-xs text-muted-foreground mb-3">{{ stat.description }}</p>
                                <div class="flex items-center gap-1.5 pt-2 border-t">
                                    <Icon
                                        :name="stat.trendUp === true ? 'trendingUp' : stat.trendUp === false ? 'trendingDown' : 'minus'"
                                        :class="`h-4 w-4 ${stat.trendUp === true ? 'text-green-600' : stat.trendUp === false ? 'text-red-600' : 'text-gray-500'}`"
                                    />
                                    <span :class="`text-sm font-semibold ${stat.trendUp === true ? 'text-green-600' : stat.trendUp === false ? 'text-red-600' : 'text-gray-600'}`">
                                        {{ stat.trend }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Empty State for System Stats -->
                    <Card v-else class="border-dashed">
                        <CardContent class="text-center py-12">
                            <Icon name="activity" class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                            <p class="text-muted-foreground text-lg font-medium">No system statistics available</p>
                            <p class="text-sm text-muted-foreground mt-1">System data will appear here once initialized</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- System Monitoring & Logs -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <Icon name="monitor" class="h-5 w-5 text-primary" />
                        System Monitoring
                    </h3>
                    <div class="grid gap-6 lg:grid-cols-2">
                        <!-- System Logs -->
                        <Card class="flex flex-col">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0">
                                <CardTitle class="flex items-center gap-2">
                                    <Icon name="fileText" class="h-5 w-5" />
                                    Recent System Logs
                                </CardTitle>
                                <Badge variant="secondary" class="text-xs">
                                    Last {{ systemLogs?.length || 0 }} entries
                                </Badge>
                            </CardHeader>
                            <CardContent class="flex-1">
                                <div v-if="systemLogs && systemLogs.length > 0" class="space-y-2">
                                    <div v-for="(log, index) in systemLogs.slice(0, 6)" :key="`${log.message}-${index}`"
                                         class="flex items-start gap-3 rounded-lg p-3 border-l-2 hover:bg-muted/50 transition-colors"
                                         :class="log.level === 'error' ? 'border-l-red-500 bg-red-50 dark:bg-red-900/10' : 
                                                 log.level === 'warning' ? 'border-l-yellow-500 bg-yellow-50 dark:bg-yellow-900/10' : 
                                                 'border-l-blue-500 bg-blue-50 dark:bg-blue-900/10'">
                                        <Badge :variant="log.level === 'error' ? 'destructive' : log.level === 'warning' ? 'secondary' : 'default'"
                                               class="text-xs shrink-0 mt-0.5">
                                            {{ log.level.toUpperCase() }}
                                        </Badge>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium">{{ log.message }}</p>
                                            <p class="text-xs text-muted-foreground mt-1">
                                                <Icon name="clock" class="h-3 w-3 inline mr-1" />
                                                {{ formatRelativeTime(log.timestamp) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="text-center py-12">
                                    <Icon name="fileText" class="h-12 w-12 mx-auto text-muted-foreground mb-3" />
                                    <p class="text-muted-foreground font-medium">No system logs available</p>
                                    <p class="text-xs text-muted-foreground mt-1">Logs will appear here as system events occur</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Backup Status -->
                        <Card class="flex flex-col" v-if="backupStatus">
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Icon name="database" class="h-5 w-5" />
                                    Database Backup
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="flex-1">
                                <div class="space-y-4">
                                    <!-- Backup Status Indicator -->
                                    <div class="flex items-center justify-center p-4 rounded-lg"
                                         :class="backupStatus.status === 'success' ? 'bg-green-50 dark:bg-green-900/20' : 
                                                 backupStatus.status === 'no_backups' ? 'bg-yellow-50 dark:bg-yellow-900/20' : 
                                                 'bg-red-50 dark:bg-red-900/20'">
                                        <div class="text-center">
                                            <Icon :name="backupStatus.status === 'success' ? 'checkCircle' : 
                                                       backupStatus.status === 'no_backups' ? 'alertCircle' : 'xCircle'" 
                                                  :class="backupStatus.status === 'success' ? 'h-10 w-10 text-green-600' : 
                                                         backupStatus.status === 'no_backups' ? 'h-10 w-10 text-yellow-600' : 
                                                         'h-10 w-10 text-red-600'" 
                                                  class="mx-auto mb-2" />
                                            <Badge :variant="backupStatus.status === 'success' ? 'default' : 
                                                           backupStatus.status === 'no_backups' ? 'secondary' : 'destructive'"
                                                   class="text-sm">
                                                {{ (backupStatus.status || 'unknown').toUpperCase().replace('_', ' ') }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <!-- Backup Details Grid -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="text-center p-3 rounded-lg border bg-muted/30">
                                            <Icon name="clock" class="h-4 w-4 mx-auto mb-1 text-muted-foreground" />
                                            <p class="text-xs font-medium text-muted-foreground mb-1">Last Backup</p>
                                            <p class="text-sm font-bold">{{ backupStatus.last_backup ? formatRelativeTime(backupStatus.last_backup) : 'Never' }}</p>
                                        </div>
                                        <div class="text-center p-3 rounded-lg border bg-muted/30">
                                            <Icon name="hardDrive" class="h-4 w-4 mx-auto mb-1 text-muted-foreground" />
                                            <p class="text-xs font-medium text-muted-foreground mb-1">Backup Size</p>
                                            <p class="text-sm font-bold">{{ backupStatus.backup_size || '0 MB' }}</p>
                                        </div>
                                    </div>

                                    <!-- Additional Backup Info -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between p-2.5 rounded-lg border bg-muted/20">
                                            <span class="text-xs font-medium flex items-center gap-1.5">
                                                <Icon name="calendar" class="h-3.5 w-3.5" />
                                                Next Scheduled
                                            </span>
                                            <span class="text-xs text-muted-foreground font-medium">
                                                {{ backupStatus.next_scheduled ? formatRelativeTime(backupStatus.next_scheduled) : 'Not scheduled' }}
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between p-2.5 rounded-lg border bg-muted/20">
                                            <span class="text-xs font-medium flex items-center gap-1.5">
                                                <Icon name="trash2" class="h-3.5 w-3.5" />
                                                Retention Period
                                            </span>
                                            <span class="text-xs text-muted-foreground font-medium">{{ backupStatus.retention_days || 30 }} days</span>
                                        </div>

                                        <div v-if="backupStatus.backup_count" class="flex items-center justify-between p-2.5 rounded-lg border bg-muted/20">
                                            <span class="text-xs font-medium flex items-center gap-1.5">
                                                <Icon name="archive" class="h-3.5 w-3.5" />
                                                Total Backups
                                            </span>
                                            <Badge variant="outline" class="text-xs">{{ backupStatus.backup_count }} files</Badge>
                                        </div>
                                    </div>

                                    <!-- Quick Action -->
                                    <Link href="/admin/database-backup" class="block">
                                        <Button variant="outline" size="sm" class="w-full">
                                            <Icon name="database" class="h-4 w-4 mr-2" />
                                            Manage Backups
                                        </Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- System Configuration -->
                <div v-if="siteSettingsOverview">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <Icon name="settings" class="h-5 w-5 text-primary" />
                        System Configuration
                    </h3>
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="sliders" class="h-5 w-5" />
                                Site Settings Overview
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="flex flex-col gap-2 p-4 rounded-lg border hover:shadow-md transition-shadow bg-gradient-to-br from-muted/30 to-transparent">
                                    <div class="flex items-center justify-between">
                                        <Icon name="home" class="h-5 w-5 text-blue-600" />
                                        <Badge variant="outline" class="text-xs">Config</Badge>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-muted-foreground mb-1">Site Name</p>
                                        <p class="text-sm font-bold truncate">{{ siteSettingsOverview.site_name || 'Not Set' }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 p-4 rounded-lg border hover:shadow-md transition-shadow bg-gradient-to-br from-muted/30 to-transparent">
                                    <div class="flex items-center justify-between">
                                        <Icon name="wrench" class="h-5 w-5 text-orange-600" />
                                        <Badge :variant="siteSettingsOverview.maintenance_mode ? 'destructive' : 'default'" class="text-xs">
                                            {{ siteSettingsOverview.maintenance_mode ? 'ON' : 'OFF' }}
                                        </Badge>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-muted-foreground mb-1">Maintenance Mode</p>
                                        <p class="text-sm font-bold">{{ siteSettingsOverview.maintenance_mode ? 'Active' : 'Inactive' }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 p-4 rounded-lg border hover:shadow-md transition-shadow bg-gradient-to-br from-muted/30 to-transparent">
                                    <div class="flex items-center justify-between">
                                        <Icon name="barChart2" class="h-5 w-5 text-green-600" />
                                        <Badge :variant="siteSettingsOverview.analytics_enabled ? 'default' : 'secondary'" class="text-xs">
                                            {{ siteSettingsOverview.analytics_enabled ? 'ON' : 'OFF' }}
                                        </Badge>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-muted-foreground mb-1">Analytics</p>
                                        <p class="text-sm font-bold">{{ siteSettingsOverview.analytics_enabled ? 'Tracking Enabled' : 'Disabled' }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 p-4 rounded-lg border hover:shadow-md transition-shadow bg-gradient-to-br from-muted/30 to-transparent">
                                    <div class="flex items-center justify-between">
                                        <Icon name="image" class="h-5 w-5 text-purple-600" />
                                        <Badge :variant="siteSettingsOverview.logo_configured ? 'default' : 'secondary'" class="text-xs">
                                            {{ siteSettingsOverview.logo_configured ? 'SET' : 'NOT SET' }}
                                        </Badge>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-muted-foreground mb-1">Logo & Branding</p>
                                        <p class="text-sm font-bold">{{ siteSettingsOverview.logo_configured ? 'Configured' : 'Not Configured' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- System Info Footer -->
                            <div v-if="props.systemStats" class="mt-6 pt-6 border-t">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-center">
                                    <div v-if="props.systemStats.php_version" class="p-3 rounded-lg bg-muted/30">
                                        <Icon name="code" class="h-4 w-4 mx-auto mb-1 text-muted-foreground" />
                                        <p class="text-xs text-muted-foreground">PHP Version</p>
                                        <p class="text-sm font-bold mt-1">{{ props.systemStats.php_version }}</p>
                                    </div>
                                    <div v-if="props.systemStats.laravel_version" class="p-3 rounded-lg bg-muted/30">
                                        <Icon name="box" class="h-4 w-4 mx-auto mb-1 text-muted-foreground" />
                                        <p class="text-xs text-muted-foreground">Laravel</p>
                                        <p class="text-sm font-bold mt-1">{{ props.systemStats.laravel_version }}</p>
                                    </div>
                                    <div v-if="props.systemStats.system_uptime" class="p-3 rounded-lg bg-muted/30">
                                        <Icon name="server" class="h-4 w-4 mx-auto mb-1 text-muted-foreground" />
                                        <p class="text-xs text-muted-foreground">System Load</p>
                                        <p class="text-sm font-bold mt-1">{{ props.systemStats.system_uptime }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Settings Actions -->
                            <div class="mt-6 flex flex-wrap gap-2">
                                <Link href="/admin/site-settings">
                                    <Button variant="outline" size="sm">
                                        <Icon name="settings" class="h-4 w-4 mr-2" />
                                        Site Settings
                                    </Button>
                                </Link>
                                <Link href="/admin/users">
                                    <Button variant="outline" size="sm">
                                        <Icon name="users" class="h-4 w-4 mr-2" />
                                        User Management
                                    </Button>
                                </Link>
                                <Link href="/admin/roles">
                                    <Button variant="outline" size="sm">
                                        <Icon name="shield" class="h-4 w-4 mr-2" />
                                        Roles & Permissions
                                    </Button>
                                </Link>
                                <Link href="/admin/database-backup">
                                    <Button variant="outline" size="sm">
                                        <Icon name="database" class="h-4 w-4 mr-2" />
                                        Backup Management
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="space-y-6">
                <h2 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                    <Icon name="zap" class="h-5 w-5" />
                    Quick Actions
                </h2>

                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
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
    </AppLayout>
</template>

