<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import {
  TrendingDown,
  Clock,
  Target,
  Sparkles,
  HelpCircle,
  BookOpen,
  ArrowRight,
  CheckCircle2,
  X,
  Calendar,
  ExternalLink,
  ChevronDown
} from 'lucide-vue-next'
import { i18n } from '@/Services/i18n'

interface WeakTopicItem {
  id: number
  title: string
  title_en: string
  title_kh: string
  subtitle: string
  course: string
  course_id: number
  lesson_id: number | null
  chapter: string
  lessonHref: string
  score: number
  totalAnswered: number
  correctCount: number
  lastAttemptAt: string | null
  lastAttempt: string
  priority: 'high' | 'medium' | 'low'
  category: 'quiz' | 'assessment' | 'course_content'
  recommendedMins: number
}

interface FocusCategory {
  minutes: number
  formatted: string
  percent: number
}

interface FocusThisWeek {
  totalMinutes: number
  formattedTotal: string
  high: FocusCategory
  medium: FocusCategory
  low: FocusCategory
}

interface RecentImprovement {
  topic_id: number
  title: string
  first_score: number
  last_score: number
  gain: number
}

interface StudyPlanItemData {
  id: number
  day_index: number
  topic_id: number
  topic: string
  minutes: number
  kind: string
  is_done: boolean
  done_at: string | null
}

interface StudyPlanData {
  id: number
  status: string
  starts_on: string
  totalDays: number
  completedCount: number
  percent: number
  items: StudyPlanItemData[]
}

interface WeakTopicsPayload {
  hasData: boolean
  unmappedQuestions: number
  summary: {
    totalTopics: number
    avgScore: number | null
    recommendedFocusTime: string | null
    recommendedMinutes: number
    gapToPass: number | null
  }
  aiRecommendation: {
    isAiGenerated: boolean
    targetTopicId: number | null
    text_km: string
    text_en: string
  } | null
  chips: {
    all: number
    quiz: number
    assessment: number
    course: number
  }
  topics: WeakTopicItem[]
  focusThisWeek: FocusThisWeek
  recentImprovements: RecentImprovement[]
  studyPlan: StudyPlanData | null
}

const props = defineProps<{
  weakTopicsData?: WeakTopicsPayload
  csrfToken?: string
}>()

const page = usePage()
const isKm = computed(() => i18n.locale.value === 'km')

const emptyFallback = (): WeakTopicsPayload => ({
  hasData: false,
  unmappedQuestions: 0,
  summary: {
    totalTopics: 0,
    avgScore: null,
    recommendedFocusTime: null,
    recommendedMinutes: 0,
    gapToPass: null,
  },
  aiRecommendation: null,
  chips: { all: 0, quiz: 0, assessment: 0, course: 0 },
  topics: [],
  focusThisWeek: {
    totalMinutes: 0,
    formattedTotal: '0m',
    high: { minutes: 0, formatted: '0m', percent: 0 },
    medium: { minutes: 0, formatted: '0m', percent: 0 },
    low: { minutes: 0, formatted: '0m', percent: 0 },
  },
  recentImprovements: [],
  studyPlan: null,
})

const dataState = ref<WeakTopicsPayload>(props.weakTopicsData || (page.props as any).weakTopicsData || emptyFallback())

// Filter and Sort State
const activeTab = ref<'all' | 'quiz' | 'assessment' | 'course_content'>('all')
const sortBy = ref<'score_asc' | 'score_desc' | 'priority' | 'recent'>('score_asc')
const showMoreTopics = ref(false)

// Modals State
const isReviewModalOpen = ref(false)
const selectedTopicForReview = ref<WeakTopicItem | null>(null)

const isPracticeModalOpen = ref(false)
const practiceLoading = ref(false)
const practiceQuestions = ref<any[]>([])
const practiceQuestionIndex = ref(0)
const practiceSelectedAnswer = ref<any>(null)
const practiceSubmitted = ref(false)
const practiceScore = ref(0)

const isResourcesModalOpen = ref(false)
const resourcesLoading = ref(false)
const topicResourcesList = ref<any[]>([])
const currentResourceTopic = ref('')

const isActivatingPlan = ref(false)
const showToast = ref(false)
const toastMessage = ref('')

const triggerToast = (msg: string) => {
  toastMessage.value = msg
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 4000)
}

// Filtered and Sorted Topics
const filteredTopics = computed(() => {
  let list = [...(dataState.value.topics || [])]

  if (activeTab.value !== 'all') {
    list = list.filter(t => t.category === activeTab.value)
  }

  if (sortBy.value === 'score_asc') {
    list.sort((a, b) => a.score - b.score)
  } else if (sortBy.value === 'score_desc') {
    list.sort((a, b) => b.score - a.score)
  } else if (sortBy.value === 'priority') {
    const w: Record<string, number> = { high: 3, medium: 2, low: 1 }
    list.sort((a, b) => (w[b.priority] || 0) - (w[a.priority] || 0))
  } else if (sortBy.value === 'recent') {
    list.sort((a, b) => new Date(b.lastAttemptAt || 0).getTime() - new Date(a.lastAttemptAt || 0).getTime())
  }

  return list
})

const visibleTopics = computed(() => {
  if (showMoreTopics.value) {
    return filteredTopics.value
  }
  return filteredTopics.value.slice(0, 5)
})

// Focus this week donut math
const RING_RADIUS = 38
const RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS // ~238.76

