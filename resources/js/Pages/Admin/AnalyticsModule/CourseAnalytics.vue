<script setup lang="ts">
import { ref, computed, watch } from 'vue'

const props = withDefaults(defineProps<{
  coursesAnalytics?: {
    summary?: {
      total_courses?: number
      published_courses?: number
      avg_enrollment?: number
      avg_progress?: number
      avg_completion_rate?: number
      avg_quiz_score?: number
    }
    list?: any[]
  }
  majors?: any[]
  subjects?: any[]
  teachers?: any[]
  academicYears?: any[]
}>(), {
  coursesAnalytics: () => ({
    summary: {
      total_courses: 35,
      published_courses: 30,
      avg_enrollment: 45,
      avg_progress: 72,
      avg_completion_rate: 68,
      avg_quiz_score: 76,
    },
    list: []
  }),
  majors: () => [],
  subjects: () => [],
  teachers: () => [],
  academicYears: () => [],
})

const emit = defineEmits<{
  (e: 'exportReport', type: string): void
}>()

// Filter state
const search = ref('')
const selectedMajor = ref('')
const selectedSubject = ref('')
const selectedTeacher = ref('')
const selectedAcademicYear = ref('')
const selectedStatus = ref('')

// Pagination
const currentPage = ref(1)
const pageSize = ref(10)

// Modal state
const showDetailModal = ref(false)
const selectedCourse = ref<any | null>(null)

