<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

export interface AnnouncementItem {
  id: number
  title_kh: string
  title_en: string
  message_kh: string
  message_en: string
  audience_type: 'all_students' | 'all_teachers' | 'students_by_major' | 'teachers_by_dept' | 'specific_course' | 'everyone'
  audience_label: string
  major: string
  course: string
  academic_year: string
  published_date: string
  status: 'Published' | 'Scheduled' | 'Draft'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  attachment?: string
  sent_count: number
  read_rate: string
  is_pinned?: boolean
}

const props = defineProps<{
  announcements?: AnnouncementItem[]
  topStats?: any
}>()

// Default Mock Data covering SPI 5 Majors and official thesis scenarios
const defaultAnnouncements: AnnouncementItem[] = [
  {
    id: 1,
    title_kh: 'ការចុះឈ្មោះមុខវិជ្ជាឆមាសទី១ ឆ្នាំ២០២៦',
    title_en: 'Semester 1 Course Registration 2026',
    message_kh: 'សូមជម្រាបជូននិស្សិតដេប៉ាតឺម៉ង់ព័ត៌មានវិទ្យាទាំងអស់ ការចុះឈ្មោះមុខវិជ្ជាសម្រាប់ឆមាសទី១ បានបើកហើយ។ សូមចូលទៅកាន់ Course Registration មុនថ្ងៃទី ១០ តុលា។',
    message_en: 'Notice to all IT students: Semester 1 course registration is now officially open. Please register before October 10th.',
    audience_type: 'students_by_major',
    audience_label: 'Students by Major',
    major: 'Information Technology',
    course: 'All Year 2 IT Courses',
    academic_year: '2026',
    published_date: '2026-09-28 08:30',
    status: 'Published',
    priority: 'high',
    attachment: 'IT_Course_Catalog_2026.pdf',
    sent_count: 520,
    read_rate: '94%',
    is_pinned: true,
  },
  {
    id: 2,
    title_kh: 'កាលវិភាគប្រឡងបញ្ចប់វគ្គសិក្សាផ្លូវការ',
    title_en: 'Official Final Examination Schedule',
    message_kh: 'វិទ្យាស្ថានសូមជូនដំណឹងអំពីកាលវិភាគប្រឡងបញ្ចប់វគ្គសិក្សា សម្រាប់និស្សិតគ្រប់ជំនាញទាំង ៥ សូមពិនិត្យកាលវិភាគលម្អិតក្នុងឯកសារភ្ជាប់។',
    message_en: 'The institute announces the official final examination schedule for all 5 majors. Check the attached document for details.',
    audience_type: 'all_students',
    audience_label: 'All Students',
    major: 'All Majors',
    course: 'All Courses',
    academic_year: '2026',
    published_date: '2026-09-30 09:00',
    status: 'Published',
    priority: 'urgent',
    attachment: 'SPI_Final_Exam_Schedule_2026.pdf',
    sent_count: 2458,
    read_rate: '96%',
    is_pinned: true,
  },
  {
    id: 3,
    title_kh: 'ដំណើរសិក្សាស្រាវជ្រាវជីវជាតិដីកសិកម្ម (Field Trip)',
    title_en: 'Soil Science Field Trip & Research Workshop',
    message_kh: 'ជូនដំណឹងដល់និស្សិតជំនាញកសិកម្ម ដំណើរសិក្សាស្រាវជ្រាវដី និងសារធាតុចិញ្ចឹមដំណាំនឹងប្រព្រឹត្តទៅនៅស្រុកបន្ទាយស្រី។',
    message_en: 'Field study research on soil nutrition for Agriculture students will be conducted at Banteay Srei district.',
    audience_type: 'students_by_major',
    audience_label: 'Students by Major',
    major: 'Agriculture',
    course: 'Soil Science & Plant Nutrition',
    academic_year: '2026',
    published_date: '2026-10-05 07:30',
    status: 'Scheduled',
    priority: 'medium',
    attachment: 'Field_Trip_Guide_Agriculture.pdf',
    sent_count: 380,
    read_rate: '0%',
    is_pinned: false,
  },
  {
    id: 4,
    title_kh: 'កិច្ចប្រជុំគណៈកម្មការកែលម្អកម្មវិធីសិក្សា',
    title_en: 'Curriculum Revision & Faculty Board Meeting',
    message_kh: 'សូមអញ្ជើញលោកគ្រូ-អ្នកគ្រូប្រធានដេប៉ាតឺម៉ង់ និងសាស្ត្រាចារ្យទាំងអស់ចូលរួមកិច្ចប្រជុំត្រួតពិនិត្យ Syllabus នៅបន្ទប់សន្និសីទ។',
    message_en: 'All department heads and faculty members are invited to the curriculum and syllabus review meeting.',
    audience_type: 'all_teachers',
    audience_label: 'All Teachers',
    major: 'All Departments',
    course: '-',
    academic_year: '2026',
    published_date: '2026-09-29 14:00',
    status: 'Published',
    priority: 'high',
    attachment: 'Curriculum_Agenda_Agenda.pdf',
    sent_count: 145,
    read_rate: '91%',
    is_pinned: false,
  },
  {
    id: 5,
    title_kh: 'គោលការណ៍ណែនាំកិច្ចការស្រាវជ្រាវទេសចរណ៍',
    title_en: 'Tourism Operations Midterm Assignment Guidelines',
    message_kh: 'និស្សិតដែលរៀនមុខវិជ្ជា Tourism Operations & Booking Systems ត្រូវទាញយកទម្រង់ assignment គំរូ និងបញ្ជូនមុនថ្ងៃផុតកំណត់។',
    message_en: 'Students enrolled in Tourism Operations & Booking Systems must review the assignment rubric and submit on time.',
    audience_type: 'specific_course',
    audience_label: 'Specific Course',
    major: 'Tourism Management',
    course: 'Tourism Operations & Booking Systems',
    academic_year: '2026',
    published_date: '2026-10-08 09:00',
    status: 'Scheduled',
    priority: 'medium',
    attachment: 'Tourism_Assignment_Rubric.pdf',
    sent_count: 245,
    read_rate: '0%',
    is_pinned: false,
  },
  {
    id: 6,
    title_kh: 'សេចក្តីព្រាង៖ ការចុះអនុវត្តការងារសង្គមកិច្ចសហគមន៍',
    title_en: 'Draft: Social Work Community Practicum Guidelines',
    message_kh: 'សេចក្តីព្រាងបទបញ្ញត្តិស្តីពីការចុះកម្មសិក្សាផ្ទាល់នៅអង្គការដៃគូ និងមន្ទីរសង្គមកិច្ចខេត្តសៀមរាប។',
    message_en: 'Drafting internship protocols with NGO partners and Siem Reap Social Affairs Department.',
    audience_type: 'students_by_major',
    audience_label: 'Students by Major',
    major: 'Social Work',
    course: 'Child Protection Protocols',
    academic_year: '2026',
    published_date: '-',
    status: 'Draft',
    priority: 'low',
    attachment: '',
    sent_count: 0,
    read_rate: '0%',
    is_pinned: false,
  }
]

