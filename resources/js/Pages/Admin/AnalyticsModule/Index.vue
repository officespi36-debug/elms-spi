<script setup lang="ts">
import { ref, computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import type { 
  OverviewMetrics, 
  StudentAnalyticsData, 
  TeacherAnalyticsData, 
  CourseAnalyticsData, 
  QuizAnalyticsData, 
} from './types'

// Sub-Components
import Overview from './Overview.vue'
import StudentAnalytics from './StudentAnalytics.vue'
import TeacherAnalytics from './TeacherAnalytics.vue'
import CourseAnalytics from './CourseAnalytics.vue'
import QuizAnalytics from './QuizAnalytics.vue'
import SystemReports from './SystemReports.vue'
import ExportReports from './ExportReports.vue'

const props = defineProps<{
  activeTab?: string
  coursesAnalytics?: {
    summary?: any
    list?: any[]
  }
  teachersAnalytics?: {
    summary?: any
    list?: any[]
  }
  studentAnalytics?: {
    summary?: any
    list?: any[]
  }
  systemReports?: {
    summary?: any
  }
  majors?: any[]
  subjects?: any[]
  teachers?: any[]
  academicYears?: any[]
}>()

// Default to user's requested tab or 'courses' or 'students'
const currentTab = ref<string>(
  props.activeTab === 'overview' ? 'system_reports' : (props.activeTab || 'courses')
)

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

function handleExportCsv(type: string = 'course') {
  showNotification(`Exporting ${type.toUpperCase()} Analytics (CSV)...`, 'info')
  window.open(`/admin/reports/export-csv?type=${type}`, '_blank')
}

function handleExportPdf(type: string = 'course') {
  showNotification(`Exporting ${type.toUpperCase()} Report (PDF)...`, 'info')
  window.open(`/admin/reports/export-pdf?type=${type}`, '_blank')
}

// Fallback student mock data for deep dive charts
const defaultStudentData: StudentAnalyticsData = {
  kpis: {
    total: props.studentAnalytics?.summary?.total_students || 2458,
    active: props.studentAnalytics?.summary?.active_learners || 2390,
    active_percent: 97,
    retention: 94.5,
    at_risk: props.studentAnalytics?.summary?.at_risk_students || 42,
    at_risk_percent: 8.4,
  },
  enrollment_trend: [
    { month: 'Jan', count: 500 },
    { month: 'Feb', count: 800 },
    { month: 'Mar', count: 1200 },
    { month: 'Apr', count: 1600 },
    { month: 'May', count: 2000 },
    { month: 'Jun', count: 2458 },
  ],
  by_major: [
    { major: 'Information Technology', count: 520, percent: 21 },
    { major: 'Tourism Management', count: 410, percent: 17 },
    { major: 'English Literature', count: 380, percent: 15 },
    { major: 'Agronomy', count: 600, percent: 24 },
    { major: 'Social Work', count: 548, percent: 22 },
  ],
  by_gender: { male_percent: 54, female_percent: 46 },
  engagement_distribution: [
    { label: 'Highly Active', range: '>10h/week', count: 850, percent: 35, color: 'bg-emerald-500' },
    { label: 'Moderate Active', range: '5-10h/week', count: 1120, percent: 46, color: 'bg-purple-500' },
    { label: 'Low Active', range: '2-5h/week', count: 275, percent: 11, color: 'bg-amber-500' },
    { label: 'Inactive', range: '<2h/week', count: 213, percent: 8, color: 'bg-red-500' },
  ],
  top_students: [
    { rank: 1, name: 'Chan Dara', major: 'Information Technology', progress: 95, avg_score: 92, hours: '52h 30m' },
    { rank: 2, name: 'Bun Rithy', major: 'Information Technology', progress: 92, avg_score: 90, hours: '48h 15m' },
    { rank: 3, name: 'Pov Sreynich', major: 'Agronomy', progress: 90, avg_score: 88, hours: '45h 50m' },
    { rank: 4, name: 'Long Vichida', major: 'English Literature', progress: 88, avg_score: 87, hours: '42h 10m' },
  ],
  retention_funnel: [
    { stage: 'Enrolled Cohort', percent: 100, count: 2458 },
    { stage: 'Started Learning', percent: 95, count: 2335 },
    { stage: 'Completed Module 1', percent: 85, count: 2089 },
    { stage: 'Completed Module 2', percent: 72, count: 1770 },
    { stage: 'Completed Full Course', percent: 58, count: 1426 },
    { stage: 'Earned Certificate', percent: 55, count: 1352 },
  ]
}

const defaultQuizData: QuizAnalyticsData = {
  kpis: {
    total_quizzes: 560,
    pass_rate: 78,
    avg_score: 72.5,
    avg_time: '18m 45s',
  },
  score_distribution: [
    { range: '90 - 100%', count: 620, percent: 25 },
    { range: '70 - 89%', count: 1230, percent: 50 },
    { range: '50 - 69%', count: 450, percent: 18 },
    { range: '0 - 49%', count: 158, percent: 7, flag: true },
  ],
  pass_by_type: [
    { type: '🚀 Pre-Tests', pass_rate: 68, note: 'Expected lower baseline' },
    { type: '✍️ Practice Quizzes', pass_rate: 85, note: 'High mastery' },
    { type: '🏁 Post-Tests', pass_rate: 78, note: 'Target achieved' },
    { type: '📎 Assignments', pass_rate: 72, note: 'Graded by teachers' },
  ],
  difficult_quizzes: [
    { name: 'Post-Test Module 3', course: 'Web Development', attempts: 380, pass_rate: 45, avg_score: 52, status: 'danger' },
    { name: 'Tourism Final Test', course: 'Tourism Operations', attempts: 280, pass_rate: 52, avg_score: 58, status: 'warning' },
    { name: 'Data Types Post-Test', course: 'Programming Fundamentals', attempts: 420, pass_rate: 60, avg_score: 65, status: 'warning' },
  ],
  difficult_questions: [
    { id: 'Q-045', preview: 'Explain relational database normalization forms (1NF to 3NF)', type: 'Essay', correct_rate: 42, difficulty: '🔴 Very Hard' },
    { id: 'Q-089', preview: 'Write an asynchronous JavaScript promise handler', type: 'Coding', correct_rate: 48, difficulty: '🔴 Very Hard' },
    { id: 'Q-124', preview: 'Match SQL commands (JOIN, GROUP BY) with usage', type: 'Matching', correct_rate: 51, difficulty: '🟠 Hard' },
    { id: 'Q-156', preview: 'What is soil pH effect on crop nutrient absorption?', type: 'MCQ', correct_rate: 55, difficulty: '🟠 Hard' },
  ],
  improvement: [
    { course: 'Web Development', pre_test: 50, post_test: 76, growth: 26 },
    { course: 'Database Systems', pre_test: 42, post_test: 72, growth: 30 },
    { course: 'Tourism Operations', pre_test: 55, post_test: 68, growth: 13 },
  ],
  ai_insights: [
    'Students who complete practice quizzes score 20% higher on final post-tests.',
    'Taking pre-tests correlates with a 15% increase in final overall course grades.',
    'Essay questions have 2x lower completion rate compared to Multiple Choice questions.',
  ]
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
      <!-- ── MODULE HEADER CARD ── -->
      <div class="relative overflow-hidden bg-slate-800/90 border border-slate-700/70 rounded-2xl p-5 shadow-xl backdrop-blur-xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <div class="p-2.5 rounded-xl bg-purple-500/10 border border-purple-500/30 shadow-inner flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div>
                <h2 class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-teal-300 to-emerald-400 tracking-tight">
                  Analytics & Reports Module
                </h2>
                <p class="text-xs text-slate-400 mt-0.5 font-medium">
                  Academic learning progress, course metrics, faculty workload monitoring & institutional reports
                </p>
              </div>
            </div>
          </div>

          <!-- Quick Global Export Actions -->
          <div class="flex items-center gap-2">
            <button
              @click="handleExportCsv(currentTab === 'teachers' ? 'teacher' : (currentTab === 'students' ? 'student' : 'course'))"
              class="px-3.5 py-2 bg-slate-900/80 hover:bg-slate-800 text-teal-300 border border-teal-500/40 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-sm active:scale-95"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              <span>Export CSV</span>
            </button>
            <button
              @click="handleExportPdf(currentTab === 'teachers' ? 'teacher' : (currentTab === 'students' ? 'student' : 'course'))"
              class="px-3.5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:brightness-110 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-md shadow-purple-600/30 active:scale-95"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span>Export PDF</span>
            </button>
          </div>
        </div>

        <!-- ── 4 CANONICAL ROADMAP TABS ── -->
        <div class="flex flex-wrap items-center gap-2 pt-4 border-t border-slate-700/50 mt-4 text-xs">
          <button
            v-for="t in [
              { id: 'students', label: 'Student Analytics', iconPath: 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z' },
              { id: 'courses', label: 'Course Analytics', iconPath: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
              { id: 'teachers', label: 'Teacher Analytics', iconPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
              { id: 'system_reports', label: 'System Reports', iconPath: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
              { id: 'quizzes', label: 'Quiz Analytics', iconPath: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
            ]"
            :key="t.id"
            @click="currentTab = t.id"
            :class="[
              currentTab === t.id 
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30 ring-1 ring-purple-400/60' 
                : 'bg-slate-900/80 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-700/50',
              'px-3.5 py-1.5 rounded-xl transition-all flex items-center gap-1.5'
            ]"
          >
            <svg class="w-3.5 h-3.5" :class="currentTab === t.id ? 'text-white' : 'text-purple-400/80'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="t.iconPath" />
            </svg>
            <span>{{ t.label }}</span>
          </button>
        </div>
      </div>

      <!-- ── TAB 1: STUDENT ANALYTICS ── -->
      <StudentAnalytics
        v-if="currentTab === 'students'"
        :data="defaultStudentData"
        @exportReport="() => handleExportCsv('student')"
        @deepDiveRetention="() => showNotification('Opening cohort retention deep dive analyzer...')"
      />

      <!-- ── TAB 2: COURSE ANALYTICS ── -->
      <CourseAnalytics
        v-else-if="currentTab === 'courses'"
        :coursesAnalytics="coursesAnalytics"
        :majors="majors"
        :subjects="subjects"
        :teachers="teachers"
        :academicYears="academicYears"
        @exportReport="(type) => handleExportCsv(type || 'course')"
      />

      <!-- ── TAB 3: TEACHER ANALYTICS ── -->
      <TeacherAnalytics
        v-else-if="currentTab === 'teachers'"
        :teachersAnalytics="teachersAnalytics"
        :majors="majors"
        :subjects="subjects"
        :academicYears="academicYears"
        @exportReport="() => handleExportCsv('teacher')"
      />

      <!-- ── TAB 4: SYSTEM REPORTS ── -->
      <SystemReports
        v-else-if="currentTab === 'system_reports' || currentTab === 'overview'"
        :systemReports="systemReports"
        :majors="majors"
        :courses="coursesAnalytics?.list"
        :teachers="teachers"
        :academicYears="academicYears"
        @exportCsv="(type) => handleExportCsv(type)"
        @exportPdf="(type) => handleExportPdf(type)"
      />

      <!-- ── QUIZ ANALYTICS ── -->
      <QuizAnalytics
        v-else-if="currentTab === 'quizzes'"
        :data="defaultQuizData"
        @reviewQuestions="(q) => showNotification(`Opening question diagnostic review for ${q}...`)"
        @sendToTeacher="(q) => showNotification(`Sent quiz diagnostic feedback for ${q} to teacher!`)"
        @modifyQuiz="(q) => showNotification(`Redirecting to quiz editor for ${q}...`)"
        @exportReport="() => handleExportCsv('quiz')"
      />
    </div>
  </AdminLayout>
</template>

