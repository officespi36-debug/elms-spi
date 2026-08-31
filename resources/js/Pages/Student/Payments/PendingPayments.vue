<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface SessionData {
  session_id: string
  invoice_number: string
  course_name: string
  course_type: string
  student_name: string
  student_id: string
  amount_khr: string
  amount_khr_raw: number
  amount_usd: string
  amount_usd_raw: number
  merchant_name: string
  merchant_id: string
  due_date: string
  due_date_iso: string
  payment_method: string
  qr_code_url: string
  expires_in_seconds: number
  status: string
}

const props = defineProps<{
  sessionData?: SessionData
}>()

const defaultSession: SessionData = {
  session_id: 'PAY-SESSION-2025-000124',
  invoice_number: 'INV-2025-0012',
  course_name: 'Web Development Fundamentals',
  course_type: 'Self-Paced Course',
  student_name: 'Sok Pisey',
  student_id: 'STU2024001',
  amount_khr: '120,000 KHR',
  amount_khr_raw: 120000,
  amount_usd: '≈ $30.00 USD',
  amount_usd_raw: 30.00,
  merchant_name: 'Saint Paul Institute',
  merchant_id: '002 328 456',
  due_date: 'June 07, 2025',
  due_date_iso: '2025-06-07',
  payment_method: 'ABA KHQR',
  qr_code_url: 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=ABA_KHQR_SPI_INV20250012_120000KHR',
  expires_in_seconds: 588, // 09:48
  status: 'WAITING_FOR_PAYMENT',
}

const session = computed(() => props.sessionData || defaultSession)

// Payment Process States
type PaymentState = 'WAITING_FOR_PAYMENT' | 'PAYMENT_DETECTED' | 'PROCESSING' | 'SUCCESS' | 'FAILED' | 'EXPIRED'
const currentState = ref<PaymentState>('WAITING_FOR_PAYMENT')
const currentStep = ref<number>(1)

// Expiration Timer in Seconds
const remainingSeconds = ref<number>(session.value.expires_in_seconds || 588)
let timerInterval: any = null

const formattedTimer = computed(() => {
  const m = Math.floor(remainingSeconds.value / 60).toString().padStart(2, '0')
  const s = (remainingSeconds.value % 60).toString().padStart(2, '0')
  return `${m} : ${s}`
})

const startTimer = () => {
  clearInterval(timerInterval)
  timerInterval = setInterval(() => {
    if (remainingSeconds.value > 0) {
      remainingSeconds.value--
    } else {
      currentState.value = 'EXPIRED'
      clearInterval(timerInterval)
    }
  }, 1000)
}

const refreshQrCode = () => {
  remainingSeconds.value = 600
  currentState.value = 'WAITING_FOR_PAYMENT'
  currentStep.value = 1
  startTimer()
}

// Copy to Clipboard with tooltip
const copiedField = ref<string | null>(null)
const copyToClipboard = (text: string, fieldName: string) => {
  navigator.clipboard.writeText(text)
  copiedField.value = fieldName
  setTimeout(() => {
    copiedField.value = null
  }, 2000)
}

// Simulated Payment Verification Flow
const isSimulating = ref(false)
const simulatePaymentScan = () => {
  if (currentState.value === 'EXPIRED' || currentState.value === 'SUCCESS') return

  isSimulating.value = true
  currentState.value = 'PAYMENT_DETECTED'
  currentStep.value = 2

  setTimeout(() => {
    currentState.value = 'PROCESSING'
    currentStep.value = 3

    setTimeout(() => {
      currentState.value = 'SUCCESS'
      currentStep.value = 4
      isSimulating.value = false
      clearInterval(timerInterval)
    }, 1800)
  }, 1200)
}

// Receipt Modal State
const showReceiptModal = ref(false)

const handleDownloadReceipt = () => {
  showReceiptModal.value = false
  window.alert('Receipt PDF downloaded successfully!')
}

onMounted(() => {
  startTimer()
})

onUnmounted(() => {
  clearInterval(timerInterval)
})
</script>

