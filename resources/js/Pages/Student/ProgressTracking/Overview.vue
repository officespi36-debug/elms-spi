<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { i18n } from '@/Services/i18n'

const props = defineProps<{
  overviewData?: Record<string, any>
}>()

const page = usePage()
const currentLocale = computed(() => (page.props as any)?.locale || 'km')
const isKm = computed(() => currentLocale.value === 'km' || i18n.locale.value === 'km')

// Search Input
const searchQuery = ref('')

// Filter State for Charts
const timeRangeFilter = ref<'month' | 'quarter' | 'year'>('month')
const frequencyFilter = ref<'daily' | 'weekly'>('daily')

// Edit Goal Modal State
const isEditGoalOpen = ref(false)
const isSavingGoal = ref(false)
const showToast = ref(false)
const toastMessage = ref('')

// Learning Goal Model (bound to real DB prop)
const learningGoal = computed(() => props.overviewData?.learningGoal || {
  title: isKm.value ? 'កំណត់គោលដៅសិក្សារបស់អ្នក' : 'Set Your Learning Goal',
  progress: 0,
  targetDate: '—',
  timeRemaining: '—',
  weeklyTargetHours: 10
})

const editGoalForm = ref({
  title: learningGoal.value.title,
  targetDate: '',
  weeklyTargetHours: learningGoal.value.weeklyTargetHours || 10
})

const openEditGoal = () => {
  editGoalForm.value = {
    title: learningGoal.value.title,
    targetDate: '',
    weeklyTargetHours: learningGoal.value.weeklyTargetHours || 10,
  }
  isEditGoalOpen.value = true
}

// Top Metrics Summary (100% real DB)
const topMetrics = computed(() => props.overviewData?.topMetrics || {
  overallProgress: 0,
  completedItems: 0,
  totalItems: 0,
  overallTrend: '0% completed',
  coursesEnrolled: 0,
  upcomingCompletions: 0,
  totalStudyTime: '0h 00m',
  weeklyStudyIncrease: '0h 00m this week',
  certificatesEarned: 0,
  nextCertificate: '—'
})

// Weekly Goal
const weeklyGoal = computed(() => props.overviewData?.weeklyGoal || {
  percent: 0,
  currentHours: '0h 00m',
  targetHours: '10h',
  targetDescription: isKm.value ? 'សិក្សា ១០ ម៉ោងក្នុងសប្តាហ៍នេះ' : 'Study 10 hours this week'
})

// Progress by Category
const categories = computed(() => props.overviewData?.categories || [
  { name: isKm.value ? 'វគ្គសិក្សា' : 'Courses', percent: 0, color: '#8b5cf6', strokeDash: '163.36', strokeOffset: '163.36' },
  { name: isKm.value ? 'កម្រងសំណួរ' : 'Quizzes', percent: 0, color: '#3b82f6', strokeDash: '163.36', strokeOffset: '163.36' },
  { name: isKm.value ? 'វិញ្ញាបនបត្រ' : 'Certificates', percent: 0, color: '#10b981', strokeDash: '163.36', strokeOffset: '163.36' }
])

// Recent Activity List
const recentActivities = computed(() => props.overviewData?.recentActivities || [])

// Course Progress List
const courseProgressList = computed(() => props.overviewData?.courseProgressList || [])

// Skills Progress List
const skillsProgressList = computed(() => props.overviewData?.skillsProgressList || [])

// Achievements Badges
const achievements = computed(() => props.overviewData?.achievements || [])
const achievementsNote = computed(() => props.overviewData?.achievementsNote || (isKm.value ? '🔥 នៅសល់ ៣ សមិទ្ធផលទៀតដែលអ្នកអាចសម្រេចបានក្នុងខែនេះ!' : '🔥 3 more achievements within reach this month!'))

// Quiz Performance Summary
const quizStats = computed(() => props.overviewData?.quizStats || {
  averageScore: 0,
  averageTrend: '0 attempts',
  quizzesTaken: 0,
  quizzesTrend: 'All time',
  highestScore: 0,
  highestScoreQuiz: '—',
  lowestScore: 0,
  lowestScoreQuiz: '—'
})

// Real 31-day Quiz Bar Chart Data
const quizBarData = computed<number[]>(() => props.overviewData?.quizBarData || Array(31).fill(0))
const quizBarLabels = computed<string[]>(() => props.overviewData?.quizBarLabels || ['Day 1', 'Day 6', 'Day 11', 'Day 16', 'Day 21', 'Day 26', 'Day 31'])

