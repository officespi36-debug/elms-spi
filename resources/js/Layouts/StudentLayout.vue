<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { i18n } from '@/Services/i18n'
import { useTheme, initTheme, playNotificationSound, playClickSound } from '@/composables/useTheme'
import { useLoading } from '@/composables/useLoading'
import GlobalToast from '@/Components/GlobalToast.vue'
import GlobalLoadingOverlay from '@/Components/GlobalLoadingOverlay.vue'
import AiTutorFloatingWidget from '@/Components/AiTutorFloatingWidget.vue'
import LogoutConfirmModal from '@/Components/LogoutConfirmModal.vue'
import ProfileAccountDropdown from '@/Components/ProfileAccountDropdown.vue'

const { isDark, toggleTheme, setTheme } = useTheme()
const { showLoading, hideLoading } = useLoading()

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
const userPrefs = computed(() => (user.value?.preferences as Record<string, any>) || {})

const studentDisplayName = computed(() => {
  if (currentLang.value === 'km') {
    if (user.value?.name_kh) return user.value.name_kh
    if (user.value?.name === 'Mengly Khun') return 'ម៉េងលី ឃុន'
    return user.value?.name || 'និស្សិត'
  }
  return user.value?.name || 'Student'
})

const headerAvatarError = ref(false)
const userAvatarUrl = computed(() => {
  const av = user.value?.avatar_url || user.value?.avatar
  if (av && typeof av === 'string' && av.trim().length > 0 && !av.includes('ui-avatars.com')) {
    if (av.startsWith('http') || av.startsWith('data:') || av.startsWith('/')) {
      return av
    }
    if (av.startsWith('uploads/') || av.startsWith('images/') || av.startsWith('storage/')) {
      return `/${av}`
    }
    return `/storage/${av}`
  }
  return null
})

watch(userAvatarUrl, () => {
  headerAvatarError.value = false
})

const getStableAvatarBg = (name: string, id?: number | string) => {
  const colors = [
    '#6366f1', '#3b82f6', '#8b5cf6', '#ec4899', '#06b6d4', '#10b981', '#f59e0b', '#0284c7'
  ]
  const seed = String(name || '') + String(id || '')
  let hash = 0
  for (let i = 0; i < seed.length; i++) {
    hash = seed.charCodeAt(i) + ((hash << 5) - hash)
  }
  const index = Math.abs(hash) % colors.length
  return colors[index]
}

const headerUserInitial = computed(() => {
  const name = (studentDisplayName.value || '').trim()
  if (!name) return 'S'
  const parts = name.split(/\s+/)
  if (parts.length > 1) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
})

const enrolledCoursesCount = computed(() => {
  const statsCount = (page.props.stats as any)?.enrolledCount
  if (typeof statsCount === 'number') return statsCount
  const sharedCount = (page.props as any).enrolled_courses_count
  if (typeof sharedCount === 'number') return sharedCount
  return (page.props.enrollments as any[])?.length ?? 0
})

const avatarInput = ref<HTMLInputElement | null>(null)
const isUploadingAvatar = ref(false)

const triggerAvatarUpload = () => {
  avatarInput.value?.click()
}

const handleAvatarChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return

  const file = target.files[0]
  const formData = new FormData()
  formData.append('avatar', file)

  isUploadingAvatar.value = true

  router.post('/user/avatar', formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      isUploadingAvatar.value = false
      headerAvatarError.value = false
    },
    onError: () => {
      isUploadingAvatar.value = false
    },
    onFinish: () => {
      isUploadingAvatar.value = false
      if (target) target.value = ''
    }
  })
}

const studentId = computed(() => {
  return user.value?.student_id || user.value?.student_code || 'STU2026001'
})

const studentMajor = computed(() => {
  return user.value?.major?.name || user.value?.major || 'Information Technology'
})

const studentDepartment = computed(() => {
  return user.value?.department?.name || user.value?.department || 'Computer Science'
})

const newEnrollmentsCount = computed<number>(() => {
  return Number((page.props as any)?.new_enrollments_count || 0)
})

const isSidebarCollapsed = ref(false)
const sidebarOpen = ref(false)

let prefDebounceTimer: any = null
const saveUserPreferences = (newPrefs: Record<string, any>) => {
  if (typeof window === 'undefined') return
  if (newPrefs.sidebar_collapsed !== undefined) {
    localStorage.setItem('elms_student_sidebar_collapsed', String(newPrefs.sidebar_collapsed))
  }
  clearTimeout(prefDebounceTimer)
  prefDebounceTimer = setTimeout(async () => {
    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
      await fetch('/student/preferences', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json'
        },
        body: JSON.stringify(newPrefs)
      })
    } catch (e) {
      // offline fallback
    }
  }, 300)
}

const toggleSidebarCollapse = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
  saveUserPreferences({ sidebar_collapsed: isSidebarCollapsed.value })
}

const handleThemeToggle = (e?: MouseEvent) => {
  toggleTheme(e)
  saveUserPreferences({ theme: isDark.value ? 'dark' : 'light' })
}

// --- Top Navbar State & Dropdown Logic ---
const searchQuery = ref('')
const isSearchOpen = ref(false)
const searchInputRef = ref<HTMLInputElement | null>(null)

watch(isSearchOpen, async (open) => {
  if (open) {
    await nextTick()
    searchInputRef.value?.focus()
  } else {
    searchQuery.value = ''
  }
})

const handleSearchEnter = () => {
  const first = filteredSearchLinks.value[0]
  if (first?.href) {
    isSearchOpen.value = false
    router.visit(first.href)
  }
}
const isNotificationOpen = ref(false)
const isProfileOpen = ref(false)
const isQuickActionOpen = ref(false)
const isLangOpen = ref(false)
const isStatusOpen = ref(false)
const isFullscreen = ref(false)

const currentLang = computed(() => i18n.locale.value)

// Web Audio API Sound Synthesizer for Language Switch (Sweet chime identical to Login form)
let audioCtx: AudioContext | null = null

const getAudioContext = (): AudioContext | null => {
  if (typeof window === 'undefined') return null
  try {
    const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext
    if (!AudioContextClass) return null
    if (!audioCtx || audioCtx.state === 'closed') {
      audioCtx = new AudioContextClass()
    }
    return audioCtx
  } catch {
    return null
  }
}

const playTopBarSound = async () => {
  // Disabled: sound plays only on notification bell
  return
}

const toggleLanguage = () => {
  playTopBarSound()
  const nextLang = currentLang.value === 'km' ? 'en' : 'km'
  showLoading(nextLang === 'km' ? 'សូមរង់ចាំ កំពុងដំណើរការ...' : 'Please wait while loading')
  i18n.setLanguage(nextLang)
  saveUserPreferences({ locale: nextLang })
  try {
    router.reload({
      onFinish: () => hideLoading(150),
      onError: () => hideLoading(150)
    })
  } catch {
    hideLoading(150)
  }
}

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
  { code: 'km', name: 'ភាសាខ្មែរ', flagUrl: '/images/flags/km.svg' },
  { code: 'en', name: 'English', flagUrl: '/images/flags/en.svg' },
]

