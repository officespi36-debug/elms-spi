<script setup lang="ts">
import { computed } from 'vue'
import { BookOpen, Play, FlaskConical, Clock, CheckCircle2, Sparkles, Layers, FileText } from 'lucide-vue-next'

const props = defineProps<{
  title: string
  description?: string | null
  lessonType?: string | null
  durationFormatted?: string | null
  lessonNumber: number
  totalLessons: number
  objectives?: string[] | null
}>()

const emit = defineEmits<{
  (e: 'start-lesson'): void
  (e: 'read-materials'): void
}>()

const typeLabel = computed(() => {
  if (props.lessonType === 'video') return 'Video Lesson'
  if (props.lessonType === 'lab') return 'Practice Lab'
  return 'Study Lesson'
})

const learnPoints = computed(() => {
  if (props.objectives && props.objectives.length > 0) {
    return props.objectives.slice(0, 3)
  }
  return [
    'យល់ដឹងពីគោលគំនិតស្នូល និងមូលដ្ឋានគ្រឹះនៃមេរៀននេះ',
    'អនុវត្តផ្ទាល់ជាមួយឧទាហរណ៍ និងឯកសារណែនាំជាជំហានៗ',
    'ត្រៀមខ្លួនសម្រាប់មេរៀនបន្ទាប់ និងការវាយតម្លៃសមត្ថភាព',
  ]
})
</script>

<template>
  <section
    aria-label="Lesson introduction"
    class="relative rounded-2xl overflow-hidden bg-gradient-to-br from-[#0b1120] via-[#171d42] to-[#281b52] border border-indigo-900/50 shadow-xl shadow-slate-900/15 p-6 sm:p-8 text-white"
  >
    <!-- Faint architectural grid + soft purple ambient glow (no heavy dot pattern) -->
    <div
      class="absolute inset-0 opacity-[0.07] pointer-events-none"
      style="background-image: linear-gradient(to right, rgba(255,255,255,0.25) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.25) 1px, transparent 1px); background-size: 36px 36px;"
    ></div>
    <div class="absolute -top-20 -right-16 w-72 h-72 rounded-full bg-purple-600/25 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-16 w-64 h-64 rounded-full bg-indigo-500/20 blur-3xl pointer-events-none"></div>

    <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
      <!-- Left: Meta pills, title, description, learning objectives, CTAs -->
      <div class="lg:col-span-8 space-y-4">
        <!-- Meta badges row -->
        <div class="flex flex-wrap items-center gap-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-500/20 border border-purple-400/30 text-purple-200 text-xs font-semibold">
            <FlaskConical v-if="lessonType === 'lab'" class="w-3.5 h-3.5" />
            <Play v-else-if="lessonType === 'video'" class="w-3.5 h-3.5" />
            <BookOpen v-else class="w-3.5 h-3.5" />
            <span>{{ typeLabel }}</span>
          </span>

          <span
            v-if="durationFormatted"
            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-slate-200 text-xs font-medium tabular-nums"
          >
            <Clock class="w-3.5 h-3.5 text-indigo-300" />
            <span>{{ durationFormatted }}</span>
          </span>

          <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/10 border border-white/15 text-slate-200 text-xs font-medium tabular-nums">
            Lesson {{ lessonNumber }} of {{ totalLessons || 1 }}
          </span>
        </div>

        <!-- Title & Khmer description -->
        <div class="space-y-2">
          <h2 class="text-xl sm:text-2xl lg:text-[26px] font-extrabold text-white tracking-tight leading-[1.45]">
            {{ title }}
          </h2>
          <p class="text-[16px] sm:text-[17px] text-slate-200/95 leading-[1.75] max-w-2xl">
            {{ description || 'សូមអានខ្លឹមសារមេរៀន និងឯកសារជំនួយខាងក្រោម រួចចុចបញ្ចប់មេរៀននៅរបារខាងក្រោមដើម្បីដោះសោមេរៀនបន្ទាប់។' }}
          </p>
        </div>

        <!-- What you will learn -->
        <div class="pt-1 space-y-1.5">
          <p class="text-xs font-bold uppercase tracking-wider text-indigo-300">What you will learn</p>
          <ul class="space-y-1.5">
            <li
              v-for="(item, idx) in learnPoints"
              :key="idx"
              class="flex items-start gap-2 text-sm text-slate-200 leading-[1.7]"
            >
              <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0 mt-1" />
              <span>{{ item }}</span>
            </li>
          </ul>
        </div>

        <!-- Primary & Secondary CTAs -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
          <button
            type="button"
            @click="emit('start-lesson')"
            class="min-h-[44px] w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#6366f1] text-white text-sm font-bold shadow-lg shadow-purple-600/35 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] flex items-center justify-center gap-2 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#171d42]"
          >
            <Play class="w-4 h-4 fill-current" />
            <span>Start Lesson</span>
          </button>

          <button
            type="button"
            @click="emit('read-materials')"
            class="min-h-[44px] w-full sm:w-auto px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/20 text-white text-sm font-semibold backdrop-blur-sm hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] flex items-center justify-center gap-2 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
          >
            <FileText class="w-4 h-4" />
            <span>Read Materials</span>
          </button>
        </div>
      </div>

      <!-- Right: Decorative glassmorphism icon cluster (desktop) -->
      <div class="hidden lg:flex lg:col-span-4 items-center justify-center" aria-hidden="true">
        <div class="relative w-52 h-48 flex items-center justify-center">
          <div class="w-28 h-28 rounded-3xl bg-white/10 backdrop-blur-xl border border-white/20 shadow-2xl flex items-center justify-center rotate-3">
            <BookOpen class="w-12 h-12 text-purple-200" />
          </div>
          <div class="absolute top-2 right-3 w-12 h-12 rounded-2xl bg-indigo-500/30 backdrop-blur-md border border-indigo-300/30 flex items-center justify-center -rotate-6 shadow-lg">
            <Sparkles class="w-6 h-6 text-indigo-200" />
          </div>
          <div class="absolute bottom-3 left-2 w-12 h-12 rounded-2xl bg-emerald-500/25 backdrop-blur-md border border-emerald-300/30 flex items-center justify-center rotate-6 shadow-lg">
            <Layers class="w-6 h-6 text-emerald-200" />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
