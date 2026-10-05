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

const displaySubtext = computed(() => {
  if (props.subtext) return props.subtext
  return currentLang.value === 'km' 
    ? 'Please wait while loading' 
    : 'សូមរង់ចាំ កំពុងដំណើរការ...'
})

const dotSizeClasses = computed(() => {
  switch (props.dotSize) {
    case 'sm': return 'w-3 h-3 gap-2'
    case 'lg': return 'w-6 h-6 gap-4'
    default: return 'w-4 h-4 sm:w-5 sm:h-5 gap-3'
  }
})
</script>

<template>
  <div
    :class="[
      'flex flex-col items-center justify-center select-none text-center',
      card ? 'bg-white/95 dark:bg-slate-900/95 backdrop-blur-md px-9 py-7 rounded-2xl shadow-2xl border border-slate-200/80 dark:border-slate-800' : ''
    ]"
  >
    <!-- 4 Sequential Traveling Dots (Smooth & Moderate Pace) -->
    <div class="flex items-center justify-center" :class="dotSizeClasses">
      <span class="dot-item dot-1 rounded-full shrink-0"></span>
      <span class="dot-item dot-2 rounded-full shrink-0"></span>
      <span class="dot-item dot-3 rounded-full shrink-0"></span>
      <span class="dot-item dot-4 rounded-full shrink-0"></span>
    </div>

    <!-- Text Below -->
    <div class="mt-4 space-y-0.5">
      <p class="text-xs sm:text-sm font-semibold tracking-wide text-slate-700 dark:text-slate-200">
        {{ displayText }}
      </p>
      <p v-if="displaySubtext && displaySubtext !== displayText" class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">
        {{ displaySubtext }}
      </p>
    </div>
  </div>
</template>

<style scoped>
/* 4 Dots Travelling Animation - Calmer, Smoother, Easily Visible */
.dot-item {
  aspect-ratio: 1 / 1;
  background-color: #cbd5e1;
  transition: transform 0.3s ease, background-color 0.3s ease, box-shadow 0.3s ease;
  animation: dotTravel 1.8s infinite ease-in-out;
}

.dot-1 { animation-delay: 0s; }
.dot-2 { animation-delay: 0.3s; }
.dot-3 { animation-delay: 0.6s; }
.dot-4 { animation-delay: 0.9s; }

@keyframes dotTravel {
  0%, 100% {
    background-color: #cbd5e1;
    transform: scale(0.9);
    opacity: 0.65;
    box-shadow: none;
  }
  20%, 35% {
    background-color: #ea580c; /* Deep warm vibrant orange from image */
    transform: scale(1.22);
    opacity: 1;
    box-shadow: 0 0 16px rgba(234, 88, 12, 0.65);
  }
  50% {
    background-color: #cbd5e1;
    transform: scale(0.95);
    opacity: 0.7;
    box-shadow: none;
  }
}
</style>
