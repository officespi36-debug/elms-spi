<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { marked } from 'marked'
import hljs from 'highlight.js'
import 'highlight.js/styles/github-dark.css'

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
  summary?: string | null
  objectives?: string[] | null
  content?: string | null
  preview_url?: string | null
  video_provider?: string | null
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
  resources?: Array<any>
  comments?: Array<any>
  discussionCounts?: {
    all: number
    unanswered: number
    answered: number
    mine: number
  }
  courseTeacherId?: number
  completionAction?: {
    type: string
    label: string
    can_complete: boolean
    reason?: string
    next_url?: string
  }
  currentUserId?: number
}>()

// Active tab state (respects initial ?tab= query parameter)
const getInitialTab = (): 'overview' | 'content' | 'resources' | 'discussion' => {
  if (typeof window !== 'undefined') {
    const p = new URLSearchParams(window.location.search).get('tab')
    if (p && ['overview', 'content', 'resources', 'discussion'].includes(p)) {
      return p as any
    }
  }
  return 'overview'
}
const activeTab = ref<'overview' | 'content' | 'resources' | 'discussion'>(getInitialTab())
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

    nextTick(() => {
      const el = document.getElementById(`sidebar-lesson-${newLesson.id}`)
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
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
  if (props.currentLesson?.video_provider) {
    return props.currentLesson.video_provider as any
  }
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
  if (videoProvider.value === 'none' && !props.currentLesson?.preview_url) {
    return
  }
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

// ─── Video Progress Tracking API (Monotonic, Backoff Retry, CSRF Handled) ───
let progressTimer: any = null
let retryTimer: any = null
const consecutiveFailures = ref(0)
const lastFailedPayload = ref<{ seconds: number; duration: number } | null>(null)

const getCsrfToken = () => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

const saveVideoProgress = async (secondsToSend: number, isBeacon = false, isRetry = false) => {
  if (!props.currentLesson?.id) return

  // If lesson has no video or already completed & ended, suppress errors
  const isAlreadyCompleted = isLessonCompletedLocally.value && currentSeconds.value >= currentDuration.value

  const safeDuration = Math.max(1, Math.floor(currentDuration.value || 600))
  // Client clamp position to [0, duration]
  const safeSeconds = Math.max(0, Math.min(Math.floor(secondsToSend), safeDuration))

  if (safeSeconds > maxWatchedSeconds.value) {
    maxWatchedSeconds.value = safeSeconds
  }

  const endpoint = `/student/lessons/${props.currentLesson.id}/progress`
  const payload = {
    seconds: safeSeconds,
    duration: safeDuration,
  }

  // sendBeacon handling: pass _token in FormData for CSRF verification
  if (isBeacon && typeof navigator !== 'undefined' && navigator.sendBeacon) {
    try {
      const formData = new FormData()
      formData.append('_token', getCsrfToken())
      formData.append('seconds', String(payload.seconds))
      formData.append('duration', String(payload.duration))
      navigator.sendBeacon(endpoint, formData)
      return
    } catch {
      return
    }
  }

  try {
    isSavingProgress.value = true
    let token = getCsrfToken()

    let response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })

    // If HTTP 419 (CSRF token expired): read token and retry once
    if (response.status === 419) {
      try {
        const freshMeta = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
        if (freshMeta) token = freshMeta
      } catch {}

      response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
    }

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    // Success: reset failure count & hide retry toast
    consecutiveFailures.value = 0
    showRetryProgress.value = false
    lastFailedPayload.value = null
    if (retryTimer) clearTimeout(retryTimer)

  } catch (err) {
    console.warn('Progress save failed:', err)

    if (isAlreadyCompleted) {
      return
    }

    lastFailedPayload.value = payload
    consecutiveFailures.value += 1

    // Show toast only after 3 consecutive failures
    if (consecutiveFailures.value >= 3) {
      showRetryProgress.value = true
    }

    // Auto retry with backoff 2s / 5s / 15s
    const backoffs = [2000, 5000, 15000]
    const delay = backoffs[Math.min(consecutiveFailures.value - 1, backoffs.length - 1)]

    if (retryTimer) clearTimeout(retryTimer)
    retryTimer = setTimeout(() => {
      if (lastFailedPayload.value && typeof navigator !== 'undefined' && navigator.onLine !== false) {
        saveVideoProgress(lastFailedPayload.value.seconds, false, true)
      }
    }, delay)

  } finally {
    isSavingProgress.value = false
  }
}

// Playback Simulation (Only runs when a real video provider exists)
const startPlaybackSimulation = () => {
  if (progressTimer) clearInterval(progressTimer)
  if (videoProvider.value === 'none') return

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
  if (html5VideoRef.value) {
    html5VideoRef.value.currentTime = currentSeconds.value
  }
  if (currentSeconds.value > maxWatchedSeconds.value) {
    maxWatchedSeconds.value = currentSeconds.value
  }
  saveVideoProgress(currentSeconds.value)
}

watch(videoSpeed, (spd) => {
  if (html5VideoRef.value) {
    html5VideoRef.value.playbackRate = parseFloat(spd) || 1
  }
})

watch(isMuted, (muted) => {
  if (html5VideoRef.value) {
    html5VideoRef.value.muted = muted
  }
})

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

// ─── Reactive Resources & Discussion Comments ───
const localResources = ref<any[]>(props.resources || [])
watch(() => props.resources, (newRes) => {
  localResources.value = newRes || []
})

const localComments = ref<any[]>(props.comments || [])
watch(() => props.comments, (newComms) => {
  localComments.value = newComms || []
})

// ─── Discussion: State, Filter & Sort ───
const discussionSort = ref<'newest' | 'oldest' | 'helpful'>('newest')
const discussionFilter = ref<'all' | 'unanswered' | 'answered' | 'mine'>('all')
const discussionCounts = ref({
  all: props.discussionCounts?.all ?? props.comments?.length ?? 0,
  unanswered: props.discussionCounts?.unanswered ?? 0,
  answered: props.discussionCounts?.answered ?? 0,
  mine: props.discussionCounts?.mine ?? 0,
})

const isFetchingComments = ref(false)
const currentCommentsPage = ref(1)
const hasMoreComments = ref(false)
const isLoadingMoreComments = ref(false)

const newCommentText = ref('')
const newCommentVideoSeconds = ref<number | null>(null)
const isPostingComment = ref(false)
const commentTextareaRef = ref<HTMLTextAreaElement | null>(null)

const replyText = ref('')
const replyingToId = ref<number | null>(null)
const isPostingReply = ref(false)

const editingCommentId = ref<number | null>(null)
const editCommentText = ref('')
const isSavingEdit = ref(false)

const deletingCommentId = ref<number | null>(null)
const expandedReplies = ref<Record<number, boolean>>({})
const highlightedCommentId = ref<number | null>(null)
const activeMenuCommentId = ref<number | null>(null)
const newCommentsPillCount = ref(0)

// Report modal state
const reportModalOpen = ref(false)
const reportingComment = ref<any>(null)
const reportReason = ref('')
const isSubmittingReport = ref(false)

