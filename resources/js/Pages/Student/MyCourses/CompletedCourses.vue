<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface CompletedCourseItem {
  id: number
  slug: string
  title: string
  category: string
  status: 'completed'
  progress: number
  teacher: {
    name: string
    avatar: string
    role?: string
  }
  completedDate: string
  completedDateTimestamp: number
  chaptersCount: number
  lessonsCount: number
  studyTime: string
  studyTimeMinutes: number
  quizScore: number
  hasCertificate: boolean
  certificateId?: string
  certIssueDate?: string
  missingCertReason?: string
  medalType: 'gold' | 'silver' | 'bronze'
  illustrationType: 'web' | 'database' | 'python' | 'uiux'
  overviewHref: string
}

const props = defineProps<{
  enrollments?: any[]
}>()

const activeTab = ref<'all' | 'certificates' | 'no_certificate'>('all')
const searchQuery = ref('')
const selectedSort = ref('newest')
const viewMode = ref<'grid' | 'list'>('grid')

// Modals
const isCertModalOpen = ref(false)
const isNoCertModalOpen = ref(false)
const selectedCertCourse = ref<CompletedCourseItem | null>(null)

// 4 Completed Courses matching screenshot & prompt
const courses = ref<CompletedCourseItem[]>([
  {
    id: 1,
    slug: 'web-dev',
    title: 'Web Development Fundamentals',
    category: 'Web Development',
    status: 'completed',
    progress: 100,
    teacher: {
      name: 'Mr. Sophea',
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80',
      role: 'Senior Web Instructor'
    },
    completedDate: 'May 25, 2025',
    completedDateTimestamp: new Date('2025-05-25').getTime(),
    chaptersCount: 5,
    lessonsCount: 30,
    studyTime: '18h 20m',
    studyTimeMinutes: 18 * 60 + 20,
    quizScore: 92,
    hasCertificate: true,
    certificateId: 'SPI-WD-2025-001',
    certIssueDate: 'May 25, 2025',
    medalType: 'gold',
    illustrationType: 'web',
    overviewHref: '/student/courses/1/overview'
  },
  {
    id: 2,
    slug: 'db-systems',
    title: 'Database Systems',
    category: 'Database Systems',
    status: 'completed',
    progress: 100,
    teacher: {
      name: 'Mr. Long Dararith',
      avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
      role: 'Database Specialist'
    },
    completedDate: 'May 15, 2025',
    completedDateTimestamp: new Date('2025-05-15').getTime(),
    chaptersCount: 4,
    lessonsCount: 23,
    studyTime: '12h 10m',
    studyTimeMinutes: 12 * 60 + 10,
    quizScore: 88,
    hasCertificate: true,
    certificateId: 'SPI-DB-2025-002',
    certIssueDate: 'May 15, 2025',
    medalType: 'gold',
    illustrationType: 'database',
    overviewHref: '/student/courses/2/overview'
  },
  {
    id: 3,
    slug: 'python',
    title: 'Python Programming',
    category: 'Programming',
    status: 'completed',
    progress: 100,
    teacher: {
      name: 'Mr. Eng Thida',
      avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=120&auto=format&fit=crop&q=80',
      role: 'Python & AI Engineer'
    },
    completedDate: 'April 28, 2025',
    completedDateTimestamp: new Date('2025-04-28').getTime(),
    chaptersCount: 6,
    lessonsCount: 28,
    studyTime: '20h 00m',
    studyTimeMinutes: 20 * 60,
    quizScore: 62,
    hasCertificate: false,
    missingCertReason: 'Final Assessment Passing Score is 70%. Your current score is 62%.',
    medalType: 'silver',
    illustrationType: 'python',
    overviewHref: '/student/courses/3/overview'
  },
  {
    id: 4,
    slug: 'ui-ux',
    title: 'UI/UX Design Basics',
    category: 'UI/UX Design',
    status: 'completed',
    progress: 100,
    teacher: {
      name: 'Ms. Nhean Sreymom',
      avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120&auto=format&fit=crop&q=80',
      role: 'Lead UI/UX Designer'
    },
    completedDate: 'April 10, 2025',
    completedDateTimestamp: new Date('2025-04-10').getTime(),
    chaptersCount: 4,
    lessonsCount: 20,
    studyTime: '15h 30m',
    studyTimeMinutes: 15 * 60 + 30,
    quizScore: 95,
    hasCertificate: true,
    certificateId: 'SPI-UX-2025-004',
    certIssueDate: 'April 10, 2025',
    medalType: 'bronze',
    illustrationType: 'uiux',
    overviewHref: '/student/courses/4/overview'
  }
])

