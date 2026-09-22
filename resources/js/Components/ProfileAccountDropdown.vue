<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import OfficialVerifiedBadge from '@/Components/OfficialVerifiedBadge.vue'
import { useMultiAccount, SavedAccount } from '@/composables/useMultiAccount'
import { i18n } from '@/Services/i18n'

interface Props {
  user: any
  isOpen: boolean
  role?: string
}

const props = withDefaults(defineProps<Props>(), {
  role: 'admin',
})

const emit = defineEmits<{
  (e: 'update:isOpen', val: boolean): void
  (e: 'close'): void
  (e: 'logout'): void
  (e: 'triggerAvatarUpload'): void
}>()

const isKhmer = computed(() => (i18n.currentLocale?.value || i18n.locale?.value || 'km') === 'km')

const {
  savedAccounts,
  isAdding,
  isSwitching,
  activeSwitchingId,
  addError,
  syncCurrentUser,
  addAccount,
  switchAccount,
  removeAccount,
} = useMultiAccount()

// Form state for inline "Add Account"
const showAddForm = ref(false)
const formIdentifier = ref('')
const formPassword = ref('')
const showPassword = ref(false)

// Synchronize current user into multi-account manager
onMounted(() => {
  if (props.user) {
    syncCurrentUser(props.user)
  }
})

// Other saved accounts excluding the current user
const otherAccounts = computed(() => {
  if (!props.user?.id) return []
  return savedAccounts.value.filter(a => a.id !== props.user.id)
})

const roleBadgeLabel = computed(() => {
  const r = (props.user?.role || props.role || 'student').toLowerCase()
  if (isKhmer.value) {
    if (r === 'admin') return 'អ្នកគ្រប់គ្រងជាន់ខ្ពស់'
    if (r === 'teacher') return 'សាស្ត្រាចារ្យ'
    return 'និស្សិត'
  }
  if (r === 'admin') return 'Super Admin'
  if (r === 'teacher') return 'Instructor'
  return 'Student'
})

const handleAddAccountSubmit = async () => {
  if (!formIdentifier.value.trim() || !formPassword.value) return
  const success = await addAccount(formIdentifier.value.trim(), formPassword.value)
  if (success) {
    formIdentifier.value = ''
    formPassword.value = ''
    showAddForm.value = false
    emit('close')
  }
}

const handleSwitchAccount = async (acc: SavedAccount) => {
  await switchAccount(acc)
}

const handleRemoveAccount = (e: Event, id: number) => {
  e.stopPropagation()
  removeAccount(id)
}
</script>

