<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import Icon from '@/components/Icon.vue';
import { formatCurrency, formatNumber, formatRelativeTime } from '@/utils/formatters';

interface Props {
    isOpen: boolean;
    propertyPerformance?: any[];
    locationStats?: any[];
    categoryStats?: any[];
    rentedStats?: any;
    clientStats?: any;
    adminStats?: any;
    systemStats?: any;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
}>();

// Loading states
const loading = ref(false);
const activeTab = ref('overview');

// Tab options
const tabs = [
    { id: 'overview', label: 'Overview', icon: 'barChart3' },
    { id: 'properties', label: 'Properties', icon: 'home' },
    { id: 'rentals', label: 'Rentals', icon: 'fileText' },
    { id: 'clients', label: 'Clients', icon: 'users' },
    { id: 'financial', label: 'Financial', icon: 'dollarSign' },
    { id: 'locations', label: 'Locations', icon: 'mapPin' },
];

// Chart data processing
const chartData = computed(() => {
    if (!props.propertyPerformance) return [];
    return props.propertyPerformance.map(item => ({
        ...item,
        revenue: Number(item.revenue) || 0,
        properties: Number(item.properties) || 0
    }));
});

// Financial metrics
const financialMetrics = computed(() => {
    if (!props.adminStats || !props.rentedStats) return [];

    return [
        {
            title: 'Total Revenue Potential',
            value: formatCurrency(props.adminStats.total_revenue || 0),
            description: 'Monthly revenue if all properties rented',
            trend: '+12%',
            trendUp: true,
            color: 'text-green-600'
        },
        {
            title: 'Current Monthly Revenue',
            value: formatCurrency(props.rentedStats.monthly_revenue || 0),
            description: 'From active rentals',
            trend: '+8%',
            trendUp: true,
            color: 'text-blue-600'
        },
        {
            title: 'Average Property Value',
            value: formatCurrency(props.adminStats.average_price || 0),
            description: 'Per property monthly rate',
            trend: '+5%',
            trendUp: true,
            color: 'text-purple-600'
        },
        {
            title: 'Revenue Efficiency',
            value: `${Math.round(((props.rentedStats.monthly_revenue || 0) / (props.adminStats.total_revenue || 1)) * 100)}%`,
            description: 'Current vs potential revenue',
            trend: '+3%',
            trendUp: true,
            color: 'text-orange-600'
        }
    ];
});

// Property performance metrics
const propertyMetrics = computed(() => {
    if (!props.adminStats) return [];

    return [
        {
            title: 'Total Properties',
            value: formatNumber(props.adminStats.total_properties || 0),
            description: 'All properties in system',
            trend: '+5',
            trendUp: true
        },
        {
            title: 'Occupancy Rate',
            value: `${props.adminStats.occupancy_rate || 0}%`,
            description: 'Properties currently rented',
            trend: props.adminStats.occupancy_rate > 80 ? 'Excellent' : props.adminStats.occupancy_rate > 60 ? 'Good' : 'Needs attention',
            trendUp: props.adminStats.occupancy_rate > 60
        },
        {
            title: 'Featured Properties',
            value: formatNumber(props.adminStats.featured_properties || 0),
            description: 'Premium listings',
            trend: '+2',
            trendUp: true
        },
        {
            title: 'Monthly Growth',
            value: `${props.adminStats.monthly_growth || 0}%`,
            description: 'New properties this month',
            trend: props.adminStats.monthly_growth >= 0 ? 'Growing' : 'Declining',
            trendUp: props.adminStats.monthly_growth >= 0
        }
    ];
});

// Rental performance metrics
const rentalMetrics = computed(() => {
    if (!props.rentedStats) return [];

    return [
        {
            title: 'Active Rentals',
            value: formatNumber(props.rentedStats.active_rentals || 0),
            description: 'Currently occupied',
            trend: '+3',
            trendUp: true
        },
        {
            title: 'Expiring Soon',
            value: formatNumber(props.rentedStats.expiring_soon || 0),
            description: 'Next 30 days',
            trend: props.rentedStats.expiring_soon > 0 ? 'Action needed' : 'All good',
            trendUp: props.rentedStats.expiring_soon === 0
        },
        {
            title: 'Pending Rentals',
            value: formatNumber(props.rentedStats.pending_rentals || 0),
            description: 'Awaiting approval',
            trend: '+1',
            trendUp: true
        },
        {
            title: 'Terminated Rentals',
            value: formatNumber(props.rentedStats.terminated_rentals || 0),
            description: 'This month',
            trend: '0',
            trendUp: null
        }
    ];
});

