<script setup lang="ts">
import { ref, computed } from 'vue'

export interface DifficultTopic {
  id: number
  topic: string
  course: string
  major: string
  students_assessed: number
  average_score: number
  difficulty_rate: number
  severity: 'Critical' | 'Moderate' | 'Warning'
  recommendation: string
  recommendation_kh: string
  suggested_actions: string[]
  common_incorrect_questions: Array<{ question: string; wrong_rate: number }>
  remedial_status: string
}

const props = withDefaults(defineProps<{
  topics?: DifficultTopic[]
  majors?: any[]
}>(), {
  topics: () => [
    {
      id: 1,
      topic: 'JavaScript Functions & Scope',
      course: 'Web Development Basics',
      major: 'Information Technology',
      students_assessed: 45,
      average_score: 45,
      difficulty_rate: 74,
      severity: 'Critical',
      recommendation: 'Review lesson, Add supplementary video tutorial, Create practice quiz',
      recommendation_kh: 'រំលឹកមេរៀនឡើងវិញ + បន្ថែមវីដេអូពន្យល់ Scope + បង្កើត Practice Quiz',
      suggested_actions: [
        'Review lesson lecture notes',
        'Add visual diagram on execution stack',
        'Create interactive practice quiz'
      ],
      common_incorrect_questions: [
        { question: 'What is the return value of an arrow function without explicit return?', wrong_rate: 68 },
        { question: 'Explain variable hoisting differences between var, let, and const', wrong_rate: 59 },
      ],
      remedial_status: 'Remedial Drill Dispatched ✅',
    },
    {
      id: 2,
      topic: 'Soil Management & pH Balance',
      course: 'Soil Science & Plant Nutrition',
      major: 'Agriculture',
      students_assessed: 38,
      average_score: 38,
      difficulty_rate: 82,
      severity: 'Critical',
      recommendation: 'Review Soil Preparation, Read supplementary learning material, Practice Quiz',
      recommendation_kh: 'Review Soil Preparation + អានឯកសារជំនួយដីកសិកម្ម + ធ្វើ Practice Quiz',
      suggested_actions: [
        'Review Soil Preparation fundamentals',
        'Read supplementary learning material',
        'Practice Quiz on soil amendments'
      ],
      common_incorrect_questions: [
        { question: 'How does soil pH below 5.5 affect phosphorus availability to plant roots?', wrong_rate: 76 },
        { question: 'Calculate lime application required to raise soil pH from 4.8 to 6.2', wrong_rate: 81 },
      ],
      remedial_status: 'Remedial Drill Dispatched ✅',
    },
    {
      id: 3,
      topic: 'Complex Sentence Structures & Clauses',
      course: 'Academic English Grammar',
      major: 'English Literature',
      students_assessed: 42,
      average_score: 46,
      difficulty_rate: 68,
      severity: 'Critical',
      recommendation: 'Sentence diagramming drills, Video on relative pronouns, Practice Quiz',
      recommendation_kh: 'លំហាត់រៀបចំប្រយោគ + វីដេអូ Relative Clauses + Practice Quiz',
      suggested_actions: [
        'Provide clause breakdown cheat sheet',
        'Add grammar workshop video',
        'Assign 10-question sentence synthesis test'
      ],
      common_incorrect_questions: [
        { question: 'Differentiate restrictive vs non-restrictive relative clauses with commas', wrong_rate: 62 },
        { question: 'Identify inverted conditionals in academic formal writing', wrong_rate: 55 },
      ],
      remedial_status: 'Scheduled for Revision',
    },
    {
      id: 4,
      topic: 'Hotel Yield Management & Dynamic Pricing',
      course: 'Tourism Operations & Booking Systems',
      major: 'Tourism Management',
      students_assessed: 35,
      average_score: 48,
      difficulty_rate: 65,
      severity: 'Moderate',
      recommendation: 'Yield calculation walkthrough, Case study review, Formulate practice drills',
      recommendation_kh: 'ពន្យល់រូបមន្តគណនា Yield + ករណីសិក្សាសណ្ឋាគារ + លំហាត់គណនា',
      suggested_actions: [
        'Step-by-step RevPAR calculation video',
        'Case study: Seasonal occupancy adjustments',
        'Quick formative check quiz'
      ],
      common_incorrect_questions: [
        { question: 'Calculate Revenue per Available Room (RevPAR) given 70% occupancy', wrong_rate: 58 },
      ],
      remedial_status: 'Material Uploaded',
    },
    {
      id: 5,
      topic: 'Legal Frameworks in Cambodian Child Welfare',
      course: 'Child Protection Protocols',
      major: 'Social Work',
      students_assessed: 30,
      average_score: 52,
      difficulty_rate: 60,
      severity: 'Moderate',
      recommendation: 'Statute analysis guide, Mock case simulation, Multiple choice quiz',
      recommendation_kh: 'សេចក្តីសង្ខេបច្បាប់ការពារកុមារ + ករណីសិក្សាជាក់ស្តែង + Quiz',
      suggested_actions: [
        'Publish Legal reference summary note',
        'Host interactive discussion session'
      ],
      common_incorrect_questions: [
        { question: 'Mandatory reporting deadlines under Cambodia Child Protection Policy', wrong_rate: 54 },
      ],
      remedial_status: 'Discussion Scheduled',
    }
  ],
  majors: () => []
})

