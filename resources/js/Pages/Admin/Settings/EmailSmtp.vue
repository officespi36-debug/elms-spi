<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  form: any
  saveSettings: () => void
  sendTestEmail: (recipient: string) => void
}>()

const testEmailRecipient = ref('admin@elms.edu.kh')
</script>

<template>
  <div class="bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl p-6 space-y-6 shadow-xl">
    <div class="flex items-center justify-between border-b border-slate-700/60 pb-4">
      <div>
        <h2 class="text-lg font-bold text-white flex items-center gap-2">📧 Email / SMTP Settings</h2>
        <p class="text-xs text-slate-400">Configure automated email delivery for Institutional Announcements, Welcome Emails, Password Reset, and Certificates.</p>
      </div>
      <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">🟢 Connected</span>
    </div>

    <div class="space-y-4">
      <h3 class="text-sm font-bold text-slate-200">Email Provider</h3>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <button v-for="prov in ['gmail', 'sendgrid', 'mailgun', 'custom']" :key="prov" @click="form.smtp_provider = prov" type="button" :class="form.smtp_provider === prov ? 'bg-blue-600/20 border-blue-500 text-white font-bold' : 'bg-slate-900 border-slate-700 text-slate-400'" class="p-3 rounded-xl border text-xs capitalize text-center transition-all">
          {{ prov === 'custom' ? 'Custom SMTP' : prov }}
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">SMTP Host</label>
          <input v-model="form.smtp_host" type="text" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">SMTP Port</label>
          <input v-model="form.smtp_port" type="text" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Encryption</label>
          <select v-model="form.smtp_encryption" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5">
            <option value="tls">TLS</option>
            <option value="ssl">SSL</option>
            <option value="none">None</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">SMTP Username</label>
          <input v-model="form.smtp_username" type="text" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">SMTP Password</label>
          <input v-model="form.smtp_password" type="password" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">From Name</label>
          <input v-model="form.mail_from_name" type="text" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">From Email</label>
          <input v-model="form.mail_from_address" type="email" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Reply-To Email</label>
          <input v-model="form.mail_reply_to" type="email" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
        </div>
      </div>
    </div>

    <!-- Sending Limits -->
    <div class="border-t border-slate-700/60 pt-6 space-y-4">
      <h3 class="text-sm font-bold text-slate-200">📊 Sending Limits & Delivery Queue</h3>

      <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-700/60 space-y-3">
        <div class="flex items-center justify-between text-xs">
          <span class="text-slate-300">Sent Today</span>
          <span class="text-slate-400 font-medium">1,245 / 10,000 emails (12%)</span>
        </div>
        <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
          <div class="bg-blue-500 h-full w-[12%] rounded-full"></div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <label class="flex items-center gap-3 cursor-pointer">
          <input v-model="form.mail_queue_enabled" type="checkbox" class="w-4 h-4 rounded text-blue-600 bg-slate-900 border-slate-700" />
          <span class="text-xs text-slate-300">Send through Redis Queue (Background Async)</span>
        </label>

        <div>
          <label class="block text-xs font-medium text-slate-300 mb-1">Retry Failed Attempts</label>
          <input v-model="form.mail_retry_attempts" type="number" min="1" max="10" class="w-full bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2" />
        </div>
      </div>
    </div>

    <!-- Test Email -->
    <div class="border-t border-slate-700/60 pt-6 space-y-4">
      <h3 class="text-sm font-bold text-slate-200">🧪 Test Email Configuration</h3>
      <div class="flex flex-col sm:flex-row items-center gap-3">
        <input v-model="testEmailRecipient" type="email" placeholder="admin@example.com" class="w-full sm:w-72 bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2.5" />
        <button @click="sendTestEmail(testEmailRecipient)" type="button" class="px-3.5 py-2.5 text-xs bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-semibold whitespace-nowrap">📧 Send Test Email</button>
        <button @click="sendTestEmail(testEmailRecipient)" type="button" class="px-3.5 py-2.5 text-xs bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-xl font-medium whitespace-nowrap">🔄 Test SMTP Connection</button>
      </div>
    </div>

    <div class="flex justify-end border-t border-slate-700/60 pt-4">
      <button @click="saveSettings" type="button" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-lg">💾 Save SMTP Settings</button>
    </div>
  </div>
</template>
