<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    isSuccess?: boolean
    isError?: boolean
    errorMessage?: string
    title?: string
    subtitle?: string
    logoUrl?: string
    lang?: 'km' | 'en'
    mode?: 'login' | 'register' | 'otp' | 'reset'
    provider?: 'google' | 'telegram' | 'github' | 'email' | 'phone' | 'general'
  }>(),
  {
    isSuccess: false,
    isError: false,
    errorMessage: '',
    title: '',
    subtitle: '',
    logoUrl: '/images/logo.png',
    lang: 'km',
    mode: 'login',
    provider: 'general',
  }
)

const emit = defineEmits<{
  (e: 'retry'): void
}>()

// Active percentage counter (0% to 100%)
const progress = ref(8)
let progressTimer: any = null
let animFrameId: number | null = null

// Guaranteed minimum time for showing orbital loader
const MIN_LOADING_MS = 1600
const mountTime = ref(Date.now())
const isVisualSuccess = ref(false)
const hasFailed = ref(false)

// Deferred titles
const currentTitle = ref('')
const currentSubtitle = ref('')
const pendingSuccessTitle = ref('')
const pendingSuccessSubtitle = ref('')

// Circle Geometry: r = 52 => C = 2 * PI * 52 ≈ 326.726
const RADIUS = 52
const CIRCUMFERENCE = 2 * Math.PI * RADIUS

const strokeDashoffset = computed(() => {
  const p = Math.min(100, Math.max(0, progress.value))
  return CIRCUMFERENCE * (1 - p / 100)
})

// 16 Sparks Particles (Emerald & Gold)
const sparks = ref<Array<{ x: number; y: number; color: string; delay: number }>>([])
const generateSparks = () => {
  const count = 16
  const list = []
  const colors = ['#2bf5b0', '#ffd36b', '#7dd8ff', '#00b87a']
  for (let i = 0; i < count; i++) {
    const angle = (i * (360 / count) * Math.PI) / 180
    const distance = 52 + Math.random() * 24
    list.push({
      x: Math.cos(angle) * distance,
      y: Math.sin(angle) * distance,
      color: colors[i % colors.length],
      delay: Math.random() * 0.12,
    })
  }
  sparks.value = list
}

// Detected Provider (Google, GitHub, Email, Telegram)
const detectedProvider = computed<'google' | 'telegram' | 'github' | 'email' | 'phone'>(() => {
  if (props.provider && props.provider !== 'general') {
    return props.provider as any
  }
  const text = `${props.title || ''} ${props.subtitle || ''}`.toLowerCase()
  if (text.includes('google') || text.includes('ហ្គូហ្គល')) return 'google'
  if (text.includes('github') || text.includes('ហ្គីតហាប់')) return 'github'
  if (text.includes('email') || text.includes('អុីមែល') || text.includes('អ៊ីមែល')) return 'email'
  if (text.includes('phone') || text.includes('ទូរស័ព្ទ')) return 'phone'
  return 'telegram'
})

