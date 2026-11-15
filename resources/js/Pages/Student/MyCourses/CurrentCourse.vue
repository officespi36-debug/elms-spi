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
  completionAction?: {
    type: string
    label: string
    can_complete: boolean
    reason?: string
    next_url?: string
  }
  currentUserId?: number
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

// ─── Reactive Resources & Discussion Comments ───
const localResources = ref<any[]>(props.resources || [])
watch(() => props.resources, (newRes) => {
  localResources.value = newRes || []
})

const localComments = ref<any[]>(props.comments || [])
watch(() => props.comments, (newComms) => {
  localComments.value = newComms || []
})

const newCommentText = ref('')
const replyText = ref('')
const replyingToId = ref<number | null>(null)
const isPostingComment = ref(false)
const deletingCommentId = ref<number | null>(null)

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

// ─── Sanitized Lesson Content ───
const sanitizedContent = computed(() => {
  const raw = props.currentLesson?.content || ''
  return raw
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
    .replace(/on\w+="[^"]*"/gi, '')
    .replace(/javascript:[^"']*/gi, '')
})

// ─── Discussion: Post Comment / Reply (Optimistic UI) ───
const postComment = async (parentId: number | null = null) => {
  const text = (parentId ? replyText.value : newCommentText.value).trim()
  if (!text || isPostingComment.value || !props.currentLesson?.id) return

  const tempId = Date.now()
  const optimisticComment: any = {
    id: tempId,
    lesson_id: props.currentLesson.id,
    user_id: props.currentUserId || 0,
    parent_id: parentId,
    body: text,
    created_at: new Date().toISOString(),
    relative_time: 'Just now',
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
    }
    replyText.value = ''
    replyingToId.value = null
  } else {
    localComments.value.unshift(optimisticComment)
    newCommentText.value = ''
  }

  try {
    isPostingComment.value = true
    const res = await fetch(`/student/lessons/${props.currentLesson.id}/comments`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ body: text, parent_id: parentId }),
    })

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}))
      throw new Error(errData.message || errData.error || `HTTP ${res.status}`)
    }

    const data = await res.json()
    // Replace optimistic item with server item
    if (parentId) {
      const parent = localComments.value.find(c => c.id === parentId)
      if (parent && parent.replies) {
        const idx = parent.replies.findIndex(r => r.id === tempId)
        if (idx !== -1) parent.replies[idx] = data.comment
      }
    } else {
      const idx = localComments.value.findIndex(c => c.id === tempId)
      if (idx !== -1) localComments.value[idx] = data.comment
    }
  } catch (err: any) {
    // Revert optimistic insert
    if (parentId) {
      const parent = localComments.value.find(c => c.id === parentId)
      if (parent && parent.replies) {
        parent.replies = parent.replies.filter(r => r.id !== tempId)
      }
    } else {
      localComments.value = localComments.value.filter(c => c.id !== tempId)
    }
    toastType.value = 'warning'
    toastMessage.value = err.message || 'Failed to post comment'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 4000)
  } finally {
    isPostingComment.value = false
  }
}

// ─── Discussion: Delete Comment ───
const deleteComment = async (commentId: number, parentId: number | null = null) => {
  if (!confirm('Are you sure you want to delete this comment?')) return

  try {
    deletingCommentId.value = commentId
    const res = await fetch(`/student/lessons/${props.currentLesson.id}/comments/${commentId}`, {
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
      localComments.value = localComments.value.filter(c => c.id !== commentId)
    }
  } catch (err) {
    toastType.value = 'error'
    toastMessage.value = 'Failed to delete comment'
    showToast.value = true
    setTimeout(() => { showToast.value = false }, 4000)
  } finally {
    deletingCommentId.value = null
  }
}

// ─── Smart Completion Action Handler ───
const handleSmartAction = () => {
  if (props.completionAction?.type === 'next') {
    handleNextNav()
  } else if (props.completionAction?.type === 'finish') {
    router.visit(props.completionAction.next_url || `/student/courses/${props.course.id}/overview`)
  } else {
    handleCompleteAndNext()
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
    window.addEventListener('keydown', handleKeydown)
    window.addEventListener('beforeunload', handleBeforeUnload)
    window.addEventListener('online', handleOnline)
  }
  startPlaybackSimulation()
})

