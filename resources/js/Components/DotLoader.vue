<script setup lang="ts">
import { computed } from 'vue'

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

const displayText = computed(() => {
  return props.text || 'Please wait while loading'
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
  <div class="flex flex-col items-center justify-center select-none text-center bg-transparent">
    <!-- 4 Sequential Traveling Dots -->
    <div class="flex items-center justify-center mb-3.5" :class="dotGapClass">
      <span class="dot-item dot-1 rounded-full shrink-0" :class="dotSizeClass"></span>
      <span class="dot-item dot-2 rounded-full shrink-0" :class="dotSizeClass"></span>
      <span class="dot-item dot-3 rounded-full shrink-0" :class="dotSizeClass"></span>
      <span class="dot-item dot-4 rounded-full shrink-0" :class="dotSizeClass"></span>
    </div>

    <!-- Text Below -->
    <div class="space-y-0.5">
      <p class="text-sm font-semibold tracking-wide text-slate-100 drop-shadow-md">
        {{ displayText }}
      </p>
      <p v-if="subtext" class="text-xs text-slate-300 font-medium drop-shadow-sm">
        {{ subtext }}
      </p>
    </div>
  </div>
</template>

<style scoped>
/* 4 Dots Travelling Animation - Exact match to user's favorite sleek dark screen */
.dot-item {
  --dot-base: #334155;
  display: inline-block;
  border-radius: 9999px;
  background-color: var(--dot-base);
  flex-shrink: 0;
  transition: transform 0.3s ease, background-color 0.3s ease, box-shadow 0.3s ease;
  animation: dotTravel 1.8s infinite ease-in-out;
}

.dot-1 { animation-delay: 0s; }
.dot-2 { animation-delay: 0.3s; }
.dot-3 { animation-delay: 0.6s; }
.dot-4 { animation-delay: 0.9s; }

@keyframes dotTravel {
  0%, 100% {
    background-color: var(--dot-base);
    transform: scale(0.9);
    opacity: 0.65;
    box-shadow: none;
  }
  20%, 35% {
    background-color: #f97316; /* Glowing warm vibrant orange */
    transform: scale(1.22);
    opacity: 1;
    box-shadow: 0 0 16px rgba(249, 115, 22, 0.75);
  }
  50% {
    background-color: var(--dot-base);
    transform: scale(0.95);
    opacity: 0.7;
    box-shadow: none;
  }
}
</style>

