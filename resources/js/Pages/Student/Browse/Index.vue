<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

interface CatalogInstructor {
  name?: string | null
  name_kh?: string | null
  avatar?: string | null
  role?: string | null
}

interface CatalogCourse {
  id: number
  slug: string
  title: string
  titleKh?: string | null
  description?: string | null
  descriptionKh?: string | null
  thumbnail?: string | null
  majorId?: number | null
  category?: string | null
  categoryKh?: string | null
  level?: 'beginner' | 'intermediate' | 'advanced' | null
  levelLabel?: string | null
  lessonsCount: number
  lessonsDoneCount?: number
  durationSeconds: number
  duration?: string | null
  enrollmentsCount: number
  price: number
  priceDisplay: string
  isFree: boolean
  rating?: number | null
  reviewsCount: number
  isWishlisted: boolean
  isComingSoon: boolean
  cardState: 'not_enrolled' | 'enrolled' | 'completed' | 'coming_soon' | 'pending_payment'
  enrollmentStatus: string
  instructor: CatalogInstructor
  overviewHref: string
  courseDetailsHref?: string
  browseShowHref: string
  continueHref: string
  enrollHref: string
  checkoutHref: string
}

interface PaginatedCourses {
  data: CatalogCourse[]
  from: number | null
  to: number | null
  total: number
  current_page: number
  last_page: number
  per_page: number
  links: Array<{ url: string | null; label: string; active: boolean }>
}

interface CategoryFacet {
  id: number
  name: string
  name_kh?: string | null
  count: number
}

interface LevelFacet {
  key: 'beginner' | 'intermediate' | 'advanced'
  count: number
}

const props = defineProps<{
  courses: PaginatedCourses
  facets: {
    categories: CategoryFacet[]
    levels: LevelFacet[]
    prices: { all: number; free: number; paid: number }
    totalPublished: number
  }
  totalPublished: number
  filters: {
    q?: string
    category?: number[]
    level?: string[]
    price?: 'all' | 'free' | 'paid'
    sort?: 'popular' | 'newest' | 'title' | 'duration'
    view?: 'grid' | 'list'
    page?: number
  }
}>()

const { t, currentLang } = useLanguage()

// Filter states synchronized from server props
const searchQuery = ref(props.filters?.q || '')
const selectedCategories = ref<number[]>([...(props.filters?.category || [])].map(Number))
const selectedLevels = ref<string[]>([...(props.filters?.level || [])])
const selectedPrice = ref<'all' | 'free' | 'paid'>(props.filters?.price || 'all')
const selectedSort = ref<'popular' | 'newest' | 'title' | 'duration'>(props.filters?.sort || 'popular')
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

watch(() => props.filters, (f) => {
  if (!f) return
  searchQuery.value = f.q || ''
  selectedCategories.value = [...(f.category || [])].map(Number)
  selectedLevels.value = [...(f.level || [])]
  selectedPrice.value = f.price || 'all'
  selectedSort.value = f.sort || 'popular'
  if (typeof window !== 'undefined') {
    const urlView = new URLSearchParams(window.location.search).get('view')
    if (urlView === 'grid' || urlView === 'list') {
      if (urlView !== viewMode.value) viewMode.value = urlView
      try { localStorage.setItem('elms_courses_view', urlView) } catch (e) {}
      return
    }
  }
  if ((f.view === 'grid' || f.view === 'list') && f.view !== viewMode.value) {
    viewMode.value = f.view
    if (typeof window !== 'undefined') {
      try { localStorage.setItem('elms_courses_view', f.view) } catch (e) {}
    }
  }
}, { deep: true })

// UI & interaction state
const isLoading = ref(false)
const fetchError = ref(false)
const enrollingCourseId = ref<number | null>(null)
const enrolledOverrides = ref<Record<number, CatalogCourse['cardState']>>({})
const wishlistPending = ref<Record<number, boolean>>({})
const wishlistOverrides = ref<Record<number, boolean>>({})
const wishlistErrorMsg = ref<string | null>(null)
const thumbErrors = ref<Record<number, boolean>>({})
const thumbLoaded = ref<Record<number, boolean>>({})
const avatarErrors = ref<Record<number, boolean>>({})

const COURSE_CARD_THEMES = [
  {
    gradient: 'from-[#2563eb] via-[#3b82f6] to-[#4f46e5]',
    wavePath: 'M0 18 Q 20 6, 42 15 T 74 7 T 100 13 L 100 25 L 0 25 Z',
    waveLine: 'M0 18 Q 20 6, 42 15 T 74 7 T 100 13',
  },
  {
    gradient: 'from-[#0284c7] via-[#2563eb] to-[#4f46e5]',
    wavePath: 'M0 15 Q 25 22, 48 10 T 80 16 T 100 7 L 100 25 L 0 25 Z',
    waveLine: 'M0 15 Q 25 22, 48 10 T 80 16 T 100 7',
  },
  {
    gradient: 'from-[#6d28d9] via-[#7c3aed] to-[#4f46e5]',
    wavePath: 'M0 20 Q 18 8, 45 16 T 72 5 T 100 14 L 100 25 L 0 25 Z',
    waveLine: 'M0 20 Q 18 8, 45 16 T 72 5 T 100 14',
  },
  {
    gradient: 'from-[#7c3aed] via-[#8b5cf6] to-[#6366f1]',
    wavePath: 'M0 14 Q 22 4, 50 18 T 78 9 T 100 12 L 100 25 L 0 25 Z',
    waveLine: 'M0 14 Q 22 4, 50 18 T 78 9 T 100 12',
  },
  {
    gradient: 'from-[#1d4ed8] via-[#4338ca] to-[#3730a3]',
    wavePath: 'M0 17 Q 24 7, 46 15 T 76 6 T 100 11 L 100 25 L 0 25 Z',
    waveLine: 'M0 17 Q 24 7, 46 15 T 76 6 T 100 11',
  },
  {
    gradient: 'from-[#4f46e5] via-[#7c3aed] to-[#9333ea]',
    wavePath: 'M0 19 Q 20 9, 44 14 T 75 8 T 100 15 L 100 25 L 0 25 Z',
    waveLine: 'M0 19 Q 20 9, 44 14 T 75 8 T 100 15',
  },
]

const getCourseCardTheme = (courseId: number) => {
  return COURSE_CARD_THEMES[Math.abs(Number(courseId) || 0) % COURSE_CARD_THEMES.length]
}

const searchInputRef = ref<HTMLInputElement | null>(null)
const filterDrawerRef = ref<HTMLDialogElement | null>(null)
const filterDrawerSheetRef = ref<HTMLDivElement | null>(null)
const filterTriggerRef = ref<HTMLButtonElement | null>(null)
const isDrawerOpen = ref(false)

let debounceTimer: ReturnType<typeof setTimeout> | null = null
let activeCancelToken: { cancel?: () => void } | null = null

const catalogItems = computed<CatalogCourse[]>(() => {
  const raw = props.courses?.data || []
  return raw.map((c) => {
    const resolvedState = Object.prototype.hasOwnProperty.call(enrolledOverrides.value, c.id)
      ? enrolledOverrides.value[c.id]
      : c.cardState
    return {
      ...c,
      cardState: resolvedState,
      isWishlisted: Object.prototype.hasOwnProperty.call(wishlistOverrides.value, c.id)
        ? wishlistOverrides.value[c.id]
        : Boolean(c.isWishlisted),
    }
  })
})

const categoriesList = computed(() => props.facets?.categories || [])
const levelsList = computed(() => props.facets?.levels || [])
const priceCounts = computed(() => props.facets?.prices || { all: 0, free: 0, paid: 0 })

const activeFilterCount = computed(() => {
  let count = 0
  if (searchQuery.value.trim()) count += 1
  count += selectedCategories.value.length
  count += selectedLevels.value.length
  if (selectedPrice.value !== 'all') count += 1
  return count
})

const hasActiveFilters = computed(() => activeFilterCount.value > 0)

