<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

interface OverviewLesson {
  id?: number
  title: string
  duration: string
  completed?: boolean
}

interface OverviewChapter {
  id: number
  title: string
  lessonsCount: number
  duration: string
  completed?: boolean
  lessons: OverviewLesson[]
}

interface CourseOverviewPayload {
  id: number
  slug: string
  title: string
  subtitle?: string | null
  bannerUrl?: string | null
  category?: string | null
  categoryKh?: string | null
  level?: string | null
  mode: string
  duration?: string | null
  progress: number
  status: 'not_enrolled' | 'not_started' | 'in_progress' | 'completed' | 'coming_soon'
  isEnrolled: boolean
  isPendingPayment?: boolean
  isWishlisted?: boolean
  totalLessons: number
  completedLessons: number
  continueHref: string
  enrollHref: string
  checkoutHref: string
  price: number
  priceDisplay: string
  isFree: boolean
  rating?: number | null
  reviewsCount: number
  instructor: {
    name?: string | null
    role?: string | null
    avatar?: string | null
    bio?: string | null
  }
  chapters: OverviewChapter[]
}

const props = defineProps<{
  courseId?: string | number
  courseData?: CourseOverviewPayload | null
}>()

const { t, currentLang } = useLanguage()
const expandedChapter = ref<number | null>(props.courseData?.chapters?.[0]?.id ?? null)
const enrolling = ref(false)
const avatarFailed = ref(false)

const course = computed(() => props.courseData || null)

const toggleChapter = (id: number) => {
  expandedChapter.value = expandedChapter.value === id ? null : id
}

const getInitials = (name?: string | null): string => {
  if (!name) return 'SP'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  return name.slice(0, 2).toUpperCase()
}

const localizedCategory = computed(() => {
  if (!course.value) return ''
  return currentLang.value === 'km'
    ? (course.value.categoryKh || course.value.category || '')
    : (course.value.category || '')
})

const handleEnroll = () => {
  if (!course.value || course.value.status === 'coming_soon' || enrolling.value) return
  if (!course.value.isFree && course.value.price > 0) {
    router.visit(course.value.checkoutHref)
    return
  }
  enrolling.value = true
  router.post(course.value.enrollHref, {}, {
    preserveScroll: true,
    onFinish: () => { enrolling.value = false },
  })
}
</script>

