<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

const { isKhmer } = useLanguage()

interface ActivityItem {
  id: string | number
  title: string
  title_kh?: string
  description: string
  description_kh?: string
  course: string
  course_kh?: string
  type: string
  duration: string
  time: string
  progress: number
  icon: string
  icon_bg: string
  progress_color: string
  href?: string
}

interface ChartPoint {
  x: number
  y: number
  date: string
  label: string
  hours: number
  seconds?: number
  duration: string
}

const props = defineProps<{
  analytics?: {
    summary: {
      date_range_label?: string
      total_study_time: string
      total_seconds: number
      time_trend: string
      daily_average: string
      daily_avg_trend: string
      total_sessions?: number
      study_sessions: number
      sessions_trend: string
      active_days: string
      active_days_count?: number
      window_days?: number
      active_days_note: string
      peak_day?: string
      longest_session: string
      longest_session_date: string
      completed_lessons?: number
      quizzes_taken?: number
      enrolled_courses?: number
    }
    trend_chart: {
      granularity: string
      max_hours?: number
      peak_point?: {
        index: number
        date: string
        label: string
        hours: number
        duration: string
      } | null
      points: Array<{
        date: string
        label: string
        hours: number
        seconds?: number
        duration: string
      }>
    }
    activity_breakdown: Array<{
      id: number
      key?: string
      name: string
      name_kh?: string
      seconds?: number
      percentage: number
      duration: string
      color: string
      bg_class: string
      code: string
    }>
    calendar_heatmap: {
      days: string[]
      weeks: Array<{
        label: string
        cells: Array<{
          day: string
          date: string
          hours: number
          duration?: string
          level: number
          sessions: number
        }>
      }>
    }
    recent_activities: ActivityItem[]
    this_week_overview: {
      date_range: string
      metrics: Array<{
        label: string
        label_kh?: string
        value: string
        trend: string
        is_positive: boolean
      }>
    }
    learning_streak: {
      current_streak: number
      best_streak: number
      best_streak_date: string
      days: Array<{
        label: string
        day_name?: string
        date: string
        active: boolean
      }>
    }
    recent_achievements: Array<{
      id: string | number
      title: string
      title_kh?: string
      description: string
      description_kh?: string
      date: string
      icon: string
      icon_bg: string
    }>
  }
  filters?: {
    range: string
    granularity: string
  }
}>()

const emptySummary = {
  date_range_label: '—',
  total_study_time: '0h 00m',
  total_seconds: 0,
  time_trend: '0% vs prior period',
  daily_average: '0h 00m',
  daily_avg_trend: '0% vs prior period',
  total_sessions: 0,
  study_sessions: 0,
  sessions_trend: '0 vs prior period',
  active_days: '0 / 7',
  active_days_count: 0,
  window_days: 7,
  active_days_note: 'No activity yet',
  peak_day: '0h 00m',
  longest_session: '0h 00m',
  longest_session_date: '—',
  completed_lessons: 0,
  quizzes_taken: 0,
  enrolled_courses: 0,
}

const summary = computed(() => props.analytics?.summary || emptySummary)
const trendPoints = computed(() => props.analytics?.trend_chart?.points || [])
const maxAxisHours = computed(() => Math.max(2, props.analytics?.trend_chart?.max_hours || 4))
const activityBreakdown = computed(() => props.analytics?.activity_breakdown || [])
const calendarHeatmap = computed(() => props.analytics?.calendar_heatmap || { days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], weeks: [] })
const recentActivities = computed(() => props.analytics?.recent_activities || [])
const thisWeekOverview = computed(() => props.analytics?.this_week_overview || { date_range: '—', metrics: [] })
const learningStreak = computed(() => props.analytics?.learning_streak || { current_streak: 0, best_streak: 0, best_streak_date: '—', days: [] })
const recentAchievements = computed(() => props.analytics?.recent_achievements || [])

const hasAnyStudyTime = computed(() => (summary.value.total_seconds || 0) > 0)

const rangeOptions = [
  { key: '7d', label: '7D' },
  { key: '30d', label: '30D' },
  { key: '90d', label: '90D' },
  { key: 'this_month', label: 'Month' },
  { key: 'all', label: 'All' },
]

const selectedRange = ref(props.filters?.range || '7d')
const selectedGranularity = ref<'daily' | 'weekly'>(
  props.filters?.granularity === 'weekly' ? 'weekly' : 'daily'
)
const isReportModalOpen = ref(false)
const hoveredChartPoint = ref<ChartPoint | null>(null)

