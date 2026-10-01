<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import type { AiRuleItem } from './types'

const props = defineProps<{
  rules: AiRuleItem[]
}>()

const emit = defineEmits<{
  (e: 'createRule'): void
  (e: 'editRule', rule: AiRuleItem): void
  (e: 'toggleStatus', rule: AiRuleItem): void
  (e: 'testRule', rule: AiRuleItem): void
  (e: 'duplicateRule', rule: AiRuleItem): void
  (e: 'deleteRule', rule: AiRuleItem): void
  (e: 'openSimulator'): void
  (e: 'exportRules'): void
}>()

const searchQuery = ref('')
const selectedCategory = ref('all')
const selectedStatus = ref('all')
const activeDropdownId = ref<number | null>(null)

const filteredRules = computed(() => {
  return props.rules.filter(r => {
    const matchSearch = searchQuery.value === '' || 
      r.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
      r.condition_text.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
      r.action_text.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchCategory = selectedCategory.value === 'all' || r.category === selectedCategory.value
    const matchStatus = selectedStatus.value === 'all' || r.status === selectedStatus.value
    return matchSearch && matchCategory && matchStatus
  })
})

const totalCount = computed(() => props.rules.length)
const activeCount = computed(() => props.rules.filter(r => r.status === 'active').length)
const disabledCount = computed(() => props.rules.filter(r => r.status === 'disabled').length)
const totalFired = computed(() => props.rules.reduce((acc, r) => acc + r.fired_count, 0))

function toggleDropdown(id: number) {
  activeDropdownId.value = activeDropdownId.value === id ? null : id
}

function closeDropdownOnOutsideClick(e: MouseEvent) {
  const target = e.target as HTMLElement
  if (!target.closest('.dropdown-container')) {
    activeDropdownId.value = null
  }
}

