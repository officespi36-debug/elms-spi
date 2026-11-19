<template>
  <StudentLayout>
    <div class="min-h-screen bg-[#F8FAFC] dark:bg-[#0F172A] text-slate-800 dark:text-slate-100 py-6 px-4 sm:px-6 lg:px-8 font-sans transition-colors duration-200">
      <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Header & Breadcrumb -->
        <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200/80 dark:border-slate-800">
          <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
              <Link href="/student/simulations" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">ស៊ីមុយឡាទ័រ</Link>
              <span>/</span>
              <span class="text-purple-600 dark:text-purple-400 font-semibold">កំពុងអនុវត្ត</span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
              {{ simulation.title_kh || simulation.title }}
            </h1>
          </div>

          <div class="flex items-center gap-3">
            <div class="text-right">
              <span class="text-[11px] text-slate-400 dark:text-slate-500 block">ពិន្ទុបច្ចុប្បន្ន</span>
              <span class="text-lg font-extrabold text-purple-600 dark:text-purple-400">{{ currentScore }} pts</span>
            </div>
            <Link
              href="/student/simulations"
              class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-200/80 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 transition-colors"
            >
              ចាកចេញ
            </Link>
          </div>
        </div>

        <!-- Step Progress Bar -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <span>ដំណើរការអនុវត្ត (Attempt #{{ attempt.attempt_no }})</span>
            <span class="font-semibold text-purple-600 dark:text-purple-400">ជំហានទី {{ stepIndex }}</span>
          </div>
          <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
            <div
              class="h-full bg-gradient-to-r from-purple-500 to-indigo-600 rounded-full transition-all duration-300"
              :style="{ width: `${progressPercent}%` }"
            ></div>
          </div>
        </div>

        <!-- Scenario Card Container -->
        <div v-if="currentNode" class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden transition-all duration-200">
          
          <!-- Scenario Scene Header -->
          <div class="p-6 sm:p-8 space-y-4">
            <div class="flex items-center gap-2">
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                {{ currentNode.title || `ស្ថានភាពជំហានទី ${stepIndex}` }}
              </span>
            </div>

            <!-- Scene Body (Markdown / Prose) -->
            <div class="prose prose-slate dark:prose-invert max-w-none text-sm sm:text-base leading-relaxed text-slate-800 dark:text-slate-200 whitespace-pre-line">
              {{ currentNode.body }}
            </div>

            <!-- Optional Media -->
            <div v-if="currentNode.media_path" class="rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 max-h-80">
              <img :src="currentNode.media_path" alt="Scenario context" class="w-full h-full object-cover" />
            </div>
          </div>

          <!-- Immediate Choice Feedback Banner (Shows after decision is made) -->
          <div
            v-if="stepFeedback"
            class="p-6 mx-6 sm:mx-8 mb-6 rounded-2xl bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/60 animate-in fade-in slide-in-from-top-3 duration-200 space-y-3"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
                <span>💡</span>
                <span>មតិកែលម្អលើការសម្រេចចិត្ត (Feedback)</span>
              </span>
              <span class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-950/60 px-2.5 py-1 rounded-lg">
                +{{ lastAwardedPoints }} ពិន្ទុ
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
              {{ stepFeedback }}
            </p>

            <div class="pt-2 flex justify-end">
              <button
                @click="advanceToNextStep"
                type="button"
                class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-white bg-purple-600 hover:bg-purple-700 active:scale-[0.98] transition-all shadow-xs flex items-center gap-2 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
              >
                <span>{{ isCompleted ? 'បញ្ចប់ & មើលលទ្ធផលសង្ខេប' : 'បន្តទៅជំហានបន្ទាប់' }}</span>
                <span>→</span>
              </button>
            </div>
          </div>

          <!-- Decision Choices (Before selection) -->
          <div v-else class="p-6 sm:p-8 pt-0 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
              <span>🎯</span>
              <span>ចូរជ្រើសរើសការឆ្លើយតបដ៏ល្អបំផុត (ចុចលេខ 1-{{ currentNode.choices?.length || 4 }} លើក្តារចុច)៖</span>
            </h4>

            <div class="space-y-3">
              <button
                v-for="(choice, idx) in currentNode.choices"
                :key="choice.id"
                @click="submitChoice(choice.id)"
                type="button"
                :disabled="isSubmitting"
                class="w-full min-h-[52px] p-4 rounded-2xl border text-left flex items-center gap-3.5 transition-all duration-150 cursor-pointer group bg-slate-50/70 hover:bg-white dark:bg-slate-800/50 dark:hover:bg-slate-800 border-slate-200/80 dark:border-slate-700 hover:border-purple-400 dark:hover:border-purple-500 hover:shadow-xs focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
              >
                <!-- Key shortcut pill -->
                <span class="w-8 h-8 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-xs font-bold text-slate-700 dark:text-slate-200 flex items-center justify-center shrink-0 group-hover:bg-purple-600 group-hover:text-white group-hover:border-purple-600 transition-colors">
                  {{ idx + 1 }}
                </span>
                
                <!-- Choice Text -->
                <span class="text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200 group-hover:text-purple-700 dark:group-hover:text-purple-300 leading-snug flex-1">
                  {{ choice.label }}
                </span>

                <svg class="w-4 h-4 text-slate-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 shrink-0 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          </div>

        </div>

        <!-- End of Simulation State (Redirecting) -->
        <div v-else class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center space-y-4">
          <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto text-2xl">
            🎉
          </div>
          <h3 class="text-xl font-bold text-slate-900 dark:text-white">ការអនុវត្តបានបញ្ចប់!</h3>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
            កំពុងរៀបចំរបាយការណ៍សង្ខេបពិន្ទុ និងការវិភាគការសម្រេចចិត្តរបស់អ្នក...
          </p>
          <Link
            :href="`/student/simulation-attempts/${attempt.id}/summary`"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-xs sm:text-sm font-bold text-white bg-purple-600 hover:bg-purple-700 transition-all"
          >
            មើលរបាយការណ៍សង្ខេបភ្លាមៗ →
          </Link>
        </div>

      </div>
    </div>
  </StudentLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface Choice {
  id: number
  label: string
}

interface NodePayload {
  id: number
  title: string | null
  body: string
  media_path: string | null
  is_end: boolean
  choices: Choice[]
}

const props = defineProps<{
  simulation: {
    id: number
    title: string
    title_kh: string | null
    skill: string
    kind: string
    pass_score: number
    estimated_minutes: number
  }
  attempt: {
    id: number
    attempt_no: number
    score: number
    status: string
    steps_count: number
  }
  node: NodePayload | null
}>()

const currentNode = ref<NodePayload | null>(props.node)
const stepIndex = ref(props.attempt.steps_count + 1)
const currentScore = ref(props.attempt.score)
const isSubmitting = ref(false)
const stepFeedback = ref<string | null>(null)
const lastAwardedPoints = ref<number>(0)
const isCompleted = ref(props.attempt.status === 'completed')
const nextPendingNode = ref<NodePayload | null>(null)

const progressPercent = computed(() => {
  if (isCompleted.value) return 100
  // Estimated progress based on steps
  return Math.min(95, Math.max(15, (stepIndex.value / 5) * 100))
})

const submitChoice = async (choiceId: number) => {
  if (isSubmitting.value) return
  isSubmitting.value = true

  try {
    const res = await window.fetch(`/student/simulation-attempts/${props.attempt.id}/choose`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({ choice_id: choiceId }),
    })

    if (!res.ok) {
      throw new Error('Failed to submit choice')
    }

    const data = await res.json()
    stepFeedback.value = data.feedback || 'ការសម្រេចចិត្តត្រូវបានកត់ត្រា។'
    lastAwardedPoints.value = data.points_awarded || 0
    currentScore.value = data.current_score || currentScore.value + data.points_awarded
    isCompleted.value = Boolean(data.done)
    nextPendingNode.value = data.node
  } catch (err) {
    console.error('Submission error:', err)
  } finally {
    isSubmitting.value = false
  }
}

const advanceToNextStep = () => {
  if (isCompleted.value) {
    router.visit(`/student/simulation-attempts/${props.attempt.id}/summary`)
    return
  }

  currentNode.value = nextPendingNode.value
  stepFeedback.value = null
  stepIndex.value += 1
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Keyboard shortcuts (1, 2, 3, 4, Enter)
const handleKeyDown = (e: KeyboardEvent) => {
  if (stepFeedback.value) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault()
      advanceToNextStep()
    }
    return
  }

  if (currentNode.value?.choices && !isSubmitting.value) {
    const num = parseInt(e.key, 10)
    if (num >= 1 && num <= currentNode.value.choices.length) {
      e.preventDefault()
      const targetChoice = currentNode.value.choices[num - 1]
      if (targetChoice) {
        submitChoice(targetChoice.id)
      }
    }
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})
</script>
