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

    const x = event?.clientX ?? window.innerWidth / 2
    const y = event?.clientY ?? window.innerHeight / 2
    const endRadius = Math.hypot(
      Math.max(x, window.innerWidth - x),
      Math.max(y, window.innerHeight - y)
    )

    const transition = (document as any).startViewTransition(() => {
      applyThemeChange()
    })

    transition.ready.then(() => {
      const clipPath = [
        `circle(0px at ${x}px ${y}px)`,
        `circle(${endRadius}px at ${x}px ${y}px)`
      ]
      document.documentElement.animate(
        {
          clipPath: clipPath
        },
        {
          duration: 550,
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
