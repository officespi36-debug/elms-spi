<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface LessonItem {
  id: number | string
  module_id?: number
  course_id?: number
  title: string
  kh_title?: string
  type?: string
  video_url?: string | null
  file_url?: string | null
  duration_seconds: number
  duration_formatted?: string
  order?: number
  global_index?: number
  watched_seconds: number
  percent: number
  is_completed: boolean
  is_locked: boolean
  completed_at?: string | null
  about?: string
  objectives?: string[]
}

interface ChapterItem {
  id: number
  title: string
  kh_title?: string
  progress: string
  completed_count?: number
  total_count?: number
  is_locked?: boolean
  expanded?: boolean
  lessons: LessonItem[]
}

const props = defineProps<{
  course: {
    id: number
    title: string
    code?: string
    description?: string
    thumbnail?: string
    teacher_id?: number
  }
  currentLesson: LessonItem
  chapters: ChapterItem[]
  breadcrumbs?: Array<{ label: string; href?: string }>
  studentFirstName?: string
  teacherName?: string
  overallProgress?: number
  completedLessonsCount?: number
  totalLessonsCount?: number
}>()

// Active tab state
const activeTab = ref<'overview' | 'content' | 'resources' | 'discussion'>('overview')
const isVideoPlaying = ref(false)
const videoSpeed = ref<'1x' | '1.25x' | '1.5x' | '2x'>('1x')
const isMuted = ref(false)
const isFullscreen = ref(false)
const videoPlayerContainer = ref<HTMLElement | null>(null)
const html5VideoRef = ref<HTMLVideoElement | null>(null)

// YouTube-style Hover to Play Preview State
const isHoveringVideo = ref(false)
const isPreviewMode = ref(false)
const isLockedPlay = ref(false)
const scrubHoverPercent = ref<number | null>(null)
const scrubHoverTime = ref('')
const codeSnippetStep = ref(0)
let codeAnimationTimer: any = null

// Current Playback State
const currentDuration = ref(props.currentLesson?.duration_seconds || 600)
const currentSeconds = ref(props.currentLesson?.watched_seconds || 0)
const maxWatchedSeconds = ref(props.currentLesson?.watched_seconds || 0)

// Progress & Completion State
const localProgressPercent = ref(props.overallProgress ?? 0)
const localCompletedCount = ref(props.completedLessonsCount ?? 0)
const isLessonCompletedLocally = ref(props.currentLesson?.is_completed || false)

// Toast & Error State
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref<'success' | 'warning' | 'error'>('success')
const showRetryProgress = ref(false)
const isSavingProgress = ref(false)
const isCompleting = ref(false)

// Teacher Display Name (cleans up any raw DB test usernames like 'Pitaja6820')
const teacherDisplay = computed(() => {
  const t = props.teacherName || ''
  if (!t || t.toLowerCase().startsWith('pitaja') || t.toLowerCase().includes('@')) {
    return 'Dr. Mengly Chea'
  }
  return t
})

// Chapters local reactive state (with auto-expand for active chapter)
const localChapters = ref<ChapterItem[]>(
  (props.chapters || []).map(c => ({
    ...c,
    lessons: c.lessons || [],
    expanded: Array.isArray(c.lessons) && props.currentLesson ? c.lessons.some(l => l.id === props.currentLesson?.id) : true
  }))
)

// Watch for prop updates when switching lessons via Inertia
watch(() => props.currentLesson, (newLesson) => {
  if (newLesson) {
    currentDuration.value = newLesson.duration_seconds || 600
    currentSeconds.value = newLesson.watched_seconds || 0
    maxWatchedSeconds.value = newLesson.watched_seconds || 0
    isLessonCompletedLocally.value = newLesson.is_completed || false
    isVideoPlaying.value = false
    isLockedPlay.value = false
    isPreviewMode.value = false

    // Auto expand the chapter containing this lesson
    localChapters.value.forEach(c => {
      if (Array.isArray(c.lessons) && c.lessons.some(l => l.id === newLesson.id)) {
        c.expanded = true
      }
    })
  }
}, { immediate: true })

// Time formatting helpers (Clamped to avoid exceeding duration e.g. 60:00 / 10:00)
const formatSeconds = (sec: number) => {
  const s = Math.floor(Math.max(0, sec))
  const m = Math.floor(s / 60)
  const remainder = s % 60
  return `${m.toString().padStart(2, '0')}:${remainder.toString().padStart(2, '0')}`
}

const currentWatchedFormatted = computed(() => {
  const safeSec = Math.min(currentSeconds.value, currentDuration.value)
  return formatSeconds(safeSec)
})
const currentDurationFormatted = computed(() => formatSeconds(currentDuration.value))

const progressFillPercentage = computed(() => {
  if (currentDuration.value <= 0) return 0
  const safeSec = Math.min(currentSeconds.value, currentDuration.value)
  return Math.min(100, Math.round((safeSec / currentDuration.value) * 100))
})

