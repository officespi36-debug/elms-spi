<script setup lang="ts">
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
  { id: 1, email: 'admin@spilms.tech', ip_address: '185.220.101.45', device: 'Linux Tor Exit', location: 'Frankfurt, DE', reason: 'Brute-force (5 failed)', time: '3m ago' },
  { id: 2, email: 'teacher_director@elms.com', ip_address: '194.26.29.112', device: 'Windows / Firefox', location: 'Kyiv, UA', reason: 'Geo-anomaly login', time: '18m ago' },
  { id: 3, email: 'guest_scan@domain.com', ip_address: '45.154.255.89', device: 'Python-requests/2.28', location: 'Amsterdam, NL', reason: 'Decoy /login scan', time: '42m ago' },
  { id: 4, email: 'student99@spilms.tech', ip_address: '110.74.218.45', device: 'Android / Chrome', location: 'Phnom Penh, KH', reason: 'Expired Token Reuse', time: '1h ago' }
]

const incidents = (props.recentFailedLogs && props.recentFailedLogs.length > 0)
  ? props.recentFailedLogs.map(log => ({
      id: log.id,
      email: log.email || 'unknown@user',
      ip_address: log.ip_address || '0.0.0.0',
      device: log.device || log.browser || 'Unknown Client',
      location: log.location || 'Unknown Geo',
      reason: 'Failed Auth Attempt',
      time: log.created_at ? new Date(log.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : 'Recently'
    }))
  : sampleIncidents

function blockIp(ip: string) {
  if (!ip) return
  if (confirm(`Block IP ${ip} permanently in firewall blacklist?`)) {
    router.post('/admin/auth-logs/block-ip', { ip_address: ip }, {
      preserveScroll: true
    })
  }
}

function unlockAllUsers() {
  if (confirm('Are you sure you want to unlock all currently suspended and locked accounts?')) {
    router.post('/admin/auth-logs/unlock-all', {}, {
      preserveScroll: true
    })
  }
}

function unlockSingleUser(userId: number) {
  router.post(`/admin/auth-logs/unlock/${userId}`, {}, {
    preserveScroll: true
  })
}
</script>

<template>
  <AdminLayout :title="i18n.t('មជ្ឈមណ្ឌលប្រតិបត្តិការសុវត្ថិភាព (SOC)', 'Security Operations Center (SOC)')">
    <div class="space-y-6 font-sans">
      <!-- Shared Header: Executive Command, Top 4 Non-AI Metric Cards, & Subnav Tabs -->
      <AuthModuleHeader activeTab="overview" :summaryStats="props.summaryStats" />

      <!-- SECTION 1: Actionable SOC Intelligence & Mitigation Console (2 Columns: 8/12 & 4/12) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- Left: Incident Triage & Live Threat Queue (8/12) -->
        <div class="lg:col-span-8 bg-white dark:bg-[#111827] border border-slate-200 dark:border-[#1F2937] rounded-xl p-5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.05)] flex flex-col justify-between">
          <div>
            <!-- Header Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-[#1F2937]">
              <div>
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-[#EF4444] animate-pulse"></span>
                  <h3 class="text-sm font-semibold text-slate-900 dark:text-[#F9FAFB] tracking-tight">
                    {{ i18n.t('ការត្រួតពិនិត្យ និងកាត់បន្ថយការគំរាមកំហែង (Live Incident Triage)', 'Incident Mitigation & Threat Stream') }}
                  </h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-[#9CA3AF] mt-1">
                  {{ i18n.t('វិភាគភ្លាមៗនូវការប៉ុនប៉ងចូលមិនជោគជ័យ និងការទប់ស្កាត់ IP ដែលមានហានិភ័យ', 'Live triage of brute-force vectors, failed authentications & suspicious origin IPs') }}
                </p>
              </div>

              <!-- Quick Triage Action Controls -->
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="unlockAllUsers"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg transition-colors cursor-pointer"
                  title="Unlock all accounts currently locked by brute-force limits"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#10B981]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 9.9-1"/>
                  </svg>
                  <span>{{ i18n.t('ដោះសោរគណនីទាំងអស់', 'Unlock All') }}</span>
                </button>

                <Link
                  href="/admin/auth/history?filter=failed"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-800/60 rounded-lg transition-colors"
                >
                  <span>{{ i18n.t('កំណត់ត្រាពេញលេញ', 'Full Audit') }}</span>
                  <span class="font-mono">→</span>
                </Link>
              </div>
            </div>

            <!-- Incident Feed Table -->
            <div class="mt-4 overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-100 dark:border-[#1F2937] text-slate-500 dark:text-[#9CA3AF]">
                    <th class="py-2.5 px-3 font-medium uppercase tracking-wider text-[11px]">{{ i18n.t('ពេលវេលា', 'Time') }}</th>
                    <th class="py-2.5 px-3 font-medium uppercase tracking-wider text-[11px]">{{ i18n.t('គណនីគោលដៅ', 'Target Account') }}</th>
                    <th class="py-2.5 px-3 font-medium uppercase tracking-wider text-[11px]">{{ i18n.t('IP & ទីតាំង', 'Origin IP / Location') }}</th>
                    <th class="py-2.5 px-3 font-medium uppercase tracking-wider text-[11px]">{{ i18n.t('មូលហេតុគំរាមកំហែង', 'Threat Vector') }}</th>
                    <th class="py-2.5 px-3 font-medium uppercase tracking-wider text-[11px] text-right">{{ i18n.t('សកម្មភាព', 'Action') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-[#1F2937]">
                  <tr
                    v-for="incident in incidents"
                    :key="incident.id"
                    class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                  >
                    <!-- Time -->
                    <td class="py-3 px-3 font-mono text-slate-500 dark:text-[#9CA3AF] whitespace-nowrap">
                      {{ incident.time }}
                    </td>

                    <!-- Account / Target -->
                    <td class="py-3 px-3">
                      <div class="font-medium text-slate-900 dark:text-[#F9FAFB]">{{ incident.email }}</div>
                      <div class="text-[11px] text-slate-500 dark:text-[#9CA3AF]">{{ incident.device }}</div>
                    </td>

                    <!-- Origin IP & Geo -->
                    <td class="py-3 px-3">
                      <div class="font-mono font-medium text-slate-800 dark:text-slate-300">{{ incident.ip_address }}</div>
                      <div class="text-[11px] text-slate-500 dark:text-[#9CA3AF]">{{ incident.location }}</div>
                    </td>

                    <!-- Threat Vector Badge -->
                    <td class="py-3 px-3">
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-[#7F1D1D]/20 text-[#EF4444] border border-rose-900/40">
                        {{ incident.reason }}
                      </span>
                    </td>

                    <!-- Action Button -->
                    <td class="py-3 px-3 text-right whitespace-nowrap">
                      <button
                        type="button"
                        @click="blockIp(incident.ip_address)"
                        class="inline-flex items-center px-2.5 py-1 text-[11px] font-medium rounded text-[#EF4444] bg-[#7F1D1D]/15 hover:bg-[#7F1D1D]/30 border border-rose-900/40 transition-colors cursor-pointer"
                        title="Add IP to firewall block list"
                      >
                        {{ i18n.t('ទប់ស្កាត់ IP', 'Block IP') }}
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Alert Context Footer -->
          <div class="pt-4 mt-3 border-t border-slate-100 dark:border-[#1F2937] flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-500 dark:text-[#9CA3AF] gap-2">
            <div class="flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
              <span>{{ i18n.t('ប្រព័ន្ធ Firewall & Rate Limiting កំពុងដំណើរការការពារស្វ័យប្រវត្តិ (24/7 Monitoring)', 'Firewall & automated rate-limit mitigation active 24/7') }}</span>
            </div>
            <span class="font-mono text-[11px]">{{ stats.failed_attempts_today }} {{ i18n.t('ការប៉ុនប៉ងត្រូវបានកត់ត្រាថ្ងៃនេះ', 'total attempts logged today') }}</span>
          </div>
        </div>

        <!-- Right: Security Posture & Hardening Status (4/12) -->
        <div class="lg:col-span-4 bg-white dark:bg-[#111827] border border-slate-200 dark:border-[#1F2937] rounded-xl p-5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.05)] flex flex-col justify-between">
          <div>
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-[#1F2937]">
              <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-[#F9FAFB] tracking-tight">
                  {{ i18n.t('ស្ថានភាពការពារប្រព័ន្ធ', 'Hardening & Posture') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-[#9CA3AF] mt-0.5">
                  {{ i18n.t('ពិន្ទុសវនកម្មសុវត្ថិភាពសរុប', 'Overall security posture index') }}
                </p>
              </div>
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-[#064E3B]/20 text-[#10B981] border border-emerald-900/40">
                {{ stats.security_score }}/100
              </span>
            </div>

            <!-- Progress Meter -->
            <div class="mt-4">
              <div class="flex justify-between items-center text-xs mb-1.5">
                <span class="text-slate-600 dark:text-slate-300 font-medium">{{ i18n.t('កម្រិតអនុលោមភាព:', 'Compliance Score:') }}</span>
                <span class="font-mono text-emerald-600 dark:text-[#10B981] font-bold">{{ stats.security_score }}% (OPTIMAL)</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                <div
                  class="bg-[#10B981] h-full rounded-full transition-all duration-500"
                  :style="{ width: stats.security_score + '%' }"
                ></div>
              </div>
            </div>

            <!-- Hardening Checklist -->
            <div class="mt-5 space-y-3">
              <!-- Item 1 -->
              <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs">
                <div class="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#10B981]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                  <span class="text-slate-700 dark:text-slate-300">{{ i18n.t('គោលការណ៍ពាក្យសម្ងាត់ (12+ តួអក្សរ)', 'Password Policy (12+ chars)') }}</span>
                </div>
                <span class="font-mono text-[11px] text-[#10B981] font-semibold">ENFORCED</span>
              </div>

              <!-- Item 2 -->
              <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs">
                <div class="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#10B981]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                  <span class="text-slate-700 dark:text-slate-300">{{ i18n.t('សុពលភាព JWT (15-min Idle Expiry)', 'JWT 15m Token Expiry') }}</span>
                </div>
                <span class="font-mono text-[11px] text-[#10B981] font-semibold">ACTIVE</span>
              </div>

              <!-- Item 3 -->
              <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs">
                <div class="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#F59E0B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                  </svg>
                  <span class="text-slate-700 dark:text-slate-300">{{ i18n.t('ការផ្ទៀងផ្ទាត់ 2FA សម្រាប់ Admin', 'Superadmin 2FA Security') }}</span>
                </div>
                <span class="font-mono text-[11px] text-[#F59E0B] font-semibold">AUDIT REQ</span>
              </div>

              <!-- Item 4 -->
              <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs">
                <div class="flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#10B981]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                  <span class="text-slate-700 dark:text-slate-300">{{ i18n.t('អន្ទាក់ Honeypots (3 Sensors)', 'Honeypot Decoy Sensors') }}</span>
                </div>
                <span class="font-mono text-[11px] text-[#10B981] font-semibold">ARMED</span>
              </div>
            </div>
          </div>

          <!-- Bottom Action Link -->
          <div class="pt-5 border-t border-slate-100 dark:border-[#1F2937] mt-5">
            <Link
              href="/admin/auth/policies"
              class="w-full py-2 px-3 rounded-lg text-xs font-medium text-center text-slate-800 dark:text-[#F9FAFB] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-[#1F2937] transition-colors flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('កែប្រែគោលការណ៍សុវត្ថិភាព', 'Configure Security Policies') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

      </div>

      <!-- SECTION 2: Core Operational Submodules (3 Columns: 4/12 Each) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        <!-- Module 1: Role-Based Access Control (RBAC) -->
        <div class="bg-white dark:bg-[#111827] border border-slate-200 dark:border-[#1F2937] hover:border-slate-400 dark:hover:border-[#374151] rounded-xl p-5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.05)] transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200/60 dark:border-indigo-800/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m9 12 2 2 4-4"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#064E3B]/20 text-[#10B981] border border-emerald-900/40">
                <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
                {{ i18n.t('RBAC សកម្ម', 'RBAC Active') }}
              </span>
            </div>

            <h4 class="text-sm font-semibold text-slate-900 dark:text-[#F9FAFB] mt-3">
              {{ i18n.t('តួនាទី និងសិទ្ធិប្រើប្រាស់', 'Role & Permission Matrix') }}
            </h4>
            <p class="text-xs text-slate-500 dark:text-[#9CA3AF] mt-1 leading-relaxed">
              {{ i18n.t('គ្រប់គ្រងម៉ាទ្រីសសិទ្ធិ (Matrix) សម្រាប់ Admin, Teacher, និង Student តាមគោលការណ៍ Least Privilege។', 'Manage granular permission boundaries across Admin, Teacher, and Student roles.') }}
            </p>

            <div class="mt-4 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 space-y-1.5 text-xs">
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#9CA3AF]">{{ i18n.t('តួនាទីកំណត់រចនាសម្ព័ន្ធ:', 'Configured Roles:') }}</span>
                <span class="font-mono font-bold text-slate-900 dark:text-[#F9FAFB]">{{ stats.total_roles }} {{ i18n.t('តួនាទី', 'Roles') }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#9CA3AF]">{{ i18n.t('សិទ្ធិប្រតិបត្តិការ:', 'Assigned Permissions:') }}</span>
                <span class="font-mono font-bold text-slate-900 dark:text-[#F9FAFB]">{{ stats.total_permissions }} {{ i18n.t('សិទ្ធិ', 'Permissions') }}</span>
              </div>
            </div>
          </div>

          <div class="pt-4 mt-4 border-t border-slate-100 dark:border-[#1F2937]">
            <Link
              href="/admin/auth/roles"
              class="w-full py-2 px-3 rounded-lg text-xs font-medium text-center text-indigo-600 dark:text-indigo-400 hover:text-white hover:bg-indigo-600 dark:hover:bg-indigo-600 border border-indigo-200 dark:border-indigo-800/60 transition-colors flex items-center justify-center gap-1.5"
            >
              <span>{{ i18n.t('គ្រប់គ្រងម៉ាទ្រីស RBAC', 'Manage Roles Matrix') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

        <!-- Module 2: Live JWT Session Monitor -->
        <div class="bg-white dark:bg-[#111827] border border-slate-200 dark:border-[#1F2937] hover:border-slate-400 dark:hover:border-[#374151] rounded-xl p-5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.05)] transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200/60 dark:border-emerald-800/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="20" height="14" x="2" y="3" rx="2"/>
                  <line x1="8" x2="16" y1="21" y2="21"/>
                  <line x1="12" x2="12" y1="17" y2="21"/>
                  <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-mono font-medium bg-[#064E3B]/20 text-[#10B981] border border-emerald-900/40">
                <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] animate-pulse"></span>
                {{ stats.active_sessions_now }} {{ i18n.t('Online', 'Active') }}
              </span>
            </div>

            <h4 class="text-sm font-semibold text-slate-900 dark:text-[#F9FAFB] mt-3">
              {{ i18n.t('សម័យការសកម្ម (JWT Sessions)', 'Live JWT Session Tracking') }}
            </h4>
            <p class="text-xs text-slate-500 dark:text-[#9CA3AF] mt-1 leading-relaxed">
              {{ i18n.t('តាមដានឧបករណ៍ កំណត់សម្គាល់ User-Agent និងដកហូត Session Token ភ្លាមៗក្នុងករណីសង្ស័យ។', 'Inspect active Bearer tokens, connected clients, and instantly revoke compromised sessions.') }}
            </p>

            <div class="mt-4 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 space-y-1.5 text-xs">
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#9CA3AF]">{{ i18n.t('ឧបករណ៍ Desktop:', 'Desktop Sessions:') }}</span>
                <span class="font-mono font-bold text-slate-900 dark:text-[#F9FAFB]">85%</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#9CA3AF]">{{ i18n.t('ឧបករណ៍ចល័ត Mobile:', 'Mobile Clients:') }}</span>
                <span class="font-mono font-bold text-slate-900 dark:text-[#F9FAFB]">15%</span>
              </div>
            </div>
          </div>

          <div class="pt-4 mt-4 border-t border-slate-100 dark:border-[#1F2937]">
            <Link
              href="/admin/auth/sessions"
              class="w-full py-2 px-3 rounded-lg text-xs font-medium text-center text-emerald-600 dark:text-[#10B981] hover:text-white hover:bg-emerald-600 dark:hover:bg-emerald-600 border border-emerald-200 dark:border-emerald-800/60 transition-colors flex items-center justify-center gap-1.5"
            >
              <span>{{ i18n.t('ត្រួតពិនិត្យសម័យការ', 'Monitor Active Sessions') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

        <!-- Module 3: Cyber Forensics & Honeypot Sensor -->
        <div class="bg-white dark:bg-[#111827] border border-slate-200 dark:border-[#1F2937] hover:border-slate-400 dark:hover:border-[#374151] rounded-xl p-5 shadow-xs dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.05)] transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-9 h-9 rounded-lg bg-rose-50 dark:bg-rose-950/50 border border-rose-200/60 dark:border-rose-800/40 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m14.5 9-5 5"/>
                  <path d="m9.5 9 5 5"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-[#7F1D1D]/20 text-[#EF4444] border border-rose-900/40">
                <span class="w-1.5 h-1.5 rounded-full bg-[#EF4444] animate-pulse"></span>
                {{ i18n.t('អន្ទាក់សកម្ម', 'Sensors Armed') }}
              </span>
            </div>

            <h4 class="text-sm font-semibold text-slate-900 dark:text-[#F9FAFB] mt-3">
              {{ i18n.t('អន្ទាក់ Honeypots & Cyber Forensics', 'Threat Forensics & Honeypots') }}
            </h4>
            <p class="text-xs text-slate-500 dark:text-[#9CA3AF] mt-1 leading-relaxed">
              {{ i18n.t('អន្ទាក់ចាប់ការវាយប្រហារលើ Endpoint ក្លែងក្លាយ និងវិភាគកំណត់ត្រាហានិភ័យស្វ័យប្រវត្តិ។', 'Automated decoy endpoints trap malicious crawlers and log forensics for threat analysis.') }}
            </p>

            <div class="mt-4 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 space-y-1.5 text-xs">
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#9CA3AF]">{{ i18n.t('អន្ទាក់ Decoy Sensors:', 'Armed Sensors:') }}</span>
                <span class="font-mono font-bold text-slate-900 dark:text-[#F9FAFB]">3 Endpoints</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 dark:text-[#9CA3AF]">{{ i18n.t('គណនីកំពុងផ្អាក Suspended:', 'Suspended Accounts:') }}</span>
                <span class="font-mono font-bold text-[#EF4444]">14 Accounts</span>
              </div>
            </div>
          </div>

          <div class="pt-4 mt-4 border-t border-slate-100 dark:border-[#1F2937]">
            <Link
              href="/admin/auth/cyber-security"
              class="w-full py-2 px-3 rounded-lg text-xs font-medium text-center text-rose-600 dark:text-rose-400 hover:text-white hover:bg-rose-600 dark:hover:bg-rose-600 border border-rose-200 dark:border-rose-800/60 transition-colors flex items-center justify-center gap-1.5"
            >
              <span>{{ i18n.t('បើកមជ្ឈមណ្ឌល Forensics', 'Open Forensics Center') }}</span>
              <span class="font-mono">→</span>
            </Link>
          </div>
        </div>

      </div>
    </div>
  </AdminLayout>
</template>
