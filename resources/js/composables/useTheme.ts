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
  if (typeof window === 'undefined') return
  try {
    const AudioCtx = window.AudioContext || (window as any).webkitAudioContext
    if (!AudioCtx) return
    const ctx = new AudioCtx()
    if (ctx.state === 'suspended') {
      ctx.resume()
    }
    const now = ctx.currentTime

    // Sound 1: Tactile snap impulse (physical switch click)
    const clickOsc = ctx.createOscillator()
    const clickGain = ctx.createGain()
    clickOsc.connect(clickGain)
    clickGain.connect(ctx.destination)

    clickOsc.type = 'triangle'
    clickOsc.frequency.setValueAtTime(1500, now)
    clickOsc.frequency.exponentialRampToValueAtTime(450, now + 0.035)

    clickGain.gain.setValueAtTime(0.3, now)
    clickGain.gain.exponentialRampToValueAtTime(0.001, now + 0.04)

    clickOsc.start(now)
    clickOsc.stop(now + 0.04)

    // Sound 2: Resonant melodic tone (clearly audible & pleasant)
    const toneOsc = ctx.createOscillator()
    const toneGain = ctx.createGain()
    toneOsc.connect(toneGain)
    toneGain.connect(ctx.destination)

    toneOsc.type = 'sine'
    if (!nextIsDark) {
      // Switching to light mode: bright uplifting chime
      toneOsc.frequency.setValueAtTime(520, now + 0.01)
      toneOsc.frequency.exponentialRampToValueAtTime(880, now + 0.15)
    } else {
      // Switching to dark mode: warm comforting tone
      toneOsc.frequency.setValueAtTime(880, now + 0.01)
      toneOsc.frequency.exponentialRampToValueAtTime(440, now + 0.15)
    }

    toneGain.gain.setValueAtTime(0.35, now + 0.01)
    toneGain.gain.exponentialRampToValueAtTime(0.001, now + 0.18)

    toneOsc.start(now + 0.01)
    toneOsc.stop(now + 0.18)
  } catch (e) {
    // AudioContext blocked or not supported
  }
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

    // Triangular Spotlight Cone Reveal (Apex at top-center, flaring down to both sides)
    const transition = (document as any).startViewTransition(() => {
      applyThemeChange()
    })

    transition.ready.then(() => {
      document.documentElement.animate(
        [
          { clipPath: 'polygon(50% 0%, 50% 0%, 50% 0%, 50% 0%)', offset: 0 },
          { clipPath: 'polygon(50% 0%, 50% 0%, 82% 100%, 18% 100%)', offset: 0.42 },
          { clipPath: 'polygon(18% 0%, 82% 0%, 115% 100%, -15% 100%)', offset: 0.72 },
          { clipPath: 'polygon(-25% 0%, 125% 0%, 145% 100%, -45% 100%)', offset: 1 }
        ],
        {
          duration: 850,
          easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
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
