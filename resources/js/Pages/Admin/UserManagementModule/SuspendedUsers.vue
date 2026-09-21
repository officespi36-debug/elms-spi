<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import UserModuleHeader from '@/Components/Admin/UserModuleHeader.vue'

const props = withDefaults(defineProps<{
  suspendedUsers?: Array<any>
  summaryStats?: any
}>(), {
  suspendedUsers: () => [],
  summaryStats: () => ({})
})

const selectedUserForRestore = ref<any | null>(null)
const restoreCondition = ref('full')
const restoreNote = ref('')
const selectedUserIds = ref<number[]>([])
const openDropdownId = ref<number | null>(null)

const reasonCategories = [
  { reason: '❌ Payment Overdue (30+ days)', count: '5 Students' },
  { reason: '🚫 Violation (Plagiarism & Misconduct)', count: '2 Students' },
  { reason: '⏸️ Manual Admin Suspension', count: '3 Users (1 Teacher, 2 Students)' },
  { reason: '🔒 Security Lock (Failed Login Attempts)', count: '2 Accounts' },
]

const openRestoreModal = (user: any) => {
  selectedUserForRestore.value = user
  restoreCondition.value = 'full'
  restoreNote.value = 'Payment completed via ABA — Confirmed'
}

const confirmRestore = () => {
  if (selectedUserForRestore.value) {
    router.post(`/admin/user-management/restore/${selectedUserForRestore.value.id}`, {
      condition: restoreCondition.value,
      note: restoreNote.value
    }, {
      onSuccess: () => {
        selectedUserForRestore.value = null
      }
    })
  }
}

const deleteAccount = (user: any) => {
  if (confirm(`PERMANENT DELETION: Are you sure you want to delete account for '${user.name}'?`)) {
    router.delete(`/admin/users/${user.id}`)
  }
}

const toggleSelectAll = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.checked) {
    selectedUserIds.value = props.suspendedUsers.map(u => u.id)
  } else {
    selectedUserIds.value = []
  }
}

const bulkRestore = () => {
  if (selectedUserIds.value.length === 0) return
  if (confirm(`Restore ${selectedUserIds.value.length} selected accounts?`)) {
    router.post('/admin/users/bulk', {
      ids: selectedUserIds.value,
      action: 'activate'
    }, {
      onSuccess: () => {
        selectedUserIds.value = []
      }
    })
  }
}
</script>

