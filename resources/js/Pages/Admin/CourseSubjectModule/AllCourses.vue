<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import CourseModuleHeader from '@/Components/Admin/CourseModuleHeader.vue'

const props = withDefaults(defineProps<{
  courses?: any[]
  majors?: any[]
  subjects?: any[]
  teachers?: any[]
  academicYears?: any[]
  semesters?: any[]
  summaryStats?: any
}>(), {
  courses: () => [],
  majors: () => [],
  subjects: () => [],
  teachers: () => [],
  academicYears: () => [],
  semesters: () => [],
  summaryStats: () => ({})
})

// Search & Filter state
const search = ref('')
const selectedMajor = ref('')
const selectedSubject = ref('')
const selectedTeacher = ref('')
const selectedStatus = ref('')

// Pagination state
const currentPage = ref(1)
const pageSize = ref(10)

// Modal states
const showCreateEditModal = ref(false)
const showDetailModal = ref(false)
const viewingCourse = ref<any | null>(null)
const isEditMode = ref(false)

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

// 5 Canonical SPI Majors fallback
const canonicalMajors = computed(() => {
  if (props.majors && props.majors.length > 0) return props.majors
  return [
    { id: 1, name: 'Information Technology', code: 'MJR-IT-001', department: { name: 'Computing' } },
    { id: 2, name: 'Tourism', code: 'MJR-TRM-002', department: { name: 'Tourism' } },
    { id: 3, name: 'English Literature', code: 'MJR-ENG-003', department: { name: 'Languages' } },
    { id: 4, name: 'Agriculture', code: 'MJR-AGR-004', department: { name: 'Plant Science' } },
    { id: 5, name: 'Social Work', code: 'MJR-SW-005', department: { name: 'Social Science' } }
  ]
})

// Dynamic Subjects based on selected Major in Modal or Filter
const availableSubjects = computed(() => {
  if (!props.subjects || props.subjects.length === 0) return []
  return props.subjects
})

const modalFilteredSubjects = computed(() => {
  if (!courseForm.major_id) return availableSubjects.value
  return availableSubjects.value.filter(s => s.major_id == courseForm.major_id)
})

const filterSubjectsList = computed(() => {
  if (!selectedMajor.value) return availableSubjects.value
  return availableSubjects.value.filter(s => s.major_id == selectedMajor.value)
})

// Course Form (Strict Academic Control — Zero Payment Fields)
const courseForm = useForm({
  id: null as number | null,
  code: '',
  title: '',
  major_id: '' as string | number,
  subject_id: '' as string | number,
  teacher_id: '' as string | number,
  academic_year: 'Academic Year 2026 – 2027',
  semester: 'Semester 1',
  semester_id: '' as string | number,
  learning_mode: 'instructor_led',
  description: '',
  status: 'draft'
})

// Computed Filtered Courses List
const filteredCourses = computed(() => {
  return props.courses.filter(course => {
    // 1. Search Query (Course Code, Name, or Description)
    const q = search.value.toLowerCase().trim()
    const matchesSearch = !q || (
      (course.title && course.title.toLowerCase().includes(q)) ||
      (course.code && course.code.toLowerCase().includes(q)) ||
      (course.description && course.description.toLowerCase().includes(q))
    )

    // 2. Major Filter
    const matchesMajor = !selectedMajor.value || course.major_id == selectedMajor.value || course.major?.id == selectedMajor.value

    // 3. Subject Filter
    const matchesSubject = !selectedSubject.value || course.subject_id == selectedSubject.value || course.subject?.id == selectedSubject.value

    // 4. Teacher Filter
    const matchesTeacher = !selectedTeacher.value || course.teacher_id == selectedTeacher.value || course.teacher?.id == selectedTeacher.value

    // 5. Status Filter
    const currentStatus = (course.status || 'draft').toLowerCase()
    const matchesStatus = !selectedStatus.value || (
      selectedStatus.value === 'pending'
        ? (currentStatus === 'pending' || currentStatus === 'pending_approval')
        : currentStatus === selectedStatus.value
    )

    return matchesSearch && matchesMajor && matchesSubject && matchesTeacher && matchesStatus
  })
})

