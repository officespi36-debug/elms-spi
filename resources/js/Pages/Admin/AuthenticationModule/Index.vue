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
  active_sessions_now: 0,
  login_history_today: 6,
  failed_attempts_today: 3,
  banned_ips_count: 0,
  security_score: 88
}
</script>

<template>
  <AdminLayout title="Authentication Module Overview">
    <div class="space-y-6 font-sans">
      <!-- Shared Navigation Header & Top Metrics -->
      <AuthModuleHeader activeTab="overview" :summaryStats="props.summaryStats" />

      <!-- Main Overview Cards Grid (All cards are fully clickable Link components with hover elevation) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- 1. Roles & Permissions Card -->
        <Link
          href="/admin/auth/roles"
          class="relative overflow-hidden bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-xl h-full cursor-pointer"
        >
          <!-- Card Top Accent Bar -->
          <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#7c3aed] to-[#6366f1]"></div>

          <div>
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#7c3aed] to-[#6366f1] text-white flex items-center justify-center shrink-0 shadow-md shadow-indigo-500/25 group-hover:scale-110 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="m9 12 2 2 4-4"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-indigo-700 dark:text-indigo-300 font-bold bg-indigo-50 dark:bg-indigo-500/15 px-3 py-1 rounded-full border border-indigo-200/80 dark:border-indigo-500/30">
                <span class="w-2 h-2 rounded-full bg-indigo-500 dark:bg-indigo-400"></span> {{ i18n.t('auth_rbac_active', 'RBAC Active') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ i18n.t('auth_card1_title', 'Roles & Permissions') }}</h3>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1.5 leading-snug">
              {{ i18n.t('auth_card1_desc', 'Manage roles and access permissions matrix for Admin, Teacher, and Student') }}
            </p>

            <div class="mt-5 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('auth_card1_stat1', 'Active Roles:') }}</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-300 font-mono text-xs bg-indigo-50 dark:bg-indigo-500/15 px-2.5 py-1 rounded-lg border border-indigo-200/80 dark:border-indigo-500/30">{{ stats.total_roles }} {{ i18n.t('auth_card1_roles_count', 'Roles') }}</span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('auth_card1_stat2', 'Total Permissions:') }}</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-300 font-mono text-xs bg-indigo-50 dark:bg-indigo-500/15 px-2.5 py-1 rounded-lg border border-indigo-200/80 dark:border-indigo-500/30">{{ stats.total_permissions }} {{ i18n.t('auth_card1_perms_count', 'Permissions') }}</span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-gradient-to-r from-indigo-50 to-indigo-100/80 group-hover:from-indigo-600 group-hover:to-indigo-500 text-indigo-700 group-hover:text-white dark:from-indigo-950/60 dark:to-indigo-900/60 dark:group-hover:from-indigo-600 dark:group-hover:to-indigo-500 dark:text-indigo-300 dark:group-hover:text-white border border-indigo-200/80 dark:border-indigo-500/30 group-hover:border-transparent shadow-xs group-hover:shadow-md group-hover:shadow-indigo-500/20 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('auth_card1_btn', 'Manage Roles & Permissions') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 2. Active Sessions Card -->
        <Link
          href="/admin/auth/sessions"
          class="relative overflow-hidden bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-xl h-full cursor-pointer"
        >
          <!-- Card Top Accent Bar -->
          <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#10b981] to-[#059669]"></div>

          <div>
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#10b981] to-[#059669] text-white flex items-center justify-center shrink-0 shadow-md shadow-emerald-500/25 group-hover:scale-110 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="20" height="14" x="2" y="3" rx="2"/>
                  <line x1="8" x2="16" y1="21" y2="21"/>
                  <line x1="12" x2="12" y1="17" y2="21"/>
                  <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-300 font-bold bg-emerald-50 dark:bg-emerald-500/15 px-3 py-1 rounded-full border border-emerald-200/80 dark:border-emerald-500/30">
                <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span> {{ i18n.t('auth_live_monitor', 'Live Monitor') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ i18n.t('auth_card2_title', 'Active Sessions') }}</h3>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1.5 leading-snug">
              {{ i18n.t('auth_card2_desc', 'Track live users and revoke active JWT session tokens') }}
            </p>

            <div class="mt-5 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('auth_card2_stat1', 'Sessions Online Now:') }}</span>
                <span
                  class="font-bold font-mono text-xs px-2.5 py-1 rounded-lg border"
                  :class="stats.active_sessions_now > 0 ? 'text-emerald-600 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/15 border-emerald-200/80 dark:border-emerald-500/30' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-900 border-slate-200 dark:border-slate-800'"
                >
                  {{ stats.active_sessions_now > 0 ? stats.active_sessions_now.toLocaleString() + ' ' + i18n.t('auth_card2_online', 'Online') : i18n.t('auth_card2_zero_session', '0 Active Sessions') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('auth_card2_stat2', 'Session Status:') }}</span>
                <span
                  class="font-bold text-xs"
                  :class="stats.active_sessions_now > 0 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-amber-600 dark:text-amber-400 font-bold'"
                >
                  {{ stats.active_sessions_now > 0 ? i18n.t('auth_card2_status_normal', 'Normal Active') : i18n.t('auth_card2_status_idle', 'Idle (No Sessions)') }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-gradient-to-r from-emerald-50 to-emerald-100/80 group-hover:from-emerald-600 group-hover:to-emerald-500 text-emerald-700 group-hover:text-white dark:from-emerald-950/60 dark:to-emerald-900/60 dark:group-hover:from-emerald-600 dark:group-hover:to-emerald-500 dark:text-emerald-300 dark:group-hover:text-white border border-emerald-200/80 dark:border-emerald-500/30 group-hover:border-transparent shadow-xs group-hover:shadow-md group-hover:shadow-emerald-500/20 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('auth_card2_btn', 'Monitor Active Sessions') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 3. Login History Card -->
        <Link
          href="/admin/auth/history"
          class="relative overflow-hidden bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-xl h-full cursor-pointer"
        >
          <!-- Card Top Accent Bar -->
          <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#0284c7] to-[#2563eb]"></div>

          <div>
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#0284c7] to-[#2563eb] text-white flex items-center justify-center shrink-0 shadow-md shadow-blue-500/25 group-hover:scale-110 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-sky-700 dark:text-sky-300 font-bold bg-sky-50 dark:bg-sky-500/15 px-3 py-1 rounded-full border border-sky-200/80 dark:border-sky-500/30">
                <span class="w-2 h-2 rounded-full bg-sky-500 dark:bg-sky-400"></span> {{ i18n.t('auth_tab_history', 'Login History') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">{{ i18n.t('auth_card3_title', 'Login History') }}</h3>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1.5 leading-snug">
              {{ i18n.t('auth_card3_desc', 'Review login logs, device info, IP addresses, and export reports') }}
            </p>

            <div class="mt-5 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('auth_card3_stat1', 'Logged In Today:') }}</span>
                <span class="font-bold text-sky-600 dark:text-sky-300 font-mono text-xs bg-sky-50 dark:bg-sky-500/15 px-2.5 py-1 rounded-lg border border-sky-200/80 dark:border-sky-500/30">{{ stats.login_history_today.toLocaleString() }} {{ i18n.t('auth_card3_entries', 'Entries') }}</span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('auth_card3_stat2', 'Overall Status:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">{{ i18n.t('auth_card3_mostly_normal', 'Mostly Normal') }}</span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-gradient-to-r from-sky-50 to-blue-100/80 group-hover:from-sky-600 group-hover:to-blue-600 text-sky-700 group-hover:text-white dark:from-sky-950/60 dark:to-blue-900/60 dark:group-hover:from-sky-600 dark:group-hover:to-blue-600 dark:text-sky-300 dark:group-hover:text-white border border-sky-200/80 dark:border-sky-500/30 group-hover:border-transparent shadow-xs group-hover:shadow-md group-hover:shadow-sky-500/20 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('auth_card3_btn', 'View Login Logs') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 4. Failed Login Attempts Card -->
        <Link
          href="/admin/auth/failed"
          class="relative overflow-hidden bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-xl h-full cursor-pointer"
        >
          <!-- Card Top Accent Bar -->
          <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#f43f5e] to-[#e11d48]"></div>

          <div>
            <div class="flex items-center justify-between">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#f43f5e] to-[#e11d48] text-white flex items-center justify-center shrink-0 shadow-md shadow-rose-500/25 group-hover:scale-110 transition-transform duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                  <line x1="12" y1="9" x2="12" y2="13"/>
                  <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
              </div>
              <span
                class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full border"
                :class="stats.failed_attempts_today > 0 ? 'text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-500/15 border-rose-200/80 dark:border-rose-500/30' : 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700'"
              >
                <span class="w-2 h-2 rounded-full" :class="stats.failed_attempts_today > 0 ? 'bg-rose-500 dark:bg-rose-400 animate-pulse' : 'bg-slate-400'"></span> {{ i18n.t('auth_tab_failed', 'Threat Alert') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">{{ i18n.t('auth_card4_title', 'Failed Login Attempts') }}</h3>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1.5 leading-snug">
              {{ i18n.t('auth_card4_desc', 'Detect brute-force attempts and manage firewall blacklisted IPs') }}
            </p>

            <div class="mt-5 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800/80 space-y-2.5 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('auth_card4_stat1', 'Failed Today:') }}</span>
                <span class="font-bold text-rose-600 dark:text-rose-300 font-mono text-xs bg-rose-50 dark:bg-rose-500/15 px-2.5 py-1 rounded-lg border border-rose-200/80 dark:border-rose-500/30">{{ stats.failed_attempts_today }} {{ i18n.t('auth_card4_attempts', 'Attempts') }}</span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('auth_card4_stat2', 'Firewall Blacklisted:') }}</span>
                <span class="font-bold text-amber-600 dark:text-amber-300 font-mono text-xs bg-amber-50 dark:bg-amber-500/15 px-2.5 py-1 rounded-lg border border-amber-200/80 dark:border-amber-500/30">{{ stats.banned_ips_count }} {{ i18n.t('auth_card4_banned', 'IPs Banned') }}</span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-gradient-to-r from-rose-50 to-red-100/80 group-hover:from-rose-600 group-hover:to-red-600 text-rose-700 group-hover:text-white dark:from-rose-950/60 dark:to-red-900/60 dark:group-hover:from-rose-600 dark:group-hover:to-red-600 dark:text-rose-300 dark:group-hover:text-white border border-rose-200/80 dark:border-rose-500/30 group-hover:border-transparent shadow-xs group-hover:shadow-md group-hover:shadow-rose-500/20 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('auth_card4_btn', 'Investigate Threat Logs') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>

        <!-- 5. Security Policies Card (Spans 2 columns on large screens for balanced grid) -->
        <Link
          href="/admin/auth/policies"
          class="lg:col-span-2 relative overflow-hidden bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-6 hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group shadow-sm backdrop-blur-xl h-full cursor-pointer"
        >
          <!-- Card Top Accent Bar -->
          <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#8b5cf6] via-[#a855f7] to-[#ec4899]"></div>

          <div>
            <div class="flex items-center justify-between flex-wrap gap-3">
              <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#8b5cf6] to-[#6366f1] text-white flex items-center justify-center shrink-0 shadow-md shadow-purple-500/25 group-hover:scale-110 transition-transform duration-200">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">{{ i18n.t('auth_card5_title', 'System Security Policies') }}</h3>
                  <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1 leading-snug">{{ i18n.t('auth_card5_desc', 'Global password policy, token expiration, lockout rules & SSL configuration') }}</p>
                </div>
              </div>

              <span class="px-3.5 py-1.5 text-xs font-extrabold bg-purple-50 dark:bg-purple-500/20 text-purple-700 dark:text-purple-200 border border-purple-200/90 dark:border-purple-500/30 rounded-xl font-mono shadow-xs">
                {{ i18n.t('auth_card5_score', 'Score:') }} {{ stats.security_score }}/100
              </span>
            </div>

            <!-- Security Score Bar -->
            <div class="mt-5 space-y-2">
              <div class="flex justify-between text-xs font-bold text-slate-700 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-semibold">{{ i18n.t('auth_card5_posture', 'Overall Security Posture:') }}</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-extrabold font-mono">{{ stats.security_score }}/100 ({{ i18n.t('auth_card5_good', 'GOOD') }})</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-950 h-3 rounded-full overflow-hidden border border-slate-200 dark:border-slate-800 p-0.5 shadow-inner">
                <div
                  class="bg-gradient-to-r from-emerald-500 via-teal-400 to-indigo-500 h-full rounded-full transition-all duration-500 shadow-xs"
                  :style="{ width: stats.security_score + '%' }"
                ></div>
              </div>
            </div>

            <!-- Shield Feature Matrix -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100 dark:border-slate-800/80 text-xs">
              <div class="px-3.5 py-2.5 bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-semibold"><span class="text-emerald-500 dark:text-emerald-400 font-black">✓</span> {{ i18n.t('auth_matrix_pwd', 'Password Policy') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/25 font-mono font-bold">{{ i18n.t('auth_matrix_pwd_status', 'Strong') }}</span>
              </div>
              <div class="px-3.5 py-2.5 bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-semibold"><span class="text-emerald-500 dark:text-emerald-400 font-black">✓</span> {{ i18n.t('auth_matrix_jwt', 'JWT Tokens') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/25 font-mono font-bold">{{ i18n.t('auth_matrix_jwt_status', 'Active') }}</span>
              </div>
              <div class="px-3.5 py-2.5 bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-semibold"><span class="text-emerald-500 dark:text-emerald-400 font-black">✓</span> {{ i18n.t('auth_matrix_2fa', 'Admin 2FA') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-lg bg-purple-50 dark:bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/25 font-mono font-bold">{{ i18n.t('auth_matrix_2fa_status', 'Enforced') }}</span>
              </div>
              <div class="px-3.5 py-2.5 bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-semibold"><span class="text-emerald-500 dark:text-emerald-400 font-black">✓</span> {{ i18n.t('auth_matrix_timeout', 'Session Timeout') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/25 font-mono font-bold">{{ i18n.t('auth_matrix_timeout_status', '15m Idle') }}</span>
              </div>
              <div class="px-3.5 py-2.5 bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-semibold"><span class="text-emerald-500 dark:text-emerald-400 font-black">✓</span> {{ i18n.t('auth_matrix_ssl', 'HTTPS / SSL') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-lg bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-500/25 font-mono font-bold">{{ i18n.t('auth_matrix_ssl_status', 'TLS 1.3 Active') }}</span>
              </div>
              <div class="px-3.5 py-2.5 bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-semibold"><span class="text-emerald-500 dark:text-emerald-400 font-black">✓</span> {{ i18n.t('auth_matrix_csrf', 'CSRF & Rate Limit') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/25 font-mono font-bold">{{ i18n.t('auth_matrix_csrf_status', 'Protected') }}</span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <div
              class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-gradient-to-r from-purple-50 to-indigo-100/80 group-hover:from-purple-600 group-hover:to-indigo-600 text-purple-700 group-hover:text-white dark:from-purple-950/60 dark:to-indigo-900/60 dark:group-hover:from-purple-600 dark:group-hover:to-indigo-600 dark:text-purple-300 dark:group-hover:text-white border border-purple-200/80 dark:border-purple-500/30 group-hover:border-transparent shadow-xs group-hover:shadow-md group-hover:shadow-purple-500/20 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span>{{ i18n.t('auth_card5_btn', 'Configure Security Policies') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </div>
          </div>
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>


