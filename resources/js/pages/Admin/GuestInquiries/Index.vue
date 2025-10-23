<template>
    <Head title="Guest Inquiries" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Guest Inquiries</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor and manage guest contact inquiries</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.pending }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Replied</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.replied }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Archived</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.archived }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                <div class="flex flex-1 gap-4">
                    <div class="flex-1 max-w-md">
                        <input v-model="searchQuery" type="text" placeholder="Search by name or email..." class="block w-full px-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-500" @input="debouncedSearch" />
                    </div>
                    <select v-model="selectedStatus" @change="filterByStatus" class="px-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="read">Read</option>
                        <option value="replied">Replied</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <tr v-if="inquiries.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No inquiries found</td>
                            </tr>
                            <tr v-else v-for="inquiry in inquiries.data" :key="inquiry.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">#{{ inquiry.id }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ inquiry.first_name }} {{ inquiry.last_name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ inquiry.email }}</td>
                                <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full" :class="getSubjectClass(inquiry.subject)">{{ getSubjectLabel(inquiry.subject) }}</span></td>
                                <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(inquiry.status)">{{ inquiry.status }}</span></td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(inquiry.created_at) }}</td>
                                <td class="px-6 py-4 text-right"><button @click="viewInquiry(inquiry)" class="text-indigo-600 hover:text-indigo-900">View</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    inquiries: Object,
    stats: Object,
    filters: Object
});

const breadcrumbs = [
    { label: 'Dashboard', url: '/admin/dashboard' },
    { label: 'Guest Inquiries', url: null }
];

const searchQuery = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || 'all');

const debouncedSearch = debounce(() => filterData(), 500);

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function filterByStatus() {
    filterData();
}

function filterData() {
    router.get(route('admin.guest-inquiries.index'), {
        search: searchQuery.value,
        status: selectedStatus.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
}

function viewInquiry(inquiry) {
    router.visit(route('admin.guest-inquiries.show', inquiry.id));
}

function getStatusClass(status) {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        read: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        replied: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        archived: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
    };
    return classes[status] || classes.pending;
}

function getSubjectClass(subject) {
    const classes = {
        rental: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        lease: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
        general: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        support: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    };
    return classes[subject] || classes.general;
}

function getSubjectLabel(subject) {
    const labels = {
        rental: 'Rental',
        lease: 'Lease',
        general: 'General',
        support: 'Support'
    };
    return labels[subject] || subject;
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
