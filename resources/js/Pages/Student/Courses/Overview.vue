<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps<{
  courseId?: string | number
}>()

const isEnrolled = ref(true)
const courseProgress = ref(53) // 8 of 15 completed

const course = ref({
  id: 1,
  title: 'Web Development Course',
  subtitle: 'Master Modern Full-Stack Web Development with HTML, CSS, JavaScript, and Modern Tooling',
  bannerUrl: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1200&auto=format&fit=crop&q=80',
  category: 'IT & Networking',
  level: 'Beginner to Intermediate',
  mode: 'Teacher-Led & Self-Paced',
  language: 'Khmer & English',
  duration: '18h 30m',
  totalLessons: 15,
  completedLessons: 8,
  lastLessonTitle: 'Chapter 3 - JavaScript Functions',
  lastLessonHref: '/student/my-courses/current',
  price: '$30.00',
  rating: 4.9,
  reviewsCount: 128,
  instructor: {
    name: 'Mr. Sophea Chem',
    role: 'Senior Software Engineer & SPI Faculty Instructor',
    avatar: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80',
    bio: 'Over 8 years of software architecture experience across enterprise systems and academic teaching at Saint Paul Institute.'
  },
  objectives: [
    'Understand fundamental Web architecture, HTTP protocol, and DevTools debugging',
    'Structure clean, accessible semantic web documents using modern HTML5',
    'Design responsive, mobile-first interfaces using CSS3, Flexbox, and Grid',
    'Write maintainable JavaScript functions, handle DOM events, and build interactive features'
  ],
  chapters: [
    {
      id: 1,
      title: 'Chapter 1: Introduction to Web Development',
      lessonsCount: 3,
      duration: '27 mins',
      completed: true,
      lessons: [
        { title: '1.1 Web Basics & HTTP Protocol', duration: '08:20', completed: true },
        { title: '1.2 Dev Tools & IDE Setup (VS Code)', duration: '12:40', completed: true },
        { title: '1.3 Course Roadmap Overview', duration: '06:15', completed: true }
      ]
    },
    {
      id: 2,
      title: 'Chapter 2: HTML5 Semantic Structure',
      lessonsCount: 4,
      duration: '46 mins',
      completed: true,
      lessons: [
        { title: '2.1 Semantic Tags & Page Layout', duration: '10:15', completed: true },
        { title: '2.2 Accessible Forms and Input Elements', duration: '14:30', completed: true },
        { title: '2.3 Media & Audio/Video Canvas Elements', duration: '11:20', completed: true },
        { title: '2.4 HTML5 Best Practices & Validation', duration: '09:45', completed: true }
      ]
    },
    {
      id: 3,
      title: 'Chapter 3: JavaScript Programming Basics',
      lessonsCount: 4,
      duration: '56 mins',
      completed: false,
      active: true,
      lessons: [
        { title: '3.1 What is JavaScript & Script Tag Loading', duration: '10:15', completed: true },
        { title: '3.2 JavaScript Functions & Scope (Current)', duration: '18:20', completed: false, active: true },
        { title: '3.3 Function Parameters & Default Values', duration: '15:30', completed: false },
        { title: '3.4 Return Values & Function Expressions', duration: '12:45', completed: false, locked: true }
      ]
    },
    {
      id: 4,
      title: 'Chapter 4: DOM Manipulation & Dynamic UI',
      lessonsCount: 2,
      duration: '30 mins',
      completed: false,
      locked: true,
      lessons: [
        { title: '4.1 Selecting & Traversing DOM Elements', duration: '16:00', locked: true },
        { title: '4.2 Modifying DOM Nodes & CSS Classes', duration: '14:20', locked: true }
      ]
    },
    {
      id: 5,
      title: 'Chapter 5: Event Handling & Capstone Project',
      lessonsCount: 2,
      duration: '34 mins',
      completed: false,
      locked: true,
      lessons: [
        { title: '5.1 Event Listeners & Callback Handlers', duration: '15:10', locked: true },
        { title: '5.2 Capstone Project: Interactive Application', duration: '18:40', locked: true }
      ]
    }
  ]
})

const expandedChapter = ref<number | null>(3)

const toggleChapter = (id: number) => {
  expandedChapter.value = expandedChapter.value === id ? null : id
}
</script>

