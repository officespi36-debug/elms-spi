<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = withDefaults(
  defineProps<{
    studentName: string
    avatarUrl?: string | null
    studentId?: number | string
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
    avatarUrl: null,
    studentId: undefined,
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

const avatarImgError = ref(false)

const getInitials = (name?: string) => {
  if (!name) return 'S'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
}

const safeName = computed(() => {
  return props.studentName?.trim() || (props.currentLang === 'km' ? 'និស្សិត' : 'Student')
})

const greetingTitle = computed(() => {
  return props.currentLang === 'km'
    ? 'ស្វាគមន៍មកកាន់ SPI E-Learning,'
    : 'Welcome to SPI E-Learning,'
})

const motivationalText = computed(() => {
  return props.currentLang === 'km'
    ? 'បន្តការសិក្សារបស់អ្នក និងឈានឆ្ពោះទៅរកគោលដៅថ្ងៃនេះ!'
    : 'Continue your learning journey and achieve your goals today!'
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
    class="welcome-banner relative overflow-hidden rounded-2xl bg-white dark:bg-[#1e1f20] border border-[#E2E8F0] dark:border-[#2d2f31] shadow-xs transition-all"
    :class="currentLang === 'km' ? 'font-khmer' : 'font-sans'"
  >
    <!-- Background subtle gradient glow -->
    <div class="absolute inset-0 bg-gradient-to-r from-purple-50/40 via-white to-indigo-50/20 dark:from-[#202331]/30 dark:via-transparent dark:to-transparent pointer-events-none"></div>

    <!-- Loading Skeleton State -->
    <div
      v-if="isLoading"
      class="relative z-10 p-5 sm:p-6 flex items-center justify-between gap-6 animate-pulse"
      aria-busy="true"
    >
      <div class="flex items-center gap-4">
        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-slate-200 dark:bg-slate-700 shrink-0"></div>
        <div class="space-y-2.5">
          <div class="h-4 w-40 bg-slate-200 dark:bg-slate-700 rounded"></div>
          <div class="h-7 w-56 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
          <div class="h-3.5 w-64 bg-slate-100 dark:bg-slate-800 rounded"></div>
        </div>
      </div>
      <div class="hidden md:block w-72 h-24 bg-slate-100 dark:bg-slate-800 rounded-xl"></div>
    </div>

    <!-- Active Welcome Banner matching Reference Design -->
    <div v-else class="relative z-10 p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
      <!-- Left: Student Avatar + Greeting Text -->
      <div class="flex items-center gap-4 sm:gap-5 min-w-0">
        <!-- Student Avatar Circle (matching reference thick white ring & soft shadow) -->
        <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-full overflow-hidden shrink-0 ring-4 ring-white dark:ring-[#282a2c] shadow-md border border-slate-100 dark:border-white/10 bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center">
          <img
            v-if="avatarUrl && !avatarImgError"
            :src="avatarUrl"
            :alt="safeName"
            class="w-full h-full object-cover"
            @error="avatarImgError = true"
          />
          <span v-else class="text-white font-black text-xl sm:text-2xl tracking-wider select-none">
            {{ getInitials(safeName) }}
          </span>
          <span class="absolute bottom-1 right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-[#1e1f20]"></span>
        </div>

        <!-- Greeting Headings -->
        <div class="min-w-0 space-y-0.5">
          <p class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-300 leading-snug">
            {{ greetingTitle }}
          </p>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-1.5 leading-snug">
            <span class="truncate">{{ safeName }}!</span>
            <span class="shrink-0 text-xl sm:text-2xl" aria-hidden="true">👋</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium line-clamp-1 pt-0.5">
            {{ motivationalText }}
          </p>
        </div>
      </div>

      <!-- Right: SPI Institute Building Artwork (SVG illustration) -->
      <div class="hidden md:flex items-center justify-end shrink-0 max-w-[400px] pointer-events-none select-none pr-2">
        <img
          :src="'/images/dashboard/spi-building-banner.svg'"
          alt="Saint Paul Institute"
          class="w-72 lg:w-84 xl:w-96 h-auto max-h-24 object-contain drop-shadow-sm opacity-95 transition-opacity duration-300"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.welcome-banner {
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
}
</style>
