<script setup lang="ts">
import { ref, computed } from 'vue'
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

// 6 Top Stats Metrics with real Lucide SVG icons
const statsCards = computed(() => [
  {
    type: 'enrolled',
    title: 'Enrolled Courses',
    value: props.stats?.enrolledCount?.toString() || '4',
    subtitle: 'Total Courses',
    iconBg: 'bg-purple-600/20 text-purple-600 dark:text-purple-400 border border-purple-500/30',
    href: '/student/my-courses/enrolled'
  },
  {
    type: 'in_progress',
    title: 'In Progress',
    value: props.stats?.inProgressCount?.toString() || '2',
    subtitle: 'Active Courses',
    iconBg: 'bg-blue-600/20 text-blue-600 dark:text-blue-400 border border-blue-500/30',
    href: '/student/my-courses/enrolled'
  },
  {
    type: 'completed',
    title: 'Completed',
    value: props.stats?.completedCount?.toString() || '1',
    subtitle: 'Finished Courses',
    iconBg: 'bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30',
    href: '/student/my-courses/completed'
  },
  {
    type: 'certificates',
    title: 'Certificates',
    value: props.stats?.certificatesCount?.toString() || '1',
    subtitle: 'Earned',
    iconBg: 'bg-amber-600/20 text-amber-600 dark:text-amber-400 border border-amber-500/30',
    href: '/student/certificates/my-certificates'
  },
  {
    type: 'study_time',
    title: 'Study Time',
    value: props.stats?.learningTime || '28h 45m',
    subtitle: 'Total Hours',
    iconBg: 'bg-cyan-600/20 text-cyan-600 dark:text-cyan-400 border border-cyan-500/30',
    href: '/student/progress/learning-time'
  },
  {
    type: 'quiz_avg',
    title: 'Quiz Average',
    value: props.stats?.averageScore || '78%',
    subtitle: 'Your Average',
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

// Upcoming Schedule with real SVG icon type
const upcomingEvents = ref([
  {
    id: 1,
    type: 'quiz',
    title: 'Quiz: Chapter 3 Quiz',
    course: 'Web Development Fundamentals',
    date: 'May 28, 2025',
    time: '10:00 AM',
    href: '/student/quizzes/practice'
  },
  {
    id: 2,
    type: 'live',
    title: 'Live Class: JavaScript DOM',
    course: 'Web Development Fundamentals',
    date: 'May 30, 2025',
    time: '02:00 PM',
    href: '/student/calendar/live-class'
  },
  {
    id: 3,
    type: 'assignment',
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
    text: 'Hi ' + (user.value?.name || 'Sok Pisey') + '! How can I assist with your coding & course questions today?',
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
        text: 'Based on your recent learning analytics:\n1. **Finish Lesson 3.2** in Web Development (18m left).\n2. **Review Function Parameters** to raise your 62% quiz score.\n3. **Practice 5 drill questions** before the May 28 Chapter Quiz!',
        time: 'Now'
      })
    } else if (qLower.includes('explain')) {
      aiMessages.value.push({
        role: 'ai',
        text: 'A JavaScript function is a reusable block of code designed to perform a specific task. It executes when invoked and returns computed output with the return keyword.',
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
        text: `Great question about "${promptText}"! To master this: break down the problem, practice coding in the editor, and test your understanding with mini-quizzes. Let me know if you want a detailed code snippet!`,
        time: 'Now'
      })
    }
  }, 350)
}

const sendUserCustomAi = () => {
  if (!aiPromptInput.value.trim()) return
  const text = aiPromptInput.value.trim()
  aiPromptInput.value = ''
  handleSendAiPrompt(text)
}

// Achievements Badges with SVG type
const achievements = ref([
  { title: '7 Days Streak', type: 'flame', color: 'from-orange-500 to-amber-500', href: '/student/certificates/achievements' },
  { title: 'Quick Learner', type: 'book', color: 'from-emerald-500 to-teal-500', href: '/student/certificates/achievements' },
  { title: 'Quiz Master', type: 'star', color: 'from-blue-500 to-indigo-500', href: '/student/certificates/achievements' },
  { title: 'Early Bird', type: 'bolt', color: 'from-amber-500 to-yellow-500', href: '/student/certificates/achievements' },
  { title: 'Top Performer', type: 'trophy', color: 'from-yellow-500 to-amber-600', href: '/student/certificates/achievements' }
])

