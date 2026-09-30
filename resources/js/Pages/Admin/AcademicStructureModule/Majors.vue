<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AcademicModuleHeader from '@/Components/Admin/AcademicModuleHeader.vue'

const props = withDefaults(defineProps<{
  majors?: any[]
  faculties?: any[]
  summaryStats?: any
}>(), {
  majors: () => [
    {
      id: 1, code: 'MJR-IT-001', name: 'Information Technology', name_kh: 'បច្ចេកវិទ្យាព័ត៌មាន', department: 'Computing', faculty: 'Faculty of Computing', students_count: 520, teachers_count: 25, courses_count: 32, price_per_subject: 25, duration: '4 Years', degree_level: 'Bachelor', credits: 120, language: 'English / Khmer', status: 'active',
      linked_courses: [
        { name: 'C Programming', price: 25, teacher: 'Mr. Sophea' },
        { name: 'Web Development', price: 30, teacher: 'Ms. Dara' },
        { name: 'Database Systems', price: 25, teacher: 'Mr. Sophea' },
        { name: 'Networking Basics', price: 20, teacher: 'Mr. Vuthy' },
      ]
    },
    {
      id: 2, code: 'MJR-TRM-002', name: 'Tourism', name_kh: 'ទេសចរណ៍', department: 'Tourism', faculty: 'Faculty of Tourism', students_count: 410, teachers_count: 18, courses_count: 28, price_per_subject: 20, duration: '4 Years', degree_level: 'Bachelor', credits: 120, language: 'English / Khmer', status: 'active',
      linked_courses: [
        { name: 'Tourism Basics', price: 20, teacher: 'Mr. Long' },
        { name: 'Hospitality Management', price: 25, teacher: 'Ms. Dara' },
      ]
    },
    {
      id: 3, code: 'MJR-ENG-003', name: 'English Literature', name_kh: 'អក្សរសាស្ត្រអង់គ្លេស', department: 'Education', faculty: 'Faculty of Education', students_count: 380, teachers_count: 20, courses_count: 24, price_per_subject: 20, duration: '4 Years', degree_level: 'Bachelor', credits: 120, language: 'English', status: 'active',
      linked_courses: [
        { name: 'English Grammar', price: 0, teacher: 'Ms. Srey' },
        { name: 'English Writing', price: 20, teacher: 'Ms. Srey' },
      ]
    },
    {
      id: 4, code: 'MJR-AGR-004', name: 'Agriculture', name_kh: 'កសិកម្ម', department: 'Agriculture', faculty: 'Faculty of Agriculture', students_count: 600, teachers_count: 22, courses_count: 30, price_per_subject: 25, duration: '4 Years', degree_level: 'Bachelor', credits: 120, language: 'Khmer', status: 'active',
      linked_courses: [
        { name: 'Plant Science', price: 25, teacher: 'Mr. Vuthy' },
        { name: 'Soil Studies', price: 20, teacher: 'Mr. Vuthy' },
      ]
    },
    {
      id: 5, code: 'MJR-SW-005', name: 'Social Work', name_kh: 'ការងារសង្គម', department: 'Social Science', faculty: 'Faculty of Social Science', students_count: 548, teachers_count: 15, courses_count: 26, price_per_subject: 25, duration: '4 Years', degree_level: 'Bachelor', credits: 120, language: 'Khmer', status: 'active',
      linked_courses: [
        { name: 'Social Work 101', price: 25, teacher: 'Mr. Rithy' },
        { name: 'Community Dev', price: 30, teacher: 'Mr. Rithy' },
      ]
    },
  ],
  faculties: () => ['Faculty of Computing', 'Faculty of Tourism', 'Faculty of Education', 'Faculty of Agriculture', 'Faculty of Social Science'],
  summaryStats: () => ({})
})

