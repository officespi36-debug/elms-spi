<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import CourseModuleHeader from '@/Components/Admin/CourseModuleHeader.vue'

const props = withDefaults(defineProps<{
  courses?: any[]
  summaryStats?: any
}>(), {
  courses: () => [],
  summaryStats: () => ({})
})

const searchQuery = ref('')
const selectedFaculty = ref('all')
const selectedTypePill = ref('all') // 'all' | 'teacher_led' | 'self_study' | 'paid' | 'free'
const selectedMode = ref('all')
const selectedPrice = ref('all')
const selectedStatus = ref('all')

// Bulk Actions State
const selectedIds = ref<number[]>([])

// Dropdown state for row actions
const activeDropdownId = ref<number | null>(null)
const toggleDropdown = (id: number) => {
  activeDropdownId.value = activeDropdownId.value === id ? null : id
}
const closeDropdown = () => {
  activeDropdownId.value = null
}

// Pagination state
const currentPage = ref(1)
const pageSize = ref(10)

const isModalOpen = ref(false)
const editingCourse = ref<any>(null)
const form = ref({
  code: 'CRS-IT-NEW101',
  title: '',
  subject: 'C Programming',
  faculty: 'Faculty of Computing',
  department: 'Computing',
  major: 'IT & Networking',
  academic_year: '2024 - 2025',
  semester: 'Semester 2',
  learning_mode: 'instructor_led',
  level: 'Beginner',
  language: 'Khmer',
  duration: '8 Weeks',
  hours: 24,
  teacher: 'Mr. Sophea',
  max_students: 60,
  enrollment_status: 'Open',
  is_paid: true,
  price: 25,
  payment_method: 'ABA Bank QR / Deep Link',
  receiver: 'School ABA Account',
  status: 'published'
})

const coursesList = computed(() => {
  return Array.isArray(props.courses) && props.courses.length > 0 ? props.courses : [
    { id: 1, code: 'CRS-IT-CP101', title: 'C Programming Basics', subject: 'C Programming', faculty: 'Faculty of Computing', department: 'Computing', major: 'Information Technology', teacher: 'Mr. Sophea', learning_mode: 'instructor_led', mode_label: '🎥 Teacher-Led', is_paid: true, price: 25, status: 'published', students_count: 52 },
    { id: 2, code: 'CRS-IT-DB102', title: 'Database Systems', subject: 'Database Systems', faculty: 'Faculty of Computing', department: 'Computing', major: 'Information Technology', teacher: 'Mr. Sophea', learning_mode: 'self_paced', mode_label: '💻 Self-Study', is_paid: true, price: 20, status: 'published', students_count: 120 },
    { id: 3, code: 'CRS-ENG-EG101', title: 'English Grammar Basics', subject: 'English Grammar', faculty: 'Faculty of Education', department: 'Education', major: 'English Literature', teacher: 'Ms. Srey', learning_mode: 'self_paced', mode_label: '💻 Self-Study', is_paid: false, price: 0, status: 'published', students_count: 1200 },
    { id: 4, code: 'CRS-TM-TB101', title: 'Tourism Basics', subject: 'Tourism Basics', faculty: 'Faculty of Tourism', department: 'Tourism', major: 'Tourism', teacher: 'Mr. Long', learning_mode: 'instructor_led', mode_label: '🎥 Teacher-Led', is_paid: true, price: 25, status: 'draft', students_count: 25 },
    { id: 5, code: 'CRS-AG-PS101', title: 'Plant Science', subject: 'Plant Science', faculty: 'Faculty of Agriculture', department: 'Agriculture', major: 'Agriculture', teacher: 'Mr. Vuthy', learning_mode: 'instructor_led', mode_label: '🎥 Teacher-Led', is_paid: true, price: 30, status: 'published', students_count: 60 },
    { id: 6, code: 'CRS-SW-SW101', title: 'Social Work 101', subject: 'Social Work 101', faculty: 'Faculty of Social Science', department: 'Social Science', major: 'Social Work', teacher: 'Mr. Rithy', learning_mode: 'self_paced', mode_label: '💻 Self-Study', is_paid: true, price: 15, status: 'published', students_count: 548 },
  ]
})

