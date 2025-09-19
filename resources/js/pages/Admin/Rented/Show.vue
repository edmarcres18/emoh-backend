<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Client {
    id: number;
    name: string;
    email: string;
}

interface Property {
    id: number;
    property_name: string;
    estimated_monthly: string;
    category: { name: string };
    location: { name: string };
}

interface Rented {
    id: number;
    client_id: number;
    property_id: number;
    monthly_rent: string;
    security_deposit: string;
    start_date: string;
    end_date: string;
    status: string;
    terms_conditions: string;
    notes: string;
    contract_signed_at: string;
    created_at: string;
    updated_at: string;
    remarks: string;
    client: Client;
    property: Property;
}

interface Props {
    rented: Rented;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rental Management',
        href: '/admin/rented',
    },
    {
        title: 'Rental Details',
        href: `/admin/rented/${props.rented.id}`,
    },
];

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

const formatDate = (dateString: string) => {
    if (!dateString) return 'Not set';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatCurrency = (value: string) => {
    if (!value) return '₱0.00';
    const num = parseFloat(value);
    return isNaN(num) ? '₱0.00' : `₱${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const rentalDuration = computed(() => {
    if (!props.rented.end_date) return 'Open-ended';

    const start = new Date(props.rented.start_date);
    const end = new Date(props.rented.end_date);
    const diffTime = Math.abs(end.getTime() - start.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    const diffMonths = Math.floor(diffDays / 30);

    if (diffMonths > 0) {
        return `${diffMonths} month${diffMonths > 1 ? 's' : ''}`;
    }
    return `${diffDays} day${diffDays > 1 ? 's' : ''}`;
});

const isActiveRental = computed(() => props.rented.status === 'active');
const isExpiredRental = computed(() => {
    if (props.rented.status === 'expired') return true;
    if (!props.rented.end_date) return false;
    return new Date(props.rented.end_date) < new Date();
});

// Renew state
const showRenewModal = ref(false);
const renewForm = ref<{ end_date: string; remarks: string }>({ end_date: '', remarks: '' });
const isRenewing = ref(false);

const openRenewModal = () => {
    const current = props.rented.end_date ? new Date(props.rented.end_date) : new Date();
    const suggested = new Date(current);
    suggested.setMonth(suggested.getMonth() + 1);
    renewForm.value.end_date = suggested.toISOString().slice(0, 10);
    renewForm.value.remarks = '';
    showRenewModal.value = true;
};

const closeRenewModal = () => {
    if (!isRenewing.value) {
        showRenewModal.value = false;
        renewForm.value = { end_date: '', remarks: '' };
    }
};

const submitRenew = () => {
    if (isRenewing.value) return;
    isRenewing.value = true;
    router.post(`/admin/rented/${props.rented.id}/renew`, {
        end_date: renewForm.value.end_date,
        remarks: renewForm.value.remarks || null,
    }, {
        preserveScroll: true,
        onSuccess: () => closeRenewModal(),
        onFinish: () => { isRenewing.value = false; }
    });
};

// End (not renew) state
const showEndModal = ref(false);
const endReason = ref<string>('');
const isEnding = ref(false);

const openEndModal = () => {
    endReason.value = '';
    showEndModal.value = true;
};

const closeEndModal = () => {
    if (!isEnding.value) {
        showEndModal.value = false;
        endReason.value = '';
    }
};

const confirmEnd = () => {
    if (isEnding.value) return;
    isEnding.value = true;
    router.post(`/admin/rented/${props.rented.id}/end`, {
        reason: endReason.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => closeEndModal(),
        onFinish: () => { isEnding.value = false; }
    });
};
</script>

<template>
    <Head title="Rental Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Rental Details</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        View complete rental information and contract details
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="rented.status !== 'ended'"
                        :href="`/admin/rented/${rented.id}/edit`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Rental
                    </Link>
                    <button
                        v-if="rented.status === 'active' || rented.status === 'expired'"
                        @click="openRenewModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3M9 12a7 7 0 117 7" />
                        </svg>
                        Renew
                    </button>
                    <button
                        v-if="rented.status === 'active'"
                        @click="openEndModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Not Renew
                    </button>
                    <Link
                        href="/admin/rented"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors duration-200"
                    >
                        Back to List
                    </Link>
                </div>
            </div>

            <!-- Status Alert -->
            <div v-if="isExpiredRental && rented.status !== 'expired'" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-red-900 dark:text-red-100">Rental Contract Expired</h3>
                        <p class="text-sm text-red-800 dark:text-red-200 mt-1">
                            This rental contract has passed its end date but status hasn't been updated.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Rental Overview -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rental Overview</h2>
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    getStatusColor(rented.status)
                                ]">
                                    {{ rented.status.charAt(0).toUpperCase() + rented.status.slice(1) }}
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rental ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">#{{ rented.id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ rentalDuration }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(rented.start_date) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">End Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(rented.end_date) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Contract Signed</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(rented.contract_signed_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(rented.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(rented.updated_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Remarks</dt>
                                    <dd class="mt-1">
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            getRemarksColor(rented.remarks)
                                        ]">
                                            {{ rented.remarks }}
                                        </span>
                                    </dd>
                                </div>
                                <div v-if="isExpiredRental">
                                    <dt class="text-sm font-medium text-red-500 dark:text-red-400">Days Overdue</dt>
                                    <dd class="mt-1 text-sm text-red-600 dark:text-red-400 font-semibold">
                                        {{ Math.ceil((new Date().getTime() - new Date(rented.end_date).getTime()) / (1000 * 60 * 60 * 24)) }} days
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Financial Details -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Financial Details</h2>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Rent</dt>
                                    <dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(rented.monthly_rent) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Security Deposit</dt>
                                    <dd class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(rented.security_deposit) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Paid</dt>
                                    <dd class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                                        {{ formatCurrency((parseFloat(rented.monthly_rent) + parseFloat(rented.security_deposit || '0')).toString()) }}
                                    </dd>
                                </div>
                            </dl>

                            <!-- Payment Summary -->
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Payment Summary</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600 dark:text-gray-400">Monthly Payment:</span>
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(rented.monthly_rent) }}</span>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600 dark:text-gray-400">Security Deposit:</span>
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(rented.security_deposit) }}</span>
                                        </div>
                                    </div>
                                    <div v-if="rentalDuration !== 'Open-ended'" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-blue-600 dark:text-blue-400">Total Contract Value:</span>
                                            <span class="font-semibold text-blue-900 dark:text-blue-100">
                                                {{ formatCurrency((parseFloat(rented.monthly_rent) * Math.ceil(Math.abs(new Date(rented.end_date).getTime() - new Date(rented.start_date).getTime()) / (1000 * 60 * 60 * 24 * 30))).toString()) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Notes -->
                    <div v-if="rented.terms_conditions || rented.notes" class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Additional Information</h2>
                        </div>
                        <div class="p-6 space-y-6">
                            <div v-if="rented.terms_conditions">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Terms and Conditions</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap bg-gray-50 dark:bg-gray-800 rounded-lg p-4">{{ rented.terms_conditions }}</dd>
                            </div>
                            <div v-if="rented.notes">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Notes</dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap bg-gray-50 dark:bg-gray-800 rounded-lg p-4">{{ rented.notes }}</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Client Information -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Client Information</h3>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-900/20 rounded-full flex items-center justify-center">
                                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ rented.client.name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ rented.client.email }}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link
                                    :href="`/admin/clients/${rented.client.id}`"
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-medium"
                                >
                                    View Client Profile →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Property Information -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Property Information</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ rented.property.property_name }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ rented.property.location.name }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Category:</span>
                                        <span class="ml-1 text-gray-900 dark:text-gray-100">{{ rented.property.category.name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Estimated:</span>
                                        <span class="ml-1 text-gray-900 dark:text-gray-100">{{ formatCurrency(rented.property.estimated_monthly) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link
                                    :href="`/admin/properties/${rented.property.id}`"
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-medium"
                                >
                                    View Property Details →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Quick Actions</h2>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-wrap gap-3">
                                <Link
                                    v-if="rented.status !== 'ended'"
                                    :href="`/admin/rented/${rented.id}/edit`"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit Rental
                                </Link>

                                <button
                                    v-if="rented.status === 'active'"
                                    class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Mark as Expired
                                </button>

                                <button
                                    v-if="rented.status !== 'terminated'"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Terminate Contract
                                </button>

                                <button
                                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Generate Contract
                                </button>

                                <button
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Send Reminder
                                </button>

                                <Link
                                    href="/admin/rented"
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Back to List
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Rental History & Activity Log -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Activity Timeline</h2>
                        </div>
                        <div class="p-6">
                            <div class="flow-root">
                                <ul role="list" class="-mb-8">
                                    <li>
                                        <div class="relative pb-8">
                                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                                        <DocumentTextIcon class="h-4 w-4 text-white" aria-hidden="true" />
                                                    </span>
                                                </div>
                                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                    <div>
                                                        <p class="text-sm text-gray-900 dark:text-gray-100">Contract signed and rental created</p>
                                                    </div>
                                                    <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                                        <time :datetime="rented.contract_signed_at">{{ formatDate(rented.contract_signed_at) }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="relative pb-8">
                                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                                        <PlayIcon class="h-4 w-4 text-white" aria-hidden="true" />
                                                    </span>
                                                </div>
                                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                    <div>
                                                        <p class="text-sm text-gray-900 dark:text-gray-100">Rental period started</p>
                                                    </div>
                                                    <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                                        <time :datetime="rented.start_date">{{ formatDate(rented.start_date) }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li v-if="rented.updated_at !== rented.created_at">
                                        <div class="relative pb-8">
                                            <span v-if="!isExpiredRental" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-yellow-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                                        <PencilIcon class="h-4 w-4 text-white" aria-hidden="true" />
                                                    </span>
                                                </div>
                                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                    <div>
                                                        <p class="text-sm text-gray-900 dark:text-gray-100">Rental details updated</p>
                                                    </div>
                                                    <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                                        <time :datetime="rented.updated_at">{{ formatDate(rented.updated_at) }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li v-if="isExpiredRental">
                                        <div class="relative">
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-red-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-900">
                                                        <ExclamationTriangleIcon class="h-4 w-4 text-white" aria-hidden="true" />
                                                    </span>
                                                </div>
                                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                    <div>
                                                        <p class="text-sm text-red-600 dark:text-red-400 font-medium">Contract expired</p>
                                                        <p class="text-xs text-red-500 dark:text-red-400">{{ Math.ceil((new Date().getTime() - new Date(rented.end_date).getTime()) / (1000 * 60 * 60 * 24)) }} days overdue</p>
                                                    </div>
                                                    <div class="whitespace-nowrap text-right text-sm text-red-500 dark:text-red-400">
                                                        <time :datetime="rented.end_date">{{ formatDate(rented.end_date) }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Renew Modal -->
    <div v-if="showRenewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="closeRenewModal"></div>
        <div class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-lg shadow-xl ring-1 ring-gray-200 dark:ring-gray-800 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Renew Rental #{{ rented.id }}</h3>
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
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Mark as Not Renewed #{{ rented.id }}</h3>
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
</template>
