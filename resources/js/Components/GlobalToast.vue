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

const confirmCenterNotice = () => {
  const msg = centerNotice.value?.message || ''
  centerNotice.value = null

  const needsEnroll = msg.includes('ចុះឈ្មោះ') || msg.toLowerCase().includes('enroll')
  const allCompleted = msg.includes('ទាំងអស់') || msg.toLowerCase().includes('all your courses')
  const currentPath = (page.url || window.location.pathname).split('?')[0]

  if (needsEnroll) {
    if (!currentPath.startsWith('/student/browse')) {
      router.visit('/student/browse')
    } else {
      const target = document.querySelector('main') || document.body
      target.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  } else if (allCompleted && !currentPath.startsWith('/student/my-courses/completed')) {
    router.visit('/student/my-courses/completed')
  }
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
      // Only show loader if navigation takes >150ms so instant/prefetched clicks feel immediate
      showDelayTimer = setTimeout(() => {
        isGlobalProcessing.value = true
      }, 150)
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
      class="fixed inset-0 z-[10000] flex flex-col items-center justify-center gap-3.5 pointer-events-none select-none"
    >
      <div class="flex items-center justify-center gap-3.5" aria-hidden="true">
        <span class="elms-dot elms-dot-1"></span>
        <span class="elms-dot elms-dot-2"></span>
        <span class="elms-dot elms-dot-3"></span>
        <span class="elms-dot elms-dot-4"></span>
      </div>
      <p class="elms-loading-text text-sm sm:text-base font-bold tracking-wide text-slate-800 dark:text-slate-100 drop-shadow-[0_2px_8px_rgba(0,0,0,0.35)] flex items-center">
        <span>{{ loadingText }}</span><span class="elms-ellipsis" aria-hidden="true">...</span>
      </p>
    </div>
  </Transition>
  <!-- Centered Screen Notice / Confirmation Card (e.g. Continue Learning when not enrolled yet) -->
  <Transition
    enter-active-class="transition duration-200 ease-[cubic-bezier(0.22,1,0.36,1)]"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-150 ease-out"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div
      v-if="centerNotice && !isGlobalProcessing"
      role="alertdialog"
      aria-modal="true"
      class="fixed inset-0 z-[10001] flex items-center justify-center p-4 pointer-events-none"
    >
      <div class="pointer-events-auto relative w-full max-w-sm p-[2.5px] rounded-2xl overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.4)]">
        <!-- Animated light beam running around the frame -->
        <div class="elms-frame-runner" aria-hidden="true"></div>

        <div class="relative w-full rounded-[14px] bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-6 py-6 flex flex-col items-center text-center gap-3">
        <!-- Close button -->
        <button
          type="button"
          @click="dismissCenterNotice"
          :aria-label="currentLang === 'km' ? 'បិទ' : 'Close'"
          class="absolute top-3 right-3 w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Icon badge -->
        <div
          :class="[
            'w-12 h-12 rounded-full flex items-center justify-center',
            centerNotice.type === 'warning'
              ? 'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400'
              : 'bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400'
          ]"
        >
          <svg v-if="centerNotice.type === 'warning'" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
          <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
          </svg>
        </div>

        <h3 class="text-base font-bold text-slate-900 dark:text-white">
          {{ centerNotice.title }}
        </h3>
        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
          {{ centerNotice.message }}
        </p>

        <button
          type="button"
          @click="confirmCenterNotice"
          class="mt-1 px-8 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 active:scale-[0.98] text-white text-sm font-semibold shadow-md shadow-purple-500/25 transition-all focus:outline-none focus:ring-2 focus:ring-purple-500"
        >
          {{ currentLang === 'km' ? 'យល់ព្រម' : 'OK, Got it' }}
        </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.elms-dot {
  width: 15px;
  height: 15px;
  border-radius: 9999px;
  display: inline-block;
  will-change: transform, opacity;
  animation: elms-dot-travel 0.9s infinite cubic-bezier(0.45, 0.05, 0.55, 0.95);
}
.elms-dot-1 {
  background: linear-gradient(135deg, #a855f7, #7c3aed);
  box-shadow: 0 0 14px rgba(168, 85, 247, 0.8);
  animation-delay: 0s;
}
.elms-dot-2 {
  background: linear-gradient(135deg, #6366f1, #3b82f6);
  box-shadow: 0 0 14px rgba(99, 102, 241, 0.8);
  animation-delay: 0.14s;
}
.elms-dot-3 {
  background: linear-gradient(135deg, #ec4899, #f43f5e);
  box-shadow: 0 0 14px rgba(236, 72, 153, 0.8);
  animation-delay: 0.28s;
}
.elms-dot-4 {
  background: linear-gradient(135deg, #06b6d4, #10b981);
  box-shadow: 0 0 14px rgba(6, 182, 212, 0.8);
  animation-delay: 0.42s;
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

.elms-frame-runner {
  position: absolute;
  inset: -60%;
  background: conic-gradient(
    from 0deg,
    #a855f7,
    #6366f1,
    #ec4899,
    #06b6d4,
    transparent 45%,
    #a855f7
  );
  will-change: transform;
  animation: elms-frame-spin 3.2s linear infinite;
}

@keyframes elms-frame-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .elms-dot,
  .elms-loading-text,
  .elms-ellipsis,
  .elms-frame-runner {
    animation: none;
  }
  .elms-ellipsis {
    width: auto;
  }
}
</style>
