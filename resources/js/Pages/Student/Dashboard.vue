<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import TodaysGoalWidget from '@/Components/Student/TodaysGoalWidget.vue'
import WelcomeBanner from '@/Components/Student/WelcomeBanner.vue'
import DashboardKpiStrip from '@/Components/Student/DashboardKpiStrip.vue'
import DashboardAnalyticsRow, { type MetricsPayload } from '@/Components/Student/DashboardAnalyticsRow.vue'
import { i18n } from '@/Services/i18n'

const props = defineProps<{
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
    title: string
    chapter: string
    teacher: string
    progress: number
    lastLesson: string
    timeLeft: string
    thumbnail?: string
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
    bgGradient?: string
    thumbnail?: string
    href: string
  }>
  recentActivities?: Array<{
    id: string | number
    type: 'play' | 'check' | 'doc'
    iconBg?: string
    title: string
    title_km?: string
    course: string
    time: string
    href: string
    progress?: number
    percent?: number
    category?: string
    category_km?: string
    actionLabel?: string
  }>
  upcomingEvents?: Array<{
    id: number
    type: 'quiz' | 'live' | 'assignment'
    title: string
    course: string
    date: string
    time: string
    href: string
  }>
    todayGoals?: {
    completedCount: number
    totalCount: number
    percentage: number
    items: Array<{
      id: number
      titleEn: string
      titleKm: string
      completed: boolean
      href: string
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
    averageScore: string
    averageScoreNum: number
    correctPct: string
    incorrectPct: string
    highestScore: string
    trend: string
  }
  learningOverview?: {
    week: { total: string; trend: string }
    last_week: { total: string; trend: string }
    month: { total: string; trend: string }
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
    href: string
    unlocked?: boolean
  }>
  initialMetrics?: MetricsPayload | null
}>()

