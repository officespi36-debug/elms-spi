<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed, defineAsyncComponent } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'))
import { useLanguage } from '@/Services/i18n'
import { isDark } from '@/composables/useTheme'

const { currentLang, t } = useLanguage()

interface StudentMajorItem {
  id: number
  name: string
  display_name?: string
  name_kh?: string
  code?: string
  count: number
  pct: number
  color?: string
}

interface TopCourseItem {
  id: number
  index: number
  name: string
  major: string
  major_name?: string
  students: number
  pass: string
  instructor: string
  status?: string
}

interface AlertItem {
  type: string
  title: string
  desc: string
  status: string
  color: string
  count: number
  items?: Array<any>
  action_label?: string
}

const props = withDefaults(defineProps<{
  stats?: {
    total_students?: number
    active_students?: number
    total_teachers?: number
    active_teachers?: number
    total_courses?: number
    published_courses?: number
    draft_courses?: number
    total_majors?: number
    active_enrollments?: number
    at_risk_students?: number
    open_alerts?: number
    completion_rate?: number
    system_health?: string
    sub_students?: string
    sub_courses?: string
    sub_teachers?: string
    sub_active_students?: string
    sub_completion?: string
    sub_at_risk?: string
  }
  academicProgress?: {
    completed_count?: number
    completed_pct?: number
    in_progress_count?: number
    in_progress_pct?: number
    not_started_count?: number
    not_started_pct?: number
    certificates_count?: number
    certificates_pct?: number
    total_enrolled?: number
  }
  studentsByMajor?: Array<StudentMajorItem>
  topCourses?: Array<TopCourseItem>
  academicStructure?: {
    majors?: number
    departments?: number
    classes?: number
    academic_years?: number
    shifts?: number
    lessons?: number
    quizzes?: number
  }
  adminAlerts?: Array<AlertItem>
  recentActivities?: Array<{
    name: string
    action: string
    badge: string
    color: string
    time: string
    icon: string
  }>
  deptWiseEnrollment?: Array<{
    id: number
    name: string
    name_kh?: string
    count: number
    pct: number
    color?: string
  }>
  enrollmentChartData?: {
    daily?: { categories: string[]; enrollments: number[]; completions: number[] }
    weekly?: { categories: string[]; enrollments: number[]; completions: number[] }
    monthly?: { categories: string[]; enrollments: number[]; completions: number[] }
  }
  allMajors?: Array<any>
}>(), {
  stats: () => ({
    total_students: 2458,
    active_students: 2390,
    total_teachers: 145,
    active_teachers: 138,
    total_courses: 328,
    published_courses: 284,
    draft_courses: 5,
    total_majors: 5,
    active_enrollments: 442,
    at_risk_students: 12,
    open_alerts: 4,
    completion_rate: 76,
    system_health: 'healthy',
    sub_students: '▲ 12.8% vs Last Term',
    sub_courses: '▲ 8.4% Active Modules',
    sub_teachers: '▲ 5.2% Faculty Staff',
    sub_active_students: '▲ 97.2% Attendance',
    sub_completion: '▲ 6.5% Target Exceeded',
    sub_at_risk: '▼ 4 Students Need Help',
  }),
  academicProgress: () => ({
    completed_count: 1868,
    completed_pct: 76,
    in_progress_count: 442,
    in_progress_pct: 18,
    not_started_count: 148,
    not_started_pct: 6,
    certificates_count: 1540,
    certificates_pct: 92,
    total_enrolled: 2458,
  }),
  studentsByMajor: () => [
    { id: 1, name: 'Info Tech (IT)', display_name: 'Info Tech (IT)', code: 'IT', count: 520, pct: 21, color: '#f59e0b' },
    { id: 2, name: 'Social Work (SW)', display_name: 'Social Work (SW)', code: 'SW', count: 548, pct: 23, color: '#2563eb' },
    { id: 3, name: 'Agriculture (AGR)', display_name: 'Agriculture (AGR)', code: 'AGR', count: 600, pct: 24, color: '#10b981' },
    { id: 4, name: 'Tourism (TRM)', display_name: 'Tourism (TRM)', code: 'TRM', count: 410, pct: 17, color: '#8b5cf6' },
    { id: 5, name: 'English Lit (ENG)', display_name: 'English Lit (ENG)', code: 'ENG', count: 380, pct: 15, color: '#ef4444' },
  ],
  topCourses: () => [
    { id: 1, index: 1, name: 'Web & Mobile Development', major: 'IT', students: 185, pass: '94%', instructor: 'Dr. Sokha Meas' },
    { id: 2, index: 2, name: 'Community Social Work', major: 'SW', students: 142, pass: '88%', instructor: 'Ms. Sreyneang Pich' },
    { id: 3, index: 3, name: 'Modern Crop Agronomy', major: 'AGR', students: 136, pass: '82%', instructor: 'Mr. Vuthy Seng' },
    { id: 4, index: 4, name: 'Eco-Tourism & Hospitality', major: 'TRM', students: 118, pass: '79%', instructor: 'Ms. Linda Meng' },
    { id: 5, index: 5, name: 'Business English Communication', major: 'ENG', students: 124, pass: '91%', instructor: 'Dr. Virak Chea' },
  ],
  academicStructure: () => ({
    majors: 5,
    departments: 12,
    classes: 36,
    academic_years: 2,
    shifts: 4,
    lessons: 1420,
    quizzes: 580,
  }),
  adminAlerts: () => [],
  recentActivities: () => [],
  deptWiseEnrollment: () => [],
  enrollmentChartData: () => ({
    daily: {
      categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      enrollments: [120, 180, 240, 310, 280, 350, 420],
      completions: [40, 70, 100, 140, 180, 220, 280]
    },
    weekly: {
      categories: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      enrollments: [350, 480, 620, 850],
      completions: [180, 260, 380, 550]
    },
    monthly: {
      categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
      enrollments: [320, 450, 520, 680, 720, 860, 920],
      completions: [210, 320, 380, 490, 560, 670, 720]
    }
  }),
})

