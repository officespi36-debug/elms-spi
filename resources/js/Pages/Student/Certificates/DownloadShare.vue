<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface RequirementItem {
  label: string
  value: string
  done: boolean
}

interface AvailableCertificate {
  id: number
  title: string
  issuer: string
  description: string
  progress: number
  level: string
  category: string
  status_type: 'in_progress' | 'almost_there' | 'not_started'
  badge_color: string
  bar_color: string
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
  count: number
  color: string
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

// Baseline default fallback data
const defaultSummary = {
  total_available: 15,
  total_note: 'Certificates available',
  in_progress: 6,
  in_progress_note: 'You are working on',
  almost_there: 4,
  almost_note: '80% or more completed',
  not_started: 5,
  not_started_note: 'Ready to begin',
  earned_this_year: 5,
  earned_note: 'Certificates earned',
}

const defaultProgressOverview = {
  total: 15,
  items: [
    { label: 'In Progress',  count: 6, percentage: 40, color: '#3B82F6' },
    { label: 'Almost There', count: 4, percentage: 27, color: '#10B981' },
    { label: 'Not Started',  count: 5, percentage: 33, color: '#F59E0B' },
  ]
}

const defaultPopularCategories: CategoryItem[] = [
  { id: 1, name: 'Programming',     count: 6, color: 'bg-purple-600/20 text-purple-300 border-purple-500/30' },
  { id: 2, name: 'Web Development', count: 4, color: 'bg-blue-600/20 text-blue-300 border-blue-500/30' },
  { id: 3, name: 'Data Science',    count: 2, color: 'bg-emerald-600/20 text-emerald-300 border-emerald-500/30' },
  { id: 4, name: 'Design',          count: 2, color: 'bg-amber-600/20 text-amber-300 border-amber-500/30' },
  { id: 5, name: 'Database',        count: 1, color: 'bg-rose-600/20 text-rose-300 border-rose-500/30' },
]

const defaultCertificates: AvailableCertificate[] = [
  {
    id: 1,
    title: 'JavaScript Advanced',
    issuer: 'SPI E-Learning Platform',
    description: 'Master advanced JavaScript concepts, ES6+, and modern development.',
    progress: 80,
    level: 'Intermediate',
    category: 'Programming',
    status_type: 'almost_there',
    badge_color: 'bg-purple-600 text-white',
    bar_color: 'bg-emerald-500',
    requirements: [
      { label: 'Lessons Completed', value: '10 / 12 (83%)', done: true },
      { label: 'Quizzes Passed', value: '2 / 2 (100%)', done: true },
      { label: 'Assignments Submitted', value: '1 / 1 (100%)', done: true },
    ]
  },
  {
    id: 2,
    title: 'React Development',
    issuer: 'SPI E-Learning Platform',
    description: 'Build modern web applications with React, Hooks, and Context API.',
    progress: 65,
    level: 'Intermediate',
    category: 'Web Development',
    status_type: 'in_progress',
    badge_color: 'bg-blue-600 text-white',
    bar_color: 'bg-blue-500',
    requirements: [
      { label: 'Lessons Completed', value: '8 / 12 (67%)', done: false },
      { label: 'Quizzes Passed', value: '1 / 2 (50%)', done: false },
      { label: 'Assignments Submitted', value: '0 / 1 (0%)', done: false },
    ]
  },
  {
    id: 3,
    title: 'Node.js Fundamentals',
    issuer: 'SPI E-Learning Platform',
    description: 'Learn backend development with Node.js, Express, and APIs.',
    progress: 90,
    level: 'Beginner',
    category: 'Web Development',
    status_type: 'almost_there',
    badge_color: 'bg-emerald-600 text-white',
    bar_color: 'bg-emerald-500',
    requirements: [
      { label: 'Lessons Completed', value: '9 / 10 (90%)', done: true },
      { label: 'Quizzes Passed', value: '2 / 2 (100%)', done: true },
      { label: 'Assignments Submitted', value: '1 / 1 (100%)', done: true },
    ]
  },
  {
    id: 4,
    title: 'Python Programming',
    issuer: 'SPI E-Learning Platform',
    description: 'Learn Python from basics to advanced programming concepts.',
    progress: 30,
    level: 'Beginner',
    category: 'Programming',
    status_type: 'in_progress',
    badge_color: 'bg-amber-600 text-white',
    bar_color: 'bg-amber-500',
    requirements: [
      { label: 'Lessons Completed', value: '3 / 10 (30%)', done: false },
      { label: 'Quizzes Passed', value: '0 / 2 (0%)', done: false },
      { label: 'Assignments Submitted', value: '0 / 1 (0%)', done: false },
    ]
  },
  {
    id: 5,
    title: 'Database Design',
    issuer: 'SPI E-Learning Platform',
    description: 'Design efficient databases and understand normalization.',
    progress: 75,
    level: 'Intermediate',
    category: 'Database',
    status_type: 'in_progress',
    badge_color: 'bg-rose-600 text-white',
    bar_color: 'bg-rose-500',
    requirements: [
      { label: 'Lessons Completed', value: '6 / 8 (75%)', done: true },
      { label: 'Quizzes Passed', value: '1 / 2 (50%)', done: false },
      { label: 'Assignments Submitted', value: '1 / 1 (100%)', done: true },
    ]
  },
  {
    id: 6,
    title: 'UI/UX Design Basics',
    issuer: 'SPI E-Learning Platform',
    description: 'Learn the fundamentals of UI/UX design and user research.',
    progress: 40,
    level: 'Beginner',
    category: 'Design',
    status_type: 'in_progress',
    badge_color: 'bg-cyan-600 text-white',
    bar_color: 'bg-cyan-500',
    requirements: [
      { label: 'Lessons Completed', value: '4 / 10 (40%)', done: false },
      { label: 'Quizzes Passed', value: '1 / 1 (100%)', done: true },
      { label: 'Assignments Submitted', value: '0 / 1 (0%)', done: false },
    ]
  },
  {
    id: 7,
    title: 'HTML & CSS Essentials',
    issuer: 'SPI E-Learning Platform',
    description: 'Build responsive websites using HTML5 and modern CSS.',
    progress: 10,
    level: 'Beginner',
    category: 'Web Development',
    status_type: 'in_progress',
    badge_color: 'bg-purple-600 text-white',
    bar_color: 'bg-purple-500',
    requirements: [
      { label: 'Lessons Completed', value: '1 / 10 (10%)', done: false },
      { label: 'Quizzes Passed', value: '0 / 2 (0%)', done: false },
      { label: 'Assignments Submitted', value: '0 / 1 (0%)', done: false },
    ]
  },
  {
    id: 8,
    title: 'Git & GitHub',
    issuer: 'SPI E-Learning Platform',
    description: 'Master version control and collaborative development.',
    progress: 0,
    level: 'Beginner',
    category: 'Programming',
    status_type: 'not_started',
    badge_color: 'bg-slate-700 text-white',
    bar_color: 'bg-slate-600',
    requirements: [
      { label: 'Lessons Completed', value: '0 / 6 (0%)', done: false },
      { label: 'Quizzes Passed', value: '0 / 1 (0%)', done: false },
      { label: 'Assignments Submitted', value: '0 / 1 (0%)', done: false },
    ]
  },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const progressOverview = computed(() => props.analytics?.progress_overview || defaultProgressOverview)
const popularCategories = computed(() => props.analytics?.popular_categories || defaultPopularCategories)
const certificates = computed(() => props.analytics?.certificates || defaultCertificates)

// Filter & View States
const activeStatusTab = ref<string>(props.filters?.status || 'all')
const searchQuery = ref<string>(props.filters?.search || '')
const selectedCategory = ref<string>(props.filters?.category || 'all')
const selectedCourse = ref<string>(props.filters?.course || 'all')
const selectedLevel = ref<string>(props.filters?.level || 'all')
const selectedSort = ref<string>(props.filters?.sort || 'progress')
const isGridView = ref(true)

// Modals State
const selectedCertForModal = ref<AvailableCertificate | null>(null)
const isRequirementsModalOpen = ref(false)

const openRequirementsModal = (cert: AvailableCertificate) => {
  selectedCertForModal.value = cert
  isRequirementsModalOpen.value = true
}

const handleFilterChange = (overrideTab?: string) => {
  if (overrideTab) {
    activeStatusTab.value = overrideTab
  }
  router.get('/student/certificates/download-share', {
    status: activeStatusTab.value,
    category: selectedCategory.value,
    course: selectedCourse.value,
    level: selectedLevel.value,
    sort: selectedSort.value,
    search: searchQuery.value,
    page: 1,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <StudentLayout title="Available Certificates — Quiz & Assessment">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>Available Certificates</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-600 dark:text-purple-400 text-lg">📄</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
            Explore all certificates you can earn. Complete courses and unlock your achievements.
          </p>
        </div>
      </div>

      <!-- ================= 2. 5 TOP SUMMARY METRIC CARDS ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        
        <!-- Card 1: Total Available -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Total Available</p>
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
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">In Progress</p>
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
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Almost There</p>
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
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Not Started</p>
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
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Earned This Year</p>
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
                placeholder="Search certificates..."
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
                <option value="all">All Categories</option>
                <option value="Programming">Programming</option>
                <option value="Web Development">Web Development</option>
                <option value="Database">Database</option>
                <option value="Design">Design</option>
              </select>

              <select
                v-model="selectedCourse"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="all">All Courses</option>
                <option value="JavaScript Advanced">JavaScript Advanced</option>
                <option value="React Development">React Development</option>
                <option value="Node.js Fundamentals">Node.js Fundamentals</option>
                <option value="Python Programming">Python Programming</option>
              </select>

              <select
                v-model="selectedLevel"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="all">All Levels</option>
                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
              </select>

              <select
                v-model="selectedSort"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="progress">Sort by Progress</option>
                <option value="title">Sort by Name</option>
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
                { key: 'all', label: 'All Certificates' },
                { key: 'in_progress', label: 'In Progress' },
                { key: 'almost_there', label: 'Almost There' },
                { key: 'not_started', label: 'Not Started' }
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

          <!-- CERTIFICATES CATALOG (4x2 Grid) -->
          <div :class="[isGridView ? 'grid grid-cols-1 sm:grid-cols-2 gap-4' : 'space-y-3']">
            <div
              v-for="cert in certificates"
              :key="cert.id"
              class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 hover:border-purple-500/40 rounded-3xl p-4 shadow-sm dark:shadow-xl flex flex-col justify-between group transition-all cursor-pointer relative"
              @click="openRequirementsModal(cert)"
            >
              <!-- TOP: MINI CERTIFICATE PREVIEW CANVAS + PROGRESS BADGE -->
              <div class="relative w-full aspect-[16/10] bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-inner flex flex-col items-center justify-between p-3 text-center group-hover:scale-[1.01] transition-transform">
                
                <!-- Ornamental Border -->
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
                  <p>SPI Platform</p>
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
                    <span class="text-slate-500 dark:text-slate-400 font-medium">Progress</span>
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
                  <span>Certificate</span>
                </span>
              </div>

            </div>
          </div>

          <!-- PAGINATION -->
          <div class="p-4 bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl flex flex-col sm:flex-row items-center justify-between gap-3 text-xs shadow-xs dark:shadow-md">
            <span class="text-slate-500 dark:text-slate-400 text-[11px]">
              Showing 1 to 8 of 15 certificates
            </span>

            <div class="flex items-center gap-1.5">
              <button class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs disabled:opacity-40 cursor-pointer">
                «
              </button>
              <button class="w-7 h-7 rounded-lg bg-purple-600 text-white font-bold text-xs shadow-xs">
                1
              </button>
              <button class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs cursor-pointer">
                2
              </button>
              <button class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs cursor-pointer">
                ›
              </button>
              <button class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs cursor-pointer">
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
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Certificate Progress Overview</h3>
            </div>

            <div class="flex items-center justify-between gap-4">
              <!-- Donut Chart -->
              <div class="relative w-24 h-24 flex items-center justify-center shrink-0">
                <svg class="w-24 h-24 -rotate-90 transform" viewBox="0 0 36 36">
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#E2E8F0" class="dark:stroke-slate-800" stroke-width="4.5" />
                  <!-- In Progress: 40% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#3B82F6" stroke-width="4.5" stroke-dasharray="40, 100" stroke-dashoffset="0" />
                  <!-- Almost There: 27% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#10B981" stroke-width="4.5" stroke-dasharray="27, 100" stroke-dashoffset="-40" />
                  <!-- Not Started: 33% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F59E0B" stroke-width="4.5" stroke-dasharray="33, 100" stroke-dashoffset="-67" />
                </svg>

                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-slate-900 dark:text-white font-mono leading-none">{{ progressOverview.total }}</span>
                  <span class="text-[8px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Total</span>
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
                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: item.color }"></span>
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
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Popular Certificate Categories</h3>
            </div>

            <div class="space-y-2">
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

                <div class="flex items-center gap-1.5">
                  <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono whitespace-nowrap">
                    {{ cat.count }} certificates
                  </span>
                  <span class="text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white transition-colors text-xs">›</span>
                </div>
              </div>
            </div>

            <button
              @click="selectedCategory = 'all'; handleFilterChange()"
              class="w-full text-center text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline pt-1 cursor-pointer"
            >
              View All Categories
            </button>
          </div>

          <!-- WIDGET 3: How to Earn Certificates (4-Step Flow) -->
          <div class="bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/60 dark:from-[#10132B] dark:via-[#0F172A] dark:to-[#1E1138] border border-purple-200 dark:border-purple-900/50 rounded-3xl p-5 shadow-sm dark:shadow-2xl space-y-4">
            <div class="border-b border-purple-200 dark:border-purple-900/40 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">How to Earn Certificates</h3>
            </div>

            <div class="space-y-3 text-xs">
              
              <!-- Step 1 -->
              <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-blue-500/10 dark:bg-blue-600/30 border border-blue-500/30 dark:border-blue-500/50 text-blue-700 dark:text-blue-300 flex items-center justify-center text-xs font-black font-mono shrink-0">
                  1
                </div>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">Enroll in a course</p>
                  <p class="text-[10px] text-slate-600 dark:text-slate-400">Choose a course with a certificate.</p>
                </div>
              </div>

              <!-- Step 2 -->
              <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-blue-500/10 dark:bg-blue-600/30 border border-blue-500/30 dark:border-blue-500/50 text-blue-700 dark:text-blue-300 flex items-center justify-center text-xs font-black font-mono shrink-0">
                  2
                </div>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">Complete all requirements</p>
                  <p class="text-[10px] text-slate-600 dark:text-slate-400">Finish lessons, quizzes, and assignments.</p>
                </div>
              </div>

              <!-- Step 3 -->
              <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-emerald-500/10 dark:bg-emerald-600/30 border border-emerald-500/30 dark:border-emerald-500/50 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-black font-mono shrink-0">
                  3
                </div>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">Achieve passing score</p>
                  <p class="text-[10px] text-slate-600 dark:text-slate-400">Meet the minimum score requirement.</p>
                </div>
              </div>

              <!-- Step 4 -->
              <div class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-amber-500/10 dark:bg-amber-600/30 border border-amber-500/30 dark:border-amber-500/50 text-amber-700 dark:text-amber-300 flex items-center justify-center text-xs font-black font-mono shrink-0">
                  4
                </div>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">Earn your certificate</p>
                  <p class="text-[10px] text-slate-600 dark:text-slate-400">Download and share your achievement!</p>
                </div>
              </div>

            </div>

            <!-- Action Button -->
            <Link
              href="/student/progress/overview"
              class="w-full py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95"
            >
              <span>📈</span>
              <span>View My Progress</span>
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
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Boost Your Career with Certificates</h4>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Certificates prove your skills and help you advance your career. Start learning today!</p>
          </div>
        </div>

        <Link
          href="/student/browse"
          class="px-5 py-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-blue-500/50 text-slate-900 dark:text-white font-bold text-xs shadow-xs whitespace-nowrap transition-colors"
        >
          Browse Courses
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
              <p class="text-[11px] text-purple-600 dark:text-purple-300">Certificate Eligibility Requirements</p>
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
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Overall Course Progress</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ selectedCertForModal.progress }}%</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500">Reach 100% to unlock official certificate</p>
          </div>

          <div class="w-12 h-12 rounded-2xl bg-purple-500/10 dark:bg-purple-600/20 border border-purple-500/20 dark:border-purple-500/30 text-purple-700 dark:text-purple-300 flex items-center justify-center text-lg font-mono font-bold">
            {{ selectedCertForModal.progress }}%
          </div>
        </div>

        <!-- Requirements Checklist -->
        <div class="space-y-2 text-xs">
          <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-wider text-[11px]">Requirements Checklist:</h4>
          
          <div
            v-for="(req, idx) in selectedCertForModal.requirements"
            :key="idx"
            class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-between"
          >
            <div class="flex items-center gap-2.5">
              <span
                :class="[
                  req.done ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/40' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700',
                  'w-5 h-5 rounded-md border flex items-center justify-center text-[10px] font-bold'
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
            Close
          </button>

          <Link
            href="/student/my-courses/enrolled"
            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5 cursor-pointer"
          >
            <span>▶</span>
            <span>Continue Course</span>
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