// ─── Discussion: Helper Actions ───
const focusComposer = () => {
  commentTextareaRef.value?.focus()
  commentTextareaRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

const seekToTimestamp = (seconds: number) => {
  const safeSec = Math.max(0, Math.floor(seconds))
  if (html5VideoRef.value) {
    html5VideoRef.value.currentTime = safeSec
    currentSeconds.value = safeSec
    html5VideoRef.value.play().catch(() => {})
    isVideoPlaying.value = true
  }
  videoPlayerContainer.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

const insertCurrentVideoTime = () => {
  const sec = Math.floor(currentSeconds.value || 0)
  const formatted = formatSeconds(sec)
  if (!newCommentText.value.includes(formatted)) {
    newCommentText.value = newCommentText.value ? `${newCommentText.value} ${formatted}` : formatted
  }
  newCommentVideoSeconds.value = sec
  commentTextareaRef.value?.focus()
}

const canEditComment = (comm: any) => {
  if (!comm || comm.user_id !== props.currentUserId) return false
  if (!comm.created_at) return false
  const created = new Date(comm.created_at).getTime()
  return (Date.now() - created) <= (15 * 60 * 1000)
}

const toggleReplies = (commId: number) => {
  expandedReplies.value[commId] = !expandedReplies.value[commId]
}

const toggleCommentMenu = (id: number) => {
  activeMenuCommentId.value = activeMenuCommentId.value === id ? null : id
}

const closeCommentMenu = () => {
  activeMenuCommentId.value = null
}

const renderCommentBodyHtml = (body: string) => {
  if (!body) return ''
  // 1. Escape HTML entities to prevent XSS
  const escaped = body
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')

  // 2. Linkify safe URLs
  const urlPattern = /(https?:\/\/[^\s<]+)/g
  const linkified = escaped.replace(urlPattern, (url) => {
    return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-purple-600 dark:text-purple-400 font-semibold underline hover:opacity-80">${url}</a>`
  })

  // 3. Highlight timestamps (e.g. 07:40, 1:02:15) as interactive jump chips
  const timePattern = /\b(?:(\d{1,2}):)?([0-5]?\d):([0-5]\d)\b/g
  return linkified.replace(timePattern, (match) => {
    return `<button type="button" data-timestamp="${match}" class="inline-flex items-center gap-1 px-1.5 py-0.5 mx-0.5 rounded-md bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/40 dark:hover:bg-purple-800/60 text-purple-700 dark:text-purple-300 font-mono text-[11px] font-bold transition-all cursor-pointer select-none">⏱️ ${match}</button>`
  })
}

const handleCommentBodyClick = (e: MouseEvent) => {
  const target = (e.target as HTMLElement).closest('[data-timestamp]')
  if (target) {
    const ts = target.getAttribute('data-timestamp')
    if (ts) {
      e.preventDefault()
      const parts = ts.split(':').map(Number)
      let seconds = 0
      if (parts.length === 3) {
        seconds = parts[0] * 3600 + parts[1] * 60 + parts[2]
      } else if (parts.length === 2) {
        seconds = parts[0] * 60 + parts[1]
      }
      seekToTimestamp(seconds)
    }
  }
}

// ─── Discussion: API Fetching ───
const fetchComments = async (page = 1, append = false) => {
  if (!props.currentLesson?.id) return
  try {
    if (append) {
      isLoadingMoreComments.value = true
    } else {
      isFetchingComments.value = true
    }
    const url = `/lessons/${props.currentLesson.id}/comments?sort=${discussionSort.value}&filter=${discussionFilter.value}&page=${page}`
    const res = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      }
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()

    if (append) {
      localComments.value = [...localComments.value, ...(data.data || [])]
    } else {
      localComments.value = data.data || []
    }
    currentCommentsPage.value = data.current_page || page
    hasMoreComments.value = !!data.has_more
    if (data.counts) {
      discussionCounts.value = data.counts
    }
    newCommentsPillCount.value = 0
  } catch (err) {
    console.error('Failed to fetch comments', err)
  } finally {
    isFetchingComments.value = false
    isLoadingMoreComments.value = false
  }
}

watch([discussionSort, discussionFilter], () => {
  fetchComments(1, false)
})

// ─── Discussion: Post Question / Reply (Optimistic UI) ───
const postComment = async (parentId: number | null = null) => {
  const text = (parentId ? replyText.value : newCommentText.value).trim()
  if (text.length < 2 || isPostingComment.value || !props.currentLesson?.id) return

  const tempId = Date.now()
  const videoSec = parentId ? null : newCommentVideoSeconds.value

  const optimisticComment: any = {
    id: tempId,
    lesson_id: props.currentLesson.id,
    user_id: props.currentUserId || 0,
    parent_id: parentId,
    body: text,
    video_seconds: videoSec,
    video_timestamp_formatted: videoSec !== null ? formatSeconds(videoSec) : null,
    created_at: new Date().toISOString(),
    relative_time: 'Just now',
    likes_count: 0,
    liked_by_me: false,
    is_answered: false,
    role_label: (props.currentUserId === (props.courseTeacherId || props.course?.teacher_id)) ? 'instructor' : 'student',
    user: {
      id: props.currentUserId || 0,
      name: props.studentFirstName ? `${props.studentFirstName} (You)` : 'You',
      avatar: null,
      role: 'student',
    },
    replies: [],
    _isOptimistic: true,
  }

  // Optimistic insert
  if (parentId) {
    const parent = localComments.value.find(c => c.id === parentId)
    if (parent) {
      if (!parent.replies) parent.replies = []
      parent.replies.push(optimisticComment)
      expandedReplies.value[parentId] = true
    }
    replyText.value = ''
    replyingToId.value = null
  } else {
    localComments.value.unshift(optimisticComment)
    newCommentText.value = ''
    newCommentVideoSeconds.value = null
    discussionCounts.value.all++
    discussionCounts.value.unanswered++
    discussionCounts.value.mine++
  }

  try {
    isPostingComment.value = true
    const res = await fetch(`/lessons/${props.currentLesson.id}/comments`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        body: text,
        parent_id: parentId,
        video_seconds: videoSec,
      }),
    })

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}))
      throw new Error(errData.message || errData.error || `HTTP ${res.status}`)
    }

    const data = await res.json()
    const saved = data.comment

    if (parentId) {
      const parent = localComments.value.find(c => c.id === parentId)
      if (parent && parent.replies) {
        const idx = parent.replies.findIndex(r => r.id === tempId)
        if (idx !== -1) parent.replies[idx] = saved
      }
    } else {
      const idx = localComments.value.findIndex(c => c.id === tempId)
      if (idx !== -1) localComments.value[idx] = saved
    }
  } catch (err: any) {
    // Rollback
    if (parentId) {
      const parent = localComments.value.find(c => c.id === parentId)
      if (parent && parent.replies) {
        parent.replies = parent.replies.filter(r => r.id !== tempId)
      }
    } else {
      localComments.value = localComments.value.filter(c => c.id !== tempId)
      discussionCounts.value.all = Math.max(0, discussionCounts.value.all - 1)
      discussionCounts.value.unanswered = Math.max(0, discussionCounts.value.unanswered - 1)
      discussionCounts.value.mine = Math.max(0, discussionCounts.value.mine - 1)
    }
    toastType.value = 'warning'
    toastMessage.value = err.message || 'Failed to post comment'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 4000)
  } finally {
    isPostingComment.value = false
  }
}

// ─── Discussion: Edit Comment ───
const startEditComment = (comm: any) => {
  editingCommentId.value = comm.id
  editCommentText.value = comm.body
  activeMenuCommentId.value = null
}

const cancelEditComment = () => {
  editingCommentId.value = null
  editCommentText.value = ''
}

const saveEditComment = async (comm: any) => {
  const text = editCommentText.value.trim()
  if (text.length < 2 || isSavingEdit.value) return
  try {
    isSavingEdit.value = true
    const res = await fetch(`/comments/${comm.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ body: text }),
    })
    if (!res.ok) {
      const errData = await res.json().catch(() => ({}))
      throw new Error(errData.message || errData.error || `HTTP ${res.status}`)
    }
    const data = await res.json()
    comm.body = data.comment.body
    comm.edited_at = data.comment.edited_at
    comm.video_seconds = data.comment.video_seconds
    comm.video_timestamp_formatted = data.comment.video_timestamp_formatted
    editingCommentId.value = null
    editCommentText.value = ''
    toastType.value = 'success'
    toastMessage.value = 'Comment updated successfully'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 3000)
  } catch (err: any) {
    toastType.value = 'warning'
    toastMessage.value = err.message || 'Failed to update comment'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 4000)
  } finally {
    isSavingEdit.value = false
  }
}

// ─── Discussion: Delete Comment ───
const deleteComment = async (commentId: number, parentId: number | null = null) => {
  if (!confirm('Are you sure you want to delete this comment?')) return
  activeMenuCommentId.value = null
  try {
    deletingCommentId.value = commentId
    const res = await fetch(`/comments/${commentId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
    })

    if (!res.ok) throw new Error(`HTTP ${res.status}`)

    if (parentId) {
      const parent = localComments.value.find(c => c.id === parentId)
      if (parent && parent.replies) {
        parent.replies = parent.replies.filter(r => r.id !== commentId)
      }
    } else {
      const comm = localComments.value.find(c => c.id === commentId)
      if (comm && comm.replies && comm.replies.length > 0) {
        comm.body = '[Deleted comment]'
        comm.is_deleted = true
      } else {
        localComments.value = localComments.value.filter(c => c.id !== commentId)
        discussionCounts.value.all = Math.max(0, discussionCounts.value.all - 1)
      }
    }
    toastType.value = 'success'
    toastMessage.value = 'Comment deleted'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 3000)
  } catch (err: any) {
    toastType.value = 'warning'
    toastMessage.value = err.message || 'Failed to delete comment'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 4000)
  } finally {
    deletingCommentId.value = null
  }
}

// ─── Discussion: Toggle Like ───
const toggleLike = async (comm: any) => {
  const previousLiked = !!comm.liked_by_me
  const previousCount = comm.likes_count || 0

  comm.liked_by_me = !previousLiked
  comm.likes_count = previousLiked ? Math.max(0, previousCount - 1) : previousCount + 1

  try {
    const res = await fetch(`/comments/${comm.id}/like`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()
    comm.liked_by_me = data.liked
    comm.likes_count = data.likes_count
  } catch {
    comm.liked_by_me = previousLiked
    comm.likes_count = previousCount
  }
}

// ─── Discussion: Toggle Accepted Answer ───
const toggleAnswer = async (parentQuestion: any, replyComm: any) => {
  try {
    const res = await fetch(`/comments/${replyComm.id}/answer`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
    })
    if (!res.ok) {
      const err = await res.json().catch(() => ({}))
      throw new Error(err.message || err.error || 'Failed to mark answer')
    }
    const data = await res.json()
    parentQuestion.is_answered = data.is_answered
    parentQuestion.answered_comment_id = data.answered_comment_id

    if (data.is_answered) {
      discussionCounts.value.answered++
      discussionCounts.value.unanswered = Math.max(0, discussionCounts.value.unanswered - 1)
    } else {
      discussionCounts.value.unanswered++
      discussionCounts.value.answered = Math.max(0, discussionCounts.value.answered - 1)
    }
  } catch (err: any) {
    toastType.value = 'warning'
    toastMessage.value = err.message || 'Failed to mark answer'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 3500)
  }
}

