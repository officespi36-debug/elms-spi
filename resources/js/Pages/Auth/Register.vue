<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { i18n, type LanguageCode } from '../../Services/i18n'
import AuthAnimatedBackground from '../../Components/AuthAnimatedBackground.vue'
import NetworkStatusPill from '../../Components/NetworkStatusPill.vue'

const logoUrl = '/images/logo.png'

interface Major {
  id: number
  name: string
  name_kh?: string
  department?: {
    id: number
    name: string
    faculty?: {
      id: number
      name: string
    }
  }
}

const props = defineProps<{
  majors?: Major[]
}>()

const step = ref<1 | 2 | 3>(1)
const maxStepReached = ref<number>(1)
const isDark = ref(true)
const isLangOpen = ref(false)

const showSuccessModal = ref(false)
const showErrorModal = ref(false)
const successTitle = ref('')
const successMessage = ref('')
const errorTitle = ref('')
const errorMessage = ref('')

const isRegistering = ref(false)
const registerLoadingTitle = ref('')
const registerLoadingSubtitle = ref('')

const languages = [
  { code: 'km' as LanguageCode, name: 'ភាសាខ្មែរ', label: 'ខ្មែរ', short: 'KH', flagUrl: '/images/flags/km.svg' },
  { code: 'en' as LanguageCode, name: 'English', label: 'English', short: 'EN', flagUrl: '/images/flags/en.svg' },
]

const currentLang = computed(() => i18n.locale.value)

const selectLanguage = (code: LanguageCode) => {
  i18n.setLanguage(code)
  isLangOpen.value = false
}

const t = (key: string, defaultText?: string) => {
  return i18n.t(key, defaultText)
}

const initTheme = () => {
  const saved = localStorage.getItem('theme')
  if (saved) {
    isDark.value = saved === 'dark'
  } else {
    isDark.value = true
  }
  applyTheme()
}

const toggleTheme = () => {
  isDark.value = !isDark.value
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
  applyTheme()
}

const applyTheme = () => {
  if (isDark.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.lang-switcher-container')) {
    isLangOpen.value = false
  }
}

onMounted(() => {
  initTheme()
  document.addEventListener('click', handleClickOutside)

  // Pre-fill email and role from URL query parameters if redirected from Login flow
  try {
    const params = new URLSearchParams(window.location.search)
    const emailParam = params.get('email')
    const roleParam = params.get('role')
    if (emailParam) {
      form.email = emailParam
      if (!form.name) {
        const prefix = emailParam.split('@')[0]
        form.name = prefix.replace(/[._-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
      }
    }
    if (roleParam === 'teacher' || roleParam === 'student') {
      form.role = roleParam
    }
  } catch (_) {}
})

const form = useForm({
  name: '',
  name_kh: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  role: 'student' as 'student' | 'teacher',
  major_id: null as number | null,
  study_type: 'on_campus' as 'on_campus' | 'online',
  payment_method: 'aba' as 'aba' | 'cash',
  receipt: null as File | null,
  payment_confirmed: false,
  terms: false,
})

const showPassword = ref(false)
const receiptPreview = ref<string | null>(null)

// Fallback mappings for major details
const majorDetailsMap: Record<string, { faculty: string; department: string }> = {
  'IT & Networking': {
    faculty: 'Faculty of Science & Technology',
    department: 'Dept of IT & Networking',
  },
  'Tourism Management': {
    faculty: 'Faculty of Tourism & Hospitality',
    department: 'Dept of Tourism & Hospitality',
  },
  'English Literature': {
    faculty: 'Faculty of Arts & Humanities',
    department: 'Dept of English Literature',
  },
  'Agronomy': {
    faculty: 'Faculty of Agriculture',
    department: 'Dept of Agronomy & Crop Science',
  },
  'Social Work': {
    faculty: 'Faculty of Social Sciences',
    department: 'Dept of Social Work',
  },
}

// Student ID preview
const studentIdDisplay = computed(() => {
  return 'STU-2026-' + Math.floor(10000 + Math.random() * 90000)
})

// Selected Major Details
const selectedMajorObj = computed(() => {
  if (!form.major_id || !props.majors) return null
  return props.majors.find(m => m.id === form.major_id) || null
})

const facultyName = computed(() => {
  if (!selectedMajorObj.value) return 'Select a major first'
  if (selectedMajorObj.value.department?.faculty?.name) {
    return selectedMajorObj.value.department.faculty.name
  }
  return majorDetailsMap[selectedMajorObj.value.name]?.faculty || 'Faculty of Applied Sciences'
})

const departmentName = computed(() => {
  if (!selectedMajorObj.value) return 'Select a major first'
  if (selectedMajorObj.value.department?.name) {
    return selectedMajorObj.value.department.name
  }
  return majorDetailsMap[selectedMajorObj.value.name]?.department || 'Academic Department'
})

const isPasswordValid = computed(() => {
  return form.password.length >= 8
})

const isStep1Valid = computed(() => {
  return (
    form.name.trim() !== '' &&
    form.email.includes('@') &&
    form.phone.trim() !== '' &&
    isPasswordValid.value &&
    form.password === form.password_confirmation &&
    form.terms
  )
})

const isStep2Valid = computed(() => {
  if (form.role === 'teacher') {
    return form.major_id !== null
  }
  return form.major_id !== null && form.study_type !== null
})

const goToStep = (targetStep: 1 | 2 | 3) => {
  if (targetStep === 1) {
    step.value = 1
  } else if (targetStep === 2 && isStep1Valid.value) {
    step.value = 2
    if (maxStepReached.value < 2) maxStepReached.value = 2
  } else if (targetStep === 3 && isStep1Valid.value && isStep2Valid.value) {
    step.value = 3
    if (maxStepReached.value < 3) maxStepReached.value = 3
  }
}

const nextStep = () => {
  if (step.value === 1 && isStep1Valid.value) {
    step.value = 2
    if (maxStepReached.value < 2) maxStepReached.value = 2
  } else if (step.value === 2 && isStep2Valid.value) {
    step.value = 3
    if (maxStepReached.value < 3) maxStepReached.value = 3
  }
}

const prevStep = () => {
  if (step.value > 1) {
    step.value = (step.value - 1) as 1 | 2 | 3
  }
}

// Auto-navigate on backend validation errors
watch(
  () => form.errors,
  (errors) => {
    if (!errors || Object.keys(errors).length === 0) return

    const step1Fields = ['name', 'name_kh', 'email', 'phone', 'password', 'password_confirmation', 'terms']
    const step2Fields = ['major_id', 'study_type']

    if (Object.keys(errors).some((key) => step1Fields.includes(key))) {
      step.value = 1
    } else if (Object.keys(errors).some((key) => step2Fields.includes(key))) {
      step.value = 2
    }
  },
  { deep: true }
)

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    form.receipt = file
    receiptPreview.value = URL.createObjectURL(file)
  }
}

