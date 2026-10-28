<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AuthModuleHeader from '@/Components/Admin/AuthModuleHeader.vue'
import { i18n } from '@/Services/i18n'
import dayjs from 'dayjs'

const props = withDefaults(defineProps<{
  activeSessions?: Array<any>
  roleBreakdown?: Record<string, any>
  summaryStats?: any
}>(), {
  activeSessions: () => [],
  roleBreakdown: () => ({}),
  summaryStats: () => ({})
})

const search = ref('')
const selectedRole = ref('')
const selectedStatus = ref('')
const isRevokeAllModalOpen = ref(false)

const totalActiveNow = computed(() => {
  if (props.activeSessions && props.activeSessions.length > 0) {
    return props.activeSessions.filter(s => !s.is_revoked).length
  }
  return props.summaryStats?.active_sessions_count ?? 0
})

const mobileUsersCount = computed(() => {
  if (!props.activeSessions || props.activeSessions.length === 0) return 0
  return props.activeSessions.filter(s => !s.is_revoked && (s.user_agent || '').toLowerCase().includes('mobile')).length
})

const desktopUsersCount = computed(() => {
  return Math.max(0, totalActiveNow.value - mobileUsersCount.value)
})

const filteredSessions = computed(() => {
  return (props.activeSessions || []).filter(session => {
    const matchesSearch = !search.value ||
      session.user?.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      session.user?.email?.toLowerCase().includes(search.value.toLowerCase()) ||
      session.ip_address?.includes(search.value)

    const matchesRole = !selectedRole.value || session.user?.role === selectedRole.value
    const isRevoked = session.is_revoked
    const matchesStatus = !selectedStatus.value ||
      (selectedStatus.value === 'active' ? !isRevoked : isRevoked)

    return matchesSearch && matchesRole && matchesStatus
  })
})

const formatDate = (dateString: string) => {
  if (!dateString) return 'Just now'
  return dayjs(dateString).format('HH:mm - DD MMM')
}

const formatTimeAgo = (dateString: string) => {
  if (!dateString) return 'Just now'
  const diffMins = dayjs().diff(dayjs(dateString), 'minute')
  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  return `${Math.floor(diffMins / 60)}h ago`
}

const revokeSession = (sessionId: number, userName: string) => {
  if (confirm(`Are you sure you want to force revoke session for '${userName}'?`)) {
    router.post(`/admin/auth-logs/revoke/${sessionId}`)
  }
}

const triggerRevokeAll = () => {
  isRevokeAllModalOpen.value = true
}

const confirmRevokeAllSessions = () => {
  isRevokeAllModalOpen.value = false
  router.post('/admin/auth-logs/revoke-all')
}

const refreshSessions = () => {
  router.reload({ preserveScroll: true } as any)
}

