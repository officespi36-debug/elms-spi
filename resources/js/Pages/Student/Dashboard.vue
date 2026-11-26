<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
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
    actionLabel?: string
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
  learningOverview?: {
    week: { total: string; trend: string | null; series?: Array<{ date: string; label: string; seconds: number; formatted: string }> }
    last_week: { total: string; trend: string | null; series?: Array<{ date: string; label: string; seconds: number; formatted: string }> }
    month: { total: string; trend: string | null; series?: Array<{ date: string; label: string; seconds: number; formatted: string }> }
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

// Determine whether student has actually studied before or is a brand-new student
const hasStudiedBefore = computed<boolean>(() => {
  if (typeof props.hasStudiedBefore === 'boolean') {
    return props.hasStudiedBefore
  }

  // Fallback: Check if student has actual enrolled courses AND learning activity
  const enrolledCount = props.stats?.enrolledCount ?? props.enrollments?.length ?? props.dbCourses?.length ?? 0
  if (enrolledCount === 0) return false

  const inProgress = props.stats?.inProgressCount ?? 0
  const completed = props.stats?.completedCount ?? 0
  const hasContinue = Boolean(props.continueCourse && props.continueCourse.title)
  const hasActivities = Boolean(props.recentActivities && props.recentActivities.length > 0)
  const studyHours = props.stats?.studyHours ?? 0
  const studyMinutes = props.stats?.studyMinutes ?? 0

  return inProgress > 0 || completed > 0 || hasContinue || hasActivities || studyHours > 0 || studyMinutes > 0
})

const formatStudyDuration = (timeStr?: string | null): string => {
  if (!timeStr) return currentLang.value === 'km' ? '0 ម៉ោង 0 នាទី' : '0h 0m'
  if (currentLang.value !== 'km') return timeStr
  return timeStr
    .replace(/(\d+)\s*h/g, '$1 ម៉ោង')
    .replace(/(\d+)\s*m/g, '$1 នាទី')
    .trim()
}

const formatCompactDuration = (timeStr?: string | null): string => {
  const { hours, minutes } = parseStudyTime(timeStr || '0h 0m')
  if (currentLang.value === 'km') {
    if (hours === 0 && minutes === 0) return '0 នាទី'
    if (hours === 0) return `${minutes} នាទី`
    if (minutes === 0) return `${hours} ម៉ោង`
    return `${hours}ម៉ ${minutes}នាទី`
  }
  if (hours === 0 && minutes === 0) return '0h 0m'
  if (hours === 0) return `${minutes}m`
  if (minutes === 0) return `${hours}h`
  return `${hours}h ${minutes}m`
}

const formatDailyAverage = (avgStr?: string | null): string => {
  if (!avgStr) return currentLang.value === 'km' ? '0 នាទី / ថ្ងៃ' : '0h / day'
  if (currentLang.value !== 'km') return avgStr
  if (avgStr === '0h / day' || avgStr === '0m / day') return '0 នាទី / ថ្ងៃ'
  return avgStr
    .replace(/([\d.]+)\s*h\s*\/\s*day/i, '$1 ម៉ោង / ថ្ងៃ')
    .replace(/([\d.]+)\s*m\s*\/\s*day/i, '$1 នាទី / ថ្ងៃ')
}

// ─── Official Dashboard Loading & Animation System (DL-01 to DL-08) ───
const loading = ref(true)
const loadingStep = ref<'skeleton' | 'counting' | 'ready'>('skeleton')
const error = ref('')
const liveStats = ref<any>(null)

// Animated count-up values (DL-04)
const animatedEnrolled = ref(0)
const animatedInProgress = ref(0)
const animatedCompleted = ref(0)
const animatedCertificates = ref(0)
const animatedStudyHours = ref(0)
const animatedStudyMinutes = ref(0)
const animatedQuizAvg = ref(0)

const parseStudyTime = (timeStr: string) => {
  if (!timeStr) return { hours: 0, minutes: 0 }
  const hMatch = timeStr.match(/(\d+)\s*h/)
  const mMatch = timeStr.match(/(\d+)\s*m/)
  return {
    hours: hMatch ? parseInt(hMatch[1], 10) : 0,
    minutes: mMatch ? parseInt(mMatch[1], 10) : 0,
  }
}

const parseScoreNum = (scoreStr: any) => {
  if (typeof scoreStr === 'number') return scoreStr
  if (!scoreStr) return 0
  const num = parseInt(scoreStr.toString().replace(/[^0-9]/g, ''), 10)
  return isNaN(num) ? 0 : num
}

function runCountUp(targets: {
  enrolled: number
  inProgress: number
  completed: number
  certificates: number
  studyHours: number
  studyMinutes: number
  quizAvg: number
}) {
  const prefersReduced = typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches

  if (prefersReduced) {
    animatedEnrolled.value = targets.enrolled
    animatedInProgress.value = targets.inProgress
    animatedCompleted.value = targets.completed
    animatedCertificates.value = targets.certificates
    animatedStudyHours.value = targets.studyHours
    animatedStudyMinutes.value = targets.studyMinutes
    animatedQuizAvg.value = targets.quizAvg
    loadingStep.value = 'ready'
    loading.value = false
    return
  }

  loadingStep.value = 'counting'
  const duration = 1000 // 1s smooth count-up
  const startTime = performance.now()

  function step(currentTime: number) {
    const elapsed = currentTime - startTime
    const progress = Math.min(elapsed / duration, 1)
    // Ease-out cubic curve: 1 - (1 - progress)^3
    const easeOut = 1 - Math.pow(1 - progress, 3)

    animatedEnrolled.value = Math.round(targets.enrolled * easeOut)
    animatedInProgress.value = Math.round(targets.inProgress * easeOut)
    animatedCompleted.value = Math.round(targets.completed * easeOut)
    animatedCertificates.value = Math.round(targets.certificates * easeOut)
    animatedStudyHours.value = Math.round(targets.studyHours * easeOut)
    animatedStudyMinutes.value = Math.round(targets.studyMinutes * easeOut)
    animatedQuizAvg.value = Math.round(targets.quizAvg * easeOut)

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

  // Immediately use props.stats if provided directly by server-side Inertia render (0ms instant render)
  if (props.stats) {
    liveStats.value = props.stats
    loading.value = false
    loadingStep.value = 'loaded'
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
  const targetQuiz = parseScoreNum(activeStats.averageScore ?? activeStats.averageScoreNum ?? 0)

  runCountUp({
    enrolled: targetEnrolled,
    inProgress: targetInProgress,
    completed: targetCompleted,
    certificates: targetCerts,
    studyHours: parsedTime.hours,
    studyMinutes: parsedTime.minutes,
    quizAvg: targetQuiz
  })
}

onMounted(() => {
  loadDashboard()
  if (typeof window !== 'undefined') {
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('in-view')
          }
        })
      }, { threshold: 0.1 })

      setTimeout(() => {
        document.querySelectorAll('.course-card-scroll, .recent-activity-card').forEach(el => observer.observe(el))
      }, 150)

      setTimeout(() => {
        document.querySelectorAll('.recent-activity-card').forEach(el => el.classList.add('in-view'))
      }, 850)
    }
  }
})

// 6 Top Stats Metrics styled like the Admin Dashboard with vibrant gradients & sparklines
const statsCards = computed(() => {
  const activeStats = liveStats.value || props.stats || {}
  const hasQuizAvg = Boolean(
    props.quizPerformance?.hasData ??
      (activeStats.averageScore && activeStats.averageScore !== '—' && activeStats.averageScore !== '-')
  )

  let enrolledDisplay = (activeStats.enrolledCount ?? 0).toString()
  let inProgressDisplay = (activeStats.inProgressCount ?? 0).toString()
  let completedDisplay = (activeStats.completedCount ?? 0).toString()
  let certsDisplay = (activeStats.certificatesCount ?? 0).toString()
  let studyTimeDisplay = activeStats.learningTime || '0h 0m'
  let quizAvgDisplay = hasQuizAvg ? (activeStats.averageScore || '0%') : '—'

  if (loadingStep.value === 'counting') {
    enrolledDisplay = animatedEnrolled.value.toString()
    inProgressDisplay = animatedInProgress.value.toString()
    completedDisplay = animatedCompleted.value.toString()
    certsDisplay = animatedCertificates.value.toString()
    studyTimeDisplay = `${animatedStudyHours.value}h ${animatedStudyMinutes.value}m`
    quizAvgDisplay = hasQuizAvg ? `${animatedQuizAvg.value}%` : '—'
  }

  return [
    {
      type: 'enrolled',
      title: currentLang.value === 'km' ? 'វគ្គសិក្សាបានចុះឈ្មោះ' : 'ENROLLED COURSES',
      value: enrolledDisplay,
      subtitle: currentLang.value === 'km' ? 'វគ្គសិក្សាសរុប' : 'Total Enrolled',
      gradient: 'from-indigo-600 to-blue-600',
      wavePath: 'M0 18 Q 20 4, 40 14 T 70 6 T 100 12 L 100 25 L 0 25 Z',
      waveLine: 'M0 18 Q 20 4, 40 14 T 70 6 T 100 12',
      href: '/student/my-courses/enrolled'
    },
    {
      type: 'in_progress',
      title: currentLang.value === 'km' ? 'កំពុងសិក្សា' : 'IN PROGRESS',
      value: inProgressDisplay,
      subtitle: currentLang.value === 'km' ? 'វគ្គសិក្សាសកម្ម' : 'Active Learning',
      gradient: 'from-blue-600 to-indigo-600',
      wavePath: 'M0 20 Q 25 8, 45 16 T 75 4 T 100 10 L 100 25 L 0 25 Z',
      waveLine: 'M0 20 Q 25 8, 45 16 T 75 4 T 100 10',
      href: '/student/my-courses/current'
    },
    {
      type: 'completed',
      title: currentLang.value === 'km' ? 'បានបញ្ចប់' : 'COMPLETED',
      value: completedDisplay,
      subtitle: currentLang.value === 'km' ? 'បានបញ្ចប់ជោគជ័យ' : 'Finished Courses',
      gradient: 'from-indigo-700 to-purple-700',
      wavePath: 'M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z',
      waveLine: 'M0 16 Q 20 22, 45 8 T 75 14 T 100 4',
      href: '/student/my-courses/completed'
    },
    {
      type: 'certificates',
      title: currentLang.value === 'km' ? 'វិញ្ញាបនបត្រ' : 'CERTIFICATES',
      value: certsDisplay,
      subtitle: currentLang.value === 'km' ? 'វិញ្ញាបនបត្រផ្លូវការ' : 'Verified & Earned',
      gradient: 'from-purple-600 to-indigo-600',
      wavePath: 'M0 18 Q 20 6, 45 16 T 75 8 T 100 14 L 100 25 L 0 25 Z',
      waveLine: 'M0 18 Q 20 6, 45 16 T 75 8 T 100 14',
      href: '/student/certificates/my-certificates'
    },
    {
      type: 'study_time',
      title: currentLang.value === 'km' ? 'ម៉ោងសិក្សា' : 'STUDY TIME',
      value: formatStudyDuration(studyTimeDisplay),
      subtitle: currentLang.value === 'km' ? 'ម៉ោងសិក្សាសរុប' : 'Total Hours Spent',
      gradient: 'from-blue-700 to-indigo-800',
      wavePath: 'M0 19 Q 25 10, 50 18 T 80 8 T 100 12 L 100 25 L 0 25 Z',
      waveLine: 'M0 19 Q 25 10, 50 18 T 80 8 T 100 12',
      href: '/student/progress/learning-time'
    },
    {
      type: 'quiz_avg',
      title: currentLang.value === 'km' ? 'មធ្យមភាគ QUIZ' : 'QUIZ AVERAGE',
      value: quizAvgDisplay,
      subtitle: currentLang.value === 'km' ? 'ពិន្ទុមធ្យមសិក្សា' : 'Academic Score',
      gradient: 'from-indigo-600 to-violet-600',
      wavePath: 'M0 12 Q 25 20, 50 10 T 75 16 T 100 6 L 100 25 L 0 25 Z',
      waveLine: 'M0 12 Q 25 20, 50 10 T 75 16 T 100 6',
      href: '/student/quizzes/scores'
    }
  ]
})

