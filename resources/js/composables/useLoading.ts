import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const isLoading = ref(false)
const loadingText = ref<string>('')
let showStartTime = 0
let showTimer: any = null
let finishTimer: any = null

export function useLoading() {
  const showLoading = (text: string = '') => {
    clearTimeout(showTimer)
    clearTimeout(finishTimer)
    loadingText.value = text
    showStartTime = Date.now()
    isLoading.value = true
  }

  const hideLoading = (minDisplayMs: number = 100) => {
    clearTimeout(showTimer)
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

// Hook into Inertia visit lifecycle for fast, responsive and ultra-smooth page transitions
if (typeof window !== 'undefined') {
  router.on('start', () => {
    clearTimeout(showTimer)
    clearTimeout(finishTimer)
    showStartTime = Date.now()
    loadingText.value = ''
    // Only display full overlay if transition takes more than 100ms (prevents jarring flash on quick local/cached visits)
    showTimer = setTimeout(() => {
      isLoading.value = true
    }, 100)
  })

  const endLoading = () => {
    clearTimeout(showTimer)
    if (isLoading.value) {
      // If already shown, close smoothly with tiny buffer so it does not flicker
      const elapsed = Date.now() - showStartTime
      const remaining = Math.max(0, 120 - elapsed)
      clearTimeout(finishTimer)
      finishTimer = setTimeout(() => {
        isLoading.value = false
      }, remaining)
    } else {
      isLoading.value = false
    }
  }

  router.on('finish', endLoading)
  router.on('error', endLoading)
  router.on('cancel', () => {
    clearTimeout(showTimer)
    clearTimeout(finishTimer)
    isLoading.value = false
  })
}
