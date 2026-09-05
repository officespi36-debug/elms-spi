<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import QRCode from 'qrcode'

const props = defineProps<{
  show: boolean
  currentLang?: string
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success', user: any): void
  (e: 'use-device'): void
}>()

type ScreenMode = 'options' | 'phone' | 'otp' | 'qr'
type SelectedOption = 'device' | 'phone' | 'qr'

const screen = ref<ScreenMode>('options')
const selectedOption = ref<SelectedOption>('device')

interface CountryItem {
  name: string
  dialCode: string
  flag: string
  code: string
}

const countries: CountryItem[] = [
  { name: 'Cambodia', dialCode: '+855', flag: '🇰🇭', code: 'KH' },
  { name: 'Afghanistan', dialCode: '+93', flag: '🇦🇫', code: 'AF' },
  { name: 'Albania', dialCode: '+355', flag: '🇦🇱', code: 'AL' },
  { name: 'Algeria', dialCode: '+213', flag: '🇩🇿', code: 'DZ' },
  { name: 'American Samoa', dialCode: '+1684', flag: '🇦🇸', code: 'AS' },
  { name: 'Andorra', dialCode: '+376', flag: '🇦🇩', code: 'AD' },
  { name: 'Angola', dialCode: '+244', flag: '🇦🇴', code: 'AO' },
  { name: 'Argentina', dialCode: '+54', flag: '🇦🇷', code: 'AR' },
  { name: 'Australia', dialCode: '+61', flag: '🇦🇺', code: 'AU' },
  { name: 'Austria', dialCode: '+43', flag: '🇦🇹', code: 'AT' },
  { name: 'Bahrain', dialCode: '+973', flag: '🇧🇭', code: 'BH' },
  { name: 'Bangladesh', dialCode: '+880', flag: '🇧🇩', code: 'BD' },
  { name: 'Belgium', dialCode: '+32', flag: '🇧🇪', code: 'BE' },
  { name: 'Brazil', dialCode: '+55', flag: '🇧🇷', code: 'BR' },
  { name: 'Canada', dialCode: '+1', flag: '🇨🇦', code: 'CA' },
  { name: 'China', dialCode: '+86', flag: '🇨🇳', code: 'CN' },
  { name: 'Egypt', dialCode: '+20', flag: '🇪🇬', code: 'EG' },
  { name: 'France', dialCode: '+33', flag: '🇫🇷', code: 'FR' },
  { name: 'Germany', dialCode: '+49', flag: '🇩🇪', code: 'DE' },
  { name: 'Hong Kong', dialCode: '+852', flag: '🇭🇰', code: 'HK' },
  { name: 'India', dialCode: '+91', flag: '🇮🇳', code: 'IN' },
  { name: 'Indonesia', dialCode: '+62', flag: '🇮🇩', code: 'ID' },
  { name: 'Italy', dialCode: '+39', flag: '🇮🇹', code: 'IT' },
  { name: 'Japan', dialCode: '+81', flag: '🇯🇵', code: 'JP' },
  { name: 'Laos', dialCode: '+856', flag: '🇱🇦', code: 'LA' },
  { name: 'Malaysia', dialCode: '+60', flag: '🇲🇾', code: 'MY' },
  { name: 'Myanmar', dialCode: '+95', flag: '🇲🇲', code: 'MM' },
  { name: 'New Zealand', dialCode: '+64', flag: '🇳🇿', code: 'NZ' },
  { name: 'Philippines', dialCode: '+63', flag: '🇵🇭', code: 'PH' },
  { name: 'Qatar', dialCode: '+974', flag: '🇶🇦', code: 'QA' },
  { name: 'Saudi Arabia', dialCode: '+966', flag: '🇸🇦', code: 'SA' },
  { name: 'Singapore', dialCode: '+65', flag: '🇸🇬', code: 'SG' },
  { name: 'South Korea', dialCode: '+82', flag: '🇰🇷', code: 'KR' },
  { name: 'Spain', dialCode: '+34', flag: '🇪🇸', code: 'ES' },
  { name: 'Taiwan', dialCode: '+886', flag: '🇹🇼', code: 'TW' },
  { name: 'Thailand', dialCode: '+66', flag: '🇹🇭', code: 'TH' },
  { name: 'United Arab Emirates', dialCode: '+971', flag: '🇦🇪', code: 'AE' },
  { name: 'United Kingdom', dialCode: '+44', flag: '🇬🇧', code: 'GB' },
  { name: 'United States', dialCode: '+1', flag: '🇺🇸', code: 'US' },
  { name: 'Vietnam', dialCode: '+84', flag: '🇻🇳', code: 'VN' },
]

