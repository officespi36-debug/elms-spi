<template>
  <StudentLayout>
    <div class="min-h-screen bg-[#F8FAFC] dark:bg-[#0F172A] text-slate-800 dark:text-slate-100 py-6 px-4 sm:px-6 lg:px-8 font-sans transition-colors duration-200">
      <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Breadcrumb & Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mb-1">
              <Link href="/student/dashboard" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">ផ្ទាំងគ្រប់គ្រង</Link>
              <span>/</span>
              <span>អនុវត្ត & កិច្ចការ</span>
              <span>/</span>
              <span class="text-purple-600 dark:text-purple-400 font-semibold">ស៊ីមុយឡាទ័រ</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
              <span>🎮</span>
              <span>ស៊ីមុយឡាទ័រអនុវត្ត (Simulations)</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
              ហ្វឹកហាត់ដោះស្រាយស្ថានភាព និងការសម្រេចចិត្តជាក់ស្តែងលើជំនាញទាំង ៥ តាមរយៈសេណារីយ៉ូពិត។
            </p>
          </div>

          <!-- Quick Action / Status indicator -->
          <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 border border-purple-200 dark:border-purple-800/50">
              <span class="w-2 h-2 rounded-full bg-purple-600 animate-pulse"></span>
              Phase A: Branching Scenarios
            </span>
          </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-[#1E293B] p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">សេណារីយ៉ូសរុប</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ counts.total }}</p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">សម្រាប់វគ្គសិក្សារបស់អ្នក</p>
          </div>
          <div class="bg-white dark:bg-[#1E293B] p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">បានឆ្លងផុត (Passed)</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ counts.passed }}</p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">សម្រេចពិន្ទុគោលដៅ</p>
          </div>
          <div class="bg-white dark:bg-[#1E293B] p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">កំពុងអនុវត្ត</p>
            <p class="text-2xl font-bold text-amber-500 dark:text-amber-400 mt-1">{{ counts.in_progress }}</p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Attempt កំពុងដំណើរការ</p>
          </div>
          <div class="bg-white dark:bg-[#1E293B] p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">បានបញ្ចប់</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ counts.completed }}</p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">បញ្ចប់យ៉ាងតិច ១ ដង</p>
          </div>
        </div>

        <!-- Filter Chips (Skill & Status) -->
        <div class="bg-white dark:bg-[#1E293B] p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-3">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">ចម្រោះតាមជំនាញ៖</span>
            <div class="flex items-center gap-1.5 flex-wrap">
              <button
                v-for="s in skillOptions"
                :key="s.value"
                @click="applyFilter('skill', s.value)"
                type="button"
                :class="[
                  currentSkill === s.value
                    ? 'bg-purple-600 text-white font-semibold shadow-xs'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 font-medium',
                  'px-3 py-1 rounded-xl text-xs transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500'
                ]"
              >
                {{ s.label }}
              </button>
            </div>
          </div>

          <div class="flex items-center justify-between flex-wrap gap-2 pt-2 border-t border-slate-100 dark:border-slate-800/80">
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">ស្ថានភាព៖</span>
            <div class="flex items-center gap-1.5 flex-wrap">
              <button
                v-for="st in statusOptions"
                :key="st.value"
                @click="applyFilter('status', st.value)"
                type="button"
                :class="[
                  currentStatus === st.value
                    ? 'bg-purple-600 text-white font-semibold shadow-xs'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 font-medium',
                  'px-3 py-1 rounded-xl text-xs transition-all duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500'
                ]"
              >
                {{ st.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Simulations Grid -->
        <div v-if="simulations.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="sim in simulations"
            :key="sim.id"
            class="bg-white dark:bg-[#1E293B] rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs hover:shadow-md transition-all duration-200 flex flex-col justify-between overflow-hidden group"
          >
            <!-- Card Body -->
            <div class="p-5 space-y-3.5">
              <div class="flex items-start justify-between gap-2">
                <!-- Skill Badge -->
                <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider', getSkillBadgeClass(sim.skill)]">
                  <span>{{ getSkillIcon(sim.skill) }}</span>
                  <span>{{ getSkillName(sim.skill) }}</span>
                </span>

                <!-- Status Badge -->
                <span
                  v-if="sim.status === 'in_progress'"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                  កំពុងអនុវត្ត
                </span>
                <span
                  v-else-if="sim.status === 'passed'"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                  បានជាប់ ({{ sim.best_score }}%)
                </span>
                <span
                  v-else-if="sim.status === 'needs_practice'"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border border-rose-200 dark:border-rose-800/40"
                >
                  ពិន្ទុ {{ sim.best_score }}% (ធ្លាក់)
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                >
                  មិនទាន់ចាប់ផ្តើម
                </span>
              </div>

              <!-- Title & Course -->
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors line-clamp-2">
                  {{ sim.title_kh || sim.title }}
                </h3>
                <p v-if="sim.course" class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-1 flex items-center gap-1">
                  <span class="font-medium text-slate-600 dark:text-slate-300">{{ sim.course.code }}:</span>
                  <span>{{ sim.course.title }}</span>
                </p>
              </div>

              <!-- Instructions preview -->
              <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2 leading-relaxed bg-slate-50 dark:bg-slate-800/50 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                {{ sim.instructions || 'សេណារីយ៉ូអនុវត្តការសម្រេចចិត្តប្រកបដោយវិជ្ជាជីវៈ។' }}
              </p>

              <!-- Meta specs -->
              <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px]">
                <div>
                  <span class="text-slate-400 dark:text-slate-500 block">រយៈពេល</span>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">~{{ sim.estimated_minutes }} នាទី</span>
                </div>
                <div>
                  <span class="text-slate-400 dark:text-slate-500 block">ពិន្ទុជាប់</span>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">≥ {{ sim.pass_score }}%</span>
                </div>
                <div>
                  <span class="text-slate-400 dark:text-slate-500 block">Attempt</span>
                  <span class="font-semibold text-slate-700 dark:text-slate-200">{{ sim.attempts_count }} / {{ sim.max_attempts }}</span>
                </div>
              </div>
            </div>

            <!-- Card Actions -->
            <div class="p-4 bg-slate-50/70 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
              <button
                v-if="sim.status === 'in_progress'"
                @click="startOrResume(sim)"
                type="button"
                :disabled="isSubmitting"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 active:scale-[0.98] transition-all duration-150 shadow-xs cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
              >
                <span>⚡</span>
                <span>បន្តការអនុវត្ត (Continue)</span>
              </button>

              <button
                v-else-if="sim.can_attempt"
                @click="startOrResume(sim)"
                type="button"
                :disabled="isSubmitting"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 active:scale-[0.98] transition-all duration-150 shadow-xs cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-500"
              >
                <span>▶</span>
                <span>{{ sim.attempts_count > 0 ? 'អនុវត្តឡើងវិញ (Retry)' : 'ចាប់ផ្តើមអនុវត្ត (Start)' }}</span>
              </button>

              <Link
                v-else-if="sim.latest_attempt_id"
                :href="`/student/simulation-attempts/${sim.latest_attempt_id}/summary`"
                class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-slate-200/80 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 transition-all duration-150 text-center"
              >
                <span>📊</span>
                <span>មើលលទ្ធផលសង្ខេប</span>
              </Link>

              <span
                v-else
                class="w-full text-center py-2.5 text-xs text-slate-400 dark:text-slate-500 font-medium"
              >
                មិនទាន់ចុះឈ្មោះវគ្គសិក្សា
              </span>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-else
          class="bg-white dark:bg-[#1E293B] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-12 text-center max-w-lg mx-auto space-y-4"
        >
          <div class="w-16 h-16 rounded-full bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center mx-auto text-2xl">
            🎮
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">ពុំទាន់មានស៊ីមុយឡាទ័រនៅឡើយទេ</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              មិនទាន់មានសេណារីយ៉ូអនុវត្តដែលត្រូវនឹងលក្ខខណ្ឌចម្រោះនេះទេ។ សូមជ្រើសរើសជំនាញផ្សេង ឬរង់ចាំគ្រូបង្រៀនបង្កើតសេណារីយ៉ូថ្មី។
            </p>
          </div>
          <button
            @click="clearFilters"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors"
          >
            សម្អាតចម្រោះទាំងអស់
          </button>
        </div>

      </div>
    </div>
  </StudentLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface SimulationItem {
  id: number
  title: string
  title_kh: string | null
  skill: 'it' | 'en' | 'sw' | 'tm' | 'ag'
  kind: string
  instructions: string | null
  estimated_minutes: number
  is_graded: boolean
  pass_score: number
  max_attempts: number
  is_enrolled: boolean
  course: { id: number; title: string; code: string; thumbnail?: string } | null
  lesson: { id: number; title: string } | null
  status: 'not_started' | 'in_progress' | 'passed' | 'needs_practice'
  best_score: number | null
  attempts_count: number
  can_attempt: boolean
  in_progress_attempt_id?: number
  latest_attempt_id?: number
}

const props = defineProps<{
  simulations: SimulationItem[]
  counts: {
    total: number
    completed: number
    passed: number
    in_progress: number
  }
  filters: {
    skill?: string
    course_id?: string
    status?: string
  }
}>()

const currentSkill = ref(props.filters?.skill || '')
const currentStatus = ref(props.filters?.status || '')
const isSubmitting = ref(false)

const skillOptions = [
  { label: 'ទាំងអស់', value: '' },
  { label: '💻 IT (កូដ & ប្រព័ន្ធ)', value: 'it' },
  { label: '🗣️ English (សន្ទនា)', value: 'en' },
  { label: '🤝 Social Work (ករណីសិក្សា)', value: 'sw' },
  { label: '🏨 Tourism (បម្រើភ្ញៀវ)', value: 'tm' },
  { label: '🌾 Agriculture (កសិកម្ម)', value: 'ag' },
]

const statusOptions = [
  { label: 'ទាំងអស់', value: '' },
  { label: 'មិនទាន់ចាប់ផ្តើម', value: 'not_started' },
  { label: 'កំពុងអនុវត្ត', value: 'in_progress' },
  { label: 'បានជាប់ (Passed)', value: 'passed' },
  { label: 'ត្រូវការហាត់បន្ថែម', value: 'needs_practice' },
]

const applyFilter = (key: 'skill' | 'status', value: string) => {
  if (key === 'skill') currentSkill.value = value
  if (key === 'status') currentStatus.value = value

  router.get('/student/simulations', {
    skill: currentSkill.value || undefined,
    status: currentStatus.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  currentSkill.value = ''
  currentStatus.value = ''
  router.get('/student/simulations')
}

const startOrResume = (sim: SimulationItem) => {
  isSubmitting.value = true
  router.post(`/student/simulations/${sim.id}/start`, {}, {
    onFinish: () => {
      isSubmitting.value = false
    }
  })
}

const getSkillName = (skill: string) => {
  const map: Record<string, string> = {
    it: 'ព័ត៌មានវិទ្យា',
    en: 'ភាសាអង់គ្លេស',
    sw: 'ការងារសង្គម',
    tm: 'ទេសចរណ៍',
    ag: 'កសិកម្ម',
  }
  return map[skill] || skill
}

const getSkillIcon = (skill: string) => {
  const map: Record<string, string> = {
    it: '💻',
    en: '🗣️',
    sw: '🤝',
    tm: '🏨',
    ag: '🌾',
  }
  return map[skill] || '🎯'
}

const getSkillBadgeClass = (skill: string) => {
  const map: Record<string, string> = {
    it: 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800/40',
    en: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40',
    sw: 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 border border-rose-200 dark:border-rose-800/40',
    tm: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40',
    ag: 'bg-lime-50 text-lime-700 dark:bg-lime-900/30 dark:text-lime-300 border border-lime-200 dark:border-lime-800/40',
  }
  return map[skill] || 'bg-purple-50 text-purple-700'
}
</script>
