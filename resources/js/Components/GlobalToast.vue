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
const centerNotice = ref<{ type: 'info' | 'warning'; title: string; message: string } | null>(null)
const currentLang = computed(() => i18n.locale.value)

const dismissCenterNotice = () => {
  centerNotice.value = null
}

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

const showCenterNotice = (type: 'info' | 'warning', message: string) => {
  const title = type === 'warning'
    ? (currentLang.value === 'km' ? 'ការព្រមាន' : 'Warning')
    : (currentLang.value === 'km' ? 'សូមជ្រាប' : 'Notice')
  centerNotice.value = { type, title, message: localizeNoticeMessage(message) }
}

const loadingText = computed(() => {
  if (processingMode.value === 'save') {
    return currentLang.value === 'km' ? 'កំពុងដំណើរការរក្សាទុក' : 'Processing, please wait'
  }
  return currentLang.value === 'km' ? 'សូមរង់ចាំ កំពុងដំណើរការ' : 'Please wait while loading'
})

let lastMethod = ''
let loadingShownAt = 0
let hideDelayTimer: ReturnType<typeof setTimeout> | null = null
let removeStartListener: (() => void) | null = null
let removeFinishListener: (() => void) | null = null
let removeSuccessListener: (() => void) | null = null
let removeErrorListener: (() => void) | null = null

const MIN_LOADER_MS = 450

const hideCenterLoader = (immediate = false) => {
  if (hideDelayTimer) {
    clearTimeout(hideDelayTimer)
    hideDelayTimer = null
  }
  if (immediate || !isGlobalProcessing.value) {
    isGlobalProcessing.value = false
    return
  }
  const elapsed = Date.now() - loadingShownAt
  const remaining = Math.max(0, MIN_LOADER_MS - elapsed)
  if (remaining === 0) {
    isGlobalProcessing.value = false
  } else {
    hideDelayTimer = setTimeout(() => {
      isGlobalProcessing.value = false
      hideDelayTimer = null
    }, remaining)
  }
}

onMounted(() => {
  const forceHide = () => hideCenterLoader(true)
  window.addEventListener('pageshow', forceHide)

  removeStartListener = router.on('start', (event: any) => {
    const visit = event?.detail?.visit
    if (visit?.prefetch) return

    const method = visit?.method?.toLowerCase() || ''
    const rawUrl = (visit?.url?.pathname || visit?.url?.href || visit?.url || '').toString().toLowerCase()
    lastMethod = method

    // NEVER show center loader for logout
    if (rawUrl.includes('logout')) return

    if (hideDelayTimer) {
      clearTimeout(hideDelayTimer)
      hideDelayTimer = null
    }

    if (method && method !== 'get') {
      processingMode.value = 'save'
    } else {
      processingMode.value = 'nav'
    }
    loadingShownAt = Date.now()
    isGlobalProcessing.value = true
  })

  removeFinishListener = router.on('finish', () => {
    hideCenterLoader(false)
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
  hideCenterLoader(true)
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
      showCenterNotice('info', flash.info)
    }
    if (flash.warning || flash.warn) {
      showCenterNotice('warning', flash.warning || flash.warn)
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
      class="fixed inset-0 z-[10000] flex items-center justify-center pointer-events-none select-none"
    >
      <div class="px-7 py-5 rounded-2xl bg-white/95 dark:bg-slate-900/95 text-slate-900 dark:text-slate-100 shadow-2xl border border-purple-200/80 dark:border-purple-500/30 flex flex-col items-center gap-3.5 min-w-[230px]">
        <div class="flex items-center justify-center gap-3.5" aria-hidden="true">
          <span class="elms-dot elms-dot-1"></span>
          <span class="elms-dot elms-dot-2"></span>
          <span class="elms-dot elms-dot-3"></span>
        </div>
        <p class="elms-loading-text text-xs sm:text-sm font-semibold tracking-wide text-slate-700 dark:text-slate-200 flex items-center">
          <span>{{ loadingText }}</span><span class="elms-ellipsis" aria-hidden="true">...</span>
        </p>
      </div>
    </div>
  </Transition>
  <!-- Centered Screen Notice / Confirmation Card (e.g. Continue Learning when not enrolled yet) -->
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div
      v-if="centerNotice && !isGlobalProcessing"
      role="alertdialog"
      aria-modal="true"
      class="fixed inset-0 z-[10001] flex items-center justify-center p-4 pointer-events-none"
    >
      <div class="pointer-events-auto w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-2xl border border-slate-200/90 dark:border-slate-700/80 p-6 flex flex-col items-center text-center gap-3">
        <div
          :class="[
            'w-12 h-12 rounded-full flex items-center justify-center',
            centerNotice.type === 'warning'
              ? 'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400'
              : 'bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400'
          ]"
        >
          <i :class="centerNotice.type === 'warning' ? 'pi pi-exclamation-triangle text-xl' : 'pi pi-info-circle text-xl'"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">
          {{ centerNotice.title }}
        </h3>
        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
          {{ centerNotice.message }}
        </p>
        <button
          type="button"
          @click="dismissCenterNotice"
          class="mt-2 px-6 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900"
        >
          {{ currentLang === 'km' ? 'យល់ព្រម' : 'OK, Got it' }}
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.elms-dot {
  width: 14px;
  height: 14px;
  border-radius: 9999px;
  display: inline-block;
  will-change: transform, opacity;
  animation: elms-dot-travel 0.9s infinite cubic-bezier(0.45, 0.05, 0.55, 0.95);
}
.elms-dot-1 {
  background-color: #a855f7;
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.7);
  animation-delay: 0s;
}
.elms-dot-2 {
  background-color: #6366f1;
  box-shadow: 0 0 12px rgba(99, 102, 241, 0.7);
  animation-delay: 0.16s;
}
.elms-dot-3 {
  background-color: #ec4899;
  box-shadow: 0 0 12px rgba(236, 72, 153, 0.7);
  animation-delay: 0.32s;
}

@keyframes elms-dot-travel {
  0%, 100% {
    transform: translateY(0) scale(0.88);
    opacity: 0.65;
  }
  40% {
    transform: translateY(-6px) scale(1.22);
    opacity: 1;
  }
}

.elms-loading-text {
  animation: elms-text-pulse 1.4s ease-in-out infinite;
}

@keyframes elms-text-pulse {
  0%, 100% { opacity: 0.78; }
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

@media (prefers-reduced-motion: reduce) {
  .elms-dot,
  .elms-loading-text,
  .elms-ellipsis {
    animation: none;
  }
  .elms-ellipsis {
    width: auto;
  }
}
</style>
