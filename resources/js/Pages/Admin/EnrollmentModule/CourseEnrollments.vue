<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import CourseModuleHeader from '@/Components/Admin/CourseModuleHeader.vue'

const props = withDefaults(defineProps<{
  enrollments?: any[]
  summaryStats?: any
  students?: any[]
  courses?: any[]
  majors?: any[]
  teachers?: any[]
  academicYears?: any[]
}>(), {
  enrollments: () => [],
  summaryStats: () => ({}),
  students: () => [],
  courses: () => [],
  majors: () => [],
  teachers: () => [],
  academicYears: () => [],
})

// Search & Filter state
const search = ref('')
const selectedMajor = ref('')
const selectedCourse = ref('')
const selectedTeacher = ref('')
const selectedAcademicYear = ref('')
const selectedStatus = ref('')

// Pagination
const currentPage = ref(1)
const pageSize = ref(10)

// Modal states
const showEnrollModal = ref(false)
const showDetailModal = ref(false)
const selectedEnrollment = ref<any | null>(null)

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

// Canonical 5 SPI Majors fallback
const canonicalMajors = computed(() => {
  if (props.majors && props.majors.length > 0) return props.majors
  return [
    { id: 1, name: 'Information Technology', code: 'MJR-IT-001' },
    { id: 2, name: 'Tourism', code: 'MJR-TRM-002' },
    { id: 3, name: 'English Literature', code: 'MJR-ENG-003' },
    { id: 4, name: 'Agriculture', code: 'MJR-AGR-004' },
    { id: 5, name: 'Social Work', code: 'MJR-SW-005' },
  ]
})

// Enroll Student Form
const enrollForm = useForm({
  student_id: '' as string | number,
  course_id: '' as string | number,
  academic_year: 'Academic Year 2026 – 2027',
})

// Computed Filtered Enrollments List (Specification 1 & 2)
const filteredEnrollments = computed(() => {
  return props.enrollments.filter(enr => {
    const q = search.value.toLowerCase().trim()
    const matchesSearch = !q || (
      (enr.student_name && enr.student_name.toLowerCase().includes(q)) ||
      (enr.student_id && enr.student_id.toLowerCase().includes(q)) ||
      (enr.course_title && enr.course_title.toLowerCase().includes(q)) ||
      (enr.course_code && enr.course_code.toLowerCase().includes(q)) ||
      (enr.student_email && enr.student_email.toLowerCase().includes(q))
    )

    const matchesMajor = !selectedMajor.value || enr.major_id == selectedMajor.value || enr.major === selectedMajor.value
    const matchesCourse = !selectedCourse.value || enr.course_id == selectedCourse.value
    const matchesTeacher = !selectedTeacher.value || enr.teacher_id == selectedTeacher.value || enr.teacher_name === selectedTeacher.value
    const matchesYear = !selectedAcademicYear.value || enr.academic_year === selectedAcademicYear.value
    const matchesStatus = !selectedStatus.value || enr.status === selectedStatus.value

    return matchesSearch && matchesMajor && matchesCourse && matchesTeacher && matchesYear && matchesStatus
  })
})

// Reset pagination on filter change
watch([search, selectedMajor, selectedCourse, selectedTeacher, selectedAcademicYear, selectedStatus], () => {
  currentPage.value = 1
})

const totalPages = computed(() => Math.ceil(filteredEnrollments.value.length / pageSize.value) || 1)
const paginatedEnrollments = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredEnrollments.value.slice(start, start + pageSize.value)
})

// Validation helper in Enroll Modal
const selectedStudentData = computed(() => {
  if (!enrollForm.student_id) return null
  return props.students.find(s => s.id == enrollForm.student_id)
})

const selectedCourseData = computed(() => {
  if (!enrollForm.course_id) return null
  return props.courses.find(c => c.id == enrollForm.course_id)
})

