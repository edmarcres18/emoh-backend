<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import DeleteModal from '@/components/DeleteModal.vue';
import { Database, Download, RefreshCw, Trash2, RotateCcw, AlertCircle, HardDrive, Archive } from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
}

interface DatabaseBackup {
    id: number;
    filename: string;
    path: string;
    size: number;
    formatted_size: string;
    created_by: number;
    creator: User;
    trashed_at: string | null;
    created_at: string;
    updated_at: string;
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
        status?: string;
    };
    stats: {
        total_active: number;
        total_trashed: number;
        total_size: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
    {
        title: 'Database Backups',
        href: '/admin/database-backups',
    },
];

// Search and filter functionality
const searchQuery = ref(props.filters.search || '');
const validSortOptions = ['latest', 'oldest', 'largest', 'smallest', 'name_asc', 'name_desc'];
const initialSort = props.filters.sort && validSortOptions.includes(props.filters.sort) 
    ? props.filters.sort 
    : 'latest';
const sortBy = ref(initialSort);
const statusFilter = ref(props.filters.status || 'active');

// Action states
const isCreating = ref(false);
const isDownloading = ref<number | null>(null);
const isRestoring = ref<number | null>(null);
const isTrashing = ref<number | null>(null);
const isRestoringFromTrash = ref<number | null>(null);

// Delete modal state
const showDeleteModal = ref(false);
const backupToDelete = ref<DatabaseBackup | null>(null);
const isDeleting = ref(false);

// Restore confirmation modal state
const showRestoreModal = ref(false);
const backupToRestore = ref<DatabaseBackup | null>(null);

// Debounced search
const debouncedSearch = debounce(() => {
    const filters = {
        search: searchQuery.value,
        sort: sortBy.value,
        status: statusFilter.value,
    };
    
    router.get('/admin/database-backups', filters, {
        preserveState: true,
        replace: true,
    });
}, 300);

// Watch for changes
watch([searchQuery, sortBy, statusFilter], () => {
    debouncedSearch();
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

const formatBytes = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const createBackup = () => {
    if (isCreating.value) return;
    
    isCreating.value = true;
    router.post('/admin/database-backups', {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by toast
        },
        onError: () => {
            // Error handled by toast
        },
        onFinish: () => {
            isCreating.value = false;
        },
    });
};

const downloadBackup = (backup: DatabaseBackup) => {
    if (isDownloading.value) return;
    
    isDownloading.value = backup.id;
    window.location.href = `/admin/database-backups/${backup.id}/download`;
    
    setTimeout(() => {
        isDownloading.value = null;
    }, 2000);
};

const openRestoreModal = (backup: DatabaseBackup) => {
    backupToRestore.value = backup;
    showRestoreModal.value = true;
};

const closeRestoreModal = () => {
    if (!isRestoring.value) {
        showRestoreModal.value = false;
        backupToRestore.value = null;
    }
};

const confirmRestore = () => {
    if (backupToRestore.value && !isRestoring.value) {
        isRestoring.value = backupToRestore.value.id;
        router.post(`/admin/database-backups/${backupToRestore.value.id}/restore`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                showRestoreModal.value = false;
                backupToRestore.value = null;
            },
            onError: () => {
                // Error handled by toast
            },
            onFinish: () => {
                isRestoring.value = null;
            },
        });
    }
};

const trashBackup = (backup: DatabaseBackup) => {
    if (isTrashing.value) return;
    
    isTrashing.value = backup.id;
    router.post(`/admin/database-backups/${backup.id}/trash`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by toast
        },
        onError: () => {
            // Error handled by toast
        },
        onFinish: () => {
            isTrashing.value = null;
        },
    });
};

