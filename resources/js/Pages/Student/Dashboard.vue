<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps<{
  stats?: {
    enrolledCount: number
    inProgressCount: number
    completedCount: number
    certificatesCount: number
    learningTime: string
    averageScore: string
  }
  continueCourse?: {
    title: string
    chapter: string
    teacher: string
    progress: number
    lastLesson: string
    timeLeft: string
    href: string
  }
  dbCourses?: Array<{
    id: number
    title: string
    teacher: string
    progress: number
    status: 'in_progress' | 'completed' | 'not_started'
    statusLabel: string
    badgeClass: string
    iconType: string
    iconColor: string
    href: string
  }>
  enrollments?: any[]
}>()

const page = usePage<any>()
const user = computed(() => page.props.auth?.user || {})

// 6 Top Stats Metrics with dedicated links to real modules
const statsCards = computed(() => [
  {
    title: 'Enrolled Courses',
    value: props.stats?.enrolledCount?.toString() || '4',
    subtitle: 'Total Courses',
    icon: '📚',
    iconBg: 'bg-purple-600/20 text-purple-600 dark:text-purple-400 border border-purple-500/30',
    href: '/student/my-courses/enrolled'
  },
  {
    title: 'In Progress',
    value: props.stats?.inProgressCount?.toString() || '2',
    subtitle: 'Active Courses',
    icon: '🎓',
    iconBg: 'bg-blue-600/20 text-blue-600 dark:text-blue-400 border border-blue-500/30',
    href: '/student/my-courses/enrolled'
  },
  {
    title: 'Completed',
    value: props.stats?.completedCount?.toString() || '1',
    subtitle: 'Finished Courses',
    icon: '✅',
    iconBg: 'bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30',
    href: '/student/my-courses/completed'
  },
  {
    title: 'Certificates',
    value: props.stats?.certificatesCount?.toString() || '1',
    subtitle: 'Earned',
    icon: '🏆',
    iconBg: 'bg-amber-600/20 text-amber-600 dark:text-amber-400 border border-amber-500/30',
    href: '/student/certificates/my-certificates'
  },
  {
    title: 'Study Time',
    value: props.stats?.learningTime || '28h 45m',
    subtitle: 'Total Hours',
    icon: '⏱',
    iconBg: 'bg-cyan-600/20 text-cyan-600 dark:text-cyan-400 border border-cyan-500/30',
    href: '/student/progress/learning-time'
  },
  {
    title: 'Quiz Average',
    value: props.stats?.averageScore || '78%',
    subtitle: 'Your Average',
    icon: '📊',
    iconBg: 'bg-indigo-600/20 text-indigo-600 dark:text-indigo-400 border border-indigo-500/30',
    href: '/student/quizzes/scores'
  }
])

// Featured Continue Learning Course
const continueCourseData = computed(() => {
  if (props.continueCourse) {
    return props.continueCourse
  }
  return {
    title: 'Web Development Fundamentals',
    chapter: 'Chapter 3 - JavaScript Functions',
    teacher: 'Mr. Sophea Chem',
    progress: 53,
    lastLesson: '3.2 JavaScript Functions',
    timeLeft: '18:20 left',
    href: '/student/my-courses/current'
  }
})

// Today's Goal (Interactive tasks with local persistence)
const todayGoal = ref({
  completedCount: 1,
  totalCount: 2,
  percentage: 50,
  items: [
    { id: 1, title: 'Chapter 3 - JavaScript Functions', completed: true, href: '/student/my-courses/current' },
    { id: 2, title: 'Practice 10 quiz questions', completed: false, href: '/student/quizzes/practice' }
  ]
})

const isAddGoalOpen = ref(false)
const newGoalTitle = ref('')

const toggleTask = (task: any) => {
  task.completed = !task.completed
  recalculateGoals()
}

const recalculateGoals = () => {
  const done = todayGoal.value.items.filter(i => i.completed).length
  todayGoal.value.completedCount = done
  todayGoal.value.totalCount = todayGoal.value.items.length || 1
  todayGoal.value.percentage = Math.round((done / todayGoal.value.totalCount) * 100)
}

const addNewGoal = () => {
  if (!newGoalTitle.value.trim()) return
  todayGoal.value.items.push({
    id: Date.now(),
    title: newGoalTitle.value.trim(),
    completed: false,
    href: '/student/my-courses/current'
  })
  newGoalTitle.value = ''
  isAddGoalOpen.value = false
  recalculateGoals()
}

