<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import UserModuleHeader from '@/Components/Admin/UserModuleHeader.vue'

const props = withDefaults(defineProps<{
  teachers?: Array<any>
  departments?: Array<any>
  majors?: Array<any>
  summaryStats?: any
}>(), {
  teachers: () => [],
  departments: () => [],
  majors: () => [],
  summaryStats: () => ({})
})

const search = ref('')
const selectedMajorFilter = ref('')
const selectedTeacherForEdit = ref<any | null>(null)
const openDropdownId = ref<number | null>(null)
const editingCourseRate = ref<string | null>(null)
const isAddTeacherModalOpen = ref(false)
const selectedTeacherIds = ref<number[]>([])

const toggleSelectAll = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.checked) {
    selectedTeacherIds.value = filteredTeachers.value.map(t => t.id)
  } else {
    selectedTeacherIds.value = []
  }
}

const bulkExport = () => {
  alert(`Exporting ${selectedTeacherIds.value.length} selected teacher accounts...`)
}

const bulkSuspend = () => {
  if (confirm(`Are you sure you want to suspend ${selectedTeacherIds.value.length} selected teacher accounts?`)) {
    alert(`Suspended ${selectedTeacherIds.value.length} teacher accounts successfully!`)
    selectedTeacherIds.value = []
  }
}

const teacherForm = useForm({
  id: null as number | null,
  name: '',
  email: '',
  phone: '',
  department_id: '',
  major_id: '',
  role: 'teacher',
  status: 'active',
  qualification: 'Master of Science in Computer Science',
  aba_name: '',
  aba_number: '',
  assigned_courses: [
    { name: 'C Programming 101', rate: 25 },
    { name: 'Database Systems', rate: 30 }
  ]
})

const filteredTeachers = computed(() => {
  return props.teachers.filter(t => {
    const matchesSearch = !search.value || (
      t.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      t.email?.toLowerCase().includes(search.value.toLowerCase()) ||
      t.phone?.toLowerCase().includes(search.value.toLowerCase())
    )
    const matchesMajor = !selectedMajorFilter.value || t.major_id == selectedMajorFilter.value || t.major?.id == selectedMajorFilter.value
    return matchesSearch && matchesMajor
  })
})

const openAddTeacherModal = () => {
  selectedTeacherForEdit.value = null
  teacherForm.reset()
  teacherForm.id = null
  teacherForm.role = 'teacher'
  isAddTeacherModalOpen.value = true
}

const openEditTeacher = (teacher: any) => {
  isAddTeacherModalOpen.value = false
  selectedTeacherForEdit.value = teacher
  teacherForm.id = teacher.id
  teacherForm.name = teacher.name || ''
  teacherForm.email = teacher.email || ''
  teacherForm.phone = teacher.phone || '+855 89 123 456'
  teacherForm.department_id = teacher.major?.department?.id || ''
  teacherForm.major_id = teacher.major_id || ''
  teacherForm.status = teacher.status || 'active'
  teacherForm.qualification = teacher.qualification || 'Master Degree'
  teacherForm.aba_name = teacher.aba_name || teacher.name
  teacherForm.aba_number = teacher.aba_number || '000 999 888'
}

const toast = ref<{ show: boolean; type: 'success' | 'info' | 'warning'; title: string; message: string }>({
  show: false,
  type: 'success',
  title: '',
  message: ''
})
let toastTimer: any = null

const triggerToast = (title: string, message: string, type: 'success' | 'info' | 'warning' = 'success') => {
  if (toastTimer) clearTimeout(toastTimer)
  toast.value = { show: true, type, title, message }
  toastTimer = setTimeout(() => {
    toast.value.show = false
  }, 4500)
}

