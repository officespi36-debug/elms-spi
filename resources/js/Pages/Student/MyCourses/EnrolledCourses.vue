<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { isCourseCachedOffline, saveCourseForOffline, getAllOfflineCourses } from '@/offline/sync'

const props = defineProps<{
  enrollments?: any[]
}>()

const page = usePage<any>()

const viewMode = ref<'grid' | 'list'>('grid')
const searchQuery = ref('')
const selectedMajor = ref('all')
const selectedStatus = ref('all')
const selectedLearningMode = ref('all')
const sortBy = ref('recent')
const cachedCourseIds = ref<number[]>([])

const refreshCachedCourses = async () => {
  const all = await getAllOfflineCourses()
  cachedCourseIds.value = all.map(c => Number(c.id))
}

const handleSaveCourseOffline = async (course: any) => {
  if (cachedCourseIds.value.includes(course.id)) return
  await saveCourseForOffline(course)
  await refreshCachedCourses()
}

onMounted(() => {
  refreshCachedCourses()
})

// Mock sample courses matching prompt specs if prop enrollments is empty or incomplete
const sampleCourses = ref([
  {
    id: 1,
    title: 'C Programming Basics',
    teacher: 'Mr. Sophea',
    teacherAvatar: 'https://ui-avatars.com/api/?name=Sophea&background=3b82f6&color=fff',
    major: 'IT & Networking',
    mode: 'Teacher-Led',
    price: '$25',
    progress: 65,
    status: 'paid',
    isCompleted: false,
    thumbnail: 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?w=500&auto=format&fit=crop&q=75',
    lastAccessed: 'Today, 09:30 AM',
    href: '/student/my-courses/current'
  },
  {
    id: 2,
    title: 'Intro to Networking',
    teacher: 'Mr. Vuthy',
    teacherAvatar: 'https://ui-avatars.com/api/?name=Vuthy&background=10b981&color=fff',
    major: 'IT & Networking',
    mode: 'Self-Study',
    price: '$20',
    progress: 40,
    status: 'paid',
    isCompleted: false,
    thumbnail: 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=500&auto=format&fit=crop&q=75',
    lastAccessed: 'Yesterday',
    href: '/student/my-courses/current'
  },
  {
    id: 3,
    title: 'Web Development',
    teacher: 'Ms. Dara',
    teacherAvatar: 'https://ui-avatars.com/api/?name=Dara&background=8b5cf6&color=fff',
    major: 'IT & Networking',
    mode: 'Teacher-Led',
    price: '$30',
    progress: 100,
    status: 'paid',
    isCompleted: true,
    thumbnail: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=500&auto=format&fit=crop&q=75',
    lastAccessed: '3 days ago',
    href: '/student/my-courses/completed'
  },
  {
    id: 4,
    title: 'Database Systems',
    teacher: 'Mr. Sophea',
    teacherAvatar: 'https://ui-avatars.com/api/?name=Sophea&background=f59e0b&color=fff',
    major: 'IT & Networking',
    mode: 'Self-Study',
    price: '$25',
    progress: 30,
    status: 'pending',
    isCompleted: false,
    thumbnail: 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?w=500&auto=format&fit=crop&q=75',
    lastAccessed: '1 week ago',
    href: '/student/payments'
  },
  {
    id: 5,
    title: 'English Grammar Basics',
    teacher: 'Ms. Srey',
    teacherAvatar: 'https://ui-avatars.com/api/?name=Srey&background=ec4899&color=fff',
    major: 'English Literature',
    mode: 'Self-Study',
    price: 'FREE',
    progress: 0,
    status: 'free',
    isCompleted: false,
    thumbnail: 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=500&auto=format&fit=crop&q=75',
    lastAccessed: 'Not started',
    href: '/student/my-courses/current'
  }
])

