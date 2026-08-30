<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface JourneyStep {
  id: number
  title: string
  category: string
  status: 'COMPLETED' | 'CURRENT' | 'AI_RECOMMENDED' | 'UPCOMING' | 'LOCKED'
  progress: number
  score?: number
  duration: string
  desc: string
  skills: string[]
  prerequisites: string
  icon: string
}

interface WeakTopic {
  id: number
  topic: string
  course: string
  score: number
  severity: 'high' | 'medium' | 'low'
  reason: string
  recommendedAction: string
  aiPrompt: string
}

interface DailyTask {
  id: number
  title: string
  duration: string
  category: string
  completed: boolean
  link: string
}

const page = usePage()
const studentName = computed(() => (page.props.auth as any)?.user?.name || 'Sok Pisey')
const studentId = computed(() => (page.props.auth as any)?.user?.student_id || 'STU2024001')

// Re-analysis state
const isReanalyzing = ref(false)
const showToast = ref(false)
const toastMessage = ref('')

// Selected Step for Modal
const selectedStep = ref<JourneyStep | null>(null)
const isStepModalOpen = ref(false)

// Practice Drill Modal
const isPracticeModalOpen = ref(false)
const currentPracticeIndex = ref(0)
const practiceSubmitted = ref(false)
const practiceSelectedAnswer = ref<number | null>(null)

// Learning Goal Summary
const learningGoal = ref({
  track: 'Full-Stack Web Developer (SPI IT Track)',
  targetDate: 'August 15, 2026',
  overallProgress: 58,
  pacing: 'Optimal (+15% ahead of schedule)',
  completedMilestones: 2,
  totalMilestones: 6,
  studyTimeWeek: '12h 30m',
  studyGoalWeek: '15h 00m'
})

// Visual Learning Journey Steps
const journeySteps = ref<JourneyStep[]>([
  {
    id: 1,
    title: 'HTML5 Fundamentals & Semantic Web',
    category: 'Frontend Foundations',
    status: 'COMPLETED',
    progress: 100,
    score: 95,
    duration: '4h 15m',
    desc: 'Mastered semantic HTML5 tags, accessibility standards, audio/video embeds, and form validations.',
    skills: ['Semantic Markup', 'Forms & Inputs', 'SEO Meta Tags', 'Accessibility (a11y)'],
    prerequisites: 'None',
    icon: '🌐'
  },
  {
    id: 2,
    title: 'CSS3 Layouts, Flexbox & CSS Grid',
    category: 'Styling & Design',
    status: 'COMPLETED',
    progress: 100,
    score: 88,
    duration: '5h 30m',
    desc: 'Built responsive mobile-first UI layouts with Flexbox, CSS Grid, media queries, and animations.',
    skills: ['Flexbox Alignment', 'Grid Areas', 'Responsive Design', 'CSS Transitions'],
    prerequisites: 'HTML5 Fundamentals',
    icon: '🎨'
  },
  {
    id: 3,
    title: 'JavaScript Core & Functions',
    category: 'Logic & Scripting',
    status: 'CURRENT',
    progress: 53,
    score: 74,
    duration: '6h 45m',
    desc: 'Currently learning function declarations, arrow functions, parameter defaults, and scope management.',
    skills: ['Functions & Scope', 'Arrow Syntax', 'Closures', 'Error Handling'],
    prerequisites: 'CSS3 Layouts',
    icon: '⚡'
  },
  {
    id: 4,
    title: 'DOM Manipulation & Web APIs',
    category: 'Interactive Web',
    status: 'AI_RECOMMENDED',
    progress: 0,
    duration: '4h 30m',
    desc: 'AI Recommended next: Connect JavaScript logic with browser DOM, handle event listeners, and fetch APIs.',
    skills: ['Event Listeners', 'DOM Traversal', 'Fetch API', 'Local Storage'],
    prerequisites: 'JavaScript Core & Functions (>= 75%)',
    icon: '⭐'
  },
  {
    id: 5,
    title: 'Asynchronous JavaScript & Promises',
    category: 'Advanced Logic',
    status: 'UPCOMING',
    progress: 0,
    duration: '5h 00m',
    desc: 'Master Async/Await, Promises, API error handling, and asynchronous data pipelines.',
    skills: ['Promises', 'Async/Await', 'REST APIs', 'JSON Parsing'],
    prerequisites: 'DOM Manipulation',
    icon: '⏳'
  },
  {
    id: 6,
    title: 'React.js & Modern Component Architecture',
    category: 'Frontend Frameworks',
    status: 'LOCKED',
    progress: 0,
    duration: '10h 00m',
    desc: 'Capstone Milestone: Component state, hooks, props, routing, and building interactive single-page apps.',
    skills: ['React Hooks', 'State Management', 'TailwindCSS', 'Component Design'],
    prerequisites: 'Asynchronous JavaScript',
    icon: '🔒'
  }
])