const getCsrfToken = (): string => {
  if (typeof document === 'undefined') return ''
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

const getInitials = (name?: string | null): string => {
  if (!name) return 'SP'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
}

const localizedCategoryName = (cat?: { name?: string | null; name_kh?: string | null } | null): string => {
  if (!cat) return ''
  return currentLang.value === 'km' ? (cat.name_kh || cat.name || '') : (cat.name || '')
}

const localizedCourseCategory = (course: CatalogCourse): string => {
  if (currentLang.value === 'km') {
    return course.categoryKh || course.category || ''
  }
  return course.category || ''
}

const localizedCourseTitle = (course: CatalogCourse): string => {
  if (currentLang.value === 'km') {
    return course.titleKh || course.title
  }
  return course.title
}

const localizedCourseDescription = (course: CatalogCourse): string => {
  if (currentLang.value === 'km') {
    return course.descriptionKh || course.description || ''
  }
  return course.description || ''
}

const localizedInstructorName = (instructor?: CatalogInstructor | null): string => {
  if (!instructor) return ''
  if (currentLang.value === 'km') {
    return instructor.name_kh || instructor.name || ''
  }
  return instructor.name || ''
}

const localizedDuration = (duration?: string | null): string => {
  if (!duration) return ''
  if (currentLang.value !== 'km') return duration
  return duration
    .replace(/(\d+)\s*h/g, '$1 ម៉ោង')
    .replace(/(\d+)\s*m/g, '$1 នាទី')
    .trim()
}

const localizedLevelLabel = (lvlKey?: string | null): string => {
  if (!lvlKey) return ''
  const key = lvlKey.toLowerCase()
  if (key === 'beginner') return t('browse_level_beginner')
  if (key === 'intermediate') return t('browse_level_intermediate')
  if (key === 'advanced') return t('browse_level_advanced')
  return lvlKey
}

const formatLessonsCount = (count: number): string => {
  return count === 1
    ? `1 ${t('browse_lessons_singular')}`
    : `${count} ${t('browse_lessons_plural')}`
}

// Navigation / Server filtering (resets page to 1 by default)
const fetchCatalog = (targetPage: number | null = 1, customUrl?: string) => {
  fetchError.value = false

  if (customUrl) {
    isLoading.value = true
    router.visit(customUrl, {
      preserveState: true,
      preserveScroll: true,
      headers: { 'X-Silent-Load': 'true' },
      onCancelToken: (token) => { activeCancelToken = token },
      onFinish: () => { isLoading.value = false },
      onError: () => { fetchError.value = true },
    })
    return
  }

  const params: Record<string, any> = {
    view: viewMode.value,
    sort: selectedSort.value,
  }
  if (searchQuery.value.trim()) {
    params.q = searchQuery.value.trim()
  }
  if (selectedCategories.value.length > 0) {
    params.category = [...selectedCategories.value]
  }
  if (selectedLevels.value.length > 0) {
    params.level = [...selectedLevels.value]
  }
  if (selectedPrice.value !== 'all') {
    params.price = selectedPrice.value
  }
  if (targetPage && targetPage > 1) {
    params.page = targetPage
  }

  isLoading.value = true
  router.get('/student/browse', params, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
    headers: { 'X-Silent-Load': 'true' },
    onCancelToken: (token) => { activeCancelToken = token },
    onFinish: () => { isLoading.value = false },
    onError: () => { fetchError.value = true },
  })
}

const onSearchInput = () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchCatalog(1)
  }, 180)
}

const clearSearch = () => {
  searchQuery.value = ''
  if (debounceTimer) clearTimeout(debounceTimer)
  fetchCatalog(1)
}

const toggleTopCategory = (categoryId: number | 'all') => {
  if (categoryId === 'all') {
    selectedCategories.value = []
  } else {
    selectedCategories.value = [categoryId]
  }
  fetchCatalog(1)
}

const onSidebarFilterChange = () => {
  fetchCatalog(1)
}

const removeCategoryChip = (catId: number) => {
  selectedCategories.value = selectedCategories.value.filter((id) => id !== catId)
  fetchCatalog(1)
}

const removeLevelChip = (lvl: string) => {
  selectedLevels.value = selectedLevels.value.filter((l) => l !== lvl)
  fetchCatalog(1)
}

const resetPriceFilter = () => {
  selectedPrice.value = 'all'
  fetchCatalog(1)
}

const clearAllFilters = () => {
  searchQuery.value = ''
  selectedCategories.value = []
  selectedLevels.value = []
  selectedPrice.value = 'all'
  if (debounceTimer) clearTimeout(debounceTimer)
  fetchCatalog(1)
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
    fetch('/student/preferences', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ courses_view: mode }),
    }).catch(() => {})
  } catch (e) {}
}

