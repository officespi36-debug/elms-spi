<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface ChatMessage {
  id: string
  role: 'user' | 'ai'
  content: string
  timestamp: string
  codeSnippet?: {
    language: string
    code: string
  }
  bullets?: string[]
  quiz?: {
    id: number
    question: string
    options: string[]
    correctIndex: number
    selectedIndex?: number
    isSubmitted?: boolean
    explanation: string
  }
  suggestedPrompts?: string[]
  liked?: boolean
  disliked?: boolean
}

interface Conversation {
  id: string
  title: string
  courseContext: string
  timestamp: string
  messages: ChatMessage[]
}

const page = usePage()
const studentName = computed(() => (page.props.auth as any)?.user?.name || 'Sok Pisey')
const studentId = computed(() => (page.props.auth as any)?.user?.student_id || 'STU2024001')

// Courses available for context switching
const availableCourses = [
  {
    id: 1,
    title: 'Web Development Fundamentals',
    chapter: 'Chapter 3: JavaScript Functions',
    lesson: '3.2 Function Declarations & Arrow Functions',
    progress: 53,
    icon: 'JS',
    iconBg: 'from-amber-400 to-amber-600',
    recentActivity: [
      { text: 'Viewed: 3.2 JavaScript Functions', time: 'Today, 09:15 AM' },
      { text: 'Completed Quiz: Chapter 2 Quiz', time: 'Yesterday, 04:15 PM' },
      { text: 'Studied: 2h 30m', time: 'This Week' }
    ]
  },
  {
    id: 2,
    title: 'Database Systems & SQL',
    chapter: 'Chapter 4: Advanced SQL Queries & Joins',
    lesson: '4.1 INNER vs LEFT JOIN Operations',
    progress: 35,
    icon: 'SQL',
    iconBg: 'from-cyan-400 to-blue-600',
    recentActivity: [
      { text: 'Viewed: 4.1 SQL Join Tables', time: 'Yesterday, 11:30 AM' },
      { text: 'Completed Exercise: Aggregations', time: '3 days ago' },
      { text: 'Studied: 1h 45m', time: 'This Week' }
    ]
  },
  {
    id: 3,
    title: 'Python Programming Mastery',
    chapter: 'Chapter 1: Python Syntax & Loops',
    lesson: '1.4 For and While Loops with Range',
    progress: 10,
    icon: 'PY',
    iconBg: 'from-emerald-400 to-teal-600',
    recentActivity: [
      { text: 'Viewed: 1.4 Loop Control Statements', time: '2 days ago' },
      { text: 'Completed Quiz: Basic Syntax', time: '4 days ago' },
      { text: 'Studied: 1h 10m', time: 'This Week' }
    ]
  },
  {
    id: 4,
    title: 'UI/UX Design Fundamentals',
    chapter: 'Chapter 5: Design Systems in Figma',
    lesson: '5.2 Typography and Color Tokens',
    progress: 100,
    icon: 'UI',
    iconBg: 'from-purple-400 to-pink-600',
    recentActivity: [
      { text: 'Course Completed 100%', time: 'Last week' },
      { text: 'Certificate Verified', time: 'Last week' },
      { text: 'Studied: 12h 00m', time: 'Total' }
    ]
  }
]

const selectedCourse = ref(availableCourses[0])
const isCourseDropdownOpen = ref(false)

// Search in page
const headerSearch = ref('')

// Suggestions list
const allSuggestions = [
  { icon: '</>', text: 'Explain JavaScript functions', query: 'Can you explain JavaScript functions with an example?' },
  { icon: '🐍', text: 'Help me with Python loops', query: 'How do for loops and while loops work in Python?' },
  { icon: '📖', text: 'Summarize this lesson', query: 'Please summarize the key takeaways of our current lesson.' },
  { icon: '❓', text: 'Generate quiz questions', query: 'Generate 3 interactive practice quiz questions on this topic.' },
  { icon: '💡', text: 'Give me an example', query: 'Can you give me a real-world practical code example?' },
  { icon: '🎯', text: 'What should I study today?', query: 'Based on my progress and weak topics, what should I study today?' },
  { icon: '🪲', text: 'Explain this error', query: 'What does TypeError: Cannot read property of undefined mean and how do I fix it?' },
  { icon: '📅', text: 'Create a study plan', query: 'Create a personalized 30-minute daily study plan for this week.' },
  { icon: '🌐', text: 'Explain REST API endpoints', query: 'What is REST API, and how do GET, POST, PUT, DELETE methods work?' },
  { icon: '🗄️', text: 'Explain SQL JOINs simply', query: 'Can you explain INNER JOIN vs LEFT JOIN with a simple visual diagram?' }
]

const showAllSuggestions = ref(false)
const displayedSuggestions = computed(() => {
  return showAllSuggestions.value ? allSuggestions : allSuggestions.slice(0, 8)
})

