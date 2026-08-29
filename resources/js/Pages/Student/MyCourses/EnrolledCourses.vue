<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { isCourseCachedOffline, saveCourseForOffline, getAllOfflineCourses } from '@/offline/sync'

interface EnrolledCourseItem {
  id: number
  slug: string
  title: string
  category: string
  status: 'in_progress' | 'not_started' | 'completed'
  statusLabel: string
  progress: number
  teacher: {
    name: string
    avatar: string
    role?: string
  }
  currentChapter: string
  totalChapters: number
  completedChapters: number
  currentLesson: string
  completedLessons: number
  totalLessons: number
  remainingTime: string
  lastAccessed: string
  lastAccessedTimeMs: number
  enrolledDate: string
  overviewHref: string
  learningHref: string
  certificateHref?: string
  themeColor: string
  accentGradient: string
  illustrationType: 'web' | 'database' | 'python' | 'uiux'
}

const props = defineProps<{
  enrollments?: any[]
}>()

const activeTab = ref<'all' | 'in_progress' | 'not_started' | 'completed'>('all')
const searchQuery = ref('')
const selectedCategory = ref('all')
const selectedSort = ref('newest')
const viewMode = ref<'grid' | 'list'>('grid')
const activeDropdownId = ref<number | null>(null)
const cachedCourseIds = ref<number[]>([])

// 4 Primary Enrolled Courses matching screenshot & prompt
const courses = ref<EnrolledCourseItem[]>([
  {
    id: 1,
    slug: 'web-dev',
    title: 'Web Development Fundamentals',
    category: 'Web Development',
    status: 'in_progress',
    statusLabel: 'In Progress',
    progress: 53,
    teacher: {
      name: 'Mr. Sophea',
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80',
      role: 'Senior Web Instructor'
    },
    currentChapter: 'Chapter 3',
    completedChapters: 3,
    totalChapters: 6,
    currentLesson: '3.2 JavaScript Functions',
    completedLessons: 12,
    totalLessons: 30,
    remainingTime: '18h 20m Left',
    lastAccessed: 'Today, 09:30 AM',
    lastAccessedTimeMs: Date.now() - 3600000,
    enrolledDate: '2026-08-01',
    overviewHref: '/student/courses/1/overview',
    learningHref: '/student/my-courses/current',
    themeColor: 'purple',
    accentGradient: 'from-purple-600 to-indigo-600',
    illustrationType: 'web'
  },
  {
    id: 2,
    slug: 'db-systems',
    title: 'Database Systems',
    category: 'Database Systems',
    status: 'in_progress',
    statusLabel: 'In Progress',
    progress: 35,
    teacher: {
      name: 'Mr. Long Dararith',
      avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
      role: 'Database Specialist'
    },
    currentChapter: 'Chapter 2',
    completedChapters: 2,
    totalChapters: 5,
    currentLesson: '2.1 Relational Schema & Keys',
    completedLessons: 8,
    totalLessons: 23,
    remainingTime: '12h 10m Left',
    lastAccessed: 'Yesterday, 04:15 PM',
    lastAccessedTimeMs: Date.now() - 86400000,
    enrolledDate: '2026-08-05',
    overviewHref: '/student/courses/2/overview',
    learningHref: '/student/my-courses/current',
    themeColor: 'indigo',
    accentGradient: 'from-indigo-600 to-blue-600',
    illustrationType: 'database'
  },
  {
    id: 3,
    slug: 'python',
    title: 'Python Programming',
    category: 'Programming',
    status: 'not_started',
    statusLabel: 'Not Started',
    progress: 0,
    teacher: {
      name: 'Mr. Eng Thida',
      avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=120&auto=format&fit=crop&q=80',
      role: 'Python & AI Engineer'
    },
    currentChapter: 'Chapter 0',
    completedChapters: 0,
    totalChapters: 7,
    currentLesson: '1.1 Python Setup & Basics',
    completedLessons: 0,
    totalLessons: 28,
    remainingTime: '20h 00m Total',
    lastAccessed: 'Not started yet',
    lastAccessedTimeMs: 0,
    enrolledDate: '2026-08-10',
    overviewHref: '/student/courses/3/overview',
    learningHref: '/student/courses/3/overview',
    themeColor: 'blue',
    accentGradient: 'from-blue-600 to-cyan-600',
    illustrationType: 'python'
  },
  {
    id: 4,
    slug: 'ui-ux',
    title: 'UI/UX Design Basics',
    category: 'UI/UX Design',
    status: 'completed',
    statusLabel: 'Completed',
    progress: 100,
    teacher: {
      name: 'Ms. Nhean Sreymom',
      avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120&auto=format&fit=crop&q=80',
      role: 'Lead UI/UX Designer'
    },
    currentChapter: 'Chapter 4',
    completedChapters: 4,
    totalChapters: 4,
    currentLesson: '4.4 Final Design Showcase',
    completedLessons: 20,
    totalLessons: 20,
    remainingTime: '15h 30m Total',
    lastAccessed: 'Completed on Aug 24',
    lastAccessedTimeMs: Date.now() - 432000000,
    enrolledDate: '2026-07-20',
    overviewHref: '/student/courses/4/overview',
    learningHref: '/student/courses/4/overview',
    certificateHref: '/student/certificates/my-certificates',
    themeColor: 'emerald',
    accentGradient: 'from-emerald-600 to-teal-600',
    illustrationType: 'uiux'
  }
])

