<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const recentAssessments = ref([
  {
    id: 1,
    title: 'Module 2: Pointers & Arrays Quiz',
    course: 'C Programming Basics',
    date: 'Yesterday, 03:45 PM',
    score: 82,
    status: 'Passed',
    strengths: ['Pointer Arithmetic syntax', 'Array iteration logic', 'Memory address lookup'],
    weaknesses: ['Double pointer referencing (**ptr)', 'Pointer to Struct dereferencing (-> vs .)'],
    aiRecommendation: 'Review Lesson 3.4 (Pointers to Structures) and complete 5 drill exercises before the upcoming midterm exam.',
    reviewLink: '/student/ai-path/review'
  },
  {
    id: 2,
    title: 'Assignment 1: Linked List Implementation',
    course: 'Data Structures & Algorithms',
    date: 'Aug 10, 2026',
    score: 91,
    status: 'Excellent',
    strengths: ['Node insertion at head and tail', 'Memory leak prevention with free()'],
    weaknesses: ['Corner case handling: Deletion on empty list'],
    aiRecommendation: 'Great job! Add defensive null-check guards at the top of delete_node() function.',
    reviewLink: '/student/quizzes/assignments'
  }
])
</script>

<template>
  <StudentLayout title="AI Assistant — Instant Feedback">
    <div class="space-y-6">
      
      <!-- Top Banner -->
      <div class="bg-gradient-to-r from-amber-950 via-slate-900 to-indigo-950 border border-amber-900/60 rounded-3xl p-6 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold uppercase tracking-wider">
            ⚡ Intelligent Performance Diagnostics
          </span>
          <h1 class="text-xl md:text-2xl font-black text-white mt-1.5 flex items-center gap-2">
            <span>AI INSTANT FEEDBACK (ផ្តល់មតិលើលទ្ធផល QUIZ & ASSIGNMENT)</span>
          </h1>
          <p class="text-xs text-slate-300 mt-1">
            Automated deep feedback analyzing your correct & incorrect quiz answers, code efficiency, and recommended next steps
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Link
            href="/student/quizzes"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition-colors"
          >
            📝 All Quizzes
          </Link>
          <Link
            href="/student/ai-path/weak-topics"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition-colors"
          >
            🎯 Weak Topics
          </Link>
        </div>
      </div>

      <!-- Feedback Cards List -->
      <div class="space-y-5">
        <div
          v-for="item in recentAssessments"
          :key="item.id"
          class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-6 shadow-xl space-y-5 hover:border-amber-500/40 transition-colors"
        >
          <!-- Assessment Header -->
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-700/60 pb-4">
            <div>
              <span class="text-xs text-amber-400 font-semibold">{{ item.course }}</span>
              <h3 class="text-lg font-bold text-white mt-0.5">{{ item.title }}</h3>
              <p class="text-[11px] text-slate-400">Completed on {{ item.date }}</p>
            </div>

            <div class="flex items-center gap-3">
              <div class="text-right">
                <p class="text-[10px] text-slate-400 font-bold uppercase">Score</p>
                <p class="text-xl font-black text-amber-400 font-mono">{{ item.score }}/100</p>
              </div>
              <span class="px-3 py-1 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold">
                {{ item.status }}
              </span>
            </div>
          </div>

          <!-- Strengths vs Weaknesses -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl space-y-2">
              <h4 class="text-xs font-bold text-emerald-300 uppercase tracking-wider flex items-center gap-1.5">
                <span>✅ Strong Mastery Points</span>
              </h4>
              <ul class="space-y-1 text-xs text-slate-300">
                <li v-for="str in item.strengths" :key="str" class="flex items-center gap-2">
                  <span class="text-emerald-400">✓</span>
                  <span>{{ str }}</span>
                </li>
              </ul>
            </div>

            <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl space-y-2">
              <h4 class="text-xs font-bold text-amber-300 uppercase tracking-wider flex items-center gap-1.5">
                <span>⚠️ Points for Immediate Improvement</span>
              </h4>
              <ul class="space-y-1 text-xs text-slate-300">
                <li v-for="weak in item.weaknesses" :key="weak" class="flex items-center gap-2">
                  <span class="text-amber-400">!</span>
                  <span>{{ weak }}</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- AI Actionable Recommendation -->
          <div class="p-4 bg-slate-900/80 border border-slate-700/80 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
              <p class="text-[11px] font-bold text-indigo-400 uppercase tracking-wider flex items-center gap-1.5">
                <img :src="'/images/ai-robot-avatar.png'" alt="" class="w-4 h-4 rounded-sm object-contain shrink-0 select-none" />
                <span>AI Customized Action Step:</span>
              </p>
              <p class="text-xs text-slate-200 font-medium">
                {{ item.aiRecommendation }}
              </p>
            </div>

            <Link
              :href="item.reviewLink"
              class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-md transition-all shrink-0 text-center"
            >
              Start Review Now →
            </Link>
          </div>

        </div>
      </div>

    </div>
  </StudentLayout>
</template>
