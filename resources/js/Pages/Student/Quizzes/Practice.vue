<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const selectedTopic = ref('JavaScript Functions & Parameters')
const selectedDifficulty = ref<'Beginner' | 'Intermediate' | 'Advanced'>('Beginner')
const isGenerating = ref(false)
const isSessionActive = ref(false)
const currentQIndex = ref(0)
const selectedAnswers = ref<Record<number, number>>({})
const isEvaluated = ref(false)

const availableTopics = ref([
  'JavaScript Functions & Parameters',
  'Return Values & Expression Scopes',
  'JavaScript Variables & Data Types',
  'DOM Manipulation & Event Listeners',
  'CSS Flexbox & Responsive Layouts'
])

// AI Generated Question Pool
const generatedQuestions = ref([
  {
    id: 1,
    question: 'What is the default return value of a JavaScript function that does not contain a "return" statement?',
    options: ['0', 'null', 'undefined', 'NaN'],
    correct: 2,
    explanation: 'In JavaScript, functions that do not explicitly return a value evaluate to "undefined" by default.'
  },
  {
    id: 2,
    question: 'How do you define a default parameter value in modern JavaScript ES6?',
    options: [
      'function test(a default 10) {}',
      'function test(a = 10) {}',
      'function test(a || 10) {}',
      'function test(default a : 10) {}'
    ],
    correct: 1,
    explanation: 'Default parameters use the assignment operator (=) directly inside the parameter list.'
  },
  {
    id: 3,
    question: 'Which of the following creates a valid arrow function in JavaScript?',
    options: [
      'const add = (a, b) => a + b;',
      'const add = (a, b) -> a + b;',
      'function add => (a, b) { return a + b; }',
      'arrow add(a, b) => a + b;'
    ],
    correct: 0,
    explanation: 'Arrow function syntax uses the fat arrow (=>) following the argument parentheses.'
  },
  {
    id: 4,
    question: 'What is the scope of a variable declared with "let" inside a function?',
    options: [
      'Global scope',
      'Block / Local function scope',
      'Window scope',
      'Module scope only'
    ],
    correct: 1,
    explanation: 'Variables declared with "let" and "const" are block-scoped to the nearest enclosing block or function.'
  },
  {
    id: 5,
    question: 'What will the following code output? \nfunction greet(name = "Student") { return "Hi, " + name; }\nconsole.log(greet());',
    options: [
      '"Hi, undefined"',
      '"Hi, null"',
      '"Hi, Student"',
      'Throws ReferenceError'
    ],
    correct: 2,
    explanation: 'Because no argument was supplied, the default parameter "Student" was utilized.'
  }
])

const generateQuestionsWithAi = () => {
  isGenerating.value = true
  setTimeout(() => {
    isGenerating.value = false
    isSessionActive.value = true
    currentQIndex.value = 0
    selectedAnswers.value = {}
    isEvaluated.value = false
  }, 500)
}

const selectAnswer = (qIdx: number, optIdx: number) => {
  if (isEvaluated.value) return
  selectedAnswers.value[qIdx] = optIdx
}

const submitForAiEvaluation = () => {
  isEvaluated.value = true
}

const practiceScore = computed(() => {
  let score = 0
  generatedQuestions.value.forEach((q, idx) => {
    if (selectedAnswers.value[idx] === q.correct) {
      score++
    }
  })
  return score
})

const practicePercentage = computed(() => {
  return Math.round((practiceScore.value / generatedQuestions.value.length) * 100)
})
</script>

