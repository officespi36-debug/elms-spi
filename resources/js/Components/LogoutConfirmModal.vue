<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { i18n } from '@/Services/i18n'
import OfficialVerifiedBadge from '@/Components/OfficialVerifiedBadge.vue'

const props = withDefaults(
  defineProps<{
    show: boolean
    user?: any
    loading?: boolean
  }>(),
  {
    show: false,
    loading: false,
  }
)

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'confirm'): void
}>()

const isKhmer = computed(() => (i18n.locale?.value || 'km') === 'km')

const userName = computed(() => props.user?.name || 'User')
const userEmail = computed(() => props.user?.email || '')
const userRole = computed(() => (props.user?.role || 'admin').toLowerCase())
const userAvatar = computed(() => {
  if (props.user?.avatar) return props.user.avatar
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(userName.value)}&background=6366f1&color=fff`
})

const roleBadgeLabel = computed(() => {
  if (isKhmer.value) {
    if (userRole.value === 'admin') return 'អ្នកគ្រប់គ្រងជាន់ខ្ពស់'
    if (userRole.value === 'teacher') return 'សាស្ត្រាចារ្យ'
    return 'និស្សិត'
  }
  if (userRole.value === 'admin') return 'Super Admin'
  if (userRole.value === 'teacher') return 'Instructor'
  return 'Student'
})

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.show && !props.loading) {
    emit('close')
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <teleport to="body">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
        role="dialog"
        aria-modal="true"
      >
        <!-- Backdrop Glass Blur -->
        <div
          class="fixed inset-0 bg-slate-950/75 backdrop-blur-md transition-opacity"
          @click="!loading && emit('close')"
        ></div>

        <!-- Centered Dialog Box -->
        <div
          class="relative w-full max-w-md bg-white dark:bg-[#12141c] border border-slate-200/90 dark:border-slate-800/90 rounded-3xl shadow-2xl p-6 sm:p-7 text-center overflow-hidden z-10 transition-all transform select-none"
          @click.stop
        >
          <!-- Ambient Top Radial Glow (Rose Glow) -->
          <div
            class="absolute -top-20 left-1/2 -translate-x-1/2 w-48 h-48 bg-rose-500/20 rounded-full blur-3xl pointer-events-none"
          ></div>

          <!-- Close Icon in Top Corner -->
          <button
            type="button"
            @click="emit('close')"
            :disabled="loading"
            class="absolute top-4 right-4 p-2 rounded-full text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition cursor-pointer disabled:opacity-40"
            title="Close"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- 3D-styled Dual-ring Logout Icon -->
          <div class="relative mx-auto w-16 h-16 mb-4 flex items-center justify-center rounded-2xl bg-gradient-to-tr from-rose-500/15 to-red-500/10 border border-rose-500/25 shadow-lg shadow-rose-500/10">
            <span class="animate-ping absolute inline-flex h-8 w-8 rounded-full bg-rose-400 opacity-20"></span>
            <svg class="w-8 h-8 text-rose-500 transform -translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </div>

          <!-- Dialog Title (Khmer & English Support) -->
          <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white tracking-tight leading-snug">
            {{ isKhmer ? 'តើអ្នកពិតជាចង់ចាកចេញពីប្រព័ន្ធមែនទេ?' : 'Are you sure you want to log out?' }}
          </h3>

          <!-- Subtitle Message -->
          <p class="mt-2 text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed max-w-sm mx-auto">
            {{ isKhmer ? 'រាល់ទិន្នន័យ ឬកិច្ចការដែលមិនទាន់រក្សាទុកអាចនឹងត្រូវបាត់បង់។ លោកអ្នកនឹងត្រូវចូលគណនីម្តងទៀតដើម្បីប្រើប្រាស់ប្រព័ន្ធ។' : 'Any unsaved changes or progress may be lost. You will need to sign in again to access the platform.' }}
          </p>

          <!-- User Identity Card Preview -->
          <div class="my-5 p-3 rounded-2xl bg-slate-50/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800 flex items-center justify-center gap-3 text-left">
            <img
              :src="userAvatar"
              class="w-10 h-10 rounded-full object-cover ring-2 ring-rose-500/30 shrink-0"
              alt="User Avatar"
            />
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ userName }}</span>
                <OfficialVerifiedBadge :role="userRole" size="xs" />
                <span class="px-1.5 py-0.2 text-[9px] font-extrabold rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                  {{ roleBadgeLabel }}
                </span>
              </div>
              <p v-if="userEmail" class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5">{{ userEmail }}</p>
            </div>
          </div>

          <!-- Action Buttons (បោះបង់ & ចាកចេញពីប្រព័ន្ធ) -->
          <div class="grid grid-cols-2 gap-3 mt-6">
            <button
              type="button"
              @click="emit('close')"
              :disabled="loading"
              class="w-full py-2.5 px-4 rounded-xl border border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 font-bold text-xs sm:text-sm hover:bg-slate-100 dark:hover:bg-slate-800 transition active:scale-95 cursor-pointer disabled:opacity-40"
            >
              <span>{{ isKhmer ? 'បោះបង់' : 'Cancel' }}</span>
            </button>

            <button
              type="button"
              @click="emit('confirm')"
              :disabled="loading"
              class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-rose-500 via-rose-600 to-red-600 hover:from-rose-600 hover:to-red-700 text-white font-bold text-xs sm:text-sm shadow-lg shadow-rose-500/25 transition active:scale-95 cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? (isKhmer ? 'កំពុងចាកចេញ...' : 'Logging out...') : (isKhmer ? 'ចាកចេញពីប្រព័ន្ធ' : 'Log Out') }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