// Computed Counts & Analytics
const totalCount = computed(() => courses.value.length)
const certEarnedCount = computed(() => courses.value.filter(c => c.hasCertificate).length)
const noCertCount = computed(() => courses.value.filter(c => !c.hasCertificate).length)

// Donut calculation percentages
const certPercent = computed(() => Math.round((certEarnedCount.value / totalCount.value) * 100))
const noCertPercent = computed(() => Math.round((noCertCount.value / totalCount.value) * 100))

// Total Study Time Sum Calculation (65h 40m)
const totalStudyTimeFormatted = computed(() => {
  const totalMinutes = courses.value.reduce((sum, c) => sum + c.studyTimeMinutes, 0)
  const hours = Math.floor(totalMinutes / 60)
  const mins = totalMinutes % 60
  return `${hours}h ${mins > 0 ? mins + 'm' : '00m'}`
})

// Average Quiz Score Calculation (86%)
const averageQuizScore = computed(() => {
  if (courses.value.length === 0) return 0
  const sum = courses.value.reduce((total, c) => total + c.quizScore, 0)
  return Math.round(sum / courses.value.length)
})

const quizScoreLabel = computed(() => {
  const score = averageQuizScore.value
  if (score >= 90) return 'Excellent Performance'
  if (score >= 80) return 'Very Good Performance'
  if (score >= 70) return 'Good Performance'
  if (score >= 60) return 'Needs Improvement'
  return 'Needs Attention'
})

// Latest Certificate
const latestCertCourse = computed(() => {
  const certCourses = courses.value.filter(c => c.hasCertificate)
  return certCourses.sort((a, b) => b.completedDateTimestamp - a.completedDateTimestamp)[0] || courses.value[0]
})