const filteredCourses = computed(() => {
  return coursesList.value.filter(crs => {
    const title = crs.title || ''
    const code = crs.code || ''
    const teacher = typeof crs.teacher === 'object' ? (crs.teacher?.name || '') : (crs.teacher || '')
    const matchesSearch = title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          teacher.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesFaculty = selectedFaculty.value === 'all' || crs.faculty === selectedFaculty.value

    // Segmented Pill Filter logic
    let matchesPill = true
    if (selectedTypePill.value === 'teacher_led') matchesPill = crs.learning_mode === 'instructor_led'
    else if (selectedTypePill.value === 'self_study') matchesPill = crs.learning_mode === 'self_paced'
    else if (selectedTypePill.value === 'paid') matchesPill = crs.is_paid && crs.price > 0
    else if (selectedTypePill.value === 'free') matchesPill = !crs.is_paid || crs.price === 0

    const matchesMode = selectedMode.value === 'all' || crs.learning_mode === selectedMode.value
    const matchesPrice = selectedPrice.value === 'all' || (selectedPrice.value === 'free' ? !crs.is_paid : crs.is_paid)
    const matchesStatus = selectedStatus.value === 'all' || crs.status === selectedStatus.value
    return matchesSearch && matchesFaculty && matchesPill && matchesMode && matchesPrice && matchesStatus
  })
})

// Reset pagination on filter change
watch([searchQuery, selectedFaculty, selectedTypePill, selectedMode, selectedPrice, selectedStatus, pageSize], () => {
  currentPage.value = 1
  selectedIds.value = []
})

// Total pages & paginated items
const totalPages = computed(() => Math.ceil(filteredCourses.value.length / pageSize.value) || 1)
const paginatedCourses = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredCourses.value.slice(start, start + pageSize.value)
})

// Select All logic
const isAllSelected = computed({
  get: () => paginatedCourses.value.length > 0 && paginatedCourses.value.every(crs => selectedIds.value.includes(crs.id)),
  set: (val: boolean) => {
    if (val) {
      const pageIds = paginatedCourses.value.map(crs => crs.id)
      selectedIds.value = Array.from(new Set([...selectedIds.value, ...pageIds]))
    } else {
      const pageIds = paginatedCourses.value.map(crs => crs.id)
      selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id))
    }
  }
})

const toggleSelectAll = () => {
  isAllSelected.value = !isAllSelected.value
}

// Bulk Actions
const bulkDelete = () => {
  if (confirm(`Are you sure you want to delete ${selectedIds.value.length} selected courses?`)) {
    alert(`Bulk deleted ${selectedIds.value.length} courses.`)
    selectedIds.value = []
  }
}

const bulkChangeStatus = (status: string) => {
  alert(`Changed status of ${selectedIds.value.length} courses to '${status}'.`)
  selectedIds.value = []
}

const openAddModal = () => {
  editingCourse.value = null
  form.value = {
    code: `CRS-NEW-${Math.floor(100 + Math.random() * 900)}`,
    title: '',
    subject: 'C Programming',
    faculty: 'Faculty of Computing',
    department: 'Computing',
    major: 'IT & Networking',
    academic_year: '2024 - 2025',
    semester: 'Semester 2',
    learning_mode: 'instructor_led',
    level: 'Beginner',
    language: 'Khmer',
    duration: '8 Weeks',
    hours: 24,
    teacher: 'Mr. Sophea',
    max_students: 60,
    enrollment_status: 'Open',
    is_paid: true,
    price: 25,
    payment_method: 'ABA Bank QR / Deep Link',
    receiver: 'School ABA Account',
    status: 'published'
  }
  isModalOpen.value = true
}

