<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

// Props passed from ProgressController
const props = defineProps<{
  analytics?: {
    summary: {
      average_score: number
      score_status: string
      score_trend: string
      score_trend_direction?: string
      quizzes_taken: number
      quizzes_status: string
      quizzes_trend: string
      quizzes_trend_direction?: string
      highest_score: { score: number; quiz: string; course: string; date: string }
      lowest_score: { score: number; quiz: string; course: string; date: string }
      accuracy: number
      accuracy_status: string
      accuracy_trend: string
      accuracy_trend_direction?: string
    }
    weekly_summary: {
      date_range: string
      avg_score: number
      score_change: string
      quizzes_taken: number
      quizzes_change: string
      correct_answers: number
      accuracy_change: string
      time_spent: string
      time_change: string
    }
    trend_chart: {
      labels: string[]
      scores: number[]
      points: Array<{
        date: string
        score: number
        quizzes: number
        highest: number
        lowest: number
        time: string
      }>
    }
    difficulty: {
      average: number
      easy: number
      medium: number
      hard: number
      expert: number
    }
    weak_topics: Array<{
      id: number
      title: string
      code?: string
      score: number
      color: string
      recommendation?: string
    }>
    recent_results: Array<{
      id: number
      quiz_id: number
      title: string
      course: string
      score: number
      accuracy: number
      time: string
      date: string
      result: string
      badge: string
      icon?: string
      icon_bg?: string
    }>
    recent_quizzes: Array<{
      id: number
      quiz_id: number
      title: string
      course: string
      score: number
      date_badge: string
      badge_color: string
    }>
    topics: Array<{
      name: string
      code: string
      score: number
      color: string
      icon_bg?: string
    }>
    ai_insight: {
      primary: string
      secondary: string
      study_plan?: Array<{
        day: string
        title: string
        duration: string
        type: string
      }>
    }
  }
  filters?: {
    period: string
    granularity: string
  }
}>()

// Default fallback data if props are missing
const defaultSummary = {
  average_score: 72,
  score_status: 'Good Performance',
  score_trend: '8% this week',
  quizzes_taken: 24,
  quizzes_status: 'Total Quizzes',
  quizzes_trend: '6 this week',
  highest_score: { score: 95, quiz: 'JavaScript Advanced Quiz', course: 'JavaScript Advanced', date: 'May 20, 2025' },
  lowest_score: { score: 35, quiz: 'SQL JOIN Operations Quiz', course: 'Database Systems', date: 'May 18, 2025' },
  accuracy: 68,
  accuracy_status: 'Average Accuracy',
  accuracy_trend: '10% this week',
}

const defaultWeeklySummary = {
  date_range: 'May 26 - Jun 1, 2025',
  avg_score: 75,
  score_change: '+6%',
  quizzes_taken: 6,
  quizzes_change: '+2',
  correct_answers: 78,
  accuracy_change: '+8%',
  time_spent: '3h 45m',
  time_change: '+45m',
}