// ─── Discussion: Report Comment ───
const openReportModal = (comm: any) => {
  reportingComment.value = comm
  reportReason.value = ''
  reportModalOpen.value = true
  activeMenuCommentId.value = null
}

const submitReport = async () => {
  if (!reportingComment.value || !reportReason.value.trim() || isSubmittingReport.value) return
  try {
    isSubmittingReport.value = true
    const res = await fetch(`/comments/${reportingComment.value.id}/report`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ reason: reportReason.value.trim() }),
    })
    if (!res.ok) throw new Error('Failed to submit report')
    reportModalOpen.value = false
    toastType.value = 'success'
    toastMessage.value = 'Report submitted to moderators'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 3500)
  } catch (err: any) {
    toastType.value = 'warning'
    toastMessage.value = err.message || 'Failed to report comment'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 3500)
  } finally {
    isSubmittingReport.value = false
  }
}

// ─── Discussion: Hash Navigation (#comment-123) ───
const checkHashAndHighlight = () => {
  if (typeof window === 'undefined') return
  const hash = window.location.hash
  if (hash && hash.startsWith('#comment-')) {
    const id = parseInt(hash.replace('#comment-', ''), 10)
    if (!isNaN(id)) {
      activeTab.value = 'discussion'
      highlightedCommentId.value = id
      setTimeout(() => {
        const el = document.getElementById(`comment-${id}`)
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }
      }, 400)
      setTimeout(() => {
        highlightedCommentId.value = null
      }, 3500)
    }
  }
}

// ─── Discussion: Polling (30s) ───
let discussionPollTimer: any = null
const pollForNewComments = async () => {
  if (activeTab.value !== 'discussion' || !props.currentLesson?.id) return
  try {
    const res = await fetch(`/lessons/${props.currentLesson.id}/comments?sort=newest&filter=all&page=1`)
    if (res.ok) {
      const data = await res.json()
      const serverTotal = data.total_questions_count || 0
      const localTotal = discussionCounts.value.all || 0
      if (serverTotal > localTotal) {
        newCommentsPillCount.value = serverTotal - localTotal
      }
    }
  } catch {}
}

// ─── Tab State & Keyboard Accessibility ───
const tabList = ['overview', 'content', 'resources', 'discussion'] as const
const setTab = (tab: 'overview' | 'content' | 'resources' | 'discussion') => {
  activeTab.value = tab
  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href)
    url.searchParams.set('tab', tab)
    window.history.replaceState({}, '', url.toString())
  }
}

const handleTabKeydown = (e: KeyboardEvent, current: typeof tabList[number]) => {
  const currentIndex = tabList.indexOf(current)
  if (e.key === 'ArrowRight') {
    e.preventDefault()
    const nextTab = tabList[(currentIndex + 1) % tabList.length]
    setTab(nextTab)
    document.getElementById(`tab-${nextTab}`)?.focus()
  } else if (e.key === 'ArrowLeft') {
    e.preventDefault()
    const prevTab = tabList[(currentIndex - 1 + tabList.length) % tabList.length]
    setTab(prevTab)
    document.getElementById(`tab-${prevTab}`)?.focus()
  } else if (e.key === 'Home') {
    e.preventDefault()
    setTab(tabList[0])
    document.getElementById(`tab-${tabList[0]}`)?.focus()
  } else if (e.key === 'End') {
    e.preventDefault()
    setTab(tabList[tabList.length - 1])
    document.getElementById(`tab-${tabList[tabList.length - 1]}`)?.focus()
  }
}

// ─── Safe Markdown Renderer with Syntax Highlighting & Copy Button ───
const markdownRenderer = {
  link({ href, title, text }: { href: string; title?: string | null; text: string }) {
    const cleanHref = href && href.startsWith('javascript:') ? '#' : href
    return `<a href="${cleanHref}" target="_blank" rel="noopener nofollow" class="text-purple-600 dark:text-purple-400 hover:underline font-semibold inline-flex items-center gap-1">${text}</a>`
  },
  heading({ tokens, depth }: { tokens: any[]; depth: number }) {
    const text = tokens.map((t: any) => t.raw || t.text).join('')
    const classes = [
      '',
      'text-base sm:text-lg font-extrabold text-slate-900 dark:text-white mt-6 mb-3 tracking-tight pb-2 border-b border-slate-200 dark:border-slate-800',
      'text-sm sm:text-base font-bold text-slate-900 dark:text-white mt-5 mb-2.5',
      'text-xs sm:text-sm font-bold text-slate-900 dark:text-white mt-4 mb-2',
      'text-xs font-semibold text-slate-800 dark:text-slate-200 mt-3 mb-1.5',
      'text-xs font-semibold text-slate-800 dark:text-slate-200 mt-2 mb-1',
      'text-xs font-semibold text-slate-700 dark:text-slate-300 mt-2 mb-1'
    ][depth] || 'text-xs sm:text-sm font-bold text-slate-900 dark:text-white mt-4 mb-2'
    return `<h${depth} class="${classes}">${text}</h${depth}>`
  },
  list({ items, ordered }: { items: any[]; ordered: boolean }) {
    const tag = ordered ? 'ol' : 'ul'
    const listClass = ordered ? 'list-decimal list-inside space-y-1.5 my-3' : 'list-disc list-inside space-y-1.5 my-3'
    const content = items.map((i: any) => `<li class="text-xs text-slate-700 dark:text-slate-300">${i.tokens ? i.tokens.map((t: any) => t.raw || t.text).join('') : i.text}</li>`).join('')
    return `<${tag} class="${listClass}">${content}</${tag}>`
  },
  blockquote({ tokens }: { tokens: any[] }) {
    const text = tokens.map((t: any) => t.raw || t.text).join('')
    return `<blockquote class="border-l-4 border-purple-500 pl-4 py-2 my-3 bg-purple-50/50 dark:bg-purple-950/20 text-slate-700 dark:text-slate-300 italic text-xs rounded-r-xl">${text}</blockquote>`
  },
  table({ header, rows }: { header: any[]; rows: any[][] }) {
    let headerHtml = '<tr>'
    for (const h of header) {
      headerHtml += `<th class="p-2.5 text-left text-xs font-bold bg-slate-100 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">${h.tokens?.map((t: any) => t.raw || t.text).join('') || h.text}</th>`
    }
    headerHtml += '</tr>'

    let rowsHtml = ''
    for (const r of rows) {
      rowsHtml += '<tr class="border-b border-slate-100 dark:border-slate-800/60 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">'
      for (const cell of r) {
        rowsHtml += `<td class="p-2.5 text-xs text-slate-700 dark:text-slate-300">${cell.tokens?.map((t: any) => t.raw || t.text).join('') || cell.text}</td>`
      }
      rowsHtml += '</tr>'
    }

    return `<div class="overflow-x-auto my-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs"><table class="w-full text-left border-collapse"><thead>${headerHtml}</thead><tbody>${rowsHtml}</tbody></table></div>`
  },
  codespan({ text }: { text: string }) {
    return `<code class="px-1.5 py-0.5 rounded-md bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/40 font-mono text-[11px] font-semibold">${text}</code>`
  },
  code({ text, lang }: { text: string; lang?: string }) {
    const rawTrimmed = (text || '').trim()
    if (!rawTrimmed) {
      return `
<div class="my-4 rounded-2xl overflow-hidden border border-slate-800 bg-slate-950 shadow-xl">
  <div class="flex items-center justify-between px-4 py-2 bg-slate-900 border-b border-slate-800 text-xs">
    <span class="font-mono font-bold text-[11px] text-purple-400 uppercase tracking-wider">${(lang || 'code').toUpperCase()}</span>
  </div>
  <div class="p-4 text-xs font-mono text-slate-500 italic">Code snippet is empty</div>
</div>`
    }

    const cleanLang = (lang || '').toLowerCase().trim()
    const validLang = cleanLang && hljs.getLanguage(cleanLang) ? cleanLang : null
    let highlighted = ''
    try {
      highlighted = validLang ? hljs.highlight(text, { language: validLang }).value : hljs.highlightAuto(text).value
    } catch {
      highlighted = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
    }
    const displayLang = (cleanLang || 'code').toUpperCase()
    const encoded = encodeURIComponent(text)

    return `
<div class="my-4 rounded-2xl overflow-hidden border border-slate-800 bg-slate-950 shadow-xl group">
  <div class="flex items-center justify-between px-4 py-2 bg-slate-900/95 border-b border-slate-800 text-xs">
    <div class="flex items-center gap-2">
      <span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-500/80"></span>
      <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500/80"></span>
      <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500/80"></span>
      <span class="font-mono font-bold text-[11px] text-purple-400 uppercase tracking-wider ml-1.5">${displayLang}</span>
    </div>
    <button type="button" class="lesson-copy-code-btn flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors text-[11px] font-semibold cursor-pointer" data-code="${encoded}">
      <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
      <span class="copy-label">Copy</span>
    </button>
  </div>
  <pre class="p-4 text-[13px] font-mono leading-relaxed overflow-x-auto text-slate-200 max-w-full"><code>${highlighted}</code></pre>
</div>`
  }
}