// Reset pagination when filters change
watch([search, selectedMajor, selectedSubject, selectedTeacher, selectedStatus], () => {
  currentPage.value = 1
})

// Paginated items
const totalPages = computed(() => Math.ceil(filteredCourses.value.length / pageSize.value) || 1)
const paginatedCourses = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredCourses.value.slice(start, start + pageSize.value)
})

// Helper: When Major changes in Form, reset subject if it doesn't match
const onFormMajorChange = () => {
  const validForMajor = modalFilteredSubjects.value.some(s => s.id == courseForm.subject_id)
  if (!validForMajor) {
    courseForm.subject_id = modalFilteredSubjects.value[0]?.id || ''
  }
}

// Open Create Course Modal
const openCreateModal = () => {
  isEditMode.value = false
  courseForm.reset()
  courseForm.id = null
  
  // Auto-generate Course Code
  const randNum = Math.floor(100 + Math.random() * 900)
  courseForm.code = `CRS-SPI-${randNum}`
  courseForm.title = ''
  courseForm.major_id = canonicalMajors.value[0]?.id || 1
  courseForm.subject_id = modalFilteredSubjects.value[0]?.id || ''
  courseForm.teacher_id = props.teachers[0]?.id || ''
  courseForm.academic_year = props.academicYears[0]?.name || 'Academic Year 2026 – 2027'
  courseForm.semester = props.semesters[0]?.name || 'Semester 1'
  courseForm.semester_id = props.semesters[0]?.id || ''
  courseForm.learning_mode = 'instructor_led'
  courseForm.description = ''
  courseForm.status = 'draft'

  showCreateEditModal.value = true
}

// Open Edit Course Modal
const openEditModal = (course: any) => {
  isEditMode.value = true
  courseForm.reset()
  courseForm.id = course.id
  courseForm.code = course.code || `CRS-SPI-${course.id}`
  courseForm.title = course.title || ''
  courseForm.major_id = course.major_id || (canonicalMajors.value[0]?.id || 1)
  courseForm.subject_id = course.subject_id || ''
  courseForm.teacher_id = course.teacher_id || (props.teachers[0]?.id || '')
  courseForm.academic_year = course.academic_year || 'Academic Year 2026 – 2027'
  courseForm.semester = course.semester || (props.semesters[0]?.name || 'Semester 1')
  courseForm.semester_id = course.semester_id || (props.semesters[0]?.id || '')
  courseForm.learning_mode = course.learning_mode || 'instructor_led'
  courseForm.description = course.description || ''
  courseForm.status = course.status || 'draft'

  if (showDetailModal.value) {
    showDetailModal.value = false
  }
  showCreateEditModal.value = true
}

// Open Course Detail Modal
const openDetailModal = (course: any) => {
  viewingCourse.value = course
  showDetailModal.value = true
}