const defaultMajors = [
  { id: 1, code: 'MJR-IT-001', name: 'Information Technology', name_kh: 'បច្ចេកវិទ្យាព័ត៌មាន', department: 'Computing', faculty: 'Faculty of Computing', students_count: 520, teachers_count: 25, courses_count: 32, price_per_subject: 25, duration: '4 Years', degree_level: 'Bachelor', credits: 120, language: 'English / Khmer', status: 'active' },
  { id: 2, code: 'MJR-TRM-002', name: 'Tourism', name_kh: 'ទេសចរណ៍', department: 'Tourism', faculty: 'Faculty of Tourism', students_count: 410, teachers_count: 18, courses_count: 28, price_per_subject: 20, duration: '4 Years', degree_level: 'Bachelor', credits: 120, language: 'English / Khmer', status: 'active' },
  { id: 3, code: 'MJR-ENG-003', name: 'English Literature', name_kh: 'អក្សរសាស្ត្រអង់គ្លេស', department: 'Education', faculty: 'Faculty of Education', students_count: 380, teachers_count: 20, courses_count: 24, price_per_subject: 20, duration: '4 Years', degree_level: 'Bachelor', credits: 120, language: 'English', status: 'active' },
  { id: 4, code: 'MJR-AGR-004', name: 'Agriculture', name_kh: 'កសិកម្ម', department: 'Agriculture', faculty: 'Faculty of Agriculture', students_count: 600, teachers_count: 22, courses_count: 30, price_per_subject: 25, duration: '4 Years', degree_level: 'Bachelor', credits: 120, language: 'Khmer', status: 'active' },
  { id: 5, code: 'MJR-SW-005', name: 'Social Work', name_kh: 'ការងារសង្គម', department: 'Social Science', faculty: 'Faculty of Social Science', students_count: 548, teachers_count: 15, courses_count: 26, price_per_subject: 25, duration: '4 Years', degree_level: 'Bachelor', credits: 120, language: 'Khmer', status: 'active' },
]

const searchQuery = ref('')
const selectedFacultyFilter = ref('all')

const activeStatusIconUrl = '/images/nav/active-status.svg'
const inactiveStatusIconUrl = '/images/nav/inactive-status.svg'
const saveIconUrl = '/images/nav/save-icon.svg'
const cancelIconUrl = '/images/nav/cancel-icon.svg'
const majorIconUrl = '/images/nav/major-icon.svg'

// Sorting State
const sortKey = ref('name')
const sortOrder = ref<'asc' | 'desc'>('asc')

// Pagination State
const currentPage = ref(1)
const itemsPerPage = ref(5)

const toggleSort = (key: string) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'asc'
  }
}

const targetRoute = (name: string, param?: any) => {
  try {
    return param !== undefined ? (window as any).route(`admin.${name}`, param) : (window as any).route(`admin.${name}`)
  } catch (e) {
    return param !== undefined ? (window as any).route(name, param) : (window as any).route(name)
  }
}

const getMajorStatus = (mjr: any) => {
  if (mjr.status) return mjr.status
  return mjr.is_active === false || mjr.is_active === 0 ? 'inactive' : 'active'
}

const toggleMajorStatus = (mjr: any) => {
  const currentStatus = getMajorStatus(mjr)
  const newStatus = currentStatus === 'inactive' ? 'active' : 'inactive'
  const newIsActive = newStatus === 'active'

  if (mjr.id) {
    router.put(
      targetRoute('academic-structure.majors.update', mjr.id),
      { ...mjr, status: newStatus, is_active: newIsActive },
      {
        preserveScroll: true,
        onSuccess: () => {
          mjr.status = newStatus
          mjr.is_active = newIsActive
          triggerToast(
            'បច្ចុប្បន្នភាពស្ថានភាពជោគជ័យ',
            `ស្ថានភាពជំនាញ "${mjr.name}" ត្រូវបានផ្លាស់ប្តូរទៅជា ${newStatus.toUpperCase()}`
          )
        },
        onError: () => {
          triggerToast('មានបញ្ហាក្នុងការផ្លាស់ប្តូរស្ថានភាព', 'សូមព្យាយាមម្តងទៀត', 'warning')
        }
      }
    )
  } else {
    mjr.status = newStatus
    mjr.is_active = newIsActive
  }
}

