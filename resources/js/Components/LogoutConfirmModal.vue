<script setup lang="ts">
import { computed, watch, onMounted, onUnmounted } from 'vue'
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

// Preload the Login page chunk ahead of time while user views the modal
// This ensures redirect after logout is instant (0ms wait)
watch(
  () => props.show,
  (isOpen) => {
    if (isOpen) {
      try {
        import('@/Pages/Auth/Login.vue')
      } catch (e) {
        // silent
      }
    }
  },
  { immediate: true }
)

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
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto select-none"
        role="dialog"
        aria-modal="true"
        @click.self="!loading && emit('close')"
      >
        <!-- Backdrop Glass Blur (Matching Login Modal Backdrop) -->
        <div
          class="fixed inset-0 bg-black/65 backdrop-blur-md transition-opacity"
          @click="!loading && emit('close')"
        ></div>

        <!-- Centered Dialog Box (Matching Login Form Card with Border Beam) -->
        <div
          class="border-beam-modal relative w-full max-w-[410px] p-6 sm:p-7 rounded-2xl sm:rounded-3xl shadow-2xl text-zinc-900 dark:text-white overflow-hidden text-center z-10 transition-all transform"
          @click.stop
        >
          <!-- Subtle Top Ambient Glow -->
          <div
            class="absolute -top-16 left-1/2 -translate-x-1/2 w-44 h-44 bg-rose-500/15 dark:bg-rose-500/20 rounded-full blur-3xl pointer-events-none"
          ></div>

          <!-- Close Icon in Top Corner (Matching Login Form style) -->
          <button
            type="button"
            @click="emit('close')"
            :disabled="loading"
            class="absolute top-4 right-4 p-1.5 text-zinc-400 hover:text-zinc-700 dark:hover:text-white rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition cursor-pointer disabled:opacity-40"
            title="Close"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Dialog Title (Matching Login Gradient Heading) -->
          <h2 class="text-xl sm:text-[22px] font-black tracking-tight bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500 dark:from-white dark:via-zinc-100 dark:to-zinc-300 bg-clip-text text-transparent text-center leading-snug pt-2">
            {{ isKhmer ? 'តើអ្នកពិតជាចង់ចាកចេញពីប្រព័ន្ធមែនទេ?' : 'Are you sure you want to log out?' }}
          </h2>

          <!-- Subtitle Message (Matching Login text style) -->
          <p class="text-xs text-slate-600 dark:text-zinc-400 text-center mt-1.5 mb-5 leading-relaxed">
            {{ isKhmer ? 'រាល់ទិន្នន័យ ឬកិច្ចការដែលមិនទាន់រក្សាទុកអាចនឹងត្រូវបាត់បង់។ លោកអ្នកនឹងត្រូវចូលគណនីម្តងទៀតដើម្បីប្រើប្រាស់ប្រព័ន្ធ។' : 'Any unsaved changes or progress may be lost. You will need to sign in again to access the platform.' }}
          </p>

          <!-- User Identity Pill (Matching Login's Matched User Identity Card) -->
          <div class="mb-5 p-3 rounded-xl bg-zinc-50/80 dark:bg-zinc-900/70 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between gap-3 text-left shadow-2xs">
            <div class="flex items-center gap-3 min-w-0">
              <img
                :src="userAvatar"
                class="w-10 h-10 rounded-full object-cover ring-2 ring-zinc-200 dark:ring-zinc-700 shrink-0"
                alt="User Avatar"
              />
              <div class="min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ userName }}</span>
                  <OfficialVerifiedBadge :role="userRole" size="xs" />
                  <!-- Explicit Role Badge matching Login Form -->
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold shrink-0 border uppercase tracking-wider',
                      userRole === 'admin'
                        ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30'
                        : userRole === 'teacher'
                          ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
                          : 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-500/30'
                    ]"
                  >
                    <i :class="['text-[9px]', userRole === 'admin' ? 'pi pi-shield' : userRole === 'teacher' ? 'pi pi-briefcase' : 'pi pi-graduation-cap']"></i>
                    <span>{{ roleBadgeLabel }}</span>
                  </span>
                </div>
                <p v-if="userEmail" class="text-[11px] text-zinc-500 dark:text-zinc-400 font-mono truncate mt-0.5">{{ userEmail }}</p>
              </div>
            </div>
          </div>

          <!-- Action Buttons (Clean Login Form Button Style) -->
          <div class="grid grid-cols-2 gap-2.5">
            <button
              type="button"
              @click="emit('close')"
              :disabled="loading"
              class="w-full h-11 px-4 rounded-xl border border-zinc-300 dark:border-zinc-700/80 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 font-semibold text-xs sm:text-sm transition-all active:scale-[0.99] cursor-pointer disabled:opacity-40 flex items-center justify-center shadow-2xs select-none"
            >
              <span>{{ isKhmer ? 'បោះបង់' : 'Cancel' }}</span>
            </button>

            <button
              type="button"
              @click="emit('confirm')"
              :disabled="loading"
              class="w-full h-11 px-4 rounded-xl bg-gradient-to-r from-rose-600 via-red-600 to-rose-700 hover:from-rose-700 hover:to-red-800 text-white font-semibold text-xs sm:text-sm shadow-md shadow-rose-600/25 active:scale-[0.99] transition-all cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50 select-none"
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

<style>
@property --beam-angle {
  syntax: '<angle>';
  inherits: false;
  initial-value: 0deg;
}

@keyframes beam-rotate {
  0% {
    --beam-angle: 0deg;
  }
  100% {
    --beam-angle: 360deg;
  }
}

/* Modal Card Border Beam */
.border-beam-modal {
  --beam-bg: #ffffff;
  --beam-border-base: #e4e4e7;
  --beam-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);

  position: relative;
  border: 1.5px solid transparent !important;
  background-clip: padding-box, border-box !important;
  background-origin: padding-box, border-box !important;
  background-image:
    linear-gradient(var(--beam-bg), var(--beam-bg)),
    conic-gradient(
      from var(--beam-angle),
      var(--beam-border-base) 0deg,
      var(--beam-border-base) 260deg,
      rgba(37, 99, 235, 0.4) 285deg,
      #3b82f6 320deg,
      #60a5fa 355deg,
      var(--beam-border-base) 360deg
    ) !important;
  animation: beam-rotate 6s linear infinite;
  box-shadow: var(--beam-shadow);
}

html.dark .border-beam-modal,
.dark .border-beam-modal {
  --beam-bg: #121214 !important;
  --beam-border-base: #27272a !important;
  --beam-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.6) !important;
  background-image:
    linear-gradient(var(--beam-bg), var(--beam-bg)),
    conic-gradient(
      from var(--beam-angle),
      var(--beam-border-base) 0deg,
      var(--beam-border-base) 260deg,
      rgba(244, 63, 94, 0.35) 285deg,
      #f43f5e 320deg,
      #fda4af 355deg,
      var(--beam-border-base) 360deg
    ) !important;
}
</style>
