<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface ActivityItem {
  id: number
  title: string
  description: string
  course: string
  type: string
  duration: string
  time: string
  progress: number
  icon: string
  icon_bg: string
  progress_color: string
}

interface ChartPoint {
  x: number
  y: number
  date: string
  label: string
  hours: number
  duration: string
}

const props = defineProps<{
  analytics?: {
    summary: {
      total_study_time: string
      total_seconds: number
      time_trend: string
      daily_average: string
      daily_avg_trend: string
      study_sessions: number
      sessions_trend: string
      active_days: string
      active_days_note: string
      longest_session: string
      longest_session_date: string
    }
    trend_chart: {
      granularity: string
      points: Array<{
        date: string
        label: string
        hours: number
        duration: string
      }>
    }
    activity_breakdown: Array<{
      id: number
      name: string
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
        date: string
        active: boolean
      }>
    }
    recent_achievements: Array<{
      id: number
      title: string
      description: string
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

// Default baseline data
const defaultSummary = {
  total_study_time: '48h 30m',
  total_seconds: 174600,
  time_trend: '+12% vs last 7 days',
  daily_average: '6h 55m',
  daily_avg_trend: '+18% vs last 7 days',
  study_sessions: 28,
  sessions_trend: '+8% vs last 7 days',
  active_days: '7 / 7',
  active_days_note: 'Perfect!',
  longest_session: '2h 45m',
  longest_session_date: 'Jun 1, 2025',
}

const defaultTrendPoints = [
  { date: 'May 20', label: 'May 20', hours: 2.0, duration: '2h 00m' },
  { date: 'May 21', label: 'May 21', hours: 3.5, duration: '3h 30m' },
  { date: 'May 22', label: 'May 22', hours: 4.8, duration: '4h 48m' },
  { date: 'May 23', label: 'May 23', hours: 4.2, duration: '4h 12m' },
  { date: 'May 24', label: 'May 24', hours: 3.8, duration: '3h 48m' },
  { date: 'May 25', label: 'May 25', hours: 6.0, duration: '6h 00m' },
  { date: 'May 26', label: 'May 26', hours: 5.8, duration: '5h 48m' },
  { date: 'May 27', label: 'May 27', hours: 4.5, duration: '4h 30m' },
  { date: 'May 28', label: 'May 28', hours: 5.2, duration: '5h 12m' },
  { date: 'May 29', label: 'May 29', hours: 6.5, duration: '6h 30m' },
  { date: 'May 30', label: 'May 30', hours: 5.0, duration: '5h 00m' },
  { date: 'May 31', label: 'May 31', hours: 9.25, duration: '9h 15m' },
  { date: 'Jun 1',  label: 'Jun 1',  hours: 7.2, duration: '7h 12m' },
  { date: 'Jun 2',  label: 'Jun 2',  hours: 5.5, duration: '5h 30m' },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const trendPoints = computed(() => props.analytics?.trend_chart?.points || defaultTrendPoints)
const activityBreakdown = computed(() => props.analytics?.activity_breakdown || [
  { id: 1, name: 'Watching Lessons', percentage: 60, duration: '29h 10m', color: '#8B5CF6', bg_class: 'bg-purple-500', code: '▶' },
  { id: 2, name: 'Doing Quizzes', percentage: 20, duration: '9h 40m', color: '#3B82F6', bg_class: 'bg-blue-500', code: '📝' },
  { id: 3, name: 'Practice & Exercises', percentage: 10, duration: '4h 50m', color: '#10B981', bg_class: 'bg-emerald-500', code: '💻' },
  { id: 4, name: 'Reading Materials', percentage: 6, duration: '2h 55m', color: '#F59E0B', bg_class: 'bg-amber-500', code: '📖' },
  { id: 5, name: 'Others', percentage: 4, duration: '1h 55m', color: '#EAB308', bg_class: 'bg-yellow-500', code: '⚡' },
])
const calendarHeatmap = computed(() => props.analytics?.calendar_heatmap || {
  days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
  weeks: [
    {
      label: 'May 20 - 26',
      cells: [
        { day: 'Mon', date: 'May 20', hours: 2.0, level: 1, sessions: 2 },
        { day: 'Tue', date: 'May 21', hours: 3.5, level: 2, sessions: 3 },
        { day: 'Wed', date: 'May 22', hours: 4.8, level: 2, sessions: 4 },
        { day: 'Thu', date: 'May 23', hours: 4.2, level: 2, sessions: 3 },
        { day: 'Fri', date: 'May 24', hours: 3.8, level: 2, sessions: 3 },
        { day: 'Sat', date: 'May 25', hours: 6.0, level: 3, sessions: 5 },
        { day: 'Sun', date: 'May 26', hours: 5.8, level: 3, sessions: 4 },
      ]
    },
    {
      label: 'May 27 - Jun 2',
      cells: [
        { day: 'Mon', date: 'May 27', hours: 4.5, level: 2, sessions: 3 },
        { day: 'Tue', date: 'May 28', hours: 5.2, level: 3, sessions: 4 },
        { day: 'Wed', date: 'May 29', hours: 6.5, level: 3, sessions: 5 },
        { day: 'Thu', date: 'May 30', hours: 5.0, level: 3, sessions: 4 },
        { day: 'Fri', date: 'May 31', hours: 9.25, level: 4, sessions: 6 },
        { day: 'Sat', date: 'Jun 1',  hours: 7.2, level: 4, sessions: 5 },
        { day: 'Sun', date: 'Jun 2',  hours: 5.5, level: 3, sessions: 4 },
      ]
    },
    {
      label: 'Jun 3 - 9',
      cells: [
        { day: 'Mon', date: 'Jun 3', hours: 2.0, level: 1, sessions: 2 },
        { day: 'Tue', date: 'Jun 4', hours: 3.0, level: 1, sessions: 2 },
        { day: 'Wed', date: 'Jun 5', hours: 1.5, level: 1, sessions: 1 },
        { day: 'Thu', date: 'Jun 6', hours: 4.0, level: 2, sessions: 3 },
        { day: 'Fri', date: 'Jun 7', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Sat', date: 'Jun 8', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Sun', date: 'Jun 9', hours: 0.0, level: 0, sessions: 0 },
      ]
    },
    {
      label: 'Jun 10 - 16',
      cells: [
        { day: 'Mon', date: 'Jun 10', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Tue', date: 'Jun 11', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Wed', date: 'Jun 12', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Thu', date: 'Jun 13', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Fri', date: 'Jun 14', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Sat', date: 'Jun 15', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Sun', date: 'Jun 16', hours: 0.0, level: 0, sessions: 0 },
      ]
    },
    {
      label: 'Jun 17 - 23',
      cells: [
        { day: 'Mon', date: 'Jun 17', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Tue', date: 'Jun 18', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Wed', date: 'Jun 19', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Thu', date: 'Jun 20', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Fri', date: 'Jun 21', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Sat', date: 'Jun 22', hours: 0.0, level: 0, sessions: 0 },
        { day: 'Sun', date: 'Jun 23', hours: 0.0, level: 0, sessions: 0 },
      ]
    }
  ]
})
const recentActivities = computed(() => props.analytics?.recent_activities || [
  { id: 1, title: 'JavaScript Functions - Part 2', description: 'Learn about function parameters and return', course: 'JavaScript Fundamentals', type: 'Lesson', duration: '42m', time: 'Today, 10:30 AM', progress: 85, icon: '▶', icon_bg: 'from-purple-500 to-indigo-600', progress_color: 'bg-purple-500' },
  { id: 2, title: 'JavaScript Functions Quiz', description: 'Test your knowledge on functions', course: 'JavaScript Fundamentals', type: 'Quiz', duration: '30m', time: 'Today, 09:15 AM', progress: 90, icon: '📝', icon_bg: 'from-blue-500 to-indigo-600', progress_color: 'bg-blue-500' },
  { id: 3, title: 'Practice: Array Methods', description: 'Solve array manipulation problems', course: 'JavaScript Fundamentals', type: 'Practice', duration: '55m', time: 'Yesterday, 04:20 PM', progress: 75, icon: '💻', icon_bg: 'from-emerald-500 to-teal-600', progress_color: 'bg-emerald-500' },
  { id: 4, title: 'React Components', description: 'Learn functional components', course: 'React.js Basics', type: 'Lesson', duration: '1h 05m', time: 'Yesterday, 02:30 PM', progress: 60, icon: '📙', icon_bg: 'from-purple-500 to-indigo-600', progress_color: 'bg-purple-500' },
  { id: 5, title: 'React Components Quiz', description: 'Check your understanding', course: 'React.js Basics', type: 'Quiz', duration: '25m', time: 'Yesterday, 01:10 PM', progress: 70, icon: '📝', icon_bg: 'from-amber-500 to-orange-600', progress_color: 'bg-blue-500' },
])
const thisWeekOverview = computed(() => props.analytics?.this_week_overview || {
  date_range: 'May 27 - Jun 2, 2025',
  metrics: [
    { label: 'Total Time', value: '48h 30m', trend: '+12% vs last week', is_positive: true },
    { label: 'Sessions', value: '28', trend: '+8% vs last week', is_positive: true },
    { label: 'Avg. Session', value: '1h 43m', trend: '+10% vs last week', is_positive: true },
    { label: 'Active Days', value: '7', trend: '0% vs last week', is_positive: true },
    { label: 'Completed Lessons', value: '18', trend: '+6% vs last week', is_positive: true },
    { label: 'Quizzes Taken', value: '12', trend: '+9% vs last week', is_positive: true },
  ]
})
const learningStreak = computed(() => props.analytics?.learning_streak || {
  current_streak: 17,
  best_streak: 21,
  best_streak_date: 'May 2025',
  days: [
    { label: 'May 27', date: '27', active: true },
    { label: 'May 28', date: '28', active: true },
    { label: 'May 29', date: '29', active: true },
    { label: 'May 30', date: '30', active: true },
    { label: 'May 31', date: '31', active: true },
    { label: 'Jun 1',  date: '1',  active: true },
    { label: 'Jun 2',  date: '2',  active: true },
  ]
})
const recentAchievements = computed(() => props.analytics?.recent_achievements || [
  { id: 1, title: 'Consistent Learner', description: 'Study 7 days in a row', date: 'May 31, 2025', icon: '🏆', icon_bg: 'from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30' },
  { id: 2, title: 'Time Master', description: 'Study more than 40 hours', date: 'May 30, 2025', icon: '⏱', icon_bg: 'from-purple-500/20 to-indigo-500/20 text-purple-400 border border-purple-500/30' },
  { id: 3, title: 'Early Bird', description: 'First session before 9 AM', date: 'May 28, 2025', icon: '🌅', icon_bg: 'from-amber-500/20 to-orange-500/20 text-amber-400 border border-amber-500/30' },
])

// Filter State
const selectedRange = ref(props.filters?.range || '7d')
const selectedGranularity = ref<'daily' | 'weekly' | 'monthly'>(
  (props.filters?.granularity as any) || 'daily'
)
const isReportModalOpen = ref(false)

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

const setGranularity = (granularity: 'daily' | 'weekly' | 'monthly') => {
  selectedGranularity.value = granularity
  router.get('/student/progress/learning-time', {
    range: selectedRange.value,
    granularity: granularity,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

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

  const maxHours = 10
  const stepX = usableW / Math.max(1, pts.length - 1)

  return pts.map((p, idx) => {
    const x = paddingX + idx * stepX
    const y = paddingY + (1 - (p.hours / maxHours)) * usableH
    return { ...p, x, y }
  })
})

const chartSvgPath = computed<string>(() => {
  const pts = chartSvgPoints.value
  if (!pts || pts.length === 0) return ''
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
  const baseLine = 150 - 10
  let area = chartSvgPath.value
  area += ` L ${pts[pts.length - 1].x},${baseLine} L ${pts[0].x},${baseLine} Z`
  return area
})

// Heatmap level color helper
const getHeatmapColor = (level: number) => {
  switch (level) {
    case 4: return 'bg-emerald-500 border-emerald-400 text-white' // > 7h
    case 3: return 'bg-teal-500 border-teal-400 text-white'       // > 5h
    case 2: return 'bg-teal-700/80 border-teal-600 text-teal-100' // > 3h
    case 1: return 'bg-emerald-950 border-emerald-800 text-emerald-300' // > 1h
    default: return 'bg-slate-900 border-slate-800 text-slate-600'     // 0
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
            <span>Learning Activity</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">⏱️</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            Track your learning time, activities and habits to stay consistent and achieve your goals.
          </p>
        </div>

        <!-- Date Range Filter & Quick Range Pills -->
        <div class="flex flex-wrap items-center gap-2.5 self-start md:self-auto">
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900/90 border border-slate-800 text-xs font-semibold text-slate-300 shadow-sm">
            <span>📅</span>
            <span>May 20 - Jun 2, 2025</span>
            <span class="text-[10px] text-slate-500">▾</span>
          </div>

          <div class="flex items-center bg-slate-900/90 border border-slate-800 rounded-xl p-1 shadow-sm">
            <button
              v-for="r in ['7D', '30D', '3M', '6M', '1Y']"
              :key="r"
              @click="setRange(r.toLowerCase())"
              :class="[
                selectedRange.toUpperCase() === r
                  ? 'bg-purple-600 text-white shadow-md shadow-purple-900/40'
                  : 'text-slate-400 hover:text-slate-200',
                'px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer'
              ]"
            >
              {{ r }}
            </button>
          </div>
        </div>
      </div>

      <!-- ================= 2. 5 SUMMARY CARDS ================= -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- CARD 1: Total Study Time -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-purple-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">Total Study Time</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.total_study_time }}</p>
            <p class="text-[11px] text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>↑</span>
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
            <p class="text-[11px] text-slate-400 font-medium">Daily Average</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.daily_average }}</p>
            <p class="text-[11px] text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>↑</span>
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
            <p class="text-[11px] text-slate-400 font-medium">Study Sessions</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.study_sessions }}</p>
            <p class="text-[11px] text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>↑</span>
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
            <p class="text-[11px] text-slate-400 font-medium">Active Days</p>
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

        <!-- CARD 5: Longest Session -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-amber-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">Longest Session</p>
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
              <h3 class="text-sm font-bold text-white tracking-tight">Study Time Over Time</h3>
              
              <!-- Daily / Weekly / Monthly Pills -->
              <div class="flex items-center bg-slate-900 border border-slate-800 rounded-lg p-0.5">
                <button
                  v-for="g in (['daily', 'weekly', 'monthly'] as const)"
                  :key="g"
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

            <!-- Line Chart SVG -->
            <div class="relative w-full h-44 pt-2">
              <!-- Y-Axis labels -->
              <div class="absolute left-0 inset-y-2 flex flex-col justify-between text-[9px] text-slate-500 font-mono select-none pointer-events-none">
                <span>10h</span>
                <span>7.5h</span>
                <span>5h</span>
                <span>2.5h</span>
                <span>0h</span>
              </div>

              <div class="ml-7 h-full relative">
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
                    class="fill-slate-950 stroke-purple-400 stroke-2 hover:r-5 hover:stroke-white transition-all cursor-pointer"
                  />
                </svg>

                <!-- Tooltip Callout for May 31 (Peak: 9h 15m) -->
                <div class="absolute left-[78%] top-[12%] -translate-x-1/2 bg-slate-900/95 border border-purple-500/70 rounded-lg px-2.5 py-1 shadow-xl text-[10px] text-slate-200 pointer-events-none whitespace-nowrap z-10">
                  <p class="font-mono text-center"><strong class="text-white">May 31</strong></p>
                  <p class="text-purple-400 font-bold font-mono text-center">9h 15m</p>
                </div>

                <!-- Bottom X-Axis labels -->
                <div class="w-full flex justify-between text-[8px] sm:text-[9px] text-slate-500 font-mono mt-1 overflow-hidden">
                  <span>May 20</span>
                  <span>May 22</span>
                  <span>May 24</span>
                  <span>May 26</span>
                  <span>May 28</span>
                  <span>May 30</span>
                  <span>Jun 1</span>
                </div>
              </div>

            </div>
          </div>

          <!-- Card B (5/12 of 8/12): Study Time by Activity -->
          <div class="md:col-span-5 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <h3 class="text-sm font-bold text-white tracking-tight border-b border-slate-800/60 pb-3">
              Study Time by Activity
            </h3>

            <div class="flex items-center justify-between gap-3 py-1">
              <!-- Donut Chart SVG -->
              <div class="relative w-28 h-28 flex items-center justify-center shrink-0">
                <svg class="w-28 h-28 -rotate-90 transform" viewBox="0 0 42 42">
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#1E293B" stroke-width="4.5" />
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#8B5CF6" stroke-width="4.5" stroke-dasharray="60, 40" stroke-dashoffset="0" />
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#3B82F6" stroke-width="4.5" stroke-dasharray="20, 80" stroke-dashoffset="-60" />
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#10B981" stroke-width="4.5" stroke-dasharray="10, 90" stroke-dashoffset="-80" />
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#F59E0B" stroke-width="4.5" stroke-dasharray="6, 94" stroke-dashoffset="-90" />
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#EAB308" stroke-width="4.5" stroke-dasharray="4, 96" stroke-dashoffset="-96" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-xs font-black text-white font-mono leading-tight">48h 30m</span>
                  <span class="text-[9px] text-slate-400">Total</span>
                </div>
              </div>

              <!-- Activity Legend -->
              <div class="space-y-1.5 text-xs flex-1">
                <div v-for="act in activityBreakdown" :key="act.id" class="flex items-center justify-between text-[10px]">
                  <div class="flex items-center gap-1.5 truncate">
                    <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: act.color }"></span>
                    <span class="text-slate-300 truncate">{{ act.name }}</span>
                  </div>
                  <div class="text-right font-mono font-bold shrink-0 ml-1">
                    <span class="text-white">{{ act.percentage }}%</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="pt-2 border-t border-slate-800/60 grid grid-cols-2 gap-2 text-[10px] text-slate-400">
              <div>Top: <strong class="text-purple-300">Lessons (29h 10m)</strong></div>
              <div class="text-right">Quiz: <strong class="text-blue-300">9h 40m</strong></div>
            </div>
          </div>

        </div>

        <!-- RIGHT (4/12): Calendar Heatmap Card -->
        <div class="lg:col-span-4 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
          <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
            <div class="flex items-center gap-1.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Calendar Heatmap</h3>
              <span class="text-[11px] text-slate-400 cursor-pointer">ⓘ</span>
            </div>
            <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">More</span>
          </div>

          <!-- Heatmap Grid -->
          <div class="space-y-2 pt-1">
            <!-- Day Header (Mon - Sun) -->
            <div class="grid grid-cols-8 text-center text-[10px] font-mono text-slate-400 font-bold">
              <span class="text-left text-[9px] text-slate-500">Week</span>
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
                :title="`${cell.date}: ${cell.hours} hours (${cell.sessions} sessions)`"
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
            <span>0</span>
            <div class="flex items-center gap-1.5">
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-emerald-950 border border-emerald-800"></span> &gt; 1h</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-teal-700 border border-teal-600"></span> &gt; 3h</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-teal-500 border border-teal-400"></span> &gt; 5h</span>
              <span class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-emerald-500 border border-emerald-400"></span> &gt; 7h</span>
            </div>
          </div>
        </div>

      </div>

      <!-- ================= 4. LOWER ROW: RECENT ACTIVITY TABLE & ACTIVITY BREAKDOWN ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT (8/12): Recent Learning Activity Table -->
        <div class="lg:col-span-8 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
            <h2 class="text-base font-bold text-white tracking-tight">Recent Learning Activity</h2>
            <span class="text-xs text-slate-400">Last 5 Activities</span>
          </div>

          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs text-slate-300">
              <thead class="text-[11px] uppercase font-bold text-slate-400 border-b border-slate-800/80">
                <tr>
                  <th class="py-2.5 px-3">Activity</th>
                  <th class="py-2.5 px-3">Course / Topic</th>
                  <th class="py-2.5 px-3">Type</th>
                  <th class="py-2.5 px-3">Duration</th>
                  <th class="py-2.5 px-3">Time</th>
                  <th class="py-2.5 px-3">Progress</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/40 font-medium">
                <tr
                  v-for="item in recentActivities"
                  :key="item.id"
                  class="hover:bg-slate-900/80 transition-colors group"
                >
                  <!-- Activity Title & Icon -->
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
                        <p class="font-bold text-white group-hover:text-purple-300 transition-colors">{{ item.title }}</p>
                        <p class="text-[10px] text-slate-400">{{ item.description }}</p>
                      </div>
                    </div>
                  </td>

                  <!-- Course / Topic -->
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded-md bg-slate-900 border border-slate-800 text-slate-300 text-[10px] font-medium">
                      {{ item.course }}
                    </span>
                  </td>

                  <!-- Type Badge -->
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

                  <!-- Duration -->
                  <td class="py-3 px-3 font-mono font-bold text-white">
                    {{ item.duration }}
                  </td>

                  <!-- Time -->
                  <td class="py-3 px-3 text-[11px] text-slate-400">
                    {{ item.time }}
                  </td>

                  <!-- Progress -->
                  <td class="py-3 px-3 w-32">
                    <div class="flex items-center gap-2">
                      <div class="w-full h-2 rounded-full bg-slate-950 overflow-hidden border border-slate-800">
                        <div
                          :class="[item.progress_color, 'h-full rounded-full transition-all duration-500']"
                          :style="{ width: `${item.progress}%` }"
                        ></div>
                      </div>
                      <span class="text-xs font-mono font-bold text-white w-7 text-right">{{ item.progress }}%</span>
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
              <span>View All Activities &gt;</span>
            </Link>
          </div>
        </div>

        <!-- RIGHT (4/12): Activity Breakdown Card -->
        <div class="lg:col-span-4 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
            <div class="flex items-center gap-1.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Activity Breakdown</h3>
              <span class="text-[11px] text-slate-400 cursor-pointer">ⓘ</span>
            </div>
            <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">More</span>
          </div>

          <!-- Progress Bars List -->
          <div class="space-y-3">
            <div v-for="act in activityBreakdown" :key="act.id" class="space-y-1.5 text-xs">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: act.color }"></span>
                  <span class="text-slate-300 font-medium">{{ act.name }}</span>
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

          <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between text-xs">
            <span class="text-slate-400 font-medium">Total</span>
            <span class="font-mono font-black text-white text-sm">48h 30m</span>
          </div>
        </div>

      </div>

      <!-- ================= 5. BOTTOM ROW: THIS WEEK OVERVIEW & STREAK / ACHIEVEMENTS ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT (8/12): This Week Overview Card -->
        <div class="lg:col-span-8 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
            <div>
              <h3 class="text-base font-bold text-white tracking-tight">This Week Overview</h3>
              <p class="text-[11px] text-slate-400">{{ thisWeekOverview.date_range }}</p>
            </div>

            <button
              @click="isReportModalOpen = true"
              class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-purple-500/30 text-purple-300 hover:text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 cursor-pointer"
            >
              <span>📥</span>
              <span>Download Report</span>
            </button>
          </div>

          <!-- 6 Metrics Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-1">
            <div
              v-for="met in thisWeekOverview.metrics"
              :key="met.label"
              class="p-3.5 bg-slate-900/80 border border-slate-800/80 rounded-2xl space-y-1"
            >
              <p class="text-[11px] text-slate-400">{{ met.label }}</p>
              <p class="text-xl font-black text-white font-mono">{{ met.value }}</p>
              <p class="text-[10px] text-emerald-400 flex items-center gap-1 font-medium">
                <span>↑</span>
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
              <div class="flex items-center gap-1.5">
                <h3 class="text-sm font-bold text-white tracking-tight">Learning Streak</h3>
                <span class="text-[11px] text-slate-400 cursor-pointer">ⓘ</span>
              </div>
              <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">More</span>
            </div>

            <div class="flex items-center justify-between gap-4 py-1">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-orange-500/20 border border-orange-500/40 text-orange-400 flex items-center justify-center text-2xl shadow-inner animate-pulse">
                  🔥
                </div>
                <div>
                  <p class="text-lg font-black text-white font-mono">{{ learningStreak.current_streak }} Days</p>
                  <p class="text-[10px] text-slate-400">Current Streak</p>
                </div>
              </div>

              <div class="text-right">
                <p class="text-xs font-bold text-white font-mono">{{ learningStreak.best_streak }} Days</p>
                <p class="text-[10px] text-slate-400">Best Streak ({{ learningStreak.best_streak_date }})</p>
              </div>
            </div>

            <!-- 7-Day Checklist -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-800/60">
              <div
                v-for="d in learningStreak.days"
                :key="d.label"
                class="flex flex-col items-center gap-1"
              >
                <div class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold shadow-sm">
                  ✓
                </div>
                <span class="text-[9px] font-mono text-slate-400">{{ d.date }}</span>
              </div>
            </div>
          </div>

          <!-- Widget 2: Recent Achievements -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Recent Achievements</h3>
              <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">More</span>
            </div>

            <div class="space-y-2">
              <div
                v-for="ach in recentAchievements"
                :key="ach.id"
                class="p-2 rounded-2xl bg-slate-900/70 border border-slate-800/60 flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      ach.icon_bg,
                      'w-7 h-7 rounded-xl flex items-center justify-center text-sm shrink-0'
                    ]"
                  >
                    {{ ach.icon }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">{{ ach.title }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ ach.description }}</p>
                  </div>
                </div>
                <span class="text-[9px] text-slate-500 font-mono shrink-0">{{ ach.date.split(',')[0] }}</span>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- ================= MODAL: DOWNLOAD REPORT ================= -->
    <div
      v-if="isReportModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <span class="text-2xl">📊</span>
            <div>
              <h3 class="text-base font-black text-white">Student Learning Activity Report</h3>
              <p class="text-[11px] text-purple-300">Generated Activity &amp; Time Summary (May 20 - Jun 2, 2025)</p>
            </div>
          </div>
          <button
            @click="isReportModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 bg-slate-950 p-4 rounded-2xl border border-slate-800 text-xs">
          <div class="grid grid-cols-2 gap-2 text-slate-300">
            <p>Total Study Time: <strong class="text-white">48h 30m</strong></p>
            <p>Daily Average: <strong class="text-white">6h 55m</strong></p>
            <p>Total Sessions: <strong class="text-white">28 sessions</strong></p>
            <p>Active Days: <strong class="text-white">7 / 7 Days</strong></p>
            <p>Learning Streak: <strong class="text-orange-400">17 Days</strong></p>
            <p>Completed Lessons: <strong class="text-emerald-400">18 Lessons</strong></p>
          </div>
          <div class="pt-2 border-t border-slate-800 text-slate-400 text-[11px]">
            This verified report provides complete analytics of course lectures, quizzes, and practice exercises completed in E-LMS.
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isReportModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Close
          </button>
          <button
            @click="isReportModalOpen = false"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5"
          >
            <span>📥</span>
            <span>Download PDF</span>
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
