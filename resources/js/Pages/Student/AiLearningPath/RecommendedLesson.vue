<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const studentProfile = ref({
  level: 'Intermediate (Level 2)',
  preTestScore: 45,
  currentQuizAvg: 78,
  learningTime: '12h 30m',
  completedLessons: 8,
  totalLessons: 15,
  pacing: 'Optimal (+15% ahead of schedule)',
  targetDate: 'June 15, 2026'
})

interface RoadmapNode {
  id: number
  title: string
  category: string
  status: 'completed' | 'active' | 'pending' | 'locked'
  score?: number
  duration: string
  desc: string
  badge?: string
}

const roadmap = ref<RoadmapNode[]>([
  {
    id: 1,
    title: 'HTML Basics & Semantic Markup',
    category: 'Core Foundations',
    status: 'completed',
    score: 95,
    duration: '4h 15m',
    desc: 'Mastered semantic HTML5 tags, accessible form inputs, and document hierarchies.',
    badge: 'Mastered'
  },
  {
    id: 2,
    title: 'CSS3 Layouts, Flexbox & Grid',
    category: 'Styling & Design',
    status: 'completed',
    score: 88,
    duration: '5h 30m',
    desc: 'Designed responsive mobile-first layouts with modern CSS Grid and Flexbox alignment.',
    badge: 'Mastered'
  },
  {
    id: 3,
    title: 'JavaScript Functions & Scope',
    category: 'Logic & Scripting',
    status: 'active',
    score: 78,
    duration: '3h 45m',
    desc: 'Currently learning function declarations, parameters, return expressions, and execution scopes.',
    badge: 'In Progress (53%)'
  },
  {
    id: 4,
    title: 'Function Parameters, Default Values & Rest Syntax',
    category: 'Logic & Scripting',
    status: 'pending',
    duration: '2h 15m',
    desc: 'Scheduled next: Deep dive into parameter handling, rest parameters, and spread operators.',
    badge: 'Up Next'
  },
  {
    id: 5,
    title: 'DOM Manipulation & Event Listeners',
    category: 'Interactive UI',
    status: 'locked',
    duration: '4h 00m',
    desc: 'Unlock after completing JavaScript Functions: Select, manipulate DOM elements and listen to user events.',
    badge: 'Locked'
  },
  {
    id: 6,
    title: 'Advanced JavaScript & Async/Await API Fetching',
    category: 'Advanced Logic',
    status: 'locked',
    duration: '6h 30m',
    desc: 'Asynchronous JavaScript, Promises, Fetch API calls, and error handling.',
    badge: 'Locked'
  },
  {
    id: 7,
    title: 'React.js & Modern Component Architecture',
    category: 'Frontend Framework',
    status: 'locked',
    duration: '8h 00m',
    desc: 'AI Recommended Future Path based on your fast progress in Web Development.',
    badge: 'Roadmap Milestone'
  }
])
</script>

