<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface TransactionItem {
  id: number
  transaction_id: string
  invoice_number: string
  course_name: string
  payment_method: string
  method_type: string
  amount_khr: string
  amount_raw: number
  date_formatted: string
  time_formatted: string
  date_time_text: string
  status: 'Successful' | 'Pending' | 'Processing' | 'Failed' | 'Refunded'
  status_type: 'successful' | 'pending' | 'processing' | 'failed' | 'refunded'
  reference_code: string
}

interface SummaryData {
  total_transactions: number
  total_tx_note: string
  successful_payments: number
  successful_note: string
  pending_payments: number
  pending_note: string
  total_paid: string
  total_paid_note: string
}

interface PaymentSummaryWidget {
  total_paid: string
  progress_percent: number
  successful_count: number
  pending_count: number
  failed_count: number
}

interface LatestPaymentWidget {
  course_name: string
  amount: string
  status: string
  status_type: string
  date_text: string
  invoice_number: string
  transaction_id: string
}

const props = defineProps<{
  analytics?: {
    summary: SummaryData
    payment_summary: PaymentSummaryWidget
    latest_payment: LatestPaymentWidget
    transactions: TransactionItem[]
    total_count: number
    current_page: number
    per_page: number
  }
  filters?: {
    status: string
    method: string
    sort: string
    search: string
    page: number
  }
}>()

// Default baseline data
const defaultSummary: SummaryData = {
  total_transactions: 12,
  total_tx_note: 'All payment records',
  successful_payments: 10,
  successful_note: 'Completed transactions',
  pending_payments: 2,
  pending_note: 'Awaiting confirmation',
  total_paid: '1,250,000 KHR',
  total_paid_note: 'Total successful payments',
}

const defaultPaymentSummary: PaymentSummaryWidget = {
  total_paid: '1,250,000 KHR',
  progress_percent: 83,
  successful_count: 10,
  pending_count: 2,
  failed_count: 0,
}

const defaultLatestPayment: LatestPaymentWidget = {
  course_name: 'Web Development Fundamentals',
  amount: '120,000 KHR',
  status: 'Payment Successful',
  status_type: 'successful',
  date_text: 'Today • 09:45 AM',
  invoice_number: 'INV-2025-0012',
  transaction_id: 'TRX-2025-00124',
}

