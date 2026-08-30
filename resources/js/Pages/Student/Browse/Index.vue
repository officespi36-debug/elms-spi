<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface CatalogCourse {
  id: number
  slug: string
  title: string
  category: string
  categoryKey: string
  badge: 'Bestseller' | 'New' | 'Popular' | 'Trending'
  badgeColor: string
  illustrationType: 'js' | 'python' | 'react' | 'uiux' | 'sql' | 'marketing' | 'mobile' | 'cloud'
  description: string
  instructor: {
    name: string
    avatar: string
    role?: string
  }
  rating: number
  reviewsCount: number
  reviewsCountDisplay: string
  level: 'Beginner' | 'Intermediate' | 'Advanced'
  lessonsCount: number
  duration: string
  price: number
  priceDisplay: string
  isFree: boolean
  language: 'English' | 'Khmer'
  skills: string[]
  isWishlisted: boolean
  enrollmentStatus: 'not_enrolled' | 'enrolled' | 'completed'
  overviewHref: string
}

const props = defineProps<{
  courses?: any
  filters?: any
}>()

// State
const searchQuery = ref('')
const selectedTopCategory = ref('all')
const selectedCategories = ref<string[]>([])
const selectedLevels = ref<string[]>([])
const selectedPrice = ref<'all' | 'free' | 'paid'>('all')
const selectedLanguage = ref<'all' | 'English' | 'Khmer'>('all')
const selectedSort = ref<'popular' | 'newest' | 'rating' | 'price_low' | 'price_high' | 'enrolled'>('popular')
const viewMode = ref<'grid' | 'list'>('grid')

// Wishlist store in localStorage
const wishlistedIds = ref<number[]>([])

// Modals
const selectedCourseForModal = ref<CatalogCourse | null>(null)
const isDetailsModalOpen = ref(false)
const isPaymentModalOpen = ref(false)
const isSuccessModalOpen = ref(false)
const enrolledSuccessCourse = ref<CatalogCourse | null>(null)

// Payment State
const selectedPaymentMethod = ref<'aba' | 'wing' | 'card'>('aba')
const isProcessingPayment = ref(false)
const paymentTimer = ref('14:59')

// Top Categories with Icons
const topCategories = [
  { key: 'all', name: 'All Categories', icon: '' },
  { key: 'web', name: 'Web Development', icon: '🏆' },
  { key: 'data', name: 'Data Science', icon: '🤖' },
  { key: 'design', name: 'Design', icon: '🎨' },
  { key: 'business', name: 'Business', icon: '📊' },
  { key: 'mobile', name: 'Mobile Development', icon: '📱' },
  { key: 'it', name: 'IT & Software', icon: '💻' }
]

