<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AuthModuleHeader from '@/Components/Admin/AuthModuleHeader.vue'
import { i18n } from '@/Services/i18n'

const props = withDefaults(defineProps<{
  rolesPermissions?: Array<any>
  allPermissions?: string[]
  summaryStats?: any
}>(), {
  rolesPermissions: () => [],
  allPermissions: () => [],
  summaryStats: () => ({})
})

const isCreateModalOpen = ref(false)
const isEditModalOpen = ref(false)
const editingRole = ref<any>(null)
const searchQuery = ref('')

const createRoleForm = useForm({
  name: '',
  code: '',
  description: '',
  status: 'Active',
  permissions: [] as string[]
})

const editRoleForm = useForm({
  original_name: '',
  name: '',
  code: '',
  description: '',
  status: 'Active'
})

const toast = ref<{ show: boolean; type: 'success' | 'info' | 'warning'; title: string; message: string }>({
  show: false,
  type: 'success',
  title: '',
  message: ''
})
let toastTimer: any = null

const triggerToast = (title: string, message: string, type: 'success' | 'info' | 'warning' = 'success') => {
  if (toastTimer) clearTimeout(toastTimer)
  toast.value = { show: true, type, title, message }
  toastTimer = setTimeout(() => {
    toast.value.show = false
  }, 4500)
}

const openEditRoleModal = (role: any) => {
  editingRole.value = role
  editRoleForm.original_name = role.role
  editRoleForm.name = role.role
  editRoleForm.code = role.role_code || ''
  editRoleForm.description = role.description || ''
  editRoleForm.status = role.status || 'Active'
  isEditModalOpen.value = true
}

const submitEditRole = () => {
  const updatedRoleName = editRoleForm.name
  editRoleForm.post('/admin/auth-logs/roles/update', {
    preserveScroll: true,
    onSuccess: () => {
      isEditModalOpen.value = false
      triggerToast(
        'រក្សាទុកបានជោគជ័យ',
        `ព័ត៌មានតួនាទី "${updatedRoleName}" ត្រូវបានធ្វើបច្ចុប្បន្នភាព`
      )
    }
  })
}

const activePermissions = ref<Record<string, string[]>>({})
const originalPermissions = ref<Record<string, string[]>>({})

// Keep permissions synchronized
watch(() => props.rolesPermissions, (newRoles) => {
  if (!newRoles) return
  const current: Record<string, string[]> = {}
  const orig: Record<string, string[]> = {}
  newRoles.forEach(r => {
    current[r.role] = [...(r.permissions || [])]
    orig[r.role] = [...(r.permissions || [])]
  })
  activePermissions.value = current
  originalPermissions.value = orig
}, { immediate: true, deep: true })

const hasUnsavedChanges = computed(() => {
  return JSON.stringify(activePermissions.value) !== JSON.stringify(originalPermissions.value)
})

const defaultPermissions = [
  'View Dashboard',
  'Manage Users',
  'Create Courses',
  'View Courses',
  'Create Quiz',
  'Take Quiz',
  'Upload Content',
  'Download Content',
  'Issue Certificate',
  'Send Notifications',
  'View All Analytics',
  'View Own Analytics',
  'System Settings',
  'Configure AI Rules',
  'Manage Payments'
]

const permissionsList = computed(() => {
  return props.allPermissions && props.allPermissions.length > 0
    ? props.allPermissions
    : defaultPermissions
})

// Flaticon SVG Icon mapper for permissions
const getPermissionIcon = (perm: string) => {
  switch (perm) {
    case 'View Dashboard': return '/images/nav/dashboard.svg'
    case 'View All Analytics': return '/images/nav/analytics.svg'
    case 'View Own Analytics': return '/images/nav/progress.svg'
    case 'Manage Users': return '/images/nav/users.svg'
    case 'System Settings': return '/images/nav/settings.svg'
    case 'Configure AI Rules': return '/images/nav/ai.svg'
    case 'Create Courses': return '/images/nav/courses.svg'
    case 'View Courses': return '/images/nav/sub/all-courses.svg'
    case 'Upload Content': return '/images/nav/content.svg'
    case 'Download Content': return '/images/nav/sub/import-export.svg'
    case 'Create Quiz': return '/images/nav/quiz.svg'
    case 'Take Quiz': return '/images/nav/sub/self-study.svg'
    case 'Issue Certificate': return '/images/nav/certificate.svg'
    case 'Send Notifications': return '/images/nav/notification.svg'
    case 'Manage Payments': return '/images/nav/payment.svg'
    default: return '/images/nav/auth.svg'
  }
}

