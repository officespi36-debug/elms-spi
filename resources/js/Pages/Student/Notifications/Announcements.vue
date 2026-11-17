<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { i18n } from '@/Services/i18n'

interface Attachment {
  name: string
  size: string
  type: string
}

interface Announcement {
  id: number
  title: string
  description: string
  content?: string
  sourceType: 'system' | 'teacher'
  source: string
  category: string
  teacherName?: string
  courseName?: string
  courseRoute?: string
  actionLabel?: string
  actionRoute?: string
  date: string
  time?: string
  isUnread: boolean
  isNew?: boolean
  isPinned?: boolean
  priority?: 'high' | 'normal' | 'low'
  attachments?: Attachment[]
}

const props = defineProps<{
  dbAnnouncements?: any[]
}>()

const currentLang = computed(() => i18n.locale.value)

const mappedDbAnnouncements = computed<Announcement[]>(() => {
  if (!props.dbAnnouncements || props.dbAnnouncements.length === 0) return []
  const isKm = currentLang.value === 'km'
  return props.dbAnnouncements.map((a: any) => ({
    id: a.id,
    title: isKm ? (a.title_kh || a.title_en || 'សេចក្តីប្រកាស') : (a.title_en || a.title_kh || 'Announcement'),
    description: isKm ? (a.body_kh || a.body_en || '') : (a.body_en || a.body_kh || ''),
    content: isKm ? (a.body_kh || a.body_en || '') : (a.body_en || a.body_kh || ''),
    sourceType: (a.audience_type === 'students' ? 'teacher' : 'system') as 'system' | 'teacher',
    source: isKm ? 'សេចក្តីប្រកាសផ្លូវការ SPI' : 'SPI Official Announcement',
    category: 'Academic',
    date: a.created_at ? new Date(a.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '',
    time: a.created_at ? new Date(a.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '',
    isUnread: true,
    isNew: true,
    isPinned: Boolean(a.is_pinned),
    priority: (a.priority === 'urgent' || a.priority === 'high') ? 'high' : 'normal',
    actionLabel: isKm ? 'មើលវគ្គសិក្សា' : 'View Courses',
    actionRoute: '/student/my-courses/enrolled',
  }))
})

// Reactive announcements list (100% real DB records)
const announcements = ref<Announcement[]>([...mappedDbAnnouncements.value])

watch(mappedDbAnnouncements, (nextList) => {
  const prevReadMap = new Map(announcements.value.map(a => [a.id, a.isUnread]))
  announcements.value = nextList.map(item => ({
    ...item,
    isUnread: prevReadMap.has(item.id) ? Boolean(prevReadMap.get(item.id)) : item.isUnread,
  }))
})

// Active Source Tab: 'all' | 'system' | 'teacher'
type SourceTab = 'all' | 'system' | 'teacher'
const activeSourceTab = ref<SourceTab>('all')

// Search & Filters
const searchQuery = ref<string>('')
const selectedCategory = ref<string>('All Categories')
const selectedSort = ref<string>('newest')
const currentPage = ref<number>(1)
const itemsPerPage = 6

// Modals State
const selectedAnnouncement = ref<Announcement | null>(null)
const isDetailModalOpen = ref<boolean>(false)
const isPreferencesModalOpen = ref<boolean>(false)
const isMarkAllConfirmModalOpen = ref<boolean>(false)
const toastMessage = ref<string | null>(null)

const showToast = (msg: string) => {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = null
  }, 3000)
}

// Preferences toggles
const preferences = ref({
  email: true,
  sms: false,
  push: true,
  systemAlerts: true,
  teacherAlerts: true,
  assignmentAlerts: true,
  examAlerts: true,
})

// Dynamic Categories with Counts from real announcements
const categoryCounts = computed(() => {
  const counts: Record<string, number> = {
    'General': 0,
    'Academic': 0,
    'Course Updates': 0,
    'Assignments': 0,
    'Exams': 0,
  }

  for (const item of announcements.value) {
    const key = item.category || 'General'
    counts[key] = (counts[key] ?? 0) + 1
  }

  return counts
})

