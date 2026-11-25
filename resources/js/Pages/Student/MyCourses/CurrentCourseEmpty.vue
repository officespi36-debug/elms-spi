<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'
import { ref } from 'vue'

type ContinueState = 'A' | 'B' | 'C' | 'D' | 'E'

interface RecommendedCourse {
  id: number
  title: string
  titleKh?: string
  code?: string
  thumbnail?: string | null
  category?: string | null
  categoryKh?: string | null
  teacherName?: string | null
  teacherNameKh?: string | null
  lessonsCount: number
  durationSeconds?: number
  durationLabel?: string | null
  isFree: boolean
  priceDisplay: string
  overviewHref: string
  enrollHref: string
  checkoutHref: string
  level?: string | null
}

interface StartCourse {
  id: number
  title: string
  titleKh?: string
  thumbnail?: string | null
  continueHref: string
  lessonsCount: number
}

const props = withDefaults(defineProps<{
  state: ContinueState
  hasEnrollments?: boolean
  startCourse?: StartCourse | null
  recommendedCourses?: RecommendedCourse[]
}>(), {
  hasEnrollments: false,
  startCourse: null,
  recommendedCourses: () => [],
})

const { t, currentLang } = useLanguage()
const enrollingId = ref<number | null>(null)
const isNavigating = ref(false)
const thumbErrors = ref<Record<number, boolean>>({})

const formatDuration = (seconds?: number, fallbackLabel?: string | null) => {
  const sec = Number(seconds || 0)
  if (!sec || sec <= 0) return fallbackLabel || ''
  const totalMinutes = Math.round(sec / 60)
  if (totalMinutes <= 0) return ''
  const hours = Math.floor(totalMinutes / 60)
  const mins = totalMinutes % 60
  if (currentLang.value === 'km') {
    return hours > 0 ? `${hours} ម៉ោង ${String(mins).padStart(2, '0')} នាទី` : `${mins} នាទី`
  }
  return hours > 0 ? `${hours}h ${String(mins).padStart(2, '0')}m` : `${mins}m`
}

const categoryIconType = (category?: string | null) => {
  const cat = (category || '').toLowerCase()
  if (cat.includes('it') || cat.includes('program') || cat.includes('web') || cat.includes('បច្ចេកវិទ្យា')) return 'code'
  if (cat.includes('tour') || cat.includes('hosp') || cat.includes('ទេសចរណ៍')) return 'compass'
  if (cat.includes('agr') || cat.includes('plant') || cat.includes('កសិកម្ម')) return 'leaf'
  if (cat.includes('social') || cat.includes('សង្គម')) return 'users'
  return 'book'
}

// Soft palette for the category fallback thumbnail (no waves).
const FALLBACK_PALETTE = [
  { bg: 'bg-blue-50 dark:bg-blue-900/20', ring: 'text-blue-600 dark:text-blue-300', soft: 'bg-blue-100/70 dark:bg-blue-800/40' },
  { bg: 'bg-purple-50 dark:bg-purple-900/20', ring: 'text-purple-600 dark:text-purple-300', soft: 'bg-purple-100/70 dark:bg-purple-800/40' },
  { bg: 'bg-emerald-50 dark:bg-emerald-900/20', ring: 'text-emerald-600 dark:text-emerald-300', soft: 'bg-emerald-100/70 dark:bg-emerald-800/40' },
  { bg: 'bg-amber-50 dark:bg-amber-900/20', ring: 'text-amber-600 dark:text-amber-300', soft: 'bg-amber-100/70 dark:bg-amber-800/40' },
  { bg: 'bg-rose-50 dark:bg-rose-900/20', ring: 'text-rose-600 dark:text-rose-300', soft: 'bg-rose-100/70 dark:bg-rose-800/40' },
]

const paletteFor = (id: number) => FALLBACK_PALETTE[Math.abs(Number(id) || 0) % FALLBACK_PALETTE.length]

const localizedTitle = (c: { title: string; titleKh?: string }) =>
  currentLang.value === 'km' ? (c.titleKh || c.title) : c.title

const handleEnroll = (course: RecommendedCourse) => {
  if (enrollingId.value === course.id) return
  if (!course.isFree) {
    router.visit(course.checkoutHref)
    return
  }
  enrollingId.value = course.id
  router.post(course.enrollHref, {}, {
    onFinish: () => {
      enrollingId.value = null
    },
  })
}
</script>