// AI Recommended For You Widget Data
const aiRecommendations = ref([
  {
    id: 1,
    type: 'weak_topic',
    title: 'Review JavaScript Functions',
    badge: 'Score: 62%',
    desc: 'Based on your recent quiz errors in Chapter 3.2. Reinforce parameter scope before DOM topics.',
    actionLabel: 'Review Lesson',
    actionHref: '/student/my-courses/current',
    btnClass: 'bg-purple-600 hover:bg-purple-500 text-white'
  },
  {
    id: 2,
    type: 'practice_drill',
    title: 'Practice 5 AI Questions',
    badge: 'Focus: Parameters',
    desc: 'Customized drill to improve your function return statement speed and accuracy.',
    actionLabel: 'Practice with AI',
    actionHref: '/student/quizzes/practice',
    btnClass: 'bg-emerald-600 hover:bg-emerald-500 text-white'
  }
])

// My Courses Filter and List
const selectedCourseFilter = ref<'all' | 'in_progress' | 'completed' | 'not_started'>('all')

const fallbackCoursesList = [
  {
    id: 1,
    title: 'Web Development Fundamentals',
    teacher: 'Mr. Sophea Chem',
    progress: 53,
    status: 'in_progress',
    statusLabel: 'In Progress',
    badgeClass: 'bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-500/30',
    iconType: 'code',
    iconColor: 'from-blue-600 to-purple-600',
    href: '/student/courses/1/overview'
  },
  {
    id: 2,
    title: 'Database Systems',
    teacher: 'Mr. Long Dararith',
    progress: 35,
    status: 'in_progress',
    statusLabel: 'In Progress',
    badgeClass: 'bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-500/30',
    iconType: 'db',
    iconColor: 'from-purple-600 to-indigo-600',
    href: '/student/courses/2/overview'
  },
  {
    id: 3,
    title: 'Python Programming',
    teacher: 'Mr. Eng Thida',
    progress: 0,
    status: 'not_started',
    statusLabel: 'Not Started',
    badgeClass: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
    iconType: 'python',
    iconColor: 'from-amber-600 to-yellow-600',
    href: '/student/courses/3/overview'
  },
  {
    id: 4,
    title: 'UI/UX Design Basics',
    teacher: 'Ms. Nhean Sreymom',
    progress: 100,
    status: 'completed',
    statusLabel: 'Completed',
    badgeClass: 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
    iconType: 'design',
    iconColor: 'from-pink-600 to-purple-600',
    href: '/student/courses/4/overview'
  }
]

const coursesToDisplay = computed(() => {
  if (props.dbCourses && props.dbCourses.length > 0) {
    return props.dbCourses
  }
  return fallbackCoursesList
})

const filteredCourses = computed(() => {
  if (selectedCourseFilter.value === 'all') return coursesToDisplay.value
  return coursesToDisplay.value.filter(c => c.status === selectedCourseFilter.value)
})

// Learning Overview Chart Range Switcher
const selectedRange = ref<'week' | 'last_week' | 'month'>('week')
const chartDataByRange = {
  week: {
    total: '2h 30m',
    sub: 'Today vs average',
    pathArea: 'M 10 60 Q 40 40 70 55 T 130 25 T 190 40 L 190 75 L 10 75 Z',
    pathLine: 'M 10 60 Q 40 40 70 55 T 130 25 T 190 40',
    dotX: 130,
    dotY: 25,
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  },
  last_week: {
    total: '14h 10m',
    sub: 'Last week total',
    pathArea: 'M 10 50 Q 40 30 70 45 T 130 35 T 190 20 L 190 75 L 10 75 Z',
    pathLine: 'M 10 50 Q 40 30 70 45 T 130 35 T 190 20',
    dotX: 190,
    dotY: 20,
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  },
  month: {
    total: '48h 20m',
    sub: 'May 2025 cumulative',
    pathArea: 'M 10 65 Q 50 50 90 35 T 150 20 T 190 15 L 190 75 L 10 75 Z',
    pathLine: 'M 10 65 Q 50 50 90 35 T 150 20 T 190 15',
    dotX: 150,
    dotY: 20,
    labels: ['W1', 'W2', 'W3', 'W4', '', '', '']
  }
}

// Upcoming Schedule
const upcomingEvents = ref([
  {
    id: 1,
    icon: '📅',
    title: 'Quiz: Chapter 3 Quiz',
    course: 'Web Development Fundamentals',
    date: 'May 28, 2025',
    time: '10:00 AM',
    href: '/student/quizzes/practice'
  },
  {
    id: 2,
    icon: '📹',
    title: 'Live Class: JavaScript DOM',
    course: 'Web Development Fundamentals',
    date: 'May 30, 2025',
    time: '02:00 PM',
    href: '/student/calendar/live-class'
  },
  {
    id: 3,
    icon: '📄',
    title: 'Assignment: Relational ER Model',
    course: 'Database Systems',
    date: 'Jun 01, 2025',
    time: '11:59 PM',
    href: '/student/assignments'
  }
])

