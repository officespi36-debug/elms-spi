<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  percent: number
  completedCount: number
  totalCount: number
}>()

const clampedPercent = computed(() => Math.max(0, Math.min(100, Math.round(props.percent || 0))))
const radius = 34
const circumference = 2 * Math.PI * radius
const dashOffset = computed(() => circumference * (1 - clampedPercent.value / 100))

const motivationalText = computed(() => {
  if (clampedPercent.value >= 100) return 'Course completed 🎉'
  if (clampedPercent.value >= 50) return 'Halfway there!'
  if (clampedPercent.value > 0) return 'Keep up the great momentum ✨'
  return "Let's start your first lesson 🚀"
})
</script>

<template>
  <section
    aria-label="Course progress"
    class="bg-white dark:bg-slate-900/95 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex items-center gap-4"
  >
    <!-- Animated SVG Circular Progress Ring -->
    <div class="relative w-20 h-20 shrink-0 flex items-center justify-center">
      <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80" aria-hidden="true">
        <defs>
          <linearGradient id="lessonProgressGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#7c3aed" />
            <stop offset="100%" stop-color="#6366f1" />
          </linearGradient>
        </defs>
        <circle
          cx="40"
          cy="40"
          :r="radius"
          fill="none"
          stroke-width="6.5"
          class="stroke-slate-100 dark:stroke-slate-800"
        />
        <circle
          cx="40"
          cy="40"
          :r="radius"
          fill="none"
          stroke-width="6.5"
          stroke-linecap="round"
          :stroke="clampedPercent >= 100 ? '#10b981' : 'url(#lessonProgressGrad)'"
          :stroke-dasharray="circumference"
          :stroke-dashoffset="dashOffset"
          class="transition-[stroke-dashoffset] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
        />
      </svg>
      <span class="absolute text-sm font-extrabold text-slate-900 dark:text-white tabular-nums">
        {{ clampedPercent }}%
      </span>
    </div>

    <div class="min-w-0 space-y-1">
      <p class="text-sm font-bold text-slate-900 dark:text-white">Course Progress</p>
      <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 tabular-nums">
        {{ completedCount }} / {{ totalCount }} lessons completed
      </p>
      <p class="text-xs text-purple-600 dark:text-purple-400 font-medium leading-[1.6]">
        {{ motivationalText }}
      </p>
    </div>
  </section>
</template>
