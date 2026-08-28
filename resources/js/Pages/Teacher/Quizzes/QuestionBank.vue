<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps<{
  courses?: Array<any>
}>()

const searchQuery = ref('')
const selectedType = ref('All')
const selectedDifficulty = ref('All')

// Question Types list
const questionTypeFilters = [
  'All', 'MCQ', 'True-False', 'Short Answer', 'Essay', 'Coding', 'Matching'
]

// Sample Question Bank Data
const questions = ref([
  {
    id: 'Q-0001',
    title: 'What is the output of printf("%d", 10 + 20); in C?',
    title_kh: 'តើលទ្ធផលនៃ printf("%d", 10 + 20); ក្នុង C គឺអ្វី?',
    type: 'MCQ',
    difficulty: 'Easy',
    used: 12,
    status: 'Live',
    course: 'C Programming Basics',
    module: 'Module 1: Intro',
    marks: 2,
    options: ['A. 30', 'B. 1020', 'C. Error', 'D. 0'],
    correct: 'A. 30',
  },
  {
    id: 'Q-0002',
    title: 'C language was created by Dennis Ritchie at Bell Labs.',
    title_kh: 'ភាសា C ត្រូវបានបង្កើតឡើងដោយ Dennis Ritchie នៅ Bell Labs។',
    type: 'True-False',
    difficulty: 'Easy',
    used: 8,
    status: 'Live',
    course: 'C Programming Basics',
    module: 'Module 1: Intro',
    marks: 1,
    options: ['True', 'False'],
    correct: 'True',
  },
  {
    id: 'Q-0003',
    title: 'Explain the main purpose of a variable in computer programming.',
    title_kh: 'ពន្យល់ពីគោលបំណងចម្បងនៃអថេរក្នុងកម្មវិធីកុំព្យូទ័រ។',
    type: 'Short Answer',
    difficulty: 'Medium',
    used: 5,
    status: 'Live',
    course: 'C Programming Basics',
    module: 'Module 2: Variables',
    marks: 5,
    options: [],
    correct: 'Variables allocate memory locations in RAM to store data values during program execution.',
  },
  {
    id: 'Q-0004',
    title: 'Match the data types with their correct size in C (int, float, char).',
    title_kh: 'ផ្គូផ្គងប្រភេទទិន្នន័យជាមួយទំហំក្នុង memory (int, float, char)។',
    type: 'Matching',
    difficulty: 'Medium',
    used: 6,
    status: 'Live',
    course: 'C Programming Basics',
    module: 'Module 2: Variables',
    marks: 4,
    options: [],
    correct: 'int -> 4 bytes, char -> 1 byte, float -> 4 bytes',
  },
  {
    id: 'Q-0005',
    title: 'Write a C function to reverse a singly linked list in O(n) time.',
    title_kh: 'សរសេរអនុគមន៍ C ដើម្បីបញ្ច្រាស Singly Linked List ក្នុង O(n) time។',
    type: 'Coding',
    difficulty: 'Hard',
    used: 3,
    status: 'Live',
    course: 'C Programming Basics',
    module: 'Module 3: Pointers',
    marks: 10,
    options: [],
    correct: 'struct Node* reverse(struct Node* head) { ... }',
  }
])

const filteredQuestions = computed(() => {
  return questions.value.filter(q => {
    const matchQuery = q.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                       q.id.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                       q.title_kh.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchType = selectedType.value === 'All' || q.type === selectedType.value
    const matchDiff = selectedDifficulty.value === 'All' || q.difficulty === selectedDifficulty.value
    return matchQuery && matchType && matchDiff
  })
})

// Create Question Modal
const showCreateModal = ref(false)
const createForm = useForm({
  type: 'MCQ',
  difficulty: 'Easy',
  marks: 2,
  question_en: '',
  question_kh: '',
  option_a: '',
  option_b: '',
  option_c: '',
  option_d: '',
  correct_option: 'A',
  explanation: '',
})