const restoreFromTrash = (backup: DatabaseBackup) => {
    if (isRestoringFromTrash.value) return;
    
    isRestoringFromTrash.value = backup.id;
    router.post(`/admin/database-backups/${backup.id}/restore-from-trash`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by toast
        },
        onError: () => {
            // Error handled by toast
        },
        onFinish: () => {
            isRestoringFromTrash.value = null;
        },
    });
};

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
        router.delete(`/admin/database-backups/${backupToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                backupToDelete.value = null;
            },
            onError: () => {
                // Error handled by toast
            },
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    }
};

const clearFilters = () => {
    searchQuery.value = '';
    sortBy.value = 'latest';
    statusFilter.value = 'active';
};
</script>

<template>
    <Head title="Database Backups" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Database Backups</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create, manage, and restore database backups</p>
                </div>
                <button 
                    @click="createBackup"
                    :disabled="isCreating"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <Database class="h-4 w-4" :class="{ 'animate-pulse': isCreating }" />
                    {{ isCreating ? 'Creating Backup...' : 'Create Backup' }}
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Backups</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total_active }}</p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                            <HardDrive class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Trashed Backups</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total_trashed }}</p>
                        </div>
                        <div class="p-3 bg-amber-100 dark:bg-amber-900/20 rounded-lg">
                            <Archive class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Size</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ formatBytes(stats.total_size) }}</p>
                        </div>
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900/20 rounded-lg">
                            <Database class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters Section -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 space-y-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Search -->
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search backups..."
                                class="block w-full pl-10 pr-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                            />
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="flex flex-wrap gap-3">
                        <select v-model="statusFilter" class="px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="active">Active Backups</option>
                            <option value="trashed">Trashed Backups</option>
                            <option value="">All Backups</option>
                        </select>
                        
                        <select v-model="sortBy" class="px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="latest">Latest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="largest">Largest First</option>
                            <option value="smallest">Smallest First</option>
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                        </select>
                        
                        <button @click="clearFilters" class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            Clear
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-900 px-3 py-2 rounded-lg shadow-sm">
                        <span class="font-medium">{{ backups.from || 0 }}</span>-<span class="font-medium">{{ backups.to || 0 }}</span> of <span class="font-medium">{{ backups.total }}</span> backups
                    </div>
                </div>
            </div>

            <!-- Backups Table -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Filename</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Size</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Created By</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Created At</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="backup in backups.data" :key="backup.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/20 flex items-center justify-center">
                                            <Database class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ backup.filename }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">ID: {{ backup.id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ backup.formatted_size }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ backup.creator.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ backup.creator.email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ formatDate(backup.created_at) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="!backup.trashed_at" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/20">
                                        Active
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/20">
                                        Trashed
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Active backup actions -->
                                        <template v-if="!backup.trashed_at">
                                            <button 
                                                @click="downloadBackup(backup)"
                                                :disabled="isDownloading === backup.id"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 disabled:opacity-50 rounded-md transition-colors duration-200">
                                                <Download class="h-3 w-3" />
                                                Download
                                            </button>
                                            <button 
                                                @click="openRestoreModal(backup)"
                                                :disabled="!!isRestoring"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 disabled:opacity-50 rounded-md transition-colors duration-200">
                                                <RefreshCw class="h-3 w-3" />
                                                Restore DB
                                            </button>
                                            <button 
                                                @click="trashBackup(backup)"
                                                :disabled="isTrashing === backup.id"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 disabled:opacity-50 rounded-md transition-colors duration-200">
                                                <Trash2 class="h-3 w-3" />
                                                Trash
                                            </button>
                                        </template>

                                        <!-- Trashed backup actions -->
                                        <template v-else>
                                            <button 
                                                @click="restoreFromTrash(backup)"
                                                :disabled="isRestoringFromTrash === backup.id"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 disabled:opacity-50 rounded-md transition-colors duration-200">
                                                <RotateCcw class="h-3 w-3" />
                                                Restore
                                            </button>
                                            <button 
                                                @click="openDeleteModal(backup)"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md transition-colors duration-200">
                                                <Trash2 class="h-3 w-3" />
                                                Delete
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <!-- Empty State -->
                            <tr v-if="backups.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <Database class="h-12 w-12 text-gray-400 mb-4" />
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                            {{ searchQuery ? 'No backups found' : 'No backups yet' }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ searchQuery ? 'Try adjusting your search terms or filters.' : 'Get started by creating your first database backup.' }}
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
        </div>

        <!-- Restore Database Confirmation Modal -->
        <DeleteModal
            :show="showRestoreModal"
            title="Restore Database?"
            :message="`Are you sure you want to restore the database from backup '${backupToRestore?.filename}'? This will replace all current data with the backup data. This action cannot be undone.`"
            :is-loading="!!isRestoring"
            confirm-text="Restore Database"
            confirm-class="bg-green-600 hover:bg-green-700 focus:ring-green-500"
            @close="closeRestoreModal"
            @confirm="confirmRestore"
        >
            <template #icon>
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/20">
                    <AlertCircle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
            </template>
        </DeleteModal>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            :show="showDeleteModal"
            :title="`Permanently Delete ${backupToDelete?.filename}`"
            :message="`Are you sure you want to permanently delete '${backupToDelete?.filename}'? This action cannot be undone and will remove the backup file from storage.`"
            :is-loading="isDeleting"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
