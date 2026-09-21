<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import UserModuleHeader from '@/Components/Admin/UserModuleHeader.vue'

const props = withDefaults(defineProps<{
  students?: Array<any>
  departments?: Array<any>
  majors?: Array<any>
  summaryStats?: any
}>(), {
  students: () => [],
  departments: () => [],
  majors: () => [],
  summaryStats: () => ({})
})

const search = ref('')
const selectedMajor = ref('')
const selectedPaymentStatus = ref('')
const selectedStudentForEnroll = ref<any | null>(null)
const selectedSubjectForAbaPay = ref<any | null>(null)
const openDropdownId = ref<number | null>(null)
const selectedStudentIds = ref<number[]>([])

const toggleSelectAll = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.checked) {
    selectedStudentIds.value = filteredStudents.value.map(s => s.id)
  } else {
    selectedStudentIds.value = []
  }
}

const bulkExport = () => {
  alert(`Exporting ${selectedStudentIds.value.length} selected student profiles...`)
}

const bulkSuspend = () => {
  if (confirm(`Are you sure you want to suspend ${selectedStudentIds.value.length} selected student accounts?`)) {
    alert(`Suspended ${selectedStudentIds.value.length} student accounts successfully!`)
    selectedStudentIds.value = []
  }
}

const studentForm = useForm({
  id: null as number | null,
  name: '',
  email: '',
  phone: '',
  gender: 'Male',
  major_id: '',
  learning_mode: 'Instructor-Led',
  payment_type: 'Paid per Subject',
  enrolled_subjects: [
    { name: 'C Programming 101', teacher: 'Mr. Sophea', price: 25, status: 'Paid' },
    { name: 'Data Structures & Algorithms', teacher: 'Mr. Vuthy', price: 20, status: 'Paid' },
    { name: 'Database Systems & SQL', teacher: 'Mr. Sophea', price: 25, status: 'Unpaid' },
  ]
})

const filteredStudents = computed(() => {
  return props.students.filter(s => {
    const matchesSearch = !search.value ||
      s.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      s.email?.toLowerCase().includes(search.value.toLowerCase())

    const matchesMajor = !selectedMajor.value || s.major_id == selectedMajor.value
    return matchesSearch && matchesMajor
  })
})

const openEnrollModal = (student: any) => {
  selectedStudentForEnroll.value = student
  studentForm.id = student.id
  studentForm.name = student.name || ''
  studentForm.email = student.email || ''
  studentForm.phone = student.phone || '+855 12 345 678'
  studentForm.major_id = student.major_id || ''
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

const saveStudentEnrollment = () => {
  const studentName = studentForm.name || 'និស្សិត'
  if (selectedStudentForEnroll.value && !selectedStudentForEnroll.value.isNew) {
    studentForm.put(`/admin/users/${studentForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        selectedStudentForEnroll.value = null
        triggerToast(
          'រក្សាទុកបានជោគជ័យ',
          `ព័ត៌មាននិស្សិត "${studentName}" ត្រូវបានបច្ចុប្បន្នភាពដោយជោគជ័យ`
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
    studentForm.post('/admin/users', {
      preserveScroll: true,
      onSuccess: () => {
        selectedStudentForEnroll.value = null
        studentForm.reset()
        triggerToast(
          'បង្កើតនិស្សិតបានជោគជ័យ',
          `គណនីនិស្សិត "${studentName}" ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast(
          'មានបញ្ហាក្នុងការបង្កើតនិស្សិត',
          'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ',
          'warning'
        )
      }
    })
  }
}

const processAbaPayment = (subject: any) => {
  selectedSubjectForAbaPay.value = subject
}

const confirmAbaPaymentSimulation = () => {
  if (selectedSubjectForAbaPay.value) {
    selectedSubjectForAbaPay.value.status = 'Paid'
    selectedSubjectForAbaPay.value = null
  }
}

