import '../css/app.css'
import '@fontsource-variable/inter'
import '@fontsource/noto-sans-khmer/400.css'
import '@fontsource/noto-sans-khmer/700.css'

import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import { VueQueryPlugin } from '@tanstack/vue-query'
import ui from '@nuxt/ui/vue-plugin'
import { registerSW } from 'virtual:pwa-register'
import { flushQueue } from '@/offline/sync'

import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import ToastService from 'primevue/toastservice'
import 'primeicons/primeicons.css'
import { initTheme } from '@/composables/useTheme'
import { i18n } from '@/Services/i18n'

initTheme()



if (typeof window !== 'undefined') {
  try {
    document.documentElement.setAttribute('translate', 'no')
    document.documentElement.classList.add('notranslate')
    if (document.body) {
      document.body.setAttribute('translate', 'no')
      document.body.classList.add('notranslate')
    }
  } catch (e) { }

  try {
    const updateSW = registerSW({
      immediate: true,
      onNeedRefresh() {
        updateSW(true)
      }
    })
  } catch (e) { }

  window.addEventListener('vite:preloadError', () => {
    window.location.reload()
  })

  window.addEventListener('online', () => {
    try {
      flushQueue()
    } catch (e) { }
  })

  // Purge any legacy demo multi-account cache to maintain strict official production isolation
  try {
    localStorage.removeItem('elms_saved_accounts')
  } catch (e) { }
}

createInertiaApp({
  title: (t) => t ? `${t} | E-LMS` : 'E-LMS',
  resolve: async (name) => {
    try {
      return await resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob<DefineComponent>('./Pages/**/*.vue')
      )
    } catch (err: any) {
      console.error('Failed to load page chunk:', err)
      if (typeof window !== 'undefined') {
        try {
          if (!sessionStorage.getItem('chunk_reload_attempted')) {
            sessionStorage.setItem('chunk_reload_attempted', '1')
            window.location.reload()
          }
        } catch (e) {
          window.location.reload()
        }
      }
      throw err
    }
  },
  setup({ el, App, props, plugin }) {
    const vueApp = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(createPinia())
      .use(VueQueryPlugin)
      .use(ui)
      .use(PrimeVue, { theme: { preset: Aura } })
      .use(ToastService)

    vueApp.config.globalProperties.$t = (key: string, defaultText?: string) => i18n.t(key, defaultText)
    vueApp.config.globalProperties.$i18n = i18n
    vueApp.provide('i18n', i18n)

    const sentryDsn = (import.meta as any).env?.VITE_SENTRY_DSN

    if (sentryDsn) {
      import('@sentry/vue').then((Sentry) => {
        Sentry.init({
          app: vueApp,
          dsn: sentryDsn,
          integrations: [
            Sentry.browserTracingIntegration(),
          ],
          tracesSampleRate: 0.2,
        })
      }).catch(() => { })
    }

    vueApp.config.errorHandler = (err, instance, info) => {
      console.error('Vue Runtime Error:', err, info)
      if (sentryDsn) {
        import('@sentry/vue').then((Sentry) => {
          Sentry.captureException(err, { extra: { info } })
        }).catch(() => { })
      }
    }

    vueApp.mount(el)

    // Smoothly fade out and remove initial preloader once Vue is fully mounted
    if (typeof window !== 'undefined') {
      const preloader = document.getElementById('initial-page-preloader')
      if (preloader) {
        setTimeout(() => {
          requestAnimationFrame(() => {
            preloader.style.opacity = '0'
            setTimeout(() => {
              try { preloader.remove() } catch (e) { }
            }, 260)
          })
        }, 300)
      }
    }
  },
  progress: false,
})

if (typeof window !== 'undefined') {
  // Clear any stuck progress bar on Back/Forward cache restore (pageshow)
  window.addEventListener('pageshow', () => {
    const nprogressEl = document.getElementById('nprogress')
    if (nprogressEl) {
      nprogressEl.style.transition = 'opacity 0.2s ease-out'
      nprogressEl.style.opacity = '0'
      setTimeout(() => {
        try { nprogressEl.remove() } catch (e) { }
      }, 200)
    }
  })

  // 65ms Hover Intent Prefetching for GET /student/* Links
  let prefetchTimer: any = null
  const prefetchedUrls = new Set<string>()

  document.addEventListener('mouseover', (e) => {
    const target = (e.target as HTMLElement)?.closest('a')
    if (!target) return

    const href = target.getAttribute('href')
    if (!href) return

    // Strictly prefetch GET /student/* routes
    if (!href.startsWith('/student/') && !href.startsWith('/student?')) return

    // Never prefetch logout, delete, api, dynamic current redirect, or external endpoints
    if (
      href.includes('logout') ||
      href.includes('delete') ||
      href.includes('destroy') ||
      href.includes('/api/') ||
      href.includes('/my-courses/current') ||
      href.includes('/courses/current') ||
      target.hasAttribute('download') ||
      target.getAttribute('target') === '_blank'
    ) return

    if (prefetchedUrls.has(href)) return

    if (prefetchTimer) clearTimeout(prefetchTimer)

    prefetchTimer = setTimeout(() => {
      prefetchedUrls.add(href)
      try {
        router.prefetch(href, { method: 'get' })
      } catch (err) { }
    }, 30)
  }, { passive: true })

  document.addEventListener('mouseout', (e) => {
    const target = (e.target as HTMLElement)?.closest('a')
    if (target && prefetchTimer) {
      clearTimeout(prefetchTimer)
    }
  }, { passive: true })
}
