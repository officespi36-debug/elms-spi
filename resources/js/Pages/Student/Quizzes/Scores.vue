<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface ResultRow {
  id: number
  title: string
  subtitle?: string
  course: string
  score: string
  percentage: number
  correct_total: string
  time_taken: string
  completed_on: string
  result: string
  result_type: 'passed' | 'failed'
  code?: string
  icon_bg?: string
  questions?: Array<{
    q: string
    user_ans: string
    correct_ans: string
    is_correct: boolean
    points: number
    explanation?: string
  }>
}

interface DistributionSegment {
  label: string
  count: number
  percentage: number
  color: string
  class?: string
}

interface TrendPoint {
  date: string
  percentage: number
}

interface CourseScore {
  course: string
  percentage: number
  color: string
}

interface AchievementItem {
  id: number
  title: string
  subtitle: string
  date: string
  icon: string
  icon_bg: string
}

const props = defineProps<{
  analytics?: {
    summary: {
      average_score: number
      average_note: string
      highest_score: number
      highest_title: string
      lowest_score: number
      lowest_title: string
      quizzes_taken: number
      taken_trend: string
      passed_count: number
      passed_note: string
    }
    performance_overview: {
      average_score: number
      distribution: DistributionSegment[]
    }
    score_trend: {
      points: TrendPoint[]
      highlight?: {
        date: string
        score_text: string
        x_percent: number
        y_percent: number
      }
    }
    score_by_course: CourseScore[]
    result_distribution: {
      total_quizzes: number
      items: DistributionSegment[]
    }
    score_comparison: {
      this_month: number
      last_month: number
      trend_text: string
      is_improved: boolean
    }
    recent_achievements: AchievementItem[]
    weak_topics: string[]
    results: ResultRow[]
    total_count: number
    current_page: number
    per_page: number
  }
  filters?: {
    status: string
    course: string
    category: string
    difficulty: string
    date_range: string
    search: string
    page: number
  }
}>()

// Default baseline data
const defaultSummary = {
  average_score: 72,
  average_note: 'Good job! Keep it up.',
  highest_score: 95,
  highest_title: 'JavaScript Advanced Quiz',
  lowest_score: 35,
  lowest_title: 'SQL JOINS Quiz',
  quizzes_taken: 12,
  taken_trend: '+3 vs last month',
  passed_count: 8,
  passed_note: '67% pass rate',
}

const defaultPerformanceDistribution: DistributionSegment[] = [
  { label: '90 - 100%', count: 2, percentage: 17, color: '#10B981', class: 'text-emerald-400' },
  { label: '70 - 89%',  count: 6, percentage: 50, color: '#3B82F6', class: 'text-blue-400' },
  { label: '50 - 69%',  count: 3, percentage: 25, color: '#F59E0B', class: 'text-amber-400' },
  { label: 'Below 50%', count: 1, percentage: 8,  color: '#EF4444', class: 'text-rose-400' },
]

const defaultScoreTrend = {
  points: [
    { date: 'May 1',  percentage: 50 },
    { date: 'May 8',  percentage: 35 },
    { date: 'May 15', percentage: 55 },
    { date: 'May 22', percentage: 40 },
    { date: 'May 29', percentage: 75 },
    { date: 'Jun 1',  percentage: 95 },
  ],
  highlight: {
    date: 'May 24, 2025',
    score_text: 'Score: 75%',
    x_percent: 65,
    y_percent: 38,
  }
}

const defaultScoreByCourse: CourseScore[] = [
  { course: 'JavaScript Advanced',   percentage: 85, color: 'from-cyan-400 to-blue-500' },
  { course: 'React.js Fundamentals', percentage: 68, color: 'from-cyan-400 to-blue-500' },
  { course: 'Database Design',       percentage: 45, color: 'from-amber-400 to-orange-500' },
  { course: 'Web Development',       percentage: 78, color: 'from-cyan-400 to-blue-500' },
  { course: 'Python Programming',    percentage: 70, color: 'from-cyan-400 to-blue-500' },
]

const defaultResultDistribution = {
  total_quizzes: 12,
  items: [
    { label: 'Passed',      count: 8, percentage: 67, color: '#10B981' },
    { label: 'Failed',      count: 3, percentage: 25, color: '#EF4444' },
    { label: 'In Progress', count: 1, percentage: 8,  color: '#F59E0B' },
  ]
}

