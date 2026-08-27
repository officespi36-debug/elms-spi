<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useForm, Link, router, usePage } from '@inertiajs/vue3'
import { i18n, type LanguageCode } from '../../Services/i18n'
import AuthAnimatedBackground from '../../Components/AuthAnimatedBackground.vue'
import NetworkStatusPill from '../../Components/NetworkStatusPill.vue'

const logoUrl = '/images/logo.png'

const props = defineProps<{
  status?: string
}>()

const page = usePage()

// Complete Auth Decision Flow: 'identifier' | 'enter_password'
const step = ref<'identifier' | 'enter_password'>('identifier')

const form = useForm({
  email: '',
  password: '',
  role: 'student' as 'student' | 'teacher' | 'admin',
  remember: true,
  turnstile_token: '',
})

// Matched User Metadata from Database Lookup
const matchedUser = ref<{
  name?: string
  role?: 'student' | 'teacher' | 'admin'
  email?: string
} | null>(null)

// Cloudflare Turnstile CAPTCHA State
const turnstileWidget = ref<HTMLElement | null>(null)
let widgetId: string | number | null = null
const isTurnstileLoading = ref(true)

const isLocalHost = computed(() => {
  if (typeof window === 'undefined') return false
  const host = window.location.hostname
  return host === 'localhost' || host === '127.0.0.1' || host.endsWith('.test') || host.endsWith('.local')
})

const initTurnstile = () => {
  if (typeof window === 'undefined') return

  if (isLocalHost.value) {
    form.turnstile_token = '1x_local_verified_token'
    form.clearErrors('turnstile_token')
    isTurnstileLoading.value = false
    return
  }

  const renderWidget = () => {
    if (typeof window === 'undefined' || !(window as any).turnstile || !turnstileWidget.value) return

    try {
      if (widgetId !== null) {
        try {
          (window as any).turnstile.remove(widgetId)
        } catch (_) {}
        widgetId = null
      }

      turnstileWidget.value.innerHTML = ''
      widgetId = (window as any).turnstile.render(turnstileWidget.value, {
        sitekey: '0x4AAAAAAEXbfl90rlcdniVI',
        theme: isDark.value ? 'dark' : 'light',
        size: 'flexible',
        callback: (token: string) => {
          form.turnstile_token = token
          form.clearErrors('turnstile_token')
          isTurnstileLoading.value = false
        },
        'expired-callback': () => {
          form.turnstile_token = ''
          if (typeof window !== 'undefined' && (window as any).turnstile && widgetId !== null) {
            try {
              (window as any).turnstile.reset(widgetId)
            } catch (_) {}
          }
        },
        'error-callback': (code: any) => {
          console.warn('Turnstile Error Code:', code)
          form.turnstile_token = ''
          isTurnstileLoading.value = false
        },
      })
      isTurnstileLoading.value = false
    } catch (e) {
      console.warn('Turnstile init exception:', e)
      isTurnstileLoading.value = false
    }
  }

  ;(window as any).onloadTurnstileCallback = () => {
    setTimeout(renderWidget, 50)
  }

  if (typeof window !== 'undefined' && (window as any).turnstile) {
    setTimeout(renderWidget, 50)
  } else {
    const scriptId = 'cf-turnstile-script'
    if (!document.getElementById(scriptId)) {
      const script = document.createElement('script')
      script.id = scriptId
      script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onloadTurnstileCallback&render=explicit'
      script.async = true
      script.defer = true
      script.onload = () => {
        setTimeout(renderWidget, 100)
      }
      document.head.appendChild(script)
    } else {
      setTimeout(renderWidget, 150)
    }
  }
}

const resetTurnstile = () => {
  if (typeof window !== 'undefined' && (window as any).turnstile && widgetId !== null) {
    try {
      (window as any).turnstile.reset(widgetId)
    } catch (_) {}
  }
  form.turnstile_token = ''
}

const removeTurnstile = () => {
  if (typeof window !== 'undefined' && (window as any).turnstile && widgetId !== null) {
    try {
      (window as any).turnstile.remove(widgetId)
    } catch (_) {}
    widgetId = null
  }
  form.turnstile_token = ''
}

const showPassword = ref(false)
const showNewPassword = ref(false)
const capsLockOn = ref(false)
const isDark = ref(true)
const isLangOpen = ref(false)
const showSuccessModal = ref(false)
const showErrorModal = ref(false)
const statusMessage = ref<string | null>(null)
const oauthNotice = ref<{
  type: 'warning' | 'error' | 'success'
  message: string
} | null>(null)

// Email / Identifier Validation
const isValidIdentifier = computed(() => {
  const input = form.email ? form.email.trim() : ''
  return input.length >= 3
})

const isTurnstileVerified = computed(() => {
  return !!form.turnstile_token
})

const canContinue = computed(() => {
  return isValidIdentifier.value && (isLocalHost.value || isTurnstileVerified.value)
})

const isCheckingUser = ref(false)

// Handle Complete Auth Decision Check when clicking Continue on Step 1
const handleCheckIdentifier = async () => {
  if (!canContinue.value || isCheckingUser.value) return
  isCheckingUser.value = true
  oauthNotice.value = null
  errorMessage.value = null

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const response = await fetch('/api/auth/check-identifier', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ email: form.email.trim() }),
    })

    const data = await response.json()

    if (data.exists) {
      matchedUser.value = {
        name: data.name,
        role: data.role,
        email: data.email || form.email,
      }
      form.role = data.role || 'student'

      if (data.provider === 'google') {
        // Step 2: Google Account -> Redirect directly to Google OAuth
        redirectToGoogleOAuth()
      } else {
        // Step 2: Existing Password Account -> Show Password Screen
        step.value = 'enter_password'
      }
    } else {
      matchedUser.value = null
      // Step 2: New Account -> Seamlessly redirect to the full SPI Academic Registration Wizard
      isAuthenticating.value = true
      authLoadingTitle.value = currentLang.value === 'km' ? 'គណនីថ្មី! កំពុងនាំអ្នកទៅកាន់ផ្ទាំងចុះឈ្មោះ...' : 'New Account! Redirecting to Registration...'
      authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ដើម្បីបំពេញព័ត៌មានចុះឈ្មោះ...' : 'Please wait a moment to complete your registration profile...'

      setTimeout(() => {
        router.visit(`/register?email=${encodeURIComponent(form.email.trim())}`)
      }, 1500)
    }
  } catch (err: any) {
    console.error('Check identifier error:', err)
    step.value = 'enter_password'
  } finally {
    isCheckingUser.value = false
  }
}

// Email & Phone OTP State
const authMode = ref<'password' | 'otp' | 'phone_otp'>('password')
const otpStep = ref<1 | 2>(1)
const otpEmail = ref('')
const otpPhone = ref('')
const phoneOtpStep = ref<1 | 2>(1)
const otpCode = ref('')
const isOtpSending = ref(false)
const isOtpVerifying = ref(false)
const isPhoneOtpSending = ref(false)
const isPhoneOtpVerifying = ref(false)
const otpCountdown = ref(0)
const phoneOtpCountdown = ref(0)
let otpCountdownTimer: any = null
let phoneOtpCountdownTimer: any = null

// 6-digit Segmented PIN Input System
const otpDigits = ref<string[]>(['', '', '', '', '', ''])
const digitRef0 = ref<HTMLInputElement | null>(null)
const digitRef1 = ref<HTMLInputElement | null>(null)
const digitRef2 = ref<HTMLInputElement | null>(null)
const digitRef3 = ref<HTMLInputElement | null>(null)
const digitRef4 = ref<HTMLInputElement | null>(null)
const digitRef5 = ref<HTMLInputElement | null>(null)

const getDigitInput = (idx: number) => {
  return [digitRef0, digitRef1, digitRef2, digitRef3, digitRef4, digitRef5][idx]?.value
}

const clearOtpDigits = () => {
  otpDigits.value = ['', '', '', '', '', '']
  otpCode.value = ''
}

const focusFirstOtpDigit = () => {
  nextTick(() => {
    setTimeout(() => {
      getDigitInput(0)?.focus()
    }, 100)
  })
}

