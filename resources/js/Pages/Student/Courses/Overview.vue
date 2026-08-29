<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps<{
  courseId?: string | number
}>()

interface LessonItem {
  title: string
  duration: string
  completed?: boolean
  active?: boolean
  locked?: boolean
}

interface ChapterItem {
  id: number
  title: string
  lessonsCount: number
  duration: string
  completed?: boolean
  active?: boolean
  locked?: boolean
  lessons: LessonItem[]
}

interface CourseOverviewData {
  id: number
  slug: string
  title: string
  subtitle: string
  bannerUrl: string
  category: string
  level: string
  mode: string
  language: string
  duration: string
  progress: number
  status: 'in_progress' | 'not_started' | 'completed'
  totalLessons: number
  completedLessons: number
  lastLessonTitle: string
  lastLessonHref: string
  price: string
  rating: number
  reviewsCount: number
  instructor: {
    name: string
    role: string
    avatar: string
    bio: string
  }
  objectives: string[]
  chapters: ChapterItem[]
}

const allCoursesData: Record<string, CourseOverviewData> = {
  '1': {
    id: 1,
    slug: 'web-dev',
    title: 'Web Development Fundamentals',
    subtitle: 'Master Modern Full-Stack Web Development with HTML, CSS, JavaScript, and Modern Tooling',
    bannerUrl: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1200&auto=format&fit=crop&q=80',
    category: 'Web Development',
    level: 'Beginner to Intermediate',
    mode: 'Teacher-Led & Self-Paced',
    language: 'Khmer & English',
    duration: '18h 30m',
    progress: 53,
    status: 'in_progress',
    totalLessons: 30,
    completedLessons: 12,
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
          { title: '2.3 Media & Canvas Elements', duration: '11:20', completed: true },
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
          { title: '3.4 Return Values & Arrow Functions', duration: '12:45', completed: false, locked: true }
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
  },
  '2': {
    id: 2,
    slug: 'db-systems',
    title: 'Database Systems',
    subtitle: 'Comprehensive Relational Database Architecture, SQL Mastery, Indexing, and Optimization',
    bannerUrl: 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?w=1200&auto=format&fit=crop&q=80',
    category: 'Database Systems',
    level: 'Intermediate',
    mode: 'Teacher-Led & Self-Paced',
    language: 'Khmer & English',
    duration: '14h 20m',
    progress: 35,
    status: 'in_progress',
    totalLessons: 23,
    completedLessons: 8,
    lastLessonTitle: 'Chapter 2 - Relational Schema & Foreign Keys',
    lastLessonHref: '/student/my-courses/current',
    price: '$35.00',
    rating: 4.8,
    reviewsCount: 94,
    instructor: {
      name: 'Mr. Long Dararith',
      role: 'Database Specialist & SPI Faculty Lecturer',
      avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80',
      bio: 'Enterprise Database Administrator with deep expertise in MySQL, PostgreSQL, and distributed storage systems.'
    },
    objectives: [
      'Master Entity-Relationship (ER) modeling and schema design',
      'Write advanced SQL queries, multi-table joins, subqueries, and aggregates',
      'Understand Database Normalization (1NF to BCNF) and eliminate data redundancy',
      'Optimize query execution plans with indexing strategies and transaction management'
    ],
    chapters: [
      {
        id: 1,
        title: 'Chapter 1: Relational Database Principles & ER Models',
        lessonsCount: 4,
        duration: '45 mins',
        completed: true,
        lessons: [
          { title: '1.1 Introduction to DBMS & Relational Model', duration: '10:00', completed: true },
          { title: '1.2 Entities, Attributes & Relationships', duration: '12:00', completed: true },
          { title: '1.3 Drawing ER Diagrams with Crow’s Foot Notation', duration: '15:00', completed: true },
          { title: '1.4 ER to Relational Mapping', duration: '08:00', completed: true }
        ]
      },
      {
        id: 2,
        title: 'Chapter 2: SQL DDL & DML Mastery',
        lessonsCount: 5,
        duration: '55 mins',
        completed: false,
        active: true,
        lessons: [
          { title: '2.1 Relational Schema & Foreign Keys (Current)', duration: '14:00', completed: false, active: true },
          { title: '2.2 Advanced SELECT, Filters & Aggregates', duration: '12:00', completed: false },
          { title: '2.3 Multi-table INNER & OUTER JOINs', duration: '15:00', completed: false, locked: true },
          { title: '2.4 Subqueries & Common Table Expressions', duration: '14:00', completed: false, locked: true }
        ]
      },
      {
        id: 3,
        title: 'Chapter 3: Normalization & Indexing',
        lessonsCount: 3,
        duration: '40 mins',
        completed: false,
        locked: true,
        lessons: [
          { title: '3.1 Understanding Functional Dependencies', duration: '12:00', locked: true },
          { title: '3.2 1NF, 2NF, and 3NF Step-by-Step', duration: '16:00', locked: true },
          { title: '3.3 B-Tree Indexing and Query Performance', duration: '12:00', locked: true }
        ]
      }
    ]
  },
  '3': {
    id: 3,
    slug: 'python',
    title: 'Python Programming',
    subtitle: 'From Foundations to Object-Oriented Programming and Automation Scripts in Modern Python 3',
    bannerUrl: 'https://images.unsplash.com/photo-1526379095098-d400fd0bf935?w=1200&auto=format&fit=crop&q=80',
    category: 'Programming',
    level: 'Beginner',
    mode: 'Self-Paced with AI Tutor',
    language: 'Khmer & English',
    duration: '20h 00m',
    progress: 0,
    status: 'not_started',
    totalLessons: 28,
    completedLessons: 0,
    lastLessonTitle: 'Chapter 1 - Python Setup & First Script',
    lastLessonHref: '/student/my-courses/current',
    price: '$25.00',
    rating: 4.9,
    reviewsCount: 156,
    instructor: {
      name: 'Mr. Eng Thida',
      role: 'Python & AI Engineer',
      avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=120&auto=format&fit=crop&q=80',
      bio: 'AI Practitioner and Python software developer specializing in data manipulation and algorithm design.'
    },
    objectives: [
      'Set up Python 3 virtual environments and run scripts smoothly',
      'Understand variables, dynamic typing, control flow, and iterative loops',
      'Master built-in data structures (Lists, Tuples, Dictionaries, Sets)',
      'Build modular Object-Oriented programs and handle file I/O operations'
    ],
    chapters: [
      {
        id: 1,
        title: 'Chapter 1: Python Environment & Syntax Basics',
        lessonsCount: 4,
        duration: '40 mins',
        completed: false,
        active: true,
        lessons: [
          { title: '1.1 Installing Python & VS Code Extension', duration: '08:00', active: true },
          { title: '1.2 Variables, Data Types & Print Formatting', duration: '12:00' },
          { title: '1.3 Arithmetic and Comparison Operators', duration: '10:00' },
          { title: '1.4 User Input and Type Casting', duration: '10:00' }
        ]
      },
      {
        id: 2,
        title: 'Chapter 2: Data Structures & Collections',
        lessonsCount: 4,
        duration: '50 mins',
        locked: true,
        lessons: [
          { title: '2.1 Python Lists & Common Operations', duration: '12:00', locked: true },
          { title: '2.2 Dictionaries & Key-Value Lookups', duration: '15:00', locked: true },
          { title: '2.3 Tuples and Set Uniqueness', duration: '11:00', locked: true }
        ]
      }
    ]
  },
  '4': {
    id: 4,
    slug: 'ui-ux',
    title: 'UI/UX Design Basics',
    subtitle: 'User Research, Wireframing, Figma Prototyping, and Design System Foundations',
    bannerUrl: 'https://images.unsplash.com/photo-1581291518633-83b4ebd1d83e?w=1200&auto=format&fit=crop&q=80',
    category: 'UI/UX Design',
    level: 'Beginner to Intermediate',
    mode: 'Teacher-Led & Studio Lab',
    language: 'Khmer & English',
    duration: '15h 30m',
    progress: 100,
    status: 'completed',
    totalLessons: 20,
    completedLessons: 20,
    lastLessonTitle: 'Chapter 4 - Final Design System Showcase',
    lastLessonHref: '/student/certificates/my-certificates',
    price: '$30.00',
    rating: 5.0,
    reviewsCount: 88,
    instructor: {
      name: 'Ms. Nhean Sreymom',
      role: 'Lead UI/UX Designer & Design Educator',
      avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=120&auto=format&fit=crop&q=80',
      bio: 'Product designer with extensive experience crafting user-centric digital products and mobile design systems.'
    },
    objectives: [
      'Master Design Thinking methodologies and user persona synthesis',
      'Construct high-fidelity interactive prototypes in Figma using Auto-Layout',
      'Establish scalable design token systems for typography, spacing, and colors',
      'Conduct usability testing and iterate based on real feedback'
    ],
    chapters: [
      {
        id: 1,
        title: 'Chapter 1: UI/UX Principles & User Research',
        lessonsCount: 4,
        duration: '45 mins',
        completed: true,
        lessons: [
          { title: '1.1 Introduction to User Experience Design', duration: '10:00', completed: true },
          { title: '1.2 Conducting User Interviews & Surveys', duration: '15:00', completed: true },
          { title: '1.3 Creating Personas and Empathy Maps', duration: '12:00', completed: true },
          { title: '1.4 Information Architecture & User Flows', duration: '08:00', completed: true }
        ]
      },
      {
        id: 2,
        title: 'Chapter 2: Figma & Wireframing Systems',
        lessonsCount: 5,
        duration: '50 mins',
        completed: true,
        lessons: [
          { title: '2.1 Figma Workspace & Vector Basics', duration: '10:00', completed: true },
          { title: '2.2 Low-Fidelity Wireframing', duration: '12:00', completed: true },
          { title: '2.3 Components and Variants in Figma', duration: '14:00', completed: true },
          { title: '2.4 Auto-Layout & Responsive Constraints', duration: '14:00', completed: true }
        ]
      },
      {
        id: 3,
        title: 'Chapter 3: Color Theory & Typography Tokens',
        lessonsCount: 5,
        duration: '45 mins',
        completed: true,
        lessons: [
          { title: '3.1 Visual Hierarchy & Contrast Guidelines', duration: '10:00', completed: true },
          { title: '3.2 Choosing Typefaces & Spacing Systems', duration: '12:00', completed: true },
          { title: '3.3 Dark Mode & Accessible Color Palettes', duration: '13:00', completed: true }
        ]
      },
      {
        id: 4,
        title: 'Chapter 4: Final Showcase & Usability Testing',
        lessonsCount: 6,
        duration: '50 mins',
        completed: true,
        lessons: [
          { title: '4.1 Prototyping Micro-Interactions in Figma', duration: '12:00', completed: true },
          { title: '4.2 Usability Testing with End-Users', duration: '15:00', completed: true },
          { title: '4.3 Design System Documentation & Handoff', duration: '13:00', completed: true },
          { title: '4.4 Final Capstone Project Review', duration: '10:00', completed: true }
        ]
      }
    ]
  }
}

