<template>
    <Head title="Inquiry Details" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Inquiry #{{ inquiry.id }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Submitted {{ formatDate(inquiry.created_at) }}</p>
                </div>
                <div class="flex gap-2">
                    <button @click="goBack" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Back</button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Information -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Contact Information</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">First Name</label>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ inquiry.first_name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Name</label>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ inquiry.last_name }}</p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</label>
                                <a :href="`mailto:${inquiry.email}`" class="mt-1 text-indigo-600 dark:text-indigo-400 hover:underline block">{{ inquiry.email }}</a>
                            </div>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Message</h2>
                        <p class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ inquiry.message }}</p>
                    </div>

                    <!-- Admin Notes -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Admin Notes</h2>
                        <textarea v-model="adminNotes" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" placeholder="Add internal notes..."></textarea>
                        <button @click="saveNotes" class="mt-3 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save Notes</button>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Status</h2>
                        <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium" :class="getStatusClass(inquiry.status)">
                            {{ inquiry.status.charAt(0).toUpperCase() + inquiry.status.slice(1) }}
                        </span>
                        <div class="mt-4 space-y-2">
                            <button v-if="inquiry.status === 'pending'" @click="updateStatus('read')" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Mark as Read</button>
                            <button v-if="inquiry.status !== 'replied'" @click="updateStatus('replied')" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Mark as Replied</button>
                            <button v-if="inquiry.status !== 'archived'" @click="updateStatus('archived')" class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Archive</button>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Details</h2>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Subject</label>
                                <span class="mt-1 inline-flex px-2 py-1 rounded-full text-xs" :class="getSubjectClass(inquiry.subject)">{{ getSubjectLabel(inquiry.subject) }}</span>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ inquiry.ip_address || 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Submitted</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(inquiry.created_at) }}</p>
                            </div>
                            <div v-if="inquiry.read_at">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Read At</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(inquiry.read_at) }}</p>
                            </div>
                            <div v-if="inquiry.replied_at">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Replied At</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ formatDate(inquiry.replied_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Actions</h2>
                        <button @click="deleteInquiry" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete Inquiry</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    inquiry: Object
});

const breadcrumbs = [
    { label: 'Dashboard', url: '/admin/dashboard' },
    { label: 'Guest Inquiries', url: '/admin/guest-inquiries' },
    { label: `Inquiry #${props.inquiry.id}`, url: null }
];

const adminNotes = ref(props.inquiry.admin_notes || '');

function goBack() {
    router.visit(route('admin.guest-inquiries.index'));
}

function updateStatus(status) {
    router.patch(route('admin.guest-inquiries.update-status', props.inquiry.id), {
        status: status
    }, {
        preserveScroll: true,
        onSuccess: () => {
            alert('Status updated successfully');
        }
    });
}

function saveNotes() {
    router.patch(route('admin.guest-inquiries.update-notes', props.inquiry.id), {
        admin_notes: adminNotes.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            alert('Notes saved successfully');
        }
    });
}

function deleteInquiry() {
    if (confirm('Are you sure you want to delete this inquiry?')) {
        router.delete(route('admin.guest-inquiries.destroy', props.inquiry.id));
    }
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
        rental: 'Property Rental',
        lease: 'Property Lease',
        general: 'General Information',
        support: 'Customer Support'
    };
    return labels[subject] || subject;
}

function formatDate(date) {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