onMounted(() => {
  window.addEventListener('click', closeDropdownOnOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('click', closeDropdownOnOutsideClick)
})
</script>

<template>
  <div class="space-y-6 text-xs font-sans">
    <!-- Header Banner -->
    <div class="bg-[#0d1222]/95 border border-purple-500/30 rounded-2xl p-5 shadow-2xl space-y-4">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-700/60 pb-4">
        <div>
          <h3 class="text-base font-black text-white flex items-center gap-2.5">
            <!-- Flaticon Multi-color Gear & Sparkles Icon -->
            <div class="p-2 rounded-xl bg-purple-500/15 border border-purple-500/30 text-purple-300 shrink-0">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" fill="#C084FC" opacity="0.4" stroke="#E9D5FF" stroke-width="1.5" />
                <path d="M19.4 15A1.65 1.65 0 0 0 19.73 16.82L19.79 16.88A2 2 0 1 1 16.96 19.71L16.9 19.65A1.65 1.65 0 0 0 15.08 19.32A1.65 1.65 0 0 0 14 20.83V20.91A2 2 0 1 1 10 20.91V20.83A1.65 1.65 0 0 0 8.92 19.32A1.65 1.65 0 0 0 7.1 19.65L7.04 19.71A2 2 0 1 1 4.21 16.88L4.27 16.82A1.65 1.65 0 0 0 4.6 15A1.65 1.65 0 0 0 3.09 13.92H3.01A2 2 0 1 1 3.01 9.92H3.09A1.65 1.65 0 0 0 4.6 8.84A1.65 1.65 0 0 0 4.27 7.02L4.21 6.96A2 2 0 1 1 7.04 4.13L7.1 4.19A1.65 1.65 0 0 0 8.92 4.52H9A1.65 1.65 0 0 0 10 3.01V2.93A2 2 0 1 1 14 2.93V3.01A1.65 1.65 0 0 0 15.08 4.52A1.65 1.65 0 0 0 16.9 4.19L16.96 4.13A2 2 0 1 1 19.79 6.96L19.73 7.02A1.65 1.65 0 0 0 19.4 8.84A1.65 1.65 0 0 0 20.91 9.92H20.99A2 2 0 1 1 20.99 13.92H20.91A1.65 1.65 0 0 0 19.4 15Z" stroke="#C084FC" stroke-width="1.5" />
              </svg>
            </div>
            <span>AI RULES — CONDITION → ACTION ENGINE</span>
          </h3>
          <p class="text-slate-400 text-xs mt-1">Configure automated recommendation logic — If This Happens → Then Do That</p>
        </div>

        <!-- Flaticon Dual-tone Buttons -->
        <div class="flex flex-wrap items-center gap-2.5">
          <button
            @click="emit('createRule')"
            class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl font-bold shadow-lg shadow-purple-600/30 flex items-center gap-2 transition-all active:scale-95 ring-1 ring-purple-400/50"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
              <path d="M12 4V20M4 12H20" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" />
            </svg>
            <span>Create Rule</span>
          </button>

          <button
            @click="emit('openSimulator')"
            class="px-4 py-2 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white rounded-xl font-bold shadow-lg shadow-teal-600/30 flex items-center gap-2 transition-all active:scale-95 ring-1 ring-teal-400/50"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
              <path d="M9 3V8L4 16C3.2 17.3 4.1 19 5.7 19H18.3C19.9 19 20.8 17.3 20 16L15 8V3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M9 3H15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              <circle cx="12" cy="15" r="1.5" fill="#A7F3D0" />
            </svg>
            <span>Rule Simulator</span>
          </button>

          <button
            @click="emit('exportRules')"
            class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-xl font-semibold flex items-center gap-2 transition-all"
          >
            <svg class="w-4 h-4 text-slate-300" viewBox="0 0 24 24" fill="none">
              <path d="M4 16V17C4 18.6569 5.34315 20 7 20H17C18.6569 20 20 18.6569 20 17V16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              <path d="M12 4V14M12 14L8 10M12 14L16 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span>Export Rules</span>
          </button>
        </div>
      </div>

      <!-- 4 KPI Cards with Flaticon Vector Icons -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Total Rules -->
        <div class="bg-[#121827] p-4 rounded-xl border border-slate-700/80 space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="text-slate-400 text-[10px] block font-semibold uppercase tracking-wider">TOTAL RULES</span>
            <div class="p-1 rounded-md bg-purple-500/15 text-purple-300">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                <rect x="3" y="3" width="7" height="7" rx="1.5" fill="#C084FC" opacity="0.6" stroke="#C084FC" stroke-width="1.2" />
                <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="#C084FC" stroke-width="1.2" />
                <rect x="14" y="14" width="7" height="7" rx="1.5" fill="#C084FC" opacity="0.6" stroke="#C084FC" stroke-width="1.2" />
                <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="#C084FC" stroke-width="1.2" />
              </svg>
            </div>
          </div>
          <p class="text-2xl font-black text-white">{{ totalCount }}</p>
          <span class="text-[10px] text-purple-300 font-bold block">Rule Engine Active</span>
        </div>

        <!-- KPI 2: Active Rules -->
        <div class="bg-[#121827] p-4 rounded-xl border border-slate-700/80 space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="text-slate-400 text-[10px] block font-semibold uppercase tracking-wider">ACTIVE RULES</span>
            <div class="p-1 rounded-md bg-emerald-500/15 text-emerald-300">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="#10B981" fill-opacity="0.3" stroke="#34D399" stroke-width="1.5" />
                <path d="M8 12L11 15L16 9" stroke="#34D399" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
          </div>
          <p class="text-2xl font-black text-emerald-400">{{ activeCount }}</p>
          <span class="text-[10px] text-emerald-300 font-semibold block">Evaluating live</span>
        </div>

        <!-- KPI 3: Disabled Rules -->
        <div class="bg-[#121827] p-4 rounded-xl border border-slate-700/80 space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="text-slate-400 text-[10px] block font-semibold uppercase tracking-wider">DISABLED RULES</span>
            <div class="p-1 rounded-md bg-slate-800 text-slate-400">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="9" stroke="#94A3B8" stroke-width="1.5" />
                <path d="M10 9V15M14 9V15" stroke="#94A3B8" stroke-width="1.8" stroke-linecap="round" />
              </svg>
            </div>
          </div>
          <p class="text-2xl font-black text-slate-400">{{ disabledCount }}</p>
          <span class="text-[10px] text-slate-400 font-semibold block">Paused for review</span>
        </div>

        <!-- KPI 4: Fired Today -->
        <div class="bg-[#121827] p-4 rounded-xl border border-slate-700/80 space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="text-slate-400 text-[10px] block font-semibold uppercase tracking-wider">TOTAL RECOMMENDATIONS</span>
            <div class="p-1 rounded-md bg-cyan-500/15 text-cyan-300">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                <path d="M12 2C12 2 6 8.5 6 14C6 17.3137 8.68629 20 12 20C15.3137 20 18 17.3137 18 14C18 8.5 12 2 12 2Z" fill="#06B6D4" fill-opacity="0.3" stroke="#22D3EE" stroke-width="1.5" />
                <path d="M12 11C12 11 9.5 14 9.5 16.5C9.5 17.8807 10.6193 19 12 19C13.3807 19 14.5 17.8807 14.5 16.5C14.5 14 12 11 12 11Z" fill="#22D3EE" />
              </svg>
            </div>
          </div>
          <p class="text-2xl font-black text-cyan-300">520</p>
          <span class="text-[10px] text-cyan-400 font-semibold block">395 Completed (76%) · 125 Active</span>
        </div>
      </div>

      <!-- ── ADMIN MONITORING: RECOMMENDATIONS BY 5 MAJORS & WEAK TOPICS (Spec 5) ── -->
      <div class="p-4 rounded-xl bg-[#090d16] border border-slate-800 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-2.5">
          <span class="text-[11px] font-bold text-purple-300 uppercase tracking-wider flex items-center gap-2">
            <span>📊</span>
            <span>ADMIN MONITORING — RECOMMENDATIONS ACCORDING TO 5 SPI MAJORS</span>
          </span>
          <span class="text-[10px] text-slate-400 italic">
            Admin មិនកែ Recommendation ដោយដៃទេ Admin មានតួនាទី Monitor & Configure
          </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 text-center">
          <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
            <span class="text-[10px] text-slate-400 block font-semibold">Information Tech</span>
            <span class="text-base font-black text-white">140 Recs</span>
            <span class="text-[9px] text-purple-400 block truncate">Frequent: Loops in C</span>
          </div>

          <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
            <span class="text-[10px] text-slate-400 block font-semibold">Agriculture</span>
            <span class="text-base font-black text-teal-300">110 Recs</span>
            <span class="text-[9px] text-teal-400 block truncate">Frequent: Soil Management</span>
          </div>

          <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
            <span class="text-[10px] text-slate-400 block font-semibold">English Literature</span>
            <span class="text-base font-black text-sky-300">105 Recs</span>
            <span class="text-[9px] text-sky-400 block truncate">Frequent: Complex Clauses</span>
          </div>

          <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
            <span class="text-[10px] text-slate-400 block font-semibold">Tourism Management</span>
            <span class="text-base font-black text-amber-300">95 Recs</span>
            <span class="text-[9px] text-amber-400 block truncate">Frequent: Yield Pricing</span>
          </div>

          <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800 col-span-2 sm:col-span-1">
            <span class="text-[10px] text-slate-400 block font-semibold">Social Work</span>
            <span class="text-base font-black text-emerald-300">70 Recs</span>
            <span class="text-[9px] text-emerald-400 block truncate">Frequent: Child Welfare Ethics</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── CONSOLIDATED LEFT-ALIGNED SEARCH & FILTERS TOOLBAR ── -->
    <div class="bg-[#0d1222]/90 border border-slate-700/60 p-3 rounded-2xl flex flex-wrap items-center justify-start gap-3">
      <!-- Search Input with Flaticon Glass Search Icon -->
      <div class="relative w-full sm:w-72">
        <div class="absolute left-3 top-2.5 text-cyan-400">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
            <circle cx="11" cy="11" r="7" stroke="#38BDF8" stroke-width="1.8" />
            <path d="M20 20L16 16" stroke="#38BDF8" stroke-width="2" stroke-linecap="round" />
            <circle cx="9" cy="9" r="2" fill="#38BDF8" fill-opacity="0.5" />
          </svg>
        </div>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search rule, condition, or action..."
          class="w-full bg-[#121827] text-slate-200 border border-slate-700/80 rounded-xl pl-9 pr-3 py-2 text-xs focus:border-purple-500 focus:outline-none transition-all placeholder:text-slate-500 font-medium"
        />
      </div>

      <!-- Category Filter Dropdown -->
      <div class="relative">
        <select
          v-model="selectedCategory"
          class="bg-[#121827] text-slate-200 border border-slate-700/80 rounded-xl px-3 py-2 text-xs focus:border-purple-500 focus:outline-none transition-all cursor-pointer font-medium"
        >
          <option value="all">Category: All</option>
          <option value="Performance">Performance</option>
          <option value="Engagement">Engagement</option>
          <option value="Completion">Completion</option>
          <option value="Behavior">Behavior</option>
        </select>
      </div>

      <!-- Status Filter Dropdown -->
      <div class="relative">
        <select
          v-model="selectedStatus"
          class="bg-[#121827] text-slate-200 border border-slate-700/80 rounded-xl px-3 py-2 text-xs focus:border-purple-500 focus:outline-none transition-all cursor-pointer font-medium"
        >
          <option value="all">Status: All</option>
          <option value="active">🟢 Active On</option>
          <option value="disabled">⏸️ Disabled Off</option>
        </select>
      </div>

      <!-- Clear Filters Button -->
      <button
        v-if="searchQuery !== '' || selectedCategory !== 'all' || selectedStatus !== 'all'"
        @click="searchQuery = ''; selectedCategory = 'all'; selectedStatus = 'all'"
        class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 rounded-lg text-xs transition-all flex items-center gap-1 font-medium border border-slate-700/50"
      >
        <span>✕ Reset Filters</span>
      </button>
    </div>

    <!-- AI Rules Table -->
    <div class="overflow-x-auto rounded-2xl border border-slate-700/60 bg-[#0d1222]/95 shadow-xl">
      <table class="w-full text-left text-xs text-slate-300">
        <thead class="bg-[#121827] text-slate-400 uppercase font-bold border-b border-slate-700/80">
          <tr>
            <th class="p-3.5 w-12">#</th>
            <th class="p-3.5">IF Condition (Trigger)</th>
            <th class="p-3.5">THEN Action (Response)</th>
            <th class="p-3.5 w-20">Priority</th>
            <th class="p-3.5 w-28">Status</th>
            <th class="p-3.5 w-24">Fired</th>
            <th class="p-3.5 text-right w-36">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/60 font-medium">
          <tr
            v-for="(r, idx) in filteredRules"
            :key="r.id"
            class="hover:bg-slate-800/50 transition-colors"
            :class="r.status === 'disabled' ? 'bg-slate-900/40 text-slate-400' : ''"
          >
            <td class="p-3.5 font-mono font-bold text-slate-400">{{ String(idx + 1).padStart(2, '0') }}</td>
            <td class="p-3.5">
              <div class="font-bold text-white text-sm flex items-center gap-2">
                <span>{{ r.name }}</span>
                <!-- Subtle Neutral Category Tag with Mini Flaticon Icons -->
                <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 border border-slate-700/80 text-[10px] font-medium tracking-wide flex items-center gap-1">
                  <!-- Performance Star Icon -->
                  <svg v-if="r.category === 'Performance'" class="w-3 h-3 text-amber-400" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#F59E0B" fill-opacity="0.8" />
                  </svg>
                  <!-- Completion Checkmark Icon -->
                  <svg v-else-if="r.category === 'Completion'" class="w-3 h-3 text-emerald-400" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="#34D399" stroke-width="1.5" fill="#10B981" fill-opacity="0.3" />
                    <path d="M8 12L11 15L16 9" stroke="#34D399" stroke-width="1.8" stroke-linecap="round" />
                  </svg>
                  <!-- Engagement Flame Icon -->
                  <svg v-else-if="r.category === 'Engagement'" class="w-3 h-3 text-rose-400" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2C12 2 6 8.5 6 14C6 17.3 8.7 20 12 20C15.3 20 18 17.3 18 14C18 8.5 12 2 12 2Z" fill="#F43F5E" fill-opacity="0.8" />
                  </svg>
                  <!-- Behavior User Icon -->
                  <svg v-else class="w-3 h-3 text-sky-400" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="8" r="4" fill="#0284C7" opacity="0.8" />
                    <path d="M4 20C4 16 8 16 12 16C16 16 20 16 20 20" stroke="#38BDF8" stroke-width="1.8" stroke-linecap="round" />
                  </svg>
                  <span>{{ r.category }}</span>
                </span>
              </div>
              <div class="text-[11px] text-teal-300/90 font-mono mt-1">IF {{ r.condition_text }}</div>
            </td>
            <td class="p-3.5">
              <div class="text-slate-200 font-semibold">THEN {{ r.action_text }}</div>
              <div v-if="r.message_template" class="text-[10px] text-slate-400 italic mt-0.5">"{{ r.message_template }}"</div>
            </td>
            <td class="p-3.5 font-mono font-bold">
              <span
                class="px-2 py-0.5 rounded text-[10px]"
                :class="
                  r.priority === 'P1' ? 'bg-red-500/20 text-red-300 border border-red-500/40' : 
                  (r.priority === 'P2' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-slate-800 text-slate-300 border border-slate-700')
                "
              >
                {{ r.priority }}
              </span>
            </td>
            <td class="p-3.5">
              <!-- High-contrast Status Button -->
              <button
                @click="emit('toggleStatus', r)"
                title="Click to toggle status"
                class="px-2.5 py-1 rounded-full text-[10px] font-bold border transition-all inline-flex items-center gap-1.5 shadow-sm"
                :class="
                  r.status === 'active'
                    ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/40 hover:bg-emerald-500/25'
                    : 'bg-slate-800/80 text-slate-400 border-slate-700 hover:bg-slate-800 hover:text-slate-200'
                "
              >
                <span
                  class="w-1.5 h-1.5 rounded-full"
                  :class="r.status === 'active' ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"
                ></span>
                <span>{{ r.status === 'active' ? 'Active On' : 'Disabled Off' }}</span>
              </button>
            </td>
            <td class="p-3.5 font-mono font-bold text-cyan-300">
              {{ r.fired_count }}
            </td>
            <!-- Streamlined Table Actions with Vibrant Flaticon Vector Icons -->
            <td class="p-3.5 text-right relative">
              <div class="flex items-center justify-end gap-1.5">
                <!-- Flaticon Dual-tone Edit Pencil Button -->
                <button
                  @click="emit('editRule', r)"
                  title="Edit Rule"
                  class="p-1.5 bg-blue-950/60 hover:bg-blue-900/80 text-blue-400 hover:text-blue-300 rounded-lg transition-all border border-blue-500/30 shadow-sm"
                >
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                    <path d="M11 4H4C3.44772 4 3 4.44772 3 5V20C3 20.5523 3.44772 21 4 21H19C19.5523 21 20 20.5523 20 20V13" stroke="#38BDF8" stroke-width="1.8" stroke-linecap="round" />
                    <path d="M18.5 2.5C19.33 1.67 20.67 1.67 21.5 2.5C22.33 3.33 22.33 4.67 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" fill="#0284C7" stroke="#38BDF8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </button>

                <!-- Flaticon Dual-tone Trash Can Delete Button -->
                <button
                  @click="emit('deleteRule', r)"
                  title="Delete Rule"
                  class="p-1.5 bg-rose-950/60 hover:bg-rose-900/80 text-rose-400 hover:text-rose-300 rounded-lg transition-all border border-rose-500/30 shadow-sm"
                >
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                    <path d="M19 7L18.133 19.142C18.0516 20.2813 17.1028 21.1429 15.9611 21.1429H8.03893C6.89722 21.1429 5.94843 20.2813 5.86701 19.142L5 7" stroke="#F87171" stroke-width="1.8" stroke-linecap="round" />
                    <path d="M10 11V17M14 11V17" stroke="#FCA5A5" stroke-width="1.8" stroke-linecap="round" />
                    <path d="M9 7V4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V7M4 7H20" stroke="#F87171" stroke-width="1.8" stroke-linecap="round" />
                  </svg>
                </button>

                <!-- Flaticon Dual-tone 3 Dots Dropdown Menu -->
                <div class="relative inline-block text-left dropdown-container">
                  <button
                    @click.stop="toggleDropdown(r.id)"
                    title="More Actions"
                    class="p-1.5 bg-purple-950/60 hover:bg-purple-900/80 text-purple-300 hover:text-white rounded-lg transition-all border border-purple-500/30 shadow-sm"
                  >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none">
                      <circle cx="12" cy="5" r="2" fill="#C084FC" />
                      <circle cx="12" cy="12" r="2" fill="#C084FC" />
                      <circle cx="12" cy="19" r="2" fill="#C084FC" />
                    </svg>
                  </button>

                  <!-- Popover Menu -->
                  <div
                    v-if="activeDropdownId === r.id"
                    class="absolute right-0 mt-1 w-44 rounded-xl bg-slate-900 border border-slate-700 shadow-2xl z-50 py-1 text-left text-xs divide-y divide-slate-800"
                  >
                    <!-- Flaticon Simulator Flask -->
                    <button
                      @click="emit('testRule', r); activeDropdownId = null"
                      class="w-full px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-teal-300 flex items-center gap-2 font-medium"
                    >
                      <svg class="w-3.5 h-3.5 text-teal-400" viewBox="0 0 24 24" fill="none">
                        <path d="M9 3V8L4 16C3.2 17.3 4.1 19 5.7 19H18.3C19.9 19 20.8 17.3 20 16L15 8V3" stroke="#2DD4BF" stroke-width="1.8" stroke-linecap="round" />
                      </svg>
                      <span>Test in Simulator</span>
                    </button>

                    <!-- Flaticon Duplicate Layers -->
                    <button
                      @click="emit('duplicateRule', r); activeDropdownId = null"
                      class="w-full px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-purple-300 flex items-center gap-2 font-medium"
                    >
                      <svg class="w-3.5 h-3.5 text-purple-400" viewBox="0 0 24 24" fill="none">
                        <rect x="8" y="8" width="12" height="12" rx="2" stroke="#C084FC" stroke-width="1.8" />
                        <path d="M16 4H6C4.89543 4 4 4.89543 4 6V16" stroke="#A855F7" stroke-width="1.8" stroke-linecap="round" />
                      </svg>
                      <span>Duplicate Rule</span>
                    </button>

                    <!-- Flaticon Toggle Power Switch -->
                    <button
                      @click="emit('toggleStatus', r); activeDropdownId = null"
                      class="w-full px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-amber-300 flex items-center gap-2 font-medium"
                    >
                      <svg class="w-3.5 h-3.5 text-amber-400" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="#F59E0B" stroke-width="1.8" />
                        <path d="M12 7V12" stroke="#FBBF24" stroke-width="2" stroke-linecap="round" />
                      </svg>
                      <span>{{ r.status === 'active' ? 'Disable Rule' : 'Enable Rule' }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
