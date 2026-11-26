<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import CourseProgress from '@/Components/Student/CourseProgress.vue'
import {
  Play,
  CheckCircle2,
  Clock,
  BookOpen,
  User,
  ArrowRight,
  Sparkles,
  RotateCcw,
  Eye,
  Layers,
  Code,
  Compass,
  Sprout,
  Users2
} from 'lucide-vue-next'

export interface EnrolledCourseDashboardItem {
  id: number
  enrollment_id?: number
  title: string
  title_kh?: string
  description?: string
  thumbnail?: string | null
  major_id?: number | null
  major: string
  major_kh?: string
  teacher: {
    name: string
    name_kh?: string
    avatar?: string | null
  }
  lessons_total: number
  lessons_completed: number
  progress_percent: number
  status: 'in_progress' | 'not_started' | 'completed' | 'coming_soon'
  status_label?: string
  last_accessed?: string | null
  last_accessed_ms?: number
  continue_url: string
  overview_url: string
  current_lesson?: {
    id: number | string
    title: string
    title_kh?: string
  } | null
}

const props = withDefaults(defineProps<{
  course: EnrolledCourseDashboardItem
  locale?: string
}>(), {
  locale: 'km',
})

const imageLoadError = ref(false)

const isKm = computed(() => props.locale === 'km')
const displayTitle = computed(() => {
  if (isKm.value && props.course.title_kh) {
    return props.course.title_kh
  }
  return props.course.title
})

const displayMajor = computed(() => {
  if (isKm.value && props.course.major_kh) {
    return props.course.major_kh
  }
  return props.course.major || (isKm.value ? 'ការសិក្សាទូទៅ' : 'General Studies')
})

const displayTeacher = computed(() => {
  if (isKm.value && props.course.teacher?.name_kh) {
    return props.course.teacher.name_kh
  }
  return props.course.teacher?.name || (isKm.value ? 'សាស្ត្រាចារ្យ SPI' : 'SPI Instructor')
})

const isCompleted = computed(() => {
  return props.course.status === 'completed' || props.course.progress_percent >= 100
})

const isInProgress = computed(() => {
  return props.course.status === 'in_progress' || (props.course.progress_percent > 0 && props.course.progress_percent < 100)
})

const statusBadge = computed(() => {
  if (isCompleted.value) {
    return {
      label: isKm.value ? 'បានបញ្ចប់' : 'Completed',
      bgClass: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/20',
      dotClass: 'bg-emerald-500',
    }
  }
  if (isInProgress.value) {
    return {
      label: isKm.value ? 'កំពុងរៀន' : 'In Progress',
      bgClass: 'bg-[#2563EB]/10 text-[#2563EB] dark:text-blue-400 border-[#2563EB]/20',
      dotClass: 'bg-[#2563EB]',
    }
  }
  return {
    label: isKm.value ? 'មិនទាន់ចាប់ផ្តើម' : 'Not Started',
    bgClass: 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/20',
    dotClass: 'bg-amber-500',
  }
})

// Dynamic fallback gradient and icon for courses without thumbnail
const fallbackStyle = computed(() => {
  const hash = Math.abs(Number(props.course.id || 0))
  const gradients = [
    'from-indigo-600 via-[#7045F5] to-purple-800',
    'from-blue-600 via-indigo-700 to-[#7045F5]',
    'from-violet-700 via-purple-700 to-indigo-900',
    'from-slate-800 via-indigo-900 to-[#7045F5]',
  ]
  return gradients[hash % gradients.length]
})

const teacherInitials = computed(() => {
  const name = props.course.teacher?.name || 'SPI'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
})
</script>

