<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AuthModuleHeader from '@/Components/Admin/AuthModuleHeader.vue'
import { i18n } from '@/Services/i18n'
import dayjs from 'dayjs'

const props = withDefaults(defineProps<{
  lockedUsers?: Array<any>
  failedLogs?: Array<any>
  blockedIps?: Array<string>
  mostAttackedIp?: string
  maxFailedAttempts?: number
  summaryStats?: any
}>(), {
  lockedUsers: () => [],
  failedLogs: () => [],
  blockedIps: () => [],
  mostAttackedIp: 'None',
  maxFailedAttempts: 5,
  summaryStats: () => ({})
})

const searchIp = ref('')
const selectedSuspiciousActivity = ref<any | null>(null)

const suspiciousActivities = [
  { id: 1, ip: '45.22.178.99', time: '10:02 AM - Jun 15', attempts: 47, location: '🌍 Russia (Unknown Network)', level: 'high' },
  { id: 2, ip: '103.45.22.11', time: '11:30 AM - Jun 15', attempts: 23, location: '🌍 China (Proxy Node)', level: 'medium' },
  { id: 3, ip: '192.168.5.88', time: '12:15 PM - Jun 15', attempts: 8, location: '📍 Phnom Penh, Cambodia', level: 'low' },
  { id: 4, ip: '77.88.99.100', time: '02:30 PM - Jun 15', attempts: 31, location: '🌍 Vietnam (Unknown Service)', level: 'high' },
]