// Optimistic Wishlist toggle with rollback on failure
const toggleWishlist = async (course: CatalogCourse) => {
  if (wishlistPending.value[course.id]) return
  wishlistErrorMsg.value = null

  const previousState = course.isWishlisted
  const nextState = !previousState

  wishlistOverrides.value[course.id] = nextState
  wishlistPending.value[course.id] = true

  try {
    const res = await fetch(`/student/courses/${course.id}/wishlist`, {
      method: nextState ? 'POST' : 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
      body: nextState ? JSON.stringify({}) : undefined,
    })

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`)
    }
    const payload = await res.json().catch(() => null)
    if (payload && typeof payload.wishlisted === 'boolean') {
      wishlistOverrides.value[course.id] = payload.wishlisted
    }
  } catch (e) {
    wishlistOverrides.value[course.id] = previousState
    wishlistErrorMsg.value = t('browse_wishlist_error')
  } finally {
    wishlistPending.value[course.id] = false
  }
}

// Enroll action (Free -> POST enroll, Paid -> Checkout redirect)
const handleEnroll = (course: CatalogCourse) => {
  if (course.isComingSoon || enrollingCourseId.value === course.id) return

  if (!course.isFree && course.price > 0) {
    router.visit(course.checkoutHref)
    return
  }

  enrollingCourseId.value = course.id
  router.post(course.enrollHref, {}, {
    preserveScroll: true,
    onSuccess: () => {
      enrolledOverrides.value[course.id] = 'enrolled'
    },
    onFinish: () => {
      enrollingCourseId.value = null
    },
  })
}

// Mobile Filter Drawer (<dialog>.showModal() per modern-web-guidance)
const openFilterDrawer = () => {
  const dlg = filterDrawerRef.value
  if (!dlg) return
  isDrawerOpen.value = true
  if (typeof dlg.showModal === 'function') {
    if (!dlg.open) dlg.showModal()
  } else {
    dlg.setAttribute('open', 'true')
  }
}

const closeFilterDrawer = () => {
  const dlg = filterDrawerRef.value
  isDrawerOpen.value = false
  if (dlg && dlg.open && typeof dlg.close === 'function') {
    dlg.close()
  } else if (dlg) {
    dlg.removeAttribute('open')
  }
  filterTriggerRef.value?.focus()
}

const onDrawerBackdropClick = (e: MouseEvent) => {
  const sheet = filterDrawerSheetRef.value
  if (sheet && !sheet.contains(e.target as Node)) {
    closeFilterDrawer()
  }
}

const onDrawerCancel = (e: Event) => {
  e.preventDefault()
  closeFilterDrawer()
}

const formatPaginationLabel = (label: string): string => {
  if (label.includes('Previous') || label.includes('laquo')) return '‹'
  if (label.includes('Next') || label.includes('raquo')) return '›'
  return label
}

const handleGlobalKeyDown = (e: KeyboardEvent) => {
  const tag = (e.target as HTMLElement)?.tagName
  const isTyping = ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || Boolean((e.target as HTMLElement)?.isContentEditable)

  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    searchInputRef.value?.focus()
  } else if (e.key === '/' && !isTyping) {
    e.preventDefault()
    searchInputRef.value?.focus()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleGlobalKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeyDown)
  if (debounceTimer) clearTimeout(debounceTimer)
  if (activeCancelToken?.cancel) activeCancelToken.cancel()
})
</script>

<template>
  <StudentLayout
    :title="t('browse_title')"
    :breadcrumbs="[
      { label: t('nav_dashboard'), href: '/student/dashboard' },
      { label: t('nav_my_courses'), href: '/student/my-courses/enrolled' },
      { label: t('browse_title') }
    ]"
  >
    <div class="space-y-6 pb-24 font-['Inter','Kantumruy_Pro',sans-serif]">
      <!-- 1. PAGE HEADER + SINGLE SEARCH INPUT -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
            {{ t('browse_title') }}
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
            {{ t('browse_subtitle') }}
          </p>
        </div>

        <!-- Single In-Page Search Box (shortcut '/') -->
        <div class="relative w-full md:w-80 shrink-0">
          <label for="browse-catalog-search" class="sr-only">{{ t('browse_search_placeholder') }}</label>
          <div class="relative flex items-center">
            <span class="absolute left-3.5 text-slate-400 pointer-events-none" aria-hidden="true">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              id="browse-catalog-search"
              ref="searchInputRef"
              v-model="searchQuery"
              @input="onSearchInput"
              type="search"
              maxlength="100"
              :placeholder="t('browse_search_placeholder')"
              class="w-full pl-10 pr-12 py-2.5 rounded-xl bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] focus:border-purple-500 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 shadow-xs transition-colors"
            />
            <div class="absolute right-2.5 flex items-center gap-1">
              <button
                v-if="searchQuery"
                @click="clearSearch"
                type="button"
                class="min-w-[24px] min-h-[24px] flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer"
                :aria-label="t('browse_clear_search')"
              >
                ✕
              </button>
              <kbd
                v-else
                class="hidden sm:inline-flex items-center justify-center w-5 h-5 text-[11px] font-mono font-bold text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-[#282a2c] border border-slate-200 dark:border-[#3c4043] rounded"
                :title="t('browse_search_shortcut_title')"
                aria-hidden="true"
              >/</kbd>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. TOP CATEGORY PILLS (Real Majors from DB) -->
      <nav aria-label="Course Categories" class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 pt-0.5">
        <button
          @click="toggleTopCategory('all')"
          type="button"
          :aria-pressed="selectedCategories.length === 0 ? 'true' : 'false'"
          :class="[
            selectedCategories.length === 0
              ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20 border-transparent'
              : 'bg-white dark:bg-[#1e1f20] hover:bg-slate-50 dark:hover:bg-[#282a2c] text-slate-700 dark:text-slate-300 border-[#dfe3e7] dark:border-[#2d2f31]',
            'px-4 py-2 rounded-full text-xs border flex items-center gap-2 shrink-0 transition-colors motion-reduce:transition-none cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#131314]'
          ]"
        >
          <span>{{ t('browse_all_categories') }}</span>
          <span
            :class="[
              selectedCategories.length === 0 ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-[#282a2c] text-slate-600 dark:text-slate-300',
              'px-1.5 py-0.5 rounded-full text-[10px] font-bold tabular-nums'
            ]"
          >
            {{ priceCounts.all }}
          </span>
        </button>

        <button
          v-for="cat in categoriesList"
          :key="cat.id"
          @click="toggleTopCategory(cat.id)"
          type="button"
          :aria-pressed="selectedCategories.includes(cat.id) ? 'true' : 'false'"
          :class="[
            selectedCategories.includes(cat.id)
              ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20 border-transparent'
              : 'bg-white dark:bg-[#1e1f20] hover:bg-slate-50 dark:hover:bg-[#282a2c] text-slate-700 dark:text-slate-300 border-[#dfe3e7] dark:border-[#2d2f31]',
            'px-4 py-2 rounded-full text-xs border flex items-center gap-2 shrink-0 transition-colors motion-reduce:transition-none cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#131314]'
          ]"
        >
          <span>{{ localizedCategoryName(cat) }}</span>
          <span
            :class="[
              selectedCategories.includes(cat.id) ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-[#282a2c] text-slate-600 dark:text-slate-300',
              'px-1.5 py-0.5 rounded-full text-[10px] font-bold tabular-nums'
            ]"
          >
            {{ cat.count }}
          </span>
        </button>
      </nav>

      <!-- Wishlist optimistic rollback error alert -->
      <div
        v-if="wishlistErrorMsg"
        role="alert"
        class="flex items-center justify-between gap-3 px-4 py-3 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-200 text-xs font-medium"
      >
        <span>{{ wishlistErrorMsg }}</span>
        <button
          @click="wishlistErrorMsg = null"
          type="button"
          class="min-w-[24px] min-h-[24px] flex items-center justify-center rounded hover:bg-rose-100 dark:hover:bg-rose-900 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"
          :aria-label="t('browse_close_filters')"
        >
          ✕
        </button>
      </div>

      <!-- 3. MAIN 2-COLUMN LAYOUT (280px Sticky Filter Panel on Desktop + Catalog Grid) -->
      <div class="flex flex-col lg:flex-row gap-6 items-start">
        <!-- DESKTOP FILTER PANEL (280px Sticky) -->
        <aside
          aria-label="Course filters"
          class="hidden lg:block w-[280px] shrink-0 sticky top-20 bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] p-5 shadow-xs dark:shadow-xl space-y-5"
        >
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#2d2f31] pb-3">
            <h2 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ t('browse_filter_courses') }}
            </h2>
            <button
              v-if="hasActiveFilters"
              @click="clearAllFilters"
              type="button"
              class="text-xs text-purple-600 dark:text-purple-400 hover:underline font-semibold cursor-pointer rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              {{ t('browse_clear_all') }}
            </button>
          </div>

          <!-- Categories Filter -->
          <fieldset class="space-y-2.5">
            <legend class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ t('browse_categories') }}
            </legend>
            <div class="space-y-2 pt-1 max-h-60 overflow-y-auto custom-scrollbar pr-1">
              <label
                v-for="cat in categoriesList"
                :key="'desk-cat-' + cat.id"
                class="flex items-center justify-between gap-2 text-xs text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white cursor-pointer py-0.5"
              >
                <span class="flex items-center gap-2.5 min-w-0">
                  <input
                    type="checkbox"
                    :value="cat.id"
                    v-model="selectedCategories"
                    @change="onSidebarFilterChange"
                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-[#131314] text-purple-600 focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer shrink-0"
                  />
                  <span class="truncate">{{ localizedCategoryName(cat) }}</span>
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium tabular-nums shrink-0">
                  {{ cat.count }}
                </span>
              </label>
            </div>
          </fieldset>

          <!-- Level Filter -->
          <fieldset class="space-y-2.5 border-t border-slate-100 dark:border-[#2d2f31] pt-4">
            <legend class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ t('browse_level') }}
            </legend>
            <div class="space-y-2 pt-1">
              <label
                v-for="lvl in levelsList"
                :key="'desk-lvl-' + lvl.key"
                class="flex items-center justify-between gap-2 text-xs text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white cursor-pointer py-0.5"
              >
                <span class="flex items-center gap-2.5">
                  <input
                    type="checkbox"
                    :value="lvl.key"
                    v-model="selectedLevels"
                    @change="onSidebarFilterChange"
                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-[#131314] text-purple-600 focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer"
                  />
                  <span>{{ localizedLevelLabel(lvl.key) }}</span>
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium tabular-nums">
                  {{ lvl.count }}
                </span>
              </label>
            </div>
          </fieldset>

          <!-- Price Filter -->
          <fieldset class="space-y-2.5 border-t border-slate-100 dark:border-[#2d2f31] pt-4">
            <legend class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ t('browse_price') }}
            </legend>
            <div class="space-y-2 pt-1 text-xs text-slate-700 dark:text-slate-300">
              <label class="flex items-center justify-between cursor-pointer hover:text-slate-900 dark:hover:text-white py-0.5">
                <span class="flex items-center gap-2.5">
                  <input
                    type="radio"
                    name="desktop_price_filter"
                    value="all"
                    v-model="selectedPrice"
                    @change="onSidebarFilterChange"
                    class="w-4 h-4 text-purple-600 border-slate-300 dark:border-slate-600 bg-white dark:bg-[#131314] focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer"
                  />
                  <span>{{ t('browse_price_all') }}</span>
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium tabular-nums">
                  {{ priceCounts.all }}
                </span>
              </label>
              <label class="flex items-center justify-between cursor-pointer hover:text-slate-900 dark:hover:text-white py-0.5">
                <span class="flex items-center gap-2.5">
                  <input
                    type="radio"
                    name="desktop_price_filter"
                    value="free"
                    v-model="selectedPrice"
                    @change="onSidebarFilterChange"
                    class="w-4 h-4 text-purple-600 border-slate-300 dark:border-slate-600 bg-white dark:bg-[#131314] focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer"
                  />
                  <span>{{ t('browse_price_free') }}</span>
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium tabular-nums">
                  {{ priceCounts.free }}
                </span>
              </label>
              <label class="flex items-center justify-between cursor-pointer hover:text-slate-900 dark:hover:text-white py-0.5">
                <span class="flex items-center gap-2.5">
                  <input
                    type="radio"
                    name="desktop_price_filter"
                    value="paid"
                    v-model="selectedPrice"
                    @change="onSidebarFilterChange"
                    class="w-4 h-4 text-purple-600 border-slate-300 dark:border-slate-600 bg-white dark:bg-[#131314] focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer"
                  />
                  <span>{{ t('browse_price_paid') }}</span>
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium tabular-nums">
                  {{ priceCounts.paid }}
                </span>
              </label>
            </div>
          </fieldset>
        </aside>

        <!-- RIGHT COLUMN: CONTROLS, CHIPS & COURSE CARDS -->
        <main class="flex-1 min-w-0 w-full space-y-5">
          <!-- Active Filter Chips Bar -->
          <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2">
            <button
              v-if="searchQuery.trim()"
              @click="clearSearch"
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-200 border border-purple-200 dark:border-purple-700/60 hover:bg-purple-100 dark:hover:bg-purple-900/50 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              <span>"{{ searchQuery.trim() }}"</span>
              <span aria-hidden="true">✕</span>
            </button>

            <button
              v-for="catId in selectedCategories"
              :key="'chip-cat-' + catId"
              @click="removeCategoryChip(catId)"
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-200 border border-purple-200 dark:border-purple-700/60 hover:bg-purple-100 dark:hover:bg-purple-900/50 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              <span>{{ localizedCategoryName(categoriesList.find(c => c.id === catId) || { name: '#' + catId }) }}</span>
              <span aria-hidden="true">✕</span>
            </button>

            <button
              v-for="lvl in selectedLevels"
              :key="'chip-lvl-' + lvl"
              @click="removeLevelChip(lvl)"
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-200 border border-purple-200 dark:border-purple-700/60 hover:bg-purple-100 dark:hover:bg-purple-900/50 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              <span>{{ localizedLevelLabel(lvl) }}</span>
              <span aria-hidden="true">✕</span>
            </button>

            <button
              v-if="selectedPrice !== 'all'"
              @click="resetPriceFilter"
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-200 border border-purple-200 dark:border-purple-700/60 hover:bg-purple-100 dark:hover:bg-purple-900/50 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              <span>{{ selectedPrice === 'free' ? t('browse_price_free') : t('browse_price_paid') }}</span>
              <span aria-hidden="true">✕</span>
            </button>

            <button
              @click="clearAllFilters"
              type="button"
              class="px-3 py-1.5 rounded-full text-xs font-bold text-slate-600 dark:text-slate-300 hover:text-purple-600 dark:hover:text-purple-400 underline underline-offset-2 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              {{ t('browse_clear_all') }}
            </button>
          </div>

          <!-- Top Results & Controls Bar -->
          <div class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] px-4 sm:px-5 py-3 shadow-xs">
            <div class="flex items-center gap-3">
              <!-- Mobile/Tablet Filter Drawer Trigger (<1024px) -->
              <button
                ref="filterTriggerRef"
                @click="openFilterDrawer"
                type="button"
                aria-controls="browse-filter-drawer"
                :aria-expanded="isDrawerOpen ? 'true' : 'false'"
                class="lg:hidden inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-[#282a2c] hover:bg-slate-100 dark:hover:bg-[#323538] border border-[#dfe3e7] dark:border-[#3c4043] text-xs font-bold text-slate-800 dark:text-slate-200 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-8.586L3.293 6.707A1 1 0 013 6V4z" />
                </svg>
                <span>
                  {{ t('browse_filters_btn') }}<template v-if="activeFilterCount > 0"> ({{ activeFilterCount }})</template>
                </span>
              </button>

              <!-- Real Paginator Total Count -->
              <div class="text-xs text-slate-600 dark:text-slate-400 tabular-nums">
                <template v-if="courses.total > 0">
                  {{ t('browse_showing') }}
                  <span class="font-bold text-slate-900 dark:text-white">{{ courses.from ?? 1 }}</span>
                  {{ t('browse_to') }}
                  <span class="font-bold text-slate-900 dark:text-white">{{ courses.to ?? catalogItems.length }}</span>
                  {{ t('browse_of') }}
                  <span class="font-bold text-slate-900 dark:text-white">{{ courses.total }}</span>
                  {{ t('browse_courses_unit') }}
                </template>
                <template v-else>
                  <span class="font-bold text-slate-900 dark:text-white">{{ t('browse_no_results') }}</span>
                </template>
              </div>
            </div>

            <div class="flex items-center gap-2.5">
              <!-- Sort Dropdown -->
              <div class="flex items-center gap-2">
                <label for="browse-sort-select" class="text-xs text-slate-500 dark:text-slate-400 hidden sm:inline">
                  {{ t('browse_sort_by') }}
                </label>
                <div class="relative">
                  <select
                    id="browse-sort-select"
                    v-model="selectedSort"
                    @change="fetchCatalog(1)"
                    class="appearance-none bg-slate-50 dark:bg-[#282a2c] border border-[#dfe3e7] dark:border-[#3c4043] hover:border-purple-400 text-slate-800 dark:text-slate-200 text-xs font-medium rounded-xl pl-3 pr-8 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer"
                  >
                    <option value="popular">{{ t('browse_sort_popular') }}</option>
                    <option value="newest">{{ t('browse_sort_newest') }}</option>
                    <option value="title">{{ t('browse_sort_title') }}</option>
                    <option value="duration">{{ t('browse_sort_duration') }}</option>
                  </select>
                  <span class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-[10px]" aria-hidden="true">▼</span>
                </div>
              </div>

              <!-- Grid / List View Switcher -->
              <div class="inline-flex items-center gap-0.5 rounded-xl bg-slate-100 dark:bg-[#282a2c] border border-slate-200/90 dark:border-[#3c4043] p-1 shadow-2xs shrink-0">
                <button
                  @click="setViewMode('grid')"
                  @mouseup="($event.currentTarget as HTMLElement)?.blur()"
                  type="button"
                  :aria-pressed="viewMode === 'grid' ? 'true' : 'false'"
                  :aria-label="t('browse_grid_view')"
                  :title="t('browse_grid_view')"
                  :class="[
                    viewMode === 'grid' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                    'w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/70'
                  ]"
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
                  :aria-label="t('browse_list_view')"
                  :title="t('browse_list_view')"
                  :class="[
                    viewMode === 'list' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                    'w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/70'
                  ]"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- ERROR STATE WITH RETRY -->
          <div
            v-if="fetchError"
            role="alert"
            class="bg-white dark:bg-[#1e1f20] border border-rose-200 dark:border-rose-800 rounded-[20px] p-10 text-center space-y-4"
          >
            <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-300 flex items-center justify-center mx-auto">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
              </svg>
            </div>
            <div class="space-y-1">
              <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ t('browse_error_title') }}</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('browse_error_desc') }}</p>
            </div>
            <button
              @click="fetchCatalog(courses?.current_page || 1)"
              type="button"
              class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              {{ t('browse_retry_btn') }}
            </button>
          </div>

          <!-- DASHBOARD-STYLE SHIMMER SKELETON LOADING STATE -->
          <div
            v-if="isLoading"
            aria-busy="true"
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6"
          >
            <div
              v-for="n in 6"
              :key="'skel-' + n"
              class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] overflow-hidden shadow-xs flex flex-col justify-between"
            >
              <div>
                <div class="relative w-full aspect-video bg-gradient-to-br from-slate-200 via-slate-100 to-slate-200 dark:from-[#1e1f20] dark:via-[#282a2c] dark:to-[#1e1f20] overflow-hidden border-b border-slate-100 dark:border-[#2d2f31]">
                  <div class="shimmer-sweep absolute inset-0 pointer-events-none"></div>
                  <div class="absolute inset-x-0 bottom-0 overflow-hidden">
                    <svg class="w-full h-9 text-white/50 dark:text-[#333538]/70" viewBox="0 0 100 25" preserveAspectRatio="none">
                      <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.45"/>
                      <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.85"/>
                    </svg>
                  </div>
                </div>
                <div class="p-4 space-y-2.5">
                  <div class="h-4 w-24 rounded-full bg-slate-200 dark:bg-[#282a2c]"></div>
                  <div class="h-4 w-4/5 rounded-md bg-slate-200 dark:bg-[#282a2c]"></div>
                  <div class="h-3 w-full rounded-md bg-slate-100 dark:bg-[#242628]"></div>
                  <div class="h-3 w-2/3 rounded-md bg-slate-100 dark:bg-[#242628]"></div>
                </div>
              </div>
              <div class="px-4 pb-4 pt-2 flex gap-2">
                <div class="h-9 flex-1 rounded-xl bg-slate-100 dark:bg-[#282a2c]"></div>
                <div class="h-9 flex-1 rounded-xl bg-slate-200 dark:bg-[#333538]"></div>
              </div>
            </div>
          </div>

          <!-- COURSES GRID OR LIST -->
          <template v-else-if="catalogItems.length > 0">
            <!-- GRID VIEW (3 cols desktop / 2 cols tablet / 1 col mobile) -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
              <article
                v-for="(course, idx) in catalogItems"
                :key="course.id"
                :style="{ animationDelay: `${Math.min(idx, 8) * 50}ms` }"
                class="browse-card-enter bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 dark:hover:border-purple-500/60 rounded-[20px] overflow-hidden shadow-xs hover:shadow-md dark:shadow-xl transition-transform duration-200 hover:-translate-y-0.5 motion-reduce:transition-none motion-reduce:hover:translate-y-0 group flex flex-col justify-between"
              >
                <div>
                  <!-- 16:9 REAL THUMBNAIL (WITH SHIMMER SKELETON) OR DASHBOARD-STYLE GRADIENT + WAVE -->
                  <div class="relative w-full aspect-video bg-slate-100 dark:bg-[#131314] overflow-hidden border-b border-slate-100 dark:border-[#2d2f31]">
                    <Link
                      :href="course.overviewHref"
                      prefetch="hover"
                      cache-for="3m"
                      class="block w-full h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-purple-500"
                    >
                      <template v-if="course.thumbnail && !thumbErrors[course.id]">
                        <div
                          v-if="!thumbLoaded[course.id]"
                          :class="['absolute inset-0 bg-gradient-to-br overflow-hidden', getCourseCardTheme(course.id).gradient]"
                        >
                          <div class="shimmer-sweep absolute inset-0 pointer-events-none"></div>
                          <svg class="absolute inset-x-0 bottom-0 w-full h-9 text-white" viewBox="0 0 100 25" preserveAspectRatio="none" aria-hidden="true">
                            <path :d="getCourseCardTheme(course.id).wavePath" fill="currentColor" opacity="0.22"/>
                            <path :d="getCourseCardTheme(course.id).waveLine" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke" opacity="0.9"/>
                          </svg>
                        </div>
                        <img
                          :src="course.thumbnail"
                          :alt="localizedCourseTitle(course)"
                          loading="lazy"
                          @load="thumbLoaded[course.id] = true"
                          @error="thumbErrors[course.id] = true"
                          :class="[
                            'w-full h-full object-cover transition-all duration-300 group-hover:scale-[1.03] motion-reduce:transition-none motion-reduce:group-hover:scale-100',
                            thumbLoaded[course.id] ? 'opacity-100' : 'opacity-0'
                          ]"
                        />
                      </template>
                      <div
                        v-else
                        :class="[
                          'relative w-full h-full bg-gradient-to-br flex flex-col items-center justify-center text-white overflow-hidden select-none',
                          getCourseCardTheme(course.id).gradient
                        ]"
                      >
                        <div class="absolute -top-8 -right-8 w-28 h-28 rounded-full bg-white/10 blur-xl pointer-events-none"></div>
                        <div class="relative z-10 flex flex-col items-center gap-1.5 -mt-2">
                          <div class="w-11 h-11 rounded-2xl bg-white/15 backdrop-blur-xs border border-white/25 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                          </div>
                          <span v-if="course.code" class="text-[10px] font-bold tracking-wider uppercase text-white/85 px-2 py-0.5 rounded-full bg-white/10 border border-white/15">
                            {{ course.code }}
                          </span>
                        </div>
                        <!-- Signature Dashboard Sparkline Wave at Bottom -->
                        <div class="absolute inset-x-0 bottom-0 overflow-hidden pointer-events-none">
                          <svg class="w-full h-9 sm:h-10 text-white" viewBox="0 0 100 25" preserveAspectRatio="none" aria-hidden="true">
                            <g transform="translate(0 3)" opacity="0.12">
                              <path :d="getCourseCardTheme(course.id).wavePath" fill="currentColor"/>
                            </g>
                            <path :d="getCourseCardTheme(course.id).wavePath" fill="currentColor" opacity="0.22"/>
                            <path :d="getCourseCardTheme(course.id).waveLine" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" opacity="0.9"/>
                          </svg>
                        </div>
                      </div>
                    </Link>

                    <!-- Status Badge (Top-Left — solid high-contrast badges, no duplicate "Free" badge) -->
                    <div class="absolute top-3 left-3 z-10 pointer-events-none">
                      <span
                        v-if="course.cardState === 'coming_soon'"
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 dark:bg-amber-900 dark:text-amber-100 dark:border-amber-700 shadow-sm"
                      >
                        {{ t('browse_badge_coming_soon') }}
                      </span>
                      <span
                        v-else-if="course.cardState === 'completed'"
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-600 text-white shadow-sm"
                      >
                        {{ t('browse_badge_completed') }}
                      </span>
                      <span
                        v-else-if="course.cardState === 'enrolled'"
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-600 text-white shadow-sm"
                      >
                        {{ currentLang === 'km' ? 'បានចុះឈ្មោះរួច' : 'Enrolled' }}
                      </span>
                      <span
                        v-else-if="course.cardState === 'pending_payment'"
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-600 text-white shadow-sm"
                      >
                        {{ t('browse_badge_pending_payment') }}
                      </span>
                    </div>

                    <!-- Wishlist Heart Button (Top-Right, >= 36px target) -->
                    <button
                      @click.stop="toggleWishlist(course)"
                      type="button"
                      :disabled="wishlistPending[course.id]"
                      :aria-pressed="course.isWishlisted ? 'true' : 'false'"
                      :aria-label="course.isWishlisted ? t('browse_wishlist_remove') : t('browse_wishlist_add')"
                      class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-white/95 dark:bg-[#1e1f20]/95 hover:bg-white dark:hover:bg-[#282a2c] border border-slate-200 dark:border-[#3c4043] shadow-sm flex items-center justify-center transition-transform active:scale-110 motion-reduce:active:scale-100 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                    >
                      <svg
                        class="w-4 h-4 transition-colors"
                        :class="course.isWishlisted ? 'text-rose-500 fill-rose-500' : 'text-slate-500 dark:text-slate-300'"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                      </svg>
                    </button>
                  </div>

                  <!-- CARD BODY -->
                  <div class="p-5 space-y-2.5">
                    <div class="flex items-center justify-between gap-2">
                      <span
                        v-if="localizedCourseCategory(course)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border border-purple-200/70 dark:border-purple-700/50 truncate"
                      >
                        {{ localizedCourseCategory(course) }}
                      </span>
                    </div>

                    <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors line-clamp-2 min-h-[2.5rem]">
                      <Link
                        :href="course.overviewHref"
                        class="focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 rounded"
                      >
                        {{ localizedCourseTitle(course) }}
                      </Link>
                    </h3>

                    <!-- Real description only (hidden when null) -->
                    <p
                      v-if="localizedCourseDescription(course)"
                      class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed"
                    >
                      {{ localizedCourseDescription(course) }}
                    </p>
                  </div>
                </div>

                <!-- CARD FOOTER & META -->
                <div class="px-5 pb-5 pt-2 space-y-3">
                  <!-- Teacher + Rating (only >= 3 reviews, else "New") -->
                  <div class="flex items-center justify-between gap-2 text-xs">
                    <div v-if="localizedInstructorName(course.instructor)" class="flex items-center gap-2 min-w-0">
                      <img
                        v-if="course.instructor.avatar && !avatarErrors[course.id]"
                        :src="course.instructor.avatar"
                        :alt="localizedInstructorName(course.instructor)"
                        @error="avatarErrors[course.id] = true"
                        class="w-6 h-6 rounded-full object-cover border border-purple-200 dark:border-purple-700/50 shrink-0"
                      />
                      <span
                        v-else
                        class="w-6 h-6 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-200 text-[10px] font-bold flex items-center justify-center shrink-0"
                        aria-hidden="true"
                      >
                        {{ getInitials(localizedInstructorName(course.instructor)) }}
                      </span>
                      <span class="text-slate-700 dark:text-slate-300 font-medium truncate">
                        {{ localizedInstructorName(course.instructor) }}
                      </span>
                    </div>

                    <div v-if="course.reviewsCount >= 3 && course.rating !== null" class="flex items-center gap-1 text-amber-500 font-semibold shrink-0 tabular-nums">
                      <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                      <span class="text-slate-900 dark:text-white">{{ course.rating.toFixed(1) }}</span>
                      <span class="text-[11px] text-slate-500 dark:text-slate-400">({{ course.reviewsCount }})</span>
                    </div>
                    <span
                      v-else
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-[#282a2c] text-slate-600 dark:text-slate-300 shrink-0"
                    >
                      {{ t('browse_badge_new') }}
                    </span>
                  </div>

                  <!-- Meta: Level (if exists) · Lessons · Duration (if > 0) & Price -->
                  <div class="flex items-center justify-between gap-2 pt-2.5 border-t border-slate-100 dark:border-[#2d2f31]">
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 flex flex-wrap items-center gap-1 min-w-0 tabular-nums">
                      <template v-if="course.level">
                        <span>{{ localizedLevelLabel(course.level) }}</span>
                        <span aria-hidden="true">·</span>
                      </template>
                      <span>{{ formatLessonsCount(course.lessonsCount) }}</span>
                      <template v-if="course.duration">
                        <span aria-hidden="true">·</span>
                        <span>{{ localizedDuration(course.duration) }}</span>
                      </template>
                    </div>

                    <span
                      :class="course.isFree ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400'"
                      class="text-xs sm:text-[13px] font-black tabular-nums whitespace-nowrap shrink-0"
                    >
                      {{ course.isFree ? t('browse_price_free') : course.priceDisplay }}
                    </span>
                  </div>

                  <!-- Action Buttons (Full-width 2-column grid to prevent overlap) -->
                  <div class="grid grid-cols-2 gap-2 pt-2.5 border-t border-slate-100 dark:border-[#2d2f31]">
                    <Link
                      :href="course.overviewHref"
                      class="w-full inline-flex items-center justify-center px-2 py-2 rounded-xl bg-slate-100 dark:bg-[#282a2c] hover:bg-slate-200 dark:hover:bg-[#323538] text-slate-700 dark:text-slate-200 text-[11px] sm:text-[11.5px] font-semibold transition-colors whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                    >
                      {{ t('browse_cta_view_details') }}
                    </Link>

                    <button
                      v-if="course.cardState === 'coming_soon'"
                      type="button"
                      disabled
                      class="w-full inline-flex items-center justify-center px-2 py-2 rounded-xl bg-slate-100 dark:bg-[#282a2c] text-slate-500 dark:text-slate-400 text-[11px] sm:text-[11.5px] font-bold cursor-not-allowed opacity-80 whitespace-nowrap"
                    >
                      {{ t('browse_cta_coming_soon') }}
                    </button>

                    <Link
                      v-else-if="course.cardState === 'completed'"
                      :href="course.overviewHref"
                      class="w-full inline-flex items-center justify-center px-2 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] sm:text-[11.5px] font-bold shadow-xs transition-colors whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                    >
                      {{ t('browse_cta_review') }}
                    </Link>

                    <Link
                      v-else-if="course.cardState === 'enrolled'"
                      :href="course.continueHref"
                      class="w-full inline-flex items-center justify-center px-2 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] sm:text-[11.5px] font-bold shadow-xs transition-colors whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                    >
                      {{ (course.lessonsDoneCount || 0) > 0 ? t('browse_cta_continue') : (currentLang === 'km' ? 'ចូលរៀន' : 'Start Learning') }}
                    </Link>

                    <Link
                      v-else-if="course.cardState === 'pending_payment'"
                      :href="course.checkoutHref"
                      class="w-full inline-flex items-center justify-center px-2 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-[11px] sm:text-[11.5px] font-bold shadow-xs transition-colors whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                    >
                      {{ t('browse_cta_complete_payment') }}
                    </Link>

                    <button
                      v-else
                      @click="handleEnroll(course)"
                      type="button"
                      :disabled="enrollingCourseId === course.id"
                      class="w-full inline-flex items-center justify-center px-2 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-[11px] sm:text-[11.5px] font-bold shadow-xs transition-colors cursor-pointer disabled:opacity-60 whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                    >
                      {{ enrollingCourseId === course.id ? t('browse_cta_enrolling') : t('browse_cta_enroll') }}
                    </button>
                  </div>
                </div>
              </article>
            </div>

            <!-- HORIZONTAL LIST VIEW -->
            <div v-else class="space-y-4">
              <article
                v-for="course in catalogItems"
                :key="'list-' + course.id"
                class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 dark:hover:border-purple-500/60 rounded-[20px] p-4 shadow-xs hover:shadow-md transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4"
              >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 min-w-0 flex-1">
                  <!-- 16:9 Thumbnail -->
                  <Link
                    :href="course.overviewHref"
                    prefetch="hover"
                    cache-for="3m"
                    class="relative w-full sm:w-48 aspect-video rounded-xl overflow-hidden bg-slate-100 dark:bg-[#131314] shrink-0 border border-slate-100 dark:border-[#2d2f31] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                  >
                    <template v-if="course.thumbnail && !thumbErrors[course.id]">
                      <div
                        v-if="!thumbLoaded[course.id]"
                        :class="['absolute inset-0 bg-gradient-to-br overflow-hidden', getCourseCardTheme(course.id).gradient]"
                      >
                        <div class="shimmer-sweep absolute inset-0 pointer-events-none"></div>
                      </div>
                      <img
                        :src="course.thumbnail"
                        :alt="localizedCourseTitle(course)"
                        loading="lazy"
                        @load="thumbLoaded[course.id] = true"
                        @error="thumbErrors[course.id] = true"
                        :class="['w-full h-full object-cover transition-opacity duration-300', thumbLoaded[course.id] ? 'opacity-100' : 'opacity-0']"
                      />
                    </template>
                    <div
                      v-else
                      :class="[
                        'relative w-full h-full bg-gradient-to-br flex items-center justify-center text-white overflow-hidden',
                        getCourseCardTheme(course.id).gradient
                      ]"
                    >
                      <svg class="w-8 h-8 relative z-10 -mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                      </svg>
                      <svg class="absolute inset-x-0 bottom-0 w-full h-6 text-white pointer-events-none" viewBox="0 0 100 25" preserveAspectRatio="none" aria-hidden="true">
                        <path :d="getCourseCardTheme(course.id).wavePath" fill="currentColor" opacity="0.22"/>
                        <path :d="getCourseCardTheme(course.id).waveLine" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke" opacity="0.9"/>
                      </svg>
                    </div>
                  </Link>

                  <div class="min-w-0 space-y-1.5 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                      <span
                        v-if="localizedCourseCategory(course)"
                        class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300"
                      >
                        {{ localizedCourseCategory(course) }}
                      </span>
                      <span
                        v-if="course.cardState === 'coming_soon'"
                        class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 dark:bg-amber-900 dark:text-amber-100 dark:border-amber-700"
                      >
                        {{ t('browse_badge_coming_soon') }}
                      </span>
                      <span
                        v-else-if="course.cardState === 'completed'"
                        class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-600 text-white"
                      >
                        {{ t('browse_badge_completed') }}
                      </span>
                      <span
                        v-else-if="course.cardState === 'enrolled'"
                        class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-600 text-white"
                      >
                        {{ currentLang === 'km' ? 'បានចុះឈ្មោះរួច' : 'Enrolled' }}
                      </span>
                    </div>

                    <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white line-clamp-2">
                      <Link
                        :href="course.overviewHref"
                        class="hover:text-purple-600 dark:hover:text-purple-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 rounded"
                      >
                        {{ localizedCourseTitle(course) }}
                      </Link>
                    </h3>

                    <p v-if="localizedCourseDescription(course)" class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2">
                      {{ localizedCourseDescription(course) }}
                    </p>

                    <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">
                      <span v-if="localizedInstructorName(course.instructor)" class="inline-flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                        <img
                          v-if="course.instructor.avatar && !avatarErrors[course.id]"
                          :src="course.instructor.avatar"
                          :alt="localizedInstructorName(course.instructor)"
                          @error="avatarErrors[course.id] = true"
                          class="w-5 h-5 rounded-full object-cover border border-purple-200 dark:border-purple-700/50 shrink-0"
                        />
                        <span
                          v-else
                          class="w-5 h-5 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-200 text-[9px] font-bold flex items-center justify-center shrink-0"
                          aria-hidden="true"
                        >
                          {{ getInitials(localizedInstructorName(course.instructor)) }}
                        </span>
                        <span>{{ localizedInstructorName(course.instructor) }}</span>
                      </span>
                      <template v-if="course.level">
                        <span aria-hidden="true">·</span>
                        <span>{{ localizedLevelLabel(course.level) }}</span>
                      </template>
                      <span aria-hidden="true">·</span>
                      <span>{{ formatLessonsCount(course.lessonsCount) }}</span>
                      <template v-if="course.duration">
                        <span aria-hidden="true">·</span>
                        <span>{{ localizedDuration(course.duration) }}</span>
                      </template>
                      <template v-if="course.reviewsCount >= 3 && course.rating !== null">
                        <span aria-hidden="true">·</span>
                        <span class="text-amber-500 font-semibold">★ {{ course.rating.toFixed(1) }} ({{ course.reviewsCount }})</span>
                      </template>
                      <template v-else>
                        <span aria-hidden="true">·</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-[#282a2c] text-slate-600 dark:text-slate-300">
                          {{ t('browse_badge_new') }}
                        </span>
                      </template>
                    </div>
                  </div>
                </div>

                <!-- Right Action Group -->
                <div class="flex flex-wrap sm:flex-nowrap items-center justify-between md:justify-end gap-2.5 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100 dark:border-[#2d2f31]">
                  <span
                    :class="course.isFree ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400'"
                    class="text-sm font-black tabular-nums whitespace-nowrap shrink-0"
                  >
                    {{ course.isFree ? t('browse_price_free') : course.priceDisplay }}
                  </span>

                  <button
                    @click="toggleWishlist(course)"
                    type="button"
                    :disabled="wishlistPending[course.id]"
                    :aria-pressed="course.isWishlisted ? 'true' : 'false'"
                    :aria-label="course.isWishlisted ? t('browse_wishlist_remove') : t('browse_wishlist_add')"
                    class="w-9 h-9 rounded-full bg-slate-50 dark:bg-[#282a2c] hover:bg-slate-100 dark:hover:bg-[#323538] border border-slate-200 dark:border-[#3c4043] flex items-center justify-center shrink-0 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                  >
                    <svg
                      class="w-4 h-4"
                      :class="course.isWishlisted ? 'text-rose-500 fill-rose-500' : 'text-slate-500 dark:text-slate-300'"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      aria-hidden="true"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                  </button>

                  <div class="flex items-center gap-2 shrink-0">
                    <Link
                      :href="course.overviewHref"
                      class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-[#282a2c] hover:bg-slate-200 dark:hover:bg-[#323538] text-slate-700 dark:text-slate-200 text-xs font-semibold whitespace-nowrap shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                    >
                      {{ t('browse_cta_view_details') }}
                    </Link>

                    <button
                      v-if="course.cardState === 'coming_soon'"
                      type="button"
                      disabled
                      class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-[#282a2c] text-slate-500 dark:text-slate-400 text-xs font-bold cursor-not-allowed whitespace-nowrap shrink-0"
                    >
                      {{ t('browse_cta_coming_soon') }}
                    </button>

                    <Link
                      v-else-if="course.cardState === 'completed'"
                      :href="course.overviewHref"
                      class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold whitespace-nowrap shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                    >
                      {{ t('browse_cta_review') }}
                    </Link>

                    <Link
                      v-else-if="course.cardState === 'enrolled'"
                      :href="course.continueHref"
                      class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold whitespace-nowrap shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                    >
                      {{ (course.lessonsDoneCount || 0) > 0 ? t('browse_cta_continue') : (currentLang === 'km' ? 'ចូលរៀន' : 'Start Learning') }}
                    </Link>

                    <Link
                      v-else-if="course.cardState === 'pending_payment'"
                      :href="course.checkoutHref"
                      class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold whitespace-nowrap shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                    >
                      {{ t('browse_cta_complete_payment') }}
                    </Link>

                    <button
                      v-else
                      @click="handleEnroll(course)"
                      type="button"
                      :disabled="enrollingCourseId === course.id"
                      class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold cursor-pointer disabled:opacity-60 whitespace-nowrap shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                    >
                      {{ enrollingCourseId === course.id ? t('browse_cta_enrolling') : t('browse_cta_enroll') }}
                    </button>
                  </div>
                </div>
              </article>
            </div>

            <!-- REAL PAGINATOR -->
            <nav
              v-if="courses.last_page > 1"
              aria-label="Pagination"
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-slate-200 dark:border-[#2d2f31] text-xs text-slate-600 dark:text-slate-400 tabular-nums"
            >
              <div>
                {{ t('browse_showing') }}
                <span class="font-bold text-slate-900 dark:text-white">{{ courses.from }}</span>
                {{ t('browse_to') }}
                <span class="font-bold text-slate-900 dark:text-white">{{ courses.to }}</span>
                {{ t('browse_of') }}
                <span class="font-bold text-slate-900 dark:text-white">{{ courses.total }}</span>
                {{ t('browse_courses_unit') }}
              </div>

              <div class="flex flex-wrap items-center gap-1.5">
                <template v-for="(link, idx) in courses.links" :key="'pg-' + idx">
                  <button
                    v-if="link.url"
                    @click="fetchCatalog(null, link.url)"
                    type="button"
                    :aria-current="link.active ? 'page' : undefined"
                    :aria-label="link.label.includes('Previous') ? t('browse_pagination_prev') : (link.label.includes('Next') ? t('browse_pagination_next') : link.label)"
                    :class="[
                      link.active
                        ? 'bg-purple-600 text-white font-bold border-transparent shadow-xs'
                        : 'bg-white dark:bg-[#1e1f20] hover:bg-slate-50 dark:hover:bg-[#282a2c] text-slate-700 dark:text-slate-300 border-[#dfe3e7] dark:border-[#2d2f31]',
                      'min-w-[36px] min-h-[36px] px-3 py-1.5 rounded-xl border text-xs transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500'
                    ]"
                  >
                    {{ formatPaginationLabel(link.label) }}
                  </button>
                  <span
                    v-else
                    class="min-w-[36px] min-h-[36px] px-2.5 py-1.5 inline-flex items-center justify-center rounded-xl bg-slate-50 dark:bg-[#1e1f20]/60 border border-slate-200/70 dark:border-[#2d2f31] text-slate-400 cursor-not-allowed"
                  >
                    {{ formatPaginationLabel(link.label) }}
                  </span>
                </template>
              </div>
            </nav>
          </template>

          <!-- EMPTY STATE: NO MATCHING RESULTS -->
          <div
            v-else-if="totalPublished > 0"
            class="bg-white dark:bg-[#1e1f20] border border-dashed border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] p-12 text-center space-y-4"
          >
            <div class="w-14 h-14 rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-300 flex items-center justify-center mx-auto">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <div class="space-y-1">
              <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ t('browse_empty_filtered_title') }}</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                {{ t('browse_empty_filtered_desc') }}
              </p>
            </div>
            <button
              @click="clearAllFilters"
              type="button"
              class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              {{ t('browse_clear_filters_btn') }}
            </button>
          </div>

          <!-- EMPTY STATE: ZERO PUBLISHED COURSES IN SYSTEM -->
          <div
            v-else
            class="bg-white dark:bg-[#1e1f20] border border-dashed border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] p-12 text-center space-y-4"
          >
            <div class="w-14 h-14 rounded-full bg-slate-100 dark:bg-[#282a2c] text-slate-500 dark:text-slate-400 flex items-center justify-center mx-auto">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <div class="space-y-1">
              <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ t('browse_empty_catalog_title') }}</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                {{ t('browse_empty_catalog_desc') }}
              </p>
            </div>
          </div>
        </main>
      </div>
    </div>

    <!-- MOBILE/TABLET FILTER DRAWER (<1024px) using native modal <dialog> -->
    <dialog
      ref="filterDrawerRef"
      id="browse-filter-drawer"
      :aria-label="t('browse_filter_courses')"
      @click="onDrawerBackdropClick"
      @cancel="onDrawerCancel"
      class="browse-drawer"
    >
      <div
        ref="filterDrawerSheetRef"
        class="w-[min(22rem,85vw)] h-[100svh] bg-white dark:bg-[#1e1f20] text-slate-900 dark:text-white border-r border-[#dfe3e7] dark:border-[#2d2f31] shadow-2xl flex flex-col justify-between overflow-y-auto"
      >
        <div class="p-5 space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#2d2f31] pb-3">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ t('browse_filter_courses') }}
            </h2>
            <button
              @click="closeFilterDrawer"
              type="button"
              autofocus
              :aria-label="t('browse_close_filters')"
              class="min-w-[36px] min-h-[36px] rounded-xl bg-slate-100 dark:bg-[#282a2c] text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white flex items-center justify-center cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              ✕
            </button>
          </div>

          <!-- Categories -->
          <fieldset class="space-y-2.5">
            <legend class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ t('browse_categories') }}
            </legend>
            <div class="space-y-2 pt-1">
              <label
                v-for="cat in categoriesList"
                :key="'mob-cat-' + cat.id"
                class="flex items-center justify-between gap-2 text-xs text-slate-700 dark:text-slate-300 py-1 cursor-pointer"
              >
                <span class="flex items-center gap-2.5">
                  <input
                    type="checkbox"
                    :value="cat.id"
                    v-model="selectedCategories"
                    @change="onSidebarFilterChange"
                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-purple-600 focus-visible:ring-2 focus-visible:ring-purple-500"
                  />
                  <span>{{ localizedCategoryName(cat) }}</span>
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">{{ cat.count }}</span>
              </label>
            </div>
          </fieldset>

          <!-- Level -->
          <fieldset class="space-y-2.5 border-t border-slate-100 dark:border-[#2d2f31] pt-4">
            <legend class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ t('browse_level') }}
            </legend>
            <div class="space-y-2 pt-1">
              <label
                v-for="lvl in levelsList"
                :key="'mob-lvl-' + lvl.key"
                class="flex items-center justify-between gap-2 text-xs text-slate-700 dark:text-slate-300 py-1 cursor-pointer"
              >
                <span class="flex items-center gap-2.5">
                  <input
                    type="checkbox"
                    :value="lvl.key"
                    v-model="selectedLevels"
                    @change="onSidebarFilterChange"
                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-purple-600 focus-visible:ring-2 focus-visible:ring-purple-500"
                  />
                  <span>{{ localizedLevelLabel(lvl.key) }}</span>
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">{{ lvl.count }}</span>
              </label>
            </div>
          </fieldset>

          <!-- Price -->
          <fieldset class="space-y-2.5 border-t border-slate-100 dark:border-[#2d2f31] pt-4">
            <legend class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ t('browse_price') }}
            </legend>
            <div class="space-y-2 pt-1 text-xs text-slate-700 dark:text-slate-300">
              <label class="flex items-center justify-between py-1 cursor-pointer">
                <span class="flex items-center gap-2.5">
                  <input
                    type="radio"
                    name="mobile_price_filter"
                    value="all"
                    v-model="selectedPrice"
                    @change="onSidebarFilterChange"
                    class="w-4 h-4 text-purple-600 focus-visible:ring-2 focus-visible:ring-purple-500"
                  />
                  <span>{{ t('browse_price_all') }}</span>
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">{{ priceCounts.all }}</span>
              </label>
              <label class="flex items-center justify-between py-1 cursor-pointer">
                <span class="flex items-center gap-2.5">
                  <input
                    type="radio"
                    name="mobile_price_filter"
                    value="free"
                    v-model="selectedPrice"
                    @change="onSidebarFilterChange"
                    class="w-4 h-4 text-purple-600 focus-visible:ring-2 focus-visible:ring-purple-500"
                  />
                  <span>{{ t('browse_price_free') }}</span>
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">{{ priceCounts.free }}</span>
              </label>
              <label class="flex items-center justify-between py-1 cursor-pointer">
                <span class="flex items-center gap-2.5">
                  <input
                    type="radio"
                    name="mobile_price_filter"
                    value="paid"
                    v-model="selectedPrice"
                    @change="onSidebarFilterChange"
                    class="w-4 h-4 text-purple-600 focus-visible:ring-2 focus-visible:ring-purple-500"
                  />
                  <span>{{ t('browse_price_paid') }}</span>
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">{{ priceCounts.paid }}</span>
              </label>
            </div>
          </fieldset>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-[#2d2f31] flex items-center gap-2.5 bg-white dark:bg-[#1e1f20] sticky bottom-0">
          <button
            @click="clearAllFilters"
            type="button"
            class="flex-1 py-2.5 rounded-xl bg-slate-100 dark:bg-[#282a2c] text-slate-700 dark:text-slate-200 text-xs font-bold cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
          >
            {{ t('browse_clear_all') }}
          </button>
          <button
            @click="closeFilterDrawer"
            type="button"
            class="flex-1 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
          >
            {{ t('browse_apply_filters') }}
          </button>
        </div>
      </div>
    </dialog>
  </StudentLayout>
</template>

<style scoped>
.browse-drawer {
  inset: 0;
  width: auto;
  height: auto;
  max-width: none;
  max-height: none;
  margin: 0;
  padding: 0;
  border: 0;
  background: transparent;
}

.browse-drawer::backdrop {
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(2px);
}

.browse-card-enter {
  animation: browseCardEnter 420ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes browseCardEnter {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.shimmer-sweep {
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.45) 50%,
    rgba(255, 255, 255, 0) 100%
  );
  animation: shimmerSweep 1.6s infinite;
}

@keyframes shimmerSweep {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}
</style>
