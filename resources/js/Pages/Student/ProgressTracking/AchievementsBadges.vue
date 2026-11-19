<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

interface SkillItem {
  id: number
  name: string
  description: string
  category: string
  level: string
  progress: number
  trend: string
  action: string
  code?: string
  icon_bg?: string
}

interface ChartPoint {
  x: number
  y: number
  date: string
  score: number
  new_skills: number
  improved: number
}

const { isKhmer } = useLanguage()
const page = usePage()
const currentLocale = computed(() => isKhmer.value || (page.props as any)?.locale === 'km' ? 'km' : 'en')

const props = defineProps<{
  analytics?: {
    summary: {
      overall_level: number
      level_name: string
      mastered_count: number
      total_skills: number
      overall_trend: string
      skills_in_progress: number
      in_progress_note: string
      need_work_count: number
      mastered_skills: number
      mastered_note: string
      new_mastered_this_month: number
      skill_points: number
      points_trend: string
      top_category: string
      top_category_note: string
    }
    categories: Array<{
      name: string
      score: number
      color: string
    }>
    level_distribution: {
      advanced: number
      intermediate: number
      beginner: number
      not_started: number
    }
    skills: SkillItem[]
    trend_chart: {
      labels: string[]
      points: Array<{
        date: string
        score: number
        new_skills: number
        improved: number
      }>
    }
    improvement_focus: Array<{
      id: number
      skill: string
      description: string
      priority: string
      badge_color: string
      progress: number
      code: string
      icon_bg: string
    }>
    recently_mastered: Array<{
      id: number
      title: string
      date: string
      icon: string
      icon_bg: string
    }>
    ai_recommendation: {
      primary: string
      secondary: string
      study_plan?: Array<{
        day: string
        title: string
        duration: string
        type: string
      }>
    }
  }
  filters?: {
    category: string
    sort: string
    tab: string
    period: string
  }
}>()

// Default baseline data
const defaultSummary = {
  overall_level: 0,
  level_name: 'Not Started',
  mastered_count: 0,
  total_skills: 0,
  overall_trend: '0% overall',
  skills_in_progress: 0,
  in_progress_note: 'មិនទាន់មានជំនាញកំពុងសិក្សាទេ',
  need_work_count: 0,
  mastered_skills: 0,
  mastered_note: 'មិនទាន់មានជំនាញដែលបានស្ទាត់ជំនាញទេ',
  new_mastered_this_month: 0,
  skill_points: 0,
  points_trend: '0 pts',
  top_category: '—',
  top_category_note: 'មិនទាន់មានទិន្នន័យសិក្សា',
}

const summary = computed(() => props.analytics?.summary || defaultSummary)
const categories = computed(() => props.analytics?.categories || [])
const levelDistribution = computed(() => props.analytics?.level_distribution || { advanced: 0, intermediate: 0, beginner: 0, not_started: 0 })
const rawSkills = computed(() => props.analytics?.skills || [])
const improvementFocus = computed(() => props.analytics?.improvement_focus || [])
const recentlyMastered = computed(() => props.analytics?.recently_mastered || [])
const aiRecommendation = computed(() => props.analytics?.ai_recommendation || {
  primary: isKhmer.value ? 'មិនទាន់មានទិន្នន័យវិភាគជំនាញនៅឡើយទេ។' : 'No skill analysis data recorded yet.',
  secondary: isKhmer.value ? 'សូមចុះឈ្មោះចូលរៀនវគ្គសិក្សា ដើម្បីចាប់ផ្តើមតាមដានជំនាញរបស់អ្នក។' : 'Enroll in courses and take quizzes to start tracking your skill mastery.',
  study_plan: []
})

// Filter & Sort State
const activeTab = ref<'all' | 'in_progress' | 'mastered' | 'not_started'>(
  (props.filters?.tab as any) || 'all'
)
const selectedCategory = ref(props.filters?.category || 'all')
const selectedSort = ref(props.filters?.sort || 'progress_desc')
const showMore = ref(false)

// Modals State
const isStudyPlanModalOpen = ref(false)
const isSkillActionModalOpen = ref(false)
const selectedSkillAction = ref<SkillItem | null>(null)
const activeHoverPoint = ref<ChartPoint | null>(null)

const openSkillAction = (skill: SkillItem) => {
  selectedSkillAction.value = skill
  isSkillActionModalOpen.value = true
}

// Available categories extracted dynamically
const availableCategories = computed(() => {
  const set = new Set<string>()
  categories.value.forEach(c => {
    if (c.name && c.name !== '—') set.add(c.name)
  })
  rawSkills.value.forEach(s => {
    if (s.category && s.category !== 'General') set.add(s.category)
  })
  return Array.from(set)
})

// Reactive filtering and sorting
const displayedSkills = computed(() => {
  let list = [...rawSkills.value]

  // Filter by Tab
  if (activeTab.value === 'in_progress') {
    list = list.filter(s => s.progress > 0 && s.progress < 90)
  } else if (activeTab.value === 'mastered') {
    list = list.filter(s => s.progress >= 90 || s.level === 'Mastered')
  } else if (activeTab.value === 'not_started') {
    list = list.filter(s => s.progress === 0 || s.level === 'Not Started')
  }

  // Filter by Category
  if (selectedCategory.value !== 'all') {
    list = list.filter(s => s.category.toLowerCase() === selectedCategory.value.toLowerCase())
  }

  // Sort
  list.sort((a, b) => {
    if (selectedSort.value === 'progress_asc') return a.progress - b.progress
    if (selectedSort.value === 'name_asc') return a.name.localeCompare(b.name)
    return b.progress - a.progress
  })

  return showMore.value ? list : list.slice(0, 8)
})

