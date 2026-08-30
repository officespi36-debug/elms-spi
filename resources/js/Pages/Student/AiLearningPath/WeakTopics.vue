<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface WeakTopic {
  id: number
  title: string
  subtitle: string
  course: string
  chapter: string
  lessonHref: string
  score: number
  lastAttempt: string
  priority: 'High' | 'Medium' | 'Low'
  category: 'quiz' | 'assessment' | 'course'
  icon: string
  iconBg: string
  description: string
  commonMistakes: string[]
  recommendedTime: string
  aiAdvice: string
}

// Filter and Sort State
const activeTab = ref<'all' | 'quiz' | 'assessment' | 'course'>('all')
const sortBy = ref<'score_asc' | 'score_desc' | 'priority' | 'recent'>('score_asc')
const showMoreTopics = ref(false)

// Modals & Toast State
const selectedTopicForReview = ref<WeakTopic | null>(null)
const isReviewModalOpen = ref(false)
const isPracticeModalOpen = ref(false)
const isResourcesModalOpen = ref(false)
const isStudyPlanModalOpen = ref(false)
const showToast = ref(false)
const toastMessage = ref('')

// Interactive Practice Quiz State
const practiceQuestionIndex = ref(0)
const practiceSelectedAnswer = ref<number | null>(null)
const practiceSubmitted = ref(false)
const practiceScore = ref(0)

const practiceQuestions = ref([
  {
    topic: 'JavaScript Function Parameters',
    question: 'What is the value of `b` inside the function if called as `multiply(5)`?',
    code: 'function multiply(a, b = 2) {\n  return a * b;\n}',
    options: [
      { text: 'undefined', correct: false },
      { text: '2 (default parameter value)', correct: true },
      { text: 'null', correct: false },
      { text: 'NaN', correct: false }
    ],
    explanation: 'In ES6+, default function parameters allow named parameters to be initialized with default values if no value or `undefined` is passed.'
  },
  {
    topic: 'JavaScript Scope & Hoisting',
    question: 'What will be output to the console?',
    code: 'function testScope() {\n  if (true) {\n    var x = 10;\n    let y = 20;\n  }\n  console.log(x);\n}\ntestScope();',
    options: [
      { text: '10 (var is function-scoped)', correct: true },
      { text: 'ReferenceError: x is not defined', correct: false },
      { text: 'undefined', correct: false },
      { text: '20', correct: false }
    ],
    explanation: '`var` declarations are function-scoped or globally-scoped, meaning `x` is accessible anywhere within `testScope()`, whereas `let` is block-scoped.'
  },
  {
    topic: 'SQL JOIN Operations',
    question: 'Which SQL clause returns ALL records from the left table and matched records from the right table?',
    code: 'SELECT students.name, enrollments.course_id\nFROM students\n______ enrollments ON students.id = enrollments.student_id;',
    options: [
      { text: 'INNER JOIN', correct: false },
      { text: 'LEFT JOIN (or LEFT OUTER JOIN)', correct: true },
      { text: 'RIGHT JOIN', correct: false },
      { text: 'CROSS JOIN', correct: false }
    ],
    explanation: 'A LEFT JOIN returns all rows from the left table, even if there are no matches in the right table (unmatched columns will contain NULL).'
  }
])

