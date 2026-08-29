<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const strongTopics = ref([
  { id: 1, title: 'JavaScript Variables & Data Types', score: 92, status: 'Mastered', lastQuiz: 'Module 2 Practice' },
  { id: 2, title: 'Basic Syntax & Arithmetic Operators', score: 88, status: 'Mastered', lastQuiz: 'Module 2 Practice' },
  { id: 3, title: 'Semantic HTML5 & Form Inputs', score: 95, status: 'Mastered', lastQuiz: 'Chapter 2 Quiz' },
  { id: 4, title: 'CSS3 Flexbox Alignment & Responsive Design', score: 85, status: 'Strong', lastQuiz: 'Chapter 2 Quiz' },
])

const weakTopics = ref([
  {
    id: 1,
    title: 'JavaScript Functions & Declarations',
    score: 62,
    threshold: 70,
    impact: 'High',
    errorPattern: 'Difficulty with return keyword vs console.log output distinction.',
    href: '/student/my-courses/current'
  },
  {
    id: 2,
    title: 'Function Parameters & Default Values',
    score: 58,
    threshold: 70,
    impact: 'High',
    errorPattern: 'Positional argument ordering vs default fallback values.',
    href: '/student/my-courses/current'
  },
  {
    id: 3,
    title: 'Closure & Lexical Scope in JS',
    score: 45,
    threshold: 70,
    impact: 'Medium',
    errorPattern: 'Variable visibility inside nested function blocks.',
    href: '/student/my-courses/current'
  }
])

const aiRecommendedActions = ref([
  {
    step: 1,
    title: 'Review JavaScript Functions Lesson',
    desc: 'Re-read the lesson transcript and notes focusing on function return values.',
    actionLabel: 'Review Lesson',
    href: '/student/my-courses/current'
  },
  {
    step: 2,
    title: 'Ask AI Assistant for a Simpler Explanation',
    desc: 'Get an interactive analogy comparing functions to mathematical machines and recipes.',
    actionLabel: 'Practice with AI',
    href: '/student/ai-tutor'
  },
  {
    step: 3,
    title: 'Complete 5 AI-Generated Practice Questions',
    desc: 'Targeted drills generated specifically for function parameters and return values.',
    actionLabel: 'Generate Practice',
    href: '/student/quizzes/practice'
  },
  {
    step: 4,
    title: 'Retake the Chapter 3 Mini Quiz',
    desc: 'Verify your improved mastery and elevate your score above the 70% threshold.',
    actionLabel: 'Retake Quiz',
    href: '/student/my-courses/current'
  }
])
</script>

