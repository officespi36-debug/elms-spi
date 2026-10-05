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
        class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-transparent pointer-events-none select-none p-4"
        role="status"
        aria-live="polite"
      >
        <DotLoader
          :text="loadingText"
          dot-size="md"
        />
      </div>
    </Transition>
  </Teleport>
</template>
