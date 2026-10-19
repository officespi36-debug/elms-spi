<script setup lang="ts">
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

const stats = props.summaryStats || {
  total_roles: 3,
  active_sessions_count: 0,
  failed_logins_today: 0,
  locked_accounts_count: 0,
  threat_level: 'Low'
}

const getThreatBadgeClass = (level: string) => {
  switch (level?.toLowerCase()) {
    case 'critical':
      return 'bg-rose-50 dark:bg-red-500/20 text-rose-700 dark:text-red-400 border-rose-200 dark:border-red-500/30 shadow-xs'
    case 'high':
      return 'bg-orange-50 dark:bg-orange-500/20 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-500/30 shadow-xs'
    case 'medium':
      return 'bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-500/30 shadow-xs'
    default:
      return 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30 shadow-xs'
  }
}

const getThreatExplanation = (level: string) => {
  const failed = stats.failed_logins_today || 0
  const locked = stats.locked_accounts_count || 0

  switch (level?.toLowerCase()) {
    case 'critical':
      return `⚠️ ${i18n.t('auth_critical_risk', 'Critical Risk')}: ${failed} failed logins & ${locked} locked accounts.`
    case 'high':
      return `🚨 ${i18n.t('auth_high_risk', 'High Risk')}: ${failed} failed attempts today, ${locked} locked accounts.`
    case 'medium':
      return `🔍 ${i18n.t('auth_medium_risk', 'Medium Risk')}: ${failed} failed attempt(s), ${locked} locked account(s).`
    default:
      return `✅ ${i18n.t('auth_low_risk', 'Low (Safe)')}: Normal login activity, 0 threats.`
  }
}
</script>

