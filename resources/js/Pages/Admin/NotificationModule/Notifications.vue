<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

export type NotificationType = 
  | 'Announcement'
  | 'Course Approval'
  | 'Quiz Published'
  | 'Assignment Due'
  | 'Assignment Graded'
  | 'AI Recommendation'
  | 'At-Risk Alert'
  | 'System Notification'

export interface SystemNotificationItem {
  id: number
  title: string
  title_kh: string
  type: NotificationType
  recipient_name: string
  recipient_role: 'Student' | 'Teacher' | 'Admin' | 'All Students' | 'All Teachers'
  recipient_code?: string
  recipient_avatar?: string
  course_name?: string
  major?: string
  message: string
  message_kh: string
  created_at: string
  is_read: boolean
  action_url?: string
  extra_meta?: Record<string, any>
}

const props = defineProps<{
  notifications?: SystemNotificationItem[]
  topStats?: any
}>()

// Default Mock Data implementing the official 8 canonical notification types and specific examples
const defaultNotifications: SystemNotificationItem[] = [
  {
    id: 1,
    title: 'Assignment Due: JavaScript Functions',
    title_kh: 'កាលបរិច្ឆេទផុតកំណត់កិច្ចការ៖ JavaScript Functions',
    type: 'Assignment Due',
    recipient_name: 'Sok Dara',
    recipient_role: 'Student',
    recipient_code: 'SPI-2026-001',
    course_name: 'Web Development Basics',
    major: 'Information Technology',
    message: 'Assignment: JavaScript Functions is due tomorrow at 11:59 PM. Please submit your repository code.',
    message_kh: 'កិច្ចការ "JavaScript Functions" នឹងផុតកំណត់នៅថ្ងៃស្អែក វេលាម៉ោង ១១:៥៩ យប់។ សូមផ្ញើកូដរបស់អ្នកតាមប្រព័ន្ធ។',
    created_at: 'Today, 14:20',
    is_read: false,
    action_url: '/admin/quizzes?tab=assignments',
    extra_meta: { due_date: 'Tomorrow, 23:59', urgency: 'High' }
  },
  {
    id: 2,
    title: 'AI Recommendation: Weak Topic Detected',
    title_kh: 'អនុសាសន៍ AI៖ បានរកឃើញប្រធានបទខ្សោយ (Weak Topic)',
    type: 'AI Recommendation',
    recipient_name: 'Sok Piseth',
    recipient_role: 'Student',
    recipient_code: 'SPI-2026-004',
    course_name: 'Soil Science & Plant Nutrition',
    major: 'Agriculture',
    message: 'AI detected a weak topic: Soil Management & pH Balance. Recommendation: Review Soil Preparation → Read learning material → Practice Quiz.',
    message_kh: 'AI បានរកឃើញប្រធានបទខ្សោយ៖ Soil Management & pH Balance (ពិន្ទុ < 50%)។ អនុសាសន៍៖ រំលឹក Soil Preparation → អានឯកសារជំនួយ → ធ្វើ Practice Quiz។',
    created_at: 'Today, 11:15',
    is_read: false,
    action_url: '/admin/ai-rules?tab=rules',
    extra_meta: { rule_code: 'R-09', engine: 'Learning Diagnostic AI' }
  },
  {
    id: 3,
    title: 'At-Risk Alert: Student A Requires Support',
    title_kh: 'ការជូនដំណឹង At-Risk៖ សិស្សត្រូវការជំនួយគាំទ្រជាបន្ទាន់',
    type: 'At-Risk Alert',
    recipient_name: 'Mr. Sophea (Instructor)',
    recipient_role: 'Teacher',
    recipient_code: 'TEA-IT-002',
    course_name: 'Web Development Basics',
    major: 'Information Technology',
    message: 'Student Sok Piseth flagged High Risk: Progress 32%, Quiz average 38%, Inactive 12 days, 2 overdue assignments. Proactive tutoring suggested.',
    message_kh: 'និស្សិត Sok Piseth ត្រូវបាន Flag High Risk (វឌ្ឍនភាព 32%, Quiz 38%, អសកម្ម 12 ថ្ងៃ, ជំពាក់កិច្ចការ 2)។ សូមគ្រូទំនាក់ទំនងជួយគាំទ្រ។',
    created_at: 'Today, 09:30',
    is_read: false,
    action_url: '/admin/ai-rules?tab=at_risk',
    extra_meta: { risk_level: 'High', student_affected: 'Sok Piseth' }
  },
  {
    id: 4,
    title: 'Announcement: Semester 1 Course Registration',
    title_kh: 'សេចក្តីប្រកាស៖ ការចុះឈ្មោះមុខវិជ្ជាឆមាសទី១ ឆ្នាំ២០២៦',
    type: 'Announcement',
    recipient_name: 'IT Students (Year 2)',
    recipient_role: 'All Students',
    course_name: 'All Year 2 Courses',
    major: 'Information Technology',
    message: 'Semester 1 Course Registration is officially open. Please complete module selection before October 10th.',
    message_kh: 'ការចុះឈ្មោះមុខវិជ្ជាសម្រាប់ឆមាសទី១ បានបើកហើយ។ សូមចូលទៅកាន់ Course Registration មុនថ្ងៃទី ១០ តុលា។',
    created_at: 'Yesterday, 16:45',
    is_read: true,
    action_url: '/admin/notifications/announcements',
  },
  {
    id: 5,
    title: 'Course Approval: Web Development Fundamentals',
    title_kh: 'ការអនុម័ត Course៖ វគ្គសិក្សាត្រូវបានអនុម័តដោយជោគជ័យ',
    type: 'Course Approval',
    recipient_name: 'Mr. Chan Vuthy',
    recipient_role: 'Teacher',
    recipient_code: 'TEA-AG-001',
    course_name: 'Soil Science & Plant Nutrition',
    major: 'Agriculture',
    message: 'Course syllabus and learning materials approved by Academic Board. Course is now Published for student enrollment.',
    message_kh: 'មាតិកាវគ្គសិក្សា និងកម្រងសំណួរត្រូវបានគណៈកម្មការសិក្សាអនុម័តរួចរាល់។ Course បានបោះពុម្ពជាផ្លូវការ។',
    created_at: 'Yesterday, 10:20',
    is_read: true,
    action_url: '/admin/course-module/approval',
  },
  {
    id: 6,
    title: 'Quiz Published: Complex Sentence Clauses Drill',
    title_kh: 'Quiz ត្រូវបានដាក់ឱ្យប្រឡង៖ Complex Sentence Clauses Drill',
    type: 'Quiz Published',
    recipient_name: 'Long Vichida',
    recipient_role: 'Student',
    recipient_code: 'SPI-2026-003',
    course_name: 'Academic English Grammar',
    major: 'English Literature',
    message: 'A 15-question formative practice quiz has been published by Ms. Srey. Test your understanding of restrictive vs non-restrictive clauses.',
    message_kh: 'កម្រងសំណួរអនុវត្ត 15 សំណួរ ត្រូវបានដាក់ឱ្យប្រឡង។ សូមចូលរួមឆ្លើយដើម្បីពង្រឹងចំណេះដឹង។',
    created_at: '28 Sep 2026, 15:00',
    is_read: true,
    action_url: '/admin/quizzes',
  },
  {
    id: 7,
    title: 'Assignment Graded: Tourism Operations Dynamic Pricing',
    title_kh: 'កិច្ចការត្រូវបានដាក់ពិន្ទុ៖ Tourism Dynamic Pricing Analysis',
    type: 'Assignment Graded',
    recipient_name: 'Keo Monika',
    recipient_role: 'Student',
    recipient_code: 'SPI-2026-002',
    course_name: 'Tourism Operations & Booking Systems',
    major: 'Tourism Management',
    message: 'Teacher Mr. Long graded your Case Study Assignment: 92/100 (Grade A). Feedback: Excellent yield formula application!',
    message_kh: 'លោកគ្រូ Long បានដាក់ពិន្ទុកិច្ចការរបស់អ្នក៖ 92/100 (និទ្ទេស A)។ មតិកែលម្អ៖ អនុវត្តរូបមន្ត Yield Pricing បានល្អឥតខ្ចោះ!',
    created_at: '27 Sep 2026, 11:30',
    is_read: true,
    action_url: '/admin/quizzes?tab=assignments',
  },
  {
    id: 8,
    title: 'System Notification: Database Backup Completed',
    title_kh: 'ការជូនដំណឹងប្រព័ន្ធ៖ ការបម្រុងទុកទិន្នន័យបានជោគជ័យ',
    type: 'System Notification',
    recipient_name: 'System Administrators',
    recipient_role: 'Admin',
    course_name: 'System Core',
    major: 'All Majors',
    message: 'Automated nightly database backup completed at 02:00 AM (Size: 42.8 MB, AES-256 encrypted). All services operational.',
    message_kh: 'ការបម្រុងទុកមូលដ្ឋានទិន្នន័យស្វ័យប្រវត្តិនារាត្រី វេលាម៉ោង ០២:០០ ព្រឹក បានជោគជ័យ។ ប្រព័ន្ធដំណើរការប្រក្រតី។',
    created_at: '26 Sep 2026, 02:01',
    is_read: true,
    action_url: '/admin/auth-logs',
  }
]

