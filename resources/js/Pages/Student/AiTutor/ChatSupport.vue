<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const messageInput = ref('')
const isSending = ref(false)

const chatMessages = ref([
  {
    id: 1,
    sender: 'ai',
    text: 'សួស្តី Chan Dara! ខ្ញុំជា AI Learning Assistant របស់ Saint Paul Institute។ តើអ្នកមានចម្ងល់អ្វីទាក់ទងនឹងមេរៀន C Programming, Data Structures, ឬមុខវិជ្ជាដទៃទៀតទេ? ខ្ញុំអាចឆ្លើយជាភាសាខ្មែរ ឬអង់គ្លេសបាន 24/7!',
    time: '10:00 AM'
  },
  {
    id: 2,
    sender: 'user',
    text: 'តើ Pointer និង Variable ធម្មតាក្នុងភាសា C ខុសគ្នាយ៉ាងដូចម្តេច? ហើយពេលណាដែលត្រូវប្រើ Pointer?',
    time: '10:02 AM'
  },
  {
    id: 3,
    sender: 'ai',
    text: 'សំណួរល្អណាស់! 💡\n\n1. **Variable ធម្មតា** (ឧ. `int x = 10;`): រក្សាទុក "តម្លៃទិន្នន័យផ្ទាល់" (Value) ក្នុង RAM Memory Address ណាមួយ។\n2. **Pointer Variable** (ឧ. `int *ptr = &x;`): រក្សាទុក "អាសយដ្ឋានមេម៉ូរី" (Memory Address) របស់ Variable ផ្សេងទៀត។\n\n📌 **ពេលណាត្រូវប្រើ Pointer?**\n- **Pass by Reference**: នៅពេលចង់ឱ្យ Function អាចផ្លាស់ប្តូរតម្លៃ Variable ដើមបាន។\n- **Dynamic Memory Allocation**: ប្រើជាមួយ `malloc()`, `calloc()` សម្រាប់បង្កើតទំហំ Array ឬ Data Structure តាមតម្រូវការជាក់ស្តែង។\n- **Linked Lists & Trees**: សម្រាប់ភ្ជាប់ Nodes ទៅវិញទៅមក។',
    time: '10:03 AM'
  }
])

const quickSuggestions = [
  'ពន្យល់ពី Dynamic Memory Allocation (malloc, free)',
  'របៀបដោះស្រាយ Loop Infinite ក្នុង C',
  'ភាពខុសគ្នារវាង Struct និង Union',
  'What is the difference between SQL JOIN and UNION?'
]

const sendMessage = async () => {
  if (!messageInput.value.trim() || isSending.value) return
  const text = messageInput.value.trim()
  chatMessages.value.push({
    id: Date.now(),
    sender: 'user',
    text: text,
    time: 'Just now'
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
        message: text,
        history: chatMessages.value.slice(-6)
      })
    })
    const data = await res.json()
    chatMessages.value.push({
      id: Date.now() + 1,
      sender: 'ai',
      text: data.reply || '🤖 បានទទួលសំណួររបស់អ្នក! សូមពិនិត្យមើលខ្លឹមសារមេរៀន និងអនុវត្តលំហាត់ជាក់ស្តែងបន្ថែម។',
      time: data.time || 'Just now'
    })
  } catch (e) {
    chatMessages.value.push({
      id: Date.now() + 1,
      sender: 'ai',
      text: '🤖 ខ្ញុំជា AI Tutor នៅ Saint Paul Institute។ សំណួររបស់អ្នកបានកត់ត្រាជោគជ័យ! សូមសាកល្បងសាកសួរម្ដងទៀត ឬចូលទៅកាន់ Practice Lab ដើម្បីអនុវត្តជាក់ស្ដែង។',
      time: 'Just now'
    })
  } finally {
    isSending.value = false
  }
}

const sendQuick = (text: string) => {
  messageInput.value = text
  sendMessage()
}
</script>