// Featured Continue Learning Course (Real Database Driven - Null if not enrolled)
const continueCourseData = computed(() => {
  return props.continueCourse || null
})

// Today's Goal state & persistence are encapsulated inside TodaysGoalWidget.vue

// AI Recommended For You Widget Data (Strictly connected to `ai_recommendations` in Database)
const aiRecommendations = computed(() => {
  if (!props.aiRecommendations || props.aiRecommendations.length === 0) {
    return []
  }
  return props.aiRecommendations.map(r => ({
    ...r,
    title: currentLang.value === 'km' ? (r.title_km || r.title) : r.title,
    badge: currentLang.value === 'km' ? (r.badge_km || r.badge) : r.badge,
    desc: currentLang.value === 'km' ? (r.desc_km || r.desc) : r.desc,
    actionLabel: currentLang.value === 'km' ? (r.actionLabel_km || r.actionLabel) : r.actionLabel,
  }))
})

// Course status badge styling helper - Solid high contrast backgrounds (WCAG AA compliant)
const getCourseStatusBadgeClass = (status: string) => {
  if (status === 'completed') {
    return 'bg-emerald-600/95 text-white border-emerald-400/40 shadow-sm'
  }
  if (status === 'in_progress') {
    return 'bg-blue-600/95 text-white border-blue-400/40 shadow-sm'
  }
  return 'bg-slate-800/90 text-white border-white/20 shadow-sm'
}

// Structured Event Date parser for Left Calendar Badge (e.g. NOV 17 or ឧសភា ២៨)
const getEventDateBadge = (item: { date?: string }) => {
  const isKm = currentLang.value === 'km'
  const dateStr = item.date || ''
  const parts = dateStr.trim().split(/\s+/)
  if (isKm) {
    return {
      month: parts[1] || 'កាលវិភាគ',
      day: parts[0] || '២៨'
    }
  } else {
    return {
      month: (parts[0] || 'NOV').replace(/,/g, '').toUpperCase().slice(0, 3),
      day: (parts[1] || '17').replace(/,/g, '')
    }
  }
}

// My Courses Filter and List (100% Database Driven)
const selectedCourseFilter = ref<'all' | 'in_progress' | 'completed' | 'not_started'>('all')

// Pure Eloquent Courses List (Empty array if no courses enrolled)
const coursesToDisplay = computed(() => {
  if (props.dbCourses && props.dbCourses.length > 0) {
    return props.dbCourses
  }
  return []
})

const filteredCourses = computed(() => {
  if (selectedCourseFilter.value === 'all') return coursesToDisplay.value
  return coursesToDisplay.value.filter(c => c.status === selectedCourseFilter.value)
})

// Learning Overview Chart Range Switcher (Connected to real study_daily series)
const selectedRange = ref<'week' | 'last_week' | 'month'>('week')
const hoveredSeriesIdx = ref<number | null>(null)

