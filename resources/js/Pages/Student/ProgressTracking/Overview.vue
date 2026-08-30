<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

// Search Input
const searchQuery = ref('')

// Filter State for Charts
const timeRangeFilter = ref<'month' | 'quarter' | 'year'>('month')
const frequencyFilter = ref<'daily' | 'weekly'>('daily')

// Edit Goal Modal State
const isEditGoalOpen = ref(false)
const showToast = ref(false)
const toastMessage = ref('')

// Learning Goal Model
const learningGoal = ref({
  title: 'Become a Full Stack Web Developer',
  progress: 58,
  targetDate: 'Dec 31, 2025',
  timeRemaining: '7 months',
  weeklyTargetHours: 10
})

const editGoalForm = ref({
  title: 'Become a Full Stack Web Developer',
  targetDate: '2025-12-31',
  weeklyTargetHours: 10
})

// Top Metrics Summary
const topMetrics = ref({
  overallProgress: 62,
  completedItems: 28,
  totalItems: 45,
  overallTrend: '+12% this month',
  coursesEnrolled: 6,
  upcomingCompletions: 2,
  totalStudyTime: '48h 30m',
  weeklyStudyIncrease: '+8h 20m this week',
  certificatesEarned: 3,
  nextCertificate: 'Full Stack Developer'
})

// Weekly Goal
const weeklyGoal = ref({
  percent: 75,
  currentHours: '7h 30m',
  targetHours: '10h',
  targetDescription: 'Study 10 hours this week'
})

// Progress by Category
const categories = ref([
  { name: 'Courses', percent: 65, color: '#8b5cf6', strokeDash: '163.36', strokeOffset: '57.17' },
  { name: 'Quizzes', percent: 58, color: '#3b82f6', strokeDash: '163.36', strokeOffset: '68.61' },
  { name: 'Assignments', percent: 70, color: '#eab308', strokeDash: '163.36', strokeOffset: '49.00' },
  { name: 'Projects', percent: 60, color: '#10b981', strokeDash: '163.36', strokeOffset: '65.34' },
  { name: 'Others', percent: 40, color: '#94a3b8', strokeDash: '163.36', strokeOffset: '98.01' }
])

// Recent Activity List
const recentActivities = ref([
  {
    id: 1,
    title: 'Completed lesson',
    detail: 'JavaScript Functions',
    time: '2 hours ago',
    icon: '✓',
    iconBg: 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
  },
  {
    id: 2,
    title: 'Scored 85% on Quiz',
    detail: 'JavaScript Basics Quiz',
    time: 'Yesterday',
    icon: '📑',
    iconBg: 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
  },
  {
    id: 3,
    title: 'Started new course',
    detail: 'React.js Fundamentals',
    time: '2 days ago',
    icon: '📘',
    iconBg: 'bg-blue-500/20 text-blue-400 border border-blue-500/30'
  },
  {
    id: 4,
    title: 'Submitted assignment',
    detail: 'HTML & CSS Project',
    time: '3 days ago',
    icon: '📝',
    iconBg: 'bg-amber-500/20 text-amber-400 border border-amber-500/30'
  },
  {
    id: 5,
    title: 'Earned certificate',
    detail: 'Web Development Basics',
    time: '5 days ago',
    icon: '🎓',
    iconBg: 'bg-purple-500/20 text-purple-300 border border-purple-500/30'
  }
])

// Course Progress List
const courseProgressList = ref([
  {
    id: 1,
    title: 'JavaScript Fundamentals',
    level: 'Intermediate',
    lessonsCount: 24,
    progress: 65,
    icon: 'JS',
    iconBg: 'from-amber-400 to-amber-500 text-slate-950',
    status: 'in_progress',
    href: '/student/my-courses/current'
  },
  {
    id: 2,
    title: 'React.js Fundamentals',
    level: 'Beginner',
    lessonsCount: 18,
    progress: 40,
    icon: '⚛️',
    iconBg: 'from-cyan-500 to-blue-600 text-white',
    status: 'in_progress',
    href: '/student/my-courses/current'
  },
  {
    id: 3,
    title: 'Node.js & Express',
    level: 'Intermediate',
    lessonsCount: 20,
    progress: 20,
    icon: 'node',
    iconBg: 'from-emerald-500 to-green-600 text-white',
    status: 'in_progress',
    href: '/student/my-courses/current'
  },
  {
    id: 4,
    title: 'HTML & CSS',
    level: 'Beginner',
    lessonsCount: 16,
    progress: 100,
    icon: '5',
    iconBg: 'from-orange-500 to-red-600 text-white',
    status: 'completed',
    href: '/student/my-courses/overview'
  }
])

