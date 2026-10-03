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
      categories: ['01 Jul', '03 Jul', '05 Jul', '07 Jul', '09 Jul', '11 Jul'],
      enrollments: [180, 240, 210, 310, 290, 380],
      completions: [120, 160, 140, 220, 205, 270]
    },
    weekly: {
      categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      enrollments: [450, 620, 580, 808],
      completions: [310, 420, 390, 560]
    },
    monthly: {
      categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
      enrollments: [320, 450, 510, 680, 720, 890, 940],
      completions: [210, 310, 380, 490, 530, 670, 710]
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
    { name: 'Information Technology', name_kh: 'បច្ចេកវិទ្យាព័ត៌មាន (IT)', count: 520, pct: 21 },
    { name: 'Social Work', name_kh: 'ការងារសង្គម (SW)', count: 548, pct: 23 },
    { name: 'Agriculture', name_kh: 'កសិកម្ម (AGR)', count: 600, pct: 24 },
    { name: 'Tourism', name_kh: 'ទេសចរណ៍ (TRM)', count: 410, pct: 17 },
    { name: 'English Literature', name_kh: 'អក្សរសាស្ត្រអង់គ្លេស (ENG)', count: 380, pct: 15 },
  ],
})

const page = usePage<any>()
const userName = computed(() => page.props.auth?.user?.name || 'Admin')

// Period & Filters state
const periodFilter = ref(props.filters?.period || 'month')
const majorFilter = ref(props.filters?.major_id || 'all')
const chartTimeframe = ref<'daily' | 'weekly' | 'monthly'>('monthly')
const isRefreshing = ref(false)

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

// ── ROW 2: Enrollment & Completion Trends Dual Area Chart ──
const enrollmentChartSeries = computed(() => [
  {
    name: t('ការចុះឈ្មោះថ្មី (New Enrollments)', 'New Enrollments'),
    data: chartTimeframe.value === 'daily' 
      ? [180, 240, 210, 310, 290, 380] 
      : chartTimeframe.value === 'weekly' 
        ? [450, 620, 580, 808] 
        : [320, 450, 510, 680, 720, 890, 940]
  },
  {
    name: t('ការបញ្ចប់វគ្គ (Completions)', 'Course Completions'),
    data: chartTimeframe.value === 'daily' 
      ? [120, 160, 140, 220, 205, 270] 
      : chartTimeframe.value === 'weekly' 
        ? [310, 420, 390, 560] 
        : [210, 310, 380, 490, 530, 670, 710]
  }
])

const enrollmentChartOptions = computed<any>(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    background: 'transparent',
    parentHeightOffset: 0,
  },
  colors: ['#2563eb', '#10b981'],
  stroke: { curve: 'smooth', width: 2.5 },
  dataLabels: { enabled: false },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 }
  },
  xaxis: {
    categories: chartTimeframe.value === 'daily'
      ? ['01 Jul', '03 Jul', '05 Jul', '07 Jul', '09 Jul', '11 Jul']
      : chartTimeframe.value === 'weekly'
        ? ['Week 1', 'Week 2', 'Week 3', 'Week 4']
        : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
    labels: {
      style: {
        colors: isDark.value ? '#94a3b8' : '#64748b',
        fontSize: '11px',
        fontWeight: 600
      },
    },
    axisBorder: { show: true, color: isDark.value ? '#334155' : '#e2e8f0' },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      formatter: (val: number) => `${val}`,
      style: {
        colors: isDark.value ? '#94a3b8' : '#64748b',
        fontSize: '11px',
        fontWeight: 600
      },
    },
  },
  grid: {
    borderColor: isDark.value ? '#334155' : '#f1f5f9',
    strokeDashArray: 3,
  },
  legend: { show: false },
  tooltip: { theme: isDark.value ? 'dark' : 'light' }
}))

