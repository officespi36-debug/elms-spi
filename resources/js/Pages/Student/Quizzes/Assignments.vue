<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface AssessmentRow {
  id: number
  title: string
  subtitle?: string
  course: string
  due_date: string
  status: string
  status_type: 'graded' | 'submitted' | 'pending' | 'returned' | 'overdue'
  score: string
  score_points: string
  submitted_on: string
  icon_bg?: string
  feedback?: string
  can_upload?: boolean
  can_download?: boolean
  rubric?: Array<{
    criteria: string
    score: number
    max: number
  }>
}

interface PerformanceSegment {
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

interface FeedbackItem {
  id: number
  title: string
  score: number
  feedback: string
  date: string
  icon_bg: string
}

interface DeadlineItem {
  id: number
  title: string
  due_text: string
  due_date: string
  icon_bg: string
}

const props = defineProps<{
  analytics?: {
    summary: {
      total_assignments: number
      total_note: string
      submitted: number
      submitted_note: string
      graded: number
      graded_note: string
      average_score: number
      average_note: string
      highest_score: number
      highest_title: string
    }
    performance_overview: {
      average_score: number
      distribution: PerformanceSegment[]
    }
    score_trend: {
      points: TrendPoint[]
      highlight?: {
        date: string
        score_text: string
      }
    }
    score_by_course: CourseScore[]
    submission_status: {
      total: number
      items: PerformanceSegment[]
    }
    recent_feedback: FeedbackItem[]
    upcoming_deadlines: DeadlineItem[]
    weak_topics: string[]
    assessments: AssessmentRow[]
    total_count: number
    current_page: number
    per_page: number
  }
  filters?: {
    status: string
    course: string
    date_range: string
    search: string
    page: number
  }
}>()

// Default baseline data
const defaultSummary = {
  total_assignments: 18,
  total_note: 'All time assignments',
  submitted: 14,
  submitted_note: '78% of total',
  graded: 10,
  graded_note: '56% of total',
  average_score: 82,
  average_note: 'Good job! Keep it up.',
  highest_score: 95,
  highest_title: 'Web Development Project',
}

const defaultPerformanceDistribution: PerformanceSegment[] = [
  { label: '90 - 100%', count: 3, percentage: 30, color: '#10B981', class: 'text-emerald-400' },
  { label: '70 - 89%',  count: 5, percentage: 50, color: '#3B82F6', class: 'text-blue-400' },
  { label: '50 - 69%',  count: 1, percentage: 10, color: '#F59E0B', class: 'text-amber-400' },
  { label: 'Below 50%', count: 1, percentage: 10, color: '#EF4444', class: 'text-rose-400' },
]

const defaultScoreTrend = {
  points: [
    { date: 'May 1',  percentage: 50 },
    { date: 'May 8',  percentage: 55 },
    { date: 'May 15', percentage: 65 },
    { date: 'May 22', percentage: 45 },
    { date: 'May 29', percentage: 80 },
    { date: 'Jun 1',  percentage: 95 },
  ],
  highlight: {
    date: 'May 24, 2025',
    score_text: 'Score: 80%',
  }
}

const defaultScoreByCourse: CourseScore[] = [
  { course: 'Web Development',       percentage: 88, color: 'from-cyan-400 to-blue-500' },
  { course: 'React Development',     percentage: 75, color: 'from-cyan-400 to-blue-500' },
  { course: 'JavaScript Advanced',   percentage: 85, color: 'from-purple-500 to-indigo-600' },
  { course: 'Database Design',       percentage: 45, color: 'from-amber-400 to-orange-500' },
  { course: 'Python Programming',    percentage: 88, color: 'from-cyan-400 to-blue-500' },
]

const defaultSubmissionStatus = {
  total: 18,
  items: [
    { label: 'Graded',    count: 10, percentage: 56, color: '#10B981' },
    { label: 'Submitted', count: 4,  percentage: 22, color: '#3B82F6' },
    { label: 'Pending',   count: 2,  percentage: 11, color: '#F59E0B' },
    { label: 'Overdue',   count: 2,  percentage: 11, color: '#EF4444' },
  ]
}

const defaultRecentFeedback: FeedbackItem[] = [
  { id: 1, title: 'Personal Portfolio Website', score: 95, feedback: 'Excellent work! Clean code and great design.', date: 'Jun 1, 2025', icon_bg: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' },
  { id: 2, title: 'React Components Project', score: 75, feedback: 'Good job! Improve state management.', date: 'May 27, 2025', icon_bg: 'bg-amber-500/20 text-amber-300 border border-amber-500/30' },
  { id: 3, title: 'Database Design Report', score: 45, feedback: 'Review normalization and relationships.', date: 'May 29, 2025', icon_bg: 'bg-rose-500/20 text-rose-300 border border-rose-500/30' },
]

const defaultUpcomingDeadlines: DeadlineItem[] = [
  { id: 1, title: 'Node.js API Development', due_text: 'Due in 2 days', due_date: 'May 24, 2025', icon_bg: 'bg-amber-500/20 text-amber-300 border border-amber-500/30' },
  { id: 2, title: 'Git Workflow Assignment', due_text: 'Due in 4 days', due_date: 'May 22, 2025', icon_bg: 'bg-rose-500/20 text-rose-300 border border-rose-500/30' },
  { id: 3, title: 'Advanced CSS Project', due_text: 'Due in 6 days', due_date: 'May 20, 2025', icon_bg: 'bg-blue-500/20 text-blue-300 border border-blue-500/30' },
]

const defaultAssessments: AssessmentRow[] = [
  { id: 1, title: 'Personal Portfolio Website', subtitle: 'Build a responsive personal portfolio', course: 'Web Development (Advanced)', due_date: 'Jun 5, 2025 11:59 PM', status: 'Graded', status_type: 'graded', score: '95%', score_points: '95 / 100', submitted_on: 'Jun 1, 2025 10:30 AM', icon_bg: 'bg-purple-600/20 text-purple-300 border border-purple-500/30', feedback: 'Excellent work! Clean code and great design.' },
  { id: 2, title: 'JavaScript Functions Assignment', subtitle: 'Implement and test JS functions', course: 'JavaScript Advanced (Intermediate)', due_date: 'Jun 3, 2025 11:59 PM', status: 'Graded', status_type: 'graded', score: '85%', score_points: '85 / 100', submitted_on: 'May 31, 2025 09:15 PM', icon_bg: 'bg-amber-500/20 text-amber-300 border border-amber-500/30', feedback: 'Great implementation of arrow functions and scope closures.' },
  { id: 3, title: 'Database Design Report', subtitle: 'Design a database for e-commerce', course: 'Database Design (Intermediate)', due_date: 'May 30, 2025 11:59 PM', status: 'Graded', status_type: 'graded', score: '45%', score_points: '45 / 100', submitted_on: 'May 29, 2025 08:45 PM', icon_bg: 'bg-blue-500/20 text-blue-300 border border-blue-500/30', feedback: 'Review normalization and foreign key constraints.' },
  { id: 4, title: 'React Components Project', subtitle: 'Build a component-based app', course: 'React Development (Advanced)', due_date: 'May 28, 2025 11:59 PM', status: 'Returned', status_type: 'returned', score: '75%', score_points: '75 / 100', submitted_on: 'May 27, 2025 04:20 PM', icon_bg: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30', feedback: 'Good job! Improve state management and resubmit.' },
  { id: 5, title: 'UI/UX Case Study', subtitle: 'Analyze and improve UI/UX', course: 'UI/UX Design (Beginner)', due_date: 'May 25, 2025 11:59 PM', status: 'Graded', status_type: 'graded', score: '80%', score_points: '80 / 100', submitted_on: 'May 24, 2025 07:30 PM', icon_bg: 'bg-rose-500/20 text-rose-300 border border-rose-500/30', feedback: 'Clear wireframing and user journey documentation.' },
  { id: 6, title: 'Node.js API Development', subtitle: 'Create RESTful API with Node.js', course: 'Backend Development (Advanced)', due_date: 'May 24, 2025 11:59 PM', status: 'Pending', status_type: 'pending', score: '-', score_points: '-', submitted_on: '-', icon_bg: 'bg-purple-500/20 text-purple-300 border border-purple-500/30', can_upload: true },
  { id: 7, title: 'Git Workflow Assignment', subtitle: 'Implement git branching workflow', course: 'DevOps Tools (Intermediate)', due_date: 'May 22, 2025 11:59 PM', status: 'Overdue', status_type: 'overdue', score: '-', score_points: '-', submitted_on: '-', icon_bg: 'bg-orange-500/20 text-orange-300 border border-orange-500/30', can_upload: true },
  { id: 8, title: 'Python Data Analysis', subtitle: 'Analyze dataset using Python', course: 'Python Programming (Intermediate)', due_date: 'May 21, 2025 11:59 PM', status: 'Graded', status_type: 'graded', score: '88%', score_points: '88 / 100', submitted_on: 'May 20, 2025 06:40 PM', icon_bg: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30', feedback: 'Great pandas and matplotlib visualization techniques.' },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const performanceDistribution = computed(() => props.analytics?.performance_overview?.distribution || defaultPerformanceDistribution)
const scoreTrend = computed(() => props.analytics?.score_trend || defaultScoreTrend)
const scoreByCourse = computed(() => props.analytics?.score_by_course || defaultScoreByCourse)
const submissionStatus = computed(() => props.analytics?.submission_status || defaultSubmissionStatus)
const recentFeedback = computed(() => props.analytics?.recent_feedback || defaultRecentFeedback)
const upcomingDeadlines = computed(() => props.analytics?.upcoming_deadlines || defaultUpcomingDeadlines)
const weakTopics = computed(() => props.analytics?.weak_topics || ['Database Design Report', 'Git Workflow Assignment'])
const assessments = computed(() => props.analytics?.assessments || defaultAssessments)

// Filter State
const activeStatusTab = ref<string>(props.filters?.status || 'all')
const selectedDateRange = ref<string>(props.filters?.date_range || 'all')
const selectedCourse = ref<string>(props.filters?.course || 'all')

// Modals State
const selectedAssessmentForModal = ref<AssessmentRow | null>(null)
const isDetailModalOpen = ref(false)
const isUploadModalOpen = ref(false)
const isAiPlanModalOpen = ref(false)

const openDetailModal = (row: AssessmentRow) => {
  selectedAssessmentForModal.value = row
  isDetailModalOpen.value = true
}

const openUploadModal = (row: AssessmentRow) => {
  selectedAssessmentForModal.value = row
  isUploadModalOpen.value = true
}

const handleFilterChange = (overrideTab?: string) => {
  if (overrideTab) {
    activeStatusTab.value = overrideTab
  }
  router.get('/student/quizzes/assignments', {
    status: activeStatusTab.value,
    course: selectedCourse.value,
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
  <StudentLayout title="My Assessments — Quiz & Assessment">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
            <span>My Assessments</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">📄</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            Submit your assignments and track your assessment performance.
          </p>
        </div>
      </div>

      <!-- ================= 2. 5 TOP SUMMARY CARDS ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        
        <!-- Card 1: Total Assignments -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Total Assignments</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.total_assignments }}</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ summary.total_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📄
          </div>
        </div>

        <!-- Card 2: Submitted -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-blue-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Submitted</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.submitted }}</p>
            <p class="text-[10px] text-blue-400 font-medium font-mono">{{ summary.submitted_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-blue-600/20 border border-blue-500/30 text-blue-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🖋️
          </div>
        </div>

        <!-- Card 3: Graded -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-emerald-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Graded</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.graded }}</p>
            <p class="text-[10px] text-emerald-400 font-medium font-mono">{{ summary.graded_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            ✓
          </div>
        </div>

        <!-- Card 4: Average Score -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-amber-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Average Score</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.average_score }}%</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ summary.average_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-amber-600/20 border border-amber-500/30 text-amber-400 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📈
          </div>
        </div>

        <!-- Card 5: Highest Score -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Highest Score</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.highest_score }}%</p>
            <p class="text-[10px] text-slate-400 font-medium truncate max-w-[110px]">{{ summary.highest_title }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🏆
          </div>
        </div>

      </div>

      <!-- ================= 3. MAIN SPLIT (LEFT: 8/12, RIGHT: 4/12) ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT (8/12): STATUS TABS + TABLE + 3 BOTTOM CARDS ================= -->
        <div class="lg:col-span-8 space-y-6">

          <!-- STATUS TABS & FILTER BAR -->
          <div class="flex flex-wrap items-center justify-between gap-3 bg-[#0F172A]/80 border border-slate-800/80 rounded-2xl p-2.5 shadow-lg">
            
            <!-- Left Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto custom-scrollbar pb-1 sm:pb-0">
              <button
                v-for="tab in [
                  { key: 'all', label: 'All' },
                  { key: 'submitted', label: 'Submitted' },
                  { key: 'graded', label: 'Graded' },
                  { key: 'pending', label: 'Pending' },
                  { key: 'returned', label: 'Returned' },
                  { key: 'overdue', label: 'Overdue' }
                ]"
                :key="tab.key"
                @click="handleFilterChange(tab.key)"
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

            <!-- Right Date & Filter Controls -->
            <div class="flex items-center gap-2">
              <button class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700/80 text-slate-300 hover:text-white text-xs font-medium flex items-center gap-1.5 cursor-pointer">
                <span>📅</span>
                <span>May 20 - Jun 2, 2025</span>
                <span class="text-[10px] text-slate-500">▼</span>
              </button>

              <button class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700/80 text-slate-300 hover:text-white text-xs font-medium flex items-center gap-1.5 cursor-pointer">
                <span>⚡</span>
                <span>Filters</span>
                <span class="text-[10px] text-slate-500">▼</span>
              </button>
            </div>

          </div>

          <!-- MY ASSESSMENTS TABLE -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl shadow-xl overflow-hidden">
            <div class="p-4 border-b border-slate-800/80">
              <h2 class="text-sm font-bold text-white tracking-tight">My Assessments</h2>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
              <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-slate-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-800/80">
                  <tr>
                    <th class="p-3.5 pl-5">Assignment Title</th>
                    <th class="p-3.5">Course</th>
                    <th class="p-3.5">Due Date</th>
                    <th class="p-3.5 text-center">Status</th>
                    <th class="p-3.5 text-center">Score</th>
                    <th class="p-3.5">Submitted On</th>
                    <th class="p-3.5 pr-5 text-right">Action</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-slate-800/50">
                  <tr
                    v-for="row in assessments"
                    :key="row.id"
                    class="hover:bg-slate-800/30 transition-colors group cursor-pointer"
                    @click="openDetailModal(row)"
                  >
                    <!-- Assignment Title -->
                    <td class="p-3.5 pl-5">
                      <div class="flex items-center gap-3">
                        <div
                          :class="[
                            row.icon_bg || 'bg-purple-600/20 text-purple-300 border border-purple-500/30',
                            'w-7 h-7 rounded-lg flex items-center justify-center text-xs shrink-0 shadow-sm'
                          ]"
                        >
                          📄
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

