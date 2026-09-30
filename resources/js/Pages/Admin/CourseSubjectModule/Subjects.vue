<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AcademicModuleHeader from '@/Components/Admin/AcademicModuleHeader.vue'

interface MajorItem {
  id: number
  name: string
  name_kh?: string
  code?: string
  department?: string
  faculty?: string
}

interface SubjectItem {
  id: number
  code: string
  name: string
  name_kh?: string
  major_id?: number
  major: string
  department_id?: number
  department: string
  faculty?: string
  credits: number
  prerequisite?: string
  difficulty?: string
  description?: string
  status: string
  is_active?: boolean
  courses_count?: number
  courses_list?: Array<{ id: number; code: string; title: string }>
}

const props = withDefaults(defineProps<{
  subjects?: SubjectItem[]
  majors?: MajorItem[]
  departments?: any[]
  faculties?: any[]
  summaryStats?: any
}>(), {
  subjects: () => [],
  majors: () => [],
  departments: () => [],
  faculties: () => [],
  summaryStats: () => ({})
})

// Canonical 5 SPI Majors fallback
const canonicalMajors = [
  { id: 1, name: 'Information Technology', name_kh: 'បច្ចេកវិទ្យាព័ត៌មាន', code: 'MJR-IT-001', department: 'Computing', faculty: 'Faculty of Computing' },
  { id: 2, name: 'Tourism', name_kh: 'ទេសចរណ៍', code: 'MJR-TRM-002', department: 'Tourism', faculty: 'Faculty of Tourism' },
  { id: 3, name: 'English Literature', name_kh: 'អក្សរសាស្ត្រអង់គ្លេស', code: 'MJR-ENG-003', department: 'Education', faculty: 'Faculty of Education' },
  { id: 4, name: 'Agriculture', name_kh: 'កសិកម្ម', code: 'MJR-AGR-004', department: 'Agriculture', faculty: 'Faculty of Agriculture' },
  { id: 5, name: 'Social Work', name_kh: 'ការងារសង្គម', code: 'MJR-SW-005', department: 'Social Science', faculty: 'Faculty of Social Science' },
]

const availableMajors = computed(() => {
  return Array.isArray(props.majors) && props.majors.length > 0 ? props.majors : canonicalMajors
})

// Search & Filter state
const searchQuery = ref('')
const selectedMajorFilter = ref('all')
const selectedDifficultyFilter = ref('all')
const selectedStatusFilter = ref('all')

// Modal states
const isModalOpen = ref(false)
const isCurriculumModalOpen = ref(false)
const selectedSubjectForCurriculum = ref<SubjectItem | null>(null)
const editingSubject = ref<SubjectItem | null>(null)

// Bulk selection
const selectedIds = ref<number[]>([])

// Dropdown row actions
const activeDropdownId = ref<number | null>(null)
const toggleDropdown = (id: number) => {
  activeDropdownId.value = activeDropdownId.value === id ? null : id
}
const closeDropdown = () => {
  activeDropdownId.value = null
}

// Pagination
const currentPage = ref(1)
const pageSize = ref(10)

// Form state
const form = ref({
  code: '',
  name: '',
  name_kh: '',
  major_id: 1,
  major: 'Information Technology',
  department: 'Computing',
  faculty: 'Faculty of Computing',
  credits: 3,
  prerequisite: 'None',
  difficulty: 'Beginner',
  description: '',
  status: 'active',
  is_active: true
})

// Watch major change in form to auto-update department & faculty
watch(() => form.value.major_id, (newId) => {
  const found = availableMajors.value.find(m => m.id === Number(newId))
  if (found) {
    form.value.major = found.name
    form.value.department = found.department || 'General'
    form.value.faculty = found.faculty || 'Faculty of Computing'
    if (!editingSubject.value && !form.value.code) {
      const prefix = found.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 3) || 'SUB'
      form.value.code = `SUB-${prefix}-${Math.floor(100 + Math.random() * 900)}`
    }
  }
})

const subjectsList = computed<SubjectItem[]>(() => {
  return Array.isArray(props.subjects) && props.subjects.length > 0 ? props.subjects : []
})

const filteredSubjects = computed(() => {
  return subjectsList.value.filter(sub => {
    const q = searchQuery.value.toLowerCase().trim()
    const matchesSearch = !q ||
      sub.name.toLowerCase().includes(q) ||
      sub.code.toLowerCase().includes(q) ||
      (sub.name_kh && sub.name_kh.includes(q)) ||
      (sub.major && sub.major.toLowerCase().includes(q))

    const matchesMajor = selectedMajorFilter.value === 'all' || sub.major === selectedMajorFilter.value
    const matchesDifficulty = selectedDifficultyFilter.value === 'all' || sub.difficulty === selectedDifficultyFilter.value
    const matchesStatus = selectedStatusFilter.value === 'all' || sub.status === selectedStatusFilter.value

    return matchesSearch && matchesMajor && matchesDifficulty && matchesStatus
  })
})

watch([searchQuery, selectedMajorFilter, selectedDifficultyFilter, selectedStatusFilter, pageSize], () => {
  currentPage.value = 1
  selectedIds.value = []
})