const clearSearch = () => {
  searchQuery.value = ''
}

const isModalOpen = ref(false)
const editingMajor = ref<any>(null)
const form = ref({
  code: '',
  name: '',
  name_kh: '',
  faculty: 'Faculty of Computing',
  department: 'Computing',
  duration: '4 Years',
  degree_level: 'Bachelor',
  credits: 120,
  language: 'English / Khmer',
  price_per_subject: 25,
  status: 'active',
  is_active: true,
  linked_courses: [] as any[]
})

const filteredAndSortedMajors = computed(() => {
  const list = Array.isArray(props.majors) && props.majors.length > 0 ? props.majors : defaultMajors

  let result = list.filter(mjr => {
    const name = mjr.name || ''
    const nameKh = mjr.name_kh || ''
    const code = mjr.code || ''
    const dept = typeof mjr.department === 'object' ? (mjr.department?.name || mjr.department?.name_kh || '') : (mjr.department || '')
    const fac = typeof mjr.faculty === 'object' ? (mjr.faculty?.name || mjr.faculty?.name_kh || '') : (mjr.faculty || '')
    const q = searchQuery.value.toLowerCase()

    const matchesSearch = name.toLowerCase().includes(q) ||
                          nameKh.includes(searchQuery.value) ||
                          code.toLowerCase().includes(q) ||
                          dept.toLowerCase().includes(q)
    const matchesFaculty = selectedFacultyFilter.value === 'all' || fac === selectedFacultyFilter.value
    return matchesSearch && matchesFaculty
  })

  result.sort((a, b) => {
    let valA = a[sortKey.value]
    let valB = b[sortKey.value]

    if (sortKey.value === 'status') {
      valA = getMajorStatus(a)
      valB = getMajorStatus(b)
    } else if (sortKey.value === 'students_count') {
      valA = a.students_count ?? 0
      valB = b.students_count ?? 0
    } else if (sortKey.value === 'price_per_subject') {
      valA = a.price_per_subject ?? 0
      valB = b.price_per_subject ?? 0
    }

    if (valA === valB) return 0
    if (valA === undefined || valA === null) return 1
    if (valB === undefined || valB === null) return -1

    if (typeof valA === 'string') {
      return sortOrder.value === 'asc'
        ? valA.localeCompare(valB)
        : valB.localeCompare(valA)
    }

    return sortOrder.value === 'asc' ? valA - valB : valB - valA
  })

  return result
})

const totalPages = computed(() => {
  return Math.ceil(filteredAndSortedMajors.value.length / itemsPerPage.value) || 1
})

const paginatedMajors = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredAndSortedMajors.value.slice(start, start + itemsPerPage.value)
})

const openAddModal = () => {
  editingMajor.value = null
  const currentList = Array.isArray(props.majors) ? props.majors : defaultMajors
  form.value = {
    code: `MJR-NEW-0${currentList.length + 1}`,
    name: '',
    name_kh: '',
    faculty: props.faculties[0] || 'Faculty of Computing',
    department: 'Computing',
    duration: '4 Years',
    degree_level: 'Bachelor',
    credits: 120,
    language: 'English / Khmer',
    price_per_subject: 25,
    status: 'active',
    is_active: true,
    linked_courses: []
  }
  isModalOpen.value = true
}