// Dynamic Multi-line Learning Progress Chart
const chartPoints = computed<any[]>(() => props.overviewData?.learningProgressChart?.points || [])
const chartLabels = computed<string[]>(() => props.overviewData?.learningProgressChart?.labels || ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7'])

const hoveredPointIndex = ref<number | null>(null)
const hoveredPoint = computed(() => {
  if (hoveredPointIndex.value === null || !chartPoints.value[hoveredPointIndex.value]) return null
  return chartPoints.value[hoveredPointIndex.value]
})

const hasProgressData = computed(() => {
  return chartPoints.value.some(p => (p.overall > 0 || p.course > 0 || p.quiz > 0 || p.assignment > 0))
})

const buildSvgPath = (key: 'overall' | 'course' | 'quiz' | 'assignment') => {
  const pts = chartPoints.value
  if (!pts || pts.length === 0) return 'M 10,145 L 390,145'
  return pts.map((p, i) => {
    const x = 10 + (i / Math.max(1, pts.length - 1)) * 380
    const val = Math.max(0, Math.min(100, p[key] ?? 0))
    const y = 145 - (val / 100) * 130
    return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)},${y.toFixed(1)}`
  }).join(' ')
}

const buildAreaPath = () => {
  const pts = chartPoints.value
  if (!pts || pts.length === 0) return 'M 10,145 L 390,145 Z'
  const line = pts.map((p, i) => {
    const x = 10 + (i / Math.max(1, pts.length - 1)) * 380
    const val = Math.max(0, Math.min(100, p.overall ?? 0))
    const y = 145 - (val / 100) * 130
    return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)},${y.toFixed(1)}`
  }).join(' ')
  return `${line} L 390,145 L 10,145 Z`
}

// Methods
const saveGoal = () => {
  if (isSavingGoal.value) return
  isSavingGoal.value = true

  router.post('/student/progress/goal', {
    title: editGoalForm.value.title,
    weekly_hours: editGoalForm.value.weeklyTargetHours,
    target_date: editGoalForm.value.targetDate || null,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isEditGoalOpen.value = false
      toastMessage.value = isKm.value ? 'គោលដៅសិក្សាត្រូវបានរក្សាទុកដោយជោគជ័យ!' : 'Learning Goal updated successfully!'
      showToast.value = true
      setTimeout(() => {
        showToast.value = false
      }, 3500)
    },
    onError: () => {
      toastMessage.value = isKm.value ? 'មិនអាចរក្សាទុកគោលដៅបានទេ សូមព្យាយាមម្តងទៀត។' : 'Failed to save goal. Please try again.'
      showToast.value = true
      setTimeout(() => {
        showToast.value = false
      }, 3500)
    },
    onFinish: () => {
      isSavingGoal.value = false
    }
  })
}
</script>