// Dynamic Donut Segments for "Skills by Category"
const donutSegments = computed(() => {
  if (!categories.value || categories.value.length === 0) return []
  const totalScore = categories.value.reduce((acc, c) => acc + (c.score || 0), 0)
  if (totalScore <= 0) return []

  let cumulativeOffset = 0
  return categories.value.map(cat => {
    const pct = Math.max(1, Math.round(((cat.score || 0) / totalScore) * 100))
    const segment = {
      ...cat,
      pct,
      dasharray: `${pct}, ${100 - pct}`,
      dashoffset: -cumulativeOffset,
    }
    cumulativeOffset += pct
    return segment
  })
})

// Dynamic Distribution Width
const totalDistribution = computed(() => {
  const d = levelDistribution.value
  return (d.advanced || 0) + (d.intermediate || 0) + (d.beginner || 0) + (d.not_started || 0)
})

const getDistWidth = (count: number) => {
  if (!totalDistribution.value || count <= 0) return '0%'
  return `${Math.round((count / totalDistribution.value) * 100)}%`
}

// Trend Points computed realistically (without fake May 20 hardcoding)
const trendPoints = computed(() => {
  const pts = props.analytics?.trend_chart?.points
  if (pts && pts.length > 0) return pts

  // Generate 7 current date baseline points with 0%
  const now = new Date()
  const baseline = []
  for (let i = 6; i >= 0; i--) {
    const d = new Date(now)
    d.setDate(d.getDate() - (i * 5))
    const label = d.toLocaleDateString(currentLocale.value === 'km' ? 'km-KH' : 'en-US', { month: 'short', day: 'numeric' })
    baseline.push({
      date: label,
      score: 0,
      new_skills: 0,
      improved: 0,
    })
  }
  return baseline
})

const chartSvgPoints = computed<ChartPoint[]>(() => {
  const pts = trendPoints.value
  if (!pts || pts.length === 0) return []
  const width = 360
  const height = 140
  const paddingX = 15
  const paddingY = 20
  const usableW = width - paddingX * 2
  const usableH = height - paddingY * 2

  const stepX = usableW / Math.max(1, pts.length - 1)

  return pts.map((p, idx) => {
    const x = paddingX + idx * stepX
    const y = paddingY + (1 - (Math.min(100, Math.max(0, p.score)) / 100)) * usableH
    return { ...p, x, y }
  })
})

const chartSvgPath = computed<string>(() => {
  const pts = chartSvgPoints.value
  if (!pts || pts.length === 0) return ''
  let path = `M ${pts[0].x},${pts[0].y}`
  for (let i = 0; i < pts.length - 1; i++) {
    const p0 = pts[i]
    const p1 = pts[i + 1]
    const cpX = (p0.x + p1.x) / 2
    path += ` C ${cpX},${p0.y} ${cpX},${p1.y} ${p1.x},${p1.y}`
  }
  return path
})

const chartSvgAreaPath = computed<string>(() => {
  const pts = chartSvgPoints.value
  if (!pts || pts.length === 0) return ''
  const baseLine = 140 - 10
  let area = chartSvgPath.value
  area += ` L ${pts[pts.length - 1].x},${baseLine} L ${pts[0].x},${baseLine} Z`
  return area
})

const setTab = (tab: 'all' | 'in_progress' | 'mastered' | 'not_started') => {
  activeTab.value = tab
}

