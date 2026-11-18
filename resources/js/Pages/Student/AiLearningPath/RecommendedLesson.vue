<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  RefreshCw,
  Compass,
  BookOpen,
  Flame,
  CheckCircle2,
  Circle,
  Trash2,
  Plus,
  AlertTriangle,
  ArrowRight,
  Sparkles,
  Target,
  Award,
  Clock,
  X,
  HelpCircle,
  PlayCircle,
  Check,
  ExternalLink,
} from 'lucide-vue-next'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { i18n } from '@/Services/i18n'

interface JourneyStep {
  id: string
  course_id: number
  lesson_id: number | null
  title_en: string
  title_km: string
  category_en: string
  category_km: string
  status: 'COMPLETED' | 'CURRENT' | 'AI_RECOMMENDED' | 'UPCOMING'
  isCourseStep?: boolean
  progress: number
  score: number | null
  duration: string
  desc_en: string
  desc_km: string
  skills_en: string[]
  skills_km: string[]
  prerequisites_en: string
  prerequisites_km: string
  learnUrl: string
  overviewUrl: string
  askAiUrl: string
}

interface SuggestedCourse {
  id: number
  title_en: string
  title_km: string
  major_en: string
  major_km: string
  instructor_en: string
  instructor_km: string
  level: string
  url: string
}

interface CurrentFocus {
  courseId: number
  courseTitle_en: string
  courseTitle_km: string
  moduleTitle_en: string
  moduleTitle_km: string
  lessonId: number | null
  lessonTitle_en: string
  lessonTitle_km: string
  progress: number
  lessonsDone: number
  lessonsTotal: number
  lastActive_en: string
  lastActive_km: string
  remaining_en: string
  remaining_km: string
  allCompleted: boolean
  learnUrl: string
  overviewUrl: string
  askAiUrl: string
}

interface AiNextStep {
  badge_en: string
  badge_km: string
  title_en: string
  title_km: string
  reason_en: string
  reason_km: string
  primaryLabel_en: string
  primaryLabel_km: string
  primaryHref: string
  askAiUrl: string
  canPracticeDrill: boolean
}

interface SkillProgressItem {
  courseId: number
  name_en: string
  name_km: string
  level: number
  status_en: string
  status_km: string
  lessonsDone: number
  lessonsTotal: number
  quizAvg: number | null
  isWarning: boolean
  href: string
}

interface WeakTopic {
  id: number
  quizId: number
  courseId: number | null
  topic_en: string
  topic_km: string
  course_en: string
  course_km: string
  score: number
  severity: 'high' | 'medium' | 'low'
  reason_en: string
  reason_km: string
  action_en: string
  action_km: string
  practiceUrl: string
  askAiUrl: string
}

interface PracticeQuestion {
  id: number
  quizId: number
  quizTitle: string
  question: string
  options: string[]
  correctIndex: number
}

interface GoalItem {
  id: string
  title: string
  titleKm: string
  titleEn: string
  isCompleted: boolean
  targetRoute: string
}

interface QuickGoalSuggestion {
  title: string
  title_km: string
  target_route: string
}

interface LearningPathPayload {
  hasEnrollments: boolean
  enrolledCoursesCount: number
  metrics: {
    trackName_en: string
    trackName_km: string
    facultyName_en?: string | null
    facultyName_km?: string | null
    targetLabel_en: string
    targetLabel_km: string
    overallProgress: number
    completedMilestones: number
    totalMilestones: number
    unitLabel_km?: string
    unitLabel_en?: string
    pacingBadge_en: string
    pacingBadge_km: string
    pacingSub_en: string
    pacingSub_km: string
    pacingTone: 'neutral' | 'emerald' | 'blue' | 'amber'
    studyTimeWeek: string
    streakDays: number
  }
  aiBanner: {
    badge_en: string
    badge_km: string
    title_en: string
    title_km: string
    desc_en: string
    desc_km: string
    primaryLabel_en: string
    primaryLabel_km: string
    primaryHref: string
    askAiUrl: string
    tone: string
    analyzedAt?: string | null
    provider?: string | null
    isFromAiDb?: boolean
  }
  journeySteps: JourneyStep[]
  suggestedCourses: SuggestedCourse[]
  currentFocus: CurrentFocus | null
  aiNextStep: AiNextStep | null
  skillProgress: SkillProgressItem[]
  weakTopics: WeakTopic[]
  practiceQuestions: PracticeQuestion[]
  todayGoals: {
    completedCount: number
    totalCount: number
    percentage: number
    items: GoalItem[]
    quickSuggestions: QuickGoalSuggestion[]
  }
}

const props = defineProps<{
  learningPath?: LearningPathPayload
  csrfToken?: string
}>()

const page = usePage()
const isKm = computed(() => i18n.locale.value === 'km')

const resolveLearningPath = (): LearningPathPayload => {
  const lp = props.learningPath || ((page.props as any)?.learningPath as LearningPathPayload | undefined)
  if (lp) return lp
  return {
    hasEnrollments: false,
    enrolledCoursesCount: 0,
    metrics: {
      trackName_en: 'No Track Selected Yet',
      trackName_km: 'មិនទាន់ជ្រើសរើសជំនាញសិក្សា',
      facultyName_en: null,
      facultyName_km: null,
      targetLabel_en: 'Enroll in a course to set milestones',
      targetLabel_km: 'ចុះឈ្មោះវគ្គសិក្សាដើម្បីចាប់ផ្តើមគោលដៅ',
      overallProgress: 0,
      completedMilestones: 0,
      totalMilestones: 0,
      unitLabel_km: 'មេរៀន',
      unitLabel_en: 'lessons',
      pacingBadge_en: 'Ready to Start',
      pacingBadge_km: 'ត្រៀមចាប់ផ្តើម',
      pacingSub_en: 'No active courses yet',
      pacingSub_km: 'មិនទាន់មានវគ្គសិក្សាសកម្ម',
      pacingTone: 'neutral',
      studyTimeWeek: '0h 0m',
      streakDays: 0,
    },
    aiBanner: {
      badge_en: 'Get Started',
      badge_km: 'ចាប់ផ្តើមដំបូង',
      title_en: 'Enroll in your first course to unlock your personalized roadmap',
      title_km: 'ចុះឈ្មោះវគ្គសិក្សាដំបូង ដើម្បីបង្កើតផែនទីសិក្សា AI ផ្ទាល់ខ្លួន',
      desc_en: 'Once enrolled, AI analyzes your real progress and quiz scores.',
      desc_km: 'បន្ទាប់ពីចុះឈ្មោះ AI នឹងវិភាគវឌ្ឍនភាពមេរៀន និងពិន្ទុ Quiz ពិតរបស់អ្នក។',
      primaryLabel_en: 'Browse Courses',
      primaryLabel_km: 'ស្វែងរកវគ្គសិក្សា',
      primaryHref: '/student/browse',
      askAiUrl: '/student/ai-tutor',
      tone: 'neutral',
      analyzedAt: null,
      provider: null,
      isFromAiDb: false,
    },
    journeySteps: [],
    suggestedCourses: [],
    currentFocus: null,
    aiNextStep: null,
    skillProgress: [],
    weakTopics: [],
    practiceQuestions: [],
    todayGoals: {
      completedCount: 0,
      totalCount: 0,
      percentage: 0,
      items: [],
      quickSuggestions: [],
    },
  }
}

