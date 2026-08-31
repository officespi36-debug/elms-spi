<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface QuizCard {
  id: number
  title: string
  course: string
  category?: string
  topic?: string
  difficulty: string
  questions_count: number
  time_limit: number
  passing_score: number
  points: number
  code?: string
  icon?: string
  icon_bg?: string
  badge_color?: string
  description: string
  instructions?: string
  status?: string
}

interface UpcomingQuiz {
  id: number
  title: string
  course: string
  available_in: string
  points: number
  icon: string
  icon_bg: string
}

interface RecommendedQuiz {
  id: number
  title: string
  note: string
  score: number
  color_class: string
  icon: string
}

const props = defineProps<{
  analytics?: {
    summary: {
      available_quizzes: number
      available_note: string
      practice_quizzes: number
      practice_note: string
      assessments: number
      assessments_note: string
      total_points: number
      points_note: string
    }
    practice_quizzes: QuizCard[]
    assessments: QuizCard[]
    upcoming_quizzes: UpcomingQuiz[]
    quiz_progress: {
      average_score: number
      quizzes_count: number
      quizzes_trend: string
      correct_answers: number
      correct_trend: string
    }
    recommended_quizzes: RecommendedQuiz[]
    quiz_streak: {
      streak_days: number
      days: Array<{
        label: string
        date: string
        active: boolean
      }>
    }
  }
  filters?: {
    category: string
    course: string
    difficulty: string
    status: string
    search: string
  }
}>()

// Default baseline data
const defaultSummary = {
  available_quizzes: 24,
  available_note: 'Quizzes ready to take',
  practice_quizzes: 18,
  practice_note: 'Improve your skills',
  assessments: 6,
  assessments_note: 'Test your knowledge',
  total_points: 1450,
  points_note: 'Points you can earn',
}

const defaultPracticeQuizzes: QuizCard[] = [
  { id: 1, title: 'JavaScript Basics Quiz', course: 'JavaScript Fundamentals', category: 'Front-End', difficulty: 'Easy', questions_count: 20, time_limit: 20, passing_score: 70, points: 100, code: 'JS', icon_bg: 'from-amber-400 to-amber-500 text-slate-950', badge_color: 'emerald', description: 'Test your understanding of JavaScript basics including variables, data types.', instructions: '20 multiple-choice questions covering variables, functions, and arrays.' },
  { id: 2, title: 'React Components Quiz', course: 'React.js Fundamentals', category: 'Front-End', difficulty: 'Medium', questions_count: 25, time_limit: 30, passing_score: 70, points: 125, code: '⚛️', icon_bg: 'from-cyan-400 to-blue-500 text-white', badge_color: 'amber', description: 'Test your knowledge of React components, props, and state management.', instructions: '25 conceptual questions on functional components and hooks.' },
  { id: 3, title: 'HTML & CSS Quiz', course: 'Web Development', category: 'Front-End', difficulty: 'Easy', questions_count: 15, time_limit: 20, passing_score: 70, points: 75, code: '5', icon_bg: 'from-orange-500 to-amber-500 text-white', badge_color: 'emerald', description: 'Test your HTML and CSS knowledge including tags, selectors, and layout.', instructions: '15 questions on CSS flexbox, grid, and semantic markup.' },
  { id: 4, title: 'SQL Queries Quiz', course: 'Database Systems', category: 'Database', difficulty: 'Hard', questions_count: 30, time_limit: 35, passing_score: 75, points: 150, code: '🗄️', icon_bg: 'from-blue-500 to-indigo-600 text-white', badge_color: 'rose', description: 'Test your SQL skills including SELECT, JOIN, WHERE, and GROUP BY.', instructions: '30 query-analysis questions with complex relational joins.' },
]

