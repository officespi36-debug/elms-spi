<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps<{
  profile: {
    name: string
    email: string
    student_id?: string
    faculty?: string
    department?: string
    major?: string
    phone?: string
    bio?: string
    semester?: string
    language?: string
    email_notifications?: boolean
    app_notifications?: boolean
  }
}>()

const page = usePage<any>()
const urlParams = new URLSearchParams(window.location.search)
const activeTab = ref(urlParams.get('tab') || 'personal')

const profileForm = useForm({
  name: props.profile?.name || '',
  email: props.profile?.email || '',
  phone: props.profile?.phone || '',
  bio: props.profile?.bio || '',
})

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const saveProfile = () => {
  profileForm.post('/student/profile', {
    preserveScroll: true,
    onSuccess: () => {
      alert('ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ (Profile Updated Successfully!)')
    }
  })
}

const updatePassword = () => {
  passwordForm.post('/student/profile/password', {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
      alert('កូដសម្ងាត់ត្រូវបានប្តូរដោយជោគជ័យ (Password Changed Successfully!)')
    }
  })
}

const learningActivityLogs = [
  { id: 1, action: 'Watched Lesson Video', detail: 'Chapter 2.3 — Operators in C (12 min)', time: 'Today at 09:30 AM', icon: '🎬' },
  { id: 2, action: 'Completed Practice Quiz', detail: 'Module 2 Drill — Score 85%', time: 'Yesterday at 04:15 PM', icon: '✅' },
  { id: 3, action: 'Submitted Assignment', detail: 'Assignment 1 — Linked List in C', time: 'Aug 12, 2026 at 02:00 PM', icon: '📝' },
  { id: 4, action: 'Payment Verified (ABA KHQR)', detail: 'Enrolled in Data Structures & Algorithms ($12.00)', time: 'Aug 10, 2026 at 11:30 AM', icon: '💳' },
  { id: 5, action: 'Certificate Issued', detail: 'C Programming Basics Certificate (ID: CERT-2026-089)', time: 'Aug 05, 2026 at 10:00 AM', icon: '🏆' }
]
</script>

