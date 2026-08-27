<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    role?: string
    size?: 'xs' | 'sm' | 'md' | 'lg'
    showTooltip?: boolean
  }>(),
  {
    role: 'admin',
    size: 'sm',
    showTooltip: true,
  }
)

const isVerified = computed(() => {
  const r = (props.role || '').toLowerCase()
  return r === 'admin' || r === 'teacher'
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'w-3 h-3'
    case 'md':
      return 'w-4.5 h-4.5'
    case 'lg':
      return 'w-5 h-5'
    case 'sm':
    default:
      return 'w-3.5 h-3.5'
  }
})

const tooltipText = computed(() => {
  const r = (props.role || '').toLowerCase()
  if (r === 'admin') {
    return 'គណនី Admin ផ្លូវការ (Verified Administrator)'
  }
  if (r === 'teacher') {
    return 'គណនី គ្រូបង្រៀនផ្លូវការ (Verified Teacher)'
  }
  return 'គណនីផ្លូវការ (Verified Account)'
})
</script>

<template>
  <span
    v-if="isVerified"
    class="inline-flex items-center justify-center shrink-0 select-none group/badge relative align-middle"
    :title="showTooltip ? tooltipText : undefined"
  >
    <svg
      :class="[
        sizeClasses,
        'transition-transform duration-200 group-hover/badge:scale-115 drop-shadow-[0_1px_4px_rgba(2,132,199,0.5)]'
      ]"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <!-- Scalloped Rosette Badge Background (Official Verified Blue) -->
      <path
        fill-rule="evenodd"
        clip-rule="evenodd"
        d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307z"
        fill="#0284c7"
      />
      <!-- Crisp White Checkmark -->
      <path
        d="M9 12.25L11.25 14.5L15.75 9.75"
        stroke="#ffffff"
        stroke-width="2.3"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
    </svg>
  </span>
</template>
