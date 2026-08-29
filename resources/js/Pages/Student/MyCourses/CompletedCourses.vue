<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const userRating = ref(5)
const reviewText = ref('')
const reviewSubmitted = ref(false)

const submitReview = () => {
  if (reviewText.value.trim()) {
    reviewSubmitted.value = true
  }
}

const completedCourses = ref([
  {
    id: 1,
    title: 'Web Development Basics',
    instructor: 'Ms. Dara',
    mode: 'Teacher-Led',
    major: 'IT & Networking',
    grade: 'A (92%)',
    timeSpent: '45h 30m',
    quizAvg: '88%',
    completedDate: '25 May 2025',
    preTestScore: 55,
    postTestScore: 92,
    improvement: '+37%',
    certCode: 'ELMS-2025-000451',
    certLink: '/student/certificates',
    rating: 5,
    modules: [
      { name: 'Module 1: HTML5 & Semantics', progress: 100, grade: 'A', duration: '2 weeks' },
      { name: 'Module 2: CSS3 & Flexbox Layouts', progress: 100, grade: 'A', duration: '3 weeks' },
      { name: 'Module 3: JavaScript ES6+ Fundamentals', progress: 100, grade: 'B+', duration: '2 weeks' },
      { name: 'Module 4: Responsive Web Design Projects', progress: 100, grade: 'A', duration: '2 weeks' },
      { name: 'Final Assessment & Portfolio Build', progress: 100, grade: 'Score: 92%', duration: 'Final Test' }
    ]
  }
])
</script>

