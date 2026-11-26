<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import TodaysGoalWidget from '@/Components/Student/TodaysGoalWidget.vue'
import WelcomeBanner from '@/Components/Student/WelcomeBanner.vue'
import { i18n } from '@/Services/i18n'

const props = defineProps<{
  studentProfile?: {
    id: number
    name: string
    name_kh?: string
    email?: string
    avatar?: string | null
    avatar_url?: string | null
  }
  academicTerm?: {
    id?: number | null
    code?: string | null
    name?: string | null
    semesterName?: string | null
    semesterNum?: string | null
    en: string
    km: string
  }
  announcements?: Array<{
    id: number
    title_en: string
    title_kh: string
    body_en: string
    body_kh: string
    priority?: string
    is_pinned?: boolean
    is_read?: boolean
    created_at?: string
    href?: string
  }>
  unreadAnnouncementsCount?: number
  hasStudiedBefore?: boolean
  stats?: {
    enrolledCount: number
    inProgressCount: number
    completedCount: number
    certificatesCount: number
    learningTime: string
    averageScore: string
    studyHours?: number
    studyMinutes?: number
    averageScoreNum?: number
  }
  continueCourse?: {
    id?: number
    title: string
    title_kh?: string
    chapter: string
    chapter_kh?: string
    major_name?: string
    major_kh?: string
    lessons_total?: number
    lessons_done?: number
    last_accessed?: string
    teacher: string
    teacher_kh?: string
    progress: number
    lastLesson: string
    lastLesson_kh?: string
    timeLeft: string
    thumbnail?: string | null
    href: string
    overviewHref?: string
  } | null
  dbCourses?: Array<{
    id: number
    title: string
    title_kh?: string
    major_name?: string
    major_kh?: string
    lessons_total?: number
    lessons_done?: number
    last_accessed?: string
    teacher: string
    teacher_kh?: string
    progress: number
    status: 'in_progress' | 'completed' | 'not_started' | 'no_content'
    statusLabel: string
    badgeClass: string
    iconType: string
    iconColor: string
    bgGradient?: string
    thumbnail?: string | null
    href: string
    continue_url?: string
    overview_url?: string
  }>
  recentActivities?: Array<{
    id: string | number
    type: 'play' | 'check' | 'doc'
    iconBg?: string
    title: string
    title_km?: string
    course: string
    course_km?: string
    time: string
    href: string
    progress?: number
    percent?: number
    category?: string
    category_km?: string
  }>
  upcomingEvents?: Array<{
    id: string | number
    type: 'quiz' | 'live' | 'assignment'
    title: string
    title_km?: string
    course: string
    course_km?: string
    date: string
    time: string
    href: string
  }>
  todayGoals?: {
    completedCount: number
    totalCount: number
    percentage: number
    items: Array<{
      id: string | number
      titleEn?: string
      titleKm?: string
      title?: string
      completed?: boolean
      isCompleted?: boolean
      href?: string
      targetRoute?: string
      createdAt?: number
    }>
  }
  enrollments?: any[]
  studyTimeBreakdown?: {
    total: string
    lessons: string
    practice: string
    quiz: string
    dailyAverage: string
    lessonsPct: number
    practicePct: number
    quizPct: number
  }
  quizPerformance?: {
    hasData?: boolean
    attemptsCount?: number
    averageScore: string | null
    averageScoreNum: number | null
    correctPct: string | null
    incorrectPct: string | null
    highestScore: string | null
    trend: string | null
  }
  aiRecommendations?: Array<{
    id: number
    type: string
    title: string
    title_km?: string
    badge: string
    badge_km?: string
    desc: string
    desc_km?: string
    actionLabel: string
    actionLabel_km?: string
    actionHref: string
  }>
  achievements?: Array<{
    id: string
    title: string
    title_km?: string
    desc: string
    desc_km?: string
    type: string
    color: string
    shadowColor?: string
    progress?: string
    progress_km?: string
    href: string
    unlocked?: boolean
  }>
}>()

const page = usePage<any>()
const user = computed(() => page.props.auth?.user || {})
const currentLang = computed(() => i18n.locale.value)

// Determine whether student has actually studied before
const hasStudiedBefore = computed<boolean>(() => {
  if (typeof props.hasStudiedBefore === 'boolean') {
    return props.hasStudiedBefore
  }
  const enrolledCount = props.stats?.enrolledCount ?? props.enrollments?.length ?? props.dbCourses?.length ?? 0
  if (enrolledCount === 0) return false
  const inProgress = props.stats?.inProgressCount ?? 0
  const completed = props.stats?.completedCount ?? 0
  const hasContinue = Boolean(props.continueCourse && props.continueCourse.title)
  const hasActivities = Boolean(props.recentActivities && props.recentActivities.length > 0)
  return inProgress > 0 || completed > 0 || hasContinue || hasActivities
})

const formatStudyDuration = (timeStr?: string | null): string => {
  if (!timeStr) return currentLang.value === 'km' ? '0h 0m' : '0h 0m'
  return timeStr
}

// Loading & Animation System
const loading = ref(true)
const loadingStep = ref<'skeleton' | 'counting' | 'ready'>('skeleton')
const error = ref('')
const liveStats = ref<any>(null)

// Animated count-up values
const animatedEnrolled = ref(0)
const animatedInProgress = ref(0)
const animatedCompleted = ref(0)
const animatedCertificates = ref(0)
const animatedStudyHours = ref(0)
const animatedStudyMinutes = ref(0)

const parseStudyTime = (timeStr: string) => {
  if (!timeStr) return { hours: 0, minutes: 0 }
  const hMatch = timeStr.match(/(\d+)\s*h/)
  const mMatch = timeStr.match(/(\d+)\s*m/)
  return {
    hours: hMatch ? parseInt(hMatch[1], 10) : 0,
    minutes: mMatch ? parseInt(mMatch[1], 10) : 0,
  }
}

