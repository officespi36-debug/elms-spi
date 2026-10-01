<script setup lang="ts">
import { ref, computed } from 'vue'

export interface AtRiskStudent {
  id: number
  student_name: string
  student_code: string
  avatar?: string
  major: string
  course: string
  progress: number
  quiz_average: number
  last_activity: string
  inactive_days: number
  overdue_assignments: number
  risk_level: 'High' | 'Medium' | 'Low'
  reason: string
  reason_kh: string
  teacher_name: string
  teacher_alerted: boolean
}

const props = withDefaults(defineProps<{
  students?: AtRiskStudent[]
  majors?: any[]
}>(), {
  students: () => [
    {
      id: 1,
      student_name: 'Sok Piseth',
      student_code: 'SPI-2026-004',
      major: 'Agriculture',
      course: 'Soil Science & Crop Nutrition',
      progress: 32,
      quiz_average: 38,
      last_activity: '12 days ago',
      inactive_days: 12,
      overdue_assignments: 2,
      risk_level: 'High',
      reason: 'Low progress (32%) + low quiz score (38%) + inactive learning (12 days) + 2 overdue assignments',
      reason_kh: 'វឌ្ឍនភាពទាប (32%) + ពិន្ទុ Quiz ខ្សោយ (38%) + អសកម្ម 12 ថ្ងៃ + ជំពាក់កិច្ចការ 2',
      teacher_name: 'Mr. Vuthy',
      teacher_alerted: true,
    },
    {
      id: 2,
      student_name: 'Dara Chamnan',
      student_code: 'SPI-2026-001',
      major: 'Information Technology',
      course: 'Web Development Basics',
      progress: 35,
      quiz_average: 42,
      last_activity: '10 days ago',
      inactive_days: 10,
      overdue_assignments: 2,
      risk_level: 'High',
      reason: 'Low progress + low quiz performance + inactive learning activity',
      reason_kh: 'វឌ្ឍនភាពសិក្សាទាប (35%) + ពិន្ទុ Quiz 42% + អសកម្ម 10 ថ្ងៃ + ជំពាក់កិច្ចការ 2',
      teacher_name: 'Mr. Sophea',
      teacher_alerted: true,
    },
    {
      id: 3,
      student_name: 'Channak Neary',
      student_code: 'SPI-2026-008',
      major: 'Tourism Management',
      course: 'Tourism Operations & Booking Systems',
      progress: 48,
      quiz_average: 52,
      last_activity: '6 days ago',
      inactive_days: 6,
      overdue_assignments: 1,
      risk_level: 'Medium',
      reason: 'Declining quiz scores over last 2 modules + 1 overdue assignment',
      reason_kh: 'ពិន្ទុ Quiz ធ្លាក់ចុះក្នុង 2 modules ចុងក្រោយ + ជំពាក់កិច្ចការ 1',
      teacher_name: 'Mr. Long',
      teacher_alerted: true,
    },
    {
      id: 4,
      student_name: 'Sokha Rith',
      student_code: 'SPI-2026-011',
      major: 'English Literature',
      course: 'Academic English Grammar',
      progress: 54,
      quiz_average: 58,
      last_activity: '4 days ago',
      inactive_days: 4,
      overdue_assignments: 1,
      risk_level: 'Medium',
      reason: 'Struggling on Complex Sentence module quizzes + slow learning pace',
      reason_kh: 'ជួបការលំបាកលើកម្រងសំណួរ Complex Sentences + ល្បឿនរៀនយឺត',
      teacher_name: 'Ms. Srey',
      teacher_alerted: false,
    },
    {
      id: 5,
      student_name: 'Mao Vireak',
      student_code: 'SPI-2026-015',
      major: 'Social Work',
      course: 'Child Protection Protocols',
      progress: 68,
      quiz_average: 65,
      last_activity: '2 days ago',
      inactive_days: 2,
      overdue_assignments: 0,
      risk_level: 'Low',
      reason: 'Minor quiz drop on Ethics case study, but actively submitting lessons',
      reason_kh: 'ធ្លាក់ពិន្ទុបន្តិចលើករណីសិក្សា Ethics ប៉ុន្តែសកម្មភាពរៀនសូត្រនៅល្អ',
      teacher_name: 'Mr. Chan',
      teacher_alerted: false,
    },
    {
      id: 6,
      student_name: 'Khem Sovann',
      student_code: 'SPI-2026-019',
      major: 'Information Technology',
      course: 'Object-Oriented Programming (C++)',
      progress: 28,
      quiz_average: 35,
      last_activity: '14 days ago',
      inactive_days: 14,
      overdue_assignments: 3,
      risk_level: 'High',
      reason: 'Inactive > 14 days + severely low quiz score (35%) + 3 missing labs',
      reason_kh: 'អសកម្មលើសពី 14 ថ្ងៃ + ពិន្ទុ Quiz 35% + មិនទាន់ប្រគល់ Lab 3',
      teacher_name: 'Mr. Sophea',
      teacher_alerted: true,
    }
  ],
  majors: () => []
})

