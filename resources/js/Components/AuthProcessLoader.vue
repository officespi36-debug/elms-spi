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
</script>

<template>
  <div class="w-full max-w-xs mx-auto flex flex-col items-center justify-center text-center py-6 select-none animate-fade-in">
    
    <!-- Hero Halo & Core Logo (Apple / Linear Minimalist Luxury) -->
    <div class="relative w-28 h-28 sm:w-32 sm:h-32 mb-5 flex items-center justify-center">
      
      <!-- Soft Ambient Glow Bloom -->
      <div
        :class="[
          'absolute inset-0 rounded-full blur-2xl transition-all duration-700 pointer-events-none',
          isSuccess
            ? 'bg-emerald-500/25 scale-125'
            : 'bg-gradient-to-tr from-sky-500/20 via-blue-600/20 to-indigo-500/20 scale-110 animate-ambient-breathe'
        ]"
      ></div>

      <!-- Outer Minimal Spinner Ring -->
      <div
        v-if="!isSuccess"
        class="absolute inset-0 rounded-full border-2 border-transparent border-t-sky-500 border-r-indigo-500/40 animate-spin-smooth pointer-events-none"
      ></div>

      <!-- Outer Subtle Track Ring -->
      <div
        class="absolute inset-0 rounded-full border border-zinc-200/60 dark:border-zinc-800/80 pointer-events-none"
      ></div>

      <!-- Core Glassmorphic Badge -->
      <div
        :class="[
          'relative w-20 h-20 sm:w-22 sm:h-22 rounded-full flex items-center justify-center transition-all duration-500 shadow-xl backdrop-blur-2xl z-10 border',
          isSuccess
            ? 'bg-emerald-500/10 border-emerald-500/40 shadow-emerald-500/20 scale-105'
            : 'bg-white/95 dark:bg-[#141721]/95 border-zinc-200/80 dark:border-zinc-800/80 shadow-black/5 dark:shadow-black/40'
        ]"
      >
        <!-- Success State: Smooth Animated Checkmark -->
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

        <!-- Loading State: Clean Floating E-LMS Logo -->
        <div v-else class="relative flex items-center justify-center">
          <img
            :src="logoUrl"
            alt="E-LMS"
            class="w-11 h-11 sm:w-12 sm:h-12 rounded-full object-contain drop-shadow-sm transform transition-transform duration-500 animate-float-micro"
            onerror="this.src='/logo.png'"
          />
        </div>
      </div>

    </div>

    <!-- Title with Animated Trailing Dots -->
    <div class="space-y-1 mb-4 max-w-xs">
      <h3
        :class="[
          'text-base sm:text-lg font-bold tracking-tight transition-colors duration-300 flex items-center justify-center gap-0.5',
          isSuccess ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-900 dark:text-white'
        ]"
      >
        <span>{{ displayTitle }}</span>
        <span v-if="!isSuccess" class="inline-flex items-center ml-0.5 text-sky-500">
          <span class="dot-bounce">.</span>
          <span class="dot-bounce delay-150">.</span>
          <span class="dot-bounce delay-300">.</span>
        </span>
      </h3>
      <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed max-w-[270px]">
        {{ displaySubtitle }}
      </p>
    </div>

    <!-- Ultra-Slim Glowing Progress Line (Minimalist 3px Bar) -->
    <div class="w-full max-w-[230px] space-y-2 mb-4">
      <div class="h-1 w-full rounded-full bg-zinc-200/80 dark:bg-zinc-800/80 overflow-hidden relative shadow-inner">
        <div
          :class="[
            'h-full rounded-full transition-all duration-300 ease-out relative',
            isSuccess
              ? 'bg-gradient-to-r from-emerald-500 to-teal-400 shadow-[0_0_10px_rgba(16,185,129,0.7)]'
              : 'bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-500 shadow-[0_0_10px_rgba(56,189,248,0.6)]'
          ]"
          :style="{ width: `${progress}%` }"
        ></div>
      </div>
    </div>

    <!-- Sleek Current Phase Capsule (Clean Single-Line Badge Replacing Cluttered Box) -->
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
      <span class="truncate max-w-[200px]">
        {{ isSuccess ? (lang === 'km' ? 'ផ្ទៀងផ្ទាត់រួចរាល់' : 'Verification Complete') : (steps[activeStepIndex]?.label || (lang === 'km' ? 'កំពុងដំណើរការ' : 'Processing')) }}
      </span>
      <span class="text-[10px] font-mono text-zinc-400 dark:text-zinc-500 ml-1">
        {{ progress }}%
      </span>
    </div>

    <!-- Whisper-Light Security Watermark -->
    <div class="mt-5 flex items-center gap-1.5 text-[11px] text-zinc-400 dark:text-zinc-600 font-medium">
      <svg class="w-3.5 h-3.5 text-emerald-500/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
      <span>{{ lang === 'km' ? 'ការតភ្ជាប់សុវត្ថិភាព SPI Secure' : 'SPI Secure Gateway' }}</span>
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