const selectedCountry = ref<CountryItem>(countries[0])
const isCountryDropdownOpen = ref(false)
const countrySearch = ref('')

const filteredCountries = computed(() => {
  const q = countrySearch.value.trim().toLowerCase()
  if (!q) return countries
  return countries.filter(c => c.name.toLowerCase().includes(q) || c.dialCode.includes(q))
})

const selectCountry = (c: CountryItem) => {
  selectedCountry.value = c
  isCountryDropdownOpen.value = false
  countrySearch.value = ''
}

// Phone state
const phone = ref('')
const isSendingOtp = ref(false)
const otpDigits = ref<string[]>(['', '', '', '', '', ''])
const isVerifyingOtp = ref(false)
const otpError = ref<string | null>(null)
const otpSuccessMsg = ref<string | null>(null)
const otpCooldown = ref(0)
let otpCooldownTimer: any = null

// QR state
const qrDataUrl = ref<string>('')
const qrToken = ref<string>('')
const qrDeepLink = ref<string>('')
const isGeneratingQr = ref(false)
const qrStatus = ref<'pending' | 'approved' | 'expired' | 'error'>('pending')
let qrPollTimer: any = null

const formattedIntlPhone = computed(() => {
  const raw = phone.value.trim().replace(/[^0-9]/g, '')
  if (!raw) return ''
  const dial = selectedCountry.value.dialCode.replace('+', '')
  if (raw.startsWith(dial)) return '+' + raw
  if (raw.startsWith('0')) return '+' + dial + raw.substring(1)
  return '+' + dial + raw
})

// Generate QR Code with canvas/DataURL
const generateQrCode = async () => {
  isGeneratingQr.value = true
  qrStatus.value = 'pending'
  stopQrPolling()

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const res = await fetch('/auth/telegram/qr-init', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    const data = await res.json()

    if (res.ok && data.success && data.deep_link) {
      qrToken.value = data.token
      qrDeepLink.value = data.deep_link

      // Generate SVG/Canvas QR Code
      qrDataUrl.value = await QRCode.toDataURL(data.deep_link, {
        width: 240,
        margin: 1.5,
        color: {
          dark: '#0f172a',
          light: '#ffffff',
        },
      })

      startQrPolling()
    } else {
      qrStatus.value = 'error'
    }
  } catch (e) {
    qrStatus.value = 'error'
  } finally {
    isGeneratingQr.value = false
  }
}

const startQrPolling = () => {
  stopQrPolling()
  qrPollTimer = setInterval(async () => {
    if (!qrToken.value) return
    try {
      const res = await fetch(`/auth/telegram/qr-status?token=${encodeURIComponent(qrToken.value)}`)
      const data = await res.json()

      if (data.status === 'approved') {
        stopQrPolling()
        qrStatus.value = 'approved'
        emit('success', data.user)
        if (data.redirect) {
          window.location.assign(data.redirect)
        }
      } else if (data.status === 'expired') {
        stopQrPolling()
        qrStatus.value = 'expired'
      }
    } catch {
      // Keep polling on minor network glitches
    }
  }, 1500)
}

const stopQrPolling = () => {
  if (qrPollTimer) {
    clearInterval(qrPollTimer)
    qrPollTimer = null
  }
}