marked.use({ renderer: markdownRenderer })

const parsedContent = computed(() => {
  const raw = props.currentLesson?.content || ''
  if (!raw.trim()) return ''
  try {
    const html = marked.parse(raw) as string
    return html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
  } catch {
    return `<div class="text-rose-500 text-xs">Error parsing content</div>`
  }
})

// Event delegation handler for Copy Code button inside rendered markdown
const handleContentClick = (e: MouseEvent) => {
  const target = (e.target as HTMLElement).closest('.lesson-copy-code-btn') as HTMLButtonElement | null
  if (!target) return
  const rawCode = target.getAttribute('data-code')
  if (!rawCode) return
  const decoded = decodeURIComponent(rawCode)
  navigator.clipboard.writeText(decoded).then(() => {
    const label = target.querySelector('.copy-label')
    if (label) {
      const orig = label.textContent
      label.textContent = 'Copied!'
      target.classList.add('bg-emerald-600', 'text-white')
      setTimeout(() => {
        label.textContent = orig || 'Copy'
        target.classList.remove('bg-emerald-600', 'text-white')
      }, 1500)
    }
  }).catch(() => {})
}

// ─── Real-Time Completion Eligibility Computed Properties ───
const isEligibleToComplete = computed(() => {
  if (isLessonCompletedLocally.value) return true
  if (!currentDuration.value || currentDuration.value <= 0) return true
  const required = Math.round(currentDuration.value * 0.9)
  const watched = Math.max(currentSeconds.value, maxWatchedSeconds.value, props.currentLesson?.watched_seconds || 0)
  return watched >= required
})

const smartActionLabel = computed(() => {
  if (isCompleting.value) return 'Saving...'
  if (isLessonCompletedLocally.value) {
    return nextLessonItem.value ? 'Next Lesson →' : 'Finish Course 🎓'
  }
  if (!isEligibleToComplete.value) {
    const watched = Math.max(currentSeconds.value, maxWatchedSeconds.value, props.currentLesson?.watched_seconds || 0)
    const pct = Math.min(100, Math.floor((watched / (currentDuration.value || 1)) * 100))
    return `Watch 90% to continue (${pct}% / 90%)`
  }
  return nextLessonItem.value ? 'Mark Complete & Continue →' : 'Complete & Finish Course 🎓'
})

const completionNotice = computed(() => {
  if (isLessonCompletedLocally.value) return ''
  if (!isEligibleToComplete.value) {
    const watched = Math.max(currentSeconds.value, maxWatchedSeconds.value, props.currentLesson?.watched_seconds || 0)
    const pct = Math.floor((watched / (currentDuration.value || 1)) * 100)
    return `សូមទស្សនាវីដេអូយ៉ាងតិច 90% សិនមុននឹងចុចបញ្ចប់ (ទស្សនាបាន ${pct}%, ត្រូវការ 90%)`
  }
  return ''
})

// ─── Smart Completion Action Handler ───
const handleSmartAction = () => {
  if (isLessonCompletedLocally.value) {
    if (nextLessonItem.value) {
      handleNextNav()
    } else {
      router.visit(props.completionAction?.next_url || `/student/courses/${props.course.id}/overview`)
    }
  } else {
    if (!isEligibleToComplete.value) {
      toastType.value = 'warning'
      toastMessage.value = completionNotice.value || 'សូមទស្សនាមេរៀនយ៉ាងហោចណាស់ 90% មុននឹងបញ្ចប់មេរៀន។'
      showToast.value = true
      setTimeout(() => { showToast.value = false }, 4000)
      return
    }
    handleCompleteAndNext()
  }
}

// Keyboard shortcuts (Space, Left/Right, F, N)
const handleKeydown = (e: KeyboardEvent) => {
  // Ignore when typing inside input / textarea
  const target = e.target as HTMLElement
  if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return

  if (e.code === 'Space' || e.key === 'k' || e.key === 'K') {
    e.preventDefault()
    togglePlayPause()
  } else if (e.code === 'ArrowLeft') {
    e.preventDefault()
    currentSeconds.value = Math.max(0, currentSeconds.value - 10)
    if (html5VideoRef.value) {
      html5VideoRef.value.currentTime = currentSeconds.value
    }
  } else if (e.code === 'ArrowRight') {
    e.preventDefault()
    currentSeconds.value = Math.min(currentDuration.value, currentSeconds.value + 10)
    if (html5VideoRef.value) {
      html5VideoRef.value.currentTime = currentSeconds.value
    }
    if (currentSeconds.value > maxWatchedSeconds.value) {
      maxWatchedSeconds.value = currentSeconds.value
    }
  } else if (e.key === 'm' || e.key === 'M') {
    e.preventDefault()
    isMuted.value = !isMuted.value
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

// Online status event handler for auto-retry
const handleOnline = () => {
  if (lastFailedPayload.value) {
    saveVideoProgress(lastFailedPayload.value.seconds, false, true)
  }
}

// Unload / sendBeacon handler
const handleBeforeUnload = () => {
  saveVideoProgress(currentSeconds.value, true)
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search)
    const tabParam = params.get('tab')
    if (tabParam && ['overview', 'content', 'resources', 'discussion'].includes(tabParam)) {
      activeTab.value = tabParam as any
    }
    checkHashAndHighlight()
    window.addEventListener('keydown', handleKeydown)
    window.addEventListener('beforeunload', handleBeforeUnload)
    window.addEventListener('online', handleOnline)
    window.addEventListener('click', closeCommentMenu)
    discussionPollTimer = setInterval(pollForNewComments, 30000)
  }
  startPlaybackSimulation()
})