<template>
  <StudentLayout
    title="AI Weak Topic Analysis & Recommendations"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'AI Learning', href: '/student/ai-path/recommended' },
      { label: 'Weak Topics Analysis' }
    ]"
  >
    <div class="space-y-6 pb-12">
      
      <!-- Top Banner -->
      <div class="bg-gradient-to-r from-rose-950 via-slate-900 to-purple-950 border border-rose-900/50 rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-80 h-80 bg-rose-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 space-y-2.5 max-w-2xl">
          <span class="px-3 py-1 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/40 text-xs font-bold uppercase tracking-wider">
            🤖 AI DIAGNOSTIC REPORT
          </span>

          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
            Weak Topic Analysis & Tailored Action Plan
          </h1>

          <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
            AI analyzed your recent quiz attempts and identified 3 key topics requiring reinforcement before advancing to DOM manipulation.
          </p>
        </div>

        <div class="relative z-10 flex flex-col sm:flex-row md:flex-col gap-3 shrink-0">
          <Link
            href="/student/quizzes/practice"
            class="px-6 py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-xl shadow-purple-600/30 flex items-center justify-center gap-2 hover:scale-105 active:scale-95 transition-all text-center cursor-pointer"
          >
            <span>⚡ Practice with AI (5 Qs)</span>
          </Link>
        </div>
      </div>

      <!-- Navigation Sub-Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-800 custom-scrollbar">
        <Link
          href="/student/ai-path/recommended"
          class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors"
        >
          🗺️ 1. Personalized Path
        </Link>
        <Link
          href="/student/ai-path/weak-topics"
          class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-purple-600 text-white shadow-md"
        >
          ⚠️ 2. Weak Topics Analysis
        </Link>
        <Link
          href="/student/ai-path/next-course"
          class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors"
        >
          🎓 3. Next Course Recommendation
        </Link>
      </div>

      <!-- MAIN 2-COLUMN GRID: Left Weak & Strong Topics, Right AI Step-by-Step Action Plan -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 7 COLUMNS: Weak Topics Cards & Strong Topics List -->
        <div class="lg:col-span-7 space-y-6">
          
          <!-- Weak Topics Focus Area -->
          <div class="bg-slate-900/80 border border-rose-500/30 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span>⚠️</span>
                <span>Topics Requiring Review (Under 70% Mastery)</span>
              </h2>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                3 Topics
              </span>
            </div>

            <div class="space-y-3.5">
              <div
                v-for="topic in weakTopics"
                :key="topic.id"
                class="p-4 rounded-2xl bg-slate-950/70 border border-rose-900/40 hover:border-rose-500/40 transition-colors space-y-2.5"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <h3 class="font-bold text-white text-xs sm:text-sm">{{ topic.title }}</h3>
                  </div>

                  <span class="text-xs font-mono font-bold text-rose-400">
                    {{ topic.score }}% (Target: {{ topic.threshold }}%)
                  </span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                  <div class="h-full bg-rose-500 rounded-full" :style="{ width: topic.score + '%' }"></div>
                </div>

                <p class="text-[11px] text-slate-300 leading-relaxed bg-slate-900/80 p-2.5 rounded-xl border border-slate-800">
                  <strong class="text-rose-300">AI Diagnosis:</strong> {{ topic.errorPattern }}
                </p>

                <div class="flex items-center justify-between pt-1 text-xs">
                  <span class="text-[10px] text-slate-400">Impact on Final Exam: <strong class="text-white">{{ topic.impact }}</strong></span>
                  <Link
                    :href="topic.href"
                    class="text-purple-400 hover:text-purple-300 font-bold text-xs"
                  >
                    Open Lesson →
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <!-- Strong Topics Mastered Area -->
          <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span>✓</span>
                <span>Strong Topics Mastered (Over 80%)</span>
              </h2>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                4 Topics
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div
                v-for="topic in strongTopics"
                :key="topic.id"
                class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-1.5"
              >
                <div class="flex items-center justify-between">
                  <span class="text-emerald-400 text-xs font-bold">✓ {{ topic.status }}</span>
                  <span class="text-xs font-mono font-bold text-white">{{ topic.score }}%</span>
                </div>
                <h4 class="font-bold text-slate-200 text-xs truncate">{{ topic.title }}</h4>
                <p class="text-[10px] text-slate-400">{{ topic.lastQuiz }}</p>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT 5 COLUMNS: AI Step-by-Step Recovery Action Plan -->
        <div class="lg:col-span-5 space-y-6">
          
          <div class="bg-gradient-to-br from-indigo-950/60 via-purple-950/40 to-slate-900 border border-purple-500/30 rounded-3xl p-6 shadow-2xl space-y-4">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center text-sm shadow-md shadow-purple-600/30">
                🤖
              </div>
              <div>
                <h3 class="text-sm font-bold text-white uppercase tracking-wider">AI Recommended Plan</h3>
                <p class="text-[11px] text-purple-300">Follow these 4 steps to achieve mastery</p>
              </div>
            </div>

            <!-- Steps List -->
            <div class="space-y-3 pt-2">
              <div
                v-for="act in aiRecommendedActions"
                :key="act.step"
                class="p-3.5 rounded-2xl bg-slate-950/80 border border-purple-500/20 space-y-2"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-purple-600 text-white text-[10px] font-bold flex items-center justify-center">
                      {{ act.step }}
                    </span>
                    <h4 class="font-bold text-white text-xs">{{ act.title }}</h4>
                  </div>
                </div>

                <p class="text-[11px] text-slate-300 leading-relaxed pl-7">
                  {{ act.desc }}
                </p>

                <div class="pl-7 pt-1">
                  <Link
                    :href="act.href"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition-all"
                  >
                    <span>{{ act.actionLabel }}</span>
                    <span>→</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- Quick Action Button Footer -->
            <div class="pt-3 border-t border-purple-500/20 flex flex-col gap-2">
              <Link
                href="/student/my-courses/current"
                class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 text-center transition-all"
              >
                Review Weak Topics Now
              </Link>
              <Link
                href="/student/quizzes/practice"
                class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs border border-slate-700 text-center transition-colors"
              >
                Practice with AI
              </Link>
            </div>
          </div>

        </div>

      </div>

    </div>
  </StudentLayout>
</template>
