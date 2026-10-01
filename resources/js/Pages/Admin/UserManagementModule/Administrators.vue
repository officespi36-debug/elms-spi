<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import UserModuleHeader from '@/Components/Admin/UserModuleHeader.vue'

const props = withDefaults(defineProps<{
  administrators?: Array<any>
  departments?: Array<any>
  summaryStats?: any
}>(), {
  administrators: () => [],
  departments: () => [],
  summaryStats: () => ({})
})

// Search & Filter state
const search = ref('')
const selectedRoleFilter = ref('')
const selectedStatusFilter = ref('')

// Modals state
const showCreateEditModal = ref(false)
const showProfileModal = ref(false)
const viewingAdmin = ref<any | null>(null)
const isEditMode = ref(false)
const showPassword = ref(false)
const adminRoleType = ref<'super_admin' | 'admin'>('admin')

// Toast notification state
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

// Admin Form
const adminForm = useForm({
  id: null as number | null,
  role: 'admin',
  student_code: '', // Admin ID (e.g. ADM-2026-001)
  name: '',
  name_kh: '',
  email: '',
  password: '',
  phone: '',
  qualification: 'super_admin', // Store role type: 'super_admin' | 'admin'
  expertise: 'Super Admin',     // Readable title
  status: 'active'
})

// Helper: Determine if user is Super Admin
const isSuperAdmin = (admin: any) => {
  if (!admin) return false
  return (
    admin.qualification === 'super_admin' ||
    admin.expertise === 'Super Admin' ||
    admin.id === 1 ||
    admin.name?.toLowerCase().includes('system admin') ||
    admin.name?.toLowerCase().includes('super admin')
  )
}

// System permissions definition per User Spec
const superAdminModules = [
  { name: '👥 User Management', desc: 'គ្រប់គ្រង Students, Teachers, Admins & Roles', capabilities: ['View', 'Create', 'Edit', 'Delete', 'Approve', 'Manage'] },
  { name: '🏫 Academic Structure', desc: 'គ្រប់គ្រង Faculties, Departments, Majors & Academic Years', capabilities: ['View', 'Create', 'Edit', 'Delete', 'Manage'] },
  { name: '📚 Course Management', desc: 'គ្រប់គ្រង Courses, Subjects, Lessons, Materials & Approvals', capabilities: ['View', 'Create', 'Edit', 'Delete', 'Approve', 'Manage'] },
  { name: '📝 Assessment & Quizzes', desc: 'គ្រប់គ្រង Quizzes, Assignments, Question Banks & Grading', capabilities: ['View', 'Create', 'Edit', 'Delete', 'Grade', 'Manage'] },
  { name: '📈 Learning & Progress', desc: 'តាមដាន Course Progress, Student Scores & Completion', capabilities: ['View', 'Track', 'Export', 'Manage'] },
  { name: '📊 Analytics & Reports', desc: 'របាយការណ៍ស្ថិតិទូទៅ ការចុះឈ្មោះ និងលទ្ធផលសិក្សា', capabilities: ['View', 'Generate', 'Export'] },
  { name: '🤖 AI Management', desc: 'គ្រប់គ្រង AI Quiz Generator, Student Risk Predictor & Tutor', capabilities: ['View', 'Configure', 'Train', 'Manage'] },
  { name: '📢 Communication', desc: 'សេចក្តីជូនដំណឹង Notifications, Discussions & Telegram Bot', capabilities: ['Broadcast', 'Send', 'Manage'] },
  { name: '⚙️ System Settings', desc: 'កំណត់រចនាសម្ព័ន្ធប្រព័ន្ធ ភាសា Timezone & Backup', capabilities: ['Configure', 'Backup', 'Restore', 'Manage'] },
  { name: '🔐 Roles & Permissions', desc: 'កំណត់សិទ្ធិ Role Check, Access Matrix & Audit Logs', capabilities: ['Configure', 'Audit', 'Manage'] }
]