// 3-Step Real-time Pipeline
const steps = computed(() => {
  if (props.mode === 'register') {
    return [
      {
        title: props.lang === 'km' ? 'បង្កើតទិន្នន័យសម្គាល់' : 'Identity Generation',
        status: progress.value >= 30 ? 'done' : progress.value > 0 ? 'running' : 'pending',
      },
      {
        title: props.lang === 'km' ? 'ផ្ទៀងផ្ទាត់សុវត្ថិភាព' : 'Security Verification',
        status: progress.value >= 65 ? 'done' : progress.value >= 30 ? 'running' : 'pending',
      },
      {
        title: props.lang === 'km' ? 'រៀបចំគណនីសិក្សា' : 'Workspace Setup',
        status: isVisualSuccess.value ? 'done' : progress.value >= 65 ? 'running' : 'pending',
      },
    ]
  }

  if (detectedProvider.value === 'google') {
    return [
      {
        title: props.lang === 'km' ? 'តភ្ជាប់សេវាកម្ម Google Cloud' : 'Connect Google Cloud',
        status: progress.value >= 25 ? 'done' : progress.value > 0 ? 'running' : 'pending',
      },
      {
        title: props.lang === 'km' ? 'ផ្ទៀងផ្ទាត់គណនី Google OAuth' : 'Verify Google OAuth',
        status: progress.value >= 65 ? 'done' : progress.value >= 25 ? 'running' : 'pending',
      },
      {
        title: props.lang === 'km' ? 'រៀបចំផ្ទាំងគ្រប់គ្រង E-LMS' : 'Initialize E-LMS Session',
        status: isVisualSuccess.value || progress.value >= 95 ? 'done' : progress.value >= 65 ? 'running' : 'pending',
      },
    ]
  }

  if (detectedProvider.value === 'github') {
    return [
      {
        title: props.lang === 'km' ? 'តភ្ជាប់សេវាកម្ម GitHub API' : 'Connect GitHub API',
        status: progress.value >= 25 ? 'done' : progress.value > 0 ? 'running' : 'pending',
      },
      {
        title: props.lang === 'km' ? 'ផ្ទៀងផ្ទាត់គណនី GitHub' : 'Verify GitHub Account',
        status: progress.value >= 65 ? 'done' : progress.value >= 25 ? 'running' : 'pending',
      },
      {
        title: props.lang === 'km' ? 'រៀបចំផ្ទាំងគ្រប់គ្រង E-LMS' : 'Initialize E-LMS Session',
        status: isVisualSuccess.value || progress.value >= 95 ? 'done' : progress.value >= 65 ? 'running' : 'pending',
      },
    ]
  }

  if (detectedProvider.value === 'email') {
    return [
      {
        title: props.lang === 'km' ? 'ផ្ញើលេខកូដ OTP ទៅកាន់អ៊ីមែល' : 'Send Email OTP',
        status: progress.value >= 28 ? 'done' : progress.value > 0 ? 'running' : 'pending',
      },
      {
        title: props.lang === 'km' ? 'ផ្ទៀងផ្ទាត់លេខកូដសម្ងាត់' : 'Verify OTP Code',
        status: progress.value >= 65 ? 'done' : progress.value >= 28 ? 'running' : 'pending',
      },
      {
        title: props.lang === 'km' ? 'បង្កើត Session សុវត្ថិភាព' : 'Create Secure Session',
        status: isVisualSuccess.value || progress.value >= 95 ? 'done' : progress.value >= 65 ? 'running' : 'pending',
      },
    ]
  }

  // Default login / telegram auth
  return [
    {
      title: props.lang === 'km' ? 'ទទួលបានលេខកូដ OTP' : 'Receive OTP Code',
      status: progress.value >= 28 ? 'done' : progress.value > 0 ? 'running' : 'pending',
    },
    {
      title: props.lang === 'km' ? 'ផ្ទៀងផ្ទាត់គណនី Telegram' : 'Verify Telegram Account',
      status: progress.value >= 65 ? 'done' : progress.value >= 28 ? 'running' : 'pending',
    },
    {
      title: props.lang === 'km' ? 'បង្កើត Session សុវត្ថិភាព' : 'Create Secure Session',
      status: isVisualSuccess.value || progress.value >= 95 ? 'done' : progress.value >= 65 ? 'running' : 'pending',
    },
  ]
})

// Progress runner simulation (glides smoothly up to ~88-95% waiting for server)
const startProgress = () => {
  mountTime.value = Date.now()
  progress.value = 10

  if (progressTimer) clearInterval(progressTimer)

  progressTimer = setInterval(() => {
    if (isVisualSuccess.value || hasFailed.value) {
      clearInterval(progressTimer)
      return
    }

    if (progress.value < 28) {
      progress.value += Math.floor(Math.random() * 5) + 3
    } else if (progress.value < 65) {
      progress.value += Math.floor(Math.random() * 4) + 2
    } else if (progress.value < 88) {
      progress.value += Math.floor(Math.random() * 2) + 1
    } else if (progress.value < 96) {
      if (Math.random() > 0.4) {
        progress.value = Math.min(96, progress.value + 1)
      }
    }
  }, 180)
}