const refreshCachedCourses = async () => {
  try {
    const all = await getAllOfflineCourses()
    cachedCourseIds.value = all.map(c => Number(c.id))
  } catch (e) {
    // ignore
  }
}

const handleSaveCourseOffline = async (course: EnrolledCourseItem) => {
  if (cachedCourseIds.value.includes(course.id)) return
  await saveCourseForOffline({
    id: course.id,
    title: course.title,
    teacher: course.teacher.name,
    progress: course.progress,
    thumbnail: ''
  })
  await refreshCachedCourses()
  activeDropdownId.value = null
}

const counts = computed(() => {
  const total = courses.value.length
  const inProgress = courses.value.filter(c => c.progress > 0 && c.progress < 100).length
  const notStarted = courses.value.filter(c => c.progress === 0).length
  const completed = courses.value.filter(c => c.progress === 100).length
  return { total, inProgress, notStarted, completed }
})

const categories = computed(() => {
  const cats = Array.from(new Set(courses.value.map(c => c.category)))
  return ['all', ...cats]
})

const filteredCourses = computed(() => {
  return courses.value
    .filter(course => {
      // Tab filter
      if (activeTab.value === 'in_progress') {
        if (course.progress === 0 || course.progress === 100) return false
      } else if (activeTab.value === 'not_started') {
        if (course.progress !== 0) return false
      } else if (activeTab.value === 'completed') {
        if (course.progress !== 100) return false
      }

      // Category filter
      if (selectedCategory.value !== 'all' && course.category !== selectedCategory.value) {
        return false
      }

      // Search Query
      if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim()
        const matchesTitle = course.title.toLowerCase().includes(q)
        const matchesTeacher = course.teacher.name.toLowerCase().includes(q)
        const matchesCategory = course.category.toLowerCase().includes(q)
        const matchesLesson = course.currentLesson.toLowerCase().includes(q)
        if (!matchesTitle && !matchesTeacher && !matchesCategory && !matchesLesson) {
          return false
        }
      }

      return true
    })
    .sort((a, b) => {
      if (selectedSort.value === 'newest') {
        return new Date(b.enrolledDate).getTime() - new Date(a.enrolledDate).getTime()
      }
      if (selectedSort.value === 'recent') {
        return b.lastAccessedTimeMs - a.lastAccessedTimeMs
      }
      if (selectedSort.value === 'name') {
        return a.title.localeCompare(b.title)
      }
      if (selectedSort.value === 'highest_progress') {
        return b.progress - a.progress
      }
      if (selectedSort.value === 'lowest_progress') {
        return a.progress - b.progress
      }
      return 0
    })
})

