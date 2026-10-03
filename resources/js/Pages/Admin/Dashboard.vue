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

// Top active courses for bottom snapshot table (matching Image style)
const topCoursesList = [
  { id: 1, name: 'Web Development & Cloud', major: 'IT', students: 185, completion: 88, status: 'Active' },
  { id: 2, name: 'Community Social Work', major: 'SW', students: 142, completion: 76, status: 'Active' },
  { id: 3, name: 'Modern Agronomy & Crop Tech', major: 'AGR', students: 136, completion: 82, status: 'Active' },
  { id: 4, name: 'Eco-Tourism & Hospitality', major: 'TRM', students: 118, completion: 70, status: 'Active' },
  { id: 5, name: 'Business English Communication', major: 'ENG', students: 124, completion: 91, status: 'Active' },
]

// System Infrastructure & Health summary (matching Image style)
const systemInfrastructure = [
  { name: 'API Gateway & Server', value: 'Online (99.9%)', icon: '🖥️', status: 'Healthy', color: 'emerald' },
  { name: 'Primary Database', value: 'Operational', icon: '🗄️', status: 'Healthy', color: 'emerald' },
  { name: 'AI Prediction Engine', value: 'Evaluating Rules', icon: '🤖', status: 'Running', color: 'indigo' },
  { name: 'Cloudinary CDN Storage', value: '128 GB / 500 GB', icon: '☁️', status: '25.6%', color: 'blue' },
  { name: 'Automated Database Backup', value: 'Today, 03:00 AM', icon: '💾', status: 'Success', color: 'teal' },
]

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

// Localized categories for Chart Timeframe
const chartCategories = computed(() => {
  const tf = chartTimeframe.value
  if (tf === 'daily') {
    return currentLang.value === 'km' 
      ? ['ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហ', 'សុក្រ', 'សៅរ៍', 'អាទិត្យ']
      : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  }
  if (tf === 'weekly') {
    return currentLang.value === 'km'
      ? ['សប្តាហ៍ទី ១', 'សប្តាហ៍ទី ២', 'សប្តាហ៍ទី ៣', 'សប្តាហ៍ទី ៤']
      : ['Week 1', 'Week 2', 'Week 3', 'Week 4']
  }
  // monthly
  return currentLang.value === 'km'
    ? ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ']
    : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
})

// Enrollment Chart Series
const activeChartData = computed(() => {
  const tf = chartTimeframe.value
  const data = props.enrollmentChartData?.[tf] || props.enrollmentChartData?.monthly
  return {
    categories: chartCategories.value,
    enrollments: data?.enrollments || [140, 220, 310, 450, 520, 680, 720, 610, 590, 810, 940, 1120],
    completions: data?.completions || [90, 150, 210, 310, 390, 510, 540, 480, 460, 640, 720, 890],
  }
})

const enrollmentSeries = computed(() => [
  { name: t('ការចុះឈ្មោះថ្មី', 'New Enrollments'), data: activeChartData.value.enrollments },
  { name: t('ការបញ្ចប់វគ្គសិក្សា', 'Course Completions'), data: activeChartData.value.completions },
])

