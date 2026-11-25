<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

const page = usePage<any>()
const { t, currentLang } = useLanguage()
const authUser = computed<Record<string, any>>(() => page.props.auth?.user || {})

interface CompletedCourseItem {
  id: number
  enrollmentId?: number
  slug: string
  title: string
  titleKh?: string
  category: string
  categoryKh?: string
  status: 'completed'
  progress: number
  progress_percentage?: number
  teacher: {
    name: string
    name_kh?: string
    avatar?: string | null
    role?: string
  }
  completedDate: string
  completedDateTimestamp: number
  chaptersCount: number
  lessonsCount: number
  studyTime: string
  studyTimeMinutes: number
  studyTimeSeconds?: number
  quizScore: number
  hasQuizScore?: boolean
  grade?: string | null
  hasCertificate: boolean
  certificateDbId?: number | null
  certificateId?: string | null
  verificationCode?: string | null
  certIssueDate?: string | null
  certIssuedTimestamp?: number
  certificateDownloadHref?: string | null
  certificateVerifyHref?: string | null
  missingCertReason?: string | null
  missingCertReasonKh?: string | null
  medalType: 'gold' | 'silver' | 'bronze'
  illustrationType: 'web' | 'database' | 'python' | 'uiux'
  thumbnail?: string | null
  overviewHref: string
  reviewHref?: string
}

interface LatestCertificateData {
  id: number
  courseId?: number | null
  title: string
  titleKh?: string
  certificateId: string
  verificationCode?: string | null
  certIssueDate?: string | null
  teacherName?: string
  teacherNameKh?: string
  score?: number | null
  grade?: string | null
  downloadHref: string
  verifyHref?: string
}

interface AchievementBadge {
  id: string
  key: string
  title: string
  title_km: string
  unlocked: boolean
  progress: string
  progress_km: string
  theme: 'emerald' | 'blue' | 'orange' | 'purple' | string
}

const props = defineProps<{
  enrollments?: CompletedCourseItem[]
  all_enrollments?: CompletedCourseItem[]
  counts?: {
    all: number
    certificates: number
    no_certificate: number
  }
  stats?: {
    totalCompleted: number
    withCertificateCount: number
    noCertificateCount: number
    certPercent: number
    noCertPercent: number
    totalStudyMinutes: number
    totalStudySeconds: number
    totalStudyTimeFormatted: string
    averageQuizScore: number
    completedQuizzesCount: number
  }
  latestCertificate?: LatestCertificateData | null
  achievements?: AchievementBadge[]
  filters?: {
    status?: string
    sort?: string
    q?: string
    view?: string
  }
}>()

const activeTab = ref<'all' | 'certificates' | 'no_certificate'>(
  props.filters?.status === 'certificates' || props.filters?.status === 'no_certificate'
    ? props.filters.status
    : 'all'
)
const searchQuery = ref(props.filters?.q || '')
const selectedSort = ref(props.filters?.sort || 'newest')

const resolveInitialViewMode = (): 'grid' | 'list' => {
  if (typeof window !== 'undefined') {
    const urlView = new URLSearchParams(window.location.search).get('view')
    if (urlView === 'grid' || urlView === 'list') {
      try { localStorage.setItem('elms_courses_view', urlView) } catch (e) {}
      return urlView
    }
  }
  if (props.filters?.view === 'grid' || props.filters?.view === 'list') {
    if (typeof window !== 'undefined') {
      try { localStorage.setItem('elms_courses_view', props.filters.view) } catch (e) {}
    }
    return props.filters.view
  }
  if (typeof window !== 'undefined') {
    try {
      const savedLocal = localStorage.getItem('elms_courses_view')
      if (savedLocal === 'grid' || savedLocal === 'list') return savedLocal
    } catch (e) {}
  }
  const savedPref = (page.props.auth?.user?.preferences as any)?.courses_view
  if (savedPref === 'grid' || savedPref === 'list') return savedPref
  return 'grid'
}

const viewMode = ref<'grid' | 'list'>(resolveInitialViewMode())
const thumbErrors = ref<Record<number, boolean>>({})
const searchInputRef = ref<HTMLInputElement | null>(null)
let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(() => props.filters?.view, (v) => {
  if (typeof window !== 'undefined') {
    const urlView = new URLSearchParams(window.location.search).get('view')
    if (urlView === 'grid' || urlView === 'list') {
      if (urlView !== viewMode.value) viewMode.value = urlView
      try { localStorage.setItem('elms_courses_view', urlView) } catch (e) {}
      return
    }
  }
  if ((v === 'grid' || v === 'list') && v !== viewMode.value) {
    viewMode.value = v
    if (typeof window !== 'undefined') {
      try { localStorage.setItem('elms_courses_view', v) } catch (e) {}
    }
  }
})

watch(() => props.filters?.status, (s) => {
  if (s === 'all' || s === 'certificates' || s === 'no_certificate') {
    activeTab.value = s
  }
})

watch(() => props.filters?.sort, (s) => {
  if (s) selectedSort.value = s
})

watch(() => props.filters?.q, (q) => {
  if (typeof q === 'string' && q !== searchQuery.value) {
    searchQuery.value = q
  }
})

const triggerServerFilter = () => {
  if (typeof window === 'undefined') return
  try {
    const url = new URL(window.location.href)
    url.searchParams.set('status', activeTab.value)
    url.searchParams.set('sort', selectedSort.value)
    if (searchQuery.value.trim()) {
      url.searchParams.set('q', searchQuery.value.trim())
    } else {
      url.searchParams.delete('q')
    }
    url.searchParams.set('view', viewMode.value)
    window.history.replaceState(window.history.state, '', url.toString())
  } catch (e) {}
}

const changeTab = (tab: 'all' | 'certificates' | 'no_certificate') => {
  if (activeTab.value === tab) return
  activeTab.value = tab
  triggerServerFilter()
}

const onSearchInput = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    triggerServerFilter()
  }, 120)
}

const clearSearch = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  searchQuery.value = ''
  triggerServerFilter()
}

const onSortChange = () => {
  triggerServerFilter()
}

const resetFilters = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  activeTab.value = 'all'
  searchQuery.value = ''
  selectedSort.value = 'newest'
  triggerServerFilter()
}

const setViewMode = (mode: 'grid' | 'list') => {
  if (viewMode.value === mode) return
  viewMode.value = mode

  if (typeof window !== 'undefined') {
    try {
      localStorage.setItem('elms_courses_view', mode)
    } catch (e) {}
    try {
      const url = new URL(window.location.href)
      url.searchParams.set('view', mode)
      window.history.replaceState(window.history.state, '', url.toString())
    } catch (e) {}
  }

  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    fetch('/student/preferences', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ courses_view: mode }),
    }).catch(() => {})
  } catch (e) {}
}

// Modals
const isCertModalOpen = ref(false)
const isNoCertModalOpen = ref(false)
const selectedCertCourse = ref<CompletedCourseItem | null>(null)

// Base source of all completed courses from Database props
const allCourses = computed<CompletedCourseItem[]>(() => {
  return Array.isArray(props.all_enrollments)
    ? props.all_enrollments
    : (Array.isArray(props.enrollments) ? props.enrollments : [])
})

