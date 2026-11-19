<script setup lang="ts">
interface ProfileData {
  id: number
  name: string
  name_kh?: string
  student_id: string
}

interface IdCardData {
  card_no: string
  token: string
  verify_url: string
  issued_at?: string
  issued_at_kh?: string
  expires_at?: string
  expires_at_kh?: string
  status: 'active' | 'revoked' | 'pending'
  is_official: boolean
  institution: string
  institution_kh: string
}

interface AcademicData {
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
  qrDataUrl: string
  cardLogoUrl?: string
}>()
</script>

<template>
  <div
    class="card-3d-face card-3d-back absolute inset-0 w-full h-full rounded-2xl p-4 sm:p-4.5 bg-[#071638] border border-blue-500/25 shadow-2xl text-white overflow-hidden flex flex-col justify-between ring-1 ring-white/10 select-none"
  >
    <!-- Background Watermarks & Subtle Geometric Mesh -->
    <img
      :src="cardLogoUrl || '/images/logo.png'"
      alt=""
      class="absolute left-[-24px] -bottom-10 w-56 h-56 object-contain rounded-full opacity-[0.06] pointer-events-none select-none blur-[0.4px]"
    />
    <div class="absolute inset-0 bg-[radial-gradient(#16C8F5_1px,transparent_1px)] [background-size:18px_18px] opacity-[0.05] pointer-events-none"></div>

    <!-- Top: Magnetic Security Stripe -->
    <div class="w-full h-7 bg-gradient-to-r from-[#030816] via-[#0f172a] to-[#030816] rounded-lg border border-slate-800 -mx-0.5 flex items-center justify-between px-3 shadow-inner relative z-10">
      <span class="text-[8px] font-mono tracking-widest text-slate-400 uppercase">
        {{ currentLang === 'km' ? 'ការផ្ទៀងផ្ទាត់អត្តសញ្ញាណនិស្សិត' : 'STUDENT IDENTITY VERIFICATION • SPI' }}
      </span>
      <span class="text-[7.5px] font-mono text-[#FFD54A] tracking-wider">SPI SECURE SYSTEM</span>
    </div>

    <!-- Center: 2 Balanced Sections (Left = High-Res QR Code, Right = Student Details & Terms) -->
    <div class="flex items-center justify-between gap-3.5 my-auto py-1 relative z-10">
      
      <!-- Left Section: Large Clearly Scannable QR Code with "Scan to Verify" label -->
      <div class="flex flex-col items-center shrink-0">
        <div class="bg-white p-2 rounded-xl shadow-lg border border-slate-200 shrink-0">
          <img
            v-if="qrDataUrl"
            :src="qrDataUrl"
            alt="Official Student Verification QR"
            class="w-[74px] h-[74px] sm:w-[80px] sm:h-[80px] rounded-md object-contain"
          />
          <div v-else class="w-[74px] h-[74px] flex items-center justify-center bg-slate-100 text-[10px] text-slate-500 font-mono">
            QR
          </div>
        </div>
        <span class="text-[8px] font-mono text-[#16C8F5] mt-1 uppercase tracking-wider font-bold">
          {{ currentLang === 'km' ? 'ស្កេនផ្ទៀងផ្ទាត់' : 'Scan to Verify' }}
        </span>
      </div>

      <!-- Right Section: Student Name, ID, Major, Card No, Signature & Disclaimer -->
      <div class="space-y-1.5 text-[9px] sm:text-[9.5px] text-slate-300 max-w-[245px] leading-relaxed flex-1">
        
        <!-- Student Identity Row -->
        <div>
          <p class="font-bold text-white text-xs truncate uppercase">{{ profile.name }}</p>
          <div class="flex items-center gap-2 text-[9px] pt-0.5">
            <span class="text-[#FFD54A] font-mono font-bold">ID: {{ profile.student_id }}</span>
            <span class="text-slate-400">•</span>
            <span class="text-slate-200 truncate">{{ currentLang === 'km' ? (academic.major_kh || academic.major) : academic.major }}</span>
          </div>
        </div>

        <!-- Authorized Signature Strip -->
        <div class="w-full bg-slate-100 rounded border border-slate-300 px-2 py-0.5 flex items-center justify-between select-none">
          <span class="text-[7px] font-mono text-slate-600 uppercase tracking-wider">
            {{ currentLang === 'km' ? 'ហត្ថលេខាម្ចាស់កាត' : 'AUTHORIZED SIGNATURE' }}
          </span>
          <span class="text-[7.5px] font-mono font-bold text-slate-800">{{ profile.student_id }}</span>
        </div>

        <!-- Official Terms of Use & Disclaimer -->
        <p class="text-[8px] sm:text-[8.5px] text-slate-300 leading-tight">
          {{ currentLang === 'km' ? 'កាតនេះជាកម្មសិទ្ធិរបស់វិទ្យាស្ថាន សន្តប៉ូល។ ប្រសិនបើរើសបាន សូមមេត្តាប្រគល់ជូនការិយាល័យសិក្សា ឬទាក់ទងមកវិទ្យាស្ថាន។' : 'This card is issued by Saint Paul Institute. If found, please return to the Academic Registry Office.' }}
        </p>

        <!-- Dates row: Issued & Expiry -->
        <div class="flex items-center justify-between text-[8px] text-slate-300 pt-0.5 border-t border-white/10">
          <div>
            <span class="text-slate-400">{{ currentLang === 'km' ? 'ថ្ងៃចេញ:' : 'Issued:' }}</span>
            <span class="font-medium text-white ml-1">{{ currentLang === 'km' ? (idCard.issued_at_kh || idCard.issued_at) : idCard.issued_at }}</span>
          </div>
          <div>
            <span class="text-slate-400">{{ currentLang === 'km' ? 'ផុតកំណត់:' : 'Expires:' }}</span>
            <span class="font-medium text-[#FFD54A] ml-1">{{ currentLang === 'km' ? (idCard.expires_at_kh || idCard.expires_at) : idCard.expires_at }}</span>
          </div>
        </div>

      </div>

    </div>

    <!-- Footer: Website, Verified by SPI, and Card Validity Status with subtle bottom wave -->
    <div class="relative pt-1.5 border-t border-white/10 z-10">
      
      <!-- Subtle Blue-to-Purple Wave on Back -->
      <div class="absolute inset-x-0 bottom-0 h-5 pointer-events-none opacity-30 overflow-hidden">
        <svg viewBox="0 0 500 50" preserveAspectRatio="none" class="w-full h-full">
          <path
            d="M0,20 C180,45 320,5 500,25 L500,50 L0,50 Z"
            fill="url(#backWaveGrad)"
          ></path>
          <defs>
            <linearGradient id="backWaveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#1459D9" />
              <stop offset="50%" stop-color="#7045F5" />
              <stop offset="100%" stop-color="#16C8F5" />
            </linearGradient>
          </defs>
        </svg>
      </div>

      <div class="flex items-center justify-between text-[8.5px] sm:text-[9px] font-mono text-slate-300 relative z-10">
        <!-- Website -->
        <a
          href="https://spilms.tech"
          target="_blank"
          class="flex items-center gap-1 text-[#16C8F5] hover:underline"
        >
          <span>🌐</span>
          <span>spilms.tech</span>
        </a>

        <!-- Verified by SPI -->
        <div class="flex items-center gap-1 text-slate-300">
          <svg class="w-3 h-3 text-[#16C8F5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          <span class="font-bold">{{ currentLang === 'km' ? 'បញ្ជាក់ដោយ SPI' : 'Verified by SPI' }}</span>
        </div>

        <!-- Card validity status -->
        <div class="flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
          <span class="font-bold text-emerald-300 uppercase">
            {{ currentLang === 'km' ? 'កាតផ្លូវការ' : 'ACTIVE' }}
          </span>
        </div>
      </div>

    </div>

  </div>
</template>