// Weak Topics Master Data (Matching Reference Screenshot)
const weakTopicsList = ref<WeakTopic[]>([
  {
    id: 1,
    title: 'JavaScript Function Parameters',
    subtitle: 'Understanding function parameters and arguments',
    course: 'JavaScript Fundamentals',
    chapter: 'Chapter 3',
    lessonHref: '/student/my-courses/current',
    score: 28,
    lastAttempt: 'May 24, 2025',
    priority: 'High',
    category: 'quiz',
    icon: 'JS',
    iconBg: 'from-amber-400 to-amber-500 text-slate-950',
    description: 'Confusion between default parameters, rest parameters (...args), and passing arguments by reference vs value.',
    commonMistakes: [
      'Confusing parameter definitions with actual argument values',
      'Misunderstanding rest parameter syntax (...args) vs spread operator',
      'Forgetting that objects and arrays are passed by reference'
    ],
    recommendedTime: '90 minutes',
    aiAdvice: 'Focus on practicing writing functions that accept variable arguments and setting sensible default parameters.'
  },
  {
    id: 2,
    title: 'JavaScript Scope',
    subtitle: 'Global scope, local scope, and block scope',
    course: 'JavaScript Fundamentals',
    chapter: 'Chapter 4',
    lessonHref: '/student/my-courses/current',
    score: 35,
    lastAttempt: 'May 23, 2025',
    priority: 'High',
    category: 'quiz',
    icon: '{ }',
    iconBg: 'from-blue-600 to-indigo-700 text-white',
    description: 'Issues with lexical scoping, variable shadowing, and differences between var, let, and const declarations.',
    commonMistakes: [
      'Assuming let/const are hoisted and accessible before initialization',
      'Accidentally creating global variables inside functions without declaration keywords',
      'Shadowing outer variables unintentionally in nested closures'
    ],
    recommendedTime: '90 minutes',
    aiAdvice: 'Study the Temporal Dead Zone (TDZ) and trace variable lookup chains step-by-step.'
  },
  {
    id: 3,
    title: 'SQL JOIN Operations',
    subtitle: 'INNER JOIN, LEFT JOIN, RIGHT JOIN',
    course: 'Database Systems',
    chapter: 'Chapter 5',
    lessonHref: '/student/my-courses/overview',
    score: 42,
    lastAttempt: 'May 20, 2025',
    priority: 'Medium',
    category: 'assessment',
    icon: '🗄️',
    iconBg: 'from-purple-600 to-indigo-600 text-white',
    description: 'Difficulty selecting the appropriate JOIN type when handling NULL values and multiple table relationships.',
    commonMistakes: [
      'Using INNER JOIN when NULL values in foreign keys must be retained',
      'Missing the ON condition leading to unintended Cartesian cross products',
      'Ambiguous column references without table prefixes'
    ],
    recommendedTime: '60 minutes',
    aiAdvice: 'Visualize Venn diagrams of table relationships and practice building multi-table queries.'
  },
  {
    id: 4,
    title: 'HTML Forms & Validation',
    subtitle: 'Form elements and validation techniques',
    course: 'Web Development',
    chapter: 'Chapter 6',
    lessonHref: '/student/my-courses/overview',
    score: 45,
    lastAttempt: 'May 22, 2025',
    priority: 'Medium',
    category: 'course',
    icon: '</>',
    iconBg: 'from-orange-500 to-rose-600 text-white',
    description: 'HTML5 built-in validation attributes (required, pattern, min/max) and client-side error handling.',
    commonMistakes: [
      'Not wrapping input fields inside standard <form> tags',
      'Misconfiguring regex pattern attributes for email and phone numbers',
      'Ignoring accessibility labels for screen readers'
    ],
    recommendedTime: '45 minutes',
    aiAdvice: 'Review HTML5 validation constraints and test custom regex validation rules.'
  },
  {
    id: 5,
    title: 'Data Structures - Arrays',
    subtitle: 'Array operations and manipulations',
    course: 'Data Structures',
    chapter: 'Chapter 2',
    lessonHref: '/student/my-courses/overview',
    score: 48,
    lastAttempt: 'May 21, 2025',
    priority: 'Medium',
    category: 'quiz',
    icon: '📊',
    iconBg: 'from-cyan-500 to-blue-600 text-white',
    description: 'Array methods: map, filter, reduce, slice vs splice, and in-place mutations.',
    commonMistakes: [
      'Confusing array slice() (immutable) with splice() (mutating)',
      'Forgetting to return accumulated values inside Array.reduce() callbacks',
      'Iterating with for-in instead of for-of or forEach'
    ],
    recommendedTime: '60 minutes',
    aiAdvice: 'Practice chaining array higher-order functions (map/filter/reduce) without mutating source data.'
  },
  {
    id: 6,
    title: 'CSS Grid Layouts & Areas',
    subtitle: 'Grid template columns, fractional units and areas',
    course: 'CSS3 Mastery',
    chapter: 'Chapter 4',
    lessonHref: '/student/my-courses/overview',
    score: 52,
    lastAttempt: 'May 19, 2025',
    priority: 'Medium',
    category: 'course',
    icon: '#',
    iconBg: 'from-teal-500 to-emerald-600 text-white',
    description: 'Grid layout positioning, auto-fit vs auto-fill, and named grid areas.',
    commonMistakes: [
      'Confusing grid-column-start/end with grid-template-columns',
      'Not specifying fallback widths for older browser viewports',
      'Misunderstanding minmax() function boundaries'
    ],
    recommendedTime: '45 minutes',
    aiAdvice: 'Build a full magazine layout using named grid-template-areas.'
  },
  {
    id: 7,
    title: 'Python Dictionary Comprehensions',
    subtitle: 'Iterating and manipulating key-value pairs efficiently',
    course: 'Python Essentials',
    chapter: 'Chapter 5',
    lessonHref: '/student/my-courses/overview',
    score: 56,
    lastAttempt: 'May 18, 2025',
    priority: 'Medium',
    category: 'quiz',
    icon: 'PY',
    iconBg: 'from-emerald-500 to-green-600 text-white',
    description: 'Constructing dictionaries with concise comprehension syntax and conditional filtering.',
    commonMistakes: [
      'Inverting key and value variables during iteration',
      'Forgetting .items() method when unpacking dictionary tuples',
      'Overcomplicating nested comprehension expressions'
    ],
    recommendedTime: '40 minutes',
    aiAdvice: 'Convert traditional for-loops that populate dictionaries into elegant single-line comprehensions.'
  },
  {
    id: 8,
    title: 'Git Merge Conflicts & Branching',
    subtitle: 'Resolving conflict markers and rebasing branches',
    course: 'Git & Version Control',
    chapter: 'Chapter 3',
    lessonHref: '/student/my-courses/overview',
    score: 64,
    lastAttempt: 'May 15, 2025',
    priority: 'Low',
    category: 'assessment',
    icon: 'GIT',
    iconBg: 'from-rose-500 to-red-600 text-white',
    description: 'Understanding HEAD markers (<<<<<<<, =======, >>>>>>>) and resolving divergent commits.',
    commonMistakes: [
      'Accidentally committing conflict delimiter strings into version control',
      'Force pushing before pulling latest upstream master branch commits',
      'Misinterpreting incoming change vs current change in merge tools'
    ],
    recommendedTime: '30 minutes',
    aiAdvice: 'Simulate conflicting branches in a sandbox repo and practice resolving three-way merges.'
  }
])

