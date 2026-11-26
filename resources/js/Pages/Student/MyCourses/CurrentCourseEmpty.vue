<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import CourseCard, { type EnrolledCourseDashboardItem } from '@/Components/Student/CourseCard.vue'
import CourseSkeletonCard from '@/Components/Student/CourseSkeletonCard.vue'
import { useLanguage } from '@/Services/i18n'
import {
  BookOpen,
  BookCopy,
  Play,
  CheckCircle2,
  Check,
  Clock,
  Search,
  X,
  GraduationCap,
  Sparkles,
  RefreshCw,
  ArrowRight,
  ChevronDown,
  ArrowUpDown,
  Filter,
  AlertCircle,
  Eye
} from 'lucide-vue-next'

type ContinueState = 'A' | 'B' | 'C' | 'D' | 'E'

export interface RecentActivityItem {
  id: string
  type: string
  iconBg?: string
  title: string
  title_km?: string
  course: string
  course_km?: string
  time: string
  duration?: string
  thumbnail?: string | null
  href: string
  timestamp: number
  progress: number
  category?: string
  category_km?: string
}

interface RecommendedCourse {
  id: number
  title: string
  titleKh?: string
  code?: string
  thumbnail?: string | null
  category?: string | null
  categoryKh?: string | null
  teacherName?: string | null
  teacherNameKh?: string | null
  lessonsCount: number
  durationSeconds?: number
  durationLabel?: string | null
  isFree: boolean
  priceDisplay: string
  overviewHref: string
  enrollHref: string
  checkoutHref: string
  level?: string | null
}

interface MajorOption {
  id: number
  name: string
  name_kh?: string
}

const props = withDefaults(defineProps<{
  state?: ContinueState
  hasEnrollments?: boolean
  startCourse?: any | null
  recommendedCourses?: RecommendedCourse[]
  courses?: EnrolledCourseDashboardItem[]
  recentActivities?: RecentActivityItem[]
  stats?: {
    total_courses: number
    in_progress: number
    completed: number
    not_started?: number
    total_study_seconds?: number
    study_hours?: number
    study_minutes?: number
    study_time?: string
    study_time_kh?: string
  }
  majors?: MajorOption[]
}>(), {
  state: 'B',
  hasEnrollments: false,
  startCourse: null,
  recommendedCourses: () => [],
  courses: () => [],
  recentActivities: () => [],
  stats: () => ({
    total_courses: 0,
    in_progress: 0,
    completed: 0,
    not_started: 0,
    total_study_seconds: 0,
    study_hours: 0,
    study_minutes: 0,
    study_time: '0m',
    study_time_kh: '០ នាទី',
  }),
  majors: () => [],
})

const { t, currentLang } = useLanguage()
const isKm = computed(() => currentLang.value === 'km')

// Filter and search state
const searchQuery = ref('')
const selectedMajor = ref<'all' | number>('all')
const selectedStatus = ref<'all' | 'in_progress' | 'completed'>('all')
const sortBy = ref<'recent' | 'title' | 'progress'>('recent')

// UI interaction states
const isLoading = ref(false)
const hasError = ref(false)
const errorMessage = ref('')
const isMajorOpen = ref(false)
const isSortOpen = ref(false)

// Close dropdowns on outside click
const closeDropdowns = () => {
  isMajorOpen.value = false
  isSortOpen.value = false
}

// Auto select tab if all courses are completed
watch(() => props.courses, (newCourses) => {
  if (newCourses && newCourses.length > 0) {
    const hasInProg = newCourses.some(c => c.status === 'in_progress' || (c.progress_percent > 0 && c.progress_percent < 100))
    const hasComp = newCourses.some(c => c.status === 'completed' || c.progress_percent >= 100)
    if (!hasInProg && hasComp) {
      selectedStatus.value = 'all'
    }
  }
}, { immediate: true })