// ── ROW 2: Students by Major Donut Chart ──
const majorDonutSeries = ref([21, 23, 24, 17, 15])
const majorDonutOptions = computed<any>(() => ({
  chart: { type: 'donut', background: 'transparent' },
  labels: ['IT', 'Social Work', 'Agriculture', 'Tourism', 'English Lit'],
  colors: ['#f59e0b', '#2563eb', '#10b981', '#8b5cf6', '#f43f5e'],
  legend: { show: false },
  stroke: { colors: [isDark.value ? '#0f172a' : '#ffffff'], width: 3 },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: { show: false }
      }
    }
  },
  tooltip: { theme: isDark.value ? 'dark' : 'light' }
}))

// ── ROW 4: Monthly Performance & Pass Rate Bar Chart ──
const performanceBarSeries = computed(() => [
  {
    name: t('អត្រាប្រឡងជាប់ (%)', 'Pass Rate (%)'),
    data: [78, 82, 85, 88, 84, 91, 93]
  }
])

const performanceBarOptions = computed<any>(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
    background: 'transparent',
    parentHeightOffset: 0,
  },
  colors: ['#10b981'],
  plotOptions: {
    bar: {
      borderRadius: 4,
      columnWidth: '45%',
      distributed: false,
    }
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
    labels: {
      style: {
        colors: isDark.value ? '#94a3b8' : '#64748b',
        fontSize: '11px',
        fontWeight: 600
      },
    },
    axisBorder: { show: true, color: isDark.value ? '#334155' : '#e2e8f0' },
    axisTicks: { show: false },
  },
  yaxis: {
    max: 100,
    labels: {
      formatter: (val: number) => `${val}%`,
      style: {
        colors: isDark.value ? '#94a3b8' : '#64748b',
        fontSize: '11px',
        fontWeight: 600
      },
    },
  },
  grid: {
    borderColor: isDark.value ? '#334155' : '#f1f5f9',
    strokeDashArray: 3,
  },
  legend: { show: false },
  tooltip: { theme: isDark.value ? 'dark' : 'light' }
}))
</script>

