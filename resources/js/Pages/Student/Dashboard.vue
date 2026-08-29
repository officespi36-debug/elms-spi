<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps<{
  enrollments?: any[]
  stats?: {
    enrolledCount?: number
    inProgressCount?: number
    completedCount?: number
    certificatesCount?: number
    learningTime?: string
    averageScore?: number
  }
}>()

const page = usePage<any>()
const user = computed(() => page.props.auth?.user || {})

const continueCourse = ref({
  title: 'C Programming Basics',
  chapter: 'Module 2: Operators',
  progress: 65,
  teacher: 'Mr. Sophea',
  lastAccessed: 'Today, 09:30 AM',
  href: '/student/courses'
})

const aiRecommendation = ref({
  title: 'Arrays in C',
  reason: 'Based on your performance in Operators quiz (72%)',
  difficulty: 2,
  href: '/student/ai-path'
})

const todayGoal = ref({
  target: 2,
  completed: 1,
  percentage: 50
})

const streakCount = ref(5)
const streakDays = ref([
  { day: 'M', active: true },
  { day: 'T', active: true },
  { day: 'W', active: true },
  { day: 'T', active: true },
  { day: 'F', active: true },
  { day: 'S', active: false },
  { day: 'S', active: false }
])
const xpPoints = ref(850)
const nextLevelXp = ref(1000)
</script>