// Filtered & Sorted Courses
const filteredCourses = computed(() => {
  return courses.value
    .filter(course => {
      // Tab filter
      if (activeTab.value === 'certificates' && !course.hasCertificate) return false
      if (activeTab.value === 'no_certificate' && course.hasCertificate) return false

      // Search Query
      if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim()
        const matchesTitle = course.title.toLowerCase().includes(q)
        const matchesTeacher = course.teacher.name.toLowerCase().includes(q)
        const matchesCategory = course.category.toLowerCase().includes(q)
        if (!matchesTitle && !matchesTeacher && !matchesCategory) return false
      }

      return true
    })
    .sort((a, b) => {
      if (selectedSort.value === 'newest') return b.completedDateTimestamp - a.completedDateTimestamp
      if (selectedSort.value === 'oldest') return a.completedDateTimestamp - b.completedDateTimestamp
      if (selectedSort.value === 'name') return a.title.localeCompare(b.title)
      if (selectedSort.value === 'highest_score') return b.quizScore - a.quizScore
      if (selectedSort.value === 'most_time') return b.studyTimeMinutes - a.studyTimeMinutes
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

const navigateToReview = (course: CompletedCourseItem) => {
  router.visit(course.overviewHref)
}
</script>

<template>
  <StudentLayout
    title="Completed Courses"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'My Courses', href: '/student/my-courses/enrolled' },
      { label: 'Completed Courses' }
    ]"
  >
    <div class="space-y-6 pb-16">
      
      <!-- 1. PAGE HEADER (Title with Green Checkmark, Subtitle & Search Bar) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
              Completed Courses
            </h1>
            <span class="w-6 h-6 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 dark:border-emerald-500/40 flex items-center justify-center text-xs font-bold shadow-xs">
              ✓
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
            Courses you have successfully completed.
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
              v-model="searchQuery"
              type="text"
              placeholder="Search courses..."
              class="w-full pl-10 pr-16 py-2 rounded-xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 focus:border-purple-500 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-purple-500/50 shadow-xs dark:shadow-inner transition-all"
            />
            <div class="absolute right-2.5 flex items-center gap-1">
              <button
                v-if="searchQuery"
                @click="searchQuery = ''"
                type="button"
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
            @click="activeTab = 'all'"
            type="button"
            :class="[
              activeTab === 'all'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs'
            ]"
          >
            <span>All Completed</span>
            <span :class="[activeTab === 'all' ? 'bg-purple-700/80 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold']">
              {{ totalCount }}
            </span>
          </button>

          <button
            @click="activeTab = 'certificates'"
            type="button"
            :class="[
              activeTab === 'certificates'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs'
            ]"
          >
            <span>Certificates Earned</span>
            <span :class="[activeTab === 'certificates' ? 'bg-purple-700/80 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold']">
              {{ certEarnedCount }}
            </span>
          </button>

          <button
            @click="activeTab = 'no_certificate'"
            type="button"
            :class="[
              activeTab === 'no_certificate'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer shadow-xs'
            ]"
          >
            <span>No Certificate</span>
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
              class="appearance-none bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded-xl pl-3 pr-8 py-1.5 focus:outline-none focus:ring-1 focus:ring-purple-500/50 cursor-pointer shadow-xs"
            >
              <option value="newest">Sort by: Newest</option>
              <option value="oldest">Sort by: Oldest</option>
              <option value="name">Course Name (A-Z)</option>
              <option value="highest_score">Highest Quiz Score</option>
              <option value="most_time">Most Study Time</option>
            </select>
            <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 dark:text-slate-500 text-xs">
              ▼
            </div>
          </div>

          <div class="flex items-center rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-0.5 shadow-xs">
            <button
              @click="viewMode = 'grid'"
              type="button"
              :class="[
                viewMode === 'grid' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200',
                'p-1.5 rounded-lg text-xs transition-colors cursor-pointer'
              ]"
              title="Grid View"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
            </button>
            <button
              @click="viewMode = 'list'"
              type="button"
              :class="[
                viewMode === 'list' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200',
                'p-1.5 rounded-lg text-xs transition-colors cursor-pointer'
              ]"
              title="List View"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>

      </div>

      <!-- 3. MAIN SECTION: 2-COLUMN LAYOUT (Left Course Cards 8 cols, Right Analytics Widgets 4 cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8 COLUMNS: 2x2 Completed Course Cards Grid -->
        <div class="lg:col-span-8 space-y-6">
          
          <div v-if="filteredCourses.length > 0">
            <!-- Grid View (2x2) -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div
                v-for="course in filteredCourses"
                :key="course.id"
                @click="navigateToReview(course)"
                class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 hover:border-purple-400 dark:hover:border-purple-500/40 rounded-3xl overflow-hidden shadow-sm dark:shadow-2xl transition-all duration-200 hover:-translate-y-1 group flex flex-col justify-between cursor-pointer"
              >
                <!-- TOP CARD HEADER / 3D ILLUSTRATION BOX -->
                <div class="relative w-full h-44 bg-slate-900 dark:bg-slate-950 flex items-center justify-center overflow-hidden border-b border-slate-100 dark:border-slate-800/80">
                  
                  <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 dark:from-[#0e1424] via-transparent to-transparent z-10"></div>
                  <div
                    class="absolute -inset-10 opacity-25 blur-2xl transition-all group-hover:opacity-40"
                    :class="[
                      course.illustrationType === 'web' ? 'bg-purple-600' :
                      course.illustrationType === 'database' ? 'bg-indigo-600' :
                      course.illustrationType === 'python' ? 'bg-blue-600' : 'bg-emerald-600'
                    ]"
                  ></div>

                  <!-- Completed Status Badge (Top-Left) -->
                  <div class="absolute top-3.5 left-3.5 z-20">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-600 text-white shadow-md shadow-emerald-600/30 backdrop-blur-md flex items-center gap-1">
                      <span>Completed</span>
                    </span>
                  </div>

                  <!-- Achievement Medal / Trophy (Top-Right) -->
                  <div class="absolute top-3.5 right-3.5 z-20">
                    <div
                      :class="[
                        course.medalType === 'gold' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-amber-500/20' :
                        course.medalType === 'silver' ? 'bg-slate-500/20 text-slate-300 border-slate-400/40 shadow-slate-500/20' :
                        'bg-orange-500/20 text-orange-300 border-orange-500/40 shadow-orange-500/20',
                        'w-8 h-8 rounded-full border flex items-center justify-center text-sm shadow-md'
                      ]"
                      :title="course.hasCertificate ? 'Certificate Earned' : 'Assessment Pending'"
                    >
                      <span>{{ course.medalType === 'gold' ? '🏆' : (course.medalType === 'silver' ? '🥈' : '🏅') }}</span>
                    </div>
                  </div>

                  <!-- 3D Course Illustrations -->
                  <!-- Web Dev -->
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

                  <!-- Database -->
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

                  <!-- Python -->
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

                  <!-- UI/UX -->
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

                </div>

                <!-- CARD BODY -->
                <div class="p-5 space-y-3.5 flex-1 flex flex-col justify-between">
                  
                  <div class="space-y-2.5">
                    <!-- Title & Teacher & Date -->
                    <div>
                      <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors leading-snug">
                        {{ course.title }}
                      </h3>

                      <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400 mt-2">
                        <div class="flex items-center gap-2">
                          <img
                            :src="course.teacher.avatar"
                            :alt="course.teacher.name"
                            class="w-5 h-5 rounded-full object-cover border border-purple-500/40"
                          />
                          <span class="text-slate-700 dark:text-slate-300 font-medium">{{ course.teacher.name }}</span>
                        </div>

                        <div class="flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                          <span>📅</span>
                          <span>Completed on: {{ course.completedDate }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- 100% Solid Emerald Green Progress Bar -->
                    <div class="space-y-1 pt-1">
                      <div class="w-full h-1.5 rounded-full bg-emerald-500 shadow-xs shadow-emerald-500/30"></div>
                    </div>

                    <!-- Metadata Row (Chapters | Lessons | Time) -->
                    <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-100 dark:border-slate-800/60">
                      <div class="flex items-center gap-1">
                        <span>📖</span>
                        <span>{{ course.chaptersCount }} Chapters</span>
                      </div>
                      <span class="text-slate-300 dark:text-slate-700">|</span>
                      <div class="flex items-center gap-1">
                        <span>📑</span>
                        <span>{{ course.lessonsCount }} Lessons</span>
                      </div>
                      <span class="text-slate-300 dark:text-slate-700">|</span>
                      <div class="flex items-center gap-1">
                        <span>⏱</span>
                        <span>{{ course.studyTime }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- ACTIONS ROW: [ Review Course ] & [ Certificate ] -->
                  <div class="grid grid-cols-2 gap-2.5 pt-2.5 border-t border-slate-100 dark:border-slate-800/80" @click.stop>
                    
                    <Link
                      :href="course.overviewHref"
                      class="py-2 px-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/20 flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer"
                    >
                      <span>👁</span>
                      <span>Review Course</span>
                    </Link>

                    <button
                      @click="handleCertificateClick(course, $event)"
                      type="button"
                      :class="[
                        course.hasCertificate
                          ? 'bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 border-slate-200 dark:border-slate-700'
                          : 'bg-slate-50 dark:bg-slate-900/60 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-800',
                        'py-2 px-3 rounded-xl border text-xs font-semibold flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer shadow-xs'
                      ]"
                    >
                      <span>🏆</span>
                      <span>Certificate</span>
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
                  <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-xl shrink-0">
                    <span>{{ course.medalType === 'gold' ? '🏆' : (course.medalType === 'silver' ? '🥈' : '🏅') }}</span>
                  </div>
                  <div class="min-w-0 space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30">
                        Completed ✓
                      </span>
                      <span class="text-xs text-slate-500 dark:text-slate-400">{{ course.completedDate }}</span>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate">
                      {{ course.title }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ course.teacher.name }} • {{ course.lessonsCount }} Lessons • {{ course.studyTime }}</p>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0" @click.stop>
                  <Link
                    :href="course.overviewHref"
                    class="px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-xs"
                  >
                    Review
                  </Link>
                  <button
                    @click="handleCertificateClick(course, $event)"
                    type="button"
                    class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-semibold cursor-pointer shadow-xs"
                  >
                    Certificate
                  </button>
                </div>
              </div>
            </div>

            <!-- Pagination Footer -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-6 border-t border-slate-200 dark:border-slate-800/80 text-xs text-slate-500 dark:text-slate-400">
              <div>
                Showing 1 to {{ filteredCourses.length }} of {{ totalCount }} courses
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
            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800/60 flex items-center justify-center mx-auto text-2xl">
              🔍
            </div>
            <div class="space-y-1">
              <h3 class="text-base font-bold text-slate-900 dark:text-white">No completed courses match your criteria</h3>
              <p class="text-xs text-slate-600 dark:text-slate-400 max-w-sm mx-auto">
                Try switching between tabs or adjusting your search keywords.
              </p>
            </div>
            <button
              @click="activeTab = 'all'; searchQuery = ''"
              type="button"
              class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs cursor-pointer shadow-md shadow-purple-600/20"
            >
              Reset Filters
            </button>
          </div>

        </div>

        <!-- RIGHT 4 COLUMNS: 5 Analytics Widgets matching screenshot -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- WIDGET 1: Completion Overview (Donut Chart) -->
          <div class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              Completion Overview
            </h3>

            <div class="flex items-center gap-4">
              <!-- Donut SVG Ring -->
              <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                  <!-- Background Ring -->
                  <path
                    class="text-slate-100 dark:text-slate-800"
                    stroke-width="4"
                    stroke="currentColor"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                  <!-- Certificates Earned Arc (75% Emerald) -->
                  <path
                    class="text-emerald-500"
                    stroke-dasharray="75, 100"
                    stroke-width="4"
                    stroke-linecap="round"
                    stroke="currentColor"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                  <!-- No Certificate Arc (25% Amber) -->
                  <path
                    class="text-amber-500"
                    stroke-dashoffset="-75"
                    stroke-dasharray="25, 100"
                    stroke-width="4"
                    stroke-linecap="round"
                    stroke="currentColor"
                    fill="none"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-slate-900 dark:text-white leading-none">{{ totalCount }}</span>
                  <span class="text-[9px] text-slate-500 dark:text-slate-400 uppercase font-semibold mt-0.5">Total</span>
                </div>
              </div>

              <!-- Legend Details -->
              <div class="space-y-2 text-xs flex-1">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-slate-700 dark:text-slate-300 font-medium">Certificates Earned</span>
                  </div>
                  <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ certEarnedCount }} ({{ certPercent }}%)</span>
                </div>
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="text-slate-700 dark:text-slate-300 font-medium">No Certificate</span>
                  </div>
                  <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ noCertCount }} ({{ noCertPercent }}%)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- WIDGET 2: Total Study Time -->
          <Link
            href="/student/progress/learning-time"
            class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 hover:border-blue-400 dark:hover:border-blue-500/40 rounded-3xl p-5 shadow-sm dark:shadow-xl flex items-center gap-4 group transition-all cursor-pointer block"
          >
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 dark:bg-blue-500/20 border border-blue-500/30 dark:border-blue-500/40 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-transform">
              ⏱
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">Total Study Time</p>
              <h4 class="text-xl font-black text-slate-900 dark:text-white mt-0.5 tracking-tight group-hover:text-blue-600 dark:group-hover:text-blue-300 transition-colors">
                {{ totalStudyTimeFormatted }}
              </h4>
              <p class="text-[10px] text-slate-500 mt-0.5">Across all completed courses</p>
            </div>
            <span class="text-slate-400 dark:text-slate-600 group-hover:text-slate-700 dark:group-hover:text-slate-300 transition-colors text-sm">›</span>
          </Link>

          <!-- WIDGET 3: Average Quiz Score -->
          <Link
            href="/student/quizzes/scores"
            class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 hover:border-purple-400 dark:hover:border-purple-500/40 rounded-3xl p-5 shadow-sm dark:shadow-xl flex items-center gap-4 group transition-all cursor-pointer block"
          >
            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 dark:bg-purple-500/20 border border-purple-500/30 dark:border-purple-500/40 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl shrink-0 group-hover:scale-110 transition-transform">
              📊
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">Average Quiz Score</p>
              <h4 class="text-xl font-black text-slate-900 dark:text-white mt-0.5 tracking-tight group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors">
                {{ averageQuizScore }}%
              </h4>
              <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">{{ quizScoreLabel }}</p>
            </div>
            <span class="text-slate-400 dark:text-slate-600 group-hover:text-slate-700 dark:group-hover:text-slate-300 transition-colors text-sm">›</span>
          </Link>

          <!-- WIDGET 4: Latest Certificate -->
          <div class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              Latest Certificate
            </h3>

            <div class="flex items-center gap-3.5 p-3 rounded-2xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800">
              <!-- Mini Certificate Thumbnail Mock -->
              <div class="w-14 h-16 rounded-lg bg-amber-50 border-2 border-amber-300 p-1 flex flex-col justify-between text-center shadow-md shrink-0">
                <div class="text-[6px] font-bold text-amber-900">SAINT PAUL</div>
                <div class="w-5 h-5 rounded-full bg-amber-400/30 border border-amber-500 mx-auto flex items-center justify-center text-[8px] text-amber-800 font-bold">
                  ★
                </div>
                <div class="text-[5px] text-slate-600 font-mono">Verified</div>
              </div>

              <div class="min-w-0 flex-1 space-y-1">
                <h4 class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ latestCertCourse.title }}</h4>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Earned on: {{ latestCertCourse.certIssueDate }}</p>
                <button
                  @click="handleCertificateClick(latestCertCourse, $event)"
                  type="button"
                  class="mt-1 px-3 py-1 rounded-lg bg-purple-600 hover:bg-purple-500 text-white text-[11px] font-bold shadow-xs transition-colors cursor-pointer"
                >
                  View Certificate
                </button>
              </div>
            </div>
          </div>

          <!-- WIDGET 5: Achievements Badges -->
          <div class="bg-white dark:bg-[#0e1424] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                Achievements
              </h3>
              <Link
                href="/student/certificates/achievements"
                class="text-[11px] text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 font-semibold"
              >
                View All
              </Link>
            </div>

            <!-- 4 Badges Row -->
            <div class="grid grid-cols-4 gap-2 text-center">
              <!-- Badge 1 -->
              <div class="flex flex-col items-center gap-1.5 p-2 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 border border-emerald-500/30 dark:border-emerald-500/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-base shadow-xs">
                  📖
                </div>
                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 leading-tight">Quick Learner</span>
              </div>

              <!-- Badge 2 -->
              <div class="flex flex-col items-center gap-1.5 p-2 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 dark:bg-blue-500/20 border border-blue-500/30 dark:border-blue-500/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-base shadow-xs">
                  ⭐
                </div>
                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 leading-tight">Course Master</span>
              </div>

              <!-- Badge 3 -->
              <div class="flex flex-col items-center gap-1.5 p-2 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-orange-500/10 dark:bg-orange-500/20 border border-orange-500/30 dark:border-orange-500/50 text-orange-600 dark:text-orange-400 flex items-center justify-center text-base shadow-xs">
                  🏆
                </div>
                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 leading-tight">Top Performer</span>
              </div>

              <!-- Badge 4 -->
              <div class="flex flex-col items-center gap-1.5 p-2 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 dark:bg-purple-500/20 border border-purple-500/30 dark:border-purple-500/50 text-purple-600 dark:text-purple-400 flex items-center justify-center text-base shadow-xs">
                  ⚡
                </div>
                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 leading-tight">Consistent Learner</span>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- CERTIFICATE MODAL (When Eligible) -->
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
            <h4 class="text-lg sm:text-xl font-black text-slate-900">Sok Pisey</h4>
            <p class="text-[10px] text-slate-500">Student ID: STU2024001</p>
          </div>

          <p class="text-xs text-slate-700 max-w-md mx-auto leading-relaxed">
            has successfully fulfilled all academic requirements, hands-on drills, and final evaluations for
          </p>

          <h4 class="text-base sm:text-lg font-black text-indigo-900">
            {{ selectedCertCourse.title }}
          </h4>

          <div class="flex items-center justify-between pt-4 border-t border-amber-300/80 text-[10px] text-slate-600">
            <div class="text-left">
              <p class="font-bold text-slate-800">Instructor: {{ selectedCertCourse.teacher.name }}</p>
              <p>Issue Date: {{ selectedCertCourse.certIssueDate }}</p>
            </div>
            <div class="text-right font-mono">
              <p class="font-bold text-indigo-800">Verified ID: {{ selectedCertCourse.certificateId }}</p>
              <p class="text-emerald-700 font-semibold">Status: Officially Verified ✓</p>
            </div>
          </div>
        </div>

        <!-- Action CTA Buttons -->
        <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
          <Link
            :href="`/student/certificates/my-certificates`"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md"
          >
            Go to My Certificates
          </Link>
          <button
            @click="isCertModalOpen = false"
            type="button"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer"
          >
            Close
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
          class="absolute top-4 right-4 text-slate-400 hover:text-slate-900 dark:hover:text-white p-1 text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-amber-500/10 dark:bg-amber-500/20 border border-amber-500/30 dark:border-amber-500/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg">
            ⚠️
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Certificate Not Yet Available</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ selectedCertCourse.title }}</p>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 space-y-3 text-xs">
          <p class="font-bold text-slate-800 dark:text-slate-200">Completion Requirements Status:</p>
          <div class="space-y-2">
            <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
              <span>✓ All Lessons Completed ({{ selectedCertCourse.lessonsCount }}/{{ selectedCertCourse.lessonsCount }})</span>
              <span class="font-bold">Passed</span>
            </div>
            <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
              <span>✓ Course Hands-on Labs</span>
              <span class="font-bold">Passed</span>
            </div>
            <div class="flex items-center justify-between text-rose-600 dark:text-rose-400">
              <span>✗ Final Assessment (Passing: 70%)</span>
              <span class="font-bold font-mono">Score: {{ selectedCertCourse.quizScore }}%</span>
            </div>
          </div>
        </div>

        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
          {{ selectedCertCourse.missingCertReason }} You can retake the assessment at any time to qualify for the official SPI certificate.
        </p>

        <div class="flex items-center justify-end gap-3 pt-2">
          <Link
            href="/student/quizzes/post-test"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md flex items-center gap-1.5"
          >
            <span>🎯 Retake Assessment</span>
          </Link>
          <button
            @click="isNoCertModalOpen = false"
            type="button"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer"
          >
            Close
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>
