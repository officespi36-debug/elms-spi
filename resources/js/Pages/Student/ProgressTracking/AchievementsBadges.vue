<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

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
  overall_level: 64,
  level_name: 'Intermediate',
  mastered_count: 16,
  total_skills: 25,
  overall_trend: '12% this month',
  skills_in_progress: 12,
  in_progress_note: 'Keep practicing!',
  need_work_count: 6,
  mastered_skills: 16,
  mastered_note: 'Amazing progress!',
  new_mastered_this_month: 3,
  skill_points: 2450,
  points_trend: '+150 this week',
  top_category: 'Front-End Development',
  top_category_note: 'Your strongest area',
}

const defaultSkills: SkillItem[] = [
  { id: 1, name: 'JavaScript', description: 'Core JavaScript concepts', category: 'Front-End', level: 'Advanced', progress: 85, trend: '+15%', action: 'Practice', code: 'JS', icon_bg: 'from-amber-400 to-amber-500 text-slate-950' },
  { id: 2, name: 'HTML & CSS', description: 'Structure and styling', category: 'Front-End', level: 'Advanced', progress: 90, trend: '+10%', action: 'Practice', code: '5', icon_bg: 'from-orange-500 to-amber-500 text-white' },
  { id: 3, name: 'React.js', description: 'Build interactive UIs', category: 'Front-End', level: 'Intermediate', progress: 65, trend: '+8%', action: 'Continue', code: '⚛️', icon_bg: 'from-cyan-400 to-blue-500 text-white' },
  { id: 4, name: 'Node.js', description: 'JavaScript runtime', category: 'Back-End', level: 'Intermediate', progress: 55, trend: '+12%', action: 'Continue', code: 'node', icon_bg: 'from-emerald-500 to-teal-600 text-white' },
  { id: 5, name: 'SQL', description: 'Database queries', category: 'Database', level: 'Intermediate', progress: 50, trend: '+5%', action: 'Practice', code: '🗄️', icon_bg: 'from-blue-500 to-indigo-600 text-white' },
  { id: 6, name: 'Git & GitHub', description: 'Version control', category: 'Tools', level: 'Beginner', progress: 35, trend: '+3%', action: 'Learn', code: 'Git', icon_bg: 'from-rose-500 to-orange-500 text-white' },
  { id: 7, name: 'UI/UX Design', description: 'Design user interfaces', category: 'Design', level: 'Beginner', progress: 25, trend: '+2%', action: 'Learn', code: 'UI', icon_bg: 'from-purple-500 to-indigo-600 text-white' },
  { id: 8, name: 'Python', description: 'Programming basics', category: 'Back-End', level: 'Not Started', progress: 0, trend: '—', action: 'Start', code: '🐍', icon_bg: 'from-blue-600 to-cyan-600 text-white' },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const categories = computed(() => props.analytics?.categories || [
  { name: 'Front-End', score: 70, color: '#8B5CF6' },
  { name: 'Back-End', score: 55, color: '#3B82F6' },
  { name: 'Database', score: 50, color: '#F59E0B' },
  { name: 'Tools', score: 45, color: '#10B981' },
  { name: 'Design', score: 30, color: '#F43F5E' },
  { name: 'Other', score: 20, color: '#94A3B8' },
])
const levelDistribution = computed(() => props.analytics?.level_distribution || { advanced: 5, intermediate: 7, beginner: 4, not_started: 3 })
const rawSkills = computed(() => props.analytics?.skills || defaultSkills)
const improvementFocus = computed(() => props.analytics?.improvement_focus || [
  { id: 1, skill: 'SQL Queries', description: 'Increase practice in JOIN and WHERE clauses', priority: 'Low', badge_color: 'rose', progress: 50, code: '🗄️', icon_bg: 'from-blue-500 to-indigo-600' },
  { id: 2, skill: 'React Components', description: 'Work on Hooks and State Management', priority: 'Medium', badge_color: 'amber', progress: 65, code: '⚛️', icon_bg: 'from-cyan-400 to-blue-500' },
  { id: 3, skill: 'Git Workflows', description: 'Learn branching and pull requests', priority: 'Medium', badge_color: 'amber', progress: 35, code: 'Git', icon_bg: 'from-rose-500 to-orange-500' },
])
const recentlyMastered = computed(() => props.analytics?.recently_mastered || [
  { id: 1, title: 'CSS Flexbox', date: 'Mastered on May 28, 2025', icon: '5', icon_bg: 'from-blue-400 to-indigo-500' },
  { id: 2, title: 'JavaScript DOM', date: 'Mastered on May 25, 2025', icon: 'JS', icon_bg: 'from-amber-400 to-amber-500 text-slate-950' },
  { id: 3, title: 'Responsive Design', date: 'Mastered on May 22, 2025', icon: '5', icon_bg: 'from-orange-500 to-amber-500' },
])
const aiRecommendation = computed(() => props.analytics?.ai_recommendation || {
  primary: 'Focus on improving your SQL skills.',
  secondary: 'Practice more JOIN operations and subqueries.',
  study_plan: [
    { day: 'Monday', title: 'SQL JOIN Fundamentals & Syntax Drill', duration: '35 minutes', type: 'Interactive Practice' },
    { day: 'Tuesday', title: 'React Hooks (useEffect & Custom Hooks)', duration: '40 minutes', type: 'Code Lab' },
    { day: 'Wednesday', title: 'Git Branching & Merge Conflict Simulation', duration: '25 minutes', type: 'Terminal Drill' },
    { day: 'Thursday', title: 'Database Subqueries & Aggregate Functions', duration: '30 minutes', type: 'Query Practice' },
    { day: 'Friday', title: 'Full-Stack Skill Integration Assessment', duration: '45 minutes', type: 'Practical Test' },
  ]
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

const openSkillAction = (skill: SkillItem) => {
  selectedSkillAction.value = skill
  isSkillActionModalOpen.value = true
}

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

// SVG Trend Line Chart
const defaultTrendPoints = [
  { date: 'May 1', score: 30, new_skills: 1, improved: 2 },
  { date: 'May 6', score: 35, new_skills: 2, improved: 3 },
  { date: 'May 11', score: 42, new_skills: 1, improved: 4 },
  { date: 'May 16', score: 48, new_skills: 2, improved: 5 },
  { date: 'May 20', score: 58, new_skills: 1, improved: 6 },
  { date: 'May 21', score: 60, new_skills: 0, improved: 4 },
  { date: 'May 26', score: 68, new_skills: 1, improved: 5 },
  { date: 'May 31', score: 72, new_skills: 2, improved: 7 },
]

const trendPoints = computed(() => props.analytics?.trend_chart?.points || defaultTrendPoints)

const chartSvgPoints = computed<ChartPoint[]>(() => {
  const pts = trendPoints.value
  if (!pts || pts.length === 0) return []
  const width = 360
  const height = 140
  const paddingX = 15
  const paddingY = 15
  const usableW = width - paddingX * 2
  const usableH = height - paddingY * 2

  const stepX = usableW / Math.max(1, pts.length - 1)

  return pts.map((p, idx) => {
    const x = paddingX + idx * stepX
    const y = paddingY + (1 - (p.score / 100)) * usableH
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
  <StudentLayout title="Skills Progress — Progress & Analytics">
    <div class="space-y-6 max-w-7xl mx-auto pb-10">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
            <span>Skills Progress</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">📈</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            Track and improve your skills. Master more skills to become a professional.
          </p>
        </div>

        <button
          @click="isStudyPlanModalOpen = true"
          class="px-4 py-2 rounded-xl bg-slate-900/90 border border-slate-800 hover:border-purple-500/40 text-slate-300 hover:text-white text-xs font-bold transition-all shadow-md self-start sm:self-auto flex items-center gap-2 group"
        >
          <span class="text-purple-400 group-hover:rotate-12 transition-transform">🤖</span>
          <span>AI Study Plan</span>
        </button>
      </div>

      <!-- ================= 2. SUMMARY CARDS (5 TOP CARDS) ================= -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- CARD 1: Overall Skills Level -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-purple-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">Overall Skills Level</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.overall_level }}%</p>
            <p class="text-[11px] text-slate-400 font-medium">
              You've mastered <strong class="text-white">{{ summary.mastered_count }} of {{ summary.total_skills }}</strong> Total Skills
            </p>
            <p class="text-[11px] text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>↑</span>
              <span>{{ summary.overall_trend }}</span>
            </p>
          </div>
          <div class="relative w-14 h-14 flex items-center justify-center shrink-0">
            <svg class="w-14 h-14 -rotate-90 transform" viewBox="0 0 36 36">
              <path
                class="text-slate-800"
                stroke-width="3"
                stroke="currentColor"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />
              <path
                class="text-purple-500 transition-all duration-1000"
                stroke-dasharray="64, 100"
                stroke-width="3.2"
                stroke-linecap="round"
                stroke="currentColor"
                fill="none"
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
              />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-purple-300">
              64%
            </span>
          </div>
        </div>

        <!-- CARD 2: Skills In Progress -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-purple-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">Skills In Progress</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.skills_in_progress }}</p>
            <p class="text-[11px] text-purple-300 font-medium">{{ summary.in_progress_note }}</p>
            <p class="text-[11px] text-slate-400 font-medium pt-0.5">
              {{ summary.need_work_count }} skills need more work
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-purple-600/20 border border-purple-500/30 flex items-center justify-center text-purple-400 text-lg shadow-inner group-hover:scale-110 transition-transform shrink-0">
            📖
          </div>
        </div>

        <!-- CARD 3: Mastered Skills -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-amber-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">Mastered Skills</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ summary.mastered_skills }}</p>
            <p class="text-[11px] text-amber-300 font-medium">{{ summary.mastered_note }}</p>
            <p class="text-[11px] text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>↑</span>
              <span>{{ summary.new_mastered_this_month }} new this month</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg shadow-inner group-hover:scale-110 transition-transform shrink-0">
            🏆
          </div>
        </div>

        <!-- CARD 4: Skill Points -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-amber-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">Skill Points</p>
            <p class="text-2xl font-black text-white font-mono tracking-tight">{{ Number(summary.skill_points).toLocaleString() }}</p>
            <p class="text-[11px] text-emerald-400 flex items-center gap-1 font-medium pt-0.5">
              <span>{{ summary.points_trend }}</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg shadow-inner group-hover:scale-110 transition-transform shrink-0">
            🎖️
          </div>
        </div>

        <!-- CARD 5: Top Category -->
        <div class="relative overflow-hidden bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4.5 shadow-xl hover:border-emerald-500/30 transition-all flex items-center justify-between group">
          <div class="space-y-1 z-10">
            <p class="text-[11px] text-slate-400 font-medium">Top Category</p>
            <p class="text-sm font-black text-white truncate max-w-[120px]">{{ summary.top_category }}</p>
            <p class="text-[11px] text-emerald-400 font-medium pt-0.5">{{ summary.top_category_note }}</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg shadow-inner group-hover:scale-110 transition-transform shrink-0">
            &lt;/&gt;
          </div>
        </div>

      </div>

      <!-- ================= 3. FILTER & SORT TAB BAR ================= -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-[#0F172A]/80 border border-slate-800/80 rounded-2xl p-3 shadow-lg">
        
        <!-- Left Tab Pills -->
        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="setTab('all')"
            :class="[
              activeTab === 'all'
                ? 'bg-purple-600 text-white shadow-md shadow-purple-900/40'
                : 'bg-slate-900/90 text-slate-400 hover:text-white border border-slate-800',
              'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer'
            ]"
          >
            All Skills
          </button>

          <button
            @click="setTab('in_progress')"
            :class="[
              activeTab === 'in_progress'
                ? 'bg-purple-600 text-white shadow-md shadow-purple-900/40'
                : 'bg-slate-900/90 text-slate-400 hover:text-white border border-slate-800',
              'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer'
            ]"
          >
            In Progress
          </button>

          <button
            @click="setTab('mastered')"
            :class="[
              activeTab === 'mastered'
                ? 'bg-purple-600 text-white shadow-md shadow-purple-900/40'
                : 'bg-slate-900/90 text-slate-400 hover:text-white border border-slate-800',
              'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer'
            ]"
          >
            Mastered
          </button>

          <button
            @click="setTab('not_started')"
            :class="[
              activeTab === 'not_started'
                ? 'bg-purple-600 text-white shadow-md shadow-purple-900/40'
                : 'bg-slate-900/90 text-slate-400 hover:text-white border border-slate-800',
              'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer'
            ]"
          >
            Not Started
          </button>
        </div>

        <!-- Right Dropdowns -->
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-400">Category:</span>
            <select
              v-model="selectedCategory"
              @change="handleFilterChange"
              class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
            >
              <option value="all">All</option>
              <option value="Front-End">Front-End</option>
              <option value="Back-End">Back-End</option>
              <option value="Database">Database</option>
              <option value="Tools">Tools</option>
              <option value="Design">Design</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-400">Sort by:</span>
            <select
              v-model="selectedSort"
              @change="handleFilterChange"
              class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
            >
              <option value="progress_desc">Progress (High to Low)</option>
              <option value="progress_asc">Progress (Low to High)</option>
              <option value="name_asc">Skill Name (A-Z)</option>
            </select>
          </div>
        </div>

      </div>

      <!-- ================= 4. MIDDLE MAIN SPLIT: SKILLS OVERVIEW TABLE & RIGHT STACK ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- LEFT (8/12): Skills Overview Table -->
        <div class="lg:col-span-8 bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
            <h2 class="text-base font-bold text-white tracking-tight">Skills Overview</h2>
            <span class="text-xs text-slate-400">{{ displayedSkills.length }} Skills Displayed</span>
          </div>

          <!-- Table Container -->
          <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs text-slate-300">
              <thead class="text-[11px] uppercase font-bold text-slate-400 border-b border-slate-800/80">
                <tr>
                  <th class="py-2.5 px-3">Skill</th>
                  <th class="py-2.5 px-3">Category</th>
                  <th class="py-2.5 px-3">Level</th>
                  <th class="py-2.5 px-3">Progress</th>
                  <th class="py-2.5 px-3">Trend</th>
                  <th class="py-2.5 px-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/40 font-medium">
                <tr
                  v-for="s in displayedSkills"
                  :key="s.id"
                  class="hover:bg-slate-900/80 transition-colors group"
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
                        {{ s.code || 'JS' }}
                      </div>
                      <div>
                        <p class="font-bold text-white group-hover:text-purple-300 transition-colors">{{ s.name }}</p>
                        <p class="text-[10px] text-slate-400">{{ s.description }}</p>
                      </div>
                    </div>
                  </td>

                  <!-- Category Pill -->
                  <td class="py-3 px-3">
                    <span class="px-2 py-0.5 rounded-md bg-slate-900 border border-slate-700/80 text-slate-300 text-[10px] font-medium">
                      {{ s.category }}
                    </span>
                  </td>

                  <!-- Level Badge -->
                  <td class="py-3 px-3">
                    <span
                      class="px-2.5 py-1 rounded-full text-[10px] font-bold border inline-block"
                      :class="[
                        s.level === 'Mastered' || s.level === 'Advanced' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' :
                        s.level === 'Intermediate' ? 'bg-blue-500/20 text-blue-300 border-blue-500/30' :
                        s.level === 'Beginner' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' :
                        'bg-slate-800 text-slate-400 border-slate-700'
                      ]"
                    >
                      {{ s.level }}
                    </span>
                  </td>

                  <!-- Progress Bar -->
                  <td class="py-3 px-3 w-36">
                    <div class="flex items-center gap-2.5">
                      <div class="w-full h-2 rounded-full bg-slate-950 overflow-hidden border border-slate-800">
                        <div
                          class="h-full rounded-full bg-purple-500 transition-all duration-500"
                          :style="{ width: `${s.progress}%` }"
                        ></div>
                      </div>
                      <span class="text-xs font-mono font-bold text-white w-8 text-right">{{ s.progress }}%</span>
                    </div>
                  </td>

                  <!-- Trend -->
                  <td class="py-3 px-3">
                    <span
                      :class="[
                        s.trend.startsWith('+') || s.trend.startsWith('↑') ? 'text-emerald-400 font-bold' : 'text-slate-500',
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
                      class="px-3 py-1 rounded-xl bg-purple-600/30 hover:bg-purple-600 border border-purple-500/40 text-purple-300 hover:text-white font-bold text-xs transition-all shadow-sm flex items-center gap-1.5 ml-auto cursor-pointer"
                    >
                      <span>{{ s.action }}</span>
                      <span class="text-[9px]">▾</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Bottom View More Button -->
          <div class="pt-2 text-center border-t border-slate-800/60">
            <button
              @click="showMore = !showMore"
              class="text-xs font-bold text-slate-400 hover:text-purple-400 transition-colors inline-flex items-center gap-1 cursor-pointer"
            >
              <span>{{ showMore ? 'Show Less Skills ∧' : 'View More Skills ∨' }}</span>
            </button>
          </div>
        </div>

        <!-- RIGHT (4/12): Stack of Category, Level Distribution & Recently Mastered -->
        <div class="lg:col-span-4 space-y-6">

          <!-- 1. Skills by Category Card -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-white tracking-tight">Skills by Category</h3>
              <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">View All</span>
            </div>

            <div class="flex items-center justify-between gap-4 py-2">
              <!-- Donut Chart -->
              <div class="relative w-28 h-28 flex items-center justify-center shrink-0">
                <svg class="w-28 h-28 -rotate-90 transform" viewBox="0 0 42 42">
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#1E293B" stroke-width="4.5" />
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#8B5CF6" stroke-width="4.5" stroke-dasharray="25, 75" stroke-dashoffset="0" />
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#3B82F6" stroke-width="4.5" stroke-dasharray="20, 80" stroke-dashoffset="-25" />
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#F59E0B" stroke-width="4.5" stroke-dasharray="18, 82" stroke-dashoffset="-45" />
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#10B981" stroke-width="4.5" stroke-dasharray="15, 85" stroke-dashoffset="-63" />
                  <circle cx="21" cy="21" r="15.915" fill="none" stroke="#F43F5E" stroke-width="4.5" stroke-dasharray="12, 88" stroke-dashoffset="-78" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-base font-black text-white font-mono">{{ summary.overall_level }}%</span>
                  <span class="text-[9px] text-slate-400">Overall</span>
                </div>
              </div>

              <!-- Category Legend -->
              <div class="space-y-1.5 text-xs flex-1">
                <div v-for="cat in categories" :key="cat.name" class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: cat.color }"></span>
                    <span class="text-slate-300 text-[11px]">{{ cat.name }}</span>
                  </div>
                  <span class="font-bold text-white font-mono text-[11px]">{{ cat.score }}%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Skill Level Distribution Card -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Skill Level Distribution</h3>
              <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">View All</span>
            </div>

            <div class="space-y-2 text-xs">
              <!-- Advanced -->
              <div class="space-y-1">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-300 font-medium">Advanced</span>
                  <span class="text-slate-400 font-mono">{{ levelDistribution.advanced }} skills</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
                  <div class="h-full bg-emerald-500 rounded-full w-[65%]"></div>
                </div>
              </div>

              <!-- Intermediate -->
              <div class="space-y-1">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-300 font-medium">Intermediate</span>
                  <span class="text-slate-400 font-mono">{{ levelDistribution.intermediate }} skills</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
                  <div class="h-full bg-blue-500 rounded-full w-[80%]"></div>
                </div>
              </div>

              <!-- Beginner -->
              <div class="space-y-1">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-300 font-medium">Beginner</span>
                  <span class="text-slate-400 font-mono">{{ levelDistribution.beginner }} skills</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
                  <div class="h-full bg-amber-500 rounded-full w-[45%]"></div>
                </div>
              </div>

              <!-- Not Started -->
              <div class="space-y-1">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-300 font-medium">Not Started</span>
                  <span class="text-slate-400 font-mono">{{ levelDistribution.not_started }} skills</span>
                </div>
                <div class="w-full h-2 rounded-full bg-slate-950 border border-slate-800 overflow-hidden">
                  <div class="h-full bg-slate-600 rounded-full w-[30%]"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Recently Mastered Skills Card -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Recently Mastered Skills</h3>
              <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">View All</span>
            </div>

            <div class="space-y-2.5">
              <div
                v-for="item in recentlyMastered"
                :key="item.id"
                class="p-2 rounded-2xl bg-slate-900/70 border border-slate-800/60 flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      item.icon_bg,
                      'w-7 h-7 rounded-lg bg-gradient-to-br flex items-center justify-center text-[10px] font-black shrink-0 font-mono shadow-sm'
                    ]"
                  >
                    {{ item.icon }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">{{ item.title }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ item.date }}</p>
                  </div>
                </div>
                <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-[10px] shrink-0 font-bold">
                  ✓
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>

      <!-- ================= 5. BOTTOM ROW: 3 EQUAL-WIDTH WIDGETS ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- 1. Skill Progress Over Time (Line Chart) -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-3 relative">
          <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
            <h3 class="text-sm font-bold text-white tracking-tight">Skill Progress Over Time</h3>
            <select
              class="bg-slate-900 border border-slate-700/80 text-[11px] font-semibold text-slate-300 rounded-xl px-2.5 py-1 focus:outline-none focus:border-purple-500 cursor-pointer"
            >
              <option>This Month</option>
              <option>Last 3 Months</option>
              <option>All Time</option>
            </select>
          </div>

          <!-- Line Chart SVG -->
          <div class="relative w-full h-40 pt-2">
            
            <!-- Y-Axis labels -->
            <div class="absolute left-0 inset-y-2 flex flex-col justify-between text-[9px] text-slate-500 font-mono select-none pointer-events-none">
              <span>100%</span>
              <span>75%</span>
              <span>50%</span>
              <span>25%</span>
              <span>0%</span>
            </div>

            <div class="ml-8 h-full relative">
              <!-- Grid lines -->
              <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                <div class="w-full border-b border-slate-800/40"></div>
                <div class="w-full border-b border-slate-800/40"></div>
                <div class="w-full border-b border-slate-800/40"></div>
                <div class="w-full border-b border-slate-800/40"></div>
                <div class="w-full border-b border-slate-800/60"></div>
              </div>

              <!-- Chart SVG -->
              <svg class="w-full h-[110px] overflow-visible" viewBox="0 0 360 140" preserveAspectRatio="none">
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
                  r="3.5"
                  class="fill-slate-950 stroke-purple-400 stroke-2 hover:r-5 hover:stroke-white transition-all cursor-pointer"
                />
              </svg>

              <!-- May 20 Tooltip Pin Callout matching screenshot -->
              <div class="absolute left-[54%] top-[26%] -translate-x-1/2 bg-slate-900/95 border border-purple-500/60 rounded-lg px-2 py-1 shadow-lg text-[9px] text-slate-200 pointer-events-none whitespace-nowrap">
                <p class="font-mono"><strong class="text-white">May 20</strong> Progress: <span class="text-purple-400 font-bold">58%</span></p>
              </div>

              <!-- Bottom X-Axis labels -->
              <div class="w-full flex justify-between text-[9px] text-slate-500 font-mono mt-1">
                <span>May 1</span>
                <span>May 6</span>
                <span>May 11</span>
                <span>May 16</span>
                <span>May 21</span>
                <span>May 26</span>
                <span>May 31</span>
              </div>
            </div>

          </div>
        </div>

        <!-- 2. Improvement Focus -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 md:p-6 shadow-xl space-y-3">
          <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
            <h3 class="text-sm font-bold text-white tracking-tight">Improvement Focus</h3>
            <span class="text-xs text-purple-400 font-bold cursor-pointer hover:underline">View All</span>
          </div>

          <div class="space-y-2.5">
            <div
              v-for="focus in improvementFocus"
              :key="focus.id"
              class="p-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/60 flex items-center justify-between gap-3 group hover:border-purple-500/30 transition-all cursor-pointer"
            >
              <div class="flex items-center gap-3 min-w-0">
                <div
                  :class="[
                    focus.icon_bg,
                    'w-8 h-8 rounded-xl bg-gradient-to-br flex items-center justify-center text-xs font-black shrink-0 font-mono shadow-sm text-white'
                  ]"
                >
                  {{ focus.code }}
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors truncate">
                    {{ focus.skill }}
                  </p>
                  <p class="text-[10px] text-slate-400 truncate">{{ focus.description }}</p>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <span
                  class="px-2 py-0.5 rounded-full text-[9px] font-bold border"
                  :class="focus.badge_color === 'rose' ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border-amber-500/30'"
                >
                  {{ focus.priority }}
                </span>
                <span class="text-xs font-bold font-mono text-white">{{ focus.progress }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. AI Skill Recommendation -->
        <div class="bg-gradient-to-br from-[#10132B] via-[#0F172A] to-[#1E1138] border border-purple-900/50 rounded-3xl p-5 md:p-6 shadow-2xl flex flex-col justify-between space-y-4 relative overflow-hidden group">
          
          <div class="absolute -right-8 -bottom-8 w-36 h-36 bg-purple-600/15 rounded-full blur-3xl pointer-events-none"></div>

          <div>
            <div class="flex items-center justify-between border-b border-purple-900/40 pb-3">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[10px] font-black uppercase">
                  AI
                </span>
                <h3 class="text-sm font-bold text-white tracking-tight">AI Skill Recommendation</h3>
              </div>
            </div>

            <div class="flex items-center gap-4 mt-3">
              <div class="w-12 h-12 rounded-2xl bg-purple-600/20 border border-purple-500/40 flex items-center justify-center text-3xl shadow-inner animate-pulse shrink-0">
                🤖
              </div>
              <div class="space-y-1 text-xs text-slate-300">
                <p class="font-medium text-white">"{{ aiRecommendation.primary }}"</p>
                <p class="text-[11px] text-slate-400">"{{ aiRecommendation.secondary }}"</p>
              </div>
            </div>
          </div>

          <button
            @click="isStudyPlanModalOpen = true"
            class="w-full py-2.5 px-4 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/50 hover:shadow-purple-700/40 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-95 z-10"
          >
            <span>✨</span>
            <span>Get AI Study Plan</span>
            <span>&gt;</span>
          </button>

        </div>

      </div>

    </div>

    <!-- ================= MODAL: AI STUDY PLAN ================= -->
    <div
      v-if="isStudyPlanModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <span class="text-2xl">🤖</span>
            <div>
              <h3 class="text-base font-black text-white">Personalized Skill Mastery Plan</h3>
              <p class="text-[11px] text-purple-300">Targeted practice roadmap to accelerate your skills</p>
            </div>
          </div>
          <button
            @click="isStudyPlanModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 max-h-80 overflow-y-auto custom-scrollbar pr-1">
          <div
            v-for="(plan, idx) in aiRecommendation.study_plan"
            :key="idx"
            class="p-3 bg-slate-950 rounded-2xl border border-slate-800 flex items-center justify-between gap-3 text-xs"
          >
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-purple-600/20 text-purple-300 border border-purple-500/30 flex items-center justify-center font-bold font-mono text-xs shrink-0">
                {{ idx + 1 }}
              </span>
              <div>
                <p class="font-bold text-white">{{ plan.day }}: {{ plan.title }}</p>
                <p class="text-[10px] text-slate-400">{{ plan.type }}</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-slate-900 border border-slate-700 text-purple-300 font-mono text-[10px] font-bold shrink-0">
              {{ plan.duration }}
            </span>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-800 text-xs">
          <Link
            href="/student/ai-tutor"
            class="text-purple-400 hover:text-purple-300 font-bold flex items-center gap-1"
          >
            <span>Ask AI Tutor</span>
            <span>→</span>
          </Link>

          <div class="flex items-center gap-2">
            <button
              @click="isStudyPlanModalOpen = false"
              class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold"
            >
              Close
            </button>
            <Link
              href="/student/ai-path/recommended"
              class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md"
            >
              Launch Learning Path
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: SKILL ACTION MODAL ================= -->
    <div
      v-if="isSkillActionModalOpen && selectedSkillAction"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
          <div>
            <h3 class="text-base font-black text-white">{{ selectedSkillAction.name }}</h3>
            <p class="text-[11px] text-purple-300">{{ selectedSkillAction.category }} • {{ selectedSkillAction.level }} ({{ selectedSkillAction.progress }}%)</p>
          </div>
          <button
            @click="isSkillActionModalOpen = false"
            class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 space-y-2 text-xs">
          <p class="text-slate-300">{{ selectedSkillAction.description }}</p>
          <div class="flex items-center justify-between pt-1">
            <span class="text-slate-400">Current Mastery:</span>
            <span class="font-mono font-bold text-purple-400">{{ selectedSkillAction.progress }}%</span>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isSkillActionModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Close
          </button>
          <Link
            href="/student/quizzes/practice"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md"
          >
            Start Practice Drill
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
  background: #0B0F19;
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #1E293B;
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #334155;
}
</style>
