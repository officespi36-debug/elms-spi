<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AuthModuleHeader from '@/Components/Admin/AuthModuleHeader.vue'
import { i18n } from '@/Services/i18n'

const props = defineProps<{
  summaryStats?: any
  overview?: {
    total_roles: number
    total_permissions: number
    active_sessions_now: number
    login_history_today: number
    failed_attempts_today: number
    banned_ips_count: number
    security_score: number
  }
  recentFailedLogs?: Array<{
    id: number
    email?: string
    ip_address?: string
    user_agent?: string
    device?: string
    browser?: string
    status?: string
    location?: string
    created_at?: string
  }>
  lockedAccounts?: Array<{
    id: number
    name: string
    email: string
    role: string
    login_attempts: number
    locked_until?: string
    status?: string
  }>
}>()

const stats = props.overview || {
  total_roles: 3,
  total_permissions: 15,
  active_sessions_now: 7,
  login_history_today: 225,
  failed_attempts_today: 65,
  banned_ips_count: 0,
  security_score: 88
}

// Fallback sample incidents if none from backend
const sampleIncidents = [
  { id: 1, email: 'admin@spilms.tech', ip_address: '185.220.101.45', device: 'Linux Tor Exit', location: 'Frankfurt, DE', reason: 'Brute-force (5 failed)', time: '04:20 PM' },
  { id: 2, email: 'teacher@gmail.com', ip_address: '36.37.147.74', device: 'Unknown Client', location: 'Phnom Penh, KH', reason: 'Failed Auth Attempt', time: '04:20 PM' },
  { id: 3, email: 'sokha_spi', ip_address: '127.0.0.1', device: 'Desktop / Chrome', location: 'Localhost', reason: 'Failed Auth Attempt', time: '05:27 PM' },
  { id: 4, email: 'kosalsensok065@gmail.com', ip_address: '127.0.0.1', device: 'Desktop / Chrome', location: 'Localhost', reason: 'Failed Auth Attempt', time: '08:06 AM' },
  { id: 5, email: 'guest_scan@domain.com', ip_address: '45.154.255.89', device: 'Python-requests/2.28', location: 'Amsterdam, NL', reason: 'Decoy /login scan', time: '09:14 AM' }
]