const onDigitInput = (index: number, event: Event) => {
  const target = event.target as HTMLInputElement
  const raw = target.value.replace(/[^0-9]/g, '')
  if (raw.length > 1) {
    const chars = raw.slice(0, 6).split('')
    chars.forEach((c, i) => {
      if (index + i < 6) otpDigits.value[index + i] = c
    })
    otpCode.value = otpDigits.value.join('')
    const nextIdx = Math.min(index + chars.length, 5)
    getDigitInput(nextIdx)?.focus()
    if (otpCode.value.length === 6) {
      if (authMode.value === 'phone_otp') verifyPhoneOtp()
      else if (authMode.value === 'otp') verifyEmailOtp()
    }
  } else {
    otpDigits.value[index] = raw
    otpCode.value = otpDigits.value.join('')
    if (raw && index < 5) {
      getDigitInput(index + 1)?.focus()
    }
    if (otpCode.value.length === 6) {
      if (authMode.value === 'phone_otp') verifyPhoneOtp()
      else if (authMode.value === 'otp') verifyEmailOtp()
    }
  }
}

const onDigitKeydown = (index: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace') {
    if (!otpDigits.value[index] && index > 0) {
      otpDigits.value[index - 1] = ''
      otpCode.value = otpDigits.value.join('')
      getDigitInput(index - 1)?.focus()
    } else {
      otpDigits.value[index] = ''
      otpCode.value = otpDigits.value.join('')
    }
  } else if (event.key === 'ArrowLeft' && index > 0) {
    getDigitInput(index - 1)?.focus()
  } else if (event.key === 'ArrowRight' && index < 5) {
    getDigitInput(index + 1)?.focus()
  } else if (event.key === 'Enter') {
    if (authMode.value === 'phone_otp') verifyPhoneOtp()
    else if (authMode.value === 'otp') verifyEmailOtp()
  }
}

const onDigitPaste = (event: ClipboardEvent) => {
  event.preventDefault()
  const text = event.clipboardData?.getData('text') || ''
  const digits = text.replace(/[^0-9]/g, '').slice(0, 6).split('')
  if (digits.length > 0) {
    digits.forEach((d, i) => {
      if (i < 6) otpDigits.value[i] = d
    })
    otpCode.value = otpDigits.value.join('')
    const nextIdx = Math.min(digits.length, 5)
    getDigitInput(nextIdx)?.focus()
    if (otpCode.value.length === 6) {
      if (authMode.value === 'phone_otp') verifyPhoneOtp()
      else if (authMode.value === 'otp') verifyEmailOtp()
    }
  }
}

const formattedOtpTime = computed(() => {
  const mins = Math.floor(otpCountdown.value / 60).toString().padStart(2, '0')
  const secs = (otpCountdown.value % 60).toString().padStart(2, '0')
  return `${mins}:${secs}`
})

const formattedPhoneOtpTime = computed(() => {
  const mins = Math.floor(phoneOtpCountdown.value / 60).toString().padStart(2, '0')
  const secs = (phoneOtpCountdown.value % 60).toString().padStart(2, '0')
  return `${mins}:${secs}`
})

const formattedDisplayPhone = computed(() => {
  const clean = otpPhone.value.replace(/[^0-9]/g, '')
  const withoutZero = clean.replace(/^855/, '').replace(/^0/, '')
  return '+855 ' + withoutZero
})

const startOtpTimer = (seconds = 300) => {
  otpCountdown.value = seconds
  if (otpCountdownTimer) clearInterval(otpCountdownTimer)
  otpCountdownTimer = setInterval(() => {
    if (otpCountdown.value > 0) {
      otpCountdown.value--
    } else {
      clearInterval(otpCountdownTimer)
    }
  }, 1000)
}

const startPhoneOtpTimer = (seconds = 300) => {
  phoneOtpCountdown.value = seconds
  if (phoneOtpCountdownTimer) clearInterval(phoneOtpCountdownTimer)
  phoneOtpCountdownTimer = setInterval(() => {
    if (phoneOtpCountdown.value > 0) {
      phoneOtpCountdown.value--
    } else {
      clearInterval(phoneOtpCountdownTimer)
    }
  }, 1000)
}

const sendEmailOtp = async () => {
  if (!otpEmail.value || isOtpSending.value) return
  isOtpSending.value = true
  oauthNotice.value = null

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const response = await fetch('/auth/email-otp/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ email: otpEmail.value }),
    })

    const data = await response.json()
    if (response.ok && data.success) {
      otpStep.value = 2
      clearOtpDigits()
      startOtpTimer(300)
      focusFirstOtpDigit()
      oauthNotice.value = {
        type: 'warning',
        message: data.message || (currentLang.value === 'km' ? 'លេខកូដ OTP ត្រូវបានផ្ញើចូលប្រអប់សំបុត្រ Gmail របស់អ្នកហើយ!' : 'OTP code has been sent to your email!')
      }
    } else {
      oauthNotice.value = {
        type: 'error',
        message: data.message || (currentLang.value === 'km' ? 'មិនអាចផ្ញើលេខកូដបានទេ!' : 'Failed to send OTP code!')
      }
    }
  } catch (err: any) {
    oauthNotice.value = {
      type: 'error',
      message: currentLang.value === 'km' ? 'មានបញ្ហាក្នុងការតភ្ជាប់ទៅកាន់ម៉ាស៊ីនបម្រើ' : 'Connection error'
    }
  } finally {
    isOtpSending.value = false
  }
}

const verifyEmailOtp = async () => {
  if (!otpCode.value || otpCode.value.length < 6 || isOtpVerifying.value) return
  isOtpVerifying.value = true
  isAuthenticating.value = true
  authLoadingTitle.value = currentLang.value === 'km' ? 'កំពុងផ្ទៀងផ្ទាត់ OTP...' : 'Verifying OTP...'
  authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងផ្ទៀងផ្ទាត់ និងនាំអ្នកទៅកាន់ Dashboard' : 'Please wait a moment while verifying your OTP...'

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const response = await fetch('/auth/email-otp/verify', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        email: otpEmail.value,
        otp: otpCode.value,
      }),
    })

    const rawText = await response.text()
    let data: any = {}
    try {
      data = JSON.parse(rawText)
    } catch (e) {
      if (response.ok || response.status === 200 || response.redirected) {
        window.location.assign('/dashboard')
        return
      }
      data = {
        message: currentLang.value === 'km' ? 'មានបញ្ហាបច្ចេកទេសលើ Server' : 'Technical server error'
      }
    }

    if (response.ok && data.success) {
      if (data.token) {
        try { localStorage.setItem('auth_token', data.token) } catch (e) {}
      }
      setTimeout(() => {
        window.location.assign(data.redirect || '/student/dashboard')
      }, 1200)
    } else {
      isAuthenticating.value = false
      let errMsg = data.message || ''
      if (typeof errMsg === 'string' && (errMsg.startsWith('<') || errMsg.includes('<!DOCTYPE'))) {
        errMsg = currentLang.value === 'km' ? 'មានបញ្ហាបច្ចេកទេសលើ Server សូមព្យាយាមម្តងទៀត' : 'Server error, please try again'
      }
      oauthNotice.value = {
        type: 'error',
        message: errMsg || (currentLang.value === 'km' ? 'លេខកូដ OTP មិនត្រឹមត្រូវ ឬផុតកំណត់!' : 'Invalid or expired OTP code!')
      }
    }
  } catch (err: any) {
    isAuthenticating.value = false
    oauthNotice.value = {
      type: 'error',
      message: err?.message || (currentLang.value === 'km' ? 'មានបញ្ហាក្នុងការផ្ទៀងផ្ទាត់ OTP' : 'OTP verification error')
    }
  } finally {
    isOtpVerifying.value = false
  }
}

const sendPhoneOtp = async () => {
  if (!otpPhone.value || isPhoneOtpSending.value) return
  isPhoneOtpSending.value = true
  oauthNotice.value = null

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const response = await fetch('/auth/phone-otp/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ phone: otpPhone.value.trim() }),
    })

    const data = await response.json()
    if (response.ok && data.success) {
      phoneOtpStep.value = 2
      clearOtpDigits()
      startPhoneOtpTimer(300)
      focusFirstOtpDigit()
      oauthNotice.value = {
        type: 'warning',
        message: data.message || (currentLang.value === 'km' ? 'លេខកូដ OTP ត្រូវបានផ្ញើទៅកាន់លេខទូរសព្ទរបស់អ្នកតាមរយៈ SMS!' : 'OTP code has been sent to your phone via SMS!')
      }
    } else {
      oauthNotice.value = {
        type: 'error',
        message: data.message || (currentLang.value === 'km' ? 'មិនអាចផ្ញើលេខកូដ SMS បានទេ!' : 'Failed to send SMS OTP code!')
      }
    }
  } catch (err: any) {
    oauthNotice.value = {
      type: 'error',
      message: currentLang.value === 'km' ? 'មានបញ្ហាក្នុងការតភ្ជាប់ទៅកាន់ម៉ាស៊ីនបម្រើ' : 'Connection error'
    }
  } finally {
    isPhoneOtpSending.value = false
  }
}

