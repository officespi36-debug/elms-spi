<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface RoadmapStage {
  id: number
  stageNumber: number
  title: string
  subtitle: string
  status: 'COMPLETED' | 'CURRENT' | 'UPCOMING' | 'LOCKED'
  coursesCount: string
  progress: number
  courses: {
    id: number
    title: string
    description: string
    level: string
    lessonsCount: number
    duration: string
    progress: number
    status: 'completed' | 'in_progress' | 'not_started' | 'locked'
    icon: string
    iconBg: string
    href: string
  }[]
}

// State
const viewMode = ref<'roadmap' | 'list'>('roadmap')
const selectedStageIndex = ref(2) // Default Stage 3 (JavaScript Core)
const isEditGoalOpen = ref(false)
const showToast = ref(false)
const toastMessage = ref('')

// Learning Goal State
const learningGoal = ref({
  title: 'Become a Full Stack Web Developer',
  track: 'fullstack',
  targetCompletion: '6 Months',
  weeklyStudyTime: '10-15 hours',
  overallProgress: 58,
  completedCourses: 6,
  inProgressCourses: 3,
  upcomingCourses: 7,
  totalCourses: 16
})

// Edit Goal Form Model
const editGoalForm = ref({
  title: 'Become a Full Stack Web Developer',
  track: 'fullstack',
  targetCompletion: '6 Months',
  weeklyHours: '10-15 hours'
})

// Search Query in header
const searchQuery = ref('')