const toggleDropdown = (id: number, e: Event) => {
  e.stopPropagation()
  activeDropdownId.value = activeDropdownId.value === id ? null : id
}

const closeAllDropdowns = () => {
  activeDropdownId.value = null
}

const resetFilters = () => {
  activeTab.value = 'all'
  searchQuery.value = ''
  selectedCategory.value = 'all'
  selectedSort.value = 'newest'
}

const navigateToCourseOverview = (course: EnrolledCourseItem) => {
  router.visit(course.overviewHref)
}

onMounted(() => {
  refreshCachedCourses()
  window.addEventListener('click', closeAllDropdowns)
})
</script>

<template>
  <StudentLayout
    title="Enrolled Courses"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'My Courses', href: '/student/my-courses/enrolled' },
      { label: 'Enrolled Courses' }
    ]"
  >
    <div class="space-y-6 pb-16">
      
      <!-- 1. PAGE HEADER (Title, Subtitle & Search Bar) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
            Enrolled Courses
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            All courses you have successfully enrolled in.
          </p>
        </div>

        <!-- Global Search within Enrolled Courses -->
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
              class="w-full pl-10 pr-16 py-2 rounded-xl bg-slate-900/90 border border-slate-800 focus:border-purple-500 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-purple-500/50 shadow-inner transition-all"
            />
            <div class="absolute right-2.5 flex items-center gap-1">
              <button
                v-if="searchQuery"
                @click="searchQuery = ''"
                type="button"
                class="text-slate-400 hover:text-white p-0.5 text-xs"
              >
                ✕
              </button>
              <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono text-slate-400 bg-slate-800 border border-slate-700 rounded shadow-xs">Ctrl K</kbd>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. FILTER CONTROLS & TABS ROW -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
        
        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="activeTab = 'all'"
            type="button"
            :class="[
              activeTab === 'all'
                ? 'bg-purple-600 text-white font-bold shadow-lg shadow-purple-600/30'
                : 'bg-slate-900 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer'
            ]"
          >
            <span>All Courses</span>
            <span :class="[activeTab === 'all' ? 'bg-purple-700/80 text-white' : 'bg-slate-800 text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold']">
              {{ counts.total }}
            </span>
          </button>

          <button
            @click="activeTab = 'in_progress'"
            type="button"
            :class="[
              activeTab === 'in_progress'
                ? 'bg-purple-600 text-white font-bold shadow-lg shadow-purple-600/30'
                : 'bg-slate-900 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer'
            ]"
          >
            <span>In Progress</span>
            <span :class="[activeTab === 'in_progress' ? 'bg-purple-700/80 text-white' : 'bg-slate-800 text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold']">
              {{ counts.inProgress }}
            </span>
          </button>

          <button
            @click="activeTab = 'not_started'"
            type="button"
            :class="[
              activeTab === 'not_started'
                ? 'bg-purple-600 text-white font-bold shadow-lg shadow-purple-600/30'
                : 'bg-slate-900 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer'
            ]"
          >
            <span>Not Started</span>
            <span :class="[activeTab === 'not_started' ? 'bg-purple-700/80 text-white' : 'bg-slate-800 text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold']">
              {{ counts.notStarted }}
            </span>
          </button>

          <button
            @click="activeTab = 'completed'"
            type="button"
            :class="[
              activeTab === 'completed'
                ? 'bg-purple-600 text-white font-bold shadow-lg shadow-purple-600/30'
                : 'bg-slate-900 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-800',
              'px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer'
            ]"
          >
            <span>Completed</span>
            <span :class="[activeTab === 'completed' ? 'bg-purple-700/80 text-white' : 'bg-slate-800 text-slate-300', 'px-1.5 py-0.5 rounded-md text-[10px] font-bold']">
              {{ counts.completed }}
            </span>
          </button>
        </div>

        <!-- Right Side: Category, Sort Dropdown & View Mode Switcher -->
        <div class="flex flex-wrap items-center gap-2.5 self-start lg:self-auto">
          
          <!-- Category Filter -->
          <div class="relative">
            <select
              v-model="selectedCategory"
              class="appearance-none bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-300 text-xs rounded-xl pl-3 pr-8 py-1.5 focus:outline-none focus:ring-1 focus:ring-purple-500/50 cursor-pointer shadow-sm"
            >
              <option value="all">All Categories</option>
              <option value="Web Development">Web Development</option>
              <option value="Database Systems">Database Systems</option>
              <option value="Programming">Programming</option>
              <option value="UI/UX Design">UI/UX Design</option>
            </select>
            <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 text-xs">
              ▼
            </div>
          </div>

          <!-- Sort Filter -->
          <div class="relative">
            <select
              v-model="selectedSort"
              class="appearance-none bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-300 text-xs rounded-xl pl-3 pr-8 py-1.5 focus:outline-none focus:ring-1 focus:ring-purple-500/50 cursor-pointer shadow-sm"
            >
              <option value="newest">Sort by: Newest</option>
              <option value="recent">Recently Accessed</option>
              <option value="name">Course Name (A-Z)</option>
              <option value="highest_progress">Highest Progress</option>
              <option value="lowest_progress">Lowest Progress</option>
            </select>
            <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 text-xs">
              ▼
            </div>
          </div>

          <!-- Grid / List Switcher -->
          <div class="flex items-center rounded-xl bg-slate-900 border border-slate-800 p-0.5">
            <button
              @click="viewMode = 'grid'"
              type="button"
              :class="[
                viewMode === 'grid' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200',
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
                viewMode === 'list' ? 'bg-purple-600 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200',
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

      <!-- 3. COURSE CARDS CONTAINER (GRID OR LIST) -->
      <div v-if="filteredCourses.length > 0">
        
        <!-- GRID VIEW (2x2 matching exact screenshot) -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div
            v-for="course in filteredCourses"
            :key="course.id"
            @click="navigateToCourseOverview(course)"
            class="bg-[#0e1424] border border-slate-800 hover:border-purple-500/40 rounded-3xl overflow-hidden shadow-2xl transition-all duration-200 hover:-translate-y-1 group flex flex-col justify-between cursor-pointer"
          >
            <!-- TOP CARD HEADER / 3D ILLUSTRATION BOX -->
            <div class="relative w-full h-48 bg-slate-950 flex items-center justify-center overflow-hidden border-b border-slate-800/80">
              
              <!-- Ambient Glow & Radial Gradient -->
              <div class="absolute inset-0 bg-gradient-to-t from-[#0e1424] via-transparent to-transparent z-10"></div>
              <div
                class="absolute -inset-10 opacity-30 blur-2xl transition-all group-hover:opacity-50"
                :class="[
                  course.illustrationType === 'web' ? 'bg-purple-600' :
                  course.illustrationType === 'database' ? 'bg-indigo-600' :
                  course.illustrationType === 'python' ? 'bg-blue-600' : 'bg-emerald-600'
                ]"
              ></div>

              <!-- Status Badge (Top-Left) -->
              <div class="absolute top-4 left-4 z-20">
                <span
                  v-if="course.status === 'in_progress'"
                  class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-600/90 text-white shadow-md shadow-indigo-600/30 backdrop-blur-md"
                >
                  In Progress
                </span>
                <span
                  v-else-if="course.status === 'not_started'"
                  class="px-3 py-1 rounded-full text-xs font-bold bg-blue-600/90 text-white shadow-md shadow-blue-600/30 backdrop-blur-md"
                >
                  Not Started
                </span>
                <span
                  v-else-if="course.status === 'completed'"
                  class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-600/90 text-white shadow-md shadow-emerald-600/30 backdrop-blur-md"
                >
                  Completed
                </span>
              </div>

              <!-- 3-Dots Context Menu Button (Top-Right) -->
              <div class="absolute top-4 right-4 z-20">
                <button
                  @click.stop="toggleDropdown(course.id, $event)"
                  type="button"
                  class="p-1.5 rounded-xl bg-slate-900/80 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-700/50 backdrop-blur-md transition-colors cursor-pointer"
                  title="Course Options"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                  </svg>
                </button>

                <!-- Context Dropdown Menu -->
                <div
                  v-show="activeDropdownId === course.id"
                  @click.stop
                  class="absolute right-0 mt-2 w-52 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-1.5 z-30 space-y-1 text-xs"
                >
                  <Link
                    :href="course.overviewHref"
                    class="flex items-center gap-2 px-3 py-2 rounded-xl text-slate-300 hover:bg-purple-600/20 hover:text-purple-300 font-medium"
                  >
                    <span>ℹ Course Details</span>
                  </Link>
                  <Link
                    :href="course.learningHref"
                    class="flex items-center gap-2 px-3 py-2 rounded-xl text-slate-300 hover:bg-purple-600/20 hover:text-purple-300 font-medium"
                  >
                    <span>▶ Continue / Resume</span>
                  </Link>
                  <button
                    @click="handleSaveCourseOffline(course)"
                    type="button"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-slate-300 hover:bg-slate-800 font-medium text-left"
                  >
                    <span class="flex items-center gap-2">
                      <span>📥</span>
                      <span>{{ cachedCourseIds.includes(course.id) ? 'Saved Offline' : 'Save for Offline' }}</span>
                    </span>
                    <span v-if="cachedCourseIds.includes(course.id)" class="text-emerald-400 font-bold">✓</span>
                  </button>
                </div>
              </div>

              <!-- 3D HIGH-TECH ILLUSTRATION FOR EACH COURSE (Matches screenshot) -->
              
              <!-- 1. Web Development (Laptop with HTML, CSS, JS cubes) -->
              <div v-if="course.illustrationType === 'web'" class="relative flex items-center justify-center scale-95 group-hover:scale-105 transition-transform duration-300">
                <!-- Laptop Screen -->
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
                  <!-- Glowing Floating Badges (HTML / CSS / JS) -->
                  <div class="absolute -right-2 top-3 flex flex-col gap-1.5 z-20">
                    <span class="px-2 py-0.5 bg-orange-600 text-white font-black text-[10px] rounded shadow-lg ring-1 ring-orange-400 transform rotate-6 animate-pulse">HTML</span>
                    <span class="px-2 py-0.5 bg-blue-600 text-white font-black text-[10px] rounded shadow-lg ring-1 ring-blue-400 transform -rotate-3">CSS</span>
                    <span class="px-2 py-0.5 bg-amber-500 text-slate-950 font-black text-[10px] rounded shadow-lg ring-1 ring-amber-300 transform rotate-12">JS</span>
                  </div>
                </div>
                <!-- Laptop Base -->
                <div class="absolute -bottom-2 w-52 h-2.5 bg-slate-800 rounded-b-xl border-t border-slate-700 shadow-md"></div>
              </div>

              <!-- 2. Database Systems (Glowing Stacked Database Cylinders) -->
              <div v-else-if="course.illustrationType === 'database'" class="relative flex items-center justify-center gap-4 scale-95 group-hover:scale-105 transition-transform duration-300">
                <!-- Stacked Cylinder 1 (Orange Glow) -->
                <div class="flex flex-col gap-1 items-center">
                  <div class="w-16 h-5 rounded-full bg-orange-500/80 border border-orange-400 shadow-lg shadow-orange-500/50 flex items-center justify-center">
                    <span class="w-2 h-1 bg-white/70 rounded-full"></span>
                  </div>
                  <div class="w-16 h-5 rounded-full bg-orange-600/90 border border-orange-500 shadow-md flex items-center justify-center"></div>
                  <div class="w-16 h-5 rounded-full bg-orange-700 border border-orange-600 shadow-sm flex items-center justify-center"></div>
                </div>
                <!-- Stacked Cylinder 2 (Purple Glow) -->
                <div class="flex flex-col gap-1 items-center">
                  <div class="w-16 h-5 rounded-full bg-purple-500/80 border border-purple-400 shadow-lg shadow-purple-500/50 flex items-center justify-center">
                    <span class="w-2 h-1 bg-white/70 rounded-full"></span>
                  </div>
                  <div class="w-16 h-5 rounded-full bg-purple-600/90 border border-purple-500 shadow-md flex items-center justify-center"></div>
                  <div class="w-16 h-5 rounded-full bg-purple-700 border border-purple-600 shadow-sm flex items-center justify-center"></div>
                </div>
                <!-- Stacked Cylinder 3 (Cyan Glow) -->
                <div class="flex flex-col gap-1 items-center">
                  <div class="w-16 h-5 rounded-full bg-cyan-500/80 border border-cyan-400 shadow-lg shadow-cyan-500/50 flex items-center justify-center">
                    <span class="w-2 h-1 bg-white/70 rounded-full"></span>
                  </div>
                  <div class="w-16 h-5 rounded-full bg-cyan-600/90 border border-cyan-500 shadow-md flex items-center justify-center"></div>
                  <div class="w-16 h-5 rounded-full bg-cyan-700 border border-cyan-600 shadow-sm flex items-center justify-center"></div>
                </div>
              </div>

              <!-- 3. Python Programming (Glowing 3D Python Logo) -->
              <div v-else-if="course.illustrationType === 'python'" class="relative flex items-center justify-center scale-100 group-hover:scale-110 transition-transform duration-300">
                <div class="relative w-24 h-24 flex items-center justify-center">
                  <!-- Python Blue Upper Curve -->
                  <div class="absolute top-1 left-2 w-14 h-12 bg-gradient-to-br from-blue-500 to-blue-700 rounded-t-2xl rounded-l-2xl shadow-lg shadow-blue-500/40 border border-blue-400/50 flex items-start justify-end p-2">
                    <span class="w-2 h-2 rounded-full bg-white shadow-xs"></span>
                  </div>
                  <!-- Python Yellow Lower Curve -->
                  <div class="absolute bottom-1 right-2 w-14 h-12 bg-gradient-to-br from-amber-400 to-amber-600 rounded-b-2xl rounded-r-2xl shadow-lg shadow-amber-500/40 border border-amber-300/50 flex items-end justify-start p-2">
                    <span class="w-2 h-2 rounded-full bg-slate-900 shadow-xs"></span>
                  </div>
                </div>
              </div>

              <!-- 4. UI/UX Design (Wireframe & Mobile Screens with UI/UX Badge) -->
              <div v-else-if="course.illustrationType === 'uiux'" class="relative flex items-center justify-center gap-3 scale-95 group-hover:scale-105 transition-transform duration-300">
                <!-- Mobile Phone 1 -->
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
                <!-- Card with UI/UX Logo -->
                <div class="w-28 h-28 rounded-2xl bg-gradient-to-br from-purple-900/60 to-slate-900 border border-purple-500/50 shadow-2xl flex flex-col items-center justify-center p-2">
                  <span class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-pink-400 to-cyan-400">UI/UX</span>
                  <span class="text-[9px] text-purple-300 font-mono mt-1">Design System</span>
                </div>
              </div>

            </div>

            <!-- CARD BODY CONTENT -->
            <div class="p-6 space-y-4 flex-1 flex flex-col justify-between">
              
              <div class="space-y-3">
                <!-- Title & Teacher Row -->
                <div>
                  <h3 class="text-base sm:text-lg font-bold text-white group-hover:text-purple-300 transition-colors leading-snug">
                    {{ course.title }}
                  </h3>

                  <div class="flex items-center gap-2 mt-2">
                    <img
                      :src="course.teacher.avatar"
                      :alt="course.teacher.name"
                      class="w-6 h-6 rounded-full object-cover border border-purple-500/40"
                    />
                    <span class="text-xs text-slate-300 font-medium">{{ course.teacher.name }}</span>
                  </div>
                </div>

                <!-- Progress Bar & Percentage -->
                <div class="space-y-1.5 pt-1">
                  <div class="flex items-center justify-between text-xs font-bold">
                    <span class="text-slate-400 text-[11px]">
                      {{ course.status === 'completed' ? 'Course Completed' : (course.status === 'not_started' ? 'Not Started' : 'Progress') }}
                    </span>
                    <span
                      :class="[
                        course.status === 'completed' ? 'text-emerald-400 font-black' :
                        course.status === 'not_started' ? 'text-slate-400' : 'text-purple-400 font-black'
                      ]"
                    >
                      {{ course.progress }}%
                    </span>
                  </div>

                  <div class="w-full h-2 rounded-full bg-slate-800/80 overflow-hidden">
                    <div
                      :class="[
                        course.status === 'completed'
                          ? 'bg-emerald-500'
                          : 'bg-gradient-to-r from-purple-600 via-indigo-500 to-purple-400',
                        'h-full rounded-full transition-all duration-500'
                      ]"
                      :style="{ width: course.progress + '%' }"
                    ></div>
                  </div>
                </div>

                <!-- Metadata Row (Chapter, Lessons, Time Remaining) -->
                <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400 pt-1 border-t border-slate-800/60 mt-2">
                  <div class="flex items-center gap-1.5">
                    <span>📖</span>
                    <span>{{ course.currentChapter }} of {{ course.totalChapters }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span>📑</span>
                    <span>{{ course.completedLessons }} / {{ course.totalLessons }} Lessons</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span>⏱</span>
                    <span>{{ course.remainingTime }}</span>
                  </div>
                </div>
              </div>

              <!-- ACTION BUTTONS ROW (Separating Resume vs Course Details) -->
              <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-800/80" @click.stop>
                
                <!-- Main Action Button (In Progress -> Resume, Not Started -> Start, Completed -> Review) -->
                <Link
                  v-if="course.status === 'in_progress'"
                  :href="course.learningHref"
                  class="py-2.5 px-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer"
                >
                  <span>▶</span>
                  <span>Continue Learning</span>
                </Link>

                <Link
                  v-else-if="course.status === 'not_started'"
                  :href="course.learningHref"
                  class="py-2.5 px-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer"
                >
                  <span>▶</span>
                  <span>Start Learning</span>
                </Link>

                <Link
                  v-else-if="course.status === 'completed'"
                  :href="course.overviewHref"
                  class="py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer"
                >
                  <span>👁</span>
                  <span>Review Course</span>
                </Link>

                <!-- Secondary Button (Course Details or Certificate) -->
                <Link
                  v-if="course.status === 'completed' && course.certificateHref"
                  :href="course.certificateHref"
                  class="py-2.5 px-3 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 font-bold text-xs border border-emerald-500/40 flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer"
                >
                  <span>🏆</span>
                  <span>Certificate</span>
                </Link>

                <Link
                  v-else
                  :href="course.overviewHref"
                  class="py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white font-semibold text-xs border border-slate-800 flex items-center justify-center gap-1.5 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer"
                >
                  <span>ⓘ</span>
                  <span>Course Details</span>
                </Link>

              </div>

            </div>
          </div>
        </div>

        <!-- LIST VIEW -->
        <div v-else class="space-y-4">
          <div
            v-for="course in filteredCourses"
            :key="course.id"
            @click="navigateToCourseOverview(course)"
            class="bg-[#0e1424] border border-slate-800 hover:border-purple-500/40 rounded-2xl p-4 sm:p-5 shadow-xl transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-4 cursor-pointer group"
          >
            <!-- Left Info -->
            <div class="flex items-center gap-4 min-w-0">
              <div class="w-16 h-16 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center shrink-0">
                <span class="text-2xl">{{ course.illustrationType === 'web' ? '💻' : (course.illustrationType === 'database' ? '🗄' : (course.illustrationType === 'python' ? '🐍' : '🎨')) }}</span>
              </div>

              <div class="min-w-0 space-y-1">
                <div class="flex items-center gap-2">
                  <span
                    :class="[
                      course.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' :
                      course.status === 'not_started' ? 'bg-blue-500/20 text-blue-400 border-blue-500/30' : 'bg-purple-500/20 text-purple-300 border-purple-500/30',
                      'px-2 py-0.5 rounded-full text-[10px] font-bold border'
                    ]"
                  >
                    {{ course.statusLabel }}
                  </span>
                  <span class="text-xs text-slate-400 font-medium">• {{ course.category }}</span>
                </div>

                <h3 class="text-sm sm:text-base font-bold text-white group-hover:text-purple-300 transition-colors truncate">
                  {{ course.title }}
                </h3>

                <div class="flex items-center gap-3 text-xs text-slate-400">
                  <span>Instructor: {{ course.teacher.name }}</span>
                  <span>•</span>
                  <span>{{ course.completedLessons }} / {{ course.totalLessons }} Lessons</span>
                </div>
              </div>
            </div>

            <!-- Right Progress & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 shrink-0" @click.stop>
              <div class="w-36 space-y-1">
                <div class="flex items-center justify-between text-xs font-bold">
                  <span class="text-slate-400 text-[10px]">Progress</span>
                  <span :class="course.status === 'completed' ? 'text-emerald-400' : 'text-purple-400'">{{ course.progress }}%</span>
                </div>
                <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                  <div
                    :class="course.status === 'completed' ? 'bg-emerald-500' : 'bg-purple-600'"
                    class="h-full rounded-full"
                    :style="{ width: course.progress + '%' }"
                  ></div>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <Link
                  v-if="course.status === 'in_progress'"
                  :href="course.learningHref"
                  class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/20"
                >
                  Continue
                </Link>
                <Link
                  v-else-if="course.status === 'not_started'"
                  :href="course.learningHref"
                  class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/20"
                >
                  Start
                </Link>
                <Link
                  v-else-if="course.status === 'completed'"
                  :href="course.overviewHref"
                  class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs"
                >
                  Review
                </Link>

                <Link
                  :href="course.overviewHref"
                  class="p-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800"
                  title="Course Details"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. PAGINATION FOOTER (Matches screenshot) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-6 border-t border-slate-800/80 text-xs text-slate-400">
          <div>
            Showing 1 to {{ filteredCourses.length }} of {{ counts.total }} courses
          </div>

          <div class="flex items-center gap-1.5 self-center sm:self-auto">
            <button
              type="button"
              disabled
              class="px-2.5 py-1.5 rounded-lg bg-slate-900/60 border border-slate-800/60 text-slate-600 cursor-not-allowed"
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
              class="px-2.5 py-1.5 rounded-lg bg-slate-900/60 border border-slate-800/60 text-slate-600 cursor-not-allowed"
            >
              ›
            </button>
          </div>
        </div>

      </div>

      <!-- EMPTY STATE -->
      <div
        v-else
        class="bg-slate-900/40 border border-dashed border-slate-800 rounded-3xl p-12 text-center space-y-4"
      >
        <div class="w-16 h-16 rounded-full bg-slate-800/60 flex items-center justify-center mx-auto text-2xl">
          🔍
        </div>
        <div class="space-y-1">
          <h3 class="text-base font-bold text-white">No courses match your criteria</h3>
          <p class="text-xs text-slate-400 max-w-sm mx-auto">
            Try adjusting your search query or filter criteria to find the enrolled course you are looking for.
          </p>
        </div>
        <button
          @click="resetFilters"
          type="button"
          class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 transition-all cursor-pointer"
        >
          Reset All Filters
        </button>
      </div>

    </div>
  </StudentLayout>
</template>
