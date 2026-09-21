<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AuthModuleHeader from '@/Components/Admin/AuthModuleHeader.vue'
import { i18n } from '@/Services/i18n'

const props = withDefaults(defineProps<{
  securityPolicies?: Record<string, any>
  summaryStats?: any
}>(), {
  securityPolicies: () => ({}),
  summaryStats: () => ({})
})

const activeSubTab = ref<'password' | 'jwt' | 'session' | 'lockout' | 'security'>('password')
const newWhitelistIp = ref('')
const ipWhitelist = ref<string[]>(['192.168.1.0/24', '10.0.0.0/16'])

interface SecurityPoliciesForm {
  min_password_length: number
  require_uppercase: boolean
  require_lowercase: boolean
  require_number: boolean
  require_special_char: boolean
  password_expiry_days: number
  prevent_reuse_count: number
  password_strength_indicator: boolean

  access_token_expiry_mins: number
  refresh_token_expiry_days: number
  token_algorithm: string
  auto_refresh_token: boolean
  revoke_on_logout: boolean

  session_expiration_hours: number
  remember_me_days: number
  max_concurrent_sessions: number
  force_single_session: boolean
  auto_logout_inactivity: boolean

  max_failed_attempts: number
  lockout_duration_mins: number
  captcha_after_attempts: number
  require_2fa_admin: boolean
  require_2fa_teacher: boolean
  require_2fa_student: boolean

  password_hashing: string
  https_ssl_enforced: boolean
  api_rate_limiting: string
  csrf_protection: boolean
  xss_protection: boolean
  sql_injection_guard: boolean

  security_level: string
  [key: string]: any
}

const form = useForm<SecurityPoliciesForm>({
  min_password_length: props.securityPolicies?.min_password_length ?? 8,
  require_uppercase: props.securityPolicies?.require_uppercase ?? true,
  require_lowercase: props.securityPolicies?.require_lowercase ?? true,
  require_number: props.securityPolicies?.require_number ?? true,
  require_special_char: props.securityPolicies?.require_special_char ?? true,
  password_expiry_days: props.securityPolicies?.password_expiry_days ?? 90,
  prevent_reuse_count: props.securityPolicies?.prevent_reuse_count ?? 5,
  password_strength_indicator: props.securityPolicies?.password_strength_indicator ?? true,

  access_token_expiry_mins: props.securityPolicies?.access_token_expiry_mins ?? 15,
  refresh_token_expiry_days: props.securityPolicies?.refresh_token_expiry_days ?? 7,
  token_algorithm: props.securityPolicies?.token_algorithm ?? 'HS256',
  auto_refresh_token: props.securityPolicies?.auto_refresh_token ?? true,
  revoke_on_logout: props.securityPolicies?.revoke_on_logout ?? true,

  session_expiration_hours: props.securityPolicies?.session_expiration_hours ?? 24,
  remember_me_days: props.securityPolicies?.remember_me_days ?? 30,
  max_concurrent_sessions: props.securityPolicies?.max_concurrent_sessions ?? 3,
  force_single_session: props.securityPolicies?.force_single_session ?? false,
  auto_logout_inactivity: props.securityPolicies?.auto_logout_inactivity ?? true,

  max_failed_attempts: props.securityPolicies?.max_failed_attempts ?? 5,
  lockout_duration_mins: props.securityPolicies?.lockout_duration_mins ?? 30,
  captcha_after_attempts: props.securityPolicies?.captcha_after_attempts ?? 3,
  require_2fa_admin: props.securityPolicies?.require_2fa_admin ?? true,
  require_2fa_teacher: props.securityPolicies?.require_2fa_teacher ?? false,
  require_2fa_student: props.securityPolicies?.require_2fa_student ?? false,

  password_hashing: props.securityPolicies?.password_hashing ?? 'bcrypt (cost: 12)',
  https_ssl_enforced: props.securityPolicies?.https_ssl_enforced ?? true,
  api_rate_limiting: props.securityPolicies?.api_rate_limiting ?? '100 req/min per IP',
  csrf_protection: props.securityPolicies?.csrf_protection ?? true,
  xss_protection: props.securityPolicies?.xss_protection ?? true,
  sql_injection_guard: props.securityPolicies?.sql_injection_guard ?? true,

  security_level: props.securityPolicies?.security_level ?? 'Strong',
})