// Real Completed Courses dynamically filtered & sorted client-side (0ms, zero loading)
const courses = computed<CompletedCourseItem[]>(() => {
  let list = [...allCourses.value]

  if (activeTab.value === 'certificates') {
    list = list.filter(c => !!c.hasCertificate)
  } else if (activeTab.value === 'no_certificate') {
    list = list.filter(c => !c.hasCertificate)
  }

  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    list = list.filter(c =>
      (c.title || '').toLowerCase().includes(q) ||
      (c.titleKh || '').toLowerCase().includes(q) ||
      (c.category || '').toLowerCase().includes(q) ||
      (c.categoryKh || '').toLowerCase().includes(q) ||
      (c.teacher?.name || '').toLowerCase().includes(q) ||
      (c.teacher?.name_kh || '').toLowerCase().includes(q) ||
      (c.slug || '').toLowerCase().includes(q) ||
      (c.certificateId || '').toLowerCase().includes(q)
    )
  }

  if (selectedSort.value === 'oldest') {
    list.sort((a, b) => (a.completedDateTimestamp || 0) - (b.completedDateTimestamp || 0))
  } else if (selectedSort.value === 'highest_score') {
    list.sort((a, b) => (b.quizScore || 0) - (a.quizScore || 0))
  } else if (selectedSort.value === 'name') {
    list.sort((a, b) => (a.title || '').localeCompare(b.title || '', undefined, { numeric: true, sensitivity: 'base' }))
  } else if (selectedSort.value === 'most_time') {
    list.sort((a, b) => (b.studyTimeSeconds || 0) - (a.studyTimeSeconds || 0))
  } else {
    list.sort((a, b) => (b.completedDateTimestamp || 0) - (a.completedDateTimestamp || 0))
  }

  return list
})

// Localization helpers
const localizedCourseTitle = (course: { title: string; titleKh?: string }): string => {
  if (currentLang.value === 'km') {
    return course.titleKh || course.title
  }
  return course.title
}

const localizedCourseCategory = (course: CompletedCourseItem): string => {
  if (currentLang.value === 'km') {
    return course.categoryKh || (course.category === 'Academic Studies' ? 'ការសិក្សាទូទៅ' : course.category)
  }
  return course.category
}

const localizedTeacherName = (teacher?: { name: string; name_kh?: string } | null): string => {
  if (!teacher) return ''
  if (currentLang.value === 'km') {
    return teacher.name_kh || teacher.name
  }
  return teacher.name
}

const localizedMissingCertReason = (course: CompletedCourseItem): string => {
  if (currentLang.value === 'km') {
    return course.missingCertReasonKh || course.missingCertReason || 'វិញ្ញាបនបត្រមិនទាន់ត្រូវបានចេញជូននៅឡើយទេ។'
  }
  return course.missingCertReason || 'Certificate has not been issued yet.'
}

// Computed Counts & Sidebar Analytics (server totals take priority so filtering tabs/search preserves sidebar metrics)
const totalCount = computed(() => props.counts?.all ?? courses.value.length)
const certEarnedCount = computed(() => props.counts?.certificates ?? courses.value.filter(c => c.hasCertificate).length)
const noCertCount = computed(() => props.counts?.no_certificate ?? courses.value.filter(c => !c.hasCertificate).length)

const certPercent = computed(() => {
  if (typeof props.stats?.certPercent === 'number') return props.stats.certPercent
  return totalCount.value > 0 ? Math.round((certEarnedCount.value / totalCount.value) * 100) : 0
})

const noCertPercent = computed(() => {
  if (typeof props.stats?.noCertPercent === 'number') return props.stats.noCertPercent
  return totalCount.value > 0 ? Math.max(0, 100 - certPercent.value) : 0
})

const totalStudyTimeFormatted = computed(() => {
  if (props.stats?.totalStudyTimeFormatted) return props.stats.totalStudyTimeFormatted
  const totalMinutes = courses.value.reduce((sum, c) => sum + (c.studyTimeMinutes || 0), 0)
  const hours = Math.floor(totalMinutes / 60)
  const mins = totalMinutes % 60
  return `${hours}h ${String(mins).padStart(2, '0')}m`
})

const completedQuizzesCount = computed(() => {
  if (typeof props.stats?.completedQuizzesCount === 'number') return props.stats.completedQuizzesCount
  return courses.value.filter(c => c.hasQuizScore).length
})

const averageQuizScore = computed(() => {
  if (typeof props.stats?.averageQuizScore === 'number') return props.stats.averageQuizScore
  const scored = courses.value.filter(c => c.hasQuizScore)
  if (scored.length === 0) return 0
  const sum = scored.reduce((total, c) => total + (c.quizScore || 0), 0)
  return Math.round(sum / scored.length)
})

const quizScoreLabel = computed(() => {
  if (completedQuizzesCount.value === 0) {
    return currentLang.value === 'km' ? 'មិនទាន់មានកម្រងសំណួរបានបញ្ចប់' : 'No quizzes completed yet'
  }
  const score = averageQuizScore.value
  if (score >= 90) return currentLang.value === 'km' ? 'លទ្ធផលល្អប្រសើរ' : 'Excellent Performance'
  if (score >= 80) return currentLang.value === 'km' ? 'លទ្ធផលល្អណាស់' : 'Very Good Performance'
  if (score >= 70) return currentLang.value === 'km' ? 'លទ្ធផលល្អ' : 'Good Performance'
  if (score >= 60) return currentLang.value === 'km' ? 'ត្រូវការកែលម្អ' : 'Needs Improvement'
  return currentLang.value === 'km' ? 'ត្រូវការយកចិត្តទុកដាក់' : 'Needs Attention'
})

// Latest Certificate strictly from real issued/valid certificates (never fallback to non-certificate course)
const latestCertData = computed<LatestCertificateData | null>(() => {
  if (props.latestCertificate !== undefined) {
    return props.latestCertificate
  }
  const certCourse = courses.value
    .filter(c => c.hasCertificate)
    .sort((a, b) => (b.certIssuedTimestamp || b.completedDateTimestamp) - (a.certIssuedTimestamp || a.completedDateTimestamp))[0]
  if (!certCourse || !certCourse.certificateId) return null
  return {
    id: certCourse.certificateDbId || certCourse.id,
    courseId: certCourse.id,
    title: certCourse.title,
    titleKh: certCourse.titleKh,
    certificateId: certCourse.certificateId,
    verificationCode: certCourse.verificationCode,
    certIssueDate: certCourse.certIssueDate || certCourse.completedDate,
    teacherName: certCourse.teacher.name,
    teacherNameKh: certCourse.teacher.name_kh,
    score: certCourse.quizScore,
    grade: certCourse.grade,
    downloadHref: certCourse.certificateDownloadHref || `/certificate/download/${certCourse.certificateDbId || certCourse.id}`,
    verifyHref: certCourse.certificateVerifyHref || `/certificate/verify/${certCourse.certificateId}`,
  }
})

// 4 Real Achievements Badges (unlocked strictly from DB progress)
const achievementsList = computed<AchievementBadge[]>(() => {
  if (props.achievements && props.achievements.length > 0) {
    return props.achievements
  }
  return [
    { id: 'quick_learner', key: 'quick_learner', title: 'Quick Learner', title_km: 'រៀនរហ័ស', unlocked: false, progress: '0/3 lessons', progress_km: '0/3 មេរៀន', theme: 'emerald' },
    { id: 'course_master', key: 'course_master', title: 'Course Master', title_km: 'ម្ចាស់វគ្គ', unlocked: false, progress: '0/1 course', progress_km: '0/1 វគ្គ', theme: 'blue' },
    { id: 'top_performer', key: 'top_performer', title: 'Top Performer', title_km: 'ពូកែបំផុត', unlocked: false, progress: '0% / 80%', progress_km: '0% / 80%', theme: 'orange' },
    { id: 'consistent_learner', key: 'consistent_learner', title: 'Consistent Learner', title_km: 'ព្យាយាមរៀន', unlocked: false, progress: '0/3 days', progress_km: '0/3 ថ្ងៃ', theme: 'purple' },
  ]
})

const hasActiveFilters = computed(() => {
  return activeTab.value !== 'all' || searchQuery.value.trim().length > 0 || selectedSort.value !== 'newest'
})

