<script setup lang="ts">
import { ref, computed } from 'vue'

const props = withDefaults(defineProps<{
  systemReports?: {
    summary?: {
      academic_year?: string
      total_students?: number
      active_courses?: number
      published_courses?: number
      course_completion?: number
      avg_quiz_score?: number
      at_risk_students?: number
    }
  }
  majors?: any[]
  courses?: any[]
  teachers?: any[]
  academicYears?: any[]
}>(), {
  systemReports: () => ({
    summary: {
      academic_year: 'Academic Year 2026 – 2027',
      total_students: 500,
      active_courses: 35,
      published_courses: 30,
      course_completion: 68,
      avg_quiz_score: 74,
      at_risk_students: 42,
    }
  }),
  majors: () => [],
  courses: () => [],
  teachers: () => [],
  academicYears: () => [],
})

// Active Report Type (Spec 1: 5 Report Types)
const selectedReportType = ref<'student' | 'course' | 'assessment' | 'completion' | 'ai_activity'>('student')

// Report Filters (Spec 2)
const selectedDateRange = ref('this_year')
const selectedAcademicYear = ref('Academic Year 2026 – 2027')
const selectedMajor = ref('')
const selectedCourse = ref('')
const selectedTeacher = ref('')

const reportTypes = [
  { id: 'student', title: 'Student Report', titleKh: 'របាយការណ៍និស្សិត', icon: '👨‍🎓', desc: 'Enrollment, progress, major breakdown, and at-risk students' },
  { id: 'course', title: 'Course Report', titleKh: 'របាយការណ៍វគ្គសិក្សា', icon: '📚', desc: 'Course capacity, completion rates, syllabus coverage' },
  { id: 'assessment', title: 'Assessment Report', titleKh: 'របាយការណ៍ការវាយតម្លៃ', icon: '📝', desc: 'Quiz scores, pass rates, assignment submissions' },
  { id: 'completion', title: 'Completion Report', titleKh: 'របាយការណ៍បញ្ចប់វគ្គ', icon: '🎓', desc: 'Course completions, certificate eligibility, retention rate' },
  { id: 'ai_activity', title: 'AI Activity Report', titleKh: 'របាយការណ៍សកម្មភាព AI', icon: '🤖', desc: 'Weak topics identified, AI study recommendations' },
]

const summary = computed(() => props.systemReports?.summary || {
  academic_year: 'Academic Year 2026 – 2027',
  total_students: 500,
  active_courses: 35,
  published_courses: 30,
  course_completion: 68,
  avg_quiz_score: 74,
  at_risk_students: 42,
})

// Preview Mock Table Rows based on selected report type
const previewRows = computed(() => {
  if (selectedReportType.value === 'student') {
    return [
      { col1: 'SPI-2026-001', col2: 'Sok Dara', col3: 'Information Technology', col4: '2 Courses', col5: '72% Progress', col6: 'Active' },
      { col1: 'SPI-2026-002', col2: 'Keo Pich', col3: 'Information Technology', col4: '2 Courses', col5: '100% Progress', col6: 'Completed' },
      { col1: 'SPI-2026-003', col2: 'Vannak Sambath', col3: 'Tourism', col4: '1 Course', col5: '45% Progress', col6: 'Active' },
      { col1: 'SPI-2026-004', col2: 'Srey Mom', col3: 'English Literature', col4: '1 Course', col5: '15% Progress', col6: 'Dropped' },
      { col1: 'SPI-2026-005', col2: 'Heng Ratana', col3: 'Agriculture', col4: '1 Course', col5: '88% Progress', col6: 'Active' },
    ]
  } else if (selectedReportType.value === 'course') {
    return [
      { col1: 'CRS-IT-WD101', col2: 'Web Development with Vue & Laravel', col3: 'Information Technology', col4: '45 Students', col5: '64% Completion', col6: 'Published' },
      { col1: 'CRS-IT-CP101', col2: 'C Programming Basics', col3: 'Information Technology', col4: '52 Students', col5: '76% Completion', col6: 'Published' },
      { col1: 'CRS-TM-TB101', col2: 'Tourism Basics & Hospitality', col3: 'Tourism', col4: '38 Students', col5: '61% Completion', col6: 'Published' },
      { col1: 'CRS-EL-EG101', col2: 'English Grammar Mastery', col3: 'English Literature', col4: '60 Students', col5: '82% Completion', col6: 'Published' },
      { col1: 'CRS-AG-PS101', col2: 'Plant Science Fundamentals', col3: 'Agriculture', col4: '42 Students', col5: '68% Completion', col6: 'Published' },
    ]
  } else if (selectedReportType.value === 'assessment') {
    return [
      { col1: 'Midterm Assessment Quiz', col2: 'Web Development', col3: '70% Passing', col4: '145 Attempts', col5: '78% Avg Score', col6: 'Active' },
      { col1: 'Concept Comprehension Check 1', col2: 'C Programming', col3: '60% Passing', col4: '120 Attempts', col5: '74% Avg Score', col6: 'Active' },
      { col1: 'Hospitality Ethics Test', col2: 'Tourism Basics', col3: '65% Passing', col4: '88 Attempts', col5: '72% Avg Score', col6: 'Active' },
      { col1: 'Syntax Grammar Check', col2: 'English Grammar', col3: '75% Passing', col4: '160 Attempts', col5: '84% Avg Score', col6: 'Active' },
    ]
  } else if (selectedReportType.value === 'completion') {
    return [
      { col1: 'Information Technology', col2: '120 Students', col3: '84 Completed', col4: '36 In Progress', col5: '70% Completion', col6: 'Certified' },
      { col1: 'Tourism', col2: '95 Students', col3: '61 Completed', col4: '34 In Progress', col5: '64% Completion', col6: 'Certified' },
      { col1: 'English Literature', col2: '105 Students', col3: '76 Completed', col4: '29 In Progress', col5: '72% Completion', col6: 'Certified' },
      { col1: 'Agriculture', col2: '90 Students', col3: '60 Completed', col4: '30 In Progress', col5: '66% Completion', col6: 'Certified' },
      { col1: 'Social Work', col2: '90 Students', col3: '61 Completed', col4: '29 In Progress', col5: '68% Completion', col6: 'Certified' },
    ]
  } else {
    return [
      { col1: 'Sok Dara (IT)', col2: 'Algorithm Complexity Big-O', col3: 'Dynamic Programming Basics', col4: 'Remediated', col5: 'High Impact', col6: 'Closed' },
      { col1: 'Vannak Sambath (TRM)', col2: 'Ecotourism Policy Standards', col3: 'Community Tourism Guidelines', col4: 'In Progress', col5: 'Moderate', col6: 'Active' },
      { col1: 'Heng Ratana (AGR)', col2: 'Soil Acidity & Treatment', col3: 'Soil Treatment Principles 101', col4: 'Remediated', col5: 'High Impact', col6: 'Closed' },
      { col1: 'Chhorn Thida (SW)', col2: 'Fieldwork Case Ethics', col3: 'Ethics & Legal Regulations', col4: 'In Progress', col5: 'Moderate', col6: 'Active' },
    ]
  }
})
</script>

