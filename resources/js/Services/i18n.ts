import { ref, computed } from 'vue'
import enTranslations from '../locales/en.json'
import kmTranslations from '../locales/km.json'

export type LanguageCode = 'km' | 'en'

const translations: Record<LanguageCode, Record<string, string>> = {
  en: enTranslations,
  km: kmTranslations
}

let savedLocale: LanguageCode | null = null
try {
  if (typeof window !== 'undefined') {
    if (window.localStorage) {
      savedLocale = localStorage.getItem('elms_lang') as LanguageCode
    }
    if (!savedLocale && document.cookie) {
      const match = document.cookie.match(/(?:^|;\s*)elms_lang=(km|en)/)
      if (match && (match[1] === 'km' || match[1] === 'en')) {
        savedLocale = match[1] as LanguageCode
      }
    }
  }
} catch (e) {}

const currentLocale = ref<LanguageCode>(savedLocale === 'en' ? 'en' : 'km')

export const i18n = {
  locale: currentLocale,
  currentLocale,

  t(keyOrKhmer: string, enTextOrDefault?: string): string {
    const lang = currentLocale.value
    // 1. Check if dictionary has the exact key
    if (translations[lang] && typeof translations[lang][keyOrKhmer] === 'string') {
      return translations[lang][keyOrKhmer]
    }
    // 2. If an explicit English translation is passed as 2nd param, return based on active language
    if (enTextOrDefault !== undefined) {
      return lang === 'km' ? keyOrKhmer : enTextOrDefault
    }
    // 3. Fallback
    return translations[lang]?.[keyOrKhmer] || keyOrKhmer
  },

  setLanguage(lang: LanguageCode) {
    if (currentLocale.value === lang && typeof window !== 'undefined' && document.documentElement.lang === lang) {
      return
    }
    currentLocale.value = lang
    try {
      if (typeof window !== 'undefined') {
        document.documentElement.lang = lang
        localStorage.setItem('elms_lang', lang)
        document.cookie = `elms_lang=${lang};path=/;max-age=31536000;SameSite=Lax`
        window.dispatchEvent(new CustomEvent('elms-lang-change', { detail: lang }))
      }
    } catch (e) {}
  },

  toggleLanguage() {
    this.setLanguage(currentLocale.value === 'km' ? 'en' : 'km')
  }
}

export function useLanguage() {
  const currentLang = computed(() => currentLocale.value)
  const isKhmer = computed(() => currentLocale.value === 'km')
  const isEnglish = computed(() => currentLocale.value === 'en')
  const t = (keyOrKhmer: string, enTextOrDefault?: string) => i18n.t(keyOrKhmer, enTextOrDefault)

  return {
    locale: currentLocale,
    currentLang,
    isKhmer,
    isEnglish,
    t,
    setLanguage: (lang: LanguageCode) => i18n.setLanguage(lang),
    toggleLanguage: () => i18n.toggleLanguage(),
  }
}

if (typeof window !== 'undefined') {
  try {
    document.documentElement.lang = currentLocale.value

    window.addEventListener('storage', (e) => {
      if (e.key === 'elms_lang' && (e.newValue === 'km' || e.newValue === 'en')) {
        currentLocale.value = e.newValue
        document.documentElement.lang = e.newValue
      }
    })

    window.addEventListener('elms-lang-change', (e: any) => {
      if (e.detail && (e.detail === 'km' || e.detail === 'en')) {
        currentLocale.value = e.detail
        document.documentElement.lang = e.detail
      }
    })
  } catch (e) {}
}