const openEditModal = (crs: any) => {
  closeDropdown()
  editingCourse.value = crs
  form.value = { ...crs }
  isModalOpen.value = true
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

const isSubmitting = ref(false)

const saveCourse = () => {
  if (!form.value.title) {
    triggerToast('សូមបញ្ចូលឈ្មោះវគ្គសិក្សា', 'សូមបញ្ចូល Course Title មុនពេលរក្សាទុក', 'warning')
    return
  }

  isSubmitting.value = true
  const courseTitle = form.value.title || 'វគ្គសិក្សា'

  if (editingCourse.value?.id) {
    router.put(`/admin/course-module/update/${editingCourse.value.id}`, form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false
        isModalOpen.value = false
        triggerToast(
          'រក្សាទុកបានជោគជ័យ',
          `ព័ត៌មានវគ្គសិក្សា "${courseTitle}" ត្រូវបានបច្ចុប្បន្នភាពដោយជោគជ័យ`
        )
      },
      onError: () => {
        isSubmitting.value = false
        triggerToast(
          'មានបញ្ហាក្នុងការរក្សាទុក',
          'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ',
          'warning'
        )
      },
      onFinish: () => {
        isSubmitting.value = false
      }
    })
  } else {
    router.post('/admin/course-module/store', form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false
        isModalOpen.value = false
        triggerToast(
          'បង្កើតវគ្គសិក្សាបានជោគជ័យ 🎉',
          `វគ្គសិក្សា "${courseTitle}" ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        isSubmitting.value = false
        triggerToast(
          'មានបញ្ហាក្នុងការបង្កើតវគ្គសិក្សា',
          'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ',
          'warning'
        )
      },
      onFinish: () => {
        isSubmitting.value = false
      }
    })
  }
}

const deleteCourse = (crs: any) => {
  closeDropdown()
  if (confirm(`Are you sure you want to delete course '${crs.title}'?`)) {
    router.delete(`/admin/course-module/destroy/${crs.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast(
          'លុបវគ្គសិក្សាបានជោគជ័យ',
          `វគ្គសិក្សា "${crs.title}" ត្រូវបានលុបចេញពីប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast(
          'មានបញ្ហាក្នុងការលុប',
          'មិនអាចលុបវគ្គសិក្សានេះបានទេ',
          'warning'
        )
      }
    })
  }
}

const viewContent = (crs: any) => {
  closeDropdown()
  alert(`Opening Content Manager for '${crs.title}' (${crs.code})...`)
}

const openAbaSettings = (crs: any) => {
  closeDropdown()
  alert(`ABA Bank QR / Pricing settings for '${crs.title}' ($${crs.price} USD)`)
}
</script>

<template>
  <AdminLayout title="All Courses — Course & Subject Management">
    <div class="space-y-4 font-sans" @click="closeDropdown">
      <!-- Shared Navigation Header (1-Layer Clean Header) -->
      <CourseModuleHeader activeTab="all" :summaryStats="props.summaryStats" />

      <!-- UNIFIED CONTROLS & FILTER BAR -->
      <div class="p-3.5 bg-slate-900/60 border border-slate-800 rounded-2xl backdrop-blur-xl space-y-2.5">
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 text-xs">
          <!-- Search Box with Clean SVG Lens -->
          <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search title, code, teacher, major..."
              class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3.5 py-2 text-white placeholder-slate-400 focus:outline-none focus:border-sky-500"
            />
          </div>

          <!-- Filters -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <select v-model="selectedFaculty" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 font-medium">
              <option value="all">Faculty: All</option>
              <option value="Faculty of Computing">Faculty of Computing</option>
              <option value="Faculty of Tourism">Faculty of Tourism</option>
              <option value="Faculty of Education">Faculty of Education</option>
              <option value="Faculty of Agriculture">Faculty of Agriculture</option>
              <option value="Faculty of Social Science">Faculty of Social Science</option>
            </select>

            <select v-model="selectedMode" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 font-medium">
              <option value="all">Mode: All</option>
              <option value="instructor_led">Teacher-Led</option>
              <option value="self_paced">Self-Study</option>
            </select>

            <select v-model="selectedPrice" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 font-medium">
              <option value="all">Price: All</option>
              <option value="paid">Paid Only</option>
              <option value="free">Free Only</option>
            </select>

            <select v-model="selectedStatus" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-slate-200 font-medium">
              <option value="all">Status: All</option>
              <option value="published">Published</option>
              <option value="draft">Draft</option>
            </select>
          </div>

          <!-- Create Button with SVG Plus -->
          <button
            @click="openAddModal"
            class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-sky-600/30 transition-all flex items-center justify-center gap-1.5 whitespace-nowrap"
          >
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Create Course</span>
          </button>
        </div>
      </div>

      <!-- BULK ACTIONS TOOLBAR (Appears when 1+ selected) -->
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between p-3 bg-sky-950/80 border border-sky-500/40 rounded-2xl text-xs backdrop-blur-xl animate-fade-in">
        <div class="flex items-center gap-2 text-sky-200 font-bold">
          <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ selectedIds.length }} course(s) selected</span>
        </div>
        <div class="flex items-center gap-2">
          <button @click="bulkChangeStatus('published')" class="px-3 py-1.5 bg-emerald-600/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/30 rounded-xl font-bold flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Bulk Publish
          </button>
          <button @click="bulkChangeStatus('draft')" class="px-3 py-1.5 bg-amber-600/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 rounded-xl font-bold flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span> Bulk Draft
          </button>
          <button @click="bulkDelete" class="px-3 py-1.5 bg-red-600/20 hover:bg-red-500/30 text-red-300 border border-red-500/30 rounded-xl font-bold flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Bulk Delete
          </button>
          <button @click="selectedIds = []" class="px-2.5 py-1.5 bg-slate-800 text-slate-300 hover:text-white rounded-xl font-bold">
            ✕ Clear
          </button>
        </div>
      </div>

      <!-- COURSES TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-xl">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-800/40 text-[11px] font-bold text-slate-300 uppercase whitespace-nowrap">
              <th class="py-3 px-3 w-10 text-center">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="rounded border-slate-700 bg-slate-950 text-sky-500 focus:ring-sky-500/20"
                />
              </th>
              <th class="py-3.5 px-4 w-12">#</th>
              <th class="py-3.5 px-4">
                <div class="flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                  <span>Course Title</span>
                </div>
              </th>
              <th class="py-3.5 px-4">
                <div class="flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h5m-5 0V11m0 0h5m-5 0H7" />
                  </svg>
                  <span>Major & Dept</span>
                </div>
              </th>
              <th class="py-3.5 px-4">
                <div class="flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span>Teacher</span>
                </div>
              </th>
              <th class="py-3.5 px-4 text-center">Enrolled</th>
              <th class="py-3.5 px-4 text-center">Mode</th>
              <th class="py-3.5 px-4 text-center">Price</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 text-xs">
            <tr v-if="paginatedCourses.length === 0">
              <td colspan="10" class="py-8 text-center text-slate-300">
                No courses found matching criteria.
              </td>
            </tr>
            <tr
              v-for="(crs, idx) in paginatedCourses"
              :key="crs.id"
              class="hover:bg-slate-800/30 transition-all"
              :class="{ 'bg-sky-950/20': selectedIds.includes(crs.id) }"
            >
              <td class="py-4 px-3 text-center">
                <input
                  type="checkbox"
                  :value="crs.id"
                  v-model="selectedIds"
                  class="rounded border-slate-700 bg-slate-950 text-sky-500 focus:ring-sky-500/20"
                />
              </td>

              <td class="py-4 px-4 font-sans text-slate-300 font-semibold whitespace-nowrap">
                {{ String((currentPage - 1) * pageSize + idx + 1).padStart(2, '0') }}
              </td>

              <td class="py-4 px-4 whitespace-nowrap">
                <div>
                  <div class="font-bold text-white text-sm">{{ crs.title }}</div>
                  <div class="text-[11px] font-sans text-slate-300 flex items-center gap-1.5 mt-0.5">
                    <span class="px-1.5 py-0.5 bg-slate-950 rounded text-sky-300 font-semibold border border-slate-800">{{ crs.code }}</span>
                    <span class="text-slate-400">• Subj: {{ crs.subject || crs.title }}</span>
                  </div>
                </div>
              </td>

              <td class="py-4 px-4 whitespace-nowrap">
                <div class="text-slate-200 font-medium">{{ crs.major || (typeof crs.major === 'object' ? crs.major?.name : 'IT & Networking') }}</div>
                <div class="text-[10px] text-slate-400">{{ crs.faculty || 'Faculty of Computing' }}</div>
              </td>

              <td class="py-4 px-4 whitespace-nowrap font-sans text-slate-200 font-semibold">
                {{ typeof crs.teacher === 'object' ? (crs.teacher?.name || 'Mr. Sophea') : (crs.teacher || 'Mr. Sophea') }}
              </td>

              <!-- Enrolled Students (Clean SVG Icon) -->
              <td class="py-4 px-4 text-center whitespace-nowrap font-sans text-slate-200 font-semibold">
                <span class="px-2.5 py-1 rounded-xl bg-slate-950 border border-slate-800 text-sky-300 text-xs inline-flex items-center gap-1">
                  <svg class="w-3.5 h-3.5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                  <span>{{ crs.students_count ? crs.students_count.toLocaleString() : 0 }}</span>
                </span>
              </td>

              <td class="py-4 px-4 text-center whitespace-nowrap">
                <span v-if="crs.learning_mode === 'instructor_led'" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-500/15 text-indigo-300 border border-indigo-500/30 inline-flex items-center gap-1">
                  <svg class="w-3 h-3 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  <span>Teacher-Led</span>
                </span>
                <span v-else class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-500/15 text-sky-300 border border-sky-500/30 inline-flex items-center gap-1">
                  <svg class="w-3 h-3 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <span>Self-Study</span>
                </span>
              </td>

              <!-- Price Badge -->
              <td class="py-4 px-4 text-center font-bold font-sans whitespace-nowrap">
                <span v-if="crs.is_paid && crs.price > 0" class="text-amber-300 text-xs">${{ crs.price }} USD</span>
                <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">FREE</span>
              </td>

              <td class="py-4 px-4 text-center whitespace-nowrap">
                <span v-if="crs.status === 'published'" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 inline-flex items-center gap-1.5 shadow-sm">
                  <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Published</span>
                </span>
                <span v-else class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30 inline-flex items-center gap-1.5 shadow-sm">
                  <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Draft</span>
                </span>
              </td>

              <!-- STANDARDIZED ACTION BUTTONS (Edit + Dropdown ...) -->
              <td class="py-4 px-4 text-right whitespace-nowrap relative" @click.stop>
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openEditModal(crs)"
                    class="px-3 py-1.5 bg-sky-600/20 hover:bg-sky-500/30 text-sky-300 border border-sky-500/30 rounded-xl font-bold whitespace-nowrap inline-flex items-center gap-1"
                  >
                    <svg class="w-3.5 h-3.5 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit</span>
                  </button>

                  <div class="relative">
                    <button
                      @click="toggleDropdown(crs.id)"
                      class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold transition-all border border-slate-700"
                      title="More Options"
                    >
                      <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                      </svg>
                    </button>

                    <!-- DROPDOWN MENU -->
                    <div
                      v-if="activeDropdownId === crs.id"
                      class="absolute right-0 mt-1 w-44 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl z-50 p-1.5 text-xs text-left space-y-1 backdrop-blur-xl animate-fade-in"
                    >
                      <button
                        @click="openEditModal(crs)"
                        class="w-full px-3 py-2 text-slate-200 hover:text-white hover:bg-slate-800 rounded-xl flex items-center gap-2 font-medium"
                      >
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Details
                      </button>

                      <button
                        @click="viewContent(crs)"
                        class="w-full px-3 py-2 text-slate-200 hover:text-white hover:bg-slate-800 rounded-xl flex items-center gap-2 font-medium"
                      >
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Course Content
                      </button>

                      <button
                        @click="openAbaSettings(crs)"
                        class="w-full px-3 py-2 text-amber-300 hover:bg-amber-500/10 rounded-xl flex items-center gap-2 font-medium"
                      >
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        ABA QR / Price
                      </button>

                      <div class="border-t border-slate-800 my-1"></div>

                      <button
                        @click="deleteCourse(crs)"
                        class="w-full px-3 py-2 text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-xl flex items-center gap-2 font-medium"
                      >
                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Course
                      </button>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION CONTROLS -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3.5 bg-slate-900/60 border border-slate-800 rounded-2xl text-xs text-slate-400 backdrop-blur-xl">
        <div class="flex items-center gap-3">
          <span>Showing <strong class="text-white">{{ filteredCourses.length === 0 ? 0 : (currentPage - 1) * pageSize + 1 }}</strong> to <strong class="text-white">{{ Math.min(currentPage * pageSize, filteredCourses.length) }}</strong> of <strong class="text-white">{{ filteredCourses.length }}</strong> courses</span>

          <div class="flex items-center gap-1.5 ml-2">
            <span>Per page:</span>
            <select v-model="pageSize" class="bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-white">
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
          </div>
        </div>

        <div class="flex items-center gap-1.5">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-1.5 rounded-xl border border-slate-800 bg-slate-950 font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-800 hover:text-white text-slate-300"
          >
            ‹ Prev
          </button>

          <div class="flex items-center gap-1 px-1">
            <button
              v-for="p in totalPages"
              :key="p"
              @click="currentPage = p"
              class="w-8 h-8 rounded-xl font-bold transition-all flex items-center justify-center"
              :class="currentPage === p ? 'bg-sky-600 text-white' : 'bg-slate-950 border border-slate-800 text-slate-400 hover:text-white'"
            >
              {{ p }}
            </button>
          </div>

          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-1.5 rounded-xl border border-slate-800 bg-slate-950 font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-800 hover:text-white text-slate-300"
          >
            Next ›
          </button>
        </div>
      </div>

      <!-- CREATE / EDIT COURSE MODAL -->
      <div v-if="isModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900/95 border border-slate-700/60 rounded-2xl max-w-xl w-full p-5 space-y-4 shadow-2xl backdrop-blur-2xl overflow-y-auto max-h-[90vh]">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-400 shadow-sm">
                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
              <h3 class="text-sm font-bold text-slate-100 tracking-wide uppercase">
                {{ editingCourse ? 'EDIT COURSE' : 'CREATE COURSE' }}
              </h3>
            </div>
            <button @click="isModalOpen = false" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition-all">✕</button>
          </div>

          <form @submit.prevent="saveCourse" class="space-y-3.5 text-xs">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Course Code</label>
                <input v-model="form.code" type="text" class="w-full bg-slate-950/80 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-sans focus:outline-none focus:ring-1 focus:ring-sky-500/20 transition-all" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Course Title</label>
                <input v-model="form.title" type="text" placeholder="e.g. C Programming Basics" class="w-full bg-slate-950/80 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-sky-500/20 transition-all" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Learning Mode</label>
                <select v-model="form.learning_mode" class="w-full bg-slate-950/80 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-medium focus:outline-none focus:ring-1 focus:ring-sky-500/20 transition-all">
                  <option value="instructor_led">Teacher-Led (Live Class)</option>
                  <option value="self_paced">Self-Study (Videos & PDF)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Main Teacher</label>
                <select v-model="form.teacher" class="w-full bg-slate-950/80 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-medium focus:outline-none focus:ring-1 focus:ring-sky-500/20 transition-all">
                  <option value="Mr. Sophea">Mr. Sophea (IT & Networking)</option>
                  <option value="Ms. Srey">Ms. Srey (English Literature)</option>
                  <option value="Mr. Long">Mr. Long (Tourism Management)</option>
                  <option value="Mr. Vuthy">Mr. Vuthy (Agronomy)</option>
                  <option value="Mr. Rithy">Mr. Rithy (Social Work)</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Payment Type</label>
                <select v-model="form.is_paid" class="w-full bg-slate-950/80 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-medium focus:outline-none focus:ring-1 focus:ring-sky-500/20 transition-all">
                  <option :value="true">Paid Course ($ USD)</option>
                  <option :value="false">Free Course (FREE)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Price ($ USD)</label>
                <input v-model.number="form.price" type="number" :disabled="!form.is_paid" class="w-full bg-slate-950/80 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-amber-300 font-bold font-sans disabled:opacity-40 focus:outline-none focus:ring-1 focus:ring-sky-500/20 transition-all" />
              </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800/80">
              <button type="button" @click="isModalOpen = false" :disabled="isSubmitting" class="px-4 py-2 bg-slate-800/80 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-xl transition-all disabled:opacity-50">Cancel</button>
              <button type="submit" :disabled="isSubmitting" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-sky-600/30 transition-all flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                <svg v-if="isSubmitting" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ isSubmitting ? (editingCourse ? 'កំពុងរក្សាទុក...' : 'កំពុងបង្កើតវគ្គសិក្សា...') : (editingCourse ? 'Save Course' : 'Create Course') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Clean & User-Friendly Toast Notification -->
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
