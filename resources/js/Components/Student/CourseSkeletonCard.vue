<script setup lang="ts">
// Reusable course card skeleton with smooth shimmer animation
</script>

<template>
  <div class="bg-white dark:bg-[#1e1f20] rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.04)] overflow-hidden flex flex-col animate-pulse">
    <!-- Cover skeleton -->
    <div class="aspect-video w-full bg-slate-200 dark:bg-slate-800 relative">
      <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 dark:via-white/5 to-transparent skeleton-shimmer"></div>
      <div class="absolute top-3 left-3 w-20 h-5 rounded-full bg-slate-300 dark:bg-slate-700"></div>
      <div class="absolute top-3 right-3 w-24 h-5 rounded-full bg-slate-300 dark:bg-slate-700"></div>
    </div>

    <!-- Body skeleton -->
    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
      <div class="space-y-2.5">
        <div class="h-5 bg-slate-200 dark:bg-slate-700 rounded-md w-4/5"></div>
        <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded-md w-3/5"></div>
      </div>

      <div class="flex items-center gap-2.5 py-1">
        <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 shrink-0"></div>
        <div class="h-3.5 bg-slate-200 dark:bg-slate-700 rounded w-1/3"></div>
      </div>

      <div class="space-y-2">
        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-full"></div>
        <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-2/3"></div>
      </div>

      <!-- Progress bar skeleton -->
      <div class="space-y-1.5 pt-2">
        <div class="flex justify-between">
          <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-16"></div>
          <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-8"></div>
        </div>
        <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full w-full"></div>
      </div>

      <!-- Bottom action skeleton -->
      <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
        <div class="h-3.5 bg-slate-200 dark:bg-slate-700 rounded w-28"></div>
        <div class="h-9 bg-slate-200 dark:bg-slate-700 rounded-xl w-32"></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}
.skeleton-shimmer {
  animation: shimmer 1.8s infinite;
}
</style>
