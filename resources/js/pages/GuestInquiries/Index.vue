<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { debounce } from 'lodash';
import DeleteModal from '@/components/DeleteModal.vue';
import { useAuth } from '@/composables/useAuth';

interface GuestInquiry {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    subject: 'rental' | 'lease' | 'general' | 'support';
    message: string;
    status: 'pending' | 'responded' | 'spam';
    created_at: string;
    responder?: { id: number; name: string };
}

interface PaginatedInquiries {
    data: GuestInquiry[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface InquiryStats {
    total: number;
    pending: number;
    responded: number;
    spam: number;
    today: number;
    this_week: number;
    this_month: number;
}

interface Props {
    inquiries: PaginatedInquiries;
    stats: InquiryStats;
    filters: { search?: string; sort?: string; status?: string; per_page?: number };
}

const props = defineProps<Props>();
const { fetchUser } = useAuth();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Guest Inquiries', href: '/guest-inquiries' }];

const searchQuery = ref(props.filters.search || '');
const sortBy = ref(props.filters.sort || 'latest');
const statusFilter = ref(props.filters.status || 'all');
const perPage = ref(props.filters?.per_page || 10);

const debouncedSearch = debounce((query: string) => {
    router.get('/guest-inquiries', { search: query, sort: sortBy.value, status: statusFilter.value, per_page: perPage.value }, { preserveState: true, replace: true });
}, 300);

watch(searchQuery, (newQuery) => debouncedSearch(newQuery));
watch([sortBy, statusFilter, perPage], () => {
    router.get('/guest-inquiries', { search: searchQuery.value, sort: sortBy.value, status: statusFilter.value, per_page: perPage.value }, { preserveState: true, replace: true });
});

const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatSubject = (subject: string) => ({ rental: 'Property Rental', lease: 'Property Lease', general: 'General Info', support: 'Support' }[subject] || subject);

const getStatusBadgeClass = (status: string) => ({
    pending: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
    responded: 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
    spam: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400'
}[status] || 'bg-gray-100');

const getSubjectBadgeClass = (subject: string) => ({
    rental: 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
    lease: 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400',
    general: 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-400',
    support: 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-400'
}[subject] || 'bg-gray-100');

const showDeleteModal = ref(false);
const inquiryToDelete = ref<GuestInquiry | null>(null);
const isDeleting = ref(false);

const openDeleteModal = (inquiry: GuestInquiry) => {
    inquiryToDelete.value = inquiry;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        showDeleteModal.value = false;
        inquiryToDelete.value = null;
    }
};

const confirmDelete = () => {
    if (inquiryToDelete.value && !isDeleting.value) {
        isDeleting.value = true;
        router.delete(`/guest-inquiries/${inquiryToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => { showDeleteModal.value = false; inquiryToDelete.value = null; },
            onFinish: () => { isDeleting.value = false; },
        });
    }
};

const isUpdatingStatus = ref(false);
const updateInquiryStatus = (inquiry: GuestInquiry, newStatus: string) => {
    if (isUpdatingStatus.value) return;
    isUpdatingStatus.value = true;
    router.patch(`/guest-inquiries/${inquiry.id}/status`, { status: newStatus }, {
        preserveScroll: true,
        onFinish: () => { isUpdatingStatus.value = false; }
    });
};

onMounted(() => fetchUser());
</script>

<template>
    <Head title="Guest Inquiries" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Guest Inquiries</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor and manage contact form submissions</p>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Inquiries</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
                        </div>
                        <div class="h-12 w-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ stats.this_month }} this month</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending</p>
                            <p class="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pending }}</p>
                        </div>
                        <div class="h-12 w-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Awaiting response</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Responded</p>
                            <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ stats.responded }}</p>
                        </div>
                        <div class="h-12 w-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Completed inquiries</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Today</p>
                            <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stats.today }}</p>
                        </div>
                        <div class="h-12 w-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ stats.this_week }} this week</p>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                <div class="flex flex-1 gap-4">
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input v-model="searchQuery" type="text" placeholder="Search inquiries..." class="block w-full pl-10 pr-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200" />
                        </div>
                    </div>
                    <select v-model="statusFilter" class="min-w-[160px] px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="responded">Responded</option>
                        <option value="spam">Spam</option>
                    </select>
                    <select v-model="sortBy" class="min-w-[160px] px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="latest">Latest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="email_asc">Email A-Z</option>
                        <option value="email_desc">Email Z-A</option>
                        <option value="status">By Status</option>
                    </select>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                        <select v-model.number="perPage" class="px-3 py-2 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-900 px-3 py-2 rounded-lg shadow-sm">
                        <span class="font-medium">{{ inquiries.from || 0 }}</span>-<span class="font-medium">{{ inquiries.to || 0 }}</span> of <span class="font-medium">{{ inquiries.total }}</span>
                    </div>
                </div>
            </div>

            <!-- Inquiries Table -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">ID</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Contact Info</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Subject</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Message</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Created</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="inquiry in inquiries.data" :key="inquiry.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">#{{ inquiry.id }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ inquiry.first_name }} {{ inquiry.last_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ inquiry.email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getSubjectBadgeClass(inquiry.subject)]">
                                        {{ formatSubject(inquiry.subject) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate" :title="inquiry.message">{{ inquiry.message }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select :value="inquiry.status" @change="updateInquiryStatus(inquiry, ($event.target as HTMLSelectElement).value)" :disabled="isUpdatingStatus" :class="['text-xs font-medium px-2.5 py-1.5 rounded-full border-0 focus:outline-none focus:ring-2 focus:ring-indigo-500', getStatusBadgeClass(inquiry.status)]">
                                        <option value="pending">Pending</option>
                                        <option value="responded">Responded</option>
                                        <option value="spam">Spam</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(inquiry.created_at) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="router.get(`/guest-inquiries/${inquiry.id}`)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-md transition-colors">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            View
                                        </button>
                                        <button @click="openDeleteModal(inquiry)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md transition-colors">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="inquiries.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">{{ searchQuery ? 'No inquiries found' : 'No inquiries yet' }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ searchQuery ? 'Try adjusting your search or filters.' : 'Guest inquiries will appear here.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="inquiries.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Showing {{ inquiries.from }} to {{ inquiries.to }} of {{ inquiries.total }} results</div>
                        <div class="flex items-center gap-2">
                            <template v-for="link in inquiries.links" :key="link.label">
                                <button v-if="link.url" @click="router.get(link.url)" :class="['px-3 py-2 text-sm font-medium rounded-md transition-colors', link.active ? 'bg-indigo-600 text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800']" v-html="link.label" />
                                <span v-else :class="['px-3 py-2 text-sm font-medium rounded-md text-gray-300 dark:text-gray-600 cursor-not-allowed']" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DeleteModal :show="showDeleteModal" :title="`Delete Inquiry #${inquiryToDelete?.id}`" :message="`Are you sure you want to delete this inquiry from ${inquiryToDelete?.first_name} ${inquiryToDelete?.last_name}? This action cannot be undone.`" :is-loading="isDeleting" @close="closeDeleteModal" @confirm="confirmDelete" />
    </AppLayout>
</template>
