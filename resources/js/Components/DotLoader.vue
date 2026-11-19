<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
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
  if (props.text) return props.text.replace(/\.+$/, '').trim()
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
    default: return 'w-[68px] h-[68px]'
  }
})

const ringRef = ref<HTMLElement | null>(null)
let animId: number | null = null

onMounted(() => {
  if (typeof window === 'undefined') return
  let start: number | null = null
  const spin = (ts: number) => {
    if (!ringRef.value) return
    if (!start) start = ts
    const deg = Math.floor(((ts - start) / 1100) * 360) % 360
    ringRef.value.style.transform = `rotate(${deg}deg)`
    animId = requestAnimationFrame(spin)
  }
  animId = requestAnimationFrame(spin)
})

onBeforeUnmount(() => {
  if (animId !== null) {
    cancelAnimationFrame(animId)
  }
})
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-3.5 select-none text-center">
    <div ref="ringRef" :class="['blue-ring-wrap shrink-0', sizeClass]" aria-hidden="true">
      <svg
        class="block w-full h-full"
        viewBox="0 0 64 64"
        fill="none"
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
    </div>

    <div class="space-y-0.5">
      <p class="blue-loading-text h-[26px] leading-[26px] whitespace-nowrap text-base sm:text-[17px] font-extrabold tracking-wide text-slate-900 dark:text-white flex items-center justify-center">
        <span>{{ displayText }}</span><span class="blue-ellipsis" aria-hidden="true">...</span>
      </p>
      <p v-if="subtext" class="text-xs text-slate-600 dark:text-slate-300 font-medium">
        {{ subtext }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.blue-ring-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  transform-origin: 50% 50% !important;
  -webkit-transform-origin: 50% 50% !important;
  will-change: transform;
  filter: drop-shadow(0 2px 8px rgba(0, 153, 255, 0.35));
  -webkit-animation: blueRingSpin 1.1s linear infinite !important;
  animation: blueRingSpin 1.1s linear infinite !important;
}

@-webkit-keyframes blueRingSpin {
  0% {
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}

@keyframes blueRingSpin {
  0% {
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}

.blue-loading-text {
  text-shadow: 0 1px 2px rgba(255, 255, 255, 0.95), 0 0 14px rgba(255, 255, 255, 0.95);
}

:global(html.dark) .blue-loading-text {
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.9), 0 0 14px rgba(11, 19, 43, 0.95);
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