// All Catalog Courses (Matching exact screenshot & rich extra catalog)
const catalogCourses = ref<CatalogCourse[]>([
  {
    id: 101,
    slug: 'js-fundamentals',
    title: 'JavaScript Fundamentals',
    category: 'Web Development',
    categoryKey: 'web',
    badge: 'Bestseller',
    badgeColor: 'bg-purple-600',
    illustrationType: 'js',
    description: 'Learn the core concepts of JavaScript step by step.',
    instructor: {
      name: 'Mr. Sophea',
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80',
      role: 'Senior Software Instructor'
    },
    rating: 4.8,
    reviewsCount: 1200,
    reviewsCountDisplay: '1.2k',
    level: 'Beginner',
    lessonsCount: 24,
    duration: '12h 30m',
    price: 0,
    priceDisplay: 'Free',
    isFree: true,
    language: 'English',
    skills: ['JavaScript ES6+', 'Variables & Scope', 'DOM Manipulation', 'Event Loop', 'Async/Await'],
    isWishlisted: false,
    enrollmentStatus: 'not_enrolled',
    overviewHref: '/student/courses/1/overview'
  },
  {
    id: 102,
    slug: 'python-mastery',
    title: 'Python Programming Mastery',
    category: 'IT & Software',
    categoryKey: 'it',
    badge: 'New',
    badgeColor: 'bg-emerald-600',
    illustrationType: 'python',
    description: 'Master Python programming from basics to advanced.',
    instructor: {
      name: 'Mr. Eng Thida',
      avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=120&auto=format&fit=crop&q=80',
      role: 'Python & AI Engineer'
    },
    rating: 4.9,
    reviewsCount: 856,
    reviewsCountDisplay: '856',
    level: 'Beginner',
    lessonsCount: 30,
    duration: '18h 45m',
    price: 29.99,
    priceDisplay: '$29.99',
    isFree: false,
    language: 'English',
    skills: ['Python 3', 'Object-Oriented Programming', 'Modules & Packages', 'File Handling', 'API Basics'],
    isWishlisted: false,
    enrollmentStatus: 'not_enrolled',
    overviewHref: '/student/courses/3/overview'
  },
  {
    id: 103,
    slug: 'react-guide',
    title: 'React - The Complete Guide',
    category: 'Web Development',
    categoryKey: 'web',
    badge: 'Popular',
    badgeColor: 'bg-purple-600',
    illustrationType: 'react',
    description: 'Build modern web applications using React.',
    instructor: {
      name: 'Ms. Nhean Sreymom',
      avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120&auto=format&fit=crop&q=80',
      role: 'Frontend Lead & React Specialist'
    },
    rating: 4.7,
    reviewsCount: 972,
    reviewsCountDisplay: '972',
    level: 'Intermediate',
    lessonsCount: 28,
    duration: '15h 20m',
    price: 39.99,
    priceDisplay: '$39.99',
    isFree: false,
    language: 'English',
    skills: ['React Hooks', 'State Management', 'React Router', 'Next.js', 'TailwindCSS'],
    isWishlisted: false,
    enrollmentStatus: 'not_enrolled',
    overviewHref: '/student/courses/1/overview'
  },
  {
    id: 104,
    slug: 'uiux-fundamentals',
    title: 'UI/UX Design Fundamentals',
    category: 'Design',
    categoryKey: 'design',
    badge: 'Trending',
    badgeColor: 'bg-amber-600',
    illustrationType: 'uiux',
    description: 'Learn UI/UX design principles and create stunning designs.',
    instructor: {
      name: 'Mr. Chan Dara',
      avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
      role: 'Product & UX Designer'
    },
    rating: 4.6,
    reviewsCount: 743,
    reviewsCountDisplay: '743',
    level: 'Beginner',
    lessonsCount: 22,
    duration: '10h 15m',
    price: 19.99,
    priceDisplay: '$19.99',
    isFree: false,
    language: 'Khmer',
    skills: ['Figma Prototyping', 'User Research', 'Design Systems', 'Wireframing', 'Color Theory'],
    isWishlisted: false,
    enrollmentStatus: 'not_enrolled',
    overviewHref: '/student/courses/4/overview'
  },
  {
    id: 105,
    slug: 'sql-data-analysis',
    title: 'SQL for Data Analysis',
    category: 'Data Science',
    categoryKey: 'data',
    badge: 'New',
    badgeColor: 'bg-emerald-600',
    illustrationType: 'sql',
    description: 'Learn SQL and analyze data like a pro.',
    instructor: {
      name: 'Mr. Long Dararith',
      avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
      role: 'Data Architect & DBA'
    },
    rating: 4.8,
    reviewsCount: 654,
    reviewsCountDisplay: '654',
    level: 'Intermediate',
    lessonsCount: 26,
    duration: '14h 10m',
    price: 0,
    priceDisplay: 'Free',
    isFree: true,
    language: 'English',
    skills: ['PostgreSQL', 'Aggregations', 'Subqueries & CTEs', 'Window Functions', 'Data Modeling'],
    isWishlisted: false,
    enrollmentStatus: 'not_enrolled',
    overviewHref: '/student/courses/2/overview'
  },
  {
    id: 106,
    slug: 'digital-marketing',
    title: 'Digital Marketing Strategy',
    category: 'Business',
    categoryKey: 'business',
    badge: 'Bestseller',
    badgeColor: 'bg-purple-600',
    illustrationType: 'marketing',
    description: 'Master digital marketing and grow your business.',
    instructor: {
      name: 'Ms. Sokun Pheakdey',
      avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80',
      role: 'Growth Marketing Strategist'
    },
    rating: 4.5,
    reviewsCount: 521,
    reviewsCountDisplay: '521',
    level: 'Beginner',
    lessonsCount: 18,
    duration: '8h 30m',
    price: 24.99,
    priceDisplay: '$24.99',
    isFree: false,
    language: 'Khmer',
    skills: ['Social Media Ads', 'SEO Optimization', 'Email Campaigns', 'Funnel Analytics', 'Copywriting'],
    isWishlisted: false,
    enrollmentStatus: 'not_enrolled',
    overviewHref: '/student/courses/1/overview'
  }
])

// Filter categories list with counts
const categoryFilters = [
  { name: 'Web Development', count: 24, key: 'web' },
  { name: 'Data Science', count: 18, key: 'data' },
  { name: 'Design', count: 16, key: 'design' },
  { name: 'Business', count: 14, key: 'business' },
  { name: 'Mobile Development', count: 12, key: 'mobile' },
  { name: 'Marketing', count: 8, key: 'marketing' },
  { name: 'IT & Software', count: 20, key: 'it' }
]

const levelFilters = [
  { name: 'Beginner', count: 42 },
  { name: 'Intermediate', count: 35 },
  { name: 'Advanced', count: 19 }
]

// Wishlist methods
const toggleWishlist = (course: CatalogCourse, e: Event) => {
  e.stopPropagation()
  const idx = wishlistedIds.value.indexOf(course.id)
  if (idx > -1) {
    wishlistedIds.value.splice(idx, 1)
    course.isWishlisted = false
  } else {
    wishlistedIds.value.push(course.id)
    course.isWishlisted = true
  }
  try {
    localStorage.setItem('spi_wishlist', JSON.stringify(wishlistedIds.value))
  } catch (err) {}
}