<template>
  <AdminLayout title="Suspended Users — Frozen Accounts">
    <div class="space-y-6 font-sans">
      <!-- Shared Header -->
      <UserModuleHeader activeTab="suspended" :summaryStats="props.summaryStats" />

      <!-- REASON CATEGORIES SUMMARY TABLE -->
      <div class="bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 space-y-3 shadow-sm dark:shadow-none backdrop-blur-xl">
        <div class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
          <svg class="w-4 h-4 text-rose-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          <span>SUSPENSION REASONS BREAKDOWN</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
          <div v-for="cat in reasonCategories" :key="cat.reason" class="p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-between gap-2 overflow-hidden">
            <span class="text-xs text-slate-700 dark:text-slate-300 font-medium whitespace-nowrap truncate">{{ cat.reason }}</span>
            <span class="px-2 py-0.5 bg-rose-50 dark:bg-red-500/20 text-rose-700 dark:text-red-300 border border-rose-200 dark:border-red-500/30 rounded text-[10px] font-mono font-bold whitespace-nowrap flex-shrink-0">
              {{ cat.count }}
            </span>
          </div>
        </div>
      </div>

      <!-- CONTEXTUAL BULK RESTORE TOOLBAR (Only Visible When Rows Selected) -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-2 opacity-0"
      >
        <div v-if="selectedUserIds.length > 0" class="flex flex-wrap items-center justify-between gap-3 p-3.5 bg-rose-50 dark:bg-red-950/80 border border-rose-200 dark:border-red-500/40 rounded-2xl shadow-lg backdrop-blur-xl">
          <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-rose-100 dark:bg-red-500/20 text-rose-800 dark:text-red-300 rounded-xl text-xs font-mono font-bold border border-rose-200 dark:border-red-500/30">
              ✓ {{ selectedUserIds.length }} Selected
            </span>
            <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Bulk Restoration Controls:</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="bulkRestore"
              class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow transition-all flex items-center gap-1.5 cursor-pointer"
            >
              <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
              <span>Restore Selected Accounts</span>
            </button>

            <button
              @click="selectedUserIds = []"
              class="px-2.5 py-1.5 bg-white hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent text-xs rounded-xl transition-all cursor-pointer font-medium"
            >
              ✕ Clear Selection
            </button>
          </div>
        </div>
      </transition>

      <!-- SUSPENDED USERS DATA TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-red-900/30 bg-white dark:bg-slate-900/60 shadow-sm dark:shadow-none backdrop-blur-xl min-h-[380px] pb-24">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-red-900/30 bg-slate-50 dark:bg-red-950/20 text-[11px] font-bold text-slate-600 dark:text-red-300 uppercase tracking-wider">
              <th class="py-3.5 px-4 w-10 text-center">
                <input type="checkbox" @change="toggleSelectAll" class="rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-rose-600 focus:ring-rose-500 cursor-pointer" />
              </th>
              <th class="py-3.5 px-4 w-12 text-center">#</th>
              <th class="py-3.5 px-4">User Name</th>
              <th class="py-3.5 px-4">Email</th>
              <th class="py-3.5 px-4 text-center">Role</th>
              <th class="py-3.5 px-4">Reason For Suspension</th>
              <th class="py-3.5 px-4 text-center">Suspended Date</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            <tr v-for="(user, idx) in props.suspendedUsers" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-red-950/10 transition-all group">
              <td class="py-3.5 px-4 text-center">
                <input v-model="selectedUserIds" :value="user.id" type="checkbox" class="rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-rose-600 focus:ring-rose-500 cursor-pointer" />
              </td>

              <td class="py-3.5 px-4 text-center font-mono text-slate-500 dark:text-slate-400 font-medium">{{ String(idx + 1).padStart(2, '0') }}</td>

              <!-- Clickable User Name & Avatar -->
              <td class="py-3.5 px-4">
                <button
                  @click="openRestoreModal(user)"
                  class="flex items-center gap-3 text-left focus:outline-none group/item"
                  title="Click to view restore options and details"
                >
                  <img
                    :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=ef4444&color=fff`"
                    class="w-9 h-9 rounded-full border border-rose-300 dark:border-red-500/30 object-cover group-hover/item:border-rose-500 dark:group-hover/item:border-red-400 transition-all shadow-xs"
                  />
                  <div>
                    <div class="font-bold text-slate-900 dark:text-white group-hover/item:text-rose-600 dark:group-hover/item:text-red-300 transition-colors flex items-center gap-1.5">
                      <span>{{ user.name }}</span>
                    </div>
                    <div class="text-[10px] text-rose-600 dark:text-red-300/80 font-mono font-medium">Status: Suspended (ID: #{{ user.id }})</div>
                  </div>
                </button>
              </td>

              <td class="py-3.5 px-4 font-mono text-slate-700 dark:text-slate-200 font-medium">
                {{ user.email }}
              </td>

              <td class="py-3.5 px-4 text-center capitalize font-bold whitespace-nowrap">
                <span v-if="user.role === 'admin'" class="px-2.5 py-0.5 rounded-full text-[10px] bg-purple-50 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30">Admin</span>
                <span v-else-if="user.role === 'teacher'" class="px-2.5 py-0.5 rounded-full text-[10px] bg-cyan-50 dark:bg-cyan-500/20 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-500/30">Teacher</span>
                <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30">Student</span>
              </td>

              <td class="py-3.5 px-4 text-rose-700 dark:text-red-300 font-medium whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-rose-50 dark:bg-red-500/10 border border-rose-200 dark:border-red-500/20 whitespace-nowrap">
                  <svg class="w-3.5 h-3.5 text-rose-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                  <span>{{ idx % 2 === 0 ? 'Unpaid Course Fee (30+ days)' : 'Admin Suspension' }}</span>
                </span>
              </td>

              <td class="py-3.5 px-4 text-center font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap">
                10 Jun 2025
              </td>

              <!-- Direct Quick Action Icons (Uniform Fixed Width & Height) -->
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openRestoreModal(user)"
                    class="w-[88px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-emerald-50 dark:bg-slate-800 dark:hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Restore Account Profile"
                  >
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Restore</span>
                  </button>

                  <button
                    @click="deleteAccount(user)"
                    class="w-[84px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-red-500/20 text-rose-600 dark:text-red-400 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Delete Account Permanently"
                  >
                    <svg class="w-3.5 h-3.5 text-rose-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="props.suspendedUsers.length === 0">
              <td colspan="8" class="py-12 text-center text-slate-500 font-medium">
                No suspended accounts currently recorded.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Table Pagination Footer -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="text-slate-500 dark:text-slate-400 font-mono">
            Showing <span class="text-slate-900 dark:text-white font-bold">1</span> to <span class="text-slate-900 dark:text-white font-bold">{{ props.suspendedUsers.length }}</span> of <span class="text-slate-900 dark:text-white font-bold">{{ props.summaryStats?.total_suspended || 12 }}</span> entries
          </div>

          <div class="flex items-center gap-1.5 font-mono">
            <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-xl font-semibold cursor-not-allowed border border-slate-200 dark:border-transparent" disabled>Previous</button>
            <button class="px-3 py-1.5 bg-rose-600 text-white font-bold rounded-xl shadow-xs shadow-rose-600/20">1</button>
            <button class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold cursor-pointer border border-slate-200 dark:border-transparent">2</button>
            <button class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold cursor-pointer border border-slate-200 dark:border-transparent">Next</button>
          </div>
        </div>
      </div>

      <!-- RESTORE ACCOUNT MODAL FORM -->
      <div v-if="selectedUserForRestore" class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-emerald-900/50 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <span>🔓 RESTORE SUSPENDED USER ACCOUNT</span>
            </h3>
            <button @click="selectedUserForRestore = null" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center text-sm transition-all cursor-pointer">✕</button>
          </div>

          <div class="space-y-4 text-xs">
            <div class="p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl">
              <div class="font-bold text-slate-900 dark:text-white text-sm">{{ selectedUserForRestore.name }} ({{ selectedUserForRestore.role }})</div>
              <div class="text-slate-500 dark:text-slate-400 font-mono mt-0.5">{{ selectedUserForRestore.email }}</div>
              <div class="text-rose-600 dark:text-red-400 font-medium mt-2">Reason: 💰 Payment Overdue — $25 for Database Systems</div>
            </div>

            <div class="space-y-2">
              <label class="block font-bold text-slate-700 dark:text-slate-300">RESTORE CONDITIONS (Choose Option):</label>
              <div class="space-y-2">
                <label class="flex items-center gap-2 p-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors">
                  <input v-model="restoreCondition" value="full" type="radio" class="text-emerald-600 focus:ring-emerald-500" />
                  <span class="text-slate-800 dark:text-slate-200 font-medium">📌 Restore with Full Access</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors">
                  <input v-model="restoreCondition" value="limited" type="radio" class="text-emerald-600 focus:ring-emerald-500" />
                  <span class="text-slate-800 dark:text-slate-200 font-medium">📌 Restore with Limited Access (Read Only)</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors">
                  <input v-model="restoreCondition" value="payment" type="radio" class="text-emerald-600 focus:ring-emerald-500" />
                  <span class="text-slate-800 dark:text-slate-200 font-medium">📌 Restore After Payment Confirmation</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Add Note / Reference</label>
              <input v-model="restoreNote" type="text" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-emerald-500" />
            </div>

            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
              <button type="button" @click="selectedUserForRestore = null" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold border border-slate-200 dark:border-transparent transition-colors cursor-pointer">Cancel</button>
              <button @click="confirmRestore" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition-all cursor-pointer">
                ✅ Confirm Restore
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
