<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { i18n } from '@/Services/i18n'
import GlobalToast from '@/Components/GlobalToast.vue'
import OfficialVerifiedBadge from '@/Components/OfficialVerifiedBadge.vue'

const logoUrl = '/images/logo.png'
const actionBtnIcon = '/images/actions/action-button.svg'

const props = defineProps<{ title?: string }>()

const page = usePage<any>()
const user = computed(() => page.props.auth?.user || {})

const sidebarOpen = ref(false)
const expandedModules = ref<Record<string, boolean>>({
  auth: false,
  users: false,
  academics: false,
  courses: false,
  enrollment: false,
  payment: false,
  content: false,
  quiz: false,
  progress: false,
  analytics: false,
  ai: false,
  certificate: false,
  notification: false,
  settings: false,
})

const toggleModule = (key: string) => {
  expandedModules.value[key] = !expandedModules.value[key]
}

interface NavSubItem {
  name: string
  href: string
  icon?: string
  iconUrl?: string
}

interface NavItem {
  key?: string
  name: string
  href?: string
  icon?: string
  iconUrl?: string
  badge?: string
  children?: NavSubItem[]
}

const navigation: NavItem[] = [
  {
    key: 'dashboard',
    name: 'Dashboard',
    href: '/admin/dashboard',
    iconUrl: '/images/nav/dashboard.svg',
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
  },
  {
    key: 'auth',
    name: 'Authentication Module',
    iconUrl: '/images/nav/auth.svg',
    icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    children: [
      { name: 'Overview Hub', href: '/admin/auth-logs', iconUrl: '/images/nav/sub/overview.svg', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
      { name: 'Roles & Permissions', href: '/admin/auth/roles', iconUrl: '/images/nav/sub/roles.svg', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
      { name: 'Active Sessions', href: '/admin/auth/sessions', iconUrl: '/images/nav/sub/sessions.svg', icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
      { name: 'Login History', href: '/admin/auth/history', iconUrl: '/images/nav/sub/history.svg', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'Failed Login Attempts', href: '/admin/auth/failed', iconUrl: '/images/nav/sub/failed.svg', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
      { name: 'Security Policies', href: '/admin/auth/policies', iconUrl: '/images/nav/sub/policies.svg', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
    ]
  },
  {
    key: 'users',
    name: 'User Management',
    iconUrl: '/images/nav/users.svg',
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    children: [
      { name: 'All Users', href: '/admin/user-management/all', iconUrl: '/images/nav/sub/all-users.svg', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
      { name: 'Administrators', href: '/admin/user-management/administrators', iconUrl: '/images/nav/sub/admins.svg', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
      { name: 'Teachers', href: '/admin/user-management/teachers', iconUrl: '/images/nav/sub/teachers.svg', icon: 'M12 14l9-5-9-5-9 5 9 5z' },
      { name: 'Students', href: '/admin/user-management/students', iconUrl: '/images/nav/sub/students.svg', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
      { name: 'Suspended Users', href: '/admin/user-management/suspended', iconUrl: '/images/nav/sub/suspended.svg', icon: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' },
      { name: 'Import / Export Users', href: '/admin/user-management/import-export', iconUrl: '/images/nav/sub/import-export.svg', icon: 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12' },
    ]
  },
  {
    key: 'academics',
    name: 'Academic Structure',
    iconUrl: '/images/nav/academics.svg',
    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 10V11m0 0h4m-4 0H7',
    children: [
      { name: 'Faculties', href: '/admin/academic-structure/faculties', iconUrl: '/images/nav/sub/faculties.svg', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11' },
      { name: 'Departments', href: '/admin/academic-structure/departments', iconUrl: '/images/nav/sub/departments.svg', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11' },
      { name: 'Majors', href: '/admin/academic-structure/majors', iconUrl: '/images/nav/sub/majors.svg', icon: 'M12 14l9-5-9-5-9 5 9 5z' },
      { name: 'Academic Years', href: '/admin/academic-structure/academic-years', iconUrl: '/images/nav/sub/academic-years.svg', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
      { name: 'Semesters', href: '/admin/academic-structure/semesters', iconUrl: '/images/nav/sub/semesters.svg', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    ]
  },
  {
    key: 'courses',
    name: 'Course Management',
    iconUrl: '/images/nav/courses.svg',
    icon: 'M12 14l9-5-9-5-9 5 9 5z',
    children: [
      { name: 'All Courses', href: '/admin/course-module/all', iconUrl: '/images/nav/sub/all-courses.svg', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
      { name: 'Subjects', href: '/admin/course-module/subjects', iconUrl: '/images/nav/sub/subjects.svg', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
      { name: 'Teacher Assignment', href: '/admin/course-module/teacher-assignments', iconUrl: '/images/nav/sub/teacher-assignments.svg', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
      { name: 'Teacher-Led Courses', href: '/admin/course-module/teacher-led', iconUrl: '/images/nav/sub/teacher-led.svg', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
      { name: 'Self-Study Courses', href: '/admin/course-module/self-study', iconUrl: '/images/nav/sub/self-study.svg', icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
      { name: 'Free Courses', href: '/admin/course-module/free', iconUrl: '/images/nav/sub/free-courses.svg', icon: 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5a2 2 0 10-2 2h2zm0 13C10.832 19.477 9.246 19 7.5 19S4.168 19.477 3 20.253V6.253C4.168 5.477 5.754 5 7.5 5s3.332.477 4.5 1.253m0 13C13.168 19.477 14.754 19 16.5 19c1.747 0 3.332.477 4.5 1.253V6.253C19.832 5.477 18.247 5 16.5 5c-1.746 0-3.332.477-4.5 1.253' },
      { name: 'Paid Courses', href: '/admin/course-module/paid', iconUrl: '/images/nav/sub/paid-courses.svg', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
    ]
  },
  {
    key: 'enrollment',
    name: 'Enrollment Management',
    iconUrl: '/images/nav/enrollment.svg',
    icon: 'M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122',
    children: [
      { name: 'Major Enrollments', href: '/admin/enrollment/majors', iconUrl: '/images/nav/sub/majors.svg', icon: 'M12 14l9-5-9-5-9 5 9 5z' },
      { name: 'Course Enrollments', href: '/admin/enrollment/courses', iconUrl: '/images/nav/sub/all-courses.svg', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
      { name: 'Single Enrollment', href: '/admin/enrollment/single', iconUrl: '/images/nav/sub/students.svg', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
      { name: 'Bulk Enrollment', href: '/admin/enrollment/bulk', iconUrl: '/images/nav/sub/import-export.svg', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
      { name: 'Enrollment History', href: '/admin/enrollment/history', iconUrl: '/images/nav/sub/history.svg', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    ]
  },
  {
    key: 'payment',
    name: 'Payment & ABA Management',
    iconUrl: '/images/nav/payment.svg',
    icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    children: [
      { name: 'Payment Dashboard', href: '/admin/payments', iconUrl: '/images/nav/payment.svg', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
      { name: 'Course Pricing', href: '/admin/courses?tab=pricing', iconUrl: '/images/nav/sub/paid-courses.svg', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'ABA Transactions', href: '/admin/payments?method=aba', iconUrl: '/images/actions/payment.svg', icon: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z' },
      { name: 'Receipt Verification', href: '/admin/payments?status=pending', iconUrl: '/images/nav/sub/roles.svg', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'Payment History', href: '/admin/payments?status=verified', iconUrl: '/images/nav/sub/history.svg', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'Refunds', href: '/admin/payments?status=refunded', iconUrl: '/images/nav/sub/suspended.svg', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
      { name: 'Revenue Reports', href: '/admin/payments?tab=revenue', iconUrl: '/images/nav/analytics.svg', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    ]
  },
  {
    key: 'content',
    name: 'Content Delivery Module',
    iconUrl: '/images/nav/content.svg',
    icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
    children: [
      { name: 'Content Library', href: '/admin/content', iconUrl: '/images/nav/content.svg', icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4' },
      { name: 'Videos', href: '/admin/content?type=video', iconUrl: '/images/nav/sub/teacher-led.svg', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
      { name: 'PDFs', href: '/admin/content?type=pdf', iconUrl: '/images/nav/sub/policies.svg', icon: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z' },
      { name: 'Slides', href: '/admin/content?type=slides', iconUrl: '/images/nav/sub/self-study.svg', icon: 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12H4z' },
      { name: 'Notes/Documents', href: '/admin/content?type=notes', iconUrl: '/images/nav/sub/subjects.svg', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
      { name: 'Modules & Chapters', href: '/admin/content?tab=modules', iconUrl: '/images/nav/sub/all-courses.svg', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
      { name: 'Offline Content', href: '/admin/content?tab=offline', iconUrl: '/images/nav/sub/import-export.svg', icon: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' },
    ]
  },
  {
    key: 'quiz',
    name: 'Quiz & Assessment Module',
    iconUrl: '/images/nav/quiz.svg',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    children: [
      { name: 'Question Bank', href: '/admin/quizzes?tab=bank', iconUrl: '/images/nav/sub/overview.svg', icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'All Quizzes', href: '/admin/quizzes', iconUrl: '/images/nav/quiz.svg', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
      { name: 'Pre-Tests', href: '/admin/quizzes?type=pre_test', iconUrl: '/images/nav/sub/semesters.svg', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
      { name: 'Practice Quizzes', href: '/admin/quizzes?type=practice', iconUrl: '/images/nav/sub/subjects.svg', icon: 'M11 4a2 2 0 114 0v1a2 2 0 01-2 2 2 2 0 01-2-2V4zm-6 8a2 2 0 114 0v1a2 2 0 01-2 2 2 2 0 01-2-2v-1zm12 0a2 2 0 114 0v1a2 2 0 01-2 2 2 2 0 01-2-2v-1z' },
      { name: 'Post-Tests', href: '/admin/quizzes?type=post_test', iconUrl: '/images/nav/sub/roles.svg', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'Assignments', href: '/admin/quizzes?tab=assignments', iconUrl: '/images/nav/sub/teacher-assignments.svg', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
      { name: 'Quiz Results', href: '/admin/quizzes?tab=results', iconUrl: '/images/nav/analytics.svg', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    ]
  },
  {
    key: 'progress',
    name: 'Progress Tracking Module',
    iconUrl: '/images/nav/progress.svg',
    icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
    children: [
      { name: 'Student Progress', href: '/admin/progress?tab=student', iconUrl: '/images/nav/sub/students.svg', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
      { name: 'Course Completion', href: '/admin/progress?tab=course', iconUrl: '/images/nav/sub/roles.svg', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'Module Completion', href: '/admin/progress?tab=module', iconUrl: '/images/nav/sub/all-courses.svg', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
      { name: 'Learning Time', href: '/admin/progress?tab=time', iconUrl: '/images/nav/sub/history.svg', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'At-Risk Students', href: '/admin/progress?tab=at_risk', iconUrl: '/images/nav/sub/failed.svg', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
      { name: 'Weekly Progress', href: '/admin/progress?tab=weekly', iconUrl: '/images/nav/progress.svg', icon: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' },
    ]
  },
  {
    key: 'analytics',
    name: 'Analytics & Reporting Module',
    iconUrl: '/images/nav/analytics.svg',
    icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    children: [
      { name: 'Overview', href: '/admin/reports?tab=overview', iconUrl: '/images/nav/sub/overview.svg', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
      { name: 'Student Analytics', href: '/admin/reports?tab=students', iconUrl: '/images/nav/sub/students.svg', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
      { name: 'Teacher Analytics', href: '/admin/reports?tab=teachers', iconUrl: '/images/nav/sub/teachers.svg', icon: 'M12 14l9-5-9-5-9 5 9 5z' },
      { name: 'Course Analytics', href: '/admin/reports?tab=courses', iconUrl: '/images/nav/sub/all-courses.svg', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
      { name: 'Quiz Analytics', href: '/admin/reports?tab=quizzes', iconUrl: '/images/nav/quiz.svg', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2' },
      { name: 'Payment Analytics', href: '/admin/reports?tab=payments', iconUrl: '/images/nav/payment.svg', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
      { name: 'Export Reports', href: '/admin/reports?tab=export', iconUrl: '/images/nav/sub/import-export.svg', icon: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' },
    ]
  },
  {
    key: 'ai',
    name: 'AI Recommendation Module',
    iconUrl: '/images/nav/ai.svg',
    icon: 'M13 10V3L4 14h7v7l9-11h-7z',
    children: [
      { name: 'AI Rules', href: '/admin/ai-rules?tab=rules', iconUrl: '/images/nav/ai.svg', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
      { name: 'Weak Topic Rules', href: '/admin/ai-rules?tab=weak_topics', iconUrl: '/images/nav/sub/failed.svg', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
      { name: 'Learning Path Rules', href: '/admin/ai-rules?tab=learning_paths', iconUrl: '/images/nav/sub/majors.svg', icon: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.82V8.056a1 1 0 00-1.447-.894L15 7m0 10V7m0 0L9 4' },
      { name: 'Recommendation Logs', href: '/admin/ai-rules?tab=logs', iconUrl: '/images/nav/sub/policies.svg', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2' },
      { name: 'AI Configuration', href: '/admin/ai-rules?tab=config', iconUrl: '/images/nav/settings.svg', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
      { name: 'Student View Preview', href: '/admin/ai-rules?tab=student_view', iconUrl: '/images/nav/sub/students.svg', icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' },
    ]
  },
  {
    key: 'certificate',
    name: 'Certificate Module',
    iconUrl: '/images/nav/certificate.svg',
    icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z',
    children: [
      { name: 'Templates', href: '/admin/certificates/templates', iconUrl: '/images/actions/certificate.svg', icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z' },
      { name: 'Issue Certificate', href: '/admin/certificates/issue', iconUrl: '/images/nav/certificate.svg', icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z' },
      { name: 'Issued Certificates', href: '/admin/certificates/issued', iconUrl: '/images/nav/sub/roles.svg', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'Certificate Verification', href: '/admin/certificates/verify', iconUrl: '/images/nav/sub/roles.svg', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
      { name: 'Revoked Certificates', href: '/admin/certificates/revoked', iconUrl: '/images/nav/sub/suspended.svg', icon: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' },
    ]
  },
  {
    key: 'notification',
    name: 'Notification Module',
    iconUrl: '/images/nav/notification.svg',
    icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    children: [
      { name: 'Announcements', href: '/admin/notifications/announcements', iconUrl: '/images/actions/announcement.svg', icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' },
      { name: 'Email Notifications', href: '/admin/notifications/emails', iconUrl: '/images/nav/sub/policies.svg', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
      { name: 'Push Notifications', href: '/admin/notifications/push', iconUrl: '/images/nav/notification.svg', icon: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z' },
      { name: 'Scheduled Notifications', href: '/admin/notifications/scheduled', iconUrl: '/images/nav/sub/history.svg', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'Notification History', href: '/admin/notifications/history', iconUrl: '/images/nav/sub/policies.svg', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2' },
    ]
  },
  {
    key: 'discussions',
    name: 'Discussions & Support',
    iconUrl: '/images/nav/discussions.svg',
    icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    children: [
      { name: 'Discussions', href: '/admin/discussions/board', iconUrl: '/images/nav/discussions.svg', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
      { name: 'Student Questions', href: '/admin/discussions/questions', iconUrl: '/images/nav/sub/overview.svg', icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
      { name: 'Support Tickets', href: '/admin/discussions/tickets', iconUrl: '/images/actions/action-button.svg', icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 001 1.732V11a2 2 0 00-1 1.732V17a2 2 0 002 2h14a2 2 0 002-2v-2.268A2 2 0 0021 13v-1.268A2 2 0 0020 10V7a2 2 0 00-2-2H5z' },
      { name: 'Reported Content', href: '/admin/discussions/reports', iconUrl: '/images/nav/sub/failed.svg', icon: 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9' },
    ]
  },
  {
    key: 'settings',
    name: 'Settings',
    iconUrl: '/images/nav/settings.svg',
    icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    children: [
      { name: 'General Settings', href: '/admin/settings', iconUrl: '/images/nav/settings.svg' },
      { name: 'Language & Localization', href: '/admin/settings?tab=language', iconUrl: '/images/flags/km.svg' },
      { name: 'Email / SMTP', href: '/admin/settings?tab=smtp', iconUrl: '/images/nav/sub/policies.svg' },
      { name: 'S3 Storage', href: '/admin/settings?tab=s3', iconUrl: '/images/nav/sub/import-export.svg' },
      { name: 'Video CDN', href: '/admin/settings?tab=cdn', iconUrl: '/images/nav/sub/teacher-led.svg' },
      { name: 'Redis / Queue', href: '/admin/settings?tab=redis', iconUrl: '/images/nav/sub/overview.svg' },
      { name: 'Reverb / Real-time', href: '/admin/settings?tab=reverb', iconUrl: '/images/nav/sub/sessions.svg' },
      { name: 'PWA & Offline Settings', href: '/admin/settings?tab=pwa', iconUrl: '/images/nav/sub/self-study.svg' },
      { name: 'ABA Payment Settings', href: '/admin/settings?tab=aba', iconUrl: '/images/actions/payment.svg' },
      { name: 'Backup & Restore', href: '/admin/settings?tab=backup', iconUrl: '/images/nav/sub/import-export.svg' },
      { name: 'System / Audit Logs', href: '/admin/auth-logs', iconUrl: '/images/nav/sub/policies.svg' },
    ]
  },
]

const isSubActive = (subHref: string) => {
  const currentUrl = page.url

  // If subHref has query param (e.g. /admin/payments?method=aba)
  if (subHref.includes('?')) {
    const [path, query] = subHref.split('?')
    if (!currentUrl.startsWith(path)) return false

    if (query.includes('tab=pricing')) return currentUrl.includes('tab=pricing')
    if (query.includes('method=aba')) return currentUrl.includes('method=aba') || currentUrl.includes('tab=transactions')
    if (query.includes('status=pending')) return currentUrl.includes('status=pending') || currentUrl.includes('tab=verification')
    if (query.includes('status=verified')) return currentUrl.includes('status=verified') || currentUrl.includes('tab=history')
    if (query.includes('status=refunded')) return currentUrl.includes('status=refunded') || currentUrl.includes('status=Refunds') || currentUrl.includes('tab=refunds')
    if (query.includes('tab=revenue')) return currentUrl.includes('tab=revenue')

    // Content Delivery Module matching
    if (query.includes('type=video')) return currentUrl.includes('type=video') || currentUrl.includes('tab=videos')
    if (query.includes('type=pdf')) return currentUrl.includes('type=pdf') || currentUrl.includes('tab=pdfs')
    if (query.includes('type=slides')) return currentUrl.includes('type=slides') || currentUrl.includes('tab=slides')
    if (query.includes('type=notes')) return currentUrl.includes('type=notes') || currentUrl.includes('tab=notes')
    if (query.includes('tab=modules')) return currentUrl.includes('tab=modules')
    if (query.includes('tab=offline')) return currentUrl.includes('tab=offline')

    return currentUrl.includes(query)
  }

  // Exact base route match (e.g. /admin/payments with no query or tab=dashboard)
  if (subHref === '/admin/payments') {
    return currentUrl === '/admin/payments' || currentUrl.includes('tab=dashboard') || (!currentUrl.includes('?') && currentUrl.startsWith('/admin/payments'))
  }
  if (subHref === '/admin/content') {
    return currentUrl === '/admin/content' || currentUrl.includes('tab=library') || (!currentUrl.includes('?') && currentUrl.startsWith('/admin/content'))
  }

  return currentUrl === subHref || (currentUrl.startsWith(subHref.split('?')[0]) && !subHref.includes('?'))
}

const isChildActive = (children?: NavSubItem[]) => {
  if (!children) return false
  return children.some(child => isSubActive(child.href))
}

const avatarInput = ref<HTMLInputElement | null>(null)
const isUploadingAvatar = ref(false)
const isSidebarCollapsed = ref(false)

const toggleSidebarCollapse = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
}

const triggerAvatarUpload = () => {
  avatarInput.value?.click()
}

const handleAvatarChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return

  const file = target.files[0]
  const formData = new FormData()
  formData.append('avatar', file)

  isUploadingAvatar.value = true

  router.post('/user/avatar', formData, {
    forceFormData: true,
    onSuccess: () => {
      isUploadingAvatar.value = false
    },
    onError: () => {
      isUploadingAvatar.value = false
    },
    onFinish: () => {
      isUploadingAvatar.value = false
    }
  })
}

const logout = () => {
  router.post('/logout')
}

const onIconError = (e: Event) => {
  const target = e.target as HTMLImageElement
  if (target) {
    target.style.display = 'none'
    const parent = target.parentElement
    if (parent) {
      const fallbackSvg = parent.querySelector('svg')
      if (fallbackSvg) {
        fallbackSvg.classList.remove('hidden')
        fallbackSvg.style.display = 'block'
      }
    }
  }
}

// --- Top Navbar State & Logic ---
const searchQuery = ref('')
const isSearchOpen = ref(false)
const isNotificationOpen = ref(false)
const isProfileOpen = ref(false)
const isQuickActionOpen = ref(false)
const isLangOpen = ref(false)
const isStatusOpen = ref(false)
const isFullscreen = ref(false)
const currentLang = computed(() => i18n.locale.value)

const isOnline = ref(typeof window !== 'undefined' ? window.navigator.onLine : true)
const manualStatusOverride = ref<boolean | null>(null)
const onlineIconUrl = '/images/nav/online.svg'
const offlineIconUrl = '/images/nav/offline.svg'

const updateOnlineStatus = () => {
  if (manualStatusOverride.value !== null) {
    isOnline.value = manualStatusOverride.value
  } else {
    isOnline.value = window.navigator.onLine
  }
}

const setStatusMode = (online: boolean) => {
  manualStatusOverride.value = online
  isOnline.value = online
  isStatusOpen.value = false
}

const languages = [
  { code: 'km', name: 'ភាសាខ្មែរ', flagUrl: '/images/flags/km.svg' },
  { code: 'en', name: 'English', flagUrl: '/images/flags/en.svg' },
]

const selectLanguage = (code: string) => {
  i18n.setLanguage(code as 'km' | 'en')
  isLangOpen.value = false
}

const currentBreadcrumb = computed(() => {
  const url = page.url
  if (url.startsWith('/admin/dashboard')) return ['Admin', 'Dashboard']
  if (url.startsWith('/admin/user-management')) return ['Admin', 'User Management']
  if (url.startsWith('/admin/academic-structure')) return ['Admin', 'Academic Structure']
  if (url.startsWith('/admin/course-module') || url.startsWith('/admin/courses')) return ['Admin', 'Course Management']
  if (url.startsWith('/admin/enrollment')) return ['Admin', 'Enrollment']
  if (url.startsWith('/admin/payments')) return ['Admin', 'Payment & ABA']
  if (url.startsWith('/admin/content')) return ['Admin', 'Content Delivery']
  if (url.startsWith('/admin/quizzes')) return ['Admin', 'Quiz & Assessment']
  if (url.startsWith('/admin/progress')) return ['Admin', 'Progress Tracking']
  if (url.startsWith('/admin/reports')) return ['Admin', 'Analytics & Reports']
  if (url.startsWith('/admin/ai-rules')) return ['Admin', 'AI Recommendation']
  if (url.startsWith('/admin/certificates')) return ['Admin', 'Certificate Module']
  if (url.startsWith('/admin/notifications')) return ['Admin', 'Notifications']
  if (url.startsWith('/admin/discussions')) return ['Admin', 'Discussions & Support']
  if (url.startsWith('/admin/settings')) return ['Admin', 'Settings']
  if (url.startsWith('/admin/auth')) return ['Admin', 'Authentication']
  return ['Admin', 'Overview']
})

const pageTitle = computed(() => {
  if (props.title) return props.title
  const crumb = currentBreadcrumb.value
  return crumb.length > 1 ? crumb[crumb.length - 1] : 'Admin Dashboard'
})

const quickActions = [
  { name: 'បង្កើតអ្នកប្រើប្រាស់ (Add User)', href: '/admin/user-management/all', iconUrl: '/images/actions/add-user.svg' },
  { name: 'បង្កើតវគ្គសិក្សា (Add Course)', href: '/admin/course-module/all', iconUrl: '/images/actions/add-course.svg' },
  { name: 'ផ្ទៀងផ្ទាត់ការបង់ប្រាក់ (Verify ABA)', href: '/admin/payments?status=pending', iconUrl: '/images/actions/payment.svg' },
  { name: 'ផ្ញើសារប្រកាស (Announcement)', href: '/admin/notifications/announcements', iconUrl: '/images/actions/announcement.svg' },
  { name: 'ចេញវិញ្ញាបនបត្រ (Issue Certificate)', href: '/admin/certificates/issue', iconUrl: '/images/actions/certificate.svg' }
]

const searchableLinks = computed(() => {
  const list: { name: string; category: string; href: string; icon?: string; iconUrl?: string }[] = []
  navigation.forEach(nav => {
    if (nav.children && nav.children.length > 0) {
      nav.children.forEach(sub => {
        list.push({
          name: sub.name,
          category: nav.name,
          href: sub.href,
          icon: sub.icon || nav.icon,
          iconUrl: sub.iconUrl || nav.iconUrl
        })
      })
    } else if (nav.href) {
      list.push({
        name: nav.name,
        category: 'Main Navigation',
        href: nav.href,
        icon: nav.icon,
        iconUrl: nav.iconUrl
      })
    }
  })
  return list
})

const filteredSearchResults = computed(() => {
  if (!searchQuery.value.trim()) return searchableLinks.value.slice(0, 7)
  const q = searchQuery.value.toLowerCase()
  return searchableLinks.value.filter(item =>
    item.name.toLowerCase().includes(q) ||
    item.category.toLowerCase().includes(q)
  )
})

const notifications = ref([
  {
    id: 1,
    title: 'ការទូទាត់ ABA #1094 ថ្មី',
    desc: 'សិស្ស សុខ ចាន់ បានផ្ញើប្រាក់ 45.00$ សម្រាប់វគ្គ Web Dev',
    time: '5 នាទីមុន',
    type: 'payment',
    read: false,
    link: '/admin/payments?status=pending'
  },
  {
    id: 2,
    title: 'ការចុះឈ្មោះសិស្សថ្មី',
    desc: 'គឹម ហុង បានចុះឈ្មោះចូលរៀនថ្នាក់វិទ្យាសាស្ត្រកុំព្យូទ័រ',
    time: '20 នាទីមុន',
    type: 'user',
    read: false,
    link: '/admin/user-management/students'
  },
  {
    id: 3,
    title: 'សំណើជំនួយ Ticket #402',
    desc: 'មានបញ្ហាមើលវីដេអូបទបង្ហាញក្នុងមេរៀនទី ២',
    time: '1 ម៉ោងមុន',
    type: 'support',
    read: false,
    link: '/admin/discussions/tickets'
  },
  {
    id: 4,
    title: 'ការបម្រុងទុកទិន្នន័យបានជោគជ័យ',
    desc: 'Automated Database Backup completed successfully',
    time: '3 ម៉ោងមុន',
    type: 'system',
    read: true,
    link: '/admin/settings?tab=backup'
  }
])

const unreadNotificationsCount = computed(() => {
  return notifications.value.filter(n => !n.read).length
})

const markAllAsRead = () => {
  notifications.value.forEach(n => n.read = true)
}

const markNotificationRead = (id: number) => {
  const item = notifications.value.find(n => n.id === id)
  if (item) item.read = true
}

const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(err => console.log(err))
    isFullscreen.value = true
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen()
      isFullscreen.value = false
    }
  }
}

const closeAllDropdowns = () => {
  isSearchOpen.value = false
  isNotificationOpen.value = false
  isProfileOpen.value = false
  isQuickActionOpen.value = false
  isLangOpen.value = false
  isStatusOpen.value = false
}

const toggleDropdown = (target: 'search' | 'notification' | 'profile' | 'quick' | 'lang' | 'status') => {
  const current = target === 'search' ? isSearchOpen.value
    : target === 'notification' ? isNotificationOpen.value
    : target === 'profile' ? isProfileOpen.value
    : target === 'quick' ? isQuickActionOpen.value
    : target === 'lang' ? isLangOpen.value
    : isStatusOpen.value

  closeAllDropdowns()

  if (target === 'search') isSearchOpen.value = !current
  else if (target === 'notification') isNotificationOpen.value = !current
  else if (target === 'profile') isProfileOpen.value = !current
  else if (target === 'quick') isQuickActionOpen.value = !current
  else if (target === 'lang') isLangOpen.value = !current
  else if (target === 'status') isStatusOpen.value = !current
}

const handleKeydown = (e: KeyboardEvent) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    toggleDropdown('search')
  } else if (e.key === 'Escape') {
    closeAllDropdowns()
  }
}

// Auto-expand active module when route changes or loads
watch(
  () => page.url,
  () => {
    navigation.forEach(item => {
      if (item.key && item.children && isChildActive(item.children)) {
        expandedModules.value[item.key] = true
      }
    })
  },
  { immediate: true }
)

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
})
</script>

<template>
  <Head :title="pageTitle" />
  <GlobalToast />
  <div class="min-h-screen bg-slate-900 text-slate-200 selection:bg-indigo-500/30">
    <!-- Sidebar for Desktop -->
    <div :class="[isSidebarCollapsed ? 'w-20 overflow-visible' : 'w-72', 'fixed inset-y-0 left-0 z-50 hidden flex-col bg-slate-900/90 backdrop-blur-xl border-r border-slate-800 lg:flex transition-all duration-300']">
      <!-- Header -->
      <div
        :class="[
          isSidebarCollapsed ? 'justify-center px-2' : 'justify-between px-4',
          'relative flex h-16 shrink-0 items-center border-b border-slate-800 transition-all duration-300 group/sidebar-header'
        ]"
      >
        <!-- Logo & Title Container -->
        <div
          @click="isSidebarCollapsed && toggleSidebarCollapse()"
          :class="[
            isSidebarCollapsed ? 'justify-center cursor-pointer' : '',
            'flex items-center gap-3 min-w-0 shrink-0 transition-all'
          ]"
        >
          <img
            :src="logoUrl"
            alt="E-LMS Logo"
            :class="[
              isSidebarCollapsed ? 'hover:scale-110' : '',
              'w-9 h-9 min-w-[36px] min-h-[36px] max-w-[36px] max-h-[36px] aspect-square rounded-full object-cover shadow-md shadow-indigo-500/20 ring-2 ring-indigo-500/30 shrink-0 transition-all duration-300'
            ]"
          />
          <div v-show="!isSidebarCollapsed" class="transition-opacity duration-200 min-w-0">
            <h1 class="text-base font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 via-cyan-300 to-sky-400 tracking-tight whitespace-nowrap">
              E-LMS Admin
            </h1>
            <p class="text-[10px] text-slate-400 font-medium tracking-wide uppercase whitespace-nowrap">Admin Panel</p>
          </div>
        </div>

        <!-- Collapse / Expand Toggle Button -->
        <button
          @click="toggleSidebarCollapse"
          type="button"
          :title="isSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
          :class="[
            isSidebarCollapsed
              ? 'absolute -right-3 top-1/2 -translate-y-1/2 bg-slate-800 text-indigo-400 border border-slate-700 shadow-md rounded-full p-1 hover:scale-110 hover:bg-slate-700 z-10'
              : 'p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 shrink-0',
            'transition-all duration-200 focus:outline-none'
          ]"
        >
          <svg
            :class="[isSidebarCollapsed ? 'rotate-180 w-3.5 h-3.5' : 'w-5 h-5', 'transition-transform duration-300']"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav
        :class="[
          isSidebarCollapsed ? 'px-0 overflow-visible' : 'px-3 custom-scrollbar overflow-y-auto',
          'flex flex-1 flex-col py-4'
        ]"
        :style="isSidebarCollapsed ? { scrollbarWidth: 'none', msOverflowStyle: 'none' } : {}"
      >
        <ul role="list" class="space-y-1 w-full">
          <li
            v-for="item in navigation"
            :key="item.name"
            :class="isSidebarCollapsed ? 'relative group/flyout flex justify-center w-full' : 'relative'"
          >
            <!-- Direct Link (No Children) -->
            <Link
              v-if="!item.children || item.children.length === 0"
              :href="item.href!"
              :title="isSidebarCollapsed ? item.name : undefined"
              :class="[
                $page.url.startsWith(item.href!) 
                  ? 'bg-indigo-500/15 text-indigo-300 border border-indigo-500/30 font-semibold shadow-sm' 
                  : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent font-medium',
                isSidebarCollapsed ? 'justify-center px-0 w-10 h-10 mx-auto' : 'px-3 w-full gap-x-3',
                'group flex items-center rounded-xl py-2.5 text-xs transition-all duration-200'
              ]"
            >
              <div class="relative flex items-center justify-center shrink-0">
                <img 
                  v-if="item.iconUrl"
                  :src="item.iconUrl" 
                  :alt="item.name"
                  @error="onIconError"
                  class="w-5 h-5 object-contain shrink-0 filter drop-shadow-sm transition-transform duration-200 group-hover:scale-110"
                />
                <svg 
                  :class="[
                    $page.url.startsWith(item.href!) ? 'text-indigo-400' : 'text-slate-500 group-hover:text-slate-300',
                    item.iconUrl ? 'hidden' : '',
                    'h-5 w-5 shrink-0 transition-colors'
                  ]"
                  fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon || 'M4 6h16M4 12h16M4 18h16'" />
                </svg>
              </div>
              <span v-show="!isSidebarCollapsed" class="flex-1 truncate">{{ item.name }}</span>
              <span v-if="item.badge && !isSidebarCollapsed" class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                {{ item.badge }}
              </span>
            </Link>

            <!-- Collapsible Module with Submenu -->
            <div v-else class="space-y-1 w-full flex flex-col items-center">
              <button
                @click="toggleModule(item.key!)"
                type="button"
                :title="isSidebarCollapsed ? item.name : undefined"
                :class="[
                  isChildActive(item.children) 
                    ? 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/20' 
                    : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent',
                  isSidebarCollapsed ? 'justify-center px-0 w-10 h-10 mx-auto' : 'px-3 w-full justify-between',
                  'group flex items-center rounded-xl py-2.5 text-xs font-medium transition-all duration-200'
                ]"
              >
                <div :class="[isSidebarCollapsed ? 'justify-center w-full' : '', 'flex items-center gap-x-3 truncate']">
                  <div class="relative flex items-center justify-center shrink-0">
                    <img 
                      v-if="item.iconUrl"
                      :src="item.iconUrl" 
                      :alt="item.name"
                      @error="onIconError"
                      class="w-5 h-5 object-contain shrink-0 filter drop-shadow-sm transition-transform duration-200 group-hover:scale-110"
                    />
                    <svg 
                      :class="[
                        isChildActive(item.children) ? 'text-indigo-400' : 'text-slate-500 group-hover:text-slate-300',
                        item.iconUrl ? 'hidden' : '',
                        'h-5 w-5 shrink-0 transition-colors'
                      ]"
                      fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon || 'M4 6h16M4 12h16M4 18h16'" />
                    </svg>
                  </div>
                  <span v-show="!isSidebarCollapsed" class="truncate">{{ item.name }}</span>
                </div>

                <div v-show="!isSidebarCollapsed" class="flex items-center gap-1.5">
                  <span v-if="item.badge" class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                    {{ item.badge }}
                  </span>

                  <!-- Minimal, Sleek Chevron Arrow Indicator (No Bulky Box / Heavy Glow) -->
                  <svg
                    :class="[
                      expandedModules[item.key!] ? 'rotate-180 text-indigo-400' : 'text-slate-500 group-hover:text-slate-300',
                      'w-4 h-4 transition-transform duration-200 shrink-0 ml-1'
                    ]"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </button>

              <!-- Submenu Items with Curved Circuit Tree Branches -->
              <div
                v-show="!isSidebarCollapsed && expandedModules[item.key!]"
                class="relative ml-6 pl-4 space-y-1 my-1.5 transition-all duration-300"
              >
                <div
                  v-for="(sub, idx) in item.children"
                  :key="sub.name"
                  class="group relative flex items-center"
                >
                  <!-- Vertical Trunk Line (Connecting down through items) -->
                  <div
                    v-if="idx < item.children.length - 1"
                    class="absolute -left-4 top-0 bottom-0 w-[2px] bg-slate-700/60"
                  ></div>

                  <!-- Curved Branch Line curving into this item (rounded-bl-xl) -->
                  <div
                    :class="[
                      isSubActive(sub.href) ? 'border-indigo-400/90 shadow-xs shadow-indigo-500/30' : 'border-slate-700/80 group-hover:border-slate-400',
                      'absolute -left-4 top-0 h-1/2 w-3.5 border-l-2 border-b-2 rounded-bl-xl transition-colors duration-200 pointer-events-none'
                    ]"
                  ></div>

                  <Link
                    :href="sub.href"
                    :class="[
                      isSubActive(sub.href) 
                        ? 'text-indigo-300 font-semibold bg-indigo-500/15 border border-indigo-500/30 shadow-xs' 
                        : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50 border border-transparent',
                      'flex-1 flex items-center gap-2 rounded-xl px-2.5 py-1.5 text-xs transition-all duration-200 ml-1'
                    ]"
                  >
                    <!-- Clean Vector Mini-Icon / Circuit Node -->
                    <div class="relative flex items-center justify-center shrink-0">
                      <img
                        v-if="sub.iconUrl"
                        :src="sub.iconUrl" 
                        :alt="sub.name"
                        @error="onIconError"
                        class="w-4 h-4 object-contain shrink-0 filter drop-shadow-xs transition-transform duration-200 group-hover:scale-110"
                      />
                      <svg
                        :class="[
                          isSubActive(sub.href) ? 'text-indigo-400 scale-110' : 'text-slate-500 group-hover:text-slate-300',
                          sub.iconUrl ? 'hidden' : '',
                          'w-4 h-4 transition-all duration-200 shrink-0'
                        ]"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" :d="sub.icon || 'M4 6h16M4 12h16M4 18h16'" />
                      </svg>
                    </div>

                    <!-- Item Name -->
                    <span class="truncate">{{ sub.name }}</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Flyout Popover for Collapsed Sidebar Mode (Has Children) -->
            <div
              v-if="isSidebarCollapsed && item.children && item.children.length > 0"
              class="absolute left-full top-0 ml-3.5 w-64 opacity-0 pointer-events-none group-hover/flyout:opacity-100 group-hover/flyout:pointer-events-auto transition-all duration-200 ease-out translate-x-1 group-hover/flyout:translate-x-0 z-50"
            >
              <!-- Invisible hover bridge between icon and flyout box -->
              <div class="absolute -left-4 top-0 bottom-0 w-4"></div>

              <div class="relative bg-slate-900/95 backdrop-blur-xl border border-slate-700/80 rounded-2xl p-3 shadow-2xl ring-1 ring-slate-800/80">
                <!-- Directional Caret / Arrow Pointer Pointing Left to the Source Icon -->
                <div class="absolute -left-1.5 top-3.5 w-3 h-3 bg-slate-900 border-l border-b border-slate-700/80 rotate-45 z-10 pointer-events-none"></div>

                <!-- Flyout Header -->
                <div class="relative z-20 flex items-center justify-between px-2 py-1.5 mb-2 border-b border-slate-800 pb-2">
                  <div class="flex items-center gap-2 min-w-0">
                    <div class="p-1.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 shrink-0 flex items-center justify-center">
                      <img 
                        v-if="item.iconUrl"
                        :src="item.iconUrl" 
                        :alt="item.name"
                        @error="onIconError"
                        class="w-4 h-4 object-contain shrink-0"
                      />
                      <svg
                        :class="[item.iconUrl ? 'hidden' : '', 'w-4 h-4 text-indigo-400']"
                        fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon || 'M4 6h16M4 12h16M4 18h16'" />
                      </svg>
                    </div>
                    <span class="text-xs font-bold text-white truncate">{{ item.name }}</span>
                  </div>
                  <span v-if="item.badge" class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 shrink-0">
                    {{ item.badge }}
                  </span>
                </div>

                <!-- Flyout Children links -->
                <div class="relative z-20 space-y-1 max-h-[70vh] overflow-y-auto custom-scrollbar pr-1">
                  <Link
                    v-for="sub in item.children"
                    :key="sub.name"
                    :href="sub.href"
                    :class="[
                      isSubActive(sub.href)
                        ? 'bg-indigo-500/15 text-indigo-300 font-semibold border border-indigo-500/30 shadow-xs'
                        : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/70 border border-transparent',
                      'flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-xs transition-all duration-150 group/flyout-sub'
                    ]"
                  >
                    <div class="relative flex items-center justify-center shrink-0">
                      <img 
                        v-if="sub.iconUrl"
                        :src="sub.iconUrl" 
                        :alt="sub.name"
                        @error="onIconError"
                        class="w-4 h-4 object-contain shrink-0 group-hover/flyout-sub:scale-110 transition-transform"
                      />
                      <svg
                        :class="[
                          isSubActive(sub.href) ? 'text-indigo-400 scale-110' : 'text-slate-500 group-hover/flyout-sub:text-slate-300',
                          sub.iconUrl ? 'hidden' : '',
                          'w-4 h-4 shrink-0 transition-colors'
                        ]"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" :d="sub.icon || 'M4 6h16M4 12h16M4 18h16'" />
                      </svg>
                    </div>
                    <span class="truncate">{{ sub.name }}</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Sleek Tooltip Popover for Collapsed Sidebar Mode (No Children) -->
            <div
              v-else-if="isSidebarCollapsed && (!item.children || item.children.length === 0)"
              class="absolute left-full top-1/2 -translate-y-1/2 ml-3.5 opacity-0 pointer-events-none group-hover/flyout:opacity-100 transition-all duration-200 ease-out translate-x-1 group-hover/flyout:translate-x-0 z-50 whitespace-nowrap"
            >
              <div class="relative bg-slate-900/95 backdrop-blur-xl border border-slate-700/80 text-white text-xs font-semibold px-3 py-1.5 rounded-xl shadow-xl flex items-center gap-2 ring-1 ring-slate-800/80">
                <!-- Directional Caret / Arrow Pointer Pointing Left to the Source Icon -->
                <div class="absolute -left-1 top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900 border-l border-b border-slate-700/80 rotate-45 z-10 pointer-events-none"></div>

                <span class="relative z-20">{{ item.name }}</span>
                <span v-if="item.badge" class="relative z-20 px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                  {{ item.badge }}
                </span>
              </div>
            </div>
          </li>
        </ul>
      </nav>

      <!-- User Profile Bottom -->
      <div :class="[isSidebarCollapsed ? 'px-0 py-3' : 'p-3', 'mt-auto border-t border-slate-800 bg-slate-900/60']">
        <div :class="[isSidebarCollapsed ? 'flex-col justify-center items-center gap-2.5 w-full' : 'gap-3', 'flex items-center']">
          <!-- Interactive Avatar with Hover Upload Icon -->
          <div
            @click="triggerAvatarUpload"
            class="relative group cursor-pointer shrink-0 mx-auto"
            title="Click to upload profile photo"
          >
            <img
              :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=6366f1&color=fff`"
              alt="Profile"
              class="h-9 w-9 rounded-full border border-slate-700 object-cover group-hover:brightness-75 transition-all shadow-md"
            />
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-slate-900"></span>

            <!-- Hover Camera Overlay -->
            <div class="absolute inset-0 rounded-full bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
              <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>

            <!-- Uploading Spinner Indicator -->
            <div v-if="isUploadingAvatar" class="absolute inset-0 rounded-full bg-slate-900/90 flex items-center justify-center">
              <svg class="animate-spin h-4 w-4 text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>

          <!-- Hidden Input for Avatar File Upload -->
          <input
            type="file"
            ref="avatarInput"
            accept="image/*"
            class="hidden"
            @change="handleAvatarChange"
          />

          <div v-show="!isSidebarCollapsed" class="flex-1 min-w-0">
            <div class="flex items-center gap-1.5 min-w-0">
              <p class="text-xs font-semibold text-white truncate cursor-pointer hover:text-indigo-300 transition-colors" @click="triggerAvatarUpload" title="Click to upload profile photo">
                {{ user.name }}
              </p>
              <OfficialVerifiedBadge :role="user.role" size="sm" />
            </div>
            <p class="text-[11px] text-slate-400 truncate">{{ user.email }}</p>
          </div>
          <button @click="logout" title="Log Out" class="p-1.5 text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Sticky Top Navbar (Desktop & Mobile) -->
    <header :class="[isSidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72', 'sticky top-0 z-40 bg-slate-900/90 backdrop-blur-xl border-b border-slate-800/80 transition-all duration-300']">
      <div class="flex h-14 items-center justify-between px-4 sm:px-6 lg:px-8 gap-4">
        
        <!-- Left Side: Mobile Menu Toggle, Breadcrumbs & Sleek Search Input -->
        <div class="flex items-center gap-3.5 min-w-0">
          <!-- Sidebar Toggle (Mobile Only) -->
          <button
            @click="sidebarOpen = !sidebarOpen"
            type="button"
            class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg focus:outline-none transition-colors cursor-pointer lg:hidden"
            title="Toggle Mobile Navigation"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <!-- Harmonized Breadcrumb -->
          <div class="hidden sm:flex items-center gap-2 text-xs font-medium truncate">
            <span class="text-slate-400 font-normal">Admin</span>
            <svg class="w-3.5 h-3.5 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="px-2.5 py-1 rounded-lg bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 font-semibold truncate">
              {{ currentBreadcrumb[1] }}
            </span>
          </div>

          <!-- Sleek Glassmorphic Search Bar -->
          <div class="relative hidden md:block">
            <button
              @click="toggleDropdown('search')"
              type="button"
              class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-slate-800/60 hover:bg-slate-800 text-slate-400 hover:text-slate-200 border border-slate-700/60 hover:border-indigo-500/40 text-xs transition-all w-56 lg:w-72 justify-between shadow-inner group"
            >
              <div class="flex items-center gap-2 truncate min-w-0 flex-1">
                <svg class="w-3.5 h-3.5 text-indigo-400 shrink-0 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="truncate text-slate-400 group-hover:text-slate-300">{{ i18n.t('nav_search_placeholder', 'ស្វែងរកប្រព័ន្ធ (Global Search)...') }}</span>
              </div>
              <kbd class="hidden lg:inline-flex items-center shrink-0 whitespace-nowrap px-1.5 py-0.5 text-[10px] font-mono font-semibold text-slate-400 bg-slate-900/80 border border-slate-700/60 rounded shadow-xs leading-none">Ctrl K</kbd>
            </button>
          </div>
        </div>

        <!-- Right Side: Quick Action Icon, Language, Fullscreen, Notifications Bell, Admin Profile Avatar -->
        <div class="flex items-center gap-1.5 sm:gap-2">

          <!-- Mobile Search Button -->
          <button
            @click="toggleDropdown('search')"
            type="button"
            class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg md:hidden focus:outline-none transition-colors"
            title="Search"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </button>

          <!-- Quick Action Dropdown (Hover Flyout & Smooth Transition) -->
          <div 
            class="relative" 
            @mouseenter="isQuickActionOpen = true" 
            @mouseleave="isQuickActionOpen = false"
          >
            <button
              @click="isQuickActionOpen = !isQuickActionOpen"
              type="button"
              class="h-8 px-2.5 bg-slate-800/60 hover:bg-slate-800 border border-slate-700/60 hover:border-slate-600 rounded-lg transition-all flex items-center gap-2 cursor-pointer group select-none focus:outline-none focus:ring-0 focus-visible:outline-none"
              title="Quick Actions"
            >
              <img :src="actionBtnIcon" alt="Actions" class="w-4 h-4 shrink-0 group-hover:scale-105 transition-transform" />
              <span class="hidden sm:inline text-xs font-semibold text-slate-300 group-hover:text-white font-sans tracking-wide">{{ i18n.t('nav_create_new', 'បង្កើតរហ័ស') }}</span>
              <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-200 transition-transform duration-200" :class="isQuickActionOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="opacity-0 -translate-y-1"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-1"
            >
              <div
                v-if="isQuickActionOpen"
                class="absolute right-0 mt-1.5 w-60 rounded-xl bg-slate-800/95 backdrop-blur-xl border border-slate-700/80 shadow-2xl py-1.5 z-50 overflow-hidden"
              >
                <div class="px-3.5 py-1.5 border-b border-slate-700/60 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                  បង្កើត/បន្ថែមរហ័ស (Quick Create)
                </div>
                <Link
                  v-for="act in quickActions"
                  :key="act.name"
                  :href="act.href"
                  @click="isQuickActionOpen = false"
                  class="flex items-center gap-2.5 px-3.5 py-2 text-xs text-slate-300 hover:text-white hover:bg-slate-700/60 transition-colors group/item"
                >
                  <img :src="act.iconUrl" :alt="act.name" class="w-4 h-4 shrink-0 group-hover/item:scale-110 transition-transform" />
                  <span class="font-medium">{{ act.name }}</span>
                </Link>
              </div>
            </Transition>
          </div>

          <!-- Online / Offline Status Badge (Flaticon Style) -->
          <div 
            class="relative" 
            @mouseenter="isStatusOpen = true" 
            @mouseleave="isStatusOpen = false"
          >
            <button
              @click="toggleDropdown('status')"
              type="button"
              class="h-8 px-2.5 bg-slate-800/60 hover:bg-slate-800 border border-slate-700/60 hover:border-slate-600 rounded-lg transition-all flex items-center gap-2 cursor-pointer group select-none focus:outline-none focus:ring-0 focus-visible:outline-none"
              :title="isOnline ? 'អនឡាញ (Online)' : 'អូហ្វឡាញ (Offline)'"
            >
              <div class="relative flex items-center justify-center shrink-0">
                <img 
                  :src="isOnline ? onlineIconUrl : offlineIconUrl" 
                  :alt="isOnline ? 'Online' : 'Offline'"
                  class="w-4 h-4 object-contain shrink-0 group-hover:scale-110 transition-transform filter drop-shadow-xs" 
                />
                <span 
                  :class="[isOnline ? 'bg-emerald-500 shadow-emerald-500/50' : 'bg-rose-500 shadow-rose-500/50']"
                  class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full ring-2 ring-slate-900 shadow-xs animate-pulse"
                ></span>
              </div>
              <span 
                :class="[isOnline ? 'text-emerald-400' : 'text-rose-400']"
                class="hidden sm:inline text-xs font-semibold font-sans tracking-wide"
              >
                {{ isOnline ? 'Online' : 'Offline' }}
              </span>
              <svg 
                :class="[isStatusOpen ? 'rotate-180 text-slate-200' : 'text-slate-400']" 
                class="w-3.5 h-3.5 transition-transform duration-200" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <!-- Status Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="transform opacity-0 scale-95 -translate-y-1"
              enter-to-class="transform opacity-100 scale-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="transform opacity-100 scale-100 translate-y-0"
              leave-to-class="transform opacity-0 scale-95 -translate-y-1"
            >
              <div
                v-if="isStatusOpen"
                class="absolute right-0 mt-1.5 w-56 rounded-xl bg-slate-800/95 backdrop-blur-xl border border-slate-700/80 shadow-2xl py-1.5 z-50 overflow-hidden"
              >
                <div class="px-3.5 py-1.5 border-b border-slate-700/60 flex items-center justify-between">
                  <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">ស្ថានភាពប្រព័ន្ធ (Status)</span>
                  <span 
                    :class="[isOnline ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border-rose-500/30']"
                    class="px-2 py-0.5 text-[9px] font-bold rounded-full border"
                  >
                    {{ isOnline ? 'Connected' : 'Disconnected' }}
                  </span>
                </div>

                <div class="p-1 space-y-1">
                  <button
                    @click="setStatusMode(true)"
                    :class="[isOnline ? 'bg-emerald-500/15 text-emerald-300 font-semibold border-emerald-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-700/60 border-transparent']"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-lg border transition-all cursor-pointer text-left"
                  >
                    <div class="flex items-center gap-2.5">
                      <img :src="onlineIconUrl" alt="Online" class="w-4 h-4 object-contain" />
                      <div>
                        <p class="font-medium text-xs text-white">អនឡាញ (Online)</p>
                        <p class="text-[10px] text-slate-400">ភ្ជាប់អ៊ីនធឺណិតធម្មតា</p>
                      </div>
                    </div>
                    <svg v-if="isOnline" class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                  </button>

                  <button
                    @click="setStatusMode(false)"
                    :class="[!isOnline ? 'bg-rose-500/15 text-rose-300 font-semibold border-rose-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-700/60 border-transparent']"
                    class="w-full flex items-center justify-between px-3 py-2 text-xs rounded-lg border transition-all cursor-pointer text-left"
                  >
                    <div class="flex items-center gap-2.5">
                      <img :src="offlineIconUrl" alt="Offline" class="w-4 h-4 object-contain" />
                      <div>
                        <p class="font-medium text-xs text-white">អូហ្វឡាញ (Offline)</p>
                        <p class="text-[10px] text-slate-400">ដាច់អ៊ីនធឺណិត / Offline</p>
                      </div>
                    </div>
                    <svg v-if="!isOnline" class="w-3.5 h-3.5 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Language Switcher Pill (Hover Flyout & Smooth Transition) -->
          <div 
            class="relative" 
            @mouseenter="isLangOpen = true" 
            @mouseleave="isLangOpen = false"
          >
            <button
              @click="isLangOpen = !isLangOpen"
              type="button"
              class="h-8 px-2.5 bg-slate-800/60 hover:bg-slate-800 border border-slate-700/60 hover:border-slate-600 rounded-lg transition-all flex items-center gap-2 cursor-pointer group select-none focus:outline-none focus:ring-0 focus-visible:outline-none"
              title="Switch Language"
            >
              <img 
                :src="languages.find(l => l.code === currentLang)?.flagUrl" 
                :alt="currentLang"
                class="w-4 h-4 rounded-full object-cover shrink-0" 
              />
              <span class="uppercase text-xs font-semibold text-slate-300 group-hover:text-white font-sans tracking-wide">
                {{ currentLang }}
              </span>
              <svg 
                :class="[isLangOpen ? 'rotate-180 text-slate-200' : 'text-slate-400']" 
                class="w-3.5 h-3.5 transition-transform duration-200" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <!-- Language Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="transform opacity-0 scale-95 -translate-y-1"
              enter-to-class="transform opacity-100 scale-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="transform opacity-100 scale-100 translate-y-0"
              leave-to-class="transform opacity-0 scale-95 -translate-y-1"
            >
              <div
                v-if="isLangOpen"
                class="absolute right-0 mt-1.5 w-40 rounded-xl bg-slate-800/95 backdrop-blur-xl border border-slate-700/80 shadow-2xl py-1.5 z-50 overflow-hidden"
              >
                <button
                  v-for="lang in languages"
                  :key="lang.code"
                  @click="selectLanguage(lang.code)"
                  :class="[
                    currentLang === lang.code ? 'bg-indigo-600/20 text-indigo-300 font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-700/60'
                  ]"
                  class="w-full flex items-center justify-between px-3.5 py-2 text-xs transition-colors rounded-none cursor-pointer focus:outline-none"
                >
                  <span class="flex items-center gap-2.5">
                    <img :src="lang.flagUrl" :alt="lang.name" class="w-4 h-4 rounded-full object-cover shrink-0" />
                    <span>{{ lang.name }}</span>
                  </span>
                  <svg v-if="currentLang === lang.code" class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                </button>
              </div>
            </Transition>
          </div>

          <!-- Fullscreen Button -->
          <button
            @click="toggleFullscreen"
            type="button"
            class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 border border-transparent hover:border-slate-700/60 rounded-xl transition-all focus:outline-none"
            :title="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'"
          >
            <svg v-if="!isFullscreen" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 9L4 4m0 0l5 0m-5 0l0 5m11 0l5-5m0 0l-5 0m5 0l0 5m-5 11l5 5m0 0l-5 0m5 0l0-5m-11 0l-5 5m0 0l5 0m-5 0l0-5" />
            </svg>
          </button>

          <!-- Notifications Bell Dropdown -->
          <div class="relative">
            <button
              @click="toggleDropdown('notification')"
              type="button"
              class="relative p-2 text-slate-400 hover:text-white hover:bg-slate-800 border border-transparent hover:border-slate-700/60 rounded-xl transition-all focus:outline-none"
              title="Notifications"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span v-if="unreadNotificationsCount > 0" class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-slate-900 animate-pulse"></span>
            </button>

            <!-- Notifications Dropdown -->
            <div
              v-if="isNotificationOpen"
              class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-slate-800 border border-slate-700/80 shadow-2xl z-50 overflow-hidden"
            >
              <div class="px-4 py-3 border-b border-slate-700/80 flex items-center justify-between bg-slate-800/80">
                <div class="flex items-center gap-2">
                  <h3 class="text-xs font-bold text-white">ការជូនដំណឹង (Notifications)</h3>
                  <span v-if="unreadNotificationsCount > 0" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-500/20 text-indigo-300">
                    {{ unreadNotificationsCount }} ថ្មី
                  </span>
                </div>
                <button
                  v-if="unreadNotificationsCount > 0"
                  @click="markAllAsRead"
                  class="text-[11px] text-indigo-400 hover:text-indigo-300 transition-colors font-medium"
                >
                  អានទាំងអស់
                </button>
              </div>

              <div class="max-h-80 overflow-y-auto custom-scrollbar divide-y divide-slate-700/40">
                <div
                  v-for="notif in notifications"
                  :key="notif.id"
                  @click="markNotificationRead(notif.id)"
                  :class="[notif.read ? 'bg-slate-800/30 opacity-70' : 'bg-slate-800 hover:bg-slate-700/50', 'p-3.5 transition-colors cursor-pointer block']"
                >
                  <Link :href="notif.link" @click="isNotificationOpen = false">
                    <div class="flex items-start gap-3">
                      <div :class="[
                        notif.type === 'payment' ? 'bg-emerald-500/20 text-emerald-400' :
                        notif.type === 'user' ? 'bg-indigo-500/20 text-indigo-400' :
                        notif.type === 'support' ? 'bg-amber-500/20 text-amber-400' : 'bg-cyan-500/20 text-cyan-400',
                        'p-2 rounded-xl shrink-0 mt-0.5'
                      ]">
                        <svg v-if="notif.type === 'payment'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <svg v-else-if="notif.type === 'user'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        <svg v-else-if="notif.type === 'support'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                          <p class="text-xs font-semibold text-slate-200 truncate">{{ notif.title }}</p>
                          <span class="text-[10px] text-slate-400 shrink-0 ml-1">{{ notif.time }}</span>
                        </div>
                        <p class="text-[11px] text-slate-400 line-clamp-2 mt-0.5">{{ notif.desc }}</p>
                      </div>
                      <span v-if="!notif.read" class="w-2 h-2 rounded-full bg-indigo-500 shrink-0 mt-1"></span>
                    </div>
                  </Link>
                </div>
              </div>

              <div class="p-2 border-t border-slate-700/80 bg-slate-900/60 text-center">
                <Link
                  href="/admin/notifications/history"
                  @click="isNotificationOpen = false"
                  class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors"
                >
                  មើលការជូនដំណឹងទាំងអស់ →
                </Link>
              </div>
            </div>
          </div>

          <!-- Admin Profile Dropdown Menu -->
          <div class="relative ml-1">
            <button
              @click="toggleDropdown('profile')"
              type="button"
              class="flex items-center gap-2 p-1 rounded-xl hover:bg-slate-800/80 border border-transparent hover:border-slate-700/60 transition-all focus:outline-none group"
            >
              <div class="relative shrink-0">
                <img
                  :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=6366f1&color=fff`"
                  alt="Admin Profile"
                  class="w-7 h-7 rounded-full border border-slate-700 object-cover shadow-xs group-hover:border-indigo-500/50 transition-colors"
                />
                <span class="absolute bottom-0 right-0 w-2 h-2 bg-emerald-500 rounded-full ring-2 ring-slate-900"></span>
              </div>
              <div class="hidden md:flex items-center gap-1.5 min-w-0 max-w-[240px]">
                <span class="text-xs font-semibold text-slate-200 group-hover:text-indigo-300 transition-colors truncate">{{ user.name }}</span>
                <OfficialVerifiedBadge :role="user.role" size="xs" :show-label="true" />
              </div>
              <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-200 transition-transform" :class="isProfileOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <!-- Profile Dropdown Popup -->
            <div
              v-if="isProfileOpen"
              class="absolute right-0 mt-2 w-64 rounded-2xl bg-slate-800 border border-slate-700/80 shadow-2xl py-2 z-50 animate-in fade-in duration-150"
            >
              <!-- Profile Header Card -->
              <div class="px-4 py-3 border-b border-slate-700/80 bg-gradient-to-r from-indigo-900/30 to-purple-900/30">
                <div class="flex items-center gap-3">
                  <img
                    :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=6366f1&color=fff`"
                    alt="Admin Avatar"
                    class="w-10 h-10 rounded-full border border-slate-700 object-cover shadow-md"
                  />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1 min-w-0">
                      <h4 class="text-xs font-bold text-white truncate">{{ user.name }}</h4>
                      <OfficialVerifiedBadge :role="user.role" size="xs" />
                    </div>
                    <p class="text-[11px] text-slate-400 truncate">{{ user.email }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                      {{ user.role === 'admin' ? 'Super Admin' : (user.role === 'teacher' ? 'Teacher' : 'Student') }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Quick Links -->
              <div class="py-1 border-b border-slate-700/60">
                <Link
                  href="/admin/settings"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-300 hover:text-white hover:bg-slate-700/50 transition-colors"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <span>ការកំណត់ប្រព័ន្ធ (Settings)</span>
                </Link>
                <Link
                  href="/admin/auth/roles"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-300 hover:text-white hover:bg-slate-700/50 transition-colors"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  <span>សិទ្ធិ និង តួនាទី (Roles & Security)</span>
                </Link>
                <Link
                  href="/admin/auth-logs"
                  @click="isProfileOpen = false"
                  class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-300 hover:text-white hover:bg-slate-700/50 transition-colors"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>កំណត់ត្រាសកម្មភាព (System Logs)</span>
                </Link>
                <button
                  @click="triggerAvatarUpload(); isProfileOpen = false;"
                  class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-slate-300 hover:text-white hover:bg-slate-700/50 transition-colors text-left"
                >
                  <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  <span>ប្ដូររូបថត (Change Avatar)</span>
                </button>
              </div>

              <!-- Logout -->
              <div class="pt-1">
                <button
                  @click="logout"
                  class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-rose-400 hover:bg-rose-500/10 transition-colors font-medium text-left"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                  <span>ចាកចេញពីប្រព័ន្ធ (Log Out)</span>
                </button>
              </div>
            </div>
          </div>

        </div>

      </div>
    </header>

    <!-- Global Command Palette Modal (Search Dialog) -->
    <div v-if="isSearchOpen" class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24 px-4">
      <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" @click="isSearchOpen = false"></div>
      <div class="relative w-full max-w-2xl bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl overflow-hidden z-50">
        <!-- Search Input Header -->
        <div class="flex items-center px-4 border-b border-slate-800 bg-slate-900/90">
          <svg class="w-5 h-5 text-indigo-400 shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ស្វែងរកម៉ូឌុល ទំព័រ ឬមុខងារគ្រប់គ្រងទាំងអស់... (Search modules, pages...)"
            class="w-full bg-transparent py-4 text-sm text-white placeholder-slate-500 focus:outline-none"
            autofocus
          />
          <button @click="isSearchOpen = false" class="p-1 text-slate-400 hover:text-white rounded-lg">
            <kbd class="px-2 py-0.5 text-xs bg-slate-800 rounded border border-slate-700">ESC</kbd>
          </button>
        </div>

        <!-- Search Results List -->
        <div class="max-h-96 overflow-y-auto p-2 custom-scrollbar">
          <div v-if="filteredSearchResults.length === 0" class="p-8 text-center text-slate-400 text-sm">
            មិនរកឃើញទិន្នន័យស្វែងរកឡើយ (No results found)
          </div>
          <div v-else class="space-y-1">
            <Link
              v-for="res in filteredSearchResults"
              :key="res.href"
              :href="res.href"
              @click="isSearchOpen = false"
              class="group flex items-center justify-between p-3 rounded-xl hover:bg-indigo-600/15 border border-transparent hover:border-indigo-500/30 transition-all"
            >
              <div class="flex items-center gap-3">
                <div class="p-1.5 rounded-lg bg-slate-800 group-hover:bg-indigo-500/20 text-slate-400 group-hover:text-indigo-300 transition-colors shrink-0 flex items-center justify-center">
                  <img v-if="res.iconUrl" :src="res.iconUrl" :alt="res.name" class="w-4 h-4 object-contain shrink-0" />
                  <svg v-else-if="res.icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" :d="res.icon"/></svg>
                </div>
                <div>
                  <h4 class="text-xs font-semibold text-white group-hover:text-indigo-200 transition-colors">{{ res.name }}</h4>
                  <p class="text-[10px] text-slate-400 uppercase tracking-wide">{{ res.category }}</p>
                </div>
              </div>
              <svg class="w-4 h-4 text-slate-600 group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </Link>
          </div>
        </div>

        <!-- Command Palette Footer -->
        <div class="px-4 py-2.5 bg-slate-900/90 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
          <div class="flex items-center gap-3">
            <span><kbd class="px-1.5 py-0.5 bg-slate-800 border border-slate-700 rounded text-[10px]">Ctrl K</kbd> ដើម្បីបើក/បិទ</span>
            <span><kbd class="px-1.5 py-0.5 bg-slate-800 border border-slate-700 rounded text-[10px]">ESC</kbd> ដើម្បីចាកចេញ</span>
          </div>
          <span class="text-indigo-400 font-medium">E-LMS Command Palette</span>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <main :class="[isSidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72', 'pt-3.5 sm:pt-4 pb-10 transition-all duration-300']">
      <div class="px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <header class="mb-6" v-if="title || $slots.header">
          <slot name="header">
            <h2 class="text-2xl font-bold leading-7 text-white sm:truncate sm:text-3xl sm:tracking-tight">{{ title }}</h2>
          </slot>
        </header>

        <!-- Page Content Slot -->
        <slot />
      </div>
    </main>
  </div>
</template>

