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

      <!-- ── 2. SUMMARY CARDS (5 Core Questions — Clickable) ── -->
      <!-- ── 2. SUMMARY CARDS (Vibrant Block Style matching Image 2) ── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- 1. Total Students ➔ Royal Blue / Indigo -->
        <Link
          href="/admin/user-management/students"
          class="relative overflow-hidden rounded-2xl p-4.5 bg-gradient-to-br from-blue-600 via-indigo-600 to-indigo-700 text-white shadow-md shadow-indigo-600/20 hover:shadow-xl hover:shadow-indigo-600/30 hover:-translate-y-1 transition-all duration-200 group cursor-pointer block"
        >
          <!-- Watermark Background Icon -->
          <div class="absolute -right-2 -bottom-2 text-white/15 pointer-events-none group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
          </div>

          <div class="relative z-10">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wider uppercase text-white/85">{{ t('និស្សិតសរុប', 'TOTAL STUDENTS') }}</span>
              <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-white/20 hover:bg-white/35 active:scale-95 text-white backdrop-blur-xs flex items-center gap-1 transition-all shadow-xs">
                {{ t('និស្សិត →', 'User Mgmt →') }}
              </span>
            </div>
            <h4 class="text-3xl font-black tracking-tight text-white mt-2 group-hover:scale-105 origin-left transition-transform">
              {{ (stats?.total_students || 2458).toLocaleString() }}
            </h4>
            <p class="text-xs text-white/90 font-medium mt-1.5 flex items-center gap-1">
              <span>✓</span>
              <span>{{ (stats?.active_students || 2390).toLocaleString() }} {{ t('និស្សិតសកម្ម', 'Active Students') }}</span>
            </p>
          </div>
        </Link>

        <!-- 2. Faculty Teachers ➔ Coral / Orange / Amber -->
        <Link
          href="/admin/user-management/teachers"
          class="relative overflow-hidden rounded-2xl p-4.5 bg-gradient-to-br from-amber-500 via-orange-500 to-orange-600 text-white shadow-md shadow-orange-500/20 hover:shadow-xl hover:shadow-orange-500/30 hover:-translate-y-1 transition-all duration-200 group cursor-pointer block"
        >
          <!-- Watermark Background Icon -->
          <div class="absolute -right-2 -bottom-2 text-white/15 pointer-events-none group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
              <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
            </svg>
          </div>

          <div class="relative z-10">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wider uppercase text-white/85">{{ t('សាស្ត្រាចារ្យ/គ្រូ', 'FACULTY TEACHERS') }}</span>
              <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-white/20 hover:bg-white/35 active:scale-95 text-white backdrop-blur-xs flex items-center gap-1 transition-all shadow-xs">
                {{ t('គ្រូបង្រៀន →', 'Teachers →') }}
              </span>
            </div>
            <h4 class="text-3xl font-black tracking-tight text-white mt-2 group-hover:scale-105 origin-left transition-transform">
              {{ (stats?.total_teachers || 145).toLocaleString() }}
            </h4>
            <p class="text-xs text-white/90 font-medium mt-1.5 flex items-center gap-1">
              <span>✓</span>
              <span>{{ (stats?.active_teachers || 140).toLocaleString() }} {{ t('គ្រូកំពុងបង្រៀន', 'Teaching Faculty') }}</span>
            </p>
          </div>
        </Link>

        <!-- 3. Active Courses ➔ Emerald / Sea Green / Teal -->
        <Link
          href="/admin/course-module/all"
          class="relative overflow-hidden rounded-2xl p-4.5 bg-gradient-to-br from-emerald-500 via-teal-600 to-teal-700 text-white shadow-md shadow-teal-500/20 hover:shadow-xl hover:shadow-teal-500/30 hover:-translate-y-1 transition-all duration-200 group cursor-pointer block"
        >
          <!-- Watermark Background Icon -->
          <div class="absolute -right-2 -bottom-2 text-white/15 pointer-events-none group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
              <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/>
            </svg>
          </div>

          <div class="relative z-10">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wider uppercase text-white/85">{{ t('វគ្គសិក្សាសកម្ម', 'ACTIVE COURSES') }}</span>
              <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-white/20 hover:bg-white/35 active:scale-95 text-white backdrop-blur-xs flex items-center gap-1 transition-all shadow-xs">
                {{ t('វគ្គសិក្សា →', 'Courses →') }}
              </span>
            </div>
            <h4 class="text-3xl font-black tracking-tight text-white mt-2 group-hover:scale-105 origin-left transition-transform">
              {{ (stats?.total_courses || 328).toLocaleString() }}
            </h4>
            <p class="text-xs text-white/90 font-medium mt-1.5 flex items-center gap-1">
              <span>✓</span>
              <span>{{ (stats?.published_courses || 290).toLocaleString() }} {{ t('បានអនុម័ត & ផ្សាយ', 'Approved & Published') }}</span>
            </p>
          </div>
        </Link>

        <!-- 4. Completion Rate ➔ Electric Sky Blue / Cyan -->
        <Link
          href="/admin/progress"
          class="relative overflow-hidden rounded-2xl p-4.5 bg-gradient-to-br from-sky-500 via-cyan-600 to-blue-600 text-white shadow-md shadow-sky-500/20 hover:shadow-xl hover:shadow-sky-500/30 hover:-translate-y-1 transition-all duration-200 group cursor-pointer block"
        >
          <!-- Watermark Background Icon -->
          <div class="absolute -right-2 -bottom-2 text-white/15 pointer-events-none group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
              <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>
            </svg>
          </div>

          <div class="relative z-10">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wider uppercase text-white/85">{{ t('អត្រាបញ្ចប់ការសិក្សា', 'COMPLETION RATE') }}</span>
              <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-white/20 hover:bg-white/35 active:scale-95 text-white backdrop-blur-xs flex items-center gap-1 transition-all shadow-xs">
                {{ t('វឌ្ឍនភាព →', 'Learning →') }}
              </span>
            </div>
            <h4 class="text-3xl font-black tracking-tight text-white mt-2 group-hover:scale-105 origin-left transition-transform">
              {{ stats?.completion_rate || 76 }}%
            </h4>
            <p class="text-xs text-white/90 font-medium mt-1.5 flex items-center gap-1">
              <span>📊</span>
              <span>{{ t('៧៦% បញ្ចប់ · ១៨% កំពុងរៀន', '76% Done · 18% Progress') }}</span>
            </p>
          </div>
        </Link>

        <!-- 5. At-Risk Students ➔ Ruby Crimson / Red -->
        <Link
          href="/admin/progress?tab=at_risk"
          class="relative overflow-hidden rounded-2xl p-4.5 bg-gradient-to-br from-rose-500 via-red-600 to-rose-700 text-white shadow-md shadow-rose-600/20 hover:shadow-xl hover:shadow-rose-600/30 hover:-translate-y-1 transition-all duration-200 group cursor-pointer block"
        >
          <!-- Watermark Background Icon -->
          <div class="absolute -right-2 -bottom-2 text-white/15 pointer-events-none group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
              <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
            </svg>
          </div>

          <div class="relative z-10">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wider uppercase text-white/95 flex items-center gap-1.5">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-85"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                </span>
                {{ t('និស្សិតប្រឈមហានិភ័យ', 'AT-RISK STUDENTS') }}
              </span>
              <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-white/20 hover:bg-white/35 active:scale-95 text-white backdrop-blur-xs flex items-center gap-1 transition-all shadow-xs">
                {{ t('ការដាស់តឿន AI →', 'AI Alert →') }}
              </span>
            </div>
            <h4 class="text-3xl font-black tracking-tight text-white mt-2 group-hover:scale-105 origin-left transition-transform">
              {{ (stats?.at_risk_students || 12).toLocaleString() }}
            </h4>
            <p class="text-xs text-white font-medium mt-1.5 flex items-center gap-1 bg-black/15 px-2 py-0.5 rounded-md w-fit">
              <span>⚠️</span>
              <span>{{ t('ត្រូវការការយកចិត្តទុកដាក់ (AI)', 'Attention Required (AI)') }}</span>
            </p>
          </div>
        </Link>
      </div>

      <!-- ── 3. MIDDLE SECTION: ENROLLMENT TREND & ACADEMIC PERFORMANCE (2 EQUAL-HEIGHT COLUMNS) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Left (7 Cols): Enrollment & Completion Trend -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col justify-between h-full">
          <div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wide">
                  <span>📈</span> {{ t('និន្នាការចុះឈ្មោះ & បញ្ចប់ការសិក្សា', 'ENROLLMENT & COMPLETION TREND') }}
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
            <div class="h-[310px] w-full pt-1">
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

        <!-- Right (5 Cols): Academic / Learning Performance (Filter by 5 Majors) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col justify-between h-full">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2 uppercase tracking-wide">
                  <span>🎯</span> {{ t('លទ្ធផលសិក្សា & វឌ្ឍនភាព', 'ACADEMIC / LEARNING PERFORMANCE') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-300 mt-0.5">
                  {{ t('ស្ថានភាពបញ្ចប់វគ្គសិក្សាតាមជំនាញទាំង ៥', 'Completion Status filtered by 5 Majors') }}
                </p>
              </div>

              <!-- Filter by 5 Majors Dropdown -->
              <select
                v-model="selectedPerfMajor"
                :style="{ colorScheme: isDark ? 'dark' : 'light' }"
                class="bg-white dark:bg-[#182234] text-emerald-800 dark:text-emerald-300 border border-emerald-300/80 dark:border-emerald-500/40 rounded-xl px-2.5 py-1 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 cursor-pointer shadow-xs transition-colors"
              >
                <option value="all" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100 font-medium">{{ t('ជំនាញទាំង ៥ ទាំងអស់', 'All 5 Majors') }}</option>
                <option value="1" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100 font-medium">{{ t('បច្ចេកវិទ្យាព័ត៌មាន (IT)', 'Information Technology') }}</option>
                <option value="2" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100 font-medium">{{ t('ការងារសង្គម (SW)', 'Social Work') }}</option>
                <option value="3" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100 font-medium">{{ t('កសិកម្ម (AGR)', 'Agriculture') }}</option>
                <option value="4" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100 font-medium">{{ t('ទេសចរណ៍ (TRM)', 'Tourism') }}</option>
                <option value="5" class="bg-white dark:bg-[#0f172a] text-slate-900 dark:text-slate-100 font-medium">{{ t('អក្សរសាស្ត្រអង់គ្លេស (ENG)', 'English Literature') }}</option>
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

            <!-- Donut Chart Explicit Legend -->
            <div class="flex flex-wrap items-center justify-center gap-2.5 sm:gap-4 py-2 px-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 text-xs my-2.5">
              <div class="flex items-center gap-1.5 font-semibold text-slate-700 dark:text-slate-200">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-xs shrink-0"></span>
                <span>{{ t('បានបញ្ចប់', 'Completed') }}:</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ currentPerformance.completed }}%</span>
              </div>
              <div class="flex items-center gap-1.5 font-semibold text-slate-700 dark:text-slate-200">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-xs shrink-0"></span>
                <span>{{ t('កំពុងរៀន', 'In Progress') }}:</span>
                <span class="font-bold text-amber-600 dark:text-amber-400">{{ currentPerformance.in_progress }}%</span>
              </div>
              <div class="flex items-center gap-1.5 font-semibold text-slate-700 dark:text-slate-200">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-400 dark:bg-slate-500 shadow-xs shrink-0"></span>
                <span>{{ t('មិនទាន់ចាប់ផ្ដើម', 'Not Started') }}:</span>
                <span class="font-bold text-slate-600 dark:text-slate-300">{{ currentPerformance.not_started }}%</span>
              </div>
            </div>

            <!-- Status Breakdown (Completed, In Progress, Not Started) -->
            <div class="grid grid-cols-3 gap-2.5 text-center text-xs pt-3 border-t border-slate-100 dark:border-slate-800">
              <!-- Completed -->
              <div class="bg-emerald-50 dark:bg-emerald-950/40 p-2.5 rounded-xl border border-emerald-200/80 dark:border-emerald-800/60">
                <span class="text-emerald-700 dark:text-emerald-400 font-black block text-base">{{ currentPerformance.completed }}%</span>
                <span class="text-slate-600 dark:text-slate-300 text-[11px] font-semibold">{{ t('បានបញ្ចប់', 'Completed') }}</span>
              </div>
              <!-- In Progress -->
              <div class="bg-amber-50 dark:bg-amber-950/40 p-2.5 rounded-xl border border-amber-200/80 dark:border-amber-800/60">
                <span class="text-amber-700 dark:text-amber-400 font-black block text-base">{{ currentPerformance.in_progress }}%</span>
                <span class="text-slate-600 dark:text-slate-300 text-[11px] font-semibold">{{ t('កំពុងរៀន', 'In Progress') }}</span>
              </div>
              <!-- Not Started -->
              <div class="bg-slate-100 dark:bg-slate-800/80 p-2.5 rounded-xl border border-slate-200 dark:border-slate-700">
                <span class="text-slate-700 dark:text-slate-200 font-black block text-base">{{ currentPerformance.not_started }}%</span>
                <span class="text-slate-600 dark:text-slate-300 text-[11px] font-semibold">{{ t('មិនទាន់ចាប់ផ្ដើម', 'Not Started') }}</span>
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