const pathState = ref<LearningPathPayload>(resolveLearningPath())
watch(
  () => [props.learningPath, (page.props as any)?.learningPath],
  () => {
    pathState.value = resolveLearningPath()
  },
  { deep: true }
)

// Interactive state
const isReanalyzing = ref(false)
const toastMessage = ref('')
const toastType = ref<'success' | 'error'>('success')
const showToast = ref(false)
let toastTimer: ReturnType<typeof setTimeout> | null = null

const selectedStep = ref<JourneyStep | null>(null)
const isStepModalOpen = ref(false)

// Practice Drill state
const isPracticeModalOpen = ref(false)
const currentPracticeIndex = ref(0)
const practiceSubmitted = ref(false)
const practiceSelectedAnswer = ref<number | null>(null)

// Study Goals CRUD state
const newGoalTitle = ref('')
const isGoalBusy = ref(false)

const labels = computed(() => isKm.value
  ? {
      pageTitle: 'គន្លងសិក្សាផ្ទាល់ខ្លួន',
      pageSubtitle: 'ផែនទីបង្ហាញផ្លូវសិក្សាដែលដំណើរការដោយ AI ផ្អែកលើទិន្នន័យសិក្សាពិតរបស់អ្នក',
      dashboard: 'ផ្ទាំងគ្រប់គ្រង',
      aiLearning: 'ការរៀនជាមួយ AI',
      reanalyze: 'វិភាគឡើងវិញជាមួយ AI',
      reanalyzing: 'កំពុងវិភាគ...',
      reanalyzeOk: 'គន្លងសិក្សា AI ត្រូវបានធ្វើបច្ចុប្បន្នភាពដោយជោគជ័យ!',
      reanalyzeErr: 'មិនអាចវិភាគឡើងវិញបានទេ។ សូមព្យាយាមម្ដងទៀត។',
      askAi: 'សួរ AI',
      targetGoalCard: 'គោលដៅសិក្សា',
      overallProgressCard: 'វឌ្ឍនភាពសរុប',
      milestonesCard: 'គោលដៅសម្រេចបាន',
      studyTimeCard: 'ពេលវេលាសិក្សាសប្តាហ៍នេះ',
      streakLabel: 'ថ្ងៃសិក្សាជាប់គ្នា៖',
      daysUnit: 'ថ្ងៃ',
      journeyTitle: 'ដំណើរសិក្សារបស់អ្នក',
      journeySubtitle: 'ចុចលើជំហាននីមួយៗ ដើម្បីមើលមេរៀន និងបន្តការសិក្សាផ្ទាល់',
      statusCompleted: 'បានបញ្ចប់',
      statusCurrent: 'កំពុងរៀន',
      statusRecommended: 'AI ណែនាំ',
      statusUpcoming: 'បន្ទាប់',
      stepPrefix: 'ជំហាន',
      progressLabel: 'វឌ្ឍនភាព',
      stepActionCompleted: 'រំលឹកឡើងវិញ',
      stepActionCurrent: 'ចូលរៀនបន្ត',
      stepActionRecommended: 'មើលវគ្គណែនាំ',
      stepActionUpcoming: 'បើកមេរៀន',
      stepDetails: 'លម្អិត',
      analyzedLabel: 'រក្សាទុកក្នុង AI DB:',
      emptyJourneyTitle: 'មិនទាន់មានដំណើរសិក្សានៅឡើយទេ',
      emptyJourneyDesc: 'ចុះឈ្មោះវគ្គសិក្សាមួយក្នុងប្រព័ន្ធ SPI ដើម្បីឲ្យ AI បង្កើតជំហានសិក្សាតាមមេរៀនជូនអ្នក។',
      browseCourses: 'ស្វែងរកវគ្គសិក្សា',
      suggestedCoursesLabel: 'វគ្គសិក្សាដែលមានក្នុងប្រព័ន្ធ៖',
      currentFocusTitle: 'ការសិក្សាបច្ចុប្បន្ន',
      resumeLesson: 'បន្តមេរៀន',
      courseOverview: 'មើលព័ត៌មានវគ្គសិក្សា',
      lastActive: 'សកម្មភាពចុងក្រោយ៖',
      remaining: 'នៅសល់៖',
      lessonsUnit: 'មេរៀន',
      noFocusTitle: 'មិនទាន់មានវគ្គសិក្សាកំពុងរៀន',
      noFocusDesc: 'ជ្រើសរើសវគ្គសិក្សាពីកាតាឡុក ដើម្បីតាមដានមេរៀនចុងក្រោយរបស់អ្នកនៅទីនេះ។',
      aiNextTitle: 'ជំហានបន្ទាប់ដែល AI ណែនាំ',
      noNextStepDesc: 'ចាប់ផ្តើមវគ្គសិក្សាមួយ ដើម្បីទទួលបានការណែនាំជំហានបន្ទាប់ពី AI។',
      skillProgressTitle: 'វឌ្ឍនភាពជំនាញតាមវគ្គសិក្សា',
      noSkillsDesc: 'ពិន្ទុជំនាញតាមវគ្គសិក្សានឹងបង្ហាញនៅទីនេះ បន្ទាប់ពីអ្នកចុះឈ្មោះវគ្គសិក្សា។',
      weakTopicsTitle: 'ចំណុចខ្សោយដែលត្រូវពង្រឹង',
      noWeakTopicsTitle: 'មិនទាន់រកឃើញចំណុចខ្សោយទេ',
      noWeakTopicsDesc: 'ប្រព័ន្ធនឹងវិភាគចំណុចដែលត្រូវពង្រឹងដោយស្វ័យប្រវត្តិ ពេលអ្នកប្រឡង Quiz ក្នុងវគ្គសិក្សា។',
      practiceBtn: 'អនុវត្តលំហាត់',
      todayPlanTitle: 'ផែនការសិក្សាប្រចាំថ្ងៃ',
      tasksCompletedSuffix: 'ភារកិច្ចបានបញ្ចប់',
      addGoalPlaceholder: 'បន្ថែមគោលដៅសិក្សាថ្មីថ្ងៃនេះ...',
      addGoalBtn: 'បន្ថែម',
      noGoalsTitle: 'មិនទាន់មានគោលដៅសិក្សាថ្ងៃនេះទេ',
      noGoalsDesc: 'បន្ថែមភារកិច្ចផ្ទាល់ខ្លួនខាងលើ ឬជ្រើសរើសការណែនាំខាងក្រោម៖',
      openLink: 'បើក',
      deleteGoal: 'លុប',
      modalPrereq: 'តម្រូវការជាមុន៖',
      modalDuration: 'រយៈពេលប៉ាន់ស្មាន៖',
      modalLessons: 'មេរៀនក្នុងជំហាននេះ',
      modalClose: 'បិទ',
      drillTitle: 'លំហាត់អនុវត្តរហ័ស',
      drillQuestionOf: 'សំណួរទី',
      drillOf: 'ក្នុងចំណោម',
      drillCorrect: 'ត្រឹមត្រូវ! ពូកែណាស់!',
      drillWrong: 'មិនទាន់ត្រឹមត្រូវទេ — ចម្លើយត្រឹមត្រូវត្រូវបានបង្ហាញខាងលើ។',
      drillNext: 'សំណួរបន្ទាប់',
      drillFinish: 'រួចរាល់',
    }
  : {
      pageTitle: 'Personalized Learning Path',
      pageSubtitle: 'Your AI-powered roadmap built from your real courses, quizzes, and study history.',
      dashboard: 'Dashboard',
      aiLearning: 'AI Learning',
      reanalyze: 'Re-analyze with AI',
      reanalyzing: 'Analyzing...',
      reanalyzeOk: 'AI Learning Path updated from your latest study data!',
      reanalyzeErr: 'Unable to re-analyze right now. Please try again.',
      askAi: 'Ask AI',
      targetGoalCard: 'Target Learning Track',
      overallProgressCard: 'Overall Path Progress',
      milestonesCard: 'Milestones Completed',
      studyTimeCard: 'Study Time This Week',
      streakLabel: 'Active streak:',
      daysUnit: 'days',
      journeyTitle: 'Your Learning Journey',
      journeySubtitle: 'Click any milestone step to view lessons and resume studying.',
      statusCompleted: 'Completed',
      statusCurrent: 'Current',
      statusRecommended: 'AI Recommended',
      statusUpcoming: 'Upcoming',
      stepPrefix: 'STEP',
      progressLabel: 'Progress',
      stepActionCompleted: 'Review',
      stepActionCurrent: 'Continue Learning',
      stepActionRecommended: 'Explore Recommended',
      stepActionUpcoming: 'Preview',
      stepDetails: 'Details',
      analyzedLabel: 'Saved AI Analysis:',
      emptyJourneyTitle: 'Your Learning Roadmap Awaits',
      emptyJourneyDesc: 'Enroll in a course in the SPI catalog so AI can build your step-by-step milestone roadmap.',
      browseCourses: 'Browse Catalog',
      suggestedCoursesLabel: 'Available published courses:',
      currentFocusTitle: 'Current Learning Focus',
      resumeLesson: 'Continue Lesson',
      courseOverview: 'Course Overview',
      lastActive: 'Last active:',
      remaining: 'Remaining:',
      lessonsUnit: 'lessons',
      noFocusTitle: 'No Active Course Yet',
      noFocusDesc: 'Pick a course from the catalog to track your live lesson progress here.',
      aiNextTitle: 'AI Recommended Next Step',
      noNextStepDesc: 'Enroll in a course to unlock tailored next-step recommendations.',
      skillProgressTitle: 'Skill & Course Mastery',
      noSkillsDesc: 'Course skill mastery bars will appear here once you enroll in courses.',
      weakTopicsTitle: 'Weak Topics Detected',
      noWeakTopicsTitle: 'No Weak Topics Detected',
      noWeakTopicsDesc: 'Keep taking course quizzes — any topic below threshold will appear here automatically.',
      practiceBtn: 'Practice',
      todayPlanTitle: "Today's AI Study Plan",
      tasksCompletedSuffix: 'tasks completed',
      addGoalPlaceholder: 'Add a study goal for today...',
      addGoalBtn: 'Add',
      noGoalsTitle: 'No Study Goals Added Today',
      noGoalsDesc: 'Type a goal above or pick a smart starter suggestion below:',
      openLink: 'Open',
      deleteGoal: 'Delete',
      modalPrereq: 'Prerequisites:',
      modalDuration: 'Estimated Duration:',
      modalLessons: 'Lessons in this Milestone',
      modalClose: 'Close',
      drillTitle: 'Quick Practice Drill',
      drillQuestionOf: 'Question',
      drillOf: 'of',
      drillCorrect: 'Correct! Well done.',
      drillWrong: 'Not quite — the correct option is highlighted above.',
      drillNext: 'Next Question',
      drillFinish: 'Done',
    })

