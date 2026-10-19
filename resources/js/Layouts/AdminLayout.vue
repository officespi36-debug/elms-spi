<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { i18n } from '@/Services/i18n'
import { useTheme, initTheme, playNotificationSound, playClickSound } from '@/composables/useTheme'
import { useLoading } from '@/composables/useLoading'
import GlobalToast from '@/Components/GlobalToast.vue'
import GlobalLoadingOverlay from '@/Components/GlobalLoadingOverlay.vue'
import OfficialVerifiedBadge from '@/Components/OfficialVerifiedBadge.vue'
import LogoutConfirmModal from '@/Components/LogoutConfirmModal.vue'
import ProfileAccountDropdown from '@/Components/ProfileAccountDropdown.vue'
import AdminProfileModal from '@/Components/AdminProfileModal.vue'

const { isDark, toggleTheme } = useTheme()
const { showLoading, hideLoading } = useLoading()

const logoUrl = '/images/logo.png'
const actionBtnIcon = '/images/actions/action-button.svg'

const props = defineProps<{ title?: string }>()

const page = usePage<any>()
const user = computed(() => page.props.auth?.user || {})

const sidebarOpen = ref(false)
const expandedModules = ref<Record<string, boolean>>({
  auth: false,
  users: false,
  academics: false,
  courses: false,
  assessment: false,
  progress: false,
  analytics: false,
  ai: false,
  communication: false,
  settings: false,
})

const toggleModule = (key: string) => {
  expandedModules.value[key] = !expandedModules.value[key]
}

interface NavSubItem {
  name: string
  href: string
  icon?: string
  iconUrl?: string
}

interface NavItem {
  key?: string
  name: string
  href?: string
  iconType?: string
  icon?: string
  iconUrl?: string
  badge?: string
  hasArrow?: boolean
  isLogout?: boolean
  children?: NavSubItem[]
}

const navigation: NavItem[] = [
  {
    key: 'dashboard',
    name: 'Dashboard',
    href: '/admin/dashboard',
    iconType: 'dashboard',
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
  },
  {
    key: 'auth',
    name: 'Authentication',
    iconType: 'auth',
    icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    children: [
      { name: 'Login & Security', href: '/admin/auth-logs' },
      { name: 'Roles & Permissions', href: '/admin/auth/roles' },
    ]
  },
  {
    key: 'users',
    name: 'User Management',
    iconType: 'users',
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    children: [
      { name: 'Students', href: '/admin/user-management/students' },
      { name: 'Teachers', href: '/admin/user-management/teachers' },
      { name: 'Admins', href: '/admin/user-management/administrators' },
    ]
  },
  {
    key: 'academics',
    name: 'Academic Structure',
    iconType: 'academics',
    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 10V11m0 0h4m-4 0H7',
    children: [
      { name: 'Departments', href: '/admin/academic-structure/departments' },
      { name: 'Majors', href: '/admin/academic-structure/majors' },
      { name: 'Subjects', href: '/admin/academic-structure/subjects' },
      { name: 'Academic Years', href: '/admin/academic-structure/academic-years' },
    ]
  },
  {
    key: 'courses',
    name: 'Course Management',
    iconType: 'courses',
    icon: 'M12 14l9-5-9-5-9 5 9 5z',
    children: [
      { name: 'Courses', href: '/admin/course-module/all' },
      { name: 'Course Approval', href: '/admin/course-module/approval' },
      { name: 'Enrollment', href: '/admin/enrollment/courses' },
    ]
  },
  {
    key: 'assessment',
    name: 'Assessment',
    iconType: 'assessment',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    children: [
      { name: 'Quizzes', href: '/admin/quizzes' },
      { name: 'Assignments', href: '/admin/quizzes?tab=assignments' },
      { name: 'Question Bank', href: '/admin/quizzes?tab=bank' },
    ]
  },
  {
    key: 'progress',
    name: 'Learning & Progress',
    iconType: 'progress',
    icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    children: [
      { name: 'Student Progress', href: '/admin/progress?tab=student' },
      { name: 'Course Completion', href: '/admin/progress?tab=course' },
    ]
  },
  {
    key: 'analytics',
    name: 'Analytics & Reports',
    iconType: 'analytics',
    icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    children: [
      { name: 'Student Analytics', href: '/admin/reports?tab=students' },
      { name: 'Course Analytics', href: '/admin/reports?tab=courses' },
      { name: 'Teacher Analytics', href: '/admin/reports?tab=teachers' },
      { name: 'System Reports', href: '/admin/reports?tab=overview' },
    ]
  },
  {
    key: 'ai',
    name: 'AI Management',
    iconType: 'ai',
    icon: 'M13 10V3L4 14h7v7l9-11h-7z',
    children: [
      { name: 'AI Recommendations', href: '/admin/ai-rules?tab=rules' },
      { name: 'At-Risk Students', href: '/admin/ai-rules?tab=at_risk' },
      { name: 'Difficult Topics', href: '/admin/ai-rules?tab=difficult_topics' },
      { name: 'AI Configuration', href: '/admin/ai-rules?tab=config' },
    ]
  },
  {
    key: 'communication',
    name: 'Communication',
    iconType: 'communication',
    icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    children: [
      { name: 'Announcements', href: '/admin/notifications/announcements' },
      { name: 'Notifications', href: '/admin/notifications' },
    ]
  },
  {
    key: 'settings',
    name: 'System Settings',
    iconType: 'settings',
    icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    children: [
      { name: 'General Settings', href: '/admin/settings?tab=general' },
      { name: 'Academic Settings', href: '/admin/settings?tab=academic' },
      { name: 'System Logs', href: '/admin/settings?tab=logs' },
    ]
  },
  {
    key: 'logout',
    name: 'Logout',
    iconType: 'logout',
    isLogout: true,
  }
]

const isSubActive = (subHref: string) => {
  const currentUrl = page.url

  // If subHref has query param (e.g. ?status=draft or ?tab=assignments)
  if (subHref.includes('?')) {
    const [path, query] = subHref.split('?')
    if (!currentUrl.startsWith(path)) return false
    return currentUrl.includes(query)
  }

  // Exact base route match (prevent /admin/quizzes matching /admin/quizzes?tab=assignments)
  if (currentUrl.includes('?') && !subHref.includes('?')) {
    const currentPath = currentUrl.split('?')[0]
    return currentPath === subHref
  }

  return currentUrl === subHref || (currentUrl.startsWith(subHref + '/') && !subHref.includes('?'))
}

const isChildActive = (children?: NavSubItem[]) => {
  if (!children) return false
  return children.some(child => isSubActive(child.href))
}

const avatarInput = ref<HTMLInputElement | null>(null)
const isUploadingAvatar = ref(false)
const isSidebarCollapsed = ref(false)

const toggleSidebarCollapse = () => {
  const isDesktop = typeof window !== 'undefined' && window.matchMedia('(min-width: 768px)').matches
  if (isDesktop) {
    isSidebarCollapsed.value = !isSidebarCollapsed.value
    try {
      localStorage.setItem('elms_sidebar_collapsed', isSidebarCollapsed.value ? 'true' : 'false')
    } catch {}
  } else {
    sidebarOpen.value = !sidebarOpen.value
  }
}

const isAdminProfileModalOpen = ref(false)

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

// --- Top Navbar State & Logic ---
const searchQuery = ref('')
const isSearchOpen = ref(false)
const isTopSearchDropdownOpen = ref(false)
const isNotificationOpen = ref(false)
const isProfileOpen = ref(false)
const isSettingsOpen = ref(false)
const isQuickActionOpen = ref(false)
const isLangOpen = ref(false)
const isStatusOpen = ref(false)
const isFullscreen = ref(false)
const isDateOpen = ref(false)

// Dynamic current real-time date
const getNowDate = () => new Date()
const monthsEnList = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
const monthsKmList = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ']

const currentDayNum = getNowDate().getDate()
const currentMonthEn = monthsEnList[getNowDate().getMonth()]
const currentMonthKm = monthsKmList[getNowDate().getMonth()]
const currentYearNum = getNowDate().getFullYear()

const todayFormattedEn = `${String(currentDayNum).padStart(2, '0')} ${currentMonthEn} ${currentYearNum}`
const todayIso = `${currentYearNum}-${String(getNowDate().getMonth() + 1).padStart(2, '0')}-${String(currentDayNum).padStart(2, '0')}`

const activeDatePreset = ref(
  (typeof window !== 'undefined' && localStorage.getItem('elms_admin_date_preset')) || 'today'
)
const selectedDateLabel = ref(
  (typeof window !== 'undefined' && localStorage.getItem('elms_admin_date_label')) || todayFormattedEn
)
const customDateInput = ref(
  (typeof window !== 'undefined' && localStorage.getItem('elms_admin_custom_date')) || todayIso
)

const datePresets = computed(() => {
  const d = getNowDate()
  const day = d.getDate()
  const month = monthsEnList[d.getMonth()]
  const monthKm = monthsKmList[d.getMonth()]
  const yr = d.getFullYear()

  const yDate = new Date()
  yDate.setDate(d.getDate() - 1)
  const yDay = yDate.getDate()
  const yMonth = monthsEnList[yDate.getMonth()]

  return [
    { id: 'today', label_km: `ថ្ងៃនេះ (${day} ${monthKm})`, label_en: `Today (${day} ${month})`, value: `${String(day).padStart(2, '0')} ${month} ${yr}` },
    { id: 'yesterday', label_km: `ម្សិលមិញ (${yDay} ${yMonth})`, label_en: `Yesterday (${yDay} ${yMonth})`, value: `${String(yDay).padStart(2, '0')} ${yMonth} ${yDate.getFullYear()}` },
    { id: 'week', label_km: 'សប្តាហ៍នេះ', label_en: 'This Week', value: 'This Week' },
    { id: 'month', label_km: `ខែនេះ (${monthKm})`, label_en: `This Month (${month})`, value: `${month} ${yr}` },
    { id: 'term', label_km: 'ឆមាសទី ១ (២០២៦-២០២៧)', label_en: 'Semester 1 (2026-2027)', value: 'Sem 1, 2026' },
    { id: 'year', label_km: 'ឆ្នាំសិក្សា ២០២៦-២០២៧', label_en: 'AY 2026-2027', value: 'AY 2026-2027' },
  ]
})

const selectDatePreset = (preset: { id: string; label_km: string; label_en: string; value: string }) => {
  playTopBarSound()
  activeDatePreset.value = preset.id
  selectedDateLabel.value = preset.value
  isDateOpen.value = false
  try {
    localStorage.setItem('elms_admin_date_preset', preset.id)
    localStorage.setItem('elms_admin_date_label', preset.value)
  } catch {}
  if (page.url.startsWith('/admin/dashboard')) {
    router.get('/admin/dashboard', { period: preset.id }, { preserveState: true, preserveScroll: true })
  }
}

