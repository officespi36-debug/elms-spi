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

const search = ref('')
const selectedAdminForEdit = ref<any | null>(null)
const openDropdownId = ref<number | null>(null)
const isAddAdminModalOpen = ref(false)

const adminForm = useForm({
  id: null as number | null,
  name: '',
  email: '',
  phone: '',
  role: 'admin',
  system_area: 'Full System - All Modules',
  status: 'active',
  department_id: '',
  permissions: ['Dashboard', 'Users', 'Courses', 'Payments', 'Analytics', 'Settings', 'Certificates', 'Backup']
})

const filteredAdmins = computed(() => {
  return props.administrators.filter(admin => {
    if (!search.value) return true
    const query = search.value.toLowerCase()
    return (
      admin.name?.toLowerCase().includes(query) ||
      admin.email?.toLowerCase().includes(query) ||
      admin.phone?.toLowerCase().includes(query)
    )
  })
})

const openAddAdminModal = () => {
  selectedAdminForEdit.value = null
  adminForm.reset()
  adminForm.id = null
  adminForm.role = 'admin'
  isAddAdminModalOpen.value = true
}

const openEditAdmin = (admin: any) => {
  isAddAdminModalOpen.value = false
  selectedAdminForEdit.value = admin
  adminForm.id = admin.id
  adminForm.name = admin.name || ''
  adminForm.email = admin.email || ''
  adminForm.phone = admin.phone || '+855 12 345 678'
  adminForm.role = 'admin'
  adminForm.system_area = admin.system_area || 'Full System - All Modules'
  adminForm.status = admin.status || 'active'
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

const saveAdminProfile = () => {
  const adminName = adminForm.name || 'Administrator'
  if (selectedAdminForEdit.value) {
    adminForm.put(`/admin/users/${adminForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        selectedAdminForEdit.value = null
        triggerToast(
          'រក្សាទុកបានជោគជ័យ',
          `ព័ត៌មាន Administrator "${adminName}" ត្រូវបានបច្ចុប្បន្នភាពដោយជោគជ័យ`
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
    adminForm.post('/admin/users', {
      preserveScroll: true,
      onSuccess: () => {
        isAddAdminModalOpen.value = false
        adminForm.reset()
        triggerToast(
          'បង្កើត Administrator បានជោគជ័យ',
          `គណនី Administrator "${adminName}" ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast(
          'មានបញ្ហាក្នុងការបង្កើត Administrator',
          'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ',
          'warning'
        )
      }
    })
  }
}

const suspendAdmin = (admin: any) => {
  if (confirm(`Suspend Administrator account '${admin.name}'?`)) {
    router.post(`/admin/user-management/suspend/${admin.id}`)
  }
}

const exportAdminsCSV = () => {
  window.location.href = '/admin/user-management/export?role=admin'
}
</script>

