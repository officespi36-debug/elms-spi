<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

interface AssessmentRow {
  id: number
  quiz_id?: number
  submission_id?: number | null
  title: string
  subtitle?: string
  course: string
  due_date: string
  due_at_raw?: string | null
  status: string
  status_type: 'graded' | 'submitted' | 'pending' | 'returned' | 'overdue'
  score: string
  score_points: string
  score_num?: number | null
  submitted_on: string
  submitted_at_raw?: string | null
  file_name?: string | null
  file_path?: string | null
  file_size?: string | null
  notes?: string | null
  icon_bg?: string
  feedback?: string | null
  can_upload?: boolean
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
      } | null
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
    courses?: string[]
  }
  filters?: {
    status: string
    course: string
    date_range: string
    search: string
    page: number
  }
}>()

const { currentLang, isKhmer } = useLanguage()
const isKm = computed(() => isKhmer.value || currentLang.value === 'km')

// Filter State
const activeStatusTab = ref<string>(props.filters?.status || 'all')
const selectedDateRange = ref<string>(props.filters?.date_range || 'all')
const selectedCourse = ref<string>(props.filters?.course || 'all')
const searchQuery = ref<string>(props.filters?.search || '')

// Analytics
const summary = computed(() => props.analytics?.summary || {
  total_assignments: 0,
  total_note: isKm.value ? 'មិនទាន់មានកិច្ចការ' : 'No assignments yet',
  submitted: 0,
  submitted_note: '0%',
  graded: 0,
  graded_note: '0%',
  average_score: 0,
  average_note: isKm.value ? 'មិនទាន់មានពិន្ទុ' : 'No graded assignments',
  highest_score: 0,
  highest_title: '-',
})

const performanceDistribution = computed(() => props.analytics?.performance_overview?.distribution || [
  { label: '90 - 100%', count: 0, percentage: 0, color: '#10B981', class: 'text-emerald-500' },
  { label: '70 - 89%',  count: 0, percentage: 0, color: '#3B82F6', class: 'text-blue-500' },
  { label: '50 - 69%',  count: 0, percentage: 0, color: '#F59E0B', class: 'text-amber-500' },
  { label: 'Below 50%', count: 0, percentage: 0, color: '#EF4444', class: 'text-rose-500' },
])

const scoreTrend = computed(() => props.analytics?.score_trend || {
  points: [],
  highlight: null,
})

const scoreByCourse = computed(() => props.analytics?.score_by_course || [])
const submissionStatus = computed(() => props.analytics?.submission_status || {
  total: 0,
  items: [
    { label: 'Graded',    count: 0, percentage: 0, color: '#10B981' },
    { label: 'Submitted', count: 0, percentage: 0, color: '#3B82F6' },
    { label: 'Pending',   count: 0, percentage: 0, color: '#F59E0B' },
    { label: 'Overdue',   count: 0, percentage: 0, color: '#EF4444' },
  ],
})

const recentFeedback = computed(() => props.analytics?.recent_feedback || [])
const upcomingDeadlines = computed(() => props.analytics?.upcoming_deadlines || [])
const weakTopics = computed(() => props.analytics?.weak_topics || [])
const assessments = computed(() => props.analytics?.assessments || [])
const totalCount = computed(() => props.analytics?.total_count ?? 0)
const currentPage = computed(() => props.analytics?.current_page ?? 1)
const perPage = computed(() => props.analytics?.per_page ?? 8)
const coursesList = computed(() => props.analytics?.courses || [])

const totalPages = computed(() => Math.max(1, Math.ceil(totalCount.value / perPage.value)))

const statusTabs = computed(() => [
  { value: 'all', label: isKm.value ? 'ទាំងអស់' : 'All' },
  { value: 'submitted', label: isKm.value ? 'បានដាក់ស្នើ' : 'Submitted' },
  { value: 'graded', label: isKm.value ? 'បានដាក់ពិន្ទុ' : 'Graded' },
  { value: 'pending', label: isKm.value ? 'រង់ចាំធ្វើ' : 'Pending' },
  { value: 'returned', label: isKm.value ? 'ផ្ញើត្រឡប់' : 'Returned' },
  { value: 'overdue', label: isKm.value ? 'ហួសកំណត់' : 'Overdue' },
])

const dateRanges = computed(() => [
  { value: 'all', label: isKm.value ? 'គ្រប់កាលបរិច្ឆេទ' : 'All Time' },
  { value: 'this_week', label: isKm.value ? 'សប្តាហ៍នេះ' : 'This Week' },
  { value: 'this_month', label: isKm.value ? 'ខែនេះ' : 'This Month' },
  { value: 'past_month', label: isKm.value ? 'ខែមុន' : 'Past Month' },
  { value: 'past_30', label: isKm.value ? '៣០ ថ្ងៃចុងក្រោយ' : 'Past 30 Days' },
])

// Pagination range info
const paginationInfo = computed(() => {
  if (totalCount.value === 0) {
    return isKm.value ? 'បង្ហាញ 0 នៃ 0 កិច្ចការ' : 'Showing 0 of 0 assignments'
  }
  const from = (currentPage.value - 1) * perPage.value + 1
  const to = Math.min(currentPage.value * perPage.value, totalCount.value)
  return isKm.value
    ? `បង្ហាញពី ${from} ដល់ ${to} នៃ ${totalCount.value} កិច្ចការ`
    : `Showing ${from} to ${to} of ${totalCount.value} assignments`
})

// Modals State
const selectedAssessmentForModal = ref<AssessmentRow | null>(null)
const isDetailModalOpen = ref(false)
const isUploadModalOpen = ref(false)
const isAiPlanModalOpen = ref(false)
const uploadFileInput = ref<HTMLInputElement | null>(null)
const fileError = ref<string | null>(null)
const submitSuccessMessage = ref<string | null>(null)

// Inertia Form for Submission
const submissionForm = useForm({
  notes: '',
  file: null as File | null,
})

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    if (file.size > 10 * 1024 * 1024) {
      fileError.value = isKm.value ? 'ទំហំឯកសារមិនត្រូវលើសពី 10MB ទេ' : 'File size cannot exceed 10MB'
      submissionForm.file = null
      return
    }
    fileError.value = null
    submissionForm.file = file
  }
}

