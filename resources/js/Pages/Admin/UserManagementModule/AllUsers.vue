<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import UserModuleHeader from '@/Components/Admin/UserModuleHeader.vue'

const props = withDefaults(defineProps<{
  users?: Array<any>
  faculties?: Array<any>
  departments?: Array<any>
  majors?: Array<any>
  summaryStats?: any
  filters?: any
}>(), {
  users: () => [],
  faculties: () => [],
  departments: () => [],
  majors: () => [],
  summaryStats: () => ({}),
  filters: () => ({})
})

const search = ref(props.filters?.search || '')
const roleFilter = ref(props.filters?.role || '')
const statusFilter = ref(props.filters?.status || '')
const deptFilter = ref('')

const selectedUserIds = ref<number[]>([])
const openDropdownId = ref<number | null>(null)
const isAddEditModalOpen = ref(false)
const selectedUserForView = ref<any | null>(null)
const editingUser = ref<any | null>(null)

const userForm = useForm({
  id: null as number | null,
  name: '',
  name_kh: '',
  email: '',
  phone: '',
  role: 'student',
  status: 'active',
  major_id: '' as string | number,
  qualification: '',
  expertise: '',
  password: '',
})

const filteredUsers = computed(() => {
  return props.users.filter(u => {
    const matchesSearch = !search.value ||
      u.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      u.email?.toLowerCase().includes(search.value.toLowerCase()) ||
      u.phone?.includes(search.value)

    const matchesRole = !roleFilter.value || u.role === roleFilter.value
    const matchesStatus = !statusFilter.value || u.status === statusFilter.value
    const matchesDept = !deptFilter.value || u.major?.department?.id == deptFilter.value

    return matchesSearch && matchesRole && matchesStatus && matchesDept
  })
})

const openAddModal = () => {
  editingUser.value = null
  userForm.reset()
  userForm.id = null
  isAddEditModalOpen.value = true
}

const openEditModal = (user: any) => {
  editingUser.value = user
  userForm.id = user.id
  userForm.name = user.name || ''
  userForm.name_kh = user.name_kh || ''
  userForm.email = user.email || ''
  userForm.phone = user.phone || ''
  userForm.role = user.role || 'student'
  userForm.status = user.status || 'active'
  userForm.major_id = user.major_id || ''
  userForm.qualification = user.qualification || ''
  userForm.expertise = user.expertise || ''
  userForm.password = ''
  isAddEditModalOpen.value = true
}

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