const triggerToast = (msg: string, type: 'success' | 'error' = 'success') => {
  toastMessage.value = msg
  toastType.value = type
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

// Re-analyze with real backend endpoint
const reanalyzeWithAi = async () => {
  if (isReanalyzing.value) return
  isReanalyzing.value = true
  try {
    const res = await fetch('/student/ai-path/reanalyze', {
      method: 'POST',
      credentials: 'same-origin',
      headers: buildHeaders(),
    })
    const data = await res.json().catch(() => null)
    if (!res.ok || !data?.ok || !data?.learningPath) {
      const serverErr = isKm.value
        ? (data?.message || labels.value.reanalyzeErr)
        : (data?.message_en || data?.message || labels.value.reanalyzeErr)
      triggerToast(serverErr, 'error')
      return
    }
    pathState.value = data.learningPath
    triggerToast(labels.value.reanalyzeOk, 'success')
  } catch {
    triggerToast(labels.value.reanalyzeErr, 'error')
  } finally {
    isReanalyzing.value = false
  }
}

// Step modal
const openStepModal = (step: JourneyStep) => {
  selectedStep.value = step
  isStepModalOpen.value = true
}

// Practice drill modal
const currentQuestion = computed<PracticeQuestion | null>(() => {
  const list = pathState.value.practiceQuestions || []
  return list[currentPracticeIndex.value] || null
})

const startPracticeDrill = () => {
  if (!(pathState.value.practiceQuestions || []).length) return
  currentPracticeIndex.value = 0
  practiceSubmitted.value = false
  practiceSelectedAnswer.value = null
  isPracticeModalOpen.value = true
}

const submitPracticeAnswer = (idx: number) => {
  practiceSelectedAnswer.value = idx
  practiceSubmitted.value = true
}

const nextPracticeQuestion = () => {
  const total = (pathState.value.practiceQuestions || []).length
  if (currentPracticeIndex.value + 1 < total) {
    currentPracticeIndex.value += 1
    practiceSubmitted.value = false
    practiceSelectedAnswer.value = null
  } else {
    isPracticeModalOpen.value = false
  }
}

// Today's AI Study Plan (student_goals CRUD)
const addGoal = async (customTitle?: string, customTitleKm?: string, targetRoute?: string) => {
  const rawTitle = (customTitle ?? newGoalTitle.value).trim()
  if (!rawTitle || isGoalBusy.value) return
  isGoalBusy.value = true
  try {
    const res = await fetch('/student/goals', {
      method: 'POST',
      credentials: 'same-origin',
      headers: buildHeaders(),
      body: JSON.stringify({
        title: rawTitle,
        title_km: customTitleKm || rawTitle,
        target_route: targetRoute || '/student/my-courses/enrolled',
      }),
    })
    const data = await res.json().catch(() => null)
    if (res.ok && data?.todayGoals) {
      pathState.value = {
        ...pathState.value,
        todayGoals: {
          ...pathState.value.todayGoals,
          completedCount: data.todayGoals.completedCount,
          totalCount: data.todayGoals.totalCount,
          percentage: data.todayGoals.percentage,
          items: data.todayGoals.items,
        },
      }
      if (!customTitle) newGoalTitle.value = ''
    }
  } finally {
    isGoalBusy.value = false
  }
}

const toggleGoal = async (goal: GoalItem) => {
  if (isGoalBusy.value) return
  isGoalBusy.value = true
  const prevState = goal.isCompleted
  goal.isCompleted = !prevState
  try {
    const res = await fetch(`/student/goals/${goal.id}`, {
      method: 'PATCH',
      credentials: 'same-origin',
      headers: buildHeaders(),
      body: JSON.stringify({ is_completed: goal.isCompleted }),
    })
    const data = await res.json().catch(() => null)
    if (res.ok && data?.todayGoals) {
      pathState.value = {
        ...pathState.value,
        todayGoals: {
          ...pathState.value.todayGoals,
          completedCount: data.todayGoals.completedCount,
          totalCount: data.todayGoals.totalCount,
          percentage: data.todayGoals.percentage,
          items: data.todayGoals.items,
        },
      }
    } else {
      goal.isCompleted = prevState
    }
  } catch {
    goal.isCompleted = prevState
  } finally {
    isGoalBusy.value = false
  }
}

const deleteGoal = async (goalId: string) => {
  if (isGoalBusy.value) return
  isGoalBusy.value = true
  try {
    const res = await fetch(`/student/goals/${goalId}`, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: buildHeaders(),
    })
    const data = await res.json().catch(() => null)
    if (res.ok && data?.todayGoals) {
      pathState.value = {
        ...pathState.value,
        todayGoals: {
          ...pathState.value.todayGoals,
          completedCount: data.todayGoals.completedCount,
          totalCount: data.todayGoals.totalCount,
          percentage: data.todayGoals.percentage,
          items: data.todayGoals.items,
        },
      }
    }
  } finally {
    isGoalBusy.value = false
  }
}

