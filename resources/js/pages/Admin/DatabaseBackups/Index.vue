<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { debounce } from 'lodash';
import axios from 'axios';
import DeleteModal from '@/components/DeleteModal.vue';

interface DatabaseBackup {
    id: number;
    filename: string;
    unique_identifier: string;
    file_size: number;
    formatted_file_size: string;
    status: 'pending' | 'in_progress' | 'completed' | 'failed' | 'in_trash';
    error_message: string | null;
    backup_date: string;
    trashed_at: string | null;
    completed_at: string | null;
    created_at: string;
    creator: { id: number; name: string; email: string } | null;
}

interface PaginatedBackups {
    data: DatabaseBackup[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/users' },
    { title: 'Database Backups', href: '/admin/database-backups' },
];

const backups = ref<PaginatedBackups>({ data: [], current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 });
const loading = ref(false);
const creating = ref(false);
const searchQuery = ref('');
const sortBy = ref('latest');
const statusFilter = ref('');
const viewMode = ref<'active' | 'trash'>('active');
const statistics = ref({ total_backups: 0, completed_backups: 0, failed_backups: 0, trashed_backups: 0, total_size: 0 });
const showDeleteModal = ref(false);
const backupToDelete = ref<DatabaseBackup | null>(null);
const isDeleting = ref(false);
const showRestoreModal = ref(false);
const backupToRestore = ref<DatabaseBackup | null>(null);
const isRestoring = ref(false);

const fetchBackups = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page, search: searchQuery.value, sort: sortBy.value, status: statusFilter.value, view: viewMode.value === 'trash' ? 'trash' : 'active' };
        const response = await axios.get('/admin/api/database-backups', { params });
        backups.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch backups:', error);
    } finally {
        loading.value = false;
    }
};

const fetchStatistics = async () => {
    try {
        const response = await axios.get('/admin/api/database-backups/statistics');
        statistics.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch statistics:', error);
    }
};

const debouncedSearch = debounce(() => fetchBackups(), 300);
watch([searchQuery, sortBy, statusFilter, viewMode], () => debouncedSearch());

const createBackup = async () => {
    if (creating.value) return;
    creating.value = true;
    try {
        await axios.post('/admin/api/database-backups');
        fetchBackups();
        fetchStatistics();
    } catch (error: any) {
        console.error('Failed to create backup:', error);
    } finally {
        creating.value = false;
    }
};

const downloadBackup = (backup: DatabaseBackup) => {
    window.location.href = `/admin/database-backups/${backup.id}/download`;
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

const confirmDelete = async () => {
    if (!backupToDelete.value || isDeleting.value) return;
    isDeleting.value = true;
    try {
        await axios.delete(`/admin/api/database-backups/${backupToDelete.value.id}`);
        showDeleteModal.value = false;
        backupToDelete.value = null;
        fetchBackups();
        fetchStatistics();
    } catch (error) {
        console.error('Failed to delete backup:', error);
    } finally {
        isDeleting.value = false;
    }
};

const moveToTrash = async (backup: DatabaseBackup) => {
    try {
        await axios.post(`/admin/api/database-backups/${backup.id}/trash`);
        fetchBackups();
        fetchStatistics();
    } catch (error) {
        console.error('Failed to move backup to trash:', error);
    }
};

const restoreFromTrash = async (backup: DatabaseBackup) => {
    try {
        await axios.post(`/admin/api/database-backups/${backup.id}/restore-from-trash`);
        fetchBackups();
        fetchStatistics();
    } catch (error) {
        console.error('Failed to restore backup from trash:', error);
    }
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

const confirmRestore = async () => {
    if (!backupToRestore.value || isRestoring.value) return;
    isRestoring.value = true;
    try {
        await axios.post(`/admin/api/database-backups/${backupToRestore.value.id}/restore`);
        showRestoreModal.value = false;
        backupToRestore.value = null;
    } catch (error) {
        console.error('Failed to restore database:', error);
    } finally {
        isRestoring.value = false;
    }
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    sortBy.value = 'latest';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        completed: 'text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/20',
        in_progress: 'text-blue-700 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/20',
        pending: 'text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/20',
        failed: 'text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/20',
        in_trash: 'text-gray-700 dark:text-gray-400 bg-gray-100 dark:bg-gray-900/20'
    };
    return colors[status] || colors.in_trash;
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        completed: 'Completed',
        in_progress: 'In Progress',
        pending: 'Pending',
        failed: 'Failed',
        in_trash: 'In Trash'
    };
    return labels[status] || status;
};