const notificationList = ref<SystemNotificationItem[]>([
  ...(props.notifications && props.notifications.length > 0 ? (props.notifications as any) : defaultNotifications)
])

// Filters State
const searchQuery = ref('')
const selectedType = ref<string>('all')
const selectedStatus = ref<'all' | 'unread' | 'read'>('all')

// View Detail Modal State
const showDetailModal = ref(false)
const selectedItem = ref<SystemNotificationItem | null>(null)

// Toast Feedback
const toastMessage = ref('')
function showToast(msg: string) {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = ''
  }, 3500)
}

// 8 Canonical Notification Types metadata
const typeConfigs: Record<NotificationType, { label: string; label_kh: string; icon: string; badgeClass: string; iconBg: string }> = {
  'Announcement': {
    label: 'Announcement',
    label_kh: 'សេចក្តីប្រកាស',
    icon: '📢',
    badgeClass: 'bg-amber-500/20 text-amber-300 border-amber-500/40',
    iconBg: 'bg-amber-500/15 text-amber-400 border-amber-500/30'
  },
  'Course Approval': {
    label: 'Course Approval',
    label_kh: 'ការអនុម័ត Course',
    icon: '✅',
    badgeClass: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
    iconBg: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'
  },
  'Quiz Published': {
    label: 'Quiz Published',
    label_kh: 'Quiz ថ្មី',
    icon: '📝',
    badgeClass: 'bg-sky-500/20 text-sky-300 border-sky-500/40',
    iconBg: 'bg-sky-500/15 text-sky-400 border-sky-500/30'
  },
  'Assignment Due': {
    label: 'Assignment Due',
    label_kh: 'ផុតកំណត់កិច្ចការ',
    icon: '⏰',
    badgeClass: 'bg-rose-500/20 text-rose-300 border-rose-500/40',
    iconBg: 'bg-rose-500/15 text-rose-400 border-rose-500/30'
  },
  'Assignment Graded': {
    label: 'Assignment Graded',
    label_kh: 'កិច្ចការបានដាក់ពិន្ទុ',
    icon: '💯',
    badgeClass: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40',
    iconBg: 'bg-indigo-500/15 text-indigo-400 border-indigo-500/30'
  },
  'AI Recommendation': {
    label: 'AI Recommendation',
    label_kh: 'អនុសាសន៍ AI',
    icon: '🤖',
    badgeClass: 'bg-purple-500/20 text-purple-300 border-purple-500/40',
    iconBg: 'bg-purple-500/15 text-purple-400 border-purple-500/30'
  },
  'At-Risk Alert': {
    label: 'At-Risk Alert',
    label_kh: 'សិស្សប្រឈមហានិភ័យ',
    icon: '⚠️',
    badgeClass: 'bg-red-500/20 text-red-300 border-red-500/40 animate-pulse',
    iconBg: 'bg-red-500/15 text-red-400 border-red-500/30'
  },
  'System Notification': {
    label: 'System Notification',
    label_kh: 'ការជូនដំណឹងប្រព័ន្ធ',
    icon: '⚙️',
    badgeClass: 'bg-slate-500/20 text-slate-300 border-slate-500/40',
    iconBg: 'bg-slate-500/15 text-slate-400 border-slate-500/30'
  }
}

