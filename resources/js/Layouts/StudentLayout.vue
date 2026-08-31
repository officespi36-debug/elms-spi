<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { i18n } from '@/Services/i18n'
import { useTheme } from '@/composables/useTheme'
import GlobalToast from '@/Components/GlobalToast.vue'
import AiTutorFloatingWidget from '@/Components/AiTutorFloatingWidget.vue'

const { isDark, toggleTheme } = useTheme()

const logoUrl = '/images/logo.png'
const actionBtnIcon = '/images/actions/action-button.svg'
const onlineIconUrl = '/images/nav/online.svg'
const offlineIconUrl = '/images/nav/offline.svg'

const props = defineProps<{
  title?: string
  breadcrumbs?: Array<{ label: string; href?: string }>
}>()

const page = usePage<any>()
const user = computed(() => page.props.auth?.user || {})

const studentDisplayName = computed(() => {
  return user.value?.name || 'Sok Pisey'
})

const studentId = computed(() => {
  return user.value?.student_id || 'STU2024001'
})

const studentMajor = computed(() => {
  return user.value?.major || 'IT & Networking'
})

const studentDepartment = computed(() => {
  return user.value?.department || 'Computer Science & Networking'
})

const isSidebarCollapsed = ref(false)
const sidebarOpen = ref(false)

const toggleSidebarCollapse = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
}

// --- Top Navbar State & Dropdown Logic ---
const searchQuery = ref('')
const isSearchOpen = ref(false)
const isNotificationOpen = ref(false)
const isProfileOpen = ref(false)
const isQuickActionOpen = ref(false)
const isLangOpen = ref(false)
const isStatusOpen = ref(false)
const isFullscreen = ref(false)

const currentLang = computed(() => i18n.locale.value || 'en')

const isOnline = ref(typeof window !== 'undefined' ? window.navigator.onLine : true)
const manualStatusOverride = ref<boolean | null>(null)

const updateOnlineStatus = () => {
  if (manualStatusOverride.value !== null) {
    isOnline.value = manualStatusOverride.value
  } else {
    isOnline.value = window.navigator.onLine
  }
}

const setStatusMode = (online: boolean) => {
  manualStatusOverride.value = online
  isOnline.value = online
  isStatusOpen.value = false
}

const languages = [
  { code: 'en', name: 'English', flagUrl: '/images/flags/en.svg' },
  { code: 'km', name: 'ភាសាខ្មែរ', flagUrl: '/images/flags/km.svg' },
]

const selectLanguage = (code: string) => {
  if (i18n.setLanguage) {
    i18n.setLanguage(code as 'km' | 'en')
  }
  isLangOpen.value = false
}

const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(err => console.log(err))
    isFullscreen.value = true
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen()
      isFullscreen.value = false
    }
  }
}

const closeAllDropdowns = () => {
  isSearchOpen.value = false
  isNotificationOpen.value = false
  isProfileOpen.value = false
  isQuickActionOpen.value = false
  isLangOpen.value = false
  isStatusOpen.value = false
}

const toggleDropdown = (target: 'search' | 'notification' | 'profile' | 'quick' | 'lang' | 'status') => {
  const current = target === 'search' ? isSearchOpen.value
    : target === 'notification' ? isNotificationOpen.value
    : target === 'profile' ? isProfileOpen.value
    : target === 'quick' ? isQuickActionOpen.value
    : target === 'lang' ? isLangOpen.value
    : isStatusOpen.value

  closeAllDropdowns()

  if (target === 'search') isSearchOpen.value = !current
  else if (target === 'notification') isNotificationOpen.value = !current
  else if (target === 'profile') isProfileOpen.value = !current
  else if (target === 'quick') isQuickActionOpen.value = !current
  else if (target === 'lang') isLangOpen.value = !current
  else if (target === 'status') isStatusOpen.value = !current
}

const handleKeydown = (e: KeyboardEvent) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    toggleDropdown('search')
  } else if (e.key === 'Escape') {
    closeAllDropdowns()
  }
}