// Skill Progress List
const skillProgressList = ref([
  { name: 'HTML5 & Semantic Web', level: 90, status: 'Mastered', color: 'from-emerald-500 to-teal-400' },
  { name: 'CSS3 & Responsive Design', level: 85, status: 'Proficient', color: 'from-blue-500 to-indigo-400' },
  { name: 'JavaScript Core & ES6+', level: 58, status: 'In Progress', color: 'from-purple-500 to-pink-400' },
  { name: 'Problem Solving & Logic', level: 42, status: 'Needs Attention', color: 'from-amber-500 to-orange-400', isWarning: true },
  { name: 'Database & SQL Queries', level: 35, status: 'Developing', color: 'from-cyan-500 to-blue-400' }
])

// Weak Topics
const weakTopics = ref<WeakTopic[]>([
  {
    id: 1,
    topic: 'JavaScript Function Parameters',
    course: 'Web Development Fundamentals',
    score: 45,
    severity: 'high',
    reason: 'Scored below 50% on parameter passing, default values, and rest parameters in last quiz.',
    recommendedAction: 'Review Lesson 3.2 and practice 5 interactive drill questions.',
    aiPrompt: 'Explain JavaScript function parameters and default arguments with code examples.'
  },
  {
    id: 2,
    topic: 'SQL Table Joins (INNER vs LEFT)',
    course: 'Database Systems & SQL',
    score: 50,
    severity: 'medium',
    reason: 'Missed 2 multi-table join aggregation questions during week 3 practice.',
    recommendedAction: 'Complete the visual join diagram exercise in Practice Lab.',
    aiPrompt: 'Can you explain INNER JOIN vs LEFT JOIN with a simple visual query example?'
  }
])

// Today's Study Plan Tasks
const dailyTasks = ref<DailyTask[]>([
  {
    id: 1,
    title: 'Continue JavaScript Functions Lesson',
    duration: '20 min',
    category: 'Core Lesson',
    completed: true,
    link: '/student/my-courses/current'
  },
  {
    id: 2,
    title: 'Review HTML5 Semantic Form Notes',
    duration: '10 min',
    category: 'Review',
    completed: true,
    link: '/student/content'
  },
  {
    id: 3,
    title: 'Review Weak Topic: Function Parameters',
    duration: '15 min',
    category: 'Targeted Review',
    completed: false,
    link: '/student/ai-tutor?course=1&prompt=Explain+Function+Parameters'
  },
  {
    id: 4,
    title: 'Complete 5-Question Practice Drill',
    duration: '15 min',
    category: 'Practice Quiz',
    completed: false,
    link: '/student/quizzes/practice'
  }
])

const completedTasksCount = computed(() => dailyTasks.value.filter(t => t.completed).length)
const totalTasksCount = computed(() => dailyTasks.value.length)
const dailyProgressPercent = computed(() => Math.round((completedTasksCount.value / totalTasksCount.value) * 100))

// Interactive Practice Drill Question
const practiceQuestion = {
  question: 'What is the default value of a function parameter in JavaScript if no argument is passed?',
  options: [
    'undefined',
    'null',
    '0',
    'SyntaxError'
  ],
  correctIndex: 0,
  explanation: 'In JavaScript, function parameters default to undefined if no value or argument is passed when calling the function.'
}

// Methods
const toggleTask = (task: DailyTask) => {
  task.completed = !task.completed
}

const reanalyzeWithAi = () => {
  isReanalyzing.value = true
  setTimeout(() => {
    isReanalyzing.value = false
    toastMessage.value = 'AI Learning Path updated with latest quiz & lesson progress!'
    showToast.value = true
    setTimeout(() => {
      showToast.value = false
    }, 3500)
  }, 1200)
}

