<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Icon from '@/components/Icon.vue';
import { computed, ref } from 'vue';
import { formatRelativeTime } from '@/utils/formatters';

interface Inquiry {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    subject: string;
    message: string;
    status: 'pending' | 'in_progress' | 'resolved' | 'closed';
    created_at: string;
    ip_address?: string;
}

interface Props {
    inquiries: {
        data: Inquiry[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total: number;
        pending: number;
        in_progress: number;
        resolved: number;
        closed: number;
    };
    recentCount: number;
    filters: {
        status: string;
        subject: string;
        search: string;
        per_page: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Guest Inquiries', href: '/admin/guest-inquiries' },
];

// Search and filter state
const searchQuery = ref(props.filters.search);
const statusFilter = ref(props.filters.status);
const subjectFilter = ref(props.filters.subject);
const perPage = ref(props.filters.per_page);

// Apply filters
const applyFilters = () => {
    router.get('/admin/guest-inquiries', {
        search: searchQuery.value,
        status: statusFilter.value,
        subject: subjectFilter.value,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Reset filters
const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    subjectFilter.value = 'all';
    perPage.value = 15;
    applyFilters();
};

// Status badge variant
const getStatusVariant = (status: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    const variants: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        pending: 'default',
        in_progress: 'secondary',
        resolved: 'default',
        closed: 'outline',
    };
    return variants[status] || 'default';
};

// Subject badge color
const getSubjectColor = (subject: string) => {
    const colors: Record<string, string> = {
        rental: 'bg-blue-100 text-blue-800',
        lease: 'bg-purple-100 text-purple-800',
        general: 'bg-gray-100 text-gray-800',
        support: 'bg-orange-100 text-orange-800',
    };
    return colors[subject] || colors.general;
};

// Format status text
const formatStatus = (status: string) => {
    return status.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
};

// Format subject text
const formatSubject = (subject: string) => {
    return subject.charAt(0).toUpperCase() + subject.slice(1);
};

// Stats cards
const statsCards = computed(() => [
    {
        title: 'Total Inquiries',
        value: props.stats.total,
        icon: 'mail',
        color: 'bg-blue-500',
        description: 'All time',
    },
    {
        title: 'Pending',
        value: props.stats.pending,
        icon: 'clock',
        color: 'bg-yellow-500',
        description: 'Awaiting response',
    },
    {
        title: 'In Progress',
        value: props.stats.in_progress,
        icon: 'loader',
        color: 'bg-purple-500',
        description: 'Being handled',
    },
    {
        title: 'Resolved',
        value: props.stats.resolved,
        icon: 'check-circle',
        color: 'bg-green-500',
        description: 'Completed',
    },
]);

// Delete inquiry
const deleteInquiry = (id: number) => {
    if (confirm('Are you sure you want to delete this inquiry?')) {
        router.delete(`/admin/guest-inquiries/${id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Guest Inquiries" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Guest Inquiries</h1>
                    <p class="text-muted-foreground mt-1">
                        Manage and respond to guest inquiries
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge variant="outline" class="text-sm">
                        {{ recentCount }} new (7 days)
                    </Badge>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in statsCards" :key="stat.title">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium">
                            {{ stat.title }}
                        </CardTitle>
                        <div :class="[stat.color, 'rounded-full p-2']">
                            <Icon :name="stat.icon" class="h-4 w-4 text-white" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stat.value }}</div>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ stat.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                    <CardDescription>Filter inquiries by status, subject, or search</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Search</label>
                            <Input
                                v-model="searchQuery"
                                placeholder="Search name or email..."
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Status</label>
                            <Select v-model="statusFilter" @update:model-value="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="in_progress">In Progress</SelectItem>
                                    <SelectItem value="resolved">Resolved</SelectItem>
                                    <SelectItem value="closed">Closed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Subject</label>
                            <Select v-model="subjectFilter" @update:model-value="applyFilters">
                                <SelectTrigger>
                                    <SelectValue placeholder="All subjects" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Subjects</SelectItem>
                                    <SelectItem value="rental">Rental</SelectItem>
                                    <SelectItem value="lease">Lease</SelectItem>
                                    <SelectItem value="general">General</SelectItem>
                                    <SelectItem value="support">Support</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2 flex items-end">
                            <div class="flex gap-2 w-full">
                                <Button @click="applyFilters" class="flex-1">
                                    <Icon name="search" class="mr-2 h-4 w-4" />
                                    Apply
                                </Button>
                                <Button @click="resetFilters" variant="outline">
                                    <Icon name="x" class="mr-2 h-4 w-4" />
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Inquiries Table -->
            <Card>
                <CardHeader>
                    <div class="flex justify-between items-center">
                        <div>
                            <CardTitle>Inquiries</CardTitle>
                            <CardDescription>
                                Showing {{ inquiries.data.length }} of {{ inquiries.total }} inquiries
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="inquiries.data.length === 0" class="text-center py-12">
                        <Icon name="inbox" class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                        <h3 class="text-lg font-semibold mb-2">No inquiries found</h3>
                        <p class="text-muted-foreground">
                            {{ filters.search || filters.status !== 'all' || filters.subject !== 'all' 
                                ? 'Try adjusting your filters' 
                                : 'Guest inquiries will appear here' }}
                        </p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="inquiry in inquiries.data"
                            :key="inquiry.id"
                            class="border rounded-lg p-4 hover:bg-accent/50 transition-colors"
                        >
                            <div class="flex flex-col sm:flex-row justify-between gap-4">
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <Link
                                                    :href="`/admin/guest-inquiries/${inquiry.id}`"
                                                    class="font-semibold hover:underline"
                                                >
                                                    {{ inquiry.first_name }} {{ inquiry.last_name }}
                                                </Link>
                                                <Badge :class="getSubjectColor(inquiry.subject)" variant="outline">
                                                    {{ formatSubject(inquiry.subject) }}
                                                </Badge>
                                                <Badge :variant="getStatusVariant(inquiry.status)">
                                                    {{ formatStatus(inquiry.status) }}
                                                </Badge>
                                            </div>
                                            <p class="text-sm text-muted-foreground mt-1">
                                                {{ inquiry.email }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="text-sm line-clamp-2">
                                        {{ inquiry.message }}
                                    </p>
                                    <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <Icon name="clock" class="h-3 w-3" />
                                            {{ formatRelativeTime(inquiry.created_at) }}
                                        </span>
                                        <span v-if="inquiry.ip_address" class="flex items-center gap-1">
                                            <Icon name="globe" class="h-3 w-3" />
                                            {{ inquiry.ip_address }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex sm:flex-col gap-2">
                                    <Button
                                        as="a"
                                        :href="`/admin/guest-inquiries/${inquiry.id}`"
                                        size="sm"
                                        variant="outline"
                                        class="flex-1 sm:flex-initial"
                                    >
                                        <Icon name="eye" class="mr-2 h-4 w-4" />
                                        View
                                    </Button>
                                    <Button
                                        @click="deleteInquiry(inquiry.id)"
                                        size="sm"
                                        variant="destructive"
                                        class="flex-1 sm:flex-initial"
                                    >
                                        <Icon name="trash" class="mr-2 h-4 w-4" />
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="inquiries.last_page > 1" class="mt-6 flex justify-center gap-2">
                        <Button
                            v-for="page in inquiries.last_page"
                            :key="page"
                            :variant="page === inquiries.current_page ? 'default' : 'outline'"
                            size="sm"
                            @click="router.get(`/admin/guest-inquiries?page=${page}&status=${statusFilter}&subject=${subjectFilter}&search=${searchQuery}`)"
                        >
                            {{ page }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
