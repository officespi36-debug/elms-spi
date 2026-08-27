<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import GlobalToast from '@/Components/GlobalToast.vue'
import {
  saveCourseForOffline,
  getOfflineCourse,
  isCourseCachedOffline,
  saveProgressOffline,
  flushQueue
} from '@/offline/sync'

const props = defineProps<{
  course: any
  progress: Record<string | number, any>
  enrollment?: any
  initialLessonId?: number | string | null
  courseProgressPercent?: number
  completedLessonsCount?: number
  totalLessonsCount?: number
}>()

const { isDark, toggleTheme } = useTheme()

// --- State Management ---
const isSidebarOpen = ref(true)
const isTheaterMode = ref(false)
const isFullscreen = ref(false)
const playlistSearch = ref('')
const activeTab = ref<'overview' | 'resources' | 'qa' | 'aiTutor' | 'notes'>('overview')

// Sequential vs Open Learning Mode
const isSequentialMode = ref(false)

// All Lessons Flattened
const allLessons = computed(() => {
  const list: any[] = []
  if (props.course?.modules && props.course.modules.length > 0) {
    props.course.modules.forEach((mod: any, mIdx: number) => {
      if (mod.lessons && mod.lessons.length > 0) {
        mod.lessons.forEach((l: any, lIdx: number) => {
          list.push({
            ...l,
            moduleTitle: mod.title || `Module ${mIdx + 1}`,
            moduleId: mod.id,
            globalIndex: list.length + 1,
          })
        })
      }
    })
  }

  // Fallback demo curriculum if course has no lessons populated
  if (list.length === 0) {
    return [
      {
        id: 101,
        title: '01. សេចក្តីផ្តើម និងទិដ្ឋភាពទូទៅនៃមុខវិជ្ជា (Introduction)',
        type: 'video',
        video_url: 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        duration_seconds: 720,
        content: '<p>សូមស្វាគមន៍មកកាន់វគ្គសិក្សា! នៅក្នុងមេរៀនដំបូងនេះ យើងនឹងស្វែងយល់ពីគោលបំណងចម្បង ឧបករណ៍ដែលត្រូវប្រើប្រាស់ និងរបៀបរៀបចំ Environment សម្រាប់ការសិក្សាប្រកបដោយប្រសិទ្ធភាពខ្ពស់។</p>',
        moduleTitle: 'Module 1: Orientation & Basics',
        moduleId: 1,
        globalIndex: 1,
      },
      {
        id: 102,
        title: '02. មូលដ្ឋានគ្រឹះ និងគោលការណ៍សំខាន់ៗ (Core Fundamentals)',
        type: 'pdf',
        file_path: 'sample.pdf',
        file_url: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
        duration_seconds: 900,
        content: '<p>ឯកសារ PDF នេះមានសង្ខេបនូវរាល់រូបមន្ត និង Synatx សំខាន់ៗដែលអ្នកត្រូវចងចាំ។ សូមទាញយក និងអានឱ្យបានម៉ត់ចត់មុននឹងបន្តទៅមេរៀនបន្ទាប់។</p>',
        moduleTitle: 'Module 1: Orientation & Basics',
        moduleId: 1,
        globalIndex: 2,
      },
      {
        id: 103,
        title: '03. ការអនុវត្តផ្ទាល់ Lab Simulator & Coding Practice',
        type: 'lab',
        duration_seconds: 1200,
        content: '<p>បន្ទប់អនុវត្តជាក់ស្តែង! សាកល្បងសរសេរកូដ និងដំណើរការលទ្ធផលក្នុង Interactive Code Sandbox ខាងក្រោម។</p>',
        moduleTitle: 'Module 2: Practical Lab & Exercises',
        moduleId: 2,
        globalIndex: 3,
      },
      {
        id: 104,
        title: '04. ស្លាយសង្ខេបមេរៀន និងគន្លឹះប្រឡង (Summary Slides)',
        type: 'slide',
        duration_seconds: 600,
        content: '<p>ស្លាយបទបង្ហាញផ្លូវការ ផ្ដល់នូវទិដ្ឋភាពសង្ខេប និងគន្លឹះសម្រាប់ឆ្លើយសំណួរ Quiz ប្រចាំជំពូក។</p>',
        moduleTitle: 'Module 2: Practical Lab & Exercises',
        moduleId: 2,
        globalIndex: 4,
      }
    ]
  }

  return list
})

// Current Active Lesson
const activeLessonId = ref<number | string>(
  props.initialLessonId || (allLessons.value[0]?.id ?? 1)
)

const activeLesson = computed(() => {
  return allLessons.value.find(l => String(l.id) === String(activeLessonId.value)) || allLessons.value[0]
})

// Current Lesson Index
const activeLessonIndex = computed(() => {
  return allLessons.value.findIndex(l => String(l.id) === String(activeLesson.value?.id))
})

// Progress Store (Reactive local copy)
const localProgress = ref<Record<string | number, any>>({ ...props.progress })

const isLessonCompleted = (lessonId: number | string) => {
  const p = localProgress.value[lessonId]
  return p && (p.percent >= 90 || p.completed_at)
}

// Check if a lesson is locked in sequential mode
const isLessonLocked = (lesson: any) => {
  if (!isSequentialMode.value) return false
  const idx = allLessons.value.findIndex(l => String(l.id) === String(lesson.id))
  if (idx <= 0) return false
  // Check if previous lesson is completed
  const prevLesson = allLessons.value[idx - 1]
  return !isLessonCompleted(prevLesson.id)
}

// Calculate Overall Progress
const completedCount = computed(() => {
  return allLessons.value.filter(l => isLessonCompleted(l.id)).length
})

const overallProgressPercent = computed(() => {
  if (allLessons.value.length === 0) return 0
  return Math.round((completedCount.value / allLessons.value.length) * 100)
})