<template>
  <AdminLayout title="Administrators Management">
    <div class="space-y-6 font-sans">
      <!-- Shared Navigation Header & Overview Cards -->
      <UserModuleHeader activeTab="administrators" :summaryStats="props.summaryStats" />

      <!-- SEARCH & ACTION TOOLBAR (Single Row Layout: Filters Left — Actions Far Right) -->
      <div class="bg-white dark:bg-slate-900/60 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-none backdrop-blur-xl flex flex-wrap items-center justify-between gap-3">
        <!-- Left Aligned Search Box -->
        <div class="flex items-center gap-3 flex-1 min-w-[280px]">
          <div class="relative flex-1 min-w-[200px]">
            <input
              v-model="search"
              type="text"
              placeholder="Search administrator name, email, phone..."
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-purple-500 transition-all"
            />
            <span class="absolute left-3 top-2.5 text-slate-400 dark:text-slate-500">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
          </div>
        </div>

        <!-- Far Right Aligned Primary Action Buttons -->
        <div class="flex items-center gap-2">
          <button
            @click="openAddAdminModal"
            class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-md shadow-purple-600/20 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add New Administrator</span>
          </button>

          <a
            href="/admin/user-management/import-export"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Import</span>
          </a>

          <button
            @click="exportAdminsCSV"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Export</span>
          </button>
        </div>
      </div>

      <!-- ADMINISTRATORS DATA TABLE (min-h-[380px] to prevent dropdown clipping) -->
      <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 shadow-sm dark:shadow-none backdrop-blur-xl min-h-[380px] pb-20">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <th class="py-3.5 px-4 w-12 text-center">#</th>
              <th class="py-3.5 px-4">Administrator Name</th>
              <th class="py-3.5 px-4">Email & Phone</th>
              <th class="py-3.5 px-4">System Area Responsibility</th>
              <th class="py-3.5 px-4">Last Login</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            <tr v-for="(admin, idx) in filteredAdmins" :key="admin.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-all group">
              <td class="py-3.5 px-4 text-center font-mono text-slate-500 dark:text-slate-400 font-medium">{{ String(idx + 1).padStart(2, '0') }}</td>

              <td class="py-3.5 px-4">
                <button
                  @click="openEditAdmin(admin)"
                  class="flex items-center gap-3 text-left focus:outline-none group/item"
                  title="Click to view/edit administrator profile"
                >
                  <img
                    :src="admin.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(admin.name)}&background=8b5cf6&color=fff`"
                    class="w-9 h-9 rounded-full border border-purple-500/30 object-cover group-hover/item:border-purple-400 transition-all"
                  />
                  <div>
                    <div class="font-bold text-slate-900 dark:text-white group-hover/item:text-purple-600 dark:group-hover/item:text-purple-300 transition-colors flex items-center gap-2">
                      <span>{{ admin.name }}</span>
                      <span v-if="idx === 0" class="inline-flex items-center px-2 py-0.5 text-[9px] bg-purple-100 dark:bg-purple-500/30 text-purple-700 dark:text-purple-200 border border-purple-200 dark:border-purple-500/40 rounded-full font-bold whitespace-nowrap">Super Admin</span>
                    </div>
                    <div class="text-[10px] text-purple-600/80 dark:text-purple-300/80 font-mono font-medium">System Admin / ID: #{{ admin.id }}</div>
                  </div>
                </button>
              </td>

              <td class="py-3.5 px-4">
                <div class="font-mono text-slate-800 dark:text-slate-200 font-medium">{{ admin.email }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-300 font-mono flex items-center gap-1 mt-0.5">
                  <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <span>{{ admin.phone || '+855 12 345 678' }}</span>
                </div>
              </td>

              <td class="py-3.5 px-4 text-slate-700 dark:text-slate-200 font-medium">
                <span class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-purple-700 dark:text-purple-300 text-xs font-semibold">
                  {{ idx === 0 ? 'Full System (All Modules)' : (idx === 1 ? 'User Mgmt & Enrollment' : 'Content & Analytics') }}
                </span>
              </td>

              <td class="py-3.5 px-4 font-mono">
                <div class="text-slate-700 dark:text-slate-200 font-bold">2m ago</div>
                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                  <span>Active Session</span>
                </div>
              </td>

              <!-- Direct Quick Action Icons (Uniform Fixed Width & Height) -->
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openEditAdmin(admin)"
                    class="w-[74px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-purple-500/20 text-slate-700 dark:text-purple-300 hover:text-purple-700 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Edit Admin Profile & Permissions"
                  >
                    <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>Edit</span>
                  </button>

                  <button
                    @click="suspendAdmin(admin)"
                    class="w-[88px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-red-500/20 text-rose-600 dark:text-red-400 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Suspend Admin"
                  >
                    <svg class="w-3.5 h-3.5 text-rose-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <span>Suspend</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredAdmins.length === 0">
              <td colspan="6" class="py-12 text-center text-slate-500 font-medium">
                No administrator accounts found matching search criteria.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Table Pagination Footer -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="text-slate-500 dark:text-slate-400 font-mono">
            Showing <span class="text-slate-900 dark:text-white font-bold">1</span> to <span class="text-slate-900 dark:text-white font-bold">{{ filteredAdmins.length }}</span> of <span class="text-slate-900 dark:text-white font-bold">{{ props.summaryStats?.total_admins || 3 }}</span> entries
          </div>

          <div class="flex items-center gap-1.5 font-mono">
            <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-xl font-semibold cursor-not-allowed" disabled>Previous</button>
            <button class="px-3 py-1.5 bg-purple-600 text-white font-bold rounded-xl shadow-sm shadow-purple-600/20">1</button>
            <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-xl font-semibold cursor-not-allowed" disabled>Next</button>
          </div>
        </div>
      </div>

      <!-- ADD / EDIT ADMIN PROFILE & PERMISSIONS MODAL -->
      <div v-if="selectedAdminForEdit || isAddAdminModalOpen" class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900/95 border border-slate-200 dark:border-purple-900/50 rounded-3xl max-w-2xl w-full p-7 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh] backdrop-blur-2xl">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-wide">
                  {{ selectedAdminForEdit ? 'EDIT ADMIN PROFILE & PERMISSIONS' : 'ADD NEW ADMINISTRATOR ACCOUNT' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">កែប្រែព័ត៌មាន និងសិទ្ធិបញ្ជាប្រព័ន្ធរបស់អ្នកគ្រប់គ្រង</p>
              </div>
            </div>
            <button @click="selectedAdminForEdit = null; isAddAdminModalOpen = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/60 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-all">✕</button>
          </div>

          <!-- Form Inputs -->
          <form @submit.prevent="saveAdminProfile" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Full Name -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  <span>Full Name *</span>
                </label>
                <input v-model="adminForm.name" type="text" required placeholder="e.g. System Admin" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all font-bold" />
              </div>

              <!-- Email Address -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <span>Email Address *</span>
                </label>
                <input v-model="adminForm.email" type="email" required placeholder="e.g. admin@elms.com" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all font-mono" />
              </div>

              <!-- Phone Number -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <span>Phone Number</span>
                </label>
                <input v-model="adminForm.phone" type="text" placeholder="e.g. +855 12 345 678" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all font-mono" />
              </div>

              <!-- Role Title -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  <span>Role Title</span>
                </label>
                <select v-model="adminForm.role" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-purple-700 dark:text-purple-300 font-bold focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all cursor-pointer">
                  <option value="admin">Super Administrator</option>
                  <option value="admin">System Admin</option>
                </select>
              </div>
            </div>

            <!-- PERMISSIONS CHECKLIST MATRIX (Clean Checkmark Cards) -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-3">
              <div class="flex items-center justify-between">
                <label class="font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wider text-[11px] flex items-center gap-2">
                  <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  <span>PERMISSIONS CHECKLIST (System Access Control Matrix)</span>
                </label>
                <span class="text-[10px] font-mono text-emerald-700 dark:text-emerald-400 font-bold px-2 py-0.5 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-lg">
                  8 / 8 Active Permissions
                </span>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                <div
                  v-for="perm in adminForm.permissions"
                  :key="perm"
                  class="px-3 py-2.5 bg-white dark:bg-slate-900/90 border border-purple-200 dark:border-purple-500/30 rounded-xl flex items-center gap-2 text-slate-700 dark:text-slate-200 text-xs font-semibold hover:border-purple-400 dark:hover:border-purple-500/60 transition-all shadow-xs"
                >
                  <div class="w-4 h-4 rounded-md bg-emerald-100 dark:bg-emerald-500/20 border border-emerald-200 dark:border-emerald-500/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                  </div>
                  <span class="truncate">{{ perm }}</span>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center gap-3">
              <button type="button" @click="selectedAdminForEdit = null; isAddAdminModalOpen = false" :disabled="adminForm.processing" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold transition-all cursor-pointer disabled:opacity-50">Cancel</button>
              <button type="submit" :disabled="adminForm.processing" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl text-xs shadow-md shadow-purple-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <svg v-if="adminForm.processing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ adminForm.processing ? (selectedAdminForEdit ? 'កំពុងរក្សាទុក...' : 'កំពុងបង្កើត...') : (selectedAdminForEdit ? 'Save Changes' : 'Create Administrator') }}</span>
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