const verifyPhoneOtp = async () => {
  if (!otpCode.value || otpCode.value.length < 6 || isPhoneOtpVerifying.value) return
  isPhoneOtpVerifying.value = true
  isAuthenticating.value = true
  authLoadingTitle.value = currentLang.value === 'km' ? 'កំពុងផ្ទៀងផ្ទាត់ OTP ទូរសព្ទ...' : 'Verifying Phone OTP...'
  authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងផ្ទៀងផ្ទាត់ និងនាំអ្នកទៅកាន់ Dashboard' : 'Please wait a moment while verifying your OTP...'

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const response = await fetch('/auth/phone-otp/verify', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        phone: otpPhone.value.trim(),
        otp: otpCode.value.trim(),
      }),
    })

    const rawText = await response.text()
    let data: any = {}
    try {
      data = JSON.parse(rawText)
    } catch (e) {
      if (response.ok || response.status === 200 || response.redirected) {
        window.location.assign('/dashboard')
        return
      }
      data = {
        message: currentLang.value === 'km' ? 'មានបញ្ហាបច្ចេកទេសលើ Server' : 'Technical server error'
      }
    }

    if (response.ok && data.success) {
      if (data.token) {
        try { localStorage.setItem('auth_token', data.token) } catch (e) {}
      }
      setTimeout(() => {
        window.location.assign(data.redirect || '/student/dashboard')
      }, 1200)
    } else {
      isAuthenticating.value = false
      let errMsg = data.message || ''
      if (typeof errMsg === 'string' && (errMsg.startsWith('<') || errMsg.includes('<!DOCTYPE'))) {
        errMsg = currentLang.value === 'km' ? 'មានបញ្ហាបច្ចេកទេសលើ Server សូមព្យាយាមម្តងទៀត' : 'Server error, please try again'
      }
      oauthNotice.value = {
        type: 'error',
        message: errMsg || (currentLang.value === 'km' ? 'លេខកូដ OTP មិនត្រឹមត្រូវ ឬផុតកំណត់!' : 'Invalid or expired OTP code!')
      }
    }
  } catch (err: any) {
    isAuthenticating.value = false
    oauthNotice.value = {
      type: 'error',
      message: err?.message || (currentLang.value === 'km' ? 'មានបញ្ហាក្នុងការផ្ទៀងផ្ទាត់ OTP' : 'OTP verification error')
    }
  } finally {
    isPhoneOtpVerifying.value = false
  }
}

onUnmounted(() => {
  if (otpCountdownTimer) clearInterval(otpCountdownTimer)
  if (phoneOtpCountdownTimer) clearInterval(phoneOtpCountdownTimer)
})

watch(step, (newStep) => {
  if (newStep === 'identifier') {
    nextTick(() => {
      initTurnstile()
    })
  }
})

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
  try {
    const saved = localStorage.getItem('theme')
    if (saved) {
      isDark.value = saved === 'dark'
    } else {
      isDark.value = true
    }
  } catch (e) {
    isDark.value = true
  }
  applyTheme()
}

const toggleTheme = () => {
  isDark.value = !isDark.value
  try {
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
  } catch (e) {}
  applyTheme()
  removeTurnstile()
  nextTick(() => {
    initTurnstile()
  })
}

const applyTheme = () => {
  if (typeof document === 'undefined') return
  if (isDark.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

const isSubmitting = ref(false)
const errorMessage = ref<string | null>(null)

const submit = async () => {
  if (isSubmitting.value || form.processing) return
  isSubmitting.value = true
  showErrorModal.value = false
  showSuccessModal.value = false
  errorMessage.value = null
  form.clearErrors()

  // 1. Ensure Turnstile token is available before posting
  if (!form.turnstile_token) {
    if (typeof window !== 'undefined' && (window as any).turnstile && widgetId !== null) {
      try {
        const directToken = (window as any).turnstile.getResponse(widgetId)
        if (directToken) {
          form.turnstile_token = directToken
        }
      } catch (_) {}
    }

    if (!form.turnstile_token) {
      for (let i = 0; i < 30; i++) {
        await new Promise((r) => setTimeout(r, 100))
        if (form.turnstile_token) break
        try {
          if ((window as any).turnstile && widgetId !== null) {
            const t = (window as any).turnstile.getResponse(widgetId)
            if (t) {
              form.turnstile_token = t
              break
            }
          }
        } catch (_) {}
      }
    }

    if (!form.turnstile_token) {
      if (isLocalHost.value) {
        form.turnstile_token = '1x_local_dev_token'
      } else {
        isSubmitting.value = false
        resetTurnstile()
        form.setError('turnstile_token', currentLang.value === 'km' ? 'សូមរង់ចាំឱ្យ Cloudflare បង្ហាញសញ្ញាគ្រីសបៃតង (Success) រួចចុចម្តងទៀត។' : 'Please wait for Cloudflare verification to succeed before continuing.')
        return
      }
    }
  }

  // 2. Submit via native Inertia form.post
  form.post('/login', {
    preserveScroll: true,
    onSuccess: () => {
      showSuccessModal.value = true
      isSubmitting.value = false
    },
    onError: (errors: any) => {
      showSuccessModal.value = false
      showErrorModal.value = true
      isSubmitting.value = false

      const firstError = Object.values(errors)[0]
      let msg = typeof firstError === 'string' ? firstError : ''
      
      if (currentLang.value === 'en') {
        if (msg.includes('ពាក្យសម្ងាត់ត្រូវមានយ៉ាងតិច') || msg.includes('8 តួអក្សរ') || msg.includes('៨ តួអក្សរ')) {
          msg = 'Password must be at least 8 characters.'
        } else if (msg.includes('សូមបញ្ចូលពាក្យសម្ងាត់')) {
          msg = 'Please enter your password.'
        } else if (msg.includes('សូមបញ្ចូលអាសយដ្ឋានអ៊ីមែល')) {
          msg = 'Please enter your email, ID, or phone number.'
        } else if (msg.includes('គណនី ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ') || msg.includes('មិនត្រឹមត្រូវ')) {
          msg = 'Incorrect email or password. Please try again.'
        } else if (msg.includes('គណនីត្រូវបានផ្អាក')) {
          msg = 'Account temporarily locked due to too many failed attempts.'
        }
      } else {
        if (!msg) {
          msg = t('login_modal_error_msg', 'សូមពិនិត្យមើលអាសយដ្ឋានអ៊ីមែល ឬពាក្យសម្ងាត់របស់អ្នកឡើងវិញ ហើយព្យាយាមម្តងទៀត។')
        }
      }

      errorMessage.value = msg || (currentLang.value === 'km' ? 'សូមពិនិត្យមើលអាសយដ្ឋានអ៊ីមែល ឬពាក្យសម្ងាត់របស់អ្នកឡើងវិញ ហើយព្យាយាមម្តងទៀត។' : 'Please check your email or password and try again.')

      resetTurnstile()
      setTimeout(() => {
        showErrorModal.value = false
      }, 4500)
    },
    onFinish: () => {
      isSubmitting.value = false
      form.reset('password')
    },
  })
}

const getTelegramOAuthUrl = () => {
  return 'https://oauth.telegram.org/auth?bot_id=8828915669&origin=https%3A%2F%2Fspilms.tech&return_to=https%3A%2F%2Fspilms.tech%2Fauth%2Ftelegram%2Fcallback&request_access=write'
}

const isAuthenticating = ref(false)
const authLoadingTitle = ref('')
const authLoadingSubtitle = ref('')
const isGoogleLoading = ref(false)
const isTelegramLoading = ref(false)

let activePopup: Window | null = null
let popupCheckTimer: any = null

const stopPopupTracking = () => {
  if (popupCheckTimer) {
    clearInterval(popupCheckTimer)
    popupCheckTimer = null
  }
  activePopup = null
}

const checkPopupClosed = () => {
  if (activePopup && activePopup.closed) {
    stopPopupTracking()
    const isLocalhost = typeof window !== 'undefined' && (window.location.hostname === '127.0.0.1' || window.location.hostname === 'localhost')

    // In local development environment, if user closed or accepted the Telegram popup, automatically complete login!
    if (isLocalhost && !isAuthenticating.value) {
      handleTelegramAuthSuccess({
        id: '78291045',
        first_name: 'Kosal',
        last_name: 'Sensok',
        username: 'kosalsensok',
        photo_url: '/images/logo.png',
        auth_date: Math.floor(Date.now() / 1000)
      })
      return
    }

    if (!isAuthenticating.value || (!authLoadingTitle.value.includes('ផ្ទៀងផ្ទាត់') && !authLoadingTitle.value.includes('Verifying'))) {
      isTelegramLoading.value = false
      isGoogleLoading.value = false
      isAuthenticating.value = false
    }
  }
}

const handleTelegramPostMessage = (event: MessageEvent) => {
  const origin = event.origin || ''
  const isAllowedOrigin = origin.includes('telegram.org') || origin.includes('spilms.tech') || (typeof window !== 'undefined' && origin === window.location.origin)
  if (!isAllowedOrigin) return

  if (event.data) {
    try {
      const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data
      if (data.event === 'auth_result') {
        stopPopupTracking()
        if (data.result === false) {
          isAuthenticating.value = false
          isTelegramLoading.value = false
          oauthNotice.value = {
            type: 'warning',
            message: currentLang.value === 'km'
              ? 'លោកអ្នកបានបដិសេធការ Login! សូមចុច Accept ដើម្បីចូលប្រើប្រាស់។'
              : 'Login was cancelled. Please accept Telegram permissions to access your account.'
          }
        } else if (data.result && (data.result.id || typeof data.result === 'object')) {
          isAuthenticating.value = true
          isTelegramLoading.value = true
          authLoadingTitle.value = currentLang.value === 'km' ? 'កំពុងផ្ទៀងផ្ទាត់ Telegram...' : 'Verifying Telegram Account...'
          authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងរៀបចំ Dashboard ជូនលោកអ្នក' : 'Please wait a moment while setting up your dashboard...'
          handleTelegramAuthSuccess(data.result)
        }
      }
    } catch (e) {}
  }
}

const redirectToTelegramOAuth = () => {
  if (typeof window === 'undefined') return
  isTelegramLoading.value = true
  stopPopupTracking()

  const url = getTelegramOAuthUrl()
  const width = 550
  const height = 650
  const left = window.screenX + Math.max(0, (window.outerWidth - width) / 2)
  const top = window.screenY + Math.max(0, (window.outerHeight - height) / 2)

  const popup = window.open(
    url,
    'telegram_oauth',
    `width=${width},height=${height},left=${left},top=${top},status=0,toolbar=0,menubar=0,location=1`
  )

  if (!popup || popup.closed || typeof popup.closed === 'undefined') {
    isAuthenticating.value = true
    authLoadingTitle.value = currentLang.value === 'km' ? 'កំពុងតភ្ជាប់ទៅកាន់ Telegram...' : 'Connecting to Telegram...'
    authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងនាំអ្នកទៅកាន់ Telegram Login' : 'Please wait a moment while redirecting to Telegram...'
    setTimeout(() => {
      window.location.assign(url)
    }, 250)
  } else {
    activePopup = popup
    popupCheckTimer = setInterval(checkPopupClosed, 300)
  }
}

const handleTelegramAuthSuccess = async (tgUser: any) => {
  isAuthenticating.value = true
  isTelegramLoading.value = true
  authLoadingTitle.value = currentLang.value === 'km' ? 'កំពុងផ្ទៀងផ្ទាត់ Telegram...' : 'Verifying Telegram Account...'
  authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងរៀបចំ Dashboard ជូនលោកអ្នក' : 'Please wait a moment while setting up your dashboard...'

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const response = await fetch('/auth/telegram', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(tgUser),
    })

    const data = await response.json()
    if (data.success && data.redirect) {
      setTimeout(() => {
        window.location.href = data.redirect
      }, 1200)
    } else {
      setTimeout(() => {
        window.location.href = '/student/dashboard'
      }, 1200)
    }
  } catch (err: any) {
    try {
      const form = document.createElement('form')
      form.method = 'POST'
      form.action = '/auth/telegram'
      for (const key in tgUser) {
        if (Object.prototype.hasOwnProperty.call(tgUser, key) && tgUser[key] !== null && tgUser[key] !== undefined) {
          const input = document.createElement('input')
          input.type = 'hidden'
          input.name = key
          input.value = typeof tgUser[key] === 'object' ? JSON.stringify(tgUser[key]) : String(tgUser[key])
          form.appendChild(input)
        }
      }
      document.body.appendChild(form)
      form.submit()
    } catch (_) {
      isAuthenticating.value = false
      isTelegramLoading.value = false
      oauthNotice.value = {
        type: 'error',
        message: currentLang.value === 'km' ? 'មានបញ្ហាក្នុងការតភ្ជាប់ទៅកាន់ម៉ាស៊ីនបម្រើ។ សូមព្យាយាមម្តងទៀត!' : 'Connection error. Please try again!'
      }
    }
  }
}

