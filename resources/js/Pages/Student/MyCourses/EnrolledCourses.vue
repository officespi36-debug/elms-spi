<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { isCourseCachedOffline, saveCourseForOffline, getAllOfflineCourses } from '@/offline/sync'
import { useLanguage } from '@/Services/i18n'

const { t, currentLang } = useLanguage()
const emptySearchImgUrl = '/images/search-empty-3d.png'

interface EnrolledCourseItem {
  id: number
  enrollment_id?: number
  is_new?: boolean
  enrolled_by?: {
    name: string
    role?: string
    avatar?: string
  } | null
  enrolled_relative?: string
  source?: string
  slug: string
  title: string
  titleKh?: string
  thumbnail?: string | null
  category: string
  categoryKh?: string
  status: 'in_progress' | 'not_started' | 'completed' | 'coming_soon' | 'no_content'
  statusLabel: string
  progress: number
  progress_percentage?: number
  teacher: {
    name: string
    name_kh?: string
    avatar?: string | null
    role?: string
  }
  currentChapter: string
  currentModuleIndex?: number
  totalChapters: number
  completedChapters: number
  currentLesson: string
  currentLessonKh?: string
  completedLessons: number
  totalLessons: number
  remainingTime: string
  lastAccessed: string
  lastAccessedTimeMs: number
  enrolledDate: string
  overviewHref: string
  learningHref: string
  has_certificate?: boolean
  certificateHref?: string | null
  themeColor: string
  accentGradient: string
  illustrationType: 'web' | 'database' | 'python' | 'uiux'
}

const props = defineProps<{
  enrollments?: EnrolledCourseItem[]
  counts?: {
    all: number
    in_progress: number
    not_started: number
    completed: number
    coming_soon?: number
  }
  categories?: string[]
  categoryOptions?: Array<{
    value: string
    label_en: string
    label_km: string
  }>
  savedOfflineCourseIds?: number[]
  filters?: {
    status?: string
    category?: string
    sort?: string
    q?: string
    view?: string
  }
}>()

const activeTab = ref<'all' | 'in_progress' | 'not_started' | 'completed' | 'coming_soon'>(
  (props.filters?.status as any) || 'all'
)
const searchQuery = ref(props.filters?.q || '')
const selectedCategory = ref(props.filters?.category || 'all')
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
  return 'grid'
}

const viewMode = ref<'grid' | 'list'>(resolveInitialViewMode())

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
  if (s) {
    activeTab.value = s as any
  }
})

const activeDropdownId = ref<number | null>(null)
const cachedCourseIds = ref<number[]>([...(props.savedOfflineCourseIds || [])])
const savingOfflineId = ref<number | null>(null)
const searchInputRef = ref<HTMLInputElement | null>(null)
const copyLinkSuccessId = ref<number | null>(null)
let debounceTimer: ReturnType<typeof setTimeout> | null = null

const getInitials = (name?: string) => {
  if (!name) return 'SP'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
}

// Real Enrolled Courses dynamically computed from Database props
const courses = computed<EnrolledCourseItem[]>(() => {
  return props.enrollments || []
})

const counts = computed(() => {
  if (props.counts) {
    return {
      all: props.counts.all ?? 0,
      in_progress: props.counts.in_progress ?? 0,
      not_started: props.counts.not_started ?? 0,
      completed: props.counts.completed ?? 0,
      coming_soon: props.counts.coming_soon ?? 0,
    }
  }
  const in_progress = courses.value.filter(c => c.status === 'in_progress').length
  const not_started = courses.value.filter(c => c.status === 'not_started').length
  const completed = courses.value.filter(c => c.status === 'completed').length
  const coming_soon = courses.value.filter(c => c.status === 'coming_soon' || c.status === 'no_content').length
  const all = in_progress + not_started + completed + coming_soon
  return { all, in_progress, not_started, completed, coming_soon }
})

const categoryKhMap = computed<Record<string, string>>(() => {
  const map: Record<string, string> = {}
  for (const opt of props.categoryOptions || []) {
    if (opt.value && opt.label_km) {
      map[opt.value] = opt.label_km
    }
  }
  for (const c of courses.value) {
    if (c.category && c.categoryKh) {
      map[c.category] = c.categoryKh
    }
  }
  return map
})

const availableCategoryOptions = computed(() => {
  if (props.categoryOptions && props.categoryOptions.length > 0) {
    return props.categoryOptions
  }
  const list = props.categories && props.categories.length > 0
    ? props.categories
    : Array.from(new Set(courses.value.map(c => c.category).filter(Boolean)))
  return list.map(cat => ({
    value: cat,
    label_en: cat,
    label_km: categoryKhMap.value[cat] || (cat === 'Academic Studies' ? 'ការសិក្សាទូទៅ' : cat),
  }))
})

const localizedCategory = (category: string) => {
  if (currentLang.value === 'km') {
    return categoryKhMap.value[category] || (category === 'Academic Studies' ? 'ការសិក្សាទូទៅ' : category)
  }
  return category
}

const localizedCourseCategory = (course: EnrolledCourseItem) => {
  if (currentLang.value === 'km') {
    return course.categoryKh || localizedCategory(course.category)
  }
  return course.category
}

const localizedCourseTitle = (course: EnrolledCourseItem): string => {
  if (currentLang.value === 'km') {
    return course.titleKh || course.title
  }
  return course.title
}

const localizedTeacherName = (teacher?: { name: string; name_kh?: string } | null): string => {
  if (!teacher) return ''
  if (currentLang.value === 'km') {
    return teacher.name_kh || teacher.name
  }
  return teacher.name
}

const localizedStatusLabel = (course: EnrolledCourseItem) => {
  if (course.status === 'coming_soon' || course.status === 'no_content') return t('នឹងមកដល់ឆាប់ៗ', 'Coming soon')
  if (course.status === 'not_started') return t('មិនទាន់ចាប់ផ្តើម', 'Not Started')
  if (course.status === 'in_progress') return t('កំពុងសិក្សា', 'In Progress')
  if (course.status === 'completed') return t('បានបញ្ចប់', 'Completed')
  return course.statusLabel
}

const localizedLastAccessed = (val?: string) => {
  if (!val || val === 'Not started yet') {
    return t('មិនទាន់ចាប់ផ្តើមនៅឡើយ', 'Not started yet')
  }
  return val
}

const hasActiveFilters = computed(() => {
  return activeTab.value !== 'all' || selectedCategory.value !== 'all' || searchQuery.value.trim().length > 0
})

