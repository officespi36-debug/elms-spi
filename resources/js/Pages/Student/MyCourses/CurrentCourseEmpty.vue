<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import CourseCard, { type EnrolledCourseDashboardItem } from '@/Components/Student/CourseCard.vue'
import CourseSkeletonCard from '@/Components/Student/CourseSkeletonCard.vue'
import { useLanguage } from '@/Services/i18n'
import {
  BookOpen,
  PlayCircle,
  CheckCircle2,
  Search,
  X,
  Filter,
  ArrowUpDown,
  GraduationCap,
  Sparkles,
  RefreshCw,
  FolderOpen,
  ArrowRight,
  Clock,
  Layers,
  AlertCircle,
  ChevronRight
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
const enrollingId = ref<number | null>(null)

// Format duration helper for recommendations
const formatDuration = (seconds?: number, fallbackLabel?: string | null) => {
  const sec = Number(seconds || 0)
  if (!sec || sec <= 0) return fallbackLabel || ''
  const totalMinutes = Math.round(sec / 60)
  if (totalMinutes <= 0) return ''
  const hours = Math.floor(totalMinutes / 60)
  const mins = totalMinutes % 60
  if (isKm.value) {
    return hours > 0 ? `${hours} ម៉ោង ${String(mins).padStart(2, '0')} នាទី` : `${mins} នាទី`
  }
  return hours > 0 ? `${hours}h ${String(mins).padStart(2, '0')}m` : `${mins}m`
}

// Formatted recent learning activities matching Image 2
const formattedActivities = computed(() => {
  if (!props.recentActivities || props.recentActivities.length === 0) {
    return []
  }
  return props.recentActivities.map((a) => {
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
    const rawTime = a.time || ''
    const shortTime = rawTime
      .replace(/(\d+)\s*hours?\s*ago/i, '$1h ago')
      .replace(/(\d+)\s*days?\s*ago/i, '$1d ago')
      .replace(/(\d+)\s*minutes?\s*ago/i, '$1m ago')
      .replace(/(\d+)\s*seconds?\s*ago/i, '$1s ago')
      .replace(/(\d+)\s*ម៉ោងមុន/i, '$1h មុន')
      .replace(/(\d+)\s*នាទីមុន/i, '$1m មុន')
      .replace(/(\d+)\s*វិនាទីមុន/i, '$1s មុន')
      .replace(/Today/i, isKm.value ? 'ថ្ងៃនេះ' : 'Today')

    return {
      ...a,
      courseDisplay: isKm.value ? (a.course_km || a.course) : a.course,
      cleanTitle,
      category,
      time: rawTime,
      shortTime,
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
    list = list.filter(c => c.major_id === targetId)
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

const handleEnrollRecommended = (course: RecommendedCourse) => {
  if (enrollingId.value === course.id) return
  if (!course.isFree) {
    router.visit(course.checkoutHref)
    return
  }
  enrollingId.value = course.id
  router.post(course.enrollHref, {}, {
    onFinish: () => {
      enrollingId.value = null
    },
  })
}
</script>

<template>
  <Head :title="isKm ? 'វគ្គសិក្សារបស់ខ្ញុំ - SPI LMS' : 'My Courses - SPI LMS'" />

  <StudentLayout
    :title="isKm ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My Courses'"
    :breadcrumbs="[
      { label: isKm ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard', href: '/student/dashboard' },
      { label: isKm ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My Courses' }
    ]"
  >
    <div class="student-courses-page space-y-6 pb-28 sm:pb-36 notranslate" translate="no">

      <!-- 1. Top Page Banner & Quick Shortcuts -->
      <div class="px-5 py-4 sm:px-6 sm:py-5 rounded-2xl border border-[#dfe3e7] dark:border-[#2d2f31] bg-gradient-to-r from-white via-indigo-50/40 to-purple-50/50 dark:from-[#1e1f20] dark:via-[#202331] dark:to-[#242031] shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5 min-w-0">
          <!-- Professional Learning Accent Icon Badge -->
          <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl overflow-hidden shadow-lg shadow-emerald-500/25 shrink-0 border border-emerald-300/40 dark:border-emerald-700/40">
            <img :src="'/images/dashboard/learning-book.png'" alt="Learning" class="w-full h-full object-cover" />
          </div>

          <div class="space-y-0.5 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ isKm ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My Courses' }}
              </h1>
              <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#7045F5]/10 text-[#7045F5] dark:text-[#a78bfa] border border-[#7045F5]/20">
                {{ isKm ? 'ផ្ទាំងសិក្សាផ្ទាល់ខ្លួន' : 'Learning Dashboard' }}
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-[1.7] line-clamp-1">
              {{ isKm ? 'តាមដាន និងបន្តការសិក្សារបស់អ្នកយ៉ាងរលូនពីកន្លែងដែលអ្នកបានផ្អាក។' : 'Track and resume your learning seamlessly right where you left off.' }}
            </p>
          </div>
        </div>

        <!-- Quick Navigation Actions -->
        <div class="flex items-center gap-2.5 shrink-0">
          <Link
            href="/student/browse"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-[#7045F5] to-[#5b32e6] hover:from-[#5f35e0] hover:to-[#4e27cc] text-white font-bold text-xs sm:text-sm shadow-md shadow-[#7045F5]/20 hover:shadow-lg transition-all duration-200"
          >
            <Sparkles class="w-4 h-4" />
            <span>{{ isKm ? 'ស្វែងរកវគ្គថ្មី' : 'Browse Catalog' }}</span>
          </Link>
          <Link
            href="/student/dashboard"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white dark:bg-[#1e1f20] hover:bg-slate-50 dark:hover:bg-[#25272a] text-slate-700 dark:text-slate-200 font-bold text-xs sm:text-sm border border-slate-200/90 dark:border-slate-800 shadow-xs hover:border-[#7045F5]/40 transition-all duration-200"
          >
            <span>{{ isKm ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard' }}</span>
            <ArrowRight class="w-3.5 h-3.5 text-slate-400" />
          </Link>
        </div>
      </div>

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
          class="px-3 py-1.5 rounded-lg bg-rose-600 text-white font-bold text-xs hover:bg-rose-700 transition-colors shrink-0"
        >
          {{ isKm ? 'ព្យាយាមម្តងទៀត' : 'Retry' }}
        </button>
      </div>

      <!-- 2. Recent Learning Activities (សកម្មភាពសិក្សាថ្មីៗ) as shown in Image 2 -->
      <section v-if="formattedActivities.length > 0" class="space-y-3.5">
        <div class="flex items-center justify-between">
          <h2 class="text-[18px] sm:text-[20px] font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
            <span>{{ isKm ? 'សកម្មភាពសិក្សាថ្មីៗ' : 'Recent Learning Activity' }}</span>
          </h2>
          <Link
            href="/student/progress/learning-time"
            class="text-[13.5px] font-semibold text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 transition-colors inline-flex items-center gap-1 group/history"
          >
            <span>{{ isKm ? 'មើលប្រវត្តិទាំងអស់' : 'View all history' }}</span>
            <ChevronRight class="w-4 h-4 group-hover/history:translate-x-0.5 transition-transform" />
          </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="(act, idx) in formattedActivities"
            :key="act.id"
            role="link"
            tabindex="0"
            @click="router.visit(act.href)"
            @keydown.enter="router.visit(act.href)"
            class="recent-activity-card group relative flex items-start gap-3.5 p-4 rounded-2xl border transition-all duration-200 cursor-pointer bg-white dark:bg-[#1e1f20] border-slate-200 dark:border-white/[0.08] hover:-translate-y-[3px] hover:border-[#a78bfa] dark:hover:border-[#a78bfa] hover:shadow-lg hover:shadow-purple-500/10 dark:hover:shadow-black/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2"
          >
            <!-- 1. Circular Play Button (exact style from Image 2: 48x48px, gradient #6366f1 -> #7c3aed, white triangle, purple glow) -->
            <Link
              :href="act.href"
              :aria-label="`${isKm ? 'បន្តមេរៀន' : 'Resume lesson'}: ${act.cleanTitle}`"
              class="w-12 h-12 rounded-full bg-gradient-to-br from-[#6366f1] to-[#7c3aed] flex items-center justify-center text-white shrink-0 shadow-lg shadow-purple-500/25 group-hover:scale-105 transition-all duration-200 focus:outline-none"
              @click.stop
            >
              <svg class="w-5 h-5 ml-0.5 fill-white text-white drop-shadow-xs" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="6 3 20 12 6 21 6 3"></polygon>
              </svg>
            </Link>

            <!-- 2. Content -->
            <div class="flex-1 min-w-0 space-y-1">
              <!-- Top line: Category • Time & Resume link on hover -->
              <div class="flex items-center justify-between gap-1 text-[12px] text-slate-500 dark:text-slate-400 font-medium">
                <span class="whitespace-nowrap shrink-0">{{ act.category }} · {{ act.shortTime }}</span>
                <span class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 text-xs font-semibold text-purple-600 dark:text-purple-400 inline-flex items-center gap-0.5 shrink-0 whitespace-nowrap">
                  {{ isKm ? 'បន្ត' : 'Resume' }}
                  <ChevronRight class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" />
                </span>
              </div>

              <!-- Lesson Title -->
              <h4
                class="font-bold text-[15px] text-slate-900 dark:text-[#e2e8f0] leading-snug line-clamp-2 min-h-[2.6rem] group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors break-words"
                :title="act.cleanTitle"
              >
                {{ act.cleanTitle }}
              </h4>

              <!-- Course Title -->
              <p class="text-[14px] text-[#475569] dark:text-slate-400 truncate font-medium">
                {{ act.courseDisplay }}
              </p>

              <!-- Progress Row & Bar -->
              <div class="pt-1.5 space-y-1">
                <div class="flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                  <span>{{ isKm ? 'វឌ្ឍនភាព' : 'Progress' }}</span>
                  <span class="font-semibold text-slate-700 dark:text-slate-300">{{ act.progress }}% {{ isKm ? 'បានមើល' : 'watched' }}</span>
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
      </section>

      <!-- 3. Search and Filter Bar -->
      <section aria-label="Course Filters" class="bg-white dark:bg-[#1e1f20] rounded-2xl p-4 sm:p-5 border border-[#dfe3e7] dark:border-[#2d2f31] shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3.5">
          <!-- Search Input -->
          <div class="relative flex-1">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="isKm ? 'ស្វែងរកវគ្គសិក្សា, គ្រូ ឬជំនាញ...' : 'Search courses, instructor, or major...'"
              class="w-full pl-10 pr-9 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-[#dfe3e7] dark:border-[#2d2f31] text-xs sm:text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#7045F5]/30 focus:border-[#7045F5] transition-all"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click="searchQuery = ''"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 rounded-full cursor-pointer"
              aria-label="Clear search"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Filters Group: Major & Sort -->
          <div class="flex items-center gap-2.5 shrink-0 flex-wrap sm:flex-nowrap">
            <!-- Major Dropdown -->
            <select
              v-if="majors && majors.length > 0"
              v-model="selectedMajor"
              class="px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-[#dfe3e7] dark:border-[#2d2f31] text-xs sm:text-sm text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-[#7045F5]/30 focus:border-[#7045F5] transition-all cursor-pointer"
            >
              <option value="all">{{ isKm ? 'ជំនាញទាំងអស់' : 'All Majors' }}</option>
              <option v-for="m in majors" :key="m.id" :value="m.id">
                {{ isKm && m.name_kh ? m.name_kh : m.name }}
              </option>
            </select>

            <!-- Sort Dropdown -->
            <select
              v-model="sortBy"
              class="px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-[#dfe3e7] dark:border-[#2d2f31] text-xs sm:text-sm text-slate-800 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-[#7045F5]/30 focus:border-[#7045F5] transition-all cursor-pointer"
            >
              <option value="recent">{{ isKm ? 'បានចូលរៀនថ្មីៗ' : 'Recently Accessed' }}</option>
              <option value="title">{{ isKm ? 'ឈ្មោះវគ្គសិក្សា A-Z' : 'Course Name A-Z' }}</option>
              <option value="progress">{{ isKm ? 'វឌ្ឍនភាព ខ្ពស់-ទាប' : 'Highest Progress' }}</option>
            </select>
          </div>
        </div>

        <!-- Status Tabs Row -->
        <div class="flex items-center gap-2 pt-3 border-t border-slate-100 dark:border-slate-800/80 overflow-x-auto no-scrollbar">
          <button
            type="button"
            @click="selectedStatus = 'all'"
            :class="[
              'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 shrink-0 select-none cursor-pointer',
              selectedStatus === 'all'
                ? 'bg-[#7045F5] text-white shadow-md shadow-[#7045F5]/25'
                : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/60 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'
            ]"
          >
            <span>{{ isKm ? 'ទាំងអស់' : 'All Courses' }}</span>
            <span
              :class="[
                'px-2 py-0.5 rounded-full text-[10px] font-extrabold',
                selectedStatus === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
              ]"
            >
              {{ totalCoursesCount }}
            </span>
          </button>

          <button
            type="button"
            @click="selectedStatus = 'in_progress'"
            :class="[
              'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 shrink-0 select-none cursor-pointer',
              selectedStatus === 'in_progress'
                ? 'bg-[#2563EB] text-white shadow-md shadow-[#2563EB]/25'
                : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/60 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'
            ]"
          >
            <span>{{ isKm ? 'កំពុងរៀន' : 'In Progress' }}</span>
            <span
              :class="[
                'px-2 py-0.5 rounded-full text-[10px] font-extrabold',
                selectedStatus === 'in_progress' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
              ]"
            >
              {{ inProgressCount }}
            </span>
          </button>

          <button
            type="button"
            @click="selectedStatus = 'completed'"
            :class="[
              'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 shrink-0 select-none cursor-pointer',
              selectedStatus === 'completed'
                ? 'bg-[#10B981] text-white shadow-md shadow-[#10B981]/25'
                : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/60 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'
            ]"
          >
            <span>{{ isKm ? 'បានបញ្ចប់' : 'Completed' }}</span>
            <span
              :class="[
                'px-2 py-0.5 rounded-full text-[10px] font-extrabold',
                selectedStatus === 'completed' ? 'bg-white/20 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
              ]"
            >
              {{ completedCount }}
            </span>
          </button>

          <button
            v-if="searchQuery || selectedMajor !== 'all' || selectedStatus !== 'all'"
            type="button"
            @click="resetFilters"
            class="ml-auto px-3 py-1.5 rounded-lg text-xs font-semibold text-[#7045F5] hover:bg-[#7045F5]/10 transition-colors flex items-center gap-1 shrink-0 cursor-pointer"
          >
            <RefreshCw class="w-3 h-3" />
            <span>{{ isKm ? 'កំណត់ឡើងវិញ' : 'Reset' }}</span>
          </button>
        </div>
      </section>

        <!-- 4. Course Cards Grid (Responsive 3-col on lg, 2-col on md, 1-col on mobile) -->
        <section aria-label="Enrolled Courses List">
          <!-- Loading Skeleton State -->
          <div
            v-if="isLoading"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
          >
            <CourseSkeletonCard v-for="i in 6" :key="`skel-${i}`" />
          </div>

          <!-- Courses Found Grid -->
          <div
            v-else-if="filteredCourses.length > 0"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
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
                {{ isKm ? 'សូមសាកល្បងផ្លាស់ប្តូរពាក្យស្វែងរក ឬដោះការច្រោះចេញ។' : 'Try adjusting your search query or resetting filters.' }}
              </p>
            </div>
            <button
              type="button"
              @click="resetFilters"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#7045F5] hover:bg-[#5f35e0] text-white font-bold text-xs sm:text-sm shadow-md shadow-[#7045F5]/25 transition-all"
            >
              <RefreshCw class="w-3.5 h-3.5" />
              <span>{{ isKm ? 'សម្អាតការច្រោះទាំងអស់' : 'Clear all filters' }}</span>
            </button>
          </div>

          <!-- Empty Enrollments State (Student has 0 enrolled courses) -->
          <div
            v-else
            class="space-y-8"
          >
            <!-- Friendly Empty Hero Box -->
            <div class="bg-white dark:bg-[#1e1f20] rounded-3xl p-8 sm:p-12 border border-slate-200/90 dark:border-slate-800 shadow-sm text-center relative overflow-hidden max-w-3xl mx-auto space-y-5">
              <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(112,69,245,0.08),transparent_70%)] pointer-events-none"></div>

              <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl overflow-hidden shadow-lg mx-auto relative z-10 border border-emerald-300/40 dark:border-emerald-700/40">
                <img :src="'/images/dashboard/learning-book.png'" alt="Learning" class="w-full h-full object-cover" />
              </div>

              <div class="space-y-2 relative z-10">
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                  {{ isKm ? 'អ្នកមិនទាន់បានចុះឈ្មោះក្នុងវគ្គសិក្សាណាមួយនៅឡើយទេ' : 'You haven’t enrolled in any courses yet' }}
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 max-w-xl mx-auto leading-[1.7]">
                  {{ isKm ? 'ចាប់ផ្តើមដំណើរសិក្សារបស់អ្នកនៅថ្ងៃនេះ ដោយជ្រើសរើសវគ្គសិក្សាដែលបានណែនាំខាងក្រោម ឬស្វែងរកនៅក្នុងកាតាឡុកវគ្គសិក្សាពេញលេញ។' : 'Start your learning journey today by exploring our curated recommended courses below or browse our full catalog.' }}
                </p>
              </div>

              <div class="pt-2 relative z-10 flex flex-wrap justify-center gap-3">
                <Link
                  href="/student/browse"
                  class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#7045F5] to-[#5b32e6] hover:from-[#5f35e0] hover:to-[#4e27cc] text-white font-bold text-sm shadow-md shadow-[#7045F5]/30 hover:shadow-lg transition-all"
                >
                  <Sparkles class="w-4 h-4" />
                  <span>{{ isKm ? 'ស្វែងរកវគ្គសិក្សាទាំងអស់' : 'Explore All Courses' }}</span>
                </Link>
              </div>
            </div>

            <!-- Recommended Courses Section (when total courses = 0) -->
            <div v-if="recommendedCourses && recommendedCourses.length > 0" class="space-y-4 pt-4">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <Sparkles class="w-5 h-5 text-[#7045F5]" />
                    <span>{{ isKm ? 'វគ្គសិក្សាណែនាំសម្រាប់អ្នក' : 'Recommended Courses for You' }}</span>
                  </h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ isKm ? 'ផ្អែកលើជំនាញ និងកម្រិតសិក្សារបស់អ្នក' : 'Curated based on your major and learning goals' }}
                  </p>
                </div>

                <Link
                  href="/student/browse"
                  class="text-xs sm:text-sm font-bold text-[#7045F5] hover:underline inline-flex items-center gap-1"
                >
                  <span>{{ isKm ? 'មើលទាំងអស់' : 'View all' }}</span>
                  <ArrowRight class="w-3.5 h-3.5" />
                </Link>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                  v-for="rec in recommendedCourses"
                  :key="rec.id"
                  class="bg-white dark:bg-[#1e1f20] rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.04)] overflow-hidden flex flex-col justify-between hover:-translate-y-1 transition-all duration-300"
                >
                  <!-- Rec Cover -->
                  <div class="aspect-video w-full relative bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <img
                      v-if="rec.thumbnail"
                      :src="rec.thumbnail"
                      :alt="isKm && rec.titleKh ? rec.titleKh : rec.title"
                      class="w-full h-full object-cover"
                    />
                    <img
                      v-else
                      :src="'/images/courses/default-study-book.png'"
                      :alt="isKm && rec.titleKh ? rec.titleKh : rec.title"
                      class="w-full h-full object-cover"
                    />

                    <!-- Category Pill -->
                    <div class="absolute top-3 left-3">
                      <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/90 dark:bg-slate-900/90 text-slate-800 dark:text-slate-200 backdrop-blur-md border border-white/30 dark:border-slate-700/50 shadow-xs">
                        {{ isKm && rec.categoryKh ? rec.categoryKh : (rec.category || 'Academic') }}
                      </span>
                    </div>

                    <!-- Price badge -->
                    <div class="absolute top-3 right-3">
                      <span
                        :class="[
                          'px-2.5 py-1 rounded-full text-[11px] font-bold backdrop-blur-md border shadow-xs',
                          rec.isFree
                            ? 'bg-emerald-500/90 text-white border-emerald-400/50'
                            : 'bg-purple-600/90 text-white border-purple-400/50'
                        ]"
                      >
                        {{ rec.priceDisplay }}
                      </span>
                    </div>
                  </div>

                  <!-- Rec Body -->
                  <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                      <h4 class="font-bold text-slate-900 dark:text-white text-base line-clamp-2 leading-[1.7]">
                        {{ isKm && rec.titleKh ? rec.titleKh : rec.title }}
                      </h4>
                      <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <Clock class="w-3.5 h-3.5 text-slate-400" />
                        <span>{{ rec.lessonsCount }} {{ isKm ? 'មេរៀន' : 'lessons' }}</span>
                        <span v-if="rec.durationSeconds" class="text-slate-300 dark:text-slate-600">•</span>
                        <span v-if="rec.durationSeconds">{{ formatDuration(rec.durationSeconds, rec.durationLabel) }}</span>
                      </p>
                    </div>

                    <!-- Rec Actions -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-2">
                      <Link
                        :href="rec.overviewHref"
                        class="flex-1 py-2 rounded-xl text-center text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors"
                      >
                        {{ isKm ? 'មើលព័ត៌មាន' : 'Overview' }}
                      </Link>

                      <button
                        type="button"
                        @click="handleEnrollRecommended(rec)"
                        :disabled="enrollingId === rec.id"
                        class="flex-1 py-2 rounded-xl text-center text-xs font-bold bg-[#7045F5] hover:bg-[#5f35e0] text-white shadow-xs transition-colors disabled:opacity-50"
                      >
                        {{ enrollingId === rec.id ? (isKm ? 'កំពុងចុះឈ្មោះ...' : 'Enrolling...') : (rec.isFree ? (isKm ? 'ចុះឈ្មោះរៀន' : 'Enroll Now') : (isKm ? 'ទិញវគ្គសិក្សា' : 'Buy Course')) }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

    </div>
  </StudentLayout>
</template>

<style scoped>
/* Clean custom styles if needed */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