// Client-side instant filtering & sorting on top of server props for zero-latency UI
const filteredCourses = computed(() => {
  return courses.value
    .filter(course => {
      if (activeTab.value === 'certificates' && !course.hasCertificate) return false
      if (activeTab.value === 'no_certificate' && course.hasCertificate) return false

      if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim()
        const haystacks = [
          course.title,
          course.titleKh,
          course.teacher.name,
          course.teacher.name_kh,
          course.category,
          course.categoryKh,
          course.slug,
          course.certificateId,
        ]
        const matches = haystacks.some(val => typeof val === 'string' && val.toLowerCase().includes(q))
        if (!matches) return false
      }

      return true
    })
    .sort((a, b) => {
      if (selectedSort.value === 'newest') return b.completedDateTimestamp - a.completedDateTimestamp
      if (selectedSort.value === 'oldest') return a.completedDateTimestamp - b.completedDateTimestamp
      if (selectedSort.value === 'name') return localizedCourseTitle(a).localeCompare(localizedCourseTitle(b))
      if (selectedSort.value === 'highest_score') return (b.quizScore || 0) - (a.quizScore || 0)
      if (selectedSort.value === 'most_time') return (b.studyTimeSeconds ?? b.studyTimeMinutes * 60) - (a.studyTimeSeconds ?? a.studyTimeMinutes * 60)
      return 0
    })
})

const handleCertificateClick = (course: CompletedCourseItem, e: Event) => {
  e.stopPropagation()
  selectedCertCourse.value = course
  if (course.hasCertificate) {
    isCertModalOpen.value = true
  } else {
    isNoCertModalOpen.value = true
  }
}

const openLatestCertificateModal = (e: Event) => {
  e.stopPropagation()
  const latest = latestCertData.value
  if (!latest) return
  const matched = courses.value.find(c => c.id === latest.courseId || c.certificateId === latest.certificateId)
  if (matched) {
    selectedCertCourse.value = matched
  } else {
    selectedCertCourse.value = {
      id: latest.courseId || latest.id,
      slug: `cert-${latest.id}`,
      title: latest.title,
      titleKh: latest.titleKh,
      category: 'Certificate',
      categoryKh: 'វិញ្ញាបនបត្រ',
      status: 'completed',
      progress: 100,
      teacher: {
        name: latest.teacherName || 'SPI Instructor',
        name_kh: latest.teacherNameKh,
      },
      completedDate: latest.certIssueDate || '',
      completedDateTimestamp: 0,
      chaptersCount: 0,
      lessonsCount: 0,
      studyTime: '0m',
      studyTimeMinutes: 0,
      quizScore: latest.score ?? 100,
      hasQuizScore: latest.score !== null && latest.score !== undefined,
      grade: latest.grade,
      hasCertificate: true,
      certificateDbId: latest.id,
      certificateId: latest.certificateId,
      verificationCode: latest.verificationCode,
      certIssueDate: latest.certIssueDate,
      certificateDownloadHref: latest.downloadHref,
      certificateVerifyHref: latest.verifyHref,
      medalType: 'gold',
      illustrationType: 'web',
      overviewHref: latest.courseId ? `/student/courses/${latest.courseId}/overview` : '/student/certificates/my-certificates',
    }
  }
  isCertModalOpen.value = true
}

const navigateToReview = (course: CompletedCourseItem) => {
  router.visit(course.reviewHref || course.overviewHref)
}

const handleKeydown = (e: KeyboardEvent) => {
  const activeTag = document.activeElement?.tagName.toLowerCase()
  const isTyping = activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select'

  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    searchInputRef.value?.focus()
  } else if (e.key === '/' && !isTyping) {
    e.preventDefault()
    searchInputRef.value?.focus()
  } else if (e.key === 'Escape') {
    if (isCertModalOpen.value) isCertModalOpen.value = false
    if (isNoCertModalOpen.value) isNoCertModalOpen.value = false
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  if (debounceTimer) clearTimeout(debounceTimer)
})
</script>