const triggerVisualSuccess = () => {
  const elapsed = Date.now() - mountTime.value
  const waitMs = Math.max(0, MIN_LOADING_MS - elapsed)

  setTimeout(() => {
    if (progressTimer) clearInterval(progressTimer)
    // Smoothly glide to 100%
    const smoothStep = setInterval(() => {
      if (progress.value < 100) {
        progress.value = Math.min(100, progress.value + 4)
      } else {
        clearInterval(smoothStep)
        isVisualSuccess.value = true
        generateSparks()

        if (pendingSuccessTitle.value) {
          currentTitle.value = pendingSuccessTitle.value
        } else {
          currentTitle.value = props.lang === 'km' ? 'ផ្ទៀងផ្ទាត់ជោគជ័យ! ✨' : 'Access Granted! ✨'
        }

        if (pendingSuccessSubtitle.value) {
          currentSubtitle.value = pendingSuccessSubtitle.value
        } else {
          currentSubtitle.value = props.lang === 'km'
            ? 'សូមរង់ចាំមួយភ្លែត កំពុងនាំអ្នកទៅកាន់ Dashboard...'
            : 'Redirecting to your dashboard...'
        }
      }
    }, 20)
  }, waitMs)
}

watch(
  () => props.isSuccess,
  (val) => {
    if (val) {
      triggerVisualSuccess()
    } else {
      isVisualSuccess.value = false
    }
  }
)

watch(
  () => props.isError,
  (val) => {
    if (val) {
      if (progressTimer) clearInterval(progressTimer)
      hasFailed.value = true
    } else {
      hasFailed.value = false
    }
  }
)

watch(
  () => props.title,
  (newTitle) => {
    if (!newTitle) return
    const isSuccessText = /ជោគជ័យ|Success|Granted/i.test(newTitle)
    if (isSuccessText && !isVisualSuccess.value) {
      pendingSuccessTitle.value = newTitle
    } else {
      currentTitle.value = newTitle
    }
  },
  { immediate: true }
)

watch(
  () => props.subtitle,
  (newSub) => {
    if (!newSub) return
    if (!isVisualSuccess.value && props.isSuccess) {
      pendingSuccessSubtitle.value = newSub
    } else {
      currentSubtitle.value = newSub
    }
  },
  { immediate: true }
)

onMounted(() => {
  startProgress()
  if (props.isSuccess) {
    triggerVisualSuccess()
  }
})

onUnmounted(() => {
  if (progressTimer) clearInterval(progressTimer)
  if (animFrameId) cancelAnimationFrame(animFrameId)
})

const displayTitle = computed(() => {
  if (hasFailed.value) {
    return props.lang === 'km' ? 'ការផ្ទៀងផ្ទាត់មិនជោគជ័យ' : 'Verification Failed'
  }
  if (currentTitle.value) return currentTitle.value
  if (isVisualSuccess.value) {
    return props.lang === 'km' ? 'ផ្ទៀងផ្ទាត់ជោគជ័យ! ✨' : 'Verification Complete! ✨'
  }
  return props.lang === 'km' ? 'កំពុងផ្ទៀងផ្ទាត់ Telegram' : 'Verifying Telegram...'
})

const displaySubtitle = computed(() => {
  let sub = ''
  if (hasFailed.value) {
    sub = props.errorMessage || (props.lang === 'km' ? 'លេខកូដ OTP មិនត្រឹមត្រូវ ឬបានផុតកំណត់សុពលភាព។' : 'Invalid or expired OTP code.')
  } else if (currentSubtitle.value) {
    sub = currentSubtitle.value
  } else if (isVisualSuccess.value) {
    sub = props.lang === 'km' ? 'សូមរង់ចាំមួយភ្លែត កំពុងនាំអ្នកទៅកាន់ Dashboard...' : 'Redirecting to your dashboard...'
  } else {
    sub = props.lang === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងផ្ទៀងផ្ទាត់ និងបង្កើត Session សុវត្ថិភាព' : 'Please wait a moment while verifying...'
  }

  // Prevent hyphenated brand terms like Sign-In from breaking awkwardly across lines
  return sub.replace(/Sign-In/g, 'Sign\u2011In')
})

const cleanTitle = computed(() => {
  const t = displayTitle.value || ''
  return t.replace(/(\.{2,}|…)+$/g, '').trim()
})
</script>