function runCountUp(targets: {
  enrolled: number
  inProgress: number
  completed: number
  certificates: number
  studyHours: number
  studyMinutes: number
}) {
  const prefersReduced = typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches

  if (prefersReduced) {
    animatedEnrolled.value = targets.enrolled
    animatedInProgress.value = targets.inProgress
    animatedCompleted.value = targets.completed
    animatedCertificates.value = targets.certificates
    animatedStudyHours.value = targets.studyHours
    animatedStudyMinutes.value = targets.studyMinutes
    loadingStep.value = 'ready'
    loading.value = false
    return
  }

  loadingStep.value = 'counting'
  const duration = 1000
  const startTime = performance.now()

  function step(currentTime: number) {
    const elapsed = currentTime - startTime
    const progress = Math.min(elapsed / duration, 1)
    const easeOut = 1 - Math.pow(1 - progress, 3)

    animatedEnrolled.value = Math.round(targets.enrolled * easeOut)
    animatedInProgress.value = Math.round(targets.inProgress * easeOut)
    animatedCompleted.value = Math.round(targets.completed * easeOut)
    animatedCertificates.value = Math.round(targets.certificates * easeOut)
    animatedStudyHours.value = Math.round(targets.studyHours * easeOut)
    animatedStudyMinutes.value = Math.round(targets.studyMinutes * easeOut)

    if (progress < 1) {
      requestAnimationFrame(step)
    } else {
      loadingStep.value = 'ready'
      loading.value = false
    }
  }

  requestAnimationFrame(step)
}

