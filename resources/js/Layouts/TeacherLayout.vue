<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { i18n } from '@/Services/i18n'
import { useTheme } from '@/composables/useTheme'
import GlobalToast from '@/Components/GlobalToast.vue'
import OfficialVerifiedBadge from '@/Components/OfficialVerifiedBadge.vue'

const { isDark, toggleTheme } = useTheme()

const logoUrl = '/images/logo.png'
const actionBtnIcon = '/images/actions/action-button.svg'
const onlineIconUrl = '/images/nav/online.svg'
const offlineIconUrl = '/images/nav/offline.svg'

const props = defineProps<{ title?: string }>()

const page = usePage<any>()
const user = computed(() => page.props.auth?.user || {})

const teacherDisplayName = computed(() => {
  const raw = user.value?.name || 'Teacher Sophea'
  if (raw.toLowerCase() === 'sophea teacher') return 'Teacher Sophea'
  return raw
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

const currentLang = computed(() => i18n.locale.value || 'km')

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

// Dynamic Breadcrumb & Page Title
const currentBreadcrumb = computed(() => {
  const url = page.url
  if (url.startsWith('/teacher/dashboard')) return ['Teacher', 'Dashboard']
  if (url.startsWith('/teacher/courses')) return ['Teacher', 'My Courses']
  if (url.startsWith('/teacher/content')) return ['Teacher', 'Content Delivery Module']
  if (url.startsWith('/teacher/quizzes') || url.startsWith('/teacher/assessment')) return ['Teacher', 'Quiz & Assessment Module']
  if (url.startsWith('/teacher/students')) return ['Teacher', 'Students']
  if (url.startsWith('/teacher/progress')) return ['Teacher', 'Progress Tracking Module']
  if (url.startsWith('/teacher/reports')) return ['Teacher', 'Reports Module']
  if (url.startsWith('/teacher/discussions') || url.startsWith('/teacher/discussion')) return ['Teacher', 'Discussion & Announcements']
  if (url.startsWith('/teacher/calendar')) return ['Teacher', 'Calendar']
  if (url.startsWith('/teacher/earnings')) return ['Teacher', 'Earnings & ABA']
  if (url.startsWith('/teacher/notifications')) return ['Teacher', 'Notifications']
  if (url.startsWith('/teacher/profile')) return ['Teacher', 'My Profile']
  return ['Teacher', 'Teacher Panel']
})

const pageTitle = computed(() => {
  if (props.title) return props.title
  const crumb = currentBreadcrumb.value
  return crumb.length > 1 ? crumb[crumb.length - 1] : 'Teacher Dashboard'
})

// Quick Actions Dropdown items for Teacher
const quickActions = [
  { name: 'បង្កើតវគ្គសិក្សា (Add Course)', href: '/teacher/courses/create', iconUrl: '/images/actions/add-course.svg' },
  { name: 'បង្កើតកាលវិភាគ (Add Schedule)', href: '/teacher/calendar?tab=schedule', iconUrl: '/images/actions/announcement.svg' },
  { name: 'កំណត់ Due Date (Set Deadline)', href: '/teacher/calendar?tab=deadlines', iconUrl: '/images/nav/sub/policies.svg' },
  { name: 'បង្កើត Quiz (Create Quiz)', href: '/teacher/quizzes?tab=quizzes', iconUrl: '/images/nav/quiz.svg' },
  { name: 'ផ្ញើសារប្រកាស (Announcement)', href: '/teacher/discussions?tab=announcements', iconUrl: '/images/actions/announcement.svg' },
]

// Notifications Drawer Data
const notifications = ref([
  {
    id: 1,
    title: 'សិស្សបានផ្ញើកិច្ចការ Assignment 1',
    desc: 'Chan Dara បានដាក់កិច្ចការ First C Program សម្រាប់ពិនិត្យ',
    time: '5 នាទីមុន',
    type: 'assignment',
    read: false,
    link: '/teacher/quizzes?tab=assignments'
  },
  {
    id: 2,
    title: 'សំណួរថ្មីក្នុងសភាពិភាក្សា Q&A',
    desc: 'Sok Dara បានសួរសំណួរអំពី Pointers & Memory Management',
    time: '30 នាទីមុន',
    type: 'question',
    read: false,
    link: '/teacher/discussions?tab=questions'
  },
  {
    id: 3,
    title: 'ការផ្ទៀងផ្ទាត់ការបង់ប្រាក់ ABA',
    desc: 'សិស្សបានទូទាត់ប្រាក់សម្រាប់វគ្គ C Programming Basics',
    time: '2 ម៉ោងមុន',
    type: 'payment',
    read: true,
    link: '/teacher/earnings'
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


// Navigation Structure Definition
interface NavSubItem {
  name: string
  khName: string
  href: string
  iconUrl?: string
  icon?: string
  tag?: string
  indent?: boolean
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
}

const expandedModules = ref<Record<string, boolean>>({
  courses: false,
  content: false,
  assessment: false,
  students: false,
  progress: false,
  reports: false,
  discussion: false,
  calendar: false,
  earnings: false,
  notifications: false,
  profile: false,
})

const teacherNav: NavItem[] = [
  {
    key: 'dashboard',
    name: 'Dashboard',
    khName: 'Dashboard',
    href: '/teacher/dashboard',
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
      { name: 'All Courses', khName: 'All Courses', href: '/teacher/courses', iconUrl: '/images/nav/courses/all-courses.svg' },
      { name: 'Create New Course', khName: 'Create New Course', href: '/teacher/courses/create', iconUrl: '/images/nav/courses/create-course.svg' },
      { name: 'Draft Courses', khName: 'Draft Courses', href: '/teacher/courses?tab=drafts', iconUrl: '/images/nav/courses/draft-courses.svg' },
      { name: 'Pending Approval', khName: 'Pending Approval', href: '/teacher/courses?tab=pending', iconUrl: '/images/nav/courses/pending-approval.svg' },
      { name: 'Published Courses', khName: 'Published Courses', href: '/teacher/courses?tab=published', iconUrl: '/images/nav/courses/published-courses.svg' },
      { name: 'Course Settings', khName: 'Course Settings', href: '/teacher/courses?tab=settings', iconUrl: '/images/nav/courses/course-settings.svg' },
    ]
  },
  {
    key: 'content',
    name: 'Content Delivery',
    khName: 'Content Delivery',
    iconUrl: '/images/nav/content.svg',
    icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
    children: [
      { name: 'Videos', khName: 'Videos', href: '/teacher/content?tab=videos', iconUrl: '/images/nav/sub/teacher-led.svg' },
      { name: 'PDFs', khName: 'PDFs', href: '/teacher/content?tab=pdfs', iconUrl: '/images/nav/sub/policies.svg' },
      { name: 'Slides', khName: 'Slides', href: '/teacher/content?tab=slides', iconUrl: '/images/nav/sub/self-study.svg' },
      { name: 'Modules & Chapters', khName: 'Modules & Chapters', href: '/teacher/content?tab=modules', iconUrl: '/images/nav/sub/all-courses.svg' },
      { name: 'Notes & Downloads', khName: 'Notes & Downloads', href: '/teacher/content?tab=notes', iconUrl: '/images/nav/sub/subjects.svg' },
      { name: 'AI-Assisted Content', khName: 'AI-Assisted Content', href: '/teacher/content?tab=ai-content', iconUrl: '/images/nav/sub/overview.svg', tag: '🤖' },
      { name: 'Practice Lab', khName: 'Practice Lab', href: '/teacher/content?tab=coding-lab', iconUrl: '/images/nav/sub/import-export.svg', tag: '💻' },
    ]
  },
  {
    key: 'assessment',
    name: 'Quiz & Assessment',
    khName: 'Quiz & Assessment',
    iconUrl: '/images/nav/quiz.svg',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    children: [
      { name: 'Question Bank', khName: 'Question Bank', href: '/teacher/assessment?tab=questions', iconUrl: '/images/nav/sub/overview.svg' },
      { name: 'Quiz', khName: 'Quiz', href: '/teacher/assessment?tab=quizzes', iconUrl: '/images/nav/quiz.svg' },
      { name: 'Pre-Test', khName: 'Pre-Test', href: '/teacher/assessment?tab=pretest', iconUrl: '/images/nav/sub/semesters.svg' },
      { name: 'Practice Quiz', khName: 'Practice Quiz', href: '/teacher/assessment?tab=practice', iconUrl: '/images/nav/sub/subjects.svg' },
      { name: 'Post-Test', khName: 'Post-Test', href: '/teacher/assessment?tab=posttest', iconUrl: '/images/nav/sub/roles.svg' },
      { name: 'Assignment', khName: 'Assignment', href: '/teacher/assessment?tab=assignments', iconUrl: '/images/nav/sub/teacher-assignments.svg' },
      { name: 'Coding Assessment', khName: 'Coding Assessment', href: '/teacher/assessment?tab=coding', iconUrl: '/images/nav/sub/import-export.svg', tag: '💻' },
      { name: 'Quiz Results', khName: 'Quiz Results', href: '/teacher/assessment?tab=results', iconUrl: '/images/nav/sub/history.svg' },
    ]
  },
  {
    key: 'students',
    name: 'Students',
    khName: 'Students',
    iconUrl: '/images/nav/users.svg',
    icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    children: [
      { name: 'Student List', khName: 'Student List', href: '/teacher/students?tab=list', iconUrl: '/images/nav/sub/students.svg' },
      { name: 'Progress', khName: 'Progress', href: '/teacher/students?tab=progress', iconUrl: '/images/nav/progress.svg' },
      { name: 'Completion', khName: 'Completion', href: '/teacher/students?tab=completion', iconUrl: '/images/nav/sub/roles.svg' },
      { name: 'Quiz Scores', khName: 'Quiz Scores', href: '/teacher/students?tab=scores', iconUrl: '/images/nav/analytics.svg' },
      { name: 'Assignment Scores', khName: 'Assignment Scores', href: '/teacher/students?tab=assignment-scores', iconUrl: '/images/nav/sub/teacher-assignments.svg' },
      { name: 'Attendance', khName: 'Attendance', href: '/teacher/students?tab=attendance', iconUrl: '/images/nav/sub/history.svg' },
      { name: 'Feedback', khName: 'Feedback', href: '/teacher/students?tab=feedback', iconUrl: '/images/nav/sub/overview.svg' },
    ]
  },
  {
    key: 'progress',
    name: 'Progress Tracking',
    khName: 'Progress Tracking',
    iconUrl: '/images/nav/progress.svg',
    icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    children: [
      { name: 'Module Completion', khName: 'Module Completion', href: '/teacher/progress?tab=modules', iconUrl: '/images/nav/sub/all-courses.svg' },
      { name: 'Learning Time', khName: 'Learning Time', href: '/teacher/progress?tab=time', iconUrl: '/images/nav/sub/history.svg' },
      { name: 'Weekly Progress', khName: 'Weekly Progress', href: '/teacher/progress?tab=weekly', iconUrl: '/images/nav/analytics.svg' },
      { name: 'Course Progress', khName: 'Course Progress', href: '/teacher/progress?tab=course-progress', iconUrl: '/images/nav/progress.svg' },
      { name: 'Weak Topics', khName: 'Weak Topics', href: '/teacher/progress?tab=weak-topics', iconUrl: '/images/nav/sub/failed.svg', tag: '🤖' },
      { name: 'At-Risk Students', khName: 'At-Risk Students', href: '/teacher/progress?tab=at-risk', iconUrl: '/images/nav/sub/failed.svg', tag: '🤖' },
    ]
  },
  {
    key: 'reports',
    name: 'Reports',
    khName: 'Reports',
    iconUrl: '/images/nav/analytics.svg',
    icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    href: '/teacher/reports'
  },
  {
    key: 'discussion',
    name: 'Discussion & Announcements',
    khName: 'Discussion & Announcements',
    iconUrl: '/images/nav/discussions.svg',
    icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    href: '/teacher/discussions'
  },
  {
    key: 'calendar',
    name: 'Calendar',
    khName: 'Calendar',
    iconUrl: '/images/actions/announcement.svg',
    icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    href: '/teacher/calendar'
  },
  {
    key: 'earnings',
    name: 'Earnings & ABA',
    khName: 'Earnings & ABA',
    iconUrl: '/images/nav/payment.svg',
    icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    href: '/teacher/earnings'
  },
  {
    key: 'notifications',
    name: 'Notifications',
    khName: 'Notifications',
    iconUrl: '/images/nav/notification.svg',
    icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    href: '/teacher/notifications'
  },
  {
    key: 'profile',
    name: 'My Profile',
    khName: 'My Profile',
    iconUrl: '/images/nav/sub/students.svg',
    icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    href: '/teacher/profile'
  }
]

// Global Command Search Links (Dynamically populated from teacherNav)
const searchableLinks = computed(() => {
  const links: Array<{ name: string; category: string; href: string; iconUrl?: string }> = []
  teacherNav.forEach(item => {
    if (item.href) {
      links.push({
        name: `${item.name} (${item.khName})`,
        category: 'Main Module',
        href: item.href,
        iconUrl: item.iconUrl
      })
    }
    if (item.children) {
      item.children.forEach(sub => {
        links.push({
          name: `${sub.name} (${sub.khName})`,
          category: item.name,
          href: sub.href,
          iconUrl: sub.iconUrl || item.iconUrl
        })
      })
    }
  })
  return links
})

const filteredSearchLinks = computed(() => {
  if (!searchQuery.value.trim()) return searchableLinks.value
  const q = searchQuery.value.toLowerCase()
  return searchableLinks.value.filter(l => l.name.toLowerCase().includes(q) || l.category.toLowerCase().includes(q))
})

const isSubActive = (subHref: string) => {
  const currentUrl = page.url
  const [subPath, subQueryString] = subHref.split('?')
  const [currentPath, currentQueryString] = currentUrl.split('?')

  let pathMatches = currentPath === subPath
  if (!pathMatches) {
    if ((subPath.startsWith('/teacher/assessment') || subPath.startsWith('/teacher/quizzes')) &&
        (currentPath.startsWith('/teacher/assessment') || currentPath.startsWith('/teacher/quizzes'))) {
      pathMatches = true
    } else if ((subPath.startsWith('/teacher/discussion') || subPath.startsWith('/teacher/discussions')) &&
               (currentPath.startsWith('/teacher/discussion') || currentPath.startsWith('/teacher/discussions'))) {
      pathMatches = true
    }
  }

  if (!pathMatches) return false

  const currentParams = new URLSearchParams(currentQueryString || '')

  if (subQueryString) {
    const subParams = new URLSearchParams(subQueryString)
    let allMatch = true
    subParams.forEach((value, key) => {
      if (currentParams.get(key) !== value) {
        allMatch = false
      }
    })
    if (!allMatch) return false

    if (!subParams.has('sub') && currentParams.has('sub')) {
      return false
    }
    return true
  }

  if (currentQueryString && (currentParams.has('tab') || currentParams.has('status'))) {
    return false
  }

  return true
}

const isChildActive = (children?: NavSubItem[]) => {
  if (!children) return false
  return children.some(child => isSubActive(child.href))
}

const toggleModule = (key: string) => {
  expandedModules.value[key] = !expandedModules.value[key]
}

watch(
  () => page.url,
  () => {
    teacherNav.forEach(item => {
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

const logout = () => {
  router.post('/logout')
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
</script>

<template>
  <Head :title="pageTitle" />
  <GlobalToast />
  <div class="min-h-screen bg-slate-50 dark:bg-[#0B0F19] text-slate-800 dark:text-slate-200 selection:bg-indigo-500/30 transition-colors duration-200">
    <!-- Desktop Sidebar (Fixed Positioning exactly like AdminLayout) -->
    <aside :class="[isSidebarCollapsed ? 'w-20 overflow-visible' : 'w-72', 'fixed inset-y-0 left-0 z-50 hidden flex-col bg-white/95 dark:bg-slate-900/90 backdrop-blur-xl border-r border-slate-200/90 dark:border-slate-800 lg:flex transition-all duration-300 shadow-sm dark:shadow-none']">
      <!-- Sidebar Header -->
      <div
        :class="[
          isSidebarCollapsed ? 'justify-center px-2' : 'justify-between px-4',
          'relative flex h-16 shrink-0 items-center border-b border-slate-200/90 dark:border-slate-800 transition-all duration-300 group/sidebar-header'
        ]"
      >
        <!-- Logo & Title Container -->
        <div
          @click="isSidebarCollapsed && toggleSidebarCollapse()"
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
            <span class="font-bold text-sm text-slate-900 dark:text-white block">E-LMS Teacher</span>
            <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase whitespace-nowrap">TEACHER PANEL</p>
          </div>
        </div>

        <!-- Sidebar Collapse / Expand Toggle Button (Admin-style Icon Button) -->
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

      <!-- Navigation Tree -->
      <nav
        :class="[
          isSidebarCollapsed ? 'px-0 overflow-visible' : 'px-3 custom-scrollbar overflow-y-auto',
          'flex flex-1 flex-col py-4'
        ]"
      >
        <ul role="list" class="space-y-1 w-full">
          <li
            v-for="item in teacherNav"
            :key="item.key"
            :class="isSidebarCollapsed ? 'relative group/flyout flex justify-center w-full' : 'relative'"
          >
            <!-- Direct Link (No Children) -->
            <Link
              v-if="!item.children || item.children.length === 0"
              :href="item.href!"
              :title="isSidebarCollapsed ? (currentLang === 'km' ? item.khName : item.name) : undefined"
              :class="[
                $page.url.startsWith(item.href!) 
                  ? 'bg-indigo-50 dark:bg-[#1E1B4B] text-indigo-600 dark:text-[#818CF8] border border-indigo-200/90 dark:border-[#818CF8]/30 font-bold shadow-xs dark:shadow-md dark:shadow-indigo-950/50' 
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-[#F1F5F9] hover:bg-slate-100 dark:hover:bg-slate-800/70 border border-transparent font-medium',
                isSidebarCollapsed ? 'justify-center px-0 w-10 h-10 mx-auto' : 'px-3 w-full justify-between',
                'group/direct flex items-center rounded-xl py-2.5 text-xs transition-all duration-200 outline-none focus:outline-none focus:ring-0 select-none cursor-pointer relative'
              ]"
            >
              <div :class="[isSidebarCollapsed ? 'justify-center w-full' : '', 'flex items-center gap-x-3 truncate']">
                <div class="relative flex items-center justify-center shrink-0">
                  <img 
                    v-if="item.iconUrl"
                    :src="item.iconUrl" 
                    :alt="item.name"
                    @error="onIconError"
                    class="w-5 h-5 object-contain shrink-0 filter drop-shadow-sm transition-transform duration-200 group-hover/direct:scale-110"
                  />
                  <svg 
                    :class="[
                      $page.url.startsWith(item.href!) ? 'text-indigo-600 dark:text-[#818CF8]' : 'text-slate-400 dark:text-slate-500 group-hover/direct:text-slate-700 dark:group-hover/direct:text-slate-300',
                      item.iconUrl ? 'hidden' : '',
                      'h-5 w-5 shrink-0 transition-colors'
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

              <!-- Badge for direct item (e.g. Notifications 🔴 5) -->
              <span 
                v-if="item.badge && !isSidebarCollapsed" 
                :class="[item.badge.colorClass, 'px-2 py-0.5 text-[10px] font-bold rounded-full shrink-0 ml-1.5']"
              >
                {{ item.badge.text }}
              </span>
              <span 
                v-else-if="item.badge && isSidebarCollapsed" 
                class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-900 animate-pulse"
              ></span>

              <!-- Floating Tooltip for Direct Link when Sidebar is Collapsed -->
              <div
                v-if="isSidebarCollapsed"
                class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 px-3 py-1.5 bg-slate-900 dark:bg-slate-800 text-white text-xs font-semibold rounded-xl shadow-2xl border border-slate-700/80 pointer-events-none whitespace-nowrap opacity-0 group-hover/flyout:opacity-100 group-hover/direct:opacity-100 transition-all duration-150 z-50 flex items-center gap-1.5"
              >
                <div class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900 dark:bg-slate-800 border-l border-b border-slate-700/80 rotate-45 pointer-events-none"></div>
                <span>{{ currentLang === 'km' ? item.khName : item.name }}</span>
              </div>
            </Link>

            <!-- Collapsible Module with Submenu -->
            <div v-else class="space-y-1 w-full flex flex-col items-center">
              <button
                @click="toggleModule(item.key!)"
                type="button"
                :title="isSidebarCollapsed ? (currentLang === 'km' ? item.khName : item.name) : undefined"
                :class="[
                  isChildActive(item.children) 
                    ? 'bg-indigo-50 dark:bg-[#1E1B4B] text-indigo-600 dark:text-[#818CF8] border border-indigo-200/90 dark:border-[#818CF8]/30 font-bold shadow-xs dark:shadow-md dark:shadow-indigo-950/50' 
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-[#F1F5F9] hover:bg-slate-100 dark:hover:bg-slate-800/70 border border-transparent font-medium',
                  isSidebarCollapsed ? 'justify-center px-0 w-10 h-10 mx-auto' : 'px-3 w-full justify-between',
                  'group flex items-center rounded-xl py-2.5 text-xs transition-all duration-200 outline-none focus:outline-none focus:ring-0 select-none cursor-pointer'
                ]"
              >
                <div :class="[isSidebarCollapsed ? 'justify-center w-full' : '', 'flex items-center gap-x-3 truncate']">
                  <div class="relative flex items-center justify-center shrink-0">
                    <img 
                      v-if="item.iconUrl"
                      :src="item.iconUrl" 
                      :alt="item.name"
                      @error="onIconError"
                      class="w-5 h-5 object-contain shrink-0 filter drop-shadow-sm transition-transform duration-200 group-hover:scale-110"
                    />
                    <svg 
                      :class="[
                        isChildActive(item.children) ? 'text-indigo-600 dark:text-[#818CF8]' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-300',
                        item.iconUrl ? 'hidden' : '',
                        'h-5 w-5 shrink-0 transition-colors'
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

                <div v-show="!isSidebarCollapsed" class="flex items-center gap-1.5">
                  <!-- Module Badge (e.g. Students 🔴 3, Assessment 🟠 8, Earnings 🟡 12) -->
                  <span 
                    v-if="item.badge" 
                    :class="[item.badge.colorClass, 'px-2 py-0.5 text-[10px] font-bold rounded-full shrink-0']"
                  >
                    {{ item.badge.text }}
                  </span>
                  <svg
                    :class="[
                      expandedModules[item.key!] ? 'rotate-180 text-indigo-600 dark:text-[#818CF8]' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-300',
                      'w-4 h-4 transition-transform duration-200 shrink-0 ml-1'
                    ]"
                    fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </button>

              <!-- Submenu Items Tree -->
              <div
                v-show="!isSidebarCollapsed && expandedModules[item.key!]"
                class="relative ml-5 pl-3.5 space-y-1 my-1.5 transition-all duration-300 border-l border-slate-200 dark:border-slate-800/80 w-[calc(100%-1.25rem)]"
              >
                <div
                  v-for="sub in item.children"
                  :key="sub.href"
                  class="relative"
                >
                  <Link
                    :href="sub.href"
                    :class="[
                      isSubActive(sub.href)
                        ? 'bg-indigo-50/80 dark:bg-[#1E1B4B]/80 text-indigo-600 dark:text-[#818CF8] font-bold border border-indigo-200/90 dark:border-[#818CF8]/30 shadow-xs'
                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-[#F1F5F9] hover:bg-slate-100 dark:hover:bg-slate-800/60 border border-transparent font-medium',
                      sub.indent ? 'ml-3 border-l border-slate-300 dark:border-slate-700/80 pl-2 text-[11px]' : '',
                      'flex items-center justify-between rounded-xl px-3 py-2 text-xs transition-all duration-150 outline-none focus:outline-none focus:ring-0 select-none group'
                    ]"
                  >
                    <div class="flex items-center gap-x-2.5 min-w-0">
                      <img
                        v-if="sub.iconUrl"
                        :src="sub.iconUrl"
                        :alt="sub.name"
                        @error="onIconError"
                        class="w-4 h-4 object-contain shrink-0 filter drop-shadow-xs"
                      />
                      <span class="truncate">{{ currentLang === 'km' ? sub.khName : sub.name }}</span>
                    </div>

                    <!-- Submenu Item Tag (🤖 or 💻) -->
                    <span v-if="sub.tag" class="text-xs shrink-0 ml-1 font-emoji">{{ sub.tag }}</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Flyout Popover for Collapsed Sidebar Mode (Has Children) -->
            <div
              v-if="isSidebarCollapsed && item.children && item.children.length > 0"
              class="absolute left-full top-0 ml-3.5 w-64 opacity-0 pointer-events-none group-hover/flyout:opacity-100 group-hover/flyout:pointer-events-auto transition-all duration-200 ease-out translate-x-1 group-hover/flyout:translate-x-0 z-50"
            >
              <div class="relative bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200 dark:border-slate-700/80 rounded-2xl p-3 shadow-2xl ring-1 ring-slate-200/50 dark:ring-slate-800/80">
                <div class="absolute -left-1.5 top-3.5 w-3 h-3 bg-white dark:bg-slate-900 border-l border-b border-slate-200 dark:border-slate-700/80 rotate-45 z-10 pointer-events-none"></div>

                <div class="relative z-20 flex items-center justify-between px-2 py-1.5 mb-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                  <div class="flex items-center gap-2 min-w-0">
                    <img v-if="item.iconUrl" :src="item.iconUrl" :alt="item.name" class="w-4 h-4 object-contain shrink-0" />
                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ currentLang === 'km' ? item.khName : item.name }}</span>
                  </div>
                  <span v-if="item.badge" :class="[item.badge.colorClass, 'px-2 py-0.5 text-[9px] font-bold rounded-full']">
                    {{ item.badge.text }}
                  </span>
                </div>

                <div class="relative z-20 space-y-1 max-h-[70vh] overflow-y-auto custom-scrollbar pr-1">
                  <Link
                    v-for="sub in item.children"
                    :key="sub.href"
                    :href="sub.href"
                    :class="[
                      isSubActive(sub.href)
                        ? 'bg-indigo-50 dark:bg-indigo-500/15 text-indigo-600 dark:text-[#818CF8] font-bold border border-indigo-200 dark:border-[#818CF8]/30'
                        : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800/70 border border-transparent',
                      'flex items-center justify-between px-2.5 py-2 rounded-xl text-xs transition-all duration-150'
                    ]"
                  >
                    <div class="flex items-center gap-2.5 min-w-0">
                      <img v-if="sub.iconUrl" :src="sub.iconUrl" :alt="sub.name" class="w-4 h-4 object-contain shrink-0" />
                      <span class="truncate">{{ currentLang === 'km' ? sub.khName : sub.name }}</span>
                    </div>
                    <span v-if="sub.tag" class="text-xs shrink-0 font-emoji ml-1">{{ sub.tag }}</span>
                  </Link>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </nav>

      <!-- Sidebar Footer User Card -->
      <div :class="[isSidebarCollapsed ? 'px-0 py-3' : 'p-3.5', 'mt-auto border-t border-slate-200/90 dark:border-slate-800 bg-slate-50 dark:bg-[#0B0F19] transition-colors']">
        <div :class="[isSidebarCollapsed ? 'flex-col justify-center items-center gap-2.5 w-full' : 'justify-between gap-3', 'flex items-center']">
          <div class="flex items-center gap-3 min-w-0 flex-1">
            <div class="relative shrink-0">
              <img
                v-if="user.avatar"
                :src="user.avatar"
                class="w-9 h-9 rounded-full object-cover border border-purple-500/40 shadow-md ring-2 ring-purple-500/20"
              />
              <div
                v-else
                class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white font-bold flex items-center justify-center text-sm shadow-md ring-2 ring-purple-500/20"
              >
                {{ user.name ? user.name.charAt(0) : 'S' }}
              </div>
              <span :class="[isOnline ? 'bg-emerald-500 shadow-emerald-500/50' : 'bg-rose-500 shadow-rose-500/50', 'absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-slate-900 transition-colors duration-200 shadow-xs']"></span>
            </div>

            <div v-show="!isSidebarCollapsed" class="flex-1 min-w-0">
              <p class="font-bold text-slate-900 dark:text-slate-100 text-xs truncate leading-snug">{{ teacherDisplayName }}</p>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate leading-snug">{{ user.email || 'teacher@elms.com' }}</p>
            </div>
          </div>

          <button
            v-show="!isSidebarCollapsed"
            @click="logout"
            title="Log Out (⎋)"
            class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-slate-200/70 dark:hover:bg-slate-800/80 rounded-lg transition-all shrink-0 flex items-center justify-center cursor-pointer group"
          >
            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Mobile Drawer Sidebar (Sliding Drawer on Mobile) -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 z-50 lg:hidden backdrop-blur-sm transition-opacity"
    ></div>

    <aside
      :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'fixed inset-y-0 left-0 z-50 w-72 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col lg:hidden transition-transform duration-300 ease-in-out shadow-2xl'
      ]"
    >
      <div class="h-16 px-4 flex items-center justify-between border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3">
          <img :src="logoUrl" alt="E-LMS Logo" class="w-8 h-8 rounded-full object-cover ring-2 ring-indigo-500/30" />
          <div>
            <span class="font-bold text-sm text-slate-900 dark:text-white block">E-LMS Teacher</span>
            <span class="text-[9px] text-slate-400 uppercase tracking-wide block">TEACHER PANEL</span>
          </div>
        </div>
        <button @click="sidebarOpen = false" class="p-1 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
        <template v-for="item in teacherNav" :key="item.key">
          <Link
            v-if="!item.children || item.children.length === 0"
            :href="item.href!"
            @click="sidebarOpen = false"
            :class="[
              $page.url.startsWith(item.href!) ? 'bg-indigo-50 dark:bg-[#1E1B4B] text-indigo-600 dark:text-[#818CF8] font-bold border border-indigo-200/90 dark:border-[#818CF8]/30 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-[#F1F5F9] hover:bg-slate-100 dark:hover:bg-slate-800/60',
              'flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-colors'
            ]"
          >
            <div class="flex items-center gap-3 truncate">
              <img v-if="item.iconUrl" :src="item.iconUrl" class="w-5 h-5 object-contain shrink-0" />
              <span class="truncate">{{ currentLang === 'km' ? item.khName : item.name }}</span>
            </div>
            <span v-if="item.badge" :class="[item.badge.colorClass, 'px-2 py-0.5 text-[10px] font-bold rounded-full shrink-0']">
              {{ item.badge.text }}
            </span>
          </Link>
          <div v-else class="space-y-1">
            <button
              @click="toggleModule(item.key!)"
              class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium"
            >
              <div class="flex items-center gap-3 truncate">
                <img v-if="item.iconUrl" :src="item.iconUrl" class="w-5 h-5 object-contain shrink-0" />
                <span class="truncate">{{ currentLang === 'km' ? item.khName : item.name }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span v-if="item.badge" :class="[item.badge.colorClass, 'px-2 py-0.5 text-[10px] font-bold rounded-full shrink-0']">
                  {{ item.badge.text }}
                </span>
                <svg :class="[expandedModules[item.key!] ? 'rotate-180 text-indigo-600 dark:text-indigo-400' : '', 'w-4 h-4 transition-transform text-slate-400 dark:text-slate-500']" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </div>
            </button>
            <div v-show="expandedModules[item.key!]" class="pl-4 ml-4 space-y-1.5 my-1.5 border-l border-slate-200 dark:border-slate-800/80">
              <Link
                v-for="sub in item.children"
                :key="sub.href"
                :href="sub.href"
                @click="sidebarOpen = false"
                :class="[
                  isSubActive(sub.href) ? 'bg-indigo-50/80 dark:bg-[#1E1B4B]/80 text-indigo-600 dark:text-[#818CF8] font-bold border border-indigo-200/90 dark:border-[#818CF8]/30 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800/60 border border-transparent font-medium',
                  sub.indent ? 'ml-3 border-l border-slate-300 dark:border-slate-700/80 pl-2 text-[11px]' : '',
                  'flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all duration-150 focus:outline-none'
                ]"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <img v-if="sub.iconUrl" :src="sub.iconUrl" :alt="sub.name" class="w-4 h-4 object-contain shrink-0" />
                  <span class="truncate">{{ currentLang === 'km' ? sub.khName : sub.name }}</span>
                </div>
                <span v-if="sub.tag" class="text-xs shrink-0 ml-1 font-emoji">{{ sub.tag }}</span>
              </Link>
            </div>
          </div>
        </template>
      </nav>

      <!-- Mobile Footer -->
      <div class="p-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-[#0B0F19] flex items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white font-bold flex items-center justify-center text-xs">
            {{ user.name ? user.name.charAt(0) : 'S' }}
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-1 min-w-0">
              <p class="font-bold text-slate-900 dark:text-slate-100 text-xs truncate">{{ teacherDisplayName }}</p>
              <OfficialVerifiedBadge :role="user.role || 'teacher'" size="xs" />
            </div>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ user.email || 'teacher@elms.com' }}</p>
          </div>
        </div>
        <button @click="logout" class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-500 dark:hover:text-red-400 rounded-lg">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </button>
      </div>
    </aside>

    <!-- STICKY TOP NAVBAR (Dynamically padded to account for fixed sidebar) -->
    <header :class="[isSidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72', 'sticky top-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-b border-slate-200/90 dark:border-slate-800/80 transition-all duration-300 shadow-xs']">
      <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8 gap-4">
        
        <!-- Left Side: Mobile Menu Toggle, Breadcrumb & Global Search Bar -->
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

          <!-- Breadcrumb (Clean SaaS Modern Style) -->
          <div class="hidden sm:flex items-center gap-2 text-xs font-medium truncate">
            <span class="text-slate-500 dark:text-slate-400 font-normal hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
              {{ currentLang === 'km' ? 'គ្រូបង្រៀន' : 'Teacher' }}
            </span>
            <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="font-bold text-indigo-600 dark:text-indigo-400 truncate">
              {{ currentBreadcrumb[1] }}
            </span>
          </div>

          <!-- Glassmorphic Search Bar -->
          <div class="relative hidden md:block">
            <button
              @click="toggleDropdown('search')"
              type="button"
              class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/60 hover:bg-slate-200/80 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-700/60 hover:border-indigo-500/40 text-xs transition-all w-56 lg:w-72 justify-between shadow-xs group"
            >
              <div class="flex items-center gap-2 truncate min-w-0 flex-1">
                <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400 shrink-0 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="truncate text-slate-500 dark:text-slate-400 group-hover:text-slate-800 dark:group-hover:text-slate-300 font-sans">
                  {{ currentLang === 'km' ? 'ស្វែងរក...' : 'Search...' }}
                </span>
              </div>
              <kbd class="hidden lg:inline-flex items-center shrink-0 whitespace-nowrap px-1.5 py-0.5 text-[10px] font-mono font-semibold text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700/60 rounded shadow-xs leading-none">ctrl k</kbd>
            </button>
          </div>
        </div>

        <!-- Right Side: Online Status, Language Switcher, Theme Toggle, Fullscreen, Notifications, User Profile -->
        <div class="flex items-center gap-1.5 sm:gap-2">
          <!-- Mobile Search Trigger -->
          <button
            @click="toggleDropdown('search')"
            type="button"
            class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg md:hidden focus:outline-none transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </button>

          <!-- Online Status Badge Pill (🟢 Online ˅) -->
          <div 
            class="relative" 
            @mouseenter="isStatusOpen = true" 
            @mouseleave="isStatusOpen = false"
          >
            <button
              @click="toggleDropdown('status')"
              type="button"
              class="h-8 px-2.5 bg-slate-100 hover:bg-slate-200/80 dark:bg-slate-800/60 dark:hover:bg-slate-800 border border-slate-200 hover:border-slate-300 dark:border-slate-700/60 dark:hover:border-slate-600 rounded-lg transition-all flex items-center gap-2 cursor-pointer group select-none focus:outline-none"
            >
              <div class="relative flex items-center justify-center shrink-0">
                <img 
                  :src="isOnline ? onlineIconUrl : offlineIconUrl" 
                  :alt="isOnline ? 'Online' : 'Offline'"
                  class="w-4 h-4 object-contain shrink-0 group-hover:scale-110 transition-transform filter drop-shadow-xs" 
                />
                <span 
                  :class="[isOnline ? 'bg-emerald-500 shadow-emerald-500/50' : 'bg-rose-500 shadow-rose-500/50']"
                  class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full ring-2 ring-white dark:ring-slate-900 shadow-xs animate-pulse"
                ></span>
              </div>
              <span 
                :class="[isOnline ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400']"
                class="hidden sm:inline text-xs font-semibold font-sans tracking-wide"
              >
                {{ currentLang === 'km' ? (isOnline ? 'អនឡាញ' : 'អូហ្វឡាញ') : (isOnline ? 'Online' : 'Offline') }}
              </span>
              <svg 
                :class="[isStatusOpen ? 'rotate-180 text-slate-800 dark:text-slate-200' : 'text-slate-400']" 
                class="w-3.5 h-3.5 transition-transform duration-200" 
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <!-- Status Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="transform opacity-0 scale-95 -translate-y-1"
              enter-to-class="transform opacity-100 scale-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="transform opacity-100 scale-100 translate-y-0"
              leave-to-class="transform opacity-0 scale-95 -translate-y-1"
            >
              <div
                v-if="isStatusOpen"
                class="absolute right-0 mt-1.5 w-56 rounded-xl bg-white dark:bg-slate-800/95 backdrop-blur-xl border border-slate-200 dark:border-slate-700/80 shadow-2xl py-1.5 z-50 overflow-hidden"
              >
                <div class="px-3.5 py-1.5 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                  <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    {{ currentLang === 'km' ? 'ស្ថានភាពប្រព័ន្ធ' : 'System Status' }}
                  </span>
                  <span 
                    :class="[isOnline ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border-emerald-500/30' : 'bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-300 border-rose-500/30']"
                    class="px-2 py-0.5 text-[9px] font-bold rounded-full border"
                  >
                    {{ currentLang === 'km' ? (isOnline ? 'បានភ្ជាប់' : 'បានផ្ដាច់') : (isOnline ? 'Connected' : 'Disconnected') }}
                  </span>
                </div>

                <div class="p-1 space-y-1 text-xs">
                  <button
                    @click="setStatusMode(true)"
                    :class="[isOnline ? 'bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-semibold border-emerald-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/60 border-transparent']"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-lg border transition-all cursor-pointer text-left"
                  >
                    <div class="flex items-center gap-2.5">
                      <img :src="onlineIconUrl" alt="Online" class="w-4 h-4 object-contain" />
                      <div>
                        <p class="font-medium text-xs text-slate-900 dark:text-white">{{ currentLang === 'km' ? 'អនឡាញ' : 'Online' }}</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ currentLang === 'km' ? 'ភ្ជាប់ប្រព័ន្ធធម្មតា' : 'Connected to server' }}</p>
                      </div>
                    </div>
                  </button>

                  <button
                    @click="setStatusMode(false)"
                    :class="[!isOnline ? 'bg-rose-500/10 dark:bg-rose-500/15 text-rose-700 dark:text-rose-300 font-semibold border-rose-500/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/60 border-transparent']"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-lg border transition-all cursor-pointer text-left"
                  >
                    <div class="flex items-center gap-2.5">
                      <img :src="offlineIconUrl" alt="Offline" class="w-4 h-4 object-contain" />
                      <div>
                        <p class="font-medium text-xs text-slate-900 dark:text-white">{{ currentLang === 'km' ? 'អូហ្វឡាញ' : 'Offline' }}</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ currentLang === 'km' ? 'ដាច់ការតភ្ជាប់' : 'Disconnected' }}</p>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Language Switcher Pill (🇰🇭 KM ˅) -->
          <div 
            class="relative" 
            @mouseenter="isLangOpen = true" 
            @mouseleave="isLangOpen = false"
          >
            <button
              @click="isLangOpen = !isLangOpen"
              type="button"
              class="h-8 px-2.5 bg-slate-100 hover:bg-slate-200/80 dark:bg-slate-800/60 dark:hover:bg-slate-800 border border-slate-200 hover:border-slate-300 dark:border-slate-700/60 dark:hover:border-slate-600 rounded-lg transition-all flex items-center gap-2 cursor-pointer group select-none focus:outline-none"
            >
              <img 
                :src="languages.find(l => l.code === currentLang)?.flagUrl || '/images/flags/km.svg'" 
                :alt="currentLang"
                class="w-4 h-4 rounded-full object-cover shrink-0 shadow-xs" 
              />
              <span class="uppercase text-xs font-semibold text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white font-sans tracking-wide">
                {{ currentLang === 'km' ? 'KM' : 'EN' }}
              </span>
              <svg 
                :class="[isLangOpen ? 'rotate-180 text-slate-800 dark:text-slate-200' : 'text-slate-400']" 
                class="w-3.5 h-3.5 transition-transform duration-200" 
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <!-- Language Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="transform opacity-0 scale-95 -translate-y-1"
              enter-to-class="transform opacity-100 scale-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="transform opacity-100 scale-100 translate-y-0"
              leave-to-class="transform opacity-0 scale-95 -translate-y-1"
            >
              <div
                v-if="isLangOpen"
                class="absolute right-0 mt-1.5 w-40 rounded-xl bg-white dark:bg-slate-800/95 backdrop-blur-xl border border-slate-200 dark:border-slate-700/80 shadow-2xl py-1.5 z-50 overflow-hidden"
              >
                <button
                  v-for="lang in languages"
                  :key="lang.code"
                  @click="selectLanguage(lang.code)"
                  :class="[
                    currentLang === lang.code ? 'bg-indigo-50 dark:bg-indigo-600/20 text-indigo-600 dark:text-indigo-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/60'
                  ]"
                  class="w-full flex items-center justify-between px-3.5 py-2 text-xs transition-colors rounded-none cursor-pointer focus:outline-none"
                >
                  <span class="flex items-center gap-2.5">
                    <img :src="lang.flagUrl" :alt="lang.name" class="w-4 h-4 rounded-full object-cover shrink-0" />
                    <span>{{ lang.name }}</span>
                  </span>
                  <svg v-if="currentLang === lang.code" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                </button>
              </div>
            </Transition>
          </div>

          <!-- Theme Switcher Pill (Matching Sign In page style) -->
          <button
            type="button"
            @click="toggleTheme"
            class="group px-3 py-1.5 rounded-full bg-white/95 dark:bg-slate-800/90 backdrop-blur-md hover:bg-white dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 transition-all duration-200 border border-slate-300/90 dark:border-slate-700/60 shadow-xs flex items-center gap-2 text-xs font-semibold cursor-pointer select-none hover:scale-105 active:scale-95 focus:outline-none"
            :title="isDark ? 'ប្ដូរទៅ Light Mode / Switch to Light Mode' : 'ប្ដូរទៅ Dark Mode / Switch to Dark Mode'"
          >
            <div class="relative w-4 h-4 flex items-center justify-center">
              <i :class="['pi text-xs transition-transform duration-300 group-hover:rotate-45', isDark ? 'pi-sun text-amber-500' : 'pi-moon text-indigo-500']"></i>
            </div>
            <span class="text-[11px] font-bold font-sans">
              {{ isDark ? 'Light Mode' : 'Dark Mode' }}
            </span>
          </button>

          <!-- Fullscreen Button (⛶) -->
          <button
            @click="toggleFullscreen"
            type="button"
            class="p-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100/80 hover:bg-slate-200 dark:bg-transparent dark:hover:bg-slate-800 border border-slate-200/80 dark:border-transparent dark:hover:border-slate-700/60 rounded-xl transition-all focus:outline-none cursor-pointer"
            :title="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'"
          >
            <svg v-if="!isFullscreen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 9L4 4m0 0l5 0m-5 0l0 5m11 0l5-5m0 0l-5 0m5 0l0 5m-5 11l5 5m0 0l-5 0m5 0l0-5m-11 0l-5 5m0 0l5 0m-5 0l0-5" />
            </svg>
          </button>

          <!-- Notifications Bell Dropdown (🔔) -->
          <div class="relative">
            <button
              @click="toggleDropdown('notification')"
              type="button"
              class="relative p-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100/80 hover:bg-slate-200 dark:bg-transparent dark:hover:bg-slate-800 border border-slate-200/80 dark:border-transparent dark:hover:border-slate-700/60 rounded-xl transition-all focus:outline-none cursor-pointer"
              title="Notifications"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span v-if="unreadNotificationsCount > 0" class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-900 animate-pulse"></span>
            </button>

            <!-- Notifications Dropdown -->
            <div
              v-if="isNotificationOpen"
              class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 shadow-2xl z-50 overflow-hidden"
            >
              <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700/80 flex items-center justify-between bg-slate-50 dark:bg-slate-800/80">
                <div class="flex items-center gap-2">
                  <h3 class="text-xs font-bold text-slate-900 dark:text-white">ការជូនដំណឹង (Notifications)</h3>
                  <span v-if="unreadNotificationsCount > 0" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300">
                    {{ unreadNotificationsCount }} ថ្មី
                  </span>
                </div>
                <button
                  v-if="unreadNotificationsCount > 0"
                  @click="markAllAsRead"
                  class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors font-medium cursor-pointer"
                >
                  អានទាំងអស់
                </button>
              </div>

              <div class="max-h-80 overflow-y-auto custom-scrollbar divide-y divide-slate-100 dark:divide-slate-700/40">
                <div
                  v-for="notif in notifications"
                  :key="notif.id"
                  @click="markNotificationRead(notif.id)"
                  :class="[notif.read ? 'bg-slate-50/50 dark:bg-slate-800/30 opacity-70' : 'bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/50', 'p-3.5 transition-colors cursor-pointer block']"
                >
                  <Link :href="notif.link" @click="isNotificationOpen = false">
                    <div class="flex items-start gap-3">
                      <div :class="[
                        notif.type === 'payment' ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' :
                        notif.type === 'assignment' ? 'bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400' : 'bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400',
                        'p-2 rounded-xl shrink-0 mt-0.5'
                      ]">
                        <svg v-if="notif.type === 'payment'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <svg v-else-if="notif.type === 'assignment'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                          <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ notif.title }}</p>
                          <span class="text-[10px] text-slate-400 shrink-0 ml-1">{{ notif.time }}</span>
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5">{{ notif.desc }}</p>
                      </div>
                    </div>
                  </Link>
                </div>
              </div>

              <div class="p-2 border-t border-slate-100 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-900/60 text-center">
                <Link
                  href="/teacher/notifications"
                  @click="isNotificationOpen = false"
                  class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                >
                  មើលការជូនដំណឹងទាំងអស់ →
                </Link>
              </div>
            </div>
          </div>

          <!-- User Profile Dropdown Avatar (👤 Teacher Sophea ˅) -->
          <div class="relative ml-1">
            <button
              @click="toggleDropdown('profile')"
              type="button"
              class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-transparent hover:border-slate-200 dark:hover:border-slate-700/60 transition-all focus:outline-none group cursor-pointer"
            >
              <div class="relative shrink-0">
                <img
                  :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(teacherDisplayName)}&background=6366f1&color=fff`"
                  alt="Teacher Profile"
                  class="w-7 h-7 rounded-full border border-slate-200 dark:border-slate-700 object-cover shadow-xs group-hover:border-indigo-500/50 transition-colors"
                />
                <span :class="[isOnline ? 'bg-emerald-500 shadow-emerald-500/50' : 'bg-rose-500 shadow-rose-500/50', 'absolute bottom-0 right-0 w-2 h-2 rounded-full ring-2 ring-white dark:ring-slate-900 transition-colors duration-200 shadow-xs']"></span>
              </div>
              <div class="hidden md:flex items-center gap-1.5 min-w-0 max-w-[240px]">
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-300 transition-colors truncate">
                  {{ teacherDisplayName }}
                </span>
                <OfficialVerifiedBadge :role="user.role || 'teacher'" size="xs" />
              </div>
              <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200 transition-transform" :class="isProfileOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <!-- Profile Dropdown Menu Popup -->
            <div
              v-if="isProfileOpen"
              class="absolute right-0 mt-2 w-64 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 shadow-2xl py-2 z-50 animate-in fade-in duration-150"
            >
              <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700/80 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30">
                <div class="flex items-center gap-3">
                  <img
                    :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(teacherDisplayName)}&background=6366f1&color=fff`"
                    alt="Teacher Avatar"
                    class="w-10 h-10 rounded-full border border-slate-200 dark:border-slate-700 object-cover shadow-md"
                  />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1 min-w-0">
                      <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ teacherDisplayName }}</h4>
                      <OfficialVerifiedBadge :role="user.role || 'teacher'" size="xs" />
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ user.email || 'teacher@elms.com' }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 border border-indigo-500/20 dark:border-indigo-500/30">
                      Instructor • IT
                    </span>
                  </div>
                </div>
              </div>

              <div class="py-1.5 border-b border-slate-100 dark:border-slate-700/60 text-xs space-y-0.5">
                <Link
                  href="/teacher/profile?tab=info"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group"
                >
                  <svg class="w-4 h-4 text-sky-500 dark:text-sky-400 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  <span>Personal Information</span>
                </Link>

                <Link
                  href="/teacher/profile?tab=teaching"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group"
                >
                  <svg class="w-4 h-4 text-purple-500 dark:text-purple-400 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  <span>Teaching Profile</span>
                </Link>

                <Link
                  href="/teacher/profile?tab=password"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group"
                >
                  <svg class="w-4 h-4 text-teal-500 dark:text-teal-400 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                  <span>Change Password</span>
                </Link>

                <Link
                  href="/teacher/profile?tab=settings"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group"
                >
                  <svg class="w-4 h-4 text-slate-400 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <span>Notification Settings</span>
                </Link>

                <Link
                  href="/teacher/profile?tab=history"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group"
                >
                  <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>Teaching History</span>
                </Link>
              </div>

              <div class="py-1 border-b border-slate-100 dark:border-slate-700/60 text-xs">
                <Link
                  href="/teacher/earnings"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group"
                >
                  <svg class="w-4 h-4 text-emerald-500 dark:text-emerald-400 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <span>Earnings & ABA (ចំណូល)</span>
                </Link>
              </div>

              <div class="pt-1">
                <button
                  @click="logout"
                  class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors font-bold text-left cursor-pointer group"
                >
                  <svg class="w-4 h-4 text-red-500 dark:text-red-400 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                  <span>Log Out (ចាកចេញ)</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Global Command Search Modal (Ctrl + K Palette) -->
    <div
      v-if="isSearchOpen"
      class="fixed inset-0 z-50 flex items-start justify-center pt-16 px-4 bg-slate-950/60 backdrop-blur-md"
      @click.self="isSearchOpen = false"
    >
      <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-xl w-full border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden transform transition-all">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center gap-3">
          <svg class="w-5 h-5 text-indigo-500 dark:text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ស្វែងរកក្នុងប្រព័ន្ធ Teacher Panel..."
            class="w-full bg-transparent border-none text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-0 text-sm font-medium placeholder-slate-400 dark:placeholder-slate-500"
            autoFocus
          />
          <kbd class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] font-mono border border-slate-200 dark:border-slate-700">ESC</kbd>
        </div>

        <div class="max-h-80 overflow-y-auto p-2 space-y-1">
          <Link
            v-for="link in filteredSearchLinks"
            :key="link.name"
            :href="link.href"
            @click="isSearchOpen = false"
            class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors text-xs group"
          >
            <div class="flex items-center gap-3">
              <img v-if="link.iconUrl" :src="link.iconUrl" class="w-4 h-4 object-contain" />
              <span class="font-semibold text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-300">{{ link.name }}</span>
            </div>
            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] border border-slate-200 dark:border-slate-700">{{ link.category }}</span>
          </Link>

          <div v-if="filteredSearchLinks.length === 0" class="p-6 text-center text-slate-500 text-xs">
            មិនមានលទ្ធផលសម្រាប់ "{{ searchQuery }}"
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT AREA (Dynamically Padded for Fixed Sidebar Layout exactly like AdminLayout) -->
    <main :class="[isSidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72', 'pt-3.5 sm:pt-4 pb-10 transition-all duration-300']">
      <div class="px-4 sm:px-6 lg:px-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #475569;
}
</style>
