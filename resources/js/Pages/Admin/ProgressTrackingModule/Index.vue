<script setup lang="ts">
import { ref, computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import type { 
  StudentProgressProfile, 
  CourseCompletionItem, 
  ModuleAnalyticsItem, 
  LearningTimeData, 
  AtRiskStudentItem, 
  WeeklyProgressMetrics,
  MajorProgressItem 
} from './types'

// Sub-Components
import StudentProgress from './StudentProgress.vue'
import CourseCompletion from './CourseCompletion.vue'
import ModuleCompletion from './ModuleCompletion.vue'
import LearningTime from './LearningTime.vue'
import AtRiskStudents from './AtRiskStudents.vue'
import WeeklyProgress from './WeeklyProgress.vue'

// Modals
import InterventionModal from './Modals/InterventionModal.vue'

const props = defineProps<{
  studentProfile?: StudentProgressProfile
  coursesCompletion?: CourseCompletionItem[]
  modulesAnalytics?: ModuleAnalyticsItem[]
  learningTimeData?: LearningTimeData
  atRiskStudentsList?: AtRiskStudentItem[]
  weeklyMetrics?: WeeklyProgressMetrics
  majorsData?: MajorProgressItem[]
  activeTab?: string
}>()

// Initial State with Safe Fallbacks
const currentTab = ref<string>(props.activeTab || 'student')
const showMessageModal = ref(false)
const showFeedbackModal = ref(false)
const showResetModal = ref(false)
const selectedStudentId = ref<string | null>(null)

// Toast Notification
const toastMessage = ref('')
const toastType = ref<'success' | 'info' | 'warning'>('success')

function showNotification(msg: string, type: 'success' | 'info' | 'warning' = 'success') {
  toastMessage.value = msg
  toastType.value = type
  setTimeout(() => {
    toastMessage.value = ''
  }, 3500)
}

// Fallback Mock Data if Props Undefined
const defaultProfile: StudentProgressProfile = props.studentProfile || {
  id: 'STU24001',
  name: 'Chan Dara',
  major: 'IT & Networking',
  course: 'C Programming Basics',
  overall_progress: 72,
  learning_time: '28h 30m',
  quiz_avg: 78,
  assignments_submitted: 3,
  assignments_total: 4,
  cert_status: 'Pending',
  modules: [
    {
      id: 1,
      title: 'Module 1: Introduction to C',
      progress: 100,
      status: 'Completed',
      chapters: [
        { id: 101, title: 'Ch 1.1 History of C', status: 'completed', video_watched_percent: 100, pdf_opened: true, slide_read: true, quiz_score: 90 },
        { id: 102, title: 'Ch 1.2 Setup Environment', status: 'completed', video_watched_percent: 90, pdf_opened: true, slide_read: true, quiz_score: 85 },
      ]
    },
    {
      id: 2,
      title: 'Module 2: Variables & Data Types',
      progress: 65,
      status: 'In Progress',
      chapters: [
        { id: 201, title: 'Ch 2.1 Variables', status: 'completed', video_watched_percent: 100, pdf_opened: true, slide_read: true, quiz_score: 80 },
        { id: 202, title: 'Ch 2.2 Data Types', status: 'in_progress', video_watched_percent: 45, pdf_opened: false, slide_read: false },
      ]
    },
    {
      id: 3,
      title: 'Module 3: Control Flow',
      progress: 0,
      status: 'Locked',
      chapters: [
        { id: 301, title: 'Ch 3.1 If-Statements & Loops', status: 'locked', video_watched_percent: 0, pdf_opened: false, slide_read: false },
      ]
    }
  ]
}

const defaultCourses: CourseCompletionItem[] = props.coursesCompletion || [
  { id: 1, title: 'C Programming Basics', teacher: 'Mr. Sophea', enrolled: 520, completed: 120, completed_percent: 23, in_progress: 380, in_progress_percent: 73, avg_score: 72, major: 'IT & Networking', semester: 'Current' },
  { id: 2, title: 'Web Development', teacher: 'Ms. Dara', enrolled: 410, completed: 95, completed_percent: 23, in_progress: 300, in_progress_percent: 73, avg_score: 76, major: 'IT & Networking', semester: 'Current' },
  { id: 3, title: 'Database Systems', teacher: 'Mr. Sophea', enrolled: 380, completed: 80, completed_percent: 21, in_progress: 280, in_progress_percent: 73, avg_score: 70, major: 'IT & Networking', semester: 'Current' },
  { id: 4, title: 'English Grammar', teacher: 'Ms. Srey', enrolled: 600, completed: 150, completed_percent: 25, in_progress: 420, in_progress_percent: 70, avg_score: 82, major: 'English Literature', semester: 'Current' },
  { id: 5, title: 'Plant Science', teacher: 'Mr. Vuthy', enrolled: 548, completed: 45, completed_percent: 8, in_progress: 480, in_progress_percent: 87, avg_score: 65, major: 'Agronomy', semester: 'Current' },
]

const defaultModules: ModuleAnalyticsItem[] = props.modulesAnalytics || [
  { id: 1, title: 'Module 1: Introduction to C', course: 'C Programming Basics', completed_percent: 92, status_color: 'green', avg_days_to_complete: 2.5, expected_days: 3, dropout_rate: 1, difficulty_flag: false },
  { id: 2, title: 'Module 2: Variables & Data Types', course: 'C Programming Basics', completed_percent: 85, status_color: 'green', avg_days_to_complete: 4.0, expected_days: 4, dropout_rate: 3, difficulty_flag: false },
  { id: 3, title: 'Module 3: Control Flow (Loops & If-Else)', course: 'C Programming Basics', completed_percent: 45, status_color: 'red', avg_days_to_complete: 8.5, expected_days: 5, dropout_rate: 12, difficulty_flag: true },
  { id: 4, title: 'Module 4: Functions & Pointers', course: 'C Programming Basics', completed_percent: 15, status_color: 'red', avg_days_to_complete: 10.0, expected_days: 7, dropout_rate: 15, difficulty_flag: true },
]

const defaultLearningTime: LearningTimeData = props.learningTimeData || {
  daily_avg_mins: 45,
  weekly_avg_hours: '5h 15m',
  video_split_percent: 60,
  document_split_percent: 40,
  daily_hours: [
    { day: 'Mon', hours: 40 },
    { day: 'Tue', hours: 85 },
    { day: 'Wed', hours: 110 },
    { day: 'Thu', hours: 95 },
    { day: 'Fri', hours: 130 },
    { day: 'Sat', hours: 65 },
    { day: 'Sun', hours: 50 },
  ],
  peak_hours: '7:00 PM – 10:00 PM',
  top_active_students: [
    { name: 'Chan Dara', major: 'IT & Networking', time_spent: '12h 45m', completed_items: 18, xp_points: 150 },
    { name: 'Bun Rithy', major: 'IT & Networking', time_spent: '11h 20m', completed_items: 15, xp_points: 120 },
    { name: 'Pov Sreynich', major: 'Agronomy', time_spent: '10h 15m', completed_items: 14, xp_points: 110 },
  ]
}

const defaultAtRiskList: AtRiskStudentItem[] = props.atRiskStudentsList || [
  { id: 'STU24001', name: 'Chan Dara', course: 'C Programming Basics', major: 'IT & Networking', risk_level: 'high', risk_factors: ['Low Quiz Score', 'Idle > 3 days'], idle_days: 4, quiz_avg: 45, last_active: '2025-06-20' },
  { id: 'STU24002', name: 'Sok Chanra', course: 'Tourism Fundamentals', major: 'Tourism Management', risk_level: 'high', risk_factors: ['Overdue Assignment', 'No Video Progress'], idle_days: 5, quiz_avg: 50, last_active: '2025-06-19' },
  { id: 'STU24005', name: 'Unknown Student X', course: 'Social Work 101', major: 'Social Work', risk_level: 'high', risk_factors: ['No Activity Since Pay'], idle_days: 12, quiz_avg: 0, last_active: '2025-06-12' },
  { id: 'STU24003', name: 'Long Vichida', course: 'English Grammar', major: 'English Literature', risk_level: 'medium', risk_factors: ['Low Quiz Score'], idle_days: 1, quiz_avg: 35, last_active: '2025-06-23' },
]

const defaultWeeklyMetrics: WeeklyProgressMetrics = props.weeklyMetrics || {
  wau: { current: 1890, growth: 12 },
  study_hours: { current: '5h 15m', growth: 8 },
  videos_watched: { current: 4250, growth: 15 },
  quizzes_taken: { current: 1200, growth: -3 },
  heatmap: [
    { hour: '08:00', days: [{ day: 'Mon', level: 'low' }, { day: 'Tue', level: 'low' }, { day: 'Wed', level: 'low' }, { day: 'Thu', level: 'low' }, { day: 'Fri', level: 'low' }, { day: 'Sat', level: 'low' }, { day: 'Sun', level: 'low' }] },
    { hour: '12:00', days: [{ day: 'Mon', level: 'med' }, { day: 'Tue', level: 'med' }, { day: 'Wed', level: 'med' }, { day: 'Thu', level: 'med' }, { day: 'Fri', level: 'med' }, { day: 'Sat', level: 'low' }, { day: 'Sun', level: 'low' }] },
    { hour: '14:00', days: [{ day: 'Mon', level: 'med' }, { day: 'Tue', level: 'med' }, { day: 'Wed', level: 'med' }, { day: 'Thu', level: 'med' }, { day: 'Fri', level: 'med' }, { day: 'Sat', level: 'med' }, { day: 'Sun', level: 'med' }] },
    { hour: '19:00', days: [{ day: 'Mon', level: 'high' }, { day: 'Tue', level: 'high' }, { day: 'Wed', level: 'high' }, { day: 'Thu', level: 'high' }, { day: 'Fri', level: 'high' }, { day: 'Sat', level: 'med' }, { day: 'Sun', level: 'med' }] },
    { hour: '22:00', days: [{ day: 'Mon', level: 'med' }, { day: 'Tue', level: 'med' }, { day: 'Wed', level: 'med' }, { day: 'Thu', level: 'med' }, { day: 'Fri', level: 'med' }, { day: 'Sat', level: 'low' }, { day: 'Sun', level: 'low' }] },
  ],
  ai_insight: 'Students are most active during weekdays between 7:00 PM and 10:00 PM.'
}

const defaultMajorsData: MajorProgressItem[] = props.majorsData || [
  { id: 1, major: 'IT & Networking', faculty: 'Computing', enrolled: 520, active_this_week: 480, active_percent: 92, at_risk_count: 12, at_risk_percent: 2.0, avg_progress: 72 },
  { id: 2, major: 'Tourism Management', faculty: 'Tourism', enrolled: 410, active_this_week: 310, active_percent: 75, at_risk_count: 25, at_risk_percent: 6.0, avg_progress: 65 },
  { id: 3, major: 'English Literature', faculty: 'Education', enrolled: 380, active_this_week: 320, active_percent: 84, at_risk_count: 15, at_risk_percent: 3.0, avg_progress: 78 },
  { id: 4, major: 'Agronomy', faculty: 'Agriculture', enrolled: 600, active_this_week: 540, active_percent: 90, at_risk_count: 10, at_risk_percent: 1.6, avg_progress: 68 },
  { id: 5, major: 'Social Work', faculty: 'Social Science', enrolled: 548, active_this_week: 410, active_percent: 74, at_risk_count: 35, at_risk_percent: 6.3, avg_progress: 60 },
]

// Modal Trigger Functions
function handleOpenMessage(sId: string) {
  selectedStudentId.value = sId
  showMessageModal.value = true
}

function handleOpenFeedback(sId: string) {
  selectedStudentId.value = sId
  showFeedbackModal.value = true
}

function handleOpenReset(sId: string) {
  selectedStudentId.value = sId
  showResetModal.value = true
}

function handleSubmitMessage(sId: string, msg: string) {
  showMessageModal.value = false
  showNotification(`Message sent to student ${sId}!`)
}

function handleSubmitFeedback(sId: string, fb: string) {
  showFeedbackModal.value = false
  showNotification(`Feedback recorded for student ${sId}!`)
}

function handleConfirmReset(sId: string) {
  showResetModal.value = false
  showNotification(`Progress reset for student ${sId}.`, 'warning')
}

function handleSaveRules(rules: any) {
  showNotification('Auto-intervention rules saved successfully!')
}
</script>

<template>
  <AdminLayout>
    <!-- Floating Notification Toast -->
    <div
      v-if="toastMessage"
      class="fixed top-5 right-5 z-[999] flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-xs font-bold transition-all border animate-bounce"
      :class="
        toastType === 'success' ? 'bg-emerald-950 border-emerald-500 text-emerald-300' : 
        (toastType === 'warning' ? 'bg-amber-950 border-amber-500 text-amber-300' : 'bg-purple-950 border-purple-500 text-purple-300')
      "
    >
      <span>{{ toastType === 'success' ? '✅' : (toastType === 'warning' ? '⚠️' : 'ℹ️') }}</span>
      <span>{{ toastMessage }}</span>
    </div>

    <div class="space-y-6 text-slate-100 font-sans pb-12">
      <!-- ── MODULE HEADER CARD (Clean, Standard, Vibrant Design) ── -->
      <div class="relative overflow-hidden bg-slate-800/90 border border-slate-700/70 rounded-2xl p-6 shadow-xl backdrop-blur-xl">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-700/50 pb-5">
          <div>
            <div class="flex items-center gap-3">
              <!-- Flaticon Style 3D Analytics Trend Chart Icon -->
              <div class="p-2 rounded-2xl bg-gradient-to-br from-purple-500/20 to-teal-500/20 border border-purple-500/40 shadow-lg shadow-purple-500/10 flex items-center justify-center">
                <svg class="w-7 h-7 shrink-0" viewBox="0 0 32 32" fill="none">
                  <rect x="4" y="18" width="5" height="10" rx="1.5" fill="url(#mainHeaderBarGrad1)" />
                  <rect x="11" y="12" width="5" height="16" rx="1.5" fill="url(#mainHeaderBarGrad2)" />
                  <rect x="18" y="15" width="5" height="13" rx="1.5" fill="url(#mainHeaderBarGrad3)" />
                  <rect x="25" y="6" width="5" height="22" rx="1.5" fill="url(#mainHeaderBarGrad4)" />
                  <path d="M4 15L11 9L18 12L27 3" stroke="#2DD4BF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                  <circle cx="27" cy="3" r="2.5" fill="#5EEAD4" />
                  <defs>
                    <linearGradient id="mainHeaderBarGrad1" x1="4" y1="18" x2="9" y2="28" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#A855F7" />
                      <stop offset="1" stop-color="#7E22CE" />
                    </linearGradient>
                    <linearGradient id="mainHeaderBarGrad2" x1="11" y1="12" x2="16" y2="28" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#C084FC" />
                      <stop offset="1" stop-color="#9333EA" />
                    </linearGradient>
                    <linearGradient id="mainHeaderBarGrad3" x1="18" y1="15" x2="23" y2="28" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#38BDF8" />
                      <stop offset="1" stop-color="#0284C7" />
                    </linearGradient>
                    <linearGradient id="mainHeaderBarGrad4" x1="25" y1="6" x2="30" y2="28" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#34D399" />
                      <stop offset="1" stop-color="#059669" />
                    </linearGradient>
                  </defs>
                </svg>
              </div>
              <h2 class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-teal-300 to-emerald-400 tracking-tight">
                Learning & Progress Module
              </h2>
            </div>
            <p class="text-xs text-slate-300 mt-1.5 font-medium">
              Student progress monitoring and cohort course completion analytics
            </p>
          </div>

          <!-- Quick Action Buttons Header Right -->
          <div class="flex flex-wrap items-center gap-2.5">
            <button
              @click="currentTab = 'student'"
              class="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-xl text-xs font-bold flex items-center gap-2 transition-all shadow-md active:scale-95"
            >
              <span>👤 2,458 Students</span>
            </button>

            <button
              @click="showNotification('Generated real-time progress report!')"
              class="px-5 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold flex items-center gap-2 shadow-lg shadow-purple-600/30 transition-all active:scale-95 ring-1 ring-purple-400/50"
            >
              <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none">
                <path d="M4 4C4 2.89543 4.89543 2 6 2H14L20 8V20C20 21.1046 19.1046 22 18 22H6C4.89543 22 4 21.1046 4 20V4Z" fill="url(#exportDocGrad)" />
                <path d="M14 2V8H20L14 2Z" fill="#E9D5FF" />
                <path d="M12 11V17M12 17L9 14M12 17L15 14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <defs>
                  <linearGradient id="exportDocGrad" x1="4" y1="2" x2="20" y2="22" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#A855F7" />
                    <stop offset="1" stop-color="#6B21A8" />
                  </linearGradient>
                </defs>
              </svg>
              <span>Export Report</span>
            </button>
          </div>
        </div>

        <!-- ── SUB-NAVIGATION TABS (Student Progress & Course Completion) ── -->
        <!-- Mobile Dropdown Selector -->
        <div class="md:hidden pt-4">
          <select
            v-model="currentTab"
            class="w-full bg-slate-900 text-slate-100 border border-slate-700 rounded-xl px-3 py-2.5 text-xs font-bold focus:outline-none focus:border-purple-500"
          >
            <option value="student">👤 Student Progress</option>
            <option value="course">🎓 Course Completion</option>
          </select>
        </div>

        <!-- Desktop Segmented Navigation Pills -->
        <div class="hidden md:flex items-center gap-2 pt-4 text-xs overflow-x-auto pb-1 max-w-full">
          <button
            v-for="t in [
              { id: 'student', label: 'Student Progress', iconPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
              { id: 'course', label: 'Course Completion', iconPath: 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z' },
            ]"
            :key="t.id"
            @click="currentTab = t.id"
            :class="[
              currentTab === t.id 
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30 ring-1 ring-purple-400/60' 
                : 'bg-slate-900/80 text-slate-300 hover:text-white hover:bg-slate-800/80 border border-slate-700/50',
              'px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap text-xs'
            ]"
          >
            <svg class="w-4 h-4 shrink-0" :class="currentTab === t.id ? 'text-white' : 'text-purple-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="t.iconPath" />
            </svg>
            <span>{{ t.label }}</span>
          </button>
        </div>
      </div>

      <!-- ── TAB CONTENT RENDERING ── -->
      <StudentProgress
        v-if="currentTab === 'student'"
        :profile="defaultProfile"
        @sendMessage="handleOpenMessage"
        @addFeedback="handleOpenFeedback"
        @resetProgress="handleOpenReset"
      />

      <CourseCompletion
        v-else-if="currentTab === 'course'"
        :courses="defaultCourses"
        @downloadReport="() => showNotification('Course completion report downloaded!')"
        @notifyIncomplete="() => showNotification('Reminder notification sent to incomplete students!')"
      />

      <ModuleCompletion
        v-else-if="currentTab === 'module'"
        :modules="defaultModules"
        @addPracticeQuiz="() => showNotification('Added practice quiz to Module 3!')"
        @sendStudyTip="() => showNotification('Sent study tips to Module 3 students!')"
      />

      <LearningTime
        v-else-if="currentTab === 'time'"
        :data="defaultLearningTime"
        @viewAllLogs="() => showNotification('Opening full active learning logs...')"
      />

      <AtRiskStudents
        v-else-if="currentTab === 'at_risk'"
        :students="defaultAtRiskList"
        @sendEmailAlert="(s) => showNotification(`Email risk alert sent to ${s.name}!`)"
        @openLiveChat="(s) => showNotification(`Opened chat session with ${s.name}...`)"
        @toggleLockAccess="(s) => showNotification(`Toggled module lock for ${s.name}.`, 'warning')"
        @saveRules="handleSaveRules"
        @exportList="() => showNotification('At-Risk list exported to CSV!')"
      />

      <WeeklyProgress
        v-else-if="currentTab === 'weekly'"
        :metrics="defaultWeeklyMetrics"
        @downloadPdf="() => showNotification('Weekly progress PDF downloaded!')"
        @emailStakeholders="() => showNotification('Weekly report sent to teachers & admins!')"
      />

      <!-- ── PROGRESS TRACKING FOR 5 MAJORS SUMMARY BANNER ── -->
      <div class="bg-[#0d1222]/95 border border-slate-700/60 rounded-2xl p-5 shadow-2xl space-y-4">
        <h4 class="font-black text-sm text-white uppercase tracking-wide border-b border-slate-700/60 pb-2.5 flex items-center gap-2.5">
          <!-- Flaticon Style 3D Graduation Cap & University Icon Badge -->
          <div class="p-1.5 rounded-lg bg-purple-500/20 border border-purple-500/40 flex items-center justify-center shrink-0 shadow-inner">
            <svg class="w-5 h-5" viewBox="0 0 32 32" fill="none">
              <!-- Top Cap Diamond -->
              <path d="M16 4L2 11L16 18L30 11L16 4Z" fill="url(#majorsCapTop3D)" />
              <!-- Cap Base Skullcap -->
              <path d="M7 14.5V21.5C7 24.5 11 27 16 27C21 27 25 24.5 25 21.5V14.5" fill="url(#majorsCapBase3D)" />
              <!-- Yellow Tassel & Button -->
              <path d="M28 11.5V20.5" stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round" />
              <circle cx="28" cy="22" r="2.5" fill="#FBBF24" />
              <circle cx="16" cy="11" r="2" fill="#E9D5FF" />
              <defs>
                <linearGradient id="majorsCapTop3D" x1="2" y1="4" x2="30" y2="18" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#C084FC" />
                  <stop offset="1" stop-color="#7E22CE" />
                </linearGradient>
                <linearGradient id="majorsCapBase3D" x1="7" y1="14.5" x2="25" y2="27" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#A855F7" />
                  <stop offset="1" stop-color="#5B21B6" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <span>PROGRESS TRACKING ACROSS 5 ACADEMIC MAJORS</span>
        </h4>

        <div class="overflow-x-auto rounded-xl border border-slate-700/80 bg-[#121827]">
          <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900 text-slate-400 uppercase font-bold border-b border-slate-700/80">
              <tr>
                <th class="p-3">#</th>
                <th class="p-3">Academic Major</th>
                <th class="p-3">Faculty</th>
                <th class="p-3">Enrolled</th>
                <th class="p-3">Active This Week</th>
                <th class="p-3">At-Risk</th>
                <th class="p-3 text-right">Avg. Progress</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80 font-medium">
              <tr v-for="m in defaultMajorsData" :key="m.id" class="hover:bg-slate-800/40 transition-colors">
                <td class="p-3 font-bold text-slate-400">{{ m.id }}</td>
                <td class="p-3 font-bold text-white">{{ m.major }}</td>
                <td class="p-3 text-slate-400">{{ m.faculty }}</td>
                <td class="p-3 font-semibold text-slate-200">{{ m.enrolled }} Students</td>
                <td class="p-3 text-emerald-400 font-bold">
                  <div class="flex items-center gap-2.5">
                    <span class="min-w-[70px]">{{ m.active_this_week }} ({{ m.active_percent }}%)</span>
                    <div class="w-20 h-2 bg-slate-800 rounded-full overflow-hidden border border-slate-700/80 hidden sm:block">
                      <div class="h-full bg-emerald-400 rounded-full transition-all duration-500 shadow-sm" :style="{ width: m.active_percent + '%' }"></div>
                    </div>
                  </div>
                </td>
                <td class="p-3 font-bold" :class="m.at_risk_percent > 4 ? 'text-red-400' : 'text-amber-300'">
                  {{ m.at_risk_count }} ({{ m.at_risk_percent }}%)
                </td>
                <td class="p-3 text-right font-black text-purple-300">{{ m.avg_progress }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── INTERVENTION MODALS ── -->
    <InterventionModal
      :showMessageModal="showMessageModal"
      :showFeedbackModal="showFeedbackModal"
      :showResetModal="showResetModal"
      :studentId="selectedStudentId"
      @close="showMessageModal = false; showFeedbackModal = false; showResetModal = false;"
      @submitMessage="handleSubmitMessage"
      @submitFeedback="handleSubmitFeedback"
      @confirmReset="handleConfirmReset"
    />
  </AdminLayout>
</template>