// Fallback courses list if empty
const coursesList = computed(() => {
  if (props.coursesAnalytics?.list && props.coursesAnalytics.list.length > 0) {
    return props.coursesAnalytics.list
  }
  return [
    {
      id: 1,
      code: 'CRS-IT-WD101',
      title: 'Web Development with Vue & Laravel',
      major: 'Information Technology',
      major_id: 1,
      subject: 'Web Development',
      teacher: 'Mr. Sophea',
      academic_year: 'Academic Year 2026 – 2027',
      students_count: 45,
      avg_progress: 72,
      quiz_average: 76,
      completion_rate: 64,
      status: 'published',
      quizzes_count: 4,
      assignments_count: 2,
      lessons_performance: [
        { order: 1, title: 'HTML5 & Modern Layouts', completion_rate: 92, drop_off_rate: 6, is_difficult: false, status_flag: '✓ High Mastery' },
        { order: 2, title: 'CSS3 Flexbox & Grid Masterclass', completion_rate: 85, drop_off_rate: 12, is_difficult: false, status_flag: '✓ High Mastery' },
        { order: 3, title: 'Vue 3 Composition API & Reactive State', completion_rate: 68, drop_off_rate: 22, is_difficult: false, status_flag: 'Normal Pace' },
        { order: 4, title: 'Laravel API Authentication & Eloquent', completion_rate: 54, drop_off_rate: 34, is_difficult: true, status_flag: '⚠️ Difficult Topic' },
      ]
    },
    {
      id: 2,
      code: 'CRS-IT-CP101',
      title: 'C Programming Basics',
      major: 'Information Technology',
      major_id: 1,
      subject: 'C Programming',
      teacher: 'Mr. Sophea',
      academic_year: 'Academic Year 2026 – 2027',
      students_count: 52,
      avg_progress: 82,
      quiz_average: 78,
      completion_rate: 76,
      status: 'published',
      quizzes_count: 3,
      assignments_count: 2,
      lessons_performance: [
        { order: 1, title: 'Variables, Types & Syntax', completion_rate: 94, drop_off_rate: 4, is_difficult: false, status_flag: '✓ High Mastery' },
        { order: 2, title: 'Control Flow & Loops', completion_rate: 82, drop_off_rate: 14, is_difficult: false, status_flag: '✓ High Mastery' },
        { order: 3, title: 'Pointers & Dynamic Memory Allocation', completion_rate: 48, drop_off_rate: 42, is_difficult: true, status_flag: '⚠️ Difficult Topic' },
      ]
    },
    {
      id: 3,
      code: 'CRS-TM-TB101',
      title: 'Tourism Basics & Hospitality',
      major: 'Tourism',
      major_id: 2,
      subject: 'Tourism Basics',
      teacher: 'Mr. Long',
      academic_year: 'Academic Year 2026 – 2027',
      students_count: 38,
      avg_progress: 68,
      quiz_average: 74,
      completion_rate: 61,
      status: 'published',
      quizzes_count: 2,
      assignments_count: 1,
      lessons_performance: [
        { order: 1, title: 'Introduction to Hospitality Ecosystem', completion_rate: 90, drop_off_rate: 8, is_difficult: false, status_flag: '✓ High Mastery' },
        { order: 2, title: 'Ecotourism & Cambodian Heritage Policies', completion_rate: 75, drop_off_rate: 18, is_difficult: false, status_flag: 'Normal Pace' },
        { order: 3, title: 'Financial Costing for Tourism Services', completion_rate: 52, drop_off_rate: 36, is_difficult: true, status_flag: '⚠️ Difficult Topic' },
      ]
    },
    {
      id: 4,
      code: 'CRS-EL-EG101',
      title: 'English Grammar Mastery',
      major: 'English Literature',
      major_id: 3,
      subject: 'English Grammar',
      teacher: 'Ms. Srey',
      academic_year: 'Academic Year 2026 – 2027',
      students_count: 60,
      avg_progress: 88,
      quiz_average: 84,
      completion_rate: 82,
      status: 'published',
      quizzes_count: 4,
      assignments_count: 2,
      lessons_performance: [
        { order: 1, title: 'Complex Sentence Structures', completion_rate: 95, drop_off_rate: 5, is_difficult: false, status_flag: '✓ High Mastery' },
        { order: 2, title: 'Academic Essay Punctuation', completion_rate: 88, drop_off_rate: 10, is_difficult: false, status_flag: '✓ High Mastery' },
        { order: 3, title: 'Advanced Modal Auxiliaries', completion_rate: 79, drop_off_rate: 18, is_difficult: false, status_flag: 'Normal Pace' },
      ]
    },
    {
      id: 5,
      code: 'CRS-AG-PS101',
      title: 'Plant Science Fundamentals',
      major: 'Agriculture',
      major_id: 4,
      subject: 'Plant Science',
      teacher: 'Mr. Vuthy',
      academic_year: 'Academic Year 2026 – 2027',
      students_count: 42,
      avg_progress: 71,
      quiz_average: 75,
      completion_rate: 68,
      status: 'published',
      quizzes_count: 3,
      assignments_count: 2,
      lessons_performance: [
        { order: 1, title: 'Soil Biology & Nutrient Cycles', completion_rate: 92, drop_off_rate: 6, is_difficult: false, status_flag: '✓ High Mastery' },
        { order: 2, title: 'Plant Diseases & Entomology', completion_rate: 69, drop_off_rate: 22, is_difficult: false, status_flag: 'Normal Pace' },
        { order: 3, title: 'Hydroponics & Irrigation Engineering', completion_rate: 55, drop_off_rate: 38, is_difficult: true, status_flag: '⚠️ Difficult Topic' },
      ]
    },
    {
      id: 6,
      code: 'CRS-SW-SW101',
      title: 'Introduction to Social Work',
      major: 'Social Work',
      major_id: 5,
      subject: 'Social Work 101',
      teacher: 'Mr. Rithy',
      academic_year: 'Academic Year 2026 – 2027',
      students_count: 35,
      avg_progress: 70,
      quiz_average: 73,
      completion_rate: 65,
      status: 'published',
      quizzes_count: 2,
      assignments_count: 1,
      lessons_performance: [
        { order: 1, title: 'Ethical Foundations of Social Work', completion_rate: 89, drop_off_rate: 9, is_difficult: false, status_flag: '✓ High Mastery' },
        { order: 2, title: 'Community Counseling Case Studies', completion_rate: 72, drop_off_rate: 21, is_difficult: false, status_flag: 'Normal Pace' },
        { order: 3, title: 'Juvenile Welfare Legal Framework', completion_rate: 56, drop_off_rate: 35, is_difficult: true, status_flag: '⚠️ Difficult Topic' },
      ]
    }
  ]
})