// Skills Progress List
const skillsProgressList = ref([
  { name: 'JavaScript', icon: 'JS', iconBg: 'from-amber-400 to-amber-500 text-slate-950', percent: 70, segments: 10, activeSegments: 7 },
  { name: 'HTML & CSS', icon: '5', iconBg: 'from-orange-500 to-red-600 text-white', percent: 90, segments: 10, activeSegments: 9 },
  { name: 'React.js', icon: '⚛️', iconBg: 'from-cyan-500 to-blue-600 text-white', percent: 40, segments: 10, activeSegments: 4 },
  { name: 'Node.js', icon: 'node', iconBg: 'from-emerald-500 to-green-600 text-white', percent: 30, segments: 10, activeSegments: 3 },
  { name: 'Problem Solving', icon: '🧠', iconBg: 'from-purple-600 to-indigo-600 text-white', percent: 60, segments: 10, activeSegments: 6 }
])

// Achievements Badges
const achievements = ref([
  {
    id: 1,
    title: 'Consistent Learner',
    subtitle: '7 Days Streak',
    icon: '⭐',
    shieldBg: 'from-emerald-500/30 to-emerald-800/40 border-emerald-500 text-emerald-400'
  },
  {
    id: 2,
    title: 'Quick Learner',
    subtitle: '5 Courses Started',
    icon: '⚡',
    shieldBg: 'from-purple-500/30 to-purple-800/40 border-purple-500 text-purple-300'
  },
  {
    id: 3,
    title: 'High Performer',
    subtitle: '85% Avg Score',
    icon: '👑',
    shieldBg: 'from-amber-500/30 to-amber-800/40 border-amber-500 text-amber-400'
  }
])

// Quiz Performance Summary
const quizStats = ref({
  averageScore: 72,
  averageTrend: '+8% this month',
  quizzesTaken: 24,
  quizzesTrend: '+6 this month',
  highestScore: 95,
  highestScoreQuiz: 'JavaScript Advanced Quiz',
  lowestScore: 35,
  lowestScoreQuiz: 'SQL JOIN Operations Quiz'
})

// Bar Chart Mock Data for May 1 - May 31 (31 bars)
const quizBarData = [
  45, 60, 75, 50, 85, 90, 65, 70, 80, 95, 60, 55, 70, 85, 90, 65, 80, 85, 70, 75, 90, 60, 75, 85, 95, 70, 65, 80, 90, 85, 95
]

// Methods
const saveGoal = () => {
  learningGoal.value.title = editGoalForm.value.title
  learningGoal.value.weeklyTargetHours = editGoalForm.value.weeklyTargetHours
  isEditGoalOpen.value = false
  
  toastMessage.value = 'Learning Goal updated successfully!'
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 3500)
}
</script>