// Dynamic Breadcrumb Calculation
const dynamicBreadcrumbs = computed(() => {
  if (props.breadcrumbs && props.breadcrumbs.length > 0) {
    return props.breadcrumbs
  }
  const url = page.url
  if (url.startsWith('/student/my-courses/current') || url.startsWith('/student/courses/current')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'My Courses', href: '/student/my-courses/enrolled' },
      { label: 'Web Development', href: '/student/my-courses/enrolled' },
      { label: 'Chapter 3 - JavaScript Functions' }
    ]
  }
  if (url.startsWith('/student/my-courses/enrolled') || url.startsWith('/student/courses/enrolled')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'My Courses', href: '/student/my-courses/enrolled' },
      { label: 'Enrolled Courses' }
    ]
  }
  if (url.startsWith('/student/my-courses/completed') || url.startsWith('/student/courses/completed')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'My Courses', href: '/student/my-courses/enrolled' },
      { label: 'Completed Courses' }
    ]
  }
  if (url.startsWith('/student/browse')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'My Courses', href: '/student/my-courses/enrolled' },
      { label: 'Browse Catalog' }
    ]
  }
  if (url.startsWith('/student/ai-tutor')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'AI Learning', href: '/student/ai-path/recommended' },
      { label: 'AI Study Assistant' }
    ]
  }
  if (url.startsWith('/student/ai-path/recommended')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'AI Learning', href: '/student/ai-path/recommended' },
      { label: 'Personalized Learning Path' }
    ]
  }
  if (url.startsWith('/student/ai-path/next-course')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'AI Learning', href: '/student/ai-path/recommended' },
      { label: 'Recommended Roadmap' }
    ]
  }
  if (url.startsWith('/student/ai-path/weak-topics')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'AI Learning', href: '/student/ai-path/recommended' },
      { label: 'Weak Topics Review' }
    ]
  }
  if (url.startsWith('/student/progress/learning-time') || url.startsWith('/student/progress/activity')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Progress & Analytics', href: '/student/progress/overview' },
      { label: 'Learning Activity' }
    ]
  }
  if (url.startsWith('/student/progress/achievements') || url.startsWith('/student/progress/skills')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Progress & Analytics', href: '/student/progress/overview' },
      { label: 'Skills Progress' }
    ]
  }
  if (url.startsWith('/student/progress/weekly')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Progress & Analytics', href: '/student/progress/overview' },
      { label: 'Quiz Performance' }
    ]
  }
  if (url.startsWith('/student/progress')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Progress & Analytics', href: '/student/progress/overview' },
      { label: 'Learning Overview' }
    ]
  }
  if (url.startsWith('/student/quizzes/history')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Quiz & Assessment', href: '/student/quizzes/practice' },
      { label: 'My Quiz Attempts' }
    ]
  }
  if (url.startsWith('/student/quizzes/scores')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Quiz & Assessment', href: '/student/quizzes/practice' },
      { label: 'Quiz Results' }
    ]
  }
  if (url.startsWith('/student/quizzes/assignments')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Quiz & Assessment', href: '/student/quizzes/practice' },
      { label: 'My Assessments' }
    ]
  }
  if (url.startsWith('/student/quizzes')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Quiz & Assessment', href: '/student/quizzes/practice' },
      { label: 'Available Quizzes' }
    ]
  }
  if (url.startsWith('/student/certificates/download-share')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Certificates', href: '/student/certificates/my-certificates' },
      { label: 'Available Certificates' }
    ]
  }
  if (url.startsWith('/student/certificates/verify')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Certificates', href: '/student/certificates/my-certificates' },
      { label: 'Certificate Verification' }
    ]
  }
  if (url.startsWith('/student/certificates')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Certificates', href: '/student/certificates/my-certificates' },
      { label: 'My Certificates' }
    ]
  }
  if (url.startsWith('/student/payments/pending')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Payment & Billing', href: '/student/payments/my-payments' },
      { label: 'Pay via ABA (KHR)' }
    ]
  }
  if (url.startsWith('/student/payments/methods')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Payment & Billing', href: '/student/payments/my-payments' },
      { label: 'Payment Methods' }
    ]
  }
  if (url.startsWith('/student/payments/transactions')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Payment & Billing', href: '/student/payments/my-payments' },
      { label: 'Transaction History' }
    ]
  }
  if (url.startsWith('/student/payments/settings')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Payment & Billing', href: '/student/payments/my-payments' },
      { label: 'Payment Settings' }
    ]
  }
  if (url.startsWith('/student/payments')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Payment & Billing', href: '/student/payments/my-payments' },
      { label: 'Course Fees & Invoices' }
    ]
  }
  if (url.startsWith('/student/notifications/announcements')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Notifications', href: '/student/notifications/announcements' },
      { label: 'System & Teacher Announcements' }
    ]
  }
  if (url.startsWith('/student/notifications/course-updates')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Notifications', href: '/student/notifications/announcements' },
      { label: 'Course Updates' }
    ]
  }
  if (url.startsWith('/student/notifications/assignments')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Notifications', href: '/student/notifications/announcements' },
      { label: 'Assignment Alerts' }
    ]
  }
  if (url.startsWith('/student/notifications/exams')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Notifications', href: '/student/notifications/announcements' },
      { label: 'Exam & Quiz Alerts' }
    ]
  }
  if (url.startsWith('/student/notifications/payments')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Notifications', href: '/student/notifications/announcements' },
      { label: 'Payment Notifications' }
    ]
  }
  if (url.startsWith('/student/notifications')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Notifications', href: '/student/notifications/announcements' },
      { label: 'All Notifications' }
    ]
  }
  if (url.startsWith('/student/calendar')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Calendar & Schedule' }
    ]
  }
  if (url.startsWith('/student/profile')) {
    return [
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Profile & Settings' }
    ]
  }
  return [
    { label: 'Dashboard', href: '/student/dashboard' }
  ]
})

const pageTitle = computed(() => {
  if (props.title) return props.title
  const crumbs = dynamicBreadcrumbs.value
  return crumbs.length > 0 ? crumbs[crumbs.length - 1].label : 'Student Panel'
})

// Quick Actions Dropdown items for Student
const quickActions = [
  { name: 'រៀនមេរៀនបន្ត (Continue Learning)', href: '/student/my-courses/current', iconUrl: '/images/actions/add-course.svg' },
  { name: 'ស្វែងរក Course ថ្មី (Browse Catalog)', href: '/student/browse', iconUrl: '/images/nav/sub/overview.svg' },
  { name: 'ធ្វើ Quiz / Practice Drill', href: '/student/quizzes/practice', iconUrl: '/images/nav/quiz.svg' },
  { name: 'សួរសំណួរ AI Assistant 24/7', href: '/student/ai-tutor/chat', iconUrl: '/images/nav/ai.svg' },
  { name: 'អនុវត្ត Practice Lab (5 Majors)', href: '/student/practice-lab', iconUrl: '/images/nav/sub/import-export.svg' },
  { name: 'ទាញយក Certificate', href: '/student/certificates/my-certificates', iconUrl: '/images/nav/certificate.svg' },
  { name: 'បង់ប្រាក់តាម ABA KHQR', href: '/student/payments/pending', iconUrl: '/images/nav/payment.svg' },
]

// Notifications Drawer Data for Student
const notifications = ref([
  {
    id: 1,
    title: 'ការរំលឹកបង់ប្រាក់ Payment Pending',
    desc: 'សូមទូទាត់ប្រាក់សម្រាប់ Database Systems ដើម្បីបើកមើលមេរៀន',
    time: '1 ម៉ោងមុន',
    type: 'payment',
    read: false,
    link: '/student/payments/pending'
  },
  {
    id: 2,
    title: 'Quiz ថ្មីអាចធ្វើបានហើយ: Module 2 Practice',
    desc: 'គ្រូ Sophea បានបើក Practice Quiz សម្រាប់ C Programming',
    time: '2 ម៉ោងមុន',
    type: 'quiz',
    read: false,
    link: '/student/quizzes/practice'
  },
  {
    id: 3,
    title: '🤖 AI Recommendation: មេរៀនថ្មីសម្រាប់អ្នក',
    desc: 'ផ្អែកលើពិន្ទុរបស់អ្នក សូមរៀនមេរៀន Operators & Pointers ឥឡូវនេះ',
    time: '5 ម៉ោងមុន',
    type: 'ai',
    read: true,
    link: '/student/ai-path/recommended'
  }
])

const unreadNotificationsCount = computed(() => {
  return notifications.value.filter(n => !n.read).length
})

const markAllAsRead = () => {
  notifications.value.forEach(n => n.read = true)
}

const markNotificationRead = (id: number) => {
  const item = notifications.value.find(n => n.id === id)
  if (item) item.read = true
}

