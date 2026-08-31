<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface AttemptRow {
  id: number
  title: string
  subtitle?: string
  course: string
  attempt: string
  score: string
  percentage: number
  time_taken: string
  date: string
  time: string
  status: string
  status_type: 'passed' | 'completed' | 'failed' | 'in_progress'
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

interface ScoreRangeDistribution {
  label: string
  count: number
  percentage: number
  color: string
  class: string
}

interface TrendPoint {
  date: string
  percentage: number
}

interface RecentActivityItem {
  id: number
  title: string
  score: number
  date_str: string
  code: string
  icon_bg: string
}

const props = defineProps<{
  analytics?: {
    summary: {
      total_attempts: number
      total_note: string
      average_score: number
      average_trend: string
      highest_score: number
      highest_title: string
      lowest_score: number
      lowest_title: string
      passed_count: number
      passed_note: string
    }
    performance_overview: {
      average_score: number
      distribution: ScoreRangeDistribution[]
    }
    score_trend: {
      points: TrendPoint[]
    }
    recent_activity: RecentActivityItem[]
    weak_topics: string[]
    attempts: AttemptRow[]
    total_count: number
    current_page: number
    per_page: number
  }
  filters?: {
    status: string
    date_range: string
    search: string
    page: number
  }
}>()

// Default baseline data
const defaultSummary = {
  total_attempts: 28,
  total_note: 'All time attempts',
  average_score: 72,
  average_trend: '+8% vs last 30 days',
  highest_score: 95,
  highest_title: 'JavaScript Advanced Quiz',
  lowest_score: 35,
  lowest_title: 'SQL JOINS Quiz',
  passed_count: 18,
  passed_note: '64% of all attempts',
}

const defaultDistribution: ScoreRangeDistribution[] = [
  { label: '90 - 100%', count: 8, percentage: 29, color: '#10B981', class: 'text-emerald-400' },
  { label: '70 - 89%', count: 10, percentage: 36, color: '#3B82F6', class: 'text-blue-400' },
  { label: '50 - 69%', count: 6, percentage: 21, color: '#F59E0B', class: 'text-amber-400' },
  { label: 'Below 50%', count: 4, percentage: 14, color: '#EF4444', class: 'text-rose-400' },
]

const defaultTrendPoints: TrendPoint[] = [
  { date: 'May 20', percentage: 25 },
  { date: 'May 23', percentage: 50 },
  { date: 'May 26', percentage: 65 },
  { date: 'May 29', percentage: 45 },
  { date: 'Jun 1',  percentage: 95 },
]

const defaultRecentActivity: RecentActivityItem[] = [
  { id: 1, title: 'JavaScript Advanced Quiz', score: 95, date_str: '10:30 AM Jun 1', code: 'JS', icon_bg: 'from-amber-400 to-amber-500 text-slate-950' },
  { id: 2, title: 'React Components Quiz', score: 68, date_str: '09:15 AM Jun 1', code: '⚛️', icon_bg: 'from-cyan-400 to-blue-500 text-white' },
  { id: 3, title: 'SQL JOINS Quiz', score: 35, date_str: 'May 31', code: '🗄️', icon_bg: 'from-blue-500 to-indigo-600 text-white' },
  { id: 4, title: 'HTML & CSS Quiz', score: 88, date_str: 'May 31', code: '5', icon_bg: 'from-orange-500 to-amber-500 text-white' },
  { id: 5, title: 'Node.js Basics Quiz', score: 60, date_str: 'May 30', code: '🟢', icon_bg: 'from-emerald-500 to-teal-600 text-white' },
]

const defaultWeakTopics = ['SQL JOINS', 'React Hooks', 'Python Functions']

const defaultAttempts: AttemptRow[] = [
  { id: 1, title: 'JavaScript Advanced Quiz', subtitle: 'Advanced JavaScript concepts', course: 'JavaScript Advanced', attempt: '2 / 3', score: '19 / 20', percentage: 95, time_taken: '28m 15s', date: 'Jun 1, 2025', time: '10:30 AM', status: 'Passed', status_type: 'passed', code: 'JS', icon_bg: 'from-amber-400 to-amber-500 text-slate-950' },
  { id: 2, title: 'React Components Quiz', subtitle: 'React.js Fundamentals', course: 'React.js Fundamentals', attempt: '1 / 3', score: '17 / 25', percentage: 68, time_taken: '32m 45s', date: 'Jun 1, 2025', time: '09:15 AM', status: 'Completed', status_type: 'completed', code: '⚛️', icon_bg: 'from-cyan-400 to-blue-500 text-white' },
  { id: 3, title: 'SQL JOINS Quiz', subtitle: 'Database Systems', course: 'Database Design', attempt: '1 / 2', score: '7 / 20', percentage: 35, time_taken: '25m 10s', date: 'May 31, 2025', time: '08:45 PM', status: 'Failed', status_type: 'failed', code: '🗄️', icon_bg: 'from-blue-500 to-indigo-600 text-white' },
  { id: 4, title: 'HTML & CSS Quiz', subtitle: 'Web Development', course: 'Web Development', attempt: '1 / 3', score: '22 / 25', percentage: 88, time_taken: '30m 00s', date: 'May 31, 2025', time: '04:20 PM', status: 'Passed', status_type: 'passed', code: '5', icon_bg: 'from-orange-500 to-amber-500 text-white' },
  { id: 5, title: 'Node.js Basics Quiz', subtitle: 'Node.js Fundamentals', course: 'Node.js Fundamentals', attempt: '2 / 3', score: '15 / 25', percentage: 60, time_taken: '35m 45s', date: 'May 30, 2025', time: '07:30 PM', status: 'Completed', status_type: 'completed', code: '🟢', icon_bg: 'from-emerald-500 to-teal-600 text-white' },
  { id: 6, title: 'Python Functions Quiz', subtitle: 'Python Programming', course: 'Python Programming', attempt: '1 / 2', score: '18 / 20', percentage: 90, time_taken: '22m 30s', date: 'May 30, 2025', time: '02:10 PM', status: 'Passed', status_type: 'passed', code: '🐍', icon_bg: 'from-blue-400 to-amber-400 text-white' },
  { id: 7, title: 'Git & GitHub Quiz', subtitle: 'Version Control', course: 'DevOps Tools', attempt: '1 / 2', score: '8 / 15', percentage: 53, time_taken: '18m 20s', date: 'May 29, 2025', time: '09:05 AM', status: 'Completed', status_type: 'completed', code: '🐙', icon_bg: 'from-rose-500 to-orange-500 text-white' },
  { id: 8, title: 'JavaScript Functions Quiz', subtitle: 'Functions and Scope', course: 'JavaScript Basics', attempt: '3 / 3', score: '14 / 20', percentage: 70, time_taken: '25m 30s', date: 'May 28, 2025', time: '06:45 PM', status: 'Completed', status_type: 'completed', code: 'JS', icon_bg: 'from-amber-400 to-amber-500 text-slate-950' },
  { id: 9, title: 'CSS Flexbox Quiz', subtitle: 'CSS Layout', course: 'Web Design', attempt: '1 / 2', score: '20 / 20', percentage: 100, time_taken: '15m 10s', date: 'May 28, 2025', time: '03:20 PM', status: 'Passed', status_type: 'passed', code: '🎨', icon_bg: 'from-cyan-500 to-blue-600 text-white' },
  { id: 10, title: 'React Hooks Quiz', subtitle: 'React.js Advanced', course: 'React.js Advanced', attempt: '1 / 2', score: '10 / 25', percentage: 40, time_taken: '28m 40s', date: 'May 27, 2025', time: '08:30 PM', status: 'Failed', status_type: 'failed', code: '⚛️', icon_bg: 'from-cyan-400 to-blue-500 text-white' },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const distribution = computed(() => props.analytics?.performance_overview?.distribution || defaultDistribution)
const trendPoints = computed(() => props.analytics?.score_trend?.points || defaultTrendPoints)
const recentActivity = computed(() => props.analytics?.recent_activity || defaultRecentActivity)
const weakTopics = computed(() => props.analytics?.weak_topics || defaultWeakTopics)
const attempts = computed(() => props.analytics?.attempts || defaultAttempts)

// Filters State
const activeStatusTab = ref<string>(props.filters?.status || 'all')
const selectedDateRange = ref<string>(props.filters?.date_range || 'all')
const currentPage = ref<number>(props.filters?.page || 1)

// Modals State
const selectedAttemptForModal = ref<AttemptRow | null>(null)
const isAttemptDetailModalOpen = ref(false)
const isAiPlanModalOpen = ref(false)

const openAttemptModal = (row: AttemptRow) => {
  selectedAttemptForModal.value = row
  isAttemptDetailModalOpen.value = true
}

const handleTabChange = (tab: string) => {
  activeStatusTab.value = tab
  router.get('/student/quizzes/history', {
    status: tab,
    date_range: selectedDateRange.value,
    page: 1,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Generate SVG points for Score Trend Line Chart
const chartWidth = 260
const chartHeight = 120
const padding = 20

const svgPath = computed(() => {
  const points = trendPoints.value
  if (points.length < 2) return ''
  const stepX = (chartWidth - padding * 2) / (points.length - 1)
  
  return points.map((p, i) => {
    const x = padding + i * stepX
    const y = chartHeight - padding - (p.percentage / 100) * (chartHeight - padding * 2)
    return `${i === 0 ? 'M' : 'L'} ${x} ${y}`
  }).join(' ')
})

const svgPoints = computed(() => {
  const points = trendPoints.value
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
  <StudentLayout title="My Quiz Attempts — Quizzes & Assessments">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
            <span>My Quiz Attempts</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">📄</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            View all your quiz attempts and track your performance.
          </p>
        </div>

        <Link
          href="/student/quizzes/practice"
          class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition-all shadow-md shadow-purple-900/40 self-start sm:self-auto flex items-center gap-2"
        >
          <span>✨</span>
          <span>Take New Quiz</span>
        </Link>
      </div>

      <!-- ================= 2. 5 TOP SUMMARY CARDS ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        
        <!-- Card 1: Total Attempts -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Total Attempts</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.total_attempts }}</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ summary.total_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📋
          </div>
        </div>

        <!-- Card 2: Average Score -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-blue-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Average Score</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.average_score }}%</p>
            <p class="text-[10px] text-emerald-400 font-medium font-mono flex items-center gap-1">
              <span>↑</span>
              <span>8% vs last 30 days</span>
            </p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-blue-600/20 border border-blue-500/30 text-blue-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📈
          </div>
        </div>

        <!-- Card 3: Highest Score -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-emerald-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Highest Score</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.highest_score }}%</p>
            <p class="text-[10px] text-slate-400 font-medium truncate max-w-[110px]">{{ summary.highest_title }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🏆
          </div>
        </div>

        <!-- Card 4: Lowest Score -->
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

        <!-- Card 5: Passed -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Passed</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.passed_count }}</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ summary.passed_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🎯
          </div>
        </div>

      </div>

      <!-- ================= 3. MAIN SPLIT (LEFT: 8/12, RIGHT: 4/12) ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT (8/12): FILTER TABS + ATTEMPTS TABLE ================= -->
        <div class="lg:col-span-8 space-y-4">

          <!-- FILTER BAR -->
          <div class="flex flex-wrap items-center justify-between gap-3 bg-[#0F172A]/80 border border-slate-800/80 rounded-2xl p-2.5 shadow-lg">
            
            <!-- Left Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto custom-scrollbar pb-1 sm:pb-0">
              <button
                v-for="tab in [
                  { key: 'all', label: 'All Attempts' },
                  { key: 'completed', label: 'Completed' },
                  { key: 'passed', label: 'Passed' },
                  { key: 'failed', label: 'Failed' },
                  { key: 'in_progress', label: 'In Progress' }
                ]"
                :key="tab.key"
                @click="handleTabChange(tab.key)"
                :class="[
                  activeStatusTab === tab.key
                    ? 'bg-purple-600 text-white shadow-md shadow-purple-900/40'
                    : 'text-slate-400 hover:text-white',
                  'px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer'
                ]"
              >
                {{ tab.label }}
              </button>
            </div>

            <!-- Right Date & Filter Dropdown -->
            <div class="flex items-center gap-2">
              <button class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700/80 text-slate-300 hover:text-white text-xs font-medium flex items-center gap-1.5">
                <span>📅</span>
                <span>May 20 - Jun 2, 2025</span>
                <span class="text-[10px] text-slate-500">▼</span>
              </button>

              <button class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700/80 text-slate-300 hover:text-white text-xs font-medium flex items-center gap-1.5">
                <span>⚡</span>
                <span>Filters</span>
                <span class="text-[10px] text-slate-500">▼</span>
              </button>
            </div>

          </div>

          <!-- QUIZ ATTEMPTS TABLE -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl shadow-xl overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
              <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-slate-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-800/80">
                  <tr>
                    <th class="p-3.5 pl-5">Quiz Title</th>
                    <th class="p-3.5">Course</th>
                    <th class="p-3.5 text-center">Attempt</th>
                    <th class="p-3.5 text-center">Score</th>
                    <th class="p-3.5 text-center">Percentage</th>
                    <th class="p-3.5 text-center">Time Taken</th>
                    <th class="p-3.5">Date</th>
                    <th class="p-3.5 text-center">Status</th>
                    <th class="p-3.5 pr-5 text-right">Action</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-slate-800/50">
                  <tr
                    v-for="row in attempts"
                    :key="row.id"
                    class="hover:bg-slate-800/30 transition-colors group cursor-pointer"
                    @click="openAttemptModal(row)"
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

                    <!-- Attempt -->
                    <td class="p-3.5 text-center font-mono text-xs text-slate-300">
                      {{ row.attempt }}
                    </td>

                    <!-- Score -->
                    <td class="p-3.5 text-center font-mono text-xs font-bold text-white">
                      {{ row.score }}
                    </td>

                    <!-- Percentage -->
                    <td class="p-3.5 text-center font-mono text-xs font-bold">
                      <span
                        :class="[
                          row.percentage >= 80 ? 'text-emerald-400' :
                          row.percentage >= 50 ? 'text-amber-400' :
                          'text-rose-400'
                        ]"
                      >
                        {{ row.percentage }}%
                      </span>
                    </td>

                    <!-- Time Taken -->
                    <td class="p-3.5 text-center font-mono text-xs text-slate-400 whitespace-nowrap">
                      {{ row.time_taken }}
                    </td>

                    <!-- Date & Time -->
                    <td class="p-3.5 whitespace-nowrap">
                      <p class="text-xs text-slate-300 font-medium">{{ row.date }}</p>
                      <p class="text-[10px] text-slate-500 font-mono">{{ row.time }}</p>
                    </td>

                    <!-- Status Pill -->
                    <td class="p-3.5 text-center whitespace-nowrap">
                      <span
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block"
                        :class="[
                          row.status_type === 'passed' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' :
                          row.status_type === 'failed' ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' :
                          'bg-blue-500/20 text-blue-300 border-blue-500/30'
                        ]"
                      >
                        {{ row.status }}
                      </span>
                    </td>

                    <!-- Actions -->
                    <td class="p-3.5 pr-5 text-right whitespace-nowrap" @click.stop>
                      <div class="flex items-center justify-end gap-1.5">
                        <button
                          @click="openAttemptModal(row)"
                          class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-700/80 hover:border-purple-500/40 text-slate-400 hover:text-white flex items-center justify-center text-xs transition-colors"
                          title="View Details"
                        >
                          👁
                        </button>
                        <button
                          class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-700/80 hover:border-purple-500/40 text-slate-400 hover:text-white flex items-center justify-center text-xs transition-colors"
                          title="Options"
                        >
                          ⋮
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- TABLE FOOTER & PAGINATION -->
            <div class="p-4 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
              <span class="text-slate-400 text-[11px]">
                Showing 1 to 10 of 28 attempts
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
                  3
                </button>
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs">
                  ›
                </button>
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs">
                  »
                </button>
              </div>
            </div>

          </div>

        </div>

        <!-- ================= RIGHT (4/12): WIDGETS SIDEBAR ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- WIDGET 1: Performance Overview (Donut Chart) -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-white tracking-tight">Performance Overview</h3>
              <select
                class="bg-slate-900 border border-slate-700 text-[10px] font-semibold text-slate-300 rounded-lg px-2 py-0.5 focus:outline-none cursor-pointer"
              >
                <option>This Month</option>
                <option>Last 3 Months</option>
                <option>All Time</option>
              </select>
            </div>

            <div class="flex items-center justify-between gap-4">
              <!-- Donut Chart -->
              <div class="relative w-24 h-24 flex items-center justify-center shrink-0">
                <svg class="w-24 h-24 -rotate-90 transform" viewBox="0 0 36 36">
                  <!-- Background Track -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#1E293B" stroke-width="4.5" />
                  
                  <!-- Segments -->
                  <!-- 90-100%: 29% -->
                  <circle
                    cx="18" cy="18" r="15.9155" fill="none"
                    stroke="#10B981" stroke-width="4.5"
                    stroke-dasharray="29, 100" stroke-dashoffset="0"
                  />
                  <!-- 70-89%: 36% -->
                  <circle
                    cx="18" cy="18" r="15.9155" fill="none"
                    stroke="#3B82F6" stroke-width="4.5"
                    stroke-dasharray="36, 100" stroke-dashoffset="-29"
                  />
                  <!-- 50-69%: 21% -->
                  <circle
                    cx="18" cy="18" r="15.9155" fill="none"
                    stroke="#F59E0B" stroke-width="4.5"
                    stroke-dasharray="21, 100" stroke-dashoffset="-65"
                  />
                  <!-- Below 50%: 14% -->
                  <circle
                    cx="18" cy="18" r="15.9155" fill="none"
                    stroke="#EF4444" stroke-width="4.5"
                    stroke-dasharray="14, 100" stroke-dashoffset="-86"
                  />
                </svg>

                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-white font-mono leading-none">{{ summary.average_score }}%</span>
                  <span class="text-[8px] text-slate-400 mt-0.5 font-medium">Average Score</span>
                </div>
              </div>

              <!-- Legend Breakdown -->
              <div class="space-y-1.5 text-xs flex-1">
                <div
                  v-for="item in distribution"
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

          <!-- WIDGET 2: Score Trend (Line Chart) -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Score Trend</h3>
              <select
                class="bg-slate-900 border border-slate-700 text-[10px] font-semibold text-slate-300 rounded-lg px-2 py-0.5 focus:outline-none cursor-pointer"
              >
                <option>This Month</option>
                <option>Last 30 Days</option>
              </select>
            </div>

            <!-- SVG Chart Canvas -->
            <div class="relative w-full h-32 flex items-center justify-center">
              <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="w-full h-full overflow-visible">
                <!-- Grid Lines -->
                <line x1="20" y1="20" :x2="chartWidth - 20" y2="20" stroke="#1E293B" stroke-dasharray="2 2" stroke-width="1" />
                <line x1="20" y1="50" :x2="chartWidth - 20" y2="50" stroke="#1E293B" stroke-dasharray="2 2" stroke-width="1" />
                <line x1="20" y1="80" :x2="chartWidth - 20" y2="80" stroke="#1E293B" stroke-dasharray="2 2" stroke-width="1" />
                <line x1="20" y1="100" :x2="chartWidth - 20" y2="100" stroke="#1E293B" stroke-width="1" />

                <!-- Left Labels -->
                <text x="2" y="24" fill="#64748B" font-size="7" font-family="monospace">100%</text>
                <text x="6" y="54" fill="#64748B" font-size="7" font-family="monospace">75%</text>
                <text x="6" y="84" fill="#64748B" font-size="7" font-family="monospace">50%</text>
                <text x="6" y="104" fill="#64748B" font-size="7" font-family="monospace">0%</text>

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
                  :y="chartHeight + 2"
                  text-anchor="middle"
                  fill="#94A3B8"
                  font-size="7"
                  font-family="monospace"
                >
                  {{ pt.label }}
                </text>
              </svg>
            </div>
          </div>

          <!-- WIDGET 3: Recent Activity -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Recent Activity</h3>
              <span class="text-xs text-purple-400 font-bold hover:underline cursor-pointer">View All</span>
            </div>

            <div class="space-y-2">
              <div
                v-for="act in recentActivity"
                :key="act.id"
                class="p-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/60 flex items-center justify-between gap-3 hover:border-purple-500/30 transition-all cursor-pointer"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      act.icon_bg,
                      'w-7 h-7 rounded-xl bg-gradient-to-br flex items-center justify-center text-[10px] font-black shrink-0 font-mono shadow-sm'
                    ]"
                  >
                    {{ act.code }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">{{ act.title }}</p>
                    <p class="text-[10px] text-slate-400 font-mono">Scored {{ act.score }}%</p>
                  </div>
                </div>

                <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap shrink-0">
                  {{ act.date_str }}
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
              Focus on these topics to improve your scores
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

    <!-- ================= MODAL: QUIZ ATTEMPT DETAIL & QUESTION BREAKDOWN ================= -->
    <div
      v-if="isAttemptDetailModalOpen && selectedAttemptForModal"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-purple-600/30 border border-purple-500/40 text-purple-300 flex items-center justify-center text-xs font-bold font-mono">
              {{ selectedAttemptForModal.code || '📄' }}
            </div>
            <div>
              <h3 class="text-base font-black text-white">{{ selectedAttemptForModal.title }}</h3>
              <p class="text-[11px] text-purple-300">{{ selectedAttemptForModal.course }} • Attempt {{ selectedAttemptForModal.attempt }}</p>
            </div>
          </div>
          <button
            @click="isAttemptDetailModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <!-- Metrics Overview Bar -->
        <div class="grid grid-cols-4 gap-2 bg-slate-950 p-3 rounded-2xl border border-slate-800 text-center text-xs">
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Score</p>
            <p class="font-mono font-bold text-white">{{ selectedAttemptForModal.score }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Percentage</p>
            <p
              class="font-mono font-bold"
              :class="selectedAttemptForModal.percentage >= 70 ? 'text-emerald-400' : 'text-rose-400'"
            >
              {{ selectedAttemptForModal.percentage }}%
            </p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Time Taken</p>
            <p class="font-mono font-bold text-slate-300">{{ selectedAttemptForModal.time_taken }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Status</p>
            <p
              class="font-mono font-bold capitalize"
              :class="selectedAttemptForModal.status_type === 'passed' ? 'text-emerald-400' : 'text-rose-400'"
            >
              {{ selectedAttemptForModal.status }}
            </p>
          </div>
        </div>

        <!-- Question-by-Question Review -->
        <div class="space-y-2.5 max-h-60 overflow-y-auto custom-scrollbar pr-1">
          <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Question Review</h4>
          
          <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 space-y-1.5 text-xs">
            <p class="font-bold text-emerald-400 flex items-center gap-1.5">
              <span>✓ Q1 (+5 pts)</span>
              <span class="text-white font-medium">What is a Closure in JavaScript?</span>
            </p>
            <p class="text-[11px] text-slate-300">Your Answer: <strong class="text-emerald-300">A function that preserves outer scope bindings</strong></p>
          </div>

          <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 space-y-1.5 text-xs">
            <p class="font-bold text-emerald-400 flex items-center gap-1.5">
              <span>✓ Q2 (+5 pts)</span>
              <span class="text-white font-medium">What is the purpose of Promise.all()?</span>
            </p>
            <p class="text-[11px] text-slate-300">Your Answer: <strong class="text-emerald-300">Wait for all promises to resolve concurrently</strong></p>
          </div>

          <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 space-y-1.5 text-xs">
            <p class="font-bold text-rose-400 flex items-center gap-1.5">
              <span>✗ Q3 (0 pts)</span>
              <span class="text-white font-medium">What is the difference between LEFT JOIN and INNER JOIN?</span>
            </p>
            <p class="text-[11px] text-slate-400">Your Answer: <span class="text-rose-300">They return identical row counts</span></p>
            <p class="text-[11px] text-emerald-400">Correct Answer: <strong>LEFT JOIN retains all records from left table</strong></p>
            <p class="text-[10px] text-slate-400 bg-slate-900 p-2 rounded-xl border border-slate-800 mt-1">
              💡 <strong>Explanation:</strong> LEFT JOIN includes all unmatched records from the primary left table filled with NULLs.
            </p>
          </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isAttemptDetailModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Close
          </button>

          <Link
            href="/student/quizzes/practice"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5"
          >
            <span>🔄</span>
            <span>Retake This Quiz</span>
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
              <p class="text-[11px] text-purple-300">Generated from your recent 28 quiz attempts</p>
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
            <span>Priority Areas for Score Boost:</span>
          </p>

          <div class="space-y-2">
            <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex items-start gap-2">
              <span class="text-rose-400 font-bold">1.</span>
              <div>
                <p class="font-bold text-white">SQL JOINS (35% Accuracy)</p>
                <p class="text-[11px] text-slate-400">Review Lesson 4 in Database Systems: INNER vs LEFT vs FULL OUTER joins.</p>
              </div>
            </div>

            <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex items-start gap-2">
              <span class="text-amber-400 font-bold">2.</span>
              <div>
                <p class="font-bold text-white">React Hooks (40% Accuracy)</p>
                <p class="text-[11px] text-slate-400">Focus on useEffect dependency arrays and useCallback memoization.</p>
              </div>
            </div>

            <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex items-start gap-2">
              <span class="text-amber-400 font-bold">3.</span>
              <div>
                <p class="font-bold text-white">Python Functions (60% Accuracy)</p>
                <p class="text-[11px] text-slate-400">Practice args, kwargs, and lambda expression scopes.</p>
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