const triggerServerFilter = () => {
  router.get(
    '/student/my-courses/enrolled',
    {
      status: activeTab.value,
      category: selectedCategory.value,
      sort: selectedSort.value,
      q: searchQuery.value.trim() || undefined,
      view: viewMode.value,
    },
    {
      preserveState: true,
      replace: true,
      preserveScroll: true,
    }
  )
}

const onSearchInput = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    triggerServerFilter()
  }, 300)
}

const changeTab = (tab: 'all' | 'in_progress' | 'not_started' | 'completed' | 'coming_soon') => {
  activeTab.value = tab
  triggerServerFilter()
}

const onCategoryChange = () => {
  triggerServerFilter()
}

const onSortChange = () => {
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

  // Save view preference to database via PATCH /student/preferences
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

const resetFilters = () => {
  activeTab.value = 'all'
  searchQuery.value = ''
  selectedCategory.value = 'all'
  selectedSort.value = 'newest'
  triggerServerFilter()
}

const refreshCachedCourses = async () => {
  const dbIds = new Set<number>(props.savedOfflineCourseIds || [])
  try {
    const all = await getAllOfflineCourses()
    all.forEach(c => dbIds.add(Number(c.id)))
  } catch (e) {}
  cachedCourseIds.value = Array.from(dbIds)
}

const persistOfflineCourseIdsToDb = async (ids: number[]) => {
  try {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    await fetch('/student/preferences', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ offline_course_ids: ids }),
    })
  } catch (e) {}
}

const handleSaveCourseOffline = async (course: EnrolledCourseItem) => {
  savingOfflineId.value = course.id
  try {
    const isAlreadySaved = cachedCourseIds.value.includes(course.id)
    if (isAlreadySaved) {
      cachedCourseIds.value = cachedCourseIds.value.filter(id => id !== course.id)
      await persistOfflineCourseIdsToDb(cachedCourseIds.value)
    } else {
      try {
        await saveCourseForOffline({
          id: course.id,
          title: course.title,
          teacher: course.teacher.name,
          progress: course.progress,
          thumbnail: course.thumbnail || '',
        })
      } catch (e) {}
      if (!cachedCourseIds.value.includes(course.id)) {
        cachedCourseIds.value = [...cachedCourseIds.value, course.id]
      }
      await persistOfflineCourseIdsToDb(cachedCourseIds.value)
    }
  } finally {
    savingOfflineId.value = null
    activeDropdownId.value = null
  }
}

const toggleDropdown = (id: number, e: Event) => {
  e.stopPropagation()
  activeDropdownId.value = activeDropdownId.value === id ? null : id
}

const closeAllDropdowns = () => {
  activeDropdownId.value = null
}

const copyCourseLink = (course: EnrolledCourseItem) => {
  const url = `${window.location.origin}${course.overviewHref}`
  navigator.clipboard.writeText(url).then(() => {
    copyLinkSuccessId.value = course.id
    setTimeout(() => {
      copyLinkSuccessId.value = null
      activeDropdownId.value = null
    }, 1500)
  }).catch(() => {
    activeDropdownId.value = null
  })
}

const navigateToCourseOverview = (course: EnrolledCourseItem) => {
  if (course.is_new && course.enrollment_id) {
    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
      fetch(`/student/enrollments/${course.enrollment_id}/seen`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
      }).catch(() => {})
    } catch (e) {}
  }
  router.visit(course.overviewHref)
}

const formatEnrollmentMeta = (course: any) => {
  const source = course.source
  const enrolledBy = course.enrolled_by
  const relative = course.enrolled_relative || t('ថ្មីៗនេះ', 'Recently')
  const date = course.enrolledDate || relative

  if (source === 'teacher' || source === 'admin') {
    if (enrolledBy?.name) {
      return currentLang.value === 'km'
        ? `បញ្ចូលដោយ ${enrolledBy.name} · ${relative}`
        : `Added by ${enrolledBy.name} · ${relative}`
    }
    const actorRole = source === 'teacher' ? t('គ្រូបង្រៀន', 'Instructor') : t('អ្នកគ្រប់គ្រង', 'Admin')
    return currentLang.value === 'km'
      ? `បញ្ចូលដោយ ${actorRole} · ${relative}`
      : `Added by ${actorRole} · ${relative}`
  }

  if (source === 'self') {
    return currentLang.value === 'km'
      ? `អ្នកបានចុះឈ្មោះ · ${relative}`
      : `You enrolled · ${relative}`
  }

  if (source === 'payment') {
    return t('បានចុះឈ្មោះក្រោយការបង់ប្រាក់', 'Enrolled after payment')
  }

  // Legacy or manual enrollment
  return currentLang.value === 'km'
    ? `បានចុះឈ្មោះ · ${date}`
    : `Enrolled · ${date}`
}

const handleGlobalKeyDown = (e: KeyboardEvent) => {
  // Focus search on '/' key press
  if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes((e.target as HTMLElement)?.tagName)) {
    e.preventDefault()
    searchInputRef.value?.focus()
  }
  // Close dropdown on Esc
  if (e.key === 'Escape' && activeDropdownId.value !== null) {
    activeDropdownId.value = null
  }
}