// Categories grouped logically with Flaticon vector icons
const permissionCategories = computed(() => {
  const all = permissionsList.value
  const q = searchQuery.value.trim().toLowerCase()
  
  const catList = [
    {
      key: 'dashboard',
      name: i18n.t('cat_dashboard', 'Dashboard & Analytics'),
      iconUrl: '/images/nav/analytics.svg',
      items: ['View Dashboard', 'View All Analytics', 'View Own Analytics']
    },
    {
      key: 'users',
      name: i18n.t('cat_users', 'User Management & RBAC'),
      iconUrl: '/images/nav/users.svg',
      items: ['Manage Users', 'System Settings', 'Configure AI Rules']
    },
    {
      key: 'courses',
      name: i18n.t('cat_courses', 'Course & Content Delivery'),
      iconUrl: '/images/nav/courses.svg',
      items: ['Create Courses', 'View Courses', 'Upload Content', 'Download Content']
    },
    {
      key: 'quizzes',
      name: i18n.t('cat_quizzes', 'Quiz & Assessment'),
      iconUrl: '/images/nav/quiz.svg',
      items: ['Create Quiz', 'Take Quiz', 'Issue Certificate']
    },
    {
      key: 'system',
      name: i18n.t('cat_system', 'Payments & Notifications'),
      iconUrl: '/images/nav/payment.svg',
      items: ['Manage Payments', 'Send Notifications']
    }
  ]

  // Filter items based on searchQuery and existing permissions
  const categorized = catList.map(c => {
    const validItems = c.items.filter(item => all.includes(item))
    const filteredItems = q ? validItems.filter(item => item.toLowerCase().includes(q)) : validItems
    return {
      ...c,
      items: filteredItems
    }
  }).filter(c => c.items.length > 0)

  // Collect leftover items
  const assigned = new Set(catList.flatMap(c => c.items.filter(item => all.includes(item))))
  const unassigned = all.filter(p => !assigned.has(p))
  const filteredUnassigned = q ? unassigned.filter(p => p.toLowerCase().includes(q)) : unassigned

  if (filteredUnassigned.length > 0) {
    categorized.push({
      key: 'other',
      name: i18n.t('cat_other', 'General Permissions'),
      iconUrl: '/images/nav/settings.svg',
      items: filteredUnassigned
    })
  }

  return categorized
})

const collapsedCategories = ref<Record<string, boolean>>({})

const toggleCategoryCollapse = (key: string) => {
  collapsedCategories.value[key] = !collapsedCategories.value[key]
}

const expandAllCategories = () => {
  collapsedCategories.value = {}
}

const collapseAllCategories = () => {
  permissionCategories.value.forEach(c => {
    collapsedCategories.value[c.key] = true
  })
}

const getRoleIconUrl = (roleName: string) => {
  switch (roleName.toLowerCase()) {
    case 'admin': return '/images/nav/sub/admins.svg'
    case 'teacher':
    case 'instructor': return '/images/nav/sub/teachers.svg'
    case 'student': return '/images/nav/sub/students.svg'
    default: return '/images/nav/sub/roles.svg'
  }
}

const getRoleUserBtnLabel = (roleName: string) => {
  switch (roleName.toLowerCase()) {
    case 'admin':
      return i18n.t('role_btn_admins', 'View Admins')
    case 'teacher':
    case 'instructor':
      return i18n.t('role_btn_teachers', 'View Teachers')
    case 'student':
      return i18n.t('role_btn_students', 'View Students')
    default:
      return i18n.t('role_btn_users', 'View Users')
  }
}

const getRoleHeaderColor = (roleName: string) => {
  switch (roleName.toLowerCase()) {
    case 'admin': return 'border-t-2 border-indigo-500 text-indigo-600 dark:text-indigo-300'
    case 'teacher':
    case 'instructor': return 'border-t-2 border-emerald-500 text-emerald-600 dark:text-emerald-300'
    case 'student': return 'border-t-2 border-sky-500 text-sky-600 dark:text-sky-300'
    default: return 'border-t-2 border-amber-500 text-amber-600 dark:text-amber-300'
  }
}

const getRoleAccessLabel = (roleName: string) => {
  switch (roleName.toLowerCase()) {
    case 'admin': return 'Full System Access'
    case 'teacher':
    case 'instructor': return 'Course Management'
    case 'student': return 'Student Portal Access'
    default: return 'Custom Security Scope'
  }
}

