<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps<{
  initialCode?: string
  status?: string // 'valid', 'revoked', 'not_found', 'pending'
  is_student_id?: boolean
  student_name?: string
  student_name_kh?: string
  student_id?: string
  course_name?: string
  major_name?: string
  faculty_name?: string
  grade?: string
  score?: number | string
  issued_at?: string
  certificate_number?: string
  revoked_at?: string
  revocation_reason?: string
  status_label?: string
  institution?: string
  checked_at?: string
}>()

const searchCode = ref(props.initialCode || props.certificate_number || '')
const isScanning = ref(false)
const showImageModal = ref(false)

const handleVerify = () => {
  if (!searchCode.value.trim()) return
  router.get(`/certificate/verify/${searchCode.value.trim()}`, {}, { preserveState: false })
}

const toggleScanner = () => {
  isScanning.value = !isScanning.value
  if (isScanning.value) {
    // Simulate instant camera scan fill
    setTimeout(() => {
      searchCode.value = 'ELMS-2025-000451'
      isScanning.value = false
      handleVerify()
    }, 1800)
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-indigo-500/30">
    <!-- Header Navbar -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-xl sticky top-0 z-50">
      <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-cyan-500 to-emerald-400 flex items-center justify-center text-white font-black text-xl shadow-lg shadow-indigo-500/20">
            🎓
          </div>
          <div>
            <h1 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
              E.LMS Certification Portal
              <span class="px-2 py-0.5 text-[10px] font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-full">
                Official Verification
              </span>
            </h1>
            <p class="text-xs text-slate-400">Public Verification System · No Login Required</p>
          </div>
        </div>

        <a href="/login" class="text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 px-3 py-1.5 rounded-lg transition border border-slate-700">
          Admin Portal →
        </a>
      </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-3xl w-full mx-auto px-4 py-10 space-y-8">
      <!-- Title & Intro -->
      <div class="text-center space-y-3">
        <h2 class="text-3xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-indigo-300 via-cyan-200 to-white tracking-tight">
          🔍 Verify a Certificate
        </h2>
        <p class="text-sm text-slate-400 max-w-md mx-auto">
          Enter the unique Certificate ID (e.g., <code class="text-indigo-400 font-mono">ELMS-2025-000451</code>) or scan the QR code printed on the official document.
        </p>
      </div>

      <!-- Search & QR Bar Card -->
      <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 shadow-2xl space-y-4">
        <form @submit.prevent="handleVerify" class="flex flex-col sm:flex-row gap-3">
          <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
              🏷️
            </div>
            <input
              v-model="searchCode"
              type="text"
              placeholder="Enter Certificate ID: ELMS-2025-000451"
              class="w-full pl-10 pr-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-sm font-mono text-white placeholder:text-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            />
          </div>

          <button
            type="submit"
            class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            Verify Now
          </button>
        </form>

        <div class="flex items-center justify-center gap-2 pt-2">
          <span class="text-xs text-slate-500">or</span>
          <button
            @click="toggleScanner"
            type="button"
            class="inline-flex items-center gap-2 text-xs font-semibold text-cyan-400 hover:text-cyan-300 bg-cyan-500/10 hover:bg-cyan-500/20 border border-cyan-500/20 px-3.5 py-1.5 rounded-lg transition"
          >
            📱 {{ isScanning ? 'Cancel Camera Scanner' : 'Scan QR Code' }}
          </button>
        </div>

        <!-- Simulated Camera Scan Viewfinder -->
        <div v-if="isScanning" class="relative bg-slate-950 border-2 border-dashed border-cyan-500/50 rounded-xl p-8 text-center space-y-3 overflow-hidden">
          <div class="w-16 h-16 border-4 border-cyan-400 border-t-transparent rounded-full animate-spin mx-auto"></div>
          <p class="text-xs font-mono text-cyan-300">Point your camera at the certificate QR code...</p>
          <span class="text-[10px] text-slate-500 animate-pulse">Detecting E.LMS Certificate Matrix...</span>
        </div>
      </div>

      <!-- Verification Results States -->
      <div v-if="status" class="transition-all duration-300">
        <!-- 🟢 VALID STATE -->
        <div v-if="status === 'valid'" class="bg-slate-900 border-2 border-emerald-500/40 rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl shadow-emerald-500/5 relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

          <!-- Header status banner -->
          <div class="flex items-center gap-4 border-b border-slate-800 pb-5">
            <div class="w-14 h-14 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-2xl shadow-inner text-emerald-400 shrink-0">
              ✅
            </div>
            <div>
              <div class="inline-flex items-center gap-2 px-2.5 py-0.5 text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-full uppercase tracking-wider">
                {{ is_student_id ? 'STUDENT ID IS VERIFIED & VALID' : 'CERTIFICATE IS VALID' }}
              </div>
              <h3 class="text-xl font-extrabold text-white mt-1">
                {{ is_student_id ? 'Official Student Credentials Verified' : 'Official Academic Credentials Verified' }}
              </h3>
              <p class="text-xs text-slate-400">{{ institution || 'Saint Paul Institute (វិទ្យាស្ថាន សន្តប៉ូល) · SPI E-LMS' }}</p>
            </div>
          </div>

          <!-- Details Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
              <span class="text-slate-400 block mb-0.5">
                {{ is_student_id ? 'Official Student Name (ឈ្មោះនិស្សិត):' : 'Certificate Holder:' }}
              </span>
              <span class="text-sm font-bold text-white">
                {{ student_name || 'kosal sensok' }}
                <span v-if="student_name_kh" class="text-xs text-indigo-400 font-medium ml-1">({{ student_name_kh }})</span>
              </span>
            </div>

            <div class="bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
              <span class="text-slate-400 block mb-0.5">Student ID (អត្តលេខនិស្សិត):</span>
              <span class="text-sm font-mono font-bold text-emerald-400">{{ student_id || certificate_number || 'STU-2024-089' }}</span>
            </div>

            <div class="bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
              <span class="text-slate-400 block mb-0.5">
                {{ is_student_id ? 'Academic Program (កម្មវិធីសិក្សា):' : 'Course Completed:' }}
              </span>
              <span class="text-sm font-bold text-indigo-300">{{ course_name || 'Information Technology & Software Engineering' }}</span>
            </div>

            <div class="bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
              <span class="text-slate-400 block mb-0.5">Major / Faculty:</span>
              <span class="text-sm font-semibold text-slate-200">
                {{ major_name || 'IT & Networking' }}
                <span v-if="faculty_name" class="block text-[11px] text-slate-400">{{ faculty_name }}</span>
              </span>
            </div>

            <div class="bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
              <span class="text-slate-400 block mb-0.5">Academic Status:</span>
              <span class="text-sm font-bold text-emerald-400">{{ status_label || (grade ? 'Grade: ' + grade : '🟢 Active Enrolled Student') }}</span>
            </div>

            <div class="bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
              <span class="text-slate-400 block mb-0.5">Registered / Issued On:</span>
              <span class="text-sm font-semibold text-slate-200">{{ issued_at || 'Academic Year 2024–2026' }}</span>
            </div>

            <div class="bg-slate-950/60 p-3.5 rounded-xl border border-slate-800 sm:col-span-2">
              <span class="text-slate-400 block mb-0.5">
                {{ is_student_id ? 'Verification ID Code:' : 'Certificate ID:' }}
              </span>
              <span class="text-sm font-mono font-bold text-cyan-400">{{ certificate_number || initialCode || 'STU-2024-089' }}</span>
            </div>
          </div>

          <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
            <button
              @click="showImageModal = true"
              type="button"
              class="w-full sm:w-auto px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs rounded-xl border border-slate-700 transition flex items-center justify-center gap-2"
            >
              📥 {{ is_student_id ? 'View Digital Student ID Card' : 'View Certificate Document' }}
            </button>
            <span class="text-[11px] text-slate-500">
              Verification checked on {{ checked_at || 'Today' }} (logged for security)
            </span>
          </div>
        </div>

        <!-- 🔴 REVOKED STATE -->
        <div v-else-if="status === 'revoked'" class="bg-slate-900 border-2 border-red-500/50 rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl shadow-red-500/10">
          <div class="flex items-center gap-4 border-b border-slate-800 pb-5">
            <div class="w-14 h-14 rounded-2xl bg-red-500/15 border border-red-500/30 flex items-center justify-center text-2xl text-red-400 shrink-0">
              🔴
            </div>
            <div>
              <div class="inline-flex items-center gap-2 px-2.5 py-0.5 text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/30 rounded-full uppercase tracking-wider">
                CERTIFICATE REVOKED
              </div>
              <h3 class="text-xl font-extrabold text-white mt-1">This Certificate Has Been Revoked</h3>
              <p class="text-xs text-slate-400">This document is no longer recognized as valid by E.LMS.</p>
            </div>
          </div>

          <div class="bg-slate-950 p-4 rounded-xl border border-red-900/40 text-xs space-y-2 text-slate-300">
            <p><strong>Certificate ID:</strong> <code class="text-red-400 font-mono">{{ certificate_number }}</code></p>
            <p><strong>Revocation Reason:</strong> <span class="text-red-300 font-medium">{{ revocation_reason || 'Plagiarism detected' }}</span></p>
            <p v-if="revoked_at"><strong>Revoked Date:</strong> <span>{{ revoked_at }}</span></p>
          </div>
        </div>

        <!-- ⚪ NOT FOUND STATE -->
        <div v-else-if="status === 'not_found'" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 space-y-4 text-center">
          <div class="w-14 h-14 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-2xl text-slate-400 mx-auto">
            ⚪
          </div>
          <h3 class="text-xl font-bold text-white">Certificate Not Found</h3>
          <p class="text-xs text-slate-400 max-w-md mx-auto">
            No record matches Certificate ID <code class="text-yellow-400 font-mono">{{ certificate_number || searchCode }}</code>. Please check the spelling or contact academic services to report possible fraudulent documents.
          </p>
        </div>
      </div>
    </main>

    <!-- Certificate Preview Modal -->
    <div v-if="showImageModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
      <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-6 space-y-4 relative">
        <button @click="showImageModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-white font-bold text-lg">✕</button>
        <h4 class="text-base font-bold text-white flex items-center gap-2">
          <span>{{ is_student_id ? '🪪 Official Digital Student ID Card' : '🏅 Certificate Document Preview' }}</span>
        </h4>
        
        <!-- Student ID Card Preview -->
        <div v-if="is_student_id" class="max-w-md mx-auto relative rounded-3xl p-6 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 border-2 border-indigo-500/40 shadow-2xl text-white space-y-5 overflow-hidden">
          <div class="flex items-center justify-between border-b border-indigo-800/50 pb-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-lg font-black shadow-md">
                🎓
              </div>
              <div>
                <p class="text-[11px] font-black tracking-wider text-indigo-200 uppercase">SAINT PAUL INSTITUTE</p>
                <p class="text-[9px] text-slate-400 font-medium">វិទ្យាស្ថាន សន្តប៉ូល • SPI E-LMS</p>
              </div>
            </div>
            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[9px] font-bold uppercase tracking-wider">
              Verified ID
            </span>
          </div>

          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-xl flex items-center justify-center shadow-lg ring-2 ring-indigo-400/40 shrink-0">
              {{ (student_name || 'K').charAt(0) }}
            </div>
            <div class="space-y-1">
              <h4 class="text-base font-black text-white leading-tight">{{ student_name || 'kosal sensok' }}</h4>
              <p class="text-xs text-indigo-300 font-extrabold font-mono">{{ student_id || certificate_number || 'STU-2024-089' }}</p>
              <p class="text-[11px] text-slate-300 font-medium">{{ major_name || 'IT & Networking' }}</p>
              <p class="text-[10px] text-slate-400">{{ faculty_name || 'Faculty of Science & Information Technology' }}</p>
            </div>
          </div>

          <div class="pt-3 border-t border-indigo-800/50 flex items-end justify-between gap-4 text-[10px]">
            <div>
              <span class="text-slate-400 block font-semibold uppercase">Status:</span>
              <span class="text-emerald-400 font-extrabold">🟢 Active Enrolled Student</span>
            </div>
            <span class="text-slate-400 font-mono">{{ checked_at }}</span>
          </div>
        </div>

        <!-- Live Render Anatomy Mockup for Certificates -->
        <div v-else class="bg-slate-950 p-6 rounded-xl border border-slate-800 text-center space-y-4 font-serif text-slate-200">
          <div class="flex justify-between items-center text-xs text-slate-400 font-sans">
            <span>🎓 Saint Paul Institute</span>
            <span>🏛️ Official Academic Seal</span>
          </div>

          <p class="text-xs tracking-widest text-indigo-400 uppercase font-sans">Certificate of Completion</p>
          <p class="text-[11px] text-slate-400 italic">This is to certify that</p>
          <h3 class="text-2xl font-bold text-white font-sans">{{ student_name || 'kosal sensok' }}</h3>
          <p class="text-xs text-slate-400">has successfully completed the course</p>
          <p class="text-lg font-bold text-cyan-300 font-sans">{{ course_name || 'Web Development Fundamentals' }}</p>
          <p class="text-xs text-slate-400">Grade: {{ grade || 'A' }} · Score: {{ score || 92 }}%</p>

          <div class="pt-6 border-t border-slate-800 flex justify-between items-center text-[10px] font-sans text-slate-400">
            <div>
              <p class="font-mono text-cyan-400">{{ certificate_number || 'SPI-CERT-2025-00124' }}</p>
              <p>Issued: {{ issued_at || '16 June 2025' }}</p>
            </div>
            <div class="w-12 h-12 bg-white text-slate-950 p-1 rounded font-bold text-[8px] flex items-center justify-center">
              [QR VERIFIED]
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