// Formatted recent learning activities matching Image 2 & Reference Image
const formattedActivities = computed(() => {
  if (!props.recentActivities || props.recentActivities.length === 0) {
    return []
  }
  return props.recentActivities.slice(0, 3).map((a) => {
    const rawTitle = (isKm.value && a.title_km) ? a.title_km : (a.title || '')
    const cleanTitle = rawTitle.replace(/^(Watched:\s*|បានទស្សនា:\s*|Completed Quiz:\s*|បានបញ្ចប់ Quiz:\s*|Downloaded:\s*|បានទាញយក:\s*)/i, '').trim()

    let category = (isKm.value && a.category_km) ? a.category_km : (a.category || (isKm.value ? 'មេរៀន' : 'Lesson'))
    const lowTitle = rawTitle.toLowerCase()
    if (lowTitle.includes('lab') || lowTitle.includes('practical') || rawTitle.includes('អនុវត្ត')) {
      category = isKm.value ? 'ការអនុវត្ត' : 'Lab'
    } else if (lowTitle.includes('quiz') || rawTitle.includes('កម្រងសំណួរ') || a.type === 'check') {
      category = isKm.value ? 'កម្រងសំណួរ' : 'Quiz'
    }

    const p = (a as any).progress ?? (a as any).percent ?? 0
    const durationDisplay = a.duration || '19:36'

    return {
      ...a,
      courseDisplay: isKm.value ? (a.course_km || a.course) : a.course,
      cleanTitle,
      category,
      duration: durationDisplay,
      thumbnail: a.thumbnail || '/images/courses/default-study-book.png',
      progress: Math.min(100, Math.max(0, Math.round(p)))
    }
  })
})

// Real database course counts
const totalCoursesCount = computed(() => {
  if (props.stats && props.stats.total_courses !== undefined && props.stats.total_courses !== null) {
    return Number(props.stats.total_courses)
  }
  return (props.courses || []).length
})

const inProgressCount = computed(() => {
  if (props.stats && props.stats.in_progress !== undefined && props.stats.in_progress !== null) {
    return Number(props.stats.in_progress)
  }
  return (props.courses || []).filter(c => c.status === 'in_progress' || (c.progress_percent > 0 && c.progress_percent < 100)).length
})

const completedCount = computed(() => {
  if (props.stats && props.stats.completed !== undefined && props.stats.completed !== null) {
    return Number(props.stats.completed)
  }
  return (props.courses || []).filter(c => c.status === 'completed' || c.progress_percent >= 100).length
})

const studyTimeDisplay = computed(() => {
  if (props.stats) {
    if (isKm.value && props.stats.study_time_kh) {
      return props.stats.study_time_kh
    }
    if (props.stats.study_time) {
      return props.stats.study_time
    }
    const hrs = Number(props.stats.study_hours || 0)
    const mins = Number(props.stats.study_minutes || 0)
    if (isKm.value) {
      return hrs > 0 ? `${hrs} ម៉ោង ${mins} នាទី` : `${mins} នាទី`
    }
    return hrs > 0 ? `${hrs}h ${mins}m` : `${mins}m`
  }
  return isKm.value ? '0 នាទី' : '0m'
})

// Available SPI majors list with guaranteed 5 SPI official majors
const allMajorsList = computed(() => {
  const existing = props.majors || []
  const official = [
    { id: 1, name: 'Information Technology', name_kh: 'បច្ចេកវិទ្យាព័ត៌មាន' },
    { id: 2, name: 'Social Work', name_kh: 'ការងារសង្គម' },
    { id: 3, name: 'Agriculture', name_kh: 'កសិកម្ម' },
    { id: 4, name: 'Tourism', name_kh: 'ទេសចរណ៍' },
    { id: 5, name: 'English Literature', name_kh: 'អក្សរសាស្ត្រអង់គ្លេស' },
  ]
  if (existing.length >= 4) {
    return existing
  }
  return official
})

const selectedMajorLabel = computed(() => {
  if (selectedMajor.value === 'all') {
    return isKm.value ? 'មុខវិជ្ជា' : 'Majors'
  }
  const found = allMajorsList.value.find(m => m.id === selectedMajor.value)
  if (found) {
    return isKm.value && found.name_kh ? found.name_kh : found.name
  }
  return isKm.value ? 'មុខវិជ្ជា' : 'Majors'
})

const selectedSortLabel = computed(() => {
  if (sortBy.value === 'recent') {
    return isKm.value ? 'តម្រៀប: ថ្មីបំផុត' : 'Sort: Newest'
  }
  if (sortBy.value === 'title') {
    return isKm.value ? 'តម្រៀប: ឈ្មោះ A-Z' : 'Sort: Title A-Z'
  }
  if (sortBy.value === 'progress') {
    return isKm.value ? 'តម្រៀប: វឌ្ឍនភាព' : 'Sort: Progress'
  }
  return isKm.value ? 'តម្រៀប' : 'Sort'
})