// Global Command Search Links for Student (Covering 13 modules)
const searchableLinks = computed(() => [
  { name: 'Dashboard (ផ្ទាំងគ្រប់គ្រងដើម)', category: 'Dashboard', href: '/student/dashboard', iconUrl: '/images/nav/dashboard.svg' },
  { name: 'My Courses (Enrolled & Current)', category: 'My Courses', href: '/student/my-courses/enrolled', iconUrl: '/images/nav/courses.svg' },
  { name: 'Browse & Filter Courses', category: 'My Courses', href: '/student/browse', iconUrl: '/images/nav/sub/all-courses.svg' },
  { name: 'Learning Content (Videos, PDFs, Slides, Notes)', category: 'Content', href: '/student/content', iconUrl: '/images/nav/content.svg' },
  { name: 'Quiz & Assessment (Pre, Practice, Post, Assignments)', category: 'Assessment', href: '/student/quizzes', iconUrl: '/images/nav/quiz.svg' },
  { name: 'AI Learning Path (Personalized Path)', category: 'AI Path', href: '/student/ai-path', iconUrl: '/images/nav/ai.svg' },
  { name: 'AI Assistant / Tutor (English, 24/7 Chat, Feedback)', category: 'AI Assistant', href: '/student/ai-tutor', iconUrl: '/images/nav/ai.svg' },
  { name: 'Practice Lab (IT, Tourism, English, Agronomy, Social Work)', category: 'Practice Lab', href: '/student/practice-lab', iconUrl: '/images/nav/sub/import-export.svg' },
  { name: 'Progress Tracking & Badges', category: 'Progress', href: '/student/progress/overview', iconUrl: '/images/nav/progress.svg' },
  { name: 'Certificates (View, Download, QR Verify)', category: 'Certificates', href: '/student/certificates/my-certificates', iconUrl: '/images/nav/certificate.svg' },
  { name: 'Payment & ABA (Fees, KHQR, Receipts)', category: 'Payments', href: '/student/payments/my-payments', iconUrl: '/images/nav/payment.svg' },
  { name: 'Notifications & Alerts', category: 'Notifications', href: '/student/notifications/announcements', iconUrl: '/images/nav/notification.svg' },
  { name: 'Calendar & Schedule (Quiz dates, Deadlines)', category: 'Calendar', href: '/student/calendar/live-class', iconUrl: '/images/actions/announcement.svg' },
  { name: 'Profile Settings & Academic Info', category: 'Profile', href: '/student/profile?tab=personal', iconUrl: '/images/nav/sub/students.svg' },
])

const filteredSearchLinks = computed(() => {
  if (!searchQuery.value.trim()) return searchableLinks.value
  const q = searchQuery.value.toLowerCase()
  return searchableLinks.value.filter(l => l.name.toLowerCase().includes(q) || l.category.toLowerCase().includes(q))
})

interface NavSubItem {
  name: string
  khName: string
  href: string
  iconUrl?: string
  icon?: string
  badge?: string
}

interface NavItem {
  key: string
  name: string
  khName: string
  href?: string
  iconUrl?: string
  icon?: string
  badge?: { text: string; colorClass: string }
  children?: NavSubItem[]
  isAction?: boolean
  onClick?: () => void
}

const expandedModules = ref<Record<string, boolean>>({
  courses: false,
  aiPath: false,
  progress: false,
  quizzes: false,
  certificates: false,
  payments: false,
  notificationsModule: false,
})

const logout = () => {
  router.post('/logout')
}