<template>
  <StudentLayout
    title="Learning Overview"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Progress & Analytics', href: '/student/progress/overview' },
      { label: 'Learning Overview' }
    ]"
  >
    <div class="space-y-6 pb-16">
      
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
          class="fixed bottom-6 right-6 z-50 p-4 rounded-2xl bg-slate-900 border border-purple-500/50 shadow-2xl text-white flex items-center gap-3 max-w-md"
        >
          <span class="text-xl text-purple-400">✨</span>
          <p class="text-xs text-slate-200">{{ toastMessage }}</p>
        </div>
      </transition>

      <!-- 1. PAGE HEADER (Title with Trend Icon & Search Bar) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
            <span>Learning Overview</span>
            <span class="text-xl">📈</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Track your learning progress and see how close you are to achieving your goals.
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
              placeholder="Search courses, skills, topics..."
              class="w-full pl-10 pr-16 py-2 rounded-xl bg-slate-900/90 border border-slate-800 focus:border-purple-500 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-purple-500/50 shadow-inner transition-all"
            />
            <div class="absolute right-2.5 flex items-center gap-1">
              <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono text-slate-400 bg-slate-800 border border-slate-700 rounded shadow-xs">Ctrl K</kbd>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. TOP 5 METRIC CARDS ROW -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- CARD 1: Overall Progress -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Overall Progress</span>
          
          <div class="flex items-center gap-3">
            <!-- Circular Donut SVG -->
            <div class="relative w-14 h-14 flex items-center justify-center shrink-0">
              <svg class="w-14 h-14 transform -rotate-90" viewBox="0 0 50 50">
                <circle cx="25" cy="25" r="20" stroke="#1e293b" stroke-width="4" fill="transparent" />
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
                <span class="text-xs font-black text-white leading-none">{{ topMetrics.overallProgress }}%</span>
                <span class="text-[7px] text-slate-400 uppercase font-semibold">Good Progress</span>
              </div>
            </div>

            <div class="min-w-0">
              <p class="text-[10px] text-slate-400">You've completed</p>
              <p class="text-xs font-bold text-white leading-tight truncate">
                {{ topMetrics.completedItems }} of {{ topMetrics.totalItems }}
              </p>
              <p class="text-[10px] text-slate-400">total learning items</p>
            </div>
          </div>

          <p class="text-[10px] font-bold text-emerald-400 flex items-center gap-1">
            <span>▲</span>
            <span>{{ topMetrics.overallTrend }}</span>
          </p>
        </div>

        <!-- CARD 2: Courses Enrolled -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Courses Enrolled</span>

          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center text-xl shrink-0">
              📖
            </div>
            <div>
              <div class="text-2xl font-black text-white leading-none">{{ topMetrics.coursesEnrolled }}</div>
              <p class="text-[11px] text-slate-400 mt-1">Active Courses</p>
            </div>
          </div>

          <p class="text-[10px] text-slate-400 font-semibold">
            {{ topMetrics.upcomingCompletions }} upcoming completions
          </p>
        </div>

        <!-- CARD 3: Study Time -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Study Time</span>

          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-purple-600/20 text-purple-300 border border-purple-500/30 flex items-center justify-center text-xl shrink-0">
              ⏱️
            </div>
            <div>
              <div class="text-2xl font-black text-white leading-none">{{ topMetrics.totalStudyTime }}</div>
              <p class="text-[11px] text-slate-400 mt-1">Total Study Time</p>
            </div>
          </div>

          <p class="text-[10px] font-bold text-emerald-400 flex items-center gap-1">
            <span>▲</span>
            <span>{{ topMetrics.weeklyStudyIncrease }}</span>
          </p>
        </div>

        <!-- CARD 4: Certifications -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3">
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Certifications</span>

          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center text-xl shrink-0">
              🏆
            </div>
            <div>
              <div class="text-2xl font-black text-white leading-none">{{ topMetrics.certificatesEarned }}</div>
              <p class="text-[11px] text-slate-400 mt-1">Certificates Earned</p>
            </div>
          </div>

          <p class="text-[10px] text-slate-400 truncate">
            Next: <strong class="text-slate-300 font-bold">{{ topMetrics.nextCertificate }}</strong>
          </p>
        </div>

        <!-- CARD 5: Learning Goal -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1.5">
              <span class="text-xs">🎯</span>
              <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Learning Goal</span>
            </div>
            <button
              @click="isEditGoalOpen = true"
              type="button"
              class="text-[10px] font-bold text-purple-400 hover:text-purple-300 cursor-pointer"
            >
              Edit
            </button>
          </div>

          <div>
            <h4 class="text-xs font-bold text-white line-clamp-1">
              {{ learningGoal.title }}
            </h4>
            
            <!-- Progress Bar -->
            <div class="space-y-1 mt-2">
              <div class="flex items-center justify-between text-[10px] text-slate-400 font-mono">
                <span>Progress</span>
                <span class="font-bold text-white">{{ learningGoal.progress }}%</span>
              </div>
              <div class="w-full h-1.5 rounded-full bg-slate-900 overflow-hidden">
                <div
                  class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full"
                  :style="{ width: `${learningGoal.progress}%` }"
                ></div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between text-[9px] text-slate-400 pt-1 border-t border-slate-800/80">
            <span>Target: <strong class="text-slate-300">{{ learningGoal.targetDate }}</strong></span>
            <span>Rem: <strong class="text-slate-300">{{ learningGoal.timeRemaining }}</strong></span>
          </div>
        </div>

      </div>

      <!-- 3. MIDDLE SECTION: LEARNING PROGRESS CHART (5 Cols) | PROGRESS BY CATEGORY (3.5 Cols) | WEEKLY GOAL & RECENT (3.5 Cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- LEARNING PROGRESS MULTI-LINE CHART (Col 5.5 / 6) -->
        <div class="lg:col-span-5 bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">
              Learning Progress
            </h3>

            <!-- Dropdown Filters -->
            <div class="flex items-center gap-2">
              <select
                v-model="timeRangeFilter"
                class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-[11px] text-slate-300 focus:outline-none focus:border-purple-500 cursor-pointer"
              >
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
                <option value="year">This Year</option>
              </select>

              <select
                v-model="frequencyFilter"
                class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-[11px] text-slate-300 focus:outline-none focus:border-purple-500 cursor-pointer"
              >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
              </select>
            </div>
          </div>

          <!-- Multi-line SVG Line Chart -->
          <div class="relative w-full h-52 flex flex-col justify-between py-2">
            <!-- Grid Lines & Y-axis -->
            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none text-[9px] text-slate-500 font-mono">
              <div class="border-b border-slate-800/60 pb-0.5">100%</div>
              <div class="border-b border-slate-800/60 pb-0.5">75%</div>
              <div class="border-b border-slate-800/60 pb-0.5">50%</div>
              <div class="border-b border-slate-800/60 pb-0.5">25%</div>
              <div class="border-b border-slate-800/60 pb-0.5">0%</div>
            </div>

            <!-- SVG Curves -->
            <svg class="w-full h-full pl-7 pr-2" viewBox="0 0 400 160" preserveAspectRatio="none">
              <!-- Area Fill for Overall Progress -->
              <defs>
                <linearGradient id="purpleArea" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.25" />
                  <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0.0" />
                </linearGradient>
              </defs>

              <path
                d="M 0,140 Q 60,110 130,85 T 260,50 T 400,20 L 400,160 L 0,160 Z"
                fill="url(#purpleArea)"
              />

              <!-- Overall Progress Line (Purple) -->
              <path
                d="M 0,140 Q 60,110 130,85 T 260,50 T 400,20"
                fill="none"
                stroke="#8b5cf6"
                stroke-width="2.5"
                stroke-linecap="round"
              />

              <!-- Course Progress Line (Blue) -->
              <path
                d="M 0,135 Q 60,105 130,95 T 260,65 T 400,35"
                fill="none"
                stroke="#3b82f6"
                stroke-width="2"
                stroke-linecap="round"
              />

              <!-- Quiz Performance Line (Yellow) -->
              <path
                d="M 0,150 Q 60,130 130,115 T 260,90 T 400,55"
                fill="none"
                stroke="#eab308"
                stroke-width="2"
                stroke-linecap="round"
              />

              <!-- Assignment Score Line (Green) -->
              <path
                d="M 0,155 Q 60,145 130,130 T 260,110 T 400,85"
                fill="none"
                stroke="#10b981"
                stroke-width="2"
                stroke-linecap="round"
              />
            </svg>

            <!-- X-axis Labels -->
            <div class="flex justify-between pl-7 pr-2 text-[9px] text-slate-400 font-mono pt-2">
              <span>May 1</span>
              <span>May 6</span>
              <span>May 11</span>
              <span>May 16</span>
              <span>May 21</span>
              <span>May 26</span>
              <span>May 31</span>
            </div>
          </div>

          <!-- Chart Legend -->
          <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-800/80 text-[10px]">
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
              <span class="text-slate-300">Overall Progress</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
              <span class="text-slate-300">Course Progress</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
              <span class="text-slate-300">Quiz Performance</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
              <span class="text-slate-300">Assignment Score</span>
            </div>
          </div>
        </div>

        <!-- PROGRESS BY CATEGORY (Col 3.5 / 4) -->
        <div class="lg:col-span-3 bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between space-y-4">
          <h3 class="text-sm font-bold text-white uppercase tracking-wider">
            Progress by Category
          </h3>

          <!-- Circular Donut SVG Chart -->
          <div class="flex items-center justify-center py-2">
            <div class="relative w-36 h-36 flex items-center justify-center">
              <svg class="w-36 h-36 transform -rotate-90" viewBox="0 0 60 60">
                <circle cx="30" cy="30" r="26" stroke="#1e293b" stroke-width="6" fill="transparent" />
                <circle
                  cx="30"
                  cy="30"
                  r="26"
                  stroke="#8b5cf6"
                  stroke-width="6"
                  stroke-dasharray="163.36"
                  stroke-dashoffset="57.17"
                  fill="transparent"
                />
                <circle
                  cx="30"
                  cy="30"
                  r="26"
                  stroke="#3b82f6"
                  stroke-width="6"
                  stroke-dasharray="163.36"
                  stroke-dashoffset="98.01"
                  fill="transparent"
                  transform="rotate(110 30 30)"
                />
                <circle
                  cx="30"
                  cy="30"
                  r="26"
                  stroke="#eab308"
                  stroke-width="6"
                  stroke-dasharray="163.36"
                  stroke-dashoffset="114.35"
                  fill="transparent"
                  transform="rotate(220 30 30)"
                />
                <circle
                  cx="30"
                  cy="30"
                  r="26"
                  stroke="#10b981"
                  stroke-width="6"
                  stroke-dasharray="163.36"
                  stroke-dashoffset="130.68"
                  fill="transparent"
                  transform="rotate(290 30 30)"
                />
              </svg>
              <div class="absolute flex flex-col items-center justify-center text-center">
                <span class="text-lg font-black text-white leading-none">62%</span>
                <span class="text-[9px] text-slate-400 uppercase font-semibold mt-0.5">Overall</span>
              </div>
            </div>
          </div>

          <!-- Category Legend List -->
          <div class="space-y-2 text-xs border-t border-slate-800/80 pt-3">
            <div
              v-for="cat in categories"
              :key="cat.name"
              class="flex items-center justify-between"
            >
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: cat.color }"></span>
                <span class="text-slate-300 text-[11px]">{{ cat.name }}</span>
              </div>
              <span class="font-bold text-white font-mono text-xs">{{ cat.percent }}%</span>
            </div>
          </div>
        </div>

        <!-- RIGHT: WEEKLY GOAL & RECENT ACTIVITY (Col 3.5 / 4) -->
        <div class="lg:col-span-4 space-y-4 flex flex-col justify-between">
          
          <!-- WEEKLY GOAL CARD -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">Weekly Goal</h3>
              <span class="text-[10px] text-purple-400 font-bold hover:underline cursor-pointer">View All</span>
            </div>

            <div class="flex items-center gap-4">
              <!-- Small Circular Ring -->
              <div class="relative w-12 h-12 flex items-center justify-center shrink-0">
                <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 50 50">
                  <circle cx="25" cy="25" r="20" stroke="#1e293b" stroke-width="4" fill="transparent" />
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
                <span class="absolute text-[11px] font-black text-white">{{ weeklyGoal.percent }}%</span>
              </div>

              <div class="flex-1 space-y-1">
                <p class="text-xs font-bold text-white">{{ weeklyGoal.targetDescription }}</p>
                <div class="flex items-center justify-between text-[10px] text-slate-400 font-mono">
                  <span>{{ weeklyGoal.currentHours }} / {{ weeklyGoal.targetHours }}</span>
                </div>
                <div class="w-full h-1.5 rounded-full bg-slate-900 overflow-hidden">
                  <div
                    class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full"
                    :style="{ width: `${weeklyGoal.percent}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- RECENT ACTIVITY CARD -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3 flex-1">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-2.5">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">Recent Activity</h3>
              <span class="text-[10px] text-purple-400 font-bold hover:underline cursor-pointer">View All</span>
            </div>

            <div class="space-y-2.5 text-xs">
              <div
                v-for="act in recentActivities"
                :key="act.id"
                class="flex items-center justify-between gap-2 p-1.5 rounded-xl hover:bg-slate-900/60 transition-colors"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div :class="[act.iconBg, 'w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-bold shrink-0']">
                    {{ act.icon }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-semibold text-white truncate">{{ act.title }}</p>
                    <p class="text-[10px] text-purple-300 truncate">{{ act.detail }}</p>
                  </div>
                </div>
                <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ act.time }}</span>
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- 4. THIRD ROW: COURSE PROGRESS (4 Cols) | SKILLS PROGRESS (4 Cols) | ACHIEVEMENTS (4 Cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- COURSE PROGRESS (4 Cols) -->
        <div class="lg:col-span-4 bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Course Progress</h3>
            <Link href="/student/my-courses/enrolled" class="text-[10px] text-purple-400 font-bold hover:underline">
              View All
            </Link>
          </div>

          <div class="space-y-4">
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
                    <h4 class="text-xs font-bold text-white truncate">{{ crs.title }}</h4>
                    <p class="text-[10px] text-slate-400">{{ crs.level }} • {{ crs.lessonsCount }} Lessons</p>
                  </div>
                </div>

                <div class="text-right">
                  <span
                    :class="[
                      crs.status === 'completed' ? 'text-emerald-400' : 'text-white',
                      'text-xs font-black font-mono'
                    ]"
                  >
                    {{ crs.progress }}%
                  </span>
                  <p v-if="crs.status === 'completed'" class="text-[9px] text-emerald-400 font-semibold leading-none">Completed</p>
                </div>
              </div>

              <!-- Bar -->
              <div class="w-full h-1.5 rounded-full bg-slate-900 overflow-hidden">
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
        </div>

        <!-- SKILLS PROGRESS (4 Cols) -->
        <div class="lg:col-span-4 bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Skills Progress</h3>
            <span class="text-[10px] text-purple-400 font-bold hover:underline cursor-pointer">View All</span>
          </div>

          <div class="space-y-3.5">
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
                  <span class="font-bold text-white text-xs">{{ skl.name }}</span>
                </div>
                <span class="font-black text-white font-mono text-xs">{{ skl.percent }}%</span>
              </div>

              <!-- Segmented Pills Bar -->
              <div class="flex items-center gap-1">
                <div
                  v-for="sIdx in skl.segments"
                  :key="sIdx"
                  :class="[
                    sIdx <= skl.activeSegments
                      ? 'bg-gradient-to-r from-purple-500 to-indigo-500'
                      : 'bg-slate-900',
                    'h-2 flex-1 rounded-sm'
                  ]"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- ACHIEVEMENTS (4 Cols) -->
        <div class="lg:col-span-4 bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4 flex flex-col justify-between">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Achievements</h3>
            <Link href="/student/progress/achievements" class="text-[10px] text-purple-400 font-bold hover:underline">
              View All
            </Link>
          </div>

          <!-- 3 3D Shield Badges -->
          <div class="grid grid-cols-3 gap-2 text-center py-2">
            <div
              v-for="ach in achievements"
              :key="ach.id"
              class="flex flex-col items-center space-y-2"
            >
              <!-- 3D Shield Icon Box -->
              <div :class="[ach.shieldBg, 'w-14 h-16 rounded-2xl border-2 flex items-center justify-center text-2xl shadow-xl transform transition-transform hover:scale-105']">
                {{ ach.icon }}
              </div>
              <div>
                <h4 class="text-[11px] font-bold text-white leading-tight">{{ ach.title }}</h4>
                <p class="text-[9px] text-slate-400 mt-0.5">{{ ach.subtitle }}</p>
              </div>
            </div>
          </div>

          <div class="p-3 rounded-2xl bg-purple-950/30 border border-purple-500/20 text-center">
            <p class="text-[11px] text-purple-300 font-semibold">
              🔥 3 more achievements within reach this month!
            </p>
          </div>
        </div>

      </div>

      <!-- 5. BOTTOM ROW: QUIZ PERFORMANCE OVERVIEW -->
      <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider">
          Quiz Performance Overview
        </h3>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
          
          <!-- Left 4 Metric Boxes (Col 5) -->
          <div class="lg:col-span-5 grid grid-cols-2 gap-4">
            
            <!-- Average Score -->
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-1">
              <span class="text-[10px] font-bold text-slate-400 uppercase">Average Score</span>
              <div class="text-2xl font-black text-white">{{ quizStats.averageScore }}%</div>
              <p class="text-[10px] font-bold text-emerald-400">▲ {{ quizStats.averageTrend }}</p>
            </div>

            <!-- Quizzes Taken -->
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-1">
              <span class="text-[10px] font-bold text-slate-400 uppercase">Quizzes Taken</span>
              <div class="text-2xl font-black text-white">{{ quizStats.quizzesTaken }}</div>
              <p class="text-[10px] font-bold text-emerald-400">▲ {{ quizStats.quizzesTrend }}</p>
            </div>

            <!-- Highest Score -->
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-1">
              <span class="text-[10px] font-bold text-slate-400 uppercase">Highest Score</span>
              <div class="text-2xl font-black text-emerald-400">{{ quizStats.highestScore }}%</div>
              <p class="text-[10px] text-slate-400 truncate">{{ quizStats.highestScoreQuiz }}</p>
            </div>

            <!-- Lowest Score -->
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-1">
              <span class="text-[10px] font-bold text-slate-400 uppercase">Lowest Score</span>
              <div class="text-2xl font-black text-rose-400">{{ quizStats.lowestScore }}%</div>
              <p class="text-[10px] text-slate-400 truncate">{{ quizStats.lowestScoreQuiz }}</p>
            </div>

          </div>

          <!-- Right Vertical Bar Chart (Col 7) -->
          <div class="lg:col-span-7 space-y-2">
            <div class="relative w-full h-36 flex flex-col justify-between py-1">
              
              <!-- Grid lines & Y-axis -->
              <div class="absolute inset-0 flex flex-col justify-between pointer-events-none text-[8px] text-slate-500 font-mono">
                <div class="border-b border-slate-800/40 pb-0.5">100%</div>
                <div class="border-b border-slate-800/40 pb-0.5">75%</div>
                <div class="border-b border-slate-800/40 pb-0.5">50%</div>
                <div class="border-b border-slate-800/40 pb-0.5">25%</div>
                <div class="border-b border-slate-800/40 pb-0.5">0%</div>
              </div>

              <!-- 31 Vertical Bars -->
              <div class="flex items-end justify-between pl-6 pr-2 h-28 pt-2">
                <div
                  v-for="(score, bIdx) in quizBarData"
                  :key="bIdx"
                  class="flex-1 mx-0.5 bg-gradient-to-t from-purple-700 to-indigo-500 hover:from-purple-500 hover:to-indigo-400 rounded-t-sm transition-all cursor-pointer group relative"
                  :style="{ height: `${score}%` }"
                >
                  <!-- Tooltip -->
                  <div class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-7 left-1/2 -translate-x-1/2 bg-slate-900 border border-slate-700 text-white text-[9px] px-1.5 py-0.5 rounded font-mono pointer-events-none whitespace-nowrap z-20">
                    {{ score }}%
                  </div>
                </div>
              </div>

              <!-- X-axis Labels -->
              <div class="flex justify-between pl-6 pr-2 text-[9px] text-slate-400 font-mono pt-1">
                <span>May 1</span>
                <span>May 6</span>
                <span>May 11</span>
                <span>May 16</span>
                <span>May 21</span>
                <span>May 26</span>
                <span>May 31</span>
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
        class="relative w-full max-w-md bg-[#0e1424] border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isEditGoalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-white text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-lg">
            🎯
          </div>
          <div>
            <h3 class="text-base font-bold text-white">Edit Learning Goal</h3>
            <p class="text-xs text-slate-400">Set target timeline and commitments</p>
          </div>
        </div>

        <div class="space-y-4 text-xs">
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300 uppercase text-[11px]">Goal Title</label>
            <input
              v-model="editGoalForm.title"
              type="text"
              class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white focus:outline-none focus:border-purple-500 text-xs"
            />
          </div>

          <div class="space-y-1.5">
            <label class="font-bold text-slate-300 uppercase text-[11px]">Weekly Target Hours</label>
            <input
              v-model.number="editGoalForm.weeklyTargetHours"
              type="number"
              min="1"
              max="50"
              class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white focus:outline-none focus:border-purple-500 text-xs"
            />
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="isEditGoalOpen = false"
            type="button"
            class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold border border-slate-800 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="saveGoal"
            type="button"
            class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30 cursor-pointer"
          >
            Save Goal
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>