<template>
  <div class="group bg-white dark:bg-[#1e1f20] rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.04)] hover:shadow-[0_12px_28px_-6px_rgba(112,69,245,0.12)] transition-all duration-300 hover:-translate-y-1 flex flex-col overflow-hidden relative">
    <!-- Top Cover Media (16:9) -->
    <div class="aspect-video w-full relative overflow-hidden bg-slate-100 dark:bg-slate-800 select-none">
      <template v-if="course.thumbnail && !imageLoadError">
        <img
          :src="course.thumbnail"
          :alt="displayTitle"
          class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
          @error="imageLoadError = true"
          loading="lazy"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent pointer-events-none"></div>
      </template>
      <div
        v-else
        :class="['w-full h-full bg-gradient-to-br flex items-center justify-center p-6 text-white relative overflow-hidden', fallbackStyle]"
      >
        <!-- Subtle decorative shapes and glow -->
        <div class="absolute -top-12 -right-12 w-36 h-36 rounded-full bg-white/10 blur-xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-36 h-36 rounded-full bg-black/20 blur-xl pointer-events-none"></div>
        <div class="absolute inset-0 opacity-15 bg-[radial-gradient(circle_at_center,white_1px,transparent_1px)] bg-[size:16px_16px]"></div>

        <div class="relative z-10 flex flex-col items-center justify-center text-center px-4">
          <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 mb-2 shadow-lg shadow-black/10 group-hover:scale-110 transition-transform duration-300">
            <BookOpen class="w-7 h-7 text-white" />
          </div>
          <span class="text-xs font-bold text-white/95 tracking-wide drop-shadow-sm line-clamp-1">
            {{ displayTitle }}
          </span>
        </div>
      </div>

      <!-- Top Badges Overlay -->
      <div class="absolute top-3 inset-x-3 flex items-center justify-between gap-2 z-10 pointer-events-none">
        <!-- Major / Subject Pill -->
        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/90 dark:bg-slate-900/90 text-slate-800 dark:text-slate-200 backdrop-blur-md border border-white/30 dark:border-slate-700/50 shadow-xs max-w-[60%] truncate">
          {{ displayMajor }}
        </span>

        <!-- Status Pill -->
        <span :class="['px-2.5 py-1 rounded-full text-[11px] font-bold backdrop-blur-md border shadow-xs flex items-center gap-1.5 shrink-0 bg-white/95 dark:bg-slate-900/95', statusBadge.bgClass]">
          <span :class="['w-1.5 h-1.5 rounded-full animate-pulse', statusBadge.dotClass]"></span>
          {{ statusBadge.label }}
        </span>
      </div>

      <!-- Quick Play Overlay on Hover -->
      <Link
        :href="course.continue_url"
        class="absolute inset-0 z-20 flex items-center justify-center bg-black/30 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-opacity duration-300"
        :aria-label="isCompleted ? (isKm ? 'មើលវគ្គសិក្សា' : 'View Course') : (isKm ? 'រៀនបន្ត' : 'Continue Learning')"
      >
        <div class="w-12 h-12 rounded-full bg-[#7045F5] text-white flex items-center justify-center shadow-lg shadow-[#7045F5]/40 transform scale-75 group-hover:scale-100 transition-transform duration-300">
          <Play class="w-5 h-5 fill-current translate-x-0.5" />
        </div>
      </Link>
    </div>

    <!-- Card Body Content -->
    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
      <div class="space-y-2.5">
        <!-- Course Title -->
        <h3 class="font-bold text-slate-900 dark:text-white text-base sm:text-lg line-clamp-2 leading-[1.7] group-hover:text-[#7045F5] dark:group-hover:text-[#9b7dfa] transition-colors">
          <Link :href="course.overview_url">
            {{ displayTitle }}
          </Link>
        </h3>

        <!-- Teacher Row -->
        <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
          <div class="w-6 h-6 rounded-full overflow-hidden bg-purple-100 dark:bg-purple-900/40 border border-purple-200/50 dark:border-purple-700/50 flex items-center justify-center shrink-0">
            <img
              v-if="course.teacher?.avatar"
              :src="course.teacher.avatar"
              :alt="displayTeacher"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-[10px] font-bold text-[#7045F5] dark:text-[#a78bfa]">
              {{ teacherInitials }}
            </span>
          </div>
          <span class="truncate font-medium">{{ displayTeacher }}</span>
        </div>

        <!-- Course Short Description -->
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
          {{ course.description }}
        </p>
      </div>

      <!-- Progress Section -->
      <div class="space-y-3 pt-2">
        <!-- Reusable CourseProgress Component -->
        <CourseProgress
          :progress="course.progress_percent"
          :completed-lessons="course.lessons_completed"
          :total-lessons="course.lessons_total"
          :show-count="true"
          :show-label="false"
          :locale="locale"
          size="md"
        />

        <!-- Last Accessed Info -->
        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1">
          <div class="flex items-center gap-1.5 truncate">
            <Clock class="w-3.5 h-3.5 text-slate-400 shrink-0" />
            <span class="truncate">
              <template v-if="course.last_accessed">
                {{ isKm ? 'ចូលរៀនចុងក្រោយ:' : 'Last accessed:' }} {{ course.last_accessed }}
              </template>
              <template v-else>
                {{ isKm ? 'មិនទាន់បានចូលរៀន' : 'Not accessed yet' }}
              </template>
            </span>
          </div>
        </div>
      </div>

      <!-- Action Button Footer -->
      <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-2">
        <!-- Completed Course: View Course / Review -->
        <template v-if="isCompleted">
          <Link
            :href="course.overview_url"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700/80 text-slate-800 dark:text-slate-200 font-bold text-xs sm:text-sm transition-colors duration-200"
          >
            <Eye class="w-4 h-4 text-slate-500" />
            <span>{{ isKm ? 'មើលវគ្គសិក្សា' : 'View Course' }}</span>
          </Link>
        </template>

        <!-- In Progress or Not Started: Continue Learning Button -->
        <template v-else>
          <Link
            :href="course.continue_url"
            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-[#7045F5] to-[#5b32e6] hover:from-[#5f35e0] hover:to-[#4e27cc] text-white font-bold text-xs sm:text-sm shadow-md shadow-[#7045F5]/20 hover:shadow-lg hover:shadow-[#7045F5]/30 active:scale-[0.99] transition-all duration-200"
          >
            <Play class="w-4 h-4 fill-current shrink-0" />
            <span>{{ isKm ? 'រៀនបន្ត' : 'Continue Learning' }}</span>
            <ArrowRight class="w-3.5 h-3.5 ml-auto opacity-70 group-hover:translate-x-0.5 transition-transform" />
          </Link>
        </template>
      </div>
    </div>
  </div>
</template>
