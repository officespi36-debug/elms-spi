<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    isSuccess?: boolean
    title?: string
    subtitle?: string
    logoUrl?: string
    lang?: 'km' | 'en'
    mode?: 'login' | 'register' | 'otp' | 'reset'
  }>(),
  {
    isSuccess: false,
    title: '',
    subtitle: '',
    logoUrl: '/images/logo.png',
    lang: 'km',
    mode: 'login',
  }
)

// Active percentage counter (0% to 100%)
const progress = ref(8)
let progressTimer: any = null
let stepTimer: any = null

// Current active pipeline step (0, 1, 2)
const activeStepIndex = ref(0)

const steps = computed(() => {
  if (props.mode === 'register') {
    return props.lang === 'km'
      ? [
          { label: 'បង្កើតទិន្នន័យសម្គាល់', sub: 'កំពុងបង្កើត Identity Profile' },
          { label: 'ផ្ទៀងផ្ទាត់សុវត្ថិភាព', sub: 'ពិនិត្យសិទ្ធិប្រើប្រាស់' },
          { label: 'រៀបចំគណនីសិក្សា', sub: 'ដំឡើងបន្ទប់រៀន E-LMS' },
        ]
      : [
          { label: 'Identity Generation', sub: 'Creating user profile' },
          { label: 'Security Verification', sub: 'Checking authorizations' },
          { label: 'Workspace Setup', sub: 'Initializing E-LMS desk' },
        ]
  } else if (props.mode === 'reset') {
    return props.lang === 'km'
      ? [
          { label: 'ផ្ទៀងផ្ទាត់កូដសម្ងាត់', sub: 'ត្រួតពិនិត្យសុពលភាព OTP' },
          { label: 'អ៊ិនគ្រីបពាក្យសម្ងាត់ថ្មី', sub: 'ការពារទិន្នន័យ 256-Bit' },
          { label: 'ធ្វើបច្ចុប្បន្នភាពគណនី', sub: 'ជោគជ័យ និងចូលប្រព័ន្ធ' },
        ]
      : [
          { label: 'OTP Verification', sub: 'Validating security code' },
          { label: 'Key Encryption', sub: 'Securing new password' },
          { label: 'Account Update', sub: 'Ready to authenticate' },
        ]
  }
  // Default login / auth
  return props.lang === 'km'
    ? [
        { label: 'តភ្ជាប់ប្រព័ន្ធសុវត្ថិភាព', sub: 'TLS 1.3 Handshake' },
        { label: 'ផ្ទៀងផ្ទាត់អត្តសញ្ញាណ', sub: 'ពិនិត្យសិទ្ធិ និង Token' },
        { label: 'រៀបចំផ្ទាំងគ្រប់គ្រង', sub: 'ផ្ទុក Dashboard និងមេរៀន' },
      ]
    : [
        { label: 'Secure Handshake', sub: 'TLS 1.3 Encrypted link' },
        { label: 'Token Verification', sub: 'Validating credentials' },
        { label: 'Dashboard Sync', sub: 'Loading learning modules' },
      ]
})

// Progress runner simulation
const startProgress = () => {
  progress.value = 12
  activeStepIndex.value = 0

  if (progressTimer) clearInterval(progressTimer)

  progressTimer = setInterval(() => {
    if (props.isSuccess) {
      progress.value = 100
      activeStepIndex.value = 2
      clearInterval(progressTimer)
      return
    }

    if (progress.value < 40) {
      progress.value += Math.floor(Math.random() * 8) + 4
      activeStepIndex.value = 0
    } else if (progress.value < 75) {
      progress.value += Math.floor(Math.random() * 5) + 3
      activeStepIndex.value = 1
    } else if (progress.value < 94) {
      progress.value += Math.floor(Math.random() * 2) + 1
      activeStepIndex.value = 2
    }
  }, 180)
}

watch(
  () => props.isSuccess,
  (val) => {
    if (val) {
      progress.value = 100
      activeStepIndex.value = 2
      if (progressTimer) clearInterval(progressTimer)
    }
  }
)

onMounted(() => {
  startProgress()
})

onUnmounted(() => {
  if (progressTimer) clearInterval(progressTimer)
  if (stepTimer) clearInterval(stepTimer)
})

const displayTitle = computed(() => {
  if (props.title) return props.title
  if (props.isSuccess) {
    return props.lang === 'km' ? 'ផ្ទៀងផ្ទាត់ជោគជ័យ!' : 'Access Granted!'
  }
  return props.lang === 'km' ? 'កំពុងដំណើរការ...' : 'Processing Request...'
})

const displaySubtitle = computed(() => {
  if (props.subtitle) return props.subtitle
  if (props.isSuccess) {
    return props.lang === 'km' ? 'កំពុងនាំលោកអ្នកទៅកាន់ទំព័រដើម...' : 'Redirecting to your dashboard...'
  }
  return props.lang === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងដំណើរការ...' : 'Please hold on while we secure your connection...'
})

const cleanTitle = computed(() => {
  const t = displayTitle.value || ''
  return t.replace(/(\.{2,}|…)+$/g, '').trim()
})