// Conversations History
const conversations = ref<Conversation[]>([
  {
    id: 'conv-1',
    title: 'Explain JavaScript functions',
    courseContext: 'Web Development Fundamentals',
    timestamp: 'Today, 10:30 AM',
    messages: [
      {
        id: 'msg-1',
        role: 'user',
        content: 'Can you explain JavaScript functions with an example?',
        timestamp: '10:30 AM'
      },
      {
        id: 'msg-2',
        role: 'ai',
        content: `Sure! I'd be happy to explain JavaScript functions for you. 😊\n\nA function in JavaScript is a block of code designed to perform a particular task. Functions help make your code reusable, organized, and easier to maintain.\n\nHere's a simple example:`,
        timestamp: '10:30 AM',
        codeSnippet: {
          language: 'JavaScript',
          code: `function greet(name) {\n  return "Hello, " + name + "!";\n}\n\nconsole.log(greet("Sok Pisey"));`
        },
        bullets: [
          'We created a function called greet',
          'It takes a parameter name',
          'It returns a greeting message',
          'Then we call the function and pass "Sok Pisey"'
        ],
        suggestedPrompts: [
          'Would you like me to generate a practice quiz for you on this topic?',
          'What is the difference between Arrow Functions and regular functions?',
          'How do default parameters work in ES6?'
        ]
      }
    ]
  },
  {
    id: 'conv-2',
    title: 'Help with Python loops',
    courseContext: 'Python Programming Mastery',
    timestamp: 'Yesterday, 03:45 PM',
    messages: [
      {
        id: 'msg-201',
        role: 'user',
        content: 'How do Python for loops with range() work?',
        timestamp: '03:45 PM'
      },
      {
        id: 'msg-202',
        role: 'ai',
        content: `In Python, the \`for\` loop iterates over sequences such as lists, strings, or numbers generated by \`range()\`.`,
        timestamp: '03:46 PM',
        codeSnippet: {
          language: 'Python',
          code: `# Loop from 1 to 5\nfor i in range(1, 6):\n    print(f"Step {i}: Learning Python at SPI")`
        },
        bullets: [
          'range(start, stop) generates numbers up to stop - 1',
          'The loop variable i updates on each iteration'
        ]
      }
    ]
  },
  {
    id: 'conv-3',
    title: 'What is DOM in JavaScript?',
    courseContext: 'Web Development Fundamentals',
    timestamp: 'May 25, 2025',
    messages: [
      {
        id: 'msg-301',
        role: 'user',
        content: 'What is Document Object Model (DOM)?',
        timestamp: '11:15 AM'
      },
      {
        id: 'msg-302',
        role: 'ai',
        content: `The DOM (Document Object Model) is a tree-like representation of HTML documents created by the browser. It allows JavaScript to dynamically read, modify, and style web page elements.`,
        timestamp: '11:16 AM'
      }
    ]
  }
])

const activeConversationId = ref('conv-1')
const activeConversation = computed(() => {
  return conversations.value.find(c => c.id === activeConversationId.value) || conversations.value[0]
})

// Input State
const userQuestion = ref('')
const isAiThinking = ref(false)
const chatContainerRef = ref<HTMLElement | null>(null)
const copiedMessageId = ref<string | null>(null)

// File Attachment Modal
const isUploadModalOpen = ref(false)
const attachedFile = ref<{ name: string; size: string } | null>(null)

// Study Plan Modal
const isStudyPlanModalOpen = ref(false)

// Quick Prompts click
const handleQuickSuggestion = (query: string) => {
  userQuestion.value = query
  sendMessage()
}

