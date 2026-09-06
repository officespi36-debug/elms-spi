<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import Toast from 'primevue/toast'
import { useAppToast } from '@/composables/useAppToast'
import { i18n } from '@/Services/i18n'

const page = usePage<any>()
const appToast = useAppToast()
const isGlobalProcessing = ref(false)
const currentLang = computed(() => i18n.locale.value)

let lastMethod = ''
let removeStartListener: (() => void) | null = null
let removeFinishListener: (() => void) | null = null
let removeSuccessListener: (() => void) | null = null
let removeErrorListener: (() => void) | null = null

onMounted(() => {
  removeStartListener = router.on('start', (event: any) => {
    const method = event?.detail?.visit?.method?.toLowerCase() || ''
    lastMethod = method
    // Show global processing indicator only for mutating requests (POST, PUT, PATCH, DELETE)
    if (method && method !== 'get') {
      isGlobalProcessing.value = true
    }
  })

  removeFinishListener = router.on('finish', () => {
    isGlobalProcessing.value = false
  })

  removeSuccessListener = router.on('success', () => {
    if (lastMethod && lastMethod !== 'get') {
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

  <!-- Global Action Processing Indicator (for Create / Update / Delete) -->
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="-translate-y-6 opacity-0 scale-95"
    enter-to-class="translate-y-0 opacity-100 scale-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="translate-y-0 opacity-100 scale-100"
    leave-to-class="-translate-y-6 opacity-0 scale-95"
  >
    <div
      v-if="isGlobalProcessing"
      class="fixed top-5 left-1/2 -translate-x-1/2 z-[10000] pointer-events-none select-none"
    >
      <div class="px-4 py-2 rounded-full bg-zinc-900/90 dark:bg-white/90 text-white dark:text-zinc-950 backdrop-blur-md shadow-2xl flex items-center gap-2.5 text-xs font-semibold border border-white/15 dark:border-black/15">
        <i class="pi pi-spin pi-spinner text-sky-400 dark:text-sky-600 text-xs"></i>
        <span>{{ currentLang === 'km' ? 'កំពុងដំណើរការរក្សាទុក...' : 'Processing, please wait...' }}</span>
      </div>
    </div>
  </Transition>
</template>
