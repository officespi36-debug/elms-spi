<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

const { t } = useLanguage()

interface CertificateItem {
  id: number
  course_id?: number | null
  title: string
  title_kh?: string | null
  student_name: string
  issuer: string
  teacher_name?: string | null
  issued_date: string
  raw_date: string
  status: string
  status_type: 'valid' | 'verified' | 'revoked' | 'expired'
  cert_number: string
  verification_code?: string
  download_url?: string
  verify_url?: string
  qr_url: string
  accent_color: string
  border_class: string
  badge_color: string
  seal_color: string
  category: string
  grade?: string
  score?: string
}

interface StatItem {
  label: string
  count: number
  percentage: number
  color: string
}

interface AchievementItem {
  id: number | string
  title: string
  description: string
  date: string
  icon: string
  icon_bg: string
}

interface CourseOption {
  id: number | string
  title: string
}

const props = defineProps<{
  analytics?: {
    summary: {
      total_certificates: number
      total_note: string
      completed_courses: number
      completed_note: string
      this_year: number
      this_year_note: string
      recent_date: string
      recent_note: string
      total_learning_time: string
      time_note: string
    }
    statistics: {
      total: number
      items: StatItem[]
    }
    recent_achievements: AchievementItem[]
    certificates: CertificateItem[]
    total_count: number
    filter_options?: {
      categories?: string[]
      courses?: CourseOption[]
      issuers?: string[]
    }
  }
  filters?: {
    status: string
    category: string
    course: string
    issuer: string
    search: string
    sort: string
  }
}>()

const defaultSummary = {
  total_certificates: 0,
  total_note: 'All time earned',
  completed_courses: 0,
  completed_note: 'Completed courses',
  this_year: 0,
  this_year_note: 'Certificates earned',
  recent_date: '—',
  recent_note: 'No certificates yet',
  total_learning_time: '0h 00m',
  time_note: 'Across all courses',
}

const defaultStats = {
  total: 0,
  items: [
    { label: 'Earned',      count: 0, percentage: 0, color: '#10B981' },
    { label: 'In Progress', count: 0, percentage: 0, color: '#3B82F6' },
    { label: 'Revoked',     count: 0, percentage: 0, color: '#EF4444' },
    { label: 'Not Started', count: 0, percentage: 0, color: '#F59E0B' },
  ],
}

const summary = computed(() => props.analytics?.summary || defaultSummary)
const statistics = computed(() => props.analytics?.statistics || defaultStats)
const recentAchievements = computed<AchievementItem[]>(() => props.analytics?.recent_achievements || [])
const certificates = computed<CertificateItem[]>(() => props.analytics?.certificates || [])

// Dynamic SVG Donut segments (circumference ≈ 100 for r = 15.9155)
const donutSegments = computed(() => {
  if (!statistics.value.total || statistics.value.total <= 0) return []
  let cumulative = 0
  return statistics.value.items
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
  return Array.from(new Set(certificates.value.map((c) => c.category).filter(Boolean)))
})

const courseOptions = computed<CourseOption[]>(() => {
  const fromProps = props.analytics?.filter_options?.courses
  if (Array.isArray(fromProps) && fromProps.length > 0) return fromProps
  const seen = new Set<string>()
  const list: CourseOption[] = []
  for (const c of certificates.value) {
    const key = String(c.course_id ?? c.title)
    if (!seen.has(key) && c.title) {
      seen.add(key)
      list.push({ id: c.course_id ?? c.title, title: c.title })
    }
  }
  return list
})

const issuerOptions = computed<string[]>(() => {
  const fromProps = props.analytics?.filter_options?.issuers
  if (Array.isArray(fromProps) && fromProps.length > 0) return fromProps
  return Array.from(new Set(certificates.value.map((c) => c.issuer).filter(Boolean)))
})

// Filter & View State
const activeStatusTab = ref<string>(props.filters?.status || 'all')
const searchQuery = ref<string>(props.filters?.search || '')
const selectedCategory = ref<string>(props.filters?.category || 'all')
const selectedCourse = ref<string>(props.filters?.course || 'all')
const selectedIssuer = ref<string>(props.filters?.issuer || 'all')
const selectedSort = ref<string>(props.filters?.sort || 'newest')
const isGridView = ref(true)

