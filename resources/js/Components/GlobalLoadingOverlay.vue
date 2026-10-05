<script setup lang="ts">
import { useLoading } from '@/composables/useLoading'
import DotLoader from '@/Components/DotLoader.vue'

const { isLoading, loadingText } = useLoading()
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-250 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isLoading"
        class="fixed inset-0 z-[99999] flex items-center justify-center bg-slate-900/40 dark:bg-slate-950/70 backdrop-blur-sm select-none p-4"
        role="status"
        aria-live="polite"
      >
        <div class="relative max-w-sm w-full">
          <DotLoader
            :text="loadingText"
            :card="true"
            dot-size="md"
          />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
