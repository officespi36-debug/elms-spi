<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { i18n } from '@/Services/i18n'

const props = defineProps<{
  activeTab: 'overview' | 'roles' | 'sessions' | 'history' | 'failed' | 'policies' | 'cyber-security'
  summaryStats?: {
    total_roles: number
    active_sessions_count: number
    failed_logins_today: number
    locked_accounts_count: number
    threat_level: string
  }
}>()

const stats = computed(() => props.summaryStats || {
  total_roles: 3,
  active_sessions_count: 0,
  failed_logins_today: 0,
  locked_accounts_count: 0,
  threat_level: 'Low'
})

const getStatusBadge = computed(() => {
  const level = stats.value.threat_level?.toLowerCase()
  const failed = stats.value.failed_logins_today || 0
  const locked = stats.value.locked_accounts_count || 0

  if (level === 'critical' || failed > 10 || locked > 5) {
    return {
      dotColor: 'bg-rose-500',
      badgeClass: 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/20',
      text: i18n.t('auth_status_critical', 'Review Required'),
      desc: i18n.t('auth_critical_desc', `${failed} failed attempts, ${locked} locked accounts`)
    }
  }

  if (level === 'high' || level === 'medium' || failed > 0 || locked > 0) {
    return {
      dotColor: 'bg-amber-500',
      badgeClass: 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/20',
      text: i18n.t('auth_status_attention', 'Activity Alert'),
      desc: i18n.t('auth_attention_desc', `${failed} failed attempt(s) today`)
    }
  }

  return {
    dotColor: 'bg-emerald-500',
    badgeClass: 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
    text: i18n.t('auth_status_protected', 'System Protected'),
    desc: i18n.t('auth_protected_desc', 'All security systems operating normally')
  }
})
</script>

