<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { i18n } from '@/Services/i18n'

const props = defineProps<{
  activeTab: 'overview' | 'roles' | 'sessions' | 'history' | 'failed' | 'policies' | 'cyber-security' | 'audit'
  summaryStats?: {
    total_roles: number
    active_sessions_count: number
    failed_logins_today: number
    locked_accounts_count: number
    threat_level: string
  }
}>()

const isReviewModalOpen = ref(false)

const stats = props.summaryStats || {
  total_roles: 3,
  active_sessions_count: 7,
  failed_logins_today: 65,
  locked_accounts_count: 14,
  threat_level: 'Critical'
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

const handleUnlockAll = () => {
  if (confirm(i18n.t('confirm_unlock_all', 'Are you sure you want to unlock all suspended accounts and reset failed attempts?'))) {
    router.post('/admin/auth-logs/unlock-all', {}, {
      preserveScroll: true,
      onSuccess: () => {
        isReviewModalOpen.value = false
      }
    })
  }
}
</script>

<template>
  <div class="space-y-4 font-sans">
    <!-- ── 1. EXECUTIVE COMMAND HEADER (MATCHING DASHBOARD SYSTEM) ── -->
    <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl border border-indigo-500/30 p-5 sm:p-6 shadow-xl text-white backdrop-blur-xl">
      <!-- Ambient Lighting Glow -->
      <div class="absolute -top-20 -right-20 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-purple-500/15 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
        <div class="flex items-center gap-3.5 sm:gap-4">
          <!-- Glowing Security Squircle Icon -->
          <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-600 flex items-center justify-center p-2 shrink-0 shadow-lg shadow-indigo-500/40 border border-white/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 sm:w-7 sm:h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
              <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
            </svg>
          </div>

          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                {{ i18n.t('auth_module_title', 'Authentication & Access') }}
              </h1>
              <span class="px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider bg-indigo-500/30 text-indigo-200 border border-indigo-400/30 rounded-full backdrop-blur-md">
                {{ i18n.t('auth_security_control_only', 'Security Control Only') }}
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl font-medium leading-relaxed">
              {{ i18n.t('auth_header_desc', 'Manage system security, role access matrix (RBAC), active sessions, login history, and security policies') }}
            </p>
          </div>
        </div>

        <!-- Top Right Actions: Interactive Review Required Button & Threat Badge -->
        <div class="flex items-center gap-2.5 self-start md:self-center shrink-0 flex-wrap">
          <!-- Review Required Button -->
          <button
            v-if="(stats.failed_logins_today || 0) > 0 || (stats.locked_accounts_count || 0) > 0"
            @click="isReviewModalOpen = true"
            type="button"
            class="px-3.5 py-1.5 rounded-full text-xs font-bold border border-rose-300/80 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-900/60 shadow-xs hover:shadow-md transition-all flex items-center gap-2 cursor-pointer"
            :title="i18n.t('auth_click_to_review', 'Click to review security incidents & locked accounts')"
          >
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
            </span>
            <span>{{ i18n.t('auth_review_required', 'Review Required') }}</span>
          </button>

          <!-- Threat Status Pill with Live Radar Ping -->
          <div class="relative group/threat cursor-help">
            <div
              class="px-3.5 py-1.5 rounded-full text-xs font-bold border flex items-center gap-2 transition-all shadow-md backdrop-blur-md"
              :class="getThreatBadgeClass(stats.threat_level)"
            >
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="stats.threat_level === 'Low' ? 'bg-emerald-400' : 'bg-red-400'"></span>
                <span class="relative inline-flex rounded-full h-2 w-2" :class="stats.threat_level === 'Low' ? 'bg-emerald-500' : 'bg-red-500'"></span>
              </span>
              <span class="tracking-wide uppercase font-extrabold">{{ stats.threat_level }}</span>
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

    <!-- ── 2. TOP 4 KPI CARDS (EXACT MATCH TO DASHBOARD VIBRANT GRADIENTS & WAVES) ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
      
      <!-- Card 1: Total Roles ➔ Royal Purple/Indigo -->
      <Link
        href="/admin/auth/roles"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7c3aed] to-[#6366f1] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card1_btn', 'Manage Roles & Permissions Matrix')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_total_roles', 'TOTAL ROLES') }}
            </span>
            <div class="text-white/95 group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="m9 12 2 2 4-4"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.total_roles || 3).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <span class="text-[11px] text-white/95 font-medium">Configured System Roles</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs">
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
            <div class="text-white/95 group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="12" x="3" y="4" rx="2.5"/>
                <line x1="8" x2="16" y1="20" y2="20"/>
                <line x1="12" y1="12" x2="12" y2="16"/>
                <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.active_sessions_count || 7).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <span class="text-[11px] text-white/95 font-medium">Live Sessions</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-300 animate-ping"></span>
              {{ i18n.t('auth_live_sessions', 'Live Sessions') }}
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
        href="/admin/auth/history?filter=failed"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#f97316] to-[#ea580c] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card4_btn', 'Investigate Threat Logs in Login History')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_failed_logins_today', 'FAILED LOGINS TODAY') }}
            </span>
            <div class="text-white/95 group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <circle cx="12" cy="16" r="0.75" fill="currentColor"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.failed_logins_today || 65).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <span class="text-[11px] text-white/95 font-medium">Threat Log Entries</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs">
              {{ i18n.t('auth_audit_required', 'Audit Required') }}
            </span>
          </div>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 4: Locked Accounts / Risk ➔ Rose / Crimson -->
      <button
        type="button"
        @click="isReviewModalOpen = true"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#e11d48] to-[#be123c] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer text-left"
        :title="i18n.t('auth_locked_accounts_btn', 'Click to Review & Unlock Accounts')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">
              {{ i18n.t('auth_locked_accounts', 'LOCKED ACCOUNTS / RISK') }}
            </span>
            <div class="text-white/95 group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.locked_accounts_count || 14).toLocaleString() }}
          </h4>
          <div class="mt-1 flex items-center justify-between">
            <span class="text-[11px] text-white/95 font-medium">Suspended Accounts</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-md border border-white/25 shadow-xs">
              {{ i18n.t('auth_suspended_accounts', 'Suspended Accounts') }}
            </span>
          </div>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 18 Q 20 6, 45 16 T 70 8 T 100 14 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 18 Q 20 6, 45 16 T 70 8 T 100 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </button>
    </div>

    <!-- ── 3. NAVIGATION TABS (COHESIVE DASHBOARD AESTHETICS) ── -->
    <div class="p-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center gap-1.5 overflow-x-auto scrollbar-none shadow-xs">
      
      <!-- Tab 1: Overview Hub -->
      <Link
        href="/admin/auth-logs"
        :class="[
          activeTab === 'overview'
            ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/overview.svg'" alt="Overview" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_overview', 'Overview Hub') }}</span>
        <span v-if="activeTab === 'overview'" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
      </Link>

      <!-- Tab 2: Active Sessions (Shows real count) -->
      <Link
        href="/admin/auth/sessions"
        :class="[
          activeTab === 'sessions'
            ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/sessions.svg'" alt="Sessions" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_sessions', 'Active Sessions') }}</span>
        <span
          class="px-1.5 py-0.5 text-[10px] font-mono rounded-md font-bold"
          :class="activeTab === 'sessions' ? 'bg-white/20 text-white' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
        >
          {{ stats.active_sessions_count || 7 }}
        </span>
      </Link>

      <!-- Tab 3: Roles & Permissions Matrix -->
      <Link
        href="/admin/auth/roles"
        :class="[
          activeTab === 'roles'
            ? 'bg-gradient-to-r from-[#7c3aed] to-[#6366f1] text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/roles.svg'" alt="Roles" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_roles', 'Roles & Permissions') }}</span>
        <span v-if="activeTab === 'roles'" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
      </Link>

      <!-- Tab 4: Login History -->
      <Link
        href="/admin/auth/history"
        :class="[
          activeTab === 'history' || activeTab === 'failed'
            ? 'bg-gradient-to-r from-sky-600 to-blue-600 text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/history.svg'" alt="History" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_history', 'Login History') }}</span>
        <span v-if="activeTab === 'history' || activeTab === 'failed'" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
      </Link>

      <!-- Tab 5: Security Policies -->
      <Link
        href="/admin/auth/policies"
        :class="[
          activeTab === 'policies'
            ? 'bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/policies.svg'" alt="Policies" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_policies', 'Security Policies') }}</span>
        <span v-if="activeTab === 'policies'" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
      </Link>

      <!-- Tab 6: Security Audit -->
      <Link
        href="/admin/auth/cyber-security"
        :class="[
          activeTab === 'cyber-security' || activeTab === 'audit'
            ? 'bg-gradient-to-r from-rose-600 to-red-600 text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-xl transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <path d="m9 12 2 2 4-4"/>
        </svg>
        <span>{{ i18n.t('auth_tab_security_audit', 'Security Audit') }}</span>
        <span v-if="activeTab === 'cyber-security' || activeTab === 'audit'" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
      </Link>
    </div>

    <!-- ── 4. REVIEW REQUIRED SECURITY MODAL ── -->
    <Teleport to="body">
      <div
        v-if="isReviewModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md animate-fade-in"
      >
        <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl border border-rose-200 dark:border-rose-900/60 shadow-2xl p-6 overflow-hidden space-y-5 max-h-[90vh] flex flex-col">
          <!-- Ambient Glow -->
          <div class="absolute -top-12 -right-12 w-48 h-48 bg-rose-500/15 rounded-full blur-2xl pointer-events-none"></div>

          <!-- Modal Header -->
          <div class="flex items-start justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 flex items-center justify-center text-2xl shrink-0">
                🚨
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-lg font-black text-slate-900 dark:text-white">
                    {{ i18n.t('review_modal_title', 'Security Incidents Requiring Review') }}
                  </h3>
                  <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded-full bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                    {{ stats.threat_level }} Risk
                  </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  {{ i18n.t('review_modal_desc', 'ពិនិត្យមើលការប៉ុនប៉ងចូលមិនជោគជ័យ និងគណនីដែលត្រូវចាក់សោរដោយស្វ័យប្រវត្តិ') }}
                </p>
              </div>
            </div>
            <button
              @click="isReviewModalOpen = false"
              class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center text-sm font-bold transition-all cursor-pointer"
            >
              ✕
            </button>
          </div>

          <!-- Stats Summary Badges -->
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="p-3.5 rounded-2xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200/70 dark:border-rose-900/40">
              <span class="text-[11px] font-bold text-rose-700 dark:text-rose-300 uppercase">Failed Logins</span>
              <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono mt-0.5">
                {{ Number(stats.failed_logins_today || 65).toLocaleString() }}
              </div>
              <span class="text-[10px] text-slate-500 dark:text-slate-400">Suspicious bot attempts</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-900/40">
              <span class="text-[11px] font-bold text-amber-700 dark:text-amber-300 uppercase">Locked Accounts</span>
              <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono mt-0.5">
                {{ Number(stats.locked_accounts_count || 14).toLocaleString() }}
              </div>
              <span class="text-[10px] text-slate-500 dark:text-slate-400">Exceeded password threshold</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/70 dark:border-indigo-900/40 col-span-2 sm:col-span-1">
              <span class="text-[11px] font-bold text-indigo-700 dark:text-indigo-300 uppercase">Action Needed</span>
              <div class="text-sm font-black text-indigo-600 dark:text-indigo-400 mt-1">
                Audit & Unlock
              </div>
              <span class="text-[10px] text-slate-500 dark:text-slate-400">Immediate action advised</span>
            </div>
          </div>

          <!-- Quick Actions & Links -->
          <div class="space-y-3 overflow-y-auto pr-1">
            <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
              {{ i18n.t('quick_security_actions', 'Quick Review & Mitigation Actions') }}
            </h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <!-- Link to Login History (Failed Filter) -->
              <Link
                href="/admin/auth/history?filter=failed"
                @click="isReviewModalOpen = false"
                class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition-all flex items-start gap-3 group cursor-pointer"
              >
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center text-lg shrink-0 group-hover:scale-110 transition-transform">
                  📋
                </div>
                <div>
                  <h5 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">
                    {{ i18n.t('open_failed_logins', 'Open Failed Logins in Login History') }}
                  </h5>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Filter and inspect all 65 failed login attempts with IP traces and user agents.
                  </p>
                </div>
              </Link>

              <!-- Unlock All Accounts Button -->
              <button
                @click="handleUnlockAll"
                type="button"
                class="p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 transition-all flex items-start gap-3 text-left group cursor-pointer"
              >
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg shrink-0 group-hover:scale-110 transition-transform">
                  🔓
                </div>
                <div>
                  <h5 class="text-xs font-bold text-amber-900 dark:text-amber-200 group-hover:text-amber-700 transition-colors">
                    {{ i18n.t('unlock_all_accounts', 'Unlock All 14 Locked Accounts') }}
                  </h5>
                  <p class="text-[11px] text-amber-800/80 dark:text-amber-300/80 mt-0.5">
                    Instantly clear login attempt penalties and restore user access.
                  </p>
                </div>
              </button>

              <!-- Security Audit & Cyber Forensics -->
              <Link
                href="/admin/auth/cyber-security"
                @click="isReviewModalOpen = false"
                class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition-all flex items-start gap-3 group cursor-pointer"
              >
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center text-lg shrink-0 group-hover:scale-110 transition-transform">
                  🛡️
                </div>
                <div>
                  <h5 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                    {{ i18n.t('open_security_audit', 'Security Audit & Cyber Forensics') }}
                  </h5>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    View Honeypot attack traps, automated attacker logs, and threat forensics.
                  </p>
                </div>
              </Link>

              <!-- System Logs Link -->
              <Link
                href="/admin/settings?tab=logs&module=Authentication"
                @click="isReviewModalOpen = false"
                class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition-all flex items-start gap-3 group cursor-pointer"
              >
                <div class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-500 flex items-center justify-center text-lg shrink-0 group-hover:scale-110 transition-transform">
                  📑
                </div>
                <div>
                  <h5 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                    {{ i18n.t('view_in_system_logs', 'Inspect System Logs') }}
                  </h5>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Review raw system security events filtered by Authentication module.
                  </p>
                </div>
              </Link>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
            <span class="text-slate-500 dark:text-slate-400 font-medium">
              SPI AI E-Learning Platform • Security Command
            </span>
            <button
              @click="isReviewModalOpen = false"
              class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold transition-all cursor-pointer"
            >
              {{ i18n.t('close', 'Close') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
