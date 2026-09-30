<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AcademicModuleHeader from '@/Components/Admin/AcademicModuleHeader.vue'

const props = withDefaults(defineProps<{
  subjects?: any[]
  summaryStats?: any
}>(), {
  subjects: () => [],
  summaryStats: () => ({})
})

const searchQuery = ref('')
const selectedFaculty = ref('all')
const isModalOpen = ref(false)
const editingSubject = ref<any>(null)

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

const form = ref({
  code: 'SUB-IT-103',
  name: '',
  name_kh: '',
  faculty: 'Faculty of Computing',
  department: 'Computing',
  major: 'Information Technology',
  credits: 3,
  prerequisite: 'None',
  difficulty: 'Beginner',
  description: '',
  status: 'active'
})

const subjectsList = computed(() => {
  return Array.isArray(props.subjects) && props.subjects.length > 0 ? props.subjects : [
    { id: 1, code: 'SUB-IT-101', name: 'C Programming', name_kh: 'ភាសា C Programming', faculty: 'Faculty of Computing', department: 'Computing', major: 'Information Technology', credits: 3, prerequisite: 'Computer Basics', difficulty: 'Beginner', status: 'active' },
    { id: 2, code: 'SUB-IT-102', name: 'Networking I', name_kh: 'បណ្តាញកុំព្យូទ័រ I', faculty: 'Faculty of Computing', department: 'Computing', major: 'Information Technology', credits: 3, prerequisite: 'C Programming', difficulty: 'Intermediate', status: 'active' },
    { id: 3, code: 'SUB-TM-101', name: 'Tourism Basics', name_kh: 'មូលដ្ឋានគ្រឹះទេសចរណ៍', faculty: 'Faculty of Tourism', department: 'Tourism', major: 'Tourism', credits: 3, prerequisite: 'None', difficulty: 'Beginner', status: 'active' },
    { id: 4, code: 'SUB-EL-101', name: 'English Grammar', name_kh: 'វេយ្យាករណ៍អង់គ្លេស', faculty: 'Faculty of Education', department: 'Education', major: 'English Literature', credits: 2, prerequisite: 'None', difficulty: 'Beginner', status: 'active' },
    { id: 5, code: 'SUB-AG-101', name: 'Plant Science', name_kh: 'វិទ្យាសាស្ត្ររុក្ខជាតិ', faculty: 'Faculty of Agriculture', department: 'Agriculture', major: 'Agriculture', credits: 3, prerequisite: 'Biology', difficulty: 'Intermediate', status: 'active' },
    { id: 6, code: 'SUB-SW-101', name: 'Social Work 101', name_kh: 'ការងារសង្គម ១០១', faculty: 'Faculty of Social Science', department: 'Social Science', major: 'Social Work', credits: 3, prerequisite: 'None', difficulty: 'Beginner', status: 'active' },
  ]
})

const filteredSubjects = computed(() => {
  return subjectsList.value.filter(sub => {
    const matchesSearch = sub.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          sub.code.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesFaculty = selectedFaculty.value === 'all' || sub.faculty === selectedFaculty.value
    return matchesSearch && matchesFaculty
  })
})

