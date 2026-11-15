<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import OfficialVerifiedBadge from '@/Components/OfficialVerifiedBadge.vue'
import { i18n } from '@/Services/i18n'

interface Props {
  user: any
  isOpen: boolean
  role?: string
}

const props = withDefaults(defineProps<Props>(), {
  role: 'student',
})

const emit = defineEmits<{
  (e: 'update:isOpen', val: boolean): void
  (e: 'close'): void
  (e: 'logout'): void
  (e: 'triggerAvatarUpload'): void
  (e: 'manageProfile'): void
}>()

const isKhmer = computed(() => (i18n.currentLocale?.value || i18n.locale?.value || 'km') === 'km')

const currentUser = computed(() => {
  return props.user || {}
})

const effectiveRole = computed(() => {
  return (currentUser.value?.role || props.role || 'student').toLowerCase()
})

const userDisplayName = computed(() => {
  return currentUser.value?.name || (isKhmer.value ? 'គណនីនិស្សិត' : 'Student User')
})

const userDisplayEmail = computed(() => {
  return currentUser.value?.email || ''
})

const getStableAvatarBg = (name: string, id?: number | string) => {
  const colors = [
    '#6366f1', // indigo
    '#3b82f6', // blue
    '#8b5cf6', // purple
    '#ec4899', // pink
    '#06b6d4', // cyan
    '#10b981', // emerald
    '#f59e0b', // amber
    '#0284c7', // sky
  ]
  const seed = String(name || '') + String(id || '')
  let hash = 0
  for (let i = 0; i < seed.length; i++) {
    hash = seed.charCodeAt(i) + ((hash << 5) - hash)
  }
  const index = Math.abs(hash) % colors.length
  return colors[index]
}

const userInitial = computed(() => {
  const name = (userDisplayName.value || '').trim()
  if (!name) return 'S'
  const parts = name.split(/\s+/)
  if (parts.length > 1) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
})

const avatarError = ref(false)

const userDisplayAvatar = computed(() => {
  const av = currentUser.value?.avatar_url || currentUser.value?.avatar
  if (av && typeof av === 'string' && av.trim().length > 0 && !av.includes('ui-avatars.com')) {
    return av.startsWith('http') ? av : `/storage/${av.replace(/^\/+/, '')}`
  }
  return null
})

const roleBadgeLabel = computed(() => {
  const r = effectiveRole.value
  if (isKhmer.value) {
    if (r === 'admin') return 'អ្នកគ្រប់គ្រងជាន់ខ្ពស់'
    if (r === 'teacher') return 'សាស្ត្រាចារ្យ'
    return 'និស្សិត'
  }
  if (r === 'admin') return 'Super Admin'
  if (r === 'teacher') return 'Instructor'
  return 'Student'
})

const manageProfileUrl = computed(() => {
  const r = effectiveRole.value
  if (r === 'student') return '/student/profile?tab=personal'
  if (r === 'teacher') return '/teacher/profile?tab=info'
  return '/admin/settings'
})

const handleManageProfile = () => {
  if (effectiveRole.value === 'admin') {
    emit('manageProfile')
  }
  emit('close')
}
</script>

