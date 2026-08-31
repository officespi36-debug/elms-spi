<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface VerificationHistoryItem {
  date: string
  verified_by: string
  ip_address: string
  location: string
  result: string
}

interface VerificationPayload {
  status: 'valid' | 'invalid' | 'not_found' | 'revoked'
  certificate_id?: string
  student_name?: string
  course_name?: string
  issuer?: string
  completion_date?: string
  issue_date?: string
  final_score?: string
  grade?: string
  certificate_status?: string
  verification_count?: number
  verified_on?: string
  verification_url?: string
  topics?: string[]
  director_name?: string
  director_title?: string
  message?: string
  history?: VerificationHistoryItem[]
}

const props = defineProps<{
  verificationData?: VerificationPayload
  initialQuery?: string
  initialTab?: string
}>()

// Active Verification Tab: 'id' | 'link' | 'qr'
const activeTab = ref<'id' | 'link' | 'qr'>((props.initialTab as any) || 'id')

// Input fields
const certificateIdInput = ref<string>(props.initialQuery || 'SPI-CERT-2025-00124')
const verificationLinkInput = ref<string>('https://spilms.tech/verify/SPI-CERT-2025-00124')
const qrUploadFile = ref<File | null>(null)

// UI States
const isLoading = ref<boolean>(false)
const isShareModalOpen = ref<boolean>(false)
const copyFeedback = ref<string>('')
const downloadFeedback = ref<string>('')

// Default Sample Data (matching screenshot pixel-for-pixel)
const defaultData: VerificationPayload = {
  status: 'valid',
  certificate_id: 'SPI-CERT-2025-00124',
  student_name: 'Sok Pisey',
  course_name: 'Web Development Fundamentals',
  issuer: 'Saint Paul Institute',
  completion_date: 'May 28, 2025',
  issue_date: 'May 28, 2025',
  final_score: '92%',
  grade: 'A',
  certificate_status: 'Valid',
  verification_count: 12,
  verified_on: 'June 5, 2025 at 10:30 AM',
  verification_url: 'https://spilms.tech/verify/SPI-CERT-2025-00124',
  topics: [
    'HTML',
    'CSS',
    'JavaScript',
    'Responsive Design',
    'Modern Web Development Practices',
  ],
  director_name: 'Dr. John Smith',
  director_title: 'Director of Education',
  history: [
    {
      date: 'June 5, 2025 10:30 AM',
      verified_by: 'Public Verification',
      ip_address: '119.75.xxx.xxx',
      location: 'Phnom Penh, Cambodia',
      result: 'VALID',
    },
    {
      date: 'June 2, 2025 04:15 PM',
      verified_by: 'Public Verification',
      ip_address: '203.144.xxx.xxx',
      location: 'Phnom Penh, Cambodia',
      result: 'VALID',
    },
    {
      date: 'May 29, 2025 09:10 AM',
      verified_by: 'Public Verification',
      ip_address: '119.75.xxx.xxx',
      location: 'Phnom Penh, Cambodia',
      result: 'VALID',
    }
  ]
}

const currentResult = computed<VerificationPayload>(() => props.verificationData || defaultData)

// Execute verification
const handleVerify = (queryValue?: string) => {
  let query = queryValue
  if (!query) {
    if (activeTab.value === 'id') query = certificateIdInput.value
    else if (activeTab.value === 'link') query = verificationLinkInput.value
    else query = 'SPI-CERT-2025-00124'
  }

  if (!query?.trim()) return

  isLoading.value = true
  router.get('/student/certificates/verify', {
    q: query.trim(),
    tab: activeTab.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      isLoading.value = false
    }
  })
}

// QR Upload Simulation
const onQrFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    qrUploadFile.value = target.files[0]
    isLoading.value = true
    setTimeout(() => {
      certificateIdInput.value = 'SPI-CERT-2025-00124'
      activeTab.value = 'id'
      handleVerify('SPI-CERT-2025-00124')
    }, 1200)
  }
}