const togglePermission = (role: string, perm: string) => {
  const list = activePermissions.value[role] || []
  if (list.includes(perm)) {
    activePermissions.value[role] = list.filter(p => p !== perm)
  } else {
    activePermissions.value[role] = [...list, perm]
  }
}

const isPermActive = (role: string, perm: string) => {
  return (activePermissions.value[role] || []).includes(perm)
}

const toggleCategoryForRole = (role: string, items: string[]) => {
  const current = activePermissions.value[role] || []
  const allActive = items.every(item => current.includes(item))
  
  if (allActive) {
    activePermissions.value[role] = current.filter(p => !items.includes(p))
  } else {
    const combined = new Set([...current, ...items])
    activePermissions.value[role] = Array.from(combined)
  }
}

const isCategoryAllActiveForRole = (role: string, items: string[]) => {
  const current = activePermissions.value[role] || []
  return items.length > 0 && items.every(item => current.includes(item))
}

const getRoleCategorySummary = (role: string, items: string[]) => {
  const current = activePermissions.value[role] || []
  const activeCount = items.filter(item => current.includes(item)).length
  const totalCount = items.length
  return {
    activeCount,
    totalCount,
    isAll: activeCount === totalCount && totalCount > 0,
    isNone: activeCount === 0
  }
}

const saveAllMatrixPermissions = () => {
  props.rolesPermissions.forEach(r => {
    router.post('/admin/auth-logs/permissions', {
      role: r.role,
      permissions: activePermissions.value[r.role] || []
    }, {
      preserveScroll: true
    })
  })
}

const resetPermissions = () => {
  const orig: Record<string, string[]> = {}
  props.rolesPermissions.forEach(r => {
    orig[r.role] = [...(r.permissions || [])]
  })
  activePermissions.value = orig
}

const deleteRole = (roleName: string) => {
  if (confirm(`Are you sure you want to delete the custom role '${roleName}'?`)) {
    router.post('/admin/auth-logs/roles/delete', { name: roleName }, {
      preserveScroll: true
    })
  }
}

const cloneRole = (role: any) => {
  createRoleForm.name = `${role.role} Copy`
  createRoleForm.code = `${role.role_code}_COPY`
  createRoleForm.description = `Cloned from ${role.role}`
  createRoleForm.permissions = [...(role.permissions || [])]
  isCreateModalOpen.value = true
}

const exportPermissionsCSV = () => {
  const headers = ['Permission', ...props.rolesPermissions.map(r => r.role)]
  const rows = permissionsList.value.map(perm => [
    perm,
    ...props.rolesPermissions.map(r => isPermActive(r.role, perm) ? 'ALLOWED' : 'DENIED')
  ])

  const csvContent = 'data:text/csv;charset=utf-8,'
    + [headers.join(','), ...rows.map(e => e.join(','))].join('\n')

  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `elms_roles_permissions_matrix.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const submitCreateRole = () => {
  const newRoleName = createRoleForm.name || 'តួនាទីថ្មី'
  createRoleForm.post('/admin/auth-logs/roles/create', {
    preserveScroll: true,
    onSuccess: () => {
      isCreateModalOpen.value = false
      createRoleForm.reset()
      triggerToast(
        'បង្កើតតួនាទីបានជោគជ័យ',
        `តួនាទី "${newRoleName}" ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
      )
    },
    onError: () => {
      triggerToast(
        'មានបញ្ហាក្នុងការបង្កើត',
        'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលម្ដងទៀត',
        'warning'
      )
    }
  })
}
</script>