<template>
  <StudentLayout title="Pay via ABA (KHR) — Payment & Billing">
    <div class="space-y-6 max-w-7xl mx-auto pb-12">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2.5">
            <span>Pay via ABA (KHR)</span>
            <span class="px-2 py-0.5 rounded-lg bg-[#005F86]/30 border border-[#00A3E0]/40 text-[#00A3E0] text-xs font-black tracking-wider uppercase">
              ABA
            </span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            Scan the QR code with your ABA Mobile app to complete your payment securely.
          </p>
        </div>

        <!-- Simulation quick testing pill -->
        <div class="flex items-center gap-2">
          <button
            v-if="currentState === 'WAITING_FOR_PAYMENT'"
            @click="simulatePaymentScan"
            :disabled="isSimulating"
            class="px-3 py-1.5 rounded-xl bg-purple-600/20 hover:bg-purple-600/40 text-purple-300 border border-purple-500/40 text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          >
            <span>⚡</span>
            <span>Simulate ABA Scan &amp; Pay</span>
          </button>
        </div>
      </div>

      <!-- ================= 2. 4-STEP PAYMENT PROGRESS STEPPER ================= -->
      <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 relative">
          
          <!-- Step 1: Scan QR Code -->
          <div class="flex items-center gap-3">
            <div
              :class="[
                currentStep >= 1 ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/40 ring-4 ring-purple-500/20' : 'bg-slate-800 text-slate-400',
                'w-8 h-8 rounded-full flex items-center justify-center font-black text-xs shrink-0 transition-all'
              ]"
            >
              {{ currentStep > 1 ? '✓' : '1' }}
            </div>
            <div>
              <p class="text-xs font-bold text-white leading-tight">Scan QR Code</p>
              <p class="text-[10px] text-slate-400">Open ABA Mobile app</p>
            </div>
          </div>

          <!-- Step 2: Confirm Payment -->
          <div class="flex items-center gap-3">
            <div
              :class="[
                currentStep >= 2 ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/40 ring-4 ring-purple-500/20' : 'bg-slate-800 text-slate-400',
                'w-8 h-8 rounded-full flex items-center justify-center font-black text-xs shrink-0 transition-all'
              ]"
            >
              {{ currentStep > 2 ? '✓' : '2' }}
            </div>
            <div>
              <p class="text-xs font-bold text-white leading-tight">Confirm Payment</p>
              <p class="text-[10px] text-slate-400">Check payment details</p>
            </div>
          </div>

          <!-- Step 3: Processing -->
          <div class="flex items-center gap-3">
            <div
              :class="[
                currentStep >= 3 ? 'bg-purple-600 text-white shadow-lg shadow-purple-900/40 ring-4 ring-purple-500/20' : 'bg-slate-800 text-slate-400',
                'w-8 h-8 rounded-full flex items-center justify-center font-black text-xs shrink-0 transition-all'
              ]"
            >
              {{ currentStep > 3 ? '✓' : '3' }}
            </div>
            <div>
              <p class="text-xs font-bold text-white leading-tight">Processing</p>
              <p class="text-[10px] text-slate-400">We will verify your payment</p>
            </div>
          </div>

          <!-- Step 4: Payment Complete -->
          <div class="flex items-center gap-3">
            <div
              :class="[
                currentStep >= 4 ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/40 ring-4 ring-emerald-500/20' : 'bg-slate-800 text-slate-400',
                'w-8 h-8 rounded-full flex items-center justify-center font-black text-xs shrink-0 transition-all'
              ]"
            >
              {{ currentStep >= 4 ? '✓' : '4' }}
            </div>
            <div>
              <p class="text-xs font-bold text-white leading-tight">Payment Complete</p>
              <p class="text-[10px] text-slate-400">Receive confirmation</p>
            </div>
          </div>

        </div>
      </div>

      <!-- ================= 3. MAIN 3-COLUMN LAYOUT ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= COLUMN 1: LEFT (~32% / lg:col-span-4) — SCAN QR CODE CARD ================= -->
        <div class="lg:col-span-4 space-y-4">
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-6 shadow-xl space-y-5 flex flex-col items-center text-center">
            
            <div class="w-full text-left space-y-0.5">
              <h2 class="text-base font-bold text-white tracking-tight">Scan QR Code</h2>
              <p class="text-xs text-slate-400">Use your ABA Mobile app to scan this QR code</p>
            </div>

            <!-- OFFICIAL WHITE ABA KHQR POSTER CARD -->
            <div class="w-full max-w-[290px] bg-white rounded-2xl p-5 shadow-2xl text-slate-900 space-y-3 relative overflow-hidden">
              
              <!-- KHQR Header -->
              <div class="flex items-center justify-center gap-1">
                <span class="font-black text-lg text-[#005F86] tracking-tight">ABA<span class="text-rose-600">'</span></span>
                <span class="font-black text-lg text-rose-600 tracking-wider">KHQR</span>
              </div>
              <p class="text-[9.5px] text-slate-500 font-bold uppercase tracking-widest -mt-2">Scan. Pay. Done.</p>

              <!-- Dynamic QR Code Container with ABA Badge Overlay -->
              <div class="relative w-48 h-48 mx-auto bg-white p-1 rounded-xl border border-slate-200 shadow-inner flex items-center justify-center">
                <img
                  :src="session.qr_code_url"
                  alt="ABA KHQR Code"
                  class="w-full h-full object-contain"
                  :class="{ 'opacity-20 blur-xs': currentState === 'EXPIRED' }"
                />

                <!-- Center Logo Overlay -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                  <div class="w-10 h-10 rounded-full bg-[#005F86] text-white flex items-center justify-center font-black text-[11px] border-2 border-white shadow-md">
                    ABA'
                  </div>
                </div>

                <!-- Expired Overlay -->
                <div
                  v-if="currentState === 'EXPIRED'"
                  class="absolute inset-0 bg-slate-900/80 backdrop-blur-xs rounded-xl flex flex-col items-center justify-center p-3 space-y-2 text-white"
                >
                  <span class="text-2xl">⌛</span>
                  <p class="text-xs font-bold text-rose-400">QR Code Expired</p>
                  <button
                    @click="refreshQrCode"
                    class="px-3 py-1.5 rounded-lg bg-purple-600 text-white font-bold text-[10px] shadow"
                  >
                    Generate New QR
                  </button>
                </div>
              </div>

              <!-- Merchant Info -->
              <div class="space-y-0.5 pt-1">
                <p class="font-bold text-xs text-slate-900">{{ session.merchant_name }}</p>
                <div class="flex items-center justify-center gap-1.5 text-[10px] text-slate-500 font-mono">
                  <span>Merchant ID: {{ session.merchant_id }}</span>
                  <button
                    @click="copyToClipboard(session.merchant_id, 'merchant_id')"
                    class="hover:text-slate-900 transition-colors"
                    title="Copy Merchant ID"
                  >
                    <span v-if="copiedField === 'merchant_id'" class="text-emerald-600 font-sans text-[9px] font-bold">Copied!</span>
                    <span v-else>⧉</span>
                  </button>
                </div>
              </div>

              <!-- Amount to Pay Box -->
              <div class="bg-slate-50 border border-slate-200 rounded-xl p-2.5 space-y-0.5">
                <p class="text-[9.5px] text-slate-500 font-medium">Amount to Pay</p>
                <div class="flex items-center justify-center gap-1.5">
                  <p class="text-lg font-black text-[#005F86] font-mono tracking-tight">{{ session.amount_khr }}</p>
                  <button
                    @click="copyToClipboard(session.amount_khr, 'amount')"
                    class="text-slate-400 hover:text-slate-700 transition-colors text-xs"
                    title="Copy Amount"
                  >
                    <span v-if="copiedField === 'amount'" class="text-emerald-600 font-sans text-[9px] font-bold">Copied!</span>
                    <span v-else>⧉</span>
                  </button>
                </div>
              </div>

            </div>

            <!-- COUNTDOWN TIMER & ACTION -->
            <div class="w-full space-y-3">
              <div class="flex items-center justify-center gap-2 py-2 px-4 rounded-xl bg-slate-900 border border-slate-800 text-xs">
                <span class="text-slate-400">🕒 This QR code will expire in</span>
                <span class="font-mono font-black text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-md border border-rose-500/20">
                  {{ formattedTimer }}
                </span>
              </div>

              <button
                @click="refreshQrCode"
                class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white font-bold text-xs border border-slate-800 flex items-center justify-center gap-2 transition-all cursor-pointer"
              >
                <span>🔄</span>
                <span>Refresh QR Code</span>
              </button>

              <p class="text-[10px] text-slate-500 flex items-center justify-center gap-1">
                <span>🔒</span>
                <span>Your payment is secured with ABA Bank KHQR</span>
              </p>
            </div>

          </div>
        </div>

        <!-- ================= COLUMN 2: CENTER (~38% / lg:col-span-5) — PAYMENT DETAILS ================= -->
        <div class="lg:col-span-5 space-y-4">
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-6 shadow-xl space-y-5">
            
            <div class="space-y-0.5">
              <h2 class="text-base font-bold text-white tracking-tight">Payment Details</h2>
              <p class="text-xs text-slate-400">Please verify the information before making the payment.</p>
            </div>

            <!-- KEY-VALUE ROWS -->
            <div class="space-y-3.5 text-xs">
              
              <!-- 1. Course Name -->
              <div class="flex items-start justify-between gap-3 border-b border-slate-800/60 pb-3">
                <div class="flex items-center gap-2 text-slate-400">
                  <span class="text-sm">📺</span>
                  <span>Course Name</span>
                </div>
                <div class="text-right">
                  <p class="font-bold text-white">{{ session.course_name }}</p>
                  <p class="text-[10px] text-slate-400">{{ session.course_type }}</p>
                </div>
              </div>

              <!-- 2. Invoice Number -->
              <div class="flex items-center justify-between gap-3 border-b border-slate-800/60 pb-3">
                <div class="flex items-center gap-2 text-slate-400">
                  <span class="text-sm">📑</span>
                  <span>Invoice Number</span>
                </div>
                <div class="flex items-center gap-1.5 font-mono font-bold text-slate-200">
                  <span>{{ session.invoice_number }}</span>
                  <button
                    @click="copyToClipboard(session.invoice_number, 'invoice')"
                    class="text-slate-500 hover:text-white transition-colors"
                  >
                    <span v-if="copiedField === 'invoice'" class="text-emerald-400 text-[10px] font-sans">Copied!</span>
                    <span v-else>⧉</span>
                  </button>
                </div>
              </div>

              <!-- 3. Student Name -->
              <div class="flex items-center justify-between gap-3 border-b border-slate-800/60 pb-3">
                <div class="flex items-center gap-2 text-slate-400">
                  <span class="text-sm">👤</span>
                  <span>Student Name</span>
                </div>
                <span class="font-bold text-white">{{ session.student_name }}</span>
              </div>

              <!-- 4. Student ID -->
              <div class="flex items-center justify-between gap-3 border-b border-slate-800/60 pb-3">
                <div class="flex items-center gap-2 text-slate-400">
                  <span class="text-sm">🆔</span>
                  <span>Student ID</span>
                </div>
                <span class="font-mono font-bold text-slate-300">{{ session.student_id }}</span>
              </div>

              <!-- 5. Amount -->
              <div class="flex items-start justify-between gap-3 border-b border-slate-800/60 pb-3">
                <div class="flex items-center gap-2 text-slate-400">
                  <span class="text-sm">💲</span>
                  <span>Amount</span>
                </div>
                <div class="text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <p class="font-black text-white font-mono text-sm">{{ session.amount_khr }}</p>
                    <button
                      @click="copyToClipboard(session.amount_khr, 'amount_row')"
                      class="text-slate-500 hover:text-white transition-colors text-xs"
                    >
                      <span v-if="copiedField === 'amount_row'" class="text-emerald-400 text-[10px] font-sans">Copied!</span>
                      <span v-else>⧉</span>
                    </button>
                  </div>
                  <p class="text-[10.5px] text-slate-400 font-mono">({{ session.amount_usd }})</p>
                </div>
              </div>

              <!-- 6. Due Date -->
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-slate-400">
                  <span class="text-sm">📅</span>
                  <span>Due Date</span>
                </div>
                <span class="font-bold text-slate-200">{{ session.due_date }}</span>
              </div>

            </div>

            <!-- IMPORTANT BLUE NOTICE BOX -->
            <div class="p-4 rounded-2xl bg-blue-950/40 border border-blue-900/50 space-y-2.5 text-xs">
              <div class="flex items-center gap-2 text-blue-400 font-bold">
                <span>ℹ️</span>
                <span>Important</span>
              </div>
              <ol class="space-y-1 text-slate-300 text-[11px] pl-4 list-decimal">
                <li>Open ABA Mobile app on your phone</li>
                <li>Tap on "Scan KHQR"</li>
                <li>Scan the QR code on the left</li>
                <li>Check the payment details</li>
                <li>Confirm the payment</li>
              </ol>
            </div>

            <!-- NEED HELP? BOX -->
            <div class="p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800/80 flex items-center justify-between gap-3 text-xs">
              <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-purple-600/20 text-purple-300 border border-purple-500/30 flex items-center justify-center shrink-0">
                  🎧
                </div>
                <div class="min-w-0">
                  <p class="font-bold text-white">Need help?</p>
                  <p class="text-[10px] text-slate-400 truncate">If you face any issues, please contact our support team.</p>
                </div>
              </div>
              <a href="mailto:support@spilms.tech" class="text-xs text-purple-400 font-bold hover:underline shrink-0 whitespace-nowrap">
                Contact Support →
              </a>
            </div>

          </div>
        </div>

        <!-- ================= COLUMN 3: RIGHT (~30% / lg:col-span-3) — GUIDE & SECURITY ================= -->
        <div class="lg:col-span-3 space-y-4">
          
          <!-- CARD 1: PAY WITH ABA MOBILE (5-STEP GUIDE) -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex items-center gap-2.5 border-b border-slate-800/60 pb-3">
              <div class="w-8 h-8 rounded-xl bg-[#005F86] text-white flex items-center justify-center font-black text-xs shrink-0">
                ABA'
              </div>
              <div>
                <h3 class="text-xs font-black text-white">Pay with ABA Mobile</h3>
                <p class="text-[10px] text-slate-400">Fast • Secure • Reliable</p>
              </div>
            </div>

            <div class="space-y-3 text-xs">
              <div class="flex items-start gap-2.5">
                <div class="w-5 h-5 rounded-full bg-blue-600/30 text-blue-300 border border-blue-500/40 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">
                  1
                </div>
                <div>
                  <p class="font-bold text-white text-[11px]">Open ABA Mobile</p>
                  <p class="text-[9.5px] text-slate-400">Launch your ABA Mobile app</p>
                </div>
              </div>

              <div class="flex items-start gap-2.5">
                <div class="w-5 h-5 rounded-full bg-blue-600/30 text-blue-300 border border-blue-500/40 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">
                  2
                </div>
                <div>
                  <p class="font-bold text-white text-[11px]">Tap Scan KHQR</p>
                  <p class="text-[9.5px] text-slate-400">Select the KHQR scanner</p>
                </div>
              </div>

              <div class="flex items-start gap-2.5">
                <div class="w-5 h-5 rounded-full bg-blue-600/30 text-blue-300 border border-blue-500/40 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">
                  3
                </div>
                <div>
                  <p class="font-bold text-white text-[11px]">Scan this QR code</p>
                  <p class="text-[9.5px] text-slate-400">Point your camera to the QR code</p>
                </div>
              </div>

              <div class="flex items-start gap-2.5">
                <div class="w-5 h-5 rounded-full bg-blue-600/30 text-blue-300 border border-blue-500/40 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">
                  4
                </div>
                <div>
                  <p class="font-bold text-white text-[11px]">Check payment details</p>
                  <p class="text-[9.5px] text-slate-400">Verify the amount and merchant name</p>
                </div>
              </div>

              <div class="flex items-start gap-2.5">
                <div class="w-5 h-5 rounded-full bg-blue-600/30 text-blue-300 border border-blue-500/40 flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">
                  5
                </div>
                <div>
                  <p class="font-bold text-white text-[11px]">Confirm payment</p>
                  <p class="text-[9.5px] text-slate-400">Enter your PIN to complete the payment</p>
                </div>
              </div>
            </div>
          </div>

          <!-- CARD 2: ACCEPTED PAYMENT METHOD -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <h3 class="text-xs font-bold text-white">Accepted Payment Method</h3>

            <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-[#005F86] text-white flex items-center justify-center font-black text-xs shrink-0">
                ABA'
              </div>
              <div>
                <p class="font-bold text-white text-xs">ABA KHQR</p>
                <p class="text-[9.5px] text-slate-400">Pay directly from your ABA account</p>
              </div>
            </div>

            <div class="space-y-1.5 pt-1 text-[11px] text-slate-300">
              <div class="flex items-center gap-2">
                <span class="text-emerald-400">✓</span>
                <span>No additional fees</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-emerald-400">✓</span>
                <span>Instant confirmation</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-emerald-400">✓</span>
                <span>Secure and reliable</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-emerald-400">✓</span>
                <span>Supported by all ABA accounts</span>
              </div>
            </div>
          </div>

          <!-- CARD 3: SECURITY NOTICE -->
          <div class="bg-gradient-to-br from-[#12142E] via-[#0F172A] to-[#1F1138] border border-purple-900/40 rounded-3xl p-5 shadow-xl space-y-2 text-xs">
            <div class="flex items-center gap-2 text-purple-300 font-bold">
              <span class="text-sm">🛡️</span>
              <span>Security Notice</span>
            </div>
            <p class="text-[11px] text-slate-400 leading-relaxed">
              Only pay to the official SPI merchant account. Do not share your payment information with anyone.
            </p>
            <a href="#" class="text-[10px] text-purple-400 font-bold hover:underline block pt-1">
              Learn more about payment security →
            </a>
          </div>

        </div>

      </div>

      <!-- ================= 4. BOTTOM CONFIRMATION BANNER ================= -->
      <div class="bg-gradient-to-r from-purple-950/70 via-[#0F172A] to-blue-950/70 border border-purple-900/40 rounded-3xl p-5 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-lg shrink-0">
            🎓
          </div>
          <div>
            <p class="font-bold text-white text-xs sm:text-sm">After payment completion</p>
            <p class="text-[11px] text-slate-400">Your invoice will be automatically updated and you will receive a confirmation notification.</p>
          </div>
        </div>

        <div class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-slate-900/80 border border-slate-800 shrink-0">
          <span class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-300 flex items-center justify-center text-xs font-bold">✓</span>
          <div>
            <p class="font-bold text-white text-[11px]">Instant Confirmation</p>
            <p class="text-[9.5px] text-slate-400">Usually within 1-2 minutes</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ================= MODAL: PAYMENT SUCCESS CONFIRMATION ================= -->
    <div
      v-if="currentState === 'SUCCESS'"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-emerald-500/40 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl text-center relative">
        
        <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center text-3xl mx-auto shadow-lg shadow-emerald-950/50">
          ✓
        </div>

        <div class="space-y-1">
          <h3 class="text-xl font-black text-white">Payment Successful</h3>
          <p class="text-xs text-slate-300">Your payment has been completed successfully.</p>
        </div>

        <!-- Receipt Summary Box -->
        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 text-xs font-mono text-left space-y-2">
          <div class="flex justify-between border-b border-slate-800/80 pb-1.5">
            <span class="text-slate-500 font-sans">Amount Paid:</span>
            <span class="font-bold text-emerald-400 text-sm">{{ session.amount_khr }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-1.5">
            <span class="text-slate-500 font-sans">Payment Method:</span>
            <span class="text-slate-300">ABA KHQR</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-1.5">
            <span class="text-slate-500 font-sans">Invoice Number:</span>
            <span class="text-purple-300">{{ session.invoice_number }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-1.5">
            <span class="text-slate-500 font-sans">Transaction ID:</span>
            <span class="text-slate-300">TRX-2025-ABA-000124</span>
          </div>
          <div class="flex justify-between pt-0.5">
            <span class="text-slate-500 font-sans">Payment Date:</span>
            <span class="text-slate-300">May 28, 2025</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-2 pt-1 text-xs">
          <button
            @click="showReceiptModal = true"
            class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-md flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <span>⤓</span>
            <span>Download Receipt</span>
          </button>
          
          <div class="grid grid-cols-2 gap-2">
            <Link
              href="/student/payments/my-payments"
              class="py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold flex items-center justify-center transition-colors"
            >
              View Invoice
            </Link>
            <Link
              href="/student/payments/my-payments"
              class="py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md flex items-center justify-center transition-colors"
            >
              Back to Payments
            </Link>
          </div>
        </div>

      </div>
    </div>

    <!-- ================= PRINTABLE RECEIPT MODAL ================= -->
    <div
      v-if="showReceiptModal"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white text-slate-900 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
          <div>
            <h3 class="text-base font-black font-serif uppercase tracking-wider text-slate-900">Official ABA e-Receipt</h3>
            <p class="text-[10px] text-slate-500 font-mono">REC-2025-0012</p>
          </div>
          <button
            @click="showReceiptModal = false"
            class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs"
          >
            ✕
          </button>
        </div>

        <div class="space-y-2.5 text-xs">
          <div class="flex justify-between"><span class="text-slate-500">Institution:</span><span class="font-bold">Saint Paul Institute (SPI)</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Student Name:</span><span class="font-bold">{{ session.student_name }} ({{ session.student_id }})</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Course / Program:</span><span class="font-bold">{{ session.course_name }}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Payment Method:</span><span class="font-bold">ABA KHQR</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Transaction ID:</span><span class="font-mono">TRX-2025-ABA-000124</span></div>
          <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm">
            <span>Amount Paid:</span>
            <span class="text-emerald-600 font-mono">{{ session.amount_khr }}</span>
          </div>
        </div>

        <div class="pt-2 border-t border-slate-200 flex justify-end gap-2 text-xs">
          <button
            @click="showReceiptModal = false"
            class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold"
          >
            Close
          </button>
          <button
            @click="handleDownloadReceipt"
            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-md flex items-center gap-1.5"
          >
            <span>⤓</span>
            <span>Download PDF</span>
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>
