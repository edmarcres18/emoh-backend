<template>
    <Head :title="`Inquiry #${inquiry.id}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Inquiry Details</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Review client inquiry and follow up</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/admin/inquiries" class="px-3 py-2 text-sm font-medium rounded-lg border bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                        Back to Inquiries
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Client</div>
                                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ inquiry.name }}</div>
                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ inquiry.email }}</div>
                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ inquiry.phone || '-' }}</div>
                            </div>
                            <div class="text-right space-y-2">
                                <div>
                                    <span v-if="!inquiry.viewed_at" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300">Unread</span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Viewed</span>
                                </div>
                                <div>
                                    <span v-if="inquiry.status === 'new'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">New</span>
                                    <span v-else-if="inquiry.status === 'contacted'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">Contacted</span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Closed</span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Submitted: {{ new Date(inquiry.created_at).toLocaleString() }}</div>
                                <div v-if="inquiry.viewed_at" class="text-xs text-gray-500 dark:text-gray-400">Viewed: {{ new Date(inquiry.viewed_at).toLocaleString() }}</div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Message</div>
                            <div class="text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ inquiry.message }}</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Property</div>
                                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ inquiry.property?.property_name }}</div>
                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ inquiry.property?.location?.name }} • {{ inquiry.property?.category?.name }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    </template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    inquiry: Object,
})

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Admin', href: '/admin' },
    { title: 'Inquiries', href: '/admin/inquiries' },
    { title: `Inquiry #${props.inquiry?.id}`, href: `/admin/inquiries/${props.inquiry?.id}` },
]

const updateStatus = async (newStatus) => {
    try {
        await fetch(`/admin/api/inquiries/${props.inquiry.id}/status`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ status: newStatus }),
        })
        await router.reload({ only: ['inquiry'], preserveScroll: true })
    } catch (e) {
        // Silent fail; could add toast
    }
}
</script>