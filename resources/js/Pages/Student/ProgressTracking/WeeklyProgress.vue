<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

const { isKhmer } = useLanguage()

interface QuestionLogItem {
  index: number
  question: string
  correct: boolean
  pending?: boolean
  points: number
}

const props = defineProps<{
  analytics?: {
    summary: {
      average_score: number
      score_status: string
      score_trend: string
      quizzes_taken: number
      quizzes_status: string
      quizzes_trend: string
      highest_score: { score: number; quiz: string; course: string; date: string }
      lowest_score: { score: number; quiz: string; course: string; date: string }
      accuracy: number
      accuracy_status: string
      accuracy_trend: string
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
      counts?: {
        easy: number
        medium: number
        hard: number
        expert: number
      }
    }
    weak_topics: Array<{
      id: number
      quiz_id?: number
      course_id?: number
      title: string
      course?: string
      code?: string
      score: number
      color: string
      recommendation?: string
    }>
    recent_results: Array<{
      id: number
      quiz_id: number
      course_id?: number
      title: string
      course: string
      score: number
      accuracy: number
      time: string
      date: string
      date_badge?: string
      result: string
      badge: string
      badge_color?: string
      icon?: string
      question_log?: QuestionLogItem[]
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
      quizzes?: number
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

const emptySummary = {
  average_score: 0,
  score_status: 'No Quizzes Yet',
  score_trend: '0% this week',
  quizzes_taken: 0,
  quizzes_status: 'No Attempts Yet',
  quizzes_trend: '0 this week',
  highest_score: { score: 0, quiz: '—', course: '—', date: '—' },
  lowest_score: { score: 0, quiz: '—', course: '—', date: '—' },
  accuracy: 0,
  accuracy_status: 'No Data Yet',
  accuracy_trend: '0% this week',
}

const emptyWeeklySummary = {
  date_range: '—',
  avg_score: 0,
  score_change: '0%',
  quizzes_taken: 0,
  quizzes_change: '0',
  correct_answers: 0,
  accuracy_change: '0%',
  time_spent: '0h 00m',
  time_change: '0m',
}

const summary = computed(() => props.analytics?.summary || emptySummary)
const weeklySummary = computed(() => props.analytics?.weekly_summary || emptyWeeklySummary)
const trendPoints = computed(() => props.analytics?.trend_chart?.points || [])
const difficulty = computed(() => props.analytics?.difficulty || { average: 0, easy: 0, medium: 0, hard: 0, expert: 0 })
const weakTopics = computed(() => props.analytics?.weak_topics || [])
const recentResults = computed(() => props.analytics?.recent_results || [])
const recentQuizzes = computed(() => props.analytics?.recent_quizzes || [])
const topics = computed(() => props.analytics?.topics || [])
const aiInsight = computed(() => props.analytics?.ai_insight || {
  primary: 'No quiz attempts recorded yet.',
  secondary: 'Enroll in a course and take your first quiz to unlock personalized AI insights.',
  study_plan: [],
})

const hasQuizzesTaken = computed(() => (summary.value.quizzes_taken || 0) > 0)

// Dynamic Difficulty SVG Donut Segments (r = 15.915 => circumference = 100)
const difficultySegments = computed(() => {
  if (!hasQuizzesTaken.value) return []
  const items = [
    { key: 'easy', val: difficulty.value.easy || 0, count: difficulty.value.counts?.easy ?? 0, color: '#10B981' },
    { key: 'medium', val: difficulty.value.medium || 0, count: difficulty.value.counts?.medium ?? 0, color: '#3B82F6' },
    { key: 'hard', val: difficulty.value.hard || 0, count: difficulty.value.counts?.hard ?? 0, color: '#A855F7' },
    { key: 'expert', val: difficulty.value.expert || 0, count: difficulty.value.counts?.expert ?? 0, color: '#F43F5E' },
  ]
  const totalCount = items.reduce((acc, it) => acc + it.count, 0)
  const totalVal = items.reduce((acc, it) => acc + it.val, 0)

  let offset = 0
  return items
    .map(it => {
      let pct = 0
      if (totalCount > 0) {
        pct = Math.round((it.count / totalCount) * 100)
      } else if (totalVal > 0) {
        pct = Math.round((it.val / totalVal) * 100)
      }
      return { ...it, pct }
    })
    .filter(it => it.pct > 0)
    .map(it => {
      const seg = {
        ...it,
        dashArray: `${it.pct} ${Math.max(0, 100 - it.pct)}`,
        dashOffset: -offset,
      }
      offset += it.pct
      return seg
    })
})

// Filter states
const selectedPeriod = ref(props.filters?.period || 'this_month')
const selectedGranularity = ref(props.filters?.granularity || 'daily')
const searchResultQuery = ref('')

// Interactive Tooltip on Line Chart
const hoveredPoint = ref<ChartPoint | null>(null)

const onPointHover = (pt: ChartPoint) => {
  hoveredPoint.value = pt
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

  const stepX = pts.length === 1 ? 0 : usableW / (pts.length - 1)

  return pts.map((p, idx) => {
    const x = pts.length === 1 ? width / 2 : paddingX + idx * stepX
    const y = paddingY + (1 - (Math.min(100, Math.max(0, p.score)) / 100)) * usableH
    return { ...p, x, y }
  })
})

const chartSvgPath = computed<string>(() => {
  const pts = chartSvgPoints.value
  if (!pts || pts.length === 0) return ''
  if (pts.length === 1) {
    return `M 20,${pts[0].y} L 620,${pts[0].y}`
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
  const baseLine = 165
  if (pts.length === 1) {
    return `M 20,${pts[0].y} L 620,${pts[0].y} L 620,${baseLine} L 20,${baseLine} Z`
  }
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

const splitDateBadge = (badgeStr?: string) => {
  if (!badgeStr) return { month: '—', day: '—' }
  const parts = badgeStr.trim().split(/\s+/)
  return {
    month: parts[0] || '—',
    day: parts[1] || '—',
  }
}

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
              <span>{{ isKhmer ? 'លទ្ធផលប្រឡង Quiz' : 'Quiz Performance' }}</span>
              <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">📈</span>
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            {{ isKhmer ? 'វិភាគពិន្ទុប្រឡង ភាពត្រឹមត្រូវ និងការរីកចម្រើនរបស់អ្នកតាមពេលវេលា។' : 'Analyze your real quiz scores, accuracy, and improvement over time.' }}
          </p>
        </div>

        <!-- Action shortcut: View Detailed Full Report -->
        <button
          type="button"
          @click="isReportModalOpen = true"
          class="px-4 py-2 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-purple-500/40 text-slate-300 hover:text-white text-xs font-bold transition-all shadow-md self-start sm:self-auto flex items-center gap-2 group cursor-pointer"
        >
          <span class="text-purple-400 group-hover:rotate-12 transition-transform">📊</span>
          <span>{{ isKhmer ? 'របាយការណ៍លម្អិត' : 'Full Analytics Summary' }}</span>
        </button>
      </div>

      <!-- ================= 2. SUMMARY CARDS (5 TOP CARDS) ================= -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

        <!-- CARD 1: Average Score -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-purple-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ពិន្ទុមធ្យម' : 'Average Score' }}</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">
              {{ hasQuizzesTaken ? `${summary.average_score}%` : '0%' }}
            </p>
            <p class="text-[11px] font-bold" :class="hasQuizzesTaken ? 'text-emerald-400' : 'text-slate-500'">
              {{ summary.score_status }}
            </p>
            <p class="text-[11px] text-slate-400 font-medium pt-0.5">
              {{ summary.score_trend }}
            </p>
          </div>
          <div class="relative w-14 h-14 flex items-center justify-center shrink-0">
            <svg class="w-14 h-14 -rotate-90 transform" viewBox="0 0 36 36">
              <path
                class="text-slate-800"
                stroke-width="3"
                stroke="currentColor"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />
              <path
                v-if="summary.average_score > 0"
                class="text-purple-500 transition-all duration-1000"
                :stroke-dasharray="`${summary.average_score}, 100`"
                stroke-width="3.2"
                stroke-linecap="round"
                stroke="currentColor"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-purple-400 text-xs font-mono font-bold">
              {{ summary.average_score }}%
            </span>
          </div>
        </div>

        <!-- CARD 2: Quizzes Taken -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-blue-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'តេស្តបានធ្វើ' : 'Quizzes Taken' }}</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.quizzes_taken }}</p>
            <p class="text-[11px] text-slate-400 font-medium">{{ summary.quizzes_status }}</p>
            <p class="text-[11px] text-slate-400 font-medium pt-0.5">
              {{ summary.quizzes_trend }}
            </p>
          </div>
          <div class="w-12 h-12 rounded-full bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-400 text-lg shadow-inner group-hover:scale-110 transition-transform shrink-0">
            📑
          </div>
        </div>

        <!-- CARD 3: Highest Score -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-amber-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10 min-w-0 pr-2">
            <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ពិន្ទុខ្ពស់បំផុត' : 'Highest Score' }}</p>
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
            <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ពិន្ទុទាបបំផុត' : 'Lowest Score' }}</p>
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
            <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ភាពត្រឹមត្រូវ' : 'Accuracy' }}</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.accuracy }}%</p>
            <p class="text-[11px] text-slate-400 font-medium">{{ summary.accuracy_status }}</p>
            <p class="text-[11px] text-slate-400 font-medium pt-0.5">
              {{ summary.accuracy_trend }}
            </p>
          </div>
          <div class="w-12 h-12 rounded-full bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg shadow-inner group-hover:scale-110 transition-transform shrink-0">
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
              <span>{{ isKhmer ? 'និន្នាការពិន្ទុប្រឡងតាមពេលវេលា' : 'Quiz Performance Over Time' }}</span>
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

          <!-- Empty State when 0 quizzes -->
          <div v-if="trendPoints.length === 0" class="h-56 flex flex-col items-center justify-center text-center px-4 space-y-2.5">
            <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-2xl">
              📝
            </div>
            <p class="text-sm font-bold text-white">
              {{ isKhmer ? 'មិនទាន់មានលទ្ធផលតេស្តក្នុងរយៈពេលនេះទេ' : 'No quiz attempts in this period yet' }}
            </p>
            <p class="text-xs text-slate-400 max-w-md">
              {{ isKhmer ? 'ចូលរួមវគ្គសិក្សា ហើយសាកល្បងធ្វើតេស្ត Quiz ដើម្បីមើលក្រាហ្វពិន្ទុរបស់អ្នក។' : 'Enroll in a course and complete a quiz to visualize your performance trend.' }}
            </p>
            <div class="flex items-center gap-2 pt-1">
              <Link
                href="/student/quizzes"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-md transition-all"
              >
                {{ isKhmer ? 'ទៅកាន់តេស្ត Quiz' : 'Go to Quizzes' }}
              </Link>
              <Link
                href="/student/browse"
                class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-200 text-xs font-bold transition-all"
              >
                {{ isKhmer ? 'រុករកវគ្គសិក្សា' : 'Browse Courses' }}
              </Link>
            </div>
          </div>

          <!-- Interactive SVG Line Chart Container -->
          <div v-else class="relative w-full h-56 pt-2">
            <!-- Left Y-Axis Labels -->
            <div class="absolute left-0 inset-y-2 flex flex-col justify-between text-[10px] text-slate-500 font-mono select-none pointer-events-none pr-2">
              <span>100%</span>
              <span>75%</span>
              <span>50%</span>
              <span>25%</span>
              <span>0%</span>
            </div>

            <div class="ml-8 h-full relative">
              <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                <div class="w-full border-b border-slate-800/50"></div>
                <div class="w-full border-b border-slate-800/40"></div>
                <div class="w-full border-b border-slate-800/40"></div>
                <div class="w-full border-b border-slate-800/40"></div>
                <div class="w-full border-b border-slate-800/60"></div>
              </div>

              <svg class="w-full h-[180px] overflow-visible" viewBox="0 0 640 180" preserveAspectRatio="none">
                <defs>
                  <linearGradient id="purpleGlow" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#A855F7" stop-opacity="0.35" />
                    <stop offset="100%" stop-color="#A855F7" stop-opacity="0.0" />
                  </linearGradient>
                </defs>

                <path :d="chartSvgAreaPath" fill="url(#purpleGlow)" />
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
                  r="4"
                  class="fill-slate-950 stroke-purple-400 stroke-2 hover:stroke-white transition-all cursor-pointer"
                  @mouseenter="onPointHover(pt)"
                  @mouseleave="onPointLeave"
                />
              </svg>

              <!-- Bottom X-Axis Labels -->
              <div class="w-full flex justify-between text-[9px] sm:text-[10px] text-slate-500 font-mono mt-1 px-1 overflow-x-hidden">
                <span v-for="(lbl, i) in trendPoints" :key="i" :class="trendPoints.length > 8 && i % 2 !== 0 ? 'hidden sm:block' : 'block'">
                  {{ lbl.date }}
                </span>
              </div>
            </div>

            <!-- Floating Tooltip Box on Hover -->
            <div
              v-if="hoveredPoint"
              class="absolute z-30 pointer-events-none top-2 right-2 bg-slate-900 border border-purple-500/50 rounded-xl px-3 py-2 shadow-2xl text-xs space-y-1 text-slate-200"
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
                <h2 class="text-base font-bold text-white tracking-tight">{{ isKhmer ? 'សង្ខេបប្រចាំសប្តាហ៍' : 'Weekly Summary' }}</h2>
                <p class="text-[11px] text-slate-400 mt-0.5">{{ weeklySummary.date_range }}</p>
              </div>
              <span class="px-2.5 py-1 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-bold">
                WEEKLY
              </span>
            </div>

            <!-- 2x2 Stats Grid -->
            <div class="grid grid-cols-2 gap-3 mt-4">
              <div class="p-3 bg-slate-900/90 rounded-2xl border border-slate-800/80 space-y-1">
                <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ពិន្ទុមធ្យម' : 'Average Score' }}</p>
                <div class="flex items-baseline gap-1.5">
                  <span class="text-xl font-black text-white font-mono">{{ weeklySummary.avg_score }}%</span>
                  <span class="text-[11px] font-bold text-slate-400">{{ weeklySummary.score_change }}</span>
                </div>
              </div>

              <div class="p-3 bg-slate-900/90 rounded-2xl border border-slate-800/80 space-y-1">
                <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'តេស្តបានធ្វើ' : 'Quizzes Taken' }}</p>
                <div class="flex items-baseline gap-1.5">
                  <span class="text-xl font-black text-white font-mono">{{ weeklySummary.quizzes_taken }}</span>
                  <span class="text-[11px] font-bold text-slate-400">{{ weeklySummary.quizzes_change }}</span>
                </div>
              </div>

              <div class="p-3 bg-slate-900/90 rounded-2xl border border-slate-800/80 space-y-1">
                <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ភាពត្រឹមត្រូវ' : 'Correct Answers' }}</p>
                <div class="flex items-baseline gap-1.5">
                  <span class="text-xl font-black text-white font-mono">{{ weeklySummary.correct_answers }}%</span>
                  <span class="text-[11px] font-bold text-slate-400">{{ weeklySummary.accuracy_change }}</span>
                </div>
              </div>

              <div class="p-3 bg-slate-900/90 rounded-2xl border border-slate-800/80 space-y-1">
                <p class="text-[11px] text-slate-400 font-medium">{{ isKhmer ? 'ពេលវេលាចំណាយ' : 'Time Spent' }}</p>
                <div class="flex items-baseline gap-1.5">
                  <span class="text-base font-black text-white font-mono">{{ weeklySummary.time_spent }}</span>
                  <span class="text-[10px] font-bold text-slate-400">{{ weeklySummary.time_change }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Button -->
          <button
            type="button"
            @click="isReportModalOpen = true"
            class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-purple-600 via-purple-700 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs tracking-wide shadow-lg shadow-purple-950/40 transition-all flex items-center justify-center gap-2 cursor-pointer"
          >
            <span>📊</span>
            <span>{{ isKhmer ? 'មើលរបាយការណ៍លម្អិត' : 'View Detailed Summary' }}</span>
          </button>
        </div>

      </div>

      <!-- ================= 4. MIDDLE-LOWER ROW: PERFORMANCE BY DIFFICULTY & WEAK TOPICS ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT (5/12): Performance by Difficulty -->
        <div class="lg:col-span-5 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          <div class="border-b border-slate-800/60 pb-3 flex items-center justify-between">
            <h2 class="text-base font-bold text-white tracking-tight">
              {{ isKhmer ? 'លទ្ធផលតាមកម្រិតលំបាក' : 'Performance by Difficulty' }}
            </h2>
            <span class="text-xs text-slate-400 font-mono">Accuracy %</span>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-center gap-6 py-2">
            <!-- Dynamic Donut Chart -->
            <div class="relative w-36 h-36 flex items-center justify-center shrink-0">
              <svg class="w-36 h-36 -rotate-90 transform" viewBox="0 0 42 42">
                <circle cx="21" cy="21" r="15.915" fill="none" stroke="#1E293B" stroke-width="4.5" />
                <circle
                  v-for="seg in difficultySegments"
                  :key="seg.key"
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
                <span class="text-xl font-black text-white font-mono">{{ difficulty.average }}%</span>
                <span class="text-[10px] text-slate-400 font-medium">{{ isKhmer ? 'មធ្យម' : 'Average' }}</span>
              </div>
            </div>

            <!-- Legend & Stats -->
            <div class="space-y-2.5 w-full sm:w-44 text-xs">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                  <span class="text-slate-300 font-medium">Easy</span>
                </div>
                <span class="font-bold text-white font-mono">{{ difficulty.easy }}%</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                  <span class="text-slate-300 font-medium">Medium</span>
                </div>
                <span class="font-bold text-white font-mono">{{ difficulty.medium }}%</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-purple-400"></span>
                  <span class="text-slate-300 font-medium">Hard</span>
                </div>
                <span class="font-bold text-white font-mono">{{ difficulty.hard }}%</span>
              </div>

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
              <h2 class="text-base font-bold text-white tracking-tight">
                {{ isKhmer ? 'ប្រធានបទដែលត្រូវពង្រឹង' : 'Weak Topics' }}
              </h2>
              <span class="px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[10px] font-bold">
                Score &lt; 70%
              </span>
            </div>

            <Link
              href="/student/ai-path/weak-topics"
              class="text-xs font-bold text-purple-400 hover:text-purple-300 transition-colors flex items-center gap-1"
            >
              <span>{{ isKhmer ? 'មើលទាំងអស់' : 'View All' }}</span>
              <span>→</span>
            </Link>
          </div>

          <!-- Empty Weak Topics State -->
          <div v-if="weakTopics.length === 0" class="py-8 text-center space-y-2">
            <div class="w-10 h-10 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-lg mx-auto">
              {{ hasQuizzesTaken ? '🎉' : '🧭' }}
            </div>
            <p class="text-xs font-bold text-white">
              {{
                hasQuizzesTaken
                  ? (isKhmer ? 'មិនមានប្រធានបទខ្សោយត្រូវបានរកឃើញទេ! ពិន្ទុរបស់អ្នកលើស ៧០% ទាំងអស់។' : 'No weak topics detected! All attempted quizzes are 70% or higher.')
                  : (isKhmer ? 'មិនទាន់មានទិន្នន័យប្រធានបទខ្សោយនៅឡើយទេ' : 'No weak topics identified yet')
              }}
            </p>
            <p class="text-[11px] text-slate-400">
              {{
                hasQuizzesTaken
                  ? (isKhmer ? 'បន្តរក្សាគុណភាពសិក្សារបស់អ្នកឱ្យបានល្អប្រសើរ!' : 'Keep up your strong academic performance across your courses.')
                  : (isKhmer ? 'នៅពេលអ្នកប្រឡងទទួលបានក្រោម ៧០% ប្រព័ន្ធនឹងបង្ហាញចំណុចដែលត្រូវរំលឹកនៅទីនេះ។' : 'Topics where you score below 70% will automatically appear here for targeted review.')
              }}
            </p>
          </div>

          <!-- Weak Topic Rows -->
          <div v-else class="space-y-3 pt-1">
            <div
              v-for="item in weakTopics"
              :key="item.id"
              @click="openTopicDrill(item)"
              class="p-2.5 rounded-xl bg-slate-900/60 hover:bg-slate-900 border border-slate-800/60 hover:border-purple-500/40 transition-all flex items-center justify-between gap-4 cursor-pointer group"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-7 h-7 rounded-lg bg-slate-800 border border-slate-700/80 flex items-center justify-center text-[10px] font-bold text-slate-300 shrink-0 font-mono">
                  {{ item.code || 'QZ' }}
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-bold text-slate-200 group-hover:text-white truncate">
                    {{ item.title }}
                  </p>
                  <p v-if="item.course" class="text-[10px] text-slate-400 truncate">{{ item.course }}</p>
                </div>
              </div>

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
            <h2 class="text-base font-bold text-white tracking-tight">
              {{ isKhmer ? 'លទ្ធផលប្រឡងថ្មីៗ' : 'Recent Quiz Results' }}
            </h2>

            <div class="flex items-center gap-2">
              <input
                v-model="searchResultQuery"
                type="text"
                :placeholder="isKhmer ? 'ស្វែងរកតេស្ត ឬវគ្គសិក្សា...' : 'Search quiz or course...'"
                class="bg-slate-900 border border-slate-700/80 text-xs text-slate-300 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 w-44"
              />
            </div>
          </div>

          <!-- Empty Results State -->
          <div v-if="filteredRecentResults.length === 0" class="py-10 text-center space-y-2.5">
            <p class="text-xs font-bold text-slate-300">
              {{ isKhmer ? 'មិនទាន់មានប្រវត្តិលទ្ធផលប្រឡងនៅឡើយទេ' : 'No quiz results recorded yet' }}
            </p>
            <p class="text-[11px] text-slate-400">
              {{ isKhmer ? 'រាល់តេស្តដែលអ្នកបានដាក់ស្នើនឹងបង្ហាញពិន្ទុ និងភាពត្រឹមត្រូវនៅទីនេះ។' : 'Completed quizzes will display your real scores, accuracy, and duration here.' }}
            </p>
          </div>

          <!-- Table Container -->
          <div v-else class="overflow-x-auto custom-scrollbar">
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
                  <td class="py-3 px-3">
                    <div class="flex items-center gap-2.5">
                      <div class="w-6 h-6 rounded-md bg-slate-800 border border-slate-700 flex items-center justify-center text-[10px] font-bold text-slate-200 shrink-0 font-mono">
                        {{ row.icon || 'QZ' }}
                      </div>
                      <span class="font-bold text-white group-hover:text-purple-300 transition-colors">{{ row.title }}</span>
                    </div>
                  </td>

                  <td class="py-3 px-3 text-slate-400">{{ row.course }}</td>

                  <td class="py-3 px-3 font-mono font-bold" :class="row.score >= 80 ? 'text-emerald-400' : (row.score >= 60 ? 'text-blue-400' : 'text-rose-400')">
                    {{ row.score }}%
                  </td>

                  <td class="py-3 px-3 font-mono text-slate-400">{{ row.accuracy }}%</td>

                  <td class="py-3 px-3 font-mono text-slate-400">{{ row.time }}</td>

                  <td class="py-3 px-3 text-slate-400 whitespace-nowrap">{{ row.date }}</td>

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

          <div class="pt-2 text-center border-t border-slate-800/60">
            <Link
              href="/student/quizzes/history"
              class="text-xs font-bold text-slate-400 hover:text-purple-400 transition-colors inline-flex items-center gap-1"
            >
              <span>{{ isKhmer ? 'មើលប្រវត្តិប្រឡងទាំងអស់' : 'View All Quiz History' }}</span>
              <span>&gt;</span>
            </Link>
          </div>
        </div>

        <!-- RIGHT (1/3): Recent Quizzes Card -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          <div class="border-b border-slate-800/60 pb-3 flex items-center justify-between">
            <h2 class="text-base font-bold text-white tracking-tight">
              {{ isKhmer ? 'តេស្តចុងក្រោយ' : 'Recent Quizzes' }}
            </h2>
            <Link
              href="/student/quizzes/history"
              class="text-xs font-bold text-purple-400 hover:text-purple-300 transition-colors"
            >
              {{ isKhmer ? 'មើលទាំងអស់' : 'View All' }}
            </Link>
          </div>

          <div v-if="recentQuizzes.length === 0" class="py-8 text-center space-y-2">
            <p class="text-xs font-bold text-slate-300">
              {{ isKhmer ? 'មិនទាន់មានតេស្តថ្មីៗទេ' : 'No recent quizzes yet' }}
            </p>
            <p class="text-[11px] text-slate-400">
              {{ isKhmer ? 'ចូលទៅកាន់ផ្នែក Quizzes ដើម្បីចាប់ផ្តើមសាកល្បងសមត្ថភាព។' : 'Visit Quizzes to take your first assessment.' }}
            </p>
          </div>

          <div v-else class="space-y-3 pt-1">
            <div
              v-for="item in recentQuizzes"
              :key="item.id"
              class="p-2.5 rounded-2xl bg-slate-900/60 hover:bg-slate-900 border border-slate-800/60 transition-all flex items-center justify-between gap-3 group"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div class="px-2 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-center shrink-0">
                  <p class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">
                    {{ splitDateBadge(item.date_badge).month }}
                  </p>
                  <p class="text-xs font-black text-white font-mono">
                    {{ splitDateBadge(item.date_badge).day }}
                  </p>
                </div>

                <div class="min-w-0">
                  <p class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors truncate">
                    {{ item.title }}
                  </p>
                  <p class="text-[10px] text-slate-400 truncate">{{ item.course }}</p>
                </div>
              </div>

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
            <h2 class="text-base font-bold text-white tracking-tight">
              {{ isKhmer ? 'លទ្ធផលតាមមុខវិជ្ជា / វគ្គសិក្សា' : 'Performance by Course / Topic' }}
            </h2>
            <Link
              href="/student/ai-path/weak-topics"
              class="text-xs font-bold text-purple-400 hover:text-purple-300 transition-colors"
            >
              {{ isKhmer ? 'មើលទាំងអស់' : 'View All' }}
            </Link>
          </div>

          <div v-if="topics.length === 0" class="py-8 text-center space-y-2">
            <p class="text-xs font-bold text-slate-300">
              {{ isKhmer ? 'មិនទាន់មានទិន្នន័យពិន្ទុតាមមុខវិជ្ជានៅឡើយទេ' : 'No course quiz averages available yet' }}
            </p>
            <p class="text-[11px] text-slate-400">
              {{ isKhmer ? 'បញ្ចប់យ៉ាងហោចណាស់ ១ តេស្ត ដើម្បីមើលការប្រៀបធៀបពិន្ទុតាមមុខវិជ្ជា។' : 'Complete at least one quiz to compare your mastery across courses.' }}
            </p>
          </div>

          <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 pt-1">
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
          <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-purple-600/15 rounded-full blur-3xl pointer-events-none"></div>

          <div>
            <div class="flex items-center justify-between border-b border-purple-900/40 pb-3">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-purple-600/20 border border-purple-500/40 flex items-center justify-center text-2xl shadow-inner">
                  🤖
                </div>
                <div>
                  <h2 class="text-base font-bold text-white tracking-tight">AI Insight</h2>
                  <p class="text-[10px] text-purple-300">{{ isKhmer ? 'ការណែនាំឆ្លាតវៃ' : 'Intelligent Recommendation' }}</p>
                </div>
              </div>

              <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-black uppercase tracking-wider">
                AI
              </span>
            </div>

            <div class="space-y-2.5 mt-4 text-xs">
              <div class="p-2.5 rounded-xl bg-purple-950/40 border border-purple-900/30 text-slate-200 flex items-start gap-2">
                <span class="text-emerald-400 font-bold shrink-0">✓</span>
                <p>{{ aiInsight.primary }}</p>
              </div>

              <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-slate-300 flex items-start gap-2">
                <span class="text-amber-400 font-bold shrink-0">💡</span>
                <p>{{ aiInsight.secondary }}</p>
              </div>
            </div>
          </div>

          <button
            type="button"
            @click="isStudyPlanModalOpen = true"
            class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/50 transition-all flex items-center justify-center gap-2 cursor-pointer z-10"
          >
            <span>✨</span>
            <span>{{ isKhmer ? 'មើលផែនការសិក្សា AI' : 'Get AI Study Plan' }}</span>
            <span>&gt;</span>
          </button>
        </div>

      </div>

    </div>

    <!-- ================= MODAL 1: AI STUDY PLAN MODAL ================= -->
    <div
      v-if="isStudyPlanModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4"
      @click="isStudyPlanModalOpen = false"
    >
      <div
        class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl relative"
        @click.stop
      >
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <span class="text-2xl">🤖</span>
            <div>
              <h3 class="text-base font-black text-white">
                {{ isKhmer ? 'ផែនការសិក្សាផ្ទាល់ខ្លួនពី AI' : 'Personalized AI Weekly Study Plan' }}
              </h3>
              <p class="text-[11px] text-purple-300">
                {{ isKhmer ? 'បង្កើតឡើងពីវគ្គសិក្សា និងលទ្ធផលប្រឡងពិតរបស់អ្នក' : 'Generated from your real enrolled courses and quiz performance' }}
              </p>
            </div>
          </div>
          <button
            type="button"
            @click="isStudyPlanModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer"
          >
            ✕
          </button>
        </div>

        <div v-if="!aiInsight.study_plan || aiInsight.study_plan.length === 0" class="py-6 text-center space-y-2 text-xs">
          <p class="font-bold text-white">
            {{ isKhmer ? 'មិនទាន់មានវគ្គសិក្សាសម្រាប់បង្កើតផែនការទេ' : 'No enrolled courses or quiz data yet' }}
          </p>
          <p class="text-slate-400">
            {{ isKhmer ? 'សូមចុះឈ្មោះចូលរៀនវគ្គសិក្សាជាមុនសិន ដើម្បីឱ្យ AI រៀបចំផែនការសិក្សាជូនអ្នក។' : 'Enroll in a course first so AI can build a tailored study schedule.' }}
          </p>
          <div class="pt-2">
            <Link
              href="/student/browse"
              class="inline-flex px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold"
            >
              {{ isKhmer ? 'រុករកវគ្គសិក្សា →' : 'Browse Courses →' }}
            </Link>
          </div>
        </div>

        <div v-else class="space-y-3 max-h-80 overflow-y-auto custom-scrollbar pr-1">
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
              type="button"
              @click="isStudyPlanModalOpen = false"
              class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold cursor-pointer"
            >
              {{ isKhmer ? 'បិទ' : 'Close' }}
            </button>
            <Link
              href="/student/ai-path/recommended"
              class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md"
            >
              {{ isKhmer ? 'បើកផ្លូវសិក្សា AI' : 'Launch Learning Path' }}
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 2: FULL DETAILED REPORT MODAL ================= -->
    <div
      v-if="isReportModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4"
      @click="isReportModalOpen = false"
    >
      <div
        class="bg-slate-900 border border-slate-700 rounded-3xl max-w-2xl w-full p-6 space-y-4 shadow-2xl relative"
        @click.stop
      >
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-white flex items-center gap-2">
              <span>📊</span>
              <span>{{ isKhmer ? 'របាយការណ៍លទ្ធផលប្រឡងសរុប' : 'Comprehensive Quiz Analytics Summary' }}</span>
            </h3>
            <p class="text-[11px] text-slate-400">
              {{ isKhmer ? 'ទិន្នន័យពិតប្រាកដពីប្រវត្តិការប្រឡងរបស់អ្នក' : 'Live performance summary from your submitted quiz attempts' }}
            </p>
          </div>
          <button
            type="button"
            @click="isReportModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer"
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
            <p class="text-slate-400 text-[10px]">Weekly Change</p>
            <p class="text-lg font-black text-purple-400 font-mono mt-1">{{ weeklySummary.score_change }}</p>
          </div>
        </div>

        <div class="space-y-2 text-xs">
          <h4 class="font-bold text-white uppercase text-[10px]">Difficulty Breakdown</h4>
          <div class="space-y-1.5">
            <div class="flex items-center justify-between text-[11px] text-slate-300">
              <span>Easy Questions ({{ difficulty.easy }}%)</span>
              <span class="text-emerald-400 font-bold">{{ difficulty.easy }}%</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full" :style="{ width: `${difficulty.easy}%` }"></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-300 pt-1">
              <span>Medium Questions ({{ difficulty.medium }}%)</span>
              <span class="text-blue-400 font-bold">{{ difficulty.medium }}%</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
              <div class="h-full bg-blue-500 rounded-full" :style="{ width: `${difficulty.medium}%` }"></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-300 pt-1">
              <span>Hard Questions ({{ difficulty.hard }}%)</span>
              <span class="text-purple-400 font-bold">{{ difficulty.hard }}%</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
              <div class="h-full bg-purple-500 rounded-full" :style="{ width: `${difficulty.hard}%` }"></div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-300 pt-1">
              <span>Expert Questions ({{ difficulty.expert }}%)</span>
              <span class="text-rose-400 font-bold">{{ difficulty.expert }}%</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
              <div class="h-full bg-rose-500 rounded-full" :style="{ width: `${difficulty.expert}%` }"></div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
          <button
            type="button"
            @click="isReportModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs cursor-pointer"
          >
            {{ isKhmer ? 'បិទ' : 'Close' }}
          </button>
          <Link
            href="/student/quizzes/history"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md"
          >
            {{ isKhmer ? 'ទៅកាន់ប្រវត្តិប្រឡង' : 'Go to Quiz History' }}
          </Link>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 3: ATTEMPT DETAIL MODAL ================= -->
    <div
      v-if="isAttemptDetailModalOpen && selectedAttempt"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4"
      @click="isAttemptDetailModalOpen = false"
    >
      <div
        class="bg-slate-900 border border-slate-700 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative"
        @click.stop
      >
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-white">{{ selectedAttempt.title }}</h3>
            <p class="text-[11px] text-slate-400">{{ selectedAttempt.course }} • {{ selectedAttempt.date }}</p>
          </div>
          <button
            type="button"
            @click="isAttemptDetailModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer"
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

        <!-- Real Question Breakdown -->
        <div class="space-y-2 text-xs max-h-48 overflow-y-auto custom-scrollbar">
          <p class="font-bold text-slate-300 text-[11px]">
            {{ isKhmer ? 'លទ្ធផលតាមសំណួរ៖' : 'Submitted Question Breakdown:' }}
          </p>

          <div
            v-if="!selectedAttempt.question_log || selectedAttempt.question_log.length === 0"
            class="p-3 bg-slate-950 rounded-xl border border-slate-800 text-slate-400 text-center"
          >
            {{ isKhmer ? 'មិនមានកំណត់ត្រាសំណួរលម្អិតសម្រាប់តេស្តនេះទេ។' : 'No per-question log available for this attempt.' }}
          </div>

          <div
            v-for="qItem in selectedAttempt.question_log"
            :key="qItem.index"
            class="p-2.5 bg-slate-950 rounded-xl border border-slate-800 space-y-0.5"
          >
            <p
              class="font-bold"
              :class="qItem.pending ? 'text-amber-400' : (qItem.correct ? 'text-emerald-400' : 'text-rose-400')"
            >
              {{ qItem.pending ? '⏳' : (qItem.correct ? '✓' : '✕') }} Q{{ qItem.index }}: {{ qItem.question }}
            </p>
            <p class="text-[10px] text-slate-400">
              {{ qItem.pending ? 'Manual grading' : (qItem.correct ? `Correct (+${qItem.points} pts)` : 'Incorrect (0 pts)') }}
            </p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
          <button
            type="button"
            @click="isAttemptDetailModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs cursor-pointer"
          >
            {{ isKhmer ? 'បិទ' : 'Close' }}
          </button>
          <Link
            :href="selectedAttempt.quiz_id ? `/student/quiz/${selectedAttempt.quiz_id}` : '/student/quizzes'"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md"
          >
            {{ isKhmer ? 'បើកតេស្ត Quiz' : 'Open Quiz' }}
          </Link>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 4: TOPIC QUICK DRILL MODAL ================= -->
    <div
      v-if="isTopicDrillModalOpen && selectedTopicDrill"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4"
      @click="isTopicDrillModalOpen = false"
    >
      <div
        class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative"
        @click.stop
      >
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-white">Target Topic Review</h3>
            <p class="text-[11px] text-purple-300">{{ selectedTopicDrill.title }}</p>
          </div>
          <button
            type="button"
            @click="isTopicDrillModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer"
          >
            ✕
          </button>
        </div>

        <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 space-y-2 text-xs">
          <div class="flex items-center justify-between">
            <span class="text-slate-400">Current Score:</span>
            <span class="font-mono font-bold text-rose-400">{{ selectedTopicDrill.score }}%</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-400">Status:</span>
            <span class="text-rose-400 font-bold">Needs Targeted Review</span>
          </div>
          <p class="text-[11px] text-slate-300 pt-1">
            💡 <strong>Recommendation:</strong> {{ selectedTopicDrill.recommendation }}
          </p>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800 text-xs">
          <button
            type="button"
            @click="isTopicDrillModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold cursor-pointer"
          >
            {{ isKhmer ? 'បិទ' : 'Close' }}
          </button>
          <Link
            href="/student/ai-path/weak-topics"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md"
          >
            {{ isKhmer ? 'ទៅកាន់ការរំលឹកចំណុចខ្សោយ' : 'Open Weak Topics' }}
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
