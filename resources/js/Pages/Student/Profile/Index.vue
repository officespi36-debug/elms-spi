<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import QRCode from 'qrcode'
import { useAppToast } from '@/composables/useAppToast'

interface MissingFieldItem {
  key: string
  label_en: string
  label_km: string
  tab: string
}

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
  missing_fields?: MissingFieldItem[]
}

interface IdCardData {
  card_no: string
  token: string
  verify_url: string
  issued_at?: string
  issued_at_kh?: string
  expires_at?: string
  expires_at_kh?: string
  status: 'active' | 'revoked' | 'pending'
  is_official: boolean
  is_live_verified?: boolean
  approved_at?: string
  approved_at_kh?: string
  institution: string
  institution_kh: string
}

interface AcademicData {
  faculty: string
  faculty_kh: string
  department: string
  department_kh: string
  major: string
  major_kh: string
  term: string
  term_kh?: string
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
const cardLogoUrl = '/images/logo.png'
const toast = useAppToast()

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
const isRegenerateModalOpen = ref(false)
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
    toast.success(
      currentLang.value === 'km' ? 'បានចម្លងតំណភ្ជាប់ដោយជោគជ័យ!' : 'Verification link copied successfully!',
      currentLang.value === 'km' ? 'ជោគជ័យ' : 'Success'
    )
    setTimeout(() => {
      linkCopied.value = false
    }, 3000)
  })
}