const defaultAssessments: QuizCard[] = [
  { id: 101, title: 'JavaScript Assessment', course: 'Advanced JavaScript', difficulty: 'Hard', questions_count: 50, time_limit: 60, passing_score: 80, points: 250, icon: '🛡️', icon_bg: 'from-purple-600 to-indigo-600', badge_color: 'rose', description: 'Comprehensive assessment covering advanced JavaScript concepts.' },
  { id: 102, title: 'Full Stack Assessment', course: 'Full Stack Development', difficulty: 'Expert', questions_count: 100, time_limit: 120, passing_score: 85, points: 500, icon: '</>', icon_bg: 'from-emerald-500 to-teal-600', badge_color: 'amber', description: 'Complete full stack assessment covering frontend, backend, and database.' },
  { id: 103, title: 'React Developer Test', course: 'React Development', difficulty: 'Hard', questions_count: 60, time_limit: 75, passing_score: 80, points: 300, icon: '🛡️', icon_bg: 'from-blue-600 to-indigo-600', badge_color: 'rose', description: 'Test your React development skills and best practices.' },
]

const defaultUpcomingQuizzes: UpcomingQuiz[] = [
  { id: 201, title: 'TypeScript Basics Quiz', course: 'TypeScript Fundamentals', available_in: '2 days', points: 50, icon: '📅', icon_bg: 'bg-blue-500/20 text-blue-300 border border-blue-500/30' },
  { id: 202, title: 'Node.js Quiz', course: 'Backend Development', available_in: '5 days', points: 100, icon: '🟢', icon_bg: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' },
  { id: 203, title: 'API Development Quiz', course: 'Backend Development', available_in: '1 week', points: 125, icon: '🔥', icon_bg: 'bg-orange-500/20 text-orange-300 border border-orange-500/30' },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const practiceQuizzes = computed(() => props.analytics?.practice_quizzes || defaultPracticeQuizzes)
const assessments = computed(() => props.analytics?.assessments || defaultAssessments)
const upcomingQuizzes = computed(() => props.analytics?.upcoming_quizzes || defaultUpcomingQuizzes)
const quizProgress = computed(() => props.analytics?.quiz_progress || { average_score: 72, quizzes_count: 16, quizzes_trend: '+4 this month', correct_answers: 78, correct_trend: '+6%' })
const recommendedQuizzes = computed(() => props.analytics?.recommended_quizzes || [
  { id: 301, title: 'JavaScript Functions Quiz', note: 'Low score in Functions', score: 28, color_class: 'text-rose-400 font-bold', icon: '📙' },
  { id: 302, title: 'DOM Manipulation Quiz', note: 'Improve DOM skills', score: 35, color_class: 'text-rose-400 font-bold', icon: '💻' },
  { id: 303, title: 'Array Methods Quiz', note: 'Practice array methods', score: 42, color_class: 'text-amber-400 font-bold', icon: '📙' },
  { id: 304, title: 'Async JavaScript Quiz', note: 'Learn async concepts', score: 50, color_class: 'text-amber-400 font-bold', icon: '🟢' },
])
const quizStreak = computed(() => props.analytics?.quiz_streak || {
  streak_days: 7,
  days: [
    { label: 'May 26', date: '26', active: true },
    { label: 'May 27', date: '27', active: true },
    { label: 'May 28', date: '28', active: true },
    { label: 'May 29', date: '29', active: true },
    { label: 'May 30', date: '30', active: true },
    { label: 'May 31', date: '31', active: true },
    { label: 'Jun 1',  date: '1',  active: true },
  ]
})

// Filter State
const searchQuery = ref(props.filters?.search || '')
const selectedCategory = ref(props.filters?.category || 'all')
const selectedCourse = ref(props.filters?.course || 'all')
const selectedDifficulty = ref(props.filters?.difficulty || 'all')
const selectedStatus = ref<'all' | 'available' | 'upcoming' | 'unlocked'>(
  (props.filters?.status as any) || 'available'
)

// Modal State
const isPreviewModalOpen = ref(false)
const selectedQuiz = ref<QuizCard | null>(null)
const isLiveQuizOpen = ref(false)
const currentQuestionIndex = ref(0)
const selectedAnswerIndex = ref<number | null>(null)
const showResultFeedback = ref(false)

const openQuizPreview = (quiz: QuizCard) => {
  selectedQuiz.value = quiz
  isPreviewModalOpen.value = true
}

const startLiveQuiz = () => {
  isPreviewModalOpen.value = false
  isLiveQuizOpen.value = true
  currentQuestionIndex.value = 0
  selectedAnswerIndex.value = null
  showResultFeedback.value = false
}

// Sample interactive drill questions
const sampleQuestions = ref([
  {
    q: 'Which keyword is used to declare a block-scoped variable in modern JavaScript (ES6+)?',
    options: ['var', 'let', 'global', 'define'],
    correct: 1,
    explanation: 'The "let" keyword declares a block-scoped local variable, optionally initializing it to a value.'
  },
  {
    q: 'What is the primary purpose of the useEffect hook in React?',
    options: ['To manage state', 'To perform side effects in functional components', 'To define routes', 'To style components'],
    correct: 1,
    explanation: 'useEffect allows you to perform side effects like data fetching, subscriptions, or DOM manipulation.'
  },
  {
    q: 'In SQL, which clause is used to filter group results created by GROUP BY?',
    options: ['WHERE', 'HAVING', 'ORDER BY', 'FILTER'],
    correct: 1,
    explanation: 'The HAVING clause was added to SQL because the WHERE keyword cannot be used with aggregate functions.'
  }
])

const handleFilterChange = () => {
  router.get('/student/quizzes/practice', {
    category: selectedCategory.value,
    course: selectedCourse.value,
    difficulty: selectedDifficulty.value,
    status: selectedStatus.value,
    search: searchQuery.value,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}
</script>

<template>
  <StudentLayout title="Available Quizzes — Quizzes & Assessments">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
            <span>Available Quizzes</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">📝</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            Browse and take quizzes to test your knowledge and track your progress.
          </p>
        </div>

        <Link
          href="/student/progress/weekly"
          class="px-4 py-2 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-purple-500/40 text-slate-300 hover:text-white text-xs font-bold transition-all shadow-md self-start sm:self-auto flex items-center gap-2"
        >
          <span>📈</span>
          <span>View Performance</span>
        </Link>
      </div>

      <!-- ================= 2. SEARCH & FILTER BAR ================= -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-[#0F172A]/80 border border-slate-800/80 rounded-2xl p-3 shadow-lg">
        
        <!-- Search Input & Dropdowns -->
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <!-- Search Box -->
          <div class="relative min-w-[200px] flex-1 sm:max-w-xs">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs">🔍</span>
            <input
              v-model="searchQuery"
              @keyup.enter="handleFilterChange"
              type="text"
              placeholder="Search quizzes..."
              class="w-full bg-slate-900 border border-slate-700/80 rounded-xl pl-8 pr-3 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500 transition-colors"
            />
          </div>

          <!-- Category Dropdown -->
          <select
            v-model="selectedCategory"
            @change="handleFilterChange"
            class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="all">All Categories</option>
            <option value="Front-End">Front-End</option>
            <option value="Back-End">Back-End</option>
            <option value="Database">Database</option>
            <option value="Full Stack">Full Stack</option>
          </select>

          <!-- Course Dropdown -->
          <select
            v-model="selectedCourse"
            @change="handleFilterChange"
            class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="all">All Courses</option>
            <option value="JavaScript Fundamentals">JavaScript Fundamentals</option>
            <option value="React.js Fundamentals">React.js Fundamentals</option>
            <option value="Web Development">Web Development</option>
            <option value="Database Systems">Database Systems</option>
          </select>

          <!-- Difficulty Dropdown -->
          <select
            v-model="selectedDifficulty"
            @change="handleFilterChange"
            class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="all">All Difficulty</option>
            <option value="Easy">Easy</option>
            <option value="Medium">Medium</option>
            <option value="Hard">Hard</option>
            <option value="Expert">Expert</option>
          </select>
        </div>

        <!-- Status Filter Pills -->
        <div class="flex items-center gap-1.5 self-start lg:self-auto bg-slate-900/90 border border-slate-800 p-1 rounded-xl">
          <button
            v-for="st in ['all', 'available', 'upcoming', 'unlocked']"
            :key="st"
            @click="selectedStatus = st as any; handleFilterChange()"
            :class="[
              selectedStatus === st
                ? 'bg-purple-600 text-white shadow-md shadow-purple-900/40'
                : 'text-slate-400 hover:text-white',
              'px-3 py-1 rounded-lg text-xs font-bold capitalize transition-all cursor-pointer'
            ]"
          >
            {{ st }}
          </button>
        </div>

      </div>

      <!-- ================= 3. MAIN SPLIT (LEFT: 8/12, RIGHT: 4/12) ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT (8/12): SUMMARY CARDS + QUIZ SECTIONS ================= -->
        <div class="lg:col-span-8 space-y-6">

          <!-- 4 TOP SUMMARY CARDS -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            
            <!-- Card 1: Available Quizzes -->
            <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
              <div class="space-y-0.5">
                <p class="text-[10px] text-slate-400 font-medium">Available Quizzes</p>
                <p class="text-2xl font-black text-white font-mono">{{ summary.available_quizzes }}</p>
                <p class="text-[10px] text-slate-400 font-medium">{{ summary.available_note }}</p>
              </div>
              <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
                🧪
              </div>
            </div>

            <!-- Card 2: Practice Quizzes -->
            <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-blue-500/30 transition-all">
              <div class="space-y-0.5">
                <p class="text-[10px] text-slate-400 font-medium">Practice Quizzes</p>
                <p class="text-2xl font-black text-white font-mono">{{ summary.practice_quizzes }}</p>
                <p class="text-[10px] text-slate-400 font-medium">{{ summary.practice_note }}</p>
              </div>
              <div class="w-10 h-10 rounded-2xl bg-blue-600/20 border border-blue-500/30 text-blue-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
                ⏱
              </div>
            </div>

            <!-- Card 3: Assessments -->
            <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-emerald-500/30 transition-all">
              <div class="space-y-0.5">
                <p class="text-[10px] text-slate-400 font-medium">Assessments</p>
                <p class="text-2xl font-black text-white font-mono">{{ summary.assessments }}</p>
                <p class="text-[10px] text-slate-400 font-medium">{{ summary.assessments_note }}</p>
              </div>
              <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
                🎯
              </div>
            </div>

            <!-- Card 4: Total Points -->
            <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-amber-500/30 transition-all">
              <div class="space-y-0.5">
                <p class="text-[10px] text-slate-400 font-medium">Total Points</p>
                <p class="text-2xl font-black text-white font-mono">{{ Number(summary.total_points).toLocaleString() }}</p>
                <p class="text-[10px] text-slate-400 font-medium">{{ summary.points_note }}</p>
              </div>
              <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-500/30 text-amber-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
                🏆
              </div>
            </div>

          </div>

          <!-- SECTION 1: PRACTICE QUIZZES (4 CARDS GRID) -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-base font-bold text-white tracking-tight">Practice Quizzes</h2>
                <p class="text-xs text-slate-400">Quizzes to help you practice and improve your understanding.</p>
              </div>
              <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">View All</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div
                v-for="quiz in practiceQuizzes"
                :key="quiz.id"
                class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-purple-500/40 transition-all flex flex-col justify-between space-y-3 group"
              >
                <div class="space-y-2">
                  <div class="flex items-start gap-3">
                    <div
                      :class="[
                        quiz.icon_bg || 'from-purple-500 to-indigo-600 text-white',
                        'w-9 h-9 rounded-xl bg-gradient-to-br flex items-center justify-center text-xs font-black shrink-0 font-mono shadow-md'
                      ]"
                    >
                      {{ quiz.code || 'JS' }}
                    </div>
                    <div class="min-w-0">
                      <h3 class="text-sm font-bold text-white group-hover:text-purple-300 transition-colors truncate">
                        {{ quiz.title }}
                      </h3>
                      <p class="text-[11px] text-slate-400 truncate">{{ quiz.course }}</p>
                    </div>
                  </div>

                  <!-- Badges: Difficulty & Questions count -->
                  <div class="flex items-center gap-2 pt-1">
                    <span
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                      :class="[
                        quiz.difficulty === 'Easy' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' :
                        quiz.difficulty === 'Medium' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' :
                        'bg-rose-500/20 text-rose-300 border-rose-500/30'
                      ]"
                    >
                      {{ quiz.difficulty }}
                    </span>
                    <span class="text-[11px] text-slate-400 font-mono font-medium">
                      {{ quiz.questions_count }} Questions
                    </span>
                  </div>

                  <!-- Description -->
                  <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed">
                    {{ quiz.description }}
                  </p>
                </div>

                <!-- Footer: Points & Start Quiz Button -->
                <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between">
                  <div class="flex items-center gap-1 text-xs font-bold text-amber-400 font-mono">
                    <span>⭐</span>
                    <span>{{ quiz.points }} Points</span>
                  </div>

                  <button
                    @click="openQuizPreview(quiz)"
                    class="px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-900/40 transition-all cursor-pointer active:scale-95"
                  >
                    Start Quiz
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION 2: ASSESSMENTS (3 CARDS GRID) -->
          <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-base font-bold text-white tracking-tight">Assessments</h2>
                <p class="text-xs text-slate-400">Comprehensive assessments to evaluate your overall knowledge.</p>
              </div>
              <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">View All</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div
                v-for="item in assessments"
                :key="item.id"
                class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-purple-500/40 transition-all flex flex-col justify-between space-y-3 group"
              >
                <div class="space-y-2">
                  <div class="flex items-start gap-3">
                    <div
                      :class="[
                        item.icon_bg,
                        'w-9 h-9 rounded-xl bg-gradient-to-br flex items-center justify-center text-sm font-black shrink-0 text-white shadow-md'
                      ]"
                    >
                      {{ item.icon }}
                    </div>
                    <div class="min-w-0">
                      <h3 class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors truncate">
                        {{ item.title }}
                      </h3>
                      <p class="text-[10px] text-slate-400 truncate">{{ item.course }}</p>
                    </div>
                  </div>

                  <div class="flex items-center gap-2 pt-0.5">
                    <span
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                      :class="[
                        item.difficulty === 'Expert' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' :
                        'bg-rose-500/20 text-rose-300 border-rose-500/30'
                      ]"
                    >
                      {{ item.difficulty }}
                    </span>
                    <span class="text-[10px] text-slate-400 font-mono font-medium">
                      {{ item.questions_count }} Questions
                    </span>
                  </div>

                  <p class="text-[11px] text-slate-300 line-clamp-2 leading-relaxed">
                    {{ item.description }}
                  </p>
                </div>

                <div class="pt-3 border-t border-slate-800/60 flex items-center justify-between">
                  <div class="flex items-center gap-1 text-[11px] font-bold text-amber-400 font-mono">
                    <span>⭐</span>
                    <span>{{ item.points }} Points</span>
                  </div>

                  <button
                    @click="openQuizPreview(item)"
                    class="px-3 py-1.5 rounded-xl bg-purple-600/30 hover:bg-purple-600 border border-purple-500/40 text-purple-300 hover:text-white font-bold text-[11px] transition-all cursor-pointer"
                  >
                    Start Assessment
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION 3: UPCOMING QUIZZES (3 CARDS GRID) -->
          <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-base font-bold text-white tracking-tight">Upcoming Quizzes</h2>
                <p class="text-xs text-slate-400">Scheduled quizzes that will be available soon.</p>
              </div>
              <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">View All</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div
                v-for="up in upcomingQuizzes"
                :key="up.id"
                class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-8 h-8 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-sm shrink-0">
                    {{ up.icon }}
                  </div>
                  <div class="min-w-0">
                    <h3 class="text-xs font-bold text-white truncate">{{ up.title }}</h3>
                    <p class="text-[10px] text-slate-400 truncate">{{ up.course }}</p>
                  </div>
                </div>

                <div class="text-right shrink-0">
                  <p class="text-[10px] text-slate-400">Available in:</p>
                  <p class="text-xs font-bold text-purple-300 font-mono">{{ up.available_in }}</p>
                  <p class="text-[9px] text-amber-400 font-mono">{{ up.points }} Points</p>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- ================= RIGHT (4/12): WIDGETS SIDEBAR ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- WIDGET 1: Your Quiz Progress -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-white tracking-tight">Your Quiz Progress</h3>
              <select
                class="bg-slate-900 border border-slate-700 text-[10px] font-semibold text-slate-300 rounded-lg px-2 py-0.5 focus:outline-none"
              >
                <option>This Month</option>
                <option>Last 30 Days</option>
                <option>All Time</option>
              </select>
            </div>

            <div class="flex items-center justify-between gap-4">
              <!-- Circular Progress Gauge -->
              <div class="relative w-20 h-20 flex items-center justify-center shrink-0">
                <svg class="w-20 h-20 -rotate-90 transform" viewBox="0 0 36 36">
                  <path
                    class="text-slate-800"
                    stroke-width="3.5"
                    stroke="currentColor"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                  <path
                    class="text-purple-500 transition-all duration-1000"
                    stroke-dasharray="72, 100"
                    stroke-width="3.5"
                    stroke-linecap="round"
                    stroke="currentColor"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-sm font-black text-white font-mono leading-none">{{ quizProgress.average_score }}%</span>
                  <span class="text-[8px] text-slate-400 mt-0.5">Quiz Score</span>
                </div>
              </div>

              <!-- Right Quick Stats -->
              <div class="space-y-1.5 text-xs flex-1">
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 text-[11px]">Average Score</span>
                  <span class="font-bold text-white font-mono text-[11px]">{{ quizProgress.average_score }}%</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 text-[11px]">Quizzes</span>
                  <span class="font-bold text-white font-mono text-[11px]">{{ quizProgress.quizzes_count }} <span class="text-emerald-400 text-[10px]">↑ 4</span></span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 text-[11px]">Correct Answers</span>
                  <span class="font-bold text-white font-mono text-[11px]">{{ quizProgress.correct_answers }}% <span class="text-emerald-400 text-[10px]">↑ 6%</span></span>
                </div>
              </div>
            </div>

            <!-- View Quiz Performance Button -->
            <Link
              href="/student/progress/weekly"
              class="w-full py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700/80 text-slate-300 hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5"
            >
              <span>📈</span>
              <span>View Quiz Performance</span>
            </Link>
          </div>

          <!-- WIDGET 2: Recommended for You (Weak Topics) -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <div>
                <h3 class="text-sm font-bold text-white tracking-tight">Recommended for You</h3>
                <p class="text-[10px] text-slate-400">Based on your weak topics</p>
              </div>
              <Link href="/student/ai-path/weak-topics" class="text-xs text-purple-400 font-bold hover:underline">
                View All
              </Link>
            </div>

            <div class="space-y-2">
              <div
                v-for="rec in recommendedQuizzes"
                :key="rec.id"
                class="p-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/60 flex items-center justify-between gap-3 hover:border-purple-500/30 transition-all cursor-pointer"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <span class="text-sm shrink-0">{{ rec.icon }}</span>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">{{ rec.title }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ rec.note }}</p>
                  </div>
                </div>

                <span :class="[rec.color_class, 'text-xs font-mono shrink-0']">
                  {{ rec.score }}%
                </span>
              </div>
            </div>
          </div>

          <!-- WIDGET 3: Quiz Streak -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <div>
                <h3 class="text-sm font-bold text-white tracking-tight">Quiz Streak</h3>
                <p class="text-[10px] text-slate-400">Keep it up! You're doing great.</p>
              </div>
              <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">More</span>
            </div>

            <div class="flex items-center gap-3 py-1">
              <div class="w-10 h-10 rounded-2xl bg-orange-500/20 border border-orange-500/30 text-orange-400 flex items-center justify-center text-xl shadow-inner animate-pulse shrink-0">
                🔥
              </div>
              <div>
                <p class="text-base font-black text-white font-mono">{{ quizStreak.streak_days }} Days</p>
                <p class="text-[10px] text-slate-400">Current Streak</p>
              </div>
            </div>

            <!-- 7-Day Checks -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-800/60">
              <div
                v-for="d in quizStreak.days"
                :key="d.label"
                class="flex flex-col items-center gap-1"
              >
                <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[9px] font-bold shadow-sm">
                  ✓
                </div>
                <span class="text-[8px] font-mono text-slate-400">{{ d.date }}</span>
              </div>
            </div>
          </div>

          <!-- WIDGET 4: AI Study Assistant -->
          <div class="bg-gradient-to-br from-[#10132B] via-[#0F172A] to-[#1E1138] border border-purple-900/50 rounded-3xl p-5 shadow-2xl space-y-3 relative overflow-hidden">
            <div class="flex items-center justify-between border-b border-purple-900/40 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">AI Study Assistant</h3>
              <span class="text-lg">🤖</span>
            </div>

            <div class="space-y-1 text-xs text-slate-300">
              <p class="font-bold text-white">Get help before taking quizzes</p>
              <p class="text-[11px] text-slate-400 leading-relaxed">
                Ask AI to explain difficult topics, create practice quizzes, or study tips.
              </p>
            </div>

            <Link
              href="/student/ai-tutor"
              class="w-full py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md shadow-purple-950/40 transition-all flex items-center justify-center gap-1.5"
            >
              <span>✨</span>
              <span>Ask AI Assistant</span>
            </Link>
          </div>

        </div>

      </div>

    </div>

    <!-- ================= MODAL: QUIZ PREVIEW & INSTRUCTIONS ================= -->
    <div
      v-if="isPreviewModalOpen && selectedQuiz"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-purple-600/30 border border-purple-500/40 text-purple-300 flex items-center justify-center text-sm font-bold font-mono">
              {{ selectedQuiz.code || '📝' }}
            </div>
            <div>
              <h3 class="text-base font-black text-white">{{ selectedQuiz.title }}</h3>
              <p class="text-[11px] text-purple-300">{{ selectedQuiz.course }}</p>
            </div>
          </div>
          <button
            @click="isPreviewModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 bg-slate-950 p-4 rounded-2xl border border-slate-800 text-xs">
          <p class="text-slate-200">{{ selectedQuiz.description }}</p>

          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-2 border-t border-slate-800/80 text-slate-300">
            <div>
              <p class="text-[10px] text-slate-500">Questions:</p>
              <p class="font-mono font-bold text-white">{{ selectedQuiz.questions_count }} Qs</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-500">Time Limit:</p>
              <p class="font-mono font-bold text-white">{{ selectedQuiz.time_limit }} Minutes</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-500">Passing Score:</p>
              <p class="font-mono font-bold text-emerald-400">{{ selectedQuiz.passing_score }}%</p>
            </div>
          </div>

          <div class="p-3 rounded-xl bg-purple-950/40 border border-purple-500/30 text-[11px] text-purple-200">
            💡 <strong>Instructions:</strong> {{ selectedQuiz.instructions || 'Answer all questions within the allocated time. Score above the passing mark to claim reward points.' }}
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isPreviewModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Cancel
          </button>
          <button
            @click="startLiveQuiz"
            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5"
          >
            <span>🚀</span>
            <span>Start Quiz Now</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: LIVE INTERACTIVE PRACTICE SESSION ================= -->
    <div
      v-if="isLiveQuizOpen && selectedQuiz"
      class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-2xl w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <span class="px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 text-[10px] font-bold border border-purple-500/30">
              Live Drill Session
            </span>
            <h3 class="text-base font-black text-white mt-1">{{ selectedQuiz.title }}</h3>
          </div>
          <button
            @click="isLiveQuizOpen = false"
            class="text-xs text-slate-400 hover:text-white"
          >
            ✕ Exit
          </button>
        </div>

        <!-- Question Box -->
        <div class="space-y-3 bg-slate-950 p-5 rounded-2xl border border-slate-800">
          <p class="text-xs font-mono font-bold text-purple-400">Question {{ currentQuestionIndex + 1 }} of {{ sampleQuestions.length }}</p>
          <h4 class="text-sm sm:text-base font-bold text-white leading-relaxed">
            {{ sampleQuestions[currentQuestionIndex].q }}
          </h4>

          <div class="space-y-2 pt-2">
            <button
              v-for="(opt, oIdx) in sampleQuestions[currentQuestionIndex].options"
              :key="oIdx"
              @click="selectedAnswerIndex = oIdx"
              :class="[
                showResultFeedback && oIdx === sampleQuestions[currentQuestionIndex].correct
                  ? 'bg-emerald-500/20 border-emerald-500 text-emerald-300 font-bold'
                  : showResultFeedback && selectedAnswerIndex === oIdx && oIdx !== sampleQuestions[currentQuestionIndex].correct
                  ? 'bg-rose-500/20 border-rose-500 text-rose-300 font-bold'
                  : selectedAnswerIndex === oIdx
                  ? 'bg-purple-600/30 border-purple-500 text-white font-bold'
                  : 'bg-slate-900 border-slate-800 text-slate-300 hover:bg-slate-800',
                'w-full p-3 rounded-xl border text-xs text-left flex items-center justify-between transition-all cursor-pointer'
              ]"
            >
              <div class="flex items-center gap-2.5">
                <span class="w-5 h-5 rounded-full bg-slate-800 flex items-center justify-center font-mono text-[10px]">
                  {{ String.fromCharCode(65 + oIdx) }}
                </span>
                <span>{{ opt }}</span>
              </div>

              <span v-if="showResultFeedback && oIdx === sampleQuestions[currentQuestionIndex].correct" class="text-emerald-400 font-bold text-[11px]">✓ Correct</span>
            </button>
          </div>

          <!-- Explanation box -->
          <div v-if="showResultFeedback" class="p-3 rounded-xl bg-indigo-950/60 border border-indigo-500/30 text-xs text-slate-200 mt-2">
            <p class="font-bold text-indigo-300">💡 Explanation:</p>
            <p class="mt-0.5 text-[11px]">{{ sampleQuestions[currentQuestionIndex].explanation }}</p>
          </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-xs">
          <button
            :disabled="currentQuestionIndex === 0"
            @click="currentQuestionIndex--; selectedAnswerIndex = null; showResultFeedback = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold disabled:opacity-40"
          >
            Previous
          </button>

          <div class="flex items-center gap-2">
            <button
              v-if="!showResultFeedback"
              :disabled="selectedAnswerIndex === null"
              @click="showResultFeedback = true"
              class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md disabled:opacity-40"
            >
              Check Answer
            </button>

            <button
              v-if="showResultFeedback && currentQuestionIndex < sampleQuestions.length - 1"
              @click="currentQuestionIndex++; selectedAnswerIndex = null; showResultFeedback = false"
              class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md"
            >
              Next Question ›
            </button>

            <button
              v-if="showResultFeedback && currentQuestionIndex === sampleQuestions.length - 1"
              @click="isLiveQuizOpen = false"
              class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-md"
            >
              Finish Practice ✓
            </button>
          </div>
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