onUnmounted(() => {
  if (progressTimer) clearInterval(progressTimer)
  if (codeAnimationTimer) clearInterval(codeAnimationTimer)
  if (retryTimer) clearTimeout(retryTimer)
  if (discussionPollTimer) clearInterval(discussionPollTimer)
  saveVideoProgress(currentSeconds.value, true)
  if (typeof window !== 'undefined') {
    window.removeEventListener('keydown', handleKeydown)
    window.removeEventListener('beforeunload', handleBeforeUnload)
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('click', closeCommentMenu)
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

      <!-- Retry Progress Toast (Top-right positioned, away from FAB button) -->
      <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-2 opacity-0"
      >
        <div
          v-if="showRetryProgress"
          class="fixed top-24 right-6 z-50 p-3.5 rounded-2xl bg-slate-900/95 text-white border border-slate-700 shadow-2xl flex items-center gap-3 text-xs backdrop-blur-md"
        >
          <span class="text-amber-400">⚠️ Failed to sync progress</span>
          <button
            @click="saveVideoProgress(currentSeconds)"
            class="px-2.5 py-1 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-semibold transition-colors cursor-pointer"
          >
            Retry
          </button>
        </div>
      </transition>

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
                <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight line-clamp-2 leading-snug">
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
                v-if="isPreviewMode && !isLockedPlay && (videoProvider !== 'none' || currentLesson.preview_url)"
                class="absolute top-4 left-4 z-30 flex items-center gap-2 px-3 py-1.5 rounded-full bg-black/85 backdrop-blur-md border border-purple-500/50 text-white text-xs font-semibold shadow-xl pointer-events-none"
              >
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="text-purple-300 font-bold uppercase tracking-wider text-[10px]">{{ videoProvider === 'youtube' ? 'YouTube Preview' : 'Preview' }}</span>
                <span class="text-slate-400 text-[10px]">🔇 Muted</span>
              </div>
            </transition>

            <!-- 1. Native HTML5 Video Element (If direct MP4) -->
            <div v-if="videoProvider === 'mp4'" class="relative aspect-video w-full bg-black flex items-center justify-center overflow-hidden">
              <video
                ref="html5VideoRef"
                :src="currentLesson.video_url!"
                class="w-full h-full object-contain cursor-pointer"
                playsinline
                @click="togglePlayPause"
                @dblclick="handleKeydown({ code: 'KeyF', key: 'F', preventDefault: () => {} } as any)"
                @play="isVideoPlaying = true"
                @pause="isVideoPlaying = false; saveVideoProgress(currentSeconds)"
                @timeupdate="currentSeconds = html5VideoRef?.currentTime || currentSeconds"
              ></video>

              <!-- Central Play Button Overlay (when paused and not in hover preview) -->
              <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-90"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-90"
              >
                <div
                  v-if="!isVideoPlaying && !isPreviewMode"
                  @click="togglePlayPause"
                  class="absolute inset-0 flex items-center justify-center bg-black/25 backdrop-blur-[2px] cursor-pointer group/overlay transition-all"
                >
                  <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-500 text-white flex items-center justify-center shadow-2xl shadow-purple-600/50 group-hover/overlay:scale-110 transition-transform">
                    <svg class="w-8 h-8 sm:w-10 sm:h-10 ml-1 fill-current" viewBox="0 0 24 24">
                      <path d="M8 5v14l11-7z"/>
                    </svg>
                  </div>
                </div>
              </transition>
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

            <!-- 3. Clean Empty State (When no video URL is available - No fake player, no fake play button) -->
            <div
              v-else
              class="relative aspect-video w-full flex flex-col items-center justify-center p-8 sm:p-12 text-center select-none bg-[#090e1a] overflow-hidden"
            >
              <!-- Ambient background pattern -->
              <div class="absolute inset-0 bg-[radial-gradient(#6366f1_1px,transparent_1px)] [background-size:24px_24px] opacity-15 pointer-events-none"></div>

              <div class="relative z-10 max-w-md space-y-4">
                <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-3xl bg-slate-900/90 border border-slate-700/80 text-purple-400 flex items-center justify-center shadow-2xl shadow-purple-900/20">
                  <svg class="w-8 h-8 sm:w-9 sm:h-9 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    <line x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                  </svg>
                </div>
                <div class="space-y-1.5">
                  <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400/10 text-amber-400 border border-amber-400/20 text-[11px] font-bold">
                    <span>⚠️ Video not available yet</span>
                  </div>
                  <h3 class="text-base sm:text-lg font-bold text-white tracking-tight">
                    {{ currentLesson.title }}
                  </h3>
                  <p class="text-xs text-slate-400 leading-relaxed">
                    វីដេអូមេរៀននេះមិនទាន់ត្រូវបានបញ្ចូលនៅឡើយទេ។ អ្នកអាចអានឯកសារមេរៀនខាងក្រោម ឬចុច Complete & Next ដើម្បីបន្តទៅមុខ។
                  </p>
                </div>
              </div>
            </div>

            <!-- Video Player Bottom Control Bar (Shown only when real video exists) -->
            <div
              v-if="videoProvider !== 'none'"
              class="bg-slate-950/90 border-t border-slate-800/80 px-4 py-3 space-y-2"
            >
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
            <nav
              role="tablist"
              aria-label="Lesson Details Tabs"
              class="flex items-center gap-4 sm:gap-6 text-xs font-semibold overflow-x-auto"
            >
              <button
                id="tab-overview"
                role="tab"
                :aria-selected="activeTab === 'overview'"
                aria-controls="panel-overview"
                tabindex="0"
                @click="setTab('overview')"
                @keydown="handleTabKeydown($event, 'overview')"
                :class="[
                  activeTab === 'overview'
                    ? 'text-purple-600 dark:text-purple-400 font-bold border-b-2 border-purple-600 dark:border-purple-400'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border-b-2 border-transparent'
                ]"
                class="pb-3 px-1 transition-all outline-none focus:outline-none focus-visible:text-purple-600 dark:focus-visible:text-purple-400 cursor-pointer"
              >
                Overview
              </button>

              <button
                id="tab-content"
                role="tab"
                :aria-selected="activeTab === 'content'"
                aria-controls="panel-content"
                tabindex="0"
                @click="setTab('content')"
                @keydown="handleTabKeydown($event, 'content')"
                :class="[
                  activeTab === 'content'
                    ? 'text-purple-600 dark:text-purple-400 font-bold border-b-2 border-purple-600 dark:border-purple-400'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border-b-2 border-transparent'
                ]"
                class="pb-3 px-1 transition-all outline-none focus:outline-none focus-visible:text-purple-600 dark:focus-visible:text-purple-400 cursor-pointer"
              >
                Lesson Content
              </button>

              <button
                id="tab-resources"
                role="tab"
                :aria-selected="activeTab === 'resources'"
                aria-controls="panel-resources"
                tabindex="0"
                @click="setTab('resources')"
                @keydown="handleTabKeydown($event, 'resources')"
                :class="[
                  activeTab === 'resources'
                    ? 'text-purple-600 dark:text-purple-400 font-bold border-b-2 border-purple-600 dark:border-purple-400'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border-b-2 border-transparent'
                ]"
                class="pb-3 px-1 transition-all outline-none focus:outline-none focus-visible:text-purple-600 dark:focus-visible:text-purple-400 cursor-pointer flex items-center gap-1.5"
              >
                <span>Resources</span>
                <span
                  v-if="localResources && localResources.length > 0"
                  class="px-1.5 py-0.5 rounded-full text-[10px] bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 font-bold tabular-nums"
                >
                  {{ localResources.length }}
                </span>
              </button>

              <button
                id="tab-discussion"
                role="tab"
                :aria-selected="activeTab === 'discussion'"
                aria-controls="panel-discussion"
                tabindex="0"
                @click="setTab('discussion')"
                @keydown="handleTabKeydown($event, 'discussion')"
                :class="[
                  activeTab === 'discussion'
                    ? 'text-purple-600 dark:text-purple-400 font-bold border-b-2 border-purple-600 dark:border-purple-400'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border-b-2 border-transparent'
                ]"
                class="pb-3 px-1 transition-all outline-none focus:outline-none focus-visible:text-purple-600 dark:focus-visible:text-purple-400 cursor-pointer flex items-center gap-1.5"
              >
                <span>Discussion</span>
                <span
                  v-if="localComments && localComments.length > 0"
                  class="px-1.5 py-0.5 rounded-full text-[10px] bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 font-bold tabular-nums"
                >
                  {{ localComments.length }}
                </span>
              </button>
            </nav>
          </div>

          <!-- TAB CONTENT: Overview -->
          <div
            v-show="activeTab === 'overview'"
            id="panel-overview"
            role="tabpanel"
            aria-labelledby="tab-overview"
            class="space-y-6"
          >
            <!-- About This Lesson Section (Only show if summary from DB exists) -->
            <div v-if="currentLesson.summary" class="space-y-2">
              <h3 class="text-[18px] font-semibold text-slate-900 dark:text-white capitalize">
                About this lesson
              </h3>
              <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-['Kantumruy_Pro',sans-serif]">
                {{ currentLesson.summary }}
              </p>
            </div>

            <!-- What you will learn Section (Only show if objectives from DB exist) -->
            <div v-if="currentLesson.objectives && currentLesson.objectives.length > 0" class="space-y-2.5">
              <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                What you will learn:
              </h4>
              <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300 font-['Kantumruy_Pro',sans-serif]">
                <li v-for="(obj, idx) in currentLesson.objectives" :key="idx" class="flex items-start gap-2.5">
                  <span class="w-4 h-4 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold mt-0.5 shrink-0">✓</span>
                  <span>{{ obj }}</span>
                </li>
              </ul>
            </div>

            <!-- Empty State for Overview if both are empty -->
            <div
              v-if="!currentLesson.summary && (!currentLesson.objectives || currentLesson.objectives.length === 0)"
              class="p-6 text-center text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800"
            >
              <span class="text-2xl block mb-2">📋</span>
              <p class="font-medium text-slate-700 dark:text-slate-300">No overview details provided for this lesson yet.</p>
              <p class="text-[11px] text-slate-500 mt-0.5">Please check the Lesson Content or Discussion tab.</p>
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
          <div
            v-show="activeTab === 'content'"
            id="panel-content"
            role="tabpanel"
            aria-labelledby="tab-content"
            class="space-y-4"
          >
            <div
              v-if="currentLesson.content && currentLesson.content.trim()"
              class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4"
            >
              <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  <span>📖</span>
                  <span>Lesson Content & Notes</span>
                </h4>
              </div>
              <div
                @click="handleContentClick"
                class="lesson-markdown-body text-xs leading-[1.8] text-slate-700 dark:text-slate-300 font-['Kantumruy_Pro',sans-serif] max-w-[75ch] space-y-3"
                v-html="parsedContent"
              ></div>
            </div>

            <div
              v-else
              class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900/60 border border-dashed border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xs"
            >
              <span class="text-3xl block mb-2">📝</span>
              <p class="font-semibold text-slate-800 dark:text-slate-200">No notes for this lesson yet</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">គ្មានកំណត់ត្រា ឬខ្លឹមសារអត្ថបទសម្រាប់មេរៀននេះនៅឡើយទេ។ You can ask questions in the Discussion tab.</p>
            </div>
          </div>

          <!-- TAB CONTENT: Resources -->
          <div
            v-show="activeTab === 'resources'"
            id="panel-resources"
            role="tabpanel"
            aria-labelledby="tab-resources"
            class="space-y-3"
          >
            <div v-if="localResources && localResources.length > 0" class="space-y-3">
              <div
                v-for="res in localResources"
                :key="res.id"
                class="p-4 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-between gap-3 text-xs shadow-2xs hover:border-purple-300 dark:hover:border-purple-700/50 transition-all"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg shrink-0 border border-purple-100 dark:border-purple-800/40">
                    <span v-if="res.file_extension === 'PDF' || (res.mime && res.mime.includes('pdf'))">📕</span>
                    <span v-else-if="['ZIP','RAR','7Z'].includes(res.file_extension)">📦</span>
                    <span v-else-if="['DOC','DOCX'].includes(res.file_extension)">📘</span>
                    <span v-else-if="['XLS','XLSX','CSV'].includes(res.file_extension)">📊</span>
                    <span v-else-if="['PPT','PPTX'].includes(res.file_extension)">📙</span>
                    <span v-else>📄</span>
                  </div>
                  <div class="min-w-0">
                    <p class="font-bold text-slate-900 dark:text-white truncate">{{ res.title }}</p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                      {{ res.file_extension || 'FILE' }} • {{ res.formatted_size || 'Attached' }}
                    </p>
                  </div>
                </div>
                <a
                  :href="`/student/lessons/${currentLesson.id}/resources/${res.id}`"
                  download
                  class="px-4 py-2 rounded-xl bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-600 hover:text-white dark:hover:bg-purple-600 text-purple-600 dark:text-purple-400 text-xs font-bold border border-purple-200 dark:border-purple-800/40 transition-all cursor-pointer flex items-center gap-1.5 shrink-0"
                >
                  <span>⬇</span>
                  <span>Download</span>
                </a>
              </div>
            </div>

            <div
              v-else
              class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900/60 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xs"
            >
              <span class="text-3xl block mb-2">📁</span>
              <p class="font-semibold text-slate-800 dark:text-slate-200">No downloadable resources for this lesson</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">គ្មានឯកសារទាញយកសម្រាប់មេរៀននេះនៅឡើយទេ។</p>
            </div>
          </div>

          <!-- TAB CONTENT: Discussion -->
          <div
            v-show="activeTab === 'discussion'"
            id="panel-discussion"
            role="tabpanel"
            aria-labelledby="tab-discussion"
            class="space-y-6"
          >
            <!-- Floating New Questions Notification Pill -->
            <transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="transform -translate-y-2 opacity-0"
              enter-to-class="transform translate-y-0 opacity-100"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="transform translate-y-0 opacity-100"
              leave-to-class="transform -translate-y-2 opacity-0"
            >
              <div v-if="newCommentsPillCount > 0" class="flex justify-center sticky top-2 z-20">
                <button
                  @click="fetchComments(1, false)"
                  class="px-4 py-1.5 rounded-full bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xl flex items-center gap-2 cursor-pointer transition-all hover:scale-105"
                >
                  <span class="animate-bounce">⬆</span>
                  <span>{{ newCommentsPillCount }} new comment{{ newCommentsPillCount > 1 ? 's' : '' }} · Click to refresh</span>
                </button>
              </div>
            </transition>

            <!-- Discussion Header Toolbar: Filters & Sorting -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-1">
              <!-- Filter Chips -->
              <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 custom-scrollbar">
                <button
                  type="button"
                  @click="discussionFilter = 'all'"
                  :class="[
                    discussionFilter === 'all'
                      ? 'bg-purple-600 text-white font-bold shadow-xs'
                      : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer shrink-0 flex items-center gap-1.5'
                  ]"
                >
                  <span>All</span>
                  <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/10 font-mono">
                    {{ discussionCounts.all }}
                  </span>
                </button>

                <button
                  type="button"
                  @click="discussionFilter = 'unanswered'"
                  :class="[
                    discussionFilter === 'unanswered'
                      ? 'bg-purple-600 text-white font-bold shadow-xs'
                      : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer shrink-0 flex items-center gap-1.5'
                  ]"
                >
                  <span>Unanswered</span>
                  <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/10 font-mono">
                    {{ discussionCounts.unanswered }}
                  </span>
                </button>

                <button
                  type="button"
                  @click="discussionFilter = 'answered'"
                  :class="[
                    discussionFilter === 'answered'
                      ? 'bg-purple-600 text-white font-bold shadow-xs'
                      : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer shrink-0 flex items-center gap-1.5'
                  ]"
                >
                  <span>Answered</span>
                  <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/10 font-mono">
                    {{ discussionCounts.answered }}
                  </span>
                </button>

                <button
                  type="button"
                  @click="discussionFilter = 'mine'"
                  :class="[
                    discussionFilter === 'mine'
                      ? 'bg-purple-600 text-white font-bold shadow-xs'
                      : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700',
                    'px-3 py-1.5 rounded-xl text-xs transition-all cursor-pointer shrink-0 flex items-center gap-1.5'
                  ]"
                >
                  <span>My Questions</span>
                  <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-black/10 dark:bg-white/10 font-mono">
                    {{ discussionCounts.mine }}
                  </span>
                </button>
              </div>

              <!-- Sort Dropdown -->
              <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Sort by:</span>
                <select
                  v-model="discussionSort"
                  class="text-xs bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-2.5 py-1.5 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-purple-500 font-medium cursor-pointer shadow-2xs"
                >
                  <option value="newest">Newest First</option>
                  <option value="oldest">Oldest First</option>
                  <option value="helpful">Most Helpful / Liked</option>
                </select>
              </div>
            </div>

            <!-- Question Composer Card -->
            <div class="p-4 sm:p-5 bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm space-y-3 transition-all">
              <div class="flex items-center justify-between">
                <label for="new-comment-input" class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                  <span class="text-base">💬</span>
                  <span>Ask a Question or Join Discussion</span>
                </label>
                <span class="text-[10px] text-slate-400 tabular-nums">
                  {{ newCommentText.length }}/1000
                </span>
              </div>

              <div class="space-y-2">
                <textarea
                  id="new-comment-input"
                  ref="commentTextareaRef"
                  v-model="newCommentText"
                  maxlength="1000"
                  rows="3"
                  placeholder="Ask a question about this lesson... (Tip: type e.g. 07:40 to link directly to a video moment)"
                  class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 shadow-2xs font-['Kantumruy_Pro',sans-serif] resize-y min-h-[75px]"
                  @keydown.ctrl.enter="postComment(null)"
                  @keydown.meta.enter="postComment(null)"
                ></textarea>

                <!-- Composer Action Bar -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                  <!-- Tag Timestamp Button -->
                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      @click="insertCurrentVideoTime"
                      title="Insert current video timestamp"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-300 font-mono text-[11px] font-bold border border-purple-200 dark:border-purple-800/40 transition-colors cursor-pointer"
                    >
                      <span>⏱️ + Current Time</span>
                      <span class="text-[10px] opacity-80">({{ formatSeconds(currentSeconds) }})</span>
                    </button>
                    <span class="hidden md:inline text-[10px] text-slate-400">
                      💡 Click timestamp to jump video directly
                    </span>
                  </div>

                  <!-- Post Button & Keyboard hint -->
                  <div class="flex items-center gap-2">
                    <span class="hidden sm:inline text-[10px] text-slate-400">Ctrl + Enter to send</span>
                    <button
                      @click="postComment(null)"
                      :disabled="isPostingComment || newCommentText.trim().length < 2"
                      class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-xs transition-all shadow-md shadow-purple-600/20 cursor-pointer flex items-center gap-2"
                    >
                      <svg v-if="isPostingComment" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                      </svg>
                      <span v-if="isPostingComment">Posting...</span>
                      <span v-else>Post Question</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Shimmer Loading Skeletons -->
            <div v-if="isFetchingComments" class="space-y-4">
              <div v-for="i in 3" :key="i" class="p-4 bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 rounded-2xl animate-pulse space-y-3">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                  <div class="space-y-1.5 flex-1">
                    <div class="h-3.5 w-32 bg-slate-200 dark:bg-slate-800 rounded"></div>
                    <div class="h-2.5 w-20 bg-slate-100 dark:bg-slate-800/60 rounded"></div>
                  </div>
                </div>
                <div class="h-3 w-3/4 bg-slate-100 dark:bg-slate-800/60 rounded ml-12"></div>
                <div class="h-3 w-1/2 bg-slate-100 dark:bg-slate-800/60 rounded ml-12"></div>
              </div>
            </div>

            <!-- Comment List -->
            <div v-else-if="localComments && localComments.length > 0" class="space-y-4">
              <div
                v-for="comm in localComments"
                :key="comm.id"
                :id="`comment-${comm.id}`"
                :class="[
                  highlightedCommentId === comm.id
                    ? 'ring-2 ring-purple-500 shadow-lg shadow-purple-500/20'
                    : 'border-slate-200 dark:border-slate-800',
                  'p-4 sm:p-5 bg-white dark:bg-slate-900/70 border rounded-2xl shadow-2xs space-y-3 transition-all duration-300'
                ]"
              >
                <!-- Question Header -->
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-center gap-3 min-w-0">
                    <img
                      :src="comm.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(comm.user?.name || 'User')}&background=8b5cf6&color=fff`"
                      :alt="comm.user?.name"
                      class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-700 shrink-0"
                    />
                    <div class="min-w-0">
                      <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-bold text-slate-900 dark:text-white truncate">
                          {{ comm.user?.name }}
                        </span>
                        <span v-if="comm.user_id === currentUserId" class="text-[10px] text-slate-400 font-medium">(You)</span>

                        <!-- Role Badge -->
                        <span
                          v-if="comm.role_label === 'instructor' || comm.user_id === (courseTeacherId || course?.teacher_id)"
                          class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-600 text-white shadow-2xs flex items-center gap-1 shrink-0"
                        >
                          <span>✓ Instructor</span>
                        </span>
                        <span
                          v-else-if="comm.role_label === 'admin' || comm.user?.role === 'admin'"
                          class="px-2 py-0.5 rounded-full text-[9px] font-medium bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 shrink-0"
                        >
                          Admin
                        </span>

                        <!-- Answered Question Badge -->
                        <span
                          v-if="comm.is_answered"
                          class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1 shrink-0"
                          title="This question has been answered"
                        >
                          <span>✓ Answered</span>
                        </span>
                      </div>

                      <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-[10px] text-slate-400 tabular-nums" :title="comm.created_at">
                          {{ comm.relative_time || 'Recent' }}
                        </span>
                        <span v-if="comm.edited_at" class="text-[9px] text-slate-400 italic">
                          (edited)
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- 3-Dots Action Dropdown Menu -->
                  <div class="relative shrink-0" @click.stop>
                    <button
                      type="button"
                      @click="toggleCommentMenu(comm.id)"
                      aria-label="Comment options"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                    >
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                      </svg>
                    </button>

                    <!-- Dropdown Content -->
                    <div
                      v-if="activeMenuCommentId === comm.id"
                      class="absolute right-0 mt-1 w-36 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 text-xs"
                    >
                      <button
                        v-if="canEditComment(comm)"
                        type="button"
                        @click="startEditComment(comm)"
                        class="w-full px-3 py-1.5 text-left text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 cursor-pointer"
                      >
                        <span>✏️</span>
                        <span>Edit (15m)</span>
                      </button>

                      <button
                        v-if="comm.user_id === currentUserId || (courseTeacherId && currentUserId === courseTeacherId) || comm.user?.role === 'admin'"
                        type="button"
                        @click="deleteComment(comm.id)"
                        class="w-full px-3 py-1.5 text-left text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 flex items-center gap-2 cursor-pointer"
                      >
                        <span>🗑️</span>
                        <span>Delete</span>
                      </button>

                      <button
                        type="button"
                        @click="openReportModal(comm)"
                        class="w-full px-3 py-1.5 text-left text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/30 flex items-center gap-2 cursor-pointer"
                      >
                        <span>🚩</span>
                        <span>Report</span>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Video Timestamp Pill (If tagged explicitly) -->
                <div v-if="comm.video_seconds !== null && comm.video_seconds !== undefined" class="pl-12">
                  <button
                    type="button"
                    @click="seekToTimestamp(comm.video_seconds)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-300 font-mono text-[11px] font-bold border border-purple-200 dark:border-purple-800/40 transition-colors cursor-pointer"
                  >
                    <span>▶️ Jump to timestamp:</span>
                    <span>{{ comm.video_timestamp_formatted || formatSeconds(comm.video_seconds) }}</span>
                  </button>
                </div>

                <!-- Comment Body / In-place Edit Form -->
                <div class="pl-12">
                  <!-- In-place Edit Form -->
                  <div v-if="editingCommentId === comm.id" class="space-y-2">
                    <textarea
                      v-model="editCommentText"
                      rows="3"
                      class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-purple-300 dark:border-purple-700 text-xs text-slate-900 dark:text-white focus:outline-none font-['Kantumruy_Pro',sans-serif]"
                    ></textarea>
                    <div class="flex items-center gap-2 justify-end">
                      <button
                        type="button"
                        @click="cancelEditComment"
                        class="px-3 py-1 rounded-lg text-xs text-slate-500 hover:text-slate-800 dark:hover:text-white cursor-pointer"
                      >
                        Cancel
                      </button>
                      <button
                        type="button"
                        @click="saveEditComment(comm)"
                        :disabled="isSavingEdit || editCommentText.trim().length < 2"
                        class="px-4 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs disabled:opacity-40 cursor-pointer shadow-xs"
                      >
                        <span v-if="isSavingEdit">Saving...</span>
                        <span v-else>Save Changes</span>
                      </button>
                    </div>
                  </div>

                  <!-- Normal Body Text with Safe Linkified URLs & Timestamps -->
                  <div
                    v-else
                    class="text-xs text-slate-700 dark:text-slate-200 leading-relaxed font-['Kantumruy_Pro',sans-serif] break-words whitespace-pre-line"
                    @click="handleCommentBodyClick"
                    v-html="renderCommentBodyHtml(comm.body)"
                  ></div>
                </div>

                <!-- Action Row (Like, Reply, Replies count) -->
                <div class="pl-12 flex flex-wrap items-center gap-4 text-xs pt-1">
                  <!-- Like Button (Optimistic) -->
                  <button
                    type="button"
                    @click="toggleLike(comm)"
                    :class="[
                      comm.liked_by_me
                        ? 'text-purple-600 dark:text-purple-400 font-bold'
                        : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white font-medium',
                      'flex items-center gap-1.5 transition-all cursor-pointer hover:scale-105 active:scale-95'
                    ]"
                  >
                    <svg
                      class="w-4 h-4 transition-transform duration-200"
                      :class="comm.liked_by_me ? 'fill-current scale-110 text-purple-600 dark:text-purple-400' : 'fill-none'"
                      stroke="currentColor"
                      stroke-width="1.8"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                    </svg>
                    <span class="tabular-nums">{{ comm.likes_count || 0 }}</span>
                  </button>

                  <!-- Reply Toggle Button -->
                  <button
                    type="button"
                    @click="replyingToId = replyingToId === comm.id ? null : comm.id"
                    class="text-slate-600 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 font-semibold transition-colors cursor-pointer flex items-center gap-1"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    <span>{{ replyingToId === comm.id ? 'Cancel Reply' : 'Reply' }}</span>
                  </button>

                  <!-- Expand/Collapse Replies Button (if has replies) -->
                  <button
                    v-if="comm.replies && comm.replies.length > 0"
                    type="button"
                    @click="toggleReplies(comm.id)"
                    class="text-purple-600 dark:text-purple-400 font-bold hover:underline cursor-pointer flex items-center gap-1 ml-auto text-[11px]"
                  >
                    <span>{{ expandedReplies[comm.id] ? 'Hide' : 'View' }} {{ comm.replies.length }} repl{{ comm.replies.length > 1 ? 'ies' : 'y' }}</span>
                    <span>{{ expandedReplies[comm.id] ? '▲' : '▼' }}</span>
                  </button>
                </div>

                <!-- Inline Reply Form -->
                <div v-if="replyingToId === comm.id" class="pl-12 pt-2">
                  <div class="flex items-start gap-2 bg-slate-50 dark:bg-slate-950 p-2.5 rounded-xl border border-purple-200 dark:border-purple-800/40">
                    <textarea
                      v-model="replyText"
                      rows="2"
                      placeholder="Write a clear reply or solution..."
                      class="flex-1 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:border-purple-500 font-['Kantumruy_Pro',sans-serif] resize-none"
                      @keydown.ctrl.enter="postComment(comm.id)"
                      @keydown.meta.enter="postComment(comm.id)"
                    ></textarea>
                    <button
                      type="button"
                      @click="postComment(comm.id)"
                      :disabled="isPostingComment || replyText.trim().length < 2"
                      class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs disabled:opacity-40 cursor-pointer shadow-xs shrink-0 self-end"
                    >
                      Reply
                    </button>
                  </div>
                </div>

                <!-- 1-Level Replies Thread (Auto-expanded when reply is posted or toggled) -->
                <div
                  v-if="comm.replies && comm.replies.length > 0 && (expandedReplies[comm.id] ?? true)"
                  class="pl-12 pt-2 space-y-2.5 border-t border-slate-100 dark:border-slate-800/60 mt-3"
                >
                  <div
                    v-for="rep in comm.replies"
                    :key="rep.id"
                    :id="`comment-${rep.id}`"
                    :class="[
                      highlightedCommentId === rep.id ? 'ring-2 ring-purple-500' : '',
                      (rep.role_label === 'instructor' || rep.user_id === (courseTeacherId || course?.teacher_id))
                        ? 'border-l-4 border-purple-600 bg-purple-50/60 dark:bg-purple-950/20 rounded-xl p-3.5 space-y-2'
                        : 'bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 rounded-xl p-3 space-y-2'
                    ]"
                  >
                    <!-- Reply Header -->
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-2 min-w-0">
                        <img
                          :src="rep.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(rep.user?.name || 'User')}&background=8b5cf6&color=fff`"
                          :alt="rep.user?.name"
                          class="w-6 h-6 rounded-full object-cover shrink-0"
                        />
                        <span class="font-bold text-slate-800 dark:text-slate-200 text-xs truncate">
                          {{ rep.user?.name }}
                        </span>

                        <!-- Instructor Badge on Reply -->
                        <span
                          v-if="rep.role_label === 'instructor' || rep.user_id === (courseTeacherId || course?.teacher_id)"
                          class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-purple-600 text-white flex items-center gap-1 shrink-0"
                        >
                          <span>✓ Instructor Answer</span>
                        </span>

                        <!-- Accepted Answer Badge -->
                        <span
                          v-if="comm.answered_comment_id === rep.id"
                          class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-emerald-600 text-white flex items-center gap-1 shrink-0"
                        >
                          <span>✓ Accepted Solution</span>
                        </span>

                        <span class="text-[9px] text-slate-400 tabular-nums">
                          {{ rep.relative_time || 'Recent' }}
                        </span>
                      </div>

                      <!-- Reply Action Options Menu -->
                      <div class="flex items-center gap-2">
                        <!-- Mark as Answer button (For Question Owner or Instructor) -->
                        <button
                          v-if="comm.user_id === currentUserId || currentUserId === (courseTeacherId || course?.teacher_id)"
                          type="button"
                          @click="toggleAnswer(comm, rep)"
                          :class="[
                            comm.answered_comment_id === rep.id
                              ? 'text-emerald-600 dark:text-emerald-400 font-bold bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300'
                              : 'text-slate-500 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-slate-700/50',
                            'px-2 py-0.5 rounded text-[10px] font-semibold transition-colors cursor-pointer flex items-center gap-1'
                          ]"
                          :title="comm.answered_comment_id === rep.id ? 'Unmark as accepted answer' : 'Mark as accepted answer'"
                        >
                          <span>✓</span>
                          <span>{{ comm.answered_comment_id === rep.id ? 'Accepted' : 'Mark as Answer' }}</span>
                        </button>

                        <button
                          v-if="rep.user_id === currentUserId || (courseTeacherId && currentUserId === courseTeacherId)"
                          type="button"
                          @click="deleteComment(rep.id, comm.id)"
                          title="Delete reply"
                          class="text-slate-400 hover:text-red-500 p-1 rounded transition-colors cursor-pointer text-[11px]"
                        >
                          🗑️
                        </button>
                      </div>
                    </div>

                    <!-- Reply Body -->
                    <div
                      class="text-xs text-slate-700 dark:text-slate-300 font-['Kantumruy_Pro',sans-serif] pl-8 break-words whitespace-pre-line"
                      @click="handleCommentBodyClick"
                      v-html="renderCommentBodyHtml(rep.body)"
                    ></div>

                    <!-- Reply Action row (Like) -->
                    <div class="pl-8 flex items-center gap-3 pt-0.5">
                      <button
                        type="button"
                        @click="toggleLike(rep)"
                        :class="[
                          rep.liked_by_me ? 'text-purple-600 font-bold' : 'text-slate-400 hover:text-slate-600',
                          'text-[10px] flex items-center gap-1 cursor-pointer'
                        ]"
                      >
                        <span>👍</span>
                        <span class="tabular-nums">{{ rep.likes_count || 0 }}</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Load More Questions Button -->
              <div v-if="hasMoreComments" class="pt-2 flex justify-center">
                <button
                  type="button"
                  @click="fetchComments(currentCommentsPage + 1, true)"
                  :disabled="isLoadingMoreComments"
                  class="px-5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition-all flex items-center gap-2 cursor-pointer"
                >
                  <svg v-if="isLoadingMoreComments" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                  </svg>
                  <span>{{ isLoadingMoreComments ? 'Loading more...' : 'Load More Questions' }}</span>
                </button>
              </div>
            </div>

            <!-- Empty State for Discussion -->
            <div
              v-else
              class="p-10 text-center text-xs text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900/60 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xs space-y-3"
            >
              <span class="text-4xl block">💬</span>
              <div>
                <p class="font-bold text-slate-800 dark:text-slate-200 text-sm">No questions asked yet</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                  Have a question about this lecture? Ask below and the instructor or peers will help you!
                </p>
              </div>
              <button
                type="button"
                @click="focusComposer"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs transition-all shadow-md shadow-purple-600/20 cursor-pointer"
              >
                Ask the First Question
              </button>
            </div>

            <!-- Report Comment Modal -->
            <div
              v-if="reportModalOpen"
              class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in"
              @click.self="reportModalOpen = false"
            >
              <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🚩</span>
                    <span>Report Comment</span>
                  </h3>
                  <button
                    type="button"
                    @click="reportModalOpen = false"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer"
                  >
                    ✕
                  </button>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-400">
                  Please let us know why you are reporting this comment. Our moderation team will review it.
                </p>

                <div class="space-y-2">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Reason</label>
                  <select
                    v-model="reportReason"
                    class="w-full text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-slate-900 dark:text-white focus:outline-none focus:border-purple-500"
                  >
                    <option value="">Select a reason...</option>
                    <option value="Inappropriate or offensive language">Inappropriate or offensive language</option>
                    <option value="Spam or promotional content">Spam or promotional content</option>
                    <option value="Misinformation or incorrect code">Misinformation or incorrect code</option>
                    <option value="Harassment or abusive behavior">Harassment or abusive behavior</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                  <button
                    type="button"
                    @click="reportModalOpen = false"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                  >
                    Cancel
                  </button>
                  <button
                    type="button"
                    @click="submitReport"
                    :disabled="isSubmittingReport || !reportReason"
                    class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 disabled:opacity-40 text-white font-bold text-xs transition-all cursor-pointer shadow-xs"
                  >
                    <span v-if="isSubmittingReport">Submitting...</span>
                    <span v-else>Submit Report</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Action Buttons: Previous Lesson & Smart Completion Action -->
          <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <button
              @click="handlePreviousNav"
              :disabled="!previousLessonItem"
              aria-label="Previous lesson"
              type="button"
              :class="[
                !previousLessonItem ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-200 dark:hover:bg-slate-700 cursor-pointer',
                'w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-200 dark:border-slate-700 flex items-center justify-center gap-2 transition-colors shadow-2xs'
              ]"
            >
              <span>←</span>
              <span>Previous Lesson</span>
            </button>

            <!-- Smart Complete & Next Action Button -->
            <div class="flex flex-col items-end gap-1 w-full sm:w-auto">
              <button
                @click="handleSmartAction"
                :disabled="isCompleting || (!isLessonCompletedLocally && !isEligibleToComplete)"
                aria-label="Smart Complete and Next"
                type="button"
                :class="[
                  isCompleting || (!isLessonCompletedLocally && !isEligibleToComplete)
                    ? 'opacity-50 cursor-not-allowed bg-purple-600/70'
                    : 'bg-purple-600 hover:bg-purple-500 hover:scale-105 active:scale-95 cursor-pointer shadow-lg shadow-purple-600/20',
                  'w-full sm:w-auto px-5 py-2.5 rounded-xl text-white font-bold text-xs flex items-center justify-center gap-2 transition-all'
                ]"
              >
                <span v-if="isCompleting">Saving...</span>
                <span v-else>{{ smartActionLabel }}</span>
              </button>
              <span
                v-if="completionNotice"
                class="text-[10px] text-amber-500 font-medium"
              >
                {{ completionNotice }}
              </span>
            </div>
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
                {{ localChapters.length > 0 && localChapters.every(c => c.expanded) ? 'Collapse All' : 'Expand All' }}
              </button>
            </div>

            <!-- Chapter Items List (Hides empty modules with 0 lessons) -->
            <div class="space-y-2">
              <div
                v-for="chap in localChapters.filter(c => (c.lessons && c.lessons.length > 0) || (c.total_count && c.total_count > 0))"
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
                    :id="'sidebar-lesson-' + lsn.id"
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
                    <div class="flex items-center gap-2.5 min-w-0 pr-2">
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

                      <span class="line-clamp-2 text-left leading-snug">{{ lsn.title }}</span>
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
          @click="handleSmartAction"
          :disabled="isCompleting || (!isLessonCompletedLocally && !isEligibleToComplete)"
          type="button"
          class="flex-1 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold text-center shadow-md shadow-purple-600/30 disabled:opacity-50"
        >
          <span v-if="isCompleting">Saving...</span>
          <span v-else>{{ smartActionLabel }}</span>
        </button>
      </div>

    </div>
  </StudentLayout>
</template>
