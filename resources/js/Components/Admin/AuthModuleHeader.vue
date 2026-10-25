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
      return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30'
    case 'high':
      return 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/30'
    case 'medium':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30'
    default:
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
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
    <!-- ── 1. APPLE / TELEGRAM LIQUID GLASS HEADER ── -->
    <div class="relative overflow-hidden bg-white/80 dark:bg-slate-900/80 rounded-3xl border border-slate-200/80 dark:border-slate-800/80 p-5 sm:p-6 shadow-sm backdrop-blur-2xl transition-all">
      <!-- Subtle Ambient Lighting Glow -->
      <div class="absolute -top-16 -right-16 w-64 h-64 bg-gradient-to-br from-[#007AFF]/15 to-[#5856D6]/10 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-gradient-to-tr from-[#34C759]/10 to-[#007AFF]/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
        <div class="flex items-center gap-3.5 sm:gap-4">
          <!-- iOS Telegram Squircle Icon -->
          <div class="relative w-14 h-14 rounded-2xl bg-gradient-to-br from-[#007AFF] via-[#0A84FF] to-[#5856D6] flex items-center justify-center p-2.5 shrink-0 shadow-lg shadow-blue-500/25 border border-white/30 group">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
              <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
            </svg>
            <!-- Telegram subtle verified shine badge -->
            <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 border-2 border-white dark:border-slate-900 flex items-center justify-center text-white text-[9px] font-bold shadow-xs">
              ✓
            </span>
          </div>

          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-1.5">
                <span>{{ i18n.t('auth_module_title', 'Authentication & Access') }}</span>
                <!-- Telegram-style verified badge -->
                <svg class="w-5 h-5 text-[#007AFF] shrink-0" viewBox="0 0 24 24" fill="currentColor" title="Verified Security Module">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.5 14.5l-4-4 1.41-1.41L10.5 13.67l6.59-6.59L18.5 8.5l-8 8z"/>
                </svg>
              </h1>
              <span class="px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider bg-blue-50 text-[#007AFF] dark:bg-blue-500/20 dark:text-blue-300 border border-blue-200/80 dark:border-blue-500/30 rounded-full">
                {{ i18n.t('auth_security_control_only', 'Security & Access Control') }}
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl font-medium leading-relaxed">
              {{ i18n.t('auth_header_desc', 'Manage system security, roles & permissions (RBAC), active sessions, and access logs') }}
            </p>
          </div>
        </div>

        <!-- Top Right Actions: Telegram / iOS Pill Badges -->
        <div class="flex items-center gap-2.5 self-start md:self-center shrink-0 flex-wrap">
          <!-- Telegram-style Network / Live Connection Pill -->
          <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/70 dark:border-slate-700/60 shadow-xs">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span class="text-[11px] font-medium">{{ i18n.t('live_synced', 'RBAC Guard Active') }}</span>
          </div>

          <!-- Review Required Pill Button -->
          <button
            v-if="(stats.failed_logins_today || 0) > 0 || (stats.locked_accounts_count || 0) > 0"
            @click="isReviewModalOpen = true"
            type="button"
            class="px-3.5 py-1.5 rounded-full text-xs font-bold border border-rose-300/80 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900/60 hover:shadow-sm transition-all flex items-center gap-2 cursor-pointer shadow-xs"
            :title="i18n.t('auth_click_to_review', 'Click to review security incidents & locked accounts')"
          >
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
            </span>
            <span>{{ i18n.t('auth_review_required', 'Review Required') }}</span>
          </button>

          <!-- Threat Status Pill with Dynamic Indicator -->
          <div class="relative group/threat cursor-help">
            <div
              class="px-3.5 py-1.5 rounded-full text-xs font-bold border flex items-center gap-2 transition-all shadow-xs backdrop-blur-md"
              :class="getThreatBadgeClass(stats.threat_level)"
            >
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="stats.threat_level === 'Low' ? 'bg-emerald-400' : 'bg-red-400'"></span>
                <span class="relative inline-flex rounded-full h-2 w-2" :class="stats.threat_level === 'Low' ? 'bg-emerald-500' : 'bg-red-500'"></span>
              </span>
              <span class="tracking-wide uppercase font-black">{{ stats.threat_level }} RISK</span>
            </div>

            <!-- iOS Tooltip popup on hover -->
            <div class="absolute right-0 top-full mt-2 w-72 p-3.5 bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-700 rounded-2xl shadow-xl text-xs text-slate-700 dark:text-slate-200 opacity-0 group-hover/threat:opacity-100 transition-all pointer-events-none group-hover/threat:pointer-events-auto z-50 backdrop-blur-2xl">
              <div class="font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800 pb-1.5">
                <span>🛡️ {{ i18n.t('auth_threat_status', 'Threat Status') }}</span>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal">
                {{ getThreatExplanation(stats.threat_level) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── 2. TOP 4 KPI CARDS (APPLE / TELEGRAM WIDGET AESTHETIC) ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      
      <!-- Card 1: Total Roles ➔ iOS Purple/Indigo Widget -->
      <Link
        href="/admin/auth/roles"
        class="relative overflow-hidden rounded-3xl bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/90 p-5 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group cursor-pointer backdrop-blur-xl"
        :title="i18n.t('auth_card1_btn', 'Manage Roles & Permissions in Sidebar')"
      >
        <div class="absolute -top-10 -right-10 w-28 h-28 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div>
          <!-- Top Bar: Squircle Icon & iOS Pill Badge -->
          <div class="flex items-center justify-between">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#7C3AED] via-[#6366F1] to-[#4F46E5] text-white flex items-center justify-center shrink-0 shadow-md shadow-indigo-500/25 border border-white/20 group-hover:scale-105 transition-transform duration-200">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="m9 12 2 2 4-4"/>
              </svg>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100/80 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 border border-purple-200/80 dark:border-purple-500/30 flex items-center gap-1 shadow-2xs">
              <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
              {{ i18n.t('auth_rbac_active', 'RBAC Active') }}
            </span>
          </div>

          <!-- Metric Number & Label -->
          <div class="mt-4">
            <span class="text-[11px] font-extrabold tracking-wider uppercase text-slate-400 dark:text-slate-500">
              {{ i18n.t('auth_total_roles', 'TOTAL ROLES') }}
            </span>
            <h4 class="text-3xl sm:text-4xl font-black tracking-tight text-slate-900 dark:text-white mt-0.5 font-sans">
              {{ Number(stats.total_roles || 3).toLocaleString() }}
            </h4>
          </div>
        </div>

        <!-- Footer Context -->
        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
          <span class="text-slate-500 dark:text-slate-400 font-medium">Configured System Roles</span>
          <span class="text-indigo-600 dark:text-indigo-400 font-bold group-hover:translate-x-1 transition-transform">→</span>
        </div>
      </Link>

      <!-- Card 2: Active Sessions ➔ iOS Emerald Green Widget -->
      <Link
        href="/admin/auth/sessions"
        class="relative overflow-hidden rounded-3xl bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/90 p-5 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group cursor-pointer backdrop-blur-xl"
        :title="i18n.t('auth_card2_btn', 'Monitor Active Sessions')"
      >
        <div class="absolute -top-10 -right-10 w-28 h-28 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div>
          <!-- Top Bar: Squircle Icon & iOS Live Pill Badge -->
          <div class="flex items-center justify-between">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#10B981] via-[#059669] to-[#047857] text-white flex items-center justify-center shrink-0 shadow-md shadow-emerald-500/25 border border-white/20 group-hover:scale-105 transition-transform duration-200">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="12" x="3" y="4" rx="2.5"/>
                <line x1="8" x2="16" y1="20" y2="20"/>
                <line x1="12" y1="12" x2="12" y2="16"/>
                <circle cx="12" cy="10" r="1.5" fill="currentColor"/>
              </svg>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100/80 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-500/30 flex items-center gap-1.5 shadow-2xs">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              {{ i18n.t('auth_live_sessions', '7 Live Online') }}
            </span>
          </div>

          <!-- Metric Number & Label -->
          <div class="mt-4">
            <span class="text-[11px] font-extrabold tracking-wider uppercase text-slate-400 dark:text-slate-500">
              {{ i18n.t('auth_active_sessions', 'ACTIVE SESSIONS') }}
            </span>
            <h4 class="text-3xl sm:text-4xl font-black tracking-tight text-slate-900 dark:text-white mt-0.5 font-sans">
              {{ Number(stats.active_sessions_count || 7).toLocaleString() }}
            </h4>
          </div>
        </div>

        <!-- Footer Context -->
        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
          <span class="text-slate-500 dark:text-slate-400 font-medium">Live Connected Tokens</span>
          <span class="text-emerald-600 dark:text-emerald-400 font-bold group-hover:translate-x-1 transition-transform">→</span>
        </div>
      </Link>

      <!-- Card 3: Failed Logins Today ➔ iOS Amber/Orange Widget -->
      <Link
        href="/admin/auth/history?filter=failed"
        class="relative overflow-hidden rounded-3xl bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/90 p-5 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group cursor-pointer backdrop-blur-xl"
        :title="i18n.t('auth_card4_btn', 'Investigate Threat Logs in Login History')"
      >
        <div class="absolute -top-10 -right-10 w-28 h-28 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div>
          <!-- Top Bar: Squircle Icon & iOS Pill Badge -->
          <div class="flex items-center justify-between">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#F59E0B] via-[#EA580C] to-[#D97706] text-white flex items-center justify-center shrink-0 shadow-md shadow-orange-500/25 border border-white/20 group-hover:scale-105 transition-transform duration-200">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <circle cx="12" cy="16" r="0.75" fill="currentColor"/>
              </svg>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100/80 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300 border border-amber-200/80 dark:border-amber-500/30 flex items-center gap-1 shadow-2xs">
              <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
              {{ i18n.t('auth_audit_required', 'Audit Required') }}
            </span>
          </div>

          <!-- Metric Number & Label -->
          <div class="mt-4">
            <span class="text-[11px] font-extrabold tracking-wider uppercase text-slate-400 dark:text-slate-500">
              {{ i18n.t('auth_failed_logins_today', 'FAILED LOGINS') }}
            </span>
            <h4 class="text-3xl sm:text-4xl font-black tracking-tight text-slate-900 dark:text-white mt-0.5 font-sans">
              {{ Number(stats.failed_logins_today || 65).toLocaleString() }}
            </h4>
          </div>
        </div>

        <!-- Footer Context -->
        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
          <span class="text-slate-500 dark:text-slate-400 font-medium">Threat Log Entries Today</span>
          <span class="text-amber-600 dark:text-amber-400 font-bold group-hover:translate-x-1 transition-transform">→</span>
        </div>
      </Link>

      <!-- Card 4: Locked Accounts / Risk ➔ iOS Rose/Coral Widget -->
      <button
        type="button"
        @click="isReviewModalOpen = true"
        class="relative overflow-hidden rounded-3xl bg-white/90 dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800/90 p-5 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group cursor-pointer backdrop-blur-xl text-left"
        :title="i18n.t('auth_locked_accounts_btn', 'Click to Review & Unlock Accounts')"
      >
        <div class="absolute -top-10 -right-10 w-28 h-28 bg-rose-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div>
          <!-- Top Bar: Squircle Icon & iOS Pill Badge -->
          <div class="flex items-center justify-between">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#F43F5E] via-[#E11D48] to-[#BE123C] text-white flex items-center justify-center shrink-0 shadow-md shadow-rose-500/25 border border-white/20 group-hover:scale-105 transition-transform duration-200">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
              </svg>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100/80 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300 border border-rose-200/80 dark:border-rose-500/30 flex items-center gap-1 shadow-2xs">
              <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
              {{ i18n.t('auth_suspended_accounts', '14 Suspended') }}
            </span>
          </div>

          <!-- Metric Number & Label -->
          <div class="mt-4">
            <span class="text-[11px] font-extrabold tracking-wider uppercase text-slate-400 dark:text-slate-500">
              {{ i18n.t('auth_locked_accounts', 'LOCKED ACCOUNTS') }}
            </span>
            <h4 class="text-3xl sm:text-4xl font-black tracking-tight text-slate-900 dark:text-white mt-0.5 font-sans">
              {{ Number(stats.locked_accounts_count || 14).toLocaleString() }}
            </h4>
          </div>
        </div>

        <!-- Footer Context -->
        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
          <span class="text-slate-500 dark:text-slate-400 font-medium">Click to Unlock All Accounts</span>
          <span class="text-rose-600 dark:text-rose-400 font-bold group-hover:translate-x-1 transition-transform">→</span>
        </div>
      </button>
    </div>

    <!-- ── 3. TELEGRAM / APPLE SEGMENTED PILL FILTER BAR (MATCHING IMAGE 1) ── -->
    <div class="p-1.5 bg-slate-200/60 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800/90 rounded-full flex items-center gap-1.5 overflow-x-auto scrollbar-none shadow-xs backdrop-blur-2xl">
      
      <!-- Tab 1: Overview Hub -->
      <Link
        href="/admin/auth-logs"
        :class="[
          activeTab === 'overview'
            ? 'bg-white text-slate-900 dark:bg-[#007AFF] dark:text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-full transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/overview.svg'" alt="Overview" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_overview', 'Overview Hub') }}</span>
        <span v-if="activeTab === 'overview'" class="w-1.5 h-1.5 rounded-full bg-[#007AFF] dark:bg-white animate-pulse"></span>
      </Link>

      <!-- Tab 2: Active Sessions (With Telegram Vibrant Blue Unread Pill Badge) -->
      <Link
        href="/admin/auth/sessions"
        :class="[
          activeTab === 'sessions'
            ? 'bg-white text-slate-900 dark:bg-[#007AFF] dark:text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-full transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/sessions.svg'" alt="Sessions" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_sessions', 'Active Sessions') }}</span>
        <!-- Telegram-style unread counter badge -->
        <span
          class="px-2 py-0.5 text-[11px] font-sans font-bold rounded-full min-w-[20px] text-center shadow-xs transition-colors"
          :class="activeTab === 'sessions' ? 'bg-[#007AFF] text-white dark:bg-white dark:text-[#007AFF]' : 'bg-[#007AFF] text-white'"
        >
          {{ stats.active_sessions_count || 7 }}
        </span>
      </Link>

      <!-- Tab 3: Login History -->
      <Link
        href="/admin/auth/history"
        :class="[
          activeTab === 'history' || activeTab === 'failed'
            ? 'bg-white text-slate-900 dark:bg-[#007AFF] dark:text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-full transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/history.svg'" alt="History" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_history', 'Login History') }}</span>
        <span
          class="px-2 py-0.5 text-[11px] font-sans font-bold rounded-full min-w-[20px] text-center shadow-xs"
          :class="activeTab === 'history' || activeTab === 'failed' ? 'bg-[#007AFF]/20 text-[#007AFF] dark:bg-white/20 dark:text-white' : 'bg-slate-300/80 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
        >
          225
        </span>
      </Link>

      <!-- Tab 4: Security Policies -->
      <Link
        href="/admin/auth/policies"
        :class="[
          activeTab === 'policies'
            ? 'bg-white text-slate-900 dark:bg-[#007AFF] dark:text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-full transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <img :src="'/images/nav/sub/policies.svg'" alt="Policies" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_policies', 'Security Policies') }}</span>
        <span v-if="activeTab === 'policies'" class="w-1.5 h-1.5 rounded-full bg-[#007AFF] dark:bg-white animate-pulse"></span>
      </Link>

      <!-- Tab 5: Security Audit -->
      <Link
        href="/admin/auth/cyber-security"
        :class="[
          activeTab === 'cyber-security' || activeTab === 'audit'
            ? 'bg-white text-slate-900 dark:bg-[#007AFF] dark:text-white shadow-md font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/60 font-semibold',
          'px-4 py-2 text-xs rounded-full transition-all duration-200 whitespace-nowrap flex items-center gap-2 shrink-0 group'
        ]"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <path d="m9 12 2 2 4-4"/>
        </svg>
        <span>{{ i18n.t('auth_tab_security_audit', 'Security Audit') }}</span>
        <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded-full bg-rose-500 text-white animate-pulse shadow-xs">
          LIVE
        </span>
      </Link>
    </div>

    <!-- ── 4. REVIEW REQUIRED SECURITY MODAL (APPLE DIALOG STYLE) ── -->
    <Teleport to="body">
      <div
        v-if="isReviewModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md animate-fade-in"
      >
        <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6 overflow-hidden space-y-5 max-h-[90vh] flex flex-col">
          <!-- Ambient Glow -->
          <div class="absolute -top-12 -right-12 w-48 h-48 bg-rose-500/10 rounded-full blur-2xl pointer-events-none"></div>

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
                  <span class="px-2.5 py-0.5 text-[10px] font-black uppercase rounded-full bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
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
            <div class="p-4 rounded-2xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200/70 dark:border-rose-900/40">
              <span class="text-[11px] font-bold text-rose-700 dark:text-rose-300 uppercase">Failed Logins</span>
              <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-sans mt-0.5">
                {{ Number(stats.failed_logins_today || 65).toLocaleString() }}
              </div>
              <span class="text-[10px] text-slate-500 dark:text-slate-400">Suspicious bot attempts</span>
            </div>
            <div class="p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-900/40">
              <span class="text-[11px] font-bold text-amber-700 dark:text-amber-300 uppercase">Locked Accounts</span>
              <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-sans mt-0.5">
                {{ Number(stats.locked_accounts_count || 14).toLocaleString() }}
              </div>
              <span class="text-[10px] text-slate-500 dark:text-slate-400">Exceeded password threshold</span>
            </div>
            <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/70 dark:border-indigo-900/40 col-span-2 sm:col-span-1">
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
              class="px-5 py-2 rounded-full bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold transition-all cursor-pointer"
            >
              {{ i18n.t('close', 'Close') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