// Client performance metrics
const clientMetrics = computed(() => {
    if (!props.clientStats) return [];

    return [
        {
            title: 'Total Clients',
            value: formatNumber(props.clientStats.total_clients || 0),
            description: 'Registered clients',
            trend: '+8',
            trendUp: true
        },
        {
            title: 'Verified Clients',
            value: formatNumber(props.clientStats.verified_clients || 0),
            description: 'Email verified',
            trend: '+6',
            trendUp: true
        },
        {
            title: 'Active Renters',
            value: formatNumber(props.clientStats.active_renters || 0),
            description: 'Currently renting',
            trend: '+3',
            trendUp: true
        },
        {
            title: 'New This Month',
            value: formatNumber(props.clientStats.new_this_month || 0),
            description: 'Recent signups',
            trend: '+4',
            trendUp: true
        }
    ];
});

// Location performance data
const locationPerformance = computed(() => {
    if (!props.locationStats) return [];

    return props.locationStats.slice(0, 10).map((location, index) => ({
        ...location,
        rank: index + 1,
        performance: Math.round((location.properties_count / Math.max(...props.locationStats.map(l => l.properties_count))) * 100)
    }));
});

// Category performance data
const categoryPerformance = computed(() => {
    if (!props.categoryStats) return [];

    return props.categoryStats.map(category => ({
        ...category,
        performance: Math.round((category.properties_count / Math.max(...props.categoryStats.map(c => c.properties_count))) * 100)
    }));
});

// Utility functions
const getTrendIcon = (trendUp: boolean | null) => {
    if (trendUp === true) return 'trendingUp';
    if (trendUp === false) return 'trendingDown';
    return 'minus';
};

const getTrendColor = (trendUp: boolean | null) => {
    if (trendUp === true) return 'text-green-600';
    if (trendUp === false) return 'text-red-600';
    return 'text-gray-600';
};