const courseKey = computed(() => {
  const cid = String(props.courseId || '1')
  return allCoursesData[cid] ? cid : '1'
})

const course = computed(() => allCoursesData[courseKey.value])

const expandedChapter = ref<number | null>(course.value.chapters.find(c => c.active)?.id || 1)

const toggleChapter = (id: number) => {
  expandedChapter.value = expandedChapter.value === id ? null : id
}
</script>

<template>
  <StudentLayout
    :title="`Course Overview — ${course.title}`"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'My Courses', href: '/student/my-courses/enrolled' },
      { label: course.title }
    ]"
  >
    <div class="space-y-6 pb-16">
      
      <!-- HERO BANNER SECTION -->
      <div class="relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-800 shadow-2xl">
        <div class="absolute inset-0 bg-cover bg-center opacity-20" :style="{ backgroundImage: `url(${course.bannerUrl})` }"></div>
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
          <div class="bg-slate-950/90 backdrop-blur-xl border border-slate-700/80 rounded-2xl p-5 shadow-2xl space-y-4 md:w-80 shrink-0">
            <div class="space-y-2">
              <div class="flex items-center justify-between text-xs font-bold">
                <span class="text-slate-300">Your Progress</span>
                <span :class="course.progress === 100 ? 'text-emerald-400' : 'text-purple-400'">{{ course.progress }}%</span>
              </div>
              <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                <div
                  :class="course.progress === 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-purple-500 to-indigo-500'"
                  class="h-full rounded-full transition-all duration-500"
                  :style="{ width: course.progress + '%' }"
                ></div>
              </div>
              <p class="text-[11px] text-slate-400">
                {{ course.completedLessons }} / {{ course.totalLessons }} Lessons Completed
              </p>
            </div>

            <div class="space-y-2">
              <Link
                v-if="course.status === 'in_progress'"
                :href="course.lastLessonHref"
                class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer"
              >
                <span>▶ Continue Learning</span>
              </Link>

              <Link
                v-else-if="course.status === 'not_started'"
                :href="course.lastLessonHref"
                class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer"
              >
                <span>🚀 Start Learning</span>
              </Link>

              <div v-else class="space-y-2">
                <Link
                  href="/student/certificates/my-certificates"
                  class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 hover:scale-[1.02] active:scale-95 transition-all text-center cursor-pointer"
                >
                  <span>🏆 View Certificate</span>
                </Link>
                <Link
                  href="/student/my-courses/current"
                  class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 font-semibold text-xs border border-slate-800 flex items-center justify-center gap-2 text-center cursor-pointer"
                >
                  <span>👁 Review Course Materials</span>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MAIN 2-COLUMN GRID (Left Details & Chapters, Right Instructor & Objectives) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8 COLUMNS: Learning Objectives & Course Curriculum Tree -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- Learning Objectives -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
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
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <div>
                <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                  <span>📁</span>
                  <span>Course Curriculum</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">{{ course.chapters.length }} Chapters • {{ course.totalLessons }} Lessons • {{ course.duration }} Total</p>
              </div>

              <span
                :class="[
                  course.progress === 100 ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-purple-500/20 text-purple-300 border-purple-500/30',
                  'px-2.5 py-1 rounded-xl text-xs font-bold border'
                ]"
              >
                {{ course.progress }}% Complete
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
                    <span v-else-if="chap.locked" class="text-xs text-slate-500">🔒</span>
                    <span v-else class="text-xs text-blue-400 font-semibold">Available</span>
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
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
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
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3 text-xs">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider">This Course Includes:</h3>
            <ul class="space-y-2.5 text-slate-300">
              <li class="flex items-center gap-2.5">
                <span>📹</span>
                <span>{{ course.duration }} on-demand high-quality video</span>
              </li>
              <li class="flex items-center gap-2.5">
                <span>📄</span>
                <span>Downloadable syllabus & slides PDF</span>
              </li>
              <li class="flex items-center gap-2.5">
                <span>💻</span>
                <span>Interactive practice quizzes & code drills</span>
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
