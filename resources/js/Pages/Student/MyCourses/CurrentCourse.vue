<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

// Active tab state
const activeTab = ref<'overview' | 'content' | 'resources' | 'discussion'>('overview')
const isVideoPlaying = ref(false)
const videoSpeed = ref('1x')
const isCC = ref(true)
const showCelebrationToast = ref(false)
const celebrationToastMessage = ref('')

// Interactive Quiz State
const isQuizOpen = ref(false)
const isQuizSubmitted = ref(false)
const currentQuestionIndex = ref(0)
const quizAnswers = ref<Record<number, number>>({})
const quizTimeSeconds = ref(95)
const isCourseCompletedModalOpen = ref(false)

// AI Chat widget state in Right Panel
const aiMessages = ref<Array<{ role: 'ai' | 'user'; text: string }>>([
  { role: 'ai', text: 'Hi Pisey! 👋 How can I help you with this lesson?' }
])
const aiInput = ref('')

const sendAiPrompt = (promptText: string) => {
  aiMessages.value.push({ role: 'user', text: promptText })
  const lessonTitle = currentLesson.value?.title || 'JavaScript Functions'
  const lessonDesc = currentLesson.value?.about || 'JavaScript functions and parameters'

  setTimeout(() => {
    const qLower = promptText.toLowerCase()
    if (qLower.includes('example')) {
      aiMessages.value.push({
        role: 'ai',
        text: `Here is a practical code example for **${lessonTitle}**:\n\`\`\`javascript\n// Function declaration with parameter & return\nfunction calculateDiscount(price, rate = 0.1) {\n  return price - (price * rate);\n}\n\nconst total = calculateDiscount(100, 0.2);\nconsole.log("Discounted Total:", total); // 80\n\`\`\`\nTry modifying the rate parameter to see how the return value changes!`
      })
    } else if (qLower.includes('explain') || qLower.includes('what is') || qLower.includes('this')) {
      aiMessages.value.push({
        role: 'ai',
        text: `**Context: ${lessonTitle}**\n\n${lessonDesc}\n\nKey Concept: Functions are declared using the \`function\` keyword, receive arguments via parameters, and pass data back using the \`return\` statement.`
      })
    } else if (qLower.includes('summarize') || qLower.includes('summary')) {
      aiMessages.value.push({
        role: 'ai',
        text: `**Key Summary of ${lessonTitle}:**\n• Reusability: Write once, call multiple times anywhere in your script.\n• Parameters: Inputs defined in the parentheses.\n• Return: Halts execution and sends the result back to the caller.`
      })
    } else if (qLower.includes('practice') || qLower.includes('question')) {
      aiMessages.value.push({
        role: 'ai',
        text: `**Quick Check for ${lessonTitle}:**\nWhat happens if a function executes without an explicit \`return\` statement?\n(A) Throws ReferenceError\n(B) Returns \`undefined\`\n(C) Returns 0\n(D) Returns \`null\``
      })
    } else {
      aiMessages.value.push({
        role: 'ai',
        text: `Regarding **${lessonTitle}**: ${promptText}\n\nIn JavaScript, functions create their own execution scope. Any variable defined inside cannot be accessed outside unless returned or passed via closures.`
      })
    }
  }, 350)
}

const handleSendAi = () => {
  if (!aiInput.value.trim()) return
  const text = aiInput.value.trim()
  aiInput.value = ''
  sendAiPrompt(text)
}

// Chapter curriculum accordion & dynamic lesson data
interface LessonItem {
  id: string
  title: string
  duration: string
  status: 'completed' | 'active' | 'pending' | 'locked'
  about?: string
  objectives?: string[]
}

interface ChapterItem {
  id: number
  title: string
  progress: string
  expanded: boolean
  locked?: boolean
  lessons: LessonItem[]
}