const submitCreateQuestion = () => {
  if (!createForm.question_en) {
    alert('Please enter question text')
    return
  }

  questions.value.unshift({
    id: `Q-000${questions.value.length + 1}`,
    title: createForm.question_en,
    title_kh: createForm.question_kh || createForm.question_en,
    type: createForm.type,
    difficulty: createForm.difficulty,
    used: 0,
    status: 'Live',
    course: 'C Programming Basics',
    module: 'Module 1: Intro',
    marks: createForm.marks,
    options: createForm.type === 'MCQ' ? [createForm.option_a, createForm.option_b, createForm.option_c, createForm.option_d] : [],
    correct: createForm.correct_option,
  })

  alert('សំណួរត្រូវបានបន្ថែមទៅក្នុង Question Bank ដោយជោគជ័យ!')
  showCreateModal.value = false
  createForm.reset()
}

// 🤖 AI Smart Question Generator State & Methods
const showAiModal = ref(false)
const isGeneratingAi = ref(false)
const aiTopic = ref('')
const aiContent = ref('')
const aiLanguage = ref('km')
const aiCount = ref(4)
const aiType = ref('MCQ')
const aiDifficulty = ref('Medium')
const generatedQuestions = ref<any[]>([])
const aiErrorMessage = ref('')

const generateAiQuestions = async () => {
  if (!aiTopic.value.trim() && !aiContent.value.trim()) {
    aiErrorMessage.value = 'សូមបញ្ចូលប្រធានបទ ឬខ្លឹមសារមេរៀនជាមុនសិន'
    return
  }

  aiErrorMessage.value = ''
  isGeneratingAi.value = true
  generatedQuestions.value = []

  try {
    const res = await fetch('/teacher/ai/generate-quiz', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
      },
      body: JSON.stringify({
        topic: aiTopic.value.trim() || 'General Academic Assessment',
        content: aiContent.value.trim(),
        language: aiLanguage.value,
        count: aiCount.value,
        type: aiType.value,
        difficulty: aiDifficulty.value,
      })
    })

    const data = await res.json()

    if (data && data.questions && data.questions.length > 0) {
      generatedQuestions.value = data.questions
    } else {
      aiErrorMessage.value = 'មិនអាចបង្កើតសំណួរបានទេ។ សូមព្យាយាមម្តងទៀត។'
    }
  } catch (err: any) {
    aiErrorMessage.value = err?.message || 'មានបញ្ហាក្នុងការទាក់ទង AI Engine។'
  } finally {
    isGeneratingAi.value = false
  }
}

const importAllAiQuestions = () => {
  if (generatedQuestions.value.length === 0) return

  generatedQuestions.value.forEach((item, index) => {
    questions.value.unshift({
      id: `Q-AI-${Date.now().toString().slice(-4)}${index + 1}`,
      title: item.title || item.question || 'AI Question',
      title_kh: item.title_kh || item.title || item.question || 'AI Question (Khmer)',
      type: item.type || aiType.value,
      difficulty: item.difficulty || aiDifficulty.value,
      used: 0,
      status: 'Live',
      course: 'AI Generated Bank',
      module: aiTopic.value || 'General Module',
      marks: item.marks || 2,
      options: item.options || [],
      correct: item.correct || (item.options ? item.options[item.correct_index ?? 0] : 'N/A'),
    })
  })

  alert(`🎉 បញ្ចូលសំណួរ AI ចំនួន ${generatedQuestions.value.length} ទៅក្នុង Question Bank ដោយជោគជ័យ!`)
  generatedQuestions.value = []
  showAiModal.value = false
}
</script>

