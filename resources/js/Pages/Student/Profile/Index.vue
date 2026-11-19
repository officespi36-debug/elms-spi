<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import QRCode from 'qrcode'

interface ProfileData {
  id: number
  name: string
  name_kh?: string
  email: string
  student_id?: string
  avatar_url?: string | null
  faculty?: string
  faculty_kh?: string
  department?: string
  department_kh?: string
  major?: string
  major_kh?: string
  major_id?: number | null
  phone?: string
  gender?: string
  dob?: string
  telegram_username?: string
  study_type?: string
  study_type_kh?: string
  degree_level?: string
  degree_level_kh?: string
  bio?: string
  semester?: string
  status?: string
  language?: string
  email_notifications?: boolean
  app_notifications?: boolean
}

interface ActivityLogItem {
  id: string
  type: string
  action: string
  action_kh?: string
  detail: string
  detail_kh?: string
  time: string
  timestamp?: string
}

interface MajorOption {
  id: number
  name: string
  name_kh?: string
  code?: string
  department_id?: number
}

const props = defineProps<{
  profile: ProfileData
  availableMajors?: MajorOption[]
  activityLogs?: ActivityLogItem[]
}>()

const page = usePage<any>()

// Language detection
const currentLang = computed<'km' | 'en'>(() => {
  const pLang = page.props?.auth?.user?.preferences?.locale || page.props?.auth?.user?.preferences?.language
  if (pLang === 'en' || pLang === 'km') return pLang
  const local = localStorage.getItem('elms_lang')
  return local === 'en' ? 'en' : 'km'
})

// Tab Navigation state
const validTabs = ['id-card', 'personal', 'academic', 'security', 'history']
const activeTab = ref('personal')

const setTab = (tab: string) => {
  if (!validTabs.includes(tab)) return
  activeTab.value = tab
  try {
    const url = new URL(window.location.href)
    url.searchParams.set('tab', tab)
    window.history.replaceState({}, '', url.toString())
  } catch (e) {
    // ignore
  }
}

onMounted(() => {
  try {
    const urlParams = new URLSearchParams(window.location.search)
    const tabParam = urlParams.get('tab')
    if (tabParam && validTabs.includes(tabParam)) {
      activeTab.value = tabParam
    }
  } catch (e) {
    // ignore
  }
  generateIdCardQr()
})

