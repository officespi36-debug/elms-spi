<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = withDefaults(
  defineProps<{
    studentName: string
    academicTerm?: string
    academicTermKm?: string
    unreadAnnouncementsCount?: number
    currentLang?: 'km' | 'en'
    isLoading?: boolean
    hasStudiedBefore?: boolean
    browseCoursesRoute?: string
    announcementsRoute?: string
    onBrowseCourses?: () => void
    onOpenAnnouncements?: () => void
  }>(),
  {
    academicTerm: '',
    academicTermKm: '',
    unreadAnnouncementsCount: 0,
    currentLang: 'en',
    isLoading: false,
    hasStudiedBefore: false,
    browseCoursesRoute: '/student/browse',
    announcementsRoute: '/student/notifications/announcements',
    onBrowseCourses: undefined,
    onOpenAnnouncements: undefined,
  }
)

const emit = defineEmits<{
  (e: 'browse-courses'): void
  (e: 'open-announcements'): void
}>()

const displayTerm = computed(() => {
  const rawTerm = props.currentLang === 'km'
    ? (props.academicTermKm || props.academicTerm || '')
    : (props.academicTerm || props.academicTermKm || '')
  return rawTerm ? `SPI Student Portal • ${rawTerm}` : 'SPI Student Portal'
})

const greetingText = computed(() => {
  const safeName = props.studentName?.trim() || (props.currentLang === 'km' ? 'និស្សិត' : 'Student')
  if (props.hasStudiedBefore) {
    return props.currentLang === 'km'
      ? `សូមស្វាគមន៍ការត្រឡប់មកវិញ, ${safeName}!`
      : `Welcome back, ${safeName}!`
  }
  return props.currentLang === 'km'
    ? `សូមស្វាគមន៍, ${safeName}!`
    : `Welcome, ${safeName}!`
})

const subtitleText = computed(() => {
  if (props.hasStudiedBefore) {
    return props.currentLang === 'km'
      ? 'បន្តការសិក្សាពីកន្លែងដែលអ្នកបានឈប់ ឬស្វែងរកវគ្គសិក្សាថ្មីៗនៅថ្ងៃនេះ។'
      : 'Continue where you left off or explore new courses today.'
  }
  return props.currentLang === 'km'
    ? 'សូមចាប់ផ្តើមដំណើរការសិក្សារបស់អ្នក ដោយស្វែងរក និងចុះឈ្មោះវគ្គសិក្សាដំបូងរបស់អ្នកនៅថ្ងៃនេះ។'
    : 'Start your learning journey by exploring and enrolling in your first course today.'
})

const handleBrowseCourses = () => {
  if (props.onBrowseCourses) {
    props.onBrowseCourses()
    return
  }
  emit('browse-courses')
  router.visit(props.browseCoursesRoute)
}

const handleOpenAnnouncements = () => {
  if (props.onOpenAnnouncements) {
    props.onOpenAnnouncements()
    return
  }
  emit('open-announcements')
  router.visit(props.announcementsRoute)
}
</script>

<template>
  <div
    class="px-5 py-4 rounded-2xl border border-[#E2E8F0] dark:border-[#2d2f31] bg-gradient-to-r from-white via-indigo-50/40 to-purple-50/50 dark:from-[#1e1f20] dark:via-[#202331] dark:to-[#242031] shadow-xs transition-all"
    :class="currentLang === 'km' ? 'font-khmer' : 'font-sans'"
  >
    <!-- Compact Skeleton Loading State -->
    <div
      v-if="isLoading"
      class="flex flex-col md:flex-row md:items-center justify-between gap-4 animate-pulse"
      aria-busy="true"
      aria-label="Loading welcome banner"
    >
      <div class="space-y-2 flex-1 min-w-0">
        <div class="h-5 w-64 bg-indigo-100/80 dark:bg-indigo-900/30 rounded-full"></div>
        <div class="h-6 w-56 sm:w-72 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
        <div class="h-4 w-72 sm:w-96 bg-slate-100 dark:bg-slate-800 rounded"></div>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 shrink-0">
        <div class="h-9 w-36 bg-indigo-200/70 dark:bg-indigo-800/40 rounded-xl"></div>
        <div class="h-9 w-36 bg-slate-100 dark:bg-slate-800 rounded-xl"></div>
      </div>
    </div>

    <!-- Active Compact Welcome Banner -->
    <div v-else class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <!-- Left Content Stack (Tight & Clean) -->
      <div class="min-w-0 flex-1">
        <div
          class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-500/30 max-w-full"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
          <span class="truncate">{{ displayTerm }}</span>
        </div>

        <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-slate-100 mt-1.5 flex items-center gap-1.5 min-w-0">
          <span class="truncate max-w-[260px] sm:max-w-md">{{ greetingText }}</span>
          <span class="shrink-0" aria-hidden="true">✨</span>
        </h1>

        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">
          {{ subtitleText }}
        </p>
      </div>

      <!-- Right Action Group (Clear Visual Hierarchy) -->
      <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 shrink-0">
        <button
          @click="handleBrowseCourses"
          type="button"
          class="rounded-full px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-gradient-to-r from-[#2563EB] to-[#4F46E5] hover:from-[#1D4ED8] hover:to-[#4338CA] shadow-sm hover:shadow-md transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 active:scale-95 group select-none"
        >
          <svg class="w-4 h-4 shrink-0 group-hover:scale-110 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
          </svg>
          <span class="tracking-normal leading-normal">{{ currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា' : 'Browse Courses' }}</span>
        </button>

        <button
          @click="handleOpenAnnouncements"
          type="button"
          class="rounded-full px-4 py-2 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white/95 dark:bg-[#202225] border border-slate-200/90 dark:border-slate-700 shadow-xs hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-500/70 hover:bg-slate-50 dark:hover:bg-[#282a2d] transition-all duration-200 flex items-center justify-center gap-2 relative cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 active:scale-95 group select-none"
        >
          <div class="relative flex items-center justify-center">
            <svg class="w-4 h-4 shrink-0 text-amber-500 dark:text-amber-400 group-hover:rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
          </div>
          <span class="tracking-normal leading-normal">{{ currentLang === 'km' ? 'សេចក្តីប្រកាស' : 'Announcements' }}</span>
          <span
            v-if="unreadAnnouncementsCount > 0"
            class="relative flex h-2.5 w-2.5 shrink-0"
            :aria-label="`${unreadAnnouncementsCount} unread announcements`"
          >
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500 ring-2 ring-white dark:ring-[#202225]"></span>
          </span>
        </button>
      </div>
    </div>
  </div>
</template>