onMounted(() => {
  refreshCachedCourses()
  window.addEventListener('click', closeAllDropdowns)
  window.addEventListener('keydown', handleGlobalKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('click', closeAllDropdowns)
  window.removeEventListener('keydown', handleGlobalKeyDown)
  if (debounceTimer) clearTimeout(debounceTimer)
})
</script>

<template>
  <StudentLayout
    :title="t('វគ្គសិក្សាដែលបានចុះឈ្មោះ', 'Enrolled Courses')"
    :breadcrumbs="[
      { label: t('ផ្ទាំងគ្រប់គ្រង', 'Dashboard'), href: '/student/dashboard' },
      { label: t('វគ្គសិក្សារបស់ខ្ញុំ', 'My Courses'), href: '/student/my-courses/enrolled' },
      { label: t('វគ្គសិក្សាដែលបានចុះឈ្មោះ', 'Enrolled Courses') }
    ]"
  >
    <div class="space-y-6 pb-24 font-['Inter','Kantumruy_Pro',sans-serif]">
      
      <!-- 1. PAGE HEADER (Title, Subtitle & Search Bar) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
            {{ t('វគ្គសិក្សាដែលបានចុះឈ្មោះ', 'Enrolled Courses') }}
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 font-['Kantumruy_Pro',sans-serif]">
            {{ t('វគ្គសិក្សាទាំងអស់ដែលអ្នកបានចុះឈ្មោះចូលរៀនដោយជោគជ័យ។', 'All courses you have successfully enrolled in.') }}
          </p>
        </div>

        <!-- In-Page Search Box (Shortcut key '/') -->
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
              :placeholder="t('ស្វែងរកវគ្គសិក្សា...', 'Search courses...')"
              class="w-full pl-10 pr-12 py-2 rounded-xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 focus:border-purple-500 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-purple-500/50 shadow-xs dark:shadow-inner transition-all"
            />
            <div class="absolute right-2.5 flex items-center gap-1">
              <button
                v-if="searchQuery"
                @click="searchQuery = ''; triggerServerFilter()"
                type="button"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-0.5 text-xs cursor-pointer"
                :title="t('លុបការស្វែងរក', 'Clear Search')"
              >
                ✕
              </button>
              <kbd class="hidden sm:inline-flex items-center justify-center w-5 h-5 text-[11px] font-mono font-bold text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded shadow-xs" :title="t('ចុច \'/\' ដើម្បីស្វែងរក', 'Press \'/\' to search')">/</kbd>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. FILTER CONTROLS & TABS ROW -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800/80 pb-4">
        
        <!-- Filter Tabs (Horizontal scroll on mobile) -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
          <button
            @click="changeTab('all')"
            type="button"
            :class="[
              activeTab === 'all'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs shrink-0'
            ]"
          >
            <span>{{ t('វគ្គសិក្សាទាំងអស់', 'All Courses') }}</span>
            <span :class="[activeTab === 'all' ? 'bg-purple-700/80 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold tabular-nums']">
              {{ counts.all }}
            </span>
          </button>

          <button
            @click="changeTab('in_progress')"
            type="button"
            :class="[
              activeTab === 'in_progress'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs shrink-0'
            ]"
          >
            <span>{{ t('កំពុងសិក្សា', 'In Progress') }}</span>
            <span :class="[activeTab === 'in_progress' ? 'bg-purple-700/80 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold tabular-nums']">
              {{ counts.in_progress }}
            </span>
          </button>

          <button
            @click="changeTab('not_started')"
            type="button"
            :class="[
              activeTab === 'not_started'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs shrink-0'
            ]"
          >
            <span>{{ t('មិនទាន់ចាប់ផ្តើម', 'Not Started') }}</span>
            <span :class="[activeTab === 'not_started' ? 'bg-purple-700/80 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold tabular-nums']">
              {{ counts.not_started }}
            </span>
          </button>

          <button
            @click="changeTab('completed')"
            type="button"
            :class="[
              activeTab === 'completed'
                ? 'bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white font-bold shadow-md shadow-purple-600/20'
                : 'bg-white dark:bg-[#1e1f20] text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-[#282a2c] border border-[#dfe3e7] dark:border-[#2d2f31]',
              'px-3.5 py-1.5 rounded-full text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500'
            ]"
          >
            <span>{{ t('បានបញ្ចប់', 'Completed') }}</span>
            <span :class="[activeTab === 'completed' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300', 'px-1.5 py-0.5 rounded-full text-[10px] font-bold tabular-nums']">
              {{ counts.completed }}
            </span>
          </button>

          <button
            v-if="(counts.coming_soon || 0) > 0"
            @click="changeTab('coming_soon')"
            type="button"
            :class="[
              activeTab === 'coming_soon'
                ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/20'
                : 'bg-white dark:bg-[#1e1f20] text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-[#282a2c] border border-[#dfe3e7] dark:border-[#2d2f31]',
              'px-3.5 py-1.5 rounded-full text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500'
            ]"
          >
            <span>{{ t('នឹងមកដល់ឆាប់ៗ', 'Coming Soon') }}</span>
            <span :class="[activeTab === 'coming_soon' ? 'bg-white/20 text-white' : 'bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200', 'px-1.5 py-0.5 rounded-full text-[10px] font-bold tabular-nums']">
              {{ counts.coming_soon }}
            </span>
          </button>
        </div>

        <!-- Right Side: Category, Sort Dropdown & View Mode Switcher -->
        <div class="flex flex-wrap items-center gap-2.5 self-start lg:self-auto">
          
          <!-- Category Filter -->
          <div class="relative">
            <select
              v-model="selectedCategory"
              @change="onCategoryChange"
              class="appearance-none bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl pl-3 pr-8 py-1.5 focus:outline-none focus:ring-1 focus:ring-purple-500/50 cursor-pointer shadow-xs"
            >
              <option value="all">{{ t('គ្រប់ប្រភេទ', 'All Categories') }}</option>
              <option v-for="cat in availableCategoryOptions" :key="cat.value" :value="cat.value">
                {{ currentLang === 'km' ? (cat.label_km || cat.label_en) : cat.label_en }}
              </option>
            </select>
            <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 dark:text-slate-500 text-xs">
              ▼
            </div>
          </div>

          <!-- Sort Filter -->
          <div class="relative">
            <select
              v-model="selectedSort"
              @change="onSortChange"
              class="appearance-none bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl pl-3 pr-8 py-1.5 focus:outline-none focus:ring-1 focus:ring-purple-500/50 cursor-pointer shadow-xs"
            >
              <option value="newest">{{ t('តម្រៀបតាម៖ ថ្មីបំផុត', 'Sort by: Newest') }}</option>
              <option value="oldest">{{ t('តម្រៀបតាម៖ ចាស់បំផុត', 'Sort by: Oldest') }}</option>
              <option value="progress">{{ t('វឌ្ឍនភាពខ្ពស់បំផុត', 'Highest Progress') }}</option>
              <option value="title">{{ t('ឈ្មោះវគ្គសិក្សា (A-Z)', 'Course Name (A-Z)') }}</option>
              <option value="recent">{{ t('ចូលមើលថ្មីៗ', 'Recently Accessed') }}</option>
            </select>
            <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 dark:text-slate-500 text-xs">
              ▼
            </div>
          </div>

          <!-- Grid / List Switcher -->
          <div class="inline-flex items-center gap-0.5 rounded-xl bg-slate-100 dark:bg-[#282a2c] border border-slate-200/90 dark:border-[#3c4043] p-1 shadow-2xs shrink-0">
            <button
              @click="setViewMode('grid')"
              @mouseup="($event.currentTarget as HTMLElement)?.blur()"
              type="button"
              :aria-pressed="viewMode === 'grid' ? 'true' : 'false'"
              :aria-label="t('ទម្រង់ក្រឡា', 'Grid View')"
              :class="[
                viewMode === 'grid' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                'w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/70'
              ]"
              :title="t('ទម្រង់ក្រឡា', 'Grid View')"
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
              :aria-label="t('ទម្រង់បញ្ជី', 'List View')"
              :class="[
                viewMode === 'list' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                'w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/70'
              ]"
              :title="t('ទម្រង់បញ្ជី', 'List View')"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>

        </div>
      </div>

      <!-- 3. COURSE CARDS CONTAINER (GRID OR LIST) -->
      <div v-if="courses.length > 0">
        
        <!-- GRID VIEW (Matching Dashboard #3 clean surface & light/dark parity) -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div
            v-for="course in courses"
            :key="course.id"
            @click="navigateToCourseOverview(course)"
            class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 hover:dark:border-purple-400/60 rounded-3xl overflow-hidden shadow-sm dark:shadow-xl transition-all duration-200 hover:-translate-y-1 group flex flex-col justify-between cursor-pointer relative"
          >
            <!-- TOP CARD HEADER / THUMBNAIL OR 3D ILLUSTRATION BOX -->
            <div class="relative w-full h-48 border-b border-slate-100 dark:border-white/5">
              
              <!-- Inner visual wrapper with overflow-hidden for image & glow -->
              <div class="absolute inset-0 bg-slate-900 dark:bg-[#131314] flex items-center justify-center overflow-hidden">
                <!-- Real Image Thumbnail if available -->
                <div v-if="course.thumbnail" class="w-full h-full relative overflow-hidden">
                  <img
                    :src="course.thumbnail"
                    :alt="localizedCourseTitle(course)"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    @error="(e: any) => { e.target.style.display = 'none'; (course as any)._thumbError = true }"
                  />
                  <div class="absolute inset-0 bg-gradient-to-b from-black/35 via-transparent to-black/15 pointer-events-none"></div>
                </div>

                <!-- Ambient Glow & Radial Gradient Fallback -->
                <template v-if="!course.thumbnail || (course as any)._thumbError">
                  <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 dark:from-[#0c1222] via-transparent to-transparent z-10"></div>
                  <div
                    class="absolute -inset-10 opacity-30 blur-2xl transition-all group-hover:opacity-50"
                    :class="[
                      course.illustrationType === 'web' ? 'bg-purple-600' :
                      course.illustrationType === 'database' ? 'bg-indigo-600' :
                      course.illustrationType === 'python' ? 'bg-blue-600' : 'bg-emerald-600'
                    ]"
                  ></div>

                  <!-- 1. Web Development (Laptop with HTML, CSS, JS cubes) -->
                  <div v-if="course.illustrationType === 'web'" class="relative flex items-center justify-center scale-95 group-hover:scale-105 transition-transform duration-300">
                    <div class="w-44 h-28 rounded-lg bg-slate-900 border-2 border-indigo-500/50 shadow-2xl flex flex-col p-2 relative overflow-hidden bg-radial from-slate-900 to-slate-950">
                      <div class="flex items-center gap-1 mb-1 border-b border-slate-800 pb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span class="text-[8px] text-slate-500 font-mono ml-1">app.js</span>
                      </div>
                      <div class="space-y-1 font-mono text-[8px] text-slate-400">
                        <div class="text-purple-400">const app = Vue.createApp()</div>
                        <div class="text-cyan-400">app.use(router).mount('#app')</div>
                        <div class="text-emerald-400">// Happy Coding!</div>
                      </div>
                      <div class="absolute -right-2 top-3 flex flex-col gap-1.5 z-20">
                        <span class="px-2 py-0.5 bg-orange-600 text-white font-black text-[10px] rounded shadow-lg ring-1 ring-orange-400 transform rotate-6 animate-pulse">HTML</span>
                        <span class="px-2 py-0.5 bg-blue-600 text-white font-black text-[10px] rounded shadow-lg ring-1 ring-blue-400 transform -rotate-3">CSS</span>
                        <span class="px-2 py-0.5 bg-amber-500 text-slate-950 font-black text-[10px] rounded shadow-lg ring-1 ring-amber-300 transform rotate-12">JS</span>
                      </div>
                    </div>
                    <div class="absolute -bottom-2 w-52 h-2.5 bg-slate-800 rounded-b-xl border-t border-slate-700 shadow-md"></div>
                  </div>

                  <!-- 2. Database Systems -->
                  <div v-else-if="course.illustrationType === 'database'" class="relative flex items-center justify-center gap-4 scale-95 group-hover:scale-105 transition-transform duration-300">
                    <div class="flex flex-col gap-1 items-center">
                      <div class="w-16 h-5 rounded-full bg-orange-500/80 border border-orange-400 shadow-lg shadow-orange-500/50 flex items-center justify-center">
                        <span class="w-2 h-1 bg-white/70 rounded-full"></span>
                      </div>
                      <div class="w-16 h-5 rounded-full bg-orange-600/90 border border-orange-500 shadow-md flex items-center justify-center"></div>
                      <div class="w-16 h-5 rounded-full bg-orange-700 border border-orange-600 shadow-sm flex items-center justify-center"></div>
                    </div>
                    <div class="flex flex-col gap-1 items-center">
                      <div class="w-16 h-5 rounded-full bg-purple-500/80 border border-purple-400 shadow-lg shadow-purple-500/50 flex items-center justify-center">
                        <span class="w-2 h-1 bg-white/70 rounded-full"></span>
                      </div>
                      <div class="w-16 h-5 rounded-full bg-purple-600/90 border border-purple-500 shadow-md flex items-center justify-center"></div>
                      <div class="w-16 h-5 rounded-full bg-purple-700 border border-purple-600 shadow-sm flex items-center justify-center"></div>
                    </div>
                    <div class="flex flex-col gap-1 items-center">
                      <div class="w-16 h-5 rounded-full bg-cyan-500/80 border border-cyan-400 shadow-lg shadow-cyan-500/50 flex items-center justify-center">
                        <span class="w-2 h-1 bg-white/70 rounded-full"></span>
                      </div>
                      <div class="w-16 h-5 rounded-full bg-cyan-600/90 border border-cyan-500 shadow-md flex items-center justify-center"></div>
                      <div class="w-16 h-5 rounded-full bg-cyan-700 border border-cyan-600 shadow-sm flex items-center justify-center"></div>
                    </div>
                  </div>

                  <!-- 3. Python / AI Programming -->
                  <div v-else-if="course.illustrationType === 'python'" class="relative flex items-center justify-center scale-100 group-hover:scale-110 transition-transform duration-300">
                    <div class="relative w-24 h-24 flex items-center justify-center">
                      <div class="absolute top-1 left-2 w-14 h-12 bg-gradient-to-br from-blue-500 to-blue-700 rounded-t-2xl rounded-l-2xl shadow-lg shadow-blue-500/40 border border-blue-400/50 flex items-start justify-end p-2">
                        <span class="w-2 h-2 rounded-full bg-white shadow-xs"></span>
                      </div>
                      <div class="absolute bottom-1 right-2 w-14 h-12 bg-gradient-to-br from-amber-400 to-amber-600 rounded-b-2xl rounded-r-2xl shadow-lg shadow-amber-500/40 border border-amber-300/50 flex items-end justify-start p-2">
                        <span class="w-2 h-2 rounded-full bg-slate-900 shadow-xs"></span>
                      </div>
                    </div>
                  </div>

                  <!-- 4. UI/UX & Design -->
                  <div v-else class="relative flex items-center justify-center gap-3 scale-95 group-hover:scale-105 transition-transform duration-300">
                    <div class="w-20 h-28 rounded-xl bg-slate-900 border-2 border-purple-500/60 shadow-xl p-1.5 flex flex-col justify-between">
                      <div class="w-4 h-1 bg-slate-700 rounded-full mx-auto"></div>
                      <div class="w-8 h-8 rounded-full bg-purple-600/30 border border-purple-500 flex items-center justify-center mx-auto text-[9px] text-purple-300 font-bold">
                        ✓
                      </div>
                      <div class="space-y-1">
                        <div class="w-full h-1 bg-slate-700 rounded"></div>
                        <div class="w-2/3 h-1 bg-purple-500/40 rounded"></div>
                      </div>
                    </div>
                    <div class="w-28 h-28 rounded-2xl bg-gradient-to-br from-purple-900/60 to-slate-900 border border-purple-500/50 shadow-2xl flex flex-col items-center justify-center p-2">
                      <span class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-pink-400 to-cyan-400">UI/UX</span>
                      <span class="text-[9px] text-purple-300 font-mono mt-1">Design System</span>
                    </div>
                  </div>
                </template>
              </div>

              <!-- Status Badges (Top-Left) -->
              <div class="absolute top-4 left-4 z-20 flex items-center gap-2">
                <!-- NEW Badge -->
                <span
                  v-if="course.is_new"
                  class="px-2.5 py-1 rounded-full text-xs font-black bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-md shadow-pink-600/30 flex items-center gap-1.5 animate-pulse"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                  {{ t('ថ្មី', 'NEW') }}
                </span>

                <!-- Coming Soon Badge (Solid high-contrast Amber) -->
                <span
                  v-if="course.status === 'coming_soon' || course.status === 'no_content'"
                  class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 dark:bg-amber-900/95 dark:text-amber-200 dark:border-amber-700 shadow-md"
                >
                  {{ t('នឹងមកដល់ឆាប់ៗ', 'Coming soon') }}
                </span>

                <!-- Not Started Badge (Slate / Gray) -->
                <span
                  v-else-if="course.status === 'not_started'"
                  class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800/90 text-slate-200 border border-slate-700/60 shadow-md backdrop-blur-md"
                >
                  {{ t('មិនទាន់ចាប់ផ្តើម', 'Not Started') }}
                </span>

                <!-- In Progress Badge (Purple) -->
                <span
                  v-else-if="course.status === 'in_progress'"
                  class="px-3 py-1 rounded-full text-xs font-bold bg-purple-600/95 text-white shadow-md shadow-purple-600/30 backdrop-blur-md"
                >
                  {{ t('កំពុងសិក្សា', 'In Progress') }}
                </span>

                <!-- Completed Badge (Emerald Green) -->
                <span
                  v-else-if="course.status === 'completed'"
                  class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-600/95 text-white shadow-md shadow-emerald-600/30 backdrop-blur-md"
                >
                  {{ t('បានបញ្ចប់', 'Completed') }}
                </span>
              </div>

              <!-- 3-Dots Context Menu Button (Top-Right) -->
              <div class="absolute top-4 right-4 z-30" @click.stop>
                <button
                  @click="toggleDropdown(course.id, $event)"
                  type="button"
                  class="p-2 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-700/50 backdrop-blur-md transition-colors cursor-pointer min-w-[36px] min-h-[36px] flex items-center justify-center"
                  :title="t('ជម្រើសវគ្គសិក្សា', 'Course Options')"
                  :aria-expanded="activeDropdownId === course.id"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                  </svg>
                </button>

                <!-- Context Dropdown Menu (Z-50, Overflow Safe) -->
                <div
                  v-show="activeDropdownId === course.id"
                  class="absolute right-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-1.5 z-50 space-y-1 text-xs"
                >
                  <Link
                    :href="course.overviewHref"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-purple-50 dark:hover:bg-purple-600/20 hover:text-purple-600 dark:hover:text-purple-300 font-medium transition-colors"
                  >
                    <span>ℹ️</span>
                    <span>{{ t('ព័ត៌មានលម្អិតវគ្គសិក្សា', 'Course Details') }}</span>
                  </Link>

                  <Link
                    v-if="course.status !== 'coming_soon' && course.status !== 'no_content'"
                    :href="course.learningHref"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-purple-50 dark:hover:bg-purple-600/20 hover:text-purple-600 dark:hover:text-purple-300 font-medium transition-colors"
                  >
                    <span>▶️</span>
                    <span>{{ course.status === 'not_started' ? t('ចាប់ផ្តើមរៀន', 'Start Learning') : (course.status === 'completed' ? t('ពិនិត្យវគ្គសិក្សាឡើងវិញ', 'Review Course') : t('បន្តការសិក្សា', 'Continue Learning')) }}</span>
                  </Link>

                  <Link
                    v-if="course.status === 'completed' && course.has_certificate && course.certificateHref"
                    :href="course.certificateHref"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-600/20 font-medium transition-colors"
                  >
                    <span>🏆</span>
                    <span>{{ t('ទាញយកវិញ្ញាបនបត្រ', 'Download Certificate') }}</span>
                  </Link>

                  <button
                    @click="copyCourseLink(course)"
                    type="button"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium text-left cursor-pointer transition-colors"
                  >
                    <span class="flex items-center gap-2.5">
                      <span>🔗</span>
                      <span>{{ copyLinkSuccessId === course.id ? t('បានចម្លងតំណរួចរាល់!', 'Copied to Clipboard!') : t('ចម្លងតំណវគ្គសិក្សា', 'Copy Course Link') }}</span>
                    </span>
                    <span v-if="copyLinkSuccessId === course.id" class="text-purple-600 dark:text-purple-400 font-bold">✓</span>
                  </button>

                  <button
                    @click="handleSaveCourseOffline(course)"
                    type="button"
                    :disabled="savingOfflineId === course.id"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-purple-50 dark:hover:bg-purple-600/20 hover:text-purple-600 dark:hover:text-purple-300 font-medium text-left cursor-pointer transition-colors disabled:opacity-60"
                  >
                    <span class="flex items-center gap-2.5">
                      <span>{{ cachedCourseIds.includes(course.id) ? '✅' : '📥' }}</span>
                      <span>{{ savingOfflineId === course.id ? t('កំពុងរក្សាទុក...', 'Saving...') : (cachedCourseIds.includes(course.id) ? t('បានរក្សាទុកសម្រាប់ Offline', 'Saved Offline') : t('រក្សាទុកសម្រាប់ Offline', 'Save for Offline')) }}</span>
                    </span>
                    <span v-if="cachedCourseIds.includes(course.id)" class="text-emerald-600 dark:text-emerald-400 font-bold">✓</span>
                  </button>
                </div>
              </div>

            </div>

            <!-- CARD BODY CONTENT -->
            <div class="p-6 space-y-4 flex-1 flex flex-col justify-between">
              
              <div class="space-y-3">
                <!-- Title & Teacher Row -->
                <div>
                  <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors leading-snug line-clamp-2">
                    {{ localizedCourseTitle(course) }}
                  </h3>

                  <div class="flex items-center gap-2 mt-2">
                    <div class="w-6 h-6 rounded-full overflow-hidden shrink-0 border border-purple-500/40 flex items-center justify-center bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300 text-[10px] font-bold">
                      <img
                        v-if="course.teacher.avatar && !(course as any)._avatarError"
                        :src="course.teacher.avatar"
                        :alt="localizedTeacherName(course.teacher)"
                        loading="lazy"
                        decoding="async"
                        class="w-full h-full object-cover"
                        @error="(course as any)._avatarError = true"
                      />
                      <span v-else>{{ getInitials(localizedTeacherName(course.teacher)) }}</span>
                    </div>
                    <Link
                      :href="course.overviewHref"
                      @click.stop
                      class="text-xs text-slate-600 dark:text-slate-300 font-medium hover:text-purple-600 dark:hover:text-purple-400 hover:underline transition-colors truncate max-w-[150px]"
                    >
                      {{ localizedTeacherName(course.teacher) }}
                    </Link>
                    <span
                      class="text-[11px] text-purple-600 dark:text-purple-400 font-medium ml-auto flex items-center gap-1 shrink-0 font-['Kantumruy_Pro',sans-serif]"
                    >
                      {{ formatEnrollmentMeta(course) }}
                    </span>
                  </div>
                </div>

                <!-- Progress Bar & Percentage -->
                <div class="space-y-1.5 pt-1">
                  <div class="flex items-center justify-between text-xs font-bold">
                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">
                      {{ course.status === 'completed' ? t('បានបញ្ចប់វគ្គសិក្សា', 'Course Completed') : (course.status === 'not_started' ? t('មិនទាន់ចាប់ផ្តើម', 'Not Started') : t('វឌ្ឍនភាពសិក្សា', 'Progress')) }}
                    </span>
                    <span
                      :class="[
                        course.status === 'completed' ? 'text-emerald-600 dark:text-emerald-400 font-black' :
                        course.status === 'in_progress' ? 'text-purple-600 dark:text-purple-400 font-black' :
                        (course.status === 'coming_soon' || course.status === 'no_content') ? 'text-amber-700 dark:text-amber-300 font-bold' :
                        'text-slate-500 dark:text-slate-400 font-bold'
                      ]"
                      class="tabular-nums"
                    >
                      {{ course.progress_percentage ?? course.progress }}%
                    </span>
                  </div>

                  <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800/80 overflow-hidden">
                    <div
                      :class="[
                        course.status === 'completed'
                          ? 'bg-emerald-500'
                          : (course.status === 'in_progress' ? 'bg-gradient-to-r from-purple-600 via-indigo-500 to-purple-400' : 'bg-slate-300 dark:bg-slate-700'),
                        'h-full rounded-full transition-all duration-500'
                      ]"
                      :style="{ width: (course.progress_percentage ?? course.progress) + '%' }"
                    ></div>
                  </div>
                </div>

                <!-- Metadata Row (Chapter, Lessons, Last Activity) -->
                <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800/60 mt-2 font-['Kantumruy_Pro',sans-serif] tabular-nums">
                  <template v-if="course.totalLessons === 0">
                    <div class="flex items-center gap-1.5">
                      <span>📖</span>
                      <span>{{ t('ទិដ្ឋភាពទូទៅវគ្គសិក្សា', 'Course Overview') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <span>📑</span>
                      <span>{{ t('0 មេរៀន', '0 Lessons') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <span>⏱</span>
                      <span>{{ t('មិនទាន់ចាប់ផ្តើមនៅឡើយ', 'Not started yet') }}</span>
                    </div>
                  </template>
                  <template v-else>
                    <div class="flex items-center gap-1.5">
                      <span>📖</span>
                      <span>{{ currentLang === 'km' ? `ជំពូក/ម៉ូឌុល ${course.currentModuleIndex || 1} នៃ ${course.totalChapters}` : `Chapter/Module ${course.currentModuleIndex || 1} of ${course.totalChapters}` }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <span>📑</span>
                      <span>{{ course.completedLessons }} / {{ course.totalLessons }} {{ t('មេរៀន', 'Lessons') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <span>⏱</span>
                      <span>{{ localizedLastAccessed(course.lastAccessed) }}</span>
                    </div>
                  </template>
                </div>
              </div>

              <!-- ACTION BUTTONS ROW -->
              <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100 dark:border-slate-800/80" @click.stop>
                
                <!-- Main Action Button -->
                <Link
                  v-if="course.status === 'coming_soon' || course.status === 'no_content'"
                  :href="course.overviewHref"
                  class="col-span-2 py-2.5 px-3 rounded-xl bg-white dark:bg-[#1e1f20] hover:bg-slate-50 dark:hover:bg-[#282a2c] text-slate-700 dark:text-slate-200 font-semibold text-xs border border-[#dfe3e7] dark:border-[#2d2f31] flex items-center justify-center gap-1.5 transition-all text-center cursor-pointer min-h-[48px] sm:min-h-[44px] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  <span>ⓘ</span>
                  <span>{{ t('ព័ត៌មានលម្អិតវគ្គសិក្សា', 'Course Details') }}</span>
                </Link>

                <Link
                  v-else-if="course.status === 'in_progress'"
                  :href="course.learningHref"
                  class="py-2.5 px-3 rounded-xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:opacity-95 text-white font-bold text-xs shadow-md shadow-purple-600/20 flex items-center justify-center gap-1.5 transition-all text-center cursor-pointer min-h-[48px] sm:min-h-[44px] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  <span>▶</span>
                  <span>{{ t('បន្តការសិក្សា', 'Continue Learning') }}</span>
                </Link>

                <Link
                  v-else-if="course.status === 'not_started'"
                  :href="course.learningHref"
                  class="py-2.5 px-3 rounded-xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:opacity-95 text-white font-bold text-xs shadow-md shadow-purple-600/20 flex items-center justify-center gap-1.5 transition-all text-center cursor-pointer min-h-[48px] sm:min-h-[44px] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  <span>▶</span>
                  <span>{{ t('ចូលរៀន', 'Start Learning') }}</span>
                </Link>

                <Link
                  v-else-if="course.status === 'completed'"
                  :href="course.learningHref"
                  class="py-2.5 px-3 rounded-xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:opacity-95 text-white font-bold text-xs shadow-md shadow-purple-600/20 flex items-center justify-center gap-1.5 transition-all text-center cursor-pointer min-h-[48px] sm:min-h-[44px] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  <span>👁</span>
                  <span>{{ t('ពិនិត្យវគ្គសិក្សាឡើងវិញ', 'Review Course') }}</span>
                </Link>

                <!-- Secondary Button (Course Details or Certificate) -->
                <template v-if="course.status !== 'coming_soon' && course.status !== 'no_content'">
                  <Link
                    v-if="course.status === 'completed' && course.has_certificate && course.certificateHref"
                    :href="course.certificateHref"
                    class="py-2.5 px-3 rounded-xl bg-emerald-500/10 dark:bg-emerald-600/20 hover:bg-emerald-500/20 dark:hover:bg-emerald-600/30 text-emerald-700 dark:text-emerald-300 font-bold text-xs border border-emerald-500/30 dark:border-emerald-500/40 flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer min-h-[44px]"
                  >
                    <span>🏆</span>
                    <span>{{ t('វិញ្ញាបនបត្រ', 'Certificate') }}</span>
                  </Link>

                  <Link
                    v-else
                    :href="course.overviewHref"
                    class="py-2.5 px-3 rounded-xl bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-semibold text-xs border border-slate-200 dark:border-slate-800 flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer min-h-[44px]"
                  >
                    <span>ⓘ</span>
                    <span>{{ t('ព័ត៌មានលម្អិតវគ្គសិក្សា', 'Course Details') }}</span>
                  </Link>
                </template>

              </div>

            </div>
          </div>
        </div>

        <!-- LIST VIEW -->
        <div v-else class="space-y-4">
          <div
            v-for="course in courses"
            :key="course.id"
            @click="navigateToCourseOverview(course)"
            class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 hover:dark:border-purple-400/60 rounded-3xl p-4 sm:p-5 shadow-sm dark:shadow-xl transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-4 cursor-pointer group"
          >
            <!-- Left Info -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 min-w-0 flex-1">
              <div class="w-full sm:w-44 aspect-video rounded-xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-center shrink-0 overflow-hidden">
                <img
                  v-if="course.thumbnail && !(course as any)._thumbError"
                  :src="course.thumbnail"
                  :alt="localizedCourseTitle(course)"
                  class="w-full h-full object-cover"
                  @error="(e: any) => { e.target.style.display = 'none'; (course as any)._thumbError = true }"
                />
                <span v-else class="text-2xl">
                  {{ course.illustrationType === 'web' ? '💻' : (course.illustrationType === 'database' ? '🗄' : (course.illustrationType === 'python' ? '🐍' : '🎨')) }}
                </span>
              </div>

              <div class="min-w-0 space-y-1">
                <div class="flex items-center gap-2">
                  <span
                    v-if="course.is_new"
                    class="px-2 py-0.5 rounded-full text-[10px] font-black bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-xs animate-pulse"
                  >
                    {{ t('ថ្មី', 'NEW') }}
                  </span>
                  <span
                    :class="[
                      course.status === 'completed' ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border-emerald-500/30' :
                      (course.status === 'coming_soon' || course.status === 'no_content') ? 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/90 dark:text-amber-200 dark:border-amber-700' :
                      course.status === 'not_started' ? 'bg-slate-500/10 dark:bg-slate-500/20 text-slate-700 dark:text-slate-400 border-slate-500/30' : 'bg-purple-500/10 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border-purple-500/30',
                      'px-2 py-0.5 rounded-full text-[10px] font-bold border'
                    ]"
                  >
                    {{ localizedStatusLabel(course) }}
                  </span>
                  <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">• {{ localizedCourseCategory(course) }}</span>
                </div>

                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate">
                  {{ localizedCourseTitle(course) }}
                </h3>

                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400 font-['Kantumruy_Pro',sans-serif] tabular-nums">
                  <span>{{ t('គ្រូបង្រៀន៖', 'Instructor:') }} <Link :href="course.overviewHref" @click.stop class="hover:text-purple-600 dark:hover:text-purple-400 hover:underline font-medium text-slate-700 dark:text-slate-200">{{ localizedTeacherName(course.teacher) }}</Link></span>
                  <span class="text-purple-600 dark:text-purple-400 font-medium">
                    • {{ formatEnrollmentMeta(course) }}
                  </span>
                  <span>•</span>
                  <span v-if="course.totalLessons > 0">{{ course.completedLessons }} / {{ course.totalLessons }} {{ t('មេរៀន', 'Lessons') }}</span>
                  <span v-else>{{ t('មិនទាន់មានមេរៀននៅឡើយទេ', 'No lessons yet') }}</span>
                </div>
              </div>
            </div>

            <!-- Right Progress & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 shrink-0" @click.stop>
              <div class="w-36 space-y-1">
                <div class="flex items-center justify-between text-xs font-bold tabular-nums">
                  <span class="text-slate-500 dark:text-slate-400 text-[10px]">{{ t('វឌ្ឍនភាពសិក្សា', 'Progress') }}</span>
                  <span :class="course.status === 'completed' ? 'text-emerald-600 dark:text-emerald-400' : (course.status === 'in_progress' ? 'text-purple-600 dark:text-purple-400' : 'text-slate-500 dark:text-slate-400')">
                    {{ course.progress_percentage ?? course.progress }}%
                  </span>
                </div>
                <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div
                    :class="course.status === 'completed' ? 'bg-emerald-500' : (course.status === 'in_progress' ? 'bg-purple-600' : 'bg-slate-300 dark:bg-slate-700')"
                    class="h-full rounded-full transition-all duration-300"
                    :style="{ width: (course.progress_percentage ?? course.progress) + '%' }"
                  ></div>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <Link
                  v-if="course.status === 'coming_soon' || course.status === 'no_content'"
                  :href="course.overviewHref"
                  class="px-4 py-2 rounded-xl bg-white dark:bg-[#1e1f20] hover:bg-slate-50 dark:hover:bg-[#282a2c] text-slate-700 dark:text-slate-200 font-semibold text-xs border border-[#dfe3e7] dark:border-[#2d2f31] cursor-pointer min-h-[38px] flex items-center focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  {{ t('ព័ត៌មានលម្អិត', 'Details') }}
                </Link>
                <Link
                  v-else-if="course.status === 'in_progress'"
                  :href="course.learningHref"
                  class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:opacity-95 text-white font-bold text-xs shadow-md shadow-purple-600/20 cursor-pointer min-h-[38px] flex items-center whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  {{ t('បន្តការសិក្សា', 'Continue Learning') }}
                </Link>
                <Link
                  v-else-if="course.status === 'not_started'"
                  :href="course.learningHref"
                  class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:opacity-95 text-white font-bold text-xs shadow-md shadow-purple-600/20 cursor-pointer min-h-[38px] flex items-center whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  {{ t('ចូលរៀន', 'Start Learning') }}
                </Link>
                <Link
                  v-else-if="course.status === 'completed'"
                  :href="course.learningHref"
                  class="px-4 py-2 rounded-xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:opacity-95 text-white font-bold text-xs shadow-md shadow-purple-600/20 cursor-pointer min-h-[38px] flex items-center whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  {{ t('ពិនិត្យឡើងវិញ', 'Review') }}
                </Link>

                <Link
                  v-if="course.status === 'completed' && course.has_certificate && course.certificateHref"
                  :href="course.certificateHref"
                  class="px-3.5 py-2 rounded-xl bg-emerald-500/10 dark:bg-emerald-600/20 hover:bg-emerald-500/20 dark:hover:bg-emerald-600/30 text-emerald-700 dark:text-emerald-300 font-bold text-xs border border-emerald-500/30 dark:border-emerald-500/40 cursor-pointer min-h-[38px] flex items-center gap-1 whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                >
                  <span>🏆</span>
                  <span>{{ t('វិញ្ញាបនបត្រ', 'Certificate') }}</span>
                </Link>

                <Link
                  :href="course.overviewHref"
                  :aria-label="currentLang === 'km' ? `ព័ត៌មានលម្អិតសម្រាប់ ${localizedCourseTitle(course)}` : `Course details for ${localizedCourseTitle(course)}`"
                  :title="t('ព័ត៌មានលម្អិតវគ្គសិក្សា', 'Course Details')"
                  class="p-2 rounded-xl bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 cursor-pointer min-w-[38px] min-h-[38px] flex items-center justify-center focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. PAGINATION FOOTER -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-6 border-t border-slate-200 dark:border-slate-800/80 text-xs text-slate-500 dark:text-slate-400 tabular-nums">
          <div>
            {{ currentLang === 'km' ? `កំពុងបង្ហាញ ${courses.length} នៃ ${counts.all} វគ្គសិក្សា` : `Showing ${courses.length} of ${counts.all} courses` }}
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

      <!-- EMPTY STATE -->
      <div
        v-else
        class="bg-white dark:bg-slate-900/40 border border-dashed border-slate-200 dark:border-slate-800 rounded-3xl p-10 sm:p-14 text-center space-y-4 shadow-xs relative overflow-hidden"
      >
        <!-- 3D Glowing Glass Magnifying Glass Illustration -->
        <div class="relative w-36 h-36 sm:w-44 sm:h-44 mx-auto flex items-center justify-center select-none py-1">
          <!-- Ambient Soft Radial Aura Glow -->
          <div class="absolute inset-2 rounded-full bg-gradient-to-tr from-cyan-400/25 via-sky-400/30 to-purple-500/25 dark:from-cyan-400/20 dark:via-sky-500/20 dark:to-purple-500/25 blur-2xl pointer-events-none transform scale-110"></div>
          
          <img
            :src="emptySearchImgUrl"
            alt="Search Empty"
            class="relative z-10 w-full h-full object-contain filter drop-shadow-[0_16px_32px_rgba(56,189,248,0.35)] dark:drop-shadow-[0_16px_36px_rgba(56,189,248,0.25)] transition-transform duration-500 hover:scale-105"
            loading="eager"
          />
        </div>

        <template v-if="counts.all === 0">
          <div class="space-y-1">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
              {{ t('មិនទាន់មានវគ្គសិក្សាដែលបានចុះឈ្មោះនៅឡើយទេ', 'No enrolled courses yet') }}
            </h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 max-w-sm mx-auto font-['Kantumruy_Pro',sans-serif]">
              {{ t('សូមស្វែងរក និងចុះឈ្មោះវគ្គសិក្សាដែលអ្នកចាប់អារម្មណ៍ដើម្បីចាប់ផ្តើមរៀន។', 'Browse our catalog and enroll in courses to start your learning journey.') }}
            </p>
          </div>
          <div class="flex flex-wrap items-center justify-center gap-3">
            <Link
              href="/student/browse"
              class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/20 transition-all cursor-pointer"
            >
              {{ t('ស្វែងរកវគ្គសិក្សា', 'Browse Courses') }}
            </Link>
            <button
              v-if="hasActiveFilters"
              @click="resetFilters"
              type="button"
              class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition-all cursor-pointer"
            >
              {{ t('កំណត់តម្រងឡើងវិញ', 'Reset All Filters') }}
            </button>
          </div>
        </template>

        <template v-else>
          <div class="space-y-1">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
              {{ t('រកមិនឃើញវគ្គសិក្សាដែលត្រូវតាមលក្ខខណ្ឌទេ', 'No courses match your criteria') }}
            </h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 max-w-sm mx-auto font-['Kantumruy_Pro',sans-serif]">
              {{ t('សូមសាកល្បងកែប្រែពាក្យស្វែងរក ឬលក្ខខណ្ឌតម្រងដើម្បីស្វែងរកវគ្គសិក្សារបស់អ្នក។', 'Try adjusting your search query or filter criteria to find the enrolled course you are looking for.') }}
            </p>
          </div>
          <button
            @click="resetFilters"
            type="button"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/20 transition-all cursor-pointer"
          >
            {{ t('កំណត់តម្រងឡើងវិញ', 'Reset All Filters') }}
          </button>
        </template>
      </div>

    </div>
  </StudentLayout>
</template>
