import { ref } from 'vue'

export const isDark = ref(false)

export function initTheme() {
  if (typeof window === 'undefined') return
  try {
    const stored = localStorage.getItem('theme')
    if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      isDark.value = true
      document.documentElement.classList.add('dark')
    } else {
      isDark.value = false
      document.documentElement.classList.remove('dark')
    }
  } catch (e) {
    isDark.value = true
    document.documentElement.classList.add('dark')
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

    const applyThemeChange = () => {
      isDark.value = nextDark
      try {
        if (nextDark) {
          document.documentElement.classList.add('dark')
          localStorage.setItem('theme', 'dark')
        } else {
          document.documentElement.classList.remove('dark')
          localStorage.setItem('theme', 'light')
        }
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
        localStorage.setItem('theme', 'dark')
      } else {
        document.documentElement.classList.remove('dark')
        localStorage.setItem('theme', 'light')
      }
    } catch (e) {}
  }

  return {
    isDark,
    toggleTheme,
    setTheme,
  }
}
