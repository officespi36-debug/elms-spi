<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
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
    iconBg: string
    title: string
    title_km?: string
    course: string
    time: string
    href: string
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
}>()

const page = usePage<any>()
const user = computed(() => page.props.auth?.user || {})
const currentLang = computed(() => i18n.locale.value)

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
      subtitle: currentLang.value === 'km' ? '▲ វគ្គសិក្សាសរុប' : '▲ Total Enrolled',
      gradient: 'from-[#7c3aed] to-[#6366f1]',
      wavePath: 'M0 18 Q 20 4, 40 14 T 70 6 T 100 12 L 100 25 L 0 25 Z',
      waveLine: 'M0 18 Q 20 4, 40 14 T 70 6 T 100 12',
      href: '/student/my-courses/enrolled'
    },
    {
      type: 'in_progress',
      title: currentLang.value === 'km' ? 'កំពុងសិក្សា' : 'IN PROGRESS',
      value: inProgressDisplay,
      subtitle: currentLang.value === 'km' ? '▲ វគ្គសិក្សាសកម្ម' : '▲ Active Learning',
      gradient: 'from-[#0284c7] to-[#2563eb]',
      wavePath: 'M0 20 Q 25 8, 45 16 T 75 4 T 100 10 L 100 25 L 0 25 Z',
      waveLine: 'M0 20 Q 25 8, 45 16 T 75 4 T 100 10',
      href: '/student/my-courses/enrolled'
    },
    {
      type: 'completed',
      title: currentLang.value === 'km' ? 'បានបញ្ចប់' : 'COMPLETED',
      value: completedDisplay,
      subtitle: currentLang.value === 'km' ? '▲ ជោគជ័យ ១០០%' : '▲ Finished Courses',
      gradient: 'from-[#10b981] to-[#059669]',
      wavePath: 'M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z',
      waveLine: 'M0 16 Q 20 22, 45 8 T 75 14 T 100 4',
      href: '/student/my-courses/completed'
    },
    {
      type: 'certificates',
      title: currentLang.value === 'km' ? 'វិញ្ញាបនបត្រ' : 'CERTIFICATES',
      value: certsDisplay,
      subtitle: currentLang.value === 'km' ? '★ ទទួលបាន' : '★ Verified & Earned',
      gradient: 'from-[#f59e0b] to-[#d97706]',
      wavePath: 'M0 18 Q 20 6, 45 16 T 75 8 T 100 14 L 100 25 L 0 25 Z',
      waveLine: 'M0 18 Q 20 6, 45 16 T 75 8 T 100 14',
      href: '/student/certificates/my-certificates'
    },
    {
      type: 'study_time',
      title: currentLang.value === 'km' ? 'ម៉ោងសិក្សា' : 'STUDY TIME',
      value: studyTimeDisplay,
      subtitle: currentLang.value === 'km' ? '⏱ ម៉ោងសរុប' : '⏱ Total Hours Spent',
      gradient: 'from-[#06b6d4] to-[#0d9488]',
      wavePath: 'M0 19 Q 25 10, 50 18 T 80 8 T 100 12 L 100 25 L 0 25 Z',
      waveLine: 'M0 19 Q 25 10, 50 18 T 80 8 T 100 12',
      href: '/student/progress/learning-time'
    },
    {
      type: 'quiz_avg',
      title: currentLang.value === 'km' ? 'មធ្យមភាគ QUIZ' : 'QUIZ AVERAGE',
      value: quizAvgDisplay,
      subtitle: currentLang.value === 'km' ? '★ ពិន្ទុមធ្យមរបស់អ្នក' : '★ Academic Score',
      gradient: 'from-[#ec4899] to-[#8b5cf6]',
      wavePath: 'M0 12 Q 25 20, 50 10 T 75 16 T 100 6 L 100 25 L 0 25 Z',
      waveLine: 'M0 12 Q 25 20, 50 10 T 75 16 T 100 6',
      href: '/student/quizzes/scores'
    }
  ]
})

// Featured Continue Learning Course
const continueCourseData = computed(() => {
  if (props.continueCourse) {
    return props.continueCourse
  }
  return {
    title: currentLang.value === 'km' ? 'មូលដ្ឋានគ្រឹះ Web Development' : 'Web Development Fundamentals',
    chapter: currentLang.value === 'km' ? 'ជំពូកទី ៣ - JavaScript Functions' : 'Chapter 3 - JavaScript Functions',
    teacher: 'Mr. Sophea Chem',
    progress: 53,
    lastLesson: currentLang.value === 'km' ? '៣.២ JavaScript Functions' : '3.2 JavaScript Functions',
    timeLeft: currentLang.value === 'km' ? 'នៅសល់ 18:20' : '18:20 left',
    href: '/student/my-courses/current'
  }
})

