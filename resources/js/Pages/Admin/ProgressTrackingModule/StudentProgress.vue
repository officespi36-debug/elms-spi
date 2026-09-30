<script setup lang="ts">
import { ref, computed } from 'vue'

export interface StudentProgressRecord {
  id: string
  name: string
  avatar?: string
  major: string
  course: string
  progress: number
  quiz_avg: number
  last_activity: string
  status: 'In Progress' | 'Completed' | 'At-Risk' | 'Not Started'
  email: string
  learning_time: string
  modules_completed: number
  modules_total: number
}

const props = withDefaults(defineProps<{
  students?: StudentProgressRecord[]
}>(), {
  students: () => [
    {
      id: 'STU-2024-001',
      name: 'Dara',
      avatar: '👨‍💻',
      major: 'Information Technology',
      course: 'Web Development',
      progress: 68,
      quiz_avg: 72,
      last_activity: '2 hours ago',
      status: 'In Progress',
      email: 'dara.chan@student.elms.edu',
      learning_time: '28h 30m',
      modules_completed: 4,
      modules_total: 6,
    },
    {
      id: 'STU-2024-002',
      name: 'Sok Chanra',
      avatar: '👩‍🎓',
      major: 'Tourism',
      course: 'Tourism Basics',
      progress: 85,
      quiz_avg: 88,
      last_activity: '30 mins ago',
      status: 'In Progress',
      email: 'chanra.sok@student.elms.edu',
      learning_time: '34h 15m',
      modules_completed: 5,
      modules_total: 6,
    },
    {
      id: 'STU-2024-003',
      name: 'Long Vichida',
      avatar: '👩‍🏫',
      major: 'English Literature',
      course: 'English Grammar',
      progress: 100,
      quiz_avg: 94,
      last_activity: 'Yesterday',
      status: 'Completed',
      email: 'vichida.long@student.elms.edu',
      learning_time: '42h 00m',
      modules_completed: 6,
      modules_total: 6,
    },
    {
      id: 'STU-2024-004',
      name: 'Pov Sreynich',
      avatar: '🌱',
      major: 'Agriculture',
      course: 'Plant Science',
      progress: 45,
      quiz_avg: 60,
      last_activity: '3 days ago',
      status: 'In Progress',
      email: 'sreynich.pov@student.elms.edu',
      learning_time: '18h 45m',
      modules_completed: 2,
      modules_total: 5,
    },
    {
      id: 'STU-2024-005',
      name: 'Kosal Rithy',
      avatar: '🤝',
      major: 'Social Work',
      course: 'Social Work 101',
      progress: 22,
      quiz_avg: 48,
      last_activity: '5 days ago',
      status: 'At-Risk',
      email: 'rithy.kosal@student.elms.edu',
      learning_time: '7h 20m',
      modules_completed: 1,
      modules_total: 5,
    },
    {
      id: 'STU-2024-006',
      name: 'Chea Vannak',
      avatar: '💻',
      major: 'Information Technology',
      course: 'C Programming Basics',
      progress: 92,
      quiz_avg: 90,
      last_activity: 'Today at 10:15 AM',
      status: 'In Progress',
      email: 'vannak.chea@student.elms.edu',
      learning_time: '39h 10m',
      modules_completed: 5,
      modules_total: 6,
    },
    {
      id: 'STU-2024-007',
      name: 'Meas Bopha',
      avatar: '🌟',
      major: 'Social Work',
      course: 'Community Development',
      progress: 100,
      quiz_avg: 96,
      last_activity: '1 day ago',
      status: 'Completed',
      email: 'bopha.meas@student.elms.edu',
      learning_time: '45h 30m',
      modules_completed: 5,
      modules_total: 5,
    },
    {
      id: 'STU-2024-008',
      name: 'Samnang Piseth',
      avatar: '🏖️',
      major: 'Tourism',
      course: 'Hospitality Management',
      progress: 15,
      quiz_avg: 42,
      last_activity: '6 days ago',
      status: 'At-Risk',
      email: 'piseth.samnang@student.elms.edu',
      learning_time: '5h 10m',
      modules_completed: 1,
      modules_total: 6,
    },
    {
      id: 'STU-2024-009',
      name: 'Heng Sovann',
      avatar: '🌾',
      major: 'Agriculture',
      course: 'Soil Studies',
      progress: 0,
      quiz_avg: 0,
      last_activity: 'Never',
      status: 'Not Started',
      email: 'sovann.heng@student.elms.edu',
      learning_time: '0h 00m',
      modules_completed: 0,
      modules_total: 5,
    },
  ]
})

const emit = defineEmits<{
  (e: 'sendMessage', studentId: string): void
  (e: 'addFeedback', studentId: string): void
  (e: 'resetProgress', studentId: string): void
}>()

const searchQuery = ref('')
const selectedMajor = ref('all')
const selectedStatus = ref('all')
const selectedStudentDetail = ref<StudentProgressRecord | null>(null)

