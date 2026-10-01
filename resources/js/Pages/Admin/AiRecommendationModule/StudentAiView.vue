<script setup lang="ts">
import { ref, computed } from 'vue'

const emit = defineEmits<{
  (e: 'notify', msg: string): void
}>()

const hiddenCard = ref(false)

interface StudentProfile {
  id: string
  name: string
  code: string
  major: string
  majorCode: string
  type: string
  badgeColor: string
  progressText: string
  progressPercent: number
  weakTopics: Array<{ title: string; score: number; severity: 'high' | 'medium'; remedialAction: string }>
  recommendation: string
  recommendationKh: string
  reason: string
  nextModule: string
}

const selectedStudentId = ref('STU-SPI-001')
const studentsList = ref<StudentProfile[]>([
  {
    id: 'STU-SPI-001',
    name: 'Sok Dara',
    code: 'SPI-2026-001',
    major: 'Information Technology',
    majorCode: 'MJR-IT-001',
    type: 'Average Student',
    badgeColor: 'bg-purple-500/20 text-purple-300 border-purple-500/40',
    progressText: 'In Progress (72%)',
    progressPercent: 72,
    weakTopics: [
      { title: 'Loops in C (For & While)', score: 45, severity: 'high', remedialAction: '🎥 Re-watch Loops Drill' },
      { title: 'SQL Joins & Grouping', score: 50, severity: 'medium', remedialAction: '✍ Practice Quiz' },
    ],
    recommendation: 'After completing Unit 2, you should learn "Conditional Logic & Flow" to solidify your algorithmic foundation before advancing to Loops.',
    recommendationKh: 'បន្ទាប់ពីបញ្ចប់ Unit 2 និស្សិតគួរសិក្សា "Conditional Logic & Control Flow" ដើម្បីពង្រឹងមូលដ្ឋានកូដ មុននឹងបន្តទៅ Loops។',
    reason: 'Because your Operators & Expressions quiz score was 72% (target threshold) with a detected conceptual drop-off on nested loops (45%).',
    nextModule: '3. Conditional Statements & Control Flow'
  },
  {
    id: 'STU-SPI-002',
    name: 'Keo Monika',
    code: 'SPI-2026-002',
    major: 'Tourism Management',
    majorCode: 'MJR-TRM-002',
    type: 'High Performer',
    badgeColor: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
    progressText: 'Advanced (94%)',
    progressPercent: 94,
    weakTopics: [],
    recommendation: 'Fast-Track: You have unlocked "Sustainable Heritage Tourism Leadership & Field Case Study".',
    recommendationKh: 'Fast-Track: និស្សិតមានសិទ្ធិចូលរៀនវគ្គកម្រិតខ្ពស់ "Sustainable Heritage Tourism Leadership" ដោយផ្ទាល់។',
    reason: 'Because your average score across all tourism assessments is 94% (≥90% criteria), allowing honors bypass.',
    nextModule: '🚀 Fast-Track: Heritage Tourism Leadership'
  },
  {
    id: 'STU-SPI-003',
    name: 'Long Vichida',
    code: 'SPI-2026-003',
    major: 'English Literature',
    majorCode: 'MJR-ENG-003',
    type: 'Moderate Pace',
    badgeColor: 'bg-sky-500/20 text-sky-300 border-sky-500/40',
    progressText: 'In Progress (68%)',
    progressPercent: 68,
    weakTopics: [
      { title: 'Complex Clauses & Sentence Synthesis', score: 48, severity: 'high', remedialAction: '📄 Grammar Synthesis Cheat Sheet' },
    ],
    recommendation: 'Review the grammar sentence synthesis drill before submitting your Week 4 research essay draft.',
    recommendationKh: 'ពិនិត្យលំហាត់អនុវត្ត Sentence Synthesis ឡើងវិញ មុនពេលដាក់ស្នើ Essay សិក្សាស្រាវជ្រាវ Week 4។',
    reason: 'Because quiz score in Complex Clauses dropped to 48% (below 50% target threshold).',
    nextModule: 'Unit 4: Advanced Sentence Synthesis & Clauses'
  },
  {
    id: 'STU-SPI-004',
    name: 'Pov Sreynich',
    code: 'SPI-2026-004',
    major: 'Agriculture',
    majorCode: 'MJR-AGR-004',
    type: 'Struggling Student',
    badgeColor: 'bg-rose-500/20 text-rose-300 border-rose-500/40',
    progressText: 'In Progress (42%)',
    progressPercent: 42,
    weakTopics: [
      { title: 'Soil pH & Nutrient Absorption', score: 35, severity: 'high', remedialAction: '🎥 Soil Chemistry Video' },
      { title: 'Crop Pest Identification', score: 48, severity: 'high', remedialAction: '✍ Pest Identification Drill' },
    ],
    recommendation: 'We strongly suggest taking the Remedial Drill on "Soil Chemistry Fundamentals" before proceeding to Fertilizers.',
    recommendationKh: 'ប្រព័ន្ធណែនាំឱ្យចូលមើលមេរៀនជំនួយ "Soil Chemistry Fundamentals" ជាបន្ទាន់ មុននឹងបន្តទៅមេរៀនជីកសិកម្ម។',
    reason: 'Because your quiz score was below 50% threshold in Soil pH & Nutrient Absorption (35%).',
    nextModule: '🆘 Remedial: Soil Chemistry Fundamentals'
  },
  {
    id: 'STU-SPI-005',
    name: 'Bun Rithy',
    code: 'SPI-2026-005',
    major: 'Social Work',
    majorCode: 'MJR-SW-005',
    type: 'Active Learner',
    badgeColor: 'bg-teal-500/20 text-teal-300 border-teal-500/40',
    progressText: 'In Progress (85%)',
    progressPercent: 85,
    weakTopics: [
      { title: 'Field Counseling Legal Ethics', score: 58, severity: 'medium', remedialAction: '📝 Case Study Drill' },
    ],
    recommendation: 'Recommended Case Study: Review Cambodian Child Welfare Legal Framework before the field simulation.',
    recommendationKh: 'រំលឹកករណីសិក្សា "Cambodian Child Welfare Legal Framework" មុនពេលចូលរួមការប្រជុំត្រាប់ជាក់ស្តែង។',
    reason: 'High progress (85%) with specific reinforcement recommended on ethical counseling protocols (58%).',
    nextModule: 'Unit 5: Child Protection Protocols & Case Studies'
  }
])

