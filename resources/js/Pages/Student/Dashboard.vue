<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { i18n } from '@/Services/i18n'

interface CourseItem {
  id: number
  title: string
  code?: string
  category?: string
  teacher: string
  progress: number
  lessonsDone: number
  lessonsTotal: number
  status: 'in_progress' | 'completed' | 'not_started' | 'no_content'
  statusLabel: string
  thumbnail?: string | null
  href: string
  continueHref: string
}

interface DailyBar {
  date: string
  label: string
  seconds: number
  minutes: number
  hours: number
}

interface GoalItem {
  id: string
  titleEn: string
  titleKm?: string
  completed: boolean
  targetRoute?: string
}

const props = defineProps<{
  stats?: {
    enrolledCount: number
    inProgressCount: number
    completedCount: number
    certificatesCount: number
    learningTime: string
    totalSeconds: number
    averageScore: string
    averageScoreNum: number | null
  }
  continueCourse?: {
    id: number
    title: string
    chapter: string
    teacher: string
    progress: number
    nextLesson: string
    timeLeft?: string | null
    thumbnail?: string | null
    href: string
    overviewHref: string
  } | null
  dbCourses?: CourseItem[]
  recentActivities?: Array<{
    id: string
    type: 'lesson' | 'quiz'
    title: string
    course: string
    time: string
    href: string
    progress: number
    completed: boolean
  }>
  upcomingEvents?: Array<{
    id: number
    title: string
    course: string
    meta: string
    href: string
  }>
  todayGoals?: {
    completedCount: number
    totalCount: number
    percentage: number
    items: GoalItem[]
  }
  studyTimeBreakdown?: {
    total: string
    totalMinutes: number
    lessons: string
    lessonsMinutes: number
    practice: string
    practiceMinutes: number
    quiz: string
    quizMinutes: number
    dailyAverage: string
    thisWeekTotal: string
    weekTrendPct: number | null
    dailyBars: DailyBar[]
  }
  quizPerformance?: {
    hasData: boolean
    attemptsCount: number
    averageScore: string
    averageScoreNum: number | null
    highestScore: string
    trend: string | null
  }
  aiRecommendations?: Array<{
    id: string
    title: string
    title_km?: string
    desc: string
    desc_km?: string
    actionLabel: string
    actionHref: string
  }>
  achievements?: Array<{
    id: string
    title: string
    title_km?: string
    unlocked: boolean
    progress: string
    href: string
  }>
}>()

const page = usePage()
const user = computed(() => (page.props as any)?.auth?.user || {})
const currentLang = computed<'km' | 'en'>(() => (i18n.locale.value === 'km' ? 'km' : 'en'))

// One-time mount draw flag (respects prefers-reduced-motion)
const hasAnimatedOnce = ref(false)
const reducedMotion = ref(false)

// Time-of-day greeting
const timeGreeting = computed(() => {
  const hour = new Date().getHours()
  const name = user.value?.name?.trim() || (currentLang.value === 'km' ? 'សិស្ស' : 'Student')

  if (currentLang.value === 'km') {
    if (hour < 12) return `អរុណសួស្តី, ${name}`
    if (hour < 17) return `ទិវាសួស្តី, ${name}`
    return `សាយ័ណ្ហសួស្តី, ${name}`
  }

  if (hour < 12) return `Good morning, ${name}`
  if (hour < 17) return `Good afternoon, ${name}`
  return `Good evening, ${name}`
})

// Count-up display values (single pass on mount if motion allowed)
const animatedStats = ref({
  enrolled: props.stats?.enrolledCount ?? 0,
  inProgress: props.stats?.inProgressCount ?? 0,
  completed: props.stats?.completedCount ?? 0,
  certificates: props.stats?.certificatesCount ?? 0,
})

const runCountUpOnce = () => {
  const target = {
    enrolled: props.stats?.enrolledCount ?? 0,
    inProgress: props.stats?.inProgressCount ?? 0,
    completed: props.stats?.completedCount ?? 0,
    certificates: props.stats?.certificatesCount ?? 0,
  }
  if (reducedMotion.value) {
    animatedStats.value = target
    hasAnimatedOnce.value = true
    return
  }
  const duration = 450
  const start = performance.now()
  const step = (now: number) => {
    const t = Math.min(1, (now - start) / duration)
    const ease = 1 - Math.pow(1 - t, 3)
    animatedStats.value = {
      enrolled: Math.round(target.enrolled * ease),
      inProgress: Math.round(target.inProgress * ease),
      completed: Math.round(target.completed * ease),
      certificates: Math.round(target.certificates * ease),
    }
    if (t < 1) {
      requestAnimationFrame(step)
    } else {
      hasAnimatedOnce.value = true
    }
  }
  requestAnimationFrame(step)
}

