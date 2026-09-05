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

const logoUrl = '/images/logo.png'

const screen = ref<ScreenMode>('options')
const selectedOption = ref<SelectedOption>('device')

import { WORLD_COUNTRIES, type CountryItem } from '@/data/countries'

const countries: CountryItem[] = WORLD_COUNTRIES
const selectedCountry = ref<CountryItem>(countries[0])
const isCountryDropdownOpen = ref(false)
const countrySearch = ref('')

const filteredCountries = computed(() => {
  const q = countrySearch.value.trim().toLowerCase()
  if (!q) return countries
  return countries.filter(c =>
    c.name.toLowerCase().includes(q) ||
    (c.nameKm && c.nameKm.toLowerCase().includes(q)) ||
    c.dialCode.includes(q) ||
    c.code.toLowerCase().includes(q)
  )
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
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm select-none"
        @click.self="emit('close')"
      >
        <div
          class="relative w-full max-w-[400px] p-6 sm:p-7 bg-white dark:bg-[#121214] border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-2xl text-zinc-900 dark:text-white overflow-hidden animate-fade-in"
        >
          <!-- Close button (matching Login style) -->
          <button
            type="button"
            @click="emit('close')"
            class="absolute top-4 right-4 p-1.5 text-zinc-400 hover:text-zinc-700 dark:hover:text-white rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition cursor-pointer"
          >
            <i class="pi pi-times text-xs"></i>
          </button>

          <!-- Top Brand Logo (Telegram + E-LMS Official Logo) -->
          <div class="flex items-center justify-center mb-4">
            <div class="relative flex items-center justify-center">
              <!-- Telegram Blue Circle -->
              <div class="w-14 h-14 rounded-full bg-[#24A1DE] flex items-center justify-center shadow-lg shadow-sky-500/25">
                <svg class="w-8 h-8 text-white translate-x-[-1px] translate-y-[-1px]" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .4z"/>
                </svg>
              </div>
              <!-- E-LMS Logo Badge from Login Form -->
              <div class="w-14 h-14 rounded-full bg-white border-2 border-sky-500 flex items-center justify-center -ml-4 shadow-lg p-1 overflow-hidden drop-shadow">
                <img
                  :src="logoUrl"
                  alt="E-LMS Logo"
                  class="w-full h-full object-contain rounded-full"
                  onerror="this.src='/logo.png'"
                />
              </div>
            </div>
          </div>

          <!-- ═══════════════ SCREEN 1: LOGIN OPTIONS (Styled like Login Form) ═══════════════ -->
          <div v-if="screen === 'options'" class="space-y-5">
            <div class="text-center space-y-1">
              <h2 class="text-xl sm:text-[22px] font-black tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-white dark:via-zinc-100 dark:to-zinc-300 bg-clip-text text-transparent text-center">
                Login options
              </h2>
              <p class="text-xs text-slate-600 dark:text-zinc-400 text-center">
                Choose how you'd like to log in to <span class="font-semibold text-zinc-800 dark:text-zinc-200">spilms.tech</span>.
              </p>
            </div>

            <!-- Radio Options Stack (Styled like Login Form Buttons) -->
            <div class="space-y-2.5 pt-1">
              <!-- Option 1: Use Telegram on this device -->
              <div
                role="button"
                tabindex="0"
                @click="chooseOption('device')"
                :class="[
                  'w-full h-12 px-4 rounded-xl border flex items-center justify-between transition-all duration-150 cursor-pointer select-none active:scale-[0.99] shadow-xs',
                  selectedOption === 'device'
                    ? 'bg-blue-50/70 dark:bg-zinc-800/80 border-blue-600 dark:border-zinc-400 ring-1 ring-blue-500/20 dark:ring-zinc-400/20'
                    : 'bg-white hover:bg-zinc-50 dark:bg-[#18181b] dark:hover:bg-[#232327] border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'
                ]"
              >
                <div class="flex items-center gap-3">
                  <svg class="w-4 h-4 text-[#0088cc] dark:text-[#29b6f6] shrink-0" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .4z"/>
                  </svg>
                  <span class="text-xs sm:text-sm font-medium text-zinc-900 dark:text-white">Use Telegram on this device</span>
                </div>
                <div
                  :class="[
                    'w-4 h-4 rounded-full border flex items-center justify-center transition-all',
                    selectedOption === 'device' ? 'border-blue-600 dark:border-white bg-blue-600 dark:bg-white' : 'border-zinc-300 dark:border-zinc-700'
                  ]"
                >
                  <div v-if="selectedOption === 'device'" class="w-1.5 h-1.5 rounded-full bg-white dark:bg-zinc-950"></div>
                </div>
              </div>

              <!-- Option 2: Log in with a phone number -->
              <div
                role="button"
                tabindex="0"
                @click="chooseOption('phone')"
                :class="[
                  'w-full h-12 px-4 rounded-xl border flex items-center justify-between transition-all duration-150 cursor-pointer select-none active:scale-[0.99] shadow-xs',
                  selectedOption === 'phone'
                    ? 'bg-blue-50/70 dark:bg-zinc-800/80 border-blue-600 dark:border-zinc-400 ring-1 ring-blue-500/20 dark:ring-zinc-400/20'
                    : 'bg-white hover:bg-zinc-50 dark:bg-[#18181b] dark:hover:bg-[#232327] border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'
                ]"
              >
                <div class="flex items-center gap-3">
                  <i class="pi pi-phone text-emerald-600 dark:text-emerald-400 text-sm"></i>
                  <span class="text-xs sm:text-sm font-medium text-zinc-900 dark:text-white">Log in with a phone number</span>
                </div>
                <div
                  :class="[
                    'w-4 h-4 rounded-full border flex items-center justify-center transition-all',
                    selectedOption === 'phone' ? 'border-blue-600 dark:border-white bg-blue-600 dark:bg-white' : 'border-zinc-300 dark:border-zinc-700'
                  ]"
                >
                  <div v-if="selectedOption === 'phone'" class="w-1.5 h-1.5 rounded-full bg-white dark:bg-zinc-950"></div>
                </div>
              </div>

              <!-- Option 3: Scan a QR code -->
              <div
                role="button"
                tabindex="0"
                @click="chooseOption('qr')"
                :class="[
                  'w-full h-12 px-4 rounded-xl border flex items-center justify-between transition-all duration-150 cursor-pointer select-none active:scale-[0.99] shadow-xs',
                  selectedOption === 'qr'
                    ? 'bg-blue-50/70 dark:bg-zinc-800/80 border-blue-600 dark:border-zinc-400 ring-1 ring-blue-500/20 dark:ring-zinc-400/20'
                    : 'bg-white hover:bg-zinc-50 dark:bg-[#18181b] dark:hover:bg-[#232327] border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700'
                ]"
              >
                <div class="flex items-center gap-3">
                  <i class="pi pi-qrcode text-indigo-600 dark:text-indigo-400 text-sm"></i>
                  <span class="text-xs sm:text-sm font-medium text-zinc-900 dark:text-white">Scan a QR code</span>
                </div>
                <div
                  :class="[
                    'w-4 h-4 rounded-full border flex items-center justify-center transition-all',
                    selectedOption === 'qr' ? 'border-blue-600 dark:border-white bg-blue-600 dark:bg-white' : 'border-zinc-300 dark:border-zinc-700'
                  ]"
                >
                  <div v-if="selectedOption === 'qr'" class="w-1.5 h-1.5 rounded-full bg-white dark:bg-zinc-950"></div>
                </div>
              </div>
            </div>

            <!-- Continue Button (Matching Login Continue button) -->
            <button
              type="button"
              @click="handleContinue"
              class="w-full h-11 rounded-xl text-xs sm:text-sm font-semibold flex items-center justify-center transition-all duration-150 select-none shadow-sm bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99] mt-2"
            >
              Continue
            </button>
          </div>

          <!-- ═══════════════ SCREEN 2: PHONE NUMBER LOGIN ═══════════════ -->
          <div v-else-if="screen === 'phone'" class="space-y-5">
            <div class="text-center space-y-1">
              <h2 class="text-xl sm:text-[22px] font-black tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-white dark:via-zinc-100 dark:to-zinc-300 bg-clip-text text-transparent text-center">
                Log in to spilms.tech
              </h2>
              <p class="text-xs text-slate-600 dark:text-zinc-400 text-center">
                Enter the phone number linked to your Telegram to confirm your login there.
              </p>
            </div>

            <div class="space-y-2.5 pt-1">
              <!-- Searchable Country Dropdown (Matching Login styles) -->
              <div class="relative">
                <button
                  type="button"
                  @click="isCountryDropdownOpen = !isCountryDropdownOpen"
                  class="w-full h-11 px-3.5 rounded-xl bg-white dark:bg-[#121214] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 text-xs sm:text-sm flex items-center justify-between text-zinc-900 dark:text-white transition cursor-pointer shadow-2xs"
                >
                  <div class="flex items-center gap-2.5 truncate">
                    <span class="relative w-5 h-3.5 rounded-xs overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-700/80 shrink-0 inline-flex items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                      <img
                        :src="`https://flagcdn.com/w40/${selectedCountry.code.toLowerCase()}.png`"
                        :alt="selectedCountry.name"
                        class="w-full h-full object-cover"
                        loading="lazy"
                      />
                    </span>
                    <span class="font-medium truncate">{{ selectedCountry.name }}</span>
                    <span class="text-xs font-mono text-zinc-400">({{ selectedCountry.dialCode }})</span>
                  </div>
                  <i :class="['pi text-xs text-zinc-400 transition-transform duration-200', isCountryDropdownOpen ? 'pi-chevron-up' : 'pi-chevron-down']"></i>
                </button>

                <!-- Dropdown Menu with Search Input -->
                <div
                  v-if="isCountryDropdownOpen"
                  class="absolute left-0 right-0 top-12 z-30 bg-white dark:bg-[#18181b] border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-2xl overflow-hidden animate-fade-in"
                >
                  <div class="p-2 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-[#121214]">
                    <div class="relative flex items-center">
                      <i class="pi pi-search absolute left-3 text-xs text-zinc-400"></i>
                      <input
                        v-model="countrySearch"
                        type="text"
                        placeholder="Search country..."
                        autofocus
                        class="w-full h-8 pl-8 pr-3 bg-white dark:bg-[#18181b] text-xs text-zinc-900 dark:text-white rounded-lg border border-zinc-200 dark:border-zinc-700 focus:outline-none focus:border-zinc-500"
                      />
                    </div>
                  </div>

                  <div class="max-h-48 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800/60 custom-scrollbar">
                    <button
                      v-for="c in filteredCountries"
                      :key="c.code"
                      type="button"
                      @click="selectCountry(c)"
                      :class="[
                        'w-full px-3.5 py-2.5 text-xs flex items-center justify-between text-left hover:bg-zinc-50 dark:hover:bg-zinc-800/60 transition cursor-pointer',
                        selectedCountry.code === c.code ? 'text-blue-600 dark:text-white font-semibold bg-blue-50 dark:bg-zinc-800' : 'text-zinc-700 dark:text-zinc-300'
                      ]"
                    >
                      <div class="flex items-center gap-2.5 truncate">
                        <span class="relative w-5 h-3.5 rounded-xs overflow-hidden shadow-xs border border-zinc-200/80 dark:border-zinc-700/80 shrink-0 inline-flex items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                          <img
                            :src="`https://flagcdn.com/w40/${c.code.toLowerCase()}.png`"
                            :alt="c.name"
                            class="w-full h-full object-cover"
                            loading="lazy"
                          />
                        </span>
                        <span class="truncate">{{ currentLang === 'km' && c.nameKm ? `${c.nameKm} (${c.name})` : c.name }}</span>
                      </div>
                      <span class="text-xs font-mono text-zinc-400 shrink-0 ml-2">{{ c.dialCode }}</span>
                    </button>
                    <div v-if="filteredCountries.length === 0" class="p-3 text-center text-xs text-zinc-400">
                      No country found
                    </div>
                  </div>
                </div>
              </div>

              <!-- Phone Number Input with Dial Code Prefix -->
              <div class="relative flex items-center rounded-xl bg-white dark:bg-[#121214] border border-zinc-300 dark:border-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-700 focus-within:border-zinc-600 dark:focus-within:border-zinc-500 focus-within:ring-1 focus-within:ring-zinc-600 dark:focus-within:ring-zinc-500 transition shadow-2xs">
                <span class="pl-3.5 pr-2 text-xs sm:text-sm font-mono text-zinc-500 dark:text-zinc-400 select-none">{{ selectedCountry.dialCode }}</span>
                <input
                  v-model="phone"
                  type="tel"
                  inputmode="numeric"
                  autofocus
                  placeholder="88 801 0546"
                  @keydown.enter.prevent="sendOtp"
                  class="w-full h-11 pr-3 bg-transparent text-xs sm:text-sm font-mono text-zinc-900 dark:text-white outline-none placeholder:text-zinc-400 dark:placeholder:text-zinc-500"
                />
              </div>

              <p v-if="otpError" class="text-xs text-rose-500 dark:text-rose-400 flex items-center gap-1.5 px-1">
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
                'w-full h-11 rounded-xl text-xs sm:text-sm font-semibold flex items-center justify-center transition-all duration-150 select-none shadow-sm',
                phone && !isSendingOtp
                  ? 'bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99]'
                  : 'bg-slate-200 dark:bg-[#18181b] text-slate-400 dark:text-zinc-600 border border-slate-300 dark:border-zinc-800 cursor-not-allowed opacity-70'
              ]"
            >
              <i v-if="isSendingOtp" class="pi pi-spin pi-spinner mr-2 text-sm"></i>
              <span>{{ isSendingOtp ? 'Sending code...' : 'Continue' }}</span>
            </button>

            <!-- Other Login Options Link -->
            <div class="text-center pt-1">
              <button
                type="button"
                @click="screen = 'options'"
                class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-300 cursor-pointer font-medium flex items-center justify-center gap-1 mx-auto transition-colors"
              >
                <span>Other login options &gt;</span>
              </button>
            </div>
          </div>

          <!-- ═══════════════ SCREEN 3: OTP VERIFICATION ═══════════════ -->
          <div v-else-if="screen === 'otp'" class="space-y-5">
            <div class="text-center space-y-1">
              <h2 class="text-xl sm:text-[22px] font-black tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-white dark:via-zinc-100 dark:to-zinc-300 bg-clip-text text-transparent text-center">
                Enter confirmation code
              </h2>
              <p class="text-xs text-slate-600 dark:text-zinc-400 text-center">
                We sent a 6-digit verification code to your Telegram from
                <span class="text-sky-600 dark:text-sky-400 font-semibold">@VerificationCodes</span>.
              </p>
            </div>

            <!-- 6-digit Segmented PIN Input System matching Login.vue -->
            <div class="flex items-center justify-center gap-1.5 sm:gap-2 my-4 select-none">
              <input
                v-for="(_, i) in 6"
                :key="i"
                :id="`tg-otp-${i}`"
                v-model="otpDigits[i]"
                type="text"
                maxlength="1"
                inputmode="numeric"
                pattern="[0-9]*"
                placeholder="-"
                :class="[
                  'w-10 sm:w-11 h-12 text-center text-lg sm:text-xl font-bold font-mono rounded-xl bg-zinc-50 dark:bg-zinc-900/90 border text-zinc-900 dark:text-white outline-none shadow-xs transition-all duration-150',
                  otpDigits[i]
                    ? 'border-blue-600 dark:border-zinc-400 bg-blue-50/40 dark:bg-zinc-800/50'
                    : 'border-zinc-300 dark:border-zinc-700 focus:border-blue-600 dark:focus:border-zinc-400 focus:ring-2 focus:ring-blue-500/20'
                ]"
                @input="onDigitInput(i, $event)"
                @keydown="onDigitKeydown(i, $event)"
              />
            </div>

            <p v-if="otpError" class="text-xs text-rose-500 dark:text-rose-400 text-center">
              {{ otpError }}
            </p>

            <button
              type="button"
              :disabled="otpDigits.some(d => !d) || isVerifyingOtp"
              @click="verifyOtp"
              :class="[
                'w-full h-11 rounded-xl text-xs sm:text-sm font-semibold flex items-center justify-center transition-all duration-150 select-none shadow-sm',
                !otpDigits.some(d => !d) && !isVerifyingOtp
                  ? 'bg-blue-600 hover:bg-blue-700 text-white dark:bg-[#e4e4e7] dark:hover:bg-white dark:text-zinc-950 cursor-pointer shadow-md shadow-blue-500/20 active:scale-[0.99]'
                  : 'bg-slate-200 dark:bg-[#18181b] text-slate-400 dark:text-zinc-600 border border-slate-300 dark:border-zinc-800 cursor-not-allowed opacity-70'
              ]"
            >
              <i v-if="isVerifyingOtp" class="pi pi-spin pi-spinner mr-2 text-sm"></i>
              <span>{{ isVerifyingOtp ? 'Verifying...' : 'Confirm Login' }}</span>
            </button>

            <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 px-1 pt-1">
              <button
                type="button"
                @click="screen = 'phone'"
                class="text-zinc-500 hover:text-zinc-900 dark:hover:text-white cursor-pointer"
              >
                &lt; Change number
              </button>
              <button
                type="button"
                :disabled="otpCooldown > 0 || isSendingOtp"
                @click="sendOtp"
                class="text-blue-600 dark:text-sky-400 hover:underline disabled:opacity-50 cursor-pointer font-medium"
              >
                {{ otpCooldown > 0 ? `Resend (${otpCooldown}s)` : 'Resend code' }}
              </button>
            </div>
          </div>

          <!-- ═══════════════ SCREEN 4: SCAN QR CODE ═══════════════ -->
          <div v-else-if="screen === 'qr'" class="space-y-5">
            <div class="text-center space-y-1">
              <h2 class="text-xl sm:text-[22px] font-black tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-white dark:via-zinc-100 dark:to-zinc-300 bg-clip-text text-transparent text-center">
                Continue with Telegram
              </h2>
              <p class="text-xs text-slate-600 dark:text-zinc-400 text-center">
                Scan this QR code with your camera on a device with Telegram installed.
              </p>
            </div>

            <!-- QR Code Card -->
            <div class="flex flex-col items-center justify-center py-2">
              <div class="relative p-3.5 bg-white rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-800">
                <img
                  v-if="qrDataUrl"
                  :src="qrDataUrl"
                  alt="Telegram QR Login"
                  class="w-52 h-52 block rounded-lg"
                />
                <div v-else class="w-52 h-52 flex flex-col items-center justify-center text-zinc-600 gap-2">
                  <i class="pi pi-spin pi-spinner text-2xl text-sky-500"></i>
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
                <span v-if="qrStatus === 'pending'" class="inline-flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                  <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                  <span>Waiting for scan...</span>
                </span>
                <span v-else-if="qrStatus === 'approved'" class="text-xs text-emerald-500 font-semibold">
                  ✓ Verified! Logging in...
                </span>
                <span v-else-if="qrStatus === 'expired'" class="text-xs text-rose-500">
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
                  class="text-xs text-sky-600 dark:text-sky-400 hover:underline cursor-pointer font-medium"
                >
                  Or log in with a phone number &gt;
                </button>
              </div>
              <div>
                <button
                  type="button"
                  @click="screen = 'options'"
                  class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-white cursor-pointer"
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
  background: rgba(24, 24, 27, 0.4);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(113, 113, 122, 0.5);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(161, 161, 170, 0.8);
}
</style>
