import { ref, computed } from 'vue'
import axios from 'axios'

interface Permission {
  id: number
  name: string
}

interface Role {
  id: number
  name: string
  permissions: Permission[]
}

interface User {
  id: number
  name: string
  email: string
  roles: Role[]
}

const user = ref<User | null>(null)
const loading = ref(false)

export function useAuth() {
  const fetchUser = async () => {
    try {
      loading.value = true
      const response = await axios.get('/api/user')
      user.value = response.data
    } catch (error) {
      console.error('Error fetching user:', error)
      user.value = null
    } finally {
      loading.value = false
    }
  }

  const hasRole = (roleName: string | string[]): boolean => {
    if (!user.value) return false
    
    const roles = Array.isArray(roleName) ? roleName : [roleName]
    return user.value.roles.some(role => roles.includes(role.name))
  }

  const hasPermission = (permissionName: string | string[]): boolean => {
    if (!user.value) return false
    
    const permissions = Array.isArray(permissionName) ? permissionName : [permissionName]
    
    return user.value.roles.some(role =>
      role.permissions.some(permission => permissions.includes(permission.name))
    )
  }

  const isSystemAdmin = computed(() => hasRole('System Admin'))
  const isAdmin = computed(() => hasRole('Admin'))
  const isAdminOrSystemAdmin = computed(() => isSystemAdmin.value || isAdmin.value)

  const canViewCategories = computed(() => hasPermission('view category'))
  const canCreateCategories = computed(() => hasPermission('create category'))
  const canEditCategories = computed(() => hasPermission('edit category'))
  const canDeleteCategories = computed(() => hasPermission('delete category'))

  const canViewLocations = computed(() => hasPermission('view location'))
  const canCreateLocations = computed(() => hasPermission('create location'))
  const canEditLocations = computed(() => hasPermission('edit location'))
  const canDeleteLocations = computed(() => hasPermission('delete location'))

  const canViewProperties = computed(() => hasPermission('view property'))
  const canCreateProperties = computed(() => hasPermission('create property'))
  const canEditProperties = computed(() => hasPermission('edit property'))
  const canDeleteProperties = computed(() => hasPermission('delete property'))

  const canManageRoles = computed(() => 
    hasPermission(['create role', 'edit role', 'delete role', 'view role'])
  )

  const canManagePermissions = computed(() => 
    hasPermission(['create permission', 'edit permission', 'delete permission', 'view permission'])
  )

  const canCreateRoles = computed(() => hasPermission('create role'))
  const canEditRoles = computed(() => hasPermission('edit role'))
  const canDeleteRoles = computed(() => hasPermission('delete role'))
  
  const canCreatePermissions = computed(() => hasPermission('create permission'))
  const canEditPermissions = computed(() => hasPermission('edit permission'))
  const canDeletePermissions = computed(() => hasPermission('delete permission'))

  return {
    user,
    loading,
    fetchUser,
    hasRole,
    hasPermission,
    isSystemAdmin,
    isAdmin,
    isAdminOrSystemAdmin,
    canManageRoles,
    canManagePermissions,
    canCreateRoles,
    canEditRoles,
    canDeleteRoles,
    canCreatePermissions,
    canEditPermissions,
    canDeletePermissions,
    canViewCategories,
    canCreateCategories,
    canEditCategories,
    canDeleteCategories,
    canViewLocations,
    canCreateLocations,
    canEditLocations,
    canDeleteLocations,
    canViewProperties,
    canCreateProperties,
    canEditProperties,
    canDeleteProperties
  }
}
