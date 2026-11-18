<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  BookOpen,
  Target,
  CheckCircle2,
  Compass,
  Clock,
  CheckSquare,
  HelpCircle,
  TrendingUp,
  Play,
  Eye,
  ChevronDown,
  ArrowRight,
  Pencil,
  X,
  AlertCircle,
  RotateCcw,
  Sparkles,
  List,
  Map as MapIcon,
  ExternalLink,
  FileText,
} from 'lucide-vue-next'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { i18n } from '@/Services/i18n'

interface RoadmapCourse {
  id: number
  stageId: number
  stageNumber: number
  stageTitle_en: string
  stageTitle_km: string
  code: string
  title_en: string
  title_km: string
  desc_en: string
  desc_km: string
  level: string
  thumbnail: string | null
  lessonsCount: number
  doneLessons: number
  durationSeconds: number
  duration: string
  progress: number
  state: 'completed' | 'in_progress' | 'not_started' | 'not_enrolled' | 'coming_soon'
  isEnrolled: boolean
  isRequired: boolean
  actionType: 'continue' | 'start' | 'review' | 'view_details' | 'coming_soon'
  actionHref: string | null
  overviewHref: string
  browseHref: string
}

interface StageResource {
  id: string
  title: string
  type: string
  course: string
  url: string
  external: boolean
}

interface RoadmapStage {
  id: number
  stageNumber: number
  title_en: string
  title_km: string
  desc_en: string
  desc_km: string
  status: 'COMPLETED' | 'CURRENT' | 'RECOMMENDED_ORDER'
  isCompleted: boolean
  completedCount: number
  totalCount: number
  doneLessons: number
  totalLessons: number
  progress: number
  courses: RoadmapCourse[]
  resources: StageResource[]
}

interface PublishedPathOption {
  id: number
  slug: string
  title_en: string
  title_km: string
  desc_en: string
  desc_km: string
  default_target_months: number
  stages_count: number
  courses_count: number
}

interface WhyFactor {
  key: string
  label_en: string
  label_km: string
}

interface RoadmapSkill {
  id: number
  name_en: string
  name_km: string
}

interface RoadmapRecommendation {
  courseId: number | null
  courseTitle_en: string
  courseTitle_km: string
  isAiGenerated: boolean
  badge_en: string
  badge_km: string
  body_en: string
  body_km: string
  actionLabel_en: string
  actionLabel_km: string
  askAiHref: string
}

interface RoadmapPayload {
  hasPublishedPaths: boolean
  hasGoal: boolean
  goal: {
    pathId: number
    title_en: string
    title_km: string
    desc_en: string
    desc_km: string
    targetMonths: number
    weeklyHours: number
    startedAtIso: string
    targetDate_en: string
    targetDate_km: string
    doneLessons: number
    totalLessons: number
  } | null
  overallProgress: number
  counts: {
    completed: number
    inProgress: number
    upcoming: number
    total: number
  }
  stages: RoadmapStage[]
  currentStageIndex: number
  currentStageId: number | null
  nextUp: RoadmapCourse | null
  skills: RoadmapSkill[]
  whyFactors: WhyFactor[]
  recommendation: RoadmapRecommendation | null
  publishedPaths: PublishedPathOption[]
}

const props = defineProps<{
  roadmap?: RoadmapPayload
  csrfToken?: string
}>()

const page = usePage()
const isKm = computed(() => i18n.locale.value === 'km')

const emptyRoadmapFallback = (): RoadmapPayload => ({
  hasPublishedPaths: false,
  hasGoal: false,
  goal: null,
  overallProgress: 0,
  counts: { completed: 0, inProgress: 0, upcoming: 0, total: 0 },
  stages: [],
  currentStageIndex: 0,
  currentStageId: null,
  nextUp: null,
  skills: [],
  whyFactors: [],
  recommendation: null,
  publishedPaths: [],
})

const resolveInitialRoadmap = (): RoadmapPayload => {
  return props.roadmap || ((page.props as any)?.roadmap as RoadmapPayload | undefined) || emptyRoadmapFallback()
}

const roadmapState = ref<RoadmapPayload>(resolveInitialRoadmap())
const viewMode = ref<'roadmap' | 'list'>('roadmap')
const selectedStageIndex = ref<number>(roadmapState.value.currentStageIndex || 0)
const isResourcesOpen = ref(false)
const showAllSkills = ref(false)

// Goal modal & API state
const isEditGoalOpen = ref(false)
const isSavingGoal = ref(false)
const isLoadingRoadmap = ref(false)
const fetchError = ref('')
const goalFormError = ref('')
const editGoalForm = ref({
  path_id: 0,
  target_months: 6,
  weekly_hours: 10,
})

// Toast state
const showToast = ref(false)
const toastMessage = ref('')
const toastIsError = ref(false)
let toastTimer: ReturnType<typeof setTimeout> | null = null

const RING_RADIUS = 48
const RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS

const donutDashOffset = computed(() => {
  const pct = Math.max(0, Math.min(100, roadmapState.value.overallProgress || 0))
  return RING_CIRCUMFERENCE - (RING_CIRCUMFERENCE * pct) / 100
})

watch(
  () => [props.roadmap, (page.props as any)?.roadmap],
  () => {
    const incoming = resolveInitialRoadmap()
    roadmapState.value = incoming
    if (selectedStageIndex.value >= incoming.stages.length) {
      selectedStageIndex.value = Math.max(0, incoming.currentStageIndex || 0)
    }
  },
  { deep: true }
)

const activeStage = computed<RoadmapStage | null>(() => {
  const list = roadmapState.value.stages || []
  if (!list.length) return null
  return list[selectedStageIndex.value] || list[0] || null
})

const visibleSkills = computed(() => {
  const list = roadmapState.value.skills || []
  return showAllSkills.value ? list : list.slice(0, 8)
})