const defaultRecentAchievements: AchievementItem[] = [
  { id: 1, title: 'Perfect Score', subtitle: 'Scored 100% in a quiz', date: 'May 28, 2025', icon: '🛡️', icon_bg: 'bg-emerald-500/20 border border-emerald-500/30 text-emerald-300' },
  { id: 2, title: 'Quiz Master', subtitle: 'Completed 10 quizzes', date: 'May 25, 2025', icon: '🛡️', icon_bg: 'bg-purple-500/20 border border-purple-500/30 text-purple-300' },
  { id: 3, title: 'Consistent Learner', subtitle: '7 days quiz streak', date: 'May 22, 2025', icon: '🔥', icon_bg: 'bg-orange-500/20 border border-orange-500/30 text-orange-300' },
]

const defaultResults: ResultRow[] = [
  { id: 1, title: 'JavaScript Advanced Quiz', subtitle: 'Advanced JavaScript concepts', course: 'JavaScript Advanced', score: '19 / 20', percentage: 95, correct_total: '19 / 20', time_taken: '28m 15s', completed_on: 'Jun 1, 2025 10:30 AM', result: 'Passed', result_type: 'passed', code: 'JS', icon_bg: 'from-amber-400 to-amber-500 text-slate-950' },
  { id: 2, title: 'React Components Quiz', subtitle: 'React.js Fundamentals', course: 'React.js Fundamentals', score: '17 / 25', percentage: 68, correct_total: '17 / 25', time_taken: '32m 45s', completed_on: 'Jun 1, 2025 09:15 AM', result: 'Passed', result_type: 'passed', code: '⚛️', icon_bg: 'from-cyan-400 to-blue-500 text-white' },
  { id: 3, title: 'SQL JOINS Quiz', subtitle: 'Database Systems', course: 'Database Design', score: '7 / 20', percentage: 35, correct_total: '7 / 20', time_taken: '25m 10s', completed_on: 'May 31, 2025 08:45 PM', result: 'Failed', result_type: 'failed', code: '🗄️', icon_bg: 'from-blue-500 to-indigo-600 text-white' },
  { id: 4, title: 'HTML & CSS Quiz', subtitle: 'Web Development', course: 'Web Development', score: '22 / 25', percentage: 88, correct_total: '22 / 25', time_taken: '30m 00s', completed_on: 'May 31, 2025 04:20 PM', result: 'Passed', result_type: 'passed', code: '5', icon_bg: 'from-orange-500 to-amber-500 text-white' },
  { id: 5, title: 'Node.js Basics Quiz', subtitle: 'Node.js Fundamentals', course: 'Backend Development', score: '15 / 25', percentage: 60, correct_total: '15 / 25', time_taken: '35m 45s', completed_on: 'May 30, 2025 07:30 PM', result: 'Passed', result_type: 'passed', code: '🟢', icon_bg: 'from-emerald-500 to-teal-600 text-white' },
  { id: 6, title: 'Python Functions Quiz', subtitle: 'Python Programming', course: 'Python Programming', score: '18 / 20', percentage: 90, correct_total: '18 / 20', time_taken: '22m 30s', completed_on: 'May 30, 2025 02:10 PM', result: 'Passed', result_type: 'passed', code: '🐍', icon_bg: 'from-blue-400 to-amber-400 text-white' },
  { id: 7, title: 'Git & GitHub Quiz', subtitle: 'Version Control', course: 'DevOps Tools', score: '8 / 15', percentage: 53, correct_total: '8 / 15', time_taken: '18m 20s', completed_on: 'May 29, 2025 09:05 AM', result: 'Failed', result_type: 'failed', code: '🐙', icon_bg: 'from-rose-500 to-orange-500 text-white' },
  { id: 8, title: 'JavaScript Functions Quiz', subtitle: 'Functions and Scope', course: 'JavaScript Basics', score: '14 / 20', percentage: 70, correct_total: '14 / 20', time_taken: '25m 30s', completed_on: 'May 28, 2025 06:45 PM', result: 'Passed', result_type: 'passed', code: 'JS', icon_bg: 'from-amber-400 to-amber-500 text-slate-950' },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const performanceDistribution = computed(() => props.analytics?.performance_overview?.distribution || defaultPerformanceDistribution)
const scoreTrend = computed(() => props.analytics?.score_trend || defaultScoreTrend)
const scoreByCourse = computed(() => props.analytics?.score_by_course || defaultScoreByCourse)
const resultDistribution = computed(() => props.analytics?.result_distribution || defaultResultDistribution)
const scoreComparison = computed(() => props.analytics?.score_comparison || { this_month: 72, last_month: 64, trend_text: '+8% vs last month', is_improved: true })
const recentAchievements = computed(() => props.analytics?.recent_achievements || defaultRecentAchievements)
const weakTopics = computed(() => props.analytics?.weak_topics || ['SQL JOINS', 'Database Queries', 'React Hooks'])
const results = computed(() => props.analytics?.results || defaultResults)

// Filters State
const activeStatusTab = ref<string>(props.filters?.status || 'all')
const selectedDateRange = ref<string>(props.filters?.date_range || 'all')
const selectedCourse = ref<string>(props.filters?.course || 'all')
const selectedCategory = ref<string>(props.filters?.category || 'all')
const selectedDifficulty = ref<string>(props.filters?.difficulty || 'all')

// Modals State
const selectedResultForModal = ref<ResultRow | null>(null)
const isResultDetailModalOpen = ref(false)
const isAiPlanModalOpen = ref(false)

const openResultModal = (row: ResultRow) => {
  selectedResultForModal.value = row
  isResultDetailModalOpen.value = true
}

const handleFilterChange = (overrideTab?: string) => {
  if (overrideTab) {
    activeStatusTab.value = overrideTab
  }
  router.get('/student/quizzes/scores', {
    status: activeStatusTab.value,
    course: selectedCourse.value,
    category: selectedCategory.value,
    difficulty: selectedDifficulty.value,
    date_range: selectedDateRange.value,
    page: 1,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Line chart calculations
const chartWidth = 260
const chartHeight = 110
const padding = 18

const svgPath = computed(() => {
  const points = scoreTrend.value.points
  if (!points || points.length < 2) return ''
  const stepX = (chartWidth - padding * 2) / (points.length - 1)
  
  return points.map((p, i) => {
    const x = padding + i * stepX
    const y = chartHeight - padding - (p.percentage / 100) * (chartHeight - padding * 2)
    return `${i === 0 ? 'M' : 'L'} ${x} ${y}`
  }).join(' ')
})

const svgPoints = computed(() => {
  const points = scoreTrend.value.points || []
  const stepX = (chartWidth - padding * 2) / (points.length - 1)
  return points.map((p, i) => ({
    x: padding + i * stepX,
    y: chartHeight - padding - (p.percentage / 100) * (chartHeight - padding * 2),
    label: p.date,
    value: p.percentage,
  }))
})
</script>

<template>
  <StudentLayout title="Quiz Results — Quizzes & Assessments">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
            <span>Quiz Results</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">📄</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            Review your quiz results and analyze your performance.
          </p>
        </div>
      </div>

      <!-- ================= 2. TOP FILTER BAR ================= -->
      <div class="flex flex-wrap items-center justify-between gap-3 bg-[#0F172A]/80 border border-slate-800/80 rounded-2xl p-2.5 shadow-lg">
        <div class="flex flex-wrap items-center gap-2 flex-1">
          <!-- Date Range Picker -->
          <button class="bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-1.5 text-xs text-slate-300 hover:text-white flex items-center gap-1.5 cursor-pointer">
            <span>📅</span>
            <span>This Month (May 1 - May 31, 2025)</span>
            <span class="text-[10px] text-slate-500">▼</span>
          </button>

          <!-- Course Dropdown -->
          <select
            v-model="selectedCourse"
            @change="handleFilterChange()"
            class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="all">All Courses</option>
            <option value="JavaScript Advanced">JavaScript Advanced</option>
            <option value="React.js Fundamentals">React.js Fundamentals</option>
            <option value="Database Design">Database Design</option>
            <option value="Web Development">Web Development</option>
            <option value="Python Programming">Python Programming</option>
          </select>

          <!-- Category Dropdown -->
          <select
            v-model="selectedCategory"
            @change="handleFilterChange()"
            class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="all">All Categories</option>
            <option value="Front-End">Front-End</option>
            <option value="Back-End">Back-End</option>
            <option value="Database">Database</option>
          </select>

          <!-- Difficulty Dropdown -->
          <select
            v-model="selectedDifficulty"
            @change="handleFilterChange()"
            class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="all">All Difficulty</option>
            <option value="Easy">Easy</option>
            <option value="Medium">Medium</option>
            <option value="Hard">Hard</option>
          </select>
        </div>

        <button class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700/80 text-slate-300 hover:text-white text-xs font-medium flex items-center gap-1.5 cursor-pointer">
          <span>⚡</span>
          <span>Filters</span>
          <span class="text-[10px] text-slate-500">▼</span>
        </button>
      </div>

      <!-- ================= 3. 5 TOP SUMMARY CARDS ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        
        <!-- Card 1: Average Score with Mini Circular Gauge -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Average Score</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.average_score }}%</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ summary.average_note }}</p>
          </div>

          <!-- Mini Donut Ring -->
          <div class="relative w-10 h-10 flex items-center justify-center shrink-0">
            <svg class="w-10 h-10 -rotate-90 transform" viewBox="0 0 36 36">
              <path class="text-slate-800" stroke-width="4" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
              <path class="text-purple-500" stroke-dasharray="72, 100" stroke-width="4" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
            </svg>
          </div>
        </div>

        <!-- Card 2: Highest Score -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-emerald-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Highest Score</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.highest_score }}%</p>
            <p class="text-[10px] text-slate-400 font-medium truncate max-w-[110px]">{{ summary.highest_title }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🎯
          </div>
        </div>

        <!-- Card 3: Lowest Score -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-amber-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Lowest Score</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.lowest_score }}%</p>
            <p class="text-[10px] text-slate-400 font-medium truncate max-w-[110px]">{{ summary.lowest_title }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-amber-600/20 border border-amber-500/30 text-amber-400 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📉
          </div>
        </div>

        <!-- Card 4: Quizzes Taken -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-blue-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Quizzes Taken</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.quizzes_taken }}</p>
            <p class="text-[10px] text-emerald-400 font-medium font-mono">{{ summary.taken_trend }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-blue-600/20 border border-blue-500/30 text-blue-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📅
          </div>
        </div>

        <!-- Card 5: Passed -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-emerald-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Passed</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.passed_count }}</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ summary.passed_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            ✓
          </div>
        </div>

      </div>

      <!-- ================= 4. MAIN SPLIT (LEFT: 8/12, RIGHT: 4/12) ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT (8/12): STATUS TABS + RESULTS TABLE + 3 BOTTOM CARDS ================= -->
        <div class="lg:col-span-8 space-y-6">

          <!-- Result Status Tabs -->
          <div class="flex items-center gap-1.5 bg-[#0F172A]/80 border border-slate-800/80 rounded-2xl p-1.5 shadow-lg overflow-x-auto custom-scrollbar">
            <button
              v-for="tab in [
                { key: 'all', label: 'All Results' },
                { key: 'passed', label: 'Passed' },
                { key: 'failed', label: 'Failed' },
                { key: 'needs_improvement', label: 'Needs Improvement' }
              ]"
              :key="tab.key"
              @click="handleFilterChange(tab.key)"
              :class="[
                activeStatusTab === tab.key
                  ? 'bg-purple-600 text-white shadow-md shadow-purple-900/40'
                  : 'text-slate-400 hover:text-white',
                'px-4 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer'
              ]"
            >
              {{ tab.label }}
            </button>
          </div>

          <!-- QUIZ RESULTS TABLE -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl shadow-xl overflow-hidden">
            <div class="p-4 border-b border-slate-800/80">
              <h2 class="text-sm font-bold text-white tracking-tight">Quiz Results</h2>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
              <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-slate-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-800/80">
                  <tr>
                    <th class="p-3.5 pl-5">Quiz Title</th>
                    <th class="p-3.5">Course</th>
                    <th class="p-3.5 text-center">Score</th>
                    <th class="p-3.5 text-center">Percentage</th>
                    <th class="p-3.5 text-center">Correct / Total</th>
                    <th class="p-3.5 text-center">Time Taken</th>
                    <th class="p-3.5">Completed On</th>
                    <th class="p-3.5 text-center">Result</th>
                    <th class="p-3.5 pr-5 text-right">Action</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-slate-800/50">
                  <tr
                    v-for="row in results"
                    :key="row.id"
                    class="hover:bg-slate-800/30 transition-colors group cursor-pointer"
                    @click="openResultModal(row)"
                  >
                    <!-- Quiz Title -->
                    <td class="p-3.5 pl-5">
                      <div class="flex items-center gap-3">
                        <div
                          :class="[
                            row.icon_bg || 'from-purple-500 to-indigo-600 text-white',
                            'w-7 h-7 rounded-lg bg-gradient-to-br flex items-center justify-center text-[10px] font-black shrink-0 font-mono shadow-sm'
                          ]"
                        >
                          {{ row.code || 'JS' }}
                        </div>
                        <div class="min-w-0">
                          <p class="font-bold text-white group-hover:text-purple-300 transition-colors truncate text-xs">
                            {{ row.title }}
                          </p>
                          <p class="text-[10px] text-slate-400 truncate">
                            {{ row.subtitle || row.course }}
                          </p>
                        </div>
                      </div>
                    </td>

                    <!-- Course -->
                    <td class="p-3.5 text-slate-300 font-medium text-xs whitespace-nowrap">
                      {{ row.course }}
                    </td>

                    <!-- Score -->
                    <td class="p-3.5 text-center font-mono text-xs font-bold text-white whitespace-nowrap">
                      {{ row.score }}
                    </td>

                    <!-- Percentage -->
                    <td class="p-3.5 text-center font-mono text-xs font-bold">
                      <span
                        :class="[
                          row.percentage >= 80 ? 'text-emerald-400' :
                          row.percentage >= 60 ? 'text-amber-400' :
                          'text-rose-400'
                        ]"
                      >
                        {{ row.percentage }}%
                      </span>
                    </td>

                    <!-- Correct / Total -->
                    <td class="p-3.5 text-center font-mono text-xs text-slate-300 whitespace-nowrap">
                      {{ row.correct_total }}
                    </td>

                    <!-- Time Taken -->
                    <td class="p-3.5 text-center font-mono text-xs text-slate-400 whitespace-nowrap">
                      {{ row.time_taken }}
                    </td>

                    <!-- Completed On -->
                    <td class="p-3.5 whitespace-nowrap">
                      <p class="text-xs text-slate-300 font-medium">{{ row.completed_on.split(' ')[0] + ' ' + row.completed_on.split(' ')[1] + ' ' + row.completed_on.split(' ')[2] }}</p>
                      <p class="text-[10px] text-slate-500 font-mono">{{ row.completed_on.split(' ')[3] + ' ' + (row.completed_on.split(' ')[4] || '') }}</p>
                    </td>

                    <!-- Result Pill -->
                    <td class="p-3.5 text-center whitespace-nowrap">
                      <span
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block"
                        :class="[
                          row.result_type === 'passed' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' :
                          'bg-rose-500/20 text-rose-300 border-rose-500/30'
                        ]"
                      >
                        {{ row.result }}
                      </span>
                    </td>

                    <!-- Actions -->
                    <td class="p-3.5 pr-5 text-right whitespace-nowrap" @click.stop>
                      <div class="flex items-center justify-end gap-1.5">
                        <button
                          @click="openResultModal(row)"
                          class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-700/80 hover:border-purple-500/40 text-slate-400 hover:text-white flex items-center justify-center text-xs transition-colors"
                          title="View Result Details"
                        >
                          👁
                        </button>
                        <Link
                          href="/student/progress/weekly"
                          class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-700/80 hover:border-purple-500/40 text-slate-400 hover:text-white flex items-center justify-center text-xs transition-colors"
                          title="View Analytics"
                        >
                          📊
                        </Link>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- TABLE FOOTER & PAGINATION -->
            <div class="p-4 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
              <span class="text-slate-400 text-[11px]">
                Showing 1 to 8 of 12 results
              </span>

              <div class="flex items-center gap-1.5">
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs disabled:opacity-40">
                  «
                </button>
                <button class="w-7 h-7 rounded-lg bg-purple-600 text-white font-bold text-xs shadow-sm">
                  1
                </button>
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs">
                  2
                </button>
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs">
                  ›
                </button>
              </div>
            </div>

          </div>

          <!-- ================= BOTTOM 3 ANALYTICS CARDS ROW ================= -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- Card 1: Score by Course -->
            <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-4 shadow-xl space-y-3">
              <div class="flex items-center justify-between border-b border-slate-800/60 pb-2">
                <h3 class="text-xs font-bold text-white tracking-tight">Score by Course</h3>
                <span class="text-[10px] text-slate-400 font-mono">This Month ▾</span>
              </div>

              <div class="space-y-2.5 pt-1">
                <div
                  v-for="c in scoreByCourse"
                  :key="c.course"
                  class="space-y-1"
                >
                  <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-300 truncate max-w-[130px]">{{ c.course }}</span>
                    <span class="font-bold text-white font-mono">{{ c.percentage }}%</span>
                  </div>
                  <div class="w-full h-1.5 bg-slate-950 rounded-full overflow-hidden">
                    <div
                      class="h-full rounded-full bg-gradient-to-r"
                      :class="c.color"
                      :style="{ width: `${c.percentage}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 2: Result Distribution Donut -->
            <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-4 shadow-xl space-y-3">
              <div class="flex items-center justify-between border-b border-slate-800/60 pb-2">
                <h3 class="text-xs font-bold text-white tracking-tight">Result Distribution</h3>
              </div>

              <div class="flex items-center justify-between gap-3 pt-1">
                <!-- Donut Chart -->
                <div class="relative w-20 h-20 flex items-center justify-center shrink-0">
                  <svg class="w-20 h-20 -rotate-90 transform" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#1E293B" stroke-width="4.5" />
                    <!-- Passed 67% -->
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#10B981" stroke-width="4.5" stroke-dasharray="67, 100" stroke-dashoffset="0" />
                    <!-- Failed 25% -->
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#EF4444" stroke-width="4.5" stroke-dasharray="25, 100" stroke-dashoffset="-67" />
                    <!-- In Progress 8% -->
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F59E0B" stroke-width="4.5" stroke-dasharray="8, 100" stroke-dashoffset="-92" />
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-sm font-black text-white font-mono">{{ resultDistribution.total_quizzes }}</span>
                    <span class="text-[7px] text-slate-400">Quizzes</span>
                  </div>
                </div>

                <!-- Legend -->
                <div class="space-y-1.5 text-xs flex-1">
                  <div
                    v-for="item in resultDistribution.items"
                    :key="item.label"
                    class="flex items-center justify-between text-[10px]"
                  >
                    <div class="flex items-center gap-1.5">
                      <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: item.color }"></span>
                      <span class="text-slate-300">{{ item.label }}</span>
                    </div>
                    <span class="font-bold text-white font-mono">{{ item.count }} ({{ item.percentage }}%)</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 3: Average Score Comparison -->
            <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-4 shadow-xl space-y-3 flex flex-col justify-between">
              <div class="flex items-center justify-between border-b border-slate-800/60 pb-2">
                <h3 class="text-xs font-bold text-white tracking-tight">Average Score Comparison</h3>
                <span class="text-[10px] text-slate-400 font-mono">This Month ▾</span>
              </div>

              <div class="grid grid-cols-2 gap-2 pt-1 text-center">
                <div class="p-3 bg-slate-950/70 border border-slate-800 rounded-2xl">
                  <p class="text-[10px] text-slate-400">This Month</p>
                  <p class="text-xl font-black text-white font-mono mt-0.5">{{ scoreComparison.this_month }}%</p>
                  <p class="text-[9px] text-emerald-400 font-mono mt-0.5">↑ {{ scoreComparison.trend_text }}</p>
                </div>

                <div class="p-3 bg-slate-950/70 border border-slate-800 rounded-2xl">
                  <p class="text-[10px] text-slate-400">Last Month</p>
                  <p class="text-xl font-black text-slate-400 font-mono mt-0.5">{{ scoreComparison.last_month }}%</p>
                </div>
              </div>
            </div>

          </div>

        </div>

        <!-- ================= RIGHT (4/12): WIDGETS SIDEBAR ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- WIDGET 1: Performance Overview Donut -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-white tracking-tight">Performance Overview</h3>
              <select
                class="bg-slate-900 border border-slate-700 text-[10px] font-semibold text-slate-300 rounded-lg px-2 py-0.5 focus:outline-none cursor-pointer"
              >
                <option>This Month</option>
                <option>All Time</option>
              </select>
            </div>

            <div class="flex items-center justify-between gap-4">
              <!-- Donut Chart -->
              <div class="relative w-24 h-24 flex items-center justify-center shrink-0">
                <svg class="w-24 h-24 -rotate-90 transform" viewBox="0 0 36 36">
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#1E293B" stroke-width="4.5" />
                  <!-- 90-100%: 17% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#10B981" stroke-width="4.5" stroke-dasharray="17, 100" stroke-dashoffset="0" />
                  <!-- 70-89%: 50% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#3B82F6" stroke-width="4.5" stroke-dasharray="50, 100" stroke-dashoffset="-17" />
                  <!-- 50-69%: 25% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F59E0B" stroke-width="4.5" stroke-dasharray="25, 100" stroke-dashoffset="-67" />
                  <!-- Below 50%: 8% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#EF4444" stroke-width="4.5" stroke-dasharray="8, 100" stroke-dashoffset="-92" />
                </svg>

                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-white font-mono leading-none">{{ summary.average_score }}%</span>
                  <span class="text-[8px] text-slate-400 mt-0.5 font-medium">Average Score</span>
                </div>
              </div>

              <!-- Legend Breakdown -->
              <div class="space-y-1.5 text-xs flex-1">
                <div
                  v-for="item in performanceDistribution"
                  :key="item.label"
                  class="flex items-center justify-between text-[11px]"
                >
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: item.color }"></span>
                    <span class="text-slate-300 font-medium">{{ item.label }}</span>
                  </div>
                  <span class="font-bold text-white font-mono">{{ item.count }} ({{ item.percentage }}%)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- WIDGET 2: Score Trend Line Chart with Tooltip Callout -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Score Trend</h3>
              <span class="text-[10px] text-slate-400 font-mono">This Month ▾</span>
            </div>

            <!-- SVG Chart Canvas -->
            <div class="relative w-full h-32 flex items-center justify-center">
              <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="w-full h-full overflow-visible">
                <!-- Grid Lines -->
                <line x1="18" y1="18" :x2="chartWidth - 18" y2="18" stroke="#1E293B" stroke-dasharray="2 2" stroke-width="1" />
                <line x1="18" y1="45" :x2="chartWidth - 18" y2="45" stroke="#1E293B" stroke-dasharray="2 2" stroke-width="1" />
                <line x1="18" y1="72" :x2="chartWidth - 18" y2="72" stroke="#1E293B" stroke-dasharray="2 2" stroke-width="1" />
                <line x1="18" y1="92" :x2="chartWidth - 18" y2="92" stroke="#1E293B" stroke-width="1" />

                <!-- Left Labels -->
                <text x="2" y="21" fill="#64748B" font-size="6.5" font-family="monospace">100%</text>
                <text x="5" y="48" fill="#64748B" font-size="6.5" font-family="monospace">75%</text>
                <text x="5" y="75" fill="#64748B" font-size="6.5" font-family="monospace">50%</text>
                <text x="5" y="94" fill="#64748B" font-size="6.5" font-family="monospace">0%</text>

                <!-- Trend Line Path -->
                <path
                  :d="svgPath"
                  fill="none"
                  stroke="#A855F7"
                  stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />

                <!-- Data Dots -->
                <circle
                  v-for="(pt, idx) in svgPoints"
                  :key="idx"
                  :cx="pt.x"
                  :cy="pt.y"
                  r="3.5"
                  fill="#C084FC"
                  stroke="#0F172A"
                  stroke-width="1.5"
                  class="hover:r-5 transition-all cursor-pointer"
                />

                <!-- Bottom X Labels -->
                <text
                  v-for="(pt, idx) in svgPoints"
                  :key="'lbl-' + idx"
                  :x="pt.x"
                  :y="chartHeight + 1"
                  text-anchor="middle"
                  fill="#94A3B8"
                  font-size="6.5"
                  font-family="monospace"
                >
                  {{ pt.label }}
                </text>
              </svg>

              <!-- Highlight Callout Tooltip -->
              <div class="absolute top-2 right-8 px-2 py-1 rounded-lg bg-slate-900/95 border border-purple-500/50 shadow-xl text-[9px] font-mono pointer-events-none">
                <p class="text-slate-400">May 24, 2025</p>
                <p class="text-purple-300 font-bold">Score: 75%</p>
              </div>
            </div>
          </div>

          <!-- WIDGET 3: Recent Achievements -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Recent Achievements</h3>
              <span class="text-xs text-purple-400 font-bold hover:underline cursor-pointer">View All</span>
            </div>

            <div class="space-y-2">
              <div
                v-for="ach in recentAchievements"
                :key="ach.id"
                class="p-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/60 flex items-center justify-between gap-3 hover:border-purple-500/30 transition-all cursor-pointer"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      ach.icon_bg,
                      'w-7 h-7 rounded-xl flex items-center justify-center text-xs shrink-0 shadow-sm'
                    ]"
                  >
                    {{ ach.icon }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">{{ ach.title }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ ach.subtitle }}</p>
                  </div>
                </div>

                <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap shrink-0">
                  {{ ach.date }}
                </span>
              </div>
            </div>
          </div>

          <!-- WIDGET 4: Need Improvement? & AI Study Plan -->
          <div class="bg-gradient-to-br from-[#10132B] via-[#0F172A] to-[#1E1138] border border-purple-900/50 rounded-3xl p-5 shadow-2xl space-y-3.5">
            <div class="flex items-center justify-between border-b border-purple-900/40 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Need Improvement?</h3>
              <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 text-[10px] font-bold border border-purple-500/30">
                AI
              </span>
            </div>

            <p class="text-xs text-slate-400">
              Focus on these topics to improve your scores.
            </p>

            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="topic in weakTopics"
                :key="topic"
                class="px-2.5 py-1 rounded-xl bg-slate-900/80 border border-slate-700/80 text-xs font-medium text-slate-200"
              >
                {{ topic }}
              </span>
            </div>

            <button
              @click="isAiPlanModalOpen = true"
              class="w-full py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/50 transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95"
            >
              <span>✨</span>
              <span>Get AI Study Plan</span>
            </button>
          </div>

        </div>

      </div>

    </div>

    <!-- ================= MODAL: QUIZ RESULT DETAIL & QUESTION BREAKDOWN ================= -->
    <div
      v-if="isResultDetailModalOpen && selectedResultForModal"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-purple-600/30 border border-purple-500/40 text-purple-300 flex items-center justify-center text-xs font-bold font-mono">
              {{ selectedResultForModal.code || '📄' }}
            </div>
            <div>
              <h3 class="text-base font-black text-white">{{ selectedResultForModal.title }}</h3>
              <p class="text-[11px] text-purple-300">{{ selectedResultForModal.course }}</p>
            </div>
          </div>
          <button
            @click="isResultDetailModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <!-- Metrics Overview Bar -->
        <div class="grid grid-cols-4 gap-2 bg-slate-950 p-3 rounded-2xl border border-slate-800 text-center text-xs">
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Score</p>
            <p class="font-mono font-bold text-white">{{ selectedResultForModal.score }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Percentage</p>
            <p
              class="font-mono font-bold"
              :class="selectedResultForModal.percentage >= 70 ? 'text-emerald-400' : 'text-rose-400'"
            >
              {{ selectedResultForModal.percentage }}%
            </p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Time Taken</p>
            <p class="font-mono font-bold text-slate-300">{{ selectedResultForModal.time_taken }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Result</p>
            <p
              class="font-mono font-bold capitalize"
              :class="selectedResultForModal.result_type === 'passed' ? 'text-emerald-400' : 'text-rose-400'"
            >
              {{ selectedResultForModal.result }}
            </p>
          </div>
        </div>

        <!-- Question-by-Question Review -->
        <div class="space-y-2.5 max-h-60 overflow-y-auto custom-scrollbar pr-1">
          <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Question Review</h4>
          
          <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 space-y-1.5 text-xs">
            <p class="font-bold text-emerald-400 flex items-center gap-1.5">
              <span>✓ Q1 (+5 pts)</span>
              <span class="text-white font-medium">What is a JavaScript Closure?</span>
            </p>
            <p class="text-[11px] text-slate-300">Your Answer: <strong class="text-emerald-300">A function with access to its parent scope</strong></p>
          </div>

          <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 space-y-1.5 text-xs">
            <p class="font-bold text-emerald-400 flex items-center gap-1.5">
              <span>✓ Q2 (+5 pts)</span>
              <span class="text-white font-medium">What does Promise.race() do?</span>
            </p>
            <p class="text-[11px] text-slate-300">Your Answer: <strong class="text-emerald-300">Resolves as soon as the first promise resolves</strong></p>
          </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isResultDetailModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Close
          </button>

          <Link
            href="/student/quizzes/practice"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5"
          >
            <span>🔄</span>
            <span>Retake Practice Drill</span>
          </Link>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: AI STUDY PLAN ================= -->
    <div
      v-if="isAiPlanModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-2xl">🤖</span>
            <div>
              <h3 class="text-base font-black text-white">AI Personalized Study Plan</h3>
              <p class="text-[11px] text-purple-300">Generated from your latest quiz results</p>
            </div>
          </div>
          <button
            @click="isAiPlanModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 bg-slate-950 p-4 rounded-2xl border border-slate-800 text-xs text-slate-300">
          <p class="font-bold text-white flex items-center gap-2">
            <span>🎯</span>
            <span>Recommended Priority Drills:</span>
          </p>

          <div class="space-y-2">
            <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex items-start gap-2">
              <span class="text-rose-400 font-bold">1.</span>
              <div>
                <p class="font-bold text-white">SQL JOINS & Aggregations (35% Accuracy)</p>
                <p class="text-[11px] text-slate-400">Review Database Design module: Practice filtering JOIN outputs with GROUP BY and HAVING.</p>
              </div>
            </div>

            <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex items-start gap-2">
              <span class="text-amber-400 font-bold">2.</span>
              <div>
                <p class="font-bold text-white">Database Queries (45% Accuracy)</p>
                <p class="text-[11px] text-slate-400">Understand nested subqueries and table index optimization.</p>
              </div>
            </div>

            <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex items-start gap-2">
              <span class="text-amber-400 font-bold">3.</span>
              <div>
                <p class="font-bold text-white">React Hooks (68% Accuracy)</p>
                <p class="text-[11px] text-slate-400">Focus on state immutability in functional components.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isAiPlanModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Done
          </button>
          <Link
            href="/student/ai-tutor"
            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5"
          >
            <span>💬</span>
            <span>Practice with AI Tutor</span>
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
</style>
