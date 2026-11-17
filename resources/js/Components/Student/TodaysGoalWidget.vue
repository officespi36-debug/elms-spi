<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

export interface GoalItem {
  id: string
  title: string
  titleKm?: string
  isCompleted: boolean
  targetRoute?: string
  createdAt: number
}

interface LegacyGoalPayload {
  items?: Array<{
    id?: string | number
    titleEn?: string
    titleKm?: string
    title?: string
    completed?: boolean
    isCompleted?: boolean
    href?: string
    targetRoute?: string
    createdAt?: number
  }>
}

const props = withDefaults(
  defineProps<{
    initialGoals?: LegacyGoalPayload | GoalItem[] | null
    userId?: string | number | null
    currentLang?: 'km' | 'en'
    isLoading?: boolean
    onOpenTask?: (task: GoalItem) => void
  }>(),
  {
    initialGoals: null,
    userId: null,
    currentLang: 'en',
    isLoading: false,
    onOpenTask: undefined,
  }
)

const emit = defineEmits<{
  (e: 'open-task', task: GoalItem): void
}>()

const mapGoalPayload = (payload: LegacyGoalPayload | GoalItem[] | null | undefined): GoalItem[] => {
  if (Array.isArray(payload)) {
    return payload.map((item, index) => ({
      id: String(item.id ?? index),
      title: String(item.title ?? ''),
      titleKm: item.titleKm ? String(item.titleKm) : undefined,
      isCompleted: Boolean(item.isCompleted),
      targetRoute: item.targetRoute || '/student/my-courses/enrolled',
      createdAt: Number(item.createdAt || Date.now()),
    }))
  }

  if (payload && Array.isArray(payload.items)) {
    return payload.items.map((item, index) => ({
      id: String(item.id ?? index),
      title: item.titleEn || item.title || item.titleKm || '',
      titleKm: item.titleKm || item.titleEn || item.title,
      isCompleted: Boolean(item.isCompleted ?? item.completed),
      targetRoute: item.targetRoute || item.href || '/student/my-courses/enrolled',
      createdAt: Number(item.createdAt || Date.now()),
    }))
  }

  return []
}

const goals = ref<GoalItem[]>(mapGoalPayload(props.initialGoals))
const isAddingGoal = ref(false)
const isSaving = ref(false)
const pendingIds = ref<Record<string, boolean>>({})
const goalError = ref<string | null>(null)
const newGoalTitle = ref('')
const goalInputRef = ref<HTMLInputElement | null>(null)

const getCsrfHeaders = (): Record<string, string> => {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }
  if (typeof document !== 'undefined') {
    const meta = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content
    if (meta) {
      headers['X-CSRF-TOKEN'] = meta
    } else {
      const xsrfMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
      if (xsrfMatch) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfMatch[1])
      }
    }
  }
  return headers
}

const syncFromProps = () => {
  goals.value = mapGoalPayload(props.initialGoals)
}

onMounted(() => {
  syncFromProps()
})

watch(
  () => props.initialGoals,
  () => {
    syncFromProps()
  },
  { deep: true }
)

const totalCount = computed(() => goals.value.length)
const completedCount = computed(() => goals.value.filter(g => g.isCompleted).length)
const progressPercent = computed(() =>
  totalCount.value === 0 ? 0 : Math.round((completedCount.value / totalCount.value) * 100)
)

