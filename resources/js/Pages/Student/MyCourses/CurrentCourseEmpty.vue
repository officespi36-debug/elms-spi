<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'
import { ref } from 'vue'

interface RecommendedCourse {
  id: number
  title: string
  titleKh?: string
  code?: string
  thumbnail?: string | null
  category?: string | null
  categoryKh?: string | null
  lessonsCount: number
  isFree: boolean
  priceDisplay: string
  overviewHref: string
  enrollHref: string
  checkoutHref: string
}

const props = defineProps<{
  recommendedCourses?: RecommendedCourse[]
}>()

const { t, currentLang } = useLanguage()
const enrollingId = ref<number | null>(null)
const thumbErrors = ref<Record<number, boolean>>({})

const COURSE_THEMES = [
  {
    gradient: 'from-[#2563eb] via-[#3b82f6] to-[#4f46e5]',
    wavePath: 'M0 18 Q 20 6, 42 15 T 74 7 T 100 13 L 100 25 L 0 25 Z',
    waveLine: 'M0 18 Q 20 6, 42 15 T 74 7 T 100 13',
  },
  {
    gradient: 'from-[#6d28d9] via-[#7c3aed] to-[#4f46e5]',
    wavePath: 'M0 20 Q 18 8, 45 16 T 72 5 T 100 14 L 100 25 L 0 25 Z',
    waveLine: 'M0 20 Q 18 8, 45 16 T 72 5 T 100 14',
  },
  {
    gradient: 'from-[#0284c7] via-[#2563eb] to-[#4f46e5]',
    wavePath: 'M0 15 Q 25 22, 48 10 T 80 16 T 100 7 L 100 25 L 0 25 Z',
    waveLine: 'M0 15 Q 25 22, 48 10 T 80 16 T 100 7',
  },
]

