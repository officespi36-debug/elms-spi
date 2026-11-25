<script setup lang="ts">
import { ChevronLeft, CheckCircle2, Sparkles } from 'lucide-vue-next'

defineProps<{
  visible: boolean
  hasPrevious: boolean
  readingPercent: number
  isCompleting: boolean
  isEligible: boolean
  isCompleted: boolean
  actionLabel: string
  justCompleted?: boolean
}>()

const emit = defineEmits<{
  (e: 'previous'): void
  (e: 'action'): void
}>()
</script>

<template>
  <Transition
    enter-active-class="transition duration-250 ease-[cubic-bezier(0.22,1,0.36,1)]"
    enter-from-class="translate-y-full opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-full opacity-0"
  >
    <div
      v-if="visible"
      role="region"
      aria-label="Lesson action bar"
      class="fixed bottom-0 left-0 right-0 z-30 backdrop-blur-md bg-white/85 dark:bg-slate-900/85 border-t border-slate-200/80 dark:border-slate-800/80 shadow-2xl px-4 sm:px-6 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]"
    >
      <div class="max-w-7xl mx-auto flex items-center justify-between gap-3">
        <button
          type="button"
          @click="emit('previous')"
          :disabled="!hasPrevious"
          aria-label="Previous lesson"
          class="min-h-[44px] px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer disabled:opacity-45 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
        >
          <ChevronLeft class="w-4 h-4" />
          <span class="hidden sm:inline">Previous</span>
        </button>

        <!-- Reading progress pill -->
        <div class="hidden md:flex items-center gap-3 flex-1 max-w-xs">
          <div class="flex-1 h-1.5 rounded-full bg-slate-200/80 dark:bg-slate-800 overflow-hidden">
            <div
              class="h-full rounded-full bg-gradient-to-r from-[#7c3aed] to-[#6366f1] transition-all duration-300"
              :style="{ width: `${Math.min(100, Math.round(readingPercent))}%` }"
            ></div>
          </div>
          <span class="text-xs font-semibold text-slate-600 dark:text-slate-300 tabular-nums">
            {{ Math.min(100, Math.round(readingPercent)) }}%
          </span>
        </div>

        <!-- Primary Complete & Continue button + celebratory confetti burst -->
        <div class="relative flex-1 sm:flex-initial">
          <button
            type="button"
            @click="emit('action')"
            :disabled="isCompleting || (!isCompleted && !isEligible)"
            class="w-full sm:w-auto min-h-[44px] px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#6366f1] text-white text-xs sm:text-sm font-bold shadow-lg shadow-purple-600/25 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
          >
            <CheckCircle2 v-if="justCompleted || isCompleted" class="w-4 h-4 text-emerald-300 animate-bounce" />
            <Sparkles v-else class="w-4 h-4" />
            <span>{{ isCompleting ? 'Saving...' : actionLabel }}</span>
          </button>

          <!-- Lightweight CSS confetti burst on completion -->
          <div v-if="justCompleted" class="confetti-burst pointer-events-none" aria-hidden="true">
            <span v-for="n in 8" :key="n" :style="`--i:${n}`"></span>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.confetti-burst {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
.confetti-burst span {
  position: absolute;
  width: 6px;
  height: 6px;
  border-radius: 9999px;
  background: hsl(calc(var(--i) * 45), 90%, 60%);
  animation: confettiPop 650ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
}
@keyframes confettiPop {
  0% { transform: translate(0, 0) scale(0.6); opacity: 1; }
  100% {
    transform: translate(
      calc((var(--i) - 4.5) * 14px),
      calc(-28px - mod(var(--i), 3) * 10px)
    ) scale(1);
    opacity: 0;
  }
}
@media (prefers-reduced-motion: reduce) {
  .confetti-burst { display: none; }
}
</style>
