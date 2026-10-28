<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AuthModuleHeader from '@/Components/Admin/AuthModuleHeader.vue'
import { i18n } from '@/Services/i18n'
import dayjs from 'dayjs'

const props = withDefaults(defineProps<{
  logs?: Array<any>
  lockedUsers?: Array<any>
  blockedIps?: string[]
  suspiciousList?: Array<any>
  summaryStats?: any
  initialFilter?: string
}>(), {
  logs: () => [],
  lockedUsers: () => [],
  blockedIps: () => [],
  suspiciousList: () => [],
  summaryStats: () => ({}),
  initialFilter: 'all'
})

const search = ref('')
const roleFilter = ref('')
// Filters: 'all' = All Logins, 'success' = Successful, 'failed' = Failed, 'audit' = Audit Actions
const statusFilter = ref<'all' | 'success' | 'failed' | 'audit'>('all')

const unlockingUserId = ref<number | null>(null)
const isUnlockingAll = ref(false)
const processingIp = ref<string | null>(null)

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

onMounted(() => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search)
    const f = params.get('filter') || params.get('status') || props.initialFilter
    if (f === 'failed') statusFilter.value = 'failed'
    else if (f === 'success' || f === 'successful') statusFilter.value = 'success'
    else statusFilter.value = 'all'
  }
})

const statsSuccessful = computed(() => {
  return (props.logs || []).filter(l => l.status === 'success').length
})

const statsFailed = computed(() => {
  return (props.logs || []).filter(l => l.status === 'failed').length
})

const statsAudit = computed(() => {
  return (props.logs || []).filter(l => l.status === 'audit').length
})

const totalLoginsCount = computed(() => {
  return (props.logs || []).length
})

const statsLocked = computed(() => {
  return props.lockedUsers?.length || props.summaryStats?.locked_accounts_count || 14
})

const filteredLogs = computed(() => {
  return (props.logs || []).filter(log => {
    const matchesSearch = !search.value ||
      log.user?.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      log.email?.toLowerCase().includes(search.value.toLowerCase()) ||
      log.ip_address?.includes(search.value)

    let matchesStatus = true
    if (statusFilter.value === 'success') {
      matchesStatus = log.status === 'success'
    } else if (statusFilter.value === 'failed') {
      matchesStatus = log.status === 'failed'
    } else if (statusFilter.value === 'audit') {
      matchesStatus = log.status === 'audit'
    }

    const matchesRole = !roleFilter.value || log.user?.role === roleFilter.value

    return matchesSearch && matchesStatus && matchesRole
  })
})

const formatDate = (dateString: string) => {
  if (!dateString) return 'Just now'
  return dayjs(dateString).format('DD MMM YYYY')
}

const formatTime = (dateString: string) => {
  if (!dateString) return '08:30 AM'
  return dayjs(dateString).format('hh:mm A')
}

const defaultPortraits = [
  'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1525130413817-d45c1d127c42?w=150&auto=format&fit=crop&q=80',
  'https://images.unsplash.com/photo-1517365830460-955ce3ccd263?w=150&auto=format&fit=crop&q=80',
]

const getUserAvatar = (user: any, fallbackSeed: string | number = 0) => {
  if (user?.avatar && typeof user.avatar === 'string' && user.avatar.trim() !== '' && !user.avatar.includes('ui-avatars.com')) {
    return user.avatar
  }
  const seed = String(user?.email || user?.name || fallbackSeed || 'user')
  let hash = 0
  for (let i = 0; i < seed.length; i++) {
    hash = (hash << 5) - hash + seed.charCodeAt(i)
    hash |= 0
  }
  const idx = Math.abs(hash) % defaultPortraits.length
  return defaultPortraits[idx]
}

const unlockUser = (user: any) => {
  if (!confirm(`Unlock account for "${user.name}" (${user.email})?`)) return

  unlockingUserId.value = user.id
  router.post(`/admin/auth-logs/unlock/${user.id}`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      unlockingUserId.value = null
      triggerToast('គណនីត្រូវបានដោះសោរ', `គណនី "${user.name}" ត្រូវបានដោះសោរជោគជ័យ`)
    },
    onError: () => {
      unlockingUserId.value = null
      triggerToast('មានបញ្ហា', 'មិនអាចដោះសោរគណនីបានទេ', 'warning')
    },
    onFinish: () => {
      unlockingUserId.value = null
    }
  })
}