const filteredStudents = computed(() => {
  return props.students.filter(s => {
    const q = searchQuery.value.toLowerCase().trim()
    const matchesSearch = !q || 
      s.name.toLowerCase().includes(q) || 
      s.id.toLowerCase().includes(q) || 
      s.course.toLowerCase().includes(q) ||
      s.email.toLowerCase().includes(q)

    const matchesMajor = selectedMajor.value === 'all' || s.major === selectedMajor.value
    const matchesStatus = selectedStatus.value === 'all' || s.status === selectedStatus.value

    return matchesSearch && matchesMajor && matchesStatus
  })
})

const stats = computed(() => {
  const all = props.students
  const total = all.length
  const completed = all.filter(s => s.status === 'Completed').length
  const inProgress = all.filter(s => s.status === 'In Progress').length
  const atRisk = all.filter(s => s.status === 'At-Risk').length
  const avgProgress = total > 0 ? Math.round(all.reduce((acc, s) => acc + s.progress, 0) / total) : 0

  return { total, completed, inProgress, atRisk, avgProgress }
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

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'Completed':
      return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 ring-1 ring-emerald-500/30'
    case 'In Progress':
      return 'bg-sky-500/20 text-sky-300 border-sky-500/40'
    case 'At-Risk':
      return 'bg-rose-500/20 text-rose-300 border-rose-500/40 ring-1 ring-rose-500/40 animate-pulse'
    case 'Not Started':
      return 'bg-slate-800 text-slate-400 border-slate-700'
    default:
      return 'bg-slate-800 text-slate-300 border-slate-700'
  }
}

const getScoreColorClass = (score: number) => {
  if (score >= 80) return 'text-emerald-400 font-extrabold'
  if (score >= 60) return 'text-sky-300 font-bold'
  if (score > 0) return 'text-amber-400 font-bold'
  return 'text-slate-500'
}
</script>

