<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const activeTab = ref<'overview' | 'transcript' | 'translation' | 'qa' | 'notes'>('translation')
const translationLanguage = ref<'km' | 'en' | 'both'>('both')
const isVideoPlaying = ref(false)
const videoSpeed = ref('1.0x')
const isCC = ref(true)

const currentLesson = ref({
  title: 'Chapter 2.1: Understanding Operators',
  courseTitle: 'C Programming Basics',
  instructor: 'Mr. Sophea',
  mode: 'Teacher-Led',
  major: 'IT & Networking',
  videoUrl: 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=720&auto=format&fit=crop&q=75',
  currentTime: '08:45',
  duration: '18:30',
  keyPoint: 'Key Point: Operator %= ប្រើសម្រាប់គណនា Remainder (សំណល់នៃការចែក)'
})

interface Chapter {
  id: string
  name: string
  completed?: boolean
  active?: boolean
  locked?: boolean
}

interface ModuleItem {
  title: string
  status: string
  progress: number
  chapters: Chapter[]
}

const modulesTree = ref<ModuleItem[]>([
  {
    title: 'Module 1: C Fundamentals',
    status: 'completed',
    progress: 100,
    chapters: [
      { id: '1.1', name: 'Ch 1.1 History & Installation', completed: true, active: false, locked: false },
      { id: '1.2', name: 'Ch 1.2 Syntax & Environment Setup', completed: true, active: false, locked: false }
    ]
  },
  {
    title: 'Module 2: Variables & Operators',
    status: 'active',
    progress: 65,
    chapters: [
      { id: '2.1', name: 'Ch 2.1 Data Types & Variables', completed: true, active: false, locked: false },
      { id: '2.2', name: 'Ch 2.2 Understanding Operators', completed: false, active: true, locked: false },
      { id: '2.3', name: 'Ch 2.3 Control Loops & Conditions', completed: false, active: false, locked: true }
    ]
  },
  {
    title: 'Module 3: Functions & Arrays',
    status: 'locked',
    progress: 0,
    chapters: [
      { id: '3.1', name: 'Ch 3.1 Defining Functions', completed: false, active: false, locked: true },
      { id: '3.2', name: 'Ch 3.2 Arrays & Memory', completed: false, active: false, locked: true }
    ]
  }
])

const notesList = ref([
  { id: 1, text: 'Operator %= calculates the remainder of division.', time: '05:20' },
  { id: 2, text: 'Remember ++i vs i++ difference for loop indexes.', time: '11:45' }
])
const newNote = ref('')

const addNote = () => {
  if (newNote.value.trim()) {
    notesList.value.push({
      id: Date.now(),
      text: newNote.value.trim(),
      time: currentLesson.value.currentTime
    })
    newNote.value = ''
  }
}
</script>