const emit = defineEmits<{
  (e: 'addRemedialContent', topic: DifficultTopic): void
  (e: 'createPracticeQuiz', topic: DifficultTopic): void
}>()

// Filter State
const search = ref('')
const selectedMajor = ref('')
const selectedSeverity = ref('')

// Modal State
const showDetailModal = ref(false)
const selectedTopic = ref<DifficultTopic | null>(null)
const notificationMsg = ref('')

const filteredTopics = computed(() => {
  return props.topics.filter(t => {
    const matchSearch = !search.value || 
      t.topic.toLowerCase().includes(search.value.toLowerCase()) ||
      t.course.toLowerCase().includes(search.value.toLowerCase())
    const matchMajor = !selectedMajor.value || t.major === selectedMajor.value
    const matchSeverity = !selectedSeverity.value || t.severity === selectedSeverity.value
    return matchSearch && matchMajor && matchSeverity
  })
})

function openDetail(t: DifficultTopic) {
  selectedTopic.value = t
  showDetailModal.value = true
}

function handleAddMaterial(t: DifficultTopic) {
  notificationMsg.value = `បានភ្ជាប់ឯកសារជំនួយ (Learning Material) ទៅកាន់ប្រធានបទ "${t.topic}"!`
  setTimeout(() => { notificationMsg.value = '' }, 3500)
}

function handleCreateQuiz(t: DifficultTopic) {
  notificationMsg.value = `បានបង្កើត Practice Quiz ដោយស្វ័យប្រវត្តិសម្រាប់ "${t.topic}"!`
  setTimeout(() => { notificationMsg.value = '' }, 3500)
}
</script>