<template>
  <StudentLayout title="Completed Courses">
    <div class="space-y-6">
      
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-white flex items-center gap-2.5">
            <span>✅</span>
            <span>COMPLETED COURSES</span>
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            មុខវិជ្ជាដែលរៀនចប់សព្វគ្រប់ — មើលពិន្ទុ វិញ្ញាបនបត្រ និងការវាយតម្លៃ
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Link
            href="/student/certificates"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/30 transition-all flex items-center gap-2"
          >
            <span>🏅 មើលវិញ្ញាបនបត្រទាំងអស់</span>
          </Link>
        </div>
      </div>

      <!-- COMPLETED COURSE CARD -->
      <div
        v-for="course in completedCourses"
        :key="course.id"
        class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-6 shadow-2xl space-y-6"
      >
        <!-- Card Title Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-700/60 pb-4">
          <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
              <span>👨‍🏫 {{ course.instructor }}</span>
              <span>•</span>
              <span class="text-blue-400">🎥 {{ course.mode }}</span>
              <span>•</span>
              <span>🏫 {{ course.major }}</span>
            </div>
            <h2 class="text-xl font-black text-white mt-1 flex items-center gap-2">
              <span>✅</span>
              <span>{{ course.title }}</span>
            </h2>
          </div>

          <span class="px-3.5 py-1.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-xs font-extrabold shadow-md shrink-0">
            🏅 Certified
          </span>
        </div>

        <!-- Metric Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
          <div class="p-4 bg-slate-900/80 rounded-2xl border border-slate-700/60 text-center">
            <p class="text-[10px] text-slate-400 font-semibold uppercase">🎓 Grade</p>
            <p class="text-xl font-extrabold text-emerald-400 mt-1">{{ course.grade }}</p>
          </div>

          <div class="p-4 bg-slate-900/80 rounded-2xl border border-slate-700/60 text-center">
            <p class="text-[10px] text-slate-400 font-semibold uppercase">⏱ Time Spent</p>
            <p class="text-xl font-extrabold text-cyan-300 mt-1">{{ course.timeSpent }}</p>
          </div>

          <div class="p-4 bg-slate-900/80 rounded-2xl border border-slate-700/60 text-center">
            <p class="text-[10px] text-slate-400 font-semibold uppercase">📝 Quiz Avg</p>
            <p class="text-xl font-extrabold text-indigo-300 mt-1">{{ course.quizAvg }}</p>
          </div>

          <div class="p-4 bg-slate-900/80 rounded-2xl border border-slate-700/60 text-center">
            <p class="text-[10px] text-slate-400 font-semibold uppercase">📅 Completed</p>
            <p class="text-sm font-extrabold text-slate-200 mt-2">{{ course.completedDate }}</p>
          </div>
        </div>

        <!-- Performance Comparison (Pre-Test vs Post-Test) -->
        <div class="p-4 bg-slate-900/80 rounded-2xl border border-slate-700/60 space-y-2">
          <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider">📊 MY PERFORMANCE</h4>
          <div class="flex flex-wrap items-center gap-3 text-xs font-bold">
            <span class="text-blue-400">🟦 Pre-Test: {{ course.preTestScore }}%</span>
            <span class="text-slate-500">→</span>
            <span class="text-rose-400">🟥 Post-Test: {{ course.postTestScore }}%</span>
            <span class="text-slate-500">→</span>
            <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
              Improvement: ↑ {{ course.improvement }}
            </span>
          </div>
        </div>

        <!-- Certificate Info & Downloads -->
        <div class="p-4 bg-gradient-to-r from-purple-900/30 via-slate-900 to-indigo-900/30 rounded-2xl border border-purple-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase">🏅 Certificate Code</p>
            <p class="text-sm font-mono font-bold text-purple-300">{{ course.certCode }}</p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <Link :href="course.certLink" class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md">
              📥 Download PDF
            </Link>
            <button class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700">
              🔗 Share LinkedIn
            </button>
            <Link href="/student/certificates?tab=verify" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700">
              🔍 Verify QR
            </Link>
          </div>
        </div>

        <!-- Rate & Review Course Form -->
        <div class="p-4 bg-slate-900/60 rounded-2xl border border-slate-700/60 space-y-3">
          <h4 class="text-xs font-bold text-white uppercase tracking-wider">⭐ RATE THIS COURSE</h4>

          <div class="flex items-center gap-2">
            <div class="flex items-center text-amber-400 text-lg cursor-pointer">
              <span v-for="i in 5" :key="i" @click="userRating = i">
                {{ i <= userRating ? '⭐' : '☆' }}
              </span>
            </div>
            <span class="text-xs font-bold text-slate-300">{{ userRating }}/5</span>
          </div>

          <div v-if="!reviewSubmitted" class="space-y-2">
            <textarea
              v-model="reviewText"
              rows="2"
              placeholder="Write your honest review about the course and teacher..."
              class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
            ></textarea>
            <button @click="submitReview" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs">
              Submit Review
            </button>
          </div>
          <div v-else class="p-3 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-xl text-xs font-bold">
            ✓ Thank you for your review!
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex flex-wrap items-center gap-3 pt-2">
          <Link href="/student/my-courses/current" prefetch="hover" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700">
            📖 Review Content Again
          </Link>
          <Link href="/student/discussions?tab=ask" prefetch="hover" class="px-4 py-2.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 font-bold text-xs">
            👨‍🏫 Thank Teacher
          </Link>
        </div>

        <!-- LEARNING JOURNEY SUMMARY PER MODULE -->
        <div class="border-t border-slate-700/60 pt-4 space-y-3">
          <h4 class="text-xs font-bold text-white uppercase tracking-wider">📊 LEARNING JOURNEY SUMMARY</h4>

          <div class="space-y-2 text-xs">
            <div v-for="mod in course.modules" :key="mod.name" class="p-3 bg-slate-900/60 rounded-xl border border-slate-700/40 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <span class="font-bold text-slate-200">{{ mod.name }}</span>
              <div class="flex items-center gap-3 text-[11px]">
                <div class="w-24 h-2 rounded-full bg-slate-700 overflow-hidden">
                  <div class="h-full bg-emerald-500 rounded-full w-full"></div>
                </div>
                <span class="font-bold text-emerald-400">100%</span>
                <span class="px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold">{{ mod.grade }}</span>
                <span class="text-slate-400">{{ mod.duration }}</span>
              </div>
            </div>
          </div>

          <p class="text-xs text-slate-400 pt-2 text-right">
            Total Learning Time: <span class="font-bold text-white">45h 30m</span>  ·  Total Quiz Score: <span class="font-bold text-emerald-400">88%</span>
          </p>
        </div>
      </div>

    </div>
  </StudentLayout>
</template>
