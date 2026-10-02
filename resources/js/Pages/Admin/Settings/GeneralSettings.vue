<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  settings?: Record<string, any>
}>()

const emit = defineEmits<{
  (e: 'saveSettings', data: any): void
  (e: 'notify', msg: string): void
}>()

// 1. Institution Information
const institutionName = ref(props.settings?.institution_name || 'Saint Paul Institute')
const institutionCode = ref(props.settings?.institution_code || 'SPI')
const logoUrl = ref(props.settings?.site_logo || '/images/logo-dark.png')
const contactEmail = ref(props.settings?.contact_email || 'info@spi.edu.kh')
const contactPhone = ref(props.settings?.contact_phone || '+855 23 888 999')
const address = ref(props.settings?.address || 'National Road 2, Angk Ta Saom, Tram Kak, Takeo Province, Cambodia')
const websiteUrl = ref(props.settings?.website_url || 'https://spi.edu.kh')

// 2. System Settings
const systemName = ref(props.settings?.site_name || 'SPI E-Learning System')
const systemDescription = ref(props.settings?.system_description || 'Autonomous & Adaptive Academic Learning Management System with AI Diagnostics')
const defaultLanguage = ref(props.settings?.default_language || 'km')
const timeZone = ref(props.settings?.timezone || 'Asia/Phnom_Penh')
const dateFormat = ref(props.settings?.date_format || 'DD/MM/YYYY')

// 3. User Registration
const studentSelfRegistration = ref(props.settings?.student_self_registration ?? true)
const teacherSelfRegistration = ref(props.settings?.teacher_self_registration ?? false)

// 4. Notification Settings
const emailNotifications = ref(props.settings?.email_notifications ?? true)
const systemNotifications = ref(props.settings?.system_notifications ?? true)
const assignmentNotifications = ref(props.settings?.assignment_notifications ?? true)
const quizNotifications = ref(props.settings?.quiz_notifications ?? true)
const aiNotifications = ref(props.settings?.ai_notifications ?? true)

// Maintenance Mode
const maintenanceMode = ref(props.settings?.maintenance_mode ?? false)

// Feedback
const isSaving = ref(false)

function handleSave() {
  isSaving.value = true
  const data = {
    institution_name: institutionName.value,
    institution_code: institutionCode.value,
    site_logo: logoUrl.value,
    contact_email: contactEmail.value,
    contact_phone: contactPhone.value,
    address: address.value,
    website_url: websiteUrl.value,
    site_name: systemName.value,
    system_description: systemDescription.value,
    default_language: defaultLanguage.value,
    timezone: timeZone.value,
    date_format: dateFormat.value,
    student_self_registration: studentSelfRegistration.value,
    teacher_self_registration: teacherSelfRegistration.value,
    email_notifications: emailNotifications.value,
    system_notifications: systemNotifications.value,
    assignment_notifications: assignmentNotifications.value,
    quiz_notifications: quizNotifications.value,
    ai_notifications: aiNotifications.value,
    maintenance_mode: maintenanceMode.value,
  }

  emit('saveSettings', data)
  setTimeout(() => {
    isSaving.value = false
    emit('notify', '✅ General Settings updated successfully! Audit log entry recorded in System Logs.')
  }, 400)
}

function handleReset() {
  institutionName.value = 'Saint Paul Institute'
  institutionCode.value = 'SPI'
  contactEmail.value = 'info@spi.edu.kh'
  contactPhone.value = '+855 23 888 999'
  address.value = 'National Road 2, Angk Ta Saom, Tram Kak, Takeo Province, Cambodia'
  websiteUrl.value = 'https://spi.edu.kh'
  systemName.value = 'SPI E-Learning System'
  defaultLanguage.value = 'km'
  timeZone.value = 'Asia/Phnom_Penh'
  dateFormat.value = 'DD/MM/YYYY'
  studentSelfRegistration.value = true
  teacherSelfRegistration.value = false
  emailNotifications.value = true
  systemNotifications.value = true
  assignmentNotifications.value = true
  quizNotifications.value = true
  aiNotifications.value = true
  maintenanceMode.value = false
  emit('notify', '🔄 Reset to official Saint Paul Institute defaults.')
}
</script>

