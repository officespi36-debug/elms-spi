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
      enrollments: [8, 12, 10, 16, 14, 18],
      completions: [3, 5, 4, 7, 6, 8]
    },
    weekly: {
      categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      enrollments: [450, 620, 580, 808],
      completions: [210, 340, 310, 490]
    },
    monthly: {
      categories: ['01 Jul', '03 Jul', '05 Jul', '07 Jul', '09 Jul', '11 Jul'],
      enrollments: [8, 12, 10, 16, 14, 18],
      completions: [3, 5, 4, 7, 6, 8]
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

// ── ROW 2: Sales Overview Dual Area Chart (Matching Image) ──
const salesOverviewSeries = computed(() => [
  {
    name: t('ការលក់ / ចុះឈ្មោះ (Sales)', 'Sales (₹)'),
    data: [8, 12, 10, 16, 14, 18]
  },
  {
    name: t('ប្រាក់ចំណេញ (Profit)', 'Profit (₹)'),
    data: [3, 5, 4, 7, 6, 8]
  }
])

const salesOverviewOptions = computed<any>(() => ({
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
    categories: ['01 Jul', '03 Jul', '05 Jul', '07 Jul', '09 Jul', '11 Jul'],
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
      formatter: (val: number) => `${val}L`,
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

// ── ROW 2: Sales by Category Donut Chart (Matching Image) ──
const categoryDonutSeries = ref([65, 18, 10, 7])
const categoryDonutOptions = computed<any>(() => ({
  chart: { type: 'donut', background: 'transparent' },
  labels: ['Gold Jewellery', 'Diamond', 'Silver', 'Platinum'],
  colors: ['#f59e0b', '#2563eb', '#10b981', '#8b5cf6'],
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

// ── ROW 4: Monthly Profit Trend Bar Chart (Matching Image) ──
const monthlyProfitSeries = computed(() => [
  {
    name: 'Profit',
    data: [8.5, 10.2, 12.8, 14.5, 13.2, 17.5, 19.2]
  }
])

const monthlyProfitOptions = computed<any>(() => ({
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
    labels: {
      formatter: (val: number) => `${val}L`,
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
    <div class="space-y-5 text-slate-800 dark:text-slate-100 font-sans pb-12">

      <!-- ── 0. TOP SUB-HEADER: Filters, Refresh, Export ── -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs dark:shadow-none flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <span class="text-xl">📊</span>
          <div>
            <h1 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white uppercase">
              {{ t('ផ្ទាំងគ្រប់គ្រងទូទៅ', 'Dashboard Overview') }}
            </h1>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
              {{ t('ប្រព័ន្ធគ្រប់គ្រងវិទ្យាស្ថាន SPI E-LMS & ហិរញ្ញវត្ថុ', 'SPI Higher Education Management & Institutional Analytics') }}
            </p>
          </div>
        </div>

        <!-- Controls: Period, Refresh, Export -->
        <div class="flex flex-wrap items-center gap-2.5">
          <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl border border-slate-200 dark:border-slate-700 text-xs">
            <button
              v-for="p in [
                { id: 'today', name: t('ថ្ងៃនេះ', 'Today') },
                { id: 'week', name: t('សប្តាហ៍', 'Week') },
                { id: 'month', name: t('ខែ', 'Month') },
                { id: 'year', name: t('ឆ្នាំ', 'Year') }
              ]"
              :key="p.id"
              @click="periodFilter = p.id; applyFilters()"
              :class="[
                periodFilter === p.id 
                  ? 'bg-blue-600 text-white font-bold shadow-xs' 
                  : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                'px-2.5 py-1 rounded-lg text-xs transition-all cursor-pointer'
              ]"
            >
              {{ p.name }}
            </button>
          </div>

          <button
            @click="applyFilters"
            :disabled="isRefreshing"
            class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950/40 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
          >
            <svg :class="{ 'animate-spin': isRefreshing }" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>{{ t('ផ្ទុកឡើងវិញ', 'Refresh') }}</span>
          </button>

          <button
            @click="exportReport"
            class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all cursor-pointer"
          >
            <span>📥 {{ t('ទាញយករបាយការណ៍', 'Export') }}</span>
          </button>
        </div>
      </div>

      <!-- ── ROW 1: 6 TOP KPI CARDS (Pixel-Perfect Matching Reference Image) ── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
        
        <!-- Card 1: Today's Sales ➔ Royal Purple/Violet -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7c3aed] to-[#6366f1] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('ការលក់ថ្ងៃនេះ', "TODAY'S SALES") }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                🛍️
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              ₹ 8,45,320
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▲ 12.8%</span>
              <span class="opacity-80">vs Yesterday</span>
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

        <!-- Card 2: This Month Sales ➔ Emerald Green -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#10b981] to-[#059669] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('ការលក់ខែនេះ', 'THIS MONTH SALES') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                📈
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              ₹ 2,86,54,120
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▲ 18.6%</span>
              <span class="opacity-80">vs Last Month</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </div>

        <!-- Card 3: Today's Profit ➔ Vivid Orange -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#f97316] to-[#ea580c] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('ប្រាក់ចំណេញថ្ងៃនេះ', "TODAY'S PROFIT") }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                ₹
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              ₹ 1,25,680
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▲ 10.4%</span>
              <span class="opacity-80">vs Yesterday</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </div>

        <!-- Card 4: Total Stock Value ➔ Royal Blue -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0284c7] to-[#2563eb] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('តម្លៃស្តុកសរុប', 'TOTAL STOCK VALUE') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                💎
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              ₹ 14,52,63,000
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▲ 9.2%</span>
              <span class="opacity-80">vs Last Month</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </div>

        <!-- Card 5: Total Customers ➔ Bright Rose/Pink -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#ec4899] to-[#e11d48] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('អតិថិជនសរុប', 'TOTAL CUSTOMERS') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                👥
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              3,568
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▲ 7.1%</span>
              <span class="opacity-80">vs Last Month</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 19 Q 25 10, 50 18 T 80 8 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 19 Q 25 10, 50 18 T 80 8 T 100 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </div>

        <!-- Card 6: Customer Outstanding ➔ Teal/Cyan -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#06b6d4] to-[#0d9488] text-white p-4 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('ជំពាក់នៅសល់', 'CUSTOMER OUTSTANDING') }}</span>
              <div class="w-8 h-8 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-sm shadow-2xs">
                💳
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              ₹ 18,75,420
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>▼ 3.4%</span>
              <span class="opacity-80">vs Last Month</span>
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

      <!-- ── ROW 2: 3-COLUMN ANALYTICS SECTION (Matching Reference Image) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (5/12): Sales Overview (This Month) Dual Line/Area Chart -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                  {{ t('ទិដ្ឋភាពទូទៅនៃការលក់ (ខែនេះ)', 'Sales Overview (This Month)') }}
                </h3>
                <!-- Legend items -->
                <div class="flex items-center gap-3 mt-1 text-xs">
                  <div class="flex items-center gap-1.5 font-semibold text-blue-600 dark:text-blue-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <span>Sales (₹)</span>
                  </div>
                  <div class="flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                    <span>Profit (₹)</span>
                  </div>
                </div>
              </div>
              <select class="text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1 font-semibold text-slate-700 dark:text-slate-200 cursor-pointer focus:outline-none">
                <option>This Month</option>
                <option>This Week</option>
                <option>This Year</option>
              </select>
            </div>

            <!-- ApexCharts Area -->
            <div class="h-64 w-full">
              <VueApexCharts
                type="area"
                height="100%"
                :options="salesOverviewOptions"
                :series="salesOverviewSeries"
              />
            </div>
          </div>
        </div>

        <!-- Col 2 (4/12): Sales by Category Donut Chart -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('ការលក់តាមប្រភេទទំនិញ', 'Sales by Category') }}
              </h3>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-2">
              <!-- Center Donut Chart with Diamond Icon -->
              <div class="relative w-44 h-44 shrink-0 flex items-center justify-center">
                <VueApexCharts
                  type="donut"
                  width="170"
                  :options="categoryDonutOptions"
                  :series="categoryDonutSeries"
                />
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                  <span class="text-2xl">💎</span>
                </div>
              </div>

              <!-- Vertical Right Legend -->
              <div class="space-y-3 w-full text-xs">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-100 leading-tight">Gold Jewellery</p>
                      <p class="text-[10px] text-slate-400">₹ 1,85,20,000</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white">65%</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-100 leading-tight">Diamond</p>
                      <p class="text-[10px] text-slate-400">₹ 51,20,000</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white">18%</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-100 leading-tight">Silver</p>
                      <p class="text-[10px] text-slate-400">₹ 28,50,000</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white">10%</span>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                    <div>
                      <p class="font-bold text-slate-800 dark:text-slate-100 leading-tight">Platinum</p>
                      <p class="text-[10px] text-slate-400">₹ 19,64,120</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white">7%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Col 3 (3/12): Collection Summary (Today) -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('សង្ខេបការប្រមូលប្រាក់ (ថ្ងៃនេះ)', 'Collection Summary (Today)') }}
              </h3>
            </div>

            <!-- List items -->
            <div class="space-y-3.5 mt-2 text-xs">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-500/15 text-emerald-600 flex items-center justify-center text-sm">
                    💵
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200">Cash</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">₹ 3,25,410</span>
                  <span class="ml-2 text-slate-400 font-semibold">38%</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-500/15 text-blue-600 flex items-center justify-center text-sm">
                    📱
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200">UPI / QR</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">₹ 2,75,320</span>
                  <span class="ml-2 text-slate-400 font-semibold">32%</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-500/15 text-amber-600 flex items-center justify-center text-sm">
                    💳
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200">Card</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">₹ 1,45,600</span>
                  <span class="ml-2 text-slate-400 font-semibold">17%</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-500/15 text-purple-600 flex items-center justify-center text-sm">
                    🏛️
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200">Bank Transfer</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">₹ 99,000</span>
                  <span class="ml-2 text-slate-400 font-semibold">11%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Collection bottom border -->
          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs mt-3">
            <span class="font-bold text-slate-700 dark:text-slate-300">{{ t('ការប្រមូលសរុប', 'Total Collection') }}</span>
            <span class="font-black text-emerald-600 dark:text-emerald-400 text-sm">₹ 8,45,330</span>
          </div>
        </div>

      </div>

      <!-- ── ROW 3: 3-COLUMN OPERATIONAL SECTION (Matching Reference Image) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (5/12): Top 5 Best Selling Items Table -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('ទំនិញលក់ដាច់បំផុតទាំង ៥', 'Top 5 Best Selling Items') }}
              </h3>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <th class="py-2">#</th>
                    <th class="py-2">Item Name</th>
                    <th class="py-2 text-center">Net Wt (gm)</th>
                    <th class="py-2 text-right">Sales (₹)</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr v-for="item in [
                    { id: 1, name: '22K Gold Necklace', weight: '125.450', sales: '24,56,800' },
                    { id: 2, name: '22K Gold Bangles', weight: '98.350', sales: '18,75,600' },
                    { id: 3, name: 'Diamond Ring', weight: '45.230', sales: '11,85,300' },
                    { id: 4, name: '18K Gold Chain', weight: '62.120', sales: '8,92,450' },
                    { id: 5, name: 'Silver Anklets', weight: '150.800', sales: '6,25,120' },
                  ]" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="py-2.5 text-slate-400 font-bold">{{ item.id }}</td>
                    <td class="py-2.5 font-semibold text-slate-800 dark:text-slate-100">{{ item.name }}</td>
                    <td class="py-2.5 text-center text-slate-600 dark:text-slate-300 font-medium">{{ item.weight }}</td>
                    <td class="py-2.5 text-right font-bold text-slate-900 dark:text-white">{{ item.sales }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-center mt-2">
            <a href="#" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
              {{ t('មើលទំនិញទាំងអស់ →', 'View All Items →') }}
            </a>
          </div>
        </div>

        <!-- Col 2 (4/12): Stock Summary -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('សង្ខេបស្តុកទំនិញ', 'Stock Summary') }}
              </h3>
            </div>

            <div class="space-y-3 mt-2 text-xs">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-500 text-white flex items-center justify-center text-xs shadow-2xs">
                    📦
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">Total Items</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">12,548</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-rose-500 text-white flex items-center justify-center text-xs shadow-2xs">
                    ⚖️
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">Total Net Weight</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">1,245.680 gm</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs shadow-2xs">
                    🪙
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">Gold Stock Value</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">₹ 11,25,40,000</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-xs shadow-2xs">
                    🥈
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">Silver Stock Value</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">₹ 2,15,30,000</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-purple-600 text-white flex items-center justify-center text-xs shadow-2xs">
                    💎
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">Diamond Stock Value</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">₹ 85,20,000</span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-center mt-2">
            <a href="#" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
              {{ t('មើលរបាយការណ៍ស្តុក →', 'View Stock Report →') }}
            </a>
          </div>
        </div>

        <!-- Col 3 (3/12): Low Stock Alert (10 Items) - Pastel Pink Soft Banner -->
        <div class="lg:col-span-3 bg-[#fff1f2] dark:bg-rose-950/25 border border-rose-200 dark:border-rose-900/50 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-rose-200/60 dark:border-rose-900/40 pb-3 mb-3">
              <div class="flex items-center gap-1.5">
                <span class="text-rose-600">🔔</span>
                <h3 class="font-bold text-sm text-rose-900 dark:text-rose-200">
                  {{ t('ការដាស់តឿនស្តុកទាប', 'Low Stock Alert') }}
                  <span class="text-rose-600 text-xs font-semibold">(10 Items)</span>
                </h3>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="text-[10px] font-bold text-rose-800/70 dark:text-rose-300 uppercase border-b border-rose-200/50 dark:border-rose-900/30">
                    <th class="py-1.5">Item Name</th>
                    <th class="py-1.5 text-center">Purity</th>
                    <th class="py-1.5 text-center">Net Wt</th>
                    <th class="py-1.5 text-right">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-rose-200/40 dark:divide-rose-900/30">
                  <tr v-for="alert in [
                    { name: 'Gold Ring (G-1021)', purity: '22K', weight: '1.250', status: 'Low', color: 'bg-rose-500 text-white' },
                    { name: 'Gold Chain (C-551)', purity: '22K', weight: '2.100', status: 'Low', color: 'bg-rose-500 text-white' },
                    { name: 'Silver Payal (S-781)', purity: '999', weight: '5.500', status: 'Low', color: 'bg-rose-500 text-white' },
                    { name: 'Diamond Pendant (D-21)', purity: '18K', weight: '0.850', status: 'Critical', color: 'bg-amber-500 text-white' },
                  ]" :key="alert.name">
                    <td class="py-2 font-semibold text-slate-800 dark:text-slate-100 truncate max-w-[95px]">{{ alert.name }}</td>
                    <td class="py-2 text-center text-slate-600 dark:text-slate-300">{{ alert.purity }}</td>
                    <td class="py-2 text-center text-slate-600 dark:text-slate-300">{{ alert.weight }}</td>
                    <td class="py-2 text-right">
                      <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="alert.color">
                        {{ alert.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="pt-3 border-t border-rose-200/60 dark:border-rose-900/40 text-center mt-2">
            <a href="#" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
              {{ t('មើលស្តុកទាបទាំងអស់ →', 'View All Low Stock →') }}
            </a>
          </div>
        </div>

      </div>

      <!-- ── ROW 4: 3-COLUMN BOTTOM SECTION (Matching Reference Image) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (4/12): Monthly Profit Trend Green Bar Chart -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('និន្នាការប្រាក់ចំណេញប្រចាំខែ', 'Monthly Profit Trend') }}
              </h3>
              <select class="text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-1 font-semibold text-slate-700 dark:text-slate-200 cursor-pointer focus:outline-none">
                <option>This Year</option>
                <option>Last Year</option>
              </select>
            </div>

            <div class="h-56 w-full">
              <VueApexCharts
                type="bar"
                height="100%"
                :options="monthlyProfitOptions"
                :series="monthlyProfitSeries"
              />
            </div>
          </div>
        </div>

        <!-- Col 2 (4/12): Recent Transactions -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('ប្រតិបត្តិការថ្មីៗ', 'Recent Transactions') }}
              </h3>
              <a href="#" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">View All →</a>
            </div>

            <!-- 5 Transactions -->
            <div class="space-y-3 mt-1 text-xs">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-teal-500 text-white flex items-center justify-center text-xs">
                    🧾
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-900 dark:text-white leading-tight">INV-2026-0711-0234</h5>
                    <p class="text-[10px] text-slate-400">Customer: Rajesh Gold Mart</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white block">₹ 1,25,680</span>
                  <span class="text-[10px] text-slate-400">11:25 AM</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-500 text-white flex items-center justify-center text-xs">
                    🧾
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-900 dark:text-white leading-tight">INV-2026-0711-0233</h5>
                    <p class="text-[10px] text-slate-400">Customer: Meena Jewellers</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white block">₹ 78,450</span>
                  <span class="text-[10px] text-slate-400">10:45 AM</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-rose-500 text-white flex items-center justify-center text-xs">
                    🛒
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-900 dark:text-white leading-tight">PUR-2026-0711-0156</h5>
                    <p class="text-[10px] text-slate-400">Supplier: Sri Gold Suppliers</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white block">₹ 2,45,000</span>
                  <span class="text-[10px] text-slate-400">09:30 AM</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-500 text-white flex items-center justify-center text-xs">
                    🧾
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-900 dark:text-white leading-tight">INV-2026-0711-0232</h5>
                    <p class="text-[10px] text-slate-400">Customer: Ananya Jewellery</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white block">₹ 94,330</span>
                  <span class="text-[10px] text-slate-400">09:10 AM</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-teal-500 text-white flex items-center justify-center text-xs">
                    📥
                  </div>
                  <div>
                    <h5 class="font-bold text-slate-900 dark:text-white leading-tight">RCPT-2026-0711-0085</h5>
                    <p class="text-[10px] text-slate-400">Customer: Kumar & Sons</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white block">₹ 1,83,000</span>
                  <span class="text-[10px] text-slate-400">08:35 AM</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Col 3 (4/12): Branch Wise Sales (This Month) -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('ការលក់តាមសាខា (ខែនេះ)', 'Branch Wise Sales (This Month)') }}
              </h3>
            </div>

            <!-- 5 Horizontal Progress Bars -->
            <div class="space-y-3.5 mt-2 text-xs">
              <div>
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200">Main Branch</span>
                  <span class="font-black text-slate-900 dark:text-white">₹ 1,25,40,000</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div class="h-full bg-blue-600 rounded-full" style="width: 85%;"></div>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200">Anna Nagar</span>
                  <span class="font-black text-slate-900 dark:text-white">₹ 65,20,000</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div class="h-full bg-emerald-500 rounded-full" style="width: 52%;"></div>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200">T.Nagar</span>
                  <span class="font-black text-slate-900 dark:text-white">₹ 48,75,000</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div class="h-full bg-amber-500 rounded-full" style="width: 38%;"></div>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200">Velachery</span>
                  <span class="font-black text-slate-900 dark:text-white">₹ 28,30,000</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div class="h-full bg-purple-600 rounded-full" style="width: 25%;"></div>
                </div>
              </div>

              <div>
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200">Tambaram</span>
                  <span class="font-black text-slate-900 dark:text-white">₹ 18,89,000</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div class="h-full bg-rose-500 rounded-full" style="width: 18%;"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Axis Scale -->
          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400 font-bold mt-2">
            <span>0</span>
            <span>50L</span>
            <span>1Cr</span>
            <span>1.5Cr</span>
          </div>
        </div>

      </div>

    </div>
  </AdminLayout>
</template>
