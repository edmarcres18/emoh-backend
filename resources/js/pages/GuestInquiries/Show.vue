<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DeleteModal from '@/components/DeleteModal.vue';

interface GuestInquiry {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    subject: 'rental' | 'lease' | 'general' | 'support';
    message: string;
    status: 'pending' | 'responded' | 'spam';
    captcha_verified: boolean;
    ip_address?: string;
    user_agent?: string;
    responded_at: string | null;
    created_at: string;
    updated_at: string;
    responder?: {
        id: number;
        name: string;
        email: string;
    };
}

interface Props {
    inquiry: GuestInquiry;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Guest Inquiries', href: '/guest-inquiries' },
    { title: `Inquiry #${props.inquiry.id}`, href: `/guest-inquiries/${props.inquiry.id}` },
];

const isDeleting = ref(false);
const showDeleteModal = ref(false);
const isUpdatingStatus = ref(false);

const goBack = () => router.get('/guest-inquiries');

const openDeleteModal = () => {
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    if (!isDeleting.value) showDeleteModal.value = false;
};

const confirmDelete = () => {
    if (!isDeleting.value) {
        isDeleting.value = true;
        router.delete(`/guest-inquiries/${props.inquiry.id}`, {
            onSuccess: () => { showDeleteModal.value = false; },
            onFinish: () => { isDeleting.value = false; },
        });
    }
};

const updateStatus = (newStatus: string) => {
    if (isUpdatingStatus.value) return;
    isUpdatingStatus.value = true;
    router.patch(`/guest-inquiries/${props.inquiry.id}/status`, { status: newStatus }, {
        preserveScroll: true,
        onFinish: () => { isUpdatingStatus.value = false; }
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDateRelative = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMs = now.getTime() - date.getTime();
    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24));
    
    if (diffInDays === 0) return 'Today';
    if (diffInDays === 1) return 'Yesterday';
    if (diffInDays < 7) return `${diffInDays} days ago`;
    if (diffInDays < 30) return `${Math.floor(diffInDays / 7)} weeks ago`;
    if (diffInDays < 365) return `${Math.floor(diffInDays / 30)} months ago`;
    return `${Math.floor(diffInDays / 365)} years ago`;
};

const formatSubject = (subject: string) => ({
    rental: 'Property Rental Inquiry',
    lease: 'Property Lease Inquiry',
    general: 'General Information',
    support: 'Customer Support'
}[subject] || subject);

const getStatusBadgeClass = (status: string) => ({
    pending: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800',
    responded: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 border-green-200 dark:border-green-800',
    spam: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 border-red-200 dark:border-red-800'
}[status] || 'bg-gray-100');
</script>

<template>
    <Head :title="`Inquiry #${inquiry.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="flex-shrink-0 h-12 w-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Inquiry #{{ inquiry.id }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">From {{ inquiry.first_name }} {{ inquiry.last_name }}</p>
                        </div>
                    </div>
                </div>
                <button @click="goBack" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Inquiries
                </button>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Inquiry Details Card -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Information -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Contact Information</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">First Name</label>
                                    <div class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ inquiry.first_name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Last Name</label>
                                    <div class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ inquiry.last_name }}</div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Email Address</label>
                                <a :href="`mailto:${inquiry.email}`" class="text-base font-medium text-indigo-600 dark:text-indigo-400 hover:underline">{{ inquiry.email }}</a>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Subject</label>
                                <div class="text-base text-gray-900 dark:text-gray-100">{{ formatSubject(inquiry.subject) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Content -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Message</h2>
                        </div>
                        <div class="p-6">
                            <div class="prose dark:prose-invert max-w-none">
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ inquiry.message }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Inquiry Details</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Submitted</label>
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ formatDate(inquiry.created_at) }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatDateRelative(inquiry.created_at) }}</div>
                                </div>
                                <div v-if="inquiry.responded_at">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Responded</label>
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ formatDate(inquiry.responded_at) }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatDateRelative(inquiry.responded_at) }}</div>
                                </div>
                            </div>
                            <div v-if="inquiry.responder" class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Responded By</label>
                                <div class="flex items-center gap-2">
                                    <div class="h-8 w-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ inquiry.responder.name.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ inquiry.responder.name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ inquiry.responder.email }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                <svg v-if="inquiry.captcha_verified" class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">CAPTCHA Verified</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions & Status Sidebar -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Status Management</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Current Status</label>
                                <div :class="['inline-flex items-center gap-2 px-3 py-2 rounded-lg border text-sm font-medium', getStatusBadgeClass(inquiry.status)]">
                                    <div class="h-2 w-2 rounded-full" :class="{'bg-yellow-500': inquiry.status === 'pending', 'bg-green-500': inquiry.status === 'responded', 'bg-red-500': inquiry.status === 'spam'}"></div>
                                    {{ inquiry.status.charAt(0).toUpperCase() + inquiry.status.slice(1) }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Update Status</label>
                                <div class="space-y-2">
                                    <button v-if="inquiry.status !== 'pending'" @click="updateStatus('pending')" :disabled="isUpdatingStatus" class="w-full px-4 py-2 text-sm font-medium text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 rounded-lg transition-colors disabled:opacity-50">
                                        Mark as Pending
                                    </button>
                                    <button v-if="inquiry.status !== 'responded'" @click="updateStatus('responded')" :disabled="isUpdatingStatus" class="w-full px-4 py-2 text-sm font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors disabled:opacity-50">
                                        Mark as Responded
                                    </button>
                                    <button v-if="inquiry.status !== 'spam'" @click="updateStatus('spam')" :disabled="isUpdatingStatus" class="w-full px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors disabled:opacity-50">
                                        Mark as Spam
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <a :href="`mailto:${inquiry.email}?subject=Re: ${formatSubject(inquiry.subject)}`" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Reply via Email
                            </a>
                            <button @click="openDeleteModal" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Inquiry
                            </button>
                        </div>
                    </div>

                    <!-- Inquiry Stats -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Details</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Inquiry ID</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">#{{ inquiry.id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Message Length</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ inquiry.message.length }} characters</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">CAPTCHA Status</span>
                                <span class="text-sm font-medium" :class="inquiry.captcha_verified ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    {{ inquiry.captcha_verified ? 'Verified' : 'Not Verified' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteModal :show="showDeleteModal" :title="`Delete Inquiry #${inquiry.id}`" :message="`Are you sure you want to delete this inquiry from ${inquiry.first_name} ${inquiry.last_name}? This action cannot be undone.`" :is-loading="isDeleting" @close="closeDeleteModal" @confirm="confirmDelete" />
    </AppLayout>
</template>
