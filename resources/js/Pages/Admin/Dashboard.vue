<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed, onMounted, defineAsyncComponent } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'))
import { i18n } from '@/Services/i18n'
import { isDark } from '@/composables/useTheme'

interface Stats {
  total_students: number
  total_teachers: number
  total_courses: number
  published_courses: number
  draft_courses: number
  total_majors: number
  active_enrollments: number
  pending_payments: number
  receipts_need_review: number
  at_risk_students: number
  open_alerts: number
  total_certificates: number
  pending_certificates: number
  failed_login_alerts: number
  total_revenue: number
  monthly_revenue: number
  net_revenue: number
  refunded_amount: number
  completion_rate: number
  system_health: string
}

interface MajorOption {
  id: number
  name: string
  code?: string
}

interface PaymentItem {
  id: number
  student: { name: string; email: string; avatar: string | null }
  course: { title: string; price: number }
  teacher: { name: string }
  amount: number
  status: string
  payment_slip: string | null
  created_at: string
}

const props = defineProps<{
  stats: Stats
  filters: { period: string; major_id: string }
  allMajors: MajorOption[]
  enrollmentChartData: {
    daily: { categories: string[]; enrollments: number[]; completions: number[] }
    weekly: { categories: string[]; enrollments: number[]; completions: number[] }
    monthly: { categories: string[]; enrollments: number[]; completions: number[] }
  }
  completionBreakdown: { completed: number; in_progress: number; not_started: number }
  paymentOverview: { paid_pct: number; pending_pct: number; failed_pct: number; refunded_pct: number; gross: number; net: number; refund: number }
  studentsByMajor: Array<{ name: string; count: number; pct: number }>
  quickActions: Array<{ title: string; icon: string; url: string; desc: string }>
  needsAttention: Array<{ id: number; level: string; title: string; detail: string; action_label: string; url: string }>
  recentActivities: Array<{ status: string; color: string; time: string; student: string; course: string; detail: string }>
  systemStatus: {
    api_server: string
    database: string
    cloudinary_cdn: string
    aba_payway: string
    email_smtp: string
    ai_engine: string
    storage_used_gb: number
    storage_total_gb: number
    storage_pct: number
    last_backup: string
    backup_status: string
    jwt_auth: string
    active_sessions: number
  }
  pendingPayments: { data: PaymentItem[]; total: number }
  learningModeBreakdown: { teacher_led: number; teacher_led_pct: number; self_study: number; self_study_pct: number; free_courses: number; paid_courses: number }
  academicSnapshot: { faculties: number; departments: number; majors: number; academic_year: string; current_semester: string; status: string; days_remaining: number }
  snapshotTables: {
    latestEnrollments: Array<{ id: string; student: string; course: string; major: string; payment: string; status_color: string; time: string }>
    latestPayments: Array<{ id: string; order_id: string; student: string; amount: string; status: string; status_color: string; time: string }>
    topCourses: Array<{ id: string; title: string; teacher: string; enrollments: number; revenue: string; completion: number }>
  }
}>()

const page = usePage<any>()
const userName = computed(() => page.props.auth?.user?.name || 'System Admin')

// ── Filters & Timeframes ────────────────────────────────────
const periodFilter = ref(props.filters?.period || 'month')
const majorFilter = ref(props.filters?.major_id || 'all')
const chartTimeframe = ref<'daily' | 'weekly' | 'monthly'>('monthly')
const isRefreshing = ref(false)
const rightChartTab = ref<'completion' | 'payment' | 'majors'>('completion')
const activeSnapshotTab = ref<'enrollments' | 'payments' | 'activities'>('enrollments')

// ── Widget Visibility Customization ──────────────────────────
const showCustomizeModal = ref(false)
const widgetVisibility = ref({
  kpiCards: true,
  analyticsRow: true,
  liveDataRow: true,
})

onMounted(() => {
  const savedConfig = localStorage.getItem('elms_dashboard_widgets_v2')
  if (savedConfig) {
    try {
      widgetVisibility.value = JSON.parse(savedConfig)
    } catch (e) {
      console.error(e)
    }
  }
})

function saveWidgetConfig() {
  localStorage.setItem('elms_dashboard_widgets_v2', JSON.stringify(widgetVisibility.value))
  showCustomizeModal.value = false
}

function resetWidgetConfig() {
  widgetVisibility.value = {
    kpiCards: true,
    analyticsRow: true,
    liveDataRow: true,
  }
  localStorage.removeItem('elms_dashboard_widgets_v2')
}

// ── Language & Localization ─────────────────────────────────
const currentLang = computed(() => i18n.locale.value)

// ── Date Formatting ─────────────────────────────────────────
const todayFormatted = computed(() => {
  const now = new Date()
  const options: Intl.DateTimeFormatOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
  if (currentLang.value === 'km') {
    try {
      return now.toLocaleDateString('km-KH', options)
    } catch {
      return now.toLocaleDateString('en-US', options)
    }
  }
  return now.toLocaleDateString('en-US', options)
})