<template>
  <div class="space-y-4 font-sans">
    <!-- Executive Header Banner with Top Gradient Accent -->
    <div class="relative overflow-hidden bg-white dark:bg-slate-900/90 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-sm dark:shadow-xl backdrop-blur-md">
      <!-- Top Accent Gradient Line -->
      <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
      <!-- Ambient Glow -->
      <div class="absolute -top-16 -right-16 w-48 h-48 bg-indigo-500/10 dark:bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 relative z-10">
        <div class="flex items-center gap-3.5">
          <!-- Squircle with soft halo matching Image 1 exactly! -->
          <div class="w-14 h-14 rounded-[22px] bg-indigo-50/90 dark:bg-indigo-950/50 border border-indigo-100/90 dark:border-indigo-800/60 flex items-center justify-center p-1.5 shrink-0 shadow-xs">
            <div class="w-11 h-11 rounded-[16px] bg-gradient-to-br from-[#4f46e5] to-[#4338ca] text-white flex items-center justify-center shadow-lg shadow-indigo-500/35 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ i18n.t('auth_module_title', 'Authentication Module') }}
              </h1>
              <span class="px-2.5 py-0.5 text-[10px] font-extrabold bg-indigo-50 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-200/90 dark:border-indigo-500/30 rounded-full uppercase tracking-wider">
                {{ i18n.t('auth_security_control_only', 'Security Control Only') }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl font-medium leading-relaxed">
              {{ i18n.t('auth_header_desc', 'គ្រប់គ្រងសុវត្ថិភាព សិទ្ធិប្រើប្រាស់កម្រិត Role (RBAC), Active Sessions, Login History, និង Security Policies') }}
            </p>
          </div>
        </div>

        <!-- Threat Status with Tooltip -->
        <div class="flex items-center gap-2">
          <div class="relative group/threat cursor-help">
            <div
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold border flex items-center gap-2 transition-all shadow-xs hover:shadow-md"
              :class="getThreatBadgeClass(stats.threat_level)"
            >
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="stats.threat_level === 'Low' ? 'bg-emerald-400' : 'bg-red-400'"></span>
                <span class="relative inline-flex rounded-full h-2 w-2" :class="stats.threat_level === 'Low' ? 'bg-emerald-500' : 'bg-red-500'"></span>
              </span>
              <span>{{ i18n.t('auth_threat_status', 'Threat Status') }}: {{ stats.threat_level }}</span>
              <span class="text-[10px] opacity-70">ℹ️</span>
            </div>

            <!-- Tooltip dropdown on hover -->
            <div class="absolute right-0 top-full mt-2.5 w-72 p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl text-xs text-slate-700 dark:text-slate-200 opacity-0 group-hover/threat:opacity-100 transition-all pointer-events-none group-hover/threat:pointer-events-auto z-50">
              <div class="absolute -top-1.5 right-6 w-3 h-3 bg-white dark:bg-slate-900 border-l border-t border-slate-200 dark:border-slate-700 rotate-45 pointer-events-none"></div>
              <div class="font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1 relative z-10">
                <span>🛡️ {{ i18n.t('auth_threat_status', 'Threat Status') }}</span>
              </div>
              <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-normal relative z-10">
                {{ getThreatExplanation(stats.threat_level) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── 4 TOP KPI CARDS (Matching Authentic Design with Glowing Squircles) ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
      <!-- Card 1: Total Roles -->
      <Link
        href="/admin/auth/roles"
        class="relative overflow-hidden bg-white dark:bg-slate-900/90 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card1_btn', 'Manage Roles & Permissions')"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wide uppercase">
            {{ i18n.t('auth_total_roles', 'Total Roles') }}
          </span>
          <!-- Squircle icon matching Image 2 -->
          <div class="w-10 h-10 rounded-[16px] bg-gradient-to-br from-[#7c3aed] to-[#6366f1] text-white flex items-center justify-center shadow-md shadow-indigo-500/25 shrink-0 group-hover:scale-110 transition-transform duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              <path d="m9 12 2 2 4-4"/>
            </svg>
          </div>
        </div>

        <div class="mt-4 flex items-baseline justify-between">
          <span class="text-3xl font-black text-slate-900 dark:text-white font-mono tracking-tight">
            {{ Number(stats.total_roles || 0).toLocaleString() }}
          </span>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-500/30 shadow-xs">
            {{ i18n.t('auth_rbac_active', 'RBAC Active') }}
          </span>
        </div>
      </Link>

      <!-- Card 2: Active Sessions -->
      <Link
        href="/admin/auth/sessions"
        class="relative overflow-hidden bg-white dark:bg-slate-900/90 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card2_btn', 'Monitor Active Sessions')"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wide uppercase">
            {{ i18n.t('auth_active_sessions', 'Active Sessions') }}
          </span>
          <!-- Squircle icon matching Image 3 -->
          <div class="w-10 h-10 rounded-[16px] bg-gradient-to-br from-[#10b981] to-[#059669] text-white flex items-center justify-center shadow-md shadow-emerald-500/25 shrink-0 group-hover:scale-110 transition-transform duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="18" height="12" x="3" y="4" rx="2.5"/>
              <line x1="8" x2="16" y1="20" y2="20"/>
              <line x1="12" x2="12" y1="16" y2="20"/>
              <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
            </svg>
          </div>
        </div>

        <div class="mt-4 flex items-baseline justify-between">
          <span class="text-3xl font-black text-slate-900 dark:text-white font-mono tracking-tight">
            {{ Number(stats.active_sessions_count || 0).toLocaleString() }}
          </span>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-500/30 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
            {{ i18n.t('auth_live_monitor', 'Live Monitor') }}
          </span>
        </div>
      </Link>

      <!-- Card 3: Failed Logins Today -->
      <Link
        href="/admin/auth/failed"
        class="relative overflow-hidden bg-white dark:bg-slate-900/90 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card4_btn', 'Investigate Threat Logs')"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wide uppercase">
            {{ i18n.t('auth_failed_logins_today', 'Failed Logins Today') }}
          </span>
          <!-- Squircle icon with cross -->
          <div class="w-10 h-10 rounded-[16px] bg-gradient-to-br from-[#f97316] to-[#ea580c] text-white flex items-center justify-center shadow-md shadow-orange-500/25 shrink-0 group-hover:scale-110 transition-transform duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9"/>
              <line x1="15" y1="9" x2="9" y2="15"/>
              <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
          </div>
        </div>

        <div class="mt-4 flex items-baseline justify-between">
          <span
            class="text-3xl font-black font-mono tracking-tight"
            :class="stats.failed_logins_today > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'"
          >
            {{ Number(stats.failed_logins_today || 0).toLocaleString() }}
          </span>
          <div class="relative group/audit" @click.stop.prevent>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-500/30 shadow-xs cursor-help">
              <span>{{ i18n.t('auth_audit_required', 'Audit Required') }}</span>
              <span class="text-[10px] opacity-70">ℹ️</span>
            </span>
            <!-- Tooltip -->
            <div class="absolute right-0 bottom-full mb-2 w-64 p-2.5 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl text-xs text-slate-100 opacity-0 group-hover/audit:opacity-100 transition-all pointer-events-none z-50">
              <p class="text-[11px] leading-snug">
                🔍 {{ i18n.t('auth_audit_required', 'Audit Required') }}: {{ stats.failed_logins_today }} {{ i18n.t('auth_card4_attempts', 'Attempts') }}.
              </p>
              <div class="absolute -bottom-1.5 right-4 w-3 h-3 bg-slate-900 border-r border-b border-slate-700 rotate-45 pointer-events-none"></div>
            </div>
          </div>
        </div>
      </Link>

      <!-- Card 4: Locked Accounts / Risk -->
      <Link
        href="/admin/auth/cyber-security"
        class="relative overflow-hidden bg-white dark:bg-slate-900/90 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_threat_status', 'Cyber Forensics')"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-500 dark:text-slate-400 tracking-wide uppercase">
            {{ i18n.t('auth_locked_accounts', 'Locked Accounts / Risk') }}
          </span>
          <!-- Squircle icon matching Image 1 Lock -->
          <div class="w-10 h-10 rounded-[16px] bg-gradient-to-br from-[#f43f5e] to-[#e11d48] text-white flex items-center justify-center shadow-md shadow-rose-500/25 shrink-0 group-hover:scale-110 transition-transform duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
              <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
            </svg>
          </div>
        </div>

        <div class="mt-4 flex items-baseline justify-between">
          <span
            class="text-3xl font-black font-mono tracking-tight"
            :class="stats.locked_accounts_count > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white'"
          >
            {{ Number(stats.locked_accounts_count || 0).toLocaleString() }}
          </span>
          <div class="relative group/risk" @click.stop.prevent>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-rose-50 dark:bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-200/80 dark:border-rose-500/30 shadow-xs cursor-help">
              <span>{{ stats.threat_level }} Risk</span>
              <span class="text-[10px] opacity-70">ℹ️</span>
            </span>
            <!-- Tooltip -->
            <div class="absolute right-0 bottom-full mb-2 w-64 p-2.5 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl text-xs text-slate-100 opacity-0 group-hover/risk:opacity-100 transition-all pointer-events-none z-50">
              <p class="text-[11px] leading-snug">
                {{ getThreatExplanation(stats.threat_level) }}
              </p>
              <div class="absolute -bottom-1.5 right-4 w-3 h-3 bg-slate-900 border-r border-b border-slate-700 rotate-45 pointer-events-none"></div>
            </div>
          </div>
        </div>
      </Link>
    </div>

    <!-- Navigation Tabs — Sleek Pill Bar -->
    <div class="p-1.5 bg-slate-100/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 rounded-2xl flex items-center gap-1.5 overflow-x-auto scrollbar-none shadow-xs backdrop-blur-md">
      <!-- 1. Overview Hub -->
      <Link
        href="/admin/auth-logs"
        :class="[
          activeTab === 'overview'
            ? 'bg-white dark:bg-indigo-600 text-indigo-600 dark:text-white shadow-md shadow-indigo-500/15 font-bold border border-slate-200/60 dark:border-indigo-500/40'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/overview.svg'" alt="Overview" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_overview', 'Overview Hub') }}</span>
        <span v-if="activeTab === 'overview'" class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-white animate-pulse"></span>
      </Link>

      <!-- 2. Roles & Permissions -->
      <Link
        href="/admin/auth/roles"
        :class="[
          activeTab === 'roles'
            ? 'bg-white dark:bg-indigo-600 text-indigo-600 dark:text-white shadow-md shadow-indigo-500/15 font-bold border border-slate-200/60 dark:border-indigo-500/40'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/roles.svg'" alt="Roles" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_roles', 'Roles & Permissions') }}</span>
        <span v-if="activeTab === 'roles'" class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-white animate-pulse"></span>
      </Link>

      <!-- 3. Active Sessions -->
      <Link
        href="/admin/auth/sessions"
        :class="[
          activeTab === 'sessions'
            ? 'bg-white dark:bg-emerald-600 text-emerald-600 dark:text-white shadow-md shadow-emerald-500/15 font-bold border border-slate-200/60 dark:border-emerald-500/40'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/sessions.svg'" alt="Sessions" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_sessions', 'Active Sessions') }}</span>
        <span
          class="px-1.5 py-0.5 text-[10px] font-mono rounded-md font-bold"
          :class="activeTab === 'sessions' ? 'bg-emerald-100 dark:bg-white/20 text-emerald-800 dark:text-white' : 'bg-slate-200/70 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
        >
          {{ stats.active_sessions_count }}
        </span>
      </Link>

      <!-- 4. Login History -->
      <Link
        href="/admin/auth/history"
        :class="[
          activeTab === 'history'
            ? 'bg-white dark:bg-sky-600 text-sky-600 dark:text-white shadow-md shadow-sky-500/15 font-bold border border-slate-200/60 dark:border-sky-500/40'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/history.svg'" alt="History" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_history', 'Login History') }}</span>
        <span v-if="activeTab === 'history'" class="w-1.5 h-1.5 rounded-full bg-sky-500 dark:bg-white animate-pulse"></span>
      </Link>

      <!-- 5. Failed Login Attempts -->
      <Link
        href="/admin/auth/failed"
        :class="[
          activeTab === 'failed'
            ? 'bg-white dark:bg-rose-600 text-rose-600 dark:text-white shadow-md shadow-rose-500/15 font-bold border border-slate-200/60 dark:border-rose-500/40'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/failed.svg'" alt="Failed" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_failed', 'Failed Login Attempts') }}</span>
        <span
          v-if="stats.failed_logins_today > 0"
          class="px-1.5 py-0.5 text-[10px] font-mono rounded-md font-bold"
          :class="activeTab === 'failed' ? 'bg-rose-100 dark:bg-white/20 text-rose-800 dark:text-white' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50'"
        >
          {{ stats.failed_logins_today }}
        </span>
      </Link>

      <!-- 6. Security Policies -->
      <Link
        href="/admin/auth/policies"
        :class="[
          activeTab === 'policies'
            ? 'bg-white dark:bg-purple-600 text-purple-600 dark:text-white shadow-md shadow-purple-500/15 font-bold border border-slate-200/60 dark:border-purple-500/40'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/policies.svg'" alt="Policies" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_policies', 'Security Policies') }}</span>
        <span v-if="activeTab === 'policies'" class="w-1.5 h-1.5 rounded-full bg-purple-500 dark:bg-white animate-pulse"></span>
      </Link>

      <!-- 7. Cyber Forensics -->
      <Link
        href="/admin/auth/cyber-security"
        :class="[
          activeTab === 'cyber-security'
            ? 'bg-gradient-to-r from-rose-500 to-red-600 text-white shadow-md shadow-rose-500/25 font-bold border border-rose-400'
            : 'text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 font-bold border border-rose-200/70 dark:border-rose-900/40 bg-white/50 dark:bg-slate-900/40',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <span class="text-sm">🛡️</span>
        <span>សន្តិសុខ & Cyber Forensics</span>
        <span v-if="activeTab === 'cyber-security'" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
        <span v-else class="px-1.5 py-0.5 text-[9px] font-black bg-rose-100 dark:bg-rose-500/25 text-rose-700 dark:text-rose-300 rounded-md uppercase tracking-wider">LIVE</span>
      </Link>
    </div>
  </div>
</template>