const hasActiveFilters = computed(() => {
  return (
    activeStatusTab.value !== 'all' ||
    selectedCategory.value !== 'all' ||
    selectedCourse.value !== 'all' ||
    selectedIssuer.value !== 'all' ||
    searchQuery.value.trim() !== ''
  )
})

// Modals State
const selectedCertForModal = ref<CertificateItem | null>(null)
const isPreviewModalOpen = ref(false)
const isShareModalOpen = ref(false)
const copiedSuccess = ref(false)

const getDownloadHref = (cert: CertificateItem) => {
  return cert.download_url || `/certificate/download/${cert.id}`
}

const getVerifyUrl = (cert: CertificateItem) => {
  return cert.verify_url || cert.qr_url
}

const openPreviewModal = (cert?: CertificateItem | null) => {
  if (!cert) return
  selectedCertForModal.value = cert
  isPreviewModalOpen.value = true
}

const openShareModal = (cert?: CertificateItem | null) => {
  if (!cert) return
  selectedCertForModal.value = cert
  isShareModalOpen.value = true
}

const copyVerificationLink = (cert?: CertificateItem | null) => {
  const target = cert || selectedCertForModal.value || certificates.value[0] || null
  if (!target) return
  const link = getVerifyUrl(target)
  if (!link) return
  navigator.clipboard.writeText(link)
  copiedSuccess.value = true
  setTimeout(() => {
    copiedSuccess.value = false
  }, 2500)
}