<template>
  <div class="space-y-4 font-sans">
    <!-- ── 1. Top Executive Banner (Natural, Clean, Enterprise Grade) ── -->
    <div class="bg-white/90 dark:bg-slate-900/60 p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 backdrop-blur-xl shadow-xs dark:shadow-sm">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Module Icon & Titles -->
        <div class="flex items-center gap-3.5">
          <!-- Clean Refined Icon Box -->
          <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200/80 dark:border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0 shadow-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>

          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-wide">
                {{ i18n.t('auth_module_title', 'Authentication & Access') }}
              </h1>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-500/20">
                {{ i18n.t('auth_security_control_only', 'Security & Access') }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-normal leading-relaxed">
              {{ i18n.t('auth_header_desc', 'គ្រប់គ្រងសុវត្ថិភាព សិទ្ធិប្រើប្រាស់កម្រិត Role (RBAC), Active Sessions, Login History, និង Security Policies') }}
            </p>
          </div>
        </div>

        <!-- Clean System Status Badge (Natural & Professional) -->
        <div class="relative group/status self-start md:self-center shrink-0">
          <div
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all cursor-default shadow-xs"
            :class="getStatusBadge.badgeClass"
          >
            <span class="w-2 h-2 rounded-full" :class="getStatusBadge.dotColor"></span>
            <span>{{ getStatusBadge.text }}</span>
          </div>

          <!-- Tooltip with Contextual Details -->
          <div class="absolute right-0 top-full mt-2 w-64 p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl text-xs text-slate-600 dark:text-slate-300 opacity-0 group-hover/status:opacity-100 transition-all pointer-events-none group-hover/status:pointer-events-auto z-50 backdrop-blur-xl">
            <div class="font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
              <span>{{ i18n.t('auth_threat_status', 'Security Health Status') }}</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
              {{ getStatusBadge.desc }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── 2. Four Modern Metric KPI Cards (Clean, Dual-Themed, No Fake Waves) ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
      <!-- Card 1: Total Roles -->
      <Link
        href="/admin/auth/roles"
        class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-4.5 hover:border-indigo-300 dark:hover:border-indigo-500/30 transition-all flex items-center justify-between shadow-xs dark:shadow-none group cursor-pointer"
        :title="i18n.t('auth_card1_btn', 'Manage Roles & Permissions')"
      >
        <div>
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
            {{ i18n.t('auth_total_roles', 'TOTAL ROLES') }}
          </span>
          <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">
            {{ Number(stats.total_roles || 0).toLocaleString() }}
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
            <span>{{ i18n.t('auth_rbac_active', 'RBAC Active') }}</span>
          </p>
        </div>
        <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-xs group-hover:scale-105 transition-transform shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            <path d="m9 12 2 2 4-4"/>
          </svg>
        </div>
      </Link>

      <!-- Card 2: Active Sessions -->
      <Link
        href="/admin/auth/sessions"
        class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-4.5 hover:border-emerald-300 dark:hover:border-emerald-500/30 transition-all flex items-center justify-between shadow-xs dark:shadow-none group cursor-pointer"
        :title="i18n.t('auth_card2_btn', 'Monitor Active Sessions')"
      >
        <div>
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
            {{ i18n.t('auth_active_sessions', 'ACTIVE SESSIONS') }}
          </span>
          <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">
            {{ Number(stats.active_sessions_count || 0).toLocaleString() }}
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>{{ i18n.t('auth_live_monitor', 'Live Sessions') }}</span>
          </p>
        </div>
        <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-xs group-hover:scale-105 transition-transform shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="12" x="3" y="4" rx="2.5"/>
            <line x1="8" x2="16" y1="20" y2="20"/>
            <line x1="12" x2="12" y1="16" y2="20"/>
            <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
          </svg>
        </div>
      </Link>

      <!-- Card 3: Failed Logins Today -->
      <Link
        href="/admin/auth/failed"
        class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-4.5 hover:border-amber-300 dark:hover:border-amber-500/30 transition-all flex items-center justify-between shadow-xs dark:shadow-none group cursor-pointer"
        :title="i18n.t('auth_card4_btn', 'Investigate Threat Logs')"
      >
        <div>
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
            {{ i18n.t('auth_failed_logins_today', 'FAILED LOGINS (TODAY)') }}
          </span>
          <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">
            {{ Number(stats.failed_logins_today || 0).toLocaleString() }}
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
            <span class="inline-block w-1.5 h-1.5 rounded-full" :class="stats.failed_logins_today > 0 ? 'bg-amber-500' : 'bg-slate-400'"></span>
            <span>{{ stats.failed_logins_today > 0 ? i18n.t('auth_audit_required', 'Audit Required') : i18n.t('auth_clean_logs', 'Normal logs') }}</span>
          </p>
        </div>
        <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-100 dark:border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-xs group-hover:scale-105 transition-transform shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
        </div>
      </Link>

      <!-- Card 4: Locked Accounts / Risk -->
      <Link
        href="/admin/auth/cyber-security"
        class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-4.5 hover:border-rose-300 dark:hover:border-rose-500/30 transition-all flex items-center justify-between shadow-xs dark:shadow-none group cursor-pointer"
        :title="i18n.t('auth_threat_status', 'Security Audit')"
      >
        <div>
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
            {{ i18n.t('auth_locked_accounts', 'LOCKED ACCOUNTS') }}
          </span>
          <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">
            {{ Number(stats.locked_accounts_count || 0).toLocaleString() }}
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
            <span class="inline-block w-1.5 h-1.5 rounded-full" :class="stats.locked_accounts_count > 0 ? 'bg-rose-500' : 'bg-slate-400'"></span>
            <span>{{ stats.locked_accounts_count > 0 ? i18n.t('auth_accounts_on_hold', 'Suspended Accounts') : i18n.t('auth_accounts_clean', 'Zero Locked') }}</span>
          </p>
        </div>
        <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-500/10 border border-rose-100 dark:border-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400 shadow-xs group-hover:scale-105 transition-transform shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
        </div>
      </Link>
    </div>

    <!-- ── 3. Navigation Tabs (Clean Unified Bar, No Rainbow Neon Gradients) ── -->
    <div class="p-1.5 bg-white/90 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl flex items-center gap-1 overflow-x-auto scrollbar-none shadow-xs backdrop-blur-md">
      <!-- 1. Overview Hub -->
      <Link
        href="/admin/auth-logs"
        :class="[
          activeTab === 'overview'
            ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white shadow-xs font-bold border border-slate-800 dark:border-slate-700'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-150 whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/overview.svg'" alt="Overview" class="w-4 h-4 object-contain" />
        <span>{{ i18n.t('auth_tab_overview', 'Overview Hub') }}</span>
      </Link>

      <!-- 2. Roles & Permissions -->
      <Link
        href="/admin/auth/roles"
        :class="[
          activeTab === 'roles'
            ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white shadow-xs font-bold border border-slate-800 dark:border-slate-700'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-150 whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/roles.svg'" alt="Roles" class="w-4 h-4 object-contain" />
        <span>{{ i18n.t('auth_tab_roles', 'Roles & Permissions') }}</span>
      </Link>

      <!-- 3. Active Sessions -->
      <Link
        href="/admin/auth/sessions"
        :class="[
          activeTab === 'sessions'
            ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white shadow-xs font-bold border border-slate-800 dark:border-slate-700'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-150 whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/sessions.svg'" alt="Sessions" class="w-4 h-4 object-contain" />
        <span>{{ i18n.t('auth_tab_sessions', 'Active Sessions') }}</span>
        <span
          class="px-1.5 py-0.5 text-[10px] font-mono rounded-md font-bold"
          :class="activeTab === 'sessions' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700'"
        >
          {{ stats.active_sessions_count }}
        </span>
      </Link>

      <!-- 4. Login History -->
      <Link
        href="/admin/auth/history"
        :class="[
          activeTab === 'history'
            ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white shadow-xs font-bold border border-slate-800 dark:border-slate-700'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-150 whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/history.svg'" alt="History" class="w-4 h-4 object-contain" />
        <span>{{ i18n.t('auth_tab_history', 'Login History') }}</span>
      </Link>

      <!-- 5. Failed Attempts -->
      <Link
        href="/admin/auth/failed"
        :class="[
          activeTab === 'failed'
            ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white shadow-xs font-bold border border-slate-800 dark:border-slate-700'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-150 whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/failed.svg'" alt="Failed" class="w-4 h-4 object-contain" />
        <span>{{ i18n.t('auth_tab_failed', 'Failed Attempts') }}</span>
        <span
          v-if="stats.failed_logins_today > 0"
          class="px-1.5 py-0.5 text-[10px] font-mono rounded-md font-bold"
          :class="activeTab === 'failed' ? 'bg-white/20 text-white' : 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50'"
        >
          {{ stats.failed_logins_today }}
        </span>
      </Link>

      <!-- 6. Security Policies -->
      <Link
        href="/admin/auth/policies"
        :class="[
          activeTab === 'policies'
            ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white shadow-xs font-bold border border-slate-800 dark:border-slate-700'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-150 whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/policies.svg'" alt="Policies" class="w-4 h-4 object-contain" />
        <span>{{ i18n.t('auth_tab_policies', 'Security Policies') }}</span>
      </Link>

      <!-- 7. Security Audit & Forensics -->
      <Link
        href="/admin/auth/cyber-security"
        :class="[
          activeTab === 'cyber-security'
            ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white shadow-xs font-bold border border-slate-800 dark:border-slate-700'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-150 whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        <span>{{ i18n.t('auth_tab_forensics', 'Security Audit') }}</span>
      </Link>
    </div>
  </div>
</template>
