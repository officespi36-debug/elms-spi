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
      return 'bg-red-500/20 text-red-400 border-red-500/30 shadow-red-500/10'
    case 'high':
      return 'bg-orange-500/20 text-orange-400 border-orange-500/30 shadow-orange-500/10'
    case 'medium':
      return 'bg-amber-500/20 text-amber-300 border-amber-500/30 shadow-amber-500/10'
    default:
      return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30 shadow-emerald-500/10'
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
    <!-- Compact Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 p-4 sm:p-5 rounded-2xl border border-slate-800 shadow-xl relative">
      <!-- Glow Effect (Clipped to background layer only) -->
      <div class="absolute inset-0 rounded-2xl overflow-hidden pointer-events-none">
        <div class="absolute -top-12 -right-12 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl"></div>
      </div>

      <div class="flex items-center gap-3.5 z-10">
        <div class="w-11 h-11 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shadow-inner shrink-0">
          <img :src="'/images/nav/auth.svg'" alt="Authentication" class="w-6 h-6 object-contain" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-extrabold text-white tracking-tight">{{ i18n.t('auth_module_title', 'Authentication Module') }}</h1>
            <span class="px-2 py-0.5 text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-full uppercase tracking-wider">
              {{ i18n.t('auth_security_control_only', 'Security Control Only') }}
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-0.5 max-w-xl truncate">
            {{ i18n.t('auth_header_desc', 'គ្រប់គ្រងសុវត្ថិភាព សិទ្ធិប្រើប្រាស់កម្រិត Role (RBAC), Active Sessions, Login History, និង Security Policies') }}
          </p>
        </div>
      </div>

      <!-- Threat Status with Tooltip -->
      <div class="flex items-center gap-2 z-10">
        <div class="relative group/threat cursor-help">
          <span class="px-3 py-1 rounded-xl text-xs font-semibold border flex items-center gap-2 transition-all shadow-sm hover:border-slate-500" :class="getThreatBadgeClass(stats.threat_level)">
            <span class="w-2 h-2 rounded-full animate-ping" :class="stats.threat_level === 'Low' ? 'bg-emerald-400' : 'bg-red-400'"></span>
            <span>{{ i18n.t('auth_threat_status', 'Threat Status') }}: {{ stats.threat_level }}</span>
            <span class="text-[10px] opacity-70">ℹ️</span>
          </span>

          <!-- Tooltip dropdown on hover (Pop up cleanly below badge with upward arrow) -->
          <div class="absolute right-0 top-full mt-2.5 w-72 p-3 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl text-xs text-slate-200 opacity-0 group-hover/threat:opacity-100 transition-all pointer-events-none group-hover/threat:pointer-events-auto z-50">
            <!-- Upward pointing arrow tip -->
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

    <!-- Compact Top Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
      <!-- Card 1: Total Roles -->
      <div class="bg-slate-800/40 border border-slate-800 rounded-xl p-3.5 backdrop-blur-xl hover:border-slate-700 transition-all group shadow-sm">
        <div class="flex items-center justify-between text-slate-400">
          <span class="text-xs font-semibold text-slate-400">{{ i18n.t('auth_total_roles', 'Total Roles') }}</span>
          <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 group-hover:scale-110 transition-transform flex items-center justify-center">
            <img :src="'/images/nav/sub/roles.svg'" alt="Roles" class="w-4 h-4 object-contain" />
          </span>
        </div>
        <div class="mt-1.5 flex items-baseline justify-between">
          <span class="text-xl font-black text-white font-mono">{{ stats.total_roles }}</span>
          <span class="text-[10px] font-semibold px-2 py-0.5 bg-indigo-500/10 text-indigo-300 rounded-lg">{{ i18n.t('auth_rbac_active', 'RBAC Active') }}</span>
        </div>
      </div>

      <!-- Card 2: Active Sessions -->
      <div class="bg-slate-800/40 border border-slate-800 rounded-xl p-3.5 backdrop-blur-xl hover:border-slate-700 transition-all group shadow-sm">
        <div class="flex items-center justify-between text-slate-400">
          <span class="text-xs font-semibold text-slate-400">{{ i18n.t('auth_active_sessions', 'Active Sessions') }}</span>
          <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 group-hover:scale-110 transition-transform flex items-center justify-center">
            <img :src="'/images/nav/sub/sessions.svg'" alt="Active Sessions" class="w-4 h-4 object-contain" />
          </span>
        </div>
        <div class="mt-1.5 flex items-baseline justify-between">
          <span class="text-xl font-black text-white font-mono">{{ stats.active_sessions_count }}</span>
          <span class="text-[10px] font-semibold px-2 py-0.5 bg-emerald-500/10 text-emerald-300 rounded-lg flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> {{ i18n.t('auth_live_monitor', 'Live Monitor') }}
          </span>
        </div>
      </div>

      <!-- Card 3: Failed Logins Today -->
      <div class="bg-slate-800/40 border border-slate-800 rounded-xl p-3.5 backdrop-blur-xl hover:border-slate-700 transition-all group shadow-sm">
        <div class="flex items-center justify-between text-slate-400">
          <span class="text-xs font-semibold text-slate-400">{{ i18n.t('auth_failed_logins_today', 'Failed Logins Today') }}</span>
          <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 group-hover:scale-110 transition-transform flex items-center justify-center">
            <img :src="'/images/nav/sub/failed.svg'" alt="Failed Logins" class="w-4 h-4 object-contain" />
          </span>
        </div>
        <div class="mt-1.5 flex items-baseline justify-between">
          <span class="text-xl font-black text-white font-mono" :class="stats.failed_logins_today > 0 ? 'text-amber-400' : 'text-white'">
            {{ stats.failed_logins_today }}
          </span>
          
          <div class="relative group/audit cursor-help">
            <span class="text-[10px] font-semibold px-2 py-0.5 bg-amber-500/15 text-amber-300 border border-amber-500/30 rounded-lg flex items-center gap-1">
              <span>{{ i18n.t('auth_audit_required', 'Audit Required') }}</span>
              <span class="text-[9px] opacity-70">ℹ️</span>
            </span>
            <!-- Audit Tooltip -->
            <div class="absolute right-0 bottom-full mb-2 w-64 p-2.5 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl text-xs text-slate-200 opacity-0 group-hover/audit:opacity-100 transition-all pointer-events-none z-50">
              <p class="text-[11px] text-slate-300 leading-snug">
                🔍 {{ i18n.t('auth_audit_required', 'Audit Required') }}: {{ stats.failed_logins_today }} {{ i18n.t('auth_card4_attempts', 'Attempts') }}.
              </p>
              <div class="absolute -bottom-1.5 right-4 w-3 h-3 bg-slate-900 border-r border-b border-slate-700 rotate-45 pointer-events-none"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 4: Locked Accounts / Risk -->
      <div class="bg-slate-800/40 border border-slate-800 rounded-xl p-3.5 backdrop-blur-xl hover:border-slate-700 transition-all group shadow-sm">
        <div class="flex items-center justify-between text-slate-400">
          <span class="text-xs font-semibold text-slate-400">{{ i18n.t('auth_locked_accounts', 'Locked Accounts / Risk') }}</span>
          <span class="p-1.5 rounded-lg bg-red-500/10 text-red-400 group-hover:scale-110 transition-transform flex items-center justify-center">
            <img :src="'/images/nav/sub/policies.svg'" alt="Locked Accounts" class="w-4 h-4 object-contain" />
          </span>
        </div>
        <div class="mt-1.5 flex items-baseline justify-between">
          <span class="text-xl font-black text-white font-mono" :class="stats.locked_accounts_count > 0 ? 'text-red-400' : 'text-white'">
            {{ stats.locked_accounts_count }}
          </span>
          
          <div class="relative group/risk cursor-help">
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg border transition-all flex items-center gap-1" :class="getThreatBadgeClass(stats.threat_level)">
              <span>{{ stats.threat_level }} Risk</span>
              <span class="text-[9px] opacity-70">ℹ️</span>
            </span>
            <!-- Risk Tooltip -->
            <div class="absolute right-0 bottom-full mb-2 w-64 p-2.5 bg-slate-900 border border-slate-700 rounded-xl shadow-2xl text-xs text-slate-200 opacity-0 group-hover/risk:opacity-100 transition-all pointer-events-none z-50">
              <p class="text-[11px] text-slate-300 leading-snug">
                {{ getThreatExplanation(stats.threat_level) }}
              </p>
              <div class="absolute -bottom-1.5 right-4 w-3 h-3 bg-slate-900 border-r border-b border-slate-700 rotate-45 pointer-events-none"></div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs — High Contrast Active Tab Line & Controlled Badges -->
    <div class="flex border-b border-slate-800 overflow-x-auto gap-1 pt-1 scrollbar-none">
      <Link
        href="/admin/auth-logs"
        :class="[
          activeTab === 'overview'
            ? 'border-b-2 border-indigo-400 text-white font-extrabold bg-gradient-to-t from-indigo-500/25 via-indigo-500/10 to-transparent shadow-[0_4px_12px_rgba(99,102,241,0.35)]'
            : 'border-b-2 border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-4 py-2 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group relative'
        ]"
      >
        <img :src="'/images/nav/sub/overview.svg'" alt="Overview" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_overview', 'Overview Hub') }}</span>
        <span v-if="activeTab === 'overview'" class="w-2 h-2 rounded-full bg-indigo-400 ring-4 ring-indigo-500/30 animate-pulse"></span>
      </Link>

      <Link
        href="/admin/auth/roles"
        :class="[
          activeTab === 'roles'
            ? 'border-b-2 border-indigo-400 text-white font-extrabold bg-gradient-to-t from-indigo-500/25 via-indigo-500/10 to-transparent shadow-[0_4px_12px_rgba(99,102,241,0.35)]'
            : 'border-b-2 border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-4 py-2 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group relative'
        ]"
      >
        <img :src="'/images/nav/sub/roles.svg'" alt="Roles" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_roles', 'Roles & Permissions') }}</span>
        <span v-if="activeTab === 'roles'" class="w-2 h-2 rounded-full bg-indigo-400 ring-4 ring-indigo-500/30 animate-pulse"></span>
      </Link>

      <Link
        href="/admin/auth/sessions"
        :class="[
          activeTab === 'sessions'
            ? 'border-b-2 border-emerald-400 text-white font-extrabold bg-gradient-to-t from-emerald-500/25 via-emerald-500/10 to-transparent shadow-[0_4px_12px_rgba(16,185,129,0.35)]'
            : 'border-b-2 border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-4 py-2 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group relative'
        ]"
      >
        <img :src="'/images/nav/sub/sessions.svg'" alt="Sessions" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_sessions', 'Active Sessions') }}</span>
        <span
          class="px-1.5 py-0.2 text-[10px] font-mono rounded-md border"
          :class="activeTab === 'sessions' ? 'bg-emerald-500/30 text-emerald-200 border-emerald-500/40 font-bold' : 'bg-slate-800/80 text-slate-400 border-slate-700/50'"
        >
          {{ stats.active_sessions_count }}
        </span>
      </Link>

      <Link
        href="/admin/auth/history"
        :class="[
          activeTab === 'history'
            ? 'border-b-2 border-sky-400 text-white font-extrabold bg-gradient-to-t from-sky-500/25 via-sky-500/10 to-transparent shadow-[0_4px_12px_rgba(56,189,248,0.35)]'
            : 'border-b-2 border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-4 py-2 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group relative'
        ]"
      >
        <img :src="'/images/nav/sub/history.svg'" alt="History" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_history', 'Login History') }}</span>
        <span v-if="activeTab === 'history'" class="w-2 h-2 rounded-full bg-sky-400 ring-4 ring-sky-500/30 animate-pulse"></span>
      </Link>

      <Link
        href="/admin/auth/failed"
        :class="[
          activeTab === 'failed'
            ? 'border-b-2 border-rose-400 text-white font-extrabold bg-gradient-to-t from-rose-500/25 via-rose-500/10 to-transparent shadow-[0_4px_12px_rgba(244,63,94,0.35)]'
            : 'border-b-2 border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-4 py-2 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group relative'
        ]"
      >
        <img :src="'/images/nav/sub/failed.svg'" alt="Failed" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_failed', 'Failed Login Attempts') }}</span>
        <span
          v-if="stats.failed_logins_today > 0"
          class="px-1.5 py-0.2 text-[10px] font-mono rounded-md border"
          :class="activeTab === 'failed' ? 'bg-rose-500/30 text-rose-200 border-rose-500/40 font-bold' : 'bg-slate-800/80 text-slate-400 border-slate-700/50'"
        >
          {{ stats.failed_logins_today }}
        </span>
      </Link>

      <Link
        href="/admin/auth/policies"
        :class="[
          activeTab === 'policies'
            ? 'border-b-2 border-purple-400 text-white font-extrabold bg-gradient-to-t from-purple-500/25 via-purple-500/10 to-transparent shadow-[0_4px_12px_rgba(168,85,247,0.35)]'
            : 'border-b-2 border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-4 py-2 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group relative'
        ]"
      >
        <img :src="'/images/nav/sub/policies.svg'" alt="Policies" class="w-4 h-4 object-contain group-hover:scale-110 transition-transform" />
        <span>{{ i18n.t('auth_tab_policies', 'Security Policies') }}</span>
        <span v-if="activeTab === 'policies'" class="w-2 h-2 rounded-full bg-purple-400 ring-4 ring-purple-500/30 animate-pulse"></span>
      </Link>

      <Link
        href="/admin/auth/cyber-security"
        :class="[
          activeTab === 'cyber-security'
            ? 'border-b-2 border-rose-500 text-white font-extrabold bg-gradient-to-t from-rose-500/25 via-rose-500/10 to-transparent shadow-[0_4px_12px_rgba(244,63,94,0.35)]'
            : 'border-b-2 border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-4 py-2 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group relative'
        ]"
      >
        <span class="text-sm">🛡️</span>
        <span class="text-rose-300 font-bold">សន្តិសុខ & Cyber Forensics</span>
        <span v-if="activeTab === 'cyber-security'" class="w-2 h-2 rounded-full bg-rose-400 ring-4 ring-rose-500/30 animate-pulse"></span>
        <span v-else class="px-1.5 py-0.2 text-[9px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded-md">LIVE</span>
      </Link>
    </div>
  </div>
</template>