const labels = computed(() => isKm.value
  ? {
      pageTitle: 'ផែនទីបង្ហាញផ្លូវណែនាំ',
      pageSubtitle: 'ផែនទីសិក្សាតាមដំណាក់កាលពីវគ្គសិក្សាពិតក្នុងប្រព័ន្ធ SPI',
      dashboard: 'ផ្ទាំងគ្រប់គ្រង',
      aiLearning: 'ការរៀនជាមួយ AI',
      yourGoalLabel: 'គោលដៅសិក្សារបស់អ្នក',
      targetCompletion: 'គោលដៅបញ្ចប់៖',
      monthsUnit: 'ខែ',
      weeklyStudyTime: 'ពេលវេលាសិក្សា៖',
      hoursPerWeek: 'ម៉ោង/សប្តាហ៍',
      editGoal: 'កែប្រែគោលដៅ',
      setGoal: 'កំណត់គោលដៅសិក្សា',
      overallLabel: 'សរុប',
      completedLabel: 'បានបញ្ចប់',
      inProgressLabel: 'កំពុងសិក្សា',
      upcomingLabel: 'បន្ទាប់',
      totalLabel: 'វគ្គសរុប',
      coursesUnit: 'វគ្គ',
      whyRoadmapTitle: 'ហេតុអ្វីបានជាមានផែនទីនេះ?',
      whyRoadmapSub: 'រៀបចំផ្អែកលើទិន្នន័យពិតរបស់អ្នក៖',
      roadmapTitle: 'ផែនទីបង្ហាញផ្លូវសិក្សា',
      viewBy: 'បង្ហាញតាម៖',
      viewRoadmap: 'ផែនទី',
      viewList: 'បញ្ជី',
      stagePrefix: 'ដំណាក់កាល',
      recommendedOrder: 'ណែនាំតាមលំដាប់',
      currentStageCourses: 'វគ្គសិក្សាក្នុងដំណាក់កាលនេះ',
      currentStageHint: 'សិក្សាវគ្គទាំងនេះតាមលំដាប់ដើម្បីឈានទៅដំណាក់កាលបន្ទាប់',
      stageProgress: 'វឌ្ឍនភាព',
      lessonsUnit: 'មេរៀន',
      continueBtn: 'បន្តការសិក្សា',
      startCourseBtn: 'ចាប់ផ្តើមរៀន',
      reviewBtn: 'រំលឹកឡើងវិញ',
      viewDetailsBtn: 'មើលព័ត៌មានលម្អិត',
      comingSoonBtn: 'មិនទាន់មានមេរៀន',
      showResources: 'បង្ហាញឯកសារជំនួយសម្រាប់ដំណាក់កាលនេះ',
      hideResources: 'លាក់ឯកសារជំនួយ',
      nextUpTitle: 'ជំហានបន្ទាប់',
      nextUpContinueHint: 'បន្តពីមេរៀនដែលអ្នកបានរៀនចុងក្រោយ',
      nextUpStartHint: 'ចាប់ផ្តើមមេរៀនដំបូងក្នុងវគ្គនេះ',
      nextUpBrowseHint: 'ពិនិត្យព័ត៌មានវគ្គ និងចុះឈ្មោះចូលរៀន',
      skillsTitle: 'ជំនាញដែលអ្នកនឹងទទួលបាន',
      viewAllSkills: 'មើលជំនាញទាំងអស់',
      collapseSkills: 'បង្ហាញតិច',
      tipsTitle: 'គន្លឹះសិក្សាប្រសិទ្ធភាព',
      tip1Title: 'រក្សាភាពទៀងទាត់',
      tip1Desc: 'សិក្សារៀងរាល់ថ្ងៃតាមម៉ោងដែលបានកំណត់',
      tip2Title: 'អនុវត្តជាប្រចាំ',
      tip2Desc: 'បំពេញលំហាត់ និងកម្រងសំណួរតាមមេរៀន',
      tip3Title: 'សួរ AI ពេលមានចម្ងល់',
      tip3Desc: 'ប្រើជំនួយការសិក្សា AI ដើម្បីពន្យល់មេរៀន',
      tip4Title: 'តាមដានវឌ្ឍនភាព',
      tip4Desc: 'រំលឹកឡើងវិញ និងកែលម្អចំណុចខ្សោយ',
      noPathsTitle: 'មិនទាន់មានផែនទីបង្ហាញផ្លូវដែលបានផ្សាយទេ',
      noPathsDesc: 'បច្ចុប្បន្នប្រព័ន្ធមិនទាន់មានផែនទីសិក្សាផ្លូវការទេ។ អ្នកអាចស្វែងរកវគ្គសិក្សាក្នុងកាតាឡុក ឬសាកសួរ AI Tutor បាន។',
      noGoalTitle: 'សូមជ្រើសរើសផែនទីគោលដៅសិក្សារបស់អ្នក',
      noGoalDesc: 'ជ្រើសរើសគន្លងសិក្សាខាងក្រោម ហើយកំណត់រយៈពេលសិក្សា ដើម្បីតាមដានវឌ្ឍនភាពពិតរបស់អ្នក។',
      emptyCoursesTitle: 'ផែនទីនេះមិនទាន់មានវគ្គសិក្សាដែលបានផ្សាយទេ',
      emptyCoursesDesc: 'សូមប្តូរទៅជ្រើសរើសគោលដៅផែនទីផ្សេងទៀត។',
      browseCatalog: 'ស្វែងរកវគ្គសិក្សា',
      askAi: 'សួរ AI',
      retry: 'ព្យាយាមម្ដងទៀត',
      modalTitle: 'កំណត់គោលដៅសិក្សា',
      modalSub: 'ជ្រើសរើសផ្លូវសិក្សាពិត និងកាលវិភាគប្រចាំសប្តាហ៍',
      fieldPath: 'ផ្លូវសិក្សា (Learning Path)',
      fieldMonths: 'រយៈពេលគោលដៅ (១ – ៣៦ ខែ)',
      fieldHours: 'ម៉ោងសិក្សាក្នុងមួយសប្តាហ៍ (១ – ៦០ ម៉ោង)',
      cancel: 'បោះបង់',
      save: 'រក្សាទុកគោលដៅ',
      saving: 'កំពុងរក្សាទុក...',
      saveSuccess: 'បានរក្សាទុកគោលដៅសិក្សាដោយជោគជ័យ!',
      loadError: 'មិនអាចទាញយកទិន្នន័យផែនទីបានទេ។ សូមសាកល្បងម្ដងទៀត។',
      beginner: 'កម្រិតដំបូង',
      intermediate: 'កម្រិតកណ្តាល',
      advanced: 'កម្រិតខ្ពស់',
    }
  : {
      pageTitle: 'Recommended Roadmap',
      pageSubtitle: 'Structured stage-by-stage roadmap built from real published SPI courses.',
      dashboard: 'Dashboard',
      aiLearning: 'AI Learning',
      yourGoalLabel: 'Your Learning Goal',
      targetCompletion: 'Target Completion:',
      monthsUnit: 'months',
      weeklyStudyTime: 'Weekly Study Time:',
      hoursPerWeek: 'hours/week',
      editGoal: 'Edit Goal',
      setGoal: 'Set Learning Goal',
      overallLabel: 'Overall',
      completedLabel: 'Completed',
      inProgressLabel: 'In Progress',
      upcomingLabel: 'Upcoming',
      totalLabel: 'Total Courses',
      coursesUnit: 'courses',
      whyRoadmapTitle: 'Why this roadmap?',
      whyRoadmapSub: 'Grounded in your real database records:',
      roadmapTitle: 'Your Recommended Roadmap',
      viewBy: 'View by:',
      viewRoadmap: 'Roadmap',
      viewList: 'List',
      stagePrefix: 'Stage',
      recommendedOrder: 'Recommended Order',
      currentStageCourses: 'Stage Courses',
      currentStageHint: 'Follow stages in order for smooth progression',
      stageProgress: 'Stage Progress',
      lessonsUnit: 'lessons',
      continueBtn: 'Continue',
      startCourseBtn: 'Start Course',
      reviewBtn: 'Review',
      viewDetailsBtn: 'View Details',
      comingSoonBtn: 'Coming Soon',
      showResources: 'Show Recommended Resources for This Stage',
      hideResources: 'Hide Resources',
      nextUpTitle: 'Next Up',
      nextUpContinueHint: 'Continue where you left off',
      nextUpStartHint: 'Start the first lesson in this course',
      nextUpBrowseHint: 'View course syllabus and enroll',
      skillsTitle: 'Skills You Will Gain',
      viewAllSkills: 'View All Skills',
      collapseSkills: 'Show Fewer',
      tipsTitle: 'Roadmap Tips',
      tip1Title: 'Stay consistent',
      tip1Desc: 'Study regularly toward your weekly hour target',
      tip2Title: 'Practice regularly',
      tip2Desc: 'Complete lesson exercises and quizzes',
      tip3Title: 'Ask AI for help',
      tip3Desc: 'Use AI Study Assistant whenever you get stuck',
      tip4Title: 'Track your progress',
      tip4Desc: 'Review completed modules and weak topics',
      noPathsTitle: 'No Published Roadmaps Available Yet',
      noPathsDesc: 'No structured learning paths are published yet. Browse the catalog or ask AI Tutor for study guidance.',
      noGoalTitle: 'Choose Your Learning Roadmap',
      noGoalDesc: 'Select a published track below and set your weekly commitment to start tracking real progress.',
      emptyCoursesTitle: 'No Published Courses in This Path Yet',
      emptyCoursesDesc: 'Please choose another published learning path using Edit Goal.',
      browseCatalog: 'Browse Catalog',
      askAi: 'Ask AI',
      retry: 'Retry',
      modalTitle: 'Configure Learning Goal',
      modalSub: 'Choose a published path and weekly study commitment',
      fieldPath: 'Learning Path',
      fieldMonths: 'Target Duration (1–36 months)',
      fieldHours: 'Weekly Study Hours (1–60 hours)',
      cancel: 'Cancel',
      save: 'Save Goal',
      saving: 'Saving...',
      saveSuccess: 'Learning goal saved successfully!',
      loadError: 'Unable to load roadmap data. Please try again.',
      beginner: 'Beginner',
      intermediate: 'Intermediate',
      advanced: 'Advanced',
    })

