<script setup lang="ts">
import { ref, computed, defineAsyncComponent } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AuthModuleHeader from '@/Components/Admin/AuthModuleHeader.vue'
import { i18n } from '@/Services/i18n'
import { isDark } from '@/composables/useTheme'

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'))

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
  active_sessions_now: 6,
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
const unlockConfirmationInput = ref('')
const isUnlockConfirmed = computed(() => unlockConfirmationInput.value.trim().toUpperCase() === 'CONFIRM')
const isProcessingUnlock = ref(false)
const selectedIncidentIds = ref<number[]>([])

// Timeframe toggle state for Row 2 Chart
const chartTimeframe = ref<'daily' | 'weekly' | 'monthly'>('monthly')

function isInternalIp(ip: string): boolean {
  const clean = (ip || '').trim().toLowerCase()
  return clean === '127.0.0.1' || clean === '::1' || clean === 'localhost' || clean === '0.0.0.0'
}

function getIpGeo(ip: string, loc?: string): { flag: string; label: string } {
  if (isInternalIp(ip)) return { flag: '🌐', label: 'Localhost (Loopback)' }
  if (loc && loc !== 'Unknown Geo' && loc !== 'Unknown') {
    if (loc.includes('KH') || loc.toLowerCase().includes('cambodia') || loc.toLowerCase().includes('phnom penh')) return { flag: '🇰🇭', label: loc }
    if (loc.includes('DE') || loc.toLowerCase().includes('germany') || loc.toLowerCase().includes('frankfurt')) return { flag: '🇩🇪', label: loc }
    if (loc.includes('NL') || loc.toLowerCase().includes('netherlands') || loc.toLowerCase().includes('amsterdam')) return { flag: '🇳🇱', label: loc }
    if (loc.includes('US') || loc.toLowerCase().includes('united states')) return { flag: '🇺🇸', label: loc }
    return { flag: '📍', label: loc }
  }
  if (ip.startsWith('36.') || ip.startsWith('119.') || ip.startsWith('118.') || ip.startsWith('203.')) {
    return { flag: '🇰🇭', label: 'Phnom Penh, KH' }
  }
  if (ip.startsWith('185.') || ip.startsWith('194.')) {
    return { flag: '🇩🇪', label: 'Frankfurt, DE' }
  }
  if (ip.startsWith('45.')) {
    return { flag: '🇳🇱', label: 'Amsterdam, NL' }
  }
  if (ip.startsWith('104.') || ip.startsWith('192.') || ip.startsWith('172.')) {
    return { flag: '🇺🇸', label: 'Ashburn, US' }
  }
  return { flag: '🌐', label: 'International IP' }
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

// ── ROW 2: Authentication & Threat Trends Dual Area Chart ──
const currentChartCategories = computed(() => {
  if (chartTimeframe.value === 'daily') {
    return ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00']
  }
  if (chartTimeframe.value === 'weekly') {
    return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  }
  return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']
})

const authChartSeries = computed(() => {
  if (chartTimeframe.value === 'daily') {
    return [
      {
        name: i18n.t('ការចូលប្រព័ន្ធជោគជ័យ (Successful Logins)', 'Successful Logins'),
        data: [120, 85, 340, 520, 480, 260, 110]
      },
      {
        name: i18n.t('ការទប់ស្កាត់ការគំរាមកំហែង (Mitigated Threats)', 'Mitigated Threats'),
        data: [15, 28, 12, 45, 65, 30, 18]
      }
    ]
  }
  if (chartTimeframe.value === 'weekly') {
    return [
      {
        name: i18n.t('ការចូលប្រព័ន្ធជោគជ័យ (Successful Logins)', 'Successful Logins'),
        data: [1150, 1340, 1580, 1720, 1640, 890, 780]
      },
      {
        name: i18n.t('ការទប់ស្កាត់ការគំរាមកំហែង (Mitigated Threats)', 'Mitigated Threats'),
        data: [110, 145, 95, 180, 220, 85, 65]
      }
    ]
  }
  return [
    {
      name: i18n.t('ការចូលប្រព័ន្ធជោគជ័យ (Successful Logins)', 'Successful Logins'),
      data: [3200, 4500, 5200, 6800, 7200, 8600, 9400]
    },
    {
      name: i18n.t('ការទប់ស្កាត់ការគំរាមកំហែង (Mitigated Threats)', 'Mitigated Threats'),
      data: [450, 620, 580, 790, 650, 820, 740]
    }
  ]
})

const authChartOptions = computed<any>(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    background: 'transparent',
    parentHeightOffset: 0,
    animations: { enabled: true, dynamicAnimation: { enabled: false } },
  },
  colors: ['#2563eb', '#10b981'],
  stroke: { curve: 'smooth', width: 2.5 },
  dataLabels: { enabled: false },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 }
  },
  xaxis: {
    categories: currentChartCategories.value,
    labels: {
      style: {
        colors: isDark.value ? '#94a3b8' : '#64748b',
        fontSize: '11px',
        fontWeight: 600
      },
    },
    axisBorder: { show: true, color: isDark.value ? '#334155' : '#e2e8f0' },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      formatter: (val: number) => `${Math.round(val)}`,
      style: {
        colors: isDark.value ? '#94a3b8' : '#64748b',
        fontSize: '11px',
        fontWeight: 600
      },
    },
  },
  grid: {
    borderColor: isDark.value ? '#334155' : '#f1f5f9',
    strokeDashArray: 3,
  },
  legend: { show: false },
  tooltip: { theme: isDark.value ? 'dark' : 'light' }
}))