const ringHighOffset = computed(() => {
  const p = dataState.value.focusThisWeek.high.percent / 100
  return RING_CIRCUMFERENCE * (1 - p)
})

const ringMedOffset = computed(() => {
  const p = dataState.value.focusThisWeek.medium.percent / 100
  return RING_CIRCUMFERENCE * (1 - p)
})

const ringLowOffset = computed(() => {
  const p = dataState.value.focusThisWeek.low.percent / 100
  return RING_CIRCUMFERENCE * (1 - p)
})

// Review Modal
const openReviewModal = (tp: WeakTopicItem) => {
  selectedTopicForReview.value = tp
  isReviewModalOpen.value = true
}

// Practice Quiz Modal
const startPracticeQuiz = async (topic?: WeakTopicItem) => {
  const targetTopic = topic || dataState.value.topics[0]
  if (!targetTopic) {
    triggerToast(isKm.value ? 'មិនមានប្រធានបទសម្រាប់អនុវត្តឡើយ' : 'No topic available for practice.')
    return
  }

  practiceLoading.value = true
  isPracticeModalOpen.value = true
  practiceQuestions.value = []
  practiceQuestionIndex.value = 0
  practiceSelectedAnswer.value = null
  practiceSubmitted.value = false
  practiceScore.value = 0

  try {
    const res = await fetch(`/student/ai-path/weak-topics/${targetTopic.id}/practice-quiz`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || props.csrfToken || '',
      },
    })
    const json = await res.json()
    if (json.ok && Array.isArray(json.questions) && json.questions.length > 0) {
      practiceQuestions.value = json.questions
    } else {
      triggerToast(json.message || (isKm.value ? 'មិនមានសំណួរក្នុងធនាគារសំណួរទេ' : 'No questions found in Question Bank.'))
      isPracticeModalOpen.value = false
    }
  } catch (err) {
    triggerToast(isKm.value ? 'មានបញ្ហាក្នុងការទាញយកសំណួរ' : 'Error loading practice drill.')
    isPracticeModalOpen.value = false
  } finally {
    practiceLoading.value = false
  }
}

const submitAnswer = () => {
  if (practiceSelectedAnswer.value === null) return
  practiceSubmitted.value = true
  if (practiceSelectedAnswer.value === 0 || practiceSelectedAnswer.value === '0') {
    practiceScore.value += 1
  }
}

const nextPracticeQuestion = () => {
  if (practiceQuestionIndex.value < practiceQuestions.value.length - 1) {
    practiceQuestionIndex.value += 1
    practiceSelectedAnswer.value = null
    practiceSubmitted.value = false
  } else {
    isPracticeModalOpen.value = false
    triggerToast(isKm.value
      ? `ការអនុវត្តបានបញ្ចប់! ពិន្ទុ៖ ${practiceScore.value}/${practiceQuestions.value.length}`
      : `Drill completed! Score: ${practiceScore.value}/${practiceQuestions.value.length}`)
  }
}

// Resources Modal
const openResourcesModal = async (topic?: WeakTopicItem) => {
  const targetTopic = topic || dataState.value.topics[0]
  if (!targetTopic) return

  currentResourceTopic.value = isKm.value ? targetTopic.title_kh : targetTopic.title_en
  isResourcesModalOpen.value = true
  resourcesLoading.value = true
  topicResourcesList.value = []

  try {
    const res = await fetch(`/student/ai-path/weak-topics/${targetTopic.id}/resources`, {
      headers: { 'Accept': 'application/json' },
    })
    const json = await res.json()
    if (json.ok && Array.isArray(json.resources)) {
      topicResourcesList.value = json.resources
    }
  } catch (err) {
    // Non-blocking
  } finally {
    resourcesLoading.value = false
  }
}

// Study Plan Activation
const handleStartStudyPlan = async () => {
  isActivatingPlan.value = true
  try {
    const res = await fetch('/student/ai-path/weak-topics/study-plan', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || props.csrfToken || '',
      },
    })
    const json = await res.json()
    if (json.ok && json.studyPlan) {
      dataState.value.studyPlan = json.studyPlan
      triggerToast(isKm.value ? 'ផែនការសិក្សាត្រូវបានបើកដំណើរការដោយជោគជ័យ!' : 'Study plan activated successfully!')
    } else {
      triggerToast(json.message || (isKm.value ? 'មិនអាចបង្កើតផែនការសិក្សាបានទេ' : 'Unable to activate study plan.'))
    }
  } catch (err) {
    triggerToast(isKm.value ? 'មានបញ្ហាក្នុងការភ្ជាប់ទៅកាន់ប្រព័ន្ធ' : 'Network error.')
  } finally {
    isActivatingPlan.value = false
  }
}

// Toggle Study Plan Item Done
const togglePlanItem = async (itemId: number) => {
  try {
    const res = await fetch(`/student/ai-path/weak-topics/study-plan/items/${itemId}`, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || props.csrfToken || '',
      },
    })
    const json = await res.json()
    if (json.ok && json.studyPlan) {
      dataState.value.studyPlan = json.studyPlan
    }
  } catch (err) {
    // Non-blocking
  }
}
</script>

