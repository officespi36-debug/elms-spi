<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  currentLang: {
    type: String,
    default: 'km'
  }
})

const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true)
const isChecking = ref(false)
let checkTimer = null

const checkRealConnectivity = async () => {
  if (typeof navigator === 'undefined') return

  // 1. If OS or Browser explicitly says offline
  if (!navigator.onLine) {
    isOnline.value = false
    return
  }

  isChecking.value = true

  // 2. Active Ping Test to verify real internet connectivity beyond local loopback
  try {
    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), 2500)

    await fetch(`https://1.1.1.1/cdn-cgi/trace?_=${Date.now()}`, {
      mode: 'no-cors',
      cache: 'no-store',
      signal: controller.signal
    })

    clearTimeout(timeoutId)
    isOnline.value = true
  } catch (e1) {
    // Try secondary fallback ping (Google favicon)
    try {
      const controller2 = new AbortController()
      const timeoutId2 = setTimeout(() => controller2.abort(), 2000)

      await fetch(`https://www.google.com/favicon.ico?_=${Date.now()}`, {
        mode: 'no-cors',
        cache: 'no-store',
        signal: controller2.signal
      })

      clearTimeout(timeoutId2)
      isOnline.value = true
    } catch (e2) {
      // Both external internet pings failed -> Real Offline
      isOnline.value = false
    }
  } finally {
    isChecking.value = false
  }
}

const handleOnlineEvent = () => {
  checkRealConnectivity()
}

const handleOfflineEvent = () => {
  isOnline.value = false
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('online', handleOnlineEvent)
    window.addEventListener('offline', handleOfflineEvent)
    window.addEventListener('focus', checkRealConnectivity)
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        checkRealConnectivity()
      }
    })

    // Initial check
    checkRealConnectivity()

    // Periodic Heartbeat check every 4 seconds
    checkTimer = setInterval(checkRealConnectivity, 4000)
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('online', handleOnlineEvent)
    window.removeEventListener('offline', handleOfflineEvent)
    window.removeEventListener('focus', checkRealConnectivity)
    if (checkTimer) clearInterval(checkTimer)
  }
})
</script>

<template>
  <button
    type="button"
    @click="checkRealConnectivity"
    :class="[
      'group h-8 px-2.5 sm:px-3 rounded-full flex items-center gap-1.5 text-xs font-semibold select-none transition-all duration-200 border backdrop-blur-md shadow-xs cursor-pointer active:scale-95',
      isOnline
        ? 'bg-emerald-500/10 hover:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/25 dark:border-emerald-500/30'
        : 'bg-amber-500/10 hover:bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/25 dark:border-amber-500/30'
    ]"
    :title="isOnline
      ? (currentLang === 'km' ? 'ស្ថានភាព៖ មានអ៊ីនធឺណិត (Online) — ចុចដើម្បី Refresh' : 'Status: Online & Connected — Click to refresh')
      : (currentLang === 'km' ? 'ស្ថានភាព៖ គ្មានអ៊ីនធឺណិត (Offline) — ចុចដើម្បី Refresh' : 'Status: Offline (No Internet) — Click to refresh')"
  >
    <!-- Crisp Vector Wifi / Signal Icon -->
    <div class="flex items-center justify-center shrink-0">
      <!-- Checking Spinner -->
      <svg
        v-if="isChecking"
        class="w-3.5 h-3.5 animate-spin opacity-70"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>

      <!-- Wifi Connected Icon (Online) -->
      <svg
        v-else-if="isOnline"
        class="w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400 transition-transform duration-200 group-hover:scale-110"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.3"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M5 12.55a11 11 0 0 1 14.08 0" />
        <path d="M1.42 9a16 16 0 0 1 21.16 0" />
        <path d="M8.53 16.11a6 6 0 0 1 6.95 0" />
        <line x1="12" y1="20" x2="12.01" y2="20" />
      </svg>

      <!-- Wifi Slashed / Offline Icon (Offline) -->
      <svg
        v-else
        class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400 transition-transform duration-200 group-hover:scale-110"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.3"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <line x1="1" y1="1" x2="23" y2="23" />
        <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55" />
        <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39" />
        <path d="M10.71 5.05A16 16 0 0 1 22.58 9" />
        <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88" />
        <path d="M8.53 16.11a6 6 0 0 1 6.95 0" />
        <line x1="12" y1="20" x2="12.01" y2="20" />
      </svg>
    </div>

    <!-- Text Label -->
    <span class="text-[11px] font-bold tracking-wide">
      {{ isOnline ? 'Online' : 'Offline' }}
    </span>
  </button>
</template>