<template>
  <AdminLayout title="Roles & Permissions Management">
    <div class="space-y-6 font-sans pb-16">
      <!-- Shared Header -->
      <AuthModuleHeader activeTab="roles" :summaryStats="props.summaryStats" />

      <!-- Section Actions Bar (Clean, with Flaticon vector icon) -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white dark:bg-slate-900/60 p-3.5 sm:p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-md">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-indigo-500/15 border border-indigo-500/30 p-1.5 flex items-center justify-center shrink-0 shadow-inner">
            <img :src="'/images/nav/sub/roles.svg'" alt="Roles" class="w-5 h-5 object-contain" />
          </div>
          <div>
            <h2 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">
              {{ i18n.t('auth_card1_title', 'Roles & Permissions Matrix') }}
            </h2>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">Configure role privileges and system access rights</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="exportPermissionsCSV"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 font-medium text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-sm cursor-pointer"
          >
            <img :src="'/images/nav/sub/import-export.svg'" alt="Export" class="w-4 h-4 object-contain" />
            <span>Export CSV</span>
          </button>

          <button
            @click="isCreateModalOpen = true"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/25 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span class="font-mono text-sm">+</span>
            <span>{{ i18n.t('create_new_role', 'Create New Role') }}</span>
          </button>
        </div>
      </div>

      <!-- Clean Roles Cards Grid (👑 ADMIN | 👨‍🏫 TEACHER | 👨‍🎓 STUDENT) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div
          v-for="item in props.rolesPermissions"
          :key="item.role"
          class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800/90 rounded-2xl p-4 space-y-3 flex flex-col justify-between backdrop-blur-xl hover:border-indigo-500/40 transition-all group shadow-sm dark:shadow-md"
        >
          <div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 p-2 flex items-center justify-center shrink-0 shadow-inner">
                  <img :src="getRoleIconUrl(item.role)" :alt="item.role" class="w-6 h-6 object-contain" />
                </div>
                <div>
                  <h3 class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-tight">{{ item.role }}</h3>
                  <span class="text-[10px] font-mono text-indigo-600 dark:text-indigo-300">{{ item.role_code }}</span>
                </div>
              </div>

              <span class="px-2.5 py-0.5 text-[10px] font-bold bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/20 dark:border-emerald-500/30 rounded-full">
                {{ item.status }}
              </span>
            </div>

            <div class="mt-3 p-2 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 text-xs flex justify-between items-center">
              <span class="text-slate-500 dark:text-slate-400">{{ i18n.t('role_access_scope', 'Access Scope') }}:</span>
              <span class="font-semibold text-slate-800 dark:text-slate-200">{{ getRoleAccessLabel(item.role) }}</span>
            </div>

            <div class="mt-1.5 p-2 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 text-xs flex justify-between items-center font-mono">
              <span class="text-slate-500 dark:text-slate-400">{{ i18n.t('role_active_users', 'Active Users') }}:</span>
              <span class="font-bold text-indigo-600 dark:text-indigo-300">{{ item.user_count.toLocaleString() }} Users</span>
            </div>
          </div>

          <!-- Clean Action Icon Buttons Footer (NO confusing Save Role Matrix) -->
          <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-1.5">
            <div class="flex items-center gap-1.5">
              <button
                @click="openEditRoleModal(item)"
                title="Edit Role Settings"
                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 text-xs font-medium rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
              >
                <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>{{ i18n.t('role_btn_edit', 'Edit') }}</span>
              </button>

              <button
                @click="cloneRole(item)"
                title="Clone Role"
                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 text-xs font-medium rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
              >
                <svg class="w-3.5 h-3.5 text-sky-500 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span>{{ i18n.t('role_btn_clone', 'Clone') }}</span>
              </button>
            </div>

            <div class="flex items-center gap-1.5">
              <Link
                :href="`/admin/user-management/all?role=${encodeURIComponent(item.role)}`"
                :title="i18n.t('role_users_tooltip', 'Click to view full user list assigned to this role')"
                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/20 text-xs font-medium rounded-lg transition-all flex items-center gap-1.5"
              >
                <img :src="'/images/nav/users.svg'" alt="Users" class="w-3.5 h-3.5 object-contain" />
                <span>{{ getRoleUserBtnLabel(item.role) }}</span>
              </Link>

              <button
                v-if="!['Admin', 'Teacher', 'Student'].includes(item.role)"
                @click="deleteRole(item.role)"
                title="Delete Custom Role"
                class="p-1 bg-red-50 hover:bg-red-100 dark:bg-red-600/20 dark:hover:bg-red-500/30 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30 rounded-lg transition-all cursor-pointer"
              >
                <svg class="w-3.5 h-3.5 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ULTRA-PREMIUM PERMISSION MATRIX SECTION WITH FLATICON VECTORS -->
      <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 space-y-4 backdrop-blur-xl shadow-sm dark:shadow-2xl">
        <!-- Matrix Top Controls: Search Filter & Global Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/80 pb-4">
          <div class="space-y-1">
            <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2 tracking-tight">
              <img :src="'/images/nav/sub/roles.svg'" alt="Matrix" class="w-5 h-5 object-contain" />
              <span>PERMISSION MATRIX</span>
              <span class="text-xs font-normal text-slate-500 dark:text-slate-400 font-mono">({{ permissionsList.length }} Features)</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Toggle granular feature access for each security role using modern controls.</p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <!-- Sleek Live Search Filter Box -->
            <div class="relative min-w-[200px] sm:min-w-[240px]">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Filter permissions..."
                class="w-full bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 focus:border-indigo-500 text-xs text-slate-900 dark:text-white placeholder-slate-400 rounded-xl pl-8 pr-3 py-1.5 focus:outline-none transition-all shadow-inner"
              />
              <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 dark:hover:text-white text-xs font-bold">✕</button>
            </div>

            <!-- Collapse / Expand All Quick Buttons -->
            <div class="flex items-center bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-xl p-0.5">
              <button
                @click="expandAllCategories"
                class="px-2.5 py-1 text-[11px] font-semibold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer"
                title="Expand All Categories"
              >
                Expand
              </button>
              <span class="text-slate-300 dark:text-slate-700">|</span>
              <button
                @click="collapseAllCategories"
                class="px-2.5 py-1 text-[11px] font-semibold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer"
                title="Collapse All Categories"
              >
                Collapse
              </button>
            </div>

            <button
              v-if="hasUnsavedChanges"
              @click="resetPermissions"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs rounded-xl border border-slate-200 dark:border-slate-700 transition-all cursor-pointer"
            >
              ↺ Reset
            </button>

            <button
              @click="saveAllMatrixPermissions"
              class="px-4 py-1.5 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 hover:from-indigo-500 hover:to-purple-500 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-1.5 cursor-pointer"
            >
              <span>💾 Save Matrix</span>
            </button>
          </div>
        </div>

        <!-- Sticky Header Matrix Table with Flaticon Vectors -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800/90 bg-white dark:bg-slate-950/70 max-h-[620px] custom-scrollbar shadow-sm dark:shadow-inner">
          <table class="w-full text-left border-collapse">
            <!-- STICKY TABLE HEADER WITH ROLE THEME ACCENTS -->
            <thead class="sticky top-0 z-30 bg-slate-50/95 dark:bg-slate-900/95 backdrop-blur-xl border-b border-slate-200 dark:border-slate-700/80 shadow-md">
              <tr class="text-[11px] font-black uppercase tracking-wider">
                <th class="py-3.5 px-5 min-w-[280px] text-slate-700 dark:text-slate-300 bg-slate-50/95 dark:bg-slate-900/95 border-b border-slate-200 dark:border-slate-700/80">
                  <div class="flex items-center gap-2">
                    <img :src="'/images/nav/auth.svg'" alt="Key" class="w-4 h-4 object-contain shrink-0" />
                    <span>PERMISSION FEATURE</span>
                  </div>
                </th>
                <th
                  v-for="r in props.rolesPermissions"
                  :key="r.role"
                  class="py-3.5 px-5 text-center min-w-[140px] bg-slate-50/95 dark:bg-slate-900/95 border-b border-slate-200 dark:border-slate-700/80"
                  :class="getRoleHeaderColor(r.role)"
                >
                  <div class="flex items-center justify-center gap-2">
                    <img :src="getRoleIconUrl(r.role)" :alt="r.role" class="w-4 h-4 object-contain shrink-0" />
                    <span>{{ r.role.toUpperCase() }}</span>
                  </div>
                </th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
              <template v-for="cat in permissionCategories" :key="cat.key">
                <!-- CLEAN, BALANCED & STANDARD CATEGORY ACCORDION HEADER ROW -->
                <tr
                  class="bg-slate-50/90 dark:bg-slate-900/90 border-y border-slate-200 dark:border-slate-800/80 font-bold select-none transition-colors group hover:bg-slate-100 dark:hover:bg-slate-850"
                >
                  <td class="py-2.5 px-5 text-indigo-600 dark:text-indigo-300" colspan="1">
                    <button
                      @click="toggleCategoryCollapse(cat.key)"
                      class="flex items-center gap-2.5 hover:text-indigo-700 dark:hover:text-white transition-colors focus:outline-none w-full text-left cursor-pointer"
                    >
                      <img :src="cat.iconUrl" :alt="cat.name" class="w-4 h-4 object-contain shrink-0 opacity-85 group-hover:opacity-100 transition-opacity" />

                      <div class="flex items-center gap-2">
                        <span class="font-extrabold uppercase text-[11px] tracking-wider text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-300 transition-colors">
                          {{ cat.name }}
                        </span>
                        <span class="text-[10px] font-mono px-2 py-0.2 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-700/60">
                          {{ cat.items.length }}
                        </span>
                      </div>

                      <div class="ml-auto flex items-center gap-1 text-xs text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300">
                        <svg
                          class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-500 dark:group-hover:text-indigo-400 transition-transform duration-200"
                          :class="collapsedCategories[cat.key] ? '-rotate-90 text-slate-400 dark:text-slate-500' : 'rotate-0 text-indigo-600 dark:text-indigo-400'"
                          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                        >
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                    </button>
                  </td>

                  <!-- Batch Category Toggle & Standard Coverage Pill for Each Role -->
                  <td
                    v-for="r in props.rolesPermissions"
                    :key="r.role"
                    class="py-2.5 px-5 text-center"
                  >
                    <button
                      @click="toggleCategoryForRole(r.role, cat.items)"
                      :title="isCategoryAllActiveForRole(r.role, cat.items) ? 'Revoke all in ' + cat.name : 'Allow all in ' + cat.name"
                      class="text-[10px] font-semibold px-2.5 py-0.5 rounded-md border transition-all inline-flex items-center gap-1 cursor-pointer"
                      :class="[
                        getRoleCategorySummary(r.role, cat.items).isAll
                          ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/25'
                          : getRoleCategorySummary(r.role, cat.items).isNone
                            ? 'bg-slate-100 dark:bg-slate-800/40 text-slate-500 border-slate-200 dark:border-slate-700/40 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300'
                            : 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border-indigo-500/30 hover:bg-indigo-500/25'
                      ]"
                    >
                      <span>
                        {{
                          getRoleCategorySummary(r.role, cat.items).isAll
                            ? '✓ All (' + getRoleCategorySummary(r.role, cat.items).activeCount + '/' + getRoleCategorySummary(r.role, cat.items).totalCount + ')'
                            : getRoleCategorySummary(r.role, cat.items).isNone
                              ? 'Off (0/' + getRoleCategorySummary(r.role, cat.items).totalCount + ')'
                              : '⚡ Batch (' + getRoleCategorySummary(r.role, cat.items).activeCount + '/' + getRoleCategorySummary(r.role, cat.items).totalCount + ')'
                        }}
                      </span>
                    </button>
                  </td>
                </tr>

                <!-- Category Permission Feature Items with Clean Vector Icons -->
                <template v-if="!collapsedCategories[cat.key]">
                  <tr
                    v-for="perm in cat.items"
                    :key="perm"
                    class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors group"
                  >
                    <!-- Feature Name Column with Clean Vector Icon -->
                    <td class="py-2.5 px-5 font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-2.5">
                      <img :src="getPermissionIcon(perm)" :alt="perm" class="w-4 h-4 object-contain shrink-0 opacity-85 group-hover:opacity-100 group-hover:scale-110 transition-all" />
                      <span class="group-hover:text-indigo-600 dark:group-hover:text-white transition-colors text-xs">{{ perm }}</span>
                    </td>

                    <!-- Modern Glassmorphic Toggle Switch per Role -->
                    <td
                      v-for="r in props.rolesPermissions"
                      :key="r.role"
                      class="py-2.5 px-5 text-center"
                    >
                      <div class="flex items-center justify-center">
                        <button
                          @click="togglePermission(r.role, perm)"
                          type="button"
                          :title="isPermActive(r.role, perm) ? 'Click to revoke ' + perm : 'Click to allow ' + perm"
                          :class="[
                            isPermActive(r.role, perm)
                              ? 'bg-emerald-500 shadow-sm shadow-emerald-500/20'
                              : 'bg-slate-200 dark:bg-slate-800 border border-slate-300 dark:border-slate-700/80 hover:border-slate-400 dark:hover:border-slate-600',
                            'relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none select-none'
                          ]"
                        >
                          <span
                            :class="[
                              isPermActive(r.role, perm) ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-white dark:bg-slate-400 shadow-sm',
                              'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow transition duration-200 ease-in-out my-auto font-bold text-[10px] flex items-center justify-center'
                            ]"
                          >
                            <svg v-if="isPermActive(r.role, perm)" class="w-2.5 h-2.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else class="w-1 h-1 rounded-full bg-slate-400 dark:bg-slate-600"></span>
                          </span>
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Matrix Bottom Helper Status Bar -->
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400">
          <div class="flex items-center gap-2">
            <span class="text-indigo-600 dark:text-indigo-400 font-bold">💡 UX Tip:</span>
            <span>Use the toggle switches to grant or revoke features. Changes take effect when you click <strong>Save Matrix</strong>.</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="exportPermissionsCSV"
              class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 font-medium text-xs rounded-xl transition-all shadow-sm cursor-pointer"
            >
              📤 Export CSV
            </button>
          </div>
        </div>
      </div>

      <!-- STICKY FLOATING ACTION BAR (Appears automatically when changes are made) -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform opacity-0 translate-y-4"
        enter-to-class="transform opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform opacity-100 translate-y-0"
        leave-to-class="transform opacity-0 translate-y-4"
      >
        <div
          v-if="hasUnsavedChanges"
          class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-indigo-500/50 rounded-2xl px-5 py-3 shadow-2xl flex items-center gap-4 ring-1 ring-indigo-500/30"
        >
          <div class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-200">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
            <span class="font-bold text-slate-900 dark:text-white">Unsaved Matrix Changes:</span>
            <span class="text-slate-500 dark:text-slate-400">You have modified role permissions.</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="resetPermissions"
              class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs rounded-xl border border-slate-200 dark:border-slate-700 transition-all cursor-pointer"
            >
              ↺ Reset
            </button>

            <button
              @click="saveAllMatrixPermissions"
              class="px-4 py-1.5 bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 hover:from-indigo-500 hover:to-purple-500 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-1.5 cursor-pointer"
            >
              <span>💾 Save Matrix Permissions</span>
            </button>
          </div>
        </div>
      </Transition>

      <!-- Create / Clone Role Modal -->
      <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-md">
        <div class="bg-white dark:bg-slate-900/95 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 w-full max-w-lg space-y-5 shadow-2xl backdrop-blur-xl">
          <div class="flex items-start justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 p-2 flex items-center justify-center shrink-0 shadow-inner">
                <img :src="'/images/nav/sub/roles.svg'" alt="Role" class="w-6 h-6 object-contain" />
              </div>
              <div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">
                  {{ i18n.t('modal_create_role_title', 'Create / Clone Security Role') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  {{ i18n.t('modal_create_role_desc', 'Configure role details, system identifier code, and status') }}
                </p>
              </div>
            </div>
            <button @click="isCreateModalOpen = false" class="text-slate-400 hover:text-slate-700 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">✕</button>
          </div>

          <form @submit.prevent="submitCreateRole" class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ i18n.t('modal_role_name', 'Role Name') }} <span class="text-indigo-500 dark:text-indigo-400">*</span>
              </label>
              <input
                v-model="createRoleForm.name"
                type="text"
                :placeholder="i18n.t('modal_role_name_placeholder', 'e.g., Financial Auditor, Academic Supervisor')"
                required
                class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white text-xs placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none transition-all"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ i18n.t('modal_role_code', 'Role Code') }} <span class="text-indigo-500 dark:text-indigo-400">*</span>
              </label>
              <input
                v-model="createRoleForm.code"
                type="text"
                :placeholder="i18n.t('modal_role_code_placeholder', 'e.g., ROLE_AUDITOR')"
                required
                class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-3.5 py-2.5 text-indigo-600 dark:text-indigo-300 font-mono text-xs placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none transition-all"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ i18n.t('modal_description', 'Description') }}
              </label>
              <textarea
                v-model="createRoleForm.description"
                rows="3"
                :placeholder="i18n.t('modal_description_placeholder', 'Specify purpose of this security role...')"
                class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white text-xs placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none transition-all"
              ></textarea>
            </div>

            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ i18n.t('modal_status', 'Status') }}
              </label>
              <select v-model="createRoleForm.status" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 focus:border-indigo-500 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white text-xs cursor-pointer focus:outline-none">
                <option value="Active">{{ i18n.t('modal_status_active', 'Active') }}</option>
                <option value="Disabled">{{ i18n.t('modal_status_disabled', 'Disabled') }}</option>
              </select>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
              <button
                type="button"
                @click="isCreateModalOpen = false"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs rounded-xl border border-slate-200 dark:border-slate-700/80 transition-all cursor-pointer"
              >
                {{ i18n.t('modal_btn_cancel', 'Cancel') }}
              </button>
              <button
                type="submit"
                :disabled="createRoleForm.processing"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer"
              >
                <svg
                  v-if="createRoleForm.processing"
                  class="animate-spin h-3.5 w-3.5 text-white"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="createRoleForm.processing" class="font-medium text-xs">កំពុងបង្កើត...</span>
                <span v-else class="flex items-center gap-1">
                  <span>+</span>
                  <span>{{ i18n.t('modal_btn_create', 'បង្កើតតួនាទី') }}</span>
                </span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Edit Role Modal -->
      <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-md">
        <div class="bg-white dark:bg-slate-900/95 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 w-full max-w-lg space-y-5 shadow-2xl backdrop-blur-xl">
          <div class="flex items-start justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 p-2 flex items-center justify-center shrink-0 shadow-inner">
                <img :src="'/images/nav/sub/roles.svg'" alt="Edit" class="w-6 h-6 object-contain" />
              </div>
              <div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">
                  {{ i18n.t('modal_edit_role_title', 'Edit Role Settings') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  {{ i18n.t('modal_edit_role_desc', 'Update name, system identifier, and operational status for this role') }}
                </p>
              </div>
            </div>
            <button @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-700 dark:hover:text-white p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">✕</button>
          </div>

          <form @submit.prevent="submitEditRole" class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ i18n.t('modal_role_name', 'Role Name') }} <span class="text-indigo-500 dark:text-indigo-400">*</span>
              </label>
              <input
                v-model="editRoleForm.name"
                type="text"
                required
                class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white text-xs focus:outline-none transition-all"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ i18n.t('modal_role_code', 'Role Code') }} <span class="text-indigo-500 dark:text-indigo-400">*</span>
              </label>
              <input
                v-model="editRoleForm.code"
                type="text"
                required
                class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-3.5 py-2.5 text-indigo-600 dark:text-indigo-300 font-mono text-xs focus:outline-none transition-all"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ i18n.t('modal_description', 'Description') }}
              </label>
              <textarea
                v-model="editRoleForm.description"
                rows="3"
                class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white text-xs focus:outline-none transition-all"
              ></textarea>
            </div>

            <div>
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ i18n.t('modal_status', 'Status') }}
              </label>
              <select v-model="editRoleForm.status" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 focus:border-indigo-500 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white text-xs cursor-pointer focus:outline-none">
                <option value="Active">{{ i18n.t('modal_status_active', 'Active') }}</option>
                <option value="Disabled">{{ i18n.t('modal_status_disabled', 'Disabled') }}</option>
              </select>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
              <button
                type="button"
                @click="isEditModalOpen = false"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs rounded-xl border border-slate-200 dark:border-slate-700/80 transition-all cursor-pointer"
              >
                {{ i18n.t('modal_btn_cancel', 'Cancel') }}
              </button>
              <button
                type="submit"
                :disabled="editRoleForm.processing"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer"
              >
                <svg
                  v-if="editRoleForm.processing"
                  class="animate-spin h-3.5 w-3.5 text-white"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="editRoleForm.processing" class="font-medium text-xs">កំពុងរក្សាទុក...</span>
                <span v-else class="flex items-center gap-1">
                  <span>{{ i18n.t('modal_btn_save', 'រក្សាទុក') }}</span>
                </span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Clean & Subtle Toast Notification -->
      <Teleport to="body">
        <Transition
          enter-active-class="transform ease-out duration-250 transition"
          enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-3"
          enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="toast.show"
            class="fixed top-5 right-5 z-[9999] max-w-sm w-full pointer-events-auto"
          >
            <div
              :class="[
                toast.type === 'success'
                  ? 'bg-slate-800/95 border-slate-700/80 text-white'
                  : 'bg-slate-800/95 border-amber-500/40 text-white',
                'relative rounded-xl border p-3 shadow-xl backdrop-blur-md'
              ]"
            >
              <div class="flex items-center gap-3">
                <!-- Rich Flaticon Vector Badge Icon -->
                <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center filter drop-shadow-sm">
                  <img
                    v-if="toast.type === 'success'"
                    :src="'/images/actions/toast-success.svg'"
                    alt="Success"
                    class="w-full h-full object-contain"
                  />
                  <img
                    v-else-if="toast.type === 'warning'"
                    :src="'/images/actions/toast-warning.svg'"
                    alt="Warning"
                    class="w-full h-full object-contain"
                  />
                  <img
                    v-else
                    :src="'/images/actions/toast-info.svg'"
                    alt="Info"
                    class="w-full h-full object-contain"
                  />
                </div>

                <div class="flex-1 min-w-0">
                  <h4 class="text-xs font-bold text-white tracking-tight leading-snug">
                    {{ toast.title }}
                  </h4>
                  <p class="text-[11px] text-slate-300 mt-0.5 leading-normal">
                    {{ toast.message }}
                  </p>
                </div>

                <button
                  @click="toast.show = false"
                  class="text-slate-400 hover:text-white p-1 rounded-md hover:bg-slate-700/60 transition-colors shrink-0 cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </AdminLayout>
</template>
