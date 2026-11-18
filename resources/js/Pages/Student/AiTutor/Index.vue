<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Sparkles,
  Send,
  Plus,
  Trash2,
  Copy,
  Check,
  ThumbsUp,
  ThumbsDown,
  BookOpen,
  RotateCcw,
  PanelRightOpen,
  X,
  AlertCircle,
  FileText,
  HelpCircle,
  ListChecks,
  Code2,
  Compass,
} from 'lucide-vue-next'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { i18n } from '@/Services/i18n'

interface CourseLessonOption {
  id: number
  title: string
  title_kh: string
  module_title: string | null
  chapter_title: string | null
}

interface EnrolledCourseItem {
  id: number
  code: string
  title: string
  title_kh: string
  module_title: string | null
  chapter_title?: string | null
  lesson_id: number | null
  lesson_title: string | null
  lesson_title_kh: string | null
  lesson_excerpt?: string
  lessons?: CourseLessonOption[]
  progress_percent: number
  lessons_done: number
  lessons_total: number
}

interface RecentActivityItem {
  type: 'lesson' | 'quiz' | 'study'
  title_km: string
  title_en: string
  time: string
}

interface StudentContextPayload {
  has_course: boolean
  active_course: EnrolledCourseItem | null
  enrolled_courses: EnrolledCourseItem[]
  recent_activity: RecentActivityItem[]
}

interface AiMessageItem {
  id: number
  role: 'user' | 'assistant'
  body: string
  html: string | null
  provider?: string | null
  model?: string | null
  my_rating: 'up' | 'down' | null
  created_at_iso: string | null
  time_label: string
}

interface ConversationItem {
  id: number
  course_id: number | null
  course_title: string | null
  course_title_kh: string | null
  title: string
  last_message_at: string | null
  relative_time: string
  messages?: AiMessageItem[]
}

interface QuotaStatus {
  limit: number
  used: number
  remaining: number
}

interface HtmlSegment {
  type: 'html' | 'code'
  content: string
  language?: string
}

const props = defineProps<{
  context: StudentContextPayload
  conversations: ConversationItem[]
  activeConversation: ConversationItem | null
  quota: QuotaStatus
  csrfToken: string
}>()

const page = usePage<any>()
const isKm = computed(() => i18n.locale.value === 'km')
const user = computed(() => page.props.auth?.user || {})

const studentName = computed(() => {
  if (isKm.value) {
    return user.value?.name_kh || user.value?.name || 'និស្សិត'
  }
  return user.value?.name || user.value?.name_kh || 'Student'
})

const studentInitials = computed(() => {
  const n = String(user.value?.name || user.value?.name_kh || 'S').trim()
  return n.slice(0, 2).toUpperCase()
})

const userAvatarUrl = computed(() => {
  const av = user.value?.avatar_url || user.value?.avatar
  if (av && typeof av === 'string' && av.trim() !== '' && !av.includes('ui-avatars.com')) {
    return av
  }
  return null
})

// Reactive state strictly hydrated from database props
const conversations = ref<ConversationItem[]>([...(props.conversations || [])])
const activeConversation = ref<ConversationItem | null>(
  props.activeConversation
    ? { ...props.activeConversation, messages: [...(props.activeConversation.messages || [])] }
    : null
)
const activeMessages = computed<AiMessageItem[]>(() => activeConversation.value?.messages || [])

const selectedCourse = ref<EnrolledCourseItem | null>(props.context?.active_course || null)
const selectedLessonId = ref<number | null>(
  props.context?.active_course?.lesson_id ?? props.context?.active_course?.lessons?.[0]?.id ?? null
)
const quotaState = ref<QuotaStatus>({ ...(props.quota || { limit: 60, used: 0, remaining: 60 }) })

const questionInput = ref('')
const isSending = ref(false)
const isLoadingConv = ref(false)
const isMobileDrawerOpen = ref(false)
const copiedKey = ref<string | null>(null)
const pendingFeedbackId = ref<number | null>(null)

const errorBanner = ref<{ message: string; retryPayload?: { text?: string; quickAction?: string } } | null>(null)
const deleteTarget = ref<ConversationItem | null>(null)
const isDeleting = ref(false)

const chatContainerRef = ref<HTMLElement | null>(null)
const composerRef = ref<HTMLTextAreaElement | null>(null)