// Reactive search & filter pipeline
const filteredCourses = computed(() => {
  let list = [...(props.courses || [])]

  // 1. Status Filter
  if (selectedStatus.value === 'in_progress') {
    list = list.filter(c => c.status === 'in_progress' || (c.progress_percent > 0 && c.progress_percent < 100))
  } else if (selectedStatus.value === 'completed') {
    list = list.filter(c => c.status === 'completed' || c.progress_percent >= 100)
  }

  // 2. Major Filter
  if (selectedMajor.value !== 'all') {
    const targetId = Number(selectedMajor.value)
    list = list.filter(c => {
      if (c.major_id === targetId) return true
      const mName = (c.major || '').toLowerCase()
      const mKh = (c.major_kh || '').toLowerCase()
      const sel = allMajorsList.value.find(m => m.id === targetId)
      if (sel) {
        return mName.includes(sel.name.toLowerCase()) || mKh.includes((sel.name_kh || '').toLowerCase())
      }
      return false
    })
  }

  // 3. Search Query Filter
  const q = searchQuery.value.trim().toLowerCase()
  if (q !== '') {
    list = list.filter(c => {
      const title = (c.title || '').toLowerCase()
      const titleKh = (c.title_kh || '').toLowerCase()
      const desc = (c.description || '').toLowerCase()
      const teacher = (c.teacher?.name || '').toLowerCase()
      const teacherKh = (c.teacher?.name_kh || '').toLowerCase()
      const major = (c.major || '').toLowerCase()
      const majorKh = (c.major_kh || '').toLowerCase()

      return (
        title.includes(q) ||
        titleKh.includes(q) ||
        desc.includes(q) ||
        teacher.includes(q) ||
        teacherKh.includes(q) ||
        major.includes(q) ||
        majorKh.includes(q)
      )
    })
  }

  // 4. Sorting
  if (sortBy.value === 'title') {
    list.sort((a, b) => {
      const nameA = isKm.value && a.title_kh ? a.title_kh : a.title
      const nameB = isKm.value && b.title_kh ? b.title_kh : b.title
      return nameA.localeCompare(nameB, isKm.value ? 'km' : 'en')
    })
  } else if (sortBy.value === 'progress') {
    list.sort((a, b) => b.progress_percent - a.progress_percent)
  } else {
    // 'recent' accessed first
    list.sort((a, b) => (b.last_accessed_ms || 0) - (a.last_accessed_ms || 0))
  }

  return list
})

const resetFilters = () => {
  searchQuery.value = ''
  selectedMajor.value = 'all'
  selectedStatus.value = 'all'
  sortBy.value = 'recent'
}

const retryLoading = () => {
  hasError.value = false
  isLoading.value = true
  router.reload({
    onFinish: () => {
      isLoading.value = false
    },
    onError: () => {
      isLoading.value = false
      hasError.value = true
      errorMessage.value = isKm.value ? 'មិនអាចទាញយកទិន្នន័យបានទេ។ សូមព្យាយាមម្តងទៀត។' : 'Failed to fetch course data. Please try again.'
    }
  })
}
</script>