// E-LMS Student - Structure as per official specification
const studentNav: NavItem[] = [
  {
    key: 'dashboard',
    name: 'Dashboard',
    khName: 'Dashboard',
    href: '/student/dashboard',
    iconUrl: '/images/nav/dashboard.svg',
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
  },
  {
    key: 'courses',
    name: 'My Courses',
    khName: 'My Courses',
    iconUrl: '/images/nav/courses.svg',
    icon: 'M12 14l9-5-9-5-9 5 9 5z',
    children: [
      { name: 'Continue Learning', khName: 'Continue Learning', href: '/student/my-courses/current', iconUrl: '/images/actions/add-course.svg' },
      { name: 'Enrolled Courses', khName: 'Enrolled Courses', href: '/student/my-courses/enrolled', iconUrl: '/images/nav/sub/all-courses.svg' },
      { name: 'Completed Courses', khName: 'Completed Courses', href: '/student/my-courses/completed', iconUrl: '/images/nav/sub/roles.svg' },
      { name: 'Browse Catalog', khName: 'Browse Catalog', href: '/student/browse', iconUrl: '/images/nav/sub/overview.svg' },
    ]
  },
  {
    key: 'aiPath',
    name: 'AI Learning',
    khName: 'AI Learning',
    iconUrl: '/images/nav/ai.svg',
    icon: 'M13 10V3L4 14h7v7l9-11h-7z',
    badge: { text: 'AI', colorClass: 'bg-purple-500/20 text-purple-300 border-purple-500/30' },
    children: [
      { name: 'AI Study Assistant', khName: 'AI Study Assistant', href: '/student/ai-tutor', iconUrl: '/images/nav/ai.svg' },
      { name: 'Personalized Learning Path', khName: 'Personalized Learning Path', href: '/student/ai-path/recommended', iconUrl: '/images/nav/ai.svg' },
      { name: 'Recommended Roadmap', khName: 'Recommended Roadmap', href: '/student/ai-path/next-course', iconUrl: '/images/nav/sub/all-courses.svg' },
      { name: 'Weak Topics Review', khName: 'Weak Topics Review', href: '/student/ai-path/weak-topics', iconUrl: '/images/nav/sub/failed.svg' },
    ]
  },
  {
    key: 'progress',
    name: 'Progress & Analytics',
    khName: 'Progress & Analytics',
    iconUrl: '/images/nav/progress.svg',
    icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    children: [
      { name: 'Learning Overview', khName: 'Learning Overview', href: '/student/progress/overview', iconUrl: '/images/nav/progress.svg' },
      { name: 'Course Progress', khName: 'Course Progress', href: '/student/my-courses/enrolled', iconUrl: '/images/nav/courses.svg' },
      { name: 'Quiz Performance', khName: 'Quiz Performance', href: '/student/progress/weekly', iconUrl: '/images/nav/analytics.svg' },
      { name: 'Skills Progress', khName: 'Skills Progress', href: '/student/progress/achievements', iconUrl: '/images/nav/sub/roles.svg' },
      { name: 'Learning Activity', khName: 'Learning Activity', href: '/student/progress/learning-time', iconUrl: '/images/nav/sub/history.svg' },
    ]
  },
  {
    key: 'quizzes',
    name: 'Quiz & Assessment',
    khName: 'Quiz & Assessment',
    iconUrl: '/images/nav/quiz.svg',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    children: [
      { name: 'Available Quizzes', khName: 'Available Quizzes', href: '/student/quizzes/practice', iconUrl: '/images/nav/quiz.svg' },
      { name: 'My Quiz Attempts', khName: 'My Quiz Attempts', href: '/student/quizzes/history', iconUrl: '/images/nav/sub/history.svg' },
      { name: 'Quiz Results', khName: 'Quiz Results', href: '/student/quizzes/scores', iconUrl: '/images/nav/sub/semesters.svg' },
      { name: 'My Assessments', khName: 'My Assessments', href: '/student/quizzes/assignments', iconUrl: '/images/nav/analytics.svg' },
    ]
  },
  {
    key: 'certificates',
    name: 'Certificates',
    khName: 'Certificates',
    iconUrl: '/images/nav/certificate.svg',
    icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    children: [
      { name: 'My Certificates', khName: 'My Certificates', href: '/student/certificates/my-certificates', iconUrl: '/images/nav/certificate.svg' },
      { name: 'Available Certificates', khName: 'Available Certificates', href: '/student/certificates/download-share', iconUrl: '/images/nav/sub/import-export.svg' },
      { name: 'Certificate Verification', khName: 'Certificate Verification', href: '/student/certificates/verify', iconUrl: '/images/actions/action-button.svg' },
    ]
  },
  {
    key: 'payments',
    name: 'Payment & Billing',
    khName: 'Payment & Billing',
    iconUrl: '/images/nav/payment.svg',
    icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    badge: { text: 'ABA', colorClass: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' },
    children: [
      { name: 'Course Fees & Invoices', khName: 'Course Fees & Invoices', href: '/student/payments/my-payments', iconUrl: '/images/nav/payment.svg' },
      { name: 'Pay via ABA (KHR)', khName: 'Pay via ABA (KHR)', href: '/student/payments/pending', iconUrl: '/images/actions/payment.svg' },
      { name: 'Payment Methods', khName: 'Payment Methods', href: '/student/payments/methods', iconUrl: '/images/actions/payment.svg' },
      { name: 'Transaction History', khName: 'Transaction History', href: '/student/payments/transactions', iconUrl: '/images/nav/sub/history.svg' },
      { name: 'Payment Settings', khName: 'Payment Settings', href: '/student/payments/settings', iconUrl: '/images/nav/sub/roles.svg' },
    ]
  },
  {
    key: 'notificationsModule',
    name: 'Notifications',
    khName: 'Notifications',
    iconUrl: '/images/nav/notification.svg',
    icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    badge: { text: '3', colorClass: 'bg-rose-500 text-white' },
    children: [
      { name: 'All Notifications', khName: 'All Notifications', href: '/student/notifications', iconUrl: '/images/nav/notification.svg' },
      { name: 'System & Teacher Announcements', khName: 'System & Teacher Announcements', href: '/student/notifications/announcements', iconUrl: '/images/actions/announcement.svg' },
      { name: 'Course Updates', khName: 'Course Updates', href: '/student/notifications/course-updates', iconUrl: '/images/nav/sub/semesters.svg' },
      { name: 'Assignment Alerts', khName: 'Assignment Alerts', href: '/student/notifications/assignments', iconUrl: '/images/nav/analytics.svg' },
      { name: 'Exam & Quiz Alerts', khName: 'Exam & Quiz Alerts', href: '/student/notifications/exams', iconUrl: '/images/nav/quiz.svg' },
      { name: 'Payment Notifications', khName: 'Payment Notifications', href: '/student/notifications/payments', iconUrl: '/images/nav/payment.svg' },
    ]
  },
  {
    key: 'calendar',
    name: 'Calendar & Schedule',
    khName: 'Calendar & Schedule',
    href: '/student/calendar/live-class',
    iconUrl: '/images/actions/announcement.svg',
    icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'
  },
  {
    key: 'profile',
    name: 'Profile & Settings',
    khName: 'Profile & Settings',
    href: '/student/profile?tab=personal',
    iconUrl: '/images/nav/sub/students.svg',
    icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
  }
]

const isSubActive = (subHref: string) => {
  const currentUrl = page.url
  if (subHref.includes('?')) {
    const [path, query] = subHref.split('?')
    if (!currentUrl.startsWith(path)) return false
    return currentUrl.includes(query)
  }
  return currentUrl === subHref || (currentUrl.startsWith(subHref.split('?')[0]) && !subHref.includes('?'))
}

const isChildActive = (children?: NavSubItem[]) => {
  if (!children) return false
  return children.some(child => isSubActive(child.href))
}

const toggleModule = (key: string) => {
  const isCurrentOpen = !!expandedModules.value[key]
  // Only one submenu expanded at a time: reset all
  Object.keys(expandedModules.value).forEach(k => {
    expandedModules.value[k] = false
  })
  expandedModules.value[key] = !isCurrentOpen
}

watch(
  () => page.url,
  () => {
    studentNav.forEach(item => {
      if (item.key && item.children && isChildActive(item.children)) {
        expandedModules.value[item.key] = true
      }
    })
  },
  { immediate: true }
)

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
})

const onIconError = (e: Event) => {
  const target = e.target as HTMLImageElement
  if (target) {
    target.style.display = 'none'
    const parent = target.parentElement
    if (parent) {
      const fallbackSvg = parent.querySelector('svg')
      if (fallbackSvg) {
        fallbackSvg.classList.remove('hidden')
        fallbackSvg.style.display = 'block'
      }
    }
  }
}
</script>

