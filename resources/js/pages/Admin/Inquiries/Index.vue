<template>
    <Head title="Inquiries Monitoring" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Inquiries</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor client inquiries and follow up</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">New</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.new }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Contacted</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.contacted }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Closed</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ stats.closed }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4">
                <div class="flex flex-1 gap-4">
                    <div class="flex-1 max-w-md">
                        <input v-model="search" type="text" placeholder="Search inquiries..." class="block w-full px-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500" @input="applyFilters" />
                    </div>
                    <select v-model="status" @change="applyFilters" class="px-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">All Status</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 dark:text-gray-400">Show:</label>
                    <select v-model.number="perPage" @change="applyFilters" class="block px-3 py-2 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Client</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Property</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Message</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="inq in inquiries.data" :key="inq.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ inq.name }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ inq.email }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ inq.phone || '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ inq.property?.property_name }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ inq.property?.location?.name }} • {{ inq.property?.category?.name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ inq.message }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="inq.status === 'new'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">New</span>
                                    <span v-else-if="inq.status === 'contacted'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">Contacted</span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Closed</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button class="text-xs px-3 py-1 rounded-lg border" @click="updateStatus(inq.id, 'contacted')">Mark Contacted</button>
                                        <button class="text-xs px-3 py-1 rounded-lg border" @click="updateStatus(inq.id, 'closed')">Close</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="inquiries.last_page > 1" class="bg-gray-50 dark:bg-gray-800/50 px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">{{ inquiries.from || 0 }}</span>-<span class="font-medium">{{ inquiries.to || 0 }}</span> of <span class="font-medium">{{ inquiries.total }}</span>
                        </div>
                        <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <Link :href="buildUrl(inquiries.prev_page_url)" class="relative inline-flex items-center px-2 py-2 text-sm font-medium border rounded-l-md" :class="inquiries.prev_page_url ? 'text-gray-700 bg-white dark:bg-gray-900' : 'text-gray-400 bg-gray-100 dark:bg-gray-800 pointer-events-none'">
                                Prev
                            </Link>
                            <Link :href="buildUrl(inquiries.next_page_url)" class="relative inline-flex items-center px-2 py-2 text-sm font-medium border rounded-r-md" :class="inquiries.next_page_url ? 'text-gray-700 bg-white dark:bg-gray-900' : 'text-gray-400 bg-gray-100 dark:bg-gray-800 pointer-events-none'">
                                Next
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    inquiries: Object,
    stats: Object,
    filters: Object,
})

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin', href: '/admin' },
    { title: 'Inquiries', href: '/admin/inquiries' },
]

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')
const perPage = ref(props.filters?.per_page || 10)

const applyFilters = () => {
    router.get('/admin/inquiries', {
        search: search.value,
        status: status.value,
        per_page: perPage.value,
    }, { preserveState: true, replace: true })
}

const buildUrl = (url) => {
    if (!url) return ''
    const u = new URL(url)
    return `/admin/inquiries?${u.searchParams.toString()}`
}

const updateStatus = async (id, newStatus) => {
    try {
        await router.reload({
            only: ['inquiries', 'stats'],
            preserveScroll: true,
            onStart: () => {
                fetch(`/admin/api/inquiries/${id}/status`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ status: newStatus }),
                })
            },
        })
    } catch (e) {
        // Silent fail; could add toast
    }
}
</script>