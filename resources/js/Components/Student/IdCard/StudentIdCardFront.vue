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
  status?: 'active' | 'revoked' | 'pending' | 'expired'
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
    class="card-3d-face card-3d-front absolute inset-0 w-full h-full rounded-2xl p-3.5 sm:p-4 pb-2.5 sm:pb-3 bg-gradient-to-br from-white via-slate-50 to-blue-50/50 dark:from-[#0a1a3e] dark:via-[#071638] dark:to-[#040e26] border border-blue-200/80 dark:border-blue-500/25 shadow-xl dark:shadow-2xl text-slate-800 dark:text-white overflow-hidden flex flex-col justify-between ring-1 ring-slate-900/5 dark:ring-white/10 select-none transition-colors duration-300"
  >
    <!-- Background Watermarks & Subtle Geometric Mesh -->
    <img
      :src="cardLogoUrl || '/images/logo.png'"
      alt=""
      class="absolute right-[-24px] -bottom-10 w-56 h-56 object-contain rounded-full opacity-[0.04] dark:opacity-[0.06] pointer-events-none select-none blur-[0.4px]"
    />
    <!-- Subtle geometric grid pattern -->
    <div class="absolute inset-0 bg-[radial-gradient(#2563EB_1px,transparent_1px)] dark:bg-[radial-gradient(#16C8F5_1px,transparent_1px)] [background-size:18px_18px] opacity-[0.04] pointer-events-none"></div>
    <!-- Soft radial ambient glows -->
    <div class="absolute -top-20 -right-20 w-56 h-56 bg-gradient-to-br from-blue-400/10 via-indigo-400/10 to-transparent dark:from-[#1459D9]/20 dark:via-[#7045F5]/10 dark:to-transparent rounded-full blur-2xl pointer-events-none"></div>
    <div class="absolute -bottom-16 -left-16 w-52 h-52 bg-gradient-to-tr from-cyan-400/10 via-blue-400/5 to-transparent dark:from-[#16C8F5]/12 dark:via-[#1459D9]/8 dark:to-transparent rounded-full blur-2xl pointer-events-none"></div>

    <!-- Header: SPI Logo, Names, and STUDENT ID CARD Badge -->
    <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-white/10 pb-1.5 relative z-10">
      <div class="flex items-center gap-2.5 min-w-0">
        <div class="w-9 h-9 sm:w-9.5 sm:h-9.5 rounded-full overflow-hidden shadow-md flex items-center justify-center shrink-0 ring-1.5 ring-blue-500/40 dark:ring-[#16C8F5]/50 bg-white dark:bg-[#0a1b42]">
          <img :src="cardLogoUrl || '/images/logo.png'" alt="SPI Logo" class="w-full h-full object-cover" />
        </div>
        <div class="min-w-0">
          <p class="text-xs sm:text-[13px] font-black text-slate-900 dark:text-white drop-shadow-xs truncate font-khmer leading-tight">
            {{ currentLang === 'km' ? (idCard.institution_kh || idCard.institution) : idCard.institution }}
          </p>
          <p class="text-[8.5px] sm:text-[9px] font-bold text-blue-700 dark:text-[#FFD54A] tracking-wider uppercase truncate font-sans">
            SAINT PAUL INSTITUTE
          </p>
        </div>
      </div>

      <!-- Small STUDENT ID CARD / Approval Badge -->
      <div class="flex items-center gap-1.5 shrink-0">
        <span
          class="px-2.5 py-0.5 rounded-full text-[9px] font-bold shadow-xs backdrop-blur-md flex items-center gap-1.5 border"
          :class="[
            idCard.is_official
              ? 'bg-blue-50/90 dark:bg-[#1459D9]/30 text-blue-700 dark:text-[#16C8F5] border-blue-200/90 dark:border-[#16C8F5]/40'
              : (idCard.status === 'revoked' || idCard.status === 'expired'
                ? 'bg-rose-50/90 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border-rose-300 dark:border-rose-500/40'
                : 'bg-amber-50/90 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border-amber-300 dark:border-amber-500/40')
          ]"
        >
          <span
            class="w-1.5 h-1.5 rounded-full"
            :class="[
              idCard.is_official
                ? 'bg-emerald-500 dark:bg-emerald-400 animate-pulse'
                : (idCard.status === 'revoked' || idCard.status === 'expired'
                  ? 'bg-rose-500'
                  : 'bg-amber-500')
            ]"
          ></span>
          <span :class="currentLang === 'km' ? 'font-khmer' : 'font-sans uppercase tracking-wider font-black'">
            {{
              idCard.is_official
                ? (currentLang === 'km' ? 'កាតផ្លូវការ' : 'OFFICIAL ID')
                : (idCard.status === 'revoked'
                  ? (currentLang === 'km' ? 'កាតត្រូវបានដកហូត' : 'REVOKED')
                  : (idCard.status === 'expired'
                    ? (currentLang === 'km' ? 'ផុតសុពលភាព' : 'EXPIRED')
                    : (currentLang === 'km' ? 'រង់ចាំការអនុម័ត' : 'PENDING APPROVAL')))
            }}
          </span>
        </span>
      </div>
    </div>

    <!-- Main Content: Student Photo (Left), Identity & Academic Details (Center) -->
    <div class="flex items-center gap-3.5 sm:gap-4 my-auto py-1 relative z-10">
      
      <!-- Left: Student Portrait with Rounded Corners & Subtle Ring -->
      <div class="relative shrink-0">
        <div class="w-20 h-20 sm:w-22 sm:h-22 rounded-xl overflow-hidden bg-slate-100 dark:bg-[#0c1e4a] ring-2 ring-blue-500/40 dark:ring-[#16C8F5]/50 shadow-xl flex items-center justify-center">
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
        <!-- Live Verified / Registrar Approval Dot on Photo -->
        <span
          v-if="idCard.is_official"
          class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 border-2 border-white dark:border-[#071638] shadow-md flex items-center justify-center text-white text-[10px] font-black"
          title="Official University Verified"
        >
          ✓
        </span>
        <span
          v-else-if="idCard.status === 'revoked' || idCard.status === 'expired'"
          class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-rose-500 border-2 border-white dark:border-[#071638] shadow-md flex items-center justify-center text-white text-[10px] font-black"
          title="Revoked or Expired"
        >
          ✕
        </span>
      </div>

      <!-- Center: Student Names, ID Badge, Major, Academic Year, and Status -->
      <div class="space-y-1 min-w-0 flex-1">
        
        <!-- Name (English large bold + Khmer subtitle) -->
        <div class="leading-tight">
          <h2 class="text-sm sm:text-[15.5px] font-black text-slate-900 dark:text-white tracking-wide truncate uppercase drop-shadow-xs font-sans">
            {{ profile.name }}
          </h2>
          <p v-if="profile.name_kh" class="text-[11.5px] sm:text-xs font-semibold text-slate-600 dark:text-slate-300 truncate font-khmer pt-0.5 leading-normal">
            {{ profile.name_kh }}
          </p>
        </div>

        <!-- Student ID Highlighted in Soft Gold Badge -->
        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-amber-500/15 dark:bg-[#FFD54A]/15 border border-amber-500/40 dark:border-[#FFD54A]/40">
          <span
            class="text-[9px] text-amber-800 dark:text-slate-300 font-bold"
            :class="currentLang === 'km' ? 'font-khmer' : 'uppercase tracking-wider font-sans'"
          >
            {{ currentLang === 'km' ? 'អត្តលេខ:' : 'ID:' }}
          </span>
          <span class="font-mono text-xs sm:text-[12.5px] font-black text-amber-700 dark:text-[#FFD54A] tracking-wider">{{ profile.student_id }}</span>
        </div>

        <!-- Major & Faculty -->
        <div class="space-y-0.5">
          <p
            class="text-[10.5px] sm:text-[11px] text-slate-900 dark:text-slate-100 font-semibold truncate flex items-center gap-1"
            :class="currentLang === 'km' ? 'font-khmer' : 'font-sans'"
          >
            <span class="text-blue-600 dark:text-[#16C8F5] text-[9px]">●</span>
            <span class="truncate">{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</span>
          </p>
          <p
            v-if="academic.faculty || academic.faculty_kh"
            class="text-[9px] sm:text-[9.5px] text-slate-600 dark:text-slate-300/85 truncate pl-2.5 font-medium"
            :class="currentLang === 'km' ? 'font-khmer' : 'font-sans'"
          >
            {{ currentLang === 'km' ? (academic.faculty_kh || academic.faculty) : academic.faculty }}
          </p>
        </div>

        <!-- Academic Term / Status Pill -->
        <div class="flex items-center gap-2 pt-0.5">
          <span
            class="text-[9px] sm:text-[9.5px] font-bold text-blue-700 dark:text-[#16C8F5] truncate"
            :class="currentLang === 'km' ? 'font-khmer' : 'font-sans'"
          >
            {{ currentLang === 'km' ? (academic.term_kh || academic.term) : academic.term }}
          </span>
          <span
            class="px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-400/30 text-[8px] sm:text-[8.5px] font-bold shrink-0"
            :class="currentLang === 'km' ? 'font-khmer' : 'font-sans uppercase'"
          >
            {{ currentLang === 'km' ? 'និស្សិតកំពុងសិក្សា' : 'Active Student' }}
          </span>
        </div>

      </div>

    </div>

    <!-- Footer: Valid From, Card Number, and Valid Thru -->
    <div class="relative pt-2 pb-1 border-t border-slate-200/80 dark:border-white/10 z-10">
      <div class="flex items-center justify-between gap-1.5 sm:gap-2 text-[8.5px] sm:text-[9.5px] text-slate-700 dark:text-slate-100 whitespace-nowrap relative z-10">
        
        <!-- Valid From Date -->
        <div class="flex items-center gap-1 shrink-0">
          <span
            class="text-slate-500 dark:text-slate-400 text-[8px] sm:text-[8.5px]"
            :class="currentLang === 'km' ? 'font-khmer' : 'uppercase font-sans'"
          >
            {{ currentLang === 'km' ? 'ថ្ងៃចេញ:' : 'Valid From:' }}
          </span>
          <span class="font-bold text-slate-800 dark:text-slate-200" :class="currentLang === 'km' ? 'font-khmer leading-relaxed' : 'font-sans'">
            {{ currentLang === 'km' ? (idCard.issued_at_kh || idCard.issued_at) : idCard.issued_at }}
          </span>
        </div>

        <!-- Unique Card Number -->
        <div class="flex items-center gap-1 shrink-0 font-mono text-[8.5px] sm:text-[9px] text-slate-800 dark:text-slate-200 bg-blue-50 dark:bg-[#0c1e4a]/90 px-2 py-0.5 rounded-md border border-blue-200 dark:border-[#16C8F5]/35 shadow-xs">
          <span class="text-blue-700 dark:text-[#16C8F5] text-[7.5px] sm:text-[8px] font-sans font-bold">CARD:</span>
          <span class="font-bold text-slate-900 dark:text-white tracking-wide">{{ idCard.card_no }}</span>
        </div>

        <!-- Valid Until Date -->
        <div class="flex items-center gap-1 shrink-0">
          <span
            class="text-slate-500 dark:text-slate-400 text-[8px] sm:text-[8.5px]"
            :class="currentLang === 'km' ? 'font-khmer' : 'uppercase font-sans'"
          >
            {{ currentLang === 'km' ? 'សុពលភាព:' : 'Valid Thru:' }}
          </span>
          <span class="font-bold text-amber-700 dark:text-[#FFD54A]" :class="currentLang === 'km' ? 'font-khmer leading-relaxed' : 'font-sans'">
            {{ currentLang === 'km' ? (idCard.expires_at_kh || idCard.expires_at) : idCard.expires_at }}
          </span>
        </div>

      </div>
    </div>

  </div>
</template>
