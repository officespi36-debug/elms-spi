<script setup lang="ts">
import { ChevronLeft, ChevronRight, Lock, BookOpen, PlayCircle, FlaskConical } from 'lucide-vue-next'

const props = defineProps<{
  title: string
  courseTitle: string
  teacherName?: string
  lessonType?: string
  hasPrevious: boolean
  hasNext: boolean
  isNextLocked: boolean
}>()

const emit = defineEmits<{
  (e: 'previous'): void
  (e: 'next'): void
}>()

const nextTooltip = 'Complete this lesson to unlock the next one (បញ្ចប់មេរៀននេះដើម្បីដោះសោមេរៀនបន្ទាប់)'
</script>

<template>
  <header
    class="bg-white dark:bg-slate-900/95 border border-slate-200/90 dark:border-slate-800 rounded-2xl px-4 sm:px-5 py-3.5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-all duration-200 ease-[cubic-bezier(0.22,1,0.36,1)]"
  >
    <div class="flex items-center gap-3.5 min-w-0">
      <div
        class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/40 dark:to-indigo-900/30 border border-purple-200/60 dark:border-purple-700/40 text-purple-600 dark:text-purple-300 flex items-center justify-center shrink-0 shadow-2xs"
        aria-hidden="true"
      >
        <FlaskConical v-if="lessonType === 'lab'" class="w-5 h-5" />
        <PlayCircle v-else-if="lessonType === 'video'" class="w-5 h-5" />
        <BookOpen v-else class="w-5 h-5" />
      </div>

      <div class="min-w-0">
        <h1 class="font-khmer-sans text-base sm:text-lg font-bold text-slate-900 dark:text-white leading-[1.6] truncate">
          {{ title }}
        </h1>
        <p class="text-xs sm:text-[13px] text-slate-500 dark:text-slate-400 leading-[1.6] truncate">
          <span>{{ courseTitle }}</span>
          <span v-if="teacherName" class="mx-1.5 text-slate-300 dark:text-slate-600">•</span>
          <span v-if="teacherName">{{ teacherName }}</span>
        </p>
      </div>
    </div>

    <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
      <button
        type="button"
        @click="emit('previous')"
        :disabled="!hasPrevious"
        aria-label="Previous lesson"
        class="min-h-[44px] px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/90 text-slate-700 dark:text-slate-200 text-xs font-semibold flex items-center gap-1.5 transition-all duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
        :class="hasPrevious ? 'hover:bg-slate-50 dark:hover:bg-slate-700 hover:-translate-y-0.5 active:scale-[0.98] cursor-pointer' : 'opacity-45 cursor-not-allowed'"
      >
        <ChevronLeft class="w-4 h-4" />
        <span>Previous</span>
      </button>

      <div class="relative group">
        <button
          type="button"
          @click="!isNextLocked && hasNext && emit('next')"
          :disabled="!hasNext || isNextLocked"
          :aria-label="isNextLocked ? nextTooltip : 'Next lesson'"
          :title="isNextLocked ? nextTooltip : ''"
          class="min-h-[44px] px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
          :class="
            !hasNext || isNextLocked
              ? 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 cursor-not-allowed'
              : 'bg-gradient-to-r from-[#7c3aed] to-[#6366f1] text-white shadow-md shadow-purple-600/20 hover:-translate-y-0.5 active:scale-[0.98] cursor-pointer'
          "
        >
          <Lock v-if="isNextLocked && hasNext" class="w-3.5 h-3.5 text-slate-400" />
          <span>Next</span>
          <ChevronRight class="w-4 h-4" />
        </button>

        <div
          v-if="isNextLocked && hasNext"
          role="tooltip"
          class="pointer-events-none opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-150 absolute right-0 top-full mt-2 w-64 p-2.5 rounded-xl bg-slate-900 text-white text-[11px] leading-[1.6] shadow-xl border border-slate-700 z-30"
        >
          <p class="font-semibold">Complete this lesson to unlock the next one</p>
          <p class="text-slate-300 mt-0.5">បញ្ចប់មេរៀននេះដើម្បីដោះសោមេរៀនបន្ទាប់</p>
        </div>
      </div>
    </div>
  </header>
</template>
