<script setup lang="ts">
import { useToast } from 'primevue/usetoast'

const props = defineProps<{
  form: any
  saveSettings: () => void
}>()

const emit = defineEmits(['openQueueJobs'])
const toast = useToast()
</script>

<template>
  <div class="bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl p-6 space-y-6 shadow-xl">
    <div class="flex items-center justify-between border-b border-slate-700/60 pb-4">
      <div>
        <h2 class="text-lg font-bold text-white flex items-center gap-2">⚡ Redis / Queue Worker Settings</h2>
        <p class="text-xs text-slate-400">Monitor background queue workers for Email delivery, Certificate PDF generation, and AI inference diagnostics.</p>
      </div>
      <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">🟢 Redis Connected</span>
    </div>

    <!-- Queue Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="p-3.5 bg-slate-900/80 rounded-xl border border-slate-700/60 text-center">
        <div class="text-xs text-slate-400">Waiting</div>
        <div class="text-xl font-bold text-amber-400 mt-1">12</div>
      </div>
      <div class="p-3.5 bg-slate-900/80 rounded-xl border border-slate-700/60 text-center">
        <div class="text-xs text-slate-400">Processing</div>
        <div class="text-xl font-bold text-blue-400 mt-1">4</div>
      </div>
      <div class="p-3.5 bg-slate-900/80 rounded-xl border border-slate-700/60 text-center">
        <div class="text-xs text-slate-400">Failed</div>
        <div class="text-xl font-bold text-rose-400 mt-1">2</div>
      </div>
      <div class="p-3.5 bg-slate-900/80 rounded-xl border border-slate-700/60 text-center">
        <div class="text-xs text-slate-400">Completed Today</div>
        <div class="text-xl font-bold text-emerald-400 mt-1">4,850</div>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs font-medium text-slate-300 mb-1">Queue Driver</label>
        <select v-model="form.queue_driver" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5">
          <option value="redis">Redis</option>
          <option value="database">Database</option>
          <option value="sync">Sync (Local Direct)</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-300 mb-1">Redis Host</label>
        <input v-model="form.redis_host" type="text" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-300 mb-1">Redis Port</label>
        <input v-model="form.redis_port" type="text" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
      </div>
    </div>

    <div class="flex items-center justify-between border-t border-slate-700/60 pt-4">
      <div class="flex items-center gap-2">
        <button @click="emit('openQueueJobs')" type="button" class="px-3 py-2 text-xs bg-slate-700 text-slate-200 hover:bg-slate-600 rounded-xl">👁 View Jobs</button>
        <button @click="toast.add({ severity: 'info', summary: 'Job Dispatched', detail: 'Test job pushed to Redis default queue', life: 2500 })" type="button" class="px-3 py-2 text-xs bg-slate-700 text-slate-200 hover:bg-slate-600 rounded-xl">🧪 Dispatch Test Job</button>
      </div>
      <button @click="saveSettings" type="button" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-lg">💾 Save Queue Settings</button>
    </div>
  </div>
</template>
