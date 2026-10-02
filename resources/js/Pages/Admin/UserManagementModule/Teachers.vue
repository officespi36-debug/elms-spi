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

// Search & Filter state
const search = ref('')
const selectedDepartment = ref('')
const selectedSpecialization = ref('')
const selectedStatus = ref('')
const selectedTeacherIds = ref<number[]>([])

// Modals state
const showCreateEditModal = ref(false)
const showProfileModal = ref(false)
const viewingTeacher = ref<any | null>(null)
const isEditMode = ref(false)
const showPassword = ref(false)

// Toast notification state
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

// Teacher Form (Strictly NO ABA or Hourly Rate fields)
const teacherForm = useForm({
  id: null as number | null,
  role: 'teacher',
  student_code: '', // Teacher ID (e.g. TEA-2026-001)
  name: '',
  name_kh: '',
  email: '',
  password: '',
  phone: '',
  major_id: '' as string | number,
  qualification: 'Master of Science / Academic Educator',
  expertise: 'Software Engineering & Cloud Computing',
  status: 'active'
})

// Common Specialization list for quick selection
const commonSpecializations = [
  'Software Engineering & Web Technologies',
  'Data Science & Artificial Intelligence',
  'Networking & Cybersecurity',
  'Social Work, Policy & Human Rights',
  'Community Development & Social Welfare',
  'Agronomy & Sustainable Agriculture',
  'Crop Science & Plant Protection',
  'Tourism Management & Eco-Tourism',
  'Hospitality & Hotel Operations',
  'English Linguistics & Applied Literature',
  'TESOL & Academic English Communication'
]

// Computed Filtered Teachers
const filteredTeachers = computed(() => {
  return props.teachers.filter(t => {
    // 1. Search Query
    const query = search.value.toLowerCase().trim()
    const matchesSearch = !query || (
      (t.name && t.name.toLowerCase().includes(query)) ||
      (t.name_kh && t.name_kh.toLowerCase().includes(query)) ||
      (t.email && t.email.toLowerCase().includes(query)) ||
      (t.phone && t.phone.toLowerCase().includes(query)) ||
      (t.student_code && t.student_code.toLowerCase().includes(query)) ||
      `#${t.id}`.includes(query)
    )

    // 2. Department Filter
    const teacherDeptId = t.major?.department_id || t.major?.department?.id
    const teacherDeptName = t.major?.department?.name || ''
    const matchesDept = !selectedDepartment.value ||
      teacherDeptId == selectedDepartment.value ||
      teacherDeptName.toLowerCase() === selectedDepartment.value.toLowerCase()

    // 3. Specialization Filter
    const matchesSpec = !selectedSpecialization.value ||
      (t.expertise && t.expertise.toLowerCase().includes(selectedSpecialization.value.toLowerCase()))

    // 4. Status Filter
    const currentStatus = (t.status || (t.is_active ? 'active' : 'inactive')).toLowerCase()
    const matchesStatus = !selectedStatus.value || currentStatus === selectedStatus.value.toLowerCase()

    return matchesSearch && matchesDept && matchesSpec && matchesStatus
  })
})

// Toggle Select All
const toggleSelectAll = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.checked) {
    selectedTeacherIds.value = filteredTeachers.value.map(t => t.id)
  } else {
    selectedTeacherIds.value = []
  }
}

// Open Add Teacher Modal
const openCreateModal = () => {
  isEditMode.value = false
  teacherForm.reset()
  teacherForm.id = null
  teacherForm.role = 'teacher'
  
  // Format: TEA-2026-XXX
  const nextNum = props.teachers.length + 1
  teacherForm.student_code = `TEA-2026-${String(nextNum).padStart(3, '0')}`
  teacherForm.name = ''
  teacherForm.name_kh = ''
  teacherForm.email = ''
  teacherForm.password = ''
  teacherForm.phone = ''
  teacherForm.major_id = props.majors[0]?.id || ''
  teacherForm.qualification = 'Master Degree'
  teacherForm.expertise = 'Software Engineering'
  teacherForm.status = 'active'
  showPassword.value = false

  showCreateEditModal.value = true
}

