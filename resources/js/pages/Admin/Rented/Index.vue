<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { debounce } from 'lodash';
import DeleteModal from '@/components/DeleteModal.vue';
import { useAuth } from '@/composables/useAuth';

interface Client {
    id: number;
    name: string;
    email: string;
}

interface Property {
    id: number;
    property_name: string;
    category: { name: string };
    location: { name: string };
}

interface Rented {
    id: number;
    client: Client;
    property: Property;
    monthly_rent: string;
    security_deposit: string;
    start_date: string;
    end_date: string;
    status: 'active' | 'expired' | 'terminated' | 'pending' | 'ended';
    created_at: string;
    formatted_monthly_rent: string;
    remaining_days: number | null;
    remarks: string;
}

interface PaginatedRented {
    data: Rented[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    rented: PaginatedRented;
    clients: Client[];
    properties: Property[];
    stats: {
        total: number;
        active: number;
        pending: number;
        expired: number;
        terminated: number;
    };
    filters: {
        search?: string;
        status?: string;
        sort_by?: string;
        sort_order?: string;
    };
}

const props = defineProps<Props>();

const {
    canViewCategories,
    canCreateCategories,
    canEditCategories,
    canDeleteCategories,
    fetchUser
} = useAuth();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rental Management',
        href: '/admin/rented',
    },
];

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const sortBy = ref(props.filters.sort_by || 'created_at');
const sortOrder = ref(props.filters.sort_order || 'desc');