const selectLanguage = (code: string) => {
  i18n.setLanguage(code as 'km' | 'en')
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
      { label: 'Continue Learning' }
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

const breadcrumbTranslations: Record<string, { km: string; en: string }> = {
  'Dashboard': { km: 'ផ្ទាំងគ្រប់គ្រង', en: 'Dashboard' },
  'My Courses': { km: 'វគ្គសិក្សារបស់ខ្ញុំ', en: 'My Courses' },
  'Web Development': { km: 'ការអភិវឌ្ឍគេហទំព័រ', en: 'Web Development' },
  'Chapter 3 - JavaScript Functions': { km: 'ជំពូកទី ៣ - JavaScript Functions', en: 'Chapter 3 - JavaScript Functions' },
  'Enrolled Courses': { km: 'វគ្គសិក្សាដែលបានចុះឈ្មោះ', en: 'Enrolled Courses' },
  'Completed Courses': { km: 'វគ្គសិក្សាដែលបានបញ្ចប់', en: 'Completed Courses' },
  'Browse Catalog': { km: 'ស្វែងរកវគ្គសិក្សា', en: 'Browse Catalog' },
  'AI Learning': { km: 'ការរៀនជាមួយ AI', en: 'AI Learning' },
  'AI Study Assistant': { km: 'ជំនួយការសិក្សា AI', en: 'AI Study Assistant' },
  'Personalized Learning Path': { km: 'គន្លងសិក្សាផ្ទាល់ខ្លួន', en: 'Personalized Learning Path' },
  'Recommended Roadmap': { km: 'ផែនទីបង្ហាញផ្លូវណែនាំ', en: 'Recommended Roadmap' },
  'Weak Topics Review': { km: 'ពិនិត្យឡើងវិញចំណុចខ្សោយ', en: 'Weak Topics Review' },
  'Progress & Analytics': { km: 'វឌ្ឍនភាព & ការវិភាគ', en: 'Progress & Analytics' },
  'Learning Activity': { km: 'សកម្មភាពនៃការរៀន', en: 'Learning Activity' },
  'Skills Progress': { km: 'ការរីកចម្រើនជំនាញ', en: 'Skills Progress' },
  'Quiz Performance': { km: 'លទ្ធផលកម្រងសំណួរ', en: 'Quiz Performance' },
  'Learning Overview': { km: 'ទិដ្ឋភាពទូទៅនៃការរៀន', en: 'Learning Overview' },
  'Quiz & Assessment': { km: 'កម្រងសំណួរ & ការវាយតម្លៃ', en: 'Quiz & Assessment' },
  'My Quiz Attempts': { km: 'ប្រវត្តិនៃការឆ្លើយសំណួរ', en: 'My Quiz Attempts' },
  'Quiz Results': { km: 'លទ្ធផលកម្រងសំណួរ', en: 'Quiz Results' },
  'My Assessments': { km: 'ការវាយតម្លៃរបស់ខ្ញុំ', en: 'My Assessments' },
  'Available Quizzes': { km: 'កម្រងសំណួរដែលមាន', en: 'Available Quizzes' },
  'Certificates': { km: 'វិញ្ញាបនបត្រ', en: 'Certificates' },
  'My Certificates': { km: 'វិញ្ញាបនបត្ររបស់ខ្ញុំ', en: 'My Certificates' },
  'Available Certificates': { km: 'វិញ្ញាបនបត្រដែលអាចទាញយកបាន', en: 'Available Certificates' },
  'Certificate Verification': { km: 'ផ្ទៀងផ្ទាត់វិញ្ញាបនបត្រ', en: 'Certificate Verification' },
  'Notifications': { km: 'ការជូនដំណឹង', en: 'Notifications' },
  'System & Teacher Announcements': { km: 'សេចក្តីប្រកាសពីប្រព័ន្ធ & គ្រូ', en: 'System & Teacher Announcements' },
  'Course Updates': { km: 'បច្ចុប្បន្នភាពវគ្គសិក្សា', en: 'Course Updates' },
  'Assignment Alerts': { km: 'ការដាស់តឿនកិច្ចការផ្ទះ', en: 'Assignment Alerts' },
  'Exam & Quiz Alerts': { km: 'ការដាស់តឿនការប្រឡង & Quiz', en: 'Exam & Quiz Alerts' },
  'All Notifications': { km: 'ការជូនដំណឹងទាំងអស់', en: 'All Notifications' },
  'Calendar & Schedule': { km: 'កាលវិភាគ & ប្រតិទិន', en: 'Calendar & Schedule' },
  'Profile & Settings': { km: 'គណនី & ការកំណត់', en: 'Profile & Settings' },
}

const getBreadcrumbLabel = (label: string): string => {
  if (breadcrumbTranslations[label]) {
    return currentLang.value === 'km' ? breadcrumbTranslations[label].km : breadcrumbTranslations[label].en
  }
  return label
}

const pageTitle = computed(() => {
  if (props.title) return props.title
  const crumbs = dynamicBreadcrumbs.value
  return crumbs.length > 0 ? getBreadcrumbLabel(crumbs[crumbs.length - 1].label) : (currentLang.value === 'km' ? 'ផ្ទាំងសិស្ស' : 'Student Panel')
})

// Quick Actions Dropdown items for Student
const quickActions = [
  { name: 'រៀនមេរៀនបន្ត (Continue Learning)', href: '/student/my-courses/current', iconUrl: '/images/actions/add-course.svg' },
  { name: 'ស្វែងរក Course ថ្មី (Browse Catalog)', href: '/student/browse', iconUrl: '/images/nav/sub/overview.svg' },
  { name: 'ធ្វើ Quiz / Practice Drill', href: '/student/quizzes/practice', iconUrl: '/images/nav/quiz.svg' },
  { name: 'សួរសំណួរ AI Assistant 24/7', href: '/student/ai-tutor/chat', iconUrl: '/images/nav/ai.svg' },
  { name: 'អនុវត្ត Practice Lab (5 Majors)', href: '/student/practice-lab', iconUrl: '/images/nav/sub/import-export.svg' },
  { name: 'ទាញយក Certificate', href: '/student/certificates/my-certificates', iconUrl: '/images/nav/certificate.svg' },
]

interface NotificationItem {
  id: number
  title_km: string
  title_en: string
  desc_km: string
  desc_en: string
  time_km: string
  time_en: string
  type: 'quiz' | 'ai' | 'lesson' | 'announcement'
  defaultRead?: boolean
  read: boolean
  link: string
  created_at?: string
}

const NOTIF_STORAGE_KEY = computed(() => `elms_student_read_notif_ids_v2_${user.value?.id || 'guest'}`)

// Base notifications from Inertia DB shared props
const dbNotifications = computed<NotificationItem[]>(() => {
  return (page.props.student_notifications as any)?.items || []
})

const notifications = ref<NotificationItem[]>([])

const syncNotificationsWithStorage = () => {
  const source = dbNotifications.value
  let readIds: number[] = []
  if (typeof window !== 'undefined') {
    try {
      const raw = localStorage.getItem(NOTIF_STORAGE_KEY.value)
      if (raw) readIds = JSON.parse(raw)
    } catch {}
  }
  notifications.value = source.map(n => ({
    ...n,
    read: n.read || readIds.includes(n.id)
  }))
}

watch(dbNotifications, () => {
  syncNotificationsWithStorage()
}, { immediate: true })

const loadPersistedNotifications = () => {
  syncNotificationsWithStorage()
}

const savePersistedReadIds = () => {
  if (typeof window === 'undefined') return
  try {
    const readIds = notifications.value.filter(n => n.read).map(n => n.id)
    localStorage.setItem(NOTIF_STORAGE_KEY.value, JSON.stringify(readIds))
  } catch {}
}

const notifTab = ref<'all' | 'unread'>('all')

const unreadNotificationsCount = computed(() => {
  return notifications.value.filter(n => !n.read).length
})

const filteredNotifications = computed(() => {
  if (notifTab.value === 'unread') {
    return notifications.value.filter(n => !n.read)
  }
  return notifications.value
})

const handleNotificationToggle = () => {
  playNotificationSound()
  toggleDropdown('notification')
}

const markAllAsRead = async () => {
  playClickSound()
  const allIds = notifications.value.map(n => n.id)
  notifications.value.forEach(n => { n.read = true })
  savePersistedReadIds()
  try {
    const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    await fetch('/student/api/notifications/read-all', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ ids: allIds })
    })
  } catch {}
}

const markNotificationRead = async (id: number) => {
  const item = notifications.value.find(n => n.id === id)
  if (item && !item.read) {
    item.read = true
    savePersistedReadIds()
    try {
      const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
      await fetch('/student/api/notifications/read', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ id })
      })
    } catch {}
  }
}

const handleNotificationItemClick = (notif: NotificationItem) => {
  playClickSound()
  markNotificationRead(notif.id)
  isNotificationOpen.value = false
  if (notif.link) {
    router.visit(notif.link)
  }
}

const getNotifTitle = (n: NotificationItem) => currentLang.value === 'km' ? n.title_km : n.title_en
const getNotifDesc = (n: NotificationItem) => currentLang.value === 'km' ? n.desc_km : n.desc_en
const getNotifTime = (n: NotificationItem) => currentLang.value === 'km' ? n.time_km : n.time_en

const handleDocumentClick = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.nav-dropdown-scope')) {
    closeAllDropdowns()
  }
}

// Global Command Search Links for Student (Covering 12 modules)
const searchableLinks = computed(() => {
  const isKm = currentLang.value === 'km'
  return [
    {
      name: isKm ? 'ផ្ទាំងគ្រប់គ្រង (Dashboard)' : 'Dashboard (Overview)',
      nameEn: 'Dashboard (Overview)',
      nameKm: 'ផ្ទាំងគ្រប់គ្រង (Dashboard)',
      category: isKm ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard',
      categoryEn: 'Dashboard',
      categoryKm: 'ផ្ទាំងគ្រប់គ្រង',
      href: '/student/dashboard',
      iconUrl: '/images/nav/dashboard.svg'
    },
    {
      name: isKm ? 'វគ្គសិក្សារបស់ខ្ញុំ (បានចុះឈ្មោះ & កំពុងរៀន)' : 'My Courses (Enrolled & Current)',
      nameEn: 'My Courses (Enrolled & Current)',
      nameKm: 'វគ្គសិក្សារបស់ខ្ញុំ (បានចុះឈ្មោះ & កំពុងរៀន)',
      category: isKm ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My Courses',
      categoryEn: 'My Courses',
      categoryKm: 'វគ្គសិក្សារបស់ខ្ញុំ',
      href: '/student/my-courses/enrolled',
      iconUrl: '/images/nav/courses.svg'
    },
    {
      name: isKm ? 'ស្វែងរក និងជ្រើសរើសវគ្គសិក្សា' : 'Browse & Filter Courses',
      nameEn: 'Browse & Filter Courses',
      nameKm: 'ស្វែងរក និងជ្រើសរើសវគ្គសិក្សា',
      category: isKm ? 'វគ្គសិក្សា' : 'Browse Courses',
      categoryEn: 'Browse Courses',
      categoryKm: 'វគ្គសិក្សា',
      href: '/student/browse',
      iconUrl: '/images/nav/sub/all-courses.svg'
    },
    {
      name: isKm ? 'មាតិកាសិក្សា (វីដេអូ, PDF, ស្លាយ, កំណត់ត្រា)' : 'Learning Content (Videos, PDFs, Slides, Notes)',
      nameEn: 'Learning Content (Videos, PDFs, Slides, Notes)',
      nameKm: 'មាតិកាសិក្សា (វីដេអូ, PDF, ស្លាយ, កំណត់ត្រា)',
      category: isKm ? 'មាតិកា' : 'Content',
      categoryEn: 'Content',
      categoryKm: 'មាតិកា',
      href: '/student/content',
      iconUrl: '/images/nav/content.svg'
    },
    {
      name: isKm ? 'កម្រងសំណួរ & ការវាយតម្លៃ (Pre, Practice, Post, កិច្ចការ)' : 'Quiz & Assessment (Pre, Practice, Post, Assignments)',
      nameEn: 'Quiz & Assessment (Pre, Practice, Post, Assignments)',
      nameKm: 'កម្រងសំណួរ & ការវាយតម្លៃ (Pre, Practice, Post, កិច្ចការ)',
      category: isKm ? 'ការវាយតម្លៃ' : 'Assessment',
      categoryEn: 'Assessment',
      categoryKm: 'ការវាយតម្លៃ',
      href: '/student/quizzes',
      iconUrl: '/images/nav/quiz.svg'
    },
    {
      name: isKm ? 'គន្លងសិក្សា AI (Personalized Path)' : 'AI Learning Path (Personalized Path)',
      nameEn: 'AI Learning Path (Personalized Path)',
      nameKm: 'គន្លងសិក្សា AI (Personalized Path)',
      category: isKm ? 'គន្លង AI' : 'AI Path',
      categoryEn: 'AI Path',
      categoryKm: 'គន្លង AI',
      href: '/student/ai-path',
      iconUrl: '/images/nav/ai.svg'
    },
    {
      name: isKm ? 'ជំនួយការ AI / គ្រូបង្រៀនផ្ទាល់ខ្លួន (24/7 Chat & Feedback)' : 'AI Assistant / Tutor (English, 24/7 Chat, Feedback)',
      nameEn: 'AI Assistant / Tutor (English, 24/7 Chat, Feedback)',
      nameKm: 'ជំនួយការ AI / គ្រូបង្រៀនផ្ទាល់ខ្លួន (24/7 Chat & Feedback)',
      category: isKm ? 'ជំនួយការ AI' : 'AI Assistant',
      categoryEn: 'AI Assistant',
      categoryKm: 'ជំនួយការ AI',
      href: '/student/ai-tutor',
      iconUrl: '/images/nav/ai.svg'
    },
    {
      name: isKm ? 'បន្ទប់អនុវត្តជាក់ស្តែង (IT, ទេសចរណ៍, អង់គ្លេស, កសិកម្ម, ការងារសង្គម)' : 'Practice Lab (IT, Tourism, English, Agronomy, Social Work)',
      nameEn: 'Practice Lab (IT, Tourism, English, Agronomy, Social Work)',
      nameKm: 'បន្ទប់អនុវត្តជាក់ស្តែង (IT, ទេសចរណ៍, អង់គ្លេស, កសិកម្ម, ការងារសង្គម)',
      category: isKm ? 'បន្ទប់អនុវត្ត' : 'Practice Lab',
      categoryEn: 'Practice Lab',
      categoryKm: 'បន្ទប់អនុវត្ត',
      href: '/student/practice-lab',
      iconUrl: '/images/nav/sub/import-export.svg'
    },
    {
      name: isKm ? 'តាមដានវឌ្ឍនភាពសិក្សា & មេដាយកិត្តិយស' : 'Progress Tracking & Badges',
      nameEn: 'Progress Tracking & Badges',
      nameKm: 'តាមដានវឌ្ឍនភាពសិក្សា & មេដាយកិត្តិយស',
      category: isKm ? 'វឌ្ឍនភាព' : 'Progress',
      categoryEn: 'Progress',
      categoryKm: 'វឌ្ឍនភាព',
      href: '/student/progress/overview',
      iconUrl: '/images/nav/progress.svg'
    },
    {
      name: isKm ? 'វិញ្ញាបនបត្រ (មើល, ទាញយក, ផ្ទៀងផ្ទាត់ QR)' : 'Certificates (View, Download, QR Verify)',
      nameEn: 'Certificates (View, Download, QR Verify)',
      nameKm: 'វិញ្ញាបនបត្រ (មើល, ទាញយក, ផ្ទៀងផ្ទាត់ QR)',
      category: isKm ? 'វិញ្ញាបនបត្រ' : 'Certificates',
      categoryEn: 'Certificates',
      categoryKm: 'វិញ្ញាបនបត្រ',
      href: '/student/certificates/my-certificates',
      iconUrl: '/images/nav/certificate.svg'
    },
    {
      name: isKm ? 'ការជូនដំណឹង & សេចក្តីប្រកាស' : 'Notifications & Alerts',
      nameEn: 'Notifications & Alerts',
      nameKm: 'ការជូនដំណឹង & សេចក្តីប្រកាស',
      category: isKm ? 'ការជូនដំណឹង' : 'Notifications',
      categoryEn: 'Notifications',
      categoryKm: 'ការជូនដំណឹង',
      href: '/student/notifications/announcements',
      iconUrl: '/images/nav/notification.svg'
    },
    {
      name: isKm ? 'កាលវិភាគ & ប្រតិទិន (កាលបរិច្ឆេទ Quiz, ថ្ងៃកំណត់)' : 'Calendar & Schedule (Quiz dates, Deadlines)',
      nameEn: 'Calendar & Schedule (Quiz dates, Deadlines)',
      nameKm: 'កាលវិភាគ & ប្រតិទិន (កាលបរិច្ឆេទ Quiz, ថ្ងៃកំណត់)',
      category: isKm ? 'ប្រតិទិន' : 'Calendar',
      categoryEn: 'Calendar',
      categoryKm: 'ប្រតិទិន',
      href: '/student/calendar/live-class',
      iconUrl: '/images/nav/calendar.svg'
    },
    {
      name: isKm ? 'ការកំណត់គណនី & ព័ត៌មានសិក្សា' : 'Profile Settings & Academic Info',
      nameEn: 'Profile Settings & Academic Info',
      nameKm: 'ការកំណត់គណនី & ព័ត៌មានសិក្សា',
      category: isKm ? 'គណនី' : 'Profile',
      categoryEn: 'Profile',
      categoryKm: 'គណនី',
      href: '/student/profile?tab=personal',
      iconUrl: '/images/nav/sub/students.svg'
    },
  ]
})