const emit = defineEmits<{
  (e: 'contactStudent', student: AtRiskStudent): void
  (e: 'viewDetail', student: AtRiskStudent): void
}>()

// Filter State
const search = ref('')
const selectedMajor = ref('')
const selectedRiskLevel = ref('')
const selectedCourse = ref('')

// Modal States
const showDetailModal = ref(false)
const showContactModal = ref(false)
const selectedStudent = ref<AtRiskStudent | null>(null)
const contactMessage = ref('')
const contactSubject = ref('ការគាំទ្រការសិក្សាពីគ្រូបង្រៀន — Academic Support Intervention')
const supportAction = ref<'tutoring' | 'material' | 'extension'>('tutoring')
const actionSuccessMsg = ref('')

const filteredStudents = computed(() => {
  return props.students.filter(s => {
    const matchSearch = !search.value || 
      s.student_name.toLowerCase().includes(search.value.toLowerCase()) ||
      s.student_code.toLowerCase().includes(search.value.toLowerCase()) ||
      s.course.toLowerCase().includes(search.value.toLowerCase())

    const matchMajor = !selectedMajor.value || s.major === selectedMajor.value
    const matchRisk = !selectedRiskLevel.value || s.risk_level === selectedRiskLevel.value
    const matchCourse = !selectedCourse.value || s.course === selectedCourse.value

    return matchSearch && matchMajor && matchRisk && matchCourse
  })
})

const highRiskCount = computed(() => props.students.filter(s => s.risk_level === 'High').length)
const mediumRiskCount = computed(() => props.students.filter(s => s.risk_level === 'Medium').length)
const lowRiskCount = computed(() => props.students.filter(s => s.risk_level === 'Low').length)

function openDetail(student: AtRiskStudent) {
  selectedStudent.value = student
  showDetailModal.value = true
}

function openContact(student: AtRiskStudent) {
  selectedStudent.value = student
  contactMessage.value = `ជម្រាបសួរ ${student.student_name}! គ្រូបានកត់សម្គាល់ឃើញថាអ្នកមានការយឺតយ៉ាវលើវគ្គសិក្សា "${student.course}"។ តើអ្នកមានការលំបាកផ្នែកណាដែរទេ? គ្រូអាចរៀបចំវគ្គពន្យល់បន្ថែម (1-on-1 Tutoring Session) សម្រាប់អ្នកបាន។`
  showContactModal.value = true
}

function sendSupportIntervention() {
  if (!selectedStudent.value) return
  actionSuccessMsg.value = `បានផ្ញើសារគាំទ្រ និងដំណោះស្រាយទៅកាន់ ${selectedStudent.value.student_name} និងលោកគ្រូ/អ្នកគ្រូ ${selectedStudent.value.teacher_name} ដោយជោគជ័យ!`
  showContactModal.value = false
  setTimeout(() => {
    actionSuccessMsg.value = ''
  }, 4000)
}
</script>