const exportSessionsCSV = () => {
  const headers = ['#', 'User', 'Email', 'Role', 'IP Address', 'Location', 'Device', 'Status', 'Expires At']
  const rows = filteredSessions.value.map((s, idx) => [
    idx + 1,
    s.user?.name || 'User',
    s.user?.email || '-',
    s.user?.role || 'User',
    s.ip_address || '127.0.0.1',
    'Phnom Penh, KH',
    s.user_agent?.includes('Mobile') ? 'Mobile' : 'Desktop',
    s.is_revoked ? 'Revoked' : 'Active',
    formatDate(s.expires_at)
  ])

  const csvContent = 'data:text/csv;charset=utf-8,'
    + [headers.join(','), ...rows.map(e => e.join(','))].join('\n')

  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `elms_active_sessions_${dayjs().format('YYYY-MM-DD')}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<template>
  <AdminLayout :title="i18n.t('sessions_page_title', 'Active Sessions Monitor')">
    <div class="space-y-5 font-sans">
      <!-- Shared Header with Sync'd Global Metrics -->
      <AuthModuleHeader activeTab="sessions" :summaryStats="props.summaryStats" />

      <!-- Top 3 Synchronized KPI Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Card 1: Total Active Now -->
        <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4.5 backdrop-blur-xl hover:border-emerald-500/30 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
          <div>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ i18n.t('sessions_total_active', 'Total Active Now') }}</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1 flex items-baseline gap-2">
              <span>{{ totalActiveNow.toLocaleString() }}</span>
              <span class="text-xs text-emerald-600 dark:text-emerald-400 font-sans font-semibold">Sessions</span>
            </div>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0 shadow-inner">
            <img :src="'/images/nav/sub/sessions.svg'" alt="Sessions" class="w-6 h-6 object-contain" />
          </div>
        </div>

        <!-- Card 2: Mobile Users -->
        <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4.5 backdrop-blur-xl hover:border-indigo-500/30 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
          <div>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ i18n.t('sessions_mobile_users', 'Mobile Users') }}</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1 flex items-baseline gap-2">
              <span>{{ mobileUsersCount.toLocaleString() }}</span>
              <span class="text-xs text-indigo-600 dark:text-indigo-300 font-sans font-semibold">Users</span>
            </div>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-xl text-indigo-500 dark:text-indigo-400 shrink-0">
            📱
          </div>
        </div>

        <!-- Card 3: Desktop Users -->
        <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4.5 backdrop-blur-xl hover:border-cyan-500/30 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
          <div>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ i18n.t('sessions_desktop_users', 'Desktop Users') }}</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1 flex items-baseline gap-2">
              <span>{{ desktopUsersCount.toLocaleString() }}</span>
              <span class="text-xs text-cyan-600 dark:text-cyan-300 font-sans font-semibold">Users</span>
            </div>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-xl text-cyan-500 dark:text-cyan-400 shrink-0">
            💻
          </div>
        </div>
      </div>

      <!-- Single Unified Toolbar -->
      <div class="flex flex-col md:flex-row items-center justify-between gap-3 bg-white dark:bg-slate-800/30 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 backdrop-blur-xl shadow-sm dark:shadow-none">
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto flex-1">
          <div class="relative w-full sm:w-64">
            <input
              v-model="search"
              type="text"
              :placeholder="i18n.t('sessions_search_placeholder', 'Search User, Email, IP...')"
              class="w-full bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all"
            />
            <span class="absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs">🔍</span>
          </div>

          <select v-model="selectedRole" class="bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
            <option value="">{{ i18n.t('sessions_role_all', 'All Roles') }}</option>
            <option value="admin">Admin</option>
            <option value="teacher">Teacher / Instructor</option>
            <option value="student">Student</option>
          </select>

          <select v-model="selectedStatus" class="bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
            <option value="">{{ i18n.t('sessions_status_all', 'All Status') }}</option>
            <option value="active">{{ i18n.t('sessions_status_active', 'Active Sessions') }}</option>
            <option value="revoked">{{ i18n.t('sessions_status_revoked', 'Revoked Tokens') }}</option>
          </select>

          <button
            @click="refreshSessions"
            class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
            title="Refresh Sessions"
          >
            <span>🔄</span>
            <span>{{ i18n.t('sessions_btn_refresh', 'Refresh') }}</span>
          </button>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto justify-end shrink-0">
          <button
            @click="exportSessionsCSV"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>📤</span>
            <span>{{ i18n.t('sessions_btn_export', 'Export CSV') }}</span>
          </button>

          <!-- Danger Action: Requires Security Confirmation Modal -->
          <button
            @click="triggerRevokeAll"
            class="px-3.5 py-2 bg-red-600/90 hover:bg-red-500 text-white font-bold text-xs rounded-xl shadow-md shadow-red-600/20 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>🚫</span>
            <span>{{ i18n.t('sessions_btn_revoke_all', 'Revoke All Sessions') }}</span>
          </button>
        </div>
      </div>

      <!-- SESSION LIST Table -->
      <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 backdrop-blur-xl shadow-sm dark:shadow-2xl">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <th class="py-3.5 px-4 w-12">#</th>
              <th class="py-3.5 px-4">👤 {{ i18n.t('sessions_th_user', 'User') }}</th>
              <th class="py-3.5 px-4">{{ i18n.t('sessions_th_role', 'Role') }}</th>
              <th class="py-3.5 px-4">🌐 {{ i18n.t('sessions_th_ip', 'IP Address & Location') }}</th>
              <th class="py-3.5 px-4">📱 {{ i18n.t('sessions_th_device', 'Device & Browser') }}</th>
              <th class="py-3.5 px-4 text-right">{{ i18n.t('sessions_th_action', 'Action') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            <tr v-for="(session, idx) in filteredSessions" :key="session.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
              <td class="py-3.5 px-4 font-mono text-slate-400 dark:text-slate-500">{{ String(idx + 1).padStart(2, '0') }}</td>

              <td class="py-3.5 px-4">
                <div class="flex items-center gap-3">
                  <div class="relative shrink-0">
                    <img
                      :src="session.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(session.user?.name || 'User')}&background=6366f1&color=fff`"
                      class="w-9 h-9 rounded-full border border-slate-200 dark:border-slate-700 object-cover"
                    />
                    <span
                      :class="session.is_revoked ? 'bg-rose-500' : 'bg-emerald-500'"
                      class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-slate-900"
                    ></span>
                  </div>
                  <div>
                    <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                      <span>{{ session.user?.name || 'Unknown User' }}</span>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">{{ session.user?.email || '-' }}</div>
                  </div>
                </div>
              </td>

              <td class="py-3.5 px-4">
                <span
                  :class="[
                    session.user?.role === 'admin' ? 'bg-purple-500/10 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 border-purple-500/30' :
                    session.user?.role === 'teacher' ? 'bg-blue-500/10 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 border-blue-500/30' :
                    'bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border-emerald-500/30',
                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold border capitalize inline-block'
                  ]"
                >
                  {{ session.user?.role || 'User' }}
                </span>
              </td>

              <td class="py-3.5 px-4">
                <div class="font-mono text-indigo-600 dark:text-indigo-300 font-bold">{{ session.ip_address || '127.0.0.1' }}</div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5">
                  <span>📍 Phnom Penh</span>
                  <span>•</span>
                  <span>🕐 {{ formatTimeAgo(session.created_at) }}</span>
                </div>
              </td>

              <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                <div class="font-medium flex items-center gap-1.5">
                  <span>{{ (session.user_agent || '').toLowerCase().includes('mobile') ? '📱 Mobile' : '💻 Desktop' }}</span>
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">
                  {{ (session.user_agent || '').toLowerCase().includes('safari') ? 'Safari Browser' : 'Chrome Browser' }}
                </div>
              </td>

              <td class="py-3.5 px-4 text-right">
                <button
                  v-if="!session.is_revoked"
                  @click="revokeSession(session.id, session.user?.name || 'User')"
                  class="px-3 py-1.5 text-xs font-bold text-red-500 hover:text-red-600 dark:text-red-400 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 border border-red-200 dark:border-red-500/20 rounded-xl transition-all flex items-center gap-1.5 ml-auto cursor-pointer"
                >
                  <span>🚫</span> {{ i18n.t('sessions_btn_revoke', 'Revoke') }}
                </button>
                <span v-else class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 italic">
                  Revoked
                </span>
              </td>
            </tr>

            <tr v-if="filteredSessions.length === 0">
              <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                No active sessions found matching criteria.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Security Confirmation Modal for Revoke All Sessions -->
      <div v-if="isRevokeAllModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-md">
        <div class="bg-white dark:bg-slate-900/95 border border-slate-200 dark:border-rose-900/50 rounded-3xl p-6 w-full max-w-md space-y-5 shadow-2xl backdrop-blur-xl">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 dark:bg-rose-500/20 border border-rose-500/20 dark:border-rose-500/30 flex items-center justify-center text-2xl text-rose-500 dark:text-rose-400 shrink-0">
              ⚠️
            </div>
            <div>
              <h3 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">
                {{ i18n.t('modal_revoke_all_title', 'Security Confirmation: Revoke All Sessions') }}
              </h3>
              <p class="text-xs text-slate-600 dark:text-slate-300 mt-2 leading-relaxed">
                {{ i18n.t('modal_revoke_all_desc', 'Are you sure you want to terminate all active sessions? All currently logged-in users will be immediately logged out of the system.') }}
              </p>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
            <button
              type="button"
              @click="isRevokeAllModalOpen = false"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs rounded-xl border border-slate-200 dark:border-slate-700/80 transition-all cursor-pointer"
            >
              {{ i18n.t('modal_btn_cancel', 'Cancel') }}
            </button>

            <button
              type="button"
              @click="confirmRevokeAllSessions"
              class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow-md shadow-rose-600/30 transition-all flex items-center gap-1.5 cursor-pointer"
            >
              <span>🚫 {{ i18n.t('modal_btn_confirm_revoke', 'Confirm & Revoke All Sessions') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