// Recent Activity with SVG type
const recentActivities = ref([
  {
    id: 1,
    type: 'play',
    iconBg: 'bg-purple-600/20 text-purple-600 dark:text-purple-400 border border-purple-500/30',
    title: 'Watched: 3.2 JavaScript Functions',
    course: 'Web Development Fundamentals',
    time: 'Today, 09:30 AM',
    href: '/student/my-courses/current'
  },
  {
    id: 2,
    type: 'check',
    iconBg: 'bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30',
    title: 'Completed Quiz: Chapter 2 Quiz',
    course: 'Web Development Fundamentals',
    time: 'Yesterday, 04:15 PM',
    href: '/student/quizzes/scores'
  },
  {
    id: 3,
    type: 'doc',
    iconBg: 'bg-amber-600/20 text-amber-600 dark:text-amber-400 border border-amber-500/30',
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
            <span class="inline-block animate-pulse text-indigo-500">✨</span>
          </h1>
          <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">
            Continue where you left off or explore new courses today. Keep moving forward!
          </p>
        </div>

        <!-- Quick Shortcut Actions with Real SVGs -->
        <div class="flex flex-wrap items-center gap-2">
          <Link
            href="/student/browse"
            class="px-3.5 py-2 rounded-xl bg-purple-50 dark:bg-purple-950/50 hover:bg-purple-100 dark:hover:bg-purple-900/60 border border-purple-200 dark:border-purple-800/60 text-purple-700 dark:text-purple-300 font-bold text-xs flex items-center gap-2 transition-colors shadow-xs"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <span>Browse Courses</span>
          </Link>

          <Link
            href="/student/payments/my-payments"
            class="px-3.5 py-2 rounded-xl bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 dark:hover:bg-blue-900/60 border border-blue-200 dark:border-blue-800/60 text-blue-700 dark:text-blue-300 font-bold text-xs flex items-center gap-2 transition-colors shadow-xs"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
            </svg>
            <span>Pay via ABA</span>
          </Link>

          <Link
            href="/student/notifications/announcements"
            class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs flex items-center gap-2 transition-colors shadow-xs"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            <span>Announcements</span>
          </Link>
        </div>
      </div>

      <!-- 6 METRICS CARDS ROW (Real SVG Icons) -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <Link
          v-for="stat in statsCards"
          :key="stat.title"
          :href="stat.href"
          class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 hover:border-purple-500/40 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center gap-3.5 transition-all duration-200 hover:-translate-y-1 group"
        >
          <div :class="[stat.iconBg, 'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-sm group-hover:scale-105 transition-transform']">
            <!-- Book SVG (Enrolled) -->
            <svg v-if="stat.type === 'enrolled'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <!-- Graduation Cap SVG (In Progress) -->
            <svg v-else-if="stat.type === 'in_progress'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
            </svg>
            <!-- Check Circle SVG (Completed) -->
            <svg v-else-if="stat.type === 'completed'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <!-- Trophy SVG (Certificates) -->
            <svg v-else-if="stat.type === 'certificates'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a3 3 0 002.828-2.002l1.636-4.908A1.125 1.125 0 0017.896 6H6.104a1.125 1.125 0 00-1.068 1.465l1.636 4.908a3 3 0 002.828 2.002z" />
            </svg>
            <!-- Clock SVG (Study Time) -->
            <svg v-else-if="stat.type === 'study_time'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <!-- Bar Chart SVG (Quiz Average) -->
            <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
          </div>
          <div class="min-w-0">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium truncate">{{ stat.title }}</p>
            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate leading-tight">{{ stat.value }}</h3>
            <p class="text-[9px] text-slate-400 dark:text-slate-500 truncate">{{ stat.subtitle }}</p>
          </div>
        </Link>
      </div>

      <!-- MAIN 2-COLUMN LAYOUT -->
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
                    <span class="font-mono flex items-center gap-1">
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      {{ continueCourseData.timeLeft }}
                    </span>
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
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
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
                    <!-- Code </> SVG -->
                    <svg v-if="c.iconType === 'code' || c.iconType?.includes('web')" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
                    </svg>
                    <!-- Database SVG -->
                    <svg v-else-if="c.iconType === 'db' || c.iconType?.includes('database')" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                    <!-- Python / Algorithm SVG -->
                    <svg v-else-if="c.iconType === 'python' || c.iconType?.includes('program')" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    <!-- Design / Figma Palette SVG -->
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42" />
                    </svg>
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
                    <span class="text-slate-400 dark:text-slate-500 text-[9px] flex items-center gap-0.5">
                      <span>Overview</span>
                      <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                  </div>
                </div>
              </Link>
            </div>
          </div>

          <!-- ANALYTICS CHARTS ROW -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- Learning Overview Line Chart Card with Range Switcher -->
            <div
              class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4 block transition-all"
            >
              <div class="flex items-center justify-between">
                <Link href="/student/progress/learning-time" class="text-xs font-bold text-slate-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-300 uppercase tracking-wider flex items-center gap-1">
                  <span>Learning Overview</span>
                  <svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
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

              <!-- SVG Line Chart -->
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
                  <svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
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
                  <svg class="w-3 h-3 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
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
                  <div :class="[act.iconBg, 'w-8 h-8 rounded-xl flex items-center justify-center shrink-0']">
                    <!-- Play SVG -->
                    <svg v-if="act.type === 'play'" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <!-- Checkmark SVG -->
                    <svg v-else-if="act.type === 'check'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <!-- Document SVG -->
                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
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

        <!-- RIGHT 4 COLUMNS -->
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
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </button>
              </div>
            </div>

            <!-- Add Task Form -->
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
                  <span v-if="item.completed" class="w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">
                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                  </span>
                  <span v-else class="w-4 h-4 rounded-full border border-slate-300 dark:border-slate-600 flex items-center justify-center text-[9px]"></span>
                  <span :class="[item.completed ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-200 font-medium']">{{ item.title }}</span>
                </button>
                
                <Link :href="item.href" class="text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 text-[11px] font-bold ml-2 flex items-center gap-0.5">
                  <span>Open</span>
                  <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </Link>
              </div>
            </div>
          </div>

          <!-- AI RECOMMENDED FOR YOU CARD -->
          <div class="bg-gradient-to-br from-indigo-50/80 via-purple-50/60 to-white dark:from-indigo-950/60 dark:via-purple-950/40 dark:to-slate-900 border border-purple-200 dark:border-purple-500/30 rounded-3xl p-5 shadow-sm dark:shadow-2xl space-y-3.5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center shadow-sm">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                </div>
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
                    :class="[rec.btnClass, 'inline-flex items-center gap-1 px-3 py-1 rounded-xl text-[10px] font-bold shadow-sm transition-transform hover:scale-105 active:scale-95']"
                  >
                    <span>{{ rec.actionLabel }}</span>
                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
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
                  <div class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-950/50 border border-purple-200 dark:border-purple-800 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 mt-0.5">
                    <!-- Calendar SVG -->
                    <svg v-if="item.type === 'quiz'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    <!-- Video Camera SVG -->
                    <svg v-else-if="item.type === 'live'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                    <!-- Document SVG -->
                    <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                  </div>
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
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z"/></svg>
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

          <!-- ACHIEVEMENTS BADGES CARD with Real SVGs -->
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
                <div :class="['w-9 h-9 rounded-xl bg-gradient-to-tr', ach.color, 'flex items-center justify-center text-white text-sm shadow-md group-hover:scale-110 transition-transform']">
                  <!-- Flame / Streak SVG -->
                  <svg v-if="ach.type === 'flame'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"/></svg>
                  <!-- Book SVG -->
                  <svg v-else-if="ach.type === 'book'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                  <!-- Star SVG -->
                  <svg v-else-if="ach.type === 'star'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                  <!-- Lightning Bolt SVG -->
                  <svg v-else-if="ach.type === 'bolt'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                  <!-- Trophy SVG -->
                  <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a3 3 0 002.828-2.002l1.636-4.908A1.125 1.125 0 0017.896 6H6.104a1.125 1.125 0 00-1.068 1.465l1.636 4.908a3 3 0 002.828 2.002z"/></svg>
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