<template>
  <Head :title="isKm ? 'វគ្គសិក្សារបស់ខ្ញុំ - SPI LMS' : 'My Courses - SPI LMS'" />

  <StudentLayout
    :title="isKm ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My Courses'"
    :breadcrumbs="[
      { label: isKm ? 'ផ្ទាំងដើម' : 'Dashboard', href: '/student/dashboard' },
      { label: isKm ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My Courses', href: '/student/my-courses/current' },
      { label: isKm ? 'បច្ចុប្បន្ន' : 'Current' }
    ]"
  >
    <div @click="closeDropdowns" class="student-courses-page space-y-6 pb-28 sm:pb-36 notranslate select-none-text" translate="no">

      <!-- 1. Hero Banner matching Reference Image -->
      <section class="rounded-2xl border border-slate-200/90 dark:border-white/[0.08] bg-gradient-to-r from-[#EDE9FE]/50 via-white to-[#EDE9FE]/60 dark:from-[#1E1B4B]/20 dark:via-[#1e1f20] dark:to-[#1e1f20] shadow-xs px-5 py-4 sm:px-6 sm:py-5 flex items-center justify-between gap-4 overflow-hidden relative">
        <div class="flex items-center gap-4 min-w-0 z-10">
          <!-- Book Open Icon Container -->
          <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-[#EDE9FE] to-[#DDD6FE] dark:from-[#7C3AED]/30 dark:to-[#6D28D9]/20 border border-[#DDD6FE] dark:border-purple-800/40 text-[#7045F5] dark:text-[#A78BFA] flex items-center justify-center shrink-0 shadow-xs">
            <BookOpen class="w-7 h-7 sm:w-8 sm:h-8" />
          </div>

          <div class="space-y-0.5 min-w-0">
            <div class="flex items-center gap-2.5 flex-wrap">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ isKm ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My Courses' }}
              </h1>
              <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#EDE9FE] text-[#7045F5] dark:bg-purple-900/40 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/50">
                Current Courses
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-medium line-clamp-1">
              {{ isKm ? 'គ្រប់គ្រង និងបន្តការសិក្សារបស់អ្នកនៅទីនេះ' : 'Manage and continue your academic studies here.' }}
            </p>
          </div>
        </div>

        <!-- Right Side: Action Buttons & Academic Illustration -->
        <div class="flex items-center gap-4 z-10">
          <div class="hidden sm:flex items-center gap-2.5">
            <Link
              href="/student/browse"
              class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#7045F5] hover:bg-[#5f35e0] text-white font-bold text-xs shadow-md shadow-[#7045F5]/25 hover:shadow-lg transition-all"
            >
              <Sparkles class="w-3.5 h-3.5" />
              <span>{{ isKm ? 'ស្វែងរកវគ្គសិក្សា' : 'Explore Courses' }}</span>
            </Link>

            <Link
              href="/student/ai/tutor"
              class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-purple-200 dark:border-purple-800/50 text-[#7045F5] dark:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-950/40 font-bold text-xs shadow-xs transition-all"
            >
              <Sparkles class="w-3.5 h-3.5 text-amber-500" />
              <span>{{ isKm ? 'ការណែនាំពី AI' : 'AI Learning' }}</span>
            </Link>
          </div>

          <!-- Academic Illustration (Books, Cap, Building) -->
          <div class="hidden md:block shrink-0 h-24 sm:h-28 w-44 sm:w-56 relative z-0 opacity-95 pointer-events-none">
            <img
              :src="'/images/dashboard/academic-books-banner.svg'"
              alt="Academic Stack"
              class="w-full h-full object-contain object-right"
            />
          </div>
        </div>
      </section>

      <!-- Error Banner with Retry -->
      <div
        v-if="hasError"
        class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 flex items-center justify-between gap-4"
      >
        <div class="flex items-center gap-3">
          <AlertCircle class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
          <span class="text-xs sm:text-sm font-medium">{{ errorMessage }}</span>
        </div>
        <button
          type="button"
          @click="retryLoading"
          class="px-3 py-1.5 rounded-lg bg-rose-600 text-white font-bold text-xs hover:bg-rose-700 transition-colors shrink-0 cursor-pointer"
        >
          {{ isKm ? 'ព្យាយាមម្តងទៀត' : 'Retry' }}
        </button>
      </div>

      <!-- 2. FOUR STATISTIC CARDS (Directly below Header as shown in Reference Image) -->
      <section aria-label="Course Statistics" class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
        <!-- Card 1: វគ្គសិក្សាសរុប (Total Courses) -->
        <div class="bg-white dark:bg-[#1e1f20] rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-white/[0.08] shadow-xs flex items-center gap-3.5 sm:gap-4 hover:shadow-md transition-all duration-200">
          <div class="w-12 h-12 rounded-full bg-purple-100/80 dark:bg-purple-950/50 text-[#7045F5] dark:text-purple-400 flex items-center justify-center shrink-0">
            <BookCopy class="w-6 h-6" />
          </div>
          <div class="space-y-0.5 min-w-0">
            <div class="text-2xl sm:text-3xl font-black text-[#7045F5] dark:text-purple-400 leading-none tabular-nums">
              {{ totalCoursesCount }}
            </div>
            <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
              {{ isKm ? 'វគ្គសិក្សាសរុប' : 'Total Courses' }}
            </div>
            <div class="text-[11px] text-slate-400 font-medium">
              Total Courses
            </div>
          </div>
        </div>

        <!-- Card 2: កំពុងសិក្សា (In Progress) -->
        <div class="bg-white dark:bg-[#1e1f20] rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-white/[0.08] shadow-xs flex items-center gap-3.5 sm:gap-4 hover:shadow-md transition-all duration-200">
          <div class="w-12 h-12 rounded-full bg-emerald-100/80 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <Play class="w-6 h-6 fill-current translate-x-0.5" />
          </div>
          <div class="space-y-0.5 min-w-0">
            <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums">
              {{ inProgressCount }}
            </div>
            <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
              {{ isKm ? 'កំពុងសិក្សា' : 'In Progress' }}
            </div>
            <div class="text-[11px] text-slate-400 font-medium">
              In Progress
            </div>
          </div>
        </div>

        <!-- Card 3: បានបញ្ចប់ (Completed) -->
        <div class="bg-white dark:bg-[#1e1f20] rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-white/[0.08] shadow-xs flex items-center gap-3.5 sm:gap-4 hover:shadow-md transition-all duration-200">
          <div class="w-12 h-12 rounded-full bg-sky-100/80 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
            <CheckCircle2 class="w-6 h-6" />
          </div>
          <div class="space-y-0.5 min-w-0">
            <div class="text-2xl sm:text-3xl font-black text-sky-600 dark:text-sky-400 leading-none tabular-nums">
              {{ completedCount }}
            </div>
            <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
              {{ isKm ? 'បានបញ្ចប់' : 'Completed' }}
            </div>
            <div class="text-[11px] text-slate-400 font-medium">
              Completed
            </div>
          </div>
        </div>

        <!-- Card 4: ម៉ោងសិក្សាសរុប (Total Study Time) -->
        <div class="bg-white dark:bg-[#1e1f20] rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-white/[0.08] shadow-xs flex items-center gap-3.5 sm:gap-4 hover:shadow-md transition-all duration-200">
          <div class="w-12 h-12 rounded-full bg-amber-100/80 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
            <Clock class="w-6 h-6" />
          </div>
          <div class="space-y-0.5 min-w-0">
            <div class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums">
              {{ studyTimeDisplay }}
            </div>
            <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
              {{ isKm ? 'ម៉ោងសិក្សាសរុប' : 'Total Study Time' }}
            </div>
            <div class="text-[11px] text-slate-400 font-medium">
              Total Study Time
            </div>
          </div>
        </div>
      </section>

      <!-- 3. Recent Learning Activities (ការសិក្សាថ្មីៗ) as shown in Reference Image -->
      <section v-if="formattedActivities.length > 0" class="space-y-3.5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full bg-[#7045F5] text-white flex items-center justify-center shrink-0 shadow-xs">
              <Play class="w-3.5 h-3.5 fill-current translate-x-0.5" />
            </div>
            <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-1.5">
              <span>{{ isKm ? 'ការសិក្សាថ្មីៗ' : 'Recent Learning' }}</span>
              <span class="text-xs text-slate-400 font-normal">({{ isKm ? 'Recent Learning' : 'Recent' }})</span>
            </h2>
          </div>
          <Link
            href="/student/progress/learning-time"
            class="text-xs sm:text-sm font-semibold text-[#7045F5] hover:text-[#5f35e0] transition-colors inline-flex items-center gap-1 group/history"
          >
            <span>{{ isKm ? 'មើលប្រវត្តិទាំងអស់' : 'View all history' }}</span>
            <span class="group-hover/history:translate-x-0.5 transition-transform">→</span>
          </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="act in formattedActivities"
            :key="act.id"
            role="link"
            tabindex="0"
            @click="router.visit(act.href)"
            @keydown.enter="router.visit(act.href)"
            class="group bg-white dark:bg-[#1e1f20] rounded-2xl p-3.5 border border-slate-200/90 dark:border-white/[0.08] shadow-xs hover:shadow-md hover:border-[#7045F5]/40 transition-all duration-200 flex items-center gap-3.5 cursor-pointer"
          >
            <!-- 16:9 Thumbnail with Overlay Play Circle -->
            <div class="w-28 sm:w-32 aspect-video rounded-xl relative overflow-hidden bg-slate-900 shrink-0">
              <img
                :src="act.thumbnail"
                :alt="act.cleanTitle"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                <div class="w-7 h-7 rounded-full bg-[#7045F5]/90 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                  <Play class="w-3.5 h-3.5 fill-current translate-x-0.5" />
                </div>
              </div>
            </div>

            <!-- Content on Right -->
            <div class="flex-1 min-w-0 space-y-1">
              <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white line-clamp-1 leading-snug group-hover:text-[#7045F5] transition-colors">
                {{ act.cleanTitle }}
              </h4>
              <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 line-clamp-1 font-medium">
                {{ act.courseDisplay }}
              </p>

              <!-- Duration & Progress row -->
              <div class="flex items-center gap-2 pt-1">
                <div class="flex items-center gap-1 text-[11px] text-slate-400 font-medium shrink-0">
                  <Clock class="w-3 h-3 text-slate-400" />
                  <span>{{ act.duration }}</span>
                </div>

                <!-- Mini Progress Bar -->
                <div class="flex-1 h-1.5 bg-slate-100 dark:bg-white/10 rounded-full overflow-hidden">
                  <div
                    :class="[
                      'h-full rounded-full transition-all duration-300',
                      act.progress >= 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-[#7045F5] to-[#9333EA]'
                    ]"
                    :style="{ width: `${act.progress}%` }"
                  ></div>
                </div>

                <!-- Percent Badge -->
                <div class="flex items-center gap-0.5 text-[11px] font-bold text-slate-700 dark:text-slate-300 shrink-0">
                  <span>{{ act.progress }}%</span>
                  <Check v-if="act.progress >= 100" class="w-3 h-3 text-emerald-500 stroke-[3]" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 4. Course Search and Filter Toolbar matching Reference Image -->
      <section aria-label="Course Filters" class="bg-white dark:bg-[#1e1f20] rounded-2xl p-3.5 sm:p-4 border border-slate-200/90 dark:border-white/[0.08] shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3.5">
        <!-- Left: Status Filter Tabs with Pill Badges -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar shrink-0">
          <!-- ទាំងអស់ (All) -->
          <button
            type="button"
            @click="selectedStatus = 'all'"
            :class="[
              'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 shrink-0 select-none cursor-pointer',
              selectedStatus === 'all'
                ? 'bg-[#7045F5] text-white shadow-md shadow-[#7045F5]/25'
                : 'bg-slate-100/90 hover:bg-slate-200/80 dark:bg-slate-800/60 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'
            ]"
          >
            <span>{{ isKm ? 'ទាំងអស់' : 'All Courses' }}</span>
            <span
              :class="[
                'px-2 py-0.5 rounded-full text-[10px] font-extrabold',
                selectedStatus === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200/80 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
              ]"
            >
              {{ totalCoursesCount }}
            </span>
          </button>

          <!-- កំពុងសិក្សា (In Progress) -->
          <button
            type="button"
            @click="selectedStatus = 'in_progress'"
            :class="[
              'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 shrink-0 select-none cursor-pointer',
              selectedStatus === 'in_progress'
                ? 'bg-[#7045F5] text-white shadow-md shadow-[#7045F5]/25'
                : 'bg-slate-100/90 hover:bg-slate-200/80 dark:bg-slate-800/60 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'
            ]"
          >
            <Play class="w-3.5 h-3.5 fill-current" />
            <span>{{ isKm ? 'កំពុងសិក្សា' : 'In Progress' }}</span>
            <span
              :class="[
                'px-2 py-0.5 rounded-full text-[10px] font-extrabold',
                selectedStatus === 'in_progress' ? 'bg-white/20 text-white' : 'bg-slate-200/80 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
              ]"
            >
              {{ inProgressCount }}
            </span>
          </button>

          <!-- បានបញ្ចប់ (Completed) -->
          <button
            type="button"
            @click="selectedStatus = 'completed'"
            :class="[
              'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 shrink-0 select-none cursor-pointer',
              selectedStatus === 'completed'
                ? 'bg-[#7045F5] text-white shadow-md shadow-[#7045F5]/25'
                : 'bg-slate-100/90 hover:bg-slate-200/80 dark:bg-slate-800/60 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'
            ]"
          >
            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500" />
            <span>{{ isKm ? 'បានបញ្ចប់' : 'Completed' }}</span>
            <span
              :class="[
                'px-2 py-0.5 rounded-full text-[10px] font-extrabold',
                selectedStatus === 'completed' ? 'bg-white/20 text-white' : 'bg-slate-200/80 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
              ]"
            >
              {{ completedCount }}
            </span>
          </button>
        </div>

        <!-- Right: Search input & Dropdown Filters -->
        <div class="flex items-center gap-2.5 flex-1 md:justify-end">
          <!-- Search Input -->
          <div class="relative flex-1 md:max-w-xs">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="isKm ? 'ស្វែងរកវគ្គសិក្សា...' : 'Search courses...'"
              class="w-full pl-9 pr-8 py-2 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/90 dark:border-slate-800 text-xs sm:text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#7045F5]/30 focus:border-[#7045F5] transition-all"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click="searchQuery = ''"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 rounded-full cursor-pointer"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Major Dropdown -->
          <div class="relative shrink-0" @click.stop>
            <button
              type="button"
              @click="isMajorOpen = !isMajorOpen; isSortOpen = false"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/90 dark:border-slate-800 text-xs sm:text-sm text-slate-800 dark:text-slate-200 font-medium hover:border-[#7045F5]/40 transition-colors cursor-pointer select-none"
            >
              <GraduationCap class="w-4 h-4 text-slate-500" />
              <span>{{ selectedMajorLabel }}</span>
              <ChevronDown class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': isMajorOpen }" />
            </button>

            <!-- Major Dropdown Menu -->
            <div
              v-if="isMajorOpen"
              class="absolute right-0 mt-1.5 w-56 rounded-xl bg-white dark:bg-[#1e1f20] border border-slate-200 dark:border-slate-800 shadow-xl z-30 py-1 overflow-hidden"
            >
              <button
                type="button"
                @click="selectedMajor = 'all'; isMajorOpen = false"
                :class="[
                  'w-full text-left px-3.5 py-2 text-xs font-semibold flex items-center justify-between transition-colors',
                  selectedMajor === 'all' ? 'bg-[#7045F5]/10 text-[#7045F5]' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                ]"
              >
                <span>{{ isKm ? 'មុខវិជ្ជាទាំងអស់' : 'All Majors' }}</span>
                <Check v-if="selectedMajor === 'all'" class="w-3.5 h-3.5 text-[#7045F5]" />
              </button>

              <button
                v-for="m in allMajorsList"
                :key="m.id"
                type="button"
                @click="selectedMajor = m.id; isMajorOpen = false"
                :class="[
                  'w-full text-left px-3.5 py-2 text-xs font-semibold flex items-center justify-between transition-colors',
                  selectedMajor === m.id ? 'bg-[#7045F5]/10 text-[#7045F5]' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                ]"
              >
                <span class="truncate">{{ isKm && m.name_kh ? m.name_kh : m.name }}</span>
                <Check v-if="selectedMajor === m.id" class="w-3.5 h-3.5 text-[#7045F5]" />
              </button>
            </div>
          </div>

          <!-- Sort Dropdown -->
          <div class="relative shrink-0" @click.stop>
            <button
              type="button"
              @click="isSortOpen = !isSortOpen; isMajorOpen = false"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/90 dark:border-slate-800 text-xs sm:text-sm text-slate-800 dark:text-slate-200 font-medium hover:border-[#7045F5]/40 transition-colors cursor-pointer select-none"
            >
              <ArrowUpDown class="w-3.5 h-3.5 text-slate-500" />
              <span>{{ selectedSortLabel }}</span>
              <ChevronDown class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': isSortOpen }" />
            </button>

            <!-- Sort Dropdown Menu -->
            <div
              v-if="isSortOpen"
              class="absolute right-0 mt-1.5 w-48 rounded-xl bg-white dark:bg-[#1e1f20] border border-slate-200 dark:border-slate-800 shadow-xl z-30 py-1 overflow-hidden"
            >
              <button
                type="button"
                @click="sortBy = 'recent'; isSortOpen = false"
                :class="[
                  'w-full text-left px-3.5 py-2 text-xs font-semibold flex items-center justify-between transition-colors',
                  sortBy === 'recent' ? 'bg-[#7045F5]/10 text-[#7045F5]' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                ]"
              >
                <span>{{ isKm ? 'ថ្មីបំផុត' : 'Newest / Recent' }}</span>
                <Check v-if="sortBy === 'recent'" class="w-3.5 h-3.5 text-[#7045F5]" />
              </button>

              <button
                type="button"
                @click="sortBy = 'title'; isSortOpen = false"
                :class="[
                  'w-full text-left px-3.5 py-2 text-xs font-semibold flex items-center justify-between transition-colors',
                  sortBy === 'title' ? 'bg-[#7045F5]/10 text-[#7045F5]' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                ]"
              >
                <span>{{ isKm ? 'ឈ្មោះវគ្គសិក្សា A-Z' : 'Course Name A-Z' }}</span>
                <Check v-if="sortBy === 'title'" class="w-3.5 h-3.5 text-[#7045F5]" />
              </button>

              <button
                type="button"
                @click="sortBy = 'progress'; isSortOpen = false"
                :class="[
                  'w-full text-left px-3.5 py-2 text-xs font-semibold flex items-center justify-between transition-colors',
                  sortBy === 'progress' ? 'bg-[#7045F5]/10 text-[#7045F5]' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                ]"
              >
                <span>{{ isKm ? 'វឌ្ឍនភាព ខ្ពស់-ទាប' : 'Highest Progress' }}</span>
                <Check v-if="sortBy === 'progress'" class="w-3.5 h-3.5 text-[#7045F5]" />
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- 5. Course Cards Grid (Responsive 3-Column Grid on Desktop matching Reference Image) -->
      <section aria-label="Enrolled Courses List">
        <!-- Loading Skeleton State -->
        <div
          v-if="isLoading"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6"
        >
          <CourseSkeletonCard v-for="i in 6" :key="`skel-${i}`" />
        </div>

        <!-- Courses Found Grid -->
        <div
          v-else-if="filteredCourses.length > 0"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6"
        >
          <CourseCard
            v-for="course in filteredCourses"
            :key="course.id"
            :course="course"
            :locale="currentLang"
          />
        </div>

        <!-- Empty Filter State (Courses exist, but none match search/filter) -->
        <div
          v-else-if="courses && courses.length > 0"
          class="bg-white dark:bg-[#1e1f20] rounded-2xl p-8 sm:p-12 border border-slate-200/90 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.04)] text-center space-y-4 max-w-lg mx-auto"
        >
          <div class="w-16 h-16 rounded-2xl bg-purple-50 dark:bg-purple-900/20 text-[#7045F5] flex items-center justify-center mx-auto border border-purple-100 dark:border-purple-800/40">
            <Search class="w-8 h-8" />
          </div>
          <div class="space-y-1">
            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
              {{ isKm ? 'រកមិនឃើញវគ្គសិក្សាត្រូវគ្នាទេ' : 'No matching courses found' }}
            </h3>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ isKm ? 'សូមសាកល្បងផ្លាស់ប្តូរពាក្យស្វែងរក ឬដកតម្រងចេញ។' : 'Try adjusting your search query or reset filters.' }}
            </p>
          </div>
          <button
            type="button"
            @click="resetFilters"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#7045F5] hover:bg-[#5f35e0] text-white text-xs sm:text-sm font-bold shadow-md shadow-[#7045F5]/25 transition-all cursor-pointer"
          >
            <RefreshCw class="w-3.5 h-3.5" />
            <span>{{ isKm ? 'កំណត់ឡើងវិញ' : 'Reset Filters' }}</span>
          </button>
        </div>

        <!-- Empty Enrolled Courses State (No courses enrolled yet) -->
        <div
          v-else
          class="bg-white dark:bg-[#1e1f20] rounded-2xl p-8 sm:p-12 border border-slate-200/90 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.04)] text-center space-y-4 max-w-lg mx-auto"
        >
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#EDE9FE] to-[#DDD6FE] text-[#7045F5] flex items-center justify-center mx-auto border border-purple-200">
            <BookOpen class="w-8 h-8" />
          </div>
          <div class="space-y-1">
            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
              {{ isKm ? 'មិនទាន់មានវគ្គសិក្សាដែលបានចុះឈ្មោះទេ' : 'No enrolled courses yet' }}
            </h3>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ isKm ? 'ស្វែងរក និងចុះឈ្មោះវគ្គសិក្សាថ្មីៗ ដើម្បីចាប់ផ្តើមដំណើរការសិក្សារបស់អ្នក។' : 'Explore and enroll in new courses to begin your learning journey.' }}
            </p>
          </div>
          <Link
            href="/student/browse"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#7045F5] hover:bg-[#5f35e0] text-white text-xs sm:text-sm font-bold shadow-md shadow-[#7045F5]/25 transition-all"
          >
            <Sparkles class="w-4 h-4" />
            <span>{{ isKm ? 'ស្វែងរកវគ្គសិក្សា' : 'Browse Courses' }}</span>
          </Link>
        </div>
      </section>

    </div>
  </StudentLayout>
</template>

<style scoped>
.select-none-text {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Hide scrollbar for Chrome, Safari and Opera */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
/* Hide scrollbar for IE, Edge and Firefox */
.no-scrollbar {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>