const redirectToGoogleOAuth = () => {
  isGoogleLoading.value = true
  isAuthenticating.value = true
  authLoadingTitle.value = currentLang.value === 'km' ? 'កំពុងតភ្ជាប់ទៅកាន់ Google...' : 'Connecting to Google...'
  authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងនាំអ្នកទៅកាន់ផ្ទាំង Google Sign-In...' : 'Please wait a moment while redirecting to Google Sign-In...'

  setTimeout(() => {
    const emailParam = form.email ? `?email=${encodeURIComponent(form.email.trim())}` : ''
    window.location.assign(`/auth/google/redirect${emailParam}`)
  }, 1200)
}

const handleKeyCheck = (e: KeyboardEvent) => {
  try {
    capsLockOn.value = e.getModifierState ? e.getModifierState('CapsLock') : false
  } catch (e) {}
}

const handleClickOutside = (e: MouseEvent) => {
  try {
    const target = e.target as HTMLElement
    if (target && !target.closest('.lang-switcher-container')) {
      isLangOpen.value = false
    }
  } catch (e) {}
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    initTheme()
    window.addEventListener('keydown', handleKeyCheck)
    window.addEventListener('keyup', handleKeyCheck)
    document.addEventListener('click', handleClickOutside)

    ;(window as any).onTelegramAuth = (user: any) => {
      try {
        handleTelegramAuthSuccess(user)
      } catch (e) {}
    }

    try {
      const flashStatus = props.status || (page.props as any).status || (page.props as any).flash?.status
      if (flashStatus) {
        statusMessage.value = flashStatus
        showSuccessModal.value = true
        setTimeout(() => {
          showSuccessModal.value = false
        }, 4500)
      }
    } catch (e) {}

    window.addEventListener('message', handleTelegramPostMessage)
    window.addEventListener('focus', checkPopupClosed)
    window.addEventListener('pageshow', () => {
      stopPopupTracking()
      isAuthenticating.value = false
      isGoogleLoading.value = false
      isTelegramLoading.value = false
    })

    // Check for Telegram OAuth URL fragment (#tgAuthResult=...)
    if (window.location.hash && window.location.hash.includes('tgAuthResult=')) {
      try {
        const hashStr = window.location.hash.substring(1)
        window.history.replaceState(null, '', window.location.pathname)
        const params = new URLSearchParams(hashStr)
        const tgAuthResult = params.get('tgAuthResult')
        if (tgAuthResult) {
          let base64 = tgAuthResult.replace(/-/g, '+').replace(/_/g, '/')
          while (base64.length % 4 !== 0) base64 += '='
          const decoded = decodeURIComponent(escape(atob(base64)))
          const tgUser = JSON.parse(decoded)
          if (tgUser && tgUser.id) {
            handleTelegramAuthSuccess(tgUser)
            return
          }
        }
      } catch (e) {
        console.error('Telegram Auth Result Hash parsing error:', e)
      }
    }

    // Check for query parameter errors
    const urlParams = new URLSearchParams(window.location.search)
    const err = urlParams.get('error')
    const status = urlParams.get('status')
    if (status === 'declined' || err === 'cancelled' || err === 'declined' || err === 'telegram_cancelled') {
      oauthNotice.value = {
        type: 'warning',
        message: currentLang.value === 'km'
          ? 'លោកអ្នកបានបដិសេធការ Login! សូមចុច Accept ដើម្បីចូលប្រើប្រាស់។'
          : 'Login was cancelled. Please accept Telegram permissions to access your account.'
      }
      try {
        window.history.replaceState({}, document.title, window.location.pathname)
      } catch (_) {}
    } else if (err === 'unauthorized' || err === 'failed') {
      oauthNotice.value = {
        type: 'error',
        message: currentLang.value === 'km'
          ? 'ការផ្ទៀងផ្ទាត់ Telegram មិនត្រឹមត្រូវទេ។ សូមព្យាយាមម្តងទៀត!'
          : 'Telegram authentication failed. Please try again!'
      }
      try {
        window.history.replaceState({}, document.title, window.location.pathname)
      } catch (_) {}
    } else if (urlParams.get('id') && urlParams.get('hash')) {
      const tgUser: Record<string, string> = {}
      urlParams.forEach((val, key) => {
        tgUser[key] = val
      })
      try {
        window.history.replaceState({}, document.title, window.location.pathname)
      } catch (_) {}
      handleTelegramAuthSuccess(tgUser)
    }

    // Initialize Cloudflare Turnstile
    initTurnstile()
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('keydown', handleKeyCheck)
    window.removeEventListener('keyup', handleKeyCheck)
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('message', handleTelegramPostMessage)
    window.removeEventListener('focus', checkPopupClosed)
    stopPopupTracking()
    if ((window as any).onTelegramAuth) {
      delete (window as any).onTelegramAuth
    }
    removeTurnstile()
  }
})
</script>