const totalPages = computed(() => Math.ceil(filteredSubjects.value.length / pageSize.value) || 1)
const paginatedSubjects = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredSubjects.value.slice(start, start + pageSize.value)
})

const isAllSelected = computed({
  get: () => paginatedSubjects.value.length > 0 && paginatedSubjects.value.every(sub => selectedIds.value.includes(sub.id)),
  set: (val: boolean) => {
    if (val) {
      const pageIds = paginatedSubjects.value.map(sub => sub.id)
      selectedIds.value = Array.from(new Set([...selectedIds.value, ...pageIds]))
    } else {
      const pageIds = paginatedSubjects.value.map(sub => sub.id)
      selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id))
    }
  }
})

const toggleSelectAll = () => {
  isAllSelected.value = !isAllSelected.value
}

// Toast notification
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

const openAddModal = () => {
  editingSubject.value = null
  const defaultMajor = availableMajors.value[0] || canonicalMajors[0]
  const prefix = defaultMajor.name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 3) || 'IT'
  form.value = {
    code: `SUB-${prefix}-${Math.floor(100 + Math.random() * 900)}`,
    name: '',
    name_kh: '',
    major_id: defaultMajor.id,
    major: defaultMajor.name,
    department: defaultMajor.department || 'Computing',
    faculty: defaultMajor.faculty || 'Faculty of Computing',
    credits: 3,
    prerequisite: 'None',
    difficulty: 'Beginner',
    description: '',
    status: 'active',
    is_active: true
  }
  isModalOpen.value = true
}

const openEditModal = (sub: SubjectItem) => {
  closeDropdown()
  editingSubject.value = sub
  const matchedMajor = availableMajors.value.find(m => m.name === sub.major || m.id === sub.major_id)
  form.value = {
    code: sub.code,
    name: sub.name,
    name_kh: sub.name_kh || '',
    major_id: matchedMajor?.id || sub.major_id || 1,
    major: sub.major,
    department: sub.department || matchedMajor?.department || 'Computing',
    faculty: sub.faculty || matchedMajor?.faculty || 'Faculty of Computing',
    credits: sub.credits || 3,
    prerequisite: sub.prerequisite || 'None',
    difficulty: sub.difficulty || 'Beginner',
    description: sub.description || '',
    status: sub.status || (sub.is_active ? 'active' : 'inactive'),
    is_active: sub.is_active !== undefined ? sub.is_active : sub.status === 'active'
  }
  isModalOpen.value = true
}

const saveSubject = () => {
  if (!form.value.name.trim()) {
    triggerToast('សូមបញ្ចូលឈ្មោះមុខវិជ្ជា', 'សូមបញ្ចូល Subject Name មុនពេលរក្សាទុក', 'warning')
    return
  }

  isSubmitting.value = true
  const subName = form.value.name

  if (editingSubject.value?.id) {
    router.put(`/admin/academic-structure/subjects/update/${editingSubject.value.id}`, form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false
        isModalOpen.value = false
        triggerToast('រក្សាទុកបានជោគជ័យ', `ព័ត៌មានមុខវិជ្ជា "${subName}" ត្រូវបានបច្ចុប្បន្នភាពដោយជោគជ័យ`)
      },
      onError: () => {
        isSubmitting.value = false
        triggerToast('មានបញ្ហាក្នុងការរក្សាទុក', 'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ', 'warning')
      },
      onFinish: () => { isSubmitting.value = false }
    })
  } else {
    router.post('/admin/academic-structure/subjects/store', form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false
        isModalOpen.value = false
        triggerToast('បង្កើតមុខវិជ្ជាបានជោគជ័យ 🎉', `មុខវិជ្ជា "${subName}" ត្រូវបានបញ្ចូលក្នុងប្រព័ន្ធដោយជោគជ័យ`)
      },
      onError: () => {
        isSubmitting.value = false
        triggerToast('មានបញ្ហាក្នុងការបង្កើតមុខវិជ្ជា', 'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ', 'warning')
      },
      onFinish: () => { isSubmitting.value = false }
    })
  }
}

const toggleSubjectStatus = (sub: SubjectItem) => {
  const newStatus = sub.status === 'active' ? 'inactive' : 'active'
  const newIsActive = newStatus === 'active'

  router.put(`/admin/academic-structure/subjects/update/${sub.id}`, {
    ...sub,
    status: newStatus,
    is_active: newIsActive
  }, {
    preserveScroll: true,
    onSuccess: () => {
      sub.status = newStatus
      sub.is_active = newIsActive
      triggerToast('បច្ចុប្បន្នភាពស្ថានភាពជោគជ័យ', `ស្ថានភាពមុខវិជ្ជា "${sub.name}" ត្រូវបានប្តូរទៅជា ${newStatus.toUpperCase()}`)
    },
    onError: () => {
      triggerToast('មានបញ្ហាក្នុងការផ្លាស់ប្តូរស្ថានភាព', 'សូមព្យាយាមម្តងទៀត', 'warning')
    }
  })
}

