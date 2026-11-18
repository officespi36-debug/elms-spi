<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

const { t } = useLanguage()

interface RequirementItem {
  label: string
  value: string
  done: boolean
}

interface AvailableCertificate {
  id: number
  title: string
  title_kh?: string | null
  issuer: string
  description: string
  progress: number
  level: string
  category: string
  major_id?: number | null
  status_type: 'in_progress' | 'almost_there' | 'not_started'
  badge_color: string
  bar_color: string
  is_enrolled?: boolean
  course_url?: string
  requirements: RequirementItem[]
}

interface OverviewSegment {
  label: string
  count: number
  percentage: number
  color: string
}

interface CategoryItem {
  id: number
  name: string
  name_kh?: string | null
  count: number
  color: string
}

interface CourseOption {
  id: number | string
  title: string
}

const props = defineProps<{
  analytics?: {
    summary: {
      total_available: number
      total_note: string
      in_progress: number
      in_progress_note: string
      almost_there: number
      almost_note: string
      not_started: number
      not_started_note: string
      earned_this_year: number
      earned_note: string
    }
    progress_overview: {
      total: number
      items: OverviewSegment[]
    }
    popular_categories: CategoryItem[]
    certificates: AvailableCertificate[]
    total_count: number
    current_page: number
    per_page: number
    last_page?: number
    filter_options?: {
      categories?: string[]
      courses?: CourseOption[]
      levels?: string[]
    }
  }
  filters?: {
    status: string
    category: string
    course: string
    level: string
    sort: string
    search: string
    page: number
  }
}>()

const defaultSummary = {
  total_available: 0,
  total_note: 'Certificates available',
  in_progress: 0,
  in_progress_note: 'You are working on',
  almost_there: 0,
  almost_note: '80% or more completed',
  not_started: 0,
  not_started_note: 'Ready to begin',
  earned_this_year: 0,
  earned_note: 'Certificates earned',
}

const defaultProgressOverview = {
  total: 0,
  items: [
    { label: 'In Progress',  count: 0, percentage: 0, color: '#3B82F6' },
    { label: 'Almost There', count: 0, percentage: 0, color: '#10B981' },
    { label: 'Not Started',  count: 0, percentage: 0, color: '#F59E0B' },
  ],
}

const summary = computed(() => props.analytics?.summary || defaultSummary)
const progressOverview = computed(() => props.analytics?.progress_overview || defaultProgressOverview)
const popularCategories = computed<CategoryItem[]>(() => props.analytics?.popular_categories || [])
const certificates = computed<AvailableCertificate[]>(() => props.analytics?.certificates || [])
const totalCount = computed(() => props.analytics?.total_count ?? certificates.value.length)
const currentPage = computed(() => props.analytics?.current_page ?? 1)
const perPage = computed(() => props.analytics?.per_page ?? 8)
const lastPage = computed(() => props.analytics?.last_page ?? Math.max(1, Math.ceil(totalCount.value / Math.max(1, perPage.value))))

const showingFrom = computed(() => (totalCount.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1))
const showingTo = computed(() => (totalCount.value === 0 ? 0 : Math.min(totalCount.value, currentPage.value * perPage.value)))

// Dynamic SVG Donut segments
const donutSegments = computed(() => {
  if (!progressOverview.value.total || progressOverview.value.total <= 0) return []
  let cumulative = 0
  return progressOverview.value.items
    .filter((item) => item.percentage > 0)
    .map((item) => {
      const seg = {
        label: item.label,
        color: item.color,
        dashArray: `${item.percentage} ${Math.max(0, 100 - item.percentage)}`,
        dashOffset: -cumulative,
      }
      cumulative += item.percentage
      return seg
    })
})

// Dynamic Filter Options
const categoryOptions = computed<string[]>(() => {
  const fromProps = props.analytics?.filter_options?.categories
  if (Array.isArray(fromProps) && fromProps.length > 0) return fromProps
  if (popularCategories.value.length > 0) {
    return Array.from(new Set(popularCategories.value.map((c) => c.name).filter(Boolean)))
  }
  return Array.from(new Set(certificates.value.map((c) => c.category).filter(Boolean)))
})