<template>
  <div class="min-h-screen w-full bg-[#f8fafc] dark:bg-[#000000] text-zinc-900 dark:text-[#ededed] flex flex-col justify-between relative font-sans overflow-x-hidden select-none transition-colors duration-300">
    
    <!-- Manus AI Signature Interactive Matrix Dots Background -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
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
            class="px-3 py-1.5 rounded-full bg-white/90 dark:bg-[#121214]/80 backdrop-blur-md hover:bg-zinc-100 dark:hover:bg-[#1c1c1f] text-zinc-700 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white transition-all duration-150 border border-zinc-300/80 dark:border-zinc-800 shadow-xs flex items-center gap-2 text-xs font-semibold cursor-pointer"
          >
            <img
              :src="languages.find(l => l.code === currentLang)?.flagUrl || '/images/flags/km.svg'"
              :alt="currentLang"
              class="w-3.5 h-3.5 rounded-full object-cover shrink-0 ring-1 ring-zinc-300 dark:ring-zinc-700"
            />
            <span class="text-[11px] font-bold tracking-wide">
              {{ currentLang === 'km' ? 'KH' : 'EN' }}
            </span>
            <i :class="['pi pi-chevron-down text-[9px] text-zinc-400 transition-transform duration-200', isLangOpen ? 'rotate-180 text-zinc-950 dark:text-white' : '']"></i>
          </button>

          <!-- Dropdown Menu -->
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
              class="absolute right-0 mt-2 w-36 rounded-xl bg-white/95 dark:bg-[#121214]/95 backdrop-blur-xl border border-zinc-200 dark:border-zinc-800 shadow-2xl py-1.5 z-50 overflow-hidden"
            >
              <button
                v-for="lang in languages"
                :key="lang.code"
                type="button"
                @click="selectLanguage(lang.code)"
                :class="[
                  'w-full flex items-center justify-between px-3 py-2 text-xs font-semibold transition-colors cursor-pointer',
                  currentLang === lang.code
                    ? 'bg-zinc-100 dark:bg-zinc-800/70 text-zinc-900 dark:text-white font-bold'
                    : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 hover:text-zinc-900 dark:hover:text-zinc-200'
                ]"
              >
                <span class="flex items-center gap-2">
                  <img :src="lang.flagUrl" :alt="lang.name" class="w-3.5 h-3.5 rounded-full object-cover shrink-0" />
                  <span>{{ lang.name }}</span>
                </span>
                <i v-if="currentLang === lang.code" class="pi pi-check text-[10px] text-zinc-900 dark:text-white font-bold"></i>
              </button>
            </div>
          </Transition>
        </div>

        <!-- Theme Switcher Pill -->
        <button
          type="button"
          @click="toggleTheme"
          class="p-1.5 px-2.5 rounded-full bg-white/90 dark:bg-[#121214]/80 backdrop-blur-md hover:bg-zinc-100 dark:hover:bg-[#1c1c1f] text-zinc-700 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white transition-all duration-150 border border-zinc-300/80 dark:border-zinc-800 shadow-xs flex items-center gap-1.5 text-xs font-semibold cursor-pointer select-none"
          :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        >
          <i :class="['pi text-xs transition-transform duration-200', isDark ? 'pi-sun text-amber-400' : 'pi-moon text-indigo-500']"></i>
        </button>

      </div>
    </header>

    <!-- Main Auth Center Stage -->
    <main class="w-full flex-grow flex flex-col items-center justify-center px-4 py-6 relative z-10">
      
      <!-- Normal Form View (When not in full loading overlay) -->
      <div v-if="!isAuthenticating" class="w-full max-w-[390px] flex flex-col items-center">
        
        <!-- Center E-LMS Logo -->
        <div class="mb-3.5 relative group">
          <div class="absolute -inset-1.5 bg-sky-500/20 rounded-full blur-md opacity-40 group-hover:opacity-80 transition duration-300 pointer-events-none"></div>
          <img
            :src="logoUrl"
            alt="E-LMS Logo"
            class="relative w-[72px] h-[72px] rounded-full drop-shadow-lg object-contain transition-transform duration-300 group-hover:scale-105"
          />
        </div>

        <!-- Heading & Subtitle -->
        <h1 class="text-2xl sm:text-[26px] font-black tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-white dark:via-zinc-100 dark:to-zinc-300 bg-clip-text text-transparent text-center transition-colors">
          {{ step === 'enter_password' ? (currentLang === 'km' ? 'ចូលប្រើប្រាស់' : 'Sign in') : t('login_title_manus', 'ចូលប្រើប្រាស់ ឬ បង្កើតគណនី') }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 text-center mt-1.5 mb-6 transition-colors">
          {{ step === 'enter_password' ? (currentLang === 'km' ? 'សូមបញ្ចូលពាក្យសម្ងាត់គណនីរបស់អ្នក' : 'Please enter your password') : t('login_subtitle_manus', 'ចាប់ផ្តើមបង្កើត និងរៀនសូត្រជាមួយ E-LMS') }}
        </p>

        <!-- OAuth Error / Notification Banner -->
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div
            v-if="oauthNotice"
            :class="[
              'w-full mb-4 rounded-xl p-3 text-xs flex items-start justify-between gap-2.5 border',
              oauthNotice.type === 'warning'
                ? 'bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300'
                : 'bg-rose-500/10 border-rose-500/30 text-rose-700 dark:text-rose-300'
            ]"
          >
            <div class="flex items-start gap-2">
              <i :class="['shrink-0 text-sm mt-0.5', oauthNotice.type === 'warning' ? 'pi pi-exclamation-triangle text-amber-500 dark:text-amber-400' : 'pi pi-times-circle text-rose-500 dark:text-rose-400']"></i>
              <span class="font-medium text-[11px] leading-relaxed">{{ oauthNotice.message }}</span>
            </div>
            <button type="button" @click="oauthNotice = null" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-white p-0.5 cursor-pointer">
              <i class="pi pi-times text-[10px]"></i>
            </button>
          </div>
        </Transition>

        <!-- ========================================================================= -->
        <!-- STEP 1 (Default Screen): IDENTIFIER ONLY (No Password & No Role Tabs)     -->
        <!-- ========================================================================= -->
        <div v-if="step === 'identifier' && authMode === 'password'" class="w-full space-y-4">
          
          <!-- Social Buttons Stack (Google, Telegram, Email) -->
          <div class="w-full space-y-2.5">
            
            <!-- 1. Google Button -->
            <button
              type="button"
              :disabled="isAuthenticating"
              @click="redirectToGoogleOAuth"
              class="w-full h-11 px-4 rounded-xl bg-white hover:bg-zinc-50 dark:bg-[#18181b] dark:hover:bg-[#232327] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 text-zinc-900 dark:text-white text-xs sm:text-sm font-medium relative flex items-center justify-center transition-all duration-150 active:scale-[0.99] cursor-pointer disabled:opacity-50 select-none shadow-xs"
            >
              <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
              </svg>
              <span class="text-center font-medium">{{ t('login_btn_continue_google', 'បន្តជាមួយ Google') }}</span>
              <span class="absolute right-3.5 text-[10px] font-semibold px-2 py-0.5 rounded-md bg-sky-50 dark:bg-[#132337] text-sky-700 dark:text-sky-400 border border-sky-200 dark:border-sky-500/20">
                {{ t('login_badge_last_used', 'បានប្រើចុងក្រោយ') }}
              </span>
            </button>

            <!-- 2. Telegram Button -->
            <button
              type="button"
              :disabled="isAuthenticating"
              @click="redirectToTelegramOAuth"
              class="w-full h-11 px-4 rounded-xl bg-white hover:bg-zinc-50 dark:bg-[#18181b] dark:hover:bg-[#232327] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 text-zinc-900 dark:text-white text-xs sm:text-sm font-medium relative flex items-center justify-center transition-all duration-150 active:scale-[0.99] cursor-pointer disabled:opacity-50 select-none shadow-xs"
            >
              <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 shrink-0 fill-[#0088cc] dark:fill-[#29b6f6]" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .4z"/>
              </svg>
              <span class="text-center font-medium">{{ t('login_btn_continue_telegram', 'បន្តជាមួយ Telegram') }}</span>
            </button>

            <!-- 3. Email Button -->
            <button
              type="button"
              :disabled="isAuthenticating"
              @click="authMode = 'otp'; otpStep = 1; otpEmail = form.email || ''; otpCode = ''"
              class="w-full h-11 px-4 rounded-xl bg-white hover:bg-zinc-50 dark:bg-[#18181b] dark:hover:bg-[#232327] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 text-zinc-900 dark:text-white text-xs sm:text-sm font-medium relative flex items-center justify-center transition-all duration-150 active:scale-[0.99] cursor-pointer disabled:opacity-50 select-none shadow-xs"
            >
              <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 shrink-0 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="16" x="2" y="4" rx="2"/>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
              <span class="text-center font-medium">{{ t('login_btn_continue_email_otp', 'បន្តជាមួយ Email') }}</span>
            </button>

            <!-- 4. Phone Number Button -->
            <button
              type="button"
              :disabled="isAuthenticating"
              @click="authMode = 'phone_otp'; phoneOtpStep = 1; otpPhone = form.email && /^[0-9+ ]+$/.test(form.email) ? form.email : ''; otpCode = ''"
              class="w-full h-11 px-4 rounded-xl bg-white hover:bg-zinc-50 dark:bg-[#18181b] dark:hover:bg-[#232327] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 text-zinc-900 dark:text-white text-xs sm:text-sm font-medium relative flex items-center justify-center transition-all duration-150 active:scale-[0.99] cursor-pointer disabled:opacity-50 select-none shadow-xs"
            >
              <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
              <span class="text-center font-medium">{{ t('login_btn_continue_phone', 'បន្តជាមួយ Phone Number') }}</span>
            </button>


          </div>

          <!-- Subtle "Or" Divider -->
          <div class="w-full flex items-center my-4 text-zinc-400 dark:text-zinc-700">
            <div class="flex-grow border-t border-zinc-200 dark:border-zinc-800"></div>
            <span class="px-3 text-xs text-zinc-400 dark:text-zinc-500 font-medium tracking-wide">{{ t('login_or', 'ឬ') }}</span>
            <div class="flex-grow border-t border-zinc-200 dark:border-zinc-800"></div>
          </div>

          <!-- Single Identifier Form (Email / ID) -->
          <form @submit.prevent="handleCheckIdentifier" class="w-full space-y-2.5">
            
            <!-- Email / ID Input -->
            <div class="w-full">
              <input
                v-model="form.email"
                type="text"
                required
                autocomplete="username"
                :placeholder="t('login_input_email_placeholder_manus', 'បញ្ចូលអាសយដ្ឋានអ៊ីមែល')"
                class="w-full h-11 px-3.5 bg-white dark:bg-[#121214] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 focus:border-zinc-600 dark:focus:border-zinc-500 focus:ring-1 focus:ring-zinc-600 dark:focus:ring-zinc-500 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none transition-all duration-150 shadow-2xs"
                @keydown.enter.prevent="handleCheckIdentifier"
              />
            </div>

            <!-- Turnstile CAPTCHA Box -->
            <div class="w-full my-2">
              <!-- Local Environment: Render sleek Verified Cloudflare box with zero errors -->
              <div
                v-if="isLocalHost"
                class="w-full h-[62px] px-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-[#fbfbfc] dark:bg-[#121214] flex items-center justify-between shadow-2xs select-none"
              >
                <div class="flex items-center gap-2.5">
                  <div class="w-5 h-5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i class="pi pi-check text-[11px] font-bold"></i>
                  </div>
                  <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ currentLang === 'km' ? 'ការផ្ទៀងផ្ទាត់ជោគជ័យ' : 'Success! Verification complete' }}</span>
                </div>
                <div class="flex items-center gap-1.5 opacity-60">
                  <svg class="h-3.5 text-zinc-700 dark:text-zinc-300" viewBox="0 0 100 40" fill="currentColor">
                    <path d="M72.2 18.5c-.8-5.3-5.3-9.5-10.8-9.5-4.4 0-8.2 2.7-9.9 6.6-1.5-.9-3.2-1.4-5.1-1.4-5.1 0-9.2 4.1-9.2 9.2 0 .6.1 1.2.2 1.8-6.1.5-10.9 5.6-10.9 11.8 0 6.5 5.3 11.8 11.8 11.8h33.9c6.5 0 11.8-5.3 11.8-11.8 0-6.1-4.7-11.1-10.7-11.7-.1-2.4-.6-4.7-1.1-6.8z"/>
                  </svg>
                  <span class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">Cloudflare</span>
                </div>
              </div>

              <!-- Production Environment: Live Cloudflare Turnstile Challenge -->
              <div v-else ref="turnstileWidget" class="w-full block min-h-[65px] turnstile-wrapper"></div>
            </div>

            <!-- Dynamic Continue Button (Disabled with not-allowed cursor if not ready) -->
            <button
              type="submit"
              :disabled="!canContinue || isCheckingUser"
              :class="[
                'w-full h-11 rounded-xl text-xs sm:text-sm font-semibold flex items-center justify-center transition-all duration-150 select-none shadow-sm',
                canContinue && !isCheckingUser
                  ? 'bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99]'
                  : 'bg-slate-200 dark:bg-[#18181b] text-slate-400 dark:text-zinc-600 border border-slate-300 dark:border-zinc-800 cursor-not-allowed opacity-70'
              ]"
            >
              <i v-if="isCheckingUser" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ isCheckingUser ? (currentLang === 'km' ? 'កំពុងពិនិត្យ...' : 'Checking...') : t('login_btn_continue_manus', 'បន្តទៅមុខ') }}</span>
            </button>

          </form>
        </div>

        <!-- ========================================================================= -->
        <!-- STEP 2 (Case 2 - Existing Account): PASSWORD LOGIN (Role is from DB)      -->
        <!-- ========================================================================= -->
        <div v-else-if="step === 'enter_password' && authMode === 'password'" class="w-full space-y-3 animate-fade-in">
          <form @submit.prevent="submit" class="w-full space-y-3">
            
            <!-- Email & Role Display Pill with Edit Button -->
            <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-800 bg-white dark:bg-[#121214] shadow-2xs">
              <div class="flex items-center gap-2 min-w-0">
                <i class="pi pi-user text-xs text-zinc-400 shrink-0"></i>
                <span class="text-xs sm:text-sm font-medium text-zinc-900 dark:text-zinc-200 truncate">{{ form.email }}</span>

                <!-- Explicit Role Badge from Database -->
                <span
                  v-if="matchedUser?.role"
                  :class="[
                    'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold shrink-0 border uppercase tracking-wider',
                    matchedUser.role === 'admin'
                      ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30'
                      : matchedUser.role === 'teacher'
                        ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
                        : 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-500/30'
                  ]"
                >
                  <i :class="['text-[9px]', matchedUser.role === 'admin' ? 'pi pi-shield' : matchedUser.role === 'teacher' ? 'pi pi-briefcase' : 'pi pi-graduation-cap']"></i>
                  <span>{{ matchedUser.role === 'admin' ? (currentLang === 'km' ? 'អ្នកគ្រប់គ្រង' : 'Admin') : matchedUser.role === 'teacher' ? (currentLang === 'km' ? 'គ្រូបង្រៀន' : 'Teacher') : (currentLang === 'km' ? 'និស្សិត' : 'Student') }}</span>
                </span>
              </div>
              <button
                type="button"
                @click="step = 'identifier'; form.password = ''"
                class="text-xs font-semibold text-blue-600 dark:text-sky-400 hover:underline cursor-pointer ml-2 shrink-0"
              >
                {{ currentLang === 'km' ? 'កែប្រែ' : 'Edit' }}
              </button>
            </div>

            <!-- Password Input with Eye Toggle -->
            <div class="w-full relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autofocus
                autocomplete="current-password"
                :placeholder="t('login_input_password_placeholder', 'ពាក្យសម្ងាត់')"
                class="w-full h-11 pl-3.5 pr-10 bg-white dark:bg-[#121214] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 focus:border-zinc-600 dark:focus:border-zinc-500 focus:ring-1 focus:ring-zinc-600 dark:focus:ring-zinc-500 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none transition-all duration-150 shadow-2xs"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 p-1 cursor-pointer"
              >
                <i :class="['pi text-xs', showPassword ? 'pi-eye-slash text-zinc-700 dark:text-zinc-300' : 'pi-eye']"></i>
              </button>
            </div>

            <!-- Caps Lock Alert -->
            <div v-if="capsLockOn" class="w-full bg-amber-500/10 border border-amber-500/30 rounded-xl p-2 text-amber-700 dark:text-amber-300 text-xs flex items-center gap-2">
              <i class="pi pi-exclamation-triangle text-xs"></i>
              <span class="text-[11px] font-medium">{{ t('login_caps_lock_active', 'Caps Lock is ON') }}</span>
            </div>

            <!-- Forgot Password Link -->
            <div class="flex justify-end">
              <Link href="/forgot-password" class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-300 transition-colors">
                {{ t('login_forgot_password', 'ភ្លេចពាក្យសម្ងាត់?') }}
              </Link>
            </div>

            <!-- Submit Button (Sign In) -->
            <button
              type="submit"
              :disabled="isSubmitting || form.processing || !form.password"
              class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99] disabled:opacity-50 disabled:shadow-none"
            >
              <i v-if="isSubmitting || form.processing" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ currentLang === 'km' ? 'ចូលប្រព័ន្ធ' : 'Sign in' }}</span>
            </button>

          </form>
        </div>



        <!-- ========================================================================= -->
        <!-- EMAIL OTP MODE VIEW (Optional Flow)                                       -->
        <!-- ========================================================================= -->
        <div v-else-if="authMode === 'otp'" class="w-full space-y-3 animate-fade-in">
          <div class="flex items-center justify-between pb-1 border-b border-zinc-200 dark:border-zinc-800">
            <button type="button" @click="authMode = 'password'; step = 'identifier'" class="text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-white flex items-center gap-1.5 cursor-pointer transition-colors">
              <i class="pi pi-arrow-left text-[10px]"></i>
              <span>{{ currentLang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}</span>
            </button>
            <span class="text-[11px] text-zinc-500">
              {{ otpStep === 1 ? (currentLang === 'km' ? 'ជំហានទី ១: ផ្ញើកូដ' : 'Step 1: Send OTP') : (currentLang === 'km' ? 'ជំហានទី ២: ផ្ទៀងផ្ទាត់' : 'Step 2: Verify') }}
            </span>
          </div>

          <div v-if="otpStep === 1" class="space-y-3">
            <input
              v-model="otpEmail"
              type="email"
              required
              placeholder="name@example.com"
              class="w-full h-11 px-3.5 bg-white dark:bg-[#121214] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 focus:border-zinc-600 dark:focus:border-zinc-500 focus:ring-1 focus:ring-zinc-600 dark:focus:ring-zinc-500 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none shadow-2xs"
              @keydown.enter.prevent="sendEmailOtp"
            />
            <button
              type="button"
              @click="sendEmailOtp"
              :disabled="isOtpSending || !otpEmail"
              class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer disabled:opacity-50 shadow-md shadow-blue-500/20 active:scale-[0.99]"
            >
              <i v-if="isOtpSending" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ currentLang === 'km' ? 'ផ្ញើលេខកូដ OTP ទៅ Email' : 'Send OTP to Email' }}</span>
            </button>
          </div>

          <div v-else class="space-y-3.5 animate-fade-in">
            <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-800 text-xs">
              <div class="flex items-center gap-1.5 min-w-0">
                <i class="pi pi-envelope text-xs text-blue-600 dark:text-sky-400 shrink-0"></i>
                <span class="text-zinc-500 dark:text-zinc-400">{{ currentLang === 'km' ? 'ផ្ញើទៅកាន់៖' : 'Sent to:' }}</span>
                <strong class="text-zinc-800 dark:text-zinc-200 font-mono truncate">{{ otpEmail }}</strong>
              </div>
              <button
                type="button"
                @click="otpStep = 1; clearOtpDigits()"
                class="text-xs font-semibold text-blue-600 dark:text-sky-400 hover:underline cursor-pointer ml-2 shrink-0"
              >
                {{ currentLang === 'km' ? 'កែប្រែ' : 'Edit' }}
              </button>
            </div>

            <!-- 6-digit PIN Box Grid -->
            <div class="flex items-center justify-center gap-1.5 sm:gap-2 my-2 select-none" @paste="onDigitPaste">
              <input
                ref="digitRef0"
                v-model="otpDigits[0]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(0, $event)"
                @keydown="onDigitKeydown(0, $event)"
              />
              <input
                ref="digitRef1"
                v-model="otpDigits[1]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(1, $event)"
                @keydown="onDigitKeydown(1, $event)"
              />
              <input
                ref="digitRef2"
                v-model="otpDigits[2]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(2, $event)"
                @keydown="onDigitKeydown(2, $event)"
              />
              <span class="text-zinc-400 dark:text-zinc-600 font-bold text-xs select-none px-0.5">•</span>
              <input
                ref="digitRef3"
                v-model="otpDigits[3]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(3, $event)"
                @keydown="onDigitKeydown(3, $event)"
              />
              <input
                ref="digitRef4"
                v-model="otpDigits[4]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(4, $event)"
                @keydown="onDigitKeydown(4, $event)"
              />
              <input
                ref="digitRef5"
                v-model="otpDigits[5]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(5, $event)"
                @keydown="onDigitKeydown(5, $event)"
              />
            </div>

            <div class="flex items-center justify-between text-[11px]">
              <span v-if="otpCountdown > 0" class="text-zinc-500">{{ currentLang === 'km' ? 'ផុតកំណត់:' : 'Expires in:' }} <strong class="text-amber-600 dark:text-amber-400">{{ formattedOtpTime }}</strong></span>
              <span v-else class="text-rose-500 font-bold">{{ currentLang === 'km' ? 'កូដផុតកំណត់' : 'Code expired' }}</span>
              <button type="button" @click="sendEmailOtp" :disabled="isOtpSending" class="text-blue-600 dark:text-zinc-400 hover:text-blue-700 dark:hover:text-white font-medium cursor-pointer">
                {{ currentLang === 'km' ? 'ផ្ញើម្តងទៀត' : 'Resend Code' }}
              </button>
            </div>
            <button
              type="button"
              @click="verifyEmailOtp"
              :disabled="isOtpVerifying || otpCode.length < 6"
              class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer disabled:opacity-50 shadow-md shadow-blue-500/20 active:scale-[0.99]"
            >
              <i v-if="isOtpVerifying" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ currentLang === 'km' ? 'ផ្ទៀងផ្ទាត់ និង ចូលប្រើប្រាស់' : 'Verify & Continue' }}</span>
            </button>
          </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PHONE OTP MODE VIEW (PlasGate SMS Flow)                                   -->
        <!-- ========================================================================= -->
        <div v-else-if="authMode === 'phone_otp'" class="w-full space-y-3 animate-fade-in">
          <div class="flex items-center justify-between pb-1 border-b border-zinc-200 dark:border-zinc-800">
            <button type="button" @click="authMode = 'password'; step = 'identifier'" class="text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-white flex items-center gap-1.5 cursor-pointer transition-colors">
              <i class="pi pi-arrow-left text-[10px]"></i>
              <span>{{ currentLang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}</span>
            </button>
            <span class="text-[11px] text-zinc-500">
              {{ phoneOtpStep === 1 ? (currentLang === 'km' ? 'ជំហានទី ១: ផ្ញើកូដ SMS' : 'Step 1: Send SMS') : (currentLang === 'km' ? 'ជំហានទី ២: ផ្ទៀងផ្ទាត់' : 'Step 2: Verify') }}
            </span>
          </div>

          <div v-if="phoneOtpStep === 1" class="space-y-3">
            <div class="relative w-full">
              <div class="absolute left-3.5 top-1/2 -translate-y-1/2 flex items-center gap-1.5 pointer-events-none text-xs font-bold text-zinc-600 dark:text-zinc-400">
                <span class="text-sm">🇰🇭</span>
                <span>+855</span>
                <span class="text-zinc-300 dark:text-zinc-700">|</span>
              </div>
              <input
                v-model="otpPhone"
                type="tel"
                required
                placeholder="12 345 678"
                class="w-full h-11 pl-20 pr-3.5 bg-white dark:bg-[#121214] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 focus:border-zinc-600 dark:focus:border-zinc-500 focus:ring-1 focus:ring-zinc-600 dark:focus:ring-zinc-500 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none shadow-2xs font-mono"
                @keydown.enter.prevent="sendPhoneOtp"
              />
            </div>
            <button
              type="button"
              @click="sendPhoneOtp"
              :disabled="isPhoneOtpSending || !otpPhone"
              class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer disabled:opacity-50 shadow-md shadow-blue-500/20 active:scale-[0.99]"
            >
              <i v-if="isPhoneOtpSending" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ isPhoneOtpSending ? (currentLang === 'km' ? 'កំពុងផ្ញើសារទៅកាន់ទូរស័ព្ទរបស់អ្នក...' : 'Sending message to your phone...') : (currentLang === 'km' ? 'ផ្ញើលេខកូដ OTP តាម SMS' : 'Send OTP via SMS') }}</span>
            </button>
          </div>

          <div v-else class="space-y-3.5 animate-fade-in">
            <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-800 text-xs">
              <div class="flex items-center gap-1.5 min-w-0">
                <i class="pi pi-phone text-xs text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                <span class="text-zinc-500 dark:text-zinc-400">{{ currentLang === 'km' ? 'ផ្ញើទៅកាន់៖' : 'Sent to:' }}</span>
                <strong class="text-zinc-800 dark:text-zinc-200 font-mono truncate">{{ formattedDisplayPhone }}</strong>
              </div>
              <button
                type="button"
                @click="phoneOtpStep = 1; clearOtpDigits()"
                class="text-xs font-semibold text-blue-600 dark:text-sky-400 hover:underline cursor-pointer ml-2 shrink-0"
              >
                {{ currentLang === 'km' ? 'កែប្រែ' : 'Edit' }}
              </button>
            </div>

            <!-- 6-digit Segmented PIN Input -->
            <div class="flex items-center justify-center gap-1.5 sm:gap-2 my-2 select-none" @paste="onDigitPaste">
              <input
                ref="digitRef0"
                v-model="otpDigits[0]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(0, $event)"
                @keydown="onDigitKeydown(0, $event)"
              />
              <input
                ref="digitRef1"
                v-model="otpDigits[1]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(1, $event)"
                @keydown="onDigitKeydown(1, $event)"
              />
              <input
                ref="digitRef2"
                v-model="otpDigits[2]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(2, $event)"
                @keydown="onDigitKeydown(2, $event)"
              />
              <span class="text-zinc-400 dark:text-zinc-600 font-bold text-xs select-none px-0.5">•</span>
              <input
                ref="digitRef3"
                v-model="otpDigits[3]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(3, $event)"
                @keydown="onDigitKeydown(3, $event)"
              />
              <input
                ref="digitRef4"
                v-model="otpDigits[4]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(4, $event)"
                @keydown="onDigitKeydown(4, $event)"
              />
              <input
                ref="digitRef5"
                v-model="otpDigits[5]"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="1"
                placeholder="-"
                class="w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105 outline-none shadow-xs transition-all duration-150"
                @input="onDigitInput(5, $event)"
                @keydown="onDigitKeydown(5, $event)"
              />
            </div>

            <div class="flex items-center justify-between text-[11px]">
              <span v-if="phoneOtpCountdown > 0" class="text-zinc-500">{{ currentLang === 'km' ? 'ផុតកំណត់:' : 'Expires in:' }} <strong class="text-amber-600 dark:text-amber-400">{{ formattedPhoneOtpTime }}</strong></span>
              <span v-else class="text-rose-500 font-bold">{{ currentLang === 'km' ? 'កូដផុតកំណត់' : 'Code expired' }}</span>
              <button type="button" @click="sendPhoneOtp" :disabled="isPhoneOtpSending" class="text-blue-600 dark:text-zinc-400 hover:text-blue-700 dark:hover:text-white font-medium cursor-pointer">
                {{ currentLang === 'km' ? 'ផ្ញើម្តងទៀត' : 'Resend Code' }}
              </button>
            </div>
            <button
              type="button"
              @click="verifyPhoneOtp"
              :disabled="isPhoneOtpVerifying || otpCode.length < 6"
              class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-150 cursor-pointer disabled:opacity-50 shadow-md shadow-blue-500/20 active:scale-[0.99]"
            >
              <i v-if="isPhoneOtpVerifying" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ currentLang === 'km' ? 'ផ្ទៀងផ្ទាត់ និង ចូលប្រើប្រាស់' : 'Verify & Continue' }}</span>
            </button>
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

      </div>

      <!-- Loading / Authenticating Overlay -->
      <div v-else class="w-full max-w-sm flex flex-col items-center justify-center text-center animate-fade-in py-6">
        <div class="w-12 h-12 rounded-full border-2 border-zinc-300 dark:border-zinc-800 border-t-zinc-900 dark:border-t-white animate-spin mb-4"></div>
        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-wide mb-1">
          {{ authLoadingTitle || (currentLang === 'km' ? 'កំពុងរៀបចំផ្ទាំងគ្រប់គ្រង...' : 'Setting up your dashboard...') }}
        </h3>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-xs leading-relaxed">
          {{ authLoadingSubtitle || (currentLang === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងដំណើរការផ្ទៀងផ្ទាត់...' : 'Please wait a moment while verifying your account...') }}
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
        <div class="max-w-xs w-full bg-white dark:bg-[#121214] border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-2xl text-center flex flex-col items-center space-y-3">
          <div class="w-12 h-12 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <i class="pi pi-check text-lg font-bold"></i>
          </div>
          <h3 class="text-sm font-bold text-zinc-900 dark:text-white">
            {{ statusMessage ? (currentLang === 'km' ? 'ជូនដំណឹង' : 'Notice') : (currentLang === 'km' ? 'ចូលប្រព័ន្ធជោគជ័យ' : 'Sign In Successful') }}
          </h3>
          <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
            {{ statusMessage || (currentLang === 'km' ? 'កំពុងបញ្ជូនទៅកាន់ផ្ទាំងគ្រប់គ្រង...' : 'Redirecting to your dashboard...') }}
          </p>
          <button
            type="button"
            @click="showSuccessModal = false"
            class="w-full py-2.5 px-4 rounded-xl bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:hover:bg-zinc-100 text-white dark:text-zinc-950 text-xs font-semibold cursor-pointer shadow-xs transition-colors"
          >
            {{ currentLang === 'km' ? 'យល់ព្រម' : 'Got it' }}
          </button>
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
        <div class="max-w-xs w-full bg-white dark:bg-[#121214] border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-2xl text-center flex flex-col items-center space-y-3">
          <div class="w-12 h-12 rounded-full bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center">
            <i class="pi pi-exclamation-triangle text-lg font-bold"></i>
          </div>
          <h3 class="text-sm font-bold text-zinc-900 dark:text-white">
            {{ currentLang === 'km' ? 'ការផ្ទៀងផ្ទាត់មិនជោគជ័យ' : 'Authentication Failed' }}
          </h3>
          <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
            {{ errorMessage || (currentLang === 'km' ? 'សូមពិនិត្យមើលអាសយដ្ឋានអ៊ីមែល ឬពាក្យសម្ងាត់របស់អ្នកឡើងវិញ ហើយព្យាយាមម្តងទៀត។' : 'Please check your email or password and try again.') }}
          </p>
          <button
            type="button"
            @click="showErrorModal = false"
            class="w-full py-2.5 px-4 rounded-xl bg-zinc-900 dark:bg-zinc-800 hover:bg-zinc-800 dark:hover:bg-zinc-700 text-white text-xs font-semibold cursor-pointer transition-colors shadow-xs"
          >
            {{ currentLang === 'km' ? 'បិទ' : 'Close' }}
          </button>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.98) translateY(4px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}

.turnstile-wrapper,
.turnstile-wrapper > div,
.turnstile-wrapper iframe {
  width: 100% !important;
  min-width: 100% !important;
  max-width: 100% !important;
  display: block !important;
  margin: 0 auto !important;
}
</style>
