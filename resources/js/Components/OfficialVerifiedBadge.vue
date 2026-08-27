<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    role?: string
    size?: 'xs' | 'sm' | 'md' | 'lg'
    showLabel?: boolean
    label?: string
  }>(),
  {
    role: 'admin',
    size: 'sm',
    showLabel: true,
  }
)

const isVerified = computed(() => {
  const r = (props.role || '').toLowerCase()
  return r === 'admin' || r === 'teacher'
})

const badgeText = computed(() => {
  if (props.label) return props.label
  const r = (props.role || '').toLowerCase()
  if (r === 'admin') {
    return 'Verified Admin'
  }
  if (r === 'teacher') {
    return 'Verified Teacher'
  }
  return 'Verified'
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'w-3 h-3'
    case 'md':
      return 'w-4 h-4'
    case 'lg':
      return 'w-5 h-5'
    case 'sm':
    default:
      return 'w-3.5 h-3.5'
  }
})
</script>

<template>
  <span
    v-if="isVerified"
    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-sky-500/15 border border-sky-500/30 text-sky-400 font-semibold text-[10px] tracking-wide shrink-0 select-none shadow-[0_0_8px_rgba(14,165,233,0.25)] leading-none align-middle"
  >
    <!-- Scalloped Rosette Badge Background (Official Verified Blue) -->
    <svg
      :class="[
        sizeClasses,
        'shrink-0 drop-shadow-[0_1px_3px_rgba(2,132,199,0.6)]'
      ]"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
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

    <!-- Always Visible Text Label -->
    <span v-if="showLabel" class="whitespace-nowrap font-medium">
      {{ badgeText }}
    </span>
  </span>
</template>
