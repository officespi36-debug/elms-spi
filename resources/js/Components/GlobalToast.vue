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

const loadingText = computed(() => {
  if (processingMode.value === 'save') {
    return currentLang.value === 'km' ? 'កំពុងដំណើរការរក្សាទុក...' : 'Processing, please wait...'
  }
  return currentLang.value === 'km' ? 'សូមរង់ចាំ កំពុងដំណើរការ...' : 'Loading, please wait...'
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
      isGlobalProcessing.value = true
    } else {
      processingMode.value = 'nav'
      showDelayTimer = setTimeout(() => {
        isGlobalProcessing.value = true
      }, 120)
    }
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
      appToast.info(flash.info, currentLang.value === 'km' ? 'ដំណឹង' : 'Notice')
    }
    if (flash.warning || flash.warn) {
      appToast.warn(flash.warning || flash.warn, currentLang.value === 'km' ? 'ការព្រមាន' : 'Warning')
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
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div
      v-if="isGlobalProcessing"
      role="status"
      aria-live="polite"
      class="fixed inset-0 z-[10000] flex items-center justify-center pointer-events-none select-none bg-slate-950/20 dark:bg-slate-950/40 backdrop-blur-[2px]"
    >
      <div class="px-6 py-5 rounded-2xl bg-white/95 dark:bg-slate-900/95 text-slate-900 dark:text-slate-100 shadow-2xl border border-slate-200/80 dark:border-slate-700/70 flex flex-col items-center gap-3 min-w-[200px]">
        <progress :aria-label="loadingText" class="elms-center-spinner"></progress>
        <span class="text-xs sm:text-sm font-semibold tracking-wide text-slate-700 dark:text-slate-200">
          {{ loadingText }}
        </span>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
@property --arc-start {
  syntax: "<angle>";
  inherits: false;
  initial-value: 0deg;
}
@property --arc-end {
  syntax: "<angle>";
  inherits: false;
  initial-value: 0deg;
}
@property --progress-dash-animation {
  syntax: "*";
  inherits: false;
  initial-value: , elms-progress-dash 3s ease-in-out infinite;
}

progress.elms-center-spinner:indeterminate::-webkit-progress-bar {
  display: none;
  background: none;
}
progress.elms-center-spinner:indeterminate::-webkit-progress-value {
  display: none;
  background: none;
}
progress.elms-center-spinner:indeterminate::-moz-progress-bar {
  display: none;
  background: none;
}
progress.elms-center-spinner:indeterminate::slider-fill {
  display: none;
  background: none;
}

progress.elms-center-spinner:indeterminate {
  --_from: calc(90deg + var(--arc-start, 0deg));
  --_to: calc(90deg + var(--arc-end, 158deg));
  --size: 42px;
  --thickness: 3.5px;
  --spinner-color: #7c3aed;
  --track-color: rgba(124, 58, 237, 0.16);
  --spinner-duration: 1.35s;
  --_used-spinner-duration: var(--spinner-duration);

  position: relative;
  width: var(--size);
  height: var(--size);
  border-radius: 50%;
  appearance: none;
  background: conic-gradient(
      from var(--_from),
      var(--spinner-color) calc(var(--_to) - var(--_from)),
      transparent 0
    )
    var(--track-color);

  animation:
    elms-progress-spin var(--_used-spinner-duration) linear infinite
    var(--progress-dash-animation, );
}

@supports (background-clip: border-area) {
  progress.elms-center-spinner:indeterminate {
    background-clip: border-area;
    border: var(--thickness) solid transparent;
    background-origin: border-box;
  }
}

@supports not (background-clip: border-area) {
  progress.elms-center-spinner:indeterminate {
    --clip-boundary: calc(100% - var(--thickness));
    mask-image: radial-gradient(
      farthest-side,
      transparent var(--clip-boundary),
      black var(--clip-boundary)
    );
    border: 0;
  }
}

@media (forced-colors: active) {
  progress.elms-center-spinner:indeterminate {
    forced-color-adjust: none;
    --spinner-color: CanvasText;
    --track-color: Canvas;
  }
}

@media (prefers-reduced-motion: reduce) {
  progress.elms-center-spinner:indeterminate {
    --_used-spinner-duration: 6s;
    --progress-dash-animation: ;
    --arc-end: 158deg;
  }
}

@keyframes elms-progress-spin {
  to {
    rotate: 1turn;
  }
}

@keyframes elms-progress-dash {
  from {
    --arc-start: 0deg;
    --arc-end: 3deg;
  }
  50% {
    --arc-start: 100deg;
    --arc-end: 358deg;
  }
  to {
    --arc-start: 360deg;
    --arc-end: 363deg;
  }
}
</style>
