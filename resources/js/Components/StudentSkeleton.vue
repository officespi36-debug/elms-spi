<script setup lang="ts">
withDefaults(
  defineProps<{
    type?: 'stat-card' | 'course-card' | 'list-row' | 'chart' | 'text'
    count?: number
  }>(),
  {
    type: 'stat-card',
    count: 1,
  }
)
</script>

<template>
  <div class="w-full">
    <!-- 1. Stat Card Skeleton -->
    <div
      v-if="type === 'stat-card'"
      class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4"
    >
      <div
        v-for="i in count"
        :key="i"
        class="relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-2xs space-y-3"
      >
        <div class="flex items-center justify-between">
          <div class="h-3 w-16 bg-slate-200 dark:bg-slate-800 rounded-md animate-pulse"></div>
          <div class="w-7 h-7 rounded-xl bg-slate-200 dark:bg-slate-800 animate-pulse"></div>
        </div>
        <div class="h-7 w-20 bg-slate-200 dark:bg-slate-800 rounded-lg animate-pulse"></div>
        <div class="h-2 w-24 bg-slate-100 dark:bg-slate-800/60 rounded animate-pulse"></div>
      </div>
    </div>

    <!-- 2. Course Card Skeleton -->
    <div
      v-else-if="type === 'course-card'"
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"
    >
      <div
        v-for="i in count"
        :key="i"
        class="overflow-hidden rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-4 p-4"
      >
        <div class="w-full aspect-video rounded-2xl bg-slate-200 dark:bg-slate-800 animate-pulse"></div>
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <div class="h-4 w-20 rounded-full bg-slate-200 dark:bg-slate-800 animate-pulse"></div>
            <div class="h-3 w-12 rounded bg-slate-100 dark:bg-slate-800 animate-pulse"></div>
          </div>
          <div class="h-5 w-3/4 rounded bg-slate-200 dark:bg-slate-800 animate-pulse"></div>
          <div class="h-3 w-1/2 rounded bg-slate-100 dark:bg-slate-800 animate-pulse"></div>
        </div>
        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 animate-pulse"></div>
        <div class="flex items-center justify-between pt-2">
          <div class="h-8 w-28 rounded-xl bg-slate-200 dark:bg-slate-800 animate-pulse"></div>
          <div class="h-8 w-24 rounded-xl bg-slate-100 dark:bg-slate-800 animate-pulse"></div>
        </div>
      </div>
    </div>

    <!-- 3. List Row Skeleton -->
    <div
      v-else-if="type === 'list-row'"
      class="space-y-3"
    >
      <div
        v-for="i in count"
        :key="i"
        class="p-4 rounded-2xl bg-white dark:bg-slate-900/70 border border-slate-200/80 dark:border-slate-800 shadow-2xs flex items-center justify-between gap-4"
      >
        <div class="flex items-center gap-3.5 min-w-0 flex-1">
          <div class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-800 animate-pulse shrink-0"></div>
          <div class="space-y-2 flex-1">
            <div class="h-4 w-1/3 rounded bg-slate-200 dark:bg-slate-800 animate-pulse"></div>
            <div class="h-3 w-1/4 rounded bg-slate-100 dark:bg-slate-800/70 animate-pulse"></div>
          </div>
        </div>
        <div class="h-7 w-20 rounded-xl bg-slate-100 dark:bg-slate-800 animate-pulse shrink-0"></div>
      </div>
    </div>

    <!-- 4. Chart Skeleton -->
    <div
      v-else-if="type === 'chart'"
      class="p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-6"
    >
      <div class="flex items-center justify-between">
        <div class="space-y-2">
          <div class="h-5 w-36 rounded bg-slate-200 dark:bg-slate-800 animate-pulse"></div>
          <div class="h-3 w-24 rounded bg-slate-100 dark:bg-slate-800/60 animate-pulse"></div>
        </div>
        <div class="h-8 w-24 rounded-xl bg-slate-100 dark:bg-slate-800 animate-pulse"></div>
      </div>
      <div class="h-48 w-full rounded-2xl bg-gradient-to-t from-slate-100 to-transparent dark:from-slate-800/40 dark:to-transparent flex items-end justify-between gap-3 p-4">
        <div
          v-for="bar in 7"
          :key="bar"
          class="w-full rounded-t-lg bg-slate-200 dark:bg-slate-800 animate-pulse"
          :style="{ height: `${30 + (bar * 9) % 65}%` }"
        ></div>
      </div>
    </div>

    <!-- 5. Generic Text Skeleton -->
    <div v-else class="space-y-2.5">
      <div
        v-for="i in count"
        :key="i"
        class="h-4 rounded bg-slate-200 dark:bg-slate-800 animate-pulse"
        :style="{ width: `${85 - (i * 12) % 35}%` }"
      ></div>
    </div>
  </div>
</template>
