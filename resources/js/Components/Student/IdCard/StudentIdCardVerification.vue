<script setup lang="ts">
import { ref } from 'vue'

interface ProfileData {
  id: number
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
  status: 'active' | 'revoked' | 'pending' | 'expired'
  is_official: boolean
  is_live_verified?: boolean
  days_remaining?: number
  is_expiring_soon?: boolean
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
  linkCopied: boolean
  isRegeneratingToken?: boolean
}>()

const emit = defineEmits<{
  (e: 'printCard'): void
  (e: 'copyLink'): void
  (e: 'copyCardNo'): void
  (e: 'openRegenerateModal'): void
  (e: 'reportLost'): void
}>()

const cardNoCopied = ref(false)
const copyCardNumber = () => {
  emit('copyCardNo')
  cardNoCopied.value = true
  setTimeout(() => {
    cardNoCopied.value = false
  }, 2000)
}
</script>

<template>
  <div class="w-full flex flex-col justify-between space-y-6">
    
    <!-- Header -->
    <div>
      <div class="flex items-center justify-between">
        <h3 class="text-[15px] font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
          <span>{{ currentLang === 'km' ? 'សុពលភាព & ស្ថានភាពកាត' : 'Card Verification Information' }}</span>
        </h3>
        
        <!-- Live Verified Badge (links to real public verification route) -->
        <a
          v-if="idCard.is_live_verified"
          :href="idCard.verify_url"
          target="_blank"
          class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-[#1459D9]/10 text-[#1459D9] dark:text-[#16C8F5] border border-[#1459D9]/25 inline-flex items-center gap-1 hover:underline transition-all"
        >
          <span>Live Verified</span>
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
        </a>
      </div>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
        {{ currentLang === 'km' ? 'គ្រប់គ្រងកាតសម្គាល់ខ្លួន និងផ្ទៀងផ្ទាត់តំណភ្ជាប់សាធារណៈផ្លូវការ' : 'Official verification metadata and security management' }}
      </p>
    </div>

    <!-- Soft Panel Rows with Metrics -->
    <div class="p-4.5 bg-[#f8fafc] dark:bg-[#131314] rounded-2xl border border-[#dfe3e7] dark:border-[#2d2f31] space-y-3.5 text-xs shadow-2xs">
      
      <!-- Card No with Copy Button (Deduplicated Student ID removed) -->
      <div class="flex items-center justify-between">
        <span class="text-slate-500 dark:text-slate-400 font-medium">
          {{ currentLang === 'km' ? 'លេខកាត:' : 'Card Number:' }}
        </span>
        <div class="flex items-center gap-2">
          <span class="font-mono font-bold text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-800/80 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">
            {{ idCard.card_no }}
          </span>
          <button
            type="button"
            @click="copyCardNumber"
            :title="currentLang === 'km' ? 'ចម្លងលេខកាត' : 'Copy Card Number'"
            class="p-1 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 transition-colors cursor-pointer"
          >
            <span v-if="cardNoCopied" class="text-emerald-500 text-[10px] font-bold">✓</span>
            <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Status -->
      <div class="flex items-center justify-between">
        <span class="text-slate-500 dark:text-slate-400 font-medium">
          {{ currentLang === 'km' ? 'ស្ថានភាពផ្លូវការ:' : 'Status:' }}
        </span>
        <span
          v-if="idCard.is_official"
          class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 inline-flex items-center gap-1.5"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          <span>{{ currentLang === 'km' ? 'បានអនុម័តផ្លូវការ' : 'Active Enrolled' }}</span>
        </span>
        <span
          v-else-if="idCard.status === 'revoked'"
          class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 inline-flex items-center gap-1.5"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
          <span>{{ currentLang === 'km' ? 'ត្រូវបានដកហូត' : 'Revoked' }}</span>
        </span>
        <span
          v-else-if="idCard.status === 'expired'"
          class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 inline-flex items-center gap-1.5"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
          <span>{{ currentLang === 'km' ? 'ផុតសុពលភាព' : 'Expired' }}</span>
        </span>
        <span
          v-else
          class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
        >
          ● {{ currentLang === 'km' ? 'រង់ចាំការអនុម័ត' : 'Pending Approval' }}
        </span>
      </div>

      <!-- Issue Date -->
      <div class="flex items-center justify-between">
        <span class="text-slate-500 dark:text-slate-400 font-medium">
          {{ currentLang === 'km' ? 'កាលបរិច្ឆេទចេញ:' : 'Issue Date:' }}
        </span>
        <span class="font-medium text-slate-700 dark:text-slate-300">
          {{ currentLang === 'km' ? (idCard.issued_at_kh || idCard.issued_at || '—') : (idCard.issued_at || '—') }}
        </span>
      </div>

      <!-- Expiry Date -->
      <div class="flex items-center justify-between">
        <span class="text-slate-500 dark:text-slate-400 font-medium">
          {{ currentLang === 'km' ? 'កាលបរិច្ឆេទផុតកំណត់:' : 'Expiry Date:' }}
        </span>
        <span class="font-bold text-slate-900 dark:text-white">
          {{ currentLang === 'km' ? (idCard.expires_at_kh || idCard.expires_at || '—') : (idCard.expires_at || '—') }}
        </span>
      </div>

      <!-- Expiry Warning Chip if <= 90 days -->
      <div
        v-if="idCard.is_expiring_soon"
        class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-400 flex items-center justify-between text-[11px] font-bold"
      >
        <span class="flex items-center gap-1.5 font-khmer">
          <span>⚠️</span>
          <span>{{ currentLang === 'km' ? `ផុតកំណត់ក្នុង ${idCard.days_remaining} ថ្ងៃ` : `Expiring in ${idCard.days_remaining} days` }}</span>
        </span>
        <span class="text-[10px] font-normal underline">{{ currentLang === 'km' ? 'ទាក់ទងរដ្ឋបាល' : 'Contact Registry' }}</span>
      </div>

    </div>

    <!-- Action Buttons -->
    <div class="space-y-3">
      
      <!-- Prominent Primary Button: Purple/Royal Blue Gradient (Download PDF & Print Card) -->
      <button
        type="button"
        @click="emit('printCard')"
        class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-[#7045F5] via-[#4842e4] to-[#1459D9] hover:from-[#5d31e3] hover:to-[#104bc2] text-white font-bold text-xs shadow-lg shadow-purple-500/25 transition-all flex items-center justify-center gap-2 hover:scale-[1.01] active:scale-[0.99] cursor-pointer font-khmer"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
        <span>{{ currentLang === 'km' ? 'បោះពុម្ពកាត / ទាញយក PDF' : 'Download PDF / Print Card' }}</span>
      </button>

      <!-- Secondary Button: Copy Verification Link -->
      <button
        type="button"
        @click="emit('copyLink')"
        class="w-full py-3 px-4 rounded-2xl bg-[#f0f4f9] dark:bg-[#131314] hover:bg-slate-200 dark:hover:bg-[#282a2c] text-slate-800 dark:text-slate-200 font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer border border-[#dfe3e7] dark:border-[#2d2f31] shadow-2xs hover:shadow-xs active:scale-[0.99] font-khmer"
      >
        <svg class="w-4 h-4 text-[#1459D9] dark:text-[#16C8F5]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        <span>{{ linkCopied ? (currentLang === 'km' ? '✓ បានចម្លងតំណភ្ជាប់' : '✓ Verification Link Copied!') : (currentLang === 'km' ? 'ចម្លងតំណភ្ជាប់ផ្ទៀងផ្ទាត់' : 'Copy Verification Link') }}</span>
      </button>

      <!-- Tertiary Subtle Button: Refresh QR Code -->
      <button
        type="button"
        @click="emit('openRegenerateModal')"
        class="w-full py-2.5 px-4 rounded-2xl border border-blue-200 dark:border-blue-900/60 bg-blue-50/50 dark:bg-blue-950/20 hover:bg-blue-100/60 dark:hover:bg-blue-900/40 text-blue-700 dark:text-blue-300 font-bold text-xs transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99] font-khmer"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>{{ currentLang === 'km' ? 'បង្កើតកូដ QR ថ្មី' : 'Refresh QR Code' }}</span>
      </button>

      <!-- Report Lost Card Button -->
      <button
        type="button"
        @click="emit('reportLost')"
        class="w-full py-2 px-4 rounded-2xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/40 dark:bg-rose-950/20 hover:bg-rose-100/60 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-400 font-semibold text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer active:scale-[0.99] font-khmer"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>{{ currentLang === 'km' ? 'រាយការណ៍បាត់កាត (Report Lost)' : 'Report Lost Card' }}</span>
      </button>

    </div>

  </div>
</template>