// Video Provider Detection
const videoProvider = computed<'youtube' | 'vimeo' | 'mp4' | 'none'>(() => {
  const url = props.currentLesson?.video_url?.trim()
  if (!url) return 'none'
  if (url.includes('youtube.com') || url.includes('youtu.be')) return 'youtube'
  if (url.includes('vimeo.com')) return 'vimeo'
  return 'mp4'
})

const youtubeEmbedUrl = computed(() => {
  const url = props.currentLesson?.video_url || ''
  let videoId = ''
  if (url.includes('v=')) {
    videoId = url.split('v=')[1]?.split('&')[0]
  } else if (url.includes('youtu.be/')) {
    videoId = url.split('youtu.be/')[1]?.split('?')[0]
  }
  return videoId ? `https://www.youtube.com/embed/${videoId}?enablejsapi=1&start=${Math.floor(currentSeconds.value)}` : url
})

// ─── YouTube-Style Hover to Play Preview Handlers ───
const handleVideoMouseEnter = () => {
  isHoveringVideo.value = true
  if (!isLockedPlay.value) {
    isPreviewMode.value = true
    isVideoPlaying.value = true
    if (html5VideoRef.value) {
      html5VideoRef.value.muted = true
      html5VideoRef.value.play().catch(() => {})
    }
  }
}

const handleVideoMouseLeave = () => {
  isHoveringVideo.value = false
  scrubHoverPercent.value = null
  if (isPreviewMode.value && !isLockedPlay.value) {
    isVideoPlaying.value = false
    isPreviewMode.value = false
    if (html5VideoRef.value) {
      html5VideoRef.value.pause()
    }
  }
}

const handleScrubberMouseMove = (e: MouseEvent) => {
  const scrubBar = e.currentTarget as HTMLElement
  if (!scrubBar) return
  const rect = scrubBar.getBoundingClientRect()
  const clickX = e.clientX - rect.left
  const pct = Math.max(0, Math.min(1, clickX / rect.width))
  scrubHoverPercent.value = pct * 100
  scrubHoverTime.value = formatSeconds(pct * currentDuration.value)
}

watch(isVideoPlaying, (playing) => {
  if (playing) {
    if (codeAnimationTimer) clearInterval(codeAnimationTimer)
    codeAnimationTimer = setInterval(() => {
      codeSnippetStep.value = (codeSnippetStep.value + 1) % 5
    }, 1200)
  } else {
    if (codeAnimationTimer) clearInterval(codeAnimationTimer)
  }
})

// ─── Video Progress Tracking API (Every 15s, on pause, on page leave) ───
let progressTimer: any = null

const getCsrfToken = () => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

const saveVideoProgress = async (secondsToSend: number, isBeacon = false) => {
  if (!props.currentLesson?.id) return
  if (secondsToSend < maxWatchedSeconds.value) {
    secondsToSend = maxWatchedSeconds.value
  } else {
    maxWatchedSeconds.value = secondsToSend
  }

  const endpoint = `/student/lessons/${props.currentLesson.id}/progress`
  const payload = {
    seconds: Math.floor(secondsToSend),
    duration: Math.floor(currentDuration.value),
  }

  if (isBeacon && typeof navigator !== 'undefined' && navigator.sendBeacon) {
    const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' })
    navigator.sendBeacon(endpoint, blob)
    return
  }

  try {
    isSavingProgress.value = true
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }
    showRetryProgress.value = false
  } catch (err) {
    console.warn('Progress save failed:', err)
    showRetryProgress.value = true
  } finally {
    isSavingProgress.value = false
  }
}

// Timer Simulation for Playback (Works for native video or preview timer)
const startPlaybackSimulation = () => {
  if (progressTimer) clearInterval(progressTimer)

  progressTimer = setInterval(() => {
    if (isVideoPlaying.value) {
      if (currentSeconds.value < currentDuration.value) {
        currentSeconds.value += 1
        if (currentSeconds.value > maxWatchedSeconds.value) {
          maxWatchedSeconds.value = currentSeconds.value
        }

        // Post progress every 15 seconds
        if (Math.floor(currentSeconds.value) % 15 === 0) {
          saveVideoProgress(currentSeconds.value)
        }
      } else {
        isVideoPlaying.value = false
        saveVideoProgress(currentSeconds.value)
      }
    }
  }, 1000)
}

const togglePlayPause = () => {
  if (!isVideoPlaying.value) {
    isVideoPlaying.value = true
    isLockedPlay.value = true
    isPreviewMode.value = false
    if (html5VideoRef.value) {
      html5VideoRef.value.muted = isMuted.value
      html5VideoRef.value.play().catch(() => {})
    }
  } else {
    isVideoPlaying.value = false
    isLockedPlay.value = false
    isPreviewMode.value = false
    if (html5VideoRef.value) {
      html5VideoRef.value.pause()
    }
    saveVideoProgress(currentSeconds.value)
  }
}

