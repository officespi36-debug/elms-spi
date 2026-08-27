<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { i18n, type LanguageCode } from '../Services/i18n'
import AuthAnimatedBackground from '../Components/AuthAnimatedBackground.vue'
import NetworkStatusPill from '../Components/NetworkStatusPill.vue'

const logoUrl = '/images/logo.png'
const isDark = ref(true)
const isLangOpen = ref(false)

const languages = [
  { code: 'km' as LanguageCode, name: 'ភាសាខ្មែរ', label: 'ខ្មែរ', short: 'KH', flagUrl: '/images/flags/km.svg' },
  { code: 'en' as LanguageCode, name: 'English', label: 'English', short: 'EN', flagUrl: '/images/flags/en.svg' },
]

const currentLang = computed(() => i18n.locale.value)

const selectLanguage = (code: LanguageCode) => {
  i18n.setLanguage(code)
  isLangOpen.value = false
}

const toggleLanguageDirect = () => {
  const nextLang = currentLang.value === 'km' ? 'en' : 'km'
  selectLanguage(nextLang)
}

const initTheme = () => {
  const saved = localStorage.getItem('theme')
  if (saved) {
    isDark.value = saved === 'dark'
  } else {
    isDark.value = true
  }
  applyTheme()
}

const toggleTheme = () => {
  isDark.value = !isDark.value
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
  applyTheme()
}

const applyTheme = () => {
  if (isDark.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.lang-switcher-container')) {
    isLangOpen.value = false
  }
}