<template>
  <div class="space-y-6 text-xs font-sans">
    <!-- Success Banner -->
    <div
      v-if="actionSuccessMsg"
      class="p-4 rounded-xl bg-emerald-950/80 border border-emerald-500/60 text-emerald-200 text-xs font-bold flex items-center justify-between shadow-lg shadow-emerald-950/50 animate-fadeIn"
    >
      <div class="flex items-center gap-2">
        <span class="text-base">✅</span>
        <span>{{ actionSuccessMsg }}</span>
      </div>
      <button @click="actionSuccessMsg = ''" class="text-emerald-400 hover:text-white font-black text-sm">✕</button>
    </div>

    <!-- ── HEADER BANNER & AI ANALYSIS FORMULA ── -->
    <div class="bg-[#0d1222]/95 border border-purple-500/30 rounded-2xl p-5 shadow-2xl space-y-4">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-700/60 pb-4">
        <div>
          <h3 class="text-base font-black text-white flex items-center gap-2.5">
            <div class="p-2 rounded-xl bg-red-500/15 border border-red-500/30 text-red-400 shrink-0">
              <svg class="w-5 h-5 animate-pulse" viewBox="0 0 24 24" fill="none">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              </svg>
            </div>
            <span>AI AT-RISK STUDENTS — ការតាមដាន និងអន្តរាគមន៍ទាន់ពេល</span>
          </h3>
          <p class="text-slate-400 text-xs mt-1 font-medium">
            គោលបំណង៖ ឲ្យ AI រកឃើញ Student ដែលមានសញ្ញាថាកំពុងមានបញ្ហាក្នុងការសិក្សា ដើម្បីឲ្យ Teacher អាចជួយបានទាន់ពេល។
          </p>
        </div>

        <!-- Strict Non-Punitive Guarantee Badge (Spec 3) -->
        <div class="px-3.5 py-2 rounded-xl bg-blue-950/40 border border-blue-500/40 text-blue-200 text-[11px] font-semibold flex items-center gap-2 max-w-md shadow-inner">
          <span class="text-base">🛡️</span>
          <span><strong>AI មិនមែនជាអ្នកសម្រេចទណ្ឌកម្ម៖</strong> AI គ្រាន់តែ Alert ទៅ Teacher ដើម្បីជួយ Student ប៉ុណ្ណោះ មិនមានការបិទ Account ឬដក Student ឡើយ។</span>
        </div>
      </div>

      <!-- ── AI RISK ANALYSIS ENGINE FORMULA (Spec 2) ── -->
      <div class="p-4 rounded-xl bg-[#090d16] border border-slate-800 space-y-2">
        <span class="text-[10px] font-mono text-purple-300 font-bold uppercase tracking-wider block">
          ⚡ AI RISK EVALUATION PIPELINE
        </span>
        <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-300 font-medium">
          <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700">Learning Progress</span>
          <span class="text-purple-400 font-bold">+</span>
          <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700">Quiz Performance</span>
          <span class="text-purple-400 font-bold">+</span>
          <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700">Assignment Status</span>
          <span class="text-purple-400 font-bold">+</span>
          <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700">Learning Activity (Days Idle)</span>
          <span class="text-purple-400 font-bold">+</span>
          <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700">Course Completion Pace</span>
          <span class="text-teal-400 font-black">➔</span>
          <span class="px-3 py-1 rounded-lg bg-red-950/60 border border-red-500/40 text-red-300 font-bold">
            AI Risk Analysis & Teacher Alert
          </span>
        </div>
      </div>

      <!-- 3 Summary KPI Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-red-950/20 border border-red-500/40 p-3.5 rounded-xl flex items-center justify-between">
          <div>
            <span class="text-slate-400 text-[10px] block font-bold">🔴 HIGH RISK LEVEL</span>
            <p class="text-xl font-black text-red-400">{{ highRiskCount }} Students</p>
            <span class="text-[10px] text-red-300">Progress &lt; 40% & Idle &gt; 7 days</span>
          </div>
          <span class="text-3xl">⚠️</span>
        </div>

        <div class="bg-amber-950/20 border border-amber-500/40 p-3.5 rounded-xl flex items-center justify-between">
          <div>
            <span class="text-slate-400 text-[10px] block font-bold">🟡 MEDIUM RISK LEVEL</span>
            <p class="text-xl font-black text-amber-300">{{ mediumRiskCount }} Students</p>
            <span class="text-[10px] text-amber-200">Slow pace or 1 overdue assignment</span>
          </div>
          <span class="text-3xl">⏳</span>
        </div>

        <div class="bg-emerald-950/20 border border-emerald-500/40 p-3.5 rounded-xl flex items-center justify-between">
          <div>
            <span class="text-slate-400 text-[10px] block font-bold">🟢 LOW RISK (MONITORED)</span>
            <p class="text-xl font-black text-emerald-400">{{ lowRiskCount }} Students</p>
            <span class="text-[10px] text-emerald-300">Minor drop, recovering pace</span>
          </div>
          <span class="text-3xl">🛡️</span>
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
            placeholder="Search student, ID, or course..."
            class="w-full bg-[#121827] text-white pl-8 pr-3 py-1.5 rounded-xl border border-slate-700/80 text-xs focus:border-purple-500 focus:outline-none placeholder-slate-500"
          />
          <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 1114 0z" />
          </svg>
        </div>

        <!-- Filter Major (5 SPI Majors) -->
        <select v-model="selectedMajor" class="bg-[#121827] text-slate-200 border border-slate-700/80 rounded-xl px-3 py-1.5 text-xs focus:border-purple-500 focus:outline-none">
          <option value="">All 5 Majors</option>
          <option value="Information Technology">Information Technology</option>
          <option value="Agriculture">Agriculture</option>
          <option value="Tourism Management">Tourism Management</option>
          <option value="English Literature">English Literature</option>
          <option value="Social Work">Social Work</option>
        </select>

        <!-- Filter Risk Level -->
        <select v-model="selectedRiskLevel" class="bg-[#121827] text-slate-200 border border-slate-700/80 rounded-xl px-3 py-1.5 text-xs focus:border-purple-500 focus:outline-none">
          <option value="">All Risk Levels</option>
          <option value="High">🔴 High Risk</option>
          <option value="Medium">🟡 Medium Risk</option>
          <option value="Low">🟢 Low Risk</option>
        </select>
      </div>

      <span class="text-slate-400 font-mono text-[11px]">
        Showing <strong>{{ filteredStudents.length }}</strong> flagged students
      </span>
    </div>

    <!-- ── AT-RISK STUDENT LIST TABLE (Spec 1) ── -->
    <div class="bg-[#0d1222]/95 border border-slate-700/60 rounded-2xl shadow-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
          <thead class="bg-slate-900 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-700/80">
            <tr>
              <th class="px-4 py-3">Student</th>
              <th class="px-3 py-3">Major</th>
              <th class="px-3 py-3">Course</th>
              <th class="px-3 py-3">Progress</th>
              <th class="px-3 py-3">Quiz Average</th>
              <th class="px-3 py-3">Last Activity</th>
              <th class="px-3 py-3">Risk Level</th>
              <th class="px-3 py-3">Reason (AI Diagnosis)</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr
              v-for="s in filteredStudents"
              :key="s.id"
              class="hover:bg-slate-800/40 transition-colors"
            >
              <!-- Student -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-md">
                    {{ s.student_name.charAt(0) }}
                  </div>
                  <div>
                    <span class="font-bold text-white block">{{ s.student_name }}</span>
                    <span class="text-[10px] text-purple-300 font-mono">{{ s.student_code }}</span>
                  </div>
                </div>
              </td>

              <!-- Major -->
              <td class="px-3 py-3 font-medium text-slate-300">
                <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700/80 text-[10px] block w-fit">
                  {{ s.major }}
                </span>
              </td>

              <!-- Course -->
              <td class="px-3 py-3 text-white font-medium">
                {{ s.course }}
                <span class="block text-[10px] text-slate-500">Instructor: {{ s.teacher_name }}</span>
              </td>

              <!-- Progress -->
              <td class="px-3 py-3">
                <div class="w-24 space-y-1">
                  <div class="flex justify-between text-[10px] font-bold">
                    <span :class="s.progress < 40 ? 'text-red-400' : 'text-slate-300'">{{ s.progress }}%</span>
                  </div>
                  <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-500"
                      :class="s.progress < 40 ? 'bg-red-500' : (s.progress < 60 ? 'bg-amber-500' : 'bg-emerald-500')"
                      :style="{ width: s.progress + '%' }"
                    ></div>
                  </div>
                </div>
              </td>

              <!-- Quiz Average -->
              <td class="px-3 py-3 font-bold">
                <span
                  class="px-2 py-0.5 rounded text-[11px]"
                  :class="s.quiz_average < 50 ? 'bg-red-500/20 text-red-300 border border-red-500/40' : (s.quiz_average < 70 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-emerald-500/20 text-emerald-300')"
                >
                  {{ s.quiz_average }}%
                </span>
              </td>

              <!-- Last Activity -->
              <td class="px-3 py-3 text-[11px]">
                <span :class="s.inactive_days >= 7 ? 'text-red-400 font-bold' : 'text-slate-300'">
                  {{ s.last_activity }}
                </span>
                <span v-if="s.overdue_assignments > 0" class="block text-[10px] text-amber-400 font-bold">
                  ⚠️ {{ s.overdue_assignments }} overdue
                </span>
              </td>

              <!-- Risk Level -->
              <td class="px-3 py-3">
                <span
                  class="px-2.5 py-1 rounded-full text-[10px] font-black inline-flex items-center gap-1 shadow-sm"
                  :class="
                    s.risk_level === 'High' ? 'bg-red-500/20 text-red-300 border border-red-500/40' :
                    (s.risk_level === 'Medium' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40')
                  "
                >
                  <span>{{ s.risk_level === 'High' ? '🔴' : (s.risk_level === 'Medium' ? '🟡' : '🟢') }}</span>
                  <span>{{ s.risk_level }} Risk</span>
                </span>
              </td>

              <!-- Reason -->
              <td class="px-3 py-3 max-w-xs">
                <p class="text-[11px] text-slate-200 line-clamp-1 font-medium" :title="s.reason">
                  {{ s.reason }}
                </p>
                <p class="text-[10px] text-slate-400 italic line-clamp-1">
                  {{ s.reason_kh }}
                </p>
              </td>

              <!-- Actions: View / Contact -->
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openDetail(s)"
                    class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white rounded-lg text-xs font-semibold border border-slate-700 transition-all flex items-center gap-1 active:scale-95"
                    title="View Comprehensive Risk Diagnostic"
                  >
                    <span>👁️</span>
                    <span>View</span>
                  </button>

                  <button
                    @click="openContact(s)"
                    class="px-2.5 py-1 bg-gradient-to-r from-purple-600 to-indigo-600 hover:brightness-110 text-white rounded-lg text-xs font-bold shadow-md shadow-purple-600/30 transition-all flex items-center gap-1 active:scale-95"
                    title="Teacher Proactive Support & Contact"
                  >
                    <span>💬</span>
                    <span>Contact</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredStudents.length === 0">
              <td colspan="9" class="px-4 py-10 text-center text-slate-400">
                <span>No at-risk students match the current filters.</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── MODAL 1: VIEW COMPREHENSIVE RISK DIAGNOSTIC ── -->
    <div
      v-if="showDetailModal && selectedStudent"
      class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 animate-fadeIn"
    >
      <div class="bg-[#0f172a] border border-slate-700/80 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl space-y-4 p-6">
        <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-red-500/20 border border-red-500/40 flex items-center justify-center text-red-400 font-bold text-sm">
              ⚠️
            </div>
            <div>
              <h4 class="font-black text-base text-white">AI Risk Diagnostic — {{ selectedStudent.student_name }}</h4>
              <span class="text-xs text-purple-300 font-mono">{{ selectedStudent.student_code }} · {{ selectedStudent.major }}</span>
            </div>
          </div>
          <button @click="showDetailModal = false" class="text-slate-400 hover:text-white font-black text-base">✕</button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
          <div class="bg-slate-900/80 border border-slate-800 p-2.5 rounded-xl">
            <span class="text-slate-400 text-[10px] block">Course Progress</span>
            <span class="text-base font-black" :class="selectedStudent.progress < 40 ? 'text-red-400' : 'text-amber-300'">{{ selectedStudent.progress }}%</span>
          </div>
          <div class="bg-slate-900/80 border border-slate-800 p-2.5 rounded-xl">
            <span class="text-slate-400 text-[10px] block">Quiz Average</span>
            <span class="text-base font-black text-red-400">{{ selectedStudent.quiz_average }}%</span>
          </div>
          <div class="bg-slate-900/80 border border-slate-800 p-2.5 rounded-xl">
            <span class="text-slate-400 text-[10px] block">Inactive Period</span>
            <span class="text-base font-black text-amber-300">{{ selectedStudent.inactive_days }} Days</span>
          </div>
          <div class="bg-slate-900/80 border border-slate-800 p-2.5 rounded-xl">
            <span class="text-slate-400 text-[10px] block">Overdue Work</span>
            <span class="text-base font-black text-red-400">{{ selectedStudent.overdue_assignments }} Items</span>
          </div>
        </div>

        <div class="space-y-2 p-3.5 rounded-xl bg-slate-900/80 border border-slate-800">
          <span class="text-slate-300 font-bold text-xs flex items-center gap-1.5">
            <span>🧠</span>
            <span>AI Diagnostic Rationale (មូលហេតុនៃការវាយតម្លៃ)៖</span>
          </span>
          <p class="text-slate-200 leading-relaxed font-medium">
            {{ selectedStudent.reason }}
          </p>
          <p class="text-purple-300 text-[11px] italic bg-purple-950/40 p-2 rounded-lg border border-purple-500/30">
            «{{ selectedStudent.reason_kh }}»
          </p>
        </div>

        <div class="p-3.5 rounded-xl bg-blue-950/30 border border-blue-500/30 text-xs text-blue-200 space-y-1">
          <span class="font-bold flex items-center gap-1.5">
            <span>👨‍🏫</span>
            <span>Course Instructor Assigned: {{ selectedStudent.teacher_name }}</span>
          </span>
          <p class="text-[11px] text-slate-300">
            Teacher has been alerted through the Teacher AI Assistant interface to provide remedial guidance.
          </p>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
          <button @click="showDetailModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
            Close
          </button>
          <button
            @click="() => { showDetailModal = false; openContact(selectedStudent); }"
            class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold shadow-md shadow-purple-600/30 flex items-center gap-1.5"
          >
            <span>💬</span>
            <span>Initiate Teacher Support</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ── MODAL 2: TEACHER CONTACT / SUPPORT INTERVENTION ── -->
    <div
      v-if="showContactModal && selectedStudent"
      class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 animate-fadeIn"
    >
      <div class="bg-[#0f172a] border border-purple-500/60 rounded-2xl w-full max-w-xl overflow-hidden shadow-2xl space-y-4 p-6">
        <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
          <div>
            <h4 class="font-black text-base text-white flex items-center gap-2">
              <span>💬</span>
              <span>Send Supportive Intervention</span>
            </h4>
            <span class="text-xs text-slate-400">To: {{ selectedStudent.student_name }} ({{ selectedStudent.student_code }})</span>
          </div>
          <button @click="showContactModal = false" class="text-slate-400 hover:text-white font-black text-base">✕</button>
        </div>

        <!-- Intervention Type Selection -->
        <div class="space-y-1.5">
          <label class="text-[11px] text-slate-300 font-bold block">Select Support Action (ប្រភេទនៃការជួយគាំទ្រ)៖</label>
          <div class="grid grid-cols-3 gap-2">
            <button
              type="button"
              @click="supportAction = 'tutoring'"
              class="p-2 rounded-xl text-left border text-xs font-semibold transition-all"
              :class="supportAction === 'tutoring' ? 'bg-purple-600/30 border-purple-400 text-white' : 'bg-slate-900 border-slate-800 text-slate-400'"
            >
              <span class="block text-sm mb-0.5">🧑‍🏫</span>
              <span>1-on-1 Tutoring</span>
            </button>

            <button
              type="button"
              @click="supportAction = 'material'"
              class="p-2 rounded-xl text-left border text-xs font-semibold transition-all"
              :class="supportAction === 'material' ? 'bg-purple-600/30 border-purple-400 text-white' : 'bg-slate-900 border-slate-800 text-slate-400'"
            >
              <span class="block text-sm mb-0.5">📚</span>
              <span>Remedial Notes</span>
            </button>

            <button
              type="button"
              @click="supportAction = 'extension'"
              class="p-2 rounded-xl text-left border text-xs font-semibold transition-all"
              :class="supportAction === 'extension' ? 'bg-purple-600/30 border-purple-400 text-white' : 'bg-slate-900 border-slate-800 text-slate-400'"
            >
              <span class="block text-sm mb-0.5">⏱️</span>
              <span>Extend Deadline</span>
            </button>
          </div>
        </div>

        <div class="space-y-1.5">
          <label class="text-[11px] text-slate-300 font-bold block">Subject:</label>
          <input
            v-model="contactSubject"
            type="text"
            class="w-full bg-[#121827] text-white px-3 py-2 rounded-xl border border-slate-700/80 text-xs focus:border-purple-500 focus:outline-none"
          />
        </div>

        <div class="space-y-1.5">
          <label class="text-[11px] text-slate-300 font-bold block">Encouragement & Guidance Message (សារលើកទឹកចិត្ត និងការណែនាំ)៖</label>
          <textarea
            v-model="contactMessage"
            rows="4"
            class="w-full bg-[#121827] text-white p-3 rounded-xl border border-slate-700/80 text-xs focus:border-purple-500 focus:outline-none"
          ></textarea>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2">
          <button @click="showContactModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
            Cancel
          </button>
          <button
            @click="sendSupportIntervention"
            class="px-5 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-purple-600/30 flex items-center gap-1.5 active:scale-95"
          >
            <span>🚀</span>
            <span>Send Support Message</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