// Send OTP via Telegram Gateway
const sendOtp = async () => {
  if (!phone.value || isSendingOtp.value) return
  isSendingOtp.value = true
  otpError.value = null

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const res = await fetch('/auth/phone-otp/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ phone: formattedIntlPhone.value }),
    })
    const data = await res.json()

    if (res.ok && data.success) {
      screen.value = 'otp'
      otpSuccessMsg.value = data.message || 'លេខកូដត្រូវបានផ្ញើទៅកាន់ Telegram របស់អ្នក!'
      startOtpCooldown(60)
    } else {
      otpError.value = data.message || 'មិនអាចផ្ញើលេខកូដបានទេ'
    }
  } catch {
    otpError.value = 'មានបញ្ហាក្នុងការតភ្ជាប់ទៅកាន់ Server'
  } finally {
    isSendingOtp.value = false
  }
}

const verifyOtp = async () => {
  const code = otpDigits.value.join('')
  if (code.length < 6 || isVerifyingOtp.value) return
  isVerifyingOtp.value = true
  otpError.value = null

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
    const res = await fetch('/auth/phone-otp/verify', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        phone: formattedIntlPhone.value,
        code: code,
        otp: code,
      }),
    })
    const data = await res.json()

    if (res.ok && data.success) {
      emit('success', data.user)
      if (data.redirect) {
        window.location.assign(data.redirect)
      }
    } else {
      otpError.value = data.message || 'លេខកូដមិនត្រឹមត្រូវទេ!'
    }
  } catch {
    otpError.value = 'មានបញ្ហាក្នុងការផ្ទៀងផ្ទាត់'
  } finally {
    isVerifyingOtp.value = false
  }
}

const startOtpCooldown = (seconds: number) => {
  if (otpCooldownTimer) clearInterval(otpCooldownTimer)
  otpCooldown.value = seconds
  otpCooldownTimer = setInterval(() => {
    if (otpCooldown.value > 0) {
      otpCooldown.value--
    } else {
      clearInterval(otpCooldownTimer)
      otpCooldownTimer = null
    }
  }, 1000)
}

const onDigitInput = (index: number, e: Event) => {
  const target = e.target as HTMLInputElement
  const val = target.value.replace(/[^0-9]/g, '')
  otpDigits.value[index] = val ? val[val.length - 1] : ''

  if (val && index < 5) {
    const nextInput = document.getElementById(`tg-otp-${index + 1}`) as HTMLInputElement
    nextInput?.focus()
  }

  if (otpDigits.value.every(d => d !== '')) {
    verifyOtp()
  }
}

const onDigitKeydown = (index: number, e: KeyboardEvent) => {
  if (e.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    const prevInput = document.getElementById(`tg-otp-${index - 1}`) as HTMLInputElement
    prevInput?.focus()
  }
}

const chooseOption = (opt: SelectedOption) => {
  selectedOption.value = opt
}

const handleContinue = () => {
  if (selectedOption.value === 'device') {
    emit('use-device')
    emit('close')
  } else if (selectedOption.value === 'phone') {
    screen.value = 'phone'
  } else if (selectedOption.value === 'qr') {
    screen.value = 'qr'
    generateQrCode()
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    screen.value = 'options'
    selectedOption.value = 'device'
    isCountryDropdownOpen.value = false
    countrySearch.value = ''
    otpError.value = null
    phone.value = ''
  } else {
    stopQrPolling()
    isCountryDropdownOpen.value = false
  }
})

