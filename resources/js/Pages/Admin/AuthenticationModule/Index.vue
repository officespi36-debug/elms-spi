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

      <!-- Main Overview 5 Cards Grid (Balanced equal-height card layouts) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- 1. Roles & Permissions Card -->
        <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 backdrop-blur-xl hover:border-indigo-500/40 transition-all flex flex-col justify-between group shadow-sm dark:shadow-xl h-full">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-11 h-11 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/15 border border-indigo-200 dark:border-indigo-500/30 flex items-center justify-center shrink-0 shadow-inner">
                <img :src="'/images/nav/sub/roles.svg'" alt="Roles" class="w-6 h-6 object-contain" />
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-300 font-semibold bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-200 dark:border-indigo-500/25">
                <span class="w-2 h-2 rounded-full bg-indigo-500 dark:bg-indigo-400"></span> {{ i18n.t('auth_rbac_active', 'RBAC Active') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight">{{ i18n.t('auth_card1_title', 'Roles & Permissions') }}</h3>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1.5 leading-snug">
              {{ i18n.t('auth_card1_desc', 'Manage roles and access permissions matrix for Admin, Teacher, and Student') }}
            </p>

            <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800/80 space-y-2 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-medium">{{ i18n.t('auth_card1_stat1', 'Active Roles:') }}</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-300 font-mono text-sm bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-500/20">{{ stats.total_roles }} {{ i18n.t('auth_card1_roles_count', 'Roles') }}</span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-medium">{{ i18n.t('auth_card1_stat2', 'Total Permissions:') }}</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-300 font-mono text-sm bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-200 dark:border-indigo-500/20">{{ stats.total_permissions }} {{ i18n.t('auth_card1_perms_count', 'Permissions') }}</span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <Link
              href="/admin/auth/roles"
              class="w-full py-2.5 px-4 bg-slate-50 hover:bg-indigo-50 dark:bg-slate-900/60 dark:hover:bg-indigo-600/20 text-indigo-600 dark:text-indigo-300 hover:text-indigo-700 dark:hover:text-indigo-200 border border-indigo-200 dark:border-indigo-500/30 hover:border-indigo-400 dark:hover:border-indigo-500/60 font-semibold text-xs rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-xs"
            >
              <span>{{ i18n.t('auth_card1_btn', 'Manage Roles & Permissions') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </Link>
          </div>
        </div>

        <!-- 2. Active Sessions Card -->
        <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 backdrop-blur-xl hover:border-emerald-500/40 transition-all flex flex-col justify-between group shadow-sm dark:shadow-xl h-full">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-11 h-11 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/15 border border-emerald-200 dark:border-emerald-500/30 flex items-center justify-center shrink-0 shadow-inner">
                <img :src="'/images/nav/sub/sessions.svg'" alt="Sessions" class="w-6 h-6 object-contain" />
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-300 font-semibold bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-200 dark:border-emerald-500/25">
                <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span> {{ i18n.t('auth_live_monitor', 'Live Monitor') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight">{{ i18n.t('auth_card2_title', 'Active Sessions') }}</h3>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1.5 leading-snug">
              {{ i18n.t('auth_card2_desc', 'Track live users and revoke active JWT session tokens') }}
            </p>

            <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800/80 space-y-2 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-medium">{{ i18n.t('auth_card2_stat1', 'Sessions Online Now:') }}</span>
                <span
                  class="font-bold font-mono text-sm px-2 py-0.5 rounded border"
                  :class="stats.active_sessions_now > 0 ? 'text-emerald-600 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-900 border-slate-200 dark:border-slate-800'"
                >
                  {{ stats.active_sessions_now > 0 ? stats.active_sessions_now.toLocaleString() + ' ' + i18n.t('auth_card2_online', 'Online') : i18n.t('auth_card2_zero_session', '0 Active Sessions') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-medium">{{ i18n.t('auth_card2_stat2', 'Session Status:') }}</span>
                <span
                  class="font-bold text-xs"
                  :class="stats.active_sessions_now > 0 ? 'text-emerald-600 dark:text-emerald-300 font-semibold' : 'text-amber-600 dark:text-amber-300/90 font-semibold'"
                >
                  {{ stats.active_sessions_now > 0 ? i18n.t('auth_card2_status_normal', 'Normal Active') : i18n.t('auth_card2_status_idle', 'Idle (No Sessions)') }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <Link
              href="/admin/auth/sessions"
              class="w-full py-2.5 px-4 bg-slate-50 hover:bg-emerald-50 dark:bg-slate-900/60 dark:hover:bg-emerald-600/20 text-emerald-600 dark:text-emerald-300 hover:text-emerald-700 dark:hover:text-emerald-200 border border-emerald-200 dark:border-emerald-500/30 hover:border-emerald-400 dark:hover:border-emerald-500/60 font-semibold text-xs rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-xs"
            >
              <span>{{ i18n.t('auth_card2_btn', 'Monitor Active Sessions') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </Link>
          </div>
        </div>

        <!-- 3. Login History Card -->
        <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 backdrop-blur-xl hover:border-sky-500/40 transition-all flex flex-col justify-between group shadow-sm dark:shadow-xl h-full">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-11 h-11 rounded-xl bg-sky-500/10 dark:bg-sky-500/15 border border-sky-200 dark:border-sky-500/30 flex items-center justify-center shrink-0 shadow-inner">
                <img :src="'/images/nav/sub/history.svg'" alt="History" class="w-6 h-6 object-contain" />
              </div>
              <span class="inline-flex items-center gap-1.5 text-xs text-sky-600 dark:text-sky-300 font-semibold bg-sky-50 dark:bg-sky-500/10 px-3 py-1 rounded-full border border-sky-200 dark:border-sky-500/25">
                <span class="w-2 h-2 rounded-full bg-sky-500 dark:bg-sky-400"></span> {{ i18n.t('auth_tab_history', 'Login History') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight">{{ i18n.t('auth_card3_title', 'Login History') }}</h3>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1.5 leading-snug">
              {{ i18n.t('auth_card3_desc', 'Review login logs, device info, IP addresses, and export reports') }}
            </p>

            <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800/80 space-y-2 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-medium">{{ i18n.t('auth_card3_stat1', 'Logged In Today:') }}</span>
                <span class="font-bold text-sky-600 dark:text-sky-300 font-mono text-sm bg-sky-50 dark:bg-sky-500/10 px-2 py-0.5 rounded border border-sky-200 dark:border-sky-500/20">{{ stats.login_history_today.toLocaleString() }} {{ i18n.t('auth_card3_entries', 'Entries') }}</span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-medium">{{ i18n.t('auth_card3_stat2', 'Overall Status:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">{{ i18n.t('auth_card3_mostly_normal', 'Mostly Normal') }}</span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <Link
              href="/admin/auth/history"
              class="w-full py-2.5 px-4 bg-slate-50 hover:bg-sky-50 dark:bg-slate-900/60 dark:hover:bg-sky-600/20 text-sky-600 dark:text-sky-300 hover:text-sky-700 dark:hover:text-sky-200 border border-sky-200 dark:border-sky-500/30 hover:border-sky-400 dark:hover:border-sky-500/60 font-semibold text-xs rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-xs"
            >
              <span>{{ i18n.t('auth_card3_btn', 'View Login Logs') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </Link>
          </div>
        </div>

        <!-- 4. Failed Login Attempts Card -->
        <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 backdrop-blur-xl hover:border-rose-500/40 transition-all flex flex-col justify-between group shadow-sm dark:shadow-xl h-full">
          <div>
            <div class="flex items-center justify-between">
              <div class="w-11 h-11 rounded-xl bg-rose-500/10 dark:bg-rose-500/15 border border-rose-200 dark:border-rose-500/30 flex items-center justify-center shrink-0 shadow-inner">
                <img :src="'/images/nav/sub/failed.svg'" alt="Failed Attempts" class="w-6 h-6 object-contain" />
              </div>
              <span
                class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full border"
                :class="stats.failed_attempts_today > 0 ? 'text-rose-600 dark:text-rose-300 bg-rose-50 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/25' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700'"
              >
                <span class="w-2 h-2 rounded-full" :class="stats.failed_attempts_today > 0 ? 'bg-rose-500 dark:bg-rose-400 animate-pulse' : 'bg-slate-400'"></span> {{ i18n.t('auth_tab_failed', 'Threat Alert') }}
              </span>
            </div>

            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-4 tracking-tight">{{ i18n.t('auth_card4_title', 'Failed Login Attempts') }}</h3>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1.5 leading-snug">
              {{ i18n.t('auth_card4_desc', 'Detect brute-force attempts and manage firewall blacklisted IPs') }}
            </p>

            <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800/80 space-y-2 text-xs">
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-medium">{{ i18n.t('auth_card4_stat1', 'Failed Today:') }}</span>
                <span class="font-bold text-rose-600 dark:text-rose-300 font-mono text-sm bg-rose-50 dark:bg-rose-500/10 px-2 py-0.5 rounded border border-rose-200 dark:border-rose-500/20">{{ stats.failed_attempts_today }} {{ i18n.t('auth_card4_attempts', 'Attempts') }}</span>
              </div>
              <div class="flex justify-between items-center text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-medium">{{ i18n.t('auth_card4_stat2', 'Firewall Blacklisted:') }}</span>
                <span class="font-bold text-amber-600 dark:text-amber-300 font-mono text-sm bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-500/20">{{ stats.banned_ips_count }} {{ i18n.t('auth_card4_banned', 'IPs Banned') }}</span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <Link
              href="/admin/auth/failed"
              class="w-full py-2.5 px-4 bg-slate-50 hover:bg-rose-50 dark:bg-slate-900/60 dark:hover:bg-rose-600/20 text-rose-600 dark:text-rose-300 hover:text-rose-700 dark:hover:text-rose-200 border border-rose-200 dark:border-rose-500/30 hover:border-rose-400 dark:hover:border-rose-500/60 font-semibold text-xs rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-xs"
            >
              <span>{{ i18n.t('auth_card4_btn', 'Investigate Threat Logs') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </Link>
          </div>
        </div>

        <!-- 5. Security Policies Card (Spans 2 columns on large screens for balanced grid) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 backdrop-blur-xl hover:border-purple-500/40 transition-all flex flex-col justify-between group shadow-sm dark:shadow-xl h-full">
          <div>
            <div class="flex items-center justify-between flex-wrap gap-3">
              <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-purple-500/10 dark:bg-purple-500/15 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center shrink-0 shadow-inner">
                  <img :src="'/images/nav/sub/policies.svg'" alt="Policies" class="w-6 h-6 object-contain" />
                </div>
                <div>
                  <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight">{{ i18n.t('auth_card5_title', 'System Security Policies') }}</h3>
                  <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1 leading-snug">{{ i18n.t('auth_card5_desc', 'Global password policy, token expiration, lockout rules & SSL configuration') }}</p>
                </div>
              </div>

              <span class="px-3.5 py-1.5 text-xs font-bold bg-purple-50 dark:bg-purple-500/20 text-purple-700 dark:text-purple-200 border border-purple-200 dark:border-purple-500/30 rounded-xl font-mono shadow-xs">
                {{ i18n.t('auth_card5_score', 'Score:') }} {{ stats.security_score }}/100
              </span>
            </div>

            <!-- Security Score Bar -->
            <div class="mt-5 space-y-2">
              <div class="flex justify-between text-xs font-semibold text-slate-600 dark:text-slate-300">
                <span class="text-slate-500 dark:text-slate-400 font-medium">{{ i18n.t('auth_card5_posture', 'Overall Security Posture:') }}</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-bold font-mono">{{ stats.security_score }}/100 ({{ i18n.t('auth_card5_good', 'GOOD') }})</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-950 h-2.5 rounded-full overflow-hidden border border-slate-200 dark:border-slate-800 p-0.5 shadow-inner">
                <div
                  class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500 shadow-xs"
                  :style="{ width: stats.security_score + '%' }"
                ></div>
              </div>
            </div>

            <!-- Shield Feature Matrix -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100 dark:border-slate-800/80 text-xs">
              <div class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-medium"><span class="text-emerald-500 dark:text-emerald-400 font-bold">✓</span> {{ i18n.t('auth_matrix_pwd', 'Password Policy') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/25 font-mono font-semibold">{{ i18n.t('auth_matrix_pwd_status', 'Strong') }}</span>
              </div>
              <div class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-medium"><span class="text-emerald-500 dark:text-emerald-400 font-bold">✓</span> {{ i18n.t('auth_matrix_jwt', 'JWT Tokens') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/25 font-mono font-semibold">{{ i18n.t('auth_matrix_jwt_status', 'Active') }}</span>
              </div>
              <div class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-medium"><span class="text-emerald-500 dark:text-emerald-400 font-bold">✓</span> {{ i18n.t('auth_matrix_2fa', 'Admin 2FA') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded bg-purple-50 dark:bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/25 font-mono font-semibold">{{ i18n.t('auth_matrix_2fa_status', 'Enforced') }}</span>
              </div>
              <div class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-medium"><span class="text-emerald-500 dark:text-emerald-400 font-bold">✓</span> {{ i18n.t('auth_matrix_timeout', 'Session Timeout') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/25 font-mono font-semibold">{{ i18n.t('auth_matrix_timeout_status', '15m Idle') }}</span>
              </div>
              <div class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-medium"><span class="text-emerald-500 dark:text-emerald-400 font-bold">✓</span> {{ i18n.t('auth_matrix_ssl', 'HTTPS / SSL') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-500/25 font-mono font-semibold">{{ i18n.t('auth_matrix_ssl_status', 'TLS 1.3 Active') }}</span>
              </div>
              <div class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/60 rounded-xl flex items-center justify-between gap-2 shadow-xs">
                <span class="text-slate-700 dark:text-slate-200 flex items-center gap-1.5 font-medium"><span class="text-emerald-500 dark:text-emerald-400 font-bold">✓</span> {{ i18n.t('auth_matrix_csrf', 'CSRF & Rate Limit') }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/25 font-mono font-semibold">{{ i18n.t('auth_matrix_csrf_status', 'Protected') }}</span>
              </div>
            </div>
          </div>

          <div class="pt-5">
            <Link
              href="/admin/auth/policies"
              class="w-full py-2.5 px-4 bg-slate-50 hover:bg-purple-50 dark:bg-slate-900/60 dark:hover:bg-purple-600/20 text-purple-600 dark:text-purple-300 hover:text-purple-700 dark:hover:text-purple-200 border border-purple-200 dark:border-purple-500/30 hover:border-purple-400 dark:hover:border-purple-500/60 font-semibold text-xs rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-xs"
            >
              <span>{{ i18n.t('auth_card5_btn', 'Configure Security Policies') }}</span>
              <span class="transition-transform group-hover:translate-x-1 font-mono">→</span>
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>


