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
  <div class="w-full max-w-sm mx-auto flex flex-col items-center justify-center text-center py-6 select-none animate-fade-in">
    
    <!-- Central Sci-Fi Orbital Holographic System -->
    <div class="relative w-36 h-36 sm:w-40 sm:h-40 mb-6 flex items-center justify-center">
      
      <!-- Ambient Glow Aura -->
      <div
        :class="[
          'absolute inset-0 rounded-full blur-2xl transition-all duration-700 pointer-events-none',
          isSuccess
            ? 'bg-emerald-500/35 scale-125'
            : 'bg-gradient-to-tr from-cyan-500/25 via-blue-600/25 to-indigo-500/25 scale-110 animate-pulse-glow'
        ]"
      ></div>

      <!-- Ring 1: Outer Dashed Radar Scanner (Counter-Rotating) -->
      <svg class="absolute inset-0 w-full h-full animate-spin-reverse pointer-events-none" viewBox="0 0 160 160">
        <circle
          cx="80"
          cy="80"
          r="74"
          fill="none"
          :stroke="isSuccess ? 'rgba(16, 185, 129, 0.4)' : 'rgba(56, 189, 248, 0.25)'"
          stroke-width="1.5"
          stroke-dasharray="6 8"
        />
        <!-- 4 Cyber Corner Ticks -->
        <circle cx="80" cy="6" r="2.5" :fill="isSuccess ? '#10b981' : '#38bdf8'" class="animate-ping-dot" />
        <circle cx="154" cy="80" r="2.5" :fill="isSuccess ? '#10b981' : '#818cf8'" />
        <circle cx="80" cy="154" r="2.5" :fill="isSuccess ? '#10b981' : '#38bdf8'" />
        <circle cx="6" cy="80" r="2.5" :fill="isSuccess ? '#10b981' : '#818cf8'" />
      </svg>

      <!-- Ring 2: Active Dynamic Progress Arc with Glowing Comet Head -->
      <svg class="absolute inset-1.5 w-[calc(100%-12px)] h-[calc(100%-12px)] -rotate-90 pointer-events-none" viewBox="0 0 148 148">
        <defs>
          <linearGradient id="cyberGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#00f2fe" />
            <stop offset="50%" stop-color="#38bdf8" />
            <stop offset="100%" stop-color="#6366f1" />
          </linearGradient>
          <linearGradient id="emeraldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#34d399" />
            <stop offset="100%" stop-color="#059669" />
          </linearGradient>
          <filter id="glowFilter" x="-20%" y="-20%" width="140%" height="140%">
            <feGaussianBlur stdDeviation="3" result="blur" />
            <feComposite in="SourceGraphic" in2="blur" operator="over" />
          </filter>
        </defs>

        <!-- Background Track Ring -->
        <circle
          cx="74"
          cy="74"
          r="66"
          fill="none"
          stroke="currentColor"
          class="text-slate-200/50 dark:text-zinc-800/80"
          stroke-width="3"
        />

        <!-- Active Progress Arc -->
        <circle
          cx="74"
          cy="74"
          r="66"
          fill="none"
          :stroke="isSuccess ? 'url(#emeraldGradient)' : 'url(#cyberGradient)'"
          stroke-width="3.5"
          stroke-linecap="round"
          stroke-dasharray="414.7"
          :stroke-dashoffset="414.7 - (414.7 * progress) / 100"
          filter="url(#glowFilter)"
          class="transition-[stroke-dashoffset] duration-300 ease-out"
        />
      </svg>

      <!-- Ring 3: High-speed Orbital Tracer Dot -->
      <div v-if="!isSuccess" class="absolute inset-2 w-[calc(100%-16px)] h-[calc(100%-16px)] animate-spin-fast pointer-events-none">
        <div class="w-3 h-3 rounded-full bg-cyan-300 shadow-[0_0_12px_#38bdf8] -top-1.5 left-1/2 -translate-x-1/2 absolute"></div>
      </div>

      <!-- Core Glassmorphic Badge with Logo or Success Checkmark -->
      <div
        :class="[
          'relative w-20 h-20 sm:w-22 sm:h-22 rounded-full flex items-center justify-center transition-all duration-500 shadow-2xl backdrop-blur-xl z-10 border',
          isSuccess
            ? 'bg-emerald-500/15 border-emerald-500/60 shadow-[0_0_35px_rgba(16,185,129,0.5)] scale-105'
            : 'bg-white/80 dark:bg-[#091122]/90 border-cyan-400/40 dark:border-cyan-500/30 shadow-[0_0_30px_rgba(56,189,248,0.3)]'
        ]"
      >
        <!-- Success Checkmark -->
        <div v-if="isSuccess" class="flex items-center justify-center animate-scale-bounce">
          <svg class="w-10 h-10 text-emerald-500 dark:text-emerald-400" viewBox="0 0 48 48" fill="none">
            <circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2.5" class="opacity-25" />
            <path
              d="M14 24.5L21 31.5L34 17.5"
              stroke="currentColor"
              stroke-width="3.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="checkmark-path"
            />
          </svg>
        </div>

        <!-- E-LMS Logo with High-Tech Breathing Pulse -->
        <div v-else class="relative flex items-center justify-center group">
          <div class="absolute -inset-1 rounded-full bg-cyan-400/20 blur-sm animate-pulse"></div>
          <img
            :src="logoUrl"
            alt="E-LMS"
            class="w-11 h-11 sm:w-12 sm:h-12 rounded-full object-contain drop-shadow-md relative transform transition-transform duration-500 animate-float-micro"
            onerror="this.src='/logo.png'"
          />
          <!-- Holographic Shimmer Line -->
          <div class="absolute inset-0 rounded-full overflow-hidden pointer-events-none">
            <div class="w-full h-full bg-gradient-to-b from-transparent via-white/20 to-transparent -translate-y-full animate-scanner-sweep"></div>
          </div>
        </div>

      </div>

      <!-- Real-time HUD Percentage Badge -->
      <div
        :class="[
          'absolute -bottom-2.5 z-20 px-2.5 py-0.5 rounded-full text-[11px] font-black tracking-wider flex items-center gap-1.5 shadow-lg border backdrop-blur-md transition-all duration-300',
          isSuccess
            ? 'bg-emerald-500 text-white border-emerald-400 shadow-emerald-500/40 scale-105'
            : 'bg-slate-900/90 text-cyan-400 border-cyan-500/40 shadow-cyan-500/20'
        ]"
      >
        <span v-if="!isSuccess" class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-ping"></span>
        <span>{{ progress }}%</span>
      </div>

    </div>

    <!-- Title & Subtitle with Rich Modern Typography -->
    <div class="space-y-1 mb-5 max-w-xs">
      <h3
        :class="[
          'text-lg sm:text-xl font-black tracking-tight transition-colors duration-300',
          isSuccess ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white'
        ]"
      >
        {{ displayTitle }}
      </h3>
      <p class="text-xs text-slate-500 dark:text-zinc-400 leading-relaxed font-medium">
        {{ displaySubtitle }}
      </p>
    </div>

    <!-- The Running Cyber Progress Track (Process វា រត់) -->
    <div class="w-full max-w-[270px] space-y-2 mb-6">
      <div class="h-2 w-full rounded-full bg-slate-200/80 dark:bg-zinc-800/80 p-0.5 border border-slate-300/40 dark:border-zinc-700/50 shadow-inner relative overflow-hidden">
        <!-- Running Progress Bar -->
        <div
          :class="[
            'h-full rounded-full transition-all duration-300 ease-out relative overflow-hidden',
            isSuccess
              ? 'bg-gradient-to-r from-emerald-500 to-teal-400 shadow-[0_0_15px_rgba(16,185,129,0.8)]'
              : 'bg-gradient-to-r from-cyan-400 via-blue-500 to-indigo-500 shadow-[0_0_15px_rgba(56,189,248,0.7)]'
          ]"
          :style="{ width: `${progress}%` }"
        >
          <!-- Shimmering Laser Ray -->
          <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/60 to-transparent -translate-x-full animate-laser-run"></div>
        </div>
      </div>
      
      <!-- Secondary Status Text Under Bar -->
      <div class="flex items-center justify-between text-[10px] font-semibold text-slate-400 dark:text-zinc-500 px-0.5">
        <span class="flex items-center gap-1">
          <span :class="['w-1.5 h-1.5 rounded-full', isSuccess ? 'bg-emerald-400' : 'bg-blue-400 animate-pulse']"></span>
          {{ isSuccess ? (lang === 'km' ? 'រួចរាល់' : 'Completed') : (lang === 'km' ? 'កំពុងដំណើរការ...' : 'Running...') }}
        </span>
        <span class="font-mono tracking-wider">{{ progress }}/100</span>
      </div>
    </div>

    <!-- Dynamic 3-Step Pipeline Card (Visualizing the Steps) -->
    <div class="w-full max-w-[290px] rounded-2xl bg-white/70 dark:bg-[#111625]/80 border border-slate-200/80 dark:border-zinc-800/90 p-3 shadow-lg backdrop-blur-md space-y-2.5 text-left">
      <div
        v-for="(st, idx) in steps"
        :key="idx"
        :class="[
          'flex items-center justify-between p-2 rounded-xl transition-all duration-300 text-xs',
          activeStepIndex === idx && !isSuccess
            ? 'bg-blue-500/10 dark:bg-blue-500/15 border border-blue-500/30 text-blue-600 dark:text-blue-300 shadow-xs'
            : activeStepIndex > idx || isSuccess
            ? 'text-emerald-600 dark:text-emerald-400 opacity-90'
            : 'text-slate-400 dark:text-zinc-600 opacity-50'
        ]"
      >
        <div class="flex items-center gap-2.5 min-w-0">
          <!-- Step Indicator Icon -->
          <div
            :class="[
              'w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 transition-all',
              activeStepIndex > idx || isSuccess
                ? 'bg-emerald-500 text-white shadow-xs shadow-emerald-500/50'
                : activeStepIndex === idx
                ? 'bg-blue-600 text-white shadow-xs shadow-blue-500/50 animate-pulse'
                : 'bg-slate-200 dark:bg-zinc-800 text-slate-500 dark:text-zinc-500'
            ]"
          >
            <i v-if="activeStepIndex > idx || isSuccess" class="pi pi-check text-[9px]"></i>
            <span v-else>{{ idx + 1 }}</span>
          </div>

          <div class="min-w-0">
            <p class="font-bold text-[11px] truncate leading-tight">{{ st.label }}</p>
            <p class="text-[9px] text-slate-400 dark:text-zinc-500 truncate leading-none mt-0.5">{{ st.sub }}</p>
          </div>
        </div>

        <!-- Right Mini Status Pill -->
        <div class="shrink-0 text-[10px]">
          <span
            v-if="activeStepIndex > idx || isSuccess"
            class="text-emerald-500 dark:text-emerald-400 font-semibold"
          >
            <i class="pi pi-check-circle"></i>
          </span>
          <span
            v-else-if="activeStepIndex === idx"
            class="flex items-center gap-1 font-mono text-blue-500 dark:text-cyan-400"
          >
            <span class="w-1 h-1 rounded-full bg-current animate-ping"></span>
            <span>LIVE</span>
          </span>
          <span v-else class="text-slate-300 dark:text-zinc-700">
            <i class="pi pi-clock"></i>
          </span>
        </div>
      </div>
    </div>

    <!-- Security & Encryption Telemetry Footer Badge -->
    <div class="mt-4 flex items-center gap-2 text-[10px] text-slate-400 dark:text-zinc-500 tracking-wide font-mono">
      <span class="inline-flex items-center gap-1 text-emerald-500 dark:text-emerald-400">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        TLS 1.3
      </span>
      <span>•</span>
      <span>256-BIT AES</span>
      <span>•</span>
      <span>SPI SECURE CLOUD</span>
    </div>

  </div>