const labels = computed(() => isKm.value
  ? {
      pageTitle: 'ជំនួយការសិក្សា AI',
      dashboard: 'ផ្ទាំងគ្រប់គ្រង',
      aiLearning: 'ការរៀនជាមួយ AI',
      greeting: `សួស្តី ${studentName.value}!`,
      heroSub: 'សួរចម្ងល់អំពីមេរៀនរបស់អ្នក ស្នើសុំឧទាហរណ៍ ឬសង្ខេបខ្លឹមសារសិក្សាជាជំហានៗ។',
      quotaText: `នៅសល់ ${quotaState.value.remaining}/${quotaState.value.limit} សារថ្ងៃនេះ`,
      contextBtn: 'ព័ត៌មានវគ្គសិក្សា',
      chatTitle: 'សន្ទនាជាមួយជំនួយការ AI',
      newChat: 'សន្ទនាថ្មី',
      emptyChatTitle: 'ចាប់ផ្តើមការសន្ទនាសិក្សាថ្មី',
      emptyChatDescWithCourse: 'វាយសំណួររបស់អ្នកខាងក្រោម ឬជ្រើសរើសសកម្មភាពរហ័សសម្រាប់មេរៀនបច្ចុប្បន្ន។',
      emptyChatDescNoCourse: 'វាយសំណួរទូទៅខាងក្រោម ឬចុះឈ្មោះវគ្គសិក្សាដើម្បីឲ្យ AI ជួយតាមមេរៀនផ្ទាល់។',
      aiLabel: 'AI',
      youLabel: 'អ្នក',
      thinking: 'AI កំពុងវិភាគ និងរៀបចំចម្លើយ...',
      placeholder: 'វាយសំណួរសិក្សារបស់អ្នកនៅទីនេះ... (Ctrl + Enter ដើម្បីផ្ញើ)',
      send: 'ផ្ញើ',
      disclaimer: 'AI អាចមានកំហុស សូមផ្ទៀងផ្ទាត់ព័ត៌មានសំខាន់ៗ។',
      retry: 'សាកល្បងម្ដងទៀត',
      copy: 'ចម្លង',
      copied: 'បានចម្លង',
      helpful: 'មានប្រយោជន៍',
      notHelpful: 'មិនសូវមានប្រយោជន៍',
      contextTitle: 'បរិបទសិក្សារបស់អ្នក',
      selectCourseLabel: 'ជ្រើសរើសវគ្គសិក្សា',
      selectLessonLabel: 'ជ្រើសរើសមេរៀន / ជំពូក',
      currentCourse: 'វគ្គសិក្សាបច្ចុប្បន្ន',
      switchCourse: 'ប្តូរវគ្គ',
      progress: 'វឌ្ឍនភាព',
      noCourseTitle: 'មិនទាន់មានវគ្គសិក្សាដែលបានចុះឈ្មោះ',
      noCourseDesc: 'អ្នកអាចសួរសំណួរទូទៅបាន ឬស្វែងរកវគ្គសិក្សាដើម្បីរៀនជាមួយមេរៀនផ្ទាល់។',
      browseCourses: 'ស្វែងរកវគ្គសិក្សា',
      recentActivity: 'សកម្មភាពសិក្សាថ្មីៗ',
      noRecentActivity: 'មិនទាន់មានសកម្មភាពសិក្សាក្នុងសប្តាហ៍នេះទេ។',
      quotaExhaustedMsg: `អ្នកបានប្រើប្រាស់គ្រប់កូតា ${quotaState.value.limit}/${quotaState.value.limit} សារសម្រាប់ថ្ងៃនេះហើយ។ សូមត្រឡប់មកវិញនៅថ្ងៃស្អែក។`,
      quickActionsTitle: 'សកម្មភាពរហ័ស',
      quickNoCourseHint: 'សូមចុះឈ្មោះវគ្គសិក្សាជាមុនសិន ដើម្បីប្រើសកម្មភាពរហ័សតាមមេរៀន។',
      recentConversations: 'ប្រវត្តិសន្ទនាថ្មីៗ',
      newBtn: 'ថ្មី',
      noConversations: 'មិនទាន់មានប្រវត្តិសន្ទនានៅឡើយទេ។',
      deleteTitle: 'បញ្ជាក់ការលុបការសន្ទនា',
      deleteDesc: 'តើអ្នកពិតជាចង់លុបប្រវត្តិសន្ទនានេះមែនទេ? សកម្មភាពនេះនឹងដកវាចេញពីបញ្ជីរបស់អ្នក។',
      cancel: 'បោះបង់',
      confirmDelete: 'លុបចេញ',
      deleting: 'កំពុងលុប...',
      quickExplainTitle: 'ពន្យល់មេរៀននេះ',
      quickExplainSub: 'ការពន្យល់លម្អិតជាជំហានៗ',
      quickQuizTitle: 'បង្កើតសំណួរអនុវត្ត',
      quickQuizSub: 'សំណួរហ្វឹកហាត់ ៣ ចំណុច',
      quickSummarizeTitle: 'សង្ខេបមេរៀន',
      quickSummarizeSub: 'ចំណុចគន្លឹះសំខាន់ៗ',
      quickExampleTitle: 'លើកឧទាហរណ៍ជាក់ស្តែង',
      quickExampleSub: 'ឧទាហរណ៍អនុវត្តផ្ទាល់',
    }
  : {
      pageTitle: 'AI Study Assistant',
      dashboard: 'Dashboard',
      aiLearning: 'AI Learning',
      greeting: `Hello, ${studentName.value}!`,
      heroSub: 'Ask questions about your lessons, request practical examples, or summarize key concepts step by step.',
      quotaText: `${quotaState.value.remaining}/${quotaState.value.limit} messages left today`,
      contextBtn: 'Learning Context',
      chatTitle: 'Chat with AI Tutor',
      newChat: 'New Chat',
      emptyChatTitle: 'Start a New Study Conversation',
      emptyChatDescWithCourse: 'Type a question below or pick a quick action tailored to your current lesson.',
      emptyChatDescNoCourse: 'Ask any general study question below, or enroll in a course for lesson-aware guidance.',
      aiLabel: 'AI',
      youLabel: 'You',
      thinking: 'AI is analyzing and writing a response...',
      placeholder: 'Type your study question here... (Ctrl + Enter to send)',
      send: 'Send',
      disclaimer: 'AI អាចមានកំហុស សូមផ្ទៀងផ្ទាត់ — AI may make mistakes. Please verify important information.',
      retry: 'Retry',
      copy: 'Copy',
      copied: 'Copied',
      helpful: 'Helpful',
      notHelpful: 'Not helpful',
      contextTitle: 'Your Learning Context',
      selectCourseLabel: 'Select Course',
      selectLessonLabel: 'Select Lesson / Chapter',
      currentCourse: 'Current Course',
      switchCourse: 'Switch',
      progress: 'Progress',
      noCourseTitle: 'No Enrolled Courses Yet',
      noCourseDesc: 'You can still ask general study questions, or browse the catalog to start a course.',
      browseCourses: 'Browse Catalog',
      recentActivity: 'Recent Activity',
      noRecentActivity: 'No study activity recorded this week yet.',
      quotaExhaustedMsg: `Daily limit of ${quotaState.value.limit}/${quotaState.value.limit} messages reached. Please come back tomorrow.`,
      quickActionsTitle: 'Quick Actions',
      quickNoCourseHint: 'Please enroll or select a course first to run lesson quick actions.',
      recentConversations: 'Recent Conversations',
      newBtn: 'New',
      noConversations: 'No conversations yet. Start asking a question!',
      deleteTitle: 'Delete Conversation?',
      deleteDesc: 'Are you sure you want to delete this conversation from your history?',
      cancel: 'Cancel',
      confirmDelete: 'Delete',
      deleting: 'Deleting...',
      quickExplainTitle: 'Explain This Lesson',
      quickExplainSub: 'Detailed step-by-step guide',
      quickQuizTitle: 'Generate Quiz Questions',
      quickQuizSub: '3 practice questions with hints',
      quickSummarizeTitle: 'Summarize Topic',
      quickSummarizeSub: 'Key takeaways to remember',
      quickExampleTitle: 'Give Me an Example',
      quickExampleSub: 'Practical real-world example',
    })

const quickActions = computed(() => [
  { key: 'explain_lesson', title: labels.value.quickExplainTitle, sub: labels.value.quickExplainSub, icon: FileText },
  { key: 'generate_quiz', title: labels.value.quickQuizTitle, sub: labels.value.quickQuizSub, icon: HelpCircle },
  { key: 'summarize_topic', title: labels.value.quickSummarizeTitle, sub: labels.value.quickSummarizeSub, icon: ListChecks },
  { key: 'give_example', title: labels.value.quickExampleTitle, sub: labels.value.quickExampleSub, icon: Code2 },
])

const availableLessons = computed<CourseLessonOption[]>(() => selectedCourse.value?.lessons || [])

const selectedLessonObj = computed<CourseLessonOption | null>(() => {
  if (!availableLessons.value.length) return null
  if (selectedLessonId.value !== null) {
    const hit = availableLessons.value.find(l => l.id === selectedLessonId.value)
    if (hit) return hit
  }
  return availableLessons.value[0] || null
})

const displayedCourseTitle = computed(() => {
  if (!selectedCourse.value) return ''
  return isKm.value
    ? (selectedCourse.value.title_kh || selectedCourse.value.title)
    : (selectedCourse.value.title || selectedCourse.value.title_kh)
})