const buildSeriesChart = (
  rangeData: { total?: string; trend?: string | null; series?: Array<{ date: string; label: string; seconds: number; formatted: string }> } | undefined,
  subLabel: string
) => {
  const series = rangeData?.series && rangeData.series.length > 0
    ? rangeData.series
    : Array.from({ length: 7 }, (_, i) => ({ date: '', label: `D${i + 1}`, seconds: 0, formatted: '0h 0m' }))

  const maxSec = Math.max(3600, ...series.map(s => s.seconds || 0))
  const n = series.length
  const points = series.map((s, idx) => {
    const x = n === 1 ? 100 : 10 + (idx / (n - 1)) * 180
    const ratio = Math.min(1, Math.max(0, (s.seconds || 0) / maxSec))
    const y = 70 - ratio * 50
    return { x: Number(x.toFixed(1)), y: Number(y.toFixed(1)), ...s }
  })

  const pathLine = points.map((p, idx) => `${idx === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ')
  const pathArea = `${pathLine} L ${points[points.length - 1].x} 75 L ${points[0].x} 75 Z`

  // Pick up to 7 evenly spaced axis labels
  const labelIndices = n <= 7
    ? points.map((_, i) => i)
    : [0, Math.floor((n - 1) * 0.25), Math.floor((n - 1) * 0.5), Math.floor((n - 1) * 0.75), n - 1]
  const labels = labelIndices.map(i => points[i]?.label || '')

  const activePoint = hoveredSeriesIdx.value !== null && points[hoveredSeriesIdx.value]
    ? points[hoveredSeriesIdx.value]
    : points[points.length - 1]

  return {
    total: rangeData?.total ?? '0h 0m',
    sub: subLabel,
    trend: rangeData?.trend ?? null,
    points,
    pathLine,
    pathArea,
    activePoint,
    labels,
  }
}

const chartDataByRange = computed(() => ({
  week: buildSeriesChart(props.learningOverview?.week, currentLang.value === 'km' ? '៧ ថ្ងៃចុងក្រោយ' : 'Last 7 days'),
  last_week: buildSeriesChart(props.learningOverview?.last_week, currentLang.value === 'km' ? '៣០ ថ្ងៃចុងក្រោយ' : 'Last 30 days'),
  month: buildSeriesChart(props.learningOverview?.month, currentLang.value === 'km' ? '៩០ ថ្ងៃចុងក្រោយ' : 'Last 90 days'),
}))

// Upcoming Schedule (Pure DB Driven - Empty if none scheduled)
const upcomingEvents = computed(() => {
  return props.upcomingEvents || []
})

// AI Study Assistant Widget (Connected to Cloudflare AI Tutor backend & Database context)
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
    const qLower = promptText.toLowerCase()

    if (qLower.includes('example') || qLower.includes('ឧទាហរណ៍')) {
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? `នេះជាឧទាហរណ៍ជាក់ស្តែងសម្រាប់ "${courseTitle}":\n\n1. វិភាគសេណារីយ៉ូជាក់ស្តែង និងកំណត់គោលបំណងច្បាស់លាស់។\n2. អនុវត្តវិធីសាស្ត្រស្តង់ដារ និងច្បាប់គ្រឹះនៃមុខវិជ្ជា។\n3. ផ្ទៀងផ្ទាត់លទ្ធផលដើម្បីធានាភាពត្រឹមត្រូវ និងប្រសិទ្ធភាពខ្ពស់។`
          : `Here is a practical example for "${courseTitle}":\n\n1. Analyze the core requirements and establish clear milestones.\n2. Apply the subject-specific best practices and standard workflows.\n3. Verify results to achieve optimal performance and accuracy.`,
        time: isKm ? 'ឥឡូវនេះ' : 'Now'
      })
    } else if (qLower.includes('study') || qLower.includes('រៀន') || qLower.includes('recommend') || qLower.includes('plan')) {
      const avgLabel = props.stats?.averageScore && props.stats.averageScore !== '—' ? props.stats.averageScore : '—'
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? `ផ្អែកលើការវិភាគទិន្នន័យសិក្សាពិតរបស់អ្នកក្នុង Database:\n1. **បន្តរៀន ${lessonTitle}** ក្នុងវគ្គ "${courseTitle}" (វឌ្ឍនភាព ${continueCourseData.value?.progress || 0}%)។\n2. **រំលឹកចំណុចខ្សោយ** ក្នុង Quiz ដើម្បីបង្កើនពិន្ទុមធ្យម (${avgLabel})។\n3. **អនុវត្តសំណួរ ៥ សំណួរ** មុននឹងឈានទៅមេរៀនបន្ទាប់!`
          : `Based on your real database analytics:\n1. **Continue ${lessonTitle}** in "${courseTitle}" (${continueCourseData.value?.progress || 0}% watched).\n2. **Review weak areas** to raise your academic score (${avgLabel}).\n3. **Practice 5 drill questions** before your next milestone!`,
        time: isKm ? 'ឥឡូវនេះ' : 'Now'
      })
    } else if (qLower.includes('explain') || qLower.includes('ពន្យល់')) {
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? `ការពន្យល់សង្ខេបលើ "${courseTitle}": ផ្ដោតលើការយល់ដឹងពីទស្សនទានគ្រឹះ, រចនាសម្ព័ន្ធនៃមុខវិជ្ជា, និងការអនុវត្តជាក់ស្តែងក្នុងវិជ្ជាជីវៈ។`
          : `Concise explanation for "${courseTitle}": Focus on mastering core foundational principles, structured methodologies, and practical applications in the field.`,
        time: isKm ? 'ឥឡូវនេះ' : 'Now'
      })
    } else if (qLower.includes('summarize') || qLower.includes('សង្ខេប')) {
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? `សង្ខេបមេរៀន ${lessonTitle}: ចំណុចសំខាន់ៗរួមមាន និយមន័យគោល, ដំណាក់កាលអនុវត្ត, និងការដោះស្រាយបញ្ហាជាក់ស្តែង។`
          : `Summary of ${lessonTitle}: Key takeaways include foundational definitions, implementation procedures, and systematic problem solving.`,
        time: isKm ? 'ឥឡូវនេះ' : 'Now'
      })
    } else {
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? `សំណួរល្អណាស់អំពី "${promptText}" ក្នុងវគ្គ "${courseTitle}"! ខ្ញុំជា AI Tutor 24/7 របស់អ្នក រង់ចាំជួយពន្យល់គ្រប់ចំណុច។ តើអ្នកចង់ឱ្យខ្ញុំពន្យល់លម្អិត ឬផ្តល់កិច្ចការអនុវត្ត?`
          : `Great question regarding "${promptText}" in "${courseTitle}"! As your 24/7 AI tutor, I am ready to guide you. Would you like a step-by-step breakdown or a practice exercise?`,
        time: isKm ? 'ឥឡូវនេះ' : 'Now'
      })
    }
  }, 350)
}

const openAiTutorChat = (prompt?: string) => {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('open-ai-tutor', { detail: { prompt } }))
  }
}

const sendUserCustomAi = () => {
  if (!aiPromptInput.value.trim()) return
  const text = aiPromptInput.value.trim()
  aiPromptInput.value = ''
  handleSendAiPrompt(text)
  openAiTutorChat(text)
}

// Achievements Badges (Connected to Database)
const achievements = computed(() => {
  if (props.achievements && props.achievements.length > 0) {
    return props.achievements.map(a => ({
      ...a,
      title: currentLang.value === 'km' ? (a.title_km || a.title) : a.title,
      desc: currentLang.value === 'km' ? (a.desc_km || a.desc) : a.desc,
      progressText: currentLang.value === 'km' ? (a.progress_km || a.progress) : a.progress
    }))
  }
  return [
    {
      id: 'streak',
      title: currentLang.value === 'km' ? 'រៀន ៧ ថ្ងៃជាប់' : '7 Days Streak',
      desc: currentLang.value === 'km' ? 'រៀន ៧ ថ្ងៃជាប់គ្នាឥតដាច់' : 'Studied 7 days in a row without breaking streak',
      progressText: currentLang.value === 'km' ? '០/៧ ថ្ងៃ' : '0/7 Days',
      type: 'flame',
      color: 'from-amber-500 via-orange-500 to-rose-500',
      shadowColor: 'shadow-orange-500/30',
      href: '/student/certificates/achievements',
      unlocked: false
    },
    {
      id: 'quick',
      title: currentLang.value === 'km' ? 'រៀនរហ័ស' : 'Quick Learner',
      desc: currentLang.value === 'km' ? 'បញ្ចប់វគ្គសិក្សានិងមេរៀនតាមល្បឿនថេរ' : 'Completed lessons at steady pace',
      progressText: currentLang.value === 'km' ? '០ មេរៀន' : '0 lessons',
      type: 'book',
      color: 'from-emerald-400 via-teal-500 to-cyan-600',
      shadowColor: 'shadow-emerald-500/30',
      href: '/student/certificates/achievements',
      unlocked: false
    },
    {
      id: 'quiz_master',
      title: currentLang.value === 'km' ? 'ជើងខ្លាំង Quiz' : 'Quiz Master',
      desc: currentLang.value === 'km' ? 'ទទួលបានពិន្ទុ ៧៥%+ ក្នុង Quiz វគ្គសិក្សា' : 'Scored 75%+ in academic course quizzes',
      progressText: currentLang.value === 'km' ? '០ Quiz' : '0 Quizzes',
      type: 'star',
      color: 'from-violet-600 via-purple-600 to-fuchsia-600',
      shadowColor: 'shadow-purple-600/35',
      href: '/student/certificates/achievements',
      unlocked: false
    },
    {
      id: 'early_bird',
      title: currentLang.value === 'km' ? 'រៀនព្រលឹម' : 'Early Bird',
      desc: currentLang.value === 'km' ? 'ចូលរួមការសិក្សាពេលព្រលឹមបានទៀងទាត់' : 'Active morning study sessions completed',
      progressText: currentLang.value === 'km' ? '០ នាទី' : '0 mins',
      type: 'bolt',
      color: 'from-amber-400 via-yellow-500 to-amber-600',
      shadowColor: 'shadow-amber-500/30',
      href: '/student/certificates/achievements',
      unlocked: false
    },
    {
      id: 'top_performer',
      title: currentLang.value === 'km' ? 'លទ្ធផលឆ្នើម' : 'Top Performer',
      desc: currentLang.value === 'km' ? 'ទទួលបានវិញ្ញាបនបត្រផ្លូវការ និងនិទ្ទេសល្អ' : 'Earned verified certificates and honor grades',
      progressText: currentLang.value === 'km' ? '០ វិញ្ញាបនបត្រ' : '0 certs',
      type: 'trophy',
      color: 'from-yellow-400 via-amber-500 to-orange-600',
      shadowColor: 'shadow-amber-600/30',
      href: '/student/certificates/achievements',
      unlocked: false
    }
  ]
})

// Recent Activity (Pure Eloquent DB Driven - Empty if no recent activity)
const recentActivities = computed(() => {
  if (!props.recentActivities || props.recentActivities.length === 0) {
    return []
  }
  const rawList = props.recentActivities

  return rawList.map((a) => {
    const rawTitle = (currentLang.value === 'km' && a.title_km) ? a.title_km : (a.title || '')
    // Strip prefixes like "Watched:", "បានទស្សនា:", etc. completely
    const cleanTitle = rawTitle.replace(/^(Watched:\s*|បានទស្សនា:\s*|Completed Quiz:\s*|បានបញ្ចប់ Quiz:\s*|Downloaded:\s*|បានទាញយក:\s*)/i, '').trim()

    let category = (currentLang.value === 'km' && a.category_km) ? a.category_km : (a.category || (currentLang.value === 'km' ? 'មេរៀន' : 'Lesson'))
    if (rawTitle.toLowerCase().includes('quiz') || rawTitle.includes('កម្រងសំណួរ') || a.type === 'check') {
      category = currentLang.value === 'km' ? 'កម្រងសំណួរ' : 'Quiz'
    } else if (rawTitle.toLowerCase().includes('download') || rawTitle.includes('ទាញយក') || a.type === 'doc') {
      category = currentLang.value === 'km' ? 'ឯកសារ' : 'Resource'
    } else if (rawTitle.toLowerCase().includes('lab')) {
      category = currentLang.value === 'km' ? 'ការអនុវត្ត' : 'Lab'
    }

    const p = (a as any).progress ?? (a as any).percent ?? 0

    const rawTime = a.time || ''
    const shortTime = rawTime
      .replace(/(\d+)\s*hours?\s*ago/i, '$1h ago')
      .replace(/(\d+)\s*days?\s*ago/i, '$1d ago')
      .replace(/(\d+)\s*minutes?\s*ago/i, '$1m ago')
      .replace(/(\d+)\s*ម៉ោងមុន/i, '$1h មុន')
      .replace(/Today/i, 'Today')

    return {
      ...a,
      courseDisplay: currentLang.value === 'km' ? (a.course_km || a.course) : a.course,
      cleanTitle,
      category,
      time: rawTime,
      shortTime,
      progress: Math.min(100, Math.max(0, Math.round(p)))
    }
  })
})
</script>

<template>
  <StudentLayout
    :title="currentLang === 'km' ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard'"
    :breadcrumbs="[
      { label: currentLang === 'km' ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard' }
    ]"
  >
    <div class="student-dashboard space-y-6 pb-28 sm:pb-36 notranslate" translate="no" style="padding-bottom: 112px;">
      
      <!-- TOP GREETING & QUICK SHORTCUTS ROW (Compact WelcomeBanner) -->
      <WelcomeBanner
        :student-name="currentLang === 'km' ? (props.studentProfile?.name_kh || user?.name_kh || props.studentProfile?.name || user?.name || '') : (props.studentProfile?.name || user?.name || '')"
        :academic-term="props.academicTerm?.en || ''"
        :academic-term-km="props.academicTerm?.km || ''"
        :unread-announcements-count="props.unreadAnnouncementsCount ?? (page.props as any)?.student_notifications?.unread_count ?? 0"
        :current-lang="currentLang"
        :has-studied-before="hasStudiedBefore"
      />

      <!-- OFFICIAL SPI DASHBOARD LOADING & KPI SYSTEM (DL-01 to DL-08) -->
      <section :aria-busy="loading">
        <!-- 1. SKELETON LOADING STATE WITH SHIMMER (DL-01, DL-02, DL-06) -->
        <div
          v-if="loading && loadingStep === 'skeleton'"
          class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-4.5 animate-in fade-in duration-200"
        >
          <div
            v-for="n in 6"
            :key="n"
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-200 via-slate-100 to-slate-200 dark:from-[#1e1f20] dark:via-[#282a2c] dark:to-[#1e1f20] p-3.5 sm:p-4 flex flex-col justify-between shadow-xs border border-slate-300/60 dark:border-[#2d2f31] min-h-[136px] sm:min-h-[140px] max-h-[142px]"
          >
            <!-- Shimmer Highlight Sweep Effect -->
            <div class="shimmer-sweep absolute inset-0 pointer-events-none"></div>

            <div>
              <div class="flex items-center justify-between">
                <!-- Title Placeholder Bar -->
                <div class="h-3 w-20 bg-white/70 dark:bg-[#333538] rounded-md"></div>
                <!-- Icon Placeholder Square -->
                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/70 dark:bg-[#333538] shrink-0"></div>
              </div>
              <!-- Number Placeholder Block -->
              <div class="h-7 w-16 bg-white/80 dark:bg-[#333538] rounded-lg mt-2"></div>
              <!-- Subtitle Placeholder Bar -->
              <div class="h-2.5 w-24 bg-white/60 dark:bg-[#282a2c] rounded-md mt-1.5"></div>
            </div>

            <!-- Wave Silhouette Placeholder at Bottom -->
            <div class="mt-1.5 -mx-3.5 -mb-3.5 sm:-mx-4 sm:-mb-4 overflow-hidden rounded-b-2xl">
              <svg class="w-full h-7 sm:h-8 text-white/50 dark:text-[#282a2c]/60" viewBox="0 0 100 25" preserveAspectRatio="none">
                <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.4"/>
                <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.8"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- 2. ERROR STATE + RETRY BUTTON (DL-05) -->
        <div
          v-else-if="error"
          role="alert"
          class="rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
              </svg>
            </div>
            <div>
              <h4 class="text-xs font-bold text-rose-800 dark:text-rose-200">
                {{ currentLang === 'km' ? 'មិនអាចទាញយកទិន្នន័យស្ថិតិបានទេ' : 'Unable to load dashboard data.' }}
              </h4>
              <p class="text-[11px] text-rose-600 dark:text-rose-300">
                {{ error || (currentLang === 'km' ? 'សូមពិនិត្យមើលការតភ្ជាប់អ៊ីនធឺណិត ឬចុចប៊ូតុងខាងក្រោមដើម្បីព្យាយាមឡើងវិញ។' : 'Please check your connection or click retry to reload.') }}
              </p>
            </div>
          </div>
          <button
            @click="loadDashboard"
            type="button"
            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition-all hover:scale-105 cursor-pointer shrink-0"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            <span>{{ currentLang === 'km' ? 'ព្យាយាមម្តងទៀត' : 'Retry' }}</span>
          </button>
        </div>

        <!-- 3. LOADED / COUNTING GRADIENT CARDS (Staggered 70ms animation, hover translateY(-6px)) -->
        <div
          v-else
          class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-4.5"
        >
          <Link
            v-for="(stat, idx) in statsCards"
            :key="stat.type"
            :href="stat.href"
            prefetch="hover"
            cache-for="3m"
            :style="{ animationDelay: `${idx * 70}ms` }"
            :class="[
              'stat-card relative overflow-hidden rounded-2xl bg-gradient-to-br text-white p-3.5 sm:p-4 shadow-sm hover:shadow-xl transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] flex flex-col justify-between group cursor-pointer border border-white/10 min-h-[136px] sm:min-h-[140px] max-h-[142px]',
              stat.gradient
            ]"
          >
            <div>
              <div class="flex items-start justify-between gap-1">
                <span class="text-[9.5px] sm:text-[10px] xl:text-[11px] font-bold tracking-tight uppercase text-white/90 leading-tight mr-1">{{ stat.title }}</span>
                <div class="text-white/95 group-hover:text-white group-hover:scale-110 transition-transform duration-200 shrink-0">
                  <!-- Book SVG (Enrolled) -->
                  <svg v-if="stat.type === 'enrolled'" class="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                  </svg>
                  <!-- Graduation Cap SVG (In Progress) -->
                  <svg v-else-if="stat.type === 'in_progress'" class="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                    <path d="M6 12v5c3 3 9 3 12 0v-5" />
                  </svg>
                  <!-- Check Circle SVG (Completed) -->
                  <svg v-else-if="stat.type === 'completed'" class="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <path d="m9 12 2 2 4-4" />
                  </svg>
                  <!-- Trophy SVG (Certificates) -->
                  <svg v-else-if="stat.type === 'certificates'" class="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                    <path d="M4 22h16" />
                    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                  </svg>
                  <!-- Clock SVG (Study Time) -->
                  <svg v-else-if="stat.type === 'study_time'" class="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                  </svg>
                  <!-- Bar Chart SVG (Quiz Average) -->
                  <svg v-else class="w-7 h-7 sm:w-8 sm:h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10" />
                    <line x1="12" y1="20" x2="12" y2="4" />
                    <line x1="6" y1="20" x2="6" y2="14" />
                  </svg>
                </div>
              </div>

              <!-- Metric Number (Count-Up Active or Final Result) -->
              <h4 class="text-2xl font-black tracking-tight text-white mt-1 tabular-nums">
                {{ stat.value }}
              </h4>
              <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1 truncate">
                <span>{{ stat.subtitle }}</span>
              </p>
            </div>

            <!-- Sparkline Wave at Bottom (Exact Admin Style) -->
            <div class="mt-1.5 -mx-3.5 -mb-3.5 sm:-mx-4 sm:-mb-4 overflow-hidden rounded-b-2xl">
              <svg class="w-full h-7 sm:h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none" aria-hidden="true">
                <g transform="translate(0 3)" opacity="0.10">
                  <path :d="stat.wavePath" fill="currentColor"/>
                </g>
                <path :d="stat.wavePath" fill="currentColor" opacity="0.20"/>
                <path :d="stat.waveLine" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" opacity="0.9"/>
              </svg>
            </div>
          </Link>
        </div>
      </section>

      <!-- MAIN 2-COLUMN LAYOUT -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8 COLUMNS -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- FEATURED CONTINUE LEARNING CARD -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h2 class="text-[18px] font-semibold text-slate-900 dark:text-slate-100 tracking-tight">
                {{ currentLang === 'km' ? 'បន្តការសិក្សា' : 'Continue learning' }}
              </h2>
              <Link href="/student/my-courses/current" class="text-xs text-[#7045F5] dark:text-[#a78bfa] hover:underline font-semibold">
                {{ currentLang === 'km' ? 'មើលទាំងអស់' : 'View All' }}
              </Link>
            </div>

            <div v-if="continueCourseData" class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#7045F5] hover:shadow-lg hover:dark:border-[#7045F5]/60 rounded-3xl p-6 shadow-sm dark:shadow-xl flex flex-col md:flex-row items-center gap-5 sm:gap-6 relative overflow-hidden transition-all duration-300 group">
              
              <!-- Real Course Thumbnail for Continue Learning -->
              <Link :href="continueCourseData.href" class="relative w-full md:w-36 h-28 rounded-2xl overflow-hidden shrink-0 shadow-md bg-gradient-to-br from-purple-600/90 via-[#7045F5] to-slate-800 border border-[#dfe3e7] dark:border-[#2d2f31] flex items-center justify-center cursor-pointer">
                <img
                  v-if="continueCourseData.thumbnail"
                  :src="continueCourseData.thumbnail"
                  :alt="currentLang === 'km' ? (continueCourseData.title_kh || continueCourseData.title) : continueCourseData.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  loading="lazy"
                />
                <svg v-else class="w-9 h-9 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent flex items-end p-2">
                  <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-amber-400 text-slate-950 font-black text-[10px] shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span>{{ currentLang === 'km' ? 'សកម្ម' : 'Active' }}</span>
                  </span>
                </div>
              </Link>

              <!-- Course Info and Progress -->
              <div class="flex-1 w-full space-y-3 min-w-0">
                <div class="space-y-1">
                  <span class="px-2.5 py-0.5 rounded-full bg-purple-50 dark:bg-purple-900/20 text-[#7045F5] dark:text-[#a78bfa] text-[13px] font-semibold border border-purple-200 dark:border-purple-800/40 inline-block">
                    {{ currentLang === 'km' ? 'គ្រូបង្រៀន: ' : 'Teacher: ' }}{{ currentLang === 'km' ? (continueCourseData.teacher_kh || continueCourseData.teacher) : continueCourseData.teacher }}
                  </span>
                  <h3 class="text-base sm:text-lg font-bold text-[#1f1f1f] dark:text-[#e3e3e3] leading-tight truncate">
                    <Link :href="continueCourseData.href" class="hover:text-[#7045F5] dark:hover:text-[#a78bfa] transition-colors">
                      {{ currentLang === 'km' ? (continueCourseData.title_kh || continueCourseData.title) : continueCourseData.title }}
                    </Link>
                  </h3>
                  <p class="text-[13.5px] text-[#475569] dark:text-[#c4c7c5] font-medium truncate">
                    {{ currentLang === 'km' ? (continueCourseData.chapter_kh || continueCourseData.chapter) : continueCourseData.chapter }}
                  </p>
                </div>

                <!-- Progress Bar with smooth transition -->
                <div class="space-y-1.5">
                  <div class="w-full h-2 rounded-full bg-[#e1e3e1] dark:bg-[#282a2c] overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-[#7045F5] to-[#2563EB] rounded-full transition-all duration-900 ease-out" :style="{ width: continueCourseData.progress + '%' }"></div>
                  </div>
                  <div class="flex items-center justify-between text-[13.5px] text-[#475569] dark:text-[#c4c7c5] font-medium min-w-0">
                    <span class="truncate min-w-0 flex-1 mr-3">{{ currentLang === 'km' ? 'មេរៀនចុងក្រោយ: ' : 'Last lesson: ' }}{{ currentLang === 'km' ? (continueCourseData.lastLesson_kh || continueCourseData.lastLesson) : continueCourseData.lastLesson }}</span>
                    <span class="tabular-nums font-semibold flex items-center gap-1 shrink-0 whitespace-nowrap text-[#7045F5] dark:text-[#a78bfa]">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      {{ continueCourseData.timeLeft }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- CTA Button (Gradient Purple with White Text) -->
              <div class="w-full md:w-auto shrink-0 py-1">
                <Link
                  :href="continueCourseData.href"
                  class="w-full md:w-auto px-6 py-3.5 rounded-full bg-gradient-to-r from-[#7045F5] to-[#5b32e6] hover:from-[#5f35e0] hover:to-[#4e27cc] text-white font-semibold text-[13.5px] shadow-md shadow-[#7045F5]/25 flex items-center justify-center gap-2 hover:scale-105 active:scale-95 transition-all text-center cursor-pointer whitespace-nowrap shrink-0"
                >
                  <span>{{ currentLang === 'km' ? 'បន្តការសិក្សា' : 'Continue Learning' }}</span>
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </Link>
              </div>

            </div>

            <!-- Empty State when no course in progress -->
            <div v-else class="rounded-3xl bg-white dark:bg-[#1e1f20] border border-dashed border-[#dfe3e7] dark:border-[#2d2f31] p-8 text-center space-y-4 shadow-sm dark:shadow-xl">
              <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
              </div>
              <div class="space-y-1">
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                  {{ currentLang === 'km' ? 'មិនទាន់មានវគ្គសិក្សាកំពុងរៀននៅឡើយទេ' : 'No active course in progress' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                  {{ currentLang === 'km' ? 'ជ្រើសរើសវគ្គសិក្សាពីកាតាឡុក ដើម្បីចាប់ផ្ដើមការសិក្សារបស់អ្នក' : 'Explore the course catalog to start your learning journey.' }}
                </p>
              </div>
              <Link href="/student/browse" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white text-xs font-semibold shadow-md hover:scale-105 active:scale-95 transition-all">
                <span>{{ currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា' : 'Browse Courses' }}</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </Link>
            </div>
          </div>

          <!-- MY COURSES SECTION WITH FILTER TABS -->
          <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <h2 class="text-[18px] font-semibold text-slate-900 dark:text-slate-100 tracking-tight">
                  {{ currentLang === 'km' ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My courses' }}
                </h2>
                
                <!-- Filter Pills (Google One Segmented Track) -->
                <div class="flex items-center gap-1 bg-[#f0f4f9] dark:bg-[#131314] p-1 rounded-full border border-[#dfe3e7] dark:border-[#2d2f31]">
                  <button
                    @click="selectedCourseFilter = 'all'"
                    :class="[selectedCourseFilter === 'all' ? 'bg-[#0b57d0] dark:bg-[#a8c7fa] text-white dark:text-[#041e49] font-medium shadow-sm' : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-[#e3e3e3] dark:hover:bg-[#282a2c]', 'px-3.5 py-1 rounded-full text-xs transition-colors cursor-pointer']"
                  >
                    {{ currentLang === 'km' ? 'វគ្គសិក្សាទាំងអស់' : 'All Courses' }}
                  </button>
                  <button
                    @click="selectedCourseFilter = 'in_progress'"
                    :class="[selectedCourseFilter === 'in_progress' ? 'bg-[#0b57d0] dark:bg-[#a8c7fa] text-white dark:text-[#041e49] font-medium shadow-sm' : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-[#e3e3e3] dark:hover:bg-[#282a2c]', 'px-3.5 py-1 rounded-full text-xs transition-colors cursor-pointer']"
                  >
                    {{ currentLang === 'km' ? 'កំពុងសិក្សា' : 'In Progress' }}
                  </button>
                  <button
                    @click="selectedCourseFilter = 'completed'"
                    :class="[selectedCourseFilter === 'completed' ? 'bg-[#0b57d0] dark:bg-[#a8c7fa] text-white dark:text-[#041e49] font-medium shadow-sm' : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-[#e3e3e3] dark:hover:bg-[#282a2c]', 'px-3.5 py-1 rounded-full text-xs transition-colors cursor-pointer']"
                  >
                    {{ currentLang === 'km' ? 'បានបញ្ចប់' : 'Completed' }}
                  </button>
                  <button
                    @click="selectedCourseFilter = 'not_started'"
                    :class="[selectedCourseFilter === 'not_started' ? 'bg-[#0b57d0] dark:bg-[#a8c7fa] text-white dark:text-[#041e49] font-medium shadow-sm' : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-[#e3e3e3] dark:hover:bg-[#282a2c]', 'px-3.5 py-1 rounded-full text-xs transition-colors cursor-pointer']"
                  >
                    {{ currentLang === 'km' ? 'មិនទាន់ចាប់ផ្ដើម' : 'Not Started' }}
                  </button>
                </div>
              </div>

              <Link href="/student/my-courses/enrolled" class="text-xs text-[#0b57d0] dark:text-[#8ab4f8] hover:underline font-semibold self-start sm:self-auto">
                {{ currentLang === 'km' ? `មើលទាំងអស់ (${coursesToDisplay.length})` : `View All (${coursesToDisplay.length})` }}
              </Link>
            </div>

            <!-- Courses Grid (Spacious 2-3 Column Layout with Elegant Card Ratios) -->
            <div v-if="filteredCourses.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
              <Link
                v-for="c in filteredCourses"
                :key="c.id"
                :href="c.continue_url || c.href"
                class="course-card-scroll bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#7045F5] hover:dark:border-[#7045F5]/70 hover:dark:bg-[#232426] rounded-2xl p-4.5 sm:p-5 shadow-xs dark:shadow-xl flex flex-col justify-between space-y-3.5 transition-all duration-300 hover:-translate-y-1 hover:shadow-md group"
              >
                <!-- Real Representative Course Thumbnail Image -->
                <div class="relative w-full h-36 rounded-2xl overflow-hidden bg-gradient-to-br from-purple-600/90 via-[#7045F5] to-slate-800 border border-[#dfe3e7] dark:border-[#2d2f31] shadow-xs flex items-center justify-center">
                  <img
                    v-if="c.thumbnail"
                    :src="c.thumbnail"
                    :alt="currentLang === 'km' ? (c.title_kh || c.title) : c.title"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    loading="lazy"
                  />
                  <svg v-else class="w-10 h-10 text-white/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                  <!-- Vignette gradient overlay for text and badge contrast -->
                  <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-transparent to-black/20 pointer-events-none"></div>

                  <!-- Status Pill Badge on Card (Solid dark emerald/blue, WCAG AA compliant) -->
                  <span
                    :class="[
                      getCourseStatusBadgeClass(c.status),
                      'absolute top-2.5 right-2.5 px-3 py-1 rounded-full text-xs font-bold border backdrop-blur-md shadow-md'
                    ]"
                  >
                    {{ c.status === 'in_progress' ? (currentLang === 'km' ? 'កំពុងសិក្សា' : 'In Progress') : c.status === 'completed' ? (currentLang === 'km' ? 'បានបញ្ចប់' : 'Completed') : (currentLang === 'km' ? 'មិនទាន់ចាប់ផ្ដើម' : 'Not Started') }}
                  </span>
                </div>

                <!-- Course Title & Instructor (4px gap between title and teacher) -->
                <div class="space-y-1">
                  <h4 class="text-[15px] font-bold text-slate-900 dark:text-[#e2e8f0] group-hover:text-[#7045F5] dark:group-hover:text-[#a78bfa] transition-colors line-clamp-2 min-h-[2.5rem] leading-snug" :title="currentLang === 'km' ? (c.title_kh || c.title) : c.title">
                    {{ currentLang === 'km' ? (c.title_kh || c.title) : c.title }}
                  </h4>
                  <p class="text-[13px] text-[#475569] dark:text-[#94a3b8] flex items-center gap-1.5 font-medium">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    <span class="truncate">{{ currentLang === 'km' ? (c.teacher_kh || c.teacher) : c.teacher }}</span>
                  </p>
                </div>

                <!-- Progress Bar (6px = h-[6px]) & Link -->
                <div class="space-y-1.5 pt-1 border-t border-slate-100 dark:border-white/10">
                  <div class="w-full h-[6px] rounded-full bg-slate-100 dark:bg-white/10 overflow-hidden">
                    <div
                      :class="c.progress === 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-[#7045F5] to-[#2563EB]'"
                      class="h-full rounded-full transition-all duration-900 ease-out"
                      :style="{ width: c.progress + '%' }"
                    ></div>
                  </div>
                  <div class="flex items-center justify-between text-[13px]">
                    <span :class="c.progress === 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-[#7045F5] dark:text-[#a78bfa]'" class="font-bold tabular-nums">{{ c.progress }}%</span>
                    <span class="text-slate-500 dark:text-slate-400 font-semibold flex items-center gap-1 group-hover:text-[#7045F5] dark:group-hover:text-[#a78bfa] transition-colors">
                      <span>{{ c.progress === 100 ? (currentLang === 'km' ? 'ពិនិត្យឡើងវិញ' : 'Review') : (currentLang === 'km' ? 'រៀនបន្ត' : 'Continue') }}</span>
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                  </div>
                </div>
              </Link>
            </div>

            <!-- EMPTY STATE FOR COURSES -->
            <div v-else class="rounded-2xl bg-white dark:bg-[#1e1f20] border border-dashed border-[#dfe3e7] dark:border-[#2d2f31] p-8 text-center space-y-2">
              <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                {{ currentLang === 'km' ? 'មិនមានវគ្គសិក្សាក្នុងប្រភេទនេះនៅឡើយទេ' : 'No courses found in this category' }}
              </p>
              <Link href="/student/browse" class="inline-block text-xs text-purple-600 dark:text-purple-400 font-semibold hover:underline">
                {{ currentLang === 'km' ? 'ស្វែងរក និងចុះឈ្មោះវគ្គសិក្សា ↗' : 'Browse & enroll in courses ↗' }}
              </Link>
            </div>
          </div>
          <!-- ANALYTICS CHARTS ROW -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- Learning Overview Line Chart Card with Segmented Control (7D | 30D | 90D) inline in header -->
            <div
              class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 hover:dark:border-purple-400/60 rounded-3xl p-4 xl:p-5 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-3 overflow-hidden transition-all hover:-translate-y-1"
            >
              <!-- Row 1: Header with inline segmented control (wrap cleanly on narrow cards) -->
              <div class="flex flex-wrap items-center justify-between min-h-[32px] gap-1.5">
                <Link href="/student/progress/learning-time" class="flex items-center gap-1 text-[14px] font-semibold text-slate-900 dark:text-slate-100 hover:text-purple-600 dark:hover:text-purple-400 transition-colors group">
                  <span>{{ currentLang === 'km' ? 'ទិដ្ឋភាពទូទៅនៃការរៀន' : 'Learning overview' }}</span>
                  <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-purple-600 group-hover:translate-x-0.5 transition-all shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </Link>

                <!-- Segmented Control (7D | 30D | 90D) inline on right -->
                <div class="inline-flex items-center gap-0.5 p-0.5 bg-[#f0f4f9] dark:bg-[#131314] rounded-xl border border-[#dfe3e7] dark:border-[#2d2f31] shrink-0">
                  <button
                    type="button"
                    @click="selectedRange = 'week'"
                    :class="selectedRange === 'week' ? 'bg-[#6366f1] text-white font-bold shadow-xs' : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-white'"
                    class="px-1.5 py-0.5 rounded-lg text-[11px] font-semibold transition-all cursor-pointer"
                  >
                    7D
                  </button>
                  <button
                    type="button"
                    @click="selectedRange = 'last_week'"
                    :class="selectedRange === 'last_week' ? 'bg-[#6366f1] text-white font-bold shadow-xs' : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-white'"
                    class="px-1.5 py-0.5 rounded-lg text-[11px] font-semibold transition-all cursor-pointer"
                  >
                    30D
                  </button>
                  <button
                    type="button"
                    @click="selectedRange = 'month'"
                    :class="selectedRange === 'month' ? 'bg-[#6366f1] text-white font-bold shadow-xs' : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-white'"
                    class="px-1.5 py-0.5 rounded-lg text-[11px] font-semibold transition-all cursor-pointer"
                  >
                    90D
                  </button>
                </div>
              </div>

              <!-- SVG Line Chart with daily points & per-day tooltip (<= 24h/day) -->
              <div class="relative pt-3 my-auto" @mouseleave="hoveredSeriesIdx = null">
                <div class="flex items-center justify-between text-[11px] mb-1">
                  <span class="px-2.5 py-0.5 rounded-full bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white font-semibold shadow-xs tabular-nums">
                    {{ hoveredSeriesIdx !== null ? `${chartDataByRange[selectedRange].activePoint.label}: ${chartDataByRange[selectedRange].activePoint.formatted}` : chartDataByRange[selectedRange].total }}
                  </span>
                  <span class="text-slate-400 dark:text-slate-500 text-[10px]">{{ chartDataByRange[selectedRange].sub }}</span>
                </div>

                <svg viewBox="0 0 200 80" class="w-full h-20 overflow-visible">
                  <defs>
                    <linearGradient id="learningOverviewLineGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#818cf8" stop-opacity="0.35" />
                      <stop offset="100%" stop-color="#818cf8" stop-opacity="0.02" />
                    </linearGradient>
                  </defs>
                  <path
                    :d="chartDataByRange[selectedRange].pathArea"
                    fill="url(#learningOverviewLineGrad)"
                  />
                  <path
                    :d="chartDataByRange[selectedRange].pathLine"
                    fill="none"
                    stroke="#818cf8"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="line-chart-draw"
                  />
                  <circle
                    v-for="(pt, idx) in chartDataByRange[selectedRange].points"
                    :key="idx"
                    :cx="pt.x"
                    :cy="pt.y"
                    :r="hoveredSeriesIdx === idx ? 4.5 : (chartDataByRange[selectedRange].points.length <= 7 ? 3 : 1.5)"
                    :fill="hoveredSeriesIdx === idx ? '#6366f1' : '#818cf8'"
                    stroke="#ffffff"
                    stroke-width="1.5"
                    class="cursor-pointer transition-all"
                    @mouseenter="hoveredSeriesIdx = idx"
                  />
                </svg>

                <div class="flex justify-between text-xs text-[#444746] dark:text-[#c4c7c5] tabular-nums font-medium pt-1">
                  <span v-for="(lbl, idx) in chartDataByRange[selectedRange].labels" :key="idx">{{ lbl }}</span>
                </div>
              </div>

              <!-- Bottom summary stat row (Hidden when prev < 1800s -> trend is null) -->
              <div v-if="chartDataByRange[selectedRange].trend" class="pt-2 border-t border-slate-100 dark:border-white/5 flex items-center justify-between gap-2 text-[12px] text-slate-500 dark:text-slate-400">
                <span class="whitespace-nowrap">{{ currentLang === 'km' ? 'និន្នាការសិក្សា' : 'Learning Trend' }}</span>
                <span class="font-semibold text-purple-600 dark:text-purple-400 whitespace-nowrap tabular-nums">{{ chartDataByRange[selectedRange].trend }}</span>
              </div>
            </div>

            <!-- Study Time Donut Ring Chart Card (Uniform border, hover purple border) -->
            <Link
              href="/student/progress/learning-time"
              class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 hover:dark:border-purple-400/60 rounded-3xl p-4 xl:p-5 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-3 overflow-hidden transition-all hover:-translate-y-1 group"
            >
              <div class="flex items-center justify-between min-h-[32px] gap-1.5">
                <div class="flex items-center gap-1 text-[14px] font-semibold text-slate-900 dark:text-slate-100 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                  <span>{{ currentLang === 'km' ? 'ម៉ោងសិក្សា' : 'Study time' }}</span>
                  <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-purple-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </div>
                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 whitespace-nowrap">
                  {{ currentLang === 'km' ? '៧ ថ្ងៃ' : '7 days' }}
                </span>
              </div>

              <!-- Content centered vertically with responsive ring and aligned legend -->
              <div class="flex items-center justify-between gap-2.5 my-auto py-1 min-w-0">
                <!-- SVG Circular Donut -->
                <div class="relative w-[72px] h-[72px] xl:w-[82px] xl:h-[82px] shrink-0 flex items-center justify-center">
                  <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                    <path
                      class="text-[#e1e3e1] dark:text-[#282a2c]"
                      stroke-width="4"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      v-if="(props.studyTimeBreakdown?.lessonsPct ?? 0) > 0"
                      class="text-[#6366f1] donut-draw"
                      :stroke-dasharray="`${props.studyTimeBreakdown?.lessonsPct ?? 0}, 100`"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      v-if="(props.studyTimeBreakdown?.practicePct ?? 0) > 0"
                      class="text-purple-400 dark:text-[#a78bfa] donut-draw-2"
                      :stroke-dasharray="`${props.studyTimeBreakdown?.practicePct ?? 0}, 100`"
                      :stroke-dashoffset="`-${props.studyTimeBreakdown?.lessonsPct ?? 0}`"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      v-if="(props.studyTimeBreakdown?.quizPct ?? 0) > 0"
                      class="text-cyan-500 dark:text-cyan-400 donut-draw-2"
                      :stroke-dasharray="`${props.studyTimeBreakdown?.quizPct ?? 0}, 100`"
                      :stroke-dashoffset="`-${(props.studyTimeBreakdown?.lessonsPct ?? 0) + (props.studyTimeBreakdown?.practicePct ?? 0)}`"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-1.5">
                    <span class="text-[11px] font-extrabold text-[#1f1f1f] dark:text-[#e3e3e3] leading-tight tabular-nums">{{ formatCompactDuration(props.studyTimeBreakdown?.total || props.stats?.learningTime || '0h 0m') }}</span>
                    <span class="text-[9px] text-[#444746] dark:text-[#c4c7c5] mt-0.5">{{ currentLang === 'km' ? 'សរុប' : 'Total' }}</span>
                  </div>
                </div>

                <!-- Aligned Legend (never truncates Khmer labels) -->
                <div class="flex-1 min-w-0 space-y-1.5 text-[11px] xl:text-xs text-[#444746] dark:text-[#c4c7c5] font-medium">
                  <div class="flex items-center justify-between gap-1.5 min-w-0">
                    <span class="flex items-center gap-1.5 min-w-0">
                      <span class="w-2 h-2 rounded-full bg-[#6366f1] shrink-0"></span>
                      <span class="whitespace-nowrap">{{ currentLang === 'km' ? 'មេរៀន' : 'Lessons' }}</span>
                    </span>
                    <span class="font-semibold text-slate-700 dark:text-slate-200 tabular-nums shrink-0 whitespace-nowrap">{{ formatCompactDuration(props.studyTimeBreakdown?.lessons || '0h 0m') }}</span>
                  </div>
                  <div class="flex items-center justify-between gap-1.5 min-w-0">
                    <span class="flex items-center gap-1.5 min-w-0">
                      <span class="w-2 h-2 rounded-full bg-purple-400 dark:bg-[#a78bfa] shrink-0"></span>
                      <span class="whitespace-nowrap">{{ currentLang === 'km' ? 'ការអនុវត្ត' : 'Practice' }}</span>
                    </span>
                    <span class="font-semibold text-slate-700 dark:text-slate-200 tabular-nums shrink-0 whitespace-nowrap">{{ formatCompactDuration(props.studyTimeBreakdown?.practice || '0h 0m') }}</span>
                  </div>
                  <div class="flex items-center justify-between gap-1.5 min-w-0">
                    <span class="flex items-center gap-1.5 min-w-0">
                      <span class="w-2 h-2 rounded-full bg-cyan-500 dark:bg-cyan-400 shrink-0"></span>
                      <span class="whitespace-nowrap">{{ currentLang === 'km' ? 'កម្រងសំណួរ' : 'Quiz' }}</span>
                    </span>
                    <span class="font-semibold text-slate-700 dark:text-slate-200 tabular-nums shrink-0 whitespace-nowrap">{{ formatCompactDuration(props.studyTimeBreakdown?.quiz || '0h 0m') }}</span>
                  </div>
                </div>
              </div>

              <!-- Bottom summary stat row -->
              <div class="pt-2 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-[12px] text-slate-500 dark:text-slate-400">
                <span>{{ currentLang === 'km' ? 'មធ្យមភាគប្រចាំថ្ងៃ' : 'Daily average' }}</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ formatDailyAverage(props.studyTimeBreakdown?.dailyAverage) }}</span>
              </div>
            </Link>

            <!-- Quiz Performance Radial Gauge Card -->
            <Link
              :href="props.quizPerformance?.hasData ? '/student/quizzes/scores' : '/student/quizzes/practice'"
              class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 hover:dark:border-purple-400/60 rounded-3xl p-4 xl:p-5 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-3 overflow-hidden transition-all hover:-translate-y-1 group"
            >
              <div class="flex flex-wrap items-center justify-between min-h-[32px] gap-1.5">
                <div class="flex items-center gap-1 text-[14px] font-semibold text-slate-900 dark:text-slate-100 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                  <span>{{ currentLang === 'km' ? 'លទ្ធផលកម្រងសំណួរ' : 'Quiz performance' }}</span>
                  <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-purple-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </div>
                <span
                  v-if="props.quizPerformance?.hasData && props.quizPerformance?.trend"
                  class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 whitespace-nowrap shrink-0"
                >
                  {{ props.quizPerformance.trend }}
                </span>
                <span
                  v-else-if="!props.quizPerformance?.hasData"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700 whitespace-nowrap shrink-0"
                >
                  <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>{{ currentLang === 'km' ? 'គ្មានទិន្នន័យ' : 'No quiz data' }}</span>
                </span>
              </div>

              <!-- Content centered vertically with responsive ring and aligned legend -->
              <div class="flex items-center justify-between gap-2.5 my-auto py-1 min-w-0">
                <!-- Gauge Circle -->
                <div class="relative w-[72px] h-[72px] xl:w-[82px] xl:h-[82px] shrink-0 flex items-center justify-center">
                  <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                    <!-- Dashed gray ring when no quiz data, solid track when data exists -->
                    <path
                      class="text-slate-300 dark:text-slate-700"
                      stroke-width="3.5"
                      :stroke-dasharray="!props.quizPerformance?.hasData ? '3 3' : undefined"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      v-if="props.quizPerformance?.hasData"
                      class="text-emerald-500 dark:text-emerald-400 donut-draw"
                      :stroke-dasharray="`${props.quizPerformance?.averageScoreNum ?? 0}, 100`"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-sm font-black text-[#1f1f1f] dark:text-[#e3e3e3] leading-none tabular-nums">{{ props.quizPerformance?.hasData ? props.quizPerformance.averageScore : '—' }}</span>
                    <span class="text-[9px] text-[#747775] dark:text-[#8e918f] mt-0.5">{{ currentLang === 'km' ? 'មធ្យមភាគ' : 'Average' }}</span>
                  </div>
                </div>

                <!-- Aligned Legend or Take First Quiz CTA -->
                <div v-if="props.quizPerformance?.hasData" class="flex-1 min-w-0 space-y-1.5 text-[11px] xl:text-xs text-[#444746] dark:text-[#c4c7c5] font-medium">
                  <div class="flex items-center justify-between gap-1.5 min-w-0">
                    <span class="flex items-center gap-1.5 min-w-0 truncate">
                      <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 shrink-0"></span>
                      <span class="truncate">{{ currentLang === 'km' ? 'ត្រឹមត្រូវ' : 'Correct' }}</span>
                    </span>
                    <span class="font-semibold text-slate-700 dark:text-slate-200 tabular-nums shrink-0">{{ props.quizPerformance.correctPct }}</span>
                  </div>
                  <div class="flex items-center justify-between gap-1.5 min-w-0">
                    <span class="flex items-center gap-1.5 min-w-0 truncate">
                      <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                      <span class="truncate">{{ currentLang === 'km' ? 'មិនត្រឹមត្រូវ' : 'Incorrect' }}</span>
                    </span>
                    <span class="font-semibold text-slate-700 dark:text-slate-200 tabular-nums shrink-0">{{ props.quizPerformance.incorrectPct }}</span>
                  </div>
                </div>
                <div v-else class="flex-1 min-w-0 space-y-1.5">
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
                    {{ currentLang === 'km' ? 'មិនទាន់មានលទ្ធផល Quiz ទេ' : 'No attempts recorded yet' }}
                  </p>
                  <span class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 dark:text-purple-400 group-hover:underline">
                    <span>{{ currentLang === 'km' ? 'ធ្វើ Quiz ដំបូង' : 'Take your first quiz' }}</span>
                    <span>→</span>
                  </span>
                </div>
              </div>

              <!-- Bottom summary stat row -->
              <div class="pt-2 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-[12px] text-slate-500 dark:text-slate-400">
                <span>{{ currentLang === 'km' ? 'ពិន្ទុខ្ពស់បំផុត' : 'Highest score' }}</span>
                <span class="font-semibold tabular-nums" :class="props.quizPerformance?.hasData ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'">
                  {{ props.quizPerformance?.hasData ? props.quizPerformance.highestScore : '—' }}
                </span>
              </div>
            </Link>

          </div>

          <!-- RECENT ACTIVITY SECTION (Inside Column ឆ្វេង under Analytics) -->
          <div class="space-y-3.5">
            <!-- ៤. ចំណងជើងផ្នែក (18px semibold, "View full history" ពណ៌ស្វាយ ជាមួយព្រួញ) -->
            <div class="flex items-center justify-between">
              <h2 class="text-[18px] font-semibold text-slate-900 dark:text-slate-100 tracking-tight">
                {{ currentLang === 'km' ? 'សកម្មភាពសិក្សាថ្មីៗ' : 'Recent activity' }}
              </h2>
              <Link
                href="/student/progress/learning-time"
                class="text-[13.5px] font-semibold text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 transition-colors inline-flex items-center gap-1 group/history"
              >
                <span>{{ currentLang === 'km' ? 'មើលប្រវត្តិទាំងអស់' : 'View full history' }}</span>
                <svg class="w-3.5 h-3.5 group-hover/history:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
              </Link>
            </div>

            <!-- ៥. Layout (grid minmax 300px, gap 16px, 1 col mobile) -->
            <div v-if="recentActivities.length > 0" class="grid grid-cols-1 sm:[grid-template-columns:repeat(auto-fit,minmax(280px,1fr))] gap-4">
              <!-- ២. Card ទាំងមូលចុចបាន (cursor: pointer, link ទៅមេរៀន, hover translateY(-3px), border #a78bfa, shadow, Resume ›) -->
              <div
                v-for="(act, idx) in recentActivities"
                :key="act.id"
                role="link"
                tabindex="0"
                @click="router.visit(act.href)"
                @keydown.enter="router.visit(act.href)"
                class="recent-activity-card group relative flex items-start gap-3.5 p-4 rounded-2xl border transition-all duration-200 cursor-pointer bg-white dark:bg-[#1e1f20] border-slate-200 dark:border-white/[0.08] hover:-translate-y-[3px] hover:border-[#a78bfa] dark:hover:border-[#a78bfa] hover:shadow-lg hover:shadow-purple-500/10 dark:hover:shadow-black/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2"
                :style="{ '--stagger-index': idx }"
              >
                <!-- ១. ប៊ូតុង Play (សំខាន់បំផុត: 48×48px, mobile 52px, gradient #6366f1 → #7c3aed, icon ស 20px, shadow, ring, hover scale 1.08, active 0.95, focus-visible ring ស្វាយ) -->
                <Link
                  :href="act.href"
                  :aria-label="`${currentLang === 'km' ? 'បន្តមេរៀន' : 'Resume lesson'}: ${act.cleanTitle}`"
                  class="activity-play-btn focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2"
                  @click.stop
                >
                  <svg class="w-5 h-5 ml-0.5 fill-white text-white drop-shadow-xs" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="6 3 20 12 6 21 6 3"></polygon>
                  </svg>
                </Link>

                <!-- ៣. រចនាសម្ព័ន្ធមាតិកា (កុំកាត់អក្សរ, ម៉ោងតូច nowrap) -->
                <div class="flex-1 min-w-0 space-y-1">
                  <!-- បន្ទាត់ទី ១: "Lesson · 20h ago" (nowrap) + បង្ហាញអក្សរតូច "Resume" / ព្រួញ › នៅជ្រុងស្តាំពេល hover -->
                  <div class="flex items-center justify-between gap-1 text-[12px] text-slate-500 dark:text-slate-400 font-medium">
                    <span class="whitespace-nowrap shrink-0">{{ act.category }} · {{ act.shortTime }}</span>
                    <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 text-xs font-semibold text-purple-600 dark:text-purple-400 inline-flex items-center gap-0.5 shrink-0 whitespace-nowrap">
                      {{ currentLang === 'km' ? 'បន្ត' : 'Resume' }}
                      <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                      </svg>
                    </span>
                  </div>

                  <!-- បន្ទាត់ទី ២: ឈ្មោះមេរៀនពេញ អនុញ្ញាត ២ ជួរ (15px bold, line-clamp: 2, break-words) -->
                  <h4
                    class="font-bold text-[15px] text-slate-900 dark:text-[#e2e8f0] leading-snug line-clamp-2 min-h-[2.6rem] group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors break-words"
                    :title="act.cleanTitle"
                  >
                    {{ act.cleanTitle }}
                  </h4>

                  <!-- បន្ទាត់ទី ៣: ឈ្មោះវគ្គសិក្សា (14px #475569, 1 ជួរ truncate) -->
                  <p class="text-[14px] text-[#475569] dark:text-slate-400 truncate font-medium">
                    {{ act.courseDisplay }}
                  </p>

                  <!-- Progress bar តូច (4px) បង្ហាញដំណាក់កាលមេរៀន (ឧ. 35% watched) -->
                  <div class="pt-1.5 space-y-1">
                    <div class="flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                      <span>{{ currentLang === 'km' ? 'វឌ្ឍនភាព' : 'Progress' }}</span>
                      <span class="font-semibold text-slate-700 dark:text-slate-300">{{ act.progress }}% {{ currentLang === 'km' ? 'បានមើល' : 'watched' }}</span>
                    </div>
                    <div class="w-full h-1 bg-slate-100 dark:bg-white/10 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-gradient-to-r from-[#6366f1] to-[#7c3aed] rounded-full transition-all duration-300"
                        :style="{ width: `${act.progress}%` }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State for Recent Activities -->
            <div v-else class="rounded-2xl bg-white dark:bg-[#1e1f20] border border-dashed border-[#dfe3e7] dark:border-[#2d2f31] p-8 text-center text-xs text-slate-500 dark:text-slate-400">
              {{ currentLang === 'km' ? 'មិនទាន់មានសកម្មភាពថ្មីៗនៅឡើយទេ។ ចាប់ផ្តើមរៀនដើម្បីតាមដានវឌ្ឍនភាពរបស់អ្នក!' : 'No recent activities recorded yet. Complete lessons to track your learning journey here!' }}
            </div>
          </div>

          <!-- ACHIEVEMENTS BADGES CARD (Moved to Left Column to balance heights with Right Column) -->
          <div class="space-y-3.5 pt-2">
            <div class="flex items-center justify-between">
              <h2 class="text-[18px] font-semibold text-slate-900 dark:text-slate-100 tracking-tight">
                {{ currentLang === 'km' ? 'សមិទ្ធផលសិក្សា' : 'Achievements' }}
              </h2>
              <Link href="/student/certificates/achievements" class="text-[13.5px] text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-semibold inline-flex items-center gap-1 group/ach">
                <span>{{ currentLang === 'km' ? 'មើលទាំងអស់' : 'View all' }}</span>
                <svg class="w-3.5 h-3.5 group-hover/ach:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </Link>
            </div>

            <div class="bg-white dark:bg-[#1e1f20] border border-slate-200 dark:border-white/[0.08] hover:border-purple-400 dark:hover:border-purple-400/60 rounded-3xl p-5 sm:p-6 shadow-xs hover:shadow-lg transition-all duration-200">
              <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4">
                <Link
                  v-for="ach in achievements"
                  :key="ach.id || ach.title"
                  :href="ach.href"
                  class="relative flex flex-col items-center justify-between p-3.5 sm:p-4 rounded-2xl bg-slate-50/70 dark:bg-white/[0.03] hover:bg-white dark:hover:bg-[#252728] border border-slate-200/70 dark:border-white/[0.06] hover:border-purple-400 dark:hover:border-purple-500/40 group text-center cursor-pointer transition-all duration-300 hover:-translate-y-1 hover:shadow-md"
                >
                  <!-- Interactive Floating Tooltip on hover/focus -->
                  <div class="absolute -top-11 left-1/2 -translate-x-1/2 px-2.5 py-1 bg-slate-900 dark:bg-slate-800 text-white text-[11px] font-medium rounded-lg shadow-xl opacity-0 pointer-events-none group-hover:opacity-100 transition-all duration-200 whitespace-nowrap z-30 transform group-hover:-translate-y-1">
                    <span>{{ ach.desc }}</span>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-900 dark:border-t-slate-800"></div>
                  </div>

                  <!-- Status Pill (Unlocked checkmark vs In-progress lock) -->
                  <div
                    v-if="ach.unlocked"
                    class="absolute top-2 right-2 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold shadow-xs shadow-emerald-500/50 ring-2 ring-white dark:ring-[#1e1f20] z-10"
                    :title="currentLang === 'km' ? 'ដោះសោរួច' : 'Unlocked'"
                  >
                    ✓
                  </div>
                  <div
                    v-else
                    class="absolute top-2 right-2 w-5 h-5 rounded-full bg-slate-200/90 dark:bg-white/10 text-slate-500 dark:text-slate-400 flex items-center justify-center text-[9px] ring-2 ring-white dark:ring-[#1e1f20] z-10"
                    :title="currentLang === 'km' ? 'កំពុងដំណើរការ' : 'In Progress'"
                  >
                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                  </div>

                  <!-- Vibrant Glowing Squircle Badge Icon -->
                  <div :class="['w-13 h-13 sm:w-14 sm:h-14 rounded-[18px] bg-gradient-to-br', ach.color, 'flex items-center justify-center text-white shadow-lg', ach.shadowColor || 'shadow-purple-500/30', 'group-hover:scale-105 group-hover:shadow-xl transition-all duration-300 ring-4 ring-white dark:ring-white/10']">
                    <!-- Flame / Streak SVG -->
                    <svg v-if="ach.type === 'flame'" class="w-7 h-7 drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"/></svg>

                    <!-- Book SVG -->
                    <svg v-else-if="ach.type === 'book'" class="w-7 h-7 drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>

                    <!-- 4-point Sparkle Star SVG (Matches Image 1 reference precisely) -->
                    <svg v-else-if="ach.type === 'star'" class="w-7 h-7 drop-shadow-md" viewBox="0 0 24 24" fill="none">
                      <path d="M12 2C12 7.52285 7.52285 12 2 12C7.52285 12 12 16.4772 12 22C12 16.4772 16.4772 12 22 12C16.4772 12 12 7.52285 12 2Z" fill="url(#quiz-star-gradient)"/>
                      <defs>
                        <linearGradient id="quiz-star-gradient" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                          <stop offset="0%" stop-color="#ffffff"/>
                          <stop offset="45%" stop-color="#fed7aa"/>
                          <stop offset="100%" stop-color="#f43f5e"/>
                        </linearGradient>
                      </defs>
                    </svg>

                    <!-- Lightning Bolt SVG -->
                    <svg v-else-if="ach.type === 'bolt'" class="w-7 h-7 drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>

                    <!-- Trophy SVG -->
                    <svg v-else class="w-7 h-7 drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a3 3 0 002.828-2.002l1.636-4.908A1.125 1.125 0 0017.896 6H6.104a1.125 1.125 0 00-1.068 1.465l1.636 4.908a3 3 0 002.828 2.002z"/></svg>
                  </div>

                  <!-- Text Block: Title + Progress chip -->
                  <div class="mt-2.5 flex flex-col items-center gap-1 w-full">
                    <span class="text-[13px] font-bold text-slate-800 dark:text-slate-100 leading-tight text-center line-clamp-1 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                      {{ ach.title }}
                    </span>
                    <span
                      v-if="ach.progressText"
                      :class="['text-[10px] font-medium px-2 py-0.5 rounded-full inline-block truncate max-w-full tracking-wide', ach.unlocked ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-semibold' : 'bg-slate-100 dark:bg-white/[0.06] text-slate-500 dark:text-slate-400']"
                    >
                      {{ ach.progressText }}
                    </span>
                  </div>
                </Link>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT 4 COLUMNS -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- TODAY'S GOAL WIDGET -->
          <TodaysGoalWidget
            :initial-goals="props.todayGoals"
            :user-id="user?.id"
            :current-lang="currentLang"
          />

          <!-- AI RECOMMENDED FOR YOU CARD (Uniform thin border, single header, no 16px text) -->
          <div class="relative overflow-hidden bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#0b57d0] dark:hover:border-[#8ab4f8] rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3.5 transition-all">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <!-- Google Gemini / AI Sparkle Icon Badge (Crisp High-DPI, Premium Squircle) -->
                <div class="relative flex items-center justify-center w-7.5 h-7.5 rounded-xl bg-gradient-to-br from-violet-600 via-indigo-600 to-purple-600 text-white shadow-md shadow-indigo-500/25 ring-1 ring-white/20 dark:ring-white/10 shrink-0 select-none">
                  <svg class="w-4 h-4 drop-shadow-[0_1px_2px_rgba(0,0,0,0.35)]" viewBox="0 0 24 24" fill="none">
                    <!-- Primary Gemini 4-Point Star (Crisp luminous white for maximum contrast & beauty) -->
                    <path d="M12 2C12 7.52285 7.52285 12 2 12C7.52285 12 12 16.4772 12 22C12 16.4772 16.4772 12 22 12C16.4772 12 12 7.52285 12 2Z" fill="#ffffff" />
                    <!-- Companion Sparkle Accent -->
                    <path d="M19.5 2.5C19.5 4.15685 18.1569 5.5 16.5 5.5C18.1569 5.5 19.5 6.84315 19.5 8.5C19.5 6.84315 20.8431 5.5 22.5 5.5C20.8431 5.5 19.5 4.15685 19.5 2.5Z" fill="#e0e7ff" fill-opacity="0.9" />
                    <defs>
                      <linearGradient id="geminiSparkleHead" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#4285F4" />
                        <stop offset="35%" stop-color="#9B72CB" />
                        <stop offset="70%" stop-color="#EA4335" />
                        <stop offset="100%" stop-color="#FBBC05" />
                      </linearGradient>
                    </defs>
                  </svg>
                </div>
                <h3 class="text-[18px] font-semibold text-slate-900 dark:text-slate-100">
                  {{ currentLang === 'km' ? 'AI ណែនាំសម្រាប់អ្នក' : 'AI recommended for you' }}
                </h3>
              </div>

              <Link href="/student/ai-path/recommended" class="text-[13.5px] text-[#0b57d0] dark:text-[#8ab4f8] hover:underline font-semibold">
                {{ currentLang === 'km' ? 'ផែនទីសិក្សា' : 'Roadmap' }}
              </Link>
            </div>

            <p class="text-[14px] text-slate-600 dark:text-slate-300 font-medium">
              {{ currentLang === 'km' ? 'ផ្អែកលើទិន្នន័យសិក្សា និងលទ្ធផល Quiz របស់អ្នកក្នុង Database:' : 'Based on your real quiz scores & learning progress:' }}
            </p>

            <div v-if="aiRecommendations.length > 0" class="space-y-2.5">
              <div
                v-for="rec in aiRecommendations"
                :key="rec.id"
                class="p-3.5 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] space-y-2"
              >
                <div class="flex items-center justify-between gap-2">
                  <h4 class="font-bold text-slate-900 dark:text-slate-100 text-[15px] leading-snug">{{ rec.title }}</h4>
                  <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#d3e3fd] text-[#041e49] border border-[#0b57d0]/20 dark:bg-[#1a73e8]/20 dark:text-[#a8c7fa] dark:border-[#1a73e8]/30 shrink-0 whitespace-nowrap">
                    {{ rec.badge }}
                  </span>
                </div>
                <p class="text-[14px] text-slate-600 dark:text-slate-300 leading-relaxed">{{ rec.desc }}</p>
                
                <div class="pt-1 flex items-center justify-between">
                  <!-- Action Button: purple-blue gradient (#6366f1 -> #7c3aed) + white text (WCAG AA) -->
                  <Link
                    :href="rec.actionHref"
                    class="inline-flex items-center gap-1.5 px-5 py-2 rounded-full text-xs font-semibold shadow-md shadow-indigo-500/25 transition-all hover:scale-105 active:scale-95 bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:from-[#4f46e5] hover:to-[#6d28d9] text-white"
                  >
                    <span>{{ rec.actionLabel }}</span>
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                  </Link>

                  <!-- Gemini Signature Branding -->
                  <div class="flex items-center gap-1 text-[13.5px] text-[#475569] dark:text-[#8e918f] font-medium">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                      <path d="M12 2C12 7.52285 7.52285 12 2 12C7.52285 12 12 16.4772 12 22C12 16.4772 16.4772 12 22 12C16.4772 12 12 7.52285 12 2Z" fill="url(#geminiSparkleHead)" />
                    </svg>
                    <span>Gemini AI</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State when no rows exist in `ai_recommendations` for this student -->
            <div v-else class="p-5 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] border border-dashed border-[#dfe3e7] dark:border-[#2d2f31] text-center space-y-2.5">
              <p class="text-xs font-medium text-slate-600 dark:text-slate-300">
                {{ currentLang === 'km' ? 'មិនទាន់មានការណែនាំពី AI សម្រាប់គណនីរបស់អ្នកនៅឡើយទេ។' : 'No AI study recommendations generated for your account yet.' }}
              </p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                {{ currentLang === 'km' ? 'សូមចូលរៀនមេរៀន ឬធ្វើ Quiz ដើម្បីឲ្យ AI វិភាគ និងណែនាំ។' : 'Complete lessons or quizzes so AI can analyze and recommend next steps.' }}
              </p>
              <Link
                href="/student/browse"
                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white shadow-sm hover:opacity-95 transition"
              >
                <span>{{ currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា' : 'Browse Courses' }}</span>
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </Link>
            </div>
          </div>

          <!-- UPCOMING SCHEDULE CARD -->
          <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-[#dfe3e7] dark:border-[#2d2f31] pb-2">
              <h3 class="text-[18px] font-semibold text-slate-900 dark:text-slate-100">
                {{ currentLang === 'km' ? 'កាលវិភាគខាងមុខ' : 'Upcoming schedule' }}
              </h3>
              <Link href="/student/calendar" class="text-[13px] text-[#0b57d0] dark:text-purple-400 hover:underline font-semibold">
                {{ currentLang === 'km' ? 'មើលប្រតិទិន' : 'View Calendar' }}
              </Link>
            </div>

            <div v-if="upcomingEvents.length > 0" class="space-y-3">
              <Link
                v-for="item in upcomingEvents"
                :key="item.id"
                :href="item.href"
                class="flex items-center gap-3 p-3 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#0b57d0] hover:dark:border-[#1a73e8]/70 text-xs transition-all hover:bg-[#f0f4f9] dark:hover:bg-[#282a2c] group"
              >
                <!-- Calendar Date Badge on Left (NOV 17 style) -->
                <div class="w-12 h-12 rounded-xl bg-gradient-to-b from-[#0b57d0] to-[#1557bf] dark:from-[#1a73e8] dark:to-[#0b57d0] text-white flex flex-col items-center justify-center shrink-0 shadow-xs border border-white/10 group-hover:scale-105 transition-transform duration-200">
                  <span class="text-[9px] font-black uppercase tracking-wider leading-none text-white/90">
                    {{ getEventDateBadge(item).month }}
                  </span>
                  <span class="text-sm font-black tabular-nums leading-none mt-1">
                    {{ getEventDateBadge(item).day }}
                  </span>
                </div>

                <!-- Event Title & Full Width Details (2 lines, not truncated with ...) -->
                <div class="flex-1 min-w-0 space-y-1">
                  <div class="flex items-center gap-2">
                    <span
                      :class="[
                        item.type === 'quiz' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : item.type === 'live' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                        'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider'
                      ]"
                    >
                      {{ item.type }}
                    </span>
                    <span class="text-xs text-[#444746] dark:text-[#c4c7c5] tabular-nums font-medium">• {{ item.time }}</span>
                  </div>
                  <h4 class="font-bold text-slate-900 dark:text-slate-100 text-[14px] leading-snug line-clamp-2 group-hover:text-[#0b57d0] dark:group-hover:text-[#8ab4f8] transition-colors" :title="currentLang === 'km' ? (item.title_km || item.title) : item.title">
                    {{ currentLang === 'km' ? (item.title_km || item.title) : item.title }}
                  </h4>
                  <p class="text-[13px] text-slate-500 dark:text-slate-400 truncate font-medium">
                    {{ currentLang === 'km' ? (item.course_km || item.course) : item.course }}
                  </p>
                </div>
              </Link>
            </div>

            <!-- Empty State for Upcoming Events -->
            <div v-else class="p-6 text-center text-xs text-slate-500 dark:text-slate-400 bg-[#f8fafd] dark:bg-[#131314] rounded-2xl border border-dashed border-[#dfe3e7] dark:border-[#2d2f31]">
              {{ currentLang === 'km' ? 'គ្មានកាលវិភាគ Quiz ឬកាលកំណត់ខាងមុខទេ' : 'No upcoming quizzes or deadlines. You are all caught up!' }}
            </div>
          </div>

          <!-- AI STUDY ASSISTANT WIDGET (Premium AI Design with Kantumruy Pro) -->
          <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3.5 transition-all">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="relative flex items-center justify-center p-1 rounded-xl bg-gradient-to-tr from-purple-500/10 via-indigo-500/10 to-pink-500/10 border border-purple-200/50 dark:border-purple-800/30">
                  <img
                    :src="'/images/ai-robot-avatar.png'"
                    alt="AI Tutor"
                    class="w-7 h-7 rounded-lg object-contain shrink-0 select-none drop-shadow-xs"
                  />
                  <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-[#1e1f20]"></span>
                </div>
                <div>
                  <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-khmer flex items-center gap-1.5 leading-snug">
                    <span>{{ currentLang === 'km' ? 'ជំនួយការសិក្សា AI' : 'AI Study Assistant' }}</span>
                    <span class="text-xs px-1.5 py-0.5 rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 font-sans font-bold">24/7</span>
                  </h3>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 font-khmer">
                    {{ currentLang === 'km' ? 'ឆ្លើយសំណួរមេរៀន និងពន្យល់លំហាត់' : 'Lesson explanations & study guidance' }}
                  </p>
                </div>
              </div>
              <button
                type="button"
                @click="openAiTutorChat()"
                class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-500/15 hover:bg-indigo-100 dark:hover:bg-indigo-500/25 text-indigo-700 dark:text-indigo-300 text-xs font-semibold font-khmer border border-indigo-200/70 dark:border-indigo-500/30 hover:border-indigo-300 transition-all cursor-pointer flex items-center gap-1 shadow-2xs active:scale-95"
              >
                <span>{{ currentLang === 'km' ? 'ជជែកពេញលេញ' : 'Full Chat' }}</span>
                <span class="text-indigo-500 dark:text-indigo-400">↗</span>
              </button>
            </div>

            <!-- Messages Stream -->
            <div class="space-y-2.5 max-h-48 overflow-y-auto custom-scrollbar pr-1">
              <div
                v-for="(msg, idx) in aiMessages"
                :key="idx"
                :class="[
                  msg.role === 'user'
                    ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl rounded-tr-xs shadow-xs ml-6 p-3'
                    : 'bg-gradient-to-br from-indigo-50/70 via-white to-purple-50/40 dark:from-[#1b1c2b] dark:via-[#1e1f20] dark:to-[#1e1f20] border border-indigo-100/90 dark:border-indigo-900/30 text-slate-800 dark:text-slate-100 rounded-2xl rounded-tl-xs shadow-2xs mr-2 p-3.5',
                  'text-xs space-y-1 transition-all'
                ]"
              >
                <div v-if="msg.role === 'ai'" class="flex items-center gap-1.5 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 mb-1 select-none">
                  <span class="inline-block animate-pulse">✨</span>
                  <span>SPI AI Tutor 24/7</span>
                </div>
                <p class="whitespace-pre-wrap leading-relaxed font-khmer text-xs sm:text-[13px] select-text">{{ msg.text }}</p>
              </div>

              <div v-if="isAiTyping" class="p-3 rounded-2xl bg-indigo-50/50 dark:bg-[#1a1c22] border border-indigo-100 dark:border-indigo-950 text-indigo-700 dark:text-indigo-300 text-xs flex items-center gap-2 font-khmer">
                <span class="flex h-2 w-2 relative">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-600"></span>
                </span>
                <span class="font-medium">{{ currentLang === 'km' ? 'AI Tutor កំពុងវិភាគចម្លើយ...' : 'AI Tutor is thinking...' }}</span>
              </div>
            </div>

            <!-- Quick Action Prompt Chips (High-End Interactive Chips with Icons) -->
            <div class="grid grid-cols-2 gap-2 pt-1 font-khmer">
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'ពន្យល់មេរៀននេះ' : 'Explain this lesson')"
                type="button"
                class="group flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl bg-white dark:bg-[#25272a] hover:bg-gradient-to-r hover:from-amber-50 hover:to-orange-50 dark:hover:from-amber-950/25 dark:hover:to-orange-950/25 border border-slate-200 dark:border-[#383a3d] hover:border-amber-300 dark:hover:border-amber-500/50 text-slate-700 dark:text-slate-200 hover:text-amber-800 dark:hover:text-amber-300 text-xs font-semibold transition-all duration-200 shadow-2xs hover:shadow-xs active:scale-97 cursor-pointer"
              >
                <span class="text-amber-500 group-hover:scale-110 transition-transform shrink-0">💡</span>
                <span class="truncate">{{ currentLang === 'km' ? 'ពន្យល់មេរៀននេះ' : 'Explain this lesson' }}</span>
              </button>
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'ផ្តល់ឧទាហរណ៍' : 'Give me an example')"
                type="button"
                class="group flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl bg-white dark:bg-[#25272a] hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-blue-950/25 dark:hover:to-indigo-950/25 border border-slate-200 dark:border-[#383a3d] hover:border-blue-300 dark:hover:border-blue-500/50 text-slate-700 dark:text-slate-200 hover:text-blue-800 dark:hover:text-blue-300 text-xs font-semibold transition-all duration-200 shadow-2xs hover:shadow-xs active:scale-97 cursor-pointer"
              >
                <span class="text-blue-500 group-hover:scale-110 transition-transform shrink-0">📝</span>
                <span class="truncate">{{ currentLang === 'km' ? 'ផ្តល់ឧទាហរណ៍' : 'Give me an example' }}</span>
              </button>
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'សង្ខេបប្រធានបទនេះ' : 'Summarize this topic')"
                type="button"
                class="group flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl bg-white dark:bg-[#25272a] hover:bg-gradient-to-r hover:from-emerald-50 hover:to-teal-50 dark:hover:from-emerald-950/25 dark:hover:to-teal-950/25 border border-slate-200 dark:border-[#383a3d] hover:border-emerald-300 dark:hover:border-emerald-500/50 text-slate-700 dark:text-slate-200 hover:text-emerald-800 dark:hover:text-emerald-300 text-xs font-semibold transition-all duration-200 shadow-2xs hover:shadow-xs active:scale-97 cursor-pointer"
              >
                <span class="text-emerald-500 group-hover:scale-110 transition-transform shrink-0">📌</span>
                <span class="truncate">{{ currentLang === 'km' ? 'សង្ខេបប្រធានបទនេះ' : 'Summarize this topic' }}</span>
              </button>
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'តើខ្ញុំគួររៀនអ្វីថ្ងៃនេះ?' : 'What should I study today?')"
                type="button"
                class="group flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl bg-white dark:bg-[#25272a] hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 dark:hover:from-purple-950/25 dark:hover:to-pink-950/25 border border-slate-200 dark:border-[#383a3d] hover:border-purple-300 dark:hover:border-purple-500/50 text-slate-700 dark:text-slate-200 hover:text-purple-800 dark:hover:text-purple-300 text-xs font-semibold transition-all duration-200 shadow-2xs hover:shadow-xs active:scale-97 cursor-pointer"
              >
                <span class="text-purple-500 group-hover:scale-110 transition-transform shrink-0">🎯</span>
                <span class="truncate">{{ currentLang === 'km' ? 'តើខ្ញុំគួររៀនអ្វី?' : 'What should I study?' }}</span>
              </button>
            </div>

            <!-- Input Form (Modern Floating Input Bar) -->
            <form @submit.prevent="sendUserCustomAi" class="relative flex items-center pt-1 font-khmer">
              <input
                v-model="aiPromptInput"
                type="text"
                :placeholder="currentLang === 'km' ? 'សួរសំណួរទៅកាន់ AI Tutor របស់អ្នក...' : 'Ask your AI academic tutor...'"
                class="w-full pl-4 pr-11 py-2.5 rounded-full bg-[#f8fafd] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] text-xs text-[#1f1f1f] dark:text-[#e3e3e3] placeholder-[#747775] dark:placeholder-[#8e918f] focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 shadow-2xs transition-all"
              />
              <button
                type="submit"
                :disabled="!aiPromptInput.trim() || isAiTyping"
                class="absolute right-1.5 p-2 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 disabled:opacity-40 text-white transition-all cursor-pointer shadow-xs active:scale-95 flex items-center justify-center"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </form>
          </div>

        </div>

      </div>

    </div>
  </StudentLayout>
</template>

<style scoped>
/* Stat Cards Staggered Animation & Hover Physics */
.stat-card {
  animation: statCardEnter 600ms cubic-bezier(0.16, 1, 0.3, 1) both;
  transition: transform 300ms cubic-bezier(0.16, 1, 0.3, 1), box-shadow 300ms cubic-bezier(0.16, 1, 0.3, 1);
}
.stat-card:hover {
  transform: translateY(-6px);
}
@keyframes statCardEnter {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Course Cards Scroll-in Animation */
.course-card-scroll {
  opacity: 0;
  transform: translateY(16px);
  transition: opacity 500ms cubic-bezier(0.16, 1, 0.3, 1), transform 500ms cubic-bezier(0.16, 1, 0.3, 1), box-shadow 300ms cubic-bezier(0.16, 1, 0.3, 1);
}
.course-card-scroll.in-view {
  opacity: 1;
  transform: translateY(0);
}
.course-card-scroll.in-view:hover {
  transform: translateY(-4px);
}

/* Line Chart SVG Draw Animation */
.line-chart-draw {
  stroke-dasharray: 400;
  stroke-dashoffset: 400;
  animation: drawLine 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes drawLine {
  to {
    stroke-dashoffset: 0;
  }
}

/* Donut Ring Draw Animation */
.donut-draw {
  animation: drawDonut 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes drawDonut {
  from {
    stroke-dashoffset: 100;
  }
  to {
    stroke-dashoffset: 0;
  }
}

.donut-draw-2 {
  animation: drawDonut2 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes drawDonut2 {
  from {
    stroke-dashoffset: 100;
  }
  to {
    stroke-dashoffset: -55;
  }
}

/* Today's Goal Checkbox Bounce */
.goal-checkbox-bounce {
  animation: checkBounce 300ms cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes checkBounce {
  0% { transform: scale(0.85); }
  50% { transform: scale(1.15); }
  100% { transform: scale(1); }
}

@keyframes shimmerSweep {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.shimmer-sweep {
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.45) 50%,
    rgba(255, 255, 255, 0) 100%
  );
  animation: shimmerSweep 1.8s infinite;
}

/* Recent Activity Play Button & Stagger Enter Animation */
.activity-play-btn {
  width: 48px;
  height: 48px;
  border-radius: 9999px;
  background: linear-gradient(135deg, #6366f1 0%, #7c3aed 100%);
  box-shadow: 0 6px 14px rgba(124, 58, 237, 0.35), 0 0 0 4px rgba(124, 58, 237, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1), box-shadow 200ms cubic-bezier(0.16, 1, 0.3, 1);
}

@media (max-width: 640px) {
  .activity-play-btn {
    width: 52px;
    height: 52px;
  }
}

.activity-play-btn:hover,
.recent-activity-card:hover .activity-play-btn {
  transform: scale(1.08);
  box-shadow: 0 8px 22px rgba(124, 58, 237, 0.52), 0 0 0 4px rgba(124, 58, 237, 0.22);
}

.activity-play-btn:active,
.recent-activity-card:active .activity-play-btn {
  transform: scale(0.95);
}

.activity-play-btn:focus-visible {
  outline: none;
  box-shadow: 0 6px 14px rgba(124, 58, 237, 0.35), 0 0 0 4px #a855f7, 0 0 0 6px rgba(124, 58, 237, 0.3);
}

@keyframes recentActivityFadeIn {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.recent-activity-card {
  opacity: 0;
  transform: translateY(14px);
  will-change: transform, opacity;
}

.recent-activity-card.in-view {
  animation: recentActivityFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  animation-delay: calc(var(--stagger-index, 0) * 70ms);
}

@media (prefers-reduced-motion: reduce) {
  .stat-card,
  .donut-draw,
  .donut-draw-2,
  .goal-checkbox-bounce,
  .shimmer-sweep,
  .course-card-scroll,
  .recent-activity-card,
  .activity-play-btn,
  .line-chart-draw {
    animation: none !important;
    transition: none !important;
    opacity: 1 !important;
    transform: none !important;
  }
}

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
  background: #3c4043;
}
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #5f6368;
}
/* Respect prefers-reduced-motion */
@media (prefers-reduced-motion: reduce) {
  .donut-draw,
  .donut-draw-2,
  .line-chart-draw,
  .shimmer-sweep,
  .recent-activity-card {
    animation: none !important;
    transition: none !important;
  }
}
</style>
