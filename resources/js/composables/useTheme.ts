import { ref } from 'vue'

export const isDark = ref(false)

export function initTheme() {
  if (typeof window === 'undefined') return
  try {
    const stored = localStorage.getItem('theme')
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
    if (stored === 'dark' || (!stored && prefersDark)) {
      isDark.value = true
      document.documentElement.classList.add('dark')
      document.documentElement.style.colorScheme = 'dark'
      document.documentElement.style.backgroundColor = '#0b132b'
    } else {
      isDark.value = false
      document.documentElement.classList.remove('dark')
      document.documentElement.style.colorScheme = 'light'
      document.documentElement.style.backgroundColor = '#f8fafc'
    }
  } catch (e) {
    isDark.value = true
    document.documentElement.classList.add('dark')
    document.documentElement.style.colorScheme = 'dark'
    document.documentElement.style.backgroundColor = '#0b132b'
  }
}

export function playThemeSound(nextIsDark: boolean) {
  // Disabled as per user requirement: sound only on notification bell
  return
}

export function playNotificationSound() {
  if (typeof window === 'undefined') return
  try {
    const AudioCtx = window.AudioContext || (window as any).webkitAudioContext
    if (!AudioCtx) return
    const ctx = new AudioCtx()
    if (ctx.state === 'suspended') {
      ctx.resume()
    }
    const now = ctx.currentTime

    // Dual chime tones (crystal clear notification bell)
    const osc1 = ctx.createOscillator()
    const gain1 = ctx.createGain()
    osc1.connect(gain1)
    gain1.connect(ctx.destination)
    osc1.type = 'sine'
    osc1.frequency.setValueAtTime(880, now)
    osc1.frequency.exponentialRampToValueAtTime(1760, now + 0.1)
    gain1.gain.setValueAtTime(0.32, now)
    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.25)
    osc1.start(now)
    osc1.stop(now + 0.25)

    const osc2 = ctx.createOscillator()
    const gain2 = ctx.createGain()
    osc2.connect(gain2)
    gain2.connect(ctx.destination)
    osc2.type = 'sine'
    osc2.frequency.setValueAtTime(1318.5, now + 0.03)
    gain2.gain.setValueAtTime(0.25, now + 0.03)
    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.28)
    osc2.start(now + 0.03)
    osc2.stop(now + 0.28)
  } catch (e) {}
}

export function playClickSound() {
  // Disabled as per user requirement: sound only on notification bell
  return
}

export function useTheme() {
  // Ensure theme is properly synced when composable is used
  if (typeof window !== 'undefined' && typeof document !== 'undefined') {
    try {
      const hasDark = document.documentElement.classList.contains('dark')
      if (hasDark !== isDark.value) {
        isDark.value = hasDark
      }
    } catch (e) {}
  }

  const toggleTheme = (event?: MouseEvent) => {
    const nextDark = !isDark.value
    playThemeSound(nextDark)

    const applyThemeChange = () => {
      isDark.value = nextDark
      try {
        if (nextDark) {
          document.documentElement.classList.add('dark')
          document.documentElement.style.colorScheme = 'dark'
          document.documentElement.style.backgroundColor = '#0b132b'
          localStorage.setItem('theme', 'dark')
        } else {
          document.documentElement.classList.remove('dark')
          document.documentElement.style.colorScheme = 'light'
          document.documentElement.style.backgroundColor = '#f8fafc'
          localStorage.setItem('theme', 'light')
        }
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark: nextDark } }))
      } catch (e) {}
    }

    const isAppearanceTransition =
      typeof document !== 'undefined' &&
      'startViewTransition' in document &&
      !window.matchMedia('(prefers-reduced-motion: reduce)').matches

    if (!isAppearanceTransition) {
      applyThemeChange()
      return
    }

    // Symmetrical Center Point Reveal (Starts in center and smoothly expands outwards in a circle)
    const x = window.innerWidth / 2
    const y = window.innerHeight / 2
    const endRadius = Math.hypot(x, y)

    const transition = (document as any).startViewTransition(() => {
      applyThemeChange()
    })

    transition.ready.then(() => {
      const clipPath = [
        `circle(0px at 50% 50%)`,
        `circle(${endRadius}px at 50% 50%)`
      ]
      document.documentElement.animate(
        {
          clipPath: clipPath
        },
        {
          duration: 500,
          easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
          pseudoElement: '::view-transition-new(root)'
        }
      )
    })
  }

  const setTheme = (theme: 'dark' | 'light') => {
    isDark.value = theme === 'dark'
    try {
      if (isDark.value) {
        document.documentElement.classList.add('dark')
        document.documentElement.style.colorScheme = 'dark'
        document.documentElement.style.backgroundColor = '#0b132b'
        localStorage.setItem('theme', 'dark')
      } else {
        document.documentElement.classList.remove('dark')
        document.documentElement.style.colorScheme = 'light'
        document.documentElement.style.backgroundColor = '#f8fafc'
        localStorage.setItem('theme', 'light')
      }
      window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark: isDark.value } }))
    } catch (e) {}
  }

  return {
    isDark,
    toggleTheme,
    setTheme,
  }
}