<template>
  <div class="space-y-6 text-xs font-sans">
    <!-- Success Banner -->
    <div
      v-if="notificationMsg"
      class="p-4 rounded-xl bg-teal-950/80 border border-teal-500/60 text-teal-200 text-xs font-bold flex items-center justify-between shadow-lg shadow-teal-950/50 animate-fadeIn"
    >
      <div class="flex items-center gap-2">
        <span class="text-base">✨</span>
        <span>{{ notificationMsg }}</span>
      </div>
      <button @click="notificationMsg = ''" class="text-teal-400 hover:text-white font-black text-sm">✕</button>
    </div>

    <!-- ── HEADER BANNER & AI ANALYSIS PIPELINE ── -->
    <div class="bg-[#0d1222]/95 border border-purple-500/30 rounded-2xl p-5 shadow-2xl space-y-4">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-700/60 pb-4">
        <div>
          <h3 class="text-base font-black text-white flex items-center gap-2.5">
            <div class="p-2 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-400 shrink-0">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="currentColor" />
              </svg>
            </div>
            <span>AI DIFFICULT TOPICS DETECTION — ប្រធានបទដែលនិស្សិតជួបការលំបាក</span>
          </h3>
          <p class="text-slate-400 text-xs mt-1 font-medium">
            គោលបំណង៖ ឲ្យ AI រកឃើញថា Lesson / Topic ណាដែល Student ជាច្រើនកំពុងពិបាក ដើម្បីឲ្យ Teacher អាចកែសម្រួលការបង្រៀនបាន។
          </p>
        </div>

        <div class="px-3.5 py-2 rounded-xl bg-purple-950/40 border border-purple-500/40 text-purple-200 text-[11px] font-semibold flex items-center gap-2 shadow-inner">
          <span>🧠</span>
          <span>Topic Difficulty Analysis Engine: Active</span>
        </div>
      </div>

      <!-- ── AI ANALYSIS & RECOMMENDATION LOOP (Spec 2 & 4) ── -->
      <div class="p-4 rounded-xl bg-[#090d16] border border-slate-800 space-y-2">
        <span class="text-[10px] font-mono text-teal-300 font-bold uppercase tracking-wider block">
          🔄 CLOSED-LOOP ADAPTIVE TEACHING & LEARNING CYCLE
        </span>
        <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-300 font-medium">
          <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700">Quiz Results + Wrong Answers</span>
          <span class="text-teal-400 font-bold">➔</span>
          <span class="px-2.5 py-1 rounded-lg bg-amber-950/60 border border-amber-500/40 text-amber-300 font-bold">Difficult Topics Identified</span>
          <span class="text-teal-400 font-bold">➔</span>
          <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700">Teacher Gets Insight & Revises</span>
          <span class="text-teal-400 font-bold">➔</span>
          <span class="px-2.5 py-1 rounded-lg bg-purple-950/60 border border-purple-500/40 text-purple-300 font-bold">AI Recommendations</span>
          <span class="text-teal-400 font-bold">➔</span>
          <span class="px-2.5 py-1 rounded-lg bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 font-bold">Students Receive Practice</span>
        </div>
      </div>

      <!-- 3 Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-red-950/20 border border-red-500/40 p-3.5 rounded-xl flex items-center justify-between">
          <div>
            <span class="text-slate-400 text-[10px] block font-bold">⚠️ CRITICAL DIFFICULT TOPICS</span>
            <p class="text-xl font-black text-red-400">3 Topics</p>
            <span class="text-[10px] text-red-300">Average score &lt; 50% across cohort</span>
          </div>
          <span class="text-3xl">🎯</span>
        </div>

        <div class="bg-amber-950/20 border border-amber-500/40 p-3.5 rounded-xl flex items-center justify-between">
          <div>
            <span class="text-slate-400 text-[10px] block font-bold">🟡 MODERATE CHALLENGES</span>
            <p class="text-xl font-black text-amber-300">2 Topics</p>
            <span class="text-[10px] text-amber-200">Score 50% – 60% requiring revision</span>
          </div>
          <span class="text-3xl">📊</span>
        </div>

        <div class="bg-purple-950/20 border border-purple-500/40 p-3.5 rounded-xl flex items-center justify-between">
          <div>
            <span class="text-slate-400 text-[10px] block font-bold">👥 TOTAL STUDENTS AFFECTED</span>
            <p class="text-xl font-black text-purple-300">190 Students</p>
            <span class="text-[10px] text-purple-200">Provided automated remedial drill</span>
          </div>
          <span class="text-3xl">💡</span>
        </div>
      </div>
    </div>

    <!-- ── FILTER BAR ── -->
    <div class="bg-[#0d1222]/90 border border-slate-700/60 rounded-xl p-3.5 flex flex-wrap items-center justify-between gap-3 shadow-md">
      <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
        <!-- Search -->
        <div class="relative flex-1 min-w-[180px] max-w-sm">
          <input
            v-model="search"
            type="text"
            placeholder="Search topic or course..."
            class="w-full bg-[#121827] text-white pl-8 pr-3 py-1.5 rounded-xl border border-slate-700/80 text-xs focus:border-purple-500 focus:outline-none placeholder-slate-500"
          />
          <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 1114 0z" />
          </svg>
        </div>

        <!-- Filter Major -->
        <select v-model="selectedMajor" class="bg-[#121827] text-slate-200 border border-slate-700/80 rounded-xl px-3 py-1.5 text-xs focus:border-purple-500 focus:outline-none">
          <option value="">All 5 Majors</option>
          <option value="Information Technology">Information Technology</option>
          <option value="Agriculture">Agriculture</option>
          <option value="Tourism Management">Tourism Management</option>
          <option value="English Literature">English Literature</option>
          <option value="Social Work">Social Work</option>
        </select>

        <!-- Filter Severity -->
        <select v-model="selectedSeverity" class="bg-[#121827] text-slate-200 border border-slate-700/80 rounded-xl px-3 py-1.5 text-xs focus:border-purple-500 focus:outline-none">
          <option value="">All Severities</option>
          <option value="Critical">🔴 Critical (&lt; 50%)</option>
          <option value="Moderate">🟡 Moderate (50-60%)</option>
        </select>
      </div>

      <span class="text-slate-400 font-mono text-[11px]">
        Showing <strong>{{ filteredTopics.length }}</strong> difficult topics
      </span>
    </div>

    <!-- ── DIFFICULT TOPICS LIST TABLE (Spec 1) ── -->
    <div class="bg-[#0d1222]/95 border border-slate-700/60 rounded-2xl shadow-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
          <thead class="bg-slate-900 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-700/80">
            <tr>
              <th class="px-4 py-3">Topic</th>
              <th class="px-3 py-3">Course</th>
              <th class="px-3 py-3">Major</th>
              <th class="px-3 py-3 text-center">Students Assessed</th>
              <th class="px-3 py-3 text-center">Average Score</th>
              <th class="px-3 py-3 text-center">Difficulty Rate</th>
              <th class="px-3 py-3">Recommendation (AI Suggestion)</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr
              v-for="t in filteredTopics"
              :key="t.id"
              class="hover:bg-slate-800/40 transition-colors"
            >
              <!-- Topic -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="text-base">{{ t.severity === 'Critical' ? '🔴' : '🟡' }}</span>
                  <div>
                    <span class="font-bold text-white block">{{ t.topic }}</span>
                    <span class="text-[10px] text-teal-300 font-semibold">{{ t.remedial_status }}</span>
                  </div>
                </div>
              </td>

              <!-- Course -->
              <td class="px-3 py-3 text-white font-medium">
                {{ t.course }}
              </td>

              <!-- Major -->
              <td class="px-3 py-3">
                <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700/80 text-[10px] block w-fit">
                  {{ t.major }}
                </span>
              </td>

              <!-- Students Assessed -->
              <td class="px-3 py-3 text-center font-bold text-slate-200">
                {{ t.students_assessed }}
              </td>

              <!-- Average Score -->
              <td class="px-3 py-3 text-center">
                <span
                  class="px-2.5 py-0.5 rounded text-[11px] font-bold"
                  :class="t.average_score < 50 ? 'bg-red-500/20 text-red-300 border border-red-500/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/40'"
                >
                  {{ t.average_score }}%
                </span>
              </td>

              <!-- Difficulty Rate -->
              <td class="px-3 py-3 text-center">
                <span class="font-mono font-bold text-red-400">
                  {{ t.difficulty_rate }}% High
                </span>
              </td>

              <!-- Recommendation -->
              <td class="px-3 py-3 max-w-xs">
                <p class="text-[11px] text-slate-200 font-medium">
                  {{ t.recommendation }}
                </p>
                <p class="text-[10px] text-purple-300 italic">
                  «{{ t.recommendation_kh }}»
                </p>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3 text-right">
                <button
                  @click="openDetail(t)"
                  class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-lg text-xs font-semibold border border-slate-700 transition-all flex items-center gap-1 active:scale-95 ml-auto"
                >
                  <span>👁️</span>
                  <span>View</span>
                </button>
              </td>
            </tr>

            <tr v-if="filteredTopics.length === 0">
              <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                <span>No difficult topics found matching current filters.</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── MODAL: TOPIC INSIGHT & REMEDIAL ACTIONS ── -->
    <div
      v-if="showDetailModal && selectedTopic"
      class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 animate-fadeIn"
    >
      <div class="bg-[#0f172a] border border-slate-700/80 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl space-y-4 p-6">
        <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
          <div>
            <h4 class="font-black text-base text-white flex items-center gap-2">
              <span>🧠</span>
              <span>Topic Insight: {{ selectedTopic.topic }}</span>
            </h4>
            <span class="text-xs text-purple-300 font-mono">{{ selectedTopic.course }} · {{ selectedTopic.major }}</span>
          </div>
          <button @click="showDetailModal = false" class="text-slate-400 hover:text-white font-black text-base">✕</button>
        </div>

        <div class="grid grid-cols-3 gap-3 text-center">
          <div class="bg-slate-900/80 border border-slate-800 p-2.5 rounded-xl">
            <span class="text-slate-400 text-[10px] block">Average Score</span>
            <span class="text-base font-black text-red-400">{{ selectedTopic.average_score }}%</span>
          </div>
          <div class="bg-slate-900/80 border border-slate-800 p-2.5 rounded-xl">
            <span class="text-slate-400 text-[10px] block">Students Affected</span>
            <span class="text-base font-black text-amber-300">{{ selectedTopic.students_assessed }} Students</span>
          </div>
          <div class="bg-slate-900/80 border border-slate-800 p-2.5 rounded-xl">
            <span class="text-slate-400 text-[10px] block">Difficulty Rate</span>
            <span class="text-base font-black text-red-400">{{ selectedTopic.difficulty_rate }}%</span>
          </div>
        </div>

        <!-- Common Incorrect Answers -->
        <div class="space-y-2 p-3.5 rounded-xl bg-slate-900/80 border border-slate-800">
          <span class="text-slate-300 font-bold text-xs flex items-center gap-1.5">
            <span>❓</span>
            <span>Frequently Failed Questions (សំណួរដែលមានការឆ្លើយខុសច្រើន)៖</span>
          </span>
          <div class="space-y-1.5">
            <div
              v-for="(q, idx) in selectedTopic.common_incorrect_questions"
              :key="idx"
              class="flex items-center justify-between p-2 rounded-lg bg-black/40 border border-slate-800 text-[11px]"
            >
              <span class="text-slate-200 flex-1 pr-3">{{ q.question }}</span>
              <span class="text-red-400 font-bold shrink-0">{{ q.wrong_rate }}% Wrong</span>
            </div>
          </div>
        </div>

        <!-- Teacher Suggested Actions -->
        <div class="space-y-2 p-3.5 rounded-xl bg-purple-950/30 border border-purple-500/30">
          <span class="text-purple-200 font-bold text-xs flex items-center gap-1.5">
            <span>💡</span>
            <span>Suggested Teacher Interventions (សកម្មភាពដែលគ្រូគួរធ្វើ)៖</span>
          </span>
          <ul class="list-disc list-inside text-[11px] text-slate-300 space-y-1 pl-1">
            <li v-for="(act, idx) in selectedTopic.suggested_actions" :key="idx">
              {{ act }}
            </li>
          </ul>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
          <button @click="showDetailModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
            Close
          </button>
          <button
            @click="() => { showDetailModal = false; handleAddMaterial(selectedTopic); }"
            class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-teal-300 rounded-xl text-xs font-bold"
          >
            <span>📄 Add Material</span>
          </button>
          <button
            @click="() => { showDetailModal = false; handleCreateQuiz(selectedTopic); }"
            class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold shadow-md shadow-purple-600/30"
          >
            <span>✍️ Create Practice Quiz</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