// Video Player Controls & State
const videoPlayerRef = ref<HTMLVideoElement | null>(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const playbackSpeed = ref(1.0)
const isMuted = ref(false)
const volume = ref(1)

const speeds = [0.5, 0.75, 1.0, 1.25, 1.5, 2.0]

const setPlaybackSpeed = (spd: number) => {
  playbackSpeed.value = spd
  if (videoPlayerRef.value) {
    videoPlayerRef.value.playbackRate = spd
  }
}

// Auto Progress Dispatch (Works Online and Offline)
const updateProgressToServer = (percent: number, seconds: number, isComplete: boolean = false) => {
  if (!activeLesson.value?.id) return

  const lessonId = activeLesson.value.id
  const courseId = Number(props.course?.id || 1)

  // 1. Immediately save progress offline in IndexedDB
  saveProgressOffline(courseId, Number(lessonId), isComplete)

  if (!localProgress.value[lessonId]) {
    localProgress.value[lessonId] = {}
  }
  localProgress.value[lessonId].percent = isComplete ? 100 : percent
  if (isComplete) {
    localProgress.value[lessonId].completed_at = new Date().toISOString()
  }

  // 2. If online, also send to server directly
  if (typeof navigator !== 'undefined' && navigator.onLine) {
    fetch(`/student/learn/progress/${lessonId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': (document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        percent: isComplete ? 100 : percent,
        seconds: seconds,
        is_completed: isComplete
      })
    })
      .then(res => res.json())
      .then(data => {
        if (data.ok) {
          if (!localProgress.value[lessonId]) {
            localProgress.value[lessonId] = {}
          }
          localProgress.value[lessonId].percent = data.percent
          if (data.is_completed) {
            localProgress.value[lessonId].completed_at = new Date().toISOString()
          }
        }
      })
      .catch(err => console.log('Progress tracking error:', err))
  }
}

const onTimeUpdate = () => {
  if (!videoPlayerRef.value) return
  currentTime.value = videoPlayerRef.value.currentTime
  duration.value = videoPlayerRef.value.duration || 1

  const currentPercent = Math.round((currentTime.value / duration.value) * 100)

  // Auto complete if >= 90% watched
  if (currentPercent >= 90 && !isLessonCompleted(activeLesson.value.id)) {
    updateProgressToServer(100, Math.round(currentTime.value), true)
  }
}

const onVideoEnded = () => {
  isPlaying.value = false
  updateProgressToServer(100, Math.round(duration.value), true)
  // Show next lesson prompt or auto proceed
}

// Select Lesson Action
const selectLesson = (lesson: any) => {
  if (isLessonLocked(lesson)) return

  // Save current progress before switching
  if (activeLesson.value?.id && currentTime.value > 0) {
    const currentPercent = duration.value > 0 ? Math.round((currentTime.value / duration.value) * 100) : 0
    updateProgressToServer(currentPercent, Math.round(currentTime.value))
  }

  activeLessonId.value = lesson.id
  currentTime.value = localProgress.value[lesson.id]?.seconds_watched || 0

  // Update browser URL silently
  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href)
    url.searchParams.set('lesson_id', String(lesson.id))
    window.history.replaceState({}, '', url.toString())
  }
}

// Navigation Previous / Next
const hasPrevious = computed(() => activeLessonIndex.value > 0)
const hasNext = computed(() => {
  if (activeLessonIndex.value < allLessons.value.length - 1) {
    const nextLesson = allLessons.value[activeLessonIndex.value + 1]
    return !isLessonLocked(nextLesson)
  }
  return false
})

const goToPrevious = () => {
  if (hasPrevious.value) {
    selectLesson(allLessons.value[activeLessonIndex.value - 1])
  }
}

const goToNext = () => {
  if (hasNext.value) {
    selectLesson(allLessons.value[activeLessonIndex.value + 1])
  }
}

const markCurrentCompleted = () => {
  updateProgressToServer(100, Math.round(currentTime.value || 60), true)
  if (hasNext.value) {
    setTimeout(() => goToNext(), 600)
  }
}

// Interactive Code Lab Sandbox State
const codeLanguage = ref('c')
const studentCode = ref(
`#include <stdio.h>

int main() {
    printf("Hello SPI AI-ELMS! Learning in Focus Mode.\\n");
    int a = 15, b = 4;
    printf("Remainder: %d %% %d = %d\\n", a, b, a % b);
    return 0;
}`
)
const codeOutput = ref('')
const isRunningCode = ref(false)

const runCode = () => {
  isRunningCode.value = true
  codeOutput.value = 'Compiling and executing code...\n'
  setTimeout(() => {
    isRunningCode.value = false
    codeOutput.value = `[OUTPUT - Execution Succeeded (0.04s)]\nHello SPI AI-ELMS! Learning in Focus Mode.\nRemainder: 15 % 4 = 3\n\nProcess finished with exit code 0`
    updateProgressToServer(100, 30, true)
  }, 900)
}

// Interactive Slide State
const currentSlideIndex = ref(1)
const totalSlides = ref(8)

// Q&A Discussion State
interface DiscussionItem {
  id: number | string
  userName: string
  avatar: string
  content: string
  time: string
  isTeacherReply?: boolean
  reply?: string | null
}

const discussionsList = ref<DiscussionItem[]>([
  {
    id: 1,
    userName: 'Channak Meas',
    avatar: 'https://ui-avatars.com/api/?name=Channak&background=6366f1&color=fff',
    content: 'លោកគ្រូ តើ Modulo Operator (%) អាចប្រើជាមួយ floating point numbers (float/double) បានដែរឬទេ?',
    time: '2 ម៉ោងមុន',
    isTeacherReply: true,
    reply: 'សួស្តីប្អូន! ក្នុង C Operator % ប្រើបានតែជាមួយ Integers ប៉ុណ្ណោះ។ ប្រសិនបើចង់គណនាសំណល់លើ float ត្រូវប្រើ function fmod() ពីបណ្ណាល័យ <math.h>។'
  }
])
const newQuestionText = ref('')
const isSubmittingQuestion = ref(false)

const submitQuestion = () => {
  if (!newQuestionText.value.trim()) return
  isSubmittingQuestion.value = true

  fetch(`/student/learn/discussion/${activeLesson.value.id}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ content: newQuestionText.value })
  })
    .then(res => res.json())
    .then(data => {
      isSubmittingQuestion.value = false
      discussionsList.value.unshift({
        id: Date.now(),
        userName: 'You (Student)',
        avatar: 'https://ui-avatars.com/api/?name=Student&background=10b981&color=fff',
        content: newQuestionText.value,
        time: 'អម្បាញ់មិញ',
        isTeacherReply: false,
        reply: null
      })
      newQuestionText.value = ''
    })
    .catch(() => {
      isSubmittingQuestion.value = false
    })
}

// AI Smart Tutor Chat State
const aiMessages = ref<{ role: 'user' | 'assistant'; text: string; time: string }[]>([
  {
    role: 'assistant',
    text: `សួស្តី! ខ្ញុំជា **SPI AI Tutor 24/7** សម្រាប់មេរៀននេះ។ តើអ្នកមានចម្ងល់ត្រង់ណា ឬចង់ឱ្យខ្ញុំពន្យល់ពីចំណុចណាខ្លះ?`,
    time: 'ឥឡូវនេះ'
  }
])
const aiInputText = ref('')
const isAiLoading = ref(false)