<template>
  <StudentLayout title="Current Course — Learning Room">
    <div class="space-y-6">
      
      <!-- Top Learning Room Banner -->
      <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 rounded-3xl p-5 md:p-6 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 text-xs text-indigo-400 font-bold mb-1">
            <span>📖 CURRENT LEARNING ROOM</span>
            <span>•</span>
            <span class="px-2 py-0.5 rounded bg-indigo-500/20 border border-indigo-500/30 text-indigo-300">
              {{ currentLesson.mode }}
            </span>
          </div>
          <h1 class="text-xl md:text-2xl font-black text-white">
            {{ currentLesson.courseTitle }}
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            👨‍🏫 Instructor: {{ currentLesson.instructor }} • 🏫 {{ currentLesson.major }}
          </p>
        </div>

        <div class="flex items-center gap-3">
          <Link
            href="/student/my-courses/enrolled"
            prefetch="hover"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs border border-slate-700 transition-all"
          >
            ← Back to Enrolled Courses
          </Link>
        </div>
      </div>

      <!-- MAIN LEARNING ROOM GRID (Sidebar + Main Player) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT SIDEBAR: CHAPTER TREE (4 Columns on LG) -->
        <div class="lg:col-span-4 bg-slate-800/90 border border-slate-700/80 rounded-3xl p-4 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
              <span>📁</span>
              <span>Course Curriculum</span>
            </h3>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-500/20 text-indigo-300">
              65% Overall
            </span>
          </div>

          <!-- Module Accordion Tree -->
          <div class="space-y-3 max-h-[600px] overflow-y-auto custom-scrollbar pr-1">
            <div v-for="mod in modulesTree" :key="mod.title" class="space-y-1.5">
              <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-900/80 border border-slate-700/60 text-xs font-bold text-slate-200">
                <span class="truncate">{{ mod.title }}</span>
                <span v-if="mod.status === 'completed'" class="text-emerald-400">✅ 100%</span>
                <span v-else-if="mod.status === 'active'" class="text-amber-400">🔄 {{ mod.progress }}%</span>
                <span v-else class="text-slate-500">🔒 Locked</span>
              </div>

              <!-- Chapter List -->
              <div class="pl-3 space-y-1">
                <div
                  v-for="ch in mod.chapters"
                  :key="ch.id"
                  :class="[
                    ch.active ? 'bg-indigo-600/20 text-indigo-300 border-indigo-500/40 font-bold' : 'text-slate-400 hover:text-slate-200 border-transparent',
                    'p-2 rounded-xl border text-xs flex items-center justify-between cursor-pointer transition-all'
                  ]"
                >
                  <div class="flex items-center gap-2 truncate">
                    <span v-if="ch.completed" class="text-emerald-400">✅</span>
                    <span v-else-if="ch.active" class="text-indigo-400 animate-pulse">▶</span>
                    <span v-else-if="ch.locked" class="text-slate-500">🔒</span>
                    <span class="truncate">{{ ch.name }}</span>
                  </div>
                  <span v-if="ch.active" class="px-1.5 py-0.5 rounded text-[9px] bg-indigo-500 text-white">Active</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT MAIN AREA: VIDEO PLAYER & INTERACTIVE TABS (8 Columns on LG) -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- Current Lesson Title -->
          <div class="bg-slate-800/90 border border-slate-700/80 rounded-2xl p-4 shadow-md flex items-center justify-between">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
              <span>📖</span>
              <span>{{ currentLesson.title }}</span>
            </h2>
            <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-bold border border-emerald-500/30">
              Module 2 • Lesson 2
            </span>
          </div>

          <!-- VIDEO PLAYER CONTAINER (Matching Prompt Specs) -->
          <div class="relative bg-slate-950 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl group">
            <div class="relative aspect-video bg-slate-900 flex items-center justify-center">
              <img :src="currentLesson.videoUrl" :alt="currentLesson.title" loading="lazy" decoding="async" class="w-full h-full object-cover opacity-60" />

              <!-- Overlay Play/Pause Controls -->
              <button
                @click="isVideoPlaying = !isVideoPlaying"
                class="absolute inset-0 m-auto w-16 h-16 rounded-full bg-indigo-600/90 hover:bg-indigo-500 text-white flex items-center justify-center shadow-2xl transition-all hover:scale-110 cursor-pointer"
              >
                <svg v-if="!isVideoPlaying" class="w-8 h-8 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                <svg v-else class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
              </button>

              <!-- Custom Video Player Control Bar (Matching Prompt Specs) -->
              <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent p-4 space-y-2">
                <div class="w-full h-1.5 rounded-full bg-slate-700/60 overflow-hidden cursor-pointer">
                  <div class="h-full bg-indigo-500 rounded-full w-[47%]"></div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-200">
                  <div class="flex items-center gap-3">
                    <button @click="isVideoPlaying = !isVideoPlaying" class="hover:text-indigo-400">
                      {{ isVideoPlaying ? '⏸' : '▶' }}
                    </button>
                    <span class="font-mono text-[11px] text-slate-300">
                      {{ currentLesson.currentTime }} / {{ currentLesson.duration }}
                    </span>
                  </div>

                  <div class="flex items-center gap-3 text-xs font-semibold">
                    <button @click="isCC = !isCC" :class="isCC ? 'text-indigo-400' : 'text-slate-500'" class="hover:text-white">
                      🔊 CC: KH | EN
                    </button>
                    <select v-model="videoSpeed" class="bg-slate-900/80 text-xs text-slate-300 rounded border border-slate-700 px-1 py-0.5">
                      <option value="0.75x">0.75x</option>
                      <option value="1.0x">1.0x</option>
                      <option value="1.25x">1.25x</option>
                      <option value="1.5x">1.5x</option>
                    </select>
                    <button class="hover:text-white">⛶ Fullscreen</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- CHAPTER NAVIGATION BAR -->
          <div class="flex items-center justify-between gap-3">
            <button class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs border border-slate-700 transition-all">
              ◀ Previous Chapter
            </button>
            <button class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-all">
              ✓ Mark Complete
            </button>
            <button class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md transition-all">
              Next Chapter ▶
            </button>
          </div>

          <!-- INTERACTIVE LESSON TABS -->
          <div class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-700/60 pb-3">
              <button
                @click="activeTab = 'overview'"
                :class="[activeTab === 'overview' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200', 'px-3.5 py-1.5 rounded-xl text-xs transition-all']"
              >
                📝 Overview
              </button>
              <button
                @click="activeTab = 'transcript'"
                :class="[activeTab === 'transcript' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200', 'px-3.5 py-1.5 rounded-xl text-xs transition-all']"
              >
                🗣 Transcript
              </button>
              <button
                @click="activeTab = 'translation'"
                :class="[activeTab === 'translation' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200', 'px-3.5 py-1.5 rounded-xl text-xs transition-all']"
              >
                🌐 KH/EN Translation
              </button>
              <button
                @click="activeTab = 'qa'"
                :class="[activeTab === 'qa' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200', 'px-3.5 py-1.5 rounded-xl text-xs transition-all']"
              >
                💬 Q&A (3)
              </button>
              <button
                @click="activeTab = 'notes'"
                :class="[activeTab === 'notes' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-slate-200', 'px-3.5 py-1.5 rounded-xl text-xs transition-all']"
              >
                📌 My Notes
              </button>
            </div>

            <!-- TAB CONTENT: BILINGUAL TRANSLATION (Matching Prompt Specs) -->
            <div v-if="activeTab === 'translation'" class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-white">🌐 TRANSLATION MODE</span>
                <div class="flex items-center gap-1 bg-slate-900 rounded-xl p-1 border border-slate-700/80">
                  <button
                    @click="translationLanguage = 'km'"
                    :class="[translationLanguage === 'km' ? 'bg-indigo-600 text-white' : 'text-slate-400', 'px-2.5 py-1 rounded-lg text-[10px] font-bold']"
                  >
                    ខ្មែរ
                  </button>
                  <button
                    @click="translationLanguage = 'en'"
                    :class="[translationLanguage === 'en' ? 'bg-indigo-600 text-white' : 'text-slate-400', 'px-2.5 py-1 rounded-lg text-[10px] font-bold']"
                  >
                    English
                  </button>
                  <button
                    @click="translationLanguage = 'both'"
                    :class="[translationLanguage === 'both' ? 'bg-indigo-600 text-white' : 'text-slate-400', 'px-2.5 py-1 rounded-lg text-[10px] font-bold']"
                  >
                    Both
                  </button>
                </div>
              </div>

              <div class="space-y-3 text-xs leading-relaxed bg-slate-900/80 p-4 rounded-2xl border border-slate-700/60">
                <div v-if="translationLanguage === 'km' || translationLanguage === 'both'" class="space-y-1">
                  <p class="font-bold text-indigo-300">KH (ខ្មែរ):</p>
                  <p class="text-slate-300">
                    ក្នុងមេរៀននេះ យើងនឹងរៀនពី Operators ក្នុង C Programming រួមមាន Arithmetic Operators (+, -, *, /, %), Relational Operators (==, !=, >, <) និង Logical Operators (&&, ||, !)...
                  </p>
                </div>

                <div v-if="translationLanguage === 'en' || translationLanguage === 'both'" class="space-y-1 pt-2 border-t border-slate-800">
                  <p class="font-bold text-cyan-300">EN (English):</p>
                  <p class="text-slate-300">
                    In this lesson, we will learn about Operators in C Programming including Arithmetic Operators (+, -, *, /, %), Relational Operators (==, !=, >, <), and Logical Operators (&&, ||, !)...
                  </p>
                </div>
              </div>
            </div>

            <!-- TAB CONTENT: MY NOTES -->
            <div v-else-if="activeTab === 'notes'" class="space-y-3">
              <div class="flex items-center gap-2">
                <input
                  v-model="newNote"
                  type="text"
                  placeholder="Take a quick note at current timestamp..."
                  class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                  @keyup.enter="addNote"
                />
                <button @click="addNote" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-bold text-xs">
                  Save Note
                </button>
              </div>

              <div class="space-y-2">
                <div v-for="note in notesList" :key="note.id" class="p-3 bg-slate-900/60 rounded-xl border border-slate-700/40 flex items-center justify-between text-xs">
                  <p class="text-slate-200">{{ note.text }}</p>
                  <span class="px-2 py-0.5 rounded bg-slate-800 text-indigo-400 font-mono text-[10px]">{{ note.time }}</span>
                </div>
              </div>
            </div>

            <div v-else class="p-4 text-xs text-slate-400 text-center">
              Content loaded for {{ activeTab }}
            </div>
          </div>

          <!-- MARQUEE RUNNING TEXT BAR (Matching Prompt Specs) -->
          <div class="bg-gradient-to-r from-amber-500/20 via-slate-800 to-indigo-900/40 border border-amber-500/30 rounded-2xl p-3 flex items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 min-w-0">
              <span class="text-amber-400 animate-bounce">🔔</span>
              <span class="font-bold text-amber-300 truncate">{{ currentLesson.keyPoint }}</span>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
              <button class="px-2 py-0.5 rounded bg-slate-800 text-[10px] font-bold text-slate-300">Pause</button>
              <button class="px-2 py-0.5 rounded bg-slate-800 text-[10px] font-bold text-slate-300">1x</button>
              <button class="px-2 py-0.5 rounded bg-indigo-600 text-[10px] font-bold text-white">KH</button>
            </div>
          </div>

        </div>

      </div>

      <!-- COURSE STATS, AI RECOMMENDATION & UPCOMING SCHEDULE -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-4">
        
        <!-- Performance Stats -->
        <div class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-5 shadow-xl space-y-4">
          <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
            <span>📊</span>
            <span>MY PROGRESS IN THIS COURSE</span>
          </h3>

          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700/60">
              <p class="text-slate-400 text-[10px]">⏱ Time Spent</p>
              <p class="text-base font-extrabold text-cyan-300 mt-1">12h 30m</p>
            </div>
            <div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700/60">
              <p class="text-slate-400 text-[10px]">📝 Quiz Avg</p>
              <p class="text-base font-extrabold text-indigo-300 mt-1">78%</p>
            </div>
            <div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700/60">
              <p class="text-slate-400 text-[10px]">📎 Assignments</p>
              <p class="text-base font-extrabold text-amber-300 mt-1">2/3 Done</p>
            </div>
            <div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700/60">
              <p class="text-slate-400 text-[10px]">🏆 Cert Status</p>
              <p class="text-xs font-extrabold text-slate-400 mt-1">⏳ Not Yet</p>
            </div>
          </div>
        </div>

        <!-- AI Recommendation -->
        <div class="bg-gradient-to-br from-indigo-900/40 via-purple-900/30 to-slate-800 border border-indigo-500/30 rounded-3xl p-5 shadow-xl space-y-3 flex flex-col justify-between">
          <div>
            <h3 class="text-xs font-bold text-indigo-300 uppercase tracking-wider flex items-center gap-2">
              <span>🤖</span>
              <span>AI RECOMMENDATION</span>
            </h3>
            <p class="text-xs text-slate-200 leading-relaxed mt-2">
              "You're doing great! After completing Operators, focus on Loops next."
            </p>
          </div>

          <button class="w-full py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md">
            ▶ Start Recommended Lesson
          </button>
        </div>

        <!-- Upcoming Schedule -->
        <div class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-5 shadow-xl space-y-3">
          <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
            <span>📅</span>
            <span>UPCOMING FOR THIS COURSE</span>
          </h3>

          <div class="space-y-2 text-xs">
            <div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700/60 flex items-center justify-between gap-2">
              <div>
                <p class="font-bold text-blue-300">🟦 May 20 • Live Class: Loops</p>
                <p class="text-[10px] text-slate-400">02:00 PM</p>
              </div>
              <button class="px-2.5 py-1 rounded-lg bg-blue-500/20 text-blue-300 text-[10px] font-bold border border-blue-500/30">
                Set Reminder
              </button>
            </div>

            <div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700/60 flex items-center justify-between gap-2">
              <div>
                <p class="font-bold text-rose-300">🟥 May 22 • Post-Test Module 2</p>
                <p class="text-[10px] text-slate-400">Due 11:59 PM</p>
              </div>
            </div>

            <div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-700/60 flex items-center justify-between gap-2">
              <div>
                <p class="font-bold text-amber-300">🟧 May 25 • Assignment Due</p>
                <p class="text-[10px] text-slate-400">Due 11:59 PM</p>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </StudentLayout>
</template>