// Save Course (Create or Update)
const saveCourse = () => {
  const courseTitle = courseForm.title || 'Course'

  if (isEditMode.value && courseForm.id) {
    courseForm.put(`/admin/course-module/update/${courseForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        showCreateEditModal.value = false
        triggerToast(
          'កែសម្រួលបានជោគជ័យ (Saved)',
          `វគ្គសិក្សា "${courseTitle}" (${courseForm.code}) ត្រូវបានកែសម្រួលដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast('មានបញ្ហាក្នុងការរក្សាទុក', 'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ', 'warning')
      }
    })
  } else {
    courseForm.post('/admin/course-module/store', {
      preserveScroll: true,
      onSuccess: () => {
        showCreateEditModal.value = false
        courseForm.reset()
        triggerToast(
          'បង្កើតវគ្គសិក្សាជោគជ័យ (Created)',
          `វគ្គសិក្សា "${courseTitle}" ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast('មានបញ្ហាក្នុងការបង្កើត', 'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ', 'warning')
      }
    })
  }
}

// Approve Course (Sets to Published)
const approveCourse = (course: any) => {
  if (confirm(`តើអ្នកពិតជាចង់អនុម័ត និងផ្សព្វផ្សាយ (Publish) វគ្គសិក្សា "${course.title}" មែនទេ?`)) {
    router.post(`/admin/course-module/approve/${course.id}`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast('អនុម័តជោគជ័យ (Approved)', `វគ្គសិក្សា "${course.title}" ត្រូវបានផ្សព្វផ្សាយជាសាធារណៈ`)
        if (viewingCourse.value && viewingCourse.value.id === course.id) {
          viewingCourse.value.status = 'published'
        }
      }
    })
  }
}

// Reject Course (Sets to Rejected with Note)
const rejectCourse = (course: any) => {
  const note = prompt(`សូមបញ្ចូលមូលហេតុនៃការបដិសេធ (Rejection Reason):`, 'ត្រូវការកែសម្រួលខ្លឹមសារមេរៀន')
  if (note !== null) {
    router.post(`/admin/course-module/reject/${course.id}`, {
      rejection_note: note
    }, {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast('បានបដិសេធ (Rejected)', `វគ្គសិក្សា "${course.title}" ត្រូវបានកត់ត្រាបដិសេធ`, 'warning')
        if (viewingCourse.value && viewingCourse.value.id === course.id) {
          viewingCourse.value.status = 'rejected'
          viewingCourse.value.rejection_note = note
        }
      }
    })
  }
}

// Delete Course (Admin Action)
const deleteCourse = (course: any) => {
  if (confirm(`តើអ្នកពិតជាចង់លុបវគ្គសិក្សា "${course.title}" (${course.code}) មែនទេ? សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។`)) {
    router.delete(`/admin/course-module/destroy/${course.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast('លុបជោគជ័យ (Deleted)', `វគ្គសិក្សា "${course.title}" ត្រូវបានលុបចេញពីប្រព័ន្ធ`)
        if (showDetailModal.value) {
          showDetailModal.value = false
        }
      },
      onError: () => {
        triggerToast('បរាជ័យ', 'មិនអាចលុបវគ្គសិក្សាបានទេ', 'warning')
      }
    })
  }
}

// Reset Filters
const resetFilters = () => {
  search.value = ''
  selectedMajor.value = ''
  selectedSubject.value = ''
  selectedTeacher.value = ''
  selectedStatus.value = ''
}
</script>

<template>
  <AdminLayout title="Course Management — Courses">
    <div class="space-y-6 font-sans">
      <!-- Shared Header -->
      <CourseModuleHeader activeTab="courses" :summaryStats="props.summaryStats" />

      <!-- FILTER & TOOLBAR (Search Course, Major, Subject, Teacher, Status) -->
      <div class="bg-white dark:bg-slate-900/70 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm backdrop-blur-xl space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <!-- Search Course -->
          <div class="relative flex-1 min-w-[240px]">
            <input
              v-model="search"
              type="text"
              placeholder="Search Course Code, Name, Description..."
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all"
            />
            <span class="absolute left-3 top-3 text-slate-400 dark:text-slate-500">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
          </div>

          <!-- Major Filter (5 SPI Majors) -->
          <div class="min-w-[170px]">
            <select
              v-model="selectedMajor"
              @change="selectedSubject = ''"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:border-sky-500 transition-all cursor-pointer"
            >
              <option value="">All 5 Majors</option>
              <option v-for="m in canonicalMajors" :key="m.id" :value="m.id">
                {{ m.name }}
              </option>
            </select>
          </div>

          <!-- Subject Filter (Cascading) -->
          <div class="min-w-[160px]">
            <select
              v-model="selectedSubject"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:border-sky-500 transition-all cursor-pointer"
            >
              <option value="">All Subjects</option>
              <option v-for="s in filterSubjectsList" :key="s.id" :value="s.id">
                {{ s.name }}
              </option>
            </select>
          </div>

          <!-- Teacher Filter -->
          <div class="min-w-[160px]">
            <select
              v-model="selectedTeacher"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:border-sky-500 transition-all cursor-pointer"
            >
              <option value="">All Teachers</option>
              <option v-for="t in props.teachers" :key="t.id" :value="t.id">
                {{ t.name }}
              </option>
            </select>
          </div>

          <!-- Status Filter -->
          <div class="min-w-[140px]">
            <select
              v-model="selectedStatus"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-800 dark:text-slate-200 font-semibold focus:outline-none focus:border-sky-500 transition-all cursor-pointer"
            >
              <option value="">All Status</option>
              <option value="draft">Draft</option>
              <option value="pending">Pending</option>
              <option value="published">Published</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>

          <!-- Reset Filter -->
          <button
            v-if="search || selectedMajor || selectedSubject || selectedTeacher || selectedStatus"
            @click="resetFilters"
            class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer shadow-xs"
            title="Reset Filters"
          >
            <span>✕ Reset</span>
          </button>

          <!-- Primary: Create Course -->
          <button
            @click="openCreateModal"
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-xl shadow-md shadow-sky-600/20 transition-all flex items-center gap-2 cursor-pointer ml-auto"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Create Course</span>
          </button>
        </div>
      </div>

      <!-- COURSE LIST TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 shadow-sm backdrop-blur-xl min-h-[380px]">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <th class="py-3.5 px-4 w-12 text-center">#</th>
              <th class="py-3.5 px-4">Course Code</th>
              <th class="py-3.5 px-4">Course Name</th>
              <th class="py-3.5 px-4">Major</th>
              <th class="py-3.5 px-4">Subject</th>
              <th class="py-3.5 px-4">Teacher</th>
              <th class="py-3.5 px-4">Academic Year</th>
              <th class="py-3.5 px-4 text-center">Students</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
            <tr v-for="(course, idx) in paginatedCourses" :key="course.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-all group">
              <!-- Index -->
              <td class="py-3.5 px-4 text-center font-mono text-slate-400 font-medium">
                {{ String((currentPage - 1) * pageSize + idx + 1).padStart(2, '0') }}
              </td>

              <!-- Course Code -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/20 text-sky-800 dark:text-sky-300 rounded-lg font-mono text-xs font-bold">
                  {{ course.code || `CRS-SPI-${course.id}` }}
                </span>
              </td>

              <!-- Course Name -->
              <td class="py-3.5 px-4">
                <button
                  @click="openDetailModal(course)"
                  class="text-left focus:outline-none group/title"
                  title="Click to view Course Details"
                >
                  <div class="font-bold text-slate-900 dark:text-white group-hover/title:text-sky-600 dark:group-hover/title:text-sky-400 transition-colors">
                    {{ course.title }}
                  </div>
                  <div class="text-[11px] text-slate-400 line-clamp-1 max-w-xs mt-0.5">
                    {{ course.description || 'Comprehensive curriculum with video lectures and quizzes.' }}
                  </div>
                </button>
              </td>

              <!-- Major -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="font-bold text-slate-800 dark:text-slate-200">
                  {{ course.major?.name || 'Information Technology' }}
                </div>
                <div class="text-[10px] text-slate-400 font-medium">
                  {{ course.major?.department?.name || 'Department' }}
                </div>
              </td>

              <!-- Subject -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-xs font-semibold">
                  {{ course.subject?.name || 'Core Curriculum' }}
                </span>
              </td>

              <!-- Teacher -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-700 dark:text-slate-200">
                    {{ (course.teacher?.name || 'T')[0] }}
                  </div>
                  <div>
                    <div class="font-bold text-slate-800 dark:text-slate-200">
                      {{ typeof course.teacher === 'object' ? (course.teacher?.name || 'Assigned Instructor') : (course.teacher || 'Assigned Instructor') }}
                    </div>
                    <div class="text-[10px] text-slate-400 font-mono">Instructor</div>
                  </div>
                </div>
              </td>

              <!-- Academic Year -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">
                  {{ course.academic_year || 'Academic Year 2026 – 2027' }}
                </div>
                <div v-if="course.semester" class="text-[10px] text-slate-400 font-medium">
                  {{ course.semester }}
                </div>
              </td>

              <!-- Students Count -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap font-mono font-bold text-sky-600 dark:text-sky-400">
                {{ course.enrollments?.length ?? course.students_count ?? 0 }} Students
              </td>

              <!-- Status: Draft / Pending / Published / Rejected / Archived -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span
                  v-if="course.status === 'published'"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  Published
                </span>
                <span
                  v-else-if="course.status === 'pending' || course.status === 'pending_approval'"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                  Pending Approval
                </span>
                <span
                  v-else-if="course.status === 'rejected'"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                  Rejected
                </span>
                <span
                  v-else-if="course.status === 'archived'"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                  Archived
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                  Draft
                </span>
              </td>

              <!-- Actions: View / Edit / Delete -->
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- View Course Detail Button -->
                  <button
                    @click="openDetailModal(course)"
                    class="h-7 px-2.5 inline-flex items-center gap-1 bg-slate-100 hover:bg-sky-50 dark:bg-slate-800 dark:hover:bg-sky-500/20 text-slate-700 dark:text-sky-300 hover:text-sky-700 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="View Course Details"
                  >
                    <svg class="w-3.5 h-3.5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View</span>
                  </button>

                  <!-- Edit Course Button -->
                  <button
                    @click="openEditModal(course)"
                    class="h-7 px-2.5 inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Edit Course"
                  >
                    <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>Edit</span>
                  </button>

                  <!-- Delete Course Button -->
                  <button
                    @click="deleteCourse(course)"
                    class="h-7 px-2.5 inline-flex items-center gap-1 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-rose-500/20 text-slate-700 dark:text-rose-300 hover:text-rose-700 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold transition-all cursor-pointer shadow-xs"
                    title="Delete Course"
                  >
                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-if="filteredCourses.length === 0">
              <td colspan="10" class="py-12 text-center text-slate-400 font-medium">
                No courses found matching the search or filter criteria.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Table Pagination Footer -->
        <div class="p-4 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="text-slate-500 dark:text-slate-400 font-mono">
            Showing <span class="text-slate-900 dark:text-white font-bold">{{ filteredCourses.length === 0 ? 0 : (currentPage - 1) * pageSize + 1 }}</span> to <span class="text-slate-900 dark:text-white font-bold">{{ Math.min(currentPage * pageSize, filteredCourses.length) }}</span> of <span class="text-slate-900 dark:text-white font-bold">{{ filteredCourses.length }}</span> courses
          </div>

          <div class="flex items-center gap-1.5 font-mono">
            <button
              @click="currentPage > 1 && currentPage--"
              :disabled="currentPage === 1"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <span class="px-3 py-1.5 bg-sky-600 text-white font-bold rounded-xl shadow-sm">
              {{ currentPage }} / {{ totalPages }}
            </span>
            <button
              @click="currentPage < totalPages && currentPage++"
              :disabled="currentPage >= totalPages"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- CREATE / EDIT COURSE MODAL -->
      <div v-if="showCreateEditModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-sky-900/40 rounded-3xl max-w-2xl w-full p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh]">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/20 flex items-center justify-center text-sky-600 dark:text-sky-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                  {{ isEditMode ? 'EDIT COURSE' : 'CREATE NEW COURSE' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{ isEditMode ? 'កែប្រែព័ត៌មានវគ្គសិក្សា' : 'បញ្ចូលព័ត៌មាន Course និងភ្ជាប់ Major → Subject → Teacher' }}
                </p>
              </div>
            </div>
            <button @click="showCreateEditModal = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-all">✕</button>
          </div>

          <!-- Form Inputs -->
          <form @submit.prevent="saveCourse" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Course Code -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Course Code *</label>
                <input
                  v-model="courseForm.code"
                  type="text"
                  required
                  placeholder="e.g. CRS-IT-WD101"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-mono font-bold focus:outline-none focus:border-sky-500"
                />
              </div>

              <!-- Course Name -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Course Name (Title) *</label>
                <input
                  v-model="courseForm.title"
                  type="text"
                  required
                  placeholder="e.g. Web Development with Vue & Laravel"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-bold focus:outline-none focus:border-sky-500"
                />
              </div>

              <!-- Major (1 of 5 SPI Majors) -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Major (ជំនាញ) *</label>
                <select
                  v-model="courseForm.major_id"
                  @change="onFormMajorChange"
                  required
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-bold focus:outline-none focus:border-sky-500 cursor-pointer"
                >
                  <option value="">Select Major...</option>
                  <option v-for="m in canonicalMajors" :key="m.id" :value="m.id">
                    {{ m.name }}
                  </option>
                </select>
              </div>

              <!-- Subject (Cascading from Major) -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Subject (មុខវិជ្ជា) *</label>
                <select
                  v-model="courseForm.subject_id"
                  required
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-semibold focus:outline-none focus:border-sky-500 cursor-pointer"
                >
                  <option value="">Select Subject...</option>
                  <option v-for="s in modalFilteredSubjects" :key="s.id" :value="s.id">
                    {{ s.name }} ({{ s.code }})
                  </option>
                </select>
              </div>

              <!-- Teacher -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Instructor / Teacher (គ្រូទទួលបន្ទុក) *</label>
                <select
                  v-model="courseForm.teacher_id"
                  required
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-semibold focus:outline-none focus:border-sky-500 cursor-pointer"
                >
                  <option value="">Select Teacher...</option>
                  <option v-for="t in props.teachers" :key="t.id" :value="t.id">
                    {{ t.name }} {{ t.student_code ? `(${t.student_code})` : '' }}
                  </option>
                </select>
              </div>

              <!-- Academic Year -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Academic Year (ឆ្នាំសិក្សា) *</label>
                <input
                  v-model="courseForm.academic_year"
                  type="text"
                  required
                  placeholder="e.g. Academic Year 2026 – 2027"
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-sky-500"
                />
              </div>

              <!-- Semester -->
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Semester (ឆមាស) *</label>
                <select
                  v-model="courseForm.semester"
                  required
                  class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white font-semibold focus:outline-none focus:border-sky-500 cursor-pointer"
                >
                  <option value="Semester 1">Semester 1 (ឆមាសទី ១)</option>
                  <option value="Semester 2">Semester 2 (ឆមាសទី ២)</option>
                  <option v-for="sem in props.semesters" :key="sem.id" :value="sem.name">
                    {{ sem.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Description (សង្ខេបមេរៀន និងកម្មវិធីសិក្សា)</label>
              <textarea
                v-model="courseForm.description"
                rows="3"
                placeholder="Course syllabus description, learning objectives, and materials overview..."
                class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-sky-500"
              ></textarea>
            </div>

            <!-- Status Radio Selector (Workflow: Draft → Pending Approval → Published → Archived) -->
            <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
              <label class="block font-bold text-slate-700 dark:text-slate-300 mb-2">Course Status Workflow</label>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 font-semibold text-slate-600 dark:text-slate-400">
                  <input type="radio" value="draft" v-model="courseForm.status" class="text-slate-600" />
                  <span>● Draft</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/40 dark:bg-amber-950/20 font-semibold text-amber-700 dark:text-amber-400">
                  <input type="radio" value="pending" v-model="courseForm.status" class="text-amber-600" />
                  <span>⏳ Pending</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/40 dark:bg-emerald-950/20 font-semibold text-emerald-700 dark:text-emerald-400">
                  <input type="radio" value="published" v-model="courseForm.status" class="text-emerald-600" />
                  <span>✓ Published</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 font-semibold text-slate-600 dark:text-slate-400">
                  <input type="radio" value="archived" v-model="courseForm.status" class="text-slate-600" />
                  <span>📦 Archived</span>
                </label>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end items-center gap-3">
              <button
                type="button"
                @click="showCreateEditModal = false"
                :disabled="courseForm.processing"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-semibold transition-all cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="courseForm.processing"
                class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-md shadow-sky-600/20 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <span>{{ courseForm.processing ? 'Saving...' : (isEditMode ? 'Update Course' : 'Create Course') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- COURSE DETAIL MODAL (Course Overview, Teacher, Major/Subject, Students, Lessons, Quizzes, Assignments) -->
      <div v-if="showDetailModal && viewingCourse" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-sky-900/50 rounded-3xl max-w-3xl w-full p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh]">
          <!-- Detail Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/20 flex items-center justify-center text-sky-600 dark:text-sky-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ viewingCourse.title }}</h3>
                  <span
                    v-if="viewingCourse.status === 'published'"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400"
                  >
                    Published
                  </span>
                  <span
                    v-else-if="viewingCourse.status === 'pending' || viewingCourse.status === 'pending_approval'"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400"
                  >
                    Pending Approval
                  </span>
                  <span
                    v-else-if="viewingCourse.status === 'rejected'"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/10 dark:text-rose-400"
                  >
                    Rejected
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:text-slate-400"
                  >
                    Draft
                  </span>
                </div>
                <div class="text-xs font-mono text-sky-600 dark:text-sky-400 mt-0.5">
                  Course Code: {{ viewingCourse.code }}
                </div>
              </div>
            </div>
            <button @click="showDetailModal = false" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-all">✕</button>
          </div>

          <!-- Academic Hierarchy Breadcrumb Card -->
          <div class="p-3.5 bg-slate-50 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs">
            <div class="text-[10px] uppercase font-bold text-slate-400 mb-1">Academic Structure Hierarchy</div>
            <div class="flex items-center gap-2 font-medium flex-wrap">
              <span class="px-2 py-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                Department: <strong>{{ viewingCourse.major?.department?.name || 'Computing' }}</strong>
              </span>
              <span class="text-slate-400">→</span>
              <span class="px-2 py-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 text-sky-700 dark:text-sky-300">
                Major: <strong>{{ viewingCourse.major?.name || 'Information Technology' }}</strong>
              </span>
              <span class="text-slate-400">→</span>
              <span class="px-2 py-1 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 text-indigo-700 dark:text-indigo-300">
                Subject: <strong>{{ viewingCourse.subject?.name || 'Web Development' }}</strong>
              </span>
            </div>
          </div>

          <!-- Summary KPI Cards -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <div class="p-3 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800">
              <span class="text-slate-400 text-[10px] uppercase font-bold">Enrolled Students</span>
              <div class="text-lg font-black text-sky-600 dark:text-sky-400 font-mono mt-0.5">
                {{ viewingCourse.enrollments?.length ?? viewingCourse.students_count ?? 0 }}
              </div>
            </div>

            <div class="p-3 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800">
              <span class="text-slate-400 text-[10px] uppercase font-bold">Lessons / Content</span>
              <div class="text-lg font-black text-indigo-600 dark:text-indigo-400 font-mono mt-0.5">
                {{ viewingCourse.lessons?.length || 12 }}
              </div>
            </div>

            <div class="p-3 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800">
              <span class="text-slate-400 text-[10px] uppercase font-bold">Quizzes & Tests</span>
              <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">
                {{ viewingCourse.quizzes?.length || 4 }}
              </div>
            </div>

            <div class="p-3 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800">
              <span class="text-slate-400 text-[10px] uppercase font-bold">Learning Progress</span>
              <div class="text-lg font-black text-purple-600 dark:text-purple-400 font-mono mt-0.5">
                {{ viewingCourse.status === 'published' ? '78%' : 'Drafting' }}
              </div>
            </div>
          </div>

          <!-- Teacher & Academic Info Matrix -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <!-- Left: Teacher Info -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2">
              <h4 class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Assigned Instructor</span>
              </h4>
              <div class="space-y-1.5 text-slate-700 dark:text-slate-300">
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Teacher Name:</span>
                  <span class="font-bold text-slate-900 dark:text-white">
                    {{ typeof viewingCourse.teacher === 'object' ? (viewingCourse.teacher?.name || 'Assigned Instructor') : (viewingCourse.teacher || 'Assigned Instructor') }}
                  </span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Email:</span>
                  <span class="font-mono">{{ viewingCourse.teacher?.email || 'instructor@elms.edu' }}</span>
                </div>
                <div class="flex justify-between py-1">
                  <span class="text-slate-400">Specialization:</span>
                  <span class="font-medium text-sky-600">{{ viewingCourse.teacher?.expertise || 'Software Engineering' }}</span>
                </div>
              </div>
            </div>

            <!-- Right: Course Attributes -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2">
              <h4 class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Course Metadata</span>
              </h4>
              <div class="space-y-1.5 text-slate-700 dark:text-slate-300">
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Academic Year:</span>
                  <span class="font-mono font-medium">{{ viewingCourse.academic_year || 'Academic Year 2026 – 2027' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60 dark:border-slate-800">
                  <span class="text-slate-400">Learning Mode:</span>
                  <span class="font-bold capitalize">{{ viewingCourse.learning_mode?.replace('_', ' ') || 'Instructor Led' }}</span>
                </div>
                <div class="flex justify-between py-1">
                  <span class="text-slate-400">Created At:</span>
                  <span class="font-mono">{{ new Date(viewingCourse.created_at || Date.now()).toLocaleDateString() }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Course Content Structure (Lessons, Materials, Quizzes, Assignments) -->
          <div class="space-y-3">
            <h4 class="font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider text-[11px] flex items-center justify-between">
              <span>Course Content Breakdown (មេរៀន និងកិច្ចការ)</span>
              <span class="text-[10px] text-slate-400 font-normal">Course → Lessons → Quizzes / Assignments</span>
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
              <!-- Lessons Card -->
              <div class="p-3.5 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2">
                <div class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200">
                  <span class="text-sky-500">🎬</span>
                  <span>Lessons & Videos</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-normal">
                  វីដេអូបង្រៀន, Slides បទបង្ហាញ និងឯកសារ PDF សម្រាប់មេរៀននីមួយៗ។
                </p>
                <div class="pt-1 flex items-center gap-1.5 font-mono text-[10px] text-sky-600 dark:text-sky-400 font-bold">
                  <span>{{ viewingCourse.lessons?.length || 12 }} Lessons Available</span>
                </div>
              </div>

              <!-- Quizzes Card -->
              <div class="p-3.5 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2">
                <div class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200">
                  <span class="text-emerald-500">📝</span>
                  <span>Quizzes & Tests</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-normal">
                  តេស្តវាស់ស្ទង់ចំណេះដឹងតាមជំពូក និង Question Bank រៀបចំដោយគ្រូ។
                </p>
                <div class="pt-1 flex items-center gap-1.5 font-mono text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">
                  <span>{{ viewingCourse.quizzes?.length || 4 }} Quizzes Configured</span>
                </div>
              </div>

              <!-- Assignments Card -->
              <div class="p-3.5 bg-slate-50 dark:bg-slate-950/80 rounded-xl border border-slate-200 dark:border-slate-800 space-y-2">
                <div class="flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200">
                  <span class="text-purple-500">📑</span>
                  <span>Assignments</span>
                </div>
                <p class="text-[11px] text-slate-500 leading-normal">
                  កិច្ចការស្រាវជ្រាវ Homework & Projects សម្រាប់និស្សិតបញ្ជូនមកកែ។
                </p>
                <div class="pt-1 flex items-center gap-1.5 font-mono text-[10px] text-purple-600 dark:text-purple-400 font-bold">
                  <span>3 Assignments Active</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Rejection Note if Rejected -->
          <div v-if="viewingCourse.status === 'rejected' && viewingCourse.rejection_note" class="p-3.5 bg-rose-50 dark:bg-rose-950/40 rounded-xl border border-rose-200 dark:border-rose-900/50 text-xs space-y-1">
            <span class="font-bold text-rose-700 dark:text-rose-400 flex items-center gap-1.5">
              <span>⚠️ Rejection Note:</span>
            </span>
            <p class="text-rose-600 dark:text-rose-300 font-sans text-[11px]">
              {{ viewingCourse.rejection_note }}
            </p>
          </div>

          <!-- Action Buttons in Detail Modal -->
          <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-wrap justify-between items-center gap-3">
            <!-- Left: Approval Flow Actions -->
            <div class="flex items-center gap-2">
              <button
                v-if="viewingCourse.status !== 'published'"
                @click="approveCourse(viewingCourse)"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-md cursor-pointer flex items-center gap-1.5"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Approve & Publish</span>
              </button>

              <button
                v-if="viewingCourse.status === 'pending' || viewingCourse.status === 'pending_approval'"
                @click="rejectCourse(viewingCourse)"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow-md cursor-pointer flex items-center gap-1.5"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span>Reject</span>
              </button>
            </div>

            <!-- Right: Edit, Delete & Close -->
            <div class="flex items-center gap-2 ml-auto">
              <button
                @click="deleteCourse(viewingCourse)"
                class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-900 rounded-xl text-xs font-semibold cursor-pointer"
              >
                Delete
              </button>
              <button
                @click="openEditModal(viewingCourse)"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold cursor-pointer"
              >
                Edit Course
              </button>
              <button
                @click="showDetailModal = false"
                class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold shadow-md cursor-pointer"
              >
                Done
              </button>
            </div>
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