<template>
  <div class="space-y-4 font-sans text-xs">
    <!-- Top KPI Cards for Student Progress -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-3.5 backdrop-blur-xl">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-slate-400">Total Tracked</span>
          <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
        </div>
        <div class="text-xl font-black text-white mt-1">{{ stats.total }} <span class="text-xs font-normal text-slate-400">Students</span></div>
        <div class="text-[10px] text-slate-400 mt-0.5">Across 5 thesis majors</div>
      </div>

      <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-3.5 backdrop-blur-xl">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-sky-400">In Progress</span>
          <span class="w-2 h-2 rounded-full bg-sky-500"></span>
        </div>
        <div class="text-xl font-black text-sky-300 mt-1">{{ stats.inProgress }}</div>
        <div class="text-[10px] text-slate-400 mt-0.5">Actively learning</div>
      </div>

      <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-3.5 backdrop-blur-xl">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-emerald-400">Completed</span>
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
        </div>
        <div class="text-xl font-black text-emerald-300 mt-1">{{ stats.completed }}</div>
        <div class="text-[10px] text-slate-400 mt-0.5">100% course finished</div>
      </div>

      <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-3.5 backdrop-blur-xl">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-semibold text-rose-400">At-Risk Students</span>
          <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
        </div>
        <div class="text-xl font-black text-rose-300 mt-1">{{ stats.atRisk }}</div>
        <div class="text-[10px] text-rose-400/80 mt-0.5 font-medium">Require intervention</div>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-slate-900/80 border border-slate-800/90 rounded-2xl p-3 flex flex-col sm:flex-row items-center justify-between gap-3 backdrop-blur-xl">
      <!-- Search Input -->
      <div class="relative w-full sm:w-80">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search student (e.g. Dara), ID, or course..."
          class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3.5 py-2 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
        />
      </div>

      <!-- Filters (Major & Status) -->
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

        <select
          v-model="selectedStatus"
          class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
        >
          <option value="all">Status: All</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
          <option value="At-Risk">At-Risk</option>
          <option value="Not Started">Not Started</option>
        </select>
      </div>
    </div>

    <!-- Student Progress Canonical Table -->
    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-xl shadow-xl">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="border-b border-slate-800 bg-slate-800/50 text-[11px] font-bold text-slate-300 uppercase tracking-wider whitespace-nowrap">
            <th class="py-3 px-4">Student</th>
            <th class="py-3 px-4">Major</th>
            <th class="py-3 px-4">Course</th>
            <th class="py-3 px-4">Progress %</th>
            <th class="py-3 px-4">Quiz Average</th>
            <th class="py-3 px-4">Last Activity</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60 font-medium">
          <tr
            v-for="s in filteredStudents"
            :key="s.id"
            class="hover:bg-slate-800/40 transition-colors group cursor-pointer"
            @click="selectedStudentDetail = s"
          >
            <!-- 1. Student -->
            <td class="py-3 px-4">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-sm shrink-0">
                  {{ s.avatar || '👤' }}
                </div>
                <div>
                  <div class="font-bold text-white group-hover:text-indigo-300 transition-colors">{{ s.name }}</div>
                  <div class="text-[10px] text-slate-400 font-mono">{{ s.id }}</div>
                </div>
              </div>
            </td>

            <!-- 2. Major -->
            <td class="py-3 px-4 whitespace-nowrap">
              <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold border', getMajorBadgeClass(s.major)]">
                {{ s.major }}
              </span>
            </td>

            <!-- 3. Course -->
            <td class="py-3 px-4">
              <div class="font-semibold text-slate-200">{{ s.course }}</div>
              <div class="text-[10px] text-slate-400">{{ s.modules_completed }}/{{ s.modules_total }} modules done</div>
            </td>

            <!-- 4. Progress % -->
            <td class="py-3 px-4 whitespace-nowrap">
              <div class="flex items-center gap-2">
                <div class="w-20 h-2 bg-slate-950 rounded-full overflow-hidden border border-slate-800 p-0.5">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="s.progress === 100 ? 'bg-emerald-400' : (s.progress < 30 ? 'bg-rose-500' : 'bg-indigo-400')"
                    :style="{ width: `${s.progress}%` }"
                  ></div>
                </div>
                <span class="font-extrabold text-white text-[11px]">{{ s.progress }}%</span>
              </div>
            </td>

            <!-- 5. Quiz Average -->
            <td class="py-3 px-4 whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span :class="getScoreColorClass(s.quiz_avg)">{{ s.quiz_avg }}%</span>
              </div>
            </td>

            <!-- 6. Last Activity -->
            <td class="py-3 px-4 whitespace-nowrap text-slate-300">
              {{ s.last_activity }}
            </td>

            <!-- 7. Status -->
            <td class="py-3 px-4 whitespace-nowrap">
              <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border', getStatusBadgeClass(s.status)]">
                {{ s.status }}
              </span>
            </td>

            <!-- 8. Actions -->
            <td class="py-3 px-4 text-right whitespace-nowrap" @click.stop>
              <button
                @click="selectedStudentDetail = s"
                class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-indigo-300 hover:text-white border border-slate-700 rounded-lg text-[11px] font-bold transition-all mr-1.5"
              >
                Inspect
              </button>
              <button
                @click="emit('sendMessage', s.id)"
                class="px-2 py-1 bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-300 border border-indigo-500/30 rounded-lg text-[11px] font-bold transition-all"
                title="Send direct notification"
              >
                Message
              </button>
            </td>
          </tr>

          <tr v-if="filteredStudents.length === 0">
            <td colspan="8" class="text-center py-8 text-slate-400">
              No students found matching your filters.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Student Detail Inspection Drawer / Modal -->
    <div
      v-if="selectedStudentDetail"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in"
      @click.self="selectedStudentDetail = null"
    >
      <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-5 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-xl">
              {{ selectedStudentDetail.avatar || '👤' }}
            </div>
            <div>
              <h3 class="text-sm font-black text-white">{{ selectedStudentDetail.name }}</h3>
              <p class="text-xs text-slate-400 font-mono">{{ selectedStudentDetail.id }} · {{ selectedStudentDetail.email }}</p>
            </div>
          </div>
          <button
            @click="selectedStudentDetail = null"
            class="text-slate-400 hover:text-white text-base px-2 py-1 rounded-lg hover:bg-slate-800"
          >
            ✕
          </button>
        </div>

        <div class="grid grid-cols-2 gap-3 text-xs">
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
            <span class="text-[10px] text-slate-400 block font-semibold">Degree Major</span>
            <span class="text-xs font-bold text-cyan-300">{{ selectedStudentDetail.major }}</span>
          </div>
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
            <span class="text-[10px] text-slate-400 block font-semibold">Current Course</span>
            <span class="text-xs font-bold text-white">{{ selectedStudentDetail.course }}</span>
          </div>
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
            <span class="text-[10px] text-slate-400 block font-semibold">Progress</span>
            <span class="text-xs font-black text-emerald-400">{{ selectedStudentDetail.progress }}%</span>
          </div>
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
            <span class="text-[10px] text-slate-400 block font-semibold">Quiz Average</span>
            <span class="text-xs font-black text-amber-300">{{ selectedStudentDetail.quiz_avg }}%</span>
          </div>
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
            <span class="text-[10px] text-slate-400 block font-semibold">Total Study Time</span>
            <span class="text-xs font-bold text-purple-300">{{ selectedStudentDetail.learning_time }}</span>
          </div>
          <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80">
            <span class="text-[10px] text-slate-400 block font-semibold">Last Active</span>
            <span class="text-xs font-bold text-slate-200">{{ selectedStudentDetail.last_activity }}</span>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
          <button
            @click="emit('sendMessage', selectedStudentDetail.id); selectedStudentDetail = null"
            class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-all"
          >
            Send Notification
          </button>
          <button
            @click="selectedStudentDetail = null"
            class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition-all"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
