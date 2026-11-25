<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { ChevronDown, Check, Lock, Play, BookOpen, FlaskConical, ListTree, X } from 'lucide-vue-next'
import ProgressRing from './ProgressRing.vue'

interface SidebarLesson {
  id: number | string
  title: string
  kh_title?: string
  type?: string
  duration_formatted?: string
  is_completed: boolean
  is_locked: boolean
}

interface SidebarChapter {
  id: number
  title: string
  kh_title?: string
  progress: string
  is_locked?: boolean
  expanded?: boolean
  lessons: SidebarLesson[]
}

const props = defineProps<{
  chapters: SidebarChapter[]
  currentLessonId: number | string
  progressPercent: number
  completedCount: number
  totalCount: number
}>()

const emit = defineEmits<{
  (e: 'toggle-chapter', chapter: SidebarChapter): void
  (e: 'toggle-expand-all'): void
  (e: 'select-lesson', lesson: SidebarLesson): void
}>()

const mobileDrawerOpen = ref(false)
const lockedTooltip = 'Complete the previous unit first (បញ្ចប់មេរៀនមុនជាមុនសិន)'

const scrollActiveIntoView = async () => {
  await nextTick()
  const el = document.getElementById(`sidebar-lesson-${props.currentLessonId}`)
  if (el) {
    el.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  }
}

onMounted(scrollActiveIntoView)

const handleSelect = (lesson: SidebarLesson) => {
  if (lesson.is_locked) return
  mobileDrawerOpen.value = false
  emit('select-lesson', lesson)
}
</script>

