<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import QRCode from 'qrcode'

interface ProfileData {
  id: number
  name: string
  name_kh?: string
  email: string
  student_id: string
  avatar_url?: string | null
  phone?: string
  telegram_username?: string
  gender?: string
  dob?: string
  bio?: string
  status: string
  is_active: boolean
  language?: string
  completion_percent: number
}

interface IdCardData {
  card_no: string
  token: string
  verify_url: string
  issued_at?: string
  expires_at?: string
  status: 'active' | 'revoked' | 'pending'
  is_official: boolean
  approved_at?: string
  institution: string
}

interface AcademicData {
  faculty: string
  faculty_kh: string
  department: string
  department_kh: string
  major: string
  major_kh: string
  term: string
  degree_level: string
  study_type: string
  gpa?: string | null
  total_credits?: number | null
}

interface ActiveSessionItem {
  id: string
  is_current: boolean
  ip_address: string
  browser: string
  platform: string
  device: string
  last_active: string
}

interface LoginHistoryItem {
  id: number
  ip_address: string
  device: string
  browser: string
  status: string
  time: string
}

interface SecurityData {
  active_sessions: ActiveSessionItem[]
  login_history: LoginHistoryItem[]
  two_factor: {
    supported: boolean
    status_text: string
  }
}

interface ChangeRequestItem {
  id: number
  field_name: string
  old_value: string
  new_value: string
  reason: string
  status: 'pending' | 'approved' | 'rejected'
  created_at: string
}

interface ActivityLogItem {
  id: string
  type: 'lesson' | 'quiz' | 'assignment' | 'payment' | 'certificate'
  action: string
  action_kh?: string
  detail: string
  detail_kh?: string
  time: string
  timestamp?: string
}

const props = defineProps<{
  activeTab?: string
  profile: ProfileData
  idCard: IdCardData
  academic: AcademicData
  security: SecurityData
  changeRequests?: ChangeRequestItem[]
  activityLogs?: ActivityLogItem[]
}>()

const page = usePage<any>()

// 1. Language detection
const currentLang = computed<'km' | 'en'>(() => {
  const pLang = page.props?.auth?.user?.preferences?.locale || page.props?.auth?.user?.preferences?.language
  if (pLang === 'en' || pLang === 'km') return pLang
  const local = localStorage.getItem('elms_lang')
  return local === 'en' ? 'en' : 'km'
})

// 2. Tab Navigation
const validTabs = ['id-card', 'personal', 'academic', 'security', 'history']
const currentTab = ref(props.activeTab && validTabs.includes(props.activeTab) ? props.activeTab : 'id-card')

const selectTab = (tab: string) => {
  if (!validTabs.includes(tab)) return
  currentTab.value = tab
  try {
    const url = new URL(window.location.href)
    url.searchParams.set('tab', tab)
    window.history.replaceState({}, '', url.toString())
  } catch (e) {
  }
}

// Arrow Key Navigation for Accessibility
const onTabKeydown = (e: KeyboardEvent, index: number) => {
  if (e.key === 'ArrowRight') {
    const nextIndex = (index + 1) % validTabs.length
    selectTab(validTabs[nextIndex])
  } else if (e.key === 'ArrowLeft') {
    const prevIndex = (index - 1 + validTabs.length) % validTabs.length
    selectTab(validTabs[prevIndex])
  }
}

onMounted(() => {
  try {
    const urlParams = new URLSearchParams(window.location.search)
    const tabParam = urlParams.get('tab')
    if (tabParam && validTabs.includes(tabParam)) {
      currentTab.value = tabParam
    }
  } catch (e) {
  }
  generateIdCardQr()
})

// 3. Digital ID Card: Flip & Local QR Code
const isCardFlipped = ref(false)
const qrDataUrl = ref<string>('')
const isRegeneratingToken = ref(false)
const linkCopied = ref(false)

const toggleCardFlip = () => {
  isCardFlipped.value = !isCardFlipped.value
}