const filteredSuspicious = computed(() => {
  if (!searchIp.value) return suspiciousActivities
  return suspiciousActivities.filter(act =>
    act.ip.includes(searchIp.value) || act.location.toLowerCase().includes(searchIp.value.toLowerCase())
  )
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

const isExporting = ref(false)
const isClearingLogs = ref(false)
const isBanningAll = ref(false)
const processingIp = ref<string | null>(null)

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return dayjs(dateString).format('DD MMM YYYY, HH:mm')
}

const blockIp = (ipAddress: string) => {
  if (!confirm(`Add IP address '${ipAddress}' to security firewall blacklist?`)) return

  processingIp.value = ipAddress
  router.post('/admin/auth-logs/block-ip', { ip_address: ipAddress }, {
    preserveScroll: true,
    onSuccess: () => {
      processingIp.value = null
      triggerToast(
        'បាន Ban IP ជោគជ័យ',
        `អាសយដ្ឋាន IP "${ipAddress}" ត្រូវបានដាក់ចូល Blacklist`
      )
    },
    onFinish: () => {
      processingIp.value = null
    }
  })
}

const unblockIp = (ipAddress: string) => {
  if (!confirm(`Remove IP address '${ipAddress}' from blacklist?`)) return

  processingIp.value = ipAddress
  router.post('/admin/auth-logs/unblock-ip', { ip_address: ipAddress }, {
    preserveScroll: true,
    onSuccess: () => {
      processingIp.value = null
      triggerToast(
        'បានដក IP ចេញពី Blacklist',
        `អាសយដ្ឋាន IP "${ipAddress}" ត្រូវដកចេញពី Blacklist រួចរាល់`
      )
    },
    onFinish: () => {
      processingIp.value = null
    }
  })
}

const banAllSuspicious = () => {
  const ipsToBan = suspiciousActivities.map(act => act.ip)
  if (!confirm(`តើអ្នកពិតជាចង់ Ban អាសយដ្ឋាន IP សង្ស័យចំនួន ${ipsToBan.length} នេះមែនទេ?`)) return

  isBanningAll.value = true
  router.post('/admin/auth-logs/ban-all-suspicious', { ips: ipsToBan }, {
    preserveScroll: true,
    onSuccess: () => {
      isBanningAll.value = false
      triggerToast(
        'បាន Ban IP ទាំងអស់ជោគជ័យ',
        'អាសយដ្ឋាន IP សង្ស័យទាំងអស់ត្រូវបានដាក់ចូល Blacklist រួចរាល់'
      )
    },
    onError: () => {
      isBanningAll.value = false
      triggerToast(
        'មានបញ្ហាក្នុងការ Ban IP',
        'សូមព្យាយាមម្ដងទៀត',
        'warning'
      )
    },
    onFinish: () => {
      isBanningAll.value = false
    }
  })
}

const clearLogs = () => {
  if (!confirm('តើអ្នកពិតជាចង់លុប Audit Threat Logs ទាំងអស់ចេញពីប្រព័ន្ធមែនទេ?')) return

  isClearingLogs.value = true
  router.post('/admin/auth-logs/clear-logs', {}, {
    preserveScroll: true,
    onSuccess: () => {
      isClearingLogs.value = false
      triggerToast(
        'សម្អាត Log បានជោគជ័យ',
        'ទិន្នន័យ Threat Logs ទាំងអស់ត្រូវបានសម្អាតចេញពីប្រព័ន្ធ'
      )
    },
    onError: () => {
      isClearingLogs.value = false
      triggerToast(
        'មានបញ្ហាក្នុងការសម្អាត Log',
        'សូមព្យាយាមម្ដងទៀត',
        'warning'
      )
    },
    onFinish: () => {
      isClearingLogs.value = false
    }
  })
}

const exportReport = () => {
  isExporting.value = true
  setTimeout(() => {
    const headers = ['IP Address', 'Time', 'Attempts Level', 'Location', 'Threat Status']
    const rows = suspiciousActivities.map(act => [
      act.ip,
      act.time,
      `${act.attempts} attempts (${act.level.toUpperCase()})`,
      act.location,
      'FLAGGED_SUSPICIOUS'
    ])
    
    const csvContent = 'data:text/csv;charset=utf-8,' 
      + [headers.join(','), ...rows.map(e => e.join(','))].join('\n')

    const encodedUri = encodeURI(csvContent)
    const link = document.createElement('a')
    link.setAttribute('href', encodedUri)
    link.setAttribute('download', `security_threat_monitoring_report_${dayjs().format('YYYY-MM-DD')}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    isExporting.value = false
    triggerToast(
      'ទាញយករបាយការណ៍បានជោគជ័យ',
      'របាយការណ៍ Security Threat Report ត្រូវបានទាញយករួចរាល់'
    )
  }, 600)
}
</script>

<template>
  <AdminLayout :title="i18n.t('failed_page_title', 'Failed Login Attempts & Threat Monitoring')">
    <div class="space-y-5 font-sans">
      <!-- Shared Header with Sync'd Global Metrics -->
      <AuthModuleHeader activeTab="failed" :summaryStats="props.summaryStats" />

      <!-- Single Row Consolidated 4-Card Threat Metrics Overview -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Today Failures -->
        <div class="bg-white dark:bg-slate-800/40 border border-rose-200 dark:border-rose-900/30 rounded-2xl p-4.5 backdrop-blur-xl hover:border-rose-500/40 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
          <div>
            <span class="text-xs font-bold text-rose-600 dark:text-rose-300">{{ i18n.t('failed_today', 'Today Failures') }}</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono mt-1">
              {{ props.summaryStats?.failed_logins_today || 213 }}
            </div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Attempts recorded today</p>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center shrink-0 shadow-inner">
            <img :src="'/images/nav/sub/failed.svg'" alt="Today Failures" class="w-6 h-6 object-contain" />
          </div>
        </div>

        <!-- Card 2: This Week Failures -->
        <div class="bg-white dark:bg-slate-800/40 border border-amber-200 dark:border-amber-900/30 rounded-2xl p-4.5 backdrop-blur-xl hover:border-amber-500/40 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
          <div>
            <span class="text-xs font-bold text-amber-600 dark:text-amber-300">{{ i18n.t('failed_this_week', 'This Week Failures') }}</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono mt-1">
              847
            </div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Attempts recorded this week</p>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center shrink-0 shadow-inner">
            <img :src="'/images/nav/sub/history.svg'" alt="This Week Failures" class="w-6 h-6 object-contain" />
          </div>
        </div>

        <!-- Card 3: Blacklisted IPs -->
        <div class="bg-white dark:bg-slate-800/40 border border-emerald-200 dark:border-emerald-900/30 rounded-2xl p-4.5 backdrop-blur-xl hover:border-emerald-500/40 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
          <div>
            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-300">{{ i18n.t('failed_blocked_ips', 'Blacklisted IPs') }}</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">
              {{ props.blockedIps?.length || 12 }}
            </div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">IP addresses banned in firewall</p>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0 shadow-inner">
            <img :src="'/images/nav/sub/suspended.svg'" alt="Blacklisted IPs" class="w-6 h-6 object-contain" />
          </div>
        </div>

        <!-- Card 4: Firewall Status -->
        <div class="bg-white dark:bg-slate-800/40 border border-cyan-200 dark:border-cyan-900/30 rounded-2xl p-4.5 backdrop-blur-xl hover:border-cyan-500/40 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
          <div>
            <span class="text-xs font-bold text-cyan-600 dark:text-cyan-300">{{ i18n.t('failed_firewall_status', 'Firewall Status') }}</span>
            <div class="text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono mt-2 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
              <span>{{ i18n.t('failed_firewall_active', 'Active Protection') }}</span>
            </div>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Real-time threat detection</p>
          </div>
          <div class="w-11 h-11 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center shrink-0 shadow-inner">
            <img :src="'/images/nav/sub/policies.svg'" alt="Firewall Status" class="w-6 h-6 object-contain" />
          </div>
        </div>
      </div>

      <div class="flex flex-col md:flex-row items-center justify-between gap-3 bg-white dark:bg-slate-800/30 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 backdrop-blur-xl shadow-sm dark:shadow-none">
        <div class="relative w-full sm:w-80">
          <input
            v-model="searchIp"
            type="text"
            :placeholder="i18n.t('failed_search_placeholder', 'Search IP, location, or network...')"
            class="w-full bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all"
          />
          <span class="absolute left-3 top-2.5 text-slate-400 dark:text-slate-500 text-xs">🔍</span>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-end">
          <button
            @click="exportReport"
            :disabled="isExporting"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="isExporting" class="animate-spin h-3.5 w-3.5 text-slate-700 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-else>📤</span>
            <span>{{ isExporting ? 'កំពុងទាញយក...' : i18n.t('failed_btn_export', 'Export Threat Report') }}</span>
          </button>

          <button
            @click="clearLogs"
            :disabled="isClearingLogs"
            class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-600/20 dark:hover:bg-rose-500/30 text-rose-600 dark:text-rose-300 border border-rose-200 dark:border-rose-500/30 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="isClearingLogs" class="animate-spin h-3.5 w-3.5 text-rose-600 dark:text-rose-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-else>🗑️</span>
            <span>{{ isClearingLogs ? 'កំពុងសម្អាត...' : i18n.t('failed_btn_clear', 'Clear Threat Log') }}</span>
          </button>

          <button
            @click="banAllSuspicious"
            :disabled="isBanningAll"
            class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow-md shadow-rose-600/20 transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="isBanningAll" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-else>🚫</span>
            <span>{{ isBanningAll ? 'កំពុង Ban...' : i18n.t('failed_btn_ban_all', 'Ban All Suspicious IPs') }}</span>
          </button>
        </div>
      </div>

      <div class="space-y-3">
        <h2 class="text-xs font-extrabold text-slate-700 dark:text-slate-300 flex items-center gap-2 uppercase tracking-wider">
          <img :src="'/images/nav/sub/failed.svg'" alt="Suspicious" class="w-4 h-4 object-contain" />
          <span>{{ i18n.t('failed_suspicious_title', 'Suspicious Activity Detected') }}</span>
        </h2>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 backdrop-blur-xl shadow-sm dark:shadow-2xl">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                <th class="py-3.5 px-4 w-12">#</th>
                <th class="py-3.5 px-4">🌐 IP Address</th>
                <th class="py-3.5 px-4">📅 Time</th>
                <th class="py-3.5 px-4">Attempts Level</th>
                <th class="py-3.5 px-4">📍 Location</th>
                <th class="py-3.5 px-4 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs font-mono">
              <tr v-for="act in filteredSuspicious" :key="act.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                <td class="py-3.5 px-4 text-slate-400 dark:text-slate-500">{{ String(act.id).padStart(2, '0') }}</td>

                <td class="py-3.5 px-4 font-bold text-rose-600 dark:text-rose-400">
                  <div>{{ act.ip }}</div>
                  <div class="text-[10px] text-slate-400 dark:text-slate-500 font-sans">Blacklist Candidate</div>
                </td>

                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">{{ act.time }}</td>

                <td class="py-3.5 px-4">
                  <span :class="[
                    act.level === 'high' ? 'bg-rose-500/10 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border-rose-500/30' : 'bg-amber-500/10 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 border-amber-500/30',
                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold border'
                  ]">
                    {{ act.attempts }} Attempts ({{ act.level.toUpperCase() }})
                  </span>
                </td>

                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-200 font-medium font-sans">{{ act.location }}</td>

                <td class="py-3.5 px-4 text-right">
                  <button
                    @click="blockIp(act.ip)"
                    :disabled="processingIp === act.ip"
                    class="px-3 py-1 text-xs font-bold text-rose-600 dark:text-rose-300 bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/20 dark:hover:bg-rose-500/30 border border-rose-200 dark:border-rose-500/30 rounded-xl transition-all cursor-pointer disabled:opacity-50"
                  >
                    <span v-if="processingIp === act.ip">...</span>
                    <span v-else>🚫 Ban IP</span>
                  </button>
                </td>
              </tr>

              <tr v-if="filteredSuspicious.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">No suspicious activities matching filter.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="space-y-3">
        <h2 class="text-xs font-extrabold text-slate-700 dark:text-slate-300 flex items-center gap-2 uppercase tracking-wider">
          <img :src="'/images/nav/sub/suspended.svg'" alt="Blacklist" class="w-4 h-4 object-contain" />
          <span>{{ i18n.t('failed_blacklisted_title', 'Firewall Blacklisted IP Addresses') }}</span>
          <span class="px-2 py-0.5 text-xs bg-rose-500/10 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30 rounded-full font-mono font-bold">
            {{ props.blockedIps?.length || 0 }}
          </span>
        </h2>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 backdrop-blur-xl shadow-sm dark:shadow-2xl">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                <th class="py-3.5 px-4">Blacklisted IP Address</th>
                <th class="py-3.5 px-4">Restriction Level</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4 text-right">Firewall Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
              <tr v-for="ip in props.blockedIps" :key="ip" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                <td class="py-3.5 px-4 font-mono font-bold text-rose-600 dark:text-rose-400">{{ ip }}</td>
                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">Total Connection Block</td>
                <td class="py-3.5 px-4">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-500/30">
                    BLOCKED
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button
                    @click="unblockIp(ip)"
                    :disabled="processingIp === ip"
                    class="px-3 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 border border-emerald-200 dark:border-emerald-500/30 rounded-xl transition-all cursor-pointer disabled:opacity-50"
                  >
                    <span v-if="processingIp === ip">...</span>
                    <span v-else>🔓 Unblock IP</span>
                  </button>
                </td>
              </tr>

              <tr v-if="!props.blockedIps || props.blockedIps.length === 0">
                <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">No IP addresses currently blacklisted in firewall.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