// Send Message
const sendMessage = async () => {
  if (!userQuestion.value.trim() || isAiThinking.value) return

  const q = userQuestion.value.trim()
  userQuestion.value = ''

  const now = new Date()
  const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })

  // Add user message
  const userMsg: ChatMessage = {
    id: 'msg-' + Date.now(),
    role: 'user',
    content: q,
    timestamp: timeStr
  }

  activeConversation.value.messages.push(userMsg)
  scrollToBottom()

  // Update conversation title if first message
  if (activeConversation.value.messages.length <= 3 && activeConversation.value.id.startsWith('new-')) {
    activeConversation.value.title = q.slice(0, 32) + (q.length > 32 ? '...' : '')
  }

  isAiThinking.value = true

  // Simulate rich context-aware AI response
  setTimeout(() => {
    isAiThinking.value = false
    const qLower = q.toLowerCase()
    let aiReply: ChatMessage

    if (qLower.includes('quiz') || qLower.includes('question') || qLower.includes('practice')) {
      aiReply = {
        id: 'msg-' + Date.now(),
        role: 'ai',
        content: `Great! Here is an interactive practice quiz question based on **${selectedCourse.value.chapter}**:`,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        quiz: {
          id: 1,
          question: 'What is the correct syntax for declaring an Arrow Function in modern JavaScript (ES6+)?',
          options: [
            'const myFunc = () => { return "Hello"; };',
            'function => myFunc() { return "Hello"; }',
            'arrow myFunc() { return "Hello"; }',
            'declare func = () => { return "Hello"; }'
          ],
          correctIndex: 0,
          explanation: 'Arrow functions use the syntax: const fnName = (parameters) => { body }. They provide a concise syntax and lexical this binding.'
        }
      }
    } else if (qLower.includes('example') || qLower.includes('code')) {
      aiReply = {
        id: 'msg-' + Date.now(),
        role: 'ai',
        content: `Here is a real-world practical example for **${selectedCourse.value.chapter}**:\n\nIn this example, we create an asynchronous function to fetch student course data safely with error handling:`,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        codeSnippet: {
          language: 'JavaScript',
          code: `// Fetch course progress with async/await\nasync function fetchCourseProgress(studentId, courseId) {\n  try {\n    const response = await fetch(\`/api/student/\${studentId}/courses/\${courseId}\`);\n    if (!response.ok) throw new Error("Course not found");\n    const data = await response.json();\n    return data.progress; // e.g. 53%\n  } catch (error) {\n    console.error("Fetch failed:", error.message);\n    return 0;\n  }\n}\n\n// Calling the function\nfetchCourseProgress("STU2024001", 1).then(p => console.log("Progress:", p));`
        },
        bullets: [
          'async keyword allows the use of await inside the function body',
          'try/catch block prevents unhandled promise rejections',
          'Template literals (`${...}`) construct the API URL dynamically',
          'Returns a clean fallback value (0) on network error'
        ]
      }
    } else if (qLower.includes('summarize') || qLower.includes('summary') || qLower.includes('key points')) {
      aiReply = {
        id: 'msg-' + Date.now(),
        role: 'ai',
        content: `Here is a structured summary of **${selectedCourse.value.chapter}** (${selectedCourse.value.lesson}):`,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        bullets: [
          'Functions are first-class citizens in JavaScript — they can be assigned to variables, passed as arguments, and returned from other functions.',
          'Function Declaration vs Function Expression: Declarations are hoisted, whereas expressions and arrow functions are not.',
          'Default Parameters: You can initialize parameters with default values (e.g. function greet(name = "Student")).',
          'Return Statement: Terminates execution immediately and sends value to the calling context.'
        ]
      }
    } else if (qLower.includes('study plan') || qLower.includes('what should i study') || qLower.includes('today')) {
      aiReply = {
        id: 'msg-' + Date.now(),
        role: 'ai',
        content: `Based on your current progress (${selectedCourse.value.progress}%) and recent activity, here is your **Personalized Study Plan for Today**:`,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        bullets: [
          '1. 🎯 Complete Lesson 3.2: Function Declarations & Arrow Functions (20 min)',
          '2. 💡 Review Function Parameters & Default Arguments (15 min)',
          '3. ❓ Take the 5-question Practice Drill before Chapter Quiz (10 min)'
        ],
        suggestedPrompts: [
          'Would you like me to start the 5-question practice drill now?',
          'Explain Arrow Functions in 2 minutes',
          'Open Course Learning Room'
        ]
      }
    } else {
      aiReply = {
        id: 'msg-' + Date.now(),
        role: 'ai',
        content: `Thank you for your question about **${selectedCourse.value.title}**!\n\nTo understand this concept clearly, let's break it down step-by-step:\n\n1. **Core Principle**: Each programming concept builds directly upon foundational data structures and control flow.\n2. **Best Practice**: Always write clean, modular, and self-documenting code with meaningful variable names.\n3. **Practical Application**: Test small code snippets in the browser console or Node.js to see instant results.`,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        suggestedPrompts: [
          'Can you show me an example?',
          'Generate a quiz question for this',
          'Explain simpler in Khmer'
        ]
      }
    }

    activeConversation.value.messages.push(aiReply)
    scrollToBottom()
  }, 700)
}

// Start New Chat
const startNewChat = () => {
  const newId = 'new-' + Date.now()
  const newConv: Conversation = {
    id: newId,
    title: 'New Conversation',
    courseContext: selectedCourse.value.title,
    timestamp: 'Just now',
    messages: [
      {
        id: 'msg-init',
        role: 'ai',
        content: `Hello ${studentName.value}! 👋 I am your AI Study Assistant for **${selectedCourse.value.title}**.\n\nHow can I help you with **${selectedCourse.value.chapter}** today?`,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      }
    ]
  }
  conversations.value.unshift(newConv)
  activeConversationId.value = newId
}

// Switch Conversation
const switchConversation = (id: string) => {
  activeConversationId.value = id
  scrollToBottom()
}

// Delete Conversation
const deleteConversation = (id: string, e: Event) => {
  e.stopPropagation()
  conversations.value = conversations.value.filter(c => c.id !== id)
  if (conversations.value.length === 0) {
    startNewChat()
  } else {
    activeConversationId.value = conversations.value[0].id
  }
}

