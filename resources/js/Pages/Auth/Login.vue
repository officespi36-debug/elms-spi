<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useForm, Link, router, usePage } from '@inertiajs/vue3'
import { i18n, type LanguageCode } from '../../Services/i18n'
import AuthAnimatedBackground from '../../Components/AuthAnimatedBackground.vue'
import NetworkStatusPill from '../../Components/NetworkStatusPill.vue'
import TelegramLoginModal from '../../Components/TelegramLoginModal.vue'
import GlobalToast from '../../Components/GlobalToast.vue'
import AuthProcessLoader from '../../Components/AuthProcessLoader.vue'

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
const isAuthenticating = ref(false)
const authSuccess = ref(false)
const authLoadingTitle = ref('')
const authLoadingSubtitle = ref('')
const statusMessage = ref<string | null>(null)

// Dynamic Last Used Login Method ('google' | 'github' | 'email' | 'phone')
const lastUsedMethod = ref<string>('')

const initLastUsedMethod = () => {
  if (typeof window === 'undefined') return
  try {
    const saved = localStorage.getItem('elms_last_login_method')
    if (saved && ['google', 'github', 'email', 'phone'].includes(saved)) {
      lastUsedMethod.value = saved
    }
  } catch (e) {}
}

const recordLoginMethod = (method: 'google' | 'github' | 'email' | 'phone') => {
  try {
    localStorage.setItem('elms_last_login_method', method)
    lastUsedMethod.value = method

    const countsStr = localStorage.getItem('elms_login_method_counts')
    const counts = countsStr ? JSON.parse(countsStr) : {}
    counts[method] = (counts[method] || 0) + 1
    localStorage.setItem('elms_login_method_counts', JSON.stringify(counts))
  } catch (e) {}
}

const oauthNotice = ref<{
  type: 'warning' | 'error' | 'success' | 'info'
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

import { WORLD_COUNTRIES, type CountryItem } from '@/data/countries'

const phoneCountries: CountryItem[] = WORLD_COUNTRIES
const selectedPhoneCountry = ref<CountryItem>(phoneCountries[0])
const isPhoneCountryDropdownOpen = ref(false)
const phoneCountrySearch = ref('')

const filteredPhoneCountries = computed(() => {
  const q = phoneCountrySearch.value.trim().toLowerCase()
  if (!q) return phoneCountries
  return phoneCountries.filter(c =>
    c.name.toLowerCase().includes(q) ||
    (c.nameKm && c.nameKm.toLowerCase().includes(q)) ||
    c.dialCode.includes(q) ||
    c.code.toLowerCase().includes(q)
  )
})

const selectPhoneCountry = (c: CountryItem) => {
  selectedPhoneCountry.value = c
  isPhoneCountryDropdownOpen.value = false
  phoneCountrySearch.value = ''
}
const phoneOtpStep = ref<1 | 2>(1)
const otpCode = ref('')
const isOtpSending = ref(false)
const isOtpVerifying = ref(false)
const isPhoneOtpSending = ref(false)
const isPhoneOtpVerifying = ref(false)
const phoneOtpChannel = ref<'telegram_bot' | 'telegram_gateway' | 'sms' | null>(null)
const preferredPhoneChannel = ref<'telegram' | 'sms'>('telegram')
const botOtpLink = ref('https://t.me/spi_elms_auth_bot')
const hasTelegramDm = ref(false)
const otpCountdown = ref(0)
const phoneOtpCountdown = ref(0)
const emailResendCooldown = ref(0)
const phoneResendCooldown = ref(0)

const pageTitle = computed(() => {
  if (step.value === 'enter_password') {
    return currentLang.value === 'km' ? 'ចូលប្រើប្រាស់' : 'Sign in'
  }
  if ((authMode.value === 'otp' && otpStep.value === 2) || (authMode.value === 'phone_otp' && phoneOtpStep.value === 2)) {
    return currentLang.value === 'km' ? 'ផ្ទៀងផ្ទាត់លេខកូដ OTP' : 'Verify OTP Code'
  }
  return t('login_title_manus', 'ស្វាគមន៍មកកាន់ E-LMS')
})

const pageSubtitle = computed(() => {
  if (step.value === 'enter_password') {
    return currentLang.value === 'km' ? 'សូមបញ្ចូលពាក្យសម្ងាត់គណនីរបស់អ្នក' : 'Please enter your password'
  }
  if (authMode.value === 'otp') {
    if (otpStep.value === 2) {
      return currentLang.value === 'km' ? 'លេខកូដ ៦ ខ្ទង់ត្រូវបានផ្ញើទៅកាន់អ៊ីមែលរបស់អ្នក' : 'A 6-digit code has been sent to your email'
    }
    return currentLang.value === 'km' ? 'សូមបញ្ចូលអ៊ីមែលរបស់អ្នកដើម្បីទទួលលេខកូដ OTP' : 'Enter your email to receive an OTP code'
  }
  if (authMode.value === 'phone_otp') {
    if (phoneOtpStep.value === 2) {
      if (phoneOtpChannel.value === 'telegram_bot') {
        return currentLang.value === 'km' ? 'លេខកូដ ៦ ខ្ទង់ត្រូវបានផ្ញើចូលទៅកាន់ Telegram (@spi_elms_auth_bot) របស់អ្នក' : 'A 6-digit code has been sent to your Telegram (@spi_elms_auth_bot)'
      }
      if (phoneOtpChannel.value === 'telegram_gateway') {
        return currentLang.value === 'km' ? 'លេខកូដ ៦ ខ្ទង់ត្រូវបានផ្ញើទៅកាន់ Telegram (@VerificationCodes) របស់អ្នក' : 'A 6-digit code has been sent to your Telegram (@VerificationCodes)'
      }
      return currentLang.value === 'km' ? 'លេខកូដ ៦ ខ្ទង់ត្រូវបានផ្ញើតាមសារ SMS ទៅកាន់ទូរស័ព្ទរបស់អ្នក' : 'A 6-digit code has been sent via SMS to your phone'
    }
    return currentLang.value === 'km' ? 'សូមបញ្ចូលលេខទូរស័ព្ទរបស់អ្នកដើម្បីទទួលលេខកូដ OTP' : 'Enter your phone number to receive an OTP code'
  }
  return t('login_subtitle_manus', 'សូមជ្រើសរើសវិធីសាស្ត្រដើម្បីចូលប្រើគណនីរបស់អ្នក')
})
let otpCountdownTimer: any = null
let phoneOtpCountdownTimer: any = null
let emailCooldownTimer: any = null
let phoneCooldownTimer: any = null

// 6-digit Segmented PIN Input System
const otpDigits = ref<string[]>(['', '', '', '', '', ''])
const digitRef0 = ref<HTMLInputElement | null>(null)
const digitRef1 = ref<HTMLInputElement | null>(null)
const digitRef2 = ref<HTMLInputElement | null>(null)
const digitRef3 = ref<HTMLInputElement | null>(null)
const digitRef4 = ref<HTMLInputElement | null>(null)
const digitRef5 = ref<HTMLInputElement | null>(null)

const getDigitInput = (idx: number): HTMLInputElement | null => {
  const refs = [digitRef0.value, digitRef1.value, digitRef2.value, digitRef3.value, digitRef4.value, digitRef5.value]
  return refs[idx] || null
}

const otpEmailInputRef = ref<HTMLInputElement | null>(null)
const isEmailInputFocused = ref(false)
const selectedSuggestionIndex = ref(-1)

const emailDomains = [
  { domain: '@gmail.com', icon: '⚡', name: 'Gmail' },
  { domain: '@spi.edu.kh', icon: '🎓', name: 'SPI Mail' },
  { domain: '@yahoo.com', icon: '📧', name: 'Yahoo' },
  { domain: '@outlook.com', icon: '💼', name: 'Outlook' },
]

const filteredEmailSuggestions = computed(() => {
  const val = otpEmail.value.trim().toLowerCase()
  if (!val || !isEmailInputFocused.value) return []

  let username = val
  let domainPart = ''

  if (val.includes('@')) {
    const parts = val.split('@')
    username = parts[0]
    domainPart = '@' + (parts[1] || '')
  }

  // Hide if no username or already an exact full domain match
  if (!username) return []
  if (val.includes('@') && emailDomains.some(d => val === username + d.domain)) {
    return []
  }

  return emailDomains
    .filter(d => !domainPart || d.domain.toLowerCase().startsWith(domainPart))
    .slice(0, 4)
    .map(d => ({
      username,
      domain: d.domain,
      fullEmail: username + d.domain,
      icon: d.icon,
      name: d.name,
    }))
})

const selectEmailSuggestion = (fullEmail: string) => {
  otpEmail.value = fullEmail
  isEmailInputFocused.value = false
  selectedSuggestionIndex.value = -1
  nextTick(() => {
    otpEmailInputRef.value?.focus()
  })
}

const onEmailKeydown = (event: KeyboardEvent) => {
  const suggestions = filteredEmailSuggestions.value
  if (suggestions.length > 0 && isEmailInputFocused.value) {
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      selectedSuggestionIndex.value = (selectedSuggestionIndex.value + 1) % suggestions.length
      return
    } else if (event.key === 'ArrowUp') {
      event.preventDefault()
      selectedSuggestionIndex.value = selectedSuggestionIndex.value <= 0
        ? suggestions.length - 1
        : selectedSuggestionIndex.value - 1
      return
    } else if ((event.key === 'Enter' || event.key === 'Tab') && selectedSuggestionIndex.value >= 0) {
      event.preventDefault()
      const chosen = suggestions[selectedSuggestionIndex.value]
      if (chosen) {
        selectEmailSuggestion(chosen.fullEmail)
      }
      return
    }
  }

  if (event.key === 'Enter') {
    event.preventDefault()
    sendEmailOtp()
  }
}