const chapters = ref<ChapterItem[]>([
  {
    id: 1,
    title: 'Chapter 1: Introduction',
    progress: '3/3',
    expanded: false,
    lessons: [
      { id: '1.1', title: '1.1 Web Basics & HTTP', duration: '08:20', status: 'completed' },
      { id: '1.2', title: '1.2 Dev Tools & IDE Setup', duration: '12:40', status: 'completed' },
      { id: '1.3', title: '1.3 Course Roadmap Overview', duration: '06:15', status: 'completed' }
    ]
  },
  {
    id: 2,
    title: 'Chapter 2: HTML Basics',
    progress: '4/4',
    expanded: false,
    lessons: [
      { id: '2.1', title: '2.1 Semantic Tags', duration: '10:15', status: 'completed' },
      { id: '2.2', title: '2.2 Forms and Inputs', duration: '14:30', status: 'completed' },
      { id: '2.3', title: '2.3 Media & Canvas Elements', duration: '11:20', status: 'completed' },
      { id: '2.4', title: '2.4 HTML5 Best Practices', duration: '09:45', status: 'completed' }
    ]
  },
  {
    id: 3,
    title: 'Chapter 3: JavaScript Basics',
    progress: '1/4',
    expanded: true,
    lessons: [
      {
        id: '3.1',
        title: '3.1 What is JavaScript?',
        duration: '10:15',
        status: 'completed',
        about: 'An introduction to JavaScript as the programming language of the Web, its history, and execution environments.',
        objectives: ['Understand how JavaScript works in browsers', 'Include JS via script tags', 'Use developer console for logging']
      },
      {
        id: '3.2',
        title: '3.2 JavaScript Functions',
        duration: '18:20',
        status: 'active',
        about: 'In this lesson, you will learn about JavaScript Functions, how to create them, use parameters, return values, and practical examples.',
        objectives: [
          'What is a function in JavaScript',
          'How to declare and call functions',
          'Function parameters and return values',
          'Real world examples'
        ]
      },
      {
        id: '3.3',
        title: '3.3 Function Parameters',
        duration: '15:30',
        status: 'pending',
        about: 'Deep dive into passing arguments, setting default parameters, rest parameters, and scope rules.',
        objectives: ['Default parameter values', 'Positional arguments vs keyword patterns', 'Rest parameter syntax (...)']
      },
      {
        id: '3.4',
        title: '3.4 Return Values',
        duration: '12:45',
        status: 'locked',
        about: 'Mastering the return statement, returning multiple values using objects and arrays, and void functions.',
        objectives: ['How the return keyword halts execution', 'Returning expressions and data structures', 'Handling undefined return values']
      }
    ]
  },
  {
    id: 4,
    title: 'Chapter 4: DOM Manipulation',
    progress: '0/2',
    expanded: false,
    locked: true,
    lessons: [
      { id: '4.1', title: '4.1 Selecting Elements', duration: '16:00', status: 'locked' },
      { id: '4.2', title: '4.2 Modifying DOM Nodes', duration: '14:20', status: 'locked' }
    ]
  },
  {
    id: 5,
    title: 'Chapter 5: Events',
    progress: '0/2',
    expanded: false,
    locked: true,
    lessons: [
      { id: '5.1', title: '5.1 Event Listeners', duration: '15:10', status: 'locked' },
      { id: '5.2', title: '5.2 Event Bubbling & Delegation', duration: '18:40', status: 'locked' }
    ]
  }
])

const currentLessonId = ref('3.2')

const currentLesson = computed(() => {
  for (const chap of chapters.value) {
    const found = chap.lessons.find(l => l.id === currentLessonId.value)
    if (found) return found
  }
  return chapters.value[2].lessons[1]
})

// Calculate total and completed lessons
const totalLessonsCount = computed(() => {
  return chapters.value.reduce((acc, chap) => acc + chap.lessons.length, 0)
})

const completedLessonsCount = computed(() => {
  return chapters.value.reduce((acc, chap) => {
    return acc + chap.lessons.filter(l => l.status === 'completed').length
  }, 0)
})

const overallProgressPercentage = computed(() => {
  return Math.round((completedLessonsCount.value / totalLessonsCount.value) * 100)
})

const toggleChapter = (chapter: ChapterItem) => {
  if (chapter.locked) return
  chapter.expanded = !chapter.expanded
}

const toggleExpandAll = () => {
  const allOpen = chapters.value.every(c => c.expanded || c.locked)
  chapters.value.forEach(c => {
    if (!c.locked) c.expanded = !allOpen
  })
}

// Select a lesson
const selectLesson = (chap: ChapterItem, lsn: LessonItem) => {
  if (lsn.status === 'locked' || chap.locked) return
  currentLessonId.value = lsn.id
  chapters.value.forEach(c => {
    c.lessons.forEach(l => {
      if (l.status === 'active') l.status = 'completed'
    })
  })
  lsn.status = 'active'
}

// Mini Quiz Questions for JavaScript Functions
const quizQuestions = ref([
  {
    id: 1,
    question: 'Which keyword is used to declare a function in JavaScript?',
    options: ['def', 'func', 'function', 'fn'],
    correct: 2
  },
  {
    id: 2,
    question: 'What is the correct syntax for declaring a function named "calculateTotal"?',
    options: [
      'function:calculateTotal() {}',
      'function calculateTotal() {}',
      'def calculateTotal() {}',
      'call calculateTotal() {}'
    ],
    correct: 1
  },
  {
    id: 3,
    question: 'What statement is used to return a value back to the caller of a function?',
    options: ['send', 'output', 'yield', 'return'],
    correct: 3
  },
  {
    id: 4,
    question: 'What is a parameter in a JavaScript function?',
    options: [
      'A variable listed inside the parentheses in the function definition',
      'The value given to the function when it is called',
      'The return value of the function',
      'The HTML tag calling the script'
    ],
    correct: 0
  },
  {
    id: 5,
    question: 'What happens if a function does not specify a return statement?',
    options: [
      'It throws a syntax error',
      'It returns null',
      'It returns undefined',
      'It automatically returns 0'
    ],
    correct: 2
  }
])

const quizScore = computed(() => {
  let score = 0
  quizQuestions.value.forEach((q, idx) => {
    if (quizAnswers.value[idx] === q.correct) {
      score++
    }
  })
  return score
})

const quizScorePercentage = computed(() => {
  return Math.round((quizScore.value / quizQuestions.value.length) * 100)
})

const isQuizPassed = computed(() => {
  return quizScorePercentage.value >= 70
})

const formattedQuizTime = computed(() => {
  const m = Math.floor(quizTimeSeconds.value / 60)
  const s = quizTimeSeconds.value % 60
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
})

// Trigger Mini Quiz
const handleCompleteAndNext = () => {
  // Open the mini quiz modal
  quizAnswers.value = {}
  currentQuestionIndex.value = 0
  isQuizSubmitted.value = false
  isQuizOpen.value = true
}

