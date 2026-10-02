<script setup lang="ts">
import { ref } from 'vue'

export interface AcademicYearItem {
  id: number
  year: string
  start_date: string
  end_date: string
  is_active: boolean
  total_students: number
  total_courses: number
}

export interface SemesterItem {
  id: number
  name: string
  academic_year: string
  start_date: string
  end_date: string
  status: 'Active' | 'Upcoming' | 'Completed'
  courses_count: number
}

const emit = defineEmits<{
  (e: 'notify', msg: string): void
  (e: 'save', data: any): void
}>()

// 1. Academic Years (Only ONE can be active)
const academicYears = ref<AcademicYearItem[]>([
  {
    id: 1,
    year: '2025–2026',
    start_date: '2025-10-01',
    end_date: '2026-07-31',
    is_active: false,
    total_students: 2150,
    total_courses: 48,
  },
  {
    id: 2,
    year: '2026–2027',
    start_date: '2026-10-01',
    end_date: '2027-07-31',
    is_active: true,
    total_students: 2458,
    total_courses: 56,
  },
  {
    id: 3,
    year: '2027–2028',
    start_date: '2027-10-01',
    end_date: '2028-07-31',
    is_active: false,
    total_students: 0,
    total_courses: 0,
  }
])

// 2. Semesters
const semesters = ref<SemesterItem[]>([
  {
    id: 1,
    name: 'Semester 1 (ឆមាសទី១)',
    academic_year: '2026–2027',
    start_date: '2026-10-01',
    end_date: '2027-02-28',
    status: 'Active',
    courses_count: 32,
  },
  {
    id: 2,
    name: 'Semester 2 (ឆមាសទី២)',
    academic_year: '2026–2027',
    start_date: '2027-03-15',
    end_date: '2027-07-31',
    status: 'Upcoming',
    courses_count: 24,
  },
  {
    id: 3,
    name: 'Summer / Special Semester (Optional)',
    academic_year: '2026–2027',
    start_date: '2027-08-01',
    end_date: '2027-09-15',
    status: 'Completed',
    courses_count: 6,
  }
])

// 3. Academic Structure Pipeline (5 SPI Majors)
const spiMajors = [
  { code: 'IT', name: 'Information Technology', faculty: 'Science & Technology', students: 520, active_courses: 14 },
  { code: 'AG', name: 'Agriculture', faculty: 'Agricultural Sciences', students: 380, active_courses: 11 },
  { code: 'ENG', name: 'English Literature', faculty: 'Languages & Humanities', students: 410, active_courses: 10 },
  { code: 'TM', name: 'Tourism Management', faculty: 'Tourism & Hospitality', students: 480, active_courses: 12 },
  { code: 'SW', name: 'Social Work', faculty: 'Social Sciences', students: 320, active_courses: 9 },
]

// 4. Enrollment Rules
const restrictMajorEnrollment = ref(true)
const enforcePrerequisites = ref(true)
const maxCoursesPerSemester = ref(6)
const allowCrossFacultyElective = ref(false)

// Edit Modal State
const showYearModal = ref(false)
const editingYear = ref<AcademicYearItem | null>(null)
const formYear = ref({
  id: 0,
  year: '',
  start_date: '',
  end_date: '',
})

// Handlers
function activateYear(target: AcademicYearItem) {
  // CRITICAL RULE: ONLY ONE ACTIVE ACADEMIC YEAR AT A TIME
  academicYears.value.forEach(y => {
    y.is_active = (y.id === target.id)
  })
  emit('notify', `✅ Academic Year "${target.year}" is now set as the ONE ACTIVE academic year.`)
}

function openEditYear(item: AcademicYearItem) {
  editingYear.value = item
  formYear.value = {
    id: item.id,
    year: item.year,
    start_date: item.start_date,
    end_date: item.end_date,
  }
  showYearModal.value = true
}

function saveYearEdit() {
  if (editingYear.value) {
    const idx = academicYears.value.findIndex(y => y.id === editingYear.value!.id)
    if (idx !== -1) {
      academicYears.value[idx].year = formYear.value.year
      academicYears.value[idx].start_date = formYear.value.start_date
      academicYears.value[idx].end_date = formYear.value.end_date
      emit('notify', `Academic Year ${formYear.value.year} updated successfully.`)
    }
  }
  showYearModal.value = false
}