const detectedProvider = computed(() => {
  const str = `${props.title || ''} ${props.subtitle || ''}`.toLowerCase()
  if (str.includes('google')) return 'google'
  if (str.includes('github')) return 'github'
  if (str.includes('telegram')) return 'telegram'
  if (str.includes('email') || str.includes('otp')) return 'email'
  return null
})
</script>

<template>
  <!-- Luxury Frosted Glassmorphic Card Modal Container -->
  <div class="w-full max-w-sm mx-auto relative select-none animate-fade-in my-auto">
    
    <!-- Top Ambient Flare Glow -->
    <div class="absolute -top-10 left-1/2 -translate-x-1/2 w-48 h-24 bg-sky-500/20 dark:bg-sky-500/25 blur-3xl pointer-events-none rounded-full"></div>

    <!-- The Glassmorphic Card Frame -->
    <div class="relative w-full p-6 sm:p-8 rounded-3xl bg-white/80 dark:bg-[#111522]/85 border border-zinc-200/90 dark:border-white/10 backdrop-blur-2xl shadow-2xl shadow-black/5 dark:shadow-black/60 flex flex-col items-center justify-center text-center overflow-hidden">
      
      <!-- Top Subtle Edge Shimmer Light -->
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-40 h-[1.5px] bg-gradient-to-r from-transparent via-sky-400 to-transparent pointer-events-none opacity-80"></div>

      <!-- Hero Halo & Central Dual Emblem System -->
      <div class="relative w-28 h-28 sm:w-32 sm:h-32 mb-5 flex items-center justify-center">
        
        <!-- Soft Ambient Glow Bloom -->
        <div
          :class="[
            'absolute inset-0 rounded-full blur-2xl transition-all duration-700 pointer-events-none',
            isSuccess
              ? 'bg-emerald-500/30 scale-125'
              : 'bg-gradient-to-tr from-sky-500/25 via-blue-600/20 to-indigo-500/25 scale-110 animate-ambient-breathe'
          ]"
        ></div>

        <!-- Outer Subtle Track Ring -->
        <div class="absolute inset-0 rounded-full border border-zinc-200/70 dark:border-zinc-800/80 pointer-events-none"></div>

        <!-- Outer Smooth Comet Ring with Glowing Head -->
        <div
          v-if="!isSuccess"
          class="absolute inset-0 rounded-full border-2 border-transparent border-t-sky-400 border-r-indigo-500/50 animate-spin-smooth pointer-events-none"
        >
          <!-- Glowing Comet Head Dot -->
          <div class="w-2.5 h-2.5 rounded-full bg-sky-300 shadow-[0_0_12px_#38bdf8] absolute top-1 right-2 -translate-y-1/2"></div>
        </div>

        <!-- Core Glassmorphic Badge with Logo -->
        <div
          :class="[
            'relative w-20 h-20 sm:w-22 sm:h-22 rounded-full flex items-center justify-center transition-all duration-500 shadow-xl backdrop-blur-2xl z-10 border',
            isSuccess
              ? 'bg-emerald-500/15 border-emerald-500/50 shadow-emerald-500/30 scale-105'
              : 'bg-white/95 dark:bg-[#141824]/95 border-zinc-200/90 dark:border-zinc-800/90 shadow-black/5 dark:shadow-black/50'
          ]"
        >
          <!-- Success State: Animated Checkmark -->
          <div v-if="isSuccess" class="flex items-center justify-center animate-scale-bounce">
            <svg class="w-9 h-9 text-emerald-500 dark:text-emerald-400" viewBox="0 0 48 48" fill="none">
              <path
                d="M14 24.5L21 31.5L34 17.5"
                stroke="currentColor"
                stroke-width="3.6"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="checkmark-path"
              />
            </svg>
          </div>

          <!-- Loading State: Clean E-LMS Logo -->
          <div v-else class="relative flex items-center justify-center">
            <img
              :src="logoUrl"
              alt="E-LMS"
              class="w-12 h-12 sm:w-13 sm:h-13 rounded-full object-contain drop-shadow-sm transform transition-transform duration-500 animate-float-micro"
              onerror="this.src='/logo.png'"
            />
          </div>

          <!-- Docked Provider Badge (Google / GitHub / Telegram) at Bottom-Right -->
          <div
            v-if="detectedProvider && !isSuccess"
            class="absolute -bottom-1 -right-1 z-20 w-7 h-7 rounded-full bg-white dark:bg-[#1e2333] border border-zinc-200 dark:border-zinc-700 shadow-md flex items-center justify-center"
          >
            <!-- Google Badge -->
            <svg v-if="detectedProvider === 'google'" class="w-3.5 h-3.5" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <!-- GitHub Badge -->
            <svg v-else-if="detectedProvider === 'github'" class="w-3.5 h-3.5 fill-zinc-900 dark:fill-white" viewBox="0 0 24 24">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
            </svg>
            <!-- Telegram Badge -->
            <svg v-else-if="detectedProvider === 'telegram'" class="w-3.5 h-3.5 text-[#229ED9] fill-current" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
            </svg>
            <!-- Email / OTP Badge -->
            <svg v-else class="w-3.5 h-3.5 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="20" height="16" x="2" y="4" rx="2"/>
              <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
          </div>
        </div>

      </div>

      <!-- Title with Clean Single Bouncing Dots -->
      <div class="space-y-1.5 mb-4 max-w-xs">
        <h3
          :class="[
            'text-base sm:text-lg font-bold tracking-tight transition-colors duration-300 flex items-center justify-center gap-0.5',
            isSuccess ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-900 dark:text-white'
          ]"
        >
          <span>{{ cleanTitle }}</span>
          <span v-if="!isSuccess" class="inline-flex items-center ml-0.5 text-sky-500 font-bold">
            <span class="dot-bounce">.</span>
            <span class="dot-bounce delay-150">.</span>
            <span class="dot-bounce delay-300">.</span>
          </span>
        </h3>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed max-w-[270px]">
          {{ displaySubtitle }}
        </p>
      </div>

      <!-- Ultra-Slim Glowing Progress Line (3px Bar) -->
      <div class="w-full max-w-[240px] space-y-2 mb-4">
        <div class="h-1 w-full rounded-full bg-zinc-200/80 dark:bg-zinc-800/80 overflow-hidden relative shadow-inner">
          <div
            :class="[
              'h-full rounded-full transition-all duration-300 ease-out relative',
              isSuccess
                ? 'bg-gradient-to-r from-emerald-500 to-teal-400 shadow-[0_0_12px_rgba(16,185,129,0.8)]'
                : 'bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-500 shadow-[0_0_12px_rgba(56,189,248,0.7)]'
            ]"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
      </div>

      <!-- Sleek Current Phase Capsule -->
      <div
        :class="[
          'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-medium border shadow-xs backdrop-blur-md transition-all duration-300',
          isSuccess
            ? 'bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300'
            : 'bg-zinc-100/90 dark:bg-zinc-900/90 border-zinc-200/80 dark:border-zinc-800/80 text-zinc-700 dark:text-zinc-300'
        ]"
      >
        <span
          :class="[
            'w-1.5 h-1.5 rounded-full shrink-0',
            isSuccess ? 'bg-emerald-500' : 'bg-sky-500 animate-ping-dot'
          ]"
        ></span>
        <span class="truncate max-w-[190px]">
          {{ isSuccess ? (lang === 'km' ? 'ផ្ទៀងផ្ទាត់រួចរាល់' : 'Verification Complete') : (steps[activeStepIndex]?.label || (lang === 'km' ? 'កំពុងដំណើរការ' : 'Processing')) }}
        </span>
        <span class="text-[10px] font-mono text-zinc-400 dark:text-zinc-500 ml-1">
          {{ progress }}%
        </span>
      </div>

      <!-- Whisper-Light Security Watermark -->
      <div class="mt-5 flex items-center gap-1.5 text-[11px] text-zinc-400 dark:text-zinc-500 font-medium">
        <svg class="w-3.5 h-3.5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <span>{{ lang === 'km' ? 'ការតភ្ជាប់សុវត្ថិភាព SPI Secure Gateway' : 'SPI Secure Gateway' }}</span>
      </div>

    </div>

  </div>