// Recommended Stages Data (Matching Screenshot 6 Stages)
const stages = ref<RoadmapStage[]>([
  {
    id: 1,
    stageNumber: 1,
    title: 'Foundations',
    subtitle: 'HTML5 & Web Basics',
    status: 'COMPLETED',
    coursesCount: '2 / 2 courses',
    progress: 100,
    courses: [
      {
        id: 101,
        title: 'HTML5 Semantic Web & Accessibility',
        description: 'Learn modern semantic tags, multimedia embeds, and accessible form structures.',
        level: 'Beginner',
        lessonsCount: 16,
        duration: '8h 30m',
        progress: 100,
        status: 'completed',
        icon: 'HTML',
        iconBg: 'from-orange-500 to-amber-600',
        href: '/student/my-courses/overview'
      },
      {
        id: 102,
        title: 'Web Standards, Git & Version Control',
        description: 'Master Git commit workflows, GitHub repositories, and collaborative branching.',
        level: 'Beginner',
        lessonsCount: 12,
        duration: '6h 15m',
        progress: 100,
        status: 'completed',
        icon: 'GIT',
        iconBg: 'from-rose-500 to-red-600',
        href: '/student/my-courses/overview'
      }
    ]
  },
  {
    id: 2,
    stageNumber: 2,
    title: 'Frontend Basics',
    subtitle: 'CSS3, Flexbox & Grid',
    status: 'COMPLETED',
    coursesCount: '2 / 2 courses',
    progress: 100,
    courses: [
      {
        id: 201,
        title: 'CSS3 Styling & Responsive Design',
        description: 'Build mobile-first fluid designs using Flexbox, CSS Grid, and media queries.',
        level: 'Beginner',
        lessonsCount: 20,
        duration: '10h 45m',
        progress: 100,
        status: 'completed',
        icon: 'CSS',
        iconBg: 'from-blue-500 to-cyan-600',
        href: '/student/my-courses/overview'
      },
      {
        id: 202,
        title: 'TailwindCSS & Modern UI Frameworks',
        description: 'Rapid UI engineering with utility classes, dark modes, and components.',
        level: 'Intermediate',
        lessonsCount: 14,
        duration: '7h 20m',
        progress: 100,
        status: 'completed',
        icon: 'UI',
        iconBg: 'from-cyan-400 to-teal-600',
        href: '/student/my-courses/overview'
      }
    ]
  },
  {
    id: 3,
    stageNumber: 3,
    title: 'JavaScript Core',
    subtitle: 'Logic, Functions & DOM',
    status: 'CURRENT',
    coursesCount: 'In Progress (60%)',
    progress: 60,
    courses: [
      {
        id: 301,
        title: 'JavaScript Fundamentals',
        description: 'Learn core JavaScript concepts, variables, data types, and operators.',
        level: 'Intermediate',
        lessonsCount: 24,
        duration: '12h 30m',
        progress: 100,
        status: 'completed',
        icon: 'JS',
        iconBg: 'from-amber-400 to-amber-500',
        href: '/student/my-courses/overview'
      },
      {
        id: 302,
        title: 'JavaScript Functions & Scope',
        description: 'Deep dive into functions, scope, closures, and hoisting.',
        level: 'Intermediate',
        lessonsCount: 18,
        duration: '8h 45m',
        progress: 20,
        status: 'in_progress',
        icon: 'JS',
        iconBg: 'from-purple-600 to-indigo-600',
        href: '/student/my-courses/current'
      },
      {
        id: 303,
        title: 'JavaScript DOM Manipulation',
        description: 'Learn how to interact with HTML elements using JavaScript.',
        level: 'Intermediate',
        lessonsCount: 20,
        duration: '10h 15m',
        progress: 0,
        status: 'not_started',
        icon: '📄',
        iconBg: 'from-blue-600 to-indigo-700',
        href: '/student/courses/1/overview'
      }
    ]
  },
  {
    id: 4,
    stageNumber: 4,
    title: 'Frontend Advanced',
    subtitle: 'Async JS & React',
    status: 'UPCOMING',
    coursesCount: 'Upcoming (0/3)',
    progress: 0,
    courses: [
      {
        id: 401,
        title: 'Asynchronous JavaScript & Fetch APIs',
        description: 'Master Promises, async/await, API data fetching, and error handling.',
        level: 'Intermediate',
        lessonsCount: 16,
        duration: '8h 00m',
        progress: 0,
        status: 'locked',
        icon: '⚡',
        iconBg: 'from-slate-700 to-slate-800',
        href: '#'
      },
      {
        id: 402,
        title: 'React.js & Modern Component Architecture',
        description: 'Build interactive SPAs using React Hooks, props, state, and router.',
        level: 'Advanced',
        lessonsCount: 28,
        duration: '16h 30m',
        progress: 0,
        status: 'locked',
        icon: '⚛️',
        iconBg: 'from-slate-700 to-slate-800',
        href: '#'
      }
    ]
  },
  {
    id: 5,
    stageNumber: 5,
    title: 'Backend Basics',
    subtitle: 'Node.js & Databases',
    status: 'UPCOMING',
    coursesCount: 'Upcoming (0/3)',
    progress: 0,
    courses: [
      {
        id: 501,
        title: 'Node.js, Express & RESTful APIs',
        description: 'Server-side programming, HTTP routing, middleware, and authentication.',
        level: 'Advanced',
        lessonsCount: 22,
        duration: '14h 00m',
        progress: 0,
        status: 'locked',
        icon: '🟢',
        iconBg: 'from-slate-700 to-slate-800',
        href: '#'
      },
      {
        id: 502,
        title: 'Database Design & PostgreSQL / MySQL',
        description: 'Relational data modeling, foreign keys, index optimization, and SQL queries.',
        level: 'Advanced',
        lessonsCount: 20,
        duration: '12h 15m',
        progress: 0,
        status: 'locked',
        icon: '🗄️',
        iconBg: 'from-slate-700 to-slate-800',
        href: '#'
      }
    ]
  },
  {
    id: 6,
    stageNumber: 6,
    title: 'Full Stack Mastery',
    subtitle: 'Deployment & Capstone',
    status: 'UPCOMING',
    coursesCount: 'Upcoming (0/2)',
    progress: 0,
    courses: [
      {
        id: 601,
        title: 'Full-Stack Capstone Project & Cloud Deployment',
        description: 'Deploy real-world web applications on cloud infrastructure with CI/CD.',
        level: 'Mastery',
        lessonsCount: 15,
        duration: '20h 00m',
        progress: 0,
        status: 'locked',
        icon: '🚀',
        iconBg: 'from-slate-700 to-slate-800',
        href: '#'
      }
    ]
  }
])