const exportReport = async () => {
    try {
        const response = await fetch('/dashboard/export', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to export data');
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `analytics-export-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

    } catch (error) {
        console.error('Export error:', error);
        alert('Failed to export analytics data');
    }
};

const refreshData = async () => {
    loading.value = true;
    try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        // In real implementation, you'd fetch fresh data here
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    // Initialize any required data
});
</script>

<template>
    <Dialog :open="isOpen" @update:open="emit('close')">
        <DialogContent class="max-w-7xl max-h-[90vh] overflow-hidden">
            <DialogHeader class="flex-shrink-0">
                <div class="flex items-center justify-between">
                    <DialogTitle class="flex items-center gap-2 text-xl">
                        <Icon name="barChart3" class="h-6 w-6" />
                        Analytics & Performance Dashboard
                    </DialogTitle>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" @click="refreshData" :disabled="loading">
                            <Icon name="refreshCw" :class="loading ? 'h-4 w-4 mr-2 animate-spin' : 'h-4 w-4 mr-2'" />
                            Refresh
                        </Button>
                        <Button variant="outline" size="sm" @click="exportReport">
                            <Icon name="download" class="h-4 w-4 mr-2" />
                            Export
                        </Button>
                    </div>
                </div>
            </DialogHeader>

            <!-- Tab Navigation -->
            <div class="flex-shrink-0 border-b">
                <nav class="flex space-x-8 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'flex items-center gap-2 px-1 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-colors',
                            activeTab === tab.id
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground hover:border-gray-300'
                        ]"
                    >
                        <Icon :name="tab.icon" class="h-4 w-4" />
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="flex-1 overflow-y-auto p-1">
                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'" class="space-y-6">
                    <!-- Key Metrics Grid -->
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card v-for="metric in financialMetrics" :key="metric.title" class="hover:shadow-md transition-shadow">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">{{ metric.title }}</CardTitle>
                                <div class="rounded-full p-2 bg-primary/10">
                                    <Icon name="dollarSign" class="h-4 w-4 text-primary" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold" :class="metric.color">{{ metric.value }}</div>
                                <p class="text-xs text-muted-foreground">{{ metric.description }}</p>
                                <div class="flex items-center gap-1 mt-2">
                                    <Icon
                                        :name="getTrendIcon(metric.trendUp)"
                                        :class="['h-3 w-3', getTrendColor(metric.trendUp)]"
                                    />
                                    <span :class="['text-xs font-medium', getTrendColor(metric.trendUp)]">
                                        {{ metric.trend }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Performance Charts -->
                    <div class="grid gap-6 lg:grid-cols-2">
                        <!-- Property Performance Chart -->
                        <Card>
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

                        <!-- Top Locations -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Icon name="mapPin" class="h-5 w-5" />
                                    Top Performing Locations
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-3">
                                    <div v-for="location in locationPerformance.slice(0, 5)" :key="location.name"
                                         class="flex items-center justify-between p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold">
                                                {{ location.rank }}
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
                </div>

                <!-- Properties Tab -->
                <div v-if="activeTab === 'properties'" class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card v-for="metric in propertyMetrics" :key="metric.title" class="hover:shadow-md transition-shadow">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">{{ metric.title }}</CardTitle>
                                <div class="rounded-full p-2 bg-blue-500/10">
                                    <Icon name="home" class="h-4 w-4 text-blue-500" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">{{ metric.value }}</div>
                                <p class="text-xs text-muted-foreground">{{ metric.description }}</p>
                                <div class="flex items-center gap-1 mt-2">
                                    <Icon
                                        :name="getTrendIcon(metric.trendUp)"
                                        :class="['h-3 w-3', getTrendColor(metric.trendUp)]"
                                    />
                                    <span :class="['text-xs font-medium', getTrendColor(metric.trendUp)]">
                                        {{ metric.trend }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Category Performance -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="tag" class="h-5 w-5" />
                                Property Categories Performance
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div v-for="category in categoryPerformance" :key="category.name"
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
                                        <div class="mt-2">
                                            <div class="flex justify-between text-xs text-muted-foreground mb-1">
                                                <span>Performance</span>
                                                <span>{{ category.performance }}%</span>
                                            </div>
                                            <Progress :value="category.performance" class="h-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Rentals Tab -->
                <div v-if="activeTab === 'rentals'" class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card v-for="metric in rentalMetrics" :key="metric.title" class="hover:shadow-md transition-shadow">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">{{ metric.title }}</CardTitle>
                                <div class="rounded-full p-2 bg-green-500/10">
                                    <Icon name="fileText" class="h-4 w-4 text-green-500" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">{{ metric.value }}</div>
                                <p class="text-xs text-muted-foreground">{{ metric.description }}</p>
                                <div class="flex items-center gap-1 mt-2">
                                    <Icon
                                        :name="getTrendIcon(metric.trendUp)"
                                        :class="['h-3 w-3', getTrendColor(metric.trendUp)]"
                                    />
                                    <span :class="['text-xs font-medium', getTrendColor(metric.trendUp)]">
                                        {{ metric.trend }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Rental Status Distribution -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="pieChart" class="h-5 w-5" />
                                Rental Status Distribution
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <div class="text-center p-4 rounded-lg border">
                                    <div class="text-2xl font-bold text-green-600">{{ formatNumber(rentedStats?.active_rentals || 0) }}</div>
                                    <p class="text-sm text-muted-foreground">Active</p>
                                    <Badge variant="default" class="mt-2">Current</Badge>
                                </div>
                                <div class="text-center p-4 rounded-lg border">
                                    <div class="text-2xl font-bold text-yellow-600">{{ formatNumber(rentedStats?.pending_rentals || 0) }}</div>
                                    <p class="text-sm text-muted-foreground">Pending</p>
                                    <Badge variant="secondary" class="mt-2">Awaiting</Badge>
                                </div>
                                <div class="text-center p-4 rounded-lg border">
                                    <div class="text-2xl font-bold text-orange-600">{{ formatNumber(rentedStats?.expiring_soon || 0) }}</div>
                                    <p class="text-sm text-muted-foreground">Expiring</p>
                                    <Badge variant="outline" class="mt-2">Soon</Badge>
                                </div>
                                <div class="text-center p-4 rounded-lg border">
                                    <div class="text-2xl font-bold text-red-600">{{ formatNumber(rentedStats?.terminated_rentals || 0) }}</div>
                                    <p class="text-sm text-muted-foreground">Terminated</p>
                                    <Badge variant="destructive" class="mt-2">Ended</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Clients Tab -->
                <div v-if="activeTab === 'clients'" class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card v-for="metric in clientMetrics" :key="metric.title" class="hover:shadow-md transition-shadow">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">{{ metric.title }}</CardTitle>
                                <div class="rounded-full p-2 bg-purple-500/10">
                                    <Icon name="users" class="h-4 w-4 text-purple-500" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold">{{ metric.value }}</div>
                                <p class="text-xs text-muted-foreground">{{ metric.description }}</p>
                                <div class="flex items-center gap-1 mt-2">
                                    <Icon
                                        :name="getTrendIcon(metric.trendUp)"
                                        :class="['h-3 w-3', getTrendColor(metric.trendUp)]"
                                    />
                                    <span :class="['text-xs font-medium', getTrendColor(metric.trendUp)]">
                                        {{ metric.trend }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Client Verification Progress -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="userCheck" class="h-5 w-5" />
                                Client Verification Status
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium">Email Verification</span>
                                        <span class="text-sm text-muted-foreground">
                                            {{ Math.round(((clientStats?.verified_clients || 0) / Math.max(clientStats?.total_clients || 1, 1)) * 100) }}%
                                        </span>
                                    </div>
                                    <Progress :value="Math.round(((clientStats?.verified_clients || 0) / Math.max(clientStats?.total_clients || 1, 1)) * 100)" class="h-2" />
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium">Active Clients</span>
                                        <span class="text-sm text-muted-foreground">
                                            {{ Math.round(((clientStats?.active_clients || 0) / Math.max(clientStats?.total_clients || 1, 1)) * 100) }}%
                                        </span>
                                    </div>
                                    <Progress :value="Math.round(((clientStats?.active_clients || 0) / Math.max(clientStats?.total_clients || 1, 1)) * 100)" class="h-2" />
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium">Active Renters</span>
                                        <span class="text-sm text-muted-foreground">
                                            {{ Math.round(((clientStats?.active_renters || 0) / Math.max(clientStats?.total_clients || 1, 1)) * 100) }}%
                                        </span>
                                    </div>
                                    <Progress :value="Math.round(((clientStats?.active_renters || 0) / Math.max(clientStats?.total_clients || 1, 1)) * 100)" class="h-2" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Financial Tab -->
                <div v-if="activeTab === 'financial'" class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card v-for="metric in financialMetrics" :key="metric.title" class="hover:shadow-md transition-shadow">
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-sm font-medium">{{ metric.title }}</CardTitle>
                                <div class="rounded-full p-2 bg-emerald-500/10">
                                    <Icon name="dollarSign" class="h-4 w-4 text-emerald-500" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl font-bold" :class="metric.color">{{ metric.value }}</div>
                                <p class="text-xs text-muted-foreground">{{ metric.description }}</p>
                                <div class="flex items-center gap-1 mt-2">
                                    <Icon
                                        :name="getTrendIcon(metric.trendUp)"
                                        :class="['h-3 w-3', getTrendColor(metric.trendUp)]"
                                    />
                                    <span :class="['text-xs font-medium', getTrendColor(metric.trendUp)]">
                                        {{ metric.trend }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Revenue Breakdown -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="pieChart" class="h-5 w-5" />
                                Revenue Breakdown
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div class="text-center p-4 rounded-lg border">
                                    <div class="text-2xl font-bold text-green-600">{{ formatCurrency(rentedStats?.monthly_revenue || 0) }}</div>
                                    <p class="text-sm text-muted-foreground">Current Revenue</p>
                                    <p class="text-xs text-muted-foreground mt-1">From active rentals</p>
                                </div>
                                <div class="text-center p-4 rounded-lg border">
                                    <div class="text-2xl font-bold text-blue-600">{{ formatCurrency((adminStats?.total_revenue || 0) - (rentedStats?.monthly_revenue || 0)) }}</div>
                                    <p class="text-sm text-muted-foreground">Potential Revenue</p>
                                    <p class="text-xs text-muted-foreground mt-1">From available properties</p>
                                </div>
                                <div class="text-center p-4 rounded-lg border">
                                    <div class="text-2xl font-bold text-purple-600">{{ formatCurrency(adminStats?.total_revenue || 0) }}</div>
                                    <p class="text-sm text-muted-foreground">Total Potential</p>
                                    <p class="text-xs text-muted-foreground mt-1">If all properties rented</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Locations Tab -->
                <div v-if="activeTab === 'locations'" class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Icon name="mapPin" class="h-5 w-5" />
                                Location Performance Analysis
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div v-for="location in locationPerformance" :key="location.name"
                                     class="flex items-center justify-between p-4 rounded-lg border hover:bg-muted/50 transition-colors">
                                    <div class="flex items-center gap-4">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold">
                                            {{ location.rank }}
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ location.name }}</p>
                                            <p class="text-sm text-muted-foreground">{{ location.code }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-6">
                                        <div class="text-center">
                                            <p class="text-lg font-bold">{{ location.properties_count }}</p>
                                            <p class="text-xs text-muted-foreground">Properties</p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-lg font-bold text-green-600">{{ formatCurrency(location.avg_price) }}</p>
                                            <p class="text-xs text-muted-foreground">Avg Price</p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-lg font-bold text-blue-600">{{ formatCurrency(location.total_revenue) }}</p>
                                            <p class="text-xs text-muted-foreground">Total Revenue</p>
                                        </div>
                                        <div class="w-20">
                                            <div class="flex justify-between text-xs text-muted-foreground mb-1">
                                                <span>Performance</span>
                                                <span>{{ location.performance }}%</span>
                                            </div>
                                            <Progress :value="location.performance" class="h-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