// Handle Quiz Submission
const submitQuizAnswer = (msg: ChatMessage, optionIndex: number) => {
  if (!msg.quiz || msg.quiz.isSubmitted) return
  msg.quiz.selectedIndex = optionIndex
  msg.quiz.isSubmitted = true

  const isCorrect = optionIndex === msg.quiz.correctIndex
  setTimeout(() => {
    const feedbackMsg: ChatMessage = {
      id: 'msg-feedback-' + Date.now(),
      role: 'ai',
      content: isCorrect
        ? `🎉 **Awesome job! That is correct!**\n\n${msg.quiz?.explanation}\n\nYou demonstrated strong mastery of this concept.`
        : `⚠️ **Not quite, but good effort!**\n\nThe correct answer is: **${msg.quiz?.options[msg.quiz.correctIndex]}**.\n\n${msg.quiz?.explanation}\n\n*Tip: I have flagged this topic for review in your personalized study plan.*`,
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
    activeConversation.value.messages.push(feedbackMsg)
    scrollToBottom()
  }, 400)
}

// Copy to Clipboard
const copyCode = (code: string, id: string) => {
  navigator.clipboard.writeText(code)
  copiedMessageId.value = id
  setTimeout(() => {
    copiedMessageId.value = null
  }, 2000)
}

// Scroll to bottom
const scrollToBottom = () => {
  nextTick(() => {
    if (chatContainerRef.value) {
      chatContainerRef.value.scrollTop = chatContainerRef.value.scrollHeight
    }
  })
}

// File Attachment handling
const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const f = target.files[0]
    attachedFile.value = {
      name: f.name,
      size: (f.size / 1024).toFixed(1) + ' KB'
    }
    isUploadModalOpen.value = false
  }
}

const removeAttachedFile = () => {
  attachedFile.value = null
}

onMounted(() => {
  scrollToBottom()

  // Check URL parameters for context loading (e.g. from Learning page or Dashboard)
  const urlParams = new URLSearchParams(window.location.search)
  const courseParam = urlParams.get('course')
  const promptParam = urlParams.get('prompt')

  if (courseParam) {
    const match = availableCourses.find(c => c.id.toString() === courseParam || c.title.toLowerCase().includes(courseParam.toLowerCase()))
    if (match) selectedCourse.value = match
  }

  if (promptParam) {
    userQuestion.value = promptParam
    sendMessage()
  }
})
</script>