<template>
  <AdminLayout :title="t('ផ្ទាំងគ្រប់គ្រង Admin', 'Admin Dashboard')">
    <div class="space-y-4 text-slate-800 dark:text-slate-100 font-sans pb-12">

      <!-- ── ROW 1: 6 TOP KPI CARDS (Matching Vibrant Wave Aesthetic with Pure LMS Data) ── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
        
        <!-- Card 1: Total Students ➔ Royal Purple/Violet -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7c3aed] to-[#6366f1] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('និស្សិតសរុប', "TOTAL STUDENTS") }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                🎓
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ (props.stats?.total_students || 2458).toLocaleString() }}
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▲ 12.8%</span>
              <span class="opacity-80">{{ t('ធៀបនឹងឆមាសមុន', 'vs Last Term') }}</span>
            </p>
          </div>
          <!-- Sparkline wave path at bottom -->
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </div>

        <!-- Card 2: Total Courses ➔ Emerald Green -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#10b981] to-[#059669] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('វគ្គសិក្សាសរុប', 'TOTAL COURSES') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                📚
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ (props.stats?.total_courses || 328).toLocaleString() }}
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▲ 8.4%</span>
              <span class="opacity-80">{{ t('វគ្គកំពុងដំណើរការ', 'Active Modules') }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </div>

        <!-- Card 3: Total Teachers ➔ Vivid Orange -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#f97316] to-[#ea580c] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('សាស្ត្រាចារ្យសរុប', "TOTAL TEACHERS") }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                👨‍🏫
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ (props.stats?.total_teachers || 145).toLocaleString() }}
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▲ 5.2%</span>
              <span class="opacity-80">{{ t('បុគ្គលិកបង្រៀន', 'Faculty Staff') }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </div>

        <!-- Card 4: Active Students ➔ Royal Blue -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0284c7] to-[#2563eb] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('និស្សិតសកម្មអនឡាញ', 'ACTIVE STUDENTS') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                💻
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ (props.stats?.active_students || 2390).toLocaleString() }}
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▲ 97.2%</span>
              <span class="opacity-80">{{ t('អត្រាវត្តមានសិក្សា', 'Attendance') }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </div>

        <!-- Card 5: Course Completion Rate ➔ Bright Rose/Pink -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#ec4899] to-[#e11d48] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('អត្រាបញ្ចប់វគ្គ', 'COMPLETION RATE') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                🏆
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ props.stats?.completion_rate || 76 }}%
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▲ 6.5%</span>
              <span class="opacity-80">{{ t('លើសពីគោលដៅ', 'Target Exceeded') }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 19 Q 25 10, 50 18 T 80 8 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 19 Q 25 10, 50 18 T 80 8 T 100 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </div>

        <!-- Card 6: At-Risk Students ➔ Teal/Cyan -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#06b6d4] to-[#0d9488] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('និស្សិតប្រឈមហានិភ័យ', 'AT-RISK STUDENTS') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                ⚠️
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ props.stats?.at_risk_students || 12 }}
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▼ 4 Students</span>
              <span class="opacity-80">{{ t('ត្រូវការការគាំទ្រ', 'Need Help') }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 12 Q 25 20, 50 10 T 75 16 T 100 6 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 12 Q 25 20, 50 10 T 75 16 T 100 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </div>

      </div>

      <!-- ── ROW 2: 3-COLUMN ANALYTICS SECTION (Pure E-LMS Metrics) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (5/12): Enrollment & Completion Trends Dual Area Chart -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                  {{ t('និន្នាការចុះឈ្មោះ និងបញ្ចប់វគ្គ', 'Enrollment & Completion Trends') }}
                </h3>
                <!-- Legend items -->
                <div class="flex items-center gap-3 mt-1 text-xs">
                  <div class="flex items-center gap-1.5 font-semibold text-blue-600 dark:text-blue-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <span>{{ t('ចុះឈ្មោះថ្មី', 'New Enrollments') }}</span>
                  </div>
                  <div class="flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                    <span>{{ t('បញ្ចប់វគ្គ', 'Completions') }}</span>
                  </div>
                </div>
              </div>
              <select
                v-model="chartTimeframe"
                class="text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1 font-semibold text-slate-700 dark:text-slate-200 cursor-pointer focus:outline-none"
              >
                <option value="monthly">{{ t('ប្រចាំខែ', 'Monthly') }}</option>
                <option value="weekly">{{ t('ប្រចាំសប្តាហ៍', 'Weekly') }}</option>
                <option value="daily">{{ t('ប្រចាំថ្ងៃ', 'Daily') }}</option>
              </select>
            </div>

            <!-- ApexCharts Area -->
            <div class="h-64 w-full">
              <VueApexCharts
                type="area"
                height="100%"
                :options="enrollmentChartOptions"
                :series="enrollmentChartSeries"
              />
            </div>
          </div>
        </div>

        <!-- Col 2 (4/12): Students by Major Donut Chart -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('ការបែងចែកនិស្សិតតាមជំនាញ', 'Students by Academic Major') }}
              </h3>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-2">
              <!-- Center Donut Chart with Cap Icon -->
              <div class="relative w-44 h-44 shrink-0 flex items-center justify-center">
                <VueApexCharts
                  type="donut"
                  width="170"
                  :options="majorDonutOptions"
                  :series="majorDonutSeries"
                />
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                  <span class="text-2xl">🎓</span>
                </div>
              </div>

              <!-- Vertical Right Legend -->
              <div class="space-y-3 w-full text-xs">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-100 leading-tight">Info Tech (IT)</p>
                      <p class="text-[10px] text-slate-400">520 {{ t('នាក់', 'Students') }}</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white">21%</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-100 leading-tight">Social Work (SW)</p>
                      <p class="text-[10px] text-slate-400">548 {{ t('នាក់', 'Students') }}</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white">23%</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-100 leading-tight">Agriculture (AGR)</p>
                      <p class="text-[10px] text-slate-400">600 {{ t('នាក់', 'Students') }}</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white">24%</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-100 leading-tight">Tourism (TRM)</p>
                      <p class="text-[10px] text-slate-400">410 {{ t('នាក់', 'Students') }}</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white">17%</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-100 leading-tight">English Lit (ENG)</p>
                      <p class="text-[10px] text-slate-400">380 {{ t('នាក់', 'Students') }}</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white">15%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Col 3 (3/12): Academic Progress Breakdown -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('ស្ថានភាពវឌ្ឍនភាពសិក្សា', 'Academic Progress Status') }}
              </h3>
            </div>

            <!-- List items -->
            <div class="space-y-3.5 mt-2 text-xs">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-500/15 text-emerald-600 flex items-center justify-center text-sm">
                    ✅
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200">{{ t('បានបញ្ចប់', 'Completed') }}</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">1,868</span>
                  <span class="ml-2 text-slate-400 font-semibold">76%</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-500/15 text-blue-600 flex items-center justify-center text-sm">
                    📖
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200">{{ t('កំពុងរៀន', 'In Progress') }}</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">442</span>
                  <span class="ml-2 text-slate-400 font-semibold">18%</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-500/15 text-amber-600 flex items-center justify-center text-sm">
                    ⏳
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200">{{ t('មិនទាន់ចាប់ផ្ដើម', 'Not Started') }}</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">148</span>
                  <span class="ml-2 text-slate-400 font-semibold">6%</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-500/15 text-purple-600 flex items-center justify-center text-sm">
                    📜
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200">{{ t('វិញ្ញាបនបត្រចេញរួច', 'Certificates') }}</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">1,540</span>
                  <span class="ml-2 text-slate-400 font-semibold">92%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Enrolled bottom border -->
          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs mt-3">
            <span class="font-bold text-slate-700 dark:text-slate-300">{{ t('និស្សិតចុះឈ្មោះសរុប', 'Total Enrolled') }}</span>
            <span class="font-black text-emerald-600 dark:text-emerald-400 text-sm">2,458 {{ t('នាក់', 'Students') }}</span>
          </div>
        </div>

      </div>

      <!-- ── ROW 3: 3-COLUMN OPERATIONAL SECTION (Top Courses, Structure & Alerts) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (5/12): Top Performing Courses Table -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('វគ្គសិក្សាដែលមានលទ្ធផលខ្ពស់បំផុត', 'Top Performing Courses') }}
              </h3>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <th class="py-2">#</th>
                    <th class="py-2">{{ t('ឈ្មោះវគ្គសិក្សា', 'Course Name') }}</th>
                    <th class="py-2 text-center">{{ t('ជំនាញ', 'Major') }}</th>
                    <th class="py-2 text-center">{{ t('និស្សិត', 'Students') }}</th>
                    <th class="py-2 text-right">{{ t('បញ្ចប់ (%)', 'Pass (%)') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr v-for="item in [
                    { id: 1, name: 'Web & Mobile Development', major: 'IT', students: 185, pass: '94%' },
                    { id: 2, name: 'Community Social Work', major: 'SW', students: 142, pass: '88%' },
                    { id: 3, name: 'Modern Crop Agronomy', major: 'AGR', students: 136, pass: '82%' },
                    { id: 4, name: 'Eco-Tourism & Hospitality', major: 'TRM', students: 118, pass: '79%' },
                    { id: 5, name: 'Business English Communication', major: 'ENG', students: 124, pass: '91%' },
                  ]" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="py-2.5 text-slate-400 font-bold">{{ item.id }}</td>
                    <td class="py-2.5 font-semibold text-slate-800 dark:text-slate-100">{{ item.name }}</td>
                    <td class="py-2.5 text-center">
                      <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400">
                        {{ item.major }}
                      </span>
                    </td>
                    <td class="py-2.5 text-center text-slate-600 dark:text-slate-300 font-medium">{{ item.students }}</td>
                    <td class="py-2.5 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ item.pass }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-center mt-2">
            <Link href="/admin/course-module/all" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
              {{ t('មើលវគ្គសិក្សាទាំងអស់ →', 'View All Courses →') }}
            </Link>
          </div>
        </div>

        <!-- Col 2 (4/12): Academic Structure Summary -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('រចនាសម្ព័ន្ធអប់រំ និងធនធាន', 'Academic Structure Summary') }}
              </h3>
            </div>

            <div class="space-y-3 mt-2 text-xs">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-500 text-white flex items-center justify-center text-xs shadow-2xs">
                    🏛️
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">{{ t('ជំនាញបណ្តុះបណ្តាល', 'Academic Majors') }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">5 {{ t('ដេប៉ាតឺម៉ង់', 'Majors') }}</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-xs shadow-2xs">
                    👥
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">{{ t('ថ្នាក់សិក្សាសកម្ម', 'Active Classes / Cohorts') }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">36 {{ t('ថ្នាក់', 'Classes') }}</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs shadow-2xs">
                    ⏰
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">{{ t('វេនសិក្សាផ្លូវការ', 'Study Shifts') }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">4 {{ t('វេន', 'Shifts') }}</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-purple-600 text-white flex items-center justify-center text-xs shadow-2xs">
                    📑
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">{{ t('មេរៀន និងធនធានបង្រៀន', 'Published Lessons') }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">1,420</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-rose-500 text-white flex items-center justify-center text-xs shadow-2xs">
                    📝
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">{{ t('កម្រងសំណួរ & ការប្រឡង', 'Quizzes & Exams') }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">580</span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-center mt-2">
            <Link href="/admin/academic-structure/majors" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
              {{ t('មើលរចនាសម្ព័ន្ធអប់រំទាំងអស់ →', 'View Academic Structure →') }}
            </Link>
          </div>
        </div>

        <!-- Col 3 (3/12): Critical Admin Alerts & Actions - Soft Pink Banner -->
        <div class="lg:col-span-3 bg-[#fff1f2] dark:bg-rose-950/25 border border-rose-200 dark:border-rose-900/50 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-rose-200/60 dark:border-rose-900/40 pb-3 mb-3">
              <div class="flex items-center gap-1.5">
                <span class="text-rose-600">🔔</span>
                <h3 class="font-bold text-sm text-rose-900 dark:text-rose-200">
                  {{ t('ការដាស់តឿនបន្ទាន់', 'Critical Alerts') }}
                  <span class="text-rose-600 text-xs font-semibold">(4 Items)</span>
                </h3>
              </div>
            </div>

            <div class="space-y-2.5 text-xs">
              <div v-for="alert in [
                { title: '12 At-Risk Students', desc: 'Completion < 30% on quizzes', status: 'Action', color: 'bg-rose-500 text-white', link: '/admin/progress?tab=at_risk' },
                { title: '5 Courses Waiting Review', desc: 'Faculty syllabus submitted', status: 'Pending', color: 'bg-amber-500 text-white', link: '/admin/course-module/all' },
                { title: '3 Teachers Accounts', desc: 'Department verification', status: 'Review', color: 'bg-blue-500 text-white', link: '/admin/user-management/teachers' },
                { title: '2 Announcements', desc: 'Ready for semester start', status: 'Scheduled', color: 'bg-emerald-500 text-white', link: '/admin/notifications/announcements' },
              ]" :key="alert.title" class="p-2 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-rose-100 dark:border-rose-900/30 flex items-center justify-between">
                <div>
                  <h5 class="font-bold text-slate-900 dark:text-slate-100 leading-tight">{{ alert.title }}</h5>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ alert.desc }}</p>
                </div>
                <Link :href="alert.link" class="px-2 py-0.5 rounded text-[10px] font-bold shrink-0 ml-2" :class="alert.color">
                  {{ alert.status }}
                </Link>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-rose-200/60 dark:border-rose-900/40 text-center mt-2">
            <Link href="/admin/progress?tab=at_risk" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">
              {{ t('មើលការដាស់តឿនទាំងអស់ →', 'View All Action Items →') }}
            </Link>
          </div>
        </div>

      </div>

      <!-- ── ROW 4: 3-COLUMN BOTTOM SECTION (Pass Rate, Activities, Department Distribution) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (4/12): Monthly Student Pass Rate Trend Bar Chart -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('និន្នាការអត្រាប្រឡងជាប់ (%)', 'Monthly Exam Pass Rate (%)') }}
              </h3>
              <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                93% Current
              </span>
            </div>

            <div class="h-56 w-full">
              <VueApexCharts
                type="bar"
                height="100%"
                :options="performanceBarOptions"
                :series="performanceBarSeries"
              />
            </div>
          </div>
        </div>

        <!-- Col 2 (4/12): Recent Academic Activities -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('សកម្មភាពសិក្សាថ្មីៗ', 'Recent Academic Activities') }}
              </h3>
              <Link href="/admin/reports?tab=students" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">{{ t('មើលទាំងអស់ →', 'View All →') }}</Link>
            </div>

            <!-- 5 Academic Activities -->
            <div class="space-y-3 mt-1 text-xs">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-500 text-white flex items-center justify-center text-xs">
                    🎓
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-900 dark:text-white leading-tight">Kosal Seng</h5>
                    <p class="text-[10px] text-slate-400">{{ t('ចុះឈ្មោះ: Web Development', 'Enrolled: Web Development') }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-emerald-600 dark:text-emerald-400 block">{{ t('ជោគជ័យ', 'Enrolled') }}</span>
                  <span class="text-[10px] text-slate-400">10 mins ago</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-xs">
                    📝
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-900 dark:text-white leading-tight">Sreyneang Pich</h5>
                    <p class="text-[10px] text-slate-400">{{ t('ប្រគល់កិច្ចការ: Social Work', 'Submitted: Social Work') }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-blue-600 dark:text-blue-400 block">Grade: A</span>
                  <span class="text-[10px] text-slate-400">25 mins ago</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-purple-500 text-white flex items-center justify-center text-xs">
                    👨‍🏫
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-900 dark:text-white leading-tight">Dr. Sokha Meas</h5>
                    <p class="text-[10px] text-slate-400">{{ t('បង្កើតកម្រងសំណួរប្រឡងបញ្ចប់', 'Published Final Exam Quiz') }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-purple-600 dark:text-purple-400 block">50 MCQs</span>
                  <span class="text-[10px] text-slate-400">1 hour ago</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs">
                    📜
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-900 dark:text-white leading-tight">Virak Chea</h5>
                    <p class="text-[10px] text-slate-400">{{ t('បញ្ចប់វគ្គ: Crop Agronomy', 'Completed: Crop Agronomy') }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-amber-600 dark:text-amber-400 block">Certificate</span>
                  <span class="text-[10px] text-slate-400">2 hours ago</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-teal-500 text-white flex items-center justify-center text-xs">
                    📥
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-900 dark:text-white leading-tight">Linda Meng</h5>
                    <p class="text-[10px] text-slate-400">{{ t('ចុះឈ្មោះ: Hospitality & Tourism', 'Enrolled: Tourism') }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-teal-600 dark:text-teal-400 block">Semester 2</span>
                  <span class="text-[10px] text-slate-400">3 hours ago</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Col 3 (4/12): Department Wise Enrollment Distribution -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('ការចុះឈ្មោះតាមមហាវិទ្យាល័យ / ដេប៉ាតឺម៉ង់', 'Department Wise Enrollment') }}
              </h3>
            </div>

            <!-- 5 Horizontal Progress Bars for 5 Departments -->
            <div class="space-y-3.5 mt-2 text-xs">
              <div>
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200">Information Technology (IT)</span>
                  <span class="font-black text-slate-900 dark:text-white">520 {{ t('នាក់', 'Students') }}</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div class="h-full bg-blue-600 rounded-full" style="width: 86%;"></div>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200">Social Work (SW)</span>
                  <span class="font-black text-slate-900 dark:text-white">548 {{ t('នាក់', 'Students') }}</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div class="h-full bg-emerald-500 rounded-full" style="width: 91%;"></div>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200">Agriculture (AGR)</span>
                  <span class="font-black text-slate-900 dark:text-white">600 {{ t('នាក់', 'Students') }}</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div class="h-full bg-amber-500 rounded-full" style="width: 100%;"></div>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200">Tourism & Hospitality (TRM)</span>
                  <span class="font-black text-slate-900 dark:text-white">410 {{ t('នាក់', 'Students') }}</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div class="h-full bg-purple-600 rounded-full" style="width: 68%;"></div>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200">English Literature (ENG)</span>
                  <span class="font-black text-slate-900 dark:text-white">380 {{ t('នាក់', 'Students') }}</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div class="h-full bg-rose-500 rounded-full" style="width: 63%;"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Axis Scale -->
          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400 font-bold mt-2">
            <span>0</span>
            <span>200</span>
            <span>400</span>
            <span>600+</span>
          </div>
        </div>

      </div>

    </div>
  </AdminLayout>
</template>
