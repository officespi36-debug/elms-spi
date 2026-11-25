<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue'

export type LessonTabId = 'content' | 'overview' | 'resources' | 'discussion'

const props = defineProps<{
  modelValue: LessonTabId
  resourcesCount: number
  discussionCount: number
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', tab: LessonTabId): void
}>()

const tabs: Array<{ id: LessonTabId; label: string }> = [
  { id: 'content', label: 'Lesson Content' },
  { id: 'overview', label: 'Overview' },
  { id: 'resources', label: 'Resources' },
  { id: 'discussion', label: 'Discussion' },
]

const tabRefs = ref<Record<string, HTMLElement | null>>({})
const indicatorStyle = ref({ left: '0px', width: '0px' })

const updateIndicator = async () => {
  await nextTick()
  const el = tabRefs.value[props.modelValue]
  if (el) {
    indicatorStyle.value = {
      left: `${el.offsetLeft}px`,
      width: `${el.offsetWidth}px`,
    }
  }
}

watch(() => props.modelValue, updateIndicator)
onMounted(updateIndicator)

const handleKeydown = (e: KeyboardEvent, currentIdx: number) => {
  if (e.key === 'ArrowRight') {
    e.preventDefault()
    const next = tabs[(currentIdx + 1) % tabs.length]
    emit('update:modelValue', next.id)
    tabRefs.value[next.id]?.focus()
  } else if (e.key === 'ArrowLeft') {
    e.preventDefault()
    const prev = tabs[(currentIdx - 1 + tabs.length) % tabs.length]
    emit('update:modelValue', prev.id)
    tabRefs.value[prev.id]?.focus()
  }
}
</script>

<template>
  <div
    class="sticky top-[68px] z-20 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 px-4 sm:px-6 pt-3 rounded-t-2xl"
  >
    <div role="tablist" aria-label="Lesson sections" class="relative flex items-center gap-6 overflow-x-auto no-scrollbar">
      <button
        v-for="(tab, idx) in tabs"
        :key="tab.id"
        :ref="(el) => { tabRefs[tab.id] = el as HTMLElement }"
        :id="`tab-${tab.id}`"
        role="tab"
        type="button"
        :aria-selected="modelValue === tab.id"
        :aria-controls="`panel-${tab.id}`"
        @click="emit('update:modelValue', tab.id)"
        @keydown="handleKeydown($event, idx)"
        class="pb-3.5 px-1 min-h-[44px] text-xs sm:text-sm whitespace-nowrap flex items-center gap-2 transition-colors duration-200 cursor-pointer focus:outline-none focus-visible:text-purple-600 dark:focus-visible:text-purple-300"
        :class="
          modelValue === tab.id
            ? 'text-purple-600 dark:text-purple-400 font-bold'
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 font-medium'
        "
      >
        <span>{{ tab.label }}</span>
        <span
          v-if="tab.id === 'resources' && resourcesCount > 0"
          class="px-2 py-0.5 rounded-full text-[11px] font-bold tabular-nums bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
        >
          {{ resourcesCount }}
        </span>
        <span
          v-if="tab.id === 'discussion' && discussionCount > 0"
          class="px-2 py-0.5 rounded-full text-[11px] font-bold tabular-nums bg-purple-50 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300"
        >
          {{ discussionCount }}
        </span>
      </button>

      <!-- Animated sliding underline -->
      <span
        aria-hidden="true"
        class="absolute bottom-0 h-[2.5px] rounded-full bg-gradient-to-r from-[#7c3aed] to-[#6366f1] transition-all duration-250 ease-[cubic-bezier(0.22,1,0.36,1)]"
        :style="indicatorStyle"
      ></span>
    </div>
  </div>
</template>