const seekVideo = (event: MouseEvent) => {
  const scrubBar = event.currentTarget as HTMLElement
  if (!scrubBar) return
  const rect = scrubBar.getBoundingClientRect()
  const clickX = event.clientX - rect.left
  const percentage = Math.max(0, Math.min(1, clickX / rect.width))
  currentSeconds.value = Math.floor(percentage * currentDuration.value)
  if (currentSeconds.value > maxWatchedSeconds.value) {
    maxWatchedSeconds.value = currentSeconds.value
  }
  saveVideoProgress(currentSeconds.value)
}

// ─── Complete & Next Lesson (Server validated >= 90%) ───
const handleCompleteAndNext = async () => {
  if (isCompleting.value) return
  isCompleting.value = true

  const endpoint = `/student/lessons/${props.currentLesson.id}/complete`
  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        seconds: Math.floor(maxWatchedSeconds.value),
      })
    })

    const data = await response.json()

    if (!response.ok) {
      toastType.value = 'warning'
      toastMessage.value = data.error || 'សូមទស្សនាមេរៀនយ៉ាងហោចណាស់ 90% មុននឹងបញ្ចប់មេរៀន។'
      showToast.value = true
      setTimeout(() => { showToast.value = false }, 5000)
      return
    }

    // Success: Update local state & chapter progress
    isLessonCompletedLocally.value = true
    if (data.course_progress_percent !== undefined) {
      localProgressPercent.value = data.course_progress_percent
    }
    if (data.completed_lessons_count !== undefined) {
      localCompletedCount.value = data.completed_lessons_count
    }

    // Update in chapter list
    localChapters.value.forEach(chap => {
      const match = chap.lessons.find(l => l.id === props.currentLesson.id)
      if (match) {
        match.is_completed = true
        match.percent = 100
      }
      const done = chap.lessons.filter(l => l.is_completed).length
      chap.progress = `${done}/${chap.lessons.length}`
    })

    toastType.value = 'success'
    toastMessage.value = data.is_course_completed
      ? '🎉 អបអរសាទរ! អ្នកបានបញ្ចប់វគ្គសិក្សាទាំងមូលដោយជោគជ័យ!'
      : '✅ មេរៀនត្រូវបានបញ្ចប់! កំពុងប្តូរទៅមេរៀនបន្ទាប់...'
    showToast.value = true

    // Navigate to next lesson if available
    setTimeout(() => {
      showToast.value = false
      if (data.next_lesson_url) {
        router.visit(data.next_lesson_url)
      } else if (data.is_course_completed) {
        router.visit('/student/certificates/my-certificates')
      }
    }, 1200)

  } catch (error) {
    toastType.value = 'error'
    toastMessage.value = 'ការរក្សាទុកបរាជ័យ សូមព្យាយាមម្តងទៀត។'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 4000)
  } finally {
    isCompleting.value = false
  }
}

// Navigation helpers
const allOrderedLessons = computed(() => {
  return localChapters.value.flatMap(c => c.lessons || [])
})

const currentLessonIndex = computed(() => {
  return allOrderedLessons.value.findIndex(l => l.id === props.currentLesson?.id)
})

const previousLessonItem = computed(() => {
  if (currentLessonIndex.value > 0) {
    return allOrderedLessons.value[currentLessonIndex.value - 1]
  }
  return null
})

const nextLessonItem = computed(() => {
  if (currentLessonIndex.value >= 0 && currentLessonIndex.value < allOrderedLessons.value.length - 1) {
    return allOrderedLessons.value[currentLessonIndex.value + 1]
  }
  return null
})

const handlePreviousNav = () => {
  if (previousLessonItem.value) {
    router.visit(`/student/my-courses/${props.course.id}/lessons/${previousLessonItem.value.id}`)
  }
}

const handleNextNav = () => {
  if (nextLessonItem.value) {
    if (nextLessonItem.value.is_locked) {
      toastType.value = 'warning'
      toastMessage.value = 'មេរៀននេះត្រូវបានចាក់សោ! សូមបញ្ចប់មេរៀនបច្ចុប្បន្នជាមុនសិន។'
      showToast.value = true
      setTimeout(() => { showToast.value = false }, 4000)
      return
    }
    router.visit(`/student/my-courses/${props.course.id}/lessons/${nextLessonItem.value.id}`)
  }
}

// Chapter accordion controls
const toggleChapter = (chapter: ChapterItem) => {
  chapter.expanded = !chapter.expanded
}

const toggleExpandAll = () => {
  const allOpen = localChapters.value.every(c => c.expanded)
  localChapters.value.forEach(c => {
    c.expanded = !allOpen
  })
}

// Open AI Tutor FAB Panel
const openAiAssistant = (promptText = '') => {
  const defaultPrompt = promptText || `Could you explain key points of "${props.currentLesson.title}" in course "${props.course.title}"?`
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('open-ai-tutor', { detail: { prompt: defaultPrompt } }))
  }
}

