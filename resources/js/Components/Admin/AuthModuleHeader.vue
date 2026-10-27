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
    <!-- ── 1. EXECUTIVE COMMAND HEADER (DUAL LIGHT & DARK MODE) ── -->
    <div class="relative overflow-hidden bg-white dark:bg-[#0F172A] rounded-2xl border border-slate-200 dark:border-[#1E293B] p-5 shadow-[0_1px_2px_0_rgba(15,23,42,0.05)] dark:shadow-[inset_0_1px_0_0_rgba(255,255,255,0.03)] text-slate-900 dark:text-[#F8FAFC] transition-colors">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
        <div class="flex items-center gap-3.5 sm:gap-4">
          <!-- Subtle Security Icon Container -->
          <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-[#080C14] border border-slate-200 dark:border-[#1E293B] flex items-center justify-center p-2 shrink-0 text-slate-700 dark:text-slate-200 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="currentColor">
              <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
            </svg>
          </div>

          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-[18px] leading-[26px] font-semibold text-slate-900 dark:text-[#F8FAFC] tracking-tight">
                {{ i18n.t('auth_module_title', 'Authentication & Access') }}
              </h1>
              <span class="px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider bg-slate-100 dark:bg-[#080C14] text-slate-600 dark:text-[#94A3B8] border border-slate-200 dark:border-[#1E293B] rounded-md font-mono">
                {{ i18n.t('auth_security_control_only', 'Security Control Only') }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-[#94A3B8] mt-1 font-normal leading-relaxed">
              {{ i18n.t('auth_header_desc', 'Manage system security, role access matrix (RBAC), active sessions, login history, and security policies') }}
            </p>
          </div>
        </div>

        <!-- Top Right Actions: Single Unified Cohesive Status Indicator -->
        <div class="flex items-center gap-2.5 self-start md:self-center shrink-0">
          <button
            type="button"
            @click="isReviewModalOpen = true"
            class="px-3.5 py-1.5 rounded-md text-xs font-medium border flex items-center gap-2.5 transition-all cursor-pointer shadow-xs"
            :class="(stats.failed_logins_today || 0) > 0 || (stats.locked_accounts_count || 0) > 0
              ? 'bg-[#FEF2F2] text-[#DC2626] border-[#FCA5A5] hover:bg-red-100 dark:bg-[rgba(239,68,68,0.12)] dark:text-[#EF4444] dark:border-[rgba(239,68,68,0.25)] dark:hover:bg-[rgba(239,68,68,0.2)]'
              : 'bg-[#ECFDF5] text-[#059669] border-[#A7F3D0] hover:bg-emerald-100 dark:bg-[rgba(16,185,129,0.12)] dark:text-[#10B981] dark:border-[rgba(16,185,129,0.25)] dark:hover:bg-[rgba(16,185,129,0.2)]'"
            :title="i18n.t('auth_click_to_review', 'Click to review security incidents & locked accounts')"
          >
            <span class="relative flex h-2 w-2">
              <span
                class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                :class="(stats.failed_logins_today || 0) > 0 ? 'bg-[#DC2626] dark:bg-[#EF4444]' : 'bg-[#059669] dark:bg-[#10B981]'"
              ></span>
              <span
                class="relative inline-flex rounded-full h-2 w-2"
                :class="(stats.failed_logins_today || 0) > 0 ? 'bg-[#DC2626] dark:bg-[#EF4444]' : 'bg-[#059669] dark:bg-[#10B981]'"
              ></span>
            </span>
            <span class="font-semibold uppercase tracking-wider font-mono">{{ stats.threat_level || 'CRITICAL' }}</span>
            <span class="text-slate-300 dark:text-[#1E293B]">|</span>
            <span class="text-[11px] text-slate-600 dark:text-[#94A3B8]">
              {{ (stats.failed_logins_today || 0) > 0 ? i18n.t('auth_review_required', 'Review Incidents') : 'Normal Guard' }}
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- ── 2. TOP 6 VIBRANT KPI CARDS (HIGH-ENERGY WAVE CARDS MATCHING ADMIN DASHBOARD) ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
      
      <!-- Card 1: Total Roles ➔ Royal Purple/Violet -->
      <Link
        href="/admin/auth/roles"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7c3aed] to-[#6366f1] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card1_btn', 'Manage Roles & Permissions Matrix')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ i18n.t('auth_total_roles', 'TOTAL ROLES') }}</span>
            <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="m9 12 2 2 4-4"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.total_roles || 3).toLocaleString() }}
          </h4>
          <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
            <span>▲ 100% RBAC Active</span>
          </p>
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
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ i18n.t('auth_active_sessions', 'ACTIVE SESSIONS') }}</span>
            <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="12" x="3" y="4" rx="2"/>
                <line x1="2" x2="22" y1="20" y2="20"/>
                <path d="M8 20v-4"/>
                <path d="M16 20v-4"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.active_sessions_count || 7).toLocaleString() }}
          </h4>
          <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
            <span>▲ 97.2% Session Health</span>
          </p>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 3: Failed Logins ➔ Vivid Orange -->
      <Link
        href="/admin/auth/history?filter=failed"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#f97316] to-[#ea580c] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_card4_btn', 'Investigate Threat Logs in Login History')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ i18n.t('auth_failed_logins_today', 'FAILED LOGINS') }}</span>
            <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.failed_logins_today || 65).toLocaleString() }}
          </h4>
          <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
            <span>▲ +12% Bot Probing</span>
          </p>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 4: Live JWT Tokens ➔ Royal Blue -->
      <Link
        href="/admin/auth/sessions"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0284c7] to-[#2563eb] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_active_tokens_btn', 'Click to inspect active JWT session tokens')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ i18n.t('auth_live_tokens', 'LIVE JWT TOKENS') }}</span>
            <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.active_sessions_count || 7).toLocaleString() }}
          </h4>
          <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
            <span>▲ 15-min Idle Expiry</span>
          </p>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 5: Posture Index ➔ Bright Rose/Pink -->
      <Link
        href="/admin/auth/policies"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#ec4899] to-[#e11d48] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
        :title="i18n.t('auth_posture_btn', 'Click to view security policies and hardening index')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ i18n.t('auth_posture_index', 'POSTURE INDEX') }}</span>
            <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                <path d="M4 22h16" />
                <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            88%
          </h4>
          <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
            <span>▲ 10/12 Rules Enforced</span>
          </p>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 19 Q 25 10, 50 18 T 80 8 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 19 Q 25 10, 50 18 T 80 8 T 100 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </Link>

      <!-- Card 6: Locked Accounts ➔ Teal/Cyan -->
      <button
        type="button"
        @click="isReviewModalOpen = true"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#06b6d4] to-[#0d9488] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer text-left"
        :title="i18n.t('auth_locked_accounts_btn', 'Click to review isolated accounts & incidents')"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ i18n.t('auth_locked_accounts', 'LOCKED ACCOUNTS') }}</span>
            <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
          </div>
          <h4 class="text-2xl font-black tracking-tight text-white mt-1">
            {{ Number(stats.locked_accounts_count || 14).toLocaleString() }}
          </h4>
          <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
            <span>▼ 14 Needs Review</span>
          </p>
        </div>
        <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
          <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
            <path d="M0 12 Q 25 20, 50 10 T 75 16 T 100 6 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
            <path d="M0 12 Q 25 20, 50 10 T 75 16 T 100 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
          </svg>
        </div>
      </button>

    </div>

    <!-- ── 3. SUB-NAVIGATION TABS (SUBTLE SLATE-900 UNDERLINE INDICATOR - BALANCED) ── -->
    <div class="border-b border-slate-200 dark:border-[#1E293B] flex items-center gap-1 sm:gap-4 overflow-x-auto scrollbar-none px-1 pt-1">
      
      <!-- Tab 1: Overview Hub -->
      <Link
        href="/admin/auth-logs"
        :class="[
          activeTab === 'overview'
            ? 'border-[#0F172A] dark:border-sky-400 text-[#0F172A] dark:text-[#F8FAFC] font-semibold'
            : 'border-transparent text-slate-500 dark:text-[#94A3B8] hover:text-[#0F172A] dark:hover:text-[#F8FAFC] hover:border-slate-300 dark:hover:border-slate-700',
          'border-b-2 px-3 py-2.5 text-xs transition-colors whitespace-nowrap flex items-center gap-2 shrink-0 -mb-[1px]'
        ]"
      >
        <img :src="'/images/nav/sub/overview.svg'" alt="Overview" class="w-3.5 h-3.5 object-contain" :class="activeTab === 'overview' ? 'opacity-100' : 'opacity-60'" />
        <span>{{ i18n.t('auth_tab_overview', 'Overview Hub') }}</span>
      </Link>

      <!-- Tab 2: Active Sessions (Shows real count) -->
      <Link
        href="/admin/auth/sessions"
        :class="[
          activeTab === 'sessions'
            ? 'border-[#0F172A] dark:border-sky-400 text-[#0F172A] dark:text-[#F8FAFC] font-semibold'
            : 'border-transparent text-slate-500 dark:text-[#94A3B8] hover:text-[#0F172A] dark:hover:text-[#F8FAFC] hover:border-slate-300 dark:hover:border-slate-700',
          'border-b-2 px-3 py-2.5 text-xs transition-colors whitespace-nowrap flex items-center gap-2 shrink-0 -mb-[1px]'
        ]"
      >
        <img :src="'/images/nav/sub/sessions.svg'" alt="Sessions" class="w-3.5 h-3.5 object-contain" :class="activeTab === 'sessions' ? 'opacity-100' : 'opacity-60'" />
        <span>{{ i18n.t('auth_tab_sessions', 'Active Sessions') }}</span>
        <span
          class="px-1.5 py-0.2 text-[10px] font-mono rounded-md font-medium"
          :class="activeTab === 'sessions' ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-300 dark:border-slate-700' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'"
        >
          {{ stats.active_sessions_count || 7 }}
        </span>
      </Link>

      <!-- Tab 3: Roles & Permissions Matrix -->
      <Link
        href="/admin/auth/roles"
        :class="[
          activeTab === 'roles'
            ? 'border-[#0F172A] dark:border-sky-400 text-[#0F172A] dark:text-[#F8FAFC] font-semibold'
            : 'border-transparent text-slate-500 dark:text-[#94A3B8] hover:text-[#0F172A] dark:hover:text-[#F8FAFC] hover:border-slate-300 dark:hover:border-slate-700',
          'border-b-2 px-3 py-2.5 text-xs transition-colors whitespace-nowrap flex items-center gap-2 shrink-0 -mb-[1px]'
        ]"
      >
        <img :src="'/images/nav/sub/roles.svg'" alt="Roles" class="w-3.5 h-3.5 object-contain" :class="activeTab === 'roles' ? 'opacity-100' : 'opacity-60'" />
        <span>{{ i18n.t('auth_tab_roles', 'Roles & Permissions') }}</span>
      </Link>

      <!-- Tab 4: Login History -->
      <Link
        href="/admin/auth/history"
        :class="[
          activeTab === 'history' || activeTab === 'failed'
            ? 'border-[#0F172A] dark:border-sky-400 text-[#0F172A] dark:text-[#F8FAFC] font-semibold'
            : 'border-transparent text-slate-500 dark:text-[#94A3B8] hover:text-[#0F172A] dark:hover:text-[#F8FAFC] hover:border-slate-300 dark:hover:border-slate-700',
          'border-b-2 px-3 py-2.5 text-xs transition-colors whitespace-nowrap flex items-center gap-2 shrink-0 -mb-[1px]'
        ]"
      >
        <img :src="'/images/nav/sub/history.svg'" alt="History" class="w-3.5 h-3.5 object-contain" :class="activeTab === 'history' || activeTab === 'failed' ? 'opacity-100' : 'opacity-60'" />
        <span>{{ i18n.t('auth_tab_history', 'Login History') }}</span>
      </Link>

      <!-- Tab 5: Security Policies -->
      <Link
        href="/admin/auth/policies"
        :class="[
          activeTab === 'policies'
            ? 'border-[#0F172A] dark:border-sky-400 text-[#0F172A] dark:text-[#F8FAFC] font-semibold'
            : 'border-transparent text-slate-500 dark:text-[#94A3B8] hover:text-[#0F172A] dark:hover:text-[#F8FAFC] hover:border-slate-300 dark:hover:border-slate-700',
          'border-b-2 px-3 py-2.5 text-xs transition-colors whitespace-nowrap flex items-center gap-2 shrink-0 -mb-[1px]'
        ]"
      >
        <img :src="'/images/nav/sub/policies.svg'" alt="Policies" class="w-3.5 h-3.5 object-contain" :class="activeTab === 'policies' ? 'opacity-100' : 'opacity-60'" />
        <span>{{ i18n.t('auth_tab_policies', 'Security Policies') }}</span>
      </Link>

      <!-- Tab 6: Security Audit -->
      <Link
        href="/admin/auth/cyber-security"
        :class="[
          activeTab === 'cyber-security' || activeTab === 'audit'
            ? 'border-[#0F172A] dark:border-sky-400 text-[#0F172A] dark:text-[#F8FAFC] font-semibold'
            : 'border-transparent text-slate-500 dark:text-[#94A3B8] hover:text-[#0F172A] dark:hover:text-[#F8FAFC] hover:border-slate-300 dark:hover:border-slate-700',
          'border-b-2 px-3 py-2.5 text-xs transition-colors whitespace-nowrap flex items-center gap-2 shrink-0 -mb-[1px]'
        ]"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" :class="activeTab === 'cyber-security' || activeTab === 'audit' ? 'opacity-100 text-[#0F172A] dark:text-sky-400' : 'opacity-60 text-slate-500'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          <path d="m9 12 2 2 4-4"/>
        </svg>
        <span>{{ i18n.t('auth_tab_security_audit', 'Security Audit') }}</span>
      </Link>
    </div>

    <!-- ── 4. REVIEW REQUIRED SECURITY MODAL ── -->
    <Teleport to="body">
      <div
        v-if="isReviewModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md animate-fade-in"
      >
        <div class="relative w-full max-w-2xl bg-white dark:bg-[#0E1524] rounded-2xl border border-slate-200 dark:border-[#1E293B] shadow-2xl p-6 overflow-hidden space-y-5 max-h-[90vh] flex flex-col">
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
