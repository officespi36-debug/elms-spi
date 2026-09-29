<script setup lang="ts">
import { ref, computed } from 'vue'
import { i18n } from '@/Services/i18n'

const currentLang = computed(() => i18n.locale.value)

const isOpen = ref(false)
const messageInput = ref('')
const isSending = ref(false)

const chatMessages = ref<{ role: 'user' | 'assistant'; text: string; textEn?: string; time: string }[]>([
  {
    role: 'assistant',
    text: 'សួស្តី! ខ្ញុំជា **SPI AI Tutor 24/7** ជំនួយការឆ្លើយសំណួរមេរៀន និងពន្យល់លំហាត់គ្រប់ជំនាញ (IT, English, Agriculture, Social Work, Tourism)។ តើអ្នកមានចម្ងល់អ្វីខ្លះនៅថ្ងៃនេះ?',
    textEn: 'Hello! I am **SPI AI Tutor 24/7**, your AI study assistant for questions and assignments across all majors (IT, English, Agriculture, Social Work, Tourism). How can I assist you today?',
    time: 'ឥឡូវនេះ'
  }
])

const quickChips = computed(() => currentLang.value === 'km' ? [
  'ពន្យល់ពី Pointer ក្នុងភាសា C',
  'របៀបប្រើ Passive Voice',
  'វិធីព្យាបាលជំងឺប្លាស់ស្រូវ',
  'ក្រមសីលធម៌សង្គមកិច្ច',
  'វិធីដោះស្រាយបញ្ហាភ្ញៀវ Hotel'
] : [
  'Explain Pointers in C language',
  'How to use Passive Voice',
  'Rice Blast disease prevention',
  'Social Work Code of Ethics',
  'Resolving hotel guest complaints'
])

const toggleWidget = () => {
  isOpen.value = !isOpen.value
}

const sendQuick = (chip: string) => {
  messageInput.value = chip
  sendMessage()
}

const sendMessage = async () => {
  if (!messageInput.value.trim() || isSending.value) return
  const userText = messageInput.value.trim()
  chatMessages.value.push({
    role: 'user',
    text: userText,
    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  })
  messageInput.value = ''
  isSending.value = true

  try {
    const res = await fetch('/api/ai/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        message: userText,
        history: chatMessages.value.slice(-6)
      })
    })
    const data = await res.json()
    chatMessages.value.push({
      role: 'assistant',
      text: data.reply || (currentLang.value === 'km' ? 'ខ្ញុំបានកត់ត្រាសំណួររបស់អ្នក! សូមពិនិត្យមើលខ្លឹមសារមេរៀនបន្ថែម។' : 'I have received your question! Please review the lesson content.'),
      time: data.time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    })
  } catch (e) {
    chatMessages.value.push({
      role: 'assistant',
      text: currentLang.value === 'km'
        ? '🤖 ខ្ញុំជា AI Tutor នៅ Saint Paul Institute។ សំណួររបស់អ្នកបានកត់ត្រាជោគជ័យ! សូមសាកល្បងសាកសួរម្ដងទៀត។'
        : '🤖 I am SPI AI Tutor at Saint Paul Institute. Your request was received! Please try asking again.',
      time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    })
  } finally {
    isSending.value = false
  }
}
</script>

