<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  config?: any
}>()

const emit = defineEmits<{
  (e: 'saveConfig', cfg: any): void
  (e: 'testConnection'): void
}>()

// 1. AI 4 Core Features Toggle (Spec 1)
const featureAiRecommendations = ref(true)
const featureAtRiskDetection = ref(true)
const featureDifficultTopics = ref(true)
const featureAiQuizGenerator = ref(true)

// 2. Recommendation Settings (Spec 2)
const minQuizAttempts = ref(1)
const minLearningActivityHours = ref(2)
const recommendationFrequency = ref('immediate')
const lowScoreThreshold = ref(50)
const weakTopicTrigger = ref(true)

// 3. At-Risk Rules & Thresholds (Spec 3)
const lowProgressThreshold = ref(40)
const lowQuizScoreThreshold = ref(50)
const inactiveDaysThreshold = ref(7)
const overdueAssignmentThreshold = ref(2)

// Engine Status & Testing
const aiEngineStatus = ref<'Active' | 'Testing' | 'Saved'>('Active')
const saveFeedback = ref('')

// 4. AI Activity Log (Spec 4)
interface ActivityLog {
  id: number
  feature: 'Recommendation' | 'Risk' | 'Difficulty' | 'Quiz'
  feature_badge: string
  user: string
  role: 'Student' | 'Teacher' | 'System'
  action: string
  date: string
  status: 'Success' | 'Failed'
}

const activityLogs = ref<ActivityLog[]>([
  {
    id: 1,
    feature: 'Recommendation',
    feature_badge: 'bg-purple-500/20 text-purple-300 border-purple-500/40',
    user: 'Sok Dara (SPI-2026-001)',
    role: 'Student',
    action: 'Generated next lesson "Conditional Statements" recommendation',
    date: 'Today, 22:15',
    status: 'Success',
  },
  {
    id: 2,
    feature: 'Risk',
    feature_badge: 'bg-red-500/20 text-red-300 border-red-500/40',
    user: 'Sok Piseth (SPI-2026-004)',
    role: 'Student',
    action: 'Flagged High Risk: Progress < 40% & Inactive 12 days',
    date: 'Today, 21:40',
    status: 'Success',
  },
  {
    id: 3,
    feature: 'Difficulty',
    feature_badge: 'bg-amber-500/20 text-amber-300 border-amber-500/40',
    user: 'Mr. Sophea (Faculty Instructor)',
    role: 'Teacher',
    action: 'Identified "JavaScript Functions" as Difficult Topic (Avg 45%)',
    date: 'Today, 20:30',
    status: 'Success',
  },
  {
    id: 4,
    feature: 'Quiz',
    feature_badge: 'bg-teal-500/20 text-teal-300 border-teal-500/40',
    user: 'Mr. Vuthy (Agriculture Teacher)',
    role: 'Teacher',
    action: 'Generated 10-Question Formative Quiz on Soil Management',
    date: 'Yesterday, 17:20',
    status: 'Success',
  },
  {
    id: 5,
    feature: 'Recommendation',
    feature_badge: 'bg-purple-500/20 text-purple-300 border-purple-500/40',
    user: 'Keo Monika (SPI-2026-002)',
    role: 'Student',
    action: 'Generated Honors Fast-track Heritage Tourism recommendation',
    date: 'Yesterday, 14:10',
    status: 'Success',
  },
  {
    id: 6,
    feature: 'Risk',
    feature_badge: 'bg-red-500/20 text-red-300 border-red-500/40',
    user: 'Khem Sovann (SPI-2026-019)',
    role: 'Student',
    action: 'Flagged High Risk: 3 overdue labs & quiz average 35%',
    date: 'Yesterday, 11:05',
    status: 'Success',
  }
])

function handleSaveAll() {
  saveFeedback.value = 'AI Configuration thresholds & feature policies updated successfully!'
  aiEngineStatus.value = 'Saved'
  emit('saveConfig', {
    features: {
      recommendations: featureAiRecommendations.value,
      at_risk: featureAtRiskDetection.value,
      difficult_topics: featureDifficultTopics.value,
      quiz_generator: featureAiQuizGenerator.value,
    },
    recommendation_settings: {
      min_attempts: minQuizAttempts.value,
      min_hours: minLearningActivityHours.value,
      frequency: recommendationFrequency.value,
      low_score: lowScoreThreshold.value,
    },
    at_risk_thresholds: {
      progress: lowProgressThreshold.value,
      quiz_score: lowQuizScoreThreshold.value,
      inactive_days: inactiveDaysThreshold.value,
      overdue_assignments: overdueAssignmentThreshold.value,
    }
  })
  setTimeout(() => {
    saveFeedback.value = ''
    aiEngineStatus.value = 'Active'
  }, 3500)
}
</script>