const submit = () => {
  showErrorModal.value = false
  showSuccessModal.value = false
  isRegistering.value = true
  registerLoadingTitle.value = currentLang.value === 'km' ? 'កំពុងបង្កើតគណនី...' : 'Creating your account...'
  registerLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងដំណើរការរៀបចំព័ត៌មាន...' : 'Please wait a moment while setting up your profile...'

  form.post('/register', {
    onSuccess: () => {
      registerLoadingTitle.value = currentLang.value === 'km' ? 'ចុះឈ្មោះជោគជ័យ!' : 'Registration Successful!'
      registerLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត កំពុងនាំអ្នកទៅកាន់ផ្ទាំងគ្រប់គ្រង...' : 'Please wait a moment, redirecting to your dashboard...'
    },
    onError: (errors) => {
      isRegistering.value = false
      showErrorModal.value = true
      errorTitle.value = currentLang.value === 'km' ? 'ព័ត៌មានមិនត្រឹមត្រូវ' : 'Invalid Information'
      const firstKey = Object.keys(errors)[0]
      errorMessage.value = errors[firstKey] || (currentLang.value === 'km' ? 'សូមពិនិត្យមើលព័ត៌មាននៃការចុះឈ្មោះរបស់អ្នកឡើងវិញ!' : 'Please check your registration details and try again!')
      setTimeout(() => {
        showErrorModal.value = false
      }, 4500)
    },
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <div class="min-h-screen w-full bg-[#f8fafc] dark:bg-[#000000] text-zinc-900 dark:text-[#ededed] flex flex-col justify-between relative font-sans overflow-x-hidden select-none transition-colors duration-300">
    
    <!-- Top Header with Branding (Left) and Language/Theme Switchers (Right) -->
    <header class="w-full flex items-center justify-between px-6 sm:px-8 py-5 z-20">
      <Link href="/" class="flex items-center gap-2.5 text-zinc-900 dark:text-white cursor-pointer select-none group">
        <img :src="logoUrl" alt="E-LMS" class="w-7 h-7 rounded-full object-contain shadow-xs transition-transform duration-200 group-hover:scale-105" />
        <span class="font-extrabold text-base tracking-tight font-sans bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-white dark:to-zinc-200 bg-clip-text text-transparent">E LMS</span>
      </Link>

      <div class="flex items-center gap-3">
        <!-- Network Status Pill (Online / Offline) -->
        <NetworkStatusPill :current-lang="currentLang" />

        <!-- Language Switcher Pill -->
        <div class="relative lang-switcher-container">
          <button
            type="button"
            @click.stop="isLangOpen = !isLangOpen"
            class="h-8 px-2.5 rounded-full bg-zinc-200/70 dark:bg-[#18181b] hover:bg-zinc-300 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 transition-all duration-150 border border-zinc-300/80 dark:border-zinc-800/80 flex items-center gap-1.5 text-xs font-semibold cursor-pointer select-none"
            :title="currentLang === 'km' ? 'ប្តូរភាសា / Change Language' : 'Change Language / ប្តូរភាសា'"
          >
            <img
              :src="languages.find(l => l.code === currentLang)?.flagUrl || '/images/flags/km.svg'"
              :alt="currentLang"
              class="w-3.5 h-3.5 rounded-full object-cover shrink-0"
            />
            <span class="text-[11px] font-bold">
              {{ currentLang === 'km' ? 'KH' : 'EN' }}
            </span>
            <i :class="['pi pi-chevron-down text-[9px] text-zinc-500 transition-transform duration-150', isLangOpen ? 'rotate-180' : '']"></i>
          </button>

          <!-- Language Dropdown -->
          <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform opacity-0 scale-95 -translate-y-1"
            enter-to-class="transform opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform opacity-100 scale-100 translate-y-0"
            leave-to-class="transform opacity-0 scale-95 -translate-y-1"
          >
            <div
              v-if="isLangOpen"
              class="absolute right-0 mt-1.5 w-36 rounded-xl bg-white dark:bg-[#18181b] border border-zinc-200 dark:border-zinc-800 shadow-lg py-1 z-50 overflow-hidden"
            >
              <button
                v-for="lang in languages"
                :key="lang.code"
                type="button"
                @click="selectLanguage(lang.code)"
                :class="[
                  'w-full flex items-center justify-between px-3 py-2 text-xs font-medium transition-colors cursor-pointer select-none',
                  currentLang === lang.code
                    ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white font-bold'
                    : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/60'
                ]"
              >
                <span class="flex items-center gap-2">
                  <img :src="lang.flagUrl" :alt="lang.name" class="w-3.5 h-3.5 rounded-full object-cover shrink-0" />
                  <span>{{ lang.name }}</span>
                </span>
                <i v-if="currentLang === lang.code" class="pi pi-check text-[10px] text-zinc-900 dark:text-white font-bold shrink-0"></i>
              </button>
            </div>
          </Transition>
        </div>

        <!-- Theme Switcher Pill -->
        <button
          type="button"
          @click="toggleTheme"
          class="h-8 px-2.5 rounded-full bg-zinc-200/70 dark:bg-[#18181b] hover:bg-zinc-300 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 transition-all duration-150 border border-zinc-300/80 dark:border-zinc-800/80 flex items-center gap-1.5 text-xs font-semibold cursor-pointer select-none"
          :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        >
          <i :class="['pi text-xs', isDark ? 'pi-sun text-amber-400' : 'pi-moon text-zinc-600 dark:text-zinc-300']"></i>
        </button>
      </div>
    </header>

    <!-- Manus AI Style Interactive Dot-Matrix Canvas Background -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0 select-none">
      <AuthAnimatedBackground />
    </div>

    <!-- Centered Clean Form Stage (Manus Minimalist Style) -->
    <main class="w-full max-w-[460px] mx-auto px-4 py-4 z-10 my-auto flex flex-col items-center">
      
      <!-- Normal Form View (When not registering) -->
      <div v-if="!isRegistering" class="w-full flex flex-col items-center">
        <!-- Top Minimalist Icon & Heading -->
        <div class="flex flex-col items-center mb-5 text-center">
        <div class="mb-3.5 relative group">
          <div class="absolute -inset-1.5 bg-sky-500/20 rounded-full blur-md opacity-40 group-hover:opacity-80 transition duration-300 pointer-events-none"></div>
          <img
            :src="logoUrl"
            alt="E-LMS Logo"
            class="relative w-[72px] h-[72px] rounded-full drop-shadow-lg object-contain transition-transform duration-300 group-hover:scale-105"
          />
        </div>
        <h1 class="text-2xl sm:text-[26px] font-black tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-white dark:via-zinc-100 dark:to-zinc-300 bg-clip-text text-transparent">
          {{ t('register_title', 'បង្កើតគណនីថ្មី') }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 mt-1">
          {{ t('register_subtitle', 'ចាប់ផ្តើមបង្កើត និងរៀនសូត្រជាមួយ E-LMS') }}
        </p>
      </div>

      <div class="w-full space-y-3.5">

        <!-- Stepper Progress Header (Clean Minimalist) -->
        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-100/90 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 text-xs select-none shadow-2xs">
          <div class="flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-blue-600 dark:bg-white text-white dark:text-zinc-950 font-bold text-[10px] flex items-center justify-center shadow-xs">
              {{ step }}
            </span>
            <span class="text-slate-800 dark:text-white font-semibold text-xs">
              {{ step === 1 ? t('register_step1_header', 'Step 1 of 3: Account Info') : step === 2 ? t('register_step2_header', 'Step 2 of 3: Academic Details') : t('register_step3_header', 'Step 3 of 3: Verification') }}
            </span>
          </div>

          <!-- Progress Indicator Pills -->
          <div class="flex items-center gap-1.5">
            <button type="button" @click="goToStep(1)" :class="['w-6 h-1.5 rounded-full transition-all duration-200 cursor-pointer', step >= 1 ? 'bg-blue-600 dark:bg-white' : 'bg-slate-300 dark:bg-zinc-800']"></button>
            <button type="button" @click="goToStep(2)" :disabled="!isStep1Valid" :class="['w-6 h-1.5 rounded-full transition-all duration-200 disabled:opacity-40 cursor-pointer', step >= 2 ? 'bg-blue-600 dark:bg-white' : 'bg-slate-300 dark:bg-zinc-800']"></button>
            <button type="button" @click="goToStep(3)" :disabled="!isStep1Valid || !isStep2Valid" :class="['w-6 h-1.5 rounded-full transition-all duration-200 disabled:opacity-40 cursor-pointer', step >= 3 ? 'bg-blue-600 dark:bg-white' : 'bg-slate-300 dark:bg-zinc-800']"></button>
          </div>
        </div>

        <!-- Role Selection Tabs (Only Student & Teacher) -->
        <div class="grid grid-cols-2 p-1 rounded-xl bg-slate-100 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 text-xs shadow-2xs">
          <button
            type="button"
            @click="form.role = 'student'"
            :class="[
              'py-2.5 rounded-lg font-medium transition-all text-xs cursor-pointer flex items-center justify-center gap-1.5',
              form.role === 'student'
                ? 'bg-blue-600 dark:bg-zinc-800 text-white font-bold shadow-sm shadow-blue-500/20'
                : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'
            ]"
          >
            <i class="pi pi-graduation-cap text-xs"></i>
            <span>{{ t('register_tab_student', 'និស្សិត') }}</span>
          </button>
          <button
            type="button"
            @click="form.role = 'teacher'"
            :class="[
              'py-2.5 rounded-lg font-medium transition-all text-xs cursor-pointer flex items-center justify-center gap-1.5',
              form.role === 'teacher'
                ? 'bg-blue-600 dark:bg-zinc-800 text-white font-bold shadow-sm shadow-blue-500/20'
                : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'
            ]"
          >
            <i class="pi pi-briefcase text-xs"></i>
            <span>{{ t('register_tab_teacher', 'លោកគ្រូ/អ្នកគ្រូ') }}</span>
          </button>
        </div>

        <!-- Validation Errors Alert -->
        <div v-if="Object.keys(form.errors).length > 0" class="bg-rose-500/10 border border-rose-500/30 rounded-xl p-2.5 text-rose-600 dark:text-rose-300 text-xs space-y-1 animate-shake">
          <div class="font-bold flex items-center gap-1.5 text-rose-500 text-[11px]">
            <i class="pi pi-exclamation-circle text-xs"></i> {{ t('register_fix_errors', 'សូមពិនិត្យមើលព័ត៌មានឡើងវិញ៖') }}
          </div>
          <ul class="list-disc list-inside space-y-0.5 text-slate-700 dark:text-zinc-300 font-medium text-[11px]">
            <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
          </ul>
        </div>

        <form @submit.prevent="submit" class="space-y-3">
          
          <!-- STEP 1: ACCOUNT INFORMATION -->
          <div v-if="step === 1" class="space-y-3">
            
            <!-- Names Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <input
                v-model="form.name_kh"
                type="text"
                :placeholder="t('register_name_kh_placeholder', 'ឈ្មោះពេញ (ភាសាខ្មែរ)')"
                class="h-11 w-full px-3.5 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none transition-all duration-150 shadow-2xs font-khmer"
              />
              <input
                v-model="form.name"
                type="text"
                required
                :placeholder="t('register_name_en_placeholder', 'Full Name (Latin) *')"
                class="h-11 w-full px-3.5 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none transition-all duration-150 shadow-2xs"
              />
            </div>

            <!-- Contact Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <input
                v-model="form.email"
                type="email"
                required
                :placeholder="t('register_email_placeholder', 'Email Address *')"
                class="h-11 w-full px-3.5 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none transition-all duration-150 shadow-2xs"
              />
              <input
                v-model="form.phone"
                type="tel"
                required
                :placeholder="t('register_phone_placeholder', 'Phone Number *')"
                class="h-11 w-full px-3.5 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none transition-all duration-150 shadow-2xs"
              />
            </div>

            <!-- Passwords Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <div class="relative">
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  :placeholder="t('register_password_placeholder', 'Password (min. 8 chars) *')"
                  class="h-11 w-full pl-3.5 pr-9 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none transition-all duration-150 shadow-2xs"
                />
                <button type="button" @click="showPassword = !showPassword" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 cursor-pointer p-1">
                  <i :class="['pi text-xs', showPassword ? 'pi-eye-slash text-slate-700 dark:text-zinc-300' : 'pi-eye']"></i>
                </button>
              </div>

              <input
                v-model="form.password_confirmation"
                :type="showPassword ? 'text' : 'password'"
                required
                :placeholder="t('register_confirm_password_label', 'Confirm Password *')"
                class="h-11 w-full px-3.5 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none transition-all duration-150 shadow-2xs"
              />
            </div>

            <!-- Password Hint -->
            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-zinc-400">
              <i class="pi pi-info-circle text-[11px] text-blue-500 shrink-0"></i>
              <span>{{ t('register_password_hint', 'Must be at least 8 characters with numbers & letters') }}</span>
            </div>

            <!-- Terms Checkbox -->
            <label class="flex items-start gap-2 text-[11px] text-slate-600 dark:text-zinc-400 cursor-pointer select-none pt-1">
              <input
                v-model="form.terms"
                type="checkbox"
                required
                class="mt-0.5 w-4 h-4 rounded border-slate-300 dark:border-zinc-700 text-blue-600 focus:ring-2 focus:ring-blue-500/20 accent-blue-600 cursor-pointer shrink-0"
              />
              <span>
                {{ currentLang === 'km' ? 'ខ្ញុំយល់ព្រមតាម' : 'I agree to the' }}
                <Link href="/terms" class="text-blue-600 hover:text-blue-700 dark:text-sky-400 font-medium underline underline-offset-2">{{ currentLang === 'km' ? 'លក្ខខណ្ឌប្រើប្រាស់' : 'Terms of Service' }}</Link>
                &
                <Link href="/privacy" class="text-blue-600 hover:text-blue-700 dark:text-sky-400 font-medium underline underline-offset-2">{{ currentLang === 'km' ? 'គោលការណ៍ឯកជនភាព' : 'Privacy Policy' }}</Link>
              </span>
            </label>

            <!-- Step 1 Button -->
            <button
              type="button"
              @click="nextStep"
              :disabled="!isStep1Valid"
              class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99] disabled:opacity-50 disabled:shadow-none"
            >
              <span>{{ currentLang === 'km' ? 'បន្តទៅជំហានទី ២' : 'Continue to Step 2' }}</span>
            </button>
          </div>

          <!-- STEP 2: ACADEMIC SELECTION -->
          <div v-if="step === 2" class="space-y-3.5">
            
            <!-- TEACHER ROLE -->
            <template v-if="form.role === 'teacher'">
              <!-- Department / Faculty Selection -->
              <div class="space-y-1">
                <select
                  v-model="form.major_id"
                  required
                  class="h-11 w-full px-3.5 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white text-xs sm:text-sm rounded-xl outline-none transition-all duration-150 shadow-2xs"
                >
                  <option :value="null" disabled>-- {{ currentLang === 'km' ? 'ជ្រើសរើសមហាវិទ្យាល័យ / ដេប៉ាតឺម៉ង់' : 'Select Department / Faculty' }} --</option>
                  <option v-for="m in props.majors" :key="m.id" :value="m.id">
                    {{ currentLang === 'km' && m.name_kh ? m.name_kh : m.name }}
                  </option>
                </select>
              </div>

              <!-- Auto-filled Academic Info Pill -->
              <div v-if="form.major_id" class="bg-slate-100/90 dark:bg-zinc-900/90 border border-slate-200 dark:border-zinc-800 rounded-xl p-3 space-y-1.5 text-xs transition-all shadow-2xs">
                <div class="font-bold text-slate-900 dark:text-zinc-100 text-xs flex items-center gap-1.5 pb-1 border-b border-slate-200 dark:border-zinc-800">
                  <i class="pi pi-building text-blue-500 text-xs shrink-0"></i>
                  <span>{{ currentLang === 'km' ? 'ព័ត៌មានសិក្សា (ស្វ័យប្រវត្តិ)' : 'Academic Information (Auto-filled)' }}</span>
                </div>
                <div class="space-y-0.5 pt-0.5 text-xs text-slate-600 dark:text-zinc-400">
                  <div class="flex items-center gap-1.5">
                    <span class="font-semibold text-slate-700 dark:text-zinc-300">• {{ currentLang === 'km' ? 'មហាវិទ្យាល័យ:' : 'Faculty:' }}</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ facultyName }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="font-semibold text-slate-700 dark:text-zinc-300">• {{ currentLang === 'km' ? 'ដេប៉ាតឺម៉ង់:' : 'Department:' }}</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ departmentName }}</span>
                  </div>
                </div>
              </div>
            </template>

            <!-- STUDENT ROLE -->
            <template v-else>
              <!-- Major Dropdown -->
              <div class="space-y-1">
                <select
                  v-model="form.major_id"
                  required
                  class="h-11 w-full px-3.5 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white text-xs sm:text-sm rounded-xl outline-none transition-all duration-150 shadow-2xs"
                >
                  <option :value="null" disabled>-- {{ currentLang === 'km' ? 'ជ្រើសរើសជំនាញសិក្សារបស់អ្នក *' : 'Choose Your Major *' }} --</option>
                  <option v-for="m in props.majors" :key="m.id" :value="m.id">
                    {{ currentLang === 'km' && m.name_kh ? m.name_kh : m.name }}
                  </option>
                </select>
              </div>

              <!-- Auto-filled Academic Info Pill -->
              <div class="bg-slate-100/90 dark:bg-zinc-900/90 border border-slate-200 dark:border-zinc-800 rounded-xl p-3 space-y-1.5 text-xs transition-all shadow-2xs">
                <div class="font-bold text-slate-900 dark:text-zinc-100 text-xs flex items-center gap-1.5 pb-1 border-b border-slate-200 dark:border-zinc-800">
                  <i class="pi pi-building text-blue-500 text-xs shrink-0"></i>
                  <span>{{ currentLang === 'km' ? 'ព័ត៌មានសិក្សា (ស្វ័យប្រវត្តិ)' : 'Academic Information (Auto-filled)' }}</span>
                </div>
                <div class="space-y-0.5 pt-0.5 text-xs text-slate-600 dark:text-zinc-400">
                  <div class="flex items-center gap-1.5">
                    <span class="font-semibold text-slate-700 dark:text-zinc-300">• {{ currentLang === 'km' ? 'មហាវិទ្យាល័យ:' : 'Faculty:' }}</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ facultyName }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span class="font-semibold text-slate-700 dark:text-zinc-300">• {{ currentLang === 'km' ? 'ដេប៉ាតឺម៉ង់:' : 'Department:' }}</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ departmentName }}</span>
                  </div>
                </div>
              </div>
              
              <!-- Study Type Radio Buttons (Manus Zinc Style) -->
              <div class="space-y-1">
                <label class="block text-xs font-medium text-slate-700 dark:text-zinc-400">{{ currentLang === 'km' ? 'ទម្រង់សិក្សា *' : 'Study Type *' }}</label>
                <div class="grid grid-cols-2 gap-2.5">
                  <label :class="[
                    'p-2.5 rounded-xl border cursor-pointer transition-all duration-150 flex items-center justify-center gap-2 text-xs font-semibold select-none',
                    form.study_type === 'online'
                      ? 'bg-blue-600 text-white dark:bg-zinc-800 dark:text-white border-blue-600 dark:border-zinc-700 shadow-sm shadow-blue-500/20'
                      : 'bg-white dark:bg-[#121214] border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 hover:border-slate-300 dark:hover:border-zinc-700'
                  ]">
                    <input type="radio" v-model="form.study_type" value="online" class="sr-only" />
                    <i class="pi pi-globe text-xs"></i>
                    <span>{{ currentLang === 'km' ? 'ការសិក្សាអនឡាញ' : 'Online Learning' }}</span>
                  </label>

                  <label :class="[
                    'p-2.5 rounded-xl border cursor-pointer transition-all duration-150 flex items-center justify-center gap-2 text-xs font-semibold select-none',
                    form.study_type === 'on_campus'
                      ? 'bg-blue-600 text-white dark:bg-zinc-800 dark:text-white border-blue-600 dark:border-zinc-700 shadow-sm shadow-blue-500/20'
                      : 'bg-white dark:bg-[#121214] border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 hover:border-slate-300 dark:hover:border-zinc-700'
                  ]">
                    <input type="radio" v-model="form.study_type" value="on_campus" class="sr-only" />
                    <i class="pi pi-building text-xs"></i>
                    <span>{{ currentLang === 'km' ? 'សិក្សានៅសាលា' : 'On-Campus' }}</span>
                  </label>
                </div>
              </div>
            </template>

            <!-- Step 2 Navigation Buttons -->
            <div class="grid grid-cols-2 gap-2.5 pt-1">
              <button
                type="button"
                @click="prevStep"
                class="h-11 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-900/90 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200 font-semibold rounded-xl border border-slate-200 dark:border-zinc-800 transition text-xs flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <i class="pi pi-arrow-left text-xs"></i>
                <span>{{ t('register_btn_back', 'ត្រឡប់ក្រោយ') }}</span>
              </button>

              <button
                v-if="form.role === 'student'"
                type="button"
                @click="nextStep"
                :disabled="!isStep2Valid"
                class="h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99] disabled:opacity-50 disabled:shadow-none"
              >
                <span>{{ currentLang === 'km' ? 'បន្តទៅជំហានទី ៣' : 'Continue to Step 3' }}</span>
              </button>

              <button
                v-else
                type="submit"
                :disabled="form.processing || !isStep2Valid"
                class="h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99] disabled:opacity-50 disabled:shadow-none"
              >
                <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs mr-1.5"></i>
                <span>{{ form.processing ? (currentLang === 'km' ? 'កំពុងចុះឈ្មោះ...' : 'Registering...') : (currentLang === 'km' ? 'ចុះឈ្មោះបង្កើតគណនី' : 'Complete Registration') }}</span>
              </button>
            </div>

          </div>

          <!-- STEP 3: PAYMENT VERIFICATION (STUDENT ONLY) -->
          <div v-if="step === 3 && form.role === 'student'" class="space-y-3.5">
            
            <!-- Fee Summary Box -->
            <div class="bg-slate-100/90 dark:bg-zinc-900/90 border border-slate-200 dark:border-zinc-800 rounded-xl p-3 space-y-1.5 text-xs">
              <div class="font-bold text-slate-900 dark:text-white text-xs flex items-center justify-between">
                <span>{{ currentLang === 'km' ? 'តម្លៃសិក្សា និងការចុះឈ្មោះ:' : 'Tuition & Registration Fee:' }}</span>
                <span class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">$324.00 (ABA Pay)</span>
              </div>
            </div>

            <!-- Payment Method Choice -->
            <div class="grid grid-cols-2 gap-2">
              <label :class="[
                'p-2.5 rounded-xl border cursor-pointer transition-all duration-150 flex items-center justify-center gap-2 text-xs font-semibold select-none',
                form.payment_method === 'aba'
                  ? 'bg-blue-600 text-white dark:bg-zinc-800 dark:text-white border-blue-600 dark:border-zinc-700 shadow-sm shadow-blue-500/20'
                  : 'bg-white dark:bg-[#121214] border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 hover:border-slate-300 dark:hover:border-zinc-700'
              ]">
                <input type="radio" v-model="form.payment_method" value="aba" class="sr-only" />
                <i class="pi pi-qrcode text-xs"></i>
                <span>{{ currentLang === 'km' ? 'ស្កេន ABA Mobile' : 'ABA Mobile QR' }}</span>
              </label>

              <label :class="[
                'p-2.5 rounded-xl border cursor-pointer transition-all duration-150 flex items-center justify-center gap-2 text-xs font-semibold select-none',
                form.payment_method === 'cash'
                  ? 'bg-blue-600 text-white dark:bg-zinc-800 dark:text-white border-blue-600 dark:border-zinc-700 shadow-sm shadow-blue-500/20'
                  : 'bg-white dark:bg-[#121214] border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400 hover:border-slate-300 dark:hover:border-zinc-700'
              ]">
                <input type="radio" v-model="form.payment_method" value="cash" class="sr-only" />
                <i class="pi pi-money-bill text-xs"></i>
                <span>{{ currentLang === 'km' ? 'បង់ប្រាក់ផ្ទាល់នៅសាលា' : 'Cash at Campus' }}</span>
              </label>
            </div>

            <!-- Step 3 Navigation -->
            <div class="grid grid-cols-2 gap-2.5 pt-1">
              <button
                type="button"
                @click="prevStep"
                class="h-11 bg-slate-100 hover:bg-slate-200 dark:bg-zinc-900/90 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-200 font-semibold rounded-xl border border-slate-200 dark:border-zinc-800 transition text-xs flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <i class="pi pi-arrow-left text-xs"></i>
                <span>{{ t('register_btn_back', 'ត្រឡប់ក្រោយ') }}</span>
              </button>

              <button
                type="submit"
                :disabled="form.processing"
                class="h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99] disabled:opacity-50 disabled:shadow-none"
              >
                <i v-if="form.processing" class="pi pi-spin pi-spinner text-xs mr-1.5"></i>
                <span>{{ form.processing ? t('register_btn_submitting', 'កំពុងចុះឈ្មោះ...') : t('register_btn_submit', 'ចុះឈ្មោះបង្កើតគណនី') }}</span>
              </button>
            </div>

          </div>

        </form>

        <!-- Navigation Footer: Link back to Sign in -->
        <div class="pt-3 border-t border-slate-200 dark:border-zinc-800 flex items-center justify-between text-xs">
          <span class="text-slate-500 dark:text-zinc-400">
            {{ t('register_already_have_account', 'មានគណនីរួចហើយ?') }}
          </span>
          <Link href="/login" class="text-blue-600 dark:text-white font-semibold hover:underline inline-flex items-center">
            <span>{{ t('register_back_to_login', 'ត្រឡប់ទៅទំព័រចូល') }}</span>
          </Link>
        </div>

        <!-- Footer Terms & Policy Legal Statement (Shifted up like Login Form) -->
        <p class="text-[11px] text-slate-500 dark:text-zinc-500 leading-normal text-center mt-6 w-full max-w-lg px-2 select-text">
          {{ currentLang === 'km' ? 'តាមរយៈការចុះឈ្មោះ អ្នកយល់ព្រមតាម ' : 'By registering, you agree to our ' }}
          <Link href="/terms" class="text-slate-700 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-zinc-200 underline underline-offset-2 transition-colors">
            {{ currentLang === 'km' ? 'លក្ខខណ្ឌប្រើប្រាស់' : 'Terms of Service' }}
          </Link>
          {{ currentLang === 'km' ? ' និងបានអាន ' : ' and have read our ' }}
          <Link href="/privacy" class="text-slate-700 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-zinc-200 underline underline-offset-2 transition-colors">
            {{ currentLang === 'km' ? 'គោលការណ៍ឯកជនភាព' : 'Privacy Policy' }}</Link>។
        </p>

        </div>
      </div>

      <!-- Loading / Registration Processing Overlay (Like Screenshot) -->
      <div v-else class="w-full max-w-sm flex flex-col items-center justify-center text-center animate-fade-in py-12">
        <div class="w-12 h-12 rounded-full border-2 border-zinc-300 dark:border-zinc-800 border-t-blue-600 dark:border-t-white animate-spin mb-4"></div>
        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-wide mb-1.5">
          {{ registerLoadingTitle || (currentLang === 'km' ? 'កំពុងបង្កើតគណនី...' : 'Creating your account...') }}
        </h3>
        <p class="text-xs text-slate-600 dark:text-zinc-400 max-w-xs leading-relaxed">
          {{ registerLoadingSubtitle || (currentLang === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងដំណើរការរៀបចំព័ត៌មាន...' : 'Please wait a moment while setting up your profile...') }}
        </p>
      </div>

    </main>

    <!-- Compact Success Alert Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm select-none">
        <div class="max-w-xs w-full bg-white dark:bg-[#121214] rounded-2xl p-6 shadow-2xl border border-zinc-200 dark:border-zinc-800 text-center flex flex-col items-center space-y-3">
          <div class="w-12 h-12 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <i class="pi pi-check text-xl font-bold"></i>
          </div>
          <div class="space-y-1">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white">
              {{ successTitle || t('register_modal_success_title', 'ចុះឈ្មោះជោគជ័យ!') }}
            </h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
              {{ successMessage || t('register_modal_success_msg', 'គណនីរបស់អ្នកត្រូវបានបង្កើតដោយជោគជ័យ!') }}
            </p>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Compact Error Alert Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="showErrorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm select-none">
        <div class="max-w-xs w-full bg-white dark:bg-[#121214] rounded-2xl p-6 shadow-2xl border border-zinc-200 dark:border-zinc-800 text-center flex flex-col items-center space-y-3">
          <div class="w-12 h-12 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center">
            <i class="pi pi-exclamation-triangle text-xl font-bold"></i>
          </div>
          <div class="space-y-1">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white">
              {{ errorTitle || (currentLang === 'km' ? 'ព័ត៌មានមិនត្រឹមត្រូវ' : 'Invalid Information') }}
            </h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
              {{ errorMessage || (currentLang === 'km' ? 'សូមពិនិត្យមើលព័ត៌មាននៃការចុះឈ្មោះរបស់អ្នកឡើងវិញ!' : 'Please check your registration information and try again!') }}
            </p>
          </div>
          <button
            type="button"
            @click="showErrorModal = false"
            class="w-full py-2.5 rounded-xl bg-zinc-900 dark:bg-zinc-800 text-white text-xs font-semibold hover:bg-zinc-800 transition-colors cursor-pointer shadow-xs"
          >
            {{ currentLang === 'km' ? 'បិទ' : 'Close' }}
          </button>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.checkmark-path {
  stroke-dasharray: 50;
  stroke-dashoffset: 50;
  animation: checkmarkDraw 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

@keyframes checkmarkDraw {
  0% {
    stroke-dashoffset: 50;
    opacity: 0;
  }
  100% {
    stroke-dashoffset: 0;
    opacity: 1;
  }
}

@keyframes floatSlow {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-22px) rotate(4deg); }
}

@keyframes floatReverse {
  0%, 100% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(22px) rotate(-4deg); }
}

@keyframes aurora {
  0% { transform: translate(0%, 0%) rotate(0deg); }
  50% { transform: translate(8%, 8%) rotate(180deg); }
  100% { transform: translate(0%, 0%) rotate(360deg); }
}

.animate-float-slow {
  animation: floatSlow 8s ease-in-out infinite;
}
.animate-float-reverse {
  animation: floatReverse 11s ease-in-out infinite;
}
.animate-aurora {
  animation: aurora 25s linear infinite;
}
.font-khmer {
  font-family: 'Kantumruy Pro', 'Kantumruy', 'Siemreap', 'Noto Sans Khmer', system-ui, sans-serif;
}
</style>
