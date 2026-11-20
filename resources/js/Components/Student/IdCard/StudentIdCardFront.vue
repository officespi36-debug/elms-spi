<script setup lang="ts">
interface ProfileData {
  id: number
  name: string
  name_kh?: string
  student_id: string
  avatar_url?: string | null
  status?: string
}

interface IdCardData {
  card_no: string
  issued_at?: string
  issued_at_kh?: string
  expires_at?: string
  expires_at_kh?: string
  is_official: boolean
  institution: string
  institution_kh: string
}

interface AcademicData {
  faculty: string
  faculty_kh: string
  department: string
  department_kh: string
  major: string
  major_kh: string
  term: string
  term_kh?: string
}

const props = defineProps<{
  profile: ProfileData
  idCard: IdCardData
  academic: AcademicData
  currentLang: 'km' | 'en'
  avatarPreview?: string | null
  cardLogoUrl?: string
}>()
</script>

<template>
  <div
    class="card-3d-face card-3d-front absolute inset-0 w-full h-full rounded-2xl p-4 sm:p-4.5 bg-[#071638] border border-blue-500/25 shadow-2xl text-white overflow-hidden flex flex-col justify-between ring-1 ring-white/10 select-none"
  >
    <!-- Background Watermarks & Subtle Geometric Mesh -->
    <img
      :src="cardLogoUrl || '/images/logo.png'"
      alt=""
      class="absolute right-[-24px] -bottom-10 w-56 h-56 object-contain rounded-full opacity-[0.07] pointer-events-none select-none blur-[0.4px]"
    />
    <!-- Subtle geometric grid pattern -->
    <div class="absolute inset-0 bg-[radial-gradient(#16C8F5_1px,transparent_1px)] [background-size:18px_18px] opacity-[0.06] pointer-events-none"></div>
    <!-- Soft radial ambient glows -->
    <div class="absolute -top-20 -right-20 w-56 h-56 bg-gradient-to-br from-[#1459D9]/25 via-[#7045F5]/15 to-transparent rounded-full blur-2xl pointer-events-none"></div>
    <div class="absolute -bottom-16 -left-16 w-52 h-52 bg-gradient-to-tr from-[#16C8F5]/15 via-[#1459D9]/10 to-transparent rounded-full blur-2xl pointer-events-none"></div>

    <!-- Header: SPI Logo, Names, and STUDENT ID CARD Badge -->
    <div class="flex items-center justify-between border-b border-white/10 pb-2 relative z-10">
      <div class="flex items-center gap-2.5 min-w-0">
        <div class="w-9 h-9 sm:w-9.5 sm:h-9.5 rounded-full overflow-hidden shadow-md flex items-center justify-center shrink-0 ring-1.5 ring-[#16C8F5]/50 bg-[#0a1b42]">
          <img :src="cardLogoUrl || '/images/logo.png'" alt="SPI Logo" class="w-full h-full object-cover" />
        </div>
        <div class="min-w-0">
          <p class="text-xs sm:text-[13px] font-black text-white drop-shadow-xs truncate font-khmer leading-tight">
            {{ currentLang === 'km' ? (idCard.institution_kh || idCard.institution) : idCard.institution }}
          </p>
          <p class="text-[8.5px] sm:text-[9px] font-bold text-[#FFD54A] tracking-wider uppercase truncate font-sans">
            SAINT PAUL INSTITUTE
          </p>
        </div>
      </div>

      <!-- Small STUDENT ID CARD Badge -->
      <div class="flex items-center gap-1.5 shrink-0">
        <span class="px-2.5 py-0.5 rounded-full bg-[#1459D9]/30 text-[#16C8F5] border border-[#16C8F5]/40 text-[9px] font-bold shadow-xs backdrop-blur-md flex items-center gap-1.5">
          <span
            class="w-1.5 h-1.5 rounded-full"
            :class="idCard.is_official ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"
          ></span>
          <span :class="currentLang === 'km' ? 'font-khmer' : 'font-sans uppercase tracking-wider font-black'">
            {{ currentLang === 'km' ? (idCard.is_official ? 'កាតនិស្សិតផ្លូវការ' : 'កាតនិស្សិត') : 'STUDENT ID CARD' }}
          </span>
        </span>
      </div>
    </div>

    <!-- Main Content: Student Photo (Left), Identity & Academic Details (Center), EMV Chip & Waves (Right) -->
    <div class="flex items-center gap-3 sm:gap-3.5 my-auto py-1 relative z-10">
      
      <!-- Left: Student Portrait with Rounded Corners & Subtle Ring -->
      <div class="relative shrink-0">
        <div class="w-19 h-19 sm:w-21 sm:h-21 rounded-xl overflow-hidden bg-[#0c1e4a] ring-2 ring-[#16C8F5]/50 shadow-xl flex items-center justify-center">
          <img
            v-if="avatarPreview"
            :src="avatarPreview"
            :alt="profile.name"
            class="w-full h-full object-cover"
          />
          <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#1459D9] to-[#071638] text-white font-extrabold text-2xl font-mono">
            {{ profile.name ? profile.name.charAt(0).toUpperCase() : 'S' }}
          </div>
        </div>
        <!-- Live Verified Dot -->
        <span
          v-if="idCard.is_official"
          class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 border-2 border-[#071638] shadow-md flex items-center justify-center text-white text-[10px]"
          title="Official University Verified"
        >
          ✓
        </span>
      </div>

      <!-- Center: Student Names, ID Badge, Major, Academic Year, and Status -->
      <div class="space-y-1 min-w-0 flex-1">
        
        <!-- Name (English large bold + Khmer subtitle) -->
        <div class="leading-tight">
          <h2 class="text-sm sm:text-base font-black text-white tracking-wide truncate uppercase drop-shadow-xs font-sans">
            {{ profile.name }}
          </h2>
          <p v-if="profile.name_kh" class="text-[11px] sm:text-xs font-semibold text-slate-300 truncate font-khmer pt-0.5 leading-normal">
            {{ profile.name_kh }}
          </p>
        </div>

        <!-- Student ID Highlighted in Soft Gold Badge -->
        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-[#FFD54A]/15 border border-[#FFD54A]/40">
          <span
            class="text-[8.5px] text-slate-300 font-bold"
            :class="currentLang === 'km' ? 'font-khmer' : 'uppercase tracking-wider font-sans'"
          >
            {{ currentLang === 'km' ? 'អត្តលេខ:' : 'ID:' }}
          </span>
          <span class="font-mono text-xs font-black text-[#FFD54A] tracking-wider">{{ profile.student_id }}</span>
        </div>

        <!-- Major & Faculty -->
        <div class="space-y-0.5">
          <p
            class="text-[10.5px] sm:text-[11px] text-slate-100 font-semibold truncate flex items-center gap-1"
            :class="currentLang === 'km' ? 'font-khmer' : 'font-sans'"
          >
            <span class="text-[#16C8F5] text-[9px]">●</span>
            <span class="truncate">{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</span>
          </p>
          <p
            v-if="academic.faculty || academic.faculty_kh"
            class="text-[9px] text-slate-300/85 truncate pl-2.5 font-medium"
            :class="currentLang === 'km' ? 'font-khmer' : 'font-sans'"
          >
            {{ currentLang === 'km' ? (academic.faculty_kh || academic.faculty) : academic.faculty }}
          </p>
        </div>

        <!-- Academic Year / Status Pill -->
        <div class="flex items-center gap-2 pt-0.5">
          <span
            class="text-[9px] sm:text-[9.5px] font-bold text-[#16C8F5] truncate"
            :class="currentLang === 'km' ? 'font-khmer' : 'font-sans'"
          >
            {{ currentLang === 'km' ? (academic.term_kh || academic.term) : academic.term }}
          </span>
          <span
            class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[8px] sm:text-[8.5px] font-bold shrink-0"
            :class="currentLang === 'km' ? 'font-khmer' : 'font-sans uppercase'"
          >
            {{ currentLang === 'km' ? 'និស្សិតកំពុងសិក្សា' : 'Active Student' }}
          </span>
        </div>

      </div>

      <!-- Right: EMV Metallic Gold Smart Chip & Contactless RFID Waves -->
      <div class="flex flex-col items-center justify-center gap-2 shrink-0 pl-1 border-l border-white/10">
        <!-- Contactless RFID Wave Icon -->
        <svg class="w-4 h-4 text-cyan-300/80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 010-8.808m3.535 11.314a9.5 9.5 0 000-13.82m3.536 16.32a13.5 13.5 0 000-18.82" />
        </svg>

        <!-- EMV Metallic Smart Chip Graphic -->
        <div class="w-9 h-7 rounded-md bg-gradient-to-br from-amber-200 via-amber-400 to-amber-600 p-[1px] shadow-md border border-amber-300/80 relative overflow-hidden">
          <div class="w-full h-full bg-gradient-to-tr from-amber-400 via-amber-300 to-amber-200 rounded-[3px] relative flex items-center justify-center">
            <div class="w-full h-[0.5px] bg-amber-800/60 absolute top-1/2"></div>
            <div class="h-full w-[0.5px] bg-amber-800/60 absolute left-1/2"></div>
            <div class="w-3.5 h-2.5 rounded-[2px] border border-amber-800/50 bg-amber-200/60"></div>
          </div>
        </div>
      </div>

    </div>

    <!-- Bottom Decorative Gradient Wave & Footer Row -->
    <div class="relative pt-2 border-t border-white/10 z-10">
      
      <!-- Subtle Blue-to-Purple Decorative Wave Background SVG -->
      <div class="absolute inset-x-0 bottom-0 h-6 pointer-events-none opacity-40 overflow-hidden">
        <svg viewBox="0 0 500 60" preserveAspectRatio="none" class="w-full h-full">
          <path
            d="M0,25 C150,55 350,5 500,30 L500,60 L0,60 Z"
            fill="url(#frontWaveGrad)"
          ></path>
          <defs>
            <linearGradient id="frontWaveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#1459D9" stop-opacity="0.8" />
              <stop offset="50%" stop-color="#7045F5" stop-opacity="0.8" />
              <stop offset="100%" stop-color="#16C8F5" stop-opacity="0.8" />
            </linearGradient>
          </defs>
        </svg>
      </div>

      <!-- Footer: Valid From, Card Number, and Valid Thru -->
      <div class="flex items-center justify-between gap-1.5 sm:gap-2 text-[9px] sm:text-[10px] text-slate-100 whitespace-nowrap relative z-10">
        
        <!-- Valid From Date -->
        <div class="flex items-center gap-1 shrink-0">
          <span
            class="text-slate-400 text-[8px] sm:text-[9px]"
            :class="currentLang === 'km' ? 'font-khmer' : 'uppercase font-sans'"
          >
            {{ currentLang === 'km' ? 'ថ្ងៃចេញ:' : 'Valid From:' }}
          </span>
          <span class="font-medium text-slate-200" :class="currentLang === 'km' ? 'font-khmer' : 'font-sans'">
            {{ currentLang === 'km' ? (idCard.issued_at_kh || idCard.issued_at) : idCard.issued_at }}
          </span>
        </div>

        <!-- Unique Card Number -->
        <div class="flex items-center gap-1 shrink-0 font-mono text-[9px] sm:text-[9.5px] text-slate-200 bg-[#0c1e4a]/80 px-2 py-0.5 rounded border border-[#16C8F5]/30 shadow-xs">
          <span class="text-[#16C8F5] text-[8px] font-sans font-bold">CARD:</span>
          <span class="font-bold text-white tracking-wide">{{ idCard.card_no }}</span>
        </div>

        <!-- Valid Until Date -->
        <div class="flex items-center gap-1 shrink-0">
          <span
            class="text-slate-400 text-[8px] sm:text-[9px]"
            :class="currentLang === 'km' ? 'font-khmer' : 'uppercase font-sans'"
          >
            {{ currentLang === 'km' ? 'សុពលភាព:' : 'Valid Thru:' }}
          </span>
          <span class="font-bold text-[#FFD54A]" :class="currentLang === 'km' ? 'font-khmer' : 'font-sans'">
            {{ currentLang === 'km' ? (idCard.expires_at_kh || idCard.expires_at) : idCard.expires_at }}
          </span>
        </div>

      </div>

    </div>

  </div>
</template>