<template>
  <StudentLayout title="AI Assistant — 24/7 Chat Support">
    <div class="space-y-6">
      
      <!-- Top Banner -->
      <div class="bg-gradient-to-r from-emerald-950 via-slate-900 to-indigo-950 border border-emerald-900/60 rounded-3xl p-6 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold uppercase tracking-wider">
            💬 24/7 Intelligent Course Q&A
          </span>
          <h1 class="text-xl md:text-2xl font-black text-white mt-1.5 flex items-center gap-2">
            <span>AI CHAT SUPPORT (សួរសំណួរមេរៀនបាន 24/7)</span>
          </h1>
          <p class="text-xs text-slate-300 mt-1">
            សួរសំណួរមេរៀនគ្រប់ពេលវេលា ដោះស្រាយកូដ bug និងពន្យល់ step-by-step
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Link
            href="/student/ai-tutor/english"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition-colors"
          >
            🗣️ English Tutor
          </Link>
          <Link
            href="/student/ai-tutor/feedback"
            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition-colors"
          >
            ⚡ Quiz Feedback
          </Link>
        </div>
      </div>

      <!-- Chat Container -->
      <div class="bg-slate-800/90 border border-slate-700/80 rounded-3xl shadow-2xl flex flex-col h-[650px] overflow-hidden">
        <!-- Chat Header -->
        <div class="px-6 py-4 border-b border-slate-700/80 bg-slate-900/60 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="relative">
              <img
                :src="'/images/ai-robot-avatar.png'"
                alt="SPI AI Tutor Assistant"
                class="w-10 h-10 rounded-xl object-contain shadow select-none"
              />
              <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 rounded-full ring-2 ring-slate-900"></span>
            </div>
            <div>
              <p class="text-sm font-bold text-white">SPI AI Tutor Assistant</p>
              <p class="text-[10px] text-emerald-400 font-medium">Online • Responds in &lt;1 second</p>
            </div>
          </div>

          <span class="px-2.5 py-1 rounded-xl bg-slate-800 text-slate-400 text-xs border border-slate-700">
            Powered by SPI Gemini Pro
          </span>
        </div>

        <!-- Chat Messages Area -->
        <div class="flex-1 p-6 overflow-y-auto custom-scrollbar space-y-4">
          <div
            v-for="msg in chatMessages"
            :key="msg.id"
            :class="[
              msg.sender === 'user' ? 'justify-end' : 'justify-start',
              'flex items-start gap-3'
            ]"
          >
            <img
              v-if="msg.sender === 'ai'"
              :src="'/images/ai-robot-avatar.png'"
              alt="AI"
              class="w-8 h-8 rounded-xl object-contain shrink-0 select-none"
            />

            <div
              :class="[
                msg.sender === 'user'
                  ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none'
                  : 'bg-slate-900/90 border border-slate-700/80 text-slate-100 rounded-2xl rounded-tl-none',
                'max-w-2xl p-4 shadow-lg text-xs md:text-sm space-y-1'
              ]"
            >
              <p class="whitespace-pre-line leading-relaxed">{{ msg.text }}</p>
              <p :class="[msg.sender === 'user' ? 'text-indigo-200' : 'text-slate-500', 'text-[10px] text-right mt-1']">
                {{ msg.time }}
              </p>
            </div>

            <div
              v-if="msg.sender === 'user'"
              class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0"
            >
              U
            </div>
          </div>

          <div v-if="isSending" class="flex items-center gap-2 text-xs text-slate-400 italic">
            <img :src="'/images/ai-robot-avatar.png'" alt="AI" class="w-5 h-5 rounded-md object-contain shrink-0 select-none" />
            <span>AI is typing response...</span>
          </div>
        </div>

        <!-- Quick Prompt Suggestions -->
        <div class="px-6 py-2 bg-slate-900/40 border-t border-slate-800 flex items-center gap-2 overflow-x-auto custom-scrollbar">
          <span class="text-[11px] text-slate-400 shrink-0">💡 Suggested:</span>
          <button
            v-for="sugg in quickSuggestions"
            :key="sugg"
            @click="sendQuick(sugg)"
            class="px-3 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs border border-slate-700 shrink-0 transition-colors"
          >
            {{ sugg }}
          </button>
        </div>

        <!-- Message Input Box -->
        <div class="p-4 bg-slate-900/90 border-t border-slate-700/80 flex items-center gap-3">
          <input
            v-model="messageInput"
            @keyup.enter="sendMessage"
            type="text"
            placeholder="Ask anything about your courses, code debugging, exercises..."
            class="flex-1 bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-xs md:text-sm text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition-colors"
          />
          <button
            @click="sendMessage"
            class="px-6 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-2 shrink-0"
          >
            <span>Send</span>
            <span>➤</span>
          </button>
        </div>
      </div>

    </div>
  </StudentLayout>
</template>