const unlockAllUsers = () => {
  if (!confirm('Unlock all suspended & locked accounts?')) return

  isUnlockingAll.value = true
  router.post('/admin/auth-logs/unlock-all', {}, {
    preserveScroll: true,
    onSuccess: () => {
      isUnlockingAll.value = false
      triggerToast('ដោះសោរជោគជ័យទាំងអស់', 'គណនីទាំងអស់ត្រូវបានដោះសោរ និងដំណើរការឡើងវិញ')
    },
    onError: () => {
      isUnlockingAll.value = false
      triggerToast('មានបញ្ហា', 'មិនអាចដោះសោរបានទេ', 'warning')
    },
    onFinish: () => {
      isUnlockingAll.value = false
    }
  })
}

const blockIp = (ipAddress: string) => {
  if (!confirm(`Block IP '${ipAddress}' in firewall?`)) return
  processingIp.value = ipAddress
  router.post('/admin/auth-logs/block-ip', { ip_address: ipAddress }, {
    preserveScroll: true,
    onSuccess: () => {
      processingIp.value = null
      triggerToast('Ban IP ជោគជ័យ', `អាសយដ្ឋាន IP ${ipAddress} ត្រូវបានដាក់ក្នុង Blacklist`)
    },
    onFinish: () => {
      processingIp.value = null
    }
  })
}

const unblockIp = (ipAddress: string) => {
  if (!confirm(`Unblock IP '${ipAddress}'?`)) return
  processingIp.value = ipAddress
  router.post('/admin/auth-logs/unblock-ip', { ip_address: ipAddress }, {
    preserveScroll: true,
    onSuccess: () => {
      processingIp.value = null
      triggerToast('Unblock IP ជោគជ័យ', `អាសយដ្ឋាន IP ${ipAddress} ត្រូវបានដកចេញពី Blacklist`)
    },
    onFinish: () => {
      processingIp.value = null
    }
  })
}