<template>
  <StudentLayout title="Student Dashboard">
    <div class="space-y-6">
      
      <!-- Welcome Hero Banner -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/15">
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-0 right-1/4 w-40 h-40 bg-purple-400/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-2.5 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md text-xs font-semibold border border-white/20">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>{{ user.major || 'IT & Networking' }} • Semester 2</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
              Welcome back, {{ user.name || 'Chan Dara' }}! <span class="animate-waving-hand inline-block">👋</span>
            </h1>
            <p class="text-indigo-100 text-xs sm:text-sm leading-relaxed max-w-xl">
              Here is your learning overview for today. Continue your courses, practice quizzes, and follow your AI learning path easily.
            </p>
          </div>

          <div class="flex items-center gap-3 shrink-0 flex-wrap sm:flex-nowrap">
            <Link
              href="/student/my-courses/current"
              prefetch="hover"
              class="px-5 py-2.5 rounded-xl bg-white text-indigo-900 font-bold text-xs hover:bg-indigo-50 transition-all duration-200 shadow-lg shadow-black/10 hover:scale-105 active:scale-95 flex items-center gap-2 cursor-pointer"
            >
              <span>▶ Continue Learning</span>
            </Link>
            <Link
              href="/student/browse"
              prefetch="hover"
              class="px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 border border-white/25 text-white font-semibold text-xs transition-all duration-200 backdrop-blur-md cursor-pointer"
            >
              Explore Catalog
            </Link>
          </div>
        </div>
      </div>

      <!-- 6 Stats Cards Grid (Fully Responsive: grid-cols-2 sm:grid-cols-3 xl:grid-cols-6) -->
      <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4">
        
        <!-- Enrolled -->
        <div class="bg-white dark:bg-[#111827]/80 border border-slate-200/80 dark:border-white/5 hover:border-indigo-500/30 rounded-2xl p-4 sm:p-4.5 flex flex-col justify-between shadow-xs dark:shadow-lg dark:shadow-black/20 transition-all duration-200 hover:-translate-y-0.5 group">
          <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Enrolled</span>
            <span class="w-7 h-7 rounded-xl bg-blue-500/10 text-blue-500 dark:text-blue-400 flex items-center justify-center text-xs group-hover:scale-110 transition-transform">📘</span>
          </div>
          <div>
            <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ stats?.enrolledCount ?? 4 }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Total Courses</p>
          </div>
        </div>

        <!-- In Progress -->
        <div class="bg-white dark:bg-[#111827]/80 border border-slate-200/80 dark:border-white/5 hover:border-amber-500/30 rounded-2xl p-4 sm:p-4.5 flex flex-col justify-between shadow-xs dark:shadow-lg dark:shadow-black/20 transition-all duration-200 hover:-translate-y-0.5 group">
          <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">In Progress</span>
            <span class="w-7 h-7 rounded-xl bg-amber-500/10 text-amber-500 dark:text-amber-400 flex items-center justify-center text-xs group-hover:scale-110 transition-transform">🔄</span>
          </div>
          <div>
            <p class="text-2xl font-black text-amber-500 dark:text-amber-400 tracking-tight">{{ stats?.inProgressCount ?? 3 }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Active Courses</p>
          </div>
        </div>

        <!-- Completed -->
        <div class="bg-white dark:bg-[#111827]/80 border border-slate-200/80 dark:border-white/5 hover:border-emerald-500/30 rounded-2xl p-4 sm:p-4.5 flex flex-col justify-between shadow-xs dark:shadow-lg dark:shadow-black/20 transition-all duration-200 hover:-translate-y-0.5 group">
          <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Completed</span>
            <span class="w-7 h-7 rounded-xl bg-emerald-500/10 text-emerald-500 dark:text-emerald-400 flex items-center justify-center text-xs group-hover:scale-110 transition-transform">✅</span>
          </div>
          <div>
            <p class="text-2xl font-black text-emerald-500 dark:text-emerald-400 tracking-tight">{{ stats?.completedCount ?? 1 }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Finished Courses</p>
          </div>
        </div>

        <!-- Certificates -->
        <div class="bg-white dark:bg-[#111827]/80 border border-slate-200/80 dark:border-white/5 hover:border-purple-500/30 rounded-2xl p-4 sm:p-4.5 flex flex-col justify-between shadow-xs dark:shadow-lg dark:shadow-black/20 transition-all duration-200 hover:-translate-y-0.5 group">
          <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Certificates</span>
            <span class="w-7 h-7 rounded-xl bg-purple-500/10 text-purple-500 dark:text-purple-400 flex items-center justify-center text-xs group-hover:scale-110 transition-transform">🏅</span>
          </div>
          <div>
            <p class="text-2xl font-black text-purple-500 dark:text-purple-400 tracking-tight">{{ stats?.certificatesCount ?? 2 }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Earned</p>
          </div>
        </div>

        <!-- Learning Time -->
        <div class="bg-white dark:bg-[#111827]/80 border border-slate-200/80 dark:border-white/5 hover:border-cyan-500/30 rounded-2xl p-4 sm:p-4.5 flex flex-col justify-between shadow-xs dark:shadow-lg dark:shadow-black/20 transition-all duration-200 hover:-translate-y-0.5 group">
          <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Learning Time</span>
            <span class="w-7 h-7 rounded-xl bg-cyan-500/10 text-cyan-500 dark:text-cyan-400 flex items-center justify-center text-xs group-hover:scale-110 transition-transform">⏱️</span>
          </div>
          <div>
            <p class="text-xl sm:text-2xl font-black text-cyan-600 dark:text-cyan-300 tracking-tight">{{ stats?.learningTime ?? '28h 30m' }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Total Hours</p>
          </div>
        </div>

        <!-- Avg Score -->
        <div class="bg-white dark:bg-[#111827]/80 border border-slate-200/80 dark:border-white/5 hover:border-indigo-500/30 rounded-2xl p-4 sm:p-4.5 flex flex-col justify-between shadow-xs dark:shadow-lg dark:shadow-black/20 transition-all duration-200 hover:-translate-y-0.5 group">
          <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Avg Score</span>
            <span class="w-7 h-7 rounded-xl bg-indigo-500/10 text-indigo-500 dark:text-indigo-400 flex items-center justify-center text-xs group-hover:scale-110 transition-transform">📊</span>
          </div>
          <div>
            <p class="text-2xl font-black text-indigo-600 dark:text-indigo-300 tracking-tight">{{ stats?.averageScore ?? 78 }}%</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Quiz Average</p>
          </div>
        </div>
      </div>

      <!-- Main 2-Column Grid: Continue Learning, AI Recommendation & Today's Goal -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Columns: Continue Learning & AI Recommendation -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Continue Learning Card -->
          <div class="bg-white dark:bg-[#111827]/80 border border-slate-200/80 dark:border-white/5 rounded-3xl p-6 shadow-xs dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-3">
              <div class="flex items-center gap-2.5">
                <span class="text-lg">📖</span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Continue Learning</h3>
              </div>
              <span class="text-xs text-slate-500 dark:text-slate-400">Last accessed: {{ continueCourse.lastAccessed }}</span>
            </div>

            <div class="bg-slate-50 dark:bg-slate-900/60 rounded-2xl p-5 border border-slate-200/80 dark:border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-5">
              <div class="space-y-2.5 flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border border-indigo-500/30">
                    Teacher-Led • Mr. Sophea
                  </span>
                </div>
                <h4 class="text-base font-bold text-slate-900 dark:text-white truncate">{{ continueCourse.title }}</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ continueCourse.chapter }}</p>

                <!-- Progress Bar -->
                <div class="space-y-1.5 pt-1">
                  <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 dark:text-slate-400">Overall Progress</span>
                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ continueCourse.progress }}%</span>
                  </div>
                  <div class="w-full h-2.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-cyan-400 rounded-full transition-all duration-500" :style="{ width: continueCourse.progress + '%' }"></div>
                  </div>
                </div>
              </div>

              <Link
                :href="continueCourse.href"
                prefetch="hover"
                class="shrink-0 px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition-all duration-200 shadow-md shadow-indigo-600/25 text-center hover:scale-105 active:scale-95 cursor-pointer"
              >
                Continue Lesson →
              </Link>
            </div>
          </div>

          <!-- AI Recommendation Card -->
          <div class="bg-gradient-to-br from-indigo-950/70 via-purple-950/50 to-[#111827] border border-indigo-500/30 rounded-3xl p-6 shadow-xl space-y-4 relative overflow-hidden text-white">
            <div class="absolute -right-8 -top-8 w-40 h-40 bg-purple-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex items-center justify-between border-b border-indigo-500/20 pb-3 relative z-10">
              <div class="flex items-center gap-2.5">
                <span class="text-lg">🤖</span>
                <h3 class="text-sm font-bold text-indigo-300 uppercase tracking-wider">AI Recommendation</h3>
              </div>
              <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30">
                Personalized
              </span>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 relative z-10">
              <div class="space-y-1.5">
                <p class="text-xs text-slate-300">Next Lesson For You:</p>
                <h4 class="text-lg font-extrabold text-white">{{ aiRecommendation.title }}</h4>
                <p class="text-xs text-slate-400">{{ aiRecommendation.reason }}</p>
                <div class="flex items-center gap-2 pt-1 text-xs">
                  <span class="text-slate-400">Difficulty:</span>
                  <span class="text-amber-400">⭐⭐☆☆☆</span>
                </div>
              </div>

              <Link
                :href="aiRecommendation.href"
                prefetch="hover"
                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 transition-all duration-200 hover:scale-105 active:scale-95 cursor-pointer"
              >
                Start Learning →
              </Link>
            </div>
          </div>
        </div>

        <!-- Right 1 Column: Today's Goal & My Courses Overview -->
        <div class="space-y-6">
          
          <!-- Today's Goal Card -->
          <div class="bg-white dark:bg-[#111827]/80 border border-slate-200/80 dark:border-white/5 rounded-3xl p-6 shadow-xs dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-3">
              <div class="flex items-center gap-2.5">
                <span class="text-lg">🎯</span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Today's Goal</h3>
              </div>
              <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ todayGoal.completed }} / {{ todayGoal.target }} Completed</span>
            </div>

            <div class="space-y-3">
              <p class="text-xs text-slate-600 dark:text-slate-300">Learn 2 lessons today</p>
              <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" :style="{ width: todayGoal.percentage + '%' }"></div>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 text-right">50% completed</p>
            </div>
          </div>

          <!-- 🔥 Daily Streak & XP Gamification Card -->
          <div class="relative overflow-hidden bg-gradient-to-br from-amber-500/10 via-purple-500/10 to-indigo-500/10 dark:bg-[#111827]/80 border border-amber-500/30 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-amber-500/20 pb-3">
              <div class="flex items-center gap-2">
                <span class="text-xl animate-bounce">🔥</span>
                <div>
                  <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ streakCount }} Days Active Streak!</h3>
                  <p class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold">Keep learning daily to earn XP</p>
                </div>
              </div>
              <span class="px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/30 text-[10px] font-black">
                ⚡ +50 XP Today
              </span>
            </div>

            <!-- 7-Day Dots -->
            <div class="flex items-center justify-between gap-1.5 pt-1">
              <div
                v-for="(d, i) in streakDays"
                :key="i"
                class="flex-1 flex flex-col items-center gap-1.5"
              >
                <div
                  :class="[
                    d.active
                      ? 'bg-gradient-to-tr from-amber-500 to-orange-500 text-white shadow-md shadow-orange-500/40 ring-2 ring-amber-400/40'
                      : 'bg-slate-200 dark:bg-slate-800 text-slate-400',
                    'w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold transition-all'
                  ]"
                >
                  <span v-if="d.active">🔥</span>
                  <span v-else class="text-[10px]">{{ d.day }}</span>
                </div>
                <span class="text-[9px] font-bold text-slate-400">{{ d.day }}</span>
              </div>
            </div>

            <!-- XP Level Bar -->
            <div class="pt-2 border-t border-slate-200/60 dark:border-white/5 space-y-1.5">
              <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-indigo-600 dark:text-indigo-300 flex items-center gap-1.5">
                  <span>🏆 Level 4 Scholar</span>
                </span>
                <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">{{ xpPoints }} / {{ nextLevelXp }} XP</span>
              </div>
              <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full" :style="{ width: (xpPoints / nextLevelXp * 100) + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- My Courses Overview -->
          <div class="bg-white dark:bg-[#111827]/80 border border-slate-200/80 dark:border-white/5 rounded-3xl p-6 shadow-xs dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/5 pb-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">My Courses Overview</h3>
              <Link href="/student/my-courses/enrolled" prefetch="hover" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">View All →</Link>
            </div>

            <div class="space-y-3">
              <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200/70 dark:border-white/5 flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="font-bold text-xs text-slate-900 dark:text-white truncate">📘 C Programming Basics</p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">Mr. Sophea • $25 Paid</p>
                </div>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">65%</span>
              </div>

              <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200/70 dark:border-white/5 flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <p class="font-bold text-xs text-slate-900 dark:text-white truncate">📗 Introduction to Networking</p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">Mr. Vuthy • $20 Paid</p>
                </div>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400">40%</span>
              </div>

              <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200/70 dark:border-white/5 flex items-center justify-between gap-3 opacity-90">
                <div class="min-w-0">
                  <p class="font-bold text-xs text-slate-900 dark:text-white truncate">📙 Database Systems</p>
                  <p class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">⏳ Pending Payment ($25)</p>
                </div>
                <Link href="/student/payments/pending" prefetch="hover" class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-amber-500/15 text-amber-600 dark:text-amber-300 border border-amber-500/30 hover:bg-amber-500/25 transition-colors">
                  Pay Now 🔒
                </Link>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </StudentLayout>
</template>