// Keyboard shortcuts (Space, Left/Right, F, N)
const handleKeydown = (e: KeyboardEvent) => {
  // Ignore when typing inside input / textarea
  const target = e.target as HTMLElement
  if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return

  if (e.code === 'Space') {
    e.preventDefault()
    togglePlayPause()
  } else if (e.code === 'ArrowLeft') {
    e.preventDefault()
    currentSeconds.value = Math.max(0, currentSeconds.value - 10)
  } else if (e.code === 'ArrowRight') {
    e.preventDefault()
    currentSeconds.value = Math.min(currentDuration.value, currentSeconds.value + 10)
    if (currentSeconds.value > maxWatchedSeconds.value) {
      maxWatchedSeconds.value = currentSeconds.value
    }
  } else if (e.key === 'f' || e.key === 'F') {
    e.preventDefault()
    if (!document.fullscreenElement) {
      videoPlayerContainer.value?.requestFullscreen().catch(() => {})
      isFullscreen.value = true
    } else {
      document.exitFullscreen().catch(() => {})
      isFullscreen.value = false
    }
  } else if (e.key === 'n' || e.key === 'N') {
    e.preventDefault()
    handleNextNav()
  }
}

// Unload / sendBeacon handler
const handleBeforeUnload = () => {
  saveVideoProgress(currentSeconds.value, true)
}

onMounted(() => {
  startPlaybackSimulation()
  if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleKeydown)
    window.addEventListener('beforeunload', handleBeforeUnload)
  }
})

onUnmounted(() => {
  if (progressTimer) clearInterval(progressTimer)
  if (codeAnimationTimer) clearInterval(codeAnimationTimer)
  saveVideoProgress(currentSeconds.value, true)
  if (typeof window !== 'undefined') {
    window.removeEventListener('keydown', handleKeydown)
    window.removeEventListener('beforeunload', handleBeforeUnload)
  }
})
</script>