const toggleGoal = async (goalId: string) => {
  const target = goals.value.find(g => g.id === goalId)
  if (!target || pendingIds.value[goalId]) return

  goalError.value = null
  const previous = target.isCompleted
  target.isCompleted = !previous
  pendingIds.value[goalId] = true

  try {
    const response = await fetch(`/student/goals/${encodeURIComponent(goalId)}`, {
      method: 'PATCH',
      credentials: 'same-origin',
      headers: getCsrfHeaders(),
      body: JSON.stringify({ is_completed: target.isCompleted }),
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const data = await response.json().catch(() => null)
    if (data?.todayGoals) {
      goals.value = mapGoalPayload(data.todayGoals)
    }
  } catch {
    target.isCompleted = previous
    goalError.value = props.currentLang === 'km'
      ? 'មិនអាចរក្សាទុកការផ្លាស់ប្តូរគោលដៅបានទេ'
      : 'Could not update study goal'
  } finally {
    pendingIds.value[goalId] = false
  }
}

const deleteGoal = async (goalId: string) => {
  if (pendingIds.value[goalId]) return
  goalError.value = null
  const snapshot = [...goals.value]
  goals.value = goals.value.filter(g => g.id !== goalId)
  pendingIds.value[goalId] = true

  try {
    const response = await fetch(`/student/goals/${encodeURIComponent(goalId)}`, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: getCsrfHeaders(),
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const data = await response.json().catch(() => null)
    if (data?.todayGoals) {
      goals.value = mapGoalPayload(data.todayGoals)
    }
  } catch {
    goals.value = snapshot
    goalError.value = props.currentLang === 'km'
      ? 'មិនអាចលុបគោលដៅបានទេ'
      : 'Could not delete study goal'
  } finally {
    pendingIds.value[goalId] = false
  }
}

const openAddInput = async () => {
  isAddingGoal.value = !isAddingGoal.value
  goalError.value = null
  if (isAddingGoal.value) {
    await nextTick()
    goalInputRef.value?.focus()
  } else {
    newGoalTitle.value = ''
  }
}

const cancelAddGoal = () => {
  newGoalTitle.value = ''
  goalError.value = null
  isAddingGoal.value = false
}

const saveNewGoal = async () => {
  const trimmed = newGoalTitle.value.trim()
  if (!trimmed || isSaving.value) return

  isSaving.value = true
  goalError.value = null

  try {
    const response = await fetch('/student/goals', {
      method: 'POST',
      credentials: 'same-origin',
      headers: getCsrfHeaders(),
      body: JSON.stringify({
        title: trimmed,
        title_km: trimmed,
        target_route: '/student/my-courses/enrolled',
      }),
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const data = await response.json().catch(() => null)
    if (data?.todayGoals) {
      goals.value = mapGoalPayload(data.todayGoals)
    } else if (data?.goal) {
      goals.value.push(data.goal)
    }

    newGoalTitle.value = ''
    isAddingGoal.value = false
  } catch {
    goalError.value = props.currentLang === 'km'
      ? 'មិនអាចរក្សាទុកគោលដៅទៅ Database បានទេ'
      : 'Could not save study goal to database'
  } finally {
    isSaving.value = false
  }
}

const handleOpenTask = (task: GoalItem) => {
  if (props.onOpenTask) {
    props.onOpenTask(task)
    return
  }
  emit('open-task', task)
  if (task.targetRoute) {
    router.visit(task.targetRoute)
  }
}
</script>

<template>
  <div
    class="bg-white dark:bg-[#1e1f20] border border-[#E5E7EB] dark:border-[#2d2f31] rounded-2xl p-5 shadow-sm transition-all"
    :class="currentLang === 'km' ? 'font-[\'Kantumruy_Pro\',sans-serif]' : 'font-[\'Inter\',sans-serif]'"
  >
    <!-- Loading Skeleton State -->
    <div v-if="isLoading" class="space-y-4 animate-pulse" aria-busy="true" aria-label="Loading today's goals">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-2 flex-1">
          <div class="h-4 w-28 bg-slate-200 dark:bg-slate-700 rounded"></div>
          <div class="h-3 w-44 bg-slate-100 dark:bg-slate-800 rounded"></div>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-6 w-16 bg-emerald-50 dark:bg-emerald-900/30 rounded-full"></div>
          <div class="h-7 w-7 bg-indigo-50 dark:bg-indigo-900/30 rounded-full"></div>
        </div>
      </div>
      <div class="space-y-1.5">
        <div class="h-3 w-20 ml-auto bg-slate-100 dark:bg-slate-800 rounded"></div>
        <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full"></div>
      </div>
      <div class="space-y-2.5 pt-1">
        <div class="h-12 w-full bg-slate-100 dark:bg-slate-800 rounded-xl"></div>
        <div class="h-12 w-full bg-slate-100 dark:bg-slate-800 rounded-xl"></div>
      </div>
    </div>

    <!-- Active Widget Content -->
    <template v-else>
      <!-- Header Row -->
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 leading-snug">
            {{ currentLang === 'km' ? 'គោលដៅថ្ងៃនេះ' : "Today's goal" }}
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
            {{ currentLang === 'km' ? 'រក្សាសន្ទុះនៃការសិក្សារបស់អ្នកឱ្យបន្តទៅមុខ!' : 'Keep your study momentum going!' }}
          </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <span
            class="bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-semibold px-2.5 py-1 rounded-full tabular-nums whitespace-nowrap"
          >
            {{ currentLang === 'km' ? `រួចរាល់ ${completedCount}/${totalCount}` : `${completedCount}/${totalCount} Done` }}
          </span>
          <button
            @click="openAddInput"
            type="button"
            class="w-7 h-7 rounded-full bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-500/30 flex items-center justify-center transition cursor-pointer shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
            :title="currentLang === 'km' ? 'បន្ថែមគោលដៅថ្មី' : 'Add new study goal'"
            :aria-expanded="isAddingGoal"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Inline Compact Add Goal Input -->
      <div
        v-if="isAddingGoal"
        class="mt-3 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-indigo-100 dark:border-indigo-500/30 space-y-2"
      >
        <input
          ref="goalInputRef"
          v-model="newGoalTitle"
          type="text"
          maxlength="120"
          :placeholder="currentLang === 'km' ? 'បញ្ចូលគោលដៅសិក្សាថ្ងៃនេះ...' : 'Add a study target for today...'"
          class="w-full px-3 py-1.5 rounded-lg bg-white dark:bg-[#1e1f20] border border-slate-200 dark:border-slate-700 text-xs sm:text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          @keydown.enter.prevent="saveNewGoal"
          @keydown.esc.prevent="cancelAddGoal"
        />
        <div class="flex items-center justify-end gap-2">
          <button
            @click="cancelAddGoal"
            type="button"
            class="px-2.5 py-1 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 cursor-pointer transition"
          >
            {{ currentLang === 'km' ? 'បោះបង់' : 'Cancel' }}
          </button>
          <button
            @click="saveNewGoal"
            type="button"
            :disabled="!newGoalTitle.trim() || isSaving"
            class="px-3 py-1 rounded-lg bg-gradient-to-r from-[#2563EB] via-[#4F46E5] to-[#7C3AED] text-white text-xs font-semibold shadow-xs hover:opacity-95 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition"
          >
            {{ isSaving ? (currentLang === 'km' ? 'កំពុងរក្សាទុក...' : 'Saving...') : (currentLang === 'km' ? 'រក្សាទុក' : 'Save') }}
          </button>
        </div>
      </div>

      <p
        v-if="goalError"
        role="alert"
        class="mt-2.5 text-xs font-medium text-rose-600 dark:text-rose-400"
      >
        {{ goalError }}
      </p>

      <!-- Progress Section -->
      <div class="mt-4">
        <div class="text-right text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5 tabular-nums">
          {{ currentLang === 'km' ? `សម្រេចបាន ${progressPercent}%` : `${progressPercent}% completed` }}
        </div>
        <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-[#2563EB] via-[#4F46E5] to-[#7C3AED] rounded-full transition-all duration-300 ease-out"
            :style="{ width: `${progressPercent}%` }"
          ></div>
        </div>
      </div>

      <!-- Task List Items -->
      <div v-if="goals.length > 0" class="space-y-2.5 mt-4">
        <div
          v-for="item in goals"
          :key="item.id"
          class="group p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:border-indigo-100 dark:hover:border-indigo-500/30 bg-slate-50/50 dark:bg-slate-900/40 hover:bg-white dark:hover:bg-slate-900 transition flex items-center justify-between gap-3"
        >
          <button
            @click="toggleGoal(item.id)"
            type="button"
            class="flex items-center gap-3 text-left flex-1 min-w-0 cursor-pointer focus:outline-none"
          >
            <span
              v-if="item.isCompleted"
              class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-2xs transition-transform duration-200 scale-100"
            >
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
            </span>
            <span
              v-else
              class="w-5 h-5 rounded-full border-2 border-slate-300 dark:border-slate-600 hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors shrink-0"
            ></span>

            <span
              :class="
                item.isCompleted
                  ? 'line-through text-slate-400 dark:text-slate-500'
                  : 'text-slate-700 dark:text-slate-200 font-medium'
              "
              class="text-xs sm:text-sm leading-snug break-words transition-colors duration-200"
            >
              {{ currentLang === 'km' ? (item.titleKm || item.title) : item.title }}
            </span>
          </button>

          <div class="flex items-center gap-1.5 shrink-0">
            <button
              @click="handleOpenTask(item)"
              type="button"
              class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 shrink-0 inline-flex items-center gap-0.5 cursor-pointer"
            >
              <span>{{ currentLang === 'km' ? 'បើក' : 'Open' }}</span>
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </button>
            <button
              @click="deleteGoal(item.id)"
              type="button"
              class="opacity-0 group-hover:opacity-100 focus:opacity-100 p-1 rounded-md text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer"
              :title="currentLang === 'km' ? 'លុប' : 'Delete goal'"
              :aria-label="currentLang === 'km' ? 'លុបគោលដៅ' : 'Delete goal'"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="mt-4 py-6 px-4 rounded-xl border border-dashed border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900/30 text-center space-y-2"
      >
        <p class="text-xs font-medium text-slate-600 dark:text-slate-300">
          {{ currentLang === 'km' ? 'មិនទាន់មានគោលដៅសិក្សាសម្រាប់ថ្ងៃនេះនៅឡើយទេ។' : 'No study goals set for today yet.' }}
        </p>
        <p class="text-[11px] text-slate-400 dark:text-slate-500">
          {{ currentLang === 'km' ? 'សូមចុចប៊ូតុង + ខាងលើដើម្បីបន្ថែមគោលដៅសិក្សាប្រចាំថ្ងៃរបស់អ្នក។' : 'Click the + button above to add a daily study target.' }}
        </p>
      </div>
    </template>
  </div>
</template>