const selectQuizAnswer = (qIndex: number, optIndex: number) => {
  quizAnswers.value[qIndex] = optIndex
}

const submitQuiz = () => {
  isQuizSubmitted.value = true
}

const retakeQuiz = () => {
  quizAnswers.value = {}
  currentQuestionIndex.value = 0
  isQuizSubmitted.value = false
}

// Pass flow: Advance to next lesson
const handlePassAndAdvance = () => {
  isQuizOpen.value = false
  
  // Find current chapter & lesson index
  let foundChapIndex = -1
  let foundLessonIndex = -1
  
  chapters.value.forEach((c, cIdx) => {
    const lIdx = c.lessons.findIndex(l => l.id === currentLessonId.value)
    if (lIdx !== -1) {
      foundChapIndex = cIdx
      foundLessonIndex = lIdx
    }
  })

  if (foundChapIndex !== -1 && foundLessonIndex !== -1) {
    // 1. Mark current lesson completed
    chapters.value[foundChapIndex].lessons[foundLessonIndex].status = 'completed'

    // Update chapter progress string
    const comp = chapters.value[foundChapIndex].lessons.filter(l => l.status === 'completed').length
    const tot = chapters.value[foundChapIndex].lessons.length
    chapters.value[foundChapIndex].progress = `${comp}/${tot}`

    // 2. Find next lesson
    if (foundLessonIndex + 1 < chapters.value[foundChapIndex].lessons.length) {
      const nextLesson = chapters.value[foundChapIndex].lessons[foundLessonIndex + 1]
      nextLesson.status = 'active'
      currentLessonId.value = nextLesson.id
    } else if (foundChapIndex + 1 < chapters.value.length) {
      // Unlock next chapter
      const nextChap = chapters.value[foundChapIndex + 1]
      nextChap.locked = false
      nextChap.expanded = true
      if (nextChap.lessons.length > 0) {
        nextChap.lessons[0].status = 'active'
        currentLessonId.value = nextChap.lessons[0].id
      }
    } else {
      // All lessons in all chapters completed!
      isCourseCompletedModalOpen.value = true
    }

    // Trigger celebration toast
    celebrationToastMessage.value = `Lesson completed! Course progress updated to ${overallProgressPercentage.value}%.`
    showCelebrationToast.value = true
    setTimeout(() => {
      showCelebrationToast.value = false
    }, 4000)
  }
}

// Previous and Next lesson triggers
const handlePreviousLesson = () => {
  let foundChapIndex = -1
  let foundLessonIndex = -1
  
  chapters.value.forEach((c, cIdx) => {
    const lIdx = c.lessons.findIndex(l => l.id === currentLessonId.value)
    if (lIdx !== -1) {
      foundChapIndex = cIdx
      foundLessonIndex = lIdx
    }
  })

  if (foundLessonIndex > 0) {
    const prev = chapters.value[foundChapIndex].lessons[foundLessonIndex - 1]
    if (prev.status !== 'locked') {
      selectLesson(chapters.value[foundChapIndex], prev)
    }
  }
}

const handleNextLessonDirect = () => {
  handleCompleteAndNext()
}
</script>