const currentStudent = computed(() => {
  return studentsList.value.find(s => s.id === selectedStudentId.value) || studentsList.value[0]
})

function handleStartLesson(moduleName: string) {
  emit('notify', `Student launched lesson "${moduleName}"!`)
}

function handleReviewTopic(topicTitle: string) {
  emit('notify', `Opened review material for weak topic "${topicTitle}".`)
}
</script>

<template>
  <div class="space-y-6 text-xs font-sans">
    <!-- Header Banner with Interactive Student Switcher Dropdown -->
    <div class="bg-[#0d1222]/95 border border-purple-500/30 rounded-2xl p-5 shadow-2xl flex flex-col md:flex-row items-center justify-between gap-4">
      <div>
        <h3 class="text-base font-black text-white flex items-center gap-2.5">
          <div class="p-2 rounded-xl bg-gradient-to-br from-purple-500/20 to-indigo-500/20 border border-purple-400/30 text-purple-300 shrink-0">
            <svg class="w-4 h-4 text-purple-300" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="9" stroke="#C084FC" stroke-width="1.8" />
              <circle cx="12" cy="12" r="3" fill="#A855F7" />
            </svg>
          </div>
          <span>STUDENT VIEW PREVIEW — អ្វីដែល STUDENT ឃើញពី AI</span>
        </h3>
        <p class="text-slate-400 text-xs mt-0.5">
          Demonstrates how the AI Engine transforms Student Learning Data → AI Analysis → Weak Topics → "អ្វីដែល Student គួររៀនបន្ទាប់" across the 5 SPI majors.
        </p>
      </div>

      <!-- Highlighted Main Preview Control Switcher -->
      <div class="flex items-center gap-2.5 bg-gradient-to-r from-purple-950/90 via-indigo-950/90 to-purple-900/90 p-2.5 rounded-2xl border-2 border-purple-500/80 shadow-lg shadow-purple-600/20 shrink-0 ring-1 ring-purple-400/40">
        <div class="flex items-center gap-1.5 text-purple-300 font-bold text-xs pl-1">
          <svg class="w-4 h-4 text-purple-300 animate-pulse" viewBox="0 0 24 24" fill="none">
            <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="1.8" />
            <path d="M12 14C7.58172 14 4 17.5817 4 22H20C20 17.5817 16.4183 14 12 14Z" stroke="currentColor" stroke-width="1.8" />
          </svg>
          <span class="whitespace-nowrap">Major & Student Profile:</span>
        </div>
        <select
          v-model="selectedStudentId"
          class="bg-purple-950 text-white font-black border border-purple-400/60 rounded-xl px-3.5 py-1.5 text-xs focus:border-purple-300 focus:outline-none cursor-pointer shadow-inner hover:bg-purple-900 transition-colors"
        >
          <option v-for="s in studentsList" :key="s.id" :value="s.id" class="bg-slate-900 text-white font-semibold">
            🎓 {{ s.major }} — {{ s.name }} ({{ s.type }})
          </option>
        </select>
      </div>
    </div>

    <!-- Student Interface Mock Frame -->
    <div class="bg-[#090d16] border-2 border-purple-500/40 rounded-2xl p-6 shadow-2xl space-y-6 max-w-3xl mx-auto">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-3.5">
        <div class="flex items-center gap-2.5">
          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ currentStudent.name.charAt(0) }}
          </div>
          <div>
            <h4 class="font-black text-sm text-white flex items-center gap-2">
              <span>AI LEARNING PATH</span>
              <span class="text-slate-400 font-normal text-xs">— {{ currentStudent.name }} ({{ currentStudent.code }})</span>
            </h4>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-teal-500/20 text-teal-300 border border-teal-500/40">
                Major: {{ currentStudent.major }}
              </span>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="currentStudent.badgeColor">
                {{ currentStudent.type }}
              </span>
            </div>
          </div>
        </div>

        <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 font-mono text-[10px] font-bold self-start sm:self-auto">
          LIVE AI PREVIEW
        </span>
      </div>

      <!-- 🛤️ YOUR PERSONALIZED LEARNING PATH -->
      <div class="space-y-2.5 bg-[#121827] p-4.5 rounded-xl border border-slate-700/80">
        <h5 class="font-bold text-xs text-slate-300 flex items-center gap-2 mb-3">
          <svg class="w-4 h-4 text-purple-400" viewBox="0 0 24 24" fill="none">
            <path d="M4 6H20M4 12H20M4 18H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
          <span>YOUR PERSONALIZED LEARNING PATH</span>
        </h5>

        <div class="space-y-2 font-medium">
          <!-- Node 1 -->
          <div class="p-3 rounded-xl bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <span class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-black flex items-center justify-center">✓</span>
              <span class="font-bold text-white">1. Basics of C Programming</span>
            </div>
            <span class="px-2.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[10px] font-bold border border-emerald-500/40">Completed</span>
          </div>

          <!-- Node 2 -->
          <div class="p-3 rounded-xl bg-amber-950/40 border border-amber-500/30 text-amber-300 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <span class="w-5 h-5 rounded-full bg-amber-500/20 text-amber-400 text-xs font-black flex items-center justify-center">⟳</span>
              <span class="font-bold text-white">2. Operators & Expressions in C</span>
            </div>
            <span class="px-2.5 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[10px] font-bold border border-amber-500/40">{{ currentStudent.progressText }}</span>
          </div>

          <!-- Node 3: Active Next Recommended Node with Clear Hover & Chevron Indicator -->
          <div
            @click="handleStartLesson(currentStudent.nextModule)"
            class="group p-3.5 rounded-xl bg-gradient-to-r from-purple-950/60 to-indigo-950/60 border-2 border-purple-500/80 text-purple-300 flex items-center justify-between hover:border-purple-400 cursor-pointer transition-all shadow-md active:scale-95"
          >
            <div class="flex items-center gap-2.5">
              <span class="w-5 h-5 rounded-full bg-purple-500/30 text-purple-200 text-xs font-black flex items-center justify-center">★</span>
              <span class="font-bold text-white text-xs group-hover:text-purple-200 transition-colors">{{ currentStudent.nextModule }}</span>
            </div>

            <!-- Chevron Action Arrow -->
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded bg-purple-500/30 text-purple-200 text-[10px] font-bold border border-purple-400/40">Next Recommended</span>
              <span class="text-purple-400 group-hover:text-white font-bold text-sm transition-transform group-hover:translate-x-1">❯</span>
            </div>
          </div>

          <!-- Node 4 Locked -->
          <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-slate-500 flex items-center justify-between opacity-70">
            <div class="flex items-center gap-2.5">
              <span>🔒</span>
              <span>4. Loops in C (For & While)</span>
            </div>
            <span class="text-[10px]">Locked</span>
          </div>
        </div>
      </div>

      <!-- 🎯 WEAK TOPICS (Focus More) with Standardized Review Buttons -->
      <div v-if="currentStudent.weakTopics.length > 0" class="space-y-2.5 bg-[#121827] p-4.5 rounded-xl border border-slate-700/80">
        <h5 class="font-bold text-xs text-red-400 flex items-center gap-2 mb-3">
          <svg class="w-4 h-4 text-red-400" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" />
            <circle cx="12" cy="12" r="3" fill="#F87171" />
          </svg>
          <span>WEAK TOPICS (RECOMMENDED DRILLS)</span>
        </h5>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div
            v-for="(w, idx) in currentStudent.weakTopics"
            :key="idx"
            class="p-3.5 rounded-xl border flex items-center justify-between gap-3 bg-[#0d1222]/90 border-slate-700/80"
          >
            <div>
              <span class="font-bold text-white block text-xs mb-1">{{ w.title }}</span>
              <!-- Score Severity Badge -->
              <span
                class="px-2 py-0.5 rounded text-[10px] font-bold inline-block"
                :class="w.severity === 'high' ? 'bg-red-500/20 text-red-300 border border-red-500/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/40'"
              >
                Quiz Score: {{ w.score }}%
              </span>
            </div>

            <!-- Standardized Secondary Style Review Button -->
            <button
              @click="handleReviewTopic(w.title)"
              class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 hover:text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-all shadow-sm active:scale-95 shrink-0"
            >
              <svg class="w-3.5 h-3.5 text-amber-400" viewBox="0 0 24 24" fill="none">
                <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke="currentColor" stroke-width="1.8" />
              </svg>
              <span>Review Now</span>
            </button>
          </div>
        </div>
      </div>

      <!-- 🤖 AI RECOMMENDATION CARD (No Duplicate Text Link) -->
      <div v-if="!hiddenCard" class="bg-gradient-to-r from-purple-950/80 via-indigo-950/80 to-slate-900 border-2 border-purple-500 p-5 rounded-2xl shadow-2xl space-y-3.5">
        <div class="flex items-center justify-between border-b border-purple-500/30 pb-2.5">
          <span class="font-black text-xs text-purple-300 flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-400" viewBox="0 0 24 24" fill="none">
              <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#A855F7" fill-opacity="0.3" stroke="#C084FC" stroke-width="1.8" />
            </svg>
            <span>AI PERSONALIZED RECOMMENDATION</span>
          </span>
          <button @click="hiddenCard = true" class="text-slate-400 hover:text-white text-[11px] flex items-center gap-1">
            <span>🙈</span>
            <span>Dismiss Card</span>
          </button>
        </div>

        <div class="space-y-2">
          <div class="text-[11px] font-bold text-teal-300 flex items-center gap-1.5">
            <span>🇰🇭 អ្វីដែល Student គួររៀនបន្ទាប់ (AI Recommended Next Step)៖</span>
          </div>
          <p class="text-white font-black text-xs leading-relaxed bg-purple-950/70 p-3 rounded-xl border border-purple-500/50 shadow-inner">
            «{{ currentStudent.recommendationKh }}»
          </p>
          <p class="text-slate-300 font-medium text-[11px] leading-relaxed italic pl-1">
            "{{ currentStudent.recommendation }}"
          </p>
        </div>

        <!-- Transparency Box (Clean & Single Instance) -->
        <div class="p-3 bg-[#121827] rounded-xl border border-purple-500/30 text-[11px] text-slate-300 space-y-1">
          <div class="font-bold text-purple-300 flex items-center gap-1.5">
            <span>💡</span>
            <span>មូលហេតុដែល AI ណែនាំមេរៀននេះ (Reasoning Trace)៖</span>
          </div>
          <p class="text-slate-200 font-medium pl-5">
            {{ currentStudent.reason }}
          </p>
        </div>

        <!-- Clean Action Row without Duplicate Text Link -->
        <div class="flex justify-end pt-1">
          <button
            @click="handleStartLesson(currentStudent.nextModule)"
            class="px-5 py-2.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl font-bold shadow-lg shadow-purple-600/30 flex items-center gap-2 transition-all active:scale-95 ring-1 ring-purple-400/50"
          >
            <svg class="w-4 h-4 text-purple-200" viewBox="0 0 24 24" fill="none">
              <path d="M5 3L19 12L5 21V3Z" fill="currentColor" />
            </svg>
            <span>Start Next Lesson Now</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