const formatFileSize = (bytes: number) => {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let size = bytes;
    let unitIndex = 0;
    while (size > 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    return `${size.toFixed(2)} ${units[unitIndex]}`;
};

const goToPage = (page: number) => fetchBackups(page);

onMounted(() => {
    fetchBackups();
    fetchStatistics();
    const interval = setInterval(() => {
        fetchBackups(backups.value.current_page);
        fetchStatistics();
    }, 30000);
    return () => clearInterval(interval);
});
</script>

<template>
    <Head title="Database Backups" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Database Backups</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your database backups and restore points</p>
                </div>
                <button @click="createBackup" :disabled="creating" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 disabled:opacity-50">
                    <svg v-if="!creating" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    <svg v-else class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    {{ creating ? 'Creating...' : 'Create Backup' }}
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Backups</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ statistics.total_backups }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ statistics.completed_backups }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">In Trash</div>
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ statistics.trashed_backups }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Size</div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ formatFileSize(statistics.total_size) }}</div>
                </div>
            </div>

            <div class="flex gap-2">
                <button @click="viewMode = 'active'" :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200', viewMode === 'active' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400']">Active Backups</button>
                <button @click="viewMode = 'trash'" :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200', viewMode === 'trash' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400']">Trash</button>
            </div>

            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 space-y-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input v-model="searchQuery" type="text" placeholder="Search backups..." class="block w-full pl-10 pr-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <select v-if="viewMode === 'active'" v-model="statusFilter" class="px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                            <option value="">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="in_progress">In Progress</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </select>
                        <select v-model="sortBy" class="px-3 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                            <option value="latest">Latest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="name_asc">Name A-Z</option>
                            <option value="name_desc">Name Z-A</option>
                            <option value="size_asc">Size Small-Large</option>
                            <option value="size_desc">Size Large-Small</option>
                        </select>
                        <button @click="clearFilters" class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400">Clear</button>
                    </div>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-900 px-3 py-2 rounded-lg shadow-sm">
                    <span class="font-medium">{{ backups.from || 0 }}</span>-<span class="font-medium">{{ backups.to || 0 }}</span> of <span class="font-medium">{{ backups.total }}</span> backups
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <div v-if="loading" class="flex items-center justify-center py-12">
                    <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>
                <div v-else-if="backups.data.length === 0" class="flex flex-col items-center justify-center py-12">
                    <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                    <p class="text-gray-500 dark:text-gray-400">No backups found</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Filename</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Size</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Created By</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="backup in backups.data" :key="backup.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ backup.filename }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">ID: {{ backup.unique_identifier }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusColor(backup.status)" class="inline-flex px-3 py-1 text-xs font-semibold rounded-full">{{ getStatusLabel(backup.status) }}</span>
                                </td>
                                <td class="px-6 py-4"><div class="text-sm">{{ backup.formatted_file_size }}</div></td>
                                <td class="px-6 py-4"><div class="text-sm">{{ formatDate(backup.backup_date) }}</div></td>
                                <td class="px-6 py-4">
                                    <div v-if="backup.creator" class="text-sm">{{ backup.creator.name }}</div>
                                    <div v-else class="text-sm text-gray-500">System</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <template v-if="viewMode === 'active'">
                                            <button v-if="backup.status === 'completed'" @click="downloadBackup(backup)" title="Download" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            </button>
                                            <button v-if="backup.status === 'completed'" @click="openRestoreModal(backup)" title="Restore" class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            </button>
                                            <button @click="moveToTrash(backup)" title="Move to Trash" class="p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button @click="restoreFromTrash(backup)" title="Restore from Trash" class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            </button>
                                            <button @click="openDeleteModal(backup)" title="Delete" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteModal 
            :show="showDeleteModal"
            :isDeleting="isDeleting"
            title="Delete Backup Permanently?"
            :message="`Are you sure you want to permanently delete backup '${backupToDelete?.filename}'? This action cannot be undone.`"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />

        <!-- Restore Confirmation Modal -->
        <DeleteModal 
            :show="showRestoreModal"
            :isDeleting="isRestoring"
            title="Restore Database?"
            :message="`Are you sure you want to restore the database from backup '${backupToRestore?.filename}'? This will replace your current database.`"
            confirmText="Restore"
            @close="closeRestoreModal"
            @confirm="confirmRestore"
        />
    </AppLayout>
</template>
