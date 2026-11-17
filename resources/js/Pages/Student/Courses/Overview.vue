<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

interface OverviewLesson {
  id: number
  title: string
  titleKh?: string
  type?: string
  duration: string
  durationSeconds?: number
  completed?: boolean
  isFreePreview?: boolean
  lessonHref?: string
}

interface OverviewChapter {
  id: number
  title: string
  titleKh?: string
  lessonsCount: number
  duration: string
  completed?: boolean
  lessons: OverviewLesson[]
}

interface OverviewVideo {
  id: string
  lessonId?: number | null
  title: string
  titleKh?: string
  duration: string
  durationSeconds?: number
  status?: string
  url?: string | null
  lessonHref?: string | null
  source?: string
}

interface OverviewMaterial {
  id: string
  title: string
  type: string
  fileName?: string | null
  fileSize?: string | null
  downloadCount?: number
  downloadUrl?: string | null
  source?: string
}

interface OverviewReview {
  id: number
  userId: number
  userName: string
  userNameKh?: string
  userAvatar?: string | null
  rating: number
  body?: string | null
  createdAt: string
  isMine?: boolean
}

interface CourseOverviewPayload {
  id: number
  slug: string
  title: string
  titleKh?: string
  subtitle?: string | null
  subtitleKh?: string | null
  bannerUrl?: string | null
  category?: string | null
  categoryKh?: string | null
  level?: string | null
  levelLabel?: string | null
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
  reviewsHref?: string
  price: number
  priceDisplay: string
  isFree: boolean
  rating?: number | null
  reviewsCount: number
  instructor: {
    name?: string | null
    name_kh?: string | null
    role?: string | null
    avatar?: string | null
    bio?: string | null
  }
  chapters: OverviewChapter[]
  videos?: OverviewVideo[]
  materials?: OverviewMaterial[]
  reviews?: OverviewReview[]
  myReview?: OverviewReview | null
}

const props = defineProps<{
  courseId?: string | number
  courseData?: CourseOverviewPayload | null
}>()

const { t, currentLang } = useLanguage()

const activeSectionTab = ref<'chapters' | 'videos' | 'materials' | 'reviews'>('chapters')
const expandedChapter = ref<number | null>(props.courseData?.chapters?.[0]?.id ?? null)
const enrolling = ref(false)
const avatarFailed = ref(false)
const wishlistedState = ref(Boolean(props.courseData?.isWishlisted))
const wishlistPending = ref(false)
const enrolledOptimistic = ref(false)

// Review Form State
const reviewRating = ref<number>(props.courseData?.myReview?.rating || 5)
const reviewBody = ref<string>(props.courseData?.myReview?.body || '')
const reviewSubmitting = ref(false)
const reviewFeedback = ref<string | null>(null)

watch(() => props.courseData, (next) => {
  if (!next) return
  wishlistedState.value = Boolean(next.isWishlisted)
  if (next.chapters?.length && expandedChapter.value === null) {
    expandedChapter.value = next.chapters[0].id
  }
  if (next.myReview) {
    reviewRating.value = next.myReview.rating || 5
    reviewBody.value = next.myReview.body || ''
  }
  if (next.isEnrolled) {
    enrolledOptimistic.value = false
  }
})

const course = computed(() => props.courseData || null)
const isEnrolledComputed = computed(() => Boolean(course.value?.isEnrolled || enrolledOptimistic.value))

const toggleChapter = (id: number) => {
  expandedChapter.value = expandedChapter.value === id ? null : id
}