const stepBadgeClass = (status: JourneyStep['status']) => {
  switch (status) {
    case 'COMPLETED':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30'
    case 'CURRENT':
      return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-500/15 dark:text-purple-300 dark:border-purple-500/30'
    case 'AI_RECOMMENDED':
      return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/30'
    default:
      return 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
  }
}

const stepCardBorderClass = (status: JourneyStep['status']) => {
  switch (status) {
    case 'COMPLETED':
      return 'border-emerald-200 hover:border-emerald-300 dark:border-emerald-500/30 dark:hover:border-emerald-500/50'
    case 'CURRENT':
      return 'border-purple-300 ring-1 ring-purple-200/70 hover:border-purple-400 dark:border-purple-500/50 dark:ring-purple-500/20'
    case 'AI_RECOMMENDED':
      return 'border-amber-200 hover:border-amber-300 dark:border-amber-500/30 dark:hover:border-amber-500/50'
    default:
      return 'border-slate-200 hover:border-slate-300 dark:border-[#2d2f31] dark:hover:border-slate-600'
  }
}

const stepStatusText = (status: JourneyStep['status']) => {
  switch (status) {
    case 'COMPLETED':
      return labels.value.statusCompleted
    case 'CURRENT':
      return labels.value.statusCurrent
    case 'AI_RECOMMENDED':
      return labels.value.statusRecommended
    default:
      return labels.value.statusUpcoming
  }
}

const pacingBadgeClass = computed(() => {
  switch (pathState.value.metrics.pacingTone) {
    case 'emerald':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-500/30'
    case 'blue':
      return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/15 dark:text-blue-300 dark:border-blue-500/30'
    case 'amber':
      return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:border-amber-500/30'
    default:
      return 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
  }
})