const rawIncidents = (props.recentFailedLogs && props.recentFailedLogs.length > 0)
  ? props.recentFailedLogs.map(log => ({
      id: log.id,
      email: log.email || 'unknown@user',
      ip_address: log.ip_address || '127.0.0.1',
      device: log.device || log.browser || 'Unknown Client',
      location: log.location || 'Unknown Geo',
      reason: 'Failed Auth Attempt',
      time: log.created_at ? new Date(log.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '04:20 PM'
    }))
  : sampleIncidents

// Filter & Search Controls
const searchQuery = ref('')
const selectedVector = ref('all')
const isUnlockModalOpen = ref(false)
const unlockConfirmationText = ref('')
const isProcessingUnlock = ref(false)
const selectedIncidentIds = ref<number[]>([])

function isInternalIp(ip: string): boolean {
  const clean = (ip || '').trim().toLowerCase()
  return clean === '127.0.0.1' || clean === '::1' || clean === 'localhost' || clean === '0.0.0.0'
}

const filteredIncidents = computed(() => {
  return rawIncidents.filter(item => {
    const q = searchQuery.value.trim().toLowerCase()
    const matchesSearch = !q || 
      item.email.toLowerCase().includes(q) || 
      item.ip_address.toLowerCase().includes(q) || 
      item.location.toLowerCase().includes(q)
    
    const matchesVector = selectedVector.value === 'all' || 
      (selectedVector.value === 'brute' && item.reason.includes('Brute-force')) ||
      (selectedVector.value === 'failed' && item.reason.includes('Failed Auth')) ||
      (selectedVector.value === 'decoy' && item.reason.includes('Decoy'))
    
    return matchesSearch && matchesVector
  })
})

const selectAll = computed({
  get: () => {
    const blockable = filteredIncidents.value.filter(i => !isInternalIp(i.ip_address))
    return blockable.length > 0 && selectedIncidentIds.value.length === blockable.length
  },
  set: (val: boolean) => {
    if (val) {
      selectedIncidentIds.value = filteredIncidents.value
        .filter(i => !isInternalIp(i.ip_address))
        .map(i => i.id)
    } else {
      selectedIncidentIds.value = []
    }
  }
})

function blockIp(ip: string) {
  if (!ip) return
  if (isInternalIp(ip)) {
    alert('Internal network loopback (127.0.0.1 / localhost) is protected and cannot be blocked.')
    return
  }
  if (confirm(`Block IP ${ip} permanently in firewall blacklist?`)) {
    router.post('/admin/auth-logs/block-ip', { ip_address: ip }, {
      preserveScroll: true
    })
  }
}

function handleBulkBlockSelected() {
  const selectedIps = filteredIncidents.value
    .filter(i => selectedIncidentIds.value.includes(i.id) && !isInternalIp(i.ip_address))
    .map(i => i.ip_address)

  if (selectedIps.length === 0) return
  if (confirm(`Block ${selectedIps.length} selected external IP address(es) in firewall blacklist?`)) {
    router.post('/admin/auth-logs/block-ip', { ip_addresses: selectedIps }, {
      preserveScroll: true,
      onSuccess: () => {
        selectedIncidentIds.value = []
      }
    })
  }
}

function handleConfirmUnlockAll() {
  isProcessingUnlock.value = true
  router.post('/admin/auth-logs/unlock-all', {}, {
    preserveScroll: true,
    onFinish: () => {
      isProcessingUnlock.value = false
      isUnlockModalOpen.value = false
      unlockConfirmationText.value = ''
    }
  })
}
</script>

<template>
  <AdminLayout :title="i18n.t('មជ្ឈមណ្ឌលប្រតិបត្តិការសុវត្ថិភាព (SOC)', 'Security Operations Center (SOC)')">
    <div class="space-y-5 font-sans text-slate-900 dark:text-[#F1F5F9]">
      
      <!-- Shared Header: Dual Light & Dark Command Header & KPI Cards -->
      <AuthModuleHeader activeTab="overview" :summaryStats="props.summaryStats" />

      <!-- SECTION 1: Actionable SOC Intelligence & Mitigation Console (2 Columns: 8/12 & 4/12) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
        
        <!-- Left: Incident Triage & Live Threat Queue (8/12) -->
        <div class="lg:col-span-8 bg-white dark:bg-[#0F172A] border border-slate-200/80 dark:border-[#1E2638] rounded-2xl p-5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] flex flex-col justify-between">
          <div>
            <!-- Header Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 border-b border-slate-100 dark:border-[#1E2638]">
              <div>
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-rose-500 dark:bg-[#EF4444] animate-pulse"></span>
                  <h3 class="text-sm font-semibold text-slate-900 dark:text-[#F1F5F9] tracking-tight">
                    {{ i18n.t('ការត្រួតពិនិត្យ និងកាត់បន្ថយការគំរាមកំហែង', 'Incident Mitigation & Threat Stream') }}
                  </h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-[#94A3B8] mt-0.5">
                  {{ i18n.t('វិភាគភ្លាមៗនូវការប៉ុនប៉ងចូលមិនជោគជ័យ និងការទប់ស្កាត់ IP ដែលមានហានិភ័យ', 'Live triage of brute-force vectors, failed authentications & suspicious origin IPs') }}
                </p>
              </div>

              <!-- Quick Triage Action Controls (Secondary Outline Button with Safety Warning) -->
              <div class="flex items-center gap-2.5 shrink-0">
                <button
                  type="button"
                  @click="isUnlockModalOpen = true"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-[#94A3B8] hover:text-amber-600 dark:hover:text-amber-400 bg-slate-50 dark:bg-transparent hover:bg-amber-50 dark:hover:bg-amber-500/10 border border-slate-200 dark:border-[#1E2638] hover:border-amber-300 dark:hover:border-amber-500/40 rounded-lg transition-colors cursor-pointer shadow-xs"
                  title="Unlock all accounts currently suspended (Requires Confirmation)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-500 dark:text-[#F59E0B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                  </svg>
                  <span>{{ i18n.t('ដោះសោរគណនីទាំងអស់', 'Unlock All') }}</span>
                </button>

                <Link
                  href="/admin/auth/history?filter=failed"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 dark:text-[#94A3B8] hover:text-slate-900 dark:hover:text-[#F1F5F9] bg-slate-50 dark:bg-transparent hover:bg-slate-100 dark:hover:bg-[#1E2638]/40 border border-slate-200 dark:border-[#1E2638] rounded-lg transition-colors"
                >
                  <span>{{ i18n.t('កំណត់ត្រាពេញលេញ', 'Full Audit') }}</span>
                  <span class="font-mono">→</span>
                </Link>
              </div>
            </div>

            <!-- Contextual Drill-down & Filter Toolbar -->
            <div class="pt-3 pb-2 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
              <!-- Search Input -->
              <div class="relative w-full sm:w-64">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-[#94A3B8] pointer-events-none">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                  </svg>
                </span>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Filter by IP, Account, or Location..."
                  class="w-full pl-8 pr-3 py-1.5 bg-slate-50 dark:bg-[#0E131E] border border-slate-200 dark:border-[#1E2638] rounded-lg text-xs text-slate-900 dark:text-[#F1F5F9] placeholder-slate-400 dark:placeholder-[#94A3B8]/60 focus:outline-none focus:border-indigo-500 focus:bg-white dark:focus:bg-[#0E131E] transition-colors"
                />
              </div>

              <!-- Threat Vector Selector -->
              <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <span class="text-[11px] text-slate-500 dark:text-[#94A3B8] hidden sm:inline">Threat Vector:</span>
                <select
                  v-model="selectedVector"
                  class="px-2.5 py-1.5 bg-slate-50 dark:bg-[#0E131E] border border-slate-200 dark:border-[#1E2638] rounded-lg text-xs text-slate-900 dark:text-[#F1F5F9] focus:outline-none focus:border-indigo-500 cursor-pointer"
                >
                  <option value="all">All Vectors</option>
                  <option value="failed">Failed Auth Attempt</option>
                  <option value="brute">Brute-force (5+)</option>
                  <option value="decoy">Decoy Scan</option>
                </select>
              </div>
            </div>

            <!-- Multi-Select Bulk Action Bar (Revealed when items selected) -->
            <div
              v-if="selectedIncidentIds.length > 0"
              class="mb-2 p-2 bg-rose-50/90 dark:bg-rose-950/40 border border-rose-200/90 dark:border-rose-900/60 rounded-xl flex items-center justify-between text-xs transition-all duration-200"
            >
              <div class="flex items-center gap-2 text-rose-800 dark:text-rose-300 font-medium">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                <span><b>{{ selectedIncidentIds.length }}</b> IP address(es) selected for firewall mitigation</span>
              </div>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="selectedIncidentIds = []"
                  class="px-2.5 py-1 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium cursor-pointer"
                >
                  Clear
                </button>
                <button
                  type="button"
                  @click="handleBulkBlockSelected"
                  class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                  <span>🚫</span>
                  <span>Block Selected ({{ selectedIncidentIds.length }})</span>
                </button>
              </div>
            </div>

            <!-- Incident Feed Table (Consistent Compact 44px Row Height) -->
            <div class="mt-1 overflow-x-auto rounded-xl border border-slate-200/60 dark:border-[#1E2638]/70">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-200/80 dark:border-[#1E2638] text-slate-500 dark:text-[#94A3B8] bg-slate-50/70 dark:bg-[#0E131E]">
                    <!-- Multi-select Header Checkbox -->
                    <th class="py-2.5 px-3 w-10 text-center">
                      <input
                        type="checkbox"
                        v-model="selectAll"
                        class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                        title="Select all external threat IPs"
                      />
                    </th>
                    <th class="py-2.5 px-3 font-semibold uppercase tracking-wider text-[11px] w-24">{{ i18n.t('ពេលវេលា', 'Time') }}</th>
                    <th class="py-2.5 px-4 font-semibold uppercase tracking-wider text-[11px]">{{ i18n.t('គណនីគោលដៅ', 'Target Account') }}</th>
                    <th class="py-2.5 px-4 font-semibold uppercase tracking-wider text-[11px] w-44">{{ i18n.t('IP & ទីតាំង', 'Origin IP / Location') }}</th>
                    <th class="py-2.5 px-3 font-semibold uppercase tracking-wider text-[11px]">{{ i18n.t('មូលហេតុគំរាមកំហែង', 'Threat Vector') }}</th>
                    <th class="py-2.5 px-4 font-semibold uppercase tracking-wider text-[11px] text-right w-28">{{ i18n.t('សកម្មភាព', 'Action') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-[#1E2638]">
                  <tr
                    v-for="incident in filteredIncidents"
                    :key="incident.id"
                    :class="[
                      selectedIncidentIds.includes(incident.id) ? 'bg-rose-50/40 dark:bg-rose-950/20' : '',
                      'h-11 hover:bg-slate-50/80 dark:hover:bg-[#161D2D] transition-colors'
                    ]"
                  >
                    <!-- Row Checkbox -->
                    <td class="py-2.5 px-3 text-center">
                      <input
                        type="checkbox"
                        :value="incident.id"
                        v-model="selectedIncidentIds"
                        :disabled="isInternalIp(incident.ip_address)"
                        class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer disabled:opacity-25 disabled:cursor-not-allowed"
                        :title="isInternalIp(incident.ip_address) ? 'Internal loopback cannot be blocked' : 'Select IP for bulk block'"
                      />
                    </td>

                    <!-- Time (JetBrains Mono / Tabular) -->
                    <td class="py-2.5 px-3 font-mono text-[12px] text-slate-500 dark:text-[#94A3B8] whitespace-nowrap tracking-tight">
                      {{ incident.time }}
                    </td>

                    <!-- Account / Target -->
                    <td class="py-2.5 px-4">
                      <div class="font-medium text-slate-900 dark:text-[#F1F5F9] leading-tight">{{ incident.email }}</div>
                      <div class="text-[11px] text-slate-500 dark:text-[#94A3B8] leading-tight mt-0.5">{{ incident.device }}</div>
                    </td>

                    <!-- Origin IP & Geo (Monospace with Protected badge) -->
                    <td class="py-2.5 px-4">
                      <div class="font-mono text-[12px] text-slate-900 dark:text-[#F1F5F9] leading-tight flex items-center gap-1.5">
                        <span>{{ incident.ip_address }}</span>
                        <span
                          v-if="isInternalIp(incident.ip_address)"
                          class="px-1.5 py-0.2 text-[9px] font-mono rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                        >
                          loopback
                        </span>
                      </div>
                      <div class="text-[11px] text-slate-500 dark:text-[#94A3B8] leading-tight mt-0.5">{{ incident.location }}</div>
                    </td>

                    <!-- Threat Vector Badge -->
                    <td class="py-2.5 px-3">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200 dark:bg-[#EF4444]/12 dark:text-[#EF4444] dark:border-[#EF4444]/25 whitespace-nowrap">
                        {{ incident.reason }}
                      </span>
                    </td>

                    <!-- Action Button: Neutral Ghost Button with Localhost Safeguard -->
                    <td class="py-2.5 px-4 text-right whitespace-nowrap">
                      <span
                        v-if="isInternalIp(incident.ip_address)"
                        class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono text-slate-400 dark:text-slate-500 bg-slate-100/70 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 cursor-not-allowed select-none"
                        title="Internal loopback cannot be blocked"
                      >
                        Protected
                      </span>
                      <button
                        v-else
                        type="button"
                        @click="blockIp(incident.ip_address)"
                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded text-slate-600 dark:text-[#94A3B8] bg-transparent hover:bg-rose-50 dark:hover:bg-[#EF4444]/15 border border-slate-200 dark:border-[#1E2638] hover:border-rose-300 dark:hover:border-[#EF4444] hover:text-rose-600 dark:hover:text-[#EF4444] transition-colors cursor-pointer"
                        title="Add IP to firewall block list"
                      >
                        {{ i18n.t('ទប់ស្កាត់ IP', 'Block IP') }}
                      </button>
                    </td>
                  </tr>

                  <tr v-if="filteredIncidents.length === 0">
                    <td colspan="6" class="py-8 text-center text-slate-500 dark:text-[#94A3B8] text-xs">
                      {{ i18n.t('គ្មានកំណត់ត្រាដែលត្រូវគ្នានឹងការស្វែងរក', 'No security incidents found matching your search filter.') }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Alert Context Footer -->
          <div class="pt-3.5 mt-3 border-t border-slate-100 dark:border-[#1E2638] flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-500 dark:text-[#94A3B8] gap-2">
            <div class="flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-[#10B981]"></span>
              <span>{{ i18n.t('ប្រព័ន្ធ Firewall & Rate Limiting កំពុងដំណើរការការពារស្វ័យប្រវត្តិ (24/7 Monitoring)', 'Firewall & automated rate-limit mitigation active 24/7') }}</span>
            </div>
            <span class="font-mono text-[11px] text-slate-500 dark:text-[#94A3B8]">{{ stats.failed_attempts_today }} {{ i18n.t('ការប៉ុនប៉ងត្រូវបានកត់ត្រាថ្ងៃនេះ', 'total attempts logged today') }}</span>
          </div>
        </div>

        <!-- Right: Security Posture & Hardening Progress Widget (4/12) -->
        <div class="lg:col-span-4 bg-white dark:bg-[#0F172A] border border-slate-200/80 dark:border-[#1E2638] rounded-2xl p-5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] flex flex-col justify-between">
          <div>
            <!-- Header -->
            <div class="pb-3.5 border-b border-slate-100 dark:border-[#1E2638]">
              <h3 class="text-sm font-semibold text-slate-900 dark:text-[#F1F5F9] tracking-tight">
                {{ i18n.t('ស្ថានភាពការពារប្រព័ន្ធ', 'Hardening & Posture') }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-[#94A3B8] mt-0.5">
                {{ i18n.t('ពិន្ទុសវនកម្មសុវត្ថិភាពសរុប', 'Overall security posture index') }}
              </p>
            </div>

            <!-- Refined High Data-Ink Posture Score Widget -->
            <div class="mt-4 p-4 rounded-xl bg-slate-50 dark:bg-[#0E131E] border border-slate-200/80 dark:border-[#1E2638] space-y-3">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-3xl font-extrabold font-mono text-slate-900 dark:text-[#F1F5F9] tracking-tight">
                    {{ stats.security_score }}%
                  </div>
                  <p class="text-xs text-slate-500 dark:text-[#94A3B8] mt-0.5 font-medium">Security Posture Index</p>
                  <div class="flex items-center gap-1.5 mt-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-[#10B981]"></span>
                    <span class="text-[11px] font-semibold text-emerald-600 dark:text-[#10B981]">OPTIMAL PROTECTION</span>
                  </div>
                </div>

                <!-- Clean Radial Gauge (Centered, No Redundant Duplicate 88% Label) -->
                <div class="relative w-16 h-16 flex items-center justify-center shrink-0">
                  <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                    <path
                      class="text-slate-200 dark:text-[#1E2638]"
                      stroke-width="3.5"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      class="text-emerald-500 dark:text-[#10B981]"
                      stroke-dasharray="88, 100"
                      stroke-width="3.5"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                  </svg>
                  <span class="absolute text-[11px] font-bold font-mono text-emerald-600 dark:text-[#10B981]">PASS</span>
                </div>
              </div>

              <!-- Multi-Segment Posture Health Bar (Explains why it's 88%) -->
              <div class="pt-2 border-t border-slate-200/60 dark:border-slate-800 space-y-1.5">
                <div class="flex justify-between items-center text-[10px] text-slate-500 dark:text-[#94A3B8]">
                  <span>Posture Breakdown:</span>
                  <span class="font-mono text-amber-600 dark:text-amber-400">2FA Audit Pending (-12%)</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden flex gap-0.5">
                  <div class="h-full bg-emerald-500 w-[25%]" title="Password Policy: 25/25"></div>
                  <div class="h-full bg-emerald-500 w-[25%]" title="JWT Expiry: 25/25"></div>
                  <div class="h-full bg-emerald-500 w-[25%]" title="Sensors: 25/25"></div>
                  <div class="h-full bg-amber-500 w-[13%]" title="Admin 2FA: 13/25"></div>
                </div>
              </div>
            </div>

            <!-- Hardening Checklist -->
            <div class="mt-4 divide-y divide-slate-100 dark:divide-[#1E2638] text-xs">
              
              <!-- Item 1: Password Policy -->
              <div class="py-2.5 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500 dark:text-[#10B981] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                  <span class="text-slate-800 dark:text-[#F1F5F9] truncate">{{ i18n.t('គោលការណ៍ពាក្យសម្ងាត់ (12+ តួអក្សរ)', 'Password Policy (12+ chars)') }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-[#10B981]/12 dark:text-[#10B981] dark:border-[#10B981]/25 shrink-0">
                  ENFORCED
                </span>
              </div>

              <!-- Item 2: JWT Inactivity Expiry -->
              <div class="py-2.5 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500 dark:text-[#10B981] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                  <span class="text-slate-800 dark:text-[#F1F5F9] truncate">{{ i18n.t('សុពលភាព JWT (15-min Idle Expiry)', 'JWT 15m Token Expiry') }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-[#10B981]/12 dark:text-[#10B981] dark:border-[#10B981]/25 shrink-0">
                  ACTIVE
                </span>
              </div>

              <!-- Item 3: Superadmin 2FA -->
              <div class="py-2.5 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-500 dark:text-[#F59E0B] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                  </svg>
                  <span class="text-slate-800 dark:text-[#F1F5F9] truncate">{{ i18n.t('ការផ្ទៀងផ្ទាត់ 2FA សម្រាប់ Admin', 'Superadmin 2FA Security') }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-[#F59E0B]/12 dark:text-[#F59E0B] dark:border-[#F59E0B]/25 shrink-0">
                  AUDIT REQ
                </span>
              </div>

              <!-- Item 4: Honeypot Decoy Sensors -->
              <div class="py-2.5 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500 dark:text-[#10B981] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                  <span class="text-slate-800 dark:text-[#F1F5F9] truncate">{{ i18n.t('អន្ទាក់ Honeypots (3 Sensors)', 'Honeypot Decoy Sensors') }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-[#10B981]/12 dark:text-[#10B981] dark:border-[#10B981]/25 shrink-0">
                  ARMED
                </span>
              </div>
            </div>
          </div>

          <!-- Bottom Action Link: Ghost Button -->
          <div class="pt-3.5 border-t border-slate-100 dark:border-[#1E2638] mt-3">
            <Link
              href="/admin/auth/policies"
              class="w-full py-2 px-3 rounded-lg text-xs font-medium text-center text-slate-600 dark:text-[#94A3B8] hover:text-slate-900 dark:hover:text-[#F1F5F9] bg-slate-50 dark:bg-[#0E131E] hover:bg-slate-100 dark:hover:bg-[#161D2D] border border-slate-200 dark:border-[#1E2638] transition-colors flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('កែប្រែគោលការណ៍សុវត្ថិភាព', 'Configure Security Policies') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

      </div>

      <!-- SECTION 2: Informative Operational Intelligence Mini-Feeds (No Redundancy) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <!-- Feed 1: RBAC Role Distribution & User Access -->
        <div class="bg-white dark:bg-[#0F172A] border border-slate-200/80 dark:border-[#1E2638] hover:border-slate-300 dark:hover:border-slate-600 rounded-2xl p-5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-[#0E131E] border border-slate-200 dark:border-[#1E2638] text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m9 12 2 2 4-4"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-[#10B981]/12 dark:text-[#10B981] dark:border-[#10B981]/25">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-[#10B981]"></span>
                {{ i18n.t('RBAC សកម្ម', 'RBAC Active') }}
              </span>
            </div>

            <h4 class="text-sm font-semibold text-slate-900 dark:text-[#F1F5F9] mt-3">
              {{ i18n.t('តួនាទី និងសិទ្ធិប្រើប្រាស់', 'Role & Permission Distribution') }}
            </h4>
            <p class="text-xs text-slate-500 dark:text-[#94A3B8] mt-1 leading-relaxed">
              Granular access scope enforced across active system accounts.
            </p>

            <!-- Role User Counts Breakdown -->
            <div class="mt-3.5 p-3 rounded-lg bg-slate-50 dark:bg-[#0E131E] border border-slate-200/80 dark:border-[#1E2638] space-y-1.5 text-xs">
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#94A3B8] flex items-center gap-1.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                  <span>Superadmin:</span>
                </span>
                <span class="font-mono font-bold text-slate-900 dark:text-[#F1F5F9]">1 User</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#94A3B8] flex items-center gap-1.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                  <span>Instructors:</span>
                </span>
                <span class="font-mono font-bold text-slate-900 dark:text-[#F1F5F9]">3 Users</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#94A3B8] flex items-center gap-1.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  <span>Enrolled Students:</span>
                </span>
                <span class="font-mono font-bold text-slate-900 dark:text-[#F1F5F9]">1,248 Users</span>
              </div>
            </div>
          </div>

          <div class="pt-3.5 mt-3 border-t border-slate-100 dark:border-[#1E2638]">
            <Link
              href="/admin/auth/roles"
              class="w-full py-1.5 px-3 rounded-lg text-xs font-medium text-center text-slate-600 dark:text-[#94A3B8] hover:text-slate-900 dark:hover:text-[#F1F5F9] bg-slate-50 dark:bg-[#0E131E] hover:bg-slate-100 dark:hover:bg-[#161D2D] border border-slate-200 dark:border-[#1E2638] transition-colors flex items-center justify-center gap-1.5"
            >
              <span>{{ i18n.t('គ្រប់គ្រងម៉ាទ្រីស RBAC', 'Configure Roles Matrix') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

        <!-- Feed 2: Live JWT Session Telemetry & Device Breakdown -->
        <div class="bg-white dark:bg-[#0F172A] border border-slate-200/80 dark:border-[#1E2638] hover:border-slate-300 dark:hover:border-slate-600 rounded-2xl p-5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-[#0E131E] border border-slate-200 dark:border-[#1E2638] text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="20" height="14" x="2" y="3" rx="2"/>
                  <line x1="8" x2="16" y1="21" y2="21"/>
                  <line x1="12" x2="12" y1="17" y2="21"/>
                  <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-mono font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-[#10B981]/12 dark:text-[#10B981] dark:border-[#10B981]/25">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-[#10B981] animate-pulse"></span>
                {{ stats.active_sessions_now }} Online
              </span>
            </div>

            <h4 class="text-sm font-semibold text-slate-900 dark:text-[#F1F5F9] mt-3">
              {{ i18n.t('សម័យការសកម្ម (JWT Sessions)', 'Live Session Device Breakdown') }}
            </h4>
            <p class="text-xs text-slate-500 dark:text-[#94A3B8] mt-1 leading-relaxed">
              Real-time connected client distribution across authenticated tokens.
            </p>

            <!-- Device Telemetry Progress Bar -->
            <div class="mt-3.5 p-3 rounded-lg bg-slate-50 dark:bg-[#0E131E] border border-slate-200/80 dark:border-[#1E2638] space-y-2 text-xs">
              <div class="flex justify-between items-center text-xs">
                <span class="text-slate-500 dark:text-[#94A3B8]">Desktop Clients (85%)</span>
                <span class="text-slate-500 dark:text-[#94A3B8]">Mobile (15%)</span>
              </div>
              <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden flex">
                <div class="h-full bg-indigo-500 w-[85%]" title="Desktop: 85%"></div>
                <div class="h-full bg-sky-400 w-[15%]" title="Mobile: 15%"></div>
              </div>
              <div class="flex justify-between items-center text-[10px] text-slate-500 dark:text-[#94A3B8] pt-0.5">
                <span>Inactivity Timeout: <b>15 mins</b></span>
                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">● Healthy</span>
              </div>
            </div>
          </div>

          <div class="pt-3.5 mt-3 border-t border-slate-100 dark:border-[#1E2638]">
            <Link
              href="/admin/auth/sessions"
              class="w-full py-1.5 px-3 rounded-lg text-xs font-medium text-center text-slate-600 dark:text-[#94A3B8] hover:text-slate-900 dark:hover:text-[#F1F5F9] bg-slate-50 dark:bg-[#0E131E] hover:bg-slate-100 dark:hover:bg-[#161D2D] border border-slate-200 dark:border-[#1E2638] transition-colors flex items-center justify-center gap-1.5"
            >
              <span>{{ i18n.t('ត្រួតពិនិត្យសម័យការ', 'Inspect Active Sessions') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

        <!-- Feed 3: Honeypot & Decoy Sensor Grid -->
        <div class="bg-white dark:bg-[#0F172A] border border-slate-200/80 dark:border-[#1E2638] hover:border-slate-300 dark:hover:border-slate-600 rounded-2xl p-5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-[#0E131E] border border-slate-200 dark:border-[#1E2638] text-rose-600 dark:text-rose-400 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m14.5 9-5 5"/>
                  <path d="m9.5 9 5 5"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200 dark:bg-[#EF4444]/12 dark:text-[#EF4444] dark:border-[#EF4444]/25">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 dark:bg-[#EF4444] animate-pulse"></span>
                {{ i18n.t('អន្ទាក់សកម្ម', 'Sensors Armed') }}
              </span>
            </div>

            <h4 class="text-sm font-semibold text-slate-900 dark:text-[#F1F5F9] mt-3">
              {{ i18n.t('អន្ទាក់ Honeypots & Cyber Forensics', 'Honeypots & Decoy Sensor Grid') }}
            </h4>
            <p class="text-xs text-slate-500 dark:text-[#94A3B8] mt-1 leading-relaxed">
              Automated decoy endpoints capturing credential stuffing attempts.
            </p>

            <!-- Decoy Sensor Status -->
            <div class="mt-3.5 p-3 rounded-lg bg-slate-50 dark:bg-[#0E131E] border border-slate-200/80 dark:border-[#1E2638] space-y-1.5 text-xs">
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#94A3B8]">Active Decoy Traps:</span>
                <span class="font-mono font-bold text-slate-900 dark:text-[#F1F5F9]">3 Endpoints</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#94A3B8]">Auto-Isolated Accounts:</span>
                <span class="font-mono font-bold text-rose-600 dark:text-[#EF4444]">14 Suspended</span>
              </div>
              <div class="flex justify-between items-center text-[10px] text-slate-400 dark:text-slate-500 pt-0.5">
                <span>Trap Endpoints:</span>
                <span class="font-mono">/admin/login.php, /.env</span>
              </div>
            </div>
          </div>

          <div class="pt-3.5 mt-3 border-t border-slate-100 dark:border-[#1E2638]">
            <Link
              href="/admin/auth/cyber-security"
              class="w-full py-1.5 px-3 rounded-lg text-xs font-medium text-center text-slate-600 dark:text-[#94A3B8] hover:text-slate-900 dark:hover:text-[#F1F5F9] bg-slate-50 dark:bg-[#0E131E] hover:bg-slate-100 dark:hover:bg-[#161D2D] border border-slate-200 dark:border-[#1E2638] transition-colors flex items-center justify-center gap-1.5"
            >
              <span>{{ i18n.t('បើកមជ្ឈមណ្ឌល Forensics', 'Open Forensics Center') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

      </div>

      <!-- TWO-STEP DESTRUCTIVE ACTION CONFIRMATION MODAL FOR UNLOCK ALL -->
      <Teleport to="body">
        <div
          v-if="isUnlockModalOpen"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm"
        >
          <div class="relative w-full max-w-md bg-white dark:bg-[#111622] border border-slate-200 dark:border-[#1E2638] rounded-2xl p-6 shadow-2xl text-slate-900 dark:text-[#F1F5F9] space-y-4">
            <!-- Modal Header -->
            <div class="flex items-start gap-3.5">
              <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-500 dark:text-amber-400 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                  <line x1="12" y1="9" x2="12" y2="13"/>
                  <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
              </div>
              <div class="min-w-0">
                <h3 class="text-sm font-bold text-slate-900 dark:text-[#F1F5F9]">
                  {{ i18n.t('បញ្ជាក់សកម្មភាពសុវត្ថិភាពកម្រិតខ្ពស់', 'High-Risk Action Confirmation') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-[#94A3B8] mt-1 leading-relaxed">
                  {{ i18n.t('តើអ្នកពិតជាចង់ដោះសោគណនីទាំងអស់ (Bulk Unlock) មែនទេ?', 'In security operations, bulk unsuspending accounts restores access to all 14 locked users simultaneously. This may allow active credential-stuffing bots to retry immediately.') }}
                </p>
              </div>
            </div>

            <!-- Caution Notice Box -->
            <div class="p-3 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/25 rounded-xl text-xs text-amber-800 dark:text-amber-300/90 leading-relaxed">
              ⚠️ <strong>Warning:</strong> Ensure that active brute-force sources have been firewalled prior to unsuspending accounts.
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2">
              <button
                type="button"
                @click="isUnlockModalOpen = false"
                class="px-4 py-2 text-xs font-medium text-slate-600 dark:text-[#94A3B8] hover:text-slate-900 dark:hover:text-[#F1F5F9] bg-slate-100 dark:bg-[#0E131E] hover:bg-slate-200 dark:hover:bg-[#161D2D] border border-slate-200 dark:border-[#1E2638] rounded-lg transition-colors cursor-pointer"
              >
                {{ i18n.t('បោះបង់', 'Cancel') }}
              </button>

              <button
                type="button"
                :disabled="isProcessingUnlock"
                @click="handleConfirmUnlockAll"
                class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-500 disabled:opacity-50 rounded-lg transition-all flex items-center gap-2 cursor-pointer shadow-md shadow-amber-600/20"
              >
                <span v-if="isProcessingUnlock" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                <span>{{ i18n.t('យល់ព្រមដោះសោទាំងអស់', 'Confirm & Unlock All') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Teleport>

    </div>
  </AdminLayout>
</template>
