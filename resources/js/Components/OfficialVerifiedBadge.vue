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
    showLabel: false, // Default to clean, official icon-only to prevent text overflow
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
    return 'Admin'
  }
  if (r === 'teacher') {
    return 'Teacher'
  }
  return 'Verified'
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

const pixelSize = computed(() => {
  switch (props.size) {
    case 'xs':
      return 13
    case 'md':
      return 17
    case 'lg':
      return 20
    case 'sm':
    default:
      return 15
  }
})
</script>

<template>
  <!-- IF WITH LABEL: Sleek pill with guaranteed no-overflow styling -->
  <span
    v-if="isVerified && showLabel"
    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-sky-500/15 border border-sky-500/30 text-sky-400 font-semibold text-[10px] tracking-wide shrink-0 select-none shadow-[0_0_8px_rgba(14,165,233,0.25)] leading-none align-middle"
    :title="tooltipText"
  >
    <svg
      :style="{ width: `${pixelSize}px`, height: `${pixelSize}px`, minWidth: `${pixelSize}px`, minHeight: `${pixelSize}px` }"
      class="shrink-0 drop-shadow-[0_1px_3px_rgba(2,132,199,0.6)]"
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
      <path
        d="M9 12.25L11.25 14.5L15.75 9.75"
        stroke="#ffffff"
        stroke-width="2.3"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
    </svg>
    <span class="whitespace-nowrap font-medium font-sans">
      {{ badgeText }}
    </span>
  </span>

  <!-- IF ICON ONLY: Exact match to Image 1 (Rosette badge with white checkmark, zero overflow) -->
  <span
    v-else-if="isVerified"
    class="inline-flex items-center justify-center shrink-0 select-none align-middle transition-transform duration-200 hover:scale-115 cursor-pointer ml-1"
    :title="tooltipText"
  >
    <svg
      :style="{ width: `${pixelSize + 1}px`, height: `${pixelSize + 1}px`, minWidth: `${pixelSize + 1}px`, minHeight: `${pixelSize + 1}px` }"
      class="shrink-0 drop-shadow-[0_1px_4px_rgba(2,132,199,0.6)]"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <!-- Rosette background -->
      <path
        fill-rule="evenodd"
        clip-rule="evenodd"
        d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307z"
        fill="#0284c7"
      />
      <!-- White checkmark -->
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