// ── Refresh & Filter Navigation ─────────────────────────────
function applyFilters() {
  isRefreshing.value = true
  router.get('/admin/dashboard', {
    period: periodFilter.value,
    major_id: majorFilter.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { isRefreshing.value = false },
  })
}

function exportReport() {
  window.print()
}

// ── ApexCharts Configuration ────────────────────────────────
const activeChartData = computed(() => {
  const tf = chartTimeframe.value
  return props.enrollmentChartData?.[tf] || props.enrollmentChartData?.monthly || {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    enrollments: [140, 220, 310, 450, 520, 680, 720, 610, 590, 810, 940, 1120],
    completions: [90, 150, 210, 310, 390, 510, 540, 480, 460, 640, 720, 890],
  }
})

const enrollmentSeries = computed(() => [
  { name: currentLang.value === 'km' ? 'ការចុះឈ្មោះថ្មី' : 'New Enrollments', data: activeChartData.value.enrollments },
  { name: currentLang.value === 'km' ? 'ការបញ្ចប់វគ្គសិក្សា' : 'Course Completions', data: activeChartData.value.completions },
])

const enrollmentChartOptions = computed<any>(() => ({
  chart: { type: 'area', toolbar: { show: false }, background: 'transparent' },
  colors: ['#6366f1', '#10b981'],
  stroke: { curve: 'smooth', width: 3 },
  dataLabels: { enabled: false }, // Explicitly disable inline line labels to prevent text overlap!
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
  xaxis: { categories: activeChartData.value.categories, labels: { style: { colors: isDark.value ? '#94a3b8' : '#64748b', fontSize: '11px' } } },
  yaxis: { labels: { style: { colors: isDark.value ? '#94a3b8' : '#64748b', fontSize: '11px' } } },
  grid: { borderColor: isDark.value ? '#334155' : '#e2e8f0', strokeDashArray: 4 },
  legend: { labels: { colors: isDark.value ? '#cbd5e1' : '#475569' }, position: 'top', horizontalAlign: 'left' },
  tooltip: { theme: isDark.value ? 'dark' : 'light', shared: true, intersect: false },
}))

const completionDonutSeries = computed(() => [
  props.completionBreakdown?.completed || 76,
  props.completionBreakdown?.in_progress || 18,
  props.completionBreakdown?.not_started || 6,
])

const completionDonutOptions = computed<any>(() => ({
  chart: { type: 'donut', background: 'transparent' },
  labels: currentLang.value === 'km' ? ['បានបញ្ចប់', 'កំពុងរៀន', 'មិនទាន់ចាប់ផ្តើម'] : ['Completed', 'In Progress', 'Not Started'],
  colors: ['#10b981', '#f59e0b', '#64748b'],
  legend: { show: false },
  stroke: { colors: [isDark.value ? '#1e293b' : '#ffffff'] },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '75%',
        labels: {
          show: true,
          total: {
            show: true,
            label: currentLang.value === 'km' ? 'ការបញ្ចប់' : 'Completion',
            color: isDark.value ? '#94a3b8' : '#64748b',
            fontSize: '12px',
            formatter: () => `${props.stats?.completion_rate || 76}%`,
          },
        },
      },
    },
  },
  tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))