<template>
  <StudentLayout title="Profile Settings — Student Account">
    <div class="space-y-6">
      
      <!-- Top Profile Hero Banner -->
      <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 rounded-3xl p-6 md:p-8 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
          <div class="relative shrink-0">
            <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-extrabold flex items-center justify-center text-3xl shadow-xl ring-4 ring-indigo-500/30">
              {{ profile.name ? profile.name.charAt(0) : 'S' }}
            </div>
            <span class="absolute bottom-1 right-1 w-4 h-4 bg-emerald-500 rounded-full ring-2 ring-slate-900 shadow"></span>
          </div>

          <div class="space-y-1">
            <div class="flex items-center gap-2.5">
              <h1 class="text-xl md:text-2xl font-black text-white">{{ profile.name }}</h1>
              <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[11px] font-bold">
                ● Online
              </span>
            </div>
            <p class="text-xs text-indigo-300 font-medium">
              ID: <span class="font-mono font-bold">{{ profile.student_id || 'STU-2024-089' }}</span> • {{ profile.major }}
            </p>
            <p class="text-[11px] text-slate-400">
              {{ profile.faculty }} • {{ profile.semester }}
            </p>
          </div>
        </div>

        <!-- Quick Status Card -->
        <div class="px-4 py-3 bg-slate-900/80 border border-slate-800 rounded-2xl flex items-center gap-6 shrink-0">
          <div>
            <p class="text-[10px] text-slate-400 uppercase font-bold">Account Status</p>
            <p class="text-xs font-bold text-emerald-400 mt-0.5">Active Enrolled Student</p>
          </div>
          <div class="border-l border-slate-800 pl-6">
            <p class="text-[10px] text-slate-400 uppercase font-bold">Institute</p>
            <p class="text-xs font-bold text-slate-200 mt-0.5">Saint Paul Institute</p>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center gap-2 border-b border-slate-800 pb-2 overflow-x-auto custom-scrollbar">
        <button
          @click="activeTab = 'id-card'"
          :class="[
            activeTab === 'id-card'
              ? 'bg-indigo-600 text-white shadow-lg'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800',
            'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0'
          ]"
        >
          🪪 1. Digital Student ID (កាតសិស្ស)
        </button>
        <button
          @click="activeTab = 'personal'"
          :class="[
            activeTab === 'personal'
              ? 'bg-indigo-600 text-white shadow-lg'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800',
            'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0'
          ]"
        >
          👤 2. Personal Information
        </button>
        <button
          @click="activeTab = 'academic'"
          :class="[
            activeTab === 'academic'
              ? 'bg-indigo-600 text-white shadow-lg'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800',
            'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0'
          ]"
        >
          🎓 3. Academic Information
        </button>
        <button
          @click="activeTab = 'security'"
          :class="[
            activeTab === 'security'
              ? 'bg-indigo-600 text-white shadow-lg'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800',
            'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0'
          ]"
        >
          🔒 4. Security & Password
        </button>
        <button
          @click="activeTab = 'history'"
          :class="[
            activeTab === 'history'
              ? 'bg-indigo-600 text-white shadow-lg'
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800',
            'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0'
          ]"
        >
          📜 5. Learning History & Logs
        </button>
      </div>

      <!-- TAB 0: Digital Student ID Card -->
      <div v-show="activeTab === 'id-card'" class="space-y-6">
        <div class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-6 md:p-8 shadow-xl space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                <span>🪪 កាតនិស្សិតឌីជីថល (Digital Student ID Card)</span>
              </h3>
              <p class="text-xs text-slate-400 mt-1">កាតសម្គាល់ខ្លួនផ្លូវការសម្រាប់ការសិក្សា និងប្រើប្រាស់សេវាកម្មក្នុងវិទ្យាស្ថាន សន្តប៉ូល</p>
            </div>
            <span class="px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold shrink-0 self-start">
              ⚡ Live Verified Badge
            </span>
          </div>

          <!-- Luxury NFC-Style ID Card -->
          <div class="max-w-md mx-auto relative rounded-3xl p-6 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 border-2 border-indigo-500/40 shadow-2xl shadow-indigo-950/80 overflow-hidden text-white space-y-5">
            <!-- Holographic Shine Background Glow -->
            <div class="absolute -right-16 -top-16 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-48 h-48 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-indigo-800/50 pb-3 relative z-10">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-lg font-black shadow-md shadow-indigo-600/40">
                  🎓
                </div>
                <div>
                  <p class="text-[11px] font-black tracking-wider text-indigo-200 uppercase">SAINT PAUL INSTITUTE</p>
                  <p class="text-[9px] text-slate-400 font-medium">វិទ្យាស្ថាន សន្តប៉ូល • SPI E-LMS</p>
                </div>
              </div>
              <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[9px] font-bold uppercase tracking-wider">
                Official ID
              </span>
            </div>

            <!-- Middle: Avatar & Identity -->
            <div class="flex items-center gap-4 relative z-10">
              <div class="relative shrink-0">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-2xl flex items-center justify-center shadow-lg ring-2 ring-indigo-400/40">
                  {{ profile.name ? profile.name.charAt(0) : 'S' }}
                </div>
                <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center text-[10px] text-white font-bold ring-2 ring-slate-900">
                  ✓
                </div>
              </div>

              <div class="space-y-1">
                <h4 class="text-base font-black text-white leading-tight">{{ profile.name }}</h4>
                <p class="text-xs text-indigo-300 font-extrabold font-mono">{{ profile.student_id || 'STU-2024-089' }}</p>
                <p class="text-[11px] text-slate-300 font-medium">{{ profile.major || 'Information Technology' }}</p>
                <p class="text-[10px] text-slate-400">{{ profile.faculty || 'Faculty of Science & Tech' }}</p>
              </div>
            </div>

            <!-- Bottom: Term & Dynamic QR Code -->
            <div class="pt-3 border-t border-indigo-800/50 flex items-end justify-between gap-4 relative z-10">
              <div class="space-y-1.5 text-[10px]">
                <div>
                  <span class="text-slate-400 block font-semibold uppercase">Academic Term:</span>
                  <span class="text-emerald-400 font-extrabold">{{ profile.semester || 'Semester 1 • Year 2026' }}</span>
                </div>
                <div>
                  <span class="text-slate-400 block font-semibold uppercase">Status:</span>
                  <span class="text-slate-200 font-bold">🟢 Active Enrolled Student</span>
                </div>
              </div>

              <!-- Live QR Code -->
              <div class="bg-white p-1.5 rounded-2xl shrink-0 shadow-lg shadow-black/40">
                <img
                  :src="`https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=https://spilms.tech/verify-certificate/${profile.student_id || 'STU-2024-089'}`"
                  alt="Student Verification QR"
                  class="w-14 h-14 rounded-lg"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 1: Personal Information -->
      <div v-show="activeTab === 'personal'" class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-6 shadow-xl space-y-5">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
          <span>👤 Personal Contact & Profile Details</span>
        </h3>

        <form @submit.prevent="saveProfile" class="space-y-4 max-w-2xl">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Full Student Name:</label>
              <input
                v-model="profileForm.name"
                type="text"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-indigo-500"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Email Address:</label>
              <input
                v-model="profileForm.email"
                type="email"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-indigo-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Phone Number (Telegram):</label>
              <input
                v-model="profileForm.phone"
                type="text"
                class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-indigo-500"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Student ID (Official):</label>
              <input
                :value="profile.student_id"
                disabled
                type="text"
                class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-slate-400 font-mono cursor-not-allowed"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Bio / About Me:</label>
            <textarea
              v-model="profileForm.bio"
              rows="3"
              class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-indigo-500"
            ></textarea>
          </div>

          <button
            type="submit"
            :disabled="profileForm.processing"
            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md transition-all"
          >
            Save Changes
          </button>
        </form>
      </div>

      <!-- TAB 2: Academic Information -->
      <div v-show="activeTab === 'academic'" class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-6 shadow-xl space-y-5">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
          <span>🎓 Academic Enrollment Information</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-3xl text-xs">
          <div class="p-4 bg-slate-900/80 rounded-2xl border border-slate-800 space-y-1">
            <p class="text-slate-400 text-[10px] uppercase font-bold">Faculty</p>
            <p class="text-sm font-bold text-white">{{ profile.faculty }}</p>
          </div>
          <div class="p-4 bg-slate-900/80 rounded-2xl border border-slate-800 space-y-1">
            <p class="text-slate-400 text-[10px] uppercase font-bold">Department</p>
            <p class="text-sm font-bold text-white">{{ profile.department }}</p>
          </div>
          <div class="p-4 bg-slate-900/80 rounded-2xl border border-slate-800 space-y-1">
            <p class="text-slate-400 text-[10px] uppercase font-bold">Major Specialization</p>
            <p class="text-sm font-bold text-indigo-300">{{ profile.major }}</p>
          </div>
          <div class="p-4 bg-slate-900/80 rounded-2xl border border-slate-800 space-y-1">
            <p class="text-slate-400 text-[10px] uppercase font-bold">Current Academic Year & Semester</p>
            <p class="text-sm font-bold text-emerald-400">{{ profile.semester }}</p>
          </div>
        </div>
      </div>

      <!-- TAB 3: Security & Change Password -->
      <div v-show="activeTab === 'security'" class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-6 shadow-xl space-y-5">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
          <span>🔒 Security & Password Management</span>
        </h3>

        <form @submit.prevent="updatePassword" class="space-y-4 max-w-md">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Current Password:</label>
            <input
              v-model="passwordForm.current_password"
              type="password"
              class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-indigo-500"
              required
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">New Password (min 8 chars):</label>
            <input
              v-model="passwordForm.password"
              type="password"
              class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-indigo-500"
              required
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Confirm New Password:</label>
            <input
              v-model="passwordForm.password_confirmation"
              type="password"
              class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-indigo-500"
              required
            />
          </div>

          <button
            type="submit"
            :disabled="passwordForm.processing"
            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md transition-all"
          >
            Update Password
          </button>
        </form>
      </div>

      <!-- TAB 4: Learning History & Logs -->
      <div v-show="activeTab === 'history'" class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-6 shadow-xl space-y-4">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
          <span>📜 Learning History & Activity Logs</span>
        </h3>

        <div class="space-y-2.5">
          <div
            v-for="log in learningActivityLogs"
            :key="log.id"
            class="p-3.5 bg-slate-900/70 border border-slate-800 rounded-2xl flex items-center justify-between gap-4 text-xs"
          >
            <div class="flex items-center gap-3">
              <span class="text-xl p-2 bg-slate-800 rounded-xl">{{ log.icon }}</span>
              <div>
                <p class="font-bold text-white">{{ log.action }}</p>
                <p class="text-slate-400 text-[11px]">{{ log.detail }}</p>
              </div>
            </div>
            <span class="text-slate-500 font-mono text-[11px] shrink-0">{{ log.time }}</span>
          </div>
        </div>
      </div>

    </div>
  </StudentLayout>
</template>
