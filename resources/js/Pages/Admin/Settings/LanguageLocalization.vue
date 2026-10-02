<script setup lang="ts">
import { useToast } from 'primevue/usetoast'

const props = defineProps<{
  form: any
  saveSettings: () => void
}>()

const emit = defineEmits(['openLangPreview'])
const toast = useToast()
</script>

<template>
  <div class="bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl p-6 space-y-6 shadow-xl">
    <div class="flex items-center justify-between border-b border-slate-700/60 pb-4">
      <div>
        <h2 class="text-lg font-bold text-white flex items-center gap-2">🌐 Language & Localization</h2>
        <p class="text-xs text-slate-400">Configure multi-language interface options (Khmer & English), currency display, and number formats.</p>
      </div>
      <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">i18n Ready</span>
    </div>

    <div class="space-y-4">
      <h3 class="text-sm font-bold text-slate-200">Default Platform Language</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <label class="p-3 bg-slate-900 border rounded-xl flex items-center gap-3 cursor-pointer" :class="form.default_language === 'kh' ? 'border-blue-500 text-white' : 'border-slate-700 text-slate-400'">
          <input type="radio" v-model="form.default_language" value="kh" class="text-blue-600 focus:ring-blue-500" />
          <span class="text-xs font-semibold">🇰🇭 Khmer (ខ្មែរ)</span>
        </label>

        <label class="p-3 bg-slate-900 border rounded-xl flex items-center gap-3 cursor-pointer" :class="form.default_language === 'en' ? 'border-blue-500 text-white' : 'border-slate-700 text-slate-400'">
          <input type="radio" v-model="form.default_language" value="en" class="text-blue-600 focus:ring-blue-500" />
          <span class="text-xs font-semibold">🇬🇧 English</span>
        </label>

        <label class="p-3 bg-slate-900 border rounded-xl flex items-center gap-3 cursor-pointer" :class="form.default_language === 'ask' ? 'border-blue-500 text-white' : 'border-slate-700 text-slate-400'">
          <input type="radio" v-model="form.default_language" value="ask" class="text-blue-600 focus:ring-blue-500" />
          <span class="text-xs font-semibold">Ask user on first login</span>
        </label>
      </div>
    </div>

    <div class="border-t border-slate-700/60 pt-6 space-y-4">
      <h3 class="text-sm font-bold text-slate-200">📅 Regional Formatting</h3>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Number Format</label>
          <select v-model="form.number_format" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5">
            <option value="1,234.56">1,234.56 (Standard comma)</option>
            <option value="1.234,56">1.234,56 (European dot)</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">First Day of Week</label>
          <select v-model="form.first_day_of_week" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5">
            <option value="Monday">Monday</option>
            <option value="Sunday">Sunday</option>
          </select>
        </div>
      </div>

      <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-700/60 flex items-center justify-between">
        <div>
          <div class="text-xs font-semibold text-slate-200">Khmer Numerals</div>
          <div class="text-[11px] text-slate-400">Display numbers as Khmer digits (១, ២, ៣) across UI</div>
        </div>
        <input type="checkbox" v-model="form.khmer_numerals" class="w-4 h-4 rounded text-blue-600 bg-slate-900 border-slate-700" />
      </div>
    </div>

    <!-- Currency & Numerical Formatting -->
    <div class="border-t border-slate-700/60 pt-6 space-y-4">
      <h3 class="text-sm font-bold text-slate-200">💱 Currency & Numerical Formatting</h3>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Exchange Rate Reference (USD to KHR)</label>
          <div class="flex items-center gap-2">
            <input v-model="form.exchange_rate_usd_khr" type="text" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
            <button @click="toast.add({ severity: 'info', summary: 'Updated', detail: 'Rate set to 1 USD = 4,100 KHR', life: 2500 })" type="button" class="px-3 py-2 text-xs bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-xl whitespace-nowrap">🔄 Update</button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Decimal Precision</label>
          <select v-model="form.decimal_precision" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5">
            <option value="2">2 Decimals ($12.50)</option>
            <option value="0">0 Decimals ($13)</option>
          </select>
        </div>
      </div>

      <label class="flex items-center gap-3 cursor-pointer pt-1">
        <input v-model="form.show_khr_equivalent" type="checkbox" class="w-4 h-4 rounded text-blue-600 bg-slate-900 border-slate-700" />
        <span class="text-xs text-slate-300">Show Khmer Riel numerical equivalent alongside USD (e.g., $10 ≈ 41,000 ៛)</span>
      </label>
    </div>

    <div class="flex items-center justify-between border-t border-slate-700/60 pt-4">
      <div class="flex items-center gap-2">
        <button @click="emit('openLangPreview', 'kh')" type="button" class="px-3 py-2 text-xs bg-slate-700 text-slate-200 hover:bg-slate-600 rounded-xl">👁 Preview KH</button>
        <button @click="emit('openLangPreview', 'en')" type="button" class="px-3 py-2 text-xs bg-slate-700 text-slate-200 hover:bg-slate-600 rounded-xl">👁 Preview EN</button>
      </div>
      <button @click="saveSettings" type="button" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-lg">💾 Save Localization</button>
    </div>
  </div>
</template>