const displayedLessonTitle = computed(() => {
  if (selectedLessonObj.value) {
    return isKm.value
      ? (selectedLessonObj.value.title_kh || selectedLessonObj.value.title)
      : (selectedLessonObj.value.title || selectedLessonObj.value.title_kh)
  }
  if (!selectedCourse.value) return ''
  return isKm.value
    ? (selectedCourse.value.lesson_title_kh || selectedCourse.value.lesson_title || selectedCourse.value.module_title || '')
    : (selectedCourse.value.lesson_title || selectedCourse.value.lesson_title_kh || selectedCourse.value.module_title || '')
})

const displayedChapterTitle = computed(() => {
  if (selectedLessonObj.value?.chapter_title) return selectedLessonObj.value.chapter_title
  if (selectedLessonObj.value?.module_title) return selectedLessonObj.value.module_title
  return selectedCourse.value?.chapter_title || selectedCourse.value?.module_title || ''
})

// CSRF helper matching existing app conventions
const buildHeaders = (): Record<string, string> => {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': props.csrfToken || '',
  }
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
  if (match && match[1]) {
    headers['X-XSRF-TOKEN'] = decodeURIComponent(match[1])
  }
  return headers
}

const scrollToBottom = async () => {
  await nextTick()
  if (chatContainerRef.value) {
    chatContainerRef.value.scrollTop = chatContainerRef.value.scrollHeight
  }
}

// Safely parse server-rendered HTML into prose segments & copyable code cards
const decodeHtmlEntities = (str: string): string => {
  return str
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&#39;/g, "'")
}

const parseHtmlSegments = (html: string | null, fallbackBody: string): HtmlSegment[] => {
  if (!html) {
    return [{ type: 'html', content: fallbackBody }]
  }
  const segments: HtmlSegment[] = []
  const pattern = /<pre><code(?:\s+class="language-([^"]*)")?>([\s\S]*?)<\/code><\/pre>/gi
  let lastIdx = 0
  let match: RegExpExecArray | null

  while ((match = pattern.exec(html)) !== null) {
    if (match.index > lastIdx) {
      const before = html.slice(lastIdx, match.index).trim()
      if (before) segments.push({ type: 'html', content: before })
    }
    segments.push({
      type: 'code',
      language: match[1] || 'code',
      content: decodeHtmlEntities(match[2] || '').trimEnd(),
    })
    lastIdx = match.index + match[0].length
  }

  if (lastIdx < html.length) {
    const rest = html.slice(lastIdx).trim()
    if (rest) segments.push({ type: 'html', content: rest })
  }

  return segments.length > 0 ? segments : [{ type: 'html', content: html }]
}

const copyText = async (text: string, key: string) => {
  try {
    await navigator.clipboard.writeText(text)
    copiedKey.value = key
    setTimeout(() => {
      if (copiedKey.value === key) copiedKey.value = null
    }, 2000)
  } catch {
    // Clipboard unavailable in insecure context
  }
}

const selectCourseById = (id: number) => {
  const found = (props.context?.enrolled_courses || []).find(c => c.id === id)
  if (found) {
    selectedCourse.value = found
    selectedLessonId.value = found.lesson_id ?? found.lessons?.[0]?.id ?? null
  }
}

const createConversationRecord = async (
  courseId: number | null,
  initialMessages: AiMessageItem[] = []
): Promise<ConversationItem> => {
  const res = await fetch('/student/ai/conversations', {
    method: 'POST',
    credentials: 'same-origin',
    headers: buildHeaders(),
    body: JSON.stringify({
      course_id: courseId,
    }),
  })
  const data = await res.json().catch(() => ({}))
  if (!res.ok || !data?.conversation) {
    throw new Error(data?.message || 'Unable to create conversation.')
  }
  const created: ConversationItem = { ...data.conversation, messages: initialMessages }
  conversations.value = [created, ...conversations.value.filter(c => c.id !== created.id)].slice(0, 10)
  activeConversation.value = created
  return created
}

const startNewChat = () => {
  if (isSending.value) return
  errorBanner.value = null
  activeConversation.value = null
  questionInput.value = ''
  isMobileDrawerOpen.value = false
  nextTick(() => composerRef.value?.focus())
}

const selectConversation = async (conv: ConversationItem) => {
  if (isLoadingConv.value || activeConversation.value?.id === conv.id) {
    isMobileDrawerOpen.value = false
    return
  }
  errorBanner.value = null
  isLoadingConv.value = true
  try {
    const res = await fetch(`/student/ai/conversations/${conv.id}`, {
      method: 'GET',
      credentials: 'same-origin',
      headers: buildHeaders(),
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok || !data?.conversation) {
      throw new Error(data?.message || 'Unable to load conversation.')
    }
    activeConversation.value = data.conversation
    if (data.conversation.course_id) {
      selectCourseById(Number(data.conversation.course_id))
    }
    isMobileDrawerOpen.value = false
    scrollToBottom()
  } catch (e: any) {
    errorBanner.value = { message: e?.message || 'Unable to load conversation.' }
  } finally {
    isLoadingConv.value = false
  }
}

const confirmDeleteConversation = async () => {
  if (!deleteTarget.value || isDeleting.value) return
  const target = deleteTarget.value
  isDeleting.value = true
  try {
    const res = await fetch(`/student/ai/conversations/${target.id}`, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: buildHeaders(),
    })
    if (!res.ok) {
      throw new Error('Failed to delete conversation.')
    }
    conversations.value = conversations.value.filter(c => c.id !== target.id)
    if (activeConversation.value?.id === target.id) {
      const next = conversations.value[0]
      if (next) {
        activeConversation.value = null
        await selectConversation(next)
      } else {
        activeConversation.value = null
      }
    }
    deleteTarget.value = null
  } catch (e: any) {
    errorBanner.value = { message: e?.message || 'Failed to delete conversation.' }
    deleteTarget.value = null
  } finally {
    isDeleting.value = false
  }
}

