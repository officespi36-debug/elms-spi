<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps<{
  isValid: boolean
  student?: {
    name: string
    name_kh?: string
    program: string
    program_kh?: string
    status: string
    status_kh?: string
    expires_at: string
    verified_at: string
    institution: string
  } | null
  message: string
}>()

const logoUrl = '/images/spi-elms-logo.png'
const isDark = ref(false)

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
})

const toggleTheme = () => {
  isDark.value = !isDark.value
  if (isDark.value) {
    document.documentElement.classList.add('dark')
    document.documentElement.style.colorScheme = 'dark'
    document.documentElement.style.backgroundColor = '#071638'
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    document.documentElement.style.colorScheme = 'light'
    document.documentElement.style.backgroundColor = '#f8fafc'
    localStorage.setItem('theme', 'light')
  }
}
</script>

<template>
  <Head title="Official Student ID Verification — Saint Paul Institute">
    <meta name="robots" content="noindex, nofollow" />
  </Head>

  <div class="min-h-screen bg-[#f8fafc] dark:bg-[#071638] text-slate-800 dark:text-slate-100 flex flex-col items-center justify-center p-4 sm:p-6 transition-colors duration-300 relative overflow-hidden font-khmer selection:bg-blue-600 selection:text-white">
    
    <!-- Ambient Background Lighting (Student Dashboard Style) -->
    <div class="fixed -top-40 -left-40 w-96 h-96 bg-blue-500/10 dark:bg-[#1459D9]/25 rounded-full blur-3xl pointer-events-none"></div>
    <div class="fixed -bottom-40 -right-40 w-96 h-96 bg-purple-500/10 dark:bg-[#7045F5]/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-cyan-500/5 dark:bg-[#16C8F5]/10 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- Main Verification Card Container -->
    <div class="w-full max-w-lg bg-white/95 dark:bg-[#0a1a3e]/90 backdrop-blur-xl border border-slate-200/90 dark:border-blue-500/25 rounded-3xl p-6 sm:p-8 shadow-xl dark:shadow-2xl dark:shadow-black/60 relative z-10 transition-all duration-300 space-y-6 overflow-hidden">
      
      <!-- Watermark Background Logo (Subtle) -->
      <img
        :src="logoUrl"
        alt=""
        class="absolute -right-12 -bottom-12 w-64 h-64 object-contain opacity-[0.035] dark:opacity-[0.05] pointer-events-none select-none blur-[0.4px]"
      />

      <!-- Subtle Geometric Dot Grid Pattern -->
      <div class="absolute inset-0 bg-[radial-gradient(#2563EB_1px,transparent_1px)] dark:bg-[radial-gradient(#16C8F5_1px,transparent_1px)] [background-size:18px_18px] opacity-[0.04] pointer-events-none"></div>

      <!-- Top Header Row: Logo, Institute Title, Theme Toggle -->
      <div class="flex items-start justify-between border-b border-slate-200/80 dark:border-white/10 pb-5 relative z-10 gap-3">
        
        <div class="flex items-center gap-3.5 min-w-0">
          <!-- Circular Blue SPI E-LMS Logo (Image 2) -->
          <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-full overflow-hidden shadow-md shadow-blue-500/25 ring-2 ring-blue-500/40 dark:ring-[#16C8F5]/50 bg-white p-0.5 shrink-0 flex items-center justify-center">
            <img
              :src="logoUrl"
              alt="SPI E-LMS Logo"
              class="w-full h-full object-contain rounded-full"
            />
          </div>

          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h1 class="text-xs sm:text-[13px] font-black text-slate-900 dark:text-white truncate">
                វិទ្យាស្ថាន សន្តប៉ូល
              </h1>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-[#16C8F5] border border-blue-200 dark:border-blue-500/30">
                Official
              </span>
            </div>
            <p class="text-[9.5px] sm:text-[10px] font-bold text-blue-700 dark:text-[#FFD54A] tracking-wider uppercase truncate font-sans">
              SAINT PAUL INSTITUTE
            </p>
            <p class="text-[10px] sm:text-[10.5px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
              ប្រព័ន្ធផ្ទៀងផ្ទាត់កាតនិស្សិតឌីជីថល (Digital ID Verification)
            </p>
          </div>
        </div>

        <!-- Light / Dark Mode Toggle Button -->
        <button
          type="button"
          @click="toggleTheme"
          :title="isDark ? 'ប្តូរទៅ Light Mode' : 'ប្តូរទៅ Dark Mode'"
          class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-[#142850] dark:hover:bg-[#1a3568] border border-slate-200 dark:border-blue-500/30 text-slate-600 dark:text-yellow-400 flex items-center justify-center shrink-0 transition-all cursor-pointer shadow-2xs hover:scale-105 active:scale-95"
        >
          <span v-if="isDark" class="text-sm">☀️</span>
          <span v-else class="text-sm">🌙</span>
        </button>

      </div>

      <!-- ========================================== -->
      <!-- 1. VALID STATE (Verified Active Student)   -->
      <!-- ========================================== -->
      <div v-if="isValid && student" class="space-y-5 relative z-10">
        
        <!-- Verified Status Pill -->
        <div class="p-4 rounded-2xl bg-emerald-50/90 dark:bg-emerald-950/30 border border-emerald-300 dark:border-emerald-500/40 text-emerald-800 dark:text-emerald-300 flex items-center gap-3.5 shadow-sm">
          <div class="w-10 h-10 rounded-full bg-emerald-500 text-white font-black text-lg flex items-center justify-center shrink-0 shadow-md shadow-emerald-500/30">
            ✓
          </div>
          <div class="min-w-0">
            <p class="text-xs sm:text-[13px] font-black text-emerald-900 dark:text-emerald-200">
              កាតផ្លូវការត្រឹមត្រូវ (Verified Official ID)
            </p>
            <p class="text-[11px] text-emerald-700 dark:text-emerald-400/90 mt-0.5">
              និស្សិតសកម្មក្នុងបញ្ជីឈ្មោះផ្លូវការរបស់វិទ្យាស្ថាន
            </p>
          </div>
        </div>

        <!-- Verified Student Details Panel (Privacy-Safe) -->
        <div class="p-5 rounded-2xl bg-slate-50/80 dark:bg-[#071638]/70 border border-slate-200/90 dark:border-blue-500/20 space-y-3.5 text-xs shadow-inner">
          
          <!-- Student Full Name -->
          <div>
            <p class="text-[10px] text-slate-400 dark:text-slate-400 uppercase font-bold tracking-wider">
              ឈ្មោះនិស្សិត (Student Name)
            </p>
            <div class="flex items-center gap-2 mt-1">
              <p class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                {{ student.name }}
              </p>
              <span v-if="student.name_kh" class="text-xs font-bold text-blue-600 dark:text-[#16C8F5]">
                ({{ student.name_kh }})
              </span>
            </div>
          </div>

          <!-- Academic Status Row -->
          <div class="pt-2.5 border-t border-slate-200 dark:border-white/10 flex items-center justify-between">
            <span class="text-[10.5px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
              ស្ថានភាពនិស្សិត (Status)
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100/90 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              {{ student.status_kh || student.status }}
            </span>
          </div>

          <!-- Program / Major Row -->
          <div class="pt-2.5 border-t border-slate-200 dark:border-white/10">
            <span class="text-[10.5px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
              កម្មវិធីសិក្សា (Academic Major)
            </span>
            <p class="text-xs sm:text-[13px] font-bold text-blue-700 dark:text-[#16C8F5] mt-1 flex items-center gap-1.5">
              <span>🎓</span>
              <span>{{ student.program_kh || student.program }}</span>
            </p>
          </div>

          <!-- Validity Expiration Row -->
          <div class="pt-2.5 border-t border-slate-200 dark:border-white/10 flex items-center justify-between text-xs">
            <span class="text-slate-500 dark:text-slate-400 font-bold">សុពលភាពដល់ (Expires):</span>
            <span class="font-bold text-slate-800 dark:text-slate-200 font-mono bg-slate-200/60 dark:bg-slate-800/60 px-2 py-0.5 rounded-md">
              {{ student.expires_at }}
            </span>
          </div>

        </div>

        <!-- Security & Realtime Verification Meta -->
        <div class="text-[10.5px] text-slate-500 dark:text-slate-400 text-center space-y-1">
          <p class="flex items-center justify-center gap-1.5">
            <span>🕒</span>
            <span>ពេលវេលាផ្ទៀងផ្ទាត់៖ <strong class="text-slate-700 dark:text-slate-300 font-mono">{{ student.verified_at }}</strong></span>
          </p>
          <p class="text-[10px] text-slate-400 dark:text-slate-500">
            🔒 ទិន្នន័យនេះត្រូវបានបញ្ជាក់សុពលភាពដោយផ្ទាល់ពីប្រព័ន្ធទិន្នន័យផ្លូវការរបស់ SPI
          </p>
        </div>

      </div>

      <!-- ========================================== -->
      <!-- 2. INVALID / REVOKED / EXPIRED STATE       -->
      <!-- ========================================== -->
      <div v-else class="space-y-5 text-center py-4 relative z-10">
        
        <!-- Invalid Icon with Glow Badge -->
        <div class="relative w-18 h-18 mx-auto flex items-center justify-center">
          <div class="absolute inset-0 rounded-full bg-rose-500/20 dark:bg-rose-500/30 blur-md"></div>
          <div class="w-16 h-16 rounded-full bg-rose-50 dark:bg-rose-950/50 border-2 border-rose-300 dark:border-rose-500/40 text-rose-600 dark:text-rose-400 text-3xl font-black flex items-center justify-center shadow-lg relative z-10">
            ✕
          </div>
        </div>

        <div class="space-y-2">
          <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
            កាតសម្គាល់ខ្លួនមិនត្រឹមត្រូវ
          </h2>
          <p class="text-xs sm:text-[13px] text-rose-600 dark:text-rose-400 font-bold">
            {{ message }}
          </p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-[#071638]/70 border border-slate-200 dark:border-white/10 text-xs text-slate-600 dark:text-slate-400 leading-relaxed text-left space-y-1.5">
          <p class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
            <span>ℹ️</span>
            <span>មូលហេតុដែលអាចកើតមាន៖</span>
          </p>
          <ul class="list-disc list-inside space-y-1 pl-1 text-[11px] text-slate-500 dark:text-slate-400">
            <li>តំណភ្ជាប់ ឬ QR Code នេះត្រូវបានបង្កើតថ្មី (កូដចាស់ឈប់ដំណើរការ)</li>
            <li>កាតត្រូវបានរាយការណ៍ថាបាត់ ឬត្រូវបានដកហូតសុពលភាព</li>
            <li>កាតបានផុតកំណត់កាលបរិច្ឆេទសុពលភាព</li>
          </ul>
        </div>

      </div>

      <!-- Bottom Action Row (Capsule Pill Design matching Student Dashboard) -->
      <div class="pt-3 border-t border-slate-200/80 dark:border-white/10 flex flex-col sm:flex-row items-center justify-center gap-3 relative z-10">
        
        <a
          href="/"
          class="w-full sm:w-auto px-6 py-2.5 rounded-full bg-gradient-to-r from-[#1d4ed8] via-[#2563eb] to-[#3b82f6] text-white text-xs font-bold shadow-md shadow-blue-500/25 hover:shadow-lg hover:shadow-blue-500/35 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center justify-center gap-2 cursor-pointer font-khmer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>ត្រឡប់ទៅកាន់ទំព័រដើម SPI E-LMS</span>
        </a>

      </div>

    </div>

    <!-- Page Footer Brand -->
    <div class="mt-6 text-center text-[10.5px] text-slate-500 dark:text-slate-400 relative z-10 flex items-center justify-center gap-1.5">
      <span>© 2026 Saint Paul Institute (SPI). All Rights Reserved.</span>
    </div>

  </div>
</template>