const coursesList = computed(() => {
  if (props.enrollments && props.enrollments.length > 0) {
    return props.enrollments.map((item: any) => ({
      id: item.id,
      title: item.course?.title || 'Untitled Course',
      teacher: item.course?.teacher?.name || 'Instructor',
      teacherAvatar: item.course?.teacher?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(item.course?.teacher?.name || 'Teacher')}`,
      major: item.course?.major?.name || 'General Study',
      mode: item.course?.learning_mode || 'Teacher-Led',
      price: item.course?.price ? `$${item.course.price}` : 'FREE',
      progress: item.progress || 0,
      status: item.status === 'completed' ? 'paid' : item.payment_status || 'paid',
      isCompleted: item.status === 'completed',
      thumbnail: item.course?.thumbnail || 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600&auto=format&fit=crop&q=80',
      lastAccessed: 'Recently',
      href: item.status === 'completed' ? '/student/my-courses/completed' : '/student/my-courses/current'
    }))
  }
  return sampleCourses.value
})

// Filtered Courses
const filteredCourses = computed(() => {
  return coursesList.value.filter(course => {
    const matchesSearch = course.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          course.teacher.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesMajor = selectedMajor.value === 'all' || course.major === selectedMajor.value
    const matchesStatus = selectedStatus.value === 'all' ||
                          (selectedStatus.value === 'active' && course.status === 'paid' && !course.isCompleted) ||
                          (selectedStatus.value === 'completed' && course.isCompleted) ||
                          (selectedStatus.value === 'pending' && course.status === 'pending') ||
                          (selectedStatus.value === 'free' && course.status === 'free')
    const matchesMode = selectedLearningMode.value === 'all' || course.mode === selectedLearningMode.value
    return matchesSearch && matchesMajor && matchesStatus && matchesMode
  }).sort((a, b) => {
    if (sortBy.value === 'progress') return b.progress - a.progress
    if (sortBy.value === 'title') return a.title.localeCompare(b.title)
    return b.id - a.id
  })
})

const statsSummary = computed(() => {
  const total = coursesList.value.length
  const active = coursesList.value.filter(c => c.status === 'paid' && !c.isCompleted).length
  const completed = coursesList.value.filter(c => c.isCompleted).length
  const locked = coursesList.value.filter(c => c.status === 'pending').length
  return { total, active, completed, locked }
})
</script>

<template>
  <StudentLayout title="My Enrolled Courses">
    <div class="space-y-6">
      
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-white flex items-center gap-2.5">
            <span>📋</span>
            <span>MY ENROLLED COURSES</span>
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            មុខវិជ្ជាដែលខ្ញុំបានចុះឈ្មោះរៀន — គ្រប់គ្រងការរៀន វឌ្ឍនភាព និងការបង់ប្រាក់
          </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <Link
            href="/student/my-courses/browse"
            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition-all flex items-center gap-2"
          >
            <span>🔍 ស្វែងរក Course ថ្មី</span>
          </Link>
        </div>
      </div>

      <!-- Stats Overview Banner Cards (Matching Prompt Spec) -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 flex flex-col justify-between shadow-lg">
          <div class="flex items-center justify-between text-slate-400">
            <span class="text-xs font-semibold">📚 Total Courses</span>
            <span class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400">📋</span>
          </div>
          <div class="mt-3">
            <p class="text-2xl font-extrabold text-white">{{ statsSummary.total }}</p>
            <p class="text-[10px] text-slate-400">Enrolled items</p>
          </div>
        </div>

        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 flex flex-col justify-between shadow-lg">
          <div class="flex items-center justify-between text-slate-400">
            <span class="text-xs font-semibold">📖 Active Learning</span>
            <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400">🔄</span>
          </div>
          <div class="mt-3">
            <p class="text-2xl font-extrabold text-emerald-400">{{ statsSummary.active }}</p>
            <p class="text-[10px] text-slate-400">In progress</p>
          </div>
        </div>

        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 flex flex-col justify-between shadow-lg">
          <div class="flex items-center justify-between text-slate-400">
            <span class="text-xs font-semibold">✅ Completed</span>
            <span class="p-1.5 rounded-lg bg-purple-500/10 text-purple-400">🏆</span>
          </div>
          <div class="mt-3">
            <p class="text-2xl font-extrabold text-purple-400">{{ statsSummary.completed }}</p>
            <p class="text-[10px] text-slate-400">Certified done</p>
          </div>
        </div>

        <div class="bg-slate-800/80 border border-slate-700/80 rounded-2xl p-4 flex flex-col justify-between shadow-lg">
          <div class="flex items-center justify-between text-slate-400">
            <span class="text-xs font-semibold">🔒 Unpaid / Pending</span>
            <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400">💳</span>
          </div>
          <div class="mt-3">
            <p class="text-2xl font-extrabold text-amber-400">{{ statsSummary.locked }}</p>
            <p class="text-[10px] text-slate-400">Requires ABA payment</p>
          </div>
        </div>
      </div>

      <!-- Filters, Search & View Switcher Bar -->
      <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-4 shadow-xl space-y-3">
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
          
          <!-- Search Bar -->
          <div class="relative flex-1 min-w-0">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search course title or instructor..."
              class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl pl-10 pr-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all"
            />
          </div>

          <!-- Filters Dropdowns -->
          <div class="flex flex-wrap items-center gap-2">
            <!-- Major Filter -->
            <select
              v-model="selectedMajor"
              class="bg-slate-900/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
            >
              <option value="all">Major: All</option>
              <option value="IT & Networking">IT & Networking</option>
              <option value="English Literature">English Literature</option>
            </select>

            <!-- Status Filter -->
            <select
              v-model="selectedStatus"
              class="bg-slate-900/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
            >
              <option value="all">Status: All</option>
              <option value="active">Learning Active</option>
              <option value="completed">Completed</option>
              <option value="pending">Pending Payment</option>
              <option value="free">Free Access</option>
            </select>

            <!-- Mode Filter -->
            <select
              v-model="selectedLearningMode"
              class="bg-slate-900/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
            >
              <option value="all">Mode: All</option>
              <option value="Teacher-Led">🎥 Teacher-Led</option>
              <option value="Self-Study">💻 Self-Study</option>
            </select>

            <!-- Sort By -->
            <select
              v-model="sortBy"
              class="bg-slate-900/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
            >
              <option value="recent">Sort: Recent</option>
              <option value="progress">Sort: Progress</option>
              <option value="title">Sort: A-Z</option>
            </select>

            <!-- Grid / List View Toggle Switcher (Matching Prompt Specs) -->
            <div class="flex items-center bg-slate-900/90 border border-slate-700/80 rounded-xl p-1 shrink-0 ml-auto sm:ml-0">
              <button
                @click="viewMode = 'grid'"
                :class="[viewMode === 'grid' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white', 'px-3 py-1 rounded-lg text-xs transition-all flex items-center gap-1.5']"
              >
                <span>🔲</span>
                <span>Grid</span>
              </button>
              <button
                @click="viewMode = 'list'"
                :class="[viewMode === 'list' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white', 'px-3 py-1 rounded-lg text-xs transition-all flex items-center gap-1.5']"
              >
                <span>📋</span>
                <span>List</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- COURSE CARDS (GRID VIEW) -->
      <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="course in filteredCourses"
          :key="course.id"
          class="bg-slate-800/90 border border-slate-700/80 rounded-3xl overflow-hidden shadow-xl hover:border-slate-600 transition-all flex flex-col group"
        >
            <!-- Thumbnail & Progress Overlay -->
            <div class="relative h-44 overflow-hidden bg-slate-900">
              <img
                :src="course.thumbnail"
                :alt="course.title"
                loading="lazy"
                decoding="async"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent"></div>

              <!-- Lock Badge overlay for Pending Payment -->
              <div v-if="course.status === 'pending'" class="absolute inset-0 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center">
                <span class="px-3 py-1.5 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-bold flex items-center gap-1.5 shadow-lg">
                  🔒 Payment Pending
                </span>
              </div>

              <!-- Mode Badge -->
              <div class="absolute top-3 left-3">
                <span :class="[course.mode === 'Teacher-Led' ? 'bg-blue-600/90' : 'bg-purple-600/90', 'px-2.5 py-1 rounded-full text-[10px] font-bold text-white shadow-md backdrop-blur-md flex items-center gap-1']">
                  <span>{{ course.mode === 'Teacher-Led' ? '🎥' : '💻' }}</span>
                  <span>{{ course.mode }}</span>
                </span>
              </div>

              <!-- Price & Status Badge -->
              <div class="absolute top-3 right-3 flex items-center gap-1.5">
                <span class="px-2.5 py-1 rounded-full bg-slate-900/80 text-white font-bold text-[10px] border border-slate-700/80 shadow-md">
                  {{ course.price }}
                </span>
              </div>

              <!-- Progress Bar Overlay Bottom -->
              <div class="absolute bottom-3 left-3 right-3 space-y-1">
                <div class="flex items-center justify-between text-[11px] font-bold">
                  <span class="text-white drop-shadow">Progress</span>
                  <span :class="course.isCompleted ? 'text-emerald-400' : 'text-indigo-400'" class="drop-shadow">{{ course.progress }}%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-900/80 backdrop-blur-xs overflow-hidden border border-slate-700/60">
                  <div
                    :class="course.isCompleted ? 'bg-emerald-500' : 'bg-gradient-to-r from-blue-500 to-indigo-500'"
                    class="h-full rounded-full transition-all duration-500"
                    :style="{ width: course.progress + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- Course Body Info -->
            <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
              <div class="space-y-2">
                <div class="flex items-center justify-between text-[10px] text-slate-400 font-medium">
                  <span>🏫 {{ course.major }}</span>
                  <span>{{ course.lastAccessed }}</span>
                </div>

                <h3 class="text-base font-bold text-white group-hover:text-indigo-300 transition-colors line-clamp-1">
                  {{ course.title }}
                </h3>

                <!-- Teacher Info -->
                <div class="flex items-center gap-2.5 pt-1">
                  <img :src="course.teacherAvatar" :alt="course.teacher" loading="lazy" decoding="async" class="w-6 h-6 rounded-full object-cover border border-slate-700" />
                  <span class="text-xs text-slate-300 font-semibold">👨‍🏫 {{ course.teacher }}</span>
                </div>
              </div>

              <!-- Status Tag -->
              <div class="pt-2 border-t border-slate-700/60 flex items-center justify-between text-xs">
                <div v-if="course.isCompleted" class="flex items-center gap-1.5 text-emerald-400 font-bold text-xs">
                  <span>✅ Paid</span>
                  <span>•</span>
                  <span>🏆 Completed</span>
                </div>
                <div v-else-if="course.status === 'paid'" class="flex items-center gap-1.5 text-emerald-400 font-bold text-xs">
                  <span>✅ Paid</span>
                </div>
                <div v-else-if="course.status === 'pending'" class="flex items-center gap-1.5 text-amber-400 font-bold text-xs">
                  <span>⏳ Payment Pending</span>
                </div>
                <div v-else class="flex items-center gap-1.5 text-cyan-400 font-bold text-xs">
                  <span>🎁 Free Access</span>
                </div>
              </div>

              <!-- Action Buttons: Learn & Offline Save -->
              <div class="pt-2 flex items-center gap-2">
                <Link
                  v-if="course.isCompleted"
                  href="/student/my-courses/completed"
                  prefetch="hover"
                  class="flex-1 py-2.5 rounded-xl bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 border border-purple-500/30 font-bold text-xs transition-all text-center block"
                >
                  🏅 View Certificate
                </Link>
                <Link
                  v-else-if="course.status === 'pending'"
                  href="/student/payments"
                  prefetch="hover"
                  class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-bold text-xs transition-all shadow-md text-center block"
                >
                  💳 Pay Now via ABA
                </Link>
                <Link
                  v-else-if="course.progress === 0"
                  :href="`/student/learn/${course.id}`"
                  prefetch="hover"
                  class="flex-1 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs transition-all shadow-md text-center block"
                >
                  ▶ Start Learning
                </Link>
                <Link
                  v-else
                  :href="`/student/learn/${course.id}`"
                  prefetch="hover"
                  class="flex-1 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs transition-all shadow-md shadow-indigo-600/30 text-center block"
                >
                  ▶ Continue Learning
                </Link>

                <!-- Offline Cache Indicator / Quick Save -->
                <button
                  type="button"
                  @click.prevent="handleSaveCourseOffline(course)"
                  :class="[
                    'h-9 px-2.5 rounded-xl border text-xs font-bold transition-all flex items-center justify-center shrink-0 select-none cursor-pointer',
                    cachedCourseIds.includes(course.id)
                      ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'
                      : 'bg-slate-900/80 hover:bg-slate-700 text-slate-400 hover:text-white border-slate-700'
                  ]"
                  :title="cachedCourseIds.includes(course.id) ? 'វគ្គសិក្សានេះត្រូវបាន Save ក្នុងម៉ាស៊ីនរួចរាល់សម្រាប់រៀន Offline' : 'Save ទុកក្នុងម៉ាស៊ីនសម្រាប់រៀនក្រៅបណ្តាញ (Offline)'"
                >
                  <svg v-if="cachedCourseIds.includes(course.id)" class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- COURSE TABLE (LIST VIEW - Matching Option 2 Prompt Spec) -->
        <div v-else class="bg-slate-800/90 border border-slate-700/80 rounded-3xl overflow-hidden shadow-2xl">
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="bg-slate-900/90 border-b border-slate-700 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                  <th class="p-4">Course</th>
                  <th class="p-4">Instructor</th>
                  <th class="p-4">Major</th>
                  <th class="p-4">Progress</th>
                  <th class="p-4">Status</th>
                  <th class="p-4 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-700/60">
                <tr
                  v-for="course in filteredCourses"
                  :key="course.id"
                  class="hover:bg-slate-700/40 transition-colors group"
                >
                  <td class="p-4">
                    <div class="flex items-center gap-3">
                      <img :src="course.thumbnail" :alt="course.title" loading="lazy" decoding="async" class="w-12 h-12 rounded-xl object-cover" />
                      <div>
                        <h4 class="font-bold text-white group-hover:text-indigo-300 transition-colors line-clamp-1">{{ course.title }}</h4>
                        <p class="text-[10px] text-slate-400">{{ course.mode }} • {{ course.price }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="p-4 text-slate-300 font-medium">{{ course.teacher }}</td>
                  <td class="p-4 text-slate-300">{{ course.major }}</td>
                  <td class="p-4">
                    <div class="space-y-1 w-28">
                      <span class="text-[10px] font-bold text-indigo-400">{{ course.progress }}%</span>
                      <div class="w-full h-1.5 rounded-full bg-slate-700 overflow-hidden">
                        <div class="h-full bg-indigo-500 rounded-full" :style="{ width: course.progress + '%' }"></div>
                      </div>
                    </div>
                  </td>
                  <td class="p-4">
                    <span v-if="course.isCompleted" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                      ✅ Completed
                    </span>
                    <span v-else-if="course.status === 'paid'" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                      📖 Learning
                    </span>
                    <span v-else-if="course.status === 'pending'" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                      🔒 Locked
                    </span>
                    <span v-else class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                      🎁 Free
                    </span>
                  </td>
                  <td class="p-4 text-right">
                    <Link
                      v-if="course.isCompleted"
                      href="/student/my-courses/completed"
                      prefetch="hover"
                      class="px-3 py-1.5 rounded-lg bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 font-bold text-xs inline-block"
                    >
                      🏅 Certificate
                    </Link>
                    <Link
                      v-else-if="course.status === 'pending'"
                      href="/student/payments"
                      prefetch="hover"
                      class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs inline-block"
                    >
                      💳 Pay
                    </Link>
                    <Link
                      v-else
                      :href="`/student/learn/${course.id}`"
                      prefetch="hover"
                      class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs inline-block shadow-md"
                    >
                      ▶ Learn
                    </Link>
                  </td>
                </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </StudentLayout>
</template>