const announcementList = ref<AnnouncementItem[]>(
  props.announcements && props.announcements.length > 0 ? (props.announcements as any) : defaultAnnouncements
)

// Filters State
const searchQuery = ref('')
const selectedStatus = ref('all')
const selectedAudience = ref('all')
const selectedMajor = ref('all')

// Modals State
const showCreateModal = ref(false)
const showViewModal = ref(false)
const selectedItem = ref<AnnouncementItem | null>(null)
const isEditing = ref(false)

// Form State for Create / Edit
const form = ref({
  id: 0,
  title_kh: '',
  title_en: '',
  message_kh: '',
  message_en: '',
  audience_type: 'all_students' as AnnouncementItem['audience_type'],
  major: 'Information Technology',
  course: 'All Courses',
  academic_year: '2026',
  published_date: new Date().toISOString().slice(0, 10),
  status: 'Published' as AnnouncementItem['status'],
  priority: 'high' as AnnouncementItem['priority'],
  attachment: '',
  is_pinned: false,
})

// SPI 5 Majors
const majorsList = [
  'Information Technology',
  'Agriculture',
  'English Literature',
  'Tourism Management',
  'Social Work',
]

// Courses List for dropdown
const courseOptions: Record<string, string[]> = {
  'Information Technology': ['All IT Courses', 'Web Development Basics', 'C Programming & Data Structures', 'Computer Networks & Security'],
  'Agriculture': ['All Agriculture Courses', 'Soil Science & Plant Nutrition', 'Crop Production & Pest Management', 'Agronomy Field Practice'],
  'English Literature': ['All English Courses', 'Academic English Grammar', 'Advanced Essay Writing', 'Applied Linguistics'],
  'Tourism Management': ['All Tourism Courses', 'Tourism Operations & Booking Systems', 'Hospitality Management', 'Ecotourism Development'],
  'Social Work': ['All Social Work Courses', 'Child Protection Protocols', 'Community Development Principles', 'Social Welfare Policies'],
}