const applyCustomDate = () => {
  if (!customDateInput.value) return
  playTopBarSound()
  const d = new Date(customDateInput.value)
  const formatted = `${String(d.getDate()).padStart(2, '0')} ${monthsEnList[d.getMonth()]} ${d.getFullYear()}`
  selectedDateLabel.value = formatted
  activeDatePreset.value = 'custom'
  isDateOpen.value = false
  try {
    localStorage.setItem('elms_admin_date_preset', 'custom')
    localStorage.setItem('elms_admin_date_label', formatted)
    localStorage.setItem('elms_admin_custom_date', customDateInput.value)
  } catch {}
  if (page.url.startsWith('/admin/dashboard')) {
    router.get('/admin/dashboard', { period: 'custom', date: customDateInput.value }, { preserveState: true, preserveScroll: true })
  }
}

const currentLang = computed(() => i18n.locale.value)

const dateDisplayLabel = computed(() => {
  if (activeDatePreset.value === 'today') {
    return currentLang.value === 'km' ? `ថ្ងៃនេះ (${currentDayNum} ${currentMonthKm})` : `${currentDayNum} ${currentMonthEn} ${currentYearNum}`
  }
  const preset = datePresets.value.find(p => p.id === activeDatePreset.value)
  if (preset) {
    return currentLang.value === 'km' ? preset.label_km : preset.label_en
  }
  return selectedDateLabel.value
})

const navTranslations: Record<string, { km: string; en: string }> = {
  // Main Navigation Modules (11 Thesis-Aligned Modules)
  'Dashboard': { km: 'ផ្ទាំងគ្រប់គ្រង', en: 'Dashboard' },
  'Authentication': { km: 'ផ្ទៀងផ្ទាត់សុវត្ថិភាព', en: 'Authentication' },
  'Authentication Module': { km: 'ផ្ទៀងផ្ទាត់សុវត្ថិភាព', en: 'Authentication' },
  'User Management': { km: 'ការគ្រប់គ្រងអ្នកប្រើប្រាស់', en: 'User Management' },
  'Academic Structure': { km: 'រចនាសម្ព័ន្ធអប់រំ', en: 'Academic Structure' },
  'Course Management': { km: 'ការគ្រប់គ្រងវគ្គសិក្សា', en: 'Course Management' },
  'Assessment': { km: 'ការវាយតម្លៃ', en: 'Assessment' },
  'Learning & Progress': { km: 'ការរៀនសូត្រ & វឌ្ឍនភាព', en: 'Learning & Progress' },
  'Analytics & Reports': { km: 'ស្ថិតិវិភាគ & របាយការណ៍', en: 'Analytics & Reports' },
  'AI Management': { km: 'ការគ្រប់គ្រង AI', en: 'AI Management' },
  'Communication': { km: 'ការទំនាក់ទំនង', en: 'Communication' },
  'System Settings': { km: 'ការកំណត់ប្រព័ន្ធ', en: 'System Settings' },
  'My Profile': { km: 'គណនីផ្ទាល់ខ្លួន', en: 'My Profile' },

  // Submenu Items
  // 1. Authentication
  'Login & Security': { km: 'ការចូល & សុវត្ថិភាព', en: 'Login & Security' },
  'Roles & Permissions': { km: 'សិទ្ធិ & តួនាទី', en: 'Roles & Permissions' },

  // 2. User Management
  'Students': { km: 'និស្សិត/សិស្ស', en: 'Students' },
  'Teachers': { km: 'សាស្ត្រាចារ្យ/គ្រូ', en: 'Teachers' },
  'Admins': { km: 'អ្នកគ្រប់គ្រង (Admins)', en: 'Admins' },
  'All Users': { km: 'អ្នកប្រើប្រាស់ទាំងអស់', en: 'All Users' },

  // 3. Academic Structure
  'Departments': { km: 'ដេប៉ាតឺម៉ង់', en: 'Departments' },
  'Majors': { km: 'ជំនាញឯកទេស (5 Majors)', en: 'Majors' },
  'Subjects': { km: 'មុខវិជ្ជា', en: 'Subjects' },
  'Academic Years': { km: 'ឆ្នាំសិក្សា', en: 'Academic Years' },
  'Semesters': { km: 'ឆមាស', en: 'Semesters' },

  // 4. Course Management
  'Courses': { km: 'វគ្គសិក្សាទាំងអស់', en: 'Courses' },
  'All Courses': { km: 'វគ្គសិក្សាទាំងអស់', en: 'All Courses' },
  'Course Approval': { km: 'អនុម័តវគ្គសិក្សា', en: 'Course Approval' },
  'Enrollment': { km: 'ការចុះឈ្មោះ', en: 'Enrollment' },

  // 5. Assessment
  'Quizzes': { km: 'កម្រងសំណួរ (Quizzes)', en: 'Quizzes' },
  'Assignments': { km: 'កិច្ចការផ្ទះ (Assignments)', en: 'Assignments' },
  'Question Bank': { km: 'ធនាគារសំណួរ', en: 'Question Bank' },

  // 6. Learning & Progress
  'Student Progress': { km: 'វឌ្ឍនភាពនិស្សិត', en: 'Student Progress' },
  'Course Completion': { km: 'ការបញ្ចប់វគ្គសិក្សា', en: 'Course Completion' },
  'Certificates': { km: 'វិញ្ញាបនបត្រ', en: 'Certificates' },

  // 7. Analytics & Reports
  'Student Analytics': { km: 'ស្ថិតិវិភាគនិស្សិត', en: 'Student Analytics' },
  'Course Analytics': { km: 'ស្ថិតិវិភាគវគ្គសិក្សា', en: 'Course Analytics' },
  'Teacher Analytics': { km: 'ស្ថិតិវិភាគគ្រូ', en: 'Teacher Analytics' },
  'System Reports': { km: 'របាយការណ៍ប្រព័ន្ធ', en: 'System Reports' },

  // 8. AI Management
  'AI Recommendations': { km: 'អនុសាសន៍ AI', en: 'AI Recommendations' },
  'At-Risk Students': { km: 'និស្សិតប្រឈមហានិភ័យ (At-Risk)', en: 'At-Risk Students' },
  'Difficult Topics': { km: 'ប្រធានបទលំបាក (Difficult Topics)', en: 'Difficult Topics' },
  'AI Configuration': { km: 'ការកំណត់រចនាសម្ព័ន្ធ AI', en: 'AI Configuration' },

  // 9. Communication
  'Announcements': { km: 'សេចក្តីប្រកាស', en: 'Announcements' },
  'Notifications': { km: 'ការជូនដំណឹង', en: 'Notifications' },

  // 10. System Settings
  'General Settings': { km: 'ការកំណត់ទូទៅ', en: 'General Settings' },
  'Academic Settings': { km: 'ការកំណត់ការសិក្សា', en: 'Academic Settings' },
  'System Logs': { km: 'កំណត់ហេតុប្រព័ន្ធ', en: 'System Logs' },
}

const getNavTitle = (name: string): string => {
  if (navTranslations[name]) {
    return currentLang.value === 'km' ? navTranslations[name].km : navTranslations[name].en
  }
  return name
}

const isOnline = ref(typeof window !== 'undefined' ? window.navigator.onLine : true)
const manualStatusOverride = ref<boolean | null>(null)
const onlineIconUrl = '/images/nav/online.svg'
const offlineIconUrl = '/images/nav/offline.svg'

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
  const nextLang = (code === 'en' ? 'en' : 'km') as 'km' | 'en'
  playTopBarSound()
  isLangOpen.value = false
  showLoading(nextLang === 'km' ? 'សូមរង់ចាំ កំពុងដំណើរការ...' : 'Please wait while loading')
  i18n.setLanguage(nextLang)
  try {
    router.reload({
      onFinish: () => hideLoading(750),
      onError: () => hideLoading(750)
    })
  } catch {
    hideLoading(750)
  }
}

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
  try {
    router.reload({
      onFinish: () => hideLoading(750),
      onError: () => hideLoading(750)
    })
  } catch {
    hideLoading(750)
  }
}

const currentBreadcrumb = computed(() => {
  const url = page.url
  const prefix = currentLang.value === 'km' ? 'អ្នកគ្រប់គ្រង' : 'Admin'
  if (url.startsWith('/admin/dashboard')) return [prefix, getNavTitle('Dashboard')]
  if (url.startsWith('/admin/auth') || url.startsWith('/admin/auth-logs')) return [prefix, getNavTitle('Authentication')]
  if (url.startsWith('/admin/user-management')) return [prefix, getNavTitle('User Management')]
  if (url.startsWith('/admin/academic-structure')) return [prefix, getNavTitle('Academic Structure')]
  if (url.startsWith('/admin/course-module') || url.startsWith('/admin/courses')) return [prefix, getNavTitle('Course Management')]
  if (url.startsWith('/admin/enrollment')) return [prefix, getNavTitle('Course Management'), getNavTitle('Enrollment')]
  if (url.startsWith('/admin/quizzes')) return [prefix, getNavTitle('Assessment')]
  if (url.startsWith('/admin/progress')) return [prefix, getNavTitle('Learning & Progress')]
  if (url.startsWith('/admin/reports')) return [prefix, getNavTitle('Analytics & Reports')]
  if (url.startsWith('/admin/ai-rules')) return [prefix, getNavTitle('AI Management')]
  if (url.startsWith('/admin/certificates')) return [prefix, getNavTitle('Learning & Progress'), getNavTitle('Certificates')]
  if (url.startsWith('/admin/notifications')) return [prefix, getNavTitle('Communication')]
  if (url.startsWith('/admin/settings')) return [prefix, getNavTitle('System Settings')]
  return [prefix, currentLang.value === 'km' ? 'ទិដ្ឋភាពទូទៅ' : 'Overview']
})

const pageTitle = computed(() => {
  if (props.title) return props.title
  const crumb = currentBreadcrumb.value
  return crumb.length > 1 ? crumb[crumb.length - 1] : (currentLang.value === 'km' ? 'ផ្ទាំងគ្រប់គ្រង' : 'Admin Dashboard')
})

const quickActions = computed(() => [
  { name: currentLang.value === 'km' ? 'បង្កើតអ្នកប្រើប្រាស់' : 'Add User', href: '/admin/user-management/students', iconUrl: '/images/actions/add-user.svg' },
  { name: currentLang.value === 'km' ? 'បង្កើតវគ្គសិក្សា' : 'Add Course', href: '/admin/course-module/all', iconUrl: '/images/actions/add-course.svg' },
  { name: currentLang.value === 'km' ? 'ចុះឈ្មោះនិស្សិត' : 'Enroll Student', href: '/admin/enrollment/courses', iconUrl: '/images/nav/enrollment.svg' },
  { name: currentLang.value === 'km' ? 'និស្សិតប្រឈមហានិភ័យ' : 'At-Risk Students', href: '/admin/progress?tab=at_risk', iconUrl: '/images/nav/sub/failed.svg' },
  { name: currentLang.value === 'km' ? 'ផ្ញើសារប្រកាស' : 'Announcement', href: '/admin/notifications/announcements', iconUrl: '/images/actions/announcement.svg' },
  { name: currentLang.value === 'km' ? 'ចេញវិញ្ញាបនបត្រ' : 'Issue Certificate', href: '/admin/certificates/issued', iconUrl: '/images/actions/certificate.svg' },
  { name: currentLang.value === 'km' ? 'ការកំណត់ប្រព័ន្ធ' : 'System Settings', href: '/admin/settings', iconUrl: '/images/nav/settings.svg' }
])

