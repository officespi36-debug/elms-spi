<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

defineProps<{
  isValid: boolean
  student?: {
    name: string
    name_kh?: string
    program: string
    program_kh?: string
    status: string
    status_kh?: string
    expires_at: string
    verified_at: string
    institution: string
  } | null
  message: string
}>()
</script>

<template>
  <Head title="Official Student ID Verification — Saint Paul Institute">
    <meta name="robots" content="noindex, nofollow" />
  </Head>

  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col items-center justify-center p-4 selection:bg-indigo-500 selection:text-white">
    <!-- Ambient Background Glow -->
    <div class="fixed -top-40 -left-40 w-96 h-96 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="fixed -bottom-40 -right-40 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md bg-slate-900/90 border border-slate-800 rounded-3xl p-6 md:p-8 shadow-2xl relative z-10 space-y-6">
      
      <!-- Top Brand Header -->
      <div class="flex items-center gap-3.5 border-b border-slate-800 pb-5">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-xl font-black shadow-lg shadow-indigo-600/30">
          🎓
        </div>
        <div>
          <h1 class="text-xs font-black tracking-widest text-indigo-300 uppercase">
            {{ student?.institution || 'SAINT PAUL INSTITUTE' }}
          </h1>
          <p class="text-[11px] text-slate-400 font-medium">
            ប្រព័ន្ធផ្ទៀងផ្ទាត់កាតនិស្សិតឌីជីថលផ្លូវការ (Digital ID Verification)
          </p>
        </div>
      </div>

      <!-- VALID STATE -->
      <div v-if="isValid && student" class="space-y-5">
        
        <!-- Status Pill -->
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-emerald-500 text-slate-950 font-black text-sm flex items-center justify-center shrink-0">
            ✓
          </div>
          <div class="text-xs">
            <p class="font-bold text-emerald-200">កាតផ្លូវការត្រឹមត្រូវ (Verified Official ID)</p>
            <p class="text-[11px] text-emerald-300/80">និស្សិតសកម្មក្នុងបញ្ជីឈ្មោះផ្លូវការ</p>
          </div>
        </div>

        <!-- Verified Details (Privacy-Safe: strictly no phone, email, dob, address) -->
        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-3.5 text-xs">
          
          <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">ឈ្មោះនិស្សិត (Student Name)</p>
            <p class="text-base font-black text-white mt-0.5">
              {{ student.name }}
              <span v-if="student.name_kh" class="text-indigo-300 text-xs font-semibold ml-1.5">({{ student.name_kh }})</span>
            </p>
          </div>

          <div class="pt-2 border-t border-slate-800/60 flex items-center justify-between">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">ស្ថានភាពនិស្សិត (Status)</p>
            <p class="text-xs font-bold text-emerald-400">● {{ student.status_kh || student.status }}</p>
          </div>

          <div class="pt-2 border-t border-slate-800/60">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">កម្មវិធីសិក្សា (Academic Major)</p>
            <p class="text-xs font-bold text-indigo-300 mt-0.5">{{ student.program_kh || student.program }}</p>
          </div>

          <div class="pt-2 border-t border-slate-800/60 flex items-center justify-between text-[11px]">
            <span class="text-slate-400">សុពលភាពដល់ (Expires):</span>
            <span class="font-bold text-slate-300 font-mono">{{ student.expires_at }}</span>
          </div>

        </div>

        <div class="text-[10px] text-slate-500 text-center space-y-1">
          <p>ពេលវេលាផ្ទៀងផ្ទាត់៖ {{ student.verified_at }}</p>
          <p>ប្រព័ន្ធរក្សាសុវត្ថិភាព និងភាពឯកជនរបស់និស្សិតយ៉ាងតឹងរ៉ឹង</p>
        </div>

      </div>

      <!-- INVALID / REVOKED STATE -->
      <div v-else class="space-y-5 text-center py-4">
        
        <div class="w-16 h-16 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-400 text-2xl font-bold flex items-center justify-center mx-auto">
          ✕
        </div>

        <div class="space-y-2">
          <h3 class="text-base font-bold text-white">កាតសម្គាល់ខ្លួនមិនត្រឹមត្រូវ</h3>
          <p class="text-xs text-slate-400 max-w-xs mx-auto leading-relaxed">
            {{ message }}
          </p>
        </div>

        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 text-[11px] text-slate-400">
          តំណភ្ជាប់នេះអាចផុតកំណត់ ឬត្រូវបានជំនួសដោយកូដថ្មី។ សូមទាក់ទងការិយាល័យសិក្សាសម្រាប់ព័ត៌មានបន្ថែម។
        </div>

      </div>

      <!-- Bottom Home Action -->
      <div class="pt-2 text-center border-t border-slate-800">
        <a
          href="/"
          class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition-colors inline-flex items-center gap-1.5"
        >
          <span>← ត្រឡប់ទៅកាន់ទំព័រដើម SPI E-LMS</span>
        </a>
      </div>

    </div>
  </div>
</template>
