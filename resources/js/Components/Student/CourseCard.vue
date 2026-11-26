<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  Play,
  CheckCircle2,
  Check,
  Clock,
  BookOpen,
  User,
  ArrowRight,
  GraduationCap,
  Eye,
  Bookmark
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
const isBookmarked = ref(false)

const toggleBookmark = () => {
  isBookmarked.value = !isBookmarked.value
}

const isKm = computed(() => props.locale === 'km')

const displayTitle = computed(() => {
  if (isKm.value && props.course.title_kh) {
    return props.course.title_kh
  }
  return props.course.title
})

const displayMajor = computed(() => {
  const kh = props.course.major_kh
  const en = props.course.major
  if (kh && en && kh !== en) {
    return `${kh} (${en})`
  }
  return isKm.value ? (kh || en || 'ការសិក្សាទូទៅ') : (en || kh || 'General Studies')
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
      type: 'completed',
      label: isKm.value ? 'បានបញ្ចប់' : 'Completed',
      bgClass: 'bg-emerald-500 text-white',
    }
  }
  if (isInProgress.value) {
    return {
      type: 'in_progress',
      label: isKm.value ? 'កំពុងសិក្សា' : 'In Progress',
      bgClass: 'bg-emerald-500 text-white',
    }
  }
  return {
    type: 'not_started',
    label: isKm.value ? 'មិនទាន់ចាប់ផ្តើម' : 'Not Started',
    bgClass: 'bg-slate-800/90 text-white',
  }
})

// Dynamic fallback thumbnail
const fallbackThumb = computed(() => {
  const titleLower = (props.course.title || '').toLowerCase()
  if (titleLower.includes('web') || titleLower.includes('vue') || titleLower.includes('laravel') || titleLower.includes('code')) {
    return '/images/courses/default-coding.png'
  }
  return '/images/courses/default-study-book.png'
})
</script>

