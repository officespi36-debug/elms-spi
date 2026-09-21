<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

const props = withDefaults(defineProps<{
  activeTab: 'all' | 'administrators' | 'teachers' | 'students' | 'suspended' | 'import-export'
  summaryStats?: {
    total_users?: number
    total_admins?: number
    total_teachers?: number
    total_students?: number
    total_suspended?: number
    active_admins?: number
    active_teachers?: number
    active_students?: number
  }
}>(), {
  summaryStats: () => ({
    total_users: 2618,
    total_admins: 3,
    total_teachers: 145,
    total_students: 2458,
    total_suspended: 12,
    active_admins: 3,
    active_teachers: 143,
    active_students: 2390,
  })
})
</script>

<template>
  <div class="space-y-4 font-sans">
    <!-- Difference Alert Banner (Dual Theme) -->
    <div class="bg-gradient-to-r from-indigo-50/90 via-white to-blue-50/70 dark:from-slate-900 dark:via-indigo-950/40 dark:to-slate-900 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-500/20 shadow-sm dark:shadow-xl backdrop-blur-xl">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-100/80 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-inner">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-lg font-black text-slate-900 dark:text-white tracking-wide uppercase">USER MANAGEMENT MODULE</h1>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-100/80 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-500/30">
                Admin Control Panel
              </span>
            </div>
            <p class="text-xs text-slate-600 dark:text-slate-400">
              គ្រប់គ្រងព័ត៌មានគណនីមនុស្ស (Profiles, Roles, Departments, Majors, Course Enrollments & Payments)
            </p>
          </div>
        </div>

        <!-- System Active Status Tag -->
        <div class="flex items-center gap-2 text-xs font-mono">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 rounded-lg font-medium text-[11px] whitespace-nowrap shadow-xs">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
            <span>System Live & Active</span>
          </span>
        </div>
      </div>
    </div>

    <!-- OVERVIEW CARDS (4 Main Categories - Dual Theme) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
      <!-- Card 1: Admin -->
      <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 backdrop-blur-xl hover:border-purple-400 dark:hover:border-purple-500/30 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
        <div>
          <span class="text-xs font-semibold text-purple-700 dark:text-purple-300 uppercase tracking-wider">ADMINISTRATORS</span>
          <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">
            {{ props.summaryStats?.total_admins || 3 }}
          </div>
          <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>{{ props.summaryStats?.active_admins || 3 }} Active Super Admins</span>
          </p>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-500/10 border border-purple-200 dark:border-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400 shadow-sm dark:shadow-lg dark:shadow-purple-500/10">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
      </div>

      <!-- Card 2: Teacher -->
      <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 backdrop-blur-xl hover:border-cyan-400 dark:hover:border-cyan-500/30 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
        <div>
          <span class="text-xs font-semibold text-cyan-700 dark:text-cyan-300 uppercase tracking-wider">TEACHERS</span>
          <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">
            {{ props.summaryStats?.total_teachers || 145 }}
          </div>
          <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-cyan-500 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 13v-1m4 1v-3m4 3V8M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            <span>{{ props.summaryStats?.active_teachers || 143 }} Active Instructors</span>
          </p>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/20 flex items-center justify-center text-cyan-600 dark:text-cyan-400 shadow-sm dark:shadow-lg dark:shadow-cyan-500/10">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
        </div>
      </div>

      <!-- Card 3: Student -->
      <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 backdrop-blur-xl hover:border-emerald-400 dark:hover:border-emerald-500/30 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
        <div>
          <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">STUDENTS</span>
          <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">
            {{ (props.summaryStats?.total_students || 2458).toLocaleString() }}
          </div>
          <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            <span>{{ (props.summaryStats?.active_students || 2390).toLocaleString() }} Active Learners</span>
          </p>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-sm dark:shadow-lg dark:shadow-emerald-500/10">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
        </div>
      </div>

      <!-- Card 4: Suspended -->
      <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 backdrop-blur-xl hover:border-red-400 dark:hover:border-red-500/30 transition-all flex items-center justify-between shadow-sm dark:shadow-none">
        <div>
          <span class="text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">SUSPENDED</span>
          <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">
            {{ props.summaryStats?.total_suspended || 12 }}
          </div>
          <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            <span>Frozen / Suspended Accounts</span>
          </p>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 flex items-center justify-center text-red-600 dark:text-red-400 shadow-sm dark:shadow-lg dark:shadow-red-500/10">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        </div>
      </div>
    </div>

    <!-- MAIN NAVIGATION TABS (5 Entity Pages Only) -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 overflow-x-auto pt-2">
      <Link
        href="/admin/user-management/all"
        :class="[
          activeTab === 'all'
            ? 'border-indigo-600 dark:border-indigo-500 text-indigo-700 dark:text-white font-extrabold bg-indigo-50/90 dark:bg-indigo-500/20 border-b-2'
            : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/40',
          'px-5 py-3 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2'
        ]"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        <span>All Users</span>
      </Link>

      <Link
        href="/admin/user-management/administrators"
        :class="[
          activeTab === 'administrators'
            ? 'border-indigo-600 dark:border-indigo-500 text-indigo-700 dark:text-white font-extrabold bg-indigo-50/90 dark:bg-indigo-500/20 border-b-2'
            : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/40',
          'px-5 py-3 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2'
        ]"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <span>Administrators</span>
      </Link>

      <Link
        href="/admin/user-management/teachers"
        :class="[
          activeTab === 'teachers'
            ? 'border-indigo-600 dark:border-indigo-500 text-indigo-700 dark:text-white font-extrabold bg-indigo-50/90 dark:bg-indigo-500/20 border-b-2'
            : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/40',
          'px-5 py-3 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2'
        ]"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
        <span>Teachers</span>
      </Link>

      <Link
        href="/admin/user-management/students"
        :class="[
          activeTab === 'students'
            ? 'border-indigo-600 dark:border-indigo-500 text-indigo-700 dark:text-white font-extrabold bg-indigo-50/90 dark:bg-indigo-500/20 border-b-2'
            : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/40',
          'px-5 py-3 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2'
        ]"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
        <span>Students</span>
      </Link>

      <Link
        href="/admin/user-management/suspended"
        :class="[
          activeTab === 'suspended'
            ? 'border-indigo-600 dark:border-indigo-500 text-indigo-700 dark:text-white font-extrabold bg-indigo-50/90 dark:bg-indigo-500/20 border-b-2'
            : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/40',
          'px-5 py-3 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2'
        ]"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        <span>Suspended Users</span>
      </Link>
    </div>
  </div>
</template>
