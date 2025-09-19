<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { debounce } from 'lodash';
import DeleteModal from '@/components/DeleteModal.vue';
import { useAuth } from '@/composables/useAuth';

interface DatabaseBackup {
    id: number;
    filename: string;
    file_path: string;
    file_size: number;
    file_size_human: string;
    status: 'pending' | 'in_progress' | 'completed' | 'failed';
    error_message?: string | null;
    type: 'manual' | 'scheduled';
    scheduled_at?: string | null;
    completed_at?: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

interface PaginatedBackups {
    data: DatabaseBackup[];
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
    backups: PaginatedBackups;
    filters: {
        search?: string;
        sort?: string;
        trash?: boolean;
    };
}

const props = defineProps<Props>();

// Auth composable for permission checks
const {
    isAdminOrSystemAdmin,
    fetchUser
} = useAuth();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Database Backup',
        href: '/admin/database-backup',
    },
];

// Search functionality with server-side integration
const searchQuery = ref(props.filters.search || '');
// Ensure sortBy always defaults to 'latest' and validate the value
const validSortOptions = ['latest', 'oldest', 'name_asc', 'name_desc', 'size_asc', 'size_desc'];
const initialSort = props.filters.sort && validSortOptions.includes(props.filters.sort)
    ? props.filters.sort
    : 'latest';
const sortBy = ref(initialSort);

// Trash functionality
const showTrash = ref(props.filters.trash || false);

// Bulk selection for trash operations
const selectedBackups = ref<number[]>([]);
const isSelectAll = ref(false);

// Debounced search to avoid too many requests
const debouncedSearch = debounce((query: string) => {
    router.get('/admin/database-backup', {
        search: query,
        sort: sortBy.value,
        trash: showTrash.value
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

// Watch for search changes and trigger server request
watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery);
});

// Watch for sort changes and trigger server request
watch(sortBy, (newSort) => {
    // Validate sort value before making request
    const validSort = validSortOptions.includes(newSort) ? newSort : 'latest';
    router.get('/admin/database-backup', {
        search: searchQuery.value,
        sort: validSort,
        trash: showTrash.value
    }, {
        preserveState: true,
        replace: true,
    });
});

// Watch for trash toggle and trigger server request
watch(showTrash, (newTrash) => {
    router.get('/admin/database-backup', {
        search: searchQuery.value,
        sort: sortBy.value,
        trash: newTrash
    }, {
        preserveState: true,
        replace: true,
    });
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 B';

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let size = bytes;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(1)} ${units[unitIndex]}`;
};


const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return 'text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/20';
        case 'failed':
            return 'text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/20';
        case 'in_progress':
            return 'text-yellow-700 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/20';
        case 'pending':
            return 'text-blue-700 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/20';
        default:
            return 'text-gray-700 dark:text-gray-400 bg-gray-100 dark:bg-gray-900/20';
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'completed':
            return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`;
        case 'failed':
            return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />`;
        case 'in_progress':
            return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />`;
        default:
            return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />`;
    }
};

// Delete modal state
const showDeleteModal = ref(false);
const backupToDelete = ref<DatabaseBackup | null>(null);
const isDeleting = ref(false);

const openDeleteModal = (backup: DatabaseBackup) => {
    backupToDelete.value = backup;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        showDeleteModal.value = false;
        backupToDelete.value = null;
    }
};

const confirmDelete = () => {
    if (backupToDelete.value && !isDeleting.value) {
        isDeleting.value = true;
        router.delete(`/admin/database-backup/${backupToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                backupToDelete.value = null;
            },
            onError: () => {
                // Error will be handled by toast notification
            },
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    }
};

const createBackup = () => {
    router.post('/admin/database-backup', {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success will be handled by toast notification
        },
        onError: () => {
            // Error will be handled by toast notification
        },
    });
};

const downloadBackup = (backup: DatabaseBackup) => {
    window.open(`/admin/database-backup/${backup.id}/download`, '_blank');
};