const submitUserForm = () => {
  const userName = userForm.name || 'អ្នកប្រើប្រាស់'
  if (editingUser.value) {
    userForm.put(`/admin/users/${userForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        isAddEditModalOpen.value = false
        triggerToast(
          'រក្សាទុកបានជោគជ័យ',
          `ព័ត៌មានអ្នកប្រើប្រាស់ "${userName}" ត្រូវបានបច្ចុប្បន្នភាពដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast(
          'មានបញ្ហាក្នុងការរក្សាទុក',
          'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ',
          'warning'
        )
      }
    })
  } else {
    userForm.post('/admin/users', {
      preserveScroll: true,
      onSuccess: () => {
        isAddEditModalOpen.value = false
        userForm.reset()
        triggerToast(
          'បង្កើតអ្នកប្រើប្រាស់បានជោគជ័យ',
          `គណនី "${userName}" ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast(
          'មានបញ្ហាក្នុងការបង្កើតអ្នកប្រើប្រាស់',
          'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ',
          'warning'
        )
      }
    })
  }
}

const suspendUser = (user: any) => {
  if (confirm(`Are you sure you want to SUSPEND user account '${user.name}'?`)) {
    router.post(`/admin/user-management/suspend/${user.id}`)
  }
}

const restoreUser = (user: any) => {
  if (confirm(`RESTORE access for user '${user.name}'?`)) {
    router.post(`/admin/user-management/restore/${user.id}`)
  }
}

const deleteUser = (user: any) => {
  if (confirm(`CRITICAL: Permanently delete user '${user.name}' (${user.email})?`)) {
    router.delete(`/admin/users/${user.id}`)
  }
}

const toggleSelectAll = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.checked) {
    selectedUserIds.value = filteredUsers.value.map(u => u.id)
  } else {
    selectedUserIds.value = []
  }
}

const executeBulkAction = (actionType: 'activate' | 'suspend' | 'delete') => {
  if (selectedUserIds.value.length === 0) {
    alert('Please select at least one user for bulk operation.')
    return
  }

  if (confirm(`Execute bulk ${actionType.toUpperCase()} on ${selectedUserIds.value.length} selected users?`)) {
    router.post('/admin/users/bulk', {
      ids: selectedUserIds.value,
      action: actionType
    }, {
      onSuccess: () => {
        selectedUserIds.value = []
      }
    })
  }
}

const exportCSV = () => {
  const headers = ['ID', 'Name', 'Email', 'Role', 'Department', 'Status', 'Phone']
  const rows = filteredUsers.value.map(u => [
    u.id,
    u.name,
    u.email,
    u.role,
    u.major?.department?.name || 'General',
    u.status,
    u.phone || ''
  ])

  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n')
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `elms_all_users_${new Date().toISOString().slice(0, 10)}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<template>
  <AdminLayout title="All Users — Master List">
    <div class="space-y-6 font-sans">
      <!-- Shared Navigation Header & Overview Cards -->
      <UserModuleHeader activeTab="all" :summaryStats="props.summaryStats" />

      <!-- SEARCH & FILTER TOOLBAR (Single Row Layout: Filters Left — Actions Far Right) -->
      <div class="bg-white dark:bg-slate-900/60 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-none backdrop-blur-xl flex flex-wrap items-center justify-between gap-3">
        <!-- Left Aligned Search & Filters -->
        <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[300px]">
          <!-- Search Box -->
          <div class="relative flex-1 min-w-[220px]">
            <input
              v-model="search"
              type="text"
              placeholder="Search name, email, phone..."
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all"
            />
            <span class="absolute left-3 top-2.5 text-slate-400 dark:text-slate-500">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
          </div>

          <!-- Role Filter -->
          <select v-model="roleFilter" class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500 cursor-pointer">
            <option value="">Role: All Roles</option>
            <option value="admin">Administrator</option>
            <option value="teacher">Teacher / Instructor</option>
            <option value="student">Student / Learner</option>
          </select>

          <!-- Status Filter -->
          <select v-model="statusFilter" class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500 cursor-pointer">
            <option value="">Status: All Status</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="suspended">Suspended</option>
          </select>

          <!-- Dept Filter -->
          <select v-model="deptFilter" class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-indigo-500 cursor-pointer">
            <option value="">Department: All Departments</option>
            <option v-for="d in props.departments" :key="d.id" :value="d.id">
              {{ d.name }}
            </option>
          </select>
        </div>

        <!-- Far Right Aligned Primary Action Buttons -->
        <div class="flex items-center gap-2">
          <button
            @click="openAddModal"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/20 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add New User</span>
          </button>

          <a
            href="/admin/user-management/import-export"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Import</span>
          </a>

          <button
            @click="exportCSV"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Export</span>
          </button>
        </div>
      </div>

      <!-- CONTEXTUAL BULK ACTIONS TOOLBAR (Only Visible When Rows Are Selected) -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-2 opacity-0"
      >
        <div v-if="selectedUserIds.length > 0" class="flex flex-wrap items-center justify-between gap-3 p-3.5 bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-500/40 rounded-2xl shadow-sm dark:shadow-xl backdrop-blur-xl">
          <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 rounded-xl text-xs font-mono font-bold border border-indigo-200 dark:border-indigo-500/30">
              ✓ {{ selectedUserIds.length }} Selected
            </span>
            <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Bulk Actions:</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="executeBulkAction('activate')"
              class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center gap-1.5 cursor-pointer"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              <span>Activate Selected</span>
            </button>

            <button
              @click="executeBulkAction('suspend')"
              class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center gap-1.5 cursor-pointer"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
              <span>Suspend Selected</span>
            </button>

            <button
              @click="executeBulkAction('delete')"
              class="px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center gap-1.5 cursor-pointer"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              <span>Delete Selected</span>
            </button>

            <button
              @click="selectedUserIds = []"
              class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl transition-all cursor-pointer"
            >
              ✕ Clear Selection
            </button>
          </div>
        </div>
      </transition>

      <!-- MASTER USERS DATA TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 shadow-sm dark:shadow-none backdrop-blur-xl min-h-[380px] pb-24">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <th class="py-3.5 px-4 w-10 text-center">
                <input type="checkbox" @change="toggleSelectAll" class="rounded bg-slate-100 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0 cursor-pointer" />
              </th>
              <th class="py-3.5 px-4 w-12 text-center">#</th>
              <th class="py-3.5 px-4">Full Name</th>
              <th class="py-3.5 px-4">Email & Phone</th>
              <th class="py-3.5 px-4 text-center">Role</th>
              <th class="py-3.5 px-4">Department & Major</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            <tr v-for="(user, idx) in filteredUsers" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-all group">
              <!-- Checkbox -->
              <td class="py-3.5 px-4 text-center">
                <input v-model="selectedUserIds" :value="user.id" type="checkbox" class="rounded bg-slate-100 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0 cursor-pointer" />
              </td>

              <!-- Number Index -->
              <td class="py-3.5 px-4 text-center font-mono text-slate-500 dark:text-slate-400 font-medium">{{ String(idx + 1).padStart(2, '0') }}</td>

              <!-- Clickable Name & Avatar -->
              <td class="py-3.5 px-4">
                <button
                  @click="selectedUserForView = user"
                  class="flex items-center gap-3 text-left focus:outline-none group/item"
                  title="Click to view user profile details"
                >
                  <img
                    :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=6366f1&color=fff`"
                    class="w-9 h-9 rounded-full border border-slate-200 dark:border-slate-700 object-cover group-hover/item:border-indigo-500 transition-all shadow-xs"
                  />
                  <div>
                    <div class="font-bold text-slate-900 dark:text-white group-hover/item:text-indigo-600 dark:group-hover/item:text-indigo-300 transition-colors flex items-center gap-1.5">
                      <span>{{ user.name }}</span>
                      <span v-if="user.name_kh" class="text-[11px] text-slate-500 dark:text-slate-400 font-normal">({{ user.name_kh }})</span>
                    </div>
                    <div class="text-[10px] text-indigo-600 dark:text-indigo-300/80 font-mono font-medium">ID: #{{ user.id }}</div>
                  </div>
                </button>
              </td>

              <!-- Email & Phone (High Contrast Typography) -->
              <td class="py-3.5 px-4">
                <div class="font-mono text-slate-800 dark:text-slate-200 font-medium">{{ user.email }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-300 font-mono flex items-center gap-1 mt-0.5">
                  <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <span>{{ user.phone || 'N/A' }}</span>
                </div>
              </td>

              <!-- Role Badge with Vector SVG -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span v-if="user.role === 'admin'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 whitespace-nowrap">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  Admin
                </span>
                <span v-else-if="user.role === 'teacher'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-50 dark:bg-cyan-500/20 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-500/30 whitespace-nowrap">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                  Teacher
                </span>
                <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 whitespace-nowrap">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                  Student
                </span>
              </td>

              <!-- Department & Major (Clean Single-Line & High-Contrast Typography) -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <template v-if="user.major">
                  <div class="font-bold text-slate-800 dark:text-slate-100 text-xs">{{ user.major.department?.name || 'General Faculty' }}</div>
                  <div class="text-[11px] text-indigo-600 dark:text-indigo-300 font-medium mt-0.5 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400"></span>
                    <span>{{ user.major.name }}</span>
                  </div>
                </template>
                <template v-else-if="user.role === 'admin'">
                  <div class="font-bold text-slate-800 dark:text-slate-100 text-xs">System Administration</div>
                  <div class="text-[11px] text-purple-600 dark:text-purple-300 font-medium mt-0.5 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 dark:bg-purple-400"></span>
                    <span>Security & Core Access</span>
                  </div>
                </template>
                <template v-else-if="user.role === 'teacher'">
                  <div class="font-bold text-slate-800 dark:text-slate-100 text-xs">Faculty of Computer Science</div>
                  <div class="text-[11px] text-cyan-600 dark:text-cyan-300 font-medium mt-0.5 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400"></span>
                    <span>General Instructor</span>
                  </div>
                </template>
                <template v-else-if="user.role === 'student'">
                  <div class="font-bold text-slate-800 dark:text-slate-100 text-xs">IT & Computer Science</div>
                  <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                    <span>Faculty of Science & Tech</span>
                  </div>
                </template>
                <template v-else>
                  <span class="text-slate-400 font-mono text-xs font-medium">-</span>
                </template>
              </td>

              <!-- Status Badge with Vector SVG Status Dot -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span v-if="user.status === 'active'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 whitespace-nowrap">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                  Active
                </span>
                <span v-else-if="user.status === 'pending'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 whitespace-nowrap">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                  Pending
                </span>
                <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-red-500/20 text-rose-700 dark:text-red-300 border border-rose-200 dark:border-red-500/30 whitespace-nowrap">
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-500 dark:bg-red-400"></span>
                  Suspended
                </span>
              </td>

              <!-- Direct Quick Action Icons (Uniform Fixed Width & Height) -->
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="selectedUserForView = user"
                    class="w-[74px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-indigo-50 dark:bg-slate-800 dark:hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 hover:text-indigo-700 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="View Profile"
                  >
                    <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View</span>
                  </button>

                  <button
                    @click="openEditModal(user)"
                    class="w-[74px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-cyan-50 dark:bg-slate-800 dark:hover:bg-cyan-500/20 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Edit Profile"
                  >
                    <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span>Edit</span>
                  </button>

                  <button
                    v-if="user.status !== 'suspended'"
                    @click="suspendUser(user)"
                    class="w-[88px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-red-500/20 text-rose-600 dark:text-red-400 hover:text-rose-700 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Suspend User"
                  >
                    <svg class="w-3.5 h-3.5 text-rose-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <span>Suspend</span>
                  </button>
                  <button
                    v-else
                    @click="restoreUser(user)"
                    class="w-[88px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-emerald-50 dark:bg-slate-800 dark:hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Restore User Account"
                  >
                    <svg class="w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Restore</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredUsers.length === 0">
              <td colspan="8" class="py-12 text-center text-slate-500 dark:text-slate-400">
                No user accounts found matching selected filters.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ADD / EDIT USER MODAL -->
      <div v-if="isAddEditModalOpen" class="fixed inset-0 bg-slate-950/70 dark:bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900/95 border border-slate-200 dark:border-slate-800/90 rounded-3xl max-w-2xl w-full p-7 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh] backdrop-blur-2xl">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-wide">
                  {{ editingUser ? 'EDIT USER PROFILE' : 'ADD NEW USER ACCOUNT' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">បំពេញព័ត៌មានលម្អិតដើម្បីបង្កើត ឬកែប្រែគណនីប្រើប្រាស់ប្រព័ន្ធ</p>
              </div>
            </div>
            <button @click="isAddEditModalOpen = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/60 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-all cursor-pointer">✕</button>
          </div>

          <!-- Form Inputs Grid -->
          <form @submit.prevent="submitUserForm" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- English Name -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  <span>Full Name (English) *</span>
                </label>
                <input v-model="userForm.name" type="text" required placeholder="e.g. Sok Dara" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" />
              </div>

              <!-- Khmer Name -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                  <span>Full Name (Khmer)</span>
                </label>
                <input v-model="userForm.name_kh" type="text" placeholder="ឧ. សុខ ដារ៉ា" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" />
              </div>

              <!-- Email Address -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <span>Email Address *</span>
                </label>
                <input v-model="userForm.email" type="email" required placeholder="e.g. dara.sok@elms.com" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" />
              </div>

              <!-- Phone Number -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <span>Phone Number</span>
                </label>
                <input v-model="userForm.phone" type="text" placeholder="e.g. +855 12 345 678" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" />
              </div>

              <!-- User Role -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  <span>User Role *</span>
                </label>
                <select v-model="userForm.role" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all cursor-pointer">
                  <option value="admin">Administrator</option>
                  <option value="teacher">Teacher / Instructor</option>
                  <option value="student">Student / Learner</option>
                </select>
              </div>

              <!-- Account Status -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  <span>Account Status *</span>
                </label>
                <select v-model="userForm.status" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all cursor-pointer">
                  <option value="active">Active</option>
                  <option value="pending">Pending</option>
                  <option value="suspended">Suspended</option>
                </select>
              </div>

              <!-- Assigned Major / Specialization -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                  <span>Assigned Major / Specialization</span>
                </label>
                <select v-model="userForm.major_id" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-medium focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all cursor-pointer">
                  <option value="">Unassigned</option>
                  <option v-for="m in props.majors" :key="m.id" :value="m.id">
                    {{ m.name }} ({{ m.department?.name }})
                  </option>
                </select>
              </div>

              <!-- Password -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                  <span>Password {{ editingUser ? '(Leave blank to keep)' : '*' }}</span>
                </label>
                <input v-model="userForm.password" type="password" :required="!editingUser" placeholder="••••••••" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all" />
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center gap-3">
              <button type="button" @click="isAddEditModalOpen = false" :disabled="userForm.processing" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold transition-all cursor-pointer disabled:opacity-50">Cancel</button>
              <button type="submit" :disabled="userForm.processing" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <svg v-if="userForm.processing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ userForm.processing ? (editingUser ? 'កំពុងរក្សាទុក...' : 'កំពុងបង្កើត...') : (editingUser ? 'Save Profile Changes' : 'Create User Account') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- VIEW USER PROFILE MODAL -->
      <div v-if="selectedUserForView" class="fixed inset-0 bg-slate-950/70 dark:bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900/95 border border-slate-200 dark:border-indigo-900/50 rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl backdrop-blur-2xl">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-3.5">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-wide">USER PROFILE DETAILS</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">ព័ត៌មានលម្អិតគណនីប្រើប្រាស់ប្រព័ន្ធ</p>
              </div>
            </div>
            <button @click="selectedUserForView = null" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/60 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center transition-all cursor-pointer">✕</button>
          </div>

          <div class="space-y-4 text-xs">
            <!-- Hero User Card Banner -->
            <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-indigo-50 via-slate-50 to-blue-50/50 dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950/40 border border-indigo-100 dark:border-indigo-500/20 rounded-2xl shadow-xs">
              <img
                :src="selectedUserForView.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(selectedUserForView.name)}&background=6366f1&color=fff`"
                class="w-14 h-14 rounded-2xl border-2 border-indigo-500/40 object-cover shadow-sm shadow-indigo-500/10"
              />
              <div class="space-y-1">
                <div class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-1.5 leading-tight">
                  <span>{{ selectedUserForView.name }}</span>
                  <span v-if="selectedUserForView.name_kh" class="text-xs text-slate-500 dark:text-slate-400 font-normal">({{ selectedUserForView.name_kh }})</span>
                </div>
                <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-mono font-semibold">User Code / ID: #{{ selectedUserForView.id }}</div>

                <div class="flex items-center gap-2 pt-0.5">
                  <span v-if="selectedUserForView.role === 'admin'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30">
                    Administrator
                  </span>
                  <span v-else-if="selectedUserForView.role === 'teacher'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 dark:bg-cyan-500/20 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-500/30">
                    Teacher / Instructor
                  </span>
                  <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30">
                    Student / Learner
                  </span>

                  <span v-if="selectedUserForView.status === 'active'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span> Active
                  </span>
                  <span v-else-if="selectedUserForView.status === 'pending'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400"></span> Pending
                  </span>
                  <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-red-500/20 text-rose-700 dark:text-red-300 border border-rose-200 dark:border-red-500/30 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 dark:bg-red-400"></span> Suspended
                  </span>
                </div>
              </div>
            </div>

            <!-- Profile Data Grid Cards with Icons -->
            <div class="grid grid-cols-2 gap-3">
              <!-- Email Card -->
              <div class="p-3.5 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1 hover:border-slate-300 dark:hover:border-slate-700/80 transition-all">
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <span>Email Address</span>
                </span>
                <span class="text-slate-900 dark:text-slate-200 font-bold font-mono text-xs block truncate">{{ selectedUserForView.email }}</span>
              </div>

              <!-- Phone Card -->
              <div class="p-3.5 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1 hover:border-slate-300 dark:hover:border-slate-700/80 transition-all">
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <span>Phone Number</span>
                </span>
                <span class="text-slate-900 dark:text-slate-200 font-bold font-mono text-xs block">{{ selectedUserForView.phone || '+855 12 345 678' }}</span>
              </div>

              <!-- Major Card -->
              <div class="p-3.5 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1 hover:border-slate-300 dark:hover:border-slate-700/80 transition-all">
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                  <span>Assigned Major</span>
                </span>
                <span class="text-indigo-600 dark:text-indigo-300 font-bold text-xs block truncate">{{ selectedUserForView.major?.name || 'Software Engineering' }}</span>
              </div>

              <!-- Department Card -->
              <div class="p-3.5 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl space-y-1 hover:border-slate-300 dark:hover:border-slate-700/80 transition-all">
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V10a2 2 0 012-2h2a2 2 0 012 2v11"/></svg>
                  <span>Department</span>
                </span>
                <span class="text-indigo-600 dark:text-indigo-300 font-bold text-xs block truncate">{{ selectedUserForView.major?.department?.name || 'Faculty of Computer Science' }}</span>
              </div>
            </div>

            <!-- Footer Buttons -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center gap-3">
              <button @click="selectedUserForView = null" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold transition-all cursor-pointer">Close</button>
              <button @click="openEditModal(selectedUserForView); selectedUserForView = null" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Edit Profile</span>
              </button>
            </div>
          </div>
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