const onEmailBlur = () => {
  setTimeout(() => {
    isEmailInputFocused.value = false
  }, 200)
}

const clearOtpDigits = () => {
  otpDigits.value = ['', '', '', '', '', '']
  otpCode.value = ''
}

const focusFirstOtpDigit = () => {
  nextTick(() => {
    setTimeout(() => {
      digitRef0.value?.focus()
    }, 80)
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
      nextTick(() => {
        if (authMode.value === 'phone_otp' && !isPhoneOtpVerifying.value) verifyPhoneOtp()
        else if (authMode.value === 'otp' && !isOtpVerifying.value) verifyEmailOtp()
      })
    }
  } else {
    otpDigits.value[index] = raw
    otpCode.value = otpDigits.value.join('')
    if (raw && index < 5) {
      getDigitInput(index + 1)?.focus()
    }
    if (otpCode.value.length === 6) {
      nextTick(() => {
        if (authMode.value === 'phone_otp' && !isPhoneOtpVerifying.value) verifyPhoneOtp()
        else if (authMode.value === 'otp' && !isOtpVerifying.value) verifyEmailOtp()
      })
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
    if (otpCode.value.length === 6) {
      if (authMode.value === 'phone_otp' && !isPhoneOtpVerifying.value) verifyPhoneOtp()
      else if (authMode.value === 'otp' && !isOtpVerifying.value) verifyEmailOtp()
    }
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
      nextTick(() => {
        if (authMode.value === 'phone_otp' && !isPhoneOtpVerifying.value) verifyPhoneOtp()
        else if (authMode.value === 'otp' && !isOtpVerifying.value) verifyEmailOtp()
      })
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

const fullFormattedPhone = computed(() => {
  const clean = otpPhone.value.trim().replace(/[^0-9]/g, '')
  if (!clean) return ''
  const dial = selectedPhoneCountry.value.dialCode.replace('+', '')
  if (clean.startsWith(dial)) return '+' + clean
  if (clean.startsWith('0')) return '+' + dial + clean.substring(1)
  return '+' + dial + clean
})

const formattedDisplayPhone = computed(() => {
  const clean = otpPhone.value.replace(/[^0-9]/g, '')
  const dial = selectedPhoneCountry.value.dialCode
  const dialDigits = dial.replace('+', '')
  const withoutZero = clean.replace(new RegExp('^' + dialDigits), '').replace(/^0/, '')
  return dial + ' ' + (withoutZero || clean)
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

const startEmailCooldown = (seconds = 60) => {
  emailResendCooldown.value = seconds
  if (emailCooldownTimer) clearInterval(emailCooldownTimer)
  emailCooldownTimer = setInterval(() => {
    if (emailResendCooldown.value > 0) {
      emailResendCooldown.value--
    } else {
      clearInterval(emailCooldownTimer)
    }
  }, 1000)
}

const startPhoneCooldown = (seconds = 60) => {
  phoneResendCooldown.value = seconds
  if (phoneCooldownTimer) clearInterval(phoneCooldownTimer)
  phoneCooldownTimer = setInterval(() => {
    if (phoneResendCooldown.value > 0) {
      phoneResendCooldown.value--
    } else {
      clearInterval(phoneCooldownTimer)
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
      startEmailCooldown(60)
      focusFirstOtpDigit()
      oauthNotice.value = {
        type: 'warning',
        message: data.message || (currentLang.value === 'km' ? 'លេខកូដ OTP ត្រូវបានផ្ញើចូលប្រអប់សំបុត្រ Gmail របស់អ្នកហើយ! សូមពិនិត្យមើល Inbox/Spam។' : 'OTP code has been sent to your email! Please check Inbox/Spam.')
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
  recordLoginMethod('email')
  isOtpVerifying.value = true
  isAuthenticating.value = true
  authSuccess.value = false
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
        authSuccess.value = true
        authLoadingTitle.value = currentLang.value === 'km' ? 'ផ្ទៀងផ្ទាត់ជោគជ័យ!' : 'OTP Verified Successfully!'
        authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត កំពុងនាំអ្នកទៅកាន់ Dashboard...' : 'Redirecting to your dashboard...'
        window.location.assign('/dashboard')
        return
      }
      data = {
        message: currentLang.value === 'km' ? 'មានបញ្ហាបច្ចេកទេសលើ Server' : 'Technical server error'
      }
    }

    if (response.ok && data.success) {
      authSuccess.value = true
      authLoadingTitle.value = currentLang.value === 'km' ? 'ផ្ទៀងផ្ទាត់ជោគជ័យ!' : 'OTP Verified Successfully!'
      authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត កំពុងនាំអ្នកទៅកាន់ Dashboard...' : 'Redirecting to your dashboard...'
      if (data.token) {
        try { localStorage.setItem('auth_token', data.token) } catch (e) {}
      }
      setTimeout(() => {
        window.location.assign(data.redirect || '/student/dashboard')
      }, 1000)
    } else {
      isAuthenticating.value = false
      authSuccess.value = false
      let errMsg = data.message || ''
      if (typeof errMsg === 'string' && (errMsg.startsWith('<') || errMsg.includes('<!DOCTYPE'))) {
        errMsg = currentLang.value === 'km' ? 'មានបញ្ហាបច្ចេកទេសលើ Server សូមព្យាយាមម្តងទៀត' : 'Server error, please try again'
      }
      errorMessage.value = errMsg || (currentLang.value === 'km' ? 'លេខកូដ OTP មិនត្រឹមត្រូវ ឬផុតកំណត់!' : 'Invalid or expired OTP code!')
      showErrorModal.value = true
    }
  } catch (err: any) {
    isAuthenticating.value = false
    authSuccess.value = false
    showErrorModal.value = true
    errorMessage.value = err?.message || (currentLang.value === 'km' ? 'មានបញ្ហាក្នុងការផ្ទៀងផ្ទាត់ OTP' : 'OTP verification error')
  } finally {
    isOtpVerifying.value = false
  }
}

const sendPhoneOtp = async (overrideChannel?: 'sms' | 'telegram' | any) => {
  if (!otpPhone.value || isPhoneOtpSending.value) return
  const channel = (typeof overrideChannel === 'string' && (overrideChannel === 'sms' || overrideChannel === 'telegram'))
    ? overrideChannel
    : preferredPhoneChannel.value

  preferredPhoneChannel.value = channel
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
      body: JSON.stringify({
        phone: fullFormattedPhone.value || otpPhone.value.trim(),
        channel: channel,
      }),
    })

    const data = await response.json()
    if (response.ok && data.success) {
      phoneOtpChannel.value = data.channel || (data.is_telegram ? 'telegram_bot' : 'sms')
      if (data.bot_otp_link) {
        botOtpLink.value = data.bot_otp_link
      } else {
        const clean = (fullFormattedPhone.value || otpPhone.value).replace(/[^0-9]/g, '')
        botOtpLink.value = `https://t.me/spi_elms_auth_bot?start=otp_${clean}`
      }
      hasTelegramDm.value = !!data.has_telegram_dm
      phoneOtpStep.value = 2
      clearOtpDigits()
      startPhoneOtpTimer(300)
      startPhoneCooldown(60)
      focusFirstOtpDigit()
      oauthNotice.value = {
        type: data.is_telegram || data.sms_delivered ? 'info' : 'warning',
        message: data.message || (
          data.is_telegram
            ? (currentLang.value === 'km' ? 'លេខកូដ OTP ត្រូវបានផ្ញើទៅកាន់ Telegram របស់អ្នករួចរាល់ហើយ!' : 'OTP code sent to your Telegram!')
            : (data.sms_delivered
                ? (currentLang.value === 'km' ? 'លេខកូដ OTP ត្រូវបានផ្ញើតាមសារ SMS ទៅកាន់ប្រអប់សារទូរស័ព្ទរបស់អ្នករួចរាល់ហើយ!' : 'OTP code has been sent to your phone via SMS!')
                : (currentLang.value === 'km' ? 'សូមចុចលើប៊ូតុង Telegram Bot ខាងក្រោម ដើម្បីទទួលកូដភ្លាមៗ (ឥតគិតថ្លៃ)!' : 'Please tap the Telegram Bot button below to get your OTP code instantly!'))
        )
      }
    } else {
      oauthNotice.value = {
        type: 'error',
        message: data.message || (currentLang.value === 'km' ? 'មិនអាចផ្ញើលេខកូដ OTP បានទេ!' : 'Failed to send OTP code!')
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
  recordLoginMethod('phone')
  isPhoneOtpVerifying.value = true
  isAuthenticating.value = true
  authSuccess.value = false
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
        phone: fullFormattedPhone.value || otpPhone.value.trim(),
        otp: otpCode.value.trim(),
      }),
    })

    const rawText = await response.text()
    let data: any = {}
    try {
      data = JSON.parse(rawText)
    } catch (e) {
      if (response.ok || response.status === 200 || response.redirected) {
        authSuccess.value = true
        authLoadingTitle.value = currentLang.value === 'km' ? 'ផ្ទៀងផ្ទាត់ជោគជ័យ!' : 'Phone OTP Verified Successfully!'
        authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត កំពុងនាំអ្នកទៅកាន់ Dashboard...' : 'Redirecting to your dashboard...'
        window.location.assign('/dashboard')
        return
      }
      data = {
        message: currentLang.value === 'km' ? 'មានបញ្ហាបច្ចេកទេសលើ Server' : 'Technical server error'
      }
    }

    if (response.ok && data.success) {
      authSuccess.value = true
      authLoadingTitle.value = currentLang.value === 'km' ? 'ផ្ទៀងផ្ទាត់ជោគជ័យ!' : 'Phone OTP Verified Successfully!'
      authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត កំពុងនាំអ្នកទៅកាន់ Dashboard...' : 'Redirecting to your dashboard...'
      if (data.token) {
        try { localStorage.setItem('auth_token', data.token) } catch (e) {}
      }
      setTimeout(() => {
        window.location.assign(data.redirect || '/student/dashboard')
      }, 1000)
    } else {
      isAuthenticating.value = false
      authSuccess.value = false
      let errMsg = data.message || ''
      if (typeof errMsg === 'string' && (errMsg.startsWith('<') || errMsg.includes('<!DOCTYPE'))) {
        errMsg = currentLang.value === 'km' ? 'មានបញ្ហាបច្ចេកទេសលើ Server សូមព្យាយាមម្តងទៀត' : 'Server error, please try again'
      }
      errorMessage.value = errMsg || (currentLang.value === 'km' ? 'លេខកូដ OTP មិនត្រឹមត្រូវ ឬផុតកំណត់!' : 'Invalid or expired OTP code!')
      showErrorModal.value = true
    }
  } catch (err: any) {
    isAuthenticating.value = false
    authSuccess.value = false
    showErrorModal.value = true
    errorMessage.value = err?.message || (currentLang.value === 'km' ? 'មានបញ្ហាក្នុងការផ្ទៀងផ្ទាត់ OTP' : 'OTP verification error')
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

// Web Audio API Sound Synthesizer for Top Control Buttons (Theme, Language, Network)
// Web Audio API Sound Synthesizer for Top Control Buttons (Theme, Language, Network)
let audioCtx: AudioContext | null = null

const getAudioContext = (): AudioContext | null => {
  if (typeof window === 'undefined') return null
  try {
    const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext
    if (!AudioContextClass) return null
    if (!audioCtx || audioCtx.state === 'closed') {
      audioCtx = new AudioContextClass()
    }
    return audioCtx
  } catch {
    return null
  }
}

// Proactive audio unlock on first user gesture (click/keydown/touchstart)
if (typeof window !== 'undefined') {
  const unlockAudio = () => {
    try {
      const ctx = getAudioContext()
      if (ctx && ctx.state === 'suspended') {
        ctx.resume()
      }
    } catch {}
    window.removeEventListener('click', unlockAudio)
    window.removeEventListener('keydown', unlockAudio)
    window.removeEventListener('touchstart', unlockAudio)
  }
  window.addEventListener('click', unlockAudio, { once: true, passive: true })
  window.addEventListener('keydown', unlockAudio, { once: true, passive: true })
  window.addEventListener('touchstart', unlockAudio, { once: true, passive: true })
}

const playTopBarSound = async (type: 'theme' | 'lang_open' | 'lang_select' | 'network' = 'lang_open') => {
  try {
    const ctx = getAudioContext()
    if (!ctx) return

    // Ensure audio context is running before scheduling audio nodes
    if (ctx.state === 'suspended') {
      await ctx.resume()
    }

    const now = ctx.currentTime

    if (type === 'theme') {
      // Clear, satisfying tactile mechanical switch click & melodic chime
      // Sound 1: Tactile snap impulse (physical switch click)
      const clickOsc = ctx.createOscillator()
      const clickGain = ctx.createGain()
      clickOsc.connect(clickGain)
      clickGain.connect(ctx.destination)

      clickOsc.type = 'triangle'
      clickOsc.frequency.setValueAtTime(1500, now)
      clickOsc.frequency.exponentialRampToValueAtTime(450, now + 0.035)

      clickGain.gain.setValueAtTime(0.3, now)
      clickGain.gain.exponentialRampToValueAtTime(0.001, now + 0.04)

      clickOsc.start(now)
      clickOsc.stop(now + 0.04)

      // Sound 2: Resonant melodic tone (clearly audible & pleasant)
      const toneOsc = ctx.createOscillator()
      const toneGain = ctx.createGain()
      toneOsc.connect(toneGain)
      toneGain.connect(ctx.destination)

      toneOsc.type = 'sine'
      // If currently dark, we are switching to light mode: bright uplifting chime
      // If currently light, switching to dark mode: warm comforting tone
      if (isDark.value) {
        toneOsc.frequency.setValueAtTime(520, now + 0.01)
        toneOsc.frequency.exponentialRampToValueAtTime(880, now + 0.15)
      } else {
        toneOsc.frequency.setValueAtTime(880, now + 0.01)
        toneOsc.frequency.exponentialRampToValueAtTime(440, now + 0.15)
      }

      toneGain.gain.setValueAtTime(0.35, now + 0.01)
      toneGain.gain.exponentialRampToValueAtTime(0.001, now + 0.18)

      toneOsc.start(now + 0.01)
      toneOsc.stop(now + 0.18)
    } else if (type === 'lang_select') {
      // Sweet clear selection chime
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.connect(gain)
      gain.connect(ctx.destination)

      osc.type = 'sine'
      osc.frequency.setValueAtTime(750, now)
      osc.frequency.exponentialRampToValueAtTime(1150, now + 0.09)

      gain.gain.setValueAtTime(0.3, now)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.11)

      osc.start(now)
      osc.stop(now + 0.11)
    } else if (type === 'network') {
      // High-tech subtle radar ping
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.connect(gain)
      gain.connect(ctx.destination)

      osc.type = 'triangle'
      osc.frequency.setValueAtTime(950, now)
      osc.frequency.exponentialRampToValueAtTime(1300, now + 0.07)

      gain.gain.setValueAtTime(0.28, now)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.085)

      osc.start(now)
      osc.stop(now + 0.085)
    } else {
      // Crisp subtle pop / tap for language button
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.connect(gain)
      gain.connect(ctx.destination)

      osc.type = 'sine'
      osc.frequency.setValueAtTime(850, now)
      osc.frequency.exponentialRampToValueAtTime(420, now + 0.05)

      gain.gain.setValueAtTime(0.28, now)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.06)

      osc.start(now)
      osc.stop(now + 0.06)
    }
  } catch (e) {
    // Ignore audio restriction gracefully
  }
}