const skillBarColor = (item: SkillProgressItem) => {
  if (item.isWarning) return 'from-amber-500 to-orange-500'
  if (item.level >= 85) return 'from-emerald-500 to-teal-400'
  if (item.level >= 70) return 'from-blue-500 to-indigo-500'
  if (item.level >= 40) return 'from-purple-500 to-indigo-500'
  return 'from-cyan-500 to-blue-500'
}
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
    <div class="space-y-6 pb-20">
      <!-- Toast notification -->
      <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showToast"
          class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-2xl border shadow-xl flex items-center gap-3 max-w-md"
          :class="toastType === 'error'
            ? 'bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950 dark:border-rose-500/40 dark:text-rose-200'
            : 'bg-white border-purple-200 text-slate-800 dark:bg-[#1e1f20] dark:border-purple-500/40 dark:text-slate-100'"
        >
          <img
            :src="'/images/ai-robot-avatar.png'"
            alt="AI"
            class="w-7 h-7 rounded-lg object-contain shrink-0 select-none"
          />
          <p class="text-xs font-medium">{{ toastMessage }}</p>
        </div>
      </transition>

      <!-- 1. PAGE HEADER (High-contrast Light + Dark mode) -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2.5 flex-wrap">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
              {{ labels.pageTitle }}
            </h1>
            <span class="px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-500/30 text-xs font-bold font-mono inline-flex items-center gap-1.5">
              <img :src="'/images/ai-robot-avatar.png'" alt="" class="w-4 h-4 rounded-sm object-contain shrink-0 select-none" />
              <span>AI</span>
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
            {{ labels.pageSubtitle }}
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button
            type="button"
            :disabled="isReanalyzing"
            @click="reanalyzeWithAi"
            class="px-4 py-2 rounded-xl bg-purple-600/10 hover:bg-purple-600/20 text-purple-700 border border-purple-300/80 dark:bg-purple-600/20 dark:hover:bg-purple-600/30 dark:text-purple-300 dark:border-purple-500/30 text-xs font-bold inline-flex items-center gap-2 transition-colors cursor-pointer disabled:opacity-50"
          >
            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isReanalyzing }" />
            <span>{{ isReanalyzing ? labels.reanalyzing : labels.reanalyze }}</span>
          </button>
        </div>
      </div>

      <!-- 2. AI LEARNING RECOMMENDATION HERO BANNER -->
      <section class="relative overflow-hidden rounded-[22px] bg-gradient-to-r from-purple-50 via-indigo-50/70 to-white dark:from-[#1b1438] dark:via-[#151b30] dark:to-[#18152b] border border-purple-200/80 dark:border-purple-800/50 p-5 sm:p-7 shadow-xs">
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
          <div class="flex items-start gap-4">
            <img
              :src="'/images/ai-robot-avatar.png'"
              alt="AI"
              class="w-13 h-13 sm:w-15 sm:h-15 rounded-2xl object-contain bg-white/80 dark:bg-white/10 p-1.5 border border-purple-200/70 dark:border-white/10 shadow-sm shrink-0 select-none"
            />
            <div class="space-y-1.5">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300 inline-flex items-center gap-1">
                  <Sparkles class="w-3.5 h-3.5" />
                  <span>{{ isKm ? 'ការណែនាំពី AI' : 'AI Learning Recommendation' }}</span>
                </span>
                <span
                  class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                  :class="pathState.aiBanner.tone === 'amber'
                    ? 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-500/20 dark:text-amber-300 dark:border-amber-500/30'
                    : (pathState.aiBanner.tone === 'emerald'
                      ? 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/30'
                      : 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-500/30')"
                >
                  {{ isKm ? pathState.aiBanner.badge_km : pathState.aiBanner.badge_en }}
                </span>
                <span
                  v-if="pathState.aiBanner.analyzedAt"
                  class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-white/80 text-slate-600 border border-slate-200 dark:bg-white/10 dark:text-slate-300 dark:border-white/10"
                >
                  {{ labels.analyzedLabel }} {{ pathState.aiBanner.analyzedAt }}
                </span>
              </div>
              <h2 class="text-base sm:text-xl font-bold text-slate-900 dark:text-white leading-snug">
                {{ isKm ? pathState.aiBanner.title_km : pathState.aiBanner.title_en }}
              </h2>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 max-w-3xl">
                {{ isKm ? pathState.aiBanner.desc_km : pathState.aiBanner.desc_en }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <Link
              :href="pathState.aiBanner.primaryHref"
              class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center gap-2 transition-colors shadow-sm"
            >
              <span>{{ isKm ? pathState.aiBanner.primaryLabel_km : pathState.aiBanner.primaryLabel_en }}</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </Link>
            <Link
              :href="pathState.aiBanner.askAiUrl"
              class="px-4 py-2.5 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 dark:bg-[#252729] dark:hover:bg-[#2d2f31] dark:text-slate-200 dark:border-[#3c4043] text-xs font-bold inline-flex items-center gap-2 transition-colors"
            >
              <img :src="'/images/ai-robot-avatar.png'" alt="" class="w-4 h-4 rounded-sm object-contain shrink-0" />
              <span>{{ labels.askAi }}</span>
            </Link>
          </div>
        </div>
      </section>

      <!-- 3. REAL KPI SUMMARY CARDS -->
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Track & Active Academic Year -->
        <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-4 sm:p-5 shadow-xs flex flex-col justify-between gap-2">
          <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            <span>{{ labels.targetGoalCard }}</span>
            <Target class="w-4 h-4 text-purple-600 dark:text-purple-400" />
          </div>
          <div>
            <p class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2">
              {{ isKm ? pathState.metrics.trackName_km : pathState.metrics.trackName_en }}
            </p>
            <p
              v-if="isKm ? pathState.metrics.facultyName_km : pathState.metrics.facultyName_en"
              class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5"
            >
              {{ isKm ? pathState.metrics.facultyName_km : pathState.metrics.facultyName_en }}
            </p>
          </div>
          <p class="text-xs text-purple-700 dark:text-purple-300 font-medium">
            {{ isKm ? pathState.metrics.targetLabel_km : pathState.metrics.targetLabel_en }}
          </p>
        </div>

        <!-- Card 2: Overall Progress -->
        <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-4 sm:p-5 shadow-xs flex flex-col justify-between gap-2">
          <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            <span>{{ labels.overallProgressCard }}</span>
            <span class="text-sm font-black text-purple-600 dark:text-purple-400">{{ pathState.metrics.overallProgress }}%</span>
          </div>
          <div class="w-full h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
            <div
              class="h-full rounded-full bg-gradient-to-r from-purple-600 via-indigo-500 to-emerald-500 transition-all duration-500"
              :style="{ width: `${pathState.metrics.overallProgress}%` }"
            ></div>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ pathState.enrolledCoursesCount }} {{ isKm ? 'វគ្គសិក្សាដែលបានចុះឈ្មោះ' : (pathState.enrolledCoursesCount === 1 ? 'enrolled course' : 'enrolled courses') }}
          </p>
        </div>

        <!-- Card 3: Milestones -->
        <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-4 sm:p-5 shadow-xs flex flex-col justify-between gap-2">
          <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            <span>{{ labels.milestonesCard }}</span>
            <Award class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
          </div>
          <div class="flex items-center gap-2.5 flex-wrap">
            <span class="text-xl font-black text-slate-900 dark:text-white">
              {{ pathState.metrics.completedMilestones }} / {{ pathState.metrics.totalMilestones }}
            </span>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border" :class="pacingBadgeClass">
              {{ isKm ? pathState.metrics.pacingBadge_km : pathState.metrics.pacingBadge_en }}
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ isKm ? pathState.metrics.pacingSub_km : pathState.metrics.pacingSub_en }}
          </p>
        </div>

        <!-- Card 4: Study Time This Week -->
        <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-4 sm:p-5 shadow-xs flex flex-col justify-between gap-2">
          <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            <span>{{ labels.studyTimeCard }}</span>
            <Clock class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
          </div>
          <p class="text-xl font-black text-slate-900 dark:text-white">
            {{ pathState.metrics.studyTimeWeek }}
          </p>
          <p class="text-xs text-amber-600 dark:text-amber-400 inline-flex items-center gap-1 font-medium">
            <Flame class="w-3.5 h-3.5" />
            <span>{{ labels.streakLabel }} {{ pathState.metrics.streakDays }} {{ labels.daysUnit }}</span>
          </p>
        </div>
      </section>

      <!-- 4. YOUR LEARNING JOURNEY ROADMAP -->
      <section class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white inline-flex items-center gap-2">
              <Compass class="w-5 h-5 text-purple-600 dark:text-purple-400" />
              <span>{{ labels.journeyTitle }}</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ labels.journeySubtitle }}
            </p>
          </div>
          <div class="flex items-center gap-3 text-[11px] font-semibold flex-wrap">
            <span class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              {{ labels.statusCompleted }}
            </span>
            <span class="inline-flex items-center gap-1.5 text-purple-700 dark:text-purple-400">
              <span class="w-2 h-2 rounded-full bg-purple-500"></span>
              {{ labels.statusCurrent }}
            </span>
            <span class="inline-flex items-center gap-1.5 text-amber-700 dark:text-amber-400">
              <span class="w-2 h-2 rounded-full bg-amber-500"></span>
              {{ labels.statusRecommended }}
            </span>
          </div>
        </div>

        <!-- Journey Steps (Green = Completed, Purple = Current, Yellow = AI Recommended) -->
        <div v-if="pathState.journeySteps.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="(step, idx) in pathState.journeySteps"
            :key="step.id"
            class="rounded-2xl border bg-slate-50/70 dark:bg-[#141517] p-4 transition-all flex flex-col justify-between gap-3.5"
            :class="stepCardBorderClass(step.status)"
          >
            <div class="space-y-2">
              <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                  {{ labels.stepPrefix }} {{ idx + 1 }}
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border" :class="stepBadgeClass(step.status)">
                  {{ stepStatusText(step.status) }}
                </span>
              </div>

              <div>
                <p class="text-[11px] font-semibold text-purple-600 dark:text-purple-400 line-clamp-1">
                  {{ isKm ? step.category_km : step.category_en }}
                </p>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2 mt-0.5">
                  {{ isKm ? step.title_km : step.title_en }}
                </h3>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                {{ isKm ? step.desc_km : step.desc_en }}
              </p>
            </div>

            <div class="space-y-3">
              <div class="space-y-1.5">
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                  <span>{{ labels.progressLabel }}: <strong class="text-slate-800 dark:text-slate-200">{{ step.progress }}%</strong></span>
                  <span>{{ step.duration }}</span>
                </div>
                <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="step.status === 'COMPLETED' ? 'bg-emerald-500' : (step.status === 'CURRENT' ? 'bg-purple-600' : 'bg-amber-500')"
                    :style="{ width: `${step.progress}%` }"
                  ></div>
                </div>
              </div>

              <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-200/70 dark:border-[#2d2f31]">
                <Link
                  :href="step.learnUrl"
                  class="px-3 py-1.5 rounded-lg text-[11px] font-bold inline-flex items-center gap-1 transition-colors"
                  :class="step.status === 'COMPLETED'
                    ? 'bg-emerald-600/10 text-emerald-700 hover:bg-emerald-600/20 dark:bg-emerald-500/20 dark:text-emerald-300'
                    : (step.status === 'CURRENT'
                      ? 'bg-purple-600 text-white hover:bg-purple-700 shadow-xs'
                      : (step.status === 'AI_RECOMMENDED'
                        ? 'bg-amber-500 text-white hover:bg-amber-600 shadow-xs'
                        : 'bg-slate-200/80 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200'))"
                >
                  <span>
                    {{ step.status === 'COMPLETED'
                      ? labels.stepActionCompleted
                      : (step.status === 'CURRENT'
                        ? labels.stepActionCurrent
                        : (step.status === 'AI_RECOMMENDED' ? labels.stepActionRecommended : labels.stepActionUpcoming)) }}
                  </span>
                  <ArrowRight class="w-3 h-3" />
                </Link>

                <button
                  type="button"
                  @click="openStepModal(step)"
                  class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition-colors cursor-pointer"
                >
                  {{ labels.stepDetails }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Honest Unenrolled Empty State (+ optional published catalog courses) -->
        <div v-else class="rounded-2xl border border-dashed border-slate-200 dark:border-[#323539] bg-slate-50/70 dark:bg-[#141517] p-6 sm:p-8 text-center space-y-4">
          <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 flex items-center justify-center mx-auto">
            <Compass class="w-6 h-6" />
          </div>
          <div class="max-w-md mx-auto space-y-1">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
              {{ labels.emptyJourneyTitle }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
              {{ labels.emptyJourneyDesc }}
            </p>
          </div>
          <div>
            <Link
              href="/student/browse"
              class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center gap-2 transition-colors shadow-xs"
            >
              <BookOpen class="w-4 h-4" />
              <span>{{ labels.browseCourses }}</span>
            </Link>
          </div>

          <div v-if="pathState.suggestedCourses.length > 0" class="pt-4 border-t border-slate-200/80 dark:border-[#2d2f31] text-left space-y-2.5">
            <p class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ labels.suggestedCoursesLabel }}</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <Link
                v-for="course in pathState.suggestedCourses"
                :key="course.id"
                :href="course.url"
                class="rounded-xl border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-3.5 hover:border-purple-300 dark:hover:border-purple-500/40 transition-colors flex flex-col justify-between gap-2"
              >
                <div>
                  <span class="text-[10px] font-bold uppercase text-purple-600 dark:text-purple-400">
                    {{ isKm ? course.major_km : course.major_en }}
                  </span>
                  <p class="text-xs font-bold text-slate-900 dark:text-white mt-0.5 line-clamp-2">
                    {{ isKm ? course.title_km : course.title_en }}
                  </p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                  <span>{{ isKm ? course.instructor_km : course.instructor_en }}</span>
                  <ArrowRight class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" />
                </div>
              </Link>
            </div>
          </div>
        </div>
      </section>

      <!-- 5. CURRENT LEARNING FOCUS & AI RECOMMENDED NEXT STEP -->
      <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Current Learning Focus -->
        <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs flex flex-col justify-between gap-4">
          <div class="space-y-3">
            <h2 class="text-base font-bold text-slate-900 dark:text-white inline-flex items-center gap-2">
              <PlayCircle class="w-5 h-5 text-purple-600 dark:text-purple-400" />
              <span>{{ labels.currentFocusTitle }}</span>
            </h2>

            <template v-if="pathState.currentFocus">
              <div class="rounded-2xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50 dark:bg-[#141517] p-4 space-y-2.5">
                <div class="flex items-center justify-between gap-2">
                  <span class="text-xs font-semibold text-purple-700 dark:text-purple-300">
                    {{ isKm ? pathState.currentFocus.courseTitle_km : pathState.currentFocus.courseTitle_en }}
                  </span>
                  <span class="text-xs font-black text-slate-900 dark:text-white">
                    {{ pathState.currentFocus.progress }}%
                  </span>
                </div>
                <p class="text-sm font-bold text-slate-900 dark:text-white">
                  {{ isKm ? pathState.currentFocus.lessonTitle_km : pathState.currentFocus.lessonTitle_en }}
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{ isKm ? pathState.currentFocus.moduleTitle_km : pathState.currentFocus.moduleTitle_en }} • {{ pathState.currentFocus.lessonsDone }}/{{ pathState.currentFocus.lessonsTotal }} {{ labels.lessonsUnit }}
                </p>
                <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                  <div
                    class="h-full rounded-full bg-purple-600 transition-all duration-300"
                    :style="{ width: `${pathState.currentFocus.progress}%` }"
                  ></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-1">
                  <span>{{ labels.lastActive }} {{ isKm ? pathState.currentFocus.lastActive_km : pathState.currentFocus.lastActive_en }}</span>
                  <span>{{ labels.remaining }} {{ isKm ? pathState.currentFocus.remaining_km : pathState.currentFocus.remaining_en }}</span>
                </div>
              </div>
            </template>

            <div v-else class="rounded-2xl border border-dashed border-slate-200 dark:border-[#323539] bg-slate-50/70 dark:bg-[#141517] p-5 space-y-2">
              <p class="text-sm font-bold text-slate-900 dark:text-white">{{ labels.noFocusTitle }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ labels.noFocusDesc }}</p>
            </div>
          </div>

          <div v-if="pathState.currentFocus" class="flex items-center gap-2.5 flex-wrap">
            <Link
              :href="pathState.currentFocus.learnUrl"
              class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center gap-1.5 transition-colors"
            >
              <span>{{ labels.resumeLesson }}</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </Link>
            <Link
              :href="pathState.currentFocus.overviewUrl"
              class="px-4 py-2 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-white hover:bg-slate-50 dark:bg-[#252729] dark:hover:bg-[#2d2f31] text-slate-700 dark:text-slate-200 text-xs font-semibold transition-colors"
            >
              {{ labels.courseOverview }}
            </Link>
          </div>
          <div v-else>
            <Link
              href="/student/browse"
              class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center gap-1.5 transition-colors"
            >
              <span>{{ labels.browseCourses }}</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>

        <!-- AI Recommended Next Step -->
        <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs flex flex-col justify-between gap-4">
          <div class="space-y-3">
            <h2 class="text-base font-bold text-slate-900 dark:text-white inline-flex items-center gap-2">
              <Sparkles class="w-5 h-5 text-amber-500" />
              <span>{{ labels.aiNextTitle }}</span>
            </h2>

            <template v-if="pathState.aiNextStep">
              <div class="rounded-2xl border border-amber-200/80 dark:border-amber-500/30 bg-amber-50/50 dark:bg-amber-950/20 p-4 space-y-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-500/20 dark:text-amber-300 dark:border-amber-500/30 inline-block">
                  {{ isKm ? pathState.aiNextStep.badge_km : pathState.aiNextStep.badge_en }}
                </span>
                <p class="text-sm font-bold text-slate-900 dark:text-white">
                  {{ isKm ? pathState.aiNextStep.title_km : pathState.aiNextStep.title_en }}
                </p>
                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                  {{ isKm ? pathState.aiNextStep.reason_km : pathState.aiNextStep.reason_en }}
                </p>
              </div>
            </template>

            <div v-else class="rounded-2xl border border-dashed border-slate-200 dark:border-[#323539] bg-slate-50/70 dark:bg-[#141517] p-5 space-y-2">
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ labels.noNextStepDesc }}</p>
            </div>
          </div>

          <div v-if="pathState.aiNextStep" class="flex items-center gap-2.5 flex-wrap">
            <button
              v-if="pathState.aiNextStep.canPracticeDrill"
              type="button"
              @click="startPracticeDrill"
              class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
            >
              <HelpCircle class="w-3.5 h-3.5" />
              <span>{{ isKm ? pathState.aiNextStep.primaryLabel_km : pathState.aiNextStep.primaryLabel_en }}</span>
            </button>
            <Link
              v-else
              :href="pathState.aiNextStep.primaryHref"
              class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold inline-flex items-center gap-1.5 transition-colors"
            >
              <span>{{ isKm ? pathState.aiNextStep.primaryLabel_km : pathState.aiNextStep.primaryLabel_en }}</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </Link>
            <Link
              :href="pathState.aiNextStep.askAiUrl"
              class="px-4 py-2 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-white hover:bg-slate-50 dark:bg-[#252729] dark:hover:bg-[#2d2f31] text-slate-700 dark:text-slate-200 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors"
            >
              <img :src="'/images/ai-robot-avatar.png'" alt="" class="w-3.5 h-3.5 rounded-xs object-contain" />
              <span>{{ labels.askAi }}</span>
            </Link>
          </div>
          <div v-else>
            <Link
              href="/student/ai-tutor"
              class="px-4 py-2 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-white hover:bg-slate-50 dark:bg-[#252729] dark:hover:bg-[#2d2f31] text-slate-700 dark:text-slate-200 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors"
            >
              <img :src="'/images/ai-robot-avatar.png'" alt="" class="w-3.5 h-3.5 rounded-xs object-contain" />
              <span>{{ labels.askAi }}</span>
            </Link>
          </div>
        </div>
      </section>

      <!-- 6. SKILL PROGRESS, WEAK TOPICS & TODAY'S AI STUDY PLAN -->
      <section class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left Column: Skill Progress + Weak Topics -->
        <div class="lg:col-span-6 space-y-6">
          <!-- Skill Progress -->
          <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs space-y-4">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ labels.skillProgressTitle }}</h2>

            <div v-if="pathState.skillProgress.length > 0" class="space-y-3">
              <Link
                v-for="skill in pathState.skillProgress"
                :key="skill.courseId"
                :href="skill.href"
                class="block rounded-xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50/70 dark:bg-[#141517] p-3.5 hover:border-purple-300 dark:hover:border-purple-500/40 transition-colors space-y-2"
              >
                <div class="flex items-center justify-between gap-2">
                  <span class="text-xs font-bold text-slate-900 dark:text-white line-clamp-1">
                    {{ isKm ? skill.name_km : skill.name_en }}
                  </span>
                  <div class="flex items-center gap-2 shrink-0 text-xs">
                    <span
                      class="font-semibold"
                      :class="skill.isWarning ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400'"
                    >
                      {{ isKm ? skill.status_km : skill.status_en }}
                    </span>
                    <strong class="text-slate-900 dark:text-white">{{ skill.level }}%</strong>
                  </div>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                  <div
                    class="h-full rounded-full bg-gradient-to-r transition-all duration-300"
                    :class="skillBarColor(skill)"
                    :style="{ width: `${skill.level}%` }"
                  ></div>
                </div>
              </Link>
            </div>

            <div v-else class="rounded-xl border border-dashed border-slate-200 dark:border-[#323539] bg-slate-50/70 dark:bg-[#141517] p-5 text-center">
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ labels.noSkillsDesc }}</p>
            </div>
          </div>

          <!-- Weak Topics Detected -->
          <div class="rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs space-y-4">
            <h2 class="text-base font-bold text-slate-900 dark:text-white inline-flex items-center gap-2">
              <AlertTriangle class="w-4 h-4 text-rose-500" />
              <span>{{ labels.weakTopicsTitle }}</span>
            </h2>

            <div v-if="pathState.weakTopics.length > 0" class="space-y-3">
              <div
                v-for="topic in pathState.weakTopics"
                :key="topic.id"
                class="rounded-xl border border-rose-200 dark:border-rose-500/30 bg-rose-50/40 dark:bg-rose-950/15 p-4 space-y-2.5"
              >
                <div class="flex items-start justify-between gap-2">
                  <div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">
                      {{ isKm ? topic.topic_km : topic.topic_en }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                      {{ isKm ? topic.course_km : topic.course_en }}
                    </p>
                  </div>
                  <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 border border-rose-200 dark:bg-rose-500/20 dark:text-rose-300 dark:border-rose-500/30 text-xs font-black shrink-0">
                    {{ topic.score }}%
                  </span>
                </div>
                <p class="text-xs text-slate-600 dark:text-slate-300">
                  {{ isKm ? topic.reason_km : topic.reason_en }}
                </p>
                <div class="flex items-center gap-2 pt-1 flex-wrap">
                  <Link
                    :href="topic.practiceUrl"
                    class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-colors"
                  >
                    {{ labels.practiceBtn }}
                  </Link>
                  <Link
                    :href="topic.askAiUrl"
                    class="px-3 py-1.5 rounded-lg bg-purple-600/10 hover:bg-purple-600/20 text-purple-700 border border-purple-200 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-500/30 text-xs font-bold inline-flex items-center gap-1 transition-colors"
                  >
                    <span>{{ labels.askAi }}</span>
                    <ArrowRight class="w-3 h-3" />
                  </Link>
                </div>
              </div>
            </div>

            <div v-else class="rounded-xl border border-dashed border-emerald-200 dark:border-emerald-500/30 bg-emerald-50/40 dark:bg-emerald-950/15 p-5 text-center space-y-1">
              <CheckCircle2 class="w-6 h-6 text-emerald-600 dark:text-emerald-400 mx-auto" />
              <p class="text-xs font-bold text-slate-900 dark:text-white">{{ labels.noWeakTopicsTitle }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ labels.noWeakTopicsDesc }}</p>
            </div>
          </div>
        </div>

        <!-- Right Column: Today's AI Study Plan (Real student_goals DB CRUD) -->
        <div class="lg:col-span-6 rounded-[20px] border border-slate-200 dark:border-[#2d2f31] bg-white dark:bg-[#1e1f20] p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between gap-2">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ labels.todayPlanTitle }}</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ pathState.todayGoals.completedCount }} / {{ pathState.todayGoals.totalCount }} {{ labels.tasksCompletedSuffix }}
              </p>
            </div>
            <span class="text-sm font-black text-purple-600 dark:text-purple-400">
              {{ pathState.todayGoals.percentage }}%
            </span>
          </div>

          <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
            <div
              class="h-full rounded-full bg-purple-600 transition-all duration-300"
              :style="{ width: `${pathState.todayGoals.percentage}%` }"
            ></div>
          </div>

          <!-- Add Goal Form -->
          <form class="flex items-center gap-2" @submit.prevent="addGoal()">
            <input
              v-model="newGoalTitle"
              type="text"
              maxlength="255"
              :placeholder="labels.addGoalPlaceholder"
              class="flex-1 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-slate-50 dark:bg-[#141517] px-3.5 py-2 text-xs text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/40"
            />
            <button
              type="submit"
              :disabled="!newGoalTitle.trim() || isGoalBusy"
              class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center gap-1.5 transition-colors cursor-pointer disabled:opacity-50"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>{{ labels.addGoalBtn }}</span>
            </button>
          </form>

          <!-- Real Goal List -->
          <div v-if="pathState.todayGoals.items.length > 0" class="space-y-2.5">
            <div
              v-for="goal in pathState.todayGoals.items"
              :key="goal.id"
              class="rounded-xl border border-slate-200 dark:border-[#2d2f31] bg-slate-50/70 dark:bg-[#141517] px-3.5 py-3 flex items-center justify-between gap-3"
            >
              <button
                type="button"
                @click="toggleGoal(goal)"
                class="flex items-center gap-3 text-left flex-1 min-w-0 cursor-pointer"
              >
                <CheckCircle2 v-if="goal.isCompleted" class="w-5 h-5 text-emerald-500 shrink-0" />
                <Circle v-else class="w-5 h-5 text-slate-400 shrink-0" />
                <span
                  class="text-xs font-medium truncate"
                  :class="goal.isCompleted
                    ? 'line-through text-slate-400 dark:text-slate-500'
                    : 'text-slate-900 dark:text-white'"
                >
                  {{ isKm ? goal.titleKm : goal.titleEn }}
                </span>
              </button>

              <div class="flex items-center gap-1.5 shrink-0">
                <Link
                  v-if="goal.targetRoute"
                  :href="goal.targetRoute"
                  class="px-2.5 py-1 rounded-lg bg-purple-600/10 hover:bg-purple-600/20 text-purple-700 dark:bg-purple-500/20 dark:text-purple-300 text-[11px] font-bold inline-flex items-center gap-1 transition-colors"
                >
                  <span>{{ labels.openLink }}</span>
                  <ExternalLink class="w-3 h-3" />
                </Link>
                <button
                  type="button"
                  :title="labels.deleteGoal"
                  @click="deleteGoal(goal.id)"
                  class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/15 transition-colors cursor-pointer"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Honest Empty Goals State + Starter Suggestions -->
          <div v-else class="rounded-xl border border-dashed border-slate-200 dark:border-[#323539] bg-slate-50/70 dark:bg-[#141517] p-5 space-y-3">
            <div class="space-y-1">
              <p class="text-xs font-bold text-slate-900 dark:text-white">{{ labels.noGoalsTitle }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ labels.noGoalsDesc }}</p>
            </div>
            <div v-if="pathState.todayGoals.quickSuggestions.length > 0" class="flex flex-wrap gap-2">
              <button
                v-for="(sugg, sIdx) in pathState.todayGoals.quickSuggestions"
                :key="sIdx"
                type="button"
                @click="addGoal(sugg.title, sugg.title_km, sugg.target_route)"
                class="px-3 py-1.5 rounded-lg border border-purple-200 dark:border-purple-500/30 bg-white dark:bg-[#1e1f20] hover:bg-purple-50 dark:hover:bg-purple-500/15 text-xs font-medium text-purple-700 dark:text-purple-300 inline-flex items-center gap-1.5 transition-colors cursor-pointer"
              >
                <Plus class="w-3 h-3 shrink-0" />
                <span>{{ isKm ? sugg.title_km : sugg.title }}</span>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- STEP DETAIL MODAL -->
      <div
        v-if="isStepModalOpen && selectedStep"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
        @click.self="isStepModalOpen = false"
      >
        <div class="w-full max-w-lg rounded-[22px] border border-slate-200 dark:border-[#3c4043] bg-white dark:bg-[#1e1f20] p-6 shadow-2xl space-y-4">
          <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold border inline-block" :class="stepBadgeClass(selectedStep.status)">
                {{ stepStatusText(selectedStep.status) }}
              </span>
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                {{ isKm ? selectedStep.title_km : selectedStep.title_en }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ isKm ? selectedStep.category_km : selectedStep.category_en }}
              </p>
            </div>
            <button
              type="button"
              @click="isStepModalOpen = false"
              class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
            {{ isKm ? selectedStep.desc_km : selectedStep.desc_en }}
          </p>

          <div class="grid grid-cols-2 gap-3 text-xs bg-slate-50 dark:bg-[#141517] rounded-xl p-3 border border-slate-200/80 dark:border-[#2d2f31]">
            <div>
              <span class="text-slate-500 dark:text-slate-400 block">{{ labels.modalDuration }}</span>
              <strong class="text-slate-900 dark:text-white">{{ selectedStep.duration }}</strong>
            </div>
            <div>
              <span class="text-slate-500 dark:text-slate-400 block">{{ labels.modalPrereq }}</span>
              <strong class="text-slate-900 dark:text-white">
                {{ isKm ? selectedStep.prerequisites_km : selectedStep.prerequisites_en }}
              </strong>
            </div>
          </div>

          <div v-if="(isKm ? selectedStep.skills_km : selectedStep.skills_en).length > 0" class="space-y-2">
            <p class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ labels.modalLessons }}</p>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="(skillName, idx) in (isKm ? selectedStep.skills_km : selectedStep.skills_en)"
                :key="idx"
                class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-[#252729] text-xs font-medium text-slate-700 dark:text-slate-200"
              >
                {{ skillName }}
              </span>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-200 dark:border-[#2d2f31]">
            <Link
              :href="selectedStep.askAiUrl"
              class="px-4 py-2 rounded-xl border border-slate-200 dark:border-[#3c4043] bg-white hover:bg-slate-50 dark:bg-[#252729] text-slate-700 dark:text-slate-200 text-xs font-bold inline-flex items-center gap-1.5"
            >
              <span>{{ labels.askAi }}</span>
            </Link>
            <Link
              :href="selectedStep.learnUrl"
              class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center gap-1.5"
            >
              <span>{{ labels.resumeLesson }}</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>
      </div>

      <!-- PRACTICE DRILL MODAL (Strictly from Real DB MCQ Questions) -->
      <div
        v-if="isPracticeModalOpen && currentQuestion"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
        @click.self="isPracticeModalOpen = false"
      >
        <div class="w-full max-w-lg rounded-[22px] border border-slate-200 dark:border-[#3c4043] bg-white dark:bg-[#1e1f20] p-6 shadow-2xl space-y-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <span class="text-[11px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">
                {{ labels.drillTitle }} — {{ currentQuestion.quizTitle }}
              </span>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ labels.drillQuestionOf }} {{ currentPracticeIndex + 1 }} {{ labels.drillOf }} {{ pathState.practiceQuestions.length }}
              </p>
            </div>
            <button
              type="button"
              @click="isPracticeModalOpen = false"
              class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white cursor-pointer"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <p class="text-sm font-bold text-slate-900 dark:text-white leading-relaxed">
            {{ currentQuestion.question }}
          </p>

          <div class="space-y-2">
            <button
              v-for="(option, optIdx) in currentQuestion.options"
              :key="optIdx"
              type="button"
              :disabled="practiceSubmitted"
              @click="submitPracticeAnswer(optIdx)"
              class="w-full text-left px-4 py-3 rounded-xl border text-xs font-medium transition-colors flex items-center justify-between gap-2 cursor-pointer"
              :class="practiceSubmitted
                ? (optIdx === currentQuestion.correctIndex
                  ? 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200'
                  : (optIdx === practiceSelectedAnswer
                    ? 'border-rose-500 bg-rose-50 text-rose-800 dark:bg-rose-500/20 dark:text-rose-200'
                    : 'border-slate-200 dark:border-[#2d2f31] text-slate-500 dark:text-slate-400'))
                : 'border-slate-200 dark:border-[#2d2f31] bg-slate-50 dark:bg-[#141517] text-slate-800 dark:text-slate-100 hover:border-purple-400'"
            >
              <span>{{ option }}</span>
              <Check v-if="practiceSubmitted && optIdx === currentQuestion.correctIndex" class="w-4 h-4 text-emerald-600 shrink-0" />
            </button>
          </div>

          <div v-if="practiceSubmitted" class="space-y-3 pt-2 border-t border-slate-200 dark:border-[#2d2f31]">
            <p
              class="text-xs font-semibold"
              :class="practiceSelectedAnswer === currentQuestion.correctIndex ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
            >
              {{ practiceSelectedAnswer === currentQuestion.correctIndex ? labels.drillCorrect : labels.drillWrong }}
            </p>
            <div class="flex justify-end">
              <button
                type="button"
                @click="nextPracticeQuestion"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold inline-flex items-center gap-1.5 cursor-pointer"
              >
                <span>{{ currentPracticeIndex + 1 < pathState.practiceQuestions.length ? labels.drillNext : labels.drillFinish }}</span>
                <ArrowRight class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </StudentLayout>
</template>
