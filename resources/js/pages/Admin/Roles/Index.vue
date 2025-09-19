<template>
    <Head title="Roles Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Roles Management</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage system roles and their permissions</p>
                </div>
                <button 
                    v-if="canCreateRoles"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Role
                </button>
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
                                placeholder="Search roles..."
                                class="block w-full pl-10 pr-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                                @input="debouncedSearch"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-900 px-3 py-2 rounded-lg shadow-sm">
                        <span class="font-medium">{{ roles.from || 0 }}</span>-<span class="font-medium">{{ roles.to || 0 }}</span> of <span class="font-medium">{{ roles.total }}</span> roles
                    </div>
                </div>
            </div>

            <!-- Roles Table -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    ID
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Role Name
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Permissions
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Created
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="role in roles.data" :key="role.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ role.id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ role.name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="permission in role.permissions.slice(0, 3)"
                                            :key="permission.id"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300"
                                        >
                                            {{ permission.name }}
                                        </span>
                                        <span
                                            v-if="role.permissions.length > 3"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300"
                                        >
                                            +{{ role.permissions.length - 3 }} more
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(role.created_at) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            v-if="canEditRoles"
                                            @click="editRole(role)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/30 rounded-md transition-colors duration-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button 
                                            v-if="canDeleteRoles && role.name !== 'System Admin'"
                                            @click="confirmDelete(role)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-md transition-colors duration-200">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Empty State -->
                            <tr v-if="roles.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                            {{ searchQuery ? 'No roles found' : 'No roles yet' }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ searchQuery ? 'Try adjusting your search terms.' : 'Get started by creating your first role.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="roles.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Showing {{ roles.from }} to {{ roles.to }} of {{ roles.total }} results
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                @click="changePage(page)"
                                :class="[
                                    'px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200',
                                    page === roles.current_page 
                                        ? 'bg-indigo-600 text-white' 
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'
                                ]"
                            >
                                {{ page }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-900">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        {{ editingRole ? 'Edit Role' : 'Create New Role' }}
                    </h3>
                    
                    <form @submit.prevent="saveRole" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="Enter role name"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Permissions</label>
                            <div class="max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-3 bg-gray-50 dark:bg-gray-800">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label
                                        v-for="permission in availablePermissions"
                                        :key="permission.id"
                                        class="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 p-2 rounded"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="permission.id"
                                            v-model="form.permissions"
                                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ permission.name }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ loading ? 'Saving...' : (editingRole ? 'Update' : 'Create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            :show="showDeleteModal"
            :title="`Delete ${roleToDelete?.name}`"
            :message="`Are you sure you want to delete '${roleToDelete?.name}'? This action cannot be undone and will permanently remove this role from your system.`"
            :is-loading="isDeleting"
            @close="closeDeleteModal"
            @confirm="deleteRole"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import { useAuth } from '@/composables/useAuth'
import DeleteModal from '@/components/DeleteModal.vue'
import { type BreadcrumbItem } from '@/types'

interface Permission {
  id: number
  name: string
}

interface Role {
  id: number
  name: string
  permissions: Permission[]
  created_at: string
}

interface PaginatedRoles {
  data: Role[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

const roles = ref<PaginatedRoles>({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})

const availablePermissions = ref<Permission[]>([])
const { canCreateRoles, canEditRoles, canDeleteRoles, fetchUser } = useAuth()

const loading = ref(false)
const showModal = ref(false)
const showDeleteModal = ref(false)
const isDeleting = ref(false)
const editingRole = ref<Role | null>(null)
const roleToDelete = ref<Role | null>(null)
const searchQuery = ref('')
const form = ref({ name: '', permissions: [] as number[] })

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles Management',
        href: '/admin/roles-management',
    },
]

const visiblePages = computed(() => {
  const current = roles.value.current_page
  const last = roles.value.last_page
  const pages = []
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }
  
  return pages
})

const fetchRoles = async (page = 1) => {
  try {
    loading.value = true
    const response = await axios.get('/admin/api/roles', {
      params: {
        page,
        search: searchQuery.value
      }
    })
    roles.value = response.data
  } catch (error) {
    console.error('Error fetching roles:', error)
  } finally {
    loading.value = false
  }
}

const fetchPermissions = async () => {
  try {
    const response = await axios.get('/admin/api/roles-permissions')
    availablePermissions.value = response.data
  } catch (error) {
    console.error('Error fetching permissions:', error)
  }
}

const debouncedSearch = (() => {
  let timeout: number
  return () => {
    clearTimeout(timeout)
    timeout = window.setTimeout(() => {
      fetchRoles(1)
    }, 300)
  }
})()

const changePage = (page: number) => {
  if (page >= 1 && page <= roles.value.last_page) {
    fetchRoles(page)
  }
}

const openCreateModal = () => {
  editingRole.value = null
  form.value = { name: '', permissions: [] }
  showModal.value = true
}

const editRole = (role: Role) => {
  editingRole.value = role
  form.value = {
    name: role.name,
    permissions: role.permissions.map(p => p.id)
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingRole.value = null
  form.value = { name: '', permissions: [] }
}

const saveRole = async () => {
  try {
    loading.value = true
    
    if (editingRole.value) {
      await axios.put(`/admin/api/roles/${editingRole.value.id}`, form.value)
    } else {
      await axios.post('/admin/api/roles', form.value)
    }
    
    closeModal()
    fetchRoles(roles.value.current_page)
  } catch (error) {
    console.error('Error saving role:', error)
  } finally {
    loading.value = false
  }
}

const confirmDelete = (role: Role) => {
  roleToDelete.value = role
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  if (!isDeleting.value) {
    showDeleteModal.value = false
    roleToDelete.value = null
  }
}

const deleteRole = async () => {
  if (!roleToDelete.value) return
  
  try {
    isDeleting.value = true
    await axios.delete(`/admin/api/roles/${roleToDelete.value.id}`)
    closeDeleteModal()
    fetchRoles(roles.value.current_page)
  } catch (error) {
    console.error('Error deleting role:', error)
  } finally {
    isDeleting.value = false
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

onMounted(async () => {
  await fetchUser()
  fetchRoles()
  fetchPermissions()
})
</script>