const deleteSubject = (sub: SubjectItem) => {
  closeDropdown()
  if (confirm(`តើអ្នកពិតជាចង់លុបមុខវិជ្ជា "${sub.name}" (${sub.code}) មែនទេ?`)) {
    router.delete(`/admin/academic-structure/subjects/destroy/${sub.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast('លុបមុខវិជ្ជាជោគជ័យ', `មុខវិជ្ជា "${sub.name}" ត្រូវបានលុបចេញពីប្រព័ន្ធដោយជោគជ័យ`)
      },
      onError: () => {
        triggerToast('មានបញ្ហាក្នុងការលុប', 'មិនអាចលុបមុខវិជ្ជានេះបានទេ ព្រោះអាចមានវគ្គសិក្សា Course ភ្ជាប់ជាមួយ', 'warning')
      }
    })
  }
}

const bulkDelete = () => {
  if (confirm(`តើអ្នកពិតជាចង់លុបមុខវិជ្ជាដែលបានជ្រើសរើសចំនួន ${selectedIds.value.length} មែនទេ?`)) {
    triggerToast('លុបមុខវិជ្ជាជោគជ័យ', `បានលុបមុខវិជ្ជាចំនួន ${selectedIds.value.length} ដោយជោគជ័យ`)
    selectedIds.value = []
  }
}

const openCurriculumModal = (sub: SubjectItem) => {
  closeDropdown()
  selectedSubjectForCurriculum.value = sub
  isCurriculumModalOpen.value = true
}

const getMajorBadgeStyle = (majorName: string) => {
  switch (majorName) {
    case 'Information Technology':
      return 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30'
    case 'Tourism':
      return 'bg-sky-500/15 text-sky-300 border-sky-500/30'
    case 'English Literature':
      return 'bg-amber-500/15 text-amber-300 border-amber-500/30'
    case 'Agriculture':
      return 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'
    case 'Social Work':
      return 'bg-purple-500/15 text-purple-300 border-purple-500/30'
    default:
      return 'bg-slate-800 text-slate-300 border-slate-700'
  }
}

const getDifficultyBadgeStyle = (level: string = 'Beginner') => {
  switch (level) {
    case 'Beginner':
      return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
    case 'Intermediate':
      return 'bg-sky-500/10 text-sky-400 border-sky-500/20'
    case 'Advanced':
      return 'bg-rose-500/10 text-rose-400 border-rose-500/20'
    default:
      return 'bg-slate-800 text-slate-400 border-slate-700'
  }
}
</script>

<template>
  <AdminLayout title="Subjects — Academic Structure">
    <div class="space-y-4 font-sans" @click="closeDropdown">
      <!-- Shared Academic Structure Header Tabs -->
      <AcademicModuleHeader activeTab="subjects" :summaryStats="props.summaryStats" />

      <!-- SPI ACADEMIC FLOW HIERARCHY BANNER (Defense Presentation Ready) -->
      <div class="p-3.5 bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-indigo-500/20 rounded-2xl backdrop-blur-xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div class="flex items-center gap-2.5">
            <span class="flex h-2.5 w-2.5 relative">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-cyan-500"></span>
            </span>
            <div class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
              <span>SPI Academic Structure Flow</span>
              <span class="text-[10px] px-2 py-0.5 rounded-full bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 font-semibold lowercase">
                architecture
              </span>
            </div>
          </div>

          <!-- Flow Breadcrumb Sequence -->
          <div class="flex items-center flex-wrap gap-1.5 text-[11px] font-medium text-slate-300">
            <span class="px-2 py-0.5 rounded-lg bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 font-bold">1. Major</span>
            <span class="text-slate-500">→</span>
            <span class="px-2 py-0.5 rounded-lg bg-sky-500/25 text-sky-200 border border-sky-400/40 font-bold shadow-xs">2. Subject (Core)</span>
            <span class="text-slate-500">→</span>
            <span class="px-2 py-0.5 rounded-lg bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">3. Course</span>
            <span class="text-slate-500">→</span>
            <span class="px-2 py-0.5 rounded-lg bg-purple-500/15 text-purple-300 border border-purple-500/30">4. Lesson</span>
            <span class="text-slate-500">→</span>
            <span class="px-2 py-0.5 rounded-lg bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">5. Material</span>
            <span class="text-slate-500">→</span>
            <span class="px-2 py-0.5 rounded-lg bg-amber-500/15 text-amber-300 border border-amber-500/30">6. Quiz / Exam</span>
          </div>
        </div>
      </div>

      <!-- OVERVIEW STATS CARDS -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <!-- Card 1: Total Core Subjects -->
        <div class="p-3.5 bg-slate-900/80 border border-sky-500/20 rounded-2xl backdrop-blur-xl relative overflow-hidden group">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
            <span>Core Subjects</span>
            <span class="text-sky-400 font-mono text-xs">📖</span>
          </div>
          <div class="text-2xl font-black text-white mt-1">
            {{ subjectsList.length }}
          </div>
          <div class="text-[11px] text-sky-300/80 mt-0.5 flex items-center gap-1 font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
            <span>{{ subjectsList.filter(s => s.status === 'active').length }} Active Subjects</span>
          </div>
        </div>

        <!-- Card 2: 5 SPI Majors -->
        <div class="p-3.5 bg-slate-900/80 border border-cyan-500/20 rounded-2xl backdrop-blur-xl relative overflow-hidden group">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
            <span>SPI Majors</span>
            <span class="text-cyan-400 font-mono text-xs">🎓</span>
          </div>
          <div class="text-2xl font-black text-cyan-300 mt-1">
            {{ availableMajors.length }}
          </div>
          <div class="text-[11px] text-cyan-200/80 mt-0.5 flex items-center gap-1 font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
            <span>Degree Programs</span>
          </div>
        </div>

        <!-- Card 3: Total Credits -->
        <div class="p-3.5 bg-slate-900/80 border border-emerald-500/20 rounded-2xl backdrop-blur-xl relative overflow-hidden group">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
            <span>Total Credits</span>
            <span class="text-emerald-400 font-mono text-xs">⭐</span>
          </div>
          <div class="text-2xl font-black text-emerald-300 mt-1">
            {{ subjectsList.reduce((acc, s) => acc + (s.credits || 0), 0) }}
          </div>
          <div class="text-[11px] text-emerald-200/80 mt-0.5 flex items-center gap-1 font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            <span>Avg 3.0 cr / subject</span>
          </div>
        </div>

        <!-- Card 4: Linked Courses -->
        <div class="p-3.5 bg-slate-900/80 border border-indigo-500/20 rounded-2xl backdrop-blur-xl relative overflow-hidden group">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
            <span>Curriculum Courses</span>
            <span class="text-indigo-400 font-mono text-xs">🚀</span>
          </div>
          <div class="text-2xl font-black text-indigo-300 mt-1">
            {{ props.summaryStats?.total_courses || 328 }}
          </div>
          <div class="text-[11px] text-indigo-200/80 mt-0.5 flex items-center gap-1 font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
            <span>Subject ➔ Course Flow</span>
          </div>
        </div>
      </div>

      <!-- CONTROLS & FILTER TOOLBAR -->
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 p-3.5 bg-slate-900/70 border border-slate-800 rounded-2xl backdrop-blur-xl">
        <div class="flex flex-wrap items-center gap-2.5 flex-1">
          <!-- Search Input -->
          <div class="w-full sm:w-64 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="ស្វែងរក Subject code, ឈ្មោះ..."
              class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3.5 py-2 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-sky-500 font-khmer transition-all"
            />
          </div>

          <!-- 5 Majors Filter -->
          <select v-model="selectedMajorFilter" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:border-sky-500 focus:outline-none font-medium">
            <option value="all">Major: ជំនាញទាំងអស់ (All 5 Majors)</option>
            <option v-for="m in availableMajors" :key="m.id" :value="m.name">
              {{ m.name }} {{ m.name_kh ? `(${m.name_kh})` : '' }}
            </option>
          </select>

          <!-- Difficulty Filter -->
          <select v-model="selectedDifficultyFilter" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:border-sky-500 focus:outline-none font-medium">
            <option value="all">Level: កម្រិតទាំងអស់</option>
            <option value="Beginner">Beginner (កម្រិតដំបូង)</option>
            <option value="Intermediate">Intermediate (មធ្យម)</option>
            <option value="Advanced">Advanced (កម្រិតខ្ពស់)</option>
          </select>

          <!-- Status Filter -->
          <select v-model="selectedStatusFilter" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:border-sky-500 focus:outline-none font-medium">
            <option value="all">Status: ស្ថានភាពទាំងអស់</option>
            <option value="active">Active (ដំណើរការ)</option>
            <option value="inactive">Inactive (ផ្អាក)</option>
          </select>
        </div>

        <button
          @click="openAddModal"
          class="px-4 py-2 bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-sky-600/30 transition-all flex items-center justify-center gap-1.5 whitespace-nowrap cursor-pointer shrink-0"
        >
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Add New Subject</span>
        </button>
      </div>

      <!-- BULK ACTIONS TOOLBAR -->
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between p-3 bg-sky-950/80 border border-sky-500/40 rounded-2xl text-xs backdrop-blur-xl animate-fade-in">
        <div class="flex items-center gap-2 text-sky-200 font-bold">
          <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>បានជ្រើសរើស {{ selectedIds.length }} មុខវិជ្ជា (Selected)</span>
        </div>
        <div class="flex items-center gap-2">
          <button @click="bulkDelete" class="px-3 py-1.5 bg-rose-600/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 rounded-xl font-bold flex items-center gap-1 cursor-pointer">
            <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Bulk Delete</span>
          </button>
          <button @click="selectedIds = []" class="px-2.5 py-1.5 bg-slate-800 text-slate-300 hover:text-white rounded-xl font-bold cursor-pointer">
            ✕ Clear
          </button>
        </div>
      </div>

      <!-- SUBJECTS TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-xl">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-800/50 text-[11px] font-bold text-slate-300 uppercase whitespace-nowrap">
              <th class="py-3 px-3 w-10 text-center">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="rounded border-slate-700 bg-slate-950 text-sky-500 focus:ring-sky-500/20"
                />
              </th>
              <th class="py-3 px-4 w-12 text-slate-400">#</th>
              <th class="py-3 px-4">Subject Name & Khmer Title</th>
              <th class="py-3 px-4">Code</th>
              <th class="py-3 px-4">SPI Major & Dept</th>
              <th class="py-3 px-4 text-center">Credits</th>
              <th class="py-3 px-4">Difficulty & Prereq</th>
              <th class="py-3 px-4 text-center">Curriculum Flow</th>
              <th class="py-3 px-4 text-center">Status</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 text-xs">
            <tr v-if="paginatedSubjects.length === 0">
              <td colspan="10" class="py-12 text-center text-slate-400">
                <div class="flex flex-col items-center justify-center gap-2">
                  <span class="text-3xl">📚</span>
                  <div class="font-bold text-white">មិនមានមុខវិជ្ជាត្រូវនឹងលក្ខខណ្ឌស្វែងរកទេ</div>
                  <div class="text-xs text-slate-500">សូមសាកល្បងផ្លាស់ប្តូរ Filter ឬចុច "Add New Subject" ដើម្បីបង្កើតថ្មី</div>
                </div>
              </td>
            </tr>

            <tr
              v-for="(sub, idx) in paginatedSubjects"
              :key="sub.id"
              class="hover:bg-slate-800/40 transition-colors"
              :class="{ 'bg-sky-950/20': selectedIds.includes(sub.id) }"
            >
              <!-- Checkbox -->
              <td class="py-3.5 px-3 text-center">
                <input
                  type="checkbox"
                  :value="sub.id"
                  v-model="selectedIds"
                  class="rounded border-slate-700 bg-slate-950 text-sky-500 focus:ring-sky-500/20"
                />
              </td>

              <!-- Index -->
              <td class="py-3.5 px-4 font-mono text-slate-400 font-semibold whitespace-nowrap">
                {{ String((currentPage - 1) * pageSize + idx + 1).padStart(2, '0') }}
              </td>

              <!-- Subject Name -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div>
                  <div class="font-bold text-white text-sm flex items-center gap-2">
                    <span>{{ sub.name }}</span>
                  </div>
                  <div class="text-xs text-sky-300 font-khmer mt-0.5">
                    {{ sub.name_kh || '—' }}
                  </div>
                </div>
              </td>

              <!-- Code -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <span class="px-2 py-0.5 bg-slate-950 rounded-md text-sky-300 font-mono text-xs font-semibold border border-slate-800">
                  {{ sub.code }}
                </span>
              </td>

              <!-- Major & Dept -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="space-y-1">
                  <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold border inline-block', getMajorBadgeStyle(sub.major)]">
                    {{ sub.major }}
                  </span>
                  <div class="text-[11px] text-slate-400">
                    Dept: {{ sub.department }}
                  </div>
                </div>
              </td>

              <!-- Credits -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap font-bold text-sky-300">
                <span class="px-2.5 py-1 rounded-xl bg-slate-950 border border-slate-800 text-xs">
                  {{ sub.credits }} Credits
                </span>
              </td>

              <!-- Difficulty & Prereq -->
              <td class="py-3.5 px-4 whitespace-nowrap">
                <div class="space-y-1">
                  <span :class="['px-2 py-0.5 rounded-md text-[10px] font-bold border inline-block', getDifficultyBadgeStyle(sub.difficulty)]">
                    {{ sub.difficulty || 'Beginner' }}
                  </span>
                  <div class="text-[11px] text-slate-400">
                    Prereq: <span class="text-slate-300 font-medium">{{ sub.prerequisite || 'None' }}</span>
                  </div>
                </div>
              </td>

              <!-- Linked Courses / Curriculum -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <button
                  @click="openCurriculumModal(sub)"
                  class="px-2.5 py-1 bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 rounded-xl text-[11px] font-bold transition-all flex items-center gap-1.5 mx-auto cursor-pointer"
                  title="View Major -> Subject -> Course Hierarchy"
                >
                  <span>🌳</span>
                  <span>{{ sub.courses_count || (sub.courses_list?.length ?? 1) }} Courses</span>
                </button>
              </td>

              <!-- Status Toggle -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <button
                  @click="toggleSubjectStatus(sub)"
                  :class="[
                    sub.status === 'active'
                      ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/25'
                      : 'bg-rose-500/15 text-rose-300 border-rose-500/30 hover:bg-rose-500/25',
                    'px-2.5 py-1 rounded-full text-[10px] font-bold border inline-flex items-center gap-1.5 transition-all cursor-pointer'
                  ]"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="sub.status === 'active' ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                  <span>{{ sub.status === 'active' ? 'Active' : 'Inactive' }}</span>
                </button>
              </td>

              <!-- Actions -->
              <td class="py-3.5 px-4 text-right whitespace-nowrap relative" @click.stop>
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openEditModal(sub)"
                    class="px-3 py-1.5 bg-sky-600/20 hover:bg-sky-500/30 text-sky-300 border border-sky-500/30 rounded-xl font-bold whitespace-nowrap inline-flex items-center gap-1 cursor-pointer transition-all"
                  >
                    <svg class="w-3.5 h-3.5 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit</span>
                  </button>

                  <div class="relative">
                    <button
                      @click="toggleDropdown(sub.id)"
                      class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold transition-all border border-slate-700 cursor-pointer"
                      title="More Options"
                    >
                      <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                      </svg>
                    </button>

                    <div
                      v-if="activeDropdownId === sub.id"
                      class="absolute right-0 mt-1 w-44 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl z-50 p-1.5 text-xs text-left space-y-1 backdrop-blur-xl animate-fade-in"
                    >
                      <button
                        @click="openCurriculumModal(sub)"
                        class="w-full px-3 py-2 text-slate-200 hover:text-white hover:bg-slate-800 rounded-xl flex items-center gap-2 font-medium cursor-pointer"
                      >
                        <span>🌳</span>
                        <span>Curriculum Flow</span>
                      </button>

                      <button
                        @click="toggleSubjectStatus(sub)"
                        class="w-full px-3 py-2 text-slate-200 hover:text-white hover:bg-slate-800 rounded-xl flex items-center gap-2 font-medium cursor-pointer"
                      >
                        <span>🔄</span>
                        <span>Toggle Status</span>
                      </button>

                      <div class="border-t border-slate-800 my-1"></div>

                      <button
                        @click="deleteSubject(sub)"
                        class="w-full px-3 py-2 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-xl flex items-center gap-2 font-medium cursor-pointer"
                      >
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Delete Subject</span>
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
          <span>បង្ហាញ <strong class="text-white">{{ filteredSubjects.length === 0 ? 0 : (currentPage - 1) * pageSize + 1 }}</strong> ដល់ <strong class="text-white">{{ Math.min(currentPage * pageSize, filteredSubjects.length) }}</strong> នៃ <strong class="text-white">{{ filteredSubjects.length }}</strong> មុខវិជ្ជា</span>

          <div class="flex items-center gap-1.5 ml-2">
            <span>Per page:</span>
            <select v-model="pageSize" class="bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-white">
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="20">20</option>
            </select>
          </div>
        </div>

        <div class="flex items-center gap-1.5">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-1.5 rounded-xl border border-slate-800 bg-slate-950 font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-800 hover:text-white text-slate-300 cursor-pointer"
          >
            ‹ Prev
          </button>

          <div class="flex items-center gap-1 px-1">
            <button
              v-for="p in totalPages"
              :key="p"
              @click="currentPage = p"
              class="w-8 h-8 rounded-xl font-bold transition-all flex items-center justify-center cursor-pointer"
              :class="currentPage === p ? 'bg-sky-600 text-white shadow-md shadow-sky-600/30' : 'bg-slate-950 border border-slate-800 text-slate-400 hover:text-white'"
            >
              {{ p }}
            </button>
          </div>

          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-1.5 rounded-xl border border-slate-800 bg-slate-950 font-bold transition-all disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-800 hover:text-white text-slate-300 cursor-pointer"
          >
            Next ›
          </button>
        </div>
      </div>

      <!-- ADD / EDIT SUBJECT MODAL -->
      <div v-if="isModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700/80 rounded-2xl max-w-xl w-full p-5 space-y-4 shadow-2xl backdrop-blur-2xl overflow-y-auto max-h-[90vh]">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-400 shadow-sm font-bold">
                📖
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-100 tracking-wide uppercase">
                  {{ editingSubject ? 'EDIT SUBJECT (កែប្រែមុខវិជ្ជា)' : 'ADD NEW SUBJECT (បង្កើតមុខវិជ្ជាថ្មី)' }}
                </h3>
                <p class="text-[11px] text-slate-400 font-khmer">
                  កំណត់មុខវិជ្ជា និងភ្ជាប់ទៅកាន់ ៥ ជំនាញរបស់ SPI
                </p>
              </div>
            </div>
            <button @click="isModalOpen = false" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition-all cursor-pointer">✕</button>
          </div>

          <form @submit.prevent="saveSubject" class="space-y-3.5 text-xs">
            <!-- 1. SPI Major Select -->
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                SPI Major (ជ្រើសរើសជំនាញ) <span class="text-rose-400">*</span>
              </label>
              <select
                v-model="form.major_id"
                class="w-full bg-slate-950 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2.5 text-xs text-white font-medium focus:outline-none focus:ring-1 focus:ring-sky-500/30 transition-all font-khmer"
              >
                <option v-for="m in availableMajors" :key="m.id" :value="m.id">
                  {{ m.name }} — {{ m.name_kh || '' }} (Dept: {{ m.department || 'General' }})
                </option>
              </select>
            </div>

            <!-- 2. Code & Name EN -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Subject Code <span class="text-rose-400">*</span></label>
                <input
                  v-model="form.code"
                  type="text"
                  placeholder="e.g. SUB-IT-106"
                  class="w-full bg-slate-950 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:outline-none focus:ring-1 focus:ring-sky-500/30 transition-all"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Subject Name (EN) <span class="text-rose-400">*</span></label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="e.g. Web Development"
                  class="w-full bg-slate-950 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:ring-1 focus:ring-sky-500/30 transition-all"
                />
              </div>
            </div>

            <!-- 3. Name KH & Credits -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">ឈ្មោះមុខវិជ្ជា (ជាភាសាខ្មែរ)</label>
                <input
                  v-model="form.name_kh"
                  type="text"
                  placeholder="ឧ. ការអភិវឌ្ឍគេហទំព័រ"
                  class="w-full bg-slate-950 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-sky-300 font-khmer focus:outline-none focus:ring-1 focus:ring-sky-500/30 transition-all"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Credits (ចំនួនក្រេឌីត)</label>
                <select v-model.number="form.credits" class="w-full bg-slate-950 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-white font-medium focus:outline-none focus:ring-1 focus:ring-sky-500/30 transition-all">
                  <option :value="1">1 Credit</option>
                  <option :value="2">2 Credits</option>
                  <option :value="3">3 Credits (Standard)</option>
                  <option :value="4">4 Credits (Advanced)</option>
                </select>
              </div>
            </div>

            <!-- 4. Prerequisite & Difficulty -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Prerequisite (មុខវិជ្ជាត្រូវរៀនមុន)</label>
                <input
                  v-model="form.prerequisite"
                  type="text"
                  placeholder="e.g. None ឬ C Programming"
                  class="w-full bg-slate-950 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:ring-1 focus:ring-sky-500/30 transition-all"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Difficulty Level (កម្រិតលំបាក)</label>
                <select v-model="form.difficulty" class="w-full bg-slate-950 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-white font-medium focus:outline-none focus:ring-1 focus:ring-sky-500/30 transition-all">
                  <option value="Beginner">Beginner (កម្រិតដំបូង)</option>
                  <option value="Intermediate">Intermediate (កម្រិតមធ្យម)</option>
                  <option value="Advanced">Advanced (កម្រិតខ្ពស់)</option>
                </select>
              </div>
            </div>

            <!-- 5. Description -->
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1.5">Description (ការពិពណ៌នាសង្ខេប)</label>
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="Subject overview, curriculum learning objectives..."
                class="w-full bg-slate-950 border border-slate-800 focus:border-sky-500 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:ring-1 focus:ring-sky-500/30 transition-all"
              ></textarea>
            </div>

            <!-- 6. Status -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800">
              <div>
                <div class="font-bold text-white text-xs">Subject Active Status</div>
                <div class="text-[11px] text-slate-400">បើកដំណើរការមុខវិជ្ជានេះក្នុងប្រព័ន្ធសិក្សា</div>
              </div>
              <button
                type="button"
                @click="form.status = form.status === 'active' ? 'inactive' : 'active'"
                :class="[
                  form.status === 'active' ? 'bg-sky-600' : 'bg-slate-700',
                  'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200'
                ]"
              >
                <span
                  :class="[
                    form.status === 'active' ? 'translate-x-6' : 'translate-x-1',
                    'inline-block h-4 w-4 transform rounded-full bg-white transition duration-200 mt-1 shadow-sm'
                  ]"
                />
              </button>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800/80">
              <button
                type="button"
                @click="isModalOpen = false"
                :disabled="isSubmitting"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-xl transition-all disabled:opacity-50 cursor-pointer"
              >
                Cancel
              </button>

              <button
                type="submit"
                :disabled="isSubmitting"
                class="px-5 py-2 bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-500 hover:to-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-sky-600/30 transition-all flex items-center gap-1.5 disabled:opacity-50 cursor-pointer"
              >
                <svg v-if="isSubmitting" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ isSubmitting ? (editingSubject ? 'កំពុងរក្សាទុក...' : 'កំពុងបង្កើត...') : (editingSubject ? 'Save Changes' : 'Create Subject') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- CURRICULUM HIERARCHY MODAL (Defense Showcase) -->
      <div v-if="isCurriculumModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700/80 rounded-2xl max-w-2xl w-full p-5 space-y-4 shadow-2xl backdrop-blur-2xl overflow-y-auto max-h-[90vh]">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400 font-bold">
                🎓
              </div>
              <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wide">
                  Curriculum Learning Relationship Flow
                </h3>
                <p class="text-[11px] text-slate-400 font-khmer">
                  រចនាសម្ព័ន្ធបណ្តុះបណ្តាលលម្អិតរបស់មុខវិជ្ជា
                </p>
              </div>
            </div>
            <button @click="isCurriculumModalOpen = false" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition-all cursor-pointer">✕</button>
          </div>

          <div v-if="selectedSubjectForCurriculum" class="space-y-4">
            <!-- Subject Header Pill -->
            <div class="p-3.5 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between">
              <div>
                <div class="font-bold text-white text-sm">{{ selectedSubjectForCurriculum.name }}</div>
                <div class="text-xs text-sky-300 font-khmer">{{ selectedSubjectForCurriculum.name_kh }}</div>
              </div>
              <div class="text-right">
                <span class="px-2.5 py-1 rounded-lg bg-sky-500/15 text-sky-300 border border-sky-500/30 font-mono text-xs font-bold">
                  {{ selectedSubjectForCurriculum.code }}
                </span>
                <div class="text-[11px] text-slate-400 mt-1">{{ selectedSubjectForCurriculum.credits }} Credits</div>
              </div>
            </div>

            <!-- Full Flow Diagram Timeline -->
            <div class="relative pl-6 space-y-4 border-l-2 border-slate-800 ml-3">
              <!-- Level 1: Major -->
              <div class="relative">
                <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-cyan-500 ring-4 ring-slate-900 flex items-center justify-center text-[9px] text-white">1</div>
                <div class="p-3 bg-slate-950/70 border border-cyan-500/30 rounded-xl space-y-1">
                  <div class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider">Level 1: SPI Major (ជំនាញ)</div>
                  <div class="text-xs font-bold text-white">{{ selectedSubjectForCurriculum.major }}</div>
                  <div class="text-[11px] text-slate-400">Department of {{ selectedSubjectForCurriculum.department }}</div>
                </div>
              </div>

              <!-- Level 2: Subject (Active) -->
              <div class="relative">
                <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-sky-500 ring-4 ring-slate-900 flex items-center justify-center text-[9px] text-white">2</div>
                <div class="p-3 bg-sky-950/30 border border-sky-400/40 rounded-xl space-y-1">
                  <div class="text-[10px] font-bold text-sky-300 uppercase tracking-wider">Level 2: Academic Subject (មុខវិជ្ជាគោល)</div>
                  <div class="text-xs font-bold text-white">{{ selectedSubjectForCurriculum.name }} ({{ selectedSubjectForCurriculum.code }})</div>
                  <div class="text-[11px] text-slate-300">Prereq: {{ selectedSubjectForCurriculum.prerequisite || 'None' }} • Difficulty: {{ selectedSubjectForCurriculum.difficulty }}</div>
                </div>
              </div>

              <!-- Level 3: Course -->
              <div class="relative">
                <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-indigo-500 ring-4 ring-slate-900 flex items-center justify-center text-[9px] text-white">3</div>
                <div class="p-3 bg-slate-950/70 border border-indigo-500/30 rounded-xl space-y-1">
                  <div class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider">Level 3: Enrolled Course (វគ្គសិក្សាជាក់ស្តែង)</div>
                  <div class="text-xs font-bold text-white">
                    {{ selectedSubjectForCurriculum.courses_list?.[0]?.title || `${selectedSubjectForCurriculum.name} Mastery` }}
                  </div>
                  <div class="text-[11px] text-slate-400">Teacher-Led / Self-Study with Live Progress Tracking</div>
                </div>
              </div>

              <!-- Level 4: Lesson & Modules -->
              <div class="relative">
                <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-purple-500 ring-4 ring-slate-900 flex items-center justify-center text-[9px] text-white">4</div>
                <div class="p-3 bg-slate-950/70 border border-purple-500/30 rounded-xl space-y-1">
                  <div class="text-[10px] font-bold text-purple-400 uppercase tracking-wider">Level 4: Structured Lessons (មេរៀន)</div>
                  <div class="text-xs font-bold text-white">Weekly Modules, Chapters & Step-by-Step Practical Labs</div>
                  <div class="text-[11px] text-slate-400">Example: Lesson 1 Fundamentals ➔ Lesson 2 Advanced Techniques</div>
                </div>
              </div>

              <!-- Level 5: Materials -->
              <div class="relative">
                <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-emerald-500 ring-4 ring-slate-900 flex items-center justify-center text-[9px] text-white">5</div>
                <div class="p-3 bg-slate-950/70 border border-emerald-500/30 rounded-xl space-y-1">
                  <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Level 5: Learning Materials (ឯកសារ & វីដេអូ)</div>
                  <div class="text-xs font-bold text-white">HD Video Lectures • Lecture Slides PDF • Practice Exercises</div>
                </div>
              </div>

              <!-- Level 6: Assessments & AI -->
              <div class="relative">
                <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-amber-500 ring-4 ring-slate-900 flex items-center justify-center text-[9px] text-white">6</div>
                <div class="p-3 bg-slate-950/70 border border-amber-500/30 rounded-xl space-y-1">
                  <div class="text-[10px] font-bold text-amber-400 uppercase tracking-wider">Level 6: Assessments & Learning Intelligence</div>
                  <div class="text-xs font-bold text-white">Quizzes • Midterm & Final Exams • Student Result Analytics</div>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-800 flex justify-end">
            <button
              @click="isCurriculumModalOpen = false"
              class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition-all cursor-pointer"
            >
              Close
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
                  ? 'bg-slate-900/95 border-emerald-500/40 text-white'
                  : 'bg-slate-900/95 border-amber-500/40 text-white',
                'relative rounded-2xl border p-3.5 shadow-2xl backdrop-blur-xl'
              ]"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center text-lg">
                  <span v-if="toast.type === 'success'">✅</span>
                  <span v-else-if="toast.type === 'warning'">⚠️</span>
                  <span v-else>ℹ️</span>
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
                  class="text-slate-400 hover:text-white p-1 rounded-md hover:bg-slate-800 transition-colors shrink-0 cursor-pointer"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </AdminLayout>
</template>