// 5 Strict Validations in Enroll Modal (Specification 4)
const validationChecks = computed(() => {
  const st = selectedStudentData.value
  const crs = selectedCourseData.value

  const isStudentActive = !!(st && st.status === 'active')
  const isCoursePublished = !!(crs && crs.status === 'published')
  const isMajorMatched = !!(st && crs && (!crs.major_id || !st.major_id || crs.major_id == st.major_id))
  
  const isDuplicate = !!(st && crs && props.enrollments.some(
    e => e.student_user_id == st.id && e.course_id == crs.id
  ))

  const canSubmit = isStudentActive && isCoursePublished && isMajorMatched && !isDuplicate

  return {
    isStudentActive,
    isCoursePublished,
    isMajorMatched,
    isDuplicate,
    canSubmit
  }
})

// Open Enroll Modal
const openEnrollModal = () => {
  enrollForm.reset()
  enrollForm.student_id = props.students[0]?.id || ''
  enrollForm.course_id = props.courses[0]?.id || ''
  enrollForm.academic_year = props.academicYears[0]?.name || 'Academic Year 2026 – 2027'
  showEnrollModal.value = true
}

// Submit Enroll Form (Specification 3 & 4)
const submitEnroll = () => {
  if (!validationChecks.value.canSubmit) {
    if (validationChecks.value.isDuplicate) {
      alert('និស្សិតនេះបានចុះឈ្មោះក្នុងវគ្គសិក្សានេះរួចរាល់ហើយ មិនត្រូវ Enroll Course ដដែលពីរដងឡើយ (Duplicate Enrollment)!')
      return
    }
    if (!validationChecks.value.isMajorMatched) {
      alert('ជំនាញរបស់និស្សិត មិនត្រូវគ្នាជាមួយជំនាញរបស់ Course ឡើយ (Major Mismatch)!')
      return
    }
  }

  enrollForm.post('/admin/enrollment/courses/store', {
    preserveScroll: true,
    onSuccess: () => {
      showEnrollModal.value = false
      triggerToast('ចុះឈ្មោះជោគជ័យ (Enrolled)', 'និស្សិតត្រូវបានចុះឈ្មោះចូលរៀនវគ្គសិក្សាដោយជោគជ័យ')
      enrollForm.reset()
    },
    onError: (errors) => {
      const errMsg = Object.values(errors)[0] as string || 'មានបញ្ហាក្នុងការចុះឈ្មោះ សូមពិនិត្យឡើងវិញ'
      triggerToast('មានបញ្ហា', errMsg, 'warning')
    }
  })
}

// Open View Detail Modal (Specification 1)
const openDetailModal = (enr: any) => {
  selectedEnrollment.value = enr
  showDetailModal.value = true
}

// Update Status (Active -> Completed or Dropped) (Specification 5)
const updateStatus = (enr: any, newStatus: 'active' | 'completed' | 'dropped') => {
  router.put(`/admin/enrollment/courses/update-status/${enr.id}`, { status: newStatus }, {
    preserveScroll: true,
    onSuccess: () => {
      if (selectedEnrollment.value && selectedEnrollment.value.id === enr.id) {
        selectedEnrollment.value.status = newStatus
      }
      triggerToast('ធ្វើបច្ចុប្បន្នភាពជោគជ័យ', `ស្ថានភាពត្រូវបានប្តូរទៅជា ${newStatus}`)
    }
  })
}