<template>
  <div class="space-y-6 text-xs font-sans">
    <!-- Save Success Toast -->
    <div
      v-if="saveFeedback"
      class="p-4 rounded-xl bg-emerald-950/90 border border-emerald-500 text-emerald-200 text-xs font-bold flex items-center justify-between shadow-xl animate-fadeIn"
    >
      <div class="flex items-center gap-2">
        <span class="text-base">✅</span>
        <span>{{ saveFeedback }}</span>
      </div>
      <button @click="saveFeedback = ''" class="text-emerald-400 hover:text-white font-black text-sm">✕</button>
    </div>

    <!-- ── HEADER BANNER ── -->
    <div class="bg-[#0d1222]/95 border border-purple-500/30 rounded-2xl p-5 shadow-2xl space-y-4">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-700/60 pb-4">
        <div>
          <h3 class="text-base font-black text-white flex items-center gap-2.5">
            <div class="p-2 rounded-xl bg-purple-500/15 border border-purple-500/30 text-purple-300 shrink-0">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="#C084FC" stroke-width="1.8" />
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="#C084FC" stroke-width="1.8" />
              </svg>
            </div>
            <span>AI CONFIGURATION & THRESHOLDS — ការកំណត់ប្រព័ន្ធ AI</span>
          </h3>
          <p class="text-slate-400 text-xs mt-1 font-medium">
            គោលបំណង៖ ឲ្យ Admin កំណត់របៀបដែល AI វិភាគ និងបង្កើត Recommendation/Alert ដោយមិនឲ្យ Admin ទៅកែ AI Model ដោយផ្ទាល់។
          </p>
        </div>

        <button
          @click="handleSaveAll"
          class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:brightness-110 text-white rounded-xl text-xs font-bold shadow-lg shadow-purple-600/30 flex items-center gap-2 active:scale-95 self-start md:self-auto"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>Save AI Configuration</span>
        </button>
      </div>

      <!-- ── SPEC 1: 4 CORE AI FEATURES (ON/OFF) ── -->
      <div class="space-y-3">
        <h4 class="font-bold text-xs text-white uppercase tracking-wider flex items-center gap-2">
          <span>⚡</span>
          <span>1. AI Core Features Activation (បើក/បិទ Feature ទាំង ៤)</span>
        </h4>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- Feature 1: AI Recommendations -->
          <div class="p-3.5 rounded-xl border bg-[#121827] flex items-center justify-between" :class="featureAiRecommendations ? 'border-purple-500/50' : 'border-slate-800'">
            <div>
              <span class="font-bold text-white block text-xs">AI Recommendations</span>
              <span class="text-[10px] text-slate-400">Adaptive next lesson drills</span>
            </div>
            <button
              type="button"
              @click="featureAiRecommendations = !featureAiRecommendations"
              class="w-12 h-6 rounded-full transition-colors relative focus:outline-none"
              :class="featureAiRecommendations ? 'bg-purple-600' : 'bg-slate-800'"
            >
              <span
                class="block w-4 h-4 bg-white rounded-full transition-transform absolute top-1 left-1"
                :class="featureAiRecommendations ? 'translate-x-6' : 'translate-x-0'"
              ></span>
            </button>
          </div>

          <!-- Feature 2: At-Risk Detection -->
          <div class="p-3.5 rounded-xl border bg-[#121827] flex items-center justify-between" :class="featureAtRiskDetection ? 'border-red-500/50' : 'border-slate-800'">
            <div>
              <span class="font-bold text-white block text-xs">At-Risk Detection</span>
              <span class="text-[10px] text-slate-400">Proactive student alerts</span>
            </div>
            <button
              type="button"
              @click="featureAtRiskDetection = !featureAtRiskDetection"
              class="w-12 h-6 rounded-full transition-colors relative focus:outline-none"
              :class="featureAtRiskDetection ? 'bg-red-600' : 'bg-slate-800'"
            >
              <span
                class="block w-4 h-4 bg-white rounded-full transition-transform absolute top-1 left-1"
                :class="featureAtRiskDetection ? 'translate-x-6' : 'translate-x-0'"
              ></span>
            </button>
          </div>

          <!-- Feature 3: Difficult Topics -->
          <div class="p-3.5 rounded-xl border bg-[#121827] flex items-center justify-between" :class="featureDifficultTopics ? 'border-amber-500/50' : 'border-slate-800'">
            <div>
              <span class="font-bold text-white block text-xs">Difficult Topics</span>
              <span class="text-[10px] text-slate-400">Cohort struggle analysis</span>
            </div>
            <button
              type="button"
              @click="featureDifficultTopics = !featureDifficultTopics"
              class="w-12 h-6 rounded-full transition-colors relative focus:outline-none"
              :class="featureDifficultTopics ? 'bg-amber-600' : 'bg-slate-800'"
            >
              <span
                class="block w-4 h-4 bg-white rounded-full transition-transform absolute top-1 left-1"
                :class="featureDifficultTopics ? 'translate-x-6' : 'translate-x-0'"
              ></span>
            </button>
          </div>

          <!-- Feature 4: AI Quiz Generator -->
          <div class="p-3.5 rounded-xl border bg-[#121827] flex items-center justify-between" :class="featureAiQuizGenerator ? 'border-teal-500/50' : 'border-slate-800'">
            <div>
              <span class="font-bold text-white block text-xs">AI Quiz Generator</span>
              <span class="text-[10px] text-slate-400">Teacher AI Assistant</span>
            </div>
            <button
              type="button"
              @click="featureAiQuizGenerator = !featureAiQuizGenerator"
              class="w-12 h-6 rounded-full transition-colors relative focus:outline-none"
              :class="featureAiQuizGenerator ? 'bg-teal-600' : 'bg-slate-800'"
            >
              <span
                class="block w-4 h-4 bg-white rounded-full transition-transform absolute top-1 left-1"
                :class="featureAiQuizGenerator ? 'translate-x-6' : 'translate-x-0'"
              ></span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── SPEC 2 & 3: RECOMMENDATION SETTINGS & AT-RISK THRESHOLDS ── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <!-- Section 2: Recommendation Settings -->
      <div class="bg-[#0d1222]/95 border border-slate-700/60 rounded-2xl p-5 shadow-2xl space-y-4">
        <h4 class="font-bold text-xs text-white uppercase tracking-wider border-b border-slate-700/60 pb-2.5 flex items-center gap-2">
          <span>🎯</span>
          <span>2. Recommendation Settings (ការកំណត់ការផ្ដល់អនុសាសន៍)</span>
        </h4>

        <div class="space-y-3.5">
          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="text-[11px] font-semibold text-slate-300">Minimum Quiz Attempts Before Analysis:</label>
              <span class="font-mono font-bold text-purple-300">{{ minQuizAttempts }} Attempt(s)</span>
            </div>
            <input
              v-model.number="minQuizAttempts"
              type="range"
              min="1"
              max="5"
              class="w-full accent-purple-500 bg-slate-800 rounded-lg cursor-pointer"
            />
            <p class="text-[10px] text-slate-500 mt-0.5">ឧ. Quiz attempts ≥ 1 → AI ចាប់ផ្តើមវិភាគលទ្ធផល</p>
          </div>

          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="text-[11px] font-semibold text-slate-300">Minimum Weekly Learning Activity:</label>
              <span class="font-mono font-bold text-purple-300">{{ minLearningActivityHours }} Hours / Week</span>
            </div>
            <input
              v-model.number="minLearningActivityHours"
              type="range"
              min="1"
              max="10"
              class="w-full accent-purple-500 bg-slate-800 rounded-lg cursor-pointer"
            />
          </div>

          <div>
            <label class="text-[11px] font-semibold text-slate-300 block mb-1">Recommendation Delivery Frequency:</label>
            <select
              v-model="recommendationFrequency"
              class="w-full bg-[#121827] text-white border border-slate-700/80 rounded-xl px-3 py-2 text-xs focus:border-purple-500 focus:outline-none"
            >
              <option value="immediate">⚡ Real-time Immediate (After Quiz / Lesson Complete)</option>
              <option value="daily">📅 Daily Batch Compilation (End of Study Day)</option>
              <option value="weekly">🗓️ Weekly Summary Recommendation</option>
            </select>
          </div>

          <div class="p-3 bg-purple-950/20 border border-purple-500/30 rounded-xl text-[11px] text-purple-200 space-y-1">
            <span class="font-bold block">✨ Automated Triggers:</span>
            <p>• Low Quiz Score (&lt; {{ lowScoreThreshold }}%) → Recommend remedial review notes & drill</p>
            <p>• Weak Topic Detected → Recommend topic practice quiz & teacher revision material</p>
          </div>
        </div>
      </div>

      <!-- Section 3: At-Risk Rules & Thresholds -->
      <div class="bg-[#0d1222]/95 border border-slate-700/60 rounded-2xl p-5 shadow-2xl space-y-4">
        <h4 class="font-bold text-xs text-white uppercase tracking-wider border-b border-slate-700/60 pb-2.5 flex items-center gap-2">
          <span>⚠️</span>
          <span>3. At-Risk Rules & Thresholds (លក្ខខណ្ឌកំណត់សិស្សប្រឈមហានិភ័យ)</span>
        </h4>

        <div class="space-y-3.5">
          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="text-[11px] font-semibold text-slate-300">Low Progress Threshold:</label>
              <span class="font-mono font-bold text-red-400">&lt; {{ lowProgressThreshold }}%</span>
            </div>
            <input
              v-model.number="lowProgressThreshold"
              type="range"
              min="20"
              max="60"
              class="w-full accent-red-500 bg-slate-800 rounded-lg cursor-pointer"
            />
          </div>

          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="text-[11px] font-semibold text-slate-300">Low Quiz Score Threshold:</label>
              <span class="font-mono font-bold text-red-400">&lt; {{ lowQuizScoreThreshold }}%</span>
            </div>
            <input
              v-model.number="lowQuizScoreThreshold"
              type="range"
              min="30"
              max="70"
              class="w-full accent-red-500 bg-slate-800 rounded-lg cursor-pointer"
            />
          </div>

          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="text-[11px] font-semibold text-slate-300">Inactive Days Threshold (No Activity):</label>
              <span class="font-mono font-bold text-amber-300">&gt; {{ inactiveDaysThreshold }} Days</span>
            </div>
            <input
              v-model.number="inactiveDaysThreshold"
              type="range"
              min="3"
              max="21"
              class="w-full accent-amber-500 bg-slate-800 rounded-lg cursor-pointer"
            />
          </div>

          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="text-[11px] font-semibold text-slate-300">Overdue Assignment Threshold:</label>
              <span class="font-mono font-bold text-amber-300">≥ {{ overdueAssignmentThreshold }} Assignments</span>
            </div>
            <input
              v-model.number="overdueAssignmentThreshold"
              type="range"
              min="1"
              max="5"
              class="w-full accent-amber-500 bg-slate-800 rounded-lg cursor-pointer"
            />
          </div>

          <!-- Strict Non-Punitive Clarification Note (Spec 3) -->
          <div class="p-3 bg-blue-950/30 border border-blue-500/40 rounded-xl text-[11px] text-blue-200">
            <strong>💡 ចំណាំសំខាន់៖</strong> Threshold គឺជា Configuration សម្រាប់ AI មិនមែនមានន័យថា Student ដែលឆ្លង Threshold ត្រូវបានសម្រេចថា “បរាជ័យ” ទេ។ AI គ្រាន់តែបង្កើត Flag ឲ្យ Teacher/Admin ពិនិត្យ និងជួយគាំទ្រប៉ុណ្ណោះ។
          </div>
        </div>
      </div>
    </div>

    <!-- ── SPEC 4: AI ACTIVITY LOG (សកម្មភាព AI ក្នុងប្រព័ន្ធ) ── -->
    <div class="bg-[#0d1222]/95 border border-slate-700/60 rounded-2xl p-5 shadow-2xl space-y-4">
      <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
        <div>
          <h4 class="font-black text-sm text-white uppercase tracking-wide flex items-center gap-2">
            <span>📜</span>
            <span>4. AI Activity Log (កំណត់ត្រាសកម្មភាព AI)</span>
          </h4>
          <p class="text-slate-400 text-xs mt-0.5">វាជួយ Admin តាមដានថា AI កំពុងដំណើរការយ៉ាងដូចម្តេចតាមពេលវេលាជាក់ស្តែង។</p>
        </div>
        <span class="text-[11px] text-slate-400 font-mono">Total Logged Events: <strong>{{ activityLogs.length }}</strong></span>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-700/80 bg-[#121827]">
        <table class="w-full text-left text-xs text-slate-300">
          <thead class="bg-slate-900 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-700/80">
            <tr>
              <th class="px-4 py-3">Feature</th>
              <th class="px-3 py-3">User</th>
              <th class="px-3 py-3">Action</th>
              <th class="px-3 py-3">Date</th>
              <th class="px-3 py-3 text-right">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr v-for="log in activityLogs" :key="log.id" class="hover:bg-slate-800/40 transition-colors">
              <td class="px-4 py-3">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border inline-block" :class="log.feature_badge">
                  {{ log.feature }}
                </span>
              </td>
              <td class="px-3 py-3">
                <span class="font-bold text-white block">{{ log.user }}</span>
                <span class="text-[10px] text-slate-400">{{ log.role }}</span>
              </td>
              <td class="px-3 py-3 text-slate-200 font-medium">
                {{ log.action }}
              </td>
              <td class="px-3 py-3 text-slate-400 font-mono text-[11px]">
                {{ log.date }}
              </td>
              <td class="px-3 py-3 text-right">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                  {{ log.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