const askAiTutor = () => {
  if (!aiInputText.value.trim() || isAiLoading.value) return
  const q = aiInputText.value
  aiMessages.value.push({ role: 'user', text: q, time: 'ឥឡូវនេះ' })
  aiInputText.value = ''
  isAiLoading.value = true

  fetch(`/student/learn/ai-tutor/${activeLesson.value.id}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': (document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ question: q })
  })
    .then(res => res.json())
    .then(data => {
      isAiLoading.value = false
      aiMessages.value.push({
        role: 'assistant',
        text: data.reply || 'ខ្ញុំបានយល់ពីសំណួររបស់អ្នក! សូមអនុវត្តតាមជំហានក្នុងមេរៀនដើម្បីផ្ទៀងផ្ទាត់។',
        time: data.time || 'ឥឡូវនេះ'
      })
    })
    .catch(() => {
      isAiLoading.value = false
      aiMessages.value.push({
        role: 'assistant',
        text: '💡 **ការណែនាំសង្ខេប៖** សូមពិនិត្យមើល Syntax និងអានសម្រង់ទ្រឹស្តីក្នុង Tab Overview បន្ថែម។',
        time: 'ឥឡូវនេះ'
      })
    })
}

// Personal Notes State
interface PersonalNote {
  id: number | string
  text: string
  time: string
  savedAt: string
}

const personalNotes = ref<PersonalNote[]>([
  { id: 1, text: 'Operator %= ប្រើសម្រាប់គណនា Remainder (សំណល់នៃការចែក)', time: '04:15', savedAt: '2026-08-20' }
])
const noteInput = ref('')

const savePersonalNote = () => {
  if (!noteInput.value.trim()) return
  const currentTimestamp = formatTime(currentTime.value)
  personalNotes.value.push({
    id: Date.now(),
    text: noteInput.value,
    time: currentTimestamp,
    savedAt: new Date().toLocaleDateString()
  })
  noteInput.value = ''
}

// AI Smart Next Steps Recommendation State
const aiNextSteps = ref<any>(null)
const isNextStepsLoading = ref(false)

const loadAiNextSteps = async () => {
  isNextStepsLoading.value = true
  try {
    const res = await fetch('/api/ai/recommendation', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        major: props.course?.major?.name || 'Information Technology',
        lesson_title: activeLesson.value?.title || props.course?.title,
        quiz_score: 85
      })
    })
    const data = await res.json()
    if (data.success && data.recommendation) {
      aiNextSteps.value = data.recommendation
    }
  } catch (e) {
    aiNextSteps.value = {
      major: props.course?.major?.name || 'Information Technology',
      next_topic: 'Advanced Practical Implementation & Lab Exercises',
      practice_project: 'Complete the attached practice exercises to solidify theoretical concepts.',
      tech_badge: props.course?.major?.name || 'SPI Academic Module',
      difficulty_level: 'Recommended Next Step'
    }
  } finally {
    isNextStepsLoading.value = false
  }
}

watch(activeLessonId, () => {
  loadAiNextSteps()
})

// Fullscreen API Helper
const togglePlayerFullscreen = () => {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(err => console.log(err))
    isFullscreen.value = true
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen()
      isFullscreen.value = false
    }
  }
}

// Time Format Helper
const formatTime = (secs: number) => {
  const m = Math.floor(secs / 60)
  const s = Math.floor(secs % 60)
  return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`
}

// Keyboard shortcuts (Space = Play/Pause, Left/Right = Seek/Navigate, F = Fullscreen)
const handlePlayerKeydown = (e: KeyboardEvent) => {
  const target = e.target as HTMLElement
  if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA')) return

  if (e.code === 'Space') {
    e.preventDefault()
    if (videoPlayerRef.value) {
      if (videoPlayerRef.value.paused) {
        videoPlayerRef.value.play()
        isPlaying.value = true
      } else {
        videoPlayerRef.value.pause()
        isPlaying.value = false
      }
    }
  } else if (e.key.toLowerCase() === 'f') {
    e.preventDefault()
    togglePlayerFullscreen()
  } else if (e.key.toLowerCase() === 't') {
    e.preventDefault()
    isTheaterMode.value = !isTheaterMode.value
  } else if (e.key === 'ArrowRight' && (e.ctrlKey || e.metaKey)) {
    e.preventDefault()
    goToNext()
  } else if (e.key === 'ArrowLeft' && (e.ctrlKey || e.metaKey)) {
    e.preventDefault()
    goToPrevious()
  }
}

// --- Offline Learning & PWA Synchronization State ---
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true)
const isCourseSavedOffline = ref(false)
const isDownloadingOffline = ref(false)
const downloadProgress = ref(0)
const downloadStatusText = ref('')
const offlineSyncToast = ref('')

const checkOfflineStatus = async () => {
  if (props.course?.id) {
    isCourseSavedOffline.value = await isCourseCachedOffline(props.course.id)
  }
}

const handleDownloadCourseOffline = async () => {
  if (isDownloadingOffline.value || !props.course) return
  isDownloadingOffline.value = true
  downloadProgress.value = 0
  downloadStatusText.value = 'កំពុងរៀបចំ...'

  const success = await saveCourseForOffline(props.course, (percent, status) => {
    downloadProgress.value = percent
    downloadStatusText.value = status
  })

  if (success) {
    isCourseSavedOffline.value = true
    offlineSyncToast.value = 'បានរក្សាទុកវគ្គសិក្សាក្នុងម៉ាស៊ីនរួចរាល់! អ្នកអាចរៀនបានទោះគ្មាន Internet។'
    setTimeout(() => {
      offlineSyncToast.value = ''
      isDownloadingOffline.value = false
    }, 3500)
  } else {
    isDownloadingOffline.value = false
  }
}

const handleOnlineStatusChange = () => {
  isOnline.value = navigator.onLine
  if (navigator.onLine) {
    flushQueue().then(({ success }) => {
      if (success > 0) {
        offlineSyncToast.value = `បានភ្ជាប់អ៊ីនធឺណិតវិញ! វឌ្ឍនភាពសិក្សា (${success} មេរៀន) ត្រូវបាន Sync ទៅ Server ដោយជោគជ័យ។`
        setTimeout(() => { offlineSyncToast.value = '' }, 4000)
      }
    })
  }
}

onMounted(async () => {
  window.addEventListener('keydown', handlePlayerKeydown)
  window.addEventListener('online', handleOnlineStatusChange)
  window.addEventListener('offline', handleOnlineStatusChange)
  loadAiNextSteps()
  await checkOfflineStatus()

  // Auto-cache course structure if online for instantaneous offline availability
  if (props.course && typeof navigator !== 'undefined' && navigator.onLine && !isCourseSavedOffline.value) {
    saveCourseForOffline(props.course).then(cached => {
      if (cached) isCourseSavedOffline.value = true
    })
  }
})

onUnmounted(() => {
  window.removeEventListener('keydown', handlePlayerKeydown)
  window.removeEventListener('online', handleOnlineStatusChange)
  window.removeEventListener('offline', handleOnlineStatusChange)
})
</script>