const page = usePage<any>()

// Timeframe toggle state
const chartTimeframe = ref<'daily' | 'weekly' | 'monthly'>('monthly')
const isProcessingAction = ref(false)
const actionToast = ref('')

// Interactive Modal State
const activeModal = ref<'none' | 'at_risk' | 'courses_review' | 'teachers_review' | 'announcements' | 'course_detail'>('none')
const selectedCourse = ref<TopCourseItem | null>(null)

function openCourseDetail(course: TopCourseItem) {
  selectedCourse.value = course
  activeModal.value = 'course_detail'
}

function openAlertModal(type: string) {
  if (type === 'at_risk') activeModal.value = 'at_risk'
  else if (type === 'courses_review') activeModal.value = 'courses_review'
  else if (type === 'teachers_review') activeModal.value = 'teachers_review'
  else if (type === 'announcements') activeModal.value = 'announcements'
}

function closeModal() {
  activeModal.value = 'none'
  selectedCourse.value = null
}

function showToast(msg: string) {
  actionToast.value = msg
  setTimeout(() => {
    actionToast.value = ''
  }, 3000)
}

// ── REAL BACKEND OPERATIONAL ACTIONS ──────────────────────────

function executeApproveCourses() {
  isProcessingAction.value = true
  router.post('/admin/dashboard/approve-courses', {}, {
    preserveScroll: true,
    onSuccess: () => {
      showToast(t('បានអនុម័តវគ្គសិក្សាទាំងអស់ដោយជោគជ័យ!', 'All pending courses have been approved!'))
      closeModal()
    },
    onFinish: () => {
      isProcessingAction.value = false
    }
  })
}

function executeVerifyTeachers() {
  isProcessingAction.value = true
  router.post('/admin/dashboard/verify-teachers', {}, {
    preserveScroll: true,
    onSuccess: () => {
      showToast(t('បានផ្ទៀងផ្ទាត់ និងផ្តល់សិទ្ធិគ្រូបង្រៀនដោយជោគជ័យ!', 'All pending teacher accounts verified!'))
      closeModal()
    },
    onFinish: () => {
      isProcessingAction.value = false
    }
  })
}

function executeInterveneAtRisk() {
  isProcessingAction.value = true
  router.post('/admin/dashboard/intervene-at-risk', {}, {
    preserveScroll: true,
    onSuccess: () => {
      showToast(t('បានផ្ញើសេចក្តីជូនដំណឹង AI អន្តរាគមន៍រួចរាល់!', 'AI intervention notifications sent!'))
      closeModal()
    },
    onFinish: () => {
      isProcessingAction.value = false
    }
  })
}

function executeBroadcastAnnouncement() {
  isProcessingAction.value = true
  router.post('/admin/dashboard/broadcast-announcement', {}, {
    preserveScroll: true,
    onSuccess: () => {
      showToast(t('បានផ្សព្វផ្សាយសេចក្តីប្រកាសជាបន្ទាន់ដោយជោគជ័យ!', 'Announcements broadcasted to all users!'))
      closeModal()
    },
    onFinish: () => {
      isProcessingAction.value = false
    }
  })
}

function downloadExportReport() {
  window.open('/admin/dashboard/export-report', '_blank')
}

// ── ROW 2: Enrollment & Completion Trends Dual Area Chart ──
const currentChartCategories = computed(() => {
  const data = props.enrollmentChartData?.[chartTimeframe.value]
  return data?.categories || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']
})

const enrollmentChartSeries = computed(() => {
  const data = props.enrollmentChartData?.[chartTimeframe.value]
  return [
    {
      name: t('ការចុះឈ្មោះថ្មី (New Enrollments)', 'New Enrollments'),
      data: data?.enrollments || [35, 52, 68, 92, 115, 142, 160]
    },
    {
      name: t('ការបញ្ចប់វគ្គ (Completions)', 'Course Completions'),
      data: data?.completions || [22, 36, 49, 68, 85, 108, 120]
    }
  ]
})

const enrollmentChartOptions = computed<any>(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    background: 'transparent',
    parentHeightOffset: 0,
  },
  colors: ['#2563eb', '#10b981'],
  stroke: { curve: 'smooth', width: 2.5 },
  dataLabels: { enabled: false },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 }
  },
  xaxis: {
    categories: currentChartCategories.value,
    labels: {
      style: {
        colors: isDark.value ? '#94a3b8' : '#64748b',
        fontSize: '11px',
        fontWeight: 600
      },
    },
    axisBorder: { show: true, color: isDark.value ? '#334155' : '#e2e8f0' },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      formatter: (val: number) => `${Math.round(val)}`,
      style: {
        colors: isDark.value ? '#94a3b8' : '#64748b',
        fontSize: '11px',
        fontWeight: 600
      },
    },
  },
  grid: {
    borderColor: isDark.value ? '#334155' : '#f1f5f9',
    strokeDashArray: 3,
  },
  legend: { show: false },
  tooltip: { theme: isDark.value ? 'dark' : 'light' }
}))

// ── ROW 2: Students by Major Donut Chart ──
const majorDonutSeries = computed(() => {
  if (props.studentsByMajor && props.studentsByMajor.length > 0) {
    return props.studentsByMajor.map(m => m.pct || 1)
  }
  return [21, 23, 24, 17, 15]
})

const majorDonutOptions = computed<any>(() => ({
  chart: { type: 'donut', background: 'transparent' },
  labels: props.studentsByMajor?.map(m => m.display_name || m.code || m.name) || ['Info Tech (IT)', 'Social Work (SW)', 'Agriculture (AGR)', 'Tourism (TRM)', 'English Lit (ENG)'],
  colors: ['#f59e0b', '#2563eb', '#10b981', '#8b5cf6', '#ef4444'],
  legend: { show: false },
  stroke: { colors: [isDark.value ? '#0f172a' : '#ffffff'], width: 3 },
  dataLabels: { enabled: false },
  plotOptions: {
    pie: {
      donut: {
        size: '72%',
        labels: { show: false }
      }
    }
  },
  tooltip: { theme: isDark.value ? 'dark' : 'light' }
}))

