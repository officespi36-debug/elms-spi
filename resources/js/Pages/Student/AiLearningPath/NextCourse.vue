<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const showToast = ref(false)
const toastMessage = ref('')

const nextCourses = ref([
  {
    id: 1,
    title: 'Advanced JavaScript & Async Programming',
    matchScore: '98% AI Match',
    reason: 'Matches your strong grasp of core syntax and expands into Closures, Promises, Async/Await, and Web APIs.',
    level: 'Advanced',
    duration: '14h 20m',
    lessonsCount: 18,
    instructor: 'Mr. Sophea Chem',
    thumbnail: 'https://images.unsplash.com/photo-1579468118864-1b9ea3c0db4a?w=600&auto=format&fit=crop&q=80',
    tags: ['ES6+', 'Async/Await', 'DOM APIs', 'Performance']
  },
  {
    id: 2,
    title: 'React.js & Modern Frontend Architecture',
    matchScore: '95% AI Match',
    reason: 'Natural continuation from JavaScript Functions into React functional components, Hooks, and State management.',
    level: 'Intermediate to Advanced',
    duration: '22h 45m',
    lessonsCount: 24,
    instructor: 'Ms. Sreyneath Seng',
    thumbnail: 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?w=600&auto=format&fit=crop&q=80',
    tags: ['React 18', 'TailwindCSS', 'Hooks', 'Vite']
  },
  {
    id: 3,
    title: 'Node.js, Express & RESTful APIs',
    matchScore: '91% AI Match',
    reason: 'Expands your web stack into backend servers, database connectivity, and authentication.',
    level: 'Intermediate',
    duration: '18h 10m',
    lessonsCount: 20,
    instructor: 'Mr. Vuthy Keo',
    thumbnail: 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop&q=80',
    tags: ['Node.js', 'Express', 'JWT Auth', 'MongoDB']
  }
])

const handleAddToPath = (course: any) => {
  toastMessage.value = `"${course.title}" added to your AI Personalized Learning Path!`
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 3500)
}
</script>

<template>
  <StudentLayout
    title="AI Next Course Recommendations"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'AI Learning', href: '/student/ai-path/recommended' },
      { label: 'Next Course Recommendations' }
    ]"
  >
    <div class="space-y-6 pb-12 relative">
      
      <!-- Toast Notification -->
      <div
        v-if="showToast"
        class="fixed top-20 right-6 z-50 p-4 rounded-2xl bg-purple-600 text-white font-bold text-xs shadow-2xl flex items-center gap-3 border border-purple-400 animate-in fade-in slide-in-from-top-4 duration-300"
      >
        <span class="text-base">✨</span>
        <span>{{ toastMessage }}</span>
      </div>

      <!-- Top Banner -->
      <div class="bg-gradient-to-r from-indigo-950 via-purple-950 to-slate-900 border border-purple-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="space-y-2.5 max-w-2xl">
          <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40 text-xs font-bold uppercase tracking-wider">
              🤖 AI CAREER & ROADMAP ENGINE
            </span>
          </div>

          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
            Recommended Next Courses
          </h1>

          <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
            Congratulations on your continuous progress! Based on your high scores in Web Development and your learning speed, AI recommends these next courses to reach full-stack software proficiency.
          </p>
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
          class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-colors"
        >
          ⚠️ 2. Weak Topics Analysis
        </Link>
        <Link
          href="/student/ai-path/next-course"
          class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap bg-purple-600 text-white shadow-md"
        >
          🎓 3. Next Course Recommendation
        </Link>
      </div>

      <!-- Courses List Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="course in nextCourses"
          :key="course.id"
          class="bg-slate-900/80 border border-slate-800 hover:border-purple-500/40 rounded-3xl overflow-hidden shadow-xl flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 group"
        >
          <div class="space-y-4">
            <!-- Thumbnail Cover with Match Badge -->
            <div class="relative aspect-video w-full overflow-hidden bg-slate-950">
              <img
                :src="course.thumbnail"
                :alt="course.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
              
              <div class="absolute top-3 right-3 px-2.5 py-1 rounded-full bg-purple-600/90 backdrop-blur-md text-white text-[10px] font-black shadow-lg">
                {{ course.matchScore }}
              </div>
            </div>

            <!-- Content Area -->
            <div class="p-5 space-y-3">
              <div class="flex items-center gap-2 text-[11px] text-slate-400">
                <span>⏱ {{ course.duration }}</span>
                <span>•</span>
                <span>📚 {{ course.lessonsCount }} Lessons</span>
                <span>•</span>
                <span class="text-purple-400 font-bold">{{ course.level }}</span>
              </div>

              <h3 class="text-base font-bold text-white group-hover:text-purple-300 transition-colors">
                {{ course.title }}
              </h3>

              <p class="text-xs text-slate-300 leading-relaxed bg-slate-950/60 p-3 rounded-2xl border border-slate-800">
                <strong class="text-purple-400">Why AI recommends this:</strong> {{ course.reason }}
              </p>

              <!-- Tags -->
              <div class="flex flex-wrap gap-1.5 pt-1">
                <span
                  v-for="tag in course.tags"
                  :key="tag"
                  class="px-2 py-0.5 rounded-lg bg-slate-800 text-[10px] font-semibold text-slate-400"
                >
                  #{{ tag }}
                </span>
              </div>
            </div>
          </div>

          <!-- Card Actions -->
          <div class="p-5 pt-0 grid grid-cols-2 gap-2.5">
            <Link
              href="/student/courses/1/overview"
              class="py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold text-center border border-slate-700 transition-colors"
            >
              View Course
            </Link>
            <button
              @click="handleAddToPath(course)"
              type="button"
              class="py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold text-center shadow-md shadow-purple-600/30 transition-all hover:scale-105 active:scale-95 cursor-pointer"
            >
              + Add to Path
            </button>
          </div>
        </div>
      </div>

    </div>
  </StudentLayout>
</template>