watch([searchQuery, selectedFaculty, pageSize], () => {
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

const bulkDelete = () => {
  if (confirm(`Are you sure you want to delete ${selectedIds.value.length} selected subjects?`)) {
    triggerToast('លុបមុខវិជ្ជាជោគជ័យ', `បានលុបមុខវិជ្ជាចំនួន ${selectedIds.value.length} ដោយជោគជ័យ`)
    selectedIds.value = []
  }
}

const openAddModal = () => {
  editingSubject.value = null
  form.value = {
    code: `SUB-NEW-${Math.floor(100 + Math.random() * 900)}`,
    name: '',
    name_kh: '',
    faculty: 'Faculty of Computing',
    department: 'Computing',
    major: 'IT & Networking',
    credits: 3,
    prerequisite: 'None',
    difficulty: 'Beginner',
    description: '',
    status: 'active'
  }
  isModalOpen.value = true
}

const openEditModal = (sub: any) => {
  closeDropdown()
  editingSubject.value = sub
  form.value = { ...sub }
  isModalOpen.value = true
}

const saveSubject = () => {
  if (!form.value.name) {
    triggerToast('សូមបញ្ចូលឈ្មោះមុខវិជ្ជា', 'សូមបញ្ចូល Subject Name មុនពេលរក្សាទុក', 'warning')
    return
  }

  isSubmitting.value = true
  const subName = form.value.name || 'មុខវិជ្ជា'

  if (editingSubject.value?.id) {
    router.put(`/admin/course-module/subjects/update/${editingSubject.value.id}`, form.value, {
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
    router.post('/admin/course-module/subjects/store', form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false
        isModalOpen.value = false
        triggerToast('បង្កើតមុខវិជ្ជាបានជោគជ័យ 🎉', `មុខវិជ្ជា "${subName}" ត្រូវបានបង្កើតក្នុងប្រព័ន្ធដោយជោគជ័យ`)
      },
      onError: () => {
        isSubmitting.value = false
        triggerToast('មានបញ្ហាក្នុងការបង្កើតមុខវិជ្ជា', 'សូមពិនិត្យមើលព័ត៌មានដែលបានបញ្ចូលឡើងវិញ', 'warning')
      },
      onFinish: () => { isSubmitting.value = false }
    })
  }
}

const deleteSubject = (sub: any) => {
  closeDropdown()
  if (confirm(`Are you sure you want to delete subject '${sub.name}'?`)) {
    router.delete(`/admin/course-module/subjects/destroy/${sub.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        triggerToast('លុបមុខវិជ្ជាជោគជ័យ', `មុខវិជ្ជា "${sub.name}" ត្រូវបានលុបចេញពីប្រព័ន្ធដោយជោគជ័យ`)
      },
      onError: () => {
        triggerToast('មានបញ្ហាក្នុងការលុប', 'មិនអាចលុបមុខវិជ្ជានេះបានទេ', 'warning')
      }
    })
  }
}

const viewDetails = (sub: any) => {
  closeDropdown()
  triggerToast('ព័ត៌មានមុខវិជ្ជា', `កូដ: ${sub.code} | ឈ្មោះ: ${sub.name} (${sub.credits} Credits)`, 'info')
}
</script>

<template>
  <AdminLayout title="Subjects — Academic Structure">
    <div class="space-y-4 font-sans" @click="closeDropdown">
      <!-- Shared Header (Academic Structure Tabs) -->
      <AcademicModuleHeader activeTab="subjects" :summaryStats="props.summaryStats" />

      <!-- CONTROLS & SEARCH BAR -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-3.5 bg-slate-900/60 border border-slate-800 rounded-2xl backdrop-blur-xl">
        <div class="flex flex-col sm:flex-row items-center gap-2.5 w-full sm:w-auto">
          <div class="w-full sm:w-72 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search subject name, code..."
              class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3.5 py-2 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-indigo-500"
            />
          </div>

          <div>
            <select v-model="selectedFaculty" class="w-full sm:w-auto bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-slate-200">
              <option value="all">Faculty: All Faculties</option>
              <option value="Faculty of Computing">Faculty of Computing</option>
              <option value="Faculty of Tourism">Faculty of Tourism</option>
              <option value="Faculty of Education">Faculty of Education</option>
              <option value="Faculty of Agriculture">Faculty of Agriculture</option>
              <option value="Faculty of Social Science">Faculty of Social Science</option>
            </select>
          </div>
        </div>

        <button @click="openAddModal" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-1.5 whitespace-nowrap">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>Add New Subject</span>
        </button>
      </div>

      <!-- BULK ACTIONS TOOLBAR -->
      <div v-if="selectedIds.length > 0" class="flex items-center justify-between p-3 bg-indigo-950/80 border border-indigo-500/40 rounded-2xl text-xs backdrop-blur-xl animate-fade-in">
        <div class="flex items-center gap-2 text-indigo-200 font-bold">
          <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ selectedIds.length }} subject(s) selected</span>
        </div>
        <div class="flex items-center gap-2">
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

      <!-- SUBJECTS TABLE -->
      <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-xl">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-800/40 text-[11px] font-bold text-slate-300 uppercase whitespace-nowrap">
              <th class="py-3 px-3 w-10 text-center">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="rounded border-slate-700 bg-slate-950 text-indigo-500 focus:ring-indigo-500/20"
                />
              </th>
              <th class="py-3.5 px-4 w-12">#</th>
              <th class="py-3.5 px-4">
                <div class="flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                  <span>Subject Name</span>
                </div>
              </th>
              <th class="py-3.5 px-4">Code</th>
              <th class="py-3.5 px-4">Major</th>
              <th class="py-3.5 px-4 text-center">Credits</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 text-xs">
            <tr v-if="paginatedSubjects.length === 0">
              <td colspan="8" class="py-8 text-center text-slate-300">
                No subjects found matching criteria.
              </td>
            </tr>
            <tr
              v-for="(sub, idx) in paginatedSubjects"
              :key="sub.id"
              class="hover:bg-slate-800/30 transition-all"
              :class="{ 'bg-indigo-950/20': selectedIds.includes(sub.id) }"
            >
              <td class="py-4 px-3 text-center">
                <input
                  type="checkbox"
                  :value="sub.id"
                  v-model="selectedIds"
                  class="rounded border-slate-700 bg-slate-950 text-indigo-500 focus:ring-indigo-500/20"
                />
              </td>

              <td class="py-4 px-4 font-sans text-slate-300 font-semibold whitespace-nowrap">
                {{ String((currentPage - 1) * pageSize + idx + 1).padStart(2, '0') }}
              </td>

              <td class="py-4 px-4 whitespace-nowrap">
                <div>
                  <div class="font-bold text-white text-sm">{{ sub.name }}</div>
                  <div class="text-[11px] text-indigo-300 font-medium">{{ sub.name_kh }}</div>
                </div>
              </td>

              <td class="py-4 px-4 whitespace-nowrap">
                <span class="px-2 py-0.5 bg-slate-950 rounded text-sky-300 font-sans font-semibold border border-slate-800">{{ sub.code }}</span>
              </td>

              <td class="py-4 px-4 whitespace-nowrap text-slate-200 font-medium">
                {{ sub.major }}
              </td>

              <td class="py-4 px-4 text-center font-bold text-sky-300 font-sans whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-xl bg-slate-950 border border-slate-800">
                  {{ sub.credits }} Credits
                </span>
              </td>

              <td class="py-4 px-4 text-center whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 inline-flex items-center gap-1.5 shadow-sm">
                  <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Active</span>
                </span>
              </td>

              <!-- STANDARDIZED ROW ACTIONS -->
              <td class="py-4 px-4 text-right whitespace-nowrap relative" @click.stop>
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openEditModal(sub)"
                    class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-500/30 rounded-xl font-bold whitespace-nowrap inline-flex items-center gap-1"
                  >
                    <svg class="w-3.5 h-3.5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Edit</span>
                  </button>

                  <div class="relative">
                    <button
                      @click="toggleDropdown(sub.id)"
                      class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl font-bold transition-all border border-slate-700"
                      title="More Options"
                    >
                      <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                      </svg>
                    </button>

                    <div
                      v-if="activeDropdownId === sub.id"
                      class="absolute right-0 mt-1 w-40 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl z-50 p-1.5 text-xs text-left space-y-1 backdrop-blur-xl animate-fade-in"
                    >
                      <button
                        @click="viewDetails(sub)"
                        class="w-full px-3 py-2 text-slate-200 hover:text-white hover:bg-slate-800 rounded-xl flex items-center gap-2 font-medium"
                      >
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Details
                      </button>

                      <div class="border-t border-slate-800 my-1"></div>

                      <button
                        @click="deleteSubject(sub)"
                        class="w-full px-3 py-2 text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-xl flex items-center gap-2 font-medium"
                      >
                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
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
          <span>Showing <strong class="text-white">{{ filteredSubjects.length === 0 ? 0 : (currentPage - 1) * pageSize + 1 }}</strong> to <strong class="text-white">{{ Math.min(currentPage * pageSize, filteredSubjects.length) }}</strong> of <strong class="text-white">{{ filteredSubjects.length }}</strong> subjects</span>

          <div class="flex items-center gap-1.5 ml-2">
            <span>Per page:</span>
            <select v-model="pageSize" class="bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-white">
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="25">25</option>
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
              :class="currentPage === p ? 'bg-indigo-600 text-white' : 'bg-slate-950 border border-slate-800 text-slate-400 hover:text-white'"
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

      <!-- ADD / EDIT SUBJECT MODAL -->
      <div v-if="isModalOpen" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900/95 border border-slate-700/60 rounded-2xl max-w-xl w-full p-5 space-y-4 shadow-2xl backdrop-blur-2xl overflow-y-auto max-h-[90vh]">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shadow-sm">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
              <h3 class="text-sm font-bold text-slate-100 tracking-wide uppercase">
                {{ editingSubject ? 'EDIT SUBJECT' : 'ADD SUBJECT' }}
              </h3>
            </div>
            <button @click="isModalOpen = false" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white transition-all">✕</button>
          </div>

          <form @submit.prevent="saveSubject" class="space-y-3.5 text-xs">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Subject Code</label>
                <input v-model="form.code" type="text" class="w-full bg-slate-950/80 border border-slate-800 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-sans focus:outline-none focus:ring-1 focus:ring-indigo-500/20 transition-all" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Subject Name (EN)</label>
                <input v-model="form.name" type="text" placeholder="e.g. C Programming" class="w-full bg-slate-950/80 border border-slate-800 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/20 transition-all" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Subject Name (KH)</label>
                <input v-model="form.name_kh" type="text" placeholder="e.g. ភាសា C Programming" class="w-full bg-slate-950/80 border border-slate-800 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-xs text-indigo-300 font-khmer focus:outline-none focus:ring-1 focus:ring-indigo-500/20 transition-all" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Credits</label>
                <select v-model.number="form.credits" class="w-full bg-slate-950/80 border border-slate-800 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500/20 transition-all">
                  <option :value="1">1 Credit</option>
                  <option :value="2">2 Credits</option>
                  <option :value="3">3 Credits</option>
                  <option :value="4">4 Credits</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Faculty</label>
                <select v-model="form.faculty" class="w-full bg-slate-950/80 border border-slate-800 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500/20 transition-all">
                  <option value="Faculty of Computing">Faculty of Computing</option>
                  <option value="Faculty of Tourism">Faculty of Tourism</option>
                  <option value="Faculty of Education">Faculty of Education</option>
                  <option value="Faculty of Agriculture">Faculty of Agriculture</option>
                  <option value="Faculty of Social Science">Faculty of Social Science</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Major</label>
                <select v-model="form.major" class="w-full bg-slate-950/80 border border-slate-800 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500/20 transition-all">
                  <option value="IT & Networking">IT & Networking</option>
                  <option value="Tourism Management">Tourism Management</option>
                  <option value="English Literature">English Literature</option>
                  <option value="Agronomy">Agronomy</option>
                  <option value="Social Work">Social Work</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Prerequisite</label>
                <input v-model="form.prerequisite" type="text" placeholder="e.g. Computer Basics" class="w-full bg-slate-950/80 border border-slate-800 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500/20 transition-all" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Difficulty Level</label>
                <select v-model="form.difficulty" class="w-full bg-slate-950/80 border border-slate-800 focus:border-indigo-500 rounded-xl px-3.5 py-2 text-xs text-slate-100 font-medium focus:outline-none focus:ring-1 focus:ring-indigo-500/20 transition-all">
                  <option value="Beginner">Beginner</option>
                  <option value="Intermediate">Intermediate</option>
                  <option value="Advanced">Advanced</option>
                </select>
              </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800/80">
              <button type="button" @click="isModalOpen = false" :disabled="isSubmitting" class="px-4 py-2 bg-slate-800/80 hover:bg-slate-700 text-slate-300 font-semibold text-xs rounded-xl transition-all disabled:opacity-50">Cancel</button>
              <button type="submit" :disabled="isSubmitting" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                <svg v-if="isSubmitting" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ isSubmitting ? (editingSubject ? 'កំពុងរក្សាទុក...' : 'កំពុងបង្កើតមុខវិជ្ជា...') : (editingSubject ? 'Save Subject' : 'Create Subject') }}</span>
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
