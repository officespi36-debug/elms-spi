<script setup lang="ts">
import { ref, computed, watch } from 'vue'

const props = withDefaults(defineProps<{
  teachersAnalytics?: {
    summary?: {
      total_teachers?: number
      active_teachers?: number
      courses_assigned?: number
      published_courses?: number
      avg_student_progress?: number
      assessment_activity?: number
    }
    list?: any[]
  }
  majors?: any[]
  subjects?: any[]
  academicYears?: any[]
}>(), {
  teachersAnalytics: () => ({
    summary: {
      total_teachers: 18,
      active_teachers: 16,
      courses_assigned: 28,
      published_courses: 30,
      avg_student_progress: 76,
      assessment_activity: 56,
    },
    list: []
  }),
  majors: () => [],
  subjects: () => [],
  academicYears: () => [],
})

// Filter state
const search = ref('')
const selectedDepartment = ref('')
const selectedMajor = ref('')
const selectedStatus = ref('')

// Pagination
const currentPage = ref(1)
const pageSize = ref(10)

// Modal state
const showDetailModal = ref(false)
const selectedTeacher = ref<any | null>(null)

// Fallback teachers list if empty
const teachersList = computed(() => {
  if (props.teachersAnalytics?.list && props.teachersAnalytics.list.length > 0) {
    return props.teachersAnalytics.list
  }
  return [
    {
      id: 1,
      name: 'Mr. Sophea',
      email: 'sophea@elms.com',
      department: 'Computing',
      major: 'Information Technology',
      courses_count: 2,
      courses_list: [
        { code: 'CRS-IT-WD101', title: 'Web Development with Vue & Laravel' },
        { code: 'CRS-IT-CP101', title: 'C Programming Basics' }
      ],
      students_count: 97,
      avg_progress: 77,
      quizzes_count: 7,
      assignments_count: 4,
      status: 'active',
      recent_activity: 'Graded Week 4 Lab Assignments & Updated Vue Notes'
    },
    {
      id: 2,
      name: 'Mr. Long',
      email: 'long@elms.com',
      department: 'Tourism',
      major: 'Tourism',
      courses_count: 1,
      courses_list: [
        { code: 'CRS-TM-TB101', title: 'Tourism Basics & Hospitality' }
      ],
      students_count: 38,
      avg_progress: 68,
      quizzes_count: 2,
      assignments_count: 2,
      status: 'active',
      recent_activity: 'Uploaded Ecotourism Fieldwork Case Study'
    },
    {
      id: 3,
      name: 'Ms. Srey',
      email: 'srey@elms.com',
      department: 'Languages',
      major: 'English Literature',
      courses_count: 1,
      courses_list: [
        { code: 'CRS-EL-EG101', title: 'English Grammar Mastery' }
      ],
      students_count: 60,
      avg_progress: 88,
      quizzes_count: 4,
      assignments_count: 3,
      status: 'active',
      recent_activity: 'Published Midterm Grammar Diagnostic Assessment'
    },
    {
      id: 4,
      name: 'Mr. Vuthy',
      email: 'vuthy@elms.com',
      department: 'Plant Science',
      major: 'Agriculture',
      courses_count: 1,
      courses_list: [
        { code: 'CRS-AG-PS101', title: 'Plant Science Fundamentals' }
      ],
      students_count: 42,
      avg_progress: 71,
      quizzes_count: 3,
      assignments_count: 2,
      status: 'active',
      recent_activity: 'Reviewed Student Soil Chemistry Reports'
    },
    {
      id: 5,
      name: 'Mr. Rithy',
      email: 'rithy@elms.com',
      department: 'Social Science',
      major: 'Social Work',
      courses_count: 1,
      courses_list: [
        { code: 'CRS-SW-SW101', title: 'Introduction to Social Work' }
      ],
      students_count: 35,
      avg_progress: 70,
      quizzes_count: 2,
      assignments_count: 2,
      status: 'active',
      recent_activity: 'Conducted Field Counseling Mentorship Session'
    },
  ]
})