// ── ROW 2: Threat Vectors & Origin Distribution Donut Chart ──
const threatVectors = [
  { id: 1, name: 'Brute-force (5+)', display_name: 'Brute-force (5+)', code: 'BRUTE', count: 35, pct: 35, color: '#f59e0b' },
  { id: 2, name: 'Credential Stuffing', display_name: 'Credential Stuffing', code: 'STUFF', count: 25, pct: 25, color: '#2563eb' },
  { id: 3, name: 'Honeypot Decoy Traps', display_name: 'Honeypot Decoy Traps', code: 'DECOY', count: 20, pct: 20, color: '#10b981' },
  { id: 4, name: 'Suspicious Geo IP', display_name: 'Suspicious Geo IP', code: 'GEO', count: 12, pct: 12, color: '#8b5cf6' },
  { id: 5, name: 'Token Replay / Expired', display_name: 'Token Replay / Expired', code: 'REPLAY', count: 8, pct: 8, color: '#ef4444' },
]

const threatDonutSeries = computed(() => threatVectors.map(v => v.pct))

const threatDonutOptions = computed<any>(() => ({
  chart: {
    type: 'donut',
    background: 'transparent',
    animations: { enabled: true, dynamicAnimation: { enabled: false } },
  },
  labels: threatVectors.map(v => v.display_name),
  colors: threatVectors.map(v => v.color),
  legend: { show: false },
  stroke: { colors: [isDark.value ? '#0f172a' : '#ffffff'], width: 3 },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '72%',
        labels: { show: false }
      }
    }
  },
  tooltip: { theme: isDark.value ? 'dark' : 'light' }
}))

