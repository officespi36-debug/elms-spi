<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const page = usePage<any>()
const user = computed(() => page.props.auth?.user || {})

// 6 Top Stats Metrics with dedicated links to real modules
const stats = ref([
  {
    title: 'Enrolled Courses',
    value: '4',
    subtitle: 'Total Courses',
    icon: '📚',
    iconBg: 'bg-purple-600/20 text-purple-400 border border-purple-500/30',
    href: '/student/my-courses/enrolled'
  },
  {
    title: 'In Progress',
    value: '2',
    subtitle: 'Active Courses',
    icon: '🎓',
    iconBg: 'bg-blue-600/20 text-blue-400 border border-blue-500/30',
    href: '/student/my-courses/enrolled'
  },
  {
    title: 'Completed',
    value: '1',
    subtitle: 'Finished Courses',
    icon: '✅',
    iconBg: 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30',
    href: '/student/my-courses/completed'
  },
  {
    title: 'Certificates',
    value: '1',
    subtitle: 'Earned',
    icon: '🏆',
    iconBg: 'bg-amber-600/20 text-amber-400 border border-amber-500/30',
    href: '/student/certificates/my-certificates'
  },
  {
    title: 'Study Time',
    value: '28h 45m',
    subtitle: 'Total Hours',
    icon: '⏱',
    iconBg: 'bg-cyan-600/20 text-cyan-400 border border-cyan-500/30',
    href: '/student/progress'
  },
  {
    title: 'Quiz Average',
    value: '78%',
    subtitle: 'Your Average',
    icon: '📊',
    iconBg: 'bg-indigo-600/20 text-indigo-400 border border-indigo-500/30',
    href: '/student/quizzes/scores'
  }
])

// Featured Continue Learning Course
const continueCourse = ref({
  title: 'Web Development Fundamentals',
  chapter: 'Chapter 3 - JavaScript Functions',
  teacher: 'Mr. Sophea',
  progress: 53,
  lastLesson: '3.2 JavaScript Functions',
  timeLeft: '18:20 left',
  href: '/student/my-courses/current'
})

// Today's Goal (Interactive tasks)
const todayGoal = ref({
  completedCount: 1,
  totalCount: 2,
  percentage: 50,
  items: [
    { id: 1, title: 'Chapter 3 - JavaScript Functions', completed: true, href: '/student/my-courses/current' },
    { id: 2, title: 'Practice 10 quiz questions', completed: false, href: '/student/quizzes/practice' }
  ]
})

const toggleTask = (task: any) => {
  task.completed = !task.completed
  const done = todayGoal.value.items.filter(i => i.completed).length
  todayGoal.value.completedCount = done
  todayGoal.value.percentage = Math.round((done / todayGoal.value.totalCount) * 100)
}

// AI Recommended For You Widget Data (Prompt Section 8)
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

const myCoursesList = ref([
  {
    id: 1,
    title: 'Web Development Fundamentals',
    teacher: 'Mr. Sophea',
    progress: 53,
    status: 'in_progress',
    statusLabel: 'In Progress',
    badgeClass: 'bg-blue-500/20 text-blue-300 border-blue-500/30',
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
    badgeClass: 'bg-blue-500/20 text-blue-300 border-blue-500/30',
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
    badgeClass: 'bg-slate-800 text-slate-400 border-slate-700',
    iconType: 'python',
    iconColor: 'from-amber-600 to-yellow-600',
    href: '/student/courses/3/overview'
  },
  {
    id: 4,
    title: 'UI/UX Design Basics',
    teacher: 'Ms. Nhean Sreymom',
    progress: 0,
    status: 'not_started',
    statusLabel: 'Not Started',
    badgeClass: 'bg-slate-800 text-slate-400 border-slate-700',
    iconType: 'design',
    iconColor: 'from-pink-600 to-purple-600',
    href: '/student/courses/4/overview'
  }
])

const filteredCourses = computed(() => {
  if (selectedCourseFilter.value === 'all') return myCoursesList.value
  return myCoursesList.value.filter(c => c.status === selectedCourseFilter.value)
})

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
    href: '/student/my-courses/current'
  },
  {
    id: 3,
    icon: '📄',
    title: 'Assignment: Mini Project',
    course: 'Database Systems',
    date: 'Jun 01, 2025',
    time: '11:59 PM',
    href: '/student/assignments'
  }
])