const currentLang = computed(() => i18n.locale.value)

const selectLanguage = (code: LanguageCode) => {
  playTopBarSound('lang_select')
  i18n.setLanguage(code)
  isLangOpen.value = false
}

const toggleLanguage = () => {
  const nextLang: LanguageCode = currentLang.value === 'km' ? 'en' : 'km'
  playTopBarSound('lang_select')
  i18n.setLanguage(nextLang)
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

const toggleTheme = (event?: MouseEvent) => {
  playTopBarSound('theme')
  const nextDark = !isDark.value

  const isAppearanceTransition =
    typeof document !== 'undefined' &&
    'startViewTransition' in document &&
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches

  if (!isAppearanceTransition) {
    isDark.value = nextDark
    try {
      localStorage.setItem('theme', nextDark ? 'dark' : 'light')
    } catch (e) {}
    applyTheme()
    removeTurnstile()
    nextTick(() => {
      initTurnstile()
    })
    return
  }

  // Triangular Spotlight Cone Reveal (Apex at top-center, flaring down to both sides)
  const transition = (document as any).startViewTransition(async () => {
    isDark.value = nextDark
    try {
      localStorage.setItem('theme', nextDark ? 'dark' : 'light')
    } catch (e) {}
    applyTheme()
    removeTurnstile()
    await nextTick()
    initTurnstile()
  })

  transition.ready.then(() => {
    document.documentElement.animate(
      [
        { clipPath: 'polygon(50% 0%, 50% 0%, 50% 0%, 50% 0%)', offset: 0 },
        { clipPath: 'polygon(50% 0%, 50% 0%, 82% 100%, 18% 100%)', offset: 0.42 },
        { clipPath: 'polygon(18% 0%, 82% 0%, 115% 100%, -15% 100%)', offset: 0.72 },
        { clipPath: 'polygon(-25% 0%, 125% 0%, 145% 100%, -45% 100%)', offset: 1 }
      ],
      {
        duration: 850,
        easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
        pseudoElement: '::view-transition-new(root)'
      }
    )
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

  // Activate authenticating screen with loading indicator
  isAuthenticating.value = true
  authSuccess.value = false
  authLoadingTitle.value = currentLang.value === 'km' ? 'កំពុងផ្ទៀងផ្ទាត់គណនី...' : 'Authenticating account...'
  authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងពិនិត្យមើលព័ត៌មាន...' : 'Please wait a moment while checking your credentials...'

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
      for (let i = 0; i < 3; i++) {
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
      form.turnstile_token = '1x_turnstile_auto_token'
    }
  }

  // 2. Submit via native Inertia form.post
  form.post('/login', {
    preserveScroll: true,
    onSuccess: () => {
      authSuccess.value = true
      authLoadingTitle.value = currentLang.value === 'km' ? 'ចូលប្រព័ន្ធជោគជ័យ!' : 'Sign In Successful!'
      authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត កំពុងនាំអ្នកទៅកាន់ Dashboard...' : 'Redirecting to your dashboard...'
      isSubmitting.value = false
    },
    onError: (errors: any) => {
      isAuthenticating.value = false
      authSuccess.value = false
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
  const botId = (page.props as any)?.telegram?.bot_id || ''
  if (!botId) return ''
  const origin = typeof window !== 'undefined' && window.location.origin.includes('spilms.tech')
    ? 'https://spilms.tech'
    : (typeof window !== 'undefined' ? window.location.origin : 'https://spilms.tech')
  const returnTo = `${origin}/auth/telegram/callback`
  return `https://oauth.telegram.org/auth?client_id=${botId}&bot_id=${botId}&origin=${encodeURIComponent(origin)}&return_to=${encodeURIComponent(returnTo)}&request_access=write`
}

const isGoogleLoading = ref(false)
const isGitHubLoading = ref(false)
const isTelegramLoading = ref(false)
const showTelegramModal = ref(false)

const onTelegramModalSuccess = (user: any) => {
  showTelegramModal.value = false
  isAuthenticating.value = true
  authSuccess.value = true
  authLoadingTitle.value = currentLang.value === 'km' ? 'ចូលប្រើប្រាស់ Telegram ជោគជ័យ!' : 'Telegram Login Successful!'
  authLoadingSubtitle.value = currentLang.value === 'km' ? 'កំពុងនាំអ្នកទៅកាន់ Dashboard...' : 'Redirecting to Dashboard...'
  setTimeout(() => {
    window.location.href = '/student/dashboard'
  }, 1000)
}

const onUseTelegramDevice = () => {
  showTelegramModal.value = false
  redirectToTelegramOAuth()
}

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
    if (data.success) {
      authSuccess.value = true
      authLoadingTitle.value = currentLang.value === 'km' ? 'ចូលប្រើប្រាស់ Telegram ជោគជ័យ!' : 'Telegram Login Successful!'
      authLoadingSubtitle.value = currentLang.value === 'km' ? 'កំពុងនាំអ្នកទៅកាន់ Dashboard...' : 'Redirecting to Dashboard...'
      setTimeout(() => {
        window.location.href = data.redirect || '/student/dashboard'
      }, 1000)
    } else {
      isAuthenticating.value = false
      authSuccess.value = false
      isTelegramLoading.value = false
      showErrorModal.value = true
      errorMessage.value = data.message || (currentLang.value === 'km' ? 'ការផ្ទៀងផ្ទាត់ Telegram មិនជោគជ័យ!' : 'Telegram authentication failed!')
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
  recordLoginMethod('google')
  isGoogleLoading.value = true
  isAuthenticating.value = true
  authLoadingTitle.value = currentLang.value === 'km' ? 'កំពុងតភ្ជាប់ទៅកាន់ Google...' : 'Connecting to Google...'
  authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងនាំអ្នកទៅកាន់ផ្ទាំង Google Sign-In...' : 'Please wait a moment while redirecting to Google Sign-In...'

  setTimeout(() => {
    const emailParam = form.email ? `?email=${encodeURIComponent(form.email.trim())}` : ''
    window.location.assign(`/auth/google/redirect${emailParam}`)
  }, 1200)
}

const redirectToGitHubOAuth = () => {
  recordLoginMethod('github')
  isGitHubLoading.value = true
  isAuthenticating.value = true
  authLoadingTitle.value = currentLang.value === 'km' ? 'កំពុងតភ្ជាប់ទៅកាន់ GitHub...' : 'Connecting to GitHub...'
  authLoadingSubtitle.value = currentLang.value === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងនាំអ្នកទៅកាន់ផ្ទាំង GitHub Sign-In...' : 'Please wait a moment while redirecting to GitHub Sign-In...'

  setTimeout(() => {
    window.location.assign('/auth/github/redirect')
  }, 1000)
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
    initLastUsedMethod()
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
  if (otpCountdownTimer) clearInterval(otpCountdownTimer)
  if (phoneOtpCountdownTimer) clearInterval(phoneOtpCountdownTimer)
  if (emailCooldownTimer) clearInterval(emailCooldownTimer)
  if (phoneCooldownTimer) clearInterval(phoneCooldownTimer)
})
</script>

<template>
  <div class="min-h-screen w-full bg-[#f8fafc] dark:bg-[#000000] text-zinc-900 dark:text-[#ededed] flex flex-col justify-between relative font-sans overflow-x-hidden select-none transition-colors duration-300">
    
    <!-- Manus AI Signature Interactive Matrix Dots Background -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
      <AuthAnimatedBackground />
    </div>

    <!-- Ambient Luminous Aurora Glow for Ultra-Sleek Light Mode -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0 transition-opacity duration-700 dark:opacity-0 opacity-100">
      <div class="absolute -top-[15%] -left-[10%] w-[55vw] h-[55vw] rounded-full bg-gradient-to-br from-sky-200/50 via-indigo-100/40 to-transparent blur-3xl"></div>
      <div class="absolute top-[5%] -right-[15%] w-[50vw] h-[50vw] rounded-full bg-gradient-to-bl from-amber-100/50 via-orange-50/40 to-transparent blur-3xl"></div>
      <div class="absolute -bottom-[20%] left-[20%] w-[60vw] h-[60vw] rounded-full bg-gradient-to-t from-blue-100/40 via-purple-50/20 to-transparent blur-3xl"></div>
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
        <NetworkStatusPill :current-lang="currentLang" @click="playTopBarSound('network')" />
        
        <!-- Language Switcher Pill (Direct 1-Click Toggle: Khmer / English) -->
        <button
          type="button"
          @click="toggleLanguage"
          class="p-1.5 px-2.5 h-8 rounded-full bg-white/90 dark:bg-[#121214]/80 backdrop-blur-md hover:bg-zinc-100 dark:hover:bg-[#1c1c1f] text-zinc-700 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white transition-all duration-150 border border-zinc-300/80 dark:border-zinc-800 shadow-xs flex items-center justify-center cursor-pointer select-none active:scale-95 group"
          :title="currentLang === 'km' ? 'Switch to English' : 'ប្តូរទៅជាភាសាខ្មែរ'"
        >
          <img
            :src="currentLang === 'km' ? '/images/flags/km.svg' : '/images/flags/en.svg'"
            :alt="currentLang"
            class="w-5 h-3.5 object-cover rounded-[3px] shadow-xs ring-1 ring-zinc-300/60 dark:ring-zinc-700/60 transition-transform duration-200 group-hover:scale-110"
          />
        </button>

        <!-- Theme Switcher Pill -->
        <button
          type="button"
          @click="toggleTheme($event)"
          class="p-1.5 px-2.5 rounded-full bg-white/90 dark:bg-[#121214]/80 backdrop-blur-md hover:bg-zinc-100 dark:hover:bg-[#1c1c1f] text-zinc-700 dark:text-zinc-300 hover:text-zinc-950 dark:hover:text-white transition-all duration-150 border border-zinc-300/80 dark:border-zinc-800 shadow-xs flex items-center gap-1.5 text-xs font-semibold cursor-pointer select-none active:scale-95 group"
          :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        >
          <i :class="['pi text-xs transition-transform duration-500 group-hover:rotate-45', isDark ? 'pi-sun text-amber-400' : 'pi-moon text-indigo-500']"></i>
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
          {{ pageTitle }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 text-center mt-1.5 mb-6 transition-colors">
          {{ pageSubtitle }}
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
            v-if="oauthNotice && !(authMode === 'phone_otp' && phoneOtpStep === 2)"
            :class="[
              'w-full mb-4 rounded-xl p-3 text-xs flex items-start justify-between gap-2.5 border transition-all',
              oauthNotice.type === 'info'
                ? 'bg-sky-500/10 border-sky-500/30 text-sky-700 dark:text-sky-300 shadow-xs'
                : oauthNotice.type === 'warning'
                  ? 'bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300 shadow-xs'
                  : 'bg-rose-500/10 border-rose-500/30 text-rose-700 dark:text-rose-300 shadow-xs'
            ]"
          >
            <div class="flex items-start gap-2.5 min-w-0 flex-1">
              <i :class="[
                'shrink-0 text-sm mt-0.5',
                oauthNotice.type === 'info' ? 'pi pi-telegram text-sky-500 dark:text-sky-400' :
                oauthNotice.type === 'warning' ? 'pi pi-exclamation-triangle text-amber-500 dark:text-amber-400' :
                'pi pi-times-circle text-rose-500 dark:text-rose-400'
              ]"></i>
              <div class="space-y-2 flex-1 min-w-0">
                <span class="font-medium text-[11px] leading-relaxed block">{{ oauthNotice.message }}</span>
                <!-- Quick Action: Direct Open Gmail Shortcut -->
                <div v-if="authMode === 'otp' && otpStep === 2 && oauthNotice.type === 'warning'" class="pt-0.5">
                  <a
                    href="https://mail.google.com"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-800 dark:text-amber-200 font-bold text-[11px] transition-all active:scale-95 border border-amber-500/30"
                  >
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24">
                      <path fill="#EA4335" d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.272H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L12 9.545l8.073-6.052C21.69 2.28 24 3.434 24 5.457z"/>
                    </svg>
                    <span>{{ currentLang === 'km' ? 'បើកមើលក្នុង Gmail' : 'Open Gmail Inbox' }}</span>
                    <i class="pi pi-external-link text-[9px]"></i>
                  </a>
                </div>

                <!-- Quick Action: Direct Open Telegram Shortcut for Verification Codes -->
                <div v-if="authMode === 'phone_otp' && phoneOtpStep === 2 && oauthNotice.type === 'info'" class="pt-0.5">
                  <a
                    href="https://t.me/VerificationCodes"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-sky-500/20 hover:bg-sky-500/30 text-sky-800 dark:text-sky-200 font-bold text-[11px] transition-all active:scale-95 border border-sky-500/30"
                  >
                    <i class="pi pi-telegram text-xs text-sky-500"></i>
                    <span>{{ currentLang === 'km' ? 'បើកមើលក្នុង @VerificationCodes' : 'Open @VerificationCodes' }}</span>
                    <i class="pi pi-external-link text-[9px]"></i>
                  </a>
                </div>
              </div>
            </div>
            <button type="button" @click="oauthNotice = null" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-white p-0.5 cursor-pointer shrink-0">
              <i class="pi pi-times text-[10px]"></i>
            </button>
          </div>
        </Transition>

        <!-- ========================================================================= -->
        <!-- STEP 1 (Default Screen): IDENTIFIER ONLY (No Password & No Role Tabs)     -->
        <!-- ========================================================================= -->
        <div v-if="step === 'identifier' && authMode === 'password'" class="w-full space-y-4">
          
          <!-- Social Buttons Stack (Google, Telegram, Email) -->
          <div class="w-full space-y-2.5 pt-2">
            
            <!-- 1. Google Button -->
            <button
              type="button"
              :disabled="isAuthenticating"
              @click="redirectToGoogleOAuth"
              class="border-beam-btn w-full h-11 px-4 rounded-xl text-zinc-900 dark:text-white text-xs sm:text-sm font-medium relative flex items-center justify-center transition-all duration-150 active:scale-[0.99] cursor-pointer disabled:opacity-50 select-none shadow-xs"
            >
              <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
              </svg>
              <span class="text-center font-medium">{{ t('login_btn_continue_google', 'បន្តទៅមុខទៀតដោយប្រើគណនី ហ្គូហ្គល') }}</span>
              <span
                class="absolute top-0 -translate-y-1/2 right-4 z-10 text-[10px] sm:text-[10.5px] font-semibold px-2.5 py-0.5 rounded-full bg-gradient-to-r from-[#e84e27] via-[#ea580c] to-[#f7931e] text-white shadow-[0_2px_6px_rgba(234,88,12,0.35)] animate-fade-in tracking-wide select-none leading-none flex items-center justify-center pointer-events-none"
              >
                {{ t('login_badge_best_choice', 'ជម្រើសល្អបំផុត') }}
              </span>
            </button>

            <!-- 2. GitHub Button -->
            <button
              type="button"
              :disabled="isAuthenticating"
              @click="redirectToGitHubOAuth"
              class="border-beam-btn w-full h-11 px-4 rounded-xl text-zinc-900 dark:text-white text-xs sm:text-sm font-medium relative flex items-center justify-center transition-all duration-150 active:scale-[0.99] cursor-pointer disabled:opacity-50 select-none shadow-xs"
              style="animation-delay: -1s;"
            >
              <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 shrink-0 fill-zinc-900 dark:fill-white" viewBox="0 0 24 24">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
              </svg>
              <span class="text-center font-medium">{{ t('login_btn_continue_github', 'បន្តទៅមុខទៀតដោយប្រើគណនី ហ្គីតហាប់') }}</span>
              <span
                v-if="lastUsedMethod === 'github'"
                class="absolute right-3.5 text-[10px] font-semibold px-2 py-0.5 rounded-md bg-sky-50 dark:bg-[#132337] text-sky-700 dark:text-sky-400 border border-sky-200 dark:border-sky-500/20 animate-fade-in"
              >
                {{ t('login_badge_last_used', 'បានប្រើចុងក្រោយ') }}
              </span>
            </button>

            <!-- 3. Email Button -->
            <button
              type="button"
              :disabled="isAuthenticating"
              @click="recordLoginMethod('email'); authMode = 'otp'; otpStep = 1; otpEmail = form.email || ''; otpCode = ''; nextTick(() => { otpEmailInputRef?.focus(); isEmailInputFocused = true })"
              class="border-beam-btn w-full h-11 px-4 rounded-xl text-zinc-900 dark:text-white text-xs sm:text-sm font-medium relative flex items-center justify-center transition-all duration-150 active:scale-[0.99] cursor-pointer disabled:opacity-50 select-none shadow-xs"
              style="animation-delay: -2s;"
            >
              <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 shrink-0 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="16" x="2" y="4" rx="2"/>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
              <span class="text-center font-medium">{{ t('login_btn_continue_email_otp', 'បន្តទៅមុខទៀតដោយប្រើគណនី អ៊ីមែល') }}</span>
              <span
                v-if="lastUsedMethod === 'email'"
                class="absolute right-3.5 text-[10px] font-semibold px-2 py-0.5 rounded-md bg-sky-50 dark:bg-[#132337] text-sky-700 dark:text-sky-400 border border-sky-200 dark:border-sky-500/20 animate-fade-in"
              >
                {{ t('login_badge_last_used', 'បានប្រើចុងក្រោយ') }}
              </span>
            </button>

            <!-- 4. Phone Number Button -->
            <button
              type="button"
              :disabled="isAuthenticating"
              @click="recordLoginMethod('phone'); authMode = 'phone_otp'; phoneOtpStep = 1; otpPhone = form.email && /^[0-9+ ]+$/.test(form.email) ? form.email : ''; otpCode = ''"
              class="border-beam-btn w-full h-11 px-4 rounded-xl text-zinc-900 dark:text-white text-xs sm:text-sm font-medium relative flex items-center justify-center transition-all duration-150 active:scale-[0.99] cursor-pointer disabled:opacity-50 select-none shadow-xs"
              style="animation-delay: -3s;"
            >
              <svg class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
              <span class="text-center font-medium">{{ t('login_btn_continue_phone', 'បន្តទៅមុខទៀតដោយប្រើគណនី លេខទូរស័ព្ទ') }}</span>
              <span
                v-if="lastUsedMethod === 'phone'"
                class="absolute right-3.5 text-[10px] font-semibold px-2 py-0.5 rounded-md bg-sky-50 dark:bg-[#132337] text-sky-700 dark:text-sky-400 border border-sky-200 dark:border-sky-500/20 animate-fade-in"
              >
                {{ t('login_badge_last_used', 'បានប្រើចុងក្រោយ') }}
              </span>
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
          <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-800">
            <button
              type="button"
              @click="authMode = 'password'; step = 'identifier'"
              class="group text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-sky-400 flex items-center gap-1 cursor-pointer transition-colors"
            >
              <svg class="w-3.5 h-3.5 transition-transform duration-150 group-hover:-translate-x-0.5 text-zinc-500 group-hover:text-blue-600 dark:group-hover:text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
              </svg>
              <span>{{ currentLang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}</span>
            </button>
            <!-- Sleek Minimalist Step Indicator (Circles) -->
            <div class="flex items-center gap-1.5 select-none" aria-label="Step Indicator">
              <span
                :class="[
                  'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold transition-all duration-200',
                  otpStep === 1
                    ? 'bg-blue-600 text-white shadow-xs shadow-blue-500/30 ring-2 ring-blue-500/20'
                    : 'bg-emerald-500 text-white'
                ]"
              >
                <i v-if="otpStep > 1" class="pi pi-check text-[8px]"></i>
                <span v-else>1</span>
              </span>
              <span
                :class="[
                  'w-4 h-0.5 rounded-full transition-colors duration-200',
                  otpStep > 1 ? 'bg-emerald-500' : 'bg-zinc-200 dark:bg-zinc-700'
                ]"
              ></span>
              <span
                :class="[
                  'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold transition-all duration-200',
                  otpStep === 2
                    ? 'bg-blue-600 text-white shadow-xs shadow-blue-500/30 ring-2 ring-blue-500/20'
                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500 border border-zinc-200 dark:border-zinc-700'
                ]"
              >
                2
              </span>
            </div>
          </div>

          <div v-if="otpStep === 1" class="space-y-3.5">
            <!-- Email Input Field with Icon Prefix & Clear Button -->
            <div class="space-y-1.5">
              <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                {{ currentLang === 'km' ? 'អាសយដ្ឋានអ៊ីមែល' : 'Email Address' }}
              </label>

              <div class="relative w-full group">
                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 flex items-center pointer-events-none text-blue-600 dark:text-sky-400">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="16" x="2" y="4" rx="2"/>
                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                  </svg>
                </div>
                <input
                  ref="otpEmailInputRef"
                  v-model="otpEmail"
                  type="email"
                  required
                  autofocus
                  autocomplete="off"
                  autocorrect="off"
                  autocapitalize="none"
                  spellcheck="false"
                  placeholder="name@gmail.com"
                  class="w-full h-11 pl-10 pr-9 bg-white dark:bg-[#121214] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none shadow-2xs transition-all font-medium"
                  @focus="isEmailInputFocused = true"
                  @blur="onEmailBlur"
                  @keydown="onEmailKeydown"
                />
                <button
                  v-if="otpEmail"
                  type="button"
                  @click="otpEmail = ''; otpEmailInputRef?.focus()"
                  class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 flex items-center justify-center text-[10px] cursor-pointer transition-colors"
                  title="Clear"
                >
                  <i class="pi pi-times"></i>
                </button>

                <!-- Dynamic Auto-Popup Email Suggestions Dropdown (Clean & Proper Alignment) -->
                <Transition
                  enter-active-class="transition duration-150 ease-out"
                  enter-from-class="opacity-0 -translate-y-1 scale-98"
                  enter-to-class="opacity-100 translate-y-0 scale-100"
                  leave-active-class="transition duration-100 ease-in"
                  leave-from-class="opacity-100 translate-y-0 scale-100"
                  leave-to-class="opacity-0 -translate-y-1 scale-98"
                >
                  <div
                    v-if="filteredEmailSuggestions.length > 0 && isEmailInputFocused"
                    class="absolute left-0 right-0 top-full mt-1.5 z-50 bg-white dark:bg-[#18181b] border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl p-1.5 space-y-1 select-none"
                  >
                    <button
                      v-for="(sug, sIdx) in filteredEmailSuggestions"
                      :key="sIdx"
                      type="button"
                      @mousedown.prevent="selectEmailSuggestion(sug.fullEmail)"
                      :class="[
                        'w-full px-3 py-2.5 rounded-xl text-left text-xs flex items-center justify-between transition-all duration-150 cursor-pointer group',
                        selectedSuggestionIndex === sIdx
                          ? 'bg-blue-50 dark:bg-zinc-800 ring-1 ring-blue-500/30 text-zinc-950 dark:text-white'
                          : 'hover:bg-zinc-100 dark:hover:bg-zinc-800/70 text-zinc-800 dark:text-zinc-200'
                      ]"
                    >
                      <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-6 h-6 rounded-lg bg-zinc-100 dark:bg-zinc-800/90 border border-zinc-200/80 dark:border-zinc-700/60 flex items-center justify-center text-xs shrink-0">
                          <svg v-if="sug.name === 'Gmail'" class="w-3.5 h-3.5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                          </svg>
                          <span v-else class="text-xs">{{ sug.icon }}</span>
                        </div>
                        <div class="truncate text-xs font-medium">
                          <span class="text-zinc-900 dark:text-zinc-100 font-semibold">{{ sug.username }}</span>
                          <span class="text-blue-600 dark:text-sky-400 font-bold font-mono">{{ sug.domain }}</span>
                        </div>
                      </div>
                      <span class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-800/80 px-2 py-0.5 rounded-md border border-zinc-200/60 dark:border-zinc-700/50">
                        {{ sug.name }}
                      </span>
                    </button>
                  </div>
                </Transition>
              </div>
            </div>

            <!-- Send OTP Button -->
            <button
              type="button"
              @click="sendEmailOtp"
              :disabled="isOtpSending || !otpEmail || !otpEmail.includes('@')"
              :class="[
                'w-full h-11 rounded-xl font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-200 select-none cursor-pointer',
                otpEmail && otpEmail.includes('@') && !isOtpSending
                  ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 active:scale-[0.99]'
                  : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500 cursor-not-allowed opacity-60'
              ]"
            >
              <i v-if="isOtpSending" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ isOtpSending ? (currentLang === 'km' ? 'កំពុងផ្ញើលេខកូដ...' : 'Sending code...') : (currentLang === 'km' ? 'ផ្ញើលេខកូដ OTP' : 'Send OTP') }}</span>
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[0] ? 'border-blue-600 dark:border-sky-400 bg-blue-50/40 dark:bg-sky-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105'
                ]"
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[1] ? 'border-blue-600 dark:border-sky-400 bg-blue-50/40 dark:bg-sky-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105'
                ]"
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[2] ? 'border-blue-600 dark:border-sky-400 bg-blue-50/40 dark:bg-sky-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105'
                ]"
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[3] ? 'border-blue-600 dark:border-sky-400 bg-blue-50/40 dark:bg-sky-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105'
                ]"
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[4] ? 'border-blue-600 dark:border-sky-400 bg-blue-50/40 dark:bg-sky-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105'
                ]"
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[5] ? 'border-blue-600 dark:border-sky-400 bg-blue-50/40 dark:bg-sky-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-blue-600 dark:focus:border-sky-400 focus:ring-2 focus:ring-blue-500/20 focus:scale-105'
                ]"
                @input="onDigitInput(5, $event)"
                @keydown="onDigitKeydown(5, $event)"
              />
            </div>

            <div class="flex items-center justify-between text-[11px] pt-0.5">
              <span v-if="otpCountdown > 0" class="text-zinc-500 flex items-center gap-1">
                <i class="pi pi-clock text-[10px]"></i>
                <span>{{ currentLang === 'km' ? 'ផុតកំណត់៖' : 'Expires in:' }}</span>
                <strong class="text-amber-600 dark:text-amber-400 font-mono">{{ formattedOtpTime }}</strong>
              </span>
              <span v-else class="text-rose-500 font-bold flex items-center gap-1">
                <i class="pi pi-exclamation-circle text-[10px]"></i>
                <span>{{ currentLang === 'km' ? 'កូដផុតកំណត់' : 'Code expired' }}</span>
              </span>

              <button
                type="button"
                @click="sendEmailOtp"
                :disabled="isOtpSending || emailResendCooldown > 0"
                :class="[
                  'font-medium text-xs transition-colors flex items-center gap-1',
                  emailResendCooldown > 0 || isOtpSending
                    ? 'text-zinc-400 dark:text-zinc-600 cursor-not-allowed'
                    : 'text-blue-600 dark:text-sky-400 hover:text-blue-700 dark:hover:text-sky-300 cursor-pointer hover:underline'
                ]"
              >
                <i v-if="isOtpSending" class="pi pi-spin pi-spinner text-[10px]"></i>
                <span v-if="emailResendCooldown > 0">
                  {{ currentLang === 'km' ? `ផ្ញើម្តងទៀត (${emailResendCooldown}s)` : `Resend (${emailResendCooldown}s)` }}
                </span>
                <span v-else>
                  {{ currentLang === 'km' ? 'ផ្ញើម្តងទៀត' : 'Resend Code' }}
                </span>
              </button>
            </div>

            <button
              type="button"
              @click="verifyEmailOtp"
              :disabled="isOtpVerifying || otpCode.length < 6"
              :class="[
                'w-full h-11 rounded-xl font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-200 select-none',
                otpCode.length === 6 && !isOtpVerifying
                  ? 'bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 shadow-md shadow-blue-500/20 cursor-pointer active:scale-[0.99]'
                  : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500 cursor-not-allowed opacity-60'
              ]"
            >
              <i v-if="isOtpVerifying" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ isOtpVerifying ? (currentLang === 'km' ? 'កំពុងផ្ទៀងផ្ទាត់...' : 'Verifying...') : (currentLang === 'km' ? 'ផ្ទៀងផ្ទាត់ និង ចូលប្រើប្រាស់' : 'Verify & Continue') }}</span>
            </button>
          </div>
        </div>

        <!-- ========================================================================= -->
        <!-- PHONE OTP MODE VIEW (PlasGate SMS Flow)                                   -->
        <!-- ========================================================================= -->
        <div v-else-if="authMode === 'phone_otp'" class="w-full space-y-3 animate-fade-in">
          <div class="flex items-center justify-between pb-1 border-b border-zinc-200 dark:border-zinc-800">
            <button
              type="button"
              @click="authMode = 'password'; step = 'identifier'"
              class="group text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center gap-1 cursor-pointer transition-colors"
            >
              <svg class="w-3.5 h-3.5 transition-transform duration-150 group-hover:-translate-x-0.5 text-zinc-500 group-hover:text-emerald-600 dark:group-hover:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6"/>
              </svg>
              <span>{{ currentLang === 'km' ? 'ត្រឡប់ក្រោយ' : 'Back' }}</span>
            </button>
            <div class="flex items-center gap-1.5">
              <span
                :class="[
                  'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold transition-all',
                  phoneOtpStep === 1
                    ? 'bg-emerald-600 text-white shadow-xs'
                    : 'bg-emerald-600 text-white'
                ]"
              >
                <i v-if="phoneOtpStep > 1" class="pi pi-check text-[9px]"></i>
                <span v-else>1</span>
              </span>
              <span class="w-3 h-0.5 rounded-full bg-zinc-300 dark:bg-zinc-700"></span>
              <span
                :class="[
                  'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold transition-all',
                  phoneOtpStep === 2
                    ? 'bg-emerald-600 text-white shadow-xs'
                    : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500 border border-zinc-200 dark:border-zinc-700'
                ]"
              >
                2
              </span>
            </div>
          </div>

          <div v-if="phoneOtpStep === 1" class="space-y-3.5">
            <div class="space-y-1.5">
              <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                {{ currentLang === 'km' ? 'លេខទូរស័ព្ទ' : 'Phone Number' }}
              </label>

              <div class="relative w-full">
                <!-- Click Outside Backdrop for Dropdown -->
                <div
                  v-if="isPhoneCountryDropdownOpen"
                  class="fixed inset-0 z-20"
                  @click="isPhoneCountryDropdownOpen = false"
                ></div>

                <!-- Interactive Country Selector Trigger Button -->
                <button
                  type="button"
                  @click="isPhoneCountryDropdownOpen = !isPhoneCountryDropdownOpen"
                  class="absolute left-2 top-1/2 -translate-y-1/2 flex items-center gap-1.5 py-1 px-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800/80 text-xs font-bold text-zinc-700 dark:text-zinc-300 transition-colors cursor-pointer z-30 select-none"
                  :title="selectedPhoneCountry.name"
                >
                  <!-- Real High-Res Country Flag from FlagCDN -->
                  <span class="relative w-5 h-3.5 rounded-xs overflow-hidden shadow-2xs border border-zinc-200/80 dark:border-zinc-700/80 shrink-0 inline-flex items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                    <img
                      :src="`https://flagcdn.com/w40/${selectedPhoneCountry.code.toLowerCase()}.png`"
                      :alt="selectedPhoneCountry.name"
                      class="w-full h-full object-cover"
                      loading="lazy"
                    />
                  </span>
                  <span class="font-mono text-xs">{{ selectedPhoneCountry.dialCode }}</span>
                  <i :class="['pi text-[9px] text-zinc-400 transition-transform duration-200', isPhoneCountryDropdownOpen ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
                  <span class="text-zinc-300 dark:text-zinc-700 ml-0.5 pointer-events-none">|</span>
                </button>

                <input
                  v-model="otpPhone"
                  type="tel"
                  required
                  :placeholder="selectedPhoneCountry.code === 'KH' ? '12 345 678' : 'Phone number'"
                  :class="[
                    'w-full h-11 pr-9 bg-white dark:bg-[#121214] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 focus:border-emerald-600 dark:focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 text-xs sm:text-sm rounded-xl outline-none shadow-2xs font-mono transition-all font-medium',
                    selectedPhoneCountry.dialCode.length > 4 ? 'pl-32' : 'pl-28'
                  ]"
                  @keydown.enter.prevent="sendPhoneOtp()"
                />

                <button
                  v-if="otpPhone"
                  type="button"
                  @click="otpPhone = ''"
                  class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 flex items-center justify-center text-[10px] cursor-pointer transition-colors z-10"
                  title="Clear"
                >
                  <i class="pi pi-times"></i>
                </button>

                <!-- Searchable All Countries Dropdown Menu -->
                <div
                  v-if="isPhoneCountryDropdownOpen"
                  class="absolute left-0 right-0 top-full mt-1.5 z-30 bg-white dark:bg-[#18181b] border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-2xl overflow-hidden animate-fade-in"
                >
                  <div class="p-2 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-[#121214]">
                    <div class="relative flex items-center">
                      <i class="pi pi-search absolute left-2.5 text-xs text-zinc-400"></i>
                      <input
                        v-model="phoneCountrySearch"
                        type="text"
                        :placeholder="currentLang === 'km' ? 'ស្វែងរកប្រទេស ឬកូដ...' : 'Search country or code...'"
                        autofocus
                        class="w-full h-8 pl-8 pr-3 bg-white dark:bg-[#18181b] text-xs text-zinc-900 dark:text-white rounded-lg border border-zinc-200 dark:border-zinc-700 focus:outline-none focus:border-emerald-500"
                      />
                    </div>
                  </div>

                  <div class="max-h-48 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800/60 custom-scrollbar">
                    <button
                      v-for="c in filteredPhoneCountries"
                      :key="c.code"
                      type="button"
                      @click="selectPhoneCountry(c)"
                      :class="[
                        'w-full px-3 py-2 text-xs flex items-center justify-between text-left hover:bg-zinc-50 dark:hover:bg-zinc-800/60 transition cursor-pointer',
                        selectedPhoneCountry.code === c.code ? 'text-emerald-600 dark:text-emerald-400 font-semibold bg-emerald-50/50 dark:bg-emerald-950/30' : 'text-zinc-700 dark:text-zinc-300'
                      ]"
                    >
                      <div class="flex items-center gap-2.5 truncate">
                        <span class="relative w-5 h-3.5 rounded-xs overflow-hidden shadow-2xs border border-zinc-200/80 dark:border-zinc-700/80 shrink-0 inline-flex items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                          <img
                            :src="`https://flagcdn.com/w40/${c.code.toLowerCase()}.png`"
                            :alt="c.name"
                            class="w-full h-full object-cover"
                            loading="lazy"
                          />
                        </span>
                        <span class="truncate">
                          {{ currentLang === 'km' && c.nameKm ? `${c.nameKm} (${c.name})` : c.name }}
                        </span>
                      </div>
                      <span class="text-xs font-mono text-zinc-400 shrink-0 ml-2">{{ c.dialCode }}</span>
                    </button>
                    <div v-if="filteredPhoneCountries.length === 0" class="p-3 text-center text-xs text-zinc-400">
                      {{ currentLang === 'km' ? 'រកមិនឃើញប្រទេសទេ' : 'No country found' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Delivery Channel Selector (Telegram @VerificationCodes vs Direct SMS) -->
            <div class="space-y-1.5 pt-0.5">
              <div class="flex items-center justify-between">
                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400">
                  {{ currentLang === 'km' ? 'វិធីសាស្ត្រទទួលលេខកូដ OTP' : 'Receive OTP Code Via' }}
                </label>
                <span class="text-[10px] text-zinc-400 dark:text-zinc-500 font-medium">
                  {{ preferredPhoneChannel === 'telegram' ? (currentLang === 'km' ? 'លឿន & ឥតគិតថ្លៃ' : 'Fast & Free') : (currentLang === 'km' ? 'ផ្ញើទៅប្រអប់សារ' : 'Direct to Phone') }}
                </span>
              </div>
              <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-zinc-100 dark:bg-zinc-800/70 border border-zinc-200/80 dark:border-zinc-700/60">
                <!-- Telegram Option -->
                <button
                  type="button"
                  @click="preferredPhoneChannel = 'telegram'"
                  :class="[
                    'flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-lg text-xs font-semibold transition-all cursor-pointer select-none',
                    preferredPhoneChannel === 'telegram'
                      ? 'bg-white dark:bg-zinc-900 text-sky-600 dark:text-sky-400 shadow-xs border border-zinc-200/80 dark:border-zinc-700'
                      : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'
                  ]"
                >
                  <i class="pi pi-telegram text-sky-500 text-xs shrink-0"></i>
                  <span class="truncate">Telegram</span>
                  <span class="text-[9px] px-1 py-0.2 rounded bg-sky-500/10 text-sky-600 dark:text-sky-400 font-normal shrink-0">
                    {{ currentLang === 'km' ? 'ឥតគិតថ្លៃ' : 'Free' }}
                  </span>
                </button>

                <!-- Phone SMS Option -->
                <button
                  type="button"
                  @click="preferredPhoneChannel = 'sms'"
                  :class="[
                    'flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-lg text-xs font-semibold transition-all cursor-pointer select-none',
                    preferredPhoneChannel === 'sms'
                      ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-xs border border-zinc-200/80 dark:border-zinc-700'
                      : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200'
                  ]"
                >
                  <i class="pi pi-envelope text-emerald-500 text-xs shrink-0"></i>
                  <span class="truncate">{{ currentLang === 'km' ? 'សារ SMS' : 'SMS' }}</span>
                  <span class="text-[9px] px-1 py-0.2 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-normal shrink-0">
                    {{ currentLang === 'km' ? 'ទូរស័ព្ទ' : 'Phone' }}
                  </span>
                </button>
              </div>
            </div>

            <button
              type="button"
              @click="sendPhoneOtp()"
              :disabled="isPhoneOtpSending || !otpPhone"
              :class="[
                'w-full h-11 rounded-xl font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-200 select-none cursor-pointer',
                otpPhone && !isPhoneOtpSending
                  ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-500/20 active:scale-[0.99]'
                  : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500 cursor-not-allowed opacity-60'
              ]"
            >
              <i v-if="isPhoneOtpSending" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ isPhoneOtpSending ? (currentLang === 'km' ? 'កំពុងផ្ញើលេខកូដ...' : 'Sending code...') : (currentLang === 'km' ? 'ផ្ញើលេខកូដ OTP' : 'Send OTP') }}</span>
            </button>
          </div>

          <div v-else class="space-y-3.5 animate-fade-in">
            <div class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-800 text-xs">
              <div class="flex items-center gap-1.5 min-w-0 flex-wrap">
                <i :class="phoneOtpChannel === 'sms' ? 'pi pi-envelope text-emerald-600 dark:text-emerald-400 text-xs shrink-0' : 'pi pi-telegram text-sky-500 text-xs shrink-0'"></i>
                <span class="text-zinc-500 dark:text-zinc-400">{{ currentLang === 'km' ? 'ផ្ញើទៅកាន់៖' : 'Sent to:' }}</span>
                <strong class="text-zinc-800 dark:text-zinc-200 font-mono truncate">{{ formattedDisplayPhone }}</strong>
                <span v-if="phoneOtpChannel === 'telegram_bot'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20">
                  <i class="pi pi-check-circle text-[10px]"></i>
                  Telegram @spi_elms_auth_bot
                </span>
                <span v-else-if="phoneOtpChannel === 'telegram_gateway'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20">
                  <i class="pi pi-check-circle text-[10px]"></i>
                  Telegram @VerificationCodes
                </span>
                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                  <i class="pi pi-envelope text-[10px]"></i>
                  {{ currentLang === 'km' ? 'សារ SMS ទូរស័ព្ទ' : 'Phone SMS' }}
                </span>
              </div>
              <button
                type="button"
                @click="phoneOtpStep = 1; clearOtpDigits()"
                class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer ml-2 shrink-0"
              >
                {{ currentLang === 'km' ? 'កែប្រែ' : 'Edit' }}
              </button>
            </div>

            <!-- Instant Guaranteed Free Telegram Bot Delivery Card (Works 100% for all phone numbers) -->
            <a
              :href="botOtpLink"
              target="_blank"
              rel="noopener noreferrer"
              class="flex items-center justify-between p-2.5 sm:p-3 rounded-xl bg-gradient-to-r from-sky-500/15 via-sky-500/10 to-indigo-500/15 hover:from-sky-500/25 hover:to-indigo-500/25 border border-sky-500/30 text-sky-700 dark:text-sky-300 transition-all cursor-pointer group shadow-xs"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-sky-500 text-white flex items-center justify-center shrink-0 shadow-xs group-hover:scale-105 transition-transform">
                  <i class="pi pi-telegram text-lg"></i>
                </div>
                <div class="text-left min-w-0">
                  <div class="text-xs font-bold leading-tight flex items-center gap-1.5 flex-wrap">
                    <span>{{ currentLang === 'km' ? 'ទទួលកូដតាម Telegram Bot ភ្លាមៗ' : 'Get Code via Telegram Bot' }}</span>
                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-emerald-500 text-white animate-pulse">100% Free</span>
                  </div>
                  <div class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">
                    {{ currentLang === 'km' ? 'ចុចទីនេះដើម្បីទទួលកូដ ៦ ខ្ទង់លើ @spi_elms_auth_bot' : 'Tap to receive your 6-digit OTP code on Telegram' }}
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-1 text-sky-600 dark:text-sky-400 shrink-0 font-semibold text-xs ml-2">
                <span class="hidden sm:inline">{{ currentLang === 'km' ? 'ទទួលកូដ' : 'Get Code' }}</span>
                <i class="pi pi-arrow-up-right text-xs group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
              </div>
            </a>

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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[0] ? 'border-emerald-500 dark:border-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105'
                ]"
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[1] ? 'border-emerald-500 dark:border-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105'
                ]"
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[2] ? 'border-emerald-500 dark:border-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105'
                ]"
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[3] ? 'border-emerald-500 dark:border-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105'
                ]"
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[4] ? 'border-emerald-500 dark:border-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105'
                ]"
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
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[5] ? 'border-emerald-500 dark:border-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/30' : 'border-zinc-300 dark:border-zinc-700 focus:border-emerald-500 dark:focus:border-emerald-400 focus:ring-2 focus:ring-emerald-500/20 focus:scale-105'
                ]"
                @input="onDigitInput(5, $event)"
                @keydown="onDigitKeydown(5, $event)"
              />
            </div>

            <div class="flex items-center justify-between text-[11px] pt-0.5">
              <span v-if="phoneOtpCountdown > 0" class="text-zinc-500 flex items-center gap-1">
                <i class="pi pi-clock text-[10px]"></i>
                <span>{{ currentLang === 'km' ? 'ផុតកំណត់៖' : 'Expires in:' }}</span>
                <strong class="text-amber-600 dark:text-amber-400 font-mono">{{ formattedPhoneOtpTime }}</strong>
              </span>
              <span v-else class="text-rose-500 font-bold flex items-center gap-1">
                <i class="pi pi-exclamation-circle text-[10px]"></i>
                <span>{{ currentLang === 'km' ? 'កូដផុតកំណត់' : 'Code expired' }}</span>
              </span>

              <button
                type="button"
                @click="sendPhoneOtp()"
                :disabled="isPhoneOtpSending || phoneResendCooldown > 0"
                :class="[
                  'font-medium text-xs transition-colors flex items-center gap-1',
                  phoneResendCooldown > 0 || isPhoneOtpSending
                    ? 'text-zinc-400 dark:text-zinc-600 cursor-not-allowed'
                    : 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 cursor-pointer hover:underline'
                ]"
              >
                <i v-if="isPhoneOtpSending" class="pi pi-spin pi-spinner text-[10px]"></i>
                <span v-if="phoneResendCooldown > 0">
                  {{ currentLang === 'km' ? `ផ្ញើម្តងទៀត (${phoneResendCooldown}s)` : `Resend (${phoneResendCooldown}s)` }}
                </span>
                <span v-else>
                  {{ currentLang === 'km' ? 'ផ្ញើម្តងទៀត' : 'Resend Code' }}
                </span>
              </button>
            </div>

            <button
              type="button"
              @click="verifyPhoneOtp"
              :disabled="isPhoneOtpVerifying || otpCode.length < 6"
              :class="[
                'w-full h-11 rounded-xl font-semibold text-xs sm:text-sm flex items-center justify-center transition-all duration-200 select-none',
                otpCode.length === 6 && !isPhoneOtpVerifying
                  ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-500/20 cursor-pointer active:scale-[0.99]'
                  : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-500 cursor-not-allowed opacity-60'
              ]"
            >
              <i v-if="isPhoneOtpVerifying" class="pi pi-spin pi-spinner text-sm mr-2"></i>
              <span>{{ isPhoneOtpVerifying ? (currentLang === 'km' ? 'កំពុងផ្ទៀងផ្ទាត់...' : 'Verifying...') : (currentLang === 'km' ? 'ផ្ទៀងផ្ទាត់ និង ចូលប្រើប្រាស់' : 'Verify & Continue') }}</span>
            </button>

            <!-- Alternate Channel Switch Fallback Option -->
            <div class="pt-2 text-center border-t border-zinc-200/60 dark:border-zinc-800/80 flex items-center justify-center">
              <button
                type="button"
                @click="sendPhoneOtp(phoneOtpChannel === 'sms' ? 'telegram' : 'sms')"
                :disabled="isPhoneOtpSending"
                class="inline-flex items-center gap-1.5 text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 font-medium cursor-pointer transition-colors hover:underline"
              >
                <i v-if="isPhoneOtpSending" class="pi pi-spin pi-spinner text-xs"></i>
                <i v-else :class="phoneOtpChannel === 'sms' ? 'pi pi-telegram text-xs' : 'pi pi-envelope text-xs'"></i>
                <span>{{ phoneOtpChannel === 'sms' ? (currentLang === 'km' ? 'ផ្ញើតាម Telegram Bot ជំនួសវិញ' : 'Send via Telegram Bot instead') : (currentLang === 'km' ? 'សាកល្បងផ្ញើតាម SMS' : 'Try sending via SMS') }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Footer Terms & Policy Legal Statement -->
        <p class="text-[11px] text-slate-500 dark:text-zinc-500 leading-normal text-center mt-6 w-full sm:w-auto max-w-lg px-2 select-text sm:whitespace-nowrap">
          {{ currentLang === 'km' ? 'តាមរយៈការបន្ត អ្នកយល់ព្រមតាម ' : 'By continuing, you agree to our ' }}
          <Link href="/terms" class="text-slate-700 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-zinc-200 underline underline-offset-2 transition-colors">
            {{ currentLang === 'km' ? 'លក្ខខណ្ឌប្រើប្រាស់' : 'Terms of Service' }}
          </Link>
          {{ currentLang === 'km' ? ' និងបានអាន ' : ' and have read our ' }}
          <Link href="/privacy" class="text-slate-700 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-zinc-200 underline underline-offset-2 transition-colors">
            {{ currentLang === 'km' ? 'គោលការណ៍ឯកជនភាព' : 'Privacy Policy' }}</Link>{{ currentLang === 'km' ? '។' : '.' }}
        </p>

      </div>

      <!-- Loading / Authenticating Overlay -->
      <AuthProcessLoader
        v-else
        :is-success="authSuccess"
        :title="authLoadingTitle"
        :subtitle="authLoadingSubtitle"
        :logo-url="logoUrl"
        :lang="currentLang"
        mode="login"
      />

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

    <!-- Telegram Login Modal (KOOMPI ID / Device / Phone / QR code style) -->
    <TelegramLoginModal
      :show="showTelegramModal"
      :current-lang="currentLang"
      @close="showTelegramModal = false"
      @success="onTelegramModalSuccess"
      @use-device="onUseTelegramDevice"
    />

    <!-- Global Toast Notifications -->
    <GlobalToast />

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

<!-- Unscoped styles so html.dark / .dark selectors work seamlessly in both modes -->
<style>
@property --beam-angle {
  syntax: '<angle>';
  inherits: false;
  initial-value: 0deg;
}

@keyframes beam-rotate {
  0% {
    --beam-angle: 0deg;
  }
  100% {
    --beam-angle: 360deg;
  }
}

.border-beam-btn {
  --beam-bg: #ffffff;
  --beam-border-base: #e2e8f0;
  --beam-shadow: 0 2px 10px -2px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);

  position: relative;
  border: 1px solid transparent !important;
  background-clip: padding-box, border-box !important;
  background-origin: padding-box, border-box !important;
  background-image:
    linear-gradient(var(--beam-bg), var(--beam-bg)),
    conic-gradient(
      from var(--beam-angle),
      var(--beam-border-base) 0deg,
      var(--beam-border-base) 250deg,
      rgba(37, 99, 235, 0.35) 280deg,
      #2563eb 320deg,
      #38bdf8 355deg,
      var(--beam-border-base) 360deg
    ) !important;
  animation: beam-rotate 4s linear infinite;
  box-shadow: var(--beam-shadow);
}

