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

const spinnerDims = computed(() => {
  switch (props.dotSize) {
    case 'sm': return { box: '40px', dot: '7px', offset: '-3.5px' }
    case 'lg': return { box: '80px', dot: '13px', offset: '-6.5px' }
    default: return { box: '64px', dot: '11px', offset: '-5.5px' }
  }
})
</script>

<template>
  <div class="flex flex-col items-center justify-center select-none text-center">
    <div
      class="blue-ring-spinner"
      :style="{
        width: spinnerDims.box,
        height: spinnerDims.box,
        '--dot-size': spinnerDims.dot,
        '--dot-offset': spinnerDims.offset
      }"
      aria-hidden="true"
    >
      <span v-for="i in 12" :key="i" class="blue-ring-spoke" :style="{ '--i': i - 1 }">
        <span class="blue-ring-dot"></span>
      </span>
    </div>
    <span class="sr-only">{{ displayText }}</span>
  </div>
</template>

<style scoped>
.blue-ring-spinner {
  position: relative;
}

.blue-ring-spoke {
  position: absolute;
  inset: 0;
  transform: rotate(calc(var(--i) * 30deg));
}

.blue-ring-dot {
  position: absolute;
  top: 2px;
  left: 50%;
  width: var(--dot-size, 11px);
  height: var(--dot-size, 11px);
  margin-left: var(--dot-offset, -5.5px);
  border-radius: 9999px;
  background-color: #0099ff;
  will-change: transform, opacity;
  animation: blueRingPulse 1.08s ease-in-out infinite;
  animation-delay: calc((var(--i) - 12) * 0.09s);
}

@keyframes blueRingPulse {
  0%, 15%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  55% {
    transform: scale(0.16);
    opacity: 0.72;
  }
}

@media (prefers-reduced-motion: reduce) {
  .blue-ring-dot {
    animation: none;
  }
}
</style>
