<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import Toast from 'primevue/toast'
import { useAppToast } from '@/composables/useAppToast'
import { i18n } from '@/Services/i18n'

const page = usePage<any>()
const appToast = useAppToast()
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

let lastMethod = ''
let showDelayTimer: ReturnType<typeof setTimeout> | null = null
let removeStartListener: (() => void) | null = null
let removeFinishListener: (() => void) | null = null
let removeSuccessListener: (() => void) | null = null
let removeErrorListener: (() => void) | null = null

const hideCenterLoader = () => {
  if (showDelayTimer) {
    clearTimeout(showDelayTimer)
    showDelayTimer = null
  }
  isGlobalProcessing.value = false
}

onMounted(() => {
  window.addEventListener('pageshow', hideCenterLoader)

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

    if (method && method !== 'get') {
      processingMode.value = 'save'
    } else {
      processingMode.value = 'nav'
    }
    isGlobalProcessing.value = true
  })

  removeFinishListener = router.on('finish', () => {
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
  window.removeEventListener('pageshow', hideCenterLoader)
  hideCenterLoader()
  if (removeStartListener) removeStartListener()
  if (removeFinishListener) removeFinishListener()
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
</script>

<template>
  <Toast position="top-right" class="z-[9999]" />

  <!-- Centered Screen Loading Indicator -->
  <Transition
    enter-active-class="transition duration-200 ease-[cubic-bezier(0.22,1,0.36,1)]"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-150 ease-out"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div
      v-if="isGlobalProcessing"
      role="status"
      aria-live="polite"
      class="fixed inset-0 z-[10000] flex flex-col items-center justify-center gap-3 pointer-events-none select-none"
    >
      <svg
        class="elms-ring-svg w-[60px] h-[60px]"
        viewBox="0 0 64 64"
        fill="none"
        aria-hidden="true"
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
      <p class="elms-loading-text text-sm sm:text-base font-bold tracking-wide text-slate-900 dark:text-white drop-shadow-[0_2px_10px_rgba(255,255,255,0.85)] dark:drop-shadow-[0_2px_10px_rgba(0,0,0,0.65)] flex items-center">
        <span>{{ loadingText }}</span><span class="elms-ellipsis" aria-hidden="true">...</span>
      </p>
    </div>
  </Transition>
</template>

<style scoped>
.elms-ring-svg {
  transform-origin: center center;
  will-change: transform;
  animation: elms-ring-spin 0.85s linear infinite;
}

@keyframes elms-ring-spin {
  to {
    transform: rotate(360deg);
  }
}

.elms-loading-text {
  animation: elms-text-pulse 1.4s ease-in-out infinite;
}

@keyframes elms-text-pulse {
  0%, 100% { opacity: 0.82; }
  50% { opacity: 1; }
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
</style>