async function loadDashboard() {
  error.value = ''
  if (props.stats) {
    liveStats.value = props.stats
    loading.value = false
    loadingStep.value = 'ready'
  } else {
    loading.value = true
    loadingStep.value = 'skeleton'
    try {
      const response = await fetch('/api/student/dashboard', {
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      if (response.ok) {
        const data = await response.json()
        liveStats.value = data.stats || data
      }
    } catch (err: any) {
      console.warn('Dashboard API fallback:', err)
    }
  }

  const activeStats = liveStats.value || props.stats || {}
  const targetEnrolled = Number(activeStats.enrolledCount ?? 0)
  const targetInProgress = Number(activeStats.inProgressCount ?? 0)
  const targetCompleted = Number(activeStats.completedCount ?? 0)
  const targetCerts = Number(activeStats.certificatesCount ?? 0)
  const parsedTime = parseStudyTime(activeStats.learningTime || '0h 0m')

  runCountUp({
    enrolled: targetEnrolled,
    inProgress: targetInProgress,
    completed: targetCompleted,
    certificates: targetCerts,
    studyHours: parsedTime.hours,
    studyMinutes: parsedTime.minutes,
  })
}

onMounted(() => {
  loadDashboard()
})

// 5 STAT CARDS (Strictly matching reference image media_1795666785038_6c508fbe.jpg)
const statsCards = computed(() => {
  const activeStats = liveStats.value || props.stats || {
    enrolledCount: 0,
    inProgressCount: 0,
    completedCount: 0,
    certificatesCount: 0,
    learningTime: '0h 0m',
  }

  let enrolledDisplay = (activeStats.enrolledCount ?? 0).toString()
  let inProgressDisplay = (activeStats.inProgressCount ?? 0).toString()
  let completedDisplay = (activeStats.completedCount ?? 0).toString()
  let certsDisplay = (activeStats.certificatesCount ?? 0).toString()
  let studyTimeDisplay = activeStats.learningTime || '0h 0m'

  if (loadingStep.value === 'counting') {
    enrolledDisplay = animatedEnrolled.value.toString()
    inProgressDisplay = animatedInProgress.value.toString()
    completedDisplay = animatedCompleted.value.toString()
    certsDisplay = animatedCertificates.value.toString()
    studyTimeDisplay = `${animatedStudyHours.value}h ${animatedStudyMinutes.value}m`
  }

  return [
    {
      type: 'enrolled',
      title: currentLang.value === 'km' ? 'វគ្គសិក្សាចុះឈ្មោះ' : 'Enrolled Courses',
      value: enrolledDisplay,
      subtitle: 'Enrolled Courses',
      gradient: 'from-[#6E42E5] to-[#8C65F7]',
      wavePath: 'M0 18 Q 20 4, 40 14 T 70 6 T 100 12 L 100 25 L 0 25 Z',
      waveLine: 'M0 18 Q 20 4, 40 14 T 70 6 T 100 12',
      href: '/student/my-courses/enrolled',
    },
    {
      type: 'in_progress',
      title: currentLang.value === 'km' ? 'កំពុងសិក្សា' : 'In Progress',
      value: inProgressDisplay,
      subtitle: 'In Progress',
      gradient: 'from-[#2563EB] to-[#3B82F6]',
      wavePath: 'M0 20 Q 25 8, 45 16 T 75 4 T 100 10 L 100 25 L 0 25 Z',
      waveLine: 'M0 20 Q 25 8, 45 16 T 75 4 T 100 10',
      href: '/student/my-courses/current',
    },
    {
      type: 'completed',
      title: currentLang.value === 'km' ? 'បានបញ្ចប់' : 'Completed',
      value: completedDisplay,
      subtitle: 'Completed',
      gradient: 'from-[#10B981] to-[#34D399]',
      wavePath: 'M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z',
      waveLine: 'M0 16 Q 20 22, 45 8 T 75 14 T 100 4',
      href: '/student/my-courses/completed',
    },
    {
      type: 'certificates',
      title: currentLang.value === 'km' ? 'វិញ្ញាបនបត្រ' : 'Certificates',
      value: certsDisplay,
      subtitle: 'Certificates',
      gradient: 'from-[#F59E0B] to-[#FBBF24]',
      wavePath: 'M0 18 Q 20 6, 45 16 T 75 8 T 100 14 L 100 25 L 0 25 Z',
      waveLine: 'M0 18 Q 20 6, 45 16 T 75 8 T 100 14',
      href: '/student/certificates/my-certificates',
    },
    {
      type: 'study_time',
      title: currentLang.value === 'km' ? 'ម៉ោងសិក្សាសរុប' : 'Total Study Time',
      value: formatStudyDuration(studyTimeDisplay),
      subtitle: 'Total Study Time',
      gradient: 'from-[#0EA5E9] to-[#38BDF8]',
      wavePath: 'M0 19 Q 25 10, 50 18 T 80 8 T 100 12 L 100 25 L 0 25 Z',
      waveLine: 'M0 19 Q 25 10, 50 18 T 80 8 T 100 12',
      href: '/student/progress/learning-time',
    },
  ]
})

// Continue Learning Course
const continueCourseData = computed(() => {
  return props.continueCourse || null
})

// Course Filter
const coursesToDisplay = computed(() => {
  return props.dbCourses && props.dbCourses.length > 0 ? props.dbCourses : []
})

const inProgressCoursesCount = computed(() => {
  return coursesToDisplay.value.filter(c => c.status === 'in_progress' || c.status === 'not_started').length
})

const completedCoursesCount = computed(() => {
  return coursesToDisplay.value.filter(c => c.status === 'completed').length
})

const totalCoursesCount = computed(() => coursesToDisplay.value.length)

// Default filter tab
const selectedCourseFilter = ref<'in_progress' | 'completed' | 'all'>('all')

watch(coursesToDisplay, (newCourses) => {
  if (newCourses && newCourses.length > 0) {
    const hasInProg = newCourses.some(c => c.status === 'in_progress' || c.status === 'not_started')
    const hasComp = newCourses.some(c => c.status === 'completed')
    if (!hasInProg && hasComp) {
      selectedCourseFilter.value = 'completed'
    } else if (hasInProg) {
      selectedCourseFilter.value = 'in_progress'
    } else {
      selectedCourseFilter.value = 'all'
    }
  }
}, { immediate: true })

const filteredCourses = computed(() => {
  if (selectedCourseFilter.value === 'all') return coursesToDisplay.value
  if (selectedCourseFilter.value === 'completed') return coursesToDisplay.value.filter(c => c.status === 'completed')
  return coursesToDisplay.value.filter(c => c.status === 'in_progress' || c.status === 'not_started')
})

// AI Study Assistant
const isAiChatExpanded = ref(false)
const aiPromptInput = ref('')
const isAiTyping = ref(false)
const aiMessages = ref<Array<{ role: 'ai' | 'user'; text: string; time?: string }>>([
  {
    role: 'ai',
    text: currentLang.value === 'km'
      ? 'សួស្តី ' + (user.value?.name || 'សិស្ស') + '! ខ្ញុំជា AI Tutor 24/7 របស់អ្នក។ តើអ្នកចង់ឱ្យខ្ញុំជួយពន្យល់ ឬរៀបចំផែនការរៀនអ្វីថ្ងៃនេះ?'
      : 'Hi ' + (user.value?.name || 'Student') + '! I am your 24/7 AI Academic Tutor. How can I assist with your course questions and study plan today?',
    time: currentLang.value === 'km' ? 'ឥឡូវនេះ' : 'Just now'
  }
])

const handleSendAiPrompt = async (promptText: string) => {
  const isKm = currentLang.value === 'km'
  aiMessages.value.push({ role: 'user', text: promptText, time: isKm ? 'ឥឡូវនេះ' : 'Now' })
  isAiTyping.value = true
  isAiChatExpanded.value = true

  const courseTitle = continueCourseData.value?.title || 'Saint Paul Institute Academic Courses'
  const lessonTitle = continueCourseData.value?.lastLesson || 'Core Lessons'
  const majorName = continueCourseData.value?.chapter || 'General Academic Study'

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const response = await fetch('/student/api/ai-tutor/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        message: promptText,
        context_course: courseTitle,
        major: majorName
      })
    })

    if (response.ok) {
      const data = await response.json()
      if (data.reply) {
        aiMessages.value.push({
          role: 'ai',
          text: data.reply,
          time: isKm ? 'ឥឡូវនេះ' : 'Now'
        })
        isAiTyping.value = false
        return
      }
    }
  } catch (err) {
    console.warn('AI Tutor API fallback:', err)
  }

  // Academic Course-Aware Fallback
  setTimeout(() => {
    isAiTyping.value = false
    aiMessages.value.push({
      role: 'ai',
      text: isKm
        ? `សំណួរល្អណាស់អំពី "${promptText}" ក្នុងវគ្គ "${courseTitle}"! ខ្ញុំជា AI Tutor 24/7 របស់អ្នក រង់ចាំជួយពន្យល់គ្រប់ចំណុច។`
        : `Great question regarding "${promptText}" in "${courseTitle}"! As your 24/7 AI tutor, I am ready to guide you.`,
      time: isKm ? 'ឥឡូវនេះ' : 'Now'
    })
  }, 350)
}

const toggleAiTutor = () => {
  isAiChatExpanded.value = !isAiChatExpanded.value
}

const sendUserCustomAi = () => {
  if (!aiPromptInput.value.trim()) return
  const text = aiPromptInput.value.trim()
  aiPromptInput.value = ''
  handleSendAiPrompt(text)
}