// Computed Filtered & Sorted Announcements
const filteredAnnouncements = computed(() => {
  let result = [...announcements.value]

  // Filter by Source Tab
  if (activeSourceTab.value === 'system') {
    result = result.filter(a => a.sourceType === 'system')
  } else if (activeSourceTab.value === 'teacher') {
    result = result.filter(a => a.sourceType === 'teacher')
  }

  // Filter by Category
  if (selectedCategory.value !== 'All Categories') {
    result = result.filter(a => a.category.toLowerCase() === selectedCategory.value.toLowerCase())
  }

  // Search Filter
  if (searchQuery.value.trim() !== '') {
    const q = searchQuery.value.toLowerCase().trim()
    result = result.filter(a =>
      a.title.toLowerCase().includes(q) ||
      a.description.toLowerCase().includes(q) ||
      a.source.toLowerCase().includes(q) ||
      a.category.toLowerCase().includes(q) ||
      (a.teacherName && a.teacherName.toLowerCase().includes(q))
    )
  }

  // Sorting
  if (selectedSort.value === 'newest') {
    result.sort((a, b) => b.id - a.id)
  } else if (selectedSort.value === 'oldest') {
    result.sort((a, b) => a.id - b.id)
  } else if (selectedSort.value === 'unread') {
    result.sort((a, b) => (b.isUnread ? 1 : 0) - (a.isUnread ? 1 : 0))
  } else if (selectedSort.value === 'important') {
    result.sort((a, b) => (b.isPinned ? 1 : 0) - (a.isPinned ? 1 : 0))
  }

  return result
})

// Paginated Announcements
const totalAnnouncementsCount = computed(() => 24)
const totalFilteredCount = computed(() => filteredAnnouncements.value.length)
const totalPages = computed(() => Math.ceil(filteredAnnouncements.value.length / itemsPerPage) || 1)

const paginatedAnnouncements = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredAnnouncements.value.slice(start, start + itemsPerPage)
})

// Summary Statistics
const summaryStats = computed(() => {
  const unreadCount = announcements.value.filter(a => a.isUnread).length
  return {
    total: 24,
    unread: unreadCount,
    teacher: 8,
    system: 16,
  }
})

// Important announcements for right sidebar
const importantList = computed(() => {
  return announcements.value.slice(0, 3)
})

// Open Announcement Detail Modal (Phase 2 & 3: automatically marks unread as read)
const openAnnouncementDetail = (announcement: Announcement) => {
  selectedAnnouncement.value = announcement
  if (announcement.isUnread) {
    announcement.isUnread = false
    showToast(`Marked "${announcement.title.slice(0, 24)}..." as read`)
  }
  isDetailModalOpen.value = true
}

// Mark Single as Read / Unread
const toggleReadStatus = (announcement: Announcement, event?: Event) => {
  if (event) event.stopPropagation()
  announcement.isUnread = !announcement.isUnread
  showToast(announcement.isUnread ? 'Marked as unread' : 'Marked as read')
}

// Mark All as Read Action
const confirmMarkAllAsRead = () => {
  announcements.value.forEach(a => {
    a.isUnread = false
  })
  isMarkAllConfirmModalOpen.value = false
  showToast('All announcements marked as read.')
}

// Filter by Category Click from Sidebar
const filterByCategory = (category: string) => {
  selectedCategory.value = category
  currentPage.value = 1
}

// Download Attachment
const downloadAttachment = (att: Attachment) => {
  window.alert(`Downloading ${att.name} (${att.size})...`)
}

// Save Preferences
const savePreferences = () => {
  isPreferencesModalOpen.value = false
  showToast('Notification preferences saved successfully!')
}
</script>