const dispatchChatRequest = async (opts: { text?: string; quickAction?: string }) => {
  if (isSending.value) return
  if (quotaState.value.remaining <= 0) {
    errorBanner.value = { message: labels.value.quotaExhaustedMsg }
    return
  }

  const trimmed = (opts.text || '').trim()
  if (!opts.quickAction && trimmed.length < 2) return

  if (opts.quickAction && !props.context?.has_course) {
    errorBanner.value = { message: labels.value.quickNoCourseHint }
    return
  }

  errorBanner.value = null
  isSending.value = true
  isMobileDrawerOpen.value = false

  const courseId = selectedCourse.value?.id ?? null
  const lessonId = selectedLessonObj.value?.id ?? selectedLessonId.value ?? null
  const optimisticText = opts.quickAction
    ? (quickActions.value.find(q => q.key === opts.quickAction)?.title || opts.quickAction)
    : trimmed

  if (!opts.quickAction) {
    questionInput.value = ''
  }

  const tempMsgId = -Date.now()
  const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false })
  const optimisticUserMsg: AiMessageItem = {
    id: tempMsgId,
    role: 'user',
    body: optimisticText,
    html: null,
    my_rating: null,
    created_at_iso: null,
    time_label: nowTime,
  }

  const hadExistingConv = !!(activeConversation.value && activeConversation.value.id > 0)
  if (!activeConversation.value) {
    activeConversation.value = {
      id: 0,
      course_id: courseId,
      course_title: selectedCourse.value?.title ?? null,
      course_title_kh: selectedCourse.value?.title_kh ?? null,
      title: optimisticText.slice(0, 20),
      last_message_at: null,
      relative_time: '',
      messages: [optimisticUserMsg],
    }
  } else {
    activeConversation.value.messages = [...(activeConversation.value.messages || []), optimisticUserMsg]
  }
  scrollToBottom()

  const rollbackOptimistic = () => {
    if (!activeConversation.value) return
    const cleaned = (activeConversation.value.messages || []).filter(m => m.id !== tempMsgId)
    if (activeConversation.value.id === 0 && cleaned.length === 0) {
      activeConversation.value = null
    } else {
      activeConversation.value.messages = cleaned
    }
    if (!opts.quickAction && trimmed.length >= 2) {
      questionInput.value = trimmed
    }
  }

  try {
    let targetConvId = hadExistingConv ? activeConversation.value!.id : 0
    if (!targetConvId) {
      const created = await createConversationRecord(courseId, [optimisticUserMsg])
      targetConvId = created.id
    }

    const res = await fetch(`/student/ai/conversations/${targetConvId}/messages`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: buildHeaders(),
      body: JSON.stringify({
        message: opts.quickAction ? undefined : trimmed,
        quick_action: opts.quickAction || undefined,
        course_id: courseId,
        lesson_id: lessonId,
        lang: i18n.locale.value,
      }),
    })

    const data = await res.json().catch(() => ({}))
    if (!res.ok || !data?.ok) {
      rollbackOptimistic()
      if (data?.quota) {
        quotaState.value = data.quota
      }
      errorBanner.value = {
        message: data?.message || (isKm.value ? 'សេវា AI មិនអាចឆ្លើយតបបានទេ។ សូមព្យាយាមម្ដងទៀត។' : 'AI service unavailable. Please retry.'),
        retryPayload: res.status === 429 ? undefined : opts,
      }
      return
    }

    if (activeConversation.value) {
      const currentMsgs = (activeConversation.value.messages || []).filter(m => m.id !== tempMsgId)
      activeConversation.value = {
        ...data.conversation,
        messages: [...currentMsgs, data.user_message, data.assistant_message],
      }
    }

    const idx = conversations.value.findIndex(c => c.id === data.conversation.id)
    if (idx !== -1) {
      const updatedList = [...conversations.value]
      updatedList.splice(idx, 1)
      conversations.value = [data.conversation, ...updatedList].slice(0, 10)
    } else {
      conversations.value = [data.conversation, ...conversations.value].slice(0, 10)
    }

    if (data.quota) {
      quotaState.value = data.quota
    }

    scrollToBottom()
  } catch {
    rollbackOptimistic()
    errorBanner.value = {
      message: isKm.value ? 'បញ្ហាការតភ្ជាប់បណ្ដាញ។ សូមសាកល្បងម្ដងទៀត។' : 'Network error. Please retry.',
      retryPayload: opts,
    }
  } finally {
    isSending.value = false
  }
}

const handleSend = () => {
  dispatchChatRequest({ text: questionInput.value })
}

const handleQuickAction = (actionKey: string) => {
  dispatchChatRequest({ quickAction: actionKey })
}

const handleComposerKeydown = (e: KeyboardEvent) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
    e.preventDefault()
    if (quotaState.value.remaining > 0) {
      handleSend()
    }
  }
}

const submitFeedback = async (msg: AiMessageItem, rating: 'up' | 'down') => {
  if (pendingFeedbackId.value === msg.id || msg.id <= 0) return
  const prev = msg.my_rating
  pendingFeedbackId.value = msg.id
  msg.my_rating = rating
  try {
    const res = await fetch(`/student/ai/messages/${msg.id}/feedback`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: buildHeaders(),
      body: JSON.stringify({ rating }),
    })
    if (!res.ok) {
      msg.my_rating = prev
    }
  } catch {
    msg.my_rating = prev
  } finally {
    pendingFeedbackId.value = null
  }
}

onMounted(() => {
  scrollToBottom()
  const promptParam = new URLSearchParams(window.location.search).get('prompt')
  if (promptParam && promptParam.trim().length >= 2) {
    questionInput.value = promptParam.trim().slice(0, 1000)
  }
})

onUnmounted(() => {
  // Cleanup hook preserved
})
</script>