const defaultTrendPoints = [
  { date: 'May 5', score: 45, quizzes: 1, highest: 55, lowest: 35, time: '20m' },
  { date: 'May 7', score: 52, quizzes: 2, highest: 60, lowest: 44, time: '35m' },
  { date: 'May 9', score: 48, quizzes: 1, highest: 48, lowest: 48, time: '18m' },
  { date: 'May 11', score: 61, quizzes: 3, highest: 75, lowest: 50, time: '50m' },
  { date: 'May 13', score: 58, quizzes: 2, highest: 65, lowest: 50, time: '30m' },
  { date: 'May 15', score: 67, quizzes: 2, highest: 72, lowest: 62, time: '38m' },
  { date: 'May 17', score: 60, quizzes: 2, highest: 68, lowest: 52, time: '40m' },
  { date: 'May 19', score: 74, quizzes: 3, highest: 85, lowest: 65, time: '55m' },
  { date: 'May 21', score: 70, quizzes: 2, highest: 78, lowest: 62, time: '42m' },
  { date: 'May 23', score: 78, quizzes: 3, highest: 88, lowest: 70, time: '60m' },
  { date: 'May 25', score: 82, quizzes: 2, highest: 90, lowest: 74, time: '45m' },
  { date: 'May 27', score: 80, quizzes: 1, highest: 80, lowest: 80, time: '25m' },
  { date: 'May 29', score: 85, quizzes: 2, highest: 92, lowest: 78, time: '40m' },
  { date: 'May 31', score: 88, quizzes: 3, highest: 95, lowest: 82, time: '65m' },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const weeklySummary = computed(() => props.analytics?.weekly_summary || defaultWeeklySummary)
const trendPoints = computed(() => props.analytics?.trend_chart?.points || defaultTrendPoints)
const difficulty = computed(() => props.analytics?.difficulty || { average: 72, easy: 84, medium: 69, hard: 58, expert: 45 })
const weakTopics = computed(() => props.analytics?.weak_topics || [
  { id: 1, title: 'JavaScript Scope', code: 'JS', score: 35, color: 'rose', recommendation: 'Review Scope Chain & Closures' },
  { id: 2, title: 'Function Parameters', code: '{ }', score: 28, color: 'rose', recommendation: 'Practice Default & Rest Params' },
  { id: 3, title: 'DOM Manipulation', code: '🖥️', score: 40, color: 'blue', recommendation: 'Master Event Delegation APIs' },
  { id: 4, title: 'SQL JOINS', code: '🗄️', score: 42, color: 'blue', recommendation: 'Drill INNER, LEFT & RIGHT JOINs' },
  { id: 5, title: 'Array Methods', code: '[ ]', score: 50, color: 'purple', recommendation: 'Practice Map, Filter, Reduce' },
])
const recentResults = computed(() => props.analytics?.recent_results || [
  { id: 101, quiz_id: 1, title: 'JavaScript Advanced Quiz', course: 'JavaScript Advanced', score: 95, accuracy: 92, time: '45m', date: 'May 20, 2025', result: 'Excellent', badge: 'emerald', icon: 'JS', icon_bg: 'from-amber-400 to-amber-500 text-slate-950' },
  { id: 102, quiz_id: 2, title: 'React Components Quiz', course: 'React.js Fundamentals', score: 70, accuracy: 68, time: '30m', date: 'May 20, 2025', result: 'Good', badge: 'blue', icon: '⚛️', icon_bg: 'from-cyan-400 to-blue-500 text-white' },
  { id: 103, quiz_id: 3, title: 'CSS Flexbox Quiz', course: 'HTML & CSS', score: 80, accuracy: 75, time: '25m', date: 'May 19, 2025', result: 'Good', badge: 'blue', icon: '🎨', icon_bg: 'from-blue-400 to-indigo-500 text-white' },
  { id: 104, quiz_id: 4, title: 'SQL JOIN Operations Quiz', course: 'Database Systems', score: 35, accuracy: 40, time: '40m', date: 'May 18, 2025', result: 'Poor', badge: 'rose', icon: '🗄️', icon_bg: 'from-rose-400 to-red-500 text-white' },
  { id: 105, quiz_id: 5, title: 'JavaScript Functions Quiz', course: 'JavaScript Fundamentals', score: 60, accuracy: 58, time: '35m', date: 'May 17, 2025', result: 'Average', badge: 'amber', icon: 'JS', icon_bg: 'from-amber-400 to-amber-500 text-slate-950' },
])
const recentQuizzes = computed(() => props.analytics?.recent_quizzes || [
  { id: 101, quiz_id: 1, title: 'JavaScript Advanced Quiz', course: 'JavaScript Advanced', score: 95, date_badge: 'MAY 20', badge_color: 'text-emerald-400' },
  { id: 102, quiz_id: 2, title: 'React Components Quiz', course: 'React.js Fundamentals', score: 70, date_badge: 'MAY 20', badge_color: 'text-blue-400' },
  { id: 103, quiz_id: 3, title: 'CSS Flexbox Quiz', course: 'HTML & CSS', score: 80, date_badge: 'MAY 19', badge_color: 'text-emerald-400' },
  { id: 104, quiz_id: 4, title: 'SQL JOIN Operations Quiz', course: 'Database Systems', score: 35, date_badge: 'MAY 18', badge_color: 'text-rose-400' },
])
const topics = computed(() => props.analytics?.topics || [
  ['JavaScript', 'JS', 75, '#10B981', 'bg-amber-400 text-slate-950'],
  ['HTML & CSS', '5', 85, '#06B6D4', 'bg-orange-500 text-white'],
  ['React.js', '⚛️', 60, '#F59E0B', 'bg-cyan-500 text-white'],
  ['Node.js', 'node', 55, '#F97316', 'bg-emerald-600 text-white'],
  ['Database', '🗄️', 40, '#EF4444', 'bg-slate-700 text-white'],
  ['Others', '🌐', 70, '#3B82F6', 'bg-blue-600 text-white'],
].map(t => ({ name: t[0] as string, code: t[1] as string, score: t[2] as number, color: t[3] as string, icon_bg: t[4] as string })))

const aiInsight = computed(() => props.analytics?.ai_insight || {
  primary: 'You perform best in JavaScript!',
  secondary: 'Focus on SQL and Database topics to improve your overall score.',
  study_plan: [
    { day: 'Monday', title: 'Review JavaScript Scope & Closures', duration: '30 minutes', type: 'Theory & Code Drill' },
    { day: 'Tuesday', title: 'Practice Function Parameters & Defaults', duration: '20 questions', type: 'Interactive Practice' },
    { day: 'Wednesday', title: 'Review SQL JOINS & Subqueries', duration: '40 minutes', type: 'Database Lab' },
    { day: 'Thursday', title: 'AI Adaptive Practice Quiz', duration: '25 questions', type: 'AI Simulation' },
    { day: 'Friday', title: 'Retake SQL & Scope Weak Topic Quiz', duration: '30 minutes', type: 'Assessment' },
  ]
})

// Filter states
const selectedPeriod = ref(props.filters?.period || 'this_month')
const selectedGranularity = ref(props.filters?.granularity || 'daily')
const searchResultQuery = ref('')

// Interactive Tooltip on Line Chart
const hoveredPoint = ref<any | null>(null)
const activeTooltipX = ref(0)
const activeTooltipY = ref(0)

const onPointHover = (pt: any, index: number, event: MouseEvent) => {
  hoveredPoint.value = pt
  const target = event.currentTarget as HTMLElement
  if (target) {
    const rect = target.getBoundingClientRect()
    activeTooltipX.value = rect.left + rect.width / 2
    activeTooltipY.value = rect.top - 10
  }
}

const onPointLeave = () => {
  hoveredPoint.value = null
}

// Modals State
const isStudyPlanModalOpen = ref(false)
const isReportModalOpen = ref(false)
const isAttemptDetailModalOpen = ref(false)
const selectedAttempt = ref<any | null>(null)
const selectedTopicDrill = ref<any | null>(null)
const isTopicDrillModalOpen = ref(false)

const openAttemptDetail = (row: any) => {
  selectedAttempt.value = row
  isAttemptDetailModalOpen.value = true
}

const openTopicDrill = (topic: any) => {
  selectedTopicDrill.value = topic
  isTopicDrillModalOpen.value = true
}

interface ChartPoint {
  x: number
  y: number
  date: string
  score: number
  quizzes: number
  highest: number
  lowest: number
  time: string
}

// SVG Line Chart Calculation
const chartSvgPoints = computed<ChartPoint[]>(() => {
  const pts = trendPoints.value
  if (!pts || pts.length === 0) return []
  const width = 640
  const height = 180
  const paddingX = 20
  const paddingY = 20
  const usableW = width - paddingX * 2
  const usableH = height - paddingY * 2

  const stepX = usableW / Math.max(1, pts.length - 1)

  return pts.map((p, idx) => {
    const x = paddingX + idx * stepX
    const y = paddingY + (1 - (p.score / 100)) * usableH
    return { ...p, x, y }
  })
})

const chartSvgPath = computed<string>(() => {
  const pts = chartSvgPoints.value
  if (!pts || pts.length === 0) return ''
  
  // Smooth cubic spline
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
  const baseLine = 180 - 15
  let area = chartSvgPath.value
  area += ` L ${pts[pts.length - 1].x},${baseLine} L ${pts[0].x},${baseLine} Z`
  return area
})

// Filter recent results
const filteredRecentResults = computed(() => {
  if (!searchResultQuery.value.trim()) return recentResults.value
  const q = searchResultQuery.value.toLowerCase()
  return recentResults.value.filter(r => 
    r.title.toLowerCase().includes(q) || 
    r.course.toLowerCase().includes(q) ||
    r.result.toLowerCase().includes(q)
  )
})

const onFilterChange = () => {
  router.get('/student/progress/weekly', {
    period: selectedPeriod.value,
    granularity: selectedGranularity.value,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}
</script>

<template>
  <StudentLayout title="Quiz Performance — Progress & Analytics">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
              <span>Quiz Performance</span>
              <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">📈</span>
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            Analyze your quiz performance and track your improvement over time.
          </p>
        </div>

        <!-- Action shortcut: View Detailed Full Report -->
        <button
          @click="isReportModalOpen = true"
          class="px-4 py-2 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-purple-500/40 text-slate-300 hover:text-white text-xs font-bold transition-all shadow-md self-start sm:self-auto flex items-center gap-2 group"
        >
          <span class="text-purple-400 group-hover:rotate-12 transition-transform">📊</span>
          <span>Full Analytics Export</span>
        </button>
      </div>

      <!-- ================= 2. SUMMARY CARDS (5 TOP CARDS) ================= -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- CARD 1: Average Score -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-purple-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">Average Score</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.average_score }}%</p>
            <p class="text-[11px] font-bold text-emerald-400">{{ summary.score_status }}</p>
            <p class="text-[11px] text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>↑</span>
              <span>{{ summary.score_trend }}</span>
            </p>
          </div>
          <div class="relative w-14 h-14 flex items-center justify-center shrink-0">
            <!-- Circular gauge SVG -->
            <svg class="w-14 h-14 -rotate-90 transform" viewBox="0 0 36 36">
              <path
                class="text-slate-800"
                stroke-width="3"
                stroke="currentColor"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />
              <path
                class="text-purple-500 transition-all duration-1000"
                stroke-dasharray="72, 100"
                stroke-width="3.2"
                stroke-linecap="round"
                stroke="currentColor"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-purple-400 text-xs">
              📈
            </span>
          </div>
        </div>

        <!-- CARD 2: Quizzes Taken -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-blue-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">Quizzes Taken</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.quizzes_taken }}</p>
            <p class="text-[11px] text-slate-400 font-medium">{{ summary.quizzes_status }}</p>
            <p class="text-[11px] text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>↑</span>
              <span>{{ summary.quizzes_trend }}</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-full bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-400 text-lg shadow-inner group-hover:scale-110 transition-transform">
            📑
          </div>
        </div>

        <!-- CARD 3: Highest Score -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-amber-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10 min-w-0 pr-2">
            <p class="text-[11px] text-slate-400 font-medium">Highest Score</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.highest_score.score }}%</p>
            <p class="text-[11px] text-slate-300 font-medium truncate" :title="summary.highest_score.quiz">{{ summary.highest_score.quiz }}</p>
            <p class="text-[10px] text-slate-500 font-medium">{{ summary.highest_score.date }}</p>
          </div>
          <div class="w-12 h-12 rounded-full bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🛡️
          </div>
        </div>

        <!-- CARD 4: Lowest Score -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-rose-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10 min-w-0 pr-2">
            <p class="text-[11px] text-slate-400 font-medium">Lowest Score</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.lowest_score.score }}%</p>
            <p class="text-[11px] text-slate-300 font-medium truncate" :title="summary.lowest_score.quiz">{{ summary.lowest_score.quiz }}</p>
            <p class="text-[10px] text-slate-500 font-medium">{{ summary.lowest_score.date }}</p>
          </div>
          <div class="w-12 h-12 rounded-full bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400 text-lg shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📉
          </div>
        </div>

        <!-- CARD 5: Accuracy -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-emerald-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">Accuracy</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.accuracy }}%</p>
            <p class="text-[11px] text-slate-400 font-medium">{{ summary.accuracy_status }}</p>
            <p class="text-[11px] text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>↑</span>
              <span>{{ summary.accuracy_trend }}</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-full bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg shadow-inner group-hover:scale-110 transition-transform">
            🎯
          </div>
        </div>

      </div>

      <!-- ================= 3. MIDDLE ROW: QUIZ PERFORMANCE OVER TIME & WEEKLY SUMMARY ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT (2/3): Quiz Performance Over Time Chart -->
        <div class="lg:col-span-2 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4 relative">
          
          <!-- Chart Header & Filters -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/60 pb-3">
            <h2 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
              <span>Quiz Performance Over Time</span>
            </h2>

            <div class="flex items-center gap-2">
              <select
                v-model="selectedPeriod"
                @change="onFilterChange"
                class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-300 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
              >
                <option value="this_month">This Month</option>
                <option value="this_week">This Week</option>
                <option value="last_3_months">Last 3 Months</option>
                <option value="last_6_months">Last 6 Months</option>
                <option value="all_time">All Time</option>
              </select>

              <select
                v-model="selectedGranularity"
                @change="onFilterChange"
                class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-300 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
              >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
              </select>
            </div>
          </div>

          <!-- Interactive SVG Line Chart Container -->
          <div class="relative w-full h-56 pt-2">
            
            <!-- Left Y-Axis Labels -->
            <div class="absolute left-0 inset-y-2 flex flex-col justify-between text-[10px] text-slate-500 font-mono select-none pointer-events-none pr-2">
              <span>100%</span>
              <span>75%</span>
              <span>50%</span>
              <span>25%</span>
              <span>0%</span>
            </div>

            <!-- SVG Grid & Curve Area -->
            <div class="ml-8 h-full relative">
              
              <!-- Horizontal Grid Lines -->
              <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                <div class="w-full border-b border-slate-800/50"></div>
                <div class="w-full border-b border-slate-800/40"></div>
                <div class="w-full border-b border-slate-800/40"></div>
                <div class="w-full border-b border-slate-800/40"></div>
                <div class="w-full border-b border-slate-800/60"></div>
              </div>

              <!-- Main SVG Line & Area -->
              <svg class="w-full h-[180px] overflow-visible" viewBox="0 0 640 180" preserveAspectRatio="none">
                <defs>
                  <linearGradient id="purpleGlow" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#A855F7" stop-opacity="0.35" />
                    <stop offset="100%" stop-color="#A855F7" stop-opacity="0.0" />
                  </linearGradient>
                  <filter id="neonGlow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                  </filter>
                </defs>

                <!-- Gradient Fill Under Curve -->
                <path :d="chartSvgAreaPath" fill="url(#purpleGlow)" />

                <!-- Main Purple Line -->
                <path
                  :d="chartSvgPath"
                  fill="none"
                  stroke="#A855F7"
                  stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  filter="url(#neonGlow)"
                />

                <!-- Interactive Data Dots -->
                <circle
                  v-for="(pt, idx) in chartSvgPoints"
                  :key="idx"
                  :cx="pt.x"
                  :cy="pt.y"
                  r="4"
                  class="fill-slate-950 stroke-purple-400 stroke-2 hover:r-6 hover:stroke-white transition-all cursor-pointer"
                  @mouseenter="(e) => onPointHover(pt, idx, e)"
                  @mouseleave="onPointLeave"
                />
              </svg>

              <!-- Bottom X-Axis Labels -->
              <div class="w-full flex justify-between text-[9px] sm:text-[10px] text-slate-500 font-mono mt-1 px-1 overflow-x-hidden">
                <span v-for="(lbl, i) in trendPoints" :key="i" :class="i % 2 === 0 ? 'block' : 'hidden sm:block'">
                  {{ lbl.date }}
                </span>
              </div>
            </div>

            <!-- Floating Tooltip Box on Hover -->
            <div
              v-if="hoveredPoint"
              class="absolute z-30 pointer-events-none -translate-x-1/2 -translate-y-full bg-slate-900 border border-purple-500/50 rounded-xl px-3 py-2 shadow-2xl text-xs space-y-1 text-slate-200"
              :style="{ left: `${hoveredPoint.x + 32}px`, top: `${hoveredPoint.y}px` }"
            >
              <p class="font-bold text-white text-[11px] flex items-center justify-between gap-3">
                <span>{{ hoveredPoint.date }}</span>
                <span class="text-purple-400 font-mono font-black">{{ hoveredPoint.score }}%</span>
              </p>
              <div class="text-[10px] text-slate-400 space-y-0.5 font-mono">
                <p>Quizzes: <span class="text-white">{{ hoveredPoint.quizzes }}</span></p>
                <p>High: <span class="text-emerald-400">{{ hoveredPoint.highest }}%</span> | Low: <span class="text-rose-400">{{ hoveredPoint.lowest }}%</span></p>
                <p>Time Spent: <span class="text-indigo-300">{{ hoveredPoint.time }}</span></p>
              </div>
            </div>

          </div>
        </div>

        <!-- RIGHT (1/3): Weekly Summary Card -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl flex flex-col justify-between space-y-4">
          <div>
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <div>
                <h2 class="text-base font-bold text-white tracking-tight">Weekly Summary</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ weeklySummary.date_range }}</p>
              </div>
              <span class="px-2.5 py-1 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-bold">
                WEEKLY
              </span>
            </div>

            <!-- 2x2 Stats Grid -->
            <div class="grid grid-cols-2 gap-3 mt-4">
              <!-- Item 1 -->
              <div class="p-3 bg-slate-900/90 rounded-2xl border border-slate-800/80 space-y-1">
                <p class="text-[11px] text-slate-400 font-medium">Average Score</p>
                <div class="flex items-baseline gap-1.5">
                  <span class="text-xl font-black text-white font-mono">{{ weeklySummary.avg_score }}%</span>
                  <span class="text-[11px] font-bold text-emerald-400">↑ {{ weeklySummary.score_change }}</span>
                </div>
              </div>

              <!-- Item 2 -->
              <div class="p-3 bg-slate-900/90 rounded-2xl border border-slate-800/80 space-y-1">
                <p class="text-[11px] text-slate-400 font-medium">Quizzes Taken</p>
                <div class="flex items-baseline gap-1.5">
                  <span class="text-xl font-black text-white font-mono">{{ weeklySummary.quizzes_taken }}</span>
                  <span class="text-[11px] font-bold text-emerald-400">↑ {{ weeklySummary.quizzes_change }}</span>
                </div>
              </div>

              <!-- Item 3 -->
              <div class="p-3 bg-slate-900/90 rounded-2xl border border-slate-800/80 space-y-1">
                <p class="text-[11px] text-slate-400 font-medium">Correct Answers</p>
                <div class="flex items-baseline gap-1.5">
                  <span class="text-xl font-black text-white font-mono">{{ weeklySummary.correct_answers }}%</span>
                  <span class="text-[11px] font-bold text-emerald-400">↑ {{ weeklySummary.accuracy_change }}</span>
                </div>
              </div>

              <!-- Item 4 -->
              <div class="p-3 bg-slate-900/90 rounded-2xl border border-slate-800/80 space-y-1">
                <p class="text-[11px] text-slate-400 font-medium">Time Spent</p>
                <div class="flex items-baseline gap-1.5">
                  <span class="text-base font-black text-white font-mono">{{ weeklySummary.time_spent }}</span>
                  <span class="text-[10px] font-bold text-emerald-400">↑ {{ weeklySummary.time_change }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Button: View Detailed Report -->
          <button
            @click="isReportModalOpen = true"
            class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-purple-600 via-purple-700 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs tracking-wide shadow-lg shadow-purple-950/40 hover:shadow-purple-700/30 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-95"
          >
            <span>📊</span>
            <span>View Detailed Report</span>
          </button>
        </div>

      </div>

      <!-- ================= 4. MIDDLE-LOWER ROW: PERFORMANCE BY DIFFICULTY & WEAK TOPICS ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT (5/12): Performance by Difficulty -->
        <div class="lg:col-span-5 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          <div class="border-b border-slate-800/60 pb-3 flex items-center justify-between">
            <h2 class="text-base font-bold text-white tracking-tight">Performance by Difficulty</h2>
            <span class="text-xs text-slate-400 font-mono">Accuracy %</span>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-center gap-6 py-2">
            
            <!-- Donut Chart -->
            <div class="relative w-36 h-36 flex items-center justify-center shrink-0">
              <svg class="w-36 h-36 -rotate-90 transform" viewBox="0 0 42 42">
                <!-- Background ring -->
                <circle cx="21" cy="21" r="15.915" fill="none" stroke="#1E293B" stroke-width="4.5" />

                <!-- Segment 1: Easy (Emerald) -->
                <circle
                  cx="21" cy="21" r="15.915"
                  fill="none"
                  stroke="#10B981"
                  stroke-width="4.5"
                  stroke-dasharray="25, 75"
                  stroke-dashoffset="0"
                />
                <!-- Segment 2: Medium (Blue) -->
                <circle
                  cx="21" cy="21" r="15.915"
                  fill="none"
                  stroke="#3B82F6"
                  stroke-width="4.5"
                  stroke-dasharray="25, 75"
                  stroke-dashoffset="-25"
                />
                <!-- Segment 3: Hard (Purple) -->
                <circle
                  cx="21" cy="21" r="15.915"
                  fill="none"
                  stroke="#A855F7"
                  stroke-width="4.5"
                  stroke-dasharray="25, 75"
                  stroke-dashoffset="-50"
                />
                <!-- Segment 4: Expert (Rose/Red) -->
                <circle
                  cx="21" cy="21" r="15.915"
                  fill="none"
                  stroke="#F43F5E"
                  stroke-width="4.5"
                  stroke-dasharray="25, 75"
                  stroke-dashoffset="-75"
                />
              </svg>

              <!-- Center Text -->
              <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-xl font-black text-white font-mono">{{ difficulty.average }}%</span>
                <span class="text-[10px] text-slate-400 font-medium">Average</span>
              </div>
            </div>

            <!-- Legend & Stats -->
            <div class="space-y-2.5 w-full sm:w-44 text-xs">
              <!-- Easy -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                  <span class="text-slate-300 font-medium">Easy</span>
                </div>
                <span class="font-bold text-white font-mono">{{ difficulty.easy }}%</span>
              </div>

              <!-- Medium -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                  <span class="text-slate-300 font-medium">Medium</span>
                </div>
                <span class="font-bold text-white font-mono">{{ difficulty.medium }}%</span>
              </div>

              <!-- Hard -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-purple-400"></span>
                  <span class="text-slate-300 font-medium">Hard</span>
                </div>
                <span class="font-bold text-white font-mono">{{ difficulty.hard }}%</span>
              </div>

              <!-- Expert -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                  <span class="text-slate-300 font-medium">Expert</span>
                </div>
                <span class="font-bold text-white font-mono">{{ difficulty.expert }}%</span>
              </div>
            </div>

          </div>
        </div>

        <!-- RIGHT (7/12): Weak Topics -->
        <div class="lg:col-span-7 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          
          <div class="border-b border-slate-800/60 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <h2 class="text-base font-bold text-white tracking-tight">Weak Topics</h2>
              <span class="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[10px] font-bold">
                Accuracy &lt; 60%
              </span>
            </div>

            <Link
              href="/student/ai-path/weak-topics"
              class="text-xs font-bold text-purple-400 hover:text-purple-300 transition-colors flex items-center gap-1"
            >
              <span>View All</span>
              <span>→</span>
            </Link>
          </div>

          <!-- Weak Topic Rows -->
          <div class="space-y-3 pt-1">
            <div
              v-for="item in weakTopics"
              :key="item.id"
              @click="openTopicDrill(item)"
              class="p-2.5 rounded-xl bg-slate-900/60 hover:bg-slate-900 border border-slate-800/60 hover:border-purple-500/40 transition-all flex items-center justify-between gap-4 cursor-pointer group"
            >
              <!-- Icon & Name -->
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-7 h-7 rounded-lg bg-slate-800 border border-slate-700/80 flex items-center justify-center text-[10px] font-bold text-slate-300 shrink-0 font-mono">
                  {{ item.code || 'JS' }}
                </div>
                <span class="text-xs font-bold text-slate-200 group-hover:text-white truncate">
                  {{ item.title }}
                </span>
              </div>

              <!-- Progress Bar & Score -->
              <div class="flex items-center gap-3 w-40 sm:w-48 shrink-0">
                <div class="w-full h-2 rounded-full bg-slate-950 overflow-hidden border border-slate-800">
                  <div
                    :class="[
                      item.color === 'rose' ? 'bg-rose-500' : (item.color === 'blue' ? 'bg-blue-500' : 'bg-purple-500'),
                      'h-full rounded-full transition-all duration-500'
                    ]"
                    :style="{ width: `${item.score}%` }"
                  ></div>
                </div>
                <span class="text-xs font-mono font-bold text-slate-400 w-9 text-right">{{ item.score }}%</span>
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- ================= 5. LOWER ROW: RECENT QUIZ RESULTS TABLE & RECENT QUIZZES ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT (2/3): Recent Quiz Results Table -->
        <div class="lg:col-span-2 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800/60 pb-3">
            <h2 class="text-base font-bold text-white tracking-tight">Recent Quiz Results</h2>
            
            <div class="flex items-center gap-2">
              <input
                v-model="searchResultQuery"
                type="text"
                placeholder="Search quiz or course..."
                class="bg-slate-900 border border-slate-700/80 text-xs text-slate-300 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 w-44"
              />
            </div>
          </div>

          <!-- Table Container -->
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs text-slate-300">
              <thead class="text-[11px] uppercase font-bold text-slate-400 border-b border-slate-800/80">
                <tr>
                  <th class="py-2.5 px-3">Quiz Title</th>
                  <th class="py-2.5 px-3">Course</th>
                  <th class="py-2.5 px-3">Score</th>
                  <th class="py-2.5 px-3">Accuracy</th>
                  <th class="py-2.5 px-3">Time</th>
                  <th class="py-2.5 px-3">Date</th>
                  <th class="py-2.5 px-3 text-right">Result</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/40 font-medium">
                <tr
                  v-for="row in filteredRecentResults"
                  :key="row.id"
                  @click="openAttemptDetail(row)"
                  class="hover:bg-slate-900/80 cursor-pointer transition-colors group"
                >
                  <!-- Quiz Title -->
                  <td class="py-3 px-3">
                    <div class="flex items-center gap-2.5">
                      <div class="w-6 h-6 rounded-md bg-slate-800 border border-slate-700 flex items-center justify-center text-[10px] font-bold text-slate-200 shrink-0 font-mono">
                        {{ row.icon || 'JS' }}
                      </div>
                      <span class="font-bold text-white group-hover:text-purple-300 transition-colors">{{ row.title }}</span>
                    </div>
                  </td>

                  <!-- Course -->
                  <td class="py-3 px-3 text-slate-400">{{ row.course }}</td>

                  <!-- Score -->
                  <td class="py-3 px-3 font-mono font-bold" :class="row.score >= 80 ? 'text-emerald-400' : (row.score >= 60 ? 'text-blue-400' : 'text-rose-400')">
                    {{ row.score }}%
                  </td>

                  <!-- Accuracy -->
                  <td class="py-3 px-3 font-mono text-slate-400">{{ row.accuracy }}%</td>

                  <!-- Time -->
                  <td class="py-3 px-3 font-mono text-slate-400">{{ row.time }}</td>

                  <!-- Date -->
                  <td class="py-3 px-3 text-slate-400 whitespace-nowrap">{{ row.date }}</td>

                  <!-- Result Badge -->
                  <td class="py-3 px-3 text-right">
                    <span
                      class="px-2.5 py-1 rounded-full text-[10px] font-bold border"
                      :class="[
                        row.badge === 'emerald' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' :
                        row.badge === 'blue' ? 'bg-blue-500/20 text-blue-300 border-blue-500/30' :
                        row.badge === 'amber' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' :
                        'bg-rose-500/20 text-rose-300 border-rose-500/30'
                      ]"
                    >
                      {{ row.result }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Bottom Footer Link -->
          <div class="pt-2 text-center border-t border-slate-800/60">
            <Link
              href="/student/quizzes/history"
              class="text-xs font-bold text-slate-400 hover:text-purple-400 transition-colors inline-flex items-center gap-1"
            >
              <span>View All Quiz History</span>
              <span>&gt;</span>
            </Link>
          </div>

        </div>

        <!-- RIGHT (1/3): Recent Quizzes Card -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          
          <div class="border-b border-slate-800/60 pb-3 flex items-center justify-between">
            <h2 class="text-base font-bold text-white tracking-tight">Recent Quizzes</h2>
            <Link
              href="/student/quizzes/history"
              class="text-xs font-bold text-purple-400 hover:text-purple-300 transition-colors"
            >
              View All
            </Link>
          </div>

          <!-- Chronological Date Badges List -->
          <div class="space-y-3 pt-1">
            <div
              v-for="item in recentQuizzes"
              :key="item.id"
              class="p-2.5 rounded-2xl bg-slate-900/60 hover:bg-slate-900 border border-slate-800/60 transition-all flex items-center justify-between gap-3 group"
            >
              <div class="flex items-center gap-3 min-w-0">
                <!-- Date Badge -->
                <div class="px-2 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-center shrink-0">
                  <p class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">MAY</p>
                  <p class="text-xs font-black text-white font-mono">{{ item.date_badge.split(' ')[1] || '20' }}</p>
                </div>

                <div class="min-w-0">
                  <p class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors truncate">
                    {{ item.title }}
                  </p>
                  <p class="text-[10px] text-slate-400 truncate">{{ item.course }}</p>
                </div>
              </div>

              <!-- Score Percentage -->
              <span class="text-sm font-black font-mono shrink-0" :class="item.badge_color || 'text-emerald-400'">
                {{ item.score }}%
              </span>
            </div>
          </div>

        </div>

      </div>

      <!-- ================= 6. BOTTOM ROW: PERFORMANCE BY TOPIC & AI INSIGHT ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT (2/3): Performance by Topic -->
        <div class="lg:col-span-2 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          
          <div class="border-b border-slate-800/60 pb-3 flex items-center justify-between">
            <h2 class="text-base font-bold text-white tracking-tight">Performance by Topic</h2>
            <Link
              href="/student/ai-path/weak-topics"
              class="text-xs font-bold text-purple-400 hover:text-purple-300 transition-colors"
            >
              View All
            </Link>
          </div>

          <!-- 6 Topic Cards Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 pt-1">
            <div
              v-for="t in topics"
              :key="t.name"
              class="p-3.5 bg-slate-900/80 hover:bg-slate-900 border border-slate-800/80 hover:border-slate-700 rounded-2xl space-y-2.5 transition-all shadow-md group relative overflow-hidden"
            >
              <div class="flex items-center justify-between">
                <div class="w-7 h-7 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-[10px] font-bold text-white font-mono">
                  {{ t.code }}
                </div>
                <span class="text-xs font-mono font-black text-white">{{ t.score }}%</span>
              </div>

              <p class="text-xs font-bold text-slate-300 group-hover:text-white transition-colors truncate">
                {{ t.name }}
              </p>

              <!-- Colored underline progress bar -->
              <div class="w-full h-1.5 rounded-full bg-slate-950 overflow-hidden border border-slate-800/80">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :style="{ width: `${t.score}%`, backgroundColor: t.color }"
                ></div>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT (1/3): AI Insight Card -->
        <div class="bg-gradient-to-br from-[#10132B] via-[#0F172A] to-[#1E1138] border border-purple-900/50 rounded-3xl p-5 md:p-6 shadow-2xl flex flex-col justify-between space-y-4 relative overflow-hidden group">
          
          <!-- Subtle background glow -->
          <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-purple-600/15 rounded-full blur-3xl pointer-events-none"></div>

          <div>
            <!-- Header with 3D Mascot Avatar -->
            <div class="flex items-center justify-between border-b border-purple-900/40 pb-3">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-purple-600/20 border border-purple-500/40 flex items-center justify-center text-2xl shadow-inner animate-pulse">
                  🤖
                </div>
                <div>
                  <h2 class="text-base font-bold text-white tracking-tight">AI Insight</h2>
                  <p class="text-[10px] text-purple-300">Intelligent Recommendation</p>
                </div>
              </div>

              <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-black uppercase tracking-wider">
                AI
              </span>
            </div>

            <!-- Insight Bullets -->
            <div class="space-y-2.5 mt-4 text-xs">
              <div class="p-2.5 rounded-xl bg-purple-950/40 border border-purple-900/30 text-slate-200 flex items-start gap-2">
                <span class="text-emerald-400 font-bold shrink-0">✓</span>
                <p>"{{ aiInsight.primary }}"</p>
              </div>

              <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-slate-300 flex items-start gap-2">
                <span class="text-amber-400 font-bold shrink-0">💡</span>
                <p>"{{ aiInsight.secondary }}"</p>
              </div>
            </div>
          </div>

          <!-- Button: Get AI Study Plan -->
          <button
            @click="isStudyPlanModalOpen = true"
            class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/50 hover:shadow-purple-700/40 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-95 z-10"
          >
            <span>✨</span>
            <span>Get AI Study Plan</span>
            <span>&gt;</span>
          </button>

        </div>

      </div>

    </div>

    <!-- ================= MODAL 1: AI STUDY PLAN MODAL ================= -->
    <div
      v-if="isStudyPlanModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <span class="text-2xl">🤖</span>
            <div>
              <h3 class="text-base font-black text-white">Personalized AI Weekly Study Plan</h3>
              <p class="text-[11px] text-purple-300">Customized according to your latest quiz weak topics</p>
            </div>
          </div>
          <button
            @click="isStudyPlanModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 max-h-80 overflow-y-auto custom-scrollbar pr-1">
          <div
            v-for="(plan, idx) in aiInsight.study_plan"
            :key="idx"
            class="p-3 bg-slate-950 rounded-2xl border border-slate-800 flex items-center justify-between gap-3 text-xs"
          >
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-purple-600/20 text-purple-300 border border-purple-500/30 flex items-center justify-center font-bold font-mono text-xs shrink-0">
                {{ idx + 1 }}
              </span>
              <div>
                <p class="font-bold text-white">{{ plan.day }}: {{ plan.title }}</p>
                <p class="text-[10px] text-slate-400">{{ plan.type }}</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-slate-900 border border-slate-700 text-purple-300 font-mono text-[10px] font-bold shrink-0">
              {{ plan.duration }}
            </span>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-800 text-xs">
          <Link
            href="/student/ai-tutor"
            class="text-purple-400 hover:text-purple-300 font-bold flex items-center gap-1"
          >
            <span>Ask AI Tutor 24/7</span>
            <span>→</span>
          </Link>

          <div class="flex items-center gap-2">
            <button
              @click="isStudyPlanModalOpen = false"
              class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold"
            >
              Close
            </button>
            <Link
              href="/student/ai-path/recommended"
              class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md"
            >
              Launch Learning Path
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 2: FULL DETAILED REPORT MODAL ================= -->
    <div
      v-if="isReportModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-2xl w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-white flex items-center gap-2">
              <span>📊</span>
              <span>Comprehensive Quiz Analytics Report</span>
            </h3>
            <p class="text-[11px] text-slate-400">Detailed performance summary and historical distribution</p>
          </div>
          <button
            @click="isReportModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center text-xs">
          <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
            <p class="text-slate-400 text-[10px]">Average Score</p>
            <p class="text-lg font-black text-white font-mono mt-1">{{ summary.average_score }}%</p>
          </div>
          <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
            <p class="text-slate-400 text-[10px]">Total Quizzes</p>
            <p class="text-lg font-black text-white font-mono mt-1">{{ summary.quizzes_taken }}</p>
          </div>
          <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
            <p class="text-slate-400 text-[10px]">Accuracy Rate</p>
            <p class="text-lg font-black text-emerald-400 font-mono mt-1">{{ summary.accuracy }}%</p>
          </div>
          <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
            <p class="text-slate-400 text-[10px]">Study Momentum</p>
            <p class="text-lg font-black text-purple-400 font-mono mt-1">+12%</p>
          </div>
        </div>

        <div class="space-y-2 text-xs">
          <h4 class="font-bold text-white uppercase text-[10px]">Difficulty Breakdown</h4>
          <div class="space-y-1.5">
            <div class="flex items-center justify-between text-[11px] text-slate-300">
              <span>Easy Questions ({{ difficulty.easy }}%)</span>
              <span class="text-emerald-400 font-bold">Strong</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full" :style="{ width: `${difficulty.easy}%` }"></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-300 pt-1">
              <span>Medium Questions ({{ difficulty.medium }}%)</span>
              <span class="text-blue-400 font-bold">Good</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
              <div class="h-full bg-blue-500 rounded-full" :style="{ width: `${difficulty.medium}%` }"></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-300 pt-1">
              <span>Hard Questions ({{ difficulty.hard }}%)</span>
              <span class="text-purple-400 font-bold">Needs Practice</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
              <div class="h-full bg-purple-500 rounded-full" :style="{ width: `${difficulty.hard}%` }"></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-300 pt-1">
              <span>Expert Questions ({{ difficulty.expert }}%)</span>
              <span class="text-rose-400 font-bold">Target for Growth</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
              <div class="h-full bg-rose-500 rounded-full" :style="{ width: `${difficulty.expert}%` }"></div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
          <button
            @click="isReportModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs"
          >
            Close
          </button>
          <Link
            href="/student/quizzes/history"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md"
          >
            Go to Full Audit History
          </Link>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 3: ATTEMPT DETAIL MODAL ================= -->
    <div
      v-if="isAttemptDetailModalOpen && selectedAttempt"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-white">{{ selectedAttempt.title }}</h3>
            <p class="text-[11px] text-slate-400">{{ selectedAttempt.course }} • {{ selectedAttempt.date }}</p>
          </div>
          <button
            @click="isAttemptDetailModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 flex items-center justify-between text-xs font-mono">
          <div>
            <p class="text-slate-400 text-[10px]">Score Achieved</p>
            <p class="text-lg font-black text-emerald-400">{{ selectedAttempt.score }}%</p>
          </div>
          <div>
            <p class="text-slate-400 text-[10px]">Accuracy</p>
            <p class="text-lg font-black text-white">{{ selectedAttempt.accuracy }}%</p>
          </div>
          <div>
            <p class="text-slate-400 text-[10px]">Time Spent</p>
            <p class="text-lg font-black text-purple-300">{{ selectedAttempt.time }}</p>
          </div>
          <div>
            <p class="text-slate-400 text-[10px]">Result</p>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
              {{ selectedAttempt.result }}
            </span>
          </div>
        </div>

        <div class="space-y-2 text-xs max-h-48 overflow-y-auto custom-scrollbar">
          <p class="font-bold text-slate-300 text-[11px]">Audit Question Log:</p>
          <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800 space-y-1">
            <p class="text-emerald-400 font-bold">✓ Q1: Conceptual Syntax (15s)</p>
            <p class="text-[10px] text-slate-400">Answer correct • +10 points awarded</p>
          </div>
          <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800 space-y-1">
            <p class="text-emerald-400 font-bold">✓ Q2: Output Prediction (22s)</p>
            <p class="text-[10px] text-slate-400">Answer correct • +10 points awarded</p>
          </div>
          <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800 space-y-1">
            <p class="text-rose-400 font-bold">✕ Q3: Edge Case Logic (45s)</p>
            <p class="text-[10px] text-slate-400">Incorrect option selected • Review recommendation triggered</p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
          <button
            @click="isAttemptDetailModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs"
          >
            Close
          </button>
          <Link
            :href="`/student/quizzes`"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md"
          >
            Retake Quiz Drill
          </Link>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 4: TOPIC QUICK DRILL MODAL ================= -->
    <div
      v-if="isTopicDrillModalOpen && selectedTopicDrill"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-white">Target Topic Drill</h3>
            <p class="text-[11px] text-purple-300">{{ selectedTopicDrill.title }}</p>
          </div>
          <button
            @click="isTopicDrillModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 space-y-2 text-xs">
          <div class="flex items-center justify-between">
            <span class="text-slate-400">Current Accuracy:</span>
            <span class="font-mono font-bold text-rose-400">{{ selectedTopicDrill.score }}%</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-400">Status:</span>
            <span class="text-rose-400 font-bold">Needs Targeted Practice</span>
          </div>
          <p class="text-[11px] text-slate-300 pt-1">
            💡 <strong>Recommendation:</strong> {{ selectedTopicDrill.recommendation || 'Practice 15 drill questions to master this topic.' }}
          </p>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isTopicDrillModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Close
          </button>
          <Link
            href="/student/ai-path/weak-topics"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md"
          >
            Start Weak Topic Practice
          </Link>
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
