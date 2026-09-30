<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = withDefaults(defineProps<{
  activeTab: 'courses' | 'approval' | 'enrollment' | 'all' | 'subjects' | 'teacher-assignments' | 'teacher-led' | 'self-study' | 'free' | 'paid'
  summaryStats?: {
    total_courses?: number
    teacher_led_count?: number
    self_study_count?: number
    paid_count?: number
    free_count?: number
    published_count?: number
    draft_count?: number
    total_subjects?: number
  }
}>(), {
  summaryStats: () => ({
    total_courses: 328,
    teacher_led_count: 185,
    self_study_count: 143,
    paid_count: 220,
    free_count: 108,
    published_count: 310,
    draft_count: 18,
    total_subjects: 24,
  })
})

const isApprovalTab = computed(() => {
  if (typeof window !== 'undefined') {
    return window.location.search.includes('status=draft') || props.activeTab === 'approval'
  }
  return props.activeTab === 'approval'
})
</script>

<template>
  <div class="bg-slate-900/60 p-3.5 rounded-2xl border border-slate-800/80 backdrop-blur-md space-y-2.5 shadow-sm">
    <!-- TOP ROW: TITLE, HIERARCHY FLOW & QUICK STATS -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
      <!-- Title & Module Icon -->
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400 shadow-sm">
          <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xs font-bold text-slate-100 tracking-wide uppercase">COURSE MANAGEMENT</h1>
            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-sky-500/10 text-sky-300 border border-sky-500/20">
              Admin Core
            </span>
          </div>
          <!-- Canonical Hierarchy Flow Indicator -->
          <div class="flex items-center gap-1.5 text-[11px] text-slate-400 mt-0.5 font-medium">
            <span class="text-cyan-400 font-semibold">Major</span>
            <span class="text-slate-600">→</span>
            <span class="text-indigo-400 font-semibold">Subject</span>
            <span class="text-slate-600">→</span>
            <span class="text-sky-400 font-semibold">Course</span>
            <span class="text-slate-600">→</span>
            <span class="text-emerald-400 font-semibold">Lesson</span>
            <span class="text-slate-600">→</span>
            <span class="text-amber-400 font-semibold">Materials</span>
          </div>
        </div>
      </div>

      <!-- Quick Summary Badges (Compact, High Contrast) -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5 lg:pb-0 font-sans text-xs whitespace-nowrap scrollbar-none">
        <div class="px-2.5 py-1 rounded-lg bg-slate-800/60 border border-slate-700/50 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <span class="text-slate-300 font-medium">Total:</span>
          <span class="font-bold text-white">{{ summaryStats?.total_courses ?? 328 }}</span>
        </div>

        <div class="px-2.5 py-1 rounded-lg bg-amber-500/15 border border-amber-500/30 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="text-amber-300 font-semibold">Pending Approval:</span>
          <span class="font-bold text-amber-200">{{ summaryStats?.draft_count ?? 18 }}</span>
        </div>

        <div class="px-2.5 py-1 rounded-lg bg-indigo-500/15 border border-indigo-500/30 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
          </svg>
          <span class="text-indigo-300 font-semibold">Teacher-Led:</span>
          <span class="font-bold text-indigo-200">{{ summaryStats?.teacher_led_count ?? 185 }}</span>
        </div>

        <div class="px-2.5 py-1 rounded-lg bg-emerald-500/15 border border-emerald-500/30 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span class="text-emerald-300 font-semibold">Paid:</span>
          <span class="font-bold text-emerald-200">{{ summaryStats?.paid_count ?? 220 }}</span>
        </div>
      </div>
    </div>

    <!-- BOTTOM ROW: 3 CANONICAL SUBMODULE TABS (Courses, Course Approval, Enrollment) -->
    <div class="flex items-center justify-between gap-3 pt-0.5 border-t border-slate-800/60">
      <div class="inline-flex items-center gap-1 p-1 bg-slate-950/70 border border-slate-800/80 rounded-xl font-sans text-xs whitespace-nowrap">
        <!-- 1. Courses -->
        <Link
          href="/admin/course-module/all"
          class="py-1.5 px-3.5 rounded-lg font-bold transition-all flex items-center gap-2"
          :class="(!isApprovalTab && (activeTab === 'all' || activeTab === 'courses' || activeTab === 'teacher-led' || activeTab === 'self-study' || activeTab === 'free' || activeTab === 'paid'))
            ? 'bg-slate-800 text-sky-300 border border-slate-700/60 shadow-sm'
            : 'text-slate-300 hover:text-white hover:bg-slate-900/60'"
        >
          <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <span>Courses</span>
        </Link>

        <!-- 2. Course Approval -->
        <Link
          href="/admin/course-module/all?status=draft"
          class="py-1.5 px-3.5 rounded-lg font-bold transition-all flex items-center gap-2"
          :class="(isApprovalTab || activeTab === 'approval')
            ? 'bg-slate-800 text-amber-300 border border-slate-700/60 shadow-sm'
            : 'text-slate-300 hover:text-white hover:bg-slate-900/60'"
        >
          <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Course Approval</span>
          <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
            {{ summaryStats?.draft_count ?? 18 }}
          </span>
        </Link>

        <!-- 3. Enrollment -->
        <Link
          href="/admin/enrollment/courses"
          class="py-1.5 px-3.5 rounded-lg font-bold transition-all flex items-center gap-2"
          :class="activeTab === 'enrollment'
            ? 'bg-slate-800 text-indigo-300 border border-slate-700/60 shadow-sm'
            : 'text-slate-300 hover:text-white hover:bg-slate-900/60'"
        >
          <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
          </svg>
          <span>Enrollment</span>
        </Link>
      </div>
    </div>
  </div>
</template>