<template>
  <Head :title="pageTitle" />
  <GlobalToast />
  <div class="min-h-screen bg-slate-50 dark:bg-[#0B0F19] text-slate-800 dark:text-slate-200 selection:bg-indigo-500/30 transition-colors duration-200">
    <!-- Desktop Sidebar (Fixed Positioning exactly like Admin & Teacher layouts) -->
    <aside :class="[isSidebarCollapsed ? 'w-20 overflow-visible' : 'w-72', 'fixed inset-y-0 left-0 z-50 hidden flex-col bg-white/95 dark:bg-slate-900/90 backdrop-blur-xl border-r border-slate-200/90 dark:border-slate-800 lg:flex transition-all duration-300 shadow-sm dark:shadow-none']">
      
      <!-- Sidebar Header & Logo -->
      <div
        :class="[
          isSidebarCollapsed ? 'justify-center px-2' : 'justify-between px-4',
          'relative flex h-16 shrink-0 items-center border-b border-slate-200/90 dark:border-slate-800 transition-all duration-300 group/sidebar-header'
        ]"
      >
        <!-- Logo & Title Container -->
        <Link
          href="/student/dashboard"
          :class="[
            isSidebarCollapsed ? 'justify-center cursor-pointer' : '',
            'flex items-center gap-3 min-w-0 shrink-0 transition-all'
          ]"
        >
          <img
            :src="logoUrl"
            alt="E-LMS Logo"
            :class="[
              isSidebarCollapsed ? 'hover:scale-110' : '',
              'w-9 h-9 min-w-[36px] min-h-[36px] max-w-[36px] max-h-[36px] aspect-square rounded-full object-cover shadow-md shadow-indigo-500/20 ring-2 ring-indigo-500/30 shrink-0 transition-all duration-300'
            ]"
          />
          <div v-show="!isSidebarCollapsed" class="transition-opacity duration-200 min-w-0">
            <h1 class="text-sm font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-500 tracking-tight whitespace-nowrap">
              E-LMS Student
            </h1>
            <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase whitespace-nowrap">Student Panel</p>
          </div>
        </Link>

        <!-- Collapse / Expand Toggle Button -->
        <button
          @click="toggleSidebarCollapse"
          type="button"
          :title="isSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
          :class="[
            isSidebarCollapsed
              ? 'absolute -right-3 top-1/2 -translate-y-1/2 bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 border border-slate-200 dark:border-slate-700 shadow-md rounded-full p-1 hover:scale-110 hover:bg-slate-50 dark:hover:bg-slate-700 z-10'
              : 'p-1.5 rounded-lg text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 shrink-0 cursor-pointer',
            'transition-all duration-200 focus:outline-none'
          ]"
        >
          <svg
            :class="[isSidebarCollapsed ? 'rotate-180 w-3.5 h-3.5' : 'w-5 h-5', 'transition-transform duration-300']"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
          </svg>
        </button>
      </div>

      <!-- Navigation Tree (13 Core Modules) -->
      <nav
        :class="[
          isSidebarCollapsed ? 'px-0 overflow-visible' : 'px-3 custom-scrollbar overflow-y-auto',
          'flex flex-1 flex-col py-4 space-y-1'
        ]"
      >
        <ul role="list" class="space-y-1 w-full">
          <li
            v-for="item in studentNav"
            :key="item.key"
            :class="isSidebarCollapsed ? 'relative group/flyout flex justify-center w-full' : 'relative'"
          >
            <!-- Action Item (e.g. Log Out) -->
            <button
              v-if="item.isAction"
              @click="item.onClick ? item.onClick() : logout()"
              type="button"
              :title="isSidebarCollapsed ? (currentLang === 'km' ? item.khName : item.name) : undefined"
              :class="[
                'text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 hover:bg-red-500/10 border border-transparent font-medium',
                isSidebarCollapsed ? 'justify-center px-0 w-10 h-10 mx-auto' : 'px-3 w-full justify-between',
                'group flex items-center rounded-xl py-2 text-xs transition-all duration-200 cursor-pointer'
              ]"
            >
              <div :class="[isSidebarCollapsed ? 'justify-center w-full' : '', 'flex items-center gap-x-2.5 truncate']">
                <div class="relative flex items-center justify-center shrink-0">
                  <svg 
                    class="h-4 w-4 shrink-0 text-red-500 transition-transform duration-200 group-hover:scale-110"
                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon || 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1'" />
                  </svg>
                </div>
                <span v-show="!isSidebarCollapsed" class="truncate font-semibold">
                  {{ currentLang === 'km' ? item.khName : item.name }}
                </span>
              </div>
            </button>

            <!-- Direct Link (No Children) -->
            <Link
              v-else-if="!item.children || item.children.length === 0"
              :href="item.href!"
              prefetch="hover"
              :title="isSidebarCollapsed ? (currentLang === 'km' ? item.khName : item.name) : undefined"
              :class="[
                $page.url.startsWith(item.href!) 
                  ? 'bg-gradient-to-r from-purple-600/30 via-indigo-600/25 to-purple-600/10 text-indigo-700 dark:text-white border border-purple-500/40 font-bold shadow-sm shadow-purple-500/10' 
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-[#F1F5F9] hover:bg-slate-100 dark:hover:bg-slate-800/70 border border-transparent font-medium',
                isSidebarCollapsed ? 'justify-center px-0 w-10 h-10 mx-auto' : 'px-3 w-full justify-between',
                'group flex items-center rounded-xl py-2 text-xs transition-all duration-200'
              ]"
            >
              <div :class="[isSidebarCollapsed ? 'justify-center w-full' : '', 'flex items-center gap-x-2.5 truncate']">
                <div class="relative flex items-center justify-center shrink-0">
                  <img 
                    v-if="item.iconUrl"
                    :src="item.iconUrl" 
                    :alt="item.name"
                    loading="lazy"
                    decoding="async"
                    @error="onIconError"
                    class="w-4 h-4 object-contain shrink-0 filter drop-shadow-sm transition-transform duration-200 group-hover:scale-110"
                  />
                  <svg 
                    :class="[
                      $page.url.startsWith(item.href!) ? 'text-indigo-600 dark:text-purple-400' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-300',
                      item.iconUrl ? 'hidden' : '',
                      'h-4 w-4 shrink-0 transition-colors'
                    ]"
                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon || 'M4 6h16M4 12h16M4 18h16'" />
                  </svg>
                </div>
                <span v-show="!isSidebarCollapsed" class="truncate">
                  {{ currentLang === 'km' ? item.khName : item.name }}
                </span>
              </div>

              <span
                v-if="!isSidebarCollapsed && item.badge"
                :class="['px-1.5 py-0.5 rounded text-[10px] font-bold border ml-1 shrink-0', item.badge.colorClass]"
              >
                {{ item.badge.text }}
              </span>
            </Link>

            <!-- Collapsible Module with Submenu -->
            <div v-else class="space-y-0.5 w-full flex flex-col items-center">
              <button
                @click="toggleModule(item.key!)"
                type="button"
                :title="isSidebarCollapsed ? (currentLang === 'km' ? item.khName : item.name) : undefined"
                :class="[
                  isChildActive(item.children) 
                    ? 'bg-gradient-to-r from-purple-600/25 via-indigo-600/20 to-purple-600/10 text-indigo-700 dark:text-white border border-purple-500/30 font-semibold' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-[#F1F5F9] hover:bg-slate-100 dark:hover:bg-slate-800/60 border border-transparent',
                  isSidebarCollapsed ? 'justify-center px-0 w-10 h-10 mx-auto' : 'px-3 w-full justify-between',
                  'group flex items-center rounded-xl py-2 text-xs font-medium transition-all duration-200 cursor-pointer'
                ]"
              >
                <div :class="[isSidebarCollapsed ? 'justify-center w-full' : '', 'flex items-center gap-x-2.5 truncate']">
                  <div class="relative flex items-center justify-center shrink-0">
                    <img 
                      v-if="item.iconUrl"
                      :src="item.iconUrl" 
                      :alt="item.name"
                      loading="lazy"
                      decoding="async"
                      @error="onIconError"
                      class="w-4 h-4 object-contain shrink-0 filter drop-shadow-sm transition-transform duration-200 group-hover:scale-110"
                    />
                    <svg 
                      :class="[
                        isChildActive(item.children) ? 'text-indigo-600 dark:text-purple-400' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-300',
                        item.iconUrl ? 'hidden' : '',
                        'h-4 w-4 shrink-0 transition-colors'
                      ]"
                      fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon || 'M4 6h16M4 12h16M4 18h16'" />
                    </svg>
                  </div>
                  <span v-show="!isSidebarCollapsed" class="truncate">
                    {{ currentLang === 'km' ? item.khName : item.name }}
                  </span>
                </div>

                <div v-show="!isSidebarCollapsed" class="flex items-center gap-1">
                  <span
                    v-if="item.badge"
                    :class="['px-1.5 py-0.5 rounded text-[9px] font-bold border', item.badge.colorClass]"
                  >
                    {{ item.badge.text }}
                  </span>
                  <svg
                    :class="[
                      expandedModules[item.key!] ? 'rotate-180 text-indigo-600 dark:text-purple-400' : 'text-slate-400 dark:text-slate-500',
                      'w-3.5 h-3.5 transition-transform duration-200 shrink-0 ml-1'
                    ]"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </button>

              <!-- Submenu Items Tree -->
              <div
                v-show="!isSidebarCollapsed && expandedModules[item.key!]"
                class="relative ml-4 pl-3.5 space-y-0.5 my-1 transition-all duration-300 border-l border-purple-500/30 w-[calc(100%-18px)]"
              >
                <div
                  v-for="sub in item.children"
                  :key="sub.href"
                  class="relative"
                >
                  <Link
                    :href="sub.href"
                    prefetch="hover"
                    :class="[
                      isSubActive(sub.href)
                        ? 'bg-purple-600/20 text-indigo-700 dark:text-purple-300 font-bold border border-purple-500/30'
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/50 border border-transparent',
                      'flex items-center gap-x-2 rounded-lg px-2 py-1.5 text-[11px] transition-all duration-200 truncate'
                    ]"
                  >
                    <img
                      v-if="sub.iconUrl"
                      :src="sub.iconUrl"
                      :alt="sub.name"
                      loading="lazy"
                      decoding="async"
                      @error="onIconError"
                      class="w-3.5 h-3.5 object-contain shrink-0 filter drop-shadow-xs"
                    />
                    <span class="truncate">{{ currentLang === 'km' ? sub.khName : sub.name }}</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Flyout Popover for Collapsed Sidebar Mode (Has Children) -->
            <div
              v-if="isSidebarCollapsed && item.children && item.children.length > 0"
              class="absolute left-full top-0 ml-3.5 w-64 opacity-0 pointer-events-none group-hover/flyout:opacity-100 group-hover/flyout:pointer-events-auto transition-all duration-200 ease-out translate-x-1 group-hover/flyout:translate-x-0 z-50"
            >
              <div class="relative bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200 dark:border-slate-700/80 rounded-2xl p-3 shadow-2xl ring-1 ring-slate-800/80">
                <div class="absolute -left-1.5 top-3.5 w-3 h-3 bg-white dark:bg-slate-900 border-l border-b border-slate-200 dark:border-slate-700/80 rotate-45 z-10 pointer-events-none"></div>

                <div class="relative z-20 flex items-center justify-between px-2 py-1.5 mb-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                  <div class="flex items-center gap-2 min-w-0">
                    <img v-if="item.iconUrl" :src="item.iconUrl" :alt="item.name" class="w-4 h-4 object-contain shrink-0" />
                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ currentLang === 'km' ? item.khName : item.name }}</span>
                  </div>
                </div>

                <div class="relative z-20 space-y-1 max-h-[70vh] overflow-y-auto custom-scrollbar pr-1">
                  <Link
                    v-for="sub in item.children"
                    :key="sub.href"
                    :href="sub.href"
                    prefetch="hover"
                    :class="[
                      isSubActive(sub.href)
                        ? 'bg-purple-600/20 text-indigo-700 dark:text-purple-300 font-bold border border-purple-500/30'
                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800/70 border border-transparent',
                      'flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs transition-all duration-150 truncate'
                    ]"
                  >
                    <img v-if="sub.iconUrl" :src="sub.iconUrl" :alt="sub.name" loading="lazy" decoding="async" class="w-3.5 h-3.5 object-contain shrink-0" />
                    <span class="truncate">{{ currentLang === 'km' ? sub.khName : sub.name }}</span>
                  </Link>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </nav>

      <!-- Sidebar Footer User Card & Log Out (🚪) -->
      <div :class="[isSidebarCollapsed ? 'px-0 py-2' : 'p-3', 'mt-auto border-t border-slate-200/90 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/70 shrink-0 space-y-2']">
        <Link
          href="/student/profile?tab=personal"
          prefetch="hover"
          :class="[
            isSidebarCollapsed ? 'justify-center' : 'justify-between',
            'flex items-center gap-2.5 p-2 rounded-2xl bg-white dark:bg-slate-900/90 border border-slate-200/80 dark:border-slate-800 hover:border-purple-500/50 shadow-xs transition-all duration-200 group'
          ]"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="relative shrink-0">
              <img
                src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80"
                alt="Student Avatar"
                loading="lazy"
                decoding="async"
                class="w-9 h-9 rounded-full object-cover border border-purple-500/40 shadow-sm"
              />
              <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-slate-900"></span>
            </div>

            <div v-show="!isSidebarCollapsed" class="min-w-0 text-left">
              <p class="font-bold text-slate-800 dark:text-white text-xs truncate group-hover:text-purple-400 transition-colors">
                {{ user.name || 'Sok Pisey' }}
              </p>
              <p class="text-[10px] text-slate-400 truncate">
                Student ID: {{ user.student_id || 'STU2024001' }}
              </p>
            </div>
          </div>

          <div v-show="!isSidebarCollapsed" class="flex items-center gap-1 text-purple-400 group-hover:translate-x-0.5 transition-transform shrink-0">
            <span class="text-[10px] font-semibold">View Profile</span>
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
          </div>
        </Link>

        <!-- Log Out Button -->
        <button
          @click="logout"
          type="button"
          :title="isSidebarCollapsed ? 'Log Out' : undefined"
          :class="[
            isSidebarCollapsed ? 'justify-center w-10 h-10 mx-auto' : 'px-3 w-full justify-start gap-2.5',
            'flex items-center py-2 rounded-xl text-xs font-semibold text-rose-500 hover:text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer'
          ]"
        >
          <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span v-show="!isSidebarCollapsed">Log Out</span>
        </button>
      </div>
    </aside>

    <!-- Mobile Drawer Sidebar -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-slate-950/80 z-50 lg:hidden backdrop-blur-sm transition-opacity"
    ></div>

    <aside
      :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col lg:hidden transition-transform duration-300 ease-in-out shadow-2xl'
      ]"
    >
      <div class="h-16 px-4 flex items-center justify-between border-b border-slate-200 dark:border-slate-800 shrink-0">
        <div class="flex items-center gap-3">
          <img :src="logoUrl" alt="E-LMS Logo" loading="lazy" decoding="async" class="w-8 h-8 rounded-full object-cover ring-2 ring-indigo-500/30" />
          <span class="font-bold text-sm text-slate-900 dark:text-white">E-LMS Student</span>
        </div>
        <button @click="sidebarOpen = false" class="p-1 text-slate-400 hover:text-slate-900 dark:hover:text-white">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
        <template v-for="item in studentNav" :key="item.key">
          <!-- Action Item (e.g. Log Out) -->
          <button
            v-if="item.isAction"
            @click="sidebarOpen = false; item.onClick ? item.onClick() : logout()"
            type="button"
            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-500/10 transition-colors cursor-pointer"
          >
            <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon || 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1'" />
            </svg>
            <span>{{ currentLang === 'km' ? item.khName : item.name }}</span>
          </button>

          <!-- Direct Link (No Children) -->
          <Link
            v-else-if="!item.children || item.children.length === 0"
            :href="item.href!"
            prefetch="hover"
            @click="sidebarOpen = false"
            :class="[
              $page.url.startsWith(item.href!) ? 'bg-purple-500/20 text-purple-300 font-bold border border-purple-500/30' : 'text-slate-400 hover:text-white',
              'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium'
            ]"
          >
            <img v-if="item.iconUrl" :src="item.iconUrl" loading="lazy" decoding="async" class="w-4 h-4 object-contain" />
            <span>{{ currentLang === 'km' ? item.khName : item.name }}</span>
          </Link>
          <div v-else class="space-y-0.5">
            <button
              @click="toggleModule(item.key!)"
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs text-slate-400 hover:text-white font-medium"
            >
              <div class="flex items-center gap-2.5">
                <img v-if="item.iconUrl" :src="item.iconUrl" loading="lazy" decoding="async" class="w-4 h-4 object-contain" />
                <span>{{ currentLang === 'km' ? item.khName : item.name }}</span>
              </div>
              <svg :class="[expandedModules[item.key!] ? 'rotate-180 text-purple-400' : '', 'w-3.5 h-3.5 transition-transform']" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div v-show="expandedModules[item.key!]" class="pl-6 space-y-0.5 border-l border-purple-500/30 ml-4">
              <Link
                v-for="sub in item.children"
                :key="sub.href"
                :href="sub.href"
                prefetch="hover"
                @click="sidebarOpen = false"
                :class="[isSubActive(sub.href) ? 'text-purple-300 font-bold' : 'text-slate-400', 'block py-1.5 text-[11px] truncate']"
              >
                {{ currentLang === 'km' ? sub.khName : sub.name }}
              </Link>
            </div>
          </div>
        </template>
      </nav>
    </aside>

    <!-- STICKY TOP NAVBAR (Dynamically padded to account for fixed sidebar) -->
    <header :class="[isSidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72', 'sticky top-0 z-40 bg-white/95 dark:bg-[#0B0F19]/90 backdrop-blur-xl border-b border-slate-200/90 dark:border-slate-800/80 transition-all duration-300 shadow-xs']">
      <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8 gap-4">
        
        <!-- Left Side: Mobile Sidebar Hamburger & Multi-Segment Breadcrumbs -->
        <div class="flex items-center gap-3.5 min-w-0">
          <button
            @click="sidebarOpen = !sidebarOpen"
            type="button"
            class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg focus:outline-none transition-colors cursor-pointer lg:hidden"
            title="Toggle Mobile Navigation"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <!-- Breadcrumbs Multi-Segment matching reference design -->
          <nav aria-label="Breadcrumb" class="hidden sm:flex items-center gap-2 text-xs font-medium truncate">
            <template v-for="(crumb, idx) in dynamicBreadcrumbs" :key="crumb.label">
              <Link
                v-if="crumb.href"
                :href="crumb.href"
                prefetch="hover"
                class="text-slate-400 hover:text-purple-400 transition-colors truncate"
              >
                {{ crumb.label }}
              </Link>
              <span
                v-else
                class="text-slate-800 dark:text-white font-semibold truncate"
              >
                {{ crumb.label }}
              </span>
              <svg
                v-if="idx < dynamicBreadcrumbs.length - 1"
                class="w-3 h-3 text-slate-500 shrink-0"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
              </svg>
            </template>
          </nav>

          <!-- Glassmorphic Search Bar -->
          <div class="relative hidden md:block">
            <button
              @click="toggleDropdown('search')"
              type="button"
              class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/60 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-700/60 hover:border-indigo-500/40 text-xs transition-all w-56 lg:w-72 justify-between shadow-inner group"
            >
              <div class="flex items-center gap-2 truncate min-w-0 flex-1">
                <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="truncate text-slate-500 dark:text-slate-400 group-hover:text-slate-800 dark:group-hover:text-slate-300">ស្វែងរក... (Search 13 Modules)</span>
              </div>
              <kbd class="hidden lg:inline-flex items-center shrink-0 whitespace-nowrap px-1.5 py-0.5 text-[10px] font-mono font-semibold text-slate-400 bg-white dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700/60 rounded shadow-xs leading-none">ctrl k</kbd>
            </button>
          </div>
        </div>

        <!-- Right Side: Quick Action Button, Theme Toggle, Online Status, Language, Fullscreen, Notifications, User Profile -->
        <div class="flex items-center gap-1.5 sm:gap-2">
          <!-- Mobile Search Trigger -->
          <button
            @click="toggleDropdown('search')"
            type="button"
            class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg md:hidden focus:outline-none transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </button>

          <!-- Dark / Light Mode Toggle Button -->
          <button
            @click="toggleTheme"
            type="button"
            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            class="p-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl focus:outline-none transition-colors cursor-pointer"
          >
            <!-- Sun Icon (when Dark) -->
            <svg v-if="isDark" class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <!-- Moon Icon (when Light) -->
            <svg v-else class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          </button>

          <!-- Online Status Toggle Menu -->
          <div class="relative">
            <button
              @click="toggleDropdown('status')"
              type="button"
              class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/60 hover:bg-slate-200 dark:hover:bg-slate-800 text-xs font-semibold border border-slate-200 dark:border-slate-700/60 transition-all cursor-pointer"
            >
              <span :class="[isOnline ? 'bg-emerald-500' : 'bg-slate-500', 'w-2 h-2 rounded-full shadow-xs']"></span>
              <span class="hidden md:inline text-slate-700 dark:text-slate-300 text-[11px]">{{ isOnline ? 'Online' : 'Offline' }}</span>
            </button>

            <div
              v-show="isStatusOpen"
              class="absolute right-0 mt-2 w-48 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-2 z-50 space-y-1"
            >
              <button
                @click="setStatusMode(true)"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-left font-medium"
              >
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Online (មានវត្តមាន)</span>
              </button>
              <button
                @click="setStatusMode(false)"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-left font-medium"
              >
                <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                <span>Offline (ក្រៅបណ្តាញ)</span>
              </button>
            </div>
          </div>

          <!-- Language Switcher -->
          <div class="relative">
            <button
              @click="toggleDropdown('lang')"
              type="button"
              class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/60 hover:bg-slate-200 dark:hover:bg-slate-800 text-xs font-semibold border border-slate-200 dark:border-slate-700/60 transition-all cursor-pointer"
            >
              <span class="text-xs uppercase font-mono font-bold text-slate-700 dark:text-slate-300">{{ currentLang }}</span>
            </button>

            <div
              v-show="isLangOpen"
              class="absolute right-0 mt-2 w-36 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-1.5 z-50 space-y-1"
            >
              <button
                v-for="l in languages"
                :key="l.code"
                @click="selectLanguage(l.code)"
                :class="[
                  currentLang === l.code ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-300 font-bold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
                  'w-full flex items-center justify-between px-3 py-1.5 rounded-xl text-xs'
                ]"
              >
                <span>{{ l.name }}</span>
                <span v-if="currentLang === l.code" class="text-indigo-500 font-bold">✓</span>
              </button>
            </div>
          </div>

          <!-- Notifications Bell & Drawer Trigger -->
          <div class="relative">
            <button
              @click="toggleDropdown('notification')"
              type="button"
              class="relative p-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl focus:outline-none transition-colors cursor-pointer"
              title="Notifications"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span
                v-if="unreadNotificationsCount > 0"
                class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white dark:ring-slate-900"
              ></span>
            </button>

            <!-- Notifications Drawer -->
            <div
              v-show="isNotificationOpen"
              class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-3 z-50 space-y-2"
            >
              <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs font-bold text-slate-900 dark:text-white">ការជូនដំណឹង (Notifications)</span>
                <button @click="markAllAsRead" class="text-[11px] text-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-300 font-semibold">
                  Mark all read
                </button>
              </div>

              <div class="space-y-1.5 max-h-72 overflow-y-auto custom-scrollbar">
                <div
                  v-for="item in notifications"
                  :key="item.id"
                  @click="markNotificationRead(item.id)"
                  :class="[
                    !item.read ? 'bg-indigo-50/70 dark:bg-indigo-950/30 border-indigo-200 dark:border-indigo-500/20' : 'bg-slate-50 dark:bg-slate-800/40 border-slate-100 dark:border-slate-800',
                    'p-2.5 rounded-xl border flex flex-col gap-1 cursor-pointer transition-colors'
                  ]"
                >
                  <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ item.title }}</p>
                    <span class="text-[10px] text-slate-400 shrink-0 font-mono">{{ item.time }}</span>
                  </div>
                  <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">{{ item.desc }}</p>
                </div>
              </div>

              <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-center">
                <Link
                  href="/student/notifications/announcements"
                  @click="isNotificationOpen = false"
                  class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                  View All Notifications →
                </Link>
              </div>
            </div>
          </div>

          <!-- User Profile Dropdown Menu -->
          <div class="relative">
            <button
              @click="toggleDropdown('profile')"
              type="button"
              class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-bold flex items-center justify-center text-xs shadow">
                {{ studentDisplayName.charAt(0) }}
              </div>
              <span class="hidden lg:inline text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[120px]">
                {{ studentDisplayName }}
              </span>
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <!-- Profile Dropdown Menu -->
            <div
              v-show="isProfileOpen"
              class="absolute right-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-2 z-50 space-y-1"
            >
              <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800">
                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ studentDisplayName }}</p>
                <p class="text-[10px] text-slate-400 truncate">ID: {{ studentId }} • {{ studentMajor }}</p>
              </div>

              <Link
                href="/student/profile?tab=personal"
                @click="isProfileOpen = false"
                class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium"
              >
                <span>👤 Profile Settings (ការកំណត់គណនី)</span>
              </Link>
              <Link
                href="/student/my-courses/current"
                @click="isProfileOpen = false"
                class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium"
              >
                <span>📚 My Courses (វគ្គសិក្សារបស់ខ្ញុំ)</span>
              </Link>
              <Link
                href="/student/certificates/my-certificates"
                @click="isProfileOpen = false"
                class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium"
              >
                <span>🏆 My Certificates (វិញ្ញាបនបត្រ)</span>
              </Link>
              <Link
                href="/student/payments/my-payments"
                @click="isProfileOpen = false"
                class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium"
              >
                <span>💳 Payment & ABA (ការបង់ប្រាក់)</span>
              </Link>

              <div class="border-t border-slate-100 dark:border-slate-800 pt-1">
                <button
                  @click="logout"
                  class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 font-bold text-left cursor-pointer"
                >
                  <span>🚪 Log Out (ចាកចេញ)</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Global Command Search Modal (Ctrl+K) -->
    <div
      v-if="isSearchOpen"
      class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4 bg-slate-950/80 backdrop-blur-md transition-opacity"
      @click.self="isSearchOpen = false"
    >
      <div class="w-full max-w-xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center gap-3">
          <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search all 13 modules, quizzes, courses, labs, certificates..."
            class="w-full bg-transparent text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none"
            autofocus
          />
          <button @click="isSearchOpen = false" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-white font-bold">
            ESC
          </button>
        </div>

        <div class="max-h-80 overflow-y-auto custom-scrollbar p-2 space-y-1">
          <Link
            v-for="link in filteredSearchLinks"
            :key="link.name"
            :href="link.href"
            @click="isSearchOpen = false"
            class="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-indigo-50 dark:hover:bg-slate-800/80 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-300 transition-colors"
          >
            <div class="flex items-center gap-2.5">
              <img :src="link.iconUrl" class="w-4 h-4 object-contain" />
              <span class="text-xs font-medium">{{ link.name }}</span>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400">
              {{ link.category }}
            </span>
          </Link>

          <div v-if="filteredSearchLinks.length === 0" class="py-8 text-center text-xs text-slate-400">
            No matching student modules found for "{{ searchQuery }}"
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT WRAPPER -->
    <main :class="[isSidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72', 'transition-all duration-300 min-h-[calc(100vh-64px)]']">
      <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-6">
        <slot />
      </div>
    </main>

    <!-- 24/7 AI Tutor Assistant Floating Button & Drawer -->
    <AiTutorFloatingWidget />
  </div>
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
  background: rgba(148, 163, 184, 0.2);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.4);
}
</style>