const triggerToast = (msg: string, isError = false) => {
  toastMessage.value = msg
  toastIsError.value = isError
  showToast.value = true
  if (toastTimer) clearTimeout(toastTimer)
  toastTimer = setTimeout(() => {
    showToast.value = false
  }, 3500)
}

const buildHeaders = (): Record<string, string> => {
  const tokenProp = props.csrfToken || ((page.props as any)?.csrfToken as string | undefined) || ''
  const metaToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': tokenProp || metaToken,
  }
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
  if (match && match[1]) {
    headers['X-XSRF-TOKEN'] = decodeURIComponent(match[1])
  }
  return headers
}

const syncUrlParams = () => {
  if (typeof window === 'undefined') return
  const params = new URLSearchParams(window.location.search)
  params.set('view', viewMode.value)
  if (activeStage.value) {
    params.set('stage', String(activeStage.value.stageNumber))
  } else {
    params.delete('stage')
  }
  const newUrl = `${window.location.pathname}?${params.toString()}`
  window.history.replaceState({}, '', newUrl)
}

const setViewMode = (mode: 'roadmap' | 'list') => {
  viewMode.value = mode
  syncUrlParams()
}

const selectStage = (idx: number) => {
  if (idx < 0 || idx >= (roadmapState.value.stages || []).length) return
  selectedStageIndex.value = idx
  isResourcesOpen.value = false
  syncUrlParams()
}

const handleStageKeydown = (e: KeyboardEvent, idx: number) => {
  const max = (roadmapState.value.stages || []).length - 1
  if (max < 0) return
  if (e.key === 'ArrowRight') {
    e.preventDefault()
    selectStage(idx >= max ? 0 : idx + 1)
  } else if (e.key === 'ArrowLeft') {
    e.preventDefault()
    selectStage(idx <= 0 ? max : idx - 1)
  }
}

const openGoalModal = (preselectPathId?: number) => {
  goalFormError.value = ''
  const paths = roadmapState.value.publishedPaths || []
  const currentGoal = roadmapState.value.goal
  const pickedPathId = preselectPathId || currentGoal?.pathId || paths[0]?.id || 0
  const pickedPath = paths.find(p => p.id === pickedPathId)
  editGoalForm.value = {
    path_id: pickedPathId,
    target_months: currentGoal && currentGoal.pathId === pickedPathId
      ? currentGoal.targetMonths
      : (pickedPath?.default_target_months || 6),
    weekly_hours: currentGoal ? currentGoal.weeklyHours : 10,
  }
  isEditGoalOpen.value = true
}

const saveGoal = async () => {
  if (isSavingGoal.value) return
  goalFormError.value = ''
  isSavingGoal.value = true
  try {
    const res = await fetch('/student/ai-path/goal', {
      method: 'PUT',
      credentials: 'same-origin',
      headers: buildHeaders(),
      body: JSON.stringify({
        path_id: Number(editGoalForm.value.path_id),
        target_months: Number(editGoalForm.value.target_months),
        weekly_hours: Number(editGoalForm.value.weekly_hours),
      }),
    })
    const data = await res.json().catch(() => null)
    if (!res.ok || !data?.ok || !data?.roadmap) {
      const firstErr = data?.errors
        ? (Object.values(data.errors).flat()[0] as string | undefined)
        : undefined
      goalFormError.value = firstErr || data?.message || labels.value.loadError
      return
    }
    roadmapState.value = data.roadmap
    selectedStageIndex.value = data.roadmap.currentStageIndex || 0
    isEditGoalOpen.value = false
    syncUrlParams()
    triggerToast(labels.value.saveSuccess, false)
  } catch {
    goalFormError.value = labels.value.loadError
  } finally {
    isSavingGoal.value = false
  }
}

const reloadRoadmap = async () => {
  if (isLoadingRoadmap.value) return
  fetchError.value = ''
  isLoadingRoadmap.value = true
  try {
    const res = await fetch('/student/ai-path/goal', {
      method: 'GET',
      credentials: 'same-origin',
      headers: buildHeaders(),
    })
    const data = await res.json().catch(() => null)
    if (!res.ok || !data?.ok || !data?.roadmap) {
      fetchError.value = labels.value.loadError
      return
    }
    roadmapState.value = data.roadmap
  } catch {
    fetchError.value = labels.value.loadError
  } finally {
    isLoadingRoadmap.value = false
  }
}

const levelLabel = (lvl: string) => {
  const lower = (lvl || 'beginner').toLowerCase()
  if (lower === 'intermediate') return labels.value.intermediate
  if (lower === 'advanced' || lower === 'mastery') return labels.value.advanced
  return labels.value.beginner
}