const generateIdCardQr = async () => {
  if (!props.idCard?.verify_url) return
  try {
    qrDataUrl.value = await QRCode.toDataURL(props.idCard.verify_url, {
      width: 256,
      margin: 1,
      color: {
        dark: '#0f172a',
        light: '#ffffff',
      },
    })
  } catch (err) {
    qrDataUrl.value = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(props.idCard.verify_url)}`
  }
}

watch(() => props.idCard?.verify_url, () => {
  generateIdCardQr()
})

const copyVerifyLink = () => {
  if (!props.idCard?.verify_url) return
  navigator.clipboard.writeText(props.idCard.verify_url).then(() => {
    linkCopied.value = true
    setTimeout(() => {
      linkCopied.value = false
    }, 3000)
  })
}

const triggerRegenerateToken = () => {
  if (!confirm(currentLang.value === 'km' ? 'តើអ្នកពិតជាចង់បង្កើតកូដ QR ថ្មីមែនទេ? តំណផ្ទៀងផ្ទាត់ចាស់នឹងលែងដំណើរការ។' : 'Are you sure you want to regenerate this ID card QR token? The previous verification link will become permanently invalid.')) {
    return
  }
  isRegeneratingToken.value = true
  router.post('/student/profile/id-card/regenerate', {}, {
    preserveScroll: true,
    onFinish: () => {
      isRegeneratingToken.value = false
    },
  })
}

const printIdCard = () => {
  window.print()
}

// 4. Personal Tab: Editable fields & Instant Avatar Upload
const avatarPreview = ref<string | null>(props.profile?.avatar_url || null)
const avatarInputRef = ref<HTMLInputElement | null>(null)
const personalSuccess = ref(false)

const personalForm = useForm({
  name_kh: props.profile?.name_kh || '',
  phone: props.profile?.phone || '',
  telegram_username: props.profile?.telegram_username || '',
  gender: props.profile?.gender || 'male',
  dob: props.profile?.dob || '',
  bio: props.profile?.bio || '',
  avatar: null as File | null,
})

const triggerAvatarPick = () => {
  avatarInputRef.value?.click()
}

const onAvatarSelected = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    if (file.size > 2 * 1024 * 1024) {
      alert(currentLang.value === 'km' ? 'ទំហំរូបភាពមិនត្រូវលើសពី 2MB ឡើយ' : 'Image size must not exceed 2MB')
      return
    }
    personalForm.avatar = file
    const reader = new FileReader()
    reader.onload = (e) => {
      avatarPreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

const savePersonalInfo = () => {
  personalSuccess.value = false
  personalForm.post('/student/profile', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      personalSuccess.value = true
      personalForm.avatar = null
      setTimeout(() => {
        personalSuccess.value = false
      }, 5000)
    },
  })
}

// 5. Correction Request Modal for Locked Fields
const isCorrectionModalOpen = ref(false)
const correctionField = ref<'name' | 'name_kh' | 'student_code' | 'major' | 'dob'>('name')
const correctionForm = useForm({
  field_name: 'name',
  new_value: '',
  reason: '',
})

const openCorrectionModal = (field: 'name' | 'name_kh' | 'student_code' | 'major' | 'dob') => {
  correctionField.value = field
  correctionForm.field_name = field
  correctionForm.new_value = ''
  correctionForm.reason = ''
  isCorrectionModalOpen.value = true
}

const submitCorrectionRequest = () => {
  correctionForm.post('/student/profile/change-request', {
    preserveScroll: true,
    onSuccess: () => {
      isCorrectionModalOpen.value = false
      correctionForm.reset()
    },
  })
}

// 6. Security Tab: Password, Active Sessions, and Sign Out Others
const passwordSuccess = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

// Password Strength Computation
const passwordStrength = computed(() => {
  const p = passwordForm.password
  if (!p) return { score: 0, text: '', color: 'bg-slate-200' }
  let score = 0
  if (p.length >= 8) score++
  if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++

  if (score <= 1) return { score: 25, text: currentLang.value === 'km' ? 'ខ្សោយ' : 'Weak', color: 'bg-rose-500' }
  if (score === 2) return { score: 50, text: currentLang.value === 'km' ? 'មធ្យម' : 'Medium', color: 'bg-amber-500' }
  if (score === 3) return { score: 75, text: currentLang.value === 'km' ? 'ល្អ' : 'Good', color: 'bg-blue-500' }
  return { score: 100, text: currentLang.value === 'km' ? 'រឹងមាំខ្លាំង' : 'Strong', color: 'bg-emerald-500' }
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

// Logout Other Devices Modal
const isLogoutOthersModalOpen = ref(false)
const logoutOthersForm = useForm({
  password: '',
})

const submitLogoutOthers = () => {
  logoutOthersForm.post('/student/profile/sessions/logout-others', {
    preserveScroll: true,
    onSuccess: () => {
      isLogoutOthersModalOpen.value = false
      logoutOthersForm.reset()
    },
  })
}

// 7. History Tab Filters
const selectedHistoryType = ref<'all' | 'lesson' | 'quiz' | 'assignment' | 'payment' | 'certificate'>('all')

const filteredHistoryLogs = computed(() => {
  const logs = props.activityLogs || []
  if (selectedHistoryType.value === 'all') return logs
  return logs.filter((l) => l.type === selectedHistoryType.value)
})

const getLogTypeBadge = (type: string) => {
  switch (type) {
    case 'lesson':
      return { label: currentLang.value === 'km' ? 'មេរៀន' : 'Lesson', class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20' }
    case 'quiz':
      return { label: currentLang.value === 'km' ? 'កម្រងសំណួរ' : 'Quiz', class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' }
    case 'assignment':
      return { label: currentLang.value === 'km' ? 'កិច្ចការ' : 'Assignment', class: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' }
    case 'payment':
      return { label: currentLang.value === 'km' ? 'ការបង់ប្រាក់' : 'Payment', class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' }
    case 'certificate':
      return { label: currentLang.value === 'km' ? 'វិញ្ញាបនបត្រ' : 'Certificate', class: 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/20' }
    default:
      return { label: 'General', class: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20' }
  }
}
</script>

<template>
  <StudentLayout :title="currentLang === 'km' ? 'គណនី & ការកំណត់ — Profile' : 'Student Account & Profile Settings'">
    <div class="space-y-6 max-w-6xl mx-auto pb-24">
      
      <!-- HEADER CARD: Clean white card, no harsh dark block, accurate branding -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6 transition-colors">
        
        <div class="flex items-center gap-5">
          <!-- Avatar with Clean Upload Trigger -->
          <div class="relative shrink-0 group">
            <div class="w-20 h-20 md:w-22 md:h-22 rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-800 border-2 border-indigo-500/30 text-indigo-600 dark:text-indigo-400 font-black flex items-center justify-center text-3xl shadow-xs">
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

            <!-- Upload Photo Trigger Overlay -->
            <button
              type="button"
              @click="triggerAvatarPick"
              :title="currentLang === 'km' ? 'ប្តូររូបថត' : 'Change Photo'"
              class="absolute inset-0 bg-slate-950/60 text-white opacity-0 group-hover:opacity-100 rounded-2xl flex flex-col items-center justify-center gap-1 text-[11px] font-bold transition-opacity cursor-pointer"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{{ currentLang === 'km' ? 'ប្តូររូប' : 'Upload' }}</span>
            </button>
          </div>

          <!-- Identity Details -->
          <div class="space-y-1.5">
            <div class="flex flex-wrap items-center gap-2.5">
              <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white">
                {{ profile.name }}
              </h1>
              <span v-if="profile.name_kh" class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                ({{ profile.name_kh }})
              </span>
              <!-- Single Accurate Status Chip -->
              <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[11px] font-bold inline-flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>{{ currentLang === 'km' ? 'និស្សិតកំពុងសិក្សា' : 'Active Enrolled Student' }}</span>
              </span>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
              ID: <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ profile.student_id }}</span>
              • <span>{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</span>
              • <span>{{ academic.term }}</span>
            </p>

            <!-- Profile Completion Progress Bar -->
            <div class="pt-1 flex items-center gap-3">
              <div class="w-32 bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                <div
                  class="bg-indigo-600 h-2 rounded-full transition-all duration-500"
                  :style="{ width: `${profile.completion_percent}%` }"
                ></div>
              </div>
              <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">
                {{ currentLang === 'km' ? 'ព័ត៌មានពេញលេញ' : 'Profile Complete' }}: {{ profile.completion_percent }}%
              </span>
            </div>
          </div>
        </div>

        <!-- Institute Brand Details -->
        <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 rounded-2xl flex items-center gap-5 shrink-0 self-start md:self-auto">
          <div>
            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
              {{ currentLang === 'km' ? 'គ្រឹះស្ថានឧត្តមសិក្សា' : 'Higher Education Institution' }}
            </p>
            <p class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-0.5">
              {{ idCard.institution }}
            </p>
          </div>
        </div>

      </div>

      <!-- TABS NAVIGATION: No numbers, single-tone Lucide-style icons, animated underline, ARIA roles -->
      <div
        role="tablist"
        aria-label="Profile Sections"
        class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto custom-scrollbar"
      >
        <!-- Tab: ID Card -->
        <button
          type="button"
          role="tab"
          :aria-selected="currentTab === 'id-card'"
          :tabindex="currentTab === 'id-card' ? 0 : -1"
          @click="selectTab('id-card')"
          @keydown="onTabKeydown($event, 0)"
          :class="[
            currentTab === 'id-card'
              ? 'bg-indigo-600 text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60',
            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <rect x="2" y="5" width="20" height="14" rx="3" />
            <line x1="2" y1="10" x2="22" y2="10" />
          </svg>
          <span>{{ currentLang === 'km' ? 'កាតនិស្សិតឌីជីថល' : 'Digital Student ID' }}</span>
        </button>

        <!-- Tab: Personal -->
        <button
          type="button"
          role="tab"
          :aria-selected="currentTab === 'personal'"
          :tabindex="currentTab === 'personal' ? 0 : -1"
          @click="selectTab('personal')"
          @keydown="onTabKeydown($event, 1)"
          :class="[
            currentTab === 'personal'
              ? 'bg-indigo-600 text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60',
            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span>{{ currentLang === 'km' ? 'ព័ត៌មានផ្ទាល់ខ្លួន' : 'Personal Information' }}</span>
        </button>

        <!-- Tab: Academic -->
        <button
          type="button"
          role="tab"
          :aria-selected="currentTab === 'academic'"
          :tabindex="currentTab === 'academic' ? 0 : -1"
          @click="selectTab('academic')"
          @keydown="onTabKeydown($event, 2)"
          :class="[
            currentTab === 'academic'
              ? 'bg-indigo-600 text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60',
            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
          </svg>
          <span>{{ currentLang === 'km' ? 'ព័ត៌មានការសិក្សា' : 'Academic Information' }}</span>
        </button>

        <!-- Tab: Security -->
        <button
          type="button"
          role="tab"
          :aria-selected="currentTab === 'security'"
          :tabindex="currentTab === 'security' ? 0 : -1"
          @click="selectTab('security')"
          @keydown="onTabKeydown($event, 3)"
          :class="[
            currentTab === 'security'
              ? 'bg-indigo-600 text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60',
            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0110 0v4" />
          </svg>
          <span>{{ currentLang === 'km' ? 'សុវត្ថិភាព & ពាក្យសម្ងាត់' : 'Security & Password' }}</span>
        </button>

        <!-- Tab: History -->
        <button
          type="button"
          role="tab"
          :aria-selected="currentTab === 'history'"
          :tabindex="currentTab === 'history' ? 0 : -1"
          @click="selectTab('history')"
          @keydown="onTabKeydown($event, 4)"
          :class="[
            currentTab === 'history'
              ? 'bg-indigo-600 text-white shadow-xs'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60',
            'px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
          </svg>
          <span>{{ currentLang === 'km' ? 'ប្រវត្តិនៃការរៀន' : 'Learning History & Logs' }}</span>
        </button>
      </div>

      <!-- TAB 1: DIGITAL STUDENT ID CARD (Flippable Card + Verification Actions) -->
      <div v-show="currentTab === 'id-card'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left: Interactive Card (Front & Back Flippable) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-xs flex flex-col items-center justify-center space-y-5">
          
          <div class="w-full flex items-center justify-between text-xs text-slate-500">
            <span class="font-bold">
              {{ isCardFlipped ? (currentLang === 'km' ? 'ផ្នែកខាងក្រោយកាត (Back Side)' : 'Card Back Side') : (currentLang === 'km' ? 'ផ្នែកខាងមុខកាត (Front Side)' : 'Card Front Side') }}
            </span>
            <button
              type="button"
              @click="toggleCardFlip"
              class="px-3 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[11px] transition-all cursor-pointer inline-flex items-center gap-1.5"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>{{ currentLang === 'km' ? 'ចុចបង្វិលកាត' : 'Flip Card' }}</span>
            </button>
          </div>

          <!-- CR80 Standard Proportional Card Container -->
          <div
            id="student-digital-id-card"
            @click="toggleCardFlip"
            @keydown.space.prevent="toggleCardFlip"
            @keydown.enter.prevent="toggleCardFlip"
            tabindex="0"
            role="button"
            :aria-label="currentLang === 'km' ? 'កាតនិស្សិតឌីជីថល ចុចដើម្បីបង្វិល' : 'Digital Student ID Card. Click to flip.'"
            class="w-full max-w-[420px] aspect-[85.6/54] rounded-3xl p-5 md:p-6 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 border-2 border-indigo-500/30 shadow-xl text-white relative overflow-hidden select-none cursor-pointer transition-transform duration-300 hover:scale-[1.01] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500"
          >
            <!-- Ambient Glow -->
            <div class="absolute -right-16 -top-16 w-44 h-44 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <!-- FRONT SIDE VIEW -->
            <div v-if="!isCardFlipped" class="h-full flex flex-col justify-between relative z-10">
              
              <!-- Card Top Row -->
              <div class="flex items-center justify-between border-b border-indigo-800/40 pb-2.5">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-sm font-black shadow-xs">
                    🎓
                  </div>
                  <div>
                    <p class="text-[10px] font-black tracking-widest text-indigo-200 uppercase">{{ idCard.institution }}</p>
                    <p class="text-[8px] text-slate-400 font-medium">វិទ្យាស្ថាន សន្តប៉ូល • SPI E-LMS</p>
                  </div>
                </div>
                <span
                  v-if="idCard.is_official"
                  class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[8px] font-bold uppercase tracking-wider"
                >
                  Official ID
                </span>
              </div>

              <!-- Card Middle: Avatar & Identification -->
              <div class="flex items-center gap-4 my-auto">
                <div class="w-16 h-16 rounded-2xl overflow-hidden bg-slate-800 border-2 border-indigo-400/40 text-white font-black text-xl flex items-center justify-center shrink-0 shadow-md">
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

                <div class="space-y-0.5">
                  <h2 class="text-sm md:text-base font-black text-white leading-tight">
                    {{ profile.name }}
                  </h2>
                  <p v-if="profile.name_kh" class="text-xs font-semibold text-indigo-300">{{ profile.name_kh }}</p>
                  <p class="text-xs font-mono font-bold text-indigo-200 mt-1">{{ profile.student_id }}</p>
                  <p class="text-[11px] text-slate-300 font-medium">{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</p>
                </div>
              </div>

              <!-- Card Bottom: Academic Term -->
              <div class="pt-2 border-t border-indigo-800/40 flex items-center justify-between text-[9px] text-slate-300">
                <div>
                  <span class="text-slate-400 uppercase font-semibold">Term: </span>
                  <span class="font-bold text-emerald-300">{{ academic.term }}</span>
                </div>
                <span class="text-[9px] font-mono text-indigo-300">CARD NO: {{ idCard.card_no }}</span>
              </div>

            </div>

            <!-- BACK SIDE VIEW -->
            <div v-else class="h-full flex flex-col justify-between relative z-10">
              
              <!-- Back Strip -->
              <div class="w-full h-7 bg-slate-950/90 rounded-lg border border-slate-800 -mx-1 flex items-center px-3">
                <span class="text-[8px] font-mono tracking-widest text-slate-500">OFFICIAL DIGITAL SECURITY TOKEN AUTHENTICATED</span>
              </div>

              <!-- QR & Terms -->
              <div class="flex items-center justify-between gap-4 py-1">
                <div class="space-y-1 text-[9px] text-slate-300 max-w-[210px] leading-relaxed">
                  <p class="font-bold text-indigo-300">ល័ក្ខខ័ណ្ឌប្រើប្រាស់ (Terms):</p>
                  <p class="text-slate-400">កាតនេះជាកម្មសិទ្ធិរបស់វិទ្យាស្ថាន សន្តប៉ូល។ ស្កេន QR Code ដើម្បីផ្ទៀងផ្ទាត់សុពលភាពតាមប្រព័ន្ធសុវត្ថិភាព។</p>
                  <p class="text-[8px] text-slate-500">Expires: {{ idCard.expires_at || '—' }}</p>
                </div>

                <!-- High-Resolution Local QR Code -->
                <div class="bg-white p-1.5 rounded-xl shrink-0 shadow-lg shadow-black/40">
                  <img
                    v-if="qrDataUrl"
                    :src="qrDataUrl"
                    alt="Official Student Verification QR"
                    class="w-16 h-16 rounded-md object-contain"
                  />
                  <div v-else class="w-16 h-16 flex items-center justify-center bg-slate-100 text-[10px] text-slate-500 font-mono">
                    QR
                  </div>
                </div>
              </div>

              <!-- Bottom Barcode Styling -->
              <div class="text-[8px] text-slate-400 font-mono flex items-center justify-between border-t border-indigo-800/40 pt-1.5">
                <span>SPI-VERIFIED-TOKEN</span>
                <span>{{ idCard.token.substring(0, 16) }}...</span>
              </div>

            </div>

          </div>

          <p class="text-[11px] text-slate-400 text-center">
            {{ currentLang === 'km' ? 'ចុចលើកាត ឬចុច Spacebar ដើម្បីត្រឡប់មើលផ្នែកខាងមុខ និងខាងក្រោយ' : 'Click card or press Spacebar to flip between front and back' }}
          </p>

        </div>

        <!-- Right: Validity Details & Action Panel -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
          
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ currentLang === 'km' ? 'សុពលភាព & ស្ថានភាពកាត' : 'Card Validity & Verification' }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ currentLang === 'km' ? 'គ្រប់គ្រងកាតសម្គាល់ខ្លួន និងផ្ទៀងផ្ទាត់តំណភ្ជាប់សាធារណៈ' : 'Manage your digital credentials and verification link' }}
            </p>
          </div>

          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-3 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ currentLang === 'km' ? 'លេខកាត:' : 'Card No:' }}</span>
              <span class="font-mono font-bold text-slate-900 dark:text-white">{{ idCard.card_no }}</span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ currentLang === 'km' ? 'ស្ថានភាពផ្លូវការ:' : 'Official Status:' }}</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                ● {{ idCard.is_official ? (currentLang === 'km' ? 'បានអនុម័តផ្លូវការ' : 'Officially Verified') : (currentLang === 'km' ? 'រង់ចាំការបញ្ជាក់' : 'Pending Approval') }}
              </span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ currentLang === 'km' ? 'កាលបរិច្ឆេទចេញ:' : 'Issued Date:' }}</span>
              <span class="font-medium text-slate-700 dark:text-slate-300">{{ idCard.issued_at || '—' }}</span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ currentLang === 'km' ? 'កាលបរិច្ឆេទផុតកំណត់:' : 'Expires:' }}</span>
              <span class="font-medium text-slate-700 dark:text-slate-300">{{ idCard.expires_at || '—' }}</span>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="space-y-2.5">
            
            <!-- Print CR80 Standard -->
            <button
              type="button"
              @click="printIdCard"
              class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              <span>{{ currentLang === 'km' ? 'បោះពុម្ពកាត (Print Standard CR80)' : 'Print ID Card (Standard CR80)' }}</span>
            </button>

            <!-- Copy Verify URL -->
            <button
              type="button"
              @click="copyVerifyLink"
              class="w-full py-2.5 px-4 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              <span>{{ linkCopied ? (currentLang === 'km' ? '✓ បានចម្លងតំណភ្ជាប់' : '✓ Link Copied') : (currentLang === 'km' ? 'ចម្លងតំណផ្ទៀងផ្ទាត់' : 'Copy Verification Link') }}</span>
            </button>

            <!-- Regenerate Token -->
            <button
              type="button"
              :disabled="isRegeneratingToken"
              @click="triggerRegenerateToken"
              class="w-full py-2.5 px-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-rose-50 dark:hover:bg-rose-950/20 text-rose-600 dark:text-rose-400 font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>{{ isRegeneratingToken ? (currentLang === 'km' ? 'កំពុងបង្កើត...' : 'Generating...') : (currentLang === 'km' ? 'បង្កើតកូដ QR ថ្មី (Regenerate)' : 'Regenerate Security QR') }}</span>
            </button>

          </div>

        </div>

      </div>

      <!-- TAB 2: PERSONAL INFORMATION (Sectioned Cards & Correction Requests) -->
      <div v-show="currentTab === 'personal'" class="space-y-6">
        
        <!-- Success Alert -->
        <div
          v-if="personalSuccess"
          class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between gap-3 animate-in fade-in"
        >
          <div class="flex items-center gap-2.5">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ currentLang === 'km' ? 'ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ!' : 'Personal information updated successfully!' }}</span>
          </div>
          <button @click="personalSuccess = false" class="text-emerald-600 dark:text-emerald-400 font-bold text-xs">✕</button>
        </div>

        <!-- Section 1: Locked Official Identification (Request Correction only) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-xs space-y-5">
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
              <span>🔒 {{ currentLang === 'km' ? 'ព័ត៌មានអត្តសញ្ញាណផ្លូវការ (ជាប់សោរ)' : 'Official Locked Records' }}</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ currentLang === 'km' ? 'ឈ្មោះ អត្តលេខនិស្សិត និងជំនាញត្រូវបានចាក់សោរដោយរដ្ឋបាល។ សូមចុច "ស្នើកែតម្រូវ" ប្រសិនបើមានកំហុសអក្ខរាវិរុទ្ធ។' : 'Full name, Student ID, and Program are verified by the registry. Click "Request Correction" to submit change requests.' }}
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            
            <!-- Full Name -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1 relative">
              <div class="flex items-center justify-between">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Full Name</span>
                <span class="text-slate-400 text-xs">🔒</span>
              </div>
              <p class="text-sm font-bold text-slate-900 dark:text-white">{{ profile.name }}</p>
              <button
                type="button"
                @click="openCorrectionModal('name')"
                class="mt-2 text-indigo-600 dark:text-indigo-400 hover:underline text-[11px] font-bold inline-flex items-center gap-1 cursor-pointer"
              >
                <span>{{ currentLang === 'km' ? 'ស្នើកែតម្រូវ' : 'Request Correction' }}</span>
              </button>
            </div>

            <!-- Student ID -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1 relative">
              <div class="flex items-center justify-between">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Official Student ID</span>
                <span class="text-slate-400 text-xs">🔒</span>
              </div>
              <p class="text-sm font-mono font-bold text-slate-900 dark:text-white">{{ profile.student_id }}</p>
              <button
                type="button"
                @click="openCorrectionModal('student_code')"
                class="mt-2 text-indigo-600 dark:text-indigo-400 hover:underline text-[11px] font-bold inline-flex items-center gap-1 cursor-pointer"
              >
                <span>{{ currentLang === 'km' ? 'ស្នើកែតម្រូវ' : 'Request Correction' }}</span>
              </button>
            </div>

            <!-- Academic Major -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1 relative">
              <div class="flex items-center justify-between">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Major Specialization</span>
                <span class="text-slate-400 text-xs">🔒</span>
              </div>
              <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</p>
              <button
                type="button"
                @click="openCorrectionModal('major')"
                class="mt-2 text-indigo-600 dark:text-indigo-400 hover:underline text-[11px] font-bold inline-flex items-center gap-1 cursor-pointer"
              >
                <span>{{ currentLang === 'km' ? 'ស្នើកែតម្រូវ' : 'Request Correction' }}</span>
              </button>
            </div>

          </div>

          <!-- Pending Correction Requests Table -->
          <div v-if="changeRequests && changeRequests.length > 0" class="pt-2 space-y-2">
            <p class="text-xs font-bold text-slate-700 dark:text-slate-300">
              {{ currentLang === 'km' ? 'សំណើកែតម្រូវដែលបានដាក់ស្នើ:' : 'Submitted Correction Requests:' }}
            </p>
            <div class="space-y-1.5">
              <div
                v-for="req in changeRequests"
                :key="req.id"
                class="p-3 bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-between text-xs"
              >
                <div>
                  <span class="font-bold text-slate-800 dark:text-slate-200 capitalize">{{ req.field_name }}</span>:
                  <span class="text-slate-600 dark:text-slate-400 ml-1">"{{ req.new_value }}"</span>
                  <span class="text-slate-400 text-[10px] ml-2">({{ req.created_at }})</span>
                </div>
                <span
                  :class="[
                    req.status === 'approved' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : '',
                    req.status === 'pending' ? 'bg-amber-500/10 text-amber-600 border-amber-500/20' : '',
                    req.status === 'rejected' ? 'bg-rose-500/10 text-rose-600 border-rose-500/20' : '',
                    'px-2 py-0.5 rounded-full text-[10px] font-bold border capitalize'
                  ]"
                >
                  {{ req.status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 2: Editable Personal Details Form -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ currentLang === 'km' ? 'ព័ត៌មានទំនាក់ទំនង & ជីវប្រវត្តិសង្ខេប' : 'Contact & Profile Details' }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ currentLang === 'km' ? 'លោកអ្នកអាចធ្វើបច្ចុប្បន្នភាពលេខទូរស័ព្ទ តេឡេក្រាម ភេទ និងជីវប្រវត្តិបានគ្រប់ពេលវេលា' : 'Update your contact information, phone, telegram, and bio at any time' }}
            </p>
          </div>

          <!-- Hidden File Input for Avatar -->
          <input
            ref="avatarInputRef"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="onAvatarSelected"
          />

          <form @submit.prevent="savePersonalInfo" class="space-y-4 max-w-2xl">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Khmer Name -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                  {{ currentLang === 'km' ? 'ឈ្មោះជាភាសាខ្មែរ:' : 'Full Name in Khmer:' }}
                </label>
                <input
                  v-model="personalForm.name_kh"
                  type="text"
                  class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-khmer"
                  placeholder="ឧ. ម៉េងលី ឃុន"
                />
              </div>

              <!-- Phone -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                  {{ currentLang === 'km' ? 'លេខទូរស័ព្ទ:' : 'Phone Number:' }}
                </label>
                <input
                  v-model="personalForm.phone"
                  type="text"
                  class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                  placeholder="012 345 678"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Telegram Username -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                  {{ currentLang === 'km' ? 'គណនី Telegram (@Username):' : 'Telegram Username:' }}
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-3 text-slate-400 font-mono text-xs">@</span>
                  <input
                    v-model="personalForm.telegram_username"
                    type="text"
                    class="w-full pl-8 bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                    placeholder="username"
                  />
                </div>
              </div>

              <!-- Gender -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                  {{ currentLang === 'km' ? 'ភេទ:' : 'Gender:' }}
                </label>
                <select
                  v-model="personalForm.gender"
                  class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all cursor-pointer"
                >
                  <option value="male">{{ currentLang === 'km' ? 'ប្រុស (Male)' : 'Male' }}</option>
                  <option value="female">{{ currentLang === 'km' ? 'ស្រី (Female)' : 'Female' }}</option>
                  <option value="other">{{ currentLang === 'km' ? 'ផ្សេងៗ (Other)' : 'Other' }}</option>
                </select>
              </div>
            </div>

            <!-- Date of Birth -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'ថ្ងៃខែឆ្នាំកំណើត:' : 'Date of Birth:' }}
              </label>
              <input
                v-model="personalForm.dob"
                type="date"
                class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
              />
            </div>

            <!-- Bio -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'ជីវប្រវត្តិសង្ខេប / គោលដៅសិក្សា:' : 'Bio / About Me:' }}
              </label>
              <textarea
                v-model="personalForm.bio"
                rows="3"
                class="w-full bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                :placeholder="currentLang === 'km' ? 'សរសេរសេចក្តីពិពណ៌នាខ្លីៗអំពីខ្លួនអ្នក...' : 'Write a short description about yourself...'"
              ></textarea>
            </div>

            <!-- Save Button -->
            <div class="pt-2">
              <button
                type="submit"
                :disabled="personalForm.processing"
                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-xs transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <svg
                  v-if="personalForm.processing"
                  class="w-4 h-4 animate-spin text-white"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ personalForm.processing ? (currentLang === 'km' ? 'កំពុងរក្សាទុក...' : 'Saving...') : (currentLang === 'km' ? 'រក្សាទុកការផ្លាស់ប្តូរ' : 'Save Changes') }}</span>
              </button>
            </div>

          </form>
        </div>

      </div>

      <!-- TAB 3: ACADEMIC INFORMATION (Read-only Academic Records) -->
      <div v-show="currentTab === 'academic'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
        
        <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
            {{ currentLang === 'km' ? 'ព័ត៌មានការចុះឈ្មោះសិក្សាផ្លូវការ' : 'Official Academic Enrollment Records' }}
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{ currentLang === 'km' ? 'ព័ត៌មានលម្អិតអំពីមហាវិទ្យាល័យ ដេប៉ាតឺម៉ង់ ជំនាញ និងឆមាសសិក្សានៅវិទ្យាស្ថាន សន្តប៉ូល' : 'Verified institutional records regarding your enrolled faculty, department, specialization, and term' }}
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
          
          <!-- Faculty -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
            <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">{{ currentLang === 'km' ? 'មហាវិទ្យាល័យ' : 'Faculty' }}</span>
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ currentLang === 'km' ? (academic.faculty_kh || academic.faculty) : academic.faculty }}</p>
            <p v-if="academic.faculty_kh && currentLang !== 'km'" class="text-[11px] text-slate-400 font-khmer">{{ academic.faculty_kh }}</p>
          </div>

          <!-- Department -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
            <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">{{ currentLang === 'km' ? 'ដេប៉ាតឺម៉ង់' : 'Department' }}</span>
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ currentLang === 'km' ? (academic.department_kh || academic.department) : academic.department }}</p>
            <p v-if="academic.department_kh && currentLang !== 'km'" class="text-[11px] text-slate-400 font-khmer">{{ academic.department_kh }}</p>
          </div>

          <!-- Major Specialization -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
            <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">{{ currentLang === 'km' ? 'ជំនាញឯកទេស' : 'Major Specialization' }}</span>
            <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</p>
            <p v-if="academic.major_kh && currentLang !== 'km'" class="text-[11px] text-slate-400 font-khmer">{{ academic.major_kh }}</p>
          </div>

          <!-- Term -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
            <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">{{ currentLang === 'km' ? 'ឆ្នាំសិក្សា & ឆមាសបច្ចុប្បន្ន' : 'Academic Year & Term' }}</span>
            <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ academic.term }}</p>
          </div>

          <!-- Degree Level -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
            <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">{{ currentLang === 'km' ? 'កម្រិតសញ្ញាបត្រ' : 'Degree Level' }}</span>
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ academic.degree_level }}</p>
          </div>

          <!-- Study Type -->
          <div class="p-4 bg-slate-50 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
            <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">{{ currentLang === 'km' ? 'ទម្រង់សិក្សា' : 'Study Mode' }}</span>
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ academic.study_type }}</p>
          </div>

        </div>

      </div>

      <!-- TAB 4: SECURITY & PASSWORD (Password Update, Active Sessions, Login History) -->
      <div v-show="currentTab === 'security'" class="space-y-6">
        
        <!-- Password Form -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ currentLang === 'km' ? 'ប្តូរពាក្យសម្ងាត់' : 'Change Password' }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ currentLang === 'km' ? 'ពាក្យសម្ងាត់ត្រូវមានយ៉ាងហោចណាស់ ៨ តួអក្សរ ដោយរួមបញ្ចូលទាំងអក្សរ និងលេខ' : 'Your password must be at least 8 characters long and include letters and numbers' }}
            </p>
          </div>

          <div
            v-if="passwordSuccess"
            class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-semibold"
          >
            ✓ {{ currentLang === 'km' ? 'ពាក្យសម្ងាត់ត្រូវបានប្តូរដោយជោគជ័យ!' : 'Password updated successfully!' }}
          </div>

          <form @submit.prevent="updatePassword" class="space-y-4 max-w-md">
            
            <!-- Current Password -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'ពាក្យសម្ងាត់បច្ចុប្បន្ន:' : 'Current Password:' }}
              </label>
              <div class="relative">
                <input
                  v-model="passwordForm.current_password"
                  :type="showCurrentPassword ? 'text' : 'password'"
                  required
                  class="w-full pr-10 bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                />
                <button
                  type="button"
                  @click="showCurrentPassword = !showCurrentPassword"
                  class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 text-xs cursor-pointer"
                >
                  {{ showCurrentPassword ? 'Hide' : 'Show' }}
                </button>
              </div>
              <p v-if="passwordForm.errors.current_password" class="mt-1 text-[11px] text-rose-500 font-medium">
                {{ passwordForm.errors.current_password }}
              </p>
            </div>

            <!-- New Password -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'ពាក្យសម្ងាត់ថ្មី:' : 'New Password:' }}
              </label>
              <div class="relative">
                <input
                  v-model="passwordForm.password"
                  :type="showNewPassword ? 'text' : 'password'"
                  required
                  minlength="8"
                  class="w-full pr-10 bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                />
                <button
                  type="button"
                  @click="showNewPassword = !showNewPassword"
                  class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 text-xs cursor-pointer"
                >
                  {{ showNewPassword ? 'Hide' : 'Show' }}
                </button>
              </div>

              <!-- Strength Meter -->
              <div v-if="passwordForm.password" class="mt-2 space-y-1">
                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                  <div
                    class="h-1.5 rounded-full transition-all duration-300"
                    :class="passwordStrength.color"
                    :style="{ width: `${passwordStrength.score}%` }"
                  ></div>
                </div>
                <span class="text-[10px] font-bold text-slate-500">
                  {{ currentLang === 'km' ? 'កម្រិតសុវត្ថិភាព' : 'Strength' }}: {{ passwordStrength.text }}
                </span>
              </div>
              <p v-if="passwordForm.errors.password" class="mt-1 text-[11px] text-rose-500 font-medium">
                {{ passwordForm.errors.password }}
              </p>
            </div>

            <!-- Confirm Password -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                {{ currentLang === 'km' ? 'បញ្ជាក់ពាក្យសម្ងាត់ថ្មី:' : 'Confirm New Password:' }}
              </label>
              <div class="relative">
                <input
                  v-model="passwordForm.password_confirmation"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  required
                  class="w-full pr-10 bg-slate-50 dark:bg-slate-950/60 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                />
                <button
                  type="button"
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 text-xs cursor-pointer"
                >
                  {{ showConfirmPassword ? 'Hide' : 'Show' }}
                </button>
              </div>
              <p v-if="passwordForm.errors.password_confirmation" class="mt-1 text-[11px] text-rose-500 font-medium">
                {{ passwordForm.errors.password_confirmation }}
              </p>
            </div>

            <div class="pt-2">
              <button
                type="submit"
                :disabled="passwordForm.processing"
                class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-xs transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <span>{{ passwordForm.processing ? (currentLang === 'km' ? 'កំពុងប្តូរ...' : 'Updating...') : (currentLang === 'km' ? 'ប្តូរពាក្យសម្ងាត់' : 'Update Password') }}</span>
              </button>
            </div>

          </form>
        </div>

        <!-- Active Sessions Panel -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-xs space-y-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                {{ currentLang === 'km' ? 'ឧបករណ៍កំពុង Login ក្នុងគណនី (Active Sessions)' : 'Active Sessions' }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ currentLang === 'km' ? 'បញ្ជីឧបករណ៍ និងកម្មវិធីរុករកដែលកំពុងភ្ជាប់ជាមួយគណនីរបស់អ្នក' : 'Devices and browsers currently logged into your account' }}
              </p>
            </div>

            <button
              type="button"
              @click="isLogoutOthersModalOpen = true"
              class="px-3.5 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-100 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60 font-bold text-xs transition-all cursor-pointer inline-flex items-center gap-1.5 shrink-0 self-start"
            >
              <span>{{ currentLang === 'km' ? 'ចាកចេញពីឧបករណ៍ផ្សេងទៀត' : 'Sign Out Other Devices' }}</span>
            </button>
          </div>

          <div class="space-y-2.5">
            <div
              v-for="sess in security.active_sessions"
              :key="sess.id"
              class="p-4 bg-slate-50 dark:bg-slate-950/50 border border-slate-200/80 dark:border-slate-800 rounded-2xl flex items-center justify-between gap-4 text-xs"
            >
              <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 text-lg">
                  💻
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <p class="font-bold text-slate-900 dark:text-white">{{ sess.platform }} — {{ sess.browser }}</p>
                    <span
                      v-if="sess.is_current"
                      class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
                    >
                      {{ currentLang === 'km' ? 'ឧបករណ៍នេះ' : 'This Device' }}
                    </span>
                  </div>
                  <p class="text-slate-400 text-[11px] font-mono mt-0.5">IP: {{ sess.ip_address }} • {{ sess.last_active }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Login History -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-xs space-y-4">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
            {{ currentLang === 'km' ? 'ប្រវត្តិនៃការចូលប្រើប្រាស់ (Login History)' : 'Recent Login History' }}
          </h3>

          <div class="space-y-2">
            <div
              v-for="h in security.login_history"
              :key="h.id"
              class="p-3 bg-slate-50 dark:bg-slate-950/40 border border-slate-200/80 dark:border-slate-800 rounded-xl flex items-center justify-between text-xs"
            >
              <div>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ h.device }} ({{ h.browser }})</span>
                <span class="text-slate-400 font-mono text-[11px] ml-2">IP: {{ h.ip_address }}</span>
              </div>
              <span class="text-slate-400 text-[11px]">{{ h.time }}</span>
            </div>
          </div>
        </div>

      </div>

      <!-- TAB 5: LEARNING HISTORY & LOGS (Filterable Audit Logs + CSV Export) -->
      <div v-show="currentTab === 'history'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ currentLang === 'km' ? 'ប្រវត្តិនៃការរៀន & កំណត់ហេតុសកម្មភាព' : 'Learning Activity History & Logs' }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ currentLang === 'km' ? 'កំណត់ត្រាពិតប្រាកដអំពីការទស្សនាមេរៀន ការប្រឡង ការដាក់កិច្ចការ និងការទូទាត់' : 'Complete audit trail of all lesson completions, quiz attempts, assignments, and payments' }}
            </p>
          </div>

          <!-- CSV Export Button -->
          <a
            href="/student/profile/history/export-csv"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all inline-flex items-center gap-1.5 self-start cursor-pointer"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>{{ currentLang === 'km' ? 'នាំចេញជា CSV' : 'Export CSV' }}</span>
          </a>
        </div>

        <!-- Filter Chips -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
          <button
            type="button"
            v-for="typeOpt in ['all', 'lesson', 'quiz', 'assignment', 'payment', 'certificate']"
            :key="typeOpt"
            @click="selectedHistoryType = typeOpt as any"
            :class="[
              selectedHistoryType === typeOpt
                ? 'bg-indigo-600 text-white font-bold'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200',
              'px-3 py-1.5 rounded-xl capitalize transition-all cursor-pointer shrink-0'
            ]"
          >
            {{ typeOpt }}
          </button>
        </div>

        <!-- Logs List -->
        <div v-if="filteredHistoryLogs.length > 0" class="space-y-3">
          <div
            v-for="log in filteredHistoryLogs"
            :key="log.id"
            class="p-4 bg-slate-50 dark:bg-slate-950/50 hover:bg-slate-100/70 dark:hover:bg-slate-950/80 border border-slate-200/80 dark:border-slate-800 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs transition-colors"
          >
            <div class="flex items-center gap-3">
              <span
                class="px-2 py-1 rounded-lg text-[10px] font-bold border uppercase shrink-0"
                :class="getLogTypeBadge(log.type).class"
              >
                {{ getLogTypeBadge(log.type).label }}
              </span>
              <div>
                <p class="font-bold text-slate-900 dark:text-white">
                  {{ currentLang === 'km' ? (log.action_kh || log.action) : log.action }}
                </p>
                <p class="text-slate-500 dark:text-slate-400 text-[11px]">
                  {{ currentLang === 'km' ? (log.detail_kh || log.detail) : log.detail }}
                </p>
              </div>
            </div>
            <span class="text-slate-400 font-mono text-[11px] shrink-0 sm:self-center">
              {{ log.time }}
            </span>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-10 space-y-2">
          <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
            {{ currentLang === 'km' ? 'មិនមានសកម្មភាពសម្រាប់ប្រភេទដែលបានជ្រើសរើសឡើយ' : 'No activity records found for this filter' }}
          </p>
          <p class="text-xs text-slate-400">
            {{ currentLang === 'km' ? 'កំណត់ត្រានឹងបង្ហាញនៅទីនេះនៅពេលអ្នកចាប់ផ្តើមរៀន និងប្រឡង' : 'Your activity history will appear here once you take lessons or quizzes' }}
          </p>
        </div>

      </div>

    </div>

    <!-- MODAL: Request Correction for Locked Field -->
    <div
      v-if="isCorrectionModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
      @click.self="isCorrectionModalOpen = false"
    >
      <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
        
        <div>
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">
            {{ currentLang === 'km' ? 'ស្នើកែតម្រូវទិន្នន័យផ្លូវការ' : 'Request Official Record Correction' }}
          </h3>
          <p class="text-xs text-slate-500 mt-1">
            {{ currentLang === 'km' ? 'សំណើរបស់អ្នកនឹងត្រូវផ្ញើទៅកាន់ការិយាល័យសិក្សាដើម្បីត្រួតពិនិត្យ' : 'Your request will be submitted to the registrar for review' }}
          </p>
        </div>

        <form @submit.prevent="submitCorrectionRequest" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ currentLang === 'km' ? 'ទិន្នន័យដែលស្នើកែប្រែថ្មី:' : 'Proposed New Value:' }}
            </label>
            <input
              v-model="correctionForm.new_value"
              type="text"
              required
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
              :placeholder="currentLang === 'km' ? 'វាយបញ្ចូលទិន្នន័យត្រឹមត្រូវ' : 'Enter accurate data'"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ currentLang === 'km' ? 'មូលហេតុនៃការស្នើសុំ:' : 'Reason for Correction:' }}
            </label>
            <textarea
              v-model="correctionForm.reason"
              rows="3"
              required
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
              :placeholder="currentLang === 'km' ? 'បញ្ជាក់មូលហេតុ (ឧ. ខុសអក្ខរាវិរុទ្ធលើអត្តសញ្ញាណប័ណ្ណ...)' : 'State reason (e.g. Typo in national ID...)'"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              @click="isCorrectionModalOpen = false"
              class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="correctionForm.processing"
              class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold cursor-pointer disabled:opacity-50"
            >
              Submit Request
            </button>
          </div>
        </form>

      </div>
    </div>

    <!-- MODAL: Confirm Sign Out Other Devices -->
    <div
      v-if="isLogoutOthersModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
      @click.self="isLogoutOthersModalOpen = false"
    >
      <div class="w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
        <div>
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">
            {{ currentLang === 'km' ? 'បញ្ជាក់ការចាកចេញពីឧបករណ៍ផ្សេងទៀត' : 'Confirm Sign Out Other Devices' }}
          </h3>
          <p class="text-xs text-slate-500 mt-1">
            {{ currentLang === 'km' ? 'សូមបញ្ចូលពាក្យសម្ងាត់របស់អ្នកដើម្បីបញ្ជាក់ការចាកចេញពីគ្រប់ Browser ផ្សេងទៀត។' : 'Please confirm your current password to sign out of all other sessions.' }}
          </p>
        </div>

        <form @submit.prevent="submitLogoutOthers" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ currentLang === 'km' ? 'ពាក្យសម្ងាត់របស់អ្នក:' : 'Your Password:' }}
            </label>
            <input
              v-model="logoutOthersForm.password"
              type="password"
              required
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500"
            />
            <p v-if="logoutOthersForm.errors.password" class="mt-1 text-[11px] text-rose-500">
              {{ logoutOthersForm.errors.password }}
            </p>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              @click="isLogoutOthersModalOpen = false"
              class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="logoutOthersForm.processing"
              class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold cursor-pointer disabled:opacity-50"
            >
              Sign Out Others
            </button>
          </div>
        </form>
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
    position: fixed;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%) scale(1.05);
    box-shadow: none !important;
    border-width: 1px !important;
  }
}
</style>
