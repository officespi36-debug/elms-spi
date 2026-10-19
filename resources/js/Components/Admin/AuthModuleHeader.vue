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
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-blue-700 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25 shrink-0 ring-4 ring-indigo-50 dark:ring-indigo-950/60">
            <img :src="'/images/nav/auth.svg'" alt="Authentication" class="w-6 h-6 object-contain filter brightness-0 invert" />
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

    <!-- ── 4 TOP KPI CARDS (Matching Dashboard.vue Aesthetics) ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
      <!-- Card 1: Total Roles ➔ Royal Purple/Violet -->
      <Link
        href="/admin/auth/roles"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7c3aed] to-[#6366f1] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card1_btn', 'Manage Roles & Permissions')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_total_roles', 'TOTAL ROLES') }}
            </span>
            <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="m9 12 2 2 4-4"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.total_roles || 0).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs">
              {{ i18n.t('auth_rbac_active', 'RBAC Active') }}
            </span>
          </div>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 2: Active Sessions ➔ Emerald Green -->
      <Link
        href="/admin/auth/sessions"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#10b981] to-[#059669] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card2_btn', 'Monitor Active Sessions')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_active_sessions', 'ACTIVE SESSIONS') }}
            </span>
            <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="14" x="2" y="3" rx="2"/>
                <line x1="8" x2="16" y1="21" y2="21"/>
                <line x1="12" x2="12" y1="17" y2="21"/>
                <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.active_sessions_count || 0).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs">
              <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
              {{ i18n.t('auth_live_monitor', 'Live Monitor') }}
            </span>
          </div>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 3: Failed Logins Today ➔ Vivid Orange -->
      <Link
        href="/admin/auth/failed"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#f97316] to-[#ea580c] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card4_btn', 'Investigate Threat Logs')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_failed_logins_today', 'FAILED LOGINS TODAY') }}
            </span>
            <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.failed_logins_today || 0).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <div class="relative group/audit" @click.stop.prevent>
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs cursor-help">
                <span>{{ i18n.t('auth_audit_required', 'Audit Required') }}</span>
                <span class="text-[9px] opacity-80">ℹ️</span>
              </span>
              <!-- Tooltip -->
              <div class="absolute left-0 bottom-full mb-2 w-64 p-2.5 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl text-xs text-slate-100 opacity-0 group-hover/audit:opacity-100 transition-all pointer-events-none z-50">
                <p class="text-[11px] leading-snug">
                  🔍 {{ i18n.t('auth_audit_required', 'Audit Required') }}: {{ stats.failed_logins_today }} {{ i18n.t('auth_card4_attempts', 'Attempts') }}.
                </p>
                <div class="absolute -bottom-1.5 left-4 w-3 h-3 bg-slate-900 border-r border-b border-slate-700 rotate-45 pointer-events-none"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 4: Locked Accounts / Risk ➔ Bright Rose/Crimson -->
      <Link
        href="/admin/auth/cyber-security"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#f43f5e] to-[#e11d48] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_threat_status', 'Cyber Forensics')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_locked_accounts', 'LOCKED ACCOUNTS / RISK') }}
            </span>
            <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                <circle cx="12" cy="16" r="1.5" fill="currentColor"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.locked_accounts_count || 0).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <div class="relative group/risk" @click.stop.prevent>
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs cursor-help">
                <span>{{ stats.threat_level }} Risk</span>
                <span class="text-[9px] opacity-80">ℹ️</span>
              </span>
              <!-- Tooltip -->
              <div class="absolute left-0 bottom-full mb-2 w-64 p-2.5 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl text-xs text-slate-100 opacity-0 group-hover/risk:opacity-100 transition-all pointer-events-none z-50">
                <p class="text-[11px] leading-snug">
                  {{ getThreatExplanation(stats.threat_level) }}
                </p>
                <div class="absolute -bottom-1.5 left-4 w-3 h-3 bg-slate-900 border-r border-b border-slate-700 rotate-45 pointer-events-none"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
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