// Filtered and Sorted Topics
const filteredTopics = computed(() => {
  let list = [...weakTopicsList.value]

  // Filter by Tab
  if (activeTab.value !== 'all') {
    list = list.filter(t => t.category === activeTab.value)
  }

  // Sort
  if (sortBy.value === 'score_asc') {
    list.sort((a, b) => a.score - b.score)
  } else if (sortBy.value === 'score_desc') {
    list.sort((a, b) => b.score - a.score)
  } else if (sortBy.value === 'priority') {
    const priorityWeight = { High: 3, Medium: 2, Low: 1 }
    list.sort((a, b) => priorityWeight[b.priority] - priorityWeight[a.priority])
  } else if (sortBy.value === 'recent') {
    list.sort((a, b) => new Date(b.lastAttempt).getTime() - new Date(a.lastAttempt).getTime())
  }

  return list
})

const visibleTopics = computed(() => {
  if (showMoreTopics.value) {
    return filteredTopics.value
  }
  return filteredTopics.value.slice(0, 5)
})

// Quick Stats
const stats = ref({
  totalTopics: 8,
  avgScore: 47,
  focusTime: '6h 30m',
  improvementPotential: '+32%'
})

// Methods
const openReviewModal = (topic: WeakTopic) => {
  selectedTopicForReview.value = topic
  isReviewModalOpen.value = true
}

const startPracticeQuiz = (topic?: WeakTopic) => {
  practiceQuestionIndex.value = 0
  practiceSelectedAnswer.value = null
  practiceSubmitted.value = false
  practiceScore.value = 0
  isReviewModalOpen.value = false
  isPracticeModalOpen.value = true
}

const submitAnswer = () => {
  if (practiceSelectedAnswer.value === null) return
  practiceSubmitted.value = true
  const currentQ = practiceQuestions.value[practiceQuestionIndex.value]
  if (currentQ.options[practiceSelectedAnswer.value].correct) {
    practiceScore.value += 1
  }
}

const nextPracticeQuestion = () => {
  if (practiceQuestionIndex.value < practiceQuestions.value.length - 1) {
    practiceQuestionIndex.value += 1
    practiceSelectedAnswer.value = null
    practiceSubmitted.value = false
  } else {
    // Quiz finished
    isPracticeModalOpen.value = false
    toastMessage.value = `Practice Quiz Completed! Score: ${practiceScore.value}/${practiceQuestions.value.length} (+18% topic mastery boost)`
    showToast.value = true
    setTimeout(() => {
      showToast.value = false
    }, 4000)
  }
}

const startStudyPlan = () => {
  isStudyPlanModalOpen.value = true
}

const confirmStudyPlan = () => {
  isStudyPlanModalOpen.value = false
  toastMessage.value = 'Day 1 Study Plan activated! Redirecting to JavaScript Function Parameters lesson...'
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
    window.location.href = '/student/my-courses/current'
  }, 2000)
}
</script>