<template>
  <StudentLayout title="System & Teacher Announcements">
    <div class="space-y-6 max-w-7xl mx-auto pb-12">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>System & Teacher Announcements</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-600 dark:text-purple-400 text-lg">📢</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
            Stay updated with important announcements from the system and your teachers.
          </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
          <button
            @click="isMarkAllConfirmModalOpen = true"
            class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white font-bold text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs active:scale-95"
          >
            <span>✓✓</span>
            <span>Mark All as Read</span>
          </button>
        </div>
      </div>

      <!-- ================= 2. MAIN 2-COLUMN LAYOUT ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT COLUMN (~70% / lg:col-span-8) ================= -->
        <div class="lg:col-span-8 space-y-4">

          <!-- 2.1 ANNOUNCEMENT SOURCE TABS -->
          <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <button
              @click="activeSourceTab = 'all'; currentPage = 1"
              :class="[
                activeSourceTab === 'all'
                  ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-950/20'
                  : 'bg-white dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-800/80 font-medium shadow-xs',
                'px-4 py-2 rounded-xl text-xs transition-all cursor-pointer whitespace-nowrap active:scale-95'
              ]"
            >
              All Announcements
            </button>

            <button
              @click="activeSourceTab = 'system'; currentPage = 1"
              :class="[
                activeSourceTab === 'system'
                  ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-950/20'
                  : 'bg-white dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-800/80 font-medium shadow-xs',
                'px-4 py-2 rounded-xl text-xs transition-all cursor-pointer whitespace-nowrap active:scale-95'
              ]"
            >
              System Announcements
            </button>

            <button
              @click="activeSourceTab = 'teacher'; currentPage = 1"
              :class="[
                activeSourceTab === 'teacher'
                  ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-950/20'
                  : 'bg-white dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border border-slate-200 dark:border-slate-800/80 font-medium shadow-xs',
                'px-4 py-2 rounded-xl text-xs transition-all cursor-pointer whitespace-nowrap active:scale-95'
              ]"
            >
              Teacher Announcements
            </button>
          </div>

          <!-- 2.2 FILTER & SEARCH BAR -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-3 shadow-sm dark:shadow-lg flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            
            <!-- Search Input -->
            <div class="relative flex-1 min-w-[200px]">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-xs">🔍</span>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search announcement..."
                class="w-full pl-8 pr-3 py-1.5 bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-purple-500 transition-colors"
              />
            </div>

            <!-- Filter Controls Group -->
            <div class="flex flex-wrap items-center gap-2">
              <!-- Category Dropdown -->
              <div class="relative">
                <select
                  v-model="selectedCategory"
                  @change="currentPage = 1"
                  class="bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-300 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
                >
                  <option value="All Categories">⊞ All Categories</option>
                  <option value="General">General</option>
                  <option value="Academic">Academic</option>
                  <option value="Course Updates">Course Updates</option>
                  <option value="Assignments">Assignments</option>
                  <option value="Exams">Exams</option>
                  <option value="Scholarship">Scholarship</option>
                  <option value="Maintenance">Maintenance</option>
                </select>
              </div>

              <!-- Sort Dropdown -->
              <div class="relative">
                <select
                  v-model="selectedSort"
                  class="bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-700 dark:text-slate-300 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
                >
                  <option value="newest">⇅ Newest First</option>
                  <option value="oldest">⇅ Oldest First</option>
                  <option value="unread">⇅ Unread First</option>
                  <option value="important">⇅ Important First</option>
                </select>
              </div>

              <!-- Filter reset button -->
              <button
                @click="searchQuery = ''; selectedCategory = 'All Categories'; selectedSort = 'newest'; activeSourceTab = 'all'"
                class="bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-700/80 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white rounded-xl px-3 py-1.5 flex items-center gap-1.5 transition-colors cursor-pointer"
                title="Reset filters"
              >
                <span>☡</span>
                <span>Filter</span>
              </button>
            </div>

          </div>

          <!-- 2.3 ANNOUNCEMENT CARDS LIST -->
          <div class="space-y-3">
            <div
              v-for="announcement in paginatedAnnouncements"
              :key="announcement.id"
              @click="openAnnouncementDetail(announcement)"
              :class="[
                announcement.isUnread
                  ? 'bg-white dark:bg-slate-900/90 border-purple-500/30 dark:border-purple-500/30 hover:border-purple-500/60 shadow-md shadow-purple-500/5 dark:shadow-purple-950/20'
                  : 'bg-white/80 dark:bg-[#0F172A]/70 border-slate-200/90 dark:border-slate-800/80 hover:border-slate-300 dark:hover:border-slate-700 text-slate-600 dark:text-slate-400',
                'border rounded-3xl p-4 sm:p-5 transition-all cursor-pointer group relative overflow-hidden'
              ]"
            >
              <!-- Unread Purple Left Indicator Dot / Bar -->
              <span
                v-if="announcement.isUnread"
                class="absolute left-2.5 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-purple-500 shadow-sm shadow-purple-400"
              ></span>

              <div class="flex items-start justify-between gap-4 pl-3">
                
                <!-- Left: Icon & Details -->
                <div class="flex items-start gap-3.5 min-w-0 flex-1">
                  
                  <!-- Icon Container -->
                  <div
                    :class="[
                      announcement.category === 'General' ? 'bg-purple-600/10 dark:bg-purple-600/20 text-purple-600 dark:text-purple-400 border border-purple-500/20 dark:border-purple-500/30' :
                      announcement.category === 'Assignments' && announcement.sourceType === 'teacher' && announcement.id === 2 ? 'bg-emerald-600/10 dark:bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 dark:border-emerald-500/30' :
                      announcement.category === 'Academic' ? 'bg-blue-600/10 dark:bg-blue-600/20 text-blue-600 dark:text-blue-400 border border-blue-500/20 dark:border-blue-500/30' :
                      announcement.category === 'Assignments' ? 'bg-amber-600/10 dark:bg-amber-600/20 text-amber-600 dark:text-amber-400 border border-amber-500/20 dark:border-amber-500/30' :
                      announcement.category === 'Scholarship' ? 'bg-purple-600/10 dark:bg-purple-600/20 text-purple-600 dark:text-purple-400 border border-purple-500/20 dark:border-purple-500/30' :
                      'bg-cyan-600/10 dark:bg-cyan-600/20 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 dark:border-cyan-500/30',
                      'w-10 h-10 sm:w-11 sm:h-11 rounded-2xl flex items-center justify-center text-lg sm:text-xl shrink-0 mt-0.5 shadow-inner'
                    ]"
                  >
                    <span v-if="announcement.category === 'General'">📢</span>
                    <span v-else-if="announcement.id === 2">🎓</span>
                    <span v-else-if="announcement.category === 'Academic'">📅</span>
                    <span v-else-if="announcement.category === 'Assignments'">📄</span>
                    <span v-else-if="announcement.category === 'Scholarship'">🎁</span>
                    <span v-else>ℹ️</span>
                  </div>

                  <!-- Content Area -->
                  <div class="min-w-0 space-y-1.5 flex-1">
                    
                    <!-- Title & Badges Row -->
                    <div class="flex flex-wrap items-center gap-2">
                      <h3
                        :class="[
                          announcement.isUnread ? 'text-slate-900 dark:text-white font-bold' : 'text-slate-700 dark:text-slate-300 font-semibold',
                          'text-xs sm:text-sm tracking-tight group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors line-clamp-1'
                        ]"
                      >
                        {{ announcement.title }}
                      </h3>

                      <!-- New Badge -->
                      <span
                        v-if="announcement.isNew"
                        class="px-2 py-0.5 rounded-full bg-purple-600 text-white text-[9.5px] font-bold uppercase tracking-wider shadow-xs"
                      >
                        New
                      </span>

                      <!-- Pinned Icon -->
                      <span
                        v-if="announcement.isPinned"
                        class="text-rose-500 dark:text-rose-400 text-xs"
                        title="Pinned Announcement"
                      >
                        📌
                      </span>
                    </div>

                    <!-- Description -->
                    <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed font-normal">
                      {{ announcement.description }}
                    </p>

                    <!-- Meta Tags Footer -->
                    <div class="flex flex-wrap items-center gap-2 pt-1 text-[11px]">
                      
                      <!-- Source & Category / Teacher Info -->
                      <span
                        v-if="announcement.sourceType === 'teacher'"
                        class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1"
                      >
                        <span>{{ announcement.source }}</span>
                        <span class="text-slate-400 dark:text-slate-600">•</span>
                        <span>{{ announcement.teacherName }}</span>
                      </span>

                      <span
                        v-else-if="announcement.category === 'Academic'"
                        class="text-blue-600 dark:text-blue-400 font-semibold flex items-center gap-1"
                      >
                        <span>{{ announcement.source }}</span>
                        <span class="text-slate-400 dark:text-slate-600">•</span>
                        <span>{{ announcement.category }}</span>
                      </span>

                      <span
                        v-else-if="announcement.category === 'Assignments'"
                        class="text-amber-600 dark:text-amber-400 font-semibold flex items-center gap-1"
                      >
                        <span>{{ announcement.source }}</span>
                        <span class="text-slate-400 dark:text-slate-600">•</span>
                        <span>{{ announcement.teacherName }}</span>
                      </span>

                      <span
                        v-else
                        class="text-purple-600 dark:text-purple-400 font-semibold flex items-center gap-1"
                      >
                        <span>{{ announcement.source }}</span>
                        <span class="text-slate-400 dark:text-slate-600">•</span>
                        <span>{{ announcement.category }}</span>
                      </span>

                    </div>

                  </div>

                </div>

                <!-- Right: Date, Time & Status Pill -->
                <div class="flex flex-col items-end justify-between self-stretch shrink-0 text-right space-y-2">
                  
                  <div class="space-y-0.5">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono font-medium">{{ announcement.date }}</p>
                    <p v-if="announcement.time" class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ announcement.time }}</p>
                  </div>

                  <!-- Status Badge -->
                  <div>
                    <span
                      v-if="announcement.isUnread"
                      class="px-2.5 py-0.5 rounded-full bg-lime-500/10 dark:bg-lime-500/20 text-lime-600 dark:text-lime-400 border border-lime-500/30 text-[10px] font-bold flex items-center gap-1"
                    >
                      <span>!</span>
                      <span>Unread</span>
                    </span>

                    <span
                      v-else
                      class="px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 text-[10px] font-medium flex items-center gap-1"
                    >
                      <span>👁</span>
                      <span>Read</span>
                    </span>
                  </div>

                </div>

              </div>

            </div>

            <!-- Empty State -->
            <div
              v-if="paginatedAnnouncements.length === 0"
              class="p-12 text-center bg-white dark:bg-[#0F172A]/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl space-y-3 shadow-xs"
            >
              <div class="w-12 h-12 rounded-full bg-purple-500/10 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 flex items-center justify-center text-2xl mx-auto">
                📢
              </div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">No announcements found</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">Try changing your search terms or filter criteria.</p>
              <button
                @click="searchQuery = ''; selectedCategory = 'All Categories'; activeSourceTab = 'all'"
                class="px-4 py-2 rounded-xl bg-purple-600 text-white font-bold text-xs shadow-md cursor-pointer"
              >
                Clear All Filters
              </button>
            </div>
          </div>

          <!-- 2.4 PAGINATION BAR -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 text-xs">
            <p class="text-slate-500 dark:text-slate-400 font-medium">
              Showing 1 to {{ paginatedAnnouncements.length }} of {{ totalAnnouncementsCount }} announcements
            </p>

            <div class="flex items-center gap-1 self-center sm:self-auto">
              <button
                @click="currentPage = Math.max(1, currentPage - 1)"
                :disabled="currentPage === 1"
                class="w-7 h-7 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white disabled:opacity-40 flex items-center justify-center font-bold cursor-pointer shadow-xs"
              >
                «
              </button>

              <button
                v-for="p in totalPages"
                :key="p"
                @click="currentPage = p"
                :class="[
                  currentPage === p
                    ? 'bg-purple-600 text-white font-bold'
                    : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white',
                  'w-7 h-7 rounded-lg flex items-center justify-center text-xs transition-colors cursor-pointer shadow-xs'
                ]"
              >
                {{ p }}
              </button>

              <button
                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="w-7 h-7 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white disabled:opacity-40 flex items-center justify-center font-bold cursor-pointer shadow-xs"
              >
                »
              </button>
            </div>
          </div>

        </div>

        <!-- ================= RIGHT COLUMN (~30% / lg:col-span-4) ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- CARD 1: ANNOUNCEMENT SUMMARY -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Announcement Summary</h3>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
              
              <!-- Total -->
              <div class="p-3 bg-purple-50 dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/40 rounded-2xl space-y-1">
                <div class="flex items-center justify-between">
                  <span class="text-purple-600 dark:text-purple-400 text-base">📢</span>
                  <span class="text-lg font-black text-slate-900 dark:text-white font-mono">{{ summaryStats.total }}</span>
                </div>
                <p class="text-[10.5px] text-slate-600 dark:text-slate-400 font-medium leading-tight">Total Announcements</p>
              </div>

              <!-- Unread -->
              <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 rounded-2xl space-y-1">
                <div class="flex items-center justify-between">
                  <span class="text-emerald-600 dark:text-emerald-400 text-base">✉️</span>
                  <span class="text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ summaryStats.unread }}</span>
                </div>
                <p class="text-[10.5px] text-slate-600 dark:text-slate-400 font-medium leading-tight">Unread Announcements</p>
              </div>

              <!-- Teacher -->
              <div class="p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 rounded-2xl space-y-1">
                <div class="flex items-center justify-between">
                  <span class="text-blue-600 dark:text-blue-400 text-base">🎓</span>
                  <span class="text-lg font-black text-slate-900 dark:text-white font-mono">{{ summaryStats.teacher }}</span>
                </div>
                <p class="text-[10.5px] text-slate-600 dark:text-slate-400 font-medium leading-tight">Teacher Announcements</p>
              </div>

              <!-- System -->
              <div class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/40 rounded-2xl space-y-1">
                <div class="flex items-center justify-between">
                  <span class="text-amber-600 dark:text-amber-400 text-base">⚙️</span>
                  <span class="text-lg font-black text-slate-900 dark:text-white font-mono">{{ summaryStats.system }}</span>
                </div>
                <p class="text-[10.5px] text-slate-600 dark:text-slate-400 font-medium leading-tight">System Announcements</p>
              </div>

            </div>
          </div>

          <!-- CARD 2: IMPORTANT ANNOUNCEMENTS -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Important Announcements</h3>
              <button
                @click="selectedSort = 'important'"
                class="text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 font-bold text-xs transition-colors cursor-pointer"
              >
                View All
              </button>
            </div>

            <div class="space-y-3 text-xs">
              <div
                v-for="item in importantList"
                :key="item.id"
                @click="openAnnouncementDetail(item)"
                class="p-3 bg-slate-50 hover:bg-slate-100 dark:bg-slate-900/60 dark:hover:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-1 cursor-pointer transition-all hover:border-purple-500/40 group shadow-xs"
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="text-purple-600 dark:text-purple-400">
                      {{ item.id === 1 ? '📢' : item.id === 2 ? '🎓' : '📅' }}
                    </span>
                    <p class="font-bold text-slate-900 dark:text-white truncate text-xs group-hover:text-purple-600 dark:group-hover:text-purple-300">
                      {{ item.title }}
                    </p>
                  </div>
                  <span
                    v-if="item.isNew"
                    class="px-1.5 py-0.2 rounded bg-purple-600 text-[9px] font-bold text-white shrink-0 shadow-xs"
                  >
                    New
                  </span>
                </div>

                <div class="flex items-center justify-between text-[10px] text-slate-500 font-mono">
                  <span class="truncate">{{ item.source }}</span>
                  <span class="shrink-0">{{ item.date }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- CARD 3: ANNOUNCEMENT CATEGORIES -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Announcement Categories</h3>
              <button
                @click="selectedCategory = 'All Categories'"
                class="text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 font-bold text-xs transition-colors cursor-pointer"
              >
                View All
              </button>
            </div>

            <div class="space-y-2 text-xs">
              <div
                v-for="cat in [
                  { name: 'General', count: categoryCounts['General'], icon: '📢', color: 'text-purple-600 dark:text-purple-400' },
                  { name: 'Academic', count: categoryCounts['Academic'], icon: '🎓', color: 'text-blue-600 dark:text-blue-400' },
                  { name: 'Course Updates', count: categoryCounts['Course Updates'], icon: '📑', color: 'text-emerald-600 dark:text-emerald-400' },
                  { name: 'Assignments', count: categoryCounts['Assignments'], icon: '📝', color: 'text-amber-600 dark:text-amber-400' },
                  { name: 'Exams', count: categoryCounts['Exams'], icon: '📋', color: 'text-rose-600 dark:text-rose-400' },
                ]"
                :key="cat.name"
                @click="filterByCategory(cat.name)"
                :class="[
                  selectedCategory === cat.name
                    ? 'bg-purple-50 dark:bg-purple-600/20 border-purple-500/40 text-purple-900 dark:text-white font-bold'
                    : 'bg-slate-50 dark:bg-slate-900/60 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-700 hover:text-slate-950 dark:hover:text-white',
                  'flex items-center justify-between p-2.5 rounded-xl border transition-all cursor-pointer shadow-xs'
                ]"
              >
                <div class="flex items-center gap-2">
                  <span :class="cat.color">{{ cat.icon }}</span>
                  <span class="font-semibold text-xs">{{ cat.name }}</span>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-950 font-bold font-mono text-[10px] text-slate-700 dark:text-slate-400">
                  {{ cat.count }}
                </span>
              </div>
            </div>
          </div>

          <!-- CARD 4: NOTIFICATION PREFERENCES -->
          <div class="bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/60 dark:from-[#12142E] dark:via-[#0F172A] dark:to-[#1F1138] border border-purple-200 dark:border-purple-900/40 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3.5 text-xs">
            <div class="space-y-1">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">Notification Preferences</h3>
              <p class="text-[11px] text-slate-600 dark:text-slate-400">Manage how you receive announcements and notifications.</p>
            </div>

            <button
              @click="isPreferencesModalOpen = true"
              class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-950/20 flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-95"
            >
              <span>⚙</span>
              <span>Manage Preferences</span>
            </button>
          </div>

        </div>

      </div>

    </div>

    <!-- ================= MODAL 1: ANNOUNCEMENT DETAIL MODAL ================= -->
    <div
      v-if="isDetailModalOpen && selectedAnnouncement"
      class="fixed inset-0 z-50 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-2xl w-full p-6 sm:p-7 space-y-5 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-slate-100 dark:border-slate-800 pb-4 gap-3">
          <div class="space-y-1 min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span
                :class="[
                  selectedAnnouncement.sourceType === 'teacher' ? 'bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-500/30' : 'bg-purple-500/10 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border-purple-500/30',
                  'px-2.5 py-0.5 rounded-full border text-[10px] font-bold'
                ]"
              >
                {{ selectedAnnouncement.source }}
              </span>
              <span class="px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-[10px] font-medium">
                {{ selectedAnnouncement.category }}
              </span>
              <span v-if="selectedAnnouncement.isPinned" class="text-rose-500 dark:text-rose-400 text-xs font-semibold">📌 Pinned</span>
            </div>

            <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-snug pt-1">
              {{ selectedAnnouncement.title }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">
              Published on {{ selectedAnnouncement.date }} <span v-if="selectedAnnouncement.time">• {{ selectedAnnouncement.time }}</span>
            </p>
          </div>

          <button
            @click="isDetailModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-sm shrink-0 cursor-pointer transition-colors"
          >
            ✕
          </button>
        </div>

        <!-- Teacher / Author Info if applicable -->
        <div
          v-if="selectedAnnouncement.teacherName"
          class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs"
        >
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-600/10 dark:bg-emerald-600/20 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">
              👨‍🏫
            </div>
            <div>
              <p class="font-bold text-slate-900 dark:text-white">{{ selectedAnnouncement.teacherName }}</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ selectedAnnouncement.courseName }}</p>
            </div>
          </div>
          <span class="px-2.5 py-0.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-[10px] text-slate-700 dark:text-slate-300 font-medium">
            Course Instructor
          </span>
        </div>

        <!-- Full Content Body -->
        <div class="space-y-3 text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-normal bg-slate-50 dark:bg-slate-950/40 p-4 rounded-2xl border border-slate-200 dark:border-slate-800/60">
          <p>{{ selectedAnnouncement.content || selectedAnnouncement.description }}</p>
        </div>

        <!-- Attachments Section if available -->
        <div v-if="selectedAnnouncement.attachments && selectedAnnouncement.attachments.length > 0" class="space-y-2 text-xs">
          <h4 class="font-bold text-slate-900 dark:text-white text-xs flex items-center gap-1.5">
            <span>📎</span>
            <span>Attachments ({{ selectedAnnouncement.attachments.length }})</span>
          </h4>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div
              v-for="att in selectedAnnouncement.attachments"
              :key="att.name"
              @click="downloadAttachment(att)"
              class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:border-purple-500/40 flex items-center justify-between transition-colors cursor-pointer group shadow-xs"
            >
              <div class="flex items-center gap-2 min-w-0">
                <span class="text-purple-600 dark:text-purple-400 text-sm">📄</span>
                <div class="min-w-0">
                  <p class="font-bold text-slate-900 dark:text-white truncate text-[11px] group-hover:text-purple-600 dark:group-hover:text-purple-300">{{ att.name }}</p>
                  <p class="text-[9.5px] text-slate-500 font-mono">{{ att.size }} • {{ att.type }}</p>
                </div>
              </div>
              <span class="text-xs text-slate-500 dark:text-slate-400 group-hover:text-purple-600 dark:group-hover:text-purple-400">⤓</span>
            </div>
          </div>
        </div>

        <!-- Modal Footer Actions (Phase 4 Cross-Module Linking) -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
          <button
            @click="toggleReadStatus(selectedAnnouncement)"
            class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold flex items-center gap-1.5 cursor-pointer transition-colors"
          >
            <span>{{ selectedAnnouncement.isUnread ? '✓ Mark as Read' : '✉ Mark as Unread' }}</span>
          </button>

          <div class="flex items-center gap-2">
            <Link
              v-if="selectedAnnouncement.actionRoute"
              :href="selectedAnnouncement.actionRoute"
              class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center gap-1.5 cursor-pointer"
            >
              <span>{{ selectedAnnouncement.actionLabel || 'View Details' }}</span>
              <span>→</span>
            </Link>

            <button
              @click="isDetailModalOpen = false"
              class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:text-white font-bold cursor-pointer transition-colors"
            >
              Close
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ================= MODAL 2: NOTIFICATION PREFERENCES MODAL ================= -->
    <div
      v-if="isPreferencesModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
          <div>
            <h3 class="text-base font-black text-slate-900 dark:text-white">Notification Preferences</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Choose channels and types of announcements</p>
          </div>
          <button
            @click="isPreferencesModalOpen = false"
            class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs cursor-pointer"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
            <div>
              <p class="font-bold text-slate-900 dark:text-white">Email Notifications</p>
              <p class="text-[10px] text-slate-500 dark:text-slate-400">Send copies to student email</p>
            </div>
            <input v-model="preferences.email" type="checkbox" class="w-4 h-4 accent-purple-600 rounded cursor-pointer" />
          </div>

          <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
            <div>
              <p class="font-bold text-slate-900 dark:text-white">Push &amp; In-App Notifications</p>
              <p class="text-[10px] text-slate-500 dark:text-slate-400">Real-time alerts on platform</p>
            </div>
            <input v-model="preferences.push" type="checkbox" class="w-4 h-4 accent-purple-600 rounded cursor-pointer" />
          </div>

          <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
            <div>
              <p class="font-bold text-slate-900 dark:text-white">System Announcements</p>
              <p class="text-[10px] text-slate-500 dark:text-slate-400">University-wide notices</p>
            </div>
            <input v-model="preferences.systemAlerts" type="checkbox" class="w-4 h-4 accent-purple-600 rounded cursor-pointer" />
          </div>

          <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
            <div>
              <p class="font-bold text-slate-900 dark:text-white">Teacher &amp; Course Alerts</p>
              <p class="text-[10px] text-slate-500 dark:text-slate-400">Instructor messages &amp; assignments</p>
            </div>
            <input v-model="preferences.teacherAlerts" type="checkbox" class="w-4 h-4 accent-purple-600 rounded cursor-pointer" />
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
          <button
            @click="isPreferencesModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="savePreferences"
            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md cursor-pointer"
          >
            Save Preferences
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 3: MARK ALL AS READ CONFIRMATION ================= -->
    <div
      v-if="isMarkAllConfirmModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-sm w-full p-6 space-y-4 shadow-2xl text-center">
        <div class="w-12 h-12 rounded-full bg-purple-600/10 dark:bg-purple-600/20 text-purple-600 dark:text-purple-400 flex items-center justify-center text-2xl mx-auto">
          ✓✓
        </div>
        <div class="space-y-1">
          <h3 class="text-base font-black text-slate-900 dark:text-white">Mark All as Read?</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            This will mark all {{ summaryStats.unread }} unread announcements as read.
          </p>
        </div>
        <div class="flex items-center justify-center gap-2 pt-2 text-xs">
          <button
            @click="isMarkAllConfirmModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold cursor-pointer hover:bg-slate-200"
          >
            Cancel
          </button>
          <button
            @click="confirmMarkAllAsRead"
            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md cursor-pointer"
          >
            Confirm
          </button>
        </div>
      </div>
    </div>

    <!-- ================= TOAST NOTIFICATION ================= -->
    <div
      v-if="toastMessage"
      class="fixed bottom-6 right-6 z-50 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-2xl border border-purple-500/50 flex items-center gap-2.5 text-xs font-bold animate-in slide-in-from-bottom-4 duration-200"
    >
      <span class="w-5 h-5 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center text-xs">✓</span>
      <span>{{ toastMessage }}</span>
    </div>

  </StudentLayout>
</template>