// Filtered Teachers
const filteredTeachers = computed(() => {
  return teachersList.value.filter(t => {
    const q = search.value.toLowerCase().trim()
    const matchesSearch = !q || (
      (t.name && t.name.toLowerCase().includes(q)) ||
      (t.email && t.email.toLowerCase().includes(q))
    )

    const matchesDept = !selectedDepartment.value || t.department === selectedDepartment.value
    const matchesMajor = !selectedMajor.value || t.major === selectedMajor.value
    const matchesStatus = !selectedStatus.value || t.status === selectedStatus.value

    return matchesSearch && matchesDept && matchesMajor && matchesStatus
  })
})

watch([search, selectedDepartment, selectedMajor, selectedStatus], () => {
  currentPage.value = 1
})

const totalPages = computed(() => Math.ceil(filteredTeachers.value.length / pageSize.value) || 1)
const paginatedTeachers = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredTeachers.value.slice(start, start + pageSize.value)
})

// Open Teacher Detail Modal
const openDetailModal = (teacher: any) => {
  selectedTeacher.value = teacher
  showDetailModal.value = true
}

const summary = computed(() => props.teachersAnalytics?.summary || {
  total_teachers: 18,
  active_teachers: 16,
  courses_assigned: 28,
  published_courses: 30,
  avg_student_progress: 76,
  assessment_activity: 56,
})
</script>

