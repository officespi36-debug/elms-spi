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
    ? 'សូមរង់ចាំ កំពុងដំណើរការ...'
    : 'Please wait while loading'
})

const dotSizeClass = computed(() => {
  switch (props.dotSize) {
    case 'sm': return 'w-3 h-3'
    case 'lg': return 'w-6 h-6'
    default: return 'w-[18px] h-[18px]'
  }
})

const dotGapClass = computed(() => {
  switch (props.dotSize) {
    case 'sm': return 'gap-2.5'
    case 'lg': return 'gap-4'
    default: return 'gap-3.5'
  }
})
</script>

<template>
  <div class="flex flex-col items-center justify-center select-none text-center">
    <!-- 4 Sequential Traveling Dots -->
    <div class="flex items-center justify-center mb-4" :class="dotGapClass">
      <span class="dot-item dot-1 rounded-full shrink-0" :class="dotSizeClass"></span>
      <span class="dot-item dot-2 rounded-full shrink-0" :class="dotSizeClass"></span>
      <span class="dot-item dot-3 rounded-full shrink-0" :class="dotSizeClass"></span>
      <span class="dot-item dot-4 rounded-full shrink-0" :class="dotSizeClass"></span>
    </div>

    <!-- Text Below -->
    <div class="space-y-0.5">
      <p class="text-sm font-semibold tracking-wide text-[#cbd5e1]">
        {{ displayText }}
      </p>
      <p v-if="subtext" class="text-xs text-slate-400 font-medium">
        {{ subtext }}
      </p>
    </div>
  </div>
</template>

<style scoped>
/* 4 Dots Travelling Animation - High-speed, 60fps ultra-smooth */
.dot-item {
  display: inline-block;
  border-radius: 9999px;
  background-color: #334155;
  flex-shrink: 0;
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s ease, box-shadow 0.2s ease;
  animation: dotTravel 0.95s infinite cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform, background-color, box-shadow;
}

.dot-1 { animation-delay: 0s; }
.dot-2 { animation-delay: 0.22s; }
.dot-3 { animation-delay: 0.44s; }
.dot-4 { animation-delay: 0.66s; }

@keyframes dotTravel {
  0%, 40%, 100% {
    background-color: #334155;
    transform: scale(0.9);
    opacity: 0.6;
    box-shadow: none;
  }
  20% {
    background-color: #f97316; /* Glowing warm vibrant orange */
    transform: scale(1.24);
    opacity: 1;
    box-shadow: 0 0 16px rgba(249, 115, 22, 0.85);
  }
}
</style>

