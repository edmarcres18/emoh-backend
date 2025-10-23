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
    if (!props.locationStats || props.locationStats.length === 0) return [];

    const maxCount = Math.max(...props.locationStats.map(l => l.properties_count));
    return props.locationStats.slice(0, 10).map((location, index) => ({
        ...location,
        rank: index + 1,
        performance: Math.round((location.properties_count / maxCount) * 100)
    }));
});

// Category performance data
const categoryPerformance = computed(() => {
    if (!props.categoryStats || props.categoryStats.length === 0) return [];

    const maxCount = Math.max(...props.categoryStats.map(c => c.properties_count));
    return props.categoryStats.map(category => ({
        ...category,
        performance: Math.round((category.properties_count / maxCount) * 100)
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
        <DialogContent class="max-w-7xl max-h-[90vh] overflow-hidden flex flex-col">
            <DialogHeader class="flex-shrink-0 pb-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <DialogTitle class="flex items-center gap-2 text-xl sm:text-2xl font-bold">
                        <div class="p-2 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600">
                            <Icon name="barChart3" class="h-5 w-5 sm:h-6 sm:w-6 text-white" />
                        </div>
                        <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            Analytics & Performance
                        </span>
                    </DialogTitle>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" @click="refreshData" :disabled="loading" class="transition-all hover:scale-105">
                            <Icon name="refreshCw" :class="loading ? 'h-4 w-4 sm:mr-2 animate-spin' : 'h-4 w-4 sm:mr-2'" />
                            <span class="hidden sm:inline">Refresh</span>
                        </Button>
                        <Button variant="outline" size="sm" @click="exportReport" class="transition-all hover:scale-105">
                            <Icon name="download" class="h-4 w-4 sm:mr-2" />
                            <span class="hidden sm:inline">Export</span>
                        </Button>
                    </div>
                </div>
            </DialogHeader>

            <!-- Tab Navigation -->
            <div class="flex-shrink-0 border-b border-gray-200 dark:border-gray-700 -mx-6 px-6">
                <nav class="flex space-x-4 sm:space-x-8 overflow-x-auto scrollbar-hide pb-px">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'flex items-center gap-2 px-2 sm:px-4 py-3 sm:py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-all duration-200',
                            activeTab === tab.id
                                ? 'border-primary text-primary scale-105'
                                : 'border-transparent text-muted-foreground hover:text-foreground hover:border-gray-300 hover:scale-102'
                        ]"
                    >
                        <Icon :name="tab.icon" class="h-4 w-4 flex-shrink-0" />
                        <span>{{ tab.label }}</span>
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 -mx-6 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'" class="space-y-6 animate-in fade-in duration-500">
                    <!-- Key Metrics Grid -->
                    <div v-if="financialMetrics.length > 0" class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                        <Card 
                            v-for="(metric, index) in financialMetrics" 
                            :key="metric.title" 
                            class="group hover:shadow-xl transition-all duration-300 hover:-translate-y-1 overflow-hidden border-l-4"
                            :class="`border-l-${metric.color.split('-')[1]}-500`"
                        >
                            <div class="absolute top-0 right-0 w-24 h-24 opacity-5 bg-gradient-to-bl" :class="`from-${metric.color.split('-')[1]}-500 to-transparent`"></div>
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2 relative">
                                <CardTitle class="text-xs sm:text-sm font-medium text-muted-foreground">{{ metric.title }}</CardTitle>
                                <div class="rounded-lg p-2 transition-transform group-hover:scale-110 group-hover:rotate-6" :class="`bg-${metric.color.split('-')[1]}-500/10`">
                                    <Icon name="dollarSign" :class="`h-4 w-4 ${metric.color}`" />
                                </div>
                            </CardHeader>
                            <CardContent class="relative">
                                <div class="text-2xl sm:text-3xl font-bold mb-1" :class="metric.color">{{ metric.value }}</div>
                                <p class="text-xs text-muted-foreground mb-3">{{ metric.description }}</p>
                                <div class="flex items-center gap-1 pt-3 border-t">
                                    <Icon
                                        :name="getTrendIcon(metric.trendUp)"
                                        :class="`h-3 w-3 ${getTrendColor(metric.trendUp)}`"
                                    />
                                    <span :class="`text-xs font-semibold ${getTrendColor(metric.trendUp)}`">
                                        {{ metric.trend }}
                                    </span>
                                    <span class="text-xs text-muted-foreground ml-1">vs last month</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    <div v-else class="text-center py-16 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-xl border-2 border-dashed">
                        <Icon name="barChart3" class="h-16 w-16 mx-auto text-muted-foreground opacity-30 mb-4" />
                        <p class="text-lg font-semibold text-muted-foreground">No Financial Data Available</p>
                        <p class="text-sm text-muted-foreground mt-2">Metrics will appear when data is available</p>
                    </div>

                    <!-- Performance Charts -->
                    <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
                        <!-- Property Performance Chart -->
                        <Card class="hover:shadow-xl transition-shadow duration-300 overflow-hidden">
                            <CardHeader class="pb-3 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-950 dark:to-cyan-950">
                                <CardTitle class="flex items-center gap-2 text-base sm:text-lg">
                                    <div class="p-1.5 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-600 shadow-md">
                                        <Icon name="trendingUp" class="h-4 w-4 sm:h-5 sm:w-5 text-white" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span>Property Performance</span>
                                        <span class="text-xs font-normal text-muted-foreground">Last 6 months trend</span>
                                    </div>
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="pt-6">
                                <div v-if="chartData.length > 0" class="space-y-3">
                                    <div v-for="(item, index) in chartData" :key="item.month"
                                         class="group flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-lg border-2 border-transparent hover:border-blue-200 dark:hover:border-blue-800 hover:bg-gradient-to-r hover:from-blue-50 hover:to-cyan-50 dark:hover:from-blue-950 dark:hover:to-cyan-950 transition-all duration-300 cursor-pointer hover:scale-102">
                                        <div class="flex items-center gap-3 mb-2 sm:mb-0">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 text-white font-bold text-xs shadow-md group-hover:scale-110 transition-transform">
                                                {{ index + 1 }}
                                            </div>
                                            <div>
                                                <span class="text-sm font-semibold group-hover:text-primary transition-colors block">{{ item.month }} {{ item.year }}</span>
                                                <div class="flex items-center gap-1.5 mt-1">
                                                    <Icon name="home" class="h-3 w-3 text-muted-foreground" />
                                                    <span class="text-xs text-muted-foreground">{{ item.properties }} properties</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="pl-11 sm:pl-0">
                                            <span class="text-base sm:text-lg font-bold text-green-600 dark:text-green-400">{{ formatCurrency(item.revenue) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <Icon name="trendingUp" class="h-12 w-12 mx-auto text-muted-foreground opacity-30 mb-3" />
                                    <p class="text-sm font-medium text-muted-foreground">No Performance Data</p>
                                    <p class="text-xs text-muted-foreground mt-1">Data will appear when available</p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Top Locations -->
                        <Card class="hover:shadow-xl transition-shadow duration-300 overflow-hidden">
                            <CardHeader class="pb-3 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-950 dark:to-pink-950">
                                <CardTitle class="flex items-center gap-2 text-base sm:text-lg">
                                    <div class="p-1.5 rounded-lg bg-gradient-to-br from-purple-500 to-pink-600 shadow-md">
                                        <Icon name="mapPin" class="h-4 w-4 sm:h-5 sm:w-5 text-white" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span>Top Locations</span>
                                        <span class="text-xs font-normal text-muted-foreground">Best performing areas</span>
                                    </div>
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="pt-6">
                                <div v-if="locationPerformance.length > 0" class="space-y-3">
                                    <div v-for="(location, index) in locationPerformance.slice(0, 5)" :key="location.name"
                                         class="group flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-lg border-2 border-transparent hover:border-purple-200 dark:hover:border-purple-800 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 dark:hover:from-purple-950 dark:hover:to-pink-950 transition-all duration-300 cursor-pointer hover:scale-102">
                                        <div class="flex items-center gap-3 mb-2 sm:mb-0">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 text-white font-bold text-xs shadow-md group-hover:scale-110 transition-transform">
                                                {{ location.rank }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-semibold group-hover:text-primary transition-colors truncate">{{ location.name }}</p>
                                                <p class="text-xs text-muted-foreground uppercase tracking-wide">{{ location.code }}</p>
                                            </div>
                                        </div>
                                        <div class="pl-11 sm:pl-0 sm:text-right">
                                            <p class="font-semibold text-sm">{{ location.properties_count }} properties</p>
                                            <p class="text-sm text-green-600 dark:text-green-400 font-semibold">{{ formatCurrency(location.avg_price) }} avg</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <Icon name="mapPin" class="h-12 w-12 mx-auto text-muted-foreground opacity-30 mb-3" />
                                    <p class="text-sm font-medium text-muted-foreground">No Location Data</p>
                                    <p class="text-xs text-muted-foreground mt-1">Data will appear when available</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Properties Tab -->
                <div v-if="activeTab === 'properties'" class="space-y-6 animate-in fade-in duration-500">
                    <div v-if="propertyMetrics.length > 0" class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                        <Card 
                            v-for="(metric, index) in propertyMetrics" 
                            :key="metric.title" 
                            class="group hover:shadow-xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-white to-blue-50 dark:from-gray-900 dark:to-blue-950 border-l-4 border-l-blue-500"
                        >
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-xs sm:text-sm font-medium text-muted-foreground">{{ metric.title }}</CardTitle>
                                <div class="rounded-lg p-2 bg-gradient-to-br from-blue-500 to-cyan-600 group-hover:scale-110 transition-transform shadow-md">
                                    <Icon name="home" class="h-4 w-4 text-white" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl sm:text-3xl font-bold text-blue-600 dark:text-blue-400 mb-1">{{ metric.value }}</div>
                                <p class="text-xs text-muted-foreground mb-3">{{ metric.description }}</p>
                                <div class="flex items-center gap-1 pt-3 border-t border-blue-100 dark:border-blue-900">
                                    <Icon
                                        :name="getTrendIcon(metric.trendUp)"
                                        :class="`h-3 w-3 ${getTrendColor(metric.trendUp)}`"
                                    />
                                    <span :class="`text-xs font-semibold ${getTrendColor(metric.trendUp)}`">
                                        {{ metric.trend }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    <div v-else class="text-center py-16 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-xl border-2 border-dashed">
                        <Icon name="home" class="h-16 w-16 mx-auto text-muted-foreground opacity-30 mb-4" />
                        <p class="text-lg font-semibold text-muted-foreground">No Property Data Available</p>
                        <p class="text-sm text-muted-foreground mt-2">Property metrics will appear when data is available</p>
                    </div>

                    <!-- Category Performance -->
                    <Card v-if="categoryPerformance.length > 0" class="hover:shadow-xl transition-shadow duration-300 overflow-hidden">
                        <CardHeader class="pb-3 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950">
                            <CardTitle class="flex items-center gap-2 text-base sm:text-lg">
                                <div class="p-1.5 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 shadow-md">
                                    <Icon name="tag" class="h-4 w-4 sm:h-5 sm:w-5 text-white" />
                                </div>
                                <div class="flex flex-col">
                                    <span>Category Performance</span>
                                    <span class="text-xs font-normal text-muted-foreground">Performance by property type</span>
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="pt-6">
                            <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                                <div v-for="(category, index) in categoryPerformance" :key="category.name"
                                     class="group rounded-xl border-2 p-5 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-white to-green-50 dark:from-gray-900 dark:to-green-950 hover:border-green-300 dark:hover:border-green-700">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-2 h-2 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 animate-pulse"></div>
                                        <h4 class="font-bold text-base group-hover:text-primary transition-colors">{{ category.name }}</h4>
                                    </div>
                                    <p class="text-xs sm:text-sm text-muted-foreground mb-4 line-clamp-2 min-h-[2.5rem]">{{ category.description }}</p>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center text-sm p-2.5 rounded-lg bg-white/50 dark:bg-gray-800/50">
                                            <span class="text-muted-foreground flex items-center gap-1.5">
                                                <Icon name="home" class="h-3.5 w-3.5" />
                                                Properties
                                            </span>
                                            <span class="font-bold">{{ category.properties_count }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm p-2.5 rounded-lg bg-green-50/50 dark:bg-green-950/50">
                                            <span class="text-muted-foreground flex items-center gap-1.5">
                                                <Icon name="dollarSign" class="h-3.5 w-3.5" />
                                                Total Revenue
                                            </span>
                                            <span class="font-bold text-green-600 dark:text-green-400">{{ formatCurrency(category.total_revenue) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm p-2.5 rounded-lg bg-blue-50/50 dark:bg-blue-950/50">
                                            <span class="text-muted-foreground flex items-center gap-1.5">
                                                <Icon name="trendingUp" class="h-3.5 w-3.5" />
                                                Avg Price
                                            </span>
                                            <span class="font-bold">{{ formatCurrency(category.avg_price) }}</span>
                                        </div>
                                        <div class="mt-4 pt-4 border-t">
                                            <div class="flex justify-between text-xs font-medium text-muted-foreground mb-2">
                                                <span>Performance Score</span>
                                                <span class="text-primary">{{ category.performance }}%</span>
                                            </div>
                                            <Progress :value="category.performance" class="h-2.5" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Rentals Tab -->
                <div v-if="activeTab === 'rentals'" class="space-y-6 animate-in fade-in duration-500">
                    <div v-if="rentalMetrics.length > 0" class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                        <Card 
                            v-for="(metric, index) in rentalMetrics" 
                            :key="metric.title" 
                            class="group hover:shadow-xl transition-all duration-300 hover:-translate-y-1 bg-gradient-to-br from-white to-green-50 dark:from-gray-900 dark:to-green-950 border-l-4 border-l-green-500"
                        >
                            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle class="text-xs sm:text-sm font-medium text-muted-foreground">{{ metric.title }}</CardTitle>
                                <div class="rounded-lg p-2 bg-gradient-to-br from-green-500 to-emerald-600 group-hover:scale-110 transition-transform shadow-md">
                                    <Icon name="fileText" class="h-4 w-4 text-white" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="text-2xl sm:text-3xl font-bold text-green-600 dark:text-green-400 mb-1">{{ metric.value }}</div>
                                <p class="text-xs text-muted-foreground mb-3">{{ metric.description }}</p>
                                <div class="flex items-center gap-1 pt-3 border-t border-green-100 dark:border-green-900">
                                    <Icon
                                        :name="getTrendIcon(metric.trendUp)"
                                        :class="`h-3 w-3 ${getTrendColor(metric.trendUp)}`"
                                    />
                                    <span :class="`text-xs font-semibold ${getTrendColor(metric.trendUp)}`">
                                        {{ metric.trend }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                    <div v-else class="text-center py-16 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-xl border-2 border-dashed">
                        <Icon name="fileText" class="h-16 w-16 mx-auto text-muted-foreground opacity-30 mb-4" />
                        <p class="text-lg font-semibold text-muted-foreground">No Rental Data Available</p>
                        <p class="text-sm text-muted-foreground mt-2">Rental metrics will appear when data is available</p>
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
