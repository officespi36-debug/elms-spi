<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  progress: number
  completedLessons?: number
  totalLessons?: number
  size?: 'sm' | 'md' | 'lg'
  showLabel?: boolean
  showCount?: boolean
  locale?: string
}>(), {
  progress: 0,
  completedLessons: 0,
  totalLessons: 0,
  size: 'md',
  showLabel: true,
  showCount: false,
  locale: 'km',
})

// Constrain progress between 0 and 100
const sanitizedPercent = computed(() => {
  const p = Number(props.progress)
  if (isNaN(p) || p < 0) return 0
  if (p > 100) return 100
  return Math.round(p)
})

const barHeightClass = computed(() => {
  switch (props.size) {
    case 'sm': return 'h-1.5'
    case 'lg': return 'h-3'
    case 'md':
    default: return 'h-2'
  }
})

const progressGradient = computed(() => {
  if (sanitizedPercent.value >= 100) {
    return 'bg-gradient-to-r from-emerald-500 to-teal-500'
  }
  return 'bg-gradient-to-r from-[#7045F5] via-[#5B32E6] to-[#2563EB]'
})
</script>

<template>
  <div class="w-full space-y-1.5" role="progressbar" :aria-valuenow="sanitizedPercent" aria-valuemin="0" aria-valuemax="100">
    <!-- Optional top label row -->
    <div v-if="showLabel || showCount" class="flex items-center justify-between text-xs font-semibold">
      <span v-if="showCount" class="text-slate-600 dark:text-slate-400 font-medium">
        <template v-if="locale === 'km'">
          {{ completedLessons }} / {{ totalLessons }} មេរៀន
        </template>
        <template v-else>
          {{ completedLessons }} / {{ totalLessons }} lessons
        </template>
      </span>
      <span v-else class="text-slate-500 dark:text-slate-400">
        {{ locale === 'km' ? 'វឌ្ឍនភាព' : 'Progress' }}
      </span>

      <span
        :class="sanitizedPercent >= 100 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-[#7045F5] dark:text-[#a78bfa] font-bold'"
      >
        {{ sanitizedPercent }}%
      </span>
    </div>

    <!-- Progress Track -->
    <div :class="['w-full rounded-full bg-slate-100 dark:bg-slate-800/80 overflow-hidden p-0.5 border border-slate-200/50 dark:border-slate-700/40', barHeightClass]">
      <div
        :class="['h-full rounded-full transition-all duration-500 ease-out shadow-xs', progressGradient]"
        :style="{ width: `${sanitizedPercent}%` }"
      />
    </div>
  </div>
</template>