const addWhitelistIp = () => {
  if (newWhitelistIp.value && !ipWhitelist.value.includes(newWhitelistIp.value)) {
    ipWhitelist.value.push(newWhitelistIp.value)
    newWhitelistIp.value = ''
  }
}

const removeWhitelistIp = (ip: string) => {
  ipWhitelist.value = ipWhitelist.value.filter(i => i !== ip)
}

const saveAllPolicies = () => {
  form.post('/admin/auth-logs/policies', {
    preserveScroll: true
  })
}

const resetToDefault = () => {
  if (confirm('Reset all security policies to default baseline configurations?')) {
    form.min_password_length = 8
    form.require_uppercase = true
    form.require_lowercase = true
    form.require_number = true
    form.require_special_char = true
    form.password_expiry_days = 90
    form.prevent_reuse_count = 5
    form.access_token_expiry_mins = 15
    form.refresh_token_expiry_days = 7
    form.token_algorithm = 'HS256'
    form.session_expiration_hours = 24
    form.remember_me_days = 30
    form.max_concurrent_sessions = 3
    form.max_failed_attempts = 5
    form.captcha_after_attempts = 3
    form.require_2fa_admin = true
    form.require_2fa_teacher = false
    form.require_2fa_student = false
    form.security_level = 'Strong'
  }
}
</script>