const filteredSearchLinks = computed(() => {
  if (!searchQuery.value.trim()) return searchableLinks.value
  const q = searchQuery.value.toLowerCase()
  return searchableLinks.value.filter(l =>
    l.name.toLowerCase().includes(q) ||
    l.nameEn.toLowerCase().includes(q) ||
    l.nameKm.toLowerCase().includes(q) ||
    l.category.toLowerCase().includes(q) ||
    l.categoryEn.toLowerCase().includes(q) ||
    l.categoryKm.toLowerCase().includes(q)
  )
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
  notificationsModule: false,
})

const isLogoutModalOpen = ref(false)
const isLoggingOut = ref(false)

const triggerLogout = () => {
  isProfileOpen.value = false
  isLogoutModalOpen.value = true
}

const confirmLogout = () => {
  isLoggingOut.value = true
  router.post('/logout', {}, {
    replace: true,
    preserveState: false,
    preserveScroll: false,
    onSuccess: () => {
      isLogoutModalOpen.value = false
      window.location.href = '/'
    },
    onError: () => {
      isLogoutModalOpen.value = false
      window.location.href = '/'
    },
    onFinish: () => {
      isLoggingOut.value = false
    }
  })
}

const logout = () => {
  triggerLogout()
}

// E-LMS Student - Structure as per official specification
const studentNav: NavItem[] = [
  {
    key: 'dashboard',
    name: 'Dashboard',
    khName: 'ផ្ទាំងគ្រប់គ្រង',
    href: '/student/dashboard',
    iconUrl: '/images/nav/dashboard.svg',
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
  },
  {
    key: 'courses',
    name: 'My Courses',
    khName: 'វគ្គសិក្សារបស់ខ្ញុំ',
    iconUrl: '/images/nav/courses.svg',
    icon: 'M12 14l9-5-9-5-9 5 9 5z',
    children: [
      { name: 'Continue Learning', khName: 'រៀនបន្ត', href: '/student/my-courses/current' },
      { name: 'Enrolled Courses', khName: 'វគ្គសិក្សាដែលបានចុះឈ្មោះ', href: '/student/my-courses/enrolled' },
      { name: 'Completed Courses', khName: 'វគ្គសិក្សាដែលបានបញ្ចប់', href: '/student/my-courses/completed' },
      { name: 'Browse Catalog', khName: 'ស្វែងរកវគ្គសិក្សា', href: '/student/browse' },
    ]
  },
  {
    key: 'aiPath',
    name: 'AI Learning',
    khName: 'ការរៀនជាមួយ AI',
    iconUrl: '/images/nav/ai.svg',
    icon: 'M13 10V3L4 14h7v7l9-11h-7z',
    badge: { text: 'AI', colorClass: 'bg-purple-500/20 text-purple-300 border-purple-500/30' },
    children: [
      { name: 'AI Study Assistant', khName: 'ជំនួយការសិក្សា AI', href: '/student/ai-tutor', iconUrl: '/images/nav/ai.svg' },
      { name: 'Personalized Learning Path', khName: 'គន្លងសិក្សាផ្ទាល់ខ្លួន', href: '/student/ai-path/recommended', iconUrl: '/images/nav/ai.svg' },
      { name: 'Recommended Roadmap', khName: 'ផែនទីបង្ហាញផ្លូវណែនាំ', href: '/student/ai-path/next-course', iconUrl: '/images/nav/sub/all-courses.svg' },
      { name: 'Weak Topics Review', khName: 'ពិនិត្យឡើងវិញចំណុចខ្សោយ', href: '/student/ai-path/weak-topics', iconUrl: '/images/nav/sub/failed.svg' },
    ]
  },
  {
    key: 'progress',
    name: 'Progress & Analytics',
    khName: 'វឌ្ឍនភាព & ការវិភាគ',
    iconUrl: '/images/nav/progress.svg',
    icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    children: [
      { name: 'Learning Overview', khName: 'ទិដ្ឋភាពទូទៅនៃការរៀន', href: '/student/progress/overview', iconUrl: '/images/nav/progress.svg' },
      { name: 'Course Progress', khName: 'វឌ្ឍនភាពតាមវគ្គសិក្សា', href: '/student/progress', iconUrl: '/images/nav/courses.svg' },
      { name: 'Quiz Performance', khName: 'លទ្ធផលកម្រងសំណួរ', href: '/student/progress/weekly', iconUrl: '/images/nav/analytics.svg' },
      { name: 'Skills Progress', khName: 'ការរីកចម្រើនជំនាញ', href: '/student/progress/achievements', iconUrl: '/images/nav/sub/roles.svg' },
      { name: 'Learning Activity', khName: 'សកម្មភាពនៃការរៀន', href: '/student/progress/learning-time', iconUrl: '/images/nav/sub/history.svg' },
    ]
  },
  {
    key: 'quizzes',
    name: 'Quiz & Assessment',
    khName: 'កម្រងសំណួរ & ការវាយតម្លៃ',
    iconUrl: '/images/nav/quiz.svg',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    children: [
      { name: 'Available Quizzes', khName: 'កម្រងសំណួរដែលមាន', href: '/student/quizzes/practice', iconUrl: '/images/nav/quiz.svg' },
      { name: 'My Quiz Attempts', khName: 'ប្រវត្តិនៃការឆ្លើយសំណួរ', href: '/student/quizzes/history', iconUrl: '/images/nav/sub/history.svg' },
      { name: 'Quiz Results', khName: 'លទ្ធផលកម្រងសំណួរ', href: '/student/quizzes/scores', iconUrl: '/images/nav/sub/semesters.svg' },
      { name: 'My Assessments', khName: 'ការវាយតម្លៃរបស់ខ្ញុំ', href: '/student/quizzes/assignments', iconUrl: '/images/nav/analytics.svg' },
    ]
  },
  {
    key: 'certificates',
    name: 'Certificates',
    khName: 'វិញ្ញាបនបត្រ',
    iconUrl: '/images/nav/certificate.svg',
    icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    children: [
      { name: 'My Certificates', khName: 'វិញ្ញាបនបត្ររបស់ខ្ញុំ', href: '/student/certificates/my-certificates', iconUrl: '/images/nav/certificate.svg' },
      { name: 'Available Certificates', khName: 'វិញ្ញាបនបត្រដែលអាចទាញយកបាន', href: '/student/certificates/download-share', iconUrl: '/images/nav/sub/import-export.svg' },
      { name: 'Certificate Verification', khName: 'ផ្ទៀងផ្ទាត់វិញ្ញាបនបត្រ', href: '/student/certificates/verify', iconUrl: '/images/actions/action-button.svg' },
    ]
  },
  {
    key: 'notificationsModule',
    name: 'Notifications',
    khName: 'ការជូនដំណឹង',
    iconUrl: '/images/nav/notification.svg',
    icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    badge: { text: '3', colorClass: 'bg-rose-500 text-white' },
    children: [
      { name: 'All Notifications', khName: 'ការជូនដំណឹងទាំងអស់', href: '/student/notifications', iconUrl: '/images/nav/notification.svg' },
      { name: 'System & Teacher Announcements', khName: 'សេចក្តីប្រកាសពីប្រព័ន្ធ & គ្រូ', href: '/student/notifications/announcements', iconUrl: '/images/actions/announcement.svg' },
      { name: 'Course Updates', khName: 'បច្ចុប្បន្នភាពវគ្គសិក្សា', href: '/student/notifications/course-updates', iconUrl: '/images/nav/sub/semesters.svg' },
      { name: 'Assignment Alerts', khName: 'ការដាស់តឿនកិច្ចការផ្ទះ', href: '/student/notifications/assignments', iconUrl: '/images/nav/analytics.svg' },
      { name: 'Exam & Quiz Alerts', khName: 'ការដាស់តឿនការប្រឡង & Quiz', href: '/student/notifications/exams', iconUrl: '/images/nav/quiz.svg' },
    ]
  },
  {
    key: 'calendar',
    name: 'Calendar & Schedule',
    khName: 'កាលវិភាគ & ប្រតិទិន',
    href: '/student/calendar/live-class',
    iconUrl: '/images/nav/calendar.svg',
    icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'
  },
  {
    key: 'profile',
    name: 'Profile & Settings',
    khName: 'គណនី & ការកំណត់',
    href: '/student/profile?tab=personal',
    iconUrl: '/images/nav/sub/students.svg',
    icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
  }
]