<template>
  <StudentLayout
    :title="course ? course.title : t('overview_curriculum')"
    :breadcrumbs="[
      { label: t('nav_dashboard'), href: '/student/dashboard' },
      { label: t('browse_title'), href: '/student/browse' },
      { label: course ? course.title : '...' }
    ]"
  >
    <div v-if="course" class="space-y-6 pb-24 font-['Inter','Kantumruy_Pro',sans-serif]">
      <!-- HERO SECTION -->
      <div class="relative rounded-[20px] overflow-hidden bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] shadow-xs dark:shadow-xl">
        <div
          v-if="course.bannerUrl"
          class="absolute inset-0 bg-cover bg-center opacity-10 dark:opacity-15 pointer-events-none"
          :style="{ backgroundImage: `url(${course.bannerUrl})` }"
        ></div>

        <div class="relative z-10 p-6 sm:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
          <div class="space-y-3 max-w-2xl">
            <div class="flex flex-wrap items-center gap-2">
              <span v-if="localizedCategory" class="px-3 py-1 rounded-full text-xs font-bold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-200 border border-purple-200 dark:border-purple-700/50">
                {{ localizedCategory }}
              </span>
              <span v-if="course.level" class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-[#282a2c] text-slate-700 dark:text-slate-200">
                {{ course.level }}
              </span>
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-[#282a2c] text-slate-700 dark:text-slate-200">
                {{ course.mode }}
              </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
              {{ course.title }}
            </h1>

            <p v-if="course.subtitle" class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
              {{ course.subtitle }}
            </p>

            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600 dark:text-slate-300 pt-1 tabular-nums">
              <template v-if="course.reviewsCount >= 3 && course.rating !== null">
                <span class="font-bold text-amber-500">★ {{ course.rating.toFixed(1) }}</span>
                <span>({{ course.reviewsCount }})</span>
                <span aria-hidden="true">·</span>
              </template>
              <span v-if="course.duration">{{ course.duration }}</span>
              <span v-if="course.duration" aria-hidden="true">·</span>
              <span>{{ course.totalLessons }} {{ course.totalLessons === 1 ? t('browse_lessons_singular') : t('browse_lessons_plural') }}</span>
            </div>
          </div>

          <!-- Action CTA Card -->
          <div class="bg-slate-50 dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-5 space-y-4 lg:w-80 shrink-0">
            <div class="flex items-center justify-between">
              <span :class="course.isFree ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400'" class="text-lg font-black tabular-nums">
                {{ course.isFree ? t('browse_price_free') : course.priceDisplay }}
              </span>
              <Link
                href="/student/browse"
                class="text-xs text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 font-semibold"
              >
                {{ t('overview_back_browse') }}
              </Link>
            </div>

            <div v-if="course.isEnrolled" class="space-y-2">
              <div class="flex items-center justify-between text-xs font-bold tabular-nums">
                <span class="text-slate-600 dark:text-slate-300">{{ t('overview_progress') }}</span>
                <span class="text-purple-600 dark:text-purple-400">{{ course.progress }}%</span>
              </div>
              <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-[#282a2c] overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-300"
                  :class="course.progress === 100 ? 'bg-emerald-500' : 'bg-purple-600'"
                  :style="{ width: course.progress + '%' }"
                ></div>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">
                {{ course.completedLessons }} / {{ course.totalLessons }} {{ t('browse_lessons_plural') }}
              </p>
            </div>

            <div class="space-y-2">
              <button
                v-if="course.status === 'coming_soon'"
                type="button"
                disabled
                class="w-full py-3 rounded-xl bg-slate-200 dark:bg-[#282a2c] text-slate-500 dark:text-slate-400 font-bold text-xs cursor-not-allowed"
              >
                {{ t('overview_coming_soon') }}
              </button>
              <Link
                v-else-if="course.isPendingPayment"
                :href="course.checkoutHref"
                class="w-full py-3 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs flex items-center justify-center"
              >
                {{ t('overview_complete_payment') }}
              </Link>
              <Link
                v-else-if="course.status === 'completed'"
                :href="course.continueHref"
                class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center"
              >
                {{ t('overview_review_course') }}
              </Link>
              <Link
                v-else-if="course.isEnrolled"
                :href="course.continueHref"
                class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs flex items-center justify-center"
              >
                {{ course.status === 'in_progress' ? t('overview_continue_learning') : t('overview_start_learning') }}
              </Link>
              <button
                v-else
                @click="handleEnroll"
                type="button"
                :disabled="enrolling"
                class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs cursor-pointer disabled:opacity-60"
              >
                {{ enrolling ? t('browse_cta_enrolling') : (course.isFree ? t('overview_enroll_free') : t('overview_enroll_paid')) }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- CURRICULUM & INSTRUCTOR -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <div class="lg:col-span-8 bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] p-6 shadow-xs space-y-4">
          <div class="border-b border-slate-100 dark:border-[#2d2f31] pb-3">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ t('overview_curriculum') }}
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 tabular-nums">
              {{ course.chapters.length }} · {{ course.totalLessons }} {{ t('browse_lessons_plural') }}
              <template v-if="course.duration"> · {{ course.duration }}</template>
            </p>
          </div>

          <div v-if="course.chapters.length === 0" class="py-8 text-center text-xs text-slate-500 dark:text-slate-400">
            {{ t('overview_no_lessons') }}
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="chap in course.chapters"
              :key="chap.id"
              class="rounded-2xl border border-[#dfe3e7] dark:border-[#2d2f31] overflow-hidden"
            >
              <button
                @click="toggleChapter(chap.id)"
                type="button"
                class="w-full p-4 bg-slate-50 dark:bg-[#282a2c]/60 hover:bg-slate-100 dark:hover:bg-[#282a2c] flex items-center justify-between text-left cursor-pointer"
              >
                <div>
                  <h4 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">{{ chap.title }}</h4>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 tabular-nums">
                    {{ chap.lessonsCount }} {{ chap.lessonsCount === 1 ? t('browse_lessons_singular') : t('browse_lessons_plural') }} · {{ chap.duration }}
                  </p>
                </div>
                <span class="text-xs font-mono text-slate-500">{{ expandedChapter === chap.id ? '▾' : '▸' }}</span>
              </button>

              <div v-show="expandedChapter === chap.id" class="p-3 space-y-1.5 bg-white dark:bg-[#1e1f20] border-t border-slate-100 dark:border-[#2d2f31]">
                <div
                  v-for="(lsn, idx) in chap.lessons"
                  :key="lsn.id || idx"
                  class="px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-[#131314] flex items-center justify-between text-xs text-slate-700 dark:text-slate-300"
                >
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span v-if="lsn.completed" class="text-emerald-500 font-bold">✓</span>
                    <span v-else class="text-slate-400">○</span>
                    <span class="truncate">{{ lsn.title }}</span>
                  </div>
                  <span class="text-[11px] text-slate-500 font-mono tabular-nums shrink-0">{{ lsn.duration }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <aside class="lg:col-span-4 bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] p-5 shadow-xs space-y-4">
          <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
            {{ t('overview_instructor') }}
          </h3>
          <div v-if="course.instructor?.name" class="flex items-center gap-3">
            <img
              v-if="course.instructor.avatar && !avatarFailed"
              :src="course.instructor.avatar"
              :alt="course.instructor.name"
              @error="avatarFailed = true"
              class="w-12 h-12 rounded-full object-cover border border-purple-200 dark:border-purple-700/50"
            />
            <span
              v-else
              class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-200 font-bold text-sm flex items-center justify-center"
            >
              {{ getInitials(course.instructor.name) }}
            </span>
            <div class="min-w-0">
              <h4 class="font-bold text-slate-900 dark:text-white text-sm truncate">{{ course.instructor.name }}</h4>
              <p v-if="course.instructor.role" class="text-xs text-purple-600 dark:text-purple-400">{{ course.instructor.role }}</p>
            </div>
          </div>
          <p v-if="course.instructor?.bio" class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
            {{ course.instructor.bio }}
          </p>
        </aside>
      </div>
    </div>

    <div v-else class="p-12 text-center bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] space-y-4">
      <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ t('browse_empty_filtered_title') }}</h2>
      <Link href="/student/browse" class="inline-block px-5 py-2.5 rounded-xl bg-purple-600 text-white text-xs font-bold">
        {{ t('overview_back_browse') }}
      </Link>
    </div>
  </StudentLayout>
</template>