// Filtered and Sorted Courses
const filteredCourses = computed(() => {
  return catalogCourses.value
    .filter(course => {
      // Top category pill
      if (selectedTopCategory.value !== 'all' && course.categoryKey !== selectedTopCategory.value) {
        return false
      }

      // Sidebar categories multi-select
      if (selectedCategories.value.length > 0 && !selectedCategories.value.includes(course.category)) {
        return false
      }

      // Level filter
      if (selectedLevels.value.length > 0 && !selectedLevels.value.includes(course.level)) {
        return false
      }

      // Price filter
      if (selectedPrice.value === 'free' && !course.isFree) return false
      if (selectedPrice.value === 'paid' && course.isFree) return false

      // Language filter
      if (selectedLanguage.value !== 'all' && course.language !== selectedLanguage.value) return false

      // Search Query
      if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim()
        const matchTitle = course.title.toLowerCase().includes(q)
        const matchDesc = course.description.toLowerCase().includes(q)
        const matchTeacher = course.instructor.name.toLowerCase().includes(q)
        const matchCategory = course.category.toLowerCase().includes(q)
        const matchSkills = course.skills.some(s => s.toLowerCase().includes(q))
        if (!matchTitle && !matchDesc && !matchTeacher && !matchCategory && !matchSkills) {
          return false
        }
      }

      return true
    })
    .sort((a, b) => {
      if (selectedSort.value === 'popular') return b.reviewsCount - a.reviewsCount
      if (selectedSort.value === 'rating') return b.rating - a.rating
      if (selectedSort.value === 'price_low') return a.price - b.price
      if (selectedSort.value === 'price_high') return b.price - a.price
      if (selectedSort.value === 'newest') return b.id - a.id
      if (selectedSort.value === 'enrolled') return b.reviewsCount - a.reviewsCount
      return 0
    })
})

const clearAllFilters = () => {
  searchQuery.value = ''
  selectedTopCategory.value = 'all'
  selectedCategories.value = []
  selectedLevels.value = []
  selectedPrice.value = 'all'
  selectedLanguage.value = 'all'
  selectedSort.value = 'popular'
}

const openCourseModal = (course: CatalogCourse) => {
  selectedCourseForModal.value = course
  isDetailsModalOpen.value = true
}

// Enrollment Handling
const handleEnrollAction = (course: CatalogCourse) => {
  isDetailsModalOpen.value = false
  if (course.isFree) {
    // Instant free enrollment
    course.enrollmentStatus = 'enrolled'
    enrolledSuccessCourse.value = course
    isSuccessModalOpen.value = true
  } else {
    // Open payment modal
    selectedCourseForModal.value = course
    isPaymentModalOpen.value = true
  }
}

const confirmPayment = () => {
  isProcessingPayment.value = true
  setTimeout(() => {
    isProcessingPayment.value = false
    isPaymentModalOpen.value = false
    if (selectedCourseForModal.value) {
      selectedCourseForModal.value.enrollmentStatus = 'enrolled'
      enrolledSuccessCourse.value = selectedCourseForModal.value
      isSuccessModalOpen.value = true
    }
  }, 1200)
}

onMounted(() => {
  try {
    const saved = localStorage.getItem('spi_wishlist')
    if (saved) {
      wishlistedIds.value = JSON.parse(saved)
      catalogCourses.value.forEach(c => {
        if (wishlistedIds.value.includes(c.id)) c.isWishlisted = true
      })
    }
  } catch (e) {}
})
</script>

