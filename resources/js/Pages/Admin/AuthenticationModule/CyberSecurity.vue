<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AuthModuleHeader from '@/Components/Admin/AuthModuleHeader.vue'

interface ForensicIncident {
  timestamp: string
  user_id: string | number
  name?: string
  username?: string
  client_lang?: string
  ip: string
  country?: string
  city?: string
  isp?: string
  coordinates?: string
  user_agent?: string
  threat_type: string
  severity: string
  payload?: string
  action?: string
}

interface HoneypotTrap {
  name: string
  url: string
  description: string
  hits: number
  risk_level: string
  status: string
}

interface EmergencySettings {
  phone: string
  call_enabled: boolean
  sms_enabled: boolean
  pushover_enabled: boolean
  auto_defense: boolean
}

const props = defineProps<{
  summaryStats?: any
  forensicIncidents: ForensicIncident[]
  rawAttackerLogs: string[]
  emergencyLogs?: string[]
  emergencySettings?: EmergencySettings
  honeypotTraps: HoneypotTrap[]
  blockedIps: string[]
  securityStatus: {
    bot_username: string
    admin_chat_id: string
    bot_token_set: boolean
    webhook_secret_set: boolean
    total_threats: number
    critical_threats: number
    banned_users_count: number
    blocked_ips_count: number
  }
}>()

// Search & Filter
const searchQuery = ref('')
const severityFilter = ref('all')
const threatTypeFilter = ref('all')

const filteredIncidents = computed(() => {
  return (props.forensicIncidents || []).filter(inc => {
    const query = searchQuery.value.toLowerCase()
    const matchesSearch = !query ||
      String(inc.user_id).includes(query) ||
      (inc.username && inc.username.toLowerCase().includes(query)) ||
      (inc.name && inc.name.toLowerCase().includes(query)) ||
      (inc.ip && inc.ip.includes(query)) ||
      (inc.country && inc.country.toLowerCase().includes(query)) ||
      (inc.city && inc.city.toLowerCase().includes(query)) ||
      (inc.isp && inc.isp.toLowerCase().includes(query)) ||
      (inc.threat_type && inc.threat_type.toLowerCase().includes(query)) ||
      (inc.payload && inc.payload.toLowerCase().includes(query))

    const matchesSeverity = severityFilter.value === 'all' || inc.severity === severityFilter.value
    const matchesType = threatTypeFilter.value === 'all' || inc.threat_type.includes(threatTypeFilter.value)

    return matchesSearch && matchesSeverity && matchesType
  })
})

// Emergency Alarm Form
const emergencyForm = useForm({
  phone: props.emergencySettings?.phone || '0964618507',
  call_enabled: props.emergencySettings?.call_enabled ?? false,
  sms_enabled: props.emergencySettings?.sms_enabled ?? true,
  pushover_enabled: props.emergencySettings?.pushover_enabled ?? false,
  auto_defense: props.emergencySettings?.auto_defense ?? true,
})

const isSavingEmergency = ref(false)
const handleSaveEmergencySettings = () => {
  isSavingEmergency.value = true
  emergencyForm.post('/admin/auth-logs/emergency-settings', {
    preserveScroll: true,
    onFinish: () => {
      isSavingEmergency.value = false
    }
  })
}

// Emergency Test Actions
const isCalling = ref(false)
const handleTestEmergencyCall = () => {
  isCalling.value = true
  router.post('/admin/auth-logs/test-emergency-call', { phone: emergencyForm.phone }, {
    preserveScroll: true,
    onFinish: () => {
      isCalling.value = false
    }
  })
}

const isSendingSms = ref(false)
const handleTestEmergencySms = () => {
  isSendingSms.value = true
  router.post('/admin/auth-logs/test-emergency-sms', { phone: emergencyForm.phone }, {
    preserveScroll: true,
    onFinish: () => {
      isSendingSms.value = false
    }
  })
}

// Ban User Action
const banUserForm = useForm({
  user_id: ''
})

const handleBanUser = (userId?: string | number) => {
  const targetId = userId || banUserForm.user_id
  if (!targetId) return
  if (confirm(`តើអ្នកប្រាកដជាចង់ Block និង Ban User ID: ${targetId} ចេញពី Telegram Group ដែរឬទេ?`)) {
    router.post('/admin/auth-logs/ban-telegram-user', { user_id: targetId }, {
      preserveScroll: true,
      onSuccess: () => {
        banUserForm.reset()
      }
    })
  }
}

