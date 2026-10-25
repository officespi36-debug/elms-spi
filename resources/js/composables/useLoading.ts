import { ref } from 'vue'

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

  const hideLoading = (minDisplayMs: number = 0) => {
    clearTimeout(showTimer)
    const elapsed = Date.now() - showStartTime
    const remaining = Math.max(0, minDisplayMs - elapsed)
    clearTimeout(finishTimer)
    if (remaining === 0) {
      isLoading.value = false
    } else {
      finishTimer = setTimeout(() => {
        isLoading.value = false
      }, remaining)
    }
  }

  return {
    isLoading,
    loadingText,
    showLoading,
    hideLoading,
  }
}

