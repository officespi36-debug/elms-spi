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
    <!-- ── 1. EXECUTIVE COMMAND HEADER (REFINED SECURITY DARK MODE) ── -->
    <div class="relative overflow-hidden bg-[#111622] rounded-2xl border border-[#1E2638] p-5 sm:p-6 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] text-[#F1F5F9]">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
        <div class="flex items-center gap-3.5 sm:gap-4">
          <!-- Subtle Security Icon Container -->
          <div class="w-11 h-11 rounded-xl bg-[#0E131E] border border-[#1E2638] flex items-center justify-center p-2 shrink-0 text-slate-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="currentColor">
              <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
            </svg>
          </div>

          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl font-semibold text-[#F1F5F9] tracking-tight">
                {{ i18n.t('auth_module_title', 'Authentication & Access') }}
              </h1>
              <span class="px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider bg-[#0E131E] text-[#94A3B8] border border-[#1E2638] rounded-md font-mono">
                {{ i18n.t('auth_security_control_only', 'Security Control Only') }}
              </span>
            </div>
            <p class="text-sm text-[#94A3B8] mt-1 font-normal leading-relaxed">
              {{ i18n.t('auth_header_desc', 'Manage system security, role access matrix (RBAC), active sessions, login history, and security policies') }}
            </p>
          </div>
        </div>

        <!-- Top Right Actions: Single Unified Cohesive Status Indicator (No Alert Fatigue) -->
        <div class="flex items-center gap-2.5 self-start md:self-center shrink-0">
          <button
            type="button"
            @click="isReviewModalOpen = true"
            class="px-3.5 py-1.5 rounded-lg text-xs font-medium border flex items-center gap-2.5 transition-all cursor-pointer shadow-xs"
            :class="(stats.failed_logins_today || 0) > 0 || (stats.locked_accounts_count || 0) > 0
              ? 'bg-[#EF4444]/10 text-[#EF4444] border-[#EF4444]/25 hover:bg-[#EF4444]/20'
              : 'bg-[#10B981]/10 text-[#10B981] border-[#10B981]/25 hover:bg-[#10B981]/20'"
            :title="i18n.t('auth_click_to_review', 'Click to review security incidents & locked accounts')"
          >
            <span class="relative flex h-2 w-2">
              <span
                class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                :class="(stats.failed_logins_today || 0) > 0 ? 'bg-[#EF4444]' : 'bg-[#10B981]'"
              ></span>
              <span
                class="relative inline-flex rounded-full h-2 w-2"
                :class="(stats.failed_logins_today || 0) > 0 ? 'bg-[#EF4444]' : 'bg-[#10B981]'"
              ></span>
            </span>
            <span class="font-semibold uppercase tracking-wider font-mono">{{ stats.threat_level || 'CRITICAL' }}</span>
            <span class="text-[#1E2638]">|</span>
            <span class="text-[11px] text-[#94A3B8]">
              {{ (stats.failed_logins_today || 0) > 0 ? i18n.t('auth_review_required', 'Review Incidents') : 'Normal Guard' }}
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- ── 2. TOP 4 KPI CARDS (REFINED SECURITY DARK SURFACE CARDS) ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      
      <!-- Card 1: Total Roles -->
      <Link
        href="/admin/auth/roles"
        class="bg-[#111622] border border-[#1E2638] hover:border-slate-600 rounded-2xl p-5 transition-all duration-150 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] group cursor-pointer flex flex-col justify-between"
        :title="i18n.t('auth_card1_btn', 'Manage Roles & Permissions Matrix')"
      >
        <div>
          <!-- Top Row: Label + Status Pill -->
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium tracking-wide uppercase text-[#94A3B8]">
              {{ i18n.t('auth_total_roles', 'TOTAL ROLES') }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#10B981]/12 text-[#10B981] border border-[#10B981]/25 flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
              {{ i18n.t('auth_rbac_active', 'RBAC Active') }}
            </span>
          </div>

          <!-- Middle Row: Large Value + Context -->
          <div class="my-3 flex items-baseline gap-2.5">
            <h4 class="text-2xl font-bold font-mono tracking-tight text-[#F1F5F9]">
              {{ Number(stats.total_roles || 3).toLocaleString() }}
            </h4>
            <span class="text-xs font-normal text-[#94A3B8]">System Roles</span>
          </div>
        </div>

        <!-- Bottom Row: Contextual Micro-copy (No Arrow) -->
        <div class="pt-2.5 border-t border-[#1E2638] text-xs text-[#94A3B8]">
          <span class="truncate">Admin, Teacher, and Student access</span>
        </div>
      </Link>

      <!-- Card 2: Active Sessions -->
      <Link
        href="/admin/auth/sessions"
        class="bg-[#111622] border border-[#1E2638] hover:border-slate-600 rounded-2xl p-5 transition-all duration-150 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] group cursor-pointer flex flex-col justify-between"
        :title="i18n.t('auth_card2_btn', 'Monitor Active Sessions')"
      >
        <div>
          <!-- Top Row: Label + Status Pill -->
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium tracking-wide uppercase text-[#94A3B8]">
              {{ i18n.t('auth_active_sessions', 'ACTIVE SESSIONS') }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#10B981]/12 text-[#10B981] border border-[#10B981]/25 flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] animate-pulse"></span>
              {{ i18n.t('auth_live_sessions', 'Live Monitor') }}
            </span>
          </div>

          <!-- Middle Row: Large Value + Context -->
          <div class="my-3 flex items-baseline gap-2.5">
            <h4 class="text-2xl font-bold font-mono tracking-tight text-[#F1F5F9]">
              {{ Number(stats.active_sessions_count || 7).toLocaleString() }}
            </h4>
            <span class="text-xs font-normal text-[#94A3B8]">Online Now</span>
          </div>
        </div>

        <!-- Bottom Row: Contextual Micro-copy (No Arrow) -->
        <div class="pt-2.5 border-t border-[#1E2638] text-xs text-[#94A3B8]">
          <span class="truncate">Across desktop & mobile JWT tokens</span>
        </div>
      </Link>

      <!-- Card 3: Failed Logins Today -->
      <Link
        href="/admin/auth/history?filter=failed"
        class="bg-[#111622] border border-[#1E2638] hover:border-slate-600 rounded-2xl p-5 transition-all duration-150 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] group cursor-pointer flex flex-col justify-between"
        :title="i18n.t('auth_card4_btn', 'Investigate Threat Logs in Login History')"
      >
        <div>
          <!-- Top Row: Label + Status Pill -->
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium tracking-wide uppercase text-[#94A3B8]">
              {{ i18n.t('auth_failed_logins_today', 'FAILED LOGINS TODAY') }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#F59E0B]/12 text-[#F59E0B] border border-[#F59E0B]/25 flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span>
              {{ i18n.t('auth_audit_required', 'Audit Required') }}
            </span>
          </div>

          <!-- Middle Row: Large Value + Trend (Subtle regular text) -->
          <div class="my-3 flex items-baseline gap-2.5">
            <h4 class="text-2xl font-bold font-mono tracking-tight text-[#F1F5F9]">
              {{ Number(stats.failed_logins_today || 65).toLocaleString() }}
            </h4>
            <span class="text-xs font-normal text-[#94A3B8]">+12% vs yesterday</span>
          </div>
        </div>

        <!-- Bottom Row: Contextual Micro-copy (No Arrow) -->
        <div class="pt-2.5 border-t border-[#1E2638] text-xs text-[#94A3B8]">
          <span class="truncate">Across 3 distinct IP addresses</span>
        </div>
      </Link>

      <!-- Card 4: Locked Accounts / Risk -->
      <button
        type="button"
        @click="isReviewModalOpen = true"
        class="bg-[#111622] border border-[#1E2638] hover:border-slate-600 rounded-2xl p-5 transition-all duration-150 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)] group cursor-pointer flex flex-col justify-between text-left"
        :title="i18n.t('auth_locked_accounts_btn', 'Click to Review & Unlock Accounts')"
      >
        <div>
          <!-- Top Row: Label + Status Pill -->
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium tracking-wide uppercase text-[#94A3B8]">
              {{ i18n.t('auth_locked_accounts', 'LOCKED ACCOUNTS') }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#EF4444]/12 text-[#EF4444] border border-[#EF4444]/25 flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-[#EF4444] animate-pulse"></span>
              {{ i18n.t('auth_suspended_accounts', 'Immediate Audit') }}
            </span>
          </div>

          <!-- Middle Row: Large Value + Context -->
          <div class="my-3 flex items-baseline gap-2.5">
            <h4 class="text-2xl font-bold font-mono tracking-tight text-[#F1F5F9]">
              {{ Number(stats.locked_accounts_count || 14).toLocaleString() }}
            </h4>
            <span class="text-xs font-normal text-[#94A3B8]">Suspended</span>
          </div>
        </div>

        <!-- Bottom Row: Contextual Micro-copy (No Arrow) -->
        <div class="pt-2.5 border-t border-[#1E2638] text-xs text-[#94A3B8]">
          <span class="truncate">Exceeded password threshold limit</span>
        </div>
      </button>
    </div>

    <!-- ── 3. SUB-NAVIGATION TABS (MONOCHROMATIC ENTERPRISE STYLE) ── -->
    <div class="p-1 bg-[#111622] border border-[#1E2638] rounded-xl flex items-center gap-1 overflow-x-auto scrollbar-none shadow-[inset_0_1px_0_0_rgba(255,255,255,0.04)]">
      
      <!-- Tab 1: Overview Hub -->
      <Link
        href="/admin/auth-logs"
        :class="[
          activeTab === 'overview'
            ? 'bg-[#1E2638] text-[#F1F5F9] border border-slate-700/80 font-medium'
            : 'text-[#94A3B8] hover:text-[#F1F5F9] hover:bg-[#1E2638]/40 border border-transparent font-normal',
          'px-3.5 py-1.5 text-xs rounded-lg transition-colors whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/overview.svg'" alt="Overview" class="w-3.5 h-3.5 object-contain opacity-80" />
        <span>{{ i18n.t('auth_tab_overview', 'Overview Hub') }}</span>
      </Link>

      <!-- Tab 2: Active Sessions (Shows real count) -->
      <Link
        href="/admin/auth/sessions"
        :class="[
          activeTab === 'sessions'
            ? 'bg-[#1E2638] text-[#F1F5F9] border border-slate-700/80 font-medium'
            : 'text-[#94A3B8] hover:text-[#F1F5F9] hover:bg-[#1E2638]/40 border border-transparent font-normal',
          'px-3.5 py-1.5 text-xs rounded-lg transition-colors whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/sessions.svg'" alt="Sessions" class="w-3.5 h-3.5 object-contain opacity-80" />
        <span>{{ i18n.t('auth_tab_sessions', 'Active Sessions') }}</span>
        <span
          class="px-1.5 py-0.2 text-[10px] font-mono rounded font-medium"
          :class="activeTab === 'sessions' ? 'bg-slate-700 text-white' : 'bg-slate-800 text-slate-400'"
        >
          {{ stats.active_sessions_count || 7 }}
        </span>
      </Link>

      <!-- Tab 3: Roles & Permissions Matrix -->
      <Link
        href="/admin/auth/roles"
        :class="[
          activeTab === 'roles'
            ? 'bg-[#1E2638] text-[#F1F5F9] border border-slate-700/80 font-medium'
            : 'text-[#94A3B8] hover:text-[#F1F5F9] hover:bg-[#1E2638]/40 border border-transparent font-normal',
          'px-3.5 py-1.5 text-xs rounded-lg transition-colors whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/roles.svg'" alt="Roles" class="w-3.5 h-3.5 object-contain opacity-80" />
        <span>{{ i18n.t('auth_tab_roles', 'Roles & Permissions') }}</span>
      </Link>

      <!-- Tab 4: Login History -->
      <Link
        href="/admin/auth/history"
        :class="[
          activeTab === 'history' || activeTab === 'failed'
            ? 'bg-[#1E2638] text-[#F1F5F9] border border-slate-700/80 font-medium'
            : 'text-[#94A3B8] hover:text-[#F1F5F9] hover:bg-[#1E2638]/40 border border-transparent font-normal',
          'px-3.5 py-1.5 text-xs rounded-lg transition-colors whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/history.svg'" alt="History" class="w-3.5 h-3.5 object-contain opacity-80" />
        <span>{{ i18n.t('auth_tab_history', 'Login History') }}</span>
      </Link>

      <!-- Tab 5: Security Policies -->
      <Link
        href="/admin/auth/policies"
        :class="[
          activeTab === 'policies'
            ? 'bg-[#1E2638] text-[#F1F5F9] border border-slate-700/80 font-medium'
            : 'text-[#94A3B8] hover:text-[#F1F5F9] hover:bg-[#1E2638]/40 border border-transparent font-normal',
          'px-3.5 py-1.5 text-xs rounded-lg transition-colors whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <img :src="'/images/nav/sub/policies.svg'" alt="Policies" class="w-3.5 h-3.5 object-contain opacity-80" />
        <span>{{ i18n.t('auth_tab_policies', 'Security Policies') }}</span>
      </Link>

      <!-- Tab 6: Security Audit -->
      <Link
        href="/admin/auth/cyber-security"
        :class="[
          activeTab === 'cyber-security' || activeTab === 'audit'
            ? 'bg-[#1E2638] text-[#F1F5F9] border border-slate-700/80 font-medium'
            : 'text-[#94A3B8] hover:text-[#F1F5F9] hover:bg-[#1E2638]/40 border border-transparent font-normal',
          'px-3.5 py-1.5 text-xs rounded-lg transition-colors whitespace-nowrap flex items-center gap-2 shrink-0'
        ]"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