// Local QR Code Generator
const qrCodeDataUrl = ref<string>('')
const generateIdCardQr = async () => {
  const code = props.profile?.student_id || ('STU' + String(props.profile?.id || 1).padStart(6, '0'))
  const verifyUrl = `https://spilms.tech/verify-certificate/${code}`
  try {
    qrCodeDataUrl.value = await QRCode.toDataURL(verifyUrl, {
      width: 200,
      margin: 1,
      color: {
        dark: '#0f172a',
        light: '#ffffff',
      },
    })
  } catch (err) {
    // Fallback QR SVG Data URL
    qrCodeDataUrl.value = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(verifyUrl)}`
  }
}

watch(() => props.profile?.student_id, () => {
  generateIdCardQr()
})

// Avatar Live Preview
const avatarPreview = ref<string | null>(props.profile?.avatar_url || null)
const avatarFileInput = ref<HTMLInputElement | null>(null)

const triggerAvatarPick = () => {
  avatarFileInput.value?.click()
}

const handleAvatarFileSelected = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files && input.files[0]) {
    const file = input.files[0]
    profileForm.avatar = file
    const reader = new FileReader()
    reader.onload = (e) => {
      avatarPreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

// Personal Profile Form
const profileSuccess = ref(false)
const profileForm = useForm({
  name: props.profile?.name || '',
  name_kh: props.profile?.name_kh || '',
  email: props.profile?.email || '',
  phone: props.profile?.phone || '',
  telegram_username: props.profile?.telegram_username || '',
  gender: props.profile?.gender || 'male',
  dob: props.profile?.dob || '',
  bio: props.profile?.bio || '',
  major_id: props.profile?.major_id || '',
  avatar: null as File | null,
})

// Keep form in sync when props update
watch(() => props.profile, (newP) => {
  if (newP) {
    profileForm.name = newP.name || ''
    profileForm.name_kh = newP.name_kh || ''
    profileForm.email = newP.email || ''
    profileForm.phone = newP.phone || ''
    profileForm.telegram_username = newP.telegram_username || ''
    profileForm.gender = newP.gender || 'male'
    profileForm.dob = newP.dob || ''
    profileForm.bio = newP.bio || ''
    profileForm.major_id = newP.major_id || ''
    if (newP.avatar_url && !profileForm.avatar) {
      avatarPreview.value = newP.avatar_url
    }
  }
}, { deep: true })

const saveProfile = () => {
  profileSuccess.value = false
  profileForm.post('/student/profile', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      profileSuccess.value = true
      setTimeout(() => {
        profileSuccess.value = false
      }, 5000)
    },
  })
}

// Password Form
const passwordSuccess = ref(false)
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const updatePassword = () => {
  passwordSuccess.value = false
  passwordForm.post('/student/profile/password', {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
      passwordSuccess.value = true
      setTimeout(() => {
        passwordSuccess.value = false
      }, 5000)
    },
  })
}

// Print / Save ID Card
const printIdCard = () => {
  window.print()
}

// Activity logs with fallbacks
const displayLogs = computed(() => {
  if (props.activityLogs && props.activityLogs.length > 0) {
    return props.activityLogs
  }
  return [
    {
      id: 'demo_1',
      type: 'lesson',
      action: 'Watched Lesson Video',
      action_kh: 'បានបញ្ចប់មេរៀន',
      detail: 'Chapter 2.3 — Operators in C (12 min)',
      detail_kh: 'ជំពូក ២.៣ — ប្រមាណវិធីក្នុងភាសា C (១២ នាទី)',
      time: 'Today at 09:30 AM',
      icon: '🎬',
    },
    {
      id: 'demo_2',
      type: 'quiz',
      action: 'Completed Practice Quiz',
      action_kh: 'បានធ្វើកម្រងសំណួរ',
      detail: 'Module 2 Drill — Score 85%',
      detail_kh: 'តេស្តសមត្ថភាពម៉ូឌុល ២ — ពិន្ទុ ៨៥%',
      time: 'Yesterday at 04:15 PM',
      icon: '✅',
    },
    {
      id: 'demo_3',
      type: 'assignment',
      action: 'Submitted Assignment',
      action_kh: 'បានបញ្ជូនកិច្ចការផ្ទះ',
      detail: 'Assignment 1 — Linked List in C',
      detail_kh: 'កិច្ចការទី ១ — Linked List ក្នុងភាសា C',
      time: 'Aug 12, 2026 at 02:00 PM',
      icon: '📝',
    },
    {
      id: 'demo_4',
      type: 'payment',
      action: 'Payment Verified (ABA KHQR)',
      action_kh: 'ការបង់ប្រាក់ត្រូវបានបញ្ជាក់ (ABA KHQR)',
      detail: 'Enrolled in Data Structures & Algorithms ($12.00)',
      detail_kh: 'ចុះឈ្មោះវគ្គ Data Structures & Algorithms ($12.00)',
      time: 'Aug 10, 2026 at 11:30 AM',
      icon: '💳',
    },
    {
      id: 'demo_5',
      type: 'certificate',
      action: 'Certificate Issued',
      action_kh: 'ទទួលបានវិញ្ញាបនបត្រ',
      detail: 'C Programming Basics Certificate (ID: CERT-2026-089)',
      detail_kh: 'វិញ្ញាបនបត្រមូលដ្ឋានគ្រឹះ C Programming (កូដ: CERT-2026-089)',
      time: 'Aug 05, 2026 at 10:00 AM',
      icon: '🏆',
    },
  ]
})

const getLogIcon = (type: string) => {
  switch (type) {
    case 'lesson': return '🎬'
    case 'quiz': return '✅'
    case 'assignment': return '📝'
    case 'payment': return '💳'
    case 'certificate': return '🏆'
    default: return '📌'
  }
}
</script>

<template>
  <StudentLayout :title="currentLang === 'km' ? 'ការកំណត់គណនីនិស្សិត — Profile' : 'Student Account & Profile Settings'">
    <div class="space-y-6 max-w-7xl mx-auto">
      
      <!-- Top Profile Hero Banner -->
      <div class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 rounded-3xl p-6 md:p-8 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <!-- Background Ambient Glow -->
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex items-center gap-5 relative z-10">
          <!-- Avatar with Camera Change Trigger -->
          <div class="relative shrink-0 group">
            <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl md:rounded-3xl overflow-hidden bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-500 text-white font-extrabold flex items-center justify-center text-3xl shadow-xl ring-4 ring-indigo-500/30">
              <img
                v-if="avatarPreview"
                :src="avatarPreview"
                :alt="profile.name"
                class="w-full h-full object-cover"
              />
              <span v-else>
                {{ profile.name ? profile.name.charAt(0).toUpperCase() : 'S' }}
              </span>
            </div>
            
            <!-- Quick Upload Avatar Overlay Button -->
            <button
              type="button"
              @click="triggerAvatarPick"
              title="Change Photo"
              class="absolute inset-0 bg-black/50 text-white opacity-0 group-hover:opacity-100 rounded-2xl md:rounded-3xl flex flex-col items-center justify-center gap-1 text-[11px] font-bold transition-opacity backdrop-blur-xs cursor-pointer"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{{ currentLang === 'km' ? 'ប្តូររូប' : 'Upload' }}</span>
            </button>

            <!-- Online Indicator -->
            <span class="absolute bottom-1 right-1 w-4 h-4 bg-emerald-500 rounded-full ring-2 ring-slate-900 shadow"></span>
          </div>

          <div class="space-y-1 relative z-10">
            <div class="flex flex-wrap items-center gap-2.5">
              <h1 class="text-xl md:text-2xl font-black text-white">
                {{ profile.name }}
              </h1>
              <span v-if="profile.name_kh" class="text-sm font-semibold text-indigo-300">
                ({{ profile.name_kh }})
              </span>
              <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[11px] font-bold inline-flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                {{ currentLang === 'km' ? 'និស្សិតផ្លូវការ' : 'Online' }}
              </span>
            </div>
            
            <p class="text-xs text-indigo-200 font-medium">
              ID: <span class="font-mono font-bold">{{ profile.student_id || 'STU-2024-089' }}</span>
              <span v-if="profile.major || profile.major_kh"> • {{ currentLang === 'km' ? (profile.major_kh || profile.major) : profile.major }}</span>
            </p>
            
            <p class="text-[11px] text-slate-400">
              <span v-if="profile.faculty || profile.faculty_kh">{{ currentLang === 'km' ? (profile.faculty_kh || profile.faculty) : profile.faculty }} • </span>
              {{ profile.semester }}
            </p>
          </div>
        </div>

        <!-- Quick Status Card -->
        <div class="px-5 py-3.5 bg-slate-900/90 border border-slate-800 rounded-2xl flex items-center gap-6 shrink-0 relative z-10">
          <div>
            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
              {{ currentLang === 'km' ? 'ស្ថានភាពគណនី' : 'Account Status' }}
            </p>
            <p class="text-xs font-bold text-emerald-400 mt-0.5 flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              {{ currentLang === 'km' ? 'និស្សិតកំពុងសិក្សា' : 'Active Enrolled Student' }}
            </p>
          </div>
          <div class="border-l border-slate-800 pl-6">
            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
              {{ currentLang === 'km' ? 'គ្រឹះស្ថានសិក្សា' : 'Institute' }}
            </p>
            <p class="text-xs font-bold text-slate-200 mt-0.5">
              {{ currentLang === 'km' ? 'វិទ្យាស្ថាន សន្តប៉ូល' : 'Saint Paul Institute' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs (Light & Dark Mode Clean Contrast) -->
      <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto custom-scrollbar">
        <button
          type="button"
          @click="setTab('id-card')"
          :class="[
            activeTab === 'id-card'
              ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60',
            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5'
          ]"
        >
          <span>🪪</span>
          <span>1. {{ currentLang === 'km' ? 'កាតនិស្សិតឌីជីថល' : 'Digital Student ID' }}</span>
        </button>

        <button
          type="button"
          @click="setTab('personal')"
          :class="[
            activeTab === 'personal'
              ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60',
            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5'
          ]"
        >
          <span>👤</span>
          <span>2. {{ currentLang === 'km' ? 'ព័ត៌មានផ្ទាល់ខ្លួន' : 'Personal Information' }}</span>
        </button>

        <button
          type="button"
          @click="setTab('academic')"
          :class="[
            activeTab === 'academic'
              ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60',
            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5'
          ]"
        >
          <span>🎓</span>
          <span>3. {{ currentLang === 'km' ? 'ព័ត៌មានការសិក្សា' : 'Academic Information' }}</span>
        </button>

        <button
          type="button"
          @click="setTab('security')"
          :class="[
            activeTab === 'security'
              ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60',
            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5'
          ]"
        >
          <span>🔒</span>
          <span>4. {{ currentLang === 'km' ? 'សុវត្ថិភាព & ពាក្យសម្ងាត់' : 'Security & Password' }}</span>
        </button>

        <button
          type="button"
          @click="setTab('history')"
          :class="[
            activeTab === 'history'
              ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60',
            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5'
          ]"
        >
          <span>📜</span>
          <span>5. {{ currentLang === 'km' ? 'ប្រវត្តិនៃការរៀន' : 'Learning History & Logs' }}</span>
        </button>
      </div>

      <!-- TAB 1: Digital Student ID Card -->
      <div v-show="activeTab === 'id-card'" class="space-y-6">
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm dark:shadow-xl space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                <span>🪪 {{ currentLang === 'km' ? 'កាតនិស្សិតឌីជីថល (Digital Student ID Card)' : 'Digital Student ID Card' }}</span>
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ currentLang === 'km' ? 'កាតសម្គាល់ខ្លួនផ្លូវការសម្រាប់ការសិក្សា និងប្រើប្រាស់សេវាកម្មក្នុងវិទ្យាស្ថាន សន្តប៉ូល' : 'Official student identification card for academic activities and campus services at Saint Paul Institute' }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <button
                type="button"
                @click="printIdCard"
                class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>{{ currentLang === 'km' ? 'បោះពុម្ព / រក្សាទុក' : 'Print / Save' }}</span>
              </button>
              <span class="px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 text-xs font-bold shrink-0">
                ⚡ Live Verified Badge
              </span>
            </div>
          </div>

          <!-- Luxury NFC-Style ID Card -->
          <div id="student-digital-id-card" class="max-w-md mx-auto relative rounded-3xl p-6 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 border-2 border-indigo-500/40 shadow-2xl shadow-indigo-950/80 overflow-hidden text-white space-y-5">
            <!-- Holographic Shine Background Glow -->
            <div class="absolute -right-16 -top-16 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-48 h-48 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-indigo-800/50 pb-3 relative z-10">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-lg font-black shadow-md shadow-indigo-600/40">
                  🎓
                </div>
                <div>
                  <p class="text-[11px] font-black tracking-wider text-indigo-200 uppercase">SAINT PAUL INSTITUTE</p>
                  <p class="text-[9px] text-slate-400 font-medium">វិទ្យាស្ថាន សន្តប៉ូល • SPI E-LMS</p>
                </div>
              </div>
              <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[9px] font-bold uppercase tracking-wider">
                Official ID
              </span>
            </div>

            <!-- Middle: Avatar & Identity -->
            <div class="flex items-center gap-4 relative z-10">
              <div class="relative shrink-0">
                <div class="w-20 h-20 rounded-2xl overflow-hidden bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-2xl flex items-center justify-center shadow-lg ring-2 ring-indigo-400/40">
                  <img
                    v-if="avatarPreview"
                    :src="avatarPreview"
                    :alt="profile.name"
                    class="w-full h-full object-cover"
                  />
                  <span v-else>
                    {{ profile.name ? profile.name.charAt(0).toUpperCase() : 'S' }}
                  </span>
                </div>
                <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center text-[10px] text-white font-bold ring-2 ring-slate-900">
                  ✓
                </div>
              </div>

              <div class="space-y-1">
                <h4 class="text-base font-black text-white leading-tight">
                  {{ profile.name }}
                  <span v-if="profile.name_kh" class="block text-xs font-semibold text-indigo-300">{{ profile.name_kh }}</span>
                </h4>
                <p class="text-xs text-indigo-300 font-extrabold font-mono">{{ profile.student_id || 'STU-2024-089' }}</p>
                <p class="text-[11px] text-slate-300 font-medium">{{ currentLang === 'km' ? (profile.major_kh || profile.major) : profile.major }}</p>
                <p class="text-[10px] text-slate-400">{{ currentLang === 'km' ? (profile.faculty_kh || profile.faculty) : profile.faculty }}</p>
              </div>
            </div>

            <!-- Bottom: Term & Dynamic QR Code -->
            <div class="pt-3 border-t border-indigo-800/50 flex items-end justify-between gap-4 relative z-10">
              <div class="space-y-1.5 text-[10px]">
                <div>
                  <span class="text-slate-400 block font-semibold uppercase">{{ currentLang === 'km' ? 'ឆ្នាំសិក្សា & ឆមាស:' : 'Academic Term:' }}</span>
                  <span class="text-emerald-400 font-extrabold">{{ profile.semester }}</span>
                </div>
                <div>
                  <span class="text-slate-400 block font-semibold uppercase">{{ currentLang === 'km' ? 'ស្ថានភាព:' : 'Status:' }}</span>
                  <span class="text-slate-200 font-bold">🟢 {{ currentLang === 'km' ? 'និស្សិតផ្លូវការកំពុងសិក្សា' : 'Active Enrolled Student' }}</span>
                </div>
              </div>

              <!-- High-Resolution Local QR Code -->
              <div class="bg-white p-1.5 rounded-2xl shrink-0 shadow-lg shadow-black/40">
                <img
                  v-if="qrCodeDataUrl"
                  :src="qrCodeDataUrl"
                  alt="Student Verification QR"
                  class="w-16 h-16 rounded-lg object-contain"
                />
                <div v-else class="w-16 h-16 flex items-center justify-center bg-slate-100 rounded-lg text-slate-400 text-xs">
                  QR
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: Personal Information -->
      <div v-show="activeTab === 'personal'" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm dark:shadow-xl space-y-6">
        
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
          <div>
            <h3 class="text-sm md:text-base font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
              <span>👤 {{ currentLang === 'km' ? 'ព័ត៌មានទំនាក់ទំនង & ព័ត៌មានលម្អិតរបស់និស្សិត' : 'Personal Contact & Profile Details' }}</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ currentLang === 'km' ? 'សូមកែសម្រួលព័ត៌មានផ្ទាល់ខ្លួនរបស់អ្នកឱ្យបានត្រឹមត្រូវសម្រាប់ការទាក់ទង និងកត់ត្រា' : 'Update your personal details accurately for institution records and communications' }}
            </p>
          </div>
        </div>

        <!-- Success Alert Message -->
        <div
          v-if="profileSuccess"
          class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between gap-3 animate-in fade-in"
        >
          <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ currentLang === 'km' ? 'ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ!' : 'Personal information updated successfully!' }}</span>
          </div>
          <button @click="profileSuccess = false" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 text-xs font-bold">✕</button>
        </div>

        <form @submit.prevent="saveProfile" class="space-y-5 max-w-3xl">
          
          <!-- Hidden File Input for Avatar Upload -->
          <input
            ref="avatarFileInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleAvatarFileSelected"
          />

          <!-- Avatar Upload Row -->
          <div class="flex items-center gap-5 p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200/80 dark:border-slate-800/80">
            <div class="w-16 h-16 rounded-2xl overflow-hidden bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-extrabold flex items-center justify-center text-xl shrink-0 shadow-md">
              <img
                v-if="avatarPreview"
                :src="avatarPreview"
                :alt="profile.name"
                class="w-full h-full object-cover"
              />
              <span v-else>
                {{ profile.name ? profile.name.charAt(0).toUpperCase() : 'S' }}
              </span>
            </div>
            <div class="space-y-1">
              <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                {{ currentLang === 'km' ? 'រូបថតគណនី (Profile Picture)' : 'Profile Picture' }}
              </p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                {{ currentLang === 'km' ? 'ទ្រង់ទ្រាយរូបភាព JPG, PNG, WEBP (អតិបរមា 5MB)' : 'Accepted formats: JPG, PNG, WEBP (Max 5MB)' }}
              </p>
              <button
                type="button"
                @click="triggerAvatarPick"
                class="mt-1 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-xs font-bold transition-all cursor-pointer inline-flex items-center gap-1.5"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <span>{{ currentLang === 'km' ? 'ជ្រើសរើសរូបថតថ្មី' : 'Choose New Photo' }}</span>
              </button>
            </div>
          </div>

          <!-- Name & Khmer Name -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'ឈ្មោះពេញជាអក្សរឡាតាំង (Full Name in English):' : 'Full Student Name (English):' }}
                <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="profileForm.name"
                type="text"
                required
                class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                :placeholder="currentLang === 'km' ? 'ឧ. Mengly Khun' : 'e.g. Mengly Khun'"
              />
              <p v-if="profileForm.errors.name" class="mt-1 text-[11px] text-rose-500 font-medium">
                {{ profileForm.errors.name }}
              </p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'ឈ្មោះជាភាសាខ្មែរ (Khmer Full Name):' : 'Khmer Full Name:' }}
              </label>
              <input
                v-model="profileForm.name_kh"
                type="text"
                class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-khmer"
                :placeholder="currentLang === 'km' ? 'ឧ. ម៉េងលី ឃុន' : 'e.g. ម៉េងលី ឃុន'"
              />
              <p v-if="profileForm.errors.name_kh" class="mt-1 text-[11px] text-rose-500 font-medium">
                {{ profileForm.errors.name_kh }}
              </p>
            </div>
          </div>

          <!-- Email & Phone -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'អាសយដ្ឋានអ៊ីមែល (Email Address):' : 'Email Address:' }}
                <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="profileForm.email"
                type="email"
                required
                class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                :placeholder="currentLang === 'km' ? 'ឧ. student@example.com' : 'e.g. student@example.com'"
              />
              <p v-if="profileForm.errors.email" class="mt-1 text-[11px] text-rose-500 font-medium">
                {{ profileForm.errors.email }}
              </p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'លេខទូរស័ព្ទ / តេឡេក្រាម (Phone / Telegram):' : 'Phone Number (Telegram):' }}
              </label>
              <input
                v-model="profileForm.phone"
                type="text"
                class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                :placeholder="currentLang === 'km' ? 'ឧ. 012 345 678' : 'e.g. 012 345 678'"
              />
              <p v-if="profileForm.errors.phone" class="mt-1 text-[11px] text-rose-500 font-medium">
                {{ profileForm.errors.phone }}
              </p>
            </div>
          </div>

          <!-- Telegram Username & Student ID Official -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'ឈ្មោះគណនី Telegram (@Username):' : 'Telegram @Username:' }}
              </label>
              <div class="relative">
                <span class="absolute left-3 top-3 text-slate-400 font-mono text-xs">@</span>
                <input
                  v-model="profileForm.telegram_username"
                  type="text"
                  class="w-full pl-8 bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                  placeholder="username"
                />
              </div>
              <p v-if="profileForm.errors.telegram_username" class="mt-1 text-[11px] text-rose-500 font-medium">
                {{ profileForm.errors.telegram_username }}
              </p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'អត្តលេខនិស្សិតផ្លូវការ (Student ID - Readonly):' : 'Official Student ID (Locked):' }}
              </label>
              <div class="relative">
                <input
                  :value="profile.student_id"
                  disabled
                  type="text"
                  class="w-full bg-slate-100 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs text-slate-500 dark:text-slate-400 font-mono cursor-not-allowed"
                />
                <span class="absolute right-3 top-3 text-slate-400 text-xs">🔒</span>
              </div>
            </div>
          </div>

          <!-- Gender & DOB -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'ភេទ (Gender):' : 'Gender:' }}
              </label>
              <select
                v-model="profileForm.gender"
                class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all cursor-pointer"
              >
                <option value="male">{{ currentLang === 'km' ? 'ប្រុស (Male)' : 'Male' }}</option>
                <option value="female">{{ currentLang === 'km' ? 'ស្រី (Female)' : 'Female' }}</option>
                <option value="other">{{ currentLang === 'km' ? 'ផ្សេងៗ (Other)' : 'Other' }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'ថ្ងៃខែឆ្នាំកំណើត (Date of Birth):' : 'Date of Birth:' }}
              </label>
              <input
                v-model="profileForm.dob"
                type="date"
                class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
              />
            </div>
          </div>

          <!-- Bio / About Me -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              {{ currentLang === 'km' ? 'អំពីខ្លួនខ្ញុំ / គោលដៅសិក្សា (Bio / About Me):' : 'Bio / About Me:' }}
            </label>
            <textarea
              v-model="profileForm.bio"
              rows="3"
              class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
              :placeholder="currentLang === 'km' ? 'សរសេរសេចក្តីពិពណ៌នាខ្លីៗអំពីខ្លួនអ្នក និងគោលដៅសិក្សា...' : 'Write a short description about yourself, passions and learning goals...'"
            ></textarea>
            <p v-if="profileForm.errors.bio" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ profileForm.errors.bio }}
            </p>
          </div>

          <!-- Submit Button -->
          <div class="pt-2 flex items-center gap-4">
            <button
              type="submit"
              :disabled="profileForm.processing"
              class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition-all flex items-center gap-2 cursor-pointer"
            >
              <svg
                v-if="profileForm.processing"
                class="w-4 h-4 animate-spin text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ profileForm.processing ? (currentLang === 'km' ? 'កំពុងរក្សាទុក...' : 'Saving...') : (currentLang === 'km' ? 'រក្សាទុកការផ្លាស់ប្តូរ' : 'Save Changes') }}</span>
            </button>

            <span v-if="profileSuccess" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
              ✓ {{ currentLang === 'km' ? 'រក្សាទុកជោគជ័យ' : 'Changes Saved' }}
            </span>
          </div>

        </form>
      </div>

      <!-- TAB 3: Academic Information -->
      <div v-show="activeTab === 'academic'" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm dark:shadow-xl space-y-6">
        
        <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
          <h3 class="text-sm md:text-base font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
            <span>🎓 {{ currentLang === 'km' ? 'ព័ត៌មានការចុះឈ្មោះសិក្សាផ្លូវការ' : 'Academic Enrollment Information' }}</span>
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{ currentLang === 'km' ? 'ព័ត៌មានលម្អិតអំពីមហាវិទ្យាល័យ ដេប៉ាតឺម៉ង់ ជំនាញ និងឆមាសសិក្សានៅវិទ្យាស្ថាន សន្តប៉ូល' : 'Details regarding your faculty, department, specialization and academic progression at SPI' }}
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
          
          <!-- Faculty -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1.5">
            <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
              <span class="text-base">🏛️</span>
              <p class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold tracking-wider">
                {{ currentLang === 'km' ? 'មហាវិទ្យាល័យ (Faculty)' : 'Faculty' }}
              </p>
            </div>
            <p class="text-sm font-bold text-slate-900 dark:text-white">
              {{ currentLang === 'km' ? (profile.faculty_kh || profile.faculty) : profile.faculty }}
            </p>
            <p v-if="profile.faculty_kh && currentLang !== 'km'" class="text-[11px] text-slate-400 font-medium font-khmer">
              {{ profile.faculty_kh }}
            </p>
          </div>

          <!-- Department -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1.5">
            <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
              <span class="text-base">🏢</span>
              <p class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold tracking-wider">
                {{ currentLang === 'km' ? 'ដេប៉ាតឺម៉ង់ (Department)' : 'Department' }}
              </p>
            </div>
            <p class="text-sm font-bold text-slate-900 dark:text-white">
              {{ currentLang === 'km' ? (profile.department_kh || profile.department) : profile.department }}
            </p>
            <p v-if="profile.department_kh && currentLang !== 'km'" class="text-[11px] text-slate-400 font-medium font-khmer">
              {{ profile.department_kh }}
            </p>
          </div>

          <!-- Major Specialization -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1.5">
            <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400">
              <span class="text-base">💻</span>
              <p class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold tracking-wider">
                {{ currentLang === 'km' ? 'ជំនាញឯកទេស (Major Specialization)' : 'Major Specialization' }}
              </p>
            </div>
            <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
              {{ currentLang === 'km' ? (profile.major_kh || profile.major) : profile.major }}
            </p>
            <p v-if="profile.major_kh && currentLang !== 'km'" class="text-[11px] text-slate-400 font-medium font-khmer">
              {{ profile.major_kh }}
            </p>
          </div>

          <!-- Semester & Term -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1.5">
            <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
              <span class="text-base">📅</span>
              <p class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold tracking-wider">
                {{ currentLang === 'km' ? 'ឆ្នាំសិក្សា & ឆមាសបច្ចុប្បន្ន' : 'Academic Year & Semester' }}
              </p>
            </div>
            <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">
              {{ profile.semester }}
            </p>
          </div>

          <!-- Degree Level -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1.5">
            <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
              <span class="text-base">📜</span>
              <p class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold tracking-wider">
                {{ currentLang === 'km' ? 'កម្រិតសញ្ញាបត្រ (Degree Level)' : 'Degree Level' }}
              </p>
            </div>
            <p class="text-sm font-bold text-slate-900 dark:text-white">
              {{ currentLang === 'km' ? (profile.degree_level_kh || 'ថ្នាក់បរិញ្ញាបត្រ (B.Sc.)') : (profile.degree_level || 'Bachelor of Science (B.Sc.)') }}
            </p>
          </div>

          <!-- Study Type -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1.5">
            <div class="flex items-center gap-2 text-teal-600 dark:text-teal-400">
              <span class="text-base">⏰</span>
              <p class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold tracking-wider">
                {{ currentLang === 'km' ? 'ទម្រង់សិក្សា (Study Mode)' : 'Study Mode' }}
              </p>
            </div>
            <p class="text-sm font-bold text-slate-900 dark:text-white">
              {{ currentLang === 'km' ? (profile.study_type_kh || 'ពេញម៉ោង (Full-time)') : (profile.study_type || 'Full-time') }}
            </p>
          </div>

        </div>
      </div>

      <!-- TAB 4: Security & Change Password -->
      <div v-show="activeTab === 'security'" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm dark:shadow-xl space-y-6">
        
        <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
          <h3 class="text-sm md:text-base font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
            <span>🔒 {{ currentLang === 'km' ? 'សុវត្ថិភាពគណនី & ប្តូរពាក្យសម្ងាត់' : 'Security & Password Management' }}</span>
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{ currentLang === 'km' ? 'សូមធានាថាពាក្យសម្ងាត់របស់អ្នកមានយ៉ាងហោចណាស់ ៨ តួអក្សរ និងរក្សាវាជាការសម្ងាត់' : 'Ensure your password is at least 8 characters with a mix of letters, numbers, and symbols' }}
          </p>
        </div>

        <!-- Success Alert Message -->
        <div
          v-if="passwordSuccess"
          class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between gap-3 animate-in fade-in"
        >
          <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ currentLang === 'km' ? 'ពាក្យសម្ងាត់របស់អ្នកត្រូវបានប្តូរដោយជោគជ័យ!' : 'Your password has been changed successfully!' }}</span>
          </div>
          <button @click="passwordSuccess = false" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 text-xs font-bold">✕</button>
        </div>

        <form @submit.prevent="updatePassword" class="space-y-4 max-w-md">
          
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              {{ currentLang === 'km' ? 'ពាក្យសម្ងាត់បច្ចុប្បន្ន (Current Password):' : 'Current Password:' }}
              <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="passwordForm.current_password"
              type="password"
              required
              class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
              :placeholder="currentLang === 'km' ? 'បញ្ចូលពាក្យសម្ងាត់បច្ចុប្បន្ន' : 'Enter your current password'"
            />
            <p v-if="passwordForm.errors.current_password" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ passwordForm.errors.current_password }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              {{ currentLang === 'km' ? 'ពាក្យសម្ងាត់ថ្មី (New Password - យ៉ាងតិច ៨ តួអក្សរ):' : 'New Password (min 8 characters):' }}
              <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="passwordForm.password"
              type="password"
              required
              minlength="8"
              class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
              :placeholder="currentLang === 'km' ? 'បញ្ចូលពាក្យសម្ងាត់ថ្មី' : 'Enter your new password'"
            />
            <p v-if="passwordForm.errors.password" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ passwordForm.errors.password }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              {{ currentLang === 'km' ? 'បញ្ជាក់ពាក្យសម្ងាត់ថ្មី (Confirm New Password):' : 'Confirm New Password:' }}
              <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="passwordForm.password_confirmation"
              type="password"
              required
              class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
              :placeholder="currentLang === 'km' ? 'វាយបញ្ចូលពាក្យសម្ងាត់ថ្មីម្តងទៀត' : 'Re-type your new password'"
            />
            <p v-if="passwordForm.errors.password_confirmation" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ passwordForm.errors.password_confirmation }}
            </p>
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="passwordForm.processing"
              class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition-all flex items-center gap-2 cursor-pointer"
            >
              <svg
                v-if="passwordForm.processing"
                class="w-4 h-4 animate-spin text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ passwordForm.processing ? (currentLang === 'km' ? 'កំពុងប្តូរ...' : 'Updating...') : (currentLang === 'km' ? 'ប្តូរពាក្យសម្ងាត់' : 'Update Password') }}</span>
            </button>
          </div>

        </form>
      </div>

      <!-- TAB 5: Learning History & Logs -->
      <div v-show="activeTab === 'history'" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-sm dark:shadow-xl space-y-5">
        
        <div class="border-b border-slate-100 dark:border-slate-800 pb-4 flex items-center justify-between">
          <div>
            <h3 class="text-sm md:text-base font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
              <span>📜 {{ currentLang === 'km' ? 'ប្រវត្តិនៃការរៀន & កំណត់ហេតុសកម្មភាព' : 'Learning History & Activity Logs' }}</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ currentLang === 'km' ? 'កំណត់ត្រាពិតប្រាកដអំពីការទស្សនាមេរៀន ការប្រឡង ការដាក់កិច្ចការ និងការទូទាត់' : 'Verified audit logs for lesson completions, quiz attempts, assignments, and payments' }}
            </p>
          </div>
          <span class="px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold font-mono">
            {{ displayLogs.length }} {{ currentLang === 'km' ? 'សកម្មភាព' : 'Activities' }}
          </span>
        </div>

        <div class="space-y-3">
          <div
            v-for="log in displayLogs"
            :key="log.id"
            class="p-4 bg-slate-50 dark:bg-slate-950/50 hover:bg-slate-100/80 dark:hover:bg-slate-950/80 border border-slate-200/80 dark:border-slate-800 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs transition-colors"
          >
            <div class="flex items-center gap-3.5">
              <span class="text-xl p-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs shrink-0">
                {{ getLogIcon(log.type) }}
              </span>
              <div class="space-y-0.5">
                <p class="font-bold text-slate-900 dark:text-white">
                  {{ currentLang === 'km' ? (log.action_kh || log.action) : log.action }}
                </p>
                <p class="text-slate-500 dark:text-slate-400 text-[11px]">
                  {{ currentLang === 'km' ? (log.detail_kh || log.detail) : log.detail }}
                </p>
              </div>
            </div>
            <span class="text-slate-400 dark:text-slate-500 font-mono text-[11px] shrink-0 sm:self-center">
              {{ log.time }}
            </span>
          </div>
        </div>

      </div>

    </div>
  </StudentLayout>
</template>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  #student-digital-id-card,
  #student-digital-id-card * {
    visibility: visible;
  }
  #student-digital-id-card {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%) scale(1.1);
    box-shadow: none !important;
  }
}
</style>