const defaultTransactions: TransactionItem[] = [
  { id: 1, transaction_id: 'TRX-2025-00124', invoice_number: 'INV-2025-0012', course_name: 'Web Development Fundamentals', payment_method: 'ABA KHQR', method_type: 'aba', amount_khr: '120,000 KHR', amount_raw: 120000, date_formatted: 'May 28, 2025', time_formatted: '09:45 AM', date_time_text: 'May 28, 2025 • 09:45 AM', status: 'Successful', status_type: 'successful', reference_code: 'REF-ABA-98421045' },
  { id: 2, transaction_id: 'TRX-2025-00123', invoice_number: 'INV-2025-0010', course_name: 'Database Systems', payment_method: 'ABA KHQR', method_type: 'aba', amount_khr: '95,000 KHR', amount_raw: 95000, date_formatted: 'May 20, 2025', time_formatted: '02:30 PM', date_time_text: 'May 20, 2025 • 02:30 PM', status: 'Successful', status_type: 'successful', reference_code: 'REF-ABA-54120984' },
  { id: 3, transaction_id: 'TRX-2025-00122', invoice_number: 'INV-2025-0009', course_name: 'Python Programming', payment_method: 'ABA KHQR', method_type: 'aba', amount_khr: '80,000 KHR', amount_raw: 80000, date_formatted: 'May 18, 2025', time_formatted: '11:15 AM', date_time_text: 'May 18, 2025 • 11:15 AM', status: 'Pending', status_type: 'pending', reference_code: 'REF-ABA-45129871' },
  { id: 4, transaction_id: 'TRX-2025-00121', invoice_number: 'INV-2025-0011', course_name: 'React Development', payment_method: 'Card Payment', method_type: 'card', amount_khr: '150,000 KHR', amount_raw: 150000, date_formatted: 'May 15, 2025', time_formatted: '10:00 AM', date_time_text: 'May 15, 2025 • 10:00 AM', status: 'Successful', status_type: 'successful', reference_code: 'REF-VISA-67210982' },
  { id: 5, transaction_id: 'TRX-2025-00120', invoice_number: 'INV-2025-0008', course_name: 'UI/UX Design Basics', payment_method: 'Bank Transfer', method_type: 'bank', amount_khr: '120,000 KHR', amount_raw: 120000, date_formatted: 'May 10, 2025', time_formatted: '04:20 PM', date_time_text: 'May 10, 2025 • 04:20 PM', status: 'Pending', status_type: 'pending', reference_code: 'REF-BANK-38910245' },
  { id: 6, transaction_id: 'TRX-2025-00119', invoice_number: 'INV-2025-0007', course_name: 'JavaScript Advanced', payment_method: 'Wing', method_type: 'wing', amount_khr: '180,000 KHR', amount_raw: 180000, date_formatted: 'May 05, 2025', time_formatted: '01:10 PM', date_time_text: 'May 05, 2025 • 01:10 PM', status: 'Successful', status_type: 'successful', reference_code: 'REF-WING-78451290' },
  { id: 7, transaction_id: 'TRX-2025-00118', invoice_number: 'INV-2025-0006', course_name: 'Node.js Backend', payment_method: 'ABA KHQR', method_type: 'aba', amount_khr: '180,000 KHR', amount_raw: 180000, date_formatted: 'Apr 28, 2025', time_formatted: '08:50 AM', date_time_text: 'Apr 28, 2025 • 08:50 AM', status: 'Successful', status_type: 'successful', reference_code: 'REF-ABA-98124578' },
  { id: 8, transaction_id: 'TRX-2025-00117', invoice_number: 'INV-2025-0005', course_name: 'Data Science Basics', payment_method: 'ABA KHQR', method_type: 'aba', amount_khr: '150,000 KHR', amount_raw: 150000, date_formatted: 'Apr 20, 2025', time_formatted: '03:40 PM', date_time_text: 'Apr 20, 2025 • 03:40 PM', status: 'Successful', status_type: 'successful', reference_code: 'REF-ABA-34120985' },
  { id: 9, transaction_id: 'TRX-2025-00116', invoice_number: 'INV-2025-0004', course_name: 'Git & GitHub', payment_method: 'Card Payment', method_type: 'card', amount_khr: '100,000 KHR', amount_raw: 100000, date_formatted: 'Apr 15, 2025', time_formatted: '09:30 AM', date_time_text: 'Apr 15, 2025 • 09:30 AM', status: 'Successful', status_type: 'successful', reference_code: 'REF-VISA-23109842' },
  { id: 10, transaction_id: 'TRX-2025-00115', invoice_number: 'INV-2025-0003', course_name: 'HTML & CSS Essentials', payment_method: 'Wing', method_type: 'wing', amount_khr: '100,000 KHR', amount_raw: 100000, date_formatted: 'Apr 10, 2025', time_formatted: '02:15 PM', date_time_text: 'Apr 10, 2025 • 02:15 PM', status: 'Successful', status_type: 'successful', reference_code: 'REF-WING-12908341' },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const paymentSummary = computed(() => props.analytics?.payment_summary || defaultPaymentSummary)
const latestPayment = computed(() => props.analytics?.latest_payment || defaultLatestPayment)
const transactions = computed(() => props.analytics?.transactions || defaultTransactions)

// Filters State
const searchQuery = ref<string>(props.filters?.search || '')
const selectedStatus = ref<string>(props.filters?.status || 'all')
const selectedMethod = ref<string>(props.filters?.method || 'all')
const selectedSort = ref<string>(props.filters?.sort || 'newest')

// Modals State
const selectedTransaction = ref<TransactionItem | null>(null)
const isDetailsModalOpen = ref<boolean>(false)
const isReceiptModalOpen = ref<boolean>(false)

const handleFilterChange = () => {
  router.get('/student/payments/transactions', {
    status: selectedStatus.value,
    method: selectedMethod.value,
    sort: selectedSort.value,
    search: searchQuery.value,
    page: 1,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const openDetailsModal = (tx: TransactionItem) => {
  selectedTransaction.value = tx
  isDetailsModalOpen.value = true
}

const openReceiptModal = (tx: TransactionItem) => {
  selectedTransaction.value = tx
  isReceiptModalOpen.value = true
}
</script>

<template>
  <StudentLayout title="Transaction History — Payment & Billing">
    <div class="space-y-6 max-w-7xl mx-auto pb-12">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
            <span>Transaction History</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">📜</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            View and track all your payment transactions.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <Link
            href="/student/payments/my-payments"
            class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-700/80 text-slate-300 hover:text-white font-bold text-xs flex items-center gap-1.5 transition-colors"
          >
            <span>←</span>
            <span>Back to Payments</span>
          </Link>

          <button
            @click="alert('Exporting transaction history statement...')"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-950/40 flex items-center gap-1.5 transition-all cursor-pointer"
          >
            <span>⤓</span>
            <span>Export History</span>
          </button>
        </div>
      </div>

      <!-- ================= 2. TOP 4 SUMMARY METRIC CARDS ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: Total Transactions -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-blue-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Total Transactions</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.total_transactions }}</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ summary.total_tx_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-blue-600/20 border border-blue-500/30 text-blue-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            💳
          </div>
        </div>

        <!-- Card 2: Successful Payments -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-emerald-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Successful</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.successful_payments }}</p>
            <p class="text-[10px] text-emerald-400 font-medium">{{ summary.successful_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            ✓
          </div>
        </div>

        <!-- Card 3: Pending Payments -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-amber-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Pending</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.pending_payments }}</p>
            <p class="text-[10px] text-amber-400 font-medium">{{ summary.pending_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-amber-600/20 border border-amber-500/30 text-amber-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            ⏳
          </div>
        </div>

        <!-- Card 4: Total Paid -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Total Paid</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.total_paid }}</p>
            <p class="text-[10px] text-purple-400 font-medium">{{ summary.total_paid_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            💰
          </div>
        </div>

      </div>

      <!-- ================= 3. FILTER & SEARCH TOOLBAR ================= -->
      <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-3 shadow-lg flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        
        <!-- Search Input -->
        <div class="relative flex-1">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 text-xs">🔎</span>
          <input
            v-model="searchQuery"
            @input="handleFilterChange"
            type="text"
            placeholder="Search transaction ID, invoice or course..."
            class="w-full bg-slate-900 border border-slate-700/80 rounded-xl pl-8 pr-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500 transition-colors"
          />
        </div>

        <!-- Dropdowns -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- Status Dropdown -->
          <select
            v-model="selectedStatus"
            @change="handleFilterChange"
            class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="all">All Status</option>
            <option value="successful">Successful</option>
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>

          <!-- Method Dropdown -->
          <select
            v-model="selectedMethod"
            @change="handleFilterChange"
            class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="all">All Methods</option>
            <option value="aba">ABA KHQR</option>
            <option value="bank">Bank Transfer</option>
            <option value="card">Card Payment</option>
            <option value="wing">Wing</option>
          </select>

          <!-- Date Range -->
          <button
            class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-300 rounded-xl px-3 py-2 hover:text-white flex items-center gap-1.5 transition-colors cursor-pointer"
          >
            <span>📅</span>
            <span>Date Range ▾</span>
          </button>

          <!-- Sort Dropdown -->
          <select
            v-model="selectedSort"
            @change="handleFilterChange"
            class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-purple-500 cursor-pointer"
          >
            <option value="newest">Sort: Newest First</option>
            <option value="oldest">Sort: Oldest First</option>
            <option value="high">Amount: High to Low</option>
            <option value="low">Amount: Low to High</option>
          </select>
        </div>

      </div>

      <!-- ================= 4. MAIN LAYOUT (LEFT: TABLE ~68%, RIGHT: SIDEBAR ~32%) ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT COLUMN: PAYMENT TRANSACTIONS TABLE (lg:col-span-8) ================= -->
        <div class="lg:col-span-8 space-y-4">
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-4">
            
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-white tracking-tight uppercase">Payment Transactions</h3>
              <span class="text-xs text-slate-400 font-mono">{{ transactions.length }} Records</span>
            </div>

            <!-- TABLE VIEW (DESKTOP / TABLET) -->
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/90 text-slate-400 text-[10px] uppercase font-bold border-b border-slate-800">
                  <tr>
                    <th class="p-3">Transaction</th>
                    <th class="p-3">Course</th>
                    <th class="p-3">Method</th>
                    <th class="p-3">Amount</th>
                    <th class="p-3">Date &amp; Time</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                  <tr
                    v-for="tx in transactions"
                    :key="tx.id"
                    class="hover:bg-slate-800/30 transition-colors group"
                  >
                    <!-- Transaction ID -->
                    <td class="p-3 whitespace-nowrap">
                      <p class="font-mono text-slate-200 font-bold">{{ tx.transaction_id }}</p>
                      <p class="text-[10px] text-slate-500 font-mono">{{ tx.invoice_number }}</p>
                    </td>

                    <!-- Course Name -->
                    <td class="p-3 font-bold text-white max-w-[150px] truncate">
                      {{ tx.course_name }}
                    </td>

                    <!-- Method -->
                    <td class="p-3 text-slate-300 whitespace-nowrap">
                      <span class="px-2 py-0.5 rounded-md bg-slate-900 border border-slate-800 text-[11px] font-medium">
                        {{ tx.payment_method }}
                      </span>
                    </td>

                    <!-- Amount -->
                    <td class="p-3 font-bold text-white font-mono whitespace-nowrap">
                      {{ tx.amount_khr }}
                    </td>

                    <!-- Date & Time -->
                    <td class="p-3 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                      {{ tx.date_time_text }}
                    </td>

                    <!-- Status -->
                    <td class="p-3 whitespace-nowrap">
                      <span
                        :class="[
                          tx.status_type === 'successful' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' :
                          tx.status_type === 'pending' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' :
                          tx.status_type === 'processing' ? 'bg-purple-500/20 text-purple-300 border-purple-500/30' :
                          tx.status_type === 'failed' ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' :
                          'bg-blue-500/20 text-blue-300 border-blue-500/30',
                          'px-2.5 py-0.5 rounded-lg text-[10px] font-bold border inline-flex items-center gap-1'
                        ]"
                      >
                        <span v-if="tx.status_type === 'successful'">✓</span>
                        <span v-else-if="tx.status_type === 'pending'">⏳</span>
                        <span v-else-if="tx.status_type === 'failed'">✕</span>
                        <span v-else-if="tx.status_type === 'refunded'">↩</span>
                        <span>{{ tx.status }}</span>
                      </span>
                    </td>

                    <!-- Action -->
                    <td class="p-3 text-right whitespace-nowrap">
                      <div class="flex items-center justify-end gap-1.5">
                        <button
                          @click="openDetailsModal(tx)"
                          class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 text-[11px] font-bold transition-colors cursor-pointer"
                        >
                          View
                        </button>
                        <button
                          v-if="tx.status_type === 'successful'"
                          @click="openReceiptModal(tx)"
                          class="p-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 transition-colors cursor-pointer"
                          title="Download Receipt"
                        >
                          ⤓
                        </button>
                      </div>
                    </td>
                  </tr>

                  <!-- EMPTY STATE -->
                  <tr v-if="transactions.length === 0">
                    <td colspan="7" class="p-8 text-center space-y-2">
                      <p class="text-2xl">💳</p>
                      <p class="font-bold text-white text-sm">No Transactions Found</p>
                      <p class="text-xs text-slate-400">No payment records matched your search or filters.</p>
                      <Link
                        href="/student/payments/my-payments"
                        class="inline-block mt-2 px-4 py-2 rounded-xl bg-purple-600 text-white font-bold text-xs shadow-md"
                      >
                        View Course Fees
                      </Link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Table Pagination -->
            <div class="pt-3 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
              <span class="text-slate-400 text-[11px]">
                Showing 1 to 10 of 12 records
              </span>

              <div class="flex items-center gap-1.5">
                <button class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white font-bold text-xs">
                  ← Previous
                </button>
                <button class="w-7 h-7 rounded-lg bg-purple-600 text-white font-bold text-xs shadow-sm">
                  1
                </button>
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs">
                  2
                </button>
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs">
                  3
                </button>
                <button class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white font-bold text-xs">
                  Next →
                </button>
              </div>
            </div>

          </div>
        </div>

        <!-- ================= RIGHT COLUMN: SIDEBAR WIDGETS (lg:col-span-4) ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- WIDGET 1: PAYMENT SUMMARY -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <div class="flex items-center gap-2 text-white font-bold text-sm">
                <span>💳</span>
                <span>Payment Summary</span>
              </div>
            </div>

            <div class="space-y-3 text-xs">
              <div>
                <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Total Paid</p>
                <p class="text-xl font-black text-white font-mono mt-0.5">{{ paymentSummary.total_paid }}</p>
              </div>

              <!-- Progress Bar -->
              <div class="space-y-1.5">
                <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden flex">
                  <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full" style="width: 83%"></div>
                </div>
                <div class="flex justify-between text-[10px] text-slate-400 font-mono">
                  <span>83% Completed</span>
                  <span>10 / 12 Paid</span>
                </div>
              </div>

              <!-- Stat Rows -->
              <div class="p-3 bg-slate-900/80 rounded-2xl border border-slate-800 space-y-2 text-[11px]">
                <div class="flex justify-between">
                  <span class="text-slate-400 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Successful</span>
                  <span class="font-bold text-white font-mono">{{ paymentSummary.successful_count }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Pending</span>
                  <span class="font-bold text-white font-mono">{{ paymentSummary.pending_count }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400 flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Failed</span>
                  <span class="font-bold text-white font-mono">{{ paymentSummary.failed_count }}</span>
                </div>
              </div>

              <Link
                href="/student/payments/my-payments"
                class="w-full py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-purple-400 hover:text-purple-300 border border-slate-800 font-bold text-xs flex items-center justify-center gap-1.5 transition-colors"
              >
                <span>View Invoices</span>
                <span>→</span>
              </Link>
            </div>
          </div>

          <!-- WIDGET 2: LATEST PAYMENT -->
          <div class="bg-gradient-to-br from-[#12142E] via-[#0F172A] to-[#1F1138] border border-purple-900/40 rounded-3xl p-5 shadow-xl space-y-3.5 text-xs">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <div class="flex items-center gap-2 text-white font-bold text-sm">
                <span>🧾</span>
                <span>Latest Payment</span>
              </div>
            </div>

            <div class="space-y-1.5">
              <p class="font-bold text-white text-sm">{{ latestPayment.course_name }}</p>
              <p class="text-base font-black text-emerald-400 font-mono">{{ latestPayment.amount }}</p>
              
              <div class="flex items-center gap-2 pt-1 text-[11px]">
                <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30">
                  ✓ {{ latestPayment.status }}
                </span>
                <span class="text-slate-400 font-mono text-[10px]">{{ latestPayment.date_text }}</span>
              </div>
            </div>

            <button
              @click="openReceiptModal(transactions[0])"
              class="w-full py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md flex items-center justify-center gap-1.5 transition-all cursor-pointer"
            >
              <span>🧾</span>
              <span>View Receipt</span>
            </button>
          </div>

        </div>

      </div>

    </div>

    <!-- ================= MODAL 1: TRANSACTION DETAILS MODAL ================= -->
    <div
      v-if="isDetailsModalOpen && selectedTransaction"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-md w-full p-6 space-y-5 shadow-2xl relative">
        
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div class="space-y-0.5">
            <h3 class="text-base font-black text-white">Transaction Details</h3>
            <p class="text-xs font-mono text-purple-300">{{ selectedTransaction.transaction_id }}</p>
          </div>
          <button
            @click="isDetailsModalOpen = false"
            class="w-7 h-7 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs"
          >
            ✕
          </button>
        </div>

        <!-- Status Header Badge -->
        <div class="text-center py-2">
          <div class="w-12 h-12 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center text-2xl mx-auto mb-2">
            ✓
          </div>
          <p class="text-sm font-black text-emerald-400">Payment {{ selectedTransaction.status }}</p>
        </div>

        <!-- Key Details Box -->
        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-2.5 text-xs font-mono">
          <div class="flex justify-between border-b border-slate-800/80 pb-2">
            <span class="text-slate-500 font-sans">Transaction ID:</span>
            <span class="text-white font-bold">{{ selectedTransaction.transaction_id }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-2">
            <span class="text-slate-500 font-sans">Invoice:</span>
            <span class="text-purple-300 font-bold">{{ selectedTransaction.invoice_number }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-2">
            <span class="text-slate-500 font-sans">Course:</span>
            <span class="text-white font-sans font-bold text-right">{{ selectedTransaction.course_name }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-2">
            <span class="text-slate-500 font-sans">Payment Method:</span>
            <span class="text-slate-200">{{ selectedTransaction.payment_method }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-2">
            <span class="text-slate-500 font-sans">Amount Paid:</span>
            <span class="text-emerald-400 font-bold text-sm">{{ selectedTransaction.amount_khr }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-2">
            <span class="text-slate-500 font-sans">Payment Date:</span>
            <span class="text-slate-300">{{ selectedTransaction.date_time_text }}</span>
          </div>
          <div class="flex justify-between pt-0.5">
            <span class="text-slate-500 font-sans">Reference Code:</span>
            <span class="text-slate-400">{{ selectedTransaction.reference_code }}</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isDetailsModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Close
          </button>
          <button
            @click="openReceiptModal(selectedTransaction); isDetailsModalOpen = false"
            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-md flex items-center gap-1.5"
          >
            <span>⤓</span>
            <span>Download Receipt</span>
          </button>
        </div>

      </div>
    </div>

    <!-- ================= MODAL 2: PRINTABLE E-RECEIPT MODAL ================= -->
    <div
      v-if="isReceiptModalOpen && selectedTransaction"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white text-slate-900 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
          <div>
            <h3 class="text-base font-black font-serif uppercase tracking-wider text-slate-900">Official SPI e-Receipt</h3>
            <p class="text-[10px] text-slate-500 font-mono">REC-{{ selectedTransaction.transaction_id }}</p>
          </div>
          <button
            @click="isReceiptModalOpen = false"
            class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs"
          >
            ✕
          </button>
        </div>

        <div class="space-y-2.5 text-xs">
          <div class="flex justify-between"><span class="text-slate-500">Institution:</span><span class="font-bold">Saint Paul Institute (SPI)</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Student Name:</span><span class="font-bold">Sok Pisey (STU2024001)</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Course / Program:</span><span class="font-bold">{{ selectedTransaction.course_name }}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Payment Method:</span><span class="font-bold">{{ selectedTransaction.payment_method }}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Transaction ID:</span><span class="font-mono">{{ selectedTransaction.transaction_id }}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Payment Date:</span><span class="font-mono">{{ selectedTransaction.date_time_text }}</span></div>
          <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm">
            <span>Amount Paid:</span>
            <span class="text-emerald-600 font-mono">{{ selectedTransaction.amount_khr }}</span>
          </div>
        </div>

        <div class="pt-2 border-t border-slate-200 flex justify-end gap-2 text-xs">
          <button
            @click="isReceiptModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold"
          >
            Close
          </button>
          <button
            @click="isReceiptModalOpen = false; alert('Receipt PDF downloaded successfully!')"
            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-md flex items-center gap-1.5 cursor-pointer"
          >
            <span>⤓</span>
            <span>Download PDF</span>
          </button>
        </div>
      </div>
    </div>

  </StudentLayout>
</template>
