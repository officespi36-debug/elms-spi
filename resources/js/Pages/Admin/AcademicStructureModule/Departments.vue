<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AcademicModuleHeader from '@/Components/Admin/AcademicModuleHeader.vue'

const props = withDefaults(defineProps<{
  departments?: any[]
  faculties?: any[]
  summaryStats?: any
}>(), {
  departments: () => [
    { id: 1, code: 'DEPT-CMP-001', name: 'Computing', name_kh: 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រកុំព្យូទ័រ', faculty: 'Faculty of Computing', head: 'Mr. Sophea', email: 'computing.dept@elms.edu', majors_count: 1, teachers_count: 25, status: 'active', linked_majors: ['Information Technology'] },
    { id: 2, code: 'DEPT-SE-002', name: 'Software Engineering', name_kh: 'ដេប៉ាតឺម៉ង់ វិស្វកម្មសូហ្វវែរ', faculty: 'Faculty of Computing', head: 'Dr. Keo Vichea', email: 'se.dept@elms.edu', majors_count: 0, teachers_count: 12, status: 'active', linked_majors: [] },
    { id: 3, code: 'DEPT-TRM-003', name: 'Tourism', name_kh: 'ដេប៉ាតឺម៉ង់ ទេសចរណ៍', faculty: 'Faculty of Tourism', head: 'Mr. Long', email: 'tourism.dept@elms.edu', majors_count: 1, teachers_count: 18, status: 'active', linked_majors: ['Tourism'] },
    { id: 4, code: 'DEPT-HSP-004', name: 'Hospitality Management', name_kh: 'ដេប៉ាតឺម៉ង់ គ្រប់គ្រងសណ្ឋាគារ', faculty: 'Faculty of Tourism', head: 'Ms. Dara', email: 'hospitality.dept@elms.edu', majors_count: 0, teachers_count: 15, status: 'active', linked_majors: [] },
    { id: 5, code: 'DEPT-EDU-005', name: 'Education', name_kh: 'ដេប៉ាតឺម៉ង់ អប់រំ', faculty: 'Faculty of Education', head: 'Ms. Srey', email: 'education.dept@elms.edu', majors_count: 1, teachers_count: 20, status: 'active', linked_majors: ['English Literature'] },
    { id: 6, code: 'DEPT-HUM-006', name: 'Humanities', name_kh: 'ដេប៉ាតឺម៉ង់ មនុស្សសាស្ត្រ', faculty: 'Faculty of Education', head: 'Mr. Chan', email: 'humanities.dept@elms.edu', majors_count: 0, teachers_count: 8, status: 'active', linked_majors: [] },
    { id: 7, code: 'DEPT-LNG-007', name: 'Languages', name_kh: 'ដេប៉ាតឺម៉ង់ ភាសាបរទេស', faculty: 'Faculty of Education', head: 'Ms. Sophea', email: 'languages.dept@elms.edu', majors_count: 0, teachers_count: 10, status: 'active', linked_majors: [] },
    { id: 8, code: 'DEPT-AGR-008', name: 'Agriculture', name_kh: 'ដេប៉ាតឺម៉ង់ កសិកម្ម', faculty: 'Faculty of Agriculture', head: 'Mr. Vuthy', email: 'agri.dept@elms.edu', majors_count: 1, teachers_count: 22, status: 'active', linked_majors: ['Agriculture'] },
    { id: 9, code: 'DEPT-PLN-009', name: 'Plant Science', name_kh: 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្ររុក្ខជាតិ', faculty: 'Faculty of Agriculture', head: 'Dr. Heng', email: 'plant.dept@elms.edu', majors_count: 0, teachers_count: 8, status: 'active', linked_majors: [] },
    { id: 10, code: 'DEPT-SOC-010', name: 'Social Science', name_kh: 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រសង្គម', faculty: 'Faculty of Social Science', head: 'Mr. Rithy', email: 'social.dept@elms.edu', majors_count: 1, teachers_count: 15, status: 'active', linked_majors: ['Social Work'] },
    { id: 11, code: 'DEPT-DEV-011', name: 'Social Development', name_kh: 'ដេប៉ាតឺម៉ង់ អភិវឌ្ឍន៍សង្គម', faculty: 'Faculty of Social Science', head: 'Ms. Bopha', email: 'dev.dept@elms.edu', majors_count: 0, teachers_count: 7, status: 'active', linked_majors: [] },
    { id: 12, code: 'DEPT-COM-012', name: 'Community Studies', name_kh: 'ដេប៉ាតឺម៉ង់ សិក្សាសហគមន៍', faculty: 'Faculty of Social Science', head: 'Mr. Sarath', email: 'community.dept@elms.edu', majors_count: 0, teachers_count: 5, status: 'active', linked_majors: [] },
  ],
  faculties: () => ['Faculty of Computing', 'Faculty of Tourism', 'Faculty of Education', 'Faculty of Agriculture', 'Faculty of Social Science'],
  summaryStats: () => ({})
})

const defaultDepartments = [
  { id: 1, code: 'DEPT-CMP-001', name: 'Computing', name_kh: 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រកុំព្យូទ័រ', faculty: 'Faculty of Computing', head: 'Mr. Sophea', email: 'computing.dept@elms.edu', majors_count: 1, teachers_count: 25, status: 'active', linked_majors: ['Information Technology'] },
  { id: 2, code: 'DEPT-SE-002', name: 'Software Engineering', name_kh: 'ដេប៉ាតឺម៉ង់ វិស្វកម្មសូហ្វវែរ', faculty: 'Faculty of Computing', head: 'Dr. Keo Vichea', email: 'se.dept@elms.edu', majors_count: 0, teachers_count: 12, status: 'active', linked_majors: [] },
  { id: 3, code: 'DEPT-TRM-003', name: 'Tourism', name_kh: 'ដេប៉ាតឺម៉ង់ ទេសចរណ៍', faculty: 'Faculty of Tourism', head: 'Mr. Long', email: 'tourism.dept@elms.edu', majors_count: 1, teachers_count: 18, status: 'active', linked_majors: ['Tourism'] },
  { id: 4, code: 'DEPT-HSP-004', name: 'Hospitality Management', name_kh: 'ដេប៉ាតឺម៉ង់ គ្រប់គ្រងសណ្ឋាគារ', faculty: 'Faculty of Tourism', head: 'Ms. Dara', email: 'hospitality.dept@elms.edu', majors_count: 0, teachers_count: 15, status: 'active', linked_majors: [] },
  { id: 5, code: 'DEPT-EDU-005', name: 'Education', name_kh: 'ដេប៉ាតឺម៉ង់ អប់រំ', faculty: 'Faculty of Education', head: 'Ms. Srey', email: 'education.dept@elms.edu', majors_count: 1, teachers_count: 20, status: 'active', linked_majors: ['English Literature'] },
  { id: 6, code: 'DEPT-HUM-006', name: 'Humanities', name_kh: 'ដេប៉ាតឺម៉ង់ មនុស្សសាស្ត្រ', faculty: 'Faculty of Education', head: 'Mr. Chan', email: 'humanities.dept@elms.edu', majors_count: 0, teachers_count: 8, status: 'active', linked_majors: [] },
  { id: 7, code: 'DEPT-LNG-007', name: 'Languages', name_kh: 'ដេប៉ាតឺម៉ង់ ភាសាបរទេស', faculty: 'Faculty of Education', head: 'Ms. Sophea', email: 'languages.dept@elms.edu', majors_count: 0, teachers_count: 10, status: 'active', linked_majors: [] },
  { id: 8, code: 'DEPT-AGR-008', name: 'Agriculture', name_kh: 'ដេប៉ាតឺម៉ង់ កសិកម្ម', faculty: 'Faculty of Agriculture', head: 'Mr. Vuthy', email: 'agri.dept@elms.edu', majors_count: 1, teachers_count: 22, status: 'active', linked_majors: ['Agriculture'] },
  { id: 9, code: 'DEPT-PLN-009', name: 'Plant Science', name_kh: 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្ររុក្ខជាតិ', faculty: 'Faculty of Agriculture', head: 'Dr. Heng', email: 'plant.dept@elms.edu', majors_count: 0, teachers_count: 8, status: 'active', linked_majors: [] },
  { id: 10, code: 'DEPT-SOC-010', name: 'Social Science', name_kh: 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រសង្គម', faculty: 'Faculty of Social Science', head: 'Mr. Rithy', email: 'social.dept@elms.edu', majors_count: 1, teachers_count: 15, status: 'active', linked_majors: ['Social Work'] },
  { id: 11, code: 'DEPT-DEV-011', name: 'Social Development', name_kh: 'ដេប៉ាតឺម៉ង់ អភិវឌ្ឍន៍សង្គម', faculty: 'Faculty of Social Science', head: 'Ms. Bopha', email: 'dev.dept@elms.edu', majors_count: 0, teachers_count: 7, status: 'active', linked_majors: [] },
  { id: 12, code: 'DEPT-COM-012', name: 'Community Studies', name_kh: 'ដេប៉ាតឺម៉ង់ សិក្សាសហគមន៍', faculty: 'Faculty of Social Science', head: 'Mr. Sarath', email: 'community.dept@elms.edu', majors_count: 0, teachers_count: 5, status: 'active', linked_majors: [] },
]

const searchQuery = ref('')
const selectedFacultyFilter = ref('all')

// Sorting State
const sortKey = ref('name')
const sortOrder = ref<'asc' | 'desc'>('asc')

// Pagination State
const currentPage = ref(1)
const itemsPerPage = ref(5)

const activeStatusIconUrl = '/images/nav/active-status.svg'
const inactiveStatusIconUrl = '/images/nav/inactive-status.svg'
const saveIconUrl = '/images/nav/save-icon.svg'
const cancelIconUrl = '/images/nav/cancel-icon.svg'
const deptIconUrl = '/images/nav/department-icon.svg'

const targetRoute = (name: string, param?: any) => {
  try {
    return param !== undefined ? (window as any).route(`admin.${name}`, param) : (window as any).route(`admin.${name}`)
  } catch (e) {
    return param !== undefined ? (window as any).route(name, param) : (window as any).route(name)
  }
}

const getDeptStatus = (dept: any) => {
  if (dept.status) return dept.status
  return dept.is_active === false || dept.is_active === 0 ? 'inactive' : 'active'
}

const toggleDeptStatus = (dept: any) => {
  const currentStatus = getDeptStatus(dept)
  const newStatus = currentStatus === 'inactive' ? 'active' : 'inactive'
  const newIsActive = newStatus === 'active'

  if (dept.id) {
    router.put(
      targetRoute('academic-structure.departments.update', dept.id),
      { ...dept, status: newStatus, is_active: newIsActive },
      {
        preserveScroll: true,
        onSuccess: () => {
          dept.status = newStatus
          dept.is_active = newIsActive
          triggerToast(
            'បច្ចុប្បន្នភាពស្ថានភាពជោគជ័យ',
            `ស្ថានភាពដេប៉ាតឺម៉ង់ "${dept.name}" ត្រូវបានផ្លាស់ប្តូរទៅជា ${newStatus.toUpperCase()}`
          )
        },
        onError: () => {
          triggerToast('មានបញ្ហាក្នុងការផ្លាស់ប្តូរស្ថានភាព', 'សូមព្យាយាមម្តងទៀត', 'warning')
        }
      }
    )
  } else {
    dept.status = newStatus
    dept.is_active = newIsActive
  }
}

const toggleSort = (key: string) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'asc'
  }
}

const isModalOpen = ref(false)
const editingDept = ref<any>(null)
const form = ref({
  code: '',
  name: '',
  name_kh: '',
  faculty: 'Faculty of Computing',
  head: '',
  email: '',
  status: 'active',
  is_active: true,
  description: ''
})

const filteredAndSortedDepartments = computed(() => {
  const list = Array.isArray(props.departments) && props.departments.length > 0 ? props.departments : defaultDepartments

  let result = list.filter(dept => {
    const name = dept.name || ''
    const nameKh = dept.name_kh || ''
    const code = dept.code || ''
    const head = dept.head || ''
    const facName = typeof dept.faculty === 'object' ? (dept.faculty?.name || '') : (dept.faculty || '')
    const q = searchQuery.value.toLowerCase()

    const matchesSearch = name.toLowerCase().includes(q) ||
                          nameKh.includes(searchQuery.value) ||
                          code.toLowerCase().includes(q) ||
                          head.toLowerCase().includes(q)
    const matchesFaculty = selectedFacultyFilter.value === 'all' || facName === selectedFacultyFilter.value
    return matchesSearch && matchesFaculty
  })

  // Sorting
  result.sort((a, b) => {
    let valA = a[sortKey.value]
    let valB = b[sortKey.value]

    if (sortKey.value === 'status') {
      valA = getDeptStatus(a)
      valB = getDeptStatus(b)
    } else if (sortKey.value === 'faculty') {
      valA = typeof a.faculty === 'object' ? (a.faculty?.name || '') : (a.faculty || '')
      valB = typeof b.faculty === 'object' ? (b.faculty?.name || '') : (b.faculty || '')
    } else if (sortKey.value === 'majors_count') {
      valA = a.majors_count ?? 0
      valB = b.majors_count ?? 0
    } else if (sortKey.value === 'teachers_count') {
      valA = a.teachers_count ?? 0
      valB = b.teachers_count ?? 0
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
  return Math.ceil(filteredAndSortedDepartments.value.length / itemsPerPage.value) || 1
})

const paginatedDepartments = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredAndSortedDepartments.value.slice(start, start + itemsPerPage.value)
})

const openAddModal = () => {
  editingDept.value = null
  const currentList = Array.isArray(props.departments) ? props.departments : defaultDepartments
  form.value = {
    code: `DEPT-NEW-0${currentList.length + 1}`,
    name: '',
    name_kh: '',
    faculty: props.faculties[0] || 'Faculty of Computing',
    head: '',
    email: '',
    status: 'active',
    is_active: true,
    description: ''
  }
  isModalOpen.value = true
}

const openEditModal = (dept: any) => {
  editingDept.value = dept
  form.value = {
    ...dept,
    status: getDeptStatus(dept),
    is_active: getDeptStatus(dept) === 'active'
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

const saveDepartment = () => {
  if (!form.value.name) {
    triggerToast('សូមបញ្ចូលឈ្មោះដេប៉ាតឺម៉ង់', 'សូមបញ្ចូល Department Name (English) មុនពេលរក្សាទុក', 'warning')
    return
  }

  isSubmitting.value = true
  const deptName = form.value.name || 'ដេប៉ាតឺម៉ង់'

  if (editingDept.value?.id) {
    router.put(targetRoute('academic-structure.departments.update', editingDept.value.id), form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false
        isModalOpen.value = false
        triggerToast(
          'រក្សាទុកបានជោគជ័យ',
          `ព័ត៌មានដេប៉ាតឺម៉ង់ "${deptName}" ត្រូវបានបច្ចុប្បន្នភាពដោយជោគជ័យ`
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
    router.post(targetRoute('academic-structure.departments.store'), form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false
        isModalOpen.value = false
        triggerToast(
          'បង្កើតដេប៉ាតឺម៉ង់បានជោគជ័យ',
          `ដេប៉ាតឺម៉ង់ "${deptName}" ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        isSubmitting.value = false
        triggerToast(
          'មានបញ្ហាក្នុងការបង្កើតដេប៉ាតឺម៉ង់',
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

const deleteDepartment = (dept: any) => {
  if (confirm(`Are you sure you want to delete '${dept.name}'?`)) {
    router.delete(targetRoute('academic-structure.departments.destroy', dept.id), {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast(
          'លុបដេប៉ាតឺម៉ង់បានជោគជ័យ',
          `ដេប៉ាតឺម៉ង់ "${dept.name}" ត្រូវបានលុបចេញពីប្រព័ន្ធដោយជោគជ័យ`
        )
      },
      onError: () => {
        triggerToast(
          'មានបញ្ហាក្នុងការលុប',
          'មិនអាចលុបដេប៉ាតឺម៉ង់នេះបានទេ',
          'warning'
        )
      }
    })
  }
}
</script>

<template>
  <AdminLayout title="Departments — Academic Structure">
    <div class="space-y-5 font-sans mb-8">
      <!-- Shared Header -->
      <AcademicModuleHeader activeTab="departments" :summaryStats="props.summaryStats" />

      <!-- OVERVIEW SUMMARY CARDS (Soft Glassmorphic Glowing Border Frames) -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Card 1: Total Departments -->
        <div class="relative group p-4.5 bg-gradient-to-b from-slate-900/90 via-slate-900/70 to-indigo-950/20 border border-indigo-500/20 hover:border-indigo-500/35 rounded-2xl backdrop-blur-xl transition-all duration-300 shadow-lg hover:shadow-indigo-500/10 space-y-1 overflow-hidden">
          <div class="absolute -right-6 -top-6 w-20 h-20 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-500/20 transition-all pointer-events-none"></div>
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
              <span>🏢</span>
              <span>Total Departments</span>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">Sub-units</span>
          </div>
          <div class="text-3xl font-black text-white tracking-tight pt-1">
            {{ filteredAndSortedDepartments.length }}
          </div>
          <div class="text-xs text-indigo-200/90 font-medium flex items-center gap-1.5 pt-0.5">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
            <span>Active Academic Sub-units</span>
          </div>
        </div>

        <!-- Card 2: Active Status -->
        <div class="relative group p-4.5 bg-gradient-to-b from-slate-900/90 via-slate-900/70 to-emerald-950/20 border border-emerald-500/20 hover:border-emerald-500/35 rounded-2xl backdrop-blur-xl transition-all duration-300 shadow-lg hover:shadow-emerald-500/10 space-y-1 overflow-hidden">
          <div class="absolute -right-6 -top-6 w-20 h-20 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition-all pointer-events-none"></div>
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
              <span>🟢</span>
              <span>Active Status</span>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">Operational</span>
          </div>
          <div class="text-3xl font-black text-emerald-300 tracking-tight pt-1">
            {{ filteredAndSortedDepartments.length }} / {{ defaultDepartments.length }}
          </div>
          <div class="text-xs text-emerald-200/90 font-medium flex items-center gap-1.5 pt-0.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            <span>Fully Operational</span>
          </div>
        </div>

        <!-- Card 3: Linked Majors -->
        <div class="relative group p-4.5 bg-gradient-to-b from-slate-900/90 via-slate-900/70 to-cyan-950/20 border border-cyan-500/20 hover:border-cyan-500/35 rounded-2xl backdrop-blur-xl transition-all duration-300 shadow-lg hover:shadow-cyan-500/10 space-y-1 overflow-hidden">
          <div class="absolute -right-6 -top-6 w-20 h-20 bg-cyan-500/10 rounded-full blur-xl group-hover:bg-cyan-500/20 transition-all pointer-events-none"></div>
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
              <span>🎓</span>
              <span>Linked Majors</span>
            </div>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">Programs</span>
          </div>
          <div class="text-3xl font-black text-cyan-300 tracking-tight pt-1">
            5 Degree Programs
          </div>
          <div class="text-xs text-cyan-200/90 font-medium flex items-center gap-1.5 pt-0.5">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
            <span>Degree Pathways</span>
          </div>
        </div>
      </div>

      <!-- SEARCH, FILTER & ACTION TOOLBAR -->
      <div class="flex flex-col md:flex-row items-center justify-between gap-3 p-4 bg-slate-900/80 border border-slate-700/80 rounded-2xl backdrop-blur-xl shadow-lg">
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto flex-1">
          <div class="relative w-full sm:w-80">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-indigo-400 text-sm">
              🔍
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search department name, code, head..."
              class="w-full bg-slate-950 border border-slate-700/80 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 rounded-xl pl-9 pr-9 py-2.5 text-xs text-white placeholder-slate-400 shadow-inner transition-all font-medium"
            />
            <button 
              v-if="searchQuery"
              @click="searchQuery = ''"
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition-colors"
            >
              ✕
            </button>
          </div>

          <select v-model="selectedFacultyFilter" class="w-full sm:w-auto bg-slate-950 border border-slate-700/80 focus:border-indigo-400 rounded-xl px-3.5 py-2.5 text-xs text-slate-200 focus:outline-none transition-colors font-medium">
            <option value="all">Parent Faculty: All Faculties</option>
            <option v-for="fac in props.faculties" :key="fac" :value="fac">{{ fac }}</option>
          </select>
        </div>

        <button 
          @click="openAddModal" 
          class="w-full md:w-auto px-4.5 py-2.5 bg-indigo-500/15 hover:bg-indigo-500/30 text-indigo-200 border border-indigo-500/40 hover:border-indigo-500/70 font-bold text-xs rounded-xl backdrop-blur-md transition-all flex items-center justify-center gap-2 whitespace-nowrap cursor-pointer shadow-xs"
        >
          <span>➕</span>
          <span>Add Department</span>
        </button>
      </div>

      <!-- DEPARTMENTS TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/80 backdrop-blur-xl shadow-xl">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-800/60 text-xs font-extrabold text-slate-200 uppercase whitespace-nowrap select-none">
              <th class="py-4 px-4 w-12 text-slate-400">#</th>
              
              <!-- Department Name -->
              <th @click="toggleSort('name')" class="py-4 px-4 cursor-pointer hover:text-white transition-colors group">
                <div class="flex items-center gap-2">
                  <span>🏢 Department Name</span>
                  <span :class="[sortKey === 'name' ? 'bg-indigo-500/20 text-indigo-300 font-extrabold border border-indigo-500/40 px-1.5 py-0.5 rounded' : 'text-slate-500 group-hover:text-slate-300', 'text-[11px] transition-all']">
                    {{ sortKey === 'name' ? (sortOrder === 'asc' ? '▲ ASC' : '▼ DESC') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Parent Faculty -->
              <th @click="toggleSort('faculty')" class="py-4 px-4 cursor-pointer hover:text-white transition-colors group">
                <div class="flex items-center gap-2">
                  <span>🏛️ Parent Faculty</span>
                  <span :class="[sortKey === 'faculty' ? 'bg-indigo-500/20 text-indigo-300 font-extrabold border border-indigo-500/40 px-1.5 py-0.5 rounded' : 'text-slate-500 group-hover:text-slate-300', 'text-[11px] transition-all']">
                    {{ sortKey === 'faculty' ? (sortOrder === 'asc' ? '▲ ASC' : '▼ DESC') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Head of Dept -->
              <th @click="toggleSort('head')" class="py-4 px-4 cursor-pointer hover:text-white transition-colors group">
                <div class="flex items-center gap-2">
                  <span>👤 Head of Dept</span>
                  <span :class="[sortKey === 'head' ? 'bg-indigo-500/20 text-indigo-300 font-extrabold border border-indigo-500/40 px-1.5 py-0.5 rounded' : 'text-slate-500 group-hover:text-slate-300', 'text-[11px] transition-all']">
                    {{ sortKey === 'head' ? (sortOrder === 'asc' ? '▲ ASC' : '▼ DESC') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Majors -->
              <th @click="toggleSort('majors_count')" class="py-4 px-4 text-center cursor-pointer hover:text-white transition-colors group">
                <div class="flex items-center justify-center gap-1.5">
                  <span>Majors</span>
                  <span :class="[sortKey === 'majors_count' ? 'bg-cyan-500/20 text-cyan-300 font-extrabold border border-cyan-500/40 px-1.5 py-0.5 rounded' : 'text-slate-500 group-hover:text-slate-300', 'text-[11px] transition-all']">
                    {{ sortKey === 'majors_count' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

              <!-- Teachers -->
              <th @click="toggleSort('teachers_count')" class="py-4 px-4 text-center cursor-pointer hover:text-white transition-colors group">
                <div class="flex items-center justify-center gap-1.5">
                  <span>Teachers</span>
                  <span :class="[sortKey === 'teachers_count' ? 'bg-emerald-500/20 text-emerald-300 font-extrabold border border-emerald-500/40 px-1.5 py-0.5 rounded' : 'text-slate-500 group-hover:text-slate-300', 'text-[11px] transition-all']">
                    {{ sortKey === 'teachers_count' ? (sortOrder === 'asc' ? '▲' : '▼') : '↕' }}
                  </span>
                </div>
              </th>

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
            <tr v-for="(dept, idx) in paginatedDepartments" :key="dept.id" class="hover:bg-slate-800/50 transition-all">
              <td class="py-4 px-4 font-bold text-slate-300 whitespace-nowrap">
                {{ String((currentPage - 1) * itemsPerPage + idx + 1).padStart(2, '0') }}
              </td>

              <td class="py-4 px-4 whitespace-nowrap">
                <div>
                  <div class="font-black text-white text-sm tracking-tight">{{ dept.name }}</div>
                  <div class="text-xs text-indigo-300 font-medium mt-0.5">{{ dept.name_kh }}</div>
                  <div class="mt-1.5">
                    <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-indigo-300 font-bold text-[11px]">
                      Code: {{ dept.code }}
                    </span>
                  </div>
                </div>
              </td>

              <td class="py-4 px-4 whitespace-nowrap text-slate-200 font-bold">
                {{ typeof dept.faculty === 'object' ? (dept.faculty?.name || 'Faculty of Computing') : (dept.faculty || 'Faculty of Computing') }}
              </td>

              <td class="py-4 px-4 whitespace-nowrap">
                <div class="text-white font-bold text-xs">{{ dept.head || (dept.id == 1 ? 'Mr. Sophea' : dept.id == 2 ? 'Dr. Keo Vichea' : dept.id == 3 ? 'Mr. Long' : dept.id == 4 ? 'Ms. Dara' : 'Ms. Srey') }}</div>
                <div class="text-xs text-slate-300 mt-0.5 font-medium">{{ dept.email || ('dept' + (dept.id || 1) + '@elms.edu') }}</div>
              </td>

              <td class="py-4 px-4 text-center font-extrabold text-cyan-300 whitespace-nowrap text-sm">
                {{ dept.majors_count ?? 1 }}
              </td>

              <td class="py-4 px-4 text-center font-extrabold text-emerald-300 whitespace-nowrap text-sm">
                {{ dept.teachers_count ?? (10 + ((dept.id || 1) * 2)) }}
              </td>

              <!-- Status Badge with Flaticon SVG icon -->
              <td class="py-4 px-4 text-center whitespace-nowrap">
                <button
                  @click="toggleDeptStatus(dept)"
                  title="Click to toggle Status"
                  :class="[dept.status === 'inactive' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 hover:bg-rose-500/30' : 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 hover:bg-emerald-500/30']"
                  class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold border transition-all cursor-pointer hover:scale-105 shadow-xs"
                >
                  <img 
                    :src="dept.status === 'inactive' ? inactiveStatusIconUrl : activeStatusIconUrl" 
                    :alt="dept.status || 'active'" 
                    class="w-4 h-4 object-contain shrink-0 filter drop-shadow-xs" 
                  />
                  <span>{{ dept.status === 'inactive' ? 'Inactive' : 'Active' }}</span>
                  <span class="text-[10px] opacity-70">▾</span>
                </button>
              </td>

              <td class="py-4 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openEditModal(dept)"
                    class="px-3.5 py-1.5 bg-indigo-500/15 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-500/40 rounded-xl font-bold whitespace-nowrap inline-flex items-center gap-1.5 transition-all cursor-pointer shadow-xs"
                  >
                    <span>✏️</span>
                    <span>Edit</span>
                  </button>

                  <button
                    @click="deleteDepartment(dept)"
                    class="px-3.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/25 text-rose-400 border border-rose-500/30 hover:border-rose-500/60 rounded-xl font-medium whitespace-nowrap inline-flex items-center gap-1.5 transition-all cursor-pointer shadow-xs"
                  >
                    <span>🗑️</span>
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="filteredAndSortedDepartments.length === 0">
              <td colspan="8" class="py-10 text-center text-slate-300 text-xs">
                No departments found matching your search query.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- PAGINATION & ROW COUNT FOOTER -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-4 border-t border-slate-800 bg-slate-900/60 text-xs text-slate-300">
          <div class="flex items-center gap-2">
            <span>Show</span>
            <select v-model="itemsPerPage" class="bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1 text-xs text-white focus:outline-none focus:border-indigo-500">
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="25">25</option>
            </select>
            <span>entries per page • Showing <strong class="text-white font-bold">{{ filteredAndSortedDepartments.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }}</strong> to <strong class="text-white font-bold">{{ Math.min(currentPage * itemsPerPage, filteredAndSortedDepartments.length) }}</strong> of <strong class="text-white font-bold">{{ filteredAndSortedDepartments.length }}</strong> Departments</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-3.5 py-1.5 rounded-xl border border-slate-700 bg-slate-800 text-slate-200 hover:text-white hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all font-semibold cursor-pointer"
            >
              ◀ Prev
            </button>

            <span class="px-3.5 py-1.5 rounded-xl bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 font-extrabold">
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

      <!-- ADD / EDIT DEPARTMENT MODAL -->
      <div v-if="isModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700/80 rounded-3xl max-w-xl w-full p-6 space-y-5 shadow-2xl overflow-y-auto max-h-[90vh] transition-all">
          <div class="flex items-center justify-between border-b border-slate-800 pb-3.5">
            <div class="flex items-center gap-3">
              <img :src="deptIconUrl" class="w-9 h-9 object-contain rounded-xl shadow-xs" alt="Department" />
              <div>
                <h3 class="text-base font-black text-white tracking-tight">
                  {{ editingDept ? 'EDIT DEPARTMENT RECORD' : 'ADD NEW DEPARTMENT' }}
                </h3>
                <p class="text-[11px] text-slate-400 font-medium">Specify details below to save department information</p>
              </div>
            </div>
            <button @click="isModalOpen = false" class="w-8 h-8 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-all cursor-pointer">✕</button>
          </div>

          <div class="space-y-4 text-xs">
            <div>
              <label class="block font-bold text-slate-300 mb-1.5">Department Code</label>
              <input v-model="form.code" type="text" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-3.5 py-2.5 text-white font-bold tracking-wide" />
            </div>

            <div>
              <label class="block font-bold text-slate-300 mb-1.5">Department Name (English)</label>
              <input v-model="form.name" type="text" placeholder="e.g. Computing" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 font-medium" />
            </div>

            <div>
              <label class="block font-bold text-slate-300 mb-1.5">Department Name (Khmer)</label>
              <input v-model="form.name_kh" type="text" placeholder="e.g. ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រកុំព្យូទ័រ" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 font-medium" />
            </div>

            <div>
              <label class="block font-bold text-slate-300 mb-1.5">Parent Faculty</label>
              <select v-model="form.faculty" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-3.5 py-2.5 text-white font-medium">
                <option v-for="fac in props.faculties" :key="fac" :value="fac">{{ fac }}</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-3.5">
              <div>
                <label class="block font-bold text-slate-300 mb-1.5">Head of Department</label>
                <input v-model="form.head" type="text" placeholder="Mr. Sophea" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 font-medium" />
              </div>
              <div>
                <label class="block font-bold text-slate-300 mb-1.5">Email Address</label>
                <input v-model="form.email" type="email" placeholder="computing.dept@elms.edu" class="w-full bg-slate-950/70 border border-slate-700/80 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/20 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 font-medium" />
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 border-t border-slate-800/80 pt-4">
            <button @click="isModalOpen = false" :disabled="isSubmitting" class="px-4.5 py-2.5 bg-slate-800/80 hover:bg-slate-700 text-slate-300 border border-slate-700 font-bold text-xs rounded-xl transition-all cursor-pointer inline-flex items-center gap-2 disabled:opacity-50">
              <img :src="cancelIconUrl" class="w-4 h-4 object-contain opacity-80" alt="Cancel" />
              <span>Cancel</span>
            </button>
            <button @click="saveDepartment" :disabled="isSubmitting" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-sky-600 hover:from-indigo-500 hover:to-sky-500 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-indigo-600/25 transition-all cursor-pointer inline-flex items-center gap-2 disabled:opacity-50">
              <svg v-if="isSubmitting" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <img v-else :src="saveIconUrl" class="w-4 h-4 object-contain brightness-200" alt="Save" />
              <span>{{ isSubmitting ? (editingDept ? 'កំពុងរក្សាទុក...' : 'កំពុងបង្កើត...') : (editingDept ? 'Save Department' : 'Create Department') }}</span>
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