const handleFileDrop = (event: DragEvent) => {
  event.preventDefault()
  if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
    const file = event.dataTransfer.files[0]
    if (file.size > 10 * 1024 * 1024) {
      fileError.value = isKm.value ? 'ទំហំឯកសារមិនត្រូវលើសពី 10MB ទេ' : 'File size cannot exceed 10MB'
      return
    }
    fileError.value = null
    submissionForm.file = file
  }
}

const removeSelectedFile = () => {
  submissionForm.file = null
  fileError.value = null
  if (uploadFileInput.value) {
    uploadFileInput.value.value = ''
  }
}

const openDetailModal = (row: AssessmentRow) => {
  selectedAssessmentForModal.value = row
  isDetailModalOpen.value = true
}

const openUploadModal = (row: AssessmentRow) => {
  selectedAssessmentForModal.value = row
  submissionForm.reset()
  submissionForm.notes = row.notes || ''
  submissionForm.file = null
  fileError.value = null
  submitSuccessMessage.value = null
  isUploadModalOpen.value = true
}

const submitAssignmentHandler = () => {
  if (!selectedAssessmentForModal.value) return
  const quizId = selectedAssessmentForModal.value.quiz_id || selectedAssessmentForModal.value.id

  submissionForm.post(`/student/quizzes/assignments/${quizId}/submit`, {
    preserveScroll: true,
    onSuccess: () => {
      submitSuccessMessage.value = isKm.value ? 'កិច្ចការត្រូវបានដាក់ស្នើដោយជោគជ័យ!' : 'Assignment submitted successfully!'
      setTimeout(() => {
        isUploadModalOpen.value = false
        submitSuccessMessage.value = null
      }, 1500)
    },
    onError: (errors) => {
      fileError.value = errors.file || errors.notes || (isKm.value ? 'មានកំហុសក្នុងការដាក់ស្នើ' : 'Failed to submit assignment')
    },
  })
}