<template>
  <div class="group bg-white dark:bg-[#1e1f20] rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.04)] hover:shadow-[0_12px_28px_-6px_rgba(112,69,245,0.14)] transition-all duration-300 hover:-translate-y-1 flex flex-col overflow-hidden relative">
    <!-- Top Cover Media (16:9) -->
    <div class="aspect-video w-full relative overflow-hidden bg-slate-900 select-none">
      <img
        v-if="course.thumbnail && !imageLoadError"
        :src="course.thumbnail"
        :alt="displayTitle"
        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
        @error="imageLoadError = true"
        loading="lazy"
      />
      <img
        v-else
        :src="fallbackThumb"
        :alt="displayTitle"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
      />

      <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-black/20 pointer-events-none"></div>

      <!-- Top Badges Overlay -->
      <div class="absolute top-3 inset-x-3 flex items-center justify-between gap-2 z-10">
        <!-- Status Pill -->
        <span
          :class="[
            'px-3 py-1 rounded-full text-xs font-bold shadow-md flex items-center gap-1.5 shrink-0',
            statusBadge.bgClass
          ]"
        >
          <template v-if="statusBadge.type === 'completed'">
            <Check class="w-3.5 h-3.5 stroke-[3]" />
          </template>
          <template v-else-if="statusBadge.type === 'in_progress'">
            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
          </template>
          <span>{{ statusBadge.label }}</span>
        </span>

        <!-- Bookmark Button -->
        <button
          type="button"
          @click.stop.prevent="toggleBookmark"
          class="w-8 h-8 rounded-full bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-white/40 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:text-[#7045F5] flex items-center justify-center shadow-xs hover:scale-105 active:scale-95 transition-all cursor-pointer"
          :aria-label="isBookmarked ? 'Remove bookmark' : 'Add bookmark'"
        >
          <Bookmark :class="['w-4 h-4 transition-colors', isBookmarked ? 'fill-[#7045F5] text-[#7045F5]' : '']" />
        </button>
      </div>

      <!-- Hover Play Button Overlay -->
      <Link
        :href="course.continue_url"
        class="absolute inset-0 z-20 flex items-center justify-center bg-black/25 backdrop-blur-[1px] opacity-0 group-hover:opacity-100 transition-opacity duration-300"
      >
        <div class="w-12 h-12 rounded-full bg-[#7045F5] text-white flex items-center justify-center shadow-lg shadow-[#7045F5]/40 transform scale-75 group-hover:scale-100 transition-transform duration-300">
          <Play class="w-5 h-5 fill-current translate-x-0.5" />
        </div>
      </Link>
    </div>

    <!-- Card Body Content -->
    <div class="p-5 flex-1 flex flex-col justify-between space-y-3.5">
      <div class="space-y-2">
        <!-- Course Title -->
        <h3 class="font-extrabold text-slate-900 dark:text-white text-base sm:text-lg line-clamp-1 leading-snug group-hover:text-[#7045F5] dark:group-hover:text-[#a78bfa] transition-colors">
          <Link :href="course.overview_url">
            {{ displayTitle }}
          </Link>
        </h3>

        <!-- Major Row -->
        <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
          <GraduationCap class="w-4 h-4 text-slate-400 shrink-0" />
          <span class="truncate font-medium">{{ displayMajor }}</span>
        </div>

        <!-- Teacher Row -->
        <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
          <User class="w-4 h-4 text-slate-400 shrink-0" />
          <span class="truncate font-medium">{{ isKm ? 'គ្រូបង្រៀន:' : 'Teacher:' }} {{ displayTeacher }}</span>
        </div>
      </div>

      <!-- Progress Section -->
      <div class="space-y-1.5 pt-1">
        <div class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-300">
          <span>{{ isKm ? 'វឌ្ឍនភាព' : 'Progress' }}</span>
          <span>{{ course.progress_percent }}%</span>
        </div>

        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-white/10 overflow-hidden">
          <div
            :class="[
              'h-full rounded-full transition-all duration-300',
              isCompleted ? 'bg-emerald-500' : 'bg-gradient-to-r from-[#7045F5] to-[#9333EA]'
            ]"
            :style="{ width: `${Math.min(100, Math.max(0, course.progress_percent))}%` }"
          ></div>
        </div>
      </div>

      <!-- Metadata Row: Lessons count & Last accessed -->
      <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-1">
        <div class="flex items-center gap-1.5 truncate">
          <Play class="w-3.5 h-3.5 text-slate-400 fill-slate-400 shrink-0" />
          <span class="font-medium text-slate-700 dark:text-slate-300">
            {{ course.lessons_completed }} / {{ course.lessons_total }} {{ isKm ? 'មេរៀន' : 'Lessons' }}
          </span>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
          <template v-if="isCompleted">
            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
            <span class="font-bold text-emerald-600 dark:text-emerald-400">
              {{ isKm ? 'បានបញ្ចប់' : 'Completed' }}
            </span>
          </template>
          <template v-else>
            <Clock class="w-3.5 h-3.5 text-slate-400 shrink-0" />
            <span class="truncate">
              {{ isKm ? 'ចូលរៀនលើកចុងក្រោយ:' : 'Last accessed:' }} {{ course.last_accessed || (isKm ? 'ថ្មីៗនេះ' : 'Recently') }}
            </span>
          </template>
        </div>
      </div>

      <!-- Action Button Footer -->
      <div class="pt-2">
        <!-- Completed Course: Review Course -->
        <template v-if="isCompleted">
          <Link
            :href="course.overview_url"
            class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-white dark:bg-slate-900 border-2 border-[#7045F5]/30 hover:border-[#7045F5] text-[#7045F5] dark:text-[#a78bfa] hover:bg-[#7045F5]/5 font-bold text-xs sm:text-sm shadow-xs transition-all duration-200"
          >
            <Eye class="w-4 h-4 shrink-0" />
            <span>{{ isKm ? 'ពិនិត្យវគ្គសិក្សាឡើងវិញ' : 'Review Course' }}</span>
          </Link>
        </template>

        <!-- In Progress or Not Started: Continue Learning Button -->
        <template v-else>
          <Link
            :href="course.continue_url"
            class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-[#7045F5] hover:bg-[#5f35e0] text-white font-bold text-xs sm:text-sm shadow-md shadow-[#7045F5]/25 hover:shadow-lg hover:shadow-[#7045F5]/35 active:scale-[0.99] transition-all duration-200"
          >
            <Play class="w-4 h-4 fill-white shrink-0" />
            <span>{{ isKm ? 'បន្តការសិក្សា' : 'Continue Learning' }}</span>
          </Link>
        </template>
      </div>
    </div>
  </div>
</template>
