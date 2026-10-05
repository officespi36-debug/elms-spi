import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const isLoading = ref(false)
const loadingText = ref<string>('')
let showStartTime = 0
let finishTimer: any = null

export function useLoading() {
  const showLoading = (text: string = '') => {
    loadingText.value = text
    showStartTime = Date.now()
    isLoading.value = true
  }

  const hideLoading = (minDisplayMs: number = 700) => {
    const elapsed = Date.now() - showStartTime
    const remaining = Math.max(0, minDisplayMs - elapsed)
    clearTimeout(finishTimer)
    finishTimer = setTimeout(() => {
      isLoading.value = false
    }, remaining)
  }

  return {
    isLoading,
    loadingText,
    showLoading,
    hideLoading,
  }
}

// Hook into Inertia visit lifecycle for automatic page transition loading
if (typeof window !== 'undefined') {
  router.on('start', () => {
    clearTimeout(finishTimer)
    showStartTime = Date.now()
    isLoading.value = true
  })

  const endLoading = () => {
    // Keep it visible for at least 750ms so it doesn't disappear too fast and is clearly visible
    const elapsed = Date.now() - showStartTime
    const remaining = Math.max(0, 750 - elapsed)
    clearTimeout(finishTimer)
    finishTimer = setTimeout(() => {
      isLoading.value = false
    }, remaining)
  }

  router.on('finish', endLoading)
  router.on('error', endLoading)
  router.on('cancel', () => {
    clearTimeout(finishTimer)
    isLoading.value = false
  })
}