const openEditModal = (mjr: any) => {
  editingMajor.value = mjr
  form.value = {
    ...mjr,
    status: getMajorStatus(mjr),
    is_active: getMajorStatus(mjr) === 'active',
    faculty: typeof mjr.faculty === 'object' ? (mjr.faculty?.name || mjr.faculty?.name_kh || 'Faculty of Computing') : (mjr.faculty || 'Faculty of Computing'),
    department: typeof mjr.department === 'object' ? (mjr.department?.name || mjr.department?.name_kh || 'Computing') : (mjr.department || 'Computing'),
  }
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

const saveMajor = () => {
  if (!form.value.name) {
    triggerToast('សូមបញ្ចូលឈ្មោះជំនាញ / ឯកទេស', 'សូមបញ្ចូល Major Name (English) មុនពេលរក្សាទុក', 'warning')
    return
  }

  isSubmitting.value = true
  const majorName = form.value.name || 'ជំនាញ'

  if (editingMajor.value?.id) {
    router.put(targetRoute('academic-structure.majors.update', editingMajor.value.id), form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false
        isModalOpen.value = false
        triggerToast(
          'រក្សាទុកបានជោគជ័យ',
          `ព័ត៌មានជំនាញ "${majorName}" ត្រូវបានបច្ចុប្បន្នភាពដោយជោគជ័យ`
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
    router.post(targetRoute('academic-structure.majors.store'), form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false
        isModalOpen.value = false
        triggerToast(
          'បង្កើតជំនាញបានជោគជ័យ',
          `ជំនាញ "${majorName}" ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        isSubmitting.value = false
        triggerToast(
          'មានបញ្ហាក្នុងការបង្កើតជំនាញ',
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

const deleteMajor = (mjr: any) => {
  if (confirm(`Are you sure you want to delete '${mjr.name}'?`)) {
    router.delete(targetRoute('academic-structure.majors.destroy', mjr.id), {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast(
          'លុបជំនាញបានជោគជ័យ',
          `ជំនាញ "${mjr.name}" ត្រូវបានលុបចេញពីប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast(
          'មានបញ្ហាក្នុងការលុប',
          'មិនអាចលុបជំនាញនេះបានទេ',
          'warning'
        )
      }
    })
  }
}
</script>

<template>
  <AdminLayout title="Majors — Academic Structure">
    <div class="space-y-5 font-sans mb-8">
      <!-- Shared Header -->
      <AcademicModuleHeader activeTab="majors" :summaryStats="props.summaryStats" />

      <!-- OVERVIEW STATS CARDS (Soft Glassmorphic Glowing Border Frames) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Card 1: Total Programs -->
        <div class="relative group p-4.5 bg-gradient-to-b from-slate-900/90 via-slate-900/70 to-cyan-950/20 border border-cyan-500/20 hover:border-cyan-500/35 rounded-2xl backdrop-blur-xl transition-all duration-300 shadow-lg hover:shadow-cyan-500/10 space-y-1 overflow-hidden">
          <div class="absolute -right-6 -top-6 w-20 h-20 bg-cyan-500/10 rounded-full blur-xl group-hover:bg-cyan-500/20 transition-all pointer-events-none"></div>
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
              <span>🎓</span>
              <span>Total Majors</span>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">Programs</span>
          </div>
          <div class="text-3xl font-black text-white tracking-tight pt-1">
            {{ filteredAndSortedMajors.length }}
          </div>
          <div class="text-xs text-cyan-300/90 font-medium flex items-center gap-1.5 pt-0.5">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
            <span>Degree Majors</span>
          </div>
        </div>

        <!-- Card 2: Total Students -->
        <div class="relative group p-4.5 bg-gradient-to-b from-slate-900/90 via-slate-900/70 to-emerald-950/20 border border-emerald-500/20 hover:border-emerald-500/35 rounded-2xl backdrop-blur-xl transition-all duration-300 shadow-lg hover:shadow-emerald-500/10 space-y-1 overflow-hidden">
          <div class="absolute -right-6 -top-6 w-20 h-20 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition-all pointer-events-none"></div>
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
              <span>👨‍🎓</span>
              <span>Total Students</span>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">Enrolled</span>
          </div>
          <div class="text-3xl font-black text-emerald-300 tracking-tight pt-1">
            2,458
          </div>
          <div class="text-xs text-emerald-200/90 font-medium flex items-center gap-1.5 pt-0.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            <span>Across All Majors</span>
          </div>
        </div>

        <!-- Card 3: Average Duration -->
        <div class="relative group p-4.5 bg-gradient-to-b from-slate-900/90 via-slate-900/70 to-indigo-950/20 border border-indigo-500/20 hover:border-indigo-500/35 rounded-2xl backdrop-blur-xl transition-all duration-300 shadow-lg hover:shadow-indigo-500/10 space-y-1 overflow-hidden">
          <div class="absolute -right-6 -top-6 w-20 h-20 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-500/20 transition-all pointer-events-none"></div>
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
              <span>⏳</span>
              <span>Degree Duration</span>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">Bachelor</span>
          </div>
          <div class="text-3xl font-black text-indigo-300 tracking-tight pt-1">
            4 Years
          </div>
          <div class="text-xs text-indigo-200/90 font-medium flex items-center gap-1.5 pt-0.5">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
            <span>120 Credits Required</span>
          </div>
        </div>

        <!-- Card 4: Core Subjects in Curriculum -->
        <div class="relative group p-4.5 bg-gradient-to-b from-slate-900/90 via-slate-900/70 to-sky-950/20 border border-sky-500/20 hover:border-sky-500/35 rounded-2xl backdrop-blur-xl transition-all duration-300 shadow-lg hover:shadow-sky-500/10 space-y-1 overflow-hidden">
          <div class="absolute -right-6 -top-6 w-20 h-20 bg-sky-500/10 rounded-full blur-xl group-hover:bg-sky-500/20 transition-all pointer-events-none"></div>
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
              <span>📖</span>
              <span>Core Subjects</span>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-sky-500/10 text-sky-300 border border-sky-500/20">Curriculum</span>
          </div>
          <div class="text-3xl font-black text-sky-300 tracking-tight pt-1">
            17 Subjects
          </div>
          <div class="text-xs text-sky-200/90 font-medium flex items-center gap-1.5 pt-0.5">
            <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
            <span>Major ➔ Subject Flow</span>
          </div>
        </div>
      </div>

      <!-- SEARCH, FILTER & ACTION TOOLBAR -->
      <div class="flex flex-col md:flex-row items-center justify-between gap-3 p-4 bg-slate-900/80 border border-slate-700/80 rounded-2xl backdrop-blur-xl shadow-lg">
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-1">
          <div class="relative w-full sm:w-80">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-cyan-400 text-sm">
              🔍
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search major name, code, department..."
              class="w-full bg-slate-950 border border-slate-700/80 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 rounded-xl pl-9 pr-9 py-2.5 text-xs text-white placeholder-slate-400 shadow-inner transition-all font-medium"
            />
            <button 
              v-if="searchQuery"
              @click="clearSearch"
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition-colors"
            >
              ✕
            </button>
          </div>

          <select v-model="selectedFacultyFilter" class="w-full sm:w-auto bg-slate-950 border border-slate-700/80 focus:border-cyan-400 rounded-xl px-3.5 py-2.5 text-xs text-slate-200 focus:outline-none transition-colors font-medium">
            <option value="all">Parent Faculty: All Faculties</option>
            <option v-for="fac in props.faculties" :key="fac" :value="fac">{{ fac }}</option>
          </select>
        </div>

        <button 
          @click="openAddModal" 
          class="w-full md:w-auto px-4.5 py-2.5 bg-cyan-500/15 hover:bg-cyan-500/30 text-cyan-200 border border-cyan-500/40 hover:border-cyan-500/70 font-bold text-xs rounded-xl backdrop-blur-md transition-all flex items-center justify-center gap-2 whitespace-nowrap cursor-pointer shadow-xs"
        >
          <span>➕</span>
          <span>Add Major Program</span>
        </button>
      </div>

      <!-- MAJORS TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/80 backdrop-blur-xl shadow-xl">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-800/60 text-xs font-extrabold text-slate-200 uppercase whitespace-nowrap select-none">
              <th class="py-4 px-4 w-12 text-slate-400">#</th>
              
              <!-- Major Name -->
              <th @click="toggleSort('name')" class="py-4 px-4 cursor-pointer hover:text-white transition-colors group">
                <div class="flex items-center gap-2">
                  <span>🎓 Major Name</span>
                  <span :class="[sortKey === 'name' ? 'bg-cyan-500/20 text-cyan-300 font-extrabold border border-cyan-500/40 px-1.5 py-0.5 rounded' : 'text-slate-500 group-hover:text-slate-300', 'text-[11px] transition-all']">
                    {{ sortKey === 'name' ? (sortOrder === 'asc' ? '▲ ASC' : '▼ DESC') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Faculty / Department -->
              <th @click="toggleSort('faculty')" class="py-4 px-4 cursor-pointer hover:text-white transition-colors group">
                <div class="flex items-center gap-2">
                  <span>🏛️ Faculty / Dept</span>
                  <span :class="[sortKey === 'faculty' ? 'bg-indigo-500/20 text-indigo-300 font-extrabold border border-indigo-500/40 px-1.5 py-0.5 rounded' : 'text-slate-500 group-hover:text-slate-300', 'text-[11px] transition-all']">
                    {{ sortKey === 'faculty' ? (sortOrder === 'asc' ? '▲ ASC' : '▼ DESC') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Students -->
              <th @click="toggleSort('students_count')" class="py-4 px-4 text-center cursor-pointer hover:text-white transition-colors group">
                <div class="flex items-center justify-center gap-1.5">
                  <span>Students</span>
                  <span :class="[sortKey === 'students_count' ? 'bg-emerald-500/20 text-emerald-300 font-extrabold border border-emerald-500/40 px-1.5 py-0.5 rounded' : 'text-slate-500 group-hover:text-slate-300', 'text-[11px] transition-all']">
                    {{ sortKey === 'students_count' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Core Subjects -->
              <th @click="toggleSort('subjects_count')" class="py-4 px-4 text-center cursor-pointer hover:text-white transition-colors group">
                <div class="flex items-center justify-center gap-1.5">
                  <span>Core Subjects</span>
                  <span :class="[sortKey === 'subjects_count' ? 'bg-sky-500/20 text-sky-300 font-extrabold border border-sky-500/40 px-1.5 py-0.5 rounded' : 'text-slate-500 group-hover:text-slate-300', 'text-[11px] transition-all']">
                    {{ sortKey === 'subjects_count' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Duration -->
              <th class="py-4 px-4 text-center">Duration</th>

              <!-- Status -->
              <th @click="toggleSort('status')" class="py-4 px-4 text-center cursor-pointer hover:text-white transition-colors group">
                <div class="flex items-center justify-center gap-1.5">
                  <span>Status</span>
                  <span :class="[sortKey === 'status' ? 'bg-slate-700 text-slate-200 font-extrabold px-1.5 py-0.5 rounded' : 'text-slate-500 group-hover:text-slate-300', 'text-[11px] transition-all']">
                    {{ sortKey === 'status' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <th class="py-4 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/80 text-xs">
            <tr v-for="(mjr, idx) in paginatedMajors" :key="mjr.id" class="hover:bg-slate-800/50 transition-all">
              <td class="py-4 px-4 font-bold text-slate-300 whitespace-nowrap">
                {{ String((currentPage - 1) * itemsPerPage + idx + 1).padStart(2, '0') }}
              </td>

              <td class="py-4 px-4 whitespace-nowrap">
                <div>
                  <div class="font-black text-white text-sm tracking-tight">{{ mjr.name }}</div>
                  <div class="text-xs text-cyan-300 font-medium mt-0.5">{{ mjr.name_kh }}</div>
                  <div class="mt-1.5">
                    <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-cyan-300 font-bold text-[11px]">
                      Code: {{ mjr.code }}
                    </span>
                  </div>
                </div>
              </td>

              <td class="py-4 px-4 whitespace-nowrap">
                <div class="text-white font-bold text-xs">
                  {{ typeof mjr.faculty === 'object' ? (mjr.faculty?.name || mjr.faculty?.name_kh || 'Faculty of Computing') : (mjr.faculty || 'Faculty of Computing') }}
                </div>
                <div class="text-xs text-indigo-300 mt-0.5 font-medium">
                  Dept: {{ typeof mjr.department === 'object' ? (mjr.department?.name || mjr.department?.name_kh || 'Computing') : (mjr.department || 'Computing') }}
                </div>
              </td>

              <td class="py-4 px-4 text-center font-extrabold text-emerald-300 whitespace-nowrap text-sm">
                {{ (mjr.students_count ?? 430).toLocaleString() }}
              </td>

              <td class="py-4 px-4 text-center whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-sky-500/15 text-sky-300 border border-sky-500/30 inline-flex items-center gap-1.5 shadow-xs">
                  <span>📖</span>
                  <span>{{ mjr.subjects_count || mjr.subjects_list?.length || (mjr.id === 1 ? 5 : 3) }} Subjects</span>
                </span>
              </td>

              <td class="py-4 px-4 text-center font-bold text-slate-200 whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 text-xs">
                  {{ mjr.duration || '4 Years' }} ({{ mjr.credits || 120 }} Cr)
                </span>
              </td>

              <!-- Status Badge with Flaticon SVG icon -->
              <td class="py-4 px-4 text-center whitespace-nowrap">
                <button
                  @click="toggleMajorStatus(mjr)"
                  title="Click to toggle Status"
                  :class="[mjr.status === 'inactive' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 hover:bg-rose-500/30' : 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 hover:bg-emerald-500/30']"
                  class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold border transition-all cursor-pointer hover:scale-105 shadow-xs"
                >
                  <img 
                    :src="mjr.status === 'inactive' ? inactiveStatusIconUrl : activeStatusIconUrl" 
                    :alt="mjr.status || 'active'" 
                    class="w-4 h-4 object-contain shrink-0 filter drop-shadow-xs" 
                  />
                  <span>{{ mjr.status === 'inactive' ? 'Inactive' : 'Active' }}</span>
                  <span class="text-[10px] opacity-70">▾</span>
                </button>
              </td>

              <td class="py-4 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openEditModal(mjr)"
                    class="px-3.5 py-1.5 bg-cyan-500/15 hover:bg-cyan-500/30 text-cyan-300 border border-cyan-500/40 rounded-xl font-bold whitespace-nowrap inline-flex items-center gap-1.5 transition-all cursor-pointer shadow-xs"
                  >
                    <span>✏️</span>
                    <span>Edit</span>
                  </button>

                  <button
                    @click="deleteMajor(mjr)"
                    class="px-3.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/25 text-rose-400 border border-rose-500/30 hover:border-rose-500/60 rounded-xl font-medium whitespace-nowrap inline-flex items-center gap-1.5 transition-all cursor-pointer shadow-xs"
                  >
                    <span>🗑️</span>
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredAndSortedMajors.length === 0">
              <td colspan="8" class="py-10 text-center text-slate-300 text-xs">
                No majors found matching your search query.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- PAGINATION & ROW COUNT FOOTER -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 border-t border-slate-800 bg-slate-900/60 text-xs text-slate-300">
          <div class="flex items-center gap-2">
            <span>Show</span>
            <select v-model="itemsPerPage" class="bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1 text-xs text-white focus:outline-none focus:border-cyan-500">
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="25">25</option>
            </select>
            <span>entries per page • Showing <strong class="text-white font-bold">{{ filteredAndSortedMajors.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }}</strong> to <strong class="text-white font-bold">{{ Math.min(currentPage * itemsPerPage, filteredAndSortedMajors.length) }}</strong> of <strong class="text-white font-bold">{{ filteredAndSortedMajors.length }}</strong> Majors</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-3.5 py-1.5 rounded-xl border border-slate-700 bg-slate-800 text-slate-200 hover:text-white hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all font-semibold cursor-pointer"
            >
              ◀ Prev
            </button>

            <span class="px-3.5 py-1.5 rounded-xl bg-cyan-500/20 border border-cyan-500/30 text-cyan-300 font-extrabold">
              {{ currentPage }} / {{ totalPages }}
            </span>

            <button
              @click="currentPage = Math.min(totalPages, currentPage + 1)"
              :disabled="currentPage >= totalPages"
              class="px-3.5 py-1.5 rounded-xl border border-slate-700 bg-slate-800 text-slate-200 hover:text-white hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all font-semibold cursor-pointer"
            >
              Next ▶
            </button>
          </div>
        </div>
      </div>

      <!-- ADD / EDIT MAJOR MODAL -->
      <div v-if="isModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700/80 rounded-3xl max-w-xl w-full p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh] transition-all">
          <div class="flex items-center justify-between border-b border-slate-800 pb-3.5">
            <div class="flex items-center gap-3">
              <img :src="majorIconUrl" class="w-9 h-9 object-contain rounded-xl shadow-xs" alt="Major" />
              <div>
                <h3 class="text-base font-black text-white tracking-tight">
                  {{ editingMajor ? 'EDIT MAJOR PROGRAM' : 'ADD MAJOR PROGRAM' }}
                </h3>
                <p class="text-[11px] text-slate-400 font-medium">Specify details below to save major program information</p>
              </div>
            </div>
            <button @click="isModalOpen = false" class="w-8 h-8 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-all cursor-pointer">✕</button>
          </div>

          <div class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-300 mb-1.5">Major Code</label>
              <input v-model="form.code" type="text" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3.5 py-2.5 text-white font-bold tracking-wide" />
            </div>

            <div>
              <label class="block font-bold text-slate-300 mb-1.5">Major Name (English)</label>
              <input v-model="form.name" type="text" placeholder="e.g. IT & Networking" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 font-medium" />
            </div>

            <div>
              <label class="block font-bold text-slate-300 mb-1.5">Major Name (Khmer)</label>
              <input v-model="form.name_kh" type="text" placeholder="e.g. បច្ចេកវិទ្យាព័ត៌មាន និងបណ្តាញ" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 font-medium" />
            </div>

            <div class="grid grid-cols-2 gap-3.5">
              <div>
                <label class="block font-bold text-slate-300 mb-1.5">Parent Faculty</label>
                <select v-model="form.faculty" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3.5 py-2.5 text-white font-medium">
                  <option v-for="fac in props.faculties" :key="fac" :value="fac">{{ fac }}</option>
                </select>
              </div>

              <div>
                <label class="block font-bold text-slate-300 mb-1.5">Department</label>
                <input v-model="form.department" type="text" placeholder="Computing" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 font-medium" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3.5">
              <div>
                <label class="block font-bold text-slate-300 mb-1.5">Required Degree Credits</label>
                <input v-model.number="form.credits" type="number" placeholder="120" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3.5 py-2.5 text-white font-bold" />
              </div>

              <div>
                <label class="block font-bold text-slate-300 mb-1.5">Degree Duration</label>
                <input v-model="form.duration" type="text" placeholder="4 Years" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 font-medium" />
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 border-t border-slate-800/80 pt-4">
            <button @click="isModalOpen = false" :disabled="isSubmitting" class="px-4.5 py-2.5 bg-slate-800/80 hover:bg-slate-700 text-slate-300 border border-slate-700 font-bold text-xs rounded-xl transition-all cursor-pointer inline-flex items-center gap-2 disabled:opacity-50">
              <img :src="cancelIconUrl" class="w-4 h-4 object-contain opacity-80" alt="Cancel" />
              <span>Cancel</span>
            </button>
            <button @click="saveMajor" :disabled="isSubmitting" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-indigo-600 hover:from-cyan-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-cyan-600/25 transition-all cursor-pointer inline-flex items-center gap-2 disabled:opacity-50">
              <svg v-if="isSubmitting" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <img v-else :src="saveIconUrl" class="w-4 h-4 object-contain brightness-200" alt="Save" />
              <span>{{ isSubmitting ? (editingMajor ? 'កំពុងរក្សាទុក...' : 'កំពុងបង្កើត...') : (editingMajor ? 'Save Major' : 'Create Major') }}</span>
            </button>
          </div>
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
