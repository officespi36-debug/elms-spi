<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const performanceMetrics = ref({
  currentScore: 60,
  previousScore: 45,
  improvement: 15,
  learningVelocity: 'Optimal',
  weeklyTime: '6h 45m',
  strongTopicsCount: 4,
  weakTopicsCount: 3
})

const todayPlan = ref({
  totalTimeMinutes: 30,
  steps: [
    { duration: '10 min', title: 'Review JavaScript Functions', type: 'Review', href: '/student/my-courses/current' },
    { duration: '10 min', title: 'Practice Questions (5 Qs)', type: 'Practice', href: '/student/quizzes/practice' },
    { duration: '10 min', title: 'Review Incorrect Answers & AI Explanations', type: 'Reflect', href: '/student/ai-path/weak-topics' }
  ]
})

const strongTopics = ref([
  { title: 'JavaScript Variables & Data Types', mastery: 92 },
  { title: 'Basic Syntax & Arithmetic Operators', mastery: 88 },
  { title: 'Semantic HTML5 Architecture', mastery: 95 },
  { title: 'CSS3 Flexbox & Alignment', mastery: 85 }
])

const weakTopics = ref([
  { title: 'JavaScript Functions & Scope', mastery: 62, target: 70 },
  { title: 'Function Parameters & Default Values', mastery: 58, target: 70 },
  { title: 'Closure & Lexical Scope', mastery: 45, target: 70 }
])
</script>

<template>
  <StudentLayout
    title="AI Performance Analysis & Study Plan"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Progress & Analytics', href: '/student/progress' },
      { label: 'AI Performance Analysis' }
    ]"
  >
    <div class="space-y-6 pb-12">
      
      <!-- Top Banner -->
      <div class="bg-gradient-to-r from-purple-950 via-slate-900 to-indigo-950 border border-purple-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="space-y-2.5 max-w-2xl">
          <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 text-xs font-bold uppercase tracking-wider">
            🤖 AI PERFORMANCE ENGINE
          </span>

          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
            Learning Performance & AI Study Plan
          </h1>

          <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
            AI measures your score improvements across diagnostic quizzes and compiles dynamic daily study routines customized to your schedule.
          </p>
        </div>
      </div>

      <!-- PERFORMANCE SUMMARY 4-CARD STATS -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-1">
          <p class="text-[11px] text-slate-400 font-medium">Current Assessment Score</p>
          <p class="text-2xl font-black text-white">{{ performanceMetrics.currentScore }}%</p>
          <p class="text-[10px] text-purple-400">Target Benchmark: 80%</p>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-1">
          <p class="text-[11px] text-slate-400 font-medium">Net Knowledge Improvement</p>
          <div class="flex items-center gap-2">
            <span class="text-2xl font-black text-emerald-400">+{{ performanceMetrics.improvement }}%</span>
            <span class="text-[10px] text-slate-400 font-mono">({{ performanceMetrics.previousScore }}% → {{ performanceMetrics.currentScore }}%)</span>
          </div>
          <p class="text-[10px] text-emerald-400/90">Solid upward growth trajectory</p>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-1">
          <p class="text-[11px] text-slate-400 font-medium">Strong Topics Mastered</p>
          <p class="text-2xl font-black text-emerald-400">{{ performanceMetrics.strongTopicsCount }} Topics</p>
          <p class="text-[10px] text-slate-400">Above 80% Mastery</p>
        </div>

        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-1">
          <p class="text-[11px] text-slate-400 font-medium">Topics for Remediation</p>
          <p class="text-2xl font-black text-rose-400">{{ performanceMetrics.weakTopicsCount }} Topics</p>
          <p class="text-[10px] text-rose-400/90">Under 70% Mastery</p>
        </div>

      </div>

      <!-- MAIN 2-COLUMN SECTION: Today's AI Recommendation vs Strong/Weak Topics -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 6 COLUMNS: Today's AI 30-Minute Recommendation -->
        <div class="lg:col-span-6 bg-gradient-to-br from-indigo-950/70 via-purple-950/50 to-slate-900 border border-purple-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
          <div class="flex items-center justify-between border-b border-purple-500/20 pb-4">
            <div class="flex items-center gap-2.5">
              <span class="text-xl">🤖</span>
              <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">Today's AI Recommendation</h3>
                <p class="text-xs text-purple-300">Target Time: {{ todayPlan.totalTimeMinutes }} Minutes Available</p>
              </div>
            </div>

            <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold border border-purple-500/30">
              Personalized Plan
            </span>
          </div>

          <!-- 3-Step Routine Timeline -->
          <div class="space-y-3.5">
            <div
              v-for="(step, idx) in todayPlan.steps"
              :key="idx"
              class="p-4 rounded-2xl bg-slate-950/80 border border-purple-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
              <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-xl bg-purple-600/30 text-purple-400 border border-purple-500/40 flex items-center justify-center text-xs font-mono font-bold shrink-0">
                  {{ idx + 1 }}
                </span>
                <div>
                  <h4 class="font-bold text-white text-xs">{{ step.title }}</h4>
                  <span class="text-[11px] text-slate-400 font-mono">⏱ {{ step.duration }} • {{ step.type }}</span>
                </div>
              </div>

              <Link
                :href="step.href"
                class="px-4 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/30 text-center transition-all self-start sm:self-auto"
              >
                Start →
              </Link>
            </div>
          </div>

          <div class="pt-2">
            <Link
              href="/student/my-courses/current"
              class="w-full py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-xl shadow-purple-600/30 flex items-center justify-center gap-2 transition-all hover:scale-105 active:scale-95 text-center"
            >
              <span>⚡ Execute Today's AI Plan</span>
            </Link>
          </div>
        </div>

        <!-- RIGHT 6 COLUMNS: Strong Topics vs Weak Topics Mastery Progress -->
        <div class="lg:col-span-6 space-y-6">
          
          <!-- Weak Topics Need Review -->
          <div class="bg-slate-900/80 border border-rose-500/30 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span>⚠️</span>
                <span>Weak Topics Requiring Practice</span>
              </h3>
              <Link href="/student/ai-path/weak-topics" class="text-xs text-rose-400 hover:text-rose-300 font-bold">
                View Details →
              </Link>
            </div>

            <div class="space-y-3">
              <div v-for="t in weakTopics" :key="t.title" class="space-y-1 text-xs">
                <div class="flex items-center justify-between">
                  <span class="text-slate-300 font-semibold">{{ t.title }}</span>
                  <span class="text-rose-400 font-mono font-bold">{{ t.mastery }}%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-950 overflow-hidden">
                  <div class="h-full bg-rose-500 rounded-full" :style="{ width: t.mastery + '%' }"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Strong Topics Mastered -->
          <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-800 pb-3">
              <span>✓</span>
              <span>Strong Topics Mastered</span>
            </h3>

            <div class="space-y-3">
              <div v-for="s in strongTopics" :key="s.title" class="space-y-1 text-xs">
                <div class="flex items-center justify-between">
                  <span class="text-slate-300 font-semibold">{{ s.title }}</span>
                  <span class="text-emerald-400 font-mono font-bold">{{ s.mastery }}%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-950 overflow-hidden">
                  <div class="h-full bg-emerald-500 rounded-full" :style="{ width: s.mastery + '%' }"></div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>
  </StudentLayout>
</template>
