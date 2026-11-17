<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import Toast from 'primevue/toast'
import { useAppToast } from '@/composables/useAppToast'
import { useLoading } from '@/composables/useLoading'
import { i18n } from '@/Services/i18n'

const page = usePage<any>()
const appToast = useAppToast()
const { isLoading: isManualLoading } = useLoading()
const isGlobalProcessing = ref(false)
const processingMode = ref<'nav' | 'save'>('nav')
const currentLang = computed(() => i18n.locale.value)

const localizeNoticeMessage = (raw: string): string => {
  const msg = (raw || '').trim()
  // Handle "Khmer text (English text)." pattern cleanly per active UI language
  const match = msg.match(/^(.+?)\s*\(([^()]+)\)\.?$/)
  if (match) {
    const kmPart = match[1].trim()
    const enPart = match[2].trim()
    const hasKhmer = /[\u1780-\u17FF]/.test(kmPart)
    const hasLatin = /[A-Za-z]/.test(enPart)
    if (hasKhmer && hasLatin) {
      return currentLang.value === 'km' ? kmPart : enPart
    }
  }
  return msg
}

const ringDots = [
  { angle: 0, r: 5.2, opacity: 1 },
  { angle: 30, r: 4.8, opacity: 0.96 },
  { angle: 60, r: 4.3, opacity: 0.92 },
  { angle: 90, r: 3.7, opacity: 0.88 },
  { angle: 120, r: 3.0, opacity: 0.84 },
  { angle: 150, r: 2.0, opacity: 0.78 },
  { angle: 180, r: 1.1, opacity: 0.72 },
  { angle: 210, r: 1.8, opacity: 0.78 },
  { angle: 240, r: 2.7, opacity: 0.84 },
  { angle: 270, r: 3.6, opacity: 0.88 },
  { angle: 300, r: 4.3, opacity: 0.94 },
  { angle: 330, r: 4.8, opacity: 0.98 },
]

const loadingText = computed(() => {
  if (processingMode.value === 'save') {
    return currentLang.value === 'km' ? 'កំពុងដំណើរការរក្សាទុក' : 'Processing, please wait'
  }
  return currentLang.value === 'km' ? 'សូមរង់ចាំ កំពុងដំណើរការ' : 'Please wait while loading'
})

const MIN_LOADER_DISPLAY_MS = 550
let loaderShownAt = 0
let lastMethod = ''
let showDelayTimer: ReturnType<typeof setTimeout> | null = null
let maxWaitTimer: ReturnType<typeof setTimeout> | null = null
let removeStartListener: (() => void) | null = null
let removeFinishListener: (() => void) | null = null
let removeCancelListener: (() => void) | null = null
let removeInvalidListener: (() => void) | null = null
let removeExceptionListener: (() => void) | null = null
let removeSuccessListener: (() => void) | null = null
let removeErrorListener: (() => void) | null = null

const hideCenterLoader = (forceImmediate = false) => {
  if (showDelayTimer) {
    clearTimeout(showDelayTimer)
    showDelayTimer = null
  }
  if (maxWaitTimer) {
    clearTimeout(maxWaitTimer)
    maxWaitTimer = null
  }
  if (forceImmediate || !isGlobalProcessing.value) {
    isGlobalProcessing.value = false
    return
  }
  const elapsed = Date.now() - loaderShownAt
  const remaining = Math.max(0, MIN_LOADER_DISPLAY_MS - elapsed)
  if (remaining === 0) {
    isGlobalProcessing.value = false
  } else {
    showDelayTimer = setTimeout(() => {
      showDelayTimer = null
      isGlobalProcessing.value = false
    }, remaining)
  }
}

const handlePageshow = () => hideCenterLoader(true)

