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
      status: isVisualSuccess.value ? 'done' : progress.value >= 65 ? 'running' : 'pending',
    },
  ]
})

// Progress runner simulation (glides smoothly up to ~88% waiting for server)
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
      progress.value += Math.floor(Math.random() * 6) + 4
    } else if (progress.value < 65) {
      progress.value += Math.floor(Math.random() * 4) + 3
    } else if (progress.value < 88) {
      progress.value += Math.floor(Math.random() * 2) + 1
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
  if (hasFailed.value) {
    return props.errorMessage || (props.lang === 'km' ? 'លេខកូដ OTP មិនត្រឹមត្រូវ ឬបានផុតកំណត់សុពលភាព។' : 'Invalid or expired OTP code.')
  }
  if (currentSubtitle.value) return currentSubtitle.value
  if (isVisualSuccess.value) {
    return props.lang === 'km' ? 'សូមរង់ចាំមួយភ្លែត កំពុងនាំអ្នកទៅកាន់ Dashboard...' : 'Redirecting to your dashboard...'
  }
  return props.lang === 'km' ? 'សូមរង់ចាំមួយភ្លែត ប្រព័ន្ធកំពុងផ្ទៀងផ្ទាត់ និងបង្កើត Session សុវត្ថិភាព' : 'Please wait a moment while verifying...'
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
            ? 'bg-emerald-400/25'
            : 'bg-[#2bf5b0]/15'
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
            stroke="rgba(255, 255, 255, 0.08)"
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
            <circle cx="112" cy="60" r="4.5" fill="#2bf5b0" filter="url(#orbGlowPrime)" />
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
              : 'bg-gradient-to-br from-[#092019] to-[#03140e] border-[#2bf5b0]/35 shadow-[0_8px_24px_rgba(0,0,0,0.6),inset_0_2px_4px_rgba(43,245,176,0.2)]'
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
          <div v-else class="flex items-baseline font-bold font-mono tracking-tight text-[#2bf5b0]">
            <span class="text-2xl sm:text-[26px] leading-none">{{ Math.round(progress) }}</span>
            <span class="text-xs ml-0.5 opacity-80">%</span>
          </div>
        </div>

        <!-- Floating Docked Telegram Badge -->
        <div
          :class="[
            'absolute bottom-0 right-0 z-20 w-8 h-8 rounded-full border-2 border-[#03100c] flex items-center justify-center transition-all duration-300 animate-badge-float',
            isVisualSuccess
              ? 'bg-white shadow-[0_4px_12px_rgba(0,184,122,0.4)]'
              : 'bg-[#229ed9] shadow-[0_4px_12px_rgba(34,158,217,0.4)]'
          ]"
        >
          <svg
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
              ? 'text-red-400 font-bold'
              : isVisualSuccess
                ? 'text-transparent bg-clip-text bg-gradient-to-r from-[#2bf5b0] via-white to-[#ffd36b] font-extrabold drop-shadow-sm'
                : 'text-transparent bg-clip-text bg-gradient-to-r from-white via-[#2bf5b0] to-[#7dd8ff]'
          ]"
        >
          <span>{{ cleanTitle }}</span>
          <span v-if="!isVisualSuccess && !hasFailed" class="inline-flex items-center ml-0.5 text-[#2bf5b0] font-bold">
            <span class="dot-bounce">.</span>
            <span class="dot-bounce delay-150">.</span>
            <span class="dot-bounce delay-300">.</span>
          </span>
        </h3>
        <p
          :class="[
            'text-xs sm:text-[13px] leading-relaxed max-w-xs transition-colors duration-300 font-medium',
            hasFailed ? 'text-red-300/80' : isVisualSuccess ? 'text-white/80' : 'text-zinc-400'
          ]"
        >
          {{ displaySubtitle }}
        </p>
      </div>

      <!-- ======================================================== -->
      <!-- ULTRA-SLIM PROGRESS BAR (Active Loading)                 -->
      <!-- ======================================================== -->
      <div v-if="!hasFailed" class="w-full max-w-[260px] relative mb-5">
        <div class="h-1.5 w-full rounded-full bg-white/10 overflow-hidden relative shadow-inner">
          <div
            class="h-full rounded-full bg-gradient-to-r from-[#7dd8ff] to-[#2bf5b0] shadow-[0_0_10px_rgba(43,245,176,0.8)] transition-all duration-300 ease-out relative overflow-hidden"
            :style="{ width: `${progress}%` }"
          >
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/70 to-transparent animate-shimmer-sweep pointer-events-none"></div>
          </div>
        </div>
      </div>

      <!-- ======================================================== -->
      <!-- 3-STEP REAL-TIME PROCESS PIPELINE                        -->
      <!-- ======================================================== -->
      <div v-if="!hasFailed" class="w-full bg-white/[0.03] border border-[#2bf5b0]/15 rounded-2xl p-2.5 mb-5 shadow-lg backdrop-blur-md">
        <div
          v-for="(st, idx) in steps"
          :key="idx"
          class="flex items-center justify-between py-2 px-2.5 border-b border-white/[0.04] last:border-b-0 transition-colors"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <!-- Indicator Icon -->
            <div class="w-5 h-5 flex items-center justify-center shrink-0">
              <svg v-if="st.status === 'done'" class="w-4.5 h-4.5 text-[#2bf5b0]" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
              <div v-else-if="st.status === 'running'" class="w-3.5 h-3.5 rounded-full border-2 border-[#2bf5b0]/25 border-t-[#2bf5b0] animate-spin-orbit"></div>
              <div v-else class="w-1.5 h-1.5 rounded-full bg-white/20"></div>
            </div>

            <!-- Title -->
            <span
              :class="[
                'text-xs truncate font-medium',
                st.status === 'done' ? 'text-white font-semibold' : st.status === 'running' ? 'text-[#2bf5b0] font-semibold' : 'text-zinc-500'
              ]"
            >
              {{ st.title }}
            </span>
          </div>

          <!-- Status Badge -->
          <span
            v-if="st.status === 'done'"
            class="text-[10px] px-2 py-0.5 rounded-full bg-[#2bf5b0]/15 text-[#2bf5b0] font-semibold shrink-0"
          >
            {{ lang === 'km' ? 'រួចរាល់' : 'Done' }}
          </span>
          <span
            v-else-if="st.status === 'running'"
            class="text-[10px] px-2 py-0.5 rounded-full bg-[#7dd8ff]/15 text-[#7dd8ff] font-semibold shrink-0"
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
            ? 'bg-[#2bf5b0]/10 border-[#2bf5b0]/40 text-[#2bf5b0] shadow-[0_2px_12px_rgba(43,245,176,0.2)]'
            : 'bg-white/[0.04] border-white/10 text-zinc-400'
        ]"
      >
        <svg class="w-3.5 h-3.5 text-[#2bf5b0]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