// Filtered Courses
const filteredCourses = computed(() => {
  return coursesList.value.filter(c => {
    const q = search.value.toLowerCase().trim()
    const matchesSearch = !q || (
      (c.title && c.title.toLowerCase().includes(q)) ||
      (c.code && c.code.toLowerCase().includes(q)) ||
      (c.teacher && c.teacher.toLowerCase().includes(q))
    )

    const matchesMajor = !selectedMajor.value || c.major_id == selectedMajor.value || c.major === selectedMajor.value
    const matchesSubject = !selectedSubject.value || c.subject_id == selectedSubject.value || c.subject === selectedSubject.value
    const matchesTeacher = !selectedTeacher.value || c.teacher_id == selectedTeacher.value || c.teacher === selectedTeacher.value
    const matchesYear = !selectedAcademicYear.value || c.academic_year === selectedAcademicYear.value
    const matchesStatus = !selectedStatus.value || c.status === selectedStatus.value

    return matchesSearch && matchesMajor && matchesSubject && matchesTeacher && matchesYear && matchesStatus
  })
})

watch([search, selectedMajor, selectedSubject, selectedTeacher, selectedAcademicYear, selectedStatus], () => {
  currentPage.value = 1
})

const totalPages = computed(() => Math.ceil(filteredCourses.value.length / pageSize.value) || 1)
const paginatedCourses = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredCourses.value.slice(start, start + pageSize.value)
})

// Open Course Detail Modal
const openDetailModal = (course: any) => {
  selectedCourse.value = course
  showDetailModal.value = true
}

const summary = computed(() => props.coursesAnalytics?.summary || {
  total_courses: 35,
  published_courses: 30,
  avg_enrollment: 45,
  avg_progress: 72,
  avg_completion_rate: 68,
  avg_quiz_score: 76,
})
</script>

