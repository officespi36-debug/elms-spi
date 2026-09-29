import { ref, reactive } from 'vue'
import enTranslations from '../locales/en.json'
import kmTranslations from '../locales/km.json'

export type LanguageCode = 'km' | 'en'

const translations: Record<LanguageCode, Record<string, string>> = {
  en: enTranslations,
  km: kmTranslations
}

let savedLocale: LanguageCode | null = null
try {
  if (typeof window !== 'undefined' && window.localStorage) {
    savedLocale = localStorage.getItem('elms_lang') as LanguageCode
  }
} catch (e) {}

const currentLocale = ref<LanguageCode>(savedLocale === 'en' ? 'en' : 'km')

export const i18n = {
  locale: currentLocale,
  currentLocale,

  t(key: string, defaultText?: string): string {
    const lang = currentLocale.value
    return translations[lang]?.[key] || defaultText || key
  },

  setLanguage(lang: LanguageCode) {
    currentLocale.value = lang
    try {
      if (typeof window !== 'undefined') {
        document.documentElement.lang = lang
        localStorage.setItem('elms_lang', lang)
        window.dispatchEvent(new CustomEvent('elms-lang-change', { detail: lang }))
      }
    } catch (e) {}
  },

  toggleLanguage() {
    this.setLanguage(currentLocale.value === 'km' ? 'en' : 'km')
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
  } catch (e) {}
}