// Filtered Notifications computation
const filteredList = computed(() => {
  return notificationList.value.filter(item => {
    const q = searchQuery.value.toLowerCase().trim()
    const matchesSearch = !q ||
      item.title.toLowerCase().includes(q) ||
      item.title_kh.toLowerCase().includes(q) ||
      item.recipient_name.toLowerCase().includes(q) ||
      (item.course_name && item.course_name.toLowerCase().includes(q)) ||
      (item.major && item.major.toLowerCase().includes(q))

    const matchesType = selectedType.value === 'all' || item.type === selectedType.value
    const matchesStatus = 
      selectedStatus.value === 'all' ? true :
      selectedStatus.value === 'unread' ? !item.is_read :
      item.is_read

    return matchesSearch && matchesType && matchesStatus
  })
})

const unreadCount = computed(() => {
  return notificationList.value.filter(n => !n.is_read).length
})

// Actions
function openDetail(item: SystemNotificationItem) {
  selectedItem.value = item
  showDetailModal.value = true
  if (!item.is_read) {
    item.is_read = true
  }
}

function toggleReadStatus(item: SystemNotificationItem) {
  item.is_read = !item.is_read
  showToast(item.is_read ? '✅ Marked as Read' : '📌 Marked as Unread')
}

function markAllAsRead() {
  notificationList.value.forEach(n => {
    n.is_read = true
  })
  showToast('✅ All notifications marked as read!')
}

