<script setup lang="ts">
import { useLoading } from '@/composables/useLoading'
import DotLoader from '@/Components/DotLoader.vue'

const { isLoading, loadingText } = useLoading()
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-100 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-75 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isLoading"
        class="elms-overlay-backdrop fixed inset-0 z-[99999] flex items-center justify-center pointer-events-none select-none p-4"
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

<style scoped>
.elms-overlay-backdrop {
  background: radial-gradient(
    circle at center,
    rgba(248, 250, 252, 0.96) 0%,
    rgba(248, 250, 252, 0.86) 32%,
    rgba(248, 250, 252, 0.72) 100%
  );
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

:global(html.dark) .elms-overlay-backdrop {
  background: radial-gradient(
    circle at center,
    rgba(11, 19, 43, 0.96) 0%,
    rgba(11, 19, 43, 0.86) 32%,
    rgba(11, 19, 43, 0.74) 100%
  );
}
</style>