const actionLabel = (actionType: RoadmapCourse['actionType']) => {
  switch (actionType) {
    case 'continue':
      return labels.value.continueBtn
    case 'start':
      return labels.value.startCourseBtn
    case 'review':
      return labels.value.reviewBtn
    case 'view_details':
      return labels.value.viewDetailsBtn
    default:
      return labels.value.comingSoonBtn
  }
}

onMounted(() => {
  if (typeof window === 'undefined') return
  const params = new URLSearchParams(window.location.search)
  const v = params.get('view')
  if (v === 'roadmap' || v === 'list') {
    viewMode.value = v
  }
  const stParam = Number(params.get('stage'))
  if (Number.isInteger(stParam) && stParam >= 1 && stParam <= (roadmapState.value.stages || []).length) {
    selectedStageIndex.value = stParam - 1
  }
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
      <!-- Toast Notification -->
      <transition
        enter-active-class="transform ease-out duration-300 transition motion-reduce:transition-none"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-150 motion-reduce:transition-none"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showToast"
          class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-2xl border shadow-xl flex items-center gap-3 max-w-md"
          :class="toastIsError
            ? 'bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950 dark:border-rose-500/40 dark:text-rose-200'
            : 'bg-white border-purple-200 text-slate-800 dark:bg-[#1e1f20] dark:border-purple-500/40 dark:text-slate-100'"
        >
          <CheckCircle2 class="w-5 h-5 shrink-0" :class="toastIsError ? 'text-rose-500' : 'text-emerald-500'" />
          <p class="text-xs font-medium">{{ toastMessage }}</p>
        </div>
      </transition>

      <!-- 1. PAGE HEADER (Light + Dark mode, ZERO duplicate Ctrl K search input) -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <BookOpen class="w-7 h-7 text-purple-600 dark:text-purple-400 shrink-0" />
            <span>{{ labels.pageTitle }}</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
            {{ labels.pageSubtitle }}
          </p>
        </div>

        <button
          v-if="roadmapState.hasPublishedPaths"
          type="button"
          @click="openGoalModal()"
          class="min-h-12 sm:min-h-10 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center justify-center gap-2 shadow-xs transition-colors motion-reduce:transition-none cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
        >
          <Pencil class="w-3.5 h-3.5" />
          <span>{{ roadmapState.hasGoal ? labels.editGoal : labels.setGoal }}</span>
        </button>
      </div>

      <!-- Error Banner with Retry -->
      <div
        v-if="fetchError"
        class="rounded-[20px] border border-rose-200 dark:border-rose-500/40 bg-rose-50 dark:bg-rose-950/30 p-4 flex items-center justify-between gap-4"
      >
        <div class="flex items-center gap-2.5 text-xs font-medium text-rose-800 dark:text-rose-200">
          <AlertCircle class="w-4 h-4 shrink-0" />
          <span>{{ fetchError }}</span>
        </div>
        <button
          type="button"
          @click="reloadRoadmap"
          class="min-h-10 px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold inline-flex items-center gap-1.5 cursor-pointer"
        >
          <RotateCcw class="w-3.5 h-3.5" />
          <span>{{ labels.retry }}</span>
        </button>
      </div>

      <!-- Loading Skeleton -->
      <div v-if="isLoadingRoadmap" class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-pulse motion-reduce:animate-none">
        <div class="lg:col-span-8 h-52 rounded-[20px] bg-slate-200 dark:bg-[#252729]"></div>
        <div class="lg:col-span-4 h-52 rounded-[20px] bg-slate-200 dark:bg-[#252729]"></div>
        <div class="lg:col-span-12 h-40 rounded-[20px] bg-slate-200 dark:bg-[#252729]"></div>
      </div>

      <!-- EMPTY STATE 1: No published learning paths in catalog -->
      <section
        v-else-if="!roadmapState.hasPublishedPaths"
        class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-8 sm:p-12 text-center space-y-4 shadow-xs"
      >
        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-[#252729] text-slate-500 dark:text-slate-300 flex items-center justify-center mx-auto">
          <Compass class="w-7 h-7" />
        </div>
        <div class="max-w-md mx-auto space-y-1.5">
          <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ labels.noPathsTitle }}</h2>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{{ labels.noPathsDesc }}</p>
        </div>
        <div class="flex items-center justify-center gap-3 flex-wrap pt-1">
          <Link
            href="/student/browse"
            class="min-h-12 sm:min-h-10 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
          >
            <BookOpen class="w-4 h-4" />
            <span>{{ labels.browseCatalog }}</span>
          </Link>
          <Link
            href="/student/ai-tutor"
            class="min-h-12 sm:min-h-10 px-5 py-2.5 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-white hover:bg-slate-50 dark:bg-[#252729] dark:hover:bg-[#2d2f31] text-slate-700 dark:text-slate-200 text-xs font-bold inline-flex items-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
          >
            <img :src="'/images/ai-robot-avatar.png'" alt="" class="w-4 h-4 rounded-sm object-contain shrink-0" />
            <span>{{ labels.askAi }}</span>
          </Link>
        </div>
      </section>

      <!-- EMPTY STATE 2: Student has not selected a Learning Goal yet (ZERO fake 58%) -->
      <section
        v-else-if="!roadmapState.hasGoal"
        class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-6 sm:p-8 shadow-xs space-y-6"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 dark:border-[#2d2f31] pb-5">
          <div class="flex items-start gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 flex items-center justify-center shrink-0">
              <Target class="w-6 h-6" />
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ labels.noGoalTitle }}</h2>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ labels.noGoalDesc }}</p>
            </div>
          </div>
          <button
            type="button"
            @click="openGoalModal()"
            class="min-h-12 sm:min-h-10 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center justify-center gap-2 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 shrink-0"
          >
            <Target class="w-4 h-4" />
            <span>{{ labels.setGoal }}</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="pathOpt in roadmapState.publishedPaths"
            :key="pathOpt.id"
            class="rounded-2xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50/70 dark:bg-[#141517] p-4 flex flex-col justify-between gap-4"
          >
            <div class="space-y-1.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                {{ isKm ? pathOpt.title_km : pathOpt.title_en }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                {{ isKm ? pathOpt.desc_km : pathOpt.desc_en }}
              </p>
              <p class="text-[11px] font-semibold text-purple-600 dark:text-purple-400 pt-1">
                {{ pathOpt.stages_count }} {{ labels.stagePrefix }} • {{ pathOpt.courses_count }} {{ labels.coursesUnit }}
              </p>
            </div>

            <button
              type="button"
              @click="openGoalModal(pathOpt.id)"
              class="min-h-11 w-full px-4 py-2 rounded-xl bg-purple-600/10 hover:bg-purple-600 hover:text-white text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 dark:hover:bg-purple-600 dark:hover:text-white text-xs font-bold inline-flex items-center justify-center gap-1.5 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              <span>{{ labels.setGoal }}</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </section>

      <!-- MAIN REAL-DATA ROADMAP -->
      <template v-else-if="roadmapState.goal">
        <!-- 2. TOP SECTION: YOUR LEARNING GOAL (8 cols) & WHY THIS ROADMAP? (4 cols) -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
          <!-- Goal Summary Card -->
          <div class="lg:col-span-8 rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-7 shadow-xs flex flex-col justify-between gap-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
              <div class="space-y-3 min-w-0">
                <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                  <Target class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0" />
                  <span class="text-xs font-bold uppercase tracking-wider">{{ labels.yourGoalLabel }}</span>
                </div>

                <div>
                  <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white leading-tight">
                    {{ isKm ? roadmapState.goal.title_km : roadmapState.goal.title_en }}
                  </h2>
                  <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600 dark:text-slate-300 mt-2">
                    <span>
                      {{ labels.targetCompletion }}
                      <strong class="text-slate-900 dark:text-white">
                        {{ roadmapState.goal.targetMonths }} {{ labels.monthsUnit }}
                        ({{ isKm ? roadmapState.goal.targetDate_km : roadmapState.goal.targetDate_en }})
                      </strong>
                    </span>
                    <span>•</span>
                    <span>
                      {{ labels.weeklyStudyTime }}
                      <strong class="text-slate-900 dark:text-white">
                        {{ roadmapState.goal.weeklyHours }} {{ labels.hoursPerWeek }}
                      </strong>
                    </span>
                  </div>
                </div>

                <button
                  type="button"
                  @click="openGoalModal()"
                  class="min-h-11 px-4 py-2 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-slate-50 hover:bg-slate-100 dark:bg-[#252729] dark:hover:bg-[#2d2f31] text-purple-700 dark:text-purple-300 text-xs font-bold inline-flex items-center gap-1.5 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  <Pencil class="w-3.5 h-3.5" />
                  <span>{{ labels.editGoal }}</span>
                </button>
              </div>

              <!-- Circular Progress Ring (Ample padding so "Overall" never clips) -->
              <div class="shrink-0 self-center sm:self-auto">
                <div class="relative w-32 h-32 flex items-center justify-center">
                  <svg class="w-32 h-32 -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
                    <circle
                      cx="60"
                      cy="60"
                      :r="RING_RADIUS"
                      class="stroke-slate-200 dark:stroke-slate-800"
                      stroke-width="9"
                      fill="transparent"
                    />
                    <circle
                      cx="60"
                      cy="60"
                      :r="RING_RADIUS"
                      stroke="url(#roadmapRingGrad)"
                      stroke-width="9"
                      :stroke-dasharray="RING_CIRCUMFERENCE"
                      :stroke-dashoffset="donutDashOffset"
                      stroke-linecap="round"
                      fill="transparent"
                      class="transition-all duration-700 ease-out motion-reduce:transition-none"
                    />
                    <defs>
                      <linearGradient id="roadmapRingGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#7c3aed" />
                        <stop offset="100%" stop-color="#4f46e5" />
                      </linearGradient>
                    </defs>
                  </svg>
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-3">
                    <span class="text-2xl font-black text-slate-900 dark:text-white leading-none">
                      {{ roadmapState.overallProgress }}%
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">
                      {{ labels.overallLabel }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Single-Source Counts Breakdown (Completed + In Progress + Upcoming === Total) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-200/80 dark:border-[#2d2f31]">
              <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                <div>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ labels.completedLabel }}</p>
                  <p class="text-sm font-bold text-slate-900 dark:text-white">
                    {{ roadmapState.counts.completed }} {{ labels.coursesUnit }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shrink-0"></span>
                <div>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ labels.inProgressLabel }}</p>
                  <p class="text-sm font-bold text-slate-900 dark:text-white">
                    {{ roadmapState.counts.inProgress }} {{ labels.coursesUnit }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                <div>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ labels.upcomingLabel }}</p>
                  <p class="text-sm font-bold text-slate-900 dark:text-white">
                    {{ roadmapState.counts.upcoming }} {{ labels.coursesUnit }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-400 shrink-0"></span>
                <div>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ labels.totalLabel }}</p>
                  <p class="text-sm font-bold text-slate-900 dark:text-white">
                    {{ roadmapState.counts.total }} {{ labels.coursesUnit }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Why this roadmap? (Real factors only + compact non-overlapping robot icon) -->
          <div class="lg:col-span-4 rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs flex flex-col justify-between gap-4">
            <div class="space-y-3">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ labels.whyRoadmapTitle }}</h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ labels.whyRoadmapSub }}</p>
                </div>
                <img
                  :src="'/images/ai-robot-avatar.png'"
                  alt="AI"
                  class="w-14 h-14 rounded-2xl object-contain bg-purple-50 dark:bg-purple-500/15 p-1.5 border border-purple-200/70 dark:border-purple-500/30 shrink-0 select-none"
                />
              </div>

              <ul class="space-y-2.5 text-xs text-slate-700 dark:text-slate-300">
                <li
                  v-for="factor in roadmapState.whyFactors"
                  :key="factor.key"
                  class="flex items-start gap-2.5"
                >
                  <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                  <span>{{ isKm ? factor.label_km : factor.label_en }}</span>
                </li>
              </ul>
            </div>
          </div>
        </section>

        <!-- Path has 0 published courses -->
        <section
          v-if="roadmapState.stages.length === 0"
          class="rounded-[20px] border border-dashed border-slate-200 dark:border-[#323539] bg-white dark:bg-[#1e1f20] p-8 text-center space-y-3"
        >
          <p class="text-sm font-bold text-slate-900 dark:text-white">{{ labels.emptyCoursesTitle }}</p>
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ labels.emptyCoursesDesc }}</p>
          <button
            type="button"
            @click="openGoalModal()"
            class="min-h-11 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center gap-2 cursor-pointer"
          >
            <Pencil class="w-3.5 h-3.5" />
            <span>{{ labels.editGoal }}</span>
          </button>
        </section>

        <template v-else>
          <!-- 3. ROADMAP STAGES + VIEW MODE SWITCHER -->
          <section class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                {{ labels.roadmapTitle }}
              </h3>

              <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ labels.viewBy }}</span>
                <div class="inline-flex items-center rounded-xl bg-slate-100 dark:bg-[#141517] border border-slate-200 dark:border-[#2d2f31] p-1">
                  <button
                    type="button"
                    @click="setViewMode('roadmap')"
                    class="min-h-9 px-3 py-1.5 rounded-lg text-xs inline-flex items-center gap-1.5 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                    :class="viewMode === 'roadmap' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                  >
                    <MapIcon class="w-3.5 h-3.5" />
                    <span>{{ labels.viewRoadmap }}</span>
                  </button>
                  <button
                    type="button"
                    @click="setViewMode('list')"
                    class="min-h-9 px-3 py-1.5 rounded-lg text-xs inline-flex items-center gap-1.5 transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                    :class="viewMode === 'list' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                  >
                    <List class="w-3.5 h-3.5" />
                    <span>{{ labels.viewList }}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Horizontal scroll on mobile, grid on desktop -->
            <div
              role="tablist"
              class="flex overflow-x-auto snap-x gap-3 pb-2 lg:grid lg:grid-cols-3 xl:grid-cols-4 lg:overflow-visible lg:pb-0"
            >
              <button
                v-for="(stg, index) in roadmapState.stages"
                :key="stg.id"
                type="button"
                role="tab"
                :aria-selected="index === selectedStageIndex"
                @click="selectStage(index)"
                @keydown="handleStageKeydown($event, index)"
                class="min-w-[210px] snap-start lg:min-w-0 text-left relative p-4 rounded-2xl border flex flex-col justify-between gap-3 cursor-pointer transition-all motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                :class="[
                  stg.status === 'COMPLETED'
                    ? 'bg-emerald-50/70 border-emerald-200 dark:bg-emerald-950/25 dark:border-emerald-500/40'
                    : (stg.status === 'CURRENT'
                      ? 'bg-purple-50/80 border-purple-300 dark:bg-purple-950/30 dark:border-purple-500/60'
                      : 'bg-slate-50/80 border-slate-200 dark:bg-[#141517] dark:border-[#2d2f31]'),
                  index === selectedStageIndex ? 'ring-2 ring-purple-500 shadow-sm' : 'hover:border-purple-300 dark:hover:border-purple-500/40'
                ]"
              >
                <div class="space-y-1">
                  <span
                    class="text-[10px] font-bold uppercase tracking-wider"
                    :class="stg.status === 'COMPLETED'
                      ? 'text-emerald-700 dark:text-emerald-400'
                      : (stg.status === 'CURRENT' ? 'text-purple-700 dark:text-purple-300' : 'text-slate-500 dark:text-slate-400')"
                  >
                    {{ labels.stagePrefix }} {{ stg.stageNumber }}
                  </span>

                  <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white line-clamp-1">
                    {{ isKm ? stg.title_km : stg.title_en }}
                  </h4>

                  <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    {{ stg.completedCount }} / {{ stg.totalCount }} {{ labels.coursesUnit }} • {{ stg.doneLessons }}/{{ stg.totalLessons }} {{ labels.lessonsUnit }}
                  </p>
                </div>

                <div class="flex items-center justify-between gap-2 pt-1">
                  <span
                    v-if="stg.status === 'RECOMMENDED_ORDER'"
                    class="text-[10px] font-medium text-slate-500 dark:text-slate-400 inline-flex items-center gap-1"
                  >
                    <Compass class="w-3.5 h-3.5 text-slate-400" />
                    <span>{{ labels.recommendedOrder }}</span>
                  </span>
                  <span v-else class="text-[10px] font-bold" :class="stg.status === 'COMPLETED' ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400'">
                    {{ stg.progress }}%
                  </span>

                  <span
                    v-if="stg.status === 'COMPLETED'"
                    class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/40 inline-flex items-center justify-center"
                  >
                    <CheckCircle2 class="w-3.5 h-3.5" />
                  </span>
                  <span
                    v-else-if="stg.status === 'CURRENT'"
                    class="px-2 py-0.5 rounded-full bg-purple-600 text-white text-[10px] font-bold font-mono"
                  >
                    {{ stg.progress }}%
                  </span>
                  <span
                    v-else
                    class="w-6 h-6 rounded-full bg-slate-200/80 text-slate-500 dark:bg-slate-800 dark:text-slate-400 inline-flex items-center justify-center"
                  >
                    <Compass class="w-3.5 h-3.5" />
                  </span>
                </div>
              </button>
            </div>
          </section>

          <!-- 4. MAIN 2-COLUMN SECTION: STAGE COURSES + RECOMMENDATION vs NEXT UP + SKILLS + TIPS -->
          <section class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- LEFT 8 COLS -->
            <div class="lg:col-span-8 space-y-6">
              <!-- VIEW MODE = ROADMAP (Active Stage Courses) -->
              <div
                v-if="viewMode === 'roadmap' && activeStage"
                class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs space-y-5"
              >
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/80 dark:border-[#2d2f31] pb-4">
                  <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                      {{ labels.stagePrefix }} {{ activeStage.stageNumber }}: {{ isKm ? activeStage.title_km : activeStage.title_en }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                      {{ (isKm ? activeStage.desc_km : activeStage.desc_en) || labels.currentStageHint }}
                    </p>
                  </div>

                  <div class="flex items-center gap-2.5 shrink-0">
                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ labels.stageProgress }}</span>
                    <div class="w-24 h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                      <div
                        class="h-full rounded-full bg-gradient-to-r from-purple-600 to-indigo-500 transition-all duration-300 motion-reduce:transition-none"
                        :style="{ width: `${activeStage.progress}%` }"
                      ></div>
                    </div>
                    <span class="text-xs font-bold text-purple-600 dark:text-purple-400 font-mono">{{ activeStage.progress }}%</span>
                  </div>
                </div>

                <!-- Courses in Selected Stage -->
                <div class="space-y-3.5">
                  <div
                    v-for="crs in activeStage.courses"
                    :key="crs.id"
                    class="p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50/70 dark:bg-[#141517] hover:border-purple-300 dark:hover:border-purple-500/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors"
                  >
                    <div class="flex items-start gap-3.5 min-w-0">
                      <img
                        v-if="crs.thumbnail"
                        :src="crs.thumbnail"
                        :alt="isKm ? crs.title_km : crs.title_en"
                        class="w-12 h-12 rounded-xl object-cover border border-slate-200 dark:border-[#2d2f31] shrink-0"
                      />
                      <div
                        v-else
                        class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-500/30 font-extrabold text-xs flex items-center justify-center font-mono shrink-0"
                      >
                        {{ crs.code.slice(0, 4) }}
                      </div>

                      <div class="space-y-1 min-w-0">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                          {{ isKm ? crs.title_km : crs.title_en }}
                        </h4>
                        <p v-if="isKm ? crs.desc_km : crs.desc_en" class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">
                          {{ isKm ? crs.desc_km : crs.desc_en }}
                        </p>
                        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 pt-0.5 flex-wrap">
                          <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ levelLabel(crs.level) }}</span>
                          <span>•</span>
                          <span>{{ crs.lessonsCount }} {{ labels.lessonsUnit }}</span>
                          <span>•</span>
                          <span>{{ crs.duration }}</span>
                        </div>
                      </div>
                    </div>

                    <div class="flex items-center gap-4 shrink-0 justify-between sm:justify-end border-t sm:border-t-0 border-slate-200/70 dark:border-[#2d2f31] pt-3 sm:pt-0">
                      <div class="w-24 space-y-1">
                        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                          <span>{{ crs.progress }}%</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                          <div
                            class="h-full rounded-full transition-all duration-300 motion-reduce:transition-none"
                            :class="crs.state === 'completed' ? 'bg-emerald-500' : 'bg-purple-600'"
                            :style="{ width: `${crs.progress}%` }"
                          ></div>
                        </div>
                      </div>

                      <button
                        v-if="crs.actionType === 'coming_soon' || !crs.actionHref"
                        type="button"
                        disabled
                        class="min-h-11 px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 text-xs font-bold cursor-not-allowed"
                      >
                        {{ actionLabel(crs.actionType) }}
                      </button>

                      <Link
                        v-else
                        :href="crs.actionHref"
                        class="min-h-11 px-4 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-1.5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                        :class="crs.actionType === 'continue'
                          ? 'bg-purple-600 hover:bg-purple-700 text-white shadow-xs'
                          : (crs.actionType === 'review'
                            ? 'bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30'
                            : 'bg-white hover:bg-slate-100 text-slate-800 border border-slate-200 dark:bg-[#252729] dark:hover:bg-[#2d2f31] dark:text-white dark:border-[#3c4043]')"
                      >
                        <Play v-if="crs.actionType === 'continue' || crs.actionType === 'start'" class="w-3.5 h-3.5" />
                        <Eye v-else class="w-3.5 h-3.5" />
                        <span>{{ actionLabel(crs.actionType) }}</span>
                      </Link>
                    </div>
                  </div>
                </div>

                <!-- Recommended Stage Resources (shown ONLY when real resources exist in DB) -->
                <div v-if="activeStage.resources.length > 0" class="pt-3 border-t border-slate-200/80 dark:border-[#2d2f31]">
                  <button
                    type="button"
                    @click="isResourcesOpen = !isResourcesOpen"
                    class="w-full py-2 text-xs text-slate-600 dark:text-slate-300 hover:text-purple-600 dark:hover:text-purple-400 font-semibold inline-flex items-center justify-center gap-1.5 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                  >
                    <span>{{ isResourcesOpen ? labels.hideResources : labels.showResources }}</span>
                    <ChevronDown class="w-4 h-4 transition-transform motion-reduce:transition-none" :class="{ 'rotate-180': isResourcesOpen }" />
                  </button>

                  <div v-if="isResourcesOpen" class="mt-3 space-y-2">
                    <a
                      v-for="res in activeStage.resources"
                      :key="res.id"
                      :href="res.url"
                      :target="res.external ? '_blank' : undefined"
                      :rel="res.external ? 'noopener noreferrer' : undefined"
                      class="p-3 rounded-xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50 dark:bg-[#141517] hover:border-purple-300 dark:hover:border-purple-500/40 text-xs flex items-center justify-between text-slate-700 dark:text-slate-200 transition-colors"
                    >
                      <div class="flex items-center gap-2.5 min-w-0">
                        <FileText class="w-4 h-4 text-purple-600 dark:text-purple-400 shrink-0" />
                        <span class="truncate font-medium">{{ res.title }}</span>
                      </div>
                      <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400 shrink-0 inline-flex items-center gap-1">
                        <span>{{ res.type }}</span>
                        <ExternalLink class="w-3 h-3" />
                      </span>
                    </a>
                  </div>
                </div>
              </div>

              <!-- VIEW MODE = LIST (All Stages Vertically) -->
              <div v-else-if="viewMode === 'list'" class="space-y-5">
                <div
                  v-for="stg in roadmapState.stages"
                  :key="stg.id"
                  class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs space-y-4"
                >
                  <div class="flex items-center justify-between gap-2 border-b border-slate-200/80 dark:border-[#2d2f31] pb-3">
                    <div>
                      <span class="text-[10px] font-bold uppercase text-purple-600 dark:text-purple-400">
                        {{ labels.stagePrefix }} {{ stg.stageNumber }}
                      </span>
                      <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ isKm ? stg.title_km : stg.title_en }}
                      </h3>
                    </div>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200 font-mono">
                      {{ stg.progress }}% ({{ stg.completedCount }}/{{ stg.totalCount }})
                    </span>
                  </div>

                  <div class="space-y-3">
                    <div
                      v-for="crs in stg.courses"
                      :key="crs.id"
                      class="p-3.5 rounded-xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50/70 dark:bg-[#141517] flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                      <div class="min-w-0">
                        <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">
                          {{ isKm ? crs.title_km : crs.title_en }}
                        </p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                          {{ levelLabel(crs.level) }} • {{ crs.lessonsCount }} {{ labels.lessonsUnit }} • {{ crs.duration }} • {{ crs.progress }}%
                        </p>
                      </div>

                      <Link
                        v-if="crs.actionHref"
                        :href="crs.actionHref"
                        class="min-h-10 px-3.5 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1.5 shrink-0"
                        :class="crs.actionType === 'continue'
                          ? 'bg-purple-600 text-white hover:bg-purple-700'
                          : 'border border-slate-200 dark:border-[#3c4043] bg-white dark:bg-[#252729] text-slate-800 dark:text-white'"
                      >
                        <span>{{ actionLabel(crs.actionType) }}</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                      </Link>
                      <button
                        v-else
                        type="button"
                        disabled
                        class="min-h-10 px-3.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 text-xs font-bold cursor-not-allowed shrink-0"
                      >
                        {{ actionLabel(crs.actionType) }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- RECOMMENDATION BANNER ("AI" badge ONLY when AI truly wrote explanation) -->
              <div
                v-if="roadmapState.recommendation"
                class="rounded-[20px] border border-purple-200/80 dark:border-purple-800/50 bg-gradient-to-r from-purple-50 via-indigo-50/60 to-white dark:from-[#1b1438] dark:via-[#151b30] dark:to-[#18152b] p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
              >
                <div class="flex items-start gap-3.5">
                  <img
                    :src="'/images/ai-robot-avatar.png'"
                    alt="AI"
                    class="w-12 h-12 rounded-xl object-contain bg-white/80 dark:bg-white/10 p-1 border border-purple-200/70 dark:border-white/10 shrink-0 select-none"
                  />
                  <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-500/30 inline-flex items-center gap-1">
                        <Sparkles v-if="roadmapState.recommendation.isAiGenerated" class="w-3 h-3" />
                        <span>{{ isKm ? roadmapState.recommendation.badge_km : roadmapState.recommendation.badge_en }}</span>
                      </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-200 leading-relaxed max-w-xl">
                      {{ isKm ? roadmapState.recommendation.body_km : roadmapState.recommendation.body_en }}
                    </p>
                  </div>
                </div>

                <Link
                  :href="roadmapState.recommendation.askAiHref"
                  class="min-h-12 sm:min-h-10 px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs inline-flex items-center justify-center gap-2 shrink-0 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                >
                  <img :src="'/images/ai-robot-avatar.png'" alt="" class="w-4 h-4 rounded-xs object-contain shrink-0 select-none" />
                  <span>{{ isKm ? roadmapState.recommendation.actionLabel_km : roadmapState.recommendation.actionLabel_en }}</span>
                </Link>
              </div>
            </div>

            <!-- RIGHT 4 COLS: NEXT UP, SKILLS (ONLY IF PRESENT) & ROADMAP TIPS -->
            <div class="lg:col-span-4 space-y-6">
              <!-- NEXT UP -->
              <div
                v-if="roadmapState.nextUp"
                class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 shadow-xs space-y-3.5"
              >
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-200/80 dark:border-[#2d2f31] pb-3">
                  {{ labels.nextUpTitle }}
                </h3>

                <div class="p-4 rounded-2xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50/70 dark:bg-[#141517] space-y-3">
                  <div class="flex items-center gap-3">
                    <img
                      v-if="roadmapState.nextUp.thumbnail"
                      :src="roadmapState.nextUp.thumbnail"
                      :alt="isKm ? roadmapState.nextUp.title_km : roadmapState.nextUp.title_en"
                      class="w-10 h-10 rounded-xl object-cover shrink-0"
                    />
                    <div
                      v-else
                      class="w-10 h-10 rounded-xl bg-purple-600 text-white font-black text-xs flex items-center justify-center font-mono shrink-0"
                    >
                      {{ roadmapState.nextUp.code.slice(0, 4) }}
                    </div>

                    <div class="min-w-0">
                      <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">
                        {{ isKm ? roadmapState.nextUp.title_km : roadmapState.nextUp.title_en }}
                      </h4>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        {{ roadmapState.nextUp.state === 'in_progress'
                          ? labels.nextUpContinueHint
                          : (roadmapState.nextUp.isEnrolled ? labels.nextUpStartHint : labels.nextUpBrowseHint) }}
                      </p>
                    </div>
                  </div>

                  <Link
                    v-if="roadmapState.nextUp.actionHref"
                    :href="roadmapState.nextUp.actionHref"
                    class="min-h-11 w-full py-2 px-4 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs inline-flex items-center justify-center gap-1.5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
                  >
                    <Play v-if="roadmapState.nextUp.isEnrolled" class="w-3.5 h-3.5" />
                    <Eye v-else class="w-3.5 h-3.5" />
                    <span>{{ actionLabel(roadmapState.nextUp.actionType) }}</span>
                  </Link>
                </div>
              </div>

              <!-- SKILLS YOU WILL GAIN (Hidden cleanly when `skills.length === 0`) -->
              <div
                v-if="roadmapState.skills.length > 0"
                class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 shadow-xs space-y-3"
              >
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-200/80 dark:border-[#2d2f31] pb-3">
                  {{ labels.skillsTitle }}
                </h3>

                <div class="flex flex-wrap gap-1.5">
                  <span
                    v-for="skill in visibleSkills"
                    :key="skill.id"
                    class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-[#141517] border border-slate-200 dark:border-[#2d2f31] text-slate-700 dark:text-slate-300 text-xs font-medium"
                  >
                    {{ isKm ? skill.name_km : skill.name_en }}
                  </span>
                </div>

                <button
                  v-if="roadmapState.skills.length > 8"
                  type="button"
                  @click="showAllSkills = !showAllSkills"
                  class="text-xs text-purple-600 dark:text-purple-400 hover:underline font-bold pt-1 inline-flex items-center gap-1 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 rounded"
                >
                  <span>{{ showAllSkills ? labels.collapseSkills : `${labels.viewAllSkills} (${roadmapState.skills.length})` }}</span>
                  <ArrowRight class="w-3 h-3" />
                </button>
              </div>

              <!-- ROADMAP TIPS (Monochrome Lucide icons, zero colorful emojis) -->
              <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 shadow-xs space-y-3.5">
                <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider border-b border-slate-200/80 dark:border-[#2d2f31] pb-3">
                  {{ labels.tipsTitle }}
                </h3>

                <div class="space-y-3 text-xs">
                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-[#252729] text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                      <Clock class="w-4 h-4" />
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white">{{ labels.tip1Title }}</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ labels.tip1Desc }}</p>
                    </div>
                  </div>

                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-[#252729] text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                      <CheckSquare class="w-4 h-4" />
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white">{{ labels.tip2Title }}</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ labels.tip2Desc }}</p>
                    </div>
                  </div>

                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-[#252729] text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                      <HelpCircle class="w-4 h-4" />
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white">{{ labels.tip3Title }}</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ labels.tip3Desc }}</p>
                    </div>
                  </div>

                  <div class="flex items-start gap-3">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-[#252729] text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                      <TrendingUp class="w-4 h-4" />
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white">{{ labels.tip4Title }}</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ labels.tip4Desc }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </template>
      </template>
    </div>

    <!-- EDIT / SET GOAL MODAL -->
    <div
      v-if="isEditGoalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
      @click.self="isEditGoalOpen = false"
    >
      <form
        class="relative w-full max-w-md rounded-[22px] border border-slate-200 dark:border-[#3c4043] bg-white dark:bg-[#1e1f20] p-6 shadow-2xl space-y-5"
        @submit.prevent="saveGoal"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 flex items-center justify-center shrink-0">
              <Target class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ labels.modalTitle }}</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ labels.modalSub }}</p>
            </div>
          </div>
          <button
            type="button"
            @click="isEditGoalOpen = false"
            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white cursor-pointer"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <div
          v-if="goalFormError"
          class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-500/30 text-xs text-rose-700 dark:text-rose-200"
        >
          {{ goalFormError }}
        </div>

        <div class="space-y-4 text-xs">
          <div class="space-y-1.5">
            <label class="font-bold text-slate-700 dark:text-slate-300 block">{{ labels.fieldPath }}</label>
            <select
              v-model.number="editGoalForm.path_id"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-slate-50 dark:bg-[#141517] text-slate-900 dark:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
            >
              <option
                v-for="pathOpt in roadmapState.publishedPaths"
                :key="pathOpt.id"
                :value="pathOpt.id"
              >
                {{ isKm ? pathOpt.title_km : pathOpt.title_en }} ({{ pathOpt.courses_count }} {{ labels.coursesUnit }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="font-bold text-slate-700 dark:text-slate-300 block">{{ labels.fieldMonths }}</label>
              <input
                v-model.number="editGoalForm.target_months"
                type="number"
                min="1"
                max="36"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-slate-50 dark:bg-[#141517] text-slate-900 dark:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
              />
            </div>

            <div class="space-y-1.5">
              <label class="font-bold text-slate-700 dark:text-slate-300 block">{{ labels.fieldHours }}</label>
              <input
                v-model.number="editGoalForm.weekly_hours"
                type="number"
                min="1"
                max="60"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-slate-50 dark:bg-[#141517] text-slate-900 dark:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
              />
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-200 dark:border-[#2d2f31]">
          <button
            type="button"
            @click="isEditGoalOpen = false"
            class="min-h-11 px-4 py-2 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-white hover:bg-slate-50 dark:bg-[#252729] text-slate-700 dark:text-slate-200 text-xs font-semibold cursor-pointer"
          >
            {{ labels.cancel }}
          </button>
          <button
            type="submit"
            :disabled="isSavingGoal"
            class="min-h-11 px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-xs cursor-pointer disabled:opacity-50"
          >
            {{ isSavingGoal ? labels.saving : labels.save }}
          </button>
        </div>
      </form>
    </div>
  </StudentLayout>
</template>