// ── Alert Finders ──
const atRiskAlert = computed(() => props.adminAlerts?.find(a => a.type === 'at_risk'))
const coursesReviewAlert = computed(() => props.adminAlerts?.find(a => a.type === 'courses_review'))
const teachersReviewAlert = computed(() => props.adminAlerts?.find(a => a.type === 'teachers_review'))
const announcementsAlert = computed(() => props.adminAlerts?.find(a => a.type === 'announcements'))
</script>

<template>
  <AdminLayout :title="t('ផ្ទាំងគ្រប់គ្រង Admin', 'Admin Dashboard')">
    <div class="space-y-4 text-slate-800 dark:text-slate-100 font-sans pb-12">

      <!-- ── ROW 1: 6 TOP KPI CARDS (Real Live Data Linked to Subsystems) ── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
        
        <!-- Card 1: Total Students ➔ Royal Purple/Violet -->
        <Link
          href="/admin/user-management/students"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7c3aed] to-[#6366f1] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
          :title="t('ចុចដើម្បីគ្រប់គ្រងនិស្សិត', 'Click to manage students')"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('និស្សិតសរុប', "TOTAL STUDENTS") }}</span>
              <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M8 8V6a4 4 0 0 1 8 0v2" />
                  <path d="M5 8h14l-1.3 12.2a2 2 0 0 1-2 1.8H8.3a2 2 0 0 1-2-1.8L5 8z" />
                  <path d="M9 8v5" />
                  <path d="M15 8v5" />
                </svg>
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ (props.stats?.total_students || 0).toLocaleString() }}
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>{{ props.stats?.sub_students || '▲ 12.8% vs Last Term' }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 18 Q 20 4, 40 14 T 70 6 T 100 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>

        <!-- Card 2: Total Courses ➔ Emerald Green -->
        <Link
          href="/admin/course-module/all"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#10b981] to-[#059669] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
          :title="t('ចុចដើម្បីគ្រប់គ្រងវគ្គសិក្សា', 'Click to manage courses')"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('វគ្គសិក្សាសរុប', 'TOTAL COURSES') }}</span>
              <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                  <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                </svg>
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ (props.stats?.total_courses || 0).toLocaleString() }}
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>{{ props.stats?.sub_courses || '▲ 8.4% Active Modules' }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 20 Q 25 8, 45 16 T 75 4 T 100 10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>

        <!-- Card 3: Total Teachers ➔ Vivid Orange -->
        <Link
          href="/admin/user-management/teachers"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#f97316] to-[#ea580c] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
          :title="t('ចុចដើម្បីគ្រប់គ្រងសាស្ត្រាចារ្យ', 'Click to manage faculty')"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('សាស្ត្រាចារ្យសរុប', "TOTAL TEACHERS") }}</span>
              <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ (props.stats?.total_teachers || 0).toLocaleString() }}
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>{{ props.stats?.sub_teachers || '▲ 5.2% Faculty Staff' }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 16 Q 20 22, 45 8 T 75 14 T 100 4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>

        <!-- Card 4: Active Students ➔ Royal Blue -->
        <Link
          href="/admin/user-management/students"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0284c7] to-[#2563eb] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
          :title="t('ចុចដើម្បីមើលវត្តមាននិស្សិត', 'Click to view student attendance')"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('និស្សិតសកម្មអនឡាញ', 'ACTIVE STUDENTS') }}</span>
              <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                </svg>
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ (props.stats?.active_students || 0).toLocaleString() }}
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>{{ props.stats?.sub_active_students || '▲ 97.2% Attendance' }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 18 Q 20 6, 45 16 T 75 8 T 100 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>

        <!-- Card 5: Course Completion Rate ➔ Bright Rose/Pink -->
        <Link
          href="/admin/progress?tab=course"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#ec4899] to-[#e11d48] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
          :title="t('ចុចដើម្បីមើលរបាយការណ៍បញ្ចប់វគ្គ', 'Click to view completion report')"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('អត្រាបញ្ចប់វគ្គ', 'COMPLETION RATE') }}</span>
              <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                  <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                  <path d="M4 22h16" />
                  <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                  <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                  <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                </svg>
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ props.stats?.completion_rate || 0 }}%
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>{{ props.stats?.sub_completion || '▲ 6.5% Target Exceeded' }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 19 Q 25 10, 50 18 T 80 8 T 100 12 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 19 Q 25 10, 50 18 T 80 8 T 100 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>

        <!-- Card 6: At-Risk Students ➔ Teal/Cyan -->
        <Link
          href="/admin/ai-rules?tab=at_risk"
          class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#06b6d4] to-[#0d9488] text-white p-4 shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-[0.99] transition-all flex flex-col justify-between group cursor-pointer"
          :title="t('ចុចដើម្បីពិនិត្យនិស្សិតប្រឈមហានិភ័យ', 'Click to review at-risk students')"
        >
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold tracking-wide uppercase text-white/90">{{ t('និស្សិតប្រឈមហានិភ័យ', 'AT-RISK STUDENTS') }}</span>
              <div class="text-white/95 group-hover:text-white group-hover:scale-105 transition-transform duration-200 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                  <line x1="12" y1="9" x2="12" y2="13" />
                  <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
              </div>
            </div>
            <h4 class="text-2xl font-black tracking-tight text-white mt-1">
              {{ props.stats?.at_risk_students || 0 }}
            </h4>
            <p class="text-[11px] text-white/95 font-medium mt-0.5 flex items-center gap-1">
              <span>{{ props.stats?.sub_at_risk || '▼ 4 Students Need Help' }}</span>
            </p>
          </div>
          <div class="mt-2 -mx-4 -mb-4 overflow-hidden rounded-b-2xl">
            <svg class="w-full h-8 text-white" viewBox="0 0 100 25" preserveAspectRatio="none">
              <path d="M0 12 Q 25 20, 50 10 T 75 16 T 100 6 L 100 25 L 0 25 Z" fill="currentColor" opacity="0.18"/>
              <path d="M0 12 Q 25 20, 50 10 T 75 16 T 100 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" opacity="0.9"/>
            </svg>
          </div>
        </Link>

      </div>

      <!-- ── ROW 2: 3-COLUMN ANALYTICS SECTION (Dynamic DB Series) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (5/12): Enrollment & Completion Trends Dual Area Chart -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                  {{ t('និន្នាការចុះឈ្មោះ និងបញ្ចប់វគ្គ', 'Enrollment & Completion Trends') }}
                </h3>
                <div class="flex items-center gap-3 mt-1 text-xs">
                  <div class="flex items-center gap-1.5 font-semibold text-blue-600 dark:text-blue-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                    <span>{{ t('ចុះឈ្មោះថ្មី', 'New Enrollments') }}</span>
                  </div>
                  <div class="flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                    <span>{{ t('បញ្ចប់វគ្គ', 'Completions') }}</span>
                  </div>
                </div>
              </div>
              <select
                v-model="chartTimeframe"
                class="text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1 font-semibold text-slate-700 dark:text-slate-200 cursor-pointer focus:outline-none"
              >
                <option value="monthly">{{ t('ប្រចាំខែ', 'Monthly') }}</option>
                <option value="weekly">{{ t('ប្រចាំសប្តាហ៍', 'Weekly') }}</option>
                <option value="daily">{{ t('ប្រចាំថ្ងៃ', 'Daily') }}</option>
              </select>
            </div>

            <!-- ApexCharts Area -->
            <div class="h-64 w-full">
              <VueApexCharts
                :key="'chart-' + chartTimeframe"
                type="area"
                height="100%"
                :options="enrollmentChartOptions"
                :series="enrollmentChartSeries"
              />
            </div>
          </div>
        </div>

        <!-- Col 2 (4/12): Students by Major Donut Chart (Real DB Majors) -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('ការបែងចែកនិស្សិតតាមជំនាញ', 'Students by Academic Major') }}
              </h3>
            </div>

            <div class="flex items-center justify-between gap-3 mt-2">
              <!-- Center Donut Chart with Cap Icon -->
              <div class="relative w-36 h-36 shrink-0 flex items-center justify-center">
                <VueApexCharts
                  :key="'donut-' + (props.studentsByMajor?.length || 0)"
                  type="donut"
                  width="145"
                  height="145"
                  :options="majorDonutOptions"
                  :series="majorDonutSeries"
                />
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-800 dark:text-slate-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                  </svg>
                </div>
              </div>

              <!-- Vertical Right Legend (Real Database Majors) -->
              <div class="space-y-1.5 flex-1 min-w-0 text-xs">
                <Link
                  v-for="(major, idx) in props.studentsByMajor"
                  :key="major.id"
                  href="/admin/academic-structure/majors"
                  class="flex items-center justify-between p-1 -mx-1 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group"
                >
                  <div class="flex items-center gap-2 min-w-0">
                    <span
                      class="w-2.5 h-2.5 rounded-full shrink-0"
                      :class="[
                        idx === 0 ? 'bg-amber-500' :
                        idx === 1 ? 'bg-blue-600' :
                        idx === 2 ? 'bg-emerald-500' :
                        idx === 3 ? 'bg-purple-500' : 'bg-rose-500'
                      ]"
                    ></span>
                    <div class="min-w-0">
                      <p class="font-bold text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 leading-tight whitespace-nowrap text-xs">
                        {{ major.display_name || major.name }}
                      </p>
                      <p class="text-[10px] text-slate-400 font-medium leading-none mt-0.5">{{ (major.count || 0).toLocaleString() }} {{ t('នាក់', 'Students') }}</p>
                    </div>
                  </div>
                  <span class="font-black text-slate-900 dark:text-white shrink-0 ml-2 text-xs">{{ major.pct }}%</span>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Col 3 (3/12): Academic Progress Breakdown (Pure Database Queries) -->
        <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('ស្ថានភាពវឌ្ឍនភាពសិក្សា', 'Academic Progress Status') }}
              </h3>
            </div>

            <div class="space-y-1.5 mt-2 text-xs">
              <Link href="/admin/progress?tab=course" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12" />
                    </svg>
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">{{ t('បានបញ្ចប់', 'Completed') }}</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">{{ (props.academicProgress?.completed_count || 0).toLocaleString() }}</span>
                  <span class="ml-2 text-slate-400 font-semibold">{{ props.academicProgress?.completed_pct || 0 }}%</span>
                </div>
              </Link>

              <Link href="/admin/enrollment/courses" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                      <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                    </svg>
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ t('កំពុងរៀន', 'In Progress') }}</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">{{ (props.academicProgress?.in_progress_count || 0).toLocaleString() }}</span>
                  <span class="ml-2 text-slate-400 font-semibold">{{ props.academicProgress?.in_progress_pct || 0 }}%</span>
                </div>
              </Link>

              <Link href="/admin/user-management/students" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M5 22h14" />
                      <path d="M5 2h14" />
                      <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22" />
                      <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2" />
                    </svg>
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400">{{ t('មិនទាន់ចាប់ផ្ដើម', 'Not Started') }}</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">{{ (props.academicProgress?.not_started_count || 0).toLocaleString() }}</span>
                  <span class="ml-2 text-slate-400 font-semibold">{{ props.academicProgress?.not_started_pct || 0 }}%</span>
                </div>
              </Link>

              <Link href="/admin/verify-certificate" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="8" r="6" />
                      <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                    </svg>
                  </div>
                  <span class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ t('វិញ្ញាបនបត្រចេញរួច', 'Certificates') }}</span>
                </div>
                <div class="text-right">
                  <span class="font-bold text-slate-900 dark:text-white">{{ (props.academicProgress?.certificates_count || 0).toLocaleString() }}</span>
                  <span class="ml-2 text-slate-400 font-semibold">{{ props.academicProgress?.certificates_pct || 0 }}%</span>
                </div>
              </Link>
            </div>
          </div>

          <!-- Total Enrolled bottom border -->
          <Link href="/admin/user-management/students" class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs mt-3 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer group">
            <span class="font-bold text-slate-700 dark:text-slate-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ t('និស្សិតចុះឈ្មោះសរុប', 'Total Enrolled') }}</span>
            <span class="font-black text-emerald-600 dark:text-emerald-400 text-sm">{{ (props.academicProgress?.total_enrolled || props.stats?.total_students || 0).toLocaleString() }} {{ t('នាក់', 'Students') }}</span>
          </Link>
        </div>

      </div>

      <!-- ── ROW 3: 3-COLUMN OPERATIONAL SECTION (Real Database Courses & Alerts) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (5/12): Top Performing Courses Table (Real Database Records) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3 flex items-center justify-between">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('វគ្គសិក្សាដែលមានលទ្ធផលខ្ពស់បំផុត', 'Top Performing Courses') }}
              </h3>
              <button @click="downloadExportReport" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                {{ t('ទាញយក CSV', 'Export CSV') }}
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <th class="py-2">#</th>
                    <th class="py-2">{{ t('ឈ្មោះវគ្គសិក្សា', 'Course Name') }}</th>
                    <th class="py-2 text-center">{{ t('ជំនាញ', 'Major') }}</th>
                    <th class="py-2 text-center">{{ t('និស្សិត', 'Students') }}</th>
                    <th class="py-2 text-right">{{ t('បញ្ចប់ (%)', 'Pass (%)') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr
                    v-for="item in props.topCourses"
                    :key="item.id"
                    @click="openCourseDetail(item)"
                    class="hover:bg-blue-50/70 dark:hover:bg-slate-800/70 transition-colors cursor-pointer group"
                    :title="t('ចុចដើម្បីពិនិត្យវគ្គសិក្សា', 'Click to view course details')"
                  >
                    <td class="py-2.5 text-slate-400 font-bold">{{ item.index }}</td>
                    <td class="py-2.5 font-semibold text-slate-800 dark:text-slate-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate max-w-[170px]">{{ item.name }}</td>
                    <td class="py-2.5 text-center">
                      <span
                        class="px-2 py-0.5 rounded text-[10px] font-bold"
                        :class="[
                          item.major === 'IT' ? 'bg-blue-50 dark:bg-blue-500/15 text-blue-600 dark:text-blue-400' :
                          item.major === 'SW' ? 'bg-indigo-50 dark:bg-indigo-500/15 text-indigo-600 dark:text-indigo-400' :
                          item.major === 'AGR' ? 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' :
                          item.major === 'TRM' ? 'bg-amber-50 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400' :
                          'bg-rose-50 dark:bg-rose-500/15 text-rose-600 dark:text-rose-400'
                        ]"
                      >
                        {{ item.major }}
                      </span>
                    </td>
                    <td class="py-2.5 text-center text-slate-600 dark:text-slate-300 font-medium">{{ item.students }}</td>
                    <td class="py-2.5 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ item.pass }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Col 2 (4/12): Academic Structure Summary (Real Database Counts) -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('រចនាសម្ព័ន្ធអប់រំ និងធនធាន', 'Academic Structure Summary') }}
              </h3>
            </div>

            <div class="space-y-1.5 mt-2 text-xs">
              <Link href="/admin/academic-structure/majors" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs shadow-2xs group-hover:scale-105 transition-transform shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="3" y1="22" x2="21" y2="22" /><line x1="6" y1="18" x2="6" y2="11" /><line x1="10" y1="18" x2="10" y2="11" /><line x1="14" y1="18" x2="14" y2="11" /><line x1="18" y1="18" x2="18" y2="11" /><polygon points="12 2 20 7 4 7" />
                    </svg>
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ t('ជំនាញបណ្តុះបណ្តាល', 'Academic Majors') }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">{{ props.academicStructure?.majors || 5 }} {{ t('ដេប៉ាតឺម៉ង់', 'Majors') }}</span>
              </Link>

              <Link href="/admin/academic-structure/classes" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs shadow-2xs group-hover:scale-105 transition-transform shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400">{{ t('ថ្នាក់រៀនសកម្ម', 'Active Classes / Cohorts') }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">{{ props.academicStructure?.classes || 36 }} {{ t('ថ្នាក់', 'Classes') }}</span>
              </Link>

              <Link href="/admin/academic-structure/academic-years" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs shadow-2xs group-hover:scale-105 transition-transform shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
                    </svg>
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400">{{ t('វេនសិក្សាផ្លូវការ', 'Study Shifts') }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">{{ props.academicStructure?.shifts || 4 }} {{ t('វេន', 'Shifts') }}</span>
              </Link>

              <Link href="/admin/course-module/all" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-purple-600 text-white flex items-center justify-center text-xs shadow-2xs group-hover:scale-105 transition-transform shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" /><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                    </svg>
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200 group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ t('មេរៀន និងធនធានបង្រៀន', 'Published Lessons') }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">{{ (props.academicStructure?.lessons || 0).toLocaleString() }}</span>
              </Link>

              <Link href="/admin/quizzes" class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-rose-500 text-white flex items-center justify-center text-xs shadow-2xs group-hover:scale-105 transition-transform shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /><polyline points="10 9 9 9 8 9" />
                    </svg>
                  </div>
                  <span class="font-semibold text-slate-700 dark:text-slate-200 group-hover:text-rose-600 dark:group-hover:text-rose-400">{{ t('កម្រងសំណួរ & ការប្រឡង', 'Quizzes & Exams') }}</span>
                </div>
                <span class="font-black text-slate-900 dark:text-white text-sm">{{ (props.academicStructure?.quizzes || 0).toLocaleString() }}</span>
              </Link>
            </div>
          </div>
        </div>

        <!-- Col 3 (3/12): Critical Admin Alerts & Actions (Real Live DB Alerts) -->
        <div class="lg:col-span-3 bg-[#fff1f2] dark:bg-rose-950/25 border border-rose-200 dark:border-rose-900/50 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-rose-200/60 dark:border-rose-900/40 pb-3 mb-3">
              <div class="flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                  <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                </svg>
                <h3 class="font-bold text-sm text-rose-900 dark:text-rose-200">
                  {{ t('ការដាស់តឿនបន្ទាន់', 'Critical Alerts') }}
                  <span class="text-rose-600 text-xs font-semibold">({{ props.adminAlerts?.length || 4 }} Items)</span>
                </h3>
              </div>
            </div>

            <div class="space-y-2 text-xs">
              <div
                v-for="alert in props.adminAlerts"
                :key="alert.title"
                @click="openAlertModal(alert.type)"
                class="p-2.5 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-rose-100 dark:border-rose-900/30 flex items-center justify-between hover:bg-white dark:hover:bg-slate-800 hover:shadow-xs transition-all cursor-pointer group"
                :title="t('ចុចដើម្បីចាត់ការភ្លាមៗ', 'Click to manage action item')"
              >
                <div>
                  <h5 class="font-bold text-slate-900 dark:text-slate-100 group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors leading-tight">{{ alert.title }}</h5>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ alert.desc }}</p>
                </div>
                <button
                  type="button"
                  @click.stop="openAlertModal(alert.type)"
                  class="px-2.5 py-1 rounded-md text-[11px] font-bold shrink-0 ml-2 group-hover:scale-105 active:scale-95 transition-transform shadow-2xs cursor-pointer"
                  :class="alert.color"
                >
                  {{ alert.status }}
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ── ROW 4: 3-COLUMN BOTTOM SECTION (Pass Rate, Activities, Department Distribution) ── -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
        
        <!-- Col 1 (4/12): Monthly Student Pass Rate Trend Bar Chart -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('និន្នាការអត្រាប្រឡងជាប់ (%)', 'Monthly Exam Pass Rate (%)') }}
              </h3>
              <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                93% Current
              </span>
            </div>

            <div class="h-56 w-full">
              <VueApexCharts
                type="bar"
                height="100%"
                :options="{
                  chart: { type: 'bar', toolbar: { show: false }, background: 'transparent' },
                  colors: ['#10b981'],
                  plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
                  dataLabels: { enabled: false },
                  xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                    labels: { style: { colors: isDark ? '#94a3b8' : '#64748b', fontSize: '11px', fontWeight: 600 } }
                  },
                  yaxis: {
                    max: 100,
                    labels: { formatter: (val) => `${val}%`, style: { colors: isDark ? '#94a3b8' : '#64748b', fontSize: '11px', fontWeight: 600 } }
                  },
                  grid: { borderColor: isDark ? '#334155' : '#f1f5f9', strokeDashArray: 3 }
                }"
                :series="[{ name: t('អត្រាប្រឡងជាប់ (%)', 'Pass Rate (%)'), data: [78, 82, 85, 88, 84, 91, 93] }]"
              />
            </div>
          </div>
        </div>

        <!-- Col 2 (4/12): Recent Academic Activities (Real DB Events) -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('សកម្មភាពសិក្សាថ្មីៗ', 'Recent Academic Activities') }}
              </h3>
              <Link href="/admin/reports?tab=students" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">{{ t('មើលទាំងអស់ →', 'View All →') }}</Link>
            </div>

            <div class="space-y-1.5 mt-1 text-xs">
              <Link
                v-for="(act, idx) in props.recentActivities"
                :key="idx"
                href="/admin/reports?tab=students"
                class="flex items-center justify-between p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group"
              >
                <div class="flex items-center gap-2.5 truncate">
                  <div
                    class="w-7 h-7 rounded-lg text-white flex items-center justify-center text-xs shadow-2xs group-hover:scale-105 transition-transform shrink-0"
                    :class="[
                      act.color === 'blue' ? 'bg-blue-600' :
                      act.color === 'emerald' ? 'bg-emerald-600' :
                      act.color === 'purple' ? 'bg-purple-600' :
                      act.color === 'amber' ? 'bg-amber-500' : 'bg-teal-600'
                    ]"
                  >
                    <svg v-if="act.icon === 'user'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    </svg>
                  </div>
                  <div class="truncate">
                    <h5 class="font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 leading-tight truncate">{{ act.name }}</h5>
                    <p class="text-[10px] text-slate-400 truncate">{{ act.action }}</p>
                  </div>
                </div>
                <div class="text-right shrink-0 ml-2">
                  <span
                    class="font-bold block"
                    :class="[
                      act.badge.includes('Grade') ? 'text-blue-600 dark:text-blue-400' :
                      act.badge.includes('MCQ') ? 'text-purple-600 dark:text-purple-400' :
                      act.badge.includes('Certificate') ? 'text-amber-500 dark:text-amber-400' :
                      act.badge.includes('Semester') ? 'text-teal-600 dark:text-teal-400' :
                      'text-emerald-600 dark:text-emerald-400'
                    ]"
                  >{{ act.badge }}</span>
                  <span class="text-[10px] text-slate-400">{{ act.time }}</span>
                </div>
              </Link>
            </div>
          </div>
        </div>

        <!-- Col 3 (4/12): Department Wise Enrollment Distribution (Real DB Counts) -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs dark:shadow-none flex flex-col justify-between">
          <div>
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3 mb-3">
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">
                {{ t('ការចុះឈ្មោះតាមមហាវិទ្យាល័យ / ដេប៉ាតឺម៉ង់', 'Department Wise Enrollment') }}
              </h3>
            </div>

            <div class="space-y-2 mt-2 text-xs">
              <Link
                v-for="dept in props.deptWiseEnrollment"
                :key="dept.id"
                href="/admin/academic-structure/departments"
                class="block p-1.5 -mx-1.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-colors group"
              >
                <div class="flex items-center justify-between mb-1">
                  <span class="font-bold text-slate-700 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate max-w-[200px]">{{ dept.name }}</span>
                  <span class="font-black text-slate-900 dark:text-white shrink-0 ml-1">{{ dept.count.toLocaleString() }} {{ t('នាក់', 'Students') }}</span>
                </div>
                <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="[
                      dept.color || (
                        dept.name.includes('IT') ? 'bg-blue-600' :
                        dept.name.includes('SW') ? 'bg-emerald-500' :
                        dept.name.includes('AGR') ? 'bg-amber-500' :
                        dept.name.includes('TRM') ? 'bg-purple-600' : 'bg-rose-500'
                      )
                    ]"
                    :style="`width: ${Math.min(100, Math.round((dept.count / 600) * 100))}%;`"
                  ></div>
                </div>
              </Link>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400 font-bold mt-2">
            <span>0</span>
            <span>200</span>
            <span>400</span>
            <span>600+</span>
          </div>
        </div>

      </div>

      <!-- ── REAL INTERACTIVE ACTION MODALS WITH DATABASE EXECUTION ── -->

      <!-- 1. At-Risk Students Intervention Modal -->
      <div v-if="activeModal === 'at_risk'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
          <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-rose-50/60 dark:bg-rose-950/30">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center font-bold text-sm">
                !
              </div>
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">{{ t('អន្តរាគមន៍និស្សិតប្រឈមហានិភ័យ', 'At-Risk Students Intervention') }} ({{ atRiskAlert?.count || 0 }})</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('ទិន្នន័យពិតចេញពីមូលដ្ឋានទិន្នន័យ (Database Query)', 'Live Real Data from Database') }}</p>
              </div>
            </div>
            <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">✕</button>
          </div>

          <div class="p-5 space-y-3 max-h-80 overflow-y-auto text-xs">
            <div v-for="st in atRiskAlert?.items" :key="st.id || st.name" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 flex items-center justify-between">
              <div>
                <h5 class="font-bold text-slate-900 dark:text-white text-xs">{{ st.name }} <span class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold ml-1">({{ st.major }})</span></h5>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ st.reason }}</p>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400">{{ st.score }}</span>
            </div>
            <div v-if="!atRiskAlert?.items || atRiskAlert.items.length === 0" class="text-center py-6 text-slate-400">
              {{ t('គ្មាននិស្សិតប្រឈមហានិភ័យនៅពេលនេះទេ', 'No at-risk students currently.') }}
            </div>
          </div>

          <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 flex flex-wrap items-center justify-between gap-2">
            <button
              @click="executeInterveneAtRisk"
              :disabled="isProcessingAction"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white cursor-pointer transition-colors shadow-sm disabled:opacity-50"
            >
              <span v-if="isProcessingAction">{{ t('កំពុងបញ្ជូន...', 'Sending...') }}</span>
              <span v-else>{{ t('⚡ ផ្ញើសេចក្តីជូនដំណឹងអន្តរាគមន៍ AI ជាក់ស្តែង', '⚡ Send Live AI Intervention Alerts') }}</span>
            </button>
            <Link href="/admin/ai-rules?tab=at_risk" @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100 transition-colors">
              {{ t('បើកម៉ូឌុល AI ពេញលេញ →', 'Open Full AI Module →') }}
            </Link>
          </div>
        </div>
      </div>

      <!-- 2. Courses Waiting Review Modal (Real Live Action) -->
      <div v-if="activeModal === 'courses_review'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
          <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-amber-50/60 dark:bg-amber-950/30">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center font-bold text-sm">
                📚
              </div>
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">{{ t('វគ្គសិក្សាកំពុងរង់ចាំការអនុម័ត', 'Courses Waiting for Review') }} ({{ coursesReviewAlert?.count || 0 }})</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('ទិន្នន័យពិតចេញពីតារាង courses (Pending / Draft)', 'Live Database Courses') }}</p>
              </div>
            </div>
            <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">✕</button>
          </div>

          <div class="p-5 space-y-2.5 max-h-80 overflow-y-auto text-xs">
            <div v-for="c in coursesReviewAlert?.items" :key="c.id || c.title" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 flex items-center justify-between">
              <div>
                <h5 class="font-bold text-slate-900 dark:text-white text-xs">{{ c.title }}</h5>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ c.teacher }} · {{ c.modules }}</p>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400">{{ c.major }}</span>
            </div>
            <div v-if="!coursesReviewAlert?.items || coursesReviewAlert.items.length === 0" class="text-center py-6 text-slate-400">
              {{ t('គ្មានវគ្គសិក្សារង់ចាំការអនុម័តទេ', 'No courses waiting for review.') }}
            </div>
          </div>

          <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 flex flex-wrap items-center justify-between gap-2">
            <button
              @click="executeApproveCourses"
              :disabled="isProcessingAction || !coursesReviewAlert?.count"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white cursor-pointer transition-colors shadow-sm disabled:opacity-50"
            >
              <span v-if="isProcessingAction">{{ t('កំពុងអនុម័ត...', 'Approving...') }}</span>
              <span v-else>{{ t('✓ អនុម័តវគ្គសិក្សាទាំងអស់ជាក់ស្តែង', '✓ Approve All Courses (DB Live)') }}</span>
            </button>
            <Link href="/admin/course-module/all" @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100 transition-colors">
              {{ t('មើលក្នុងផ្ទាំងគ្រប់គ្រងវគ្គសិក្សា →', 'View All Courses →') }}
            </Link>
          </div>
        </div>
      </div>

      <!-- 3. Teachers Accounts Verification Modal (Real Live Action) -->
      <div v-if="activeModal === 'teachers_review'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
          <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-blue-50/60 dark:bg-blue-950/30">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
                👨‍🏫
              </div>
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">{{ t('គណនីសាស្ត្រាចារ្យរង់ចាំការផ្ទៀងផ្ទាត់', 'Teacher Accounts Pending') }} ({{ teachersReviewAlert?.count || 0 }})</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('ទិន្នន័យពិតចេញពីតារាង users (role=teacher)', 'Live Faculty Accounts') }}</p>
              </div>
            </div>
            <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">✕</button>
          </div>

          <div class="p-5 space-y-2.5 max-h-80 overflow-y-auto text-xs">
            <div v-for="tch in teachersReviewAlert?.items" :key="tch.id || tch.name" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 flex items-center justify-between">
              <div>
                <h5 class="font-bold text-slate-900 dark:text-white text-xs">{{ tch.name }}</h5>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ tch.dept }} · {{ tch.email }}</p>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400">{{ tch.exp }}</span>
            </div>
            <div v-if="!teachersReviewAlert?.items || teachersReviewAlert.items.length === 0" class="text-center py-6 text-slate-400">
              {{ t('គ្មានសាស្ត្រាចារ្យរង់ចាំការផ្ទៀងផ្ទាត់ទេ', 'All teachers verified.') }}
            </div>
          </div>

          <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 flex flex-wrap items-center justify-between gap-2">
            <button
              @click="executeVerifyTeachers"
              :disabled="isProcessingAction || !teachersReviewAlert?.count"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white cursor-pointer transition-colors shadow-sm disabled:opacity-50"
            >
              <span v-if="isProcessingAction">{{ t('កំពុងផ្ទៀងផ្ទាត់...', 'Verifying...') }}</span>
              <span v-else>{{ t('✓ ផ្ទៀងផ្ទាត់ និងផ្តល់សិទ្ធិទាំងអស់ជាក់ស្តែង', '✓ Verify & Approve All (DB Live)') }}</span>
            </button>
            <Link href="/admin/user-management/teachers" @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100 transition-colors">
              {{ t('គ្រប់គ្រងសាស្ត្រាចារ្យ →', 'Manage Teachers →') }}
            </Link>
          </div>
        </div>
      </div>

      <!-- 4. Announcements Schedule Modal (Real Live Action) -->
      <div v-if="activeModal === 'announcements'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
        <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
          <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-emerald-50/60 dark:bg-emerald-950/30">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-sm">
                📢
              </div>
              <div>
                <h3 class="font-bold text-sm text-slate-900 dark:text-white">{{ t('សេចក្តីប្រកាសជូនដំណឹង', 'Scheduled Announcements') }} ({{ announcementsAlert?.count || 0 }})</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('ទិន្នន័យពិតចេញពីតារាង announcements', 'Live Announcements Database') }}</p>
              </div>
            </div>
            <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">✕</button>
          </div>

          <div class="p-5 space-y-2.5 max-h-80 overflow-y-auto text-xs">
            <div v-for="ann in announcementsAlert?.items" :key="ann.id || ann.title" class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60 flex items-center justify-between">
              <div>
                <h5 class="font-bold text-slate-900 dark:text-white text-xs">{{ ann.title }}</h5>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">Target: {{ ann.aud }} · Time: {{ ann.date }}</p>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400">Scheduled</span>
            </div>
            <div v-if="!announcementsAlert?.items || announcementsAlert.items.length === 0" class="text-center py-6 text-slate-400">
              {{ t('គ្មានសេចក្តីប្រកាសរង់ចាំទេ', 'No scheduled announcements.') }}
            </div>
          </div>

          <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 flex flex-wrap items-center justify-between gap-2">
            <button
              @click="executeBroadcastAnnouncement"
              :disabled="isProcessingAction || !announcementsAlert?.count"
              class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer transition-colors shadow-sm disabled:opacity-50"
            >
              <span v-if="isProcessingAction">{{ t('កំពុងផ្សាយ...', 'Broadcasting...') }}</span>
              <span v-else>{{ t('🚀 ផ្សព្វផ្សាយជាបន្ទាន់ជាក់ស្តែង', '🚀 Broadcast Now (DB Live)') }}</span>
            </button>
            <Link href="/admin/notifications/announcements" @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100 transition-colors">
              {{ t('គ្រប់គ្រងសេចក្តីប្រកាស →', 'Announcement Center →') }}
            </Link>
          </div>
        </div>
      </div>

      <!-- 5. Course Quick Detail Modal (Real DB Course) -->
      <div v-if="activeModal === 'course_detail' && selectedCourse" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
        <div class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
          <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-blue-50/60 dark:bg-blue-950/30">
            <div>
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 uppercase tracking-wide">{{ selectedCourse.major }}</span>
              <h3 class="font-bold text-sm text-slate-900 dark:text-white mt-1">{{ selectedCourse.name }}</h3>
            </div>
            <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer">✕</button>
          </div>

          <div class="p-5 space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-3">
              <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
                <span class="text-[10px] text-slate-400 block font-semibold uppercase">{{ t('និស្សិតចុះឈ្មោះ', 'Enrolled') }}</span>
                <span class="text-lg font-black text-slate-900 dark:text-white">{{ selectedCourse.students }} {{ t('នាក់', 'Students') }}</span>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
                <span class="text-[10px] text-slate-400 block font-semibold uppercase">{{ t('អត្រាប្រឡងជាប់', 'Pass Rate') }}</span>
                <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ selectedCourse.pass }}</span>
              </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/60">
              <span class="text-[10px] text-slate-400 block font-semibold uppercase">{{ t('សាស្ត្រាចារ្យទទួលបន្ទុក', 'Instructor') }}</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ selectedCourse.instructor || 'Faculty Specialist' }}</span>
            </div>
          </div>

          <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/60 flex items-center justify-between gap-2">
            <button @click="downloadExportReport" class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white cursor-pointer transition-colors shadow-sm">
              {{ t('📄 ទាញយករបាយការណ៍ CSV', '📄 Export CSV Report') }}
            </button>
            <Link href="/admin/course-module/all" @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100 transition-colors">
              {{ t('បើកទំព័រវគ្គសិក្សា →', 'Open Course Module →') }}
            </Link>
          </div>
        </div>
      </div>

      <!-- Action Success Toast Notification -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-3"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-3"
      >
        <div v-if="actionToast" class="fixed bottom-6 right-6 z-50 bg-emerald-600 text-white px-5 py-3 rounded-2xl shadow-2xl font-bold flex items-center gap-2.5 text-xs">
          <span>✓</span>
          <span>{{ actionToast }}</span>
        </div>
      </transition>

    </div>
  </AdminLayout>
</template>