const restoreBackup = (backup: DatabaseBackup) => {
    router.post(`/admin/database-backup/${backup.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success will be handled by toast notification
        },
        onError: () => {
            // Error will be handled by toast notification
        },
    });
};

const forceDeleteBackup = (backup: DatabaseBackup) => {
    if (confirm(`Are you sure you want to permanently delete '${backup.filename}'? This action cannot be undone.`)) {
        router.delete(`/admin/database-backup/${backup.id}/force-delete`, {
            preserveScroll: true,
            onSuccess: () => {
                // Success will be handled by toast notification
            },
            onError: () => {
                // Error will be handled by toast notification
            },
        });
    }
};

// Bulk selection functions
const toggleSelectAll = () => {
    if (isSelectAll.value) {
        selectedBackups.value = backups.data.map(backup => backup.id);
    } else {
        selectedBackups.value = [];
    }
};

const toggleSelectBackup = (backupId: number) => {
    const index = selectedBackups.value.indexOf(backupId);
    if (index > -1) {
        selectedBackups.value.splice(index, 1);
    } else {
        selectedBackups.value.push(backupId);
    }
    updateSelectAllState();
};

const updateSelectAllState = () => {
    isSelectAll.value = selectedBackups.value.length === backups.data.length && backups.data.length > 0;
};

const bulkDeleteForever = () => {
    if (selectedBackups.value.length === 0) {
        alert('Please select backups to delete.');
        return;
    }

    const count = selectedBackups.value.length;
    if (confirm(`Are you sure you want to permanently delete ${count} backup(s)? This action cannot be undone.`)) {
        // Delete each selected backup
        selectedBackups.value.forEach(backupId => {
            router.delete(`/admin/database-backup/${backupId}/force-delete`, {
                preserveScroll: true,
                onSuccess: () => {
                    // Remove from selected list
                    const index = selectedBackups.value.indexOf(backupId);
                    if (index > -1) {
                        selectedBackups.value.splice(index, 1);
                    }
                },
                onError: () => {
                    // Error will be handled by toast notification
                },
            });
        });
    }
};

// Watch for data changes to update selection state
watch(() => backups.data, () => {
    selectedBackups.value = [];
    isSelectAll.value = false;
}, { deep: true });

// Initialize auth on component mount
onMounted(() => {
    fetchUser();
});
</script>

<template>
    <Head title="Database Backup" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ showTrash ? 'Trash - Database Backups' : 'Database Backup' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ showTrash ? 'Manage deleted database backups' : 'Manage and download your database backups' }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Trash Toggle Button -->
                    <button
                        @click="showTrash = !showTrash"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900',
                            showTrash
                                ? 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500'
                                : 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500'
                        ]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ showTrash ? 'View Active' : 'View Trash' }}
                    </button>

                    <!-- Bulk Delete Forever Button (only show in trash view) -->
                    <button
                        v-if="isAdminOrSystemAdmin && showTrash && selectedBackups.length > 0"
                        @click="bulkDeleteForever"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Forever ({{ selectedBackups.length }})
                    </button>

                    <!-- Create Backup Button (only show when not in trash) -->
                    <button
                        v-if="isAdminOrSystemAdmin && !showTrash"
                        @click="createBackup"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Create Backup
                    </button>
                </div>
            </div>

            <!-- Search and Stats Section -->
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
                                :placeholder="showTrash ? 'Search deleted backups...' : 'Search backups...'"
                                class="block w-full pl-10 pr-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                            />
                        </div>
                    </div>
                    <div class="min-w-[160px]">
                        <select
                            v-model="sortBy"
                            class="block w-full px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                        >
                            <option value="latest">Latest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                            <option value="size_asc">Size Small-Large</option>
                            <option value="size_desc">Size Large-Small</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-900 px-3 py-2 rounded-lg shadow-sm">
                        <span class="font-medium">{{ backups.from || 0 }}</span>-<span class="font-medium">{{ backups.to || 0 }}</span> of <span class="font-medium">{{ backups.total }}</span> {{ showTrash ? 'deleted backups' : 'backups' }}
                    </div>
                </div>
            </div>

            <!-- Backups Table -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th v-if="showTrash" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    <input
                                        type="checkbox"
                                        :checked="isSelectAll"
                                        @change="toggleSelectAll"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    ID
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Filename
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Size
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Type
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Created
                                </th>
                                <th v-if="showTrash" class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Deleted
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="backup in backups.data" :key="backup.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                                <td v-if="showTrash" class="px-6 py-4 whitespace-nowrap">
                                    <input
                                        type="checkbox"
                                        :checked="selectedBackups.includes(backup.id)"
                                        @change="toggleSelectBackup(backup.id)"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ backup.id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ backup.filename }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ backup.file_size_human || formatFileSize(backup.file_size) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full',
                                        backup.type === 'manual'
                                            ? 'text-blue-700 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/20'
                                            : 'text-purple-700 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/20'
                                    ]">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path v-if="backup.type === 'manual'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ backup.type.charAt(0).toUpperCase() + backup.type.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full',
                                        getStatusColor(backup.status)
                                    ]">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getStatusIcon(backup.status)">
                                        </svg>
                                        {{ backup.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(backup.created_at) }}</div>
                                </td>
                                <td v-if="showTrash" class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-red-500 dark:text-red-400">{{ backup.deleted_at ? formatDate(backup.deleted_at) : '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Active backups actions -->
                                        <template v-if="!showTrash">
                                            <button
                                                v-if="backup.status === 'completed'"
                                                @click="downloadBackup(backup)"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-md transition-colors duration-200">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Download
                                            </button>
                                            <button
                                                v-if="isAdminOrSystemAdmin"
                                                @click="openDeleteModal(backup)"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md transition-colors duration-200">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Move to Trash
                                            </button>
                                        </template>

                                        <!-- Trash backups actions -->
                                        <template v-else>
                                            <button
                                                v-if="isAdminOrSystemAdmin"
                                                @click="restoreBackup(backup)"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-md transition-colors duration-200">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                                Restore
                                            </button>
                                            <button
                                                v-if="isAdminOrSystemAdmin"
                                                @click="forceDeleteBackup(backup)"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md transition-colors duration-200">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Delete Forever
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <!-- Empty State -->
                            <tr v-if="backups.data.length === 0">
                                <td :colspan="showTrash ? 9 : 7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path v-if="showTrash" stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                            {{ showTrash
                                                ? (searchQuery ? 'No deleted backups found' : 'Trash is empty')
                                                : (searchQuery ? 'No backups found' : 'No backups yet')
                                            }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ showTrash
                                                ? (searchQuery ? 'Try adjusting your search terms.' : 'Deleted backups will appear here.')
                                                : (searchQuery ? 'Try adjusting your search terms.' : 'Get started by creating your first database backup.')
                                            }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="backups.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Showing {{ backups.from }} to {{ backups.to }} of {{ backups.total }} results
                        </div>
                        <div class="flex items-center gap-2">
                            <template v-for="link in backups.links" :key="link.label">
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

            <!-- Help Section -->
            <div :class="[
                'rounded-xl p-4 border',
                showTrash
                    ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
                    : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
            ]">
                <div class="flex items-start gap-3">
                    <svg :class="[
                        'h-5 w-5 flex-shrink-0 mt-0.5',
                        showTrash
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-blue-600 dark:text-blue-400'
                    ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h3 :class="[
                            'text-sm font-semibold mb-1',
                            showTrash
                                ? 'text-red-900 dark:text-red-100'
                                : 'text-blue-900 dark:text-blue-100'
                        ]">
                            {{ showTrash ? 'Trash Management' : 'Database Backup Guidelines' }}
                        </h3>
                        <ul :class="[
                            'text-sm space-y-1',
                            showTrash
                                ? 'text-red-800 dark:text-red-200'
                                : 'text-blue-800 dark:text-blue-200'
                        ]">
                            <li v-if="!showTrash">• Regular backups help protect your data from loss or corruption</li>
                            <li v-if="!showTrash">• Only completed backups can be downloaded</li>
                            <li v-if="!showTrash">• Failed backups can be moved to trash to free up storage space</li>
                            <li v-if="!showTrash">• Backups are stored securely and can be restored when needed</li>

                            <li v-if="showTrash">• Deleted backups are moved to trash and can be restored</li>
                            <li v-if="showTrash">• Use "Restore" to recover a backup from trash</li>
                            <li v-if="showTrash">• "Delete Forever" permanently removes the backup and cannot be undone</li>
                            <li v-if="showTrash">• Trash helps prevent accidental data loss</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            :show="showDeleteModal"
            :title="`Move ${backupToDelete?.filename} to Trash`"
            :message="`Are you sure you want to move '${backupToDelete?.filename}' to trash? This backup can be restored later if needed.`"
            :is-loading="isDeleting"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
