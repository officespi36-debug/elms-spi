<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
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
</script>

<template>
  <AdminLayout :title="i18n.t('ផ្ទាំងគ្រប់គ្រងប្រព័ន្ធសុវត្ថិភាព និងការផ្ទៀងផ្ទាត់', 'Authentication Module Overview')">
    <div class="space-y-6 font-sans">
      <!-- Shared Navigation Header & Top Metrics (Telegram / Apple iOS Style) -->
      <AuthModuleHeader activeTab="overview" :summaryStats="props.summaryStats" />

      <!-- Main Overview 6-Card Grid (Apple / Telegram Clean Glassmorphic Grid) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        
        <!-- 1. Roles & Permissions Card -->
        <Link
          href="/admin/auth/roles"
          class="relative overflow-hidden bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-xs backdrop-blur-xl h-full cursor-pointer"
        >
          <div>
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#8B5CF6] to-[#6366F1] text-white flex items-center justify-center shrink-0 shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m9 12 2 2 4-4"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-purple-700 dark:text-purple-300 font-bold bg-purple-50 dark:bg-purple-500/15 px-3 py-1 rounded-full border border-purple-200/80 dark:border-purple-500/30">
                <span class="w-2 h-2 rounded-full bg-purple-500 dark:bg-purple-400"></span>
                {{ i18n.t('RBAC សកម្ម', 'RBAC Active') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors">
              {{ i18n.t('តួនាទី និងសិទ្ធិប្រើប្រាស់', 'Roles & Permissions') }}
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('គ្រប់គ្រងតួនាទី និងម៉ាទ្រីសសិទ្ធិប្រើប្រាស់សម្រាប់ Admin, Teacher, និង Student', 'Manage roles and access permissions matrix for Admin, Teacher, and Student') }}
            </p>

            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/70 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('តួនាទីសកម្ម:', 'Active Roles:') }}</span>
                <span class="font-bold text-purple-600 dark:text-purple-300 font-sans text-xs bg-purple-50 dark:bg-purple-500/15 px-2.5 py-0.5 rounded-full border border-purple-200/80 dark:border-purple-500/30">
                  {{ stats.total_roles }} {{ i18n.t('តួនាទី', 'Roles') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('សិទ្ធិសរុប:', 'Total Permissions:') }}</span>
                <span class="font-bold text-purple-600 dark:text-purple-300 font-sans text-xs bg-purple-50 dark:bg-purple-500/15 px-2.5 py-0.5 rounded-full border border-purple-200/80 dark:border-purple-500/30">
                  {{ stats.total_permissions }} {{ i18n.t('សិទ្ធិ', 'Permissions') }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('គ្រប់គ្រងតួនាទី & សិទ្ធិ', 'Manage Roles & Permissions') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 2. Active Sessions Card -->
        <Link
          href="/admin/auth/sessions"
          class="relative overflow-hidden bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-xs backdrop-blur-xl h-full cursor-pointer"
        >
          <div>
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#10B981] to-[#059669] text-white flex items-center justify-center shrink-0 shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="20" height="14" x="2" y="3" rx="2"/>
                  <line x1="8" x2="16" y1="21" y2="21"/>
                  <line x1="12" x2="12" y1="17" y2="21"/>
                  <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-300 font-bold bg-emerald-50 dark:bg-emerald-500/15 px-3 py-1 rounded-full border border-emerald-200/80 dark:border-emerald-500/30">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ i18n.t('តាមដានផ្ទាល់', 'Live Monitor') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors">
              {{ i18n.t('សម័យការសកម្ម', 'Active Sessions') }}
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('តាមដានអ្នកប្រើប្រាស់កំពុង Online និងដកហូត Session Tokens ភ្លាមៗ', 'Track live users and revoke active JWT session tokens') }}
            </p>

            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/70 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('កំពុង Online ឥឡូវនេះ:', 'Sessions Online Now:') }}</span>
                <span
                  class="font-bold text-xs px-2.5 py-0.5 rounded-full border"
                  :class="stats.active_sessions_now > 0 ? 'text-emerald-600 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/15 border-emerald-200/80 dark:border-emerald-500/30' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-900 border-slate-200 dark:border-slate-800'"
                >
                  {{ stats.active_sessions_now > 0 ? stats.active_sessions_now.toLocaleString() + ' ' + i18n.t('Online', 'Online') : i18n.t('0 សម័យការ', '0 Active Sessions') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ស្ថានភាពសម័យការ:', 'Session Status:') }}</span>
                <span
                  class="font-bold text-xs"
                  :class="stats.active_sessions_now > 0 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-amber-600 dark:text-amber-400 font-bold'"
                >
                  {{ stats.active_sessions_now > 0 ? i18n.t('សកម្មធម្មតា', 'Normal Active') : i18n.t('គ្មានសម័យការ', 'Idle (No Sessions)') }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('តាមដានសម័យការសកម្ម', 'Monitor Active Sessions') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 3. Login History Card -->
        <Link
          href="/admin/auth/history"
          class="relative overflow-hidden bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-xs backdrop-blur-xl h-full cursor-pointer"
        >
          <div>
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#0284C7] to-[#2563EB] text-white flex items-center justify-center shrink-0 shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-sky-700 dark:text-sky-300 font-bold bg-sky-50 dark:bg-sky-500/15 px-3 py-1 rounded-full border border-sky-200/80 dark:border-sky-500/30">
                <span class="w-2 h-2 rounded-full bg-sky-500 dark:bg-sky-400"></span>
                {{ i18n.t('កំណត់ត្រាត្រួតពិនិត្យ', 'Audit Trail') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors">
              {{ i18n.t('ប្រវត្តិការចូលប្រព័ន្ធ', 'Login History') }}
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('ពិនិត្យមើលកំណត់ត្រា Logins ឧបករណ៍ IP Address និងទាញយករបាយការណ៍', 'Review login logs, device info, IP addresses, and export reports') }}
            </p>

            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/70 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ចូលប្រព័ន្ធថ្ងៃនេះ:', 'Logged In Today:') }}</span>
                <span class="font-bold text-sky-600 dark:text-sky-300 font-sans text-xs bg-sky-50 dark:bg-sky-500/15 px-2.5 py-0.5 rounded-full border border-sky-200/80 dark:border-sky-500/30">
                  {{ stats.login_history_today.toLocaleString() }} {{ i18n.t('កំណត់ត្រា', 'Entries') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ស្ថានភាពទូទៅ:', 'Overall Status:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">
                  {{ i18n.t('ធម្មតាភាគច្រើន', 'Mostly Normal') }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('ពិនិត្យកំណត់ត្រាចូលប្រព័ន្ធ', 'View Login Logs') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 4. Failed Login Attempts Card -->
        <Link
          href="/admin/auth/history?filter=failed"
          class="relative overflow-hidden bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-xs backdrop-blur-xl h-full cursor-pointer"
        >
          <div>
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#F59E0B] to-[#EA580C] text-white flex items-center justify-center shrink-0 shadow-md shadow-orange-500/20 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                  <line x1="12" y1="9" x2="12" y2="13"/>
                  <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
              </div>
              <span
                class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full border"
                :class="stats.failed_attempts_today > 0 ? 'text-orange-700 dark:text-orange-300 bg-orange-50 dark:bg-orange-500/15 border-orange-200/80 dark:border-orange-500/30' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700'"
              >
                <span class="w-2 h-2 rounded-full" :class="stats.failed_attempts_today > 0 ? 'bg-orange-500 animate-pulse' : 'bg-slate-400'"></span>
                {{ i18n.t('ការជូនដំណឹងហានិភ័យ', 'Threat Alert') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors">
              {{ i18n.t('ការប៉ុនប៉ងចូលមិនជោគជ័យ', 'Failed Attempts & Lockouts') }}
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('បញ្ចូលក្នុង Login History: ស្វែងរកការប៉ុនប៉ង Brute-force ដោះសោរគណនី និងគ្រប់គ្រង IP Blacklist', 'Merged in Login History: Detect brute-force attempts, unlock accounts, and manage firewall blacklisted IPs') }}
            </p>

            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/70 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('បរាជ័យថ្ងៃនេះ:', 'Failed Today:') }}</span>
                <span class="font-bold text-orange-600 dark:text-orange-300 font-sans text-xs bg-orange-50 dark:bg-orange-500/15 px-2.5 py-0.5 rounded-full border border-orange-200/80 dark:border-orange-500/30">
                  {{ stats.failed_attempts_today }} {{ i18n.t('ដង', 'Attempts') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('បានទប់ស្កាត់ IP:', 'Firewall Banned:') }}</span>
                <span class="font-bold text-amber-600 dark:text-amber-300 font-sans text-xs bg-amber-50 dark:bg-amber-500/15 px-2.5 py-0.5 rounded-full border border-amber-200/80 dark:border-amber-500/30">
                  {{ stats.banned_ips_count }} {{ i18n.t('IPs', 'IPs Banned') }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('ពិនិត្យ Failed Attempts ក្នុង Login History', 'Review in Login History') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 5. Security Policies Card -->
        <Link
          href="/admin/auth/policies"
          class="relative overflow-hidden bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-xs backdrop-blur-xl h-full cursor-pointer"
        >
          <div>
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#8B5CF6] to-[#EC4899] text-white flex items-center justify-center shrink-0 shadow-md shadow-purple-500/20 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-purple-700 dark:text-purple-300 font-bold bg-purple-50 dark:bg-purple-500/15 px-3 py-1 rounded-full border border-purple-200/80 dark:border-purple-500/30">
                <span class="w-2 h-2 rounded-full bg-purple-500 dark:bg-purple-400"></span>
                {{ i18n.t('ពិន្ទុ:', 'Score:') }} {{ stats.security_score }}/100
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors">
              {{ i18n.t('គោលការណ៍សុវត្ថិភាពប្រព័ន្ធ', 'Security Policies') }}
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('គោលការណ៍ពាក្យសម្ងាត់ សុពលភាព Session Token ការចាក់សោរ និង SSL', 'Global password policy, token expiration, lockout rules & SSL configuration') }}
            </p>

            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/70 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('កម្រិតការពារ:', 'Security Posture:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 font-sans text-xs">
                  {{ stats.security_score }}/100 ({{ i18n.t('ល្អប្រសើរ', 'OPTIMAL') }})
                </span>
              </div>
              <!-- Mini Progress Bar -->
              <div class="w-full bg-slate-200/70 dark:bg-slate-700/60 h-2 rounded-full overflow-hidden p-0.5">
                <div
                  class="bg-gradient-to-r from-emerald-500 via-teal-400 to-[#007AFF] h-full rounded-full transition-all duration-500"
                  :style="{ width: stats.security_score + '%' }"
                ></div>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('កំណត់គោលការណ៍សុវត្ថិភាព', 'Configure Security Policies') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 6. Security Audit & Cyber Forensics Card -->
        <Link
          href="/admin/auth/cyber-security"
          class="relative overflow-hidden bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-xs backdrop-blur-xl h-full cursor-pointer"
        >
          <div>
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#F43F5E] to-[#BE123C] text-white flex items-center justify-center shrink-0 shadow-md shadow-rose-500/20 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m14.5 9-5 5"/>
                  <path d="m9.5 9 5 5"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-rose-700 dark:text-rose-300 font-bold bg-rose-50 dark:bg-rose-500/15 px-3 py-1 rounded-full border border-rose-200/80 dark:border-rose-500/30">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                {{ i18n.t('សវនកម្មសុវត្ថិភាព', 'Security Audit') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors">
              {{ i18n.t('សវនកម្មសុវត្ថិភាព & Cyber Forensics', 'Security Audit & Cyber Forensics') }}
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('អន្ទាក់ចាប់ការវាយប្រហារ (Honeypot) វិភាគគន្លង Incident និងភ្ជាប់ទៅកាន់ System Logs', 'Honeypot traps, threat forensics, automated incident logs & link to System Logs') }}
            </p>

            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800/70 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ប្រព័ន្ធការពារ:', 'Defense Shield:') }}</span>
                <span class="font-bold text-rose-600 dark:text-rose-400 font-sans text-xs bg-rose-50 dark:bg-rose-500/15 px-2.5 py-0.5 rounded-full border border-rose-200/80 dark:border-rose-500/30">
                  {{ i18n.t('ស្វ័យប្រវត្តិកម្ម', 'Active Defense') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('អន្ទាក់ Honeypot:', 'Honeypot Traps:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">
                  {{ i18n.t('ដំណើរការ 100%', 'Armed & Guarding') }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('បើកមជ្ឈមណ្ឌល Security Audit', 'Open Security Audit Center') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

      </div>
    </div>
  </AdminLayout>
</template>