const courseOptions = computed<CourseOption[]>(() => {
  const fromProps = props.analytics?.filter_options?.courses
  if (Array.isArray(fromProps) && fromProps.length > 0) return fromProps
  return certificates.value.map((c) => ({ id: c.id, title: c.title }))
})

const levelOptions = computed<string[]>(() => {
  const fromProps = props.analytics?.filter_options?.levels
  if (Array.isArray(fromProps) && fromProps.length > 0) return fromProps
  return Array.from(new Set(certificates.value.map((c) => c.level).filter(Boolean)))
})

// Filter & View States
const activeStatusTab = ref<string>(props.filters?.status || 'all')
const searchQuery = ref<string>(props.filters?.search || '')
const selectedCategory = ref<string>(props.filters?.category || 'all')
const selectedCourse = ref<string>(props.filters?.course || 'all')
const selectedLevel = ref<string>(props.filters?.level || 'all')
const selectedSort = ref<string>(props.filters?.sort || 'progress')
const isGridView = ref(true)

const hasActiveFilters = computed(() => {
  return (
    activeStatusTab.value !== 'all' ||
    selectedCategory.value !== 'all' ||
    selectedCourse.value !== 'all' ||
    selectedLevel.value !== 'all' ||
    searchQuery.value.trim() !== ''
  )
})

// Modals State
const selectedCertForModal = ref<AvailableCertificate | null>(null)
const isRequirementsModalOpen = ref(false)

const openRequirementsModal = (cert: AvailableCertificate) => {
  selectedCertForModal.value = cert
  isRequirementsModalOpen.value = true
}