</template>

<style scoped>
@keyframes spinFast {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@keyframes spinReverse {
  from {
    transform: rotate(360deg);
  }
  to {
    transform: rotate(0deg);
  }
}

@keyframes pulseGlow {
  0%, 100% {
    opacity: 0.4;
    transform: scale(1.05);
  }
  50% {
    opacity: 0.75;
    transform: scale(1.2);
  }
}

@keyframes floatMicro {
  0%, 100% {
    transform: translateY(0px) scale(1);
  }
  50% {
    transform: translateY(-3px) scale(1.03);
  }
}

@keyframes scannerSweep {
  0% {
    transform: translateY(-100%);
  }
  100% {
    transform: translateY(100%);
  }
}

@keyframes laserRun {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
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

@keyframes scaleBounce {
  0% {
    transform: scale(0.6);
    opacity: 0;
  }
  60% {
    transform: scale(1.15);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.animate-spin-fast {
  animation: spinFast 2.2s linear infinite;
}

.animate-spin-reverse {
  animation: spinReverse 14s linear infinite;
}

.animate-pulse-glow {
  animation: pulseGlow 3s ease-in-out infinite;
}

.animate-float-micro {
  animation: floatMicro 3.5s ease-in-out infinite;
}

.animate-scanner-sweep {
  animation: scannerSweep 2.5s ease-in-out infinite;
}

.animate-laser-run {
  animation: laserRun 1.6s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

.animate-scale-bounce {
  animation: scaleBounce 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

.checkmark-path {
  stroke-dasharray: 45;
  stroke-dashoffset: 45;
  animation: checkmarkDraw 0.5s cubic-bezier(0.65, 0, 0.45, 1) 0.1s forwards;
}
</style>