// 4 Achievements Badges strictly matching reference image
const achievementsList = computed(() => {
  const completedVal = props.stats?.completedCount ?? 0
  const quizMasterVal = (props.quizPerformance?.hasData && (props.quizPerformance?.averageScoreNum ?? 0) >= 75)
    ? 1
    : (props.stats?.completedCount ? 1 : 0)

  let streakVal = '1 ថ្ងៃ'
  const achStreak = props.achievements?.find(a => a.id === 'streak')
  if (achStreak && achStreak.progress_km) {
    streakVal = achStreak.progress_km.replace('/៧ ថ្ងៃ', ' ថ្ងៃ').replace('/7 Days', ' ថ្ងៃ')
  }

  const certsVal = props.stats?.certificatesCount ?? 0

  return [
    {
      id: 'completed',
      title: 'Course Completed',
      value: completedVal.toString(),
      type: 'trophy',
      bgGradient: 'from-amber-400 to-yellow-500',
      badgeBg: 'bg-amber-50/70 border-amber-200/60 dark:bg-amber-950/20 dark:border-amber-800/40',
      textColor: 'text-amber-600 dark:text-amber-400',
    },
    {
      id: 'quiz_master',
      title: 'Quiz Master',
      value: quizMasterVal.toString(),
      type: 'star',
      bgGradient: 'from-violet-500 to-purple-600',
      badgeBg: 'bg-purple-50/70 border-purple-200/60 dark:bg-purple-950/20 dark:border-purple-800/40',
      textColor: 'text-purple-600 dark:text-purple-400',
    },
    {
      id: 'streak',
      title: 'Learning Streak',
      value: streakVal,
      type: 'book',
      bgGradient: 'from-blue-500 to-indigo-600',
      badgeBg: 'bg-blue-50/70 border-blue-200/60 dark:bg-blue-950/20 dark:border-blue-800/40',
      textColor: 'text-blue-600 dark:text-blue-400',
    },
    {
      id: 'certs',
      title: 'Certificates',
      value: certsVal.toString(),
      type: 'medal',
      bgGradient: 'from-emerald-500 to-teal-600',
      badgeBg: 'bg-emerald-50/70 border-emerald-200/60 dark:bg-emerald-950/20 dark:border-emerald-800/40',
      textColor: 'text-emerald-600 dark:text-emerald-400',
    },
  ]
})

// Recent Activities
const recentActivities = computed(() => {
  if (!props.recentActivities || props.recentActivities.length === 0) {
    return []
  }
  return props.recentActivities.slice(0, 3).map((a) => {
    const rawTitle = (currentLang.value === 'km' && a.title_km) ? a.title_km : (a.title || '')
    const cleanTitle = rawTitle.replace(/^(Watched:\s*|បានទស្សនា:\s*|Completed Quiz:\s*|បានបញ្ចប់ Quiz:\s*|Downloaded:\s*|បានទាញយក:\s*)/i, '').trim()
    const p = (a as any).progress ?? (a as any).percent ?? 0
    return {
      ...a,
      cleanTitle,
      progress: Math.min(100, Math.max(0, Math.round(p)))
    }
  })
})

// Structured Upcoming Events with Date Badges
const upcomingEvents = computed(() => {
  if (props.upcomingEvents && props.upcomingEvents.length > 0) {
    return props.upcomingEvents.slice(0, 3)
  }
  return []
})
</script>

