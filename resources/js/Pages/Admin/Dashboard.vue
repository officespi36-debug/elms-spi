<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed, defineAsyncComponent } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'))
import { useLanguage } from '@/Services/i18n'
import { isDark } from '@/composables/useTheme'

const { currentLang, t } = useLanguage()

interface MajorPerformanceItem {
  name: string
  completed: number
  in_progress: number
  not_started: number
  total_count: number
}

const props = withDefaults(defineProps<{
  stats?: any
  filters?: { period?: string; major_id?: string }
  allMajors?: Array<any>
  enrollmentChartData?: {
    daily?: { categories: string[]; enrollments: number[]; completions: number[] }
    weekly?: { categories: string[]; enrollments: number[]; completions: number[] }
    monthly?: { categories: string[]; enrollments: number[]; completions: number[] }
  }
  completionBreakdown?: { completed: number; in_progress: number; not_started: number }
  majorPerformance?: Record<string, MajorPerformanceItem>
  studentsByMajor?: Array<{ name: string; name_kh?: string; count: number; pct: number }>
  adminAlerts?: Array<{ id: number; icon: string; level: string; title: string; detail: string; action_label: string; url: string }>
}>(), {
  stats: () => ({
    total_students: 2458,
    active_students: 2390,
    total_teachers: 145,
    active_teachers: 140,
    total_courses: 328,
    published_courses: 290,
    draft_courses: 5,
    completion_rate: 76,
    at_risk_students: 12,
  }),
  filters: () => ({ period: 'month', major_id: 'all' }),
  allMajors: () => [
    { id: 1, name: 'Information Technology' },
    { id: 2, name: 'Social Work' },
    { id: 3, name: 'Agriculture' },
    { id: 4, name: 'Tourism' },
    { id: 5, name: 'English Literature' },
  ],
  enrollmentChartData: () => ({
    daily: {
      categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      enrollments: [120, 210, 180, 290, 420, 310, 520],
      completions: [40, 85, 90, 140, 210, 190, 280]
    },
    weekly: {
      categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      enrollments: [450, 620, 580, 808],
      completions: [210, 340, 310, 490]
    },
    monthly: {
      categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      enrollments: [140, 220, 310, 450, 520, 680, 720, 610, 590, 810, 940, 1120],
      completions: [90, 150, 210, 310, 390, 510, 540, 480, 460, 640, 720, 890]
    }
  }),
  completionBreakdown: () => ({ completed: 76, in_progress: 18, not_started: 6 }),
  majorPerformance: () => ({
    all: { name: 'All Majors', completed: 76, in_progress: 18, not_started: 6, total_count: 2458 },
    1: { name: 'Information Technology', completed: 82, in_progress: 14, not_started: 4, total_count: 520 },
    2: { name: 'Social Work', completed: 74, in_progress: 20, not_started: 6, total_count: 548 },
    3: { name: 'Agriculture', completed: 70, in_progress: 22, not_started: 8, total_count: 600 },
    4: { name: 'Tourism', completed: 68, in_progress: 24, not_started: 8, total_count: 410 },
    5: { name: 'English Literature', completed: 85, in_progress: 11, not_started: 4, total_count: 380 }
  }),
  studentsByMajor: () => [
    { name: 'Information Technology', name_kh: 'បច្ចេកវិទ្យាព័ត៌មាន', count: 520, pct: 21 },
    { name: 'Social Work', name_kh: 'ការងារសង្គម', count: 548, pct: 23 },
    { name: 'Agriculture', name_kh: 'កសិកម្ម', count: 600, pct: 24 },
    { name: 'Tourism', name_kh: 'ទេសចរណ៍', count: 410, pct: 17 },
    { name: 'English Literature', name_kh: 'អក្សរសាស្ត្រអង់គ្លេស', count: 380, pct: 15 },
  ],
  adminAlerts: () => [
    {
      id: 1,
      icon: '⚠',
      level: 'red',
      title: '12 At-Risk Students',
      detail: 'AI-detected low course completion (< 30%) and missed quiz deadlines',
      action_label: 'View Students →',
      url: '/admin/progress?tab=at_risk'
    },
    {
      id: 2,
      icon: '📚',
      level: 'purple',
      title: '5 Courses Waiting for Approval',
      detail: 'Teacher-created courses submitted for syllabus & publication review',
      action_label: 'Review Courses →',
      url: '/admin/course-module/all?status=draft'
    },
    {
      id: 3,
      icon: '👨‍🏫',
      level: 'amber',
      title: '3 Teacher Accounts Pending',
      detail: 'Faculty teaching accounts awaiting department role assignment',
      action_label: 'Review Teachers →',
      url: '/admin/user-management/teachers'
    },
    {
      id: 4,
      icon: '📢',
      level: 'blue',
      title: '2 New System Notifications',
      detail: 'Important academic announcements scheduled for semester launch',
      action_label: 'View Notifications →',
      url: '/admin/notifications/announcements'
    }
  ]
})