const getTheme = (id: number) => COURSE_THEMES[Math.abs(Number(id) || 0) % COURSE_THEMES.length]

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
    <div class="w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800/80 pb-5">
        <div class="space-y-1">
          <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-purple-50 dark:bg-purple-900/30 border border-purple-200/80 dark:border-purple-700/40 text-purple-700 dark:text-purple-300 text-[11px] font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-purple-600 dark:bg-purple-400"></span>
            {{ t('វគ្គសិក្សារបស់ខ្ញុំ', 'My Learning') }}
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
            {{ t('រៀនបន្ត', 'Continue Learning') }}
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400">
            {{ t('បន្តមេរៀនចុងក្រោយរបស់អ្នកយ៉ាងឆាប់រហ័សពីកន្លែងដែលអ្នកបានផ្អាក។', 'Pick up right where you left off in your active courses.') }}
          </p>
        </div>
      </div>

      <!-- Coursera / Udemy-style Empty State Card -->
      <div class="bg-white dark:bg-[#1e1f20] border border-dashed border-slate-200 dark:border-[#2d2f31] rounded-3xl p-8 sm:p-12 text-center shadow-xs relative overflow-hidden">
        <!-- Ambient Radial Glow -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(124,58,237,0.06),transparent_65%)] dark:bg-[radial-gradient(circle_at_top,rgba(139,92,246,0.12),transparent_65%)] pointer-events-none"></div>

        <div class="relative z-10 max-w-lg mx-auto space-y-5">
          <!-- 3D Luminous Play-Course Badge -->
          <div class="relative w-20 h-20 sm:w-24 sm:h-24 mx-auto flex items-center justify-center select-none">
            <div class="absolute inset-1 rounded-full bg-gradient-to-tr from-indigo-500/30 via-purple-500/30 to-cyan-400/25 blur-xl pointer-events-none"></div>
            <div class="relative z-10 w-18 h-18 sm:w-20 sm:h-20 rounded-3xl bg-gradient-to-br from-[#6366f1] via-[#7c3aed] to-[#4f46e5] shadow-lg shadow-purple-600/30 border border-white/25 flex items-center justify-center">
              <svg class="w-9 h-9 text-white translate-x-0.5 drop-shadow-sm" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M8 5.14v14l11-7-11-7z" />
              </svg>
            </div>
          </div>

          <div class="space-y-2">
            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
              {{ t('មិនទាន់មានវគ្គសិក្សាដែលកំពុងរៀននៅឡើយទេ', 'No active course to continue yet') }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
              {{ t('នៅពេលអ្នកចុះឈ្មោះចូលរៀនវគ្គសិក្សាណាមួយ ប្រព័ន្ធនឹងកត់ត្រាមេរៀនចុងក្រោយរបស់អ្នក ដើម្បីឲ្យអ្នកចុចបន្តរៀនពីកន្លែងនេះភ្លាមៗ។', 'Once you enroll in a course, your latest lesson progress will be saved here so you can jump back in with one click.') }}
            </p>
          </div>

          <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
            <Link
              href="/student/browse"
              prefetch="hover"
              cache-for="3m"
              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:opacity-95 text-white font-bold text-xs shadow-md shadow-purple-600/25 transition-colors duration-100 active:scale-[0.97] cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>
              </svg>
              <span>{{ t('ស្វែងរកវគ្គសិក្សា', 'Browse Courses') }}</span>
            </Link>

            <Link
              href="/student/my-courses/enrolled"
              prefetch="hover"
              cache-for="3m"
              class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-[#282a2c] hover:bg-slate-200 dark:hover:bg-[#323538] text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-[#3c4043] transition-colors duration-100 active:scale-[0.97] cursor-pointer"
            >
              <span>{{ t('វគ្គសិក្សាដែលបានចុះឈ្មោះ', 'Enrolled Courses') }}</span>
            </Link>
          </div>
        </div>
      </div>

      <!-- Popular Recommended Courses to Start Immediately -->
      <section v-if="recommendedCourses && recommendedCourses.length > 0" class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
            {{ t('វគ្គសិក្សាណែនាំសម្រាប់ចាប់ផ្តើម', 'Recommended Courses to Start') }}
          </h2>
          <Link
            href="/student/browse"
            prefetch="hover"
            cache-for="3m"
            class="text-xs font-bold text-purple-600 dark:text-purple-400 hover:underline"
          >
            {{ t('មើលទាំងអស់', 'View all') }} →
          </Link>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <article
            v-for="course in recommendedCourses"
            :key="course.id"
            class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 dark:hover:border-purple-500/60 rounded-2xl overflow-hidden shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between group"
          >
            <div>
              <Link :href="course.overviewHref" prefetch="hover" cache-for="3m" class="block relative aspect-video overflow-hidden">
                <img
                  v-if="course.thumbnail && !thumbErrors[course.id]"
                  :src="course.thumbnail"
                  :alt="currentLang === 'km' ? (course.titleKh || course.title) : course.title"
                  @error="thumbErrors[course.id] = true"
                  class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-300"
                />
                <div
                  v-else
                  :class="['relative w-full h-full bg-gradient-to-br flex flex-col items-center justify-center text-white overflow-hidden', getTheme(course.id).gradient]"
                >
                  <div class="shimmer-sweep-soft absolute inset-0 pointer-events-none"></div>
                  <div class="relative z-10 w-10 h-10 rounded-xl bg-white/15 border border-white/25 flex items-center justify-center -mt-2">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                  </div>
                  <svg class="absolute inset-x-0 bottom-0 w-full h-8 text-white pointer-events-none" viewBox="0 0 100 25" preserveAspectRatio="none">
                    <path :d="getTheme(course.id).wavePath" fill="currentColor" opacity="0.22"/>
                    <path :d="getTheme(course.id).waveLine" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" vector-effect="non-scaling-stroke" opacity="0.9"/>
                  </svg>
                </div>
              </Link>

              <div class="p-4 space-y-1.5">
                <span
                  v-if="course.category || course.categoryKh"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10.5px] font-semibold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300"
                >
                  {{ currentLang === 'km' ? (course.categoryKh || course.category) : (course.category || course.categoryKh) }}
                </span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2">
                  {{ currentLang === 'km' ? (course.titleKh || course.title) : course.title }}
                </h3>
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1">
                  <span>{{ course.lessonsCount }} {{ t('មេរៀន', 'lessons') }}</span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400">
                    {{ course.isFree ? t('ឥតគិតថ្លៃ', 'Free') : course.priceDisplay }}
                  </span>
                </div>
              </div>
            </div>

            <div class="px-4 pb-4 grid grid-cols-2 gap-2">
              <Link
                :href="course.overviewHref"
                prefetch="hover"
                cache-for="3m"
                class="py-2 px-3 rounded-xl bg-slate-100 dark:bg-[#282a2c] hover:bg-slate-200 dark:hover:bg-[#323538] text-slate-700 dark:text-slate-200 text-xs font-semibold text-center transition-colors"
              >
                {{ t('មើលលម្អិត', 'Details') }}
              </Link>
              <button
                @click="handleEnroll(course)"
                type="button"
                :disabled="enrollingId === course.id"
                class="py-2 px-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold text-center transition-colors cursor-pointer disabled:opacity-60"
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
.shimmer-sweep-soft {
  background: linear-gradient(
    110deg,
    rgba(255, 255, 255, 0) 20%,
    rgba(255, 255, 255, 0.16) 50%,
    rgba(255, 255, 255, 0) 80%
  );
  animation: shimmerSweep 2.6s ease-in-out infinite;
}

@keyframes shimmerSweep {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}
</style>