const handleFilterChange = (overrideTab?: string) => {
  if (overrideTab !== undefined) {
    activeStatusTab.value = overrideTab
  }
  router.get('/student/quizzes/assignments', {
    status: activeStatusTab.value,
    course: selectedCourse.value,
    date_range: selectedDateRange.value,
    search: searchQuery.value,
    page: 1,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const goToPage = (pageNumber: number) => {
  if (pageNumber < 1 || pageNumber > totalPages.value) return
  router.get('/student/quizzes/assignments', {
    status: activeStatusTab.value,
    course: selectedCourse.value,
    date_range: selectedDateRange.value,
    search: searchQuery.value,
    page: pageNumber,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Line chart calculations
const chartWidth = 260
const chartHeight = 110
const padding = 20

const svgPath = computed(() => {
  const points = scoreTrend.value.points
  if (!points || points.length < 2) return ''
  const stepX = (chartWidth - padding * 2) / (points.length - 1)
  
  return points.map((p, i) => {
    const x = padding + i * stepX
    const y = chartHeight - padding - (Math.min(100, Math.max(0, p.percentage)) / 100) * (chartHeight - padding * 2)
    return `${i === 0 ? 'M' : 'L'} ${x} ${y}`
  }).join(' ')
})

const svgPoints = computed(() => {
  const points = scoreTrend.value.points || []
  if (points.length === 0) return []
  const stepX = points.length > 1 ? (chartWidth - padding * 2) / (points.length - 1) : 0
  return points.map((p, i) => ({
    x: padding + i * stepX,
    y: chartHeight - padding - (Math.min(100, Math.max(0, p.percentage)) / 100) * (chartHeight - padding * 2),
    label: p.date,
    value: p.percentage,
  }))
})

// Submission Status SVG segments
const submissionDonutSegments = computed(() => {
  let accumulated = 0
  return submissionStatus.value.items.map(item => {
    const pct = Math.min(100, Math.max(0, item.percentage))
    const strokeDash = `${pct}, 100`
    const strokeOffset = -accumulated
    accumulated += pct
    return {
      ...item,
      strokeDash,
      strokeOffset,
    }
  })
})

// Performance Distribution SVG segments
const performanceDonutSegments = computed(() => {
  let accumulated = 0
  return performanceDistribution.value.map(item => {
    const pct = Math.min(100, Math.max(0, item.percentage))
    const strokeDash = `${pct}, 100`
    const strokeOffset = -accumulated
    accumulated += pct
    return {
      ...item,
      strokeDash,
      strokeOffset,
    }
  })
})

const statusBadgeClass = (statusType: string) => {
  switch (statusType) {
    case 'graded':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'
    case 'submitted':
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20'
    case 'returned':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20'
    case 'overdue':
      return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20'
  }
}

const statusText = (statusType: string, defaultLabel: string) => {
  if (!isKm.value) return defaultLabel
  switch (statusType) {
    case 'graded': return 'បានដាក់ពិន្ទុ'
    case 'submitted': return 'បានដាក់ស្នើ'
    case 'returned': return 'ផ្ញើត្រឡប់'
    case 'overdue': return 'ហួសកាលកំណត់'
    case 'pending': return 'រង់ចាំធ្វើ'
    default: return defaultLabel
  }
}
</script>

<template>
  <StudentLayout :title="isKm ? 'កិច្ចការ & ការវាយតម្លៃរបស់ខ្ញុំ — E-LMS' : 'My Assessments & Assignments — E-LMS'">
    <div class="space-y-6 max-w-7xl mx-auto pb-12 px-2 sm:px-4">

      <!-- ================= 1. PAGE BREADCRUMB & HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <!-- Breadcrumb -->
          <nav class="flex items-center gap-2 text-xs text-slate-400 dark:text-slate-500 mb-1.5 font-medium">
            <Link href="/student/dashboard" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
              {{ isKm ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard' }}
            </Link>
            <span>/</span>
            <span>{{ isKm ? 'កម្រងសំណួរ & ការវាយតម្លៃ' : 'Quizzes & Assessments' }}</span>
            <span>/</span>
            <span class="text-purple-600 dark:text-purple-400 font-semibold">
              {{ isKm ? 'ការវាយតម្លៃ & កិច្ចការ' : 'Assignments' }}
            </span>
          </nav>

          <!-- Main Title (Readable in both Light & Dark modes) -->
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>{{ isKm ? 'ការវាយតម្លៃ & កិច្ចការរបស់ខ្ញុំ' : 'My Assessments & Assignments' }}</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-400 text-lg">📄</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
            {{ isKm ? 'ដាក់ស្នើកិច្ចការរបស់អ្នក ពិនិត្យមតិយោបល់របស់គ្រូ និងតាមដានវឌ្ឍនភាពពិន្ទុសិក្សា។' : 'Submit your assignments, review teacher feedback, and track your assessment performance.' }}
          </p>
        </div>

        <!-- Quick AI action -->
        <div class="flex items-center gap-2 self-start sm:self-center">
          <button
            @click="isAiPlanModalOpen = true"
            class="px-4 py-2 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md shadow-purple-500/20 flex items-center gap-1.5 transition-all cursor-pointer active:scale-95"
          >
            <span>✨</span>
            <span>{{ isKm ? 'ផែនការជំនួយ AI' : 'AI Study Plan' }}</span>
          </button>
        </div>
      </div>

      <!-- ================= 2. 5 TOP SUMMARY CARDS ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
        
        <!-- Card 1: Total Assignments -->
        <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-purple-500/40 transition-all">
          <div class="space-y-0.5">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ isKm ? 'កិច្ចការសរុប' : 'Total Assignments' }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.total_assignments }}</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium truncate max-w-[110px]">
              {{ summary.total_assignments > 0 ? (isKm ? 'កិច្ចការក្នុងវគ្គសិក្សា' : 'Enrolled assignments') : (isKm ? 'មិនទាន់មានកិច្ចការ' : 'No assignments yet') }}
            </p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-300 flex items-center justify-center text-base shrink-0 group-hover:scale-110 transition-transform">
            📄
          </div>
        </div>

        <!-- Card 2: Submitted -->
        <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-blue-500/40 transition-all">
          <div class="space-y-0.5">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ isKm ? 'បានដាក់ស្នើ' : 'Submitted' }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.submitted }}</p>
            <p class="text-[10px] text-blue-600 dark:text-blue-400 font-medium font-mono">{{ summary.submitted_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-600 dark:text-blue-300 flex items-center justify-center text-base shrink-0 group-hover:scale-110 transition-transform">
            📤
          </div>
        </div>

        <!-- Card 3: Graded -->
        <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-emerald-500/40 transition-all">
          <div class="space-y-0.5">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ isKm ? 'បានដាក់ពិន្ទុ' : 'Graded' }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.graded }}</p>
            <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium font-mono">{{ summary.graded_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-300 flex items-center justify-center text-base shrink-0 group-hover:scale-110 transition-transform">
            ✓
          </div>
        </div>

        <!-- Card 4: Average Score -->
        <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-amber-500/40 transition-all">
          <div class="space-y-0.5">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ isKm ? 'ពិន្ទុមធ្យម' : 'Average Score' }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.average_score }}%</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium truncate max-w-[110px]">
              {{ summary.graded > 0 ? (summary.average_score >= 70 ? (isKm ? 'លទ្ធផលល្អប្រសើរ!' : 'Good job!') : (isKm ? 'ត្រូវខិតខំបន្ថែម' : 'Keep practicing')) : (isKm ? 'មិនទាន់មានពិន្ទុ' : 'No graded yet') }}
            </p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-300 flex items-center justify-center text-base shrink-0 group-hover:scale-110 transition-transform">
            📈
          </div>
        </div>

        <!-- Card 5: Highest Score -->
        <div class="col-span-2 sm:col-span-1 bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-purple-500/40 transition-all">
          <div class="space-y-0.5 min-w-0">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ isKm ? 'ពិន្ទុខ្ពស់បំផុត' : 'Highest Score' }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.highest_score }}%</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium truncate max-w-[110px]">{{ summary.highest_title }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-300 flex items-center justify-center text-base shrink-0 group-hover:scale-110 transition-transform">
            🏆
          </div>
        </div>

      </div>

      <!-- ================= 3. MAIN CONTENT: LEFT 8/12 TABLE & RIGHT 4/12 WIDGETS ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- ================= LEFT (8/12): FILTERS & ASSIGNMENTS TABLE ================= -->
        <div class="lg:col-span-8 space-y-5">

          <!-- FILTER BAR (Status Tabs, Date Filter, Course Dropdown & Search) -->
          <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-3xl p-3.5 shadow-sm dark:shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-3">
            
            <!-- Status Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar pb-1 md:pb-0">
              <button
                v-for="tab in statusTabs"
                :key="tab.value"
                @click="handleFilterChange(tab.value)"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
                :class="[
                  activeStatusTab === tab.value
                    ? 'bg-purple-600 text-white shadow-sm'
                    : 'bg-slate-100 dark:bg-slate-900/60 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                ]"
              >
                {{ tab.label }}
              </button>
            </div>

            <!-- Controls: Course, Date, and Search -->
            <div class="flex flex-wrap items-center gap-2">
              
              <!-- Course Select -->
              <select
                v-if="coursesList.length > 0"
                v-model="selectedCourse"
                @change="handleFilterChange()"
                class="px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 text-xs font-medium focus:ring-1 focus:ring-purple-500 focus:outline-none cursor-pointer max-w-[140px] truncate"
              >
                <option value="all">{{ isKm ? 'គ្រប់វគ្គសិក្សា' : 'All Courses' }}</option>
                <option v-for="c in coursesList" :key="c" :value="c">{{ c }}</option>
              </select>

              <!-- Date Range Select -->
              <select
                v-model="selectedDateRange"
                @change="handleFilterChange()"
                class="px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 text-xs font-medium focus:ring-1 focus:ring-purple-500 focus:outline-none cursor-pointer"
              >
                <option v-for="d in dateRanges" :key="d.value" :value="d.value">{{ d.label }}</option>
              </select>

              <!-- Search Input -->
              <div class="relative min-w-[120px] flex-1 sm:flex-initial">
                <input
                  v-model="searchQuery"
                  @keyup.enter="handleFilterChange()"
                  type="text"
                  :placeholder="isKm ? 'ស្វែងរកកិច្ចការ...' : 'Search assignments...'"
                  class="w-full pl-7 pr-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 text-xs placeholder-slate-400 focus:ring-1 focus:ring-purple-500 focus:outline-none"
                />
                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
              </div>

            </div>

          </div>

          <!-- ASSIGNMENTS TABLE -->
          <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-3xl shadow-sm dark:shadow-xl overflow-hidden">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
              <h2 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>{{ isKm ? 'បញ្ជីកិច្ចការ & ការវាយតម្លៃ' : 'Assessments & Assignments List' }}</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-600 dark:text-purple-400">
                  {{ totalCount }}
                </span>
              </h2>
            </div>

            <!-- Empty State -->
            <div v-if="assessments.length === 0" class="py-14 px-4 text-center space-y-3">
              <div class="w-14 h-14 mx-auto rounded-3xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center text-2xl">
                📂
              </div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">
                {{ isKm ? 'មិនទាន់មានកិច្ចការនៅក្នុងបញ្ជីនេះទេ' : 'No assignments found' }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                {{ isKm ? 'ពុំមានកិច្ចការដែលត្រូវនឹងលក្ខខណ្ឌចម្រោះនេះឡើយ។ សូមជ្រើសរើសចម្រោះឡើងវិញ ឬរង់ចាំគ្រូដាក់កិច្ចការថ្មី។' : 'No assignments match your selected filter. Try adjusting your filters or wait for your instructor to publish new assignments.' }}
              </p>
              <button
                v-if="activeStatusTab !== 'all' || selectedCourse !== 'all' || selectedDateRange !== 'all' || searchQuery"
                @click="activeStatusTab = 'all'; selectedCourse = 'all'; selectedDateRange = 'all'; searchQuery = ''; handleFilterChange()"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition-all shadow-sm cursor-pointer"
              >
                {{ isKm ? 'សម្អាតចម្រោះទាំងអស់' : 'Clear All Filters' }}
              </button>
            </div>

            <!-- Table with Real Data -->
            <div v-else class="overflow-x-auto custom-scrollbar">
              <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-950/80 text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-800/80">
                  <tr>
                    <th class="p-3.5 pl-5">{{ isKm ? 'ចំណងជើងកិច្ចការ' : 'Assignment Title' }}</th>
                    <th class="p-3.5">{{ isKm ? 'វគ្គសិក្សា' : 'Course' }}</th>
                    <th class="p-3.5">{{ isKm ? 'ថ្ងៃផុតកំណត់' : 'Due Date' }}</th>
                    <th class="p-3.5 text-center">{{ isKm ? 'ស្ថានភាព' : 'Status' }}</th>
                    <th class="p-3.5 text-center">{{ isKm ? 'ពិន្ទុ' : 'Score' }}</th>
                    <th class="p-3.5">{{ isKm ? 'កាលបរិច្ឆេទដាក់ស្នើ' : 'Submitted On' }}</th>
                    <th class="p-3.5 pr-5 text-right">{{ isKm ? 'សកម្មភាព' : 'Action' }}</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                  <tr
                    v-for="row in assessments"
                    :key="row.id"
                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group cursor-pointer"
                    @click="openDetailModal(row)"
                  >
                    <!-- Assignment Title -->
                    <td class="p-3.5 pl-5">
                      <div class="flex items-center gap-3">
                        <div
                          :class="[
                            row.icon_bg || 'bg-purple-600/10 text-purple-600 dark:text-purple-300 border border-purple-500/20',
                            'w-8 h-8 rounded-xl flex items-center justify-center text-xs shrink-0 shadow-2xs'
                          ]"
                        >
                          📄
                        </div>
                        <div class="min-w-0">
                          <p class="font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate text-xs">
                            {{ row.title }}
                          </p>
                          <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate">
                            {{ row.subtitle || row.course }}
                          </p>
                        </div>
                      </div>
                    </td>

                    <!-- Course -->
                    <td class="p-3.5 text-slate-600 dark:text-slate-300 font-medium text-xs whitespace-nowrap">
                      {{ row.course }}
                    </td>

                    <!-- Due Date -->
                    <td class="p-3.5 whitespace-nowrap text-slate-600 dark:text-slate-300">
                      <p class="text-xs font-medium">{{ row.due_date }}</p>
                    </td>

                    <!-- Status Pill -->
                    <td class="p-3.5 text-center whitespace-nowrap">
                      <span
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold inline-block"
                        :class="statusBadgeClass(row.status_type)"
                      >
                        {{ statusText(row.status_type, row.status) }}
                      </span>
                    </td>

                    <!-- Score -->
                    <td class="p-3.5 text-center whitespace-nowrap">
                      <template v-if="row.score !== '-'">
                        <p
                          class="font-mono font-bold text-xs"
                          :class="[
                            parseInt(row.score) >= 80 ? 'text-emerald-600 dark:text-emerald-400' :
                            parseInt(row.score) >= 60 ? 'text-amber-600 dark:text-amber-400' :
                            'text-rose-600 dark:text-rose-400'
                          ]"
                        >
                          {{ row.score }}
                        </p>
                        <p class="text-[9px] text-slate-400 font-mono">{{ row.score_points }}</p>
                      </template>
                      <span v-else class="text-slate-400 font-mono">-</span>
                    </td>

                    <!-- Submitted On -->
                    <td class="p-3.5 whitespace-nowrap text-slate-600 dark:text-slate-300">
                      <span v-if="row.submitted_on !== '-'" class="text-xs font-medium font-mono">
                        {{ row.submitted_on }}
                      </span>
                      <span v-else class="text-slate-400 font-mono">-</span>
                    </td>

                    <!-- Actions -->
                    <td class="p-3.5 pr-5 text-right whitespace-nowrap" @click.stop>
                      <div class="flex items-center justify-end gap-1.5">
                        <!-- Upload Button -->
                        <button
                          v-if="row.can_upload"
                          @click="openUploadModal(row)"
                          class="px-2.5 py-1 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-bold text-[11px] flex items-center gap-1 shadow-2xs transition-colors cursor-pointer"
                          :title="isKm ? 'ដាក់ស្នើកិច្ចការ' : 'Upload Submission'"
                        >
                          <span>📤</span>
                          <span>{{ isKm ? 'ដាក់ស្នើ' : 'Submit' }}</span>
                        </button>
                        
                        <!-- View Details Button -->
                        <button
                          @click="openDetailModal(row)"
                          class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 hover:border-purple-500/40 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center text-xs transition-colors cursor-pointer"
                          :title="isKm ? 'មើលព័ត៌មានលម្អិត' : 'View Details'"
                        >
                          👁
                        </button>

                        <!-- Download File (if submitted) -->
                        <a
                          v-if="row.file_path"
                          :href="row.file_path"
                          target="_blank"
                          class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 hover:border-purple-500/40 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center text-xs transition-colors cursor-pointer"
                          :title="isKm ? 'ទាញយកឯកសារ' : 'Download File'"
                        >
                          ⬇
                        </a>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- TABLE FOOTER & DYNAMIC PAGINATION -->
            <div class="p-4 border-t border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
              <span class="text-slate-500 dark:text-slate-400 text-[11px] font-medium">
                {{ paginationInfo }}
              </span>

              <div v-if="totalPages > 1" class="flex items-center gap-1.5">
                <button
                  @click="goToPage(currentPage - 1)"
                  :disabled="currentPage <= 1"
                  class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center font-bold text-xs disabled:opacity-40 cursor-pointer"
                >
                  «
                </button>
                <button
                  v-for="p in totalPages"
                  :key="p"
                  @click="goToPage(p)"
                  class="w-7 h-7 rounded-lg font-bold text-xs shadow-2xs transition-all cursor-pointer"
                  :class="[
                    currentPage === p
                      ? 'bg-purple-600 text-white'
                      : 'bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                  ]"
                >
                  {{ p }}
                </button>
                <button
                  @click="goToPage(currentPage + 1)"
                  :disabled="currentPage >= totalPages"
                  class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white flex items-center justify-center font-bold text-xs disabled:opacity-40 cursor-pointer"
                >
                  »
                </button>
              </div>
            </div>

          </div>

          <!-- BOTTOM SECTION: Score by Course & Submission Donut & Recent Feedback -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- Card 1: Score by Course -->
            <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-3xl p-4 shadow-sm dark:shadow-xl space-y-3">
              <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-2">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white tracking-tight">{{ isKm ? 'ពិន្ទុតាមវគ្គសិក្សា' : 'Score by Course' }}</h3>
              </div>

              <div v-if="scoreByCourse.length === 0" class="py-6 text-center text-[11px] text-slate-400">
                {{ isKm ? 'មិនទាន់មានពិន្ទុតាមវគ្គ' : 'No course scores yet' }}
              </div>
              <div v-else class="space-y-2.5">
                <div v-for="sc in scoreByCourse" :key="sc.course" class="space-y-1">
                  <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-600 dark:text-slate-300 font-medium truncate max-w-[130px]">{{ sc.course }}</span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono">{{ sc.percentage }}%</span>
                  </div>
                  <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div
                      class="h-full rounded-full bg-gradient-to-r"
                      :class="sc.color || 'from-purple-500 to-indigo-600'"
                      :style="{ width: `${sc.percentage}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 2: Submission Status Donut -->
            <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-3xl p-4 shadow-sm dark:shadow-xl space-y-3">
              <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-2">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white tracking-tight">{{ isKm ? 'ស្ថានភាពដាក់ស្នើ' : 'Submission Status' }}</h3>
              </div>

              <div class="flex items-center justify-between gap-3 pt-1">
                <!-- Dynamic Donut Chart -->
                <div class="relative w-20 h-20 flex items-center justify-center shrink-0">
                  <svg class="w-20 h-20 -rotate-90 transform" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="currentColor" class="text-slate-100 dark:text-slate-800" stroke-width="4.5" />
                    <circle
                      v-for="(seg, idx) in submissionDonutSegments"
                      :key="'sub-' + idx"
                      cx="18" cy="18" r="15.9155"
                      fill="none"
                      :stroke="seg.color"
                      stroke-width="4.5"
                      :stroke-dasharray="seg.strokeDash"
                      :stroke-dashoffset="seg.strokeOffset"
                    />
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-sm font-black text-slate-900 dark:text-white font-mono">{{ submissionStatus.total }}</span>
                    <span class="text-[8px] text-slate-400">{{ isKm ? 'សរុប' : 'Total' }}</span>
                  </div>
                </div>

                <!-- Legend -->
                <div class="space-y-1.5 text-xs flex-1 min-w-0">
                  <div
                    v-for="item in submissionStatus.items"
                    :key="item.label"
                    class="flex items-center justify-between text-[10px]"
                  >
                    <div class="flex items-center gap-1.5 truncate">
                      <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: item.color }"></span>
                      <span class="text-slate-600 dark:text-slate-300 truncate">{{ statusText(item.label.toLowerCase(), item.label) }}</span>
                    </div>
                    <span class="font-bold text-slate-900 dark:text-white font-mono shrink-0">{{ item.count }} ({{ item.percentage }}%)</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card 3: Recent Feedback -->
            <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-3xl p-4 shadow-sm dark:shadow-xl space-y-2.5">
              <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-2">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white tracking-tight">{{ isKm ? 'មតិយោបល់គ្រូ' : 'Recent Feedback' }}</h3>
              </div>

              <div v-if="recentFeedback.length === 0" class="py-6 text-center text-[11px] text-slate-400">
                {{ isKm ? 'មិនទាន់មានមតិយោបល់' : 'No feedback yet' }}
              </div>
              <div v-else class="space-y-2">
                <div
                  v-for="fb in recentFeedback"
                  :key="fb.id"
                  class="p-2 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800/60 flex items-start gap-2.5"
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
                      <p class="text-[11px] font-bold text-slate-900 dark:text-white truncate max-w-[110px]">{{ fb.title }}</p>
                      <span class="text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ fb.score }}%</span>
                    </div>
                    <p class="text-[9px] text-slate-500 dark:text-slate-400 line-clamp-1 italic mt-0.5">{{ fb.feedback }}</p>
                  </div>
                </div>
              </div>
            </div>

          </div>

        </div>

        <!-- ================= RIGHT (4/12): WIDGETS SIDEBAR ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- WIDGET 1: Assessment Performance Donut -->
          <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ isKm ? 'ប្រសិទ្ធភាពវាយតម្លៃ' : 'Assessment Performance' }}</h3>
              <span class="text-[10px] font-semibold text-slate-500 font-mono">{{ isKm ? 'ពិន្ទុសរុប' : 'All Time' }}</span>
            </div>

            <div class="flex items-center justify-between gap-4">
              <!-- Donut Chart -->
              <div class="relative w-24 h-24 flex items-center justify-center shrink-0">
                <svg class="w-24 h-24 -rotate-90 transform" viewBox="0 0 36 36">
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="currentColor" class="text-slate-100 dark:text-slate-800" stroke-width="4.5" />
                  <circle
                    v-for="(seg, idx) in performanceDonutSegments"
                    :key="'perf-' + idx"
                    cx="18" cy="18" r="15.9155"
                    fill="none"
                    :stroke="seg.color"
                    stroke-width="4.5"
                    :stroke-dasharray="seg.strokeDash"
                    :stroke-dashoffset="seg.strokeOffset"
                  />
                </svg>

                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-slate-900 dark:text-white font-mono leading-none">{{ summary.average_score }}%</span>
                  <span class="text-[8px] text-slate-400 mt-0.5 font-medium">{{ isKm ? 'មធ្យម' : 'Avg' }}</span>
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
                    <span class="text-slate-600 dark:text-slate-300 font-medium">{{ item.label }}</span>
                  </div>
                  <span class="font-bold text-slate-900 dark:text-white font-mono">{{ item.count }} ({{ item.percentage }}%)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- WIDGET 2: Score Trend Line Chart -->
          <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ isKm ? 'និន្នាការនៃពិន្ទុ' : 'Score Trend' }}</h3>
              <span class="text-[10px] text-slate-400 font-mono">{{ isKm ? 'ថ្មីៗ' : 'Recent' }}</span>
            </div>

            <!-- SVG Chart Canvas or Empty State -->
            <div v-if="scoreTrend.points.length < 2" class="py-8 text-center text-xs text-slate-400 space-y-1">
              <p>📊</p>
              <p>{{ isKm ? 'មិនទាន់មានទិន្នន័យពិន្ទុគ្រប់គ្រាន់' : 'Not enough score data for trend chart' }}</p>
            </div>
            <div v-else class="relative w-full h-32 flex items-center justify-center">
              <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" class="w-full h-full overflow-visible">
                <!-- Grid Lines -->
                <line x1="20" y1="20" :x2="chartWidth - 20" y2="20" stroke="currentColor" class="text-slate-200 dark:text-slate-800" stroke-dasharray="2 2" stroke-width="1" />
                <line x1="20" y1="50" :x2="chartWidth - 20" y2="50" stroke="currentColor" class="text-slate-200 dark:text-slate-800" stroke-dasharray="2 2" stroke-width="1" />
                <line x1="20" y1="80" :x2="chartWidth - 20" y2="80" stroke="currentColor" class="text-slate-200 dark:text-slate-800" stroke-dasharray="2 2" stroke-width="1" />

                <!-- Left Labels -->
                <text x="2" y="23" fill="#94A3B8" font-size="6.5" font-family="monospace">100%</text>
                <text x="5" y="53" fill="#94A3B8" font-size="6.5" font-family="monospace">50%</text>
                <text x="5" y="83" fill="#94A3B8" font-size="6.5" font-family="monospace">0%</text>

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
                  stroke="#7E22CE"
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

              <!-- Dynamic Highlight Callout Tooltip (Only shown if highlight exists) -->
              <div
                v-if="scoreTrend.highlight"
                class="absolute top-2 right-4 px-2 py-1 rounded-lg bg-slate-900/90 text-white border border-purple-500/40 shadow-xl text-[9px] font-mono pointer-events-none"
              >
                <p class="text-slate-300">{{ scoreTrend.highlight.date }}</p>
                <p class="text-purple-300 font-bold">{{ scoreTrend.highlight.score_text }}</p>
              </div>
            </div>
          </div>

          <!-- WIDGET 3: Upcoming Deadlines -->
          <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ isKm ? 'កាលបរិច្ឆេទផុតកំណត់ខាងមុខ' : 'Upcoming Deadlines' }}</h3>
              <Link href="/student/calendar/schedule" class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline">
                {{ isKm ? 'មើលទាំងអស់' : 'View All' }}
              </Link>
            </div>

            <div v-if="upcomingDeadlines.length === 0" class="py-4 text-center text-xs text-slate-400">
              {{ isKm ? 'ពុំមានកិច្ចការជិតផុតកំណត់ទេ' : 'No upcoming deadlines' }}
            </div>
            <div v-else class="space-y-2">
              <div
                v-for="dl in upcomingDeadlines"
                :key="dl.id"
                class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/60 flex items-center justify-between gap-3 hover:border-purple-500/40 transition-all cursor-pointer"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      dl.icon_bg || 'bg-amber-500/10 text-amber-600 border border-amber-500/20',
                      'w-7 h-7 rounded-xl flex items-center justify-center text-xs shrink-0 shadow-2xs'
                    ]"
                  >
                    📅
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ dl.title }}</p>
                    <p class="text-[10px] text-slate-400">{{ dl.due_date }}</p>
                  </div>
                </div>
                <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
                  {{ dl.due_text }}
                </span>
              </div>
            </div>
          </div>

          <!-- WIDGET 4: Need Improvement? & AI Study Plan -->
          <div class="bg-gradient-to-br from-purple-50 dark:from-[#10132B] via-white dark:via-[#0F172A] to-indigo-50 dark:to-[#1E1138] border border-purple-200 dark:border-purple-900/50 rounded-3xl p-5 shadow-sm dark:shadow-2xl space-y-3.5">
            <div class="flex items-center justify-between border-b border-purple-200 dark:border-purple-900/40 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ isKm ? 'ចំណុចត្រូវពង្រឹង' : 'Need Improvement?' }}</h3>
              <span class="text-xs text-purple-600 dark:text-purple-400 font-bold">AI Support</span>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-400">
              {{ isKm ? 'ផ្តោតលើកិច្ចការទាំងនេះដើម្បីបង្កើនចំណាត់ថ្នាក់ និងពិន្ទុរបស់អ្នក៖' : 'Focus on these topics to improve your assessment outcomes:' }}
            </p>

            <div v-if="weakTopics.length > 0" class="flex flex-wrap gap-1.5">
              <span
                v-for="topic in weakTopics"
                :key="topic"
                class="px-2.5 py-1 rounded-xl bg-purple-100/70 dark:bg-slate-900/80 border border-purple-200 dark:border-slate-700/80 text-xs font-medium text-purple-900 dark:text-slate-200"
              >
                {{ topic }}
              </span>
            </div>
            <p v-else class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">
              {{ isKm ? 'អស្ចារ្យណាស់! អ្នកកំពុងបំពេញការងារបានយ៉ាងល្អ។' : 'Great job! No weak topics detected.' }}
            </p>

            <button
              @click="isAiPlanModalOpen = true"
              class="w-full py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-500/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95"
            >
              <span>✨</span>
              <span>{{ isKm ? 'ស្នើសុំផែនការសិក្សាពី AI' : 'Get AI Study Plan' }}</span>
            </button>
          </div>

        </div>

      </div>

    </div>

    <!-- ================= MODAL 1: ASSIGNMENT DETAIL & TEACHER FEEDBACK ================= -->
    <div
      v-if="isDetailModalOpen && selectedAssessmentForModal"
      class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl relative text-slate-800 dark:text-slate-200">
        
        <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-300 flex items-center justify-center text-sm font-bold">
              📄
            </div>
            <div>
              <h3 class="text-base font-black text-slate-900 dark:text-white">{{ selectedAssessmentForModal.title }}</h3>
              <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">{{ selectedAssessmentForModal.course }}</p>
            </div>
          </div>
          <button
            @click="isDetailModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Metrics Overview Bar -->
        <div class="grid grid-cols-4 gap-2 bg-slate-50 dark:bg-slate-950 p-3 rounded-2xl border border-slate-200 dark:border-slate-800 text-center text-xs">
          <div>
            <p class="text-[10px] text-slate-500 font-medium">{{ isKm ? 'ពិន្ទុ' : 'Score' }}</p>
            <p class="font-mono font-bold text-slate-900 dark:text-white">{{ selectedAssessmentForModal.score }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">{{ isKm ? 'ក្រិត្យពិន្ទុ' : 'Points' }}</p>
            <p class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ selectedAssessmentForModal.score_points }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">{{ isKm ? 'ស្ថានភាព' : 'Status' }}</p>
            <p
              class="font-mono font-bold capitalize"
              :class="selectedAssessmentForModal.status_type === 'graded' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
            >
              {{ statusText(selectedAssessmentForModal.status_type, selectedAssessmentForModal.status) }}
            </p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500 font-medium">{{ isKm ? 'ផុតកំណត់' : 'Due Date' }}</p>
            <p class="font-mono font-bold text-slate-700 dark:text-slate-300 truncate">{{ selectedAssessmentForModal.due_date }}</p>
          </div>
        </div>

        <!-- Student Submitted Notes or File -->
        <div v-if="selectedAssessmentForModal.notes || selectedAssessmentForModal.file_name" class="p-4 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-2 text-xs">
          <p class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
            <span>📝</span>
            <span>{{ isKm ? 'ការដាក់ស្នើរបស់អ្នក' : 'Your Submission' }}</span>
          </p>
          <p v-if="selectedAssessmentForModal.notes" class="text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">
            {{ selectedAssessmentForModal.notes }}
          </p>
          <div v-if="selectedAssessmentForModal.file_path" class="pt-1 flex items-center gap-2">
            <span class="text-xs">📎</span>
            <a
              :href="selectedAssessmentForModal.file_path"
              target="_blank"
              class="text-purple-600 dark:text-purple-400 font-medium hover:underline flex items-center gap-1 truncate"
            >
              <span>{{ selectedAssessmentForModal.file_name || 'Download Submitted File' }}</span>
              <span v-if="selectedAssessmentForModal.file_size" class="text-slate-400 text-[10px]">({{ selectedAssessmentForModal.file_size }})</span>
            </a>
          </div>
        </div>

        <!-- Teacher Feedback -->
        <div v-if="selectedAssessmentForModal.feedback" class="p-4 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-500/30 rounded-2xl space-y-1 text-xs">
          <p class="font-bold text-indigo-700 dark:text-indigo-300 flex items-center gap-1">
            <span>👨‍🏫</span>
            <span>{{ isKm ? 'មតិយោបល់ និងការវាយតម្លៃពីគ្រូ៖' : 'Teacher Feedback:' }}</span>
          </p>
          <p class="text-slate-700 dark:text-slate-200 italic leading-relaxed">{{ selectedAssessmentForModal.feedback }}</p>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
          <button
            @click="isDetailModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold cursor-pointer"
          >
            {{ isKm ? 'បិទ' : 'Close' }}
          </button>

          <button
            v-if="selectedAssessmentForModal.can_upload"
            @click="isDetailModalOpen = false; openUploadModal(selectedAssessmentForModal)"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5 cursor-pointer"
          >
            <span>📤</span>
            <span>{{ selectedAssessmentForModal.status_type === 'submitted' ? (isKm ? 'ដាក់ស្នើឡើងវិញ' : 'Resubmit Assignment') : (isKm ? 'ដាក់ស្នើកិច្ចការ' : 'Submit Assignment') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 2: UPLOAD & SUBMIT ASSIGNMENT ================= -->
    <div
      v-if="isUploadModalOpen && selectedAssessmentForModal"
      class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative text-slate-800 dark:text-slate-200">
        
        <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-1.5">
              <span>📤</span>
              <span>{{ isKm ? 'ដាក់ស្នើកិច្ចការ៖ ' : 'Submit Assignment: ' }}{{ selectedAssessmentForModal.title }}</span>
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
              {{ isKm ? 'ផុតកំណត់៖ ' : 'Due: ' }}{{ selectedAssessmentForModal.due_date }}
            </p>
          </div>
          <button
            @click="isUploadModalOpen = false"
            class="text-slate-400 hover:text-slate-900 dark:hover:text-white font-bold"
          >
            ✕
          </button>
        </div>

        <!-- Success Toast -->
        <div v-if="submitSuccessMessage" class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold text-center">
          ✓ {{ submitSuccessMessage }}
        </div>

        <!-- Error Banner -->
        <div v-if="fileError" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-bold">
          ⚠️ {{ fileError }}
        </div>

        <!-- Drag & Drop Zone -->
        <div
          @dragover.prevent
          @drop="handleFileDrop"
          class="p-6 bg-slate-50 dark:bg-slate-950 rounded-2xl border-2 border-dashed border-purple-500/40 text-center space-y-2.5 transition-colors"
        >
          <input
            ref="uploadFileInput"
            type="file"
            class="hidden"
            accept=".zip,.pdf,.docx,.doc,.js,.py,.txt,.png,.jpg,.jpeg"
            @change="handleFileSelect"
          />

          <div class="w-10 h-10 mx-auto rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg">
            📁
          </div>

          <div v-if="!submissionForm.file">
            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">
              {{ isKm ? 'អូសទម្លាក់ឯកសារនៅទីនេះ ឬចុចរុករកឯកសារ' : 'Drag & Drop files here or click to browse' }}
            </p>
            <p class="text-[10px] text-slate-400 mt-0.5">
              {{ isKm ? 'ប្រភេទឯកសារ៖ .zip, .pdf, .docx, .js, .py, រូបភាព (ទំហំអតិបរមា 10MB)' : 'Supported: .zip, .pdf, .docx, .js, .py, images (Max 10MB)' }}
            </p>
            <button
              type="button"
              @click="uploadFileInput?.click()"
              class="mt-2.5 px-4 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-purple-600 dark:text-purple-300 text-xs font-bold border border-slate-200 dark:border-slate-700 shadow-2xs cursor-pointer"
            >
              {{ isKm ? 'រុករកឯកសារ (Browse)' : 'Browse Files' }}
            </button>
          </div>

          <!-- Selected File Details -->
          <div v-else class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-purple-500/30 flex items-center justify-between gap-3 text-left">
            <div class="flex items-center gap-2 min-w-0">
              <span class="text-base">📄</span>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ submissionForm.file.name }}</p>
                <p class="text-[10px] text-slate-400">{{ (submissionForm.file.size / 1024 / 1024).toFixed(2) }} MB</p>
              </div>
            </div>
            <button
              type="button"
              @click="removeSelectedFile"
              class="text-rose-500 hover:text-rose-600 font-bold text-xs p-1"
              title="Remove File"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Notes / Comments / Links Input -->
        <div class="space-y-1.5 text-xs">
          <label class="font-bold text-slate-700 dark:text-slate-300">
            {{ isKm ? 'កំណត់ចំណាំ ឬតំណភ្ជាប់សម្រាប់គ្រូ (Notes / Links):' : 'Notes or Project Links for Teacher:' }}
          </label>
          <textarea
            v-model="submissionForm.notes"
            rows="3"
            :placeholder="isKm ? 'ពិពណ៌នាអំពីកិច្ចការរបស់អ្នក ឬបញ្ចូលតំណភ្ជាប់ (GitHub, Drive, Figma)...' : 'Describe your implementation, key points, or paste project links (GitHub, Google Drive, Figma)...'"
            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-purple-500 focus:outline-none"
          ></textarea>
        </div>

        <!-- Modal Actions -->
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
          <button
            type="button"
            @click="isUploadModalOpen = false"
            :disabled="submissionForm.processing"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold cursor-pointer"
          >
            {{ isKm ? 'បោះបង់' : 'Cancel' }}
          </button>
          <button
            type="button"
            @click="submitAssignmentHandler"
            :disabled="submissionForm.processing"
            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50"
          >
            <span v-if="submissionForm.processing">⏳ {{ isKm ? 'កំពុងដាក់ស្នើ...' : 'Submitting...' }}</span>
            <span v-else>✓ {{ isKm ? 'បញ្ជូនកិច្ចការ' : 'Submit Assignment' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 3: AI STUDY PLAN ================= -->
    <div
      v-if="isAiPlanModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative text-slate-800 dark:text-slate-200">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg">
              ✨
            </div>
            <div>
              <h3 class="text-base font-black text-slate-900 dark:text-white">
                {{ isKm ? 'ផែនការសិក្សាឆ្លាតវៃ (AI Study Plan)' : 'AI Personalized Study Plan' }}
              </h3>
              <p class="text-[11px] text-slate-400">
                {{ isKm ? 'ការណែនាំផ្អែកលើការវាយតម្លៃ និងកិច្ចការរបស់អ្នក' : 'Recommendations based on your assessments' }}
              </p>
            </div>
          </div>
          <button
            @click="isAiPlanModalOpen = false"
            class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center text-xs font-bold"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 text-xs leading-relaxed">
          <div class="p-3.5 rounded-2xl bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-500/30">
            <p class="font-bold text-purple-900 dark:text-purple-300">
              🎯 {{ isKm ? 'អនុសាសន៍សំខាន់សម្រាប់អ្នក៖' : 'Key Recommendation:' }}
            </p>
            <p class="text-slate-700 dark:text-slate-300 mt-1">
              {{ isKm
                ? 'សូមបញ្ចប់កិច្ចការដែលជិតផុតកំណត់ ឬហួសកាលកំណត់មុនគេបង្អស់។ បន្ទាប់មកពិនិត្យមតិយោបល់របស់គ្រូដើម្បីកែលម្អគុណភាពនៃការដាក់ស្នើលើកក្រោយ។'
                : 'Prioritize upcoming and overdue assignments first. Review teacher feedback on returned projects to continually improve quality.' }}
            </p>
          </div>

          <div v-if="weakTopics.length > 0" class="space-y-1.5">
            <p class="font-bold text-slate-900 dark:text-white">
              {{ isKm ? 'ប្រធានបទដែលគួរយកចិត្តទុកដាក់៖' : 'Topics needing attention:' }}
            </p>
            <ul class="list-disc list-inside space-y-1 text-slate-600 dark:text-slate-300">
              <li v-for="w in weakTopics" :key="w">{{ w }}</li>
            </ul>
          </div>
        </div>

        <div class="flex items-center justify-end pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
          <button
            @click="isAiPlanModalOpen = false"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold cursor-pointer"
          >
            {{ isKm ? 'យល់ព្រម' : 'Got it' }}
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 5px;
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.2);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.4);
}
</style>