const searchableLinks = computed(() => {
  const list: { name: string; category: string; href: string; icon?: string; iconUrl?: string }[] = []
  navigation.forEach(nav => {
    const parentName = getNavTitle(nav.name)
    if (nav.children && nav.children.length > 0) {
      nav.children.forEach(sub => {
        list.push({
          name: getNavTitle(sub.name),
          category: parentName,
          href: sub.href,
          icon: sub.icon || nav.icon,
          iconUrl: sub.iconUrl || nav.iconUrl
        })
      })
    } else if (nav.href) {
      list.push({
        name: parentName,
        category: currentLang.value === 'km' ? 'ម៉ឺនុយមេ' : 'Main Navigation',
        href: nav.href,
        icon: nav.icon,
        iconUrl: nav.iconUrl
      })
    }
  })
  return list
})

interface GlobalSearchResult {
  pages: Array<{ title: string; title_km?: string; category: string; url: string; icon?: string }>
  courses: Array<{ id: number; title: string; code: string; url: string }>
  students: Array<{ id: number; name: string; subtitle: string; url: string }>
  teachers: Array<{ id: number; name: string; subtitle: string; url: string }>
}

const isSearching = ref(false)
const globalSearchResults = ref<GlobalSearchResult>({
  pages: [],
  courses: [],
  students: [],
  teachers: []
})

let searchDebounceTimer: any = null