// Estimated Reach calculation
const estimatedReach = computed(() => {
  if (form.value.audience_type === 'everyone') return 2751
  if (form.value.audience_type === 'all_students') return 2458
  if (form.value.audience_type === 'all_teachers') return 145
  if (form.value.audience_type === 'students_by_major') {
    if (form.value.major === 'Information Technology') return 520
    if (form.value.major === 'Agriculture') return 380
    if (form.value.major === 'English Literature') return 410
    if (form.value.major === 'Tourism Management') return 480
    if (form.value.major === 'Social Work') return 320
    return 400
  }
  if (form.value.audience_type === 'specific_course') return 65
  return 145
})

// Filtered List
const filteredList = computed(() => {
  return announcementList.value.filter(item => {
    const q = searchQuery.value.toLowerCase().trim()
    const matchesSearch = !q ||
      item.title_kh.toLowerCase().includes(q) ||
      item.title_en.toLowerCase().includes(q) ||
      item.major.toLowerCase().includes(q) ||
      item.course.toLowerCase().includes(q)

    const matchesStatus = selectedStatus.value === 'all' || item.status === selectedStatus.value
    const matchesAudience = selectedAudience.value === 'all' || item.audience_type === selectedAudience.value
    const matchesMajor = selectedMajor.value === 'all' || item.major === selectedMajor.value || item.major === 'All Majors'

    return matchesSearch && matchesStatus && matchesAudience && matchesMajor
  })
})

// Toast Feedback
const toastMessage = ref('')
function showToast(msg: string) {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = ''
  }, 3500)
}

// Modal Handlers
function openCreateModal() {
  isEditing.value = false
  form.value = {
    id: 0,
    title_kh: '',
    title_en: '',
    message_kh: '',
    message_en: '',
    audience_type: 'students_by_major',
    major: 'Information Technology',
    course: 'All IT Courses',
    academic_year: '2026',
    published_date: new Date().toISOString().slice(0, 10),
    status: 'Published',
    priority: 'high',
    attachment: '',
    is_pinned: false,
  }
  showCreateModal.value = true
}

function openEditModal(item: AnnouncementItem) {
  isEditing.value = true
  form.value = {
    id: item.id,
    title_kh: item.title_kh,
    title_en: item.title_en,
    message_kh: item.message_kh,
    message_en: item.message_en,
    audience_type: item.audience_type,
    major: item.major,
    course: item.course,
    academic_year: item.academic_year || '2026',
    published_date: item.published_date !== '-' ? item.published_date.slice(0, 10) : new Date().toISOString().slice(0, 10),
    status: item.status,
    priority: item.priority,
    attachment: item.attachment || '',
    is_pinned: !!item.is_pinned,
  }
  showCreateModal.value = true
}

function openViewModal(item: AnnouncementItem) {
  selectedItem.value = item
  showViewModal.value = true
}