onMounted(() => {
  window.addEventListener('pageshow', handlePageshow)

  removeStartListener = router.on('start', (event: any) => {
    const visit = event?.detail?.visit
    if (visit?.prefetch) return

    const method = visit?.method?.toLowerCase() || ''
    const rawUrl = (visit?.url?.pathname || visit?.url?.href || visit?.url || '').toString().toLowerCase()
    lastMethod = method

    // NEVER show center loader for logout
    if (rawUrl.includes('logout')) return

    if (showDelayTimer) {
      clearTimeout(showDelayTimer)
      showDelayTimer = null
    }
    if (maxWaitTimer) {
      clearTimeout(maxWaitTimer)
      maxWaitTimer = null
    }

    if (method && method !== 'get') {
      processingMode.value = 'save'
    } else {
      processingMode.value = 'nav'
    }
    loaderShownAt = Date.now()
    isGlobalProcessing.value = true
    maxWaitTimer = setTimeout(() => {
      hideCenterLoader(true)
    }, 8000)
  })

  removeFinishListener = router.on('finish', () => {
    hideCenterLoader()
  })

  removeCancelListener = router.on('cancel', () => {
    hideCenterLoader()
  })

  removeInvalidListener = router.on('invalid', () => {
    hideCenterLoader()
  })

  removeExceptionListener = router.on('exception', () => {
    hideCenterLoader()
  })

  removeSuccessListener = router.on('success', (event: any) => {
    const rawUrl = (event?.detail?.page?.url || '').toString().toLowerCase()
    const isAuthPage = rawUrl.includes('login') || rawUrl === '/'
    if (lastMethod && lastMethod !== 'get' && lastMethod !== 'logout' && !isAuthPage) {
      setTimeout(() => {
        if (!page.props.flash?.success && !page.props.flash?.status && !page.props.flash?.info) {
          appToast.success(
            currentLang.value === 'km' ? 'ប្រតិបត្តិការត្រូវបានរក្សាទុកដោយជោគជ័យ!' : 'Operation completed successfully!',
            currentLang.value === 'km' ? 'ជោគជ័យ' : 'Success'
          )
        }
      }, 120)
    }
  })

  removeErrorListener = router.on('error', (event: any) => {
    hideCenterLoader()
    const errs = event?.detail?.errors || {}
    const keys = Object.keys(errs)
    if (keys.length > 0) {
      const firstErr = errs[keys[0]]
      if (typeof firstErr === 'string') {
        appToast.error(firstErr, currentLang.value === 'km' ? 'មិនបានជោគជ័យ' : 'Failed')
      }
    }
  })
})

onUnmounted(() => {
  window.removeEventListener('pageshow', handlePageshow)
  hideCenterLoader(true)
  if (removeStartListener) removeStartListener()
  if (removeFinishListener) removeFinishListener()
  if (removeCancelListener) removeCancelListener()
  if (removeInvalidListener) removeInvalidListener()
  if (removeExceptionListener) removeExceptionListener()
  if (removeSuccessListener) removeSuccessListener()
  if (removeErrorListener) removeErrorListener()
})

watch(
  () => page.props.flash,
  (flash) => {
    if (!flash) return
    if (flash.success) {
      appToast.success(flash.success, currentLang.value === 'km' ? 'ជោគជ័យ' : 'Success')
    }
    if (flash.error) {
      appToast.error(flash.error, currentLang.value === 'km' ? 'បរាជ័យ' : 'Failed')
    }
    if (flash.info) {
      appToast.info(localizeNoticeMessage(flash.info), currentLang.value === 'km' ? 'ដំណឹង' : 'Notice')
    }
    if (flash.warning || flash.warn) {
      appToast.warn(localizeNoticeMessage(flash.warning || flash.warn), currentLang.value === 'km' ? 'ការព្រមាន' : 'Warning')
    }
    if (flash.status) {
      appToast.info(flash.status, currentLang.value === 'km' ? 'ដំណឹង' : 'Notice')
    }
  },
  { deep: true, immediate: true }
)

watch(
  () => page.props.errors,
  (errors) => {
    if (!errors) return
    const keys = Object.keys(errors)
    if (keys.length > 0) {
      const firstError = errors[keys[0]]
      if (typeof firstError === 'string') {
        appToast.error(firstError, currentLang.value === 'km' ? 'មិនបានជោគជ័យ' : 'Failed')
      }
    }
  },
  { deep: true }
)

watch(
  () => page.props.status,
  (status) => {
    if (status && typeof status === 'string') {
      appToast.info(status, currentLang.value === 'km' ? 'ដំណឹង' : 'Notice')
    }
  },
  { immediate: true }
)