const openStepModal = (step: JourneyStep) => {
  selectedStep.value = step
  isStepModalOpen.value = true
}

const startPracticeDrill = () => {
  practiceSubmitted.value = false
  practiceSelectedAnswer.value = null
  isPracticeModalOpen.value = true
}

const submitPracticeAnswer = (idx: number) => {
  practiceSelectedAnswer.value = idx
  practiceSubmitted.value = true
}
</script>

<template>
  <StudentLayout
    title="Personalized Learning Path"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'AI Learning', href: '/student/ai-path/recommended' },
      { label: 'Personalized Learning Path' }
    ]"
  >
    <div class="space-y-6 pb-16">
      
      <!-- TOAST NOTIFICATION -->
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
          <span class="text-xl text-purple-400">🤖</span>
          <p class="text-xs text-slate-200">{{ toastMessage }}</p>
        </div>
      </transition>

      <!-- 1. PAGE HEADER (Title, Subtitle, AI Badge & Re-analyze CTA) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
              <span>Personalized Learning Path</span>
            </h1>
            <span class="px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-xs font-bold font-mono">
              🤖 AI
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Your AI-powered roadmap for achieving your learning goals.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="reanalyzeWithAi"
            :disabled="isReanalyzing"
            type="button"
            class="px-4 py-2 rounded-xl bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 border border-purple-500/30 text-xs font-bold flex items-center gap-2 transition-all cursor-pointer shadow-sm disabled:opacity-50"
          >
            <span :class="{ 'animate-spin': isReanalyzing }">🔄</span>
            <span>{{ isReanalyzing ? 'Analyzing with AI...' : 'Re-analyze with AI' }}</span>
          </button>
        </div>
      </div>

      <!-- 2. AI LEARNING RECOMMENDATION BANNER -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#170e3b] via-[#101935] to-[#120e2e] border border-purple-800/50 p-6 sm:p-8 shadow-2xl">
        <div class="absolute top-0 right-1/4 w-64 h-64 bg-purple-600/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="flex items-start gap-4">
            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-600 to-pink-500 p-0.5 shadow-xl shadow-purple-600/30 shrink-0">
              <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center text-3xl shadow-inner">
                🤖
              </div>
            </div>

            <div class="space-y-1.5">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-purple-300">
                  AI Learning Recommendation
                </span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                  Action Required
                </span>
              </div>
              <h2 class="text-base sm:text-xl font-bold text-white leading-snug">
                Based on your recent progress, we recommend focusing on <span class="text-purple-300 underline underline-offset-4">JavaScript Functions</span>.
              </h2>
              <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                You're progressing well in Web Development Fundamentals (53%). Mastering function parameters will prepare you for DOM Manipulation next.
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 shrink-0 self-start sm:self-auto">
            <Link
              href="/student/my-courses/current"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center gap-2 transition-all"
            >
              <span>🚀 Continue Learning</span>
            </Link>

            <Link
              href="/student/ai-tutor?course=1&prompt=Explain+JavaScript+Functions"
              class="px-4 py-2.5 rounded-xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 font-bold text-xs border border-slate-800 flex items-center gap-1.5 transition-all"
            >
              <span>🤖 Ask AI</span>
            </Link>
          </div>
        </div>
      </div>

      <!-- 3. LEARNING GOAL & PACING METRICS STRIP -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Target Track -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-2xl p-4 space-y-1 shadow-md">
          <p class="text-[11px] text-slate-400 font-bold uppercase">Target Learning Goal</p>
          <p class="text-xs font-bold text-white truncate">{{ learningGoal.track }}</p>
          <p class="text-[10px] text-purple-400 font-mono">Target: {{ learningGoal.targetDate }}</p>
        </div>

        <!-- Overall Roadmap Progress -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-2xl p-4 space-y-2 shadow-md">
          <div class="flex items-center justify-between text-[11px]">
            <span class="text-slate-400 font-bold uppercase">Overall Path Progress</span>
            <span class="text-emerald-400 font-black">{{ learningGoal.overallProgress }}%</span>
          </div>
          <div class="w-full h-1.5 rounded-full bg-slate-900 overflow-hidden">
            <div
              class="h-full bg-gradient-to-r from-purple-500 via-indigo-500 to-emerald-400 rounded-full"
              :style="{ width: `${learningGoal.overallProgress}%` }"
            ></div>
          </div>
        </div>

        <!-- Milestones Completed -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-2xl p-4 space-y-1 shadow-md">
          <p class="text-[11px] text-slate-400 font-bold uppercase">Milestones Completed</p>
          <div class="flex items-center gap-2">
            <span class="text-base font-black text-white">{{ learningGoal.completedMilestones }} / {{ learningGoal.totalMilestones }}</span>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold">On Schedule</span>
          </div>
          <p class="text-[10px] text-slate-400">{{ learningGoal.pacing }}</p>
        </div>

        <!-- Weekly Study Time -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-2xl p-4 space-y-1 shadow-md">
          <p class="text-[11px] text-slate-400 font-bold uppercase">Study Time This Week</p>
          <div class="flex items-center gap-2">
            <span class="text-base font-black text-white">{{ learningGoal.studyTimeWeek }}</span>
            <span class="text-[10px] text-slate-400">/ {{ learningGoal.studyGoalWeek }}</span>
          </div>
          <p class="text-[10px] text-purple-400 font-mono">Streak: 6 days active 🔥</p>
        </div>

      </div>

      <!-- 4. YOUR LEARNING JOURNEY (INTERACTIVE VISUAL ROADMAP) -->
      <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800/80 pb-4">
          <div>
            <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
              <span>🗺️</span>
              <span>Your Learning Journey</span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Click any milestone step to view prerequisite requirements, syllabus details, or resume learning.
            </p>
          </div>

          <div class="flex items-center gap-3 text-xs">
            <span class="flex items-center gap-1 text-emerald-400"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Completed</span>
            <span class="flex items-center gap-1 text-purple-400"><span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span> Current</span>
            <span class="flex items-center gap-1 text-amber-400"><span class="w-2 h-2 rounded-full bg-amber-400"></span> AI Recommended</span>
          </div>
        </div>

        <!-- Visual Step Nodes Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="step in journeySteps"
            :key="step.id"
            @click="openStepModal(step)"
            :class="[
              step.status === 'COMPLETED'
                ? 'border-emerald-500/40 bg-slate-950/80 hover:border-emerald-500'
                : step.status === 'CURRENT'
                ? 'border-purple-500 bg-purple-950/20 shadow-lg shadow-purple-950/40 hover:border-purple-400'
                : step.status === 'AI_RECOMMENDED'
                ? 'border-amber-500/40 bg-slate-950/80 hover:border-amber-500'
                : 'border-slate-800/80 bg-slate-950/40 opacity-70 hover:opacity-100',
              'p-5 rounded-2xl border transition-all duration-200 hover:-translate-y-0.5 cursor-pointer flex flex-col justify-between space-y-4 group'
            ]"
          >
            <!-- Step Card Header -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-mono font-bold text-slate-500">STEP {{ step.id }}</span>
                <span
                  :class="[
                    step.status === 'COMPLETED'
                      ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
                      : step.status === 'CURRENT'
                      ? 'bg-purple-500/20 text-purple-300 border-purple-500/30'
                      : step.status === 'AI_RECOMMENDED'
                      ? 'bg-amber-500/20 text-amber-300 border-amber-500/30'
                      : 'bg-slate-800 text-slate-400 border-slate-700',
                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold border'
                  ]"
                >
                  {{ step.status === 'COMPLETED' ? '✓ Completed' : (step.status === 'CURRENT' ? '▶ Current' : (step.status === 'AI_RECOMMENDED' ? '⭐ AI Recommended' : '🔒 Locked')) }}
                </span>
              </div>

              <div class="flex items-start gap-3">
                <span class="text-2xl shrink-0">{{ step.icon }}</span>
                <div>
                  <h4 class="text-xs sm:text-sm font-bold text-white group-hover:text-purple-300 transition-colors">
                    {{ step.title }}
                  </h4>
                  <p class="text-[11px] text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                    {{ step.desc }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Progress bar & Footer -->
            <div class="space-y-2 pt-2 border-t border-slate-800/80">
              <div class="flex items-center justify-between text-[11px] text-slate-400">
                <span>Progress: <strong class="text-white">{{ step.progress }}%</strong></span>
                <span>{{ step.duration }}</span>
              </div>
              <div class="w-full h-1.5 rounded-full bg-slate-900 overflow-hidden">
                <div
                  :class="[
                    step.status === 'COMPLETED'
                      ? 'bg-emerald-400'
                      : step.status === 'CURRENT'
                      ? 'bg-purple-500'
                      : 'bg-slate-700',
                    'h-full rounded-full transition-all'
                  ]"
                  :style="{ width: `${step.progress}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- 5. 2-COLUMN FOCUS & NEXT STEP GRID (Current Focus vs AI Next Step) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- CURRENT LEARNING FOCUS CARD -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4 flex flex-col justify-between">
          <div class="space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span>🎯</span>
                <span>Current Learning Focus</span>
              </h3>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                In Progress
              </span>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-300 font-bold text-xs flex items-center justify-center font-mono">
                  JS
                </div>
                <div>
                  <h4 class="text-sm font-bold text-white">Web Development Fundamentals</h4>
                  <p class="text-xs text-purple-300 font-medium">Chapter 3: JavaScript Functions</p>
                </div>
              </div>

              <div class="space-y-1 pt-1">
                <div class="flex items-center justify-between text-xs">
                  <span class="text-slate-400">Lesson 3.2: Declarations & Parameters</span>
                  <span class="text-emerald-400 font-bold">53% Completed</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-900 overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-purple-500 to-emerald-400 rounded-full" style="width: 53%;"></div>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-400 pt-1">
                <div>Last Active: <strong class="text-slate-200">Today, 09:15 AM</strong></div>
                <div>Est. Remaining: <strong class="text-slate-200">45 mins</strong></div>
              </div>
            </div>
          </div>

          <Link
            href="/student/my-courses/current"
            class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/30 flex items-center justify-center gap-2 transition-all"
          >
            <span>▶ Continue Current Lesson</span>
          </Link>
        </div>

        <!-- AI RECOMMENDED NEXT STEP CARD -->
        <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4 flex flex-col justify-between">
          <div class="space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span>⚡</span>
                <span>AI Recommended Next Step</span>
              </h3>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                High Priority
              </span>
            </div>

            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
              <div>
                <h4 class="text-sm font-bold text-white">Practice Drill: Function Parameters & Return Values</h4>
                <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                  Targeted 5-question drill to solidify parameter passing, default values, and avoid common scope pitfalls.
                </p>
              </div>

              <div class="p-3 rounded-xl bg-purple-950/40 border border-purple-800/40 space-y-1 text-xs">
                <div class="text-purple-300 font-bold flex items-center gap-1.5">
                  <span>💡 Why Recommended:</span>
                </div>
                <p class="text-[11px] text-slate-300">
                  You scored 62% in Chapter 2 Quiz on parameter handling. Lifting this to 85%+ unlocks DOM Manipulation.
                </p>
              </div>

              <div class="flex items-center justify-between text-[11px] text-slate-400">
                <span>Est. Duration: <strong class="text-white">20 mins</strong></span>
                <span>Expected Score Gain: <strong class="text-emerald-400">+25%</strong></span>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <button
              @click="startPracticeDrill"
              type="button"
              class="flex-1 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-md shadow-emerald-600/30 flex items-center justify-center gap-1.5 transition-all cursor-pointer"
            >
              <span>⚡ Start Practice Drill</span>
            </button>

            <Link
              href="/student/ai-tutor?course=1&prompt=Explain+Function+Parameters"
              class="px-4 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-purple-300 font-bold text-xs border border-slate-800 flex items-center justify-center gap-1 transition-all"
            >
              <span>🤖 Explain</span>
            </Link>
          </div>
        </div>

      </div>

      <!-- 6. 2-COLUMN SECTION: SKILL PROGRESS (Left) vs WEAK TOPICS & STUDY PLAN (Right) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 6 COLS: SKILL PROGRESS & MASTERY -->
        <div class="lg:col-span-6 bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-5">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
              <span>📊</span>
              <span>Skill Progress & Mastery</span>
            </h3>
            <span class="text-xs text-slate-400">5 Tracks Active</span>
          </div>

          <div class="space-y-4">
            <div
              v-for="skill in skillProgressList"
              :key="skill.name"
              class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800/90 space-y-2"
            >
              <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-white flex items-center gap-1.5">
                  <span v-if="skill.isWarning" class="text-amber-400">⚠️</span>
                  <span>{{ skill.name }}</span>
                </span>
                <div class="flex items-center gap-2">
                  <span :class="skill.isWarning ? 'text-amber-400' : 'text-slate-400'" class="text-[10px] font-semibold">
                    {{ skill.status }}
                  </span>
                  <span class="font-mono font-bold text-white">{{ skill.level }}%</span>
                </div>
              </div>

              <div class="w-full h-2 rounded-full bg-slate-900 overflow-hidden">
                <div
                  :class="[skill.color, 'h-full bg-gradient-to-r rounded-full transition-all duration-500']"
                  :style="{ width: `${skill.level}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT 6 COLS: WEAK TOPICS & TODAY'S STUDY PLAN -->
        <div class="lg:col-span-6 space-y-6">
          
          <!-- WEAK TOPICS REVIEW -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span>⚠️</span>
                <span>Weak Topics Detected</span>
              </h3>
              <span class="text-[11px] text-amber-400 font-semibold font-mono">2 Topics Flagged</span>
            </div>

            <div class="space-y-3">
              <div
                v-for="wt in weakTopics"
                :key="wt.id"
                class="p-4 rounded-2xl bg-slate-950 border border-amber-500/30 space-y-2.5"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="text-xs font-bold text-white">{{ wt.topic }}</h4>
                    <p class="text-[10px] text-slate-400">{{ wt.course }}</p>
                  </div>
                  <div class="text-right">
                    <span class="text-xs font-black text-rose-400 font-mono">{{ wt.score }}%</span>
                    <p class="text-[9px] text-slate-500">Quiz Score</p>
                  </div>
                </div>

                <p class="text-[11px] text-slate-300 leading-relaxed bg-slate-900/60 p-2.5 rounded-xl border border-slate-800/80">
                  {{ wt.reason }}
                </p>

                <div class="flex items-center justify-between pt-1 text-xs">
                  <span class="text-[10px] text-purple-300">⚡ {{ wt.recommendedAction }}</span>
                  <Link
                    :href="`/student/ai-tutor?course=1&prompt=${encodeURIComponent(wt.aiPrompt)}`"
                    class="px-3 py-1 rounded-lg bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white border border-purple-500/30 text-[11px] font-bold transition-all"
                  >
                    Ask AI →
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <!-- TODAY'S DAILY STUDY PLAN -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
              <div>
                <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                  <span>📅</span>
                  <span>Today's AI Study Plan</span>
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  {{ completedTasksCount }} of {{ totalTasksCount }} tasks completed ({{ dailyProgressPercent }}%)
                </p>
              </div>
              <span class="text-xs font-bold text-purple-400 font-mono">{{ dailyProgressPercent }}%</span>
            </div>

            <div class="space-y-2">
              <div
                v-for="task in dailyTasks"
                :key="task.id"
                @click="toggleTask(task)"
                :class="[
                  task.completed ? 'bg-slate-950/40 border-slate-800/60 opacity-60' : 'bg-slate-950 border-slate-800',
                  'p-3 rounded-2xl border flex items-center justify-between transition-all cursor-pointer hover:border-purple-500/40 group'
                ]"
              >
                <div class="flex items-center gap-3">
                  <div
                    :class="[
                      task.completed ? 'bg-emerald-500 border-emerald-400 text-slate-950' : 'border-slate-700 bg-slate-900 text-transparent',
                      'w-5 h-5 rounded-lg border flex items-center justify-center text-xs font-black transition-all'
                    ]"
                  >
                    ✓
                  </div>
                  <div>
                    <p :class="task.completed ? 'line-through text-slate-400' : 'text-white'" class="text-xs font-semibold">
                      {{ task.title }}
                    </p>
                    <p class="text-[10px] text-slate-500">{{ task.category }} • {{ task.duration }}</p>
                  </div>
                </div>

                <Link
                  :href="task.link"
                  @click.stop
                  class="text-[11px] text-purple-400 hover:text-purple-300 font-bold px-2 py-1"
                >
                  Start →
                </Link>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- 1. MILESTONE STEP DETAIL MODAL -->
    <div
      v-if="isStepModalOpen && selectedStep"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isStepModalOpen = false"
    >
      <div
        class="relative w-full max-w-lg bg-[#0e1424] border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto custom-scrollbar"
        @click.stop
      >
        <button
          @click="isStepModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-white text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <span class="text-3xl">{{ selectedStep.icon }}</span>
          <div>
            <span class="text-[10px] font-bold font-mono text-purple-400 uppercase">STEP {{ selectedStep.id }} • {{ selectedStep.category }}</span>
            <h3 class="text-base font-bold text-white">{{ selectedStep.title }}</h3>
          </div>
        </div>

        <p class="text-xs text-slate-300 leading-relaxed bg-slate-950 p-4 rounded-2xl border border-slate-800">
          {{ selectedStep.desc }}
        </p>

        <!-- Skills Covered -->
        <div class="space-y-2">
          <p class="text-[11px] font-bold text-slate-400 uppercase">Skills Acquired</p>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="s in selectedStep.skills"
              :key="s"
              class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-purple-300 text-xs font-medium"
            >
              ✓ {{ s }}
            </span>
          </div>
        </div>

        <!-- Prerequisite Info -->
        <div class="grid grid-cols-2 gap-3 text-xs p-3.5 rounded-2xl bg-slate-950 border border-slate-800">
          <div>
            <p class="text-[10px] text-slate-500">Prerequisites</p>
            <p class="font-bold text-white">{{ selectedStep.prerequisites }}</p>
          </div>
          <div>
            <p class="text-[10px] text-slate-500">Estimated Duration</p>
            <p class="font-bold text-white">{{ selectedStep.duration }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="isStepModalOpen = false"
            type="button"
            class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold border border-slate-800 cursor-pointer"
          >
            Close
          </button>

          <Link
            v-if="selectedStep.status === 'COMPLETED' || selectedStep.status === 'CURRENT'"
            href="/student/my-courses/current"
            class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30"
          >
            {{ selectedStep.status === 'COMPLETED' ? 'Review Milestone' : 'Continue Learning' }}
          </Link>
          <Link
            v-else
            :href="`/student/ai-tutor?course=1&prompt=How+can+I+prepare+for+${encodeURIComponent(selectedStep.title)}`"
            class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30"
          >
            Ask AI How to Prepare
          </Link>
        </div>
      </div>
    </div>

    <!-- 2. INTERACTIVE PRACTICE DRILL MODAL -->
    <div
      v-if="isPracticeModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isPracticeModalOpen = false"
    >
      <div
        class="relative w-full max-w-lg bg-[#0e1424] border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isPracticeModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-white text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 flex items-center justify-center text-lg">
            ⚡
          </div>
          <div>
            <h3 class="text-base font-bold text-white">AI Quick Practice Drill</h3>
            <p class="text-xs text-slate-400">Function Parameters & Return Expressions</p>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-4">
          <p class="text-xs sm:text-sm font-bold text-white leading-relaxed">
            {{ practiceQuestion.question }}
          </p>

          <div class="space-y-2">
            <button
              v-for="(opt, idx) in practiceQuestion.options"
              :key="opt"
              @click="submitPracticeAnswer(idx)"
              :disabled="practiceSubmitted"
              type="button"
              :class="[
                practiceSubmitted && idx === practiceQuestion.correctIndex
                  ? 'bg-emerald-950/80 border-emerald-500 text-emerald-300 font-bold'
                  : practiceSubmitted && practiceSelectedAnswer === idx && idx !== practiceQuestion.correctIndex
                  ? 'bg-rose-950/80 border-rose-500 text-rose-300'
                  : 'bg-slate-900 hover:bg-slate-800 border-slate-800 text-slate-300',
                'w-full p-3 rounded-xl border text-left text-xs flex items-center justify-between transition-all cursor-pointer disabled:cursor-default'
              ]"
            >
              <span>{{ opt }}</span>
              <span v-if="practiceSubmitted && idx === practiceQuestion.correctIndex" class="text-emerald-400">✓ Correct</span>
              <span v-else-if="practiceSubmitted && practiceSelectedAnswer === idx && idx !== practiceQuestion.correctIndex" class="text-rose-400">✗ Incorrect</span>
            </button>
          </div>

          <div v-if="practiceSubmitted" class="p-3 rounded-xl bg-purple-950/30 border border-purple-800/40 text-xs text-slate-300 space-y-1">
            <p class="font-bold text-purple-300">💡 Explanation:</p>
            <p class="text-[11px] leading-relaxed">{{ practiceQuestion.explanation }}</p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="isPracticeModalOpen = false"
            type="button"
            class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold cursor-pointer"
          >
            Done
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #475569;
}
</style>