const standardAdminModules = [
  { name: '👥 User Management', desc: 'គ្រប់គ្រង Students, Teachers & Standard Admins', capabilities: ['View', 'Create', 'Edit', 'Approve'] },
  { name: '🏫 Academic Structure', desc: 'មើល និងគ្រប់គ្រង Majors, Departments & Academic Years', capabilities: ['View', 'Edit', 'Manage'] },
  { name: '📚 Course Management', desc: 'ពិនិត្យ និងអនុម័ត Courses, Lessons & Materials', capabilities: ['View', 'Approve', 'Manage'] },
  { name: '📝 Assessment & Quizzes', desc: 'ត្រួតពិនិត្យ Quizzes, Assignments & Performance Results', capabilities: ['View', 'Monitor', 'Export'] },
  { name: '📈 Learning & Progress', desc: 'តាមដាន Course Progress & Student Attendance', capabilities: ['View', 'Track', 'Export'] },
  { name: '📊 Analytics & Reports', desc: 'របាយការណ៍ស្ថិតិទូទៅ និងការចុះឈ្មោះ', capabilities: ['View', 'Generate', 'Export'] },
  { name: '📢 Communication', desc: 'ផ្ញើសេចក្តីជូនដំណឹង Announcements ដល់សិស្ស និងគ្រូ', capabilities: ['Broadcast', 'Send'] }
]

// Computed Filtered Admins
const filteredAdmins = computed(() => {
  return props.administrators.filter(admin => {
    // 1. Search Query
    const query = search.value.toLowerCase().trim()
    const matchesSearch = !query || (
      (admin.name && admin.name.toLowerCase().includes(query)) ||
      (admin.name_kh && admin.name_kh.toLowerCase().includes(query)) ||
      (admin.email && admin.email.toLowerCase().includes(query)) ||
      (admin.phone && admin.phone.toLowerCase().includes(query)) ||
      (admin.student_code && admin.student_code.toLowerCase().includes(query)) ||
      `#${admin.id}`.includes(query)
    )

    // 2. Role Filter
    const isAdminSuper = isSuperAdmin(admin)
    const matchesRole = !selectedRoleFilter.value || (
      selectedRoleFilter.value === 'super_admin' ? isAdminSuper : !isAdminSuper
    )

    // 3. Status Filter
    const currentStatus = (admin.status || (admin.is_active ? 'active' : 'inactive')).toLowerCase()
    const matchesStatus = !selectedStatusFilter.value || currentStatus === selectedStatusFilter.value.toLowerCase()

    return matchesSearch && matchesRole && matchesStatus
  })
})

// Open Add Admin Modal
const openCreateModal = () => {
  isEditMode.value = false
  adminForm.reset()
  adminForm.id = null
  adminForm.role = 'admin'

  const nextNum = props.administrators.length + 1
  adminForm.student_code = `ADM-2026-${String(nextNum).padStart(3, '0')}`
  adminForm.name = ''
  adminForm.name_kh = ''
  adminForm.email = ''
  adminForm.password = ''
  adminForm.phone = ''
  adminRoleType.value = 'admin'
  adminForm.qualification = 'admin'
  adminForm.expertise = 'Admin'
  adminForm.status = 'active'
  showPassword.value = false

  showCreateEditModal.value = true
}

// Open Edit Admin Modal
const openEditModal = (admin: any) => {
  isEditMode.value = true
  adminForm.reset()
  adminForm.id = admin.id
  adminForm.role = 'admin'
  adminForm.student_code = admin.student_code || `ADM-2026-${String(admin.id).padStart(3, '0')}`
  adminForm.name = admin.name || ''
  adminForm.name_kh = admin.name_kh || ''
  adminForm.email = admin.email || ''
  adminForm.password = ''
  adminForm.phone = admin.phone || ''
  
  const superAdmin = isSuperAdmin(admin)
  adminRoleType.value = superAdmin ? 'super_admin' : 'admin'
  adminForm.qualification = superAdmin ? 'super_admin' : 'admin'
  adminForm.expertise = superAdmin ? 'Super Admin' : 'Admin'
  adminForm.status = admin.status || (admin.is_active ? 'active' : 'inactive')
  showPassword.value = false

  if (showProfileModal.value) {
    showProfileModal.value = false
  }
  showCreateEditModal.value = true
}

// Open View Admin Profile Modal
const openProfileModal = (admin: any) => {
  viewingAdmin.value = admin
  showProfileModal.value = true
}

// When role radio changes in modal
const onRoleChange = (role: 'super_admin' | 'admin') => {
  adminRoleType.value = role
  adminForm.qualification = role
  adminForm.expertise = role === 'super_admin' ? 'Super Admin' : 'Admin'
}