<template>
  <div class="space-y-6 text-xs font-sans">
    <!-- Header Banner -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-xl backdrop-blur-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
          <span>📑</span> System Reports (របាយការណ៍ប្រព័ន្ធ)
        </h3>
        <p class="text-slate-400 text-xs mt-0.5">
          បង្កើត និងទាញយករបាយការណ៍សរុបផ្លូវការសម្រាប់ Admin និងគណៈគ្រប់គ្រង (Management)
        </p>
      </div>

      <!-- Export Buttons (Spec 4: Export CSV & Export PDF) -->
      <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
        <a
          :href="`/admin/reports/export-csv?type=${selectedReportType}`"
          class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition-all flex items-center gap-1.5"
        >
          <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span>Export CSV</span>
        </a>

        <a
          :href="`/admin/reports/export-pdf?type=${selectedReportType}`"
          target="_blank"
          class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition-all flex items-center gap-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          <span>Export PDF</span>
        </a>
      </div>
    </div>

    <!-- 1. REPORT TYPES CARDS (SPECIFICATION 1) -->
    <div class="space-y-2">
      <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
        ជ្រើសរើសប្រភេទរបាយការណ៍ (Select Report Type):
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <button
          v-for="rt in reportTypes"
          :key="rt.id"
          type="button"
          @click="selectedReportType = rt.id as any"
          class="p-3.5 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between"
          :class="selectedReportType === rt.id 
            ? 'bg-indigo-600/15 border-indigo-500 text-white shadow-md shadow-indigo-500/20 ring-1 ring-indigo-500' 
            : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:text-white hover:bg-slate-850'"
        >
          <div>
            <div class="text-xl mb-1">{{ rt.icon }}</div>
            <div class="font-bold text-xs" :class="selectedReportType === rt.id ? 'text-indigo-300' : 'text-slate-200'">
              {{ rt.title }}
            </div>
            <div class="text-[10px] text-slate-500 mt-0.5">{{ rt.titleKh }}</div>
          </div>
          <div class="text-[10px] text-slate-400 mt-2 line-clamp-2">{{ rt.desc }}</div>
        </button>
      </div>
    </div>

    <!-- 3. REPORT SUMMARY (SPECIFICATION 3) -->
    <div class="p-4 bg-slate-900/60 border border-slate-800 rounded-2xl space-y-3">
      <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
        <div class="font-bold text-slate-200 text-xs flex items-center gap-2">
          <span>📊</span>
          <span>Report Aggregate Summary (ទិន្នន័យសង្ខេប):</span>
          <span class="font-mono text-indigo-400 font-semibold">{{ summary.academic_year }}</span>
        </div>
        <span class="text-[10px] text-slate-500">Auto-calculated from System DB</span>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl text-center">
          <span class="text-[10px] text-slate-400 block font-semibold uppercase">Total Students</span>
          <span class="text-lg font-black text-white font-mono mt-0.5">{{ summary.total_students }}</span>
        </div>
        <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl text-center">
          <span class="text-[10px] text-slate-400 block font-semibold uppercase">Active Courses</span>
          <span class="text-lg font-black text-indigo-300 font-mono mt-0.5">{{ summary.active_courses }}</span>
        </div>
        <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl text-center">
          <span class="text-[10px] text-slate-400 block font-semibold uppercase">Published Courses</span>
          <span class="text-lg font-black text-emerald-300 font-mono mt-0.5">{{ summary.published_courses }}</span>
        </div>
        <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl text-center">
          <span class="text-[10px] text-slate-400 block font-semibold uppercase">Course Completion</span>
          <span class="text-lg font-black text-teal-300 font-mono mt-0.5">{{ summary.course_completion }}%</span>
        </div>
        <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl text-center">
          <span class="text-[10px] text-slate-400 block font-semibold uppercase">Avg Quiz Score</span>
          <span class="text-lg font-black text-amber-300 font-mono mt-0.5">{{ summary.avg_quiz_score }}%</span>
        </div>
        <div class="p-3 bg-slate-950/60 border border-slate-800/80 rounded-xl text-center">
          <span class="text-[10px] text-slate-400 block font-semibold uppercase">At-Risk Students</span>
          <span class="text-lg font-black text-rose-400 font-mono mt-0.5">{{ summary.at_risk_students }}</span>
        </div>
      </div>
    </div>

    <!-- 2. REPORT FILTERS (SPECIFICATION 2) -->
    <div class="p-3.5 bg-slate-900/60 rounded-2xl border border-slate-800 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
      <!-- 1. Date Range -->
      <div>
        <label class="block text-[10px] font-semibold text-slate-400 mb-1">Date Range</label>
        <select
          v-model="selectedDateRange"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="this_month">This Month</option>
          <option value="last_3_months">Last 3 Months</option>
          <option value="this_semester">This Semester (S1)</option>
          <option value="this_year">This Academic Year (2026-2027)</option>
        </select>
      </div>

      <!-- 2. Academic Year -->
      <div>
        <label class="block text-[10px] font-semibold text-slate-400 mb-1">Academic Year</label>
        <select
          v-model="selectedAcademicYear"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="Academic Year 2026 – 2027">Academic Year 2026 – 2027</option>
          <option value="Academic Year 2025 – 2026">Academic Year 2025 – 2026</option>
        </select>
      </div>

      <!-- 3. Major (5 SPI Majors) -->
      <div>
        <label class="block text-[10px] font-semibold text-slate-400 mb-1">Major</label>
        <select
          v-model="selectedMajor"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="">All 5 Majors</option>
          <option value="Information Technology">Information Technology</option>
          <option value="Tourism">Tourism</option>
          <option value="English Literature">English Literature</option>
          <option value="Agriculture">Agriculture</option>
          <option value="Social Work">Social Work</option>
        </select>
      </div>

      <!-- 4. Course -->
      <div>
        <label class="block text-[10px] font-semibold text-slate-400 mb-1">Course</label>
        <select
          v-model="selectedCourse"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="">All Courses</option>
          <option v-for="c in props.courses" :key="c.id" :value="c.title">
            {{ c.title }}
          </option>
        </select>
      </div>

      <!-- 5. Teacher -->
      <div>
        <label class="block text-[10px] font-semibold text-slate-400 mb-1">Teacher</label>
        <select
          v-model="selectedTeacher"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="">All Teachers</option>
          <option v-for="t in props.teachers" :key="t.id" :value="t.name">
            {{ t.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- REPORT PREVIEW TABLE -->
    <div class="bg-slate-900/60 rounded-2xl border border-slate-800/80 overflow-hidden shadow-sm">
      <div class="p-3.5 bg-slate-950/70 border-b border-slate-800 flex items-center justify-between">
        <div class="font-bold text-slate-200 text-xs flex items-center gap-2">
          <span>📋</span>
          <span class="uppercase">Report Data Preview: {{ reportTypes.find(r => r.id === selectedReportType)?.title }}</span>
        </div>
        <span class="text-[10px] text-slate-500">Live preview before exporting</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
          <thead class="bg-slate-950/90 text-slate-400 text-[10px] uppercase font-bold border-b border-slate-800">
            <tr>
              <th class="p-3">Reference / Key</th>
              <th class="p-3">Primary Field</th>
              <th class="p-3">Category / Major</th>
              <th class="p-3">Metrics / Volume</th>
              <th class="p-3">Progress / Achievement</th>
              <th class="p-3 text-right">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 font-medium">
            <tr v-for="(row, idx) in previewRows" :key="idx" class="hover:bg-slate-800/40 transition-colors">
              <td class="p-3 font-mono font-bold text-sky-400">{{ row.col1 }}</td>
              <td class="p-3 font-bold text-white">{{ row.col2 }}</td>
              <td class="p-3 text-slate-300">{{ row.col3 }}</td>
              <td class="p-3 font-mono text-slate-200">{{ row.col4 }}</td>
              <td class="p-3 font-mono text-emerald-400">{{ row.col5 }}</td>
              <td class="p-3 text-right">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                  {{ row.col6 }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