const activeStage = computed(() => stages.value[selectedStageIndex.value])

// Skills List
const allSkills = [
  'HTML', 'CSS', 'JavaScript', 'React', 'Node.js',
  'Express.js', 'MongoDB', 'Git', 'API Integration',
  'Problem Solving', 'Responsive Design', 'And more...'
]

// Accordion for Recommended Resources
const isResourcesOpen = ref(false)
const stageResources = [
  { name: 'JavaScript MDN Documentation Guide', type: 'Documentation', link: 'https://developer.mozilla.org/en-US/docs/Web/JavaScript' },
  { name: 'JavaScript Functions & Scope Cheat Sheet (PDF)', type: 'Downloadable PDF', link: '/student/content' },
  { name: 'ES6 Arrow Functions Practice Lab', type: 'Interactive Sandbox', link: '/student/practice-lab/it' }
]

// Methods
const saveGoal = () => {
  learningGoal.value.title = editGoalForm.value.title
  learningGoal.value.targetCompletion = editGoalForm.value.targetCompletion
  learningGoal.value.weeklyStudyTime = editGoalForm.value.weeklyHours
  isEditGoalOpen.value = false
  
  toastMessage.value = 'Learning Goal and Roadmap updated successfully!'
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 3500)
}
</script>

<template>
  <StudentLayout
    title="Recommended Roadmap"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Recommended Roadmap' }
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

      <!-- 1. PAGE HEADER (Title with Book Icon, Subtitle & Search Bar) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
              <span>Recommended Roadmap</span>
              <span class="text-xl">📖</span>
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            AI-powered roadmap to help you achieve your learning goals faster.
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

      <!-- 2. TOP SECTION (Learning Goal Card 8 cols | Why this roadmap? Card 4 cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- LEFT 8 COLS: YOUR LEARNING GOAL & DONUT PROGRESS -->
        <div class="lg:col-span-8 bg-[#0e1424] border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl flex flex-col justify-between space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            
            <!-- Left Info -->
            <div class="space-y-3">
              <div class="flex items-center gap-2">
                <span class="text-sm">🎯</span>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Your Learning Goal</span>
              </div>

              <div>
                <h2 class="text-xl sm:text-2xl font-black text-white leading-tight">
                  {{ learningGoal.title }}
                </h2>
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-300 mt-2">
                  <span>Target Completion: <strong class="text-white">{{ learningGoal.targetCompletion }}</strong></span>
                  <span>•</span>
                  <span>Weekly Study Time: <strong class="text-white">{{ learningGoal.weeklyStudyTime }}</strong></span>
                </div>
              </div>

              <button
                @click="isEditGoalOpen = true"
                type="button"
                class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-purple-300 hover:text-white border border-slate-800 text-xs font-bold flex items-center gap-1.5 transition-all cursor-pointer shadow-sm"
              >
                <span>✏️</span>
                <span>Edit Goal</span>
              </button>
            </div>

            <!-- Middle Circular Donut Progress Ring -->
            <div class="flex items-center gap-6 shrink-0 self-center sm:self-auto">
              <div class="relative w-28 h-28 flex items-center justify-center">
                <!-- SVG Donut Chart -->
                <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 100 100">
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    stroke="#1e293b"
                    stroke-width="8"
                    fill="transparent"
                  />
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    stroke="url(#purpleGradient)"
                    stroke-width="8"
                    stroke-dasharray="251.2"
                    :stroke-dashoffset="251.2 - (251.2 * learningGoal.overallProgress) / 100"
                    stroke-linecap="round"
                    fill="transparent"
                    class="transition-all duration-1000 ease-out"
                  />
                  <defs>
                    <linearGradient id="purpleGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#8b5cf6" />
                      <stop offset="100%" stop-color="#3b82f6" />
                    </linearGradient>
                  </defs>
                </svg>

                <!-- Center Text -->
                <div class="absolute flex flex-col items-center justify-center text-center">
                  <span class="text-xl font-black text-white">{{ learningGoal.overallProgress }}%</span>
                  <span class="text-[9px] text-slate-400 uppercase font-semibold">Overall Progress</span>
                </div>
              </div>
            </div>

          </div>

          <!-- Bottom Stats Breakdown -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-800/80 text-xs">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-500/50"></span>
              <div>
                <p class="text-slate-400 text-[11px]">Completed</p>
                <p class="text-sm font-bold text-white">{{ learningGoal.completedCourses }} courses</p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-blue-400 shadow-sm shadow-blue-500/50"></span>
              <div>
                <p class="text-slate-400 text-[11px]">In Progress</p>
                <p class="text-sm font-bold text-white">{{ learningGoal.inProgressCourses }} courses</p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-400 shadow-sm shadow-amber-500/50"></span>
              <div>
                <p class="text-slate-400 text-[11px]">Upcoming</p>
                <p class="text-sm font-bold text-white">{{ learningGoal.upcomingCourses }} courses</p>
              </div>
            </div>

            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span>
              <div>
                <p class="text-slate-400 text-[11px]">Total Courses</p>
                <p class="text-sm font-bold text-white">{{ learningGoal.totalCourses }} courses</p>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT 4 COLS: WHY THIS ROADMAP? -->
        <div class="lg:col-span-4 bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-2xl flex flex-col justify-between relative overflow-hidden">
          
          <div class="space-y-3 z-10">
            <h3 class="text-sm font-bold text-white">Why this roadmap?</h3>
            <p class="text-xs text-slate-400">This roadmap is personalized based on:</p>

            <ul class="space-y-2 text-xs text-slate-300">
              <li class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                <span>Your current progress</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                <span>Quiz performance</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                <span>Learning time & consistency</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                <span>Industry best practices</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                <span>Your learning goal</span>
              </li>
            </ul>
          </div>

          <!-- 3D AI Robot Graphic on the right -->
          <div class="absolute right-3 bottom-3 opacity-90 pointer-events-none hidden sm:block">
            <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-purple-600/30 to-indigo-600/30 border border-purple-500/40 p-1 flex items-center justify-center text-4xl shadow-xl">
              🤖
            </div>
          </div>

        </div>

      </div>

      <!-- 3. YOUR RECOMMENDED ROADMAP (6-STAGE STEPPER TIMELINE) -->
      <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <h3 class="text-sm font-bold text-white uppercase tracking-wider">
            Your Recommended Roadmap
          </h3>

          <!-- View Switcher -->
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400">View by:</span>
            <div class="flex items-center rounded-xl bg-slate-900 border border-slate-800 p-0.5">
              <button
                @click="viewMode = 'roadmap'"
                type="button"
                :class="[
                  viewMode === 'roadmap' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-slate-200',
                  'px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer'
                ]"
              >
                Roadmap
              </button>
              <button
                @click="viewMode = 'list'"
                type="button"
                :class="[
                  viewMode === 'list' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'text-slate-400 hover:text-slate-200',
                  'px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer'
                ]"
              >
                List
              </button>
            </div>
          </div>
        </div>

        <!-- 6 Stages Horizontal Flow with Cards (Matching Screenshot) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 pt-2">
          
          <div
            v-for="(stg, index) in stages"
            :key="stg.id"
            @click="selectedStageIndex = index"
            :class="[
              stg.status === 'COMPLETED'
                ? 'bg-emerald-950/30 border-emerald-500/40 hover:border-emerald-400'
                : stg.status === 'CURRENT'
                ? 'bg-purple-950/40 border-purple-500 ring-2 ring-purple-500/30 shadow-lg shadow-purple-950/60'
                : 'bg-slate-950/60 border-slate-800 opacity-70 hover:opacity-100',
              'relative p-4 rounded-2xl border flex flex-col justify-between space-y-3 cursor-pointer transition-all duration-200 hover:-translate-y-1'
            ]"
          >
            <!-- Stage Number Badge -->
            <div class="space-y-1">
              <span
                :class="[
                  stg.status === 'COMPLETED'
                    ? 'text-emerald-400'
                    : stg.status === 'CURRENT'
                    ? 'text-purple-300'
                    : 'text-slate-400',
                  'text-[10px] font-bold font-mono uppercase'
                ]"
              >
                Stage {{ stg.stageNumber }}
              </span>

              <h4 class="text-xs font-bold text-white leading-tight line-clamp-1">
                {{ stg.title }}
              </h4>

              <p class="text-[10px] text-slate-400 line-clamp-1">
                {{ stg.coursesCount }}
              </p>
            </div>

            <!-- Status Icon indicator -->
            <div class="flex items-center justify-end pt-1">
              <div
                v-if="stg.status === 'COMPLETED'"
                class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold border border-emerald-500/40"
              >
                ✓
              </div>
              <div
                v-else-if="stg.status === 'CURRENT'"
                class="px-2 py-0.5 rounded-full bg-purple-600/30 text-purple-300 flex items-center justify-center text-[10px] font-bold border border-purple-500/40 font-mono"
              >
                {{ stg.progress }}%
              </div>
              <div
                v-else
                class="w-6 h-6 rounded-full bg-slate-900 text-slate-500 flex items-center justify-center text-xs border border-slate-800"
              >
                🔒
              </div>
            </div>

            <!-- Active Stage Arrow Down Indicator -->
            <div
              v-if="index === selectedStageIndex"
              class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-3 h-3 bg-[#0e1424] border-r border-b border-purple-500 transform rotate-45"
            ></div>
          </div>

        </div>

      </div>

      <!-- 4. MAIN 2-COLUMN SECTION: CURRENT STAGE COURSES (8 cols) vs NEXT UP & SKILLS (4 cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8 COLS: CURRENT STAGE COURSES & AI RECOMMENDATION BANNER -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- CURRENT STAGE COURSES CARD -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-5">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800/80 pb-4">
              <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">
                  Current Stage Courses
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                  Complete these courses to unlock the next stage
                </p>
              </div>

              <!-- Stage Progress Indicator -->
              <div class="flex items-center gap-3">
                <span class="text-xs text-slate-300 font-bold">Stage {{ activeStage.stageNumber }} Progress</span>
                <div class="w-28 h-2 rounded-full bg-slate-900 overflow-hidden">
                  <div
                    class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full transition-all"
                    :style="{ width: `${activeStage.progress}%` }"
                  ></div>
                </div>
                <span class="text-xs font-bold text-purple-400 font-mono">{{ activeStage.progress }}%</span>
              </div>
            </div>

            <!-- Courses List in Active Stage -->
            <div class="space-y-4">
              <div
                v-for="crs in activeStage.courses"
                :key="crs.id"
                class="p-4 sm:p-5 rounded-2xl bg-slate-950 border border-slate-800 hover:border-purple-500/40 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all group"
              >
                <!-- Course Left: Icon & Info -->
                <div class="flex items-start gap-4 min-w-0">
                  <div :class="[crs.iconBg, 'w-12 h-12 rounded-2xl bg-gradient-to-br text-slate-950 font-black text-sm flex items-center justify-center font-mono shadow-md shrink-0']">
                    {{ crs.icon }}
                  </div>

                  <div class="space-y-1 min-w-0">
                    <h4 class="text-sm font-bold text-white group-hover:text-purple-300 transition-colors truncate">
                      {{ crs.title }}
                    </h4>
                    <p class="text-xs text-slate-400 line-clamp-1 leading-relaxed">
                      {{ crs.description }}
                    </p>
                    <div class="flex items-center gap-2 text-[11px] text-slate-400 pt-0.5">
                      <span class="text-emerald-400 font-bold">● {{ crs.level }}</span>
                      <span>•</span>
                      <span>{{ crs.lessonsCount }} Lessons</span>
                      <span>•</span>
                      <span>{{ crs.duration }}</span>
                    </div>
                  </div>
                </div>

                <!-- Course Right: Progress & Action -->
                <div class="flex items-center gap-4 shrink-0 justify-between sm:justify-end border-t sm:border-t-0 border-slate-800/80 pt-3 sm:pt-0">
                  <div class="w-24 sm:w-28 space-y-1">
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                      <span>Progress</span>
                      <span class="font-bold text-white">{{ crs.progress }}%</span>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-slate-900 overflow-hidden">
                      <div
                        class="h-full bg-gradient-to-r from-purple-500 to-indigo-400 rounded-full"
                        :style="{ width: `${crs.progress}%` }"
                      ></div>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <Link
                    v-if="crs.status === 'completed'"
                    :href="crs.href"
                    class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white border border-slate-800 text-xs font-bold flex items-center gap-1.5 transition-all shadow-xs"
                  >
                    <span>👁</span>
                    <span>Review</span>
                    <span class="text-[10px]">▼</span>
                  </Link>

                  <Link
                    v-else-if="crs.status === 'in_progress'"
                    :href="crs.href"
                    class="px-5 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30 flex items-center gap-1.5 transition-all"
                  >
                    <span>▶</span>
                    <span>Continue</span>
                  </Link>

                  <Link
                    v-else-if="crs.status === 'not_started'"
                    :href="crs.href"
                    class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-xs font-bold flex items-center gap-1.5 transition-all"
                  >
                    <span>▶</span>
                    <span>Start Course</span>
                  </Link>

                  <button
                    v-else
                    disabled
                    type="button"
                    class="px-4 py-2 rounded-xl bg-slate-900/60 border border-slate-800 text-slate-600 text-xs font-bold cursor-not-allowed flex items-center gap-1.5"
                  >
                    <span>🔒</span>
                    <span>Locked</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Accordion: Show Recommended Resources for This Stage -->
            <div class="pt-2 border-t border-slate-800/80">
              <button
                @click="isResourcesOpen = !isResourcesOpen"
                type="button"
                class="w-full text-center py-2 text-xs text-slate-400 hover:text-purple-300 font-semibold flex items-center justify-center gap-1.5 cursor-pointer transition-colors"
              >
                <span>{{ isResourcesOpen ? 'Hide' : 'Show' }} Recommended Resources for This Stage</span>
                <span :class="{ 'rotate-180': isResourcesOpen }" class="transition-transform">▼</span>
              </button>

              <div v-if="isResourcesOpen" class="mt-3 space-y-2">
                <a
                  v-for="res in stageResources"
                  :key="res.name"
                  :href="res.link"
                  class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-purple-500/40 text-xs flex items-center justify-between text-slate-300 hover:text-white transition-all group"
                >
                  <div class="flex items-center gap-2.5">
                    <span class="text-purple-400">📄</span>
                    <span>{{ res.name }}</span>
                  </div>
                  <span class="text-[10px] text-purple-400 font-mono group-hover:underline">{{ res.type }} →</span>
                </a>
              </div>
            </div>

          </div>

          <!-- AI RECOMMENDATION BANNER (Bottom Left) -->
          <div class="p-5 rounded-3xl bg-gradient-to-r from-[#170e3b] via-[#101935] to-[#120e2e] border border-purple-800/50 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-600/30 border border-purple-500/50 text-purple-300 flex items-center justify-center text-lg shrink-0">
                ✦
              </div>
              <div class="space-y-1">
                <h4 class="text-xs font-bold text-white uppercase tracking-wider">AI Recommendation</h4>
                <p class="text-xs text-slate-300 leading-relaxed max-w-xl">
                  You're making great progress! Focus more on <strong class="text-purple-300">JavaScript Functions & Scope</strong>. Strengthening this skill will help you in advanced topics.
                </p>
              </div>
            </div>

            <Link
              href="/student/ai-tutor?course=1&prompt=Why+should+I+study+JavaScript+Functions+next"
              class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-2 shrink-0 transition-all"
            >
              <span>🤖 Ask AI for Advice</span>
            </Link>
          </div>

        </div>

        <!-- RIGHT 4 COLS: NEXT UP, SKILLS YOU WILL GAIN & ROADMAP TIPS -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- NEXT UP CARD (Matching Screenshot) -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider border-b border-slate-800/80 pb-3">
              Next Up
            </h3>

            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 text-white font-black text-xs flex items-center justify-center font-mono shadow-md shrink-0">
                  JS
                </div>
                <div>
                  <h4 class="text-xs font-bold text-white">JavaScript Functions & Scope</h4>
                  <p class="text-[10px] text-slate-400">Continue where you left off</p>
                </div>
              </div>

              <Link
                href="/student/my-courses/current"
                class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/30 flex items-center justify-center gap-1.5 transition-all"
              >
                <span>▶ Continue</span>
              </Link>
            </div>
          </div>

          <!-- SKILLS YOU WILL GAIN CARD (Matching Screenshot) -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider border-b border-slate-800/80 pb-3">
              Skills You Will Gain
            </h3>

            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="skill in allSkills"
                :key="skill"
                class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 text-xs font-medium"
              >
                {{ skill }}
              </span>
            </div>

            <button
              type="button"
              class="text-xs text-purple-400 hover:text-purple-300 font-bold pt-1 block cursor-pointer"
            >
              View All Skills →
            </button>
          </div>

          <!-- ROADMAP TIPS CARD (Matching Screenshot) -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider border-b border-slate-800/80 pb-3">
              Roadmap Tips
            </h3>

            <div class="space-y-3 text-xs">
              
              <!-- Tip 1: Stay consistent -->
              <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-sm shrink-0">
                  ⏱️
                </div>
                <div>
                  <p class="font-bold text-white">Stay consistent</p>
                  <p class="text-[11px] text-slate-400">Study a little every day</p>
                </div>
              </div>

              <!-- Tip 2: Practice regularly -->
              <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center text-sm shrink-0">
                  💼
                </div>
                <div>
                  <p class="font-bold text-white">Practice regularly</p>
                  <p class="text-[11px] text-slate-400">Complete exercises and quizzes</p>
                </div>
              </div>

              <!-- Tip 3: Ask for help -->
              <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center text-sm shrink-0">
                  🤖
                </div>
                <div>
                  <p class="font-bold text-white">Ask for help</p>
                  <p class="text-[11px] text-slate-400">Use AI Assistant when you're stuck</p>
                </div>
              </div>

              <!-- Tip 4: Track your progress -->
              <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center text-sm shrink-0">
                  📈
                </div>
                <div>
                  <p class="font-bold text-white">Track your progress</p>
                  <p class="text-[11px] text-slate-400">Review and improve continuously</p>
                </div>
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
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/40 text-purple-300 flex items-center justify-center text-lg">
            🎯
          </div>
          <div>
            <h3 class="text-base font-bold text-white">Edit Learning Goal</h3>
            <p class="text-xs text-slate-400">Customize your personalized study target</p>
          </div>
        </div>

        <div class="space-y-4 text-xs">
          <!-- Target Track -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300 uppercase text-[11px]">Career Track</label>
            <select
              v-model="editGoalForm.title"
              class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white focus:outline-none focus:border-purple-500"
            >
              <option value="Become a Full Stack Web Developer">Full Stack Web Developer (SPI IT Track)</option>
              <option value="Become a Frontend React Specialist">Frontend React Specialist</option>
              <option value="Become a Python & AI Developer">Python & AI Data Specialist</option>
              <option value="Become a UI/UX Product Designer">UI/UX Product Designer</option>
            </select>
          </div>

          <!-- Target Timeline -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300 uppercase text-[11px]">Target Timeline</label>
            <select
              v-model="editGoalForm.targetCompletion"
              class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white focus:outline-none focus:border-purple-500"
            >
              <option value="3 Months">3 Months (Fast Track)</option>
              <option value="6 Months">6 Months (Recommended)</option>
              <option value="12 Months">12 Months (Comprehensive)</option>
            </select>
          </div>

          <!-- Weekly Hours -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-300 uppercase text-[11px]">Weekly Study Commitment</label>
            <select
              v-model="editGoalForm.weeklyHours"
              class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white focus:outline-none focus:border-purple-500"
            >
              <option value="5-10 hours">5-10 hours / week</option>
              <option value="10-15 hours">10-15 hours / week (Standard)</option>
              <option value="15-25 hours">15-25 hours / week (Intensive)</option>
            </select>
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