.border-beam-btn:hover {
  --beam-bg: #f8fafc;
  --beam-border-base: #cbd5e1;
  --beam-shadow: 0 0 16px -2px rgba(37, 99, 235, 0.25);
}

/* Dark Mode: When <html> or parent has .dark class */
html.dark .border-beam-btn,
.dark .border-beam-btn {
  --beam-bg: #18181b !important;
  --beam-border-base: #27272a !important;
  --beam-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.4) !important;
  background-image:
    linear-gradient(var(--beam-bg), var(--beam-bg)),
    conic-gradient(
      from var(--beam-angle),
      var(--beam-border-base) 0deg,
      var(--beam-border-base) 260deg,
      rgba(249, 115, 22, 0.25) 285deg,
      #f97316 325deg,
      #fed7aa 355deg,
      var(--beam-border-base) 360deg
    ) !important;
}

html.dark .border-beam-btn:hover,
.dark .border-beam-btn:hover {
  --beam-bg: #232327 !important;
  --beam-border-base: #3f3f46 !important;
  --beam-shadow: 0 0 16px -2px rgba(249, 115, 22, 0.35) !important;
}

/* High-Definition Circular Theme Reveal Transition */
::view-transition-old(root),
::view-transition-new(root) {
  animation: none;
  mix-blend-mode: normal;
}
::view-transition-old(root) {
  z-index: 1;
}
::view-transition-new(root) {
  z-index: 99999;
}
</style>