function saveAllSettings() {
  emit('save', {
    active_academic_year: academicYears.value.find(y => y.is_active)?.year,
    semesters: semesters.value,
    enrollment_rules: {
      restrict_major: restrictMajorEnrollment.value,
      enforce_prerequisites: enforcePrerequisites.value,
      max_courses: maxCoursesPerSemester.value,
      allow_cross_elective: allowCrossFacultyElective.value,
    }
  })
  emit('notify', '✅ Academic Settings saved! System structure synchronized with SPI 5 Majors.')
}
</script>

<template>
  <div class="space-y-6 text-xs text-slate-200">
    <!-- Header Card -->
    <div class="bg-slate-800/90 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-2">
      <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
        <div class="flex items-center gap-3">
          <div class="p-2.5 rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/30 text-base">
            🎓
          </div>
          <div>
            <h2 class="text-base font-bold text-white">Academic Settings (ការកំណត់ការសិក្សា)</h2>
            <p class="text-[11px] text-slate-400">
              កំណត់រចនាសម្ព័ន្ធ Academic Year → Semester → Major → Subject → Course សម្រាប់ SPI ELMS
            </p>
          </div>
        </div>
        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40">
          Curriculum Engine
        </span>
      </div>
      <p class="text-[11px] text-slate-400 pt-1">
        ℹ️ គ្រប់គ្រងឆ្នាំសិក្សាដែលមានសុពលភាព, ឆមាសសិក្សា, និងលក្ខខណ្ឌចុះឈ្មោះមុខវិជ្ជា (Enrollment Rules) តាម 5 ជំនាញផ្លូវការ។
      </p>
    </div>

    <!-- 1. Academic Year Management -->
    <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
      <div class="flex items-center justify-between border-b border-slate-700/60 pb-2">
        <h3 class="text-sm font-bold text-purple-300 flex items-center gap-2">
          <span>📅</span>
          <span>1. Academic Year (ឆ្នាំសិក្សា — ត្រូវមាន Active តែមួយ)</span>
        </h3>
        <span class="text-[10px] text-emerald-400 font-mono bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
          Only ONE Active Year Rule Enforced ✓
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-900/80 border-b border-slate-700 text-slate-400 uppercase text-[10px] font-semibold">
              <th class="p-3 pl-4">Academic Year</th>
              <th class="p-3">Start Date</th>
              <th class="p-3">End Date</th>
              <th class="p-3">Students Enrolled</th>
              <th class="p-3">Courses</th>
              <th class="p-3">Status</th>
              <th class="p-3 pr-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/50">
            <tr
              v-for="year in academicYears"
              :key="year.id"
              :class="year.is_active ? 'bg-purple-950/20' : 'hover:bg-slate-700/30'"
              class="transition"
            >
              <td class="p-3 pl-4 font-bold text-white flex items-center gap-2">
                <span v-if="year.is_active" class="text-emerald-400 text-xs" title="Currently Active Year">⭐</span>
                <span>{{ year.year }}</span>
              </td>
              <td class="p-3 font-mono text-slate-300">{{ year.start_date }}</td>
              <td class="p-3 font-mono text-slate-300">{{ year.end_date }}</td>
              <td class="p-3 font-mono text-slate-200">{{ year.total_students.toLocaleString() }} students</td>
              <td class="p-3 font-mono text-slate-200">{{ year.total_courses }} courses</td>
              <td class="p-3">
                <span
                  v-if="year.is_active"
                  class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 inline-flex items-center gap-1"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                  <span>Active (បច្ចុប្បន្ន)</span>
                </span>
                <span
                  v-else
                  class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-slate-700/40 text-slate-400 border border-slate-600"
                >
                  Inactive
                </span>
              </td>
              <td class="p-3 pr-4 text-right space-x-1.5 whitespace-nowrap">
                <button
                  v-if="!year.is_active"
                  @click="activateYear(year)"
                  class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition font-bold"
                  title="Make this the active academic year"
                >
                  Activate
                </button>
                <button
                  @click="openEditYear(year)"
                  class="px-2.5 py-1 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg transition"
                >
                  ✏️ Edit
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 2. Semester Management -->
    <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
      <h3 class="text-sm font-bold text-purple-300 flex items-center gap-2 border-b border-slate-700/60 pb-2">
        <span>📚</span>
        <span>2. Semester (ឆមាសសិក្សា)</span>
      </h3>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div
          v-for="sem in semesters"
          :key="sem.id"
          class="p-4 rounded-xl bg-slate-900 border border-slate-700/80 space-y-2 relative"
        >
          <div class="flex items-center justify-between">
            <span class="font-bold text-white text-xs">{{ sem.name }}</span>
            <span
              :class="[
                sem.status === 'Active' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' :
                sem.status === 'Upcoming' ? 'bg-sky-500/20 text-sky-300 border-sky-500/40' :
                'bg-slate-700/40 text-slate-400 border-slate-600'
              ]"
              class="px-2 py-0.5 rounded text-[10px] font-bold border"
            >
              {{ sem.status }}
            </span>
          </div>

          <div class="text-[11px] text-slate-400 space-y-1">
            <div class="flex justify-between">
              <span>Academic Year:</span>
              <strong class="text-slate-200 font-mono">{{ sem.academic_year }}</strong>
            </div>
            <div class="flex justify-between">
              <span>Period:</span>
              <span class="font-mono text-slate-300">{{ sem.start_date }} → {{ sem.end_date }}</span>
            </div>
            <div class="flex justify-between">
              <span>Active Courses:</span>
              <strong class="text-purple-300">{{ sem.courses_count }} Courses</strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Academic Structure Connection (Visual Pipeline) -->
    <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
      <div class="flex items-center justify-between border-b border-slate-700/60 pb-2">
        <h3 class="text-sm font-bold text-purple-300 flex items-center gap-2">
          <span>🔗</span>
          <span>3. Academic Structure Connection (ការតភ្ជាប់រចនាសម្ព័ន្ធ)</span>
        </h3>
        <span class="text-[10px] text-slate-400">Synchronized with 5 SPI Majors</span>
      </div>

      <!-- Process Diagram Flow -->
      <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between gap-1 overflow-x-auto text-[11px] font-mono">
        <span class="px-2.5 py-1 bg-purple-900/40 text-purple-300 border border-purple-700 rounded-lg">Academic Year</span>
        <span class="text-slate-500">➔</span>
        <span class="px-2.5 py-1 bg-indigo-900/40 text-indigo-300 border border-indigo-700 rounded-lg">Semester</span>
        <span class="text-slate-500">➔</span>
        <span class="px-2.5 py-1 bg-cyan-900/40 text-cyan-300 border border-cyan-700 rounded-lg">5 Majors</span>
        <span class="text-slate-500">➔</span>
        <span class="px-2.5 py-1 bg-teal-900/40 text-teal-300 border border-teal-700 rounded-lg">Subject</span>
        <span class="text-slate-500">➔</span>
        <span class="px-2.5 py-1 bg-emerald-900/40 text-emerald-300 border border-emerald-700 rounded-lg">Course</span>
        <span class="text-slate-500">➔</span>
        <span class="px-2.5 py-1 bg-amber-900/40 text-amber-300 border border-amber-700 rounded-lg">Lesson</span>
        <span class="text-slate-500">➔</span>
        <span class="px-2.5 py-1 bg-rose-900/40 text-rose-300 border border-rose-700 rounded-lg">Quiz/Assign</span>
      </div>

      <!-- 5 Majors Grid Summary -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div
          v-for="major in spiMajors"
          :key="major.code"
          class="p-3 rounded-xl bg-slate-900 border border-slate-800 space-y-1.5"
        >
          <div class="flex items-center justify-between">
            <span class="font-mono text-[10px] font-bold text-indigo-400">#{{ major.code }}</span>
            <span class="text-[10px] text-emerald-400 font-bold">{{ major.students }} Students</span>
          </div>
          <p class="font-bold text-white text-xs truncate">{{ major.name }}</p>
          <div class="text-[10px] text-slate-400 truncate">{{ major.faculty }}</div>
          <div class="text-[10px] text-purple-300 font-mono">{{ major.active_courses }} Courses Active</div>
        </div>
      </div>
    </div>

    <!-- 4. Enrollment Rules -->
    <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
      <h3 class="text-sm font-bold text-purple-300 flex items-center gap-2 border-b border-slate-700/60 pb-2">
        <span>🛡️</span>
        <span>4. Enrollment Rules (លក្ខខណ្ឌចុះឈ្មោះរៀន)</span>
      </h3>
      <p class="text-slate-400 text-[11px]">
        កំណត់លក្ខខណ្ឌសម្រាប់ Student ក្នុងការចូលរៀនតាម Academic Year និង Major របស់ខ្លួន៖
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Restrict to Major -->
        <label class="p-3.5 rounded-xl bg-slate-900 border border-slate-700 flex items-center justify-between cursor-pointer">
          <div>
            <div class="font-bold text-white">Restrict Courses to Student's Major</div>
            <div class="text-[11px] text-slate-400">សិស្សអាចចូលរៀនបានតែ Course ដែលត្រូវនឹង Major របស់ខ្លួន</div>
          </div>
          <input type="checkbox" v-model="restrictMajorEnrollment" class="rounded border-slate-700 text-purple-600" />
        </label>

        <!-- Enforce Prerequisites -->
        <label class="p-3.5 rounded-xl bg-slate-900 border border-slate-700 flex items-center justify-between cursor-pointer">
          <div>
            <div class="font-bold text-white">Enforce Semester Prerequisites</div>
            <div class="text-[11px] text-slate-400">ត្រូវឆ្លងកាត់មុខវិជ្ជាគ្រឹះ Semester 1 មុនរៀន Semester 2</div>
          </div>
          <input type="checkbox" v-model="enforcePrerequisites" class="rounded border-slate-700 text-purple-600" />
        </label>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
        <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-700 space-y-1">
          <label class="font-bold text-white block">Maximum Courses per Semester (បន្ទុកអតិបរមា)</label>
          <div class="flex items-center gap-3">
            <input
              type="number"
              v-model="maxCoursesPerSemester"
              min="1"
              max="12"
              class="w-24 bg-slate-950 border border-slate-700 text-white rounded-lg p-2 font-mono"
            />
            <span class="text-slate-400 text-[11px]">Courses (មុខវិជ្ជាក្នុងមួយឆមាស)</span>
          </div>
        </div>

        <label class="p-3.5 rounded-xl bg-slate-900 border border-slate-700 flex items-center justify-between cursor-pointer">
          <div>
            <div class="font-bold text-white">Cross-Faculty Electives</div>
            <div class="text-[11px] text-slate-400">អនុញ្ញាតឱ្យរើសមុខវិជ្ជាក្រៅជំនាញ (Electives)</div>
          </div>
          <input type="checkbox" v-model="allowCrossFacultyElective" class="rounded border-slate-700 text-purple-600" />
        </label>
      </div>
    </div>

    <!-- 5. Save Changes Footer -->
    <div class="flex items-center justify-between bg-slate-900/90 border border-slate-700/70 p-4 rounded-2xl shadow-xl">
      <button
        @click="emit('notify', 'Reset to default academic settings.')"
        type="button"
        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl transition font-semibold"
      >
        Reset
      </button>

      <div class="flex items-center gap-2">
        <button
          @click="emit('notify', 'Changes cancelled.')"
          type="button"
          class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 rounded-xl transition"
        >
          Cancel
        </button>
        <button
          @click="saveAllSettings"
          type="button"
          class="px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-purple-500/20 transition flex items-center gap-2"
        >
          <span>Save Changes (រក្សាទុក)</span>
        </button>
      </div>
    </div>

    <!-- Edit Academic Year Modal -->
    <div v-if="showYearModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
        <h3 class="text-sm font-bold text-white border-b border-slate-800 pb-2">
          Edit Academic Year: {{ formYear.year }}
        </h3>
        <div class="space-y-3">
          <div>
            <label class="block text-slate-300 mb-1">Academic Year</label>
            <input v-model="formYear.year" type="text" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-white font-mono" />
          </div>
          <div>
            <label class="block text-slate-300 mb-1">Start Date</label>
            <input v-model="formYear.start_date" type="date" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-white font-mono" />
          </div>
          <div>
            <label class="block text-slate-300 mb-1">End Date</label>
            <input v-model="formYear.end_date" type="date" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-white font-mono" />
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
          <button @click="showYearModal = false" class="px-3 py-1.5 bg-slate-800 text-slate-300 rounded-lg">Cancel</button>
          <button @click="saveYearEdit" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-lg">Update</button>
        </div>
      </div>
    </div>
  </div>
</template>
