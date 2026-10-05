<script setup lang="ts">
import { computed } from 'vue'
import { usePage, Link, Head } from '@inertiajs/vue3'
import { adminMenu, teacherMenu, studentMenu } from '@/composables/useSidebar'
import GlobalToast from '@/Components/GlobalToast.vue'
import GlobalLoadingOverlay from '@/Components/GlobalLoadingOverlay.vue'

const props = defineProps<{ title?: string }>()

const page = usePage()
const user = computed<any>(() => (page.props as any).auth?.user || {})

const menu = computed(() => {
  if (user.value.role === 'admin') return adminMenu
  if (user.value.role === 'teacher') return teacherMenu
  return studentMenu
})
</script>

<template>
  <Head :title="props.title || 'E-LMS'" />
  <GlobalToast />
  <GlobalLoadingOverlay />
  <div class="min-h-screen flex bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col flex-shrink-0">
      <div class="p-5 text-xl font-bold border-b border-slate-800 flex items-center gap-2">
        <span class="text-blue-500">E.LMS</span> System
      </div>
      <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <Link
          v-for="item in menu"
          :key="item.href"
          :href="item.href"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition hover:bg-slate-800"
          :class="{ 'bg-blue-600 text-white': $page.url.startsWith(item.href) }"
        >
          <span>{{ item.label }}</span>
        </Link>
      </nav>
      <div class="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
        <div>
          <p class="font-semibold text-white">{{ user.name || 'User' }}</p>
          <p class="capitalize">{{ user.role || 'Guest' }}</p>
        </div>
        <Link href="/logout" method="post" as="button" class="text-red-400 hover:underline">
          Logout
        </Link>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold capitalize">{{ user.role || 'E.LMS' }} Portal</h1>
        <div class="flex items-center gap-4 text-sm">
          <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 capitalize">
            {{ user.role || 'Guest' }}
          </span>
          <span class="font-medium">{{ user.name }}</span>
        </div>
      </header>
      <main class="flex-1 p-6 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
