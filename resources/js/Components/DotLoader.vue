<script setup lang="ts">
import { computed } from 'vue'
import { useLanguage } from '@/Services/i18n'

const props = withDefaults(defineProps<{
  text?: string
  subtext?: string
  dotSize?: 'sm' | 'md' | 'lg'
  card?: boolean
}>(), {
  text: '',
  subtext: '',
  dotSize: 'md',
  card: false
})

const { currentLang } = useLanguage()

const displayText = computed(() => {
  if (props.text) return props.text
  return currentLang.value === 'km'
    ? 'សូមរង់ចាំ កំពុងដំណើរការ'
    : 'Please wait while loading'
})

const ringDots = [
  { angle: 0, r: 5.2, opacity: 1 },
  { angle: 30, r: 4.8, opacity: 0.96 },
  { angle: 60, r: 4.3, opacity: 0.92 },
  { angle: 90, r: 3.7, opacity: 0.88 },
  { angle: 120, r: 3.0, opacity: 0.84 },
  { angle: 150, r: 2.0, opacity: 0.78 },
  { angle: 180, r: 1.1, opacity: 0.72 },
  { angle: 210, r: 1.8, opacity: 0.78 },
  { angle: 240, r: 2.7, opacity: 0.84 },
  { angle: 270, r: 3.6, opacity: 0.88 },
  { angle: 300, r: 4.3, opacity: 0.94 },
  { angle: 330, r: 4.8, opacity: 0.98 },
]

const sizeClass = computed(() => {
  switch (props.dotSize) {
    case 'sm': return 'w-10 h-10'
    case 'lg': return 'w-20 h-20'
    default: return 'w-[60px] h-[60px]'
  }
})
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-3 select-none text-center">
    <svg
      :class="['blue-ring-svg', sizeClass]"
      viewBox="0 0 64 64"
      fill="none"
      aria-hidden="true"
    >
      <circle
        v-for="dot in ringDots"
        :key="dot.angle"
        cx="32"
        cy="9"
        :r="dot.r"
        fill="#0099ff"
        :fill-opacity="dot.opacity"
        :transform="`rotate(${dot.angle} 32 32)`"
      />
    </svg>

    <div class="space-y-0.5">
      <p class="blue-loading-text text-sm sm:text-base font-bold tracking-wide text-slate-900 dark:text-white drop-shadow-[0_2px_10px_rgba(255,255,255,0.85)] dark:drop-shadow-[0_2px_10px_rgba(0,0,0,0.65)] flex items-center justify-center">
        <span>{{ displayText }}</span><span class="blue-ellipsis" aria-hidden="true">...</span>
      </p>
      <p v-if="subtext" class="text-xs text-slate-600 dark:text-slate-300 font-medium">
        {{ subtext }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.blue-ring-svg {
  transform-origin: center center;
  will-change: transform;
  animation: blueRingSpin 0.85s linear infinite;
}

@keyframes blueRingSpin {
  to {
    transform: rotate(360deg);
  }
}

.blue-loading-text {
  animation: blueTextPulse 1.4s ease-in-out infinite;
}

@keyframes blueTextPulse {
  0%, 100% { opacity: 0.82; }
  50% { opacity: 1; }
}

.blue-ellipsis {
  display: inline-block;
  overflow: hidden;
  vertical-align: bottom;
  width: 0;
  animation: blueEllipsisRun 1.2s steps(4, end) infinite;
}

@keyframes blueEllipsisRun {
  to { width: 1.25em; }
}
</style>