</template>

<style scoped>
@keyframes spinSmooth {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@keyframes ambientBreathe {
  0%, 100% {
    opacity: 0.35;
    transform: scale(1.05);
  }
  50% {
    opacity: 0.65;
    transform: scale(1.18);
  }
}

@keyframes floatMicro {
  0%, 100% {
    transform: translateY(0px) scale(1);
  }
  50% {
    transform: translateY(-2px) scale(1.02);
  }
}

@keyframes scaleBounce {
  0% {
    transform: scale(0.6);
    opacity: 0;
  }
  60% {
    transform: scale(1.12);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes checkmarkDraw {
  0% {
    stroke-dashoffset: 45;
    opacity: 0;
  }
  100% {
    stroke-dashoffset: 0;
    opacity: 1;
  }
}

@keyframes dotPulse {
  0%, 100% {
    opacity: 0.2;
    transform: translateY(0px);
  }
  50% {
    opacity: 1;
    transform: translateY(-2px);
  }
}

@keyframes pingDot {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 4px rgba(56, 189, 248, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(56, 189, 248, 0);
  }
}

.animate-spin-smooth {
  animation: spinSmooth 1.6s linear infinite;
}

.animate-ambient-breathe {
  animation: ambientBreathe 3s ease-in-out infinite;
}

.animate-float-micro {
  animation: floatMicro 3s ease-in-out infinite;
}

.animate-scale-bounce {
  animation: scaleBounce 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

.checkmark-path {
  stroke-dasharray: 45;
  stroke-dashoffset: 45;
  animation: checkmarkDraw 0.45s cubic-bezier(0.65, 0, 0.45, 1) 0.1s forwards;
}

.dot-bounce {
  display: inline-block;
  animation: dotPulse 1.2s infinite ease-in-out;
}

.delay-150 {
  animation-delay: 0.2s;
}

.delay-300 {
  animation-delay: 0.4s;
}

.animate-ping-dot {
  animation: pingDot 1.8s infinite;
}
</style>