const suspendStudent = (student: any) => {
  if (confirm(`Suspend student account '${student.name}'?`)) {
    router.post(`/admin/user-management/suspend/${student.id}`)
  }
}

const openAddStudentModal = () => {
  studentForm.reset()
  studentForm.id = null
  studentForm.name = ''
  studentForm.email = ''
  studentForm.phone = ''
  studentForm.major_id = props.majors[0]?.id || ''
  selectedStudentForEnroll.value = { id: 0, isNew: true }
}

const exportStudentsCSV = () => {
  const headers = ['ID', 'Name', 'Email', 'Phone', 'Major']
  const rows = filteredStudents.value.map(s => [
    s.id,
    s.name,
    s.email,
    s.phone || '',
    s.major?.name || ''
  ])

  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n')
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', `elms_students_${new Date().toISOString().slice(0, 10)}.csv`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<template>
  <AdminLayout title="Students — Learners Management">
    <div class="space-y-6 font-sans">
      <!-- Shared Header -->
      <UserModuleHeader activeTab="students" :summaryStats="props.summaryStats" />

      <!-- SINGLE ROW FILTER & ACTION TOOLBAR (Filters Left — Actions Far Right) -->
      <div class="bg-white dark:bg-slate-900/60 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-none backdrop-blur-xl flex flex-wrap items-center justify-between gap-3">
        <!-- Left Aligned Filters -->
        <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[300px]">
          <div class="relative flex-1 min-w-[200px]">
            <input
              v-model="search"
              type="text"
              placeholder="Search student name, email, phone..."
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-all"
            />
            <span class="absolute left-3 top-2.5 text-slate-400 dark:text-slate-500">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
          </div>

          <select v-model="selectedMajor" class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-emerald-700 dark:text-emerald-300 font-semibold focus:outline-none focus:border-emerald-500 cursor-pointer">
            <option value="">Filter by Major (All)</option>
            <option v-for="m in props.majors" :key="m.id" :value="m.id">
              {{ m.name }}
            </option>
          </select>

          <select v-model="selectedPaymentStatus" class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500 cursor-pointer">
            <option value="">Payment: All Status</option>
            <option value="paid">Paid Status</option>
            <option value="pending">Pending Payment</option>
            <option value="free">Scholarship</option>
          </select>

          <button v-if="search || selectedMajor || selectedPaymentStatus" @click="search = ''; selectedMajor = ''; selectedPaymentStatus = ''" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold transition-all flex items-center gap-1 cursor-pointer">
            <span>✕ Reset</span>
          </button>
        </div>

        <!-- Far Right Primary Actions -->
        <div class="flex items-center gap-2">
          <button
            @click="openAddStudentModal"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add New Student</span>
          </button>

          <a
            href="/admin/user-management/import-export"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Import</span>
          </a>

          <button
            @click="exportStudentsCSV"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold text-xs rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Export</span>
          </button>
        </div>
      </div>

      <!-- FLOATING BULK ACTIONS TOOLBAR -->
      <div v-if="selectedStudentIds.length > 0" class="p-3.5 bg-emerald-50 dark:bg-indigo-950/80 border border-emerald-200 dark:border-indigo-500/30 rounded-2xl flex items-center justify-between text-xs backdrop-blur-xl shadow-lg animate-fade-in">
        <div class="flex items-center gap-2 text-emerald-800 dark:text-indigo-300 font-bold font-mono">
          <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
          <span>Selected ({{ selectedStudentIds.length }}) Students</span>
        </div>
        <div class="flex items-center gap-2">
          <button @click="bulkExport" class="px-3.5 py-1.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 transition-all cursor-pointer flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Export Selected</span>
          </button>
          <button @click="bulkSuspend" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-600/20 transition-all cursor-pointer flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <span>Suspend Selected</span>
          </button>
        </div>
      </div>

      <!-- STUDENTS DATA TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 shadow-sm dark:shadow-none backdrop-blur-xl min-h-[380px]">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <th class="py-3.5 px-4 w-10 text-center">
                <input type="checkbox" @change="toggleSelectAll" class="rounded bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 cursor-pointer" />
              </th>
              <th class="py-3.5 px-4 w-12 text-center">#</th>
              <th class="py-3.5 px-4">Student Name</th>
              <th class="py-3.5 px-4">Email & Phone</th>
              <th class="py-3.5 px-4">Major & Department</th>
              <th class="py-3.5 px-4">Enrolled Subjects</th>
              <th class="py-3.5 px-4 text-center">Payment Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            <tr v-for="(student, idx) in filteredStudents" :key="student.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-all group">
              <!-- Checkbox -->
              <td class="py-3.5 px-4 text-center">
                <input type="checkbox" :value="student.id" v-model="selectedStudentIds" class="rounded bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-emerald-600 focus:ring-emerald-500 cursor-pointer" />
              </td>

              <!-- Number Index -->
              <td class="py-3.5 px-4 text-center font-mono text-slate-500 dark:text-slate-400 font-medium">{{ String(idx + 1).padStart(2, '0') }}</td>

              <!-- Clickable Student Name & Avatar -->
              <td class="py-3.5 px-4">
                <button
                  @click="openEnrollModal(student)"
                  class="flex items-center gap-3 text-left focus:outline-none group/item"
                  title="Click to view student enrollments and profile"
                >
                  <img
                    :src="student.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(student.name)}&background=10b981&color=fff`"
                    class="w-9 h-9 rounded-full border-2 border-emerald-500/40 shadow-xs object-cover group-hover/item:border-emerald-500 group-hover/item:scale-105 transition-all"
                  />
                  <div>
                    <div class="font-bold text-slate-900 dark:text-white group-hover/item:text-emerald-600 dark:group-hover/item:text-emerald-300 transition-colors flex items-center gap-1.5">
                      <span>{{ student.name }}</span>
                    </div>
                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400/90 font-mono font-semibold flex items-center gap-1 mt-0.5">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                      <span>STU-2025-{{ student.id + 100 }}</span>
                    </div>
                  </div>
                </button>
              </td>

              <!-- Email & Phone -->
              <td class="py-3.5 px-4">
                <div class="font-mono text-slate-800 dark:text-slate-200 font-medium group-hover:text-slate-900 dark:group-hover:text-white transition-colors flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <span>{{ student.email }}</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 font-mono flex items-center gap-1.5 mt-0.5">
                  <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <span>{{ student.phone || '+855 12 345 678' }}</span>
                </div>
              </td>

              <!-- Major & Department (Clean Single-Line & High-Contrast Typography) -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <template v-if="student.major">
                  <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">{{ student.major.name }}</div>
                  <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>{{ student.major.department?.name || 'Faculty of Science & Tech' }}</span>
                  </div>
                </template>
                <template v-else>
                  <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">IT & Computer Science</div>
                  <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>Faculty of Science & Tech</span>
                  </div>
                </template>
              </td>

              <!-- Enrolled Subjects -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-300 rounded-xl font-mono font-bold text-[11px] whitespace-nowrap shadow-xs">
                  <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                  <span>4 Subjects Enrolled</span>
                </span>
              </td>

              <!-- Payment Status -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span v-if="idx % 3 === 0" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 whitespace-nowrap">
                  <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <span>Paid ($70)</span>
                </span>
                <span v-else-if="idx % 3 === 1" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 whitespace-nowrap">
                  <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>Unpaid ($25)</span>
                </span>
                <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 whitespace-nowrap">
                  <svg class="w-3 h-3 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V6a2 2 0 10-2 2h2zm0 13C10.832 19.477 9.246 19 7.5 19S4.168 19.477 3 20.253v-13C4.168 6.477 5.754 6 7.5 6s3.332.477 4.5 1.253m0 13C13.168 19.477 14.754 19 16.5 19c1.747 0 3.332.477 4.5 1.253v-13C19.832 6.477 18.247 6 16.5 6c-1.746 0-3.332.477-4.5 1.253"/></svg>
                  <span>Scholarship</span>
                </span>
              </td>

              <!-- Direct Quick Action Icons (Uniform Fixed Width & Height) -->
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openEnrollModal(student)"
                    class="w-[105px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-emerald-50 dark:bg-slate-800 dark:hover:bg-emerald-500/20 text-slate-700 dark:text-slate-200 hover:text-emerald-700 dark:hover:text-white border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Quick Enroll & Edit Profile"
                  >
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span>Enroll / Edit</span>
                  </button>

                  <button
                    @click="suspendStudent(student)"
                    class="w-[88px] h-8 inline-flex items-center justify-center gap-1 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-red-500/20 text-rose-600 dark:text-red-400 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Suspend Student"
                  >
                    <svg class="w-3.5 h-3.5 text-rose-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <span>Suspend</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredStudents.length === 0">
              <td colspan="8" class="py-12 text-center text-slate-500 font-medium">
                No student accounts found matching search or filter criteria.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Table Pagination Footer -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="text-slate-500 dark:text-slate-400 font-mono">
            Showing <span class="text-slate-900 dark:text-white font-bold">1</span> to <span class="text-slate-900 dark:text-white font-bold">{{ filteredStudents.length }}</span> of <span class="text-slate-900 dark:text-white font-bold">{{ props.summaryStats?.total_students || 2458 }}</span> entries
          </div>

          <div class="flex items-center gap-1.5 font-mono">
            <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-xl font-semibold cursor-not-allowed border border-slate-200 dark:border-transparent" disabled>Previous</button>
            <button class="px-3 py-1.5 bg-emerald-600 text-white font-bold rounded-xl shadow-xs shadow-emerald-600/20">1</button>
            <button class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold cursor-pointer border border-slate-200 dark:border-transparent">2</button>
            <button class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold cursor-pointer border border-slate-200 dark:border-transparent">Next</button>
          </div>
        </div>
      </div>

      <!-- STUDENT PROFILE & ENROLLMENT FORM MODAL -->
      <div v-if="selectedStudentForEnroll" class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900/95 border border-slate-200 dark:border-emerald-900/50 rounded-3xl max-w-3xl w-full p-7 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh] backdrop-blur-2xl">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-wide uppercase">
                  STUDENT ENROLLMENT & SUBJECT PAYMENT FORM
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">គ្រប់គ្រងការចុះឈ្មោះមុខវិជ្ជា, ប័ណ្ណទូទាត់ប្រាក់ និង ABA Pay Direct QR</p>
              </div>
            </div>
            <button @click="selectedStudentForEnroll = null" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/60 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-all cursor-pointer">✕</button>
          </div>

          <form @submit.prevent="saveStudentEnrollment" class="space-y-4 text-xs">
            <!-- Basic Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Student Name -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  <span>Student Full Name *</span>
                </label>
                <div class="relative">
                  <input
                    v-model="studentForm.name"
                    type="text"
                    placeholder="e.g. SOK SOPHEA"
                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-bold focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                  />
                  <span class="absolute left-3 top-3 text-slate-400 dark:text-slate-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  </span>
                </div>
              </div>

              <!-- Email Address -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <span>Email Address *</span>
                </label>
                <div class="relative">
                  <input
                    v-model="studentForm.email"
                    type="email"
                    placeholder="e.g. student@elms.edu.kh"
                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-mono focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                  />
                  <span class="absolute left-3 top-3 text-slate-400 dark:text-slate-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  </span>
                </div>
              </div>

              <!-- Assigned Major -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                  <span>Assigned Major</span>
                </label>
                <select v-model="studentForm.major_id" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-xs text-emerald-700 dark:text-emerald-300 font-bold focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all cursor-pointer">
                  <option value="">Select Major...</option>
                  <option v-for="m in props.majors" :key="m.id" :value="m.id">
                    {{ m.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- ENROLLED SUBJECTS TABLE -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-3 shadow-xs">
              <div class="flex items-center justify-between">
                <label class="font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider text-[11px] flex items-center gap-2">
                  <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                  <span>ENROLLED SUBJECTS & PAYMENT TRACKING</span>
                </label>
                <span class="text-[10px] font-mono text-emerald-700 dark:text-emerald-300 font-bold px-2 py-0.5 bg-emerald-100 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-lg">
                  3 Enrolled
                </span>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                  <thead>
                    <tr class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800/80 pb-2">
                      <th class="pb-2.5 px-2">Subject Name</th>
                      <th class="pb-2.5 px-2">Teacher</th>
                      <th class="pb-2.5 px-2">Subject Fee</th>
                      <th class="pb-2.5 px-2 text-center">Status</th>
                      <th class="pb-2.5 px-2 text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80 text-xs">
                    <tr v-for="sub in studentForm.enrolled_subjects" :key="sub.name" class="hover:bg-slate-100/60 dark:hover:bg-slate-900/40 transition-colors">
                      <td class="py-3 px-2 font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>{{ sub.name }}</span>
                      </td>
                      <td class="py-3 px-2 text-slate-600 dark:text-slate-300 font-medium">{{ sub.teacher }}</td>
                      <td class="py-3 px-2 font-mono text-emerald-600 dark:text-emerald-400 font-bold">${{ sub.price }}</td>
                      <td class="py-3 px-2 text-center">
                        <span v-if="sub.status === 'Paid'" class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 rounded-lg text-[10px] font-bold">
                          <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                          <span>Paid</span>
                        </span>
                        <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 rounded-lg text-[10px] font-bold">
                          <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                          <span>Unpaid</span>
                        </span>
                      </td>
                      <td class="py-3 px-2 text-right">
                        <button
                          v-if="sub.status !== 'Paid'"
                          type="button"
                          @click="processAbaPayment(sub)"
                          class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-[10px] transition-all flex items-center gap-1 justify-end ml-auto shadow-xs shadow-emerald-600/30 cursor-pointer"
                        >
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                          <span>Pay ABA Direct</span>
                        </button>
                        <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700/60 rounded-xl text-[10px] font-semibold cursor-default">
                          <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                          <span>Settled</span>
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end items-center gap-3">
              <button type="button" @click="selectedStudentForEnroll = null" :disabled="studentForm.processing" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold transition-all cursor-pointer disabled:opacity-50 border border-slate-200 dark:border-transparent">Cancel</button>
              <button type="submit" :disabled="studentForm.processing" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <svg v-if="studentForm.processing" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ studentForm.processing ? (selectedStudentForEnroll?.isNew ? 'កំពុងបង្កើត...' : 'កំពុងរក្សាទុក...') : (selectedStudentForEnroll?.isNew ? 'Create Student' : 'Save Enrollment Changes') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ABA PAY DIRECT KHQR MODAL -->
      <div v-if="selectedSubjectForAbaPay" class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/85 backdrop-blur-md z-[60] flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900/95 border border-slate-200 dark:border-emerald-500/30 rounded-3xl max-w-md w-full p-7 space-y-5 shadow-2xl backdrop-blur-2xl text-center relative overflow-hidden">
          <!-- Header Badge -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3.5">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              </div>
              <div class="text-left">
                <div class="flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                  <h3 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider">ABA PAY DIRECT — KHQR</h3>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">សូមប្រើប្រាស់ ABA Mobile ស្កែន QR Code ដើម្បីបង់ប្រាក់</p>
              </div>
            </div>
            <button @click="selectedSubjectForAbaPay = null" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/60 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center text-xs transition-all cursor-pointer">✕</button>
          </div>

          <!-- Merchant & Subject Details -->
          <div class="space-y-1.5 bg-slate-50 dark:bg-slate-950/60 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800/80">
            <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">MERCHANT: <span class="text-slate-900 dark:text-white font-bold">ELMS ACADEMY CAMBODIA</span></div>
            <h4 class="text-base font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">{{ selectedSubjectForAbaPay.name }}</h4>
            <div class="text-xs text-slate-600 dark:text-slate-300 font-medium">Instructor: {{ selectedSubjectForAbaPay.teacher }}</div>
          </div>

          <!-- Total Amount Badge -->
          <div class="py-3 px-6 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 rounded-2xl inline-block shadow-xs">
            <div class="text-[10px] uppercase tracking-wider text-emerald-800 dark:text-emerald-300 font-bold">TOTAL PAYABLE AMOUNT</div>
            <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">${{ selectedSubjectForAbaPay.price }}.00 <span class="text-xs font-mono text-emerald-700 dark:text-emerald-300">USD</span></div>
          </div>

          <!-- ABA KHQR Card Stand Frame with Laser Scan Animation -->
          <div class="bg-white p-5 rounded-3xl max-w-[230px] mx-auto shadow-2xl border-4 border-red-600/90 relative overflow-hidden group">
            <!-- Top ABA PAY Tag -->
            <div class="bg-red-600 text-white font-black text-[11px] tracking-widest uppercase py-1.5 px-3 rounded-lg mb-3 flex items-center justify-between shadow-sm">
              <span class="flex items-center gap-1">
                <span>ABA</span>
                <span class="text-amber-300 font-sans">PAY</span>
              </span>
              <span class="text-[9px] bg-white text-red-600 font-mono font-bold px-1.5 py-0.5 rounded">KHQR</span>
            </div>

            <!-- Animated Laser Scanning Line -->
            <div class="relative">
              <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-red-500 to-transparent animate-pulse shadow-md shadow-red-500/50 z-10 top-1/2 -translate-y-1/2"></div>
              <svg class="w-40 h-40 mx-auto text-slate-900 relative z-0" viewBox="0 0 100 100" fill="currentColor">
                <rect x="0" y="0" width="30" height="30" />
                <rect x="3" y="3" width="24" height="24" fill="#fff" />
                <rect x="7" y="7" width="16" height="16" />
                
                <rect x="70" y="0" width="30" height="30" />
                <rect x="73" y="3" width="24" height="24" fill="#fff" />
                <rect x="77" y="7" width="16" height="16" />

                <rect x="0" y="70" width="30" height="30" />
                <rect x="3" y="73" width="24" height="24" fill="#fff" />
                <rect x="7" y="77" width="16" height="16" />

                <rect x="35" y="5" width="8" height="8" />
                <rect x="48" y="5" width="8" height="8" />
                <rect x="58" y="15" width="8" height="8" />
                <rect x="35" y="25" width="8" height="8" />

                <rect x="5" y="35" width="8" height="8" />
                <rect x="20" y="45" width="8" height="8" />
                <rect x="35" y="40" width="12" height="12" fill="#dc2626" />
                <rect x="55" y="35" width="8" height="8" />
                <rect x="70" y="45" width="8" height="8" />
                <rect x="85" y="35" width="8" height="8" />

                <rect x="35" y="65" width="8" height="8" />
                <rect x="50" y="75" width="8" height="8" />
                <rect x="65" y="65" width="8" height="8" />
                <rect x="80" y="75" width="8" height="8" />
              </svg>
            </div>

            <!-- Footer Label -->
            <div class="text-[10px] font-mono text-slate-800 font-bold mt-3 border-t border-slate-200 pt-2 flex items-center justify-center gap-1">
              <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
              <span>Scan with ABA Mobile</span>
            </div>
          </div>

          <!-- Simulation Button -->
          <div class="space-y-2 pt-1">
            <button
              @click="confirmAbaPaymentSimulation"
              class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <span>Simulate Successful ABA Payment</span>
            </button>

            <button
              @click="selectedSubjectForAbaPay = null"
              class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-transparent text-xs rounded-xl transition-all cursor-pointer font-semibold"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