<template>
  <!-- Desktop Sticky Sidebar -->
  <aside class="hidden lg:block lg:sticky lg:top-24 space-y-5" aria-label="Course progress and curriculum">
    <ProgressRing
      :percent="progressPercent"
      :completed-count="completedCount"
      :total-count="totalCount"
    />

    <div class="bg-white dark:bg-slate-900/95 border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 shadow-sm space-y-4">
      <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
        <h2 class="text-base font-bold text-slate-900 dark:text-white">Course Content</h2>
        <button
          type="button"
          @click="emit('toggle-expand-all')"
          class="text-xs font-semibold text-purple-600 dark:text-purple-400 hover:underline cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 rounded px-1"
        >
          {{ chapters.length > 0 && chapters.every(c => c.expanded) ? 'Collapse All' : 'Expand All' }}
        </button>
      </div>

      <div class="space-y-2.5 max-h-[60vh] overflow-y-auto pr-1">
        <div
          v-for="chap in chapters.filter(c => c.lessons && c.lessons.length > 0)"
          :key="chap.id"
          class="rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 overflow-hidden"
        >
          <button
            type="button"
            @click="emit('toggle-chapter', chap)"
            :aria-expanded="!!chap.expanded"
            class="w-full min-h-[44px] px-3.5 py-2.5 flex items-center justify-between gap-2 text-left text-xs font-bold text-slate-800 dark:text-slate-100 hover:bg-slate-100/80 dark:hover:bg-slate-800/60 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
          >
            <span class="truncate leading-[1.6]">{{ chap.kh_title || chap.title }}</span>
            <span class="flex items-center gap-2 shrink-0">
              <span class="px-2 py-0.5 rounded-full bg-purple-100/80 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 text-[11px] font-bold tabular-nums">
                {{ chap.progress }}
              </span>
              <ChevronDown
                class="w-4 h-4 text-slate-500 transition-transform duration-200 ease-[cubic-bezier(0.22,1,0.36,1)]"
                :class="chap.expanded ? 'rotate-180' : 'rotate-0'"
              />
            </span>
          </button>

          <!-- Smooth CSS grid height animation -->
          <div
            class="grid transition-[grid-template-rows] duration-250 ease-[cubic-bezier(0.22,1,0.36,1)]"
            :class="chap.expanded ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
          >
            <div class="overflow-hidden">
              <ul class="border-t border-slate-200/80 dark:border-slate-800 p-2 space-y-1 bg-white dark:bg-slate-950/90">
                <li v-for="lsn in chap.lessons" :key="lsn.id">
                  <button
                    :id="`sidebar-lesson-${lsn.id}`"
                    type="button"
                    @click="handleSelect(lsn)"
                    :disabled="lsn.is_locked"
                    :aria-current="lsn.id === currentLessonId ? 'step' : undefined"
                    :title="lsn.is_locked ? lockedTooltip : ''"
                    class="w-full min-h-[44px] px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between gap-2 transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                    :class="[
                      lsn.id === currentLessonId
                        ? 'bg-purple-50 dark:bg-purple-900/30 border-l-4 border-l-purple-600 text-purple-900 dark:text-white font-bold'
                        : lsn.is_locked
                          ? 'text-slate-500 dark:text-slate-400 opacity-75 cursor-not-allowed'
                          : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-900 cursor-pointer'
                    ]"
                  >
                    <span class="flex items-center gap-2.5 min-w-0">
                      <span
                        v-if="lsn.is_completed"
                        class="w-5 h-5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0"
                      >
                        <Check class="w-3.5 h-3.5" />
                      </span>
                      <Lock v-else-if="lsn.is_locked" class="w-4 h-4 text-slate-400 dark:text-slate-500 shrink-0" />
                      <FlaskConical v-else-if="lsn.type === 'lab'" class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0" />
                      <Play v-else-if="lsn.type === 'video'" class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0" />
                      <BookOpen v-else class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0" />

                      <span class="truncate leading-[1.6]">{{ lsn.kh_title || lsn.title }}</span>
                    </span>

                    <span v-if="lsn.duration_formatted" class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums shrink-0">
                      {{ lsn.duration_formatted }}
                    </span>
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </aside>

  <!-- Mobile Floating Course Content Button + Bottom-Sheet Drawer -->
  <div class="lg:hidden">
    <button
      type="button"
      @click="mobileDrawerOpen = true"
      aria-label="Open course content drawer"
      class="fixed bottom-20 left-4 z-30 min-h-[44px] px-4 py-2.5 rounded-full bg-slate-900 dark:bg-purple-600 text-white text-xs font-bold shadow-xl flex items-center gap-2 cursor-pointer"
    >
      <ListTree class="w-4 h-4" />
      <span>Course Content ({{ completedCount }}/{{ totalCount }})</span>
    </button>

    <Transition
      enter-active-class="transition duration-250 ease-[cubic-bezier(0.22,1,0.36,1)]"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="mobileDrawerOpen"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-end"
        @click.self="mobileDrawerOpen = false"
      >
        <div class="w-full max-h-[82vh] bg-white dark:bg-slate-900 rounded-t-3xl p-5 space-y-4 overflow-y-auto shadow-2xl">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Course Content</h2>
            <button
              type="button"
              @click="mobileDrawerOpen = false"
              aria-label="Close course content"
              class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <ProgressRing
            :percent="progressPercent"
            :completed-count="completedCount"
            :total-count="totalCount"
          />

          <div class="space-y-2.5">
            <div
              v-for="chap in chapters.filter(c => c.lessons && c.lessons.length > 0)"
              :key="`mob-${chap.id}`"
              class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden"
            >
              <div class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between text-xs font-bold">
                <span>{{ chap.kh_title || chap.title }}</span>
                <span class="text-purple-600 dark:text-purple-400 tabular-nums">{{ chap.progress }}</span>
              </div>
              <ul class="p-2 space-y-1">
                <li v-for="lsn in chap.lessons" :key="`mob-lsn-${lsn.id}`">
                  <button
                    type="button"
                    @click="handleSelect(lsn)"
                    :disabled="lsn.is_locked"
                    class="w-full min-h-[44px] px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between gap-2"
                    :class="
                      lsn.id === currentLessonId
                        ? 'bg-purple-50 dark:bg-purple-900/30 border-l-4 border-l-purple-600 font-bold text-purple-900 dark:text-white'
                        : lsn.is_locked
                          ? 'text-slate-500 opacity-75'
                          : 'text-slate-700 dark:text-slate-200'
                    "
                  >
                    <span class="truncate">{{ lsn.kh_title || lsn.title }}</span>
                    <Check v-if="lsn.is_completed" class="w-4 h-4 text-emerald-500 shrink-0" />
                    <Lock v-else-if="lsn.is_locked" class="w-4 h-4 text-slate-400 shrink-0" />
                  </button>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>