<template>
  <StudentLayout
    title="AI Practice Questions Generator"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Quiz & Assessment', href: '/student/quizzes/practice' },
      { label: 'AI Practice Questions' }
    ]"
  >
    <div class="space-y-6 pb-12">
      
      <!-- Top Banner -->
      <div class="bg-gradient-to-r from-purple-950 via-slate-900 to-indigo-950 border border-purple-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="space-y-2.5 max-w-2xl">
          <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 text-xs font-bold uppercase tracking-wider">
            🤖 AI QUESTION SYNTHESIZER
          </span>

          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
            AI Practice Generator
          </h1>

          <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
            Generate unlimited customized drills targeted specifically to your weak areas with instant step-by-step AI evaluations.
          </p>
        </div>
      </div>

      <!-- GENERATION CONFIGURATION BOX (When not in active quiz session) -->
      <div v-if="!isSessionActive" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <div class="lg:col-span-8 bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
          <div class="border-b border-slate-800 pb-4">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
              <span>⚡</span>
              <span>Configure AI Practice Drill</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Select a topic and target difficulty level to generate 5 interactive questions</p>
          </div>

          <div class="space-y-4">
            <!-- Topic Selector -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-slate-200">Target Learning Topic:</label>
              <select
                v-model="selectedTopic"
                class="w-full p-3 rounded-2xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500 transition-colors"
              >
                <option v-for="t in availableTopics" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>

            <!-- Difficulty Selector -->
            <div class="space-y-2">
              <label class="text-xs font-bold text-slate-200">Target Difficulty:</label>
              <div class="grid grid-cols-3 gap-3">
                <button
                  v-for="diff in ['Beginner', 'Intermediate', 'Advanced']"
                  :key="diff"
                  @click="selectedDifficulty = diff as any"
                  type="button"
                  :class="[
                    selectedDifficulty === diff
                      ? 'bg-purple-600 border-purple-500 text-white font-bold shadow-lg shadow-purple-600/30'
                      : 'bg-slate-950/80 border-slate-800 text-slate-400 hover:text-white',
                    'py-2.5 rounded-xl border text-xs text-center transition-all cursor-pointer'
                  ]"
                >
                  {{ diff }}
                </button>
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
            <span class="text-xs text-slate-400">Generates 5 questions with detailed explanations</span>
            <button
              @click="generateQuestionsWithAi"
              :disabled="isGenerating"
              type="button"
              class="px-6 py-3 rounded-2xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-xl shadow-purple-600/30 flex items-center gap-2 hover:scale-105 active:scale-95 transition-all cursor-pointer"
            >
              <span v-if="isGenerating" class="animate-spin">⏳</span>
              <span v-else>✨</span>
              <span>{{ isGenerating ? 'Synthesizing with AI...' : 'Generate 5 Practice Questions' }}</span>
            </button>
          </div>
        </div>

        <!-- Right Quick Info Card -->
        <div class="lg:col-span-4 bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4 text-xs">
          <div class="flex items-center gap-2.5">
            <span class="text-lg">🤖</span>
            <h3 class="text-xs font-bold text-white uppercase tracking-wider">How AI Practice Works</h3>
          </div>

          <ul class="space-y-2.5 text-slate-300 leading-relaxed">
            <li class="flex items-start gap-2">
              <span class="text-purple-400 font-bold">1.</span>
              <span>AI examines your past quiz errors and designs targeted questions to fix weak concepts.</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-purple-400 font-bold">2.</span>
              <span>You answer at your own pace without pressure on your final course GPA.</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-purple-400 font-bold">3.</span>
              <span>Receive deep AI explanations and code snippets for each question upon submission.</span>
            </li>
          </ul>
        </div>

      </div>

      <!-- ACTIVE PRACTICE SESSION & AI EVALUATION -->
      <div v-else class="space-y-6">
        
        <!-- Top Session Nav -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="px-2.5 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold border border-purple-500/30">
              {{ selectedTopic }} • {{ selectedDifficulty }}
            </span>
            <span class="text-xs text-slate-400">Question {{ currentQIndex + 1 }} of {{ generatedQuestions.length }}</span>
          </div>

          <button
            @click="isSessionActive = false"
            class="text-xs text-slate-400 hover:text-white transition-colors"
          >
            ← Exit Session
          </button>
        </div>

        <!-- Question Box Card -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
          
          <div class="space-y-3">
            <span class="text-[11px] font-bold text-purple-400 uppercase tracking-wider">Question {{ currentQIndex + 1 }}</span>
            <h3 class="text-base sm:text-lg font-bold text-white whitespace-pre-wrap leading-relaxed">
              {{ generatedQuestions[currentQIndex].question }}
            </h3>
          </div>

          <!-- Options Grid -->
          <div class="space-y-3">
            <button
              v-for="(opt, optIdx) in generatedQuestions[currentQIndex].options"
              :key="optIdx"
              @click="selectAnswer(currentQIndex, optIdx)"
              type="button"
              :class="[
                isEvaluated && optIdx === generatedQuestions[currentQIndex].correct
                  ? 'bg-emerald-500/20 border-emerald-500 text-emerald-300 font-bold'
                  : isEvaluated && selectedAnswers[currentQIndex] === optIdx && optIdx !== generatedQuestions[currentQIndex].correct
                  ? 'bg-rose-500/20 border-rose-500 text-rose-300 font-bold'
                  : selectedAnswers[currentQIndex] === optIdx
                  ? 'bg-purple-600/30 border-purple-500 text-white font-bold'
                  : 'bg-slate-950/80 border-slate-800 text-slate-300 hover:bg-slate-800/60',
                'w-full p-4 rounded-2xl border text-xs text-left flex items-center justify-between transition-all cursor-pointer'
              ]"
            >
              <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-slate-800 flex items-center justify-center font-bold text-[11px]">
                  {{ String.fromCharCode(65 + optIdx) }}
                </span>
                <span>{{ opt }}</span>
              </div>

              <span v-if="isEvaluated && optIdx === generatedQuestions[currentQIndex].correct" class="text-emerald-400 font-bold">✓ Correct</span>
              <span v-else-if="isEvaluated && selectedAnswers[currentQIndex] === optIdx" class="text-rose-400 font-bold">✗ Incorrect</span>
            </button>
          </div>

          <!-- AI Explanation Box (When Evaluated) -->
          <div v-if="isEvaluated" class="p-4 rounded-2xl bg-indigo-950/50 border border-indigo-500/30 space-y-1.5 text-xs">
            <p class="font-bold text-indigo-300 flex items-center gap-1.5">
              <span>🤖 AI Explanation:</span>
            </p>
            <p class="text-slate-200 leading-relaxed">
              {{ generatedQuestions[currentQIndex].explanation }}
            </p>
          </div>

          <!-- Session Controls -->
          <div class="flex items-center justify-between pt-4 border-t border-slate-800">
            <button
              :disabled="currentQIndex === 0"
              @click="currentQIndex--"
              class="px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs font-bold disabled:opacity-40"
            >
              Previous
            </button>

            <div class="flex items-center gap-3">
              <button
                v-if="currentQIndex < generatedQuestions.length - 1"
                @click="currentQIndex++"
                class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold"
              >
                Next Question ›
              </button>

              <button
                v-if="!isEvaluated && currentQIndex === generatedQuestions.length - 1"
                @click="submitForAiEvaluation"
                class="px-6 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30"
              >
                Submit for AI Evaluation ✓
              </button>

              <button
                v-if="isEvaluated"
                @click="generateQuestionsWithAi"
                class="px-6 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-lg shadow-purple-600/30"
              >
                Generate Another Set ⚡
              </button>
            </div>
          </div>

        </div>

      </div>

    </div>
  </StudentLayout>
</template>
