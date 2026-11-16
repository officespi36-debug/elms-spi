<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'

export type TimeRange = '7d' | '30d' | '90d'

export interface MetricsPayload {
  range: TimeRange
  kpi: {
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
  analytics: {
    learning_activity: {
      range: TimeRange
      total_seconds: number
      total_formatted: string
      prev_total_seconds: number
      trend_pct: number | null
      series: Array<{ label: string; seconds: number; minutes: number }>
    }
    study_time: {
      total_seconds: number
      total_formatted: string
      total_minutes: number
      lessons_formatted: string
      lessons_minutes: number
      lessons_pct: number
      practice_formatted: string
      practice_minutes: number
      practice_pct: number
      quiz_formatted: string
      quiz_minutes: number
      quiz_pct: number
      active_days_in_range: number
      daily_average_seconds: number
      daily_average_label: string
    }
    quiz_performance: {
      attempts_count: number
      average_pct: number | null
      highest_pct: number | null
      correct_pct: number | null
      incorrect_pct: number | null
      trend_label: string | null
    }
  }
}

const props = withDefaults(
  defineProps<{
    initialMetrics?: MetricsPayload | null
    currentLang?: 'km' | 'en'
    isLoading?: boolean
  }>(),
  {
    initialMetrics: null,
    currentLang: 'en',
    isLoading: false,
  }
)

const emit = defineEmits<{
  (e: 'metrics-updated', payload: MetricsPayload): void
}>()

const activeRange = ref<TimeRange>(props.initialMetrics?.range || '7d')
const metrics = ref<MetricsPayload | null>(props.initialMetrics || null)
const isFetchingRange = ref(false)
const syncError = ref(false)
let abortController: AbortController | null = null

watch(
  () => props.initialMetrics,
  val => {
    if (val) {
      metrics.value = val
      activeRange.value = val.range || '7d'
    }
  }
)

const fetchRangeMetrics = async (range: TimeRange) => {
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()
  activeRange.value = range
  isFetchingRange.value = true
  syncError.value = false

  try {
    const res = await fetch(`/api/v1/student/dashboard/metrics?range=${encodeURIComponent(range)}`, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      signal: abortController.signal,
    })

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`)
    }

    const json = await res.json()
    if (json && json.success && json.data) {
      metrics.value = json.data
      emit('metrics-updated', json.data)
    } else {
      throw new Error('Invalid response')
    }
  } catch (err: any) {
    if (err?.name === 'AbortError') {
      return
    }
    syncError.value = true
  } finally {
    isFetchingRange.value = false
  }
}

onUnmounted(() => {
  if (abortController) {
    abortController.abort()
  }
})

// Area chart geometry (viewBox 220 x 80)
const learningActivity = computed(() => metrics.value?.analytics?.learning_activity)
const seriesPoints = computed(() => {
  const s = learningActivity.value?.series || []
  if (s.length === 0) {
    return [{ label: '—', seconds: 0, minutes: 0 }]
  }
  return s
})

const areaChartGeometry = computed(() => {
  const pts = seriesPoints.value
  const width = 220
  const height = 68
  const topPad = 10
  const bottomY = 74
  const maxMins = Math.max(...pts.map(p => p.minutes), 1)
  const count = pts.length

  const coords = pts.map((p, i) => {
    const x = count === 1 ? width / 2 : (i / (count - 1)) * width
    const ratio = p.minutes / maxMins
    const y = bottomY - ratio * (height - topPad)
    return { x: Number(x.toFixed(1)), y: Number(y.toFixed(1)) }
  })

  if (coords.length === 1) {
    const c = coords[0]
    return {
      line: `M 0 ${c.y} L ${width} ${c.y}`,
      area: `M 0 ${c.y} L ${width} ${c.y} L ${width} ${bottomY} L 0 ${bottomY} Z`,
      last: c,
    }
  }

  let line = `M ${coords[0].x} ${coords[0].y}`
  for (let i = 1; i < coords.length; i++) {
    const prev = coords[i - 1]
    const curr = coords[i]
    const midX = Number(((prev.x + curr.x) / 2).toFixed(1))
    line += ` Q ${prev.x} ${prev.y}, ${midX} ${Number(((prev.y + curr.y) / 2).toFixed(1))} T ${curr.x} ${curr.y}`
  }

  const last = coords[coords.length - 1]
  const area = `${line} L ${last.x} ${bottomY} L ${coords[0].x} ${bottomY} Z`

  return { line, area, last }
})

// Donut geometry: viewBox="0 0 120 120", r=46, stroke-width=14, C = 289.027
const CIRCUMFERENCE = 2 * Math.PI * 46

const studyTime = computed(() => metrics.value?.analytics?.study_time)
const studyDonutSegments = computed(() => {
  const st = studyTime.value
  const total = st?.total_minutes || 0
  if (!st || total <= 0) {
    return { hasData: false, lessonsLen: 0, practiceLen: 0, quizLen: 0 }
  }
  const lessonsLen = (st.lessons_pct / 100) * CIRCUMFERENCE
  const practiceLen = (st.practice_pct / 100) * CIRCUMFERENCE
  const quizLen = (st.quiz_pct / 100) * CIRCUMFERENCE
  return {
    hasData: true,
    lessonsLen,
    practiceLen,
    quizLen,
    practiceOffset: -lessonsLen,
    quizOffset: -(lessonsLen + practiceLen),
  }
})

const quizPerf = computed(() => metrics.value?.analytics?.quiz_performance)
const quizHasData = computed(() => (quizPerf.value?.attempts_count ?? 0) > 0)
const quizCorrectStroke = computed(() => {
  if (!quizHasData.value || quizPerf.value?.average_pct === null || quizPerf.value?.average_pct === undefined) {
    return 0
  }
  return (quizPerf.value.average_pct / 100) * CIRCUMFERENCE
})
</script>

<template>
  <section aria-label="Analytics overview" class="font-['Inter','Kantumruy_Pro',sans-serif]">
    <!-- Structural Skeleton State (exact h-[280px], no spinners) -->
    <div v-if="isLoading" class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-6">
      <div
        v-for="n in 3"
        :key="n"
        class="h-[280px] rounded-2xl bg-[#F1F5F9] dark:bg-slate-800 border border-[#E2E8F0] dark:border-slate-700 animate-pulse p-6 flex flex-col justify-between"
      >
        <div class="flex justify-between items-center">
          <div class="h-4 w-32 bg-slate-200 dark:bg-slate-700 rounded"></div>
          <div class="h-6 w-20 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
        </div>
        <div class="h-28 w-full bg-slate-200 dark:bg-slate-700 rounded-xl"></div>
        <div class="h-4 w-40 bg-slate-200 dark:bg-slate-700 rounded"></div>
      </div>
    </div>

    <!-- 3-Column Analytics Row -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-6">
      <!-- CARD 1: Learning Activity Area Chart -->
      <div
        class="bg-white dark:bg-[#1e1f20] border border-[#E2E8F0] dark:border-[#2d2f31] rounded-2xl p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex flex-col justify-between min-h-[280px]"
      >
        <div>
          <div class="flex items-center justify-between gap-2 mb-5">
            <h3 class="text-[15px] font-semibold text-slate-900 dark:text-slate-100 leading-relaxed max-[340px]:truncate">
              {{ currentLang === 'km' ? 'សកម្មភាពសិក្សា' : 'Learning Activity' }}
            </h3>

            <div class="inline-flex items-center gap-0.5 p-0.5 bg-slate-100 dark:bg-[#131314] rounded-lg border border-[#E2E8F0] dark:border-[#2d2f31] shrink-0">
              <button
                v-for="r in (['7d', '30d', '90d'] as TimeRange[])"
                :key="r"
                type="button"
                @click="fetchRangeMetrics(r)"
                class="px-2 py-1 rounded-md text-xs font-semibold whitespace-nowrap cursor-pointer transition-colors"
                :class="
                  activeRange === r
                    ? 'bg-[#4F46E5] text-white'
                    : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
                "
              >
                {{ r.toUpperCase() }}
              </button>
            </div>
          </div>

          <!-- Inline Non-Intrusive Error Retry Banner -->
          <div
            v-if="syncError"
            class="mb-3 px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-[11px] text-rose-700 dark:text-rose-300 flex items-center justify-between gap-2"
          >
            <span>{{ currentLang === 'km' ? 'មិនអាចធ្វើបច្ចុប្បន្នភាពទិន្នន័យបានទេ' : 'Unable to sync latest metrics' }}</span>
            <button
              type="button"
              @click="fetchRangeMetrics(activeRange)"
              class="font-semibold underline cursor-pointer shrink-0"
            >
              {{ currentLang === 'km' ? 'ព្យាយាមម្តងទៀត' : 'Retry' }}
            </button>
          </div>

          <!-- Area Chart -->
          <div class="relative pt-2" :class="{ 'opacity-60 transition-opacity': isFetchingRange }">
            <div class="flex items-baseline justify-between mb-2">
              <span class="text-xl font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                {{ learningActivity?.total_formatted || '0h 0m' }}
              </span>
              <span class="text-xs text-slate-500 dark:text-slate-400">
                {{ activeRange.toUpperCase() }}
              </span>
            </div>

            <svg viewBox="0 0 220 80" class="w-full h-24 overflow-visible" aria-hidden="true">
              <defs>
                <linearGradient id="spiLearningAreaGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#4F46E5" stop-opacity="0.22" />
                  <stop offset="100%" stop-color="#4F46E5" stop-opacity="0.01" />
                </linearGradient>
              </defs>
              <path :d="areaChartGeometry.area" fill="url(#spiLearningAreaGrad)" />
              <path
                :d="areaChartGeometry.line"
                fill="none"
                stroke="#4F46E5"
                stroke-width="2.5"
                stroke-linecap="round"
              />
              <circle
                :cx="areaChartGeometry.last.x"
                :cy="areaChartGeometry.last.y"
                r="4"
                fill="#4F46E5"
                stroke="#FFFFFF"
                stroke-width="2"
              />
            </svg>

            <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 tabular-nums pt-1">
              <span v-for="(pt, i) in seriesPoints" :key="i" class="truncate">{{ pt.label }}</span>
            </div>
          </div>
        </div>

        <div class="pt-3 mt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
          <span>{{ currentLang === 'km' ? 'និន្នាការសិក្សា' : 'Trend' }}</span>
          <span
            v-if="learningActivity?.trend_pct !== null && learningActivity?.trend_pct !== undefined"
            class="font-semibold tabular-nums"
            :class="learningActivity.trend_pct >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
          >
            {{ learningActivity.trend_pct >= 0 ? '+' : '' }}{{ learningActivity.trend_pct }}%
            {{ currentLang === 'km' ? 'ធៀបនឹងមុន' : 'vs previous period' }}
          </span>
          <span v-else class="font-medium tabular-nums text-slate-400">
            {{ currentLang === 'km' ? 'មិនទាន់មានទិន្នន័យមុន' : 'No prior period' }}
          </span>
        </div>
      </div>

      <!-- CARD 2: Study Time Donut Breakdown -->
      <div
        class="bg-white dark:bg-[#1e1f20] border border-[#E2E8F0] dark:border-[#2d2f31] rounded-2xl p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex flex-col justify-between min-h-[280px]"
      >
        <div>
          <div class="flex items-center justify-between gap-2 mb-5">
            <h3 class="text-[15px] font-semibold text-slate-900 dark:text-slate-100 leading-relaxed max-[340px]:truncate">
              {{ currentLang === 'km' ? 'ម៉ោងសិក្សា' : 'Study Time' }}
            </h3>
            <Link
              href="/student/progress/learning-time"
              class="text-xs font-medium text-[#4F46E5] dark:text-indigo-400 hover:underline whitespace-nowrap shrink-0"
            >
              {{ currentLang === 'km' ? 'លម្អិត' : 'Details' }}
            </Link>
          </div>

          <div class="flex items-center justify-center gap-5 py-2">
            <!-- 14px Stroke Donut Chart -->
            <div class="relative w-[116px] h-[116px] shrink-0 flex items-center justify-center">
              <svg viewBox="0 0 120 120" class="w-[116px] h-[116px] -rotate-90">
                <circle
                  cx="60"
                  cy="60"
                  r="46"
                  fill="none"
                  class="text-[#E2E8F0] dark:text-slate-800"
                  stroke="currentColor"
                  stroke-width="14"
                />
                <circle
                  v-if="studyDonutSegments.hasData && studyDonutSegments.lessonsLen > 0"
                  cx="60"
                  cy="60"
                  r="46"
                  fill="none"
                  stroke="#4F46E5"
                  stroke-width="14"
                  stroke-linecap="round"
                  :stroke-dasharray="`${studyDonutSegments.lessonsLen} ${CIRCUMFERENCE}`"
                />
                <circle
                  v-if="studyDonutSegments.hasData && studyDonutSegments.practiceLen > 0"
                  cx="60"
                  cy="60"
                  r="46"
                  fill="none"
                  stroke="#8B5CF6"
                  stroke-width="14"
                  stroke-linecap="round"
                  :stroke-dasharray="`${studyDonutSegments.practiceLen} ${CIRCUMFERENCE}`"
                  :stroke-dashoffset="studyDonutSegments.practiceOffset"
                />
                <circle
                  v-if="studyDonutSegments.hasData && studyDonutSegments.quizLen > 0"
                  cx="60"
                  cy="60"
                  r="46"
                  fill="none"
                  stroke="#06B6D4"
                  stroke-width="14"
                  stroke-linecap="round"
                  :stroke-dasharray="`${studyDonutSegments.quizLen} ${CIRCUMFERENCE}`"
                  :stroke-dashoffset="studyDonutSegments.quizOffset"
                />
              </svg>

              <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                <span class="text-sm font-bold text-slate-900 dark:text-slate-100 tabular-nums leading-tight">
                  {{ studyTime?.total_formatted || '0h 0m' }}
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                  {{ currentLang === 'km' ? 'សរុប' : 'Total' }}
                </span>
              </div>
            </div>

            <!-- Aligned Legend Rows with 8px dots -->
            <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300 font-medium min-w-0">
              <div class="flex items-center gap-2 whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-[#4F46E5] shrink-0"></span>
                <span>{{ currentLang === 'km' ? 'មេរៀន៖' : 'Lessons:' }}</span>
                <span class="font-semibold text-slate-900 dark:text-slate-100 tabular-nums">{{ studyTime?.lessons_formatted || '0h 0m' }}</span>
              </div>
              <div class="flex items-center gap-2 whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-[#8B5CF6] shrink-0"></span>
                <span>{{ currentLang === 'km' ? 'អនុវត្ត៖' : 'Practice:' }}</span>
                <span class="font-semibold text-slate-900 dark:text-slate-100 tabular-nums">{{ studyTime?.practice_formatted || '0h 0m' }}</span>
              </div>
              <div class="flex items-center gap-2 whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-[#06B6D4] shrink-0"></span>
                <span>{{ currentLang === 'km' ? 'កម្រងសំណួរ៖' : 'Quiz:' }}</span>
                <span class="font-semibold text-slate-900 dark:text-slate-100 tabular-nums">{{ studyTime?.quiz_formatted || '0h 0m' }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-3 mt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
          <span>{{ currentLang === 'km' ? 'មធ្យមភាគប្រចាំថ្ងៃ' : 'Daily average' }}</span>
          <span class="font-semibold text-slate-700 dark:text-slate-200 tabular-nums">
            {{ studyTime?.daily_average_label || '0h / day' }}
          </span>
        </div>
      </div>

      <!-- CARD 3: Quiz Performance Donut -->
      <div
        class="bg-white dark:bg-[#1e1f20] border border-[#E2E8F0] dark:border-[#2d2f31] rounded-2xl p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)] flex flex-col justify-between min-h-[280px]"
      >
        <div>
          <div class="flex items-center justify-between gap-2 mb-5">
            <h3 class="text-[15px] font-semibold text-slate-900 dark:text-slate-100 leading-relaxed max-[340px]:truncate">
              {{ currentLang === 'km' ? 'លទ្ធផលកម្រងសំណួរ' : 'Quiz Performance' }}
            </h3>
            <span
              class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold whitespace-nowrap shrink-0"
              :class="
                quizHasData
                  ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                  : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
              "
            >
              {{ quizPerf?.trend_label || (currentLang === 'km' ? 'មិនទាន់មានទិន្នន័យ' : 'No attempts yet') }}
            </span>
          </div>

          <div class="flex items-center justify-center gap-5 py-2">
            <!-- 14px Stroke Quiz Ring -->
            <div class="relative w-[116px] h-[116px] shrink-0 flex items-center justify-center">
              <svg viewBox="0 0 120 120" class="w-[116px] h-[116px] -rotate-90">
                <circle
                  cx="60"
                  cy="60"
                  r="46"
                  fill="none"
                  class="text-[#E2E8F0] dark:text-slate-800"
                  stroke="currentColor"
                  stroke-width="14"
                />
                <circle
                  v-if="quizHasData && quizCorrectStroke > 0"
                  cx="60"
                  cy="60"
                  r="46"
                  fill="none"
                  stroke="#10B981"
                  stroke-width="14"
                  stroke-linecap="round"
                  :stroke-dasharray="`${quizCorrectStroke} ${CIRCUMFERENCE}`"
                />
              </svg>

              <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                <span class="text-base font-bold text-slate-900 dark:text-slate-100 tabular-nums leading-tight">
                  {{ quizHasData && quizPerf?.average_pct !== null ? `${quizPerf.average_pct}%` : '—' }}
                </span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                  {{ currentLang === 'km' ? 'មធ្យមភាគ' : 'Average' }}
                </span>
              </div>
            </div>

            <!-- Legend or Zero-Data CTA -->
            <div v-if="quizHasData" class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300 font-medium min-w-0">
              <div class="flex items-center gap-2 whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                <span>{{ currentLang === 'km' ? 'ត្រឹមត្រូវ៖' : 'Correct:' }}</span>
                <span class="font-semibold text-slate-900 dark:text-slate-100 tabular-nums">{{ quizPerf?.correct_pct ?? 0 }}%</span>
              </div>
              <div class="flex items-center gap-2 whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                <span>{{ currentLang === 'km' ? 'មិនត្រឹមត្រូវ៖' : 'Incorrect:' }}</span>
                <span class="font-semibold text-slate-900 dark:text-slate-100 tabular-nums">{{ quizPerf?.incorrect_pct ?? 0 }}%</span>
              </div>
            </div>

            <div v-else class="space-y-1.5 min-w-0">
              <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                {{ currentLang === 'km' ? 'មិនទាន់មានការប្រឡងសាកល្បងនៅឡើយទេ។' : 'No quiz attempts recorded yet.' }}
              </p>
              <Link
                href="/student/quizzes/practice"
                class="inline-flex items-center text-xs font-semibold text-[#4F46E5] dark:text-indigo-400 hover:underline whitespace-nowrap"
              >
                {{ currentLang === 'km' ? 'សាកល្បងធ្វើ Quiz →' : 'Take a diagnostic quiz →' }}
              </Link>
            </div>
          </div>
        </div>

        <div class="pt-3 mt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
          <span>{{ currentLang === 'km' ? 'ពិន្ទុខ្ពស់បំផុត' : 'Highest score' }}</span>
          <span class="font-semibold tabular-nums" :class="quizHasData ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'">
            {{ quizHasData && quizPerf?.highest_pct !== null ? `${quizPerf.highest_pct}%` : '—' }}
          </span>
        </div>
      </div>
    </div>
  </section>
</template>