const handleFilterChange = () => {
  router.get('/student/progress/achievements', {
    tab: activeTab.value,
    category: selectedCategory.value,
    sort: selectedSort.value,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}
</script>

<template>
  <StudentLayout :title="isKhmer ? 'ការរីកចម្រើនជំនាញ — វឌ្ឍនភាព និងការវិភាគ' : 'Skills Progress — Progress & Analytics'">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <span>{{ isKhmer ? 'ការរីកចម្រើនជំនាញ' : 'Skills Progress' }}</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-600 dark:text-purple-400 text-lg">📈</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
            {{ isKhmer ? 'តាមដាន និងពង្រឹងជំនាញរបស់អ្នកដើម្បីក្លាយជាអ្នកជំនាញវិជ្ជាជីវៈ។' : 'Track and improve your skills. Master more skills to become a professional.' }}
          </p>
        </div>

        <button
          @click="isStudyPlanModalOpen = true"
          class="px-4 py-2.5 rounded-xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-purple-500/50 text-slate-700 dark:text-slate-300 hover:text-purple-600 dark:hover:text-white text-xs font-bold transition-all shadow-sm hover:shadow-md self-start sm:self-auto flex items-center gap-2 group cursor-pointer"
        >
          <span class="text-purple-600 dark:text-purple-400 group-hover:rotate-12 transition-transform">🤖</span>
          <span>{{ isKhmer ? 'ផែនការសិក្សា AI' : 'AI Study Plan' }}</span>
        </button>
      </div>

      <!-- ================= 2. SUMMARY CARDS (5 TOP CARDS) ================= -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- CARD 1: Overall Skills Level -->
        <div class="relative overflow-hidden bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4.5 shadow-sm dark:shadow-xl hover:border-purple-500/40 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
              {{ isKhmer ? 'កម្រិតជំនាញសរុប' : 'Overall Skills Level' }}
            </p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ summary.overall_level }}%</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
              {{ isKhmer ? 'ស្ទាត់ជំនាញ' : "You've mastered" }}
              <strong class="text-slate-800 dark:text-white">{{ summary.mastered_count }} {{ isKhmer ? 'នៃ' : 'of' }} {{ summary.total_skills }}</strong>
              {{ isKhmer ? 'ជំនាញសរុប' : 'Total Skills' }}
            </p>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>↑</span>
              <span>{{ summary.overall_trend }}</span>
            </p>
          </div>
          <!-- Dynamic Circular Progress Gauge -->
          <div class="relative w-14 h-14 flex items-center justify-center shrink-0">
            <svg class="w-14 h-14 -rotate-90 transform" viewBox="0 0 36 36">
              <path
                class="text-slate-100 dark:text-slate-800"
                stroke-width="3.2"
                stroke="currentColor"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />
              <path
                class="text-purple-600 dark:text-purple-500 transition-all duration-1000"
                :stroke-dasharray="`${summary.overall_level}, 100`"
                stroke-width="3.2"
                stroke-linecap="round"
                stroke="currentColor"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold font-mono text-purple-600 dark:text-purple-300">
              {{ summary.overall_level }}%
            </span>
          </div>
        </div>

        <!-- CARD 2: Skills In Progress -->
        <div class="relative overflow-hidden bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4.5 shadow-sm dark:shadow-xl hover:border-purple-500/40 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
              {{ isKhmer ? 'ជំនាញកំពុងសិក្សា' : 'Skills In Progress' }}
            </p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ summary.skills_in_progress }}</p>
            <p class="text-[11px] text-purple-600 dark:text-purple-300 font-medium">
              {{ isKhmer ? summary.in_progress_note : (summary.skills_in_progress > 0 ? 'Active skills in progress' : 'No active skills yet') }}
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium pt-0.5">
              {{ summary.need_work_count }} {{ isKhmer ? 'ជំនាញត្រូវការការកែលម្អ' : 'skills need more work' }}
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-600/20 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center text-purple-600 dark:text-purple-400 text-lg shadow-inner group-hover:scale-110 transition-transform shrink-0">
            📖
          </div>
        </div>

        <!-- CARD 3: Mastered Skills -->
        <div class="relative overflow-hidden bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4.5 shadow-sm dark:shadow-xl hover:border-amber-500/40 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
              {{ isKhmer ? 'ជំនាញដែលបានស្ទាត់' : 'Mastered Skills' }}
            </p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ summary.mastered_skills }}</p>
            <p class="text-[11px] text-amber-600 dark:text-amber-300 font-medium">
              {{ isKhmer ? summary.mastered_note : (summary.mastered_skills > 0 ? 'Great progress!' : 'Complete courses to master skills') }}
            </p>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>↑</span>
              <span>{{ summary.new_mastered_this_month }} {{ isKhmer ? 'ថ្មីក្នុងខែនេះ' : 'new this month' }}</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-500/20 border border-amber-200 dark:border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 text-lg shadow-inner group-hover:scale-110 transition-transform shrink-0">
            🏆
          </div>
        </div>

        <!-- CARD 4: Skill Points -->
        <div class="relative overflow-hidden bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4.5 shadow-sm dark:shadow-xl hover:border-amber-500/40 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
              {{ isKhmer ? 'ពិន្ទុជំនាញ' : 'Skill Points' }}
            </p>
            <p class="text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">{{ Number(summary.skill_points).toLocaleString() }}</p>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1 font-medium pt-0.5 font-mono">
              <span>{{ summary.points_trend }}</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-500/20 border border-amber-200 dark:border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 text-lg shadow-inner group-hover:scale-110 transition-transform shrink-0">
            🎖️
          </div>
        </div>

        <!-- CARD 5: Top Category -->
        <div class="relative overflow-hidden bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4.5 shadow-sm dark:shadow-xl hover:border-emerald-500/40 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
              {{ isKhmer ? 'ជំនាញលេចធ្លោ' : 'Top Category' }}
            </p>
            <p class="text-sm font-black text-slate-900 dark:text-white truncate max-w-[120px]">{{ summary.top_category }}</p>
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium pt-0.5 truncate max-w-[130px]">
              {{ isKhmer ? summary.top_category_note : (summary.top_category !== '—' ? 'Your strongest area' : 'No courses enrolled yet') }}
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-500/20 border border-emerald-200 dark:border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 text-base font-bold shadow-inner group-hover:scale-110 transition-transform shrink-0 font-mono">
            &lt;/&gt;
          </div>
        </div>

      </div>

      <!-- ================= 3. FILTER & SORT TAB BAR ================= -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-[#0F172A]/80 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-3 shadow-sm dark:shadow-lg">
        
        <!-- Left Tab Pills -->
        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="setTab('all')"
            :class="[
              activeTab === 'all'
                ? 'bg-purple-600 text-white shadow-md shadow-purple-900/30'
                : 'bg-slate-100 dark:bg-slate-900/90 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800',
              'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer'
            ]"
          >
            {{ isKhmer ? 'ជំនាញទាំងអស់' : 'All Skills' }}
          </button>

          <button
            @click="setTab('in_progress')"
            :class="[
              activeTab === 'in_progress'
                ? 'bg-purple-600 text-white shadow-md shadow-purple-900/30'
                : 'bg-slate-100 dark:bg-slate-900/90 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800',
              'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer'
            ]"
          >
            {{ isKhmer ? 'កំពុងសិក្សា' : 'In Progress' }}
          </button>

          <button
            @click="setTab('mastered')"
            :class="[
              activeTab === 'mastered'
                ? 'bg-purple-600 text-white shadow-md shadow-purple-900/30'
                : 'bg-slate-100 dark:bg-slate-900/90 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800',
              'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer'
            ]"
          >
            {{ isKhmer ? 'បានស្ទាត់ជំនាញ' : 'Mastered' }}
          </button>

          <button
            @click="setTab('not_started')"
            :class="[
              activeTab === 'not_started'
                ? 'bg-purple-600 text-white shadow-md shadow-purple-900/30'
                : 'bg-slate-100 dark:bg-slate-900/90 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800',
              'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer'
            ]"
          >
            {{ isKhmer ? 'មិនទាន់ចាប់ផ្តើម' : 'Not Started' }}
          </button>
        </div>

        <!-- Right Dropdowns -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ isKhmer ? 'ប្រភេទ:' : 'Category:' }}</span>
            <select
              v-model="selectedCategory"
              @change="handleFilterChange"
              class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-800 dark:text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
            >
              <option value="all">{{ isKhmer ? 'ទាំងអស់' : 'All' }}</option>
              <option v-for="cat in availableCategories" :key="cat" :value="cat">
                {{ cat }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ isKhmer ? 'តម្រៀបតាម:' : 'Sort by:' }}</span>
            <select
              v-model="selectedSort"
              @change="handleFilterChange"
              class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-xs font-semibold text-slate-800 dark:text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
            >
              <option value="progress_desc">{{ isKhmer ? 'វឌ្ឍនភាព (ខ្ពស់ទៅទាប)' : 'Progress (High to Low)' }}</option>
              <option value="progress_asc">{{ isKhmer ? 'វឌ្ឍនភាព (ទាបទៅខ្ពស់)' : 'Progress (Low to High)' }}</option>
              <option value="name_asc">{{ isKhmer ? 'ឈ្មោះជំនាញ (A-Z)' : 'Skill Name (A-Z)' }}</option>
            </select>
          </div>
        </div>

      </div>

      <!-- ================= 4. MIDDLE MAIN SPLIT: SKILLS OVERVIEW TABLE & RIGHT STACK ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT (8/12): Skills Overview Table -->
        <div class="lg:col-span-8 bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-sm dark:shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800/60 pb-3">
            <h2 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">
              {{ isKhmer ? 'ទិដ្ឋភាពទូទៅនៃជំនាញ' : 'Skills Overview' }}
            </h2>
            <span class="text-xs text-slate-500 dark:text-slate-400">
              {{ displayedSkills.length }} {{ isKhmer ? 'ជំនាញត្រូវបានបង្ហាញ' : 'Skills Displayed' }}
            </span>
          </div>

          <!-- Table Container -->
          <div v-if="displayedSkills.length > 0" class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
              <thead class="text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800/80">
                <tr>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'ជំនាញ' : 'Skill' }}</th>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'ប្រភេទ' : 'Category' }}</th>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'កម្រិត' : 'Level' }}</th>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'វឌ្ឍនភាព' : 'Progress' }}</th>
                  <th class="py-2.5 px-3">{{ isKhmer ? 'និន្នាការ' : 'Trend' }}</th>
                  <th class="py-2.5 px-3 text-right">{{ isKhmer ? 'សកម្មភាព' : 'Action' }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 font-medium">
                <tr
                  v-for="s in displayedSkills"
                  :key="s.id"
                  class="hover:bg-slate-50 dark:hover:bg-slate-900/80 transition-colors group"
                >
                  <!-- Skill Name & Icon -->
                  <td class="py-3 px-3">
                    <div class="flex items-center gap-3">
                      <div
                        :class="[
                          s.icon_bg || 'from-purple-500 to-indigo-600 text-white',
                          'w-7 h-7 rounded-lg bg-gradient-to-br flex items-center justify-center text-[10px] font-black shrink-0 shadow-md font-mono'
                        ]"
                      >
                        {{ s.code || 'SK' }}
                      </div>
                      <div>
                        <p class="font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors">{{ s.name }}</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ s.description }}</p>
                      </div>
                    </div>
                  </td>

                  <!-- Category Pill -->
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 text-[10px] font-medium">
                      {{ s.category }}
                    </span>
                  </td>

                  <!-- Level Badge -->
                  <td class="py-3 px-3">
                    <span
                      class="px-2.5 py-1 rounded-full text-[10px] font-bold border inline-block"
                      :class="[
                        s.level === 'Mastered' || s.level === 'Advanced' ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border-emerald-300 dark:border-emerald-500/30' :
                        s.level === 'Intermediate' ? 'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-500/30' :
                        s.level === 'Beginner' ? 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-500/30' :
                        'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700'
                      ]"
                    >
                      {{ s.level }}
                    </span>
                  </td>

                  <!-- Progress Bar -->
                  <td class="py-3 px-3 w-36">
                    <div class="flex items-center gap-2.5">
                      <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-950 overflow-hidden border border-slate-200 dark:border-slate-800">
                        <div
                          class="h-full rounded-full bg-purple-600 dark:bg-purple-500 transition-all duration-500"
                          :style="{ width: `${s.progress}%` }"
                        ></div>
                      </div>
                      <span class="text-xs font-mono font-bold text-slate-800 dark:text-white w-8 text-right">{{ s.progress }}%</span>
                    </div>
                  </td>

                  <!-- Trend -->
                  <td class="py-3 px-3">
                    <span
                      :class="[
                        s.trend.startsWith('+') || s.trend.startsWith('↑') ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400 dark:text-slate-500',
                        'text-xs font-mono'
                      ]"
                    >
                      {{ s.trend }}
                    </span>
                  </td>

                  <!-- Action Button -->
                  <td class="py-3 px-3 text-right">
                    <button
                      @click="openSkillAction(s)"
                      class="px-3 py-1 rounded-xl bg-purple-100 dark:bg-purple-600/30 hover:bg-purple-600 dark:hover:bg-purple-600 border border-purple-200 dark:border-purple-500/40 text-purple-700 dark:text-purple-300 hover:text-white font-bold text-xs transition-all shadow-sm flex items-center gap-1.5 ml-auto cursor-pointer"
                    >
                      <span>{{ s.action }}</span>
                      <span class="text-[9px]">▾</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State when no skills exist -->
          <div v-else class="py-12 px-4 text-center space-y-3">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/60 flex items-center justify-center text-2xl text-purple-600 dark:text-purple-400 shadow-sm">
              🛡️
            </div>
            <div class="space-y-1">
              <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                {{ isKhmer ? 'មិនទាន់មានជំនាញដែលបានតាមដាននៅឡើយទេ' : 'No Skills Tracked Yet' }}
              </h4>
              <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                {{ isKhmer ? 'ជំនាញរបស់អ្នកនឹងបង្ហាញឡើងនៅទីនេះដោយស្វ័យប្រវត្តិតាមរយៈវគ្គសិក្សាដែលអ្នកបានចុះឈ្មោះ និងរៀនចប់។' : 'Your skills will automatically appear here as you enroll in courses, complete lessons, and take quizzes.' }}
              </p>
            </div>
            <div class="flex items-center justify-center gap-3 pt-2">
              <Link
                href="/student/courses"
                class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition-all shadow-md inline-flex items-center gap-1.5"
              >
                <span>📚</span>
                <span>{{ isKhmer ? 'ស្វែងរកវគ្គសិក្សា' : 'Explore Courses' }}</span>
              </Link>
              <Link
                href="/student/ai-path/recommended"
                class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all inline-flex items-center gap-1.5"
              >
                <span>✨</span>
                <span>{{ isKhmer ? 'គន្លងសិក្សា AI' : 'AI Learning Path' }}</span>
              </Link>
            </div>
          </div>

          <!-- Bottom View More Button -->
          <div v-if="rawSkills.length > 8" class="pt-2 text-center border-t border-slate-200/80 dark:border-slate-800/60">
            <button
              @click="showMore = !showMore"
              class="text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors inline-flex items-center gap-1 cursor-pointer"
            >
              <span>{{ showMore ? (isKhmer ? 'បង្ហាញតិចជាងមុន ∧' : 'Show Less Skills ∧') : (isKhmer ? 'មើលជំនាញបន្ថែមទៀត ∨' : 'View More Skills ∨') }}</span>
            </button>
          </div>
        </div>

        <!-- RIGHT (4/12): Stack of Category, Level Distribution & Recently Mastered -->
        <div class="lg:col-span-4 space-y-6">

          <!-- 1. Skills by Category Card -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-sm dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">
                {{ isKhmer ? 'ជំនាញតាមប្រភេទ' : 'Skills by Category' }}
              </h3>
              <Link href="/student/courses" class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline">
                {{ isKhmer ? 'មើលទាំងអស់' : 'View All' }}
              </Link>
            </div>

            <div class="flex items-center justify-between gap-4 py-2">
              <!-- Donut Chart -->
              <div class="relative w-28 h-28 flex items-center justify-center shrink-0">
                <svg class="w-28 h-28 -rotate-90 transform" viewBox="0 0 42 42">
                  <circle cx="21" cy="21" r="15.915" fill="none" class="stroke-slate-100 dark:stroke-slate-800" stroke-width="4.5" />
                  <circle
                    v-for="seg in donutSegments"
                    :key="seg.name"
                    cx="21"
                    cy="21"
                    r="15.915"
                    fill="none"
                    :stroke="seg.color"
                    stroke-width="4.5"
                    :stroke-dasharray="seg.dasharray"
                    :stroke-dashoffset="seg.dashoffset"
                    class="transition-all duration-700"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-slate-900 dark:text-white font-mono">{{ summary.overall_level }}%</span>
                  <span class="text-[9px] text-slate-500 dark:text-slate-400">{{ isKhmer ? 'សរុប' : 'Overall' }}</span>
                </div>
              </div>

              <!-- Category Legend -->
              <div v-if="categories.length > 0" class="space-y-1.5 text-xs flex-1">
                <div v-for="cat in categories" :key="cat.name" class="flex items-center justify-between">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: cat.color }"></span>
                    <span class="text-slate-600 dark:text-slate-300 text-[11px] truncate">{{ cat.name }}</span>
                  </div>
                  <span class="font-bold text-slate-900 dark:text-white font-mono text-[11px]">{{ cat.score }}%</span>
                </div>
              </div>
              <div v-else class="text-xs text-slate-400 dark:text-slate-500 flex-1 text-center py-2">
                {{ isKhmer ? 'មិនទាន់មានទិន្នន័យប្រភេទ' : 'No category data yet' }}
              </div>
            </div>
          </div>

          <!-- 2. Skill Level Distribution Card -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">
                {{ isKhmer ? 'ការបែងចែកកម្រិតជំនាញ' : 'Skill Level Distribution' }}
              </h3>
              <Link href="/student/progress/overview" class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline">
                {{ isKhmer ? 'មើលទាំងអស់' : 'View All' }}
              </Link>
            </div>

            <div class="space-y-2.5 text-xs">
              <!-- Advanced -->
              <div class="space-y-1">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-700 dark:text-slate-300 font-medium">{{ isKhmer ? 'កម្រិតខ្ពស់ (Advanced)' : 'Advanced' }}</span>
                  <span class="text-slate-500 dark:text-slate-400 font-mono">{{ levelDistribution.advanced }} {{ isKhmer ? 'ជំនាញ' : 'skills' }}</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 overflow-hidden">
                  <div class="h-full bg-emerald-500 rounded-full transition-all duration-700" :style="{ width: getDistWidth(levelDistribution.advanced) }"></div>
                </div>
              </div>

              <!-- Intermediate -->
              <div class="space-y-1">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-700 dark:text-slate-300 font-medium">{{ isKhmer ? 'កម្រិតមធ្យម (Intermediate)' : 'Intermediate' }}</span>
                  <span class="text-slate-500 dark:text-slate-400 font-mono">{{ levelDistribution.intermediate }} {{ isKhmer ? 'ជំនាញ' : 'skills' }}</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 overflow-hidden">
                  <div class="h-full bg-blue-500 rounded-full transition-all duration-700" :style="{ width: getDistWidth(levelDistribution.intermediate) }"></div>
                </div>
              </div>

              <!-- Beginner -->
              <div class="space-y-1">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-700 dark:text-slate-300 font-medium">{{ isKhmer ? 'កម្រិតដំបូង (Beginner)' : 'Beginner' }}</span>
                  <span class="text-slate-500 dark:text-slate-400 font-mono">{{ levelDistribution.beginner }} {{ isKhmer ? 'ជំនាញ' : 'skills' }}</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 overflow-hidden">
                  <div class="h-full bg-amber-500 rounded-full transition-all duration-700" :style="{ width: getDistWidth(levelDistribution.beginner) }"></div>
                </div>
              </div>

              <!-- Not Started -->
              <div class="space-y-1">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-700 dark:text-slate-300 font-medium">{{ isKhmer ? 'មិនទាន់ចាប់ផ្តើម (Not Started)' : 'Not Started' }}</span>
                  <span class="text-slate-500 dark:text-slate-400 font-mono">{{ levelDistribution.not_started }} {{ isKhmer ? 'ជំនាញ' : 'skills' }}</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 overflow-hidden">
                  <div class="h-full bg-slate-400 dark:bg-slate-600 rounded-full transition-all duration-700" :style="{ width: getDistWidth(levelDistribution.not_started) }"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Recently Mastered Skills Card -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-sm dark:shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">
                {{ isKhmer ? 'ជំនាញដែលទើបតែស្ទាត់' : 'Recently Mastered Skills' }}
              </h3>
              <Link href="/student/certificates" class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline">
                {{ isKhmer ? 'វិញ្ញាបនបត្រ' : 'Certificates' }}
              </Link>
            </div>

            <div v-if="recentlyMastered.length > 0" class="space-y-2.5">
              <div
                v-for="item in recentlyMastered"
                :key="item.id"
                class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/70 border border-slate-200/80 dark:border-slate-800/60 flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      item.icon_bg || 'from-emerald-500 to-teal-600 text-white',
                      'w-7 h-7 rounded-lg bg-gradient-to-br flex items-center justify-center text-[10px] font-black shrink-0 font-mono shadow-sm'
                    ]"
                  >
                    {{ item.icon }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ item.title }}</p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ item.date }}</p>
                  </div>
                </div>
                <div class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-500/30 flex items-center justify-center text-[10px] shrink-0 font-bold">
                  ✓
                </div>
              </div>
            </div>
            <div v-else class="py-6 text-center text-slate-400 dark:text-slate-500 space-y-1">
              <p class="text-xs font-medium">{{ isKhmer ? 'មិនទាន់មានជំនាញដែលបានស្ទាត់ជំនាញទេ' : 'No mastered skills yet' }}</p>
              <p class="text-[10px] text-slate-400">{{ isKhmer ? 'រៀនចប់វគ្គសិក្សាដើម្បីទទួលបានការស្ទាត់ជំនាញ' : 'Complete courses and pass quizzes to master skills' }}</p>
            </div>
          </div>

        </div>

      </div>

      <!-- ================= 5. BOTTOM ROW: 3 EQUAL-WIDTH WIDGETS ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- 1. Skill Progress Over Time (Line Chart) -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-sm dark:shadow-xl space-y-3 relative">
          <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800/60 pb-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">
              {{ isKhmer ? 'និន្នាការរីកចម្រើនជំនាញ' : 'Skill Progress Over Time' }}
            </h3>
            <select
              class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 text-[11px] font-semibold text-slate-700 dark:text-slate-300 rounded-xl px-2.5 py-1 focus:outline-none focus:border-purple-500 cursor-pointer"
            >
              <option>{{ isKhmer ? 'ខែនេះ' : 'This Month' }}</option>
              <option>{{ isKhmer ? '៣ ខែចុងក្រោយ' : 'Last 3 Months' }}</option>
              <option>{{ isKhmer ? 'ទាំងអស់' : 'All Time' }}</option>
            </select>
          </div>

          <!-- Line Chart SVG -->
          <div class="relative w-full h-44 pt-2">
            
            <!-- Y-Axis labels -->
            <div class="absolute left-0 inset-y-2 flex flex-col justify-between text-[9px] text-slate-400 dark:text-slate-500 font-mono select-none pointer-events-none pb-5">
              <span>100%</span>
              <span>75%</span>
              <span>50%</span>
              <span>25%</span>
              <span>0%</span>
            </div>

            <div class="ml-8 h-full relative">
              <!-- Grid lines -->
              <div class="absolute inset-0 flex flex-col justify-between pointer-events-none pb-5">
                <div class="w-full border-b border-slate-100 dark:border-slate-800/40"></div>
                <div class="w-full border-b border-slate-100 dark:border-slate-800/40"></div>
                <div class="w-full border-b border-slate-100 dark:border-slate-800/40"></div>
                <div class="w-full border-b border-slate-100 dark:border-slate-800/40"></div>
                <div class="w-full border-b border-slate-200 dark:border-slate-800/60"></div>
              </div>

              <!-- Chart SVG -->
              <svg class="w-full h-[120px] overflow-visible" viewBox="0 0 360 140" preserveAspectRatio="none">
                <defs>
                  <linearGradient id="skillPurpleGlow" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#A855F7" stop-opacity="0.3" />
                    <stop offset="100%" stop-color="#A855F7" stop-opacity="0.0" />
                  </linearGradient>
                </defs>

                <path :d="chartSvgAreaPath" fill="url(#skillPurpleGlow)" />
                <path
                  :d="chartSvgPath"
                  fill="none"
                  stroke="#A855F7"
                  stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />

                <circle
                  v-for="(pt, idx) in chartSvgPoints"
                  :key="idx"
                  :cx="pt.x"
                  :cy="pt.y"
                  r="4"
                  class="fill-white dark:fill-slate-950 stroke-purple-500 stroke-2 hover:r-6 hover:stroke-purple-700 dark:hover:stroke-white transition-all cursor-pointer"
                  @mouseenter="activeHoverPoint = pt"
                  @mouseleave="activeHoverPoint = null"
                />
              </svg>

              <!-- Dynamic Hover Tooltip Callout -->
              <div
                v-if="activeHoverPoint"
                :style="{ left: `${(activeHoverPoint.x / 360) * 100}%`, top: `${Math.max(0, (activeHoverPoint.y / 140) * 100 - 30)}%` }"
                class="absolute -translate-x-1/2 bg-slate-900/95 text-white border border-purple-500/60 rounded-lg px-2 py-1 shadow-lg text-[9px] pointer-events-none whitespace-nowrap z-20"
              >
                <p class="font-mono"><strong>{{ activeHoverPoint.date }}</strong>: <span class="text-purple-400 font-bold">{{ activeHoverPoint.score }}%</span></p>
              </div>

              <!-- Bottom X-Axis labels -->
              <div class="w-full flex justify-between text-[9px] text-slate-400 dark:text-slate-500 font-mono mt-1 px-1">
                <span v-for="(p, i) in trendPoints" :key="i">{{ p.date }}</span>
              </div>
            </div>

          </div>
        </div>

        <!-- 2. Improvement Focus -->
        <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-sm dark:shadow-xl space-y-3">
          <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800/60 pb-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">
              {{ isKhmer ? 'ជំនាញដែលត្រូវពង្រឹង' : 'Improvement Focus' }}
            </h3>
            <Link href="/student/ai-path/weak-topics" class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline">
              {{ isKhmer ? 'ប្រធានបទខ្សោយ' : 'Weak Topics' }}
            </Link>
          </div>

          <div v-if="improvementFocus.length > 0" class="space-y-2.5">
            <div
              v-for="focus in improvementFocus"
              :key="focus.id"
              class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/70 border border-slate-200/80 dark:border-slate-800/60 flex items-center justify-between gap-3 group hover:border-purple-500/40 transition-all cursor-pointer"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div
                  :class="[
                    focus.icon_bg || 'from-rose-500 to-pink-600 text-white',
                    'w-8 h-8 rounded-xl bg-gradient-to-br flex items-center justify-center text-xs font-black shrink-0 font-mono shadow-sm'
                  ]"
                >
                  {{ focus.code }}
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors truncate">
                    {{ focus.skill }}
                  </p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ focus.description }}</p>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <span
                  class="px-2 py-0.5 rounded-full text-[9px] font-bold border"
                  :class="focus.badge_color === 'rose' ? 'bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 border-rose-300 dark:border-rose-500/30' : 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-500/30'"
                >
                  {{ focus.priority }}
                </span>
                <span class="text-xs font-bold font-mono text-slate-800 dark:text-white">{{ focus.progress }}%</span>
              </div>
            </div>
          </div>
          <div v-else class="py-8 text-center text-slate-400 dark:text-slate-500 space-y-1">
            <span class="text-2xl">🎯</span>
            <p class="text-xs font-medium">{{ isKhmer ? 'មិនមានជំនាញដែលត្រូវពង្រឹងបន្ទាន់ទេ' : 'No skills needing immediate improvement' }}</p>
            <p class="text-[10px] text-slate-400">{{ isKhmer ? 'ពិន្ទុ និងការយល់ដឹងរបស់អ្នកស្ថិតក្នុងកម្រិតល្អប្រសើរ' : 'Your performance across all enrolled topics is on track' }}</p>
          </div>
        </div>

        <!-- 3. AI Skill Recommendation -->
        <div class="bg-gradient-to-br from-purple-50/80 via-white to-indigo-50/50 dark:from-[#10132B] dark:via-[#0F172A] dark:to-[#1E1138] border border-purple-200/80 dark:border-purple-900/50 rounded-3xl p-5 md:p-6 shadow-sm dark:shadow-2xl flex flex-col justify-between space-y-4 relative overflow-hidden group">
          
          <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-purple-600/10 dark:bg-purple-600/15 rounded-full blur-3xl pointer-events-none"></div>

          <div>
            <div class="flex items-center justify-between border-b border-purple-200/80 dark:border-purple-900/40 pb-3">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-300 dark:border-purple-500/30 text-[10px] font-black uppercase">
                  AI
                </span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">
                  {{ isKhmer ? 'ការណែនាំជំនាញពី AI' : 'AI Skill Recommendation' }}
                </h3>
              </div>
            </div>

            <div class="flex items-center gap-4 mt-3">
              <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-600/20 border border-purple-200 dark:border-purple-500/40 flex items-center justify-center text-3xl shadow-inner animate-pulse shrink-0">
                🤖
              </div>
              <div class="space-y-1 text-xs text-slate-600 dark:text-slate-300">
                <p class="font-bold text-slate-900 dark:text-white">"{{ aiRecommendation.primary }}"</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">"{{ aiRecommendation.secondary }}"</p>
              </div>
            </div>
          </div>

          <button
            @click="isStudyPlanModalOpen = true"
            class="w-full py-2.5 px-4 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-md shadow-purple-600/20 dark:shadow-purple-950/50 hover:shadow-purple-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-95 z-10"
          >
            <span>✨</span>
            <span>{{ isKhmer ? 'ទទួលបានផែនការសិក្សា AI' : 'Get AI Study Plan' }}</span>
            <span>&gt;</span>
          </button>

        </div>

      </div>

    </div>

    <!-- ================= MODAL: AI STUDY PLAN ================= -->
    <div
      v-if="isStudyPlanModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <span class="text-2xl">🤖</span>
            <div>
              <h3 class="text-base font-black text-slate-900 dark:text-white">
                {{ isKhmer ? 'ផែនការស្ទាត់ជំនាញផ្ទាល់ខ្លួនជាមួយ AI' : 'Personalized Skill Mastery Plan' }}
              </h3>
              <p class="text-[11px] text-purple-600 dark:text-purple-300">
                {{ isKhmer ? 'ផែនទីបង្ហាញផ្លូវសម្រាប់ការអនុវត្តជាក់ស្តែងដើម្បីពន្លឿនជំនាញ' : 'Targeted practice roadmap to accelerate your skills' }}
              </p>
            </div>
          </div>
          <button
            @click="isStudyPlanModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 max-h-80 overflow-y-auto custom-scrollbar pr-1">
          <div
            v-if="aiRecommendation.study_plan && aiRecommendation.study_plan.length > 0"
            v-for="(plan, idx) in aiRecommendation.study_plan"
            :key="idx"
            class="p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 text-xs"
          >
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-purple-100 dark:bg-purple-600/20 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center font-bold font-mono text-xs shrink-0">
                {{ idx + 1 }}
              </span>
              <div>
                <p class="font-bold text-slate-900 dark:text-white">{{ plan.day }}: {{ plan.title }}</p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ plan.type }}</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-purple-600 dark:text-purple-300 font-mono text-[10px] font-bold shrink-0">
              {{ plan.duration }}
            </span>
          </div>
          <div v-else class="p-4 rounded-2xl bg-purple-50/50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800/40 text-center space-y-2">
            <p class="text-xs font-bold text-slate-900 dark:text-white">
              {{ isKhmer ? 'AI បានបង្កើតការណែនាំដំបូងសម្រាប់អ្នក៖' : 'Initial AI Guidance:' }}
            </p>
            <p class="text-xs text-slate-600 dark:text-slate-300">
              {{ aiRecommendation.primary }}
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
              {{ aiRecommendation.secondary }}
            </p>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-200 dark:border-slate-800 text-xs">
          <Link
            href="/student/ai-tutor"
            class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-bold flex items-center gap-1"
          >
            <span>{{ isKhmer ? 'សួរ AI Tutor' : 'Ask AI Tutor' }}</span>
            <span>→</span>
          </Link>

          <div class="flex items-center gap-2">
            <button
              @click="isStudyPlanModalOpen = false"
              class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold cursor-pointer"
            >
              {{ isKhmer ? 'បិទ' : 'Close' }}
            </button>
            <Link
              href="/student/ai-path/recommended"
              class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md cursor-pointer"
            >
              {{ isKhmer ? 'បើកគន្លងសិក្សា' : 'Launch Learning Path' }}
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: SKILL ACTION MODAL ================= -->
    <div
      v-if="isSkillActionModalOpen && selectedSkillAction"
      class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-slate-900 dark:text-white">{{ selectedSkillAction.name }}</h3>
            <p class="text-[11px] text-purple-600 dark:text-purple-300">{{ selectedSkillAction.category }} • {{ selectedSkillAction.level }} ({{ selectedSkillAction.progress }}%)</p>
          </div>
          <button
            @click="isSkillActionModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center font-bold text-sm cursor-pointer"
          >
            ✕
          </button>
        </div>

        <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2 text-xs">
          <p class="text-slate-600 dark:text-slate-300">{{ selectedSkillAction.description }}</p>
          <div class="flex items-center justify-between pt-1">
            <span class="text-slate-500 dark:text-slate-400">{{ isKhmer ? 'កម្រិតស្ទាត់ជំនាញបច្ចុប្បន្ន:' : 'Current Mastery:' }}</span>
            <span class="font-mono font-bold text-purple-600 dark:text-purple-400">{{ selectedSkillAction.progress }}%</span>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800 text-xs">
          <button
            @click="isSkillActionModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold cursor-pointer"
          >
            {{ isKhmer ? 'បិទ' : 'Close' }}
          </button>
          <Link
            href="/student/quizzes/practice"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md cursor-pointer"
          >
            {{ isKhmer ? 'ចាប់ផ្តើមអនុវត្ត' : 'Start Practice Drill' }}
          </Link>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}
:root.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #1E293B;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
:root.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #334155;
}
</style>