<template>
  <Head :title="t('រៀនបន្ត', 'Continue Learning')" />

  <StudentLayout>
    <div class="w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24 space-y-8">
      <!-- Page Header -->
      <div class="flex flex-col gap-3 border-b border-slate-200 dark:border-slate-800/80 pb-5">
        <div class="flex items-center gap-3">
          <!-- 64px play icon inside a soft circle -->
          <div class="relative shrink-0 w-16 h-16 rounded-full bg-purple-50 dark:bg-purple-900/25 border border-purple-100 dark:border-purple-700/40 flex items-center justify-center">
            <svg class="w-8 h-8 text-purple-600 dark:text-purple-300 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M8 5.14v14l11-7-11-7z" />
            </svg>
          </div>
          <div class="space-y-0.5 min-w-0">
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight truncate">
              {{ t('រៀនបន្ត', 'Continue Learning') }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 truncate">
              {{ t('បន្តមេរៀនចុងក្រោយរបស់អ្នកយ៉ាងឆាប់រហ័សពីកន្លែងដែលអ្នកបានផ្អាក។', 'Pick up right where you left off in your active courses.') }}
            </p>
          </div>
        </div>
      </div>

      <!-- State B: Start first lesson -->
      <section v-if="state === 'B' && startCourse" class="space-y-4">
        <div class="bg-white dark:bg-[#1e1f20] border border-slate-200 dark:border-[#2d2f31] rounded-3xl p-6 sm:p-10 shadow-sm relative overflow-hidden">
          <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(124,58,237,0.06),transparent_65%)] dark:bg-[radial-gradient(circle_at_top,rgba(139,92,246,0.12),transparent_65%)] pointer-events-none"></div>
          <div class="relative z-10 max-w-2xl space-y-5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200/70 dark:border-emerald-700/40 text-emerald-700 dark:text-emerald-300 text-[11px] font-bold">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              {{ t('រួចរាល់ក្នុងការចាប់ផ្តើម', 'Ready to start') }}
            </div>
            <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white">
              {{ t('ចាប់ផ្តើមមេរៀនដំបូងរបស់អ្នក', 'Start your first lesson') }}
            </h2>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              {{ t('អ្នកបានចុះឈ្មោះចូលរៀនវគ្គសិក្សានេះរួចហើយ។ ចុចប៊ូតុងខាងក្រោមដើម្បីចាប់ផ្តើមមេរៀនដំបូង។', 'You have enrolled in this course. Click below to begin your first lesson.') }}
            </p>

            <div class="flex flex-col sm:flex-row sm:items-center gap-4 bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-4">
              <div class="w-24 h-16 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 shrink-0">
                <img v-if="startCourse.thumbnail" :src="startCourse.thumbnail" :alt="localizedTitle(startCourse)" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                  <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="font-bold text-slate-900 dark:text-white truncate">{{ localizedTitle(startCourse) }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ startCourse.lessonsCount }} {{ t('មេរៀន', 'lessons') }}</p>
              </div>
              <Link
                :href="startCourse.continueHref"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 min-h-[48px] rounded-xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:opacity-95 text-white font-bold text-sm shadow-md shadow-purple-600/25 transition-opacity active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#1e1f20]"
              >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v14l11-7-11-7z" /></svg>
                {{ t('ចាប់ផ្តើម', 'Start') }}
              </Link>
            </div>
          </div>
        </div>
      </section>

      <!-- State A: Empty state -->
      <section v-else-if="state === 'A'" class="space-y-4">
        <div class="bg-white dark:bg-[#1e1f20] border border-dashed border-slate-200 dark:border-[#2d2f31] rounded-3xl p-8 sm:p-12 text-center shadow-sm relative overflow-hidden">
          <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(124,58,237,0.06),transparent_65%)] dark:bg-[radial-gradient(circle_at_top,rgba(139,92,246,0.12),transparent_65%)] pointer-events-none"></div>
          <div class="relative z-10 max-w-lg mx-auto space-y-5">
            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
              {{ t('មិនទាន់មានវគ្គសិក្សាដែលកំពុងរៀននៅឡើយទេ', 'No active course to continue yet') }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
              {{ t('នៅពេលអ្នកចុះឈ្មោះចូលរៀនវគ្គសិក្សាណាមួយ ប្រព័ន្ធនឹងកត់ត្រាមេរៀនចុងក្រោយរបស់អ្នក ដើម្បីឲ្យអ្នកចុចបន្តរៀនពីកន្លែងនេះភ្លាមៗ។', 'Once you enroll in a course, your latest lesson progress will be saved here so you can jump back in with one click.') }}
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
              <Link
                href="/student/browse"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 min-h-[48px] rounded-xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:opacity-95 text-white font-bold text-xs shadow-md shadow-purple-600/25 transition-opacity active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#1e1f20]"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                <span>{{ t('ស្វែងរកវគ្គសិក្សា', 'Browse Courses') }}</span>
              </Link>
              <Link
                v-if="hasEnrollments"
                href="/student/my-courses/enrolled"
                class="inline-flex items-center justify-center gap-2 px-4 py-3 min-h-[48px] rounded-xl bg-slate-100 dark:bg-[#282a2c] hover:bg-slate-200 dark:hover:bg-[#323538] text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-[#3c4043] transition-colors active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#1e1f20]"
              >
                <span>{{ t('វគ្គសិក្សាដែលបានចុះឈ្មោះ', 'Enrolled Courses') }}</span>
              </Link>
            </div>
          </div>
        </div>
      </section>

      <!-- State D: All completed -->
      <section v-else-if="state === 'D'" class="space-y-4">
        <div class="bg-white dark:bg-[#1e1f20] border border-emerald-200/70 dark:border-emerald-700/40 rounded-3xl p-8 sm:p-12 text-center shadow-sm relative overflow-hidden">
          <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(16,185,129,0.08),transparent_65%)] dark:bg-[radial-gradient(circle_at_top,rgba(16,185,129,0.16),transparent_65%)] pointer-events-none"></div>
          <div class="relative z-10 max-w-lg mx-auto space-y-5">
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
              <svg class="w-9 h-9 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            </div>
            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
              {{ t('អ្នកបានបញ្ចប់វគ្គសិក្សាទាំងអស់រួចរាល់ហើយ!', 'You have completed all your enrolled courses!') }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
              {{ t('អបអរសាទរ! សូមពិនិត្យមើលវិញ្ញាបនបត្ររបស់អ្នក ឬស្វែងរកវគ្គសិក្សាថ្មីៗដើម្បីបន្តការរៀន។', 'Congratulations! Check your certificates or explore new courses to keep learning.') }}
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
              <Link
                href="/student/certificates/my-certificates"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 min-h-[48px] rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:opacity-95 text-white font-bold text-xs shadow-md shadow-emerald-600/25 transition-opacity active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#1e1f20]"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                <span>{{ t('វិញ្ញាបនបត្ររបស់ខ្ញុំ', 'My Certificates') }}</span>
              </Link>
              <Link
                href="/student/browse"
                class="inline-flex items-center justify-center gap-2 px-4 py-3 min-h-[48px] rounded-xl bg-slate-100 dark:bg-[#282a2c] hover:bg-slate-200 dark:hover:bg-[#323538] text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-[#3c4043] transition-colors active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#1e1f20]"
              >
                {{ t('ស្វែងរកវគ្គសិក្សា', 'Browse Courses') }}
              </Link>
            </div>
          </div>
        </div>
      </section>

      <!-- State E: Coming soon -->
      <section v-else-if="state === 'E'" class="space-y-4">
        <div class="bg-white dark:bg-[#1e1f20] border border-dashed border-slate-200 dark:border-[#2d2f31] rounded-3xl p-8 sm:p-12 text-center shadow-sm">
          <div class="max-w-lg mx-auto space-y-5">
            <div class="w-16 h-16 mx-auto rounded-full bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
              <svg class="w-9 h-9 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
              {{ t('មាតិកានឹងមកដល់ឆាប់ៗ', 'Content coming soon') }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
              {{ t('វគ្គសិក្សាដែលអ្នកបានចុះឈ្មោះមិនទាន់មានមេរៀននៅឡើយទេ។ សូមមើលវិញនៅពេលឆាប់ៗនេះ ឬស្វែងរកវគ្គសិក្សាផ្សេងទៀត។', 'The courses you enrolled in do not have lessons yet. Please check back soon or explore other courses.') }}
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
              <Link
                v-if="hasEnrollments"
                href="/student/my-courses/enrolled"
                class="inline-flex items-center justify-center gap-2 px-4 py-3 min-h-[48px] rounded-xl bg-slate-100 dark:bg-[#282a2c] hover:bg-slate-200 dark:hover:bg-[#323538] text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-[#3c4043] transition-colors active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#1e1f20]"
              >
                {{ t('វគ្គសិក្សាដែលបានចុះឈ្មោះ', 'Enrolled Courses') }}
              </Link>
              <Link
                href="/student/browse"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 min-h-[48px] rounded-xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:opacity-95 text-white font-bold text-xs shadow-md shadow-purple-600/25 transition-opacity active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#1e1f20]"
              >
                {{ t('ស្វែងរកវគ្គសិក្សា', 'Browse Courses') }}
              </Link>
            </div>
          </div>
        </div>
      </section>

      <!-- Recommended courses (shown in A, D, E) -->
      <section v-if="['A','D','E'].includes(state) && recommendedCourses.length > 0" class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
            {{ t('វគ្គសិក្សាណែនាំសម្រាប់ចាប់ផ្តើម', 'Recommended Courses to Start') }}
          </h2>
          <Link
            href="/student/browse"
            class="text-xs font-bold text-purple-600 dark:text-purple-400 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 rounded"
          >
            {{ t('មើលទាំងអស់', 'View all') }} →
          </Link>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
          <article
            v-for="course in recommendedCourses"
            :key="course.id"
            class="bg-white dark:bg-[#1e1f20] border border-slate-200 dark:border-[#2d2f31] hover:border-purple-400 dark:hover:border-purple-500/60 rounded-2xl overflow-hidden shadow-xs hover:shadow-md transition-all duration-200 flex flex-col h-full"
          >
            <!-- 16:9 thumbnail with soft-color fallback (no waves) -->
            <Link :href="course.overviewHref" class="block relative aspect-video overflow-hidden bg-slate-100 dark:bg-slate-800">
              <img
                v-if="course.thumbnail && !thumbErrors[course.id]"
                :src="course.thumbnail"
                :alt="localizedTitle(course)"
                loading="lazy"
                @error="thumbErrors[course.id] = true"
                class="w-full h-full object-cover"
              />
              <div
                v-else
                :class="['relative w-full h-full flex items-center justify-center', paletteFor(course.id).bg]"
              >
                <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', paletteFor(course.id).soft]">
                  <svg v-if="categoryIconType(course.categoryKh || course.category) === 'code'" :class="['w-6 h-6', paletteFor(course.id).ring]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                  </svg>
                  <svg v-else-if="categoryIconType(course.categoryKh || course.category) === 'compass'" :class="['w-6 h-6', paletteFor(course.id).ring]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a10 10 0 100 20 10 10 0 000-20zm3.5 6.5l-2 5-5 2 2-5 5-2z" />
                  </svg>
                  <svg v-else-if="categoryIconType(course.categoryKh || course.category) === 'leaf'" :class="['w-6 h-6', paletteFor(course.id).ring]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 21c.5-4.5 2.5-8 7-10 3-1.3 6-1.5 9-1.5 0 4.5-1.5 8.5-5 10.5-3.5 2-7.5 1.5-11 1z" />
                  </svg>
                  <svg v-else-if="categoryIconType(course.categoryKh || course.category) === 'users'" :class="['w-6 h-6', paletteFor(course.id).ring]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <svg v-else :class="['w-6 h-6', paletteFor(course.id).ring]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                </div>
              </div>
            </Link>

            <div class="p-4 space-y-2 flex-1 flex flex-col">
              <div class="flex items-center gap-2 flex-wrap">
                <span
                  v-if="course.category || course.categoryKh"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10.5px] font-semibold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300"
                >
                  {{ currentLang === 'km' ? (course.categoryKh || course.category) : (course.category || course.categoryKh) }}
                </span>
                <span
                  :class="course.isFree ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10.5px] font-semibold"
                >
                  {{ course.isFree ? t('ឥតគិតថ្លៃ', 'Free') : course.priceDisplay }}
                </span>
              </div>

              <h3 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2 leading-snug">
                {{ localizedTitle(course) }}
              </h3>

              <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-x-2 gap-y-1 flex-wrap mt-auto pt-1">
                <span v-if="course.teacherName" class="truncate max-w-[140px]">
                  {{ currentLang === 'km' ? (course.teacherNameKh || course.teacherName) : course.teacherName }}
                </span>
                <span v-if="course.teacherName" class="text-slate-300 dark:text-slate-600">•</span>
                <span>{{ course.lessonsCount }} {{ t('មេរៀន', 'lessons') }}</span>
                <span v-if="course.durationSeconds || course.durationLabel" class="text-slate-300 dark:text-slate-600">•</span>
                <span v-if="course.durationSeconds || course.durationLabel">{{ formatDuration(course.durationSeconds, course.durationLabel) }}</span>
              </div>
            </div>

            <div class="px-4 pb-4 grid grid-cols-2 gap-2">
              <Link
                :href="course.overviewHref"
                class="flex items-center justify-center py-2.5 px-3 min-h-[48px] rounded-xl bg-slate-100 dark:bg-[#282a2c] hover:bg-slate-200 dark:hover:bg-[#323538] text-slate-700 dark:text-slate-200 text-xs font-semibold text-center transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
              >
                {{ t('មើលលម្អិត', 'Details') }}
              </Link>
              <button
                @click="handleEnroll(course)"
                type="button"
                :disabled="enrollingId === course.id"
                class="flex items-center justify-center py-2.5 px-3 min-h-[48px] rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold text-center transition-colors cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#1e1f20]"
              >
                {{ enrollingId === course.id ? t('កំពុងចុះឈ្មោះ...', 'Enrolling...') : t('ចុះឈ្មោះឥឡូវ', 'Enroll Now') }}
              </button>
            </div>
          </article>
        </div>
      </section>
    </div>
  </StudentLayout>
</template>

<style scoped>
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
}
</style>
