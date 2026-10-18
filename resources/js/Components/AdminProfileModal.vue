<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { i18n } from '@/Services/i18n'
import OfficialVerifiedBadge from '@/Components/OfficialVerifiedBadge.vue'

interface Props {
  show: boolean
  user: any
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'close'): void
  (e: 'triggerAvatarUpload'): void
}>()

const isKhmer = computed(() => (i18n.currentLocale?.value || i18n.locale?.value || 'km') === 'km')

const activeTab = ref<'personal' | 'security'>('personal')

// Personal Form State
const profileForm = ref({
  name: '',
  name_kh: '',
  email: '',
  phone: '',
})
const profileLoading = ref(false)
const profileError = ref<string | null>(null)
const profileSuccess = ref(false)

// Password Form State
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const passwordLoading = ref(false)
const passwordError = ref<string | null>(null)
const passwordSuccess = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)

// Populate fields when modal opens or user changes
watch(
  () => [props.show, props.user],
  ([show]) => {
    if (show && props.user) {
      profileForm.value = {
        name: props.user.name || '',
        name_kh: props.user.name_kh || '',
        email: props.user.email || '',
        phone: props.user.phone || '',
      }
      profileError.value = null
      profileSuccess.value = false
      passwordForm.value = {
        current_password: '',
        password: '',
        password_confirmation: '',
      }
      passwordError.value = null
      passwordSuccess.value = false
    }
  },
  { immediate: true }
)

const handleProfileSubmit = () => {
  profileLoading.value = true
  profileError.value = null
  profileSuccess.value = false

  router.post(
    '/admin/profile/update',
    {
      name: profileForm.value.name,
      name_kh: profileForm.value.name_kh,
      email: profileForm.value.email,
      phone: profileForm.value.phone,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        profileLoading.value = false
        profileSuccess.value = true
        setTimeout(() => {
          profileSuccess.value = false
        }, 3000)
      },
      onError: (errs) => {
        profileLoading.value = false
        profileError.value = Object.values(errs)[0] as string || 'Failed to update profile.'
      },
      onFinish: () => {
        profileLoading.value = false
      },
    }
  )
}