<template>
  <div class="space-y-6">
    <!-- Top Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="p-4 bg-white dark:bg-gray-800 rounded-3xl border border-slate-200/80 dark:border-gray-700 shadow-sm flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 font-black flex items-center justify-center text-lg">❓</div>
        <div>
          <p class="text-[10px] text-slate-400 font-extrabold uppercase">Total Questions</p>
          <p class="text-xl font-black text-slate-800 dark:text-white">420</p>
        </div>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 rounded-3xl border border-slate-200/80 dark:border-gray-700 shadow-sm flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 font-black flex items-center justify-center text-lg">🟢</div>
        <div>
          <p class="text-[10px] text-slate-400 font-extrabold uppercase">Published</p>
          <p class="text-xl font-black text-slate-800 dark:text-white">385</p>
        </div>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 rounded-3xl border border-slate-200/80 dark:border-gray-700 shadow-sm flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 font-black flex items-center justify-center text-lg">♻️</div>
        <div>
          <p class="text-[10px] text-slate-400 font-extrabold uppercase">Reused in Quizzes</p>
          <p class="text-xl font-black text-slate-800 dark:text-white">1,240 <span class="text-xs font-normal">ដង</span></p>
        </div>
      </div>
      <div class="p-4 bg-white dark:bg-gray-800 rounded-3xl border border-slate-200/80 dark:border-gray-700 shadow-sm flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 font-black flex items-center justify-center text-lg">🟡</div>
        <div>
          <p class="text-[10px] text-slate-400 font-extrabold uppercase">Draft Vault</p>
          <p class="text-xl font-black text-slate-800 dark:text-white">35</p>
        </div>
      </div>
    </div>

    <!-- Toolbar & Filter Tags -->
    <div class="bg-white dark:bg-gray-800 p-5 rounded-3xl border border-slate-200/80 dark:border-gray-700 shadow-sm space-y-4">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
          <div class="relative">
            <i class="pi pi-search absolute left-3 top-3 text-slate-400 text-xs"></i>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="ស្វែងរកសំណួរ ឬពាក្យគន្លឹះ..."
              class="pl-8 pr-3 py-2 rounded-2xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-700 text-xs w-64 focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <select v-model="selectedDifficulty" class="p-2 rounded-2xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-700 text-xs font-semibold">
            <option value="All">All Difficulty ▼</option>
            <option value="Easy">🟢 Easy</option>
            <option value="Medium">🟡 Medium</option>
            <option value="Hard">🔴 Hard</option>
          </select>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button @click="showAiModal = true" class="px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 rounded-2xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
            <span>✨ AI Auto-Gen Questions</span>
          </button>
          <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-xs font-black shadow-md shadow-blue-500/20 transition flex items-center gap-1.5 cursor-pointer">
            <span>+ បន្ថែមសំណួរ (Add Question)</span>
          </button>
        </div>
      </div>

      <!-- Tag Filter Pills -->
      <div class="flex flex-wrap gap-1.5 pt-1 border-t border-slate-100 dark:border-gray-700">
        <button
          v-for="tag in questionTypeFilters"
          :key="tag"
          @click="selectedType = tag"
          :class="[
            'px-3 py-1 rounded-xl text-xs font-bold transition',
            selectedType === tag
              ? 'bg-blue-600 text-white shadow-sm'
              : 'bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200'
          ]"
        >
          {{ tag }}
        </button>
      </div>
    </div>

    <!-- Question Cards List -->
    <div class="space-y-3">
      <div
        v-for="q in filteredQuestions"
        :key="q.id"
        class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-slate-200/80 dark:border-gray-700 shadow-sm hover:shadow-md transition space-y-3"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-mono text-[10px] font-extrabold">
              {{ q.id }}
            </span>
            <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-slate-300 text-[10px] font-bold">
              {{ q.type }}
            </span>
            <span
              class="px-2 py-0.5 rounded-lg text-[10px] font-extrabold"
              :class="{
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': q.difficulty === 'Easy',
                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300': q.difficulty === 'Medium',
                'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300': q.difficulty === 'Hard',
              }"
            >
              {{ q.difficulty }}
            </span>
          </div>

          <!-- Reuse Indicator (Used in N Quizzes) -->
          <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 border border-purple-200 dark:border-purple-800 font-extrabold text-[11px]">
              ♻️ ប្រើប្រាស់ក្នុង {{ q.used }} Quizzes
            </span>
            <span class="text-xs font-bold text-slate-400">{{ q.marks }} Marks</span>
          </div>
        </div>

        <div>
          <p class="font-black text-slate-800 dark:text-white text-sm">{{ q.title }}</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">{{ q.title_kh }}</p>
        </div>

        <div v-if="q.options && q.options.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
          <div
            v-for="(opt, idx) in q.options"
            :key="idx"
            :class="[
              'p-2.5 rounded-xl text-xs font-medium border',
              opt.startsWith('A.')
                ? 'bg-emerald-50 text-emerald-800 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 font-bold'
                : 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-gray-700 dark:text-slate-200 dark:border-gray-600'
            ]"
          >
            {{ opt }}
          </div>
        </div>

        <div v-else class="p-3 bg-slate-50 dark:bg-gray-700/50 rounded-2xl border border-slate-100 dark:border-gray-700 text-xs text-slate-600 dark:text-slate-300">
          <span class="font-bold text-slate-500">Correct Key / Reference:</span> {{ q.correct }}
        </div>
      </div>
    </div>

    <!-- ➕ ADD QUESTION MODAL -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200 dark:border-gray-700 overflow-y-auto max-h-[90vh]">
        <div class="flex items-center justify-between border-b pb-3">
          <h3 class="text-base font-extrabold text-slate-800 dark:text-white">➕ បន្ថែមសំណួរទៅ Question Bank</h3>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600"><i class="pi pi-times"></i></button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold mb-1">Question Type</label>
              <select v-model="createForm.type" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-700">
                <option value="MCQ">MCQ (Multiple Choice)</option>
                <option value="True-False">True / False</option>
                <option value="Short Answer">Short Answer</option>
                <option value="Essay">Essay</option>
                <option value="Coding">Coding</option>
              </select>
            </div>
            <div>
              <label class="block font-bold mb-1">Difficulty Level</label>
              <select v-model="createForm.difficulty" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-700">
                <option value="Easy">🟢 Easy</option>
                <option value="Medium">🟡 Medium</option>
                <option value="Hard">🔴 Hard</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-bold mb-1">Question Text (English)</label>
            <input v-model="createForm.question_en" type="text" placeholder="e.g. What is the difference between TCP and UDP?" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-700" />
          </div>

          <div>
            <label class="block font-bold mb-1">Question Text (Khmer)</label>
            <input v-model="createForm.question_kh" type="text" placeholder="e.g. តើអ្វីជាភាពខុសគ្នារវាង TCP និង UDP?" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-700" />
          </div>

          <div v-if="createForm.type === 'MCQ'" class="space-y-2 pt-1">
            <label class="font-bold">Choices & Correct Answer</label>
            <input v-model="createForm.option_a" placeholder="Option A (Correct)" class="w-full p-2 rounded-lg border bg-slate-50 dark:bg-gray-700" />
            <input v-model="createForm.option_b" placeholder="Option B" class="w-full p-2 rounded-lg border bg-slate-50 dark:bg-gray-700" />
            <input v-model="createForm.option_c" placeholder="Option C" class="w-full p-2 rounded-lg border bg-slate-50 dark:bg-gray-700" />
            <input v-model="createForm.option_d" placeholder="Option D" class="w-full p-2 rounded-lg border bg-slate-50 dark:bg-gray-700" />
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t">
          <button @click="showCreateModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">Cancel</button>
          <button @click="submitCreateQuestion" class="px-5 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold shadow">Save to Vault</button>
        </div>
      </div>
    </div>

    <!-- 🤖 AI SMART QUESTION GENERATOR MODAL -->
    <div v-if="showAiModal" class="fixed inset-0 bg-black/75 backdrop-blur-md flex items-center justify-center p-4 z-50 animate-fade-in">
      <div class="bg-white dark:bg-gray-900 rounded-3xl max-w-2xl w-full p-6 space-y-5 shadow-2xl border border-purple-200 dark:border-purple-900/50 overflow-y-auto max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-800 pb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-500 text-white flex items-center justify-center font-black text-lg shadow-lg shadow-purple-500/25">
              ✨
            </div>
            <div>
              <h3 class="text-base font-extrabold text-slate-800 dark:text-white">AI Smart Quiz Generator</h3>
              <p class="text-xs text-purple-600 dark:text-purple-400 font-semibold">បង្កើតសំនួរប្រឡង និងលំហាត់ដោយស្វ័យប្រវត្តិតាមរយៈ AI</p>
            </div>
          </div>
          <button @click="showAiModal = false" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-gray-800 text-slate-400 hover:text-slate-600 flex items-center justify-center transition">
            <i class="pi pi-times text-xs"></i>
          </button>
        </div>

        <!-- Inputs Section (When not yet generated) -->
        <div v-if="generatedQuestions.length === 0" class="space-y-4 text-xs">
          <div v-if="aiErrorMessage" class="p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 rounded-2xl font-bold">
            ⚠️ {{ aiErrorMessage }}
          </div>

          <div>
            <label class="block font-extrabold text-slate-700 dark:text-slate-200 mb-1.5">
              📌 ប្រធានបទមេរៀន ឬ មុខវិជ្ជា (Topic / Subject) <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="aiTopic"
              type="text"
              placeholder="ឧទាហរណ៍៖ Cloud Computing & Virtualization, TCP/IP Model, Data Structures"
              class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 text-xs font-medium focus:ring-2 focus:ring-purple-500"
            />
          </div>

          <div>
            <label class="block font-extrabold text-slate-700 dark:text-slate-200 mb-1.5">
              📝 ខ្លឹមសារមេរៀនសង្ខេប ឬ អត្ថបទស្លាយ (Lesson Context / Notes) <span class="text-slate-400 font-normal">(ស្រេចចិត្ត)</span>
            </label>
            <textarea
              v-model="aiContent"
              rows="4"
              placeholder="Copy & Paste ខ្លឹមសារមេរៀន ឬឯកសារបង្រៀនចូលទីនេះ ដើម្បីឱ្យ AI បង្កើតសំណួរកាន់តែចំគោលដៅ..."
              class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 text-xs font-medium focus:ring-2 focus:ring-purple-500 resize-none"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div>
              <label class="block font-bold text-slate-600 dark:text-slate-300 mb-1">ភាសាសំណួរ</label>
              <select v-model="aiLanguage" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 font-bold">
                <option value="km">🇰🇭 ភាសាខ្មែរ (Khmer)</option>
                <option value="en">🇬🇧 English</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-600 dark:text-slate-300 mb-1">ចំនួនសំណួរ</label>
              <select v-model.number="aiCount" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 font-bold">
                <option :value="3">3 សំណួរ</option>
                <option :value="4">4 សំណួរ</option>
                <option :value="5">5 សំណួរ</option>
                <option :value="8">8 សំណួរ</option>
                <option :value="10">10 សំណួរ</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-600 dark:text-slate-300 mb-1">ប្រភេទសំណួរ</label>
              <select v-model="aiType" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 font-bold">
                <option value="MCQ">MCQ (ពហុជ្រើសរើស)</option>
                <option value="True-False">True / False</option>
                <option value="Short Answer">Short Answer</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-600 dark:text-slate-300 mb-1">កម្រិតលំបាក</label>
              <select v-model="aiDifficulty" class="w-full p-2.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-800 font-bold">
                <option value="Easy">🟢 ងាយ (Easy)</option>
                <option value="Medium">🟡 មធ្យម (Medium)</option>
                <option value="Hard">🔴 ពិបាក (Hard)</option>
              </select>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between">
            <button @click="showAiModal = false" class="px-4 py-2.5 bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-slate-300 rounded-2xl font-bold">
              បោះបង់ (Cancel)
            </button>
            <button
              @click="generateAiQuestions"
              :disabled="isGeneratingAi"
              class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-2xl font-extrabold shadow-lg shadow-purple-500/30 flex items-center gap-2 transition disabled:opacity-50 cursor-pointer"
            >
              <span v-if="isGeneratingAi" class="animate-spin text-sm">⏳</span>
              <span v-else>⚡</span>
              <span>{{ isGeneratingAi ? 'AI កំពុងបង្កើតសំណួរ...' : 'បង្កើតសំណួរឥឡូវនេះ' }}</span>
            </button>
          </div>
        </div>

        <!-- Results / Preview Section (When generated successfully) -->
        <div v-else class="space-y-4 text-xs">
          <div class="flex items-center justify-between bg-purple-50 dark:bg-purple-950/40 p-3 rounded-2xl border border-purple-200 dark:border-purple-800">
            <div class="flex items-center gap-2">
              <span class="text-base">🎉</span>
              <span class="font-extrabold text-purple-900 dark:text-purple-200">
                AI បានបង្កើតសំណួរចំនួន {{ generatedQuestions.length }} ដោយជោគជ័យ!
              </span>
            </div>
            <button @click="generateAiQuestions" :disabled="isGeneratingAi" class="px-3 py-1 bg-white dark:bg-gray-800 rounded-xl text-purple-700 dark:text-purple-300 font-bold border shadow-xs hover:bg-purple-100 transition">
              🔄 បង្កើតឡើងវិញ
            </button>
          </div>

          <!-- Question Previews List -->
          <div class="space-y-3 max-h-[50vh] overflow-y-auto pr-1">
            <div
              v-for="(item, idx) in generatedQuestions"
              :key="idx"
              class="p-4 bg-slate-50 dark:bg-gray-800/80 rounded-2xl border border-slate-200 dark:border-gray-700 space-y-2.5"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 font-extrabold font-mono text-[10px]">
                    #{{ idx + 1 }}
                  </span>
                  <span class="px-2 py-0.5 rounded-lg bg-slate-200 dark:bg-gray-700 font-bold text-[10px]">
                    {{ item.type || aiType }}
                  </span>
                  <span class="px-2 py-0.5 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 font-bold text-[10px]">
                    {{ item.difficulty || aiDifficulty }}
                  </span>
                </div>
                <span class="font-bold text-slate-400">{{ item.marks || 2 }} Marks</span>
              </div>

              <div>
                <p class="font-extrabold text-slate-800 dark:text-white">{{ item.title || item.question }}</p>
                <p v-if="item.title_kh && item.title_kh !== item.title" class="text-slate-600 dark:text-slate-300 mt-0.5">{{ item.title_kh }}</p>
              </div>

              <div v-if="item.options && item.options.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                <div
                  v-for="(opt, oIdx) in item.options"
                  :key="oIdx"
                  :class="[
                    'p-2 rounded-xl text-[11px] font-medium border',
                    (opt === item.correct || opt.startsWith('A.') || oIdx === item.correct_index)
                      ? 'bg-emerald-50 text-emerald-800 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 font-bold'
                      : 'bg-white dark:bg-gray-700 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-gray-600'
                  ]"
                >
                  {{ opt }}
                  <span v-if="opt === item.correct || opt.startsWith('A.') || oIdx === item.correct_index" class="ml-1 text-emerald-600 dark:text-emerald-400 font-black">✓ Correct</span>
                </div>
              </div>

              <div v-if="item.explanation" class="p-2.5 bg-blue-50 dark:bg-blue-950/30 rounded-xl border border-blue-100 dark:border-blue-900 text-[11px] text-blue-800 dark:text-blue-200">
                💡 <span class="font-bold">ពន្យល់៖</span> {{ item.explanation }}
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between">
            <button @click="generatedQuestions = []" class="px-4 py-2.5 bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-slate-300 rounded-2xl font-bold">
              ← កែសម្រួលប្រធានបទ
            </button>
            <button
              @click="importAllAiQuestions"
              class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black shadow-lg shadow-emerald-500/25 flex items-center gap-2 transition cursor-pointer"
            >
              <span>📥 បញ្ចូលសំណួរទាំងអស់ទៅក្នុង Question Bank</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