// Copy verification link
const copyVerificationLink = () => {
  const url = currentResult.value.verification_url || 'https://spilms.tech/verify/SPI-CERT-2025-00124'
  navigator.clipboard.writeText(url)
  copyFeedback.value = 'Verification link copied successfully!'
  setTimeout(() => copyFeedback.value = '', 3000)
}

// Download PDF flow
const downloadCertificate = () => {
  downloadFeedback.value = 'Generating official PDF certificate...'
  setTimeout(() => {
    downloadFeedback.value = 'Certificate downloaded successfully.'
    // Trigger virtual file download
    const element = document.createElement('a')
    const file = new Blob([`SPI Official Certificate: ${currentResult.value.certificate_id} - ${currentResult.value.student_name}`], {type: 'text/plain'})
    element.href = URL.createObjectURL(file)
    element.download = `${currentResult.value.certificate_id}.pdf`
    document.body.appendChild(element)
    element.click()
    document.body.removeChild(element)
    setTimeout(() => downloadFeedback.value = '', 4000)
  }, 1200)
}

// Print Certificate flow
const printCertificate = () => {
  window.print()
}

// Reset / Try Again
const resetSearch = () => {
  certificateIdInput.value = 'SPI-CERT-2025-00124'
  verificationLinkInput.value = 'https://spilms.tech/verify/SPI-CERT-2025-00124'
  handleVerify('SPI-CERT-2025-00124')
}
</script>