                    <!-- Due Date -->
                    <td class="p-3.5 whitespace-nowrap">
                      <p class="text-xs text-slate-300 font-medium">{{ row.due_date.split(' ')[0] + ' ' + row.due_date.split(' ')[1] + ' ' + row.due_date.split(' ')[2] }}</p>
                      <p class="text-[10px] text-slate-500 font-mono">{{ row.due_date.split(' ')[3] + ' ' + (row.due_date.split(' ')[4] || '') }}</p>
                    </td>

                    <!-- Status Pill -->
                    <td class="p-3.5 text-center whitespace-nowrap">
                      <span
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-block"
                        :class="[
                          row.status_type === 'graded' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' :
                          row.status_type === 'returned' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' :
                          row.status_type === 'overdue' ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' :
                          'bg-amber-500/20 text-amber-300 border-amber-500/30'
                        ]"
                      >
                        {{ row.status }}
                      </span>
                    </td>

                    <!-- Score -->
                    <td class="p-3.5 text-center whitespace-nowrap">
                      <template v-if="row.score !== '-'">
                        <p
                          class="font-mono font-bold text-xs"
                          :class="[
                            parseInt(row.score) >= 80 ? 'text-emerald-400' :
                            parseInt(row.score) >= 60 ? 'text-amber-400' :
                            'text-rose-400'
                          ]"
                        >
                          {{ row.score }}
                        </p>
                        <p class="text-[9px] text-slate-500 font-mono">{{ row.score_points }}</p>
                      </template>
                      <span v-else class="text-slate-500 font-mono">-</span>
                    </td>

                    <!-- Submitted On -->
                    <td class="p-3.5 whitespace-nowrap">
                      <template v-if="row.submitted_on !== '-'">
                        <p class="text-xs text-slate-300 font-medium">{{ row.submitted_on.split(' ')[0] + ' ' + row.submitted_on.split(' ')[1] + ' ' + row.submitted_on.split(' ')[2] }}</p>
                        <p class="text-[10px] text-slate-500 font-mono">{{ row.submitted_on.split(' ')[3] + ' ' + (row.submitted_on.split(' ')[4] || '') }}</p>
                      </template>
                      <span v-else class="text-slate-500 font-mono">-</span>
                    </td>

                    <!-- Actions -->
                    <td class="p-3.5 pr-5 text-right whitespace-nowrap" @click.stop>
                      <div class="flex items-center justify-end gap-1.5">
                        <template v-if="row.can_upload">
                          <button
                            @click="openUploadModal(row)"
                            class="w-7 h-7 rounded-lg bg-purple-600/30 border border-purple-500/40 text-purple-300 hover:text-white flex items-center justify-center text-xs transition-colors"
                            title="Upload Submission"
                          >
                            📤
                          </button>
                          <button
                            class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-700/80 hover:border-purple-500/40 text-slate-400 hover:text-white flex items-center justify-center text-xs transition-colors"
                            title="Options"
                          >
                            ⋮
                          </button>
                        </template>
                        <template v-else>
                          <button
                            @click="openDetailModal(row)"
                            class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-700/80 hover:border-purple-500/40 text-slate-400 hover:text-white flex items-center justify-center text-xs transition-colors"
                            title="View Details"
                          >
                            👁
                          </button>
                          <button
                            class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-700/80 hover:border-purple-500/40 text-slate-400 hover:text-white flex items-center justify-center text-xs transition-colors"
                            title="Download Report"
                          >
                            ⬇
                          </button>
                        </template>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- TABLE FOOTER & PAGINATION -->
            <div class="p-4 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
              <span class="text-slate-400 text-[11px]">
                Showing 1 to 8 of 18 assignments
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

            <!-- Card 2: Submission Status Donut -->
            <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-4 shadow-xl space-y-3">
              <div class="flex items-center justify-between border-b border-slate-800/60 pb-2">
                <h3 class="text-xs font-bold text-white tracking-tight">Submission Status</h3>
              </div>

              <div class="flex items-center justify-between gap-3 pt-1">
                <!-- Donut Chart -->
                <div class="relative w-20 h-20 flex items-center justify-center shrink-0">
                  <svg class="w-20 h-20 -rotate-90 transform" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#1E293B" stroke-width="4.5" />
                    <!-- Graded 56% -->
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#10B981" stroke-width="4.5" stroke-dasharray="56, 100" stroke-dashoffset="0" />
                    <!-- Submitted 22% -->
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#3B82F6" stroke-width="4.5" stroke-dasharray="22, 100" stroke-dashoffset="-56" />
                    <!-- Pending 11% -->
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F59E0B" stroke-width="4.5" stroke-dasharray="11, 100" stroke-dashoffset="-78" />
                    <!-- Overdue 11% -->
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#EF4444" stroke-width="4.5" stroke-dasharray="11, 100" stroke-dashoffset="-89" />
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-sm font-black text-white font-mono">{{ submissionStatus.total }}</span>
                    <span class="text-[7px] text-slate-400">Total</span>
                  </div>
                </div>

                <!-- Legend -->
                <div class="space-y-1.5 text-xs flex-1">
                  <div
                    v-for="item in submissionStatus.items"
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

            <!-- Card 3: Recent Feedback -->
            <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-4 shadow-xl space-y-2.5">
              <div class="flex items-center justify-between border-b border-slate-800/60 pb-2">
                <h3 class="text-xs font-bold text-white tracking-tight">Recent Feedback</h3>
                <span class="text-[10px] text-purple-400 font-bold hover:underline cursor-pointer">View All</span>
              </div>

              <div class="space-y-2">
                <div
                  v-for="fb in recentFeedback"
                  :key="fb.id"
                  class="p-2 rounded-2xl bg-slate-950/70 border border-slate-800/60 flex items-start gap-2.5"
                >
                  <div
                    :class="[
                      fb.icon_bg,
                      'w-6 h-6 rounded-lg flex items-center justify-center text-[10px] shrink-0'
                    ]"
                  >
                    📄
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                      <p class="text-[11px] font-bold text-white truncate max-w-[110px]">{{ fb.title }}</p>
                      <span class="text-[10px] font-mono font-bold text-emerald-400">{{ fb.score }}%</span>
                    </div>
                    <p class="text-[9px] text-slate-400 line-clamp-1 italic mt-0.5">{{ fb.feedback }}</p>
                  </div>
                </div>
              </div>
            </div>

          </div>

        </div>

        <!-- ================= RIGHT (4/12): WIDGETS SIDEBAR ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- WIDGET 1: Assessment Performance Donut -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-white tracking-tight">Assessment Performance</h3>
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
                  <!-- 90-100%: 30% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#10B981" stroke-width="4.5" stroke-dasharray="30, 100" stroke-dashoffset="0" />
                  <!-- 70-89%: 50% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#3B82F6" stroke-width="4.5" stroke-dasharray="50, 100" stroke-dashoffset="-30" />
                  <!-- 50-69%: 10% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F59E0B" stroke-width="4.5" stroke-dasharray="10, 100" stroke-dashoffset="-80" />
                  <!-- Below 50%: 10% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#EF4444" stroke-width="4.5" stroke-dasharray="10, 100" stroke-dashoffset="-90" />
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

          <!-- WIDGET 2: Score Trend Line Chart -->
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
                <p class="text-purple-300 font-bold">Score: 80%</p>
              </div>
            </div>
          </div>

          <!-- WIDGET 3: Upcoming Deadlines -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Upcoming Deadlines</h3>
              <span class="text-xs text-purple-400 font-bold hover:underline cursor-pointer">View All</span>
            </div>

            <div class="space-y-2">
              <div
                v-for="dl in upcomingDeadlines"
                :key="dl.id"
                class="p-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/60 flex items-center justify-between gap-3 hover:border-purple-500/30 transition-all cursor-pointer"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      dl.icon_bg,
                      'w-7 h-7 rounded-xl flex items-center justify-center text-xs shrink-0 shadow-sm'
                    ]"
                  >
                    📄
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">{{ dl.title }}</p>
                    <p class="text-[10px] text-slate-400">{{ dl.due_text }}</p>
                  </div>
                </div>

                <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap shrink-0">
                  {{ dl.due_date }}
                </span>
              </div>
            </div>
          </div>

          <!-- WIDGET 4: Need Improvement? & AI Study Plan -->
          <div class="bg-gradient-to-br from-[#10132B] via-[#0F172A] to-[#1E1138] border border-purple-900/50 rounded-3xl p-5 shadow-2xl space-y-3.5">
            <div class="flex items-center justify-between border-b border-purple-900/40 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Need Improvement?</h3>
              <span class="text-xs text-purple-400 font-bold hover:underline cursor-pointer">View All</span>
            </div>

            <p class="text-xs text-slate-400">
              Focus on these assignments to improve your performance.
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

    <!-- ================= MODAL: ASSIGNMENT DETAIL & RUBRIC FEEDBACK ================= -->
    <div
      v-if="isDetailModalOpen && selectedAssessmentForModal"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-purple-600/30 border border-purple-500/40 text-purple-300 flex items-center justify-center text-xs font-bold font-mono">
              📄
            </div>
            <div>
              <h3 class="text-base font-black text-white">{{ selectedAssessmentForModal.title }}</h3>
              <p class="text-[11px] text-purple-300">{{ selectedAssessmentForModal.course }}</p>
            </div>
          </div>
          <button
            @click="isDetailModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <!-- Metrics Overview Bar -->
        <div class="grid grid-cols-4 gap-2 bg-slate-950 p-3 rounded-2xl border border-slate-800 text-center text-xs">
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Score</p>
            <p class="font-mono font-bold text-white">{{ selectedAssessmentForModal.score }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Points</p>
            <p class="font-mono font-bold text-emerald-400">{{ selectedAssessmentForModal.score_points }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Status</p>
            <p
              class="font-mono font-bold capitalize"
              :class="selectedAssessmentForModal.status_type === 'graded' ? 'text-emerald-400' : 'text-amber-400'"
            >
              {{ selectedAssessmentForModal.status }}
            </p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">Submitted</p>
            <p class="font-mono font-bold text-slate-300 truncate">{{ selectedAssessmentForModal.submitted_on }}</p>
          </div>
        </div>

        <!-- Teacher Feedback -->
        <div v-if="selectedAssessmentForModal.feedback" class="p-4 bg-indigo-950/40 border border-indigo-500/30 rounded-2xl space-y-1 text-xs">
          <p class="font-bold text-indigo-300">👨‍🏫 Teacher Feedback:</p>
          <p class="text-slate-200 italic leading-relaxed">{{ selectedAssessmentForModal.feedback }}</p>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isDetailModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Close
          </button>

          <button
            @click="isDetailModalOpen = false; openUploadModal(selectedAssessmentForModal)"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5"
          >
            <span>📤</span>
            <span>Resubmit Project</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: UPLOAD & SUBMIT ASSIGNMENT ================= -->
    <div
      v-if="isUploadModalOpen && selectedAssessmentForModal"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-white">📤 Submit: {{ selectedAssessmentForModal.title }}</h3>
            <p class="text-[11px] text-slate-400">Due: {{ selectedAssessmentForModal.due_date }}</p>
          </div>
          <button
            @click="isUploadModalOpen = false"
            class="text-slate-400 hover:text-white"
          >
            ✕
          </button>
        </div>

        <div class="p-6 bg-slate-950 rounded-2xl border-2 border-dashed border-purple-500/40 text-center space-y-2">
          <p class="text-xs font-bold text-slate-200">📁 Drag &amp; Drop submission files here</p>
          <p class="text-[10px] text-slate-400">Supported: .zip .pdf .docx .js .py (Max 50MB)</p>
          <button class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-purple-300 text-xs font-bold border border-slate-700">
            Browse Files
          </button>
        </div>

        <div class="space-y-1 text-xs">
          <label class="font-bold text-slate-300">Notes / Comments for Teacher:</label>
          <textarea
            rows="2"
            placeholder="Describe your implementation or project notes..."
            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white placeholder-slate-500"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isUploadModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Cancel
          </button>
          <button
            @click="isUploadModalOpen = false"
            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md"
          >
            Submit Assignment ✓
          </button>
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
              <h3 class="text-base font-black text-white">AI Assessment Improvement Plan</h3>
              <p class="text-[11px] text-purple-300">Targeting low scoring project submissions</p>
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
            <span>Priority Areas for Assignment Mastery:</span>
          </p>

          <div class="space-y-2">
            <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex items-start gap-2">
              <span class="text-rose-400 font-bold">1.</span>
              <div>
                <p class="font-bold text-white">Database Design Report (45% Score)</p>
                <p class="text-[11px] text-slate-400">Focus on database normalization (1NF to 3NF) and entity relationship diagrams.</p>
              </div>
            </div>

            <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex items-start gap-2">
              <span class="text-amber-400 font-bold">2.</span>
              <div>
                <p class="font-bold text-white">Git Workflow & Branching</p>
                <p class="text-[11px] text-slate-400">Practice resolving merge conflicts, feature branch rebasing, and PR workflows.</p>
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