const performGlobalSearch = (query: string) => {
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(async () => {
    isSearching.value = true
    try {
      const res = await fetch(`/admin/api/global-search?q=${encodeURIComponent(query.trim())}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      if (res.ok) {
        const data = await res.json()
        globalSearchResults.value = data.results || { pages: [], courses: [], students: [], teachers: [] }
      }
    } catch (e) {
      // fallback
    } finally {
      isSearching.value = false
    }
  }, 200)
}

watch(searchQuery, (newVal) => {
  performGlobalSearch(newVal)
})

const filteredSearchResults = computed(() => {
  if (!searchQuery.value.trim()) return searchableLinks.value.slice(0, 7)
  const q = searchQuery.value.toLowerCase()
  return searchableLinks.value.filter(item =>
    item.name.toLowerCase().includes(q) ||
    item.category.toLowerCase().includes(q)
  )
})

const handleSearchEnter = () => {
  playClickSound()
  if (globalSearchResults.value.courses && globalSearchResults.value.courses.length > 0) {
    const target = globalSearchResults.value.courses[0].url
    closeAllDropdowns()
    router.visit(target)
    return
  }
  if (globalSearchResults.value.students && globalSearchResults.value.students.length > 0) {
    const target = globalSearchResults.value.students[0].url
    closeAllDropdowns()
    router.visit(target)
    return
  }
  if (globalSearchResults.value.teachers && globalSearchResults.value.teachers.length > 0) {
    const target = globalSearchResults.value.teachers[0].url
    closeAllDropdowns()
    router.visit(target)
    return
  }
  if (globalSearchResults.value.pages && globalSearchResults.value.pages.length > 0) {
    const target = globalSearchResults.value.pages[0].url
    closeAllDropdowns()
    router.visit(target)
    return
  }
  if (filteredSearchResults.value.length > 0) {
    const target = filteredSearchResults.value[0].href
    closeAllDropdowns()
    router.visit(target)
    return
  }
  if (searchQuery.value.trim()) {
    closeAllDropdowns()
    router.visit(`/admin/course-module/all?search=${encodeURIComponent(searchQuery.value.trim())}`)
  }
}

interface NotificationItem {
  id: number
  title_km: string
  title_en: string
  desc_km: string
  desc_en: string
  time_km: string
  time_en: string
  type: 'payment' | 'user' | 'support' | 'system' | 'assignment' | 'ai' | 'risk' | 'announcement'
  defaultRead: boolean
  read: boolean
  link: string
}

const rawAdminNotifications: Omit<NotificationItem, 'read'>[] = [
  {
    id: 1,
    title_km: 'កាលបរិច្ឆេទផុតកំណត់៖ JavaScript Functions',
    title_en: 'Assignment Due: JavaScript Functions',
    desc_km: 'សិស្ស Sok Dara ជិតដល់ថ្ងៃផុតកំណត់កិច្ចការ (ស្អែក ម៉ោង ១១:៥៩ យប់)',
    desc_en: 'Assignment: JavaScript Functions is due tomorrow at 11:59 PM',
    time_km: '10 នាទីមុន',
    time_en: '10 mins ago',
    type: 'assignment',
    defaultRead: false,
    link: '/admin/quizzes?tab=assignments'
  },
  {
    id: 2,
    title_km: 'អនុសាសន៍ AI៖ បានរកឃើញប្រធានបទខ្សោយ',
    title_en: 'AI Recommendation: Weak Topic Detected',
    desc_km: 'AI បានរកឃើញប្រធានបទខ្សោយ Soil Management & pH Balance',
    desc_en: 'AI detected a weak topic: JavaScript Functions / Soil Management',
    time_km: '25 នាទីមុន',
    time_en: '25 mins ago',
    type: 'ai',
    defaultRead: false,
    link: '/admin/ai-rules?tab=rules'
  },
  {
    id: 3,
    title_km: 'ការជូនដំណឹង At-Risk៖ សិស្សត្រូវការជំនួយ',
    title_en: 'At-Risk Alert: Student A Requires Attention',
    desc_km: 'សិស្ស Sok Piseth (IT/Agri) វឌ្ឍនភាព 32% + អសកម្ម 12 ថ្ងៃ',
    desc_en: 'Student Sok Piseth flagged High Risk: Progress 32%, Inactive 12d',
    time_km: '1 ម៉ោងមុន',
    time_en: '1 hour ago',
    type: 'risk',
    defaultRead: false,
    link: '/admin/ai-rules?tab=at_risk'
  },
  {
    id: 4,
    title_km: 'សេចក្តីប្រកាស៖ ការចុះឈ្មោះមុខវិជ្ជាឆមាសទី១',
    title_en: 'Announcement: Semester 1 Course Registration',
    desc_km: 'បានផ្សព្វផ្សាយទៅកាន់និស្សិតដេប៉ាតឺម៉ង់ព័ត៌មានវិទ្យា',
    desc_en: 'Broadcasted to Information Technology Year 2 students',
    time_km: 'ម្សិលមិញ',
    time_en: 'Yesterday',
    type: 'announcement',
    defaultRead: true,
    link: '/admin/notifications/announcements'
  },
  {
    id: 5,
    title_km: 'ការអនុម័ត Course៖ វគ្គសិក្សាត្រូវបានអនុម័ត',
    title_en: 'Course Approved: Soil Science & Crop Nutrition',
    desc_km: 'មាតិកាវគ្គសិក្សាត្រូវបានអនុម័ត និងបោះពុម្ពផ្សាយផ្លូវការ',
    desc_en: 'Course syllabus approved and published for enrollment',
    time_km: '2 ថ្ងៃមុន',
    time_en: '2 days ago',
    type: 'support',
    defaultRead: true,
    link: '/admin/course-module/approval'
  },
  {
    id: 6,
    title_km: 'ការបម្រុងទុកទិន្នន័យបានជោគជ័យ',
    title_en: 'Database Backup Successful',
    desc_km: 'ការបម្រុងទុកមូលដ្ឋានទិន្នន័យប្រព័ន្ធស្វ័យប្រវត្តិនារាត្រីបានជោគជ័យ',
    desc_en: 'Automated nightly database backup completed successfully',
    time_km: '3 ថ្ងៃមុន',
    time_en: '3 days ago',
    type: 'system',
    defaultRead: true,
    link: '/admin/auth-logs'
  }
]

const NOTIF_STORAGE_KEY = 'elms_admin_read_notif_ids_v2'

const notifications = ref<NotificationItem[]>(
  rawAdminNotifications.map(n => ({ ...n, read: n.defaultRead }))
)

const loadPersistedNotifications = () => {
  if (typeof window === 'undefined') return
  try {
    const raw = localStorage.getItem(NOTIF_STORAGE_KEY)
    const readIds: number[] = raw ? JSON.parse(raw) : []
    notifications.value = rawAdminNotifications.map(n => ({
      ...n,
      read: n.defaultRead || readIds.includes(n.id)
    }))
  } catch {
    notifications.value = rawAdminNotifications.map(n => ({ ...n, read: n.defaultRead }))
  }
}

const savePersistedReadIds = () => {
  if (typeof window === 'undefined') return
  try {
    const readIds = notifications.value.filter(n => n.read).map(n => n.id)
    localStorage.setItem(NOTIF_STORAGE_KEY, JSON.stringify(readIds))
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

const markAllAsRead = () => {
  playClickSound()
  notifications.value.forEach(n => { n.read = true })
  savePersistedReadIds()
}

const markNotificationRead = (id: number) => {
  const item = notifications.value.find(n => n.id === id)
  if (item && !item.read) {
    item.read = true
    savePersistedReadIds()
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

const toggleFullscreen = () => {
  playTopBarSound()
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

const onFullscreenChange = () => {
  isFullscreen.value = !!document.fullscreenElement
}

const closeAllDropdowns = () => {
  isSearchOpen.value = false
  isTopSearchDropdownOpen.value = false
  isNotificationOpen.value = false
  isProfileOpen.value = false
  isSettingsOpen.value = false
  isQuickActionOpen.value = false
  isLangOpen.value = false
  isStatusOpen.value = false
  isDateOpen.value = false
}

const toggleDropdown = (target: 'search' | 'topsearch' | 'notification' | 'profile' | 'settings' | 'quick' | 'lang' | 'status' | 'date') => {
  const current = target === 'search' ? isSearchOpen.value
    : target === 'topsearch' ? isTopSearchDropdownOpen.value
    : target === 'notification' ? isNotificationOpen.value
    : target === 'profile' ? isProfileOpen.value
    : target === 'settings' ? isSettingsOpen.value
    : target === 'quick' ? isQuickActionOpen.value
    : target === 'lang' ? isLangOpen.value
    : target === 'status' ? isStatusOpen.value
    : isDateOpen.value

  closeAllDropdowns()

  if (target === 'search') {
    isSearchOpen.value = !current
    if (isSearchOpen.value && (!globalSearchResults.value.pages || globalSearchResults.value.pages.length === 0)) {
      performGlobalSearch('')
    }
  }
  else if (target === 'topsearch') {
    isTopSearchDropdownOpen.value = !current
    if (isTopSearchDropdownOpen.value && (!globalSearchResults.value.pages || globalSearchResults.value.pages.length === 0)) {
      performGlobalSearch('')
    }
  }
  else if (target === 'notification') isNotificationOpen.value = !current
  else if (target === 'profile') isProfileOpen.value = !current
  else if (target === 'settings') isSettingsOpen.value = !current
  else if (target === 'quick') isQuickActionOpen.value = !current
  else if (target === 'lang') isLangOpen.value = !current
  else if (target === 'status') isStatusOpen.value = !current
  else if (target === 'date') isDateOpen.value = !current
}

const handleDocumentClick = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.nav-dropdown-scope')) {
    closeAllDropdowns()
  }
}

const handleKeydown = (e: KeyboardEvent) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    toggleDropdown('search')
    if (isSearchOpen.value) {
      performGlobalSearch('')
    }
  } else if (e.key === 'Escape') {
    closeAllDropdowns()
  }
}

// Auto-expand active module when route changes or loads
watch(
  () => page.url,
  () => {
    navigation.forEach(item => {
      if (item.key && item.children && isChildActive(item.children)) {
        expandedModules.value[item.key] = true
      }
    })
  },
  { immediate: true }
)

onMounted(() => {
  try {
    const saved = localStorage.getItem('elms_sidebar_collapsed')
    if (saved !== null) {
      isSidebarCollapsed.value = saved === 'true'
    }
  } catch {}
  loadPersistedNotifications()
  initTheme()
  window.addEventListener('keydown', handleKeydown)
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
  window.addEventListener('click', handleDocumentClick)
  document.addEventListener('fullscreenchange', onFullscreenChange)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
  window.removeEventListener('click', handleDocumentClick)
  document.removeEventListener('fullscreenchange', onFullscreenChange)
})
</script>

<template>
  <Head :title="pageTitle" />
  <GlobalToast />
  <GlobalLoadingOverlay />
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 selection:bg-indigo-500/30 transition-colors duration-200">
    <!-- Sidebar for Desktop Matching Reference Design (Dark Navy, Starts Under Header) -->
    <aside :class="[isSidebarCollapsed ? 'is-collapsed overflow-visible' : 'overflow-hidden', 'elms-sidebar-anim fixed top-14 bottom-0 left-0 z-40 hidden flex-col bg-[#071120] text-slate-300 border-r border-slate-800 md:flex shadow-xl']">
      <!-- Navigation -->
      <nav
        :class="[
          isSidebarCollapsed ? 'overflow-visible' : 'custom-scrollbar overflow-y-auto',
          'flex flex-1 flex-col py-4 px-3'
        ]"
        :style="isSidebarCollapsed ? { scrollbarWidth: 'none', msOverflowStyle: 'none' } : {}"
      >
        <ul role="list" class="space-y-1 w-full">
          <li
            v-for="item in navigation"
            :key="item.name"
            class="relative w-full"
            :class="isSidebarCollapsed ? 'group/flyout' : ''"
          >
            <!-- 1. Logout Action Button -->
            <button
              v-if="item.isLogout"
              @click="logout"
              type="button"
              :title="isSidebarCollapsed ? item.name : undefined"
              class="elms-nav-item group flex items-center w-full h-10 px-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-rose-500/10 cursor-pointer"
            >
              <div class="relative flex items-center justify-center shrink-0 w-10 h-10 rounded-lg group-hover:bg-rose-500/15 transition-colors">
                <svg class="h-4.5 w-4.5 shrink-0 text-rose-500" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/>
                </svg>
              </div>
              <div
                :class="[
                  'elms-nav-label-container flex items-center min-w-0 overflow-hidden whitespace-nowrap',
                  isSidebarCollapsed ? 'max-w-0 opacity-0 pointer-events-none' : 'max-w-[200px] opacity-100 ml-2 flex-1'
                ]"
              >
                <span class="truncate text-left font-medium">{{ getNavTitle(item.name) }}</span>
              </div>
            </button>

            <!-- 2. Direct Link (No Children) -->
            <Link
              v-else-if="!item.children || item.children.length === 0"
              :href="item.href!"
              :title="isSidebarCollapsed ? item.name : undefined"
              :class="[
                $page.url.startsWith(item.href!) 
                  ? 'bg-slate-800/90 text-white font-medium border border-slate-700/60 shadow-xs relative before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-blue-500 before:rounded-r' 
                  : 'text-slate-400 hover:text-white hover:bg-slate-800/50 border border-transparent font-normal',
                'elms-nav-item group flex items-center w-full h-10 px-2 rounded-xl text-xs transition-colors'
              ]"
            >
              <div class="relative flex items-center justify-center shrink-0 w-10 h-10 rounded-lg">
                <!-- Dashboard -->
                <svg v-if="item.iconType === 'dashboard'" :class="['h-4.5 w-4.5 shrink-0 transition-colors', $page.url.startsWith(item.href!) ? 'text-blue-400' : 'text-slate-400 group-hover:text-white']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <!-- Sales -->
                <svg v-else-if="item.iconType === 'sales'" class="h-4.5 w-4.5 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <!-- Purchase -->
                <svg v-else-if="item.iconType === 'purchase'" class="h-4.5 w-4.5 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <!-- Inventory -->
                <svg v-else-if="item.iconType === 'inventory'" class="h-4.5 w-4.5 shrink-0 text-amber-500 fill-amber-500/20" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <!-- Customers -->
                <svg v-else-if="item.iconType === 'customers'" class="h-4.5 w-4.5 shrink-0 text-rose-400 fill-rose-500/20" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <!-- Suppliers -->
                <svg v-else-if="item.iconType === 'suppliers'" class="h-4.5 w-4.5 shrink-0 text-emerald-400 fill-emerald-500/10" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 17a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4zM3 4h11a1 1 0 011 1v10H3V4zm11 3h3.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V15H14V7z"/>
                </svg>
                <!-- Jewellery -->
                <svg v-else-if="item.iconType === 'jewellery'" class="h-4.5 w-4.5 shrink-0 text-yellow-400 fill-yellow-400/20" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l8 6-8 14L4 8l8-6zm-4.5 6l4.5 11 4.5-11H7.5zM6 8h12"/>
                </svg>
                <!-- Accounts -->
                <svg v-else-if="item.iconType === 'accounts'" class="h-4.5 w-4.5 shrink-0 text-cyan-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <rect x="3" y="4" width="18" height="16" rx="3" stroke-width="2"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6M9 11h4a2 2 0 000-4H9v9m3-2l3 3"/>
                </svg>
                <!-- GST Reports -->
                <svg v-else-if="item.iconType === 'gst-reports'" class="h-4.5 w-4.5 shrink-0 text-purple-400 fill-purple-400/10" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <!-- Branch -->
                <svg v-else-if="item.iconType === 'branch'" class="h-4.5 w-4.5 shrink-0 text-slate-300" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <!-- Employees -->
                <svg v-else-if="item.iconType === 'employees'" class="h-4.5 w-4.5 shrink-0 text-blue-400 fill-blue-400/20" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <!-- Reports -->
                <svg v-else-if="item.iconType === 'reports'" class="h-4.5 w-4.5 shrink-0 text-slate-300" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 20v-4m5 4v-7m5 7v-10"/>
                </svg>
                <!-- Backup -->
                <svg v-else-if="item.iconType === 'backup'" class="h-4.5 w-4.5 shrink-0 text-slate-300" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/>
                </svg>
              </div>
              <div
                :class="[
                  'elms-nav-label-container flex items-center justify-between min-w-0 overflow-hidden whitespace-nowrap',
                  isSidebarCollapsed ? 'max-w-0 opacity-0 pointer-events-none' : 'max-w-[200px] opacity-100 ml-2 flex-1'
                ]"
              >
                <span class="truncate">{{ getNavTitle(item.name) }}</span>
                <svg
                  v-if="item.hasArrow"
                  class="w-3.5 h-3.5 text-slate-500 ml-auto shrink-0 transition-transform group-hover:translate-x-0.5"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
              </div>
            </Link>

            <!-- 3. Collapsible Module with Submenu (Settings) -->
            <div v-else class="space-y-1 w-full flex flex-col items-center">
              <button
                @click="toggleModule(item.key!)"
                type="button"
                :title="isSidebarCollapsed ? item.name : undefined"
                :class="[
                  isChildActive(item.children) 
                    ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30 font-semibold' 
                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60 border border-transparent font-normal',
                  'elms-nav-item group flex items-center justify-between w-full h-10 px-2 rounded-xl text-xs cursor-pointer'
                ]"
              >
                <div class="flex items-center min-w-0">
                  <div class="relative flex items-center justify-center shrink-0 w-10 h-10 rounded-lg">
                    <svg v-if="item.iconType === 'auth'" class="h-4.5 w-4.5 shrink-0 text-amber-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <svg v-else-if="item.iconType === 'users'" class="h-4.5 w-4.5 shrink-0 text-rose-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <svg v-else-if="item.iconType === 'academics'" class="h-4.5 w-4.5 shrink-0 text-cyan-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <svg v-else-if="item.iconType === 'courses'" class="h-4.5 w-4.5 shrink-0 text-emerald-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <svg v-else-if="item.iconType === 'assessment'" class="h-4.5 w-4.5 shrink-0 text-purple-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <svg v-else-if="item.iconType === 'progress'" class="h-4.5 w-4.5 shrink-0 text-orange-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 20v-4m5 4v-7m5 7v-10"/>
                    </svg>
                    <svg v-else-if="item.iconType === 'analytics'" class="h-4.5 w-4.5 shrink-0 text-blue-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <svg v-else-if="item.iconType === 'ai'" class="h-4.5 w-4.5 shrink-0 text-indigo-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <svg v-else-if="item.iconType === 'communication'" class="h-4.5 w-4.5 shrink-0 text-amber-300" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <svg v-else class="h-4.5 w-4.5 shrink-0 text-slate-300" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                  </div>
                  <div
                    :class="[
                      'elms-nav-label-container flex items-center min-w-0 overflow-hidden whitespace-nowrap',
                      isSidebarCollapsed ? 'max-w-0 opacity-0 pointer-events-none' : 'max-w-[150px] opacity-100 ml-2'
                    ]"
                  >
                    <span class="truncate">{{ getNavTitle(item.name) }}</span>
                  </div>
                </div>

                <div
                  :class="[
                    'elms-nav-label-container flex items-center overflow-hidden',
                    isSidebarCollapsed ? 'max-w-0 opacity-0 pointer-events-none' : 'max-w-[30px] opacity-100'
                  ]"
                >
                  <svg
                    :class="[
                      expandedModules[item.key!] ? 'rotate-180 text-blue-400' : 'text-slate-500 group-hover:text-slate-300',
                      'w-3.5 h-3.5 transition-transform duration-200 shrink-0 ml-1'
                    ]"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </button>

              <!-- Submenu Items (Smooth Accordion Slide & Fade) -->
              <div
                :class="[
                  'overflow-hidden transition-all duration-300 ease-in-out w-full',
                  (!isSidebarCollapsed && expandedModules[item.key!])
                    ? 'max-h-96 opacity-100 my-1.5'
                    : 'max-h-0 opacity-0 my-0 pointer-events-none'
                ]"
              >
                <div class="relative ml-6 pl-4 space-y-1 pr-3">
                  <div
                    v-for="(sub, idx) in item.children"
                    :key="sub.name"
                    class="group relative flex items-center"
                  >
                    <div
                      v-if="idx < item.children.length - 1"
                      class="absolute -left-4 top-0 bottom-0 w-[2px] bg-slate-800"
                    ></div>
                    <div
                      :class="[
                        isSubActive(sub.href) ? 'border-blue-500' : 'border-slate-800 group-hover:border-slate-600',
                        'absolute -left-4 top-0 h-1/2 w-3.5 border-l-2 border-b-2 rounded-bl-xl transition-colors duration-200 pointer-events-none'
                      ]"
                    ></div>

                    <Link
                      :href="sub.href"
                      :class="[
                        isSubActive(sub.href) 
                          ? 'text-white font-medium bg-blue-500/15 border border-blue-500/30' 
                          : 'text-slate-400 hover:text-white hover:bg-slate-800/50 border border-transparent',
                        'flex-1 flex items-center gap-2 rounded-xl px-2.5 py-1.5 text-xs transition-all duration-200 ml-1'
                      ]"
                    >
                      <span class="truncate">{{ getNavTitle(sub.name) }}</span>
                    </Link>
                  </div>
                </div>
              </div>
            </div>

            <!-- Flyout Popover for Collapsed Sidebar Mode (Has Children) -->
            <div
              v-if="isSidebarCollapsed && item.children && item.children.length > 0"
              class="absolute left-full top-0 ml-2 w-64 opacity-0 pointer-events-none group-hover/flyout:opacity-100 group-hover/flyout:pointer-events-auto transition-all duration-200 ease-out translate-x-1 group-hover/flyout:translate-x-0 z-50"
            >
              <div class="absolute -left-3 top-0 bottom-0 w-3"></div>
              <div class="relative bg-[#0c1a2e] border border-slate-800 rounded-2xl p-3 shadow-2xl ring-1 ring-slate-800">
                <div class="absolute -left-1.5 top-3.5 w-3 h-3 bg-[#0c1a2e] border-l border-b border-slate-800 rotate-45 z-10 pointer-events-none"></div>
                <div class="relative z-20 flex items-center justify-between px-2 py-1.5 mb-2 border-b border-slate-800 pb-2">
                  <span class="text-xs font-bold text-white truncate">{{ getNavTitle(item.name) }}</span>
                </div>
                <div class="relative z-20 space-y-1 max-h-[70vh] overflow-y-auto custom-scrollbar pr-1">
                  <Link
                    v-for="sub in item.children"
                    :key="sub.name"
                    :href="sub.href"
                    :class="[
                      isSubActive(sub.href)
                        ? 'bg-blue-600/25 text-blue-300 font-semibold border border-blue-500/30 shadow-xs'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800/70 border border-transparent',
                      'flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-xs transition-all duration-150 group/flyout-sub'
                    ]"
                  >
                    <span class="truncate">{{ getNavTitle(sub.name) }}</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Sleek Tooltip Popover for Collapsed Sidebar Mode (No Children) -->
            <div
              v-else-if="isSidebarCollapsed && (!item.children || item.children.length === 0)"
              class="absolute left-full top-1/2 -translate-y-1/2 ml-2 opacity-0 pointer-events-none group-hover/flyout:opacity-100 transition-all duration-200 ease-out translate-x-1 group-hover/flyout:translate-x-0 z-50 whitespace-nowrap"
            >
              <div class="relative bg-[#0c1a2e] border border-slate-800 text-white text-xs font-semibold px-3 py-1.5 rounded-xl shadow-xl flex items-center gap-2 ring-1 ring-slate-800">
                <div class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-[#0c1a2e] border-l border-b border-slate-800 rotate-45 pointer-events-none"></div>
                <span class="relative z-20">{{ getNavTitle(item.name) }}</span>
              </div>
            </div>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Mobile Drawer Sidebar (Sliding Drawer on Mobile) -->
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-300 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-slate-950/80 z-50 md:hidden backdrop-blur-sm"
      ></div>
    </Transition>

    <aside
      :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col md:hidden transition-transform duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] shadow-2xl'
      ]"
    >
      <div class="h-14 px-4 flex items-center justify-between border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3">
          <img :src="logoUrl" alt="E-LMS Logo" class="w-8 h-8 rounded-full object-cover ring-2 ring-indigo-500/30" />
          <div>
            <span class="font-bold text-sm text-slate-900 dark:text-white block">E-LMS Admin</span>
            <span class="text-[9px] text-slate-500 dark:text-slate-400 uppercase tracking-wide block">{{ currentLang === 'km' ? 'ផ្ទាំងគ្រប់គ្រង' : 'ADMIN PANEL' }}</span>
          </div>
        </div>
        <button @click="sidebarOpen = false" class="p-1 text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar bg-[#071120] text-slate-300">
        <template v-for="item in navigation" :key="item.name">
          <!-- Mobile Logout -->
          <button
            v-if="item.isLogout"
            @click="sidebarOpen = false; logout()"
            type="button"
            class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-rose-500/10 transition-colors cursor-pointer"
          >
            <div class="flex items-center gap-3 truncate">
              <svg class="h-4.5 w-4.5 shrink-0 text-rose-500" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/>
              </svg>
              <span class="truncate">{{ getNavTitle(item.name) }}</span>
            </div>
          </button>

          <!-- Mobile Direct Link -->
          <Link
            v-else-if="!item.children || item.children.length === 0"
            :href="item.href!"
            @click="sidebarOpen = false"
            :class="[
              $page.url.startsWith(item.href!) 
                ? 'bg-slate-800/90 text-white font-medium border border-slate-700/60 shadow-xs relative before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-blue-500 before:rounded-r' 
                : 'text-slate-400 hover:text-white hover:bg-slate-800/60 border border-transparent font-normal',
              'flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-colors'
            ]"
          >
            <div class="flex items-center gap-3 truncate">
              <!-- Dashboard -->
              <svg v-if="item.iconType === 'dashboard'" :class="['h-4.5 w-4.5 shrink-0 transition-colors', $page.url.startsWith(item.href!) ? 'text-blue-400' : 'text-slate-400 group-hover:text-white']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
              </svg>
              <svg v-else-if="item.iconType === 'sales'" class="h-4.5 w-4.5 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              <svg v-else-if="item.iconType === 'purchase'" class="h-4.5 w-4.5 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <svg v-else-if="item.iconType === 'inventory'" class="h-4.5 w-4.5 shrink-0 text-amber-500" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
              <svg v-else-if="item.iconType === 'customers'" class="h-4.5 w-4.5 shrink-0 text-rose-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              <svg v-else-if="item.iconType === 'suppliers'" class="h-4.5 w-4.5 shrink-0 text-emerald-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 17a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4zM3 4h11a1 1 0 011 1v10H3V4zm11 3h3.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V15H14V7z"/>
              </svg>
              <svg v-else-if="item.iconType === 'jewellery'" class="h-4.5 w-4.5 shrink-0 text-yellow-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l8 6-8 14L4 8l8-6zm-4.5 6l4.5 11 4.5-11H7.5zM6 8h12"/>
              </svg>
              <svg v-else-if="item.iconType === 'accounts'" class="h-4.5 w-4.5 shrink-0 text-cyan-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                <rect x="3" y="4" width="18" height="16" rx="3" stroke-width="2"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h6M9 11h4a2 2 0 000-4H9v9m3-2l3 3"/>
              </svg>
              <svg v-else-if="item.iconType === 'gst-reports'" class="h-4.5 w-4.5 shrink-0 text-purple-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <svg v-else-if="item.iconType === 'branch'" class="h-4.5 w-4.5 shrink-0 text-slate-300" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              <svg v-else-if="item.iconType === 'employees'" class="h-4.5 w-4.5 shrink-0 text-blue-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              <svg v-else-if="item.iconType === 'reports'" class="h-4.5 w-4.5 shrink-0 text-slate-300" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 20v-4m5 4v-7m5 7v-10"/>
              </svg>
              <svg v-else-if="item.iconType === 'backup'" class="h-4.5 w-4.5 shrink-0 text-slate-300" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/>
              </svg>
              <span class="truncate">{{ getNavTitle(item.name) }}</span>
            </div>
            <svg
              v-if="item.hasArrow"
              class="w-3.5 h-3.5 text-slate-500 shrink-0"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </Link>

          <!-- Mobile Collapsible (Settings) -->
          <div v-else class="space-y-1">
            <button
              @click="toggleModule(item.key!)"
              class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs text-slate-300 hover:text-white hover:bg-slate-800/60 font-normal cursor-pointer"
            >
              <div class="flex items-center gap-3 truncate">
                <svg v-if="item.iconType === 'auth'" class="h-4.5 w-4.5 shrink-0 text-amber-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <svg v-else-if="item.iconType === 'users'" class="h-4.5 w-4.5 shrink-0 text-rose-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <svg v-else-if="item.iconType === 'academics'" class="h-4.5 w-4.5 shrink-0 text-cyan-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <svg v-else-if="item.iconType === 'courses'" class="h-4.5 w-4.5 shrink-0 text-emerald-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <svg v-else-if="item.iconType === 'assessment'" class="h-4.5 w-4.5 shrink-0 text-purple-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <svg v-else-if="item.iconType === 'progress'" class="h-4.5 w-4.5 shrink-0 text-orange-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 20v-4m5 4v-7m5 7v-10"/>
                </svg>
                <svg v-else-if="item.iconType === 'analytics'" class="h-4.5 w-4.5 shrink-0 text-blue-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <svg v-else-if="item.iconType === 'ai'" class="h-4.5 w-4.5 shrink-0 text-indigo-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <svg v-else-if="item.iconType === 'communication'" class="h-4.5 w-4.5 shrink-0 text-amber-300" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <svg v-else class="h-4.5 w-4.5 shrink-0 text-slate-300" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="truncate">{{ getNavTitle(item.name) }}</span>
              </div>
              <svg :class="[expandedModules[item.key!] ? 'rotate-180 text-blue-400' : '', 'w-4 h-4 transition-transform text-slate-400']" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div v-show="expandedModules[item.key!]" class="pl-4 ml-4 space-y-1 my-1 border-l border-slate-800">
              <Link
                v-for="sub in item.children"
                :key="sub.href"
                :href="sub.href"
                @click="sidebarOpen = false"
                :class="[
                  $page.url.startsWith(sub.href) ? 'bg-blue-600/25 text-blue-300 font-bold border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60',
                  'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs transition-all'
                ]"
              >
                <span class="truncate">{{ getNavTitle(sub.name) }}</span>
              </Link>
            </div>
          </div>
        </template>
      </nav>

      <!-- Mobile Footer -->
      <div class="p-3.5 border-t border-slate-800 bg-[#071120] flex items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs">
            {{ user.name ? user.name.charAt(0) : 'A' }}
          </div>
          <div class="min-w-0">
            <p class="font-bold text-white text-xs truncate">{{ user.name }}</p>
            <p class="text-[10px] text-slate-400 truncate">{{ user.email }}</p>
          </div>
        </div>
        <button @click="logout" class="p-2 text-slate-400 hover:text-red-400 rounded-lg cursor-pointer">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </button>
      </div>
    </aside>

    <!-- Fixed Top Navbar Matching Reference Image (Dark Navy Theme, Spans 100% Width) -->
    <header class="fixed top-0 inset-x-0 z-50 h-14 bg-[#071120] text-white border-b border-slate-800 shadow-md">
      <div class="flex h-14 items-center justify-between px-3 sm:px-6 gap-3">
        
        <!-- Left Side: Gold Diamond Logo, Title/Subtitle, Hamburger Toggle, White Pill Search Input -->
        <div class="flex items-center gap-2.5 sm:gap-4 min-w-0">
          <!-- Logo & Title -->
          <Link href="/admin/dashboard" class="flex items-center gap-2.5 shrink-0 group">
            <div class="w-8 h-8 rounded-lg bg-blue-500/15 border border-blue-500/30 flex items-center justify-center text-lg shadow-2xs group-hover:scale-105 transition-transform">
              <img :src="logoUrl" alt="E-LMS Logo" class="w-6 h-6 rounded-full object-cover" @error="onIconError" />
            </div>
            <div class="hidden sm:block leading-tight">
              <h1 class="text-xs sm:text-sm font-bold text-white tracking-wide truncate">
                {{ currentLang === 'km' ? 'E-LMS ផតថលអ្នកគ្រប់គ្រង' : 'E-LMS Admin Portal' }}
              </h1>
              <p class="text-[9px] text-blue-400 font-semibold tracking-wider">
                {{ currentLang === 'km' ? 'ប្រព័ន្ធគ្រប់គ្រងការអប់រំ' : 'Education Management System' }}
              </p>
            </div>
          </Link>

          <!-- Sidebar Layout Toggle Icon (Modern PanelLeft Window Standard) -->
          <button
            @click.stop.prevent="toggleSidebarCollapse"
            type="button"
            :class="[
              'p-2 rounded-xl transition-all duration-200 cursor-pointer shrink-0 active:scale-95 focus:outline-none flex items-center justify-center border',
              isSidebarCollapsed
                ? 'bg-blue-500/10 text-blue-400 border-blue-500/20 hover:bg-blue-500/20'
                : 'text-slate-300 hover:text-white hover:bg-slate-800/60 border-transparent'
            ]"
            :title="isSidebarCollapsed ? (currentLang === 'km' ? 'ពង្រីកម៉ឺនុយ' : 'Expand Sidebar') : (currentLang === 'km' ? 'បង្រួមម៉ឺនុយ' : 'Collapse Sidebar')"
          >
            <div class="w-5 h-5 flex items-center justify-center pointer-events-none">
              <!-- Collapsed on Desktop: PanelLeftOpen (Window with Right Chevron to Expand) -->
              <svg
                v-if="isSidebarCollapsed"
                class="w-5 h-5 transition-transform duration-200"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <rect x="3" y="3.5" width="18" height="17" rx="3" />
                <path d="M9 3.5v17" />
                <path stroke-linecap="round" stroke-linejoin="round" d="m14 9.5 2.5 2.5-2.5 2.5" />
              </svg>

              <!-- Drawer open on Mobile: Close X Icon -->
              <svg
                v-else-if="sidebarOpen"
                class="w-5 h-5 text-rose-400 transition-transform duration-200"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>

              <!-- Expanded on Desktop: PanelLeftClose (Window with Left Chevron to Collapse) -->
              <svg
                v-else
                class="w-5 h-5 transition-transform duration-200"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <rect x="3" y="3.5" width="18" height="17" rx="3" />
                <path d="M9 3.5v17" />
                <path stroke-linecap="round" stroke-linejoin="round" d="m15.5 14.5-2.5-2.5 2.5-2.5" />
              </svg>
            </div>
          </button>

          <!-- Long White Pill Search Input with Dual Magnifying Glass Icons & Direct Dropdown -->
          <div class="relative hidden md:block nav-dropdown-scope">
            <div class="flex items-center bg-white text-slate-800 rounded-full px-3.5 py-1.5 w-60 lg:w-80 xl:w-96 shadow-sm border border-slate-200 focus-within:ring-2 focus-within:ring-blue-500/40 focus-within:border-blue-500 transition-all">
              <svg @click="toggleDropdown('topsearch')" class="w-4 h-4 text-slate-400 mr-2 shrink-0 cursor-pointer hover:text-blue-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input
                type="text"
                v-model="searchQuery"
                @focus="isTopSearchDropdownOpen = true; performGlobalSearch(searchQuery)"
                @keydown.enter="handleSearchEnter"
                @keydown.esc="isTopSearchDropdownOpen = false"
                :placeholder="currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា និស្សិត របាយការណ៍...' : 'Search courses, students, reports...'"
                class="bg-transparent text-xs text-slate-800 placeholder-slate-400 focus:outline-none w-full"
              />
              <span class="text-[9.5px] text-slate-400 font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 shrink-0 ml-1 select-none">⌘K</span>
              <svg @click="handleSearchEnter" class="w-4 h-4 text-slate-400 ml-1.5 shrink-0 cursor-pointer hover:text-blue-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <!-- Top Search Dropdown Menu (Anchored directly under Search Bar) -->
            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="opacity-0 -translate-y-1 scale-98"
              enter-to-class="opacity-100 translate-y-0 scale-100"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="opacity-100 translate-y-0 scale-100"
              leave-to-class="opacity-0 -translate-y-1 scale-98"
            >
              <div
                v-if="isTopSearchDropdownOpen"
                @click.stop
                class="absolute left-0 mt-2 w-full min-w-[360px] max-w-[480px] rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xl p-2.5 z-50 overflow-hidden max-h-[460px] overflow-y-auto custom-scrollbar"
              >
                <!-- Loading State -->
                <div v-if="isSearching" class="flex items-center justify-center py-6 text-xs text-slate-400 gap-2">
                  <svg class="animate-spin w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  <span>{{ currentLang === 'km' ? 'កំពុងស្វែងរក...' : 'Searching LMS database...' }}</span>
                </div>

                <div v-else class="space-y-3">
                  <!-- Courses Section -->
                  <div v-if="globalSearchResults.courses && globalSearchResults.courses.length > 0">
                    <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
                      <span>📚</span> {{ currentLang === 'km' ? 'វគ្គសិក្សា' : 'Courses' }}
                    </div>
                    <div class="space-y-0.5">
                      <Link
                        v-for="c in globalSearchResults.courses"
                        :key="c.id"
                        :href="c.url"
                        @click="closeAllDropdowns"
                        class="flex items-center justify-between p-2 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/30 text-slate-800 dark:text-slate-100 transition-colors group cursor-pointer"
                      >
                        <div class="min-w-0">
                          <p class="text-xs font-bold truncate group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ c.title }}</p>
                          <p class="text-[10px] text-slate-400 font-mono">{{ c.code }}</p>
                        </div>
                        <span class="text-xs text-slate-400 group-hover:translate-x-0.5 transition-transform">→</span>
                      </Link>
                    </div>
                  </div>

                  <!-- Students Section -->
                  <div v-if="globalSearchResults.students && globalSearchResults.students.length > 0">
                    <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                      <span>🎓</span> {{ currentLang === 'km' ? 'និស្សិត / សិស្ស' : 'Students' }}
                    </div>
                    <div class="space-y-0.5">
                      <Link
                        v-for="s in globalSearchResults.students"
                        :key="s.id"
                        :href="s.url"
                        @click="closeAllDropdowns"
                        class="flex items-center justify-between p-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-slate-800 dark:text-slate-100 transition-colors group cursor-pointer"
                      >
                        <div class="min-w-0">
                          <p class="text-xs font-bold truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400">{{ s.name }}</p>
                          <p class="text-[10px] text-slate-400 truncate">{{ s.subtitle }}</p>
                        </div>
                        <span class="text-xs text-slate-400 group-hover:translate-x-0.5 transition-transform">→</span>
                      </Link>
                    </div>
                  </div>

                  <!-- Teachers Section -->
                  <div v-if="globalSearchResults.teachers && globalSearchResults.teachers.length > 0">
                    <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                      <span>👨‍🏫</span> {{ currentLang === 'km' ? 'សាស្ត្រាចារ្យ / គ្រូ' : 'Faculty / Teachers' }}
                    </div>
                    <div class="space-y-0.5">
                      <Link
                        v-for="t in globalSearchResults.teachers"
                        :key="t.id"
                        :href="t.url"
                        @click="closeAllDropdowns"
                        class="flex items-center justify-between p-2 rounded-xl hover:bg-amber-50 dark:hover:bg-amber-900/30 text-slate-800 dark:text-slate-100 transition-colors group cursor-pointer"
                      >
                        <div class="min-w-0">
                          <p class="text-xs font-bold truncate group-hover:text-amber-600 dark:group-hover:text-amber-400">{{ t.name }}</p>
                          <p class="text-[10px] text-slate-400 truncate">{{ t.subtitle }}</p>
                        </div>
                        <span class="text-xs text-slate-400 group-hover:translate-x-0.5 transition-transform">→</span>
                      </Link>
                    </div>
                  </div>

                  <!-- Navigation Modules -->
                  <div v-if="globalSearchResults.pages && globalSearchResults.pages.length > 0">
                    <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 flex items-center gap-1.5">
                      <span>⚡</span> {{ currentLang === 'km' ? 'ទំព័រ & ម៉ូឌុល' : 'Navigation & Modules' }}
                    </div>
                    <div class="grid grid-cols-2 gap-1">
                      <Link
                        v-for="p in globalSearchResults.pages"
                        :key="p.url"
                        :href="p.url"
                        @click="closeAllDropdowns"
                        class="p-2 rounded-xl bg-slate-50/70 dark:bg-slate-800/60 hover:bg-purple-50 dark:hover:bg-purple-900/30 text-slate-800 dark:text-slate-100 transition-colors group cursor-pointer truncate"
                      >
                        <p class="text-xs font-bold truncate group-hover:text-purple-600 dark:group-hover:text-purple-400">
                          {{ currentLang === 'km' && p.title_km ? p.title_km : p.title }}
                        </p>
                        <p class="text-[9px] text-slate-400 uppercase tracking-tight truncate">{{ p.category }}</p>
                      </Link>
                    </div>
                  </div>

                  <!-- Empty result -->
                  <div v-if="(!globalSearchResults.courses?.length && !globalSearchResults.students?.length && !globalSearchResults.teachers?.length && !globalSearchResults.pages?.length)" class="py-6 text-center text-xs text-slate-400">
                    <p class="font-semibold text-slate-500">{{ currentLang === 'km' ? 'មិនមានទិន្នន័យត្រូវគ្នានឹង' : 'No matches found for' }} "{{ searchQuery }}"</p>
                    <p class="text-[11px] text-slate-400 mt-1">{{ currentLang === 'km' ? 'ចុច Enter ដើម្បីស្វែងរកក្នុងតារាងវគ្គសិក្សា' : 'Press Enter to search all courses' }}</p>
                  </div>
                </div>

                <!-- Footer with Command Palette trigger -->
                <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[10px] text-slate-400">
                  <span class="flex items-center gap-1">
                    <kbd class="px-1 py-0.5 bg-slate-100 dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700">Enter</kbd> {{ currentLang === 'km' ? 'ជ្រើសរើស' : 'Select' }}
                  </span>
                  <button
                    type="button"
                    @click="isTopSearchDropdownOpen = false; isSearchOpen = true"
                    class="text-blue-600 dark:text-blue-400 font-bold hover:underline cursor-pointer"
                  >
                    {{ currentLang === 'km' ? 'បើកផ្ទាំងពេញលេញ (Ctrl+K) →' : 'Full Command Palette (Ctrl+K) →' }}
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>

        <!-- Right Side: Date Pill, Fullscreen, Bell, Settings Gear, Theme/Lang, Admin Profile Avatar -->
        <div class="flex items-center gap-1.5 sm:gap-2">

          <!-- Date Dropdown Pill (Fully Interactive with Presets & Picker) -->
          <div class="relative nav-dropdown-scope">
            <button
              @click.stop="toggleDropdown('date')"
              type="button"
              class="hidden sm:flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-700 px-3.5 py-1.5 rounded-full text-xs font-semibold shadow-sm border border-slate-200 select-none mr-1 cursor-pointer transition-all active:scale-95 focus:outline-none"
              :title="currentLang === 'km' ? 'ជ្រើសរើសកាលបរិច្ឆេទ / ឆមាសសិក្សា' : 'Select Date / Academic Term'"
            >
              <span class="text-blue-500 text-xs">📅</span>
              <span class="font-bold text-slate-900">{{ dateDisplayLabel }}</span>
              <span class="text-slate-400 text-[10px] ml-0.5 transition-transform duration-200" :class="isDateOpen ? 'rotate-180 text-blue-600' : ''">⌄</span>
            </button>

            <!-- Date Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="opacity-0 -translate-y-1 scale-95"
              enter-to-class="opacity-100 translate-y-0 scale-100"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="opacity-100 translate-y-0 scale-100"
              leave-to-class="opacity-0 -translate-y-1 scale-95"
            >
              <div
                v-if="isDateOpen"
                @click.stop
                class="absolute right-0 mt-2 w-72 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-3 z-50 overflow-hidden"
              >
                <div class="px-2 pb-2 mb-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                  <span class="text-xs font-bold text-slate-800 dark:text-white flex items-center gap-1.5">
                    <span>📅</span> {{ currentLang === 'km' ? 'កាលបរិច្ឆេទ & ឆមាសសិក្សា' : 'Date & Academic Term' }}
                  </span>
                  <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-md">Live</span>
                </div>

                <!-- Quick Preset Filter Buttons -->
                <div class="grid grid-cols-2 gap-1.5 mb-3">
                  <button
                    v-for="preset in datePresets"
                    :key="preset.id"
                    @click="selectDatePreset(preset)"
                    type="button"
                    :class="[
                      activeDatePreset === preset.id
                        ? 'bg-blue-600 text-white font-bold shadow-xs'
                        : 'bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-300 font-medium',
                      'px-2.5 py-1.5 rounded-xl text-xs text-left transition-all cursor-pointer truncate'
                    ]"
                  >
                    {{ currentLang === 'km' ? preset.label_km : preset.label_en }}
                  </button>
                </div>

                <!-- Direct Date Picker Input -->
                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-1.5">
                  <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    {{ currentLang === 'km' ? 'ជ្រើសរើសថ្ងៃជាក់លាក់' : 'Pick Specific Date' }}
                  </label>
                  <div class="flex items-center gap-2">
                    <input
                      type="date"
                      v-model="customDateInput"
                      @change="applyCustomDate"
                      class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    <button
                      @click="applyCustomDate"
                      type="button"
                      class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg cursor-pointer transition-colors shrink-0"
                    >
                      {{ currentLang === 'km' ? 'អនុវត្ត' : 'Apply' }}
                    </button>
                  </div>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Mobile Search Button (Mobile Only) -->
          <button
            @click="toggleDropdown('search')"
            type="button"
            class="p-1.5 text-slate-400 hover:text-white rounded-lg md:hidden focus:outline-none transition-colors cursor-pointer"
            :title="currentLang === 'km' ? 'ស្វែងរក' : 'Search'"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </button>

          <!-- Language Switcher (Direct 1-Click Toggle: Khmer / English) -->
          <button
            type="button"
            @click="toggleLanguage"
            class="p-1 h-8 w-8 rounded-full bg-slate-800/80 hover:bg-slate-700 transition-all border border-slate-700/60 shadow-xs flex items-center justify-center cursor-pointer select-none active:scale-95 group focus:outline-none"
            :title="currentLang === 'km' ? 'Switch to English (ប្តូរទៅ English)' : 'ប្តូរទៅភាសាខ្មែរ (Switch to Khmer)'"
          >
            <img
              :src="currentLang === 'km' ? '/images/flags/km.svg' : '/images/flags/en.svg'"
              :alt="currentLang"
              class="w-4 h-3 object-cover rounded-[2px]"
            />
          </button>

          <!-- Theme Switcher (Crisp SVG Sun/Moon Icons) -->
          <button
            type="button"
            @click="toggleTheme($event)"
            class="h-8 w-8 rounded-full bg-slate-800/80 hover:bg-slate-700 transition-all border border-slate-700/60 shadow-xs flex items-center justify-center cursor-pointer select-none active:scale-95 group focus:outline-none"
            :title="isDark ? (currentLang === 'km' ? 'ប្តូរទៅ Light Mode' : 'Switch to Light Mode') : (currentLang === 'km' ? 'ប្តូរទៅ Dark Mode' : 'Switch to Dark Mode')"
          >
            <svg v-if="isDark" class="w-4 h-4 text-amber-400 group-hover:rotate-45 transition-transform duration-300" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
            </svg>
            <svg v-else class="w-4 h-4 text-indigo-400 group-hover:-rotate-12 transition-transform duration-300" fill="currentColor" viewBox="0 0 20 20">
              <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
            </svg>
          </button>

          <!-- Notifications Bell (White Circle with Red Badge Matching Image) -->
          <div class="relative nav-dropdown-scope">
            <button
              @click.stop="handleNotificationToggle"
              type="button"
              class="relative p-1.5 w-8 h-8 rounded-full bg-white text-slate-800 hover:bg-slate-100 transition-all shadow-xs flex items-center justify-center cursor-pointer select-none active:scale-95 group focus:outline-none"
              :title="currentLang === 'km' ? 'ការជូនដំណឹង' : 'Notifications'"
            >
              <svg class="w-4 h-4 text-slate-700 transition-transform duration-300 group-hover:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <!-- Notification Count Badge with Number -->
              <span
                v-if="unreadNotificationsCount > 0"
                class="absolute -top-1 -right-1 min-w-[17px] h-[17px] px-1 flex items-center justify-center rounded-full bg-[#f43f5e] text-white text-[9.5px] font-black leading-none ring-2 ring-[#071120] shadow-sm select-none"
              >
                {{ unreadNotificationsCount > 99 ? '99+' : unreadNotificationsCount }}
              </span>
            </button>

            <!-- Notifications Dropdown -->
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
                class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xl z-50 overflow-hidden"
              >
                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/80 dark:bg-slate-900/80 backdrop-blur-md">
                  <div class="flex items-center gap-2">
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white">{{ currentLang === 'km' ? 'ការជូនដំណឹង' : 'Notifications' }}</h3>
                    <span v-if="unreadNotificationsCount > 0" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-500/10 text-rose-500 border border-rose-500/20">
                      {{ unreadNotificationsCount }} {{ currentLang === 'km' ? 'ថ្មី' : 'New' }}
                    </span>
                  </div>
                  <button
                    v-if="unreadNotificationsCount > 0"
                    @click="markAllAsRead"
                    class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 font-medium cursor-pointer transition-colors"
                  >
                    {{ currentLang === 'km' ? 'អានទាំងអស់' : 'Mark all read' }}
                  </button>
                </div>

                <!-- Tabs: All vs Unread -->
                <div class="flex items-center gap-1 px-3 py-2 bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800 text-xs">
                  <button
                    type="button"
                    @click="notifTab = 'all'; playClickSound()"
                    :class="[notifTab === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800', 'px-2.5 py-1 rounded-lg font-medium transition-all cursor-pointer']"
                  >
                    {{ currentLang === 'km' ? 'ទាំងអស់' : 'All' }} ({{ notifications.length }})
                  </button>
                  <button
                    type="button"
                    @click="notifTab = 'unread'; playClickSound()"
                    :class="[notifTab === 'unread' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800', 'px-2.5 py-1 rounded-lg font-medium transition-all cursor-pointer']"
                  >
                    {{ currentLang === 'km' ? 'មិនទាន់អាន' : 'Unread' }} ({{ unreadNotificationsCount }})
                  </button>
                </div>

                <div class="max-h-80 overflow-y-auto custom-scrollbar divide-y divide-slate-100 dark:divide-slate-800">
                  <div
                    v-for="notif in filteredNotifications"
                    :key="notif.id"
                    @click="handleNotificationItemClick(notif)"
                    :class="[
                      notif.read ? 'bg-slate-50/40 dark:bg-slate-900/40 opacity-75' : 'bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/60',
                      'p-3.5 transition-colors cursor-pointer block select-none'
                    ]"
                  >
                    <div class="flex items-start gap-3">
                      <div :class="[
                        notif.type === 'payment' ? 'bg-emerald-500/20 text-emerald-500 dark:text-emerald-400' :
                        notif.type === 'user' ? 'bg-indigo-500/20 text-indigo-500 dark:text-indigo-400' :
                        notif.type === 'support' ? 'bg-amber-500/20 text-amber-500 dark:text-amber-400' : 'bg-cyan-500/20 text-cyan-500 dark:text-cyan-400',
                        'p-2 rounded-xl shrink-0 mt-0.5'
                      ]">
                        <svg v-if="notif.type === 'payment'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <svg v-else-if="notif.type === 'user'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        <svg v-else-if="notif.type === 'support'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                          <p :class="[notif.read ? 'text-slate-600 dark:text-slate-400 font-medium' : 'text-slate-900 dark:text-slate-100 font-bold', 'text-xs truncate']">{{ getNotifTitle(notif) }}</p>
                          <span class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0 ml-1">{{ getNotifTime(notif) }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">{{ getNotifDesc(notif) }}</p>
                      </div>
                      <span v-if="!notif.read" class="w-2 h-2 rounded-full bg-indigo-500 shrink-0 mt-1.5"></span>
                    </div>
                  </div>

                  <div v-if="filteredNotifications.length === 0" class="py-8 text-center">
                    <svg class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ currentLang === 'km' ? 'មិនមានការជូនដំណឹងទេ' : 'No notifications' }}</p>
                  </div>
                </div>

                <div class="p-2 border-t border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/60 text-center">
                  <Link
                    href="/admin/notifications"
                    @click="isNotificationOpen = false; playClickSound()"
                    class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 transition-colors"
                  >
                    {{ currentLang === 'km' ? 'មើលការជូនដំណឹងទាំងអស់ →' : 'View All Notifications →' }}
                  </Link>
                </div>
              </div>
            </transition>
          </div>

          <!-- Admin Profile Dropdown Menu (Avatar + Name ⌄) -->
          <div class="relative ml-1 nav-dropdown-scope">
            <button
              @click.stop="toggleDropdown('profile')"
              type="button"
              class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-800/80 transition-all focus:outline-none cursor-pointer"
            >
              <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-blue-600 text-sm font-bold shadow-xs shrink-0 overflow-hidden ring-1 ring-slate-700">
                <img
                  v-if="user.avatar"
                  :src="user.avatar"
                  alt="Admin Profile"
                  class="w-full h-full object-cover"
                />
                <span v-else>👤</span>
              </div>
              <div class="hidden md:flex items-center gap-1 text-left">
                <span class="text-xs font-bold text-white">{{ user.name || 'Admin' }}</span>
                <span class="text-slate-400 text-[10px] transition-transform duration-200" :class="isProfileOpen ? 'rotate-180 text-blue-400' : ''">⌄</span>
              </div>
            </button>

            <!-- Profile Account Dropdown with Multi-Account Switcher -->
            <ProfileAccountDropdown
              :user="user"
              :is-open="isProfileOpen"
              role="admin"
              @close="isProfileOpen = false"
              @logout="logout"
              @trigger-avatar-upload="triggerAvatarUpload(); isProfileOpen = false"
              @manage-profile="isAdminProfileModalOpen = true; isProfileOpen = false"
            />
          </div>

        </div>

      </div>
    </header>

    <!-- Global Command Palette Modal (Search Dialog) -->
    <div v-if="isSearchOpen" class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24 px-4">
      <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isSearchOpen = false"></div>
      <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-2xl shadow-2xl overflow-hidden z-50">
        <!-- Search Input Header -->
        <div class="flex items-center px-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/90">
          <svg class="w-5 h-5 text-indigo-500 dark:text-indigo-400 shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input
            v-model="searchQuery"
            @input="performGlobalSearch(searchQuery)"
            @keydown.enter="handleSearchEnter"
            @keydown.esc="isSearchOpen = false"
            type="text"
            :placeholder="currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា និស្សិត គ្រូ ឬម៉ូឌុលប្រព័ន្ធ...' : 'Search courses, students, teachers, or modules...'"
            class="w-full bg-transparent py-4 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none"
            autofocus
          />
          <button @click="isSearchOpen = false" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-lg cursor-pointer">
            <kbd class="px-2 py-0.5 text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded border border-slate-200 dark:border-slate-700">ESC</kbd>
          </button>
        </div>

        <!-- Search Results List -->
        <div class="max-h-96 overflow-y-auto p-3 custom-scrollbar">
          <!-- Loading State -->
          <div v-if="isSearching" class="p-8 text-center text-slate-400 text-xs flex items-center justify-center gap-2">
            <svg class="animate-spin w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span>{{ currentLang === 'km' ? 'កំពុងស្វែងរកក្នុងមូលដ្ឋានទិន្នន័យ E-LMS...' : 'Searching E-LMS database...' }}</span>
          </div>

          <div v-else-if="filteredSearchResults.length === 0 && (!globalSearchResults.courses?.length && !globalSearchResults.students?.length && !globalSearchResults.teachers?.length)" class="p-8 text-center text-slate-500 dark:text-slate-400 text-sm">
            {{ currentLang === 'km' ? 'មិនរកឃើញទិន្នន័យស្វែងរកឡើយ' : 'No results found' }}
          </div>

          <div v-else class="space-y-3">
            <!-- Global Courses Section -->
            <div v-if="globalSearchResults.courses && globalSearchResults.courses.length > 0">
              <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
                <span>📚</span> {{ currentLang === 'km' ? 'វគ្គសិក្សា' : 'Courses' }}
              </div>
              <div class="space-y-1">
                <Link
                  v-for="c in globalSearchResults.courses"
                  :key="'cmd-c-' + c.id"
                  :href="c.url"
                  @click="isSearchOpen = false"
                  class="group flex items-center justify-between p-2.5 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/30 border border-transparent hover:border-blue-200 dark:hover:border-blue-800 transition-all cursor-pointer"
                >
                  <div class="min-w-0">
                    <h4 class="text-xs font-semibold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-300 truncate">{{ c.title }}</h4>
                    <p class="text-[10px] text-slate-400 font-mono">{{ c.code }}</p>
                  </div>
                  <span class="text-xs text-slate-400 group-hover:translate-x-0.5 transition-transform shrink-0">→</span>
                </Link>
              </div>
            </div>

            <!-- Global Students Section -->
            <div v-if="globalSearchResults.students && globalSearchResults.students.length > 0">
              <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                <span>🎓</span> {{ currentLang === 'km' ? 'និស្សិត / សិស្ស' : 'Students' }}
              </div>
              <div class="space-y-1">
                <Link
                  v-for="s in globalSearchResults.students"
                  :key="'cmd-s-' + s.id"
                  :href="s.url"
                  @click="isSearchOpen = false"
                  class="group flex items-center justify-between p-2.5 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/30 border border-transparent hover:border-emerald-200 dark:hover:border-emerald-800 transition-all cursor-pointer"
                >
                  <div class="min-w-0">
                    <h4 class="text-xs font-semibold text-slate-800 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-300 truncate">{{ s.name }}</h4>
                    <p class="text-[10px] text-slate-400 truncate">{{ s.subtitle }}</p>
                  </div>
                  <span class="text-xs text-slate-400 group-hover:translate-x-0.5 transition-transform shrink-0">→</span>
                </Link>
              </div>
            </div>

            <!-- Global Teachers Section -->
            <div v-if="globalSearchResults.teachers && globalSearchResults.teachers.length > 0">
              <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                <span>👨‍🏫</span> {{ currentLang === 'km' ? 'សាស្ត្រាចារ្យ / គ្រូបង្រៀន' : 'Teachers' }}
              </div>
              <div class="space-y-1">
                <Link
                  v-for="t in globalSearchResults.teachers"
                  :key="'cmd-t-' + t.id"
                  :href="t.url"
                  @click="isSearchOpen = false"
                  class="group flex items-center justify-between p-2.5 rounded-xl hover:bg-amber-50 dark:hover:bg-amber-900/30 border border-transparent hover:border-amber-200 dark:hover:border-amber-800 transition-all cursor-pointer"
                >
                  <div class="min-w-0">
                    <h4 class="text-xs font-semibold text-slate-800 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-300 truncate">{{ t.name }}</h4>
                    <p class="text-[10px] text-slate-400 truncate">{{ t.subtitle }}</p>
                  </div>
                  <span class="text-xs text-slate-400 group-hover:translate-x-0.5 transition-transform shrink-0">→</span>
                </Link>
              </div>
            </div>

            <!-- System Modules and Navigation -->
            <div v-if="filteredSearchResults.length > 0">
              <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                <span>⚡</span> {{ currentLang === 'km' ? 'ទំព័រ & មុខងារគ្រប់គ្រងប្រព័ន្ធ' : 'Pages & Admin Features' }}
              </div>
              <div class="space-y-1">
                <Link
                  v-for="res in filteredSearchResults"
                  :key="res.href"
                  :href="res.href"
                  @click="isSearchOpen = false"
                  class="group flex items-center justify-between p-2.5 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-600/15 border border-transparent hover:border-indigo-200 dark:hover:border-indigo-500/30 transition-all cursor-pointer"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-500/20 text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-300 transition-colors shrink-0 flex items-center justify-center">
                      <img v-if="res.iconUrl" :src="res.iconUrl" :alt="res.name" class="w-4 h-4 object-contain shrink-0" />
                      <svg v-else-if="res.icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" :d="res.icon"/></svg>
                    </div>
                    <div class="min-w-0">
                      <h4 class="text-xs font-semibold text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-200 transition-colors truncate">{{ res.name }}</h4>
                      <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wide truncate">{{ res.category }}</p>
                    </div>
                  </div>
                  <svg class="w-4 h-4 text-slate-400 dark:text-slate-600 group-hover:text-indigo-500 dark:group-hover:text-indigo-400 transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Command Palette Footer -->
        <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-900/90 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
          <div class="flex items-center gap-3">
            <span><kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-[10px]">Ctrl K</kbd> {{ currentLang === 'km' ? 'ដើម្បីបើក/បិទ' : 'to toggle' }}</span>
            <span><kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-[10px]">ESC</kbd> {{ currentLang === 'km' ? 'ដើម្បីចាកចេញ' : 'to close' }}</span>
            <span><kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded text-[10px]">Enter</kbd> {{ currentLang === 'km' ? 'ជ្រើសរើស' : 'to select' }}</span>
          </div>
          <span class="text-indigo-600 dark:text-indigo-400 font-medium">E-LMS Command Palette</span>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <main :class="[isSidebarCollapsed ? 'is-collapsed' : '', 'elms-main-anim pt-16 sm:pt-20 pb-12']">
      <div class="px-4 sm:px-6 lg:px-8">
        <!-- Page Header (Suppressed on Admin Dashboard to Match Clean Reference UI) -->
        <header class="mb-5" v-if="($slots.header || title) && $page.url !== '/admin/dashboard'">
          <slot name="header">
            <h2 class="text-2xl font-black leading-relaxed bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-800 dark:from-white dark:via-slate-100 dark:to-slate-300 bg-clip-text text-transparent sm:truncate sm:text-3xl tracking-normal font-sans">{{ title }}</h2>
          </slot>
        </header>

        <!-- Page Content Slot -->
        <slot />
      </div>
    </main>

    <!-- Hidden Avatar File Input -->
    <input
      ref="avatarInput"
      type="file"
      accept="image/*"
      class="hidden"
      @change="handleAvatarChange"
    />

    <!-- Admin Personal Profile & Security Modal -->
    <AdminProfileModal
      :show="isAdminProfileModalOpen"
      :user="user"
      @close="isAdminProfileModalOpen = false"
      @trigger-avatar-upload="triggerAvatarUpload"
    />

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

<style>
/* ==========================================================================
   E-LMS 60FPS Ultra-Smooth Hardware-Accelerated Sidebar & Layout Animations
   ========================================================================== */

/* Desktop Sidebar Container */
.elms-sidebar-anim {
  width: 15rem !important; /* 240px */
  transition: width 300ms cubic-bezier(0.4, 0, 0.2, 1) !important;
  will-change: width;
}

.elms-sidebar-anim.is-collapsed {
  width: 5rem !important; /* 80px */
}

/* Main Content Area */
@media (min-width: 768px) {
  .elms-main-anim {
    padding-left: 15rem !important; /* 240px */
    transition: padding-left 300ms cubic-bezier(0.4, 0, 0.2, 1) !important;
    will-change: padding-left;
  }

  .elms-main-anim.is-collapsed {
    padding-left: 5rem !important; /* 80px */
  }
}

/* Nav Item Buttons */
.elms-nav-item {
  transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease, width 300ms cubic-bezier(0.4, 0, 0.2, 1) !important;
  will-change: background-color, width;
}

/* Nav Item Labels */
.elms-nav-label-container {
  transition: max-width 300ms cubic-bezier(0.4, 0, 0.2, 1), opacity 200ms cubic-bezier(0.4, 0, 0.2, 1) !important;
  will-change: max-width, opacity;
}
</style>