<template>
  <div class="space-y-5 text-xs font-sans">
    <!-- Header Banner -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-xl backdrop-blur-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
          <span>📚</span> Course Analytics (ស្ថិតិវគ្គសិក្សា)
        </h3>
        <p class="text-slate-400 text-xs mt-0.5">
          តាមដានដំណើរការ និងលទ្ធផលសិក្សារបស់និស្សិតតាម Course នីមួយៗ និង Lesson Performance សម្រាប់ AI Analysis
        </p>
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
        <a
          href="/admin/reports/export-csv?type=course"
          class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition-all flex items-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span>Export CSV</span>
        </a>

        <a
          href="/admin/reports/export-pdf?type=course"
          target="_blank"
          class="px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition-all flex items-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
          </svg>
          <span>Export PDF</span>
        </a>
      </div>
    </div>

    <!-- 1. COURSE SUMMARY (6 STAT CARDS - SPECIFICATION 1) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
      <!-- Total Courses -->
      <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-md">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Courses</span>
        <div class="text-xl font-black text-white font-mono mt-1">{{ summary.total_courses }}</div>
        <span class="text-[10px] text-slate-500 mt-0.5 block">Catalog Volume</span>
      </div>

      <!-- Published Courses -->
      <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 backdrop-blur-md">
        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Published</span>
        <div class="text-xl font-black text-emerald-300 font-mono mt-1">{{ summary.published_courses }}</div>
        <span class="text-[10px] text-emerald-400/80 mt-0.5 block">Active for Students</span>
      </div>

      <!-- Average Enrollment -->
      <div class="p-3.5 rounded-2xl bg-sky-500/10 border border-sky-500/25 backdrop-blur-md">
        <span class="text-[10px] font-bold text-sky-400 uppercase tracking-wider block">Avg Enrollment</span>
        <div class="text-xl font-black text-sky-300 font-mono mt-1">{{ summary.avg_enrollment }}</div>
        <span class="text-[10px] text-sky-400/80 mt-0.5 block">Students / Course</span>
      </div>

      <!-- Average Progress -->
      <div class="p-3.5 rounded-2xl bg-indigo-500/10 border border-indigo-500/25 backdrop-blur-md">
        <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider block">Avg Progress</span>
        <div class="text-xl font-black text-indigo-300 font-mono mt-1">{{ summary.avg_progress }}%</div>
        <span class="text-[10px] text-indigo-400/80 mt-0.5 block">Learning Pace</span>
      </div>

      <!-- Average Completion Rate -->
      <div class="p-3.5 rounded-2xl bg-teal-500/10 border border-teal-500/25 backdrop-blur-md">
        <span class="text-[10px] font-bold text-teal-400 uppercase tracking-wider block">Completion Rate</span>
        <div class="text-xl font-black text-teal-300 font-mono mt-1">{{ summary.avg_completion_rate }}%</div>
        <span class="text-[10px] text-teal-400/80 mt-0.5 block">Completed Cohort</span>
      </div>

      <!-- Average Quiz Score -->
      <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/25 backdrop-blur-md">
        <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block">Avg Quiz Score</span>
        <div class="text-xl font-black text-amber-300 font-mono mt-1">{{ summary.avg_quiz_score }}%</div>
        <span class="text-[10px] text-amber-400/80 mt-0.5 block">Assessment Average</span>
      </div>
    </div>

    <!-- 3. FILTERS (SPECIFICATION 3) -->
    <div class="p-3.5 bg-slate-900/60 rounded-2xl border border-slate-800 backdrop-blur-md grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5">
      <!-- Search -->
      <div class="relative lg:col-span-2">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="ស្វែងរក Course Code, Course Name..."
          class="w-full pl-9 pr-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        />
      </div>

      <!-- Major Filter (5 SPI Majors) -->
      <div>
        <select
          v-model="selectedMajor"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="">Major: All 5 Majors</option>
          <option value="Information Technology">Information Technology</option>
          <option value="Tourism">Tourism</option>
          <option value="English Literature">English Literature</option>
          <option value="Agriculture">Agriculture</option>
          <option value="Social Work">Social Work</option>
        </select>
      </div>

      <!-- Teacher Filter -->
      <div>
        <select
          v-model="selectedTeacher"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="">Teacher: All Teachers</option>
          <option v-for="t in props.teachers" :key="t.id" :value="t.name">
            {{ t.name }}
          </option>
        </select>
      </div>

      <!-- Academic Year Filter -->
      <div>
        <select
          v-model="selectedAcademicYear"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="">Year: All Academic Years</option>
          <option v-for="y in props.academicYears" :key="y.id" :value="y.name">
            {{ y.name }}
          </option>
        </select>
      </div>

      <!-- Status Filter -->
      <div>
        <select
          v-model="selectedStatus"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="">Status: All Status</option>
          <option value="published">Published</option>
          <option value="pending">Pending</option>
          <option value="rejected">Rejected</option>
          <option value="draft">Draft</option>
        </select>
      </div>
    </div>

    <!-- 2. COURSE PERFORMANCE LIST TABLE (SPECIFICATION 2) -->
    <div class="bg-slate-900/60 rounded-2xl border border-slate-800/80 backdrop-blur-md overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
            <tr>
              <th class="py-3 px-4">Course</th>
              <th class="py-3 px-4">Major</th>
              <th class="py-3 px-4">Teacher</th>
              <th class="py-3 px-4 text-center">Students</th>
              <th class="py-3 px-4 min-w-[130px]">Avg. Progress</th>
              <th class="py-3 px-4 text-center">Quiz Average</th>
              <th class="py-3 px-4 text-center">Completion Rate</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 text-center">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <tr
              v-for="c in paginatedCourses"
              :key="c.id"
              class="hover:bg-slate-800/40 transition-colors group"
            >
              <!-- 1. Course -->
              <td class="py-3 px-4">
                <div class="font-bold text-white group-hover:text-indigo-300 transition-colors">
                  {{ c.title }}
                </div>
                <div class="text-[10px] font-mono text-sky-400 mt-0.5">{{ c.code }}</div>
              </td>

              <!-- 2. Major (5 SPI Majors) -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">
                  {{ c.major }}
                </span>
              </td>

              <!-- 3. Teacher -->
              <td class="py-3 px-4 whitespace-nowrap text-slate-300 font-medium">
                {{ c.teacher }}
              </td>

              <!-- 4. Students -->
              <td class="py-3 px-4 text-center font-bold text-white font-mono">
                {{ c.students_count }}
              </td>

              <!-- 5. Avg. Progress -->
              <td class="py-3 px-4">
                <div class="flex items-center justify-between text-[11px] font-mono mb-1">
                  <span class="font-bold text-slate-200">{{ c.avg_progress }}%</span>
                </div>
                <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="c.avg_progress >= 75 ? 'bg-emerald-400' : (c.avg_progress >= 50 ? 'bg-sky-400' : 'bg-amber-400')"
                    :style="{ width: `${c.avg_progress}%` }"
                  ></div>
                </div>
              </td>

              <!-- 6. Quiz Average -->
              <td class="py-3 px-4 text-center whitespace-nowrap font-mono font-bold" :class="c.quiz_average >= 75 ? 'text-emerald-400' : 'text-amber-400'">
                {{ c.quiz_average }}%
              </td>

              <!-- 7. Completion Rate -->
              <td class="py-3 px-4 text-center whitespace-nowrap font-mono font-bold text-teal-300">
                {{ c.completion_rate }}%
              </td>

              <!-- 8. Status -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span
                  v-if="c.status === 'published'"
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30"
                >
                  Published
                </span>
                <span
                  v-else-if="c.status === 'rejected'"
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-300 border border-rose-500/30"
                >
                  Rejected
                </span>
                <span
                  v-else
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30"
                >
                  Pending
                </span>
              </td>

              <!-- 9. Actions (View Detail) -->
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <button
                  @click="openDetailModal(c)"
                  class="px-3 py-1.5 rounded-xl bg-indigo-600/80 hover:bg-indigo-600 text-white font-bold text-xs shadow-sm transition-all inline-flex items-center gap-1.5 cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span>View</span>
                </button>
              </td>
            </tr>

            <tr v-if="paginatedCourses.length === 0">
              <td colspan="9" class="text-center py-10 text-slate-500">
                មិនមាន Course ត្រូវតាមលក្ខខណ្ឌ Filter ឡើយ
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="p-3.5 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
        <div>
          បង្ហាញពី <span class="font-bold text-white">{{ filteredCourses.length ? ((currentPage - 1) * pageSize + 1) : 0 }}</span>
          ដល់ <span class="font-bold text-white">{{ Math.min(currentPage * pageSize, filteredCourses.length) }}</span>
          នៃ <span class="font-bold text-white">{{ filteredCourses.length }}</span> Courses
        </div>
        <div class="flex items-center gap-1.5">
          <button
            @click="currentPage > 1 && currentPage--"
            :disabled="currentPage <= 1"
            class="px-2.5 py-1 rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed text-white"
          >
            Previous
          </button>
          <span class="px-2 font-mono text-slate-300">ទំព័រ {{ currentPage }} / {{ totalPages }}</span>
          <button
            @click="currentPage < totalPages && currentPage++"
            :disabled="currentPage >= totalPages"
            class="px-2.5 py-1 rounded-lg border border-slate-700 bg-slate-800 hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed text-white"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- 4. COURSE DETAIL ANALYTICS MODAL (SPECIFICATION 4) -->
    <div
      v-if="showDetailModal && selectedCourse"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md overflow-y-auto"
    >
      <div class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5 my-8 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-bold text-white">{{ selectedCourse.title }}</h3>
              <p class="text-xs text-slate-400 font-mono">{{ selectedCourse.code }} • {{ selectedCourse.major }}</p>
            </div>
          </div>
          <button @click="showDetailModal = false" class="text-slate-400 hover:text-white cursor-pointer">
            ✕
          </button>
        </div>

        <!-- 5 Key Analytics KPI Metrics -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center">
          <div class="p-2.5 bg-slate-950/60 border border-slate-800 rounded-xl">
            <span class="text-[10px] text-slate-400 block font-semibold">Enrollment</span>
            <span class="text-base font-bold text-white font-mono">{{ selectedCourse.students_count }}</span>
          </div>
          <div class="p-2.5 bg-slate-950/60 border border-slate-800 rounded-xl">
            <span class="text-[10px] text-slate-400 block font-semibold">Learning Progress</span>
            <span class="text-base font-bold text-indigo-400 font-mono">{{ selectedCourse.avg_progress }}%</span>
          </div>
          <div class="p-2.5 bg-slate-950/60 border border-slate-800 rounded-xl">
            <span class="text-[10px] text-slate-400 block font-semibold">Quiz Avg</span>
            <span class="text-base font-bold text-amber-400 font-mono">{{ selectedCourse.quiz_average }}%</span>
          </div>
          <div class="p-2.5 bg-slate-950/60 border border-slate-800 rounded-xl">
            <span class="text-[10px] text-slate-400 block font-semibold">Assignments</span>
            <span class="text-base font-bold text-sky-400 font-mono">{{ selectedCourse.assignments_count }} Graded</span>
          </div>
          <div class="p-2.5 bg-slate-950/60 border border-slate-800 rounded-xl col-span-2 sm:col-span-1">
            <span class="text-[10px] text-slate-400 block font-semibold">Completion</span>
            <span class="text-base font-bold text-emerald-400 font-mono">{{ selectedCourse.completion_rate }}%</span>
          </div>
        </div>

        <!-- LESSON PERFORMANCE BREAKDOWN (SPECIFICATION 4: FEEDS AI DIFFICULT TOPICS) -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
              <span>📊</span> Lesson Performance & Drop-off Analysis
            </h4>
            <span class="text-[10px] text-purple-300 font-mono bg-purple-500/10 px-2 py-0.5 rounded-full border border-purple-500/20">
              Feeds AI Difficult Topics
            </span>
          </div>

          <div class="space-y-2">
            <div
              v-for="lesson in (selectedCourse.lessons_performance || [])"
              :key="lesson.id || lesson.order"
              class="p-3 bg-slate-950/60 border border-slate-800 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
              <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-lg bg-slate-800 text-indigo-400 text-xs font-bold flex items-center justify-center shrink-0">
                  {{ lesson.order }}
                </span>
                <div>
                  <div class="font-bold text-slate-200 text-xs">{{ lesson.title }}</div>
                  <div class="text-[10px] text-slate-400">Drop-off: {{ lesson.drop_off_rate }}% of learners</div>
                </div>
              </div>

              <div class="flex items-center gap-3 justify-between sm:justify-end">
                <div class="w-28 text-right">
                  <div class="text-[11px] font-mono font-bold text-white">{{ lesson.completion_rate }}% Completed</div>
                  <div class="w-full h-1.5 bg-slate-800 rounded-full mt-1 overflow-hidden">
                    <div
                      class="h-full rounded-full"
                      :class="lesson.completion_rate >= 80 ? 'bg-emerald-400' : (lesson.completion_rate >= 60 ? 'bg-sky-400' : 'bg-rose-400')"
                      :style="{ width: `${lesson.completion_rate}%` }"
                    ></div>
                  </div>
                </div>

                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                  :class="lesson.is_difficult ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'"
                >
                  {{ lesson.status_flag }}
                </span>
              </div>
            </div>
          </div>

          <!-- AI Flow Note -->
          <div class="p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-[11px] flex items-center gap-2">
            <span>🤖</span>
            <span>
              <strong>AI Diagnostic Link:</strong> មេរៀនដែលមាន completion_rate &lt; 60% ត្រូវបាន auto-flag ទៅកាន់ <strong>AI Management → Difficult Topics</strong> ដើម្បីបង្កើតអនុសាសន៍រៀនបំប៉ន។
            </span>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end pt-3 border-t border-slate-800">
          <button
            @click="showDetailModal = false"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs cursor-pointer"
          >
            បិទ (Close)
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
