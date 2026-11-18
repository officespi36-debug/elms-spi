<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import Announcements from './Announcements.vue'
import AiAlerts from './AiAlerts.vue'
import TeacherFeedback from './TeacherFeedback.vue'
import PaymentAlerts from './PaymentAlerts.vue'

const props = defineProps<{
  activeTab?: string
}>()

const currentTab = ref(props.activeTab || 'all')
const markAllSuccess = ref(false)

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search)
  const tabParam = urlParams.get('tab')
  if (tabParam && ['all', 'announcements', 'ai-alerts', 'feedback', 'payments'].includes(tabParam)) {
    currentTab.value = tabParam
  }
})

watch(() => props.activeTab, (newTab) => {
  if (newTab) currentTab.value = newTab
})

const markAllRead = () => {
  markAllSuccess.value = true
  setTimeout(() => markAllSuccess.value = false, 2500)
}
</script>

<template>
  <div class="space-y-6">
    
    <!-- NOTIFICATION CENTER LAYOUT TOP HEADER (Matching Prompt Spec) -->
    <div class="bg-gradient-to-r from-blue-950 via-slate-900 to-indigo-950 border border-blue-900/60 rounded-3xl p-6 shadow-2xl space-y-4">
      
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
          <span class="px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30 text-xs font-bold uppercase tracking-wider">
            🔔 STUDENT NOTIFICATION CENTER
          </span>
          <h1 class="text-xl md:text-2xl font-black text-white mt-1.5">
            🔔 MY NOTIFICATIONS
          </h1>
        </div>

        <div class="flex items-center gap-2">
          <button @click="markAllRead" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md">
            ✓ Mark all as read
          </button>
          <button class="px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 font-bold text-xs">
            ⚙️ Settings
          </button>
        </div>
      </div>

      <div v-if="markAllSuccess" class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-xs text-emerald-300 font-bold">
        ✅ All notifications marked as read!
      </div>

      <!-- Controls Row: Filter, Search, Sort -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
        <select class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
          <option>Filter: All</option>
          <option>Unread</option>
          <option>Read</option>
        </select>

        <input type="text" placeholder="Search title/keyword..." class="bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500" />

        <select class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
          <option>Sort: Newest</option>
          <option>Priority</option>
        </select>
      </div>

      <!-- TABS WITH BADGE COUNTERS (Matching Prompt Spec) -->
      <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-800 text-xs">
        <button
          @click="currentTab = 'all'"
          :class="[currentTab === 'all' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-900 text-slate-300 border border-slate-700', 'px-4 py-2 rounded-xl text-xs transition-all']"
        >
          All (10)
        </button>
        <button
          @click="currentTab = 'announcements'"
          :class="[currentTab === 'announcements' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-900 text-slate-300 border border-slate-700', 'px-4 py-2 rounded-xl text-xs transition-all']"
        >
          📢 Announcements (3)
        </button>
        <button
          @click="currentTab = 'ai-alerts'"
          :class="[currentTab === 'ai-alerts' ? 'bg-purple-600 text-white font-bold' : 'bg-slate-900 text-slate-300 border border-slate-700', 'px-4 py-2 rounded-xl text-xs transition-all inline-flex items-center gap-1.5']"
        >
          <img :src="'/images/ai-robot-avatar.png'" alt="" class="w-4 h-4 rounded-sm object-contain shrink-0 select-none" />
          <span>AI Alerts (2)</span>
        </button>
        <button
          @click="currentTab = 'feedback'"
          :class="[currentTab === 'feedback' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-900 text-slate-300 border border-slate-700', 'px-4 py-2 rounded-xl text-xs transition-all']"
        >
          💬 Teacher Feedback (4)
        </button>
        <button
          @click="currentTab = 'payments'"
          :class="[currentTab === 'payments' ? 'bg-amber-600 text-white font-bold' : 'bg-slate-900 text-slate-300 border border-slate-700', 'px-4 py-2 rounded-xl text-xs transition-all']"
        >
          💳 Payment Alerts (1)
        </button>
      </div>

    </div>

    <!-- TAB CONTENT RENDER -->
    <div>
      <template v-if="currentTab === 'all'">
        <div class="space-y-6">
          <Announcements />
          <AiAlerts />
          <TeacherFeedback />
          <PaymentAlerts />
        </div>
      </template>
      <Announcements v-else-if="currentTab === 'announcements'" />
      <AiAlerts v-else-if="currentTab === 'ai-alerts'" />
      <TeacherFeedback v-else-if="currentTab === 'feedback'" />
      <PaymentAlerts v-else-if="currentTab === 'payments'" />
      <Announcements v-else />
    </div>

  </div>
</template>
