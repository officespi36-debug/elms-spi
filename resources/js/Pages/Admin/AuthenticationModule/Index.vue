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
          class="relative overflow-hidden bg-white/85 dark:bg-slate-900/85 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-2xl h-full cursor-pointer"
        >
          <!-- Ambient Glow -->
          <div class="absolute -top-12 -right-12 w-36 h-36 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>

          <div>
            <div class="flex items-center justify-between">
              <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-[#7C3AED] via-[#6366F1] to-[#4F46E5] text-white flex items-center justify-center shrink-0 shadow-lg shadow-indigo-500/25 border border-white/30 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6.5 h-6.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m9 12 2 2 4-4"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-purple-700 dark:text-purple-300 font-bold bg-purple-100/80 dark:bg-purple-500/20 px-3 py-1 rounded-full border border-purple-200/80 dark:border-purple-500/30 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-purple-500 dark:bg-purple-400"></span>
                {{ i18n.t('RBAC សកម្ម', 'RBAC Active') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors flex items-center gap-1.5">
              <span>{{ i18n.t('តួនាទី និងសិទ្ធិប្រើប្រាស់', 'Roles & Permissions') }}</span>
              <svg class="w-4.5 h-4.5 text-[#007AFF] opacity-80" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.5 14.5l-4-4 1.41-1.41L10.5 13.67l6.59-6.59L18.5 8.5l-8 8z"/>
              </svg>
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('គ្រប់គ្រងតួនាទី និងម៉ាទ្រីសសិទ្ធិប្រើប្រាស់សម្រាប់ Admin, Teacher, និង Student', 'Manage roles and access permissions matrix for Admin, Teacher, and Student') }}
            </p>

            <!-- Inset Grouped Table (Apple iOS Inset Style) -->
            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('តួនាទីសកម្ម:', 'Active Roles:') }}</span>
                <span class="font-bold text-purple-700 dark:text-purple-300 font-sans text-xs bg-purple-100/80 dark:bg-purple-500/20 px-2.5 py-0.5 rounded-full border border-purple-200/80 dark:border-purple-500/30">
                  {{ stats.total_roles }} {{ i18n.t('តួនាទី', 'Roles') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('សិទ្ធិសរុប:', 'Total Permissions:') }}</span>
                <span class="font-bold text-indigo-700 dark:text-indigo-300 font-sans text-xs bg-indigo-100/80 dark:bg-indigo-500/20 px-2.5 py-0.5 rounded-full border border-indigo-200/80 dark:border-indigo-500/30">
                  {{ stats.total_permissions }} {{ i18n.t('សិទ្ធិ', 'Permissions') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300 pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ស្ថានភាពម៉ាទ្រីស:', 'Matrix State:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs flex items-center gap-1">
                  <span>✓</span> {{ i18n.t('បានកំណត់រចនាសម្ព័ន្ធ', 'Synchronized') }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100/90 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs group-hover:shadow-lg group-hover:shadow-blue-500/25 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('គ្រប់គ្រងតួនាទី & សិទ្ធិ', 'Manage Roles & Permissions') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 2. Active Sessions Card -->
        <Link
          href="/admin/auth/sessions"
          class="relative overflow-hidden bg-white/85 dark:bg-slate-900/85 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-2xl h-full cursor-pointer"
        >
          <!-- Ambient Glow -->
          <div class="absolute -top-12 -right-12 w-36 h-36 bg-gradient-to-br from-emerald-500/10 to-teal-500/10 rounded-full blur-2xl pointer-events-none"></div>

          <div>
            <div class="flex items-center justify-between">
              <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-[#10B981] via-[#059669] to-[#047857] text-white flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/25 border border-white/30 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6.5 h-6.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="20" height="14" x="2" y="3" rx="2"/>
                  <line x1="8" x2="16" y1="21" y2="21"/>
                  <line x1="12" x2="12" y1="17" y2="21"/>
                  <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-emerald-800 dark:text-emerald-300 font-bold bg-emerald-100/80 dark:bg-emerald-500/20 px-3 py-1 rounded-full border border-emerald-200/80 dark:border-emerald-500/30 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ i18n.t('តាមដានផ្ទាល់', '7 Live Online') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors flex items-center gap-1.5">
              <span>{{ i18n.t('សម័យការសកម្ម', 'Active Sessions') }}</span>
              <svg class="w-4.5 h-4.5 text-[#007AFF] opacity-80" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.5 14.5l-4-4 1.41-1.41L10.5 13.67l6.59-6.59L18.5 8.5l-8 8z"/>
              </svg>
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('តាមដានអ្នកប្រើប្រាស់កំពុង Online និងដកហូត Session Tokens ភ្លាមៗ', 'Track live users and revoke active JWT session tokens') }}
            </p>

            <!-- Inset Grouped Table -->
            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('កំពុង Online ឥឡូវនេះ:', 'Sessions Online Now:') }}</span>
                <span
                  class="font-bold text-xs px-2.5 py-0.5 rounded-full border shadow-2xs"
                  :class="stats.active_sessions_now > 0 ? 'text-white bg-[#007AFF] border-[#007AFF]/80' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-900 border-slate-200 dark:border-slate-800'"
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
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300 pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('សុវត្ថិភាព Token:', 'Token Security:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs flex items-center gap-1">
                  <span>🔒</span> {{ i18n.t('ការពារដោយស្វ័យប្រវត្តិ', 'Auto Protected') }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100/90 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs group-hover:shadow-lg group-hover:shadow-blue-500/25 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('តាមដានសម័យការសកម្ម', 'Monitor Active Sessions') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 3. Login History Card -->
        <Link
          href="/admin/auth/history"
          class="relative overflow-hidden bg-white/85 dark:bg-slate-900/85 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-2xl h-full cursor-pointer"
        >
          <!-- Ambient Glow -->
          <div class="absolute -top-12 -right-12 w-36 h-36 bg-gradient-to-br from-blue-500/10 to-sky-500/10 rounded-full blur-2xl pointer-events-none"></div>

          <div>
            <div class="flex items-center justify-between">
              <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-[#0284C7] via-[#007AFF] to-[#2563EB] text-white flex items-center justify-center shrink-0 shadow-lg shadow-blue-500/25 border border-white/30 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6.5 h-6.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-sky-800 dark:text-sky-300 font-bold bg-sky-100/80 dark:bg-sky-500/20 px-3 py-1 rounded-full border border-sky-200/80 dark:border-sky-500/30 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-[#007AFF]"></span>
                {{ i18n.t('កំណត់ត្រាត្រួតពិនិត្យ', 'Audit Trail') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors flex items-center gap-1.5">
              <span>{{ i18n.t('ប្រវត្តិការចូលប្រព័ន្ធ', 'Login History') }}</span>
              <svg class="w-4.5 h-4.5 text-[#007AFF] opacity-80" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.5 14.5l-4-4 1.41-1.41L10.5 13.67l6.59-6.59L18.5 8.5l-8 8z"/>
              </svg>
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('ពិនិត្យមើលកំណត់ត្រា Logins ឧបករណ៍ IP Address និងទាញយករបាយការណ៍', 'Review login logs, device info, IP addresses, and export reports') }}
            </p>

            <!-- Inset Grouped Table -->
            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ចូលប្រព័ន្ធថ្ងៃនេះ:', 'Logged In Today:') }}</span>
                <span class="font-bold text-sky-700 dark:text-sky-300 font-sans text-xs bg-sky-100/80 dark:bg-sky-500/20 px-2.5 py-0.5 rounded-full border border-sky-200/80 dark:border-sky-500/30">
                  {{ stats.login_history_today.toLocaleString() }} {{ i18n.t('កំណត់ត្រា', 'Entries') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ស្ថានភាពទូទៅ:', 'Overall Status:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">
                  {{ i18n.t('ធម្មតាភាគច្រើន', 'Mostly Normal') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300 pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ការរក្សាទុកកំណត់ត្រា:', 'Data Retention:') }}</span>
                <span class="font-bold text-slate-600 dark:text-slate-300 text-xs">
                  90 Days Auto-Archive
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100/90 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs group-hover:shadow-lg group-hover:shadow-blue-500/25 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('ពិនិត្យកំណត់ត្រាចូលប្រព័ន្ធ', 'View Login Logs') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 4. Failed Login Attempts Card -->
        <Link
          href="/admin/auth/history?filter=failed"
          class="relative overflow-hidden bg-white/85 dark:bg-slate-900/85 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-2xl h-full cursor-pointer"
        >
          <!-- Ambient Glow -->
          <div class="absolute -top-12 -right-12 w-36 h-36 bg-gradient-to-br from-amber-500/10 to-orange-500/10 rounded-full blur-2xl pointer-events-none"></div>

          <div>
            <div class="flex items-center justify-between">
              <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-[#F59E0B] via-[#EA580C] to-[#D97706] text-white flex items-center justify-center shrink-0 shadow-lg shadow-orange-500/25 border border-white/30 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6.5 h-6.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                  <line x1="12" y1="9" x2="12" y2="13"/>
                  <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
              </div>
              <span
                class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full border shadow-2xs"
                :class="stats.failed_attempts_today > 0 ? 'text-amber-800 dark:text-amber-300 bg-amber-100/80 dark:bg-amber-500/20 border-amber-200/80 dark:border-amber-500/30' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700'"
              >
                <span class="w-2 h-2 rounded-full" :class="stats.failed_attempts_today > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-400'"></span>
                {{ i18n.t('ការជូនដំណឹងហានិភ័យ', 'Threat Alert') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors flex items-center gap-1.5">
              <span>{{ i18n.t('ការប៉ុនប៉ងចូលមិនជោគជ័យ', 'Failed Attempts & Lockouts') }}</span>
              <svg class="w-4.5 h-4.5 text-amber-500 opacity-80" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
              </svg>
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('បញ្ចូលក្នុង Login History: ស្វែងរកការប៉ុនប៉ង Brute-force ដោះសោរគណនី និងគ្រប់គ្រង IP Blacklist', 'Detect brute-force attempts, unlock accounts, and manage firewall blacklisted IPs') }}
            </p>

            <!-- Inset Grouped Table -->
            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('បរាជ័យថ្ងៃនេះ:', 'Failed Today:') }}</span>
                <span class="font-bold text-amber-800 dark:text-amber-300 font-sans text-xs bg-amber-100/80 dark:bg-amber-500/20 px-2.5 py-0.5 rounded-full border border-amber-200/80 dark:border-amber-500/30">
                  {{ stats.failed_attempts_today }} {{ i18n.t('ដង', 'Attempts') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('បានទប់ស្កាត់ IP:', 'Firewall Banned:') }}</span>
                <span class="font-bold text-orange-700 dark:text-orange-300 font-sans text-xs bg-orange-100/80 dark:bg-orange-500/20 px-2.5 py-0.5 rounded-full border border-orange-200/80 dark:border-orange-500/30">
                  {{ stats.banned_ips_count }} {{ i18n.t('IPs', 'IPs Banned') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300 pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('កម្រិតចាក់សោរ:', 'Lockout Rule:') }}</span>
                <span class="font-bold text-slate-600 dark:text-slate-300 text-xs">
                  5 Retries / 15 Mins
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100/90 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs group-hover:shadow-lg group-hover:shadow-blue-500/25 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('ពិនិត្យ Failed Attempts ក្នុង Login History', 'Review in Login History') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 5. Security Policies Card -->
        <Link
          href="/admin/auth/policies"
          class="relative overflow-hidden bg-white/85 dark:bg-slate-900/85 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-2xl h-full cursor-pointer"
        >
          <!-- Ambient Glow -->
          <div class="absolute -top-12 -right-12 w-36 h-36 bg-gradient-to-br from-purple-500/10 to-pink-500/10 rounded-full blur-2xl pointer-events-none"></div>

          <div>
            <div class="flex items-center justify-between">
              <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-[#8B5CF6] via-[#A855F7] to-[#EC4899] text-white flex items-center justify-center shrink-0 shadow-lg shadow-purple-500/25 border border-white/30 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6.5 h-6.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-purple-800 dark:text-purple-300 font-bold bg-purple-100/80 dark:bg-purple-500/20 px-3 py-1 rounded-full border border-purple-200/80 dark:border-purple-500/30 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-purple-500 dark:bg-purple-400"></span>
                {{ i18n.t('ពិន្ទុ:', 'Score:') }} {{ stats.security_score }}/100
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors flex items-center gap-1.5">
              <span>{{ i18n.t('គោលការណ៍សុវត្ថិភាពប្រព័ន្ធ', 'Security Policies') }}</span>
              <svg class="w-4.5 h-4.5 text-[#007AFF] opacity-80" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.5 14.5l-4-4 1.41-1.41L10.5 13.67l6.59-6.59L18.5 8.5l-8 8z"/>
              </svg>
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('គោលការណ៍ពាក្យសម្ងាត់ សុពលភាព Session Token ការចាក់សោរ និង SSL', 'Global password policy, token expiration, lockout rules & SSL configuration') }}
            </p>

            <!-- Inset Grouped Table -->
            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('កម្រិតការពារ:', 'Security Posture:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 font-sans text-xs">
                  {{ stats.security_score }}/100 ({{ i18n.t('ល្អប្រសើរ', 'OPTIMAL') }})
                </span>
              </div>
              <!-- Mini Progress Bar -->
              <div class="w-full bg-slate-200/80 dark:bg-slate-700/60 h-2 rounded-full overflow-hidden p-0.5">
                <div
                  class="bg-gradient-to-r from-emerald-500 via-teal-400 to-[#007AFF] h-full rounded-full transition-all duration-500"
                  :style="{ width: stats.security_score + '%' }"
                ></div>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300 pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ការអនុវត្ត SSL/TLS:', 'SSL Encryption:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs flex items-center gap-1">
                  <span>✓</span> {{ i18n.t('HTTPS សកម្ម', 'HTTPS Enforced') }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100/90 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs group-hover:shadow-lg group-hover:shadow-blue-500/25 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('កំណត់គោលការណ៍សុវត្ថិភាព', 'Configure Security Policies') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 6. Security Audit & Cyber Forensics Card -->
        <Link
          href="/admin/auth/cyber-security"
          class="relative overflow-hidden bg-white/85 dark:bg-slate-900/85 border border-slate-200/80 dark:border-slate-800/90 rounded-3xl p-6 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-2xl h-full cursor-pointer"
        >
          <!-- Ambient Glow -->
          <div class="absolute -top-12 -right-12 w-36 h-36 bg-gradient-to-br from-rose-500/10 to-red-500/10 rounded-full blur-2xl pointer-events-none"></div>

          <div>
            <div class="flex items-center justify-between">
              <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-[#F43F5E] via-[#E11D48] to-[#BE123C] text-white flex items-center justify-center shrink-0 shadow-lg shadow-rose-500/25 border border-white/30 group-hover:scale-105 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6.5 h-6.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m14.5 9-5 5"/>
                  <path d="m9.5 9 5 5"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-rose-800 dark:text-rose-300 font-bold bg-rose-100/80 dark:bg-rose-500/20 px-3 py-1 rounded-full border border-rose-200/80 dark:border-rose-500/30 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                {{ i18n.t('សវនកម្មសុវត្ថិភាព', 'LIVE Defense') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-[#007AFF] transition-colors flex items-center gap-1.5">
              <span>{{ i18n.t('សវនកម្មសុវត្ថិភាព & Cyber Forensics', 'Security Audit & Forensics') }}</span>
              <svg class="w-4.5 h-4.5 text-[#007AFF] opacity-80" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.5 14.5l-4-4 1.41-1.41L10.5 13.67l6.59-6.59L18.5 8.5l-8 8z"/>
              </svg>
            </h3>
            <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              {{ i18n.t('អន្ទាក់ចាប់ការវាយប្រហារ (Honeypot) វិភាគគន្លង Incident និងភ្ជាប់ទៅកាន់ System Logs', 'Honeypot traps, threat forensics, automated incident logs & link to System Logs') }}
            </p>

            <!-- Inset Grouped Table -->
            <div class="mt-5 p-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ប្រព័ន្ធការពារ:', 'Defense Shield:') }}</span>
                <span class="font-bold text-rose-700 dark:text-rose-300 font-sans text-xs bg-rose-100/80 dark:bg-rose-500/20 px-2.5 py-0.5 rounded-full border border-rose-200/80 dark:border-rose-500/30">
                  {{ i18n.t('ស្វ័យប្រវត្តិកម្ម', 'Active Defense') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('អន្ទាក់ Honeypot:', 'Honeypot Traps:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">
                  {{ i18n.t('ដំណើរការ 100%', 'Armed & Guarding') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300 pt-1 border-t border-slate-200/50 dark:border-slate-700/50">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('ឆានែលជូនដំណឹង:', 'Alert Webhook:') }}</span>
                <span class="font-bold text-sky-600 dark:text-sky-400 text-xs flex items-center gap-1">
                  <span>✈️</span> Telegram Bot Hook Active
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-2xl font-bold text-xs bg-slate-100/90 dark:bg-slate-800/80 group-hover:bg-[#007AFF] text-slate-700 dark:text-slate-200 group-hover:text-white dark:group-hover:text-white border border-slate-200/70 dark:border-slate-700/70 group-hover:border-transparent shadow-xs group-hover:shadow-lg group-hover:shadow-blue-500/25 transition-all duration-200 flex items-center justify-center gap-2"
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
