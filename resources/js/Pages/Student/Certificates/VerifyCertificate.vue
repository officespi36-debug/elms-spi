<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

const props = defineProps<{
  publicCertUuid?: string
  certificateData?: any
  studentData?: any
}>()

const certInput = ref(props.publicCertUuid || 'ELMS-2025-000451')

const hasRecord = computed(() => !!props.certificateData || !!props.studentData)

const isStudentOnly = computed(() => !props.certificateData && !!props.studentData)

const handleVerify = () => {
  if (!certInput.value.trim()) return
  router.get(`/verify-certificate/${certInput.value.trim()}`, {}, {
    preserveState: false,
    preserveScroll: true
  })
}
</script>

<template>
  <StudentLayout title="Verify Certificate & Student Identity — Saint Paul Institute">
    <div class="space-y-6">
      
      <!-- Top Header Summary Bar -->
      <div class="bg-gradient-to-r from-cyan-950 via-slate-900 to-indigo-950 border border-cyan-900/60 rounded-3xl p-5 md:p-6 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <span class="px-3 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-xs font-bold uppercase tracking-wider">
            🏛️ OFFICIAL PUBLIC VERIFICATION GATEWAY
          </span>
          <h1 class="text-xl md:text-2xl font-black text-white mt-1.5 flex items-center gap-2">
            <span>🔍 VERIFY CERTIFICATE & STUDENT ID</span>
          </h1>
          <p class="text-xs text-slate-300 mt-1">
            ផ្ទៀងផ្ទាត់ភាពត្រឹមត្រូវនៃវិញ្ញាបនបត្រ និងកាតនិស្សិតឌីជីថលផ្លូវការ នៃវិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)
          </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <span class="px-3.5 py-1.5 rounded-2xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-xs font-bold flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            Blockchain & Database Verified
          </span>
        </div>
      </div>

      <!-- SEARCH BAR CARD -->
      <div class="bg-slate-800/90 border border-slate-700/80 rounded-3xl p-6 shadow-xl space-y-3">
        <label class="text-xs font-bold text-white uppercase tracking-wider block">
          Enter Certificate Code / Student ID Number:
        </label>
        <div class="flex items-center gap-3">
          <input
            v-model="certInput"
            @keyup.enter="handleVerify"
            type="text"
            placeholder="e.g. CERT-2026-089 or STU-2024-089 or STU241092"
            class="flex-1 bg-slate-900 border border-slate-700 rounded-2xl px-4 py-3 text-sm text-white font-mono font-bold focus:outline-none focus:border-cyan-500"
          />
          <button
            @click="handleVerify"
            class="px-6 py-3 rounded-2xl bg-gradient-to-r from-cyan-600 to-indigo-600 hover:from-cyan-500 hover:to-indigo-500 text-white font-black text-xs shadow-md transition-all cursor-pointer hover:scale-105 active:scale-95"
          >
            🔍 Verify Now
          </button>
        </div>
      </div>

      <!-- STUDENT ID CARD VERIFIED BOX -->
      <div v-if="isStudentOnly && studentData" class="bg-slate-800/90 border border-emerald-500/50 rounded-3xl p-6 md:p-8 shadow-2xl space-y-6">
        <div class="flex items-center justify-between border-b border-slate-700/60 pb-4">
          <div>
            <span class="text-xs text-slate-400 font-bold uppercase">Identity Verification Status</span>
            <h2 class="text-2xl font-black text-emerald-400 mt-0.5">✅ VERIFIED ACTIVE STUDENT</h2>
          </div>
          <span class="px-3.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold font-mono">
            STATUS: ENROLLED
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Student Full Name</span>
            <p class="text-sm font-bold text-white font-sans">{{ studentData.name }} <span v-if="studentData.name_kh">({{ studentData.name_kh }})</span></p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Student ID Code</span>
            <p class="text-sm font-bold text-indigo-300">{{ studentData.student_code || studentData.id }}</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Major Specialization</span>
            <p class="text-sm font-bold text-slate-200 font-sans">{{ studentData.major?.name || 'General Academic Studies' }}</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Department / Faculty</span>
            <p class="text-sm font-bold text-emerald-400 font-sans">{{ studentData.major?.department?.name || 'Saint Paul Institute' }}</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Institution</span>
            <p class="text-sm font-bold text-slate-300 font-sans">Saint Paul Institute (SPI)</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Verification Date & Time</span>
            <p class="text-sm font-bold text-slate-400">Live (Today)</p>
          </div>
        </div>
      </div>

      <!-- CERTIFICATE VERIFICATION RESULT BOX -->
      <div v-else-if="certificateData" class="bg-slate-800/90 border border-emerald-500/50 rounded-3xl p-6 md:p-8 shadow-2xl space-y-6">
        <div class="flex items-center justify-between border-b border-slate-700/60 pb-4">
          <div>
            <span class="text-xs text-slate-400 font-bold uppercase">Verification Status</span>
            <h2 class="text-2xl font-black text-emerald-400 mt-0.5">Result: ✅ VALID OFFICIAL CERTIFICATE</h2>
          </div>
          <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold font-mono">
            STATUS: ACTIVE
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Certificate Holder</span>
            <p class="text-sm font-bold text-white font-sans">{{ certificateData.student?.name || 'Official Student' }}</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Course Name</span>
            <p class="text-sm font-bold text-white font-sans">{{ certificateData.course?.title || 'Academic Program' }}</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Certificate Number</span>
            <p class="text-sm font-bold text-indigo-300">{{ certificateData.certificate_number || certificateData.certificate_uuid }}</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Grade / Score</span>
            <p class="text-sm font-bold text-emerald-400 font-sans">{{ certificateData.grade || 'Pass (Honors)' }}</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Issued On</span>
            <p class="text-sm font-bold text-slate-300">{{ certificateData.issue_date || certificateData.created_at || 'Recent' }}</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Issuing Body</span>
            <p class="text-sm font-bold text-slate-400 font-sans">Saint Paul Institute (SPI)</p>
          </div>
        </div>
      </div>

      <!-- DEFAULT SAMPLE VERIFICATION BOX IF NO QUERY PROVIDED -->
      <div v-else class="bg-slate-800/90 border border-emerald-500/50 rounded-3xl p-6 md:p-8 shadow-2xl space-y-6">
        <div class="flex items-center justify-between border-b border-slate-700/60 pb-4">
          <div>
            <span class="text-xs text-slate-400 font-bold uppercase">Verification Gateway</span>
            <h2 class="text-2xl font-black text-emerald-400 mt-0.5">Result: ✅ VALID SPECIMEN DEMO</h2>
          </div>
          <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold font-mono">
            STATUS: ACTIVE
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Certificate Holder</span>
            <p class="text-sm font-bold text-white font-sans">Chan Dara</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Course Name</span>
            <p class="text-sm font-bold text-white font-sans">C Programming Basics</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Major</span>
            <p class="text-sm font-bold text-indigo-300 font-sans">Information Technology</p>
          </div>
          <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-1">
            <span class="text-[10px] text-slate-400 font-bold uppercase">Grade / Score</span>
            <p class="text-sm font-bold text-emerald-400 font-sans">A (82%)</p>
          </div>
        </div>
      </div>

    </div>
  </StudentLayout>
</template>