function markAllAsUnread() {
  notificationList.value.forEach(n => {
    n.is_read = false
  })
  showToast('📌 All notifications marked as unread!')
}
</script>

<template>
  <AdminLayout title="Communication — Notifications Hub">
    <div class="space-y-6 pb-12">
      <!-- Toast Alert -->
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
              <div class="p-3 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 text-indigo-400 shadow-lg shadow-indigo-500/10">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
              </div>
              <div>
                <h1 class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 via-sky-300 to-purple-300">
                  🔔 Communication → Notifications Hub
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                  ប្រមូលការជូនដំណឹងសំខាន់ៗពីប្រព័ន្ធ ឲ្យ Student និង Teacher មិនខកខានព័ត៌មានសំខាន់ (Unified Event Notification Engine)
                </p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="markAllAsRead"
              class="px-3.5 py-2 bg-indigo-600/30 hover:bg-indigo-600/50 text-indigo-300 border border-indigo-500/40 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
            >
              <span>✓ Mark All as Read</span>
            </button>
            <button
              @click="markAllAsUnread"
              class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-medium transition"
            >
              Mark All Unread
            </button>
          </div>
        </div>

        <!-- ── SUB-NAVIGATION TABS ── -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pt-1 text-xs">
          <Link
            href="/admin/notifications/announcements"
            class="px-4 py-2 rounded-xl bg-slate-900/80 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-700/50 flex items-center gap-2 shrink-0 transition-all"
          >
            <span>📢 Announcements</span>
          </Link>
          <Link
            href="/admin/notifications"
            class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30 flex items-center gap-2 shrink-0"
          >
            <span>🔔 Notifications Hub</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-white/20 text-white">{{ notificationList.length }}</span>
            <span v-if="unreadCount > 0" class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-500 text-white animate-pulse">
              {{ unreadCount }} Unread
            </span>
          </Link>
        </div>
      </div>

      <!-- ── 8 NOTIFICATION TYPES PILLS / SUMMARY STATS ── -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 text-xs">
        <div
          v-for="(cfg, typeKey) in typeConfigs"
          :key="typeKey"
          @click="selectedType = selectedType === typeKey ? 'all' : typeKey"
          :class="[
            selectedType === typeKey ? 'ring-2 ring-indigo-400 bg-slate-800' : 'bg-slate-800/70 hover:bg-slate-800',
            'border border-slate-700/60 p-3 rounded-xl cursor-pointer transition flex flex-col justify-between space-y-1'
          ]"
        >
          <div class="flex items-center justify-between">
            <span class="text-base">{{ cfg.icon }}</span>
            <span class="font-bold text-[10px] font-mono text-slate-400">
              {{ notificationList.filter(n => n.type === typeKey).length }}
            </span>
          </div>
          <p class="font-bold text-slate-200 text-[11px] truncate">{{ cfg.label }}</p>
          <span class="text-[10px] text-slate-400 truncate">{{ cfg.label_kh }}</span>
        </div>
      </div>

      <!-- ── FILTERS & SEARCH BAR ── -->
      <div class="bg-slate-800/80 border border-slate-700/60 p-4 rounded-2xl flex flex-col md:flex-row gap-3 items-center justify-between text-xs">
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
          <!-- Type Filter -->
          <select v-model="selectedType" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-slate-200 font-medium">
            <option value="all">Type: All 8 Types</option>
            <option v-for="(cfg, typeKey) in typeConfigs" :key="typeKey" :value="typeKey">
              {{ cfg.icon }} {{ cfg.label }} ({{ cfg.label_kh }})
            </option>
          </select>

          <!-- Status Filter -->
          <div class="flex items-center bg-slate-900 border border-slate-700 rounded-xl p-0.5">
            <button
              @click="selectedStatus = 'all'"
              :class="selectedStatus === 'all' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200'"
              class="px-3 py-1.5 rounded-lg transition"
            >
              All ({{ notificationList.length }})
            </button>
            <button
              @click="selectedStatus = 'unread'"
              :class="selectedStatus === 'unread' ? 'bg-rose-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200'"
              class="px-3 py-1.5 rounded-lg transition flex items-center gap-1"
            >
              <span>Unread</span>
              <span class="px-1 py-0.2 bg-white/20 rounded-full text-[9px]">{{ unreadCount }}</span>
            </button>
            <button
              @click="selectedStatus = 'read'"
              :class="selectedStatus === 'read' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200'"
              class="px-3 py-1.5 rounded-lg transition"
            >
              Read
            </button>
          </div>
        </div>

        <div class="w-full md:w-72">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="🔍 Search title, recipient, course..."
              class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-3 pr-8 py-2 text-slate-200 focus:outline-none focus:border-indigo-500"
            />
            <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-2.5 top-2 text-slate-500 hover:text-slate-300">✕</button>
          </div>
        </div>
      </div>

      <!-- ── NOTIFICATIONS LIST TABLE (Official: Title | Type | Recipient | Created Date | Status | Action) ── -->
      <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-900/90 border-b border-slate-700 text-slate-400 uppercase font-semibold tracking-wider">
                <th class="p-3.5 pl-4">Title (ចំណងជើង)</th>
                <th class="p-3.5">Type (ប្រភេទ)</th>
                <th class="p-3.5">Recipient (អ្នកទទួល)</th>
                <th class="p-3.5">Created Date (ថ្ងៃបង្កើត)</th>
                <th class="p-3.5">Status</th>
                <th class="p-3.5 pr-4 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/60 text-slate-300">
              <tr
                v-for="item in filteredList"
                :key="item.id"
                :class="[
                  !item.is_read ? 'bg-indigo-950/20 hover:bg-indigo-950/30' : 'hover:bg-slate-700/30',
                  'transition'
                ]"
              >
                <!-- Title -->
                <td class="p-3.5 pl-4 max-w-sm">
                  <div class="flex items-start gap-2.5">
                    <!-- Unread dot -->
                    <span v-if="!item.is_read" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 mt-1.5 shadow-sm shadow-rose-500/50" title="Unread"></span>
                    <span v-else class="w-2 h-2 rounded-full bg-transparent shrink-0 mt-1.5"></span>

                    <div>
                      <div class="font-bold" :class="!item.is_read ? 'text-white' : 'text-slate-200'">
                        {{ item.title }}
                      </div>
                      <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-1 font-sans">
                        {{ item.title_kh }}
                      </div>
                      <div v-if="item.course_name" class="text-[10px] text-indigo-400 font-mono mt-0.5">
                        📚 {{ item.course_name }} <span v-if="item.major">({{ item.major }})</span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Type Badge -->
                <td class="p-3.5">
                  <span
                    :class="typeConfigs[item.type]?.badgeClass || 'bg-slate-700 text-slate-300'"
                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg border inline-flex items-center gap-1.5 whitespace-nowrap"
                  >
                    <span>{{ typeConfigs[item.type]?.icon }}</span>
                    <span>{{ item.type }}</span>
                  </span>
                </td>

                <!-- Recipient -->
                <td class="p-3.5 font-medium whitespace-nowrap">
                  <div class="flex items-center gap-1.5">
                    <span class="text-slate-400">
                      <span v-if="item.recipient_role === 'Student'">👨‍🎓</span>
                      <span v-else-if="item.recipient_role === 'Teacher'">👨‍🏫</span>
                      <span v-else-if="item.recipient_role === 'Admin'">🛡️</span>
                      <span v-else>👥</span>
                    </span>
                    <div>
                      <span class="text-slate-200 block">{{ item.recipient_name }}</span>
                      <span class="text-[10px] text-slate-400 block font-mono">
                        {{ item.recipient_code || item.recipient_role }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Created Date -->
                <td class="p-3.5 font-mono text-slate-400 whitespace-nowrap text-[11px]">
                  {{ item.created_at }}
                </td>

                <!-- Status (Read / Unread) -->
                <td class="p-3.5 whitespace-nowrap">
                  <button
                    @click="toggleReadStatus(item)"
                    :class="[
                      item.is_read 
                        ? 'bg-slate-700/40 text-slate-400 hover:text-slate-200 border-slate-600' 
                        : 'bg-rose-500/20 text-rose-300 border-rose-500/40 font-bold'
                    ]"
                    class="px-2.5 py-1 text-[10px] rounded-lg border transition inline-flex items-center gap-1"
                    title="Click to toggle Read / Unread"
                  >
                    <span v-if="item.is_read">✓ Read</span>
                    <span v-else>● Unread</span>
                  </button>
                </td>

                <!-- Action (View) -->
                <td class="p-3.5 pr-4 text-right space-x-1.5 whitespace-nowrap">
                  <button
                    @click="openDetail(item)"
                    class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition font-bold"
                    title="View Notification Details"
                  >
                    👁 View
                  </button>
                </td>
              </tr>

              <tr v-if="filteredList.length === 0">
                <td colspan="6" class="p-8 text-center text-slate-500">
                  មិនមានការជូនដំណឹងដែលត្រូវនឹងលក្ខខណ្ឌស្វែងរកទេ។ (No notifications found for selected filters.)
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── VIEW NOTIFICATION DETAIL MODAL ── -->
    <div v-if="showDetailModal && selectedItem" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-xl p-6 shadow-2xl space-y-4 text-xs text-slate-200">
        <!-- Modal Header -->
        <div class="flex justify-between items-start border-b border-slate-800 pb-3">
          <div class="flex items-center gap-3">
            <span class="text-2xl p-2 rounded-xl" :class="typeConfigs[selectedItem.type]?.iconBg">
              {{ typeConfigs[selectedItem.type]?.icon }}
            </span>
            <div>
              <span
                :class="typeConfigs[selectedItem.type]?.badgeClass"
                class="px-2 py-0.5 text-[10px] font-bold rounded border inline-block"
              >
                {{ selectedItem.type }} ({{ typeConfigs[selectedItem.type]?.label_kh }})
              </span>
              <h3 class="text-sm font-bold text-white mt-1">{{ selectedItem.title }}</h3>
              <p class="text-slate-400 text-[11px]">{{ selectedItem.title_kh }}</p>
            </div>
          </div>
          <button @click="showDetailModal = false" class="text-slate-400 hover:text-white text-base">✕</button>
        </div>

        <!-- Meta Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 bg-slate-950 p-3 rounded-xl border border-slate-800">
          <div>
            <span class="text-slate-500 block text-[10px]">Recipient (អ្នកទទួល)</span>
            <span class="font-bold text-white">{{ selectedItem.recipient_name }}</span>
            <span class="text-[10px] text-slate-400 block font-mono">({{ selectedItem.recipient_role }})</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[10px]">Course / Context</span>
            <span class="font-bold text-white">{{ selectedItem.course_name || 'System Level' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block text-[10px]">Timestamp</span>
            <span class="font-bold text-white font-mono">{{ selectedItem.created_at }}</span>
          </div>
        </div>

        <!-- Notification Message -->
        <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 space-y-2">
          <span class="font-bold text-indigo-400 uppercase text-[10px] tracking-wider">ខ្លឹមសារការជូនដំណឹង (Message Body):</span>
          <p class="text-slate-200 leading-relaxed">{{ selectedItem.message }}</p>
          <p class="text-slate-400 text-[11px] pt-2 border-t border-slate-800/80">{{ selectedItem.message_kh }}</p>
        </div>

        <!-- Extra Meta / Action URL -->
        <div v-if="selectedItem.extra_meta" class="p-3 bg-purple-950/20 border border-purple-500/30 rounded-xl space-y-1 text-purple-300">
          <span class="text-[10px] uppercase font-bold text-purple-400 block">AI & System Diagnostic Traces:</span>
          <div class="grid grid-cols-2 gap-2 text-[11px] font-mono">
            <div v-for="(v, k) in selectedItem.extra_meta" :key="k">
              <strong class="capitalize">{{ k }}:</strong> {{ v }}
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-between items-center pt-2 border-t border-slate-800">
          <button
            @click="toggleReadStatus(selectedItem)"
            class="text-xs text-slate-400 hover:text-slate-200 flex items-center gap-1.5"
          >
            <span>Status:</span>
            <span :class="selectedItem.is_read ? 'text-emerald-400 font-bold' : 'text-rose-400 font-bold'">
              {{ selectedItem.is_read ? 'Read ✓' : 'Unread ●' }}
            </span>
            <span class="text-[10px] text-indigo-400 underline">(toggle)</span>
          </button>

          <div class="flex items-center gap-2">
            <Link
              v-if="selectedItem.action_url"
              :href="selectedItem.action_url"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition"
            >
              Go to Related Page →
            </Link>
            <button @click="showDetailModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