<template>
  <StudentLayout
    :title="`${currentLesson.title} — Web Development Course`"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'My Courses', href: '/student/my-courses/enrolled' },
      { label: 'Web Development', href: '/student/courses/1/overview' },
      { label: currentLesson.title }
    ]"
  >
    <div class="space-y-6 pb-12 relative">
      
      <!-- Toast Notification -->
      <div
        v-if="showCelebrationToast"
        class="fixed top-20 right-6 z-50 p-4 rounded-2xl bg-emerald-600 text-white font-bold text-xs shadow-2xl flex items-center gap-3 border border-emerald-400 animate-in fade-in slide-in-from-top-4 duration-300"
      >
        <span class="text-base">🎉</span>
        <span>{{ celebrationToastMessage }}</span>
      </div>

      <!-- MAIN GRID: Left 8 cols Video & Tabs, Right 4 cols Course Progress & AI Assistant -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8 COLUMNS: Video Player, Title, Tabs, About, Next/Previous Actions -->
        <div class="lg:col-span-8 space-y-5">
          
          <!-- Video Title Bar matching screenshot -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-500/10 dark:bg-purple-600/30 text-purple-600 dark:text-purple-400 border border-purple-500/30 dark:border-purple-500/40 flex items-center justify-center text-lg shrink-0 shadow-sm">
                ▶
              </div>
              <div>
                <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                  {{ currentLesson.title }}
                </h1>
                <p class="text-xs text-purple-600 dark:text-purple-400/90 font-medium">
                  Web Development Course
                </p>
              </div>
            </div>

            <!-- Top Navigation Buttons (< Previous, Next >) -->
            <div class="flex items-center gap-2 self-start sm:self-auto">
              <button
                @click="handlePreviousLesson"
                type="button"
                class="px-3.5 py-1.5 rounded-xl bg-white dark:bg-slate-800/90 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold border border-slate-200 dark:border-slate-700/80 flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
              >
                <span>‹</span>
                <span>Previous</span>
              </button>
              <button
                @click="handleNextLessonDirect"
                type="button"
                class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/20 flex items-center gap-1.5 transition-colors cursor-pointer"
              >
                <span>Next</span>
                <span>›</span>
              </button>
            </div>
          </div>

          <!-- Video Player Screen matching design in screenshot -->
          <div class="relative rounded-3xl overflow-hidden bg-gradient-to-br from-[#0c1322] via-[#0f172a] to-[#0a0f1d] border border-slate-800 shadow-2xl group">
            
            <!-- Graphic Illustration Container for Lesson Video Cover -->
            <div class="relative aspect-video w-full flex items-center justify-center p-6 sm:p-12 overflow-hidden select-none bg-[#090e1a]">
              <!-- Grid background accent -->
              <div class="absolute inset-0 bg-[radial-gradient(#3b82f6_1px,transparent_1px)] [background-size:20px_20px] opacity-15 pointer-events-none"></div>

              <div class="relative z-10 w-full max-w-2xl flex flex-col md:flex-row items-center justify-between gap-6">
                <!-- Left Title Banner -->
                <div class="space-y-3 text-left">
                  <div class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-amber-400 text-slate-950 font-black text-xs shadow-md">
                    JS
                  </div>
                  <h2 class="text-2xl sm:text-4xl font-black text-white leading-tight tracking-tight">
                    JavaScript<br />
                    <span class="text-amber-400">Functions</span>
                  </h2>
                </div>

                <!-- Right Code Editor Mockup Card -->
                <div class="relative w-64 sm:w-72 rounded-2xl bg-slate-900/90 border border-slate-700/80 p-4 shadow-2xl backdrop-blur-md">
                  <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-[10px] text-slate-400">
                    <div class="flex items-center gap-1.5">
                      <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                      <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                      <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <span class="font-mono text-purple-400">{...}</span>
                  </div>
                  <div class="space-y-1.5 font-mono text-[11px] leading-relaxed">
                    <div class="flex items-center gap-2">
                      <div class="w-12 h-2 rounded bg-purple-400/60"></div>
                      <div class="w-20 h-2 rounded bg-blue-400/60"></div>
                    </div>
                    <div class="flex items-center gap-2 pl-3">
                      <div class="w-16 h-2 rounded bg-amber-400/60"></div>
                      <div class="w-10 h-2 rounded bg-emerald-400/60"></div>
                    </div>
                    <div class="flex items-center gap-2 pl-3">
                      <div class="w-24 h-2 rounded bg-cyan-400/60"></div>
                    </div>
                    <div class="flex items-center gap-2">
                      <div class="w-8 h-2 rounded bg-purple-400/60"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Overlay Play Button -->
              <button
                @click="isVideoPlaying = !isVideoPlaying"
                type="button"
                class="absolute inset-0 m-auto w-16 h-16 rounded-full bg-purple-600/90 hover:bg-purple-500 text-white flex items-center justify-center shadow-2xl shadow-purple-600/40 hover:scale-110 active:scale-95 transition-all cursor-pointer z-20"
              >
                <span v-if="!isVideoPlaying" class="text-xl ml-1">▶</span>
                <span v-else class="text-xl">❚❚</span>
              </button>
            </div>

            <!-- Video Player Bottom Control Bar -->
            <div class="bg-slate-950/90 border-t border-slate-800/80 px-4 py-3 space-y-2">
              <!-- Scrub bar -->
              <div class="relative w-full h-1.5 rounded-full bg-slate-800 cursor-pointer overflow-hidden group/bar">
                <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full w-[48%]"></div>
              </div>

              <div class="flex items-center justify-between text-xs text-slate-300">
                <div class="flex items-center gap-3">
                  <button @click="isVideoPlaying = !isVideoPlaying" class="hover:text-white transition-colors cursor-pointer">
                    <span v-if="!isVideoPlaying">▶</span>
                    <span v-else>❚❚</span>
                  </button>
                  <button class="hover:text-white transition-colors cursor-pointer">
                    🔊
                  </button>
                  <span class="text-[11px] font-mono text-slate-400">08:45 / {{ currentLesson.duration }}</span>
                </div>

                <div class="flex items-center gap-3">
                  <button
                    @click="videoSpeed = videoSpeed === '1x' ? '1.5x' : videoSpeed === '1.5x' ? '2x' : '1x'"
                    class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-[11px] font-bold text-slate-300 transition-colors cursor-pointer"
                  >
                    {{ videoSpeed }}
                  </button>
                  <button
                    @click="isCC = !isCC"
                    :class="[isCC ? 'bg-purple-600 text-white' : 'bg-slate-800 text-slate-400', 'px-2 py-0.5 rounded text-[11px] font-bold transition-colors cursor-pointer']"
                  >
                    CC
                  </button>
                  <button class="hover:text-white transition-colors cursor-pointer text-slate-400">
                    ⚙️
                  </button>
                  <button class="hover:text-white transition-colors cursor-pointer text-slate-400">
                    ⛶
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Lesson Navigation Tabs (Overview, Lesson Content, Resources, Discussion) -->
          <div class="border-b border-slate-200 dark:border-slate-800">
            <nav class="flex items-center gap-6 text-xs font-semibold">
              <button
                @click="activeTab = 'overview'"
                :class="[
                  activeTab === 'overview'
                    ? 'text-purple-600 dark:text-purple-400 border-b-2 border-purple-500 pb-3 font-bold'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 pb-3 border-b-2 border-transparent'
                ]"
                class="transition-colors cursor-pointer"
              >
                Overview
              </button>
              <button
                @click="activeTab = 'content'"
                :class="[
                  activeTab === 'content'
                    ? 'text-purple-600 dark:text-purple-400 border-b-2 border-purple-500 pb-3 font-bold'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 pb-3 border-b-2 border-transparent'
                ]"
                class="transition-colors cursor-pointer"
              >
                Lesson Content
              </button>
              <button
                @click="activeTab = 'resources'"
                :class="[
                  activeTab === 'resources'
                    ? 'text-purple-600 dark:text-purple-400 border-b-2 border-purple-500 pb-3 font-bold'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 pb-3 border-b-2 border-transparent'
                ]"
                class="transition-colors cursor-pointer"
              >
                Resources
              </button>
              <button
                @click="activeTab = 'discussion'"
                :class="[
                  activeTab === 'discussion'
                    ? 'text-purple-600 dark:text-purple-400 border-b-2 border-purple-500 pb-3 font-bold'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 pb-3 border-b-2 border-transparent'
                ]"
                class="transition-colors cursor-pointer"
              >
                Discussion
              </button>
            </nav>
          </div>

          <!-- TAB CONTENT: Overview -->
          <div v-show="activeTab === 'overview'" class="space-y-6">
            <!-- About This Lesson Section -->
            <div class="space-y-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">About This Lesson</h3>
              <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                {{ currentLesson.about || 'In this lesson, you will learn about JavaScript Functions, how to create them, use parameters, return values, and practical examples.' }}
              </p>
            </div>

            <!-- What you will learn Section -->
            <div class="space-y-2.5">
              <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">What you will learn:</h4>
              <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                <li
                  v-for="(obj, idx) in (currentLesson.objectives || ['What is a function in JavaScript', 'How to declare and call functions', 'Function parameters and return values', 'Real world examples'])"
                  :key="idx"
                  class="flex items-center gap-2.5"
                >
                  <span class="w-4 h-4 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                  <span>{{ obj }}</span>
                </li>
              </ul>
            </div>

            <!-- Need help banner with Ask AI Assistant -->
            <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-indigo-50/80 via-white to-purple-50/80 dark:from-indigo-950/60 dark:via-purple-950/40 dark:to-slate-900 border border-purple-200 dark:border-purple-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm dark:shadow-xl">
              <div>
                <h4 class="text-xs font-bold text-slate-900 dark:text-white">Need help understanding this lesson?</h4>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">Ask our AI Assistant to explain or give you examples.</p>
              </div>

              <button
                @click="sendAiPrompt('Explain this lesson')"
                type="button"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/20 flex items-center gap-2 transition-all hover:scale-105 active:scale-95 cursor-pointer shrink-0"
              >
                <span>✨ Ask AI Assistant</span>
                <span>›</span>
              </button>
            </div>
          </div>

          <!-- TAB CONTENT: Lesson Content -->
          <div v-show="activeTab === 'content'" class="space-y-4 text-xs text-slate-700 dark:text-slate-300 leading-relaxed bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-xs">
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Detailed Lesson Transcript & Notes</h4>
            <p>1. <strong>Function Declarations</strong>: Declaring a function using the <code>function</code> keyword creates a named reusable function.</p>
            <pre class="p-3 bg-slate-900 text-purple-300 dark:bg-slate-950 rounded-xl font-mono text-[11px] overflow-x-auto">function calculateTotal(price, tax) {
  return price + (price * tax);
}</pre>
            <p>2. <strong>Function Expressions and Arrow Functions</strong>: Modern ES6 syntax offers concise arrow function syntax for callbacks and handlers.</p>
          </div>

          <!-- TAB CONTENT: Resources -->
          <div v-show="activeTab === 'resources'" class="space-y-3">
            <div class="p-3.5 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-between gap-3 text-xs shadow-xs">
              <div class="flex items-center gap-2.5">
                <span class="text-base">📄</span>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">JavaScript Functions Cheatsheet.pdf</p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">PDF Document • 2.4 MB</p>
                </div>
              </div>
              <button class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-purple-600 dark:text-purple-400 text-xs font-bold border border-slate-200 dark:border-slate-700 cursor-pointer">Download</button>
            </div>
            <div class="p-3.5 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-between gap-3 text-xs shadow-xs">
              <div class="flex items-center gap-2.5">
                <span class="text-base">💻</span>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">lesson-3-starter-code.zip</p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">Source Code • 150 KB</p>
                </div>
              </div>
              <button class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-purple-600 dark:text-purple-400 text-xs font-bold border border-slate-200 dark:border-slate-700 cursor-pointer">Download</button>
            </div>
          </div>

          <!-- TAB CONTENT: Discussion -->
          <div v-show="activeTab === 'discussion'" class="space-y-4">
            <div class="flex items-center gap-3">
              <input
                type="text"
                placeholder="Ask a question about this lesson..."
                class="flex-1 px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-purple-500 shadow-xs"
              />
              <button class="px-4 py-2 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-500 cursor-pointer">Post</button>
            </div>
          </div>

          <!-- Bottom Action Buttons (Previous Lesson / Complete & Next Lesson) -->
          <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
            <button
              @click="handlePreviousLesson"
              type="button"
              class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 flex items-center gap-2 transition-colors cursor-pointer shadow-xs"
            >
              <span>←</span>
              <span>Previous Lesson</span>
            </button>

            <button
              @click="handleCompleteAndNext"
              type="button"
              class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/20 flex items-center gap-2 hover:scale-105 active:scale-95 transition-all cursor-pointer"
            >
              <span>Complete & Next Lesson</span>
              <span>→</span>
            </button>
          </div>

        </div>

        <!-- RIGHT 4 COLUMNS: Course Progress & Curriculum Accordion + AI Study Assistant -->
        <div class="lg:col-span-4 space-y-6 sticky top-20">
          
          <!-- Course Progress Card -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between text-xs font-bold">
              <span class="text-slate-900 dark:text-white">Course Progress</span>
              <span class="text-purple-600 dark:text-purple-400">{{ overallProgressPercentage }}%</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ completedLessonsCount }} / {{ totalLessonsCount }} Lessons Completed</p>
            
            <div class="w-full h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full transition-all duration-500" :style="{ width: overallProgressPercentage + '%' }"></div>
            </div>
          </div>

          <!-- Course Content Accordion -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Course Content</h3>
              <button
                @click="toggleExpandAll"
                type="button"
                class="text-[11px] font-semibold text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 transition-colors cursor-pointer"
              >
                Expand All
              </button>
            </div>

            <!-- Chapter Items List -->
            <div class="space-y-2">
              <div
                v-for="chap in chapters"
                :key="chap.id"
                class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-950/60 overflow-hidden"
              >
                <!-- Chapter Header -->
                <button
                  @click="toggleChapter(chap)"
                  type="button"
                  :class="[
                    chap.locked ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/40',
                    'w-full p-3 flex items-center justify-between text-xs transition-colors'
                  ]"
                >
                  <div class="flex items-center gap-2 text-left truncate">
                    <span :class="[chap.expanded ? 'rotate-90 text-purple-600 dark:text-purple-400' : 'text-slate-400 dark:text-slate-500', 'transition-transform text-[10px]']">›</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200 truncate">{{ chap.title }}</span>
                  </div>

                  <div class="flex items-center gap-1.5 shrink-0">
                    <span :class="[chap.progress.startsWith(chap.progress.split('/')[1]) ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-purple-600 dark:text-purple-400 font-bold', 'text-[11px]']">
                      {{ chap.progress }}
                    </span>
                    <span v-if="chap.locked" class="text-xs text-slate-400 dark:text-slate-500">🔒</span>
                    <span v-else :class="[chap.expanded ? 'rotate-180' : '', 'text-[10px] text-slate-400 dark:text-slate-500 transition-transform']">⌄</span>
                  </div>
                </button>

                <!-- Lessons inside chapter -->
                <div v-show="chap.expanded && !chap.locked" class="border-t border-slate-200 dark:border-slate-800/60 p-2 space-y-1 bg-white dark:bg-slate-950/90">
                  <div
                    v-for="lsn in chap.lessons"
                    :key="lsn.id"
                    @click="selectLesson(chap, lsn)"
                    :class="[
                      lsn.status === 'active' || lsn.id === currentLessonId
                        ? 'bg-purple-50 dark:bg-purple-600/25 border-purple-300 dark:border-purple-500/40 text-purple-900 dark:text-white font-bold'
                        : lsn.status === 'completed'
                        ? 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-900 border-transparent cursor-pointer'
                        : lsn.status === 'pending'
                        ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-900 border-transparent cursor-pointer'
                        : 'text-slate-400 dark:text-slate-600 border-transparent opacity-60 cursor-not-allowed',
                      'p-2.5 rounded-xl border text-xs flex items-center justify-between transition-colors'
                    ]"
                  >
                    <div class="flex items-center gap-2.5 truncate">
                      <span v-if="lsn.status === 'completed'" class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                      <span v-else-if="lsn.status === 'active' || lsn.id === currentLessonId" class="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-[9px]">▶</span>
                      <span v-else-if="lsn.status === 'locked'" class="w-4 h-4 text-slate-400 dark:text-slate-500 flex items-center justify-center text-[10px]">🔒</span>
                      <span v-else class="w-4 h-4 rounded-full border border-slate-300 dark:border-slate-600 flex items-center justify-center text-[9px]">○</span>
                      <span class="truncate">{{ lsn.title }}</span>
                    </div>

                    <span class="text-[10px] text-slate-500 dark:text-slate-400 shrink-0 font-mono">{{ lsn.duration }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- AI Study Assistant Card matching screenshot -->
          <div class="bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/60 dark:from-[#111827] dark:via-slate-900 dark:to-[#1e1b4b]/60 border border-purple-200 dark:border-purple-500/30 rounded-3xl p-5 shadow-sm dark:shadow-2xl space-y-4">
            <div class="flex items-center gap-2.5">
              <div class="w-7 h-7 rounded-xl bg-purple-600 text-white flex items-center justify-center text-xs shadow-md shadow-purple-600/20">
                🤖
              </div>
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">AI Study Assistant</h3>
            </div>

            <!-- Messages Area -->
            <div class="space-y-3 max-h-48 overflow-y-auto custom-scrollbar pr-1">
              <div
                v-for="(msg, idx) in aiMessages"
                :key="idx"
                :class="[
                  msg.role === 'user' ? 'bg-purple-50 dark:bg-purple-600/30 border border-purple-200 dark:border-purple-500/40 text-purple-900 dark:text-purple-100 ml-6' : 'bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 text-slate-800 dark:text-slate-200 mr-2',
                  'p-3 rounded-2xl text-xs space-y-1'
                ]"
              >
                <p class="whitespace-pre-wrap leading-relaxed">{{ msg.text }}</p>
              </div>
            </div>

            <!-- Suggested prompt chips -->
            <div class="flex flex-wrap gap-1.5 pt-1">
              <button
                @click="sendAiPrompt('Explain this lesson')"
                type="button"
                class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/90 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-medium border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer"
              >
                Explain this lesson
              </button>
              <button
                @click="sendAiPrompt('Give me an example')"
                type="button"
                class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/90 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-medium border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer"
              >
                Give me an example
              </button>
              <button
                @click="sendAiPrompt('Summarize this topic')"
                type="button"
                class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/90 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-medium border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer"
              >
                Summarize this topic
              </button>
              <button
                @click="sendAiPrompt('Generate practice questions')"
                type="button"
                class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/90 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-medium border border-slate-200 dark:border-slate-700/60 transition-colors cursor-pointer"
              >
                Generate practice questions
              </button>
            </div>

            <!-- Chat Input form -->
            <form @submit.prevent="handleSendAi" class="relative flex items-center pt-1">
              <input
                v-model="aiInput"
                type="text"
                placeholder="Type your question..."
                class="w-full pl-3.5 pr-10 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-purple-500 transition-colors shadow-inner"
              />
              <button
                type="submit"
                class="absolute right-1.5 p-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white transition-all cursor-pointer"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </form>
          </div>

        </div>

      </div>

      <!-- MINI QUIZ MODAL (Interactive Assessment Step) -->
      <div
        v-if="isQuizOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md transition-opacity"
      >
        <div class="w-full max-w-2xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xl p-6 sm:p-8 space-y-6 text-slate-900 dark:text-white animate-in zoom-in-95 duration-200">
          
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-purple-500/10 dark:bg-purple-600/30 text-purple-600 dark:text-purple-400 border border-purple-500/30 dark:border-purple-500/40 flex items-center justify-center text-sm font-bold">
                📝
              </div>
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Lesson Practice / Mini Quiz</h3>
                <p class="text-xs text-purple-600 dark:text-purple-400">{{ currentLesson.title }}</p>
              </div>
            </div>

            <button
              @click="isQuizOpen = false"
              type="button"
              class="p-2 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              ✕
            </button>
          </div>

          <!-- QUIZ IN PROGRESS VIEW -->
          <div v-if="!isQuizSubmitted" class="space-y-6">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
              <span>Question {{ currentQuestionIndex + 1 }} of {{ quizQuestions.length }}</span>
              <span class="font-mono">⏱ {{ formattedQuizTime }}</span>
            </div>

            <!-- Progress Bar -->
            <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <div
                class="h-full bg-purple-500 rounded-full transition-all duration-300"
                :style="{ width: `${((currentQuestionIndex + 1) / quizQuestions.length) * 100}%` }"
              ></div>
            </div>

            <!-- Current Question -->
            <div class="space-y-4">
              <h4 class="text-sm sm:text-base font-bold text-slate-800 dark:text-slate-100 leading-snug">
                {{ quizQuestions[currentQuestionIndex].question }}
              </h4>

              <!-- Multiple Choice Options -->
              <div class="space-y-2.5">
                <button
                  v-for="(option, optIdx) in quizQuestions[currentQuestionIndex].options"
                  :key="optIdx"
                  @click="selectQuizAnswer(currentQuestionIndex, optIdx)"
                  type="button"
                  :class="[
                    quizAnswers[currentQuestionIndex] === optIdx
                      ? 'bg-purple-50 dark:bg-purple-600/30 border-purple-500 text-purple-900 dark:text-white font-bold shadow-md shadow-purple-600/10'
                      : 'bg-slate-50 dark:bg-slate-950/60 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60',
                    'w-full p-3.5 rounded-2xl border text-xs text-left flex items-center justify-between transition-all cursor-pointer'
                  ]"
                >
                  <div class="flex items-center gap-3">
                    <span
                      :class="[
                        quizAnswers[currentQuestionIndex] === optIdx
                          ? 'bg-purple-600 text-white'
                          : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-400',
                        'w-6 h-6 rounded-full flex items-center justify-center font-bold text-[11px] shrink-0'
                      ]"
                    >
                      {{ String.fromCharCode(65 + optIdx) }}
                    </span>
                    <span>{{ option }}</span>
                  </div>

                  <span v-if="quizAnswers[currentQuestionIndex] === optIdx" class="text-purple-600 dark:text-purple-400 font-bold">●</span>
                </button>
              </div>
            </div>

            <!-- Navigation Controls inside Quiz -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
              <button
                :disabled="currentQuestionIndex === 0"
                @click="currentQuestionIndex--"
                type="button"
                :class="[
                  currentQuestionIndex === 0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer',
                  'px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 text-xs font-bold transition-colors'
                ]"
              >
                Previous
              </button>

              <button
                v-if="currentQuestionIndex < quizQuestions.length - 1"
                @click="currentQuestionIndex++"
                type="button"
                class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-md shadow-purple-600/20 transition-all cursor-pointer"
              >
                Next Question ›
              </button>

              <button
                v-else
                @click="submitQuiz"
                type="button"
                class="px-6 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition-all cursor-pointer"
              >
                Submit Quiz ✓
              </button>
            </div>
          </div>

          <!-- QUIZ RESULT & AI ANALYSIS VIEW -->
          <div v-else class="space-y-6 text-center">
            <div
              :class="[
                isQuizPassed ? 'bg-emerald-500/10 dark:bg-emerald-500/15 border-emerald-500/30 text-emerald-800 dark:text-emerald-300' : 'bg-rose-500/10 dark:bg-rose-500/15 border-rose-500/30 text-rose-800 dark:text-rose-300',
                'p-6 rounded-3xl border space-y-3'
              ]"
            >
              <div class="text-3xl">
                {{ isQuizPassed ? '🎉' : '⚠️' }}
              </div>
              <h4 class="text-lg font-black text-slate-900 dark:text-white">
                {{ isQuizPassed ? 'Congratulations! You passed this lesson.' : 'You need more practice.' }}
              </h4>
              <p class="text-xs opacity-90">
                {{ isQuizPassed ? 'You have successfully verified your understanding of JavaScript Functions.' : 'Score 70% or higher to unlock the next lesson and advance.' }}
              </p>

              <!-- Score Summary Grid -->
              <div class="grid grid-cols-3 gap-3 pt-3 text-left">
                <div class="p-3 rounded-2xl bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80 shadow-xs">
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">Score</p>
                  <p class="text-base font-black text-slate-900 dark:text-white mt-0.5">{{ quizScore }} / {{ quizQuestions.length }}</p>
                </div>
                <div class="p-3 rounded-2xl bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80 shadow-xs">
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">Percentage</p>
                  <p :class="[isQuizPassed ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400', 'text-base font-black mt-0.5']">
                    {{ quizScorePercentage }}%
                  </p>
                </div>
                <div class="p-3 rounded-2xl bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80 shadow-xs">
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">Time Used</p>
                  <p class="text-base font-black text-cyan-600 dark:text-cyan-300 font-mono mt-0.5">{{ formattedQuizTime }}</p>
                </div>
              </div>
            </div>

            <!-- AI Analysis Placeholder Box -->
            <div class="p-4 rounded-2xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-500/30 text-left space-y-2 shadow-xs">
              <div class="flex items-center gap-2">
                <span class="text-sm">🤖</span>
                <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider">AI Analysis & Feedback</span>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                {{ isQuizPassed
                  ? 'Great performance! You demonstrated clear comprehension of function syntax and return mechanisms. Continue practicing parameter scoping in the next lesson.'
                  : 'Review the return statement logic in Chapter 3.2. Remember that without an explicit return, a function defaults to returning undefined.' }}
              </p>
            </div>

            <!-- Action Buttons based on Pass / Fail -->
            <div class="flex items-center justify-center gap-3 pt-2">
              <template v-if="isQuizPassed">
                <button
                  @click="handlePassAndAdvance"
                  type="button"
                  class="px-8 py-3 rounded-2xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-xl shadow-purple-600/20 transition-all hover:scale-105 active:scale-95 cursor-pointer"
                >
                  Continue to Next Lesson →
                </button>
              </template>
              <template v-else>
                <button
                  @click="isQuizOpen = false"
                  type="button"
                  class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition-colors cursor-pointer"
                >
                  Review Lesson
                </button>
                <button
                  @click="retakeQuiz"
                  type="button"
                  class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/20 transition-all cursor-pointer"
                >
                  Retake Quiz 🔄
                </button>
              </template>
            </div>
          </div>

        </div>
      </div>

      <!-- COURSE COMPLETED CELEBRATION MODAL (Final Assessment Completed) -->
      <div
        v-if="isCourseCompletedModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md transition-opacity"
      >
        <div class="w-full max-w-lg rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 shadow-2xl p-6 sm:p-8 space-y-6 text-center text-slate-900 dark:text-white animate-in zoom-in-95 duration-200">
          <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-600 text-white text-3xl flex items-center justify-center mx-auto shadow-2xl shadow-purple-600/30">
            🏆
          </div>

          <div class="space-y-2">
            <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Course Completed!</h3>
            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
              Congratulations Sok Pisey! You have completed all 15 lessons and assessments for the <strong>Web Development Course</strong>.
            </p>
          </div>

          <div class="p-4 rounded-2xl bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-500/30 text-left flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-2xl">🎓</span>
              <div>
                <p class="text-xs font-bold text-slate-900 dark:text-white">Certificate of Completion</p>
                <p class="text-[10px] text-purple-600 dark:text-purple-300">Verified by Saint Paul Institute</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold border border-emerald-500/30">
              Ready
            </span>
          </div>

          <div class="flex items-center justify-center gap-3 pt-2">
            <button
              @click="isCourseCompletedModalOpen = false"
              type="button"
              class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs cursor-pointer"
            >
              Close
            </button>
            <Link
              href="/student/certificates/my-certificates"
              class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-xl shadow-purple-600/30 cursor-pointer"
            >
              View Certificate 🏅
            </Link>
          </div>
        </div>
      </div>

    </div>
  </StudentLayout>
</template>