const getCsrfToken = (): string => {
  if (typeof document === 'undefined') return ''
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

const getInitials = (name?: string | null): string => {
  if (!name) return 'SP'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  return name.slice(0, 2).toUpperCase()
}

const localizedCourseTitle = computed(() => {
  if (!course.value) return ''
  return currentLang.value === 'km'
    ? (course.value.titleKh || course.value.title)
    : course.value.title
})

const localizedCourseSubtitle = computed(() => {
  if (!course.value) return ''
  return currentLang.value === 'km'
    ? (course.value.subtitleKh || course.value.subtitle || '')
    : (course.value.subtitle || '')
})

const localizedCategory = computed(() => {
  if (!course.value) return ''
  return currentLang.value === 'km'
    ? (course.value.categoryKh || course.value.category || '')
    : (course.value.category || '')
})

const localizedInstructorName = computed(() => {
  if (!course.value?.instructor) return ''
  return currentLang.value === 'km'
    ? (course.value.instructor.name_kh || course.value.instructor.name || '')
    : (course.value.instructor.name || '')
})

const localizedLevelLabel = computed(() => {
  const lvl = course.value?.level?.toLowerCase()
  if (!lvl) return ''
  if (lvl === 'beginner') return t('browse_level_beginner')
  if (lvl === 'intermediate') return t('browse_level_intermediate')
  if (lvl === 'advanced') return t('browse_level_advanced')
  return course.value?.levelLabel || lvl
})

const toggleWishlist = async () => {
  if (!course.value || wishlistPending.value) return
  const prev = wishlistedState.value
  const next = !prev
  wishlistedState.value = next
  wishlistPending.value = true

  try {
    const res = await fetch(`/student/courses/${course.value.id}/wishlist`, {
      method: next ? 'POST' : 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
      body: next ? JSON.stringify({}) : undefined,
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const payload = await res.json().catch(() => null)
    if (payload && typeof payload.wishlisted === 'boolean') {
      wishlistedState.value = payload.wishlisted
    }
  } catch (e) {
    wishlistedState.value = prev
  } finally {
    wishlistPending.value = false
  }
}

const handleEnroll = () => {
  if (!course.value || course.value.status === 'coming_soon' || enrolling.value) return
  if (!course.value.isFree && course.value.price > 0) {
    router.visit(course.value.checkoutHref)
    return
  }
  enrolling.value = true
  router.post(course.value.enrollHref, {}, {
    preserveScroll: true,
    onSuccess: () => {
      enrolledOptimistic.value = true
    },
    onFinish: () => {
      enrolling.value = false
    },
  })
}

const submitReview = () => {
  if (!course.value || !isEnrolledComputed.value || reviewSubmitting.value) return
  reviewSubmitting.value = true
  reviewFeedback.value = null
  router.post(
    course.value.reviewsHref || `/student/courses/${course.value.id}/reviews`,
    {
      rating: reviewRating.value,
      body: reviewBody.value.trim(),
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        reviewFeedback.value = currentLang.value === 'km'
          ? 'បានរក្សាទុកការវាយតម្លៃរបស់អ្នកដោយជោគជ័យ!'
          : 'Your review has been saved!'
      },
      onFinish: () => {
        reviewSubmitting.value = false
      },
    }
  )
}
</script>

<template>
  <StudentLayout
    :title="course ? localizedCourseTitle : t('overview_curriculum')"
    :breadcrumbs="[
      { label: t('nav_dashboard'), href: '/student/dashboard' },
      { label: t('browse_title'), href: '/student/browse' },
      { label: course ? localizedCourseTitle : '...' }
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
              <span v-if="localizedLevelLabel" class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-[#282a2c] text-slate-700 dark:text-slate-200">
                {{ localizedLevelLabel }}
              </span>
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-[#282a2c] text-slate-700 dark:text-slate-200">
                {{ course.mode }}
              </span>
              <span
                v-if="isEnrolledComputed"
                class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30"
              >
                ✓ {{ currentLang === 'km' ? 'បានចុះឈ្មោះរួច' : 'Enrolled' }}
              </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
              {{ localizedCourseTitle }}
            </h1>

            <p v-if="localizedCourseSubtitle" class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
              {{ localizedCourseSubtitle }}
            </p>

            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600 dark:text-slate-300 pt-1 tabular-nums">
              <template v-if="course.reviewsCount > 0 && course.rating !== null">
                <span class="font-bold text-amber-500">★ {{ course.rating.toFixed(1) }}</span>
                <span>({{ course.reviewsCount }} {{ currentLang === 'km' ? 'វាយតម្លៃ' : 'reviews' }})</span>
                <span aria-hidden="true">·</span>
              </template>
              <span v-if="course.duration">{{ course.duration }}</span>
              <span v-if="course.duration" aria-hidden="true">·</span>
              <span>{{ course.totalLessons }} {{ course.totalLessons === 1 ? t('browse_lessons_singular') : t('browse_lessons_plural') }}</span>
            </div>
          </div>

          <!-- Action CTA Card -->
          <div class="bg-slate-50 dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-5 space-y-4 lg:w-80 shrink-0">
            <div class="flex items-center justify-between gap-2">
              <span :class="course.isFree ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400'" class="text-lg font-black tabular-nums">
                {{ course.isFree ? t('browse_price_free') : course.priceDisplay }}
              </span>

              <div class="flex items-center gap-2">
                <button
                  @click="toggleWishlist"
                  type="button"
                  :disabled="wishlistPending"
                  :aria-pressed="wishlistedState ? 'true' : 'false'"
                  :title="wishlistedState ? t('browse_wishlist_remove') : t('browse_wishlist_add')"
                  class="w-9 h-9 rounded-full bg-white dark:bg-[#1e1f20] hover:bg-slate-100 dark:hover:bg-[#282a2c] border border-[#dfe3e7] dark:border-[#2d2f31] flex items-center justify-center cursor-pointer transition-transform active:scale-105"
                >
                  <svg
                    class="w-4 h-4 transition-colors"
                    :class="wishlistedState ? 'text-rose-500 fill-rose-500' : 'text-slate-500 dark:text-slate-300'"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                </button>
                <Link
                  href="/student/browse"
                  class="text-xs text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 font-semibold"
                >
                  {{ t('overview_back_browse') }}
                </Link>
              </div>
            </div>

            <div v-if="isEnrolledComputed" class="space-y-2">
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
                v-else-if="isEnrolledComputed"
                :href="course.continueHref"
                class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center"
              >
                {{ course.completedLessons > 0 ? t('overview_continue_learning') : t('overview_start_learning') }}
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

      <!-- DETAILS TABS + INSTRUCTOR -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <div class="lg:col-span-8 bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] p-6 shadow-xs space-y-5">
          <!-- 4 Database-Backed Content Tabs -->
          <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 dark:border-[#2d2f31] pb-4">
            <button
              @click="activeSectionTab = 'chapters'"
              type="button"
              :class="[
                activeSectionTab === 'chapters'
                  ? 'bg-purple-600 text-white font-bold shadow-xs'
                  : 'bg-slate-100 dark:bg-[#282a2c] text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#323538]',
                'px-3.5 py-2 rounded-xl text-xs flex items-center gap-2 transition-colors cursor-pointer'
              ]"
            >
              <span>{{ currentLang === 'km' ? 'មាតិកាមេរៀន' : 'Curriculum' }}</span>
              <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-black/15 dark:bg-white/10 font-bold tabular-nums">
                {{ course.totalLessons }}
              </span>
            </button>

            <button
              @click="activeSectionTab = 'videos'"
              type="button"
              :class="[
                activeSectionTab === 'videos'
                  ? 'bg-purple-600 text-white font-bold shadow-xs'
                  : 'bg-slate-100 dark:bg-[#282a2c] text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#323538]',
                'px-3.5 py-2 rounded-xl text-xs flex items-center gap-2 transition-colors cursor-pointer'
              ]"
            >
              <span>{{ currentLang === 'km' ? 'វីដេអូ' : 'Videos' }}</span>
              <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-black/15 dark:bg-white/10 font-bold tabular-nums">
                {{ course.videos?.length || 0 }}
              </span>
            </button>

            <button
              @click="activeSectionTab = 'materials'"
              type="button"
              :class="[
                activeSectionTab === 'materials'
                  ? 'bg-purple-600 text-white font-bold shadow-xs'
                  : 'bg-slate-100 dark:bg-[#282a2c] text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#323538]',
                'px-3.5 py-2 rounded-xl text-xs flex items-center gap-2 transition-colors cursor-pointer'
              ]"
            >
              <span>{{ currentLang === 'km' ? 'ឯកសារ' : 'Materials' }}</span>
              <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-black/15 dark:bg-white/10 font-bold tabular-nums">
                {{ course.materials?.length || 0 }}
              </span>
            </button>

            <button
              @click="activeSectionTab = 'reviews'"
              type="button"
              :class="[
                activeSectionTab === 'reviews'
                  ? 'bg-purple-600 text-white font-bold shadow-xs'
                  : 'bg-slate-100 dark:bg-[#282a2c] text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#323538]',
                'px-3.5 py-2 rounded-xl text-xs flex items-center gap-2 transition-colors cursor-pointer'
              ]"
            >
              <span>{{ currentLang === 'km' ? 'ការវាយតម្លៃ' : 'Reviews' }}</span>
              <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-black/15 dark:bg-white/10 font-bold tabular-nums">
                {{ course.reviewsCount || 0 }}
              </span>
            </button>
          </div>

          <!-- TAB 1: CHAPTERS / CURRICULUM -->
          <div v-if="activeSectionTab === 'chapters'" class="space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                  {{ t('overview_curriculum') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 tabular-nums">
                  {{ course.chapters.length }} {{ currentLang === 'km' ? 'ជំពូក' : 'Chapters' }} · {{ course.totalLessons }} {{ t('browse_lessons_plural') }}
                  <template v-if="course.duration"> · {{ course.duration }}</template>
                </p>
              </div>
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
                    <h4 class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">
                      {{ currentLang === 'km' ? (chap.titleKh || chap.title) : chap.title }}
                    </h4>
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
                    class="px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-[#131314] flex items-center justify-between gap-3 text-xs text-slate-700 dark:text-slate-300"
                  >
                    <div class="flex items-center gap-2.5 min-w-0">
                      <span v-if="lsn.completed" class="text-emerald-500 font-bold shrink-0">✓</span>
                      <span v-else class="text-slate-400 shrink-0">○</span>
                      <Link
                        v-if="(isEnrolledComputed || lsn.isFreePreview) && lsn.lessonHref"
                        :href="lsn.lessonHref"
                        class="truncate font-medium hover:text-purple-600 dark:hover:text-purple-400"
                      >
                        {{ currentLang === 'km' ? (lsn.titleKh || lsn.title) : lsn.title }}
                      </Link>
                      <span v-else class="truncate">
                        {{ currentLang === 'km' ? (lsn.titleKh || lsn.title) : lsn.title }}
                      </span>
                      <span
                        v-if="lsn.isFreePreview"
                        class="px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold shrink-0"
                      >
                        {{ currentLang === 'km' ? 'មើលសាកល្បង' : 'Preview' }}
                      </span>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono tabular-nums shrink-0">{{ lsn.duration }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2: VIDEOS (course_videos + video lessons) -->
          <div v-else-if="activeSectionTab === 'videos'" class="space-y-4">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ currentLang === 'km' ? 'វីដេអូមេរៀន (Course Videos)' : 'Course Videos' }}
            </h2>

            <div v-if="!course.videos || course.videos.length === 0" class="py-10 text-center text-xs text-slate-500 dark:text-slate-400 border border-dashed border-slate-200 dark:border-[#2d2f31] rounded-2xl">
              {{ currentLang === 'km' ? 'មិនទាន់មានវីដេអូសម្រាប់វគ្គសិក្សានេះនៅឡើយទេ។' : 'No videos uploaded for this course yet.' }}
            </div>

            <div v-else class="space-y-2.5">
              <div
                v-for="vid in course.videos"
                :key="vid.id"
                class="p-3.5 rounded-2xl bg-slate-50 dark:bg-[#131314] border border-slate-200/80 dark:border-[#2d2f31] flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-9 h-9 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                    ▶
                  </div>
                  <div class="min-w-0">
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
                      {{ currentLang === 'km' ? (vid.titleKh || vid.title) : vid.title }}
                    </h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">
                      {{ currentLang === 'km' ? 'រយៈពេល៖' : 'Duration:' }} {{ vid.duration }}
                    </p>
                  </div>
                </div>

                <Link
                  v-if="isEnrolledComputed && vid.lessonHref"
                  :href="vid.lessonHref"
                  class="px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shrink-0"
                >
                  {{ currentLang === 'km' ? 'ទស្សនាវីដេអូ' : 'Watch' }}
                </Link>
                <span v-else class="text-[11px] text-slate-400 font-medium shrink-0">
                  {{ isEnrolledComputed ? (vid.status || 'Ready') : (currentLang === 'km' ? 'ចុះឈ្មោះដើម្បីមើល' : 'Enroll to watch') }}
                </span>
              </div>
            </div>
          </div>

          <!-- TAB 3: MATERIALS (course_materials & lesson_resources) -->
          <div v-else-if="activeSectionTab === 'materials'" class="space-y-4">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ currentLang === 'km' ? 'ឯកសារសិក្សា (Course Materials)' : 'Course Materials' }}
            </h2>

            <div v-if="!course.materials || course.materials.length === 0" class="py-10 text-center text-xs text-slate-500 dark:text-slate-400 border border-dashed border-slate-200 dark:border-[#2d2f31] rounded-2xl">
              {{ currentLang === 'km' ? 'មិនទាន់មានឯកសារសម្រាប់ទាញយកនៅឡើយទេ។' : 'No study materials available for this course yet.' }}
            </div>

            <div v-else class="space-y-2.5">
              <div
                v-for="mat in course.materials"
                :key="mat.id"
                class="p-3.5 rounded-2xl bg-slate-50 dark:bg-[#131314] border border-slate-200/80 dark:border-[#2d2f31] flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <span class="px-2 py-1 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase shrink-0">
                    {{ mat.type }}
                  </span>
                  <div class="min-w-0">
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">{{ mat.title }}</h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate tabular-nums">
                      {{ mat.fileName }}
                      <template v-if="mat.fileSize"> · {{ mat.fileSize }}</template>
                    </p>
                  </div>
                </div>

                <a
                  v-if="isEnrolledComputed && mat.downloadUrl"
                  :href="mat.downloadUrl"
                  target="_blank"
                  rel="noopener"
                  class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shrink-0"
                >
                  {{ currentLang === 'km' ? 'ទាញយក' : 'Download' }}
                </a>
                <span v-else class="text-[11px] text-slate-400 font-medium shrink-0">
                  {{ currentLang === 'km' ? 'ចុះឈ្មោះដើម្បីទាញយក' : 'Enroll to download' }}
                </span>
              </div>
            </div>
          </div>

          <!-- TAB 4: REVIEWS (course_reviews) -->
          <div v-else class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                  {{ currentLang === 'km' ? 'ការវាយតម្លៃពីនិស្សិត' : 'Student Reviews' }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 tabular-nums">
                  <template v-if="course.reviewsCount > 0 && course.rating !== null">
                    ★ {{ course.rating.toFixed(1) }} / 5.0 ({{ course.reviewsCount }} {{ currentLang === 'km' ? 'វាយតម្លៃ' : 'reviews' }})
                  </template>
                  <template v-else>
                    {{ currentLang === 'km' ? 'មិនទាន់មានការវាយតម្លៃនៅឡើយទេ' : 'No reviews yet' }}
                  </template>
                </p>
              </div>
            </div>

            <!-- Write / Update Review Form for Enrolled Students -->
            <form
              v-if="isEnrolledComputed"
              @submit.prevent="submitReview"
              class="p-4 rounded-2xl bg-slate-50 dark:bg-[#131314] border border-slate-200 dark:border-[#2d2f31] space-y-3"
            >
              <div class="flex items-center justify-between gap-2">
                <label class="text-xs font-bold text-slate-800 dark:text-slate-200">
                  {{ currentLang === 'km' ? 'ផ្តល់ពិន្ទុ និងមតិយោបល់របស់អ្នក' : 'Your Rating & Review' }}
                </label>
                <div class="flex items-center gap-1">
                  <button
                    v-for="star in [1, 2, 3, 4, 5]"
                    :key="star"
                    @click="reviewRating = star"
                    type="button"
                    class="text-lg leading-none cursor-pointer transition-transform hover:scale-110"
                    :class="star <= reviewRating ? 'text-amber-400' : 'text-slate-300 dark:text-slate-700'"
                  >
                    ★
                  </button>
                </div>
              </div>

              <textarea
                v-model="reviewBody"
                rows="2"
                maxlength="1000"
                :placeholder="currentLang === 'km' ? 'សរសេរមតិយោបល់អំពីវគ្គសិក្សានេះ...' : 'Share your experience with this course...'"
                class="w-full rounded-xl bg-white dark:bg-[#1e1f20] border border-slate-200 dark:border-[#2d2f31] px-3 py-2 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
              ></textarea>

              <div class="flex items-center justify-between gap-3">
                <span v-if="reviewFeedback" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                  {{ reviewFeedback }}
                </span>
                <span v-else></span>
                <button
                  type="submit"
                  :disabled="reviewSubmitting"
                  class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold cursor-pointer disabled:opacity-60"
                >
                  {{ reviewSubmitting ? (currentLang === 'km' ? 'កំពុងរក្សាទុក...' : 'Saving...') : (currentLang === 'km' ? 'ផ្ញើការវាយតម្លៃ' : 'Submit Review') }}
                </button>
              </div>
            </form>

            <!-- Reviews List -->
            <div v-if="!course.reviews || course.reviews.length === 0" class="py-8 text-center text-xs text-slate-500 dark:text-slate-400 border border-dashed border-slate-200 dark:border-[#2d2f31] rounded-2xl">
              {{ currentLang === 'km' ? 'មិនទាន់មានមតិវាយតម្លៃសម្រាប់វគ្គសិក្សានេះទេ។' : 'No student reviews have been posted yet.' }}
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="rev in course.reviews"
                :key="rev.id"
                class="p-4 rounded-2xl bg-slate-50 dark:bg-[#131314] border border-slate-200/80 dark:border-[#2d2f31] space-y-2"
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <img
                      v-if="rev.userAvatar"
                      :src="rev.userAvatar"
                      :alt="rev.userName"
                      class="w-7 h-7 rounded-full object-cover shrink-0"
                    />
                    <span
                      v-else
                      class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-200 text-[10px] font-bold flex items-center justify-center shrink-0"
                    >
                      {{ getInitials(rev.userName) }}
                    </span>
                    <div class="min-w-0">
                      <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                        {{ currentLang === 'km' ? (rev.userNameKh || rev.userName) : rev.userName }}
                      </p>
                      <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ rev.createdAt }}</p>
                    </div>
                  </div>

                  <span class="text-xs font-bold text-amber-500 shrink-0">
                    {{ '★'.repeat(rev.rating) }}{{ '☆'.repeat(Math.max(0, 5 - rev.rating)) }}
                  </span>
                </div>
                <p v-if="rev.body" class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                  {{ rev.body }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Instructor Card -->
        <aside class="lg:col-span-4 bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] p-5 shadow-xs space-y-4">
          <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
            {{ t('overview_instructor') }}
          </h3>
          <div v-if="localizedInstructorName" class="flex items-center gap-3">
            <img
              v-if="course.instructor.avatar && !avatarFailed"
              :src="course.instructor.avatar"
              :alt="localizedInstructorName"
              @error="avatarFailed = true"
              class="w-12 h-12 rounded-full object-cover border border-purple-200 dark:border-purple-700/50 shrink-0"
            />
            <span
              v-else
              class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-200 font-bold text-sm flex items-center justify-center shrink-0"
            >
              {{ getInitials(localizedInstructorName) }}
            </span>
            <div class="min-w-0">
              <h4 class="font-bold text-slate-900 dark:text-white text-sm truncate">{{ localizedInstructorName }}</h4>
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