function saveAnnouncement() {
  if (!form.value.title_kh && !form.value.title_en) {
    showToast('⚠️ Please enter an announcement title.')
    return
  }

  const audienceLabels: Record<string, string> = {
    all_students: 'All Students',
    all_teachers: 'All Teachers',
    students_by_major: 'Students by Major',
    teachers_by_dept: 'Teachers by Dept',
    specific_course: 'Specific Course',
    everyone: 'Everyone',
  }

  if (isEditing.value && form.value.id) {
    const idx = announcementList.value.findIndex(a => a.id === form.value.id)
    if (idx !== -1) {
      announcementList.value[idx] = {
        ...announcementList.value[idx],
        title_kh: form.value.title_kh,
        title_en: form.value.title_en || form.value.title_kh,
        message_kh: form.value.message_kh,
        message_en: form.value.message_en || form.value.message_kh,
        audience_type: form.value.audience_type,
        audience_label: audienceLabels[form.value.audience_type] || 'Custom Audience',
        major: form.value.audience_type === 'students_by_major' || form.value.audience_type === 'specific_course' ? form.value.major : 'All Majors',
        course: form.value.course,
        academic_year: form.value.academic_year,
        published_date: form.value.status === 'Draft' ? '-' : form.value.published_date,
        status: form.value.status,
        priority: form.value.priority,
        attachment: form.value.attachment,
        is_pinned: form.value.is_pinned,
      }
      showToast(`✅ Announcement "${form.value.title_kh || form.value.title_en}" updated successfully!`)
    }
  } else {
    const nextId = Math.max(...announcementList.value.map(a => a.id), 0) + 1
    const newItem: AnnouncementItem = {
      id: nextId,
      title_kh: form.value.title_kh,
      title_en: form.value.title_en || form.value.title_kh,
      message_kh: form.value.message_kh,
      message_en: form.value.message_en || form.value.message_kh,
      audience_type: form.value.audience_type,
      audience_label: audienceLabels[form.value.audience_type] || 'Audience',
      major: form.value.audience_type === 'students_by_major' || form.value.audience_type === 'specific_course' ? form.value.major : 'All Majors',
      course: form.value.course,
      academic_year: form.value.academic_year,
      published_date: form.value.status === 'Draft' ? '-' : `${form.value.published_date} 09:00`,
      status: form.value.status,
      priority: form.value.priority,
      attachment: form.value.attachment,
      sent_count: form.value.status === 'Published' ? estimatedReach.value : 0,
      read_rate: form.value.status === 'Published' ? '12%' : '0%',
      is_pinned: form.value.is_pinned,
    }
    announcementList.value.unshift(newItem)
    showToast(`📢 Announcement published! Dispatched notifications to ${estimatedReach.value} users.`)
  }

  showCreateModal.value = false
}

function deleteAnnouncement(id: number) {
  announcementList.value = announcementList.value.filter(a => a.id !== id)
  showToast('🗑️ Announcement deleted.')
}
</script>