// Block IP Action
const blockIpForm = useForm({
  ip_address: '',
  reason: 'Honeypot threat intercept'
})

const handleBlockIp = (ip?: string) => {
  const targetIp = ip || blockIpForm.ip_address
  if (!targetIp) return
  if (confirm(`តើអ្នកពិតជាចង់ Blacklist IP: ${targetIp} លើ Firewall ដែរឬទេ?`)) {
    router.post('/admin/auth-logs/block-ip', { ip_address: targetIp, reason: 'Manual Cyber Security Blacklist' }, {
      preserveScroll: true,
      onSuccess: () => {
        blockIpForm.reset()
      }
    })
  }
}

// Unblock IP
const handleUnblockIp = (ip: string) => {
  if (confirm(`តើអ្នកចង់ដក IP: ${ip} ចេញពី Blacklist វិញដែរឬទេ?`)) {
    router.post('/admin/auth-logs/unblock-ip', { ip_address: ip }, {
      preserveScroll: true
    })
  }
}

// Simulate Alert
const isSimulating = ref(false)
const handleSimulateAlert = () => {
  isSimulating.value = true
  router.post('/admin/auth-logs/simulate-alert', {}, {
    preserveScroll: true,
    onFinish: () => {
      isSimulating.value = false
    }
  })
}

// Clear Logs
const handleClearForensics = () => {
  if (confirm('តើអ្នកពិតជាចង់សម្អាតកំណត់ត្រា Cyber Threat Logs និង Honeypot Forensics ទាំងអស់មែនទេ?')) {
    router.post('/admin/auth-logs/clear-forensics', {}, {
      preserveScroll: true
    })
  }
}

const getSeverityClass = (sev?: string) => {
  switch (sev?.toUpperCase()) {
    case 'CRITICAL':
      return 'bg-red-500/20 text-red-400 border-red-500/40'
    case 'HIGH':
      return 'bg-orange-500/20 text-orange-400 border-orange-500/40'
    case 'MEDIUM':
      return 'bg-amber-500/20 text-amber-300 border-amber-500/40'
    default:
      return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40'
  }
}
</script>