const page = usePage<any>()
const user = computed(() => page.props.auth?.user || {})
const currentLang = computed(() => i18n.locale.value)
const activeMetrics = ref<MetricsPayload | null>(props.initialMetrics || null)

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
  loading.value = true
  loadingStep.value = 'skeleton'
  error.value = ''

  // Immediately use props.stats if provided directly by server-side Inertia render
  if (props.stats) {
    liveStats.value = props.stats
  }

  // Smooth 500ms skeleton shimmer
  await new Promise(resolve => setTimeout(resolve, 500))

  // If props.stats was not provided (e.g. client API fallback), fetch endpoint
  if (!liveStats.value) {
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
    try {
      const key = 'student_today_goals_' + (user.value?.id || 'default')
      const saved = localStorage.getItem(key)
      if (saved) {
        const parsed = JSON.parse(saved)
        if (parsed && Array.isArray(parsed.items) && parsed.items.length > 0) {
          todayGoal.value = parsed
        }
      }
    } catch {}

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

  let enrolledDisplay = (activeStats.enrolledCount ?? 0).toString()
  let inProgressDisplay = (activeStats.inProgressCount ?? 0).toString()
  let completedDisplay = (activeStats.completedCount ?? 0).toString()
  let certsDisplay = (activeStats.certificatesCount ?? 0).toString()
  let studyTimeDisplay = activeStats.learningTime || '0h 0m'
  let quizAvgDisplay = activeStats.averageScore || '0%'

  if (loadingStep.value === 'counting') {
    enrolledDisplay = animatedEnrolled.value.toString()
    inProgressDisplay = animatedInProgress.value.toString()
    completedDisplay = animatedCompleted.value.toString()
    certsDisplay = animatedCertificates.value.toString()
    studyTimeDisplay = `${animatedStudyHours.value}h ${animatedStudyMinutes.value}m`
    quizAvgDisplay = `${animatedQuizAvg.value}%`
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
      href: '/student/my-courses/enrolled'
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
      value: studyTimeDisplay,
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

// AI Recommended For You Widget Data (Connected to Database)
const aiRecommendations = computed(() => {
  if (props.aiRecommendations && props.aiRecommendations.length > 0) {
    return props.aiRecommendations.map(r => ({
      ...r,
      title: currentLang.value === 'km' ? (r.title_km || r.title) : r.title,
      badge: currentLang.value === 'km' ? (r.badge_km || r.badge) : r.badge,
      desc: currentLang.value === 'km' ? (r.desc_km || r.desc) : r.desc,
      actionLabel: currentLang.value === 'km' ? (r.actionLabel_km || r.actionLabel) : r.actionLabel,
    }))
  }
  return [
    {
      id: 1,
      type: 'weak_topic',
      title: continueCourseData.value ? (currentLang.value === 'km' ? `រំលឹកឡើងវិញ ${continueCourseData.value.title}` : `Review ${continueCourseData.value.title}`) : (currentLang.value === 'km' ? 'រំលឹកមេរៀនវគ្គសិក្សា' : 'Review Course Lessons'),
      badge: currentLang.value === 'km' ? 'វគ្គកំពុងរៀន' : 'Active Course',
      desc: continueCourseData.value ? (currentLang.value === 'km' ? `ផ្ដោតលើមេរៀន ${continueCourseData.value.lastLesson} ដើម្បីរក្សាសន្ទុះនៃការរៀន។` : `Focus on ${continueCourseData.value.lastLesson} to maintain steady pace.`) : (currentLang.value === 'km' ? 'ពង្រឹងទស្សនទានគ្រឹះមុនពេលប្រឡងតេស្តបន្ទាប់។' : 'Reinforce core academic concepts before next assessments.'),
      actionLabel: currentLang.value === 'km' ? 'រំលឹកមេរៀន' : 'Review Lesson',
      actionHref: continueCourseData.value?.href || '/student/my-courses/enrolled',
    },
    {
      id: 2,
      type: 'practice_drill',
      title: currentLang.value === 'km' ? 'អនុវត្តសំណួរ AI ចំនួន ៥' : 'Practice 5 AI Questions',
      badge: currentLang.value === 'km' ? 'ផ្ដោតលើ: AI Drill' : 'Focus: AI Drill',
      desc: currentLang.value === 'km' ? 'ការហ្វឹកហាត់សំណួរ AI ឆ្លាតវៃដើម្បីបង្កើនការយល់ដឹង និងភាពត្រឹមត្រូវ។' : 'Customized drill to improve your understanding and accuracy.',
      actionLabel: currentLang.value === 'km' ? 'អនុវត្តជាមួយ AI' : 'Practice with AI',
      actionHref: '/student/quizzes/practice',
    }
  ]
})

// Curated Representative Course Cover Images (High-Resolution Professional Thematic Imagery)
const getCourseFallbackImage = (course: any) => {
  if (course?.thumbnail) return course.thumbnail
  const title = (course?.title || '').toLowerCase()
  if (title.includes('business english') || (title.includes('english') && title.includes('communication'))) {
    return 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=600&q=80'
  }
  if (title.includes('english') || title.includes('grammar') || title.includes('language')) {
    return 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80'
  }
  if (title.includes('crop') || title.includes('agronomy')) {
    return 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=600&q=80'
  }
  if (title.includes('plant') || title.includes('botany')) {
    return 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?auto=format&fit=crop&w=600&q=80'
  }
  if (title.includes('agriculture') || title.includes('organic')) {
    return 'https://images.unsplash.com/photo-1574943320219-553eb213f72d?auto=format&fit=crop&w=600&q=80'
  }
  if (title.includes('tourism') || title.includes('hospitality') || title.includes('hotel')) {
    return 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=600&q=80'
  }
  if (title.includes('social') || title.includes('community') || title.includes('advocacy')) {
    return 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&w=600&q=80'
  }
  if (title.includes('mobile') || title.includes('flutter')) {
    return 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?auto=format&fit=crop&w=600&q=80'
  }
  if (title.includes('c program') || title.includes('dsa') || title.includes('algorithm')) {
    return 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=600&q=80'
  }
  if (title.includes('machine learning') || title.includes('ai')) {
    return 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=600&q=80'
  }
  if (title.includes('vue') || title.includes('laravel') || title.includes('web')) {
    return 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80'
  }
  return 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=600&q=80'
}

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

// My Courses Filter and List - 6 Representative Courses for 2 full clean rows
const selectedCourseFilter = ref<'all' | 'in_progress' | 'completed' | 'not_started'>('all')

const fallbackCoursesList = computed(() => [
  {
    id: 1,
    title: currentLang.value === 'km' ? 'មូលដ្ឋានគ្រឹះ Web Development' : 'Web Development Fundamentals',
    teacher: 'Mr. Sophea Chem',
    progress: 53,
    status: 'in_progress' as const,
    statusLabel: currentLang.value === 'km' ? 'កំពុងសិក្សា' : 'In Progress',
    badgeClass: 'bg-blue-600/95 text-white border-blue-400/40 shadow-sm',
    iconType: 'code',
    iconColor: 'from-blue-600 to-purple-600',
    thumbnail: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80',
    href: '/student/courses/1/overview'
  },
  {
    id: 2,
    title: currentLang.value === 'km' ? 'ប្រព័ន្ធមូលដ្ឋានទិន្នន័យ (Database)' : 'Database Systems',
    teacher: 'Mr. Long Dararith',
    progress: 35,
    status: 'in_progress' as const,
    statusLabel: currentLang.value === 'km' ? 'កំពុងសិក្សា' : 'In Progress',
    badgeClass: 'bg-blue-600/95 text-white border-blue-400/40 shadow-sm',
    iconType: 'db',
    iconColor: 'from-purple-600 to-indigo-600',
    thumbnail: 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?auto=format&fit=crop&w=600&q=80',
    href: '/student/courses/2/overview'
  },
  {
    id: 3,
    title: currentLang.value === 'km' ? 'ការសរសេរកម្មវិធី Python' : 'Python Programming',
    teacher: 'Mr. Eng Thida',
    progress: 0,
    status: 'not_started' as const,
    statusLabel: currentLang.value === 'km' ? 'មិនទាន់ចាប់ផ្ដើម' : 'Not Started',
    badgeClass: 'bg-slate-800/90 text-white border-white/20 shadow-sm',
    iconType: 'python',
    iconColor: 'from-amber-600 to-yellow-600',
    thumbnail: 'https://images.unsplash.com/photo-1526379095098-d400fd0bf935?auto=format&fit=crop&w=600&q=80',
    href: '/student/courses/3/overview'
  },
  {
    id: 4,
    title: currentLang.value === 'km' ? 'មូលដ្ឋានគ្រឹះ UI/UX Design' : 'UI/UX Design Basics',
    teacher: 'Ms. Nhean Sreymom',
    progress: 100,
    status: 'completed' as const,
    statusLabel: currentLang.value === 'km' ? 'បានបញ្ចប់' : 'Completed',
    badgeClass: 'bg-emerald-600/95 text-white border-emerald-400/40 shadow-sm',
    iconType: 'design',
    iconColor: 'from-pink-600 to-purple-600',
    thumbnail: 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=600&q=80',
    href: '/student/courses/4/overview'
  },
  {
    id: 5,
    title: currentLang.value === 'km' ? 'បច្ចេកវិទ្យា Cloud & សុវត្ថិភាពបណ្តាញ' : 'Cloud & Network Security',
    teacher: 'Mr. Chea Sovann',
    progress: 82,
    status: 'in_progress' as const,
    statusLabel: currentLang.value === 'km' ? 'កំពុងសិក្សា' : 'In Progress',
    badgeClass: 'bg-blue-600/95 text-white border-blue-400/40 shadow-sm',
    iconType: 'cloud',
    iconColor: 'from-sky-600 to-indigo-600',
    thumbnail: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=600&q=80',
    href: '/student/courses/5/overview'
  },
  {
    id: 6,
    title: currentLang.value === 'km' ? 'ភាសាអង់គ្លេសសម្រាប់ទំនាក់ទំនងការងារ' : 'Business English Communication',
    teacher: 'Dr. Virak Chea',
    progress: 100,
    status: 'completed' as const,
    statusLabel: currentLang.value === 'km' ? 'បានបញ្ចប់' : 'Completed',
    badgeClass: 'bg-emerald-600/95 text-white border-emerald-400/40 shadow-sm',
    iconType: 'english',
    iconColor: 'from-emerald-600 to-teal-600',
    thumbnail: 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80',
    href: '/student/courses/6/overview'
  }
])

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

// Learning Overview Chart Range Switcher (Connected to Database)
const selectedRange = ref<'week' | 'last_week' | 'month'>('week')
const chartDataByRange = computed(() => ({
  week: {
    total: props.learningOverview?.week?.total || (currentLang.value === 'km' ? '២ ម៉ោង ៣០ នាទី' : '2h 30m'),
    sub: currentLang.value === 'km' ? 'សប្តាហ៍នេះធៀបនឹងមធ្យមភាគ' : 'This week total',
    trend: props.learningOverview?.week?.trend || '+12% vs last period',
    pathArea: 'M 10 60 Q 40 40 70 55 T 130 25 T 190 40 L 190 75 L 10 75 Z',
    pathLine: 'M 10 60 Q 40 40 70 55 T 130 25 T 190 40',
    dotX: 130,
    dotY: 25,
    labels: currentLang.value === 'km' ? ['ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហ', 'សុក្រ', 'សៅរ៍', 'អាទិត្យ'] : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  },
  last_week: {
    total: props.learningOverview?.last_week?.total || (currentLang.value === 'km' ? '១៤ ម៉ោង ១០ នាទី' : '14h 10m'),
    sub: currentLang.value === 'km' ? 'សរុបសប្តាហ៍មុន' : 'Last week total',
    trend: props.learningOverview?.last_week?.trend || '+8% vs prior week',
    pathArea: 'M 10 50 Q 40 30 70 45 T 130 35 T 190 20 L 190 75 L 10 75 Z',
    pathLine: 'M 10 50 Q 40 30 70 45 T 130 35 T 190 20',
    dotX: 190,
    dotY: 20,
    labels: currentLang.value === 'km' ? ['ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហ', 'សុក្រ', 'សៅរ៍', 'អាទិត្យ'] : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  },
  month: {
    total: props.learningOverview?.month?.total || (currentLang.value === 'km' ? '៤៨ ម៉ោង ២០ នាទី' : '48h 20m'),
    sub: currentLang.value === 'km' ? 'សរុបប្រចាំខែ' : 'Monthly cumulative',
    trend: props.learningOverview?.month?.trend || '+18% this month',
    pathArea: 'M 10 65 Q 50 50 90 35 T 150 20 T 190 15 L 190 75 L 10 75 Z',
    pathLine: 'M 10 65 Q 50 50 90 35 T 150 20 T 190 15',
    dotX: 150,
    dotY: 20,
    labels: currentLang.value === 'km' ? ['ស១', 'ស២', 'ស៣', 'ស៤', '', '', ''] : ['W1', 'W2', 'W3', 'W4', '', '', '']
  }
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
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? `ផ្អែកលើការវិភាគទិន្នន័យសិក្សាពិតរបស់អ្នកក្នុង Database:\n1. **បន្តរៀន ${lessonTitle}** ក្នុងវគ្គ "${courseTitle}" (វឌ្ឍនភាព ${continueCourseData.value?.progress || 0}%)។\n2. **រំលឹកចំណុចខ្សោយ** ក្នុង Quiz ដើម្បីបង្កើនពិន្ទុមធ្យម ${props.stats?.averageScore || '72%'}។\n3. **អនុវត្តសំណួរ ៥ សំណួរ** មុននឹងឈានទៅមេរៀនបន្ទាប់!`
          : `Based on your real database analytics:\n1. **Continue ${lessonTitle}** in "${courseTitle}" (${continueCourseData.value?.progress || 0}% watched).\n2. **Review weak areas** to raise your academic score beyond ${props.stats?.averageScore || '72%'}.\n3. **Practice 5 drill questions** before your next milestone!`,
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
      desc: currentLang.value === 'km' ? (a.desc_km || a.desc) : a.desc
    }))
  }
  return [
    {
      id: 'streak',
      title: currentLang.value === 'km' ? 'រៀន ៧ ថ្ងៃជាប់' : '7 Days Streak',
      desc: currentLang.value === 'km' ? 'រៀន ៧ ថ្ងៃជាប់គ្នាឥតដាច់' : 'Studied 7 days in a row without breaking streak',
      type: 'flame',
      color: 'from-orange-500 to-amber-500',
      href: '/student/certificates/achievements',
      unlocked: false
    },
    {
      id: 'quick',
      title: currentLang.value === 'km' ? 'រៀនរហ័ស' : 'Quick Learner',
      desc: currentLang.value === 'km' ? 'បញ្ចប់មេរៀនមុនកាលកំណត់' : 'Completed lessons 30% faster than average',
      type: 'book',
      color: 'from-emerald-500 to-teal-500',
      href: '/student/certificates/achievements',
      unlocked: false
    },
    {
      id: 'quiz_master',
      title: currentLang.value === 'km' ? 'ជើងខ្លាំង Quiz' : 'Quiz Master',
      desc: currentLang.value === 'km' ? 'ពិន្ទុ 90%+ លើ Quiz ជំពូក' : 'Scored 90%+ in 5 consecutive quizzes',
      type: 'star',
      color: 'from-blue-600 to-indigo-600',
      href: '/student/certificates/achievements',
      unlocked: false
    },
    {
      id: 'early_bird',
      title: currentLang.value === 'km' ? 'រៀនព្រលឹម' : 'Early Bird',
      desc: currentLang.value === 'km' ? 'ចូលរៀនមុនម៉ោង ៨:០០ ព្រឹក' : 'Completed learning session before 8:00 AM',
      type: 'bolt',
      color: 'from-amber-500 to-yellow-500',
      href: '/student/certificates/achievements',
      unlocked: false
    },
    {
      id: 'top_performer',
      title: currentLang.value === 'km' ? 'លទ្ធផលឆ្នើម' : 'Top Performer',
      desc: currentLang.value === 'km' ? 'ជាប់ចំណាត់ថ្នាក់ Top 10%' : 'Ranked in the top 10% high achievers',
      type: 'trophy',
      color: 'from-yellow-500 to-amber-600',
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

  return rawList.map((a, index) => {
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

    const defaultProgressSteps = [35, 60, 80]
    const p = (a as any).progress ?? (a as any).percent ?? defaultProgressSteps[index % defaultProgressSteps.length]

    const rawTime = a.time || (currentLang.value === 'km' ? '៦ ម៉ោងមុន' : '6 hours ago')
    const shortTime = rawTime
      .replace(/(\d+)\s*hours?\s*ago/i, '$1h ago')
      .replace(/(\d+)\s*days?\s*ago/i, '$1d ago')
      .replace(/(\d+)\s*minutes?\s*ago/i, '$1m ago')
      .replace(/(\d+)\s*ម៉ោងមុន/i, '$1h មុន')
      .replace(/Today/i, 'Today')

    return {
      ...a,
      cleanTitle,
      category,
      time: rawTime,
      shortTime,
      progress: Math.min(100, Math.max(5, Math.round(p)))
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
        :student-name="user?.name || 'Student'"
        academic-term="Active Academic Term 2026-2027"
        :unread-announcements-count="(page.props as any)?.student_notifications?.unread_count || 0"
        :current-lang="currentLang"
      />

      <!-- UNIFIED 6-KPI SUMMARY STRIP -->
      <DashboardKpiStrip
        :kpi="activeMetrics?.kpi"
        :current-lang="currentLang"
        :is-loading="loading && loadingStep === 'skeleton'"
      />

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
              <Link href="/student/my-courses/enrolled" class="text-xs text-[#0b57d0] dark:text-[#8ab4f8] hover:underline font-semibold">
                {{ currentLang === 'km' ? 'មើលទាំងអស់' : 'View All' }}
              </Link>
            </div>

            <div v-if="continueCourseData" class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#0b57d0] hover:shadow-md hover:dark:border-[#1a73e8]/70 rounded-3xl p-6 shadow-sm dark:shadow-xl flex flex-col md:flex-row items-center gap-5 sm:gap-6 relative overflow-hidden transition-all duration-300">
              
              <!-- Real Course Thumbnail for Continue Learning -->
              <div class="relative w-full md:w-36 h-28 rounded-2xl overflow-hidden shrink-0 flex-shrink-0 shadow-md bg-slate-900 border border-[#dfe3e7] dark:border-[#2d2f31]">
                <img
                  :src="continueCourseData.thumbnail || 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80'"
                  :alt="continueCourseData.title"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent flex items-end p-2">
                  <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-amber-400 text-slate-950 font-black text-[10px] shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span>Active</span>
                  </span>
                </div>
              </div>

              <!-- Course Info and Progress -->
              <div class="flex-1 w-full space-y-3 min-w-0">
                <div class="space-y-1">
                  <span class="px-2.5 py-0.5 rounded-full bg-[#e8f0fe] dark:bg-[#1a73e8]/15 text-[#475569] dark:text-[#a8c7fa] text-[13px] font-semibold border border-[#d2e3fc] dark:border-[#1a73e8]/30 inline-block">
                    {{ currentLang === 'km' ? 'គ្រូបង្រៀន: ' : 'Teacher: ' }}{{ continueCourseData.teacher }}
                  </span>
                  <h3 class="text-base font-bold text-[#1f1f1f] dark:text-[#e3e3e3] leading-tight truncate">
                    {{ continueCourseData.title }}
                  </h3>
                  <p class="text-[13.5px] text-[#475569] dark:text-[#c4c7c5] font-medium truncate">
                    {{ continueCourseData.chapter }}
                  </p>
                </div>

                <!-- Progress Bar with 900ms smooth transition -->
                <div class="space-y-1.5">
                  <div class="w-full h-2 rounded-full bg-[#e1e3e1] dark:bg-[#282a2c] overflow-hidden">
                    <div class="h-full bg-[#0b57d0] dark:bg-[#a8c7fa] rounded-full transition-all duration-900 ease-out" :style="{ width: continueCourseData.progress + '%' }"></div>
                  </div>
                  <div class="flex items-center justify-between text-[13.5px] text-[#475569] dark:text-[#c4c7c5] font-medium min-w-0">
                    <span class="truncate min-w-0 flex-1 mr-3">{{ currentLang === 'km' ? 'មេរៀនចុងក្រោយ: ' : 'Last lesson: ' }}{{ continueCourseData.lastLesson }}</span>
                    <span class="tabular-nums font-semibold flex items-center gap-1 shrink-0 flex-shrink-0 whitespace-nowrap">
                      <svg class="w-3.5 h-3.5 text-[#0b57d0] dark:text-[#8ab4f8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      {{ continueCourseData.timeLeft }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- CTA Button (Gradient Purple-Blue with White Text) with safe padding 24px & flex-shrink 0 -->
              <div class="w-full md:w-auto shrink-0 flex-shrink-0 py-1">
                <Link
                  :href="continueCourseData.href"
                  class="w-full md:w-auto px-6 py-3.5 rounded-full bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:from-[#4f46e5] hover:to-[#6d28d9] text-white font-semibold text-[13.5px] shadow-md shadow-indigo-500/25 flex items-center justify-center gap-2 hover:scale-105 active:scale-95 transition-all text-center cursor-pointer whitespace-nowrap shrink-0 flex-shrink-0"
                >
                  <span>{{ currentLang === 'km' ? 'បន្តការសិក្សា' : 'Continue Learning' }}</span>
                  <svg class="w-3.5 h-3.5 shrink-0 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
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
                :href="c.href"
                class="course-card-scroll bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#0b57d0] hover:dark:border-[#1a73e8]/70 hover:dark:bg-[#232426] rounded-2xl p-4.5 sm:p-5 shadow-xs dark:shadow-xl flex flex-col justify-between space-y-3.5 transition-all duration-300 hover:-translate-y-1 hover:shadow-md group"
              >
                <!-- Real Representative Course Thumbnail Image -->
                <div class="relative w-full h-36 rounded-2xl overflow-hidden bg-slate-900 border border-[#dfe3e7] dark:border-[#2d2f31] shadow-xs">
                  <img
                    :src="c.thumbnail || getCourseFallbackImage(c)"
                    :alt="c.title"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    loading="lazy"
                  />
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
                  <h4 class="text-[15px] font-bold text-slate-900 dark:text-[#e2e8f0] group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors line-clamp-2 min-h-[2.5rem] leading-snug" :title="c.title">
                    {{ c.title }}
                  </h4>
                  <p class="text-[13px] text-[#475569] dark:text-[#94a3b8] flex items-center gap-1.5 font-medium">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    <span class="truncate">{{ c.teacher }}</span>
                  </p>
                </div>

                <!-- Progress Bar (6px = h-[6px]) & Link -->
                <div class="space-y-1.5 pt-1 border-t border-slate-100 dark:border-white/10">
                  <div class="w-full h-[6px] rounded-full bg-slate-100 dark:bg-white/10 overflow-hidden">
                    <div
                      :class="c.progress === 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-[#6366f1] to-[#7c3aed]'"
                      class="h-full rounded-full transition-all duration-900 ease-out"
                      :style="{ width: c.progress + '%' }"
                    ></div>
                  </div>
                  <div class="flex items-center justify-between text-[13px]">
                    <span :class="c.progress === 100 ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400'" class="font-bold tabular-nums">{{ c.progress }}%</span>
                    <span class="text-slate-500 dark:text-slate-400 font-semibold flex items-center gap-0.5 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                      <span>{{ currentLang === 'km' ? 'ទិដ្ឋភាពទូទៅ' : 'Overview' }}</span>
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
          <!-- UNIFIED 3-COLUMN ANALYTICS OVERVIEW ROW -->
          <DashboardAnalyticsRow
            :initial-metrics="props.initialMetrics"
            :current-lang="currentLang"
            :is-loading="loading && loadingStep === 'skeleton'"
            @metrics-updated="(m) => activeMetrics = m"
          />

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
            <div v-if="recentActivities.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 [grid-template-columns:repeat(auto-fit,minmax(300px,1fr))]">
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
                    {{ act.course }}
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
                  :key="ach.title"
                  :href="ach.href"
                  class="relative flex flex-col items-center gap-2.5 p-3 rounded-2xl bg-slate-50/70 dark:bg-white/[0.03] hover:bg-purple-50/60 dark:hover:bg-purple-950/20 border border-transparent hover:border-purple-300 dark:hover:border-purple-500/30 group text-center cursor-pointer transition-all duration-200 hover:-translate-y-1"
                >
                  <!-- Interactive Floating Tooltip on hover/focus -->
                  <div class="absolute -top-11 left-1/2 -translate-x-1/2 px-2.5 py-1 bg-slate-900 dark:bg-slate-800 text-white text-[11px] font-medium rounded-lg shadow-xl opacity-0 pointer-events-none group-hover:opacity-100 transition-all duration-200 whitespace-nowrap z-30 transform group-hover:-translate-y-1">
                    <span>{{ ach.desc }}</span>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-900 dark:border-t-slate-800"></div>
                  </div>

                  <!-- Brand Color Badge Icon Container -->
                  <div :class="['w-12 h-12 rounded-2xl bg-gradient-to-tr', ach.color, 'flex items-center justify-center text-white text-base shadow-md group-hover:scale-110 group-hover:shadow-lg transition-all duration-200 ring-4 ring-white dark:ring-white/10', ach.unlocked === false ? 'opacity-40 grayscale' : '']">
                    <!-- Flame / Streak SVG -->
                    <svg v-if="ach.type === 'flame'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"/></svg>
                    <!-- Book SVG -->
                    <svg v-else-if="ach.type === 'book'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <!-- Star SVG -->
                    <svg v-else-if="ach.type === 'star'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                    <!-- Lightning Bolt SVG -->
                    <svg v-else-if="ach.type === 'bolt'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                    <!-- Trophy SVG -->
                    <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a3 3 0 002.828-2.002l1.636-4.908A1.125 1.125 0 0017.896 6H6.104a1.125 1.125 0 00-1.068 1.465l1.636 4.908a3 3 0 002.828 2.002z"/></svg>
                  </div>

                  <!-- Equal min-height label container -->
                  <div class="min-h-[36px] flex items-center justify-center">
                    <span class="text-[13.5px] font-semibold text-slate-700 dark:text-[#e2e8f0] leading-tight text-center line-clamp-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                      {{ ach.title }}
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
              <div class="flex items-center gap-2">
                <!-- Google Gemini 4-Point Sparkle Icon -->
                <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center shadow-xs shrink-0 flex-shrink-0">
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                    <defs>
                      <linearGradient id="geminiSparkleHead" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#4285F4" />
                        <stop offset="35%" stop-color="#9B72CB" />
                        <stop offset="70%" stop-color="#EA4335" />
                        <stop offset="100%" stop-color="#FBBC05" />
                      </linearGradient>
                    </defs>
                    <path d="M12 2C12 7.52285 7.52285 12 2 12C7.52285 12 12 16.4772 12 22C12 16.4772 16.4772 12 22 12C16.4772 12 12 7.52285 12 2Z" fill="url(#geminiSparkleHead)" />
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
              {{ currentLang === 'km' ? 'ផ្អែកលើពិន្ទុ Quiz ថ្មីៗ និងល្បឿននៃការរៀនរបស់អ្នក:' : 'Based on your recent quiz scores & learning pace:' }}
            </p>

            <div class="space-y-2.5">
              <div
                v-for="rec in aiRecommendations"
                :key="rec.id"
                class="p-3.5 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] space-y-2"
              >
                <div class="flex items-center justify-between">
                  <h4 class="font-bold text-slate-900 dark:text-slate-100 text-[15px] leading-snug">{{ rec.title }}</h4>
                  <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#d3e3fd] text-[#041e49] border border-[#0b57d0]/20 dark:bg-[#1a73e8]/20 dark:text-[#a8c7fa] dark:border-[#1a73e8]/30">
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
                  <h4 class="font-bold text-slate-900 dark:text-slate-100 text-[14px] leading-snug line-clamp-2 group-hover:text-[#0b57d0] dark:group-hover:text-[#8ab4f8] transition-colors" :title="item.title">
                    {{ item.title }}
                  </h4>
                  <p class="text-[13px] text-slate-500 dark:text-slate-400 truncate font-medium">
                    {{ item.course }}
                  </p>
                </div>
              </Link>
            </div>

            <!-- Empty State for Upcoming Events -->
            <div v-else class="p-6 text-center text-xs text-slate-500 dark:text-slate-400 bg-[#f8fafd] dark:bg-[#131314] rounded-2xl border border-dashed border-[#dfe3e7] dark:border-[#2d2f31]">
              {{ currentLang === 'km' ? 'គ្មានកាលវិភាគ Quiz ឬកាលកំណត់ខាងមុខទេ' : 'No upcoming quizzes or deadlines. You are all caught up!' }}
            </div>
          </div>

          <!-- AI STUDY ASSISTANT WIDGET -->
          <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3.5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-purple-600 to-indigo-600 dark:from-transparent dark:to-transparent text-white flex items-center justify-center text-xs shadow-sm">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2C12 7.52285 7.52285 12 2 12C7.52285 12 12 16.4772 12 22C12 16.4772 16.4772 12 22 12C16.4772 12 12 7.52285 12 2Z" fill="url(#geminiSparkHeadAss)" />
                    <defs>
                      <linearGradient id="geminiSparkHeadAss" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#4285F4" />
                        <stop offset="35%" stop-color="#9B72CB" />
                        <stop offset="70%" stop-color="#EA4335" />
                        <stop offset="100%" stop-color="#FBBC05" />
                      </linearGradient>
                    </defs>
                  </svg>
                </div>
                <h3 class="text-[18px] font-semibold text-slate-900 dark:text-slate-100">
                  {{ currentLang === 'km' ? 'ជំនួយការសិក្សា AI' : 'AI study assistant' }}
                </h3>
              </div>
              <button
                type="button"
                @click="openAiTutorChat()"
                class="px-3 py-1 rounded-full bg-[#e8f0fe] dark:bg-[#1a73e8]/20 text-[#0b57d0] dark:text-[#a8c7fa] text-xs font-semibold border border-[#d2e3fc] dark:border-[#1a73e8]/30 hover:bg-[#0b57d0] hover:text-white transition-colors cursor-pointer"
              >
                {{ currentLang === 'km' ? 'ជជែកពេញលេញ ↗' : 'Full Chat ↗' }}
              </button>
            </div>

            <!-- Messages Stream -->
            <div class="space-y-2 max-h-44 overflow-y-auto custom-scrollbar pr-1">
              <div
                v-for="(msg, idx) in aiMessages"
                :key="idx"
                :class="[
                  msg.role === 'user' ? 'bg-[#d3e3fd] text-[#041e49] border border-[#0b57d0]/20 dark:bg-[#004a77] dark:border-[#004a77] dark:text-[#c2e7ff] ml-4' : 'bg-[#f8fafd] text-[#1f1f1f] border border-[#dfe3e7] dark:bg-[#131314] dark:border-[#2d2f31] dark:text-[#c4c7c5] mr-2',
                  'p-3 rounded-2xl text-xs space-y-1'
                ]"
              >
                <p class="whitespace-pre-wrap leading-relaxed">{{ msg.text }}</p>
              </div>

              <div v-if="isAiTyping" class="p-2.5 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] text-[#444746] dark:text-[#c4c7c5] text-xs italic flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-[#0b57d0] animate-pulse"></span>
                <span>{{ currentLang === 'km' ? 'AI Tutor កំពុងគិត...' : 'AI Tutor is thinking...' }}</span>
              </div>
            </div>

            <!-- Quick Action Prompt Chips (Google One Selection Chips) -->
            <div class="grid grid-cols-2 gap-1.5 pt-1">
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'ពន្យល់មេរៀននេះ' : 'Explain this lesson')"
                type="button"
                class="p-2 rounded-full bg-white dark:bg-[#282a2c] hover:bg-[#f0f4f9] dark:hover:bg-[#333538] text-[#1f1f1f] dark:text-[#c4c7c5] text-xs font-medium border border-[#c7c7c7] dark:border-[#3c4043] text-center transition-colors cursor-pointer truncate shadow-2xs"
              >
                {{ currentLang === 'km' ? 'ពន្យល់មេរៀននេះ' : 'Explain this lesson' }}
              </button>
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'ផ្តល់ឧទាហរណ៍' : 'Give me an example')"
                type="button"
                class="p-2 rounded-full bg-white dark:bg-[#282a2c] hover:bg-[#f0f4f9] dark:hover:bg-[#333538] text-[#1f1f1f] dark:text-[#c4c7c5] text-xs font-medium border border-[#c7c7c7] dark:border-[#3c4043] text-center transition-colors cursor-pointer truncate shadow-2xs"
              >
                {{ currentLang === 'km' ? 'ផ្តល់ឧទាហរណ៍' : 'Give me an example' }}
              </button>
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'សង្ខេបប្រធានបទនេះ' : 'Summarize this topic')"
                type="button"
                class="p-2 rounded-full bg-white dark:bg-[#282a2c] hover:bg-[#f0f4f9] dark:hover:bg-[#333538] text-[#1f1f1f] dark:text-[#c4c7c5] text-xs font-medium border border-[#c7c7c7] dark:border-[#3c4043] text-center transition-colors cursor-pointer truncate shadow-2xs"
              >
                {{ currentLang === 'km' ? 'សង្ខេបប្រធានបទនេះ' : 'Summarize this topic' }}
              </button>
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'តើខ្ញុំគួររៀនអ្វីថ្ងៃនេះ?' : 'What should I study today?')"
                type="button"
                class="p-2 rounded-full bg-white dark:bg-[#282a2c] hover:bg-[#f0f4f9] dark:hover:bg-[#333538] text-[#1f1f1f] dark:text-[#c4c7c5] text-xs font-medium border border-[#c7c7c7] dark:border-[#3c4043] text-center transition-colors cursor-pointer truncate shadow-2xs"
              >
                {{ currentLang === 'km' ? 'តើខ្ញុំគួររៀនអ្វី?' : 'What should I study?' }}
              </button>
            </div>

            <!-- Input Form (Google Rounded-Full Input) -->
            <form @submit.prevent="sendUserCustomAi" class="relative flex items-center pt-1">
              <input
                v-model="aiPromptInput"
                type="text"
                :placeholder="currentLang === 'km' ? 'សួរសំណួរទៅកាន់ AI Tutor របស់អ្នក...' : 'Ask your AI academic tutor...'"
                class="w-full pl-4 pr-10 py-2.5 rounded-full bg-[#f8fafd] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] text-xs text-[#1f1f1f] dark:text-[#e3e3e3] placeholder-[#747775] dark:placeholder-[#8e918f] focus:outline-none focus:border-[#0b57d0] dark:focus:border-[#1a73e8] shadow-2xs"
              />
              <button
                type="submit"
                class="absolute right-1.5 p-1.5 rounded-full bg-[#0b57d0] hover:bg-[#1557bf] text-white dark:bg-[#a8c7fa] dark:hover:bg-[#b8d2fa] dark:text-[#041e49] transition-all cursor-pointer shadow-xs"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
</style>
