<template>
  <StudentLayout>
    <div class="min-h-screen bg-[#F8FAFC] dark:bg-[#0F172A] text-slate-800 dark:text-slate-100 py-6 px-4 sm:px-6 lg:px-8 font-sans transition-colors duration-200">
      <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Breadcrumb & Header -->
        <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200/80 dark:border-slate-800">
          <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
              <Link href="/student/simulations" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                {{ isKm ? 'ស៊ីមុយឡាទ័រ' : 'Simulations' }}
              </Link>
              <span>/</span>
              <span class="text-purple-600 dark:text-purple-400 font-semibold">
                {{ isKm ? 'របាយការណ៍សង្ខេប' : 'Summary Report' }}
              </span>
            </nav>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
              {{ isKm ? 'របាយការណ៍លទ្ធផលសេណារីយ៉ូ' : 'Simulation Result Report' }}
            </h1>
          </div>

          <Link
            href="/student/simulations"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            {{ isKm ? '← បញ្ជីស៊ីមុយឡាទ័រ' : '← Back to Simulations' }}
          </Link>
        </div>

        <!-- Result Hero Card -->
        <div
          :class="[
            attempt.passed
              ? 'bg-gradient-to-br from-emerald-500/10 via-white to-purple-500/5 dark:from-emerald-950/40 dark:via-[#1E293B] dark:to-purple-950/20 border-emerald-300 dark:border-emerald-800/60'
              : 'bg-gradient-to-br from-rose-500/10 via-white to-amber-500/5 dark:from-rose-950/40 dark:via-[#1E293B] dark:to-amber-950/20 border-rose-300 dark:border-rose-800/60',
            'rounded-3xl border p-6 sm:p-8 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6'
          ]"
        >
          <div class="space-y-2 text-center md:text-left">
            <span
              :class="[
                attempt.passed
                  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300'
                  : 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300',
                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold tracking-wide uppercase'
              ]"
            >
              <span>{{ attempt.passed ? '🎉' : '⚠️' }}</span>
              <span>
                {{ attempt.passed 
                  ? (isKm ? 'បានឆ្លងផុតដោយជោគជ័យ (Passed)' : 'Passed Successfully') 
                  : (isKm ? 'មិនទាន់ឆ្លងផុត (Needs Practice)' : 'Needs Practice (Did not pass)') 
                }}
              </span>
            </span>

            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white">
              {{ isKm ? (simulation.title_kh || simulation.title) : (simulation.title || simulation.title_kh) }}
            </h2>

            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300">
              {{ isKm 
                ? `Attempt #${attempt.attempt_no} • បញ្ចប់នៅ៖ ${formatDate(attempt.completed_at)}` 
                : `Attempt #${attempt.attempt_no} • Completed: ${formatDate(attempt.completed_at)}` 
              }}
            </p>
          </div>

          <!-- Score Circle KPI -->
          <div class="flex flex-col items-center justify-center p-5 rounded-2xl bg-white/80 dark:bg-slate-800/80 backdrop-blur-xs border border-slate-200/80 dark:border-slate-700 min-w-[160px] text-center shadow-xs">
            <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">
              {{ isKm ? 'ពិន្ទុសម្រេចបាន' : 'Score Achieved' }}
            </span>
            <span
              :class="[
                attempt.passed ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400',
                'text-4xl font-black mt-1'
              ]"
            >
              {{ attempt.score }}
            </span>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
              {{ isKm ? `ពិន្ទុគោលដៅ ≥ ${simulation.pass_score} pts` : `Target Pass Score ≥ ${simulation.pass_score} pts` }}
            </span>
          </div>
        </div>

        <!-- Decision Path Breakdown -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <span>🧭</span>
              <span>{{ isKm ? 'ប្រវត្តិការសម្រេចចិត្តតាមជំហាន (Decision Path)' : 'Decision Path History' }}</span>
            </h3>
            <span class="text-xs text-slate-400 dark:text-slate-500">
              {{ isKm ? `សរុប ${steps.length} ជំហាន` : `Total ${steps.length} Steps` }}
            </span>
          </div>

          <div class="space-y-4">
            <div
              v-for="(step, idx) in steps"
              :key="step.step_id"
              class="bg-white dark:bg-[#1E293B] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 space-y-3.5 shadow-2xs"
            >
              <!-- Step Header -->
              <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <span class="text-xs font-bold text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-900/40 px-2.5 py-1 rounded-lg">
                  {{ isKm 
                    ? `ជំហានទី ${idx + 1}: ${step.node_title_kh || step.node_title || 'ការសម្រេចចិត្ត'}` 
                    : `Step ${idx + 1}: ${step.node_title || step.node_title_kh || 'Decision'}` 
                  }}
                </span>

                <span
                  :class="[
                    step.is_best
                      ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/40'
                      : 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border-amber-200 dark:border-amber-800/40',
                    'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border'
                  ]"
                >
                  <span>{{ step.is_best ? (isKm ? '✓ ល្អបំផុត' : '✓ Optimal') : (isKm ? 'ជម្រើសធម្មតា' : 'Sub-optimal') }}</span>
                  <span>(+{{ step.points }} pts)</span>
                </span>
              </div>

              <!-- Context Question -->
              <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2 italic">
                "{{ isKm ? (step.node_body_kh || step.node_body) : (step.node_body || step.node_body_kh) }}"
              </p>

              <!-- What student chose -->
              <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700 space-y-1">
                <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">
                  {{ isKm ? 'ចម្លើយដែលអ្នកបានជ្រើសរើស៖' : 'Your Selected Response:' }}
                </span>
                <p class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100">
                  {{ isKm ? (step.chosen_label_kh || step.chosen_label) : (step.chosen_label || step.chosen_label_kh) }}
                </p>
              </div>

              <!-- Feedback of that choice -->
              <div v-if="step.feedback || step.feedback_kh" class="p-3 rounded-xl bg-purple-50/50 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/30 text-xs text-purple-900 dark:text-purple-200 leading-relaxed">
                <span class="font-bold">{{ isKm ? 'មតិកែលម្អ៖ ' : 'Feedback: ' }}</span>
                {{ isKm ? (step.feedback_kh || step.feedback) : (step.feedback || step.feedback_kh) }}
              </div>

              <!-- Better choice hint if not best -->
              <div v-if="!step.is_best && (step.best_choice_label || step.best_choice_label_kh)" class="p-3 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/40 text-xs text-emerald-900 dark:text-emerald-200 leading-relaxed">
                <span class="font-bold">{{ isKm ? '💡 ជម្រើសដែលប្រសើរជាង៖ ' : '💡 Better Choice: ' }}</span>
                {{ isKm ? (step.best_choice_label_kh || step.best_choice_label) : (step.best_choice_label || step.best_choice_label_kh) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
          <span class="text-xs text-slate-500 dark:text-slate-400">
            {{ isKm 
              ? `នៅសល់សិទ្ធិអនុវត្តចំនួន ${attempts_left} ដងទៀត។` 
              : `${attempts_left} attempt(s) remaining.` 
            }}
          </span>

          <div class="flex items-center gap-3 w-full sm:w-auto">
            <button
              v-if="can_retry"
              @click="retrySimulation"
              type="button"
              class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 active:scale-[0.98] transition-all shadow-xs cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              {{ isKm ? '🔄 សាកល្បងអនុវត្តឡើងវិញ (Retry)' : '🔄 Retry Scenario' }}
            </button>

            <Link
              href="/student/simulations"
              class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 transition-colors text-center"
            >
              {{ isKm ? 'បញ្ចប់ & ត្រឡប់ក្រោយ' : 'Finish & Return' }}
            </Link>
          </div>
        </div>

      </div>
    </div>
  </StudentLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

interface StepSummary {
  step_id: number
  node_id: number
  node_title: string | null
  node_title_kh?: string | null
  node_body: string
  node_body_kh?: string | null
  chosen_label: string
  chosen_label_kh?: string | null
  points: number
  feedback: string | null
  feedback_kh?: string | null
  is_best: boolean
  best_choice_label: string | null
  best_choice_label_kh?: string | null
}

const props = defineProps<{
  attempt: {
    id: number
    attempt_no: number
    score: number
    status: string
    passed: boolean
    started_at: string | null
    completed_at: string | null
  }
  simulation: {
    id: number
    title: string
    title_kh: string | null
    skill: string
    kind: string
    pass_score: number
    max_attempts: number
    course: { id: number; title: string; code: string } | null
    lesson: { id: number; title: string } | null
  }
  steps: StepSummary[]
  can_retry: boolean
  attempts_left: number
}>()

const { currentLang, isKhmer } = useLanguage()
const isKm = computed(() => isKhmer.value || currentLang.value === 'km')

const retrySimulation = () => {
  router.post(`/student/simulations/${props.simulation.id}/start`)
}

const formatDate = (dateStr: string | null) => {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString(isKm.value ? 'km-KH' : 'en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