<template>
  <StudentLayout title="Certificate Verification — SPI E-Learning Platform">
    <div class="space-y-6 max-w-7xl mx-auto pb-12 print:p-0 print:m-0">

      <!-- ================= 1. PAGE HEADER (Hidden on Print) ================= -->
      <div class="print:hidden">
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
          <span>Certificate Verification</span>
          <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-600 dark:text-purple-400 text-lg">🛡️</span>
        </h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
          Verify the authenticity of any certificate issued by SPI E-Learning Platform
        </p>
      </div>

      <!-- FEEDBACK ALERTS -->
      <div v-if="downloadFeedback" class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-xs text-emerald-700 dark:text-emerald-300 font-bold flex items-center gap-2 print:hidden animate-in fade-in">
        <span>✅</span>
        <span>{{ downloadFeedback }}</span>
      </div>
      <div v-if="copyFeedback" class="p-3 bg-purple-500/10 border border-purple-500/30 rounded-2xl text-xs text-purple-700 dark:text-purple-300 font-bold flex items-center gap-2 print:hidden animate-in fade-in">
        <span>📋</span>
        <span>{{ copyFeedback }}</span>
      </div>

      <!-- ================= 2. MAIN LAYOUT (DESKTOP 65% LEFT / 35% RIGHT) ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT COLUMN (~65% / 8 of 12) ================= -->
        <div class="lg:col-span-8 space-y-6">

          <!-- CARD 1: VERIFICATION METHODS & FORM (Hidden on Print) -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-xl space-y-5 print:hidden">
            
            <!-- 3 Tabs -->
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800/80 pb-3 overflow-x-auto">
              <button
                @click="activeTab = 'id'"
                :class="[
                  activeTab === 'id'
                    ? 'bg-purple-600/10 dark:bg-purple-600/20 text-purple-700 dark:text-purple-300 border-purple-500/40 shadow-xs'
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white border-transparent',
                  'px-4 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer whitespace-nowrap'
                ]"
              >
                Certificate ID
              </button>

              <button
                @click="activeTab = 'link'"
                :class="[
                  activeTab === 'link'
                    ? 'bg-purple-600/10 dark:bg-purple-600/20 text-purple-700 dark:text-purple-300 border-purple-500/40 shadow-xs'
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white border-transparent',
                  'px-4 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer whitespace-nowrap'
                ]"
              >
                Verification Link
              </button>

              <button
                @click="activeTab = 'qr'"
                :class="[
                  activeTab === 'qr'
                    ? 'bg-purple-600/10 dark:bg-purple-600/20 text-purple-700 dark:text-purple-300 border-purple-500/40 shadow-xs'
                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white border-transparent',
                  'px-4 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer whitespace-nowrap'
                ]"
              >
                QR Code Scan
              </button>
            </div>

            <!-- TAB 1: CERTIFICATE ID FORM -->
            <div v-if="activeTab === 'id'" class="space-y-3">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Enter Certificate ID</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Enter the certificate ID exactly as it appears on the certificate</p>
              </div>

              <div class="flex flex-col sm:flex-row items-center gap-3 pt-1">
                <div class="relative flex-1 w-full">
                  <input
                    type="text"
                    v-model="certificateIdInput"
                    @keyup.enter="handleVerify(certificateIdInput)"
                    placeholder="e.g., SPI-CERT-2025-00124"
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-2xl px-4 py-2.5 text-xs text-slate-900 dark:text-white font-mono placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-purple-500 transition-colors shadow-xs"
                  />
                </div>

                <button
                  @click="handleVerify(certificateIdInput)"
                  :disabled="isLoading"
                  class="w-full sm:w-auto px-6 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/20 flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-95 disabled:opacity-50"
                >
                  <span v-if="isLoading" class="animate-spin text-sm">⏳</span>
                  <span v-else>🔍</span>
                  <span>{{ isLoading ? 'Verifying Certificate...' : 'Verify Certificate' }}</span>
                </button>
              </div>

              <p class="text-[11px] text-slate-500 dark:text-slate-500 flex items-center gap-1">
                <span>Certificate ID format: SPI-CERT-YYYY-XXXXXX</span>
                <span class="cursor-help" title="Format standard">ⓘ</span>
              </p>
            </div>

            <!-- TAB 2: VERIFICATION LINK FORM -->
            <div v-else-if="activeTab === 'link'" class="space-y-3">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Paste Certificate Verification Link</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Enter the full verification URL from the certificate</p>
              </div>

              <div class="flex flex-col sm:flex-row items-center gap-3 pt-1">
                <div class="relative flex-1 w-full">
                  <input
                    type="url"
                    v-model="verificationLinkInput"
                    @keyup.enter="handleVerify(verificationLinkInput)"
                    placeholder="https://spilms.tech/verify/SPI-CERT-2025-00124"
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-2xl px-4 py-2.5 text-xs text-slate-900 dark:text-white font-mono placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-purple-500 transition-colors shadow-xs"
                  />
                </div>

                <button
                  @click="handleVerify(verificationLinkInput)"
                  :disabled="isLoading"
                  class="w-full sm:w-auto px-6 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50"
                >
                  <span v-if="isLoading" class="animate-spin text-sm">⏳</span>
                  <span v-else>🔍</span>
                  <span>{{ isLoading ? 'Verifying...' : 'Verify Link' }}</span>
                </button>
              </div>
            </div>

            <!-- TAB 3: QR CODE SCAN FORM -->
            <div v-else-if="activeTab === 'qr'" class="space-y-4">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Scan Certificate QR Code</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Upload or scan the QR code from a certificate to verify its authenticity</p>
              </div>

              <div class="border-2 border-dashed border-slate-200 dark:border-slate-700/80 hover:border-purple-500/50 rounded-2xl p-6 text-center space-y-3 bg-slate-50 dark:bg-slate-900/50 transition-colors">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 dark:bg-purple-600/20 border border-purple-500/20 dark:border-purple-500/30 text-purple-600 dark:text-purple-300 flex items-center justify-center text-xl mx-auto">
                  📷
                </div>
                <div>
                  <p class="text-xs font-bold text-slate-900 dark:text-white">Upload Certificate QR Image</p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Supports PNG, JPG, or PDF snippets</p>
                </div>

                <div class="flex items-center justify-center gap-3 pt-1">
                  <label class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md cursor-pointer transition-colors">
                    <span>Select QR Image</span>
                    <input type="file" accept="image/*" class="hidden" @change="onQrFileChange" />
                  </label>
                  <button
                    @click="handleVerify('SPI-CERT-2025-00124')"
                    class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer"
                  >
                    Simulate Camera Scan
                  </button>
                </div>
              </div>
            </div>

          </div>

          <!-- LOADING STATE -->
          <div v-if="isLoading" class="bg-white dark:bg-[#0F172A]/90 border border-purple-500/40 rounded-3xl p-10 text-center space-y-3 shadow-sm dark:shadow-xl">
            <div class="w-12 h-12 rounded-full border-4 border-purple-500/20 border-t-purple-500 animate-spin mx-auto"></div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Verifying Certificate Authenticity...</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400">Querying official blockchain records and registry database</p>
          </div>

          <!-- ERROR STATE: NOT FOUND / INVALID -->
          <div v-else-if="currentResult.status === 'not_found' || currentResult.status === 'invalid' || currentResult.status === 'revoked'" class="bg-white dark:bg-[#0F172A]/90 border border-rose-500/40 rounded-3xl p-6 shadow-sm dark:shadow-2xl space-y-4">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 rounded-2xl bg-rose-500/10 dark:bg-rose-600/20 border border-rose-500/40 text-rose-600 dark:text-rose-400 flex items-center justify-center text-2xl shrink-0">
                ⚠️
              </div>
              <div class="space-y-1">
                <h3 class="text-lg font-black text-rose-600 dark:text-rose-400">
                  {{ currentResult.status === 'not_found' ? 'Certificate Not Found' : 'Certificate Not Valid / Revoked' }}
                </h3>
                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                  {{ currentResult.message || 'We could not verify the authenticity of the certificate ID provided.' }}
                </p>
              </div>
            </div>

            <div class="p-4 bg-slate-50 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-400 space-y-1">
              <p class="font-bold text-slate-900 dark:text-white">Possible Reasons:</p>
              <p>• Certificate ID was mistyped or does not exist</p>
              <p>• Certificate has been officially revoked or expired</p>
              <p>• Verification QR code or link was altered</p>
            </div>

            <div class="flex items-center gap-3 pt-1">
              <button
                @click="resetSearch"
                class="px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md cursor-pointer"
              >
                Try Sample Specimen
              </button>
              <a
                href="mailto:support@spilms.tech"
                class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700 cursor-pointer"
              >
                Contact Support
              </a>
            </div>
          </div>

          <!-- CARD 2: VALID CERTIFICATE PREVIEW SECTION -->
          <div v-else class="space-y-4">
            
            <div class="flex items-center justify-between print:hidden">
              <h2 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Certificate Preview</h2>
            </div>

            <!-- ================= ORNAMENTAL CERTIFICATE CANVAS ================= -->
            <div class="relative w-full aspect-[16/11] bg-[#FDFBF7] text-slate-900 rounded-3xl p-6 sm:p-8 shadow-2xl border-4 border-[#C5A059] flex flex-col justify-between overflow-hidden print:border-none print:shadow-none print:aspect-auto">
              
              <!-- Ornamental Outer & Inner Borders -->
              <div class="absolute inset-2 border-2 border-[#C5A059]/60 rounded-2xl pointer-events-none"></div>
              <div class="absolute inset-3.5 border border-[#C5A059]/30 rounded-xl pointer-events-none"></div>

              <!-- Top Right Green Shield Ribbon Badge (Matching Screenshot) -->
              <div class="absolute top-0 right-8 w-12 h-14 bg-gradient-to-b from-emerald-600 to-emerald-700 shadow-lg flex flex-col items-center justify-center text-white rounded-b-lg z-20">
                <span class="text-base">🛡️</span>
                <span class="text-[7px] font-black uppercase tracking-wider">VALID</span>
              </div>

              <!-- Header Branding -->
              <div class="text-center pt-2 space-y-1 relative z-10">
                <p class="text-[10px] sm:text-xs font-black tracking-[0.25em] text-[#1E293B] uppercase font-serif">
                  SAINT PAUL INSTITUTE
                </p>
                <p class="text-[7.5px] sm:text-[9px] font-bold tracking-[0.2em] text-[#64748B] uppercase">
                  E-LEARNING PLATFORM
                </p>
                <h3 class="text-xl sm:text-3xl font-black font-serif text-[#0F172A] tracking-wide pt-1">
                  CERTIFICATE
                </h3>
                <div class="flex items-center justify-center gap-3">
                  <div class="w-12 h-0.5 bg-[#C5A059]"></div>
                  <span class="text-[8.5px] sm:text-[10px] tracking-[0.2em] uppercase font-serif text-[#C5A059] font-bold">OF COMPLETION</span>
                  <div class="w-12 h-0.5 bg-[#C5A059]"></div>
                </div>
              </div>

              <!-- Certificate Recipient & Course -->
              <div class="text-center my-auto space-y-1.5 relative z-10">
                <p class="text-[9px] sm:text-xs italic font-serif text-[#64748B]">This is to certify that</p>
                <h4 class="text-2xl sm:text-4xl font-black font-serif text-[#0F172A] tracking-tight">
                  {{ currentResult.student_name }}
                </h4>
                <p class="text-[8.5px] sm:text-[11px] italic font-serif text-[#64748B]">has successfully completed the course</p>
                <p class="text-base sm:text-2xl font-black font-serif text-indigo-950 tracking-tight">
                  {{ currentResult.course_name }}
                </p>
                <p class="text-[7.5px] sm:text-[10px] text-[#64748B] max-w-md mx-auto leading-tight">
                  This course covered essential topics in HTML, CSS, JavaScript, responsive design, and modern web development practices.
                </p>
              </div>

              <!-- Certificate Footer with Seal & Signatures -->
              <div class="flex items-end justify-between px-2 sm:px-6 pt-3 relative z-10 text-center">
                
                <!-- Date Left -->
                <div class="text-left space-y-0.5">
                  <p class="text-[7.5px] sm:text-[9px] text-[#64748B] font-serif">Completion Date</p>
                  <p class="text-[9px] sm:text-xs font-bold font-serif text-[#0F172A]">{{ currentResult.completion_date }}</p>
                </div>

                <!-- Golden Seal Center -->
                <div class="flex flex-col items-center justify-center">
                  <div class="w-12 sm:w-16 h-12 sm:h-16 rounded-full bg-gradient-to-br from-[#E2B755] via-[#C5A059] to-[#8C6B2D] border-2 border-white shadow-xl flex flex-col items-center justify-center text-slate-950 font-serif font-black text-[9px] sm:text-xs relative">
                    <span>SPI</span>
                    <span class="text-[6px] tracking-tighter uppercase font-sans font-bold">EST. 2009</span>
                    <!-- Ribbon tails -->
                    <div class="absolute -bottom-2 w-4 h-3 bg-blue-900 -z-10 transform rotate-12"></div>
                    <div class="absolute -bottom-2 w-4 h-3 bg-blue-900 -z-10 transform -rotate-12"></div>
                  </div>
                </div>

                <!-- Signature Right -->
                <div class="text-right space-y-0.5">
                  <p class="font-serif italic text-xs sm:text-sm text-slate-800 font-bold border-b border-slate-400 pb-0.5">
                    {{ currentResult.director_name }}
                  </p>
                  <p class="text-[7.5px] sm:text-[9px] text-[#64748B] font-serif">{{ currentResult.director_title }}</p>
                </div>

              </div>

            </div>

            <!-- ACTION BUTTONS BELOW PREVIEW (Hidden on Print) -->
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2 print:hidden">
              <button
                @click="downloadCertificate"
                class="px-5 py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-900 dark:text-white font-bold text-xs border border-slate-200 dark:border-slate-700 flex items-center gap-2 shadow-xs dark:shadow-md transition-colors cursor-pointer"
              >
                <span>📥</span>
                <span>Download Certificate</span>
              </button>

              <button
                @click="isShareModalOpen = true"
                class="px-5 py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-900 dark:text-white font-bold text-xs border border-slate-200 dark:border-slate-700 flex items-center gap-2 shadow-xs dark:shadow-md transition-colors cursor-pointer"
              >
                <span>🔗</span>
                <span>Share Certificate</span>
              </button>

              <button
                @click="printCertificate"
                class="px-5 py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-900 dark:text-white font-bold text-xs border border-slate-200 dark:border-slate-700 flex items-center gap-2 shadow-xs dark:shadow-md transition-colors cursor-pointer"
              >
                <span>🖨️</span>
                <span>Print Certificate</span>
              </button>
            </div>

          </div>

          <!-- CARD 3: NEED HELP? (BOTTOM LEFT) -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-2 print:hidden">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Need Help?</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400">
              If you have any questions about certificate verification, please contact our support team.
            </p>
            <div class="text-xs text-slate-700 dark:text-slate-300 font-mono pt-1 flex flex-wrap gap-4">
              <span>Email: <a href="mailto:support@spilms.tech" class="text-purple-600 dark:text-purple-400 hover:underline">support@spilms.tech</a></span>
              <span>Phone: <a href="tel:+85512345678" class="text-purple-600 dark:text-purple-400 hover:underline">+855 12 345 678</a></span>
            </div>
          </div>

        </div>

        <!-- ================= RIGHT COLUMN (~35% / 4 of 12) ================= -->
        <div class="lg:col-span-4 space-y-6 print:hidden">

          <!-- WIDGET 1: VERIFICATION STATUS (Glowing Green Shield) -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-emerald-500/40 rounded-3xl p-6 shadow-sm dark:shadow-2xl space-y-3 relative overflow-hidden">
            <div class="absolute -right-8 -top-8 w-28 h-28 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Verification Status</h3>
            </div>

            <div class="flex items-center gap-4 pt-1">
              <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/20 border border-emerald-500/30 dark:border-emerald-500/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-2xl shadow-lg shadow-emerald-950/10 shrink-0">
                🛡️
              </div>
              <div class="space-y-0.5">
                <h4 class="text-base font-black text-emerald-600 dark:text-emerald-400 tracking-wide">VALID CERTIFICATE</h4>
                <p class="text-xs text-slate-600 dark:text-slate-300">This certificate is authentic and valid.</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-400 font-mono pt-0.5">Verified on {{ currentResult.verified_on }}</p>
              </div>
            </div>
          </div>

          <!-- WIDGET 2: CERTIFICATE INFORMATION -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Certificate Information</h3>
            </div>

            <div class="space-y-2.5 text-xs">
              
              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                  <span>🆔</span>
                  <span>Certificate ID</span>
                </span>
                <span class="font-bold text-slate-900 dark:text-white font-mono text-[11px]">{{ currentResult.certificate_id }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                  <span>👤</span>
                  <span>Student Name</span>
                </span>
                <span class="font-bold text-slate-900 dark:text-white">{{ currentResult.student_name }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                  <span>🎓</span>
                  <span>Course Name</span>
                </span>
                <span class="font-bold text-slate-900 dark:text-white truncate max-w-[170px] text-right">{{ currentResult.course_name }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                  <span>🏛️</span>
                  <span>Issuer</span>
                </span>
                <span class="font-bold text-slate-900 dark:text-white">{{ currentResult.issuer }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                  <span>📅</span>
                  <span>Completion Date</span>
                </span>
                <span class="font-bold text-slate-900 dark:text-white font-mono text-[11px]">{{ currentResult.completion_date }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                  <span>🗓️</span>
                  <span>Issue Date</span>
                </span>
                <span class="font-bold text-slate-900 dark:text-white font-mono text-[11px]">{{ currentResult.issue_date }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                  <span>🛡️</span>
                  <span>Certificate Status</span>
                </span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">
                  {{ currentResult.certificate_status }}
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                  <span>👁️</span>
                  <span>Verification Count</span>
                </span>
                <span class="font-bold text-slate-900 dark:text-white font-mono text-[11px]">{{ currentResult.verification_count }} times</span>
              </div>

            </div>
          </div>

          <!-- WIDGET 3: VERIFICATION FEATURES -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Verification Features</h3>
            </div>

            <div class="space-y-3 text-xs">
              
              <!-- Feature 1 -->
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 dark:bg-emerald-600/20 border border-emerald-500/20 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-300 flex items-center justify-center text-sm shrink-0">
                  🛡️
                </div>
                <div>
                  <h4 class="font-bold text-slate-900 dark:text-white">Tamper Proof</h4>
                  <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-tight mt-0.5">This certificate is digitally signed and tamper-proof</p>
                </div>
              </div>

              <!-- Feature 2 -->
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-blue-500/10 dark:bg-blue-600/20 border border-blue-500/20 dark:border-blue-500/30 text-blue-600 dark:text-blue-300 flex items-center justify-center text-sm shrink-0">
                  🔒
                </div>
                <div>
                  <h4 class="font-bold text-slate-900 dark:text-white">Secure Verification</h4>
                  <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-tight mt-0.5">Verified using blockchain technology</p>
                </div>
              </div>

              <!-- Feature 3 -->
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-purple-500/10 dark:bg-purple-600/20 border border-purple-500/20 dark:border-purple-500/30 text-purple-600 dark:text-purple-300 flex items-center justify-center text-sm shrink-0">
                  🌐
                </div>
                <div>
                  <h4 class="font-bold text-slate-900 dark:text-white">Global Recognition</h4>
                  <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-tight mt-0.5">Accepted and recognized worldwide</p>
                </div>
              </div>

              <!-- Feature 4 -->
              <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 dark:bg-amber-600/20 border border-amber-500/20 dark:border-amber-500/30 text-amber-600 dark:text-amber-300 flex items-center justify-center text-sm shrink-0">
                  ⚡
                </div>
                <div>
                  <h4 class="font-bold text-slate-900 dark:text-white">Instant Verification</h4>
                  <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-tight mt-0.5">Real-time certificate authenticity check</p>
                </div>
              </div>

            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- ================= MODAL: SHARE CERTIFICATE ================= -->
    <div
      v-if="isShareModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl relative">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-xl">📢</span>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Share Certificate</h3>
          </div>
          <button
            @click="isShareModalOpen = false"
            class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs cursor-pointer"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <span class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold">Certificate Name</span>
            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ currentResult.course_name }}</p>
          </div>

          <div>
            <span class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold">Certificate ID</span>
            <p class="text-xs font-mono font-bold text-purple-600 dark:text-purple-300">{{ currentResult.certificate_id }}</p>
          </div>

          <!-- Copy Link -->
          <div class="space-y-1">
            <span class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold">Public Verification Link</span>
            <div class="flex items-center gap-2">
              <input
                type="text"
                readonly
                :value="currentResult.verification_url"
                class="flex-1 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-[11px] text-slate-700 dark:text-slate-300 font-mono"
              />
              <button
                @click="copyVerificationLink"
                class="px-3 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs whitespace-nowrap cursor-pointer"
              >
                Copy Link
              </button>
            </div>
          </div>

          <!-- Social Shortcuts -->
          <div class="pt-2">
            <span class="text-slate-500 dark:text-slate-400 text-[10px] uppercase font-bold block mb-2">Share on Social Media:</span>
            <div class="grid grid-cols-3 gap-2">
              <button
                @click="copyVerificationLink"
                class="py-2 px-3 rounded-xl bg-[#0088cc]/10 dark:bg-[#0088cc]/20 border border-[#0088cc]/30 dark:border-[#0088cc]/40 hover:bg-[#0088cc]/20 dark:hover:bg-[#0088cc]/30 text-sky-700 dark:text-sky-300 font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>✈️</span> Telegram
              </button>
              <button
                @click="copyVerificationLink"
                class="py-2 px-3 rounded-xl bg-[#1877f2]/10 dark:bg-[#1877f2]/20 border border-[#1877f2]/30 dark:border-[#1877f2]/40 hover:bg-[#1877f2]/20 dark:hover:bg-[#1877f2]/30 text-blue-700 dark:text-blue-300 font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>📘</span> Facebook
              </button>
              <button
                @click="copyVerificationLink"
                class="py-2 px-3 rounded-xl bg-purple-500/10 dark:bg-purple-500/20 border border-purple-500/30 dark:border-purple-500/40 hover:bg-purple-500/20 dark:hover:bg-purple-500/30 text-purple-700 dark:text-purple-300 font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>✉️</span> Email
              </button>
            </div>
          </div>
        </div>

        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-end">
          <button
            @click="isShareModalOpen = false"
            class="px-5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs cursor-pointer"
          >
            Close
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>

<style>
@media print {
  body {
    background: white !important;
    color: black !important;
  }
}
</style>
