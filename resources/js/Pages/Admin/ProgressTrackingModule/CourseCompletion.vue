<script setup lang="ts">
import { ref, computed } from 'vue'

export interface CourseCompletionRecord {
  id: number
  course: string
  code: string
  major: string
  instructor: string
  total_students: number
  completed: number
  in_progress: number
  not_started: number
  completion_rate: number
}

const props = withDefaults(defineProps<{
  courses?: CourseCompletionRecord[]
}>(), {
  courses: () => [
    {
      id: 1,
      course: 'Web Development',
      code: 'CRS-IT-WD101',
      major: 'Information Technology',
      instructor: 'Ms. Dara',
      total_students: 410,
      completed: 120,
      in_progress: 250,
      not_started: 40,
      completion_rate: 29,
    },
    {
      id: 2,
      course: 'C Programming Basics',
      code: 'CRS-IT-CP101',
      major: 'Information Technology',
      instructor: 'Mr. Sophea',
      total_students: 520,
      completed: 380,
      in_progress: 110,
      not_started: 30,
      completion_rate: 73,
    },
    {
      id: 3,
      course: 'Tourism Basics',
      code: 'CRS-TRM-TB101',
      major: 'Tourism',
      instructor: 'Mr. Long',
      total_students: 350,
      completed: 265,
      in_progress: 65,
      not_started: 20,
      completion_rate: 76,
    },
    {
      id: 4,
      course: 'English Grammar',
      code: 'CRS-ENG-EG101',
      major: 'English Literature',
      instructor: 'Ms. Srey',
      total_students: 600,
      completed: 490,
      in_progress: 80,
      not_started: 30,
      completion_rate: 82,
    },
    {
      id: 5,
      course: 'Plant Science',
      code: 'CRS-AG-PS101',
      major: 'Agriculture',
      instructor: 'Mr. Vuthy',
      total_students: 480,
      completed: 340,
      in_progress: 105,
      not_started: 35,
      completion_rate: 71,
    },
    {
      id: 6,
      course: 'Social Work 101',
      code: 'CRS-SW-SW101',
      major: 'Social Work',
      instructor: 'Mr. Rithy',
      total_students: 450,
      completed: 320,
      in_progress: 95,
      not_started: 35,
      completion_rate: 71,
    },
  ]
})

const emit = defineEmits<{
  (e: 'downloadReport', courseId: number): void
  (e: 'notifyIncomplete', courseId: number): void
}>()

const searchQuery = ref('')
const selectedMajor = ref('all')

const filteredCourses = computed(() => {
  return props.courses.filter(c => {
    const q = searchQuery.value.toLowerCase().trim()
    const matchesSearch = !q ||
      c.course.toLowerCase().includes(q) ||
      c.code.toLowerCase().includes(q) ||
      c.instructor.toLowerCase().includes(q)

    const matchesMajor = selectedMajor.value === 'all' || c.major === selectedMajor.value

    return matchesSearch && matchesMajor
  })
})

const stats = computed(() => {
  const all = props.courses
  const totalCourses = all.length
  const totalStudents = all.reduce((acc, c) => acc + c.total_students, 0)
  const totalCompleted = all.reduce((acc, c) => acc + c.completed, 0)
  const totalInProgress = all.reduce((acc, c) => acc + c.in_progress, 0)
  const totalNotStarted = all.reduce((acc, c) => acc + c.not_started, 0)
  const avgCompletionRate = totalStudents > 0 ? Math.round((totalCompleted / totalStudents) * 100) : 0

  return { totalCourses, totalStudents, totalCompleted, totalInProgress, totalNotStarted, avgCompletionRate }
})

const getMajorBadgeClass = (major: string) => {
  switch (major) {
    case 'Information Technology':
      return 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30'
    case 'Tourism':
      return 'bg-amber-500/15 text-amber-300 border-amber-500/30'
    case 'English Literature':
      return 'bg-purple-500/15 text-purple-300 border-purple-500/30'
    case 'Agriculture':
      return 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'
    case 'Social Work':
      return 'bg-rose-500/15 text-rose-300 border-rose-500/30'
    default:
      return 'bg-slate-500/15 text-slate-300 border-slate-500/30'
  }
}
</script>