<template>
  <StudentLayout
    title="Weak Topics Review"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Weak Topics Review' }
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

      <!-- 1. PAGE HEADER (Title with Declining Chart Icon & Subtitle) -->
      <div>
        <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
          <span>Weak Topics Review</span>
          <span class="text-xl">📉</span>
        </h1>
        <p class="text-xs sm:text-sm text-slate-400 mt-1">
          Focus on your weak topics to improve your understanding and boost your performance.
        </p>
      </div>

      <!-- 2. TOP SECTION: 4 METRIC CARDS (8 Cols) vs AI RECOMMENDATION (4 Cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- LEFT 8 COLS: 4 METRIC CARDS -->
        <div class="lg:col-span-8 grid grid-cols-2 sm:grid-cols-4 gap-4">
          
          <!-- Metric 1: Overall Weak Topics -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-400">Overall Weak Topics</span>
              <div class="w-8 h-8 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center text-xs border border-rose-500/30">
                📉
              </div>
            </div>
            <div>
              <div class="text-2xl font-black text-white">
                {{ stats.totalTopics }} <span class="text-xs font-normal text-slate-400">topics</span>
              </div>
              <p class="text-[11px] text-rose-400 font-semibold mt-0.5">Needs improvement</p>
            </div>
          </div>

          <!-- Metric 2: Average Score -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-400">Average Score</span>
            </div>
            <div>
              <div class="text-2xl font-black text-white">
                {{ stats.avgScore }}%
              </div>
              <p class="text-[11px] text-slate-400 mt-0.5">Across weak topics</p>
              <!-- Mini score bar -->
              <div class="w-full h-1 rounded-full bg-slate-900 overflow-hidden mt-2">
                <div class="h-full bg-rose-500 rounded-full" :style="{ width: `${stats.avgScore}%` }"></div>
              </div>
            </div>
          </div>

          <!-- Metric 3: Recommended Focus Time -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-400">Recommended Focus Time</span>
              <div class="w-8 h-8 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center text-xs border border-purple-500/30">
                ⏱️
              </div>
            </div>
            <div>
              <div class="text-2xl font-black text-white">
                {{ stats.focusTime }}
              </div>
              <p class="text-[11px] text-slate-400 mt-0.5">This week</p>
            </div>
          </div>

          <!-- Metric 4: Improvement Potential -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-400">Improvement Potential</span>
              <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs border border-emerald-500/30">
                ↗
              </div>
            </div>
            <div>
              <div class="text-2xl font-black text-emerald-400">
                {{ stats.improvementPotential }}
              </div>
              <p class="text-[11px] text-slate-400 mt-0.5">If you focus on these topics</p>
            </div>
          </div>

        </div>

        <!-- RIGHT 4 COLS: AI RECOMMENDATION CARD -->
        <div class="lg:col-span-4 bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between relative overflow-hidden">
          <div class="space-y-3 z-10">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider">AI Recommendation</h3>
            <p class="text-xs font-bold text-slate-200">Focus on high priority topics first.</p>
            <p class="text-xs text-slate-400 leading-relaxed pr-16">
              I recommend you spend more time on <strong class="text-purple-300">JavaScript Function Parameters</strong> as it's fundamental for your next topics.
            </p>
          </div>

          <!-- 3D Robot Graphic on Right -->
          <div class="absolute right-3 top-4 pointer-events-none opacity-90 hidden sm:block">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-600/30 to-indigo-600/30 border border-purple-500/40 p-1 flex items-center justify-center text-3xl shadow-xl">
              🤖
            </div>
          </div>

          <!-- Action Button -->
          <div class="pt-4 z-10">
            <Link
              href="/student/ai-tutor?prompt=Create+a+personalized+study+plan+to+fix+my+weak+topic+JavaScript+Function+Parameters"
              class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-1.5 transition-all"
            >
              <span>🤖 ></span>
              <span>Ask AI for Study Plan</span>
            </Link>
          </div>
        </div>

      </div>

      <!-- 3. FILTER TABS & SORT CONTROLS -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 custom-scrollbar">
          <button
            @click="activeTab = 'all'"
            type="button"
            :class="[
              activeTab === 'all'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800',
              'px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            <span>● All Weak Topics</span>
            <span class="px-1.5 py-0.5 rounded-md bg-purple-950/80 text-purple-200 text-[10px] font-mono font-bold">{{ weakTopicsList.length }}</span>
          </button>

          <button
            @click="activeTab = 'quiz'"
            type="button"
            :class="[
              activeTab === 'quiz'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800',
              'px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            <span>Quiz Based</span>
            <span class="px-1.5 py-0.5 rounded-md bg-slate-800 text-slate-300 text-[10px] font-mono font-bold">5</span>
          </button>

          <button
            @click="activeTab = 'assessment'"
            type="button"
            :class="[
              activeTab === 'assessment'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800',
              'px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            <span>Assessment Based</span>
            <span class="px-1.5 py-0.5 rounded-md bg-slate-800 text-slate-300 text-[10px] font-mono font-bold">3</span>
          </button>

          <button
            @click="activeTab = 'course'"
            type="button"
            :class="[
              activeTab === 'course'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800',
              'px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            <span>Course Content</span>
            <span class="px-1.5 py-0.5 rounded-md bg-slate-800 text-slate-300 text-[10px] font-mono font-bold">4</span>
          </button>
        </div>

        <!-- Sort Dropdown -->
        <div class="flex items-center gap-2 shrink-0">
          <span class="text-xs text-slate-400">Sort by:</span>
          <select
            v-model="sortBy"
            class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs text-white focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="score_asc">Score (Low to High)</option>
            <option value="score_desc">Score (High to Low)</option>
            <option value="priority">Priority (High to Low)</option>
            <option value="recent">Recently Attempted</option>
          </select>
        </div>

      </div>

      <!-- 4. MAIN 2-COLUMN SECTION: WEAK TOPICS TABLE (8 Cols) vs FOCUS / ACTIONS / IMPROVEMENT (4 Cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8 COLS: YOUR WEAK TOPICS TABLE -->
        <div class="lg:col-span-8 bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
          
          <div>
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Your Weak Topics</h3>
            <p class="text-xs text-slate-400 mt-0.5">Topics you should focus on to improve your learning outcomes.</p>
          </div>

          <!-- Table Header -->
          <div class="hidden sm:grid grid-cols-12 gap-4 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800/80 pb-2 px-3">
            <div class="col-span-4">Topic</div>
            <div class="col-span-3">Related To</div>
            <div class="col-span-2">Your Score</div>
            <div class="col-span-1">Last Attempt</div>
            <div class="col-span-1 text-center">Priority</div>
            <div class="col-span-1 text-right">Action</div>
          </div>

          <!-- Topic Rows -->
          <div class="space-y-3">
            <div
              v-for="tp in visibleTopics"
              :key="tp.id"
              class="p-4 rounded-2xl bg-slate-950 border border-slate-800 hover:border-purple-500/40 shadow-sm flex flex-col sm:grid sm:grid-cols-12 gap-4 items-start sm:items-center transition-all group"
            >
              <!-- Topic Column (Col 4) -->
              <div class="sm:col-span-4 flex items-center gap-3 min-w-0">
                <div :class="[tp.iconBg, 'w-10 h-10 rounded-xl bg-gradient-to-br font-black text-xs flex items-center justify-center font-mono shadow-sm shrink-0']">
                  {{ tp.icon }}
                </div>
                <div class="min-w-0">
                  <h4 class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors truncate">
                    {{ tp.title }}
                  </h4>
                  <p class="text-[10px] text-slate-400 line-clamp-1">
                    {{ tp.subtitle }}
                  </p>
                </div>
              </div>

              <!-- Related To (Col 3) -->
              <div class="sm:col-span-3 text-xs text-slate-300">
                <p class="font-semibold text-white truncate">{{ tp.course }}</p>
                <p class="text-[10px] text-slate-400">{{ tp.chapter }}</p>
              </div>

              <!-- Your Score (Col 2) -->
              <div class="sm:col-span-2 space-y-1 w-full sm:w-auto">
                <div class="flex items-center justify-between sm:justify-start gap-2">
                  <span
                    :class="[
                      tp.score < 40 ? 'text-rose-400' : tp.score < 60 ? 'text-amber-400' : 'text-emerald-400',
                      'text-xs font-black font-mono'
                    ]"
                  >
                    {{ tp.score }}%
                  </span>
                </div>
                <div class="w-full sm:w-20 h-1 rounded-full bg-slate-900 overflow-hidden">
                  <div
                    :class="[
                      tp.score < 40 ? 'bg-rose-500' : tp.score < 60 ? 'bg-amber-500' : 'bg-emerald-500',
                      'h-full rounded-full'
                    ]"
                    :style="{ width: `${tp.score}%` }"
                  ></div>
                </div>
              </div>

              <!-- Last Attempt (Col 1) -->
              <div class="sm:col-span-1 text-[10px] text-slate-400 whitespace-nowrap">
                {{ tp.lastAttempt }}
              </div>

              <!-- Priority (Col 1) -->
              <div class="sm:col-span-1 flex justify-start sm:justify-center">
                <span
                  :class="[
                    tp.priority === 'High'
                      ? 'bg-rose-500/20 text-rose-300 border-rose-500/40'
                      : tp.priority === 'Medium'
                      ? 'bg-amber-500/20 text-amber-300 border-amber-500/40'
                      : 'bg-slate-800 text-slate-300 border-slate-700',
                    'px-2 py-0.5 rounded-full text-[10px] font-bold border'
                  ]"
                >
                  ● {{ tp.priority }}
                </span>
              </div>

              <!-- Action (Col 1) -->
              <div class="sm:col-span-1 flex items-center justify-end gap-1.5 w-full sm:w-auto">
                <button
                  @click="openReviewModal(tp)"
                  type="button"
                  class="px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/30 flex items-center gap-1 transition-all cursor-pointer whitespace-nowrap"
                >
                  <span>Review Now</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Show More Button -->
          <div class="pt-2 text-center">
            <button
              @click="showMoreTopics = !showMoreTopics"
              type="button"
              class="text-xs font-bold text-slate-400 hover:text-purple-300 transition-colors cursor-pointer inline-flex items-center gap-1.5 py-1"
            >
              <span>{{ showMoreTopics ? 'Show Less Weak Topics' : 'Show More Weak Topics' }}</span>
              <span :class="{ 'rotate-180': showMoreTopics }" class="transition-transform">▼</span>
            </button>
          </div>

        </div>

        <!-- RIGHT 4 COLS: FOCUS THIS WEEK, QUICK ACTIONS & RECENT IMPROVEMENT -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- CARD 1: FOCUS THIS WEEK (Circular Chart & Priority Breakdown) -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider border-b border-slate-800/80 pb-3">
              Focus This Week
            </h3>

            <div class="flex items-center justify-center pt-2">
              <div class="relative w-32 h-32 flex items-center justify-center">
                <!-- SVG Multi-Colored Ring -->
                <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                  <circle cx="50" cy="50" r="38" stroke="#1e293b" stroke-width="8" fill="transparent" />
                  <!-- High Priority (46%) -->
                  <circle
                    cx="50"
                    cy="50"
                    r="38"
                    stroke="#f43f5e"
                    stroke-width="8"
                    stroke-dasharray="238.76"
                    stroke-dashoffset="128.93"
                    fill="transparent"
                  />
                  <!-- Medium Priority (38%) -->
                  <circle
                    cx="50"
                    cy="50"
                    r="38"
                    stroke="#f59e0b"
                    stroke-width="8"
                    stroke-dasharray="238.76"
                    stroke-dashoffset="148.03"
                    stroke-linecap="round"
                    fill="transparent"
                    transform="rotate(165 50 50)"
                  />
                  <!-- Low Priority (16%) -->
                  <circle
                    cx="50"
                    cy="50"
                    r="38"
                    stroke="#10b981"
                    stroke-width="8"
                    stroke-dasharray="238.76"
                    stroke-dashoffset="200.55"
                    stroke-linecap="round"
                    fill="transparent"
                    transform="rotate(300 50 50)"
                  />
                </svg>

                <div class="absolute flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-white leading-none">6h 30m</span>
                  <span class="text-[9px] text-slate-400 uppercase font-semibold mt-1">Total Focus Time</span>
                </div>
              </div>
            </div>

            <!-- Priority Breakdown -->
            <div class="space-y-2 text-xs pt-1 border-t border-slate-800/80">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                  <span class="text-slate-300">High Priority</span>
                </div>
                <span class="font-bold text-white">3h 0m (46%)</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                  <span class="text-slate-300">Medium Priority</span>
                </div>
                <span class="font-bold text-white">2h 30m (38%)</span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                  <span class="text-slate-300">Low Priority</span>
                </div>
                <span class="font-bold text-white">1h 0m (16%)</span>
              </div>
            </div>
          </div>

          <!-- CARD 2: QUICK ACTIONS -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider border-b border-slate-800/80 pb-3">
              Quick Actions
            </h3>

            <div class="space-y-2 text-xs">
              <!-- Action 1: Practice Quiz -->
              <button
                @click="startPracticeQuiz()"
                type="button"
                class="w-full p-3 rounded-2xl bg-slate-950 hover:bg-slate-900 border border-slate-800 hover:border-purple-500/40 flex items-center justify-between transition-all cursor-pointer group text-left"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-xl bg-orange-500/20 text-orange-400 flex items-center justify-center text-xs font-bold">
                    ❓
                  </div>
                  <div>
                    <h4 class="font-bold text-white group-hover:text-purple-300 transition-colors">Practice Quiz</h4>
                    <p class="text-[10px] text-slate-400">Generate quiz on weak topics</p>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-purple-400 group-hover:translate-x-0.5 transition-all">›</span>
              </button>

              <!-- Action 2: AI Explain Topics -->
              <Link
                href="/student/ai-tutor?prompt=Explain+the+most+common+mistakes+in+my+weak+topics+with+examples"
                class="w-full p-3 rounded-2xl bg-slate-950 hover:bg-slate-900 border border-slate-800 hover:border-purple-500/40 flex items-center justify-between transition-all group"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center text-xs font-bold">
                    💬
                  </div>
                  <div>
                    <h4 class="font-bold text-white group-hover:text-purple-300 transition-colors">AI Explain Topics</h4>
                    <p class="text-[10px] text-slate-400">Get AI explanation for topics</p>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-purple-400 group-hover:translate-x-0.5 transition-all">›</span>
              </Link>

              <!-- Action 3: Study Resources -->
              <button
                @click="isResourcesModalOpen = true"
                type="button"
                class="w-full p-3 rounded-2xl bg-slate-950 hover:bg-slate-900 border border-slate-800 hover:border-purple-500/40 flex items-center justify-between transition-all cursor-pointer group text-left"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold">
                    📄
                  </div>
                  <div>
                    <h4 class="font-bold text-white group-hover:text-purple-300 transition-colors">Study Resources</h4>
                    <p class="text-[10px] text-slate-400">View recommended materials</p>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-purple-400 group-hover:translate-x-0.5 transition-all">›</span>
              </button>

              <!-- Action 4: Track Improvement -->
              <button
                @click="toastMessage = 'All improvements are synced live with your Learning Analytics!'"
                class="w-full p-3 rounded-2xl bg-slate-950 hover:bg-slate-900 border border-slate-800 hover:border-purple-500/40 flex items-center justify-between transition-all cursor-pointer group text-left"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center text-xs font-bold">
                    📈
                  </div>
                  <div>
                    <h4 class="font-bold text-white group-hover:text-purple-300 transition-colors">Track Improvement</h4>
                    <p class="text-[10px] text-slate-400">Monitor your progress</p>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-purple-400 group-hover:translate-x-0.5 transition-all">›</span>
              </button>
            </div>
          </div>

          <!-- CARD 3: RECENT IMPROVEMENT -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">Recent Improvement</h3>
              <span class="text-[11px] text-purple-400 font-bold hover:underline cursor-pointer">View All</span>
            </div>

            <div class="space-y-3">
              <!-- Item 1: JS Variables -->
              <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950 border border-slate-800/80">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center text-xs">
                    📕
                  </div>
                  <div>
                    <h4 class="text-xs font-bold text-white">JavaScript Variables</h4>
                    <p class="text-[10px] text-slate-400">Improved from 30% to 65%</p>
                  </div>
                </div>
                <span class="text-xs font-bold text-emerald-400 font-mono">+35%</span>
              </div>

              <!-- Item 2: HTML Elements -->
              <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950 border border-slate-800/80">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center text-xs">
                    📄
                  </div>
                  <div>
                    <h4 class="text-xs font-bold text-white">HTML Elements</h4>
                    <p class="text-[10px] text-slate-400">Improved from 40% to 70%</p>
                  </div>
                </div>
                <span class="text-xs font-bold text-emerald-400 font-mono">+30%</span>
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- 5. BOTTOM CARD: STUDY PLAN FOR YOU -->
      <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-5">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-lg">
              📅
            </div>
            <div>
              <h3 class="text-sm font-bold text-white uppercase tracking-wider">Study Plan for You</h3>
              <p class="text-xs text-slate-400 mt-0.5">Personalized study plan to improve your weak topics</p>
            </div>
          </div>

          <button
            @click="startStudyPlan"
            type="button"
            class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center gap-1.5 transition-all cursor-pointer shrink-0"
          >
            <span>></span>
            <span>Start Study Plan</span>
          </button>
        </div>

        <!-- 4 Connected Day Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
          
          <!-- Day 1 -->
          <div class="p-4 rounded-2xl bg-slate-950 border border-purple-500/50 shadow-md space-y-2 relative overflow-hidden group">
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-purple-300 font-black font-mono uppercase">Day 1</span>
              <span class="text-slate-400 font-mono">⏱ 90 min</span>
            </div>
            <h4 class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors">
              JavaScript Function Parameters
            </h4>
            <div class="flex items-center gap-1.5 text-[10px] text-rose-400 font-semibold pt-1">
              <span>●</span>
              <span>High Priority Review</span>
            </div>
          </div>

          <!-- Day 2 -->
          <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2 relative overflow-hidden group">
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-slate-400 font-black font-mono uppercase">Day 2</span>
              <span class="text-slate-400 font-mono">⏱ 90 min</span>
            </div>
            <h4 class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors">
              JavaScript Scope
            </h4>
            <div class="flex items-center gap-1.5 text-[10px] text-rose-400 font-semibold pt-1">
              <span>●</span>
              <span>High Priority Review</span>
            </div>
          </div>

          <!-- Day 3 -->
          <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2 relative overflow-hidden group">
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-slate-400 font-black font-mono uppercase">Day 3</span>
              <span class="text-slate-400 font-mono">⏱ 60 min</span>
            </div>
            <h4 class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors">
              SQL JOIN Operations
            </h4>
            <div class="flex items-center gap-1.5 text-[10px] text-amber-400 font-semibold pt-1">
              <span>●</span>
              <span>Medium Priority Review</span>
            </div>
          </div>

          <!-- Day 4 -->
          <div class="p-4 rounded-2xl bg-slate-950 border border-emerald-500/30 space-y-2 relative overflow-hidden group">
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-emerald-400 font-black font-mono uppercase">Day 4</span>
              <span class="text-slate-400 font-mono">⏱ 60 min</span>
            </div>
            <h4 class="text-xs font-bold text-white group-hover:text-emerald-300 transition-colors">
              Practice & Quiz
            </h4>
            <div class="flex items-center gap-1.5 text-[10px] text-emerald-400 font-semibold pt-1">
              <span>✓</span>
              <span>Comprehensive Drill</span>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- 1. TOPIC REVIEW MODAL -->
    <div
      v-if="isReviewModalOpen && selectedTopicForReview"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isReviewModalOpen = false"
    >
      <div
        class="relative w-full max-w-lg bg-[#0e1424] border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isReviewModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-white text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-start gap-3.5">
          <div :class="[selectedTopicForReview.iconBg, 'w-12 h-12 rounded-2xl bg-gradient-to-br font-black text-sm flex items-center justify-center font-mono shadow-md shrink-0']">
            {{ selectedTopicForReview.icon }}
          </div>
          <div>
            <span
              :class="[
                selectedTopicForReview.priority === 'High' ? 'text-rose-400' : 'text-amber-400',
                'text-[10px] font-bold uppercase tracking-wider'
              ]"
            >
              ● {{ selectedTopicForReview.priority }} Priority Review
            </span>
            <h3 class="text-base font-bold text-white leading-tight">
              {{ selectedTopicForReview.title }}
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              {{ selectedTopicForReview.course }} • {{ selectedTopicForReview.chapter }}
            </p>
          </div>
        </div>

        <!-- Performance Stats Box -->
        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 grid grid-cols-3 gap-3 text-center">
          <div>
            <span class="text-[10px] text-slate-400 uppercase">Current Score</span>
            <p class="text-base font-black text-rose-400 font-mono">{{ selectedTopicForReview.score }}%</p>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 uppercase">Target Score</span>
            <p class="text-base font-black text-emerald-400 font-mono">75%+</p>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 uppercase">Est. Review Time</span>
            <p class="text-base font-black text-white font-mono">{{ selectedTopicForReview.recommendedTime }}</p>
          </div>
        </div>

        <!-- Common Mistakes -->
        <div class="space-y-2 text-xs">
          <h4 class="font-bold text-slate-300">Common Mistakes Detected:</h4>
          <ul class="space-y-1.5">
            <li
              v-for="(mistake, idx) in selectedTopicForReview.commonMistakes"
              :key="idx"
              class="flex items-start gap-2 text-slate-300 text-[11px]"
            >
              <span class="text-rose-400 font-bold">✕</span>
              <span>{{ mistake }}</span>
            </li>
          </ul>
        </div>

        <!-- AI Advice -->
        <div class="p-3.5 rounded-2xl bg-purple-950/40 border border-purple-500/30 text-xs space-y-1">
          <span class="text-[10px] font-bold text-purple-300 uppercase flex items-center gap-1">
            <span>🤖</span>
            <span>AI Advice</span>
          </span>
          <p class="text-slate-300 text-[11px] leading-relaxed">
            {{ selectedTopicForReview.aiAdvice }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center gap-2 pt-2">
          <Link
            :href="selectedTopicForReview.lessonHref"
            class="w-full sm:flex-1 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-1.5 transition-all text-center"
          >
            <span>🚀 Open Exact Lesson</span>
          </Link>
          <button
            @click="startPracticeQuiz(selectedTopicForReview)"
            type="button"
            class="w-full sm:flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-purple-300 hover:text-white border border-slate-800 font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer"
          >
            <span>⚡ Practice Drill</span>
          </button>
        </div>

      </div>
    </div>

    <!-- 2. PRACTICE QUIZ MODAL -->
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

        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-sm">⚡</span>
            <span class="text-xs font-bold text-white uppercase tracking-wider">
              Weak Topic Practice Drill ({{ practiceQuestionIndex + 1 }}/{{ practiceQuestions.length }})
            </span>
          </div>
          <span class="text-xs text-purple-400 font-bold font-mono">
            Score: {{ practiceScore }}
          </span>
        </div>

        <div class="space-y-4 text-xs">
          <div>
            <span class="text-[10px] text-purple-300 font-bold font-mono uppercase">
              Topic: {{ practiceQuestions[practiceQuestionIndex].topic }}
            </span>
            <h3 class="text-sm font-bold text-white mt-1">
              {{ practiceQuestions[practiceQuestionIndex].question }}
            </h3>
          </div>

          <!-- Code Snippet -->
          <div v-if="practiceQuestions[practiceQuestionIndex].code" class="p-3 rounded-xl bg-slate-950 border border-slate-800 font-mono text-[11px] text-purple-300 whitespace-pre overflow-x-auto">
            {{ practiceQuestions[practiceQuestionIndex].code }}
          </div>

          <!-- Options -->
          <div class="space-y-2">
            <button
              v-for="(opt, oIdx) in practiceQuestions[practiceQuestionIndex].options"
              :key="oIdx"
              @click="!practiceSubmitted && (practiceSelectedAnswer = oIdx)"
              :disabled="practiceSubmitted"
              :class="[
                practiceSelectedAnswer === oIdx
                  ? practiceSubmitted
                    ? opt.correct
                      ? 'bg-emerald-950/60 border-emerald-500 text-emerald-300'
                      : 'bg-rose-950/60 border-rose-500 text-rose-300'
                    : 'bg-purple-950/60 border-purple-500 text-white'
                  : practiceSubmitted && opt.correct
                  ? 'bg-emerald-950/60 border-emerald-500 text-emerald-300'
                  : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-purple-500/40',
                'w-full p-3 rounded-xl border text-left text-xs font-semibold flex items-center justify-between transition-all cursor-pointer'
              ]"
            >
              <span>{{ opt.text }}</span>
              <span v-if="practiceSubmitted && opt.correct" class="text-emerald-400 font-bold">✓</span>
              <span v-else-if="practiceSubmitted && practiceSelectedAnswer === oIdx && !opt.correct" class="text-rose-400 font-bold">✕</span>
            </button>
          </div>

          <!-- Explanation after submit -->
          <div v-if="practiceSubmitted" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 text-xs space-y-1">
            <span class="text-[10px] font-bold text-purple-300 uppercase">💡 Explanation:</span>
            <p class="text-slate-300 text-[11px] leading-relaxed">
              {{ practiceQuestions[practiceQuestionIndex].explanation }}
            </p>
          </div>
        </div>

        <!-- Drill CTA -->
        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            v-if="!practiceSubmitted"
            @click="submitAnswer"
            :disabled="practiceSelectedAnswer === null"
            type="button"
            :class="[
              practiceSelectedAnswer === null ? 'opacity-50 cursor-not-allowed' : 'hover:bg-purple-500 cursor-pointer',
              'px-5 py-2.5 rounded-xl bg-purple-600 text-white text-xs font-bold shadow-lg shadow-purple-600/30 transition-all'
            ]"
          >
            Check Answer
          </button>
          <button
            v-else
            @click="nextPracticeQuestion"
            type="button"
            class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30 transition-all cursor-pointer"
          >
            {{ practiceQuestionIndex < practiceQuestions.length - 1 ? 'Next Question →' : 'Finish Practice Drill' }}
          </button>
        </div>
      </div>
    </div>

    <!-- 3. STUDY RESOURCES MODAL -->
    <div
      v-if="isResourcesModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isResourcesModalOpen = false"
    >
      <div
        class="relative w-full max-w-md bg-[#0e1424] border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isResourcesModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-white text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 flex items-center justify-center text-lg">
            📄
          </div>
          <div>
            <h3 class="text-base font-bold text-white">Recommended Study Resources</h3>
            <p class="text-xs text-slate-400">Curated cheat sheets and guides</p>
          </div>
        </div>

        <div class="space-y-2.5 text-xs">
          <a
            href="https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Functions"
            target="_blank"
            class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-purple-500/40 flex items-center justify-between text-slate-300 hover:text-white transition-all group"
          >
            <div class="flex items-center gap-2.5">
              <span class="text-purple-400">🌐</span>
              <span>MDN Functions & Scope Deep Dive</span>
            </div>
            <span class="text-[10px] text-purple-400 font-mono">Guide →</span>
          </a>

          <a
            href="/student/content"
            class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-purple-500/40 flex items-center justify-between text-slate-300 hover:text-white transition-all group"
          >
            <div class="flex items-center gap-2.5">
              <span class="text-rose-400">📑</span>
              <span>JavaScript ES6+ Cheatsheet (PDF)</span>
            </div>
            <span class="text-[10px] text-purple-400 font-mono">Download →</span>
          </a>

          <a
            href="https://sqlbolt.com/"
            target="_blank"
            class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-purple-500/40 flex items-center justify-between text-slate-300 hover:text-white transition-all group"
          >
            <div class="flex items-center gap-2.5">
              <span class="text-blue-400">🗄️</span>
              <span>SQL Joins Visual Interactive Tutorial</span>
            </div>
            <span class="text-[10px] text-purple-400 font-mono">Interactive →</span>
          </a>
        </div>

        <div class="pt-2 text-right">
          <button
            @click="isResourcesModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-900 text-slate-300 text-xs font-bold border border-slate-800"
          >
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- 4. START STUDY PLAN CONFIRMATION MODAL -->
    <div
      v-if="isStudyPlanModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isStudyPlanModalOpen = false"
    >
      <div
        class="relative w-full max-w-md bg-[#0e1424] border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isStudyPlanModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-white text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-lg">
            🚀
          </div>
          <div>
            <h3 class="text-base font-bold text-white">Start 4-Day Weak Topics Plan</h3>
            <p class="text-xs text-slate-400">Begin with Day 1: Function Parameters</p>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-xs text-slate-300 space-y-2">
          <p>
            Your AI Study Plan will guide you through:
          </p>
          <ul class="space-y-1 text-[11px] text-slate-400 list-disc list-inside">
            <li>Day 1: JS Function Parameters (90 mins review + 5 drills)</li>
            <li>Day 2: JS Scope & Closures (90 mins)</li>
            <li>Day 3: SQL JOIN Operations (60 mins)</li>
            <li>Day 4: Comprehensive Mastery Quiz (60 mins)</li>
          </ul>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            @click="isStudyPlanModalOpen = false"
            type="button"
            class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold border border-slate-800 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="confirmStudyPlan"
            type="button"
            class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30 cursor-pointer"
          >
            Confirm & Start Day 1
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>