<template>
  <AdminLayout :title="i18n.t('policies_page_title', 'Security Policies Configuration')">
    <div class="space-y-5 font-sans">
      <!-- Shared Header -->
      <AuthModuleHeader activeTab="policies" :summaryStats="props.summaryStats" />

      <!-- Top Header Row: Security Score Widget & Action Buttons Bar -->
      <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4 backdrop-blur-xl shadow-sm dark:shadow-none">
        <div class="flex items-center gap-3 w-full sm:w-auto">
          <div class="w-11 h-11 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center shrink-0 shadow-inner">
            <img :src="'/images/nav/sub/policies.svg'" alt="Security Policies" class="w-6 h-6 object-contain" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ i18n.t('policies_score_label', 'Security Health') }}:</span>
              <span class="px-2.5 py-0.5 text-xs font-mono font-bold bg-emerald-500/10 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-500/20 dark:border-emerald-500/30 rounded-full">
                {{ props.summaryStats?.security_score || 88 }}/100 GOOD
              </span>
            </div>
            <div class="w-48 bg-slate-100 dark:bg-slate-950 h-2 rounded-full overflow-hidden mt-1.5 border border-slate-200 dark:border-slate-800">
              <div
                class="bg-gradient-to-r from-indigo-500 via-purple-500 to-emerald-400 h-full rounded-full transition-all duration-500"
                :style="{ width: (props.summaryStats?.security_score || 88) + '%' }"
              ></div>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
          <button
            type="button"
            @click="resetToDefault"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/80 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>🔄</span>
            <span>{{ i18n.t('policies_btn_reset', 'Reset Defaults') }}</span>
          </button>

          <button
            type="button"
            @click="saveAllPolicies"
            :disabled="form.processing"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-600/30 transition-all flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
          >
            <span>💾</span>
            <span>{{ i18n.t('policies_btn_save', 'Save All Policies') }}</span>
          </button>
        </div>
      </div>

      <!-- 2-Column Settings Layout (Left: Sub-Nav, Right: Form Card) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- Left Side: Vertical Sub-Navigation Tabs (3 cols) -->
        <div class="lg:col-span-4 space-y-2">
          <button
            @click="activeSubTab = 'password'"
            :class="[
              activeSubTab === 'password'
                ? 'bg-indigo-50 border-indigo-300 text-indigo-700 font-extrabold shadow-sm dark:bg-indigo-600/20 dark:border-indigo-500/50 dark:text-indigo-200'
                : 'bg-white dark:bg-slate-900/60 border-slate-200 dark:border-slate-800/80 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-white shadow-sm dark:shadow-none',
              'w-full p-3.5 rounded-2xl border text-xs text-left transition-all flex items-center justify-between cursor-pointer'
            ]"
          >
            <div class="flex items-center gap-3">
              <span class="text-base">🔒</span>
              <span>{{ i18n.t('policies_tab_password', 'Password Policy') }}</span>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500">▶</span>
          </button>

          <button
            @click="activeSubTab = 'jwt'"
            :class="[
              activeSubTab === 'jwt'
                ? 'bg-indigo-50 border-indigo-300 text-indigo-700 font-extrabold shadow-sm dark:bg-indigo-600/20 dark:border-indigo-500/50 dark:text-indigo-200'
                : 'bg-white dark:bg-slate-900/60 border-slate-200 dark:border-slate-800/80 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-white shadow-sm dark:shadow-none',
              'w-full p-3.5 rounded-2xl border text-xs text-left transition-all flex items-center justify-between cursor-pointer'
            ]"
          >
            <div class="flex items-center gap-3">
              <span class="text-base">🔑</span>
              <span>{{ i18n.t('policies_tab_jwt', 'JWT & Token Policy') }}</span>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500">▶</span>
          </button>

          <button
            @click="activeSubTab = 'session'"
            :class="[
              activeSubTab === 'session'
                ? 'bg-indigo-50 border-indigo-300 text-indigo-700 font-extrabold shadow-sm dark:bg-indigo-600/20 dark:border-indigo-500/50 dark:text-indigo-200'
                : 'bg-white dark:bg-slate-900/60 border-slate-200 dark:border-slate-800/80 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-white shadow-sm dark:shadow-none',
              'w-full p-3.5 rounded-2xl border text-xs text-left transition-all flex items-center justify-between cursor-pointer'
            ]"
          >
            <div class="flex items-center gap-3">
              <span class="text-base">⏱️</span>
              <span>{{ i18n.t('policies_tab_session', 'Session Policy') }}</span>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500">▶</span>
          </button>

          <button
            @click="activeSubTab = 'lockout'"
            :class="[
              activeSubTab === 'lockout'
                ? 'bg-indigo-50 border-indigo-300 text-indigo-700 font-extrabold shadow-sm dark:bg-indigo-600/20 dark:border-indigo-500/50 dark:text-indigo-200'
                : 'bg-white dark:bg-slate-900/60 border-slate-200 dark:border-slate-800/80 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-white shadow-sm dark:shadow-none',
              'w-full p-3.5 rounded-2xl border text-xs text-left transition-all flex items-center justify-between cursor-pointer'
            ]"
          >
            <div class="flex items-center gap-3">
              <span class="text-base">🛡️</span>
              <span>{{ i18n.t('policies_tab_lockout', 'Login Protection & Lockout') }}</span>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500">▶</span>
          </button>

          <button
            @click="activeSubTab = 'security'"
            :class="[
              activeSubTab === 'security'
                ? 'bg-indigo-50 border-indigo-300 text-indigo-700 font-extrabold shadow-sm dark:bg-indigo-600/20 dark:border-indigo-500/50 dark:text-indigo-200'
                : 'bg-white dark:bg-slate-900/60 border-slate-200 dark:border-slate-800/80 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-white shadow-sm dark:shadow-none',
              'w-full p-3.5 rounded-2xl border text-xs text-left transition-all flex items-center justify-between cursor-pointer'
            ]"
          >
            <div class="flex items-center gap-3">
              <span class="text-base">🔐</span>
              <span>{{ i18n.t('policies_tab_security', 'Data Security & IP Whitelist') }}</span>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500">▶</span>
          </button>
        </div>

        <!-- Right Side: Active Section Form Details Card (8 cols) -->
        <div class="lg:col-span-8 bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 backdrop-blur-xl shadow-sm dark:shadow-2xl space-y-5">
          <form @submit.prevent="saveAllPolicies" class="space-y-5 text-xs">
            <!-- TAB 1: PASSWORD POLICY -->
            <div v-if="activeSubTab === 'password'" class="space-y-4">
              <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                  <span>🔒 {{ i18n.t('policies_tab_password', 'Password Policy Configuration') }}</span>
                </h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Minimum Password Length</label>
                  <select v-model.number="form.min_password_length" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-indigo-500">
                    <option :value="6">6 characters</option>
                    <option :value="8">8 characters (Recommended)</option>
                    <option :value="12">12 characters (Strong)</option>
                    <option :value="16">16 characters (Enterprise)</option>
                  </select>
                </div>

                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Password Expiration Days</label>
                  <select v-model.number="form.password_expiry_days" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-indigo-500">
                    <option :value="30">30 days</option>
                    <option :value="60">60 days</option>
                    <option :value="90">90 days (Recommended)</option>
                    <option :value="180">180 days</option>
                  </select>
                </div>
              </div>

              <div class="pt-2">
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Complexity Requirements:</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                    <input v-model="form.require_uppercase" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                    <span class="text-slate-700 dark:text-slate-200 font-medium">Require Uppercase (A-Z)</span>
                  </label>
                  <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                    <input v-model="form.require_lowercase" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                    <span class="text-slate-700 dark:text-slate-200 font-medium">Require Lowercase (a-z)</span>
                  </label>
                  <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                    <input v-model="form.require_number" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                    <span class="text-slate-700 dark:text-slate-200 font-medium">Require Numbers (0-9)</span>
                  </label>
                  <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                    <input v-model="form.require_special_char" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                    <span class="text-slate-700 dark:text-slate-200 font-medium">Require Special Symbols (!@#$)</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- TAB 2: JWT & TOKEN POLICY -->
            <div v-if="activeSubTab === 'jwt'" class="space-y-4">
              <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                  <span>🔑 {{ i18n.t('policies_tab_jwt', 'JWT & Token Policy Configuration') }}</span>
                </h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Access Token Lifetime</label>
                  <select v-model.number="form.access_token_expiry_mins" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-indigo-500">
                    <option :value="15">15 minutes (High Security)</option>
                    <option :value="30">30 minutes</option>
                    <option :value="60">60 minutes (1 Hour)</option>
                  </select>
                </div>

                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Refresh Token Lifetime</label>
                  <select v-model.number="form.refresh_token_expiry_days" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-indigo-500">
                    <option :value="1">1 day</option>
                    <option :value="7">7 days (Recommended)</option>
                    <option :value="30">30 days</option>
                  </select>
                </div>
              </div>

              <div class="pt-2 space-y-3">
                <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-3 rounded-xl border border-slate-200 dark:border-slate-800/80">
                  <input v-model="form.auto_refresh_token" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                  <div>
                    <div class="text-slate-800 dark:text-slate-200 font-medium">Automatic Silent Token Refresh</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Seamlessly refresh access tokens without interrupting user workflow</div>
                  </div>
                </label>

                <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-3 rounded-xl border border-slate-200 dark:border-slate-800/80">
                  <input v-model="form.revoke_on_logout" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                  <div>
                    <div class="text-slate-800 dark:text-slate-200 font-medium">Revoke All Tokens on Logout</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Blacklist tokens immediately when user signs out</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- TAB 3: SESSION POLICY -->
            <div v-if="activeSubTab === 'session'" class="space-y-4">
              <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                  <span>⏱️ {{ i18n.t('policies_tab_session', 'Session & Expiration Policy') }}</span>
                </h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Max Concurrent Sessions per User</label>
                  <select v-model.number="form.max_concurrent_sessions" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-indigo-500">
                    <option :value="1">1 Session (Strict Single-Device)</option>
                    <option :value="3">3 Sessions (Recommended)</option>
                    <option :value="5">5 Sessions</option>
                  </select>
                </div>

                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Remember Me Extended Days</label>
                  <select v-model.number="form.remember_me_days" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-indigo-500">
                    <option :value="7">7 days</option>
                    <option :value="14">14 days</option>
                    <option :value="30">30 days</option>
                  </select>
                </div>
              </div>

              <div class="pt-2">
                <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-3 rounded-xl border border-slate-200 dark:border-slate-800/80">
                  <input v-model="form.auto_logout_inactivity" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                  <div>
                    <div class="text-slate-800 dark:text-slate-200 font-medium">Auto Logout on 15m Idle Inactivity</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Lock session automatically if user leaves workstation unattended</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- TAB 4: LOGIN PROTECTION & LOCKOUT -->
            <div v-if="activeSubTab === 'lockout'" class="space-y-4">
              <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                  <span>🛡️ {{ i18n.t('policies_tab_lockout', 'Login Protection & Lockout Rules') }}</span>
                </h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Max Failed Login Attempts</label>
                  <select v-model.number="form.max_failed_attempts" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-indigo-500">
                    <option :value="3">3 Attempts</option>
                    <option :value="5">5 Attempts (Recommended)</option>
                    <option :value="10">10 Attempts</option>
                  </select>
                </div>

                <div>
                  <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Lockout Duration Minutes</label>
                  <select v-model.number="form.lockout_duration_mins" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-indigo-500">
                    <option :value="15">15 minutes</option>
                    <option :value="30">30 minutes</option>
                    <option :value="60">60 minutes (1 Hour)</option>
                  </select>
                </div>
              </div>

              <div class="pt-2">
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Mandatory 2FA Enforcement by Role:</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <label class="flex items-center gap-2 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                    <input v-model="form.require_2fa_admin" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                    <span class="text-slate-700 dark:text-slate-200 font-medium">Admin 2FA</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                    <input v-model="form.require_2fa_teacher" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                    <span class="text-slate-700 dark:text-slate-200 font-medium">Teacher 2FA</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                    <input v-model="form.require_2fa_student" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                    <span class="text-slate-700 dark:text-slate-200 font-medium">Student 2FA</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- TAB 5: DATA SECURITY & IP WHITELIST -->
            <div v-if="activeSubTab === 'security'" class="space-y-4">
              <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                  <span>🔐 {{ i18n.t('policies_tab_security', 'Data Security & IP Whitelist') }}</span>
                </h3>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-3 rounded-xl border border-slate-200 dark:border-slate-800/80">
                  <input v-model="form.https_ssl_enforced" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                  <span class="text-slate-700 dark:text-slate-200 font-medium">Enforce HTTPS / TLS 1.3</span>
                </label>
                <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-3 rounded-xl border border-slate-200 dark:border-slate-800/80">
                  <input v-model="form.csrf_protection" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                  <span class="text-slate-700 dark:text-slate-200 font-medium">CSRF Token Guard</span>
                </label>
                <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-3 rounded-xl border border-slate-200 dark:border-slate-800/80">
                  <input v-model="form.xss_protection" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                  <span class="text-slate-700 dark:text-slate-200 font-medium">XSS Input Sanitizer</span>
                </label>
                <label class="flex items-center gap-2.5 cursor-pointer bg-slate-50 dark:bg-slate-950/50 p-3 rounded-xl border border-slate-200 dark:border-slate-800/80">
                  <input v-model="form.sql_injection_guard" type="checkbox" class="w-4 h-4 rounded bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0" />
                  <span class="text-slate-700 dark:text-slate-200 font-medium">SQL Injection Guard</span>
                </label>
              </div>

              <div class="pt-2">
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">Trusted IP Whitelist Subnets</label>
                <div class="flex gap-2 mb-2">
                  <input
                    v-model="newWhitelistIp"
                    type="text"
                    placeholder="e.g. 192.168.1.0/24"
                    class="flex-1 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2 text-slate-900 dark:text-white font-mono text-xs focus:outline-none focus:border-indigo-500 placeholder-slate-400 dark:placeholder-slate-500"
                  />
                  <button
                    type="button"
                    @click="addWhitelistIp"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs cursor-pointer"
                  >
                    + Add IP
                  </button>
                </div>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="ip in ipWhitelist"
                    :key="ip"
                    class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 font-mono text-xs flex items-center gap-1.5"
                  >
                    <span>{{ ip }}</span>
                    <button type="button" @click="removeWhitelistIp(ip)" class="text-rose-500 dark:text-rose-400 hover:text-rose-700 dark:hover:text-white cursor-pointer">✕</button>
                  </span>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
