<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = withDefaults(defineProps<{
  activeTab: 'departments' | 'majors' | 'subjects' | 'academic-years' | 'faculties' | 'semesters'
  summaryStats?: {
    total_faculties?: number
    total_departments?: number
    total_majors?: number
    total_subjects?: number
    total_academic_years?: number
    total_semesters?: number
    total_students?: number
    total_teachers?: number
    total_courses?: number
  }
}>(), {
  summaryStats: () => ({
    total_faculties: 5,
    total_departments: 12,
    total_majors: 5,
    total_subjects: 24,
    total_academic_years: 4,
    total_semesters: 2,
    total_students: 2458,
    total_teachers: 145,
    total_courses: 328,
  })
})

const headerTheme = computed(() => {
  switch (props.activeTab) {
    case 'departments':
      return {
        bg: 'from-slate-900 via-indigo-950/30 to-slate-900',
        border: 'border-indigo-500/20',
        iconBg: 'bg-indigo-500/10 border-indigo-500/20 text-indigo-400',
        badge: 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20',
        label: 'Department Sub-units'
      }
    case 'majors':
      return {
        bg: 'from-slate-900 via-cyan-950/30 to-slate-900',
        border: 'border-cyan-500/20',
        iconBg: 'bg-cyan-500/10 border-cyan-500/20 text-cyan-400',
        badge: 'bg-cyan-500/10 text-cyan-300 border-cyan-500/20',
        label: '5 Degree Programs'
      }
    case 'subjects':
      return {
        bg: 'from-slate-900 via-sky-950/30 to-slate-900',
        border: 'border-sky-500/20',
        iconBg: 'bg-sky-500/10 border-sky-500/20 text-sky-400',
        badge: 'bg-sky-500/10 text-sky-300 border-sky-500/20',
        label: 'Core Subjects'
      }
    case 'academic-years':
      return {
        bg: 'from-slate-900 via-emerald-950/30 to-slate-900',
        border: 'border-emerald-500/20',
        iconBg: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
        badge: 'bg-emerald-500/10 text-emerald-300 border-emerald-500/20',
        label: 'Academic Timeline'
      }
    case 'faculties':
      return {
        bg: 'from-slate-900 via-purple-950/30 to-slate-900',
        border: 'border-purple-500/20',
        iconBg: 'bg-purple-500/10 border-purple-500/20 text-purple-400',
        badge: 'bg-purple-500/10 text-purple-300 border-purple-500/20',
        label: 'Faculty Structure'
      }
    case 'semesters':
      return {
        bg: 'from-slate-900 via-amber-950/30 to-slate-900',
        border: 'border-amber-500/20',
        iconBg: 'bg-amber-500/10 border-amber-500/20 text-amber-400',
        badge: 'bg-amber-500/10 text-amber-300 border-amber-500/20',
        label: 'Term Schedules'
      }
    default:
      return {
        bg: 'from-slate-900 via-sky-950/30 to-slate-900',
        border: 'border-sky-500/20',
        iconBg: 'bg-sky-500/10 border-sky-500/20 text-sky-400',
        badge: 'bg-sky-500/10 text-sky-300 border-sky-500/20',
        label: 'Educational Backbone'
      }
  }
})
</script>

<template>
  <div class="space-y-3 font-sans">
    <!-- Header Summary Banner (Compact & Sleek Soft Themed) -->
    <div :class="['bg-gradient-to-r p-3.5 rounded-2xl border backdrop-blur-xl flex items-center justify-between gap-4 transition-all duration-500', headerTheme.bg, headerTheme.border]">
      <div class="flex items-center gap-3">
        <div :class="['w-9 h-9 rounded-xl border flex items-center justify-center text-lg font-bold transition-all', headerTheme.iconBg]">
          🏫
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-base font-black text-white tracking-wide uppercase">ACADEMIC STRUCTURE MODULE</h1>
            <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-bold border transition-all', headerTheme.badge]">
              {{ headerTheme.label }}
            </span>
          </div>
          <p class="text-xs text-slate-400">
            រចនាសម្ព័ន្ធអប់រំ (Department → Major → Subject → Academic Year)
          </p>
        </div>
      </div>
    </div>

    <!-- Sub-navigation Tabs (4 Primary Academic Structure Nodes) -->
    <div class="flex border-b border-slate-800/80 overflow-x-auto gap-2 pt-2">
      <!-- Departments Tab -->
      <Link
        href="/admin/academic-structure/departments"
        :class="[
          activeTab === 'departments'
            ? 'border-indigo-400 text-indigo-300 font-bold bg-indigo-500/10 border-b-2 shadow-xs'
            : 'border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-5 py-3 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group'
        ]"
      >
        <svg class="w-4 h-4 text-indigo-400 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 10V11m0 0h4m-4 0H7" />
        </svg>
        <span>Departments</span>
      </Link>

      <!-- Majors Tab -->
      <Link
        href="/admin/academic-structure/majors"
        :class="[
          activeTab === 'majors'
            ? 'border-cyan-400 text-cyan-300 font-bold bg-cyan-500/10 border-b-2 shadow-xs'
            : 'border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-5 py-3 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group'
        ]"
      >
        <svg class="w-4 h-4 text-cyan-400 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
        </svg>
        <span>Majors</span>
      </Link>

      <!-- Subjects Tab -->
      <Link
        href="/admin/academic-structure/subjects"
        :class="[
          activeTab === 'subjects'
            ? 'border-sky-400 text-sky-300 font-bold bg-sky-500/10 border-b-2 shadow-xs'
            : 'border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-5 py-3 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group'
        ]"
      >
        <svg class="w-4 h-4 text-sky-400 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <span>Subjects</span>
      </Link>

      <!-- Academic Years Tab -->
      <Link
        href="/admin/academic-structure/academic-years"
        :class="[
          activeTab === 'academic-years'
            ? 'border-emerald-400 text-emerald-300 font-bold bg-emerald-500/10 border-b-2 shadow-xs'
            : 'border-transparent text-slate-400 hover:text-slate-200 hover:bg-slate-800/40',
          'px-5 py-3 text-xs rounded-t-xl transition-all whitespace-nowrap flex items-center gap-2 group'
        ]"
      >
        <svg class="w-4 h-4 text-emerald-400 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span>Academic Years</span>
      </Link>
    </div>
  </div>
</template>