<template>
  <div class="space-y-4 font-sans text-xs">
    <!-- Top Summary Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-3.5 backdrop-blur-xl">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-slate-400">Total Enrolled</span>
          <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
        </div>
        <div class="text-xl font-black text-white mt-1">{{ stats.totalStudents.toLocaleString() }}</div>
        <div class="text-[10px] text-slate-400 mt-0.5">Across {{ stats.totalCourses }} courses</div>
      </div>

      <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-3.5 backdrop-blur-xl">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-emerald-400">Completed (Total)</span>
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
        </div>
        <div class="text-xl font-black text-emerald-300 mt-1">{{ stats.totalCompleted.toLocaleString() }}</div>
        <div class="text-[10px] text-slate-400 mt-0.5">Graduated cohort</div>
      </div>

      <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-3.5 backdrop-blur-xl">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-sky-400">In Progress</span>
          <span class="w-2 h-2 rounded-full bg-sky-500"></span>
        </div>
        <div class="text-xl font-black text-sky-300 mt-1">{{ stats.totalInProgress.toLocaleString() }}</div>
        <div class="text-[10px] text-slate-400 mt-0.5">Actively studying</div>
      </div>

      <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-3.5 backdrop-blur-xl">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-purple-400">Avg Completion Rate</span>
          <span class="w-2 h-2 rounded-full bg-purple-500"></span>
        </div>
        <div class="text-xl font-black text-purple-300 mt-1">{{ stats.avgCompletionRate }}%</div>
        <div class="text-[10px] text-slate-400 mt-0.5">Thesis cohort benchmark</div>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-slate-900/80 border border-slate-800/90 rounded-2xl p-3 flex flex-col sm:flex-row items-center justify-between gap-3 backdrop-blur-xl">
      <div class="relative w-full sm:w-80">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search course title, code, instructor..."
          class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3.5 py-2 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
        />
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto">
        <select
          v-model="selectedMajor"
          class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
        >
          <option value="all">Major: All 5 Majors</option>
          <option value="Information Technology">Information Technology</option>
          <option value="Tourism">Tourism</option>
          <option value="English Literature">English Literature</option>
          <option value="Agriculture">Agriculture</option>
          <option value="Social Work">Social Work</option>
        </select>

        <button
          @click="emit('downloadReport', 0)"
          class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-xl font-bold flex items-center gap-1.5 transition-all shrink-0"
        >
          <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span>Export All</span>
        </button>
      </div>
    </div>

    <!-- Course Completion Canonical Table -->
    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-xl shadow-xl">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-800 bg-slate-800/50 text-[11px] font-bold text-slate-300 uppercase tracking-wider whitespace-nowrap">
            <th class="py-3 px-4">Course</th>
            <th class="py-3 px-4">Major</th>
            <th class="py-3 px-4">Total Students</th>
            <th class="py-3 px-4">Completed</th>
            <th class="py-3 px-4">In Progress</th>
            <th class="py-3 px-4">Not Started</th>
            <th class="py-3 px-4">Completion Rate</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60 font-medium">
          <tr
            v-for="c in filteredCourses"
            :key="c.id"
            class="hover:bg-slate-800/40 transition-colors group"
          >
            <!-- 1. Course -->
            <td class="py-3.5 px-4">
              <div class="font-bold text-white text-sm group-hover:text-indigo-300 transition-colors">{{ c.course }}</div>
              <div class="text-[10px] text-slate-400 flex items-center gap-2 mt-0.5">
                <span class="font-mono text-indigo-400">{{ c.code }}</span>
                <span>·</span>
                <span>{{ c.instructor }}</span>
              </div>
            </td>

            <!-- 2. Major -->
            <td class="py-3.5 px-4 whitespace-nowrap">
              <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold border', getMajorBadgeClass(c.major)]">
                {{ c.major }}
              </span>
            </td>

            <!-- 3. Total Students -->
            <td class="py-3.5 px-4 whitespace-nowrap">
              <span class="font-extrabold text-white text-sm">{{ c.total_students.toLocaleString() }}</span>
              <span class="text-[10px] text-slate-400 ml-1">enrolled</span>
            </td>

            <!-- 4. Completed -->
            <td class="py-3.5 px-4 whitespace-nowrap">
              <span class="px-2 py-0.5 rounded-md bg-emerald-500/15 text-emerald-300 font-bold border border-emerald-500/30">
                {{ c.completed.toLocaleString() }}
              </span>
            </td>

            <!-- 5. In Progress -->
            <td class="py-3.5 px-4 whitespace-nowrap">
              <span class="px-2 py-0.5 rounded-md bg-sky-500/15 text-sky-300 font-bold border border-sky-500/30">
                {{ c.in_progress.toLocaleString() }}
              </span>
            </td>

            <!-- 6. Not Started -->
            <td class="py-3.5 px-4 whitespace-nowrap">
              <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 font-bold border border-slate-700">
                {{ c.not_started.toLocaleString() }}
              </span>
            </td>

            <!-- 7. Completion Rate -->
            <td class="py-3.5 px-4 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <div class="w-24 h-2 bg-slate-950 rounded-full overflow-hidden border border-slate-800 p-0.5">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="c.completion_rate >= 75 ? 'bg-emerald-400' : (c.completion_rate >= 50 ? 'bg-cyan-400' : 'bg-amber-400')"
                    :style="{ width: `${c.completion_rate}%` }"
                  ></div>
                </div>
                <span class="font-black text-white text-xs">{{ c.completion_rate }}%</span>
              </div>
            </td>

            <!-- 8. Actions -->
            <td class="py-3.5 px-4 text-right whitespace-nowrap">
              <button
                @click="emit('downloadReport', c.id)"
                class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-300 hover:text-white border border-slate-700 rounded-lg text-[11px] font-bold transition-all mr-1.5"
              >
                Report
              </button>
              <button
                @click="emit('notifyIncomplete', c.id)"
                class="px-2.5 py-1 bg-purple-600/20 hover:bg-purple-600/40 text-purple-300 border border-purple-500/30 rounded-lg text-[11px] font-bold transition-all"
              >
                Remind
              </button>
            </td>
          </tr>

          <tr v-if="filteredCourses.length === 0">
            <td colspan="8" class="text-center py-8 text-slate-400">
              No courses found matching your search.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