onUnmounted(() => {
  if (progressTimer) clearInterval(progressTimer)
  if (codeAnimationTimer) clearInterval(codeAnimationTimer)
  if (retryTimer) clearTimeout(retryTimer)
  saveVideoProgress(currentSeconds.value, true)
  if (typeof window !== 'undefined') {
    window.removeEventListener('keydown', handleKeydown)
    window.removeEventListener('beforeunload', handleBeforeUnload)
    window.removeEventListener('online', handleOnline)
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
                v-if="isPreviewMode && !isLockedPlay && (videoProvider !== 'none' || currentLesson.preview_url)"
                class="absolute top-4 left-4 z-30 flex items-center gap-2 px-3 py-1.5 rounded-full bg-black/85 backdrop-blur-md border border-purple-500/50 text-white text-xs font-semibold shadow-xl pointer-events-none"
              >
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="text-purple-300 font-bold uppercase tracking-wider text-[10px]">{{ videoProvider === 'youtube' ? 'YouTube Preview' : 'Preview' }}</span>
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
              class="flex items-center gap-6 text-xs font-semibold"
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
                    ? 'text-purple-600 dark:text-purple-400 font-bold border-b-2 border-purple-500'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border-b-2 border-transparent'
                ]"
                class="pb-3 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 rounded-t-sm cursor-pointer"
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
                    ? 'text-purple-600 dark:text-purple-400 font-bold border-b-2 border-purple-500'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border-b-2 border-transparent'
                ]"
                class="pb-3 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 rounded-t-sm cursor-pointer"
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
                    ? 'text-purple-600 dark:text-purple-400 font-bold border-b-2 border-purple-500'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border-b-2 border-transparent'
                ]"
                class="pb-3 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 rounded-t-sm cursor-pointer flex items-center gap-1.5"
              >
                <span>Resources</span>
                <span
                  v-if="localResources && localResources.length > 0"
                  class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 font-bold"
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
                    ? 'text-purple-600 dark:text-purple-400 font-bold border-b-2 border-purple-500'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border-b-2 border-transparent'
                ]"
                class="pb-3 transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 rounded-t-sm cursor-pointer flex items-center gap-1.5"
              >
                <span>Discussion</span>
                <span
                  v-if="localComments && localComments.length > 0"
                  class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 font-bold"
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
              v-if="currentLesson.content"
              class="space-y-4 text-xs text-slate-700 dark:text-slate-300 leading-relaxed bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 p-5 rounded-2xl shadow-2xs font-['Kantumruy_Pro',sans-serif]"
            >
              <h4 class="text-[18px] font-semibold text-slate-900 dark:text-white capitalize">
                Lesson content & notes
              </h4>
              <div
                class="prose dark:prose-invert max-w-none text-xs leading-relaxed whitespace-pre-line"
                v-html="sanitizedContent"
              ></div>
            </div>

            <div
              v-else
              class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900/60 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xs"
            >
              <span class="text-3xl block mb-2">📝</span>
              <p class="font-semibold text-slate-800 dark:text-slate-200">No notes for this lesson yet</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">គ្មានកំណត់ចំណាំសម្រាប់មេរៀននេះនៅឡើយទេ។ You can ask questions in the Discussion tab.</p>
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
            class="space-y-5"
          >
            <!-- Add Comment Input Box -->
            <div class="p-4 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xs space-y-3">
              <label for="new-comment-input" class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                <span>💬</span>
                <span>Ask a Question or Share Feedback</span>
              </label>
              <div class="flex items-start gap-3">
                <textarea
                  id="new-comment-input"
                  v-model="newCommentText"
                  rows="2"
                  placeholder="Ask a question about this lesson..."
                  class="flex-1 px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 shadow-2xs font-['Kantumruy_Pro',sans-serif] resize-none"
                  @keydown.ctrl.enter="postComment(null)"
                ></textarea>
                <button
                  @click="postComment(null)"
                  :disabled="isPostingComment || !newCommentText.trim()"
                  class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-xs transition-all shadow-md shadow-purple-600/20 cursor-pointer shrink-0 self-end"
                >
                  <span v-if="isPostingComment">Posting...</span>
                  <span v-else>Post</span>
                </button>
              </div>
            </div>

            <!-- Comment List -->
            <div v-if="localComments && localComments.length > 0" class="space-y-4">
              <div
                v-for="comm in localComments"
                :key="comm.id"
                class="p-4 bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xs space-y-3"
              >
                <!-- Comment Header -->
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <img
                      :src="comm.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(comm.user?.name || 'User')}&background=8b5cf6&color=fff`"
                      :alt="comm.user?.name"
                      class="w-8 h-8 rounded-full object-cover border border-slate-200 dark:border-slate-700"
                    />
                    <div>
                      <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-900 dark:text-white">{{ comm.user?.name }}</span>
                        <span
                          v-if="comm.user?.role === 'teacher' || comm.user?.role === 'admin'"
                          class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-300"
                        >
                          Instructor
                        </span>
                      </div>
                      <span class="text-[10px] text-slate-400 tabular-nums">{{ comm.relative_time || 'Recent' }}</span>
                    </div>
                  </div>

                  <!-- Delete comment button for owner -->
                  <button
                    v-if="comm.user_id === currentUserId"
                    @click="deleteComment(comm.id)"
                    :disabled="deletingCommentId === comm.id"
                    aria-label="Delete comment"
                    class="text-[11px] text-red-500 hover:text-red-700 dark:hover:text-red-400 p-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer"
                  >
                    🗑️
                  </button>
                </div>

                <!-- Comment Body -->
                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed font-['Kantumruy_Pro',sans-serif] pl-11">
                  {{ comm.body }}
                </p>

                <!-- Action row (Reply toggle) -->
                <div class="pl-11 flex items-center gap-4 text-[11px]">
                  <button
                    @click="replyingToId = replyingToId === comm.id ? null : comm.id"
                    class="text-purple-600 dark:text-purple-400 font-semibold hover:underline cursor-pointer"
                  >
                    {{ replyingToId === comm.id ? 'Cancel Reply' : 'Reply' }}
                  </button>
                </div>

                <!-- Reply Form -->
                <div v-if="replyingToId === comm.id" class="pl-11 pt-2 flex items-center gap-2">
                  <input
                    type="text"
                    v-model="replyText"
                    placeholder="Write a reply..."
                    class="flex-1 px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:border-purple-500 font-['Kantumruy_Pro',sans-serif]"
                    @keydown.enter="postComment(comm.id)"
                  />
                  <button
                    @click="postComment(comm.id)"
                    :disabled="isPostingComment || !replyText.trim()"
                    class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs disabled:opacity-40 cursor-pointer"
                  >
                    Reply
                  </button>
                </div>

                <!-- 1-Level Replies -->
                <div v-if="comm.replies && comm.replies.length > 0" class="pl-11 pt-2 space-y-2 border-t border-slate-100 dark:border-slate-800/60 mt-2">
                  <div
                    v-for="rep in comm.replies"
                    :key="rep.id"
                    class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 text-xs space-y-1.5"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-2">
                        <img
                          :src="rep.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(rep.user?.name || 'User')}&background=8b5cf6&color=fff`"
                          :alt="rep.user?.name"
                          class="w-5 h-5 rounded-full object-cover"
                        />
                        <span class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">{{ rep.user?.name }}</span>
                        <span class="text-[9px] text-slate-400">{{ rep.relative_time || 'Recent' }}</span>
                      </div>
                      <button
                        v-if="rep.user_id === currentUserId"
                        @click="deleteComment(rep.id, comm.id)"
                        class="text-[10px] text-red-500 hover:text-red-700 cursor-pointer"
                      >
                        🗑️
                      </button>
                    </div>
                    <p class="text-slate-600 dark:text-slate-300 font-['Kantumruy_Pro',sans-serif] pl-7">
                      {{ rep.body }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State for Discussion -->
            <div
              v-else
              class="p-8 text-center text-xs text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900/60 border border-dashed border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xs"
            >
              <span class="text-3xl block mb-2">💬</span>
              <p class="font-semibold text-slate-800 dark:text-slate-200">No questions yet. Be the first to ask!</p>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">មិនទាន់មានសំណួរនៅឡើយទេ។ សូមក្លាយជាអ្នកដំបូងដែលសួរសំណួរ!</p>
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
                :disabled="isCompleting || (completionAction && !completionAction.can_complete)"
                aria-label="Smart Complete and Next"
                type="button"
                :class="[
                  isCompleting || (completionAction && !completionAction.can_complete)
                    ? 'opacity-50 cursor-not-allowed bg-purple-600/70'
                    : 'bg-purple-600 hover:bg-purple-500 hover:scale-105 active:scale-95 cursor-pointer shadow-lg shadow-purple-600/20',
                  'w-full sm:w-auto px-5 py-2.5 rounded-xl text-white font-bold text-xs flex items-center justify-center gap-2 transition-all'
                ]"
              >
                <span v-if="isCompleting">Saving...</span>
                <span v-else>{{ completionAction?.label || (isLessonCompletedLocally ? 'Next Lesson →' : 'Complete & Next Lesson →') }}</span>
              </button>
              <span
                v-if="completionAction && !completionAction.can_complete && completionAction.reason"
                class="text-[10px] text-amber-500 font-medium"
              >
                {{ completionAction.reason }}
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
          @click="handleSmartAction"
          :disabled="isCompleting || (completionAction && !completionAction.can_complete)"
          type="button"
          class="flex-1 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold text-center shadow-md shadow-purple-600/30 disabled:opacity-50"
        >
          <span v-if="isCompleting">Saving...</span>
          <span v-else>{{ completionAction?.label || (isLessonCompletedLocally ? 'Next Lesson →' : 'Complete & Next Lesson →') }}</span>
        </button>
      </div>

    </div>
  </StudentLayout>
</template>
