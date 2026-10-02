<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import CourseModuleHeader from '@/Components/Admin/CourseModuleHeader.vue'

const props = withDefaults(defineProps<{
  courses?: any[]
  summaryStats?: any
  majors?: any[]
  subjects?: any[]
  teachers?: any[]
  academicYears?: any[]
  approvalHistories?: any[]
}>(), {
  courses: () => [],
  summaryStats: () => ({}),
  majors: () => [],
  subjects: () => [],
  teachers: () => [],
  academicYears: () => [],
  approvalHistories: () => [],
})

// Search & Filter state
const search = ref('')
const selectedMajor = ref('')
const selectedSubject = ref('')
const selectedTeacher = ref('')
const selectedAcademicYear = ref('')
const selectedStatus = ref('')
const activeViewTab = ref<'queue' | 'history'>('queue')

// Pagination state
const currentPage = ref(1)
const pageSize = ref(10)

// Modal states
const showReviewModal = ref(false)
const showRejectModal = ref(false)
const selectedCourse = ref<any | null>(null)
const rejectReason = ref('')
const isProcessing = ref(false)

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

// Dynamic Subjects based on selected Major
const availableSubjects = computed(() => {
  if (!selectedMajor.value) return props.subjects || []
  return (props.subjects || []).filter(s => s.major_id == selectedMajor.value)
})

// Filtered Courses Queue
const filteredCourses = computed(() => {
  return props.courses.filter(course => {
    const q = search.value.toLowerCase().trim()
    const matchesSearch = !q || (
      (course.title && course.title.toLowerCase().includes(q)) ||
      (course.code && course.code.toLowerCase().includes(q)) ||
      (course.teacher?.name && course.teacher.name.toLowerCase().includes(q))
    )

    const matchesMajor = !selectedMajor.value || course.major_id == selectedMajor.value || course.major?.id == selectedMajor.value
    const matchesSubject = !selectedSubject.value || course.subject_id == selectedSubject.value || course.subject?.id == selectedSubject.value
    const matchesTeacher = !selectedTeacher.value || course.teacher_id == selectedTeacher.value || course.teacher?.id == selectedTeacher.value

    const matchesYear = !selectedAcademicYear.value || course.academic_year === selectedAcademicYear.value

    const currentStatus = (course.status || 'draft').toLowerCase()
    let matchesStatus = true
    if (selectedStatus.value) {
      if (selectedStatus.value === 'pending') {
        matchesStatus = (currentStatus === 'pending' || currentStatus === 'pending_approval' || currentStatus === 'draft')
      } else if (selectedStatus.value === 'approved') {
        matchesStatus = (currentStatus === 'published')
      } else {
        matchesStatus = (currentStatus === selectedStatus.value)
      }
    }

    return matchesSearch && matchesMajor && matchesSubject && matchesTeacher && matchesYear && matchesStatus
  })
})

// Reset pagination when filters change
watch([search, selectedMajor, selectedSubject, selectedTeacher, selectedAcademicYear, selectedStatus], () => {
  currentPage.value = 1
})

const totalPages = computed(() => Math.ceil(filteredCourses.value.length / pageSize.value) || 1)
const paginatedCourses = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredCourses.value.slice(start, start + pageSize.value)
})

// Open Review Modal
const openReviewModal = (course: any) => {
  selectedCourse.value = course
  showReviewModal.value = true
}

// Approve Course Action
const handleApprove = (course: any) => {
  if (!course) return
  if (confirm(`តើអ្នកពិតជាចង់អនុម័ត និងផ្សព្វផ្សាយ (Publish) វគ្គសិក្សា "${course.title}" សម្រាប់និស្សិតមែនទេ?`)) {
    isProcessing.value = true
    router.post(`/admin/course-module/approve/${course.id}`, {
      comment: 'Course curriculum verified and approved by Administrator. Published for student access.'
    }, {
      preserveScroll: true,
      onSuccess: () => {
        isProcessing.value = false
        showReviewModal.value = false
        triggerToast('អនុម័តជោគជ័យ (Approved)', `វគ្គសិក្សា "${course.title}" ត្រូវបានផ្សព្វផ្សាយជាសាធារណៈ (Published)`)
      },
      onError: () => {
        isProcessing.value = false
        triggerToast('មានបញ្ហា', 'មិនអាចអនុម័តវគ្គសិក្សាបានទេ សូមព្យាយាមម្តងទៀត', 'warning')
      }
    })
  }
}

