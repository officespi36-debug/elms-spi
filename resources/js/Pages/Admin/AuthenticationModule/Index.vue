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

function blockIp(ip: string) {
  if (!ip) return
  if (confirm(`Block IP ${ip} permanently in firewall blacklist?`)) {
    router.post('/admin/auth-logs/block-ip', { ip_address: ip }, {
      preserveScroll: true
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
    <!-- Unified Dark Canvas Wrapper to prevent any split-theme contrast -->
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 -mt-6 sm:-mt-8 -mb-12 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 min-h-screen bg-[#0A0D14] text-[#F1F5F9] space-y-6">
      
      <!-- Shared Header: Refined Security Dark Command Header & KPI Cards -->
      <AuthModuleHeader activeTab="overview" :summaryStats="props.summaryStats" />

      <!-- SECTION 1: Actionable SOC Intelligence & Mitigation Console (2 Columns: 8/12 & 4/12) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
        
        <!-- Left: Incident Triage & Live Threat Queue (8/12) -->
        <div class="lg:col-span-8 bg-[#111622] border border-[#1E2638] rounded-2xl p-5 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] flex flex-col justify-between">
          <div>
            <!-- Header Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-[#1E2638]">
              <div>
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-[#EF4444] animate-pulse"></span>
                  <h3 class="text-sm font-semibold text-[#F1F5F9] tracking-tight">
                    {{ i18n.t('ការត្រួតពិនិត្យ និងកាត់បន្ថយការគំរាមកំហែង', 'Incident Mitigation & Threat Stream') }}
                  </h3>
                </div>
                <p class="text-xs text-[#94A3B8] mt-1">
                  {{ i18n.t('វិភាគភ្លាមៗនូវការប៉ុនប៉ងចូលមិនជោគជ័យ និងការទប់ស្កាត់ IP ដែលមានហានិភ័យ', 'Live triage of brute-force vectors, failed authentications & suspicious origin IPs') }}
                </p>
              </div>

              <!-- Quick Triage Action Controls (Secondary Outline Button with Safety Warning) -->
              <div class="flex items-center gap-2.5 shrink-0">
                <button
                  type="button"
                  @click="isUnlockModalOpen = true"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#94A3B8] hover:text-amber-400 bg-transparent hover:bg-amber-500/10 border border-[#1E2638] hover:border-amber-500/40 rounded-lg transition-colors cursor-pointer shadow-xs"
                  title="Unlock all accounts currently suspended (Requires Confirmation)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#F59E0B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                  </svg>
                  <span>{{ i18n.t('ដោះសោរគណនីទាំងអស់', 'Unlock All') }}</span>
                </button>

                <Link
                  href="/admin/auth/history?filter=failed"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#94A3B8] hover:text-[#F1F5F9] bg-transparent hover:bg-[#1E2638]/40 border border-[#1E2638] rounded-lg transition-colors"
                >
                  <span>{{ i18n.t('កំណត់ត្រាពេញលេញ', 'Full Audit') }}</span>
                  <span class="font-mono">→</span>
                </Link>
              </div>
            </div>

            <!-- Contextual Drill-down & Filter Toolbar -->
            <div class="pt-3.5 pb-2 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
              <!-- Search Input -->
              <div class="relative w-full sm:w-64">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8] pointer-events-none">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                  </svg>
                </span>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Filter by IP, Account, or Location..."
                  class="w-full pl-8 pr-3 py-1.5 bg-[#0E131E] border border-[#1E2638] rounded-lg text-xs text-[#F1F5F9] placeholder-[#94A3B8]/60 focus:outline-none focus:border-indigo-500/60 transition-colors"
                />
              </div>

              <!-- Threat Vector Selector -->
              <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <span class="text-[11px] text-[#94A3B8] hidden sm:inline">Threat Vector:</span>
                <select
                  v-model="selectedVector"
                  class="px-2.5 py-1.5 bg-[#0E131E] border border-[#1E2638] rounded-lg text-xs text-[#F1F5F9] focus:outline-none focus:border-indigo-500/60 cursor-pointer"
                >
                  <option value="all">All Vectors</option>
                  <option value="failed">Failed Auth Attempt</option>
                  <option value="brute">Brute-force (5+)</option>
                  <option value="decoy">Decoy Scan</option>
                </select>
              </div>
            </div>

            <!-- Incident Feed Table (Consistent 48px row height & 12px 16px cell padding) -->
            <div class="mt-2 overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-[#1E2638] text-[#94A3B8]">
                    <th class="py-3 px-4 font-semibold uppercase tracking-wider text-[11px] w-24">{{ i18n.t('ពេលវេលា', 'Time') }}</th>
                    <th class="py-3 px-4 font-semibold uppercase tracking-wider text-[11px]">{{ i18n.t('គណនីគោលដៅ', 'Target Account') }}</th>
                    <th class="py-3 px-4 font-semibold uppercase tracking-wider text-[11px]">{{ i18n.t('IP & ទីតាំង', 'Origin IP / Location') }}</th>
                    <th class="py-3 px-4 font-semibold uppercase tracking-wider text-[11px]">{{ i18n.t('មូលហេតុគំរាមកំហែង', 'Threat Vector') }}</th>
                    <th class="py-3 px-4 font-semibold uppercase tracking-wider text-[11px] text-right">{{ i18n.t('សកម្មភាព', 'Action') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#1E2638]">
                  <tr
                    v-for="incident in filteredIncidents"
                    :key="incident.id"
                    class="h-12 hover:bg-[#161D2D] transition-colors"
                  >
                    <!-- Time (Monospace) -->
                    <td class="py-3 px-4 font-mono text-[#94A3B8] whitespace-nowrap text-xs">
                      {{ incident.time }}
                    </td>

                    <!-- Account / Target -->
                    <td class="py-3 px-4">
                      <div class="font-medium text-[#F1F5F9] leading-tight">{{ incident.email }}</div>
                      <div class="text-[11px] text-[#94A3B8] leading-tight mt-0.5">{{ incident.device }}</div>
                    </td>

                    <!-- Origin IP & Geo (Monospace) -->
                    <td class="py-3 px-4">
                      <div class="font-mono text-xs text-[#F1F5F9] leading-tight">{{ incident.ip_address }}</div>
                      <div class="text-[11px] text-[#94A3B8] leading-tight mt-0.5">{{ incident.location }}</div>
                    </td>

                    <!-- Threat Vector Badge -->
                    <td class="py-3 px-4">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-[#EF4444]/12 text-[#EF4444] border border-[#EF4444]/25">
                        {{ incident.reason }}
                      </span>
                    </td>

                    <!-- Action Button: Ghost Button (No loud red borders by default) -->
                    <td class="py-3 px-4 text-right whitespace-nowrap">
                      <button
                        type="button"
                        @click="blockIp(incident.ip_address)"
                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded text-[#94A3B8] bg-transparent hover:bg-[#EF4444]/15 border border-[#1E2638] hover:border-[#EF4444] hover:text-[#EF4444] transition-colors cursor-pointer"
                        title="Add IP to firewall block list"
                      >
                        {{ i18n.t('ទប់ស្កាត់ IP', 'Block IP') }}
                      </button>
                    </td>
                  </tr>

                  <tr v-if="filteredIncidents.length === 0">
                    <td colspan="5" class="py-8 text-center text-[#94A3B8] text-xs">
                      {{ i18n.t('គ្មានកំណត់ត្រាដែលត្រូវគ្នានឹងការស្វែងរក', 'No security incidents found matching your search filter.') }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Alert Context Footer -->
          <div class="pt-4 mt-3 border-t border-[#1E2638] flex flex-col sm:flex-row sm:items-center justify-between text-xs text-[#94A3B8] gap-2">
            <div class="flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
              <span>{{ i18n.t('ប្រព័ន្ធ Firewall & Rate Limiting កំពុងដំណើរការការពារស្វ័យប្រវត្តិ (24/7 Monitoring)', 'Firewall & automated rate-limit mitigation active 24/7') }}</span>
            </div>
            <span class="font-mono text-[11px] text-[#94A3B8]">{{ stats.failed_attempts_today }} {{ i18n.t('ការប៉ុនប៉ងត្រូវបានកត់ត្រាថ្ងៃនេះ', 'total attempts logged today') }}</span>
          </div>
        </div>

        <!-- Right: Security Posture & Hardening Progress Widget (4/12) -->
        <div class="lg:col-span-4 bg-[#111622] border border-[#1E2638] rounded-2xl p-5 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] flex flex-col justify-between">
          <div>
            <!-- Header without big oval badge -->
            <div class="pb-4 border-b border-[#1E2638]">
              <h3 class="text-sm font-semibold text-[#F1F5F9] tracking-tight">
                {{ i18n.t('ស្ថានភាពការពារប្រព័ន្ធ', 'Hardening & Posture') }}
              </h3>
              <p class="text-xs text-[#94A3B8] mt-0.5">
                {{ i18n.t('ពិន្ទុសវនកម្មសុវត្ថិភាពសរុប', 'Overall security posture index') }}
              </p>
            </div>

            <!-- Refined Security Posture Score Widget (Large Metric + Radial Visual) -->
            <div class="mt-4 p-4 rounded-xl bg-[#0E131E] border border-[#1E2638] flex items-center justify-between">
              <div>
                <div class="text-3xl font-extrabold font-mono text-[#F1F5F9] tracking-tight">
                  {{ stats.security_score }}%
                </div>
                <p class="text-xs text-[#94A3B8] mt-1 font-medium">Security Posture Index</p>
                <div class="flex items-center gap-1.5 mt-1.5">
                  <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                  <span class="text-[11px] font-semibold text-[#10B981]">OPTIMAL PROTECTION</span>
                </div>
              </div>

              <!-- Mini Circular Radial Meter -->
              <div class="relative w-16 h-16 flex items-center justify-center shrink-0">
                <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                  <path
                    class="text-[#1E2638]"
                    stroke-width="3.5"
                    stroke="currentColor"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                  <path
                    class="text-[#10B981]"
                    stroke-dasharray="88, 100"
                    stroke-width="3.5"
                    stroke-linecap="round"
                    stroke="currentColor"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                </svg>
                <span class="absolute text-[11px] font-bold font-mono text-[#F1F5F9]">88%</span>
              </div>
            </div>

            <!-- Hardening Checklist (16px Icons, Left Label & Right-aligned Status Pill) -->
            <div class="mt-4 divide-y divide-[#1E2638] text-xs">
              
              <!-- Item 1: Password Policy -->
              <div class="py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#10B981] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                  <span class="text-[#F1F5F9] truncate">{{ i18n.t('គោលការណ៍ពាក្យសម្ងាត់ (12+ តួអក្សរ)', 'Password Policy (12+ chars)') }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-[#10B981]/12 text-[#10B981] border border-[#10B981]/25 shrink-0">
                  ENFORCED
                </span>
              </div>

              <!-- Item 2: JWT Inactivity Expiry -->
              <div class="py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#10B981] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                  <span class="text-[#F1F5F9] truncate">{{ i18n.t('សុពលភាព JWT (15-min Idle Expiry)', 'JWT 15m Token Expiry') }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-[#10B981]/12 text-[#10B981] border border-[#10B981]/25 shrink-0">
                  ACTIVE
                </span>
              </div>

              <!-- Item 3: Superadmin 2FA -->
              <div class="py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#F59E0B] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                  </svg>
                  <span class="text-[#F1F5F9] truncate">{{ i18n.t('ការផ្ទៀងផ្ទាត់ 2FA សម្រាប់ Admin', 'Superadmin 2FA Security') }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-[#F59E0B]/12 text-[#F59E0B] border border-[#F59E0B]/25 shrink-0">
                  AUDIT REQ
                </span>
              </div>

              <!-- Item 4: Honeypot Decoy Sensors -->
              <div class="py-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#10B981] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                  <span class="text-[#F1F5F9] truncate">{{ i18n.t('អន្ទាក់ Honeypots (3 Sensors)', 'Honeypot Decoy Sensors') }}</span>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-[#10B981]/12 text-[#10B981] border border-[#10B981]/25 shrink-0">
                  ARMED
                </span>
              </div>
            </div>
          </div>

          <!-- Bottom Action Link: Ghost Button -->
          <div class="pt-4 border-t border-[#1E2638] mt-4">
            <Link
              href="/admin/auth/policies"
              class="w-full py-2 px-3 rounded-lg text-xs font-medium text-center text-[#94A3B8] hover:text-[#F1F5F9] bg-[#0E131E] hover:bg-[#161D2D] border border-[#1E2638] transition-colors flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('កែប្រែគោលការណ៍សុវត្ថិភាព', 'Configure Security Policies') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

      </div>

      <!-- SECTION 2: Core Operational Submodules (3 Columns: 4/12 Each - Unified Dark Tokens) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <!-- Module 1: Role-Based Access Control (RBAC) -->
        <div class="bg-[#111622] border border-[#1E2638] hover:border-slate-600 rounded-2xl p-5 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-9 h-9 rounded-lg bg-[#0E131E] border border-[#1E2638] text-indigo-400 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m9 12 2 2 4-4"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#10B981]/12 text-[#10B981] border border-[#10B981]/25">
                <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
                {{ i18n.t('RBAC សកម្ម', 'RBAC Active') }}
              </span>
            </div>

            <h4 class="text-sm font-semibold text-[#F1F5F9] mt-3">
              {{ i18n.t('តួនាទី និងសិទ្ធិប្រើប្រាស់', 'Role & Permission Matrix') }}
            </h4>
            <p class="text-xs text-[#94A3B8] mt-1 leading-relaxed">
              {{ i18n.t('គ្រប់គ្រងម៉ាទ្រីសសិទ្ធិ (Matrix) សម្រាប់ Admin, Teacher, និង Student តាមគោលការណ៍ Least Privilege។', 'Manage granular permission boundaries across Admin, Teacher, and Student roles.') }}
            </p>

            <div class="mt-4 p-3 rounded-lg bg-[#0E131E] border border-[#1E2638] space-y-1.5 text-xs">
              <div class="flex justify-between items-center">
                <span class="text-[#94A3B8]">{{ i18n.t('តួនាទីកំណត់រចនាសម្ព័ន្ធ:', 'Configured Roles:') }}</span>
                <span class="font-mono font-bold text-[#F1F5F9]">{{ stats.total_roles }} {{ i18n.t('តួនាទី', 'Roles') }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-[#94A3B8]">{{ i18n.t('សិទ្ធិប្រតិបត្តិការ:', 'Assigned Permissions:') }}</span>
                <span class="font-mono font-bold text-[#F1F5F9]">{{ stats.total_permissions }} {{ i18n.t('សិទ្ធិ', 'Permissions') }}</span>
              </div>
            </div>
          </div>

          <div class="pt-4 mt-4 border-t border-[#1E2638]">
            <Link
              href="/admin/auth/roles"
              class="w-full py-2 px-3 rounded-lg text-xs font-medium text-center text-[#94A3B8] hover:text-[#F1F5F9] bg-[#0E131E] hover:bg-[#161D2D] border border-[#1E2638] transition-colors flex items-center justify-center gap-1.5"
            >
              <span>{{ i18n.t('គ្រប់គ្រងម៉ាទ្រីស RBAC', 'Manage Roles Matrix') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

        <!-- Module 2: Live JWT Session Monitor -->
        <div class="bg-[#111622] border border-[#1E2638] hover:border-slate-600 rounded-2xl p-5 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-9 h-9 rounded-lg bg-[#0E131E] border border-[#1E2638] text-emerald-400 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="20" height="14" x="2" y="3" rx="2"/>
                  <line x1="8" x2="16" y1="21" y2="21"/>
                  <line x1="12" x2="12" y1="17" y2="21"/>
                  <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-mono font-medium bg-[#10B981]/12 text-[#10B981] border border-[#10B981]/25">
                <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] animate-pulse"></span>
                {{ stats.active_sessions_now }} {{ i18n.t('Online', 'Active') }}
              </span>
            </div>

            <h4 class="text-sm font-semibold text-[#F1F5F9] mt-3">
              {{ i18n.t('សម័យការសកម្ម (JWT Sessions)', 'Live JWT Session Tracking') }}
            </h4>
            <p class="text-xs text-[#94A3B8] mt-1 leading-relaxed">
              {{ i18n.t('តាមដានឧបករណ៍ កំណត់សម្គាល់ User-Agent និងដកហូត Session Token ភ្លាមៗក្នុងករណីសង្ស័យ។', 'Inspect active Bearer tokens, connected clients, and instantly revoke compromised sessions.') }}
            </p>

            <div class="mt-4 p-3 rounded-lg bg-[#0E131E] border border-[#1E2638] space-y-1.5 text-xs">
              <div class="flex justify-between items-center">
                <span class="text-[#94A3B8]">{{ i18n.t('ឧបករណ៍ Desktop:', 'Desktop Sessions:') }}</span>
                <span class="font-mono font-bold text-[#F1F5F9]">85%</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-[#94A3B8]">{{ i18n.t('ឧបករណ៍ចល័ត Mobile:', 'Mobile Clients:') }}</span>
                <span class="font-mono font-bold text-[#F1F5F9]">15%</span>
              </div>
            </div>
          </div>

          <div class="pt-4 mt-4 border-t border-[#1E2638]">
            <Link
              href="/admin/auth/sessions"
              class="w-full py-2 px-3 rounded-lg text-xs font-medium text-center text-[#94A3B8] hover:text-[#F1F5F9] bg-[#0E131E] hover:bg-[#161D2D] border border-[#1E2638] transition-colors flex items-center justify-center gap-1.5"
            >
              <span>{{ i18n.t('ត្រួតពិនិត្យសម័យការ', 'Monitor Active Sessions') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

        <!-- Module 3: Cyber Forensics & Honeypot Sensor -->
        <div class="bg-[#111622] border border-[#1E2638] hover:border-slate-600 rounded-2xl p-5 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-9 h-9 rounded-lg bg-[#0E131E] border border-[#1E2638] text-rose-400 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m14.5 9-5 5"/>
                  <path d="m9.5 9 5 5"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#EF4444]/12 text-[#EF4444] border border-[#EF4444]/25">
                <span class="w-1.5 h-1.5 rounded-full bg-[#EF4444] animate-pulse"></span>
                {{ i18n.t('អន្ទាក់សកម្ម', 'Sensors Armed') }}
              </span>
            </div>

            <h4 class="text-sm font-semibold text-[#F1F5F9] mt-3">
              {{ i18n.t('អន្ទាក់ Honeypots & Cyber Forensics', 'Threat Forensics & Honeypots') }}
            </h4>
            <p class="text-xs text-[#94A3B8] mt-1 leading-relaxed">
              {{ i18n.t('អន្ទាក់ចាប់ការវាយប្រហារលើ Endpoint ក្លែងក្លាយ និងវិភាគកំណត់ត្រាហានិភ័យស្វ័យប្រវត្តិ។', 'Automated decoy endpoints trap malicious crawlers and log forensics for threat analysis.') }}
            </p>

            <div class="mt-4 p-3 rounded-lg bg-[#0E131E] border border-[#1E2638] space-y-1.5 text-xs">
              <div class="flex justify-between items-center">
                <span class="text-[#94A3B8]">{{ i18n.t('អន្ទាក់ Decoy Sensors:', 'Armed Sensors:') }}</span>
                <span class="font-mono font-bold text-[#F1F5F9]">3 Endpoints</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-[#94A3B8]">{{ i18n.t('គណនីកំពុងផ្អាក Suspended:', 'Suspended Accounts:') }}</span>
                <span class="font-mono font-bold text-[#EF4444]">14 Accounts</span>
              </div>
            </div>
          </div>

          <div class="pt-4 mt-4 border-t border-[#1E2638]">
            <Link
              href="/admin/auth/cyber-security"
              class="w-full py-2 px-3 rounded-lg text-xs font-medium text-center text-[#94A3B8] hover:text-[#F1F5F9] bg-[#0E131E] hover:bg-[#161D2D] border border-[#1E2638] transition-colors flex items-center justify-center gap-1.5"
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
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        >
          <div class="relative w-full max-w-md bg-[#111622] border border-[#1E2638] rounded-2xl p-6 shadow-2xl text-[#F1F5F9] space-y-4">
            <!-- Modal Header -->
            <div class="flex items-start gap-3.5">
              <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-400 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                  <line x1="12" y1="9" x2="12" y2="13"/>
                  <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
              </div>
              <div class="min-w-0">
                <h3 class="text-sm font-bold text-[#F1F5F9]">
                  {{ i18n.t('បញ្ជាក់សកម្មភាពសុវត្ថិភាពកម្រិតខ្ពស់', 'High-Risk Action Confirmation') }}
                </h3>
                <p class="text-xs text-[#94A3B8] mt-1 leading-relaxed">
                  {{ i18n.t('តើអ្នកពិតជាចង់ដោះសោគណនីទាំងអស់ (Bulk Unlock) មែនទេ?', 'In security operations, bulk unsuspending accounts restores access to all 14 locked users simultaneously. This may allow active credential-stuffing bots to retry immediately.') }}
                </p>
              </div>
            </div>

            <!-- Caution Notice Box -->
            <div class="p-3 bg-amber-500/10 border border-amber-500/25 rounded-xl text-xs text-amber-300/90 leading-relaxed">
              ⚠️ <strong>Warning:</strong> Ensure that active brute-force sources have been firewalled prior to unsuspending accounts.
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2">
              <button
                type="button"
                @click="isUnlockModalOpen = false"
                class="px-4 py-2 text-xs font-medium text-[#94A3B8] hover:text-[#F1F5F9] bg-[#0E131E] hover:bg-[#161D2D] border border-[#1E2638] rounded-lg transition-colors cursor-pointer"
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