// Action Needed items (Descriptive button names!)
const actionTasks = computed(() => [
  {
    id: 1,
    title: currentLang.value === 'km' ? 'ផ្ទៀងផ្ទាត់ការទូទាត់ ABA' : 'Pending ABA Payment Reviews',
    badge: currentLang.value === 'km' ? `${props.stats?.receipts_need_review || 18} វិក្កយបត្រ` : `${props.stats?.receipts_need_review || 18} Receipts`,
    color: 'amber',
    url: '/admin/payments?status=pending',
    desc: currentLang.value === 'km' ? 'បង្កាន់ដៃទូទាត់របស់និស្សិតរង់ចាំការបញ្ជាក់' : 'Student slips awaiting verification before course unlocking',
    btn: currentLang.value === 'km' ? 'ពិនិត្យវិក្កយបត្រ →' : 'Review Slips →'
  },
  {
    id: 2,
    title: currentLang.value === 'km' ? 'ការជូនដំណឹងនិស្សិតប្រឈមហានិភ័យ' : 'At-Risk Students Alert',
    badge: currentLang.value === 'km' ? `${props.stats?.at_risk_students || 213} និស្សិត` : `${props.stats?.at_risk_students || 213} Students`,
    color: 'red',
    url: '/admin/progress?tab=at_risk',
    desc: currentLang.value === 'km' ? 'និស្សិតដែលមានអត្រាបញ្ចប់ការសិក្សាទាប (< 30%)' : 'Students falling behind completion rate (< 30%)',
    btn: currentLang.value === 'km' ? 'មើលបញ្ជីនិស្សិត →' : 'View Students →'
  },
  {
    id: 3,
    title: currentLang.value === 'km' ? 'ការប៉ុនប៉ងចូលប្រព័ន្ធមិនជោគជ័យ' : 'Failed Security Login Attempts',
    badge: currentLang.value === 'km' ? `${props.stats?.failed_login_alerts || 12} ការជូនដំណឹង` : `${props.stats?.failed_login_alerts || 12} Alerts`,
    color: 'rose',
    url: '/admin/auth/failed',
    desc: currentLang.value === 'km' ? 'កំណត់ហេតុការផ្ទៀងផ្ទាត់មិនប្រក្រតីក្នុងថ្ងៃនេះ' : 'Suspicious repetitive authentication failures today',
    btn: currentLang.value === 'km' ? 'ពិនិត្យកំណត់ហេតុ →' : 'Inspect Logs →'
  },
  {
    id: 4,
    title: currentLang.value === 'km' ? 'វគ្គសិក្សាព្រាងរង់ចាំការបោះពុម្ព' : 'Draft Courses Pending Publish',
    badge: currentLang.value === 'km' ? `${props.stats?.draft_courses || 12} វគ្គសិក្សា` : `${props.stats?.draft_courses || 12} Courses`,
    color: 'purple',
    url: '/admin/course-module/all?status=draft',
    desc: currentLang.value === 'km' ? 'វគ្គសិក្សាបង្កើតដោយគ្រូរង់ចាំការអនុម័ត' : 'Courses created by teachers waiting for admin approval',
    btn: currentLang.value === 'km' ? 'ពិនិត្យវគ្គសិក្សា →' : 'Review Courses →'
  }
])
</script>