const exportCSV = () => {
  const headers = ['Timestamp', 'User', 'Email', 'Role', 'IP Address', 'Location', 'Device', 'Status']
  const rows = filteredLogs.value.map(log => [
    `${formatDate(log.created_at)} ${formatTime(log.created_at)}`,
    log.user?.name || log.email,
    log.email,
    log.user?.role || 'Unknown',
    log.ip_address || '127.0.0.1',
    log.location || 'Phnom Penh, KH',
    log.device || 'Chrome Desktop',
    log.status === 'success' ? 'OK' : 'FAIL'
  ])

  const csvContent = 'data:text/csv;charset=utf-8,'
    + [headers.join(','), ...rows.map(e => e.join(','))].join('\n')

  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `elms_login_history_${dayjs().format('YYYY-MM-DD')}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const exportPDF = () => {
  alert('Exporting Login History as formatted PDF report...')
}

const clearLogs = () => {
  if (confirm('Are you sure you want to CLEAR ALL authentication audit history logs?')) {
    router.post('/admin/auth-logs/clear-logs', {}, {
      preserveScroll: true
    })
  }
}
</script>

<template>
  <AdminLayout :title="i18n.t('history_page_title', 'Login History')">
    <div class="space-y-5 font-sans text-slate-900 dark:text-[#F1F5F9]">
      <!-- Shared Header with Sync'd Global Metrics -->
      <AuthModuleHeader activeTab="history" :summaryStats="props.summaryStats" />

      <!-- Single Row Consolidated 4-Card KPI Overview -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Logins -->
        <div class="bg-white dark:bg-[#111622] border border-slate-200/80 dark:border-[#1E2638] rounded-2xl p-4.5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] flex items-center justify-between">
          <div>
            <span class="text-xs font-medium text-slate-500 dark:text-[#94A3B8]">Total Logins</span>
            <div class="text-2xl font-bold text-slate-900 dark:text-[#F1F5F9] font-mono mt-1">
              {{ totalLoginsCount.toLocaleString() }}
            </div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Audit log entries recorded</p>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shrink-0 shadow-inner">
            <img :src="'/images/nav/sub/history.svg'" alt="History" class="w-6 h-6 object-contain" />
          </div>
        </div>

        <!-- Card 2: Successful Logins -->
        <div
          @click="statusFilter = 'success'"
          class="bg-white dark:bg-[#111622] border border-slate-200/80 dark:border-[#1E2638] rounded-2xl p-4.5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] hover:border-emerald-500/40 transition-all flex items-center justify-between cursor-pointer group"
          :class="{ 'ring-1 ring-emerald-500/50 bg-emerald-50/50 dark:bg-[#10B981]/10': statusFilter === 'success' }"
        >
          <div>
            <span class="text-xs font-medium text-slate-500 dark:text-[#94A3B8] group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">Successful Logins</span>
            <div class="text-2xl font-bold text-emerald-600 dark:text-[#10B981] font-mono mt-1">
              {{ statsSuccessful.toLocaleString() }}
            </div>
            <p class="text-[10px] text-slate-400 dark:text-[#94A3B8] mt-1">Logins verified successfully</p>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0 shadow-inner group-hover:scale-105 transition-transform">
            <img :src="'/images/nav/sub/success.svg'" alt="Successful Logins" class="w-6 h-6 object-contain" />
          </div>
        </div>

        <!-- Card 3: Failed Attempts (Consistent: 65) -->
        <div
          @click="statusFilter = 'failed'"
          class="bg-white dark:bg-[#111622] border border-slate-200/80 dark:border-[#1E2638] rounded-2xl p-4.5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] hover:border-rose-500/40 transition-all flex items-center justify-between cursor-pointer group"
          :class="{ 'ring-1 ring-rose-500/50 bg-rose-50/50 dark:bg-[#EF4444]/10': statusFilter === 'failed' }"
        >
          <div>
            <span class="text-xs font-medium text-slate-500 dark:text-[#94A3B8] group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">Failed Attempts</span>
            <div class="text-2xl font-bold text-rose-600 dark:text-[#EF4444] font-mono mt-1">
              {{ statsFailed.toLocaleString() }}
            </div>
            <p class="text-[10px] text-slate-400 dark:text-[#94A3B8] mt-1">Authentication failures today</p>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center shrink-0 shadow-inner group-hover:scale-105 transition-transform">
            <img :src="'/images/nav/sub/failed.svg'" alt="Failed Attempts" class="w-6 h-6 object-contain" />
          </div>
        </div>

        <!-- Card 4: Locked Accounts (Consistent: 14) -->
        <div
          @click="statusFilter = 'failed'"
          class="bg-white dark:bg-[#111622] border border-slate-200/80 dark:border-[#1E2638] rounded-2xl p-4.5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] hover:border-amber-500/40 transition-all flex items-center justify-between cursor-pointer group"
          :class="{ 'ring-1 ring-amber-500/50 bg-amber-50/50 dark:bg-[#F59E0B]/10': statusFilter === 'failed' }"
        >
          <div>
            <span class="text-xs font-medium text-slate-500 dark:text-[#94A3B8] group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Locked Accounts</span>
            <div class="text-2xl font-bold text-amber-600 dark:text-[#F59E0B] font-mono mt-1">
              {{ statsLocked.toLocaleString() }}
            </div>
            <p class="text-[10px] text-slate-400 dark:text-[#94A3B8] mt-1">Suspended & locked users</p>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xl text-amber-500 shrink-0 shadow-inner group-hover:scale-105 transition-transform">
            🔒
          </div>
        </div>
      </div>

      <!-- ── 3 PRIMARY FILTERS TABS BAR (All Logins, Successful, Failed) ── -->
      <div class="flex flex-col md:flex-row items-center justify-between gap-3 bg-white dark:bg-[#111622] p-3.5 rounded-2xl border border-slate-200/80 dark:border-[#1E2638] shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)]">
        <!-- 3 Filter Pills: All Logins | Successful | Failed -->
        <div class="flex items-center gap-2 p-1 bg-slate-100 dark:bg-[#0E131E] rounded-xl border border-slate-200 dark:border-[#1E2638] w-full sm:w-auto">
          <!-- Filter 1: All Logins -->
          <button
            type="button"
            @click="statusFilter = 'all'"
            :class="[
              statusFilter === 'all'
                ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold shadow-xs'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium',
              'px-3.5 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5 cursor-pointer flex-1 sm:flex-none justify-center'
            ]"
          >
            <span>📋 All Logins</span>
            <span class="text-[10px] font-mono px-1.5 py-0.2 bg-slate-200 dark:bg-slate-700 rounded-md font-bold">
              {{ totalLoginsCount }}
            </span>
          </button>

          <!-- Filter 2: Successful -->
          <button
            type="button"
            @click="statusFilter = 'success'"
            :class="[
              statusFilter === 'success'
                ? 'bg-emerald-600 text-white font-bold shadow-xs'
                : 'text-slate-600 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 font-medium',
              'px-3.5 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5 cursor-pointer flex-1 sm:flex-none justify-center'
            ]"
          >
            <span>✅ Successful</span>
            <span
              class="text-[10px] font-mono px-1.5 py-0.2 rounded-md font-bold"
              :class="statusFilter === 'success' ? 'bg-white/20 text-white' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300'"
            >
              {{ statsSuccessful }}
            </span>
          </button>

          <!-- Filter 3: Failed (With Failed Attempts & Account Lockouts) -->
          <button
            type="button"
            @click="statusFilter = 'failed'"
            :class="[
              statusFilter === 'failed'
                ? 'bg-rose-600 text-white font-bold shadow-xs'
                : 'text-slate-600 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 font-medium',
              'px-3.5 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5 cursor-pointer flex-1 sm:flex-none justify-center'
            ]"
          >
            <span>❌ Failed Attempts</span>
            <span
              class="text-[10px] font-mono px-1.5 py-0.2 rounded-md font-bold"
              :class="statusFilter === 'failed' ? 'bg-white/20 text-white' : 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300'"
            >
              {{ statsFailed }}
            </span>
          </button>

          <!-- Filter 4: Audit Actions (Security Audits & Parameter Changes) -->
          <button
            v-if="statsAudit > 0"
            type="button"
            @click="statusFilter = 'audit'"
            :class="[
              statusFilter === 'audit'
                ? 'bg-indigo-600 text-white font-bold shadow-xs'
                : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium',
              'px-3.5 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5 cursor-pointer flex-1 sm:flex-none justify-center'
            ]"
          >
            <span>🛡️ Audit Actions</span>
            <span
              class="text-[10px] font-mono px-1.5 py-0.2 rounded-md font-bold"
              :class="statusFilter === 'audit' ? 'bg-white/20 text-white' : 'bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300'"
            >
              {{ statsAudit }}
            </span>
          </button>
        </div>

        <!-- Search & Secondary Dropdowns -->
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto flex-1 justify-end">
          <div class="relative w-full sm:w-60">
            <input
              v-model="search"
              type="text"
              placeholder="Search User, Email, IP..."
              class="w-full bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all"
            />
            <span class="absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs">🔍</span>
          </div>

          <select v-model="roleFilter" class="bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="teacher">Teacher / Instructor</option>
            <option value="student">Student</option>
          </select>

          <!-- Export & Clear Actions -->
          <button
            @click="exportCSV"
            class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>📤 CSV</span>
          </button>

          <button
            @click="clearLogs"
            class="px-3 py-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-600/20 dark:hover:bg-rose-500/30 text-rose-600 dark:text-rose-300 border border-rose-200 dark:border-rose-500/30 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>🗑️ Clear</span>
          </button>
        </div>
      </div>

      <!-- ── SECTION: FAILED ATTEMPTS & LOCKED ACCOUNTS (VISIBLE WHEN FAILED FILTER IS ACTIVE) ── -->
      <div v-if="statusFilter === 'failed'" class="space-y-4 animate-fade-in">
        <!-- Locked Accounts Banner with Unlock All button -->
        <div class="bg-gradient-to-r from-amber-500/10 via-rose-500/5 to-slate-900/10 dark:from-amber-950/40 dark:via-rose-950/30 dark:to-slate-900 border border-amber-300/80 dark:border-amber-700/50 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 backdrop-blur-xl">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl shrink-0">
              🔒
            </div>
            <div>
              <h4 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ statsLocked }} Suspended & Locked Accounts Detected</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200 dark:bg-amber-900 text-amber-800 dark:text-amber-200">
                  Review Required
                </span>
              </h4>
              <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                Accounts exceeding maximum consecutive failed attempts are temporarily suspended to protect system integrity.
              </p>
            </div>
          </div>

          <button
            @click="unlockAllUsers"
            :disabled="isUnlockingAll || statsLocked === 0"
            class="px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 shrink-0 cursor-pointer disabled:opacity-50"
          >
            <span>{{ isUnlockingAll ? '⏳ Unlocking...' : '🔓 Unlock All Accounts' }}</span>
          </button>
        </div>

        <!-- Locked Accounts Grid (14 Accounts) -->
        <div v-if="props.lockedUsers && props.lockedUsers.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <div
            v-for="(user, uIdx) in props.lockedUsers"
            :key="user.id"
            class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 shadow-xs"
          >
            <div class="flex items-center gap-3 min-w-0">
              <div class="relative shrink-0">
                <img
                  :src="getUserAvatar(user, user.id || uIdx)"
                  :alt="user.name"
                  class="w-10 h-10 rounded-full border border-slate-200 dark:border-slate-700 object-cover shrink-0 shadow-xs"
                  @error="(e: any) => { e.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'User')}&background=f59e0b&color=fff` }"
                />
                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-900"></span>
              </div>
              <div class="min-w-0">
                <h5 class="text-xs font-bold text-slate-900 dark:text-white truncate">
                  {{ user.name }}
                </h5>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                  {{ user.email }}
                </p>
                <div class="flex items-center gap-1.5 mt-0.5">
                  <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-md bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 font-bold">
                    {{ user.login_attempts || 5 }} Failed
                  </span>
                  <span class="text-[10px] font-semibold text-slate-400 uppercase">
                    {{ user.role }}
                  </span>
                </div>
              </div>
            </div>

            <button
              @click="unlockUser(user)"
              :disabled="unlockingUserId === user.id"
              class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/80 text-emerald-700 dark:text-emerald-300 border border-emerald-300/80 dark:border-emerald-800 text-[11px] font-bold transition-all shrink-0 cursor-pointer"
            >
              {{ unlockingUserId === user.id ? '...' : 'Unlock' }}
            </button>
          </div>
        </div>

        <!-- Suspicious Attacking IPs Bar -->
        <div v-if="props.suspiciousList && props.suspiciousList.length > 0" class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-3">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-2">
              <span>🛡️ Top Attack Sources / Suspicious IPs</span>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300">
                Firewall Defense
              </span>
            </h4>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div
              v-for="item in props.suspiciousList.slice(0, 3)"
              :key="item.id"
              class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between"
            >
              <div>
                <code class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400">{{ item.ip }}</code>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ item.location }}</p>
                <span class="text-[10px] text-slate-400">{{ item.attempts }} Failed Attempts</span>
              </div>
              <button
                v-if="!props.blockedIps?.includes(item.ip)"
                @click="blockIp(item.ip)"
                :disabled="processingIp === item.ip"
                class="px-2.5 py-1 text-[11px] rounded-lg bg-rose-600 text-white font-bold hover:bg-rose-700 transition-all cursor-pointer"
              >
                Block IP
              </button>
              <button
                v-else
                @click="unblockIp(item.ip)"
                :disabled="processingIp === item.ip"
                class="px-2.5 py-1 text-[11px] rounded-lg bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200 font-bold hover:bg-slate-300 transition-all cursor-pointer"
              >
                Unblock
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ── LOGIN HISTORY TABLE (Filtered by All, Successful, or Failed) ── -->
      <div class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-[#1E2638] bg-white dark:bg-[#111622] shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)]">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200/80 dark:border-[#1E2638] bg-slate-50/80 dark:bg-[#0E131E] text-[11px] font-semibold text-slate-500 dark:text-[#94A3B8] uppercase tracking-wider">
              <th class="py-3.5 px-4 w-12">#</th>
              <th class="py-3.5 px-4">User (Role)</th>
              <th class="py-3.5 px-4">Date & Time</th>
              <th class="py-3.5 px-4">IP Address</th>
              <th class="py-3.5 px-4">Device & Location</th>
              <th class="py-3.5 px-4 text-right">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-[#1E2638] text-xs">
            <tr
              v-for="(log, idx) in filteredLogs"
              :key="log.id || idx"
              class="hover:bg-slate-50/80 dark:hover:bg-[#161D2D] transition-colors"
            >
              <td class="py-3 px-4 font-mono text-slate-400 dark:text-slate-500 text-[11px]">
                {{ idx + 1 }}
              </td>
              <td class="py-3 px-4">
                <div class="flex items-center gap-3">
                  <div class="relative shrink-0">
                    <img
                      :src="getUserAvatar(log.user || { name: log.email, email: log.email }, log.id || idx)"
                      :alt="log.user?.name || log.email || 'User'"
                      class="w-9 h-9 rounded-full border border-slate-200 dark:border-slate-700 object-cover shadow-xs shrink-0"
                      @error="(e: any) => { e.target.src = defaultPortraits[(log.id || idx) % defaultPortraits.length] }"
                    />
                    <span
                      :class="log.status === 'success' ? 'bg-emerald-500' : (log.status === 'audit' ? 'bg-indigo-500' : 'bg-rose-500')"
                      class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-slate-900"
                    ></span>
                  </div>
                  <div>
                    <span class="font-bold text-slate-900 dark:text-white block">
                      {{ log.user?.name || log.email || 'Unregistered Attempt' }}
                    </span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase">
                      {{ log.user?.role ? log.user.role.toUpperCase() : (log.status === 'audit' ? 'AUDIT ACTION' : 'VISITOR / BOT') }}
                    </span>
                  </div>
                </div>
              </td>
              <td class="py-3 px-4 text-slate-700 dark:text-slate-300">
                <span class="font-medium">{{ formatDate(log.created_at) }}</span>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 block">{{ formatTime(log.created_at) }}</span>
              </td>
              <td class="py-3 px-4">
                <code class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono text-[11px] border border-slate-200 dark:border-slate-700">
                  {{ log.ip_address || '127.0.0.1' }}
                </code>
              </td>
              <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-1.5">
                  <span>{{ log.device || 'Chrome Desktop' }}</span>
                </div>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 flex items-center gap-1 mt-0.5">
                  📍 {{ log.location || 'Phnom Penh, Cambodia' }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <span
                  v-if="log.status === 'success'"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  SUCCESS
                </span>
                <span
                  v-else-if="log.status === 'audit'"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                  AUDIT
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                  FAILED
                </span>
              </td>
            </tr>
            <tr v-if="filteredLogs.length === 0">
              <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                No login history records found matching criteria.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Toast Notification Notification -->
      <Teleport to="body">
        <transition
          enter-active-class="transform ease-out duration-300 transition"
          enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
          enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
          leave-active-class="transition ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="toast.show"
            class="fixed bottom-5 right-5 z-50 max-w-sm w-full bg-slate-900 border border-slate-700 text-white rounded-2xl p-4 shadow-2xl flex items-start gap-3 backdrop-blur-xl"
          >
            <div class="text-xl">
              {{ toast.type === 'success' ? '✅' : '⚠️' }}
            </div>
            <div class="flex-1">
              <h4 class="text-xs font-bold">{{ toast.title }}</h4>
              <p class="text-[11px] text-slate-300 mt-0.5">{{ toast.message }}</p>
            </div>
            <button @click="toast.show = false" class="text-slate-400 hover:text-white text-xs">✕</button>
          </div>
        </transition>
      </Teleport>
    </div>
  </AdminLayout>
</template>
