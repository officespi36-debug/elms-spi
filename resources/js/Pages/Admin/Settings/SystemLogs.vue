<script setup lang="ts">
import { ref, computed } from 'vue'

export interface SystemLogItem {
  id: number | string
  datetime: string
  user: string
  email?: string
  role: 'Admin' | 'Teacher' | 'Student' | 'System'
  module: 'Authentication' | 'User Management' | 'Academic Structure' | 'Course Management' | 'Assessment' | 'AI Management' | 'System Settings'
  action: string
  description: string
  ip_address: string
  status: 'Success' | 'Failed'
  details?: Record<string, any>
  user_agent?: string
}

const props = defineProps<{
  initialLogs?: SystemLogItem[]
}>()

const emit = defineEmits<{
  (e: 'notify', msg: string): void
}>()

// Default Canonical System Logs matching SPI Requirements
const logs = ref<SystemLogItem[]>(props.initialLogs && props.initialLogs.length > 0 ? props.initialLogs : [
  {
    id: 1,
    datetime: '01/10/2026 10:30',
    user: 'Admin',
    email: 'admin@spi.edu.kh',
    role: 'Admin',
    module: 'Course Management',
    action: 'Approved Course',
    description: 'Approved course "Web Development" for Information Technology.',
    ip_address: '192.168.1.45',
    status: 'Success',
    details: {
      course_id: 104,
      course_name: 'Web Development',
      major: 'Information Technology',
      teacher: 'Sokha Chan',
      status: 'Approved'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 2,
    datetime: '01/10/2026 10:15',
    user: 'Admin',
    email: 'admin@spi.edu.kh',
    role: 'Admin',
    module: 'System Settings',
    action: 'Settings Updated',
    description: 'Updated general settings: Institution name "Saint Paul Institute", Timezone "Asia/Phnom_Penh", Date format "DD/MM/YYYY".',
    ip_address: '192.168.1.45',
    status: 'Success',
    details: {
      institution_name: 'Saint Paul Institute',
      institution_code: 'SPI',
      timezone: 'Asia/Phnom_Penh',
      date_format: 'DD/MM/YYYY'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 3,
    datetime: '01/10/2026 09:50',
    user: 'AI Engine',
    email: 'ai-daemon@spi.edu.kh',
    role: 'System',
    module: 'AI Management',
    action: 'AI Recommendation Generated',
    description: 'Generated adaptive study recommendations for 45 students in Agriculture (Weak Topic: Soil Management).',
    ip_address: '127.0.0.1',
    status: 'Success',
    details: {
      major: 'Agriculture',
      topic: 'Soil Management',
      student_count: 45,
      recommendation_type: 'Review Soil Preparation & Practice Quiz'
    },
    user_agent: 'SPI-AI-Inference-Engine/2.4'
  },
  {
    id: 4,
    datetime: '01/10/2026 09:22',
    user: 'Dara Sam',
    email: 'dara.sam@spi.edu.kh',
    role: 'Teacher',
    module: 'Assessment',
    action: 'Create Quiz',
    description: 'Created 15-question Midterm Assessment for "Organic Farming Principles" with auto-grading.',
    ip_address: '119.82.253.18',
    status: 'Success',
    details: {
      quiz_id: 88,
      quiz_name: 'Organic Farming Midterm',
      questions_count: 15,
      major: 'Agriculture'
    },
    user_agent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15'
  },
  {
    id: 5,
    datetime: '01/10/2026 08:45',
    user: 'Unknown Attacker',
    email: 'root@unknown.net',
    role: 'Student',
    module: 'Authentication',
    action: 'Failed Login',
    description: 'Repeated failed login attempt with invalid credentials (brute-force threshold alert triggered).',
    ip_address: '203.189.155.82',
    status: 'Failed',
    details: {
      attempted_email: 'admin@spi.edu.kh',
      attempts_count: 5,
      mitigation: 'IP Rate-limited for 15 minutes'
    },
    user_agent: 'python-requests/2.31.0'
  },
  {
    id: 6,
    datetime: '01/10/2026 08:30',
    user: 'Admin',
    email: 'admin@spi.edu.kh',
    role: 'Admin',
    module: 'Academic Structure',
    action: 'Update Academic Year',
    description: 'Enforced active academic year to "2026–2027" and verified 5 SPI major curriculum pipelines.',
    ip_address: '192.168.1.45',
    status: 'Success',
    details: {
      academic_year: '2026–2027',
      status: 'Active',
      previous_year: '2025–2026'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 7,
    datetime: '01/10/2026 08:05',
    user: 'Admin',
    email: 'admin@spi.edu.kh',
    role: 'Admin',
    module: 'User Management',
    action: 'Create User',
    description: 'Registered new teacher account "Dr. Bopha Meas" for Social Work Department.',
    ip_address: '192.168.1.45',
    status: 'Success',
    details: {
      user_name: 'Dr. Bopha Meas',
      role: 'Teacher',
      department: 'Social Work',
      email: 'bopha.meas@spi.edu.kh'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 8,
    datetime: '30/09/2026 17:15',
    user: 'Admin',
    email: 'admin@spi.edu.kh',
    role: 'Admin',
    module: 'AI Management',
    action: 'AI Configuration Changed',
    description: 'Updated AI diagnostic model confidence threshold to 85% and enabled Khmer language explanation prompts.',
    ip_address: '192.168.1.45',
    status: 'Success',
    details: {
      confidence_threshold: 0.85,
      language: 'Khmer/English',
      model: 'SPI-Gemini-Tutor-v2'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 9,
    datetime: '30/09/2026 16:40',
    user: 'Admin',
    email: 'admin@spi.edu.kh',
    role: 'Admin',
    module: 'Course Management',
    action: 'Reject Course',
    description: 'Rejected course "Advanced Agro-ecology" submitted by Sokunthea Chhay due to incomplete syllabus and zero quiz items.',
    ip_address: '192.168.1.45',
    status: 'Success',
    details: {
      course_name: 'Advanced Agro-ecology',
      reason: 'Missing syllabus and minimum quiz requirements',
      teacher: 'Sokunthea Chhay'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 10,
    datetime: '30/09/2026 14:10',
    user: 'Sokunthea Keo',
    email: 'sokunthea.keo@spi.edu.kh',
    role: 'Teacher',
    module: 'Assessment',
    action: 'Create Assignment',
    description: 'Published practical coding assignment "RESTful API Development with Laravel" for IT Year 3.',
    ip_address: '119.82.253.30',
    status: 'Success',
    details: {
      assignment_title: 'RESTful API Development with Laravel',
      max_score: 100,
      due_date: '15/10/2026'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 11,
    datetime: '30/09/2026 11:30',
    user: 'Admin',
    email: 'admin@spi.edu.kh',
    role: 'Admin',
    module: 'User Management',
    action: 'Disable User',
    description: 'Temporarily disabled student account "STU-Takeo-0914" per disciplinary committee notice.',
    ip_address: '192.168.1.45',
    status: 'Success',
    details: {
      student_id: 'STU-Takeo-0914',
      reason: 'Administrative inquiry pending'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 12,
    datetime: '30/09/2026 09:00',
    user: 'AI Engine',
    email: 'ai-daemon@spi.edu.kh',
    role: 'System',
    module: 'AI Management',
    action: 'AI Analysis Executed',
    description: 'Completed automated batch analysis for 2,458 active student learning telemetry records.',
    ip_address: '127.0.0.1',
    status: 'Success',
    details: {
      students_analyzed: 2458,
      at_risk_flagged: 18,
      retention_projection: '96.2%'
    },
    user_agent: 'SPI-AI-Analytics/1.9'
  },
  {
    id: 13,
    datetime: '29/09/2026 18:20',
    user: 'Kosal Sensok',
    email: 'kosalsensok@gmail.com',
    role: 'Admin',
    module: 'Authentication',
    action: 'Login',
    description: 'Successful administrative login via password and two-factor SMS OTP authentication.',
    ip_address: '192.168.1.10',
    status: 'Success',
    details: {
      auth_method: 'Password + 2FA SMS',
      location: 'Takeo Province, Cambodia'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 14,
    datetime: '29/09/2026 15:00',
    user: 'Admin',
    email: 'admin@spi.edu.kh',
    role: 'Admin',
    module: 'Academic Structure',
    action: 'Create Major',
    description: 'Synchronized major definition: English Literature under Faculty of Languages & Humanities.',
    ip_address: '192.168.1.45',
    status: 'Success',
    details: {
      major_code: 'ENG',
      major_name: 'English Literature',
      faculty: 'Languages & Humanities'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 15,
    datetime: '29/09/2026 13:45',
    user: 'Channak Roth',
    email: 'channak.roth@spi.edu.kh',
    role: 'Teacher',
    module: 'Assessment',
    action: 'Update Question',
    description: 'Updated question #42 in Tourism Management Question Bank with revised Angkor Wat heritage options.',
    ip_address: '119.82.253.22',
    status: 'Success',
    details: {
      question_id: 42,
      major: 'Tourism Management',
      editor: 'Channak Roth'
    },
    user_agent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0'
  },
  {
    id: 16,
    datetime: '29/09/2026 10:10',
    user: 'System Bot',
    email: 'cron@spi.edu.kh',
    role: 'System',
    module: 'Authentication',
    action: 'Logout',
    description: 'Automated expiration and token cleanup for 35 idle student sessions after 120 minutes of inactivity.',
    ip_address: '127.0.0.1',
    status: 'Success',
    details: {
      cleared_sessions: 35,
      inactivity_limit_minutes: 120
    },
    user_agent: 'SPI-Session-Cleaner/1.0'
  }
])

// Filter & Search States
const searchQuery = ref('')
const selectedModule = ref<string>('All')
const selectedRole = ref<string>('All')
const selectedStatus = ref<string>('All')
const selectedDateRange = ref<string>('All')

// Modal Details State
const showDetailModal = ref(false)
const selectedLog = ref<SystemLogItem | null>(null)

function openDetails(log: SystemLogItem) {
  selectedLog.value = log
  showDetailModal.value = true
}

function closeDetails() {
  showDetailModal.value = false
  selectedLog.value = null
}

// Filtered Logs
const filteredLogs = computed(() => {
  return logs.value.filter(log => {
    // Search matching
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase()
      const match =
        log.user.toLowerCase().includes(q) ||
        (log.email && log.email.toLowerCase().includes(q)) ||
        log.action.toLowerCase().includes(q) ||
        log.description.toLowerCase().includes(q) ||
        log.ip_address.toLowerCase().includes(q) ||
        log.module.toLowerCase().includes(q)
      if (!match) return false
    }

    // Module filter
    if (selectedModule.value !== 'All' && log.module !== selectedModule.value) {
      return false
    }

    // Role filter
    if (selectedRole.value !== 'All' && log.role !== selectedRole.value) {
      return false
    }

    // Status filter
    if (selectedStatus.value !== 'All' && log.status !== selectedStatus.value) {
      return false
    }

    return true
  })
})

// Statistics Computed
const stats = computed(() => {
  const total = logs.value.length
  const success = logs.value.filter(l => l.status === 'Success').length
  const failed = logs.value.filter(l => l.status === 'Failed').length
  const rate = total > 0 ? ((success / total) * 100).toFixed(1) : '100.0'
  return { total, success, failed, rate }
})

// Export CSV
function exportReportCSV() {
  const headers = ['ID', 'Date & Time', 'User', 'Role', 'Module', 'Action', 'Description', 'IP Address', 'Status']
  const rows = filteredLogs.value.map(l => [
    l.id,
    `"${l.datetime}"`,
    `"${l.user}"`,
    `"${l.role}"`,
    `"${l.module}"`,
    `"${l.action}"`,
    `"${l.description.replace(/"/g, '""')}"`,
    `"${l.ip_address}"`,
    `"${l.status}"`
  ])

  const csvContent = 'data:text/csv;charset=utf-8,\uFEFF' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n')
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `SPI_ELMS_System_Logs_${new Date().toISOString().slice(0, 10)}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  emit('notify', '📥 System Logs report exported to CSV successfully!')
}

// Module Icon & Style Helper
function getModuleBadge(module: string) {
  switch (module) {
    case 'Authentication':
      return { icon: '🔐', class: 'bg-amber-500/20 text-amber-300 border-amber-500/30' }
    case 'User Management':
      return { icon: '👥', class: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30' }
    case 'Academic Structure':
      return { icon: '🏫', class: 'bg-purple-500/20 text-purple-300 border-purple-500/30' }
    case 'Course Management':
      return { icon: '📚', class: 'bg-blue-500/20 text-blue-300 border-blue-500/30' }
    case 'Assessment':
      return { icon: '📝', class: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' }
    case 'AI Management':
      return { icon: '🤖', class: 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30' }
    case 'System Settings':
      return { icon: '⚙️', class: 'bg-rose-500/20 text-rose-300 border-rose-500/30' }
    default:
      return { icon: '📜', class: 'bg-slate-700 text-slate-300 border-slate-600' }
  }
}

function getRoleBadge(role: string) {
  switch (role) {
    case 'Admin':
      return 'bg-purple-500/20 text-purple-300 border-purple-500/40'
    case 'Teacher':
      return 'bg-blue-500/20 text-blue-300 border-blue-500/40'
    case 'Student':
      return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40'
    default:
      return 'bg-slate-700/60 text-slate-300 border-slate-600'
  }
}
</script>

<template>
  <div class="space-y-6 text-xs text-slate-200">
    <!-- Header Card -->
    <div class="bg-slate-800/90 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-700/60 pb-4">
        <div class="flex items-center gap-3">
          <div class="p-2.5 rounded-xl bg-blue-500/20 text-blue-400 border border-blue-500/30 text-lg">
            🧾
          </div>
          <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
              System Logs (កំណត់ត្រាសកម្មភាពប្រព័ន្ធ)
            </h2>
            <p class="text-[11px] text-slate-400">
              រក្សាទុកប្រវត្តិសកម្មភាពសំខាន់ៗក្នុងប្រព័ន្ធ ដើម្បីឱ្យ Admin អាចតាមដាន ពិនិត្យ និងស្វែងរកបញ្ហា
            </p>
          </div>
        </div>

        <!-- Export Action & Immutability Badge -->
        <div class="flex items-center gap-2">
          <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            Immutable Audit Trail ✓
          </span>
          <button
            @click="exportReportCSV"
            type="button"
            class="px-3.5 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl border border-slate-600 font-semibold transition flex items-center gap-1.5 shadow-sm"
          >
            <span>📥</span>
            <span>Export Report (CSV)</span>
          </button>
        </div>
      </div>

      <!-- KPI Statistics Overview -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-slate-900/60 border border-slate-700/60 rounded-xl p-3.5">
          <div class="text-[11px] text-slate-400 font-medium">Total Activity Logs</div>
          <div class="text-xl font-bold text-white font-mono mt-1">{{ stats.total }} <span class="text-xs font-normal text-slate-400">records</span></div>
        </div>

        <div class="bg-slate-900/60 border border-slate-700/60 rounded-xl p-3.5">
          <div class="text-[11px] text-slate-400 font-medium">Success Rate</div>
          <div class="text-xl font-bold text-emerald-400 font-mono mt-1">{{ stats.rate }}%</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-700/60 rounded-xl p-3.5">
          <div class="text-[11px] text-slate-400 font-medium">Failed / Warning Events</div>
          <div class="text-xl font-bold text-rose-400 font-mono mt-1">{{ stats.failed }}</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-700/60 rounded-xl p-3.5">
          <div class="text-[11px] text-slate-400 font-medium">Monitored Modules</div>
          <div class="text-xl font-bold text-cyan-400 font-mono mt-1">7 <span class="text-xs font-normal text-slate-400">Modules</span></div>
        </div>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-4 shadow-lg space-y-3">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
        <!-- Search Input -->
        <div class="md:col-span-2 relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="🔍 Search logs by user, action, description, IP..."
            class="w-full bg-slate-900/90 border border-slate-700 text-xs text-white rounded-xl pl-8 pr-3 py-2 focus:ring-2 focus:ring-blue-500 placeholder:text-slate-500 transition"
          />
          <svg class="w-4 h-4 text-slate-500 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- Module Filter -->
        <div>
          <select
            v-model="selectedModule"
            class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500"
          >
            <option value="All">All Modules (7)</option>
            <option value="Authentication">🔐 Authentication</option>
            <option value="User Management">👥 User Management</option>
            <option value="Academic Structure">🏫 Academic Structure</option>
            <option value="Course Management">📚 Course Management</option>
            <option value="Assessment">📝 Assessment</option>
            <option value="AI Management">🤖 AI Management</option>
            <option value="System Settings">⚙️ System Settings</option>
          </select>
        </div>

        <!-- Role Filter -->
        <div>
          <select
            v-model="selectedRole"
            class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500"
          >
            <option value="All">All Roles</option>
            <option value="Admin">Administrator</option>
            <option value="Teacher">Teacher</option>
            <option value="Student">Student</option>
            <option value="System">System / Bot</option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="selectedStatus"
            class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500"
          >
            <option value="All">All Status</option>
            <option value="Success">Success (ជោគជ័យ)</option>
            <option value="Failed">Failed (បរាជ័យ)</option>
          </select>
        </div>
      </div>

      <!-- Quick Module Category Pills -->
      <div class="flex items-center gap-1.5 overflow-x-auto pt-1 pb-0.5 border-t border-slate-700/40">
        <span class="text-[10px] text-slate-400 uppercase font-semibold shrink-0 mr-1">Quick Filter:</span>
        <button
          v-for="mod in ['All', 'Course Management', 'AI Management', 'Assessment', 'Authentication', 'User Management', 'Academic Structure', 'System Settings']"
          :key="mod"
          @click="selectedModule = mod"
          :class="selectedModule === mod ? 'bg-blue-600 text-white font-bold' : 'bg-slate-900/80 text-slate-400 hover:text-slate-200'"
          class="px-2.5 py-1 text-[10px] rounded-lg transition shrink-0 border border-slate-700/60"
        >
          {{ mod === 'All' ? '⚡ All (16)' : mod }}
        </button>
      </div>
    </div>

    <!-- Main System Logs Table -->
    <div class="bg-slate-800/90 border border-slate-700/60 rounded-2xl shadow-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-900/90 border-b border-slate-700 text-slate-400 uppercase text-[10px] font-semibold tracking-wider">
              <th class="p-3 pl-4">Date & Time</th>
              <th class="p-3">User</th>
              <th class="p-3">Role</th>
              <th class="p-3">Module</th>
              <th class="p-3">Action</th>
              <th class="p-3 min-w-[260px]">Description</th>
              <th class="p-3">IP Address</th>
              <th class="p-3">Status</th>
              <th class="p-3 pr-4 text-right">Details</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr
              v-for="log in filteredLogs"
              :key="log.id"
              class="hover:bg-slate-700/30 transition cursor-pointer"
              @click="openDetails(log)"
            >
              <!-- Date & Time -->
              <td class="p-3 pl-4 font-mono text-[11px] text-slate-300 whitespace-nowrap">
                {{ log.datetime }}
              </td>

              <!-- User -->
              <td class="p-3 whitespace-nowrap">
                <div class="font-semibold text-white">{{ log.user }}</div>
                <div v-if="log.email" class="text-[10px] text-slate-400 font-mono">{{ log.email }}</div>
              </td>

              <!-- Role -->
              <td class="p-3 whitespace-nowrap">
                <span :class="getRoleBadge(log.role)" class="px-2 py-0.5 text-[10px] font-bold rounded-full border">
                  {{ log.role }}
                </span>
              </td>

              <!-- Module -->
              <td class="p-3 whitespace-nowrap">
                <span :class="getModuleBadge(log.module).class" class="px-2 py-0.5 text-[10px] font-medium rounded-full border inline-flex items-center gap-1">
                  <span>{{ getModuleBadge(log.module).icon }}</span>
                  <span>{{ log.module }}</span>
                </span>
              </td>

              <!-- Action -->
              <td class="p-3 font-semibold text-slate-200 whitespace-nowrap">
                {{ log.action }}
              </td>

              <!-- Description -->
              <td class="p-3 text-slate-300 text-[11px]">
                <div class="truncate max-w-md" :title="log.description">
                  {{ log.description }}
                </div>
              </td>

              <!-- IP Address -->
              <td class="p-3 font-mono text-slate-400 text-[11px] whitespace-nowrap">
                {{ log.ip_address }}
              </td>

              <!-- Status -->
              <td class="p-3 whitespace-nowrap">
                <span
                  v-if="log.status === 'Success'"
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 inline-flex items-center gap-1"
                >
                  <span>✓</span>
                  <span>Success</span>
                </span>
                <span
                  v-else
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/40 inline-flex items-center gap-1"
                >
                  <span>✕</span>
                  <span>Failed</span>
                </span>
              </td>

              <!-- Actions -->
              <td class="p-3 pr-4 text-right whitespace-nowrap" @click.stop>
                <button
                  @click="openDetails(log)"
                  type="button"
                  class="px-2.5 py-1 bg-blue-600/20 hover:bg-blue-600 text-blue-300 hover:text-white rounded-lg border border-blue-500/40 transition font-medium text-[11px]"
                >
                  View
                </button>
              </td>
            </tr>

            <tr v-if="filteredLogs.length === 0">
              <td colspan="9" class="p-8 text-center text-slate-400">
                <div class="text-2xl mb-1">🔍</div>
                <div class="font-bold text-white">No logs match the current search or filters</div>
                <div class="text-xs text-slate-500 mt-1">Try clearing filters or changing your search keyword.</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer Info -->
      <div class="bg-slate-900/90 border-t border-slate-700/60 p-3.5 px-4 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 gap-2">
        <div>
          Showing <strong class="text-white">{{ filteredLogs.length }}</strong> of <strong class="text-white">{{ logs.length }}</strong> logged system events
        </div>
        <div class="text-slate-500 text-[10px] flex items-center gap-1">
          <span>🔒</span>
          <span>SPI Audit Compliance Rule: Logs are strictly append-only and cannot be manually modified.</span>
        </div>
      </div>
    </div>

    <!-- 👁️ LOG DETAIL MODAL -->
    <div
      v-if="showDetailModal && selectedLog"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
    >
      <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden space-y-4">
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-950/60">
          <div class="flex items-center gap-3">
            <span class="text-2xl">{{ getModuleBadge(selectedLog.module).icon }}</span>
            <div>
              <h3 class="font-bold text-white text-base">System Log Details</h3>
              <p class="text-xs text-slate-400">Log ID #{{ selectedLog.id }} • {{ selectedLog.datetime }}</p>
            </div>
          </div>
          <button @click="closeDetails" class="text-slate-400 hover:text-white text-lg p-1">✕</button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 space-y-4 text-xs">
          <!-- Summary Header Grid -->
          <div class="grid grid-cols-2 gap-3 bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
            <div>
              <span class="text-slate-500 text-[10px] block uppercase font-bold">User</span>
              <strong class="text-white">{{ selectedLog.user }}</strong>
              <div v-if="selectedLog.email" class="text-slate-400 text-[10px] font-mono">{{ selectedLog.email }}</div>
            </div>
            <div>
              <span class="text-slate-500 text-[10px] block uppercase font-bold">Role</span>
              <span :class="getRoleBadge(selectedLog.role)" class="px-2 py-0.5 text-[10px] font-bold rounded-full border inline-block mt-0.5">
                {{ selectedLog.role }}
              </span>
            </div>
            <div>
              <span class="text-slate-500 text-[10px] block uppercase font-bold">Module</span>
              <span class="text-slate-200 font-semibold">{{ selectedLog.module }}</span>
            </div>
            <div>
              <span class="text-slate-500 text-[10px] block uppercase font-bold">Status</span>
              <span
                :class="selectedLog.status === 'Success' ? 'text-emerald-400' : 'text-rose-400'"
                class="font-bold flex items-center gap-1"
              >
                <span>{{ selectedLog.status === 'Success' ? '✓' : '✕' }}</span>
                <span>{{ selectedLog.status }}</span>
              </span>
            </div>
          </div>

          <!-- Description Box -->
          <div class="space-y-1">
            <label class="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Action & Description</label>
            <div class="bg-slate-950 p-3 rounded-xl border border-slate-800 text-slate-200 font-medium">
              <div class="text-blue-400 font-bold text-xs mb-1">{{ selectedLog.action }}</div>
              <div>{{ selectedLog.description }}</div>
            </div>
          </div>

          <!-- Network & Client Info -->
          <div class="grid grid-cols-2 gap-3 bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
            <div>
              <span class="text-slate-500 text-[10px] block uppercase font-bold">IP Address</span>
              <code class="text-amber-400 font-mono text-[11px]">{{ selectedLog.ip_address }}</code>
            </div>
            <div>
              <span class="text-slate-500 text-[10px] block uppercase font-bold">Location</span>
              <span class="text-slate-300">Takeo / Cambodia</span>
            </div>
            <div class="col-span-2">
              <span class="text-slate-500 text-[10px] block uppercase font-bold">User Agent / Client</span>
              <div class="text-slate-400 font-mono text-[10px] truncate">{{ selectedLog.user_agent || 'Mozilla/5.0' }}</div>
            </div>
          </div>

          <!-- Payload / Metadata Details -->
          <div v-if="selectedLog.details" class="space-y-1">
            <label class="text-slate-400 font-bold uppercase text-[10px] tracking-wider">Context Payload (JSON)</label>
            <pre class="bg-slate-950 p-3 rounded-xl border border-slate-800 text-emerald-400 font-mono text-[10px] overflow-x-auto">{{ JSON.stringify(selectedLog.details, null, 2) }}</pre>
          </div>

          <!-- Tamper-evident Notice -->
          <div class="p-3 rounded-xl bg-purple-950/20 border border-purple-500/30 text-[11px] text-purple-300 flex items-start gap-2">
            <span class="text-base shrink-0">🔒</span>
            <div>
              <strong>Audit Trail Integrity:</strong>
              This record is securely stored in compliance with Saint Paul Institute academic auditing rules. System logs cannot be deleted or altered by standard administrative users.
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 border-t border-slate-800 flex justify-end bg-slate-950/60">
          <button
            @click="closeDetails"
            type="button"
            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl transition text-xs font-semibold"
          >
            Close Details
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
