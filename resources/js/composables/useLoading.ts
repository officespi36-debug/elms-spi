import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const isLoading = ref(false)
const loadingText = ref<string>('Please wait while loading')
let delayTimer: any = null

export function useLoading() {
  const showLoading = (text: string = 'Please wait while loading') => {
    loadingText.value = text
    isLoading.value = true
  }

  const hideLoading = () => {
    clearTimeout(delayTimer)
    isLoading.value = false
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
    clearTimeout(delayTimer)
    // 150ms buffer to avoid flicker on instant local/cached transitions
    delayTimer = setTimeout(() => {
      isLoading.value = true
    }, 150)
  })

  router.on('finish', () => {
    clearTimeout(delayTimer)
    isLoading.value = false
  })
}