<template>
  <Head :title="`${course.title} — Focus Player`" />
  <GlobalToast />

  <!-- 100vw / 100vh Fullscreen Focus Mode Wrapper -->
  <div class="h-screen w-screen overflow-hidden flex flex-col bg-slate-950 text-slate-100 font-sans selection:bg-indigo-500/30">
    
    <!-- ═════════════════════════════════════════════════════════════════════════════ -->
    <!-- 1. TOP NAVIGATION BAR (Focus Mode Header) -->
    <!-- ═════════════════════════════════════════════════════════════════════════════ -->
    <header class="h-16 shrink-0 bg-slate-900/95 backdrop-blur-xl border-b border-slate-800 px-4 md:px-6 flex items-center justify-between gap-4 z-40">
      
      <!-- Left: Back Button & Course Title -->
      <div class="flex items-center gap-3 min-w-0 flex-1">
        <Link
          href="/student/my-courses/enrolled"
          title="Exit Focus Mode (ត្រឡប់ទៅ My Courses)"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 text-xs font-bold transition-all shrink-0 cursor-pointer shadow-sm group"
        >
          <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span class="hidden sm:inline">My Courses</span>
        </Link>

        <div class="h-6 w-px bg-slate-800 hidden sm:block"></div>

        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h1 class="text-xs sm:text-sm font-bold text-white truncate max-w-[200px] md:max-w-[400px] lg:max-w-[600px]">
              {{ course.title }}
            </h1>
            <span class="hidden md:inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 shrink-0">
              {{ course.major?.name || 'General Course' }}
            </span>
          </div>
          <p class="text-[10px] text-slate-400 truncate">
            👨‍🏫 {{ course.teacher?.name || 'Instructor' }} • Lesson {{ activeLessonIndex + 1 }}/{{ allLessons.length }}
          </p>
        </div>
      </div>

      <!-- Center / Right: Progress Bar & Prev/Next & Controls -->
      <div class="flex items-center gap-3 shrink-0">
        
        <!-- Course Overall Progress Meter -->
        <div class="hidden lg:flex items-center gap-3 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/80">
          <div class="text-right">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Overall Progress</p>
            <p class="text-xs font-extrabold text-indigo-400">{{ overallProgressPercent }}% ({{ completedCount }}/{{ allLessons.length }})</p>
          </div>
          <div class="w-20 h-2 bg-slate-950 rounded-full overflow-hidden border border-slate-700">
            <div
              class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full transition-all duration-500"
              :style="{ width: overallProgressPercent + '%' }"
            ></div>
          </div>
        </div>

        <!-- Prev / Next Lesson Navigation Buttons -->
        <div class="flex items-center bg-slate-800 border border-slate-700 rounded-xl p-0.5 shadow-sm">
          <button
            @click="goToPrevious"
            :disabled="!hasPrevious"
            title="Previous Lesson (Ctrl + ←)"
            :class="[
              hasPrevious ? 'text-slate-200 hover:text-white hover:bg-slate-700 cursor-pointer' : 'text-slate-600 cursor-not-allowed',
              'px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1'
            ]"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            <span class="hidden md:inline">Prev</span>
          </button>

          <div class="h-4 w-px bg-slate-700"></div>

          <button
            @click="goToNext"
            :disabled="!hasNext"
            title="Next Lesson (Ctrl + →)"
            :class="[
              hasNext ? 'text-slate-200 hover:text-white hover:bg-slate-700 cursor-pointer' : 'text-slate-600 cursor-not-allowed',
              'px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1'
            ]"
          >
            <span class="hidden md:inline">Next</span>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <!-- Offline Download & Ready Pill -->
        <button
          type="button"
          @click="handleDownloadCourseOffline"
          :disabled="isDownloadingOffline || isCourseSavedOffline"
          :class="[
            'hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold transition-all shadow-xs select-none',
            isCourseSavedOffline
              ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 cursor-default'
              : isDownloadingOffline
                ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40 animate-pulse'
                : 'bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border-slate-700 cursor-pointer'
          ]"
          :title="isCourseSavedOffline ? 'វគ្គសិក្សានេះត្រូវបានរក្សាទុកក្នុងម៉ាស៊ីនរួចរាល់ អាចរៀនពេលគ្មាន Internet' : 'ទាញយកវគ្គសិក្សានេះសម្រាប់រៀនក្រៅបណ្តាញ (Offline Learning)'"
        >
          <svg v-if="isCourseSavedOffline" class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <svg v-else-if="isDownloadingOffline" class="w-3.5 h-3.5 animate-spin text-indigo-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span>{{ isCourseSavedOffline ? 'Offline Ready' : (isDownloadingOffline ? `${downloadProgress}%` : 'Save Offline') }}</span>
        </button>

        <!-- Theater Mode & Fullscreen Toggles -->
        <button
          @click="isTheaterMode = !isTheaterMode"
          :title="isTheaterMode ? 'Exit Theater Mode (T)' : 'Theater Mode (T)'"
          :class="[isTheaterMode ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800', 'p-2 rounded-xl border border-slate-700/80 transition-all hidden sm:flex cursor-pointer']"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 18h16" />
          </svg>
        </button>

        <button
          @click="togglePlayerFullscreen"
          :title="isFullscreen ? 'Exit Fullscreen (F)' : 'Fullscreen (F)'"
          class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-700/80 transition-all cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
          </svg>
        </button>

        <!-- Toggle Playlist Sidebar Button -->
        <button
          @click="isSidebarOpen = !isSidebarOpen"
          title="Toggle Playlist Sidebar"
          :class="[isSidebarOpen ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800 border-slate-700', 'p-2 rounded-xl border transition-all cursor-pointer lg:flex']"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
          </svg>
        </button>
      </div>
    </header>

    <!-- Offline Learning Mode Notification Banner -->
    <div
      v-if="!isOnline"
      class="bg-amber-500/15 border-b border-amber-500/30 px-4 py-2 text-xs text-amber-300 flex items-center justify-between z-30 shrink-0 select-none animate-fadeIn"
    >
      <div class="flex items-center gap-2 max-w-4xl truncate">
        <span class="flex h-2 w-2 relative shrink-0">
          <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-400"></span>
        </span>
        <span class="font-bold">⚡ របៀបក្រៅបណ្តាញ (Offline Mode)៖</span>
        <span class="truncate">អ្នកកំពុងរៀនដោយគ្មាន Internet។ មេរៀន និងឯកសារត្រូវបានទាញចេញពីម៉ាស៊ីន ហើយវឌ្ឍនភាពសិក្សានឹង Auto-Sync ពេលភ្ជាប់ Internet វិញ។</span>
      </div>
      <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md bg-amber-500/20 border border-amber-500/40 text-amber-300 shrink-0">
        PWA Active
      </span>
    </div>

    <!-- Sync Toast Alert -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform opacity-0 -translate-y-2"
      enter-to-class="transform opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform opacity-100 translate-y-0"
      leave-to-class="transform opacity-0 -translate-y-2"
    >
      <div
        v-if="offlineSyncToast"
        class="fixed top-20 right-6 z-50 bg-emerald-950/95 border border-emerald-500/50 text-emerald-200 px-4 py-3 rounded-2xl shadow-2xl backdrop-blur-xl flex items-center gap-3 text-xs font-semibold"
      >
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
        <span>{{ offlineSyncToast }}</span>
      </div>
    </Transition>

    <!-- ═════════════════════════════════════════════════════════════════════════════ -->
    <!-- 2. MAIN LEARNING VIEWPORT (Player + Playlist Grid) -->
    <!-- ═════════════════════════════════════════════════════════════════════════════ -->
    <div class="flex-1 flex overflow-hidden relative">
      
      <!-- ─────────────────────────────────────────────────────────────────────────── -->
      <!-- LEFT / CENTER AREA: DYNAMIC CONTENT PLAYER & ACTION TABS -->
      <!-- ─────────────────────────────────────────────────────────────────────────── -->
      <main :class="[isTheaterMode || !isSidebarOpen ? 'w-full' : 'flex-1', 'h-full overflow-y-auto custom-scrollbar flex flex-col transition-all duration-300 bg-slate-950']">
        
        <div class="max-w-6xl mx-auto w-full p-4 md:p-6 flex flex-col flex-1 space-y-6">
          
          <!-- Lesson Header Banner -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900/80 border border-slate-800/90 rounded-2xl p-4 shadow-xl">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-300 uppercase tracking-wider">
                  {{ activeLesson?.type || 'Lesson' }}
                </span>
                <span class="text-xs text-slate-400 font-medium">
                  {{ activeLesson?.moduleTitle }}
                </span>
              </div>
              <h2 class="text-base sm:text-lg md:text-xl font-black text-white leading-tight">
                {{ activeLesson?.title }}
              </h2>
            </div>

            <!-- Complete Check Button -->
            <button
              @click="markCurrentCompleted"
              :class="[
                isLessonCompleted(activeLesson?.id) 
                  ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' 
                  : 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white shadow-lg shadow-indigo-600/30',
                'px-4 py-2 rounded-xl text-xs font-bold border transition-all flex items-center justify-center gap-2 shrink-0 cursor-pointer'
              ]"
            >
              <svg v-if="isLessonCompleted(activeLesson?.id)" class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ isLessonCompleted(activeLesson?.id) ? 'Completed (បានរៀនចប់)' : 'Mark as Complete (រៀនចប់)' }}</span>
            </button>
          </div>

          <!-- ═════════════════════════════════════════════════════════════════════ -->
          <!-- DYNAMIC CONTENT PLAYER BOX -->
          <!-- ═════════════════════════════════════════════════════════════════════ -->
          <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col">
            
            <!-- 1. VIDEO PLAYER (MP4 / Cloudinary / YouTube) -->
            <div v-if="activeLesson?.type === 'video'" class="relative aspect-video w-full bg-black flex items-center justify-center group/player">
              
              <!-- Embedded Iframe for YouTube or External Embeds -->
              <iframe
                v-if="activeLesson?.video_url && (activeLesson.video_url.includes('youtube') || activeLesson.video_url.includes('youtu.be') || activeLesson.video_url.includes('vimeo'))"
                :src="activeLesson.video_url"
                class="w-full h-full"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
              ></iframe>

              <!-- Direct Video Player for MP4 / Cloudinary / Storage -->
              <video
                v-else
                ref="videoPlayerRef"
                class="w-full h-full object-contain"
                :src="activeLesson?.video_url || 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4'"
                @timeupdate="onTimeUpdate"
                @ended="onVideoEnded"
                controls
                playsinline
              ></video>

              <!-- Speed & Resolution Overlay Pill (Top Right) -->
              <div class="absolute top-4 right-4 flex items-center gap-2 opacity-0 group-hover/player:opacity-100 transition-opacity z-20">
                <div class="relative group/speed">
                  <button class="px-2.5 py-1 rounded-xl bg-slate-900/80 backdrop-blur-md border border-slate-700 text-white font-bold text-xs shadow-lg flex items-center gap-1 cursor-pointer">
                    <span>⚡ {{ playbackSpeed }}x</span>
                  </button>
                  <div class="absolute right-0 mt-1 w-24 bg-slate-900 border border-slate-700 rounded-xl p-1 shadow-2xl hidden group-hover/speed:block z-30">
                    <button
                      v-for="s in speeds"
                      :key="s"
                      @click="setPlaybackSpeed(s)"
                      :class="[playbackSpeed === s ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800', 'w-full text-left px-2 py-1 rounded-lg text-xs font-bold transition-colors block']"
                    >
                      {{ s }}x
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. PDF VIEWER & READER -->
            <div v-else-if="activeLesson?.type === 'pdf'" class="p-6 md:p-8 flex flex-col items-center justify-center text-center space-y-4 bg-slate-900 min-h-[420px]">
              <div class="w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-400 flex items-center justify-center shadow-lg">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="max-w-md space-y-1">
                <h3 class="text-base font-bold text-white">ឯកសារមេរៀន PDF (Course Material)</h3>
                <p class="text-xs text-slate-400">
                  សូមអានឯកសារ PDF នេះដោយយកចិត្តទុកដាក់ដើម្បីស្វែងយល់បន្ថែមអំពីមេរៀន។
                </p>
              </div>

              <div class="flex flex-wrap items-center gap-3 pt-2">
                <a
                  :href="activeLesson?.file_url || `/storage/${activeLesson?.file_path}`"
                  target="_blank"
                  class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                  <span>Open PDF in Full Viewer</span>
                </a>
                <button
                  @click="markCurrentCompleted"
                  class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-all flex items-center gap-1.5 cursor-pointer"
                >
                  <span>✓ Mark PDF as Read</span>
                </button>
              </div>
            </div>

            <!-- 3. SLIDE PRESENTATION VIEWER -->
            <div v-else-if="activeLesson?.type === 'slide'" class="p-6 md:p-8 flex flex-col bg-slate-900 min-h-[420px] justify-between">
              <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <span class="text-xs font-bold text-slate-400">Slide Deck Presentation</span>
                <span class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 text-xs font-bold">
                  Slide {{ currentSlideIndex }}/{{ totalSlides }}
                </span>
              </div>

              <!-- Slide Visual Canvas -->
              <div class="my-6 p-8 rounded-2xl bg-gradient-to-br from-slate-950 via-indigo-950/40 to-slate-950 border border-slate-800 flex flex-col items-center justify-center text-center space-y-4">
                <h4 class="text-xl font-black text-white">
                  {{ activeLesson?.title }} — Key Concept #{{ currentSlideIndex }}
                </h4>
                <p class="text-sm text-slate-300 max-w-xl leading-relaxed">
                  ចំណុចសំខាន់៖ រាល់ប្រតិបត្តិការ និងការប្រើប្រាស់ Operator ក្នុងប្រព័ន្ធត្រូវបានកំណត់យ៉ាងច្បាស់លាស់ដើម្បីធានាសុក្រឹតភាពនៃទិន្នន័យ។
                </p>
              </div>

              <!-- Slide Controls -->
              <div class="flex items-center justify-between pt-2">
                <button
                  @click="currentSlideIndex = Math.max(1, currentSlideIndex - 1)"
                  :disabled="currentSlideIndex <= 1"
                  class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 disabled:opacity-40 text-xs font-bold transition-all"
                >
                  ← Previous Slide
                </button>
                <button
                  @click="currentSlideIndex = Math.min(totalSlides, currentSlideIndex + 1)"
                  :disabled="currentSlideIndex >= totalSlides"
                  class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white text-xs font-bold transition-all"
                >
                  Next Slide →
                </button>
              </div>
            </div>

            <!-- 4. INTERACTIVE PRACTICE LAB & CODING -->
            <div v-else-if="activeLesson?.type === 'lab'" class="p-5 bg-slate-900 space-y-4">
              <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold text-white">💻 Interactive Code Sandbox</span>
                  <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[10px] font-bold">C / C++ Compiler</span>
                </div>
                <button
                  @click="runCode"
                  :disabled="isRunningCode"
                  class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                  </svg>
                  <span>{{ isRunningCode ? 'Running...' : 'Run Code ▶' }}</span>
                </button>
              </div>

              <!-- Code Editor Textarea -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="space-y-1">
                  <label class="text-[10px] font-bold text-slate-400 uppercase">Code Editor</label>
                  <textarea
                    v-model="studentCode"
                    rows="8"
                    class="w-full bg-slate-950 font-mono text-xs text-indigo-300 p-3 rounded-xl border border-slate-800 focus:outline-none focus:border-indigo-500 transition-all custom-scrollbar"
                  ></textarea>
                </div>

                <div class="space-y-1">
                  <label class="text-[10px] font-bold text-slate-400 uppercase">Terminal Output</label>
                  <div class="w-full bg-slate-950 font-mono text-xs text-emerald-400 p-3 rounded-xl border border-slate-800 min-h-[145px] whitespace-pre-wrap">
                    {{ codeOutput || 'Click "Run Code" to compile and execute program.' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- 5. DEFAULT ARTICLE / TEXT LESSON -->
            <div v-else class="p-6 md:p-8 prose prose-invert max-w-none">
              <div v-html="activeLesson?.content || '<p>ខ្លឹមសារមេរៀន...</p>'"></div>
            </div>
          </div>

          <!-- ═════════════════════════════════════════════════════════════════════ -->
          <!-- 3. ACTION TABS (Overview, Resources, Q&A, AI Tutor, Notes) -->
          <!-- ═════════════════════════════════════════════════════════════════════ -->
          <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
            
            <!-- Tab Navigation Header -->
            <div class="flex items-center gap-1 px-4 pt-3 border-b border-slate-800 overflow-x-auto custom-scrollbar">
              <button
                @click="activeTab = 'overview'"
                :class="[
                  activeTab === 'overview'
                    ? 'text-indigo-400 border-indigo-500 font-bold'
                    : 'text-slate-400 hover:text-slate-200 border-transparent font-medium',
                  'px-4 py-2.5 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-2 cursor-pointer'
                ]"
              >
                <span>📖</span>
                <span>Overview (សេចក្តីសង្ខេប)</span>
              </button>

              <button
                @click="activeTab = 'resources'"
                :class="[
                  activeTab === 'resources'
                    ? 'text-indigo-400 border-indigo-500 font-bold'
                    : 'text-slate-400 hover:text-slate-200 border-transparent font-medium',
                  'px-4 py-2.5 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-2 cursor-pointer'
                ]"
              >
                <span>📁</span>
                <span>Resources & Downloads</span>
              </button>

              <button
                @click="activeTab = 'qa'"
                :class="[
                  activeTab === 'qa'
                    ? 'text-indigo-400 border-indigo-500 font-bold'
                    : 'text-slate-400 hover:text-slate-200 border-transparent font-medium',
                  'px-4 py-2.5 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-2 cursor-pointer'
                ]"
              >
                <span>💬</span>
                <span>Q&A Discussion ({{ discussionsList.length }})</span>
              </button>

              <button
                @click="activeTab = 'aiTutor'"
                :class="[
                  activeTab === 'aiTutor'
                    ? 'text-purple-400 border-purple-500 font-bold'
                    : 'text-slate-400 hover:text-slate-200 border-transparent font-medium',
                  'px-4 py-2.5 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-2 cursor-pointer'
                ]"
              >
                <span>🤖</span>
                <span>AI Helper 24/7</span>
                <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-purple-500/20 text-purple-300 border border-purple-500/30">AI</span>
              </button>

              <button
                @click="activeTab = 'notes'"
                :class="[
                  activeTab === 'notes'
                    ? 'text-indigo-400 border-indigo-500 font-bold'
                    : 'text-slate-400 hover:text-slate-200 border-transparent font-medium',
                  'px-4 py-2.5 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-2 cursor-pointer'
                ]"
              >
                <span>📝</span>
                <span>Personal Notes ({{ personalNotes.length }})</span>
              </button>
            </div>

            <!-- Tab 1: Overview -->
            <div v-show="activeTab === 'overview'" class="p-6 space-y-4">
              <div class="prose prose-invert max-w-none text-xs leading-relaxed text-slate-300">
                <div v-if="activeLesson?.content" v-html="activeLesson.content"></div>
                <p v-else>
                  នៅក្នុងមេរៀននេះ សិស្សនឹងទទួលបាននូវចំណេះដឹងគន្លឹះ អំពីគោលការណ៍គ្រឹះ វិធីសាស្រ្តអនុវត្តជាក់ស្តែង និងការដោះស្រាយបញ្ហាផ្សេងៗក្នុងដំណើរការសិក្សា។
                </p>
              </div>

              <!-- Objectives Pill Card -->
              <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-2">
                <h4 class="text-xs font-bold text-white flex items-center gap-2">
                  <span>🎯</span>
                  <span>គោលបំណងសិក្សា (Learning Objectives)</span>
                </h4>
                <ul class="text-xs text-slate-400 space-y-1.5 pl-5 list-disc">
                  <li>យល់ដឹងពី Syntax និង Logic នៃមេរៀនច្បាស់លាស់</li>
                  <li>អាចអនុវត្ត Code និងលំហាត់ដោយផ្ទាល់បានជោគជ័យ</li>
                  <li>ត្រៀមខ្លួនសម្រាប់ធ្វើ Quiz ប្រចាំ Module</li>
                </ul>
              </div>

              <!-- ✨ AI NEXT STEPS RECOMMENDATION CARD (TAILORED TO MAJOR) -->
              <div class="rounded-3xl bg-gradient-to-br from-indigo-950 via-slate-900 to-purple-950 border border-indigo-500/30 p-5 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-indigo-500/20 pb-3 flex-wrap gap-2">
                  <div class="flex items-center gap-2">
                    <span class="text-xl">✨</span>
                    <div>
                      <h4 class="font-extrabold text-sm text-white flex items-center gap-2">
                        <span>AI Smart Next Steps & Skill Roadmap</span>
                        <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 text-[10px] font-bold">
                          Cloudflare AI
                        </span>
                      </h4>
                      <p class="text-[11px] text-indigo-300">Tailored for {{ course.major?.name || 'Your Degree Major' }}</p>
                    </div>
                  </div>

                  <span 
                    v-if="aiNextSteps?.tech_badge || aiNextSteps?.crop_badge || aiNextSteps?.cefr_level || aiNextSteps?.etiquette_badge"
                    class="px-3 py-1 rounded-xl bg-indigo-600/30 border border-indigo-400/40 text-indigo-200 text-xs font-bold font-mono"
                  >
                    🏷️ {{ aiNextSteps.tech_badge || aiNextSteps.crop_badge || aiNextSteps.cefr_level || aiNextSteps.etiquette_badge }}
                  </span>
                </div>

                <div v-if="isNextStepsLoading" class="p-4 text-center text-xs text-indigo-300 flex items-center justify-center gap-2">
                  <span class="animate-spin">⏳</span>
                  <span>Generating personalized next steps recommendations...</span>
                </div>

                <div v-else-if="aiNextSteps" class="space-y-3 text-xs">
                  <!-- Next Recommended Topic -->
                  <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-indigo-500/20 space-y-1">
                    <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider">🚀 Next Recommended Topic (ប្រធានបទបន្ត):</p>
                    <p class="text-white font-bold text-sm">{{ aiNextSteps.next_topic }}</p>
                  </div>

                  <!-- Hands-on Project / Field Task -->
                  <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-indigo-500/20 space-y-1">
                    <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">
                      🛠️ Hands-on Challenge / Practice Task (កិច្ចការអនុវត្ត):
                    </p>
                    <p class="text-slate-200 leading-relaxed">
                      {{ aiNextSteps.practice_project || aiNextSteps.daily_practice_task || aiNextSteps.field_practice || aiNextSteps.simulated_case || aiNextSteps.hospitality_scenario }}
                    </p>
                  </div>

                  <!-- Pitfalls / Smart Tips -->
                  <div 
                    v-if="aiNextSteps.common_pitfalls || aiNextSteps.smart_farming_tip || aiNextSteps.ethical_tip || aiNextSteps.customer_service_tip || aiNextSteps.vocabulary_booster"
                    class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-1.5"
                  >
                    <p class="text-[10px] font-bold text-amber-400 uppercase tracking-wider">💡 Pro Mentor Guidance & Tips:</p>
                    <div v-if="aiNextSteps.common_pitfalls" class="text-slate-300 text-[11px] space-y-1">
                      <p v-for="(pf, idx) in aiNextSteps.common_pitfalls" :key="idx">• {{ pf }}</p>
                    </div>
                    <p v-else-if="aiNextSteps.smart_farming_tip" class="text-slate-300 text-[11px]">
                      {{ aiNextSteps.smart_farming_tip }}
                    </p>
                    <p v-else-if="aiNextSteps.ethical_tip" class="text-slate-300 text-[11px]">
                      {{ aiNextSteps.ethical_tip }}
                    </p>
                    <p v-else-if="aiNextSteps.customer_service_tip" class="text-slate-300 text-[11px]">
                      {{ aiNextSteps.customer_service_tip }}
                    </p>
                    <div v-else-if="aiNextSteps.vocabulary_booster" class="flex gap-2 flex-wrap text-[11px]">
                      <span class="text-slate-400">Vocab Booster:</span>
                      <span v-for="w in aiNextSteps.vocabulary_booster" :key="w" class="px-2 py-0.5 rounded bg-purple-500/20 text-purple-300 font-bold">
                        {{ w }}
                      </span>
                    </div>
                  </div>

                  <!-- Quick Action Buttons -->
                  <div class="flex items-center gap-2 pt-1 flex-wrap">
                    <Link
                      href="/student/practice-lab"
                      class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                      <span>💻 Open Practice Sandbox</span>
                    </Link>
                    <Link
                      href="/student/quizzes"
                      class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                      <span>❓ Take Knowledge Check</span>
                    </Link>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab 2: Resources & Downloads -->
            <div v-show="activeTab === 'resources'" class="p-6 space-y-3">
              <div class="space-y-2">
                <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-950 border border-slate-800 text-xs">
                  <div class="flex items-center gap-3">
                    <span class="text-red-400 text-base">📄</span>
                    <div>
                      <p class="font-bold text-white">Lesson Summary & Formula Sheet.pdf</p>
                      <p class="text-[10px] text-slate-500">PDF Document • 2.4 MB</p>
                    </div>
                  </div>
                  <a
                    href="#"
                    class="px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-bold transition-all"
                  >
                    Download ⬇
                  </a>
                </div>

                <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-950 border border-slate-800 text-xs">
                  <div class="flex items-center gap-3">
                    <span class="text-emerald-400 text-base">💻</span>
                    <div>
                      <p class="font-bold text-white">Source_Code_Exercise_Starter.zip</p>
                      <p class="text-[10px] text-slate-500">ZIP Archive • 1.1 MB</p>
                    </div>
                  </div>
                  <a
                    href="#"
                    class="px-3 py-1.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-bold transition-all"
                  >
                    Download ⬇
                  </a>
                </div>
              </div>
            </div>

            <!-- Tab 3: Q&A Discussion -->
            <div v-show="activeTab === 'qa'" class="p-6 space-y-6">
              <!-- New Question Input -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-slate-300">សួរសំណួរទៅកាន់គ្រូ ឬមិត្តរួមថ្នាក់៖</label>
                <div class="flex gap-2">
                  <input
                    v-model="newQuestionText"
                    type="text"
                    placeholder="វាយសំណួររបស់អ្នកត្រង់នេះ..."
                    @keyup.enter="submitQuestion"
                    class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                  />
                  <button
                    @click="submitQuestion"
                    :disabled="isSubmittingQuestion || !newQuestionText.trim()"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white font-bold text-xs transition-all shadow-md shrink-0 cursor-pointer"
                  >
                    Post Question
                  </button>
                </div>
              </div>

              <!-- Question Stream -->
              <div class="space-y-3">
                <div
                  v-for="qa in discussionsList"
                  :key="qa.id"
                  class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <img :src="qa.avatar" class="w-6 h-6 rounded-full object-cover" />
                      <span class="text-xs font-bold text-white">{{ qa.userName }}</span>
                    </div>
                    <span class="text-[10px] text-slate-500">{{ qa.time }}</span>
                  </div>

                  <p class="text-xs text-slate-300 leading-relaxed">{{ qa.content }}</p>

                  <!-- Teacher Reply -->
                  <div v-if="qa.reply" class="ml-4 pl-3 border-l-2 border-indigo-500/60 pt-2 space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-500/20 text-indigo-300">Instructor Answer</span>
                    </div>
                    <p class="text-xs text-slate-400">{{ qa.reply }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab 4: AI Smart Tutor 24/7 -->
            <div v-show="activeTab === 'aiTutor'" class="p-6 space-y-4">
              <!-- Chat Stream -->
              <div class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar pr-1">
                <div
                  v-for="(msg, mIdx) in aiMessages"
                  :key="mIdx"
                  :class="[
                    msg.role === 'user' ? 'ml-auto bg-indigo-600 text-white' : 'mr-auto bg-slate-950 border border-slate-800 text-slate-200',
                    'max-w-[85%] p-3.5 rounded-2xl text-xs leading-relaxed space-y-1 shadow-md'
                  ]"
                >
                  <div class="flex items-center justify-between gap-2 text-[10px] opacity-70">
                    <span class="font-bold">{{ msg.role === 'user' ? 'You' : '🤖 SPI AI Tutor' }}</span>
                    <span>{{ msg.time }}</span>
                  </div>
                  <div class="whitespace-pre-wrap">{{ msg.text }}</div>
                </div>

                <div v-if="isAiLoading" class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-xs text-purple-400 flex items-center gap-2 max-w-[200px]">
                  <span class="animate-spin">⏳</span>
                  <span>AI កំពុងវិភាគចម្លើយ...</span>
                </div>
              </div>

              <!-- Input -->
              <div class="flex gap-2 pt-2">
                <input
                  v-model="aiInputText"
                  type="text"
                  placeholder="សួរ AI ពីមេរៀននេះ (ឧ. ពន្យល់ចំណុចនេះជាភាសាខ្មែរ)..."
                  @keyup.enter="askAiTutor"
                  class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500"
                />
                <button
                  @click="askAiTutor"
                  :disabled="isAiLoading || !aiInputText.trim()"
                  class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-40 text-white font-bold text-xs shadow-md transition-all shrink-0 cursor-pointer"
                >
                  Ask AI ✨
                </button>
              </div>
            </div>

            <!-- Tab 5: Personal Notes -->
            <div v-show="activeTab === 'notes'" class="p-6 space-y-4">
              <div class="space-y-2">
                <label class="text-xs font-bold text-slate-300">កត់ត្រាកំណត់ចំណាំថ្មី៖</label>
                <div class="flex gap-2">
                  <input
                    v-model="noteInput"
                    type="text"
                    placeholder="វាយកំណត់ចំណាំរបស់អ្នក..."
                    @keyup.enter="savePersonalNote"
                    class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                  />
                  <button
                    @click="savePersonalNote"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition-all shadow-md shrink-0 cursor-pointer"
                  >
                    Save Note
                  </button>
                </div>
              </div>

              <div class="space-y-2">
                <div
                  v-for="n in personalNotes"
                  :key="n.id"
                  class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs flex items-center justify-between"
                >
                  <div class="space-y-0.5">
                    <p class="text-slate-200">{{ n.text }}</p>
                    <p class="text-[10px] text-slate-500">⏱️ {{ n.time }} • {{ n.savedAt }}</p>
                  </div>
                  <button @click="personalNotes = personalNotes.filter(item => item.id !== n.id)" class="text-slate-500 hover:text-red-400 p-1">
                    🗑️
                  </button>
                </div>
              </div>
            </div>

          </div>

        </div>

      </main>

      <!-- ─────────────────────────────────────────────────────────────────────────── -->
      <!-- RIGHT SIDEBAR: PLAYLIST ACCORDION & LESSON TREE (30%) -->
      <!-- ─────────────────────────────────────────────────────────────────────────── -->
      <aside
        v-show="isSidebarOpen && !isTheaterMode"
        class="w-80 md:w-96 shrink-0 bg-slate-900 border-l border-slate-800 flex flex-col h-full z-30 transition-all duration-300"
      >
        <!-- Playlist Header -->
        <div class="p-4 border-b border-slate-800 space-y-3 shrink-0">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
              <span>📋</span>
              <span>Course Curriculum</span>
            </h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
              {{ completedCount }}/{{ allLessons.length }} Done
            </span>
          </div>

          <!-- Search Filter in Playlist -->
          <div class="relative">
            <svg class="w-3.5 h-3.5 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="playlistSearch"
              type="text"
              placeholder="Filter lessons..."
              class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
            />
          </div>
        </div>

        <!-- Lessons Playlist List -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-2">
          
          <div
            v-for="lesson in allLessons.filter(l => !playlistSearch.trim() || l.title.toLowerCase().includes(playlistSearch.toLowerCase()))"
            :key="lesson.id"
            @click="selectLesson(lesson)"
            :class="[
              activeLessonId === lesson.id 
                ? 'bg-indigo-600/20 border-indigo-500/60 shadow-md shadow-indigo-950/50' 
                : isLessonLocked(lesson)
                  ? 'bg-slate-950/40 border-slate-800/40 opacity-60 cursor-not-allowed'
                  : 'bg-slate-950 hover:bg-slate-800/60 border-slate-800/80 cursor-pointer',
              'p-3 rounded-2xl border transition-all flex items-start gap-3 group/item'
            ]"
          >
            <!-- Status Icon -->
            <div class="mt-0.5 shrink-0">
              <!-- Completed Check -->
              <div v-if="isLessonCompleted(lesson.id)" class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center text-[10px] font-bold">
                ✓
              </div>
              <!-- Active Playing -->
              <div v-else-if="activeLessonId === lesson.id" class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[9px] animate-pulse">
                ▶
              </div>
              <!-- Locked -->
              <div v-else-if="isLessonLocked(lesson)" class="w-5 h-5 rounded-full bg-slate-800 text-slate-500 flex items-center justify-center text-[10px]">
                🔒
              </div>
              <!-- Default Dot / Type -->
              <div v-else class="w-5 h-5 rounded-full bg-slate-800 border border-slate-700 text-slate-400 flex items-center justify-center text-[9px] font-bold">
                {{ lesson.globalIndex }}
              </div>
            </div>

            <!-- Title & Duration -->
            <div class="flex-1 min-w-0 space-y-1">
              <p
                :class="[
                  activeLessonId === lesson.id ? 'text-indigo-300 font-bold' : 'text-slate-200 group-hover/item:text-white font-medium',
                  'text-xs line-clamp-2 leading-snug'
                ]"
              >
                {{ lesson.title }}
              </p>

              <div class="flex items-center gap-2 text-[10px] text-slate-400">
                <span class="uppercase font-bold text-slate-500">{{ lesson.type }}</span>
                <span>•</span>
                <span>⏱️ {{ formatTime(lesson.duration_seconds || 600) }}</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Playlist Footer with Mode Switch -->
        <div class="p-3 border-t border-slate-800 bg-slate-950/60 shrink-0 flex items-center justify-between text-xs text-slate-400">
          <span class="text-[10px]">Sequential Learning Mode</span>
          <button
            @click="isSequentialMode = !isSequentialMode"
            :class="[isSequentialMode ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400', 'px-2 py-0.5 rounded text-[10px] font-bold transition-colors cursor-pointer']"
          >
            {{ isSequentialMode ? 'ON (តម្រូវតាមលំដាប់)' : 'OFF (សេរី)' }}
          </button>
        </div>
      </aside>

    </div>

  </div>
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
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #475569;
}
</style>