function exportIncidentsCsv() {
  const headers = ['Time', 'Target Account', 'Device', 'IP Address', 'Location', 'Threat Vector']
  const rows = filteredIncidents.value.map(i => [
    `"${i.time}"`,
    `"${i.email}"`,
    `"${i.device}"`,
    `"${i.ip_address}"`,
    `"${getIpGeo(i.ip_address, i.location).label}"`,
    `"${i.reason}"`
  ])
  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n')
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `security_threat_incidents_${new Date().toISOString().slice(0, 10)}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

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
  if (!isUnlockConfirmed.value) return
  isProcessingUnlock.value = true
  router.post('/admin/auth-logs/unlock-all', {}, {
    preserveScroll: true,
    onFinish: () => {
      isProcessingUnlock.value = false
      isUnlockModalOpen.value = false
      unlockConfirmationInput.value = ''
    }
  })
}
</script>

<template>
  <AdminLayout :title="i18n.t('មជ្ឈមណ្ឌលប្រតិបត្តិការសុវត្ថិភាព (SOC)', 'Security Operations Center (SOC)')">
    <div class="space-y-5 font-sans text-slate-800 dark:text-slate-100 pb-12">
      
      <!-- Shared Header: Dual Light & Dark Command Header & 6 Top KPI Cards -->
      <AuthModuleHeader activeTab="overview" :summaryStats="props.summaryStats" />

      <!-- ── ROW 2: 3-COLUMN ANALYTICS SECTION (5/12, 4/12, 3/12 MATCHING DASHBOARD) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (5/12): Threat Mitigation & Authentication Trends Dual Area Chart -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                  {{ i18n.t('និន្នាការចូលប្រព័ន្ធ និងការទប់ស្កាត់ការគំរាមកំហែង', 'Authentication & Threat Trends') }}
                </h3>
                <div class="flex items-center gap-3 mt-1 text-xs">
                  <div class="flex items-center gap-1.5 font-semibold text-blue-600 dark:text-blue-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <span>{{ i18n.t('ការចូលប្រព័ន្ធជោគជ័យ', 'Successful Logins') }}</span>
                  </div>
                  <div class="flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                    <span>{{ i18n.t('ការទប់ស្កាត់ការគំរាមកំហែង', 'Threats Mitigated') }}</span>
                  </div>
                </div>
              </div>
              <select
                v-model="chartTimeframe"
                class="text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1 font-semibold text-slate-700 dark:text-slate-200 cursor-pointer focus:outline-none"
              >
                <option value="monthly">{{ i18n.t('ប្រចាំខែ', 'Monthly') }}</option>
                <option value="weekly">{{ i18n.t('ប្រចាំសប្តាហ៍', 'Weekly') }}</option>
                <option value="daily">{{ i18n.t('ប្រចាំថ្ងៃ', 'Daily') }}</option>
              </select>
            </div>

            <!-- ApexCharts Area -->
            <div class="h-64 w-full">
              <VueApexCharts
                :key="'auth-chart-' + chartTimeframe"
                type="area"
                height="100%"
                :options="authChartOptions"
                :series="authChartSeries"
              />
            </div>
          </div>
        </div>

        <!-- Col 2 (4/12): Threat Vectors & Attack Origins Donut Chart -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ i18n.t('ការបែងចែកប្រភពគំរាមកំហែង និងវ៉ិចទ័រ', 'Threat Vectors & Attack Origins') }}
              </h3>
            </div>

            <div class="flex items-center justify-between gap-3 mt-2">
              <!-- Center Donut Chart with Shield Icon -->
              <div class="relative w-36 h-36 shrink-0 flex items-center justify-center">
                <VueApexCharts
                  type="donut"
                  width="145"
                  height="145"
                  :options="threatDonutOptions"
                  :series="threatDonutSeries"
                />
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-800 dark:text-slate-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="m9 12 2 2 4-4"/>
                  </svg>
                </div>
              </div>

              <!-- Vertical Right Legend -->
              <div class="space-y-1.5 flex-1 min-w-0 text-xs">
                <div
                  v-for="(item, idx) in threatVectors"
                  :key="item.id"
                  class="flex items-center justify-between p-1 -mx-1 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group"
                >
                  <div class="flex items-center gap-2 min-w-0">
                    <span
                      class="w-2.5 h-2.5 rounded-full shrink-0"
                      :class="[
                        idx === 0 ? 'bg-amber-500' :
                        idx === 1 ? 'bg-blue-600' :
                        idx === 2 ? 'bg-emerald-500' :
                        idx === 3 ? 'bg-purple-500' : 'bg-rose-500'
                      ]"
                    ></span>
                    <div class="min-w-0">
                      <p class="font-bold text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 leading-tight whitespace-nowrap text-xs">
                        {{ item.display_name }}
                      </p>
                      <p class="text-[10px] text-slate-400 font-medium leading-none mt-0.5">{{ item.count }} incidents</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white shrink-0 ml-2 text-xs">{{ item.pct }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Col 3 (3/12): Security Posture & Compliance Status -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ i18n.t('ស្ថានភាពវឌ្ឍនភាពសុវត្ថិភាព', 'Security Posture Status') }}
              </h3>
            </div>

            <div class="space-y-1.5 mt-2 text-xs">
              <!-- Item 1: Password Policy -->
              <Link href="/admin/auth/policies" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12" />
                    </svg>
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Password Policy</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">Enforced</span>
                  <span class="ml-2 text-slate-400 font-semibold">100%</span>
                </div>
              </Link>

              <!-- Item 2: JWT Expiry -->
              <Link href="/admin/auth/sessions" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10" />
                      <polyline points="12 6 12 12 16 14" />
                    </svg>
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">15m JWT Expiry</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">Active</span>
                  <span class="ml-2 text-slate-400 font-semibold">100%</span>
                </div>
              </Link>

              <!-- Item 3: Superadmin 2FA -->
              <Link href="/admin/auth/policies" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M5 22h14" />
                      <path d="M5 2h14" />
                      <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22" />
                      <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2" />
                    </svg>
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400">Admin 2FA</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">Audit Req</span>
                  <span class="ml-2 text-slate-400 font-semibold">88%</span>
                </div>
              </Link>

              <!-- Item 4: Honeypot Grid -->
              <Link href="/admin/auth/cyber-security" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="8" r="6" />
                      <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                    </svg>
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-purple-600 dark:group-hover:text-purple-400">Honeypot Grid</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">Armed</span>
                  <span class="ml-2 text-slate-400 font-semibold">100%</span>
                </div>
              </Link>
            </div>
          </div>

          <!-- Total Posture bottom border -->
          <Link href="/admin/auth/policies" class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs mt-3 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer group">
            <span class="font-bold text-slate-700 dark:text-slate-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ i18n.t('ពិន្ទុសវនកម្មសុវត្ថិភាពសរុប', 'Overall Posture Score') }}</span>
            <span class="font-black text-emerald-600 dark:text-emerald-400 text-sm">88% Optimal</span>
          </Link>
        </div>

      </div>

      <!-- ── ROW 3: 3-COLUMN OPERATIONAL SECTION (5/12, 4/12, 3/12 MATCHING DASHBOARD) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (5/12): Incident Mitigation & Threat Stream Table -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3 flex items-center justify-between">
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                  {{ i18n.t('ការត្រួតពិនិត្យ និងកាត់បន្ថយការគំរាមកំហែង', 'Incident Mitigation & Threat Stream') }}
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ i18n.t('វិភាគភ្លាមៗនូវការប៉ុនប៉ងចូលមិនជោគជ័យ និងការទប់ស្កាត់ IP', 'Live triage of brute-force vectors & threat IPs') }}</p>
              </div>
              <button @click="exportIncidentsCsv" class="px-2.5 py-1 rounded-lg text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/40 hover:bg-blue-100 transition-colors cursor-pointer">
                {{ i18n.t('ទាញយក CSV', 'Export CSV') }}
              </button>
            </div>

            <!-- Contextual Filter Toolbar -->
            <div class="pb-2.5 flex items-center justify-between gap-2 text-xs">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search IP, Account, or Location..."
                class="w-full pl-3 pr-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-slate-100 focus:outline-none"
              />
              <select
                v-model="selectedVector"
                class="px-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-700 dark:text-slate-200 focus:outline-none cursor-pointer shrink-0"
              >
                <option value="all">All Vectors</option>
                <option value="failed">Failed Auth</option>
                <option value="brute">Brute-force</option>
                <option value="decoy">Decoy Trap</option>
              </select>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <th class="py-2.5 w-6">
                      <input
                        type="checkbox"
                        v-model="selectAll"
                        class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                      />
                    </th>
                    <th class="py-2.5 w-16">{{ i18n.t('ពេលវេលា', 'Time') }}</th>
                    <th class="py-2.5">{{ i18n.t('គណនី', 'Account') }}</th>
                    <th class="py-2.5 text-center">{{ i18n.t('IP & ទីតាំង', 'Origin IP') }}</th>
                    <th class="py-2.5 text-right">{{ i18n.t('សកម្មភាព', 'Action') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr
                    v-for="incident in filteredIncidents"
                    :key="incident.id"
                    :class="[
                      selectedIncidentIds.includes(incident.id) ? 'bg-indigo-50/60 dark:bg-indigo-950/30' : '',
                      'hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors'
                    ]"
                  >
                    <td class="py-2.5">
                      <input
                        type="checkbox"
                        :value="incident.id"
                        v-model="selectedIncidentIds"
                        :disabled="isInternalIp(incident.ip_address)"
                        class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 cursor-pointer disabled:opacity-25"
                      />
                    </td>
                    <td class="py-2.5 font-mono text-[11px] text-slate-400 tabular-nums whitespace-nowrap">{{ incident.time }}</td>
                    <td class="py-2.5 max-w-[150px]">
                      <div class="font-bold text-slate-800 dark:text-slate-100 truncate leading-tight">{{ incident.email }}</div>
                      <div class="text-[10px] text-slate-400 truncate mt-0.5">{{ incident.reason }}</div>
                    </td>
                    <td class="py-2.5 text-center whitespace-nowrap">
                      <div class="font-mono text-xs font-semibold text-slate-800 dark:text-slate-200">
                        {{ getIpGeo(incident.ip_address, incident.location).flag }} {{ incident.ip_address }}
                      </div>
                      <div class="text-[10px] text-slate-400 font-sans">
                        {{ getIpGeo(incident.ip_address, incident.location).label }}
                      </div>
                    </td>
                    <td class="py-2.5 text-right whitespace-nowrap">
                      <span
                        v-if="isInternalIp(incident.ip_address)"
                        class="px-2 py-0.5 text-[10px] font-mono text-slate-400 bg-slate-100 dark:bg-slate-800 rounded select-none"
                      >
                        Protected
                      </span>
                      <button
                        v-else
                        type="button"
                        @click="blockIp(incident.ip_address)"
                        class="px-2 py-1 text-[11px] font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 border border-red-100 dark:border-red-900/40 rounded-md hover:bg-red-100 transition-colors cursor-pointer"
                      >
                        {{ i18n.t('ទប់ស្កាត់', 'Block') }}
                      </button>
                    </td>
                  </tr>

                  <tr v-if="filteredIncidents.length === 0">
                    <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                      {{ i18n.t('គ្មានកំណត់ត្រាដែលត្រូវគ្នានឹងការស្វែងរក', 'No security incidents found.') }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400 mt-2">
            <span>24/7 Threat Guard Active</span>
            <span class="font-mono">{{ stats.failed_attempts_today }} threats logged today</span>
          </div>
        </div>

        <!-- Col 2 (4/12): Security Infrastructure Summary -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ i18n.t('សង្ខេបរចនាសម្ព័ន្ធសុវត្ថិភាព', 'Security Architecture Summary') }}
              </h3>
              <p class="text-[11px] text-slate-400 mt-0.5">{{ i18n.t('ម៉ាទ្រីសផ្ទៀងផ្ទាត់ និងបរិវេណការពារប្រព័ន្ធ', 'Core authentication matrix & defensive perimeter') }}</p>
            </div>

            <div class="space-y-2 mt-2 text-xs">
              <!-- Item 1: RBAC Roles -->
              <Link href="/admin/auth/roles" class="flex items-center justify-between p-2 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                      <path d="m9 12 2 2 4-4"/>
                    </svg>
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-800 dark:text-slate-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Active RBAC Roles</h4>
                    <p class="text-[10px] text-slate-400">Superadmin, Instructors, Students</p>
                  </div>
                </div>
                <span class="font-bold text-slate-900 dark:text-white">{{ stats.total_roles || 3 }} Roles</span>
              </Link>

              <!-- Item 2: Security Policies -->
              <Link href="/admin/auth/policies" class="flex items-center justify-between p-2 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-800 dark:text-slate-100 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Security Policies</h4>
                    <p class="text-[10px] text-slate-400">Password, JWT, Rate Limit, Pinning</p>
                  </div>
                </div>
                <span class="font-bold text-slate-900 dark:text-white">6 Active</span>
              </Link>

              <!-- Item 3: Blacklisted IPs -->
              <Link href="/admin/auth/history?filter=failed" class="flex items-center justify-between p-2 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"/>
                      <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                    </svg>
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-800 dark:text-slate-100 group-hover:text-red-600 dark:group-hover:text-red-400">Firewall Blacklist</h4>
                    <p class="text-[10px] text-slate-400">Active automated threat IP drop</p>
                  </div>
                </div>
                <span class="font-bold text-slate-900 dark:text-white">Active Guard</span>
              </Link>

              <!-- Item 4: Decoy Endpoints -->
              <Link href="/admin/auth/cyber-security" class="flex items-center justify-between p-2 rounded-xl bg-slate-50/70 dark:bg-slate-800/40 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-800 dark:text-slate-100 group-hover:text-purple-600 dark:group-hover:text-purple-400">Decoy Honeypots</h4>
                    <p class="text-[10px] text-slate-400">/admin/login.php, /.env sensors</p>
                  </div>
                </div>
                <span class="font-bold text-slate-900 dark:text-white">3 Traps</span>
              </Link>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 mt-2">
            <Link href="/admin/auth/cyber-security" class="text-xs text-blue-600 dark:text-blue-400 font-semibold hover:underline flex items-center justify-between">
              <span>{{ i18n.t('ពិនិត្យ Forensic និងការស៊ើបអង្កេត', 'View Forensics & Threat Intelligence') }}</span>
              <span>→</span>
            </Link>
          </div>
        </div>

        <!-- Col 3 (3/12): Critical Security Alerts -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                  {{ i18n.t('ការជូនដំណឹងបន្ទាន់', 'Critical Alerts') }}
                </h3>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300">
                4 Items
              </span>
            </div>

            <div class="space-y-3 mt-2">
              <!-- Alert Card 1: 14 Locked Accounts -->
              <div class="p-3.5 rounded-xl bg-rose-50/70 dark:bg-rose-950/25 border border-rose-200/70 dark:border-rose-900/40 flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <h4 class="font-bold text-xs text-slate-900 dark:text-white leading-tight">
                    14 Locked Accounts
                  </h4>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Exceeded password threshold
                  </p>
                </div>
                <button
                  type="button"
                  @click="isUnlockModalOpen = true"
                  class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 transition-colors shadow-xs shrink-0 cursor-pointer"
                >
                  Action
                </button>
              </div>

              <!-- Alert Card 2: 3 Decoy Scans -->
              <div class="p-3.5 rounded-xl bg-amber-50/70 dark:bg-amber-950/25 border border-amber-200/70 dark:border-amber-900/40 flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <h4 class="font-bold text-xs text-slate-900 dark:text-white leading-tight">
                    3 Decoy Trap Hits
                  </h4>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Automated intruder scans isolated
                  </p>
                </div>
                <Link
                  href="/admin/auth/cyber-security"
                  class="px-3 py-1.5 rounded-lg text-xs font-bold text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-950/60 hover:bg-amber-200 transition-colors shrink-0 cursor-pointer"
                >
                  Inspect
                </Link>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 mt-3">
            <Link
              href="/admin/auth/history?filter=failed"
              class="w-full py-1.5 px-3 rounded-lg text-xs font-semibold text-center text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 transition-colors flex items-center justify-center gap-1.5"
            >
              <span>{{ i18n.t('ពិនិត្យកំណត់ត្រាទាំងអស់', 'Review Incident Queue') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

      </div>

      <!-- FLOATING BATCH ACTION BAR FOR BULK ACTIONS -->
      <Teleport to="body">
        <div
          v-if="selectedIncidentIds.length > 0"
          class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 px-5 py-3 bg-white/95 dark:bg-slate-900/95 text-slate-900 dark:text-white border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl backdrop-blur-md flex items-center gap-4 transition-all duration-200 animate-in fade-in slide-in-from-bottom-3"
        >
          <div class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-200">
            <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
            <span><b class="font-mono text-slate-900 dark:text-white">{{ selectedIncidentIds.length }}</b> {{ i18n.t('ធាតុត្រូវបានជ្រើសរើស', 'items selected') }}:</span>
          </div>
          <div class="h-4 w-px bg-slate-200 dark:bg-slate-700"></div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="handleBulkBlockSelected"
              class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-md shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="m4.93 4.93 14.14 14.14"/>
              </svg>
              <span>{{ i18n.t('បិទខ្ទប់ IP ដែលបានជ្រើស', 'Block Selected IPs') }}</span>
            </button>
            <button
              type="button"
              @click="selectedIncidentIds = []"
              class="px-3 py-1.5 text-xs text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors cursor-pointer"
            >
              {{ i18n.t('បដិសេធ', 'Dismiss') }}
            </button>
          </div>
        </div>
      </Teleport>

      <!-- TWO-STEP DESTRUCTIVE ACTION CONFIRMATION MODAL FOR UNLOCK ALL (HIGH FRICTION) -->
      <Teleport to="body">
        <div
          v-if="isUnlockModalOpen"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm"
        >
          <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-2xl text-slate-900 dark:text-white space-y-4">
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
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                  {{ i18n.t('បញ្ជាក់សកម្មភាពសុវត្ថិភាពកម្រិតខ្ពស់', 'High-Risk Action Confirmation') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                  Bulk unsuspending accounts restores access to all locked users simultaneously. This may allow active credential-stuffing bots to retry immediately.
                </p>
              </div>
            </div>

            <!-- Caution Notice Box -->
            <div class="p-3 bg-amber-500/10 border border-amber-500/25 rounded-xl text-xs text-amber-800 dark:text-amber-300 leading-relaxed">
              ⚠️ <strong>Warning:</strong> Ensure that active brute-force sources have been firewalled prior to unsuspending accounts.
            </div>

            <!-- Safety Typing Confirmation Field (Explicit Friction) -->
            <div class="space-y-1.5 pt-1">
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                Type <span class="font-mono font-bold text-amber-600 dark:text-amber-400">CONFIRM</span> to authorize bulk unlock:
              </label>
              <input
                v-model="unlockConfirmationInput"
                type="text"
                placeholder="CONFIRM"
                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg font-mono text-xs uppercase tracking-wider text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
              />
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                @click="isUnlockModalOpen = false; unlockConfirmationInput = ''"
                class="px-4 py-2 text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors cursor-pointer"
              >
                {{ i18n.t('បោះបង់', 'Cancel') }}
              </button>

              <button
                type="button"
                :disabled="!isUnlockConfirmed || isProcessingUnlock"
                @click="handleConfirmUnlockAll"
                class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-500 disabled:opacity-40 disabled:cursor-not-allowed rounded-lg transition-all flex items-center gap-2 cursor-pointer shadow-md shadow-amber-600/20"
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