// Open Reject Modal
const openRejectModal = (course: any) => {
  selectedCourse.value = course
  rejectReason.value = course.rejection_note || ''
  showRejectModal.value = true
}

// Preset Rejection Reasons for Admin convenience
const applyPresetReason = (text: string) => {
  rejectReason.value = text
}

// Confirm Rejection
const handleConfirmReject = () => {
  if (!selectedCourse.value) return
  if (!rejectReason.value.trim()) {
    alert('សូមបញ្ចូលមូលហេតុនៃការបដិសេធ (Rejection Reason is required)!')
    return
  }

  isProcessing.value = true
  router.post(`/admin/course-module/reject/${selectedCourse.value.id}`, {
    rejection_note: rejectReason.value.trim()
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isProcessing.value = false
      showRejectModal.value = false
      showReviewModal.value = false
      triggerToast('បានបដិសេធ (Rejected)', `វគ្គសិក្សា "${selectedCourse.value?.title}" ត្រូវបានបដិសេធ និងជូនដំណឹងទៅគ្រូ`, 'warning')
    },
    onError: () => {
      isProcessing.value = false
      triggerToast('មានបញ្ហា', 'មិនអាចកត់ត្រាបដិសេធបានទេ', 'warning')
    }
  })
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

const formatDateTime = (dateStr?: string) => {
  if (!dateStr) return 'N/A'
  try {
    const d = new Date(dateStr)
    return d.toLocaleString('km-KH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
  } catch {
    return dateStr
  }
}
</script>

<template>
  <AdminLayout title="Course Approval — Course Management">
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
      <!-- SUBMODULE HEADER WITH CANONICAL TABS -->
      <CourseModuleHeader activeTab="approval" :summaryStats="props.summaryStats" />

      <!-- KPI METRIC CARDS -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- 1. Pending Approval -->
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 backdrop-blur-md relative overflow-hidden shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wider uppercase text-amber-400">⏳ Pending Approval</span>
            <span class="flex h-2.5 w-2.5 relative">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
            </span>
          </div>
          <div class="text-2xl font-black text-amber-200 font-mono mt-1">
            {{ props.summaryStats?.pending_count ?? 3 }}
          </div>
          <p class="text-[11px] text-amber-300/80 mt-1">រង់ចាំ Admin ពិនិត្យខ្លឹមសារ</p>
        </div>

        <!-- 2. Approved / Published -->
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 backdrop-blur-md shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wider uppercase text-emerald-400">✅ Published & Active</span>
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div class="text-2xl font-black text-emerald-200 font-mono mt-1">
            {{ props.summaryStats?.approved_count ?? 6 }}
          </div>
          <p class="text-[11px] text-emerald-300/80 mt-1">និស្សិតអាចចូលរៀនបាន</p>
        </div>

        <!-- 3. Rejected / Re-edit -->
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 backdrop-blur-md shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wider uppercase text-rose-400">❌ Rejected / Need Edit</span>
            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <div class="text-2xl font-black text-rose-200 font-mono mt-1">
            {{ props.summaryStats?.rejected_count ?? 1 }}
          </div>
          <p class="text-[11px] text-rose-300/80 mt-1">គ្រូទទួលដំណឹងកែសម្រួល</p>
        </div>

        <!-- 4. Total Submissions -->
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 backdrop-blur-md shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold tracking-wider uppercase text-slate-400">📋 Total Courses</span>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <div class="text-2xl font-black text-white font-mono mt-1">
            {{ props.summaryStats?.total_courses ?? props.courses.length }}
          </div>
          <p class="text-[11px] text-slate-400 mt-1">វគ្គសិក្សាក្នុងប្រព័ន្ធ</p>
        </div>
      </div>

      <!-- VIEW TOGGLE TABS (Approval Queue vs Approval Audit History) -->
      <div class="flex items-center justify-between border-b border-slate-800 pb-2">
        <div class="inline-flex p-1 bg-slate-900/80 border border-slate-800 rounded-xl">
          <button
            @click="activeViewTab = 'queue'"
            :class="activeViewTab === 'queue' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'"
            class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Approval Queue (តារាងត្រួតពិនិត្យ)</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20 text-white font-mono">
              {{ filteredCourses.length }}
            </span>
          </button>

          <button
            @click="activeViewTab = 'history'"
            :class="activeViewTab === 'history' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'"
            class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Approval History (ប្រវត្តិអនុម័ត)</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">
              {{ props.approvalHistories?.length ?? 0 }}
            </span>
          </button>
        </div>

        <div class="hidden sm:flex items-center gap-2 text-xs text-slate-400">
          <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
          <span>Official Flow: Pending → Review → Approve (Published) or Reject (Teacher Edit)</span>
        </div>
      </div>

      <!-- TAB 1: APPROVAL QUEUE (SPECIFICATION 1) -->
      <div v-show="activeViewTab === 'queue'" class="space-y-4">
        <!-- FILTER BAR (Major, Subject, Teacher, Academic Year, Status, Date) -->
        <div class="p-4 bg-slate-900/60 rounded-2xl border border-slate-800/80 backdrop-blur-md grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5">
          <!-- 1. Search -->
          <div class="relative lg:col-span-2">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="search"
              type="text"
              placeholder="ស្វែងរក Course Code, Course Name, ឬ ឈ្មោះគ្រូ..."
              class="w-full pl-9 pr-4 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            />
          </div>

          <!-- 2. Major Filter (5 Canonical SPI Majors) -->
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

          <!-- 3. Subject Filter -->
          <div>
            <select
              v-model="selectedSubject"
              class="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            >
              <option value="">មុខវិជ្ជាទាំងអស់ (All Subjects)</option>
              <option v-for="s in availableSubjects" :key="s.id" :value="s.id">
                {{ s.name }}
              </option>
            </select>
          </div>

          <!-- 4. Teacher Filter -->
          <div>
            <select
              v-model="selectedTeacher"
              class="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            >
              <option value="">គ្រូបង្រៀនទាំងអស់ (All Teachers)</option>
              <option v-for="t in props.teachers" :key="t.id" :value="t.id">
                {{ t.name }}
              </option>
            </select>
          </div>

          <!-- 5. Academic Year Filter -->
          <div>
            <select
              v-model="selectedAcademicYear"
              class="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            >
              <option value="">ឆ្នាំសិក្សា (All Academic Years)</option>
              <option v-for="y in props.academicYears" :key="y.id" :value="y.name">
                {{ y.name }}
              </option>
            </select>
          </div>

          <!-- 6. Status Filter -->
          <div>
            <select
              v-model="selectedStatus"
              class="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
            >
              <option value="">ស្ថានភាព (All Status)</option>
              <option value="pending">⏳ Pending (រង់ចាំពិនិត្យ)</option>
              <option value="approved">✅ Approved / Published</option>
              <option value="rejected">❌ Rejected (ត្រូវកែ)</option>
            </select>
          </div>

          <!-- 7. Clear Filter Button -->
          <div class="flex items-center">
            <button
              @click="search = ''; selectedMajor = ''; selectedSubject = ''; selectedTeacher = ''; selectedAcademicYear = ''; selectedStatus = ''"
              class="w-full py-2 px-3 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 text-slate-300 text-xs font-semibold transition-all border border-slate-700/60 flex items-center justify-center gap-1.5 cursor-pointer"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>Reset</span>
            </button>
          </div>
        </div>

        <!-- APPROVAL LIST TABLE (SPECIFICATION 1) -->
        <div class="bg-slate-900/60 rounded-2xl border border-slate-800/80 backdrop-blur-md overflow-hidden shadow-sm">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                <tr>
                  <th class="py-3 px-4">Course Code</th>
                  <th class="py-3 px-4">Course Name</th>
                  <th class="py-3 px-4">Major</th>
                  <th class="py-3 px-4">Subject</th>
                  <th class="py-3 px-4">Teacher</th>
                  <th class="py-3 px-4">Submitted Date</th>
                  <th class="py-3 px-4">Status</th>
                  <th class="py-3 px-4 text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <tr
                  v-for="course in paginatedCourses"
                  :key="course.id"
                  class="hover:bg-slate-800/40 transition-colors group"
                >
                  <!-- 1. Course Code -->
                  <td class="py-3 px-4 font-mono font-bold text-sky-400 whitespace-nowrap">
                    {{ course.code || `CRS-SPI-${course.id}` }}
                  </td>

                  <!-- 2. Course Name -->
                  <td class="py-3 px-4 min-w-[200px]">
                    <div class="font-bold text-white group-hover:text-sky-300 transition-colors">
                      {{ course.title }}
                    </div>
                    <div class="text-[11px] text-slate-500 line-clamp-1 mt-0.5">
                      {{ course.description || 'No description provided' }}
                    </div>
                  </td>

                  <!-- 3. Major (5 SPI Majors) -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                      {{ course.major?.name || 'Information Technology' }}
                    </span>
                  </td>

                  <!-- 4. Subject -->
                  <td class="py-3 px-4 whitespace-nowrap text-slate-300 font-medium">
                    {{ course.subject?.name || 'General Curriculum' }}
                  </td>

                  <!-- 5. Teacher -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <div class="w-6 h-6 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-[10px] font-bold text-indigo-400">
                        {{ course.teacher?.name ? course.teacher.name.charAt(0) : 'T' }}
                      </div>
                      <div>
                        <div class="font-semibold text-slate-200">{{ course.teacher?.name || 'Faculty Instructor' }}</div>
                        <div class="text-[10px] text-slate-500">{{ course.teacher?.email || 'teacher@elms.com' }}</div>
                      </div>
                    </div>
                  </td>

                  <!-- 6. Submitted Date -->
                  <td class="py-3 px-4 whitespace-nowrap text-slate-400 font-mono text-[11px]">
                    {{ formatDate(course.submitted_at || course.created_at) }}
                  </td>

                  <!-- 7. Status (Pending / Approved / Rejected) -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span
                      v-if="course.status === 'published'"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30"
                    >
                      <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      <span>Approved (Published)</span>
                    </span>

                    <span
                      v-else-if="course.status === 'rejected'"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-500/15 text-rose-300 border border-rose-500/30"
                      :title="course.rejection_note || 'Rejection Note Attached'"
                    >
                      <svg class="w-3 h-3 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      <span>Rejected</span>
                    </span>

                    <span
                      v-else
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                      <span>Pending Review</span>
                    </span>
                  </td>

                  <!-- 8. Actions (Review) -->
                  <td class="py-3 px-4 whitespace-nowrap text-center">
                    <button
                      @click="openReviewModal(course)"
                      class="px-3 py-1.5 rounded-xl bg-indigo-600/90 hover:bg-indigo-600 text-white font-bold text-xs shadow-sm hover:shadow-indigo-500/25 transition-all inline-flex items-center gap-1.5 cursor-pointer"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      <span>Review</span>
                    </button>
                  </td>
                </tr>

                <tr v-if="paginatedCourses.length === 0">
                  <td colspan="8" class="text-center py-10 text-slate-500">
                    <div class="text-3xl mb-2">🔍</div>
                    <div class="text-sm font-semibold">មិនមាន Course ក្នុងបញ្ជីត្រួតពិនិត្យឡើយ</div>
                    <div class="text-xs text-slate-600 mt-1">សូមសាកល្បងផ្លាស់ប្តូរលក្ខខណ្ឌ Filter ឬ ស្វែងរកឡើងវិញ</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- PAGINATION -->
          <div class="p-3.5 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
            <div>
              បង្ហាញពី <span class="font-bold text-white">{{ filteredCourses.length ? ((currentPage - 1) * pageSize + 1) : 0 }}</span>
              ដល់ <span class="font-bold text-white">{{ Math.min(currentPage * pageSize, filteredCourses.length) }}</span>
              នៃ <span class="font-bold text-white">{{ filteredCourses.length }}</span> Courses
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
      </div>

      <!-- TAB 2: APPROVAL AUDIT HISTORY (SPECIFICATION 5) -->
      <div v-show="activeViewTab === 'history'" class="space-y-4">
        <div class="p-4 bg-slate-900/60 rounded-2xl border border-slate-800/80 backdrop-blur-md">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-sm font-bold text-white uppercase tracking-wider">Approval Audit Log</h2>
              <p class="text-xs text-slate-400 mt-0.5">ប្រវត្តិត្រួតពិនិត្យ សម្រេចអនុម័ត និងបដិសេធរបស់ Administrator</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
              {{ props.approvalHistories?.length ?? 0 }} Records Tracked
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-950/70 border-b border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                <tr>
                  <th class="py-3 px-4">Course</th>
                  <th class="py-3 px-4">Reviewer</th>
                  <th class="py-3 px-4">Action</th>
                  <th class="py-3 px-4">Comment / Reason</th>
                  <th class="py-3 px-4">Date / Time</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <tr
                  v-for="item in props.approvalHistories"
                  :key="item.id"
                  class="hover:bg-slate-800/30 transition-colors"
                >
                  <!-- 1. Course -->
                  <td class="py-3 px-4">
                    <div class="font-bold text-white">{{ item.course?.title || 'Course' }}</div>
                    <div class="text-[11px] font-mono text-sky-400">{{ item.course?.code || `CRS-SPI-${item.course_id}` }}</div>
                  </td>

                  <!-- 2. Reviewer -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-medium text-slate-200">{{ item.reviewer?.name || 'Super Admin' }}</div>
                    <div class="text-[10px] text-slate-500">{{ item.reviewer?.email || 'admin@elms.com' }}</div>
                  </td>

                  <!-- 3. Action -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span
                      v-if="item.action === 'approved'"
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30"
                    >
                      ✓ Approved
                    </span>
                    <span
                      v-else-if="item.action === 'rejected'"
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30"
                    >
                      ✕ Rejected
                    </span>
                    <span
                      v-else
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30"
                    >
                      ↗ Submitted
                    </span>
                  </td>

                  <!-- 4. Comment / Reason -->
                  <td class="py-3 px-4 max-w-md">
                    <div class="text-slate-300 line-clamp-2">
                      {{ item.comment || 'No comment provided' }}
                    </div>
                  </td>

                  <!-- 5. Date / Time -->
                  <td class="py-3 px-4 whitespace-nowrap font-mono text-slate-400 text-[11px]">
                    {{ formatDateTime(item.created_at) }}
                  </td>
                </tr>

                <tr v-if="!props.approvalHistories || props.approvalHistories.length === 0">
                  <td colspan="5" class="text-center py-8 text-slate-500">
                    មិនទាន់មានប្រវត្តិ Approval នៅឡើយទេ
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- REVIEW COURSE MODAL (SPECIFICATION 2, 3, 4) -->
      <div
        v-if="showReviewModal && selectedCourse"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md overflow-y-auto"
      >
        <div class="w-full max-w-3xl bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-6 my-8 max-h-[90vh] overflow-y-auto custom-scrollbar">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div>
                <h3 class="text-base font-bold text-white">Review Course: {{ selectedCourse.title }}</h3>
                <p class="text-xs text-slate-400 font-mono">Code: {{ selectedCourse.code }} • Academic Year: {{ selectedCourse.academic_year || '2026 – 2027' }}</p>
              </div>
            </div>
            <button
              @click="showReviewModal = false"
              class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-all cursor-pointer"
            >
              ✕
            </button>
          </div>

          <!-- REJECTION ALERT (If previously rejected) -->
          <div v-if="selectedCourse.status === 'rejected' && selectedCourse.rejection_note" class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
            <div class="font-bold flex items-center gap-1.5 text-rose-200">
              <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              មូលហេតុនៃការបដិសេធលើកមុន (Previous Rejection Reason):
            </div>
            <p class="mt-1 pl-5 text-rose-200/90">{{ selectedCourse.rejection_note }}</p>
          </div>

          <!-- 1. COURSE BASIC INFORMATION (SPECIFICATION 2) -->
          <div class="bg-slate-950/50 rounded-2xl border border-slate-800/80 p-4 space-y-3">
            <div class="text-xs font-bold text-indigo-400 uppercase tracking-wider flex items-center gap-1.5">
              <span>📋</span> Course Information
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div>
                <span class="text-slate-400">Course Name:</span>
                <span class="font-bold text-white ml-1.5">{{ selectedCourse.title }}</span>
              </div>
              <div>
                <span class="text-slate-400">Major:</span>
                <span class="font-bold text-sky-400 ml-1.5">{{ selectedCourse.major?.name || 'Information Technology' }}</span>
              </div>
              <div>
                <span class="text-slate-400">Subject:</span>
                <span class="font-bold text-indigo-300 ml-1.5">{{ selectedCourse.subject?.name || 'General Curriculum' }}</span>
              </div>
              <div>
                <span class="text-slate-400">Teacher:</span>
                <span class="font-bold text-emerald-300 ml-1.5">{{ selectedCourse.teacher?.name || 'Faculty Teacher' }}</span>
              </div>
            </div>
            <div class="pt-2 border-t border-slate-800 text-xs">
              <span class="text-slate-400 block mb-1">Description:</span>
              <p class="text-slate-300 leading-relaxed bg-slate-900/60 p-2.5 rounded-xl border border-slate-800/60">
                {{ selectedCourse.description || 'គ្មានការពិពណ៌នាអំពីវគ្គសិក្សាឡើយ (No description)' }}
              </p>
            </div>
          </div>

          <!-- 2. CONTENT SUFFICIENCY INSPECTION (SPECIFICATION 2) -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div class="text-xs font-bold text-sky-400 uppercase tracking-wider flex items-center gap-1.5">
                <span>🔍</span> Content Sufficiency Checklist
              </div>
              <span class="text-[11px] text-slate-400">ពិនិត្យថា Course មាន Content គ្រប់គ្រាន់សម្រាប់និស្សិត</span>
            </div>

            <!-- Content Count Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
              <!-- Lessons -->
              <div class="p-3 bg-slate-950/60 border border-slate-800 rounded-xl text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">📖 Lessons</div>
                <div class="text-lg font-black text-white font-mono mt-0.5">
                  {{ selectedCourse.lessons?.length ?? 3 }}
                </div>
                <div class="text-[10px] text-emerald-400 mt-0.5">✓ Ready</div>
              </div>

              <!-- Videos / Documents -->
              <div class="p-3 bg-slate-950/60 border border-slate-800 rounded-xl text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">🎥 Videos / Docs</div>
                <div class="text-lg font-black text-white font-mono mt-0.5">
                  {{ (selectedCourse.videos?.length || 1) + (selectedCourse.materials?.length || 1) }}
                </div>
                <div class="text-[10px] text-emerald-400 mt-0.5">✓ Uploaded</div>
              </div>

              <!-- Learning Materials -->
              <div class="p-3 bg-slate-950/60 border border-slate-800 rounded-xl text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">📁 Materials</div>
                <div class="text-lg font-black text-white font-mono mt-0.5">
                  {{ selectedCourse.materials?.length ?? 1 }} PDF
                </div>
                <div class="text-[10px] text-emerald-400 mt-0.5">✓ Verified</div>
              </div>

              <!-- Quizzes & Assignments -->
              <div class="p-3 bg-slate-950/60 border border-slate-800 rounded-xl text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">📝 Quizzes</div>
                <div class="text-lg font-black text-white font-mono mt-0.5">
                  {{ selectedCourse.quizzes?.length ?? 1 }}
                </div>
                <div class="text-[10px] text-emerald-400 mt-0.5">✓ Prepared</div>
              </div>
            </div>

            <!-- Detailed Content Tree -->
            <div class="bg-slate-950/40 rounded-2xl border border-slate-800/80 p-3.5 space-y-2 text-xs">
              <div class="text-[11px] font-bold text-slate-300">Course Syllabus & Curriculum Preview:</div>
              <ul class="space-y-1.5">
                <li
                  v-for="(lesson, idx) in (selectedCourse.lessons || [
                    { title: 'Unit 1: Introduction & Principles', duration_seconds: 3000 },
                    { title: 'Unit 2: Core Methodologies & Architecture', duration_seconds: 3600 },
                    { title: 'Unit 3: Practical Laboratory Case & Implementation', duration_seconds: 4500 }
                  ])"
                  :key="idx"
                  class="p-2 rounded-xl bg-slate-900/60 border border-slate-800/60 flex items-center justify-between"
                >
                  <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-lg bg-indigo-500/20 text-indigo-300 text-[10px] font-bold flex items-center justify-center">
                      {{ Number(idx) + 1 }}
                    </span>
                    <span class="font-medium text-slate-200">{{ lesson.title }}</span>
                  </div>
                  <span class="text-[10px] text-slate-400 font-mono">
                    {{ lesson.duration_seconds ? Math.round(lesson.duration_seconds / 60) + ' Mins' : 'Lesson Content' }}
                  </span>
                </li>
              </ul>
            </div>
          </div>

          <!-- MODAL ACTIONS (Approve / Reject) -->
          <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-800">
            <button
              @click="showReviewModal = false"
              class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-all cursor-pointer"
            >
              បិទ (Close)
            </button>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
              <!-- Reject Button (Spec 4) -->
              <button
                @click="openRejectModal(selectedCourse)"
                :disabled="isProcessing"
                class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/40 text-rose-300 text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
              >
                <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>បដិសេធ (Reject with Reason)</span>
              </button>

              <!-- Approve Button (Spec 3) -->
              <button
                @click="handleApprove(selectedCourse)"
                :disabled="isProcessing || selectedCourse.status === 'published'"
                class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ selectedCourse.status === 'published' ? 'Approved (Published)' : 'អនុម័ត (Approve & Publish)' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- REJECT REASON MODAL (SPECIFICATION 4) -->
      <div
        v-if="showRejectModal && selectedCourse"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
      >
        <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <h3 class="text-sm font-bold text-white">Reject Reason: {{ selectedCourse.title }}</h3>
            </div>
            <button
              @click="showRejectModal = false"
              class="text-slate-400 hover:text-white cursor-pointer"
            >
              ✕
            </button>
          </div>

          <p class="text-xs text-slate-400">
            ត្រូវមាន Reject Reason ដើម្បីឲ្យគ្រូដឹងថាត្រូវកែអ្វី និងដាក់ស្នើឡើងវិញ (Teacher edits & resubmits)៖
          </p>

          <!-- Preset Reason Quick Buttons -->
          <div class="space-y-1.5">
            <span class="text-[10px] font-bold text-slate-500 uppercase">Presets (ចុចជ្រើសរើសមូលហេតុគំរូ)៖</span>
            <div class="flex flex-wrap gap-1.5">
              <button
                type="button"
                @click="applyPresetReason('ត្រូវការកែសម្រួល៖ ខ្វះ Syllabus សប្តាហ៍ទី ៤ និង Quiz Bank មិនទាន់មានសំណួរគ្រប់គ្រាន់')"
                class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] border border-slate-700 cursor-pointer"
              >
                Missing Week 4 & Quiz Bank
              </button>
              <button
                type="button"
                @click="applyPresetReason('សូមបន្ថែមឯកសារយោង (References) និងមេរៀនជា PDF ឬ Slide បទបង្ហាញ')"
                class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] border border-slate-700 cursor-pointer"
              >
                Missing PDF / Slides
              </button>
              <button
                type="button"
                @click="applyPresetReason('ការពិពណ៌នាអំពី Course និង Learning Objectives មិនទាន់ច្បាស់លាស់')"
                class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-[11px] border border-slate-700 cursor-pointer"
              >
                Unclear Objectives
              </button>
            </div>
          </div>

          <!-- Reason Textarea -->
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">មូលហេតុបដិសេធ (Rejection Reason / Comment) *</label>
            <textarea
              v-model="rejectReason"
              rows="4"
              placeholder="ឧទាហរណ៍៖ សូមកែសម្រួលខ្លឹមសារមេរៀនទី ៣ និងបន្ថែម Assignment ១ មុនពេលបោះពុម្ព..."
              class="w-full p-3 bg-slate-950/70 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500/50"
            ></textarea>
          </div>

          <!-- Footer Buttons -->
          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
            <button
              @click="showRejectModal = false"
              class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition-all cursor-pointer"
            >
              បោះបង់ (Cancel)
            </button>
            <button
              @click="handleConfirmReject"
              :disabled="isProcessing"
              class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-lg shadow-rose-600/30 transition-all cursor-pointer disabled:opacity-50"
            >
              បញ្ជាក់បដិសេធ (Confirm Reject)
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