// Course filter tabs
const courseFilter = ref<'all' | 'in_progress' | 'completed' | 'not_started'>('all')
const allCourses = computed<CourseItem[]>(() => props.dbCourses || [])
const filteredCourses = computed<CourseItem[]>(() => {
  if (courseFilter.value === 'all') return allCourses.value.slice(0, 6)
  if (courseFilter.value === 'not_started') {
    return allCourses.value.filter(c => c.status === 'not_started' || c.status === 'no_content').slice(0, 6)
  }
  return allCourses.value.filter(c => c.status === courseFilter.value).slice(0, 6)
})

// 7-day bar chart scaling
const dailyBars = computed<DailyBar[]>(() => props.studyTimeBreakdown?.dailyBars || [])
const maxDailyMinutes = computed(() => {
  const max = Math.max(...dailyBars.value.map(b => b.minutes), 0)
  return max > 0 ? max : 60
})

// Today's Goal (synced with server + localStorage)
const goalStorageKey = computed(() => `spi_study_desk_goals_${user.value?.id || 'guest'}`)
const goalItems = ref<GoalItem[]>([])
const isAddingGoal = ref(false)
const newGoalInput = ref('')
const goalInputEl = ref<HTMLInputElement | null>(null)

const loadGoals = () => {
  const serverItems = props.todayGoals?.items || []
  if (typeof window !== 'undefined') {
    try {
      const saved = localStorage.getItem(goalStorageKey.value)
      if (saved) {
        const parsed = JSON.parse(saved)
        if (Array.isArray(parsed)) {
          goalItems.value = parsed
          return
        }
      }
    } catch {}
  }
  goalItems.value = serverItems.map(item => ({ ...item }))
}

const saveGoals = () => {
  if (typeof window !== 'undefined') {
    try {
      localStorage.setItem(goalStorageKey.value, JSON.stringify(goalItems.value))
    } catch {}
  }
}

const toggleGoal = (goal: GoalItem) => {
  goal.completed = !goal.completed
  saveGoals()
}

const openAddGoal = async () => {
  isAddingGoal.value = !isAddingGoal.value
  if (isAddingGoal.value) {
    await nextTick()
    goalInputEl.value?.focus()
  }
}

const addGoal = () => {
  const text = newGoalInput.value.trim()
  if (!text) return
  goalItems.value.push({
    id: `custom-${Date.now()}`,
    titleEn: text,
    titleKm: text,
    completed: false,
    targetRoute: '/student/my-courses/enrolled',
  })
  newGoalInput.value = ''
  isAddingGoal.value = false
  saveGoals()
}

const removeGoal = (id: string) => {
  goalItems.value = goalItems.value.filter(g => g.id !== id)
  saveGoals()
}

const goalCompletedCount = computed(() => goalItems.value.filter(g => g.completed).length)
const goalTotalCount = computed(() => goalItems.value.length)
const goalPercent = computed(() =>
  goalTotalCount.value === 0 ? 0 : Math.round((goalCompletedCount.value / goalTotalCount.value) * 100)
)

const unlockedAchievementsCount = computed(() =>
  (props.achievements || []).filter(a => a.unlocked).length
)

onMounted(() => {
  if (typeof window !== 'undefined') {
    reducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  }
  loadGoals()
  runCountUpOnce()
})
</script>