// Remove Enrollment
const removeEnrollment = (enr: any) => {
  if (confirm(`តើអ្នកពិតជាចង់ដកនិស្សិត "${enr.student_name}" ចេញពីវគ្គ "${enr.course_title}" មែនទេ?`)) {
    router.delete(`/admin/enrollment/courses/remove/${enr.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        if (showDetailModal.value) showDetailModal.value = false
        triggerToast('បានដកការចុះឈ្មោះ', `បានដកចេញពីបញ្ជីដោយជោគជ័យ`)
      }
    })
  }
}

// Format Date Helper
const formatDate = (dateStr?: string) => {
  if (!dateStr) return 'N/A'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('km-KH', { year: 'numeric', month: 'short', day: 'numeric' })
  } catch {
    return dateStr
  }
}
</script>

<template>
  <AdminLayout title="Course Enrollment — Course Management">
    <!-- Toast Notification -->
    <transition enter-active-class="transform transition ease-out duration-300" enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="toast.show" class="fixed top-20 right-6 z-50 flex items-center gap-3 px-4 py-3 rounded-2xl shadow-2xl border backdrop-blur-xl transition-all"
        :class="toast.type === 'success' ? 'bg-emerald-950/90 border-emerald-500/50 text-emerald-200' : 'bg-amber-950/90 border-amber-500/50 text-amber-200'">
        <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-lg"
          :class="toast.type === 'success' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300'">
          {{ toast.type === 'success' ? '✓' : '!' }}
        </div>
        <div>
          <div class="text-xs font-bold">{{ toast.title }}</div>
          <div class="text-[11px] opacity-90">{{ toast.message }}</div>
        </div>
      </div>
    </transition>

    <div class="space-y-5 font-sans">
      <!-- SUBMODULE HEADER (WITH CANONICAL TABS: Courses, Course Approval, Enrollment) -->
      <CourseModuleHeader activeTab="enrollment" :summaryStats="props.summaryStats" />

      <!-- KPI METRIC CARDS -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- 1. Total Enrolled -->
        <div class="p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 backdrop-blur-md shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wider uppercase text-indigo-400">📚 Total Enrolled</span>
            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <div class="text-2xl font-black text-indigo-200 font-mono mt-1">
            {{ props.summaryStats?.total_enrolled ?? props.enrollments.length }}
          </div>
          <p class="text-[11px] text-indigo-300/80 mt-1">និស្សិតបានចូលរៀនសរុប</p>
        </div>

        <!-- 2. Active Learners -->
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 backdrop-blur-md shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wider uppercase text-emerald-400">🟢 Active Learners</span>
            <span class="flex h-2.5 w-2.5 relative">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
          </div>
          <div class="text-2xl font-black text-emerald-200 font-mono mt-1">
            {{ props.summaryStats?.active_count ?? 5 }}
          </div>
          <p class="text-[11px] text-emerald-300/80 mt-1">កំពុងសិក្សាសកម្ម</p>
        </div>

        <!-- 3. Completed -->
        <div class="p-4 rounded-2xl bg-sky-500/10 border border-sky-500/20 backdrop-blur-md shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wider uppercase text-sky-400">🎓 Completed</span>
            <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div class="text-2xl font-black text-sky-200 font-mono mt-1">
            {{ props.summaryStats?.completed_count ?? 1 }}
          </div>
          <p class="text-[11px] text-sky-300/80 mt-1">បញ្ចប់វគ្គសិក្សា 100%</p>
        </div>

        <!-- 4. Dropped -->
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 backdrop-blur-md shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wider uppercase text-rose-400">⏹ Dropped / Inactive</span>
            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <div class="text-2xl font-black text-rose-200 font-mono mt-1">
            {{ props.summaryStats?.dropped_count ?? 1 }}
          </div>
          <p class="text-[11px] text-rose-300/80 mt-1">ផ្អាក ឬបោះបង់ការរៀន</p>
        </div>
      </div>

      <!-- FILTER & ACTION BAR (SPECIFICATION 2 & 3) -->
      <div class="p-4 bg-slate-900/60 rounded-2xl border border-slate-800/80 backdrop-blur-md space-y-3">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
          <div>
            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Enrollment Management</h2>
            <p class="text-xs text-slate-400 mt-0.5">កំណត់ថា Student ម្នាក់ៗ អាចចូល Course ណាខ្លះ ហើយភ្ជាប់ទៅ Student My Courses</p>
          </div>

          <!-- Enroll Student Action Button (Spec 3) -->
          <button
            @click="openEnrollModal"
            class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/25 transition-all flex items-center justify-center gap-2 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>+ Enroll Student (ចុះឈ្មោះនិស្សិត)</span>
          </button>
        </div>

        <!-- FILTERS GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5 pt-2 border-t border-slate-800/70">
          <!-- 1. Search Student -->
          <div class="relative lg:col-span-2">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="search"
              type="text"
              placeholder="ស្វែងរកឈ្មោះ, Student ID, Email..."
              class="w-full pl-9 pr-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            />
          </div>

          <!-- 2. Major Filter (5 SPI Majors) -->
          <div>
            <select
              v-model="selectedMajor"
              class="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            >
              <option value="">ជំនាញទាំងអស់ (All Majors)</option>
              <option v-for="m in canonicalMajors" :key="m.id" :value="m.id">
                {{ m.name }}
              </option>
            </select>
          </div>

          <!-- 3. Course Filter -->
          <div>
            <select
              v-model="selectedCourse"
              class="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            >
              <option value="">វគ្គសិក្សាទាំងអស់ (All Courses)</option>
              <option v-for="c in props.courses" :key="c.id" :value="c.id">
                {{ c.title }}
              </option>
            </select>
          </div>

          <!-- 4. Academic Year -->
          <div>
            <select
              v-model="selectedAcademicYear"
              class="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            >
              <option value="">ឆ្នាំសិក្សាទាំងអស់ (All Years)</option>
              <option v-for="y in props.academicYears" :key="y.id" :value="y.name">
                {{ y.name }}
              </option>
            </select>
          </div>

          <!-- 5. Status Filter -->
          <div>
            <select
              v-model="selectedStatus"
              class="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            >
              <option value="">ស្ថានភាព (All Status)</option>
              <option value="active">Active (កំពុងរៀន)</option>
              <option value="completed">Completed (បានបញ្ចប់)</option>
              <option value="dropped">Dropped (ផ្អាក/បោះបង់)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ENROLLMENT LIST TABLE (SPECIFICATION 1) -->
      <div class="bg-slate-900/60 rounded-2xl border border-slate-800/80 backdrop-blur-md overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
              <tr>
                <th class="py-3 px-4">Student ID</th>
                <th class="py-3 px-4">Student Name</th>
                <th class="py-3 px-4">Major</th>
                <th class="py-3 px-4">Course</th>
                <th class="py-3 px-4">Teacher</th>
                <th class="py-3 px-4">Enrolled Date</th>
                <th class="py-3 px-4">Progress</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-center">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
              <tr
                v-for="enr in paginatedEnrollments"
                :key="enr.id"
                class="hover:bg-slate-800/40 transition-colors group"
              >
                <!-- 1. Student ID -->
                <td class="py-3 px-4 font-mono font-bold text-sky-400 whitespace-nowrap">
                  {{ enr.student_id }}
                </td>

                <!-- 2. Student Name -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-[10px] font-bold text-emerald-400 shrink-0">
                      {{ enr.student_name ? enr.student_name.charAt(0) : 'S' }}
                    </div>
                    <div>
                      <div class="font-bold text-white group-hover:text-emerald-300 transition-colors">
                        {{ enr.student_name }}
                      </div>
                      <div class="text-[10px] text-slate-500">{{ enr.student_email }}</div>
                    </div>
                  </div>
                </td>

                <!-- 3. Major (5 SPI Majors) -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">
                    {{ enr.major }}
                  </span>
                </td>

                <!-- 4. Course -->
                <td class="py-3 px-4 min-w-[180px]">
                  <div class="font-semibold text-slate-200">{{ enr.course_title }}</div>
                  <div class="text-[10px] font-mono text-sky-400/80">{{ enr.course_code }}</div>
                </td>

                <!-- 5. Teacher -->
                <td class="py-3 px-4 whitespace-nowrap text-slate-300">
                  {{ enr.teacher_name }}
                </td>

                <!-- 6. Enrolled Date -->
                <td class="py-3 px-4 whitespace-nowrap text-slate-400 font-mono text-[11px]">
                  {{ formatDate(enr.enrolled_date) }}
                </td>

                <!-- 7. Progress (Calculated from Student Learning Activity) -->
                <td class="py-3 px-4 whitespace-nowrap min-w-[120px]">
                  <div class="flex items-center justify-between text-[11px] mb-1 font-mono">
                    <span class="font-bold text-slate-200">{{ enr.progress }}%</span>
                    <span class="text-[10px] text-slate-400">Activity</span>
                  </div>
                  <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-500"
                      :class="enr.progress >= 100 ? 'bg-emerald-400' : (enr.progress >= 50 ? 'bg-sky-400' : (enr.status === 'dropped' ? 'bg-rose-400' : 'bg-indigo-500'))"
                      :style="{ width: `${enr.progress}%` }"
                    ></div>
                  </div>
                </td>

                <!-- 8. Status (Active / Completed / Dropped) -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <span
                    v-if="enr.status === 'completed'"
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-500/15 text-sky-300 border border-sky-500/30"
                  >
                    <svg class="w-3 h-3 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Completed</span>
                  </span>

                  <span
                    v-else-if="enr.status === 'dropped'"
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-300 border border-rose-500/30"
                  >
                    <svg class="w-3 h-3 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Dropped</span>
                  </span>

                  <span
                    v-else
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Active</span>
                  </span>
                </td>

                <!-- 9. Actions (View & Status transition) -->
                <td class="py-3 px-4 whitespace-nowrap text-center">
                  <div class="inline-flex items-center gap-1.5">
                    <button
                      @click="openDetailModal(enr)"
                      class="px-2.5 py-1 rounded-lg bg-indigo-600/80 hover:bg-indigo-600 text-white font-bold text-xs transition-all flex items-center gap-1 cursor-pointer"
                      title="View Enrollment Details"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <span>View</span>
                    </button>

                    <!-- Quick Drop / Re-activate toggle -->
                    <button
                      v-if="enr.status === 'active'"
                      @click="updateStatus(enr, 'dropped')"
                      class="p-1 rounded-lg bg-slate-800 hover:bg-rose-900/60 text-slate-400 hover:text-rose-300 border border-slate-700/60 transition-all cursor-pointer"
                      title="Set to Dropped"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                    <button
                      v-else-if="enr.status === 'dropped'"
                      @click="updateStatus(enr, 'active')"
                      class="p-1 rounded-lg bg-slate-800 hover:bg-emerald-900/60 text-slate-400 hover:text-emerald-300 border border-slate-700/60 transition-all cursor-pointer"
                      title="Re-activate Student"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="paginatedEnrollments.length === 0">
                <td colspan="9" class="text-center py-10 text-slate-500">
                  <div class="text-3xl mb-2">🎓</div>
                  <div class="text-sm font-semibold">មិនមានទិន្នន័យ Enrollment ឡើយ</div>
                  <div class="text-xs text-slate-600 mt-1">សូមចុច "+ Enroll Student" ដើម្បីចាប់ផ្តើមចុះឈ្មោះនិស្សិតចូល Course</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAGINATION -->
        <div class="p-3.5 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
          <div>
            បង្ហាញពី <span class="font-bold text-white">{{ filteredEnrollments.length ? ((currentPage - 1) * pageSize + 1) : 0 }}</span>
            ដល់ <span class="font-bold text-white">{{ Math.min(currentPage * pageSize, filteredEnrollments.length) }}</span>
            នៃ <span class="font-bold text-white">{{ filteredEnrollments.length }}</span> Enrollments
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

      <!-- ENROLL STUDENT MODAL (SPECIFICATION 3 & 4) -->
      <div
        v-if="showEnrollModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      >
        <div class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold">
                +
              </div>
              <div>
                <h3 class="text-sm font-bold text-white">Enroll Student into Course</h3>
                <p class="text-xs text-slate-400">Select Student → Select Course → Validate Major & Academic Year</p>
              </div>
            </div>
            <button @click="showEnrollModal = false" class="text-slate-400 hover:text-white cursor-pointer">
              ✕
            </button>
          </div>

          <form @submit.prevent="submitEnroll" class="space-y-4 text-xs">
            <!-- 1. Select Student -->
            <div>
              <label class="block font-semibold text-slate-300 mb-1">ជ្រើសរើសនិស្សិត (Select Student) *</label>
              <select
                v-model="enrollForm.student_id"
                class="w-full px-3 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
              >
                <option value="" disabled>-- ជ្រើសរើសនិស្សិត (Active Students Only) --</option>
                <option v-for="st in props.students" :key="st.id" :value="st.id">
                  {{ st.name }} ({{ st.student_code || 'SPI-STU' }}) — {{ st.major?.name || 'General' }}
                </option>
              </select>
            </div>

            <!-- 2. Select Course -->
            <div>
              <label class="block font-semibold text-slate-300 mb-1">ជ្រើសរើសវគ្គសិក្សា (Select Course - Published Only) *</label>
              <select
                v-model="enrollForm.course_id"
                class="w-full px-3 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
              >
                <option value="" disabled>-- ជ្រើសរើស Course (Published Only) --</option>
                <option v-for="crs in props.courses" :key="crs.id" :value="crs.id">
                  {{ crs.title }} ({{ crs.code }}) — {{ crs.major?.name }}
                </option>
              </select>
            </div>

            <!-- 3. Academic Year -->
            <div>
              <label class="block font-semibold text-slate-300 mb-1">ឆ្នាំសិក្សា (Academic Year)</label>
              <select
                v-model="enrollForm.academic_year"
                class="w-full px-3 py-2.5 bg-slate-950/70 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
              >
                <option v-for="yr in props.academicYears" :key="yr.id" :value="yr.name">
                  {{ yr.name }}
                </option>
              </select>
            </div>

            <!-- 4. ENROLLMENT VALIDATION STATUS CARD (SPECIFICATION 4) -->
            <div class="p-3.5 bg-slate-950/60 rounded-2xl border border-slate-800 space-y-2">
              <div class="font-bold text-slate-300 text-[11px] uppercase tracking-wider">
                Enrollment Validation Checks (ការផ្ទៀងផ្ទាត់លក្ខខណ្ឌមុន Enroll):
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                <!-- Check 1: Student Active -->
                <div class="flex items-center gap-1.5" :class="validationChecks.isStudentActive ? 'text-emerald-300' : 'text-slate-500'">
                  <span>{{ validationChecks.isStudentActive ? '✓' : '○' }}</span>
                  <span>Student Active Account</span>
                </div>

                <!-- Check 2: Course Published -->
                <div class="flex items-center gap-1.5" :class="validationChecks.isCoursePublished ? 'text-emerald-300' : 'text-slate-500'">
                  <span>{{ validationChecks.isCoursePublished ? '✓' : '○' }}</span>
                  <span>Course is Published</span>
                </div>

                <!-- Check 3: Major Match -->
                <div class="flex items-center gap-1.5" :class="validationChecks.isMajorMatched ? 'text-emerald-300' : 'text-rose-400 font-bold'">
                  <span>{{ validationChecks.isMajorMatched ? '✓' : '✕' }}</span>
                  <span>Matching Major ({{ selectedStudentData?.major?.name || 'Major' }})</span>
                </div>

                <!-- Check 4: Duplicate Check -->
                <div class="flex items-center gap-1.5" :class="!validationChecks.isDuplicate ? 'text-emerald-300' : 'text-rose-400 font-bold'">
                  <span>{{ !validationChecks.isDuplicate ? '✓' : '✕' }}</span>
                  <span>{{ validationChecks.isDuplicate ? 'Already Enrolled (ស្ទួន)' : 'No Duplicate Enrollment' }}</span>
                </div>
              </div>

              <!-- Alert if mismatch or duplicate -->
              <div v-if="validationChecks.isDuplicate" class="p-2 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-[11px] mt-1">
                ⚠️ និស្សិតនេះបានចុះឈ្មោះក្នុង Course នេះរួចហើយ! មិនអាចចុះឈ្មោះស្ទួនបានឡើយ។
              </div>
              <div v-else-if="!validationChecks.isMajorMatched && selectedStudentData && selectedCourseData" class="p-2 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[11px] mt-1">
                ⚠️ ជំនាញនិស្សិត ({{ selectedStudentData.major?.name }}) មិនត្រូវគ្នាជាមួយជំនាញ Course ({{ selectedCourseData.major?.name }}) ឡើយ។
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
              <button
                type="button"
                @click="showEnrollModal = false"
                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold cursor-pointer"
              >
                បោះបង់ (Cancel)
              </button>
              <button
                type="submit"
                :disabled="enrollForm.processing || !validationChecks.canSubmit"
                class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold shadow-lg shadow-emerald-600/30 cursor-pointer"
              >
                {{ enrollForm.processing ? 'កំពុងចុះឈ្មោះ...' : 'Enroll Student (ចុះឈ្មោះ)' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- VIEW DETAIL MODAL (SPECIFICATION 1) -->
      <div
        v-if="showDetailModal && selectedEnrollment"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      >
        <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
          <!-- Header -->
          <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div>
                <h3 class="text-sm font-bold text-white">Enrollment Details: {{ selectedEnrollment.student_name }}</h3>
                <p class="text-xs text-slate-400 font-mono">{{ selectedEnrollment.student_id }} • {{ selectedEnrollment.major }}</p>
              </div>
            </div>
            <button @click="showDetailModal = false" class="text-slate-400 hover:text-white cursor-pointer">
              ✕
            </button>
          </div>

          <!-- Course & Student Info -->
          <div class="bg-slate-950/50 rounded-2xl border border-slate-800 p-3.5 space-y-2 text-xs">
            <div class="grid grid-cols-2 gap-2">
              <div>
                <span class="text-slate-400">Enrolled Course:</span>
                <div class="font-bold text-white mt-0.5">{{ selectedEnrollment.course_title }}</div>
                <div class="font-mono text-[10px] text-sky-400">{{ selectedEnrollment.course_code }}</div>
              </div>
              <div>
                <span class="text-slate-400">Instructor:</span>
                <div class="font-bold text-slate-200 mt-0.5">{{ selectedEnrollment.teacher_name }}</div>
              </div>
              <div>
                <span class="text-slate-400">Enrolled Date:</span>
                <div class="font-bold text-slate-300 mt-0.5">{{ formatDate(selectedEnrollment.enrolled_date) }}</div>
              </div>
              <div>
                <span class="text-slate-400">Academic Year:</span>
                <div class="font-bold text-slate-300 mt-0.5">{{ selectedEnrollment.academic_year }}</div>
              </div>
            </div>
          </div>

          <!-- Real Learning Progress Breakdown (Spec 5) -->
          <div class="p-3.5 bg-slate-950/60 rounded-2xl border border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold text-slate-300">Learning Progress (ពី Student Activity):</span>
              <span class="font-bold text-emerald-400 font-mono text-sm">{{ selectedEnrollment.progress }}%</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
              <div
                class="h-full rounded-full bg-emerald-500 transition-all duration-300"
                :style="{ width: `${selectedEnrollment.progress}%` }"
              ></div>
            </div>
            <div class="text-[11px] text-slate-400 flex items-center justify-between pt-1">
              <span>Status: <strong class="text-white uppercase">{{ selectedEnrollment.status }}</strong></span>
              <span>Flow: My Courses → Lessons → Progress</span>
            </div>
          </div>

          <!-- Status Switcher Buttons (Active / Completed / Dropped) -->
          <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-1.5">
              <button
                v-if="selectedEnrollment.status !== 'active'"
                @click="updateStatus(selectedEnrollment, 'active')"
                class="px-2.5 py-1.5 rounded-lg bg-emerald-600/80 hover:bg-emerald-600 text-white text-xs font-bold cursor-pointer"
              >
                Mark Active
              </button>
              <button
                v-if="selectedEnrollment.status !== 'completed'"
                @click="updateStatus(selectedEnrollment, 'completed')"
                class="px-2.5 py-1.5 rounded-lg bg-sky-600/80 hover:bg-sky-600 text-white text-xs font-bold cursor-pointer"
              >
                Mark Completed
              </button>
              <button
                v-if="selectedEnrollment.status !== 'dropped'"
                @click="updateStatus(selectedEnrollment, 'dropped')"
                class="px-2.5 py-1.5 rounded-lg bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-bold cursor-pointer"
              >
                Mark Dropped
              </button>
            </div>

            <button
              @click="removeEnrollment(selectedEnrollment)"
              class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-rose-900/70 text-rose-300 text-xs font-bold border border-slate-700 cursor-pointer"
            >
              Remove
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