const enrollmentChartOptions = computed<any>(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    background: 'transparent',
    parentHeightOffset: 0,
  },
  colors: ['#6366f1', '#10b981'],
  stroke: { curve: 'smooth', width: 3 },
  dataLabels: { enabled: false },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
  xaxis: {
    type: 'category',
    categories: chartCategories.value,
    labels: {
      show: true,
      rotate: -25,
      rotateAlways: false,
      style: {
        colors: isDark.value ? '#cbd5e1' : '#475569',
        fontSize: '11px',
        fontWeight: 600,
      },
    },
    axisBorder: { show: true, color: isDark.value ? '#334155' : '#cbd5e1' },
    axisTicks: { show: true, color: isDark.value ? '#334155' : '#cbd5e1' },
  },
  yaxis: {
    labels: {
      style: {
        colors: isDark.value ? '#cbd5e1' : '#475569',
        fontSize: '11px',
        fontWeight: 600,
      },
    },
  },
  grid: { borderColor: isDark.value ? '#334155' : '#e2e8f0', strokeDashArray: 4 },
  legend: {
    labels: { colors: isDark.value ? '#cbd5e1' : '#334155' },
    position: 'top',
    horizontalAlign: 'right',
    fontSize: '12px',
    fontWeight: 600,
  },
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
              <h1 class="text-xl sm:text-2xl font-black bg-gradient-to-r from-emerald-700 via-teal-700 to-slate-900 dark:from-emerald-400 dark:via-teal-300 dark:to-cyan-300 bg-clip-text text-transparent tracking-tight">
                {{ t('ផ្ទាំងគ្រប់គ្រង ADMIN', 'ADMIN DASHBOARD') }}
              </h1>
              <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                Official ELMS
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 font-medium">
              <span>{{ t('សូមស្វាគមន៍,', 'Welcome,') }} <strong class="text-emerald-700 dark:text-emerald-300 font-bold">{{ userName }}</strong> 👋</span>
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
                    ? 'bg-emerald-600 dark:bg-emerald-500 text-white dark:text-slate-950 font-bold shadow-xs' 
                    : 'text-slate-600 dark:text-slate-300 hover:text-emerald-700 dark:hover:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-slate-700/60',
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
              class="bg-white dark:bg-[#182234] text-emerald-800 dark:text-emerald-300 border border-emerald-300/80 dark:border-emerald-500/40 rounded-xl px-3 py-1.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 cursor-pointer shadow-xs transition-colors"
            >
              <option value="all" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100 font-medium">{{ t('ជំនាញទាំងអស់ (All Majors)', 'All Majors (5 SPI Majors)') }}</option>
              <option v-for="m in majorsList" :key="m.id" :value="m.id" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100 font-medium">{{ getMajorDisplayName(m.name) }}</option>
            </select>

            <!-- Refresh Button -->
            <button
              @click="applyFilters"
              :disabled="isRefreshing"
              class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 text-slate-700 dark:text-slate-200 hover:text-emerald-700 dark:hover:text-emerald-300 border border-slate-200 dark:border-slate-700 hover:border-emerald-300 dark:hover:border-emerald-500/40 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50"
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

      <!-- ── 2. TOP METRIC CARDS (Vibrant Sparkline Cards with Glass Icons matching Image) ── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- 1. Total Students ➔ Royal Indigo -->
        <Link
          href="/admin/user-management/students"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#6366f1] via-[#4f46e5] to-[#4338ca] text-white p-4.5 shadow-md shadow-indigo-600/20 hover:shadow-xl hover:shadow-indigo-600/30 hover:-translate-y-1 transition-all duration-200 group cursor-pointer block flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wider uppercase text-white/85">{{ t('និស្សិតសរុប', 'TOTAL STUDENTS') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-xs shrink-0 group-hover:scale-110 transition-transform">
                👨‍🎓
              </div>
            </div>
            <h4 class="text-3xl font-black tracking-tight text-white mt-1 group-hover:scale-105 origin-left transition-transform">
              {{ (stats?.total_students || 2458).toLocaleString() }}
            </h4>
            <p class="text-xs text-white/90 font-medium mt-1 flex items-center gap-1">
              <span>▲ 2,390</span>
              <span class="opacity-80">{{ t('និស្សិតសកម្ម', 'Active Students') }}</span>
            </p>
          </div>

          <!-- Bottom Wave Sparkline -->
          <div class="mt-3 -mx-4.5 -mb-4.5 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>

        <!-- 2. Faculty Teachers ➔ Emerald Green -->
        <Link
          href="/admin/user-management/teachers"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#10b981] via-[#059669] to-[#047857] text-white p-4.5 shadow-md shadow-emerald-600/20 hover:shadow-xl hover:shadow-emerald-600/30 hover:-translate-y-1 transition-all duration-200 group cursor-pointer block flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wider uppercase text-white/85">{{ t('សាស្ត្រាចារ្យ/គ្រូ', 'FACULTY TEACHERS') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-xs shrink-0 group-hover:scale-110 transition-transform">
                👨‍🏫
              </div>
            </div>
            <h4 class="text-3xl font-black tracking-tight text-white mt-1 group-hover:scale-105 origin-left transition-transform">
              {{ (stats?.total_teachers || 145).toLocaleString() }}
            </h4>
            <p class="text-xs text-white/90 font-medium mt-1 flex items-center gap-1">
              <span>▲ 140</span>
              <span class="opacity-80">{{ t('គ្រូកំពុងបង្រៀន', 'Teaching Faculty') }}</span>
            </p>
          </div>

          <!-- Bottom Wave Sparkline -->
          <div class="mt-3 -mx-4.5 -mb-4.5 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>

        <!-- 3. Active Courses ➔ Coral Orange -->
        <Link
          href="/admin/course-module/all"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#f59e0b] via-[#ea580c] to-[#d97706] text-white p-4.5 shadow-md shadow-orange-500/20 hover:shadow-xl hover:shadow-orange-500/30 hover:-translate-y-1 transition-all duration-200 group cursor-pointer block flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wider uppercase text-white/85">{{ t('វគ្គសិក្សាសកម្ម', 'ACTIVE COURSES') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-xs shrink-0 group-hover:scale-110 transition-transform">
                📚
              </div>
            </div>
            <h4 class="text-3xl font-black tracking-tight text-white mt-1 group-hover:scale-105 origin-left transition-transform">
              {{ (stats?.total_courses || 328).toLocaleString() }}
            </h4>
            <p class="text-xs text-white/90 font-medium mt-1 flex items-center gap-1">
              <span>▲ 290</span>
              <span class="opacity-80">{{ t('បានអនុម័ត & ផ្សាយ', 'Approved & Published') }}</span>
            </p>
          </div>

          <!-- Bottom Wave Sparkline -->
          <div class="mt-3 -mx-4.5 -mb-4.5 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>

        <!-- 4. Completion Rate ➔ Royal Blue -->
        <Link
          href="/admin/progress"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0284c7] via-[#2563eb] to-[#1d4ed8] text-white p-4.5 shadow-md shadow-blue-600/20 hover:shadow-xl hover:shadow-blue-600/30 hover:-translate-y-1 transition-all duration-200 group cursor-pointer block flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wider uppercase text-white/85">{{ t('អត្រាបញ្ចប់ការសិក្សា', 'COMPLETION RATE') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-xs shrink-0 group-hover:scale-110 transition-transform">
                📈
              </div>
            </div>
            <h4 class="text-3xl font-black tracking-tight text-white mt-1 group-hover:scale-105 origin-left transition-transform">
              {{ stats?.completion_rate || 76 }}%
            </h4>
            <p class="text-xs text-white/90 font-medium mt-1 flex items-center gap-1">
              <span>▲ +4.2%</span>
              <span class="opacity-80">{{ t('ធៀបនឹងខែមុន', 'vs Last Month') }}</span>
            </p>
          </div>

          <!-- Bottom Wave Sparkline -->
          <div class="mt-3 -mx-4.5 -mb-4.5 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>

        <!-- 5. At-Risk Students ➔ Magenta Crimson / Rose -->
        <Link
          href="/admin/progress?tab=at_risk"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#ec4899] via-[#e11d48] to-[#be123c] text-white p-4.5 shadow-md shadow-rose-600/20 hover:shadow-xl hover:shadow-rose-600/30 hover:-translate-y-1 transition-all duration-200 group cursor-pointer block flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wider uppercase text-white/95 flex items-center gap-1.5">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-85"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                </span>
                {{ t('និស្សិតប្រឈមហានិភ័យ', 'AT-RISK STUDENTS') }}
              </span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-xs shrink-0 group-hover:scale-110 transition-transform">
                ⚠️
              </div>
            </div>
            <h4 class="text-3xl font-black tracking-tight text-white mt-1 group-hover:scale-105 origin-left transition-transform">
              {{ (stats?.at_risk_students || 12).toLocaleString() }}
            </h4>
            <p class="text-xs text-white font-medium mt-1 flex items-center gap-1 bg-black/15 px-2 py-0.5 rounded-md w-fit">
              <span>●</span>
              <span>{{ t('ត្រូវការការយកចិត្តទុកដាក់ (AI)', 'Attention Required (AI)') }}</span>
            </p>
          </div>

          <!-- Bottom Wave Sparkline -->
          <div class="mt-3 -mx-4.5 -mb-4.5 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 14 Q 25 20, 50 8 T 80 16 T 100 6 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 14 Q 25 20, 50 8 T 80 16 T 100 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>
      </div>

      <!-- ── 3. MIDDLE ANALYTICS (3-Column Layout Matching Image Row 2) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (6 / 12): Enrollment & Completion Trend -->
        <div class="lg:col-span-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col justify-between h-full">
          <div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wide">
                  <span>📈</span> {{ t('និន្នាការចុះឈ្មោះ & បញ្ចប់ការសិក្សា', 'Enrollment & Completion Trend') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-300 mt-0.5">
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
                      ? 'bg-emerald-600 dark:bg-emerald-500 text-white dark:text-slate-950 font-bold shadow-xs' 
                      : 'text-slate-600 dark:text-slate-300 hover:text-emerald-700 dark:hover:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-slate-700/60',
                    'px-3 py-1 rounded-lg transition-all cursor-pointer'
                  ]"
                >
                  {{ tf.label }}
                </button>
              </div>
            </div>

            <!-- Area Chart -->
            <div class="h-[300px] w-full pt-1">
              <VueApexCharts
                :key="`${isDark ? 'dark' : 'light'}_${chartTimeframe}_${currentLang}`"
                type="area"
                height="100%"
                :options="(enrollmentChartOptions as any)"
                :series="enrollmentSeries"
              />
            </div>
          </div>
        </div>

        <!-- Col 2 (3 / 12): Academic Performance Donut (Matching 'Sales by Category') -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col justify-between h-full">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5 uppercase tracking-wide">
                <span>🎯</span> {{ t('លទ្ធផលសិក្សា', 'Academic Performance') }}
              </h3>
              <select
                v-model="selectedPerfMajor"
                :style="{ colorScheme: isDark ? 'dark' : 'light' }"
                class="bg-white dark:bg-[#182234] text-emerald-800 dark:text-emerald-300 border border-emerald-300/80 dark:border-emerald-500/40 rounded-xl px-2 py-0.5 text-[11px] font-bold focus:outline-none cursor-pointer shadow-xs"
              >
                <option value="all">{{ t('៥ ជំនាញ', 'All 5 Majors') }}</option>
                <option value="1">IT</option>
                <option value="2">Social Work</option>
                <option value="3">Agriculture</option>
                <option value="4">Tourism</option>
                <option value="5">English</option>
              </select>
            </div>

            <!-- Donut Chart -->
            <div class="h-[175px] flex items-center justify-center my-1">
              <VueApexCharts
                :key="`${isDark ? 'dark-donut' : 'light-donut'}_${selectedPerfMajor}_${currentLang}`"
                type="donut"
                height="100%"
                width="100%"
                :options="(completionDonutOptions as any)"
                :series="completionDonutSeries"
              />
            </div>

            <!-- Vertical Legend (Matching Image Style) -->
            <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
              <div class="flex items-center justify-between py-0.5">
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>
                  <span class="font-medium text-slate-700 dark:text-slate-200">{{ t('បានបញ្ចប់', 'Completed') }}</span>
                </div>
                <span class="font-black text-emerald-600 dark:text-emerald-400">{{ currentPerformance.completed }}%</span>
              </div>
              <div class="flex items-center justify-between py-0.5">
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                  <span class="font-medium text-slate-700 dark:text-slate-200">{{ t('កំពុងរៀន', 'In Progress') }}</span>
                </div>
                <span class="font-black text-amber-600 dark:text-amber-400">{{ currentPerformance.in_progress }}%</span>
              </div>
              <div class="flex items-center justify-between py-0.5">
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full bg-slate-400 dark:bg-slate-500 shrink-0"></span>
                  <span class="font-medium text-slate-700 dark:text-slate-200">{{ t('មិនទាន់ចាប់ផ្ដើម', 'Not Started') }}</span>
                </div>
                <span class="font-black text-slate-600 dark:text-slate-300">{{ currentPerformance.not_started }}%</span>
              </div>
            </div>
          </div>

          <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 text-right mt-2">
            <Link href="/admin/progress" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500">
              {{ t('មើលលម្អិត →', 'View Details →') }}
            </Link>
          </div>
        </div>

        <!-- Col 3 (3 / 12): 5 SPI Majors Summary (Matching 'Collection Summary') -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col justify-between h-full">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5 uppercase tracking-wide">
                <span>🏛️</span> {{ t('ជំនាញទាំង ៥ SPI', '5 SPI Majors') }}
              </h3>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30">
                2,458 {{ t('សរុប', 'Total') }}
              </span>
            </div>

            <!-- List of 5 Majors -->
            <div class="space-y-2.5">
              <div
                v-for="(m, idx) in [
                  { name: 'Information Tech', name_kh: 'បច្ចេកវិទ្យាព័ត៌មាន', count: 520, pct: 21, icon: '💻', color: 'bg-indigo-500' },
                  { name: 'Social Work', name_kh: 'ការងារសង្គម', count: 548, pct: 23, icon: '🤝', color: 'bg-emerald-500' },
                  { name: 'Agriculture', name_kh: 'កសិកម្ម', count: 600, pct: 24, icon: '🌾', color: 'bg-amber-500' },
                  { name: 'Tourism & Hosp.', name_kh: 'ទេសចរណ៍', count: 410, pct: 17, icon: '✈️', color: 'bg-blue-500' },
                  { name: 'English Literature', name_kh: 'អក្សរសាស្ត្រអង់គ្លេស', count: 380, pct: 15, icon: '📖', color: 'bg-purple-500' },
                ]"
                :key="idx"
                class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800"
              >
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg text-white flex items-center justify-center text-xs shrink-0 shadow-xs" :class="m.color">
                    {{ m.icon }}
                  </div>
                  <div>
                    <h5 class="text-xs font-semibold text-slate-800 dark:text-slate-100 leading-tight">
                      {{ currentLang === 'km' ? m.name_kh : m.name }}
                    </h5>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400">{{ m.count }} {{ t('នាក់', 'Students') }}</span>
                  </div>
                </div>
                <span class="text-xs font-black text-slate-900 dark:text-white">{{ m.pct }}%</span>
              </div>
            </div>
          </div>

          <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs mt-2">
            <span class="text-slate-500 dark:text-slate-400">{{ t('សិស្សសកម្មសរុប', 'Active Total') }}:</span>
            <span class="font-bold text-emerald-600 dark:text-emerald-400">2,390 (97.2%)</span>
          </div>
        </div>

      </div>

      <!-- ── 4. BOTTOM ACTION & SNAPSHOT GRID (3-Column Layout Matching Image Row 3) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (5 / 12): Top Active Courses Table (Matching 'Top 5 Best Selling Items') -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col justify-between h-full">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wide">
                <span>🏆</span> {{ t('វគ្គសិក្សាល្អបំផុតទាំង ៥', 'Top 5 Active Courses') }}
              </h3>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                Official
              </span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800">
                    <th class="py-2 font-bold">#</th>
                    <th class="py-2 font-bold">{{ t('ឈ្មោះវគ្គសិក្សា', 'Course Name') }}</th>
                    <th class="py-2 font-bold text-center">{{ t('ជំនាញ', 'Major') }}</th>
                    <th class="py-2 font-bold text-center">{{ t('និស្សិត', 'Enrolled') }}</th>
                    <th class="py-2 font-bold text-right">{{ t('បញ្ចប់', 'Rate') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr v-for="c in topCoursesList" :key="c.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="py-2.5 text-slate-400 font-bold">{{ c.id }}</td>
                    <td class="py-2.5 font-semibold text-slate-800 dark:text-slate-100">{{ c.name }}</td>
                    <td class="py-2.5 text-center">
                      <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                        {{ c.major }}
                      </span>
                    </td>
                    <td class="py-2.5 text-center font-bold text-slate-900 dark:text-white">{{ c.students }}</td>
                    <td class="py-2.5 text-right font-black text-emerald-600 dark:text-emerald-400">{{ c.completion }}%</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-center mt-2">
            <Link href="/admin/course-module/all" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
              {{ t('មើលវគ្គសិក្សាទាំងអស់ →', 'View All Courses →') }}
            </Link>
          </div>
        </div>

        <!-- Col 2 (3.5 / 12): System Infrastructure & Health (Matching 'Stock Summary') -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col justify-between h-full">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5 uppercase tracking-wide">
                <span>⚡</span> {{ t('ប្រព័ន្ធ & ហេដ្ឋារចនាសម្ព័ន្ធ', 'System Health & Summary') }}
              </h3>
              <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Online
              </span>
            </div>

            <!-- List of System Health Metrics -->
            <div class="space-y-3">
              <div v-for="(item, idx) in systemInfrastructure" :key="idx" class="flex items-center justify-between text-xs py-1">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-sm shadow-2xs">
                    {{ item.icon }}
                  </div>
                  <div>
                    <h5 class="font-semibold text-slate-800 dark:text-slate-200">{{ item.name }}</h5>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ item.value }}</p>
                  </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                  {{ item.status }}
                </span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-center mt-2">
            <Link href="/admin/system-logs" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
              {{ t('មើលរបាយការណ៍ប្រព័ន្ធ →', 'View System Report →') }}
            </Link>
          </div>
        </div>

        <!-- Col 3 (3 / 12): AI Alerts & Critical Intervention (Matching 'Low Stock Alert') -->
        <div class="lg:col-span-3 bg-rose-50/40 dark:bg-rose-950/20 border border-rose-200/80 dark:border-rose-900/50 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col justify-between h-full">
          <div>
            <div class="flex items-center justify-between border-b border-rose-200/60 dark:border-rose-900/40 pb-3 mb-3">
              <div class="flex items-center gap-2">
                <span class="text-rose-600 dark:text-rose-400">🔔</span>
                <h3 class="font-bold text-sm text-rose-900 dark:text-rose-200 uppercase tracking-wide">
                  {{ t('ការដាស់តឿន AI & បន្ទាន់', 'AI & Urgent Alerts') }}
                </h3>
              </div>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white">
                4 {{ t('សកម្មភាព', 'Items') }}
              </span>
            </div>

            <div class="space-y-2.5">
              <div
                v-for="alert in [
                  { title: t('១២ និស្សិតប្រឈមហានិភ័យ', '12 At-Risk Students'), status: 'Critical', color: 'bg-rose-600 text-white', url: '/admin/progress?tab=at_risk' },
                  { title: t('៥ វគ្គសិក្សារង់ចាំការអនុម័ត', '5 Courses Awaiting Approval'), status: 'Pending', color: 'bg-amber-500 text-white', url: '/admin/course-module/all?status=draft' },
                  { title: t('៣ គណនីគ្រូរង់ចាំការកំណត់', '3 Faculty Accounts Pending'), status: 'Review', color: 'bg-purple-600 text-white', url: '/admin/user-management/teachers' },
                  { title: t('២ ការជូនដំណឹងប្រព័ន្ធថ្មី', '2 System Announcements'), status: 'Active', color: 'bg-blue-600 text-white', url: '/admin/notifications/announcements' },
                ]"
                :key="alert.title"
                class="flex items-center justify-between p-2.5 rounded-xl bg-white/80 dark:bg-slate-900/80 border border-rose-100 dark:border-rose-900/30 text-xs shadow-2xs"
              >
                <div class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100 truncate pr-2">
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                  <span class="truncate">{{ alert.title }}</span>
                </div>
                <Link
                  :href="alert.url"
                  class="px-2 py-0.5 rounded-md text-[10px] font-bold shrink-0 transition-opacity hover:opacity-80"
                  :class="alert.color"
                >
                  {{ alert.status }} →
                </Link>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-rose-200/60 dark:border-rose-900/40 text-center mt-2">
            <Link href="/admin/progress?tab=at_risk" class="text-xs font-bold text-rose-700 dark:text-rose-400 hover:text-rose-600">
              {{ t('ដោះស្រាយសកម្មភាពទាំងអស់ →', 'View All Action Items →') }}
            </Link>
          </div>
        </div>

      </div>

    </div>
  </AdminLayout>
</template>