<template>
  <StudentLayout
    title="Browse Catalog"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Browse Catalog' }
    ]"
  >
    <div class="space-y-6 pb-16">
      
      <!-- 1. PAGE HEADER (Title with Open Book Icon, Subtitle & Global Search Input) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
              Browse Catalog
            </h1>
            <span class="text-xl sm:text-2xl">📖</span>
          </div>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Explore our wide range of courses and start your learning journey.
          </p>
        </div>

        <!-- Global Search Input with Ctrl K -->
        <div class="relative w-full md:w-96">
          <div class="relative flex items-center">
            <span class="absolute left-3.5 text-slate-400 pointer-events-none">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search courses, skills, topics..."
              class="w-full pl-10 pr-16 py-2 rounded-xl bg-slate-900/90 border border-slate-800 focus:border-purple-500 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-purple-500/50 shadow-inner transition-all"
            />
            <div class="absolute right-2.5 flex items-center gap-1">
              <button
                v-if="searchQuery"
                @click="searchQuery = ''"
                type="button"
                class="text-slate-400 hover:text-white p-0.5 text-xs cursor-pointer"
              >
                ✕
              </button>
              <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono text-slate-400 bg-slate-800 border border-slate-700 rounded shadow-xs">Ctrl K</kbd>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. TOP CATEGORIES HORIZONTAL PILLS -->
      <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-2 pt-1">
        <button
          v-for="cat in topCategories"
          :key="cat.key"
          @click="selectedTopCategory = cat.key"
          type="button"
          :class="[
            selectedTopCategory === cat.key
              ? 'bg-purple-600 text-white font-bold shadow-lg shadow-purple-600/30'
              : 'bg-slate-900/90 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border border-slate-800',
            'px-4 py-2 rounded-xl text-xs flex items-center gap-2 shrink-0 transition-all cursor-pointer'
          ]"
        >
          <span v-if="cat.icon" class="text-sm">{{ cat.icon }}</span>
          <span>{{ cat.name }}</span>
        </button>
      </div>

      <!-- 3. MAIN 2-COLUMN LAYOUT (Left Filters Sidebar 3 cols, Right Course Cards Grid 9 cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 3 COLUMNS: FILTER COURSES SIDEBAR -->
        <aside class="lg:col-span-3 bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-6">
          
          <!-- Filter Header & Clear All -->
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <h2 class="text-xs font-bold text-white uppercase tracking-wider">
              Filter Courses
            </h2>
            <button
              @click="clearAllFilters"
              type="button"
              class="text-xs text-purple-400 hover:text-purple-300 font-semibold cursor-pointer"
            >
              Clear All
            </button>
          </div>

          <!-- Mini Search inside Filter -->
          <div class="space-y-1.5">
            <label class="text-[11px] font-bold text-slate-400 uppercase">Search</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs">🔍</span>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search courses..."
                class="w-full pl-8 pr-3 py-1.5 rounded-xl bg-slate-950/80 border border-slate-800 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-purple-500"
              />
            </div>
          </div>

          <!-- Categories Checkboxes -->
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <label class="text-[11px] font-bold text-slate-400 uppercase">Categories</label>
              <button
                @click="selectedCategories = []"
                class="text-[10px] text-purple-400 hover:underline cursor-pointer"
              >
                View All
              </button>
            </div>

            <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-1">
              <label
                v-for="cat in categoryFilters"
                :key="cat.name"
                class="flex items-center justify-between text-xs text-slate-300 hover:text-white cursor-pointer group"
              >
                <div class="flex items-center gap-2">
                  <input
                    type="checkbox"
                    :value="cat.name"
                    v-model="selectedCategories"
                    class="rounded border-slate-700 bg-slate-900 text-purple-600 focus:ring-purple-500/50 cursor-pointer"
                  />
                  <span>{{ cat.name }}</span>
                </div>
                <span class="text-[11px] text-slate-500 group-hover:text-slate-400 font-mono">{{ cat.count }}</span>
              </label>
            </div>
          </div>

          <!-- Level Filter -->
          <div class="space-y-2.5 border-t border-slate-800/80 pt-4">
            <label class="text-[11px] font-bold text-slate-400 uppercase">Level</label>
            <div class="space-y-2">
              <label
                v-for="lvl in levelFilters"
                :key="lvl.name"
                class="flex items-center justify-between text-xs text-slate-300 hover:text-white cursor-pointer group"
              >
                <div class="flex items-center gap-2">
                  <input
                    type="checkbox"
                    :value="lvl.name"
                    v-model="selectedLevels"
                    class="rounded border-slate-700 bg-slate-900 text-purple-600 focus:ring-purple-500/50 cursor-pointer"
                  />
                  <span>{{ lvl.name }}</span>
                </div>
                <span class="text-[11px] text-slate-500 group-hover:text-slate-400 font-mono">{{ lvl.count }}</span>
              </label>
            </div>
          </div>

          <!-- Price Filter -->
          <div class="space-y-2.5 border-t border-slate-800/80 pt-4">
            <label class="text-[11px] font-bold text-slate-400 uppercase">Price</label>
            <div class="space-y-2 text-xs text-slate-300">
              <label class="flex items-center justify-between cursor-pointer hover:text-white">
                <div class="flex items-center gap-2">
                  <input
                    type="radio"
                    value="all"
                    v-model="selectedPrice"
                    class="text-purple-600 bg-slate-900 border-slate-700 focus:ring-purple-500/50 cursor-pointer"
                  />
                  <span>All</span>
                </div>
              </label>
              <label class="flex items-center justify-between cursor-pointer hover:text-white">
                <div class="flex items-center gap-2">
                  <input
                    type="radio"
                    value="free"
                    v-model="selectedPrice"
                    class="text-purple-600 bg-slate-900 border-slate-700 focus:ring-purple-500/50 cursor-pointer"
                  />
                  <span>Free</span>
                </div>
                <span class="text-[11px] text-slate-500 font-mono">28</span>
              </label>
              <label class="flex items-center justify-between cursor-pointer hover:text-white">
                <div class="flex items-center gap-2">
                  <input
                    type="radio"
                    value="paid"
                    v-model="selectedPrice"
                    class="text-purple-600 bg-slate-900 border-slate-700 focus:ring-purple-500/50 cursor-pointer"
                  />
                  <span>Paid</span>
                </div>
                <span class="text-[11px] text-slate-500 font-mono">68</span>
              </label>
            </div>
          </div>

          <!-- Language Filter -->
          <div class="space-y-2.5 border-t border-slate-800/80 pt-4">
            <label class="text-[11px] font-bold text-slate-400 uppercase">Language</label>
            <div class="space-y-2 text-xs text-slate-300">
              <label class="flex items-center justify-between cursor-pointer hover:text-white">
                <div class="flex items-center gap-2">
                  <input
                    type="radio"
                    value="all"
                    v-model="selectedLanguage"
                    class="text-purple-600 bg-slate-900 border-slate-700 focus:ring-purple-500/50 cursor-pointer"
                  />
                  <span>All Languages</span>
                </div>
              </label>
              <label class="flex items-center justify-between cursor-pointer hover:text-white">
                <div class="flex items-center gap-2">
                  <input
                    type="radio"
                    value="English"
                    v-model="selectedLanguage"
                    class="text-purple-600 bg-slate-900 border-slate-700 focus:ring-purple-500/50 cursor-pointer"
                  />
                  <span>English</span>
                </div>
                <span class="text-[11px] text-slate-500 font-mono">85</span>
              </label>
              <label class="flex items-center justify-between cursor-pointer hover:text-white">
                <div class="flex items-center gap-2">
                  <input
                    type="radio"
                    value="Khmer"
                    v-model="selectedLanguage"
                    class="text-purple-600 bg-slate-900 border-slate-700 focus:ring-purple-500/50 cursor-pointer"
                  />
                  <span>Khmer</span>
                </div>
                <span class="text-[11px] text-slate-500 font-mono">11</span>
              </label>
            </div>
          </div>

        </aside>

        <!-- RIGHT 9 COLUMNS: COURSE CATALOG GRID & CONTROLS -->
        <main class="lg:col-span-9 space-y-6">
          
          <!-- Top Bar: Results Count, Sort Dropdown, Grid/List Switcher -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#0e1424] border border-slate-800 rounded-2xl px-5 py-3 shadow-md">
            <div class="text-xs text-slate-400">
              Showing <span class="text-white font-bold">1</span> to <span class="text-white font-bold">{{ filteredCourses.length }}</span> of <span class="text-white font-bold">96</span> courses
            </div>

            <div class="flex items-center gap-3 self-start sm:self-auto">
              <!-- Sort Dropdown -->
              <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 hidden md:inline">Sort by:</span>
                <div class="relative">
                  <select
                    v-model="selectedSort"
                    class="appearance-none bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-200 text-xs rounded-xl pl-3 pr-8 py-1.5 focus:outline-none focus:ring-1 focus:ring-purple-500/50 cursor-pointer shadow-sm font-medium"
                  >
                    <option value="popular">Most Popular</option>
                    <option value="newest">Newest</option>
                    <option value="rating">Highest Rated</option>
                    <option value="price_low">Lowest Price</option>
                    <option value="price_high">Highest Price</option>
                    <option value="enrolled">Most Enrolled</option>
                  </select>
                  <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-500 text-xs">
                    ▼
                  </div>
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

          <!-- Course Cards Container (3x2 Grid or List) -->
          <div v-if="filteredCourses.length > 0">
            
            <!-- 3x2 Grid View (Matching Screenshot) -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
              <div
                v-for="course in filteredCourses"
                :key="course.id"
                @click="openCourseModal(course)"
                class="bg-[#0e1424] border border-slate-800 hover:border-purple-500/40 rounded-3xl overflow-hidden shadow-2xl transition-all duration-200 hover:-translate-y-1 group flex flex-col justify-between cursor-pointer"
              >
                <!-- TOP CARD 3D ILLUSTRATION BOX -->
                <div class="relative w-full h-44 bg-slate-950 flex items-center justify-center overflow-hidden border-b border-slate-800/80">
                  <div class="absolute inset-0 bg-gradient-to-t from-[#0e1424] via-transparent to-transparent z-10"></div>
                  
                  <!-- Badge (Top-Left) -->
                  <div class="absolute top-3.5 left-3.5 z-20">
                    <span :class="[course.badgeColor, 'px-2.5 py-0.5 rounded-full text-[10px] font-bold text-white shadow-md']">
                      {{ course.badge }}
                    </span>
                  </div>

                  <!-- Wishlist Heart Button (Top-Right) -->
                  <button
                    @click="toggleWishlist(course, $event)"
                    type="button"
                    class="absolute top-3.5 right-3.5 z-20 w-8 h-8 rounded-full bg-slate-900/80 hover:bg-slate-800 text-slate-300 border border-slate-700/60 backdrop-blur-md flex items-center justify-center text-sm transition-transform active:scale-125 cursor-pointer"
                    :title="course.isWishlisted ? 'Remove from Wishlist' : 'Add to Wishlist'"
                  >
                    <span v-if="course.isWishlisted" class="text-rose-500">❤️</span>
                    <span v-else class="text-slate-400 hover:text-rose-400">♡</span>
                  </button>

                  <!-- 3D HIGH-TECH ILLUSTRATIONS (Matching Screenshot) -->
                  
                  <!-- 1. JS Fundamentals Cube -->
                  <div v-if="course.illustrationType === 'js'" class="relative flex items-center justify-center scale-100 group-hover:scale-110 transition-transform">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-500 border border-amber-300 shadow-xl shadow-amber-500/30 flex items-center justify-center text-slate-950 font-black text-3xl font-mono">
                      JS
                    </div>
                  </div>

                  <!-- 2. Python Programming 3D Logo -->
                  <div v-else-if="course.illustrationType === 'python'" class="relative flex items-center justify-center scale-105 group-hover:scale-115 transition-transform">
                    <div class="relative w-20 h-20 flex items-center justify-center">
                      <div class="absolute top-0 left-1 w-12 h-10 bg-gradient-to-br from-blue-500 to-blue-700 rounded-t-xl rounded-l-xl shadow-md border border-blue-400/50 flex items-start justify-end p-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                      </div>
                      <div class="absolute bottom-0 right-1 w-12 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-b-xl rounded-r-xl shadow-md border border-amber-300/50 flex items-end justify-start p-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                      </div>
                    </div>
                  </div>

                  <!-- 3. React Atom Logo -->
                  <div v-else-if="course.illustrationType === 'react'" class="relative flex items-center justify-center scale-100 group-hover:scale-110 transition-transform">
                    <div class="relative flex flex-col items-center justify-center">
                      <div class="w-16 h-16 rounded-full border-2 border-cyan-400/80 shadow-lg shadow-cyan-500/40 flex items-center justify-center animate-spin-slow">
                        <div class="w-3 h-3 rounded-full bg-cyan-400 shadow-md"></div>
                      </div>
                      <span class="text-sm font-black text-cyan-300 font-mono mt-1">React</span>
                    </div>
                  </div>

                  <!-- 4. UI/UX Design Mockups -->
                  <div v-else-if="course.illustrationType === 'uiux'" class="relative flex items-center justify-center gap-2 scale-90 group-hover:scale-95 transition-transform">
                    <div class="w-14 h-22 rounded-xl bg-slate-900 border-2 border-purple-500/60 shadow-xl p-1 flex flex-col justify-between">
                      <div class="w-3 h-1 bg-slate-700 rounded-full mx-auto"></div>
                      <div class="w-6 h-6 rounded-full bg-purple-600/30 border border-purple-500 mx-auto flex items-center justify-center text-[8px] text-purple-300">✓</div>
                      <div class="w-full h-1 bg-purple-500/40 rounded"></div>
                    </div>
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-900/60 to-slate-900 border border-purple-500/50 shadow-2xl flex flex-col items-center justify-center">
                      <span class="text-base font-black bg-clip-text text-transparent bg-gradient-to-r from-purple-400 to-cyan-400">UI/UX</span>
                    </div>
                  </div>

                  <!-- 5. SQL for Data Analysis -->
                  <div v-else-if="course.illustrationType === 'sql'" class="relative flex items-center justify-center gap-3 scale-95 group-hover:scale-105 transition-transform">
                    <div class="flex flex-col gap-1 items-center">
                      <div class="w-12 h-4 rounded-full bg-cyan-500 border border-cyan-400 shadow-md"></div>
                      <div class="w-12 h-4 rounded-full bg-cyan-600 border border-cyan-500"></div>
                      <div class="w-12 h-4 rounded-full bg-cyan-700"></div>
                    </div>
                    <span class="text-xl font-black text-cyan-400 font-mono">SQL</span>
                  </div>

                  <!-- 6. Digital Marketing Megaphone -->
                  <div v-else-if="course.illustrationType === 'marketing'" class="relative flex items-center justify-center scale-100 group-hover:scale-110 transition-transform">
                    <div class="text-4xl filter drop-shadow-lg">
                      📢
                    </div>
                    <div class="absolute -right-2 top-2 flex flex-col gap-1">
                      <span class="text-xs">👍</span>
                      <span class="text-xs">❤️</span>
                    </div>
                  </div>

                </div>

                <!-- CARD BODY -->
                <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                  <div class="space-y-2">
                    <h3 class="text-sm font-bold text-white group-hover:text-purple-300 transition-colors line-clamp-1">
                      {{ course.title }}
                    </h3>

                    <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
                      {{ course.description }}
                    </p>

                    <!-- Instructor & Rating -->
                    <div class="flex items-center justify-between text-xs pt-1">
                      <div class="flex items-center gap-2 min-w-0">
                        <img
                          :src="course.instructor.avatar"
                          :alt="course.instructor.name"
                          class="w-5 h-5 rounded-full object-cover border border-purple-500/40 shrink-0"
                        />
                        <span class="text-slate-300 font-medium truncate">{{ course.instructor.name }}</span>
                      </div>

                      <div class="flex items-center gap-1 text-amber-400 font-semibold shrink-0">
                        <span>★</span>
                        <span class="text-white">{{ course.rating }}</span>
                        <span class="text-[10px] text-slate-500 font-mono">({{ course.reviewsCountDisplay }})</span>
                      </div>
                    </div>

                    <!-- Meta info (Level • Lessons • Duration) -->
                    <div class="text-[11px] text-slate-400 flex items-center gap-2 pt-1 border-t border-slate-800/60">
                      <span>{{ course.level }}</span>
                      <span>•</span>
                      <span>{{ course.lessonsCount }} Lessons</span>
                      <span>•</span>
                      <span>{{ course.duration }}</span>
                    </div>
                  </div>

                  <!-- PRICE ROW & ACTION -->
                  <div class="flex items-center justify-between pt-2 border-t border-slate-800/80">
                    <div>
                      <span
                        :class="[
                          course.isFree ? 'text-emerald-400' : 'text-cyan-400',
                          'text-sm font-black'
                        ]"
                      >
                        {{ course.priceDisplay }}
                      </span>
                    </div>

                    <button
                      @click.stop="openCourseModal(course)"
                      type="button"
                      class="px-3 py-1.5 rounded-xl bg-purple-600/20 hover:bg-purple-600 text-purple-300 hover:text-white border border-purple-500/30 text-xs font-bold transition-all cursor-pointer"
                    >
                      View Details
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
                @click="openCourseModal(course)"
                class="bg-[#0e1424] border border-slate-800 hover:border-purple-500/40 rounded-2xl p-4 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4 cursor-pointer group transition-all"
              >
                <div class="flex items-center gap-4 min-w-0">
                  <div class="w-16 h-16 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center text-2xl shrink-0">
                    <span>{{ course.illustrationType === 'js' ? '🟨' : (course.illustrationType === 'python' ? '🐍' : (course.illustrationType === 'react' ? '⚛️' : (course.illustrationType === 'uiux' ? '🎨' : (course.illustrationType === 'sql' ? '🗄️' : '📢')))) }}</span>
                  </div>

                  <div class="min-w-0 space-y-1">
                    <div class="flex items-center gap-2">
                      <span :class="[course.badgeColor, 'px-2 py-0.5 rounded text-[9px] font-bold text-white']">
                        {{ course.badge }}
                      </span>
                      <span class="text-xs text-slate-400">{{ course.category }}</span>
                    </div>
                    <h3 class="text-sm font-bold text-white group-hover:text-purple-300 transition-colors truncate">
                      {{ course.title }}
                    </h3>
                    <p class="text-xs text-slate-400 line-clamp-1">{{ course.instructor.name }} • {{ course.lessonsCount }} Lessons • {{ course.duration }}</p>
                  </div>
                </div>

                <div class="flex items-center gap-4 shrink-0" @click.stop>
                  <span :class="course.isFree ? 'text-emerald-400' : 'text-cyan-400'" class="text-base font-black">
                    {{ course.priceDisplay }}
                  </span>
                  <button
                    @click="openCourseModal(course)"
                    type="button"
                    class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md cursor-pointer"
                  >
                    Enroll Now
                  </button>
                </div>
              </div>
            </div>

            <!-- 4. PAGINATION CONTROLS (Matching Screenshot: < 1 2 3 ... 8 >) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-6 border-t border-slate-800/80 text-xs text-slate-400">
              <div>
                Showing 1 to {{ filteredCourses.length }} of 96 courses
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
                  class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 cursor-pointer"
                >
                  2
                </button>
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 cursor-pointer"
                >
                  3
                </button>
                <span class="px-2 text-slate-600">...</span>
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 cursor-pointer"
                >
                  8
                </button>
                <button
                  type="button"
                  class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 cursor-pointer"
                >
                  ›
                </button>
              </div>
            </div>

          </div>

          <!-- Empty Search State -->
          <div
            v-else
            class="bg-slate-900/40 border border-dashed border-slate-800 rounded-3xl p-12 text-center space-y-4"
          >
            <div class="w-16 h-16 rounded-full bg-slate-800/60 flex items-center justify-center mx-auto text-2xl">
              🔍
            </div>
            <div class="space-y-1">
              <h3 class="text-base font-bold text-white">No courses found</h3>
              <p class="text-xs text-slate-400 max-w-sm mx-auto">
                Try searching with a different keyword, skill name, or clear some filter checkboxes.
              </p>
            </div>
            <button
              @click="clearAllFilters"
              type="button"
              class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs cursor-pointer"
            >
              Clear All Filters
            </button>
          </div>

        </main>

      </div>

    </div>

    <!-- 1. COURSE DETAILS MODAL -->
    <div
      v-if="isDetailsModalOpen && selectedCourseForModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isDetailsModalOpen = false"
    >
      <div
        class="relative w-full max-w-2xl bg-[#0e1424] border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto custom-scrollbar"
        @click.stop
      >
        <button
          @click="isDetailsModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-white p-1 text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <span :class="[selectedCourseForModal.badgeColor, 'px-2.5 py-0.5 rounded-full text-[10px] font-bold text-white']">
              {{ selectedCourseForModal.badge }}
            </span>
            <span class="text-xs text-purple-400 font-semibold">• {{ selectedCourseForModal.category }}</span>
          </div>

          <h2 class="text-xl sm:text-2xl font-black text-white">
            {{ selectedCourseForModal.title }}
          </h2>

          <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
            {{ selectedCourseForModal.description }} Master essential core concepts with hands-on practice, quizzes, and projects at Saint Paul Institute.
          </p>
        </div>

        <!-- Instructor Profile & Meta Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-950/80 border border-slate-800">
          <div class="flex items-center gap-3">
            <img
              :src="selectedCourseForModal.instructor.avatar"
              :alt="selectedCourseForModal.instructor.name"
              class="w-10 h-10 rounded-full object-cover border border-purple-500/40"
            />
            <div>
              <p class="text-xs font-bold text-white">{{ selectedCourseForModal.instructor.name }}</p>
              <p class="text-[10px] text-slate-400">{{ selectedCourseForModal.instructor.role }}</p>
            </div>
          </div>

          <div class="flex items-center justify-between sm:justify-end gap-4 text-xs">
            <div>
              <p class="text-[10px] text-slate-500">Rating</p>
              <p class="font-bold text-amber-400">★ {{ selectedCourseForModal.rating }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-500">Duration</p>
              <p class="font-bold text-white">{{ selectedCourseForModal.duration }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-500">Lessons</p>
              <p class="font-bold text-white">{{ selectedCourseForModal.lessonsCount }}</p>
            </div>
          </div>
        </div>

        <!-- Skills Covered -->
        <div class="space-y-2">
          <h4 class="text-xs font-bold text-slate-300 uppercase">What You Will Learn</h4>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="skill in selectedCourseForModal.skills"
              :key="skill"
              class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-purple-300 text-xs font-medium"
            >
              ✓ {{ skill }}
            </span>
          </div>
        </div>

        <!-- Price & Action CTA -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-800">
          <div>
            <p class="text-[10px] text-slate-400">Course Price</p>
            <p :class="selectedCourseForModal.isFree ? 'text-emerald-400' : 'text-cyan-400'" class="text-xl font-black">
              {{ selectedCourseForModal.priceDisplay }}
            </p>
          </div>

          <div class="flex items-center gap-3">
            <Link
              :href="selectedCourseForModal.overviewHref"
              class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold border border-slate-800"
            >
              Full Syllabus
            </Link>

            <button
              @click="handleEnrollAction(selectedCourseForModal)"
              type="button"
              class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30 cursor-pointer"
            >
              {{ selectedCourseForModal.isFree ? '🚀 Enroll Free' : `💳 Enroll ($${selectedCourseForModal.price})` }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. PAYMENT / CHECKOUT MODAL (For Paid Courses) -->
    <div
      v-if="isPaymentModalOpen && selectedCourseForModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isPaymentModalOpen = false"
    >
      <div
        class="relative w-full max-w-lg bg-[#0e1424] border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isPaymentModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-white p-1 text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-indigo-600/20 border border-indigo-500/40 text-indigo-400 flex items-center justify-center text-lg">
            💳
          </div>
          <div>
            <h3 class="text-base font-bold text-white">Course Checkout</h3>
            <p class="text-xs text-slate-400">{{ selectedCourseForModal.title }}</p>
          </div>
        </div>

        <!-- ABA KHQR Code Preview Box -->
        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-center space-y-3">
          <div class="flex items-center justify-between text-xs text-slate-400 border-b border-slate-800 pb-2">
            <span>ABA KHQR Pay</span>
            <span class="font-mono text-emerald-400 font-bold">Expires in: {{ paymentTimer }}</span>
          </div>

          <!-- QR Code Mock with KHQR Frame -->
          <div class="w-48 h-48 mx-auto bg-white rounded-2xl p-2.5 flex flex-col items-center justify-between shadow-xl">
            <div class="text-[9px] font-bold text-red-600 font-mono tracking-widest">KHQR • ABA PAY</div>
            <!-- Dynamic QR Code SVG pattern -->
            <svg class="w-36 h-36" viewBox="0 0 100 100" fill="currentColor">
              <rect width="100" height="100" fill="white" />
              <!-- Top Left Marker -->
              <rect x="10" y="10" width="25" height="25" fill="#0B0F19" />
              <rect x="15" y="15" width="15" height="15" fill="white" />
              <rect x="18" y="18" width="9" height="9" fill="#0B0F19" />
              <!-- Top Right Marker -->
              <rect x="65" y="10" width="25" height="25" fill="#0B0F19" />
              <rect x="70" y="15" width="15" height="15" fill="white" />
              <rect x="73" y="18" width="9" height="9" fill="#0B0F19" />
              <!-- Bottom Left Marker -->
              <rect x="10" y="65" width="25" height="25" fill="#0B0F19" />
              <rect x="15" y="70" width="15" height="15" fill="white" />
              <rect x="18" y="73" width="9" height="9" fill="#0B0F19" />
              <!-- Data Pixels -->
              <rect x="42" y="12" width="6" height="6" fill="#0B0F19" />
              <rect x="52" y="18" width="6" height="6" fill="#0B0F19" />
              <rect x="42" y="30" width="6" height="6" fill="#0B0F19" />
              <rect x="60" y="45" width="6" height="6" fill="#0B0F19" />
              <rect x="45" y="55" width="10" height="10" fill="#7C3AED" rx="2" />
              <rect x="75" y="65" width="6" height="6" fill="#0B0F19" />
              <rect x="65" y="80" width="6" height="6" fill="#0B0F19" />
              <rect x="85" y="85" width="6" height="6" fill="#0B0F19" />
            </svg>
            <div class="text-[8px] text-slate-700 font-bold">Total: ${{ selectedCourseForModal.price }} USD</div>
          </div>

          <p class="text-[11px] text-slate-400">
            Scan this KHQR with your mobile banking app (ABA, ACLEDA, Bakong, Wing)
          </p>
        </div>

        <!-- Verification CTA -->
        <button
          @click="confirmPayment"
          :disabled="isProcessingPayment"
          type="button"
          class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
        >
          <span v-if="isProcessingPayment">Verifying with Bank System...</span>
          <span v-else>I Have Paid • Complete Enrollment</span>
        </button>
      </div>
    </div>

    <!-- 3. ENROLLMENT SUCCESS MODAL -->
    <div
      v-if="isSuccessModalOpen && enrolledSuccessCourse"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isSuccessModalOpen = false"
    >
      <div
        class="relative w-full max-w-md bg-[#0e1424] border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl text-center space-y-5"
        @click.stop
      >
        <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/50 text-emerald-400 flex items-center justify-center text-3xl mx-auto shadow-lg shadow-emerald-500/20">
          🎉
        </div>

        <div class="space-y-1">
          <h3 class="text-xl font-black text-white">Enrollment Successful!</h3>
          <p class="text-xs text-slate-300">
            You are now enrolled in <span class="text-purple-300 font-bold">{{ enrolledSuccessCourse.title }}</span>.
          </p>
        </div>

        <p class="text-xs text-slate-400 leading-relaxed bg-slate-950 p-3.5 rounded-2xl border border-slate-800">
          Your learning progress is now initialized. You can start watching lessons, accessing downloadable slides, and practicing with your 24/7 AI tutor right away!
        </p>

        <div class="space-y-2 pt-2">
          <Link
            href="/student/my-courses/current"
            class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-2"
          >
            <span>▶ Start Learning Now</span>
          </Link>
          <Link
            href="/student/my-courses/enrolled"
            class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 text-xs font-semibold border border-slate-800 flex items-center justify-center"
          >
            View in My Enrolled Courses
          </Link>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>

<style scoped>
@keyframes spin-slow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
.animate-spin-slow {
  animation: spin-slow 12s linear infinite;
}
</style>