const confirmRegenerateToken = () => {
  isRegeneratingToken.value = true
  router.post('/student/profile/id-card/regenerate', {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(
        currentLang.value === 'km' ? 'កូដ QR ថ្មីត្រូវបានបង្កើតដោយជោគជ័យ!' : 'New QR code generated successfully!',
        currentLang.value === 'km' ? 'ជោគជ័យ' : 'Success'
      )
    },
    onFinish: () => {
      isRegeneratingToken.value = false
      isRegenerateModalOpen.value = false
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
  <StudentLayout :title="currentLang === 'km' ? 'គណនី & ការកំណត់' : 'Student Account & Profile'">
    <!-- Main container with safe padding-bottom: 96px preventing FAB from obstructing controls -->
    <div class="space-y-6 max-w-6xl mx-auto notranslate" translate="no" style="padding-bottom: 96px;">
      
      <!-- HEADER CARD (Aesthetic of Image 3: Clean White Card, Rounded-3xl, Subtle Borders) -->
      <div class="relative overflow-hidden bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 sm:p-7 shadow-sm dark:shadow-xl transition-all">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
          
          <div class="flex items-center gap-5">
            <!-- Squircle Avatar with Camera Overlay -->
            <div class="relative shrink-0 group">
              <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl overflow-hidden bg-[#f0f4f9] dark:bg-[#131314] border border-slate-200 dark:border-slate-700 text-indigo-600 dark:text-indigo-400 font-black flex items-center justify-center text-3xl shadow-sm">
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
                class="absolute inset-0 bg-slate-950/65 text-white opacity-0 group-hover:opacity-100 rounded-2xl flex flex-col items-center justify-center gap-1 text-[11px] font-bold transition-opacity cursor-pointer shadow-md"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ currentLang === 'km' ? 'ប្តូររូប' : 'Upload' }}</span>
              </button>
            </div>

            <!-- Identity Details -->
            <div class="space-y-1.5 min-w-0">
              <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-slate-100 tracking-tight">
                  {{ profile.name }}
                </h1>
                <span v-if="profile.name_kh" class="text-sm font-semibold text-slate-600 dark:text-slate-300">
                  ({{ profile.name_kh }})
                </span>
                
                <!-- Status Chip -->
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[11px] font-bold inline-flex items-center gap-1.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  <span>{{ currentLang === 'km' ? 'និស្សិតកំពុងសិក្សា' : 'Active Enrolled Student' }}</span>
                </span>
              </div>

              <!-- Student Meta Info: Academic term shown once -->
              <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-[#f0f4f9] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] font-mono font-bold text-slate-800 dark:text-slate-200">
                  {{ profile.student_id }}
                </span>
                <span>•</span>
                <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</span>
                <span>•</span>
                <span>{{ currentLang === 'km' ? (academic.term_kh || academic.term) : academic.term }}</span>
              </div>

              <!-- Profile Completion Progress Bar with Missing Fields Link -->
              <div class="pt-1.5 space-y-1">
                <div class="flex items-center gap-3">
                  <div class="w-36 bg-[#e1e3e1] dark:bg-[#282a2c] rounded-full h-2 overflow-hidden">
                    <div
                      class="bg-gradient-to-r from-[#6366f1] via-[#8b5cf6] to-[#06b6d4] h-2 rounded-full transition-all duration-900 ease-out"
                      :style="{ width: `${profile.completion_percent}%` }"
                    ></div>
                  </div>
                  <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 tabular-nums">
                    {{ currentLang === 'km' ? 'ព័ត៌មានពេញលេញ' : 'Profile Complete' }}: {{ profile.completion_percent }}%
                  </span>
                </div>

                <!-- Missing Fields Hint with Sleek Pill Buttons -->
                <div v-if="profile.missing_fields && profile.missing_fields.length > 0" class="flex flex-wrap items-center gap-1.5 text-[11px] pt-1">
                  <span class="text-rose-500 dark:text-rose-400 font-bold inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ currentLang === 'km' ? 'ខ្វះ:' : 'Missing:' }}</span>
                  </span>
                  <div class="inline-flex flex-wrap items-center gap-1">
                    <button
                      v-for="mf in profile.missing_fields"
                      :key="mf.key"
                      type="button"
                      @click="selectTab(mf.tab)"
                      class="px-2 py-0.5 rounded-md bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 text-[10.5px] font-bold transition-all cursor-pointer inline-flex items-center gap-1 shadow-2xs"
                    >
                      <span>{{ currentLang === 'km' ? mf.label_km : mf.label_en }}</span>
                      <svg class="w-2.5 h-2.5 opacity-60 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Higher Education Institution Card on Right (Official Config Name) -->
          <div class="bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-4 flex items-center gap-3.5 shrink-0 self-start md:self-auto shadow-2xs">
            <div class="w-10 h-10 rounded-full overflow-hidden shadow-sm shrink-0 ring-1 ring-slate-200 dark:ring-slate-700">
              <img :src="cardLogoUrl" alt="Logo" class="w-full h-full object-cover" />
            </div>
            <div>
              <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
                {{ currentLang === 'km' ? 'គ្រឹះស្ថានឧត្តមសិក្សា' : 'Higher Education Institution' }}
              </p>
              <p class="text-xs font-bold text-slate-900 dark:text-slate-100 mt-0.5">
                {{ currentLang === 'km' ? (idCard.institution_kh || idCard.institution) : idCard.institution }}
              </p>
              <p class="text-[10px] text-slate-400">
                {{ currentLang === 'km' ? 'ប្រព័ន្ធគ្រប់គ្រងការសិក្សាផ្លូវការ' : 'Official Learning Management System' }}
              </p>
            </div>
          </div>

        </div>

      </div>

      <!-- TABS TRACK (Styled with Image 3 Segmented Control: Pill track with active gradient highlight) -->
      <div
        role="tablist"
        aria-label="Profile Sections"
        class="inline-flex items-center gap-1.5 p-1.5 bg-[#f0f4f9] dark:bg-[#131314] rounded-2xl border border-[#dfe3e7] dark:border-[#2d2f31] overflow-x-auto max-w-full shadow-2xs custom-scrollbar"
      >
        <!-- Tab 1: ID Card -->
        <button
          type="button"
          role="tab"
          :aria-selected="currentTab === 'id-card'"
          :tabindex="currentTab === 'id-card' ? 0 : -1"
          @click="selectTab('id-card')"
          @keydown="onTabKeydown($event, 0)"
          :class="[
            currentTab === 'id-card'
              ? 'bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white shadow-xs font-bold'
              : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-white hover:bg-white/60 dark:hover:bg-[#282a2c] font-semibold',
            'px-4 py-2.5 rounded-xl text-xs sm:text-[13px] transition-all shrink-0 cursor-pointer flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <rect x="2" y="5" width="20" height="14" rx="3" />
            <line x1="2" y1="10" x2="22" y2="10" />
          </svg>
          <span>{{ currentLang === 'km' ? 'កាតនិស្សិតឌីជីថល' : 'Digital Student ID' }}</span>
        </button>

        <!-- Tab 2: Personal -->
        <button
          type="button"
          role="tab"
          :aria-selected="currentTab === 'personal'"
          :tabindex="currentTab === 'personal' ? 0 : -1"
          @click="selectTab('personal')"
          @keydown="onTabKeydown($event, 1)"
          :class="[
            currentTab === 'personal'
              ? 'bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white shadow-xs font-bold'
              : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-white hover:bg-white/60 dark:hover:bg-[#282a2c] font-semibold',
            'px-4 py-2.5 rounded-xl text-xs sm:text-[13px] transition-all shrink-0 cursor-pointer flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span>{{ currentLang === 'km' ? 'ព័ត៌មានផ្ទាល់ខ្លួន' : 'Personal Information' }}</span>
        </button>

        <!-- Tab 3: Academic -->
        <button
          type="button"
          role="tab"
          :aria-selected="currentTab === 'academic'"
          :tabindex="currentTab === 'academic' ? 0 : -1"
          @click="selectTab('academic')"
          @keydown="onTabKeydown($event, 2)"
          :class="[
            currentTab === 'academic'
              ? 'bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white shadow-xs font-bold'
              : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-white hover:bg-white/60 dark:hover:bg-[#282a2c] font-semibold',
            'px-4 py-2.5 rounded-xl text-xs sm:text-[13px] transition-all shrink-0 cursor-pointer flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
          </svg>
          <span>{{ currentLang === 'km' ? 'ព័ត៌មានការសិក្សា' : 'Academic Information' }}</span>
        </button>

        <!-- Tab 4: Security -->
        <button
          type="button"
          role="tab"
          :aria-selected="currentTab === 'security'"
          :tabindex="currentTab === 'security' ? 0 : -1"
          @click="selectTab('security')"
          @keydown="onTabKeydown($event, 3)"
          :class="[
            currentTab === 'security'
              ? 'bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white shadow-xs font-bold'
              : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-white hover:bg-white/60 dark:hover:bg-[#282a2c] font-semibold',
            'px-4 py-2.5 rounded-xl text-xs sm:text-[13px] transition-all shrink-0 cursor-pointer flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0110 0v4" />
          </svg>
          <span>{{ currentLang === 'km' ? 'សុវត្ថិភាព & ពាក្យសម្ងាត់' : 'Security & Password' }}</span>
        </button>

        <!-- Tab 5: History -->
        <button
          type="button"
          role="tab"
          :aria-selected="currentTab === 'history'"
          :tabindex="currentTab === 'history' ? 0 : -1"
          @click="selectTab('history')"
          @keydown="onTabKeydown($event, 4)"
          :class="[
            currentTab === 'history'
              ? 'bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white shadow-xs font-bold'
              : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-white hover:bg-white/60 dark:hover:bg-[#282a2c] font-semibold',
            'px-4 py-2.5 rounded-xl text-xs sm:text-[13px] transition-all shrink-0 cursor-pointer flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
          </svg>
          <span>{{ currentLang === 'km' ? 'ប្រវត្តិនៃការរៀន' : 'Learning History & Logs' }}</span>
        </button>
      </div>

      <!-- TAB 1: DIGITAL STUDENT ID CARD (Flippable Card + Verification Details) -->
      <div v-show="currentTab === 'id-card'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left: Flippable CR80 Digital Card Container -->
        <div class="lg:col-span-7 bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 sm:p-7 shadow-sm dark:shadow-xl flex flex-col items-center justify-between space-y-6">
          
          <!-- Top Row: Side indicator & Flip trigger (Single Language - no bilingual English suffix) -->
          <div class="w-full flex items-center justify-between text-xs text-slate-500">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-[#6366f1]"></span>
              <span class="font-bold text-slate-900 dark:text-slate-100">
                {{ isCardFlipped ? (currentLang === 'km' ? 'ផ្នែកខាងក្រោយនៃកាត' : 'Card Back Side') : (currentLang === 'km' ? 'ផ្នែកខាងមុខនៃកាត' : 'Card Front Side') }}
              </span>
            </div>

            <button
              type="button"
              @click="toggleCardFlip"
              class="px-3 py-1.5 rounded-xl bg-[#f0f4f9] dark:bg-[#131314] hover:bg-slate-200 dark:hover:bg-[#282a2c] text-slate-700 dark:text-slate-300 font-bold text-xs transition-all cursor-pointer inline-flex items-center gap-1.5 border border-[#dfe3e7] dark:border-[#2d2f31]"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>{{ currentLang === 'km' ? 'ត្រឡប់កាត' : 'Flip Card' }}</span>
            </button>
          </div>

          <!-- Card Scene Container: Aspect Ratio 1.586 / 1 (CR80 standard) with 3D Flip -->
          <div
            id="student-digital-id-card"
            @click="toggleCardFlip"
            @keydown.space.prevent="toggleCardFlip"
            @keydown.enter.prevent="toggleCardFlip"
            tabindex="0"
            role="button"
            :aria-label="currentLang === 'km' ? 'កាតនិស្សិតឌីជីថល ចុចដើម្បីបង្វិល' : 'Digital Student ID Card. Click to flip.'"
            class="card-3d-scene w-full max-w-[440px] aspect-[1.586/1] select-none cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-2xl group transition-transform duration-300 hover:scale-[1.01]"
          >
            <div
              class="card-3d-inner w-full h-full relative transition-transform duration-700 ease-out"
              :class="{ 'is-flipped': isCardFlipped }"
            >
              <!-- FRONT SIDE FACE -->
              <div
                class="card-3d-face card-3d-front absolute inset-0 w-full h-full rounded-2xl p-4 sm:p-5 bg-gradient-to-br from-[#070e24] via-[#0f1d46] to-[#080d1e] border border-slate-700/70 shadow-2xl text-white overflow-hidden flex flex-col justify-between ring-1 ring-white/10"
              >
                <!-- Background Security Watermark & Sheen Layers -->
                <img
                  :src="cardLogoUrl"
                  alt=""
                  class="absolute right-[-25px] -bottom-10 w-52 h-52 object-contain rounded-full opacity-[0.08] pointer-events-none select-none blur-[0.5px]"
                />
                <div class="absolute inset-0 bg-[radial-gradient(#3b82f6_1px,transparent_1px)] [background-size:16px_16px] opacity-[0.07] pointer-events-none"></div>
                <div class="absolute -top-24 -right-24 w-60 h-60 bg-gradient-to-bl from-indigo-500/15 via-cyan-400/10 to-transparent rounded-full blur-2xl pointer-events-none"></div>
                <!-- Subtle glass shine on hover -->
                <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/5 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                <!-- Card Top Row: Official Institution Name from Config & Logo (NOT E.LMS) -->
                <div class="flex items-center justify-between border-b border-slate-700/60 pb-2 relative z-10">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-full overflow-hidden shadow-sm flex items-center justify-center shrink-0 ring-1 ring-white/30">
                      <img :src="cardLogoUrl" alt="Logo" class="w-full h-full object-cover" />
                    </div>
                    <div class="min-w-0">
                      <p class="text-xs sm:text-[13px] font-black tracking-wide text-white uppercase drop-shadow-xs truncate">
                        {{ currentLang === 'km' ? (idCard.institution_kh || idCard.institution) : idCard.institution }}
                      </p>
                      <p class="text-[9px] font-semibold text-amber-300/90 tracking-wider uppercase truncate">
                        SAINT PAUL INSTITUTE
                      </p>
                    </div>
                  </div>
                  
                  <div class="flex items-center gap-2 shrink-0">
                    <!-- OFFICIAL ID Badge: Displayed ONLY when approved_by & approved_at are from real registrar -->
                    <span
                      v-if="idCard.is_official"
                      class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/40 text-[9.5px] font-black uppercase tracking-wider shadow-xs backdrop-blur-md flex items-center gap-1.5"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                      <span>{{ currentLang === 'km' ? 'កាតផ្លូវការ' : 'OFFICIAL ID' }}</span>
                    </span>
                    <span
                      v-else
                      class="px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-400/40 text-[9.5px] font-black uppercase tracking-wider shadow-xs backdrop-blur-md"
                    >
                      {{ currentLang === 'km' ? 'រង់ចាំការបញ្ជាក់' : 'PENDING' }}
                    </span>
                  </div>
                </div>

                <!-- Card Middle: Balanced 3 Columns (Left: Photo, Center: Info, Right: Smart Chip) -->
                <div class="flex items-center gap-3.5 my-auto relative z-10">
                  <!-- Left: Student Photo with Double Ring & Verified Seal -->
                  <div class="relative shrink-0">
                    <div class="w-[72px] h-[72px] sm:w-[80px] sm:h-[80px] rounded-xl overflow-hidden bg-slate-900 border-2 border-indigo-400/40 ring-2 ring-white/30 shadow-md flex items-center justify-center">
                      <img
                        v-if="avatarPreview"
                        :src="avatarPreview"
                        :alt="profile.name"
                        class="w-full h-full object-cover"
                      />
                      <span v-else class="text-2xl font-black text-indigo-400">
                        {{ profile.name ? profile.name.charAt(0).toUpperCase() : 'S' }}
                      </span>
                    </div>
                    <div v-if="idCard.is_official" class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow-md border-2 border-[#091228]">
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                  </div>

                  <!-- Center: Student Details (Names, Student ID Badge, Major & Faculty) -->
                  <div class="space-y-1 min-w-0 flex-1">
                    <div>
                      <h2 class="text-sm sm:text-base font-black text-white leading-tight truncate tracking-tight">
                        {{ profile.name }}
                      </h2>
                      <p v-if="profile.name_kh" class="text-xs font-semibold text-indigo-200 truncate mt-0.5">
                        {{ profile.name_kh }}
                      </p>
                    </div>

                    <!-- Student ID in an elegant badge -->
                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-white/10 backdrop-blur-md border border-white/20">
                      <span class="text-[9px] uppercase tracking-wider text-slate-300 font-bold">ID:</span>
                      <span class="font-mono text-xs font-extrabold text-amber-300">{{ profile.student_id }}</span>
                    </div>

                    <!-- Major and Faculty -->
                    <div class="space-y-0.5">
                      <p class="text-[11px] sm:text-xs text-slate-200 font-semibold truncate flex items-center gap-1">
                        <span class="text-indigo-400 text-[10px]">●</span>
                        <span>{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</span>
                      </p>
                      <p v-if="academic.faculty || academic.faculty_kh" class="text-[9.5px] text-slate-300/80 truncate pl-3 font-medium">
                        {{ currentLang === 'km' ? (academic.faculty_kh || academic.faculty) : academic.faculty }}
                      </p>
                    </div>
                  </div>

                  <!-- Right: Smart Chip & Contactless RFID Waves for Perfect Visual Equilibrium -->
                  <div class="flex flex-col items-center justify-center gap-2 shrink-0 pl-1 border-l border-slate-700/40">
                    <!-- Contactless RFID Wave Icon -->
                    <svg class="w-4 h-4 text-slate-400/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 16.5a5 5 0 010-9M12 19a9 9 0 000-14M15.5 21.5a13 13 0 000-19" />
                    </svg>

                    <!-- Metallic Gold/Silver EMV Smart Chip SVG -->
                    <div class="w-9 h-7 rounded-md bg-gradient-to-br from-amber-200 via-amber-400 to-amber-600 p-[1px] shadow-md border border-amber-300/70 relative overflow-hidden">
                      <div class="w-full h-full bg-gradient-to-tr from-amber-400 via-amber-300 to-amber-200 rounded-[3px] relative flex items-center justify-center">
                        <div class="w-full h-[0.5px] bg-amber-800/60 absolute top-1/2"></div>
                        <div class="h-full w-[0.5px] bg-amber-800/60 absolute left-1/2"></div>
                        <div class="w-3.5 h-2.5 rounded-[2px] border border-amber-800/50 bg-amber-200/60"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Card Bottom: CARD NO, TERM, and VALID THRU in Equal Alignment -->
                <div class="pt-2 border-t border-slate-700/60 flex items-center justify-between gap-2 text-[11px] text-slate-100 whitespace-nowrap overflow-hidden relative z-10">
                  <!-- Term / Academic Year -->
                  <div class="flex items-center gap-1 shrink-0 truncate max-w-[170px]">
                    <span class="text-slate-400 text-[10px]">{{ currentLang === 'km' ? 'ឆមាស:' : 'Term:' }}</span>
                    <span class="font-bold text-emerald-300 truncate">
                      {{ currentLang === 'km' ? (academic.term_kh || academic.term) : academic.term }}
                    </span>
                  </div>

                  <!-- Card No -->
                  <div class="flex items-center gap-1 shrink-0 font-mono text-[10.5px] text-slate-300 bg-slate-900/60 px-1.5 py-0.5 rounded border border-slate-700/60">
                    <span class="text-slate-400 text-[9px] font-sans">CARD:</span>
                    <span class="font-bold text-white">{{ idCard.card_no }}</span>
                  </div>

                  <!-- Valid Thru -->
                  <div class="flex items-center gap-1 shrink-0">
                    <span class="text-slate-400 text-[10px]">{{ currentLang === 'km' ? 'សុពលភាព:' : 'Valid Thru:' }}</span>
                    <span class="font-bold text-amber-200">
                      {{ currentLang === 'km' ? (idCard.expires_at_kh || idCard.expires_at) : idCard.expires_at }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- BACK SIDE FACE -->
              <div
                class="card-3d-face card-3d-back absolute inset-0 w-full h-full rounded-2xl p-4 sm:p-4.5 bg-gradient-to-br from-[#070e24] via-[#0f1d46] to-[#080d1e] border border-slate-700/70 shadow-2xl text-white overflow-hidden flex flex-col justify-between ring-1 ring-white/10"
              >
                <!-- Background Security Watermark & Sheen Layers -->
                <img
                  :src="cardLogoUrl"
                  alt=""
                  class="absolute left-[-20px] -bottom-10 w-52 h-52 object-contain rounded-full opacity-[0.08] pointer-events-none select-none blur-[0.5px]"
                />
                <div class="absolute inset-0 bg-[radial-gradient(#3b82f6_1px,transparent_1px)] [background-size:16px_16px] opacity-[0.07] pointer-events-none"></div>

                <!-- Back Magnetic Security Strip -->
                <div class="w-full h-7 sm:h-7.5 bg-gradient-to-r from-[#030712] via-[#111827] to-[#030712] rounded-lg border border-slate-800 -mx-0.5 flex items-center justify-between px-3 shadow-inner relative z-10">
                  <span class="text-[8px] font-mono tracking-widest text-slate-400 uppercase">
                    {{ currentLang === 'km' ? 'ការផ្ទៀងផ្ទាត់អត្តសញ្ញាណនិស្សិត (STUDENT VERIFICATION)' : 'STUDENT IDENTITY VERIFICATION' }}
                  </span>
                  <span class="text-[7.5px] font-mono text-amber-400/80 tracking-wider">SPI SECURE</span>
                </div>

                <!-- Center 2 Columns: Left = Real Working QR Code, Right = Signature & Terms -->
                <div class="flex items-center justify-between gap-3 my-auto py-1 relative z-10">
                  <!-- Left: Real Working QR Code -->
                  <div class="flex flex-col items-center shrink-0">
                    <div class="bg-white p-2 rounded-xl shadow-md border border-slate-200 shrink-0">
                      <img
                        v-if="qrDataUrl"
                        :src="qrDataUrl"
                        alt="Official Student Verification QR"
                        class="w-[72px] h-[72px] sm:w-[78px] sm:h-[78px] rounded-md object-contain"
                      />
                      <div v-else class="w-[72px] h-[72px] flex items-center justify-center bg-slate-100 text-[10px] text-slate-500 font-mono">
                        QR
                      </div>
                    </div>
                    <span class="text-[8px] font-mono text-slate-400 mt-1 uppercase tracking-wider">
                      SCAN TO VERIFY
                    </span>
                  </div>

                  <!-- Right: Signature, Official Terms & Dates -->
                  <div class="space-y-1.5 text-[9.5px] text-slate-300 max-w-[235px] leading-relaxed flex-1">
                    <!-- Authorized Signature Strip -->
                    <div class="w-full bg-slate-100 rounded border border-slate-300 px-2 py-0.5 flex items-center justify-between select-none">
                      <span class="text-[7px] font-mono text-slate-500 uppercase tracking-wider">AUTHORIZED SIGNATURE / ហត្ថលេខា</span>
                      <span class="text-[7.5px] font-mono font-bold text-slate-800">{{ profile.student_id }}</span>
                    </div>

                    <!-- Terms of Use -->
                    <p class="text-[8.5px] sm:text-[9px] text-slate-300 leading-tight">
                      {{ currentLang === 'km' ? 'កាតនេះជាកម្មសិទ្ធិរបស់វិទ្យាស្ថាន សន្តប៉ូល។ ប្រសិនបើរើសបាន សូមមេត្តាប្រគល់ជូនការិយាល័យសិក្សា ឬទាក់ទងមកវិទ្យាស្ថាន។' : 'This card remains the property of Saint Paul Institute. If found, please return to the Academic Office.' }}
                    </p>

                    <!-- Issued & Expiration Dates -->
                    <div class="flex items-center justify-between text-[8.5px] text-slate-300 pt-0.5 border-t border-slate-700/50">
                      <div>
                        <span class="text-slate-400">{{ currentLang === 'km' ? 'ថ្ងៃចេញ:' : 'Issued:' }}</span>
                        <span class="font-medium text-white ml-1">{{ currentLang === 'km' ? (idCard.issued_at_kh || idCard.issued_at) : idCard.issued_at }}</span>
                      </div>
                      <div>
                        <span class="text-slate-400">{{ currentLang === 'km' ? 'ផុតកំណត់:' : 'Expires:' }}</span>
                        <span class="font-medium text-amber-200 ml-1">{{ currentLang === 'km' ? (idCard.expires_at_kh || idCard.expires_at) : idCard.expires_at }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Bottom Row: Website, Barcode/Token -->
                <div class="pt-1.5 border-t border-slate-700/60 flex items-center justify-between text-[9.5px] font-mono text-slate-300 relative z-10">
                  <a
                    href="https://spilms.tech"
                    target="_blank"
                    class="flex items-center gap-1 text-indigo-300 hover:text-indigo-200 underline"
                  >
                    <span>🌐</span>
                    <span>spilms.tech</span>
                  </a>
                  <div class="flex items-center gap-1.5">
                    <span class="text-slate-400 text-[8px] uppercase">TOKEN:</span>
                    <span class="bg-slate-900/60 px-1.5 py-0.5 rounded border border-slate-700/60 font-bold text-slate-200 text-[8.5px]">
                      •••• •••• {{ idCard.token ? idCard.token.slice(-4) : '••••' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <p class="text-[11px] text-slate-400 text-center">
            {{ currentLang === 'km' ? 'ចុចលើកាត ឬចុច Spacebar ដើម្បីត្រឡប់កាត' : 'Click card or press Spacebar to flip between front and back' }}
          </p>

        </div>

        <!-- Right: Validity Details & Actions (Styled like Image 3, No ✓ icon in header) -->
        <div class="lg:col-span-5 bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 sm:p-7 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-6">
          
          <div>
            <div class="flex items-center justify-between">
              <!-- Removed checkmark icon from header -->
              <h3 class="text-[15px] font-bold text-slate-900 dark:text-slate-100">
                {{ currentLang === 'km' ? 'សុពលភាព & ស្ថានភាពកាត' : 'Card Validity & Verification' }}
              </h3>
              
              <!-- Live Verified Badge: Shown ONLY when verified token is active and valid -->
              <a
                v-if="idCard.is_live_verified"
                :href="idCard.verify_url"
                target="_blank"
                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 inline-flex items-center gap-1 hover:underline"
              >
                <span>Live Verified</span>
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
              </a>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ currentLang === 'km' ? 'គ្រប់គ្រងកាតសម្គាល់ខ្លួន និងផ្ទៀងផ្ទាត់តំណភ្ជាប់សាធារណៈ' : 'Manage your digital credentials and verification link' }}
            </p>
          </div>

          <!-- Soft Panel Rows with Metrics -->
          <div class="p-4.5 bg-[#f8fafc] dark:bg-[#131314] rounded-2xl border border-[#dfe3e7] dark:border-[#2d2f31] space-y-3.5 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ currentLang === 'km' ? 'លេខកាត:' : 'Card No:' }}</span>
              <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ idCard.card_no }}</span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ currentLang === 'km' ? 'ស្ថានភាពផ្លូវការ:' : 'Official Status:' }}</span>
              <span
                v-if="idCard.is_official"
                class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
              >
                ● {{ currentLang === 'km' ? 'បានអនុម័តផ្លូវការ' : 'Officially Verified' }}
              </span>
              <span
                v-else
                class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
              >
                ● {{ currentLang === 'km' ? 'រង់ចាំការអនុម័ត' : 'Pending Registrar Approval' }}
              </span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ currentLang === 'km' ? 'កាលបរិច្ឆេទចេញ:' : 'Issued Date:' }}</span>
              <span class="font-medium text-slate-700 dark:text-slate-300">
                {{ currentLang === 'km' ? (idCard.issued_at_kh || idCard.issued_at || '—') : (idCard.issued_at || '—') }}
              </span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ currentLang === 'km' ? 'កាលបរិច្ឆេទផុតកំណត់:' : 'Expires:' }}</span>
              <span class="font-medium text-slate-700 dark:text-slate-300">
                {{ currentLang === 'km' ? (idCard.expires_at_kh || idCard.expires_at || '—') : (idCard.expires_at || '—') }}
              </span>
            </div>
          </div>

          <!-- Action Buttons (Single Language - No English bilingual suffixes) -->
          <div class="space-y-3">
            
            <!-- Print CR80 Standard -->
            <button
              type="button"
              @click="printIdCard"
              class="w-full py-3 px-4 rounded-2xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:from-[#4f46e5] hover:to-[#6d28d9] text-white font-bold text-xs shadow-md shadow-indigo-500/25 transition-all flex items-center justify-center gap-2 hover:scale-[1.01] active:scale-[0.99] cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              <span>{{ currentLang === 'km' ? 'បោះពុម្ពកាតសម្គាល់ខ្លួន' : 'Print ID Card' }}</span>
            </button>

            <!-- Copy Verify URL -->
            <button
              type="button"
              @click="copyVerifyLink"
              class="w-full py-3 px-4 rounded-2xl bg-[#f0f4f9] dark:bg-[#131314] hover:bg-slate-200 dark:hover:bg-[#282a2c] text-slate-800 dark:text-slate-200 font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer border border-[#dfe3e7] dark:border-[#2d2f31]"
            >
              <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              <span>{{ linkCopied ? (currentLang === 'km' ? '✓ បានចម្លងតំណភ្ជាប់' : '✓ Link Copied') : (currentLang === 'km' ? 'ចម្លងតំណភ្ជាប់ផ្ទៀងផ្ទាត់' : 'Copy Verification Link') }}</span>
            </button>

            <!-- Regenerate Token: Opens Modal Dialog -->
            <button
              type="button"
              @click="isRegenerateModalOpen = true"
              class="w-full py-2.5 px-4 rounded-2xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/50 dark:bg-rose-950/20 hover:bg-rose-100/60 text-rose-600 dark:text-rose-400 font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>{{ currentLang === 'km' ? 'បង្កើតកូដ QR ថ្មី' : 'Regenerate QR Code' }}</span>
            </button>

          </div>

        </div>

      </div>

      <!-- TAB 2: PERSONAL INFORMATION -->
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

        <!-- Section 1: Locked Official Identification -->
        <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 sm:p-7 shadow-sm dark:shadow-xl space-y-5">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-[15px] font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider flex items-center gap-2">
                <span>🔒 {{ currentLang === 'km' ? 'ព័ត៌មានអត្តសញ្ញាណផ្លូវការ (ជាប់សោរ)' : 'Official Locked Records' }}</span>
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ currentLang === 'km' ? 'ឈ្មោះ អត្តលេខនិស្សិត និងជំនាញត្រូវបានចាក់សោរដោយរដ្ឋបាល។ សូមចុច "ស្នើកែតម្រូវ" ប្រសិនបើមានកំហុសអក្ខរាវិរុទ្ធ។' : 'Full name, Student ID, and Program are verified by the registry. Click "Request Correction" to submit change requests.' }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4.5 text-xs">
            
            <!-- Full Name Card -->
            <div class="p-4.5 bg-[#f8fafc] dark:bg-[#131314] rounded-2xl border border-[#dfe3e7] dark:border-[#2d2f31] space-y-1 relative hover:border-indigo-400 transition-colors group">
              <div class="flex items-center justify-between">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Full Name</span>
                <span class="text-slate-400 text-xs">🔒</span>
              </div>
              <p class="text-sm font-bold text-slate-900 dark:text-white pt-0.5">{{ profile.name }}</p>
              <button
                type="button"
                @click="openCorrectionModal('name')"
                class="mt-2 text-indigo-600 dark:text-indigo-400 hover:underline text-[11px] font-bold inline-flex items-center gap-1 cursor-pointer"
              >
                <span>{{ currentLang === 'km' ? 'ស្នើកែតម្រូវ' : 'Request Correction' }}</span>
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>

            <!-- Student ID Card -->
            <div class="p-4.5 bg-[#f8fafc] dark:bg-[#131314] rounded-2xl border border-[#dfe3e7] dark:border-[#2d2f31] space-y-1 relative hover:border-indigo-400 transition-colors group">
              <div class="flex items-center justify-between">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Official Student ID</span>
                <span class="text-slate-400 text-xs">🔒</span>
              </div>
              <p class="text-sm font-mono font-bold text-slate-900 dark:text-white pt-0.5">{{ profile.student_id }}</p>
              <button
                type="button"
                @click="openCorrectionModal('student_code')"
                class="mt-2 text-indigo-600 dark:text-indigo-400 hover:underline text-[11px] font-bold inline-flex items-center gap-1 cursor-pointer"
              >
                <span>{{ currentLang === 'km' ? 'ស្នើកែតម្រូវ' : 'Request Correction' }}</span>
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>

            <!-- Academic Major Card -->
            <div class="p-4.5 bg-[#f8fafc] dark:bg-[#131314] rounded-2xl border border-[#dfe3e7] dark:border-[#2d2f31] space-y-1 relative hover:border-indigo-400 transition-colors group">
              <div class="flex items-center justify-between">
                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Major Specialization</span>
                <span class="text-slate-400 text-xs">🔒</span>
              </div>
              <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400 pt-0.5">{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</p>
              <button
                type="button"
                @click="openCorrectionModal('major')"
                class="mt-2 text-indigo-600 dark:text-indigo-400 hover:underline text-[11px] font-bold inline-flex items-center gap-1 cursor-pointer"
              >
                <span>{{ currentLang === 'km' ? 'ស្នើកែតម្រូវ' : 'Request Correction' }}</span>
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>

          </div>

          <!-- Pending Correction Requests -->
          <div v-if="changeRequests && changeRequests.length > 0" class="pt-2 space-y-2">
            <p class="text-xs font-bold text-slate-700 dark:text-slate-300">
              {{ currentLang === 'km' ? 'សំណើកែតម្រូវដែលបានដាក់ស្នើ:' : 'Submitted Correction Requests:' }}
            </p>
            <div class="space-y-1.5">
              <div
                v-for="req in changeRequests"
                :key="req.id"
                class="p-3 bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-xl flex items-center justify-between text-xs"
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
                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold border capitalize'
                  ]"
                >
                  {{ req.status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 2: Editable Personal Details Form -->
        <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 sm:p-7 shadow-sm dark:shadow-xl space-y-6">
          <div>
            <h3 class="text-[15px] font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">
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
                  class="w-full bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:bg-white dark:focus:bg-[#1e1f20] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-khmer"
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
                  class="w-full bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:bg-white dark:focus:bg-[#1e1f20] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
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
                  <span class="absolute left-3.5 top-3 text-slate-400 font-mono text-xs">@</span>
                  <input
                    v-model="personalForm.telegram_username"
                    type="text"
                    class="w-full pl-8 bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:bg-white dark:focus:bg-[#1e1f20] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
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
                  class="w-full bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:bg-white dark:focus:bg-[#1e1f20] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all cursor-pointer"
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
                class="w-full bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:bg-white dark:focus:bg-[#1e1f20] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
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
                class="w-full bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:bg-white dark:focus:bg-[#1e1f20] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                :placeholder="currentLang === 'km' ? 'សរសេរសេចក្តីពិពណ៌នាខ្លីៗអំពីខ្លួនអ្នក...' : 'Write a short description about yourself...'"
              ></textarea>
            </div>

            <!-- Save Button -->
            <div class="pt-2">
              <button
                type="submit"
                :disabled="personalForm.processing"
                class="px-7 py-3 rounded-2xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:from-[#4f46e5] hover:to-[#6d28d9] text-white font-bold text-xs shadow-md shadow-indigo-500/25 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 hover:scale-[1.01] active:scale-[0.99]"
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

      <!-- TAB 3: ACADEMIC INFORMATION (3 Cards Layout identical to Image 3) -->
      <div v-show="currentTab === 'academic'" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          
          <!-- Card 1: Faculty & Department -->
          <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 hover:dark:border-purple-400/60 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-4 transition-all hover:-translate-y-1">
            <div class="flex items-center justify-between min-h-[32px]">
              <div class="flex items-center gap-1.5 text-[15px] font-bold text-slate-900 dark:text-slate-100">
                <span>{{ currentLang === 'km' ? 'មហាវិទ្យាល័យ' : 'Faculty & Dept' }}</span>
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
              </div>
              <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 text-[11px] font-bold">
                {{ currentLang === 'km' ? 'ផ្លូវការ' : 'Official' }}
              </span>
            </div>

            <div class="space-y-3 my-auto">
              <div>
                <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">{{ currentLang === 'km' ? 'មហាវិទ្យាល័យ' : 'Faculty Name' }}</p>
                <p class="text-sm font-bold text-slate-900 dark:text-slate-100 mt-0.5">{{ currentLang === 'km' ? (academic.faculty_kh || academic.faculty) : academic.faculty }}</p>
              </div>

              <div>
                <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">{{ currentLang === 'km' ? 'ដេប៉ាតឺម៉ង់' : 'Department Name' }}</p>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mt-0.5">{{ currentLang === 'km' ? (academic.department_kh || academic.department) : academic.department }}</p>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
              <span>{{ currentLang === 'km' ? 'ស្ថាប័ន' : 'Institute' }}</span>
              <span class="font-bold text-slate-800 dark:text-slate-200">{{ currentLang === 'km' ? (idCard.institution_kh || idCard.institution) : idCard.institution }}</span>
            </div>
          </div>

          <!-- Card 2: Major Specialization -->
          <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 hover:dark:border-purple-400/60 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-4 transition-all hover:-translate-y-1">
            <div class="flex items-center justify-between min-h-[32px]">
              <div class="flex items-center gap-1.5 text-[15px] font-bold text-slate-900 dark:text-slate-100">
                <span>{{ currentLang === 'km' ? 'ជំនាញឯកទេស' : 'Major Program' }}</span>
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
              </div>
              <span class="px-2.5 py-0.5 rounded-full bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white text-[11px] font-bold shadow-xs">
                {{ academic.degree_level }}
              </span>
            </div>

            <div class="space-y-2.5 my-auto">
              <p class="text-base font-black text-indigo-600 dark:text-indigo-400 leading-snug">
                {{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}
              </p>
              <div class="flex items-center gap-2 text-xs">
                <span class="px-2.5 py-1 rounded-xl bg-[#f0f4f9] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] text-slate-700 dark:text-slate-300 font-semibold">
                  {{ academic.study_type }}
                </span>
                <span class="px-2.5 py-1 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-semibold">
                  {{ currentLang === 'km' ? 'កំពុងដំណើរការ' : 'Active Track' }}
                </span>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
              <span>{{ currentLang === 'km' ? 'កម្រិតសញ្ញាបត្រ' : 'Degree Level' }}</span>
              <span class="font-bold text-purple-600 dark:text-purple-400">{{ academic.degree_level }}</span>
            </div>
          </div>

          <!-- Card 3: Semester & Academic Term (Shown only once) -->
          <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] hover:border-purple-400 hover:dark:border-purple-400/60 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-4 transition-all hover:-translate-y-1">
            <div class="flex items-center justify-between min-h-[32px]">
              <div class="flex items-center gap-1.5 text-[15px] font-bold text-slate-900 dark:text-slate-100">
                <span>{{ currentLang === 'km' ? 'ឆមាស & ឆ្នាំសិក្សា' : 'Term & Academic Year' }}</span>
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
              </div>
              <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[11px] font-bold">
                {{ currentLang === 'km' ? 'បច្ចុប្បន្ន' : 'Current' }}
              </span>
            </div>

            <div class="space-y-2 my-auto">
              <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">{{ currentLang === 'km' ? 'ឆមាសបច្ចុប្បន្ន' : 'Active Semester' }}</p>
              <p class="text-base font-black text-emerald-600 dark:text-emerald-400 leading-tight">
                {{ currentLang === 'km' ? (academic.term_kh || academic.term) : academic.term }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ currentLang === 'km' ? 'ការចុះឈ្មោះសិក្សាត្រូវបានផ្ទៀងផ្ទាត់ដោយប្រព័ន្ធ E-LMS' : 'Enrollment officially verified by E-LMS' }}
              </p>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
              <span>{{ currentLang === 'km' ? 'ស្ថានភាពសិក្សា' : 'Status' }}</span>
              <span class="font-bold text-emerald-600 dark:text-emerald-400">Enrolled & Active</span>
            </div>
          </div>

        </div>

      </div>

      <!-- TAB 4: SECURITY & PASSWORD (Password Update, Active Sessions, Login History) -->
      <div v-show="currentTab === 'security'" class="space-y-6">
        
        <!-- Password Form -->
        <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 sm:p-7 shadow-sm dark:shadow-xl space-y-6">
          <div>
            <h3 class="text-[15px] font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">
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
                  class="w-full pr-10 bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:bg-white dark:focus:bg-[#1e1f20] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                />
                <button
                  type="button"
                  @click="showCurrentPassword = !showCurrentPassword"
                  class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-600 text-xs cursor-pointer font-bold"
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
                  class="w-full pr-10 bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:bg-white dark:focus:bg-[#1e1f20] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                />
                <button
                  type="button"
                  @click="showNewPassword = !showNewPassword"
                  class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-600 text-xs cursor-pointer font-bold"
                >
                  {{ showNewPassword ? 'Hide' : 'Show' }}
                </button>
              </div>

              <!-- Strength Meter -->
              <div v-if="passwordForm.password" class="mt-2 space-y-1">
                <div class="w-full bg-[#e1e3e1] dark:bg-[#282a2c] rounded-full h-1.5 overflow-hidden">
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
                  class="w-full pr-10 bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-xs text-slate-900 dark:text-white focus:outline-none focus:bg-white dark:focus:bg-[#1e1f20] focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                />
                <button
                  type="button"
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-600 text-xs cursor-pointer font-bold"
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
                class="px-7 py-3 rounded-2xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] hover:from-[#4f46e5] hover:to-[#6d28d9] text-white font-bold text-xs shadow-md shadow-indigo-500/25 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 hover:scale-[1.01] active:scale-[0.99]"
              >
                <span>{{ passwordForm.processing ? (currentLang === 'km' ? 'កំពុងប្តូរ...' : 'Updating...') : (currentLang === 'km' ? 'ប្តូរពាក្យសម្ងាត់' : 'Update Password') }}</span>
              </button>
            </div>

          </form>
        </div>

        <!-- Active Sessions Panel -->
        <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 sm:p-7 shadow-sm dark:shadow-xl space-y-5">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-[15px] font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">
                {{ currentLang === 'km' ? 'ឧបករណ៍កំពុង Login ក្នុងគណនី (Active Sessions)' : 'Active Sessions' }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ currentLang === 'km' ? 'បញ្ជីឧបករណ៍ និងកម្មវិធីរុករកដែលកំពុងភ្ជាប់ជាមួយគណនីរបស់អ្នក' : 'Devices and browsers currently logged into your account' }}
              </p>
            </div>

            <button
              type="button"
              @click="isLogoutOthersModalOpen = true"
              class="px-4 py-2 rounded-2xl bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-100 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60 font-bold text-xs transition-all cursor-pointer inline-flex items-center gap-1.5 shrink-0 self-start"
            >
              <span>{{ currentLang === 'km' ? 'ចាកចេញពីឧបករណ៍ផ្សេងទៀត' : 'Sign Out Other Devices' }}</span>
            </button>
          </div>

          <div class="space-y-2.5">
            <div
              v-for="sess in security.active_sessions"
              :key="sess.id"
              class="p-4 bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl flex items-center justify-between gap-4 text-xs"
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
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
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
        <div class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 sm:p-7 shadow-sm dark:shadow-xl space-y-4">
          <h3 class="text-[15px] font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">
            {{ currentLang === 'km' ? 'ប្រវត្តិនៃការចូលប្រើប្រាស់ (Login History)' : 'Recent Login History' }}
          </h3>

          <div class="space-y-2">
            <div
              v-for="h in security.login_history"
              :key="h.id"
              class="p-3.5 bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl flex items-center justify-between text-xs"
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

      <!-- TAB 5: LEARNING HISTORY & LOGS -->
      <div v-show="currentTab === 'history'" class="bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 sm:p-7 shadow-sm dark:shadow-xl space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#dfe3e7] dark:border-[#2d2f31] pb-5">
          <div>
            <h3 class="text-[15px] font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider">
              {{ currentLang === 'km' ? 'ប្រវត្តិនៃការរៀន & កំណត់ហេតុសកម្មភាព' : 'Learning Activity History & Logs' }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              {{ currentLang === 'km' ? 'កំណត់ត្រាពិតប្រាកដអំពីការទស្សនាមេរៀន ការប្រឡង ការដាក់កិច្ចការ និងការទូទាត់' : 'Complete audit trail of all lesson completions, quiz attempts, assignments, and payments' }}
            </p>
          </div>

          <!-- CSV Export Button with Image 3 styling -->
          <a
            href="/student/profile/history/export-csv"
            class="px-4 py-2 rounded-2xl bg-[#f0f4f9] dark:bg-[#131314] hover:bg-slate-200 dark:hover:bg-[#282a2c] text-slate-700 dark:text-slate-200 text-xs font-bold transition-all inline-flex items-center gap-1.5 self-start cursor-pointer border border-[#dfe3e7] dark:border-[#2d2f31]"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>{{ currentLang === 'km' ? 'នាំចេញជា CSV' : 'Export CSV' }}</span>
          </a>
        </div>

        <!-- Filter Segmented Control Track matching Image 3 -->
        <div class="inline-flex items-center gap-1 p-1 bg-[#f0f4f9] dark:bg-[#131314] rounded-2xl border border-[#dfe3e7] dark:border-[#2d2f31] overflow-x-auto max-w-full">
          <button
            type="button"
            v-for="typeOpt in ['all', 'lesson', 'quiz', 'assignment', 'payment', 'certificate']"
            :key="typeOpt"
            @click="selectedHistoryType = typeOpt as any"
            :class="[
              selectedHistoryType === typeOpt
                ? 'bg-[#6366f1] text-white font-bold shadow-xs'
                : 'text-[#444746] dark:text-[#c4c7c5] hover:text-[#1f1f1f] dark:hover:text-white',
              'px-3.5 py-1.5 rounded-xl text-xs capitalize transition-all cursor-pointer shrink-0'
            ]"
          >
            {{ typeOpt === 'all' ? (currentLang === 'km' ? 'ទាំងអស់' : 'All') : typeOpt === 'lesson' ? (currentLang === 'km' ? 'មេរៀន' : 'Lessons') : typeOpt === 'quiz' ? 'Quiz' : typeOpt === 'assignment' ? (currentLang === 'km' ? 'កិច្ចការ' : 'Assignments') : typeOpt === 'payment' ? (currentLang === 'km' ? 'ការបង់ប្រាក់' : 'Payments') : (currentLang === 'km' ? 'វិញ្ញាបនបត្រ' : 'Certificates') }}
          </button>
        </div>

        <!-- Logs List -->
        <div v-if="filteredHistoryLogs.length > 0" class="space-y-3">
          <div
            v-for="log in filteredHistoryLogs"
            :key="log.id"
            class="p-4 bg-[#f8fafc] dark:bg-[#131314] hover:bg-slate-100/70 dark:hover:bg-[#232426] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs transition-colors"
          >
            <div class="flex items-center gap-3">
              <span
                class="px-2.5 py-1 rounded-xl text-[10px] font-bold border uppercase shrink-0"
                :class="getLogTypeBadge(log.type).class"
              >
                {{ getLogTypeBadge(log.type).label }}
              </span>
              <div>
                <p class="font-bold text-slate-900 dark:text-white">
                  {{ currentLang === 'km' ? (log.action_kh || log.action) : log.action }}
                </p>
                <p class="text-slate-500 dark:text-slate-400 text-[11px] mt-0.5">
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
        <div v-else class="text-center py-12 space-y-2 rounded-2xl border border-dashed border-[#dfe3e7] dark:border-[#2d2f31] p-8">
          <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
            {{ currentLang === 'km' ? 'មិនមានសកម្មភាពសម្រាប់ប្រភេទដែលបានជ្រើសរើសឡើយ' : 'No activity records found for this filter' }}
          </p>
          <p class="text-xs text-slate-400">
            {{ currentLang === 'km' ? 'កំណត់ត្រានឹងបង្ហាញនៅទីនេះនៅពេលអ្នកចាប់ផ្តើមរៀន និងប្រឡង' : 'Your activity history will appear here once you take lessons or quizzes' }}
          </p>
        </div>

      </div>

    </div>

    <!-- DIALOG MODAL: Regenerate QR Confirmation with Warning -->
    <div
      v-if="isRegenerateModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
      @click.self="isRegenerateModalOpen = false"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="regenerate-dialog-title"
        class="w-full max-w-md bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 shadow-2xl space-y-4"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div>
            <h3 id="regenerate-dialog-title" class="text-base font-bold text-slate-900 dark:text-white">
              {{ currentLang === 'km' ? 'បញ្ជាក់ការបង្កើតកូដ QR ថ្មី' : 'Confirm QR Code Regeneration' }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ currentLang === 'km' ? 'សកម្មភាពនេះមិនអាចត្រឡប់ថយក្រោយបានឡើយ' : 'This action cannot be undone' }}
            </p>
          </div>
        </div>

        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-[#f8fafc] dark:bg-[#131314] p-3.5 rounded-2xl border border-[#dfe3e7] dark:border-[#2d2f31]">
          {{ currentLang === 'km' ? 'តើអ្នកពិតជាចង់បង្កើតកូដ QR ថ្មីមែនទេ? តំណភ្ជាប់ផ្ទៀងផ្ទាត់ និងកូដ QR ចាស់ទាំងអស់នឹងលែងដំណើរការជារៀងរហូត។' : 'Are you sure you want to regenerate your digital ID QR code? The previous QR code and public verification link will permanently stop working.' }}
        </p>

        <div class="flex items-center justify-end gap-2.5 pt-2">
          <button
            type="button"
            @click="isRegenerateModalOpen = false"
            class="px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold text-xs cursor-pointer"
          >
            {{ currentLang === 'km' ? 'បោះបង់' : 'Cancel' }}
          </button>
          <button
            type="button"
            :disabled="isRegeneratingToken"
            @click="confirmRegenerateToken"
            class="px-5 py-2.5 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-md cursor-pointer disabled:opacity-50 inline-flex items-center gap-1.5"
          >
            <svg v-if="isRegeneratingToken" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ isRegeneratingToken ? (currentLang === 'km' ? 'កំពុងបង្កើត...' : 'Regenerating...') : (currentLang === 'km' ? 'បង្កើត QR ថ្មី' : 'Regenerate QR') }}</span>
          </button>
        </div>

      </div>
    </div>

    <!-- MODAL: Request Correction for Locked Field -->
    <div
      v-if="isCorrectionModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
      @click.self="isCorrectionModalOpen = false"
    >
      <div class="w-full max-w-md bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 shadow-2xl space-y-4">
        
        <div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            {{ currentLang === 'km' ? 'ស្នើកែតម្រូវទិន្នន័យផ្លូវការ' : 'Request Official Record Correction' }}
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{ currentLang === 'km' ? 'សំណើរបស់អ្នកនឹងត្រូវផ្ញើទៅកាន់ការិយាល័យសិក្សាដើម្បីត្រួតពិនិត្យ' : 'Your request will be submitted to the registrar for review' }}
          </p>
        </div>

        <form @submit.prevent="submitCorrectionRequest" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ currentLang === 'km' ? 'ព័ត៌មានដែលស្នើកែ:' : 'Target Field:' }}
            </label>
            <input
              type="text"
              readonly
              :value="correctionField"
              class="w-full bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-slate-500 font-mono capitalize"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ currentLang === 'km' ? 'តម្លៃត្រឹមត្រូវថ្មី:' : 'New Correct Value:' }}
            </label>
            <input
              v-model="correctionForm.new_value"
              type="text"
              required
              class="w-full bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
              :placeholder="currentLang === 'km' ? 'បញ្ចូលតម្លៃត្រឹមត្រូវ...' : 'Enter the corrected value...'"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ currentLang === 'km' ? 'មូលហេតុនៃការកែតម្រូវ:' : 'Reason for Correction:' }}
            </label>
            <textarea
              v-model="correctionForm.reason"
              rows="3"
              required
              class="w-full bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
              :placeholder="currentLang === 'km' ? 'បញ្ជាក់មូលហេតុ (ឧ. ខុសអក្ខរាវិរុទ្ធលើសំបុត្រកំណើត...)' : 'Explain why this needs to be changed...'"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              @click="isCorrectionModalOpen = false"
              class="px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold cursor-pointer"
            >
              {{ currentLang === 'km' ? 'បោះបង់' : 'Cancel' }}
            </button>
            <button
              type="submit"
              :disabled="correctionForm.processing"
              class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-[#6366f1] to-[#7c3aed] text-white font-bold shadow-md cursor-pointer disabled:opacity-50"
            >
              {{ correctionForm.processing ? (currentLang === 'km' ? 'កំពុងផ្ញើ...' : 'Submitting...') : (currentLang === 'km' ? 'ដាក់ស្នើសំណើ' : 'Submit Request') }}
            </button>
          </div>
        </form>

      </div>
    </div>

    <!-- MODAL: Logout Others Devices Confirmation -->
    <div
      v-if="isLogoutOthersModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
      @click.self="isLogoutOthersModalOpen = false"
    >
      <div class="w-full max-w-md bg-white dark:bg-[#1e1f20] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-3xl p-6 shadow-2xl space-y-4">
        
        <div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">
            {{ currentLang === 'km' ? 'បញ្ជាក់ការចាកចេញពីឧបករណ៍ផ្សេងទៀត' : 'Confirm Sign Out Other Devices' }}
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{ currentLang === 'km' ? 'សូមបញ្ចូលពាក្យសម្ងាត់របស់អ្នកដើម្បីបញ្ជាក់ និងចាកចេញពីគ្រប់ឧបករណ៍ទាំងអស់លើកលែងតែឧបករណ៍នេះ' : 'Please enter your password to sign out of all other sessions' }}
          </p>
        </div>

        <form @submit.prevent="submitLogoutOthers" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              {{ currentLang === 'km' ? 'ពាក្យសម្ងាត់:' : 'Current Password:' }}
            </label>
            <input
              v-model="logoutOthersForm.password"
              type="password"
              required
              class="w-full bg-[#f8fafc] dark:bg-[#131314] border border-[#dfe3e7] dark:border-[#2d2f31] rounded-2xl p-3 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
            />
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              @click="isLogoutOthersModalOpen = false"
              class="px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold cursor-pointer"
            >
              {{ currentLang === 'km' ? 'បោះបង់' : 'Cancel' }}
            </button>
            <button
              type="submit"
              :disabled="logoutOthersForm.processing"
              class="px-5 py-2.5 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold shadow-md cursor-pointer disabled:opacity-50"
            >
              {{ logoutOthersForm.processing ? (currentLang === 'km' ? 'កំពុងចាកចេញ...' : 'Signing out...') : (currentLang === 'km' ? 'ចាកចេញពីឧបករណ៍ផ្សេង' : 'Sign Out Others') }}
            </button>
          </div>
        </form>

      </div>
    </div>

    <!-- DEDICATED PRINTABLE CR80 CONTAINER (Printed on 2 pages: Front on Page 1, Back on Page 2) -->
    <div id="printable-cr80-document" class="hidden print:block">
      
      <!-- PAGE 1: CR80 CARD FRONT SIDE -->
      <div class="cr80-page-front w-[85.6mm] h-[54mm] p-[4.5mm] bg-[#070e24] text-white flex flex-col justify-between rounded-[3.18mm] relative overflow-hidden">
        
        <!-- Top Row -->
        <div class="flex items-center justify-between border-b border-slate-700 pb-1">
          <div class="flex items-center gap-2">
            <div class="w-[7mm] h-[7mm] rounded-full overflow-hidden flex items-center justify-center shrink-0">
              <img :src="cardLogoUrl" alt="Logo" class="w-full h-full object-cover" />
            </div>
            <div>
              <p class="text-[7.5pt] font-black uppercase tracking-wider text-slate-100">
                {{ currentLang === 'km' ? (idCard.institution_kh || idCard.institution) : idCard.institution }}
              </p>
              <p class="text-[5.5pt] text-amber-300 font-semibold">
                SAINT PAUL INSTITUTE
              </p>
            </div>
          </div>
          <span
            v-if="idCard.is_official"
            class="px-1.5 py-0.5 rounded-full bg-emerald-500 text-white text-[5.5pt] font-black uppercase"
          >
            OFFICIAL ID
          </span>
        </div>

        <!-- Middle Row: 3 Balanced Sections (Photo, Details, Chip) -->
        <div class="flex items-center gap-2.5 my-auto">
          <div class="w-[18mm] h-[18mm] rounded-lg overflow-hidden bg-slate-900 border border-slate-600 flex items-center justify-center shrink-0">
            <img
              v-if="avatarPreview"
              :src="avatarPreview"
              :alt="profile.name"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-sm font-bold text-white">
              {{ profile.name ? profile.name.charAt(0).toUpperCase() : 'S' }}
            </span>
          </div>

          <div class="space-y-0.5 min-w-0 flex-1">
            <p class="text-[9pt] font-black leading-tight text-white">{{ profile.name }}</p>
            <p v-if="profile.name_kh" class="text-[7pt] font-semibold text-indigo-300">{{ profile.name_kh }}</p>
            <p class="text-[7pt] font-mono font-bold text-amber-200">ID: {{ profile.student_id }}</p>
            <p class="text-[6pt] text-slate-200">{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</p>
            <p v-if="academic.faculty || academic.faculty_kh" class="text-[5.5pt] text-slate-400">
              {{ currentLang === 'km' ? (academic.faculty_kh || academic.faculty) : academic.faculty }}
            </p>
          </div>

          <!-- Metallic Gold Chip in Print -->
          <div class="w-[7mm] h-[5.5mm] rounded bg-amber-400 border border-amber-600 shrink-0 flex items-center justify-center">
            <div class="w-[3.5mm] h-[2.5mm] border border-amber-800 bg-amber-200"></div>
          </div>
        </div>

        <!-- Bottom Row: Card No, Term, Valid Thru -->
        <div class="pt-1 border-t border-slate-700 flex items-center justify-between text-[6pt] text-slate-200 whitespace-nowrap">
          <span>TERM: {{ currentLang === 'km' ? (academic.term_kh || academic.term) : academic.term }}</span>
          <span class="font-mono font-bold">CARD: {{ idCard.card_no }}</span>
          <span>EXP: {{ currentLang === 'km' ? (idCard.expires_at_kh || idCard.expires_at) : idCard.expires_at }}</span>
        </div>

      </div>

      <!-- PAGE 2: CR80 CARD BACK SIDE -->
      <div class="cr80-page-back w-[85.6mm] h-[54mm] p-[4.5mm] bg-[#070e24] text-white flex flex-col justify-between rounded-[3.18mm] relative overflow-hidden">
        
        <!-- Magnetic Stripe -->
        <div class="w-full h-[6mm] bg-black rounded border border-slate-800 flex items-center px-2">
          <span class="text-[5pt] font-mono text-slate-400 tracking-widest uppercase">STUDENT IDENTITY VERIFICATION • SPI</span>
        </div>

        <!-- Terms & QR -->
        <div class="flex items-center justify-between gap-3 my-auto py-1">
          <div class="bg-white p-1 rounded-lg shrink-0">
            <img
              v-if="qrDataUrl"
              :src="qrDataUrl"
              alt="Verification QR"
              class="w-[18mm] h-[18mm] object-contain"
            />
          </div>

          <div class="space-y-1 text-[6pt] text-slate-300 max-w-[50mm] leading-tight flex-1">
            <div class="w-full bg-slate-200 text-slate-800 px-1 py-0.5 rounded text-[5pt] font-mono flex justify-between">
              <span>AUTHORIZED SIGNATURE</span>
              <span>{{ profile.student_id }}</span>
            </div>
            <p class="text-slate-300">
              {{ currentLang === 'km' ? 'កាតនេះជាកម្មសិទ្ធិរបស់វិទ្យាស្ថាន សន្តប៉ូល។ ប្រសិនបើរើសបាន សូមមេត្តាប្រគល់ជូនការិយាល័យសិក្សា ឬទាក់ទងមកវិទ្យាស្ថាន។' : 'This card is issued by Saint Paul Institute. If found, return to the office.' }}
            </p>
            <div class="flex justify-between text-[5.5pt] text-slate-300 pt-0.5 border-t border-slate-700">
              <span>ISSUED: {{ currentLang === 'km' ? (idCard.issued_at_kh || idCard.issued_at) : idCard.issued_at }}</span>
              <span>EXPIRES: {{ currentLang === 'km' ? (idCard.expires_at_kh || idCard.expires_at) : idCard.expires_at }}</span>
            </div>
          </div>
        </div>

        <!-- Token Hash & Website -->
        <div class="text-[5.5pt] font-mono text-slate-400 flex items-center justify-between border-t border-slate-700 pt-1">
          <span>spilms.tech</span>
          <span>•••• •••• {{ idCard.token ? idCard.token.slice(-4) : '••••' }}</span>
        </div>

      </div>

    </div>

  </StudentLayout>
</template>

<style scoped>
/* 3D Flip Card Scene & Transforms */
.card-3d-scene {
  perspective: 1200px;
}
.card-3d-inner {
  transform-style: preserve-3d;
  will-change: transform;
}
.card-3d-inner.is-flipped {
  transform: rotateY(180deg);
}
.card-3d-face {
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
}
.card-3d-front {
  transform: rotateY(0deg);
}
.card-3d-back {
  transform: rotateY(180deg);
}

@media print {
  @page {
    size: 85.6mm 54mm;
    margin: 0;
  }
  body * {
    visibility: hidden !important;
  }
  #printable-cr80-document,
  #printable-cr80-document * {
    visibility: visible !important;
  }
  #printable-cr80-document {
    position: fixed !important;
    left: 0 !important;
    top: 0 !important;
    width: 85.6mm !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
    background: transparent !important;
  }
  .cr80-page-front {
    width: 85.6mm !important;
    height: 54mm !important;
    page-break-after: always !important;
    break-after: page !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    box-sizing: border-box !important;
  }
  .cr80-page-back {
    width: 85.6mm !important;
    height: 54mm !important;
    page-break-before: always !important;
    break-before: page !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    box-sizing: border-box !important;
  }
}
</style>