const debouncedSearch = debounce(() => {
    router.get('/admin/rented', {
        search: searchQuery.value,
        status: statusFilter.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([searchQuery, statusFilter, sortBy, sortOrder], () => {
    debouncedSearch();
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStatusColor = (status: 'active' | 'pending' | 'expired' | 'terminated' | 'ended' | string) => {
    const colors = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        expired: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
        terminated: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
        ended: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
    } as const;
    return colors[status as keyof typeof colors] || colors.pending;
};

const getRemarksColor = (remarks: string) => {
    if (remarks.includes('Over Due')) {
        return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
    } else if (remarks.includes('Due Date Today')) {
        return 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400';
    } else if (remarks.includes('Almost Due Date')) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400';
    } else if (remarks.includes('Due Soon')) {
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400';
    } else {
        return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
    }
};

const showDeleteModal = ref(false);
const rentedToDelete = ref<Rented | null>(null);
const isDeleting = ref(false);

const openDeleteModal = (rented: Rented) => {
    rentedToDelete.value = rented;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        showDeleteModal.value = false;
        rentedToDelete.value = null;
    }
};

const confirmDelete = () => {
    if (rentedToDelete.value && !isDeleting.value) {
        isDeleting.value = true;
        router.delete(`/admin/rented/${rentedToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                rentedToDelete.value = null;
            },
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    }
};

onMounted(() => {
    fetchUser();
});

// Renew modal state
const showRenewModal = ref(false);
const renewTarget = ref<Rented | null>(null);
const renewForm = ref<{ end_date: string; remarks: string }>({ end_date: '', remarks: '' });
const isRenewing = ref(false);

const openRenewModal = (rental: Rented) => {
    renewTarget.value = rental;
    // Pre-fill with current end_date or today + 1 month
    const current = rental.end_date ? new Date(rental.end_date) : new Date();
    const suggested = new Date(current);
    suggested.setMonth(suggested.getMonth() + 1);
    renewForm.value.end_date = suggested.toISOString().slice(0, 10);
    renewForm.value.remarks = '';
    showRenewModal.value = true;
};

const closeRenewModal = () => {
    if (!isRenewing.value) {
        showRenewModal.value = false;
        renewTarget.value = null;
        renewForm.value = { end_date: '', remarks: '' };
    }
};

const submitRenew = () => {
    if (!renewTarget.value || isRenewing.value) return;
    isRenewing.value = true;
    router.post(`/admin/rented/${renewTarget.value.id}/renew`, {
        end_date: renewForm.value.end_date,
        remarks: renewForm.value.remarks || null,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeRenewModal();
            // Optionally refresh list data without losing UI state
            router.reload({ only: ['rented', 'stats'] });
        },
        onFinish: () => {
            isRenewing.value = false;
        }
    });
};

// Not renew (end) modal state
const showEndModal = ref(false);
const endTarget = ref<Rented | null>(null);
const endReason = ref<string>('');
const isEnding = ref(false);

const openEndModal = (rental: Rented) => {
    endTarget.value = rental;
    endReason.value = '';
    showEndModal.value = true;
};

const closeEndModal = () => {
    if (!isEnding.value) {
        showEndModal.value = false;
        endTarget.value = null;
        endReason.value = '';
    }
};

const confirmEnd = () => {
    if (!endTarget.value || isEnding.value) return;
    isEnding.value = true;
    router.post(`/admin/rented/${endTarget.value.id}/end`, {
        reason: endReason.value || null,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeEndModal();
            // Optionally refresh list data without losing UI state
            router.reload({ only: ['rented', 'stats'] });
        },
        onFinish: () => {
            isEnding.value = false;
        }
    });
};
</script>

<template>
    <Head title="Rental Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Rental Management</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage property rentals and tenant relationships</p>
                </div>
                <button
                    v-if="canCreateCategories"
                    @click="router.get('/admin/rented/create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Rental
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.active }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                            <svg class="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pending }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Expired</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.expired }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Terminated</p>
                            <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ stats.terminated }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                <div class="flex flex-1 gap-4">
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search rentals..."
                                class="block w-full pl-10 pr-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                            />
                        </div>
                    </div>
                    <div class="min-w-[140px]">
                        <select
                            v-model="statusFilter"
                            class="block w-full px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                        >
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="expired">Expired</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                    <div class="min-w-[160px]">
                        <select
                            v-model="sortBy"
                            class="block w-full px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                        >
                            <option value="created_at">Latest First</option>
                            <option value="start_date">Start Date</option>
                            <option value="monthly_rent">Monthly Rent</option>
                            <option value="client_name">Client Name</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-900 px-3 py-2 rounded-lg shadow-sm">
                        <span class="font-medium">{{ rented.from || 0 }}</span>-<span class="font-medium">{{ rented.to || 0 }}</span> of <span class="font-medium">{{ rented.total }}</span> rentals
                    </div>
                </div>
            </div>

            <!-- Rentals Table -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    ID
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Client
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Property
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Monthly Rent
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Security Deposit
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Start Date
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    End Date
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Remarks
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="rental in rented.data" :key="rental.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ rental.id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ rental.client.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ rental.client.email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ rental.property.property_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ rental.property.location.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">₱{{ parseFloat(rental.monthly_rent).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ rental.security_deposit ? '₱' + parseFloat(rental.security_deposit).toLocaleString('en-US', { minimumFractionDigits: 2 }) : 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize',
                                        getStatusColor(rental.status)
                                    ]">
                                        {{ rental.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ formatDate(rental.start_date) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ rental.end_date ? formatDate(rental.end_date) : 'Open-ended' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        getRemarksColor(rental.remarks)
                                    ]">
                                        {{ rental.remarks }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            v-if="canViewCategories"
                                            @click="router.get(`/admin/rented/${rental.id}`)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-md transition-colors duration-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </button>
                                        <button
                                            v-if="canEditCategories && rental.status !== 'ended'"
                                            @click="router.get(`/admin/rented/${rental.id}/edit`)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 rounded-md transition-colors duration-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button
                                            v-if="canEditCategories && (rental.status === 'active' || rental.status === 'expired')"
                                            @click="openRenewModal(rental)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-md transition-colors duration-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3M9 12a7 7 0 117 7" />
                                            </svg>
                                            Renew
                                        </button>
                                        <button
                                            v-if="canEditCategories && rental.status === 'active'"
                                            @click="openEndModal(rental)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md transition-colors duration-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Not Renew
                                        </button>
                                        <button
                                            v-if="canDeleteCategories"
                                            @click="openDeleteModal(rental)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md transition-colors duration-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Empty State -->
                            <tr v-if="rented.data.length === 0">
                                <td colspan="10" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                            {{ searchQuery ? 'No rentals found' : 'No rentals yet' }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ searchQuery ? 'Try adjusting your search terms.' : 'Get started by creating your first rental record.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="rented.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Showing {{ rented.from }} to {{ rented.to }} of {{ rented.total }} results
                        </div>
                        <div class="flex items-center gap-2">
                            <template v-for="link in rented.links" :key="link.label">
                                <button
                                    v-if="link.url"
                                    @click="router.get(link.url)"
                                    :class="[
                                        'px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    :class="[
                                        'px-3 py-2 text-sm font-medium rounded-md',
                                        'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            :show="showDeleteModal"
            :title="`Delete Rental #${rentedToDelete?.id}`"
            :message="`Are you sure you want to delete this rental record? This action cannot be undone and will permanently remove this rental from your system.`"
            :is-loading="isDeleting"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />

        <!-- Renew Modal -->
        <div v-if="showRenewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="closeRenewModal"></div>
            <div class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-lg shadow-xl ring-1 ring-gray-200 dark:ring-gray-800 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Renew Rental #{{ renewTarget?.id }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Set a new end date and optional remarks.</p>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">New End Date</label>
                        <input type="date" v-model="renewForm.end_date" class="mt-1 block w-full px-3 py-2 rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Remarks (optional)</label>
                        <textarea v-model="renewForm.remarks" rows="3" class="mt-1 block w-full px-3 py-2 rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Add notes for this renewal..."></textarea>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button @click="closeRenewModal" class="px-4 py-2 text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg">Cancel</button>
                    <button @click="submitRenew" :disabled="isRenewing" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg">
                        <svg v-if="isRenewing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Renew
                    </button>
                </div>
            </div>
        </div>

        <!-- Not Renew (End) Modal -->
        <div v-if="showEndModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="closeEndModal"></div>
            <div class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-lg shadow-xl ring-1 ring-gray-200 dark:ring-gray-800 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Mark as Not Renewed #{{ endTarget?.id }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This will end the rental and free the property for new rentals.</p>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason (optional)</label>
                    <textarea v-model="endReason" rows="3" class="mt-1 block w-full px-3 py-2 rounded-md bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Add an optional reason..."></textarea>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button @click="closeEndModal" class="px-4 py-2 text-sm font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg">Cancel</button>
                    <button @click="confirmEnd" :disabled="isEnding" class="inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-800 disabled:opacity-50 text-white text-sm font-medium rounded-lg">
                        <svg v-if="isEnding" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Mark as Not Renewed
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