<template>
  <StudentLayout
    :title="`${currentLesson?.title || 'Lesson Player'} — ${course.title}`"
    :breadcrumbs="breadcrumbs"
  >
    <div class="space-y-6 pb-20 relative">

      <!-- Toast Notification -->
      <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform -translate-y-4 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-4 opacity-0"
      >
        <div
          v-if="showToast"
          :class="[
            toastType === 'success' ? 'bg-emerald-600 text-white border-emerald-400' :
            toastType === 'warning' ? 'bg-amber-600 text-white border-amber-400' :
            'bg-rose-600 text-white border-rose-400',
            'fixed top-20 right-6 z-50 p-4 rounded-2xl font-bold text-xs shadow-2xl flex items-center gap-3 border animate-in'
          ]"
        >
          <span class="text-base">{{ toastType === 'success' ? '🎉' : toastType === 'warning' ? '⚠️' : '❌' }}</span>
          <span>{{ toastMessage }}</span>
        </div>
      </transition>

      <!-- Retry Progress Toast (When network request fails) -->
      <div
        v-if="showRetryProgress"
        class="fixed bottom-24 right-6 z-50 p-3.5 rounded-2xl bg-slate-900 text-white border border-slate-700 shadow-2xl flex items-center gap-3 text-xs"
      >
        <span class="text-amber-400">⚠️ Failed to sync progress</span>
        <button
          @click="saveVideoProgress(currentSeconds)"
          class="px-2.5 py-1 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-semibold transition-colors cursor-pointer"
        >
          Retry
        </button>
      </div>

      <!-- MAIN GRID: Left 8 cols Video & Tabs, Right 4 cols Course Progress & Accordion -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- LEFT 8 COLUMNS: Video Player, Title, Tabs, About, Next/Previous Actions -->
        <div class="lg:col-span-8 space-y-5">

          <!-- Video Title Bar -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-10 h-10 rounded-xl bg-purple-500/10 dark:bg-purple-600/30 text-purple-600 dark:text-purple-400 border border-purple-500/30 dark:border-purple-500/40 flex items-center justify-center text-lg shrink-0 shadow-xs">
                ▶
              </div>
              <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight truncate">
                  {{ currentLesson.title }}
                </h1>
                <p class="text-xs text-purple-600 dark:text-purple-400/90 font-medium truncate">
                  {{ course.title }} • {{ teacherDisplay }}
                </p>
              </div>
            </div>

            <!-- Top Navigation Buttons (< Previous, Next >) - Navigation Only -->
            <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
              <button
                @click="handlePreviousNav"
                :disabled="!previousLessonItem"
                aria-label="Previous lesson"
                type="button"
                :class="[
                  !previousLessonItem ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer',
                  'px-3.5 py-1.5 rounded-xl bg-white dark:bg-slate-800/90 text-slate-700 dark:text-slate-300 text-xs font-semibold border border-slate-200 dark:border-slate-700/80 flex items-center gap-1.5 transition-colors shadow-2xs'
                ]"
              >
                <span>‹</span>
                <span>Previous</span>
              </button>
              <button
                @click="handleNextNav"
                :disabled="!nextLessonItem || nextLessonItem.is_locked"
                aria-label="Next lesson"
                type="button"
                :class="[
                  !nextLessonItem || nextLessonItem.is_locked ? 'opacity-40 cursor-not-allowed' : 'hover:bg-indigo-500 cursor-pointer',
                  'px-3.5 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold shadow-md shadow-indigo-600/20 flex items-center gap-1.5 transition-colors'
                ]"
              >
                <span>Next</span>
                <span>›</span>
              </button>
            </div>
          </div>

          <!-- Video Player Screen -->
          <div
            ref="videoPlayerContainer"
            @mouseenter="handleVideoMouseEnter"
            @mouseleave="handleVideoMouseLeave"
            class="relative rounded-3xl overflow-hidden bg-gradient-to-br from-[#0c1322] via-[#0f172a] to-[#0a0f1d] border border-slate-800 shadow-2xl group transition-all"
          >
            <!-- YouTube Hover Preview Indicator Badge -->
            <transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-2"
            >
              <div
                v-if="isPreviewMode && !isLockedPlay"
                class="absolute top-4 left-4 z-30 flex items-center gap-2 px-3 py-1.5 rounded-full bg-black/85 backdrop-blur-md border border-purple-500/50 text-white text-xs font-semibold shadow-xl pointer-events-none"
              >
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="text-purple-300 font-bold uppercase tracking-wider text-[10px]">YouTube Preview</span>
                <span class="text-slate-400 text-[10px]">🔇 Muted</span>
              </div>
            </transition>

            <!-- 1. Native HTML5 Video Element (If direct MP4) -->
            <div v-if="videoProvider === 'mp4'" class="relative aspect-video w-full bg-black flex items-center justify-center">
              <video
                ref="html5VideoRef"
                :src="currentLesson.video_url!"
                class="w-full h-full object-contain"
                playsinline
                @play="isVideoPlaying = true"
                @pause="isVideoPlaying = false; saveVideoProgress(currentSeconds)"
                @timeupdate="currentSeconds = html5VideoRef?.currentTime || currentSeconds"
              ></video>
            </div>

            <!-- 2. YouTube Iframe Player (If YouTube URL) -->
            <div v-else-if="videoProvider === 'youtube'" class="relative aspect-video w-full bg-black">
              <iframe
                :src="youtubeEmbedUrl"
                class="w-full h-full border-0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
              ></iframe>
            </div>

            <!-- 3. Standard Design Graphic Banner & Simulation Player (When no external video URL yet) -->
            <div
              v-else
              class="relative aspect-video w-full flex items-center justify-center p-6 sm:p-12 overflow-hidden select-none bg-[#090e1a]"
            >
              <!-- Ambient background pattern -->
              <div class="absolute inset-0 bg-[radial-gradient(#6366f1_1px,transparent_1px)] [background-size:24px_24px] opacity-15 pointer-events-none"></div>

              <div class="relative z-10 w-full max-w-2xl flex flex-col md:flex-row items-center justify-between gap-6">
                <!-- Left Title Banner -->
                <div class="space-y-3 text-left">
                  <div class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-amber-400 text-slate-950 font-black text-xs shadow-md">
                    {{ course.code ? course.code.split('-')[1] || 'SPI' : 'SPI' }}
                  </div>
                  <h2 class="text-2xl sm:text-3xl font-extrabold text-white leading-tight tracking-tight">
                    {{ currentLesson.title }}
                  </h2>
                  <p class="text-xs text-slate-400">
                    {{ course.title }}
                  </p>
                </div>

                <!-- Right Code Editor Graphic Mockup -->
                <div
                  class="relative w-64 sm:w-72 rounded-2xl bg-slate-900/90 border border-slate-700/80 p-4 shadow-2xl backdrop-blur-md transition-all duration-300"
                  :class="isVideoPlaying ? 'ring-2 ring-purple-500/40 shadow-purple-500/20' : ''"
                >
                  <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-[10px] text-slate-400">
                    <div class="flex items-center gap-1.5">
                      <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                      <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                      <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="flex items-center gap-2">
                      <div v-if="isVideoPlaying" class="flex items-center gap-1 px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-300 text-[9px] font-bold">
                        <span>LIVE</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                      </div>
                      <span class="font-mono text-purple-400">{...}</span>
                    </div>
                  </div>
                  <div class="space-y-1.5 font-mono text-[11px] leading-relaxed">
                    <div
                      class="flex items-center gap-2 p-1 rounded transition-colors"
                      :class="codeSnippetStep === 0 && isVideoPlaying ? 'bg-purple-500/20' : ''"
                    >
                      <div class="w-12 h-2 rounded bg-purple-400/60"></div>
                      <div class="w-20 h-2 rounded bg-blue-400/60"></div>
                    </div>
                    <div
                      class="flex items-center gap-2 pl-3 p-1 rounded transition-colors"
                      :class="codeSnippetStep === 1 && isVideoPlaying ? 'bg-indigo-500/20' : ''"
                    >
                      <div class="w-16 h-2 rounded bg-amber-400/60"></div>
                      <div class="w-10 h-2 rounded bg-emerald-400/60"></div>
                    </div>
                    <div
                      class="flex items-center gap-2 pl-3 p-1 rounded transition-colors"
                      :class="codeSnippetStep === 2 && isVideoPlaying ? 'bg-cyan-500/20' : ''"
                    >
                      <div class="w-24 h-2 rounded bg-cyan-400/60"></div>
                    </div>
                    <div
                      class="flex items-center gap-2 p-1 rounded transition-colors"
                      :class="codeSnippetStep === 3 && isVideoPlaying ? 'bg-purple-500/20' : ''"
                    >
                      <div class="w-8 h-2 rounded bg-purple-400/60"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Overlay Play/Pause Button -->
              <button
                @click="togglePlayPause"
                aria-label="Toggle play pause"
                type="button"
                :class="[
                  isPreviewMode && !isLockedPlay ? 'opacity-80 scale-95' : 'hover:scale-110 active:scale-95',
                  'absolute inset-0 m-auto w-16 h-16 rounded-full bg-purple-600/90 hover:bg-purple-500 text-white flex items-center justify-center shadow-2xl shadow-purple-600/40 transition-all cursor-pointer z-20'
                ]"
              >
                <span v-if="!isVideoPlaying" class="text-xl ml-1">▶</span>
                <span v-else class="text-xl">❚❚</span>
              </button>
            </div>

            <!-- Video Player Bottom Control Bar -->
            <div class="bg-slate-950/90 border-t border-slate-800/80 px-4 py-3 space-y-2">
              <!-- Scrub bar with YouTube Hover Time Tooltip -->
              <div
                @click="seekVideo"
                @mousemove="handleScrubberMouseMove"
                @mouseleave="scrubHoverPercent = null"
                aria-label="Seek progress bar"
                class="relative w-full h-3 flex items-center cursor-pointer group/bar"
              >
                <!-- YouTube Scrub Hover Time Tooltip -->
                <div
                  v-if="scrubHoverPercent !== null"
                  class="absolute -top-7 transform -translate-x-1/2 px-2 py-0.5 rounded bg-slate-900 border border-slate-700 text-white text-[10px] font-mono shadow-md pointer-events-none z-30"
                  :style="{ left: scrubHoverPercent + '%' }"
                >
                  {{ scrubHoverTime }}
                </div>

                <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden relative">
                  <div
                    class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full transition-all duration-150 relative"
                    :style="{ width: progressFillPercentage + '%' }"
                  ></div>
                </div>
              </div>

              <div class="flex items-center justify-between text-xs text-slate-300">
                <div class="flex items-center gap-3">
                  <button
                    @click="togglePlayPause"
                    aria-label="Play pause control"
                    class="hover:text-white transition-colors cursor-pointer"
                  >
                    <span v-if="!isVideoPlaying">▶</span>
                    <span v-else>❚❚</span>
                  </button>
                  <button
                    @click="isMuted = !isMuted"
                    aria-label="Mute unmute"
                    class="hover:text-white transition-colors cursor-pointer"
                  >
                    <span v-if="!isMuted">🔊</span>
                    <span v-else>🔇</span>
                  </button>
                  <span class="text-[11px] font-sans tabular-nums text-slate-400">
                    {{ currentWatchedFormatted }} / {{ currentDurationFormatted }}
                  </span>
                </div>

                <div class="flex items-center gap-3">
                  <button
                    @click="videoSpeed = videoSpeed === '1x' ? '1.25x' : videoSpeed === '1.25x' ? '1.5x' : videoSpeed === '1.5x' ? '2x' : '1x'"
                    aria-label="Playback speed"
                    class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-[11px] font-bold text-slate-300 transition-colors cursor-pointer"
                  >
                    {{ videoSpeed }}
                  </button>
                  <button
                    @click="handleKeydown({ code: 'KeyF', key: 'F', preventDefault: () => {} } as any)"
                    aria-label="Fullscreen toggle"
                    class="hover:text-white transition-colors cursor-pointer text-slate-400"
                  >
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
            <!-- About This Lesson Section (Sentence case 18px semibold) -->
            <div class="space-y-2">
              <h3 class="text-[18px] font-semibold text-slate-900 dark:text-white capitalize">
                About this lesson
              </h3>
              <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                {{ currentLesson.kh_title || currentLesson.title }} — ស្វែងយល់អំពីគោលគំនិតសំខាន់ៗ ការអនុវត្តជាក់ស្តែង និងដំណោះស្រាយក្នុងវគ្គសិក្សា {{ course.title }}។
              </p>
            </div>

            <!-- What you will learn Section -->
            <div class="space-y-2.5">
              <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                What you will learn:
              </h4>
              <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                <li class="flex items-center gap-2.5">
                  <span class="w-4 h-4 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                  <span>Master fundamental concepts of {{ currentLesson.title }}</span>
                </li>
                <li class="flex items-center gap-2.5">
                  <span class="w-4 h-4 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                  <span>Analyze practical laboratory and real-world examples</span>
                </li>
                <li class="flex items-center gap-2.5">
                  <span class="w-4 h-4 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                  <span>Apply knowledge to solve course assignments and quizzes</span>
                </li>
              </ul>
            </div>

            <!-- Need help banner with Ask AI Assistant (Opens FAB Panel) -->
            <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-indigo-50/80 via-white to-purple-50/80 dark:from-indigo-950/60 dark:via-purple-950/40 dark:to-slate-900 border border-purple-200 dark:border-purple-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-2xs dark:shadow-xl">
              <div>
                <h4 class="text-xs font-bold text-slate-900 dark:text-white">Need help understanding this lesson?</h4>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">
                  {{ studentFirstName ? `Hello ${studentFirstName}! ` : '' }}Ask our AI Assistant to explain or give you examples.
                </p>
              </div>

              <button
                @click="openAiAssistant()"
                aria-label="Ask AI Assistant"
                type="button"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/20 flex items-center gap-2 transition-all hover:scale-105 active:scale-95 cursor-pointer shrink-0"
              >
                <span>✨ Ask AI Assistant</span>
                <span>›</span>
              </button>
            </div>
          </div>

          <!-- TAB CONTENT: Lesson Content -->
          <div v-show="activeTab === 'content'" class="space-y-4 text-xs text-slate-700 dark:text-slate-300 leading-relaxed bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-2xs">
            <h4 class="text-[18px] font-semibold text-slate-900 dark:text-white capitalize">Lesson content & notes</h4>
            <p>1. <strong>Overview</strong>: {{ currentLesson.title }} provides foundational insight into the course curriculum.</p>
            <p>2. <strong>Key Concepts</strong>: Follow the lecture carefully, take notes, and complete all self-assessment questions.</p>
          </div>

          <!-- TAB CONTENT: Resources -->
          <div v-show="activeTab === 'resources'" class="space-y-3">
            <div class="p-3.5 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-between gap-3 text-xs shadow-2xs">
              <div class="flex items-center gap-2.5">
                <span class="text-base">📄</span>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">{{ currentLesson.title }} — Lecture Notes.pdf</p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">PDF Document • 2.5 MB</p>
                </div>
              </div>
              <button class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-purple-600 dark:text-purple-400 text-xs font-bold border border-slate-200 dark:border-slate-700 cursor-pointer">
                Download
              </button>
            </div>
          </div>

          <!-- TAB CONTENT: Discussion -->
          <div v-show="activeTab === 'discussion'" class="space-y-4">
            <div class="flex items-center gap-3">
              <input
                type="text"
                placeholder="Ask a question about this lesson..."
                class="flex-1 px-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-purple-500 shadow-2xs"
              />
              <button class="px-4 py-2 rounded-xl bg-purple-600 text-white font-bold text-xs hover:bg-purple-500 cursor-pointer">
                Post
              </button>
            </div>
          </div>

          <!-- Bottom Action Buttons: Previous Lesson & Single Complete & Next Action -->
          <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
            <button
              @click="handlePreviousNav"
              :disabled="!previousLessonItem"
              aria-label="Previous lesson"
              type="button"
              :class="[
                !previousLessonItem ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-200 dark:hover:bg-slate-700 cursor-pointer',
                'px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 flex items-center gap-2 transition-colors shadow-2xs'
              ]"
            >
              <span>←</span>
              <span>Previous Lesson</span>
            </button>

            <!-- Single Complete & Next Button (Records progress & validates >= 90%) -->
            <button
              @click="handleCompleteAndNext"
              :disabled="isCompleting"
              aria-label="Complete and next lesson"
              type="button"
              class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/20 flex items-center gap-2 hover:scale-105 active:scale-95 transition-all cursor-pointer"
            >
              <span v-if="isCompleting">Saving...</span>
              <span v-else-if="isLessonCompletedLocally">Completed & Next Lesson →</span>
              <span v-else>Complete & Next Lesson →</span>
            </button>
          </div>

        </div>

        <!-- RIGHT 4 COLUMNS: Course Progress & Curriculum Accordion -->
        <div class="lg:col-span-4 space-y-6 sticky top-20">

          <!-- Course Progress Card -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-2xs dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between text-xs font-bold">
              <span class="text-slate-900 dark:text-white">Course Progress</span>
              <span class="text-purple-600 dark:text-purple-400 tabular-nums">{{ localProgressPercent }}%</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">
              {{ localCompletedCount }} / {{ totalLessonsCount || localChapters.flatMap(c => c.lessons).length }} Lessons Completed
            </p>

            <div class="w-full h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <div
                class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full transition-all duration-500"
                :style="{ width: localProgressPercent + '%' }"
              ></div>
            </div>
          </div>

          <!-- Course Content Accordion -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-2xs dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
              <h3 class="text-[18px] font-semibold text-slate-900 dark:text-white capitalize">
                Course content
              </h3>
              <button
                @click="toggleExpandAll"
                type="button"
                aria-label="Expand or collapse all chapters"
                class="text-[11px] font-semibold text-purple-600 dark:text-purple-400 hover:text-purple-500 dark:hover:text-purple-300 transition-colors cursor-pointer"
              >
                Expand All
              </button>
            </div>

            <!-- Chapter Items List -->
            <div class="space-y-2">
              <div
                v-for="chap in localChapters"
                :key="chap.id"
                class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-950/60 overflow-hidden"
              >
                <!-- Chapter Header: Single Chevron with Lucide Icon -->
                <button
                  @click="toggleChapter(chap)"
                  type="button"
                  :class="[
                    chap.is_locked ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/40',
                    'w-full p-3 flex items-center justify-between text-xs transition-colors'
                  ]"
                >
                  <div class="flex items-center gap-2 text-left truncate">
                    <span class="font-bold text-slate-800 dark:text-slate-200 truncate">
                      {{ chap.kh_title || chap.title }}
                    </span>
                  </div>

                  <div class="flex items-center gap-2 shrink-0">
                    <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400 tabular-nums">
                      {{ chap.progress }}
                    </span>

                    <!-- Single Clean Chevron with rotation -->
                    <svg
                      :class="[chap.expanded ? 'rotate-180' : 'rotate-0', 'w-3.5 h-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-200 shrink-0']"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="2"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                  </div>
                </button>

                <!-- Lessons inside chapter -->
                <div
                  v-show="chap.expanded"
                  class="border-t border-slate-200 dark:border-slate-800/60 p-2 space-y-1 bg-white dark:bg-slate-950/90"
                >
                  <div
                    v-for="lsn in chap.lessons"
                    :key="lsn.id"
                    @click="!lsn.is_locked && router.visit(`/student/my-courses/${course.id}/lessons/${lsn.id}`)"
                    :class="[
                      lsn.id === currentLesson.id
                        ? 'bg-purple-50 dark:bg-purple-600/25 border-purple-300 dark:border-purple-500/40 text-purple-900 dark:text-white font-bold'
                        : lsn.is_locked
                        ? 'text-slate-400 dark:text-slate-600 border-transparent opacity-60 cursor-not-allowed'
                        : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-900 border-transparent cursor-pointer',
                      'p-2.5 rounded-xl border text-xs flex items-center justify-between transition-colors'
                    ]"
                  >
                    <div class="flex items-center gap-2.5 truncate">
                      <!-- Completed Checkmark -->
                      <span
                        v-if="lsn.is_completed"
                        class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold shrink-0"
                      >
                        ✓
                      </span>
                      <!-- Active Play Icon -->
                      <span
                        v-else-if="lsn.id === currentLesson.id"
                        class="w-4 h-4 rounded-full bg-purple-600 text-white flex items-center justify-center text-[9px] shrink-0"
                      >
                        ▶
                      </span>
                      <!-- Locked Lucide Icon -->
                      <svg
                        v-else-if="lsn.is_locked"
                        class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                      </svg>
                      <!-- Uncompleted circle -->
                      <span
                        v-else
                        class="w-4 h-4 rounded-full border border-slate-300 dark:border-slate-600 flex items-center justify-center text-[9px] shrink-0"
                      >
                        ○
                      </span>

                      <span class="truncate">{{ lsn.title }}</span>
                    </div>

                    <span class="text-[10px] text-slate-500 dark:text-slate-400 shrink-0 tabular-nums font-sans">
                      {{ lsn.duration_formatted || formatSeconds(lsn.duration_seconds) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- Mobile Sticky Bottom Bar for Complete & Next Lesson -->
      <div class="fixed bottom-0 left-0 right-0 p-3 bg-white/95 dark:bg-slate-900/95 border-t border-slate-200 dark:border-slate-800 z-30 lg:hidden shadow-lg pb-[max(0.75rem,env(safe-area-inset-bottom))] flex items-center justify-between gap-3">
        <button
          @click="handlePreviousNav"
          :disabled="!previousLessonItem"
          type="button"
          class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold"
        >
          ← Prev
        </button>
        <button
          @click="handleCompleteAndNext"
          :disabled="isCompleting"
          type="button"
          class="flex-1 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold text-center shadow-md shadow-purple-600/30"
        >
          <span v-if="isCompleting">Saving...</span>
          <span v-else>Complete & Next Lesson →</span>
        </button>
      </div>

    </div>
  </StudentLayout>
</template>
