<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

export interface DashboardKpiData {
  total_enrolled: number
  in_progress_count: number
  completed_count: number
  not_started_count: number
  certificates_count: number
  study_time_seconds: number
  study_time_formatted: string
  quiz_average_pct: number | null
  quiz_attempts_count: number
}

const props = withDefaults(
  defineProps<{
    kpi?: DashboardKpiData | null
    currentLang?: 'km' | 'en'
    isLoading?: boolean
  }>(),
  {
    kpi: null,
    currentLang: 'en',
    isLoading: false,
  }
)

interface CardConfig {
  key: string
  label: string
  value: string
  subtitle: string
  href: string
}

const cards = computed<CardConfig[]>(() => {
  const data = props.kpi
  const km = props.currentLang === 'km'

  const quizVal =
    data && data.quiz_average_pct !== null && data.quiz_average_pct !== undefined
      ? `${data.quiz_average_pct}%`
      : '—'

  const quizSub =
    data && data.quiz_attempts_count > 0
      ? km
        ? `${data.quiz_attempts_count} ដង`
        : `${data.quiz_attempts_count} ${data.quiz_attempts_count === 1 ? 'attempt' : 'attempts'}`
      : km
        ? 'មិនទាន់មានការសាកល្បង'
        : 'No attempts yet'

  return [
    {
      key: 'enrolled',
      label: km ? 'វគ្គបានចុះឈ្មោះ' : 'Enrolled Courses',
      value: String(data?.total_enrolled ?? 0),
      subtitle: km ? 'វគ្គសរុប' : 'Total enrolled',
      href: '/student/my-courses/enrolled',
    },
    {
      key: 'in_progress',
      label: km ? 'កំពុងសិក្សា' : 'In Progress',
      value: String(data?.in_progress_count ?? 0),
      subtitle: km ? 'កំពុងរៀនសកម្ម' : 'Active learning',
      href: '/student/my-courses/enrolled?status=in_progress',
    },
    {
      key: 'completed',
      label: km ? 'បានបញ្ចប់' : 'Completed',
      value: String(data?.completed_count ?? 0),
      subtitle: km ? 'វគ្គបញ្ចប់ពិត' : 'Finished courses',
      href: '/student/my-courses/enrolled?status=completed',
    },
    {
      key: 'certificates',
      label: km ? 'វិញ្ញាបនបត្រ' : 'Certificates',
      value: String(data?.certificates_count ?? 0),
      subtitle: km ? 'បានផ្ទៀងផ្ទាត់' : 'Verified & earned',
      href: '/student/certificates/my-certificates',
    },
    {
      key: 'study_time',
      label: km ? 'ម៉ោងសិក្សា' : 'Study Time',
      value: data?.study_time_formatted || '0h 0m',
      subtitle: km ? 'ពេលវេលាសរុប' : 'Total hours spent',
      href: '/student/progress/learning-time',
    },
    {
      key: 'quiz_average',
      label: km ? 'មធ្យមភាគ Quiz' : 'Quiz Average',
      value: quizVal,
      subtitle: quizSub,
      href: '/student/quizzes/scores',
    },
  ]
})
</script>

<template>
  <section
    aria-label="Key study metrics"
    class="font-['Inter','Kantumruy_Pro',sans-serif]"
  >
    <!-- Structural Skeleton State (exact h-[124px], no spinners) -->
    <div v-if="isLoading" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
      <div
        v-for="n in 6"
        :key="n"
        class="h-[124px] rounded-2xl bg-[#F1F5F9] dark:bg-slate-800 border border-[#E2E8F0] dark:border-slate-700 animate-pulse p-5 flex flex-col justify-between"
      >
        <div class="h-3 w-20 bg-slate-200 dark:bg-slate-700 rounded"></div>
        <div class="h-7 w-14 bg-slate-200 dark:bg-slate-700 rounded"></div>
        <div class="h-3 w-24 bg-slate-200 dark:bg-slate-700 rounded"></div>
      </div>
    </div>

    <!-- Refined 2-Tone Indigo KPI Cards -->
    <div v-else class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
      <Link
        v-for="card in cards"
        :key="card.key"
        :href="card.href"
        class="relative overflow-hidden rounded-2xl p-5 min-h-[124px] text-white transition-transform duration-150 ease-out hover:-translate-y-0.5 flex flex-col justify-between focus:outline-none focus-visible:ring-2 focus-visible:ring-[#4F46E5] focus-visible:ring-offset-2"
        style="
          background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
          border: 1px solid rgba(255, 255, 255, 0.2);
        "
      >
        <!-- Subtle opacity-10 geometric SVG wave -->
        <svg
          class="absolute inset-x-0 bottom-0 w-full h-12 text-white opacity-10 pointer-events-none"
          viewBox="0 0 144 40"
          preserveAspectRatio="none"
          aria-hidden="true"
        >
          <path
            d="M0 28 C 32 10, 64 36, 96 18 C 118 6, 132 22, 144 16 L 144 40 L 0 40 Z"
            fill="currentColor"
          />
        </svg>

        <div class="relative z-10">
          <div class="text-[11px] uppercase font-semibold tracking-wider text-indigo-100 leading-relaxed">
            {{ card.label }}
          </div>
          <div class="text-[28px] font-bold text-white tabular-nums leading-tight my-1.5">
            {{ card.value }}
          </div>
        </div>

        <div class="relative z-10 text-[12px] text-indigo-100/90 leading-relaxed truncate">
          {{ card.subtitle }}
        </div>
      </Link>
    </div>
  </section>
</template>