<template>
  <StudentLayout
    :title="currentLang === 'km' ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard'"
    :breadcrumbs="[
      { label: currentLang === 'km' ? 'ផ្ទាំងដើម' : 'Dashboard' },
      { label: 'Student Dashboard' }
    ]"
  >
    <div class="student-dashboard space-y-6 pb-28 sm:pb-36 notranslate font-khmer" translate="no" style="padding-bottom: 112px;">
      
      <!-- 1. TOP WELCOME BANNER (With Student Avatar, Greeting, and SPI Building Illustration) -->
      <WelcomeBanner
        :student-name="currentLang === 'km' ? (props.studentProfile?.name_kh || user?.name_kh || props.studentProfile?.name || user?.name || '') : (props.studentProfile?.name || user?.name || '')"
        :avatar-url="props.studentProfile?.avatar || props.studentProfile?.avatar_url || user?.avatar_url || user?.avatar"
        :student-id="user?.id"
        :academic-term="props.academicTerm?.en || ''"
        :academic-term-km="props.academicTerm?.km || ''"
        :unread-announcements-count="props.unreadAnnouncementsCount ?? 0"
        :current-lang="currentLang"
        :is-loading="loading && loadingStep === 'skeleton'"
        :has-studied-before="hasStudiedBefore"
      />

      <!-- 2. MAIN 2-COLUMN GRID (Strictly matching media_1795666785038_6c508fbe.jpg) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 sm:gap-6 items-start">
        
        <!-- LEFT COLUMN (lg:col-span-8 or ~72% width) -->
        <div class="lg:col-span-8 space-y-6 min-w-0">
          
          <!-- 2.1. FIVE STAT CARDS ROW -->
          <section :aria-busy="loading">
            <!-- Skeleton Loading -->
            <div
              v-if="loading && loadingStep === 'skeleton'"
              class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5"
            >
              <div
                v-for="n in 5"
                :key="n"
                class="relative overflow-hidden rounded-2xl bg-slate-200 dark:bg-[#1e1f20] p-4 flex flex-col justify-between min-h-[132px] animate-pulse border border-slate-300/60 dark:border-[#2d2f31]"
              >
                <div class="space-y-2">
                  <div class="h-3 w-16 bg-white/70 rounded"></div>
                  <div class="h-6 w-12 bg-white/80 rounded mt-2"></div>
                  <div class="h-2.5 w-20 bg-white/60 rounded"></div>
                </div>
              </div>
            </div>

            <!-- Loaded / Counting 5 Stat Cards -->
            <div
              v-else
              class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-3.5"
            >
              <Link
                v-for="(stat, idx) in statsCards"
                :key="stat.type"
                :href="stat.href"
                :style="{ animationDelay: `${idx * 60}ms` }"
                :class="[
                  'stat-card relative overflow-hidden rounded-2xl bg-gradient-to-br text-white p-3.5 sm:p-4 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group cursor-pointer border border-white/10 min-h-[132px]',
                  stat.gradient
                ]"
              >
                <div>
                  <div class="flex items-start justify-between gap-1">
                    <!-- Icon Box -->
                    <div class="w-8 h-8 rounded-xl bg-white/15 backdrop-blur-xs flex items-center justify-center text-white shrink-0 group-hover:scale-110 transition-transform">
                      <!-- Book Open (Enrolled) -->
                      <svg v-if="stat.type === 'enrolled'" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                      </svg>
                      <!-- Graduation Cap (In Progress) -->
                      <svg v-else-if="stat.type === 'in_progress'" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                        <path d="M6 12v5c3 3 9 3 12 0v-5" />
                      </svg>
                      <!-- Circle Check (Completed) -->
                      <svg v-else-if="stat.type === 'completed'" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <path d="m9 12 2 2 4-4" />
                      </svg>
                      <!-- Trophy (Certificates) -->
                      <svg v-else-if="stat.type === 'certificates'" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                        <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                        <path d="M4 22h16" />
                        <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                        <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                        <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                      </svg>
                      <!-- Clock (Study Time) -->
                      <svg v-else class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                      </svg>
                    </div>

                    <span class="text-[10px] sm:text-[10.5px] xl:text-[11px] font-bold text-white/95 leading-tight whitespace-nowrap">{{ stat.title }}</span>
                  </div>

                  <!-- Number Value -->
                  <h4 class="text-2xl font-black text-white mt-2 tabular-nums tracking-tight">
                    {{ stat.value }}
                  </h4>
                  <p class="text-[10px] text-white/80 font-medium truncate mt-0.5">
                    {{ stat.subtitle }}
                  </p>
                </div>

                <!-- Sparkline Wave SVG at bottom -->
                <div class="mt-1 -mx-3.5 -mb-3.5 sm:-mx-4 sm:-mb-4 overflow-hidden rounded-b-2xl">
                  <svg class="w-full h-6 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
                    <path :d="stat.wavePath" fill="currentColor" opacity="0.2"/>
                    <path :d="stat.waveLine" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.9"/>
                  </svg>
                </div>
              </Link>
            </div>
          </section>

          <!-- 2.2. CONTINUE LEARNING (បន្តការសិក្សា - Continue Learning) -->
          <div class="space-y-3">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-full bg-[#7045F5]/10 text-[#7045F5] flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 ml-0.5 fill-current" viewBox="0 0 24 24">
                  <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
              </div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                <span>បន្តការសិក្សា</span>
                <span class="text-sm font-normal text-slate-500 ml-1.5">(Continue Learning)</span>
              </h2>
            </div>

            <!-- Continue Learning Card -->
            <div
              v-if="continueCourseData"
              class="bg-white dark:bg-[#1e1f20] border border-slate-200/90 dark:border-[#2d2f31] rounded-2xl p-5 sm:p-6 shadow-xs hover:shadow-lg transition-all duration-300 flex flex-col md:flex-row items-center gap-5 sm:gap-6 relative overflow-hidden group"
            >
              <!-- 16:9 Thumbnail -->
              <Link :href="continueCourseData.href" class="relative w-full md:w-64 h-40 rounded-xl overflow-hidden shrink-0 bg-slate-900 shadow-sm border border-slate-100 dark:border-white/10 group-hover:scale-[1.01] transition-transform">
                <img
                  :src="continueCourseData.thumbnail || '/images/courses/default-study-book.png'"
                  :alt="continueCourseData.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                />
                <span class="absolute top-2.5 left-2.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold shadow-sm flex items-center gap-1.5 bg-emerald-500 text-white">
                  <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                  <span>{{ continueCourseData.progress === 100 ? 'បានបញ្ចប់' : 'កំពុងសិក្សា' }}</span>
                </span>
              </Link>

              <!-- Course Info & Progress -->
              <div class="flex-1 w-full space-y-3 min-w-0">
                <div class="space-y-1">
                  <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight truncate">
                    <Link :href="continueCourseData.href" class="hover:text-[#7045F5] transition-colors">
                      {{ continueCourseData.title_kh || continueCourseData.title }}
                    </Link>
                  </h3>
                  
                  <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <span class="flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-[#7045F5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                      </svg>
                      <span>{{ continueCourseData.major_kh || continueCourseData.major_name || continueCourseData.chapter_kh || continueCourseData.chapter }}</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                      </svg>
                      <span>{{ currentLang === 'km' ? 'គ្រូបង្រៀន: ' : 'Instructor: ' }}{{ continueCourseData.teacher_kh || continueCourseData.teacher }}</span>
                    </span>
                  </div>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1.5">
                  <div class="flex items-center justify-between text-xs font-semibold">
                    <span class="text-slate-700 dark:text-slate-300">វឌ្ឍនភាពសិក្សា</span>
                    <span class="text-[#7045F5] font-black tabular-nums">{{ continueCourseData.progress }}%</span>
                  </div>
                  <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-white/10 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-900 ease-out"
                      :class="continueCourseData.progress === 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-[#7045F5] via-indigo-600 to-blue-500'"
                      :style="{ width: continueCourseData.progress + '%' }"
                    ></div>
                  </div>
                </div>

                <!-- Footer details + CTA Button -->
                <div class="pt-1 flex flex-wrap items-center justify-between gap-3">
                  <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <span>📚 {{ continueCourseData.lessons_done || 0 }} / {{ continueCourseData.lessons_total || 0 }} មេរៀន</span>
                    <span>🕒 ចូលរៀនថ្មីៗ: {{ continueCourseData.last_accessed || 'ថ្មីៗនេះ' }}</span>
                  </div>

                  <Link
                    :href="continueCourseData.href"
                    class="px-5 py-2.5 rounded-full bg-gradient-to-r from-[#7045F5] to-[#5b32e6] hover:from-[#5f35e0] hover:to-[#4e27cc] text-white font-bold text-xs shadow-md shadow-purple-500/25 flex items-center gap-1.5 hover:scale-105 active:scale-95 transition-all cursor-pointer whitespace-nowrap shrink-0"
                  >
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                      <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span>{{ continueCourseData.progress === 100 ? 'ពិនិត្យវគ្គសិក្សា' : 'បន្តការសិក្សា' }}</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Empty State when no courses in progress -->
            <div v-else class="rounded-2xl bg-white dark:bg-[#1e1f20] border border-dashed border-slate-200 dark:border-[#2d2f31] p-8 text-center space-y-3">
              <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                មិនទាន់មានវគ្គសិក្សាកំពុងរៀននៅឡើយទេ។
              </p>
              <Link href="/student/browse" class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-[#7045F5] text-white text-xs font-bold shadow-md hover:bg-[#5b32e6] transition-all">
                <span>ស្វែងរកវគ្គសិក្សា</span>
                <span>→</span>
              </Link>
            </div>
          </div>

          <!-- 2.3. MY COURSES SECTION (វគ្គសិក្សារបស់ខ្ញុំ) -->
          <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-2.5">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                      <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                    </svg>
                  </div>
                  <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                    វគ្គសិក្សារបស់ខ្ញុំ
                  </h2>
                </div>

                <!-- Filter Pills (Matching Reference Image) -->
                <div class="flex items-center gap-1.5 ml-0 sm:ml-2">
                  <button
                    @click="selectedCourseFilter = 'in_progress'"
                    :class="[
                      selectedCourseFilter === 'in_progress'
                        ? 'bg-[#7045F5] text-white shadow-sm font-bold'
                        : 'bg-slate-100 dark:bg-[#282a2c] text-slate-600 dark:text-slate-300 hover:bg-slate-200',
                      'px-3.5 py-1.5 rounded-full text-xs transition-all cursor-pointer'
                    ]"
                  >
                    <span>វគ្គសិក្សាកំពុងរៀន ({{ inProgressCoursesCount }})</span>
                  </button>
                  <button
                    @click="selectedCourseFilter = 'completed'"
                    :class="[
                      selectedCourseFilter === 'completed'
                        ? 'bg-[#7045F5] text-white shadow-sm font-bold'
                        : 'bg-slate-100 dark:bg-[#282a2c] text-slate-600 dark:text-slate-300 hover:bg-slate-200',
                      'px-3.5 py-1.5 rounded-full text-xs transition-all cursor-pointer'
                    ]"
                  >
                    <span>✓ បានបញ្ចប់ ({{ completedCoursesCount }})</span>
                  </button>
                  <button
                    @click="selectedCourseFilter = 'all'"
                    :class="[
                      selectedCourseFilter === 'all'
                        ? 'bg-[#7045F5] text-white shadow-sm font-bold'
                        : 'bg-slate-100 dark:bg-[#282a2c] text-slate-600 dark:text-slate-300 hover:bg-slate-200',
                      'px-3.5 py-1.5 rounded-full text-xs transition-all cursor-pointer'
                    ]"
                  >
                    <span>ទាំងអស់ ({{ totalCoursesCount }})</span>
                  </button>
                </div>
              </div>

              <Link href="/student/my-courses/enrolled" class="text-xs text-[#7045F5] hover:underline font-bold self-start sm:self-auto">
                <span>មើលទាំងអស់ ></span>
              </Link>
            </div>

            <!-- 3-Column Course Cards Grid -->
            <div v-if="filteredCourses.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4.5">
              <Link
                v-for="c in filteredCourses"
                :key="c.id"
                :href="c.continue_url || c.href"
                class="bg-white dark:bg-[#1e1f20] border border-slate-200/90 dark:border-[#2d2f31] rounded-2xl overflow-hidden flex flex-col justify-between shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group cursor-pointer"
              >
                <!-- 16:9 Thumbnail Cover -->
                <div class="relative w-full h-36 bg-slate-900 overflow-hidden shrink-0">
                  <img
                    :src="c.thumbnail || '/images/courses/default-study-book.png'"
                    :alt="c.title"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                  <!-- Status Pill Badge top-left -->
                  <span
                    class="absolute top-2.5 left-2.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold shadow-sm flex items-center gap-1.5"
                    :class="c.progress === 100 ? 'bg-emerald-500 text-white' : 'bg-emerald-500 text-white'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                    <span>{{ c.progress === 100 ? 'បានបញ្ចប់' : 'កំពុងសិក្សា' }}</span>
                  </span>
                </div>

                <!-- Card Body -->
                <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                  <div class="space-y-1">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2 leading-snug group-hover:text-[#7045F5] transition-colors">
                      {{ c.title_kh || c.title }}
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                      {{ c.teacher_kh || c.teacher }}
                    </p>
                  </div>

                  <!-- Progress Bar with Percentage -->
                  <div class="space-y-1">
                    <div class="flex items-center justify-end text-[11px] font-bold text-slate-700 dark:text-slate-300 tabular-nums">
                      <span>{{ c.progress }}%</span>
                    </div>
                    <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-white/10 overflow-hidden">
                      <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="c.progress === 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-[#7045F5] to-[#2563EB]'"
                        :style="{ width: c.progress + '%' }"
                      ></div>
                    </div>
                  </div>

                  <!-- Footer: Lessons Done + Circular Purple Play Button -->
                  <div class="pt-1.5 border-t border-slate-100 dark:border-white/5 flex items-center justify-between">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                      📚 {{ c.lessons_done || 0 }} / {{ c.lessons_total || 0 }} មេរៀន
                    </span>

                    <div class="w-8 h-8 rounded-full bg-[#7045F5] text-white flex items-center justify-center shadow-md shadow-purple-500/25 group-hover:scale-110 transition-transform">
                      <svg class="w-3.5 h-3.5 ml-0.5 fill-current" viewBox="0 0 24 24">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                      </svg>
                    </div>
                  </div>
                </div>
              </Link>
            </div>

            <!-- Empty State for Courses -->
            <div v-else class="rounded-2xl bg-white dark:bg-[#1e1f20] border border-dashed border-slate-200 dark:border-[#2d2f31] p-8 text-center space-y-2">
              <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                មិនមានវគ្គសិក្សាក្នុងប្រភេទនេះនៅឡើយទេ។
              </p>
              <Link href="/student/browse" class="inline-block text-xs text-[#7045F5] font-bold hover:underline">
                ស្វែងរក និងចុះឈ្មោះវគ្គសិក្សា ↗
              </Link>
            </div>
          </div>

          <!-- 2.4. RECENT ACTIVITIES (សកម្មភាពសិក្សាថ្មីៗ) -->
          <div class="space-y-3.5">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                សកម្មភាពសិក្សាថ្មីៗ
              </h2>
              <Link
                href="/student/progress/learning-time"
                class="text-xs font-bold text-[#7045F5] hover:underline"
              >
                <span>មើលប្រវត្តិទាំងអស់ ></span>
              </Link>
            </div>

            <div v-if="recentActivities.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <Link
                v-for="act in recentActivities"
                :key="act.id"
                :href="act.href"
                class="bg-white dark:bg-[#1e1f20] border border-slate-200/80 dark:border-white/[0.08] hover:border-[#7045F5] rounded-2xl p-4 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between group cursor-pointer"
              >
                <div class="flex items-start gap-3">
                  <!-- Play Button (Purple gradient with white arrow) -->
                  <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center shrink-0 shadow-md shadow-purple-500/25 group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4 ml-0.5 fill-current" viewBox="0 0 24 24">
                      <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                  </div>

                  <div class="flex-1 min-w-0 space-y-1">
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                      {{ act.category_km || 'មេរៀន' }} · {{ act.time }}
                    </div>
                    <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white line-clamp-2 min-h-[2.2rem] group-hover:text-[#7045F5] transition-colors leading-snug">
                      {{ act.cleanTitle }}
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                      {{ act.course_km || act.course }}
                    </p>
                  </div>
                </div>

                <!-- Progress Bar -->
                <div class="pt-3 space-y-1 border-t border-slate-100 dark:border-white/5 mt-3">
                  <div class="flex items-center justify-between text-[11px] text-slate-500">
                    <span>វឌ្ឍនភាព</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ act.progress }}% បានមើល</span>
                  </div>
                  <div class="w-full h-1 bg-slate-100 dark:bg-white/10 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-gradient-to-r from-purple-600 to-indigo-600 rounded-full"
                      :style="{ width: `${act.progress}%` }"
                    ></div>
                  </div>
                </div>
              </Link>
            </div>

            <!-- Empty State for Recent Activities -->
            <div v-else class="rounded-2xl bg-white dark:bg-[#1e1f20] border border-dashed border-slate-200 dark:border-[#2d2f31] p-6 text-center text-xs text-slate-500">
              មិនទាន់មានសកម្មភាពថ្មីៗនៅឡើយទេ។ ចាប់ផ្តើមរៀនដើម្បីតាមដានវឌ្ឍនភាពរបស់អ្នក!
            </div>
          </div>

        </div>

        <!-- RIGHT COLUMN (lg:col-span-4 or ~28% width) -->
        <div class="lg:col-span-4 space-y-6 min-w-0">
          
          <!-- 2.5. SPI AI TUTOR 24/7 CARD -->
          <div class="bg-white dark:bg-[#1e1f20] border border-slate-200/90 dark:border-[#2d2f31] rounded-2xl p-5 shadow-xs space-y-3.5 transition-all">
            <!-- Header -->
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-purple-50 dark:bg-purple-900/30 border border-purple-100 dark:border-purple-800/40 flex items-center justify-center shrink-0 shadow-xs">
                  <img
                    :src="'/images/ai-robot-avatar.png'"
                    alt="AI Tutor"
                    class="w-7 h-7 object-contain drop-shadow-xs"
                  />
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-tight">
                    SPI AI Tutor 24/7
                  </h3>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug mt-0.5">
                    ជំនួយការសិក្សា AI សម្រាប់ឆ្លើយសំណួរ សង្ខេបមេរៀន និងផ្តល់ដំបូន្មានសិក្សា
                  </p>
                </div>
              </div>

              <!-- Online badge -->
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 shrink-0">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Online</span>
              </span>
            </div>

            <!-- Primary CTA Button -->
            <button
              @click="toggleAiTutor"
              type="button"
              class="w-full py-2.5 rounded-xl bg-gradient-to-r from-[#7045F5] to-[#5b32e6] hover:from-[#5f35e0] hover:to-[#4e27cc] text-white font-bold text-xs shadow-md shadow-purple-500/20 flex items-center justify-center gap-2 transition-all hover:scale-[1.02] active:scale-98 cursor-pointer"
            >
              <span>✨ ចាប់ផ្តើមសួរ AI</span>
            </button>

            <!-- Expanded AI Chat Stream and Input -->
            <div v-if="isAiChatExpanded" class="space-y-3 pt-2 border-t border-slate-100 dark:border-white/5 animate-in fade-in duration-200">
              <div class="space-y-2 max-h-48 overflow-y-auto pr-1 text-xs">
                <div
                  v-for="(msg, idx) in aiMessages"
                  :key="idx"
                  :class="[
                    msg.role === 'user'
                      ? 'bg-[#7045F5] text-white rounded-2xl rounded-tr-xs ml-4 p-2.5'
                      : 'bg-slate-50 dark:bg-[#25272a] text-slate-800 dark:text-slate-100 rounded-2xl rounded-tl-xs mr-2 p-3 border border-slate-200/60 dark:border-white/5',
                    'leading-relaxed'
                  ]"
                >
                  <p class="whitespace-pre-wrap">{{ msg.text }}</p>
                </div>

                <div v-if="isAiTyping" class="p-2.5 rounded-2xl bg-purple-50 dark:bg-purple-950/30 text-[#7045F5] text-xs flex items-center gap-2">
                  <span class="animate-spin text-sm">⏳</span>
                  <span>AI Tutor កំពុងវិភាគចម្លើយ...</span>
                </div>
              </div>

              <!-- Quick prompt chips -->
              <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                <button
                  @click="handleSendAiPrompt('ពន្យល់មេរៀននេះ')"
                  type="button"
                  class="py-1.5 px-2 rounded-lg bg-slate-50 dark:bg-[#25272a] hover:bg-purple-50 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-white/10 text-center font-medium truncate"
                >
                  💡 ពន្យល់មេរៀននេះ
                </button>
                <button
                  @click="handleSendAiPrompt('ផ្តល់ឧទាហរណ៍')"
                  type="button"
                  class="py-1.5 px-2 rounded-lg bg-slate-50 dark:bg-[#25272a] hover:bg-purple-50 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-white/10 text-center font-medium truncate"
                >
                  📝 ផ្តល់ឧទាហរណ៍
                </button>
              </div>

              <!-- Input Form -->
              <form @submit.prevent="sendUserCustomAi" class="relative flex items-center">
                <input
                  v-model="aiPromptInput"
                  type="text"
                  placeholder="សួរសំណួរទៅកាន់ AI Tutor..."
                  class="w-full pl-3.5 pr-10 py-2 rounded-full bg-slate-50 dark:bg-[#131314] border border-slate-200 dark:border-[#2d2f31] text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-[#7045F5]"
                />
                <button
                  type="submit"
                  :disabled="!aiPromptInput.trim() || isAiTyping"
                  class="absolute right-1 p-1.5 rounded-full bg-[#7045F5] text-white disabled:opacity-40 hover:bg-[#5b32e6] transition"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                  </svg>
                </button>
              </form>
            </div>
          </div>

          <!-- 2.6. UPCOMING ACTIVITIES (សកម្មភាពជិតមកដល់) -->
          <div class="bg-white dark:bg-[#1e1f20] border border-slate-200/90 dark:border-[#2d2f31] rounded-2xl p-5 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-2.5">
              <div class="flex items-center gap-2">
                <span class="text-base">📅</span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                  សកម្មភាពជិតមកដល់
                </h3>
              </div>
              <Link href="/student/calendar" class="text-xs text-[#7045F5] hover:underline font-bold">
                មើលទាំងអស់ >
              </Link>
            </div>

            <!-- List items -->
            <div v-if="upcomingEvents.length > 0" class="space-y-3">
              <Link
                v-for="item in upcomingEvents"
                :key="item.id"
                :href="item.href"
                class="flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50/70 dark:bg-[#131314] border border-slate-200/70 dark:border-white/5 hover:border-[#7045F5] transition-all group"
              >
                <div class="flex items-start gap-2.5 min-w-0">
                  <div class="w-8 h-8 rounded-lg bg-purple-100 text-[#7045F5] flex items-center justify-center shrink-0 font-bold text-xs mt-0.5">
                    {{ item.type === 'quiz' ? 'Q' : 'A' }}
                  </div>
                  <div class="min-w-0 space-y-0.5">
                    <span
                      class="px-1.5 py-0.2 rounded text-[9.5px] font-black uppercase tracking-wider inline-block"
                      :class="item.type === 'quiz' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                    >
                      {{ item.type }}
                    </span>
                    <h4 class="font-bold text-xs text-slate-900 dark:text-white line-clamp-1 group-hover:text-[#7045F5] transition-colors">
                      {{ item.title_km || item.title }}
                    </h4>
                    <p class="text-[11px] text-slate-500 truncate">
                      {{ item.course_km || item.course }}
                    </p>
                  </div>
                </div>

                <!-- Date & Time on right -->
                <div class="text-right shrink-0">
                  <div class="text-xs font-bold text-rose-600 dark:text-rose-400">
                    📅 {{ item.date || '26 Nov' }}
                  </div>
                  <div class="text-[11px] text-slate-400">
                    🕒 {{ item.time || '23:59' }}
                  </div>
                </div>
              </Link>
            </div>

            <!-- Empty state -->
            <div v-else class="p-5 text-center text-xs text-slate-500 bg-slate-50 dark:bg-[#131314] rounded-xl border border-dashed border-slate-200">
              គ្មានកាលវិភាគ Quiz ឬកិច្ចការខាងមុខទេ។
            </div>
          </div>

          <!-- 2.7. ACHIEVEMENTS (សមិទ្ធផលសិក្សា) - 4 BADGES IN ROW -->
          <div class="bg-white dark:bg-[#1e1f20] border border-slate-200/90 dark:border-[#2d2f31] rounded-2xl p-5 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-2.5">
              <div class="flex items-center gap-2">
                <span class="text-base">🏆</span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                  សមិទ្ធផលសិក្សា
                </h3>
              </div>
              <Link href="/student/certificates/achievements" class="text-xs text-[#7045F5] hover:underline font-bold">
                មើលទាំងអស់ >
              </Link>
            </div>

            <!-- 4 Badges in Horizontal Row (Strictly matching reference image) -->
            <div class="grid grid-cols-4 gap-2">
              <Link
                v-for="ach in achievementsList"
                :key="ach.id"
                href="/student/certificates/achievements"
                :class="[
                  'flex flex-col items-center justify-between p-2.5 rounded-xl border text-center transition-all hover:scale-105 cursor-pointer',
                  ach.badgeBg
                ]"
              >
                <!-- Squircle Icon with Gradient -->
                <div :class="['w-9 h-9 rounded-xl bg-gradient-to-br', ach.bgGradient, 'text-white flex items-center justify-center shadow-sm shrink-0']">
                  <!-- Trophy -->
                  <svg v-if="ach.type === 'trophy'" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                    <path d="M4 22h16" />
                    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                  </svg>
                  <!-- Star -->
                  <svg v-else-if="ach.type === 'star'" class="w-4.5 h-4.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                  </svg>
                  <!-- Book -->
                  <svg v-else-if="ach.type === 'book'" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                  </svg>
                  <!-- Medal -->
                  <svg v-else class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="6" />
                    <path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526" />
                  </svg>
                </div>

                <div class="mt-1.5 space-y-0.5 w-full">
                  <div class="text-[8.5px] sm:text-[9.5px] font-bold text-slate-700 dark:text-slate-300 leading-tight text-center break-words min-h-[22px] flex items-center justify-center">
                    {{ ach.title }}
                  </div>
                  <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-white tabular-nums">
                    {{ ach.value }}
                  </div>
                </div>
              </Link>
            </div>
          </div>

          <!-- 2.8. TODAY'S GOAL (គោលដៅថ្ងៃនេះ) WIDGET -->
          <TodaysGoalWidget
            :initial-goals="props.todayGoals"
            :user-id="user?.id"
            :current-lang="currentLang"
          />

        </div>

      </div>

    </div>
  </StudentLayout>
</template>

<style scoped>
.stat-card {
  animation: statCardEnter 500ms cubic-bezier(0.16, 1, 0.3, 1) both;
}
.stat-card:hover {
  transform: translateY(-4px);
}
@keyframes statCardEnter {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