<template>
  <div class="space-y-6 text-xs text-slate-200">
    <!-- Header Summary Card -->
    <div class="bg-slate-800/90 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-2">
      <div class="flex items-center justify-between border-b border-slate-700/60 pb-3">
        <div class="flex items-center gap-3">
          <div class="p-2.5 rounded-xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 text-base">
            🏢
          </div>
          <div>
            <h2 class="text-base font-bold text-white">General Settings (ការកំណត់ទូទៅ)</h2>
            <p class="text-[11px] text-slate-400">កំណត់ព័ត៌មាន និងការកំណត់ទូទៅរបស់ SPI E-Learning System</p>
          </div>
        </div>
        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
          ● Institute Config Active
        </span>
      </div>
      <p class="text-[11px] text-slate-400 pt-1">
        ℹ️ កំណត់អត្តសញ្ញាណវិទ្យាស្ថាន, ភាសាប្រព័ន្ធ, ពេលវេលា, សិទ្ធិចុះឈ្មោះ និងប្រព័ន្ធការជូនដំណឹង (គ្មានការប្រើប្រាស់ Payment/ABA)។
      </p>
    </div>

    <!-- 1. Institution Information -->
    <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
      <h3 class="text-sm font-bold text-indigo-300 flex items-center gap-2 border-b border-slate-700/60 pb-2">
        <span>🏫</span>
        <span>1. Institution Information (ព័ត៌មានវិទ្យាស្ថាន)</span>
      </h3>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="sm:col-span-2">
          <label class="block font-medium text-slate-300 mb-1">Institution Name (ឈ្មោះគ្រឹះស្ថាន) *</label>
          <input
            v-model="institutionName"
            type="text"
            class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>
        <div>
          <label class="block font-medium text-slate-300 mb-1">Institution Code (លេខកូដ) *</label>
          <input
            v-model="institutionCode"
            type="text"
            class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block font-medium text-slate-300 mb-1">Contact Email (អ៊ីមែលទំនាក់ទំនង) *</label>
          <input
            v-model="contactEmail"
            type="email"
            class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>
        <div>
          <label class="block font-medium text-slate-300 mb-1">Phone Number (លេខទូរស័ព្ទ) *</label>
          <input
            v-model="contactPhone"
            type="text"
            class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block font-medium text-slate-300 mb-1">Website URL (គេហទំព័រផ្លូវការ)</label>
          <input
            v-model="websiteUrl"
            type="url"
            class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>
        <div>
          <label class="block font-medium text-slate-300 mb-1">Physical Address (អាសយដ្ឋាន)</label>
          <input
            v-model="address"
            type="text"
            class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>
      </div>

      <!-- Logo Preview & Upload -->
      <div class="p-4 bg-slate-900/60 rounded-xl border border-slate-700/60 flex flex-col sm:flex-row items-center justify-between gap-4 mt-2">
        <div class="flex items-center gap-3">
          <img :src="logoUrl" alt="Institute Logo" class="w-12 h-12 rounded-xl object-contain bg-slate-950 p-1 border border-slate-700" />
          <div>
            <div class="font-bold text-white text-xs">Official Logo & Emblem</div>
            <div class="text-[11px] text-slate-400">SVG, PNG format (Recomm. 512x512 transparent)</div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <input
            v-model="logoUrl"
            type="text"
            placeholder="/images/logo-dark.png"
            class="bg-slate-900 border border-slate-700 text-slate-200 px-3 py-1.5 rounded-lg text-xs font-mono w-48 sm:w-60"
          />
        </div>
      </div>
    </div>

    <!-- 2. System Settings -->
    <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
      <h3 class="text-sm font-bold text-indigo-300 flex items-center gap-2 border-b border-slate-700/60 pb-2">
        <span>⚙️</span>
        <span>2. System Settings (ការកំណត់ប្រព័ន្ធ)</span>
      </h3>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block font-medium text-slate-300 mb-1">System Name (ឈ្មោះប្រព័ន្ធ) *</label>
          <input
            v-model="systemName"
            type="text"
            class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          />
        </div>
        <div>
          <label class="block font-medium text-slate-300 mb-1">Default Language (ភាសាលំនាំដើម) *</label>
          <select
            v-model="defaultLanguage"
            class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
          >
            <option value="km">🇰🇭 ភាសាខ្មែរ (Khmer)</option>
            <option value="en">🇬🇧 English</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block font-medium text-slate-300 mb-1">System Description (ការពិពណ៌នាអំពីប្រព័ន្ធ)</label>
        <textarea
          v-model="systemDescription"
          rows="2"
          class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
        ></textarea>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block font-medium text-slate-300 mb-1">Time Zone (តំបន់ម៉ោង) *</label>
          <select
            v-model="timeZone"
            class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono"
          >
            <option value="Asia/Phnom_Penh">Asia/Phnom_Penh (GMT+07:00 Indochina Time)</option>
            <option value="Asia/Bangkok">Asia/Bangkok (GMT+07:00)</option>
            <option value="UTC">UTC (Universal Coordinated Time)</option>
          </select>
        </div>
        <div>
          <label class="block font-medium text-slate-300 mb-1">Date Format (ទម្រង់កាលបរិច្ឆេទ) *</label>
          <select
            v-model="dateFormat"
            class="w-full bg-slate-900 border border-slate-700 text-slate-100 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono"
          >
            <option value="DD/MM/YYYY">DD/MM/YYYY (ឧ. 01/10/2026)</option>
            <option value="YYYY-MM-DD">YYYY-MM-DD (ឧ. 2026-10-01)</option>
            <option value="MM/DD/YYYY">MM/DD/YYYY (ឧ. 10/01/2026)</option>
          </select>
        </div>
      </div>
    </div>

    <!-- 3. User Registration Controls -->
    <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
      <h3 class="text-sm font-bold text-indigo-300 flex items-center gap-2 border-b border-slate-700/60 pb-2">
        <span>👥</span>
        <span>3. User Registration (ការចុះឈ្មោះបង្កើតគណនី)</span>
      </h3>
      <p class="text-slate-400 text-[11px]">
        កំណត់ថាតើអ្នកប្រើប្រាស់អាចបង្កើត Account ដោយខ្លួនឯង ឬតម្រូវឱ្យ Admin បង្កើតជូនជាផ្លូវការ៖
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Student Self Registration -->
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-700/80 flex items-center justify-between">
          <div>
            <div class="font-bold text-white text-xs">Student Self Registration</div>
            <div class="text-[11px] text-slate-400">អនុញ្ញាតឱ្យនិស្សិតចុះឈ្មោះបង្កើត Account ដោយខ្លួនឯង</div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="studentSelfRegistration" class="sr-only peer" />
            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
          </label>
        </div>

        <!-- Teacher Self Registration -->
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-700/80 flex items-center justify-between">
          <div>
            <div class="font-bold text-white text-xs">Teacher Self Registration</div>
            <div class="text-[11px] text-slate-400">អនុញ្ញាតឱ្យគ្រូចុះឈ្មោះខ្លួនឯង (ជាទូទៅបិទ OFF សម្រាប់សុវត្ថិភាព)</div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" v-model="teacherSelfRegistration" class="sr-only peer" />
            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
          </label>
        </div>
      </div>
    </div>

    <!-- 4. Notification Settings -->
    <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-6 shadow-xl space-y-4">
      <h3 class="text-sm font-bold text-indigo-300 flex items-center gap-2 border-b border-slate-700/60 pb-2">
        <span>🔔</span>
        <span>4. Notification Settings (ការកំណត់ការជូនដំណឹង)</span>
      </h3>
      <p class="text-slate-400 text-[11px]">
        Admin អាចបើក ឬបិទ (Enable / Disable) ប្រភេទ Notification តាមតម្រូវការជាក់ស្តែង៖
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <!-- Email Notifications -->
        <label class="p-3 rounded-xl bg-slate-900 border border-slate-700/80 flex items-center justify-between cursor-pointer">
          <div>
            <div class="font-bold text-slate-200">📧 Email Notifications</div>
            <div class="text-[10px] text-slate-400">ផ្ញើអ៊ីមែលជូនដំណឹងផ្លូវការ</div>
          </div>
          <input type="checkbox" v-model="emailNotifications" class="rounded border-slate-700 text-indigo-600" />
        </label>

        <!-- System Notifications -->
        <label class="p-3 rounded-xl bg-slate-900 border border-slate-700/80 flex items-center justify-between cursor-pointer">
          <div>
            <div class="font-bold text-slate-200">🔔 System Notifications</div>
            <div class="text-[10px] text-slate-400">ការជូនដំណឹងក្នុង Header Bell</div>
          </div>
          <input type="checkbox" v-model="systemNotifications" class="rounded border-slate-700 text-indigo-600" />
        </label>

        <!-- Assignment Notifications -->
        <label class="p-3 rounded-xl bg-slate-900 border border-slate-700/80 flex items-center justify-between cursor-pointer">
          <div>
            <div class="font-bold text-slate-200">⏰ Assignment Alerts</div>
            <div class="text-[10px] text-slate-400">ផុតកំណត់ និងដាក់ពិន្ទុកិច្ចការ</div>
          </div>
          <input type="checkbox" v-model="assignmentNotifications" class="rounded border-slate-700 text-indigo-600" />
        </label>

        <!-- Quiz Notifications -->
        <label class="p-3 rounded-xl bg-slate-900 border border-slate-700/80 flex items-center justify-between cursor-pointer">
          <div>
            <div class="font-bold text-slate-200">📝 Quiz Notifications</div>
            <div class="text-[10px] text-slate-400">ពេលគ្រូដាក់ Quiz ថ្មី</div>
          </div>
          <input type="checkbox" v-model="quizNotifications" class="rounded border-slate-700 text-indigo-600" />
        </label>

        <!-- AI Notifications -->
        <label class="p-3 rounded-xl bg-slate-900 border border-slate-700/80 flex items-center justify-between cursor-pointer">
          <div>
            <div class="font-bold text-slate-200">🤖 AI Notifications</div>
            <div class="text-[10px] text-slate-400">អនុសាសន៍ & At-Risk alerts</div>
          </div>
          <input type="checkbox" v-model="aiNotifications" class="rounded border-slate-700 text-indigo-600" />
        </label>

        <!-- Maintenance Mode Toggle -->
        <label class="p-3 rounded-xl bg-slate-900 border border-slate-700/80 flex items-center justify-between cursor-pointer" :class="maintenanceMode ? 'border-amber-500/60 bg-amber-950/20' : ''">
          <div>
            <div class="font-bold text-slate-200">⚠️ Maintenance Mode</div>
            <div class="text-[10px] text-slate-400">ផ្អាកសេវាសម្រាប់ Student</div>
          </div>
          <input type="checkbox" v-model="maintenanceMode" class="rounded border-slate-700 text-amber-500" />
        </label>
      </div>
    </div>

    <!-- 5. Save Settings / Footer Actions -->
    <div class="flex items-center justify-between bg-slate-900/90 border border-slate-700/70 p-4 rounded-2xl shadow-xl">
      <button
        @click="handleReset"
        type="button"
        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl transition text-xs font-semibold"
      >
        Reset to Defaults
      </button>

      <div class="flex items-center gap-2">
        <button
          @click="emit('notify', 'Changes cancelled.')"
          type="button"
          class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 rounded-xl transition text-xs font-medium"
        >
          Cancel
        </button>
        <button
          @click="handleSave"
          :disabled="isSaving"
          type="button"
          class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/20 transition text-xs flex items-center gap-2"
        >
          <span v-if="isSaving" class="animate-spin">🌀</span>
          <span>Save Changes</span>
        </button>
      </div>
    </div>
  </div>
</template>