<template>
  <StudentLayout
    title="AI Personalized Learning Path"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'AI Learning', href: '/student/ai-path/recommended' },
      { label: 'Personalized Learning Path' }
    ]"
  >
    <div class="space-y-6 pb-12">
      
      <!-- Top AI Banner -->
      <div class="bg-gradient-to-r from-purple-950 via-slate-900 to-indigo-950 border border-purple-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-80 h-80 bg-purple-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 space-y-2.5 max-w-2xl">
          <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 text-xs font-bold uppercase tracking-wider">
              🤖 AI ADAPTIVE ENGINE
            </span>
            <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold border border-indigo-500/30">
              {{ studentProfile.level }}
            </span>
          </div>

          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
            Your Personalized Learning Path
          </h1>

          <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
            AI continuously adapts this visual roadmap based on your Pre-Test ({{ studentProfile.preTestScore }}%), Quiz average ({{ studentProfile.currentQuizAvg }}%), and lesson completion speed.
          </p>
        </div>

        <div class="relative z-10 flex flex-col sm:flex-row md:flex-col gap-3 shrink-0">
          <Link
            href="/student/my-courses/current"
            class="px-6 py-3 rounded-2xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-xl shadow-purple-600/30 flex items-center justify-center gap-2 hover:scale-105 active:scale-95 transition-all text-center cursor-pointer"
          >
            <span>▶ Continue Active Lesson</span>
          </Link>
        </div>
      </div>

      <!-- Navigation Sub-Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-800 custom-scrollbar">
        <Link
          href="/student/ai-path/recommended"
          class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-purple-600 text-white shadow-md"
        >
          🗺️ 1. Personalized Path
        </Link>
        <Link
          href="/student/ai-path/weak-topics"
          class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors"
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

      <!-- AI Profile Metrics 4-Card Row -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4.5 shadow-xl space-y-1">
          <p class="text-[11px] text-slate-400 font-medium">🎯 Starting Pre-Test</p>
          <p class="text-xl font-black text-white">{{ studentProfile.preTestScore }}%</p>
          <p class="text-[10px] text-purple-400">Determined Level 2 Baseline</p>
        </div>
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4.5 shadow-xl space-y-1">
          <p class="text-[11px] text-slate-400 font-medium">📊 Current Quiz Avg</p>
          <p class="text-xl font-black text-emerald-400">{{ studentProfile.currentQuizAvg }}%</p>
          <p class="text-[10px] text-emerald-400/80">+33% Growth from Pre-Test</p>
        </div>
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4.5 shadow-xl space-y-1">
          <p class="text-[11px] text-slate-400 font-medium">⏱ Total Learning Time</p>
          <p class="text-xl font-black text-cyan-300">{{ studentProfile.learningTime }}</p>
          <p class="text-[10px] text-slate-400">8 / 15 Lessons Completed</p>
        </div>
        <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4.5 shadow-xl space-y-1">
          <p class="text-[11px] text-slate-400 font-medium">⚡ AI Pacing Analysis</p>
          <p class="text-sm font-black text-purple-300 truncate">{{ studentProfile.pacing }}</p>
          <p class="text-[10px] text-slate-400">Est. Target: {{ studentProfile.targetDate }}</p>
        </div>
      </div>

      <!-- VISUAL ROADMAP SECTION -->
      <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
          <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
              <span>📍</span>
              <span>Interactive Step-by-Step Learning Roadmap</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Customized progression based on your real-time assessment feedback</p>
          </div>
          <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 text-xs font-bold border border-purple-500/30">
            53% Path Completed
          </span>
        </div>

        <!-- Roadmap Timeline Tree -->
        <div class="relative pl-6 sm:pl-8 space-y-6 before:absolute before:left-3 sm:before:left-4 before:top-3 before:bottom-3 before:w-0.5 before:bg-gradient-to-b before:from-emerald-500 before:via-purple-500 before:to-slate-700">
          
          <div
            v-for="node in roadmap"
            :key="node.id"
            class="relative group"
          >
            <!-- Timeline Node Indicator Icon -->
            <div
              :class="[
                node.status === 'completed'
                  ? 'bg-emerald-500 text-white ring-4 ring-emerald-500/20'
                  : node.status === 'active'
                  ? 'bg-purple-600 text-white ring-4 ring-purple-500/30 animate-pulse'
                  : node.status === 'pending'
                  ? 'bg-slate-800 text-slate-400 ring-4 ring-slate-800'
                  : 'bg-slate-900 text-slate-600 ring-4 ring-slate-900',
                'absolute -left-6 sm:-left-8 top-3 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-all z-10'
              ]"
            >
              <span v-if="node.status === 'completed'">✓</span>
              <span v-else-if="node.status === 'active'">▶</span>
              <span v-else-if="node.status === 'pending'">○</span>
              <span v-else>🔒</span>
            </div>

            <!-- Card Content Box -->
            <div
              :class="[
                node.status === 'active'
                  ? 'bg-purple-950/30 border-purple-500/40 shadow-xl shadow-purple-950/50'
                  : node.status === 'completed'
                  ? 'bg-slate-950/70 border-slate-800/80 hover:border-emerald-500/30'
                  : 'bg-slate-950/40 border-slate-800/60 opacity-80',
                'rounded-2xl p-5 border transition-all duration-200 space-y-2.5'
              ]"
            >
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                  <span class="text-[11px] font-bold text-purple-400 uppercase tracking-wider">{{ node.category }}</span>
                  <span class="text-slate-600">•</span>
                  <span class="text-[11px] text-slate-400 font-mono">{{ node.duration }}</span>
                </div>

                <span
                  :class="[
                    node.status === 'completed'
                      ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'
                      : node.status === 'active'
                      ? 'bg-purple-500/20 text-purple-300 border-purple-500/30'
                      : node.status === 'pending'
                      ? 'bg-blue-500/20 text-blue-300 border-blue-500/30'
                      : 'bg-slate-800 text-slate-500 border-slate-700',
                    'px-2.5 py-0.5 rounded-full text-[10px] font-bold border self-start sm:self-auto'
                  ]"
                >
                  {{ node.badge }}
                </span>
              </div>

              <h3 class="text-sm sm:text-base font-bold text-white flex items-center justify-between">
                <span>{{ node.title }}</span>
                <span v-if="node.score" class="text-xs text-emerald-400 font-mono font-bold">{{ node.score }}% Score</span>
              </h3>

              <p class="text-xs text-slate-300 leading-relaxed">
                {{ node.desc }}
              </p>

              <!-- Node Action Trigger if active -->
              <div v-if="node.status === 'active'" class="pt-2 flex items-center gap-3">
                <Link
                  href="/student/my-courses/current"
                  class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/30 flex items-center gap-2 transition-all hover:scale-105 active:scale-95"
                >
                  <span>Launch Lesson Room</span>
                  <span>→</span>
                </Link>
                <Link
                  href="/student/ai-path/weak-topics"
                  class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700"
                >
                  View Topic Analysis
                </Link>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </StudentLayout>
</template>
