<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const preTestSummary = ref({
  score: 45,
  level: 'Beginner / Foundation Level',
  date: 'Feb 10, 2026',
  startingRecommendation: 'Chapter 1: Introduction to Web Development',
  topicBreakdown: [
    { topic: 'HTML Basics', score: 60, status: 'Moderate' },
    { topic: 'CSS Fundamentals', score: 50, status: 'Basic' },
    { topic: 'JavaScript Logic', score: 25, status: 'Novice' }
  ]
})

const postTestSummary = ref({
  score: 85,
  growth: 40,
  date: 'Active Assessment',
  strongAreas: [
    'HTML5 Semantic Architecture (95%)',
    'CSS Flexbox & Responsive Layouts (88%)',
    'JavaScript Function Syntax & Statements (85%)'
  ],
  reinforceAreas: [
    'Closure & Scope Invariance (62%)',
    'Asynchronous Fetch & Promise Chaining (68%)'
  ],
  recommendedCourses: [
    { title: 'Advanced JavaScript & Async/Await', match: '98% Match' },
    { title: 'React.js Component Architecture', match: '95% Match' }
  ]
})

const gradeComponents = ref([
  { name: 'Pre-Test Diagnostic', weight: '10%', max: 100, score: '45.0%', weighted: '4.5 / 10', status: '✅ Done' },
  { name: 'Chapter Practice Quizzes', weight: '20%', max: 100, score: '78.0%', weighted: '15.6 / 20', status: '✅ Active' },
  { name: 'Interactive Lab Assignments', weight: '30%', max: 100, score: '88.0%', weighted: '26.4 / 30', status: '✅ Graded' },
  { name: 'Post-Test Assessment', weight: '40%', max: 100, score: '85.0%', weighted: '34.0 / 40', status: '✅ Passed' }
])
</script>

<template>
  <StudentLayout
    title="Assessment Scores & AI Pre/Post Test Analysis"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'Quizzes', href: '/student/quizzes/all' },
      { label: 'Scores & AI Analysis' }
    ]"
  >
    <div class="space-y-6 pb-12">
      
      <!-- Top Banner -->
      <div class="bg-gradient-to-r from-purple-950 via-slate-900 to-indigo-950 border border-purple-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="space-y-2.5 max-w-2xl">
          <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 text-xs font-bold uppercase tracking-wider">
            🤖 AI EVALUATION REPORT
          </span>

          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
            Diagnostic & Assessment Scores
          </h1>

          <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
            AI tracks your full learning trajectory from starting diagnostic Pre-Test ({{ preTestSummary.score }}%) to current Post-Test mastery ({{ postTestSummary.score }}%), measuring a net +{{ postTestSummary.growth }}% knowledge expansion.
          </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <div class="px-5 py-3 rounded-2xl bg-slate-900/90 border border-slate-800 text-right">
            <p class="text-[10px] text-slate-400 font-bold uppercase">Overall Grade</p>
            <p class="text-xl font-black text-emerald-400">80.5% (A-)</p>
          </div>
        </div>
      </div>

      <!-- PRE-TEST & POST-TEST 2-COLUMN COMPARISON -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Pre-Test AI Analysis Card -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
              <span class="text-base">📋</span>
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">Pre-Test Diagnostic Analysis</h3>
            </div>
            <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 text-xs font-mono font-bold">
              Score: {{ preTestSummary.score }}%
            </span>
          </div>

          <div class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-2">
            <p class="text-xs text-slate-400 font-medium">Assessed Baseline Level:</p>
            <p class="text-sm font-bold text-purple-300">{{ preTestSummary.level }}</p>
            <p class="text-xs text-slate-300 pt-1">
              <strong class="text-white">AI Starting Recommendation:</strong> Start from {{ preTestSummary.startingRecommendation }}.
            </p>
          </div>

          <!-- Pre-test topic breakdown -->
          <div class="space-y-2 pt-1">
            <p class="text-[11px] font-bold text-slate-400 uppercase">Diagnostic Topic Baseline:</p>
            <div
              v-for="item in preTestSummary.topicBreakdown"
              :key="item.topic"
              class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/60 flex items-center justify-between text-xs"
            >
              <span class="text-slate-300">{{ item.topic }}</span>
              <span class="text-slate-400 font-mono">{{ item.score }}% ({{ item.status }})</span>
            </div>
          </div>
        </div>

        <!-- Post-Test AI Analysis Card -->
        <div class="bg-slate-900/80 border border-purple-500/30 rounded-3xl p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
              <span class="text-base">🏆</span>
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">Post-Test Mastery Report</h3>
            </div>
            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-mono font-bold border border-emerald-500/30">
              Score: {{ postTestSummary.score }}% (+{{ postTestSummary.growth }}%)
            </span>
          </div>

          <div class="p-4 rounded-2xl bg-indigo-950/40 border border-indigo-500/30 space-y-2">
            <p class="text-xs text-indigo-300 font-bold flex items-center gap-1.5">
              <span>🤖 AI Knowledge Growth:</span>
              <span class="text-emerald-400">+{{ postTestSummary.growth }}% Increase</span>
            </p>
            <p class="text-xs text-slate-300 leading-relaxed">
              Outstanding learning trajectory. You transitioned from basic syntax comprehension to building modular JavaScript functions and modern styling.
            </p>
          </div>

          <!-- Strong Areas vs Reinforce Areas -->
          <div class="space-y-2 text-xs pt-1">
            <p class="text-[11px] font-bold text-emerald-400 uppercase">Top Strong Areas:</p>
            <ul class="space-y-1.5 text-slate-300">
              <li v-for="area in postTestSummary.strongAreas" :key="area" class="flex items-center gap-2">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>{{ area }}</span>
              </li>
            </ul>
          </div>
        </div>

      </div>

      <!-- GRADEBOOK TABLE -->
      <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Full Course Grade Breakdown</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left text-slate-300">
            <thead class="text-[11px] uppercase bg-slate-950 text-slate-400 border-b border-slate-800">
              <tr>
                <th class="p-3.5">Component</th>
                <th class="p-3.5">Weight</th>
                <th class="p-3.5">Score</th>
                <th class="p-3.5">Weighted Contribution</th>
                <th class="p-3.5">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
              <tr v-for="g in gradeComponents" :key="g.name" class="hover:bg-slate-800/40">
                <td class="p-3.5 font-bold text-white">{{ g.name }}</td>
                <td class="p-3.5 font-mono text-slate-400">{{ g.weight }}</td>
                <td class="p-3.5 font-mono font-bold text-emerald-400">{{ g.score }}</td>
                <td class="p-3.5 font-mono text-white">{{ g.weighted }}</td>
                <td class="p-3.5 text-slate-300">{{ g.status }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </StudentLayout>
</template>