const sidebarNavItems = computed<NavItem[]>(() => {
  return studentNav.map(item => {
    if (item.key === 'notificationsModule') {
      const count = unreadNotificationsCount.value
      return {
        ...item,
        badge: count > 0 ? {
          text: count > 99 ? '99+' : String(count),
          colorClass: 'bg-rose-500 text-white border-rose-600 shadow-xs'
        } : undefined
      }
    }
    if (item.key === 'courses') {
      const count = newEnrollmentsCount.value
      return {
        ...item,
        badge: (count > 0 && !expandedModules.value['courses']) ? {
          text: count > 9 ? '9+' : String(count),
          colorClass: 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 border-purple-200 dark:border-purple-700/50 shadow-xs'
        } : undefined
      }
    }
    return item
  })
})

const isSubActive = (subHref: string) => {
  const currentUrl = page.url
  const currentPath = currentUrl.split(/[?#]/)[0]
  const subPath = subHref.split(/[?#]/)[0]

  // Continue Learning: active on /student/my-courses/current, /continue, and any lesson route
  if (subHref === '/student/my-courses/current' || subHref.includes('/my-courses/current')) {
    return currentPath === '/student/my-courses/current' ||
           currentPath.includes('/continue') ||
           currentPath.includes('/lessons/') ||
           currentUrl.includes('/lessons/')
  }

  // Enrolled Courses: active on /student/my-courses/enrolled or /student/courses/enrolled
  if (subHref === '/student/my-courses/enrolled') {
    return currentPath === '/student/my-courses/enrolled' || currentPath === '/student/courses/enrolled'
  }

  // Completed Courses
  if (subHref === '/student/my-courses/completed') {
    return currentPath === '/student/my-courses/completed' || currentPath === '/student/courses/completed'
  }

  // Browse Catalog
  if (subHref === '/student/browse' || subHref.includes('/browse')) {
    return currentPath === '/student/browse' || currentPath.startsWith('/student/browse/')
  }

  const [, subQuery] = subHref.split('?')
  if (subQuery) {
    if (currentPath !== subPath) return false
    return currentUrl.includes(subQuery)
  }

  if (currentPath === subPath) {
    return true
  }

  // Deep child route (e.g. /student/quizzes/practice/123 matches /student/quizzes/practice)
  // Ensure we don't match sibling routes where another nav item has an exact match
  if (currentPath.startsWith(subPath + '/')) {
    const hasMoreSpecificMatch = studentNav.some(nav =>
      nav.children?.some(c => c.href.split(/[?#]/)[0] === currentPath)
    )
    return !hasMoreSpecificMatch
  }

  return false
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
    const currentUrl = page.url
    const currentPath = currentUrl.split(/[?#]/)[0]

    // If navigating inside any course, lesson, or my-courses route, prioritize expanding 'courses'
    let activeKey: string | null = null
    if (
      currentPath.includes('/lessons/') ||
      currentPath.startsWith('/student/my-courses') ||
      currentPath.startsWith('/student/courses')
    ) {
      activeKey = 'courses'
    } else {
      const activeModule = studentNav.find(item => item.children && isChildActive(item.children))
      activeKey = activeModule?.key || null
    }

    Object.keys(expandedModules.value).forEach(k => {
      expandedModules.value[k] = (activeKey === k)
    })
  },
  { immediate: true }
)

onMounted(() => {
  if (typeof userPrefs.value.sidebar_collapsed === 'boolean') {
    isSidebarCollapsed.value = userPrefs.value.sidebar_collapsed
  } else if (typeof window !== 'undefined') {
    const local = localStorage.getItem('elms_student_sidebar_collapsed')
    if (local !== null) {
      isSidebarCollapsed.value = local === 'true'
    }
  }

  const storedLocalTheme = typeof window !== 'undefined' ? localStorage.getItem('theme') : null
  if (!storedLocalTheme && userPrefs.value.theme && (userPrefs.value.theme === 'dark' || userPrefs.value.theme === 'light')) {
    if ((userPrefs.value.theme === 'dark') !== isDark.value) {
      setTheme(userPrefs.value.theme)
    }
  }

  if (userPrefs.value.locale && (userPrefs.value.locale === 'km' || userPrefs.value.locale === 'en')) {
    if (userPrefs.value.locale !== currentLang.value) {
      i18n.setLanguage(userPrefs.value.locale)
    }
  }

  loadPersistedNotifications()
  window.addEventListener('keydown', handleKeydown)
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
  window.addEventListener('click', handleDocumentClick)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
  window.removeEventListener('click', handleDocumentClick)
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
  <GlobalLoadingOverlay />
  <div class="min-h-screen bg-[#f8fafd] dark:bg-[#131314] text-[#1f1f1f] dark:text-[#e3e3e3] selection:bg-[#0b57d0]/20 transition-colors duration-200 notranslate" translate="no">
    <!-- Desktop Sidebar (Fixed Positioning exactly like Admin & Teacher layouts) -->
    <aside
      :style="{ viewTransitionName: 'student-sidebar', top: 'var(--notice-h, 0px)', height: 'calc(100vh - var(--notice-h, 0px))' }"
      :class="[isSidebarCollapsed ? 'w-[72px] overflow-visible' : 'w-72', 'fixed inset-y-0 left-0 z-50 hidden flex-col bg-white/95 dark:bg-[#131314] backdrop-blur-xl border-r border-[#dfe3e7] dark:border-[#28292a] lg:flex transition-all duration-250 ease-in-out shadow-xs dark:shadow-none']"
    >
      
      <!-- Sidebar Header & Logo -->
      <div
        :class="[
          isSidebarCollapsed ? 'justify-center px-2' : 'justify-between px-4',
          'relative flex h-16 shrink-0 items-center border-b border-[#dfe3e7] dark:border-[#28292a] transition-all duration-300'
        ]"
      >
        <!-- Logo & Title Container -->
        <button
          v-if="isSidebarCollapsed"
          @click="toggleSidebarCollapse"
          type="button"
          :title="currentLang === 'km' ? 'ពង្រីកម៉ឺនុយ' : 'Expand Sidebar'"
          class="flex items-center justify-center p-1 rounded-xl hover:bg-[#f0f4f9] dark:hover:bg-[#282a2c] transition-all cursor-pointer focus:outline-none"
        >
          <img
            :src="logoUrl"
            alt="E-LMS Logo"
            class="w-9 h-9 min-w-[36px] min-h-[36px] max-w-[36px] max-h-[36px] aspect-square rounded-full object-cover shrink-0 hover:scale-105 transition-transform"
          />
        </button>

        <Link
          v-else
          href="/student/dashboard"
          class="flex items-center gap-3 min-w-0 shrink-0 transition-all focus:outline-none"
        >
          <img
            :src="logoUrl"
            alt="E-LMS Logo"
            class="w-9 h-9 min-w-[36px] min-h-[36px] max-w-[36px] max-h-[36px] aspect-square rounded-full object-cover shrink-0"
          />
          <div class="transition-opacity duration-200 min-w-0">
            <h1 class="text-sm font-bold bg-clip-text text-transparent bg-gradient-to-r from-[#0b57d0] via-indigo-600 to-purple-600 tracking-tight whitespace-nowrap">
              {{ currentLang === 'km' ? 'E-LMS និស្សិត' : 'E-LMS Student' }}
            </h1>
            <p class="text-[10px] text-[#747775] dark:text-slate-400 font-medium tracking-wide uppercase whitespace-nowrap">{{ currentLang === 'km' ? 'ផ្ទាំងសិស្ស' : 'Student Panel' }}</p>
          </div>
        </Link>

        <!-- Single Collapse Button (When expanded) -->
        <button
          v-if="!isSidebarCollapsed"
          @click="toggleSidebarCollapse"
          type="button"
          :title="currentLang === 'km' ? 'បង្រួមម៉ឺនុយ' : 'Collapse Sidebar'"
          class="p-2 rounded-2xl border border-[#dfe3e7] dark:border-[#28292a] bg-[#f0f4f9] dark:bg-[#1e1f20] text-[#747775] hover:text-[#1f1f1f] dark:hover:text-white shrink-0 cursor-pointer transition-colors focus:outline-none shadow-2xs"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
          </svg>
        </button>
      </div>

      <!-- Navigation Tree (12 Core Modules) -->
      <nav
        :class="[
          isSidebarCollapsed ? 'px-0 overflow-visible' : 'px-3 custom-scrollbar overflow-y-auto',
          'flex flex-1 flex-col py-4 space-y-1'
        ]"
      >
        <ul role="list" class="space-y-1 w-full">
          <li
            v-for="item in sidebarNavItems"
            :key="item.key"
            :class="isSidebarCollapsed ? 'relative group/flyout flex justify-center w-full my-0.5' : 'relative my-0.5'"
          >
            <!-- Action Item (e.g. Log Out) -->
            <button
              v-if="item.isAction"
              @click="item.onClick ? item.onClick() : logout()"
              type="button"
              :class="[
                'text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 hover:bg-red-500/10 border border-transparent font-medium',
                isSidebarCollapsed ? 'justify-center w-11 h-11 p-0 rounded-2xl mx-auto' : 'px-3 w-full justify-between py-2 rounded-xl',
                'group flex items-center text-xs transition-all duration-200 cursor-pointer'
              ]"
            >
              <div :class="[isSidebarCollapsed ? 'justify-center w-full' : '', 'flex items-center gap-x-2.5 truncate']">
                <div class="relative flex items-center justify-center shrink-0">
                  <svg 
                    :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', 'shrink-0 text-red-500 transition-transform duration-200 group-hover:scale-110']"
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
            <template v-else-if="!item.children || item.children.length === 0">
              <Link
                :href="item.href!"
                prefetch="hover"
                :title="currentLang === 'km' ? item.khName : item.name"
                :class="[
                  (item.href === '/student/dashboard' ? ($page.url === '/student/dashboard' || $page.url === '/student') : $page.url.startsWith(item.href!))
                    ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200 shadow-2xs dark:bg-purple-600/20 dark:text-purple-300 dark:border-purple-500/40' 
                    : 'text-[#64748b] hover:text-[#1f1f1f] hover:bg-[#f0f4f9] dark:text-slate-400 dark:hover:text-[#F1F5F9] dark:hover:bg-slate-800/70 border border-transparent font-medium',
                  isSidebarCollapsed ? 'justify-center w-11 h-11 p-0 rounded-2xl mx-auto' : 'px-3 w-full justify-between py-2 rounded-xl',
                  'group flex items-center text-xs transition-all duration-200'
                ]"
              >
                <div :class="[isSidebarCollapsed ? 'justify-center w-full' : '', 'flex items-center gap-x-2.5 truncate']">
                  <div class="relative flex items-center justify-center shrink-0">
                    <!-- Lucide Dashboard (LayoutDashboard) -->
                    <svg v-if="item.key === 'dashboard'" :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', ($page.url === '/student/dashboard' || $page.url === '/student') ? 'text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400 group-hover:text-[#1f1f1f] dark:group-hover:text-slate-200', 'shrink-0 transition-colors']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
                    </svg>
                    <!-- Lucide Calendar (Calendar Grid) -->
                    <svg v-else-if="item.key === 'calendar'" :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', $page.url.startsWith('/student/calendar') ? 'text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400 group-hover:text-[#1f1f1f] dark:group-hover:text-slate-200', 'shrink-0 transition-colors']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
                    </svg>
                    <!-- Lucide Profile & Settings (User) -->
                    <svg v-else-if="item.key === 'profile'" :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', $page.url.startsWith('/student/profile') ? 'text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400 group-hover:text-[#1f1f1f] dark:group-hover:text-slate-200', 'shrink-0 transition-colors']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <!-- Fallback SVG -->
                    <svg v-else :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', 'shrink-0 text-[#64748b] dark:text-slate-400']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="4" />
                    </svg>
                  </div>
                  <span v-show="!isSidebarCollapsed" class="truncate font-medium">
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

              <!-- Flyout Tooltip for Direct Link when collapsed -->
              <div
                v-if="isSidebarCollapsed"
                class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 rounded-xl bg-slate-900/95 dark:bg-slate-800/95 text-white text-xs font-semibold whitespace-nowrap shadow-xl border border-slate-700/60 opacity-0 pointer-events-none group-hover/flyout:opacity-100 transition-all duration-150 z-50 flex items-center gap-2"
              >
                <span>{{ currentLang === 'km' ? item.khName : item.name }}</span>
              </div>
            </template>

            <!-- Collapsible Module with Submenu -->
            <div v-else class="space-y-0.5 w-full flex flex-col items-center">
              <button
                @click="toggleModule(item.key!)"
                type="button"
                :title="currentLang === 'km' ? item.khName : item.name"
                :class="[
                  isChildActive(item.children) 
                    ? 'bg-purple-50 text-purple-700 border border-purple-200 font-semibold dark:bg-purple-600/20 dark:text-purple-300 dark:border-purple-500/30' 
                    : 'text-[#64748b] hover:text-[#1f1f1f] hover:bg-[#f0f4f9] dark:text-slate-400 dark:hover:text-[#F1F5F9] dark:hover:bg-slate-800/60 border border-transparent',
                  isSidebarCollapsed ? 'justify-center w-11 h-11 p-0 rounded-2xl mx-auto' : 'px-3 w-full justify-between py-2 rounded-xl',
                  'group flex items-center text-xs font-medium transition-all duration-200 cursor-pointer'
                ]"
              >
                <div :class="[isSidebarCollapsed ? 'justify-center w-full' : '', 'flex items-center gap-x-2.5 truncate']">
                  <div class="relative flex items-center justify-center shrink-0">
                    <!-- Lucide Courses (BookOpen) -->
                    <svg v-if="item.key === 'courses'" :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', isChildActive(item.children) ? 'text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400 group-hover:text-[#1f1f1f] dark:group-hover:text-slate-200', 'shrink-0 transition-colors']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/>
                    </svg>
                    <!-- Lucide AI Learning (Sparkles) -->
                    <svg v-else-if="item.key === 'aiPath'" :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', isChildActive(item.children) ? 'text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400 group-hover:text-[#1f1f1f] dark:group-hover:text-slate-200', 'shrink-0 transition-colors']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                    </svg>
                    <!-- Lucide Progress (TrendingUp) -->
                    <svg v-else-if="item.key === 'progress'" :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', isChildActive(item.children) ? 'text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400 group-hover:text-[#1f1f1f] dark:group-hover:text-slate-200', 'shrink-0 transition-colors']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                    </svg>
                    <!-- Lucide Quiz & Assessment (CheckSquare) -->
                    <svg v-else-if="item.key === 'quizzes'" :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', isChildActive(item.children) ? 'text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400 group-hover:text-[#1f1f1f] dark:group-hover:text-slate-200', 'shrink-0 transition-colors']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 12 2 2 4-4"/>
                    </svg>
                    <!-- Lucide Certificates (Award) -->
                    <svg v-else-if="item.key === 'certificates'" :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', isChildActive(item.children) ? 'text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400 group-hover:text-[#1f1f1f] dark:group-hover:text-slate-200', 'shrink-0 transition-colors']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                    </svg>
                    <!-- Lucide Notifications (Bell - NEVER Calendar or Megaphone) -->
                    <svg v-else-if="item.key === 'notificationsModule'" :class="[isSidebarCollapsed ? 'h-5 w-5' : 'h-4 w-4', isChildActive(item.children) ? 'text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400 group-hover:text-[#1f1f1f] dark:group-hover:text-slate-200', 'shrink-0 transition-colors']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                    </svg>

                    <!-- Collapsed Notification Indicator Dot -->
                    <span
                      v-if="isSidebarCollapsed && item.key === 'notificationsModule' && unreadNotificationsCount > 0"
                      class="absolute -top-1 -right-1 flex h-2.5 w-2.5 pointer-events-none z-10"
                    >
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500 ring-2 ring-white dark:ring-[#131314]"></span>
                    </span>

                    <!-- Collapsed Courses Indicator Badge -->
                    <span
                      v-if="isSidebarCollapsed && item.key === 'courses' && newEnrollmentsCount > 0"
                      :title="`${newEnrollmentsCount} new course${newEnrollmentsCount > 1 ? 's' : ''}`"
                      class="absolute -top-1.5 -right-1.5 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-purple-600 text-[9px] font-bold text-white shadow-xs pointer-events-none ring-2 ring-white dark:ring-[#131314] animate-in fade-in zoom-in-95 duration-200"
                    >
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-60"></span>
                      <span class="relative">{{ newEnrollmentsCount > 9 ? '9+' : newEnrollmentsCount }}</span>
                    </span>
                  </div>
                  <span v-show="!isSidebarCollapsed" class="truncate font-medium">
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
                      expandedModules[item.key!] ? 'rotate-180 text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400',
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
                class="relative ml-4 pl-3.5 space-y-0.5 my-1 transition-all duration-300 border-l border-[#dfe3e7] dark:border-purple-500/30 w-[calc(100%-18px)]"
              >
                <div
                  v-for="sub in item.children"
                  :key="sub.href"
                  class="relative"
                >
                  <Link
                    :href="sub.href"
                    :prefetch="sub.href.includes('/my-courses/current') ? false : 'hover'"
                    :title="currentLang === 'km' ? sub.khName : sub.name"
                    :class="[
                      isSubActive(sub.href)
                        ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200 dark:bg-purple-600/20 dark:text-purple-300 dark:border-purple-500/30'
                        : 'text-[#64748b] hover:text-[#1f1f1f] hover:bg-[#f0f4f9] dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/50 border border-transparent font-medium',
                      'flex items-center gap-x-2 rounded-lg px-2 py-1.5 text-[11px] transition-all duration-200 truncate group/sub select-none'
                    ]"
                  >
                    <!-- Lucide Line Icons for Submenu (Clean grey line icons, no emojis) -->
                    <svg v-if="sub.name === 'Continue Learning' || sub.href.includes('/my-courses/current')" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>
                    </svg>
                    <svg v-else-if="sub.name === 'Enrolled Courses' || sub.href === '/student/my-courses/enrolled'" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>
                    </svg>
                    <svg v-else-if="sub.name === 'Completed Courses' || sub.href.includes('/my-courses/completed')" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>
                    </svg>
                    <svg v-else-if="sub.name === 'Browse Catalog' || sub.href.includes('/browse')" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>
                    </svg>
                    <img v-else-if="sub.iconUrl" :src="sub.iconUrl" :alt="sub.name" class="w-3.5 h-3.5 object-contain shrink-0" @error="onIconError" />
                    <span v-else :class="[isSubActive(sub.href) ? 'bg-purple-600 dark:bg-purple-400' : 'bg-slate-400 dark:bg-slate-600', 'w-1.5 h-1.5 rounded-full shrink-0']"></span>
                    <span class="truncate">{{ currentLang === 'km' ? sub.khName : sub.name }}</span>
                    <!-- New Enrolled Courses Badge -->
                    <span
                      v-if="(sub.name === 'Enrolled Courses' || sub.href === '/student/my-courses/enrolled') && newEnrollmentsCount > 0"
                      class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-200 dark:border-purple-700/50 shrink-0 transition-transform duration-200 animate-in fade-in zoom-in-95"
                      :aria-label="`${newEnrollmentsCount} new enrolled courses`"
                    >
                      {{ newEnrollmentsCount > 9 ? '9+' : newEnrollmentsCount }} {{ currentLang === 'km' ? 'ថ្មី' : 'New' }}
                    </span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Flyout Popover for Collapsed Sidebar Mode (Has Children) -->
            <div
              v-if="isSidebarCollapsed && item.children && item.children.length > 0"
              class="absolute left-full top-0 ml-3.5 w-64 opacity-0 pointer-events-none group-hover/flyout:opacity-100 group-hover/flyout:pointer-events-auto transition-all duration-200 ease-out translate-x-1 group-hover/flyout:translate-x-0 z-50"
            >
              <div class="relative bg-white/95 dark:bg-[#1e1f20]/95 backdrop-blur-xl border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 shadow-2xl ring-1 ring-slate-800/80">
                <div class="absolute -left-1.5 top-3.5 w-3 h-3 bg-white dark:bg-[#1e1f20] border-l border-b border-[#dfe3e7] dark:border-[#2d2f31] rotate-45 z-10 pointer-events-none"></div>

                <div class="relative z-20 flex items-center justify-between px-2 py-1.5 mb-2 border-b border-[#dfe3e7] dark:border-[#28292a] pb-2">
                  <div class="flex items-center gap-2 min-w-0">
                    <img v-if="item.iconUrl" :src="item.iconUrl" :alt="item.name" class="w-4 h-4 object-contain shrink-0" />
                    <span class="text-xs font-bold text-[#1f1f1f] dark:text-[#e3e3e3] truncate">{{ currentLang === 'km' ? item.khName : item.name }}</span>
                  </div>
                </div>

                <div class="relative z-20 space-y-1 max-h-[70vh] overflow-y-auto custom-scrollbar pr-1">
                  <Link
                    v-for="sub in item.children"
                    :key="sub.href"
                    :href="sub.href"
                    :prefetch="sub.href.includes('/my-courses/current') ? false : 'hover'"
                    :class="[
                      isSubActive(sub.href)
                        ? 'bg-[#d3e3fd] text-[#041e49] font-bold border border-[#0b57d0]/20 dark:bg-purple-600/20 dark:text-purple-300 dark:border-purple-500/30'
                        : 'text-[#444746] hover:text-[#1f1f1f] hover:bg-[#f0f4f9] dark:text-slate-400 dark:hover:text-[#e3e3e3] dark:hover:bg-[#282a2c] border border-transparent',
                      'flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs transition-all duration-150 truncate group/sub'
                    ]"
                  >
                    <!-- Lucide Line Icons for Submenu (Clean grey line icons, no emojis) -->
                    <svg v-if="sub.name === 'Continue Learning' || sub.href.includes('/my-courses/current')" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>
                    </svg>
                    <svg v-else-if="sub.name === 'Enrolled Courses' || sub.href === '/student/my-courses/enrolled'" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>
                    </svg>
                    <svg v-else-if="sub.name === 'Completed Courses' || sub.href.includes('/my-courses/completed')" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>
                    </svg>
                    <svg v-else-if="sub.name === 'Browse Catalog' || sub.href.includes('/browse')" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>
                    </svg>
                    <img v-else-if="sub.iconUrl" :src="sub.iconUrl" :alt="sub.name" loading="lazy" decoding="async" class="w-3.5 h-3.5 object-contain shrink-0" />
                    <span v-else :class="[isSubActive(sub.href) ? 'bg-purple-600 dark:bg-purple-400' : 'bg-slate-400 dark:bg-slate-600', 'w-1.5 h-1.5 rounded-full shrink-0']"></span>
                    <span class="truncate">{{ currentLang === 'km' ? sub.khName : sub.name }}</span>
                <!-- New Enrolled Courses Badge (Mobile) -->
                <span
                  v-if="(sub.name === 'Enrolled Courses' || sub.href === '/student/my-courses/enrolled') && newEnrollmentsCount > 0"
                  class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-200 dark:border-purple-700/50 shrink-0"
                >
                  {{ newEnrollmentsCount > 9 ? '9+' : newEnrollmentsCount }} {{ currentLang === 'km' ? 'ថ្មី' : 'New' }}
                </span>
                    <!-- New Enrolled Courses Badge in Popover -->
                    <span
                      v-if="(sub.name === 'Enrolled Courses' || sub.href === '/student/my-courses/enrolled') && newEnrollmentsCount > 0"
                      class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-200 dark:border-purple-700/50 shrink-0"
                    >
                      {{ newEnrollmentsCount > 9 ? '9+' : newEnrollmentsCount }} {{ currentLang === 'km' ? 'ថ្មី' : 'New' }}
                    </span>
                  </Link>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </nav>

      <!-- Sidebar Footer Log Out (🚪) -->
      <div :class="[isSidebarCollapsed ? 'px-0 py-2.5 flex justify-center' : 'p-3', 'mt-auto border-t border-[#dfe3e7] dark:border-[#28292a] bg-[#f8fafd] dark:bg-[#131314] shrink-0 relative group/logout']">
        <!-- Log Out Button -->
        <button
          @click="logout"
          type="button"
          :class="[
            isSidebarCollapsed ? 'justify-center w-11 h-11 p-0 rounded-2xl mx-auto' : 'px-3 w-full justify-start gap-2.5 py-2 rounded-xl',
            'flex items-center text-xs font-semibold text-rose-500 hover:text-rose-600 hover:bg-rose-500/10 transition-colors cursor-pointer'
          ]"
        >
          <svg class="w-4.5 h-4.5 shrink-0 text-rose-500 transition-transform group-hover/logout:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span v-show="!isSidebarCollapsed">{{ currentLang === 'km' ? 'ចាកចេញ' : 'Log Out' }}</span>
        </button>

        <!-- Tooltip for Logout when collapsed -->
        <div
          v-if="isSidebarCollapsed"
          class="absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 rounded-xl bg-slate-900/95 dark:bg-[#1e1f20] text-rose-300 text-xs font-semibold whitespace-nowrap shadow-xl border border-rose-500/30 opacity-0 pointer-events-none group-hover/logout:opacity-100 transition-all duration-150 z-50 flex items-center gap-1.5"
        >
          <span>{{ currentLang === 'km' ? 'ចាកចេញ' : 'Log Out' }}</span>
        </div>
      </div>
    </aside>

    <!-- Mobile Drawer Sidebar -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-slate-950/80 z-50 lg:hidden backdrop-blur-sm transition-opacity"
    ></div>

    <aside
      :style="{ top: 'var(--notice-h, 0px)', height: 'calc(100vh - var(--notice-h, 0px))' }"
      :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-[#131314] border-r border-slate-200 dark:border-[#28292a] flex flex-col lg:hidden transition-transform duration-300 ease-in-out shadow-2xl'
      ]"
    >
      <div class="h-16 px-4 flex items-center justify-between border-b border-slate-200 dark:border-[#28292a] shrink-0">
        <div class="flex items-center gap-3">
          <img :src="logoUrl" alt="E-LMS Logo" loading="lazy" decoding="async" class="w-8 h-8 rounded-full object-cover" />
          <span class="font-bold text-sm text-slate-900 dark:text-white">E-LMS Student</span>
        </div>
        <button @click="sidebarOpen = false" class="p-1 text-slate-400 hover:text-slate-900 dark:hover:text-white">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
        <template v-for="item in sidebarNavItems" :key="item.key">
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
            :title="currentLang === 'km' ? item.khName : item.name"
            :class="[
              (item.href === '/student/dashboard' ? ($page.url === '/student/dashboard' || $page.url === '/student') : $page.url.startsWith(item.href!))
                ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200 dark:bg-purple-600/20 dark:text-purple-300 dark:border-purple-500/40' 
                : 'text-[#64748b] dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
              'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium'
            ]"
          >
            <!-- Lucide Dashboard (LayoutDashboard) -->
            <svg v-if="item.key === 'dashboard'" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>
            </svg>
            <!-- Lucide Calendar (Calendar Grid) -->
            <svg v-else-if="item.key === 'calendar'" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
            </svg>
            <!-- Lucide Profile & Settings (User) -->
            <svg v-else-if="item.key === 'profile'" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <span class="truncate">{{ currentLang === 'km' ? item.khName : item.name }}</span>
          </Link>
          <div v-else class="space-y-0.5">
            <button
              @click="toggleModule(item.key!)"
              :class="[
                isChildActive(item.children)
                  ? 'bg-purple-50 text-purple-700 font-bold border border-purple-200 dark:bg-purple-600/20 dark:text-purple-300 dark:border-purple-500/40'
                  : 'text-[#64748b] dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium cursor-pointer'
              ]"
            >
              <div class="flex items-center gap-2.5 truncate">
                <!-- Lucide Courses (BookOpen) -->
                <svg v-if="item.key === 'courses'" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6 6h10"/><path d="M6 10h10"/>
                </svg>
                <!-- Lucide AI Learning (Sparkles) -->
                <svg v-else-if="item.key === 'aiPath'" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                </svg>
                <!-- Lucide Progress (TrendingUp) -->
                <svg v-else-if="item.key === 'progress'" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                </svg>
                <!-- Lucide Quiz & Assessment (CheckSquare) -->
                <svg v-else-if="item.key === 'quizzes'" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 12 2 2 4-4"/>
                </svg>
                <!-- Lucide Certificates (Award) -->
                <svg v-else-if="item.key === 'certificates'" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                </svg>
                <!-- Lucide Notifications (Bell - NEVER Calendar or Megaphone) -->
                <svg v-else-if="item.key === 'notificationsModule'" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                </svg>
                <span class="truncate">{{ currentLang === 'km' ? item.khName : item.name }}</span>
              </div>
              <div class="flex items-center gap-1">
                <span
                  v-if="item.badge"
                  :class="['px-1.5 py-0.5 rounded text-[9px] font-bold border', item.badge.colorClass]"
                >
                  {{ item.badge.text }}
                </span>
                <svg :class="[expandedModules[item.key!] ? 'rotate-180 text-purple-600 dark:text-purple-400' : 'text-[#64748b] dark:text-slate-400', 'w-3.5 h-3.5 transition-transform']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
              </div>
            </button>
            <div v-show="expandedModules[item.key!]" class="pl-6 space-y-0.5 border-l border-purple-200 dark:border-purple-500/30 ml-4">
              <Link
                v-for="sub in item.children"
                :key="sub.href"
                :href="sub.href"
                prefetch="hover"
                @click="sidebarOpen = false"
                :class="[isSubActive(sub.href) ? 'text-purple-600 dark:text-purple-300 font-bold' : 'text-[#64748b] dark:text-slate-400 hover:text-slate-900 dark:hover:text-white', 'flex items-center gap-2 py-1.5 text-[11px] truncate group/sub']"
              >
                <!-- Lucide Submenu Line Icons (Grey, clean, professional) -->
                <svg v-if="sub.name === 'Continue Learning'" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>
                </svg>
                <svg v-else-if="sub.name === 'Enrolled Courses'" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>
                </svg>
                <svg v-else-if="sub.name === 'Completed Courses'" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>
                </svg>
                <svg v-else-if="sub.name === 'Browse Catalog'" class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 group-hover/sub:text-purple-600 dark:group-hover/sub:text-purple-400 shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>
                </svg>
                <img v-else-if="sub.iconUrl" :src="sub.iconUrl" :alt="sub.name" class="w-3.5 h-3.5 object-contain shrink-0" @error="onIconError" />
                <span v-else :class="[isSubActive(sub.href) ? 'bg-purple-600 dark:bg-purple-400' : 'bg-slate-400 dark:bg-slate-600', 'w-1.5 h-1.5 rounded-full shrink-0']"></span>
                <span class="truncate">{{ currentLang === 'km' ? sub.khName : sub.name }}</span>
                <!-- New Enrolled Courses Badge (Mobile) -->
                <span
                  v-if="(sub.name === 'Enrolled Courses' || sub.href === '/student/my-courses/enrolled') && newEnrollmentsCount > 0"
                  class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 border border-purple-200 dark:border-purple-700/50 shrink-0"
                >
                  {{ newEnrollmentsCount > 9 ? '9+' : newEnrollmentsCount }} {{ currentLang === 'km' ? 'ថ្មី' : 'New' }}
                </span>
              </Link>
            </div>
          </div>
        </template>
      </nav>
    </aside>

    <!-- STICKY TOP NAVBAR (Dynamically padded to account for fixed sidebar) -->
    <header
      style="view-transition-name: student-topbar; top: var(--notice-h, 0px);"
      :class="[isSidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-72', 'sticky top-0 z-40 bg-white/95 dark:bg-[#131314]/95 backdrop-blur-xl border-b border-[#dfe3e7] dark:border-[#28292a] transition-all duration-250 ease-in-out shadow-2xs']"
    >
      <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8 xl:px-10 max-w-[1600px] mx-auto gap-4">
        
        <!-- Left Side: Mobile Sidebar Hamburger, Desktop Toggle & Multi-Segment Breadcrumbs -->
        <div class="flex items-center gap-3 min-w-0">
          <button
            @click="sidebarOpen = !sidebarOpen"
            type="button"
            class="p-1.5 text-[#747775] dark:text-slate-400 hover:text-[#1f1f1f] dark:hover:text-white hover:bg-[#f0f4f9] dark:hover:bg-slate-800 rounded-lg focus:outline-none transition-colors cursor-pointer lg:hidden"
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
                class="text-[#747775] hover:text-[#0b57d0] dark:hover:text-purple-400 transition-colors truncate"
              >
                {{ getBreadcrumbLabel(crumb.label) }}
              </Link>
              <span
                v-else
                class="text-[#1f1f1f] dark:text-white font-semibold truncate"
              >
                {{ getBreadcrumbLabel(crumb.label) }}
              </span>
              <svg
                v-if="idx < dynamicBreadcrumbs.length - 1"
                class="w-3 h-3 text-[#747775] dark:text-slate-500 shrink-0"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
              </svg>
            </template>
          </nav>

          <!-- Google Search Bar (Soft pill) -->
          <div class="relative hidden md:block nav-dropdown-scope">
            <button
              @click.stop="toggleDropdown('search')"
              type="button"
              class="flex items-center gap-2.5 px-4 py-2 rounded-full bg-[#f0f4f9] hover:bg-[#e8edf4] dark:bg-[#1e1f20] dark:hover:bg-[#282a2c] text-[#444746] hover:text-[#1f1f1f] dark:text-[#c4c7c5] dark:hover:text-[#e3e3e3] border border-[#dfe3e7] hover:border-[#0b57d0]/40 dark:border-[#2d2f31] dark:hover:border-[#1a73e8] text-xs transition-all w-56 lg:w-72 justify-between shadow-2xs group cursor-pointer"
            >
              <div class="flex items-center gap-2 truncate min-w-0 flex-1">
                <svg class="w-4 h-4 text-[#0b57d0] dark:text-[#8ab4f8] shrink-0 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="truncate text-[#444746] dark:text-[#c4c7c5] group-hover:text-[#1f1f1f] dark:group-hover:text-white">
                  {{ currentLang === 'km' ? 'ស្វែងរកម៉ូឌុលទាំង ១២...' : 'Search 12 Modules...' }}
                </span>
              </div>
              <kbd class="hidden lg:inline-flex items-center shrink-0 whitespace-nowrap px-2 py-0.5 text-[10px] font-mono font-semibold text-[#747775] dark:text-[#8e918f] bg-white dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-full shadow-2xs leading-none">ctrl k</kbd>
            </button>
          </div>
        </div>

        <!-- Right Side: Quick Action Button, Theme Toggle, Online Status, Language, Fullscreen, Notifications, User Profile -->
        <div class="flex items-center gap-1.5 sm:gap-2">
          <!-- Mobile Search Trigger -->
          <button
            @click.stop="toggleDropdown('search')"
            type="button"
            class="p-1.5 text-[#747775] dark:text-slate-400 hover:text-[#1f1f1f] dark:hover:text-white hover:bg-[#f0f4f9] dark:hover:bg-[#1e1f20] rounded-lg md:hidden focus:outline-none transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </button>

          <!-- Language Switcher Pill (Direct 1-Click Toggle: Khmer / English) -->
          <button
            type="button"
            @click="toggleLanguage"
            class="p-1.5 h-8 w-8 rounded-full bg-white hover:bg-[#f0f4f9] dark:bg-[#1e1f20] dark:hover:bg-[#282a2c] transition-all duration-150 border border-[#dfe3e7] dark:border-[#2d2f31] shadow-2xs flex items-center justify-center cursor-pointer select-none active:scale-95 group focus:outline-none"
            :title="currentLang === 'km' ? 'ប្តូរទៅជាភាសាអង់គ្លេស (Switch to English)' : 'Switch to Khmer (ប្តូរទៅជាភាសាខ្មែរ)'"
          >
            <img
              :src="currentLang === 'km' ? '/images/flags/km.svg' : '/images/flags/en.svg'"
              :alt="currentLang"
              class="w-5 h-3.5 object-cover rounded-[3px] shadow-xs ring-1 ring-[#dfe3e7] dark:ring-zinc-700/60 transition-transform duration-200 group-hover:scale-110"
            />
          </button>

          <!-- Theme Switcher Pill (Icon Only) -->
          <button
            type="button"
            @click="handleThemeToggle($event)"
            class="w-8 h-8 rounded-full bg-white hover:bg-[#f0f4f9] dark:bg-[#1e1f20] dark:hover:bg-[#282a2c] transition-all duration-200 border border-[#dfe3e7] dark:border-[#2d2f31] shadow-2xs flex items-center justify-center cursor-pointer select-none active:scale-95 group focus:outline-none"
            :title="isDark ? (currentLang === 'km' ? 'ប្ដូរទៅ Light Mode' : 'Switch to Light Mode') : (currentLang === 'km' ? 'ប្ដូរទៅ Dark Mode' : 'Switch to Dark Mode')"
            aria-label="Toggle theme"
          >
            <i :class="['pi text-sm transition-transform duration-500 group-hover:rotate-45', isDark ? 'pi-moon text-indigo-400' : 'pi-sun text-amber-500']"></i>
          </button>

          <!-- Notifications Bell & Drawer Trigger -->
          <div class="relative nav-dropdown-scope">
            <button
              @click.stop="handleNotificationToggle"
              type="button"
              class="relative p-1.5 w-8 h-8 rounded-full bg-white hover:bg-[#f0f4f9] dark:bg-[#1e1f20] dark:hover:bg-[#282a2c] text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-white transition-all duration-150 border border-[#dfe3e7] dark:border-[#2d2f31] shadow-2xs flex items-center justify-center cursor-pointer select-none active:scale-95 group focus:outline-none"
              :title="currentLang === 'km' ? 'ការជូនដំណឹង' : 'Notifications'"
            >
              <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-12 group-hover:scale-105" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <!-- Notification Count Badge with Number -->
              <span
                v-if="unreadNotificationsCount > 0"
                class="absolute -top-1.5 -right-1.5 flex items-center justify-center pointer-events-none z-10"
              >
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-50"></span>
                <span class="relative min-w-[17px] h-[17px] px-1 flex items-center justify-center rounded-full bg-[#f43f5e] text-white text-[9.5px] font-black leading-none ring-2 ring-white dark:ring-[#1e1f20] shadow-sm select-none">
                  {{ unreadNotificationsCount > 99 ? '99+' : unreadNotificationsCount }}
                </span>
              </span>
            </button>

            <!-- Notifications Drawer -->
            <transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <div
                v-if="isNotificationOpen"
                @click.stop
                class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] shadow-2xl z-50 overflow-hidden"
              >
                <div class="px-4 py-3 border-b border-[#dfe3e7] dark:border-[#28292a] flex items-center justify-between bg-[#f8fafd] dark:bg-[#1e1f20] backdrop-blur-md">
                  <div class="flex items-center gap-2">
                    <h3 class="text-xs font-bold text-[#1f1f1f] dark:text-[#e3e3e3]">{{ currentLang === 'km' ? 'ការជូនដំណឹង' : 'Notifications' }}</h3>
                    <span v-if="unreadNotificationsCount > 0" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/20">
                      {{ unreadNotificationsCount }} {{ currentLang === 'km' ? 'ថ្មី' : 'New' }}
                    </span>
                  </div>
                  <button
                    v-if="unreadNotificationsCount > 0"
                    @click="markAllAsRead"
                    class="text-[11px] text-[#0b57d0] dark:text-[#8ab4f8] hover:underline font-medium cursor-pointer transition-colors"
                  >
                    {{ currentLang === 'km' ? 'អានទាំងអស់' : 'Mark all read' }}
                  </button>
                </div>

                <!-- Tabs: All vs Unread -->
                <div class="flex items-center gap-1 px-3 py-2 bg-[#f8fafd] dark:bg-[#131314] border-b border-[#dfe3e7] dark:border-[#28292a] text-xs">
                  <button
                    type="button"
                    @click="notifTab = 'all'; playClickSound()"
                    :class="[notifTab === 'all' ? 'bg-[#0b57d0] dark:bg-[#a8c7fa] text-white dark:text-[#041e49] shadow-xs' : 'text-[#444746] dark:text-[#c4c7c5] hover:bg-[#f0f4f9] dark:hover:bg-[#282a2c]', 'px-2.5 py-1 rounded-full font-medium transition-all cursor-pointer']"
                  >
                    {{ currentLang === 'km' ? 'ទាំងអស់' : 'All' }} ({{ notifications.length }})
                  </button>
                  <button
                    type="button"
                    @click="notifTab = 'unread'; playClickSound()"
                    :class="[notifTab === 'unread' ? 'bg-[#0b57d0] dark:bg-[#a8c7fa] text-white dark:text-[#041e49] shadow-xs' : 'text-[#444746] dark:text-[#c4c7c5] hover:bg-[#f0f4f9] dark:hover:bg-[#282a2c]', 'px-2.5 py-1 rounded-full font-medium transition-all cursor-pointer']"
                  >
                    {{ currentLang === 'km' ? 'មិនទាន់អាន' : 'Unread' }} ({{ unreadNotificationsCount }})
                  </button>
                </div>

                <div class="max-h-80 overflow-y-auto custom-scrollbar divide-y divide-[#dfe3e7] dark:divide-[#28292a]">
                  <div
                    v-for="item in filteredNotifications"
                    :key="item.id"
                    @click="handleNotificationItemClick(item)"
                    :class="[
                      item.read ? 'bg-[#f8fafd]/60 dark:bg-[#1e1f20]/60 opacity-75' : 'bg-white dark:bg-[#1e1f20] hover:bg-[#f0f4f9] dark:hover:bg-[#282a2c]',
                      'p-3.5 transition-colors cursor-pointer block select-none'
                    ]"
                  >
                    <div class="flex items-start gap-3">
                      <!-- Type-specific Icon -->
                      <div v-if="item.type === 'ai'" class="p-2 rounded-xl shrink-0 mt-0.5 bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-300">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <rect x="3" y="11" width="18" height="10" rx="2" />
                          <circle cx="12" cy="5" r="2" />
                          <path d="M12 7v4" />
                          <line x1="8" y1="16" x2="8" y2="16.01" />
                          <line x1="16" y1="16" x2="16" y2="16.01" />
                        </svg>
                      </div>
                      <div v-else-if="item.type === 'lesson'" class="p-2 rounded-xl shrink-0 mt-0.5 bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                      </div>
                      <div v-else-if="item.type === 'quiz'" class="p-2 rounded-xl shrink-0 mt-0.5 bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div v-else class="p-2 rounded-xl shrink-0 mt-0.5 bg-[#e8f0fe] text-[#0b57d0] dark:bg-[#1a73e8]/20 dark:text-[#8ab4f8]">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                          <p :class="[item.read ? 'text-[#444746] dark:text-[#c4c7c5] font-medium' : 'text-[#1f1f1f] dark:text-[#e3e3e3] font-bold', 'text-xs truncate']">{{ getNotifTitle(item) }}</p>
                          <span class="text-[10px] text-[#747775] dark:text-[#8e918f] shrink-0 ml-1">{{ getNotifTime(item) }}</span>
                        </div>
                        <p class="text-[11px] text-[#444746] dark:text-[#8e918f] line-clamp-2 mt-0.5">{{ getNotifDesc(item) }}</p>
                      </div>
                      <span v-if="!item.read" class="w-2 h-2 rounded-full bg-[#0b57d0] dark:bg-[#8ab4f8] shrink-0 mt-1.5"></span>
                    </div>
                  </div>

                  <div v-if="filteredNotifications.length === 0" class="py-8 text-center">
                    <svg class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <p class="text-xs text-[#747775] dark:text-[#8e918f]">{{ currentLang === 'km' ? 'មិនមានការជូនដំណឹងទេ' : 'No notifications' }}</p>
                  </div>
                </div>

                <div class="p-2 border-t border-[#dfe3e7] dark:border-[#28292a] bg-[#f8fafd] dark:bg-[#131314] text-center">
                  <Link
                    href="/student/notifications/announcements"
                    @click="isNotificationOpen = false; playClickSound()"
                    class="text-xs font-semibold text-[#0b57d0] dark:text-[#8ab4f8] hover:underline transition-colors"
                  >
                    {{ currentLang === 'km' ? 'មើលការជូនដំណឹងទាំងអស់ →' : 'View All Notifications →' }}
                  </Link>
                </div>
              </div>
            </transition>
          </div>

          <!-- User Profile Dropdown Menu -->
          <div class="relative nav-dropdown-scope">
            <button
              @click.stop="toggleDropdown('profile')"
              type="button"
              :aria-expanded="isProfileOpen"
              class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              <div class="relative shrink-0">
                <img
                  v-if="userAvatarUrl && !headerAvatarError"
                  :src="userAvatarUrl"
                  @error="headerAvatarError = true"
                  class="w-8 h-8 rounded-full object-cover ring-2 ring-purple-500/30 shadow-xs"
                  alt="Avatar"
                />
                <div
                  v-else
                  class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-white text-xs shadow-xs ring-2 ring-purple-500/30 select-none"
                  :style="{ backgroundColor: getStableAvatarBg(studentDisplayName, user.id) }"
                >
                  {{ headerUserInitial }}
                </div>
              </div>
              <span class="hidden lg:inline text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[200px]">
                {{ studentDisplayName }}
              </span>
              <svg :class="['w-3.5 h-3.5 text-slate-400 transition-transform duration-200', isProfileOpen ? 'rotate-180 text-indigo-500' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <!-- Profile Account Dropdown with Multi-Account Switcher -->
            <ProfileAccountDropdown
              :user="user"
              :is-open="isProfileOpen"
              role="student"
              @close="isProfileOpen = false"
              @logout="logout"
              @trigger-avatar-upload="triggerAvatarUpload(); isProfileOpen = false"
            >
              <template #links>
                <Link
                  href="/student/profile?tab=personal"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors"
                >
                  <svg class="w-4 h-4 text-purple-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                  </svg>
                  <span>{{ currentLang === 'km' ? 'ការកំណត់គណនី' : 'Profile Settings' }}</span>
                </Link>
                <Link
                  href="/student/my-courses/enrolled"
                  @click="isProfileOpen = false"
                  class="flex items-center justify-between px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors"
                >
                  <span class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>{{ currentLang === 'km' ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My Courses' }}</span>
                  </span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                    {{ enrolledCoursesCount }}
                  </span>
                </Link>
                <Link
                  href="/student/certificates/my-certificates"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors"
                >
                  <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a3 3 0 002.828-2.002l1.636-4.908A1.125 1.125 0 0017.896 6H6.104a1.125 1.125 0 00-1.068 1.465l1.636 4.908a3 3 0 002.828 2.002z" />
                  </svg>
                  <span>{{ currentLang === 'km' ? 'វិញ្ញាបនបត្ររបស់ខ្ញុំ' : 'My Certificates' }}</span>
                </Link>
              </template>
            </ProfileAccountDropdown>

            <!-- Hidden File Input for Avatar Upload -->
            <input
              ref="avatarInput"
              type="file"
              accept="image/*"
              class="hidden"
              @change="handleAvatarChange"
            />
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
      <div
        @click.stop
        class="nav-dropdown-scope w-full max-w-xl rounded-3xl bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
      >
        <div class="p-4 border-b border-[#dfe3e7] dark:border-[#28292a] flex items-center gap-3">
          <svg class="w-5 h-5 text-[#0b57d0] dark:text-[#8ab4f8] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            @keydown.enter.prevent="handleSearchEnter"
            :placeholder="currentLang === 'km' ? 'ស្វែងរកម៉ូឌុលទាំង ១២, កម្រងសំណួរ, វគ្គសិក្សា, ការអនុវត្ត...' : 'Search all 12 modules, quizzes, courses, labs, certificates...'"
            class="w-full bg-transparent text-sm text-[#1f1f1f] dark:text-[#e3e3e3] placeholder-[#747775] dark:placeholder-[#8e918f] focus:outline-none"
          />
          <button
            v-if="searchQuery"
            type="button"
            @click="searchQuery = ''; searchInputRef?.focus()"
            class="text-xs text-[#747775] hover:text-[#1f1f1f] dark:hover:text-white px-1.5 py-0.5 rounded cursor-pointer"
          >
            ✕
          </button>
          <button type="button" @click="isSearchOpen = false" class="text-xs text-[#747775] hover:text-[#1f1f1f] dark:hover:text-white font-bold cursor-pointer">
            ESC
          </button>
        </div>

        <div class="max-h-80 overflow-y-auto custom-scrollbar p-2 space-y-1">
          <Link
            v-for="link in filteredSearchLinks"
            :key="link.name"
            :href="link.href"
            @click="isSearchOpen = false"
            class="flex items-center justify-between px-3 py-2.5 rounded-xl hover:bg-[#f0f4f9] dark:hover:bg-[#282a2c] text-[#444746] dark:text-[#c4c7c5] hover:text-[#0b57d0] dark:hover:text-[#a8c7fa] transition-colors"
          >
            <div class="flex items-center gap-2.5">
              <img :src="link.iconUrl" class="w-4 h-4 object-contain" />
              <span class="text-xs font-medium">{{ link.name }}</span>
            </div>
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-[#f0f4f9] dark:bg-[#131314] text-[#747775] dark:text-[#8e918f] border border-[#dfe3e7] dark:border-[#2d2f31]">
              {{ link.category }}
            </span>
          </Link>

          <div v-if="filteredSearchLinks.length === 0" class="py-8 text-center text-xs text-slate-400 dark:text-[#8e918f]">
            {{ currentLang === 'km' ? `មិនមានម៉ូឌុលត្រូវគ្នានឹង "${searchQuery}" ទេ` : `No matching student modules found for "${searchQuery}"` }}
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT WRAPPER -->
    <main :class="[isSidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-72', 'transition-all duration-250 ease-in-out min-h-[calc(100vh-64px)] pb-28 sm:pb-32']">
      <div class="px-4 sm:px-6 lg:px-8 xl:px-10 py-6 max-w-[1600px] mx-auto space-y-6">
        <slot />
      </div>
    </main>

    <!-- 24/7 AI Tutor Assistant Floating Button & Drawer -->
    <AiTutorFloatingWidget />

    <!-- Logout Confirmation Modal (Centered, Glassmorphic, Modern) -->
    <LogoutConfirmModal
      :show="isLogoutModalOpen"
      :user="user"
      :loading="isLoggingOut"
      @close="isLogoutModalOpen = false"
      @confirm="confirmLogout"
    />
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