<template>
  <StudentLayout
    :title="labels.pageTitle"
    :breadcrumbs="[
      { label: labels.dashboard, href: '/student/dashboard' },
      { label: labels.aiLearning, href: '/student/ai-path/recommended' },
      { label: labels.pageTitle },
    ]"
  >
    <div class="space-y-6 pb-24 motion-reduce:transition-none">
      <!-- Short Hero Header (Light + Dark theme aware, zero white-on-white headings) -->
      <section class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1.5">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-50 dark:bg-purple-500/15 border border-purple-200 dark:border-purple-500/30 text-purple-700 dark:text-purple-300 text-xs font-semibold">
              <Sparkles class="w-3.5 h-3.5 shrink-0" />
              <span>{{ labels.pageTitle }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
              {{ labels.greeting }}
            </h1>
            <p class="text-sm text-slate-600 dark:text-slate-300 max-w-2xl">
              {{ labels.heroSub }}
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 dark:bg-[#282a2c] border border-slate-200 dark:border-[#3c4043] text-xs font-semibold text-slate-700 dark:text-slate-200">
              {{ labels.quotaText }}
            </span>
            <button
              @click="isMobileDrawerOpen = true"
              type="button"
              class="lg:hidden inline-flex items-center gap-2 min-h-[48px] px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer"
            >
              <PanelRightOpen class="w-4 h-4 shrink-0" />
              <span>{{ labels.contextBtn }}</span>
            </button>
          </div>
        </div>
      </section>

      <!-- Main 2-Column Layout: Chat (Main) | Context + Quick Actions + Recent Conversations (340px Right) -->
      <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_340px] gap-6 items-start">
        <!-- MAIN CHAT CARD -->
        <section class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] shadow-xs overflow-hidden flex flex-col">
          <!-- Chat Header -->
          <div class="px-5 py-4 border-b border-slate-200 dark:border-[#2d2f31] flex flex-wrap items-center justify-between gap-3 bg-slate-50/70 dark:bg-[#18191b]">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center shrink-0">
                <Sparkles class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                  {{ activeConversation?.title || labels.chatTitle }}
                </h2>
                <p v-if="context.has_course && displayedCourseTitle" class="text-xs text-purple-700 dark:text-purple-300 truncate font-medium">
                  {{ displayedCourseTitle }} ({{ selectedCourse?.progress_percent ?? 0 }}%)
                </p>
              </div>
            </div>

            <button
              @click="startNewChat"
              type="button"
              :disabled="isSending"
              class="inline-flex items-center gap-1.5 min-h-[40px] px-3.5 py-1.5 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-white dark:bg-[#282a2c] hover:bg-slate-100 dark:hover:bg-[#323437] text-xs font-semibold text-slate-700 dark:text-slate-200 transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 disabled:opacity-50 cursor-pointer"
            >
              <Plus class="w-4 h-4 shrink-0" />
              <span>{{ labels.newChat }}</span>
            </button>
          </div>

          <!-- Messages Viewport -->
          <div
            ref="chatContainerRef"
            class="p-4 sm:p-6 space-y-5 min-h-[380px] max-h-[60vh] overflow-y-auto bg-slate-50/40 dark:bg-[#131314]/60"
          >
            <!-- Loading Skeleton -->
            <div v-if="isLoadingConv" class="space-y-4 animate-pulse motion-reduce:animate-none">
              <div class="flex gap-3">
                <div class="w-8 h-8 rounded-xl bg-slate-200 dark:bg-[#282a2c] shrink-0"></div>
                <div class="h-16 w-2/3 rounded-2xl bg-slate-200 dark:bg-[#282a2c]"></div>
              </div>
              <div class="flex justify-end gap-3">
                <div class="h-12 w-1/2 rounded-2xl bg-purple-200/70 dark:bg-purple-900/30"></div>
              </div>
            </div>

            <!-- Honest Empty Chat State -->
            <div v-else-if="activeMessages.length === 0" class="py-8 text-center space-y-4">
              <div class="w-12 h-12 mx-auto rounded-2xl bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-300 flex items-center justify-center">
                <Sparkles class="w-6 h-6" />
              </div>
              <div class="space-y-1 max-w-md mx-auto">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                  {{ labels.emptyChatTitle }}
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                  {{ context.has_course ? labels.emptyChatDescWithCourse : labels.emptyChatDescNoCourse }}
                </p>
              </div>

              <div v-if="context.has_course" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-w-xl mx-auto pt-2">
                <button
                  v-for="qa in quickActions"
                  :key="qa.key"
                  @click="handleQuickAction(qa.key)"
                  type="button"
                  :disabled="isSending || quotaState.remaining <= 0"
                  class="flex items-center gap-3 p-3 rounded-2xl border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] hover:border-purple-400 dark:hover:border-purple-500/50 text-left transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                >
                  <component :is="qa.icon" class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0" />
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ qa.title }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ qa.sub }}</p>
                  </div>
                </button>
              </div>
            </div>

            <!-- Message Bubbles -->
            <template v-else>
              <div
                v-for="msg in activeMessages"
                :key="msg.id"
                :class="[msg.role === 'user' ? 'justify-end' : 'justify-start', 'flex gap-3 items-start']"
              >
                <!-- AI Avatar -->
                <div
                  v-if="msg.role === 'assistant'"
                  class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-500/20 border border-purple-200 dark:border-purple-500/40 text-purple-700 dark:text-purple-300 flex items-center justify-center shrink-0"
                  aria-hidden="true"
                >
                  <Sparkles class="w-4 h-4" />
                </div>

                <!-- Message Card -->
                <div
                  :class="[
                    msg.role === 'user'
                      ? 'bg-purple-600 text-white rounded-2xl rounded-tr-xs max-w-xl'
                      : 'bg-white dark:bg-[#1e1f20] border border-slate-200 dark:border-[#2d2f31] text-slate-800 dark:text-slate-100 rounded-2xl rounded-tl-xs max-w-2xl shadow-2xs',
                    'p-4 space-y-3 text-sm leading-relaxed'
                  ]"
                >
                  <!-- Role Badge Header -->
                  <div v-if="msg.role === 'assistant'" class="flex items-center gap-2 text-[11px] font-bold text-purple-700 dark:text-purple-300">
                    <span class="px-2 py-0.5 rounded-md bg-purple-50 dark:bg-purple-500/20 border border-purple-200 dark:border-purple-500/30 uppercase tracking-wider">
                      {{ labels.aiLabel }}
                    </span>
                    <span v-if="msg.time_label" class="font-normal text-slate-500 dark:text-slate-400">{{ msg.time_label }}</span>
                  </div>

                  <!-- User Text -->
                  <div v-if="msg.role === 'user'" class="whitespace-pre-wrap break-words">{{ msg.body }}</div>

                  <!-- Safe Server-Sanitized Markdown & Copyable Code Blocks -->
                  <div v-else class="space-y-3">
                    <template v-for="(seg, sIdx) in parseHtmlSegments(msg.html, msg.body)" :key="`${msg.id}-${sIdx}`">
                      <div
                        v-if="seg.type === 'html'"
                        v-html="seg.content"
                        class="ai-markdown-prose text-sm text-slate-800 dark:text-slate-100 leading-relaxed break-words"
                      ></div>

                      <div
                        v-else
                        class="rounded-xl border border-slate-200 dark:border-[#2d2f31] bg-slate-900 text-slate-100 overflow-hidden text-xs font-mono"
                      >
                        <div class="px-3.5 py-2 bg-slate-800 border-b border-slate-700 flex items-center justify-between">
                          <span class="text-[11px] font-semibold text-slate-300 uppercase">{{ seg.language }}</span>
                          <button
                            @click="copyText(seg.content, `code-${msg.id}-${sIdx}`)"
                            type="button"
                            :aria-label="labels.copy"
                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg hover:bg-slate-700 text-slate-200 text-[11px] transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 cursor-pointer"
                          >
                            <Check v-if="copiedKey === `code-${msg.id}-${sIdx}`" class="w-3.5 h-3.5 text-emerald-400" />
                            <Copy v-else class="w-3.5 h-3.5" />
                            <span>{{ copiedKey === `code-${msg.id}-${sIdx}` ? labels.copied : labels.copy }}</span>
                          </button>
                        </div>
                        <pre class="p-3.5 overflow-x-auto leading-relaxed"><code>{{ seg.content }}</code></pre>
                      </div>
                    </template>
                  </div>

                  <!-- Message Footer -->
                  <div class="flex items-center justify-between gap-2 pt-1 text-xs">
                    <div v-if="msg.role === 'assistant'" class="flex items-center gap-1">
                      <button
                        @click="submitFeedback(msg, 'up')"
                        type="button"
                        :aria-label="labels.helpful"
                        :class="[
                          msg.my_rating === 'up'
                            ? 'bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/40'
                            : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-[#282a2c] border-transparent',
                          'inline-flex items-center justify-center min-h-[36px] min-w-[36px] rounded-lg border transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer'
                        ]"
                      >
                        <ThumbsUp class="w-3.5 h-3.5" />
                      </button>

                      <button
                        @click="submitFeedback(msg, 'down')"
                        type="button"
                        :aria-label="labels.notHelpful"
                        :class="[
                          msg.my_rating === 'down'
                            ? 'bg-rose-50 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-500/40'
                            : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-[#282a2c] border-transparent',
                          'inline-flex items-center justify-center min-h-[36px] min-w-[36px] rounded-lg border transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer'
                        ]"
                      >
                        <ThumbsDown class="w-3.5 h-3.5" />
                      </button>

                      <button
                        @click="copyText(msg.body, `msg-${msg.id}`)"
                        type="button"
                        :aria-label="labels.copy"
                        class="inline-flex items-center gap-1 min-h-[36px] px-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-[#282a2c] transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer"
                      >
                        <Check v-if="copiedKey === `msg-${msg.id}`" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                        <Copy v-else class="w-3.5 h-3.5" />
                      </button>
                    </div>

                    <span v-else class="text-[11px] text-purple-100 ml-auto">{{ msg.time_label }}</span>
                  </div>
                </div>

                <!-- Student Real Avatar / Initials -->
                <div
                  v-if="msg.role === 'user'"
                  class="w-8 h-8 rounded-xl bg-purple-600/15 border border-purple-500/30 flex items-center justify-center text-xs font-bold text-purple-700 dark:text-purple-200 shrink-0 overflow-hidden"
                >
                  <img
                    v-if="userAvatarUrl"
                    :src="userAvatarUrl"
                    :alt="studentName"
                    class="w-full h-full object-cover"
                  />
                  <span v-else>{{ studentInitials }}</span>
                </div>
              </div>
            </template>

            <!-- Typing Indicator -->
            <div v-if="isSending" class="flex gap-3 items-start">
              <div class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-500/20 border border-purple-200 dark:border-purple-500/40 text-purple-700 dark:text-purple-300 flex items-center justify-center shrink-0">
                <Sparkles class="w-4 h-4" />
              </div>
              <div class="rounded-2xl rounded-tl-xs border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] px-4 py-3 flex items-center gap-3 text-xs text-slate-600 dark:text-slate-300">
                <span class="flex items-center gap-1" aria-hidden="true">
                  <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce motion-reduce:animate-none"></span>
                  <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce [animation-delay:0.15s] motion-reduce:animate-none"></span>
                  <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce [animation-delay:0.3s] motion-reduce:animate-none"></span>
                </span>
                <span>{{ labels.thinking }}</span>
              </div>
            </div>

            <!-- Inline Error + Retry Banner -->
            <div
              v-if="errorBanner"
              role="alert"
              class="rounded-2xl border border-rose-200 dark:border-rose-500/40 bg-rose-50 dark:bg-rose-500/10 p-3.5 flex flex-wrap items-center justify-between gap-3"
            >
              <div class="flex items-center gap-2.5 text-xs font-medium text-rose-700 dark:text-rose-200">
                <AlertCircle class="w-4 h-4 shrink-0" />
                <span>{{ errorBanner.message }}</span>
              </div>
              <button
                v-if="errorBanner.retryPayload"
                @click="dispatchChatRequest(errorBanner.retryPayload)"
                type="button"
                class="inline-flex items-center gap-1.5 min-h-[36px] px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 cursor-pointer"
              >
                <RotateCcw class="w-3.5 h-3.5 shrink-0" />
                <span>{{ labels.retry }}</span>
              </button>
            </div>
          </div>

          <!-- Composer Footer (No attachment button; clean contrast disclaimer) -->
          <div class="p-4 border-t border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] space-y-2.5">
            <div
              v-if="quotaState.remaining <= 0"
              role="status"
              class="rounded-xl border border-amber-200 dark:border-amber-500/40 bg-amber-50 dark:bg-amber-500/10 px-3.5 py-2 text-xs font-medium text-amber-800 dark:text-amber-200"
            >
              {{ labels.quotaExhaustedMsg }}
            </div>

            <form @submit.prevent="handleSend" class="flex items-end gap-2.5">
              <textarea
                ref="composerRef"
                v-model="questionInput"
                @keydown="handleComposerKeydown"
                rows="2"
                maxlength="1000"
                :disabled="isSending || quotaState.remaining <= 0"
                :placeholder="labels.placeholder"
                class="flex-1 resize-none rounded-2xl border border-slate-300 dark:border-[#3c4043] bg-slate-50 dark:bg-[#131314] px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-500 dark:placeholder-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus:border-purple-500 disabled:opacity-60 disabled:cursor-not-allowed"
              ></textarea>

              <button
                type="submit"
                :disabled="isSending || questionInput.trim().length < 2 || quotaState.remaining <= 0"
                class="inline-flex items-center justify-center gap-2 min-h-[48px] min-w-[48px] px-4 sm:px-5 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold shadow-xs transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer shrink-0"
              >
                <Send class="w-4 h-4 shrink-0" />
                <span class="hidden sm:inline">{{ labels.send }}</span>
              </button>
            </form>

            <p class="text-[13px] text-slate-600 dark:text-slate-300 text-center">
              {{ labels.disclaimer }}
            </p>
          </div>
        </section>

        <!-- RIGHT SIDEBAR (Desktop 340px) -->
        <aside class="hidden lg:flex lg:flex-col gap-5 w-full">
          <!-- 1. LEARNING CONTEXT CARD -->
          <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#2d2f31] pb-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-100">
                {{ labels.contextTitle }}
              </h3>
            </div>

            <!-- Real Current Course & Lesson/Chapter Dropdowns -->
            <div v-if="context.has_course && selectedCourse" class="space-y-3">
              <div class="space-y-1.5">
                <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">
                  {{ labels.selectCourseLabel }}
                </label>
                <select
                  :value="selectedCourse.id"
                  @change="selectCourseById(Number(($event.target as HTMLSelectElement).value))"
                  class="w-full rounded-xl border border-slate-200 dark:border-[#3c4043] bg-slate-50 dark:bg-[#131314] px-3 py-2 text-xs font-semibold text-slate-900 dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer"
                >
                  <option v-for="c in context.enrolled_courses" :key="c.id" :value="c.id">
                    {{ isKm ? (c.title_kh || c.title) : (c.title || c.title_kh) }} ({{ c.progress_percent }}%)
                  </option>
                </select>
              </div>

              <div v-if="availableLessons.length > 0" class="space-y-1.5">
                <label class="block text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">
                  {{ labels.selectLessonLabel }}
                </label>
                <select
                  v-model.number="selectedLessonId"
                  class="w-full rounded-xl border border-slate-200 dark:border-[#3c4043] bg-slate-50 dark:bg-[#131314] px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 cursor-pointer"
                >
                  <option v-for="l in availableLessons" :key="l.id" :value="l.id">
                    {{ isKm ? (l.title_kh || l.title) : (l.title || l.title_kh) }}{{ l.chapter_title ? ` — ${l.chapter_title}` : '' }}
                  </option>
                </select>
              </div>

              <div class="p-3.5 rounded-2xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50 dark:bg-[#131314] space-y-2.5">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-xl bg-purple-600/15 text-purple-700 dark:text-purple-300 font-bold text-xs flex items-center justify-center shrink-0">
                    <BookOpen class="w-5 h-5" />
                  </div>
                  <div class="min-w-0">
                    <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">
                      {{ displayedCourseTitle }}
                    </h4>
                    <p v-if="displayedChapterTitle" class="text-[11px] font-medium text-purple-700 dark:text-purple-300 truncate mt-0.5">
                      {{ displayedChapterTitle }}
                    </p>
                    <p v-if="displayedLessonTitle" class="text-[11px] text-slate-600 dark:text-slate-400 truncate mt-0.5">
                      {{ displayedLessonTitle }}
                    </p>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <div class="flex items-center justify-between text-[11px]">
                    <span class="text-slate-600 dark:text-slate-400">{{ labels.progress }}</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ selectedCourse.progress_percent }}%</span>
                  </div>
                  <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-[#282a2c] overflow-hidden">
                    <div
                      class="h-full bg-purple-600 dark:bg-purple-500 rounded-full transition-all duration-300 motion-reduce:transition-none"
                      :style="{ width: `${selectedCourse.progress_percent}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Honest Empty Course State -->
            <div v-else class="p-4 rounded-2xl border border-dashed border-slate-300 dark:border-[#3c4043] bg-slate-50 dark:bg-[#131314] text-center space-y-2.5">
              <Compass class="w-6 h-6 mx-auto text-slate-400 dark:text-slate-500" />
              <h4 class="text-xs font-bold text-slate-900 dark:text-white">{{ labels.noCourseTitle }}</h4>
              <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">{{ labels.noCourseDesc }}</p>
              <Link
                href="/student/browse"
                class="inline-flex items-center justify-center min-h-[40px] px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold transition-colors"
              >
                {{ labels.browseCourses }}
              </Link>
            </div>

            <!-- Real Recent Activity -->
            <div class="pt-3 border-t border-slate-100 dark:border-[#2d2f31] space-y-2">
              <p class="text-[11px] font-bold uppercase text-slate-500 dark:text-slate-400">
                {{ labels.recentActivity }}
              </p>
              <ul v-if="context.recent_activity && context.recent_activity.length > 0" class="space-y-2">
                <li
                  v-for="(act, aIdx) in context.recent_activity"
                  :key="aIdx"
                  class="flex items-start gap-2 text-xs text-slate-700 dark:text-slate-300"
                >
                  <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-purple-500 shrink-0"></span>
                  <div class="min-w-0">
                    <p class="font-medium truncate">{{ isKm ? act.title_km : act.title_en }}</p>
                    <p v-if="act.time" class="text-[11px] text-slate-500 dark:text-slate-400">{{ act.time }}</p>
                  </div>
                </li>
              </ul>
              <p v-else class="text-xs text-slate-500 dark:text-slate-400">
                {{ labels.noRecentActivity }}
              </p>
            </div>
          </div>

          <!-- 2. QUICK ACTIONS CARD -->
          <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 shadow-xs space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-100 border-b border-slate-100 dark:border-[#2d2f31] pb-3">
              {{ labels.quickActionsTitle }}
            </h3>
            <div class="space-y-2">
              <button
                v-for="qa in quickActions"
                :key="qa.key"
                @click="handleQuickAction(qa.key)"
                type="button"
                :disabled="isSending || !context.has_course || quotaState.remaining <= 0"
                class="w-full min-h-[48px] p-3 rounded-2xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50/70 dark:bg-[#131314] hover:border-purple-400 dark:hover:border-purple-500/50 text-left flex items-center gap-3 transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
              >
                <component :is="qa.icon" class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0" />
                <div class="min-w-0">
                  <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ qa.title }}</p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ qa.sub }}</p>
                </div>
              </button>
            </div>
          </div>

          <!-- 3. RECENT CONVERSATIONS CARD -->
          <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-[#2d2f31] pb-3">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-100">
                {{ labels.recentConversations }}
              </h3>
              <button
                @click="startNewChat"
                type="button"
                class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 dark:text-purple-400 hover:text-purple-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 rounded-lg px-2 py-1 cursor-pointer"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>{{ labels.newBtn }}</span>
              </button>
            </div>

            <div v-if="conversations.length === 0" class="py-6 text-center text-xs text-slate-500 dark:text-slate-400">
              {{ labels.noConversations }}
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="conv in conversations"
                :key="conv.id"
                :class="[
                  activeConversation?.id === conv.id
                    ? 'bg-purple-50 dark:bg-purple-500/15 border-purple-300 dark:border-purple-500/50'
                    : 'bg-slate-50/70 dark:bg-[#131314] border-slate-200 dark:border-[#2d2f31] hover:border-purple-300 dark:hover:border-purple-500/30',
                  'rounded-2xl border flex items-center justify-between gap-2 px-3 py-2.5 transition-colors motion-reduce:transition-none'
                ]"
              >
                <button
                  @click="selectConversation(conv)"
                  type="button"
                  class="flex-1 min-w-0 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 rounded-lg cursor-pointer"
                >
                  <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ conv.title }}</p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ conv.relative_time }}</p>
                </button>

                <button
                  @click="deleteTarget = conv"
                  type="button"
                  :aria-label="labels.confirmDelete"
                  class="inline-flex items-center justify-center min-h-[36px] min-w-[36px] rounded-xl text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 cursor-pointer shrink-0"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>

    <!-- MOBILE CONTEXT & CONVERSATIONS DRAWER -->
    <div
      v-if="isMobileDrawerOpen"
      class="fixed inset-0 z-50 lg:hidden bg-slate-950/70 backdrop-blur-xs flex justify-end"
      @click.self="isMobileDrawerOpen = false"
    >
      <div class="w-full max-w-sm h-full bg-white dark:bg-[#1e1f20] border-l border-slate-200 dark:border-[#2d2f31] p-5 overflow-y-auto space-y-5">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-[#2d2f31] pb-3">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ labels.contextBtn }}</h3>
          <button
            @click="isMobileDrawerOpen = false"
            type="button"
            aria-label="Close"
            class="min-h-[48px] min-w-[48px] flex items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-[#282a2c] cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Mobile Course Context -->
        <div v-if="context.has_course && selectedCourse" class="space-y-3">
          <div class="space-y-1.5">
            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400">{{ labels.selectCourseLabel }}</label>
            <select
              :value="selectedCourse.id"
              @change="selectCourseById(Number(($event.target as HTMLSelectElement).value))"
              class="w-full text-xs rounded-xl border border-slate-300 dark:border-[#3c4043] bg-slate-50 dark:bg-[#131314] px-3 py-2 font-semibold text-slate-900 dark:text-white"
            >
              <option v-for="c in context.enrolled_courses" :key="c.id" :value="c.id">
                {{ isKm ? (c.title_kh || c.title) : c.title }} ({{ c.progress_percent }}%)
              </option>
            </select>
          </div>

          <div v-if="availableLessons.length > 0" class="space-y-1.5">
            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400">{{ labels.selectLessonLabel }}</label>
            <select
              v-model.number="selectedLessonId"
              class="w-full text-xs rounded-xl border border-slate-300 dark:border-[#3c4043] bg-slate-50 dark:bg-[#131314] px-3 py-2 text-slate-900 dark:text-white"
            >
              <option v-for="l in availableLessons" :key="l.id" :value="l.id">
                {{ isKm ? (l.title_kh || l.title) : (l.title || l.title_kh) }}{{ l.chapter_title ? ` — ${l.chapter_title}` : '' }}
              </option>
            </select>
          </div>

          <div class="p-3.5 rounded-2xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50 dark:bg-[#131314] space-y-2">
            <p class="text-xs font-bold text-slate-900 dark:text-white">{{ displayedCourseTitle }}</p>
            <p v-if="displayedChapterTitle" class="text-[11px] font-medium text-purple-700 dark:text-purple-300">{{ displayedChapterTitle }}</p>
            <p v-if="displayedLessonTitle" class="text-[11px] text-slate-600 dark:text-slate-400">{{ displayedLessonTitle }}</p>
            <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-[#282a2c] overflow-hidden">
              <div class="h-full bg-purple-600 rounded-full" :style="{ width: `${selectedCourse.progress_percent}%` }"></div>
            </div>
          </div>
        </div>

        <div v-else class="p-4 rounded-2xl border border-dashed border-slate-300 dark:border-[#3c4043] text-center space-y-2">
          <p class="text-xs font-bold text-slate-900 dark:text-white">{{ labels.noCourseTitle }}</p>
          <Link
            href="/student/browse"
            class="inline-flex items-center justify-center min-h-[48px] px-4 rounded-xl bg-purple-600 text-white text-xs font-semibold"
          >
            {{ labels.browseCourses }}
          </Link>
        </div>

        <!-- Mobile Recent Activity -->
        <div class="space-y-2">
          <p class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">{{ labels.recentActivity }}</p>
          <ul v-if="context.recent_activity && context.recent_activity.length > 0" class="space-y-1.5 text-xs text-slate-700 dark:text-slate-300">
            <li v-for="(act, aIdx) in context.recent_activity" :key="aIdx">• {{ isKm ? act.title_km : act.title_en }}</li>
          </ul>
          <p v-else class="text-xs text-slate-500 dark:text-slate-400">{{ labels.noRecentActivity }}</p>
        </div>

        <!-- Mobile Quick Actions -->
        <div class="space-y-2">
          <p class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">{{ labels.quickActionsTitle }}</p>
          <button
            v-for="qa in quickActions"
            :key="qa.key"
            @click="handleQuickAction(qa.key)"
            type="button"
            :disabled="isSending || !context.has_course || quotaState.remaining <= 0"
            class="w-full min-h-[48px] p-3 rounded-2xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50 dark:bg-[#131314] text-left flex items-center gap-3 text-xs font-bold text-slate-900 dark:text-white disabled:opacity-50"
          >
            <component :is="qa.icon" class="w-4 h-4 text-purple-600 shrink-0" />
            <span>{{ qa.title }}</span>
          </button>
        </div>

        <!-- Mobile Conversations -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <p class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">{{ labels.recentConversations }}</p>
            <button @click="startNewChat" type="button" class="text-xs font-semibold text-purple-600 dark:text-purple-400 min-h-[40px] px-2">
              + {{ labels.newBtn }}
            </button>
          </div>
          <p v-if="conversations.length === 0" class="text-xs text-slate-500 dark:text-slate-400">{{ labels.noConversations }}</p>
          <div v-else class="space-y-2">
            <div v-for="conv in conversations" :key="conv.id" class="flex items-center gap-2">
              <button
                @click="selectConversation(conv)"
                type="button"
                class="flex-1 min-h-[48px] px-3 py-2 rounded-2xl border border-slate-200 dark:border-[#2d2f31] text-left text-xs font-bold text-slate-900 dark:text-white truncate"
              >
                {{ conv.title }}
              </button>
              <button
                @click="deleteTarget = conv"
                type="button"
                :aria-label="labels.confirmDelete"
                class="min-h-[48px] min-w-[48px] flex items-center justify-center rounded-2xl border border-slate-200 dark:border-[#2d2f31] text-rose-500"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div
      v-if="deleteTarget"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
      @click.self="deleteTarget = null"
    >
      <div class="w-full max-w-sm rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-6 shadow-2xl space-y-4">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ labels.deleteTitle }}</h3>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">{{ labels.deleteDesc }}</p>
        <div class="flex items-center justify-end gap-2.5 pt-2">
          <button
            @click="deleteTarget = null"
            type="button"
            class="min-h-[40px] px-4 rounded-xl border border-slate-300 dark:border-[#3c4043] text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-[#282a2c] cursor-pointer"
          >
            {{ labels.cancel }}
          </button>
          <button
            @click="confirmDeleteConversation"
            type="button"
            :disabled="isDeleting"
            class="min-h-[40px] px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold disabled:opacity-50 cursor-pointer"
          >
            {{ isDeleting ? labels.deleting : labels.confirmDelete }}
          </button>
        </div>
      </div>
    </div>
  </StudentLayout>
