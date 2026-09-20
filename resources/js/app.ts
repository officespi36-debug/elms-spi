import '../css/app.css'
import '@fontsource-variable/inter'
import '@fontsource/noto-sans-khmer/400.css'
import '@fontsource/noto-sans-khmer/700.css'

import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
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
    registerSW({ immediate: true })
  } catch (e) {}

  window.addEventListener('vite:preloadError', () => {
    window.location.reload()
  })

  window.addEventListener('online', () => {
    try {
      flushQueue()
    } catch (e) {}
  })
}

createInertiaApp({
  title: (t) => t ? `${t} - E-LMS` : 'E-LMS',
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

    vueApp.config.errorHandler = (err, instance, info) => {
      console.error('Vue Runtime Error:', err, info)
    }

    vueApp.mount(el)
  },
  progress: {
    color: '#3B82F6',
    showSpinner: false,
    delay: 50,
  },
})