<template>
  <StudentLayout
    :title="currentLang === 'km' ? 'ទំព័រដើម' : 'Dashboard'"
    :breadcrumbs="[{ label: currentLang === 'km' ? 'ទំព័រដើម' : 'Dashboard' }]"
  >
    <div class="student-dashboard space-y-6 pb-20">
      <!-- 1. CALM HEADER (Time-aware greeting + academic term + clean actions) -->
      <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-2 border-b border-[var(--desk-border)]">
        <div class="min-w-0 space-y-1">
          <p class="text-[13px] font-medium text-[var(--desk-muted)]">
            {{ currentLang === 'km' ? 'ឆមាសសិក្សា ២០២៦–២០២៧' : 'Academic term 2026–2027' }}
          </p>
          <h1 class="text-[28px] leading-[1.2] font-bold tracking-[-0.02em] text-[var(--desk-text)] truncate">
            {{ timeGreeting }}
          </h1>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
          <Link
            href="/student/browse"
            class="desk-btn desk-btn-secondary min-h-[48px] sm:min-h-[38px] px-4 inline-flex items-center justify-center text-[13px] font-medium"
          >
            {{ currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា' : 'Browse courses' }}
          </Link>
          <Link
            href="/student/notifications/announcements"
            class="desk-btn desk-btn-secondary min-h-[48px] sm:min-h-[38px] px-4 inline-flex items-center justify-center text-[13px] font-medium"
          >
            {{ currentLang === 'km' ? 'សេចក្តីប្រកាស' : 'Announcements' }}
          </Link>
        </div>
      </header>

      <!-- 2. CONTINUE LEARNING (Primary visual focus) -->
      <section aria-label="Continue learning">
        <div v-if="continueCourse" class="desk-card p-5 flex flex-col md:flex-row md:items-center gap-5">
          <div class="w-full md:w-52 h-32 rounded-[8px] overflow-hidden bg-slate-100 dark:bg-slate-800 border border-[var(--desk-border)] shrink-0">
            <img
              v-if="continueCourse.thumbnail"
              :src="continueCourse.thumbnail"
              :alt="continueCourse.title"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-[13px] text-[var(--desk-muted)]">
              {{ continueCourse.chapter }}
            </div>
          </div>

          <div class="flex-1 min-w-0 space-y-2.5">
            <div class="flex flex-wrap items-center gap-2 text-[13px] text-[var(--desk-muted)]">
              <span>{{ continueCourse.chapter }}</span>
              <span aria-hidden="true">·</span>
              <span>{{ continueCourse.teacher }}</span>
              <template v-if="continueCourse.timeLeft">
                <span aria-hidden="true">·</span>
                <span class="tabular-nums">{{ continueCourse.timeLeft }}</span>
              </template>
            </div>

            <h2 class="text-[20px] leading-[1.3] font-bold text-[var(--desk-text)] truncate">
              {{ continueCourse.title }}
            </h2>

            <p class="text-[14px] text-[var(--desk-muted)] truncate">
              <span class="font-medium text-[var(--desk-text)]">
                {{ currentLang === 'km' ? 'បន្ទាប់៖ ' : 'Next: ' }}
              </span>
              {{ continueCourse.nextLesson }}
            </p>

            <div class="flex items-center gap-3 pt-1">
              <div class="flex-1 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-500 ease-out"
                  :class="continueCourse.progress >= 100 ? 'bg-[var(--desk-done)]' : 'bg-[var(--brand)]'"
                  :style="{ width: (hasAnimatedOnce || reducedMotion ? continueCourse.progress : 0) + '%' }"
                ></div>
              </div>
              <span class="text-[13px] font-semibold tabular-nums text-[var(--desk-text)]">
                {{ continueCourse.progress }}%
              </span>
            </div>
          </div>

          <div class="shrink-0">
            <Link
              :href="continueCourse.href"
              class="desk-btn desk-btn-primary min-h-[48px] sm:min-h-[40px] px-5 inline-flex items-center justify-center w-full md:w-auto text-[14px] font-semibold"
            >
              {{ currentLang === 'km' ? 'បន្តការសិក្សា' : 'Continue learning' }}
            </Link>
          </div>
        </div>

        <div v-else class="desk-card p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1">
            <h2 class="text-[16px] font-bold text-[var(--desk-text)]">
              {{ currentLang === 'km' ? 'មិនទាន់មានវគ្គសិក្សាកំពុងរៀនទេ' : 'No active course yet' }}
            </h2>
            <p class="text-[13px] text-[var(--desk-muted)]">
              {{ currentLang === 'km' ? 'ស្វែងរក និងចុះឈ្មោះចូលរៀនវគ្គសិក្សាដំបូងរបស់អ្នក ដើម្បីចាប់ផ្តើម។' : 'Browse the course catalog and enroll to begin tracking your progress.' }}
            </p>
          </div>
          <Link
            href="/student/browse"
            class="desk-btn desk-btn-primary min-h-[48px] sm:min-h-[40px] px-5 inline-flex items-center justify-center text-[13px] font-semibold shrink-0"
          >
            {{ currentLang === 'km' ? 'ស្វែងរកវគ្គសិក្សា' : 'Browse courses' }}
          </Link>
        </div>
      </section>

      <!-- 3. STAT STRIP (Single flat card, 6 values with dividers, no icons, 2x3 on mobile) -->
      <section aria-label="Study summary" class="desk-card overflow-hidden">
        <div class="grid grid-cols-2 md:grid-cols-6 divide-y md:divide-y-0 md:divide-x divide-[var(--desk-border)]">
          <div class="p-4">
            <div class="text-[13px] text-[var(--desk-muted)]">{{ currentLang === 'km' ? 'វគ្គបានចុះឈ្មោះ' : 'Enrolled' }}</div>
            <div class="text-[20px] font-bold tabular-nums text-[var(--desk-text)] mt-1">{{ animatedStats.enrolled }}</div>
          </div>
          <div class="p-4 border-l border-[var(--desk-border)] md:border-l-0">
            <div class="text-[13px] text-[var(--desk-muted)]">{{ currentLang === 'km' ? 'កំពុងសិក្សា' : 'In progress' }}</div>
            <div class="text-[20px] font-bold tabular-nums text-[var(--desk-text)] mt-1">{{ animatedStats.inProgress }}</div>
          </div>
          <div class="p-4">
            <div class="text-[13px] text-[var(--desk-muted)]">{{ currentLang === 'km' ? 'បានបញ្ចប់' : 'Completed' }}</div>
            <div class="text-[20px] font-bold tabular-nums text-[var(--desk-text)] mt-1">{{ animatedStats.completed }}</div>
          </div>
          <div class="p-4 border-l border-[var(--desk-border)] md:border-l-0">
            <div class="text-[13px] text-[var(--desk-muted)]">{{ currentLang === 'km' ? 'វិញ្ញាបនបត្រ' : 'Certificates' }}</div>
            <div class="text-[20px] font-bold tabular-nums text-[var(--desk-text)] mt-1">{{ animatedStats.certificates }}</div>
          </div>
          <div class="p-4">
            <div class="text-[13px] text-[var(--desk-muted)]">{{ currentLang === 'km' ? 'ម៉ោងសិក្សា' : 'Study time' }}</div>
            <div class="text-[20px] font-bold tabular-nums text-[var(--desk-text)] mt-1">{{ stats?.learningTime || '0h 0m' }}</div>
          </div>
          <div class="p-4 border-l border-[var(--desk-border)] md:border-l-0">
            <div class="text-[13px] text-[var(--desk-muted)]">{{ currentLang === 'km' ? 'មធ្យមភាគ Quiz' : 'Quiz average' }}</div>
            <div class="text-[20px] font-bold tabular-nums text-[var(--desk-text)] mt-1">
              {{ quizPerformance?.hasData ? quizPerformance.averageScore : '—' }}
            </div>
          </div>
        </div>
      </section>

      <!-- 4. TWO-COLUMN DESK LAYOUT (Main | 360px Side) -->
      <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_360px] gap-6 items-start">
        <!-- MAIN COLUMN -->
        <div class="space-y-6 min-w-0">
          <!-- My courses -->
          <section class="desk-card p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <h2 class="text-[16px] font-bold text-[var(--desk-text)]">
                {{ currentLang === 'km' ? 'វគ្គសិក្សារបស់ខ្ញុំ' : 'My courses' }}
              </h2>
              <Link href="/student/my-courses/enrolled" class="text-[13px] font-medium text-[var(--brand)] hover:underline">
                {{ currentLang === 'km' ? 'មើលទាំងអស់' : 'View all' }} ({{ allCourses.length }})
              </Link>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
              <button
                v-for="tab in [
                  { id: 'all', en: 'All', km: 'ទាំងអស់' },
                  { id: 'in_progress', en: 'In progress', km: 'កំពុងរៀន' },
                  { id: 'completed', en: 'Completed', km: 'បានបញ្ចប់' },
                  { id: 'not_started', en: 'Not started', km: 'មិនទាន់រៀន' },
                ]"
                :key="tab.id"
                @click="courseFilter = tab.id as any"
                type="button"
                class="desk-chip px-3 py-1.5 text-[13px] font-medium cursor-pointer transition-colors"
                :class="courseFilter === tab.id ? 'bg-[var(--brand)] text-white' : 'bg-slate-100 dark:bg-slate-800 text-[var(--desk-muted)] hover:text-[var(--desk-text)]'"
              >
                {{ currentLang === 'km' ? tab.km : tab.en }}
              </button>
            </div>

            <div v-if="filteredCourses.length > 0" class="divide-y divide-[var(--desk-border)]">
              <Link
                v-for="course in filteredCourses"
                :key="course.id"
                :href="course.href"
                class="py-3.5 first:pt-1 last:pb-1 flex items-center justify-between gap-4 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 -mx-2 px-2 rounded-[8px] transition-colors"
              >
                <div class="min-w-0 flex-1 space-y-1">
                  <div class="flex items-center gap-2">
                    <span
                      class="inline-flex items-center gap-1 text-[12px] font-medium"
                      :class="course.status === 'completed' ? 'text-[var(--desk-done)]' : 'text-[var(--desk-muted)]'"
                    >
                      <svg v-if="course.status === 'completed'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                      </svg>
                      <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="8" />
                      </svg>
                      <span>{{ course.statusLabel }}</span>
                    </span>
                    <span class="text-[12px] text-[var(--desk-muted)]">·</span>
                    <span class="text-[12px] text-[var(--desk-muted)] truncate">{{ course.teacher }}</span>
                  </div>
                  <div class="text-[14px] font-semibold text-[var(--desk-text)] truncate">
                    {{ course.title }}
                  </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                  <div class="w-20 sm:w-28 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div
                      class="h-full rounded-full"
                      :class="course.status === 'completed' ? 'bg-[var(--desk-done)]' : 'bg-[var(--brand)]'"
                      :style="{ width: course.progress + '%' }"
                    ></div>
                  </div>
                  <span class="text-[13px] font-medium tabular-nums text-[var(--desk-text)] w-10 text-right">
                    {{ course.progress }}%
                  </span>
                </div>
              </Link>
            </div>

            <div v-else class="py-6 text-center text-[13px] text-[var(--desk-muted)]">
              {{ currentLang === 'km' ? 'គ្មានវគ្គសិក្សាក្នុងផ្នែកនេះទេ។ សាកល្បងជ្រើស Tab ផ្សេង ឬស្វែងរកវគ្គសិក្សាថ្មី។' : 'No courses in this filter. Try another tab or browse the catalog.' }}
            </div>
          </section>

          <!-- Study time (7-day real bars + exact breakdown) -->
          <section class="desk-card p-5 space-y-4">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
              <div class="space-y-0.5">
                <h2 class="text-[16px] font-bold text-[var(--desk-text)]">
                  {{ currentLang === 'km' ? 'ពេលវេលាសិក្សា (៧ ថ្ងៃចុងក្រោយ)' : 'Study time (last 7 days)' }}
                </h2>
                <p class="text-[13px] text-[var(--desk-muted)] tabular-nums">
                  {{ currentLang === 'km' ? 'មធ្យមប្រចាំថ្ងៃ៖ ' : 'Daily average: ' }}{{ studyTimeBreakdown?.dailyAverage || '0h / day' }}
                  <template v-if="studyTimeBreakdown?.weekTrendPct !== null && studyTimeBreakdown?.weekTrendPct !== undefined">
                    · {{ studyTimeBreakdown.weekTrendPct >= 0 ? '+' : '' }}{{ studyTimeBreakdown.weekTrendPct }}% {{ currentLang === 'km' ? 'ធៀបនឹងសប្តាហ៍មុន' : 'vs prior 7 days' }}
                  </template>
                </p>
              </div>
              <div class="text-[20px] font-bold tabular-nums text-[var(--desk-text)]">
                {{ studyTimeBreakdown?.total || '0h 0m' }}
              </div>
            </div>

            <!-- 7-day bars -->
            <div v-if="(studyTimeBreakdown?.totalMinutes || 0) > 0" class="space-y-4">
              <div class="grid grid-cols-7 gap-2 items-end h-28 pt-2">
                <div
                  v-for="bar in dailyBars"
                  :key="bar.date"
                  class="flex flex-col items-center justify-end h-full gap-1.5"
                >
                  <span class="text-[11px] tabular-nums text-[var(--desk-muted)]">
                    {{ bar.minutes > 0 ? `${bar.minutes}m` : '—' }}
                  </span>
                  <div class="w-full max-w-[28px] h-16 bg-slate-100 dark:bg-slate-800 rounded-[6px] flex items-end overflow-hidden">
                    <div
                      class="w-full bg-[var(--brand)] rounded-[6px] transition-all duration-500 ease-out"
                      :style="{ height: (bar.minutes > 0 ? Math.max(10, Math.round((bar.minutes / maxDailyMinutes) * 100)) : 0) + '%' }"
                    ></div>
                  </div>
                  <span class="text-[12px] text-[var(--desk-muted)]">{{ bar.label }}</span>
                </div>
              </div>

              <!-- Exact minute-consistent breakdown (never overflows card) -->
              <div class="flex flex-wrap items-center gap-x-5 gap-y-1 pt-3 border-t border-[var(--desk-border)] text-[13px] text-[var(--desk-muted)] tabular-nums">
                <span>{{ currentLang === 'km' ? 'មេរៀន៖ ' : 'Learning: ' }}<strong class="text-[var(--desk-text)]">{{ studyTimeBreakdown?.lessons }}</strong></span>
                <span>{{ currentLang === 'km' ? 'អនុវត្ត៖ ' : 'Practice: ' }}<strong class="text-[var(--desk-text)]">{{ studyTimeBreakdown?.practice }}</strong></span>
                <span>{{ currentLang === 'km' ? 'កម្រងសំណួរ៖ ' : 'Quizzes: ' }}<strong class="text-[var(--desk-text)]">{{ studyTimeBreakdown?.quiz }}</strong></span>
              </div>
            </div>

            <div v-else class="py-5 text-center text-[13px] text-[var(--desk-muted)] border-t border-[var(--desk-border)]">
              — · {{ currentLang === 'km' ? 'មិនទាន់មានកំណត់ត្រាសិក្សាទេ។ ចាប់ផ្តើមមេរៀនដំបូងរបស់អ្នកដើម្បីកត់ត្រាពេលវេលា។' : 'No study sessions recorded yet. Open a lesson to start tracking.' }}
            </div>
          </section>

          <!-- Recent activity (horizontal rows) -->
          <section class="desk-card p-5 space-y-3">
            <div class="flex items-center justify-between">
              <h2 class="text-[16px] font-bold text-[var(--desk-text)]">
                {{ currentLang === 'km' ? 'សកម្មភាពថ្មីៗ' : 'Recent activity' }}
              </h2>
              <Link href="/student/progress/history" class="text-[13px] font-medium text-[var(--brand)] hover:underline">
                {{ currentLang === 'km' ? 'ប្រវត្តិទាំងអស់' : 'View history' }}
              </Link>
            </div>

            <div v-if="(recentActivities || []).length > 0" class="divide-y divide-[var(--desk-border)]">
              <Link
                v-for="act in recentActivities"
                :key="act.id"
                :href="act.href"
                class="py-3 first:pt-1 last:pb-1 flex items-center justify-between gap-3 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 -mx-2 px-2 rounded-[8px] transition-colors"
              >
                <div class="min-w-0 flex-1 flex items-center gap-2.5">
                  <span
                    class="w-5 h-5 rounded-full flex items-center justify-center shrink-0"
                    :class="act.completed ? 'bg-[var(--desk-done)] text-white' : 'border border-[var(--desk-border)] text-[var(--desk-muted)]'"
                  >
                    <svg v-if="act.completed" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span v-else class="w-1.5 h-1.5 rounded-full bg-[var(--brand)]"></span>
                  </span>
                  <div class="min-w-0">
                    <div class="text-[14px] font-medium text-[var(--desk-text)] truncate">{{ act.title }}</div>
                    <div class="text-[12px] text-[var(--desk-muted)] truncate">{{ act.course }} · {{ act.time }}</div>
                  </div>
                </div>
                <span class="text-[13px] font-medium tabular-nums text-[var(--desk-muted)] shrink-0">
                  {{ act.progress }}%
                </span>
              </Link>
            </div>

            <div v-else class="py-5 text-center text-[13px] text-[var(--desk-muted)]">
              — · {{ currentLang === 'km' ? 'មិនទាន់មានសកម្មភាពសិក្សាថ្មីៗទេ។' : 'No recent activity yet. Open a course to get started.' }}
            </div>
          </section>
        </div>

        <!-- SIDE COLUMN (360px) -->
        <div class="space-y-6">
          <!-- Today's goal -->
          <section class="desk-card p-5 space-y-3.5">
            <div class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <h2 class="text-[16px] font-bold text-[var(--desk-text)]">
                  {{ currentLang === 'km' ? 'គោលដៅថ្ងៃនេះ' : "Today's goal" }}
                </h2>
                <p class="text-[12px] text-[var(--desk-muted)] tabular-nums">
                  {{ goalCompletedCount }}/{{ goalTotalCount }} {{ currentLang === 'km' ? 'រួចរាល់' : 'done' }} · {{ goalPercent }}%
                </p>
              </div>
              <button
                @click="openAddGoal"
                type="button"
                class="desk-btn desk-btn-secondary w-9 h-9 flex items-center justify-center cursor-pointer"
                :title="currentLang === 'km' ? 'បន្ថែមគោលដៅ' : 'Add goal'"
                aria-label="Add goal"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
              </button>
            </div>

            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-300 ease-out"
                :class="goalPercent >= 100 && goalTotalCount > 0 ? 'bg-[var(--desk-done)]' : 'bg-[var(--brand)]'"
                :style="{ width: goalPercent + '%' }"
              ></div>
            </div>

            <div v-if="isAddingGoal" class="space-y-2 pt-1">
              <input
                ref="goalInputEl"
                v-model="newGoalInput"
                type="text"
                maxlength="120"
                :placeholder="currentLang === 'km' ? 'បន្ថែមគោលដៅសិក្សា...' : 'Add a study goal...'"
                class="w-full px-3 py-2 rounded-[8px] border border-[var(--desk-border)] bg-[var(--desk-bg)] text-[13px] text-[var(--desk-text)] focus:outline-none focus:border-[var(--brand)]"
                @keydown.enter.prevent="addGoal"
                @keydown.esc.prevent="isAddingGoal = false"
              />
              <div class="flex justify-end gap-2">
                <button @click="isAddingGoal = false" type="button" class="text-[12px] text-[var(--desk-muted)] px-2 py-1 cursor-pointer">
                  {{ currentLang === 'km' ? 'បោះបង់' : 'Cancel' }}
                </button>
                <button @click="addGoal" type="button" class="desk-btn desk-btn-primary text-[12px] px-3 py-1 cursor-pointer">
                  {{ currentLang === 'km' ? 'រក្សាទុក' : 'Save' }}
                </button>
              </div>
            </div>

            <div v-if="goalItems.length > 0" class="divide-y divide-[var(--desk-border)]">
              <div
                v-for="item in goalItems"
                :key="item.id"
                class="group py-2.5 first:pt-1 last:pb-1 flex items-center justify-between gap-2.5"
              >
                <button
                  @click="toggleGoal(item)"
                  type="button"
                  class="flex items-center gap-2.5 text-left flex-1 min-w-0 cursor-pointer"
                >
                  <span
                    class="w-4 h-4 rounded-full flex items-center justify-center shrink-0"
                    :class="item.completed ? 'bg-[var(--desk-done)] text-white' : 'border border-slate-400 dark:border-slate-500'"
                  >
                    <svg v-if="item.completed" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                  </span>
                  <span
                    class="text-[13px] truncate"
                    :class="item.completed ? 'line-through text-[var(--desk-muted)]' : 'text-[var(--desk-text)] font-medium'"
                  >
                    {{ currentLang === 'km' ? (item.titleKm || item.titleEn) : item.titleEn }}
                  </span>
                </button>

                <div class="flex items-center gap-1.5 shrink-0">
                  <button
                    v-if="item.targetRoute"
                    @click="router.visit(item.targetRoute)"
                    type="button"
                    class="text-[12px] font-medium text-[var(--brand)] hover:underline cursor-pointer"
                  >
                    {{ currentLang === 'km' ? 'បើក' : 'Open' }}
                  </button>
                  <button
                    @click="removeGoal(item.id)"
                    type="button"
                    class="opacity-0 group-hover:opacity-100 focus:opacity-100 text-[12px] text-[var(--desk-muted)] hover:text-rose-600 px-1 cursor-pointer"
                    aria-label="Remove goal"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>

            <div v-else class="py-3 text-[13px] text-[var(--desk-muted)]">
              — · {{ currentLang === 'km' ? 'ចុច + ដើម្បីបន្ថែមគោលដៅថ្ងៃនេះ។' : 'Click + to add a daily study goal.' }}
            </div>
          </section>

          <!-- Upcoming quizzes -->
          <section class="desk-card p-5 space-y-3">
            <div class="flex items-center justify-between">
              <h2 class="text-[16px] font-bold text-[var(--desk-text)]">
                {{ currentLang === 'km' ? 'កម្រងសំណួរខាងមុខ' : 'Upcoming quizzes' }}
              </h2>
              <Link href="/student/quizzes/practice" class="text-[13px] font-medium text-[var(--brand)] hover:underline">
                {{ currentLang === 'km' ? 'កម្រងសំណួរ' : 'Quizzes' }}
              </Link>
            </div>

            <div v-if="(upcomingEvents || []).length > 0" class="divide-y divide-[var(--desk-border)]">
              <Link
                v-for="ev in upcomingEvents"
                :key="ev.id"
                :href="ev.href"
                class="py-2.5 first:pt-1 last:pb-1 flex items-center justify-between gap-3 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 -mx-2 px-2 rounded-[8px]"
              >
                <div class="min-w-0">
                  <div class="text-[13px] font-medium text-[var(--desk-text)] truncate">{{ ev.title }}</div>
                  <div class="text-[12px] text-[var(--desk-muted)] truncate">{{ ev.course }}</div>
                </div>
                <span class="text-[12px] text-[var(--desk-muted)] tabular-nums shrink-0">{{ ev.meta }}</span>
              </Link>
            </div>

            <div v-else class="py-3 text-[13px] text-[var(--desk-muted)]">
              — · {{ currentLang === 'km' ? 'មិនទាន់មានកម្រងសំណួរក្នុងវគ្គសិក្សារបស់អ្នកទេ។' : 'No quizzes scheduled for your enrolled courses.' }}
            </div>
          </section>

          <!-- Recommended (clearly labeled AI) -->
          <section class="desk-card p-5 space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <h2 class="text-[16px] font-bold text-[var(--desk-text)]">
                  {{ currentLang === 'km' ? 'ណែនាំសម្រាប់អ្នក' : 'Recommended' }}
                </h2>
                <span class="desk-chip px-1.5 py-0.5 text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-[var(--brand)] border border-[var(--desk-border)]">
                  AI
                </span>
              </div>
              <Link href="/student/ai-path/recommended" class="text-[13px] font-medium text-[var(--brand)] hover:underline">
                {{ currentLang === 'km' ? 'ផែនទីសិក្សា' : 'Roadmap' }}
              </Link>
            </div>

            <div v-if="(aiRecommendations || []).length > 0" class="space-y-2.5">
              <div
                v-for="rec in aiRecommendations"
                :key="rec.id"
                class="flex items-center justify-between gap-3"
              >
                <div class="min-w-0">
                  <div class="text-[13px] font-medium text-[var(--desk-text)] truncate">
                    {{ currentLang === 'km' ? (rec.title_km || rec.title) : rec.title }}
                  </div>
                  <div class="text-[12px] text-[var(--desk-muted)] truncate">
                    {{ currentLang === 'km' ? (rec.desc_km || rec.desc) : rec.desc }}
                  </div>
                </div>
                <Link
                  :href="rec.actionHref"
                  class="desk-btn desk-btn-secondary px-3 py-1.5 text-[12px] font-medium shrink-0"
                >
                  {{ rec.actionLabel }}
                </Link>
              </div>
            </div>

            <div v-else class="py-2 text-[13px] text-[var(--desk-muted)]">
              — · {{ currentLang === 'km' ? 'ចាប់ផ្តើមមេរៀន ឬ Quiz ដើម្បីទទួលការណែនាំ AI។' : 'Complete a lesson or quiz to unlock AI study guidance.' }}
            </div>
          </section>

          <!-- Achievements (compact rule-based list) -->
          <section class="desk-card p-5 space-y-3">
            <div class="flex items-center justify-between">
              <h2 class="text-[16px] font-bold text-[var(--desk-text)]">
                {{ currentLang === 'km' ? 'សមិទ្ធផល' : 'Achievements' }}
              </h2>
              <span class="text-[12px] text-[var(--desk-muted)] tabular-nums">
                {{ unlockedAchievementsCount }}/{{ (achievements || []).length }}
              </span>
            </div>

            <div class="divide-y divide-[var(--desk-border)]">
              <Link
                v-for="ach in achievements"
                :key="ach.id"
                :href="ach.href"
                class="py-2.5 first:pt-1 last:pb-1 flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <span
                    class="w-4 h-4 rounded-full flex items-center justify-center shrink-0"
                    :class="ach.unlocked ? 'bg-[var(--desk-done)] text-white' : 'border border-[var(--desk-border)] text-[var(--desk-muted)]'"
                  >
                    <svg v-if="ach.unlocked" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                  </span>
                  <span
                    class="text-[13px] truncate"
                    :class="ach.unlocked ? 'font-medium text-[var(--desk-text)]' : 'text-[var(--desk-muted)]'"
                  >
                    {{ currentLang === 'km' ? (ach.title_km || ach.title) : ach.title }}
                  </span>
                </div>
                <span class="text-[12px] tabular-nums text-[var(--desk-muted)] shrink-0">
                  {{ ach.unlocked ? (currentLang === 'km' ? 'សម្រេច' : 'Earned') : ach.progress }}
                </span>
              </Link>
            </div>
          </section>
        </div>
      </div>
    </div>
  </StudentLayout>