<template>
  <div
    v-if="isOpen"
    class="absolute right-0 mt-2 w-72 sm:w-80 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-2xl overflow-hidden z-50 animate-in fade-in zoom-in-95 duration-150 select-none text-slate-800 dark:text-slate-100"
    @click.stop
  >
    <!-- Header: Manage Profile -->
    <div class="px-4 py-3 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-purple-50/70 dark:from-slate-800/80 dark:via-slate-800/60 dark:to-slate-900/90 border-b border-slate-200/80 dark:border-slate-800">
      <div class="flex items-center gap-3">
        <div class="relative shrink-0">
          <img
            :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=2563eb&color=fff`"
            :alt="user.name"
            class="w-11 h-11 rounded-full object-cover border-2 border-white dark:border-slate-700 shadow-md ring-2 ring-blue-500/20"
          />
          <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-slate-900"></span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-1.5 min-w-0">
            <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">{{ user.name }}</h4>
            <OfficialVerifiedBadge :role="user.role || role" size="xs" />
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono truncate mt-0.5">{{ user.email }}</p>
          <span
            :class="[
              'inline-flex items-center gap-1 mt-1 px-2 py-0.5 text-[9.5px] font-bold uppercase tracking-wider rounded-md border',
              user.role === 'admin'
                ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30'
                : user.role === 'teacher'
                  ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
                  : 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30'
            ]"
          >
            <i :class="['text-[9px]', user.role === 'admin' ? 'pi pi-shield' : user.role === 'teacher' ? 'pi pi-briefcase' : 'pi pi-graduation-cap']"></i>
            <span>{{ roleBadgeLabel }}</span>
          </span>
        </div>
      </div>
    </div>

    <!-- Section: Switch Account (Modeled after Image 2 reference) -->
    <div class="px-3.5 py-3 border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-200">
          <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          <span>{{ isKhmer ? 'ប្តូរគណនី (Switch Account)' : 'Switch Account' }}</span>
        </div>
        
        <button
          type="button"
          @click="showAddForm = !showAddForm"
          class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:underline flex items-center gap-1 cursor-pointer"
        >
          <span>{{ showAddForm ? (isKhmer ? 'បិទ' : 'Cancel') : (isKhmer ? '+ បន្ថែមគណនី' : '+ Add Account') }}</span>
        </button>
      </div>

      <!-- Current Active Account Pill (Image 2 style) -->
      <div class="p-2 rounded-xl bg-blue-50/90 dark:bg-blue-950/40 border border-blue-200/90 dark:border-blue-800/60 flex items-center justify-between gap-2 shadow-2xs">
        <div class="flex items-center gap-2.5 min-w-0">
          <img
            :src="user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=2563eb&color=fff`"
            class="w-8 h-8 rounded-full object-cover border border-blue-300 dark:border-blue-700 shrink-0"
            alt="Current"
          />
          <div class="min-w-0">
            <div class="flex items-center gap-1">
              <span class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ user.name }}</span>
              <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ user.email }}</p>
          </div>
        </div>
        <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-blue-600 text-white shrink-0 tracking-wider">
          {{ isKhmer ? 'កំពុងប្រើ' : 'Active' }}
        </span>
      </div>

      <!-- Other Saved Accounts List (1-Click Switch) -->
      <div v-if="otherAccounts.length > 0" class="mt-2 space-y-1.5">
        <div
          v-for="acc in otherAccounts"
          :key="acc.id"
          @click="handleSwitchAccount(acc)"
          class="p-2 rounded-xl bg-white dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-700/60 border border-slate-200/80 dark:border-slate-700/60 flex items-center justify-between gap-2 cursor-pointer transition-all active:scale-[0.99] group shadow-2xs"
          :title="isKhmer ? `ចុចដើម្បីប្តូរទៅកាន់ ${acc.name}` : `Click to switch to ${acc.name}`"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <img
              :src="acc.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(acc.name)}&background=6366f1&color=fff`"
              class="w-7 h-7 rounded-full object-cover border border-slate-200 dark:border-slate-600 shrink-0"
              alt="Avatar"
            />
            <div class="min-w-0">
              <p class="text-xs font-bold text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 truncate">{{ acc.name }}</p>
              <p class="text-[10px] text-slate-400 truncate">{{ acc.email }}</p>
            </div>
          </div>

          <div class="flex items-center gap-1.5 shrink-0">
            <span v-if="activeSwitchingId === acc.id" class="flex items-center gap-1 text-[10px] font-bold text-blue-600 dark:text-blue-400">
              <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            <span v-else class="text-[10px] font-semibold text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 flex items-center gap-0.5">
              {{ isKhmer ? 'ប្តូរ' : 'Switch' }} &rarr;
            </span>

            <!-- Remove from saved list button -->
            <button
              type="button"
              @click="handleRemoveAccount($event, acc.id)"
              class="p-1 text-slate-300 hover:text-rose-500 rounded-md hover:bg-rose-50 dark:hover:bg-rose-950/30 transition cursor-pointer"
              :title="isKhmer ? 'លុបចេញពីបញ្ជីចងចាំ' : 'Remove from saved accounts'"
            >
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Add Account Inline Form (Image 2 style) -->
      <div v-if="showAddForm" class="mt-2.5 p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm animate-in fade-in slide-in-from-top-2 duration-150">
        <form @submit.prevent="handleAddAccountSubmit" class="space-y-2">
          <div>
            <input
              v-model="formIdentifier"
              type="text"
              required
              :disabled="isAdding"
              :placeholder="isKhmer ? 'អ៊ីមែល / ID / ទូរស័ព្ទ' : 'Email / Username / Phone'"
              class="w-full h-8 px-2.5 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
            />
          </div>

          <div class="relative">
            <input
              v-model="formPassword"
              :type="showPassword ? 'text' : 'password'"
              required
              :disabled="isAdding"
              :placeholder="isKhmer ? 'ពាក្យសម្ងាត់ (Password)' : 'Password'"
              class="w-full h-8 pl-2.5 pr-8 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
            >
              <i :class="['text-xs', showPassword ? 'pi pi-eye-slash' : 'pi pi-eye']"></i>
            </button>
          </div>

          <p v-if="addError" class="text-[10.5px] text-rose-500 font-medium leading-tight">
            {{ addError }}
          </p>

          <button
            type="submit"
            :disabled="isAdding"
            class="w-full h-8 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm hover:shadow active:scale-[0.99] transition cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
          >
            <svg v-if="isAdding" class="animate-spin w-3 h-3 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ isAdding ? (isKhmer ? 'កំពុងភ្ជាប់...' : 'Adding...') : (isKhmer ? '+ បន្ថែមគណនី' : 'Add Account') }}</span>
          </button>
        </form>
      </div>
    </div>

    <!-- Quick Navigation Links (Image 1 style) -->
    <div class="py-1 border-b border-slate-200/80 dark:border-slate-800">
      <slot name="links">
        <Link
          href="/admin/settings"
          @click="emit('close')"
          class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors"
        >
          <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span>{{ isKhmer ? 'ការកំណត់ប្រព័ន្ធ' : 'System Settings' }}</span>
        </Link>

        <Link
          v-if="user.role === 'admin'"
          href="/admin/auth/roles"
          @click="emit('close')"
          class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors"
        >
          <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
          <span>{{ isKhmer ? 'សិទ្ធិ និង តួនាទី' : 'Roles & Permissions' }}</span>
        </Link>

        <Link
          v-if="user.role === 'admin'"
          href="/admin/auth-logs"
          @click="emit('close')"
          class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors"
        >
          <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span>{{ isKhmer ? 'កំណត់ត្រាសកម្មភាព' : 'System Logs' }}</span>
        </Link>

        <button
          type="button"
          @click="emit('triggerAvatarUpload'); emit('close')"
          class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors text-left cursor-pointer"
        >
          <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <span>{{ isKhmer ? 'ប្ដូររូបថត' : 'Change Avatar' }}</span>
        </button>
      </slot>
    </div>

    <!-- Sign Out Button -->
    <div class="p-1">
      <button
        type="button"
        @click="emit('logout')"
        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-xl transition-colors font-bold text-left cursor-pointer"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        <span>{{ isKhmer ? 'ចាកចេញពីប្រព័ន្ធ' : 'Log Out' }}</span>
      </button>
    </div>
  </div>
</template>