<template>
  <AdminLayout title="Communication — Announcements">
    <div class="space-y-6 pb-12">
      <!-- Toast Notification Alert -->
      <transition
        enter-active-class="transform transition ease-out duration-300"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="toastMessage" class="fixed top-20 right-6 z-50 bg-emerald-600 text-white text-xs px-4 py-3 rounded-xl shadow-2xl flex items-center gap-2 border border-emerald-400">
          <span>{{ toastMessage }}</span>
        </div>
      </transition>

      <!-- ── MODULE HEADER CARD ── -->
      <div class="relative overflow-hidden bg-slate-800/90 border border-slate-700/70 rounded-2xl p-6 shadow-xl backdrop-blur-xl space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-700/50 pb-4">
          <div>
            <div class="flex items-center gap-3">
              <div class="p-3 rounded-2xl bg-amber-500/20 border border-amber-400/30 text-amber-400 shadow-lg shadow-amber-500/10">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
              </div>
              <div>
                <h1 class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-amber-400 via-orange-300 to-amber-200">
                  📢 Communication → Announcements
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                  ផ្សព្វផ្សាយព័ត៌មានផ្លូវការទៅកាន់ Student / Teacher តាម Major, Course ឬអ្នកប្រើប្រាស់ទាំងមូល (Official Broadcast Hub)
                </p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2.5">
            <button
              @click="openCreateModal"
              class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-bold text-xs rounded-xl shadow-lg shadow-amber-500/20 transition flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
              <span>+ Create Announcement</span>
            </button>
          </div>
        </div>

        <!-- ── SUB-NAVIGATION TABS ── -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pt-1 text-xs">
          <Link
            href="/admin/notifications/announcements"
            class="px-4 py-2 rounded-xl bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 flex items-center gap-2 shrink-0"
          >
            <span>📢 Announcements</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-white/20 text-white">{{ announcementList.length }}</span>
          </Link>
          <Link
            href="/admin/notifications"
            class="px-4 py-2 rounded-xl bg-slate-900/80 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-700/50 flex items-center gap-2 shrink-0 transition-all"
          >
            <span>🔔 Notifications Hub</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-slate-800 text-slate-400">8 Types</span>
          </Link>
        </div>
      </div>

      <!-- ── STATS CARDS ── -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div class="bg-slate-800/80 border border-slate-700/60 p-4 rounded-xl space-y-1">
          <span class="text-slate-400 block font-medium">📢 Total Announcements</span>
          <p class="text-2xl font-black text-white">{{ announcementList.length }}</p>
          <span class="text-[10px] text-emerald-400">Across 5 SPI Majors</span>
        </div>
        <div class="bg-slate-800/80 border border-slate-700/60 p-4 rounded-xl space-y-1">
          <span class="text-slate-400 block font-medium">✅ Published Live</span>
          <p class="text-2xl font-black text-emerald-400">{{ announcementList.filter(a => a.status === 'Published').length }}</p>
          <span class="text-[10px] text-slate-400">Active broadcasts</span>
        </div>
        <div class="bg-slate-800/80 border border-slate-700/60 p-4 rounded-xl space-y-1">
          <span class="text-slate-400 block font-medium">⏰ Scheduled Queue</span>
          <p class="text-2xl font-black text-amber-400">{{ announcementList.filter(a => a.status === 'Scheduled').length }}</p>
          <span class="text-[10px] text-amber-300/80">Pending future date</span>
        </div>
        <div class="bg-slate-800/80 border border-slate-700/60 p-4 rounded-xl space-y-1">
          <span class="text-slate-400 block font-medium">📝 Drafts</span>
          <p class="text-2xl font-black text-slate-400">{{ announcementList.filter(a => a.status === 'Draft').length }}</p>
          <span class="text-[10px] text-slate-500">Unpublished</span>
        </div>
      </div>

      <!-- ── FILTERS BAR ── -->
      <div class="bg-slate-800/80 border border-slate-700/60 p-4 rounded-2xl flex flex-col md:flex-row gap-3 items-center justify-between text-xs">
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
          <!-- Status Filter -->
          <select v-model="selectedStatus" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-slate-200">
            <option value="all">Status: All Statuses</option>
            <option value="Published">Published Live</option>
            <option value="Scheduled">Scheduled</option>
            <option value="Draft">Draft</option>
          </select>

          <!-- Major Filter -->
          <select v-model="selectedMajor" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-slate-200">
            <option value="all">Major: All 5 Majors</option>
            <option v-for="m in majorsList" :key="m" :value="m">{{ m }}</option>
          </select>

          <!-- Audience Filter -->
          <select v-model="selectedAudience" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-slate-200">
            <option value="all">Audience: All Audiences</option>
            <option value="all_students">All Students</option>
            <option value="all_teachers">All Teachers</option>
            <option value="students_by_major">Students by Major</option>
            <option value="teachers_by_dept">Teachers by Dept</option>
            <option value="specific_course">Specific Course</option>
          </select>
        </div>

        <div class="w-full md:w-72">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="🔍 Search title, major, course..."
              class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-3 pr-8 py-2 text-slate-200 focus:outline-none focus:border-amber-500"
            />
            <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-2.5 top-2 text-slate-500 hover:text-slate-300">✕</button>
          </div>
        </div>
      </div>

      <!-- ── ANNOUNCEMENTS TABLE (Official Schema: Title | Audience | Major | Course | Published Date | Status | Actions) ── -->
      <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-900/90 border-b border-slate-700 text-slate-400 uppercase font-semibold tracking-wider">
                <th class="p-3.5 pl-4">Title (ចំណងជើង)</th>
                <th class="p-3.5">Audience (អ្នកទទួល)</th>
                <th class="p-3.5">Major (ជំនាញ)</th>
                <th class="p-3.5">Course (Course)</th>
                <th class="p-3.5">Published Date (ថ្ងៃផ្សព្វផ្សាយ)</th>
                <th class="p-3.5">Status</th>
                <th class="p-3.5 pr-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/60 text-slate-300">
              <tr v-for="item in filteredList" :key="item.id" class="hover:bg-slate-700/30 transition">
                <!-- Title -->
                <td class="p-3.5 pl-4 max-w-xs">
                  <div class="flex items-center gap-2 font-bold text-white">
                    <span v-if="item.is_pinned" class="text-amber-400 shrink-0" title="Pinned to Top">📌</span>
                    <span class="truncate">{{ item.title_kh }}</span>
                  </div>
                  <div class="text-[11px] text-slate-400 truncate mt-0.5 font-sans">{{ item.title_en }}</div>
                  <div v-if="item.attachment" class="text-[10px] text-indigo-400 mt-0.5 flex items-center gap-1 font-mono">
                    📎 {{ item.attachment }}
                  </div>
                </td>

                <!-- Audience -->
                <td class="p-3.5">
                  <span class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-slate-900 text-slate-200 border border-slate-700/80 inline-flex items-center gap-1.5">
                    <span v-if="item.audience_type === 'all_students'">👨‍🎓</span>
                    <span v-else-if="item.audience_type === 'all_teachers'">👨‍🏫</span>
                    <span v-else-if="item.audience_type === 'students_by_major'">🎓</span>
                    <span v-else>👥</span>
                    <span>{{ item.audience_label }}</span>
                  </span>
                </td>

                <!-- Major -->
                <td class="p-3.5 font-medium">
                  <span
                    :class="[
                      item.major === 'Information Technology' ? 'text-cyan-400' :
                      item.major === 'Agriculture' ? 'text-emerald-400' :
                      item.major === 'English Literature' ? 'text-indigo-400' :
                      item.major === 'Tourism Management' ? 'text-amber-400' :
                      item.major === 'Social Work' ? 'text-rose-400' : 'text-slate-300'
                    ]"
                  >
                    {{ item.major }}
                  </span>
                </td>

                <!-- Course -->
                <td class="p-3.5 text-slate-300 font-mono text-[11px] max-w-[150px] truncate" :title="item.course">
                  {{ item.course }}
                </td>

                <!-- Published Date -->
                <td class="p-3.5 text-slate-400 font-mono text-[11px]">
                  {{ item.published_date }}
                </td>

                <!-- Status -->
                <td class="p-3.5">
                  <span
                    :class="[
                      item.status === 'Published' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' :
                      item.status === 'Scheduled' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' :
                      'bg-slate-700/40 text-slate-400 border-slate-600'
                    ]"
                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg border inline-flex items-center gap-1.5"
                  >
                    <span v-if="item.status === 'Published'" class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span v-else-if="item.status === 'Scheduled'" class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                    <span>{{ item.status }}</span>
                  </span>
                </td>

                <!-- Actions -->
                <td class="p-3.5 pr-4 text-right space-x-1.5 whitespace-nowrap">
                  <button
                    @click="openViewModal(item)"
                    class="px-2.5 py-1 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg transition"
                    title="View Announcement"
                  >
                    👁 View
                  </button>
                  <button
                    @click="openEditModal(item)"
                    class="px-2.5 py-1 bg-indigo-600/30 hover:bg-indigo-600/50 text-indigo-300 border border-indigo-500/40 rounded-lg transition"
                    title="Edit Announcement"
                  >
                    ✏️ Edit
                  </button>
                  <button
                    @click="deleteAnnouncement(item.id)"
                    class="px-2 py-1 hover:bg-red-500/20 text-red-400 rounded-lg transition"
                    title="Delete"
                  >
                    🗑️
                  </button>
                </td>
              </tr>

              <tr v-if="filteredList.length === 0">
                <td colspan="7" class="p-8 text-center text-slate-500">
                  មិនមានសេចក្តីប្រកាសដែលត្រូវនឹងលក្ខខណ្ឌស្វែងរកទេ។ (No announcements match your search filters.)
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── 2. CREATE / EDIT ANNOUNCEMENT MODAL ── -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-3xl max-h-[92vh] flex flex-col shadow-2xl overflow-hidden my-auto text-xs text-slate-200">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center bg-slate-950">
          <div class="flex items-center gap-2.5">
            <span class="p-2 rounded-xl bg-amber-500/20 text-amber-400 font-bold">📢</span>
            <div>
              <h3 class="text-sm font-bold text-white">
                {{ isEditing ? 'Edit Announcement' : 'Create Official Announcement' }}
              </h3>
              <p class="text-[11px] text-slate-400">ផ្សព្វផ្សាយទៅកាន់ Students, Teachers តាម Major និង Course</p>
            </div>
          </div>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white text-base">✕</button>
        </div>

        <!-- Form Body -->
        <div class="p-6 space-y-4 overflow-y-auto custom-scrollbar">
          <!-- Title (Khmer & English) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-300 block mb-1">Title (ភាសាខ្មែរ) *</label>
              <input
                v-model="form.title_kh"
                type="text"
                placeholder="ឧ. ការចុះឈ្មោះមុខវិជ្ជាឆមាសទី១..."
                class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500"
              />
            </div>
            <div>
              <label class="font-bold text-slate-300 block mb-1">Title (English)</label>
              <input
                v-model="form.title_en"
                type="text"
                placeholder="e.g. Semester 1 Course Registration..."
                class="w-full bg-slate-950 border border-slate-700 rounded-xl p-2.5 text-white focus:outline-none focus:border-amber-500"
              />
            </div>
          </div>

          <!-- Message Body -->
          <div>
            <label class="font-bold text-slate-300 block mb-1">Message Content (ខ្លឹមសារសេចក្តីប្រកាស) *</label>
            <textarea
              v-model="form.message_kh"
              rows="4"
              placeholder="សូមជម្រាបជូននិស្សិតទាំងអស់..."
              class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-slate-200 focus:outline-none focus:border-amber-500"
            ></textarea>
          </div>

          <!-- Audience Selection -->
          <div class="bg-slate-950/80 border border-slate-800 p-4 rounded-xl space-y-3">
            <label class="font-bold text-amber-400 uppercase tracking-wider block">🎯 Target Audience (អ្នកទទួល)</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-lg cursor-pointer hover:border-slate-700">
                <input type="radio" v-model="form.audience_type" value="all_students" /> All Students
              </label>
              <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-lg cursor-pointer hover:border-slate-700">
                <input type="radio" v-model="form.audience_type" value="all_teachers" /> All Teachers
              </label>
              <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-lg cursor-pointer hover:border-slate-700">
                <input type="radio" v-model="form.audience_type" value="students_by_major" /> Students by Major
              </label>
              <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-lg cursor-pointer hover:border-slate-700">
                <input type="radio" v-model="form.audience_type" value="teachers_by_dept" /> Teachers by Dept
              </label>
              <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-lg cursor-pointer hover:border-slate-700">
                <input type="radio" v-model="form.audience_type" value="specific_course" /> Specific Course
              </label>
              <label class="flex items-center gap-2 p-2 bg-slate-900 border border-slate-800 rounded-lg cursor-pointer hover:border-slate-700">
                <input type="radio" v-model="form.audience_type" value="everyone" /> Everyone
              </label>
            </div>

            <!-- Contextual Dropdowns for Major & Course -->
            <div v-if="form.audience_type === 'students_by_major' || form.audience_type === 'specific_course'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
              <div>
                <label class="text-slate-400 block mb-1">Select Major (ជំនាញ):</label>
                <select v-model="form.major" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white">
                  <option v-for="m in majorsList" :key="m" :value="m">{{ m }}</option>
                </select>
              </div>
              <div>
                <label class="text-slate-400 block mb-1">Course:</label>
                <select v-model="form.course" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white">
                  <option v-for="c in (courseOptions[form.major] || ['All Courses'])" :key="c" :value="c">{{ c }}</option>
                </select>
              </div>
            </div>

            <div class="text-right text-[11px] font-bold text-emerald-400">
              ➔ Estimated Target Reach: ~{{ estimatedReach }} users
            </div>
          </div>

          <!-- Publish Date, Status & Attachment -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="font-bold text-slate-300 block mb-1">Status *</label>
              <select v-model="form.status" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-white">
                <option value="Published">Published (ផ្សព្វផ្សាយភ្លាម)</option>
                <option value="Scheduled">Scheduled (កំណត់កាលបរិច្ឆេទ)</option>
                <option value="Draft">Draft (សេចក្តីព្រាង)</option>
              </select>
            </div>
            <div>
              <label class="font-bold text-slate-300 block mb-1">Publish Date</label>
              <input
                v-model="form.published_date"
                type="date"
                :disabled="form.status === 'Draft'"
                class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-white disabled:opacity-50"
              />
            </div>
            <div>
              <label class="font-bold text-slate-300 block mb-1">Attachment (Optional)</label>
              <input
                v-model="form.attachment"
                type="text"
                placeholder="e.g. Schedule.pdf"
                class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-white font-mono text-[11px]"
              />
            </div>
          </div>

          <!-- Pin option -->
          <div class="flex items-center gap-2 pt-2">
            <input type="checkbox" id="pinCheck" v-model="form.is_pinned" class="rounded border-slate-700" />
            <label for="pinCheck" class="text-slate-300 cursor-pointer">📌 Pin announcement to the top of Student / Teacher dashboard</label>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-3 border-t border-slate-800 bg-slate-950 flex justify-between items-center">
          <button @click="showCreateModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl">
            Cancel
          </button>
          <button
            @click="saveAnnouncement"
            class="px-5 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/20"
          >
            {{ isEditing ? 'Update Announcement' : (form.status === 'Published' ? '🚀 Publish Announcement' : '💾 Save ' + form.status) }}
          </button>
        </div>
      </div>
    </div>

    <!-- ── 3. VIEW DETAILS MODAL ── -->
    <div v-if="showViewModal && selectedItem" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-2xl p-6 shadow-2xl space-y-4 text-xs">
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div>
            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/40">
              {{ selectedItem.status }} Announcement
            </span>
            <h3 class="text-base font-bold text-white mt-1.5">{{ selectedItem.title_kh }}</h3>
            <p class="text-slate-400 text-xs">{{ selectedItem.title_en }}</p>
          </div>
          <button @click="showViewModal = false" class="text-slate-400 hover:text-white text-base">✕</button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-slate-950 p-3 rounded-xl border border-slate-800">
          <div>
            <span class="text-slate-500 block text-[10px]">Audience</span>
            <span class="font-bold text-white">{{ selectedItem.audience_label }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[10px]">Major</span>
            <span class="font-bold text-white">{{ selectedItem.major }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[10px]">Course</span>
            <span class="font-bold text-white">{{ selectedItem.course }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[10px]">Published Date</span>
            <span class="font-bold text-white">{{ selectedItem.published_date }}</span>
          </div>
        </div>

        <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-2">
          <span class="font-bold text-slate-400 uppercase text-[10px]">ខ្លឹមសារសេចក្តីប្រកាស (Message):</span>
          <p class="text-slate-200 leading-relaxed">{{ selectedItem.message_kh }}</p>
          <p v-if="selectedItem.message_en" class="text-slate-400 text-[11px] pt-2 border-t border-slate-800/80">{{ selectedItem.message_en }}</p>
        </div>

        <div v-if="selectedItem.attachment" class="flex items-center justify-between p-3 bg-indigo-950/30 border border-indigo-500/30 rounded-xl text-indigo-300">
          <div class="flex items-center gap-2">
            <span>📎</span>
            <span class="font-mono text-xs">{{ selectedItem.attachment }}</span>
          </div>
          <button @click="showToast('📥 Downloading attachment...')" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-[11px] font-bold">
            Download
          </button>
        </div>

        <div class="flex justify-end pt-2">
          <button @click="showViewModal = false" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl">
            Close
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