</template>

<style scoped>
.student-dashboard {
  --brand: #5b3fd6;
  --desk-done: #15803d;
  --desk-bg: #ffffff;
  --desk-border: #e5e7eb;
  --desk-text: #0f172a;
  --desk-muted: #64748b;
  font-family: 'Kantumruy Pro', system-ui, -apple-system, sans-serif;
}

:global(.dark) .student-dashboard {
  --brand: #8b78f0;
  --desk-done: #22c55e;
  --desk-bg: #1e1f20;
  --desk-border: #2d2f31;
  --desk-text: #f1f5f9;
  --desk-muted: #94a3b8;
}

.student-dashboard .desk-card {
  background-color: var(--desk-bg);
  border: 1px solid var(--desk-border);
  border-radius: 12px;
}

.student-dashboard .desk-btn {
  border-radius: 8px;
  transition: background-color 150ms ease, border-color 150ms ease, opacity 150ms ease;
}

.student-dashboard .desk-btn-primary {
  background-color: var(--brand);
  color: #ffffff;
}

:global(.dark) .student-dashboard .desk-btn-primary {
  color: #0f172a;
}

.student-dashboard .desk-btn-primary:hover {
  opacity: 0.92;
}

.student-dashboard .desk-btn-secondary {
  background-color: var(--desk-bg);
  border: 1px solid var(--desk-border);
  color: var(--desk-text);
}

.student-dashboard .desk-btn-secondary:hover {
  background-color: rgba(148, 163, 184, 0.08);
}

.student-dashboard .desk-chip {
  border-radius: 6px;
}

.student-dashboard a:focus-visible,
.student-dashboard button:focus-visible,
.student-dashboard input:focus-visible {
  outline: 2px solid var(--brand);
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .student-dashboard * {
    transition: none !important;
    animation: none !important;
  }
}
</style>