const getToastTitle = (severity?: string) => {
  const isKm = currentLang.value === 'km'
  if (severity === 'success') return isKm ? 'ជោគជ័យ' : 'Success'
  if (severity === 'error') return isKm ? 'មិនបានជោគជ័យ' : 'Failed'
  if (severity === 'warn') return isKm ? 'ការព្រមាន' : 'Warning'
  return isKm ? 'ដំណឹង' : 'Notice'
}

const getToastBadge = (severity?: string, summary?: string) => {
  const isKm = currentLang.value === 'km'
  const sum = (summary || '').toLowerCase()
  if (sum.includes('ជោគជ័យ') || sum.includes('success')) {
    return isKm ? 'ប្រព័ន្ធ' : 'System'
  }
  if (severity === 'success') return isKm ? 'ជោគជ័យ' : 'Success'
  if (severity === 'error') return isKm ? 'បរាជ័យ' : 'Error'
  if (severity === 'warn') return isKm ? 'ការព្រមាន' : 'Warning'
  return isKm ? 'ដំណឹង' : 'System'
}
</script>

<template>
  <Toast position="top-right" class="!z-[9999]">
    <template #container="{ message, closeCallback }">
      <div
        class="elms-toast-card relative flex items-start gap-3.5 p-4 pr-11 w-full rounded-2xl bg-white/98 dark:bg-[#18191b]/98 border border-slate-200/90 dark:border-slate-800 shadow-[0_16px_36px_-6px_rgba(15,23,42,0.14),0_6px_14px_-2px_rgba(15,23,42,0.06)] dark:shadow-[0_16px_36px_-6px_rgba(0,0,0,0.65),0_6px_14px_-2px_rgba(0,0,0,0.45)] backdrop-blur-md overflow-hidden select-none transition-all duration-200 hover:shadow-2xl"
      >
        <!-- Left vertical accent bar -->
        <div
          class="absolute left-0 top-0 bottom-0 w-1.5"
          :class="{
            'bg-gradient-to-b from-emerald-500 to-teal-600': message.severity === 'success',
            'bg-gradient-to-b from-rose-500 to-red-600': message.severity === 'error',
            'bg-gradient-to-b from-amber-400 to-amber-600': message.severity === 'warn',
            'bg-gradient-to-b from-purple-500 to-indigo-600': message.severity === 'info' || !message.severity,
          }"
        />

        <!-- Icon Squircle (Matching Image 2 style) -->
        <div
          class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-sm"
          :class="{
            'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-800/60': message.severity === 'success',
            'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-800/60': message.severity === 'error',
            'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60': message.severity === 'warn',
            'bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200/80 dark:border-purple-800/60': message.severity === 'info' || !message.severity,
          }"
        >
          <!-- Success Icon -->
          <svg v-if="message.severity === 'success'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <!-- Error Icon -->
          <svg v-else-if="message.severity === 'error'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
          <!-- Warning Icon -->
          <svg v-else-if="message.severity === 'warn'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <!-- Info Icon -->
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>

        <!-- Text Content -->
        <div class="flex-1 min-w-0 pr-1">
          <!-- Title & Pill -->
          <div class="flex items-center gap-2 flex-wrap">
            <h4 class="text-[14px] font-bold text-slate-900 dark:text-white tracking-tight leading-tight">
              {{ message.summary || getToastTitle(message.severity) }}
            </h4>
            <span
              class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold"
              :class="{
                'bg-emerald-100/90 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-300/50 dark:border-emerald-700/50': message.severity === 'success',
                'bg-rose-100/90 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 border border-rose-300/50 dark:border-rose-700/50': message.severity === 'error',
                'bg-amber-100/90 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-300/50 dark:border-amber-700/50': message.severity === 'warn',
                'bg-purple-100/90 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300 border border-purple-300/50 dark:border-purple-700/50': message.severity === 'info' || !message.severity,
              }"
            >
              {{ getToastBadge(message.severity, message.summary) }}
            </span>
          </div>

          <!-- Message Detail: High-contrast, deeply readable text -->
          <p class="text-[13px] font-medium text-slate-600 dark:text-slate-300 leading-relaxed mt-1 select-text break-words">
            {{ message.detail }}
          </p>
        </div>

        <!-- Sleek close button -->
        <button
          v-if="message.closable !== false"
          type="button"
          @click="closeCallback"
          class="absolute top-3.5 right-3.5 w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
          aria-label="Close notification"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Dynamic countdown timer bar along the bottom -->
        <div
          class="elms-toast-progress absolute bottom-0 left-0 h-[2.5px] rounded-b-2xl"
          :style="{ animationDuration: `${message.life || 3500}ms` }"
          :class="{
            'bg-gradient-to-r from-emerald-500 to-teal-500': message.severity === 'success',
            'bg-gradient-to-r from-rose-500 to-red-500': message.severity === 'error',
            'bg-gradient-to-r from-amber-400 to-amber-500': message.severity === 'warn',
            'bg-gradient-to-r from-purple-500 to-indigo-500': message.severity === 'info' || !message.severity,
          }"
        />
      </div>
    </template>
  </Toast>

  <!-- Centered Screen Loading Indicator -->
  <Transition
    enter-active-class="transition duration-200 ease-[cubic-bezier(0.22,1,0.36,1)]"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-200 ease-out"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div
      v-if="isGlobalProcessing && !isManualLoading"
      role="status"
      aria-live="polite"
      class="elms-loader-backdrop fixed z-[10000] flex flex-col items-center justify-center gap-3.5 pointer-events-none select-none overflow-hidden"
    >
      <div class="elms-ring-wrap w-[68px] h-[68px] shrink-0" aria-hidden="true">
        <svg
          class="block w-full h-full"
          viewBox="0 0 64 64"
          fill="none"
        >
          <circle
            v-for="dot in ringDots"
            :key="dot.angle"
            cx="32"
            cy="9"
            :r="dot.r"
            fill="#0099ff"
            :fill-opacity="dot.opacity"
            :transform="`rotate(${dot.angle} 32 32)`"
          />
        </svg>
      </div>
      <p class="elms-loading-text h-[26px] leading-[26px] whitespace-nowrap text-base sm:text-[17px] font-extrabold tracking-wide text-slate-900 dark:text-white flex items-center justify-center">
        <span>{{ loadingText }}</span><span class="elms-ellipsis" aria-hidden="true">...</span>
      </p>
    </div>
  </Transition>