<template>
  <AdminLayout title="Cyber Security & Emergency Defense Center">
    <div class="space-y-6 font-sans">
      <!-- Shared Header Navigation -->
      <AuthModuleHeader activeTab="cyber-security" :summaryStats="props.summaryStats" />

      <!-- Top System Security Live Banner -->
      <div class="bg-gradient-to-r from-slate-900 via-rose-950/40 to-slate-900 border border-rose-500/30 rounded-2xl p-6 shadow-2xl relative overflow-hidden">
        <div class="absolute -top-16 -right-16 w-60 h-60 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
          <div class="space-y-2">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-rose-500/20 border border-rose-500/40 flex items-center justify-center text-2xl shadow-inner">
                🛡️
              </div>
              <div>
                <h2 class="text-xl font-black text-white tracking-tight flex items-center gap-2.5">
                  មជ្ឈមណ្ឌលសន្តិសុខឌីជីថល & ប្រព័ន្ធទូរស័ព្ទរោទ៍ប្រកាសអាសន្នបន្ទាន់
                  <span class="px-2.5 py-0.5 text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-full animate-pulse">
                    🟢 ONLINE & ARMED
                  </span>
                </h2>
                <p class="text-xs text-slate-300">
                  ដាស់តឿនល្បឿនលឿនបំផុត (Voice Call, SMS, Emergency Push, & Auto-Defense Session Isolation) មិនបាច់បើក Telegram ក៏ដឹងភ្លាមៗ!
                </p>
              </div>
            </div>

            <!-- Bot & Group Details Badges -->
            <div class="flex flex-wrap items-center gap-2 pt-2 text-xs">
              <span class="px-3 py-1 bg-slate-800/80 border border-slate-700 rounded-lg text-slate-200 flex items-center gap-1.5">
                🤖 <b>Bot:</b> <code class="text-sky-300">@{{ securityStatus.bot_username }}</code>
              </span>
              <span class="px-3 py-1 bg-slate-800/80 border border-slate-700 rounded-lg text-slate-200 flex items-center gap-1.5">
                👥 <b>Admin Group ID:</b> <code class="text-emerald-300">{{ securityStatus.admin_chat_id }}</code>
              </span>
              <span class="px-3 py-1 bg-slate-800/80 border border-slate-700 rounded-lg text-slate-200 flex items-center gap-1.5">
                📞 <b>Emergency Phone:</b> <span class="text-rose-400 font-mono font-bold">{{ emergencyForm.phone }}</span>
              </span>
              <span class="px-3 py-1 bg-slate-800/80 border border-slate-700 rounded-lg text-slate-200 flex items-center gap-1.5">
                🛡️ <b>Auto-Defense:</b> <span class="text-emerald-400 font-bold">Enabled ✅</span>
              </span>
            </div>
          </div>

          <!-- Quick Test Action Buttons -->
          <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <button
              @click="handleTestEmergencyCall"
              :disabled="isCalling"
              class="px-3.5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5 disabled:opacity-50"
            >
              <span>{{ isCalling ? '⏳ កំពុង Call...' : '📞 តេស្ត Voice Call' }}</span>
            </button>

            <button
              @click="handleTestEmergencySms"
              :disabled="isSendingSms"
              class="px-3.5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5 disabled:opacity-50"
            >
              <span>{{ isSendingSms ? '⏳ កំពុងផ្ញើ...' : '📱 តេស្ត SMS' }}</span>
            </button>

            <button
              @click="handleSimulateAlert"
              :disabled="isSimulating"
              class="px-3.5 py-2.5 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center gap-1.5 disabled:opacity-50"
            >
              <span>{{ isSimulating ? '⏳ កំពុងផ្ញើ...' : '🧪 តេស្ត Telegram Alert' }}</span>
            </button>

            <button
              @click="handleClearForensics"
              class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5"
            >
              <span>🧹 សម្អាត Log</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Emergency Trigger Channels & Auto-Defense Control Panel -->
      <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950/40 border border-slate-700/80 rounded-2xl p-6 shadow-2xl space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-700/60 pb-4">
          <div>
            <h3 class="text-base font-black text-white flex items-center gap-2">
              <span>🚨 ការកំណត់បណ្តាញដាស់តឿនបន្ទាន់ (Emergency Alert Channels) & Active Defense</span>
            </h3>
            <p class="text-xs text-slate-300 mt-0.5">
              កំណត់លេខទូរស័ព្ទ និងបើកបិទមុខងាររោទ៍ទូរស័ព្ទ ផ្ញើសារ SMS ឬបញ្ជាឱ្យកាត់ផ្តាច់ Session Hacker ដោយស្វ័យប្រវត្តិ។
            </p>
          </div>
          <button
            @click="handleSaveEmergencySettings"
            :disabled="isSavingEmergency"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 shrink-0"
          >
            <span>{{ isSavingEmergency ? '⏳ កំពុងរក្សាទុក...' : '💾 រក្សាទុកការកំណត់' }}</span>
          </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-center">
          <!-- Emergency Phone Input -->
          <div class="lg:col-span-2 space-y-1.5">
            <label class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
              <span>📱 លេខទូរស័ព្ទទទួលការ Call & SMS បន្ទាន់៖</span>
            </label>
            <div class="relative">
              <input
                v-model="emergencyForm.phone"
                type="text"
                placeholder="0964618507"
                class="w-full bg-slate-950/80 border border-slate-700 text-sm font-mono text-emerald-400 font-bold rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-indigo-500"
              />
              <span class="absolute right-3 top-2.5 text-xs text-slate-500">Cambodia (+855)</span>
            </div>
            <p class="text-[11px] text-slate-400">
              លេខនេះនឹងរោទ៍ពេលប្រព័ន្ធជួប Threat កម្រិត Critical/High (គាំទ្រ Smart, Cellcard, Metfone)
            </p>
          </div>

          <!-- Toggle Channels -->
          <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Channel 1: Voice Call -->
            <label class="flex items-center justify-between p-3 bg-slate-900/60 border border-slate-700 rounded-xl cursor-pointer hover:border-purple-500/50 transition-all">
              <div class="space-y-0.5">
                <span class="text-xs font-bold text-white flex items-center gap-1.5">
                  <span>📞 Automated Voice Call</span>
                </span>
                <p class="text-[10px] text-slate-400">ទូរស័ព្ទរោទ៍ដូចគេ Call មកផ្ទាល់</p>
              </div>
              <input
                v-model="emergencyForm.call_enabled"
                type="checkbox"
                class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-purple-600 focus:ring-purple-500"
              />
            </label>

            <!-- Channel 2: Emergency SMS -->
            <label class="flex items-center justify-between p-3 bg-slate-900/60 border border-slate-700 rounded-xl cursor-pointer hover:border-emerald-500/50 transition-all">
              <div class="space-y-0.5">
                <span class="text-xs font-bold text-white flex items-center gap-1.5">
                  <span>💬 Emergency SMS Alert</span>
                </span>
                <p class="text-[10px] text-slate-400">ផ្ញើ SMS បន្ទាន់ (មិនបាច់មាន Internet)</p>
              </div>
              <input
                v-model="emergencyForm.sms_enabled"
                type="checkbox"
                class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-emerald-600 focus:ring-emerald-500"
              />
            </label>

            <!-- Channel 3: Pushover Alarm -->
            <label class="flex items-center justify-between p-3 bg-slate-900/60 border border-slate-700 rounded-xl cursor-pointer hover:border-amber-500/50 transition-all">
              <div class="space-y-0.5">
                <span class="text-xs font-bold text-white flex items-center gap-1.5">
                  <span>🚨 Pushover Siren Push</span>
                </span>
                <p class="text-[10px] text-slate-400">បន្លឺសំឡេងទម្លុះ Silent/DND Mode</p>
              </div>
              <input
                v-model="emergencyForm.pushover_enabled"
                type="checkbox"
                class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-amber-600 focus:ring-amber-500"
              />
            </label>

            <!-- Channel 4: Auto-Defense Isolation -->
            <label class="flex items-center justify-between p-3 bg-rose-950/20 border border-rose-500/30 rounded-xl cursor-pointer hover:border-rose-500 transition-all">
              <div class="space-y-0.5">
                <span class="text-xs font-bold text-rose-300 flex items-center gap-1.5">
                  <span>🛡️ Auto-Defense Isolation</span>
                </span>
                <p class="text-[10px] text-slate-300">កាត់ផ្តាច់ Session & Block IP ស្វ័យប្រវត្តិ</p>
              </div>
              <input
                v-model="emergencyForm.auto_defense"
                type="checkbox"
                class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-rose-600 focus:ring-rose-500"
              />
            </label>
          </div>
        </div>
      </div>

      <!-- 4 Cyber Metrics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total Threats -->
        <div class="bg-slate-800/40 border border-slate-800 rounded-2xl p-4 backdrop-blur-xl shadow-lg flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-slate-400">ការប៉ុនប៉ងដែលបានស្ទាក់ចាប់</p>
            <h3 class="text-2xl font-black text-white font-mono mt-1">{{ securityStatus.total_threats }}</h3>
            <p class="text-[11px] text-rose-400 font-medium mt-0.5">Threat Footprints Logged</p>
          </div>
          <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/25 flex items-center justify-center text-xl">
            🚨
          </div>
        </div>

        <!-- Metric 2: Critical Threats -->
        <div class="bg-slate-800/40 border border-slate-800 rounded-2xl p-4 backdrop-blur-xl shadow-lg flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-slate-400">ការវាយប្រហារកម្រិតខ្ពស់</p>
            <h3 class="text-2xl font-black text-rose-400 font-mono mt-1">{{ securityStatus.critical_threats }}</h3>
            <p class="text-[11px] text-slate-400 font-medium mt-0.5">Critical SQLi / RCE / Trap</p>
          </div>
          <div class="w-12 h-12 rounded-xl bg-red-500/10 border border-red-500/25 flex items-center justify-center text-xl">
            🔥
          </div>
        </div>

        <!-- Metric 3: Honeypot Traps Triggered -->
        <div class="bg-slate-800/40 border border-slate-800 rounded-2xl p-4 backdrop-blur-xl shadow-lg flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-slate-400">Honeypot Bait Traps Hit</p>
            <h3 class="text-2xl font-black text-amber-300 font-mono mt-1">{{ honeypotTraps.reduce((acc, cur) => acc + cur.hits, 0) }}</h3>
            <p class="text-[11px] text-amber-400 font-medium mt-0.5">Decoy Exploits Intercepted</p>
          </div>
          <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/25 flex items-center justify-center text-xl">
            🪤
          </div>
        </div>

        <!-- Metric 4: Firewall Blacklisted IPs -->
        <div class="bg-slate-800/40 border border-slate-800 rounded-2xl p-4 backdrop-blur-xl shadow-lg flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-slate-400">IP ក្នុង Firewall Blacklist</p>
            <h3 class="text-2xl font-black text-emerald-400 font-mono mt-1">{{ blockedIps.length }}</h3>
            <p class="text-[11px] text-emerald-400 font-medium mt-0.5">Blocked Threat Sources</p>
          </div>
          <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-xl">
            🛡️
          </div>
        </div>
      </div>

      <!-- Honeypot Bait Portals Section -->
      <div class="bg-slate-800/40 border border-slate-800 rounded-2xl p-5 backdrop-blur-xl shadow-xl space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>🪤 គេហទំព័រអន្ទាក់ Honeypot Decoys (Bait Traps)</span>
              <span class="text-xs font-normal text-slate-400">(ប្រើសម្រាប់ទាក់ទាញ Hacker ឱ្យចុចដើម្បីទាញយក IP & ទីតាំង)</span>
            </h3>
          </div>
          <span class="text-xs text-rose-300 bg-rose-500/10 border border-rose-500/20 px-3 py-1 rounded-full font-semibold">
            Armed & Trigger-Ready
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="trap in honeypotTraps" :key="trap.name" class="bg-slate-900/60 border border-slate-700/60 rounded-xl p-4 space-y-3 relative group hover:border-rose-500/40 transition-all">
            <div class="flex items-start justify-between">
              <div>
                <h4 class="font-bold text-white text-sm">{{ trap.name }}</h4>
                <p class="text-[11px] text-slate-400 mt-0.5 leading-snug">{{ trap.description }}</p>
              </div>
              <span class="px-2 py-0.5 text-[10px] font-mono font-bold rounded" :class="getSeverityClass(trap.risk_level)">
                {{ trap.risk_level }}
              </span>
            </div>

            <div class="bg-slate-950/80 p-2 rounded-lg border border-slate-800/80 flex items-center justify-between text-xs">
              <code class="text-rose-300 font-mono truncate max-w-[200px]">{{ trap.url }}</code>
              <a :href="trap.url" target="_blank" class="text-sky-400 hover:text-sky-300 font-semibold shrink-0 ml-2">
                បើកមើល ↗
              </a>
            </div>

            <div class="flex items-center justify-between text-xs text-slate-400 pt-1 border-t border-slate-800">
              <span>ចំនួនអ្នកធ្លាក់អន្ទាក់ (Hits):</span>
              <span class="font-bold font-mono text-white bg-slate-800 px-2 py-0.5 rounded border border-slate-700">{{ trap.hits }} នាក់</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Threat Incidents Forensics Log Table -->
      <div class="bg-slate-800/40 border border-slate-800 rounded-2xl backdrop-blur-xl shadow-xl overflow-hidden">
        <!-- Controls Bar -->
        <div class="p-5 border-b border-slate-800/80 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <span>📡 បញ្ជីរាយនាម Footprints & ការវាយប្រហារដែលបានស្ទាក់ចាប់ (Live Forensics)</span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              បង្ហាញទិន្នន័យ IP Address, ទីតាំង Geo-IP, ISP, គណនី Telegram, Payload និងប៊ូតុងចុចមើលផែនទី Google Maps
            </p>
          </div>

          <!-- Search & Filters -->
          <div class="flex flex-wrap items-center gap-3">
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="ស្វែងរក IP, User ID, ទីតាំង..."
                class="w-56 bg-slate-900/80 border border-slate-700 text-xs text-white rounded-xl px-3.5 py-2 pl-9 focus:outline-none focus:border-rose-500 transition-all placeholder:text-slate-500"
              />
              <span class="absolute left-3 top-2.5 text-xs text-slate-400">🔍</span>
            </div>

            <select
              v-model="severityFilter"
              class="bg-slate-900/80 border border-slate-700 text-xs text-white rounded-xl px-3 py-2 focus:outline-none focus:border-rose-500"
            >
              <option value="all">គ្រប់ Severity</option>
              <option value="CRITICAL">Critical</option>
              <option value="HIGH">High</option>
              <option value="LOW">Low</option>
            </select>
          </div>
        </div>

        <!-- Incidents Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/70 border-b border-slate-800 text-[11px] uppercase tracking-wider text-slate-400 font-semibold">
              <tr>
                <th class="py-3.5 px-4">កាលបរិច្ឆេទ & ម៉ោង</th>
                <th class="py-3.5 px-4">ប្រភេទ Threat</th>
                <th class="py-3.5 px-4">អត្តសញ្ញាណ Attacker</th>
                <th class="py-3.5 px-4">IP & ទីតាំង (Geo-IP)</th>
                <th class="py-3.5 px-4">ISP / Network</th>
                <th class="py-3.5 px-4">Intercepted Payload</th>
                <th class="py-3.5 px-4 text-right">សកម្មភាព (Actions)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr
                v-for="(inc, idx) in filteredIncidents"
                :key="idx"
                class="hover:bg-slate-800/30 transition-colors"
              >
                <!-- Timestamp -->
                <td class="py-3 px-4 font-mono text-[11px] whitespace-nowrap text-slate-400">
                  {{ inc.timestamp ? inc.timestamp.replace('T', ' ').replace('+00:00', '') : 'N/A' }}
                </td>

                <!-- Threat & Severity -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="space-y-1">
                    <span class="font-bold text-white block">{{ inc.threat_type }}</span>
                    <span class="inline-block px-2 py-0.5 text-[9px] font-bold rounded border uppercase" :class="getSeverityClass(inc.severity)">
                      {{ inc.severity || 'UNKNOWN' }}
                    </span>
                  </div>
                </td>

                <!-- Attacker Identity -->
                <td class="py-3 px-4">
                  <div class="space-y-0.5">
                    <div class="font-bold text-rose-300 font-mono">
                      ID: {{ inc.user_id }}
                    </div>
                    <div class="text-[11px] text-slate-300 flex items-center gap-1">
                      <span>{{ inc.name || 'Anonymous' }}</span>
                      <span v-if="inc.username" class="text-sky-400 font-mono">{{ inc.username }}</span>
                    </div>
                    <div v-if="inc.client_lang" class="text-[10px] text-slate-400">
                      Lang: <code class="text-slate-300">{{ inc.client_lang }}</code>
                    </div>
                  </div>
                </td>

                <!-- IP & Geo-Location -->
                <td class="py-3 px-4">
                  <div class="space-y-1">
                    <div class="font-bold text-white font-mono flex items-center gap-1.5">
                      <span class="text-slate-400">🌐</span>
                      <span>{{ inc.ip }}</span>
                    </div>
                    <div class="text-[11px] text-slate-300 flex items-center gap-1">
                      <span>📍</span>
                      <span>{{ inc.city || 'Unknown' }}, {{ inc.country || 'Unknown' }}</span>
                    </div>
                    <div v-if="inc.coordinates && inc.coordinates !== '0,0'">
                      <a
                        :href="'https://maps.google.com/?q=' + inc.coordinates"
                        target="_blank"
                        class="inline-flex items-center gap-1 text-[10px] text-sky-400 hover:text-sky-300 font-semibold bg-sky-500/10 px-2 py-0.5 rounded border border-sky-500/20"
                      >
                        <span>📍 មើលលើ Google Maps</span> ↗
                      </a>
                    </div>
                  </div>
                </td>

                <!-- ISP -->
                <td class="py-3 px-4 text-slate-300 text-[11px]">
                  <span class="font-medium">{{ inc.isp || 'Internal/Unknown ISP' }}</span>
                  <div v-if="inc.user_agent" class="text-[10px] text-slate-400 truncate max-w-[160px]" :title="inc.user_agent">
                    UA: {{ inc.user_agent }}
                  </div>
                </td>

                <!-- Payload -->
                <td class="py-3 px-4">
                  <div class="bg-slate-950/80 p-2 rounded-lg border border-slate-800 text-[11px] font-mono text-rose-300 max-w-xs break-all">
                    {{ inc.payload || 'N/A' }}
                  </div>
                </td>

                <!-- Actions -->
                <td class="py-3 px-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="handleBanUser(inc.user_id)"
                      class="px-2.5 py-1 bg-red-500/15 hover:bg-red-500/30 text-red-300 border border-red-500/30 rounded-lg text-[11px] font-bold transition-all"
                      title="Block & Ban User ID"
                    >
                      ⛔ Ban User
                    </button>
                    <button
                      @click="handleBlockIp(inc.ip)"
                      class="px-2.5 py-1 bg-amber-500/15 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 rounded-lg text-[11px] font-bold transition-all"
                      title="Blacklist IP on Firewall"
                    >
                      🛡️ Blacklist IP
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="filteredIncidents.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400">
                  <p class="text-sm">✨ ពុំមានការវាយប្រហារ ឬទិន្នន័យសង្ស័យដែលត្រូវបង្ហាញឡើយ។</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Emergency Auto-Defense Log Stream -->
      <div v-if="props.emergencyLogs && props.emergencyLogs.length > 0" class="bg-slate-950 border border-slate-800 rounded-2xl p-5 shadow-2xl space-y-3 font-mono">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div class="flex items-center gap-2 text-xs text-rose-400 font-bold">
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
            <span>storage/logs/emergency_defense.log (Active Defense & Emergency Calls Stream)</span>
          </div>
          <span class="text-[10px] text-slate-500">Live Auto-Mitigation Stream</span>
        </div>

        <div class="max-h-48 overflow-y-auto space-y-1.5 text-xs text-slate-300 font-mono leading-relaxed bg-black/40 p-4 rounded-xl border border-slate-900 scrollbar-thin">
          <div v-for="(elog, idx) in props.emergencyLogs" :key="idx" class="text-emerald-400/90 hover:text-white transition-colors">
            <span class="text-slate-500">{{ idx + 1 }}.</span> {{ elog }}
          </div>
        </div>
      </div>

      <!-- Raw Attacker Terminal Console View -->
      <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 shadow-2xl space-y-3 font-mono">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div class="flex items-center gap-2 text-xs text-slate-300 font-bold">
            <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
            <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
            <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
            <span class="ml-2 text-slate-400">storage/logs/attacker_log.txt (Raw Terminal Stream)</span>
          </div>
          <span class="text-[10px] text-slate-500">Live Structured Log Stream</span>
        </div>

        <div class="max-h-60 overflow-y-auto space-y-1.5 text-xs text-slate-300 font-mono leading-relaxed bg-black/40 p-4 rounded-xl border border-slate-900 scrollbar-thin">
          <div v-for="(log, idx) in rawAttackerLogs" :key="idx" class="text-rose-400/90 hover:text-white transition-colors">
            <span class="text-slate-500">{{ idx + 1 }}.</span> {{ log }}
          </div>
          <div v-if="rawAttackerLogs.length === 0" class="text-slate-500 italic">
            // កំណត់ត្រាទទេស្អាត (Log file is currently empty).
          </div>
        </div>
      </div>

      <!-- Blacklisted IP Management Drawer -->
      <div class="bg-slate-800/40 border border-slate-800 rounded-2xl p-5 backdrop-blur-xl shadow-xl space-y-4">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
          <span>🛡️ បញ្ជីរាយនាម IP ដែលត្រូវបាន Blacklist លើ Firewall ({{ blockedIps.length }})</span>
        </h3>

        <div class="flex flex-wrap gap-2">
          <div
            v-for="ip in blockedIps"
            :key="ip"
            class="px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-xl text-xs font-mono text-slate-200 flex items-center gap-2 shadow-sm"
          >
            <span>🌐 {{ ip }}</span>
            <button
              @click="handleUnblockIp(ip)"
              class="text-red-400 hover:text-red-300 font-bold ml-1"
              title="ដក IP នេះចេញពី Blacklist"
            >
              ✕
            </button>
          </div>
          <div v-if="blockedIps.length === 0" class="text-xs text-slate-400 italic">
            ពុំមាន IP ណាមួយត្រូវបាន Blacklist នៅឡើយទេ។
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