const page = usePage<any>()
const userName = computed(() => page.props.auth?.user?.name || 'Admin')

// Period & Filters state
const periodFilter = ref(props.filters?.period || 'month')
const majorFilter = ref(props.filters?.major_id || 'all')
const chartTimeframe = ref<'daily' | 'weekly' | 'monthly'>('monthly')
const isRefreshing = ref(false)

// Academic Performance Major Filter (for right card)
const selectedPerfMajor = ref<string>('all')

const currentPerformance = computed(() => {
  const perf = props.majorPerformance?.[selectedPerfMajor.value] || props.majorPerformance?.['all']
  return perf || {
    name: 'All Majors',
    completed: 76,
    in_progress: 18,
    not_started: 6,
    total_count: 2458
  }
})

// Refresh action
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

const getMajorDisplayName = (name: string) => {
  const map: Record<string, { km: string; en: string }> = {
    'All Majors': { km: 'ជំនាញទាំង ៥ ទាំងអស់', en: 'All 5 Majors' },
    'Information Technology': { km: 'បច្ចេកវិទ្យាព័ត៌មាន (IT)', en: 'Information Technology' },
    'Social Work': { km: 'ការងារសង្គម (SW)', en: 'Social Work' },
    'Agriculture': { km: 'កសិកម្ម (AGR)', en: 'Agriculture' },
    'Tourism': { km: 'ទេសចរណ៍ (TRM)', en: 'Tourism' },
    'English Literature': { km: 'អក្សរសាស្ត្រអង់គ្លេស (ENG)', en: 'English Literature' },
  }
  if (map[name]) {
    return currentLang.value === 'km' ? map[name].km : map[name].en
  }
  return name
}

const majorsList = computed(() => {
  if (props.allMajors && props.allMajors.length > 0) {
    return props.allMajors
  }
  return [
    { id: 1, name: 'Information Technology' },
    { id: 2, name: 'Social Work' },
    { id: 3, name: 'Agriculture' },
    { id: 4, name: 'Tourism' },
    { id: 5, name: 'English Literature' },
  ]
})

// Enrollment Chart Series
const activeChartData = computed(() => {
  const tf = chartTimeframe.value
  const data = props.enrollmentChartData?.[tf] || props.enrollmentChartData?.monthly
  return {
    categories: data?.categories || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    enrollments: data?.enrollments || [140, 220, 310, 450, 520, 680, 720, 610, 590, 810, 940, 1120],
    completions: data?.completions || [90, 150, 210, 310, 390, 510, 540, 480, 460, 640, 720, 890],
  }
})

const enrollmentSeries = computed(() => [
  { name: t('ការចុះឈ្មោះថ្មី', 'New Enrollments'), data: activeChartData.value.enrollments },
  { name: t('ការបញ្ចប់វគ្គសិក្សា', 'Course Completions'), data: activeChartData.value.completions },
])

const enrollmentChartOptions = computed<any>(() => ({
  chart: { type: 'area', toolbar: { show: false }, background: 'transparent' },
  colors: ['#6366f1', '#10b981'],
  stroke: { curve: 'smooth', width: 3 },
  dataLabels: { enabled: false },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
  xaxis: {
    categories: activeChartData.value.categories,
    labels: { style: { colors: isDark.value ? '#94a3b8' : '#64748b', fontSize: '11px' } }
  },
  yaxis: {
    labels: { style: { colors: isDark.value ? '#94a3b8' : '#64748b', fontSize: '11px' } }
  },
  grid: { borderColor: isDark.value ? '#334155' : '#e2e8f0', strokeDashArray: 4 },
  legend: { labels: { colors: isDark.value ? '#cbd5e1' : '#475569' }, position: 'top', horizontalAlign: 'right' },
  tooltip: { theme: isDark.value ? 'dark' : 'light', shared: true, intersect: false },
}))