<template>
  <div class="fixed bottom-6 right-6 z-50">
    <!-- FLOATING TOGGLE BUTTON -->
    <button
      v-if="!isOpen"
      @click="toggleWidget"
      type="button"
      class="group relative flex items-center gap-2.5 px-4 py-3 rounded-full bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 text-white font-bold text-xs shadow-2xl shadow-purple-600/40 hover:scale-105 hover:shadow-purple-600/60 transition-all cursor-pointer border border-purple-400/40"
      :title="currentLang === 'km' ? 'បើក SPI AI Tutor 24/7' : 'Open 24/7 AI Tutor'"
    >
      <span class="text-lg animate-bounce">🤖</span>
      <span class="hidden sm:inline font-extrabold tracking-wide">SPI AI Tutor 24/7</span>
      <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
    </button>

    <!-- EXPANDED AI CHAT MODAL / DRAWER -->
    <div
      v-else
      class="w-[360px] sm:w-[420px] h-[560px] bg-slate-900/95 backdrop-blur-2xl border border-purple-500/40 rounded-3xl shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200"
    >
      <!-- Header -->
      <div class="px-5 py-4 bg-gradient-to-r from-purple-950 via-slate-900 to-indigo-950 border-b border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="relative">
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-purple-500 to-indigo-500 flex items-center justify-center text-white text-base font-bold shadow">
              🤖
            </div>
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 rounded-full ring-2 ring-slate-900"></span>
          </div>
          <div>
            <div class="flex items-center gap-1.5">
              <p class="text-xs font-bold text-white">SPI AI Tutor 24/7</p>
              <span class="px-1.5 py-0.2 rounded text-[8px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">Workers AI</span>
            </div>
            <p class="text-[10px] text-emerald-400 font-medium">
              {{ currentLang === 'km' ? 'ភ្ជាប់ Cloudflare AI Gateway រួចរាល់' : 'Cloudflare AI Gateway Connected' }}
            </p>
          </div>
        </div>

        <button
          @click="toggleWidget"
          class="w-7 h-7 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center text-xs transition-colors cursor-pointer"
        >
          ✕
        </button>
      </div>

      <!-- Messages Stream -->
      <div class="flex-1 p-4 overflow-y-auto custom-scrollbar space-y-3">
        <div
          v-for="(msg, idx) in chatMessages"
          :key="idx"
          :class="[
            msg.role === 'user' ? 'justify-end' : 'justify-start',
            'flex items-start gap-2.5'
          ]"
        >
          <div
            v-if="msg.role === 'assistant'"
            class="w-7 h-7 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 flex items-center justify-center text-xs shrink-0 mt-0.5"
          >
            🤖
          </div>

          <div
            :class="[
              msg.role === 'user'
                ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none'
                : 'bg-slate-950 border border-slate-800 text-slate-200 rounded-2xl rounded-tl-none',
              'max-w-[85%] p-3 text-xs leading-relaxed space-y-1 shadow-md'
            ]"
          >
            <p class="whitespace-pre-line">{{ (currentLang === 'en' && msg.textEn) ? msg.textEn : msg.text }}</p>
            <p :class="[msg.role === 'user' ? 'text-indigo-200' : 'text-slate-500', 'text-[9px] text-right']">
              {{ msg.time === 'ឥឡូវនេះ' ? (currentLang === 'km' ? 'ឥឡូវនេះ' : 'Just now') : msg.time }}
            </p>
          </div>
        </div>

        <div v-if="isSending" class="flex items-center gap-2 text-xs text-purple-400 italic">
          <span class="animate-spin">⏳</span>
          <span>{{ currentLang === 'km' ? 'AI កំពុងវិភាគចម្លើយ...' : 'AI is analyzing your question...' }}</span>
        </div>
      </div>

      <!-- Quick Suggestion Chips -->
      <div class="px-3 py-1.5 bg-slate-950/70 border-t border-slate-800 flex items-center gap-1.5 overflow-x-auto custom-scrollbar">
        <button
          v-for="chip in quickChips"
          :key="chip"
          @click="sendQuick(chip)"
          class="px-2.5 py-1 rounded-full bg-slate-800/90 hover:bg-slate-700 text-slate-300 text-[10px] border border-slate-700 shrink-0 transition-colors cursor-pointer"
        >
          {{ chip }}
        </button>
      </div>

      <!-- Input Box -->
      <div class="p-3 bg-slate-950 border-t border-slate-800 flex items-center gap-2">
        <input
          v-model="messageInput"
          @keyup.enter="sendMessage"
          type="text"
          :placeholder="currentLang === 'km' ? 'សួរសំណួរមេរៀនគ្រប់មុខវិជ្ជា...' : 'Ask any study question across all majors...'"
          class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500"
        />
        <button
          @click="sendMessage"
          :disabled="isSending || !messageInput.trim()"
          class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-40 text-white font-bold text-xs shadow-md transition-all shrink-0 cursor-pointer"
        >
          ➤
        </button>
      </div>
    </div>
  </div>
</template>