<template>
  <div
    v-if="isOpen"
    class="absolute right-0 top-full mt-2 w-72 sm:w-80 rounded-2xl bg-white dark:bg-[#0f172a] border border-slate-200/90 dark:border-slate-800 shadow-2xl overflow-hidden z-50 animate-in fade-in zoom-in-95 duration-150 select-none text-slate-800 dark:text-slate-100 font-sans"
    @click.stop
  >
    <!-- ── 1. OFFICIAL USER IDENTITY PROFILE HEADER ── -->
    <div class="p-3.5 bg-gradient-to-br from-slate-50 via-slate-100/60 to-blue-50/40 dark:from-[#0f172a] dark:via-[#1e293b]/70 dark:to-[#0f172a] border-b border-slate-100 dark:border-slate-800/80">
      <div class="flex items-center gap-3">
        <!-- Avatar from DB or Hash-Based Initial Circle (No hardcoded fake image) -->
        <div class="relative shrink-0">
          <img
            v-if="userDisplayAvatar && !avatarError"
            :src="userDisplayAvatar"
            @error="avatarError = true"
            class="w-11 h-11 rounded-full object-cover border-2 border-white dark:border-slate-800 shadow-sm"
            alt="User Avatar"
          />
          <div
            v-else
            class="w-11 h-11 rounded-full flex items-center justify-center font-bold text-white text-sm shadow-sm ring-2 ring-white dark:ring-slate-800 select-none"
            :style="{ backgroundColor: getStableAvatarBg(userDisplayName, currentUser.id) }"
          >
            {{ userInitial }}
          </div>

          <!-- Active online dot: Only show if real last_seen_at exists in DB -->
          <span
            v-if="currentUser?.last_seen_at"
            class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full ring-2 ring-white dark:ring-[#0f172a]"
            :title="isKhmer ? 'គណនីផ្លូវការសកម្ម (Online)' : 'Official Active Account'"
          ></span>
        </div>

        <!-- Name, Role & Email (Normal font, truncated with tooltip) -->
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-1.5 flex-wrap">
            <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[170px]" :title="userDisplayName">
              {{ userDisplayName }}
            </h4>
            <OfficialVerifiedBadge :role="effectiveRole" size="xs" />
          </div>

          <p class="text-[11px] text-slate-500 dark:text-slate-400 font-sans truncate mt-0.5 max-w-[190px]" :title="userDisplayEmail">
            {{ userDisplayEmail }}
          </p>

          <div class="mt-1 flex items-center gap-1.5">
            <span
              :class="[
                'inline-flex items-center gap-1 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-md border',
                effectiveRole === 'admin'
                  ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
                  : effectiveRole === 'teacher'
                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                    : 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20'
              ]"
            >
              <span
                class="w-1.5 h-1.5 rounded-full"
                :class="effectiveRole === 'admin' ? 'bg-amber-500' : effectiveRole === 'teacher' ? 'bg-emerald-500' : 'bg-sky-500'"
              ></span>
              {{ roleBadgeLabel }}
            </span>
          </div>
        </div>
      </div>

      <!-- Quick Action: Manage Profile Banner for Admin/Teacher (Omitted for Student to avoid duplicate with links slot) -->
      <div v-if="effectiveRole === 'admin'" class="mt-3 pt-2 border-t border-slate-200/60 dark:border-slate-800/80">
        <button
          type="button"
          @click="handleManageProfile"
          class="w-full flex items-center justify-between px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800/90 hover:bg-blue-50 dark:hover:bg-slate-800 border border-slate-200/70 dark:border-slate-700/70 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 shadow-2xs transition-all cursor-pointer group"
        >
          <span class="flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-blue-500 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span>{{ isKhmer ? 'គ្រប់គ្រងគណនី (Manage Profile)' : 'Manage Profile' }}</span>
          </span>
          <svg class="w-3.5 h-3.5 text-slate-400 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>

    <!-- ── 2. QUICK NAVIGATION & ROLE MODULE LINKS ── -->
    <div class="py-1">
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
      </slot>

      <!-- Change Avatar Option -->
      <button
        type="button"
        @click="emit('triggerAvatarUpload'); emit('close')"
        class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-colors text-left cursor-pointer"
      >
        <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span>{{ isKhmer ? 'ប្ដូររូបថតគណនី' : 'Change Avatar' }}</span>
      </button>
    </div>

    <!-- ── 3. LOG OUT BUTTON ── -->
    <div class="p-1.5 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/30">
      <button
        type="button"
        @click="emit('logout')"
        class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-transparent hover:border-rose-200 dark:hover:border-rose-900/50 font-bold transition-all cursor-pointer active:scale-[0.99]"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        <span>{{ isKhmer ? 'ចាកចេញពីប្រព័ន្ធ (Log Out)' : 'Log Out' }}</span>
      </button>
    </div>
  </div>
</template>