<template>
  <StudentLayout
    :title="currentLang === 'km' ? 'វគ្គសិក្សាដែលបានបញ្ចប់' : 'Completed Courses'"
    :breadcrumbs="[
      { label: t('ផ្ទាំងគ្រប់គ្រង', 'Dashboard'), href: '/student/dashboard' },
      { label: t('វគ្គសិក្សារបស់ខ្ញុំ', 'My Courses'), href: '/student/my-courses/enrolled' },
      { label: t('វគ្គសិក្សាដែលបានបញ្ចប់', 'Completed Courses') }
    ]"
  >
    <div class="space-y-6 pb-16">
      
      <!-- 1. PAGE HEADER (Title with Green Checkmark, Subtitle & Search Bar) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
              {{ currentLang === 'km' ? 'វគ្គសិក្សាដែលបានបញ្ចប់' : 'Completed Courses' }}
            </h1>
            <span class="w-6 h-6 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 dark:border-emerald-500/40 flex items-center justify-center text-xs font-bold shadow-xs">
              ✓
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
            {{ currentLang === 'km' ? 'វគ្គសិក្សាទាំងអស់ដែលអ្នកបានបញ្ចប់ដោយជោគជ័យ។' : 'Courses you have successfully completed.' }}
          </p>
        </div>

        <!-- Global Search within Completed Courses -->
        <div class="relative w-full md:w-80">
          <div class="relative flex items-center">
            <span class="absolute left-3.5 text-slate-400 pointer-events-none">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              ref="searchInputRef"
              v-model="searchQuery"
              @input="onSearchInput"
              type="text"
              :placeholder="currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា...' : 'Search courses...'"
              class="w-full pl-10 pr-16 py-2 rounded-xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 focus:border-purple-500 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-purple-500/50 shadow-xs dark:shadow-inner transition-all"
            />
            <div class="absolute right-2.5 flex items-center gap-1">
              <button
                v-if="searchQuery"
                @click="clearSearch"
                type="button"
                aria-label="Clear search"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-0.5 text-xs cursor-pointer"
              >
                ✕
              </button>
              <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded shadow-xs">Ctrl K</kbd>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. FILTER TABS, SORT DROPDOWN & VIEW MODE -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800/80 pb-4">
        
        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="changeTab('all')"
            type="button"
            :class="[
              activeTab === 'all'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs'
            ]"
          >
            <span>{{ currentLang === 'km' ? 'បានបញ្ចប់ទាំងអស់' : 'All Completed' }}</span>
            <span :class="[activeTab === 'all' ? 'bg-purple-700/80 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold']">
              {{ totalCount }}
            </span>
          </button>

          <button
            @click="changeTab('certificates')"
            type="button"
            :class="[
              activeTab === 'certificates'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs'
            ]"
          >
            <span>{{ currentLang === 'km' ? 'ទទួលបានវិញ្ញាបនបត្រ' : 'With Certificate' }}</span>
            <span :class="[activeTab === 'certificates' ? 'bg-purple-700/80 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold']">
              {{ certEarnedCount }}
            </span>
          </button>

          <button
            @click="changeTab('no_certificate')"
            type="button"
            :class="[
              activeTab === 'no_certificate'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs'
            ]"
          >
            <span>{{ currentLang === 'km' ? 'មិនទាន់មានវិញ្ញាបនបត្រ' : 'Pending / No Certificate' }}</span>
            <span :class="[activeTab === 'no_certificate' ? 'bg-purple-700/80 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold']">
              {{ noCertCount }}
            </span>
          </button>
        </div>

        <!-- Sort & View Controls -->
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
          <div class="relative">
            <select
              v-model="selectedSort"
              @change="onSortChange"
              class="appearance-none bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl pl-3 pr-8 py-1.5 focus:outline-none focus:ring-1 focus:ring-purple-500/50 cursor-pointer shadow-xs"
            >
              <option value="newest">{{ currentLang === 'km' ? 'តម្រៀបតាម៖ ថ្មីបំផុត' : 'Sort by: Newest' }}</option>
              <option value="oldest">{{ currentLang === 'km' ? 'តម្រៀបតាម៖ ចាស់បំផុត' : 'Sort by: Oldest' }}</option>
              <option value="highest_score">{{ currentLang === 'km' ? 'តម្រៀបតាម៖ ពិន្ទុខ្ពស់បំផុត' : 'Highest Score' }}</option>
              <option value="name">{{ currentLang === 'km' ? 'តាមឈ្មោះ (A-Z)' : 'Course Name (A-Z)' }}</option>
              <option value="most_time">{{ currentLang === 'km' ? 'ម៉ោងសិក្សាច្រើនបំផុត' : 'Most Study Time' }}</option>
            </select>
            <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 dark:text-slate-500 text-xs">
              ▼
            </div>
          </div>

          <button
            v-if="hasActiveFilters"
            @click="resetFilters"
            type="button"
            class="px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-colors cursor-pointer whitespace-nowrap"
          >
            {{ currentLang === 'km' ? 'កំណត់ឡើងវិញ' : 'Reset' }}
          </button>

          <div class="inline-flex items-center gap-0.5 rounded-xl bg-slate-100 dark:bg-[#282a2c] border border-slate-200/90 dark:border-[#3c4043] p-1 shadow-2xs shrink-0">
            <button
              @click="setViewMode('grid')"
              @mouseup="($event.currentTarget as HTMLElement)?.blur()"
              type="button"
              :aria-pressed="viewMode === 'grid' ? 'true' : 'false'"
              :aria-label="currentLang === 'km' ? 'ទម្រង់ក្រឡា' : 'Grid View'"
              :class="[
                viewMode === 'grid' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                'w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/70'
              ]"
              :title="currentLang === 'km' ? 'ទម្រង់ក្រឡា' : 'Grid View'"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
            </button>
            <button
              @click="setViewMode('list')"
              @mouseup="($event.currentTarget as HTMLElement)?.blur()"
              type="button"
              :aria-pressed="viewMode === 'list' ? 'true' : 'false'"
              :aria-label="currentLang === 'km' ? 'ទម្រង់បញ្ជី' : 'List View'"
              :class="[
                viewMode === 'list' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                'w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/70'
              ]"
              :title="currentLang === 'km' ? 'ទម្រង់បញ្ជី' : 'List View'"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>

      </div>

      <!-- 3. MAIN SECTION: 2-COLUMN LAYOUT (Left Course Cards 8 cols, Right Analytics Widgets 4 cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8 COLUMNS: Completed Course Cards Grid/List -->
        <div class="lg:col-span-8 space-y-6">
          
          <div v-if="filteredCourses.length > 0">
            <!-- Grid View -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div
                v-for="course in filteredCourses"
                :key="course.id"
                @click="navigateToReview(course)"
                class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 hover:border-purple-400 dark:hover:border-purple-500/40 rounded-3xl overflow-hidden shadow-sm dark:shadow-2xl transition-all duration-200 hover:-translate-y-1 group flex flex-col justify-between cursor-pointer"
              >
                <!-- TOP CARD HEADER / THUMBNAIL OR 3D ILLUSTRATION BOX -->
                <div class="relative w-full h-44 bg-slate-900 dark:bg-slate-950 flex items-center justify-center overflow-hidden border-b border-slate-100 dark:border-slate-800/80">
                  
                  <img
                    v-if="course.thumbnail && !thumbErrors[course.id]"
                    :src="course.thumbnail"
                    :alt="localizedCourseTitle(course)"
                    class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                    @error="thumbErrors[course.id] = true"
                  />

                  <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-900/20 to-transparent z-10"></div>
                  <div
                    v-if="!course.thumbnail || thumbErrors[course.id]"
                    class="absolute -inset-10 opacity-25 blur-2xl transition-all group-hover:opacity-40"
                    :class="[
                      course.illustrationType === 'web' ? 'bg-purple-600' :
                      course.illustrationType === 'database' ? 'bg-indigo-600' :
                      course.illustrationType === 'python' ? 'bg-blue-600' : 'bg-emerald-600'
                    ]"
                  ></div>

                  <!-- Completed Status Badge & Category (Top-Left) -->
                  <div class="absolute top-3.5 left-3.5 z-20 flex flex-wrap items-center gap-1.5">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-600 text-white shadow-md shadow-emerald-600/30 backdrop-blur-md flex items-center gap-1">
                      <span>✓</span>
                      <span>{{ currentLang === 'km' ? 'បានបញ្ចប់ 100%' : 'Completed 100%' }}</span>
                    </span>
                  </div>

                  <!-- Achievement Medal / Final Score Pill (Top-Right) -->
                  <div class="absolute top-3.5 right-3.5 z-20 flex items-center gap-1.5">
                    <span
                      v-if="course.hasQuizScore"
                      class="px-2.5 py-1 rounded-full bg-slate-950/75 text-white border border-white/15 backdrop-blur-md text-[11px] font-bold shadow-sm tabular-nums"
                    >
                      {{ currentLang === 'km' ? 'ពិន្ទុ៖' : 'Score:' }} {{ course.quizScore }}%
                    </span>
                    <div
                      :class="[
                        course.medalType === 'gold' ? 'bg-amber-500/25 text-amber-300 border-amber-400/50 shadow-amber-500/20' :
                        course.medalType === 'silver' ? 'bg-slate-400/25 text-slate-200 border-slate-300/50 shadow-slate-500/20' :
                        'bg-orange-500/25 text-orange-300 border-orange-400/50 shadow-orange-500/20',
                        'w-8 h-8 rounded-full border backdrop-blur-md flex items-center justify-center text-sm shadow-md'
                      ]"
                      :title="course.hasCertificate ? t('ទទួលបានវិញ្ញាបនបត្រ', 'Certificate Earned') : t('រង់ចាំវិញ្ញាបនបត្រ', 'Assessment Pending')"
                    >
                      <span>{{ course.medalType === 'gold' ? '🏆' : (course.medalType === 'silver' ? '🥈' : '🏅') }}</span>
                    </div>
                  </div>

                  <!-- Category & Cert Pill Bottom-Left inside image -->
                  <div class="absolute bottom-3 left-3.5 right-3.5 z-20 flex items-center justify-between gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-white/90 dark:bg-slate-900/90 text-slate-800 dark:text-slate-200 text-[10px] font-bold shadow-xs truncate max-w-[65%]">
                      {{ localizedCourseCategory(course) }}
                    </span>
                    <span
                      v-if="course.hasCertificate"
                      class="px-2 py-0.5 rounded-full bg-amber-400/95 text-slate-950 text-[10px] font-black shadow-xs shrink-0"
                    >
                      {{ currentLang === 'km' ? 'មានវិញ្ញាបនបត្រ' : 'Certified' }}
                    </span>
                  </div>

                  <!-- Fallback 3D Course Illustrations when no thumbnail -->
                  <template v-if="!course.thumbnail || thumbErrors[course.id]">
                    <div v-if="course.illustrationType === 'web'" class="relative flex items-center justify-center scale-90 group-hover:scale-95 transition-transform">
                      <div class="w-40 h-24 rounded-lg bg-slate-900 border border-indigo-500/50 shadow-2xl flex flex-col p-2 relative overflow-hidden">
                        <div class="flex items-center gap-1 mb-1 border-b border-slate-800 pb-1">
                          <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                          <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        </div>
                        <div class="space-y-1 font-mono text-[8px] text-slate-400">
                          <div class="text-purple-400">const course = 100%</div>
                          <div class="text-emerald-400">// Status: Complete</div>
                        </div>
                        <div class="absolute -right-2 top-2 flex flex-col gap-1 z-20">
                          <span class="px-1.5 py-0.5 bg-orange-600 text-white font-black text-[9px] rounded">HTML</span>
                          <span class="px-1.5 py-0.5 bg-blue-600 text-white font-black text-[9px] rounded">CSS</span>
                          <span class="px-1.5 py-0.5 bg-amber-500 text-slate-950 font-black text-[9px] rounded">JS</span>
                        </div>
                      </div>
                      <div class="absolute -bottom-2 w-44 h-2 bg-slate-800 rounded-b-xl border-t border-slate-700"></div>
                    </div>

                    <div v-else-if="course.illustrationType === 'database'" class="relative flex items-center justify-center gap-3 scale-90 group-hover:scale-95 transition-transform">
                      <div class="flex flex-col gap-1 items-center">
                        <div class="w-12 h-4 rounded-full bg-orange-500 border border-orange-400 shadow-md"></div>
                        <div class="w-12 h-4 rounded-full bg-orange-600 border border-orange-500"></div>
                        <div class="w-12 h-4 rounded-full bg-orange-700"></div>
                      </div>
                      <div class="flex flex-col gap-1 items-center">
                        <div class="w-12 h-4 rounded-full bg-purple-500 border border-purple-400 shadow-md"></div>
                        <div class="w-12 h-4 rounded-full bg-purple-600 border border-purple-500"></div>
                        <div class="w-12 h-4 rounded-full bg-purple-700"></div>
                      </div>
                      <div class="flex flex-col gap-1 items-center">
                        <div class="w-12 h-4 rounded-full bg-cyan-500 border border-cyan-400 shadow-md"></div>
                        <div class="w-12 h-4 rounded-full bg-cyan-600 border border-cyan-500"></div>
                        <div class="w-12 h-4 rounded-full bg-cyan-700"></div>
                      </div>
                    </div>

                    <div v-else-if="course.illustrationType === 'python'" class="relative flex items-center justify-center scale-95 group-hover:scale-100 transition-transform">
                      <div class="relative w-20 h-20 flex items-center justify-center">
                        <div class="absolute top-0 left-1 w-12 h-10 bg-gradient-to-br from-blue-500 to-blue-700 rounded-t-xl rounded-l-xl shadow-md border border-blue-400/50 flex items-start justify-end p-1.5">
                          <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                        </div>
                        <div class="absolute bottom-0 right-1 w-12 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-b-xl rounded-r-xl shadow-md border border-amber-300/50 flex items-end justify-start p-1.5">
                          <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                        </div>
                      </div>
                    </div>

                    <div v-else-if="course.illustrationType === 'uiux'" class="relative flex items-center justify-center gap-2 scale-90 group-hover:scale-95 transition-transform">
                      <div class="w-16 h-24 rounded-xl bg-slate-900 border-2 border-purple-500/60 shadow-xl p-1 flex flex-col justify-between">
                        <div class="w-3 h-1 bg-slate-700 rounded-full mx-auto"></div>
                        <div class="text-center text-[8px] text-purple-300 font-bold">✓ 100%</div>
                        <div class="w-full h-1 bg-purple-500/40 rounded"></div>
                      </div>
                      <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-purple-900/60 to-slate-900 border border-purple-500/50 shadow-2xl flex flex-col items-center justify-center">
                        <span class="text-xl font-black bg-clip-text text-transparent bg-gradient-to-r from-purple-400 to-cyan-400">UI/UX</span>
                        <span class="text-[8px] text-purple-300 font-mono">Completed</span>
                      </div>
                    </div>
                  </template>

                </div>

                <!-- CARD BODY -->
                <div class="p-5 space-y-3.5 flex-1 flex flex-col justify-between">
                  
                  <div class="space-y-2.5">
                    <!-- Title, Score Pill & Teacher & Completion Date -->
                    <div>
                      <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors leading-snug line-clamp-2">
                        {{ localizedCourseTitle(course) }}
                      </h3>

                      <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400 mt-2">
                        <div class="flex items-center gap-2 min-w-0">
                          <img
                            v-if="course.teacher.avatar"
                            :src="course.teacher.avatar"
                            :alt="localizedTeacherName(course.teacher)"
                            class="w-5 h-5 rounded-full object-cover border border-purple-500/40 shrink-0"
                          />
                          <div
                            v-else
                            class="w-5 h-5 rounded-full bg-purple-600/20 text-purple-600 dark:text-purple-300 border border-purple-500/40 flex items-center justify-center text-[9px] font-bold shrink-0"
                          >
                            {{ (course.teacher.name || 'SP').slice(0, 2).toUpperCase() }}
                          </div>
                          <span class="text-slate-700 dark:text-slate-300 font-medium truncate">{{ localizedTeacherName(course.teacher) }}</span>
                        </div>

                        <div class="flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400 shrink-0">
                          <span>{{ currentLang === 'km' ? 'បានបញ្ចប់៖' : 'Completed on:' }} {{ course.completedDate }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- 100% Solid Emerald Green Progress Bar -->
                    <div class="space-y-1 pt-1">
                      <div class="flex items-center justify-between text-[11px] font-semibold">
                        <span class="text-emerald-600 dark:text-emerald-400">{{ currentLang === 'km' ? 'ការសិក្សាបានបញ្ចប់' : 'Course Progress' }}</span>
                        <span class="text-slate-700 dark:text-slate-200 tabular-nums">
                          100% <template v-if="course.hasQuizScore">• {{ currentLang === 'km' ? 'ពិន្ទុបញ្ចប់៖' : 'Final Score:' }} {{ course.quizScore }}%</template>
                        </span>
                      </div>
                      <div class="w-full h-1.5 rounded-full bg-emerald-500 shadow-xs shadow-emerald-500/30"></div>
                    </div>

                    <!-- Metadata Row (Chapters | Lessons | Time) -->
                    <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1.5 border-t border-slate-100 dark:border-slate-800/60">
                      <div class="flex items-center gap-1">
                        <span>{{ course.chaptersCount }} {{ currentLang === 'km' ? 'ជំពូក' : 'Chapters' }}</span>
                      </div>
                      <span class="text-slate-300 dark:text-slate-700">|</span>
                      <div class="flex items-center gap-1">
                        <span>{{ course.lessonsCount }} {{ currentLang === 'km' ? 'មេរៀន' : 'Lessons' }}</span>
                      </div>
                      <span class="text-slate-300 dark:text-slate-700">|</span>
                      <div class="flex items-center gap-1 tabular-nums">
                        <span>{{ course.studyTime }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- ACTIONS ROW: [ Review Lessons ] & [ Download Certificate / Eligibility ] -->
                  <div class="grid grid-cols-2 gap-2.5 pt-2.5 border-t border-slate-100 dark:border-slate-800/80" @click.stop>
                    
                    <Link
                      :href="course.reviewHref || course.overviewHref"
                      class="py-2 px-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/20 flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer"
                    >
                      <span>{{ currentLang === 'km' ? 'រំលឹកមេរៀនឡើងវិញ' : 'Review Lessons' }}</span>
                    </Link>

                    <div v-if="course.hasCertificate && course.certificateDownloadHref" class="flex items-center gap-1.5">
                      <a
                        :href="course.certificateDownloadHref"
                        class="flex-1 py-2 px-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center justify-center gap-1 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer shadow-xs"
                        :title="currentLang === 'km' ? 'ទាញយកវិញ្ញាបនបត្រ (PDF)' : 'Download Certificate (PDF)'"
                      >
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                        </svg>
                        <span class="truncate">{{ currentLang === 'km' ? 'ទាញយកវិញ្ញាបនបត្រ' : 'Certificate PDF' }}</span>
                      </a>
                      <button
                        @click="handleCertificateClick(course, $event)"
                        type="button"
                        class="p-2 rounded-xl bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 cursor-pointer transition-colors shrink-0"
                        :title="currentLang === 'km' ? 'មើលវិញ្ញាបនបត្រ' : 'Preview Certificate'"
                        :aria-label="currentLang === 'km' ? 'មើលវិញ្ញាបនបត្រ' : 'Preview Certificate'"
                      >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </button>
                    </div>

                    <button
                      v-else
                      @click="handleCertificateClick(course, $event)"
                      type="button"
                      class="py-2 px-3 rounded-xl border bg-slate-50 dark:bg-slate-900/60 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 text-xs font-semibold flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer shadow-xs"
                    >
                      <span>{{ currentLang === 'km' ? 'លក្ខខណ្ឌវិញ្ញាបនបត្រ' : 'Certificate Info' }}</span>
                    </button>

                  </div>

                </div>
              </div>
            </div>

            <!-- List View -->
            <div v-else class="space-y-4">
              <div
                v-for="course in filteredCourses"
                :key="course.id"
                @click="navigateToReview(course)"
                class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 hover:border-purple-400 dark:hover:border-purple-500/40 rounded-2xl p-4 shadow-sm dark:shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4 cursor-pointer group transition-all"
              >
                <div class="flex items-center gap-3.5 min-w-0">
                  <div class="w-16 h-12 rounded-xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-xl shrink-0 overflow-hidden">
                    <img
                      v-if="course.thumbnail && !thumbErrors[course.id]"
                      :src="course.thumbnail"
                      :alt="localizedCourseTitle(course)"
                      class="w-full h-full object-cover"
                      @error="thumbErrors[course.id] = true"
                    />
                    <span v-else>{{ course.medalType === 'gold' ? '🏆' : (course.medalType === 'silver' ? '🥈' : '🏅') }}</span>
                  </div>
                  <div class="min-w-0 space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30">
                        {{ currentLang === 'km' ? 'បានបញ្ចប់ 100% ✓' : 'Completed 100% ✓' }}
                      </span>
                      <span
                        v-if="course.hasQuizScore"
                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-700 dark:text-purple-300 border border-purple-500/25 tabular-nums"
                      >
                        {{ currentLang === 'km' ? 'ពិន្ទុ៖' : 'Score:' }} {{ course.quizScore }}%
                      </span>
                      <span class="text-xs text-slate-500 dark:text-slate-400">{{ course.completedDate }}</span>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate">
                      {{ localizedCourseTitle(course) }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                      {{ localizedTeacherName(course.teacher) }} • {{ course.lessonsCount }} {{ currentLang === 'km' ? 'មេរៀន' : 'Lessons' }} • {{ course.studyTime }}
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 flex-wrap" @click.stop>
                  <Link
                    :href="course.reviewHref || course.overviewHref"
                    class="px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-xs whitespace-nowrap"
                  >
                    {{ currentLang === 'km' ? 'រំលឹកមេរៀនឡើងវិញ' : 'Review Lessons' }}
                  </Link>
                  <a
                    v-if="course.hasCertificate && course.certificateDownloadHref"
                    :href="course.certificateDownloadHref"
                    class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs inline-flex items-center gap-1 whitespace-nowrap"
                  >
                    <span>{{ currentLang === 'km' ? 'ទាញយកវិញ្ញាបនបត្រ' : 'Download Certificate' }}</span>
                  </a>
                  <button
                    @click="handleCertificateClick(course, $event)"
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-semibold cursor-pointer shadow-xs whitespace-nowrap"
                  >
                    {{ course.hasCertificate ? (currentLang === 'km' ? 'មើល' : 'Preview') : (currentLang === 'km' ? 'លក្ខខណ្ឌ' : 'Eligibility') }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Footer Summary -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-6 border-t border-slate-200 dark:border-slate-800/80 text-xs text-slate-500 dark:text-slate-400">
              <div>
                {{ currentLang === 'km' ? `កំពុងបង្ហាញ 1 ដល់ ${filteredCourses.length} នៃ ${totalCount} វគ្គសិក្សា` : `Showing 1 to ${filteredCourses.length} of ${totalCount} courses` }}
              </div>

              <div class="flex items-center gap-1.5 self-center sm:self-auto">
                <button
                  type="button"
                  disabled
                  class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/60 text-slate-400 dark:text-slate-600 cursor-not-allowed"
                >
                  ‹
                </button>
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-lg bg-purple-600 text-white font-bold shadow-xs cursor-pointer"
                >
                  1
                </button>
                <button
                  type="button"
                  disabled
                  class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/60 text-slate-400 dark:text-slate-600 cursor-not-allowed"
                >
                  ›
                </button>
              </div>
            </div>

          </div>

          <!-- Empty State -->
          <div
            v-else
            class="bg-white dark:bg-slate-900/40 border border-dashed border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center space-y-4 shadow-xs"
          >
            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800/60 flex items-center justify-center mx-auto text-slate-400 dark:text-slate-500">
              <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <div class="space-y-1">
              <h3 class="text-base font-bold text-slate-900 dark:text-white">
                {{ currentLang === 'km' ? 'មិនទាន់មានវគ្គសិក្សាដែលបានបញ្ចប់ទេ' : 'No completed courses match your criteria' }}
              </h3>
              <p class="text-xs text-slate-600 dark:text-slate-400 max-w-sm mx-auto">
                {{ currentLang === 'km' ? 'សាកល្បងប្តូរផ្ទាំង ឬស្វែងរកវគ្គសិក្សាថ្មីដើម្បីចាប់ផ្តើមរៀន។' : 'Try switching between tabs or browse courses to begin learning.' }}
              </p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2.5">
              <button
                @click="resetFilters"
                type="button"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs cursor-pointer shadow-md shadow-purple-600/20"
              >
                {{ currentLang === 'km' ? 'កំណត់តម្រងឡើងវិញ' : 'Reset Filters' }}
              </button>
              <Link
                href="/student/browse"
                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs transition-colors"
              >
                {{ currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា' : 'Browse Courses' }}
              </Link>
            </div>
          </div>

        </div>

        <!-- RIGHT 4 COLUMNS: 5 Analytics Widgets -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- WIDGET 1: Completion Overview (Donut Chart) -->
          <div class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ currentLang === 'km' ? 'ទិដ្ឋភាពទូទៅនៃការបញ្ចប់' : 'Completion Overview' }}
            </h3>

            <div class="flex items-center gap-4">
              <!-- Donut SVG Ring -->
              <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                  <path
                    class="text-slate-100 dark:text-slate-800"
                    stroke-width="4"
                    stroke="currentColor"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                  <path
                    v-if="certPercent > 0"
                    class="text-emerald-500"
                    :stroke-dasharray="`${certPercent}, 100`"
                    stroke-width="4"
                    stroke-linecap="round"
                    stroke="currentColor"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                  <path
                    v-if="noCertPercent > 0"
                    class="text-amber-500"
                    :stroke-dashoffset="`-${certPercent}`"
                    :stroke-dasharray="`${noCertPercent}, 100`"
                    stroke-width="4"
                    stroke-linecap="round"
                    stroke="currentColor"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-slate-900 dark:text-white leading-none tabular-nums">{{ totalCount }}</span>
                  <span class="text-[9px] text-slate-500 dark:text-slate-400 uppercase font-semibold mt-0.5">{{ currentLang === 'km' ? 'សរុប' : 'Total' }}</span>
                </div>
              </div>

              <!-- Legend Details -->
              <div class="space-y-2 text-xs flex-1">
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="text-slate-700 dark:text-slate-300 font-medium truncate">{{ currentLang === 'km' ? 'មានវិញ្ញាបនបត្រ' : 'With Certificate' }}</span>
                  </div>
                  <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px] shrink-0 tabular-nums">{{ certEarnedCount }} ({{ certPercent }}%)</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="text-slate-700 dark:text-slate-300 font-medium truncate">{{ currentLang === 'km' ? 'មិនទាន់មាន' : 'No Certificate' }}</span>
                  </div>
                  <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px] shrink-0 tabular-nums">{{ noCertCount }} ({{ noCertPercent }}%)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- WIDGET 2: Total Study Time -->
          <Link
            href="/student/progress/learning-time"
            class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 hover:border-blue-400 dark:hover:border-blue-500/40 rounded-3xl p-5 shadow-sm dark:shadow-xl flex items-center gap-4 group transition-all cursor-pointer"
          >
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 dark:bg-blue-500/20 border border-blue-500/25 dark:border-blue-500/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 1.5"/><path d="M9 2h6"/><path d="m19 7-1.5-1.5"/>
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">{{ currentLang === 'km' ? 'ពេលវេលាសិក្សាសរុប' : 'Total Study Time' }}</p>
              <h4 class="text-xl font-black text-slate-900 dark:text-white mt-0.5 tracking-tight group-hover:text-blue-600 dark:group-hover:text-blue-300 transition-colors tabular-nums">
                {{ totalStudyTimeFormatted }}
              </h4>
              <p class="text-[10px] text-slate-500 mt-0.5">{{ currentLang === 'km' ? 'គ្រប់វគ្គសិក្សាដែលបានបញ្ចប់' : 'Across all completed courses' }}</p>
            </div>
            <span class="text-slate-400 dark:text-slate-600 group-hover:text-slate-700 dark:group-hover:text-slate-300 transition-colors text-sm">›</span>
          </Link>

          <!-- WIDGET 3: Average Quiz Score -->
          <Link
            href="/student/quizzes/scores"
            class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 hover:border-purple-400 dark:hover:border-purple-500/40 rounded-3xl p-5 shadow-sm dark:shadow-xl flex items-center gap-4 group transition-all cursor-pointer"
          >
            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 dark:bg-purple-500/20 border border-purple-500/25 dark:border-purple-500/40 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">{{ currentLang === 'km' ? 'ពិន្ទុ Quiz មធ្យម' : 'Average Quiz Score' }}</p>
              <h4 class="text-xl font-black text-slate-900 dark:text-white mt-0.5 tracking-tight group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors tabular-nums">
                {{ averageQuizScore }}%
              </h4>
              <p
                :class="[
                  'text-[10px] font-medium mt-0.5',
                  completedQuizzesCount === 0
                    ? 'text-slate-400 dark:text-slate-500'
                    : 'text-emerald-600 dark:text-emerald-400'
                ]"
              >
                {{ quizScoreLabel }}
              </p>
            </div>
            <span class="text-slate-400 dark:text-slate-600 group-hover:text-slate-700 dark:group-hover:text-slate-300 transition-colors text-sm">›</span>
          </Link>

          <!-- WIDGET 4: Latest Certificate -->
          <div class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ currentLang === 'km' ? 'វិញ្ញាបនបត្រថ្មីៗបំផុត' : 'Latest Certificate' }}
            </h3>

            <div v-if="latestCertData" class="flex items-center gap-3.5 p-3 rounded-2xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800">
              <!-- Mini Certificate Thumbnail -->
              <div class="w-14 h-16 rounded-lg bg-amber-50 border-2 border-amber-300 p-1 flex flex-col justify-between text-center shadow-md shrink-0">
                <div class="text-[6px] font-bold text-amber-900">SAINT PAUL</div>
                <div class="w-5 h-5 rounded-full bg-amber-400/30 border border-amber-500 mx-auto flex items-center justify-center text-[8px] text-amber-800 font-bold">
                  ★
                </div>
                <div class="text-[5px] text-slate-600 font-mono">Verified</div>
              </div>

              <div class="min-w-0 flex-1 space-y-1">
                <h4 class="font-bold text-xs text-slate-900 dark:text-white truncate">
                  {{ currentLang === 'km' ? (latestCertData.titleKh || latestCertData.title) : latestCertData.title }}
                </h4>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">
                  {{ currentLang === 'km' ? 'ទទួលបាននៅ៖' : 'Earned on:' }} {{ latestCertData.certIssueDate }}
                </p>
                <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                  <button
                    @click="openLatestCertificateModal($event)"
                    type="button"
                    class="px-2.5 py-1 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-[11px] font-bold shadow-xs transition-colors cursor-pointer"
                  >
                    {{ currentLang === 'km' ? 'មើលវិញ្ញាបនបត្រ' : 'View' }}
                  </button>
                  <a
                    :href="latestCertData.downloadHref"
                    class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold shadow-xs transition-colors"
                  >
                    {{ currentLang === 'km' ? 'ទាញយក PDF' : 'PDF' }}
                  </a>
                </div>
              </div>
            </div>

            <div v-else class="p-5 rounded-2xl bg-slate-50/80 dark:bg-slate-950/60 border border-dashed border-slate-200 dark:border-slate-800 flex flex-col items-center text-center gap-2">
              <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/25 text-amber-500 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                </svg>
              </div>
              <div class="space-y-0.5">
                <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ currentLang === 'km' ? 'មិនទាន់មានវិញ្ញាបនបត្រនៅឡើយទេ' : 'No certificates earned yet' }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ currentLang === 'km' ? 'បញ្ចប់វគ្គសិក្សាដើម្បីទទួលបានវិញ្ញាបនបត្រ។' : 'Complete a course to unlock your certificate.' }}</p>
              </div>
              <Link
                href="/student/browse"
                class="mt-1 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-purple-600/10 hover:bg-purple-600/20 text-purple-600 dark:text-purple-400 text-[11px] font-semibold transition-colors"
              >
                {{ currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា ›' : 'Browse Courses ›' }}
              </Link>
            </div>
          </div>

          <!-- WIDGET 5: Achievements Badges (Real Locked / Unlocked State from MySQL) -->
          <div class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                {{ currentLang === 'km' ? 'សមិទ្ធផល' : 'Achievements' }}
              </h3>
              <Link
                href="/student/certificates/achievements"
                class="text-[11px] text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 font-semibold"
              >
                {{ currentLang === 'km' ? 'មើលទាំងអស់' : 'View All' }}
              </Link>
            </div>

            <!-- 4 Dynamic Badges Row -->
            <div class="grid grid-cols-4 gap-2 text-center">
              <div
                v-for="badge in achievementsList"
                :key="badge.id"
                :class="[
                  'relative flex flex-col items-center gap-1.5 p-2 rounded-2xl border transition-all',
                  badge.unlocked
                    ? 'bg-slate-50 dark:bg-slate-950/60 border-slate-200/90 dark:border-slate-800/80 shadow-2xs'
                    : 'bg-slate-50/50 dark:bg-slate-950/30 border-slate-200/60 dark:border-slate-800/40 opacity-60 grayscale'
                ]"
                :title="`${currentLang === 'km' ? badge.title_km : badge.title} • ${badge.unlocked ? t('បានដោះសោរ', 'Unlocked') : t('ជាប់សោរ', 'Locked')} (${currentLang === 'km' ? badge.progress_km : badge.progress})`"
              >
                <!-- Lock / Unlock Indicator -->
                <span
                  :class="[
                    'absolute -top-1 -right-1 w-4 h-4 rounded-full text-[9px] flex items-center justify-center font-bold shadow-xs border',
                    badge.unlocked
                      ? 'bg-emerald-500 text-white border-emerald-400'
                      : 'bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-300 dark:border-slate-700'
                  ]"
                >
                  {{ badge.unlocked ? '✓' : '🔒' }}
                </span>

                <!-- Badge Icon by Key -->
                <div
                  :class="[
                    'w-10 h-10 rounded-xl border flex items-center justify-center shadow-xs',
                    badge.theme === 'emerald' ? 'bg-emerald-500/10 dark:bg-emerald-500/20 border-emerald-500/25 dark:border-emerald-500/40 text-emerald-600 dark:text-emerald-400' :
                    badge.theme === 'blue' ? 'bg-blue-500/10 dark:bg-blue-500/20 border-blue-500/25 dark:border-blue-500/40 text-blue-600 dark:text-blue-400' :
                    badge.theme === 'orange' ? 'bg-orange-500/10 dark:bg-orange-500/20 border-orange-500/25 dark:border-orange-500/40 text-orange-600 dark:text-orange-400' :
                    'bg-purple-500/10 dark:bg-purple-500/20 border-purple-500/25 dark:border-purple-500/40 text-purple-600 dark:text-purple-400'
                  ]"
                >
                  <svg v-if="badge.key === 'quick_learner'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                  </svg>
                  <svg v-else-if="badge.key === 'course_master'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                  </svg>
                  <svg v-else-if="badge.key === 'top_performer'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
                  </svg>
                  <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                  </svg>
                </div>

                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 leading-tight">
                  {{ currentLang === 'km' ? badge.title_km : badge.title }}
                </span>
                <span class="text-[9px] font-mono text-slate-500 dark:text-slate-400 leading-none">
                  {{ currentLang === 'km' ? badge.progress_km : badge.progress }}
                </span>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- CERTIFICATE MODAL (When Issued/Valid) -->
    <div
      v-if="isCertModalOpen && selectedCertCourse"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 dark:bg-slate-950/80 backdrop-blur-md"
      @click="isCertModalOpen = false"
    >
      <div
        class="relative w-full max-w-2xl bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6"
        @click.stop
      >
        <button
          @click="isCertModalOpen = false"
          type="button"
          class="absolute top-4 right-4 text-slate-400 hover:text-slate-900 dark:hover:text-white p-1 text-sm cursor-pointer"
        >
          ✕
        </button>

        <!-- Certificate Card Display -->
        <div class="rounded-2xl border-4 border-amber-400/60 bg-gradient-to-b from-amber-50 to-amber-100/90 text-slate-900 p-6 sm:p-8 text-center space-y-4 shadow-xl">
          <div class="space-y-1">
            <h3 class="text-xs font-mono uppercase tracking-widest text-amber-800 font-bold">Saint Paul Institute</h3>
            <h2 class="text-xl sm:text-2xl font-serif font-black text-amber-950">Certificate of Completion</h2>
            <p class="text-[11px] text-slate-600">This is to proudly certify that</p>
          </div>

          <div class="py-2 border-b border-amber-300 max-w-sm mx-auto">
            <h4 class="text-lg sm:text-xl font-black text-slate-900">{{ authUser.name || 'Student' }}</h4>
            <p class="text-[10px] text-slate-500">{{ currentLang === 'km' ? 'លេខសម្គាល់និស្សិត៖' : 'Student ID:' }} {{ authUser.student_code || ('STU-' + (authUser.id || '001')) }}</p>
          </div>

          <p class="text-xs text-slate-700 max-w-md mx-auto leading-relaxed">
            has successfully fulfilled all academic requirements, hands-on drills, and final evaluations for
          </p>

          <h4 class="text-base sm:text-lg font-black text-indigo-900">
            {{ localizedCourseTitle(selectedCertCourse) }}
          </h4>

          <div class="flex items-center justify-between pt-4 border-t border-amber-300/80 text-[10px] text-slate-600">
            <div class="text-left">
              <p class="font-bold text-slate-800">{{ currentLang === 'km' ? 'គ្រូបង្រៀន៖' : 'Instructor:' }} {{ localizedTeacherName(selectedCertCourse.teacher) }}</p>
              <p>{{ currentLang === 'km' ? 'កាលបរិច្ឆេទចេញ៖' : 'Issue Date:' }} {{ selectedCertCourse.certIssueDate || selectedCertCourse.completedDate }}</p>
            </div>
            <div class="text-right font-mono">
              <p class="font-bold text-indigo-800">ID: {{ selectedCertCourse.certificateId }}</p>
              <p class="text-emerald-700 font-semibold">Status: Officially Verified ✓</p>
            </div>
          </div>
        </div>

        <!-- Action CTA Buttons -->
        <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
          <a
            v-if="selectedCertCourse.certificateDownloadHref"
            :href="selectedCertCourse.certificateDownloadHref"
            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md inline-flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
            </svg>
            <span>{{ currentLang === 'km' ? 'ទាញយកវិញ្ញាបនបត្រ (PDF)' : 'Download Certificate (PDF)' }}</span>
          </a>
          <Link
            href="/student/certificates/my-certificates"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md"
          >
            {{ currentLang === 'km' ? 'ទៅកាន់វិញ្ញាបនបត្ររបស់ខ្ញុំ' : 'Go to My Certificates' }}
          </Link>
          <button
            @click="isCertModalOpen = false"
            type="button"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer"
          >
            {{ currentLang === 'km' ? 'បិទ' : 'Close' }}
          </button>
        </div>
      </div>
    </div>

    <!-- NO CERTIFICATE / ELIGIBILITY MODAL -->
    <div
      v-if="isNoCertModalOpen && selectedCertCourse"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 dark:bg-slate-950/80 backdrop-blur-md"
      @click="isNoCertModalOpen = false"
    >
      <div
        class="relative w-full max-w-lg bg-white dark:bg-[#0e1424] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isNoCertModalOpen = false"
          type="button"
          class="absolute top-4 right-4 text-slate-400 hover:text-slate-900 dark:hover:text-white p-1 text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-amber-500/10 dark:bg-amber-500/20 border border-amber-500/30 dark:border-amber-500/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg">
            ⚠️
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ currentLang === 'km' ? 'មិនទាន់មានវិញ្ញាបនបត្រនៅឡើយទេ' : 'Certificate Not Yet Available' }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ localizedCourseTitle(selectedCertCourse) }}</p>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 space-y-3 text-xs">
          <p class="font-bold text-slate-800 dark:text-slate-200">{{ currentLang === 'km' ? 'ស្ថានភាពលក្ខខណ្ឌបញ្ចប់៖' : 'Completion Requirements Status:' }}</p>
          <div class="space-y-2">
            <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
              <span>✓ {{ currentLang === 'km' ? 'មេរៀនទាំងអស់បានបញ្ចប់' : 'All Lessons Completed' }} ({{ selectedCertCourse.lessonsCount }}/{{ selectedCertCourse.lessonsCount }})</span>
              <span class="font-bold">{{ currentLang === 'km' ? 'ជាប់' : 'Passed' }}</span>
            </div>
            <div
              :class="[
                'flex items-center justify-between',
                selectedCertCourse.hasQuizScore && selectedCertCourse.quizScore >= 70
                  ? 'text-emerald-600 dark:text-emerald-400'
                  : 'text-rose-600 dark:text-rose-400'
              ]"
            >
              <span>
                {{ selectedCertCourse.hasQuizScore && selectedCertCourse.quizScore >= 70 ? '✓' : '✗' }}
                {{ currentLang === 'km' ? 'ការវាយតម្លៃចុងក្រោយ (កម្រិតជាប់៖ 70%)' : 'Final Assessment (Passing: 70%)' }}
              </span>
              <span class="font-bold font-mono">
                {{ selectedCertCourse.hasQuizScore ? `${currentLang === 'km' ? 'ពិន្ទុ៖' : 'Score:'} ${selectedCertCourse.quizScore}%` : (currentLang === 'km' ? 'មិនទាន់ប្រឡង' : 'Pending') }}
              </span>
            </div>
          </div>
        </div>

        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
          {{ localizedMissingCertReason(selectedCertCourse) }}
          {{ currentLang === 'km' ? 'អ្នកអាចធ្វើតេស្តវាយតម្លៃឡើងវិញគ្រប់ពេល ដើម្បីទទួលបានវិញ្ញាបនបត្រផ្លូវការ។' : 'You can take or retake the assessment at any time to qualify for the official SPI certificate.' }}
        </p>

        <div class="flex items-center justify-end gap-3 pt-2">
          <Link
            href="/student/quizzes/post-test"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md flex items-center gap-1.5"
          >
            <span>{{ currentLang === 'km' ? 'ទៅធ្វើតេស្តវាយតម្លៃ' : 'Take / Retake Assessment' }}</span>
          </Link>
          <button
            @click="isNoCertModalOpen = false"
            type="button"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer"
          >
            {{ currentLang === 'km' ? 'បិទ' : 'Close' }}
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>