<template>
  <StudentLayout
    title="AI Study Assistant"
    :breadcrumbs="[
      { label: 'Dashboard', href: '/student/dashboard' },
      { label: 'AI Learning', href: '/student/ai-path/recommended' },
      { label: 'AI Study Assistant' }
    ]"
  >
    <div class="space-y-6 pb-16">
      
      <!-- 1. PAGE HEADER (Title with Robot Icon, Subtitle & Global Search Input) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
              <span>🤖</span>
              <span>AI Study Assistant</span>
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            Your personal AI tutor to help you learn smarter and achieve your goals.
          </p>
        </div>

        <!-- Global Search Input with Ctrl K -->
        <div class="relative w-full md:w-96">
          <div class="relative flex items-center">
            <span class="absolute left-3.5 text-slate-400 pointer-events-none">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              v-model="headerSearch"
              type="text"
              placeholder="Search anything you want to learn..."
              class="w-full pl-10 pr-16 py-2 rounded-xl bg-slate-900/90 border border-slate-800 focus:border-purple-500 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-purple-500/50 shadow-inner transition-all"
            />
            <div class="absolute right-2.5 flex items-center gap-1">
              <button
                v-if="headerSearch"
                @click="headerSearch = ''"
                type="button"
                class="text-slate-400 hover:text-white p-0.5 text-xs cursor-pointer"
              >
                ✕
              </button>
              <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono text-slate-400 bg-slate-800 border border-slate-700 rounded shadow-xs">Ctrl K</kbd>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. MAIN 2-COLUMN LAYOUT (Left 8.5 Cols: Hero + Suggestions + Chat Box | Right 3.5 Cols: Context + Actions + History) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8.5 COLS: MAIN AI TUTOR INTERACTION -->
        <div class="lg:col-span-8 space-y-6">
          
          <!-- HERO AI GREETING BANNER (Matching Screenshot with 3D Robot & Glowing Podium) -->
          <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#170e3b] via-[#101935] to-[#120e2e] border border-purple-800/40 p-6 sm:p-8 shadow-2xl">
            <!-- Background Glow Orbs -->
            <div class="absolute top-0 right-1/4 w-64 h-64 bg-purple-600/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-10 left-10 w-48 h-48 bg-indigo-600/15 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
              
              <!-- Left: 3D Cute Robot Icon & Greeting -->
              <div class="flex items-start gap-4">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-600 to-pink-500 p-0.5 shadow-xl shadow-purple-600/30 shrink-0">
                  <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center text-3xl sm:text-4xl shadow-inner">
                    🤖
                  </div>
                </div>

                <div class="space-y-1.5">
                  <div class="text-xs sm:text-sm font-bold text-purple-300 flex items-center gap-1.5">
                    <span>Hi {{ studentName }}!</span>
                    <span>👋</span>
                  </div>
                  <h2 class="text-lg sm:text-2xl font-black text-white leading-snug">
                    How can I help you with your learning today?
                  </h2>
                  <p class="text-xs text-slate-300 max-w-lg leading-relaxed">
                    Ask me anything about your courses, get explanations, solve problems, or explore new topics.
                  </p>
                </div>
              </div>

              <!-- Right: 3D Glowing AI Graduation Graphic (Matching Screenshot) -->
              <div class="hidden md:flex items-center justify-center shrink-0">
                <div class="relative w-24 h-24 rounded-2xl bg-gradient-to-br from-purple-900/60 to-slate-900 border border-purple-500/40 flex flex-col items-center justify-center shadow-2xl shadow-purple-600/30">
                  <div class="text-3xl">🎓</div>
                  <span class="text-[11px] font-black text-purple-300 mt-1 font-mono tracking-wider">AI</span>
                </div>
              </div>

            </div>
          </div>

          <!-- "YOU CAN TRY ASKING:" PROMPT SUGGESTIONS PILLS -->
          <div class="space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-300">You can try asking:</span>
              <button
                @click="showAllSuggestions = !showAllSuggestions"
                type="button"
                class="text-xs text-purple-400 hover:text-purple-300 font-semibold cursor-pointer"
              >
                {{ showAllSuggestions ? 'Show fewer' : 'View all suggestions' }}
              </button>
            </div>

            <div class="flex flex-wrap gap-2">
              <button
                v-for="sug in displayedSuggestions"
                :key="sug.text"
                @click="handleQuickSuggestion(sug.query)"
                type="button"
                class="px-3.5 py-2 rounded-xl bg-slate-900/90 hover:bg-purple-900/30 hover:border-purple-500/50 border border-slate-800 text-xs text-slate-300 hover:text-white flex items-center gap-2 transition-all cursor-pointer shadow-sm group"
              >
                <span class="text-sm group-hover:scale-110 transition-transform">{{ sug.icon }}</span>
                <span>{{ sug.text }}</span>
              </button>
            </div>
          </div>

          <!-- CHAT WITH AI TUTOR BOX -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col">
            
            <!-- Chat Box Header -->
            <div class="px-6 py-4 border-b border-slate-800/80 flex items-center justify-between bg-slate-950/40">
              <div class="flex items-center gap-2.5">
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></div>
                <h3 class="text-sm font-bold text-white">Chat with AI Tutor</h3>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 font-mono">
                  {{ selectedCourse.title }}
                </span>
              </div>

              <div class="flex items-center gap-2">
                <button
                  @click="startNewChat"
                  type="button"
                  class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-xs flex items-center gap-1.5 cursor-pointer"
                  title="Start New Chat"
                >
                  <span>+</span>
                  <span class="hidden sm:inline">New Chat</span>
                </button>
              </div>
            </div>

            <!-- Messages Area -->
            <div
              ref="chatContainerRef"
              class="p-6 space-y-6 max-h-[500px] min-h-[360px] overflow-y-auto custom-scrollbar bg-slate-950/20"
            >
              
              <div
                v-for="msg in activeConversation.messages"
                :key="msg.id"
                :class="[
                  msg.role === 'user' ? 'justify-end' : 'justify-start',
                  'flex gap-3 items-start'
                ]"
              >
                <!-- AI Avatar -->
                <div
                  v-if="msg.role === 'ai'"
                  class="w-9 h-9 rounded-xl bg-purple-600/30 border border-purple-500/60 flex items-center justify-center text-lg shrink-0 shadow-md shadow-purple-600/20"
                >
                  🤖
                </div>

                <!-- Message Content Bubble -->
                <div
                  :class="[
                    msg.role === 'user'
                      ? 'bg-purple-600 text-white rounded-2xl rounded-tr-xs max-w-xl shadow-lg shadow-purple-600/20'
                      : 'bg-slate-900 border border-slate-800/90 text-slate-200 rounded-2xl rounded-tl-xs max-w-2xl shadow-md',
                    'p-4 space-y-3 text-xs sm:text-sm leading-relaxed'
                  ]"
                >
                  <!-- Text Body -->
                  <div class="whitespace-pre-line">{{ msg.content }}</div>

                  <!-- Code Snippet Box (If present) -->
                  <div
                    v-if="msg.codeSnippet"
                    class="rounded-xl bg-slate-950 border border-slate-800 overflow-hidden shadow-inner text-xs font-mono"
                  >
                    <div class="px-3.5 py-1.5 bg-slate-900/80 border-b border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                      <span>{{ msg.codeSnippet.language }}</span>
                      <button
                        @click="copyCode(msg.codeSnippet.code, msg.id)"
                        type="button"
                        class="hover:text-white flex items-center gap-1 cursor-pointer transition-colors"
                      >
                        <span v-if="copiedMessageId === msg.id" class="text-emerald-400 font-bold">✓ Copied!</span>
                        <span v-else>📋 Copy</span>
                      </button>
                    </div>
                    <pre class="p-3.5 text-slate-200 overflow-x-auto custom-scrollbar"><code>{{ msg.codeSnippet.code }}</code></pre>
                  </div>

                  <!-- Bullets (If present) -->
                  <ul v-if="msg.bullets" class="space-y-1.5 pt-1 text-slate-300">
                    <li v-for="bullet in msg.bullets" :key="bullet" class="flex items-start gap-2">
                      <span class="text-purple-400 font-bold">•</span>
                      <span>{{ bullet }}</span>
                    </li>
                  </ul>

                  <!-- Interactive Quiz Box (If generated) -->
                  <div
                    v-if="msg.quiz"
                    class="p-4 rounded-xl bg-slate-950 border border-purple-500/40 space-y-3 mt-2"
                  >
                    <div class="flex items-center justify-between text-xs text-purple-300 font-bold">
                      <span>📝 Practice Drill</span>
                      <span v-if="msg.quiz.isSubmitted" class="text-[10px] text-slate-400">Submitted</span>
                    </div>

                    <p class="font-bold text-white text-xs sm:text-sm">{{ msg.quiz.question }}</p>

                    <div class="space-y-2">
                      <button
                        v-for="(opt, idx) in msg.quiz.options"
                        :key="opt"
                        @click="submitQuizAnswer(msg, idx)"
                        :disabled="msg.quiz.isSubmitted"
                        type="button"
                        :class="[
                          msg.quiz.isSubmitted && idx === msg.quiz.correctIndex
                            ? 'bg-emerald-950/80 border-emerald-500 text-emerald-300 font-bold'
                            : msg.quiz.isSubmitted && msg.quiz.selectedIndex === idx && idx !== msg.quiz.correctIndex
                            ? 'bg-rose-950/80 border-rose-500 text-rose-300'
                            : 'bg-slate-900 hover:bg-slate-800 border-slate-800 text-slate-300',
                          'w-full p-2.5 rounded-xl border text-left text-xs flex items-center justify-between transition-all cursor-pointer disabled:cursor-default'
                        ]"
                      >
                        <span>{{ opt }}</span>
                        <span v-if="msg.quiz.isSubmitted && idx === msg.quiz.correctIndex" class="text-emerald-400">✓ Correct</span>
                        <span v-else-if="msg.quiz.isSubmitted && msg.quiz.selectedIndex === idx && idx !== msg.quiz.correctIndex" class="text-rose-400">✗ Wrong</span>
                      </button>
                    </div>
                  </div>

                  <!-- Suggested Prompts / Chips -->
                  <div v-if="msg.suggestedPrompts" class="pt-2 border-t border-slate-800 space-y-1.5">
                    <p class="text-[11px] text-slate-400">{{ msg.suggestedPrompts[0] }}</p>
                    <div class="flex flex-wrap gap-1.5">
                      <button
                        v-for="p in msg.suggestedPrompts.slice(1)"
                        :key="p"
                        @click="handleQuickSuggestion(p)"
                        type="button"
                        class="px-2.5 py-1 rounded-lg bg-purple-900/30 hover:bg-purple-800/50 border border-purple-500/30 text-[11px] text-purple-300 hover:text-white cursor-pointer"
                      >
                        {{ p }}
                      </button>
                    </div>
                  </div>

                  <!-- Message Footer (Timestamp & Feedback) -->
                  <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1">
                    <div v-if="msg.role === 'ai'" class="flex items-center gap-2">
                      <button
                        @click="msg.liked = !msg.liked; msg.disliked = false"
                        type="button"
                        :class="msg.liked ? 'text-purple-400 font-bold' : 'hover:text-slate-300'"
                        class="cursor-pointer"
                        title="Helpful"
                      >
                        👍
                      </button>
                      <button
                        @click="msg.disliked = !msg.disliked; msg.liked = false"
                        type="button"
                        :class="msg.disliked ? 'text-rose-400 font-bold' : 'hover:text-slate-300'"
                        class="cursor-pointer"
                        title="Not helpful"
                      >
                        👎
                      </button>
                      <button
                        @click="copyCode(msg.content, msg.id)"
                        type="button"
                        class="hover:text-slate-300 cursor-pointer"
                        title="Copy message"
                      >
                        📋
                      </button>
                    </div>
                    <span :class="msg.role === 'user' ? 'text-purple-200' : 'text-slate-500'">{{ msg.timestamp }}</span>
                  </div>

                </div>

                <!-- User Avatar -->
                <div
                  v-if="msg.role === 'user'"
                  class="w-9 h-9 rounded-xl bg-slate-800 border border-purple-500/40 flex items-center justify-center text-xs font-bold text-white shrink-0 overflow-hidden shadow-md"
                >
                  <img
                    src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80"
                    alt="Student"
                    class="w-full h-full object-cover"
                  />
                </div>
              </div>

              <!-- AI Thinking Indicator -->
              <div v-if="isAiThinking" class="flex gap-3 items-start">
                <div class="w-9 h-9 rounded-xl bg-purple-600/30 border border-purple-500/60 flex items-center justify-center text-lg shrink-0">
                  🤖
                </div>
                <div class="bg-slate-900 border border-slate-800 text-slate-400 rounded-2xl rounded-tl-xs p-4 flex items-center gap-3 text-xs shadow-md">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce"></span>
                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce [animation-delay:0.4s]"></span>
                  </div>
                  <span>AI is thinking & analyzing course context...</span>
                </div>
              </div>

            </div>

            <!-- Attached File Notification Chip (If any) -->
            <div v-if="attachedFile" class="px-6 py-2 bg-slate-900/90 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-300">
              <div class="flex items-center gap-2">
                <span>📎 Attached:</span>
                <span class="font-bold text-purple-300">{{ attachedFile.name }} ({{ attachedFile.size }})</span>
              </div>
              <button @click="removeAttachedFile" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
            </div>

            <!-- Chat Input Area -->
            <div class="p-4 bg-slate-950/90 border-t border-slate-800/80 space-y-2">
              <form @submit.prevent="sendMessage" class="relative flex items-center gap-2">
                
                <!-- Attachment Button -->
                <button
                  @click="isUploadModalOpen = true"
                  type="button"
                  class="p-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-purple-300 border border-slate-800 transition-colors cursor-pointer"
                  title="Attach note or document"
                >
                  📎
                </button>

                <!-- Text Input -->
                <input
                  v-model="userQuestion"
                  type="text"
                  placeholder="Type your question here..."
                  :disabled="isAiThinking"
                  class="flex-1 px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 focus:border-purple-500 text-xs sm:text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-purple-500/50 shadow-inner"
                />

                <!-- Send Button -->
                <button
                  type="submit"
                  :disabled="!userQuestion.trim() || isAiThinking"
                  class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 flex items-center justify-center gap-1.5 transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                >
                  <span>Send</span>
                  <span>✈</span>
                </button>
              </form>

              <p class="text-[10px] text-slate-500 text-center">
                AI may make mistakes. Please verify important information.
              </p>
            </div>

          </div>

        </div>

        <!-- RIGHT 3.5 COLS: CONTEXT, QUICK ACTIONS & RECENT CONVERSATIONS -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- WIDGET 1: YOUR LEARNING CONTEXT (Matching Screenshot) -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-4">
            
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">
                Your Learning Context
              </h3>
              <!-- Switch Course Dropdown -->
              <div class="relative">
                <button
                  @click="isCourseDropdownOpen = !isCourseDropdownOpen"
                  type="button"
                  class="text-[11px] text-purple-400 hover:text-purple-300 font-semibold flex items-center gap-1 cursor-pointer"
                >
                  <span>Switch</span>
                  <span>▼</span>
                </button>

                <div
                  v-if="isCourseDropdownOpen"
                  class="absolute right-0 top-full mt-2 w-64 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl p-2 z-30 space-y-1"
                >
                  <button
                    v-for="c in availableCourses"
                    :key="c.id"
                    @click="selectedCourse = c; isCourseDropdownOpen = false"
                    type="button"
                    :class="[
                      selectedCourse.id === c.id ? 'bg-purple-600/20 text-purple-300 font-bold border border-purple-500/30' : 'text-slate-300 hover:bg-slate-800',
                      'w-full text-left p-2 rounded-xl text-xs flex items-center gap-2 cursor-pointer'
                    ]"
                  >
                    <span :class="[c.iconBg, 'w-6 h-6 rounded-lg bg-gradient-to-br text-slate-950 font-black text-[9px] flex items-center justify-center font-mono']">
                      {{ c.icon }}
                    </span>
                    <span class="truncate">{{ c.title }}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Current Course Highlight Box -->
            <div class="space-y-3">
              <p class="text-[11px] font-bold text-slate-400 uppercase">Current Course</p>
              
              <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 space-y-2.5">
                <div class="flex items-center gap-3">
                  <div :class="[selectedCourse.iconBg, 'w-10 h-10 rounded-xl bg-gradient-to-br text-slate-950 font-black text-xs flex items-center justify-center font-mono shadow-md shrink-0']">
                    {{ selectedCourse.icon }}
                  </div>
                  <div class="min-w-0">
                    <h4 class="text-xs font-bold text-white truncate">{{ selectedCourse.title }}</h4>
                    <p class="text-[10px] text-slate-400 truncate">{{ selectedCourse.chapter }}</p>
                  </div>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1">
                  <div class="flex items-center justify-between text-[10px]">
                    <span class="text-slate-400">Progress</span>
                    <span class="text-emerald-400 font-bold">{{ selectedCourse.progress }}%</span>
                  </div>
                  <div class="w-full h-1.5 rounded-full bg-slate-900 overflow-hidden">
                    <div
                      class="h-full bg-gradient-to-r from-purple-500 to-emerald-400 rounded-full transition-all duration-500"
                      :style="{ width: `${selectedCourse.progress}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recent Activity List -->
            <div class="space-y-2.5 pt-2 border-t border-slate-800/80">
              <p class="text-[11px] font-bold text-slate-400 uppercase">Recent Activity</p>
              <div class="space-y-2 text-xs">
                <div
                  v-for="act in selectedCourse.recentActivity"
                  :key="act.text"
                  class="flex items-start gap-2 text-slate-300"
                >
                  <span class="text-purple-400 font-bold">•</span>
                  <div>
                    <p class="text-xs text-white">{{ act.text }}</p>
                    <p class="text-[10px] text-slate-500">{{ act.time }}</p>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- WIDGET 2: QUICK ACTIONS (Matching Screenshot) -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider border-b border-slate-800/80 pb-3">
              Quick Actions
            </h3>

            <div class="space-y-2">
              
              <!-- 1. Explain This Lesson -->
              <button
                @click="handleQuickSuggestion(`Explain the current lesson (${selectedCourse.chapter}) in simple terms with examples.`)"
                type="button"
                class="w-full p-3 rounded-2xl bg-slate-950/80 hover:bg-slate-900 border border-slate-800 hover:border-purple-500/40 text-left flex items-center justify-between transition-all group cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <span class="text-base">📄</span>
                  <div>
                    <p class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors">Explain This Lesson</p>
                    <p class="text-[10px] text-slate-400">Get detailed explanation</p>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-white transition-colors">›</span>
              </button>

              <!-- 2. Generate Quiz -->
              <button
                @click="handleQuickSuggestion(`Generate 3 interactive practice quiz questions on ${selectedCourse.chapter}.`)"
                type="button"
                class="w-full p-3 rounded-2xl bg-slate-950/80 hover:bg-slate-900 border border-slate-800 hover:border-purple-500/40 text-left flex items-center justify-between transition-all group cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <span class="text-base">❓</span>
                  <div>
                    <p class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors">Generate Quiz</p>
                    <p class="text-[10px] text-slate-400">Create practice questions</p>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-white transition-colors">›</span>
              </button>

              <!-- 3. Summarize Topic -->
              <button
                @click="handleQuickSuggestion(`Summarize the key points of ${selectedCourse.chapter}.`)"
                type="button"
                class="w-full p-3 rounded-2xl bg-slate-950/80 hover:bg-slate-900 border border-slate-800 hover:border-purple-500/40 text-left flex items-center justify-between transition-all group cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <span class="text-base">📑</span>
                  <div>
                    <p class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors">Summarize Topic</p>
                    <p class="text-[10px] text-slate-400">Get key points summary</p>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-white transition-colors">›</span>
              </button>

              <!-- 4. Give Me Examples -->
              <button
                @click="handleQuickSuggestion(`Give me real practical code examples for ${selectedCourse.chapter}.`)"
                type="button"
                class="w-full p-3 rounded-2xl bg-slate-950/80 hover:bg-slate-900 border border-slate-800 hover:border-purple-500/40 text-left flex items-center justify-between transition-all group cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <span class="text-base">💡</span>
                  <div>
                    <p class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors">Give Me Examples</p>
                    <p class="text-[10px] text-slate-400">Show practical examples</p>
                  </div>
                </div>
                <span class="text-slate-500 group-hover:text-white transition-colors">›</span>
              </button>

            </div>
          </div>

          <!-- WIDGET 3: RECENT CONVERSATIONS (Matching Screenshot) -->
          <div class="bg-[#0e1424] border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
              <h3 class="text-xs font-bold text-white uppercase tracking-wider">
                Recent Conversations
              </h3>
              <button
                @click="startNewChat"
                type="button"
                class="text-[11px] text-purple-400 hover:text-purple-300 font-semibold cursor-pointer"
              >
                + New
              </button>
            </div>

            <div class="space-y-2">
              <div
                v-for="conv in conversations"
                :key="conv.id"
                @click="switchConversation(conv.id)"
                :class="[
                  conv.id === activeConversationId
                    ? 'bg-purple-950/60 border-purple-500/60 shadow-lg shadow-purple-900/20'
                    : 'bg-slate-950/80 hover:bg-slate-900 border-slate-800',
                  'p-3 rounded-2xl border flex items-center justify-between transition-all cursor-pointer group'
                ]"
              >
                <div class="min-w-0 pr-2">
                  <p :class="conv.id === activeConversationId ? 'text-white font-bold' : 'text-slate-300 group-hover:text-white'" class="text-xs truncate">
                    {{ conv.title }}
                  </p>
                  <p class="text-[10px] text-slate-400">{{ conv.timestamp }}</p>
                </div>

                <button
                  @click="deleteConversation(conv.id, $event)"
                  type="button"
                  class="text-slate-500 hover:text-rose-400 p-1 text-xs opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
                  title="Delete chat"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- 1. FILE UPLOAD MODAL -->
    <div
      v-if="isUploadModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isUploadModalOpen = false"
    >
      <div
        class="relative w-full max-w-md bg-[#0e1424] border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isUploadModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-white text-sm cursor-pointer"
        >
          ✕
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/40 text-purple-400 flex items-center justify-center text-lg">
            📎
          </div>
          <div>
            <h3 class="text-base font-bold text-white">Attach Study Material</h3>
            <p class="text-xs text-slate-400">Ask AI questions about your notes or PDFs</p>
          </div>
        </div>

        <label class="block p-6 border-2 border-dashed border-slate-700 hover:border-purple-500 rounded-2xl bg-slate-950/80 text-center cursor-pointer transition-colors">
          <input
            type="file"
            accept=".pdf,.txt,.docx,.js,.py,.html,.css"
            @change="handleFileUpload"
            class="hidden"
          />
          <div class="space-y-2">
            <span class="text-3xl">📄</span>
            <p class="text-xs font-bold text-white">Click to browse or drag file here</p>
            <p class="text-[10px] text-slate-500">Supports PDF, DOCX, TXT, Code files (Max 15MB)</p>
          </div>
        </label>
      </div>
    </div>

  </StudentLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #475569;
}
</style>