// Open Edit Teacher Modal
const openEditModal = (teacher: any) => {
  isEditMode.value = true
  teacherForm.reset()
  teacherForm.id = teacher.id
  teacherForm.role = 'teacher'
  teacherForm.student_code = teacher.student_code || `TEA-2026-${String(teacher.id).padStart(3, '0')}`
  teacherForm.name = teacher.name || ''
  teacherForm.name_kh = teacher.name_kh || ''
  teacherForm.email = teacher.email || ''
  teacherForm.password = ''
  teacherForm.phone = teacher.phone || ''
  teacherForm.major_id = teacher.major_id || (props.majors[0]?.id || '')
  teacherForm.qualification = teacher.qualification || 'Master Degree'
  teacherForm.expertise = teacher.expertise || 'Software Engineering'
  teacherForm.status = teacher.status || (teacher.is_active ? 'active' : 'inactive')
  showPassword.value = false

  if (showProfileModal.value) {
    showProfileModal.value = false
  }
  showCreateEditModal.value = true
}

// Open View Teacher Profile Modal
const openProfileModal = (teacher: any) => {
  viewingTeacher.value = teacher
  showProfileModal.value = true
}

// Helper: Calculate total students for a teacher
const getTeacherStudentCount = (teacher: any) => {
  if (!teacher.courses || teacher.courses.length === 0) return 0
  return teacher.courses.reduce((sum: number, c: any) => sum + (c.enrollments?.length || 0), 0)
}

// Helper: Calculate total quizzes for a teacher
const getTeacherQuizCount = (teacher: any) => {
  if (!teacher.courses || teacher.courses.length === 0) return 0
  return teacher.courses.reduce((sum: number, c: any) => sum + (c.quizzes?.length || 0), 0)
}

// Helper: Calculate total lessons for a teacher
const getTeacherLessonCount = (teacher: any) => {
  if (!teacher.courses || teacher.courses.length === 0) return 0
  return teacher.courses.reduce((sum: number, c: any) => sum + (c.lessons?.length || 0), 0)
}