const handleFilterChange = (overrideTab?: string) => {
  if (overrideTab !== undefined) {
    activeStatusTab.value = overrideTab
  }
  router.get('/student/certificates/my-certificates', {
    status: activeStatusTab.value,
    category: selectedCategory.value,
    course: selectedCourse.value,
    issuer: selectedIssuer.value,
    search: searchQuery.value,
    sort: selectedSort.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const resetFilters = () => {
  activeStatusTab.value = 'all'
  selectedCategory.value = 'all'
  selectedCourse.value = 'all'
  selectedIssuer.value = 'all'
  searchQuery.value = ''
  selectedSort.value = 'newest'
  handleFilterChange('all')
}
</script>

<template>
  <StudentLayout title="My Certificates — Student Degrees & Achievements">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>{{ t('វិញ្ញាបនបត្ររបស់ខ្ញុំ', 'My Certificates') }}</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-600 dark:text-purple-400 text-lg">🛡️</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
            {{ t('ពិនិត្យ និងគ្រប់គ្រងវិញ្ញាបនបត្រផ្លូវការទាំងអស់ដែលអ្នកទទួលបាន។', 'View and manage all your earned official certificates.') }}
          </p>
        </div>
      </div>

      <!-- ================= 2. 5 TOP SUMMARY METRIC CARDS ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">

        <!-- Card 1: Total Certificates -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ t('វិញ្ញាបនបត្រសរុប', 'Total Certificates') }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.total_certificates }}</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">{{ summary.total_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-500/10 dark:bg-purple-600/20 border border-purple-500/20 dark:border-purple-500/30 text-purple-600 dark:text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🛡️
          </div>
        </div>

        <!-- Card 2: Completed Courses -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-blue-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ t('វគ្គសិក្សាបានបញ្ចប់', 'Completed Courses') }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.completed_courses }}</p>
            <p class="text-[10px] text-blue-600 dark:text-blue-400 font-medium font-mono">{{ summary.completed_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-blue-500/10 dark:bg-blue-600/20 border border-blue-500/20 dark:border-blue-500/30 text-blue-600 dark:text-blue-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🏆
          </div>
        </div>

        <!-- Card 3: This Year -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-emerald-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ t('ក្នុងឆ្នាំនេះ', 'This Year') }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.this_year }}</p>
            <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium font-mono">{{ summary.this_year_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 dark:bg-emerald-600/20 border border-emerald-500/20 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            🏅
          </div>
        </div>

        <!-- Card 4: Recent Certificate -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-amber-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ t('វិញ្ញាបនបត្រថ្មីៗ', 'Recent Certificate') }}</p>
            <p class="text-xl font-black text-slate-900 dark:text-white font-mono leading-tight">{{ summary.recent_date }}</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">{{ summary.recent_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-amber-500/10 dark:bg-amber-600/20 border border-amber-500/20 dark:border-amber-500/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📅
          </div>
        </div>

        <!-- Card 5: Total Learning Time -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ t('ម៉ោងសិក្សាសរុប', 'Total Learning Time') }}</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ summary.total_learning_time }}</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">{{ summary.time_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-500/10 dark:bg-purple-600/20 border border-purple-500/20 dark:border-purple-500/30 text-purple-600 dark:text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            ⏱️
          </div>
        </div>

      </div>

      <!-- ================= 3. MAIN SPLIT (LEFT: 8/12, RIGHT: 4/12) ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT (8/12): SEARCH, STATUS TABS & CERTIFICATE GALLERY ================= -->
        <div class="lg:col-span-8 space-y-6">

          <!-- SEARCH & FILTER BAR -->
          <div class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-[#0F172A]/80 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-3 shadow-sm dark:shadow-lg">

            <!-- Search Input -->
            <div class="relative flex-1 min-w-[200px]">
              <input
                type="text"
                v-model="searchQuery"
                @keyup.enter="handleFilterChange()"
                :placeholder="t('ស្វែងរកវិញ្ញាបនបត្រ...', 'Search certificates...')"
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
                v-model="selectedIssuer"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="all">{{ t('គ្រប់ស្ថាប័នចេញផ្សាយ', 'All Issuers') }}</option>
                <option v-for="iss in issuerOptions" :key="iss" :value="iss">{{ iss }}</option>
              </select>

              <select
                v-model="selectedSort"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="newest">{{ t('ថ្មីបំផុតមុន', 'Newest First') }}</option>
                <option value="oldest">{{ t('ចាស់បំផុតមុន', 'Oldest First') }}</option>
                <option value="score">{{ t('ពិន្ទុខ្ពស់បំផុត', 'Highest Score') }}</option>
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
                { key: 'all', label: t('វិញ្ញាបនបត្រទាំងអស់', 'All Certificates') },
                { key: 'valid', label: t('មានសុពលភាព', 'Valid') },
                { key: 'revoked', label: t('បានដកហូត', 'Revoked') }
              ]"
              :key="tab.key"
              @click="handleFilterChange(tab.key)"
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
              🎓
            </div>
            <div class="space-y-1.5 max-w-md mx-auto">
              <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                {{ hasActiveFilters
                  ? 'រកមិនឃើញវិញ្ញាបនបត្រតាមលក្ខខណ្ឌស្វែងរក · No Matching Certificates'
                  : 'មិនទាន់មានវិញ្ញាបនបត្រនៅឡើយទេ · No Certificates Earned Yet'
                }}
              </h3>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                {{ hasActiveFilters
                  ? t('សូមសាកល្បងផ្លាស់ប្តូរ ឬកំណត់ឡើងវិញនូវតម្រងស្វែងរករបស់អ្នក។', 'Try adjusting or resetting your search filters to view your certificates.')
                  : t('សូមចុះឈ្មោះ និងបញ្ចប់មេរៀនទាំងអស់ក្នុងវគ្គសិក្សា ដើម្បីទទួលបានវិញ្ញាបនបត្រផ្លូវការ។', 'Enroll in a course and complete all lessons and assessments to earn your official certificate.')
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

          <!-- CERTIFICATE GALLERY -->
          <div v-else :class="[isGridView ? 'grid grid-cols-1 sm:grid-cols-2 gap-4' : 'space-y-3']">
            <div
              v-for="cert in certificates"
              :key="cert.id"
              class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 hover:border-purple-500/40 rounded-3xl p-4 shadow-sm dark:shadow-xl flex flex-col justify-between group transition-all"
            >
              <!-- TOP: CERTIFICATE CANVAS MINI PREVIEW -->
              <div class="relative w-full aspect-[16/10] bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-inner flex flex-col items-center justify-between p-3 text-center group-hover:scale-[1.01] transition-transform">

                <div class="absolute inset-1.5 border border-amber-500/40 rounded-xl pointer-events-none"></div>
                <div class="absolute inset-2 border border-dashed border-amber-500/20 rounded-lg pointer-events-none"></div>

                <!-- Top Header -->
                <div class="relative z-10 pt-1">
                  <p class="text-[8px] tracking-[0.2em] uppercase font-serif text-amber-400 font-bold">CERTIFICATE OF COMPLETION</p>
                  <p class="text-[6.5px] text-slate-400">PROUDLY PRESENTED TO</p>
                </div>

                <!-- Student & Course Name -->
                <div class="relative z-10 space-y-0.5 my-auto">
                  <p class="text-sm font-black text-white font-serif tracking-wide drop-shadow-sm">{{ cert.student_name }}</p>
                  <div class="w-16 h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent mx-auto"></div>
                  <p class="text-[10px] font-bold text-amber-300 font-sans truncate max-w-[200px]">{{ cert.title }}</p>
                </div>

                <!-- Bottom Seal & Signatures -->
                <div class="relative z-10 w-full flex items-center justify-between px-2 pb-0.5 text-[6.5px] text-slate-400">
                  <div>
                    <p class="font-mono text-slate-300">{{ cert.raw_date }}</p>
                    <p class="border-t border-slate-700 pt-0.5">Issue Date</p>
                  </div>

                  <div class="w-6 h-6 rounded-full bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 border border-amber-300 flex items-center justify-center text-[7px] text-slate-950 font-black shadow-md font-serif">
                    ★
                  </div>

                  <div>
                    <p class="font-serif italic text-slate-300">Saint Paul Institute</p>
                    <p class="border-t border-slate-700 pt-0.5">Official Seal</p>
                  </div>
                </div>

              </div>

              <!-- CARD BODY INFO -->
              <div class="pt-3 pb-1 space-y-1">
                <div class="flex items-center justify-between gap-2">
                  <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate">
                    {{ cert.title }}
                  </h3>
                  <span
                    :class="[
                      cert.status_type === 'revoked'
                        ? 'bg-rose-500/10 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 border-rose-500/30'
                        : 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
                      'px-2 py-0.5 rounded-md border text-[10px] font-bold font-mono shrink-0'
                    ]"
                  >
                    {{ cert.status }}
                  </span>
                </div>

                <p class="text-[11px] text-slate-600 dark:text-slate-400">{{ cert.issuer }}</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ cert.cert_number }} · {{ cert.issued_date }}</p>
              </div>

              <!-- CARD ACTIONS -->
              <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                <button
                  @click="openPreviewModal(cert)"
                  class="py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 hover:border-purple-500/40 text-slate-700 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                >
                  <span>👁</span>
                  <span>{{ t('មើល', 'View') }}</span>
                </button>

                <a
                  v-if="cert.status_type !== 'revoked'"
                  :href="getDownloadHref(cert)"
                  class="py-1.5 rounded-xl bg-purple-50 dark:bg-purple-600/30 border border-purple-200 dark:border-purple-500/40 hover:bg-purple-600 hover:text-white text-purple-700 dark:text-purple-200 font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer shadow-xs"
                >
                  <span>⬇</span>
                  <span>{{ t('ទាញយក PDF', 'Download') }}</span>
                </a>
                <span
                  v-else
                  class="py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 text-slate-400 dark:text-slate-500 font-bold text-xs flex items-center justify-center gap-1.5 cursor-not-allowed"
                >
                  <span>🚫</span>
                  <span>{{ t('បានដកហូត', 'Revoked') }}</span>
                </span>
              </div>

            </div>
          </div>

        </div>

        <!-- ================= RIGHT (4/12): WIDGETS SIDEBAR ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- WIDGET 1: Certificate Stats Donut Chart -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ t('ស្ថិតិវិញ្ញាបនបត្រ', 'Certificate Stats') }}</h3>
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
                  <span class="text-base font-black text-slate-900 dark:text-white font-mono leading-none">{{ statistics.total }}</span>
                  <span class="text-[8px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">{{ t('សរុប', 'Total') }}</span>
                </div>
              </div>

              <!-- Legend Breakdown -->
              <div class="space-y-1.5 text-xs flex-1">
                <div
                  v-for="item in statistics.items"
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

            <button
              @click="handleFilterChange('all')"
              class="w-full py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-purple-500/40 text-slate-700 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer shadow-xs"
            >
              <span>📊</span>
              <span>{{ t('មើលវិញ្ញាបនបត្រទាំងអស់', 'View All Certificates') }}</span>
            </button>
          </div>

          <!-- WIDGET 2: Recent Achievements -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ t('សមិទ្ធផលថ្មីៗ', 'Recent Achievements') }}</h3>
              <Link href="/student/progress/achievements" class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline">
                {{ t('មើលទាំងអស់', 'View All') }}
              </Link>
            </div>

            <div v-if="recentAchievements.length === 0" class="py-6 text-center space-y-1.5">
              <p class="text-2xl">🏅</p>
              <p class="text-xs font-bold text-slate-700 dark:text-slate-300">
                មិនទាន់មានសមិទ្ធផលនៅឡើយទេ · No Achievements Yet
              </p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                {{ t('បញ្ចប់វគ្គសិក្សាដើម្បីទទួលបានសមិទ្ធផលដំបូងរបស់អ្នក។', 'Complete courses to unlock your first achievement.') }}
              </p>
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="ach in recentAchievements"
                :key="ach.id"
                class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/60 flex items-center justify-between gap-3 hover:border-purple-500/30 transition-all shadow-xs"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      ach.icon_bg,
                      'w-7 h-7 rounded-xl flex items-center justify-center text-xs shrink-0 shadow-sm'
                    ]"
                  >
                    {{ ach.icon }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ ach.title }}</p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ ach.description }}</p>
                  </div>
                </div>

                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono whitespace-nowrap shrink-0">
                  {{ ach.date }}
                </span>
              </div>
            </div>
          </div>

          <!-- WIDGET 3: Share Your Achievement -->
          <div class="bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/60 dark:from-[#10132B] dark:via-[#0F172A] dark:to-[#1E1138] border border-purple-200 dark:border-purple-900/50 rounded-3xl p-5 shadow-sm dark:shadow-2xl space-y-3.5 relative overflow-hidden">

            <div class="absolute -right-2 top-4 w-16 h-16 opacity-30 text-purple-400 text-5xl pointer-events-none">
              🏅
            </div>

            <div class="space-y-1">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">{{ t('ចែករំលែកសមិទ្ធផលរបស់អ្នក', 'Share Your Achievement') }}</h3>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                {{ t('ចែករំលែកតំណផ្ទៀងផ្ទាត់វិញ្ញាបនបត្រ ឬទាញយកឯកសារ PDF ផ្លូវការ។', 'Share your verified certificate links or download official PDF credentials.') }}
              </p>
            </div>

            <!-- Social Media Icon Buttons -->
            <div class="flex items-center gap-2 pt-1">
              <button
                @click="openShareModal(certificates[0])"
                :disabled="certificates.length === 0"
                class="w-8 h-8 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center justify-center shadow-md transition-transform hover:scale-105 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                title="Share on LinkedIn"
              >
                in
              </button>
              <button
                @click="openShareModal(certificates[0])"
                :disabled="certificates.length === 0"
                class="w-8 h-8 rounded-xl bg-sky-500 hover:bg-sky-400 text-white font-bold text-xs flex items-center justify-center shadow-md transition-transform hover:scale-105 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                title="Share on Twitter / X"
              >
                𝕏
              </button>
              <button
                @click="openShareModal(certificates[0])"
                :disabled="certificates.length === 0"
                class="w-8 h-8 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs flex items-center justify-center shadow-md transition-transform hover:scale-105 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                title="Share on Facebook"
              >
                f
              </button>
              <button
                @click="copyVerificationLink(certificates[0])"
                :disabled="certificates.length === 0"
                class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white font-bold text-xs flex items-center justify-center border border-slate-200 dark:border-slate-700 transition-transform hover:scale-105 cursor-pointer shadow-xs disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                title="Copy Verification Link"
              >
                🔗
              </button>
            </div>

            <!-- Action Button -->
            <button
              @click="openShareModal(certificates[0])"
              :disabled="certificates.length === 0"
              class="w-full py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <span>📢</span>
              <span>{{ certificates.length > 0 ? t('ចែករំលែកវិញ្ញាបនបត្រ', 'Share Certificates') : t('មិនទាន់មានវិញ្ញាបនបត្រសម្រាប់ចែករំលែក', 'No Certificates to Share Yet') }}</span>
            </button>
          </div>

        </div>

      </div>

      <!-- ================= 4. CERTIFICATE SECURITY BANNER ================= -->
      <div class="bg-gradient-to-r from-blue-50/80 via-white to-purple-50/80 dark:from-blue-950/70 dark:via-slate-900/90 dark:to-purple-950/70 border border-blue-100 dark:border-blue-900/50 rounded-3xl p-4 sm:p-5 shadow-sm dark:shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-blue-500/10 dark:bg-blue-600/20 border border-blue-500/20 dark:border-blue-500/30 text-blue-600 dark:text-blue-300 flex items-center justify-center text-lg shrink-0 shadow-inner">
            🛡️
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ t('វិញ្ញាបនបត្រទាំងអស់ត្រូវបានចុះបញ្ជី និងផ្ទៀងផ្ទាត់ជាផ្លូវការ', 'All certificates are officially registered and verifiable') }}</h4>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">{{ t('និយោជក និងស្ថាប័ននានាអាចផ្ទៀងផ្ទាត់សុពលភាពវិញ្ញាបនបត្រតាមរយៈលេខសម្គាល់ផ្លូវការ។', 'Employers and institutions can verify certificate authenticity anytime via the official registry.') }}</p>
          </div>
        </div>

        <Link
          href="/student/certificates/verify"
          class="px-5 py-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-blue-500/50 text-slate-900 dark:text-white font-bold text-xs shadow-sm whitespace-nowrap transition-colors"
        >
          {{ t('ផ្ទៀងផ្ទាត់វិញ្ញាបនបត្រ', 'Verify Certificate') }}
        </Link>
      </div>

    </div>

    <!-- ================= MODAL: FULL RESOLUTION CERTIFICATE PREVIEW ================= -->
    <div
      v-if="isPreviewModalOpen && selectedCertForModal"
      class="fixed inset-0 z-50 bg-slate-950/70 dark:bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-2xl w-full p-6 space-y-4 shadow-2xl relative">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-xl">🎓</span>
            <h3 class="text-base font-black text-slate-900 dark:text-white">{{ t('រូបភាពវិញ្ញាបនបត្រផ្លូវការ', 'Official Certificate Preview') }}</h3>
          </div>
          <button
            @click="isPreviewModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer transition-colors"
          >
            ✕
          </button>
        </div>

        <!-- HIGH-RES CERTIFICATE DOCUMENT CANVAS -->
        <div class="p-6 bg-slate-950 rounded-2xl border-4 border-amber-500/40 text-center space-y-3 relative shadow-2xl">
          <div class="border border-amber-400/30 p-4 rounded-xl space-y-3">
            <p class="text-xs uppercase tracking-[0.3em] font-serif text-amber-400 font-bold">CERTIFICATE OF COMPLETION</p>
            <p class="text-[10px] text-slate-400 uppercase">This is to certify that</p>
            <h2 class="text-2xl font-black text-white font-serif tracking-wide">{{ selectedCertForModal.student_name }}</h2>
            <p class="text-xs text-slate-300">has successfully completed all requirements for</p>
            <h3 class="text-lg font-bold text-amber-300 font-sans">{{ selectedCertForModal.title }}</h3>

            <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-800 text-xs">
              <div>
                <p class="text-[9px] text-slate-500">Grade / Score</p>
                <p class="font-bold text-emerald-400 font-mono">{{ selectedCertForModal.grade }} ({{ selectedCertForModal.score }})</p>
              </div>
              <div>
                <p class="text-[9px] text-slate-500">Certificate ID</p>
                <p class="font-bold text-slate-300 font-mono text-[10px]">{{ selectedCertForModal.cert_number }}</p>
              </div>
              <div>
                <p class="text-[9px] text-slate-500">Issue Date</p>
                <p class="font-bold text-slate-300 font-mono">{{ selectedCertForModal.raw_date }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- MODAL ACTIONS -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
          <Link
            :href="`/student/certificates/verify?code=${encodeURIComponent(selectedCertForModal.cert_number)}`"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-cyan-600 dark:text-cyan-300 font-bold border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 shadow-xs"
          >
            <span>🔍</span>
            <span>{{ t('ផ្ទៀងផ្ទាត់លើប្រព័ន្ធ', 'Verify on Registry') }}</span>
          </Link>

          <div class="flex items-center gap-2">
            <button
              @click="isPreviewModalOpen = false; openShareModal(selectedCertForModal)"
              class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200 dark:hover:text-white cursor-pointer"
            >
              {{ t('ចែករំលែកតំណ', 'Share Link') }}
            </button>
            <a
              v-if="selectedCertForModal.status_type !== 'revoked'"
              :href="getDownloadHref(selectedCertForModal)"
              class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5 cursor-pointer"
            >
              <span>📥</span>
              <span>{{ t('ទាញយក PDF', 'Download PDF') }}</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: SOCIAL SHARING ================= -->
    <div
      v-if="isShareModalOpen && selectedCertForModal"
      class="fixed inset-0 z-50 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-xl">📢</span>
            <h3 class="text-base font-black text-slate-900 dark:text-white">{{ t('ចែករំលែកវិញ្ញាបនបត្រ', 'Share Your Certificate') }}</h3>
          </div>
          <button
            @click="isShareModalOpen = false"
            class="text-slate-400 hover:text-slate-950 dark:hover:text-white cursor-pointer"
          >
            ✕
          </button>
        </div>

        <p class="text-xs text-slate-600 dark:text-slate-300">
          {{ t('ចែករំលែកជោគជ័យរបស់អ្នកក្នុងវគ្គសិក្សា', 'Share your accomplishment in') }}
          <strong class="text-purple-600 dark:text-purple-300">{{ selectedCertForModal.title }}</strong>.
        </p>

        <!-- Verification URL Copy Box -->
        <div class="space-y-1.5">
          <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Public Verification URL:</label>
          <div class="flex items-center gap-2">
            <input
              type="text"
              readonly
              :value="getVerifyUrl(selectedCertForModal)"
              class="flex-1 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-700 dark:text-slate-300 select-all"
            />
            <button
              @click="copyVerificationLink(selectedCertForModal)"
              class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md whitespace-nowrap cursor-pointer"
            >
              {{ copiedSuccess ? '✓ Copied!' : 'Copy' }}
            </button>
          </div>
        </div>

        <!-- Social Buttons Grid -->
        <div class="grid grid-cols-3 gap-2 pt-2 text-xs">
          <a
            :href="`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(getVerifyUrl(selectedCertForModal))}`"
            target="_blank"
            rel="noopener noreferrer"
            class="p-2.5 rounded-xl bg-blue-500/10 dark:bg-blue-600/20 border border-blue-500/20 dark:border-blue-500/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white font-bold flex flex-col items-center gap-1 transition-all cursor-pointer"
          >
            <span>💼</span>
            <span>LinkedIn</span>
          </a>
          <a
            :href="`https://twitter.com/intent/tweet?url=${encodeURIComponent(getVerifyUrl(selectedCertForModal))}&text=${encodeURIComponent('Earned my certificate in ' + selectedCertForModal.title)}`"
            target="_blank"
            rel="noopener noreferrer"
            class="p-2.5 rounded-xl bg-sky-500/10 dark:bg-sky-500/20 border border-sky-500/20 dark:border-sky-500/30 text-sky-700 dark:text-sky-300 hover:bg-sky-500 hover:text-white font-bold flex flex-col items-center gap-1 transition-all cursor-pointer"
          >
            <span>🐦</span>
            <span>Twitter / X</span>
          </a>
          <a
            :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(getVerifyUrl(selectedCertForModal))}`"
            target="_blank"
            rel="noopener noreferrer"
            class="p-2.5 rounded-xl bg-indigo-500/10 dark:bg-indigo-600/20 border border-indigo-500/20 dark:border-indigo-500/30 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-600 hover:text-white font-bold flex flex-col items-center gap-1 transition-all cursor-pointer"
          >
            <span>📘</span>
            <span>Facebook</span>
          </a>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
          <button
            @click="isShareModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200 cursor-pointer"
          >
            {{ t('បិទ', 'Done') }}
          </button>
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