onBeforeUnmount(() => {
  stopQrPolling()
  if (otpCooldownTimer) clearInterval(otpCooldownTimer)
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md"
        @click.self="emit('close')"
      >
        <div
          class="relative w-full max-w-md p-6 sm:p-8 bg-[#18222d] border border-slate-700/60 rounded-3xl shadow-2xl text-white overflow-hidden animate-fade-in"
        >
          <!-- Close button -->
          <button
            type="button"
            @click="emit('close')"
            class="absolute top-4 right-4 p-2 text-slate-400 hover:text-white rounded-full hover:bg-slate-700/50 transition cursor-pointer"
          >
            <i class="pi pi-times text-sm"></i>
          </button>

          <!-- Top Brand Logo (Telegram + Shield Fingerprint) -->
          <div class="flex items-center justify-center mb-5">
            <div class="relative flex items-center justify-center">
              <!-- Telegram Blue Circle -->
              <div class="w-16 h-16 rounded-full bg-[#24A1DE] flex items-center justify-center shadow-lg shadow-[#24A1DE]/30">
                <svg class="w-9 h-9 text-white translate-x-[-1px] translate-y-[-1px]" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .4z"/>
                </svg>
              </div>
              <!-- Shield Fingerprint Badge -->
              <div class="w-16 h-16 rounded-full bg-[#0f172a] border-2 border-[#24A1DE] flex items-center justify-center -ml-5 shadow-lg">
                <svg class="w-8 h-8 text-[#24A1DE]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                  <path d="M12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                  <path d="M12 11v4"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- ═══════════════ SCREEN 1: LOGIN OPTIONS (Screenshot 5) ═══════════════ -->
          <div v-if="screen === 'options'" class="space-y-6">
            <div class="text-center space-y-1">
              <h2 class="text-xl font-bold text-white tracking-tight">Login options</h2>
              <p class="text-xs text-slate-400">
                Choose how you'd like to log in to <span class="text-slate-300 font-medium">spilms.tech</span>.
              </p>
            </div>

            <!-- Radio Options Stack -->
            <div class="space-y-2.5">
              <!-- Option 1: Use Telegram on this device -->
              <div
                role="button"
                tabindex="0"
                @click="chooseOption('device')"
                :class="[
                  'flex items-center justify-between p-3.5 rounded-2xl border transition-all duration-150 cursor-pointer select-none active:scale-[0.99]',
                  selectedOption === 'device'
                    ? 'bg-[#202c3a] border-[#24A1DE] shadow-sm shadow-[#24A1DE]/20'
                    : 'bg-[#1c2633] border-slate-700/60 hover:border-slate-600 text-slate-300'
                ]"
              >
                <div class="flex items-center gap-3">
                  <i class="pi pi-send text-[#24A1DE] text-base"></i>
                  <span class="text-sm font-medium">Use Telegram on this device</span>
                </div>
                <div
                  :class="[
                    'w-5 h-5 rounded-full border flex items-center justify-center transition',
                    selectedOption === 'device' ? 'border-[#24A1DE] bg-[#24A1DE]' : 'border-slate-500'
                  ]"
                >
                  <div v-if="selectedOption === 'device'" class="w-2 h-2 rounded-full bg-white"></div>
                </div>
              </div>

              <!-- Option 2: Log in with a phone number -->
              <div
                role="button"
                tabindex="0"
                @click="chooseOption('phone')"
                :class="[
                  'flex items-center justify-between p-3.5 rounded-2xl border transition-all duration-150 cursor-pointer select-none active:scale-[0.99]',
                  selectedOption === 'phone'
                    ? 'bg-[#202c3a] border-[#24A1DE] shadow-sm shadow-[#24A1DE]/20'
                    : 'bg-[#1c2633] border-slate-700/60 hover:border-slate-600 text-slate-300'
                ]"
              >
                <div class="flex items-center gap-3">
                  <i class="pi pi-phone text-slate-400 text-base"></i>
                  <span class="text-sm font-medium">Log in with a phone number</span>
                </div>
                <div
                  :class="[
                    'w-5 h-5 rounded-full border flex items-center justify-center transition',
                    selectedOption === 'phone' ? 'border-[#24A1DE] bg-[#24A1DE]' : 'border-slate-500'
                  ]"
                >
                  <div v-if="selectedOption === 'phone'" class="w-2 h-2 rounded-full bg-white"></div>
                </div>
              </div>

              <!-- Option 3: Scan a QR code -->
              <div
                role="button"
                tabindex="0"
                @click="chooseOption('qr')"
                :class="[
                  'flex items-center justify-between p-3.5 rounded-2xl border transition-all duration-150 cursor-pointer select-none active:scale-[0.99]',
                  selectedOption === 'qr'
                    ? 'bg-[#202c3a] border-[#24A1DE] shadow-sm shadow-[#24A1DE]/20'
                    : 'bg-[#1c2633] border-slate-700/60 hover:border-slate-600 text-slate-300'
                ]"
              >
                <div class="flex items-center gap-3">
                  <i class="pi pi-qrcode text-slate-400 text-base"></i>
                  <span class="text-sm font-medium">Scan a QR code</span>
                </div>
                <div
                  :class="[
                    'w-5 h-5 rounded-full border flex items-center justify-center transition',
                    selectedOption === 'qr' ? 'border-[#24A1DE] bg-[#24A1DE]' : 'border-slate-500'
                  ]"
                >
                  <div v-if="selectedOption === 'qr'" class="w-2 h-2 rounded-full bg-white"></div>
                </div>
              </div>
            </div>

            <!-- Continue Button -->
            <button
              type="button"
              @click="handleContinue"
              class="w-full h-12 rounded-2xl font-semibold text-sm bg-[#24A1DE] hover:bg-[#1f93cc] active:scale-[0.99] text-white transition-all shadow-md shadow-[#24A1DE]/25 flex items-center justify-center cursor-pointer"
            >
              Continue
            </button>
          </div>

          <!-- ═══════════════ SCREEN 2: PHONE NUMBER LOGIN (Screenshot 4) ═══════════════ -->
          <div v-else-if="screen === 'phone'" class="space-y-5">
            <div class="text-center space-y-1">
              <h2 class="text-xl font-bold text-white tracking-tight">Log in to spilms.tech</h2>
              <p class="text-xs text-slate-400 leading-relaxed px-2">
                Enter the phone number linked to your Telegram to confirm your login there.
              </p>
            </div>

            <div class="space-y-3 pt-1">
              <!-- Searchable Country Dropdown (Screenshot 4 style) -->
              <div class="relative">
                <button
                  type="button"
                  @click="isCountryDropdownOpen = !isCountryDropdownOpen"
                  class="w-full h-11 px-3.5 rounded-xl bg-[#1c2633] border border-slate-700/70 text-sm flex items-center justify-between text-slate-200 hover:border-slate-600 transition cursor-pointer"
                >
                  <div class="flex items-center gap-2 truncate">
                    <span class="text-base">{{ selectedCountry.flag }}</span>
                    <span class="font-medium text-slate-200 truncate">{{ selectedCountry.name }}</span>
                    <span class="text-xs font-mono text-slate-400">({{ selectedCountry.dialCode }})</span>
                  </div>
                  <i :class="['pi text-xs text-slate-400 transition-transform duration-200', isCountryDropdownOpen ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
                </button>

                <!-- Dropdown Menu with Search Input -->
                <div
                  v-if="isCountryDropdownOpen"
                  class="absolute left-0 right-0 top-12 z-30 bg-[#1c2633] border border-slate-700 rounded-xl shadow-2xl overflow-hidden animate-fade-in"
                >
                  <div class="p-2 border-b border-slate-700/80 bg-[#17212b]">
                    <div class="relative flex items-center">
                      <i class="pi pi-search absolute left-3 text-xs text-slate-400"></i>
                      <input
                        v-model="countrySearch"
                        type="text"
                        placeholder="Search"
                        autofocus
                        class="w-full h-8 pl-8 pr-3 bg-[#111822] text-xs text-white rounded-lg border border-slate-700/70 focus:outline-none focus:border-[#24A1DE]"
                      />
                    </div>
                  </div>

                  <div class="max-h-48 overflow-y-auto divide-y divide-slate-800/60 custom-scrollbar">
                    <button
                      v-for="c in filteredCountries"
                      :key="c.code"
                      type="button"
                      @click="selectCountry(c)"
                      :class="[
                        'w-full px-3.5 py-2 text-xs flex items-center justify-between text-left hover:bg-[#202c3a] transition cursor-pointer',
                        selectedCountry.code === c.code ? 'text-[#24A1DE] font-semibold bg-[#202c3a]/70' : 'text-slate-300'
                      ]"
                    >
                      <div class="flex items-center gap-2 truncate">
                        <span class="text-sm">{{ c.flag }}</span>
                        <span class="truncate">{{ c.name }}</span>
                      </div>
                      <span class="text-xs font-mono text-slate-400 shrink-0 ml-2">{{ c.dialCode }}</span>
                    </button>
                    <div v-if="filteredCountries.length === 0" class="p-3 text-center text-xs text-slate-500">
                      No country found
                    </div>
                  </div>
                </div>
              </div>

              <!-- Phone Number Input with Dial Code Prefix -->
              <div class="relative flex items-center rounded-xl bg-[#1c2633] border border-slate-700/70 focus-within:border-[#24A1DE] focus-within:ring-2 focus-within:ring-[#24A1DE]/20 transition">
                <span class="pl-3.5 pr-2 text-sm font-mono text-slate-400 select-none">{{ selectedCountry.dialCode }}</span>
                <input
                  v-model="phone"
                  type="tel"
                  inputmode="numeric"
                  autofocus
                  placeholder="88 801 0546"
                  @keydown.enter.prevent="sendOtp"
                  class="w-full h-11 pr-3 bg-transparent text-sm font-mono text-white outline-none placeholder:text-slate-500"
                />
              </div>

              <p v-if="otpError" class="text-xs text-rose-400 flex items-center gap-1.5 px-1">
                <i class="pi pi-times-circle text-xs"></i>
                <span>{{ otpError }}</span>
              </p>
            </div>

            <!-- Continue Button -->
            <button
              type="button"
              :disabled="!phone || isSendingOtp"
              @click="sendOtp"
              :class="[
                'w-full h-12 rounded-2xl font-semibold text-sm transition-all flex items-center justify-center cursor-pointer',
                phone && !isSendingOtp
                  ? 'bg-[#24A1DE] hover:bg-[#1f93cc] active:scale-[0.99] text-white shadow-md shadow-[#24A1DE]/25'
                  : 'bg-slate-700/50 text-slate-400 cursor-not-allowed'
              ]"
            >
              <i v-if="isSendingOtp" class="pi pi-spin pi-spinner mr-2 text-sm"></i>
              <span>{{ isSendingOtp ? 'Sending code...' : 'Continue' }}</span>
            </button>

            <!-- Other Login Options Link (Screenshot 4 style) -->
            <div class="text-center pt-1">
              <button
                type="button"
                @click="screen = 'options'"
                class="text-xs text-[#24A1DE] hover:underline cursor-pointer flex items-center justify-center gap-1 mx-auto"
              >
                <span>Other login options &gt;</span>
              </button>
            </div>
          </div>

          <!-- ═══════════════ SCREEN 3: OTP VERIFICATION ═══════════════ -->
          <div v-else-if="screen === 'otp'" class="space-y-5">
            <div class="text-center space-y-1">
              <h2 class="text-xl font-bold text-white tracking-tight">Enter confirmation code</h2>
              <p class="text-xs text-slate-400 leading-relaxed">
                We sent a 6-digit verification code to your Telegram from
                <span class="text-[#24A1DE] font-semibold">@VerificationCodes</span>.
              </p>
            </div>

            <!-- 6 PIN inputs -->
            <div class="flex items-center justify-center gap-2 my-4">
              <input
                v-for="(_, i) in 6"
                :key="i"
                :id="`tg-otp-${i}`"
                v-model="otpDigits[i]"
                type="text"
                maxlength="1"
                inputmode="numeric"
                class="w-11 h-12 text-center text-lg font-bold font-mono rounded-xl bg-[#1c2633] border border-slate-700/80 focus:border-[#24A1DE] focus:ring-2 focus:ring-[#24A1DE]/20 text-white outline-none transition"
                @input="onDigitInput(i, $event)"
                @keydown="onDigitKeydown(i, $event)"
              />
            </div>

            <p v-if="otpError" class="text-xs text-rose-400 text-center">
              {{ otpError }}
            </p>

            <button
              type="button"
              :disabled="otpDigits.some(d => !d) || isVerifyingOtp"
              @click="verifyOtp"
              :class="[
                'w-full h-12 rounded-2xl font-semibold text-sm transition-all flex items-center justify-center cursor-pointer',
                !otpDigits.some(d => !d) && !isVerifyingOtp
                  ? 'bg-[#24A1DE] hover:bg-[#1f93cc] active:scale-[0.99] text-white shadow-md shadow-[#24A1DE]/25'
                  : 'bg-slate-700/50 text-slate-400 cursor-not-allowed'
              ]"
            >
              <i v-if="isVerifyingOtp" class="pi pi-spin pi-spinner mr-2 text-sm"></i>
              <span>{{ isVerifyingOtp ? 'Verifying...' : 'Confirm Login' }}</span>
            </button>

            <div class="flex items-center justify-between text-xs text-slate-400 px-1 pt-1">
              <button
                type="button"
                @click="screen = 'phone'"
                class="text-slate-400 hover:text-white cursor-pointer"
              >
                &lt; Change number
              </button>
              <button
                type="button"
                :disabled="otpCooldown > 0 || isSendingOtp"
                @click="sendOtp"
                class="text-[#24A1DE] hover:underline disabled:opacity-50 cursor-pointer"
              >
                {{ otpCooldown > 0 ? `Resend (${otpCooldown}s)` : 'Resend code' }}
              </button>
            </div>
          </div>

          <!-- ═══════════════ SCREEN 4: SCAN QR CODE (Screenshot 3 link) ═══════════════ -->
          <div v-else-if="screen === 'qr'" class="space-y-5">
            <div class="text-center space-y-1">
              <h2 class="text-xl font-bold text-white tracking-tight">Continue with Telegram</h2>
              <p class="text-xs text-slate-400 leading-relaxed px-3">
                Scan this QR code with your camera on a device with Telegram installed.
              </p>
            </div>

            <!-- QR Code Card -->
            <div class="flex flex-col items-center justify-center py-2">
              <div class="relative p-3.5 bg-white rounded-2xl shadow-xl">
                <img
                  v-if="qrDataUrl"
                  :src="qrDataUrl"
                  alt="Telegram QR Login"
                  class="w-52 h-52 block rounded-lg"
                />
                <div v-else class="w-52 h-52 flex flex-col items-center justify-center text-slate-600 gap-2">
                  <i class="pi pi-spin pi-spinner text-2xl text-[#24A1DE]"></i>
                  <span class="text-xs">Generating QR code...</span>
                </div>

                <!-- Telegram Center Logo on QR -->
                <div
                  v-if="qrDataUrl"
                  class="absolute inset-0 flex items-center justify-center pointer-events-none"
                >
                  <div class="w-11 h-11 rounded-full bg-[#24A1DE] border-2 border-white flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white translate-x-[-0.5px] translate-y-[-0.5px]" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .4z"/>
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Status indicator -->
              <div class="mt-3 text-center">
                <span v-if="qrStatus === 'pending'" class="inline-flex items-center gap-1.5 text-xs text-slate-400">
                  <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                  <span>Waiting for scan...</span>
                </span>
                <span v-else-if="qrStatus === 'approved'" class="text-xs text-emerald-400 font-semibold">
                  ✓ Verified! Logging in...
                </span>
                <span v-else-if="qrStatus === 'expired'" class="text-xs text-rose-400">
                  QR code expired. <button type="button" @click="generateQrCode" class="underline font-bold">Refresh</button>
                </span>
              </div>
            </div>

            <!-- Alternative links -->
            <div class="space-y-2 text-center pt-1">
              <div>
                <button
                  type="button"
                  @click="screen = 'phone'"
                  class="text-xs text-[#24A1DE] hover:underline cursor-pointer"
                >
                  Or log in with a phone number &gt;
                </button>
              </div>
              <div>
                <button
                  type="button"
                  @click="screen = 'options'"
                  class="text-xs text-slate-400 hover:text-white cursor-pointer"
                >
                  &lt; Other login options
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.4);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(100, 116, 139, 0.5);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.8);
}
</style>