// AI Study Assistant Widget
const aiPromptInput = ref('')
const aiMessages = ref<Array<{ role: 'ai' | 'user'; text: string }>>([
  {
    role: 'ai',
    text: 'Hi Sok Pisey! 👋 How can I help you with your learning today?'
  }
])

const handleSendAiPrompt = (promptText: string) => {
  aiMessages.value.push({ role: 'user', text: promptText })
  setTimeout(() => {
    const qLower = promptText.toLowerCase()
    if (qLower.includes('example')) {
      aiMessages.value.push({
        role: 'ai',
        text: 'Here is a quick JavaScript function example:\n```javascript\nfunction greet(name) {\n  return `Hello, ${name}!`;\n}\nconsole.log(greet("Pisey")); // Hello, Pisey!\n```'
      })
    } else if (qLower.includes('what should i study') || qLower.includes('recommend') || qLower.includes('plan')) {
      aiMessages.value.push({
        role: 'ai',
        text: 'Based on your recent activity:\n1. 🎯 **Finish Lesson 3.2** in Web Development (18m remaining).\n2. ⚠️ **Review Function Parameters** to lift your 62% quiz score.\n3. ⚡ **Practice 5 drill questions** before the May 28 Chapter Quiz!'
      })
    } else if (qLower.includes('explain')) {
      aiMessages.value.push({
        role: 'ai',
        text: 'A JavaScript function is a reusable block of code designed to perform a particular task. It executes when invoked and can return a computed result.'
      })
    } else if (qLower.includes('summarize')) {
      aiMessages.value.push({
        role: 'ai',
        text: 'Summary: Functions accept parameters, execute logic in their local block scope, and pass back values with the return keyword.'
      })
    } else {
      aiMessages.value.push({
        role: 'ai',
        text: 'Quick Quiz Check: What keyword declares a variable inside block scope? (A) var (B) let (C) define (D) dim'
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
    iconBg: 'bg-purple-600/20 text-purple-400 border border-purple-500/30',
    title: 'Watched: 3.2 JavaScript Functions',
    course: 'Web Development Fundamentals',
    time: 'Today, 09:30 AM',
    href: '/student/my-courses/current'
  },
  {
    id: 2,
    icon: '✓',
    iconBg: 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30',
    title: 'Completed Quiz: Chapter 2 Quiz',
    course: 'Web Development Fundamentals',
    time: 'Yesterday, 04:15 PM',
    href: '/student/quizzes/scores'
  },
  {
    id: 3,
    icon: '📄',
    iconBg: 'bg-amber-600/20 text-amber-400 border border-amber-500/30',
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
      
      <!-- TOP GREETING HEADER -->
      <div class="space-y-1">
        <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
          <span>Welcome back, {{ user.name || 'Sok Pisey' }}!</span>
          <span class="animate-waving-hand inline-block">👋</span>
        </h1>
        <p class="text-xs text-slate-400 font-medium">
          Keep learning and achieve your goals. You've got this! 💪
        </p>
      </div>

      <!-- 6 METRICS CARDS ROW (Fully clickable and connected) -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <Link
          v-for="stat in stats"
          :key="stat.title"
          :href="stat.href"
          class="bg-slate-900/80 border border-slate-800 hover:border-purple-500/40 rounded-2xl p-4 shadow-xl flex items-center gap-3.5 transition-all duration-200 hover:-translate-y-1 group"
        >
          <div :class="[stat.iconBg, 'w-10 h-10 rounded-xl flex items-center justify-center text-base shrink-0 shadow-sm group-hover:scale-105 transition-transform']">
            {{ stat.icon }}
          </div>
          <div class="min-w-0">
            <p class="text-[10px] text-slate-400 font-medium truncate">{{ stat.title }}</p>
            <h3 class="text-base sm:text-lg font-black text-white group-hover:text-purple-300 transition-colors truncate leading-tight">{{ stat.value }}</h3>
            <p class="text-[9px] text-slate-500 truncate">{{ stat.subtitle }}</p>
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
              <h2 class="text-xs font-bold text-slate-200 uppercase tracking-wider">Continue Learning</h2>
              <Link href="/student/my-courses/enrolled" class="text-xs text-purple-400 hover:text-purple-300 font-semibold">
                View All
              </Link>
            </div>

            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-5 sm:p-6 shadow-xl flex flex-col md:flex-row items-center gap-5 relative overflow-hidden">
              
              <!-- 3D / Mock cover illustration -->
              <div class="relative w-full md:w-36 h-28 rounded-2xl bg-gradient-to-br from-[#0c1322] via-[#0f172a] to-[#1e1b4b] border border-slate-800 flex items-center justify-center shrink-0 shadow-md">
                <div class="text-center space-y-1">
                  <div class="inline-flex items-center justify-center px-2 py-0.5 rounded-lg bg-amber-400 text-slate-950 font-black text-xs shadow-sm">
                    JS
                  </div>
                  <p class="text-[10px] font-mono text-purple-400">{ functions }</p>
                </div>
              </div>

              <!-- Course Info and Progress -->
              <div class="flex-1 w-full space-y-3">
                <div class="space-y-1">
                  <span class="px-2.5 py-0.5 rounded-md bg-purple-500/20 text-purple-300 text-[10px] font-bold border border-purple-500/30">
                    Teacher: {{ continueCourse.teacher }}
                  </span>
                  <h3 class="text-base font-bold text-white leading-tight">
                    {{ continueCourse.title }}
                  </h3>
                  <p class="text-xs text-slate-400 font-medium">
                    {{ continueCourse.chapter }}
                  </p>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1.5">
                  <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full" :style="{ width: continueCourse.progress + '%' }"></div>
                  </div>
                  <div class="flex items-center justify-between text-[11px] text-slate-400">
                    <span>Last lesson: {{ continueCourse.lastLesson }}</span>
                    <span class="font-mono flex items-center gap-1">⏱ {{ continueCourse.timeLeft }}</span>
                  </div>
                </div>
              </div>

              <!-- CTA Button -->
              <div class="w-full md:w-auto shrink-0">
                <Link
                  :href="continueCourse.href"
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
                <h2 class="text-xs font-bold text-slate-200 uppercase tracking-wider">My Courses</h2>
                
                <!-- Filter Pills -->
                <div class="flex items-center gap-1 bg-slate-950/80 p-1 rounded-xl border border-slate-800">
                  <button
                    @click="selectedCourseFilter = 'all'"
                    :class="[selectedCourseFilter === 'all' ? 'bg-purple-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200', 'px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer']"
                  >
                    All Courses
                  </button>
                  <button
                    @click="selectedCourseFilter = 'in_progress'"
                    :class="[selectedCourseFilter === 'in_progress' ? 'bg-purple-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200', 'px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer']"
                  >
                    In Progress
                  </button>
                  <button
                    @click="selectedCourseFilter = 'completed'"
                    :class="[selectedCourseFilter === 'completed' ? 'bg-purple-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200', 'px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer']"
                  >
                    Completed
                  </button>
                  <button
                    @click="selectedCourseFilter = 'not_started'"
                    :class="[selectedCourseFilter === 'not_started' ? 'bg-purple-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200', 'px-3 py-1 rounded-lg text-xs transition-colors cursor-pointer']"
                  >
                    Not Started
                  </button>
                </div>
              </div>

              <Link href="/student/my-courses/enrolled" class="text-xs text-purple-400 hover:text-purple-300 font-semibold self-start sm:self-auto">
                View All
              </Link>
            </div>

            <!-- 4 Courses Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <Link
                v-for="c in filteredCourses"
                :key="c.id"
                :href="c.href"
                class="bg-slate-900/80 border border-slate-800 hover:border-purple-500/40 rounded-2xl p-4 shadow-xl flex flex-col justify-between space-y-3 transition-all duration-200 hover:-translate-y-1 group"
              >
                <!-- Illustration Box with Badge -->
                <div class="relative w-full h-24 rounded-xl bg-slate-950 flex items-center justify-center overflow-hidden">
                  <div :class="['w-10 h-10 rounded-xl bg-gradient-to-tr', c.iconColor, 'flex items-center justify-center text-white text-lg font-mono font-bold shadow-md']">
                    <span v-if="c.iconType === 'code'">&lt;/&gt;</span>
                    <span v-else-if="c.iconType === 'db'">🗄️</span>
                    <span v-else-if="c.iconType === 'python'">🐍</span>
                    <span v-else>🎨</span>
                  </div>

                  <span :class="[c.badgeClass, 'absolute top-2 right-2 px-2 py-0.5 rounded-md text-[9px] font-bold border']">
                    {{ c.statusLabel }}
                  </span>
                </div>

                <!-- Course Title & Instructor -->
                <div class="space-y-1">
                  <h4 class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors line-clamp-1">
                    {{ c.title }}
                  </h4>
                  <p class="text-[10px] text-slate-400">{{ c.teacher }}</p>
                </div>

                <!-- Progress -->
                <div class="space-y-1 pt-1">
                  <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                    <div class="h-full bg-purple-500 rounded-full" :style="{ width: c.progress + '%' }"></div>
                  </div>
                  <span class="text-[10px] text-purple-400 font-bold font-mono">{{ c.progress }}%</span>
                </div>
              </Link>
            </div>
          </div>

          <!-- ANALYTICS CHARTS ROW (Learning Overview, Study Time Donut, Quiz Performance Gauge) -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- Learning Overview Line Chart Card -->
            <Link
              href="/student/progress"
              class="bg-slate-900/80 border border-slate-800 hover:border-purple-500/40 rounded-3xl p-5 shadow-xl space-y-4 block transition-all hover:-translate-y-1"
            >
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Learning Overview</h3>
                <span class="text-[11px] text-slate-400 bg-slate-950 px-2 py-0.5 rounded-md border border-slate-800">This Week ⌄</span>
              </div>

              <!-- SVG Line Chart Mockup -->
              <div class="relative pt-2">
                <div class="absolute top-2 left-1/2 -translate-x-1/2 px-2 py-1 rounded-lg bg-purple-600 text-white text-[10px] font-bold shadow-md">
                  2h 30m
                </div>

                <svg viewBox="0 0 200 80" class="w-full h-20 overflow-visible">
                  <defs>
                    <linearGradient id="purpleGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.4" />
                      <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0" />
                    </linearGradient>
                  </defs>
                  <path
                    d="M 10 60 Q 40 40 70 55 T 130 25 T 190 40 L 190 75 L 10 75 Z"
                    fill="url(#purpleGrad)"
                  />
                  <path
                    d="M 10 60 Q 40 40 70 55 T 130 25 T 190 40"
                    fill="none"
                    stroke="#8b5cf6"
                    stroke-width="2.5"
                    stroke-linecap="round"
                  />
                  <circle cx="130" cy="25" r="4" fill="#a855f7" stroke="#ffffff" stroke-width="2" />
                </svg>

                <div class="flex justify-between text-[9px] text-slate-500 font-mono pt-1">
                  <span>Mon</span>
                  <span>Tue</span>
                  <span>Wed</span>
                  <span>Thu</span>
                  <span>Fri</span>
                  <span>Sat</span>
                  <span>Sun</span>
                </div>
              </div>
            </Link>

            <!-- Study Time Donut Ring Chart Card -->
            <Link
              href="/student/progress"
              class="bg-slate-900/80 border border-slate-800 hover:border-purple-500/40 rounded-3xl p-5 shadow-xl space-y-4 block transition-all hover:-translate-y-1"
            >
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Study Time</h3>
              </div>

              <div class="flex items-center justify-center gap-4">
                <!-- SVG Circular Donut -->
                <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                  <svg viewBox="0 0 36 36" class="w-24 h-24 -rotate-90">
                    <path
                      class="text-slate-800"
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
                    <span class="text-[11px] font-black text-white leading-none">28h 45m</span>
                    <span class="text-[8px] text-slate-400">Total</span>
                  </div>
                </div>

                <!-- Legend -->
                <div class="space-y-1.5 text-[10px] text-slate-300">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span>Lessons: 16h 30m</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                    <span>Practice: 8h 15m</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                    <span>Quiz: 4h 00m</span>
                  </div>
                </div>
              </div>
            </Link>

            <!-- Quiz Performance Radial Gauge Card -->
            <Link
              href="/student/quizzes/scores"
              class="bg-slate-900/80 border border-slate-800 hover:border-purple-500/40 rounded-3xl p-5 shadow-xl space-y-4 block transition-all hover:-translate-y-1"
            >
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Quiz Performance</h3>
                <span class="text-[11px] text-slate-400 bg-slate-950 px-2 py-0.5 rounded-md border border-slate-800">This Month ⌄</span>
              </div>

              <div class="flex items-center justify-center gap-4">
                <!-- Gauge Circle -->
                <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                  <svg viewBox="0 0 36 36" class="w-24 h-24 -rotate-90">
                    <path
                      class="text-rose-500/40"
                      stroke-width="4"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      class="text-emerald-400"
                      stroke-dasharray="78, 100"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-sm font-black text-white leading-none">78%</span>
                    <span class="text-[8px] text-slate-400">Average</span>
                  </div>
                </div>

                <!-- Legend -->
                <div class="space-y-1.5 text-[10px] text-slate-300">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
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
              <h2 class="text-xs font-bold text-slate-200 uppercase tracking-wider">Recent Activity</h2>
              <Link href="/student/progress" class="text-xs text-purple-400 hover:text-purple-300 font-semibold">
                View All
              </Link>
            </div>

            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
              <Link
                v-for="act in recentActivities"
                :key="act.id"
                :href="act.href"
                class="flex items-center justify-between p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80 hover:border-purple-500/30 text-xs transition-all hover:bg-slate-950"
              >
                <div class="flex items-center gap-3">
                  <div :class="[act.iconBg, 'w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold shrink-0']">
                    {{ act.icon }}
                  </div>
                  <div>
                    <h4 class="font-bold text-white">{{ act.title }}</h4>
                    <p class="text-[10px] text-slate-400">{{ act.course }}</p>
                  </div>
                </div>

                <span class="text-[10px] text-slate-400 font-mono shrink-0">{{ act.time }}</span>
              </Link>
            </div>
          </div>

        </div>

        <!-- RIGHT 4 COLUMNS: Today's Goal, AI Recommended For You, Upcoming, AI Assistant, Achievements -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- TODAY'S GOAL CARD -->
          <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">Today's Goal</h3>
              <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold border border-emerald-500/30">
                {{ todayGoal.completedCount }} / {{ todayGoal.totalCount }} Completed
              </span>
            </div>

            <p class="text-xs text-slate-300 font-medium">Learn 2 lessons today</p>

            <div class="space-y-1.5">
              <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full transition-all duration-300" :style="{ width: todayGoal.percentage + '%' }"></div>
              </div>
              <p class="text-[10px] text-right text-slate-400">{{ todayGoal.percentage }}% completed</p>
            </div>

            <!-- Task Checklist -->
            <div class="space-y-2 pt-1">
              <div
                v-for="item in todayGoal.items"
                :key="item.id"
                class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/60 text-xs"
              >
                <button
                  @click="toggleTask(item)"
                  type="button"
                  class="flex items-center gap-2.5 text-left flex-1 cursor-pointer"
                >
                  <span v-if="item.completed" class="w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">✓</span>
                  <span v-else class="w-4 h-4 rounded-full border border-slate-600 flex items-center justify-center text-[9px]"></span>
                  <span :class="[item.completed ? 'line-through text-slate-400' : 'text-slate-200']">{{ item.title }}</span>
                </button>
                
                <Link :href="item.href" class="text-purple-400 hover:text-purple-300 text-[11px] font-bold ml-2">
                  Open →
                </Link>
              </div>
            </div>
          </div>

          <!-- AI RECOMMENDED FOR YOU CARD (Prompt Section 8) -->
          <div class="bg-gradient-to-br from-indigo-950/60 via-purple-950/40 to-slate-900 border border-purple-500/30 rounded-3xl p-5 shadow-2xl space-y-3.5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-base">🤖</span>
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">AI Recommended For You</h3>
              </div>
              <Link href="/student/ai-path/recommended" class="text-[10px] text-purple-400 hover:text-purple-300 font-semibold">
                Roadmap
              </Link>
            </div>

            <p class="text-[11px] text-purple-300">Based on your recent quiz scores & learning pace:</p>

            <div class="space-y-2.5">
              <div
                v-for="rec in aiRecommendations"
                :key="rec.id"
                class="p-3 rounded-2xl bg-slate-950/80 border border-purple-500/20 space-y-2"
              >
                <div class="flex items-center justify-between">
                  <h4 class="font-bold text-white text-xs">{{ rec.title }}</h4>
                  <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                    {{ rec.badge }}
                  </span>
                </div>
                <p class="text-[10px] text-slate-300 leading-relaxed">{{ rec.desc }}</p>
                
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
          <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">Upcoming</h3>
              <Link href="/student/calendar" class="text-[11px] text-purple-400 hover:text-purple-300 font-semibold">
                View Calendar
              </Link>
            </div>

            <div class="space-y-3">
              <Link
                v-for="item in upcomingEvents"
                :key="item.id"
                :href="item.href"
                class="flex items-start justify-between gap-2 p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/60 hover:border-purple-500/30 text-xs transition-colors block"
              >
                <div class="flex items-start gap-2.5">
                  <span class="text-sm mt-0.5">{{ item.icon }}</span>
                  <div>
                    <h4 class="font-bold text-white text-[11px]">{{ item.title }}</h4>
                    <p class="text-[10px] text-slate-400">{{ item.course }}</p>
                  </div>
                </div>

                <div class="text-right text-[10px] text-slate-400 shrink-0 font-mono">
                  <p class="font-bold text-slate-300">{{ item.date }}</p>
                  <p>{{ item.time }}</p>
                </div>
              </Link>
            </div>
          </div>

          <!-- AI STUDY ASSISTANT WIDGET -->
          <div class="bg-gradient-to-br from-[#111827] via-slate-900 to-[#1e1b4b]/60 border border-purple-500/30 rounded-3xl p-5 shadow-2xl space-y-3.5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-purple-600 text-white flex items-center justify-center text-xs">
                  🤖
                </div>
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">AI Study Assistant</h3>
              </div>
              <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 text-[10px] font-bold border border-purple-500/30">
                New
              </span>
            </div>

            <!-- Messages Stream -->
            <div class="space-y-2 max-h-40 overflow-y-auto custom-scrollbar pr-1">
              <div
                v-for="(msg, idx) in aiMessages"
                :key="idx"
                :class="[
                  msg.role === 'user' ? 'bg-purple-600/30 border border-purple-500/40 text-purple-100 ml-4' : 'bg-slate-800/80 border border-slate-700/60 text-slate-200 mr-2',
                  'p-2.5 rounded-2xl text-[11px] space-y-1'
                ]"
              >
                <p class="whitespace-pre-wrap leading-relaxed">{{ msg.text }}</p>
              </div>
            </div>

            <!-- Quick Action Prompt Chips -->
            <div class="grid grid-cols-2 gap-1.5 pt-1">
              <button
                @click="handleSendAiPrompt('Explain this lesson')"
                type="button"
                class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-medium border border-slate-700 text-center transition-colors cursor-pointer truncate"
              >
                Explain this lesson
              </button>
              <button
                @click="handleSendAiPrompt('Give me an example')"
                type="button"
                class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-medium border border-slate-700 text-center transition-colors cursor-pointer truncate"
              >
                Give me an example
              </button>
              <button
                @click="handleSendAiPrompt('Summarize this topic')"
                type="button"
                class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-medium border border-slate-700 text-center transition-colors cursor-pointer truncate"
              >
                Summarize this topic
              </button>
              <button
                @click="handleSendAiPrompt('What should I study today?')"
                type="button"
                class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] font-medium border border-slate-700 text-center transition-colors cursor-pointer truncate"
              >
                What should I study?
              </button>
            </div>

            <!-- Input Form -->
            <form @submit.prevent="sendUserCustomAi" class="relative flex items-center pt-1">
              <input
                v-model="aiPromptInput"
                type="text"
                placeholder="Type your question..."
                class="w-full pl-3 pr-8 py-2 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-purple-500 shadow-inner"
              />
              <button
                type="submit"
                class="absolute right-1.5 p-1 rounded-lg bg-purple-600 hover:bg-purple-500 text-white transition-all cursor-pointer"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </form>
          </div>

          <!-- ACHIEVEMENTS BADGES CARD -->
          <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">Achievements</h3>
              <Link href="/student/certificates/achievements" class="text-[11px] text-purple-400 hover:text-purple-300 font-semibold">
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
                <span class="text-[8px] text-slate-400 leading-tight w-12 truncate">{{ ach.title }}</span>
              </Link>
            </div>
          </div>

        </div>

      </div>

    </div>
  </StudentLayout>
</template>