onMounted(() => {
  initTheme()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <Head :title="currentLang === 'km' ? 'លក្ខខណ្ឌប្រើប្រាស់ - E-LMS' : 'Terms of Service - E-LMS'">
    <meta name="description" content="Terms of Service and Conditions of Use for E-LMS Portal (spilms.tech)" />
    <meta name="robots" content="index, follow" />
  </Head>

  <div class="min-h-screen w-full bg-[#f8fafc] dark:bg-[#000000] text-zinc-900 dark:text-[#ededed] flex flex-col justify-between relative font-sans overflow-x-hidden select-none transition-colors duration-300">
    
    <!-- Manus AI Style Interactive Dot-Matrix Canvas Background -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0 select-none">
      <AuthAnimatedBackground />
    </div>

    <!-- Top Navigation: Left Branding & Right Controls -->
    <header class="w-full relative z-20 flex items-center justify-between px-6 py-5 sm:px-8 border-b border-slate-200/60 dark:border-zinc-800/60 backdrop-blur-md bg-white/70 dark:bg-black/50">
      <!-- Logo Mark -->
      <div class="flex items-center gap-2.5 cursor-pointer transition-opacity hover:opacity-80 group" @click="router.visit('/')">
        <img :src="logoUrl" alt="E-LMS" class="w-7 h-7 rounded-full object-contain shadow-xs transition-transform duration-200 group-hover:scale-105" />
        <span class="font-extrabold tracking-tight text-lg font-sans bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-white dark:to-zinc-200 bg-clip-text text-transparent">E LMS</span>
      </div>

      <!-- Right Controls: Language & Theme Switchers & Back Link -->
      <div class="flex items-center gap-2.5">
        <!-- Network Status Pill (Online / Offline) -->
        <NetworkStatusPill :current-lang="currentLang" />

        <!-- Language Switcher Pill -->
        <div class="relative lang-switcher-container">
          <button
            type="button"
            @click.stop="isLangOpen = !isLangOpen"
            class="h-8 px-2.5 rounded-full bg-zinc-200/70 dark:bg-[#18181b] hover:bg-zinc-300 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 transition-all duration-150 border border-zinc-300/80 dark:border-zinc-800/80 flex items-center gap-1.5 text-xs font-semibold cursor-pointer select-none"
            :title="currentLang === 'km' ? 'ប្តូរភាសា / Change Language' : 'Change Language / ប្តូរភាសា'"
          >
            <img
              :src="languages.find(l => l.code === currentLang)?.flagUrl || '/images/flags/km.svg'"
              :alt="currentLang"
              class="w-3.5 h-3.5 rounded-full object-cover shrink-0"
            />
            <span class="text-[11px] font-bold">
              {{ currentLang === 'km' ? 'KH' : 'EN' }}
            </span>
            <i :class="['pi pi-chevron-down text-[9px] text-zinc-500 transition-transform duration-150', isLangOpen ? 'rotate-180' : '']"></i>
          </button>

          <!-- Language Dropdown -->
          <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform opacity-0 scale-95 -translate-y-1"
            enter-to-class="transform opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform opacity-100 scale-100 translate-y-0"
            leave-to-class="transform opacity-0 scale-95 -translate-y-1"
          >
            <div
              v-if="isLangOpen"
              class="absolute right-0 mt-1.5 w-36 rounded-xl bg-white dark:bg-[#18181b] border border-zinc-200 dark:border-zinc-800 shadow-lg py-1 z-50 overflow-hidden"
            >
              <button
                v-for="lang in languages"
                :key="lang.code"
                type="button"
                @click="selectLanguage(lang.code)"
                :class="[
                  'w-full flex items-center justify-between px-3 py-2 text-xs font-medium transition-colors cursor-pointer select-none',
                  currentLang === lang.code
                    ? 'bg-zinc-100 dark:bg-zinc-800 text-blue-600 dark:text-sky-400 font-semibold'
                    : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'
                ]"
              >
                <span class="flex items-center gap-2">
                  <img :src="lang.flagUrl" :alt="lang.name" class="w-3.5 h-3.5 rounded-full object-cover shrink-0" />
                  <span>{{ lang.name }}</span>
                </span>
                <i v-if="currentLang === lang.code" class="pi pi-check text-[10px] text-blue-600 dark:text-sky-400"></i>
              </button>
            </div>
          </Transition>
        </div>

        <!-- Theme Switcher Pill -->
        <button
          type="button"
          @click="toggleTheme"
          class="h-8 w-8 rounded-full bg-zinc-200/70 dark:bg-[#18181b] hover:bg-zinc-300 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 transition-all duration-150 border border-zinc-300/80 dark:border-zinc-800/80 flex items-center justify-center cursor-pointer select-none"
          :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        >
          <i :class="['pi text-xs', isDark ? 'pi-sun text-amber-400' : 'pi-moon text-indigo-400']"></i>
        </button>

        <!-- Back to Login Pill -->
        <Link
          href="/login"
          class="h-8 px-3.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs flex items-center justify-center transition-colors shadow-sm shadow-blue-500/20"
        >
          <span>{{ currentLang === 'km' ? 'ចូលប្រព័ន្ធ' : 'Sign In' }}</span>
        </Link>
      </div>
    </header>

    <!-- Main Content Container -->
    <main class="relative z-10 max-w-3xl mx-auto px-4 sm:px-6 py-10 sm:py-14 space-y-8 select-text">
      
      <!-- Header Section with Gradient Title -->
      <div class="flex flex-col items-center text-center space-y-3.5">
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-white dark:via-zinc-100 dark:to-zinc-300 bg-clip-text text-transparent">
          {{ currentLang === 'km' ? 'លក្ខខណ្ឌប្រើប្រាស់សេវាកម្ម' : 'Terms of Service' }}
        </h1>

        <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 max-w-xl leading-relaxed">
          {{ currentLang === 'km' 
            ? 'សូមអានលក្ខខណ្ឌប្រើប្រាស់ខាងក្រោមឱ្យបានច្បាស់លាស់មុនពេលប្រើប្រាស់ប្រព័ន្ធគ្រប់គ្រងការសិក្សា E-LMS (spilms.tech)។ ការចូលប្រើប្រាស់របស់អ្នកមានន័យថាអ្នកបានយល់ព្រមតាមលក្ខខណ្ឌទាំងអស់នេះ។'
            : 'Please review these Terms of Service carefully before accessing or using the E-LMS (spilms.tech) platform. By signing in, you agree to be bound by these platform terms and policies.'
          }}
        </p>

        <!-- Metadata Badges -->
        <div class="flex flex-wrap items-center justify-center gap-2 pt-1 text-xs">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-medium">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            {{ currentLang === 'km' ? 'អនុវត្តជាផ្លូវការ' : 'Official & Active' }}
          </span>
          <span class="px-2.5 py-1 rounded-lg bg-slate-100/90 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400">
            {{ currentLang === 'km' ? 'ប្រព័ន្ធ:' : 'Platform:' }} <strong class="text-slate-900 dark:text-zinc-200">https://spilms.tech</strong>
          </span>
          <span class="px-2.5 py-1 rounded-lg bg-slate-100/90 dark:bg-zinc-900/80 border border-slate-200 dark:border-zinc-800 text-slate-600 dark:text-zinc-400">
            {{ currentLang === 'km' ? 'កាលបរិច្ឆេទកែប្រែ:' : 'Last Revised:' }} <strong class="text-slate-900 dark:text-zinc-200">{{ currentLang === 'km' ? 'ខែសីហា ឆ្នាំ២០២៦' : 'August 2026' }}</strong>
          </span>
        </div>
      </div>

      <!-- Section List (Obsidian / Slate Minimalist Cards) -->
      <div class="space-y-3.5">

        <!-- 1. Acceptance -->
        <div class="rounded-2xl bg-white dark:bg-[#121214] p-5 sm:p-6 border border-slate-200/90 dark:border-zinc-800/90 shadow-2xs transition hover:border-slate-300 dark:hover:border-zinc-700">
          <div class="flex items-center gap-3 mb-2.5">
            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white dark:bg-white dark:text-zinc-950 font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
              {{ currentLang === 'km' ? '១' : '1' }}
            </span>
            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
              {{ currentLang === 'km' ? 'ការយល់ព្រមលើលក្ខខណ្ឌប្រើប្រាស់' : 'Acceptance of Terms' }}
            </h3>
          </div>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 leading-relaxed pl-0 sm:pl-9">
            {{ currentLang === 'km'
              ? 'ដោយការបង្កើតគណនី ការចូលតាមរយៈ Google Sign-In ឬការប្រើប្រាស់គេហទំព័រ https://spilms.tech អ្នកបញ្ជាក់ថាអ្នកជាសិស្ស និស្សិត សាស្ត្រាចារ្យ ឬអ្នកប្រើប្រាស់ដែលមានសិទ្ធិស្របច្បាប់របស់ប្រព័ន្ធ E-LMS ហើយយល់ព្រមគោរពតាមបទបញ្ជា និងលក្ខខណ្ឌប្រើប្រាស់ទាំងអស់។'
              : 'By creating an account, authenticating via Google Sign-In, or using https://spilms.tech, you confirm that you are an authorized user of E-LMS and agree to adhere to these Terms of Service and platform policies.'
            }}
          </p>
        </div>

        <!-- 2. Account Security & Google Sign-In -->
        <div class="rounded-2xl bg-white dark:bg-[#121214] p-5 sm:p-6 border border-slate-200/90 dark:border-zinc-800/90 shadow-2xs transition hover:border-slate-300 dark:hover:border-zinc-700">
          <div class="flex items-center gap-3 mb-2.5">
            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white dark:bg-white dark:text-zinc-950 font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
              {{ currentLang === 'km' ? '២' : '2' }}
            </span>
            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
              {{ currentLang === 'km' ? 'សុវត្ថិភាពគណនី និងការ Login ជាមួយ Google' : 'Account Security & Google Authentication' }}
            </h3>
          </div>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 leading-relaxed pl-0 sm:pl-9">
            {{ currentLang === 'km'
              ? 'អ្នកប្រើប្រាស់ត្រូវទទួលខុសត្រូវចំពោះការរក្សាការសម្ងាត់នៃព័ត៌មាន Login របស់ខ្លួន។ ប្រសិនបើអ្នកប្រើ Google Sign-In អ្នកត្រូវប្រាកដថាគណនី Google ផ្ទាល់ខ្លួនរបស់អ្នកមានសុវត្ថិភាពខ្ពស់។ ហាមដាច់ខាតការចែករំលែក Password ឬ Session ឱ្យអ្នកដទៃចូលប្រើជំនួស។'
              : 'Users are strictly responsible for safeguarding their login credentials. When using Google Sign-In, ensure your Google account is secured. Account sharing or unauthorized credential disclosure is strictly prohibited.'
            }}
          </p>
        </div>

        <!-- 3. Academic Integrity & Course Materials -->
        <div class="rounded-2xl bg-white dark:bg-[#121214] p-5 sm:p-6 border border-slate-200/90 dark:border-zinc-800/90 shadow-2xs transition hover:border-slate-300 dark:hover:border-zinc-700">
          <div class="flex items-center gap-3 mb-2.5">
            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white dark:bg-white dark:text-zinc-950 font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
              {{ currentLang === 'km' ? '៣' : '3' }}
            </span>
            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
              {{ currentLang === 'km' ? 'សេចក្តីថ្លៃថ្នូរក្នុងការសិក្សា និងកម្មសិទ្ធិបញ្ញា' : 'Academic Integrity & Intellectual Property' }}
            </h3>
          </div>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 leading-relaxed pl-0 sm:pl-9">
            {{ currentLang === 'km'
              ? 'រាល់ឯកសារមេរៀន វីដេអូបង្រៀន កម្រងសំណួរ និងវិញ្ញាបនបត្រទាំងអស់នៅលើ E-LMS គឺជាកម្មសិទ្ធិបញ្ញារបស់ប្រព័ន្ធ E-LMS និងសាស្ត្រាចារ្យដែលពាក់ព័ន្ធ។ ហាមលួចចម្លង ចែកចាយលក់បន្ត ឬយកទៅប្រើប្រាស់ក្រៅប្រព័ន្ធដោយគ្មានការអនុញ្ញាតជាលាយលក្ខណ៍អក្សរ។'
              : 'All lecture videos, syllabus materials, question banks, AI recommendations, and digital certificates hosted on E-LMS are protected intellectual property. Redistribution, copying, or unauthorized commercial exploitation is strictly prohibited.'
            }}
          </p>
        </div>

        <!-- 4. Prohibited Conduct -->
        <div class="rounded-2xl bg-white dark:bg-[#121214] p-5 sm:p-6 border border-slate-200/90 dark:border-zinc-800/90 shadow-2xs transition hover:border-slate-300 dark:hover:border-zinc-700">
          <div class="flex items-center gap-3 mb-2.5">
            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white dark:bg-white dark:text-zinc-950 font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
              {{ currentLang === 'km' ? '៤' : '4' }}
            </span>
            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
              {{ currentLang === 'km' ? 'សកម្មភាពដែលត្រូវបានហាមឃាត់' : 'Prohibited Conduct' }}
            </h3>
          </div>
          <ul class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 space-y-1.5 pl-0 sm:pl-9 list-disc list-inside leading-relaxed">
            <li>{{ currentLang === 'km' ? 'ការប៉ុនប៉ង Hack, បំពានប្រព័ន្ធសុវត្ថិភាព ឬ Brute-force លើប្រព័ន្ធ' : 'Attempting to breach, probe, test vulnerabilities, or disrupt system security' }}</li>
            <li>{{ currentLang === 'km' ? 'ការក្លែងបន្លំពិន្ទុ កិច្ចការ ឬវិញ្ញាបនបត្រសិក្សា' : 'Fabricating, tampering with, or falsifying academic grades, quizzes, or certificates' }}</li>
            <li>{{ currentLang === 'km' ? 'ការបង្ហោះមាតិកាដែលខុសច្បាប់ មិនសមរម្យ ឬបង្កគ្រោះថ្នាក់' : 'Uploading malicious code, offensive content, or unauthorized copyrighted materials' }}</li>
          </ul>
        </div>

        <!-- 5. Termination -->
        <div class="rounded-2xl bg-white dark:bg-[#121214] p-5 sm:p-6 border border-slate-200/90 dark:border-zinc-800/90 shadow-2xs transition hover:border-slate-300 dark:hover:border-zinc-700">
          <div class="flex items-center gap-3 mb-2.5">
            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white dark:bg-white dark:text-zinc-950 font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
              {{ currentLang === 'km' ? '៥' : '5' }}
            </span>
            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
              {{ currentLang === 'km' ? 'ការផ្អាក ឬលុបគណនី' : 'Account Suspension & Termination' }}
            </h3>
          </div>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-zinc-400 leading-relaxed pl-0 sm:pl-9">
            {{ currentLang === 'km'
              ? 'ប្រព័ន្ធ E-LMS រក្សាសិទ្ធិក្នុងការផ្អាក ឬបិទគណនីរបស់អ្នកប្រើប្រាស់ណាដែលបានបំពានលើលក្ខខណ្ឌប្រើប្រាស់ ឬបង្កផលប៉ះពាល់ដល់ប្រព័ន្ធដោយពុំចាំបាច់ជូនដំណឹងជាមុន។'
              : 'E-LMS reserves the right to suspend or terminate platform access for any user found violating these terms, engaging in misconduct, or endangering system security.'
            }}
          </p>
        </div>

      </div>

      <!-- Bottom Quick Links Footer -->
      <footer class="pt-8 border-t border-slate-200 dark:border-zinc-800/80 text-center space-y-3">
        <div class="flex flex-wrap items-center justify-center gap-4 text-xs text-slate-500 dark:text-zinc-500">
          <Link href="/privacy" class="hover:text-blue-600 dark:hover:text-white transition-colors">{{ currentLang === 'km' ? 'គោលការណ៍ឯកជនភាព' : 'Privacy Policy' }}</Link>
          <span>•</span>
          <Link href="/terms" class="text-blue-600 dark:text-white font-bold">{{ currentLang === 'km' ? 'លក្ខខណ្ឌប្រើប្រាស់' : 'Terms of Service' }}</Link>
          <span>•</span>
          <Link href="/login" class="hover:text-blue-600 dark:hover:text-white transition-colors">{{ currentLang === 'km' ? 'ចូលប្រព័ន្ធ' : 'Sign In' }}</Link>
        </div>
        <p class="text-[11px] text-slate-400 dark:text-zinc-500">
          © 2026 E-LMS. All Rights Reserved. AI-Based E-Learning Platform.
        </p>
      </footer>

    </main>
  </div>
</template>