</template>

<style scoped>
.elms-loader-backdrop {
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  contain: strict;
  will-change: opacity;
  transform: translate3d(0, 0, 0);
  backface-visibility: hidden;
  background: radial-gradient(
    circle at center,
    rgba(248, 250, 252, 0.96) 0%,
    rgba(248, 250, 252, 0.86) 32%,
    rgba(248, 250, 252, 0.72) 100%
  );
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

:global(html.dark) .elms-loader-backdrop {
  background: radial-gradient(
    circle at center,
    rgba(11, 19, 43, 0.96) 0%,
    rgba(11, 19, 43, 0.86) 32%,
    rgba(11, 19, 43, 0.74) 100%
  );
}

.elms-ring-wrap {
  transform-origin: center center;
  will-change: transform;
  transform: translate3d(0, 0, 0);
  backface-visibility: hidden;
  filter: drop-shadow(0 2px 8px rgba(0, 153, 255, 0.35));
  animation: elms-ring-spin 1.15s linear infinite;
}

@keyframes elms-ring-spin {
  to {
    transform: translate3d(0, 0, 0) rotate(360deg);
  }
}

.elms-loading-text {
  text-shadow: 0 1px 2px rgba(255, 255, 255, 0.95), 0 0 14px rgba(255, 255, 255, 0.95);
}

:global(html.dark) .elms-loading-text {
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.9), 0 0 14px rgba(11, 19, 43, 0.95);
}

.elms-ellipsis {
  display: inline-block;
  overflow: hidden;
  vertical-align: bottom;
  width: 0;
  animation: elms-ellipsis-run 1.2s steps(4, end) infinite;
}

@keyframes elms-ellipsis-run {
  to { width: 1.25em; }
}

@keyframes elmsToastCountdown {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

.elms-toast-progress {
  animation-name: elmsToastCountdown;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}

.elms-toast-card:hover .elms-toast-progress {
  animation-play-state: paused;
}
</style>