// AI Study Assistant Widget
const aiPromptInput = ref('')
const isAiTyping = ref(false)
const aiMessages = ref<Array<{ role: 'ai' | 'user'; text: string; time?: string }>>([
  {
    role: 'ai',
    text: 'Hi ' + (user.value?.name || 'Sok Pisey') + '! 👋 How can I help you with your learning today?',
    time: 'Just now'
  }
])

const handleSendAiPrompt = (promptText: string) => {
  aiMessages.value.push({ role: 'user', text: promptText, time: 'Now' })
  isAiTyping.value = true

  setTimeout(() => {
    isAiTyping.value = false
    const qLower = promptText.toLowerCase()
    if (qLower.includes('example') || qLower.includes('function')) {
      aiMessages.value.push({
        role: 'ai',
        text: 'Here is a clean JavaScript function example:\n```javascript\nfunction calculateTotal(price, taxRate = 0.1) {\n  return price + (price * taxRate);\n}\nconsole.log(calculateTotal(100)); // 110\n```',
        time: 'Now'
      })
    } else if (qLower.includes('what should i study') || qLower.includes('recommend') || qLower.includes('plan')) {
      aiMessages.value.push({
        role: 'ai',
        text: 'Based on your recent learning data:\n1. 🎯 **Finish Lesson 3.2** in Web Development (18m left).\n2. ⚠️ **Review Function Parameters** to raise your 62% quiz score.\n3. ⚡ **Practice 5 drill questions** before the May 28 Chapter Quiz!',
        time: 'Now'
      })
    } else if (qLower.includes('explain')) {
      aiMessages.value.push({
        role: 'ai',
        text: 'A JavaScript function is a reusable block of code designed to perform a specific task. It executes when called (invoked) and can return calculated output with the return statement.',
        time: 'Now'
      })
    } else if (qLower.includes('summarize')) {
      aiMessages.value.push({
        role: 'ai',
        text: 'Summary: Functions accept parameters, execute logic in their local block scope, and return computed results using the return keyword.',
        time: 'Now'
      })
    } else {
      aiMessages.value.push({
        role: 'ai',
        text: `Great question about "${promptText}"! To succeed: break down the problem into smaller steps, practice coding in the editor, and test your understanding with mini-quizzes. Let me know if you want a step-by-step example!`,
        time: 'Now'
      })
    }
  }, 400)
}

const sendUserCustomAi = () => {
  if (!aiPromptInput.value.trim()) return
  const text = aiPromptInput.value.trim()
  aiPromptInput.value = ''
  handleSendAiPrompt(text)
}

// Achievements Badges
const achievements = ref([
  { title: '7 Days Streak', icon: '🔥', color: 'from-orange-500 to-amber-500', href: '/student/certificates/achievements' },
  { title: 'Quick Learner', icon: '📗', color: 'from-emerald-500 to-teal-500', href: '/student/certificates/achievements' },
  { title: 'Quiz Master', icon: '🌟', color: 'from-blue-500 to-indigo-500', href: '/student/certificates/achievements' },
  { title: 'Early Bird', icon: '⚡', color: 'from-amber-500 to-yellow-500', href: '/student/certificates/achievements' },
  { title: 'Top Performer', icon: '🏆', color: 'from-yellow-500 to-amber-600', href: '/student/certificates/achievements' }
])

// Recent Activity
const recentActivities = ref([
  {
    id: 1,
    icon: '▶',
    iconBg: 'bg-purple-600/20 text-purple-600 dark:text-purple-400 border border-purple-500/30',
    title: 'Watched: 3.2 JavaScript Functions',
    course: 'Web Development Fundamentals',
    time: 'Today, 09:30 AM',
    href: '/student/my-courses/current'
  },
  {
    id: 2,
    icon: '✓',
    iconBg: 'bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30',
    title: 'Completed Quiz: Chapter 2 Quiz',
    course: 'Web Development Fundamentals',
    time: 'Yesterday, 04:15 PM',
    href: '/student/quizzes/scores'
  },
  {
    id: 3,
    icon: '📄',
    title: 'Downloaded: JavaScript Cheat Sheet',
    course: 'Web Development Fundamentals',
    time: 'May 26, 03:20 PM',
    href: '/student/learning-content/resources'
  }
])
</script>

