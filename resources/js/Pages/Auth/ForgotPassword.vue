<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useForm, usePage, Link, router } from '@inertiajs/vue3'
import { i18n, type LanguageCode } from '../../Services/i18n'
import AuthAnimatedBackground from '../../Components/AuthAnimatedBackground.vue'
import NetworkStatusPill from '../../Components/NetworkStatusPill.vue'

const props = defineProps<{
  status?: string
}>()

const page = usePage()
const logoUrl = '/images/logo.png'

const step = ref<1 | 2>(1)
const isOtpVerified = ref(false)
const isVerifyingOtp = ref(false)
const otpError = ref('')

const isDark = ref(true)
const isLangOpen = ref(false)

const showSuccessModal = ref(false)
const showErrorModal = ref(false)
const successTitle = ref('')
const successMessage = ref('')
const errorTitle = ref('')
const errorMessage = ref('')

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

const flashData = computed(() => {
  const p = page.props as any
  return {
    ...p,
    ...(p.flash || {}),
  }
})

onMounted(() => {
  initTheme()
  document.addEventListener('click', handleClickOutside)
  if (props.status || (page.props as any).status || (page.props as any).flash?.status) {
    showSuccessModal.value = true
    successTitle.value = t('forgot_modal_success_title', 'ផ្ញើកូដជោគជ័យ!')
    successMessage.value = props.status || (page.props as any).status || (page.props as any).flash?.status
    setTimeout(() => {
      showSuccessModal.value = false
    }, 4000)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Step 1 Form
const selectedChannel = ref<'telegram' | 'email'>('telegram')

const requestForm = useForm({
  email: '',
  channel: 'telegram',
})

// Step 2 / 3 Form
const resetForm = useForm({
  email: '',
  code: '',
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const showConfirmPassword = ref(false)
const codeDigits = ref(['', '', '', '', '', ''])
const digitInputs = ref<HTMLInputElement[]>([])

const clearEmail = () => {
  requestForm.email = ''
}

const resetToStepOne = () => {
  step.value = 1
  isOtpVerified.value = false
  isVerifyingOtp.value = false
  otpError.value = ''
  codeDigits.value = ['', '', '', '', '', '']
  resetForm.reset()
}

const handleCodeInput = (index: number, e: Event) => {
  const input = e.target as HTMLInputElement
  const val = input.value
  otpError.value = ''

  if (val.length > 1) {
    const chars = val.replace(/\D/g, '').split('').slice(0, 6)
    chars.forEach((char, i) => {
      if (i < 6) codeDigits.value[i] = char
    })
    resetForm.code = codeDigits.value.join('')
    if (chars.length === 6) {
      onVerifyOtp()
    } else if (chars.length > 0 && digitInputs.value[Math.min(chars.length - 1, 5)]) {
      digitInputs.value[Math.min(chars.length - 1, 5)].focus()
    }
    return
  }

  codeDigits.value[index] = val
  resetForm.code = codeDigits.value.join('')

  if (val && index < 5 && digitInputs.value[index + 1]) {
    digitInputs.value[index + 1].focus()
  } else if (val && index === 5 && resetForm.code.length === 6) {
    onVerifyOtp()
  }
}

const handleCodeKeyDown = (index: number, e: KeyboardEvent) => {
  if (e.key === 'Backspace' && !codeDigits.value[index] && index > 0 && digitInputs.value[index - 1]) {
    digitInputs.value[index - 1].focus()
  }
}

const handleCodePaste = (e: ClipboardEvent) => {
  e.preventDefault()
  const pasted = e.clipboardData?.getData('text') || ''
  const digits = pasted.replace(/\D/g, '').split('').slice(0, 6)
  digits.forEach((digit, i) => {
    codeDigits.value[i] = digit
  })
  resetForm.code = codeDigits.value.join('')
  if (digits.length === 6) {
    onVerifyOtp()
  } else if (digits.length > 0 && digitInputs.value[Math.min(digits.length - 1, 5)]) {
    digitInputs.value[Math.min(digits.length - 1, 5)].focus()
  }
}

const onRequestCode = (overrideChannel?: 'telegram' | 'email') => {
  if (overrideChannel) {
    selectedChannel.value = overrideChannel
    requestForm.channel = overrideChannel
  } else {
    requestForm.channel = selectedChannel.value
  }

  showErrorModal.value = false
  showSuccessModal.value = false
  requestForm.post('/forgot-password', {
    preserveScroll: true,
    onSuccess: (pageRes: any) => {
      resetForm.email = requestForm.email
      step.value = 2
      isOtpVerified.value = false
      showSuccessModal.value = true
      const flash = (pageRes?.props as any)?.flash || (pageRes?.props as any) || {}
      if (flash.channel) {
        selectedChannel.value = flash.channel
      }
      successTitle.value = t('forgot_modal_success_title', 'ផ្ញើកូដជោគជ័យ!')
      successMessage.value = flash.status || flash.message || props.status || (
        selectedChannel.value === 'email'
          ? (currentLang.value === 'km' ? 'កូដផ្ទៀងផ្ទាត់ 6 ខ្ទង់ ត្រូវបានផ្ញើទៅកាន់ Email របស់អ្នករួចរាល់ហើយ!' : 'A 6-digit OTP code has been sent to your email!')
          : (currentLang.value === 'km' ? 'កូដផ្ទៀងផ្ទាត់ 6 ខ្ទង់ ត្រូវបានផ្ញើទៅកាន់ Telegram Bot របស់អ្នករួចរាល់ហើយ!' : 'A 6-digit OTP code has been sent to your Telegram Bot!')
      )

      setTimeout(() => {
        showSuccessModal.value = false
      }, 4500)

      nextTick(() => {
        if (digitInputs.value[0]) {
          digitInputs.value[0].focus()
        }
      })
    },
    onError: (errors) => {
      showErrorModal.value = true
      errorTitle.value = t('forgot_modal_error_title', 'ព័ត៌មានមិនត្រឹមត្រូវ')
      errorMessage.value = errors.email || errors.code || errors.password || t('forgot_modal_error_msg', 'សូមពិនិត្យមើលអ៊ីមែល ឬលេខទូរស័ព្ទរបស់អ្នកឡើងវិញ!')
      setTimeout(() => {
        showErrorModal.value = false
      }, 4500)
    },
  })
}

const onVerifyOtp = () => {
  const code = resetForm.code || codeDigits.value.join('')
  if (code.length !== 6) {
    otpError.value = t('forgot_otp_invalid', 'សូមបញ្ចូលលេខកូដ OTP ឱ្យគ្រប់ ៦ ខ្ទង់')
    return
  }

  isVerifyingOtp.value = true
  otpError.value = ''

  router.post('/verify-reset-otp', {
    email: resetForm.email,
    code: code,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isVerifyingOtp.value = false
      isOtpVerified.value = true
      showSuccessModal.value = true
      successTitle.value = t('forgot_modal_success_title', 'ផ្ទៀងផ្ទាត់ជោគជ័យ!')
      successMessage.value = t('forgot_otp_verified_success', 'លេខកូដ OTP ត្រូវបានផ្ទៀងផ្ទាត់ជោគជ័យ!')
      setTimeout(() => {
        showSuccessModal.value = false
      }, 3000)
    },
    onError: (errors) => {
      isVerifyingOtp.value = false
      isOtpVerified.value = false
      otpError.value = errors.code || errors.email || t('forgot_modal_error_msg', 'កូដផ្ទៀងផ្ទាត់មិនត្រឹមត្រូវទេ! សូមពិនិត្យកូដ 6 ខ្ទង់ម្តងទៀត។')
    },
  })
}

const onResetPassword = () => {
  showErrorModal.value = false
  showSuccessModal.value = false
  resetForm.post('/reset-password', {
    preserveScroll: true,
    onFinish: () => resetForm.reset('password', 'password_confirmation'),
    onError: (errors) => {
      showErrorModal.value = true
      errorTitle.value = t('forgot_modal_error_title', 'ព័ត៌មានមិនត្រឹមត្រូវ')
      errorMessage.value = errors.code || errors.email || errors.password || t('forgot_modal_error_msg', 'កូដផ្ទៀងផ្ទាត់មិនត្រឹមត្រូវទេ! សូមពិនិត្យកូដ 6 ខ្ទង់ម្តងទៀត។')
      setTimeout(() => {
        showErrorModal.value = false
      }, 4500)
    },
  })
}
</script>

<template>
  <div class="min-h-screen w-full bg-[#f8fafc] dark:bg-[#000000] text-zinc-900 dark:text-[#ededed] flex flex-col justify-between relative font-sans overflow-x-hidden select-none transition-colors duration-300">
    
    <!-- Manus AI Style Interactive Dot-Matrix Canvas Background -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0 select-none">
      <AuthAnimatedBackground />
    </div>

    <!-- Top Navigation: Left Branding & Right Language/Theme Switchers -->
    <header class="w-full relative z-20 flex items-center justify-between px-6 py-5 sm:px-8">
      <!-- Logo Mark -->
      <div class="flex items-center gap-2.5 cursor-pointer transition-opacity hover:opacity-80 group" @click="router.visit('/')">
        <img :src="logoUrl" alt="E-LMS" class="w-7 h-7 rounded-full object-contain shadow-xs transition-transform duration-200 group-hover:scale-105" />
        <span class="font-extrabold tracking-tight text-lg font-sans bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-white dark:to-zinc-200 bg-clip-text text-transparent">E LMS</span>
      </div>

      <!-- Right Controls: Language & Theme Switchers -->
      <div class="flex items-center gap-2.5">
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
                    ? 'bg-zinc-100 dark:bg-zinc-800 text-blue-600 dark:text-sky-400 font-semibold'
                    : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'
                ]"
              >
                <span class="flex items-center gap-2">
                  <img :src="lang.flagUrl" :alt="lang.name" class="w-3.5 h-3.5 rounded-full object-cover shrink-0" />
                  <span>{{ lang.name }}</span>
                </span>
                <i v-if="currentLang === lang.code" class="pi pi-check text-[10px] text-blue-600 dark:text-sky-400"></i>
              </button>
            </div>
          </Transition>
        </div>

        <!-- Theme Switcher Pill -->
        <button
          type="button"
          @click="toggleTheme"
          class="h-8 w-8 rounded-full bg-zinc-200/70 dark:bg-[#18181b] hover:bg-zinc-300 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 transition-all duration-150 border border-zinc-300/80 dark:border-zinc-800/80 flex items-center justify-center cursor-pointer select-none"
          :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        >
          <i :class="['pi text-xs', isDark ? 'pi-sun text-amber-400' : 'pi-moon text-indigo-400']"></i>
        </button>
      </div>
    </header>

    <!-- Centered Clean Form Stage (Manus Minimalist Style) -->
    <main class="w-full max-w-[390px] mx-auto px-4 py-4 z-10 my-auto flex flex-col items-center">
      
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
        <h1 class="text-2xl sm:text-[26px] font-black tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-white dark:via-zinc-100 dark:to-zinc-300 bg-clip-text text-transparent text-center transition-colors">
          {{ t('forgot_title', 'ភ្លេចពាក្យសម្ងាត់') }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 text-center mt-1.5 mb-6 transition-colors">
          {{ step === 1 ? (currentLang === 'km' ? 'បញ្ចូលគណនីរបស់អ្នកដើម្បីទទួលលេខកូដ OTP' : 'Enter your account to receive OTP code') : !isOtpVerified ? (selectedChannel === 'email' ? (currentLang === 'km' ? 'ផ្ទៀងផ្ទាត់កូដ OTP ពី Email' : 'Verify OTP code from Email') : (currentLang === 'km' ? 'ផ្ទៀងផ្ទាត់កូដ OTP ពី Telegram' : 'Verify OTP code from Telegram')) : (currentLang === 'km' ? 'កំណត់ពាក្យសម្ងាត់ថ្មីសម្រាប់គណនីរបស់អ្នក' : 'Set a new password for your account') }}
        </p>
      </div>

      <!-- Main Form Container -->
      <div class="w-full space-y-3.5 animate-fade-in">
        
        <!-- STEP 1 FORM: REQUEST OTP -->
        <form v-if="step === 1" @submit.prevent="onRequestCode()" class="space-y-3">
          
          <!-- Input Account -->
          <div class="space-y-1.5">
            <div class="relative">
              <input
                v-model="requestForm.email"
                type="text"
                required
                autocomplete="username"
                :placeholder="t('forgot_input_email_placeholder', 'ឈ្មោះគណនី, Email ឬ លេខទូរស័ព្ទ')"
                class="w-full h-11 px-3.5 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none shadow-2xs transition-colors"
              />
              <button
                v-if="requestForm.email"
                type="button"
                @click="clearEmail"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors p-1 cursor-pointer"
                title="Clear"
              >
                <i class="pi pi-times-circle text-xs"></i>
              </button>
            </div>
          </div>

          <!-- Channel Selection (Clean Icon + Label Only) -->
          <div class="space-y-1.5 pt-0.5 text-left">
            <label class="text-[11px] font-semibold text-slate-600 dark:text-zinc-400 block px-0.5">
              {{ currentLang === 'km' ? 'ជ្រើសរើសវិធីទទួលលេខកូដ OTP' : 'Choose OTP Delivery Channel' }}
            </label>

            <div class="grid grid-cols-2 gap-2.5">
              <!-- Telegram Option -->
              <button
                type="button"
                @click="selectedChannel = 'telegram'"
                :class="[
                  'py-3.5 px-3 rounded-2xl border text-center transition-all duration-200 cursor-pointer flex flex-col items-center justify-center gap-2 select-none group',
                  selectedChannel === 'telegram'
                    ? 'border-sky-500 bg-sky-500/10 dark:bg-sky-500/15 ring-2 ring-sky-500/30 shadow-md shadow-sky-500/10'
                    : 'border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#121214] hover:border-slate-300 dark:hover:border-zinc-700'
                ]"
              >
                <div
                  :class="[
                    'w-11 h-11 rounded-2xl flex items-center justify-center transition-all',
                    selectedChannel === 'telegram'
                      ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/30 scale-105'
                      : 'bg-slate-100 dark:bg-zinc-800/90 text-sky-500 dark:text-sky-400 group-hover:scale-105'
                  ]"
                >
                  <i class="pi pi-send text-base"></i>
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                  Telegram
                </span>
              </button>

              <!-- Email Option -->
              <button
                type="button"
                @click="selectedChannel = 'email'"
                :class="[
                  'py-3.5 px-3 rounded-2xl border text-center transition-all duration-200 cursor-pointer flex flex-col items-center justify-center gap-2 select-none group',
                  selectedChannel === 'email'
                    ? 'border-blue-600 bg-blue-500/10 dark:bg-blue-500/15 ring-2 ring-blue-500/30 shadow-md shadow-blue-500/10'
                    : 'border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#121214] hover:border-slate-300 dark:hover:border-zinc-700'
                ]"
              >
                <div
                  :class="[
                    'w-11 h-11 rounded-2xl flex items-center justify-center transition-all',
                    selectedChannel === 'email'
                      ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/30 scale-105'
                      : 'bg-slate-100 dark:bg-zinc-800/90 text-blue-600 dark:text-blue-400 group-hover:scale-105'
                  ]"
                >
                  <i class="pi pi-envelope text-base"></i>
                </div>
                <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                  Email
                </span>
              </button>
            </div>
          </div>

          <!-- Submit Button Step 1 -->
          <button
            type="submit"
            :disabled="requestForm.processing || !requestForm.email"
            class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99] disabled:opacity-50 disabled:shadow-none"
          >
            <i v-if="requestForm.processing" class="pi pi-spin pi-spinner text-sm mr-2"></i>
            <span>{{ requestForm.processing ? (currentLang === 'km' ? 'កំពុងផ្ញើ...' : 'Sending...') : (currentLang === 'km' ? 'ផ្ញើលេខកូដ OTP' : 'Send OTP Code') }}</span>
          </button>

        </form>

        <!-- STEP 2 & 3: OTP VERIFICATION & SET PASSWORD -->
        <div v-else class="space-y-3.5">
          
          <!-- Account Display Pill with Edit Button -->
          <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#121214] shadow-2xs">
            <div class="flex items-center gap-2 truncate">
              <i class="pi pi-user text-xs text-slate-400"></i>
              <span class="text-xs sm:text-sm font-medium text-slate-900 dark:text-zinc-200 truncate">{{ resetForm.email }}</span>
            </div>
            <button
              type="button"
              @click="resetToStepOne"
              class="text-xs font-semibold text-blue-600 dark:text-sky-400 hover:underline cursor-pointer ml-2 shrink-0"
            >
              {{ currentLang === 'km' ? 'កែប្រែ' : 'Change' }}
            </button>
          </div>

          <!-- STAGE 1: OTP VERIFICATION -->
          <div v-if="!isOtpVerified" class="space-y-3">
            
            <!-- Delivery Banner: Email or Telegram -->
            <div
              v-if="selectedChannel === 'email'"
              class="p-3 bg-blue-500/10 border border-blue-500/20 rounded-xl flex items-center justify-between gap-3 shadow-2xs"
            >
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                  <i class="pi pi-envelope text-xs"></i>
                </div>
                <div class="leading-tight text-left">
                  <p class="text-xs font-bold text-slate-800 dark:text-slate-100">
                    {{ currentLang === 'km' ? 'បានផ្ញើកូដទៅកាន់ Email' : 'OTP Sent to Email' }}
                  </p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ currentLang === 'km' ? 'សូមពិនិត្យមើល Inbox ឬ Junk/Spam' : 'Please check your Inbox or Spam folder' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Telegram Banner -->
            <div
              v-else
              class="p-3 bg-sky-500/10 border border-sky-500/20 rounded-xl flex items-center justify-between gap-3 shadow-2xs"
            >
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-sky-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                  <i class="pi pi-send text-xs"></i>
                </div>
                <div class="leading-tight text-left">
                  <p class="text-xs font-bold text-slate-800 dark:text-slate-100">
                    {{ currentLang === 'km' ? 'បានផ្ញើកូដ OTP ទៅកាន់ Telegram' : 'OTP Sent to Telegram' }}
                  </p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ currentLang === 'km' ? 'សូមពិនិត្យមើលសារក្នុង Telegram Bot' : 'Please check your Telegram Bot' }}
                  </p>
                </div>
              </div>

              <a
                :href="'https://t.me/' + (flashData.telegram_bot_name || 'spi_elms_auth_bot')"
                target="_blank"
                rel="noopener noreferrer"
                class="px-3 py-1.5 bg-sky-500 hover:bg-sky-600 text-white text-xs font-semibold rounded-lg transition shadow-xs whitespace-nowrap inline-flex items-center gap-1 cursor-pointer shrink-0"
              >
                <i class="pi pi-telegram text-xs"></i>
                <span>{{ currentLang === 'km' ? 'បើក Telegram' : 'Telegram' }}</span>
              </a>
            </div>

            <!-- 6-Digit OTP Inputs -->
            <div class="space-y-1.5">
              <div class="grid grid-cols-6 gap-2" @paste="handleCodePaste">
                <input
                  v-for="(digit, idx) in 6"
                  :key="idx"
                  ref="digitInputs"
                  v-model="codeDigits[idx]"
                  type="text"
                  maxlength="6"
                  inputmode="numeric"
                  @paste="handleCodePaste"
                  @input="handleCodeInput(idx, $event)"
                  @keydown="handleCodeKeyDown(idx, $event)"
                  class="w-full h-11 text-center text-lg font-bold font-mono rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#121214] text-slate-900 dark:text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15 outline-none shadow-2xs"
                />
              </div>
            </div>

            <!-- Verify OTP Button -->
            <button
              type="button"
              @click="onVerifyOtp"
              :disabled="isVerifyingOtp || resetForm.code.length !== 6"
              class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99] disabled:opacity-50 disabled:shadow-none"
            >
              <i v-if="isVerifyingOtp" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ isVerifyingOtp ? (currentLang === 'km' ? 'កំពុងផ្ទៀងផ្ទាត់...' : 'Verifying...') : (currentLang === 'km' ? 'ផ្ទៀងផ្ទាត់កូដ OTP' : 'Verify OTP Code') }}</span>
            </button>

            <!-- Channel Switch & Resend Helper -->
            <div class="flex items-center justify-between text-xs pt-1 px-0.5">
              <button
                type="button"
                @click="onRequestCode(selectedChannel === 'telegram' ? 'email' : 'telegram')"
                :disabled="requestForm.processing"
                class="text-slate-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-sky-400 font-medium transition cursor-pointer inline-flex items-center gap-1.5"
              >
                <i :class="selectedChannel === 'telegram' ? 'pi pi-envelope text-[11px]' : 'pi pi-send text-[11px]'"></i>
                <span>
                  {{ selectedChannel === 'telegram'
                      ? (currentLang === 'km' ? 'ផ្ញើតាម Email ជំនួសវិញ' : 'Send via Email instead')
                      : (currentLang === 'km' ? 'ផ្ញើតាម Telegram ជំនួសវិញ' : 'Send via Telegram instead') }}
                </span>
              </button>

              <button
                type="button"
                @click="onRequestCode(selectedChannel)"
                :disabled="requestForm.processing"
                class="text-blue-600 dark:text-sky-400 font-semibold hover:underline cursor-pointer"
              >
                {{ requestForm.processing ? (currentLang === 'km' ? 'កំពុងផ្ញើ...' : 'Sending...') : (currentLang === 'km' ? 'ផ្ញើកូដឡើងវិញ' : 'Resend Code') }}
              </button>
            </div>

          </div>

          <!-- STAGE 2: SET NEW PASSWORD -->
          <form v-else @submit.prevent="onResetPassword" class="space-y-3">
            
            <!-- OTP Verified Badge -->
            <div class="p-2.5 bg-emerald-500/10 border border-emerald-500/30 rounded-xl flex items-center gap-2 text-emerald-700 dark:text-emerald-300 text-xs font-semibold shadow-2xs">
              <i class="pi pi-check-circle text-sm text-emerald-600"></i>
              <span>{{ currentLang === 'km' ? 'កូដ OTP ផ្ទៀងផ្ទាត់ត្រឹមត្រូវ!' : 'OTP Verified Successfully!' }}</span>
            </div>

            <!-- New Password Input -->
            <div class="relative">
              <input
                v-model="resetForm.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="new-password"
                placeholder="••••••••••••"
                class="w-full h-11 px-3.5 pr-10 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none shadow-2xs"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors p-1 cursor-pointer"
                title="Show password"
              >
                <i :class="['pi text-xs', showPassword ? 'pi-eye-slash text-blue-600 dark:text-sky-400' : 'pi-eye']"></i>
              </button>
            </div>

            <!-- Confirm Password Input -->
            <div class="relative">
              <input
                v-model="resetForm.password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                autocomplete="new-password"
                placeholder="••••••••••••"
                class="w-full h-11 px-3.5 pr-10 bg-white dark:bg-[#121214] border border-slate-200 dark:border-zinc-800 hover:border-slate-300 dark:hover:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/15 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none shadow-2xs"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors p-1 cursor-pointer"
                title="Show password"
              >
                <i :class="['pi text-xs', showConfirmPassword ? 'pi-eye-slash text-blue-600 dark:text-sky-400' : 'pi-eye']"></i>
              </button>
            </div>

            <!-- Submit Button (Save & Login) -->
            <button
              type="submit"
              :disabled="resetForm.processing || resetForm.password.length < 8 || resetForm.password !== resetForm.password_confirmation"
              class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99] disabled:opacity-50 disabled:shadow-none"
            >
              <i v-if="resetForm.processing" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ resetForm.processing ? (currentLang === 'km' ? 'កំពុងរក្សាទុក...' : 'Saving...') : (currentLang === 'km' ? 'រក្សាទុកពាក្យសម្ងាត់ថ្មី' : 'Save New Password & Login') }}</span>
            </button>

          </form>

        </div>

        <!-- Footer Navigation Links -->
        <div class="pt-2 flex items-center justify-between text-xs">
          <Link href="/login" class="text-slate-600 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-white font-medium inline-flex items-center gap-1 transition-colors">
            <i class="pi pi-arrow-left text-[10px]"></i>
            <span>{{ currentLang === 'km' ? 'ត្រឡប់ទៅទំព័រចូល' : 'Back to Sign In' }}</span>
          </Link>
          <Link href="/register" class="text-blue-600 dark:text-white font-semibold hover:underline">
            {{ currentLang === 'km' ? 'បង្កើតគណនី' : 'Register Account' }}
          </Link>
        </div>

      </div>

      <!-- Footer Terms & Policy Legal Statement -->
      <p class="text-[11px] text-slate-500 dark:text-zinc-500 leading-normal text-center mt-6 w-full max-w-lg px-2 select-text">
        {{ currentLang === 'km' ? 'តាមរយៈការបន្ត អ្នកយល់ព្រមតាម ' : 'By continuing, you agree to our ' }}
        <Link href="/terms" class="text-slate-700 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-zinc-200 underline underline-offset-2 transition-colors">
          {{ currentLang === 'km' ? 'លក្ខខណ្ឌប្រើប្រាស់' : 'Terms of Service' }}
        </Link>
        {{ currentLang === 'km' ? ' និងបានអាន ' : ' and have read our ' }}
        <Link href="/privacy" class="text-slate-700 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-zinc-200 underline underline-offset-2 transition-colors">
          {{ currentLang === 'km' ? 'គោលការណ៍ឯកជនភាព' : 'Privacy Policy' }}</Link>។
      </p>

    </main>

    <!-- Premium Success Alert Modal (Matches Login Design Style) -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="showSuccessModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 dark:bg-black/75 backdrop-blur-md select-none"
        @click.self="showSuccessModal = false"
      >
        <div class="max-w-[380px] w-full bg-white/95 dark:bg-[#141417]/95 backdrop-blur-xl rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-200/80 dark:border-zinc-800/80 text-center flex flex-col items-center space-y-4 relative overflow-hidden">
          
          <!-- Top Ambient Glow Aura -->
          <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-36 h-36 bg-emerald-500/20 dark:bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>

          <!-- Top-Right Close Button -->
          <button
            type="button"
            @click="showSuccessModal = false"
            class="absolute right-4 top-4 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors p-1 cursor-pointer rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800/50"
            title="Close"
          >
            <i class="pi pi-times text-xs"></i>
          </button>

          <!-- Status Icon -->
          <div class="relative w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500/15 via-emerald-500/10 to-teal-500/5 dark:from-emerald-500/25 dark:to-teal-500/10 border border-emerald-500/25 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-lg shadow-emerald-500/10 ring-4 ring-emerald-500/10 dark:ring-emerald-500/15">
            <i class="pi pi-check text-2xl font-black"></i>
          </div>

          <!-- Text Block -->
          <div class="space-y-1.5 px-2">
            <h3 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white">
              {{ successTitle || (currentLang === 'km' ? 'ផ្ញើកូដជោគជ័យ!' : 'Code Sent Successfully!') }}
            </h3>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 leading-relaxed font-medium">
              {{ successMessage }}
            </p>
          </div>

          <!-- Action Button (Login style: Blue in light, crisp white in dark) -->
          <button
            type="button"
            @click="showSuccessModal = false"
            class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-white dark:hover:bg-zinc-100 dark:text-zinc-950 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all duration-150 active:scale-[0.99] cursor-pointer shadow-md shadow-blue-500/20 dark:shadow-none"
          >
            <span>{{ currentLang === 'km' ? 'យល់ព្រម' : 'Continue' }}</span>
            <i class="pi pi-arrow-right text-xs"></i>
          </button>
        </div>
      </div>
    </Transition>

    <!-- Premium Error Alert Modal (Matches Login Design Style) -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="showErrorModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 dark:bg-black/75 backdrop-blur-md select-none"
        @click.self="showErrorModal = false"
      >
        <div class="max-w-[380px] w-full bg-white/95 dark:bg-[#141417]/95 backdrop-blur-xl rounded-3xl p-6 sm:p-7 shadow-2xl border border-slate-200/80 dark:border-zinc-800/80 text-center flex flex-col items-center space-y-4 relative overflow-hidden">
          
          <!-- Top Ambient Glow Aura -->
          <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-36 h-36 bg-rose-500/20 dark:bg-rose-500/15 rounded-full blur-2xl pointer-events-none"></div>

          <!-- Top-Right Close Button -->
          <button
            type="button"
            @click="showErrorModal = false"
            class="absolute right-4 top-4 text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors p-1 cursor-pointer rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800/50"
            title="Close"
          >
            <i class="pi pi-times text-xs"></i>
          </button>

          <!-- Status Icon -->
          <div class="relative w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-500/15 via-rose-500/10 to-pink-500/5 dark:from-rose-500/25 dark:to-pink-500/10 border border-rose-500/25 dark:border-rose-500/30 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-lg shadow-rose-500/10 ring-4 ring-rose-500/10 dark:ring-rose-500/15">
            <i class="pi pi-exclamation-triangle text-2xl font-bold"></i>
          </div>

          <!-- Text Block -->
          <div class="space-y-1.5 px-2">
            <h3 class="text-base sm:text-lg font-black tracking-tight text-slate-900 dark:text-white">
              {{ errorTitle || (currentLang === 'km' ? 'ព័ត៌មានមិនត្រឹមត្រូវ' : 'Error') }}
            </h3>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 leading-relaxed font-medium">
              {{ errorMessage }}
            </p>
          </div>

          <!-- Action Button -->
          <button
            type="button"
            @click="showErrorModal = false"
            class="w-full h-11 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-slate-800 dark:text-zinc-100 font-bold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 active:scale-[0.99] cursor-pointer"
          >
            {{ currentLang === 'km' ? 'យល់ព្រម' : 'Dismiss' }}
          </button>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* Progressive Disclosure Animations */
.slide-fade-enter-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-fade-leave-active {
  transition: all 0.25s cubic-bezier(0.7, 0, 0.84, 0);
}
.slide-fade-enter-from {
  transform: translateY(12px);
  opacity: 0;
}
.slide-fade-leave-to {
  transform: translateY(-8px);
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

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

@keyframes spinSlow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes spinReverse {
  from { transform: rotate(360deg); }
  to { transform: rotate(0deg); }
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
.animate-spin-slow {
  animation: spinSlow 30s linear infinite;
}
.animate-spin-reverse {
  animation: spinReverse 35s linear infinite;
}
.animate-aurora {
  animation: aurora 25s linear infinite;
}
.animate-pulse-slow {
  animation: pulse 5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
