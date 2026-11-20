<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

interface WishlistCourse {
  id: number
  title: string
  thumbnail?: string | null
  category?: string | null
  categoryKh?: string | null
  teacher?: string | null
  mode: string
  price: number
  priceDisplay: string
  isFree: boolean
  lessonsCount: number
  duration?: string | null
  enrollmentsCount: number
  isEnrolled: boolean
  isComingSoon: boolean
  addedDate: string
  overviewHref: string
  continueHref: string
}

const props = defineProps<{
  wishlistedCourses?: WishlistCourse[]
}>()

const { t, currentLang } = useLanguage()
const items = ref<WishlistCourse[]>([...(props.wishlistedCourses || [])])
const removingId = ref<number | null>(null)

const count = computed(() => items.value.length)

const getCsrfToken = (): string => {
  if (typeof document === 'undefined') return ''
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

const removeItem = async (id: number) => {
  if (removingId.value === id) return
  const snapshot = [...items.value]
  items.value = items.value.filter((c) => c.id !== id)
  removingId.value = id

  try {
    const res = await fetch(`/student/courses/${id}/wishlist`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
    })
    if (!res.ok) throw new Error('Failed')
  } catch (e) {
    items.value = snapshot
  } finally {
    removingId.value = null
  }
}

const enrollCourse = (course: WishlistCourse) => {
  if (course.isComingSoon) return
  if (!course.isFree && course.price > 0) {
    router.visit(`/student/payments/${course.id}/upload`)
    return
  }
  router.post(`/student/courses/${course.id}/enroll`, {}, { preserveScroll: true })
}
</script>

<template>
  <StudentLayout
    :title="t('wishlist_title')"
    :breadcrumbs="[
      { label: t('nav_dashboard'), href: '/student/dashboard' },
      { label: t('nav_my_courses'), href: '/student/my-courses/enrolled' },
      { label: t('wishlist_title') }
    ]"
  >
    <div class="space-y-6 pb-24 font-['Inter','Kantumruy_Pro',sans-serif]">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-[#2d2f31] pb-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
            {{ t('wishlist_title') }}
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
            {{ t('wishlist_subtitle') }}
          </p>
        </div>

        <span class="px-3.5 py-1.5 rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-200 border border-purple-200 dark:border-purple-700/50 text-xs font-bold self-start tabular-nums">
          {{ count }} {{ t('wishlist_count_label') }}
        </span>
      </div>

      <div
        v-if="items.length === 0"
        class="p-12 text-center bg-white dark:bg-[#1e1f20] rounded-[20px] border border-dashed border-[#dfe3e7] dark:border-[#2d2f31] space-y-4"
      >
        <div class="w-14 h-14 rounded-full bg-rose-50 dark:bg-rose-900/30 text-rose-500 flex items-center justify-center mx-auto">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ t('wishlist_empty_title') }}</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">{{ t('wishlist_empty_desc') }}</p>
        <Link
          href="/student/browse"
          class="inline-block px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
        >
          {{ t('wishlist_browse_btn') }}
        </Link>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <article
          v-for="course in items"
          :key="course.id"
          class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-[20px] p-5 shadow-xs space-y-4 flex flex-col justify-between"
        >
          <div class="space-y-2.5">
            <div class="flex items-start justify-between gap-3">
              <span
                v-if="course.category || course.categoryKh"
                class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-200"
              >
                {{ currentLang === 'km' ? (course.categoryKh || course.category) : course.category }}
              </span>
              <span
                :class="course.isFree ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400'"
                class="text-xs font-black tabular-nums"
              >
                {{ course.isFree ? t('browse_price_free') : course.priceDisplay }}
              </span>
            </div>

            <h3 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2">
              <Link :href="course.overviewHref" class="hover:text-purple-600 dark:hover:text-purple-400">
                {{ course.title }}
              </Link>
            </h3>

            <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1 tabular-nums">
              <p v-if="course.teacher">{{ course.teacher }}</p>
              <p>
                {{ course.lessonsCount }} {{ course.lessonsCount === 1 ? t('browse_lessons_singular') : t('browse_lessons_plural') }}
                <template v-if="course.duration"> · {{ course.duration }}</template>
              </p>
              <p v-if="course.addedDate">{{ t('wishlist_added_label') }} {{ course.addedDate }}</p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100 dark:border-[#2d2f31]">
            <Link
              v-if="course.isEnrolled && !course.isComingSoon"
              :href="course.continueHref"
              class="flex-1 text-center px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs"
            >
              {{ t('browse_cta_continue') }}
            </Link>
            <button
              v-else-if="course.isComingSoon"
              type="button"
              disabled
              class="flex-1 px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-[#282a2c] text-slate-500 font-bold text-xs cursor-not-allowed"
            >
              {{ t('browse_cta_coming_soon') }}
            </button>
            <button
              v-else
              @click="enrollCourse(course)"
              type="button"
              class="flex-1 px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs cursor-pointer"
            >
              {{ t('browse_cta_enroll') }}
            </button>

            <Link
              :href="course.overviewHref"
              class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-[#282a2c] text-slate-700 dark:text-slate-200 font-semibold text-xs"
            >
              {{ t('browse_cta_view_details') }}
            </Link>

            <button
              @click="removeItem(course.id)"
              type="button"
              :disabled="removingId === course.id"
              class="px-3 py-2 rounded-xl bg-rose-50 dark:bg-rose-900/30 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-300 font-bold text-xs cursor-pointer"
            >
              {{ t('wishlist_remove_btn') }}
            </button>
          </div>
        </article>
      </div>
    </div>
  </StudentLayout>
</template>