// Today's Goal (Interactive tasks with database & local state)
const todayGoal = ref(props.todayGoals || {
  completedCount: 1,
  totalCount: 2,
  percentage: 50,
  items: [
    {
      id: 1,
      titleEn: 'Continue Learning Core Academic Lessons',
      titleKm: 'បន្តការសិក្សាមេរៀនក្នុងវគ្គសិក្សាគោល',
      completed: true,
      href: '/student/my-courses/enrolled'
    },
    {
      id: 2,
      titleEn: 'Practice 10 quiz questions',
      titleKm: 'អនុវត្តសំណួរ Quiz ចំនួន ១០',
      completed: false,
      href: '/student/quizzes/practice'
    }
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
  const text = newGoalTitle.value.trim()
  todayGoal.value.items.push({
    id: Date.now(),
    titleEn: text,
    titleKm: text,
    completed: false,
    href: '/student/my-courses/current'
  })
  newGoalTitle.value = ''
  isAddGoalOpen.value = false
  recalculateGoals()
}

// AI Recommended For You Widget Data
const aiRecommendations = computed(() => [
  {
    id: 1,
    type: 'weak_topic',
    title: currentLang.value === 'km' ? 'រំលឹកឡើងវិញ JavaScript Functions' : 'Review JavaScript Functions',
    badge: currentLang.value === 'km' ? 'ពិន្ទុ: 62%' : 'Score: 62%',
    desc: currentLang.value === 'km'
      ? 'ផ្អែកលើកំហុស Quiz ថ្មីៗរបស់អ្នកក្នុងជំពូក 3.2។ ពង្រឹង parameter scope មុននឹងចូលរៀនប្រធានបទ DOM។'
      : 'Based on your recent quiz errors in Chapter 3.2. Reinforce parameter scope before DOM topics.',
    actionLabel: currentLang.value === 'km' ? 'រំលឹកមេរៀន' : 'Review Lesson',
    actionHref: '/student/my-courses/current',
    btnClass: 'bg-purple-600 hover:bg-purple-500 text-white'
  },
  {
    id: 2,
    type: 'practice_drill',
    title: currentLang.value === 'km' ? 'អនុវត្តសំណួរ AI ចំនួន ៥' : 'Practice 5 AI Questions',
    badge: currentLang.value === 'km' ? 'ផ្ដោតលើ: Parameters' : 'Focus: Parameters',
    desc: currentLang.value === 'km'
      ? 'ការហ្វឹកហាត់ពិសេសដើម្បីបង្កើនល្បឿន និងភាពត្រឹមត្រូវនៃ function return statement។'
      : 'Customized drill to improve your function return statement speed and accuracy.',
    actionLabel: currentLang.value === 'km' ? 'អនុវត្តជាមួយ AI' : 'Practice with AI',
    actionHref: '/student/quizzes/practice',
    btnClass: 'bg-emerald-600 hover:bg-emerald-500 text-white'
  }
])

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

// My Courses Filter and List
const selectedCourseFilter = ref<'all' | 'in_progress' | 'completed' | 'not_started'>('all')

const fallbackCoursesList = computed(() => [
  {
    id: 1,
    title: currentLang.value === 'km' ? 'មូលដ្ឋានគ្រឹះ Web Development' : 'Web Development Fundamentals',
    teacher: 'Mr. Sophea Chem',
    progress: 53,
    status: 'in_progress' as const,
    statusLabel: currentLang.value === 'km' ? 'កំពុងសិក្សា' : 'In Progress',
    badgeClass: 'bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-500/30',
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
    badgeClass: 'bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-500/30',
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
    badgeClass: 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
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
    badgeClass: 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
    iconType: 'design',
    iconColor: 'from-pink-600 to-purple-600',
    thumbnail: 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&w=600&q=80',
    href: '/student/courses/4/overview'
  }
])

const coursesToDisplay = computed(() => {
  if (props.dbCourses && props.dbCourses.length > 0) {
    return props.dbCourses
  }
  return fallbackCoursesList.value
})

const filteredCourses = computed(() => {
  if (selectedCourseFilter.value === 'all') return coursesToDisplay.value
  return coursesToDisplay.value.filter(c => c.status === selectedCourseFilter.value)
})

// Learning Overview Chart Range Switcher
const selectedRange = ref<'week' | 'last_week' | 'month'>('week')
const chartDataByRange = computed(() => ({
  week: {
    total: currentLang.value === 'km' ? '២ ម៉ោង ៣០ នាទី' : '2h 30m',
    sub: currentLang.value === 'km' ? 'ថ្ងៃនេះធៀបនឹងមធ្យមភាគ' : 'Today vs average',
    pathArea: 'M 10 60 Q 40 40 70 55 T 130 25 T 190 40 L 190 75 L 10 75 Z',
    pathLine: 'M 10 60 Q 40 40 70 55 T 130 25 T 190 40',
    dotX: 130,
    dotY: 25,
    labels: currentLang.value === 'km' ? ['ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហ', 'សុក្រ', 'សៅរ៍', 'អាទិត្យ'] : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  },
  last_week: {
    total: currentLang.value === 'km' ? '១៤ ម៉ោង ១០ នាទី' : '14h 10m',
    sub: currentLang.value === 'km' ? 'សរុបសប្តាហ៍មុន' : 'Last week total',
    pathArea: 'M 10 50 Q 40 30 70 45 T 130 35 T 190 20 L 190 75 L 10 75 Z',
    pathLine: 'M 10 50 Q 40 30 70 45 T 130 35 T 190 20',
    dotX: 190,
    dotY: 20,
    labels: currentLang.value === 'km' ? ['ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហ', 'សុក្រ', 'សៅរ៍', 'អាទិត្យ'] : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  },
  month: {
    total: currentLang.value === 'km' ? '៤៨ ម៉ោង ២០ នាទី' : '48h 20m',
    sub: currentLang.value === 'km' ? 'សរុបប្រចាំខែ ឧសភា' : 'May cumulative',
    pathArea: 'M 10 65 Q 50 50 90 35 T 150 20 T 190 15 L 190 75 L 10 75 Z',
    pathLine: 'M 10 65 Q 50 50 90 35 T 150 20 T 190 15',
    dotX: 150,
    dotY: 20,
    labels: currentLang.value === 'km' ? ['ស១', 'ស២', 'ស៣', 'ស៤', '', '', ''] : ['W1', 'W2', 'W3', 'W4', '', '', '']
  }
}))

// Upcoming Schedule with real SVG icon type
const upcomingEvents = computed(() => {
  if (props.upcomingEvents && props.upcomingEvents.length > 0) {
    return props.upcomingEvents
  }
  return [
    {
      id: 1,
      type: 'quiz',
      title: currentLang.value === 'km' ? 'Quiz: Quiz ជំពូកទី ៣' : 'Quiz: Chapter 3 Quiz',
      course: currentLang.value === 'km' ? 'មូលដ្ឋានគ្រឹះ Web Development' : 'Web Development Fundamentals',
      date: currentLang.value === 'km' ? '២៨ ឧសភា ២០២៥' : 'May 28, 2025',
      time: currentLang.value === 'km' ? 'ម៉ោង ១០:០០ ព្រឹក' : '10:00 AM',
      href: '/student/quizzes/practice'
    },
    {
      id: 2,
      type: 'live',
      title: currentLang.value === 'km' ? 'ថ្នាក់រៀនផ្ទាល់: JavaScript DOM' : 'Live Class: JavaScript DOM',
      course: currentLang.value === 'km' ? 'មូលដ្ឋានគ្រឹះ Web Development' : 'Web Development Fundamentals',
      date: currentLang.value === 'km' ? '៣០ ឧសភា ២០២៥' : 'May 30, 2025',
      time: currentLang.value === 'km' ? 'ម៉ោង ០២:០០ រសៀល' : '02:00 PM',
      href: '/student/calendar/live-class'
    },
    {
      id: 3,
      type: 'assignment',
      title: currentLang.value === 'km' ? 'កិច្ចការ: គំរូ Relational ER' : 'Assignment: Relational ER Model',
      course: currentLang.value === 'km' ? 'ប្រព័ន្ធមូលដ្ឋានទិន្នន័យ (Database)' : 'Database Systems',
      date: currentLang.value === 'km' ? '០១ មិថុនា ២០២៥' : 'Jun 01, 2025',
      time: currentLang.value === 'km' ? 'ម៉ោង ១១:៥៩ យប់' : '11:59 PM',
      href: '/student/assignments'
    }
  ]
})

// AI Study Assistant Widget
const aiPromptInput = ref('')
const isAiTyping = ref(false)
const aiMessages = ref<Array<{ role: 'ai' | 'user'; text: string; time?: string }>>([
  {
    role: 'ai',
    text: currentLang.value === 'km'
      ? 'សួស្តី ' + (user.value?.name || 'Sok Pisey') + '! តើខ្ញុំអាចជួយអ្វីខ្លះទាក់ទងនឹងការសរសេរកូដ និងមេរៀនរបស់អ្នកថ្ងៃនេះ?'
      : 'Hi ' + (user.value?.name || 'Sok Pisey') + '! How can I assist with your coding & course questions today?',
    time: currentLang.value === 'km' ? 'ឥឡូវនេះ' : 'Just now'
  }
])

const handleSendAiPrompt = (promptText: string) => {
  const isKm = currentLang.value === 'km'
  aiMessages.value.push({ role: 'user', text: promptText, time: isKm ? 'ឥឡូវនេះ' : 'Now' })
  isAiTyping.value = true

  setTimeout(() => {
    isAiTyping.value = false
    const qLower = promptText.toLowerCase()
    if (qLower.includes('example') || qLower.includes('ឧទាហរណ៍') || qLower.includes('function')) {
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? 'នេះជាឧទាហរណ៍ JavaScript function យ៉ាងច្បាស់លាស់:\n```javascript\nfunction calculateTotal(price, taxRate = 0.1) {\n  return price + (price * taxRate);\n}\nconsole.log(calculateTotal(100)); // 110\n```'
          : 'Here is a clean JavaScript function example:\n```javascript\nfunction calculateTotal(price, taxRate = 0.1) {\n  return price + (price * taxRate);\n}\nconsole.log(calculateTotal(100)); // 110\n```',
        time: isKm ? 'ឥឡូវនេះ' : 'Now'
      })
    } else if (qLower.includes('study') || qLower.includes('រៀន') || qLower.includes('recommend') || qLower.includes('plan')) {
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? 'ផ្អែកលើការវិភាគការសិក្សាថ្មីៗរបស់អ្នក:\n1. **រៀនបញ្ចប់មេរៀន 3.2** ក្នុងវគ្គ Web Development (នៅសល់ 18m)។\n2. **រំលឹក Function Parameters** ដើម្បីបង្កើនពិន្ទុ Quiz 62%។\n3. **អនុវត្ត 5 សំណួរហ្វឹកហាត់** មុនការប្រឡង Quiz ជំពូកទី ៣!'
          : 'Based on your recent learning analytics:\n1. **Finish Lesson 3.2** in Web Development (18m left).\n2. **Review Function Parameters** to raise your 62% quiz score.\n3. **Practice 5 drill questions** before the May 28 Chapter Quiz!',
        time: isKm ? 'ឥឡូវនេះ' : 'Now'
      })
    } else if (qLower.includes('explain') || qLower.includes('ពន្យល់')) {
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? 'JavaScript Function គឺជាប្លុកកូដដែលអាចយកមកប្រើឡើងវិញបាន ដើម្បីបំពេញកិច្ចការជាក់លាក់ណាមួយ។ វានឹងដំណើរការនៅពេលហៅប្រើ (invoke) និងបញ្ជូនលទ្ធផលត្រឡប់មកវិញដោយប្រើ return keyword។'
          : 'A JavaScript function is a reusable block of code designed to perform a specific task. It executes when invoked and returns computed output with the return keyword.',
        time: isKm ? 'ឥឡូវនេះ' : 'Now'
      })
    } else if (qLower.includes('summarize') || qLower.includes('សង្ខេប')) {
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? 'សង្ខេប: Functions ទទួល parameters, អនុវត្ត logic ក្នុង block scope ផ្ទាល់ខ្លួន, និងបញ្ជូនលទ្ធផលត្រឡប់មកវិញដោយប្រើពាក្យ return។'
          : 'Summary: Functions accept parameters, execute logic in their local block scope, and return computed results using the return keyword.',
        time: isKm ? 'ឥឡូវនេះ' : 'Now'
      })
    } else {
      aiMessages.value.push({
        role: 'ai',
        text: isKm
          ? `សំណួរល្អណាស់អំពី "${promptText}"! ដើម្បីយល់ច្បាស់: បំបែកបញ្ហាជាដំណាក់កាលតូចៗ, អនុវត្តសរសេរកូដផ្ទាល់, និងសាកល្បងសមត្ថភាពជាមួយ mini-quizzes។ ប្រាប់ខ្ញុំប្រសិនបើអ្នកចង់បានកូដគំរូលម្អិត!`
          : `Great question about "${promptText}"! To master this: break down the problem, practice coding in the editor, and test your understanding with mini-quizzes. Let me know if you want a detailed code snippet!`,
        time: isKm ? 'ឥឡូវនេះ' : 'Now'
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
const achievements = computed(() => [
  { title: currentLang.value === 'km' ? 'រៀន ៧ ថ្ងៃជាប់' : '7 Days Streak', type: 'flame', color: 'from-orange-500 to-amber-500', href: '/student/certificates/achievements' },
  { title: currentLang.value === 'km' ? 'រៀនរហ័ស' : 'Quick Learner', type: 'book', color: 'from-emerald-500 to-teal-500', href: '/student/certificates/achievements' },
  { title: currentLang.value === 'km' ? 'ជើងខ្លាំង Quiz' : 'Quiz Master', type: 'star', color: 'from-blue-500 to-indigo-500', href: '/student/certificates/achievements' },
  { title: currentLang.value === 'km' ? 'រៀនព្រលឹម' : 'Early Bird', type: 'bolt', color: 'from-amber-500 to-yellow-500', href: '/student/certificates/achievements' },
  { title: currentLang.value === 'km' ? 'លទ្ធផលឆ្នើម' : 'Top Performer', type: 'trophy', color: 'from-yellow-500 to-amber-600', href: '/student/certificates/achievements' }
])

// Recent Activity with SVG type
const recentActivities = computed(() => {
  if (props.recentActivities && props.recentActivities.length > 0) {
    return props.recentActivities.map(a => ({
      ...a,
      title: currentLang.value === 'km' && a.title_km ? a.title_km : a.title
    }))
  }
  return [
    {
      id: 1,
      type: 'play',
      iconBg: 'bg-purple-600/20 text-purple-600 dark:text-purple-400 border border-purple-500/30',
      title: currentLang.value === 'km' ? 'បានទស្សនា: 3.2 JavaScript Functions' : 'Watched: 3.2 JavaScript Functions',
      course: currentLang.value === 'km' ? 'មូលដ្ឋានគ្រឹះ Web Development' : 'Web Development Fundamentals',
      time: currentLang.value === 'km' ? 'ថ្ងៃនេះ, ម៉ោង ០៩:៣០ ព្រឹក' : 'Today, 09:30 AM',
      href: '/student/my-courses/current'
    },
    {
      id: 2,
      type: 'check',
      iconBg: 'bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30',
      title: currentLang.value === 'km' ? 'បានបញ្ចប់ Quiz: Quiz ជំពូកទី ២' : 'Completed Quiz: Chapter 2 Quiz',
      course: currentLang.value === 'km' ? 'មូលដ្ឋានគ្រឹះ Web Development' : 'Web Development Fundamentals',
      time: currentLang.value === 'km' ? 'ម្សិលមិញ, ម៉ោង ០៤:១៥ រសៀល' : 'Yesterday, 04:15 PM',
      href: '/student/quizzes/scores'
    },
    {
      id: 3,
      type: 'doc',
      iconBg: 'bg-amber-600/20 text-amber-600 dark:text-amber-400 border border-amber-500/30',
      title: currentLang.value === 'km' ? 'បានទាញយក: JavaScript Cheat Sheet' : 'Downloaded: JavaScript Cheat Sheet',
      course: currentLang.value === 'km' ? 'មូលដ្ឋានគ្រឹះ Web Development' : 'Web Development Fundamentals',
      time: currentLang.value === 'km' ? '២៦ ឧសភា, ម៉ោង ០៣:២០ រសៀល' : 'May 26, 03:20 PM',
      href: '/student/learning-content/resources'
    }
  ]
})
</script>

<template>
  <StudentLayout
    :title="currentLang === 'km' ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard'"
    :breadcrumbs="[
      { label: currentLang === 'km' ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard' }
    ]"
  >
    <div class="space-y-6 pb-12 notranslate" translate="no">
      
      <!-- TOP GREETING & QUICK SHORTCUTS ROW (Glassmorphic Hero Header) -->
      <div class="relative overflow-hidden rounded-3xl bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] p-5 sm:p-6 shadow-sm dark:shadow-xl">
        <!-- Ambient decorative background glow -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-gradient-to-br from-[#0b57d0]/5 via-purple-500/5 to-transparent rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-gradient-to-tr from-[#0b57d0]/5 to-transparent rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#e8f0fe] dark:bg-[#1a73e8]/15 border border-[#d2e3fc] dark:border-[#1a73e8]/30 text-[11px] font-semibold text-[#0b57d0] dark:text-[#a8c7fa]">
              <span class="w-2 h-2 rounded-full bg-[#137333] animate-pulse"></span>
              <span>{{ currentLang === 'km' ? 'SPI Student Portal • ឆមាសសិក្សាថ្មី ២០២៦-២០២៧' : 'SPI Student Portal • Active Academic Term 2026-2027' }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-[#1f1f1f] dark:text-[#e3e3e3] tracking-normal leading-snug font-sans flex items-center gap-2">
              <span>{{ currentLang === 'km' ? `សូមស្វាគមន៍ការត្រឡប់មកវិញ, ${user.name || 'Sok Pisey'}!` : `Welcome back, ${user.name || 'Sok Pisey'}!` }}</span>
              <span class="inline-block animate-bounce text-[#0b57d0]">✨</span>
            </h1>
            <p class="text-xs sm:text-sm text-[#444746] dark:text-[#c4c7c5] font-medium max-w-2xl">
              {{ currentLang === 'km' ? 'បន្តការសិក្សាពីកន្លែងដែលអ្នកបានឈប់ ឬស្វែងរកវគ្គសិក្សាថ្មីៗនៅថ្ងៃនេះ។ បន្តដំណើរទៅមុខជានិច្ចដើម្បីសម្រេចគោលដៅរបស់អ្នក!' : 'Continue where you left off or explore new courses today. Keep moving forward to reach your learning goals!' }}
            </p>
          </div>

          <!-- Quick Shortcut Actions with Sleek Badges -->
          <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <Link
              href="/student/browse"
              class="px-4 py-2 rounded-full bg-white hover:bg-[#f0f4f9] dark:bg-[#282a2c] dark:hover:bg-[#333538] border border-[#c7c7c7] dark:border-[#3c4043] text-[#1f1f1f] dark:text-[#e3e3e3] font-medium text-xs flex items-center gap-2 transition-all hover:scale-105 shadow-2xs"
            >
              <svg class="w-4 h-4 text-[#0b57d0]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
              </svg>
              <span>{{ currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា' : 'Browse Courses' }}</span>
            </Link>

            <Link
              href="/student/notifications/announcements"
              class="px-4 py-2 rounded-full bg-white hover:bg-[#f0f4f9] dark:bg-[#282a2c] dark:hover:bg-[#333538] border border-[#c7c7c7] dark:border-[#3c4043] text-[#1f1f1f] dark:text-[#e3e3e3] font-medium text-xs flex items-center gap-2 transition-all hover:scale-105 shadow-2xs"
            >
              <svg class="w-4 h-4 text-[#0b57d0]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
              </svg>
              <span>{{ currentLang === 'km' ? 'សេចក្តីប្រកាស' : 'Announcements' }}</span>
            </Link>
          </div>
        </div>
      </div>

      <!-- OFFICIAL SPI DASHBOARD LOADING & KPI SYSTEM (DL-01 to DL-08) -->
      <section aria-busy="loading">
        <!-- 1. SKELETON LOADING STATE WITH SHIMMER (DL-01, DL-02, DL-06) -->
        <div
          v-if="loading && loadingStep === 'skeleton'"
          class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-4.5 animate-in fade-in duration-200"
        >
          <div
            v-for="n in 6"
            :key="n"
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-200 via-slate-100 to-slate-200 dark:from-[#1e1f20] dark:via-[#282a2c] dark:to-[#1e1f20] p-4.5 sm:p-5 flex flex-col justify-between shadow-xs border border-slate-300/60 dark:border-[#2d2f31] min-h-[148px]"
          >
            <!-- Shimmer Highlight Sweep Effect -->
            <div class="shimmer-sweep absolute inset-0 pointer-events-none"></div>

            <div>
              <div class="flex items-center justify-between">
                <!-- Title Placeholder Bar -->
                <div class="h-3 w-24 bg-white/70 dark:bg-[#333538] rounded-md"></div>
                <!-- Icon Placeholder Square -->
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/70 dark:bg-[#333538] shrink-0"></div>
              </div>
              <!-- Number Placeholder Block -->
              <div class="h-8 w-20 bg-white/80 dark:bg-[#333538] rounded-lg mt-3"></div>
              <!-- Subtitle Placeholder Bar -->
              <div class="h-3 w-28 bg-white/60 dark:bg-[#282a2c] rounded-md mt-2"></div>
            </div>

            <!-- Wave Silhouette Placeholder at Bottom -->
            <div class="mt-2.5 -mx-4.5 -mb-4.5 sm:-mx-5 sm:-mb-5 overflow-hidden rounded-b-2xl">
              <svg class="w-full h-10 sm:h-11 text-white/50 dark:text-[#282a2c]/60" viewBox="0 0 100 25" preserveAspectRatio="none">
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

        <!-- 3. LOADED / COUNTING GRADIENT CARDS (DL-03, DL-04, DL-07, DL-08) -->
        <div
          v-else
          class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-4.5 animate-in fade-in duration-300"
        >
          <Link
            v-for="stat in statsCards"
            :key="stat.type"
            :href="stat.href"
            :class="[
              'relative overflow-hidden rounded-2xl bg-gradient-to-br text-white p-4.5 sm:p-5 shadow-sm hover:shadow-xl hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer border border-white/10 min-h-[148px]',
              stat.gradient
            ]"
          >
            <div>
              <div class="flex items-start justify-between gap-1">
                <span class="text-[9.5px] sm:text-[10px] xl:text-[11px] font-bold tracking-tight uppercase text-white/90 leading-tight mr-1">{{ stat.title }}</span>
                <div class="text-white/95 group-hover:text-white group-hover:scale-110 transition-transform duration-200 shrink-0">
                  <!-- Book SVG (Enrolled) -->
                  <svg v-if="stat.type === 'enrolled'" class="w-9 h-9 sm:w-10 sm:h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                  </svg>
                  <!-- Graduation Cap SVG (In Progress) -->
                  <svg v-else-if="stat.type === 'in_progress'" class="w-9 h-9 sm:w-10 sm:h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                    <path d="M6 12v5c3 3 9 3 12 0v-5" />
                  </svg>
                  <!-- Check Circle SVG (Completed) -->
                  <svg v-else-if="stat.type === 'completed'" class="w-9 h-9 sm:w-10 sm:h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <path d="m9 12 2 2 4-4" />
                  </svg>
                  <!-- Trophy SVG (Certificates) -->
                  <svg v-else-if="stat.type === 'certificates'" class="w-9 h-9 sm:w-10 sm:h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                    <path d="M4 22h16" />
                    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                  </svg>
                  <!-- Clock SVG (Study Time) -->
                  <svg v-else-if="stat.type === 'study_time'" class="w-9 h-9 sm:w-10 sm:h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                  </svg>
                  <!-- Bar Chart SVG (Quiz Average) -->
                  <svg v-else class="w-9 h-9 sm:w-10 sm:h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10" />
                    <line x1="12" y1="20" x2="12" y2="4" />
                    <line x1="6" y1="20" x2="6" y2="14" />
                  </svg>
                </div>
              </div>

              <!-- Metric Number (Count-Up Active or Final Result) -->
              <h4 class="text-2xl sm:text-3xl font-black tracking-tight text-white mt-1.5 tabular-nums">
                {{ stat.value }}
              </h4>
              <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1 truncate">
                <span>{{ stat.subtitle }}</span>
              </p>
            </div>

            <!-- Sparkline Wave at Bottom (Exact Admin Style) -->
            <div class="mt-2.5 -mx-4.5 -mb-4.5 sm:-mx-5 sm:-mb-5 overflow-hidden rounded-b-2xl">
              <svg class="w-full h-10 sm:h-11 text-white" viewBox="0 0 100 25" preserveAspectRatio="none" aria-hidden="true">
                <g transform="translate(0 4)" opacity="0.10">
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
              <h2 class="text-xs font-bold text-[#1f1f1f] dark:text-[#c4c7c5] uppercase tracking-wider">
                {{ currentLang === 'km' ? 'បន្តការសិក្សា' : 'Continue Learning' }}
              </h2>
              <Link href="/student/my-courses/enrolled" class="text-xs text-[#0b57d0] dark:text-[#8ab4f8] hover:underline font-semibold">
                {{ currentLang === 'km' ? 'មើលទាំងអស់' : 'View All' }}
              </Link>
            </div>

            <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#0b57d0] hover:shadow-md hover:dark:border-[#1a73e8]/70 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-xl flex flex-col md:flex-row items-center gap-5 relative overflow-hidden transition-all duration-300">
              
              <!-- Real Course Thumbnail for Continue Learning -->
              <div class="relative w-full md:w-36 h-28 rounded-2xl overflow-hidden shrink-0 shadow-md bg-slate-900 border border-[#dfe3e7] dark:border-[#2d2f31]">
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
              <div class="flex-1 w-full space-y-3">
                <div class="space-y-1">
                  <span class="px-2.5 py-0.5 rounded-full bg-[#e8f0fe] dark:bg-[#1a73e8]/15 text-[#0b57d0] dark:text-[#a8c7fa] text-[10px] font-semibold border border-[#d2e3fc] dark:border-[#1a73e8]/30">
                    {{ currentLang === 'km' ? 'គ្រូបង្រៀន: ' : 'Teacher: ' }}{{ continueCourseData.teacher }}
                  </span>
                  <h3 class="text-base font-bold text-[#1f1f1f] dark:text-[#e3e3e3] leading-tight">
                    {{ continueCourseData.title }}
                  </h3>
                  <p class="text-xs text-[#444746] dark:text-[#c4c7c5] font-medium">
                    {{ continueCourseData.chapter }}
                  </p>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1.5">
                  <div class="w-full h-2 rounded-full bg-[#e1e3e1] dark:bg-[#282a2c] overflow-hidden">
                    <div class="h-full bg-[#0b57d0] dark:bg-[#a8c7fa] rounded-full" :style="{ width: continueCourseData.progress + '%' }"></div>
                  </div>
                  <div class="flex items-center justify-between text-[11px] text-[#747775] dark:text-[#8e918f]">
                    <span>{{ currentLang === 'km' ? 'មេរៀនចុងក្រោយ: ' : 'Last lesson: ' }}{{ continueCourseData.lastLesson }}</span>
                    <span class="font-mono flex items-center gap-1">
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      {{ continueCourseData.timeLeft }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- CTA Button (Google One Style Action Pill) -->
              <div class="w-full md:w-auto shrink-0">
                <Link
                  :href="continueCourseData.href"
                  class="w-full md:w-auto px-6 py-3 rounded-full bg-[#0b57d0] hover:bg-[#1557bf] dark:bg-[#a8c7fa] dark:hover:bg-[#b8d2fa] text-white dark:text-[#041e49] font-medium text-xs shadow-sm flex items-center justify-center gap-2 hover:scale-105 active:scale-95 transition-all text-center cursor-pointer"
                >
                  <span>{{ currentLang === 'km' ? 'បន្តការសិក្សា' : 'Continue Learning' }}</span>
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </Link>
              </div>

            </div>
          </div>

          <!-- MY COURSES SECTION WITH FILTER TABS -->
          <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <h2 class="text-xs font-bold text-[#1f1f1f] dark:text-[#c4c7c5] uppercase tracking-wider">
                  {{ currentLang === 'km' ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My Courses' }}
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
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
              <Link
                v-for="c in filteredCourses"
                :key="c.id"
                :href="c.href"
                class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#0b57d0] hover:dark:border-[#1a73e8]/70 hover:dark:bg-[#232426] rounded-2xl p-4.5 sm:p-5 shadow-xs dark:shadow-xl flex flex-col justify-between space-y-3.5 transition-all duration-200 hover:-translate-y-1 hover:shadow-md group"
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

                  <!-- Status Pill Badge on Card -->
                  <span :class="[c.badgeClass, 'absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold border backdrop-blur-md shadow-xs']">
                    {{ c.status === 'in_progress' ? (currentLang === 'km' ? 'កំពុងសិក្សា' : 'In Progress') : c.status === 'completed' ? (currentLang === 'km' ? 'បានបញ្ចប់' : 'Completed') : (currentLang === 'km' ? 'មិនទាន់ចាប់ផ្ដើម' : 'Not Started') }}
                  </span>
                </div>

                <!-- Course Title & Instructor -->
                <div class="space-y-1">
                  <h4 class="text-xs sm:text-sm font-bold text-[#1f1f1f] dark:text-[#e3e3e3] group-hover:text-[#0b57d0] dark:group-hover:text-[#8ab4f8] transition-colors line-clamp-2 min-h-[2.4rem] leading-snug" :title="c.title">
                    {{ c.title }}
                  </h4>
                  <p class="text-[11px] text-[#747775] dark:text-[#8e918f] flex items-center gap-1">
                    <svg class="w-3 h-3 text-[#747775] dark:text-[#8e918f] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    <span class="truncate">{{ c.teacher }}</span>
                  </p>
                </div>

                <!-- Progress Bar & Link -->
                <div class="space-y-1.5 pt-1 border-t border-[#dfe3e7] dark:border-[#2d2f31]">
                  <div class="w-full h-1.5 rounded-full bg-[#e1e3e1] dark:bg-[#282a2c] overflow-hidden">
                    <div
                      :class="c.progress === 100 ? 'bg-[#137333]' : 'bg-[#0b57d0] dark:bg-[#a8c7fa]'"
                      class="h-full rounded-full transition-all duration-300"
                      :style="{ width: c.progress + '%' }"
                    ></div>
                  </div>
                  <div class="flex items-center justify-between text-[11px]">
                    <span :class="c.progress === 100 ? 'text-[#137333]' : 'text-[#0b57d0] dark:text-[#8ab4f8]'" class="font-bold font-mono">{{ c.progress }}%</span>
                    <span class="text-[#747775] dark:text-[#8e918f] text-[10px] font-medium flex items-center gap-0.5 group-hover:text-[#0b57d0] dark:group-hover:text-[#8ab4f8] transition-colors">
                      <span>{{ currentLang === 'km' ? 'ទិដ្ឋភាពទូទៅ' : 'Overview' }}</span>
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
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
              class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4 block transition-all"
            >
              <div class="flex items-center justify-between">
                <Link href="/student/progress/learning-time" class="text-xs font-bold text-[#1f1f1f] dark:text-[#e3e3e3] hover:text-[#0b57d0] dark:hover:text-[#8ab4f8] uppercase tracking-wider flex items-center gap-1">
                  <span>{{ currentLang === 'km' ? 'ទិដ្ឋភាពទូទៅនៃការរៀន' : 'Learning Overview' }}</span>
                  <svg class="w-3 h-3 text-[#0b57d0] dark:text-[#8ab4f8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </Link>

                <!-- Range Selector (Google One Segmented Pill) -->
                <div class="flex items-center gap-1 text-[10px] bg-[#f0f4f9] dark:bg-[#131314] p-0.5 rounded-full border border-[#dfe3e7] dark:border-[#2d2f31]">
                  <button
                    @click="selectedRange = 'week'"
                    :class="selectedRange === 'week' ? 'bg-[#0b57d0] dark:bg-[#a8c7fa] text-white dark:text-[#041e49] font-medium shadow-xs' : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-[#e3e3e3]'"
                    class="px-2.5 py-0.5 rounded-full cursor-pointer transition-colors"
                  >
                    {{ currentLang === 'km' ? 'សប្តាហ៍' : 'Week' }}
                  </button>
                  <button
                    @click="selectedRange = 'last_week'"
                    :class="selectedRange === 'last_week' ? 'bg-[#0b57d0] dark:bg-[#a8c7fa] text-white dark:text-[#041e49] font-medium shadow-xs' : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-[#e3e3e3]'"
                    class="px-2.5 py-0.5 rounded-full cursor-pointer transition-colors"
                  >
                    {{ currentLang === 'km' ? 'សប្តាហ៍មុន' : 'Last' }}
                  </button>
                  <button
                    @click="selectedRange = 'month'"
                    :class="selectedRange === 'month' ? 'bg-[#0b57d0] dark:bg-[#a8c7fa] text-white dark:text-[#041e49] font-medium shadow-xs' : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-[#e3e3e3]'"
                    class="px-2.5 py-0.5 rounded-full cursor-pointer transition-colors"
                  >
                    {{ currentLang === 'km' ? 'ខែ' : 'Month' }}
                  </button>
                </div>
              </div>

              <!-- SVG Line Chart -->
              <div class="relative pt-2">
                <div class="absolute top-2 left-1/2 -translate-x-1/2 px-2 py-1 rounded-full bg-[#0b57d0] dark:bg-[#1a73e8] text-white text-[10px] font-medium shadow-sm">
                  {{ chartDataByRange[selectedRange].total }}
                </div>

                <svg viewBox="0 0 200 80" class="w-full h-20 overflow-visible">
                  <defs>
                    <linearGradient id="purpleGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#0b57d0" stop-opacity="0.25" />
                      <stop offset="100%" stop-color="#0b57d0" stop-opacity="0" />
                    </linearGradient>
                  </defs>
                  <path
                    :d="chartDataByRange[selectedRange].pathArea"
                    fill="url(#purpleGrad)"
                  />
                  <path
                    :d="chartDataByRange[selectedRange].pathLine"
                    fill="none"
                    stroke="#0b57d0"
                    stroke-width="2.5"
                    stroke-linecap="round"
                  />
                  <circle :cx="chartDataByRange[selectedRange].dotX" :cy="chartDataByRange[selectedRange].dotY" r="4" fill="#0b57d0" stroke="#ffffff" stroke-width="2" />
                </svg>

                <div class="flex justify-between text-[9px] text-[#747775] dark:text-[#8e918f] font-mono pt-1">
                  <span v-for="(lbl, idx) in chartDataByRange[selectedRange].labels" :key="idx">{{ lbl }}</span>
                </div>
              </div>
            </div>

            <!-- Study Time Donut Ring Chart Card -->
            <Link
              href="/student/progress/learning-time"
              class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#0b57d0] hover:dark:border-[#1a73e8]/70 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4 block transition-all hover:-translate-y-1"
            >
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-[#1f1f1f] dark:text-[#e3e3e3] uppercase tracking-wider flex items-center gap-1">
                  <span>{{ currentLang === 'km' ? 'ម៉ោងសិក្សា' : 'Study Time' }}</span>
                  <svg class="w-3 h-3 text-[#0b57d0] dark:text-[#8ab4f8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </h3>
                <span class="text-[10px] text-[#0b57d0] dark:text-[#8ab4f8] font-semibold">{{ currentLang === 'km' ? 'លម្អិត' : 'Breakdown' }}</span>
              </div>

              <div class="flex items-center justify-center gap-4">
                <!-- SVG Circular Donut -->
                <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                  <svg viewBox="0 0 36 36" class="w-24 h-24 -rotate-90">
                    <path
                      class="text-[#e1e3e1] dark:text-[#282a2c]"
                      stroke-width="4"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      class="text-[#0b57d0]"
                      stroke-dasharray="55, 100"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      class="text-indigo-500 dark:text-[#8ab4f8]"
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
                    <span class="text-[11px] font-black text-[#1f1f1f] dark:text-[#e3e3e3] leading-none">{{ props.stats?.learningTime || (currentLang === 'km' ? '២៨h ៤៥m' : '28h 45m') }}</span>
                    <span class="text-[8px] text-[#747775] dark:text-[#8e918f]">{{ currentLang === 'km' ? 'សរុប' : 'Total' }}</span>
                  </div>
                </div>

                <!-- Legend -->
                <div class="space-y-1.5 text-[10px] text-[#444746] dark:text-[#c4c7c5]">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-[#0b57d0]"></span>
                    <span>{{ currentLang === 'km' ? 'មេរៀន: 16h 30m' : 'Lessons: 16h 30m' }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 dark:bg-[#8ab4f8]"></span>
                    <span>{{ currentLang === 'km' ? 'ការអនុវត្ត: 8h 15m' : 'Practice: 8h 15m' }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-cyan-500 dark:bg-cyan-400"></span>
                    <span>{{ currentLang === 'km' ? 'កម្រងសំណួរ: 4h 00m' : 'Quiz: 4h 00m' }}</span>
                  </div>
                </div>
              </div>
            </Link>

            <!-- Quiz Performance Radial Gauge Card -->
            <Link
              href="/student/quizzes/scores"
              class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#0b57d0] hover:dark:border-[#1a73e8]/70 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4 block transition-all hover:-translate-y-1"
            >
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-[#1f1f1f] dark:text-[#e3e3e3] uppercase tracking-wider flex items-center gap-1">
                  <span>{{ currentLang === 'km' ? 'លទ្ធផលកម្រងសំណួរ' : 'Quiz Performance' }}</span>
                  <svg class="w-3 h-3 text-[#0b57d0] dark:text-[#8ab4f8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </h3>
                <span class="text-[10px] text-[#137333] dark:text-emerald-400 font-semibold">{{ currentLang === 'km' ? 'ជាប់' : 'Passing' }}</span>
              </div>

              <div class="flex items-center justify-center gap-4">
                <!-- Gauge Circle -->
                <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                  <svg viewBox="0 0 36 36" class="w-24 h-24 -rotate-90">
                    <path
                      class="text-[#e1e3e1] dark:text-[#282a2c]"
                      stroke-width="4"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      class="text-[#137333] dark:text-emerald-400"
                      stroke-dasharray="78, 100"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke="currentColor"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-sm font-black text-[#1f1f1f] dark:text-[#e3e3e3] leading-none">{{ props.stats?.averageScore || '78%' }}</span>
                    <span class="text-[8px] text-[#747775] dark:text-[#8e918f]">{{ currentLang === 'km' ? 'មធ្យមភាគ' : 'Average' }}</span>
                  </div>
                </div>

                <!-- Legend -->
                <div class="space-y-1.5 text-[10px] text-[#444746] dark:text-[#c4c7c5]">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-[#137333] dark:bg-emerald-400"></span>
                    <span>{{ currentLang === 'km' ? 'ត្រឹមត្រូវ: 78%' : 'Correct: 78%' }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>{{ currentLang === 'km' ? 'មិនត្រឹមត្រូវ: 22%' : 'Incorrect: 22%' }}</span>
                  </div>
                </div>
              </div>
            </Link>

          </div>

          <!-- RECENT ACTIVITY LIST -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h2 class="text-xs font-bold text-[#1f1f1f] dark:text-[#c4c7c5] uppercase tracking-wider">
                {{ currentLang === 'km' ? 'សកម្មភាពថ្មីៗ' : 'Recent Activity' }}
              </h2>
              <Link href="/student/progress/overview" class="text-xs text-[#0b57d0] dark:text-[#8ab4f8] hover:underline font-semibold">
                {{ currentLang === 'km' ? 'មើលទាំងអស់' : 'View All' }}
              </Link>
            </div>

            <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
              <Link
                v-for="act in recentActivities"
                :key="act.id"
                :href="act.href"
                class="flex items-center justify-between p-3 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#0b57d0]/40 dark:hover:border-[#3c4043] text-xs transition-all hover:bg-[#f0f4f9] dark:hover:bg-[#282a2c]"
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
                    <h4 class="font-bold text-[#1f1f1f] dark:text-[#e3e3e3]">{{ act.title }}</h4>
                    <p class="text-[10px] text-[#747775] dark:text-[#8e918f]">{{ act.course }}</p>
                  </div>
                </div>

                <span class="text-[10px] text-[#747775] dark:text-[#8e918f] font-mono shrink-0">{{ act.time }}</span>
              </Link>
            </div>
          </div>

        </div>

        <!-- RIGHT 4 COLUMNS -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- TODAY'S GOAL CARD -->
          <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-[#1f1f1f] dark:text-[#e3e3e3] uppercase tracking-wider">
                {{ currentLang === 'km' ? 'គោលដៅថ្ងៃនេះ' : "Today's Goal" }}
              </h3>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full bg-[#e6f4ea] dark:bg-emerald-500/20 text-[#137333] dark:text-emerald-400 text-[10px] font-semibold border border-[#ceead6] dark:border-emerald-500/30">
                  {{ currentLang === 'km' ? `រួចរាល់ ${todayGoal.completedCount} / ${todayGoal.totalCount}` : `${todayGoal.completedCount} / ${todayGoal.totalCount} Done` }}
                </span>
                <button
                  @click="isAddGoalOpen = !isAddGoalOpen"
                  class="w-6 h-6 rounded-full bg-[#e8f0fe] hover:bg-[#d2e3fc] dark:bg-purple-600/10 dark:hover:bg-purple-600/20 text-[#0b57d0] dark:text-[#8ab4f8] flex items-center justify-center text-xs font-bold cursor-pointer"
                  :title="currentLang === 'km' ? 'បន្ថែមគោលដៅ' : 'Add custom task'"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </button>
              </div>
            </div>

            <!-- Add Task Form -->
            <div v-if="isAddGoalOpen" class="p-2.5 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] space-y-2">
              <input
                v-model="newGoalTitle"
                type="text"
                :placeholder="currentLang === 'km' ? 'បញ្ចូលគោលដៅសិក្សាថ្មី...' : 'Enter new study goal...'"
                class="w-full px-3 py-1.5 rounded-full bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#3c4043] text-xs text-[#1f1f1f] dark:text-[#e3e3e3]"
                @keyup.enter="addNewGoal"
              />
              <div class="flex justify-end gap-2 text-xs">
                <button
                  @click="isAddGoalOpen = false"
                  class="px-3 py-1 rounded-full text-[#444746] hover:text-[#1f1f1f] dark:hover:text-[#e3e3e3] text-[11px]"
                >
                  {{ currentLang === 'km' ? 'បោះបង់' : 'Cancel' }}
                </button>
                <button
                  @click="addNewGoal"
                  class="px-4 py-1 rounded-full bg-[#0b57d0] hover:bg-[#1557bf] dark:bg-[#a8c7fa] dark:hover:bg-[#b8d2fa] text-white dark:text-[#041e49] font-medium text-[11px] shadow-sm"
                >
                  {{ currentLang === 'km' ? 'បន្ថែមគោលដៅ' : 'Add Goal' }}
                </button>
              </div>
            </div>

            <p class="text-xs text-[#444746] dark:text-[#c4c7c5] font-medium">
              {{ currentLang === 'km' ? 'រក្សាសន្ទុះនៃការសិក្សារបស់អ្នកឱ្យបន្តទៅមុខ!' : 'Keep your study momentum going!' }}
            </p>

            <div class="space-y-1.5">
              <div class="w-full h-1.5 rounded-full bg-[#e1e3e1] dark:bg-[#282a2c] overflow-hidden">
                <div class="h-full bg-[#137333] dark:bg-emerald-500 rounded-full transition-all duration-300" :style="{ width: todayGoal.percentage + '%' }"></div>
              </div>
              <p class="text-[10px] text-right text-[#747775] dark:text-[#8e918f]">
                {{ currentLang === 'km' ? `សម្រេចបាន ${todayGoal.percentage}%` : `${todayGoal.percentage}% completed` }}
              </p>
            </div>

            <!-- Task Checklist -->
            <div class="space-y-2 pt-1">
              <div
                v-for="item in todayGoal.items"
                :key="item.id"
                class="flex items-center justify-between p-2 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] text-xs hover:bg-[#f0f4f9] dark:hover:bg-[#282a2c] transition-colors"
              >
                <button
                  @click="toggleTask(item)"
                  type="button"
                  class="flex items-center gap-3 text-left flex-1 cursor-pointer group"
                >
                  <span v-if="item.completed" class="w-5 h-5 rounded-full bg-[#137333] text-white flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                  </span>
                  <span v-else class="w-5 h-5 rounded-full border-2 border-[#c7c7c7] dark:border-[#3c4043] group-hover:border-[#0b57d0] dark:group-hover:border-[#8ab4f8] transition-colors flex items-center justify-center shrink-0"></span>
                  <span :class="[item.completed ? 'line-through text-[#747775] dark:text-[#8e918f]' : 'text-[#1f1f1f] dark:text-[#e3e3e3] font-medium']" class="text-xs">
                    {{ currentLang === 'km' ? (item.titleKm || item.titleEn) : (item.titleEn || item.titleKm) }}
                  </span>
                </button>
                
                <Link :href="item.href" class="text-[#0b57d0] dark:text-[#8ab4f8] hover:underline text-[11px] font-semibold ml-2 flex items-center gap-0.5">
                  <span>{{ currentLang === 'km' ? 'បើក' : 'Open' }}</span>
                  <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </Link>
              </div>
            </div>
          </div>

          <!-- AI RECOMMENDED FOR YOU CARD (Matches Google One RECOMMENDED Card) -->
          <div class="relative overflow-hidden bg-white dark:bg-[#1e1f20] border-2 border-[#0b57d0] dark:border-[#1a73e8] rounded-3xl p-5 shadow-[0_4px_24px_rgba(11,87,208,0.08)] dark:shadow-[0_0_25px_rgba(26,115,232,0.18)] space-y-3.5 transition-all">
            <div class="flex items-center justify-between">
              <div class="space-y-1">
                <span class="text-[10px] font-bold text-[#0b57d0] dark:text-[#8ab4f8] tracking-widest uppercase">
                  RECOMMENDED
                </span>
                <div class="flex items-center gap-2">
                  <!-- Google Gemini 4-Point Sparkle Icon -->
                  <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-purple-600 to-indigo-600 dark:from-transparent dark:to-transparent text-white flex items-center justify-center shadow-sm shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
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
                  <h3 class="text-xs font-bold text-[#1f1f1f] dark:text-[#e3e3e3] uppercase tracking-wider">
                    {{ currentLang === 'km' ? 'AI ណែនាំសម្រាប់អ្នក' : 'AI Recommended For You' }}
                  </h3>
                </div>
              </div>

              <Link href="/student/ai-path/recommended" class="text-[10px] text-[#0b57d0] dark:text-[#8ab4f8] hover:underline font-semibold">
                {{ currentLang === 'km' ? 'ផែនទីសិក្សា' : 'Roadmap' }}
              </Link>
            </div>

            <p class="text-[11px] text-[#444746] dark:text-[#c4c7c5] font-medium">
              {{ currentLang === 'km' ? 'ផ្អែកលើពិន្ទុ Quiz ថ្មីៗ និងល្បឿននៃការរៀនរបស់អ្នក:' : 'Based on your recent quiz scores & learning pace:' }}
            </p>

            <div class="space-y-2.5">
              <div
                v-for="rec in aiRecommendations"
                :key="rec.id"
                class="p-3.5 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] space-y-2"
              >
                <div class="flex items-center justify-between">
                  <h4 class="font-bold text-[#1f1f1f] dark:text-[#e3e3e3] text-xs">{{ rec.title }}</h4>
                  <span class="px-2.5 py-0.5 rounded-full text-[9px] font-semibold bg-[#d3e3fd] text-[#041e49] border border-[#0b57d0]/20 dark:bg-[#1a73e8]/20 dark:text-[#a8c7fa] dark:border-[#1a73e8]/30">
                    {{ rec.badge }}
                  </span>
                </div>
                <p class="text-[10px] text-[#444746] dark:text-[#c4c7c5] leading-relaxed">{{ rec.desc }}</p>
                
                <div class="pt-1 flex items-center justify-between">
                  <!-- Google One Solid Blue Pill Action Button -->
                  <Link
                    :href="rec.actionHref"
                    class="inline-flex items-center gap-1.5 px-5 py-2 rounded-full text-[10px] font-medium shadow-sm transition-all hover:scale-105 active:scale-95 bg-[#0b57d0] hover:bg-[#1557bf] text-white dark:bg-[#a8c7fa] dark:hover:bg-[#b8d2fa] dark:text-[#041e49]"
                  >
                    <span>{{ rec.actionLabel }}</span>
                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                  </Link>

                  <!-- Gemini Signature Branding -->
                  <div class="flex items-center gap-1 text-[10px] text-[#747775] dark:text-[#8e918f] font-medium">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none">
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
              <h3 class="text-xs font-bold text-[#1f1f1f] dark:text-[#e3e3e3] uppercase tracking-wider">
                {{ currentLang === 'km' ? 'កាលវិភាគខាងមុខ' : 'Upcoming' }}
              </h3>
              <Link href="/student/calendar" class="text-[11px] text-[#0b57d0] dark:text-purple-400 hover:underline font-semibold">
                {{ currentLang === 'km' ? 'មើលប្រតិទិន' : 'View Calendar' }}
              </Link>
            </div>

            <div class="space-y-3">
              <Link
                v-for="item in upcomingEvents"
                :key="item.id"
                :href="item.href"
                class="flex items-start justify-between gap-2 p-2.5 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-[#0b57d0]/40 dark:hover:border-[#3c4043] text-xs transition-colors block hover:bg-[#f0f4f9] dark:hover:bg-[#282a2c]"
              >
                <div class="flex items-start gap-2.5">
                  <div class="w-7 h-7 rounded-full bg-[#e8f0fe] dark:bg-[#282a2c] border border-[#d2e3fc] dark:border-[#3c4043] text-[#0b57d0] dark:text-[#8ab4f8] flex items-center justify-center shrink-0 mt-0.5">
                    <!-- Calendar SVG -->
                    <svg v-if="item.type === 'quiz'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    <!-- Video Camera SVG -->
                    <svg v-else-if="item.type === 'live'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                    <!-- Document SVG -->
                    <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                  </div>
                  <div>
                    <h4 class="font-bold text-[#1f1f1f] dark:text-[#e3e3e3] text-[11px]">{{ item.title }}</h4>
                    <p class="text-[10px] text-[#747775] dark:text-[#8e918f]">{{ item.course }}</p>
                  </div>
                </div>

                <div class="text-right text-[10px] text-[#747775] dark:text-[#8e918f] shrink-0 font-mono">
                  <p class="font-bold text-[#1f1f1f] dark:text-[#e3e3e3]">{{ item.date }}</p>
                  <p>{{ item.time }}</p>
                </div>
              </Link>
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
                <h3 class="text-xs font-bold text-[#1f1f1f] dark:text-[#e3e3e3] uppercase tracking-wider">
                  {{ currentLang === 'km' ? 'ជំនួយការសិក្សា AI' : 'AI Study Assistant' }}
                </h3>
              </div>
              <Link
                href="/student/ai-tutor/chat"
                class="px-3 py-1 rounded-full bg-[#e8f0fe] dark:bg-[#1a73e8]/20 text-[#0b57d0] dark:text-[#a8c7fa] text-[10px] font-semibold border border-[#d2e3fc] dark:border-[#1a73e8]/30 hover:bg-[#0b57d0] hover:text-white transition-colors"
              >
                {{ currentLang === 'km' ? 'ជជែកពេញលេញ ↗' : 'Full Chat ↗' }}
              </Link>
            </div>

            <!-- Messages Stream -->
            <div class="space-y-2 max-h-44 overflow-y-auto custom-scrollbar pr-1">
              <div
                v-for="(msg, idx) in aiMessages"
                :key="idx"
                :class="[
                  msg.role === 'user' ? 'bg-[#d3e3fd] text-[#041e49] border border-[#0b57d0]/20 dark:bg-[#004a77] dark:border-[#004a77] dark:text-[#c2e7ff] ml-4' : 'bg-[#f8fafd] text-[#1f1f1f] border border-[#dfe3e7] dark:bg-[#131314] dark:border-[#2d2f31] dark:text-[#c4c7c5] mr-2',
                  'p-3 rounded-2xl text-[11px] space-y-1'
                ]"
              >
                <p class="whitespace-pre-wrap leading-relaxed">{{ msg.text }}</p>
              </div>

              <div v-if="isAiTyping" class="p-2.5 rounded-2xl bg-[#f8fafd] dark:bg-[#131314] text-[#747775] dark:text-[#8e918f] text-[10px] italic flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-[#0b57d0] animate-pulse"></span>
                <span>{{ currentLang === 'km' ? 'AI Tutor កំពុងគិត...' : 'AI Tutor is thinking...' }}</span>
              </div>
            </div>

            <!-- Quick Action Prompt Chips (Google One Selection Chips) -->
            <div class="grid grid-cols-2 gap-1.5 pt-1">
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'ពន្យល់មេរៀននេះ' : 'Explain this lesson')"
                type="button"
                class="p-2 rounded-full bg-white dark:bg-[#282a2c] hover:bg-[#f0f4f9] dark:hover:bg-[#333538] text-[#1f1f1f] dark:text-[#c4c7c5] text-[10px] font-medium border border-[#c7c7c7] dark:border-[#3c4043] text-center transition-colors cursor-pointer truncate shadow-2xs"
              >
                {{ currentLang === 'km' ? 'ពន្យល់មេរៀននេះ' : 'Explain this lesson' }}
              </button>
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'ផ្តល់ឧទាហរណ៍' : 'Give me an example')"
                type="button"
                class="p-2 rounded-full bg-white dark:bg-[#282a2c] hover:bg-[#f0f4f9] dark:hover:bg-[#333538] text-[#1f1f1f] dark:text-[#c4c7c5] text-[10px] font-medium border border-[#c7c7c7] dark:border-[#3c4043] text-center transition-colors cursor-pointer truncate shadow-2xs"
              >
                {{ currentLang === 'km' ? 'ផ្តល់ឧទាហរណ៍' : 'Give me an example' }}
              </button>
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'សង្ខេបប្រធានបទនេះ' : 'Summarize this topic')"
                type="button"
                class="p-2 rounded-full bg-white dark:bg-[#282a2c] hover:bg-[#f0f4f9] dark:hover:bg-[#333538] text-[#1f1f1f] dark:text-[#c4c7c5] text-[10px] font-medium border border-[#c7c7c7] dark:border-[#3c4043] text-center transition-colors cursor-pointer truncate shadow-2xs"
              >
                {{ currentLang === 'km' ? 'សង្ខេបប្រធានបទនេះ' : 'Summarize this topic' }}
              </button>
              <button
                @click="handleSendAiPrompt(currentLang === 'km' ? 'តើខ្ញុំគួររៀនអ្វីថ្ងៃនេះ?' : 'What should I study today?')"
                type="button"
                class="p-2 rounded-full bg-white dark:bg-[#282a2c] hover:bg-[#f0f4f9] dark:hover:bg-[#333538] text-[#1f1f1f] dark:text-[#c4c7c5] text-[10px] font-medium border border-[#c7c7c7] dark:border-[#3c4043] text-center transition-colors cursor-pointer truncate shadow-2xs"
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

          <!-- ACHIEVEMENTS BADGES CARD with Real SVGs -->
          <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-[#1f1f1f] dark:text-[#e3e3e3] uppercase tracking-wider">
                {{ currentLang === 'km' ? 'សមិទ្ធផល' : 'Achievements' }}
              </h3>
              <Link href="/student/certificates/achievements" class="text-[11px] text-[#0b57d0] dark:text-[#8ab4f8] hover:underline font-semibold">
                {{ currentLang === 'km' ? 'មើលទាំងអស់' : 'View All' }}
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
                <span class="text-[8px] text-[#747775] dark:text-[#8e918f] leading-tight w-12 truncate">{{ ach.title }}</span>
              </Link>
            </div>
          </div>

        </div>

      </div>

    </div>
  </StudentLayout>
</template>

<style scoped>
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

@media (prefers-reduced-motion: reduce) {
  .shimmer-sweep {
    animation: none;
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