<template>
  <StudentLayout
    title="Dashboard"
    :breadcrumbs="[
      { label: 'Dashboard' }
    ]"
  >
    <div class="space-y-6 pb-12">
      
      <!-- TOP GREETING & QUICK SHORTCUTS ROW -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 p-5 rounded-3xl shadow-xs dark:shadow-xl">
        <div class="space-y-1">
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <span>Welcome back, {{ user.name || 'Sok Pisey' }}!</span>
            <span class="animate-waving-hand inline-block">👋</span>
          </h1>
          <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">
            Continue where you left off or explore new courses today. You've got this! 💪
          </p>
        </div>

        <!-- Quick Shortcut Actions -->
        <div class="flex flex-wrap items-center gap-2">
          <Link
            href="/student/browse"
            class="px-3.5 py-2 rounded-xl bg-purple-50 dark:bg-purple-950/50 hover:bg-purple-100 dark:hover:bg-purple-900/60 border border-purple-200 dark:border-purple-800/60 text-purple-700 dark:text-purple-300 font-bold text-xs flex items-center gap-1.5 transition-colors shadow-xs"
          >
            <span>🔍</span>
            <span>Browse Courses</span>
          </Link>

          <Link
            href="/student/payments/my-payments"
            class="px-3.5 py-2 rounded-xl bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200 dark:border-blue-800/60 text-blue-700 dark:text-blue-300 font-bold text-xs flex items-center gap-1.5 transition-colors shadow-xs"
          >
            <span>💳</span>
            <span>Pay via ABA</span>
          </Link>

          <Link
            href="/student/notifications/announcements"
            class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs flex items-center gap-1.5 transition-colors shadow-xs"
          >
            <span>📢</span>
            <span>Announcements</span>
          </Link>
        </div>
      </div>

      <!-- 6 METRICS CARDS ROW (Fully clickable and connected) -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <Link
          v-for="stat in statsCards"
          :key="stat.title"
          :href="stat.href"
          class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 hover:border-purple-500/40 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center gap-3.5 transition-all duration-200 hover:-translate-y-1 group"
        >
          <div :class="[stat.iconBg, 'w-10 h-10 rounded-xl flex items-center justify-center text-base shrink-0 shadow-sm group-hover:scale-105 transition-transform']">
            {{ stat.icon }}
          </div>
          <div class="min-w-0">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium truncate">{{ stat.title }}</p>
            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate leading-tight">{{ stat.value }}</h3>
            <p class="text-[9px] text-slate-400 dark:text-slate-500 truncate">{{ stat.subtitle }}</p>
          </div>
        </Link>
      </div>

      <!-- MAIN 2-COLUMN LAYOUT (Left Main 8 cols, Right Sidebar 4 cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8 COLUMNS -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- FEATURED CONTINUE LEARNING CARD -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h2 class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Continue Learning</h2>
              <Link href="/student/my-courses/enrolled" class="text-xs text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 font-semibold">
                View All
              </Link>
            </div>

            <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-xl flex flex-col md:flex-row items-center gap-5 relative overflow-hidden">
              
              <!-- 3D / Mock cover illustration -->
              <div class="relative w-full md:w-36 h-28 rounded-2xl bg-gradient-to-br from-indigo-50 via-slate-100 to-purple-100 dark:from-[#0c1322] dark:via-[#0f172a] dark:to-[#1e1b4b] border border-slate-200 dark:border-slate-800 flex items-center justify-center shrink-0 shadow-md">
                <div class="text-center space-y-1">
                  <div class="inline-flex items-center justify-center px-2 py-0.5 rounded-lg bg-amber-400 text-slate-950 font-black text-xs shadow-sm">
                    JS
                  </div>
                  <p class="text-[10px] font-mono text-purple-600 dark:text-purple-400 font-bold">{ functions }</p>
                </div>
              </div>

              <!-- Course Info and Progress -->
              <div class="flex-1 w-full space-y-3">
                <div class="space-y-1">
                  <span class="px-2.5 py-0.5 rounded-md bg-purple-500/10 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 text-[10px] font-bold border border-purple-500/20 dark:border-purple-500/30">
                    Teacher: {{ continueCourseData.teacher }}
                  </span>
                  <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                    {{ continueCourseData.title }}
                  </h3>
                  <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">
                    {{ continueCourseData.chapter }}
                  </p>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1.5">
                  <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full" :style="{ width: continueCourseData.progress + '%' }"></div>
                  </div>
                  <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Last lesson: {{ continueCourseData.lastLesson }}</span>
                    <span class="font-mono flex items-center gap-1">⏱ {{ continueCourseData.timeLeft }}</span>
                  </div>
                </div>
              </div>

              <!-- CTA Button -->
              <div class="w-full md:w-auto shrink-0">
                <Link
                  :href="continueCourseData.href"
                  class="w-full md:w-auto px-5 py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-2 hover:scale-105 active:scale-95 transition-all text-center cursor-pointer"
                >
                  <span>Continue Learning</span>
                  <span>→</span>
                </Link>
              </div>

            </div>
          </div>

          <!-- MY COURSES SECTION WITH FILTER TABS -->
          <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <h2 class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">My Courses</h2>
                
                <!-- Filter Pills -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-950/80 p-1 rounded-xl border border-slate-200 dark:border-slate-800">
                  <button
                    @click="selectedCourseFilter = 'all'"
                    :class="[selectedCourseFilter === 'all' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200', 'px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer']"
                  >
                    All Courses
                  </button>
                  <button
                    @click="selectedCourseFilter = 'in_progress'"
                    :class="[selectedCourseFilter === 'in_progress' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200', 'px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer']"
                  >
                    In Progress
                  </button>
                  <button
                    @click="selectedCourseFilter = 'completed'"
                    :class="[selectedCourseFilter === 'completed' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200', 'px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer']"
                  >
                    Completed
                  </button>
                  <button
                    @click="selectedCourseFilter = 'not_started'"
                    :class="[selectedCourseFilter === 'not_started' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200', 'px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer']"
                  >
                    Not Started
                  </button>
                </div>
              </div>

              <Link href="/student/my-courses/enrolled" class="text-xs text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 font-semibold self-start sm:self-auto">
                View All ({{ coursesToDisplay.length }})
              </Link>
            </div>

            <!-- Courses Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <Link
                v-for="c in filteredCourses"
                :key="c.id"
                :href="c.href"
                class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 hover:border-purple-500/40 rounded-2xl p-4 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-3 transition-all duration-200 hover:-translate-y-1 group"
              >
                <!-- Illustration Box with Badge -->
                <div class="relative w-full h-24 rounded-xl bg-slate-100 dark:bg-slate-950 flex items-center justify-center overflow-hidden">
                  <div :class="['w-10 h-10 rounded-xl bg-gradient-to-tr', c.iconColor || 'from-purple-600 to-indigo-600', 'flex items-center justify-center text-white text-lg font-mono font-bold shadow-md']">
                    <span v-if="c.iconType === 'code' || c.iconType?.includes('web')">&lt;/&gt;</span>
                    <span v-else-if="c.iconType === 'db' || c.iconType?.includes('database')">🗄️</span>
                    <span v-else-if="c.iconType === 'python' || c.iconType?.includes('program')">🐍</span>
                    <span v-else>🎨</span>
                  </div>

                  <span :class="[c.badgeClass, 'absolute top-2 right-2 px-2 py-0.5 rounded-md text-[9px] font-bold border']">
                    {{ c.statusLabel }}
                  </span>
                </div>

                <!-- Course Title & Instructor -->
                <div class="space-y-1">
                  <h4 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors line-clamp-1">
                    {{ c.title }}
                  </h4>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ c.teacher }}</p>
                </div>

                <!-- Progress -->
                <div class="space-y-1 pt-1">
                  <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                    <div
                      :class="c.progress === 100 ? 'bg-emerald-500' : 'bg-purple-500'"
                      class="h-full rounded-full transition-all"
                      :style="{ width: c.progress + '%' }"
                    ></div>
                  </div>
                  <div class="flex items-center justify-between text-[10px]">
                    <span :class="c.progress === 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400'" class="font-bold font-mono">{{ c.progress }}%</span>
                    <span class="text-slate-400 dark:text-slate-500 text-[9px]">Open Overview →</span>
                  </div>
                </div>
              </Link>
            </div>
          </div>

          <!-- ANALYTICS CHARTS ROW (Learning Overview, Study Time Donut, Quiz Performance Gauge) -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- Learning Overview Line Chart Card with Range Switcher -->
            <div
              class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4 block transition-all"
            >
              <div class="flex items-center justify-between">
                <Link href="/student/progress/learning-time" class="text-xs font-bold text-slate-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-300 uppercase tracking-wider flex items-center gap-1">
                  <span>Learning Overview</span>
                  <span class="text-[10px]">↗</span>
                </Link>

                <!-- Range Selector -->
                <div class="flex items-center gap-1 text-[10px] bg-slate-100 dark:bg-slate-950 p-0.5 rounded-lg border border-slate-200 dark:border-slate-800">
                  <button
                    @click="selectedRange = 'week'"
                    :class="selectedRange === 'week' ? 'bg-purple-600 text-white font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2 py-0.5 rounded-md cursor-pointer transition-colors"
                  >
                    Week
                  </button>
                  <button
                    @click="selectedRange = 'last_week'"
                    :class="selectedRange === 'last_week' ? 'bg-purple-600 text-white font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2 py-0.5 rounded-md cursor-pointer transition-colors"
                  >
                    Last
                  </button>
                  <button
                    @click="selectedRange = 'month'"
                    :class="selectedRange === 'month' ? 'bg-purple-600 text-white font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                    class="px-2 py-0.5 rounded-md cursor-pointer transition-colors"
                  >
                    Month
                  </button>
                </div>
              </div>

              <!-- SVG Line Chart Mockup -->
              <div class="relative pt-2">
                <div class="absolute top-2 left-1/2 -translate-x-1/2 px-2 py-1 rounded-lg bg-purple-600 text-white text-[10px] font-bold shadow-md">
                  {{ chartDataByRange[selectedRange].total }}
                </div>

                <svg viewBox="0 0 200 80" class="w-full h-20 overflow-visible">
                  <defs>
                    <linearGradient id="purpleGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.4" />
                      <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0" />
                    </linearGradient>
                  </defs>
                  <path
                    :d="chartDataByRange[selectedRange].pathArea"
                    fill="url(#purpleGrad)"
                  />
                  <path
                    :d="chartDataByRange[selectedRange].pathLine"
                    fill="none"
                    stroke="#8b5cf6"
                    stroke-width="2.5"
                    stroke-linecap="round"
                  />
                  <circle :cx="chartDataByRange[selectedRange].dotX" :cy="chartDataByRange[selectedRange].dotY" r="4" fill="#a855f7" stroke="#ffffff" stroke-width="2" />
                </svg>

                <div class="flex justify-between text-[9px] text-slate-400 dark:text-slate-500 font-mono pt-1">
                  <span v-for="(lbl, idx) in chartDataByRange[selectedRange].labels" :key="idx">{{ lbl }}</span>
                </div>
              </div>
            </div>

            <!-- Study Time Donut Ring Chart Card -->
            <Link
              href="/student/progress/learning-time"
              class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 hover:border-purple-500/40 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4 block transition-all hover:-translate-y-1"
            >
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1">
                  <span>Study Time</span>
                  <span class="text-[10px]">↗</span>
                </h3>
                <span class="text-[10px] text-purple-600 dark:text-purple-400 font-semibold">Breakdown</span>
              </div>

              <div class="flex items-center justify-center gap-4">
                <!-- SVG Circular Donut -->
                <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                  <svg viewBox="0 0 36 36" class="w-24 h-24 -rotate-90">
                    <path
                      class="text-slate-200 dark:text-slate-800"
                      stroke-width="4"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      class="text-blue-500"
                      stroke-dasharray="55, 100"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      class="text-purple-500"
                      stroke-dasharray="30, 100"
                      stroke-dashoffset="-55"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-[11px] font-black text-slate-900 dark:text-white leading-none">{{ props.stats?.learningTime || '28h 45m' }}</span>
                    <span class="text-[8px] text-slate-500 dark:text-slate-400">Total</span>
                  </div>
                </div>

                <!-- Legend -->
                <div class="space-y-1.5 text-[10px] text-slate-600 dark:text-slate-300">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span>Lessons: 16h 30m</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    <span>Practice: 8h 15m</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-cyan-500 dark:bg-cyan-400"></span>
                    <span>Quiz: 4h 00m</span>
                  </div>
                </div>
              </div>
            </Link>

            <!-- Quiz Performance Radial Gauge Card -->
            <Link
              href="/student/quizzes/scores"
              class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 hover:border-purple-500/40 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4 block transition-all hover:-translate-y-1"
            >
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1">
                  <span>Quiz Performance</span>
                  <span class="text-[10px]">↗</span>
                </h3>
                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Passing</span>
              </div>

              <div class="flex items-center justify-center gap-4">
                <!-- Gauge Circle -->
                <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                  <svg viewBox="0 0 36 36" class="w-24 h-24 -rotate-90">
                    <path
                      class="text-rose-500/20 dark:text-rose-500/40"
                      stroke-width="4"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      class="text-emerald-500 dark:text-emerald-400"
                      stroke-dasharray="78, 100"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-sm font-black text-slate-900 dark:text-white leading-none">{{ props.stats?.averageScore || '78%' }}</span>
                    <span class="text-[8px] text-slate-500 dark:text-slate-400">Average</span>
                  </div>
                </div>

                <!-- Legend -->
                <div class="space-y-1.5 text-[10px] text-slate-600 dark:text-slate-300">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                    <span>Correct: 78%</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>Incorrect: 22%</span>
                  </div>
                </div>
              </div>
            </Link>

          </div>

          <!-- RECENT ACTIVITY LIST -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h2 class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Recent Activity</h2>
              <Link href="/student/progress/overview" class="text-xs text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 font-semibold">
                View All
              </Link>
            </div>

            <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
              <Link
                v-for="act in recentActivities"
                :key="act.id"
                :href="act.href"
                class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800/80 hover:border-purple-500/30 text-xs transition-all hover:bg-slate-100 dark:hover:bg-slate-950"
              >
                <div class="flex items-center gap-3">
                  <div :class="[act.iconBg, 'w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold shrink-0']">
                    {{ act.icon }}
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-900 dark:text-white">{{ act.title }}</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ act.course }}</p>
                  </div>
                </div>

                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono shrink-0">{{ act.time }}</span>
              </Link>
            </div>
          </div>

        </div>

        <!-- RIGHT 4 COLUMNS: Today's Goal, AI Recommended For You, Upcoming, AI Assistant, Achievements -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- TODAY'S GOAL CARD -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Today's Goal</h3>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold border border-emerald-500/20 dark:border-emerald-500/30">
                  {{ todayGoal.completedCount }} / {{ todayGoal.totalCount }} Done
                </span>
                <button
                  @click="isAddGoalOpen = !isAddGoalOpen"
                  class="w-6 h-6 rounded-lg bg-purple-600/10 hover:bg-purple-600/20 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xs font-bold cursor-pointer"
                  title="Add custom task"
                >
                  +
                </button>
              </div>
            </div>

            <!-- Add Task Form Modal / Inline -->
            <div v-if="isAddGoalOpen" class="p-2.5 rounded-2xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/50 space-y-2">
              <input
                v-model="newGoalTitle"
                type="text"
                placeholder="Enter new study goal..."
                class="w-full px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-purple-200 dark:border-purple-700 text-xs text-slate-900 dark:text-white"
                @keyup.enter="addNewGoal"
              />
              <div class="flex justify-end gap-2 text-xs">
                <button
                  @click="isAddGoalOpen = false"
                  class="px-2.5 py-1 rounded-lg text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 text-[11px]"
                >
                  Cancel
                </button>
                <button
                  @click="addNewGoal"
                  class="px-3 py-1 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-bold text-[11px]"
                >
                  Add Goal
                </button>
              </div>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300 font-medium">Keep your study momentum going!</p>

            <div class="space-y-1.5">
              <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full transition-all duration-300" :style="{ width: todayGoal.percentage + '%' }"></div>
              </div>
              <p class="text-[10px] text-right text-slate-500 dark:text-slate-400">{{ todayGoal.percentage }}% completed</p>
            </div>

            <!-- Task Checklist -->
            <div class="space-y-2 pt-1">
              <div
                v-for="item in todayGoal.items"
                :key="item.id"
                class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800/60 text-xs"
              >
                <button
                  @click="toggleTask(item)"
                  type="button"
                  class="flex items-center gap-2.5 text-left flex-1 cursor-pointer"
                >
                  <span v-if="item.completed" class="w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">✓</span>
                  <span v-else class="w-4 h-4 rounded-full border border-slate-300 dark:border-slate-600 flex items-center justify-center text-[9px]"></span>
                  <span :class="[item.completed ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-200 font-medium']">{{ item.title }}</span>
                </button>
                
                <Link :href="item.href" class="text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 text-[11px] font-bold ml-2">
                  Open →
                </Link>
              </div>
            </div>
          </div>

          <!-- AI RECOMMENDED FOR YOU CARD -->
          <div class="bg-gradient-to-br from-indigo-50/80 via-purple-50/60 to-white dark:from-indigo-950/60 dark:via-purple-950/40 dark:to-slate-900 border border-purple-200 dark:border-purple-500/30 rounded-3xl p-5 shadow-sm dark:shadow-2xl space-y-3.5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-base">🤖</span>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">AI Recommended For You</h3>
              </div>
              <Link href="/student/ai-path/recommended" class="text-[10px] text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 font-semibold">
                Roadmap
              </Link>
            </div>

            <p class="text-[11px] text-purple-700 dark:text-purple-300 font-medium">Based on your recent quiz scores & learning pace:</p>

            <div class="space-y-2.5">
              <div
                v-for="rec in aiRecommendations"
                :key="rec.id"
                class="p-3 rounded-2xl bg-white dark:bg-slate-950/80 border border-purple-100 dark:border-purple-500/20 space-y-2 shadow-xs dark:shadow-none"
              >
                <div class="flex items-center justify-between">
                  <h4 class="font-bold text-slate-900 dark:text-white text-xs">{{ rec.title }}</h4>
                  <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-purple-500/10 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-500/20 dark:border-purple-500/30">
                    {{ rec.badge }}
                  </span>
                </div>
                <p class="text-[10px] text-slate-600 dark:text-slate-300 leading-relaxed">{{ rec.desc }}</p>
                
                <div class="pt-1">
                  <Link
                    :href="rec.actionHref"
                    :class="[rec.btnClass, 'inline-block px-3 py-1 rounded-xl text-[10px] font-bold shadow-sm transition-transform hover:scale-105 active:scale-95']"
                  >
                    {{ rec.actionLabel }} →
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <!-- UPCOMING SCHEDULE CARD -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Upcoming</h3>
              <Link href="/student/calendar" class="text-[11px] text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 font-semibold">
                View Calendar
              </Link>
            </div>

            <div class="space-y-3">
              <Link
                v-for="item in upcomingEvents"
                :key="item.id"
                :href="item.href"
                class="flex items-start justify-between gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800/60 hover:border-purple-500/30 text-xs transition-colors block hover:bg-slate-100 dark:hover:bg-slate-950"
              >
                <div class="flex items-start gap-2.5">
                  <span class="text-sm mt-0.5">{{ item.icon }}</span>
                  <div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-[11px]">{{ item.title }}</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ item.course }}</p>
                  </div>
                </div>

                <div class="text-right text-[10px] text-slate-500 dark:text-slate-400 shrink-0 font-mono">
                  <p class="font-bold text-slate-700 dark:text-slate-300">{{ item.date }}</p>
                  <p>{{ item.time }}</p>
                </div>
              </Link>
            </div>
          </div>

          <!-- AI STUDY ASSISTANT WIDGET -->
          <div class="bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/60 dark:from-[#111827] dark:via-slate-900 dark:to-[#1e1b4b]/60 border border-purple-200 dark:border-purple-500/30 rounded-3xl p-5 shadow-sm dark:shadow-2xl space-y-3.5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-purple-600 text-white flex items-center justify-center text-xs shadow-sm">
                  🤖
                </div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">AI Study Assistant</h3>
              </div>
              <Link
                href="/student/ai-tutor/chat"
                class="px-2 py-0.5 rounded-full bg-purple-500/10 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 text-[10px] font-bold border border-purple-500/20 dark:border-purple-500/30 hover:bg-purple-600 hover:text-white transition-colors"
              >
                Full Chat ↗
              </Link>
            </div>

            <!-- Messages Stream -->
            <div class="space-y-2 max-h-44 overflow-y-auto custom-scrollbar pr-1">
              <div
                v-for="(msg, idx) in aiMessages"
                :key="idx"
                :class="[
                  msg.role === 'user' ? 'bg-purple-600/15 dark:bg-purple-600/30 border border-purple-500/30 dark:border-purple-500/40 text-purple-950 dark:text-purple-100 ml-4' : 'bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-slate-800 dark:text-slate-200 mr-2',
                  'p-2.5 rounded-2xl text-[11px] space-y-1'
                ]"
              >
                <p class="whitespace-pre-wrap leading-relaxed">{{ msg.text }}</p>
              </div>

              <div v-if="isAiTyping" class="p-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800/80 text-slate-500 text-[10px] italic flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                <span>AI Tutor is thinking...</span>
              </div>
            </div>

            <!-- Quick Action Prompt Chips -->
            <div class="grid grid-cols-2 gap-1.5 pt-1">
              <button
                @click="handleSendAiPrompt('Explain this lesson')"
                type="button"
                class="p-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-medium border border-slate-200 dark:border-slate-700 text-center transition-colors cursor-pointer truncate shadow-xs"
              >
                Explain this lesson
              </button>
              <button
                @click="handleSendAiPrompt('Give me an example')"
                type="button"
                class="p-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-medium border border-slate-200 dark:border-slate-700 text-center transition-colors cursor-pointer truncate shadow-xs"
              >
                Give me an example
              </button>
              <button
                @click="handleSendAiPrompt('Summarize this topic')"
                type="button"
                class="p-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-medium border border-slate-200 dark:border-slate-700 text-center transition-colors cursor-pointer truncate shadow-xs"
              >
                Summarize this topic
              </button>
              <button
                @click="handleSendAiPrompt('What should I study today?')"
                type="button"
                class="p-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-medium border border-slate-200 dark:border-slate-700 text-center transition-colors cursor-pointer truncate shadow-xs"
              >
                What should I study?
              </button>
            </div>

            <!-- Input Form -->
            <form @submit.prevent="sendUserCustomAi" class="relative flex items-center pt-1">
              <input
                v-model="aiPromptInput"
                type="text"
                placeholder="Ask your AI academic tutor..."
                class="w-full pl-3 pr-8 py-2 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-purple-500 shadow-inner"
              />
              <button
                type="submit"
                class="absolute right-1.5 p-1 rounded-lg bg-purple-600 hover:bg-purple-500 text-white transition-all cursor-pointer shadow-xs"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </form>
          </div>

          <!-- ACHIEVEMENTS BADGES CARD -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Achievements</h3>
              <Link href="/student/certificates/achievements" class="text-[11px] text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 font-semibold">
                View All
              </Link>
            </div>

            <div class="flex items-center justify-between gap-1 pt-1">
              <Link
                v-for="ach in achievements"
                :key="ach.title"
                :href="ach.href"
                class="flex flex-col items-center gap-1 group text-center"
              >
                <div :class="['w-9 h-9 rounded-xl bg-gradient-to-tr', ach.color, 'flex items-center justify-center text-sm shadow-md group-hover:scale-110 transition-transform']">
                  {{ ach.icon }}
                </div>
                <span class="text-[8px] text-slate-500 dark:text-slate-400 leading-tight w-12 truncate">{{ ach.title }}</span>
              </Link>
            </div>
          </div>

        </div>

      </div>

    </div>
  </StudentLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 4px;
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
}
</style>
