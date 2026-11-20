<script setup lang="ts">
import { computed } from 'vue'
import StudentIdCardFront from './StudentIdCardFront.vue'
import StudentIdCardBack from './StudentIdCardBack.vue'

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
  qrDataUrl: string
  isFlipped: boolean
  cardLogoUrl?: string
}>()

const emit = defineEmits<{
  (e: 'toggleFlip'): void
}>()

const handleFlip = () => {
  emit('toggleFlip')
}
</script>

<template>
  <div class="w-full flex flex-col items-center justify-between space-y-5">
    
    <!-- Top Row: Active Side Indicator & Flip Card Action Button -->
    <div class="w-full flex items-center justify-between text-xs text-slate-500 font-khmer">
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-blue-600 dark:bg-[#16C8F5] shadow-sm"></span>
        <span class="font-bold text-slate-900 dark:text-slate-100">
          {{ isFlipped ? (currentLang === 'km' ? 'ផ្នែកខាងក្រោយនៃកាត' : 'Card Back Side') : (currentLang === 'km' ? 'ផ្នែកខាងមុខនៃកាត' : 'Card Front Side') }}
        </span>
      </div>

      <!-- Clear Flip Card Button -->
      <button
        type="button"
        :aria-pressed="isFlipped"
        @click="handleFlip"
        class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-[#131314] hover:bg-slate-200 dark:hover:bg-[#282a2c] text-slate-700 dark:text-slate-200 font-bold font-khmer text-xs transition-all cursor-pointer inline-flex items-center gap-2 border border-slate-200 dark:border-[#2d2f31] shadow-2xs hover:shadow-xs active:scale-95"
      >
        <svg
          class="w-4 h-4 text-indigo-600 dark:text-[#7045F5] transition-transform duration-500"
          :class="{ 'rotate-180': isFlipped }"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>{{ currentLang === 'km' ? 'ត្រឡប់កាត' : 'Flip Card' }}</span>
      </button>
    </div>

    <!-- 3D Card Scene Container (Aspect ratio ~1.586 : 1, standard CR80) -->
    <button
      id="student-digital-id-card"
      type="button"
      :aria-pressed="isFlipped"
      @click="handleFlip"
      @keydown.space.prevent="handleFlip"
      @keydown.enter.prevent="handleFlip"
      :aria-label="currentLang === 'km' ? 'កាតនិស្សិតឌីជីថល ចុចដើម្បីបង្វិល' : 'Digital Student ID Card. Click or press Spacebar to flip.'"
      class="card-3d-scene w-full max-w-[440px] aspect-[1.586/1] select-none cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-2xl group transition-all duration-300 hover:scale-[1.01] hover:shadow-2xl text-left p-0 border-0 bg-transparent block relative"
    >
      <div
        class="card-3d-inner w-full h-full relative transition-transform duration-600 ease-in-out"
        :class="{ 'is-flipped': isFlipped }"
      >
        <!-- FRONT SIDE -->
        <StudentIdCardFront
          :profile="profile"
          :idCard="idCard"
          :academic="academic"
          :currentLang="currentLang"
          :avatarPreview="avatarPreview"
          :cardLogoUrl="cardLogoUrl"
        />

        <!-- BACK SIDE -->
        <StudentIdCardBack
          :profile="profile"
          :idCard="idCard"
          :academic="academic"
          :currentLang="currentLang"
          :qrDataUrl="qrDataUrl"
          :cardLogoUrl="cardLogoUrl"
        />
      </div>
    </button>

    <!-- Caption / Hint below card -->
    <p class="text-[11px] text-slate-400 dark:text-slate-500 text-center flex items-center justify-center gap-1.5">
      <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
      </svg>
      <span>{{ currentLang === 'km' ? 'ចុចលើកាត ឬចុច Spacebar ដើម្បីត្រឡប់កាត' : 'Click on card or press Spacebar to flip between front and back' }}</span>
    </p>

  </div>
</template>

<style scoped>
.card-3d-scene {
  perspective: 1200px;
}

.card-3d-inner {
  transform-style: preserve-3d;
  will-change: transform;
}

.card-3d-inner.is-flipped {
  transform: rotateY(180deg);
}

:deep(.card-3d-face) {
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
}

:deep(.card-3d-back) {
  transform: rotateY(180deg);
}

/* Reduced motion preference: fade transition instead of 3D flip */
@media (prefers-reduced-motion: reduce) {
  .card-3d-scene {
    perspective: none;
  }
  .card-3d-inner {
    transform-style: flat;
    transition: opacity 0.3s ease;
  }
  .card-3d-inner.is-flipped {
    transform: none;
  }
  :deep(.card-3d-face) {
    backface-visibility: visible;
    -webkit-backface-visibility: visible;
  }
  :deep(.card-3d-front) {
    opacity: 1;
    transition: opacity 0.3s ease;
  }
  .card-3d-inner.is-flipped :deep(.card-3d-front) {
    opacity: 0;
    pointer-events: none;
  }
  :deep(.card-3d-back) {
    transform: none;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
  }
  .card-3d-inner.is-flipped :deep(.card-3d-back) {
    opacity: 1;
    pointer-events: auto;
  }
}
</style>