const handlePasswordSubmit = () => {
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    passwordError.value = isKhmer.value ? 'ការបញ្ជាក់ពាក្យសម្ងាត់មិនត្រូវគ្នាទេ' : 'Password confirmation does not match.'
    return
  }

  passwordLoading.value = true
  passwordError.value = null
  passwordSuccess.value = false

  router.post(
    '/admin/profile/password',
    {
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.password,
      password_confirmation: passwordForm.value.password_confirmation,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        passwordLoading.value = false
        passwordSuccess.value = true
        passwordForm.value = {
          current_password: '',
          password: '',
          password_confirmation: '',
        }
        setTimeout(() => {
          passwordSuccess.value = false
        }, 3000)
      },
      onError: (errs) => {
        passwordLoading.value = false
        passwordError.value = Object.values(errs)[0] as string || 'Failed to update password.'
      },
      onFinish: () => {
        passwordLoading.value = false
      },
    }
  )
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div
      class="fixed inset-0 bg-slate-950/75 backdrop-blur-sm transition-opacity"
      @click="emit('close')"
    ></div>

    <!-- Modal Box -->
    <div
      class="relative w-full max-w-lg bg-white dark:bg-[#0f172a] border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl overflow-hidden z-10 animate-in fade-in zoom-in-95 duration-200 text-slate-800 dark:text-slate-100 font-sans"
      @click.stop
    >
      <!-- Top Header -->
      <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/60 dark:bg-slate-900/40">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-blue-500/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-tight">
              {{ isKhmer ? 'គ្រប់គ្រងគណនីផ្ទាល់ខ្លួន' : 'Manage Profile' }}
            </h3>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
              {{ isKhmer ? 'ព័ត៌មានគណនី និងសុវត្ថិភាព Super Admin' : 'Super Admin credentials & security' }}
            </p>
          </div>
        </div>

        <button
          type="button"
          @click="emit('close')"
          class="w-7 h-7 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- User Identity Card -->
      <div class="px-6 py-4 bg-gradient-to-r from-blue-50/60 via-indigo-50/40 to-slate-50/60 dark:from-blue-950/20 dark:via-indigo-950/15 dark:to-slate-900/30 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
          <div class="relative shrink-0">
            <img
              :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'Admin')}&background=2563eb&color=fff`"
              class="w-12 h-12 rounded-2xl object-cover ring-2 ring-blue-500/30 dark:ring-blue-400/40 shadow-sm"
              alt="Avatar"
            />
            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-slate-900"></span>
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-1.5">
              <span class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ user.name || 'Admin' }}</span>
              <OfficialVerifiedBadge :role="user.role || 'admin'" size="sm" />
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono truncate">{{ user.email }}</p>
          </div>
        </div>

        <button
          type="button"
          @click="emit('triggerAvatarUpload')"
          class="shrink-0 px-3 py-1.5 text-xs font-semibold rounded-xl bg-white dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-slate-200 dark:border-slate-700 shadow-2xs hover:border-blue-400 transition cursor-pointer flex items-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span>{{ isKhmer ? 'ប្ដូររូបថត' : 'Change Avatar' }}</span>
        </button>
      </div>

      <!-- Tab Switcher -->
      <div class="px-6 pt-3 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800">
        <button
          type="button"
          @click="activeTab = 'personal'"
          :class="[
            'pb-2.5 px-3 text-xs font-bold transition-all border-b-2 cursor-pointer',
            activeTab === 'personal'
              ? 'text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400'
              : 'text-slate-500 dark:text-slate-400 border-transparent hover:text-slate-700 dark:hover:text-slate-200'
          ]"
        >
          {{ isKhmer ? 'ព័ត៌មានផ្ទាល់ខ្លួន' : 'Personal Details' }}
        </button>
        <button
          type="button"
          @click="activeTab = 'security'"
          :class="[
            'pb-2.5 px-3 text-xs font-bold transition-all border-b-2 cursor-pointer',
            activeTab === 'security'
              ? 'text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400'
              : 'text-slate-500 dark:text-slate-400 border-transparent hover:text-slate-700 dark:hover:text-slate-200'
          ]"
        >
          {{ isKhmer ? 'សុវត្ថិភាព & ពាក្យសម្ងាត់' : 'Password & Security' }}
        </button>
      </div>

      <!-- Tab Content -->
      <div class="p-6">
        <!-- Personal Tab -->
        <form v-if="activeTab === 'personal'" @submit.prevent="handleProfileSubmit" class="space-y-4">
          <div v-if="profileSuccess" class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-medium flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span>{{ isKhmer ? 'កែប្រែព័ត៌មានគណនីបានជោគជ័យ!' : 'Profile updated successfully!' }}</span>
          </div>

          <div v-if="profileError" class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-medium flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ profileError }}</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                {{ isKhmer ? 'ឈ្មោះឡាតាំង' : 'Full Name (Latin)' }} <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="profileForm.name"
                type="text"
                required
                class="w-full h-9 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
                placeholder="kosal sensok"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                {{ isKhmer ? 'ឈ្មោះជាភាសាខ្មែរ' : 'Khmer Name' }}
              </label>
              <input
                v-model="profileForm.name_kh"
                type="text"
                class="w-full h-9 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
                placeholder="កុសល សែនសុខ"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                {{ isKhmer ? 'អាសយដ្ឋានអ៊ីមែល' : 'Email Address' }} <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="profileForm.email"
                type="email"
                required
                class="w-full h-9 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
                placeholder="kosalsensok065@gmail.com"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                {{ isKhmer ? 'លេខទូរស័ព្ទ' : 'Phone Number' }}
              </label>
              <input
                v-model="profileForm.phone"
                type="text"
                class="w-full h-9 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
                placeholder="+855 12 345 678"
              />
            </div>
          </div>

          <div class="pt-2 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="emit('close')"
              class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition cursor-pointer"
            >
              {{ isKhmer ? 'បោះបង់' : 'Cancel' }}
            </button>
            <button
              type="submit"
              :disabled="profileLoading"
              class="h-9 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm hover:shadow transition-all active:scale-[0.99] cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
            >
              <svg v-if="profileLoading" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              <span>{{ profileLoading ? (isKhmer ? 'កំពុងរក្សាទុក...' : 'Saving...') : (isKhmer ? 'រក្សាទុកការកែប្រែ' : 'Save Changes') }}</span>
            </button>
          </div>
        </form>

        <!-- Security Tab -->
        <form v-else @submit.prevent="handlePasswordSubmit" class="space-y-4">
          <div v-if="passwordSuccess" class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-medium flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span>{{ isKhmer ? 'ពាក្យសម្ងាត់ត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ!' : 'Password changed successfully!' }}</span>
          </div>

          <div v-if="passwordError" class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-medium flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ passwordError }}</span>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
              {{ isKhmer ? 'ពាក្យសម្ងាត់បច្ចុប្បន្ន' : 'Current Password' }} <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model="passwordForm.current_password"
                :type="showCurrentPassword ? 'text' : 'password'"
                required
                class="w-full h-9 pl-3 pr-9 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
                placeholder="••••••••"
              />
              <button
                type="button"
                @click="showCurrentPassword = !showCurrentPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
              >
                <svg v-if="!showCurrentPassword" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                {{ isKhmer ? 'ពាក្យសម្ងាត់ថ្មី' : 'New Password' }} <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="passwordForm.password"
                  :type="showNewPassword ? 'text' : 'password'"
                  required
                  minlength="6"
                  class="w-full h-9 pl-3 pr-9 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
                  placeholder="Min. 6 chars"
                />
                <button
                  type="button"
                  @click="showNewPassword = !showNewPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                >
                  <svg v-if="!showNewPassword" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                </button>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                {{ isKhmer ? 'បញ្ជាក់ពាក្យសម្ងាត់' : 'Confirm Password' }} <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="passwordForm.password_confirmation"
                :type="showNewPassword ? 'text' : 'password'"
                required
                minlength="6"
                class="w-full h-9 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
                placeholder="Re-type password"
              />
            </div>
          </div>

          <div class="pt-2 flex items-center justify-end gap-2">
            <button
              type="button"
              @click="emit('close')"
              class="h-9 px-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold transition cursor-pointer"
            >
              {{ isKhmer ? 'បោះបង់' : 'Cancel' }}
            </button>
            <button
              type="submit"
              :disabled="passwordLoading"
              class="h-9 px-5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm hover:shadow transition-all active:scale-[0.99] cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
            >
              <svg v-if="passwordLoading" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              <span>{{ passwordLoading ? (isKhmer ? 'កំពុងផ្លាស់ប្តូរ...' : 'Updating...') : (isKhmer ? 'ផ្លាស់ប្តូរពាក្យសម្ងាត់' : 'Update Password') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