<template>
  <!-- Full Atmosphere Dark Premium Container -->
  <div class="w-full max-w-md mx-auto flex flex-col items-center justify-center text-center py-6 select-none animate-fade-in my-auto px-4 relative">
    
    <!-- Ambient Radial Glow -->
    <div
      :class="[
        'absolute w-[360px] h-[360px] rounded-full blur-[60px] pointer-events-none transition-all duration-700',
        hasFailed
          ? 'bg-red-500/15'
          : isVisualSuccess
            ? 'bg-emerald-500/15 dark:bg-emerald-400/25'
            : 'bg-emerald-500/10 dark:bg-[#2bf5b0]/15'
      ]"
    ></div>

    <!-- Main Card Body -->
    <main
      class="relative z-10 w-full flex flex-col items-center"
      role="progressbar"
      :aria-valuenow="Math.round(progress)"
      aria-valuemin="0"
      aria-valuemax="100"
    >
      <!-- ======================================================== -->
      <!-- HERO DISC & ORBIT SYSTEM                                 -->
      <!-- ======================================================== -->
      <div v-if="!hasFailed" class="relative w-32 h-32 mb-6 flex items-center justify-center">
        
        <!-- Breathing Outer Halo -->
        <div
          :class="[
            'absolute -inset-2 rounded-full blur-xl pointer-events-none transition-all duration-500',
            isVisualSuccess
              ? 'bg-gradient-to-tr from-[#2bf5b0]/50 via-teal-400/40 to-[#ffd36b]/30 scale-125'
              : 'bg-gradient-to-tr from-[#2bf5b0]/25 via-sky-500/20 to-teal-500/25 animate-ambient-breathe'
          ]"
        ></div>

        <!-- Exploding Ripple Wave on 100% Finish -->
        <div
          v-if="isVisualSuccess"
          class="absolute -inset-4 rounded-full border-2 border-[#2bf5b0] animate-ripple-out pointer-events-none"
        ></div>

        <!-- SVG Orbital Track Ring + Dynamic Gradient Arc -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none -rotate-90 overflow-visible" viewBox="0 0 120 120">
          <defs>
            <linearGradient id="orbitGradPrime" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#7dd8ff" />
              <stop offset="50%" stop-color="#2bf5b0" />
              <stop offset="100%" stop-color="#00b87a" />
            </linearGradient>

            <filter id="orbGlowPrime" x="-50%" y="-50%" width="200%" height="200%">
              <feGaussianBlur stdDeviation="3" result="blur" />
              <feMerge>
                <feMergeNode in="blur" />
                <feMergeNode in="SourceGraphic" />
              </feMerge>
            </filter>
          </defs>

          <!-- 1. Background Circular Track -->
          <circle
            cx="60"
            cy="60"
            :r="RADIUS"
            fill="none"
            class="stroke-zinc-200/90 dark:stroke-white/[0.08]"
            stroke-width="3.5"
          />

          <!-- 2. Active Smooth Gradient Fill Arc -->
          <circle
            cx="60"
            cy="60"
            :r="RADIUS"
            fill="none"
            stroke="url(#orbitGradPrime)"
            stroke-width="4"
            stroke-linecap="round"
            :stroke-dasharray="CIRCUMFERENCE"
            :stroke-dashoffset="strokeDashoffset"
            class="transition-all duration-300 ease-out"
          />

          <!-- 3. Satellite Orbit Dot (Running on track during loading) -->
          <g
            v-if="!isVisualSuccess"
            class="auth-orbit-satellite"
            style="transform-origin: 60px 60px; animation: spinSmooth 2.8s linear infinite;"
          >
            <circle cx="112" cy="60" r="4.5" fill="#059669" class="dark:fill-[#2bf5b0]" filter="url(#orbGlowPrime)" />
            <circle cx="112" cy="60" r="2" fill="#ffffff" />
          </g>
        </svg>

        <!-- 16 Bursting Sparks Particles on Success -->
        <div v-if="isVisualSuccess" class="absolute inset-0 pointer-events-none z-20">
          <span
            v-for="(s, idx) in sparks"
            :key="idx"
            class="spark-particle"
            :style="{
              '--target-x': `${s.x}px`,
              '--target-y': `${s.y}px`,
              '--spark-color': s.color,
              animationDelay: `${s.delay}s`
            }"
          ></span>
        </div>

        <!-- Central Disc (Live Numeric Counter or Animated Checkmark) -->
        <div
          :class="[
            'relative z-10 w-22 h-22 rounded-full flex items-center justify-center transition-all duration-500 shadow-xl border',
            isVisualSuccess
              ? 'bg-gradient-to-br from-[#2bf5b0] via-[#00b87a] to-emerald-600 border-white/60 shadow-[0_12px_36px_rgba(43,245,176,0.55),inset_0_2px_6px_rgba(255,255,255,0.6)] scale-106'
              : 'bg-white dark:bg-gradient-to-br dark:from-[#092019] dark:to-[#03140e] border-emerald-400/40 dark:border-[#2bf5b0]/35 shadow-[0_8px_24px_rgba(16,185,129,0.15),inset_0_2px_4px_rgba(16,185,129,0.08)] dark:shadow-[0_8px_24px_rgba(0,0,0,0.6),inset_0_2px_4px_rgba(43,245,176,0.2)]'
          ]"
        >
          <!-- State A: Animated Drawing Checkmark on Finish -->
          <div v-if="isVisualSuccess" class="flex items-center justify-center">
            <svg class="w-11 h-11 text-white drop-shadow-[0_2px_6px_rgba(0,0,0,0.3)]" viewBox="0 0 48 48" fill="none">
              <path
                d="M12 24.5L20.5 33L36 16"
                stroke="currentColor"
                stroke-width="4.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="checkmark-path"
              />
            </svg>
          </div>

          <!-- State B: Active Numeric Counter (0% -> 100%) -->
          <div v-else class="flex items-baseline font-bold font-mono tracking-tight text-emerald-600 dark:text-[#2bf5b0]">
            <span class="text-2xl sm:text-[26px] leading-none">{{ Math.round(progress) }}</span>
            <span class="text-xs ml-0.5 opacity-80">%</span>
          </div>
        </div>

        <!-- Floating Docked Provider Badge -->
        <div
          :class="[
            'absolute bottom-0 right-0 z-20 w-8 h-8 rounded-full border-2 border-white dark:border-[#03100c] flex items-center justify-center transition-all duration-300 animate-badge-float overflow-hidden',
            detectedProvider === 'google'
              ? 'bg-white shadow-[0_4px_12px_rgba(66,133,244,0.35)]'
              : detectedProvider === 'github'
                ? 'bg-zinc-900 border-zinc-700 shadow-[0_4px_12px_rgba(0,0,0,0.4)]'
                : detectedProvider === 'email'
                  ? 'bg-sky-500 shadow-[0_4px_12px_rgba(14,165,233,0.35)]'
                  : isVisualSuccess
                    ? 'bg-white shadow-[0_4px_12px_rgba(0,184,122,0.4)]'
                    : 'bg-[#229ed9] shadow-[0_4px_12px_rgba(34,158,217,0.4)]'
          ]"
        >
          <!-- Google 4-color icon -->
          <svg v-if="detectedProvider === 'google'" class="w-4 h-4" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
          </svg>

          <!-- GitHub Octocat icon -->
          <svg v-else-if="detectedProvider === 'github'" class="w-4 h-4 fill-white" viewBox="0 0 24 24">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
          </svg>

          <!-- Email envelope icon -->
          <svg v-else-if="detectedProvider === 'email'" class="w-4 h-4 text-white fill-none stroke-current" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="16" x="2" y="4" rx="2" />
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
          </svg>

          <!-- Telegram paper plane icon -->
          <svg
            v-else
            :class="['w-4 h-4 fill-current', isVisualSuccess ? 'text-[#00b87a]' : 'text-white']"
            viewBox="0 0 24 24"
          >
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
          </svg>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- ERROR DISC (On Failure)                                  -->
      <!-- ======================================================== -->
      <div v-else class="relative w-32 h-32 mb-6 flex items-center justify-center">
        <div class="relative z-10 w-22 h-22 rounded-full flex items-center justify-center bg-gradient-to-br from-[#2b0b0b] to-[#170505] border border-red-500/50 shadow-[0_10px_30px_rgba(239,68,68,0.3)]">
          <svg class="w-10 h-10 text-red-500" viewBox="0 0 48 48" fill="none">
            <path d="M14 14L34 34M34 14L14 34" stroke="currentColor" stroke-width="4" stroke-linecap="round" class="error-cross-path" />
          </svg>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- TEXT HEADINGS                                            -->
      <!-- ======================================================== -->
      <div class="space-y-1.5 mb-5 w-full max-w-sm mx-auto flex flex-col items-center" aria-live="polite">
        <h3
          :class="[
            'text-lg sm:text-xl font-bold tracking-tight transition-colors duration-300 flex items-center justify-center gap-1.5',
            hasFailed
              ? 'text-red-500 font-bold'
              : isVisualSuccess
                ? 'text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 via-teal-700 to-amber-600 dark:from-[#2bf5b0] dark:via-white dark:to-[#ffd36b] font-extrabold drop-shadow-sm'
                : 'text-transparent bg-clip-text bg-gradient-to-r from-zinc-900 via-emerald-600 to-teal-700 dark:from-white dark:via-[#2bf5b0] dark:to-[#7dd8ff]'
          ]"
        >
          <span>{{ cleanTitle }}</span>
          <span v-if="!isVisualSuccess && !hasFailed" class="inline-flex items-center ml-0.5 text-emerald-600 dark:text-[#2bf5b0] font-bold">
            <span class="dot-bounce">.</span>
            <span class="dot-bounce delay-150">.</span>
            <span class="dot-bounce delay-300">.</span>
          </span>
        </h3>
        <p
          :class="[
            'text-xs sm:text-[13px] leading-relaxed max-w-sm sm:max-w-md w-full px-2 transition-colors duration-300 font-medium text-balance [text-wrap:balance]',
            hasFailed
              ? 'text-red-500'
              : isVisualSuccess
                ? 'text-zinc-700 dark:text-white/80'
                : 'text-zinc-600 dark:text-zinc-400'
          ]"
        >
          {{ displaySubtitle }}
        </p>
      </div>

      <!-- ======================================================== -->
      <!-- ULTRA-SLIM PROGRESS BAR (Active Loading)                 -->
      <!-- ======================================================== -->
      <div v-if="!hasFailed" class="w-full max-w-[260px] relative mb-5">
        <div class="h-1.5 w-full rounded-full bg-zinc-200/90 dark:bg-white/10 overflow-hidden relative shadow-inner">
          <div
            class="h-full rounded-full bg-gradient-to-r from-sky-500 to-emerald-500 dark:from-[#7dd8ff] dark:to-[#2bf5b0] shadow-[0_0_10px_rgba(43,245,176,0.6)] transition-all duration-300 ease-out relative overflow-hidden"
            :style="{ width: `${progress}%` }"
          >
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/70 to-transparent animate-shimmer-sweep pointer-events-none"></div>
          </div>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- 3-STEP REAL-TIME PROCESS PIPELINE                        -->
      <!-- ======================================================== -->
      <div v-if="!hasFailed" class="w-full bg-white/90 dark:bg-white/[0.03] border border-emerald-500/20 dark:border-[#2bf5b0]/15 rounded-2xl p-2.5 mb-5 shadow-sm dark:shadow-lg backdrop-blur-md">
        <div
          v-for="(st, idx) in steps"
          :key="idx"
          class="flex items-center justify-between py-2 px-2.5 border-b border-zinc-200/70 dark:border-white/[0.04] last:border-b-0 transition-colors"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <!-- Indicator Icon -->
            <div class="w-5 h-5 flex items-center justify-center shrink-0">
              <svg v-if="st.status === 'done'" class="w-4.5 h-4.5 text-emerald-600 dark:text-[#2bf5b0]" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
              <div v-else-if="st.status === 'running'" class="w-3.5 h-3.5 rounded-full border-2 border-emerald-600/25 border-t-emerald-600 dark:border-[#2bf5b0]/25 dark:border-t-[#2bf5b0] animate-spin-orbit"></div>
              <div v-else class="w-1.5 h-1.5 rounded-full bg-zinc-300 dark:bg-white/20"></div>
            </div>

            <!-- Title -->
            <span
              :class="[
                'text-xs truncate font-medium',
                st.status === 'done'
                  ? 'text-zinc-900 dark:text-white font-semibold'
                  : st.status === 'running'
                    ? 'text-emerald-700 dark:text-[#2bf5b0] font-semibold'
                    : 'text-zinc-400 dark:text-zinc-500'
              ]"
            >
              {{ st.title }}
            </span>
          </div>

          <!-- Status Badge -->
          <span
            v-if="st.status === 'done'"
            class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 dark:bg-[#2bf5b0]/15 dark:text-[#2bf5b0] font-semibold shrink-0"
          >
            {{ lang === 'km' ? 'រួចរាល់' : 'Done' }}
          </span>
          <span
            v-else-if="st.status === 'running'"
            class="text-[10px] px-2 py-0.5 rounded-full bg-sky-500/10 text-sky-700 dark:bg-[#7dd8ff]/15 dark:text-[#7dd8ff] font-semibold shrink-0"
          >
            {{ lang === 'km' ? 'កំពុងដំណើរការ' : 'Processing' }}
          </span>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- ERROR ACTION RETRY BUTTON                                -->
      <!-- ======================================================== -->
      <div v-if="hasFailed" class="w-full mb-5">
        <button
          type="button"
          class="w-full h-11 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-semibold rounded-xl text-xs sm:text-sm flex items-center justify-center gap-2 shadow-lg shadow-red-500/30 active:scale-[0.99] transition-all cursor-pointer"
          @click="emit('retry')"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 4v6h6M23 20v-6h-6"/>
            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
          </svg>
          <span>{{ lang === 'km' ? 'ព្យាយាមម្តងទៀត' : 'Try Again' }}</span>
        </button>
      </div>

      <!-- ======================================================== -->
      <!-- SECURITY WATERMARK PILL                                  -->
      <!-- ======================================================== -->
      <div
        :class="[
          'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold border backdrop-blur-md transition-all duration-300',
          isVisualSuccess
            ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:bg-[#2bf5b0]/10 dark:border-[#2bf5b0]/40 dark:text-[#2bf5b0] shadow-[0_2px_12px_rgba(43,245,176,0.2)]'
            : 'bg-white/80 dark:bg-white/[0.04] border-zinc-200 dark:border-white/10 text-zinc-600 dark:text-zinc-400 shadow-xs dark:shadow-none'
        ]"
      >
        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-[#2bf5b0]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <span>{{ isVisualSuccess ? (lang === 'km' ? 'ផ្ទៀងផ្ទាត់រួចរាល់ 100%' : 'Verified 100%') : 'SPI Secure Gateway · TLS 1.3' }}</span>
      </div>

    </main>
  </div>
</template>

<style>
@keyframes spinOrbit {
  to { transform: rotate(360deg); }
}

@keyframes spinSmooth {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes ambientBreathe {
  0%, 100% { transform: scale(0.95); opacity: 0.5; }
  50% { transform: scale(1.1); opacity: 0.9; }
}

.animate-ambient-breathe {
  animation: ambientBreathe 3s ease-in-out infinite;
}

@keyframes badgeFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

.animate-badge-float {
  animation: badgeFloat 3s ease-in-out infinite;
}

@keyframes shimmerSweep {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(200%); }
}

.animate-shimmer-sweep {
  animation: shimmerSweep 1.8s infinite;
}

@keyframes rippleExplode {
  0% { transform: scale(0.9); opacity: 1; }
  100% { transform: scale(1.6); opacity: 0; }
}

.animate-ripple-out {
  animation: rippleExplode 1.2s cubic-bezier(0.1, 0.8, 0.3, 1) forwards;
}

@keyframes drawCheckmarkPath {
  to { stroke-dashoffset: 0; }
}

.checkmark-path {
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  animation: drawCheckmarkPath 0.6s cubic-bezier(0.65, 0, 0.45, 1) 0.15s forwards;
}

.error-cross-path {
  stroke-dasharray: 60;
  stroke-dashoffset: 60;
  animation: drawCheckmarkPath 0.5s ease-out forwards;
}

@keyframes burstParticleEffect {
  0% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
  80% { opacity: 0.8; }
  100% { transform: translate(calc(-50% + var(--target-x)), calc(-50% + var(--target-y))) scale(0); opacity: 0; }
}

.spark-particle {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background-color: var(--spark-color);
  box-shadow: 0 0 6px var(--spark-color);
  animation: burstParticleEffect 0.85s cubic-bezier(0.12, 0.8, 0.32, 1) forwards;
}

.animate-spin-orbit {
  animation: spinOrbit 0.8s linear infinite;
}

.dot-bounce {
  animation: dotBlink 1.4s infinite steps(1);
}

@keyframes dotBlink {
  0%, 20% { opacity: 0; }
  40% { opacity: 0.4; }
  60% { opacity: 0.7; }
  80%, 100% { opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
  .animate-ambient-breathe,
  .animate-badge-float,
  .animate-shimmer-sweep,
  .animate-ripple-out,
  .auth-orbit-satellite,
  .spark-particle {
    animation: none !important;
  }
}
</style>
