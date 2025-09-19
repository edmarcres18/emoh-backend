<template>
    <Head title="Users Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Users Management</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage system users and their roles</p>
                </div>
                <Link 
                    v-if="isAdminOrSystemAdmin"
                    href="/admin/users/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add User
                </Link>
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
                                placeholder="Search users..."
                                class="block w-full pl-10 pr-4 py-3 border-0 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"
                                @input="debouncedSearch"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-900 px-3 py-2 rounded-lg shadow-sm">
                        <span class="font-medium">{{ users.from || 0 }}</span>-<span class="font-medium">{{ users.to || 0 }}</span> of <span class="font-medium">{{ users.total }}</span> users
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    ID
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    User
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Email
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                    Roles
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
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ user.id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ user.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ user.email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in user.roles"
                                            :key="role.id"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="role.name === 'System Admin' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300'"
                                        >
                                            {{ role.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            v-if="canEditUser(user)"
                                            :href="`/admin/users/${user.id}`"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-150"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            v-if="canEditUser(user)"
                                            :href="`/admin/users/${user.id}/edit`"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-150"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="canDeleteUser(user)"
                                            @click="confirmDelete(user)"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors duration-150"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <button
                            @click="loadUsers(users.current_page - 1)"
                            :disabled="users.current_page <= 1"
                            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Previous
                        </button>
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            Page {{ users.current_page }} of {{ users.last_page }}
                        </span>
                        <button
                            @click="loadUsers(users.current_page + 1)"
                            :disabled="users.current_page >= users.last_page"
                            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Delete Confirmation Modal -->
        <DeleteModal
            :show="showDeleteModal"
            :title="`Delete ${userToDelete?.name}`"
            :message="`Are you sure you want to delete '${userToDelete?.name}'? This action cannot be undone.`"
            :is-loading="isDeleting"
            @close="closeDeleteModal"
            @confirm="deleteUser"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'
import { useAuth } from '@/composables/useAuth'
import DeleteModal from '@/components/DeleteModal.vue'
import { type BreadcrumbItem } from '@/types'

interface Role {
  id: number
  name: string
}

interface User {
  id: number
  name: string
  email: string
  roles: Role[]
  created_at: string
}

interface PaginatedUsers {
  data: User[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

const users = ref<PaginatedUsers>({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})

const { isSystemAdmin, isAdminOrSystemAdmin, user: currentUser, fetchUser } = useAuth()

const showDeleteModal = ref(false)
const isDeleting = ref(false)
const userToDelete = ref<User | null>(null)
const searchQuery = ref('')

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: '/admin' },
  { title: 'Users', href: '/admin/users' }
]

// Computed properties for permissions
const canEditUser = (user: User) => {
  if (!isAdminOrSystemAdmin.value) return false
  if (user.roles.some(role => role.name === 'System Admin') && !isSystemAdmin.value) return false
  return true
}

const canDeleteUser = (user: User) => {
  if (!isSystemAdmin.value) return false
  if (user.id === currentUser.value?.id) return false
  if (user.roles.some(role => role.name === 'System Admin')) return false
  return true
}

// Methods
const loadUsers = async (page = 1) => {
  try {
    const response = await axios.get('/admin/api/users', {
      params: {
        page,
        search: searchQuery.value,
        per_page: 15
      }
    })
    users.value = response.data
  } catch (error) {
    console.error('Error loading users:', error)
  }
}


const confirmDelete = (user: User) => {
  userToDelete.value = user
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  userToDelete.value = null
}

const deleteUser = async () => {
  if (!userToDelete.value) return
  
  isDeleting.value = true
  try {
    await axios.delete(`/admin/api/users/${userToDelete.value.id}`)
    closeDeleteModal()
    loadUsers(users.value.current_page)
  } catch (error) {
    console.error('Error deleting user:', error)
  } finally {
    isDeleting.value = false
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

let searchTimeout: NodeJS.Timeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadUsers(1)
  }, 300)
}

onMounted(() => {
  fetchUser()
  loadUsers()
})
</script>