// Save Admin (Create or Update)
const saveAdmin = () => {
  const adminName = adminForm.name || 'Administrator'
  adminForm.qualification = adminRoleType.value
  adminForm.expertise = adminRoleType.value === 'super_admin' ? 'Super Admin' : 'Admin'

  if (isEditMode.value && adminForm.id) {
    adminForm.put(`/admin/users/${adminForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        showCreateEditModal.value = false
        triggerToast(
          'កែសម្រួលបានជោគជ័យ (Saved)',
          `ព័ត៌មានអ្នកគ្រប់គ្រង "${adminName}" (${adminForm.student_code}) ត្រូវបានកែសម្រួលដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast('មានបញ្ហាក្នុងការរក្សាទុក', 'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ', 'warning')
      }
    })
  } else {
    adminForm.post('/admin/users', {
      preserveScroll: true,
      onSuccess: () => {
        showCreateEditModal.value = false
        adminForm.reset()
        triggerToast(
          'បង្កើតអ្នកគ្រប់គ្រងជោគជ័យ (Created)',
          `គណនី "${adminName}" (${adminForm.student_code}) ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast('មានបញ្ហាក្នុងការបង្កើត', 'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ', 'warning')
      }
    })
  }
}

// Toggle Activate / Disable Admin (Preserve history, no hard delete)
const toggleActivateDisable = (admin: any) => {
  const isActivating = admin.status !== 'active'
  const actionText = isActivating ? 'Activate (បើកដំណើរការ)' : 'Disable / Inactive (ផ្អាកដំណើរការ)'

  if (confirm(`តើអ្នកពិតជាចង់ ${actionText} គណនីអ្នកគ្រប់គ្រង "${admin.name}" មែនទេ?`)) {
    router.post(`/admin/user-management/toggle-status/${admin.id}`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast(
          isActivating ? 'បានបើកដំណើរការ (Activated)' : 'បានផ្អាកដំណើរការ (Disabled)',
          `គណនី "${admin.name}" ត្រូវបានផ្លាស់ប្តូរទៅជា ${isActivating ? 'Active' : 'Inactive'} ដោយជោគជ័យ`
        )
        if (viewingAdmin.value && viewingAdmin.value.id === admin.id) {
          viewingAdmin.value.status = isActivating ? 'active' : 'inactive'
          viewingAdmin.value.is_active = isActivating
        }
      },
      onError: () => {
        triggerToast('បរាជ័យ', 'មិនអាចផ្លាស់ប្តូរស្ថានភាពគណនីបានទេ', 'warning')
      }
    })
  }
}

// Helper: Format Last Login
const formatLastLogin = (admin: any) => {
  if (admin.auth_logs && admin.auth_logs.length > 0) {
    const latest = admin.auth_logs[0]
    return {
      text: new Date(latest.created_at).toLocaleString(),
      isRecent: true,
      status: latest.status || 'success'
    }
  }
  return {
    text: 'Active Session',
    isRecent: true,
    status: 'success'
  }
}
</script>

<template>
  <AdminLayout title="Administrators — System Management">
    <div class="space-y-6 font-sans">
      <!-- Shared Header -->
      <UserModuleHeader activeTab="administrators" :summaryStats="props.summaryStats" />

      <!-- FILTER & TOOLBAR (Search, Role, Status, + Add Administrator) -->
      <div class="bg-white dark:bg-slate-900/70 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm backdrop-blur-xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <!-- Search Box -->
          <div class="relative flex-1 min-w-[260px]">
            <input
              v-model="search"
              type="text"
              placeholder="Search Admin ID, Name, Email, Phone..."
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-all"
            />
            <span class="absolute left-3 top-3 text-slate-400 dark:text-slate-500">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
          </div>

          <!-- Role Filter -->
          <div class="min-w-[160px]">
            <select
              v-model="selectedRoleFilter"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:border-purple-500 transition-all cursor-pointer"
            >
              <option value="">All Admin Roles</option>
              <option value="super_admin">Super Admin</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <!-- Status Filter -->
          <div class="min-w-[140px]">
            <select
              v-model="selectedStatusFilter"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:border-purple-500 transition-all cursor-pointer"
            >
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <!-- Reset Filter -->
          <button
            v-if="search || selectedRoleFilter || selectedStatusFilter"
            @click="search = ''; selectedRoleFilter = ''; selectedStatusFilter = ''"
            class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold transition-all flex items-center gap-1 cursor-pointer"
          >
            <span>✕ Reset</span>
          </button>

          <!-- Add New Administrator Button -->
          <button
            @click="openCreateModal"
            class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-md shadow-purple-600/20 transition-all flex items-center gap-2 cursor-pointer ml-auto"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Add Admin</span>
          </button>
        </div>
      </div>

      <!-- ADMINISTRATORS DATA TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 shadow-sm backdrop-blur-xl min-h-[380px]">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <th class="py-3.5 px-4 w-12 text-center">#</th>
              <th class="py-3.5 px-4">Admin ID</th>
              <th class="py-3.5 px-4">Name</th>
              <th class="py-3.5 px-4">Email & Phone</th>
              <th class="py-3.5 px-4">Role</th>
              <th class="py-3.5 px-4">Permissions Scope</th>
              <th class="py-3.5 px-4">Last Login</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            <tr v-for="(admin, idx) in filteredAdmins" :key="admin.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all group">
              <!-- Index -->
              <td class="py-3.5 px-4 text-center font-mono text-slate-400 font-medium">{{ String(idx + 1).padStart(2, '0') }}</td>

              <!-- Admin ID -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 text-purple-800 dark:text-purple-300 rounded-lg font-mono text-xs font-bold">
                  {{ admin.student_code || `ADM-2026-${String(admin.id).padStart(3, '0')}` }}
                </span>
              </td>

              <!-- Name & Avatar -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <button
                  @click="openProfileModal(admin)"
                  class="flex items-center gap-3 text-left focus:outline-none group/name"
                  title="Click to view Admin Profile & Permissions"
                >
                  <img
                    :src="admin.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(admin.name)}&background=8b5cf6&color=fff`"
                    class="w-8 h-8 rounded-full border border-purple-500/30 object-cover group-hover/name:scale-105 transition-transform"
                  />
                  <div>
                    <div class="font-bold text-slate-900 dark:text-white group-hover/name:text-purple-600 dark:group-hover/name:text-purple-400 transition-colors">
                      {{ admin.name }}
                    </div>
                    <div v-if="admin.name_kh" class="text-[11px] text-slate-400 font-medium">
                      {{ admin.name_kh }}
                    </div>
                  </div>
                </button>
              </td>

              <!-- Email & Phone -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="font-mono text-slate-700 dark:text-slate-300 text-xs">{{ admin.email }}</div>
                <div class="text-[11px] text-slate-400 font-mono mt-0.5">{{ admin.phone || '+855 12 345 678' }}</div>
              </td>

              <!-- Role (Super Admin vs Admin) -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span
                  v-if="isSuperAdmin(admin)"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-extrabold bg-gradient-to-r from-purple-100 to-indigo-100 dark:from-purple-950/60 dark:to-indigo-950/60 text-purple-700 dark:text-purple-300 border border-purple-300 dark:border-purple-500/40 rounded-full"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                  <span>Super Admin</span>
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30 rounded-full"
                >
                  <span>Admin</span>
                </span>
              </td>

              <!-- Permissions Scope -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="font-medium text-slate-800 dark:text-slate-200 text-xs">
                  {{ isSuperAdmin(admin) ? 'Full System Control (10 Modules)' : 'Standard Operation (7 Modules)' }}
                </div>
                <div class="text-[10px] text-slate-400">
                  {{ isSuperAdmin(admin) ? 'Users, Academic, Courses, AI, Settings & Roles' : 'Users, Academic, Courses, Quizzes & Reports' }}
                </div>
              </td>

              <!-- Last Login -->
              <td class="py-3.5 px-4 whitespace-nowrap font-mono">
                <div class="text-slate-700 dark:text-slate-300 font-medium text-xs">
                  {{ formatLastLogin(admin).text }}
                </div>
                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1 mt-0.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  <span>Verified Auth Session</span>
                </div>
              </td>

              <!-- Status -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span
                  v-if="(admin.status || 'active') === 'active'"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  Active
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                  Inactive
                </span>
              </td>

              <!-- Actions: View Profile 👁, Edit ✏️, Activate/Disable 🔒 -->
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- View Profile Button -->
                  <button
                    @click="openProfileModal(admin)"
                    class="h-7 px-2.5 inline-flex items-center gap-1 bg-slate-100 hover:bg-purple-50 dark:bg-slate-800 dark:hover:bg-purple-500/20 text-slate-700 dark:text-purple-300 hover:text-purple-700 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="View Admin Profile & Permissions"
                  >
                    <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View</span>
                  </button>

                  <!-- Edit Button -->
                  <button
                    @click="openEditModal(admin)"
                    class="h-7 px-2.5 inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Edit Admin"
                  >
                    <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>Edit</span>
                  </button>

                  <!-- Activate / Disable Button -->
                  <button
                    @click="toggleActivateDisable(admin)"
                    :class="[
                      (admin.status || 'active') === 'active'
                        ? 'bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/10 dark:hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/30'
                        : 'bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30',
                      'h-7 px-2.5 inline-flex items-center gap-1 border rounded-lg text-xs font-semibold transition-all cursor-pointer shadow-xs'
                    ]"
                    :title="(admin.status || 'active') === 'active' ? 'Disable Admin' : 'Activate Admin'"
                  >
                    <svg v-if="(admin.status || 'active') === 'active'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    <span>{{ (admin.status || 'active') === 'active' ? 'Disable' : 'Activate' }}</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredAdmins.length === 0">
              <td colspan="9" class="py-12 text-center text-slate-400 font-medium">
                No administrator accounts found matching search or filter criteria.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Table Pagination Footer -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="text-slate-500 dark:text-slate-400 font-mono">
            Showing <span class="text-slate-900 dark:text-white font-bold">1</span> to <span class="text-slate-900 dark:text-white font-bold">{{ filteredAdmins.length }}</span> of <span class="text-slate-900 dark:text-white font-bold">{{ props.administrators.length }}</span> administrators
          </div>

          <div class="flex items-center gap-1.5 font-mono">
            <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-xl font-semibold cursor-not-allowed" disabled>Previous</button>
            <button class="px-3 py-1.5 bg-purple-600 text-white font-bold rounded-xl shadow-sm">1</button>
            <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-xl font-semibold cursor-not-allowed" disabled>Next</button>
          </div>
        </div>
      </div>

      <!-- ADD / EDIT ADMINISTRATOR MODAL -->
      <div v-if="showCreateEditModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-900/50 rounded-3xl max-w-2xl w-full p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh]">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                  {{ isEditMode ? 'EDIT ADMINISTRATOR ACCOUNT' : 'ADD NEW ADMINISTRATOR' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{ isEditMode ? 'កែប្រែព័ត៌មាន និងសិទ្ធិគ្រប់គ្រងរបស់អ្នកគ្រប់គ្រង' : 'បង្កើតគណនីអ្នកគ្រប់គ្រងប្រព័ន្ធ ELMS និងកំណត់សិទ្ធិ' }}
                </p>
              </div>
            </div>
            <button @click="showCreateEditModal = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-all">✕</button>
          </div>

          <!-- Form Inputs -->
          <form @submit.prevent="saveAdmin" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Admin ID -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Admin ID *</label>
                <input
                  v-model="adminForm.student_code"
                  type="text"
                  required
                  placeholder="e.g. ADM-2026-001"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-mono font-bold focus:outline-none focus:border-purple-500"
                />
              </div>

              <!-- Full Name (English) -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Full Name (English) *</label>
                <input
                  v-model="adminForm.name"
                  type="text"
                  required
                  placeholder="e.g. Kosal Sensok"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-bold focus:outline-none focus:border-purple-500"
                />
              </div>

              <!-- Full Name (Khmer) -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Full Name (Khmer)</label>
                <input
                  v-model="adminForm.name_kh"
                  type="text"
                  placeholder="ឧ. កុសល សែនសុខ"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"
                />
              </div>

              <!-- Email -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Email Address *</label>
                <input
                  v-model="adminForm.email"
                  type="email"
                  required
                  placeholder="e.g. admin@spilms.tech"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-purple-500"
                />
              </div>

              <!-- Phone Number -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                <input
                  v-model="adminForm.phone"
                  type="text"
                  placeholder="e.g. +855 12 345 678"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-purple-500"
                />
              </div>

              <!-- Password -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">
                  {{ isEditMode ? 'Password (ទុកទទេបើមិនប្តូរ)' : 'Password *' }}
                </label>
                <div class="relative">
                  <input
                    v-model="adminForm.password"
                    :type="showPassword ? 'text' : 'password'"
                    :required="!isEditMode"
                    placeholder="Minimum 6 characters"
                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl pl-3.5 pr-10 py-2.5 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-purple-500"
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"
                  >
                    {{ showPassword ? '🙈' : '👁️' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Role Selector (Super Admin vs Admin) -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-3">
              <label class="block font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px]">
                Administrative Role & Authority Level *
              </label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Option 1: Super Admin -->
                <label
                  @click="onRoleChange('super_admin')"
                  :class="[
                    adminRoleType === 'super_admin'
                      ? 'border-purple-500 bg-purple-50/60 dark:bg-purple-950/40 text-purple-900 dark:text-purple-200'
                      : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300',
                    'p-3.5 rounded-xl border-2 cursor-pointer transition-all flex items-start gap-3'
                  ]"
                >
                  <input type="radio" name="adminRole" value="super_admin" :checked="adminRoleType === 'super_admin'" class="mt-0.5 text-purple-600 focus:ring-purple-500" />
                  <div>
                    <div class="font-black text-xs flex items-center gap-1.5">
                      <span>Super Admin</span>
                      <span class="text-[9px] px-1.5 py-0.2 bg-purple-600 text-white rounded font-mono">Full Access</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                      សិទ្ធិពេញលេញលើ Modules ទាំងអស់ រួមមាន System Settings, AI Management, និង Roles & Permissions។
                    </p>
                  </div>
                </label>

                <!-- Option 2: Standard Admin -->
                <label
                  @click="onRoleChange('admin')"
                  :class="[
                    adminRoleType === 'admin'
                      ? 'border-blue-500 bg-blue-50/60 dark:bg-blue-950/40 text-blue-900 dark:text-blue-200'
                      : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300',
                    'p-3.5 rounded-xl border-2 cursor-pointer transition-all flex items-start gap-3'
                  ]"
                >
                  <input type="radio" name="adminRole" value="admin" :checked="adminRoleType === 'admin'" class="mt-0.5 text-blue-600 focus:ring-blue-500" />
                  <div>
                    <div class="font-black text-xs flex items-center gap-1.5">
                      <span>Admin</span>
                      <span class="text-[9px] px-1.5 py-0.2 bg-blue-600 text-white rounded font-mono">Operational</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                      គ្រប់គ្រង Users, Academic Structure, Courses, Quizzes, Progress & Reports (គ្មានសិទ្ធិកែ Settings)។
                    </p>
                  </div>
                </label>
              </div>
            </div>

            <!-- Permissions Checklist Preview -->
            <div class="p-3.5 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                  Granted Module Privileges ({{ adminRoleType === 'super_admin' ? '10 / 10' : '7 / 10' }})
                </span>
                <span class="text-[10px] font-mono text-purple-600 font-bold">
                  {{ adminRoleType === 'super_admin' ? 'All System Capabilities Granted' : 'Restricted Security & Settings' }}
                </span>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 text-[11px]">
                <div
                  v-for="mod in (adminRoleType === 'super_admin' ? superAdminModules : standardAdminModules)"
                  :key="mod.name"
                  class="p-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-medium"
                >
                  <span class="text-emerald-500 font-bold">✓</span>
                  <span class="truncate">{{ mod.name }}</span>
                </div>
              </div>
            </div>

            <!-- Status Radio Selector -->
            <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Account Status</label>
              <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                  <input type="radio" value="active" v-model="adminForm.status" class="text-emerald-600 focus:ring-emerald-500" />
                  <span>● Active (អាច Login បាន)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-400">
                  <input type="radio" value="inactive" v-model="adminForm.status" class="text-slate-500 focus:ring-slate-400" />
                  <span>○ Inactive (មិនអាច Login បាន)</span>
                </label>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center gap-3">
              <button
                type="button"
                @click="showCreateEditModal = false"
                :disabled="adminForm.processing"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold transition-all cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="adminForm.processing"
                class="px-5 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl shadow-md shadow-purple-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <span>{{ adminForm.processing ? 'Saving...' : (isEditMode ? 'Update Administrator' : 'Create Administrator') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- VIEW ADMIN PROFILE & PERMISSIONS MODAL -->
      <div v-if="showProfileModal && viewingAdmin" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-900/50 rounded-3xl max-w-3xl w-full p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh]">
          <!-- Profile Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-4">
              <img
                :src="viewingAdmin.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(viewingAdmin.name)}&background=8b5cf6&color=fff`"
                class="w-14 h-14 rounded-2xl border-2 border-purple-500/40 object-cover shadow-sm"
              />
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ viewingAdmin.name }}</h3>
                  <span
                    v-if="isSuperAdmin(viewingAdmin)"
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-300 dark:border-purple-600"
                  >
                    Super Admin
                  </span>
                  <span
                    v-else
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-600"
                  >
                    Admin
                  </span>
                  <span
                    :class="[
                      (viewingAdmin.status || 'active') === 'active'
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400'
                        : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400',
                      'px-2 py-0.5 rounded-full text-[10px] font-bold border'
                    ]"
                  >
                    {{ (viewingAdmin.status || 'active').toUpperCase() }}
                  </span>
                </div>
                <div class="text-xs font-mono text-purple-600 dark:text-purple-400 mt-0.5">
                  Admin ID: {{ viewingAdmin.student_code || `ADM-2026-${String(viewingAdmin.id).padStart(3, '0')}` }}
                </div>
              </div>
            </div>
            <button @click="showProfileModal = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-all">✕</button>
          </div>

          <!-- Account Details -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2">
              <h4 class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Account Credentials</span>
              </h4>
              <div class="space-y-1.5 text-slate-700 dark:text-slate-300">
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Email:</span>
                  <span class="font-mono font-medium">{{ viewingAdmin.email }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Phone:</span>
                  <span class="font-mono font-medium">{{ viewingAdmin.phone || '+855 12 345 678' }}</span>
                </div>
                <div class="flex justify-between py-1">
                  <span class="text-slate-400">Created:</span>
                  <span class="font-mono font-medium">{{ new Date(viewingAdmin.created_at).toLocaleDateString() }}</span>
                </div>
              </div>
            </div>

            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2">
              <h4 class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Session & Security</span>
              </h4>
              <div class="space-y-1.5 text-slate-700 dark:text-slate-300">
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Role Authority:</span>
                  <span class="font-bold text-purple-600">{{ isSuperAdmin(viewingAdmin) ? 'Super Administrator' : 'System Administrator' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Session Status:</span>
                  <span class="font-mono font-medium text-emerald-600">Active Token</span>
                </div>
                <div class="flex justify-between py-1">
                  <span class="text-slate-400">Last Verified Login:</span>
                  <span class="font-mono font-medium">{{ formatLastLogin(viewingAdmin).text }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Granted Permissions Matrix -->
          <div class="space-y-2">
            <h4 class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px] flex items-center justify-between">
              <span>Authorized System Modules & Actions Matrix</span>
              <span class="text-[10px] text-purple-600 font-bold">
                {{ isSuperAdmin(viewingAdmin) ? 'Super Admin (Full Access: 10 Modules)' : 'Admin (Operational: 7 Modules)' }}
              </span>
            </h4>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-[10px] uppercase font-bold text-slate-500">
                  <tr>
                    <th class="py-2.5 px-3">Module</th>
                    <th class="py-2.5 px-3">Description</th>
                    <th class="py-2.5 px-3">Action Privileges</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                  <tr
                    v-for="mod in (isSuperAdmin(viewingAdmin) ? superAdminModules : standardAdminModules)"
                    :key="mod.name"
                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
                  >
                    <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white whitespace-nowrap">
                      {{ mod.name }}
                    </td>
                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300">
                      {{ mod.desc }}
                    </td>
                    <td class="py-2.5 px-3 whitespace-nowrap">
                      <div class="flex flex-wrap gap-1">
                        <span
                          v-for="cap in mod.capabilities"
                          :key="cap"
                          class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/20"
                        >
                          {{ cap }}
                        </span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Action Buttons in Profile -->
          <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center gap-3">
            <button
              @click="toggleActivateDisable(viewingAdmin)"
              :class="[
                (viewingAdmin.status || 'active') === 'active'
                  ? 'bg-rose-50 hover:bg-rose-100 text-rose-600 border-rose-200'
                  : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border-emerald-200',
                'px-4 py-2 border rounded-xl text-xs font-semibold cursor-pointer'
              ]"
            >
              {{ (viewingAdmin.status || 'active') === 'active' ? 'Disable Account' : 'Activate Account' }}
            </button>
            <button
              @click="openEditModal(viewingAdmin)"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold cursor-pointer"
            >
              Edit Admin
            </button>
            <button
              @click="showProfileModal = false"
              class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold shadow-md cursor-pointer"
            >
              Done
            </button>
          </div>
        </div>
      </div>

      <!-- Clean Toast Notification -->
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