<template>
  <StudentLayout
    title="Course Overview — Web Development"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'My Courses', href: '/student/my-courses/enrolled' },
      { label: 'Web Development Course' }
    ]"
  >
    <div class="space-y-6 pb-12">
      
      <!-- HERO BANNER SECTION -->
      <div class="relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-800 shadow-2xl">
        <div class="absolute inset-0 bg-cover bg-center opacity-25" :style="{ backgroundImage: `url(${course.bannerUrl})` }"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#0B0F19] via-[#0B0F19]/90 to-transparent"></div>

        <div class="relative z-10 p-6 sm:p-8 md:p-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-3.5 max-w-2xl">
            <div class="flex flex-wrap items-center gap-2">
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-500/20 text-purple-300 border border-purple-500/40 shadow-xs">
                {{ course.category }}
              </span>
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                {{ course.level }}
              </span>
              <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                {{ course.mode }}
              </span>
            </div>

            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-white tracking-tight leading-tight">
              {{ course.title }}
            </h1>

            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
              {{ course.subtitle }}
            </p>

            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-300 pt-2">
              <div class="flex items-center gap-1.5">
                <span class="text-amber-400">★</span>
                <span class="font-bold text-white">{{ course.rating }}</span>
                <span class="text-slate-400">({{ course.reviewsCount }} reviews)</span>
              </div>
              <span>•</span>
              <div class="flex items-center gap-1.5">
                <span>⏱</span>
                <span>{{ course.duration }} Total</span>
              </div>
              <span>•</span>
              <div class="flex items-center gap-1.5">
                <span>📚</span>
                <span>{{ course.totalLessons }} Lessons</span>
              </div>
            </div>
          </div>

          <!-- Action CTA Card on the Banner -->
          <div class="bg-slate-950/80 backdrop-blur-xl border border-slate-700/80 rounded-2xl p-5 shadow-2xl space-y-4 md:w-80 shrink-0">
            <div v-if="isEnrolled" class="space-y-2">
              <div class="flex items-center justify-between text-xs font-bold">
                <span class="text-slate-300">Your Progress</span>
                <span class="text-purple-400">{{ courseProgress }}%</span>
              </div>
              <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full" :style="{ width: courseProgress + '%' }"></div>
              </div>
              <p class="text-[11px] text-slate-400">
                {{ course.completedLessons }} / {{ course.totalLessons }} Lessons Completed
              </p>
            </div>

            <div class="space-y-2">
              <Link
                v-if="isEnrolled && courseProgress > 0"
                href="/student/my-courses/current"
                class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-2 hover:scale-105 active:scale-95 transition-all text-center cursor-pointer"
              >
                <span>▶ Continue Learning</span>
              </Link>
              <Link
                v-else-if="isEnrolled && courseProgress === 0"
                href="/student/my-courses/current"
                class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2 hover:scale-105 active:scale-95 transition-all text-center cursor-pointer"
              >
                <span>🚀 Start Learning</span>
              </Link>
              <button
                v-else
                @click="isEnrolled = true"
                type="button"
                class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 hover:scale-105 active:scale-95 transition-all cursor-pointer"
              >
                <span>💳 Enroll Now ({{ course.price }})</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- MAIN 2-COLUMN GRID (Left Details & Chapters, Right Instructor & Objectives) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8 COLUMNS: Learning Objectives & Course Curriculum Tree -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- Learning Objectives -->
          <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
              <span>🎯</span>
              <span>What You Will Learn in This Course</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <div
                v-for="(obj, idx) in course.objectives"
                :key="idx"
                class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800/80 flex items-start gap-3 text-xs text-slate-300 leading-relaxed"
              >
                <span class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">✓</span>
                <span>{{ obj }}</span>
              </div>
            </div>
          </div>

          <!-- Course Curriculum Accordion -->
          <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <div>
                <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                  <span>📁</span>
                  <span>Course Curriculum</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">5 Chapters • 15 Lessons • 18h 30m Total</p>
              </div>

              <span class="px-2.5 py-1 rounded-xl bg-purple-500/20 text-purple-300 text-xs font-bold border border-purple-500/30">
                53% Complete
              </span>
            </div>

            <!-- Chapter Breakdown Accordion -->
            <div class="space-y-3 pt-1">
              <div
                v-for="chap in course.chapters"
                :key="chap.id"
                class="rounded-2xl border border-slate-800/80 bg-slate-950/60 overflow-hidden"
              >
                <!-- Chapter Header -->
                <button
                  @click="toggleChapter(chap.id)"
                  type="button"
                  :class="[
                    chap.locked ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:bg-slate-800/40',
                    'w-full p-4 flex items-center justify-between text-xs transition-colors'
                  ]"
                >
                  <div class="flex items-center gap-3 text-left">
                    <span :class="[expandedChapter === chap.id ? 'rotate-90 text-purple-400' : 'text-slate-500', 'transition-transform font-mono text-sm']">›</span>
                    <div>
                      <h4 class="font-bold text-slate-200 text-xs sm:text-sm">{{ chap.title }}</h4>
                      <p class="text-[11px] text-slate-400 mt-0.5">{{ chap.lessonsCount }} Lessons • {{ chap.duration }}</p>
                    </div>
                  </div>

                  <div class="flex items-center gap-2 shrink-0">
                    <span v-if="chap.completed" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                      Completed
                    </span>
                    <span v-else-if="chap.active" class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                      In Progress
                    </span>
                    <span v-else class="text-xs text-slate-500">🔒</span>
                  </div>
                </button>

                <!-- Lessons inside Chapter -->
                <div v-show="expandedChapter === chap.id && !chap.locked" class="border-t border-slate-800/60 p-3 space-y-1.5 bg-slate-950/90">
                  <div
                    v-for="lsn in chap.lessons"
                    :key="lsn.title"
                    class="p-2.5 rounded-xl border border-slate-800/60 text-xs flex items-center justify-between text-slate-300 bg-slate-900/50"
                  >
                    <div class="flex items-center gap-2.5">
                      <span v-if="lsn.completed" class="text-emerald-400 text-xs font-bold">✓</span>
                      <span v-else-if="lsn.active" class="text-purple-400 text-xs font-bold">▶</span>
                      <span v-else-if="lsn.locked" class="text-slate-500 text-xs">🔒</span>
                      <span v-else class="text-slate-500 text-xs">○</span>
                      <span :class="[lsn.active ? 'text-purple-300 font-bold' : '']">{{ lsn.title }}</span>
                    </div>
                    <span class="text-[11px] text-slate-400 font-mono">{{ lsn.duration }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT 4 COLUMNS: Instructor Profile & Course Requirements -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- Instructor Profile Card -->
          <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
              <span>👨‍🏫</span>
              <span>Course Instructor</span>
            </h3>

            <div class="flex items-center gap-3.5">
              <img
                :src="course.instructor.avatar"
                :alt="course.instructor.name"
                class="w-12 h-12 rounded-full object-cover border border-purple-500/40 shadow-md"
              />
              <div class="min-w-0">
                <h4 class="font-bold text-white text-xs truncate">{{ course.instructor.name }}</h4>
                <p class="text-[10px] text-purple-400 font-medium leading-tight mt-0.5">{{ course.instructor.role }}</p>
              </div>
            </div>

            <p class="text-xs text-slate-300 leading-relaxed bg-slate-950/60 p-3.5 rounded-2xl border border-slate-800/80">
              {{ course.instructor.bio }}
            </p>
          </div>

          <!-- Course Features & Resources -->
          <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3 text-xs">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider">This Course Includes:</h3>
            <ul class="space-y-2 text-slate-300">
              <li class="flex items-center gap-2.5">
                <span>📹</span>
                <span>18.5 hours on-demand high-quality video</span>
              </li>
              <li class="flex items-center gap-2.5">
                <span>📄</span>
                <span>8 Downloadable resource PDF files</span>
              </li>
              <li class="flex items-center gap-2.5">
                <span>💻</span>
                <span>Interactive practice labs & code starter kits</span>
              </li>
              <li class="flex items-center gap-2.5">
                <span>🤖</span>
                <span>24/7 AI Study Assistant explanations</span>
              </li>
              <li class="flex items-center gap-2.5">
                <span>🏆</span>
                <span>Certificate of Completion with QR Verification</span>
              </li>
            </ul>
          </div>

        </div>

      </div>

    </div>
  </StudentLayout>
</template>