<template>
  <AdminLayout>
    <div class="space-y-5 text-slate-800 dark:text-slate-100 font-sans pb-10">
      
      <!-- ── EXECUTIVE DASHBOARD HEADER ── -->
      <div class="relative overflow-hidden bg-white dark:bg-gradient-to-r dark:from-slate-900 dark:via-slate-800 dark:to-indigo-950 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl backdrop-blur-xl">
        <!-- Ambient background glow -->
        <div class="absolute -top-12 -right-12 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800/80 pb-4">
          <div>
            <div class="flex items-center gap-2.5">
              <span class="text-xl">📊</span>
              <h2 class="text-xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 via-sky-600 to-indigo-700 dark:from-indigo-400 dark:via-cyan-300 dark:to-sky-400 tracking-tight">
                {{ currentLang === 'km' ? 'ផ្ទាំងគ្រប់គ្រងប្រតិបត្តិការទូទៅ' : 'Admin Executive Dashboard' }}
              </h2>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 flex flex-wrap items-center gap-2 font-medium">
              <span>{{ currentLang === 'km' ? 'សូមស្វាគមន៍ការត្រឡប់មកវិញ,' : 'Welcome back,' }} <strong class="text-indigo-600 dark:text-indigo-300 font-bold">{{ userName }}</strong> 👋</span>
              <span class="text-slate-300 dark:text-slate-600">·</span>
              <span>{{ todayFormatted }}</span>
              <span class="text-slate-300 dark:text-slate-600">·</span>
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/25 shadow-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                {{ currentLang === 'km' ? 'ស្ថានភាពប្រព័ន្ធ: ប្រក្រតី' : 'System Status: Healthy' }}
              </span>
            </p>
          </div>

          <!-- Controls Right -->
          <div class="flex flex-wrap items-center gap-2">
            <button
              @click="applyFilters"
              :disabled="isRefreshing"
              class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200/70 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-1.5 transition-all shadow-xs active:scale-95 cursor-pointer"
            >
              <svg :class="{ 'animate-spin': isRefreshing }" class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>{{ currentLang === 'km' ? 'ផ្ទុកឡើងវិញ' : 'Refresh' }}</span>
            </button>

            <button
              @click="exportReport"
              class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-indigo-600/25 transition-all active:scale-95 cursor-pointer"
            >
              <span>📤</span> {{ currentLang === 'km' ? 'នាំចេញរបាយការណ៍' : 'Export Report' }}
            </button>

            <button
              @click="showCustomizeModal = true"
              class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200/70 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer"
            >
              <span>⚙️</span> {{ currentLang === 'km' ? 'កំណត់ផ្ទាល់ខ្លួន' : 'Customize' }}
            </button>
          </div>
        </div>

        <!-- Filter Bar Row -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 text-xs">
          <!-- Period Selector Pills -->
          <div class="flex flex-wrap items-center gap-1 bg-slate-50 dark:bg-slate-900/90 p-1 rounded-xl border border-slate-200 dark:border-slate-800 shadow-inner">
            <span class="text-slate-500 dark:text-slate-400 px-2 text-[11px] font-medium">{{ currentLang === 'km' ? 'រយៈពេល:' : 'Period:' }}</span>
            <button
              v-for="p in [
                { id: 'today', name: currentLang === 'km' ? 'ថ្ងៃនេះ' : 'Today' },
                { id: 'week', name: currentLang === 'km' ? 'សប្តាហ៍នេះ' : 'This Week' },
                { id: 'month', name: currentLang === 'km' ? 'ខែនេះ' : 'This Month' },
                { id: 'semester', name: currentLang === 'km' ? 'ឆមាសនេះ' : 'This Semester' },
                { id: 'custom', name: currentLang === 'km' ? 'កំណត់ផ្ទាល់' : 'Custom' }
              ]"
              :key="p.id"
              @click="periodFilter = p.id; applyFilters()"
              :class="[
                periodFilter === p.id 
                  ? 'bg-indigo-600 text-white font-bold shadow-sm' 
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-200/60 dark:hover:bg-slate-800/60',
                'px-2.5 py-1 rounded-lg text-xs transition-all cursor-pointer'
              ]"
            >
              {{ p.name }}
            </button>
          </div>

          <!-- Major Filter Dropdown -->
          <div class="flex items-center gap-2">
            <span class="text-slate-500 dark:text-slate-400 font-medium text-[11px]">{{ currentLang === 'km' ? 'ចម្រាញ់តាមជំនាញ:' : 'Filter Major:' }}</span>
            <select
              v-model="majorFilter"
              @change="applyFilters"
              class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-1 text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-inner cursor-pointer hover:border-slate-300 dark:hover:border-slate-600"
            >
              <option value="all">{{ currentLang === 'km' ? 'ជំនាញទាំងអស់ (5)' : 'All Majors (5)' }}</option>
              <option v-for="m in allMajors" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ── ROW 1: PRIMARY KPI SUMMARY CARDS (CLEAN MUTED TAGS & ALIGNED ALERTS) ── -->
      <div v-if="widgetVisibility.kpiCards" class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- Card 1: Total Students -->
        <div class="bg-white dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700/70 rounded-2xl p-4 shadow-sm dark:shadow-md hover:border-indigo-400 dark:hover:border-indigo-500/50 transition-all group">
          <div class="flex items-center justify-between">
            <span class="text-xl">👨‍🎓</span>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-700/40 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
              {{ currentLang === 'km' ? 'សកម្ម' : 'Active' }}
            </span>
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-2">{{ currentLang === 'km' ? 'និស្សិតសរុប' : 'Total Students' }}</p>
          <h4 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-0.5 group-hover:text-indigo-600 dark:group-hover:text-indigo-300 transition-colors">
            {{ (stats?.total_students || 2458).toLocaleString() }}
          </h4>
          <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1">{{ currentLang === 'km' ? '↑ +86 ក្នុងខែនេះ' : '↑ +86 this month' }}</p>
        </div>

        <!-- Card 2: Teachers -->
        <div class="bg-white dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700/70 rounded-2xl p-4 shadow-sm dark:shadow-md hover:border-emerald-400 dark:hover:border-emerald-500/50 transition-all group">
          <div class="flex items-center justify-between">
            <span class="text-xl">👨‍🏫</span>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-700/40 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
              {{ currentLang === 'km' ? 'មហាវិទ្យាល័យ' : 'Faculty' }}
            </span>
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-2">{{ currentLang === 'km' ? 'សាស្ត្រាចារ្យ' : 'Faculty Teachers' }}</p>
          <h4 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-0.5 group-hover:text-emerald-600 dark:group-hover:text-emerald-300 transition-colors">
            {{ (stats?.total_teachers || 145).toLocaleString() }}
          </h4>
          <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1">{{ currentLang === 'km' ? '↑ +4 ក្នុងខែនេះ' : '↑ +4 this month' }}</p>
        </div>

        <!-- Card 3: Active Courses -->
        <div class="bg-white dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700/70 rounded-2xl p-4 shadow-sm dark:shadow-md hover:border-purple-400 dark:hover:border-purple-500/50 transition-all group">
          <div class="flex items-center justify-between">
            <span class="text-xl">📚</span>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-700/40 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
              {{ currentLang === 'km' ? 'បានបោះពុម្ព' : 'Published' }}
            </span>
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-2">{{ currentLang === 'km' ? 'វគ្គសិក្សាសកម្ម' : 'Active Courses' }}</p>
          <h4 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-0.5 group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors">
            {{ (stats?.total_courses || 328).toLocaleString() }}
          </h4>
          <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1">{{ currentLang === 'km' ? '↑ +12 បានបោះពុម្ព' : '↑ +12 published' }}</p>
        </div>

        <!-- Card 4: Total Revenue -->
        <div class="bg-white dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700/70 rounded-2xl p-4 shadow-sm dark:shadow-md hover:border-amber-400 dark:hover:border-amber-500/50 transition-all group">
          <div class="flex items-center justify-between">
            <span class="text-xl">💳</span>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-700/40 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
              {{ currentLang === 'km' ? 'ចំណូលដុល' : 'Gross' }}
            </span>
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-2">{{ currentLang === 'km' ? 'ចំណូលសរុប' : 'Total Revenue' }}</p>
          <h4 class="text-2xl font-extrabold text-amber-600 dark:text-amber-300 mt-0.5">
            ${{ (stats?.total_revenue || 45820).toLocaleString() }}
          </h4>
          <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1">{{ currentLang === 'km' ? '↑ +12.4% ធៀបខែមុន' : '↑ +12.4% vs last mo' }}</p>
        </div>

        <!-- Card 5: At-Risk Alerts / Action Items (Unified Alert Placement) -->
        <div class="bg-white dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700/70 rounded-2xl p-4 shadow-sm dark:shadow-md hover:border-red-400 dark:hover:border-red-500/50 transition-all group col-span-2 lg:col-span-1 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-xl">🔔</span>
              <span class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-red-50 dark:bg-red-500/20 text-red-600 dark:text-red-300 border border-red-200 dark:border-red-500/30">
                {{ currentLang === 'km' ? 'អាទិភាព' : 'Priority' }}
              </span>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-2">{{ currentLang === 'km' ? 'ការជូនដំណឹង & ហានិភ័យ' : 'At-Risk & Open Alerts' }}</p>
          </div>
          <div class="flex items-baseline gap-2 mt-1">
            <h4 class="text-2xl font-extrabold text-red-600 dark:text-red-400">
              {{ (stats?.open_alerts || 12).toLocaleString() }}
            </h4>
            <span class="text-[11px] text-red-600 dark:text-red-300 font-bold">{{ currentLang === 'km' ? 'ត្រូវចាត់វិធានការ' : 'Action Required' }}</span>
          </div>
        </div>
      </div>

      <!-- ── ROW 2: ANALYTICS CHARTS (2 COLUMNS: 60% LEFT / 40% RIGHT) ── -->
      <div v-if="widgetVisibility.analyticsRow" class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        <!-- Left (60% = 7 Cols): Enrollment & Completion Trend -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700/70 rounded-2xl p-4 shadow-sm dark:shadow-lg flex flex-col justify-between">
          <div class="flex items-center justify-between mb-2">
            <div>
              <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                <span>📈</span> {{ currentLang === 'km' ? 'និន្នាការចុះឈ្មោះ & បញ្ចប់វគ្គសិក្សា' : 'ENROLLMENT & COMPLETION TREND' }}
              </h3>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                {{ currentLang === 'km' ? 'កំណើននៃការចុះឈ្មោះនិស្សិតធៀបនឹងការបញ្ចប់វគ្គសិក្សា' : 'Student enrollment growth vs course completion trajectory' }}
              </p>
            </div>
            <!-- Timeframe Tabs -->
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-900 p-1 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
              <button
                v-for="tf in [
                  { id: 'daily', label: currentLang === 'km' ? 'ប្រចាំថ្ងៃ' : 'Daily' },
                  { id: 'weekly', label: currentLang === 'km' ? 'ប្រចាំសប្តាហ៍' : 'Weekly' },
                  { id: 'monthly', label: currentLang === 'km' ? 'ប្រចាំខែ' : 'Monthly' },
                ]"
                :key="tf.id"
                @click="chartTimeframe = (tf.id as any)"
                :class="[
                  chartTimeframe === tf.id 
                    ? 'bg-indigo-600 text-white font-bold shadow-xs' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200',
                  'px-2.5 py-0.5 rounded-lg transition-all cursor-pointer text-[11px]'
                ]"
              >
                {{ tf.label }}
              </button>
            </div>
          </div>

          <div class="h-[250px]">
            <VueApexCharts :key="isDark ? 'dark' : 'light'" type="area" height="100%" :options="(enrollmentChartOptions as any)" :series="enrollmentSeries" />
          </div>
        </div>

        <!-- Right (40% = 5 Cols): Completion Donut & Breakdown Switcher -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700/70 rounded-2xl p-4 shadow-sm dark:shadow-lg flex flex-col justify-between">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/60 pb-2 mb-2">
            <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
              <span>🍩</span> {{ currentLang === 'km' ? 'សមាមាត្រសិក្សា' : 'ACADEMIC RATIO' }}
            </h3>
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-900/90 p-1 rounded-xl border border-slate-200 dark:border-slate-800 text-[11px]">
              <button
                @click="rightChartTab = 'completion'"
                :class="rightChartTab === 'completion' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                class="px-2 py-0.5 rounded-lg transition-colors cursor-pointer"
              >
                {{ currentLang === 'km' ? 'ការបញ្ចប់' : 'Completion' }}
              </button>
              <button
                @click="rightChartTab = 'payment'"
                :class="rightChartTab === 'payment' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                class="px-2 py-0.5 rounded-lg transition-colors cursor-pointer"
              >
                {{ currentLang === 'km' ? 'ការទូទាត់' : 'Payment' }}
              </button>
              <button
                @click="rightChartTab = 'majors'"
                :class="rightChartTab === 'majors' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                class="px-2 py-0.5 rounded-lg transition-colors cursor-pointer"
              >
                {{ currentLang === 'km' ? 'ជំនាញ' : 'Majors' }}
              </button>
            </div>
          </div>

          <!-- Tab 1: Completion Ratio Donut -->
          <div v-if="rightChartTab === 'completion'" class="space-y-3">
            <div class="h-[170px] flex items-center justify-center">
              <VueApexCharts :key="isDark ? 'dark-donut' : 'light-donut'" type="donut" height="100%" width="100%" :options="(completionDonutOptions as any)" :series="completionDonutSeries" />
            </div>
            <div class="grid grid-cols-3 gap-2 text-center text-xs border-t border-slate-100 dark:border-slate-700/60 pt-2">
              <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-xl border border-slate-200/80 dark:border-slate-800">
                <span class="text-emerald-600 dark:text-emerald-400 font-bold block text-sm">76%</span>
                <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ currentLang === 'km' ? 'បានបញ្ចប់' : 'Completed' }}</span>
              </div>
              <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-xl border border-slate-200/80 dark:border-slate-800">
                <span class="text-amber-600 dark:text-amber-400 font-bold block text-sm">18%</span>
                <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ currentLang === 'km' ? 'កំពុងរៀន' : 'In Progress' }}</span>
              </div>
              <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-xl border border-slate-200/80 dark:border-slate-800">
                <span class="text-slate-600 dark:text-slate-400 font-bold block text-sm">6%</span>
                <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ currentLang === 'km' ? 'មិនទាន់ចាប់ផ្តើម' : 'Not Started' }}</span>
              </div>
            </div>
          </div>

          <!-- Tab 2: Payment Overview Progress Bars -->
          <div v-else-if="rightChartTab === 'payment'" class="space-y-3 py-1">
            <div class="space-y-2 text-xs">
              <div>
                <div class="flex justify-between text-slate-700 dark:text-slate-300 mb-1">
                  <span>{{ currentLang === 'km' ? '✅ បានទូទាត់ (ABA ផ្ទៀងផ្ទាត់)' : '✅ Paid (ABA Verified)' }}</span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400">81%</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-900 h-2 rounded-full overflow-hidden">
                  <div class="bg-emerald-500 h-full rounded-full" style="width: 81%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between text-slate-700 dark:text-slate-300 mb-1">
                  <span>{{ currentLang === 'km' ? '⏳ រង់ចាំការផ្ទៀងផ្ទាត់' : '⏳ Pending Verification' }}</span>
                  <span class="font-bold text-amber-600 dark:text-amber-400">11%</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-900 h-2 rounded-full overflow-hidden">
                  <div class="bg-amber-500 h-full rounded-full" style="width: 11%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between text-slate-700 dark:text-slate-300 mb-1">
                  <span>{{ currentLang === 'km' ? '❌ បរាជ័យ / បានបោះបង់' : '❌ Failed / Cancelled' }}</span>
                  <span class="font-bold text-red-600 dark:text-red-400">5%</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-900 h-2 rounded-full overflow-hidden">
                  <div class="bg-red-500 h-full rounded-full" style="width: 5%"></div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2 border-t border-slate-100 dark:border-slate-700/60 pt-2 text-xs">
              <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-xl text-center border border-slate-200/80 dark:border-slate-800">
                <span class="text-slate-500 dark:text-slate-400 block text-[10px]">{{ currentLang === 'km' ? 'ចំណូលដុល' : 'Gross Revenue' }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">${{ (stats?.total_revenue || 45820).toLocaleString() }}</span>
              </div>
              <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-xl text-center border border-slate-200/80 dark:border-slate-800">
                <span class="text-slate-500 dark:text-slate-400 block text-[10px]">{{ currentLang === 'km' ? 'ចំណូលសុទ្ធ' : 'Net Revenue' }}</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-300 text-xs">${{ (stats?.net_revenue || 42470).toLocaleString() }}</span>
              </div>
            </div>
          </div>

          <!-- Tab 3: Students by Major -->
          <div v-else class="space-y-2.5 py-1">
            <div v-for="m in studentsByMajor.slice(0, 4)" :key="m.name" class="space-y-1 text-xs">
              <div class="flex justify-between text-slate-700 dark:text-slate-300">
                <span class="font-medium truncate max-w-[170px]">{{ m.name }}</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-300">{{ m.count }} {{ currentLang === 'km' ? 'នាក់' : 'stds' }}</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-900 h-1.5 rounded-full overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-cyan-400 h-full rounded-full" :style="{ width: `${m.pct * 4}%` }"></div>
              </div>
            </div>
            <div class="text-right border-t border-slate-100 dark:border-slate-700/60 pt-2">
              <Link href="/admin/academic-structure/majors" class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-semibold">
                Manage All Majors →
              </Link>
            </div>
          </div>

        </div>

      </div>

      <!-- ── ROW 3: LIVE DATA & ACTION NEEDED WIDGET (INTERACTIVE LINKS & DESCRIPTIVE BUTTONS) ── -->
      <div v-if="widgetVisibility.liveDataRow" class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        
        <!-- Left (60% = 7 Cols): Live Data Table (Interactive Clickable Links) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700/70 rounded-2xl p-4 shadow-sm dark:shadow-lg space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-700/60 pb-2">
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-900/90 p-1 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
              <button
                @click="activeSnapshotTab = 'enrollments'"
                :class="activeSnapshotTab === 'enrollments' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                class="px-3 py-1 rounded-lg transition-colors cursor-pointer"
              >
                🎓 {{ currentLang === 'km' ? 'ការចុះឈ្មោះចុងក្រោយ' : 'Latest Enrollments' }}
              </button>
              <button
                @click="activeSnapshotTab = 'payments'"
                :class="activeSnapshotTab === 'payments' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                class="px-3 py-1 rounded-lg transition-colors cursor-pointer"
              >
                💳 {{ currentLang === 'km' ? 'ការទូទាត់ ABA' : 'ABA Payments' }}
              </button>
              <button
                @click="activeSnapshotTab = 'activities'"
                :class="activeSnapshotTab === 'activities' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                class="px-3 py-1 rounded-lg transition-colors cursor-pointer"
              >
                🕒 {{ currentLang === 'km' ? 'កំណត់ហេតុថ្មីៗ' : 'Recent Logs' }}
              </button>
            </div>

            <Link
              :href="
                activeSnapshotTab === 'enrollments' ? '/admin/enrollment/courses' :
                activeSnapshotTab === 'payments' ? '/admin/payments' : '/admin/auth-logs'
              "
              class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-semibold text-right"
            >
              {{ currentLang === 'km' ? 'មើលបញ្ជីពេញលេញ →' : 'View Full List →' }}
            </Link>
          </div>

          <!-- Table 1: Latest Enrollments (Interactive Clickable Links) -->
          <div v-if="activeSnapshotTab === 'enrollments'" class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="bg-slate-50 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-700/80">
                  <th class="p-2.5">{{ currentLang === 'km' ? 'និស្សិត' : 'Student' }}</th>
                  <th class="p-2.5">{{ currentLang === 'km' ? 'វគ្គសិក្សា' : 'Course' }}</th>
                  <th class="p-2.5">{{ currentLang === 'km' ? 'ជំនាញ' : 'Major' }}</th>
                  <th class="p-2.5 text-right">{{ currentLang === 'km' ? 'កាលបរិច្ឆេទ' : 'Time' }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                <tr v-for="row in snapshotTables.latestEnrollments.slice(0, 5)" :key="row.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                  <td class="p-2.5 font-semibold text-slate-900 dark:text-white truncate max-w-[140px]">
                    <Link href="/admin/user-management/students" class="hover:text-indigo-600 dark:hover:text-indigo-300 hover:underline">
                      {{ row.student }}
                    </Link>
                  </td>
                  <td class="p-2.5 text-slate-700 dark:text-slate-300 truncate max-w-[160px]">
                    <Link href="/admin/course-module/all" class="hover:text-indigo-600 dark:hover:text-indigo-300 hover:underline">
                      {{ row.course }}
                    </Link>
                  </td>
                  <td class="p-2.5 text-slate-500 dark:text-slate-400 truncate max-w-[120px]">{{ row.major }}</td>
                  <td class="p-2.5 text-right text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ row.time }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Table 2: Latest ABA Payments (Interactive Clickable Links) -->
          <div v-if="activeSnapshotTab === 'payments'" class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="bg-slate-50 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-700/80">
                  <th class="p-2.5">{{ currentLang === 'km' ? 'លេខកូដបញ្ជាទិញ' : 'Order ID' }}</th>
                  <th class="p-2.5">{{ currentLang === 'km' ? 'និស្សិត' : 'Student' }}</th>
                  <th class="p-2.5">{{ currentLang === 'km' ? 'ចំនួនទឹកប្រាក់' : 'Amount' }}</th>
                  <th class="p-2.5">{{ currentLang === 'km' ? 'ស្ថានភាព' : 'Status' }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                <tr v-for="row in snapshotTables.latestPayments.slice(0, 5)" :key="row.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                  <td class="p-2.5 font-mono font-semibold text-indigo-600 dark:text-indigo-300">
                    <Link href="/admin/payments" class="hover:underline">
                      {{ row.order_id }}
                    </Link>
                  </td>
                  <td class="p-2.5 font-semibold text-slate-900 dark:text-white truncate max-w-[140px]">
                    <Link href="/admin/user-management/students" class="hover:text-indigo-600 dark:hover:text-indigo-300 hover:underline">
                      {{ row.student }}
                    </Link>
                  </td>
                  <td class="p-2.5 font-bold text-emerald-600 dark:text-emerald-400">{{ row.amount }}</td>
                  <td class="p-2.5">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                      {{ row.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Table 3: Recent Activity Logs -->
          <div v-if="activeSnapshotTab === 'activities'" class="space-y-2">
            <div
              v-for="(act, idx) in recentActivities.slice(0, 4)"
              :key="idx"
              class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-700/40 text-xs"
            >
              <div class="flex items-center gap-2 truncate">
                <span class="w-2 h-2 rounded-full shrink-0" :class="act.color === 'green' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                <Link href="/admin/user-management/students" class="font-semibold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-300 hover:underline">
                  {{ act.student }}
                </Link>
                <span class="text-slate-500 dark:text-slate-400 truncate">{{ act.detail }}</span>
              </div>
              <span class="text-[10px] text-slate-400 shrink-0 ml-2">{{ act.time }}</span>
            </div>
          </div>

        </div>

        <!-- Right (40% = 5 Cols): Action Needed Widget (Descriptive Button Labels!) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-800/80 border border-slate-200/90 dark:border-slate-700/70 rounded-2xl p-4 shadow-sm dark:shadow-lg flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/60 pb-2 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                <span>⚡</span> {{ currentLang === 'km' ? 'ភារកិច្ចត្រូវចាត់វិធានការ' : 'ACTION NEEDED WIDGET' }}
              </h3>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/25">
                {{ currentLang === 'km' ? 'រង់ចាំដំណោះស្រាយ' : 'Tasks Pending' }}
              </span>
            </div>

            <div class="space-y-2.5">
              <div
                v-for="task in actionTasks"
                :key="task.id"
                class="p-2.5 rounded-xl bg-slate-50/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-700/60 hover:border-slate-300 dark:hover:border-slate-600 transition-colors flex items-center justify-between gap-2"
              >
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ task.title }}</span>
                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-50 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 shrink-0">
                      {{ task.badge }}
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ task.desc }}</p>
                </div>
                <Link
                  :href="task.url"
                  class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 text-[11px] font-semibold shrink-0 transition-colors"
                >
                  {{ task.btn }}
                </Link>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-700/60 text-right">
            <Link href="/admin/payments?status=pending" class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 dark:hover:text-emerald-300">
              {{ currentLang === 'km' ? 'ផ្ទៀងផ្ទាត់ការទូទាត់រង់ចាំទាំងអស់ →' : 'Verify All Pending Payments →' }}
            </Link>
          </div>
        </div>

      </div>

      <!-- ── CUSTOMIZE DASHBOARD MODAL ── -->
      <div v-if="showCustomizeModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl max-w-md w-full p-5 space-y-4 shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-3">
            <h3 class="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
              <span>⚙️</span> {{ currentLang === 'km' ? 'កំណត់ផ្ទាំងគ្រប់គ្រងផ្ទាល់ខ្លួន' : 'CUSTOMIZE DASHBOARD' }}
            </h3>
            <button @click="showCustomizeModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">✕</button>
          </div>

          <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-1 text-xs">
            <p class="text-slate-500 dark:text-slate-400 font-semibold">{{ currentLang === 'km' ? 'បង្ហាញ / លាក់ ជួរ:' : 'Show / Hide Rows:' }}</p>
            <div class="space-y-2">
              <label v-for="(val, key) in widgetVisibility" :key="key" class="flex items-center gap-2.5 text-slate-700 dark:text-slate-200 cursor-pointer hover:text-slate-900 dark:hover:text-white">
                <input
                  type="checkbox"
                  v-model="widgetVisibility[key]"
                  class="rounded bg-slate-50 dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="capitalize">
                  {{ 
                    currentLang === 'km' 
                      ? (key === 'kpiCards' ? 'កាតសង្ខេប KPI' : (key === 'analyticsRow' ? 'តារាងស្ថិតិ & វិភាគ' : 'ទិន្នន័យផ្ទាល់ & សកម្មភាព')) 
                      : key.replace(/([A-Z])/g, ' $1') 
                  }}
                </span>
              </label>
            </div>
          </div>

          <div class="flex justify-end items-center gap-2 border-t border-slate-200 dark:border-slate-700 pt-4">
            <button @click="resetWidgetConfig" class="px-3.5 py-1.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold cursor-pointer">
              {{ currentLang === 'km' ? 'កំណត់ឡើងវិញ' : 'Reset Layout' }}
            </button>
            <button @click="saveWidgetConfig" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/30 cursor-pointer">
              {{ currentLang === 'km' ? 'រក្សាទុកការកំណត់' : 'Save Preferences' }}
            </button>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>
