<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { i18n } from '@/Services/i18n'

const currentLang = computed(() => i18n.locale.value)

const isOpen = ref(false)
const messageInput = ref('')
const isSending = ref(false)
const chatContainerRef = ref<HTMLElement | null>(null)

const scrollToBottom = async () => {
  await nextTick()
  if (chatContainerRef.value) {
    chatContainerRef.value.scrollTo({
      top: chatContainerRef.value.scrollHeight,
      behavior: 'smooth'
    })
  }
}

const handleExternalOpen = (event: Event) => {
  isOpen.value = true
  const customEv = event as CustomEvent<{ prompt?: string }>
  if (customEv.detail?.prompt) {
    messageInput.value = customEv.detail.prompt
    sendMessage()
  }
  scrollToBottom()
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && isOpen.value) {
    isOpen.value = false
  }
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('open-ai-tutor', handleExternalOpen)
    window.addEventListener('keydown', handleKeydown)
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('open-ai-tutor', handleExternalOpen)
    window.removeEventListener('keydown', handleKeydown)
  }
})

const formatMarkdown = (text: string) => {
  if (!text) return ''
  return text
    // Escape HTML special characters
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    // Multi-line code block ```code```
    .replace(/```([\s\S]*?)```/g, '<pre class="bg-slate-900 border border-slate-700/80 p-2.5 rounded-xl text-emerald-300 font-mono text-[11px] my-1.5 overflow-x-auto"><code>$1</code></pre>')
    // Inline code `code`
    .replace(/`([^`]+)`/g, '<code class="bg-purple-100 dark:bg-slate-800 text-purple-700 dark:text-purple-300 px-1.5 py-0.5 rounded text-[11px] font-mono">$1</code>')
    // Bold **text**
    .replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900 dark:text-white">$1</strong>')
    // Italic *text*
    .replace(/\*(.*?)\*/g, '<em class="italic text-purple-800 dark:text-purple-200">$1</em>')
    // Line breaks
    .replace(/\n/g, '<br/>')
}

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
  if (isOpen.value) {
    scrollToBottom()
  }
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
  scrollToBottom()

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
    scrollToBottom()
  }
}
</script>

<template>
  <div>
    <!-- LIGHT DISMISS BACKDROP OVERLAY (Closes on outside click) -->
    <Transition name="fade-overlay">
      <div
        v-if="isOpen"
        @click="isOpen = false"
        class="fixed inset-0 bg-slate-900/40 dark:bg-black/60 backdrop-blur-[2px] z-40 transition-opacity duration-200"
        aria-hidden="true"
      ></div>
    </Transition>

    <!-- FLOATING TOGGLE BUTTON (56px circular FAB with gentle pulse & hover-expand label) -->
    <div v-if="!isOpen" class="fixed bottom-6 right-6 z-50">
      <button
        @click="toggleWidget"
        type="button"
        class="group relative flex items-center h-14 w-14 hover:w-auto hover:px-4 rounded-full bg-gradient-to-tr from-[#0b57d0] via-indigo-600 to-purple-600 text-white shadow-xl shadow-indigo-600/30 hover:shadow-2xl hover:shadow-indigo-600/50 hover:scale-105 active:scale-95 transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] cursor-pointer border border-white/20 p-2 overflow-hidden ring-4 ring-indigo-500/20"
        :title="currentLang === 'km' ? 'បើក SPI AI Tutor 24/7' : 'Open 24/7 AI Tutor'"
        aria-label="SPI AI Tutor 24/7"
      >
        <!-- Gentle pulse glow effect -->
        <span class="absolute inset-0 rounded-full bg-indigo-500/25 animate-ping pointer-events-none opacity-40"></span>

        <!-- Icon & Status Dot (56px centered when not hovered) -->
        <div class="relative flex items-center justify-center w-10 h-10 shrink-0">
          <svg class="w-6 h-6 text-white group-hover:scale-110 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 8V4H8"></path>
            <rect width="16" height="12" x="4" y="8" rx="2"></rect>
            <path d="M2 14h2"></path>
            <path d="M20 14h2"></path>
            <path d="M15 13v2"></path>
            <path d="M9 13v2"></path>
          </svg>
          <span class="absolute top-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-400 ring-2 ring-slate-900 animate-pulse"></span>
        </div>

        <!-- Hover Reveal Label -->
        <span class="max-w-0 group-hover:max-w-xs transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] overflow-hidden whitespace-nowrap text-xs font-bold tracking-wide text-white ml-0 group-hover:ml-2">
          SPI AI Tutor 24/7
        </span>
      </button>
    </div>

    <!-- AI CHAT PANEL (Theme-Adaptive: Desktop 560px × 420px, Mobile Bottom Sheet 85vh) -->
    <div
      v-if="isOpen"
      class="fixed inset-x-0 bottom-0 h-[85vh] sm:h-[560px] sm:w-[420px] sm:bottom-6 sm:right-6 sm:inset-auto z-50 flex flex-col"
    >
      <div
        class="ai-chat-panel w-full h-full bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl border border-purple-200/90 dark:border-purple-500/30 rounded-t-3xl sm:rounded-3xl shadow-[0_20px_60px_-15px_rgba(79,70,229,0.25),0_0_35px_rgba(168,85,247,0.15)] dark:shadow-[0_20px_60px_-15px_rgba(0,0,0,0.8),0_0_35px_rgba(147,51,234,0.3)] flex flex-col overflow-hidden text-slate-800 dark:text-slate-100"
        @click.stop
      >
        <!-- Mobile Drag Handle -->
        <div class="sm:hidden pt-2 pb-1 bg-gradient-to-r from-purple-700 via-indigo-700 to-purple-800 flex justify-center">
          <div class="w-12 h-1.5 rounded-full bg-white/40"></div>
        </div>

        <!-- Purple Gradient Header -->
        <div class="px-5 py-3.5 bg-gradient-to-r from-purple-700 via-indigo-700 to-purple-800 text-white border-b border-purple-600/30 flex items-center justify-between shrink-0 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="relative">
              <div class="w-9 h-9 rounded-full bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center text-white text-base font-bold shadow">
                🤖
              </div>
              <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 rounded-full ring-2 ring-purple-900 animate-pulse"></span>
            </div>
            <div>
              <div class="flex items-center gap-1.5">
                <p class="text-xs font-bold text-white tracking-wide">SPI AI Tutor 24/7</p>
                <span class="px-1.5 py-0.2 rounded text-[8px] font-bold bg-white/20 text-white border border-white/30">Workers AI</span>
              </div>
              <!-- Online · Ready to help status -->
              <p class="text-[10px] text-emerald-300 font-medium flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span>{{ currentLang === 'km' ? 'អនឡាញ · រួចរាល់ក្នុងការជួយ' : 'Online · Ready to help' }}</span>
              </p>
            </div>
          </div>

          <button
            @click="isOpen = false"
            class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center text-xs transition-colors cursor-pointer"
            aria-label="Close AI Tutor"
          >
            ✕
          </button>
        </div>

        <!-- Messages Stream with Auto-scroll -->
        <div
          ref="chatContainerRef"
          class="flex-1 p-4 overflow-y-auto space-y-3 bg-slate-50/70 dark:bg-slate-950/60 custom-scrollbar"
        >
          <div
            v-for="(msg, idx) in chatMessages"
            :key="idx"
            :class="[
              msg.role === 'user' ? 'justify-end' : 'justify-start',
              'msg-slide-up flex items-start gap-2.5'
            ]"
          >
            <div
              v-if="msg.role === 'assistant'"
              class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center text-xs shrink-0 mt-0.5 shadow-xs"
            >
              🤖
            </div>

            <div
              :class="[
                msg.role === 'user'
                  ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-2xl rounded-tr-none shadow-md shadow-purple-600/20'
                  : 'bg-white dark:bg-slate-950 border border-slate-200/90 dark:border-slate-800 text-slate-800 dark:text-slate-200 rounded-2xl rounded-tl-none shadow-sm',
                'max-w-[85%] p-3 text-xs leading-relaxed space-y-1'
              ]"
            >
              <!-- Rendered Markdown -->
              <div
                class="leading-relaxed whitespace-pre-wrap select-text"
                v-html="formatMarkdown((currentLang === 'en' && msg.textEn) ? msg.textEn : msg.text)"
              ></div>
              <p :class="[msg.role === 'user' ? 'text-purple-200' : 'text-slate-400 dark:text-slate-500', 'text-[9px] text-right font-medium']">
                {{ msg.time === 'ឥឡូវនេះ' ? (currentLang === 'km' ? 'ឥឡូវនេះ' : 'Just now') : msg.time }}
              </p>
            </div>
          </div>

          <!-- Suggested Quick Question Chips (Directly under the first message to prevent empty space) -->
          <div v-if="chatMessages.length <= 2" class="pt-1 pb-2 pl-9 space-y-2 msg-slide-up">
            <p class="text-[11px] font-semibold text-purple-700 dark:text-purple-300 flex items-center gap-1">
              <span>💡</span>
              <span>{{ currentLang === 'km' ? 'សំណួរណែនាំរហ័ស:' : 'Suggested quick questions:' }}</span>
            </p>
            <div class="flex flex-wrap gap-1.5 no-scrollbar max-h-36 overflow-y-auto">
              <button
                v-for="chip in quickChips"
                :key="chip"
                @click="sendQuick(chip)"
                type="button"
                class="px-3 py-1.5 rounded-full bg-white dark:bg-slate-800 hover:bg-purple-50 dark:hover:bg-slate-700 text-purple-900 dark:text-purple-200 text-[11px] font-medium border border-purple-200 dark:border-slate-700 shadow-xs hover:border-purple-400 dark:hover:border-purple-500/50 transition-all hover:scale-102 active:scale-95 cursor-pointer leading-tight text-left"
              >
                ✨ {{ chip }}
              </button>
            </div>
          </div>

          <!-- Modern Animated Typing Indicator -->
          <div v-if="isSending" class="flex items-start gap-2.5 msg-slide-up">
            <div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-500/20 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center text-xs shrink-0 mt-0.5">
              🤖
            </div>
            <div class="bg-white dark:bg-slate-950 border border-purple-100 dark:border-slate-800 rounded-2xl rounded-tl-none p-3 shadow-sm flex items-center gap-2">
              <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce [animation-delay:-0.3s]"></span>
                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce [animation-delay:-0.15s]"></span>
                <span class="w-2 h-2 rounded-full bg-pink-500 animate-bounce"></span>
              </div>
              <span class="text-xs text-purple-700 dark:text-purple-300 font-medium ml-1">
                {{ currentLang === 'km' ? 'AI កំពុងវិភាគចម្លើយ...' : 'AI Tutor is thinking...' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Input Box with Bright Vibrant Gradient Send Button -->
        <div class="p-3 bg-white dark:bg-slate-950 border-t border-slate-200/90 dark:border-slate-800 flex items-center gap-2 shrink-0">
          <input
            v-model="messageInput"
            @keyup.enter="sendMessage"
            type="text"
            :placeholder="currentLang === 'km' ? 'សួរសំណួរមេរៀនគ្រប់មុខវិជ្ជា...' : 'Ask any study question across all majors...'"
            class="flex-1 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl px-3.5 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 font-medium focus:outline-none focus:border-purple-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-purple-500/20 transition-all"
          />
          <button
            @click="sendMessage"
            :disabled="isSending || !messageInput.trim()"
            class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-pink-500 hover:from-purple-500 hover:via-indigo-500 hover:to-pink-400 text-white font-bold text-xs shadow-md shadow-purple-500/30 hover:shadow-lg hover:shadow-purple-500/40 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed transition-all shrink-0 cursor-pointer flex items-center gap-1"
          >
            <span>{{ currentLang === 'km' ? 'ផ្ញើ' : 'Send' }}</span>
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14"></path>
              <path d="m12 5 7 7-7 7"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* 250ms scale + fade entrance */
.ai-chat-panel {
  animation: aiPanelEnter 250ms cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes aiPanelEnter {
  from {
    opacity: 0;
    transform: scale(0.92) translateY(12px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@media (max-width: 639px) {
  .ai-chat-panel {
    animation: aiSheetEnter 250ms cubic-bezier(0.16, 1, 0.3, 1) both;
  }
  @keyframes aiSheetEnter {
    from {
      opacity: 0;
      transform: translateY(100%);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
}

.msg-slide-up {
  animation: msgSlideUp 200ms cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes msgSlideUp {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-overlay-enter-active,
.fade-overlay-leave-active {
  transition: opacity 200ms ease;
}
.fade-overlay-enter-from,
.fade-overlay-leave-to {
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .ai-chat-panel,
  .msg-slide-up {
    animation: none !important;
    transition: none !important;
  }
}

/* Hide scrollbar for clean chip appearance */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.custom-scrollbar::-webkit-scrollbar {
  height: 4px;
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.3);
  border-radius: 9999px;
}
</style>