<template>
  <StudentLayout
    :title="isKm ? 'ពិនិត្យឡើងវិញចំណុចខ្សោយ' : 'Weak Topics Review'"
    :breadcrumbs="[
      { label: isKm ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard', href: '/student/dashboard' },
      { label: isKm ? 'ការរៀនជាមួយ AI' : 'AI Learning', href: '/student/ai-path/recommended' },
      { label: isKm ? 'ពិនិត្យឡើងវិញចំណុចខ្សោយ' : 'Weak Topics Review' }
    ]"
  >
    <div class="space-y-6 pb-24">
      
      <!-- Toast Alert -->
      <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showToast"
          class="fixed bottom-6 right-6 z-50 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-purple-500/50 shadow-2xl text-slate-900 dark:text-white flex items-center gap-3 max-w-md"
        >
          <Sparkles class="w-5 h-5 text-purple-600 dark:text-purple-400 shrink-0" />
          <p class="text-xs text-slate-700 dark:text-slate-200 font-medium">{{ toastMessage }}</p>
        </div>
      </transition>

      <!-- 1. PAGE HEADER -->
      <div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
          <span>{{ isKm ? 'ពិនិត្យឡើងវិញចំណុចខ្សោយ' : 'Weak Topics Review' }}</span>
          <TrendingDown class="w-6 h-6 text-rose-500 shrink-0" />
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
          {{ isKm ? 'ផ្តោតលើចំណុចខ្សោយរបស់អ្នកដើម្បីពង្រឹងការយល់ដឹង និងបង្កើនលទ្ធផលសិក្សាពិតប្រាកដ។' : 'Focus on your weak topics to improve your understanding and boost your performance.' }}
        </p>
      </div>

      <!-- 2. TOP SECTION: 4 METRIC CARDS vs AI RECOMMENDATION -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        
        <!-- LEFT 8 COLS: 4 METRIC CARDS -->
        <div class="lg:col-span-8 grid grid-cols-2 sm:grid-cols-4 gap-4">
          
          <!-- Metric 1: Overall Weak Topics -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm flex flex-col justify-between space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ isKm ? 'ប្រធានបទខ្សោយសរុប' : 'Overall Weak Topics' }}</span>
              <div class="w-8 h-8 rounded-full bg-rose-500/10 text-rose-500 flex items-center justify-center border border-rose-500/20">
                <TrendingDown class="w-4 h-4" />
              </div>
            </div>
            <div>
              <div class="text-2xl font-black text-slate-900 dark:text-white">
                {{ dataState.hasData ? dataState.summary.totalTopics : '—' }}
                <span v-if="dataState.hasData" class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ isKm ? 'ប្រធានបទ' : 'topics' }}</span>
              </div>
              <p class="text-[11px] text-rose-500 font-semibold mt-0.5">
                {{ dataState.hasData ? (isKm ? 'ត្រូវការកែលម្អ' : 'Needs improvement') : (isKm ? 'គ្មានទិន្នន័យ' : 'No data') }}
              </p>
            </div>
          </div>

          <!-- Metric 2: Average Score -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm flex flex-col justify-between space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ isKm ? 'ពិន្ទុមធ្យមភាគ' : 'Average Score' }}</span>
              <div class="w-8 h-8 rounded-full bg-amber-500/10 text-amber-500 flex items-center justify-center border border-amber-500/20">
                <Target class="w-4 h-4" />
              </div>
            </div>
            <div>
              <div class="text-2xl font-black text-slate-900 dark:text-white">
                {{ dataState.hasData && dataState.summary.avgScore !== null ? `${dataState.summary.avgScore}%` : '—' }}
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ isKm ? 'ក្នុងចំណោមចំណុចខ្សោយ' : 'Across weak topics' }}</p>
              <div class="w-full h-1 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden mt-2">
                <div
                  class="h-full bg-rose-500 rounded-full"
                  :style="{ width: `${dataState.summary.avgScore || 0}%` }"
                ></div>
              </div>
            </div>
          </div>

          <!-- Metric 3: Recommended Focus Time -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm flex flex-col justify-between space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ isKm ? 'ពេលវេលាត្រូវផ្តោត' : 'Recommended Focus' }}</span>
              <div class="w-8 h-8 rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center border border-purple-500/20">
                <Clock class="w-4 h-4" />
              </div>
            </div>
            <div>
              <div class="text-2xl font-black text-slate-900 dark:text-white">
                {{ dataState.hasData && dataState.summary.recommendedFocusTime ? dataState.summary.recommendedFocusTime : '—' }}
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ isKm ? 'ក្នុងសប្តាហ៍នេះ' : 'This week' }}</p>
            </div>
          </div>

          <!-- Metric 4: Gap to Pass (No fake prophecy) -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm flex flex-col justify-between space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ isKm ? 'គម្លាតដល់ជាប់ (70%)' : 'Gap to Pass (70%)' }}</span>
              <div class="w-8 h-8 rounded-full bg-emerald-500/10 text-emerald-500 flex items-center justify-center border border-emerald-500/20">
                <CheckCircle2 class="w-4 h-4" />
              </div>
            </div>
            <div>
              <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                {{ dataState.hasData && dataState.summary.gapToPass !== null ? `+${dataState.summary.gapToPass} pts` : '—' }}
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ isKm ? 'ដើម្បីឈានដល់កម្រិតជាប់' : 'Points needed to reach 70%' }}</p>
            </div>
          </div>

        </div>

        <!-- RIGHT 4 COLS: AI RECOMMENDATION CARD -->
        <div class="lg:col-span-4 bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm flex flex-col justify-between relative overflow-hidden">
          <div class="space-y-3 z-10">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">{{ isKm ? 'ការណែនាំពី AI' : 'AI Recommendation' }}</h3>
              <span
                v-if="dataState.aiRecommendation?.isAiGenerated"
                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-600 dark:text-purple-300 border border-purple-500/30 font-mono"
              >
                AI
              </span>
            </div>
            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
              {{ isKm ? 'ផ្តោតលើចំណុចខ្សោយអាទិភាពខ្ពស់ជាមុន' : 'Focus on high priority topics first.' }}
            </p>
            <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed pr-14">
              {{ isKm ? (dataState.aiRecommendation?.text_km || 'សូមអនុវត្តតេស្តសាកល្បងដើម្បីឲ្យ AI វិភាគចំណុចខ្សោយរបស់អ្នក។') : (dataState.aiRecommendation?.text_en || 'Take quizzes so AI can analyze your weak topics.') }}
            </p>
          </div>

          <!-- Robot Avatar graphic -->
          <div class="absolute right-3 top-4 pointer-events-none opacity-90 hidden sm:block">
            <img
              :src="'/images/ai-robot-avatar.png'"
              alt="AI"
              class="w-14 h-14 rounded-2xl object-contain select-none shadow-md"
            />
          </div>

          <!-- Action Button -->
          <div class="pt-4 z-10">
            <Link
              href="/student/ai-tutor?prompt=Help+me+create+a+study+strategy+for+my+weak+topics"
              class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-600/30 flex items-center justify-center gap-2 transition-all"
            >
              <img :src="'/images/ai-robot-avatar.png'" alt="" class="w-4 h-4 rounded-sm object-contain shrink-0 select-none" />
              <span>{{ isKm ? 'សួរ AI អំពីផែនការសិក្សា' : 'Ask AI for Study Plan' }}</span>
            </Link>
          </div>
        </div>

      </div>

      <!-- 3. FILTER TABS & SORT CONTROLS -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
          <button
            @click="activeTab = 'all'"
            type="button"
            :class="[
              activeTab === 'all'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800',
              'px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            <span>● {{ isKm ? 'ប្រធានបទខ្សោយទាំងអស់' : 'All Weak Topics' }}</span>
            <span class="px-1.5 py-0.5 rounded-md bg-purple-950/80 text-purple-200 text-[10px] font-mono font-bold">{{ dataState.chips.all }}</span>
          </button>

          <button
            @click="activeTab = 'quiz'"
            type="button"
            :class="[
              activeTab === 'quiz'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800',
              'px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            <span>{{ isKm ? 'កម្រងសំណួរ (Quiz)' : 'Quiz Based' }}</span>
            <span class="px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-mono font-bold">{{ dataState.chips.quiz }}</span>
          </button>

          <button
            @click="activeTab = 'assessment'"
            type="button"
            :class="[
              activeTab === 'assessment'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800',
              'px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            <span>{{ isKm ? 'ការវាយតម្លៃ (Assessment)' : 'Assessment Based' }}</span>
            <span class="px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-mono font-bold">{{ dataState.chips.assessment }}</span>
          </button>

          <button
            @click="activeTab = 'course_content'"
            type="button"
            :class="[
              activeTab === 'course_content'
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30'
                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800',
              'px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            <span>{{ isKm ? 'មាតិកាវគ្គសិក្សា' : 'Course Content' }}</span>
            <span class="px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-mono font-bold">{{ dataState.chips.course }}</span>
          </button>
        </div>

        <!-- Sort Dropdown -->
        <div class="flex items-center gap-2 shrink-0">
          <span class="text-xs text-slate-500 dark:text-slate-400">{{ isKm ? 'តម្រៀបតាម៖' : 'Sort by:' }}</span>
          <select
            v-model="sortBy"
            class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-800 dark:text-white focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="score_asc">{{ isKm ? 'ពិន្ទុ (ទាប ទៅ ខ្ពស់)' : 'Score (Low to High)' }}</option>
            <option value="score_desc">{{ isKm ? 'ពិន្ទុ (ខ្ពស់ ទៅ ទាប)' : 'Score (High to Low)' }}</option>
            <option value="priority">{{ isKm ? 'អាទិភាព (ខ្ពស់ ទៅ ទាប)' : 'Priority (High to Low)' }}</option>
            <option value="recent">{{ isKm ? 'បានប្រឡងថ្មីៗ' : 'Recently Attempted' }}</option>
          </select>
        </div>

      </div>

      <!-- 4. MAIN 2-COLUMN SECTION -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT 8 COLS: WEAK TOPICS TABLE -->
        <div class="lg:col-span-8 bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
          
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                {{ isKm ? 'ចំណុចខ្សោយរបស់អ្នក' : 'Your Weak Topics' }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ isKm ? 'ប្រធានបទដែលអ្នកគួរផ្តោតលើដើម្បីបង្កើនលទ្ធផលសិក្សា។' : 'Topics you should focus on to improve your learning outcomes.' }}
              </p>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="!dataState.hasData || visibleTopics.length === 0" class="py-12 px-6 text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center mx-auto">
              <CheckCircle2 class="w-6 h-6" />
            </div>
            <h4 class="text-base font-bold text-slate-900 dark:text-white">
              {{ isKm ? 'មិនមានចំណុចខ្សោយត្រូវកែលម្អនៅឡើយទេ!' : 'No weak topics identified!' }}
            </h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
              {{ isKm ? 'អ្នកមិនទាន់មានប្រវត្តិប្រឡង ឬពិន្ទុរបស់អ្នកស្ថិតក្នុងកម្រិតល្អប្រសើរ (≥70%)។ សូមបន្តធ្វើ Quiz ដើម្បីវាស់ស្ទង់សមត្ថភាព។' : 'You either have no quiz attempts yet or all your topic scores are at or above 70%.' }}
            </p>
            <div class="pt-2">
              <Link
                href="/student/quizzes/practice"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md transition-all"
              >
                <span>{{ isKm ? 'ទៅកាន់កម្រងសំណួរ (Quizzes)' : 'Go to Practice Quizzes' }}</span>
                <ArrowRight class="w-4 h-4" />
              </Link>
            </div>
          </div>

          <!-- Table Content -->
          <div v-else class="space-y-3">
            
            <!-- Table Header (Desktop) -->
            <div class="hidden sm:grid grid-cols-12 gap-4 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-2 px-3">
              <div class="col-span-4">{{ isKm ? 'ប្រធានបទ' : 'Topic' }}</div>
              <div class="col-span-3">{{ isKm ? 'ទាក់ទងនឹង' : 'Related To' }}</div>
              <div class="col-span-2">{{ isKm ? 'ពិន្ទុ' : 'Your Score' }}</div>
              <div class="col-span-1">{{ isKm ? 'កាលបរិច្ឆេទ' : 'Attempted' }}</div>
              <div class="col-span-1 text-center">{{ isKm ? 'អាទិភាព' : 'Priority' }}</div>
              <div class="col-span-1 text-right">{{ isKm ? 'សកម្មភាព' : 'Action' }}</div>
            </div>

            <!-- Topic Rows -->
            <div
              v-for="(tp, idx) in visibleTopics"
              :key="tp.id"
              class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:border-purple-500/40 flex flex-col sm:grid sm:grid-cols-12 gap-4 items-start sm:items-center transition-all group"
            >
              <!-- Topic Column -->
              <div class="sm:col-span-4 flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-purple-600/10 text-purple-600 dark:text-purple-400 font-black text-xs flex items-center justify-center font-mono border border-purple-500/20 shrink-0">
                  #{{ idx + 1 }}
                </div>
                <div class="min-w-0">
                  <h4 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate">
                    {{ isKm ? tp.title_kh : tp.title_en }}
                  </h4>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-1">
                    {{ tp.subtitle }}
                  </p>
                </div>
              </div>

              <!-- Related To -->
              <div class="sm:col-span-3 text-xs text-slate-600 dark:text-slate-300">
                <p class="font-semibold text-slate-900 dark:text-white truncate">{{ tp.course }}</p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ tp.chapter }}</p>
              </div>

              <!-- Your Score -->
              <div class="sm:col-span-2 space-y-1 w-full sm:w-auto">
                <span
                  :class="[
                    tp.score < 40 ? 'text-rose-500' : tp.score < 60 ? 'text-amber-500' : 'text-emerald-500',
                    'text-xs font-black font-mono'
                  ]"
                >
                  {{ tp.score }}%
                </span>
                <div class="w-full sm:w-20 h-1 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                  <div
                    :class="[
                      tp.score < 40 ? 'bg-rose-500' : tp.score < 60 ? 'bg-amber-500' : 'bg-emerald-500',
                      'h-full rounded-full'
                    ]"
                    :style="{ width: `${tp.score}%` }"
                  ></div>
                </div>
              </div>

              <!-- Last Attempt -->
              <div class="sm:col-span-1 text-[10px] text-slate-500 dark:text-slate-400 whitespace-nowrap">
                {{ tp.lastAttempt }}
              </div>

              <!-- Priority -->
              <div class="sm:col-span-1 flex justify-start sm:justify-center">
                <span
                  :class="[
                    tp.priority === 'high'
                      ? 'bg-rose-500/10 text-rose-600 dark:text-rose-300 border-rose-500/30'
                      : tp.priority === 'medium'
                      ? 'bg-amber-500/10 text-amber-600 dark:text-amber-300 border-amber-500/30'
                      : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700',
                    'px-2 py-0.5 rounded-full text-[10px] font-bold border capitalize'
                  ]"
                >
                  ● {{ tp.priority }}
                </span>
              </div>

              <!-- Action: Review Now -->
              <div class="sm:col-span-1 flex items-center justify-end gap-1.5 w-full sm:w-auto">
                <button
                  @click="openReviewModal(tp)"
                  type="button"
                  :class="[
                    idx === 0
                      ? 'bg-purple-600 hover:bg-purple-500 text-white shadow-md shadow-purple-600/30'
                      : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:text-purple-600 dark:hover:text-white border border-slate-200 dark:border-slate-700',
                    'px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1 transition-all cursor-pointer whitespace-nowrap min-h-[44px] sm:min-h-0'
                  ]"
                >
                  <span>{{ isKm ? 'រំលឹកឡើងវិញ' : 'Review Now' }}</span>
                </button>
              </div>
            </div>

            <!-- Show More Button -->
            <div v-if="filteredTopics.length > 5" class="pt-2 text-center">
              <button
                @click="showMoreTopics = !showMoreTopics"
                type="button"
                class="text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-300 transition-colors cursor-pointer inline-flex items-center gap-1.5 py-1"
              >
                <span>{{ showMoreTopics ? (isKm ? 'បង្ហាញតិចជាងមុន' : 'Show Less Weak Topics') : (isKm ? 'បង្ហាញបន្ថែមទៀត' : 'Show More Weak Topics') }}</span>
                <ChevronDown :class="{ 'rotate-180': showMoreTopics }" class="w-4 h-4 transition-transform" />
              </button>
            </div>

          </div>

        </div>

        <!-- RIGHT 4 COLS: FOCUS THIS WEEK, QUICK ACTIONS & RECENT IMPROVEMENT -->
        <div class="lg:col-span-4 space-y-6">
          
          <!-- CARD 1: FOCUS THIS WEEK -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-4">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-3">
              {{ isKm ? 'ការផ្តោតក្នុងសប្តាហ៍នេះ' : 'Focus This Week' }}
            </h3>

            <div class="flex items-center justify-center pt-2">
              <div class="relative w-32 h-32 flex items-center justify-center">
                <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                  <circle cx="50" cy="50" r="38" stroke="currentColor" class="text-slate-200 dark:text-slate-800" stroke-width="8" fill="transparent" />
                  <!-- High Priority -->
                  <circle
                    cx="50"
                    cy="50"
                    r="38"
                    stroke="#f43f5e"
                    stroke-width="8"
                    :stroke-dasharray="RING_CIRCUMFERENCE"
                    :stroke-dashoffset="ringHighOffset"
                    fill="transparent"
                  />
                  <!-- Medium Priority -->
                  <circle
                    cx="50"
                    cy="50"
                    r="38"
                    stroke="#f59e0b"
                    stroke-width="8"
                    :stroke-dasharray="RING_CIRCUMFERENCE"
                    :stroke-dashoffset="ringMedOffset"
                    fill="transparent"
                    transform="rotate(120 50 50)"
                  />
                  <!-- Low Priority -->
                  <circle
                    cx="50"
                    cy="50"
                    r="38"
                    stroke="#10b981"
                    stroke-width="8"
                    :stroke-dasharray="RING_CIRCUMFERENCE"
                    :stroke-dashoffset="ringLowOffset"
                    fill="transparent"
                    transform="rotate(240 50 50)"
                  />
                </svg>

                <div class="absolute flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-slate-900 dark:text-white leading-none">
                    {{ dataState.focusThisWeek.formattedTotal }}
                  </span>
                  <span class="text-[9px] text-slate-500 dark:text-slate-400 uppercase font-semibold mt-1">
                    {{ isKm ? 'ពេលសរុប' : 'Total Focus Time' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Priority Breakdown (Single Source of Truth) -->
            <div class="space-y-2 text-xs pt-1 border-t border-slate-200 dark:border-slate-800">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                  <span class="text-slate-600 dark:text-slate-300">{{ isKm ? 'អាទិភាពខ្ពស់' : 'High Priority' }}</span>
                </div>
                <span class="font-bold text-slate-900 dark:text-white">
                  {{ dataState.focusThisWeek.high.formatted }} ({{ dataState.focusThisWeek.high.percent }}%)
                </span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                  <span class="text-slate-600 dark:text-slate-300">{{ isKm ? 'អាទិភាពមធ្យម' : 'Medium Priority' }}</span>
                </div>
                <span class="font-bold text-slate-900 dark:text-white">
                  {{ dataState.focusThisWeek.medium.formatted }} ({{ dataState.focusThisWeek.medium.percent }}%)
                </span>
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                  <span class="text-slate-600 dark:text-slate-300">{{ isKm ? 'អាទិភាពទាប' : 'Low Priority' }}</span>
                </div>
                <span class="font-bold text-slate-900 dark:text-white">
                  {{ dataState.focusThisWeek.low.formatted }} ({{ dataState.focusThisWeek.low.percent }}%)
                </span>
              </div>
            </div>
          </div>

          <!-- CARD 2: QUICK ACTIONS -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-3">
              {{ isKm ? 'សកម្មភាពរហ័ស' : 'Quick Actions' }}
            </h3>

            <div class="space-y-2 text-xs">
              <!-- Action 1: Practice Quiz -->
              <button
                @click="startPracticeQuiz()"
                type="button"
                class="w-full p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-purple-500/40 flex items-center justify-between transition-all cursor-pointer group text-left"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center">
                    <HelpCircle class="w-4 h-4" />
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors">
                      {{ isKm ? 'កម្រងសំណួរអនុវត្ត (Practice Drill)' : 'Practice Quiz' }}
                    </h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">
                      {{ isKm ? 'ធ្វើតេស្ត 5 សំណួរពីធនាគារសំណួរ' : 'Generate quiz on weak topics' }}
                    </p>
                  </div>
                </div>
                <span class="text-slate-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-transform">›</span>
              </button>

              <!-- Action 2: AI Explain Topics -->
              <Link
                href="/student/ai-tutor?prompt=Explain+the+most+common+mistakes+in+my+weak+topics+with+examples"
                class="w-full p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-purple-500/40 flex items-center justify-between transition-all group"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center">
                    <Sparkles class="w-4 h-4" />
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors">
                      {{ isKm ? 'AI ពន្យល់ប្រធានបទ' : 'AI Explain Topics' }}
                    </h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">
                      {{ isKm ? 'សួរ AI អំពីមូលដ្ឋាននិងកំហុសទូទៅ' : 'Get AI explanation for topics' }}
                    </p>
                  </div>
                </div>
                <span class="text-slate-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-transform">›</span>
              </Link>

              <!-- Action 3: Study Resources -->
              <button
                @click="openResourcesModal()"
                type="button"
                class="w-full p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 hover:bg-slate-100 dark:hover:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-purple-500/40 flex items-center justify-between transition-all cursor-pointer group text-left"
              >
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                    <BookOpen class="w-4 h-4" />
                  </div>
                  <div>
                    <h4 class="font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors">
                      {{ isKm ? 'ឯកសារសិក្សាភ្ជាប់មេរៀន' : 'Study Resources' }}
                    </h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">
                      {{ isKm ? 'ឯកសារ PDF និងស្លាយពិតប្រាកដ' : 'View real course resources' }}
                    </p>
                  </div>
                </div>
                <span class="text-slate-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-transform">›</span>
              </button>
            </div>
          </div>

          <!-- CARD 3: RECENT IMPROVEMENT (Hidden if no real 2-attempt improvements) -->
          <div v-if="dataState.recentImprovements.length > 0" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                {{ isKm ? 'ការរីកចម្រើនថ្មីៗ' : 'Recent Improvement' }}
              </h3>
            </div>

            <div class="space-y-3">
              <div
                v-for="imp in dataState.recentImprovements"
                :key="imp.topic_id"
                class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xs shrink-0">
                    ✓
                  </div>
                  <div class="min-w-0">
                    <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ imp.title }}</h4>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">
                      {{ isKm ? `កើនពី ${imp.first_score}% ទៅ ${imp.last_score}%` : `Improved from ${imp.first_score}% to ${imp.last_score}%` }}
                    </p>
                  </div>
                </div>
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                  +{{ imp.gain }}%
                </span>
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- 5. BOTTOM CARD: STUDY PLAN FOR YOU -->
      <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-5">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-purple-600/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 flex items-center justify-center">
              <Calendar class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                {{ isKm ? 'ផែនការសិក្សាផ្ទាល់ខ្លួនសម្រាប់អ្នក' : 'Study Plan for You' }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ isKm ? 'រៀបចំកាលវិភាគសិក្សាដើម្បីលុបបំបាត់ចំណុចខ្សោយ' : 'Personalized study plan to improve your weak topics' }}
              </p>
            </div>
          </div>

          <button
            v-if="!dataState.studyPlan"
            @click="handleStartStudyPlan"
            :disabled="isActivatingPlan || !dataState.hasData"
            type="button"
            class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md flex items-center gap-1.5 transition-all cursor-pointer shrink-0 disabled:opacity-50"
          >
            <span>{{ isKm ? 'ចាប់ផ្តើមផែនការសិក្សា' : 'Start Study Plan' }}</span>
            <ArrowRight class="w-4 h-4" />
          </button>
          <div v-else class="text-xs font-bold text-purple-600 dark:text-purple-300">
            {{ isKm ? `វឌ្ឍនភាព៖ ${dataState.studyPlan.completedCount}/${dataState.studyPlan.totalDays} ថ្ងៃ (${dataState.studyPlan.percent}%)` : `Progress: ${dataState.studyPlan.completedCount}/${dataState.studyPlan.totalDays} Days (${dataState.studyPlan.percent}%)` }}
          </div>
        </div>

        <!-- Plan Items -->
        <div v-if="dataState.studyPlan && dataState.studyPlan.items.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
          <div
            v-for="item in dataState.studyPlan.items"
            :key="item.id"
            @click="togglePlanItem(item.id)"
            :class="[
              item.is_done
                ? 'border-emerald-500/50 bg-emerald-500/5'
                : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950',
              'p-4 rounded-2xl border space-y-2 cursor-pointer transition-all hover:border-purple-500/50'
            ]"
          >
            <div class="flex items-center justify-between text-[11px]">
              <span class="text-purple-600 dark:text-purple-300 font-black font-mono uppercase">
                Day {{ item.day_index }}
              </span>
              <span class="text-slate-500 dark:text-slate-400 font-mono">⏱ {{ item.minutes }} min</span>
            </div>
            <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">
              {{ item.topic }}
            </h4>
            <div class="flex items-center justify-between text-[10px] pt-1">
              <span :class="item.is_done ? 'text-emerald-500 font-semibold' : 'text-slate-500'">
                {{ item.is_done ? (isKm ? '✓ បានបញ្ចប់' : '✓ Completed') : (isKm ? '● កំពុងរង់ចាំ' : '● Pending') }}
              </span>
              <span class="capitalize text-slate-400">{{ item.kind }}</span>
            </div>
          </div>
        </div>

        <div v-else class="text-xs text-slate-500 dark:text-slate-400 italic">
          {{ isKm ? 'ចុច «ចាប់ផ្តើមផែនការសិក្សា» ដើម្បីបង្កើតកាលវិភាគសិក្សា 4 ថ្ងៃដោយផ្អែកលើចំណុចខ្សោយរបស់អ្នក។' : 'Click "Start Study Plan" to generate an actionable 4-day revision schedule based on your weak topics.' }}
        </div>

      </div>

    </div>

    <!-- 1. TOPIC REVIEW MODAL -->
    <div
      v-if="isReviewModalOpen && selectedTopicForReview"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isReviewModalOpen = false"
    >
      <div
        class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isReviewModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 dark:hover:text-white text-sm cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>

        <div class="flex items-start gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 font-black text-sm flex items-center justify-center border border-purple-500/20 shrink-0">
            📖
          </div>
          <div>
            <span
              :class="[
                selectedTopicForReview.priority === 'high' ? 'text-rose-500' : 'text-amber-500',
                'text-[10px] font-bold uppercase tracking-wider'
              ]"
            >
              ● {{ selectedTopicForReview.priority }} Priority Review
            </span>
            <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
              {{ isKm ? selectedTopicForReview.title_kh : selectedTopicForReview.title_en }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ selectedTopicForReview.course }} • {{ selectedTopicForReview.chapter }}
            </p>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 grid grid-cols-3 gap-3 text-center">
          <div>
            <span class="text-[10px] text-slate-400 uppercase">{{ isKm ? 'ពិន្ទុបច្ចុប្បន្ន' : 'Current Score' }}</span>
            <p class="text-base font-black text-rose-500 font-mono">{{ selectedTopicForReview.score }}%</p>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 uppercase">{{ isKm ? 'គោលដៅ' : 'Target' }}</span>
            <p class="text-base font-black text-emerald-500 font-mono">70%+</p>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 uppercase">{{ isKm ? 'ពេលណែនាំ' : 'Focus Time' }}</span>
            <p class="text-base font-black text-slate-900 dark:text-white font-mono">{{ selectedTopicForReview.recommendedMins }}m</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center gap-2 pt-2">
          <Link
            :href="selectedTopicForReview.lessonHref"
            class="w-full sm:flex-1 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md flex items-center justify-center gap-1.5 transition-all text-center"
          >
            <span>{{ isKm ? 'បើកមេរៀនពិត' : 'Open Exact Lesson' }}</span>
          </Link>
          <button
            @click="isReviewModalOpen = false; startPracticeQuiz(selectedTopicForReview)"
            type="button"
            class="w-full sm:flex-1 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-purple-600 dark:text-purple-300 font-bold text-xs flex items-center justify-center gap-1.5 transition-all cursor-pointer"
          >
            <span>{{ isKm ? 'អនុវត្តតេស្ត (Practice)' : 'Practice Drill' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 2. PRACTICE QUIZ MODAL -->
    <div
      v-if="isPracticeModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isPracticeModalOpen = false"
    >
      <div
        class="relative w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isPracticeModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 dark:hover:text-white text-sm cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>

        <div v-if="practiceLoading" class="py-12 text-center text-xs text-slate-500">
          {{ isKm ? 'កំពុងទាញយកសំណួរពី Question Bank...' : 'Loading questions from Question Bank...' }}
        </div>

        <div v-else-if="practiceQuestions.length > 0" class="space-y-4">
          <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
              {{ isKm ? 'កម្រងសំណួរអនុវត្ត' : 'Practice Drill' }} ({{ practiceQuestionIndex + 1 }}/{{ practiceQuestions.length }})
            </span>
            <span class="text-xs text-purple-600 dark:text-purple-400 font-bold font-mono">
              Score: {{ practiceScore }}
            </span>
          </div>

          <div class="space-y-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
              {{ practiceQuestions[practiceQuestionIndex].question }}
            </h3>

            <div class="space-y-2">
              <button
                v-for="(opt, oIdx) in (practiceQuestions[practiceQuestionIndex].options || [])"
                :key="oIdx"
                @click="!practiceSubmitted && (practiceSelectedAnswer = oIdx)"
                :disabled="practiceSubmitted"
                :class="[
                  practiceSelectedAnswer === oIdx
                    ? 'border-purple-500 bg-purple-500/10 text-purple-600 dark:text-purple-300'
                    : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300',
                  'w-full p-3 rounded-xl border text-left text-xs font-semibold flex items-center justify-between cursor-pointer'
                ]"
              >
                <span>{{ opt }}</span>
              </button>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              v-if="!practiceSubmitted"
              @click="submitAnswer"
              :disabled="practiceSelectedAnswer === null"
              type="button"
              class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-md cursor-pointer disabled:opacity-50"
            >
              {{ isKm ? 'ផ្ទៀងផ្ទាត់ចម្លើយ' : 'Check Answer' }}
            </button>
            <button
              v-else
              @click="nextPracticeQuestion"
              type="button"
              class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold shadow-md cursor-pointer"
            >
              {{ practiceQuestionIndex < practiceQuestions.length - 1 ? (isKm ? 'សំណួរបន្ទាប់ →' : 'Next Question →') : (isKm ? 'បញ្ចប់' : 'Finish Drill') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. STUDY RESOURCES MODAL -->
    <div
      v-if="isResourcesModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
      @click="isResourcesModalOpen = false"
    >
      <div
        class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5"
        @click.stop
      >
        <button
          @click="isResourcesModalOpen = false"
          class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 dark:hover:text-white text-sm cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
            <BookOpen class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ isKm ? 'ឯកសារសិក្សាពិត' : 'Real Study Resources' }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ currentResourceTopic }}</p>
          </div>
        </div>

        <div v-if="resourcesLoading" class="py-8 text-center text-xs text-slate-500">
          {{ isKm ? 'កំពុងទាញយកឯកសារ...' : 'Loading resources...' }}
        </div>

        <div v-else-if="topicResourcesList.length > 0" class="space-y-2 text-xs">
          <a
            v-for="res in topicResourcesList"
            :key="res.id"
            :href="res.url || '#'"
            target="_blank"
            class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:border-purple-500/40 flex items-center justify-between text-slate-700 dark:text-slate-300 hover:text-purple-600 transition-all"
          >
            <div class="flex items-center gap-2.5 truncate">
              <span class="text-purple-500">📄</span>
              <span class="truncate">{{ res.title }}</span>
            </div>
            <ExternalLink class="w-3.5 h-3.5 text-slate-400 shrink-0" />
          </a>
        </div>

        <div v-else class="py-6 text-center text-xs text-slate-400">
          {{ isKm ? 'មិនមានឯកសារបន្ថែមសម្រាប់ប្រធានបទនេះទេ។' : 'No downloadable resources attached to this topic yet.' }}
        </div>
      </div>
    </div>

  </StudentLayout>
</template>
