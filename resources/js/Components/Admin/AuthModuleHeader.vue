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
      return 'bg-rose-500/20 text-rose-300 border-rose-500/40 shadow-xs'
    case 'high':
      return 'bg-orange-500/20 text-orange-300 border-orange-500/40 shadow-xs'
    case 'medium':
      return 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-xs'
    default:
      return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 shadow-xs'
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
    <!-- Executive Header Banner with Sleek Command Center Aesthetic -->
    <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl sm:rounded-3xl border border-indigo-500/30 p-5 sm:p-6 shadow-xl text-white backdrop-blur-xl">
      <!-- Ambient Gradient Glow Effects -->
      <div class="absolute -top-20 -right-20 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-purple-500/15 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
        <div class="flex items-center gap-3.5 sm:gap-4">
          <!-- Glowing Security Squircle Icon -->
          <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-600 flex items-center justify-center p-1.5 shrink-0 shadow-lg shadow-indigo-500/40 border border-white/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 sm:w-7 sm:h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
              <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
            </svg>
          </div>

          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                {{ i18n.t('auth_module_title', 'Authentication & Access Module') }}
              </h1>
              <span class="px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider bg-indigo-500/30 text-indigo-200 border border-indigo-400/30 rounded-full backdrop-blur-md">
                {{ i18n.t('auth_security_control_only', 'Security Control') }}
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl font-medium leading-relaxed">
              {{ i18n.t('auth_header_desc', 'គ្រប់គ្រងសុវត្ថិភាព សិទ្ធិប្រើប្រាស់កម្រិត Role (RBAC), Active Sessions, Login History, និង Security Policies') }}
            </p>
          </div>
        </div>

        <!-- Real-time Threat Status Pill with Live Radar Ping -->
        <div class="flex items-center gap-2 self-start md:self-center shrink-0">
          <div class="relative group/threat cursor-help">
            <div
              class="px-4 py-2 rounded-xl text-xs font-black border flex items-center gap-2.5 transition-all shadow-md backdrop-blur-md"
              :class="getThreatBadgeClass(stats.threat_level)"
            >
              <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="stats.threat_level === 'Low' ? 'bg-emerald-400' : 'bg-red-400'"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="stats.threat_level === 'Low' ? 'bg-emerald-500' : 'bg-red-500'"></span>
              </span>
              <span class="tracking-wide uppercase font-extrabold">{{ i18n.t('auth_threat_status', 'Threat Status') }}: {{ stats.threat_level }}</span>
              <span class="text-[10px] opacity-75">ℹ️</span>
            </div>

            <!-- Tooltip dropdown on hover -->
            <div class="absolute right-0 top-full mt-2 w-72 p-3 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl text-xs text-slate-200 opacity-0 group-hover/threat:opacity-100 transition-all pointer-events-none group-hover/threat:pointer-events-auto z-50 backdrop-blur-xl">
              <div class="absolute -top-1.5 right-6 w-3 h-3 bg-slate-900 border-l border-t border-slate-700 rotate-45 pointer-events-none"></div>
              <div class="font-bold text-white mb-1 flex items-center gap-1.5 border-b border-slate-800 pb-1 relative z-10">
                <span>🛡️ {{ i18n.t('auth_threat_status', 'Threat Status') }}</span>
              </div>
              <p class="text-[11px] text-slate-300 leading-normal relative z-10">
                {{ getThreatExplanation(stats.threat_level) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── 4 TOP KPI CARDS (Dashboard Aesthetic: Deep Vibrant Gradients & Micro-Waves) ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
      <!-- Card 1: Total Roles ➔ Royal Purple/Indigo -->
      <Link
        href="/admin/auth/roles"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7c3aed] to-[#6366f1] text-white p-4.5 shadow-sm hover:shadow-xl hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card1_btn', 'Manage Roles & Permissions')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_total_roles', 'TOTAL ROLES') }}
            </span>
            <div class="text-white/95 group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="m9 12 2 2 4-4"/>
              </svg>
            </div>
          </div>
          <h4 class="text-3xl font-black tracking-tight text-white mt-1 font-mono">
            {{ Number(stats.total_roles || 0).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <span class="text-[11px] text-white/90 font-medium">Configured System Roles</span>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs">
              {{ i18n.t('auth_rbac_active', 'RBAC Active') }}
            </span>
          </div>
        </div>
        <div class="mt-2 -mx-4.5 -mb-4.5 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 2: Active Sessions ➔ Emerald Green -->
      <Link
        href="/admin/auth/sessions"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#10b981] to-[#059669] text-white p-4.5 shadow-sm hover:shadow-xl hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card2_btn', 'Monitor Active Sessions')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_active_sessions', 'ACTIVE SESSIONS') }}
            </span>
            <div class="text-white/95 group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="12" x="3" y="4" rx="2.5"/>
                <line x1="8" x2="16" y1="20" y2="20"/>
                <line x1="12" x2="12" y1="16" y2="20"/>
                <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
              </svg>
            </div>
          </div>
          <h4 class="text-3xl font-black tracking-tight text-white mt-1 font-mono">
            {{ Number(stats.active_sessions_count || 0).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <span class="text-[11px] text-white/90 font-medium">Live JWT Tokens Online</span>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-300 animate-ping"></span>
              {{ i18n.t('auth_live_monitor', 'Live Monitor') }}
            </span>
          </div>
        </div>
        <div class="mt-2 -mx-4.5 -mb-4.5 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 3: Failed Logins Today ➔ Vivid Orange -->
      <Link
        href="/admin/auth/failed"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#f97316] to-[#ea580c] text-white p-4.5 shadow-sm hover:shadow-xl hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card4_btn', 'Investigate Threat Logs')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_failed_logins_today', 'FAILED LOGINS TODAY') }}
            </span>
            <div class="text-white/95 group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
              </svg>
            </div>
          </div>
          <h4 class="text-3xl font-black tracking-tight text-white mt-1 font-mono">
            {{ Number(stats.failed_logins_today || 0).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <span class="text-[11px] text-white/90 font-medium">Threat Log Entries</span>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs">
              {{ i18n.t('auth_audit_required', 'Audit Required') }}
            </span>
          </div>
        </div>
        <div class="mt-2 -mx-4.5 -mb-4.5 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 4: Locked Accounts / Risk ➔ Rose/Crimson -->
      <Link
        href="/admin/auth/cyber-security"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#e11d48] to-[#be123c] text-white p-4.5 shadow-sm hover:shadow-xl hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_threat_status', 'Cyber Forensics')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_locked_accounts', 'LOCKED ACCOUNTS / RISK') }}
            </span>
            <div class="text-white/95 group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
          <h4 class="text-3xl font-black tracking-tight text-white mt-1 font-mono">
            {{ Number(stats.locked_accounts_count || 0).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <span class="text-[11px] text-white/90 font-medium">Incident Defense Rate</span>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs">
              {{ stats.threat_level }} Risk
            </span>
          </div>
        </div>
        <div class="mt-2 -mx-4.5 -mb-4.5 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 18 Q 20 6, 45 16 T 70 8 T 100 14 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 18 Q 20 6, 45 16 T 70 8 T 100 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>
    </div>

    <!-- Navigation Tabs — Sleek Pill Bar with Smooth Transitions -->
    <div class="p-1.5 bg-white/90 dark:bg-slate-900/90 border border-slate-200/90 dark:border-slate-800 rounded-2xl flex items-center gap-1.5 overflow-x-auto scrollbar-none shadow-sm backdrop-blur-md">
      <!-- 1. Overview Hub -->
      <Link
        href="/admin/auth-logs"
        :class="[
          activeTab === 'overview'
            ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-md shadow-indigo-600/30 font-bold border border-indigo-400/30'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/overview.svg'" alt="Overview" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_overview', 'Overview Hub') }}</span>
        <span v-if="activeTab === 'overview'" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
      </Link>

      <!-- 2. Roles & Permissions -->
      <Link
        href="/admin/auth/roles"
        :class="[
          activeTab === 'roles'
            ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-md shadow-indigo-600/30 font-bold border border-indigo-400/30'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/roles.svg'" alt="Roles" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_roles', 'Roles & Permissions') }}</span>
        <span v-if="activeTab === 'roles'" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
      </Link>

      <!-- 3. Active Sessions -->
      <Link
        href="/admin/auth/sessions"
        :class="[
          activeTab === 'sessions'
            ? 'bg-gradient-to-r from-emerald-600 to-emerald-500 text-white shadow-md shadow-emerald-600/30 font-bold border border-emerald-400/30'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/sessions.svg'" alt="Sessions" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_sessions', 'Active Sessions') }}</span>
        <span
          class="px-1.5 py-0.5 text-[10px] font-mono rounded-md font-bold"
          :class="activeTab === 'sessions' ? 'bg-white/20 text-white' : 'bg-slate-200/70 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
        >
          {{ stats.active_sessions_count }}
        </span>
      </Link>

      <!-- 4. Login History -->
      <Link
        href="/admin/auth/history"
        :class="[
          activeTab === 'history'
            ? 'bg-gradient-to-r from-sky-600 to-sky-500 text-white shadow-md shadow-sky-600/30 font-bold border border-sky-400/30'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/history.svg'" alt="History" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_history', 'Login History') }}</span>
        <span v-if="activeTab === 'history'" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
      </Link>

      <!-- 5. Failed Login Attempts -->
      <Link
        href="/admin/auth/failed"
        :class="[
          activeTab === 'failed'
            ? 'bg-gradient-to-r from-rose-600 to-rose-500 text-white shadow-md shadow-rose-600/30 font-bold border border-rose-400/30'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/failed.svg'" alt="Failed" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_failed', 'Failed Login Attempts') }}</span>
        <span
          v-if="stats.failed_logins_today > 0"
          class="px-1.5 py-0.5 text-[10px] font-mono rounded-md font-bold"
          :class="activeTab === 'failed' ? 'bg-white/20 text-white' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/50'"
        >
          {{ stats.failed_logins_today }}
        </span>
      </Link>

      <!-- 6. Security Policies -->
      <Link
        href="/admin/auth/policies"
        :class="[
          activeTab === 'policies'
            ? 'bg-gradient-to-r from-purple-600 to-purple-500 text-white shadow-md shadow-purple-600/30 font-bold border border-purple-400/30'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold border border-transparent',
          'px-3.5 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/policies.svg'" alt="Policies" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_policies', 'Security Policies') }}</span>
        <span v-if="activeTab === 'policies'" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
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