const handleFilterChange = (overrideTab?: string, targetPage = 1) => {
  if (overrideTab !== undefined) {
    activeStatusTab.value = overrideTab
  }
  router.get('/student/certificates/download-share', {
    status: activeStatusTab.value,
    category: selectedCategory.value,
    course: selectedCourse.value,
    level: selectedLevel.value,
    sort: selectedSort.value,
    search: searchQuery.value,
    page: targetPage,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const goToPage = (pageNum: number) => {
  if (pageNum < 1 || pageNum > lastPage.value || pageNum === currentPage.value) return
  handleFilterChange(undefined, pageNum)
}

const visiblePages = computed<number[]>(() => {
  const pages: number[] = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(lastPage.value, start + 4)
  for (let p = start; p <= end; p++) {
    pages.push(p)
  }
  return pages
})

const resetFilters = () => {
  activeStatusTab.value = 'all'
  selectedCategory.value = 'all'
  selectedCourse.value = 'all'
  selectedLevel.value = 'all'
  searchQuery.value = ''
  selectedSort.value = 'progress'
  handleFilterChange('all', 1)
}
</script>

<template>
  <StudentLayout title="Available Certificates — Quiz & Assessment">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>{{ t('វិញ្ញាបនបត្រដែលអាចទទួលបាន', 'Available Certificates') }}</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-600 dark:text-purple-400 text-lg">📄</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
            {{ t('ស្វែងយល់ពីវគ្គសិក្សាដែលផ្តល់វិញ្ញាបនបត្រ និងតាមដានលក្ខខណ្ឌបញ្ចប់ការសិក្សារបស់អ្នក។', 'Explore certificates you can earn and track your course completion requirements.') }}
          </p>
        </div>
      </div>

      <!-- ================= 2. 5 TOP SUMMARY METRIC CARDS ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">

        <!-- Card 1: Total Available -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ t('វិញ្ញាបនបត្រអាចទទួលបាន', 'Total Available') }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.total_available }}</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">{{ summary.total_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-500/10 dark:bg-purple-600/20 border border-purple-500/20 dark:border-purple-500/30 text-purple-600 dark:text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🛡️
          </div>
        </div>

        <!-- Card 2: In Progress -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-blue-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ t('កំពុងសិក្សា', 'In Progress') }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.in_progress }}</p>
            <p class="text-[10px] text-blue-600 dark:text-blue-400 font-medium font-mono">{{ summary.in_progress_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-blue-500/10 dark:bg-blue-600/20 border border-blue-500/20 dark:border-blue-500/30 text-blue-600 dark:text-blue-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📅
          </div>
        </div>

        <!-- Card 3: Almost There -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-emerald-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ t('ជិតបញ្ចប់', 'Almost There') }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.almost_there }}</p>
            <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium font-mono">{{ summary.almost_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 dark:bg-emerald-600/20 border border-emerald-500/20 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🎯
          </div>
        </div>

        <!-- Card 4: Not Started -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-amber-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ t('មិនទាន់ចាប់ផ្តើម', 'Not Started') }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.not_started }}</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">{{ summary.not_started_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-amber-500/10 dark:bg-amber-600/20 border border-amber-500/20 dark:border-amber-500/30 text-amber-600 dark:text-amber-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            ⭐
          </div>
        </div>

        <!-- Card 5: Earned This Year -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ t('ទទួលបានក្នុងឆ្នាំនេះ', 'Earned This Year') }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.earned_this_year }}</p>
            <p class="text-[10px] text-purple-600 dark:text-purple-400 font-medium font-mono">{{ summary.earned_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-500/10 dark:bg-purple-600/20 border border-purple-500/20 dark:border-purple-500/30 text-purple-600 dark:text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🏆
          </div>
        </div>

      </div>

      <!-- ================= 3. MAIN SPLIT (LEFT: 8/12, RIGHT: 4/12) ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT (8/12): SEARCH, TABS & CATALOG ================= -->
        <div class="lg:col-span-8 space-y-6">

          <!-- SEARCH & FILTER CONTROLS BAR -->
          <div class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-[#0F172A]/80 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-3 shadow-sm dark:shadow-lg">

            <!-- Search Input -->
            <div class="relative flex-1 min-w-[180px]">
              <input
                type="text"
                v-model="searchQuery"
                @keyup.enter="handleFilterChange()"
                :placeholder="t('ស្វែងរកវគ្គសិក្សា...', 'Search certificates...')"
                class="w-full bg-slate-50 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3 py-1.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-purple-500 shadow-xs"
              />
              <span class="absolute left-3 top-2 text-xs text-slate-400 dark:text-slate-500">🔍</span>
            </div>

            <!-- Filter Dropdowns -->
            <div class="flex flex-wrap items-center gap-2">
              <select
                v-model="selectedCategory"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="all">{{ t('គ្រប់ជំនាញទាំងអស់', 'All Categories') }}</option>
                <option v-for="cat in categoryOptions" :key="cat" :value="cat">{{ cat }}</option>
              </select>

              <select
                v-model="selectedCourse"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="all">{{ t('គ្រប់វគ្គសិក្សាទាំងអស់', 'All Courses') }}</option>
                <option v-for="c in courseOptions" :key="c.id" :value="String(c.id)">{{ c.title }}</option>
              </select>

              <select
                v-model="selectedLevel"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="all">{{ t('គ្រប់កម្រិតសិក្សា', 'All Levels') }}</option>
                <option v-for="lvl in levelOptions" :key="lvl" :value="lvl">{{ lvl }}</option>
              </select>

              <select
                v-model="selectedSort"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="progress">{{ t('តម្រៀបតាមវឌ្ឍនភាព', 'Sort by Progress') }}</option>
                <option value="title">{{ t('តម្រៀបតាមឈ្មោះ', 'Sort by Name') }}</option>
              </select>

              <!-- View Switch -->
              <div class="flex items-center bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-xl p-0.5 shadow-xs">
                <button
                  @click="isGridView = true"
                  :class="[isGridView ? 'bg-purple-600 text-white' : 'text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white', 'p-1 rounded-lg text-xs transition-colors cursor-pointer']"
                  title="Grid View"
                >
                  ▦
                </button>
                <button
                  @click="isGridView = false"
                  :class="[!isGridView ? 'bg-purple-600 text-white' : 'text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white', 'p-1 rounded-lg text-xs transition-colors cursor-pointer']"
                  title="List View"
                >
                  ☰
                </button>
              </div>
            </div>

          </div>

          <!-- STATUS TABS -->
          <div class="flex items-center gap-1.5 bg-white dark:bg-[#0F172A]/80 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-1.5 shadow-sm dark:shadow-lg overflow-x-auto custom-scrollbar">
            <button
              v-for="tab in [
                { key: 'all', label: t('ទាំងអស់', 'All Certificates') },
                { key: 'in_progress', label: t('កំពុងសិក្សា', 'In Progress') },
                { key: 'almost_there', label: t('ជិតបញ្ចប់', 'Almost There') },
                { key: 'not_started', label: t('មិនទាន់ចាប់ផ្តើម', 'Not Started') }
              ]"
              :key="tab.key"
              @click="handleFilterChange(tab.key, 1)"
              :class="[
                activeStatusTab === tab.key
                  ? 'bg-purple-600 text-white shadow-md shadow-purple-900/20'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white',
                'px-4 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer'
              ]"
            >
              {{ tab.label }}
            </button>
          </div>

          <!-- BILINGUAL EMPTY STATE -->
          <div
            v-if="certificates.length === 0"
            class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-10 text-center space-y-4 shadow-sm dark:shadow-xl"
          >
            <div class="w-16 h-16 rounded-2xl bg-purple-500/10 dark:bg-purple-600/20 border border-purple-500/30 text-purple-600 dark:text-purple-300 flex items-center justify-center text-3xl mx-auto shadow-inner">
              📄
            </div>
            <div class="space-y-1.5 max-w-md mx-auto">
              <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                {{ hasActiveFilters
                  ? 'រកមិនឃើញវគ្គសិក្សាតាមលក្ខខណ្ឌស្វែងរក · No Matching Courses'
                  : 'មិនទាន់មានវគ្គសិក្សាសម្រាប់ទទួលវិញ្ញាបនបត្រនៅឡើយទេ · No Available Certificates Yet'
                }}
              </h3>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                {{ hasActiveFilters
                  ? t('សូមសាកល្បងផ្លាស់ប្តូរ ឬកំណត់ឡើងវិញនូវតម្រងស្វែងរករបស់អ្នក។', 'Try adjusting or resetting your search filters to find courses.')
                  : t('វគ្គសិក្សាដែលបានបោះពុម្ពផ្សាយ និងអាចទទួលបានវិញ្ញាបនបត្រនឹងបង្ហាញនៅទីនេះ។', 'Published courses eligible for official certificates will appear here once available.')
                }}
              </p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
              <button
                v-if="hasActiveFilters"
                @click="resetFilters"
                class="px-5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer"
              >
                {{ t('កំណត់តម្រងឡើងវិញ', 'Reset Filters') }}
              </button>
              <Link
                href="/student/browse"
                class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/20 flex items-center gap-2 transition-all"
              >
                <span>📚</span>
                <span>{{ t('ស្វែងរកវគ្គសិក្សា · Browse Courses', 'Browse Courses · ស្វែងរកវគ្គសិក្សា') }}</span>
              </Link>
            </div>
          </div>

          <!-- CERTIFICATES CATALOG -->
          <div v-else :class="[isGridView ? 'grid grid-cols-1 sm:grid-cols-2 gap-4' : 'space-y-3']">
            <div
              v-for="cert in certificates"
              :key="cert.id"
              class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 hover:border-purple-500/40 rounded-3xl p-4 shadow-sm dark:shadow-xl flex flex-col justify-between group transition-all cursor-pointer relative"
              @click="openRequirementsModal(cert)"
            >
              <!-- TOP: MINI CERTIFICATE PREVIEW CANVAS + PROGRESS BADGE -->
              <div class="relative w-full aspect-[16/10] bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-inner flex flex-col items-center justify-between p-3 text-center group-hover:scale-[1.01] transition-transform">

                <div class="absolute inset-1.5 border border-amber-500/30 rounded-xl pointer-events-none"></div>

                <!-- Top Right Percentage Pill -->
                <div
                  :class="[
                    cert.badge_color,
                    'absolute top-2 right-2 px-2 py-0.5 rounded-lg text-[10px] font-black font-mono shadow-md z-20'
                  ]"
                >
                  {{ cert.progress }}%
                </div>

                <!-- Certificate Title Preview -->
                <div class="relative z-10 pt-1">
                  <p class="text-[7.5px] tracking-[0.2em] uppercase font-serif text-amber-400 font-bold">CERTIFICATE OF COMPLETION</p>
                </div>

                <div class="relative z-10 my-auto space-y-0.5">
                  <p class="text-[11px] font-black text-white font-serif tracking-wide truncate max-w-[200px]">{{ cert.title }}</p>
                  <div class="w-12 h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent mx-auto"></div>
                </div>

                <!-- Bottom Seal -->
                <div class="relative z-10 w-full flex items-center justify-between px-2 text-[6.5px] text-slate-400">
                  <p>{{ cert.category }}</p>
                  <div class="w-5 h-5 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-slate-950 flex items-center justify-center font-bold text-[7px]">
                    ★
                  </div>
                  <p>Certified</p>
                </div>
              </div>

              <!-- CARD BODY INFO -->
              <div class="pt-3 pb-1 space-y-1.5">
                <div>
                  <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate">
                    {{ cert.title }}
                  </h3>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ cert.issuer }}</p>
                </div>

                <p class="text-[11px] text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed">
                  {{ cert.description }}
                </p>

                <!-- Progress Bar -->
                <div class="space-y-1 pt-1">
                  <div class="flex items-center justify-between text-[10px]">
                    <span class="text-slate-500 dark:text-slate-400 font-medium">{{ t('វឌ្ឍនភាព', 'Progress') }}</span>
                    <span class="font-bold text-slate-900 dark:text-white font-mono">{{ cert.progress }}%</span>
                  </div>
                  <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-950 rounded-full overflow-hidden">
                    <div
                      class="h-full rounded-full"
                      :class="cert.bar_color"
                      :style="{ width: `${cert.progress}%` }"
                    ></div>
                  </div>
                </div>
              </div>

              <!-- CARD FOOTER BADGES -->
              <div class="flex items-center justify-between pt-2.5 border-t border-slate-100 dark:border-slate-800/80 text-[10px] text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1 font-medium text-slate-700 dark:text-slate-300">
                  <span>◈</span>
                  <span>{{ cert.level }}</span>
                </span>
                <span class="flex items-center gap-1 font-medium text-purple-600 dark:text-purple-300">
                  <span>📄</span>
                  <span>{{ cert.is_enrolled ? t('បានចុះឈ្មោះ', 'Enrolled') : t('មើលលក្ខខណ្ឌ', 'Requirements') }}</span>
                </span>
              </div>

            </div>
          </div>

          <!-- DYNAMIC PAGINATION -->
          <div
            v-if="totalCount > 0"
            class="p-4 bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl flex flex-col sm:flex-row items-center justify-between gap-3 text-xs shadow-xs dark:shadow-md"
          >
            <span class="text-slate-500 dark:text-slate-400 text-[11px]">
              Showing {{ showingFrom }} to {{ showingTo }} of {{ totalCount }} certificates
            </span>

            <div class="flex items-center gap-1.5">
              <button
                @click="goToPage(1)"
                :disabled="currentPage <= 1"
                class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
              >
                «
              </button>
              <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage <= 1"
                class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
              >
                ‹
              </button>
              <button
                v-for="p in visiblePages"
                :key="p"
                @click="goToPage(p)"
                :class="[
                  p === currentPage
                    ? 'bg-purple-600 text-white shadow-xs'
                    : 'bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white cursor-pointer',
                  'w-7 h-7 rounded-lg font-bold text-xs flex items-center justify-center'
                ]"
              >
                {{ p }}
              </button>
              <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage >= lastPage"
                class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
              >
                ›
              </button>
              <button
                @click="goToPage(lastPage)"
                :disabled="currentPage >= lastPage"
                class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
              >
                »
              </button>
            </div>
          </div>

        </div>

        <!-- ================= RIGHT (4/12): WIDGETS SIDEBAR ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- WIDGET 1: Certificate Progress Overview Donut Chart -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ t('ទិដ្ឋភាពវឌ្ឍនភាពវិញ្ញាបនបត្រ', 'Certificate Progress Overview') }}</h3>
            </div>

            <div class="flex items-center justify-between gap-4">
              <!-- Dynamic SVG Donut Chart -->
              <div class="relative w-24 h-24 flex items-center justify-center shrink-0">
                <svg class="w-24 h-24 -rotate-90 transform" viewBox="0 0 36 36">
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#E2E8F0" class="dark:stroke-slate-800" stroke-width="4.5" />
                  <circle
                    v-for="seg in donutSegments"
                    :key="seg.label"
                    cx="18"
                    cy="18"
                    r="15.9155"
                    fill="none"
                    :stroke="seg.color"
                    stroke-width="4.5"
                    :stroke-dasharray="seg.dashArray"
                    :stroke-dashoffset="seg.dashOffset"
                  />
                </svg>

                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-slate-900 dark:text-white font-mono leading-none">{{ progressOverview.total }}</span>
                  <span class="text-[8px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">{{ t('សរុប', 'Total') }}</span>
                </div>
              </div>

              <!-- Legend Breakdown -->
              <div class="space-y-1.5 text-xs flex-1">
                <div
                  v-for="item in progressOverview.items"
                  :key="item.label"
                  class="flex items-center justify-between text-[11px]"
                >
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: item.color }"></span>
                    <span class="text-slate-600 dark:text-slate-300 font-medium">{{ item.label }}</span>
                  </div>
                  <span class="font-bold text-slate-900 dark:text-white font-mono">{{ item.count }} ({{ item.percentage }}%)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- WIDGET 2: Popular Certificate Categories -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ t('ជំនាញដែលមានវិញ្ញាបនបត្រ', 'Certificate Categories') }}</h3>
            </div>

            <div v-if="popularCategories.length === 0" class="py-5 text-center space-y-1">
              <p class="text-xl">📂</p>
              <p class="text-xs font-bold text-slate-700 dark:text-slate-300">
                មិនទាន់មានជំនាញសិក្សានៅឡើយទេ · No Categories Yet
              </p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                {{ t('ជំនាញសិក្សានឹងបង្ហាញនៅពេលមានវគ្គសិក្សា។', 'Categories appear once courses are published.') }}
              </p>
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="cat in popularCategories"
                :key="cat.id"
                @click="selectedCategory = cat.name; handleFilterChange()"
                class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/60 flex items-center justify-between gap-3 hover:border-purple-500/30 transition-all cursor-pointer group shadow-xs"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      cat.color,
                      'w-7 h-7 rounded-xl flex items-center justify-center text-xs shrink-0 shadow-sm'
                    ]"
                  >
                    📂
                  </div>
                  <p class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate">
                    {{ cat.name }}
                  </p>
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                  <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono whitespace-nowrap">
                    {{ cat.count }} {{ cat.count === 1 ? 'course' : 'courses' }}
                  </span>
                  <span class="text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white transition-colors text-xs">›</span>
                </div>
              </div>
            </div>

            <button
              v-if="popularCategories.length > 0"
              @click="selectedCategory = 'all'; handleFilterChange()"
              class="w-full text-center text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline pt-1 cursor-pointer"
            >
              {{ t('មើលជំនាញទាំងអស់', 'View All Categories') }}
            </button>
          </div>

          <!-- WIDGET 3: How to Earn Certificates (4-Step Flow) -->
          <div class="bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/60 dark:from-[#10132B] dark:via-[#0F172A] dark:to-[#1E1138] border border-purple-200 dark:border-purple-900/50 rounded-3xl p-5 shadow-sm dark:shadow-2xl space-y-4">
            <div class="border-b border-purple-200 dark:border-purple-900/40 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ t('របៀបទទួលបានវិញ្ញាបនបត្រ', 'How to Earn Certificates') }}</h3>
            </div>

            <div class="space-y-3 text-xs">

              <!-- Step 1 -->
              <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-blue-500/10 dark:bg-blue-600/30 border border-blue-500/30 dark:border-blue-500/50 text-blue-700 dark:text-blue-300 flex items-center justify-center text-xs font-black font-mono shrink-0">
                  1
                </div>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">{{ t('ចុះឈ្មោះចូលរៀនវគ្គសិក្សា', 'Enroll in a course') }}</p>
                  <p class="text-[10px] text-slate-600 dark:text-slate-400">{{ t('ជ្រើសរើសវគ្គសិក្សាដែលអ្នកចង់រៀន។', 'Choose a course with an official certificate.') }}</p>
                </div>
              </div>

              <!-- Step 2 -->
              <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-blue-500/10 dark:bg-blue-600/30 border border-blue-500/30 dark:border-blue-500/50 text-blue-700 dark:text-blue-300 flex items-center justify-center text-xs font-black font-mono shrink-0">
                  2
                </div>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">{{ t('បញ្ចប់គ្រប់លក្ខខណ្ឌមេរៀន', 'Complete all requirements') }}</p>
                  <p class="text-[10px] text-slate-600 dark:text-slate-400">{{ t('សិក្សាមេរៀន ធ្វើតេស្ត និងកិច្ចការស្រាវជ្រាវ។', 'Finish lessons, quizzes, and assignments.') }}</p>
                </div>
              </div>

              <!-- Step 3 -->
              <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-emerald-500/10 dark:bg-emerald-600/30 border border-emerald-500/30 dark:border-emerald-500/50 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-black font-mono shrink-0">
                  3
                </div>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">{{ t('ទទួលបានពិន្ទុជាប់', 'Achieve passing score') }}</p>
                  <p class="text-[10px] text-slate-600 dark:text-slate-400">{{ t('បំពេញពិន្ទុអប្បបរមាតាមការកំណត់។', 'Meet the minimum passing score requirement.') }}</p>
                </div>
              </div>

              <!-- Step 4 -->
              <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-amber-500/10 dark:bg-amber-600/30 border border-amber-500/30 dark:border-amber-500/50 text-amber-700 dark:text-amber-300 flex items-center justify-center text-xs font-black font-mono shrink-0">
                  4
                </div>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">{{ t('ទាញយកវិញ្ញាបនបត្រផ្លូវការ', 'Earn your certificate') }}</p>
                  <p class="text-[10px] text-slate-600 dark:text-slate-400">{{ t('ទាញយក PDF និងចែករំលែកតំណផ្ទៀងផ្ទាត់!', 'Download and share your verified credential!') }}</p>
                </div>
              </div>

            </div>

            <!-- Action Button -->
            <Link
              href="/student/progress/overview"
              class="w-full py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95"
            >
              <span>📈</span>
              <span>{{ t('មើលវឌ្ឍនភាពរបស់ខ្ញុំ', 'View My Progress') }}</span>
            </Link>
          </div>

        </div>

      </div>

      <!-- ================= 4. CAREER BOOSTER BANNER ================= -->
      <div class="bg-gradient-to-r from-blue-50/80 via-white to-purple-50/80 dark:from-blue-950/70 dark:via-slate-900/90 dark:to-purple-950/70 border border-blue-100 dark:border-blue-900/50 rounded-3xl p-4 sm:p-5 shadow-sm dark:shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-blue-500/10 dark:bg-blue-600/20 border border-blue-500/20 dark:border-blue-500/30 text-blue-600 dark:text-blue-300 flex items-center justify-center text-lg shrink-0 shadow-inner">
            🛡️
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ t('ពង្រឹងអាជីពរបស់អ្នកជាមួយវិញ្ញាបនបត្រផ្លូវការ', 'Boost Your Career with Certificates') }}</h4>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">{{ t('វិញ្ញាបនបត្របញ្ជាក់ពីសមត្ថភាព និងជំនាញពិតប្រាកដរបស់អ្នក។', 'Certificates prove your skills and help you advance your academic and professional career.') }}</p>
          </div>
        </div>

        <Link
          href="/student/browse"
          class="px-5 py-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-blue-500/50 text-slate-900 dark:text-white font-bold text-xs shadow-xs whitespace-nowrap transition-colors"
        >
          {{ t('ស្វែងរកវគ្គសិក្សា', 'Browse Courses') }}
        </Link>
      </div>

    </div>

    <!-- ================= MODAL: COURSE REQUIREMENTS & PROGRESS ================= -->
    <div
      v-if="isRequirementsModalOpen && selectedCertForModal"
      class="fixed inset-0 z-50 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-2xl">🎓</span>
            <div>
              <h3 class="text-base font-black text-slate-900 dark:text-white">{{ selectedCertForModal.title }}</h3>
              <p class="text-[11px] text-purple-600 dark:text-purple-300">{{ t('លក្ខខណ្ឌទទួលបានវិញ្ញាបនបត្រ', 'Certificate Eligibility Requirements') }}</p>
            </div>
          </div>
          <button
            @click="isRequirementsModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Overall Progress Ring -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ t('វឌ្ឍនភាពវគ្គសិក្សាសរុប', 'Overall Course Progress') }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ selectedCertForModal.progress }}%</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ t('សម្រេចឱ្យបាន 100% ដើម្បីទទួលបានវិញ្ញាបនបត្រផ្លូវការ', 'Reach 100% to unlock official certificate') }}</p>
          </div>

          <div class="w-12 h-12 rounded-2xl bg-purple-500/10 dark:bg-purple-600/20 border border-purple-500/20 dark:border-purple-500/30 text-purple-700 dark:text-purple-300 flex items-center justify-center text-lg font-mono font-bold shrink-0">
            {{ selectedCertForModal.progress }}%
          </div>
        </div>

        <!-- Requirements Checklist -->
        <div class="space-y-2 text-xs">
          <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider text-[11px]">{{ t('បញ្ជីលក្ខខណ្ឌ៖', 'Requirements Checklist:') }}</h4>

          <div
            v-for="(req, idx) in selectedCertForModal.requirements"
            :key="idx"
            class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between"
          >
            <div class="flex items-center gap-2.5">
              <span
                :class="[
                  req.done ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/40' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700',
                  'w-5 h-5 rounded-md border flex items-center justify-center text-[10px] font-bold shrink-0'
                ]"
              >
                {{ req.done ? '✓' : '•' }}
              </span>
              <span class="text-slate-700 dark:text-slate-300 font-medium">{{ req.label }}</span>
            </div>

            <span class="font-mono text-[11px] font-bold" :class="req.done ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
              {{ req.value }}
            </span>
          </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
          <button
            @click="isRequirementsModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200 dark:hover:text-white cursor-pointer"
          >
            {{ t('បិទ', 'Close') }}
          </button>

          <Link
            :href="selectedCertForModal.course_url || '/student/browse'"
            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5 cursor-pointer"
          >
            <span>▶</span>
            <span>{{ selectedCertForModal.is_enrolled ? t('បន្តការសិក្សា', 'Continue Course') : t('មើលវគ្គសិក្សា', 'View Course') }}</span>
          </Link>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #0B0F19;
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #1E293B;
  border-radius: 9999px;
}
</style>