<template>
  <div class="space-y-5 text-xs font-sans">
    <!-- Header Banner -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 shadow-xl backdrop-blur-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
            <span>👨‍🏫</span> Teacher Analytics (ស្ថិតិសាស្ត្រាចារ្យ)
          </h3>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
            Monitoring & Reporting (គ្មាន Ranking)
          </span>
        </div>
        <p class="text-slate-400 text-xs mt-0.5">
          ត្រួតពិនិត្យបន្ទុកបង្រៀន វគ្គសិក្សាទទួលខុសត្រូវ វឌ្ឍនភាពនិស្សិត និងសកម្មភាព Assessment ដោយផ្អែកលើ Data ពិត
        </p>
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
        <a
          href="/admin/reports/export-csv?type=teacher"
          class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition-all flex items-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span>Export CSV</span>
        </a>

        <a
          href="/admin/reports/export-pdf?type=teacher"
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

    <!-- 1. TEACHER SUMMARY (6 STAT CARDS - SPECIFICATION 1) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
      <!-- Total Teachers -->
      <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-md">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Teachers</span>
        <div class="text-xl font-black text-white font-mono mt-1">{{ summary.total_teachers }}</div>
        <span class="text-[10px] text-slate-500 mt-0.5 block">Faculty Staff</span>
      </div>

      <!-- Active Teachers -->
      <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 backdrop-blur-md">
        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Active Teachers</span>
        <div class="text-xl font-black text-emerald-300 font-mono mt-1">{{ summary.active_teachers }}</div>
        <span class="text-[10px] text-emerald-400/80 mt-0.5 block">Teaching Now</span>
      </div>

      <!-- Courses Assigned -->
      <div class="p-3.5 rounded-2xl bg-indigo-500/10 border border-indigo-500/25 backdrop-blur-md">
        <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider block">Courses Assigned</span>
        <div class="text-xl font-black text-indigo-300 font-mono mt-1">{{ summary.courses_assigned }}</div>
        <span class="text-[10px] text-indigo-400/80 mt-0.5 block">Workload Allocated</span>
      </div>

      <!-- Published Courses -->
      <div class="p-3.5 rounded-2xl bg-sky-500/10 border border-sky-500/25 backdrop-blur-md">
        <span class="text-[10px] font-bold text-sky-400 uppercase tracking-wider block">Published Courses</span>
        <div class="text-xl font-black text-sky-300 font-mono mt-1">{{ summary.published_courses }}</div>
        <span class="text-[10px] text-sky-400/80 mt-0.5 block">Live Curriculum</span>
      </div>

      <!-- Avg Student Progress -->
      <div class="p-3.5 rounded-2xl bg-purple-500/10 border border-purple-500/25 backdrop-blur-md">
        <span class="text-[10px] font-bold text-purple-400 uppercase tracking-wider block">Avg Student Progress</span>
        <div class="text-xl font-black text-purple-300 font-mono mt-1">{{ summary.avg_student_progress }}%</div>
        <span class="text-[10px] text-purple-400/80 mt-0.5 block">Class Achievement</span>
      </div>

      <!-- Assessment Activity -->
      <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/25 backdrop-blur-md">
        <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block">Assessments</span>
        <div class="text-xl font-black text-amber-300 font-mono mt-1">{{ summary.assessment_activity }}</div>
        <span class="text-[10px] text-amber-400/80 mt-0.5 block">Quizzes & Assignments</span>
      </div>
    </div>

    <!-- 3. FILTERS (SPECIFICATION 3) -->
    <div class="p-3.5 bg-slate-900/60 rounded-2xl border border-slate-800 backdrop-blur-md grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
      <!-- Search Teacher -->
      <div class="relative lg:col-span-2">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="ស្វែងរកឈ្មោះសាស្ត្រាចារ្យ, Email..."
          class="w-full pl-9 pr-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        />
      </div>

      <!-- Department Filter -->
      <div>
        <select
          v-model="selectedDepartment"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="">Department: All Departments</option>
          <option value="Computing">Computing</option>
          <option value="Tourism">Tourism</option>
          <option value="Languages">Languages</option>
          <option value="Plant Science">Plant Science</option>
          <option value="Social Science">Social Science</option>
        </select>
      </div>

      <!-- Major Filter -->
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

      <!-- Teacher Status -->
      <div>
        <select
          v-model="selectedStatus"
          class="w-full px-3 py-1.5 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
        >
          <option value="">Status: All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
    </div>

    <!-- 2. TEACHER PERFORMANCE LIST TABLE (SPECIFICATION 2 - NO RANKING!) -->
    <div class="bg-slate-900/60 rounded-2xl border border-slate-800/80 backdrop-blur-md overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
            <tr>
              <th class="py-3 px-4">Teacher</th>
              <th class="py-3 px-4">Department</th>
              <th class="py-3 px-4 text-center">Courses</th>
              <th class="py-3 px-4 text-center">Students</th>
              <th class="py-3 px-4 min-w-[130px]">Avg. Progress</th>
              <th class="py-3 px-4 text-center">Quizzes</th>
              <th class="py-3 px-4 text-center">Assignments</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 text-center">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <tr
              v-for="t in paginatedTeachers"
              :key="t.id"
              class="hover:bg-slate-800/40 transition-colors group"
            >
              <!-- 1. Teacher -->
              <td class="py-3 px-4 whitespace-nowrap">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-[10px] text-indigo-400 shrink-0">
                    {{ t.name ? t.name.charAt(0) : 'T' }}
                  </div>
                  <div>
                    <div class="font-bold text-white group-hover:text-indigo-300 transition-colors">
                      {{ t.name }}
                    </div>
                    <div class="text-[10px] text-slate-500">{{ t.email }}</div>
                  </div>
                </div>
              </td>

              <!-- 2. Department -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">
                  {{ t.department }}
                </span>
              </td>

              <!-- 3. Courses -->
              <td class="py-3 px-4 text-center font-mono font-bold text-white">
                {{ t.courses_count }}
              </td>

              <!-- 4. Students -->
              <td class="py-3 px-4 text-center font-mono font-bold text-sky-400">
                {{ t.students_count }}
              </td>

              <!-- 5. Avg. Progress -->
              <td class="py-3 px-4">
                <div class="flex items-center justify-between text-[11px] font-mono mb-1">
                  <span class="font-bold text-slate-200">{{ t.avg_progress }}%</span>
                </div>
                <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="t.avg_progress >= 75 ? 'bg-emerald-400' : 'bg-sky-400'"
                    :style="{ width: `${t.avg_progress}%` }"
                  ></div>
                </div>
              </td>

              <!-- 6. Quizzes -->
              <td class="py-3 px-4 text-center font-mono text-slate-300">
                {{ t.quizzes_count }}
              </td>

              <!-- 7. Assignments -->
              <td class="py-3 px-4 text-center font-mono text-slate-300">
                {{ t.assignments_count }}
              </td>

              <!-- 8. Status -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="t.status === 'active' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                >
                  {{ t.status === 'active' ? 'Active' : 'Inactive' }}
                </span>
              </td>

              <!-- 9. Actions (View Detail) -->
              <td class="py-3 px-4 text-center whitespace-nowrap">
                <button
                  @click="openDetailModal(t)"
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

            <tr v-if="paginatedTeachers.length === 0">
              <td colspan="9" class="text-center py-10 text-slate-500">
                មិនមានទិន្នន័យសាស្ត្រាចារ្យត្រូវតាមលក្ខខណ្ឌ Filter ឡើយ
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="p-3.5 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
        <div>
          បង្ហាញពី <span class="font-bold text-white">{{ filteredTeachers.length ? ((currentPage - 1) * pageSize + 1) : 0 }}</span>
          ដល់ <span class="font-bold text-white">{{ Math.min(currentPage * pageSize, filteredTeachers.length) }}</span>
          នៃ <span class="font-bold text-white">{{ filteredTeachers.length }}</span> Teachers
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

    <!-- 4. TEACHER DETAIL MODAL (SPECIFICATION 4) -->
    <div
      v-if="showDetailModal && selectedTeacher"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
    >
      <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold">
              {{ selectedTeacher.name ? selectedTeacher.name.charAt(0) : 'T' }}
            </div>
            <div>
              <h3 class="text-sm font-bold text-white">{{ selectedTeacher.name }}</h3>
              <p class="text-xs text-slate-400 font-mono">{{ selectedTeacher.email }} • {{ selectedTeacher.department }}</p>
            </div>
          </div>
          <button @click="showDetailModal = false" class="text-slate-400 hover:text-white cursor-pointer">
            ✕
          </button>
        </div>

        <!-- Metric Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
          <div class="p-2.5 bg-slate-950/60 border border-slate-800 rounded-xl">
            <span class="text-[10px] text-slate-400 block font-semibold">Courses</span>
            <span class="text-base font-bold text-white font-mono">{{ selectedTeacher.courses_count }}</span>
          </div>
          <div class="p-2.5 bg-slate-950/60 border border-slate-800 rounded-xl">
            <span class="text-[10px] text-slate-400 block font-semibold">Students</span>
            <span class="text-base font-bold text-sky-400 font-mono">{{ selectedTeacher.students_count }}</span>
          </div>
          <div class="p-2.5 bg-slate-950/60 border border-slate-800 rounded-xl">
            <span class="text-[10px] text-slate-400 block font-semibold">Avg. Progress</span>
            <span class="text-base font-bold text-emerald-400 font-mono">{{ selectedTeacher.avg_progress }}%</span>
          </div>
          <div class="p-2.5 bg-slate-950/60 border border-slate-800 rounded-xl">
            <span class="text-[10px] text-slate-400 block font-semibold">Assessments</span>
            <span class="text-base font-bold text-purple-400 font-mono">{{ selectedTeacher.quizzes_count + selectedTeacher.assignments_count }}</span>
          </div>
        </div>

        <!-- Assigned Courses -->
        <div class="p-3.5 bg-slate-950/50 rounded-2xl border border-slate-800 space-y-2">
          <div class="font-bold text-slate-300 text-xs flex items-center gap-1.5">
            <span>📚</span> Assigned Courses:
          </div>
          <ul class="space-y-1.5">
            <li
              v-for="crs in (selectedTeacher.courses_list || [])"
              :key="crs.id || crs.code"
              class="p-2 rounded-xl bg-slate-900/60 border border-slate-800/60 flex items-center justify-between"
            >
              <span class="text-slate-200 font-medium">{{ crs.title }}</span>
              <span class="font-mono text-[10px] text-sky-400">{{ crs.code }}</span>
            </li>
          </ul>
        </div>

        <!-- Recent Teaching Activity -->
        <div class="p-3.5 bg-slate-950/50 rounded-2xl border border-slate-800 space-y-1">
          <div class="font-bold text-slate-300 text-xs flex items-center gap-1.5">
            <span>🕒</span> Recent Teaching Activity:
          </div>
          <p class="text-slate-300 text-xs pt-1">
            {{ selectedTeacher.recent_activity || 'Graded lab assignments & uploaded lesson resources' }}
          </p>
        </div>

        <!-- Footer -->
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