// Donut Chart Series & Options for Academic Performance
const completionDonutSeries = computed(() => [
  currentPerformance.value.completed,
  currentPerformance.value.in_progress,
  currentPerformance.value.not_started,
])

const completionDonutOptions = computed<any>(() => ({
  chart: { type: 'donut', background: 'transparent' },
  labels: [
    t('បានបញ្ចប់', 'Completed'),
    t('កំពុងរៀន', 'In Progress'),
    t('មិនទាន់ចាប់ផ្ដើម', 'Not Started')
  ],
  colors: ['#10b981', '#f59e0b', '#64748b'],
  legend: { show: false },
  stroke: { colors: [isDark.value ? '#1e293b' : '#ffffff'] },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '72%',
        labels: {
          show: true,
          total: {
            show: true,
            label: t('ការបញ្ចប់', 'Completion'),
            color: isDark.value ? '#94a3b8' : '#64748b',
            fontSize: '11px',
            formatter: () => `${currentPerformance.value.completed}%`,
          },
        },
      },
    },
  },
  tooltip: { theme: isDark.value ? 'dark' : 'light' },
}))
</script>

<template>
  <AdminLayout :title="t('ផ្ទាំងគ្រប់គ្រង Admin', 'Admin Dashboard')">
    <div class="space-y-6 text-slate-800 dark:text-slate-100 font-sans pb-10">

      <!-- ── 1. HEADER (Filters, Refresh, Export) ── -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none backdrop-blur-xl">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <!-- Title & Welcome -->
          <div>
            <div class="flex items-center gap-2.5">
              <span class="text-2xl">🏠</span>
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ t('ផ្ទាំងគ្រប់គ្រង ADMIN', 'ADMIN DASHBOARD') }}
              </h1>
              <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                Official ELMS
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 font-medium">
              <span>{{ t('សូមស្វាគមន៍,', 'Welcome,') }} <strong class="text-slate-900 dark:text-white font-bold">{{ userName }}</strong> 👋</span>
              <span class="text-slate-300 dark:text-slate-700">·</span>
              <span>{{ t('ទិដ្ឋភាពទូទៅនៃប្រតិបត្តិការសិក្សា និងសុខភាពប្រព័ន្ធ', 'Overview of Institutional Learning Performance & System Health') }}</span>
            </p>
          </div>

          <!-- Controls: Period, Major, Refresh, Export -->
          <div class="flex flex-wrap items-center gap-2.5">
            <!-- Period Filter -->
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700 text-xs">
              <button
                v-for="p in [
                  { id: 'today', name: t('ថ្ងៃនេះ', 'Today') },
                  { id: 'week', name: t('សប្តាហ៍', 'Week') },
                  { id: 'month', name: t('ខែ', 'Month') },
                  { id: 'semester', name: t('ឆមាស', 'Semester') }
                ]"
                :key="p.id"
                @click="periodFilter = p.id; applyFilters()"
                :class="[
                  periodFilter === p.id 
                    ? 'bg-emerald-600 text-white font-bold shadow-xs' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200',
                  'px-2.5 py-1 rounded-lg text-xs transition-all cursor-pointer'
                ]"
              >
                {{ p.name }}
              </button>
            </div>

            <!-- Major Filter Dropdown -->
            <select
              v-model="majorFilter"
              @change="applyFilters"
              :style="{ colorScheme: isDark ? 'dark' : 'light' }"
              class="bg-white dark:bg-[#182234] text-slate-900 dark:text-slate-100 border border-slate-300 dark:border-slate-700/80 rounded-xl px-3 py-1.5 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 cursor-pointer shadow-xs transition-colors"
            >
              <option value="all" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100">{{ t('ជំនាញទាំងអស់ (All Majors)', 'All Majors (5 SPI Majors)') }}</option>
              <option v-for="m in majorsList" :key="m.id" :value="m.id" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100">{{ getMajorDisplayName(m.name) }}</option>
            </select>

            <!-- Refresh Button -->
            <button
              @click="applyFilters"
              :disabled="isRefreshing"
              class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50"
            >
              <svg :class="{ 'animate-spin': isRefreshing }" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>{{ t('ផ្ទុកឡើងវិញ', 'Refresh') }}</span>
            </button>

            <!-- Export Report Button -->
            <button
              @click="exportReport"
              class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-emerald-600/20 transition-all cursor-pointer"
            >
              <span>📥 {{ t('ទាញយករបាយការណ៍', 'Export Report') }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ── 2. SUMMARY CARDS (5 Core Questions — Clickable) ── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- 1. Total Students ➔ /admin/user-management/students -->
        <Link
          href="/admin/user-management/students"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm dark:shadow-none hover:border-emerald-500 dark:hover:border-emerald-500/50 hover:shadow-md transition-all group cursor-pointer block"
        >
          <div class="flex items-center justify-between">
            <span class="text-2xl">👨‍🎓</span>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
              {{ t('និស្សិត →', 'User Mgmt →') }}
            </span>
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold mt-2.5">{{ t('និស្សិតសរុប', 'Total Students') }}</p>
          <h4 class="text-2xl font-black text-slate-900 dark:text-white mt-0.5 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
            {{ (stats?.total_students || 2458).toLocaleString() }}
          </h4>
          <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1">
            ✓ {{ (stats?.active_students || 2390).toLocaleString() }} {{ t('និស្សិតសកម្ម', 'Active Students') }}
          </p>
        </Link>

        <!-- 2. Faculty Teachers ➔ /admin/user-management/teachers -->
        <Link
          href="/admin/user-management/teachers"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm dark:shadow-none hover:border-indigo-500 dark:hover:border-indigo-500/50 hover:shadow-md transition-all group cursor-pointer block"
        >
          <div class="flex items-center justify-between">
            <span class="text-2xl">👨‍🏫</span>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20">
              {{ t('គ្រូបង្រៀន →', 'Teachers →') }}
            </span>
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold mt-2.5">{{ t('សាស្ត្រាចារ្យ/គ្រូ', 'Faculty Teachers') }}</p>
          <h4 class="text-2xl font-black text-slate-900 dark:text-white mt-0.5 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
            {{ (stats?.total_teachers || 145).toLocaleString() }}
          </h4>
          <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold mt-1">
            ✓ {{ (stats?.active_teachers || 140).toLocaleString() }} {{ t('គ្រូកំពុងបង្រៀន', 'Teaching Faculty') }}
          </p>
        </Link>

        <!-- 3. Active Courses ➔ /admin/course-module/all -->
        <Link
          href="/admin/course-module/all"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm dark:shadow-none hover:border-purple-500 dark:hover:border-purple-500/50 hover:shadow-md transition-all group cursor-pointer block"
        >
          <div class="flex items-center justify-between">
            <span class="text-2xl">📚</span>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20">
              {{ t('វគ្គសិក្សា →', 'Courses →') }}
            </span>
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold mt-2.5">{{ t('វគ្គសិក្សាសកម្ម', 'Active Courses') }}</p>
          <h4 class="text-2xl font-black text-slate-900 dark:text-white mt-0.5 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
            {{ (stats?.total_courses || 328).toLocaleString() }}
          </h4>
          <p class="text-[11px] text-purple-600 dark:text-purple-400 font-semibold mt-1">
            ✓ {{ (stats?.published_courses || 290).toLocaleString() }} {{ t('បានអនុម័ត & ផ្សាយ', 'Approved & Published') }}
          </p>
        </Link>

        <!-- 4. Completion Rate ➔ /admin/progress -->
        <Link
          href="/admin/progress"
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm dark:shadow-none hover:border-teal-500 dark:hover:border-teal-500/50 hover:shadow-md transition-all group cursor-pointer block"
        >
          <div class="flex items-center justify-between">
            <span class="text-2xl">📈</span>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-400 border border-teal-200 dark:border-teal-500/20">
              {{ t('វឌ្ឍនភាព →', 'Learning →') }}
            </span>
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold mt-2.5">{{ t('អត្រាបញ្ចប់ការសិក្សា', 'Completion Rate') }}</p>
          <h4 class="text-2xl font-black text-teal-600 dark:text-teal-400 mt-0.5 group-hover:text-teal-500 transition-colors">
            {{ stats?.completion_rate || 76 }}%
          </h4>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium mt-1">
            {{ t('៧៦% បញ្ចប់ · ១៨% កំពុងរៀន · ៦% ថ្មី', '76% Done · 18% Progress · 6% New') }}
          </p>
        </Link>

        <!-- 5. At-Risk Students ➔ /admin/progress?tab=at_risk -->
        <Link
          href="/admin/progress?tab=at_risk"
          class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/50 rounded-2xl p-4 shadow-sm dark:shadow-none hover:border-rose-500 hover:shadow-md transition-all group cursor-pointer block"
        >
          <div class="flex items-center justify-between">
            <span class="text-2xl">⚠️</span>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-50 dark:bg-rose-500/15 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30">
              {{ t('ការដាស់តឿន AI →', 'AI Alert →') }}
            </span>
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold mt-2.5">{{ t('និស្សិតប្រឈមហានិភ័យ', 'At-Risk Students') }}</p>
          <h4 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-0.5 group-hover:text-rose-500 transition-colors">
            {{ (stats?.at_risk_students || 12).toLocaleString() }}
          </h4>
          <p class="text-[11px] text-rose-600 dark:text-rose-400 font-semibold mt-1">
            ⚠️ {{ t('ត្រូវការការយកចិត្តទុកដាក់ (AI)', 'Attention Required (AI)') }}
          </p>
        </Link>
      </div>

      <!-- ── 3. MIDDLE SECTION: ENROLLMENT TREND & ACADEMIC PERFORMANCE (2 COLUMNS) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        
        <!-- Left (7 Cols): Enrollment & Completion Trend -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col justify-between">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
              <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wide">
                <span>📈</span> {{ t('និន្នាការចុះឈ្មោះ & បញ្ចប់ការសិក្សា', 'ENROLLMENT & COMPLETION TREND') }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ t('កំណើននៃការចុះឈ្មោះថ្មី ធៀបនឹងការបញ្ចប់វគ្គសិក្សា', 'New Enrollments VS Course Completions') }}
              </p>
            </div>

            <!-- Timeframe switcher: Daily | Weekly | Monthly -->
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700 text-xs shrink-0">
              <button
                v-for="tf in [
                  { id: 'daily', label: t('ប្រចាំថ្ងៃ', 'Daily') },
                  { id: 'weekly', label: t('ប្រចាំសប្តាហ៍', 'Weekly') },
                  { id: 'monthly', label: t('ប្រចាំខែ', 'Monthly') },
                ]"
                :key="tf.id"
                @click="chartTimeframe = (tf.id as any)"
                :class="[
                  chartTimeframe === tf.id 
                    ? 'bg-emerald-600 text-white font-bold shadow-xs' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200',
                  'px-3 py-1 rounded-lg transition-all cursor-pointer'
                ]"
              >
                {{ tf.label }}
              </button>
            </div>
          </div>

          <!-- Area Chart -->
          <div class="h-[280px]">
            <VueApexCharts
              :key="`${isDark ? 'dark' : 'light'}_${chartTimeframe}_${currentLang}`"
              type="area"
              height="100%"
              :options="(enrollmentChartOptions as any)"
              :series="enrollmentSeries"
            />
          </div>
        </div>

        <!-- Right (5 Cols): Academic / Learning Performance (Filter by 5 Majors) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wide">
                  <span>🎯</span> {{ t('លទ្ធផលសិក្សា & វឌ្ឍនភាព', 'ACADEMIC / LEARNING PERFORMANCE') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  {{ t('ស្ថានភាពបញ្ចប់វគ្គសិក្សាតាមជំនាញទាំង ៥', 'Completion Status filtered by 5 Majors') }}
                </p>
              </div>

              <!-- Filter by 5 Majors Dropdown -->
              <select
                v-model="selectedPerfMajor"
                :style="{ colorScheme: isDark ? 'dark' : 'light' }"
                class="bg-white dark:bg-[#182234] text-slate-900 dark:text-slate-100 border border-slate-300 dark:border-slate-700/80 rounded-xl px-2.5 py-1 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 cursor-pointer shadow-xs transition-colors"
              >
                <option value="all" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100">{{ t('ជំនាញទាំង ៥ ទាំងអស់', 'All 5 Majors') }}</option>
                <option value="1" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100">{{ t('បច្ចេកវិទ្យាព័ត៌មាន (IT)', 'Information Technology') }}</option>
                <option value="2" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100">{{ t('ការងារសង្គម (SW)', 'Social Work') }}</option>
                <option value="3" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100">{{ t('កសិកម្ម (AGR)', 'Agriculture') }}</option>
                <option value="4" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100">{{ t('ទេសចរណ៍ (TRM)', 'Tourism') }}</option>
                <option value="5" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100">{{ t('អក្សរសាស្ត្រអង់គ្លេស (ENG)', 'English Literature') }}</option>
              </select>
            </div>

            <!-- Major Scope Indicator -->
            <div class="flex items-center justify-between text-xs py-1 text-slate-600 dark:text-slate-300">
              <span class="font-semibold">{{ getMajorDisplayName(currentPerformance.name) }}</span>
              <span class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                {{ currentPerformance.total_count }} {{ t('និស្សិត', 'Students') }}
              </span>
            </div>

            <!-- Donut Chart -->
            <div class="h-[180px] flex items-center justify-center my-1">
              <VueApexCharts
                :key="`${isDark ? 'dark-donut' : 'light-donut'}_${selectedPerfMajor}_${currentLang}`"
                type="donut"
                height="100%"
                width="100%"
                :options="(completionDonutOptions as any)"
                :series="completionDonutSeries"
              />
            </div>

            <!-- Status Breakdown (Completed, In Progress, Not Started) -->
            <div class="grid grid-cols-3 gap-2.5 text-center text-xs pt-3 border-t border-slate-100 dark:border-slate-800">
              <!-- Completed -->
              <div class="bg-emerald-50 dark:bg-emerald-950/40 p-2.5 rounded-xl border border-emerald-200/80 dark:border-emerald-800/60">
                <span class="text-emerald-700 dark:text-emerald-400 font-black block text-base">{{ currentPerformance.completed }}%</span>
                <span class="text-slate-600 dark:text-slate-400 text-[11px] font-semibold">{{ t('បានបញ្ចប់', 'Completed') }}</span>
              </div>
              <!-- In Progress -->
              <div class="bg-amber-50 dark:bg-amber-950/40 p-2.5 rounded-xl border border-amber-200/80 dark:border-amber-800/60">
                <span class="text-amber-700 dark:text-amber-400 font-black block text-base">{{ currentPerformance.in_progress }}%</span>
                <span class="text-slate-600 dark:text-slate-400 text-[11px] font-semibold">{{ t('កំពុងរៀន', 'In Progress') }}</span>
              </div>
              <!-- Not Started -->
              <div class="bg-slate-100 dark:bg-slate-800/80 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700">
                <span class="text-slate-700 dark:text-slate-300 font-black block text-base">{{ currentPerformance.not_started }}%</span>
                <span class="text-slate-500 dark:text-slate-400 text-[11px] font-semibold">{{ t('មិនទាន់ចាប់ផ្ដើម', 'Not Started') }}</span>
              </div>
            </div>
          </div>

          <!-- Link to Learning & Progress -->
          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-right mt-3">
            <Link href="/admin/progress" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500">
              {{ t('មើលវឌ្ឍនភាពលម្អិតរបស់និស្សិត →', 'View Detailed Student Progress →') }}
            </Link>
          </div>
        </div>

      </div>

      <!-- ── 4. BOTTOM SECTION: ADMIN ALERTS / ACTION REQUIRED ── -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-4">
          <div class="flex items-center gap-2.5">
            <span class="text-xl">⚡</span>
            <div>
              <h3 class="font-bold text-sm text-slate-900 dark:text-white uppercase tracking-wider">
                {{ t('ការដាស់តឿន & សកម្មភាពបន្ទាន់', 'ADMIN ALERTS / ACTION REQUIRED') }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ t('ការជូនដំណឹងសំខាន់ៗដែលទាមទារឱ្យ Admin ពិនិត្យ និងចាត់វិធានការ', 'Important institutional alerts requiring Admin review & immediate action') }}
              </p>
            </div>
          </div>
          <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30">
            {{ t('៤ សកម្មភាពកំពុងរង់ចាំ', '4 Actions Pending') }}
          </span>
        </div>

        <!-- 4 Action Alert Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- 1. 12 At-Risk Students -->
          <div class="p-4 rounded-2xl bg-rose-50/60 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/40 flex flex-col justify-between space-y-3">
            <div>
              <div class="flex items-center justify-between">
                <span class="text-lg">⚠</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300">
                  {{ t('អាទិភាពខ្ពស់', 'High Priority') }}
                </span>
              </div>
              <h4 class="font-bold text-sm text-slate-900 dark:text-white mt-2">{{ t('១២ និស្សិតប្រឈមហានិភ័យ', '12 At-Risk Students') }}</h4>
              <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1">
                {{ t('AI បានរកឃើញវឌ្ឍនភាពសិក្សាទាប (< ៣០%) និងខកខានកាលបរិច្ឆេទប្រឡង។', 'Low course completion (< 30%) and missed deadlines detected by AI.') }}
              </p>
            </div>
            <Link
              href="/admin/progress?tab=at_risk"
              class="w-full py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs text-center shadow-xs transition-all block cursor-pointer"
            >
              {{ t('ពិនិត្យនិស្សិត →', 'View Students →') }}
            </Link>
          </div>

          <!-- 2. 5 Courses Waiting for Approval -->
          <div class="p-4 rounded-2xl bg-purple-50/60 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-900/40 flex flex-col justify-between space-y-3">
            <div>
              <div class="flex items-center justify-between">
                <span class="text-lg">📚</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300">
                  {{ t('ត្រូវការការត្រួតពិនិត្យ', 'Review Needed') }}
                </span>
              </div>
              <h4 class="font-bold text-sm text-slate-900 dark:text-white mt-2">{{ t('៥ វគ្គសិក្សាកំពុងរង់ចាំការអនុម័ត', '5 Courses Waiting Approval') }}</h4>
              <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1">
                {{ t('វគ្គសិក្សាដែលគ្រូបានបង្កើតបញ្ជូនមកពិនិត្យមាតិកា និងអនុម័តផ្សាយ។', 'Teacher-created courses submitted for syllabus & publication approval.') }}
              </p>
            </div>
            <Link
              href="/admin/course-module/all?status=draft"
              class="w-full py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl text-xs text-center shadow-xs transition-all block cursor-pointer"
            >
              {{ t('ពិនិត្យវគ្គសិក្សា →', 'Review Courses →') }}
            </Link>
          </div>

          <!-- 3. 3 Teacher Accounts Pending -->
          <div class="p-4 rounded-2xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 flex flex-col justify-between space-y-3">
            <div>
              <div class="flex items-center justify-between">
                <span class="text-lg">👨‍🏫</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300">
                  {{ t('គណនីគ្រូ', 'Faculty Account') }}
                </span>
              </div>
              <h4 class="font-bold text-sm text-slate-900 dark:text-white mt-2">{{ t('៣ គណនីគ្រូបង្រៀនកំពុងរង់ចាំ', '3 Teacher Accounts Pending') }}</h4>
              <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1">
                {{ t('គណនីសាស្ត្រាចារ្យកំពុងរង់ចាំការកំណត់ដេប៉ាតឺម៉ង់ និងមុខវិជ្ជាបង្រៀន។', 'Faculty teaching accounts awaiting department role & course assignment.') }}
              </p>
            </div>
            <Link
              href="/admin/user-management/teachers"
              class="w-full py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-xl text-xs text-center shadow-xs transition-all block cursor-pointer"
            >
              {{ t('ពិនិត្យគ្រូបង្រៀន →', 'Review Teachers →') }}
            </Link>
          </div>

          <!-- 4. 2 New System Notifications -->
          <div class="p-4 rounded-2xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/40 flex flex-col justify-between space-y-3">
            <div>
              <div class="flex items-center justify-between">
                <span class="text-lg">📢</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300">
                  {{ t('សេចក្តីប្រកាស', 'Broadcast') }}
                </span>
              </div>
              <h4 class="font-bold text-sm text-slate-900 dark:text-white mt-2">{{ t('២ ការជូនដំណឹងប្រព័ន្ធថ្មី', '2 New Notifications') }}</h4>
              <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1">
                {{ t('សេចក្តីប្រកាសប្រតិទិនសិក្សាត្រៀមរួចរាល់សម្រាប់ផ្សាយទូទាំងសាលា។', 'Academic semester calendar announcement ready for campus-wide release.') }}
              </p>
            </div>
            <Link
              href="/admin/notifications/announcements"
              class="w-full py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl text-xs text-center shadow-xs transition-all block cursor-pointer"
            >
              {{ t('ពិនិត្យការជូនដំណឹង →', 'View Notifications →') }}
            </Link>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>