<template>
  <StudentLayout
    :title="isKm ? 'ទិដ្ឋភាពទូទៅនៃការរៀន' : 'Learning Overview'"
    :breadcrumbs="[
      { label: isKm ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard', href: '/student/dashboard' },
      { label: isKm ? 'វឌ្ឍនភាព & ការវិភាគ' : 'Progress & Analytics', href: '/student/progress/overview' },
      { label: isKm ? 'ទិដ្ឋភាពទូទៅនៃការរៀន' : 'Learning Overview' }
    ]"
  >
    <div class="space-y-6 pb-20">
      
      <!-- Toast Alert -->
      <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showToast"
          class="fixed bottom-6 right-6 z-50 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-purple-500/50 shadow-2xl text-slate-900 dark:text-white flex items-center gap-3 max-w-md"
        >
          <span class="text-xl text-purple-500">✨</span>
          <p class="text-xs text-slate-700 dark:text-slate-200 font-medium">{{ toastMessage }}</p>
        </div>
      </transition>

      <!-- 1. PAGE HEADER (Title with Trend Icon & Search Bar) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>{{ isKm ? 'ទិដ្ឋភាពទូទៅនៃការរៀន' : 'Learning Overview' }}</span>
            <span class="text-2xl">📈</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            {{ isKm ? 'តាមដានវឌ្ឍនភាពសិក្សា និងពិនិត្យមើលថាតើអ្នកខិតជិតដល់គោលដៅសិក្សារបស់អ្នកកម្រិតណា។' : 'Track your learning progress and see how close you are to achieving your goals.' }}
          </p>
        </div>

        <!-- Global Search Input with Ctrl K -->
        <div class="relative w-full md:w-96">
          <div class="relative flex items-center">
            <span class="absolute left-3.5 text-slate-400 pointer-events-none">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="isKm ? 'ស្វែងរកវគ្គសិក្សា ជំនាញ ប្រធានបទ...' : 'Search courses, skills, topics...'"
              class="w-full pl-10 pr-16 py-2.5 rounded-xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 focus:border-purple-500 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-purple-500/50 shadow-sm transition-all"
            />
            <div class="absolute right-2.5 flex items-center gap-1">
              <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded shadow-xs">Ctrl K</kbd>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. TOP 5 METRIC CARDS ROW -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- CARD 1: Overall Progress -->
        <div class="bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-3 transition-colors">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ isKm ? 'វឌ្ឍនភាពសរុប' : 'Overall Progress' }}</span>
          
          <div class="flex items-center gap-3">
            <!-- Circular Donut SVG -->
            <div class="relative w-14 h-14 flex items-center justify-center shrink-0">
              <svg class="w-14 h-14 transform -rotate-90" viewBox="0 0 50 50">
                <circle cx="25" cy="25" r="20" class="stroke-slate-200 dark:stroke-slate-800" stroke-width="4" fill="transparent" />
                <circle
                  cx="25"
                  cy="25"
                  r="20"
                  stroke="#8b5cf6"
                  stroke-width="4"
                  stroke-dasharray="125.66"
                  :stroke-dashoffset="125.66 - (125.66 * topMetrics.overallProgress) / 100"
                  stroke-linecap="round"
                  fill="transparent"
                />
              </svg>
              <div class="absolute flex flex-col items-center justify-center text-center">
                <span class="text-xs font-black text-slate-900 dark:text-white leading-none">{{ topMetrics.overallProgress }}%</span>
                <span class="text-[7px] text-slate-400 uppercase font-semibold">{{ topMetrics.overallProgress >= 70 ? 'Optimal' : (topMetrics.overallProgress > 0 ? 'Progress' : 'Ready') }}</span>
              </div>
            </div>

            <div class="min-w-0">
              <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ isKm ? 'បានបញ្ចប់' : 'Completed' }}</p>
              <p class="text-xs font-bold text-slate-900 dark:text-white leading-tight truncate">
                {{ topMetrics.completedItems }} / {{ topMetrics.totalItems }}
              </p>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ isKm ? 'មេរៀនសរុប' : 'learning items' }}</p>
            </div>
          </div>

          <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
            <span>▲</span>
            <span>{{ topMetrics.overallTrend }}</span>
          </p>
        </div>

        <!-- CARD 2: Courses Enrolled -->
        <div class="bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-3 transition-colors">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ isKm ? 'វគ្គសិក្សាដែលបានចុះឈ្មោះ' : 'Courses Enrolled' }}</span>

          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex items-center justify-center text-xl shrink-0">
              📖
            </div>
            <div>
              <div class="text-2xl font-black text-slate-900 dark:text-white leading-none">{{ topMetrics.coursesEnrolled }}</div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">{{ isKm ? 'វគ្គសិក្សាកំពុងរៀន' : 'Active Courses' }}</p>
            </div>
          </div>

          <p class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold truncate">
            {{ topMetrics.upcomingCompletions }} {{ isKm ? 'ជិតបានបញ្ចប់' : 'upcoming completions' }}
          </p>
        </div>

        <!-- CARD 3: Study Time -->
        <div class="bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-3 transition-colors">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ isKm ? 'ម៉ោងសិក្សា' : 'Study Time' }}</span>

          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-300 border border-purple-500/20 flex items-center justify-center text-xl shrink-0">
              ⏱️
            </div>
            <div>
              <div class="text-2xl font-black text-slate-900 dark:text-white leading-none">{{ topMetrics.totalStudyTime }}</div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">{{ isKm ? 'ម៉ោងសិក្សាសរុប' : 'Total Study Time' }}</p>
            </div>
          </div>

          <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
            <span>▲</span>
            <span>{{ topMetrics.weeklyStudyIncrease }}</span>
          </p>
        </div>

        <!-- CARD 4: Certifications -->
        <div class="bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-3 transition-colors">
          <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ isKm ? 'វិញ្ញាបនបត្រ' : 'Certifications' }}</span>

          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center justify-center text-xl shrink-0">
              🏆
            </div>
            <div>
              <div class="text-2xl font-black text-slate-900 dark:text-white leading-none">{{ topMetrics.certificatesEarned }}</div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">{{ isKm ? 'វិញ្ញាបនបត្រដែលទទួលបាន' : 'Certificates Earned' }}</p>
            </div>
          </div>

          <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">
            {{ isKm ? 'បន្ទាប់:' : 'Next:' }} <strong class="text-slate-700 dark:text-slate-300 font-bold">{{ topMetrics.nextCertificate }}</strong>
          </p>
        </div>

        <!-- CARD 5: Learning Goal -->
        <div class="bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-3 transition-colors">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5">
              <span class="text-xs">🎯</span>
              <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ isKm ? 'គោលដៅសិក្សា' : 'Learning Goal' }}</span>
            </div>
            <button
              @click="openEditGoal"
              type="button"
              class="text-[10px] font-bold text-purple-600 dark:text-purple-400 hover:text-purple-500 cursor-pointer"
            >
              {{ isKm ? 'កែប្រែ' : 'Edit' }}
            </button>
          </div>

          <div>
            <h4 class="text-xs font-bold text-slate-900 dark:text-white line-clamp-1">
              {{ learningGoal.title }}
            </h4>
            
            <!-- Progress Bar -->
            <div class="space-y-1 mt-2">
              <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 font-mono">
                <span>{{ isKm ? 'វឌ្ឍនភាព' : 'Progress' }}</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ learningGoal.progress }}%</span>
              </div>
              <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-900 overflow-hidden">
                <div
                  class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full"
                  :style="{ width: `${learningGoal.progress}%` }"
                ></div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between text-[9px] text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-200 dark:border-slate-800/80">
            <span>{{ isKm ? 'គោលដៅ:' : 'Target:' }} <strong class="text-slate-700 dark:text-slate-300">{{ learningGoal.targetDate }}</strong></span>
            <span>{{ isKm ? 'នៅសល់:' : 'Rem:' }} <strong class="text-slate-700 dark:text-slate-300">{{ learningGoal.timeRemaining }}</strong></span>
          </div>
        </div>

      </div>

      <!-- 3. MIDDLE SECTION: LEARNING PROGRESS CHART (5 Cols) | PROGRESS BY CATEGORY (3.5 Cols) | WEEKLY GOAL & RECENT (3.5 Cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- LEARNING PROGRESS MULTI-LINE CHART (Col 5) -->
        <div class="lg:col-span-5 bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                {{ isKm ? 'វឌ្ឍនភាពនៃការរៀន' : 'Learning Progress' }}
              </h3>
              <p v-if="hoveredPoint" class="text-[11px] text-purple-600 dark:text-purple-400 font-semibold mt-0.5">
                {{ hoveredPoint.date }}: {{ isKm ? 'រួម' : 'Overall' }} {{ hoveredPoint.overall }}% • {{ isKm ? 'វគ្គ' : 'Course' }} {{ hoveredPoint.course }}% • Quiz {{ hoveredPoint.quiz }}%
              </p>
            </div>

            <!-- Dropdown Filters -->
            <div class="flex items-center gap-2">
              <select
                v-model="timeRangeFilter"
                class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-[11px] text-slate-700 dark:text-slate-300 focus:outline-none focus:border-purple-500 cursor-pointer"
              >
                <option value="month">{{ isKm ? 'ខែនេះ' : 'This Month' }}</option>
                <option value="quarter">{{ isKm ? 'ត្រីមាសនេះ' : 'This Quarter' }}</option>
                <option value="year">{{ isKm ? 'ឆ្នាំនេះ' : 'This Year' }}</option>
              </select>

              <select
                v-model="frequencyFilter"
                class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-[11px] text-slate-700 dark:text-slate-300 focus:outline-none focus:border-purple-500 cursor-pointer"
              >
                <option value="daily">{{ isKm ? 'ប្រចាំថ្ងៃ' : 'Daily' }}</option>
                <option value="weekly">{{ isKm ? 'ប្រចាំសប្តាហ៍' : 'Weekly' }}</option>
              </select>
            </div>
          </div>

          <!-- Multi-line SVG Line Chart -->
          <div class="relative w-full h-52 flex flex-col justify-between py-2">
            <!-- Grid Lines & Y-axis -->
            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none text-[9px] text-slate-400 dark:text-slate-500 font-mono">
              <div class="border-b border-slate-200/80 dark:border-slate-800/60 pb-0.5">100%</div>
              <div class="border-b border-slate-200/80 dark:border-slate-800/60 pb-0.5">75%</div>
              <div class="border-b border-slate-200/80 dark:border-slate-800/60 pb-0.5">50%</div>
              <div class="border-b border-slate-200/80 dark:border-slate-800/60 pb-0.5">25%</div>
              <div class="border-b border-slate-200/80 dark:border-slate-800/60 pb-0.5">0%</div>
            </div>

            <!-- Dynamic SVG Curves -->
            <svg class="w-full h-full pl-7 pr-2" viewBox="0 0 400 160" preserveAspectRatio="none">
              <defs>
                <linearGradient id="purpleArea" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.25" />
                  <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0.0" />
                </linearGradient>
              </defs>

              <!-- Filled Area Under Overall Line -->
              <path
                :d="buildAreaPath()"
                fill="url(#purpleArea)"
              />

              <!-- Overall Progress Line (Purple) -->
              <path
                :d="buildSvgPath('overall')"
                fill="none"
                stroke="#8b5cf6"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />

              <!-- Course Progress Line (Blue) -->
              <path
                :d="buildSvgPath('course')"
                fill="none"
                stroke="#3b82f6"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />

              <!-- Quiz Performance Line (Yellow) -->
              <path
                :d="buildSvgPath('quiz')"
                fill="none"
                stroke="#eab308"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />

              <!-- Assignment Score Line (Green) -->
              <path
                :d="buildSvgPath('assignment')"
                fill="none"
                stroke="#10b981"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />

              <!-- Interactive Hover Circles -->
              <circle
                v-for="(p, pIdx) in chartPoints"
                :key="pIdx"
                :cx="10 + (pIdx / Math.max(1, chartPoints.length - 1)) * 380"
                :cy="145 - (Math.max(0, Math.min(100, p.overall ?? 0)) / 100) * 130"
                :r="hoveredPointIndex === pIdx ? 5 : 3"
                :fill="hoveredPointIndex === pIdx ? '#8b5cf6' : '#c084fc'"
                class="cursor-pointer transition-all"
                @mouseenter="hoveredPointIndex = pIdx"
                @mouseleave="hoveredPointIndex = null"
              />
            </svg>

            <!-- X-axis Labels (Strictly dynamic from calendar) -->
            <div class="flex justify-between pl-7 pr-2 text-[9px] text-slate-500 dark:text-slate-400 font-mono pt-2">
              <span v-for="(lbl, lIdx) in chartLabels" :key="lIdx">{{ lbl }}</span>
            </div>
          </div>

          <!-- Empty State Notice if no activity yet -->
          <div v-if="!hasProgressData" class="p-2.5 rounded-xl bg-purple-50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-500/20 text-center">
            <p class="text-[11px] text-purple-700 dark:text-purple-300 font-medium">
              💡 {{ isKm ? 'មិនទាន់មានទិន្នន័យវឌ្ឍនភាពសិក្សាត្រូវបានកត់ត្រាក្នុងកំឡុងពេលនេះនៅឡើយទេ' : 'No learning progress recorded in this period yet. Start learning to see your trend!' }}
            </p>
          </div>

          <!-- Chart Legend -->
          <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200 dark:border-slate-800/80 text-[10px]">
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
              <span class="text-slate-600 dark:text-slate-300">{{ isKm ? 'វឌ្ឍនភាពសរុប' : 'Overall Progress' }}</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
              <span class="text-slate-600 dark:text-slate-300">{{ isKm ? 'វគ្គសិក្សា' : 'Course Progress' }}</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
              <span class="text-slate-600 dark:text-slate-300">{{ isKm ? 'កម្រងសំណួរ' : 'Quiz Performance' }}</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
              <span class="text-slate-600 dark:text-slate-300">{{ isKm ? 'កិច្ចការ' : 'Assignment Score' }}</span>
            </div>
          </div>
        </div>

        <!-- PROGRESS BY CATEGORY (Col 3.5 / 4) -->
        <div class="lg:col-span-3 bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-4">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
            {{ isKm ? 'វឌ្ឍនភាពតាមប្រភេទ' : 'Progress by Category' }}
          </h3>

          <!-- Circular Donut SVG Chart (Dynamic Real Percentages) -->
          <div class="flex items-center justify-center py-2">
            <div class="relative w-36 h-36 flex items-center justify-center">
              <svg class="w-36 h-36 transform -rotate-90" viewBox="0 0 60 60">
                <!-- Background Circle Track -->
                <circle cx="30" cy="30" r="26" class="stroke-slate-200 dark:stroke-slate-800" stroke-width="6" fill="transparent" />
                
                <!-- Category 1: Courses (Purple) -->
                <circle
                  cx="30"
                  cy="30"
                  r="26"
                  stroke="#8b5cf6"
                  stroke-width="6"
                  stroke-linecap="round"
                  stroke-dasharray="163.36"
                  :stroke-dashoffset="163.36 - (163.36 * (categories[0]?.percent || 0)) / 100"
                  fill="transparent"
                />
                
                <!-- Category 2: Quizzes (Blue) -->
                <circle
                  cx="30"
                  cy="30"
                  r="26"
                  stroke="#3b82f6"
                  stroke-width="6"
                  stroke-linecap="round"
                  stroke-dasharray="163.36"
                  :stroke-dashoffset="163.36 - (163.36 * (categories[1]?.percent || 0)) / 100"
                  fill="transparent"
                  transform="rotate(120 30 30)"
                />
                
                <!-- Category 3: Certificates (Green) -->
                <circle
                  cx="30"
                  cy="30"
                  r="26"
                  stroke="#10b981"
                  stroke-width="6"
                  stroke-linecap="round"
                  stroke-dasharray="163.36"
                  :stroke-dashoffset="163.36 - (163.36 * (categories[2]?.percent || 0)) / 100"
                  fill="transparent"
                  transform="rotate(240 30 30)"
                />
              </svg>
              
              <!-- Center Real Percentage -->
              <div class="absolute flex flex-col items-center justify-center text-center">
                <span class="text-xl font-black text-slate-900 dark:text-white leading-none">{{ topMetrics.overallProgress }}%</span>
                <span class="text-[9px] text-slate-500 dark:text-slate-400 uppercase font-semibold mt-0.5">{{ isKm ? 'រួម' : 'Overall' }}</span>
              </div>
            </div>
          </div>

          <!-- Category Legend List -->
          <div class="space-y-2 text-xs border-t border-slate-200 dark:border-slate-800/80 pt-3">
            <div
              v-for="cat in categories"
              :key="cat.name"
              class="flex items-center justify-between"
            >
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: cat.color }"></span>
                <span class="text-slate-600 dark:text-slate-300 text-[11px]">{{ cat.name }}</span>
              </div>
              <span class="font-bold text-slate-900 dark:text-white font-mono text-xs">{{ cat.percent }}%</span>
            </div>
          </div>
        </div>

        <!-- RIGHT: WEEKLY GOAL & RECENT ACTIVITY (Col 3.5 / 4) -->
        <div class="lg:col-span-4 space-y-4 flex flex-col justify-between">
          
          <!-- WEEKLY GOAL CARD -->
          <div class="bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 pb-2.5">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">{{ isKm ? 'គោលដៅប្រចាំសប្តាហ៍' : 'Weekly Goal' }}</h3>
              <Link href="/student/progress/learning-time" class="text-[10px] text-purple-600 dark:text-purple-400 font-bold hover:underline">
                {{ isKm ? 'មើលលម្អិត' : 'View All' }}
              </Link>
            </div>

            <div class="flex items-center gap-4">
              <!-- Small Circular Ring -->
              <div class="relative w-12 h-12 flex items-center justify-center shrink-0">
                <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 50 50">
                  <circle cx="25" cy="25" r="20" class="stroke-slate-200 dark:stroke-slate-800" stroke-width="4" fill="transparent" />
                  <circle
                    cx="25"
                    cy="25"
                    r="20"
                    stroke="#8b5cf6"
                    stroke-width="4"
                    stroke-dasharray="125.66"
                    :stroke-dashoffset="125.66 - (125.66 * weeklyGoal.percent) / 100"
                    stroke-linecap="round"
                    fill="transparent"
                  />
                </svg>
                <span class="absolute text-[11px] font-black text-slate-900 dark:text-white">{{ weeklyGoal.percent }}%</span>
              </div>

              <div class="flex-1 space-y-1">
                <p class="text-xs font-bold text-slate-900 dark:text-white">{{ weeklyGoal.targetDescription }}</p>
                <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 font-mono">
                  <span>{{ weeklyGoal.currentHours }} / {{ weeklyGoal.targetHours }}</span>
                </div>
                <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-900 overflow-hidden">
                  <div
                    class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full"
                    :style="{ width: `${weeklyGoal.percent}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- RECENT ACTIVITY CARD -->
          <div class="bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3 flex-1">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 pb-2.5">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">{{ isKm ? 'សកម្មភាពថ្មីៗ' : 'Recent Activity' }}</h3>
              <Link href="/student/progress/learning-time" class="text-[10px] text-purple-600 dark:text-purple-400 font-bold hover:underline">
                {{ isKm ? 'មើលទាំងអស់' : 'View All' }}
              </Link>
            </div>

            <!-- List of real activities -->
            <div v-if="recentActivities.length > 0" class="space-y-2.5 text-xs">
              <div
                v-for="act in recentActivities"
                :key="act.id"
                class="flex items-center justify-between gap-2 p-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-900/60 transition-colors"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div :class="[act.iconBg, 'w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-bold shrink-0']">
                    {{ act.icon }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">{{ act.title }}</p>
                    <p class="text-[10px] text-purple-600 dark:text-purple-300 truncate">{{ act.detail }}</p>
                  </div>
                </div>
                <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ act.time }}</span>
              </div>
            </div>

            <!-- Friendly Clean Empty State -->
            <div v-else class="py-6 text-center text-slate-400 text-xs">
              <span class="text-2xl block mb-1">⏳</span>
              <p class="font-semibold text-slate-600 dark:text-slate-300">{{ isKm ? 'មិនទាន់មានសកម្មភាពថ្មីៗនៅឡើយទេ' : 'No learning activity yet' }}</p>
              <p class="text-[10px] text-slate-400 mt-0.5">{{ isKm ? 'រៀនមេរៀន ឬធ្វើ Quiz ដើម្បីកត់ត្រាសកម្មភាពរបស់អ្នកនៅទីនេះ' : 'Complete lessons or quizzes to see your activity timeline here.' }}</p>
            </div>
          </div>

        </div>

      </div>

      <!-- 4. THIRD ROW: COURSE PROGRESS (4 Cols) | SKILLS PROGRESS (4 Cols) | ACHIEVEMENTS (4 Cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- COURSE PROGRESS (4 Cols) -->
        <div class="lg:col-span-4 bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm dark:shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 pb-3">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">{{ isKm ? 'វឌ្ឍនភាពវគ្គសិក្សា' : 'Course Progress' }}</h3>
            <Link href="/student/my-courses/enrolled" class="text-[10px] text-purple-600 dark:text-purple-400 font-bold hover:underline">
              {{ isKm ? 'មើលទាំងអស់' : 'View All' }}
            </Link>
          </div>

          <div v-if="courseProgressList.length > 0" class="space-y-4">
            <div
              v-for="crs in courseProgressList"
              :key="crs.id"
              class="space-y-1.5"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                  <div :class="[crs.iconBg, 'w-7 h-7 rounded-lg bg-gradient-to-br font-bold text-[10px] flex items-center justify-center font-mono shadow-xs shrink-0']">
                    {{ crs.icon }}
                  </div>
                  <div class="min-w-0">
                    <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ crs.title }}</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ crs.level }} • {{ crs.lessonsCount }} {{ isKm ? 'មេរៀន' : 'Lessons' }}</p>
                  </div>
                </div>

                <div class="text-right">
                  <span
                    :class="[
                      crs.status === 'completed' ? 'text-emerald-500' : 'text-slate-900 dark:text-white',
                      'text-xs font-black font-mono'
                    ]"
                  >
                    {{ crs.progress }}%
                  </span>
                  <p v-if="crs.status === 'completed'" class="text-[9px] text-emerald-500 font-semibold leading-none">{{ isKm ? 'បានបញ្ចប់' : 'Completed' }}</p>
                </div>
              </div>

              <!-- Bar -->
              <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-900 overflow-hidden">
                <div
                  :class="[
                    crs.status === 'completed' ? 'bg-emerald-500' : 'bg-gradient-to-r from-purple-500 to-indigo-500',
                    'h-full rounded-full'
                  ]"
                  :style="{ width: `${crs.progress}%` }"
                ></div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="py-8 text-center text-slate-400 text-xs space-y-2">
            <span class="text-2xl block">📚</span>
            <p class="font-semibold text-slate-600 dark:text-slate-300">{{ isKm ? 'មិនទាន់បានចុះឈ្មោះវគ្គសិក្សានៅឡើយទេ' : 'No courses enrolled yet' }}</p>
            <Link href="/student/courses" class="inline-block px-3.5 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-600/20 border border-purple-200 dark:border-purple-500/30 text-purple-600 dark:text-purple-400 text-[11px] font-bold hover:bg-purple-100 dark:hover:bg-purple-600/30 transition-colors">
              {{ isKm ? 'ស្វែងរកវគ្គសិក្សា' : 'Browse Courses' }}
            </Link>
          </div>
        </div>

        <!-- SKILLS PROGRESS (4 Cols) -->
        <div class="lg:col-span-4 bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm dark:shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 pb-3">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">{{ isKm ? 'វឌ្ឍនភាពជំនាញ' : 'Skills Progress' }}</h3>
            <Link href="/student/progress/achievements" class="text-[10px] text-purple-600 dark:text-purple-400 font-bold hover:underline">
              {{ isKm ? 'មើលទាំងអស់' : 'View All' }}
            </Link>
          </div>

          <div v-if="skillsProgressList.length > 0" class="space-y-3.5">
            <div
              v-for="skl in skillsProgressList"
              :key="skl.name"
              class="space-y-1.5"
            >
              <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                  <div :class="[skl.iconBg, 'w-6 h-6 rounded-md bg-gradient-to-br font-bold text-[9px] flex items-center justify-center font-mono shrink-0']">
                    {{ skl.icon }}
                  </div>
                  <span class="font-bold text-slate-900 dark:text-white text-xs">{{ skl.name }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white font-mono text-xs">{{ skl.percent }}%</span>
              </div>

              <!-- Segmented Pills Bar -->
              <div class="flex items-center gap-1">
                <div
                  v-for="sIdx in skl.segments"
                  :key="sIdx"
                  :class="[
                    sIdx <= skl.activeSegments
                      ? 'bg-gradient-to-r from-purple-500 to-indigo-500'
                      : 'bg-slate-100 dark:bg-slate-900',
                    'h-2 flex-1 rounded-sm'
                  ]"
                ></div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="py-8 text-center text-slate-400 text-xs">
            <span class="text-2xl block mb-1">💡</span>
            <p class="font-semibold text-slate-600 dark:text-slate-300">{{ isKm ? 'មិនទាន់មានទិន្នន័យជំនាញនៅឡើយទេ' : 'No skills data recorded yet' }}</p>
            <p class="text-[10px] text-slate-400 mt-0.5">{{ isKm ? 'ជំនាញនឹងគណនាដោយស្វ័យប្រវត្តិតាមវគ្គសិក្សារបស់អ្នក' : 'Skills will populate automatically as you complete lessons.' }}</p>
          </div>
        </div>

        <!-- ACHIEVEMENTS (4 Cols) -->
        <div class="lg:col-span-4 bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm dark:shadow-xl space-y-4 flex flex-col justify-between">
          <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 pb-3">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">{{ isKm ? 'សមិទ្ធផល' : 'Achievements' }}</h3>
            <Link href="/student/progress/achievements" class="text-[10px] text-purple-600 dark:text-purple-400 font-bold hover:underline">
              {{ isKm ? 'មើលទាំងអស់' : 'View All' }}
            </Link>
          </div>

          <!-- 3 Milestone Shields with Real Status -->
          <div class="grid grid-cols-3 gap-2 text-center py-2">
            <div
              v-for="ach in achievements"
              :key="ach.id"
              class="flex flex-col items-center space-y-2"
            >
              <!-- 3D Shield Icon Box -->
              <div :class="[ach.shieldBg, 'w-14 h-16 rounded-2xl border-2 flex items-center justify-center text-2xl shadow-md transform transition-transform hover:scale-105']">
                {{ ach.icon }}
              </div>
              <div>
                <h4 class="text-[11px] font-bold text-slate-900 dark:text-white leading-tight line-clamp-1">{{ ach.title }}</h4>
                <p class="text-[9px] text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ ach.subtitle }}</p>
              </div>
            </div>
          </div>

          <div class="p-3 rounded-2xl bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-500/20 text-center">
            <p class="text-[11px] text-purple-700 dark:text-purple-300 font-semibold">
              {{ achievementsNote }}
            </p>
          </div>
        </div>

      </div>

      <!-- 5. BOTTOM ROW: QUIZ PERFORMANCE OVERVIEW -->
      <div class="bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm dark:shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
          {{ isKm ? 'ទិដ្ឋភាពទូទៅនៃលទ្ធផល Quiz' : 'Quiz Performance Overview' }}
        </h3>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
          
          <!-- Left 4 Metric Boxes (Col 5) -->
          <div class="lg:col-span-5 grid grid-cols-2 gap-4">
            
            <!-- Average Score -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-1">
              <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">{{ isKm ? 'ពិន្ទុមធ្យម' : 'Average Score' }}</span>
              <div class="text-2xl font-black text-slate-900 dark:text-white">{{ quizStats.averageScore }}%</div>
              <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">▲ {{ quizStats.averageTrend }}</p>
            </div>

            <!-- Quizzes Taken -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-1">
              <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">{{ isKm ? 'Quiz បានធ្វើ' : 'Quizzes Taken' }}</span>
              <div class="text-2xl font-black text-slate-900 dark:text-white">{{ quizStats.quizzesTaken }}</div>
              <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">▲ {{ quizStats.quizzesTrend }}</p>
            </div>

            <!-- Highest Score -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-1">
              <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">{{ isKm ? 'ពិន្ទុខ្ពស់បំផុត' : 'Highest Score' }}</span>
              <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ quizStats.highestScore }}%</div>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ quizStats.highestScoreQuiz }}</p>
            </div>

            <!-- Lowest Score -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-1">
              <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase">{{ isKm ? 'ពិន្ទុទាបបំផុត' : 'Lowest Score' }}</span>
              <div class="text-2xl font-black text-rose-500">{{ quizStats.lowestScore }}%</div>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ quizStats.lowestScoreQuiz }}</p>
            </div>

          </div>

          <!-- Right Vertical Bar Chart (Col 7) -->
          <div class="lg:col-span-7 space-y-2">
            <div class="relative w-full h-36 flex flex-col justify-between py-1">
              
              <!-- Grid lines & Y-axis -->
              <div class="absolute inset-0 flex flex-col justify-between pointer-events-none text-[8px] text-slate-400 dark:text-slate-500 font-mono">
                <div class="border-b border-slate-200/80 dark:border-slate-800/40 pb-0.5">100%</div>
                <div class="border-b border-slate-200/80 dark:border-slate-800/40 pb-0.5">75%</div>
                <div class="border-b border-slate-200/80 dark:border-slate-800/40 pb-0.5">50%</div>
                <div class="border-b border-slate-200/80 dark:border-slate-800/40 pb-0.5">25%</div>
                <div class="border-b border-slate-200/80 dark:border-slate-800/40 pb-0.5">0%</div>
              </div>

              <!-- 31 Vertical Bars with Real Calendar Dates -->
              <div class="flex items-end justify-between pl-6 pr-2 h-28 pt-2">
                <div
                  v-for="(score, bIdx) in quizBarData"
                  :key="bIdx"
                  class="flex-1 mx-0.5 bg-gradient-to-t from-purple-700 to-indigo-500 hover:from-purple-500 hover:to-indigo-400 rounded-t-sm transition-all cursor-pointer group relative min-h-[2px]"
                  :style="{ height: `${Math.max(2, score)}%` }"
                >
                  <!-- Tooltip -->
                  <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-900 border border-slate-700 text-white text-[9px] px-1.5 py-0.5 rounded font-mono pointer-events-none whitespace-nowrap z-20 shadow-lg">
                    {{ score }}%
                  </div>
                </div>
              </div>

              <!-- X-axis Labels (Dynamic Calendar Dates) -->
              <div class="flex justify-between pl-6 pr-2 text-[9px] text-slate-500 dark:text-slate-400 font-mono pt-1">
                <span v-for="(lbl, lIdx) in quizBarLabels" :key="lIdx">{{ lbl }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- 1. EDIT GOAL MODAL -->
    <div
      v-if="isEditGoalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isEditGoalOpen = false"
    >
      <div
        class="relative w-full max-w-md bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isEditGoalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-white text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-300 flex items-center justify-center text-lg">
            🎯
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ isKm ? 'កែប្រែគោលដៅសិក្សា' : 'Edit Learning Goal' }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ isKm ? 'កំណត់គោលដៅ និងម៉ោងសិក្សាប្រចាំសប្តាហ៍' : 'Set target timeline and commitments' }}</p>
          </div>
        </div>

        <div class="space-y-4 text-xs">
          <div class="space-y-1.5">
            <label class="font-bold text-slate-700 dark:text-slate-300 uppercase text-[11px]">{{ isKm ? 'ចំណងជើងគោលដៅ' : 'Goal Title' }}</label>
            <input
              v-model="editGoalForm.title"
              type="text"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 text-xs"
            />
          </div>

          <div class="space-y-1.5">
            <label class="font-bold text-slate-700 dark:text-slate-300 uppercase text-[11px]">{{ isKm ? 'ម៉ោងសិក្សាគោលដៅប្រចាំសប្តាហ៍' : 'Weekly Target Hours' }}</label>
            <input
              v-model.number="editGoalForm.weeklyTargetHours"
              type="number"
              min="1"
              max="50"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-purple-500 text-xs"
            />
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="isEditGoalOpen = false"
            type="button"
            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold border border-slate-200 dark:border-slate-800 cursor-pointer transition-colors"
          >
            {{ isKm ? 'បោះបង់' : 'Cancel' }}
          </button>
          <button
            @click="saveGoal"
            :disabled="isSavingGoal"
            type="button"
            class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30 cursor-pointer transition-all disabled:opacity-50"
          >
            {{ isSavingGoal ? (isKm ? 'កំពុងរក្សាទុក...' : 'Saving...') : (isKm ? 'រក្សាទុកគោលដៅ' : 'Save Goal') }}
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>