// Save Teacher (Create or Update)
const saveTeacher = () => {
  const teacherName = teacherForm.name || 'លោកគ្រូ/អ្នកគ្រូ'

  if (isEditMode.value && teacherForm.id) {
    teacherForm.put(`/admin/users/${teacherForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        showCreateEditModal.value = false
        triggerToast(
          'កែសម្រួលបានជោគជ័យ (Saved)',
          `ព័ត៌មានលោកគ្រូ/អ្នកគ្រូ "${teacherName}" (${teacherForm.student_code}) ត្រូវបានកែសម្រួលដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast('មានបញ្ហាក្នុងការរក្សាទុក', 'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ', 'warning')
      }
    })
  } else {
    teacherForm.post('/admin/users', {
      preserveScroll: true,
      onSuccess: () => {
        showCreateEditModal.value = false
        teacherForm.reset()
        triggerToast(
          'បង្កើតលោកគ្រូ/អ្នកគ្រូជោគជ័យ (Created)',
          `គណនីលោកគ្រូ/អ្នកគ្រូ "${teacherName}" (${teacherForm.student_code}) ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast('មានបញ្ហាក្នុងការបង្កើត', 'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ', 'warning')
      }
    })
  }
}

// Toggle Activate / Disable Teacher (No permanent delete to preserve academic records)
const toggleActivateDisable = (teacher: any) => {
  const isActivating = teacher.status !== 'active'
  const actionText = isActivating ? 'Activate (បើកដំណើរការ)' : 'Disable / Inactive (ផ្អាកដំណើរការ)'

  if (confirm(`តើអ្នកពិតជាចង់ ${actionText} គណនីលោកគ្រូ/អ្នកគ្រូ "${teacher.name}" មែនទេ?`)) {
    router.post(`/admin/user-management/toggle-status/${teacher.id}`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast(
          isActivating ? 'បានបើកដំណើរការ (Activated)' : 'បានផ្អាកដំណើរការ (Disabled)',
          `គណនី "${teacher.name}" ត្រូវបានផ្លាស់ប្តូរទៅជា ${isActivating ? 'Active' : 'Inactive'} ដោយជោគជ័យ`
        )
        if (viewingTeacher.value && viewingTeacher.value.id === teacher.id) {
          viewingTeacher.value.status = isActivating ? 'active' : 'inactive'
          viewingTeacher.value.is_active = isActivating
        }
      },
      onError: () => {
        triggerToast('បរាជ័យ', 'មិនអាចផ្លាស់ប្តូរស្ថានភាពគណនីបានទេ', 'warning')
      }
    })
  }
}

// Bulk Suspend / Inactivate
const bulkSuspend = () => {
  if (selectedTeacherIds.value.length === 0) return
  if (confirm(`តើអ្នកពិតជាចង់ផ្អាក (${selectedTeacherIds.value.length}) គណនីគ្រូដែលបានជ្រើសរើសមែនទេ?`)) {
    router.post('/admin/user-management/bulk-action', {
      ids: selectedTeacherIds.value,
      action: 'suspend'
    }, {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast('ផ្អាកជោគជ័យ', `បានផ្អាកដំណើរការ (${selectedTeacherIds.value.length}) គណនីជោគជ័យ`)
        selectedTeacherIds.value = []
      }
    })
  }
}

// Bulk Activate
const bulkActivate = () => {
  if (selectedTeacherIds.value.length === 0) return
  if (confirm(`តើអ្នកពិតជាចង់បើកដំណើរការ (${selectedTeacherIds.value.length}) គណនីគ្រូដែលបានជ្រើសរើសមែនទេ?`)) {
    router.post('/admin/user-management/bulk-action', {
      ids: selectedTeacherIds.value,
      action: 'activate'
    }, {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast('បើកដំណើរការជោគជ័យ', `បានបើកដំណើរការ (${selectedTeacherIds.value.length}) គណនីជោគជ័យ`)
        selectedTeacherIds.value = []
      }
    })
  }
}

// Reset All Filters
const resetFilters = () => {
  search.value = ''
  selectedDepartment.value = ''
  selectedSpecialization.value = ''
  selectedStatus.value = ''
}
</script>

<template>
  <AdminLayout title="Teachers — Instructors Management">
    <div class="space-y-6 font-sans">
      <!-- Shared Header -->
      <UserModuleHeader activeTab="teachers" :summaryStats="props.summaryStats" />

      <!-- FILTER & TOOLBAR (Search, Department, Specialization, Status, Actions) -->
      <div class="bg-white dark:bg-slate-900/70 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm backdrop-blur-xl space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <!-- Left: Search Box -->
          <div class="relative flex-1 min-w-[260px]">
            <input
              v-model="search"
              type="text"
              placeholder="Search Teacher ID, Name, Email, Phone..."
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all"
            />
            <span class="absolute left-3 top-3 text-slate-400 dark:text-slate-500">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
          </div>

          <!-- Department Filter -->
          <div class="min-w-[170px]">
            <select
              v-model="selectedDepartment"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:border-cyan-500 transition-all cursor-pointer"
            >
              <option value="">All Departments</option>
              <option v-for="d in props.departments" :key="d.id" :value="d.id">
                {{ d.name }}
              </option>
            </select>
          </div>

          <!-- Specialization Filter -->
          <div class="min-w-[170px]">
            <input
              v-model="selectedSpecialization"
              type="text"
              placeholder="Specialization..."
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-cyan-500 transition-all"
            />
          </div>

          <!-- Status Filter -->
          <div class="min-w-[140px]">
            <select
              v-model="selectedStatus"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:border-cyan-500 transition-all cursor-pointer"
            >
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="pending">Pending</option>
            </select>
          </div>

          <!-- Reset Filter -->
          <button
            v-if="search || selectedDepartment || selectedSpecialization || selectedStatus"
            @click="resetFilters"
            class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
            title="Reset Filters"
          >
            <span>✕ Reset</span>
          </button>

          <!-- Primary: Add New Teacher -->
          <button
            @click="openCreateModal"
            class="px-4 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl shadow-md shadow-cyan-600/20 transition-all flex items-center gap-2 cursor-pointer ml-auto"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Add Teacher</span>
          </button>
        </div>
      </div>

      <!-- FLOATING BULK ACTIONS TOOLBAR -->
      <div v-if="selectedTeacherIds.length > 0" class="p-3.5 bg-cyan-50 dark:bg-cyan-950/80 border border-cyan-200 dark:border-cyan-500/30 rounded-2xl flex items-center justify-between text-xs backdrop-blur-xl shadow-md">
        <div class="flex items-center gap-2 text-cyan-800 dark:text-cyan-300 font-bold font-mono">
          <span class="w-2 h-2 rounded-full bg-cyan-500 animate-pulse"></span>
          <span>Selected ({{ selectedTeacherIds.length }}) Teachers</span>
        </div>
        <div class="flex items-center gap-2">
          <button @click="bulkActivate" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>Activate Selected</span>
          </button>
          <button @click="bulkSuspend" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <span>Disable Selected</span>
          </button>
        </div>
      </div>

      <!-- TEACHERS DATA TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 shadow-sm backdrop-blur-xl min-h-[380px]">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <th class="py-3.5 px-4 w-10 text-center">
                <input type="checkbox" @change="toggleSelectAll" class="rounded bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-cyan-600 focus:ring-cyan-500 cursor-pointer" />
              </th>
              <th class="py-3.5 px-4 w-12 text-center">#</th>
              <th class="py-3.5 px-4">Teacher ID</th>
              <th class="py-3.5 px-4">Name</th>
              <th class="py-3.5 px-4">Email / Phone</th>
              <th class="py-3.5 px-4">Department</th>
              <th class="py-3.5 px-4">Major</th>
              <th class="py-3.5 px-4">Courses</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            <tr v-for="(teacher, idx) in filteredTeachers" :key="teacher.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all group">
              <!-- Checkbox -->
              <td class="py-3.5 px-4 text-center">
                <input type="checkbox" :value="teacher.id" v-model="selectedTeacherIds" class="rounded bg-slate-50 dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-cyan-600 focus:ring-cyan-500 cursor-pointer" />
              </td>

              <!-- Index -->
              <td class="py-3.5 px-4 text-center font-mono text-slate-400 font-medium">{{ String(idx + 1).padStart(2, '0') }}</td>

              <!-- Teacher ID -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/20 text-cyan-800 dark:text-cyan-300 rounded-lg font-mono text-xs font-bold">
                  {{ teacher.student_code || `TEA-2026-${String(teacher.id).padStart(3, '0')}` }}
                </span>
              </td>

              <!-- Name & Avatar -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <button
                  @click="openProfileModal(teacher)"
                  class="flex items-center gap-3 text-left focus:outline-none group/name"
                  title="Click to view Teacher Profile"
                >
                  <img
                    :src="teacher.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(teacher.name)}&background=06b6d4&color=fff`"
                    class="w-8 h-8 rounded-full border border-cyan-500/30 object-cover group-hover/name:scale-105 transition-transform"
                  />
                  <div>
                    <div class="font-bold text-slate-900 dark:text-white group-hover/name:text-cyan-600 dark:group-hover/name:text-cyan-400 transition-colors">
                      {{ teacher.name }}
                    </div>
                    <div v-if="teacher.name_kh" class="text-[11px] text-slate-400 font-medium">
                      {{ teacher.name_kh }}
                    </div>
                  </div>
                </button>
              </td>

              <!-- Email / Phone -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="font-mono text-slate-700 dark:text-slate-300 text-xs">{{ teacher.email }}</div>
                <div class="text-[11px] text-slate-400 font-mono mt-0.5">{{ teacher.phone || '+855 89 123 456' }}</div>
              </td>

              <!-- Department -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="font-bold text-slate-800 dark:text-slate-200">
                  {{ teacher.major?.department?.name || 'Department of Computing' }}
                </div>
                <div class="text-[10px] text-slate-400 font-medium">
                  {{ teacher.major?.department?.faculty?.name || 'Faculty of Science' }}
                </div>
              </td>

              <!-- Major -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="font-bold text-cyan-700 dark:text-cyan-400">
                  {{ teacher.major?.name || 'Information Technology' }}
                </div>
                <div v-if="teacher.expertise" class="text-[10px] text-slate-400 font-medium max-w-[150px] truncate" :title="teacher.expertise">
                  {{ teacher.expertise }}
                </div>
              </td>

              <!-- Courses -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="flex items-center gap-1.5">
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-bold font-mono">
                    {{ teacher.teaching_summary?.assigned_courses ?? (teacher.courses ? teacher.courses.length : 0) }} Courses
                  </span>
                </div>
                <div v-if="teacher.courses && teacher.courses.length > 0" class="flex flex-wrap items-center gap-1 mt-1 max-w-[220px]">
                  <span
                    v-for="c in teacher.courses.slice(0, 2)"
                    :key="c.id"
                    class="inline-block px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded text-[9px] font-mono truncate max-w-[100px]"
                    :title="c.title"
                  >
                    {{ c.code || c.title }}
                  </span>
                  <span v-if="teacher.courses.length > 2" class="text-[9px] text-slate-400">
                    +{{ teacher.courses.length - 2 }}
                  </span>
                </div>
                <div v-else class="text-[10px] text-slate-400 italic mt-0.5">
                  0 Courses Assigned
                </div>
              </td>

              <!-- Status -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span
                  v-if="(teacher.status || 'active') === 'active'"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  Active
                </span>
                <span
                  v-else-if="teacher.status === 'pending'"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                  Pending
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                  Inactive
                </span>
              </td>

              <!-- Actions: View 👁, Edit ✏️, Activate/Disable 🔒 -->
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- View Profile Button -->
                  <button
                    @click="openProfileModal(teacher)"
                    class="h-7 px-2.5 inline-flex items-center gap-1 bg-slate-100 hover:bg-cyan-50 dark:bg-slate-800 dark:hover:bg-cyan-500/20 text-slate-700 dark:text-cyan-300 hover:text-cyan-700 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="View Teacher Profile"
                  >
                    <svg class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View</span>
                  </button>

                  <!-- Edit Button -->
                  <button
                    @click="openEditModal(teacher)"
                    class="h-7 px-2.5 inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Edit Teacher"
                  >
                    <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>Edit</span>
                  </button>

                  <!-- Activate / Disable Button -->
                  <button
                    @click="toggleActivateDisable(teacher)"
                    :class="[
                      (teacher.status || 'active') === 'active'
                        ? 'bg-rose-50 hover:bg-rose-100 dark:bg-rose-500/10 dark:hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/30'
                        : 'bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30',
                      'h-7 px-2.5 inline-flex items-center gap-1 border rounded-lg text-xs font-semibold transition-all cursor-pointer shadow-xs'
                    ]"
                    :title="(teacher.status || 'active') === 'active' ? 'Disable Teacher' : 'Activate Teacher'"
                  >
                    <svg v-if="(teacher.status || 'active') === 'active'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    <span>{{ (teacher.status || 'active') === 'active' ? 'Disable' : 'Activate' }}</span>
                  </button>
                </div>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-if="filteredTeachers.length === 0">
              <td colspan="10" class="py-12 text-center text-slate-400 font-medium">
                No instructor accounts found matching the search or filter criteria.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Table Pagination Footer -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="text-slate-500 dark:text-slate-400 font-mono">
            Showing <span class="text-slate-900 dark:text-white font-bold">1</span> to <span class="text-slate-900 dark:text-white font-bold">{{ filteredTeachers.length }}</span> of <span class="text-slate-900 dark:text-white font-bold">{{ props.teachers.length }}</span> instructors
          </div>

          <div class="flex items-center gap-1.5 font-mono">
            <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-xl font-semibold cursor-not-allowed" disabled>Previous</button>
            <button class="px-3 py-1.5 bg-cyan-600 text-white font-bold rounded-xl shadow-sm">1</button>
            <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-xl font-semibold cursor-not-allowed" disabled>Next</button>
          </div>
        </div>
      </div>

      <!-- ADD / EDIT TEACHER MODAL -->
      <div v-if="showCreateEditModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-cyan-900/40 rounded-3xl max-w-2xl w-full p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh]">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/20 flex items-center justify-center text-cyan-600 dark:text-cyan-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                  {{ isEditMode ? 'EDIT TEACHER PROFILE' : 'ADD NEW TEACHER' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{ isEditMode ? 'កែប្រែព័ត៌មានគណនីគ្រូបង្រៀន' : 'បង្កើតគណនីគ្រូបង្រៀនថ្មី និងភ្ជាប់ដេប៉ាតឺម៉ង់បង្រៀន' }}
                </p>
              </div>
            </div>
            <button @click="showCreateEditModal = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-all">✕</button>
          </div>

          <!-- Form Inputs -->
          <form @submit.prevent="saveTeacher" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Teacher ID -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Teacher ID *</label>
                <input
                  v-model="teacherForm.student_code"
                  type="text"
                  required
                  placeholder="e.g. TEA-2026-001"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-mono font-bold focus:outline-none focus:border-cyan-500"
                />
              </div>

              <!-- Full Name -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Full Name (English) *</label>
                <input
                  v-model="teacherForm.name"
                  type="text"
                  required
                  placeholder="e.g. Sok Sophea"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-bold focus:outline-none focus:border-cyan-500"
                />
              </div>

              <!-- Name (Khmer) -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Full Name (Khmer)</label>
                <input
                  v-model="teacherForm.name_kh"
                  type="text"
                  placeholder="ឧ. សុខ សុភា"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-cyan-500"
                />
              </div>

              <!-- Email -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Email Address *</label>
                <input
                  v-model="teacherForm.email"
                  type="email"
                  required
                  placeholder="e.g. sophea.sok@elms.edu"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-cyan-500"
                />
              </div>

              <!-- Phone Number -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                <input
                  v-model="teacherForm.phone"
                  type="text"
                  placeholder="e.g. +855 89 123 456"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-cyan-500"
                />
              </div>

              <!-- Password -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">
                  {{ isEditMode ? 'Password (ទុកទទេបើមិនប្តូរ)' : 'Password *' }}
                </label>
                <div class="relative">
                  <input
                    v-model="teacherForm.password"
                    :type="showPassword ? 'text' : 'password'"
                    :required="!isEditMode"
                    placeholder="Minimum 6 characters"
                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl pl-3.5 pr-10 py-2.5 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-cyan-500"
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"
                  >
                    {{ showPassword ? '🙈' : '👁️' }}
                  </button>
                </div>
              </div>

              <!-- Department / Major Association -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Department / Major *</label>
                <select
                  v-model="teacherForm.major_id"
                  required
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-bold focus:outline-none focus:border-cyan-500 cursor-pointer"
                >
                  <option value="">Select Department & Major...</option>
                  <option v-for="m in props.majors" :key="m.id" :value="m.id">
                    {{ m.department?.name ? `${m.department.name} — ` : '' }}{{ m.name }}
                  </option>
                </select>
              </div>

              <!-- Specialization -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Specialization / Expertise *</label>
                <input
                  v-model="teacherForm.expertise"
                  type="text"
                  required
                  list="specializations-list"
                  placeholder="e.g. Software Engineering"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-cyan-500"
                />
                <datalist id="specializations-list">
                  <option v-for="s in commonSpecializations" :key="s" :value="s" />
                </datalist>
              </div>
            </div>

            <!-- Qualification (Degree) -->
            <div>
              <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Academic Qualification / Degree</label>
              <input
                v-model="teacherForm.qualification"
                type="text"
                placeholder="e.g. Master of Science in Computer Science, Ph.D in Social Work"
                class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-cyan-500"
              />
            </div>

            <!-- Status Radio Selector -->
            <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Account Status</label>
              <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                  <input type="radio" value="active" v-model="teacherForm.status" class="text-emerald-600 focus:ring-emerald-500" />
                  <span>● Active (អាច Login បាន)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-400">
                  <input type="radio" value="inactive" v-model="teacherForm.status" class="text-slate-500 focus:ring-slate-400" />
                  <span>○ Inactive (ផ្អាកបណ្តោះអាសន្ន)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-amber-600 dark:text-amber-400">
                  <input type="radio" value="pending" v-model="teacherForm.status" class="text-amber-500 focus:ring-amber-400" />
                  <span>○ Pending (រង់ចាំការអនុម័ត)</span>
                </label>
              </div>
            </div>

            <!-- Notice on Course Management -->
            <div class="p-3 bg-cyan-50/70 dark:bg-cyan-950/40 rounded-xl border border-cyan-200 dark:border-cyan-800/60 text-slate-600 dark:text-slate-300 text-[11px] flex items-start gap-2">
              <span class="text-cyan-600 font-bold">ℹ️</span>
              <span>ការចាត់ចែងមុខវិជ្ជាបង្រៀន (Course Assignment) នឹងត្រូវធ្វើនៅ <strong>Course Management → Course Approval</strong> បន្ទាប់ពីគណនីគ្រូត្រូវបានបង្កើត។</span>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center gap-3">
              <button
                type="button"
                @click="showCreateEditModal = false"
                :disabled="teacherForm.processing"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold transition-all cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="teacherForm.processing"
                class="px-5 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl shadow-md shadow-cyan-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <span>{{ teacherForm.processing ? 'Saving...' : (isEditMode ? 'Update Teacher' : 'Create Teacher') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- VIEW TEACHER PROFILE MODAL -->
      <div v-if="showProfileModal && viewingTeacher" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-cyan-900/50 rounded-3xl max-w-3xl w-full p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh]">
          <!-- Profile Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-4">
              <img
                :src="viewingTeacher.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(viewingTeacher.name)}&background=06b6d4&color=fff`"
                class="w-14 h-14 rounded-2xl border-2 border-cyan-500/40 object-cover shadow-sm"
              />
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ viewingTeacher.name }}</h3>
                  <span
                    :class="[
                      (viewingTeacher.status || 'active') === 'active'
                        ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30'
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                      'px-2 py-0.5 rounded-full text-[10px] font-bold border'
                    ]"
                  >
                    {{ (viewingTeacher.status || 'active').toUpperCase() }}
                  </span>
                </div>
                <div class="text-xs font-mono text-cyan-600 dark:text-cyan-400 mt-0.5">
                  Teacher ID: {{ viewingTeacher.student_code || `TEA-2026-${String(viewingTeacher.id).padStart(3, '0')}` }}
                </div>
              </div>
            </div>
            <button @click="showProfileModal = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-all">✕</button>
          </div>

          <!-- Academic Teaching Summary KPI Cards (User Spec: Assigned Courses, Published Courses, Pending Courses, Total Students) -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <!-- 1. Assigned Courses -->
            <div class="p-3 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800">
              <span class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold">Assigned Courses</span>
              <div class="text-lg font-black text-slate-900 dark:text-white font-mono mt-0.5">
                {{ viewingTeacher.teaching_summary?.assigned_courses ?? (viewingTeacher.courses ? viewingTeacher.courses.length : 0) }}
              </div>
            </div>

            <!-- 2. Published Courses -->
            <div class="p-3 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800">
              <span class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold">Published Courses</span>
              <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">
                {{ viewingTeacher.teaching_summary?.published_courses ?? (viewingTeacher.courses ? viewingTeacher.courses.filter((c: any) => c.status === 'published').length : 0) }}
              </div>
            </div>

            <!-- 3. Pending Courses -->
            <div class="p-3 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800">
              <span class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold">Pending Courses</span>
              <div class="text-lg font-black text-amber-600 dark:text-amber-400 font-mono mt-0.5">
                {{ viewingTeacher.teaching_summary?.pending_courses ?? (viewingTeacher.courses ? viewingTeacher.courses.filter((c: any) => c.status !== 'published').length : 0) }}
              </div>
            </div>

            <!-- 4. Total Students -->
            <div class="p-3 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800">
              <span class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold">Total Students</span>
              <div class="text-lg font-black text-cyan-600 dark:text-cyan-400 font-mono mt-0.5">
                {{ viewingTeacher.teaching_summary?.total_students ?? getTeacherStudentCount(viewingTeacher) }}
              </div>
            </div>
          </div>

          <!-- Sub-summary: Lessons & Quizzes -->
          <div class="flex items-center justify-between px-3 py-2 bg-slate-50/70 dark:bg-slate-950/50 rounded-xl border border-slate-200/60 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 font-mono">
            <span>Lessons / Content: <strong class="text-slate-800 dark:text-slate-200">{{ getTeacherLessonCount(viewingTeacher) }} Lessons</strong></span>
            <span>•</span>
            <span>Quizzes & Tests: <strong class="text-slate-800 dark:text-slate-200">{{ getTeacherQuizCount(viewingTeacher) }} Quizzes</strong></span>
          </div>

          <!-- Basic Information & Department Matrix -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <!-- Left: Basic Info -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2.5">
              <h4 class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Basic Information</span>
              </h4>
              <div class="space-y-1.5 font-sans text-slate-700 dark:text-slate-300">
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Email:</span>
                  <span class="font-mono font-medium">{{ viewingTeacher.email }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Phone:</span>
                  <span class="font-mono font-medium">{{ viewingTeacher.phone || '+855 89 123 456' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Qualification:</span>
                  <span class="font-medium">{{ viewingTeacher.qualification || 'Master Degree' }}</span>
                </div>
                <div class="flex justify-between py-1">
                  <span class="text-slate-400">Member Since:</span>
                  <span class="font-mono font-medium">{{ new Date(viewingTeacher.created_at).toLocaleDateString() }}</span>
                </div>
              </div>
            </div>

            <!-- Right: Department & Specialization -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2.5">
              <h4 class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Department & Specialization</span>
              </h4>
              <div class="space-y-1.5 text-slate-700 dark:text-slate-300">
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Department:</span>
                  <span class="font-bold text-slate-900 dark:text-white">{{ viewingTeacher.major?.department?.name || 'Department of Computing' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Major:</span>
                  <span class="font-medium text-cyan-600 dark:text-cyan-400">{{ viewingTeacher.major?.name || 'Information Technology' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Specialization:</span>
                  <span class="font-semibold text-slate-900 dark:text-white">{{ viewingTeacher.expertise || 'Software Engineering' }}</span>
                </div>
                <div class="flex justify-between py-1">
                  <span class="text-slate-400">Role Authority:</span>
                  <span class="font-mono text-emerald-600 font-bold">Faculty Instructor</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Assigned Courses Table -->
          <div class="space-y-2">
            <h4 class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px] flex items-center justify-between">
              <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span>Assigned Courses ({{ viewingTeacher.courses ? viewingTeacher.courses.length : 0 }})</span>
              </span>
              <span class="text-[10px] text-slate-400 font-normal">Managed via Course Management</span>
            </h4>

            <div v-if="viewingTeacher.courses && viewingTeacher.courses.length > 0" class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-[10px] uppercase font-bold text-slate-500">
                  <tr>
                    <th class="py-2.5 px-3">Course Code & Title</th>
                    <th class="py-2.5 px-3">Enrolled Students</th>
                    <th class="py-2.5 px-3">Lessons</th>
                    <th class="py-2.5 px-3">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                  <tr v-for="c in viewingTeacher.courses" :key="c.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                    <td class="py-2.5 px-3">
                      <div class="font-bold text-slate-900 dark:text-white">{{ c.title }}</div>
                      <div class="text-[10px] text-slate-400 font-mono">{{ c.code || 'CRS-IT-001' }}</div>
                    </td>
                    <td class="py-2.5 px-3 font-mono font-bold text-cyan-600">
                      {{ c.enrollments ? c.enrollments.length : 0 }} Students
                    </td>
                    <td class="py-2.5 px-3 font-mono text-slate-600 dark:text-slate-300">
                      {{ c.lessons ? c.lessons.length : 0 }} Lessons
                    </td>
                    <td class="py-2.5 px-3">
                      <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 border border-emerald-200 dark:border-emerald-500/20">
                        {{ c.status || 'Published' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="p-4 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800 text-center text-slate-400 text-xs">
              No courses assigned yet. Courses can be assigned under <strong>Course Management → Courses</strong>.
            </div>
          </div>

          <!-- Action Buttons in Profile -->
          <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center gap-3">
            <button
              @click="toggleActivateDisable(viewingTeacher)"
              :class="[
                (viewingTeacher.status || 'active') === 'active'
                  ? 'bg-rose-50 hover:bg-rose-100 text-rose-600 border-rose-200'
                  : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border-emerald-200',
                'px-4 py-2 border rounded-xl text-xs font-semibold cursor-pointer'
              ]"
            >
              {{ (viewingTeacher.status || 'active') === 'active' ? 'Disable Account' : 'Activate Account' }}
            </button>
            <button
              @click="openEditModal(viewingTeacher)"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold cursor-pointer"
            >
              Edit Profile
            </button>
            <button
              @click="showProfileModal = false"
              class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold shadow-md cursor-pointer"
            >
              Done
            </button>
          </div>
        </div>
      </div>

      <!-- Clean Toast Notification -->
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