</template>

<style scoped>
.ai-markdown-prose :deep(p) {
  margin-bottom: 0.5rem;
}
.ai-markdown-prose :deep(p:last-child) {
  margin-bottom: 0;
}
.ai-markdown-prose :deep(ul),
.ai-markdown-prose :deep(ol) {
  margin: 0.5rem 0;
  padding-left: 1.25rem;
}
.ai-markdown-prose :deep(ul) {
  list-style-type: disc;
}
.ai-markdown-prose :deep(ol) {
  list-style-type: decimal;
}
.ai-markdown-prose :deep(code) {
  padding: 0.125rem 0.375rem;
  border-radius: 0.375rem;
  font-size: 0.85em;
  background-color: rgba(147, 51, 234, 0.1);
}
.ai-markdown-prose :deep(strong) {
  font-weight: 700;
}
.ai-markdown-prose :deep(h1),
.ai-markdown-prose :deep(h2),
.ai-markdown-prose :deep(h3),
.ai-markdown-prose :deep(h4) {
  font-weight: 700;
  margin-top: 0.75rem;
  margin-bottom: 0.35rem;
  line-height: 1.35;
}
.ai-markdown-prose :deep(blockquote) {
  margin: 0.6rem 0;
  padding: 0.4rem 0.75rem;
  border-left: 3px solid rgb(147, 51, 234);
  background-color: rgba(147, 51, 234, 0.06);
  border-radius: 0.375rem;
}
.ai-markdown-prose :deep(hr) {
  margin: 0.75rem 0;
  border-color: rgba(148, 163, 184, 0.3);
}
.ai-markdown-prose :deep(a) {
  color: rgb(147, 51, 234);
  text-decoration: underline;
}
.ai-markdown-prose :deep(table) {
  width: 100%;
  margin: 0.75rem 0;
  border-collapse: collapse;
  font-size: 0.85em;
  display: block;
  overflow-x: auto;
}
.ai-markdown-prose :deep(thead) {
  background-color: rgba(147, 51, 234, 0.08);
}
.ai-markdown-prose :deep(th),
.ai-markdown-prose :deep(td) {
  border: 1px solid rgba(148, 163, 184, 0.35);
  padding: 0.45rem 0.65rem;
  text-align: left;
  vertical-align: top;
}
.ai-markdown-prose :deep(th) {
  font-weight: 700;
}
</style>
