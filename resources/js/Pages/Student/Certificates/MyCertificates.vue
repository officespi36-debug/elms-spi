<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface CertificateItem {
  id: number
  title: string
  student_name: string
  issuer: string
  issued_date: string
  raw_date: string
  status: string
  status_type: 'verified' | 'expired' | 'in_progress'
  cert_number: string
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
  id: number
  title: string
  description: string
  date: string
  icon: string
  icon_bg: string
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

// Default baseline data
const defaultSummary = {
  total_certificates: 12,
  total_note: 'All time earned',
  completed_courses: 8,
  completed_note: 'With certificates',
  this_year: 5,
  this_year_note: 'Certificates earned',
  recent_date: 'May 28, 2025',
  recent_note: 'Most recent',
  total_learning_time: '245h 30m',
  time_note: 'Across all courses',
}

const defaultStats = {
  total: 12,
  items: [
    { label: 'Completed',   count: 8, percentage: 67, color: '#10B981' },
    { label: 'In Progress', count: 2, percentage: 17, color: '#3B82F6' },
    { label: 'Expired',     count: 0, percentage: 0,  color: '#F59E0B' },
    { label: 'Upcoming',    count: 2, percentage: 16, color: '#A855F7' },
  ]
}

const defaultAchievements: AchievementItem[] = [
  { id: 1, title: 'Learning Champion', description: 'Earned 10 certificates', date: 'May 28, 2025', icon: '🏆', icon_bg: 'bg-amber-500/20 text-amber-300 border border-amber-500/30' },
  { id: 2, title: 'Consistent Learner', description: '7 days learning streak', date: 'May 25, 2025', icon: '🎖️', icon_bg: 'bg-blue-500/20 text-blue-300 border border-blue-500/30' },
  { id: 3, title: 'Course Master', description: 'Completed 8 courses', date: 'May 20, 2025', icon: '🛡️', icon_bg: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' },
  { id: 4, title: 'Skill Developer', description: 'Mastered new skills', date: 'May 18, 2025', icon: '🔮', icon_bg: 'bg-purple-500/20 text-purple-300 border border-purple-500/30' },
]

const defaultCertificates: CertificateItem[] = [
  {
    id: 1,
    title: 'JavaScript Advanced',
    student_name: 'Sok Pisey',
    issuer: 'SPI E-Learning Platform',
    issued_date: 'Issued on May 28, 2025',
    raw_date: 'May 28, 2025',
    status: 'Verified',
    status_type: 'verified',
    cert_number: 'SPI-CERT-2025-JS8921',
    qr_url: 'https://spilms.tech/verify/SPI-CERT-2025-JS8921',
    accent_color: 'purple',
    border_class: 'from-purple-500/40 to-indigo-500/40',
    badge_color: 'bg-purple-600/20 text-purple-300 border border-purple-500/30',
    seal_color: '#8B5CF6',
    category: 'Programming',
    grade: 'A+',
    score: '95%',
  },
  {
    id: 2,
    title: 'React Development',
    student_name: 'Sok Pisey',
    issuer: 'SPI E-Learning Platform',
    issued_date: 'Issued on May 25, 2025',
    raw_date: 'May 25, 2025',
    status: 'Verified',
    status_type: 'verified',
    cert_number: 'SPI-CERT-2025-RC4410',
    qr_url: 'https://spilms.tech/verify/SPI-CERT-2025-RC4410',
    accent_color: 'blue',
    border_class: 'from-cyan-500/40 to-blue-500/40',
    badge_color: 'bg-blue-600/20 text-blue-300 border border-blue-500/30',
    seal_color: '#3B82F6',
    category: 'Frontend',
    grade: 'A',
    score: '88%',
  },
  {
    id: 3,
    title: 'Node.js Fundamentals',
    student_name: 'Sok Pisey',
    issuer: 'SPI E-Learning Platform',
    issued_date: 'Issued on May 25, 2025',
    raw_date: 'May 25, 2025',
    status: 'Verified',
    status_type: 'verified',
    cert_number: 'SPI-CERT-2025-ND3190',
    qr_url: 'https://spilms.tech/verify/SPI-CERT-2025-ND3190',
    accent_color: 'emerald',
    border_class: 'from-emerald-500/40 to-teal-500/40',
    badge_color: 'bg-emerald-600/20 text-emerald-300 border border-emerald-500/30',
    seal_color: '#10B981',
    category: 'Backend',
    grade: 'A',
    score: '90%',
  },
  {
    id: 4,
    title: 'Python Programming',
    student_name: 'Sok Pisey',
    issuer: 'SPI E-Learning Platform',
    issued_date: 'Issued on May 20, 2025',
    raw_date: 'May 20, 2025',
    status: 'Verified',
    status_type: 'verified',
    cert_number: 'SPI-CERT-2025-PY7832',
    qr_url: 'https://spilms.tech/verify/SPI-CERT-2025-PY7832',
    accent_color: 'amber',
    border_class: 'from-amber-500/40 to-orange-500/40',
    badge_color: 'bg-amber-600/20 text-amber-300 border border-amber-500/30',
    seal_color: '#F59E0B',
    category: 'Programming',
    grade: 'A',
    score: '88%',
  },
  {
    id: 5,
    title: 'Database Design',
    student_name: 'Sok Pisey',
    issuer: 'SPI E-Learning Platform',
    issued_date: 'Issued on May 15, 2025',
    raw_date: 'May 15, 2025',
    status: 'Verified',
    status_type: 'verified',
    cert_number: 'SPI-CERT-2025-DB6012',
    qr_url: 'https://spilms.tech/verify/SPI-CERT-2025-DB6012',
    accent_color: 'rose',
    border_class: 'from-rose-500/40 to-red-500/40',
    badge_color: 'bg-rose-600/20 text-rose-300 border border-rose-500/30',
    seal_color: '#EF4444',
    category: 'Database',
    grade: 'B+',
    score: '78%',
  },
  {
    id: 6,
    title: 'UI/UX Design Basics',
    student_name: 'Sok Pisey',
    issuer: 'SPI E-Learning Platform',
    issued_date: 'Issued on May 10, 2025',
    raw_date: 'May 10, 2025',
    status: 'Verified',
    status_type: 'verified',
    cert_number: 'SPI-CERT-2025-UX1182',
    qr_url: 'https://spilms.tech/verify/SPI-CERT-2025-UX1182',
    accent_color: 'cyan',
    border_class: 'from-teal-500/40 to-cyan-500/40',
    badge_color: 'bg-cyan-600/20 text-cyan-300 border border-cyan-500/30',
    seal_color: '#06B6D4',
    category: 'Design',
    grade: 'A',
    score: '85%',
  },
  {
    id: 7,
    title: 'HTML & CSS Basics',
    student_name: 'Sok Pisey',
    issuer: 'SPI E-Learning Platform',
    issued_date: 'Issued on May 5, 2025',
    raw_date: 'May 5, 2025',
    status: 'Verified',
    status_type: 'verified',
    cert_number: 'SPI-CERT-2025-HC9044',
    qr_url: 'https://spilms.tech/verify/SPI-CERT-2025-HC9044',
    accent_color: 'purple',
    border_class: 'from-purple-500/40 to-indigo-500/40',
    badge_color: 'bg-purple-600/20 text-purple-300 border border-purple-500/30',
    seal_color: '#8B5CF6',
    category: 'Frontend',
    grade: 'A+',
    score: '96%',
  },
  {
    id: 8,
    title: 'Git & GitHub',
    student_name: 'Sok Pisey',
    issuer: 'SPI E-Learning Platform',
    issued_date: 'Issued on Apr 2, 2025',
    raw_date: 'Apr 2, 2025',
    status: 'Verified',
    status_type: 'verified',
    cert_number: 'SPI-CERT-2025-GT0058',
    qr_url: 'https://spilms.tech/verify/SPI-CERT-2025-GT0058',
    accent_color: 'teal',
    border_class: 'from-teal-500/40 to-slate-500/40',
    badge_color: 'bg-teal-600/20 text-teal-300 border border-teal-500/30',
    seal_color: '#14B8A6',
    category: 'DevOps',
    grade: 'A',
    score: '92%',
  },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const statistics = computed(() => props.analytics?.statistics || defaultStats)
const recentAchievements = computed(() => props.analytics?.recent_achievements || defaultAchievements)
const certificates = computed(() => props.analytics?.certificates || defaultCertificates)

// Filter & View State
const activeStatusTab = ref<string>(props.filters?.status || 'all')
const searchQuery = ref<string>(props.filters?.search || '')
const selectedCategory = ref<string>(props.filters?.category || 'all')
const selectedCourse = ref<string>(props.filters?.course || 'all')
const selectedIssuer = ref<string>(props.filters?.issuer || 'all')
const selectedSort = ref<string>(props.filters?.sort || 'newest')
const isGridView = ref(true)

// Modals State
const selectedCertForModal = ref<CertificateItem | null>(null)
const isPreviewModalOpen = ref(false)
const isShareModalOpen = ref(false)
const copiedSuccess = ref(false)

const openPreviewModal = (cert: CertificateItem) => {
  selectedCertForModal.value = cert
  isPreviewModalOpen.value = true
}

const openShareModal = (cert: CertificateItem) => {
  selectedCertForModal.value = cert
  isShareModalOpen.value = true
}

const copyVerificationLink = () => {
  if (selectedCertForModal.value) {
    navigator.clipboard.writeText(selectedCertForModal.value.qr_url)
    copiedSuccess.value = true
    setTimeout(() => {
      copiedSuccess.value = false
    }, 2500)
  }
}

const handleFilterChange = (overrideTab?: string) => {
  if (overrideTab) {
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
</script>

<template>
  <StudentLayout title="My Certificates — Student Degrees & Achievements">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>My Certificates</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-600 dark:text-purple-400 text-lg">🛡️</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
            View and manage all your earned certificates. Share your achievements with the world!
          </p>
        </div>
      </div>

      <!-- ================= 2. 5 TOP SUMMARY METRIC CARDS ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        
        <!-- Card 1: Total Certificates -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm dark:shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Total Certificates</p>
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
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Completed Courses</p>
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
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">This Year</p>
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
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Recent Certificate</p>
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
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Total Learning Time</p>
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
                <option value="Frontend">Frontend</option>
                <option value="Backend">Backend</option>
                <option value="Database">Database</option>
                <option value="Design">Design</option>
                <option value="DevOps">DevOps</option>
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
                v-model="selectedIssuer"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="all">All Issuers</option>
                <option value="SPI E-Learning Platform">SPI E-Learning Platform</option>
              </select>

              <select
                v-model="selectedSort"
                @change="handleFilterChange()"
                class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
              >
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
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
                { key: 'verified', label: 'Verified' },
                { key: 'expired', label: 'Expired' },
                { key: 'in_progress', label: 'In Progress' }
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

          <!-- CERTIFICATE GALLERY (4x2 Grid Matching Screenshot) -->
          <div :class="[isGridView ? 'grid grid-cols-1 sm:grid-cols-2 gap-4' : 'space-y-3']">
            <div
              v-for="cert in certificates"
              :key="cert.id"
              class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 hover:border-purple-500/40 rounded-3xl p-4 shadow-sm dark:shadow-xl flex flex-col justify-between group transition-all"
            >
              <!-- TOP: CERTIFICATE CANVAS MINI PREVIEW -->
              <div class="relative w-full aspect-[16/10] bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-inner flex flex-col items-center justify-between p-3 text-center group-hover:scale-[1.01] transition-transform">
                
                <!-- Realistic Ornamental Certificate Framing -->
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

                  <!-- Official SPI Gold Seal -->
                  <div class="w-6 h-6 rounded-full bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 border border-amber-300 flex items-center justify-center text-[7px] text-slate-950 font-black shadow-md font-serif">
                    ★
                  </div>

                  <div>
                    <p class="font-serif italic text-slate-300">Director SPI</p>
                    <p class="border-t border-slate-700 pt-0.5">Signature</p>
                  </div>
                </div>

              </div>

              <!-- CARD BODY INFO -->
              <div class="pt-3 pb-1 space-y-1">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate max-w-[180px]">
                    {{ cert.title }}
                  </h3>
                  <span class="px-2 py-0.5 rounded-md bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 text-[10px] font-bold font-mono">
                    {{ cert.status }}
                  </span>
                </div>

                <p class="text-[11px] text-slate-600 dark:text-slate-400">{{ cert.issuer }}</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ cert.issued_date }}</p>
              </div>

              <!-- CARD ACTIONS -->
              <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                <button
                  @click="openPreviewModal(cert)"
                  class="py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 hover:border-purple-500/40 text-slate-700 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                >
                  <span>👁</span>
                  <span>View</span>
                </button>

                <button
                  @click="openPreviewModal(cert)"
                  class="py-1.5 rounded-xl bg-purple-50 dark:bg-purple-600/30 border border-purple-200 dark:border-purple-500/40 hover:bg-purple-600 hover:text-white text-purple-700 dark:text-purple-200 font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer shadow-xs"
                >
                  <span>⬇</span>
                  <span>Download</span>
                </button>
              </div>

            </div>
          </div>

        </div>

        <!-- ================= RIGHT (4/12): WIDGETS SIDEBAR ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- WIDGET 1: Certificate Stats Donut Chart -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Certificate Stats</h3>
            </div>

            <div class="flex items-center justify-between gap-4">
              <!-- Donut Chart -->
              <div class="relative w-24 h-24 flex items-center justify-center shrink-0">
                <svg class="w-24 h-24 -rotate-90 transform" viewBox="0 0 36 36">
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#E2E8F0" class="dark:stroke-slate-800" stroke-width="4.5" />
                  <!-- Completed: 67% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#10B981" stroke-width="4.5" stroke-dasharray="67, 100" stroke-dashoffset="0" />
                  <!-- In Progress: 17% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#3B82F6" stroke-width="4.5" stroke-dasharray="17, 100" stroke-dashoffset="-67" />
                  <!-- Upcoming: 16% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#A855F7" stroke-width="4.5" stroke-dasharray="16, 100" stroke-dashoffset="-84" />
                </svg>

                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-slate-900 dark:text-white font-mono leading-none">{{ statistics.total }}</span>
                  <span class="text-[8px] text-slate-500 dark:text-slate-400 mt-0.5 font-medium">Total</span>
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
                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: item.color }"></span>
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
              <span>View All Statistics</span>
            </button>
          </div>

          <!-- WIDGET 2: Recent Achievements -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Recent Achievements</h3>
              <span class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline cursor-pointer">View All</span>
            </div>

            <div class="space-y-2">
              <div
                v-for="ach in recentAchievements"
                :key="ach.id"
                class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/60 flex items-center justify-between gap-3 hover:border-purple-500/30 transition-all cursor-pointer shadow-xs"
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
            
            <!-- Glowing Purple Rosette Medal -->
            <div class="absolute -right-2 top-4 w-16 h-16 opacity-30 text-purple-400 text-5xl pointer-events-none">
              🏅
            </div>

            <div class="space-y-1">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Share Your Achievement</h3>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                Share your certificates on LinkedIn, Twitter, or download high-quality versions.
              </p>
            </div>

            <!-- Social Media Icon Buttons -->
            <div class="flex items-center gap-2 pt-1">
              <button
                @click="openShareModal(certificates[0])"
                class="w-8 h-8 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center justify-center shadow-md transition-transform hover:scale-105 cursor-pointer"
                title="Share on LinkedIn"
              >
                in
              </button>
              <button
                @click="openShareModal(certificates[0])"
                class="w-8 h-8 rounded-xl bg-sky-500 hover:bg-sky-400 text-white font-bold text-xs flex items-center justify-center shadow-md transition-transform hover:scale-105 cursor-pointer"
                title="Share on Twitter / X"
              >
                𝕏
              </button>
              <button
                @click="openShareModal(certificates[0])"
                class="w-8 h-8 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs flex items-center justify-center shadow-md transition-transform hover:scale-105 cursor-pointer"
                title="Share on Facebook"
              >
                f
              </button>
              <button
                @click="copyVerificationLink()"
                class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white font-bold text-xs flex items-center justify-center border border-slate-200 dark:border-slate-700 transition-transform hover:scale-105 cursor-pointer shadow-xs"
                title="Copy Link"
              >
                🔗
              </button>
            </div>

            <!-- Action Button -->
            <button
              @click="openShareModal(certificates[0])"
              class="w-full py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-95"
            >
              <span>📢</span>
              <span>Share Certificates</span>
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
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">All certificates are blockchain verified and tamper-proof</h4>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Your certificates are securely stored and can be verified by employers and institutions worldwide.</p>
          </div>
        </div>

        <Link
          href="/student/certificates/verify"
          class="px-5 py-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:border-blue-500/50 text-slate-900 dark:text-white font-bold text-xs shadow-sm whitespace-nowrap transition-colors"
        >
          Verify Certificate
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
            <h3 class="text-base font-black text-slate-900 dark:text-white">Official Certificate Verification</h3>
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
            :href="`/student/certificates/verify?code=${selectedCertForModal.cert_number}`"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-cyan-600 dark:text-cyan-300 font-bold border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 shadow-xs"
          >
            <span>🔍</span>
            <span>Verify on Registry</span>
          </Link>

          <div class="flex items-center gap-2">
            <button
              @click="isPreviewModalOpen = false; openShareModal(selectedCertForModal)"
              class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200 dark:hover:text-white cursor-pointer"
            >
              Share Link
            </button>
            <a
              :href="`/student/certificates/download-share`"
              class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5 cursor-pointer"
            >
              <span>📥</span>
              <span>Download PDF</span>
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
            <h3 class="text-base font-black text-slate-900 dark:text-white">Share Your Certificate</h3>
          </div>
          <button
            @click="isShareModalOpen = false"
            class="text-slate-400 hover:text-slate-950 dark:hover:text-white cursor-pointer"
          >
            ✕
          </button>
        </div>

        <p class="text-xs text-slate-600 dark:text-slate-300">
          Share your accomplishment in <strong class="text-purple-600 dark:text-purple-300">{{ selectedCertForModal.title }}</strong> with employers and your network.
        </p>

        <!-- Verification URL Copy Box -->
        <div class="space-y-1.5">
          <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Public Verification URL:</label>
          <div class="flex items-center gap-2">
            <input
              type="text"
              readonly
              :value="selectedCertForModal.qr_url"
              class="flex-1 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-mono text-slate-700 dark:text-slate-300 select-all"
            />
            <button
              @click="copyVerificationLink()"
              class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md whitespace-nowrap cursor-pointer"
            >
              {{ copiedSuccess ? '✓ Copied!' : 'Copy' }}
            </button>
          </div>
        </div>

        <!-- Social Buttons Grid -->
        <div class="grid grid-cols-3 gap-2 pt-2 text-xs">
          <button
            @click="copyVerificationLink()"
            class="p-2.5 rounded-xl bg-blue-500/10 dark:bg-blue-600/20 border border-blue-500/20 dark:border-blue-500/30 text-blue-700 dark:text-blue-300 hover:bg-blue-600 hover:text-white font-bold flex flex-col items-center gap-1 transition-all cursor-pointer"
          >
            <span>💼</span>
            <span>LinkedIn</span>
          </button>
          <button
            @click="copyVerificationLink()"
            class="p-2.5 rounded-xl bg-sky-500/10 dark:bg-sky-500/20 border border-sky-500/20 dark:border-sky-500/30 text-sky-700 dark:text-sky-300 hover:bg-sky-500 hover:text-white font-bold flex flex-col items-center gap-1 transition-all cursor-pointer"
          >
            <span>🐦</span>
            <span>Twitter / X</span>
          </button>
          <button
            @click="copyVerificationLink()"
            class="p-2.5 rounded-xl bg-indigo-500/10 dark:bg-indigo-600/20 border border-indigo-500/20 dark:border-indigo-500/30 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-600 hover:text-white font-bold flex flex-col items-center gap-1 transition-all cursor-pointer"
          >
            <span>📘</span>
            <span>Facebook</span>
          </button>
        </div>

        <div class="flex justify-end pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
          <button
            @click="isShareModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200 cursor-pointer"
          >
            Done
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