const setRange = (range: string) => {
  selectedRange.value = range
  router.get('/student/progress/learning-time', {
    range: range,
    granularity: selectedGranularity.value,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

const setGranularity = (granularity: 'daily' | 'weekly') => {
  selectedGranularity.value = granularity
  router.get('/student/progress/learning-time', {
    range: selectedRange.value,
    granularity: granularity,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

// Dynamic Y-Axis labels
const yAxisLabels = computed(() => {
  const m = maxAxisHours.value
  return [
    `${m}h`,
    `${+(m * 0.75).toFixed(1)}h`,
    `${+(m * 0.5).toFixed(1)}h`,
    `${+(m * 0.25).toFixed(1)}h`,
    '0h',
  ]
})

// Dynamic X-Axis labels (up to 7 evenly spaced labels)
const xAxisLabels = computed(() => {
  const pts = trendPoints.value
  if (!pts.length) return []
  if (pts.length <= 7) return pts.map(p => p.label)
  const step = (pts.length - 1) / 6
  const labels: string[] = []
  for (let i = 0; i < 7; i++) {
    const idx = Math.min(pts.length - 1, Math.round(i * step))
    labels.push(pts[idx].label)
  }
  return labels
})

// SVG Line Chart Calculation
const chartSvgPoints = computed<ChartPoint[]>(() => {
  const pts = trendPoints.value
  if (!pts || pts.length === 0) return []
  const width = 480
  const height = 150
  const paddingX = 15
  const paddingY = 20
  const usableW = width - paddingX * 2
  const usableH = height - paddingY * 2

  const maxH = maxAxisHours.value
  const stepX = pts.length === 1 ? 0 : usableW / (pts.length - 1)

  return pts.map((p, idx) => {
    const x = pts.length === 1 ? width / 2 : paddingX + idx * stepX
    const ratio = Math.min(1, Math.max(0, p.hours / maxH))
    const y = paddingY + (1 - ratio) * usableH
    return { ...p, x, y }
  })
})

const chartSvgPath = computed<string>(() => {
  const pts = chartSvgPoints.value
  if (!pts || pts.length === 0) return ''
  if (pts.length === 1) {
    return `M 15,${pts[0].y} L 465,${pts[0].y}`
  }
  let path = `M ${pts[0].x},${pts[0].y}`
  for (let i = 0; i < pts.length - 1; i++) {
    const p0 = pts[i]
    const p1 = pts[i + 1]
    const cpX = (p0.x + p1.x) / 2
    path += ` C ${cpX},${p0.y} ${cpX},${p1.y} ${p1.x},${p1.y}`
  }
  return path
})

const chartSvgAreaPath = computed<string>(() => {
  const pts = chartSvgPoints.value
  if (!pts || pts.length === 0) return ''
  const baseLine = 140
  if (pts.length === 1) {
    return `M 15,${pts[0].y} L 465,${pts[0].y} L 465,${baseLine} L 15,${baseLine} Z`
  }
  let area = chartSvgPath.value
  area += ` L ${pts[pts.length - 1].x},${baseLine} L ${pts[0].x},${baseLine} Z`
  return area
})

// Dynamic Donut Segments (circumference = 100 for r = 15.915)
const donutSegments = computed(() => {
  if (!hasAnyStudyTime.value) return []
  let offset = 0
  return activityBreakdown.value
    .filter(item => item.percentage > 0)
    .map(item => {
      const seg = {
        ...item,
        dashArray: `${item.percentage} ${Math.max(0, 100 - item.percentage)}`,
        dashOffset: -offset,
      }
      offset += item.percentage
      return seg
    })
})

const topActivityItem = computed(() => {
  if (!hasAnyStudyTime.value || !activityBreakdown.value.length) return null
  return [...activityBreakdown.value].sort((a, b) => b.percentage - a.percentage)[0]
})

const quizActivityItem = computed(() => {
  return activityBreakdown.value.find(a => a.key === 'quiz') || null
})

// Heatmap level color helper
const getHeatmapColor = (level: number) => {
  switch (level) {
    case 4: return 'bg-emerald-500 border-emerald-400 text-white'
    case 3: return 'bg-teal-500 border-teal-400 text-white'
    case 2: return 'bg-teal-700/80 border-teal-600 text-teal-100'
    case 1: return 'bg-emerald-950 border-emerald-800 text-emerald-300'
    default: return 'bg-slate-900 border-slate-800 text-slate-600'
  }
}
</script>

<template>
  <StudentLayout title="Learning Activity — Progress & Analytics">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER & DATE FILTERS ================= -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
            <span>{{ isKhmer ? 'សកម្មភាពសិក្សា' : 'Learning Activity' }}</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">⏱️</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            {{ isKhmer ? 'តាមដានម៉ោងសិក្សា សកម្មភាព និងទម្លាប់សិក្សាប្រចាំថ្ងៃរបស់អ្នកពីទិន្នន័យពិត។' : 'Track your real study time, sessions, and learning consistency.' }}
          </p>
        </div>

        <!-- Date Range Filter & Quick Range Pills -->
        <div class="flex flex-wrap items-center gap-2.5 self-start md:self-auto">
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900/90 border border-slate-800 text-xs font-semibold text-slate-300 shadow-sm">
            <span>📅</span>
            <span>{{ summary.date_range_label || '—' }}</span>
          </div>

          <div class="flex items-center bg-slate-900/90 border border-slate-800 rounded-xl p-1 shadow-sm">
            <button
              v-for="r in rangeOptions"
              :key="r.key"
              type="button"
              @click="setRange(r.key)"
              :class="[
                selectedRange.toLowerCase() === r.key
                  ? 'bg-purple-600 text-white shadow-md shadow-purple-900/40'
                  : 'text-slate-400 hover:text-slate-200',
                'px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer'
              ]"
            >
              {{ r.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- ================= 2. 5 SUMMARY CARDS ================= -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

        <!-- CARD 1: Total Study Time -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-purple-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ម៉ោងសិក្សាសរុប' : 'Total Study Time' }}</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.total_study_time }}</p>
            <p class="text-[11px] text-slate-400 flex items-center gap-1 font-medium pt-0.5">
              <span>{{ summary.time_trend }}</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-full bg-purple-600 text-white flex items-center justify-center text-lg shadow-lg shadow-purple-900/40 group-hover:scale-110 transition-transform shrink-0">
            ⏱
          </div>
        </div>

        <!-- CARD 2: Daily Average -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-blue-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'មធ្យមភាគប្រចាំថ្ងៃ' : 'Daily Average' }}</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.daily_average }}</p>
            <p class="text-[11px] text-slate-400 flex items-center gap-1 font-medium pt-0.5">
              <span>{{ summary.daily_avg_trend }}</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center text-lg shadow-lg shadow-blue-900/40 group-hover:scale-110 transition-transform shrink-0">
            📅
          </div>
        </div>

        <!-- CARD 3: Study Sessions -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-emerald-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ចំនួនដងសិក្សា' : 'Study Sessions' }}</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.study_sessions }}</p>
            <p class="text-[11px] text-slate-400 flex items-center gap-1 font-medium pt-0.5">
              <span>{{ summary.sessions_trend }}</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center text-lg shadow-lg shadow-emerald-900/40 group-hover:scale-110 transition-transform shrink-0">
            〽️
          </div>
        </div>

        <!-- CARD 4: Active Days -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-orange-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ថ្ងៃសិក្សាសកម្ម' : 'Active Days' }}</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.active_days }}</p>
            <p class="text-[11px] text-orange-400 flex items-center gap-1 font-medium pt-0.5">
              <span>🔥</span>
              <span>{{ summary.active_days_note }}</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-full bg-orange-600 text-white flex items-center justify-center text-lg shadow-lg shadow-orange-900/40 group-hover:scale-110 transition-transform shrink-0">
            🔥
          </div>
        </div>

        <!-- CARD 5: Peak Day -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-amber-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ថ្ងៃសិក្សាច្រើនបំផុត' : 'Peak Study Day' }}</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.longest_session }}</p>
            <p class="text-[11px] text-slate-400 font-medium pt-0.5">
              {{ summary.longest_session_date }}
            </p>
          </div>
          <div class="w-12 h-12 rounded-full bg-amber-500 text-white flex items-center justify-center text-lg shadow-lg shadow-amber-900/40 group-hover:scale-110 transition-transform shrink-0">
            🏆
          </div>
        </div>

      </div>

      <!-- ================= 3. MIDDLE ROW: CHARTS & CALENDAR HEATMAP ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT (8/12): Study Time Over Time & Study Time by Activity -->
        <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-12 gap-6">

          <!-- Card A (7/12 of 8/12): Study Time Over Time -->
          <div class="md:col-span-7 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3 relative">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-white tracking-tight">
                {{ isKhmer ? 'ម៉ោងសិក្សាតាមពេលវេលា' : 'Study Time Over Time' }}
              </h3>

              <!-- Daily / Weekly Pills -->
              <div class="flex items-center bg-slate-900 border border-slate-800 rounded-lg p-0.5">
                <button
                  v-for="g in (['daily', 'weekly'] as const)"
                  :key="g"
                  type="button"
                  @click="setGranularity(g)"
                  :class="[
                    selectedGranularity === g
                      ? 'bg-purple-600 text-white font-bold'
                      : 'text-slate-400 hover:text-white',
                    'px-2 py-0.5 rounded text-[10px] capitalize transition-all cursor-pointer'
                  ]"
                >
                  {{ g }}
                </button>
              </div>
            </div>

            <!-- Empty State when 0 study time -->
            <div v-if="!hasAnyStudyTime" class="h-44 flex flex-col items-center justify-center text-center px-4 space-y-2">
              <div class="w-10 h-10 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-lg">
                📉
              </div>
              <p class="text-xs font-bold text-slate-300">
                {{ isKhmer ? 'មិនទាន់មានទិន្នន័យម៉ោងសិក្សាក្នុងរយៈពេលនេះទេ' : 'No study sessions recorded in this period yet' }}
              </p>
              <p class="text-[11px] text-slate-400">
                {{ isKhmer ? 'ចាប់ផ្តើមមើលមេរៀន ឬធ្វើតេស្តដើម្បីកត់ត្រាម៉ោងសិក្សារបស់អ្នក។' : 'Watch a lesson or complete a quiz to track your study hours.' }}
              </p>
              <Link
                href="/student/browse"
                class="mt-1 px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-[11px] font-bold transition-all"
              >
                {{ isKhmer ? 'ស្វែងរកវគ្គសិក្សា →' : 'Browse Courses →' }}
              </Link>
            </div>

            <!-- Line Chart SVG -->
            <div v-else class="relative w-full h-44 pt-2">
              <!-- Y-Axis labels -->
              <div class="absolute left-0 inset-y-2 flex flex-col justify-between text-[9px] text-slate-500 font-mono select-none pointer-events-none">
                <span v-for="yl in yAxisLabels" :key="yl">{{ yl }}</span>
              </div>

              <div class="ml-8 h-full relative">
                <!-- Grid lines -->
                <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                  <div class="w-full border-b border-slate-800/30"></div>
                  <div class="w-full border-b border-slate-800/30"></div>
                  <div class="w-full border-b border-slate-800/30"></div>
                  <div class="w-full border-b border-slate-800/30"></div>
                  <div class="w-full border-b border-slate-800/60"></div>
                </div>

                <!-- SVG Area & Stroke -->
                <svg class="w-full h-[115px] overflow-visible" viewBox="0 0 480 150" preserveAspectRatio="none">
                  <defs>
                    <linearGradient id="activityPurpleGlow" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" stop-color="#A855F7" stop-opacity="0.35" />
                      <stop offset="100%" stop-color="#A855F7" stop-opacity="0.0" />
                    </linearGradient>
                  </defs>

                  <path :d="chartSvgAreaPath" fill="url(#activityPurpleGlow)" />
                  <path
                    :d="chartSvgPath"
                    fill="none"
                    stroke="#A855F7"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />

                  <circle
                    v-for="(pt, idx) in chartSvgPoints"
                    :key="idx"
                    :cx="pt.x"
                    :cy="pt.y"
                    r="3.5"
                    class="fill-slate-950 stroke-purple-400 stroke-2 hover:stroke-white transition-all cursor-pointer"
                    @mouseenter="hoveredChartPoint = pt"
                    @mouseleave="hoveredChartPoint = null"
                  />
                </svg>

                <!-- Dynamic Hover Tooltip -->
                <div
                  v-if="hoveredChartPoint"
                  class="absolute -top-2 right-2 bg-slate-900/95 border border-purple-500/70 rounded-lg px-2.5 py-1 shadow-xl text-[10px] text-slate-200 pointer-events-none whitespace-nowrap z-10"
                >
                  <p class="font-mono text-center"><strong class="text-white">{{ hoveredChartPoint.label }}</strong></p>
                  <p class="text-purple-400 font-bold font-mono text-center">{{ hoveredChartPoint.duration }}</p>
                </div>

                <!-- Bottom X-Axis labels -->
                <div class="w-full flex justify-between text-[8px] sm:text-[9px] text-slate-500 font-mono mt-1 overflow-hidden">
                  <span v-for="(lbl, idx) in xAxisLabels" :key="idx">{{ lbl }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Card B (5/12 of 8/12): Study Time by Activity -->
          <div class="md:col-span-5 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3 flex flex-col justify-between">
            <h3 class="text-sm font-bold text-white tracking-tight border-b border-slate-800/60 pb-3">
              {{ isKhmer ? 'ម៉ោងសិក្សាតាមប្រភេទ' : 'Study Time by Activity' }}
            </h3>

            <div class="flex items-center justify-between gap-3 py-1">
              <!-- Donut Chart SVG -->
              <div class="relative w-28 h-28 flex items-center justify-center shrink-0">
                <svg class="w-28 h-28 -rotate-90 transform" viewBox="0 0 42 42">
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#1E293B" stroke-width="4.5" />
                  <circle
                    v-for="seg in donutSegments"
                    :key="seg.id"
                    cx="21"
                    cy="21"
                    r="15.915"
                    fill="none"
                    :stroke="seg.color"
                    stroke-width="4.5"
                    :stroke-dasharray="seg.dashArray"
                    :stroke-dashoffset="seg.dashOffset"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-xs font-black text-white font-mono leading-tight">{{ summary.total_study_time }}</span>
                  <span class="text-[9px] text-slate-400">{{ isKhmer ? 'សរុប' : 'Total' }}</span>
                </div>
              </div>

              <!-- Activity Legend -->
              <div class="space-y-2 text-xs flex-1">
                <div v-for="act in activityBreakdown" :key="act.id" class="flex items-center justify-between text-[10px]">
                  <div class="flex items-center gap-1.5 truncate">
                    <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: act.color }"></span>
                    <span class="text-slate-300 truncate">{{ isKhmer && act.name_kh ? act.name_kh : act.name }}</span>
                  </div>
                  <div class="text-right font-mono font-bold shrink-0 ml-1">
                    <span class="text-white">{{ act.percentage }}%</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="pt-2 border-t border-slate-800/60 grid grid-cols-2 gap-2 text-[10px] text-slate-400">
              <div>
                {{ isKhmer ? 'ច្រើនបំផុត:' : 'Top:' }}
                <strong class="text-purple-300">
                  {{ topActivityItem ? `${topActivityItem.duration}` : '0h 00m' }}
                </strong>
              </div>
              <div class="text-right">
                Quiz:
                <strong class="text-blue-300">
                  {{ quizActivityItem ? quizActivityItem.duration : '0h 00m' }}
                </strong>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT (4/12): Calendar Heatmap Card -->
        <div class="lg:col-span-4 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3 flex flex-col justify-between">
          <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
            <h3 class="text-sm font-bold text-white tracking-tight">
              {{ isKhmer ? 'ប្រតិទិនសកម្មភាពសិក្សា' : 'Calendar Heatmap' }}
            </h3>
            <span class="text-[11px] text-slate-400 font-mono">{{ isKhmer ? '៥ សប្តាហ៍ចុងក្រោយ' : 'Last 5 Weeks' }}</span>
          </div>

          <!-- Heatmap Grid -->
          <div class="space-y-2 pt-1">
            <!-- Day Header (Mon - Sun) -->
            <div class="grid grid-cols-8 text-center text-[10px] font-mono text-slate-400 font-bold">
              <span class="text-left text-[9px] text-slate-500">Wk</span>
              <span v-for="d in calendarHeatmap.days" :key="d">{{ d }}</span>
            </div>

            <!-- Heatmap Rows -->
            <div
              v-for="wk in calendarHeatmap.weeks"
              :key="wk.label"
              class="grid grid-cols-8 items-center gap-1 text-center"
            >
              <span class="text-[8px] font-mono text-slate-500 text-left truncate">{{ wk.label.split('-')[0] }}</span>
              <div
                v-for="cell in wk.cells"
                :key="cell.date"
                :title="`${cell.date}: ${cell.duration || (cell.hours + 'h')} (${cell.sessions} sessions)`"
                :class="[
                  getHeatmapColor(cell.level),
                  'w-6 h-6 rounded-md border flex items-center justify-center text-[8px] font-mono font-bold transition-transform hover:scale-125 cursor-pointer mx-auto'
                ]"
              >
                {{ cell.hours > 0 ? (cell.hours >= 1 ? Math.floor(cell.hours) : '•') : '' }}
              </div>
            </div>
          </div>

          <!-- Heatmap Scale Legend -->
          <div class="flex items-center justify-between pt-3 border-t border-slate-800/60 text-[9px] text-slate-400 font-mono">
            <span>0h</span>
            <div class="flex items-center gap-1.5">
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-emerald-950 border border-emerald-800"></span> &lt;1h</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-teal-700 border border-teal-600"></span> 1h+</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-teal-500 border border-teal-400"></span> 3h+</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-emerald-500 border border-emerald-400"></span> 5h+</span>
            </div>
          </div>
        </div>

      </div>

      <!-- ================= 4. LOWER ROW: RECENT ACTIVITY TABLE & ACTIVITY BREAKDOWN ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT (8/12): Recent Learning Activity Table -->
        <div class="lg:col-span-8 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
            <h2 class="text-base font-bold text-white tracking-tight">
              {{ isKhmer ? 'សកម្មភាពសិក្សាថ្មីៗ' : 'Recent Learning Activity' }}
            </h2>
            <span class="text-xs text-slate-400">
              {{ recentActivities.length }} {{ isKhmer ? 'សកម្មភាព' : (recentActivities.length === 1 ? 'Activity' : 'Activities') }}
            </span>
          </div>

          <!-- Empty State -->
          <div v-if="recentActivities.length === 0" class="py-10 text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-2xl mx-auto">
              📚
            </div>
            <div class="space-y-1">
              <p class="text-sm font-bold text-white">
                {{ isKhmer ? 'មិនទាន់មានសកម្មភាពសិក្សាថ្មីៗនៅឡើយទេ' : 'No recent learning activities yet' }}
              </p>
              <p class="text-xs text-slate-400 max-w-md mx-auto">
                {{ isKhmer ? 'នៅពេលអ្នកចុះឈ្មោះចូលរៀន មើលវីដេអូមេរៀន ឬធ្វើតេស្ត Quiz សកម្មភាពរបស់អ្នកនឹងបង្ហាញនៅទីនេះ។' : 'Once you enroll in a course, watch lessons, or submit quizzes, your activity history will appear here.' }}
              </p>
            </div>
            <div class="pt-1">
              <Link
                href="/student/browse"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-md transition-all"
              >
                <span>{{ isKhmer ? 'រុករកវគ្គសិក្សា' : 'Browse Courses' }}</span>
                <span>→</span>
              </Link>
            </div>
          </div>

          <!-- Activity Table -->
          <div v-else class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs text-slate-300">
              <thead class="text-[11px] uppercase font-bold text-slate-400 border-b border-slate-800/80">
                <tr>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'សកម្មភាព' : 'Activity' }}</th>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'វគ្គសិក្សា' : 'Course' }}</th>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'ប្រភេទ' : 'Type' }}</th>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'រយៈពេល' : 'Duration' }}</th>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'ពេលវេលា' : 'Time' }}</th>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'វឌ្ឍនភាព' : 'Progress' }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/40 font-medium">
                <tr
                  v-for="item in recentActivities"
                  :key="item.id"
                  class="hover:bg-slate-900/80 transition-colors group"
                >
                  <td class="py-3 px-3">
                    <div class="flex items-center gap-3">
                      <div
                        :class="[
                          item.icon_bg,
                          'w-7 h-7 rounded-lg bg-gradient-to-br flex items-center justify-center text-xs font-black shrink-0 text-white shadow-md'
                        ]"
                      >
                        {{ item.icon }}
                      </div>
                      <div>
                        <p class="font-bold text-white group-hover:text-purple-300 transition-colors">
                          {{ isKhmer && item.title_kh ? item.title_kh : item.title }}
                        </p>
                        <p class="text-[10px] text-slate-400">
                          {{ isKhmer && item.description_kh ? item.description_kh : item.description }}
                        </p>
                      </div>
                    </div>
                  </td>

                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded-md bg-slate-900 border border-slate-800 text-slate-300 text-[10px] font-medium">
                      {{ isKhmer && item.course_kh ? item.course_kh : item.course }}
                    </span>
                  </td>

                  <td class="py-3 px-3">
                    <span
                      class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                      :class="[
                        item.type === 'Lesson' ? 'bg-purple-500/20 text-purple-300 border-purple-500/30' :
                        item.type === 'Quiz' ? 'bg-blue-500/20 text-blue-300 border-blue-500/30' :
                        'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
                      ]"
                    >
                      {{ item.type }}
                    </span>
                  </td>

                  <td class="py-3 px-3 font-mono font-bold text-white">
                    {{ item.duration }}
                  </td>

                  <td class="py-3 px-3 text-[11px] text-slate-400">
                    {{ item.time }}
                  </td>

                  <td class="py-3 px-3 w-32">
                    <div class="flex items-center gap-2">
                      <div class="w-full h-2 rounded-full bg-slate-950 overflow-hidden border border-slate-800">
                        <div
                          :class="[item.progress_color, 'h-full rounded-full transition-all duration-500']"
                          :style="{ width: `${item.progress}%` }"
                        ></div>
                      </div>
                      <span class="text-xs font-mono font-bold text-white w-8 text-right">{{ item.progress }}%</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="pt-2 text-center border-t border-slate-800/60">
            <Link
              href="/student/my-courses/enrolled"
              class="text-xs font-bold text-slate-400 hover:text-purple-400 transition-colors inline-flex items-center gap-1"
            >
              <span>{{ isKhmer ? 'មើលវគ្គសិក្សាទាំងអស់ →' : 'Go to Enrolled Courses →' }}</span>
            </Link>
          </div>
        </div>

        <!-- RIGHT (4/12): Activity Breakdown Card -->
        <div class="lg:col-span-4 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4 flex flex-col justify-between">
          <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-white tracking-tight">
                {{ isKhmer ? 'ការបែងចែកសកម្មភាព' : 'Activity Breakdown' }}
              </h3>
              <span class="text-xs text-slate-400 font-mono">{{ selectedRange.toUpperCase() }}</span>
            </div>

            <div class="space-y-3">
              <div v-for="act in activityBreakdown" :key="act.id" class="space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: act.color }"></span>
                    <span class="text-slate-300 font-medium">{{ isKhmer && act.name_kh ? act.name_kh : act.name }}</span>
                  </div>
                  <div class="font-mono text-slate-400">
                    <strong class="text-white">{{ act.duration }}</strong> ({{ act.percentage }}%)
                  </div>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-950 overflow-hidden border border-slate-800">
                  <div
                    :class="[act.bg_class, 'h-full rounded-full transition-all duration-500']"
                    :style="{ width: `${act.percentage}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between text-xs">
            <span class="text-slate-400 font-medium">{{ isKhmer ? 'សរុប' : 'Total' }}</span>
            <span class="font-mono font-black text-white text-sm">{{ summary.total_study_time }}</span>
          </div>
        </div>

      </div>

      <!-- ================= 5. BOTTOM ROW: THIS WEEK OVERVIEW & STREAK / ACHIEVEMENTS ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT (8/12): This Week Overview Card -->
        <div class="lg:col-span-8 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
            <div>
              <h3 class="text-base font-bold text-white tracking-tight">
                {{ isKhmer ? 'ទិដ្ឋភាពសប្តាហ៍នេះ' : 'This Week Overview' }}
              </h3>
              <p class="text-[11px] text-slate-400">{{ thisWeekOverview.date_range }}</p>
            </div>

            <button
              type="button"
              @click="isReportModalOpen = true"
              class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-purple-500/30 text-purple-300 hover:text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
            >
              <span>📊</span>
              <span>{{ isKhmer ? 'សង្ខេបរបាយការណ៍' : 'Summary Report' }}</span>
            </button>
          </div>

          <!-- 6 Metrics Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-1">
            <div
              v-for="met in thisWeekOverview.metrics"
              :key="met.label"
              class="p-3.5 bg-slate-900/80 border border-slate-800/80 rounded-2xl space-y-1"
            >
              <p class="text-[11px] text-slate-400">{{ isKhmer && met.label_kh ? met.label_kh : met.label }}</p>
              <p class="text-xl font-black text-white font-mono">{{ met.value }}</p>
              <p
                :class="[
                  met.is_positive ? 'text-emerald-400' : 'text-rose-400',
                  'text-[10px] flex items-center gap-1 font-medium'
                ]"
              >
                <span>{{ met.trend }}</span>
              </p>
            </div>
          </div>
        </div>

        <!-- RIGHT (4/12): Stacked Widgets (Learning Streak & Achievements) -->
        <div class="lg:col-span-4 space-y-6">

          <!-- Widget 1: Learning Streak -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">
                {{ isKhmer ? 'ស្ទ្រីកសិក្សាជាប់គ្នា' : 'Learning Streak' }}
              </h3>
              <span class="text-xs text-slate-400 font-mono">{{ isKhmer ? '៧ ថ្ងៃចុងក្រោយ' : 'Last 7 Days' }}</span>
            </div>

            <div class="flex items-center justify-between gap-4 py-1">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-orange-500/20 border border-orange-500/40 text-orange-400 flex items-center justify-center text-2xl shadow-inner">
                  🔥
                </div>
                <div>
                  <p class="text-lg font-black text-white font-mono">{{ learningStreak.current_streak }} {{ isKhmer ? 'ថ្ងៃ' : 'Days' }}</p>
                  <p class="text-[10px] text-slate-400">{{ isKhmer ? 'ស្ទ្រីកបច្ចុប្បន្ន' : 'Current Streak' }}</p>
                </div>
              </div>

              <div class="text-right">
                <p class="text-xs font-bold text-white font-mono">{{ learningStreak.best_streak }} {{ isKhmer ? 'ថ្ងៃ' : 'Days' }}</p>
                <p class="text-[10px] text-slate-400">
                  {{ isKhmer ? 'ខ្ពស់បំផុត' : 'Best' }} ({{ learningStreak.best_streak_date }})
                </p>
              </div>
            </div>

            <!-- 7-Day Dynamic Checklist -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-800/60">
              <div
                v-for="d in learningStreak.days"
                :key="d.label"
                class="flex flex-col items-center gap-1"
                :title="`${d.label}: ${d.active ? 'Active' : 'No study recorded'}`"
              >
                <div
                  :class="[
                    d.active
                      ? 'bg-emerald-500 text-white shadow-sm'
                      : 'bg-slate-900 border border-slate-800 text-slate-600',
                    'w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold'
                  ]"
                >
                  {{ d.active ? '✓' : '·' }}
                </div>
                <span class="text-[9px] font-mono text-slate-400">{{ d.date }}</span>
              </div>
            </div>
          </div>

          <!-- Widget 2: Recent Achievements -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">
                {{ isKhmer ? 'សមិទ្ធផលដែលទទួលបាន' : 'Recent Achievements' }}
              </h3>
              <Link href="/student/progress/achievements" class="text-xs text-purple-400 font-bold hover:underline">
                {{ isKhmer ? 'មើលទាំងអស់' : 'View All' }}
              </Link>
            </div>

            <!-- Empty Achievements State -->
            <div v-if="recentAchievements.length === 0" class="py-4 text-center space-y-1.5">
              <p class="text-xs font-bold text-slate-300">
                {{ isKhmer ? 'មិនទាន់មានសមិទ្ធផលនៅឡើយទេ' : 'No achievements unlocked yet' }}
              </p>
              <p class="text-[11px] text-slate-400">
                {{ isKhmer ? 'បញ្ចប់មេរៀន និងធ្វើតេស្តដើម្បីទទួលបានស្លាកសញ្ញាដំបូងរបស់អ្នក។' : 'Complete lessons and quizzes to earn your first milestone badge.' }}
              </p>
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="ach in recentAchievements"
                :key="ach.id"
                class="p-2 rounded-2xl bg-slate-900/70 border border-slate-800/60 flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      ach.icon_bg,
                      'w-7 h-7 rounded-xl bg-gradient-to-br flex items-center justify-center text-sm shrink-0'
                    ]"
                  >
                    {{ ach.icon }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">{{ isKhmer && ach.title_kh ? ach.title_kh : ach.title }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ isKhmer && ach.description_kh ? ach.description_kh : ach.description }}</p>
                  </div>
                </div>
                <span class="text-[9px] text-slate-500 font-mono shrink-0">{{ ach.date.split(',')[0] }}</span>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- ================= MODAL: SUMMARY REPORT ================= -->
    <div
      v-if="isReportModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4"
      @click="isReportModalOpen = false"
    >
      <div
        class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative"
        @click.stop
      >
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <span class="text-2xl">📊</span>
            <div>
              <h3 class="text-base font-black text-white">
                {{ isKhmer ? 'របាយការណ៍សកម្មភាពសិក្សារបស់និស្សិត' : 'Student Learning Activity Report' }}
              </h3>
              <p class="text-[11px] text-purple-300">
                {{ summary.date_range_label }}
              </p>
            </div>
          </div>
          <button
            type="button"
            @click="isReportModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 bg-slate-950 p-4 rounded-2xl border border-slate-800 text-xs">
          <div class="grid grid-cols-2 gap-2.5 text-slate-300">
            <p>{{ isKhmer ? 'ម៉ោងសិក្សាសរុប:' : 'Total Study Time:' }} <strong class="text-white font-mono">{{ summary.total_study_time }}</strong></p>
            <p>{{ isKhmer ? 'មធ្យមភាគប្រចាំថ្ងៃ:' : 'Daily Average:' }} <strong class="text-white font-mono">{{ summary.daily_average }}</strong></p>
            <p>{{ isKhmer ? 'ចំនួនដងសិក្សា:' : 'Total Sessions:' }} <strong class="text-white font-mono">{{ summary.study_sessions }}</strong></p>
            <p>{{ isKhmer ? 'ថ្ងៃសកម្ម:' : 'Active Days:' }} <strong class="text-white font-mono">{{ summary.active_days }}</strong></p>
            <p>{{ isKhmer ? 'ស្ទ្រីកសិក្សា:' : 'Learning Streak:' }} <strong class="text-orange-400 font-mono">{{ learningStreak.current_streak }} {{ isKhmer ? 'ថ្ងៃ' : 'Days' }}</strong></p>
            <p>{{ isKhmer ? 'មេរៀនបានបញ្ចប់:' : 'Completed Lessons:' }} <strong class="text-emerald-400 font-mono">{{ summary.completed_lessons || 0 }}</strong></p>
          </div>
          <div class="pt-2 border-t border-slate-800 text-slate-400 text-[11px]">
            {{ isKhmer ? 'របាយការណ៍នេះបង្ហាញពីទិន្នន័យសិក្សាពិតប្រាកដពីមេរៀន និងការប្រឡងរបស់អ្នកក្នុងប្រព័ន្ធ E-LMS។' : 'This live summary reflects your verified lesson progress, study time, and quiz sessions recorded in E-LMS.' }}
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800 text-xs">
          <button
            type="button"
            @click="isReportModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold cursor-pointer"
          >
            {{ isKhmer ? 'បិទ' : 'Close' }}
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #0B0F19;
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #1E293B;
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #334155;
}
</style>