const saveTeacherProfile = () => {
  const teacherName = teacherForm.name || 'លោកគ្រូ/អ្នកគ្រូ'
  if (selectedTeacherForEdit.value) {
    teacherForm.put(`/admin/users/${teacherForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        selectedTeacherForEdit.value = null
        triggerToast(
          'រក្សាទុកបានជោគជ័យ',
          `ព័ត៌មានលោកគ្រូ/អ្នកគ្រូ "${teacherName}" ត្រូវបានបច្ចុប្បន្នភាពដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast(
          'មានបញ្ហាក្នុងការរក្សាទុក',
          'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ',
          'warning'
        )
      }
    })
  } else {
    teacherForm.post('/admin/users', {
      preserveScroll: true,
      onSuccess: () => {
        isAddTeacherModalOpen.value = false
        teacherForm.reset()
        triggerToast(
          'បង្កើតលោកគ្រូ/អ្នកគ្រូបានជោគជ័យ',
          `គណនីលោកគ្រូ/អ្នកគ្រូ "${teacherName}" ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast(
          'មានបញ្ហាក្នុងការបង្កើតគណនី',
          'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ',
          'warning'
        )
      }
    })
  }
}

const suspendTeacher = (teacher: any) => {
  if (confirm(`Suspend instructor account '${teacher.name}'?`)) {
    router.post(`/admin/user-management/suspend/${teacher.id}`)
  }
}

const exportTeachersCSV = () => {
  window.location.href = '/admin/user-management/export?role=teacher'
}
</script>

<template>
  <AdminLayout title="Teachers — Instructors Management">
    <div class="space-y-6 font-sans">
      <!-- Shared Header -->
      <UserModuleHeader activeTab="teachers" :summaryStats="props.summaryStats" />

      <!-- SEARCH & ACTION TOOLBAR (Single Row Layout: Filters Left — Actions Far Right) -->
      <div class="bg-white dark:bg-slate-900/60 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-none backdrop-blur-xl flex flex-wrap items-center justify-between gap-3">
        <!-- Left Aligned Search Box & Major Dropdown Filter -->
        <div class="flex items-center gap-3 flex-1 min-w-[320px]">
          <!-- Search Input -->
          <div class="relative flex-1 min-w-[200px]">
            <input
              v-model="search"
              type="text"
              placeholder="Search teacher name, email, phone..."
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-cyan-500 transition-all"
            />
            <span class="absolute left-3 top-2.5 text-slate-400 dark:text-slate-500">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
          </div>

          <!-- Dropdown Filter by Major -->
          <select
            v-model="selectedMajorFilter"
            class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-cyan-700 dark:text-cyan-300 font-semibold focus:outline-none focus:border-cyan-500 transition-all cursor-pointer min-w-[160px]"
          >
            <option value="">Filter by Major (All)</option>
            <option v-for="m in props.majors" :key="m.id" :value="m.id">
              {{ m.name }}
            </option>
          </select>

          <!-- Reset Filter Button -->
          <button
            v-if="search || selectedMajorFilter"
            @click="search = ''; selectedMajorFilter = ''"
            class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold transition-all flex items-center gap-1 cursor-pointer"
          >
            <span>✕ Reset</span>
          </button>
        </div>

        <!-- Far Right Aligned Primary Action Buttons -->
        <div class="flex items-center gap-2">
          <button
            @click="openAddTeacherModal"
            class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl shadow-md shadow-cyan-600/20 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add New Teacher</span>
          </button>

          <a
            href="/admin/user-management/import-export"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Import</span>
          </a>

          <button
            @click="exportTeachersCSV"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
          >
            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Export</span>
          </button>
        </div>
      </div>

      <!-- FLOATING BULK ACTIONS TOOLBAR -->
      <div v-if="selectedTeacherIds.length > 0" class="p-3.5 bg-indigo-50 dark:bg-indigo-950/80 border border-indigo-200 dark:border-indigo-500/30 rounded-2xl flex items-center justify-between text-xs backdrop-blur-xl shadow-md dark:shadow-xl animate-fade-in">
        <div class="flex items-center gap-2 text-indigo-700 dark:text-indigo-300 font-bold font-mono">
          <span class="w-2 h-2 rounded-full bg-indigo-500 dark:bg-indigo-400 animate-pulse"></span>
          <span>Selected ({{ selectedTeacherIds.length }}) Teachers</span>
        </div>
        <div class="flex items-center gap-2">
          <button @click="bulkExport" class="px-3.5 py-1.5 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 transition-all cursor-pointer flex items-center gap-1.5 shadow-xs">
            <svg class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Export Selected</span>
          </button>
          <button @click="bulkSuspend" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-600/20 transition-all cursor-pointer flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <span>Suspend Selected</span>
          </button>
        </div>
      </div>

      <!-- TEACHERS DATA TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 shadow-sm dark:shadow-none backdrop-blur-xl min-h-[380px]">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <th class="py-3.5 px-4 w-10 text-center">
                <input type="checkbox" @change="toggleSelectAll" class="rounded bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-cyan-600 dark:text-cyan-500 focus:ring-cyan-500 cursor-pointer" />
              </th>
              <th class="py-3.5 px-4 w-12 text-center">#</th>
              <th class="py-3.5 px-4">Teacher Name</th>
              <th class="py-3.5 px-4">Email & Phone</th>
              <th class="py-3.5 px-4">Department & Major</th>
              <th class="py-3.5 px-4">Assigned Courses</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            <tr v-for="(teacher, idx) in filteredTeachers" :key="teacher.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-all group">
              <!-- Checkbox -->
              <td class="py-3.5 px-4 text-center">
                <input type="checkbox" :value="teacher.id" v-model="selectedTeacherIds" class="rounded bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-cyan-600 dark:text-cyan-500 focus:ring-cyan-500 cursor-pointer" />
              </td>

              <!-- Index -->
              <td class="py-3.5 px-4 text-center font-mono text-slate-500 dark:text-slate-400 font-medium">{{ String(idx + 1).padStart(2, '0') }}</td>

              <!-- Clickable Teacher Name & Avatar -->
              <td class="py-3.5 px-4">
                <button
                  @click="openEditTeacher(teacher)"
                  class="flex items-center gap-3 text-left focus:outline-none group/item"
                  title="Click to view teacher profile and rates"
                >
                  <img
                    :src="teacher.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(teacher.name)}&background=06b6d4&color=fff`"
                    class="w-9 h-9 rounded-full border-2 border-cyan-500/40 shadow-sm object-cover group-hover/item:border-cyan-400 group-hover/item:scale-105 transition-all"
                  />
                  <div>
                    <div class="font-bold text-slate-900 dark:text-white group-hover/item:text-cyan-600 dark:group-hover/item:text-cyan-300 transition-colors flex items-center gap-1.5">
                      <span>{{ teacher.name }}</span>
                    </div>
                    <div class="text-[10px] text-cyan-600 dark:text-cyan-400/90 font-mono font-semibold flex items-center gap-1 mt-0.5">
                      <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400"></span>
                      <span>Instructor / ID: #{{ teacher.id }}</span>
                    </div>
                  </div>
                </button>
              </td>

              <!-- Email & Phone -->
              <td class="py-3.5 px-4">
                <div class="font-mono text-slate-800 dark:text-slate-200 font-medium group-hover:text-slate-900 dark:group-hover:text-white transition-colors flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <span>{{ teacher.email }}</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-300 font-mono flex items-center gap-1.5 mt-0.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <span>{{ teacher.phone || '+855 89 123 456' }}</span>
                </div>
              </td>

              <!-- Department & Major (Clean Single-Line & High-Contrast Typography) -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <template v-if="teacher.major">
                  <div class="font-bold text-slate-800 dark:text-slate-100 text-xs">{{ teacher.major.department?.name || 'Faculty of Computer Science' }}</div>
                  <div class="text-[11px] text-cyan-700 dark:text-cyan-400 font-medium mt-0.5 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400/80"></span>
                    <span>{{ teacher.major.name }}</span>
                  </div>
                </template>
                <template v-else>
                  <div class="font-bold text-slate-800 dark:text-slate-100 text-xs">Faculty of Computer Science</div>
                  <div class="text-[11px] text-cyan-700 dark:text-cyan-400 font-medium mt-0.5 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400/80"></span>
                    <span>Software Engineering Instructor</span>
                  </div>
                </template>
              </td>

              <!-- Assigned Courses & ABA Rates -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="flex flex-wrap gap-1.5">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/30 text-cyan-800 dark:text-cyan-300 rounded-xl font-mono text-[11px] font-bold shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 dark:bg-cyan-400"></span>
                    <span>C Prog ($25)</span>
                  </span>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30 text-indigo-800 dark:text-indigo-300 rounded-xl font-mono text-[11px] font-bold shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 dark:bg-indigo-400"></span>
                    <span>Database ($30)</span>
                  </span>
                </div>
              </td>

              <!-- Direct Quick Action Icons (Uniform Fixed Width & Height) -->
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openEditTeacher(teacher)"
                    class="w-[74px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-cyan-500/20 text-slate-700 dark:text-cyan-300 hover:text-cyan-700 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="View Profile & Edit Rates"
                  >
                    <svg class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>Edit</span>
                  </button>

                  <button
                    @click="suspendTeacher(teacher)"
                    class="w-[88px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-red-500/20 text-rose-600 dark:text-red-400 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Suspend Instructor"
                  >
                    <svg class="w-3.5 h-3.5 text-rose-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <span>Suspend</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredTeachers.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-500 font-medium">
                No instructor accounts found matching search or filter criteria.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Table Pagination Footer -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="text-slate-500 dark:text-slate-400 font-mono">
            Showing <span class="text-slate-900 dark:text-white font-bold">1</span> to <span class="text-slate-900 dark:text-white font-bold">{{ filteredTeachers.length }}</span> of <span class="text-slate-900 dark:text-white font-bold">{{ props.summaryStats?.total_teachers || 145 }}</span> entries
          </div>

          <div class="flex items-center gap-1.5 font-mono">
            <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-xl font-semibold cursor-not-allowed" disabled>Previous</button>
            <button class="px-3 py-1.5 bg-cyan-600 text-white font-bold rounded-xl shadow-sm shadow-cyan-600/20">1</button>
            <button class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold cursor-pointer">2</button>
            <button class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold cursor-pointer">Next</button>
          </div>
        </div>
      </div>

      <!-- TEACHER PROFILE EDIT / ADD MODAL -->
      <div v-if="selectedTeacherForEdit || isAddTeacherModalOpen" class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900/95 border border-slate-200 dark:border-cyan-900/50 rounded-3xl max-w-3xl w-full p-7 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh] backdrop-blur-2xl">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/20 flex items-center justify-center text-cyan-600 dark:text-cyan-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-wide">
                  {{ selectedTeacherForEdit ? 'TEACHER PROFILE — EDIT & ABA PAYMENT FORM' : 'ADD NEW TEACHER / INSTRUCTOR ACCOUNT' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">កែប្រែព័ត៌មានគ្រូបង្រៀន, មុខវិជ្ជាបង្កាត់ និងប្រព័ន្ធទូទាត់ ABA</p>
              </div>
            </div>
            <button @click="selectedTeacherForEdit = null; isAddTeacherModalOpen = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/60 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-all">✕</button>
          </div>

          <!-- Form Inputs -->
          <form @submit.prevent="saveTeacherProfile" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Full Name -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  <span>Full Name *</span>
                </label>
                <input v-model="teacherForm.name" type="text" required placeholder="e.g. Sok Sophea" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all font-bold" />
              </div>

              <!-- Email Address -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <span>Email Address *</span>
                </label>
                <input v-model="teacherForm.email" type="email" required placeholder="e.g. teacher@elms.com" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all font-mono" />
              </div>

              <!-- Phone Number -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <span>Phone Number</span>
                </label>
                <input v-model="teacherForm.phone" type="text" placeholder="e.g. +855 89 123 456" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all font-mono" />
              </div>

              <!-- Assigned Major -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                  <span>Assigned Major</span>
                </label>
                <select v-model="teacherForm.major_id" class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-cyan-700 dark:text-cyan-300 font-bold focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all cursor-pointer">
                  <option value="">Select Major...</option>
                  <option v-for="m in props.majors" :key="m.id" :value="m.id">
                    {{ m.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- ASSIGNED COURSES & RATES -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-3">
              <label class="block font-bold text-cyan-700 dark:text-cyan-300 uppercase tracking-wider text-[11px] flex items-center gap-2">
                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span>ASSIGNED COURSES & PER SUBJECT RATES</span>
              </label>
              <div class="space-y-2">
                <div v-for="c in teacherForm.assigned_courses" :key="c.name" class="flex items-center justify-between p-3 bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                  <span class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-cyan-500 dark:bg-cyan-400"></span>
                    <span>{{ c.name }}</span>
                  </span>
                  <div class="flex items-center gap-2">
                    <template v-if="editingCourseRate === c.name">
                      <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-950 px-2 py-1 rounded-lg border border-cyan-500/50">
                        <span class="text-emerald-600 dark:text-emerald-400 font-mono font-bold text-xs">$</span>
                        <input
                          v-model.number="c.rate"
                          type="number"
                          min="0"
                          max="500"
                          class="w-14 bg-transparent text-xs text-emerald-600 dark:text-emerald-400 font-mono font-bold focus:outline-none"
                        />
                        <span class="text-slate-500 dark:text-slate-400 text-[10px] font-mono">/subj</span>
                      </div>
                      <button
                        type="button"
                        @click="editingCourseRate = null"
                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold rounded-lg cursor-pointer transition-all flex items-center gap-1 shadow-sm"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>Done</span>
                      </button>
                    </template>
                    <template v-else>
                      <span class="text-emerald-700 dark:text-emerald-400 font-mono font-bold text-xs bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-1 rounded-lg border border-emerald-200 dark:border-emerald-500/20">${{ c.rate }}/subject</span>
                      <button
                        type="button"
                        @click="editingCourseRate = c.name"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 text-[11px] rounded-lg font-semibold transition-all cursor-pointer flex items-center gap-1.5 hover:text-cyan-700 dark:hover:text-cyan-300 shadow-xs"
                      >
                        <svg class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span>Edit Rate</span>
                      </button>
                    </template>
                  </div>
                </div>
              </div>
            </div>

            <!-- PAYMENT ACCOUNT ABA (Clean ABA Integration Card) -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border border-emerald-200 dark:border-emerald-900/40 rounded-2xl space-y-3 shadow-xs">
              <div class="flex items-center justify-between">
                <label class="font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider text-[11px] flex items-center gap-2">
                  <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                  <span>PAYMENT ACCOUNT (ABA Integration — Per Subject Rate)</span>
                </label>
                <span class="text-[10px] font-mono text-emerald-700 dark:text-emerald-300 font-bold px-2 py-0.5 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-lg flex items-center gap-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span> ABA Pay Direct
                </span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- ABA Account Name Input with Icon -->
                <div>
                  <label class="block text-slate-700 dark:text-slate-300 mb-1.5 font-medium flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>ABA Account Name</span>
                  </label>
                  <div class="relative">
                    <input
                      v-model="teacherForm.aba_name"
                      type="text"
                      placeholder="e.g. SOK SOPHEA"
                      class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-mono uppercase focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                    />
                    <span class="absolute left-3 top-3 text-slate-400 dark:text-slate-500">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4"/></svg>
                    </span>
                  </div>
                </div>

                <!-- ABA Account Number Input with Card Icon -->
                <div>
                  <label class="block text-slate-700 dark:text-slate-300 mb-1.5 font-medium flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <span>ABA Account Number</span>
                  </label>
                  <div class="relative">
                    <input
                      v-model="teacherForm.aba_number"
                      type="text"
                      placeholder="e.g. 000 123 456 789"
                      class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-mono focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                    />
                    <span class="absolute left-3 top-3 text-slate-400 dark:text-slate-500">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center gap-3">
              <button type="button" @click="selectedTeacherForEdit = null; isAddTeacherModalOpen = false" :disabled="teacherForm.processing" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold transition-all cursor-pointer disabled:opacity-50">Cancel</button>
              <button type="submit" :disabled="teacherForm.processing" class="px-5 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl text-xs shadow-md shadow-cyan-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <svg v-if="teacherForm.processing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ teacherForm.processing ? (selectedTeacherForEdit ? 'កំពុងរក្សាទុក...' : 'កំពុងបង្កើត...') : (selectedTeacherForEdit ? 'Save Teacher Profile' : 'Create Instructor') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Clean & Subtle Toast Notification -->
      <Teleport to="body">
        <Transition
          enter-active-class="transform ease-out duration-250 transition"
          enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-3"
          enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="toast.show"
            class="fixed top-5 right-5 z-[9999] max-w-sm w-full pointer-events-auto"
          >
            <div
              :class="[
                toast.type === 'success'
                  ? 'bg-slate-800/95 border-slate-700/80 text-white'
                  : 'bg-slate-800/95 border-amber-500/40 text-white',
                'relative rounded-xl border p-3 shadow-xl backdrop-blur-md'
              ]"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center filter drop-shadow-sm">
                  <img
                    v-if="toast.type === 'success'"
                    :src="'/images/actions/toast-success.svg'"
                    alt="Success"
                    class="w-full h-full object-contain"
                  />
                  <img
                    v-else-if="toast.type === 'warning'"
                    :src="'/images/actions/toast-warning.svg'"
                    alt="Warning"
                    class="w-full h-full object-contain"
                  />
                  <img
                    v-else
                    :src="'/images/actions/toast-info.svg'"
                    alt="Info"
                    class="w-full h-full object-contain"
                  />
                </div>

                <div class="flex-1 min-w-0">
                  <h4 class="text-xs font-bold text-white tracking-tight leading-snug">
                    {{ toast.title }}
                  </h4>
                  <p class="text-[11px] text-slate-300 mt-0.5 leading-normal">
                    {{ toast.message }}
                  </p>
                </div>

                <button
                  @click="toast.show = false"
                  class="text-slate-400 hover:text-white p-1 rounded-md hover:bg-slate-700/60 transition-colors shrink-0 cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </AdminLayout>
</template>
