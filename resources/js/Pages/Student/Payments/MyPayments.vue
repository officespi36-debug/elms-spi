<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface InvoiceItem {
  id: number
  invoice_number: string
  course_name: string
  invoice_date: string
  due_date: string
  amount: string
  amount_raw: number
  status: 'Paid' | 'Pending' | 'Overdue' | 'Cancelled'
  status_type: 'paid' | 'pending' | 'overdue' | 'cancelled'
  payment_method: string
  payment_method_sub: string
  transaction_id?: string | null
  receipt_number?: string | null
}

interface SummaryItem {
  label: string
  amount: string
  percentage: number
  color: string
}

interface UpcomingPaymentItem {
  id: number
  course: string
  due_date: string
  amount: string
  status: string
  status_type: 'pending' | 'overdue'
  color: string
}

interface TransactionItem {
  id: number
  title: string
  date: string
  amount: string
  amount_type: 'positive' | 'negative'
  invoice_number: string
  method: string
}

const props = defineProps<{
  analytics?: {
    summary: {
      total_due: string
      total_due_raw: number
      total_due_note: string
      paid_amount: string
      paid_amount_raw: number
      paid_amount_note: string
      total_invoices: number
      total_inv_note: string
      paid_invoices: number
      paid_inv_note: string
      pending_invoices: number
      pending_inv_note: string
    }
    payment_summary: {
      total_amount: string
      total_note: string
      items: SummaryItem[]
    }
    upcoming_payments: UpcomingPaymentItem[]
    recent_transactions: TransactionItem[]
    invoices: InvoiceItem[]
    total_count: number
    current_page: number
    per_page: number
  }
  filters?: {
    status: string
    course: string
    date_range: string
    search: string
    page: number
  }
}>()

// Default baseline data
const defaultSummary = {
  total_due: '$180.00',
  total_due_raw: 180.00,
  total_due_note: 'Outstanding balance',
  paid_amount: '$1,320.00',
  paid_amount_raw: 1320.00,
  paid_amount_note: 'Total paid',
  total_invoices: 12,
  total_inv_note: 'All invoices',
  paid_invoices: 9,
  paid_inv_note: 'Paid successfully',
  pending_invoices: 3,
  pending_inv_note: 'Awaiting payment',
}

const defaultPaymentSummary = {
  total_amount: '$1,500',
  total_note: 'All time',
  items: [
    { label: 'Paid',    amount: '$1,320.00', percentage: 88, color: '#10B981' },
    { label: 'Pending', amount: '$280.00',   percentage: 12, color: '#F59E0B' },
    { label: 'Overdue', amount: '$180.00',   percentage: 8,  color: '#EF4444' },
  ]
}

const defaultUpcoming: UpcomingPaymentItem[] = [
  { id: 1, course: 'UI/UX Design Basics', due_date: 'Due on May 20, 2025', amount: '$120.00', status: 'Pending', status_type: 'pending', color: 'amber' },
  { id: 2, course: 'Node.js Backend', due_date: 'Due on May 08, 2025', amount: '$180.00', status: 'Pending', status_type: 'pending', color: 'amber' },
  { id: 3, course: 'JavaScript Advanced', due_date: 'Due on May 15, 2025', amount: '$180.00', status: 'Overdue', status_type: 'overdue', color: 'rose' },
]

const defaultTransactions: TransactionItem[] = [
  { id: 1, title: 'Payment Received', date: 'May 28, 2025 10:30 AM', amount: '+$120.00', amount_type: 'positive', invoice_number: 'INV-2025-0012', method: 'ABA Bank' },
  { id: 2, title: 'Payment Received', date: 'May 25, 2025 09:15 AM', amount: '+$150.00', amount_type: 'positive', invoice_number: 'INV-2025-0011', method: 'Visa Card' },
  { id: 3, title: 'Payment Received', date: 'May 20, 2025 02:45 PM', amount: '+$100.00', amount_type: 'positive', invoice_number: 'INV-2025-0010', method: 'ABA Bank' },
  { id: 4, title: 'Payment Pending', date: 'May 10, 2025 11:20 AM', amount: '-$120.00', amount_type: 'negative', invoice_number: 'INV-2025-0008', method: 'Pending' },
]

const defaultInvoices: InvoiceItem[] = [
  { id: 1, invoice_number: 'INV-2025-0012', course_name: 'Web Development Fundamentals', invoice_date: 'May 28, 2025', due_date: 'Jun 07, 2025', amount: '$120.00', amount_raw: 120, status: 'Paid', status_type: 'paid', payment_method: 'ABA Bank', payment_method_sub: '•••• 4567', transaction_id: 'TXN-ABA-98421045', receipt_number: 'REC-2025-0012' },
  { id: 2, invoice_number: 'INV-2025-0011', course_name: 'React Development', invoice_date: 'May 25, 2025', due_date: 'Jun 04, 2025', amount: '$150.00', amount_raw: 150, status: 'Paid', status_type: 'paid', payment_method: 'Visa Card', payment_method_sub: '•••• 7890', transaction_id: 'TXN-VISA-67210982', receipt_number: 'REC-2025-0011' },
  { id: 3, invoice_number: 'INV-2025-0010', course_name: 'Database Design', invoice_date: 'May 20, 2025', due_date: 'May 30, 2025', amount: '$100.00', amount_raw: 100, status: 'Paid', status_type: 'paid', payment_method: 'ABA Bank', payment_method_sub: '•••• 4567', transaction_id: 'TXN-ABA-54120984', receipt_number: 'REC-2025-0010' },
  { id: 4, invoice_number: 'INV-2025-0009', course_name: 'Python Programming', invoice_date: 'May 15, 2025', due_date: 'May 25, 2025', amount: '$150.00', amount_raw: 150, status: 'Paid', status_type: 'paid', payment_method: 'Wing', payment_method_sub: '•••• 1234', transaction_id: 'TXN-WING-45129871', receipt_number: 'REC-2025-0009' },
  { id: 5, invoice_number: 'INV-2025-0008', course_name: 'UI/UX Design Basics', invoice_date: 'May 10, 2025', due_date: 'May 20, 2025', amount: '$120.00', amount_raw: 120, status: 'Pending', status_type: 'pending', payment_method: '—', payment_method_sub: '', transaction_id: null, receipt_number: null },
  { id: 6, invoice_number: 'INV-2025-0007', course_name: 'JavaScript Advanced', invoice_date: 'May 05, 2025', due_date: 'May 15, 2025', amount: '$180.00', amount_raw: 180, status: 'Overdue', status_type: 'overdue', payment_method: '—', payment_method_sub: '', transaction_id: null, receipt_number: null },
  { id: 7, invoice_number: 'INV-2025-0006', course_name: 'Node.js Backend', invoice_date: 'Apr 28, 2025', due_date: 'May 08, 2025', amount: '$180.00', amount_raw: 180, status: 'Pending', status_type: 'pending', payment_method: '—', payment_method_sub: '', transaction_id: null, receipt_number: null },
  { id: 8, invoice_number: 'INV-2025-0005', course_name: 'Data Science Basics', invoice_date: 'Apr 20, 2025', due_date: 'Apr 30, 2025', amount: '$150.00', amount_raw: 150, status: 'Paid', status_type: 'paid', payment_method: 'ABA Bank', payment_method_sub: '•••• 4567', transaction_id: 'TXN-ABA-34120985', receipt_number: 'REC-2025-0005' },
  { id: 9, invoice_number: 'INV-2025-0004', course_name: 'Git & GitHub', invoice_date: 'Apr 15, 2025', due_date: 'Apr 25, 2025', amount: '$100.00', amount_raw: 100, status: 'Paid', status_type: 'paid', payment_method: 'Visa Card', payment_method_sub: '•••• 7890', transaction_id: 'TXN-VISA-23109842', receipt_number: 'REC-2025-0004' },
  { id: 10, invoice_number: 'INV-2025-0003', course_name: 'HTML & CSS Essentials', invoice_date: 'Apr 10, 2025', due_date: 'Apr 20, 2025', amount: '$100.00', amount_raw: 100, status: 'Paid', status_type: 'paid', payment_method: 'Wing', payment_method_sub: '•••• 1234', transaction_id: 'TXN-WING-12908341', receipt_number: 'REC-2025-0003' },
]

const summary = computed(() => props.analytics?.summary || defaultSummary)
const paymentSummary = computed(() => props.analytics?.payment_summary || defaultPaymentSummary)
const upcomingPayments = computed(() => props.analytics?.upcoming_payments || defaultUpcoming)
const recentTransactions = computed(() => props.analytics?.recent_transactions || defaultTransactions)
const invoices = computed(() => props.analytics?.invoices || defaultInvoices)

// Filter States
const activeTab = ref<string>(props.filters?.status || 'all')
const selectedCourse = ref<string>(props.filters?.course || 'all')
const selectedStatus = ref<string>(props.filters?.status || 'all')
const selectedDateRange = ref<string>(props.filters?.date_range || 'all')
const searchQuery = ref<string>(props.filters?.search || '')

// Modals State
const selectedInvoice = ref<InvoiceItem | null>(null)
const isInvoiceModalOpen = ref<boolean>(false)
const isReceiptModalOpen = ref<boolean>(false)
const isPaymentModalOpen = ref<boolean>(false)

// Payment Process Flow States
const selectedPaymentMethod = ref<'aba' | 'wing' | 'card' | 'bank'>('aba')
const isPaymentProcessing = ref<boolean>(false)
const isPaymentSuccess = ref<boolean>(false)
const isPaymentFailed = ref<boolean>(false)

const handleFilterChange = (overrideTab?: string) => {
  if (overrideTab) {
    activeTab.value = overrideTab
    selectedStatus.value = overrideTab
  }
  router.get('/student/payments/my-payments', {
    status: activeTab.value,
    course: selectedCourse.value,
    date_range: selectedDateRange.value,
    search: searchQuery.value,
    page: 1,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Open Modals
const openInvoiceModal = (inv: InvoiceItem) => {
  selectedInvoice.value = inv
  isInvoiceModalOpen.value = true
}

const openReceiptModal = (inv: InvoiceItem) => {
  selectedInvoice.value = inv
  isReceiptModalOpen.value = true
}

const openPaymentModal = (inv: InvoiceItem) => {
  selectedInvoice.value = inv
  selectedPaymentMethod.value = 'aba'
  isPaymentProcessing.value = false
  isPaymentSuccess.value = false
  isPaymentFailed.value = false
  isPaymentModalOpen.value = true
}

// Confirm Payment Action
const confirmPayment = () => {
  if (!selectedInvoice.value) return
  isPaymentProcessing.value = true

  setTimeout(() => {
    isPaymentProcessing.value = false
    isPaymentSuccess.value = true

    // Optimistically update invoice state
    if (selectedInvoice.value) {
      selectedInvoice.value.status = 'Paid'
      selectedInvoice.value.status_type = 'paid'
      selectedInvoice.value.payment_method = selectedPaymentMethod.value === 'aba' ? 'ABA Bank' : selectedPaymentMethod.value === 'wing' ? 'Wing' : 'Visa Card'
      selectedInvoice.value.payment_method_sub = '•••• ' + Math.floor(1000 + Math.random() * 9000)
      selectedInvoice.value.transaction_id = 'TXN-' + Math.floor(10000000 + Math.random() * 90000000)
      selectedInvoice.value.receipt_number = 'REC-2025-' + selectedInvoice.value.invoice_number.slice(-4)
    }
  }, 1800)
}
</script>

<template>
  <StudentLayout title="Course Fees & Invoices — Payment & Billing">
    <div class="space-y-6 max-w-7xl mx-auto pb-12">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
            <span>Course Fees & Invoices</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-400 text-lg">📄</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1 font-medium">
            View your course fees, invoices and payment status in one place.
          </p>
        </div>
      </div>

      <!-- ================= 2. TOP 5 SUMMARY METRIC CARDS ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        
        <!-- Card 1: Total Due -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-purple-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Total Due</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.total_due }}</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ summary.total_due_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📄
          </div>
        </div>

        <!-- Card 2: Paid Amount -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-blue-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Paid Amount</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.paid_amount }}</p>
            <p class="text-[10px] text-blue-400 font-medium font-mono">{{ summary.paid_amount_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-blue-600/20 border border-blue-500/30 text-blue-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            💳
          </div>
        </div>

        <!-- Card 3: Total Invoices -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-emerald-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Total Invoices</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.total_invoices }}</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ summary.total_inv_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            📑
          </div>
        </div>

        <!-- Card 4: Paid Invoices (Paid successfully) -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-emerald-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Total Invoices</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.paid_invoices }}</p>
            <p class="text-[10px] text-emerald-400 font-medium font-mono">{{ summary.paid_inv_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            ✓
          </div>
        </div>

        <!-- Card 5: Pending Invoices -->
        <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-2xl p-4 shadow-xl flex items-center justify-between group hover:border-amber-500/30 transition-all">
          <div class="space-y-0.5">
            <p class="text-[10px] text-slate-400 font-medium">Pending Invoices</p>
            <p class="text-2xl font-black text-white font-mono">{{ summary.pending_invoices }}</p>
            <p class="text-[10px] text-amber-400 font-medium font-mono">{{ summary.pending_inv_note }}</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-amber-600/20 border border-amber-500/30 text-amber-300 flex items-center justify-center text-base shadow-inner shrink-0 group-hover:scale-110 transition-transform">
            ⏳
          </div>
        </div>

      </div>

      <!-- ================= 3. MAIN SPLIT (LEFT: 8/12, RIGHT: 4/12) ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT COLUMN (8/12): INVOICES TABLE & CONTROLS ================= -->
        <div class="lg:col-span-8 space-y-6">

          <!-- STATUS TABS & FILTER CONTROLS BAR -->
          <div class="flex flex-wrap items-center justify-between gap-3 bg-[#0F172A]/80 border border-slate-800/80 rounded-2xl p-2.5 shadow-lg">
            
            <!-- Status Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto">
              <button
                v-for="tab in [
                  { key: 'all', label: 'All Invoices' },
                  { key: 'paid', label: 'Paid' },
                  { key: 'pending', label: 'Pending' },
                  { key: 'overdue', label: 'Overdue' },
                  { key: 'cancelled', label: 'Cancelled' }
                ]"
                :key="tab.key"
                @click="handleFilterChange(tab.key)"
                :class="[
                  activeTab === tab.key
                    ? 'bg-purple-600 text-white shadow-md shadow-purple-900/40'
                    : 'text-slate-400 hover:text-white',
                  'px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer'
                ]"
              >
                {{ tab.label }}
              </button>
            </div>

            <!-- Dropdown Filters & Export -->
            <div class="flex flex-wrap items-center gap-2">
              <select
                v-model="selectedCourse"
                @change="handleFilterChange()"
                class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
              >
                <option value="all">All Courses</option>
                <option value="Web Development Fundamentals">Web Development Fundamentals</option>
                <option value="React Development">React Development</option>
                <option value="Database Design">Database Design</option>
                <option value="Python Programming">Python Programming</option>
                <option value="UI/UX Design Basics">UI/UX Design Basics</option>
                <option value="JavaScript Advanced">JavaScript Advanced</option>
                <option value="Node.js Backend">Node.js Backend</option>
              </select>

              <select
                v-model="selectedStatus"
                @change="handleFilterChange(selectedStatus)"
                class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer"
              >
                <option value="all">All Status</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="overdue">Overdue</option>
              </select>

              <button
                class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-300 rounded-xl px-3 py-1.5 hover:text-white flex items-center gap-1.5 transition-colors cursor-pointer"
              >
                <span>📅</span>
                <span>Date Range ▾</span>
              </button>

              <button
                @click="alert('Exporting invoices statement...')"
                class="bg-slate-900 border border-slate-700/80 text-xs font-semibold text-slate-300 rounded-xl px-3 py-1.5 hover:text-white flex items-center gap-1.5 transition-colors cursor-pointer"
              >
                <span>⤓</span>
                <span>Export</span>
              </button>
            </div>

          </div>

          <!-- INVOICES TABLE CARD -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-4">
            <h3 class="text-sm font-bold text-white tracking-tight">Invoices</h3>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/90 text-slate-400 text-[10px] uppercase font-bold border-b border-slate-800">
                  <tr>
                    <th class="p-3">Invoice #</th>
                    <th class="p-3">Course</th>
                    <th class="p-3">Invoice Date</th>
                    <th class="p-3">Due Date</th>
                    <th class="p-3">Amount</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Payment Method</th>
                    <th class="p-3 text-right">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-medium">
                  <tr
                    v-for="inv in invoices"
                    :key="inv.id"
                    class="hover:bg-slate-800/30 transition-colors group"
                  >
                    <!-- Invoice # -->
                    <td class="p-3 font-mono text-slate-300 font-bold whitespace-nowrap">
                      {{ inv.invoice_number }}
                    </td>

                    <!-- Course -->
                    <td class="p-3 font-bold text-white max-w-[160px] truncate">
                      {{ inv.course_name }}
                    </td>

                    <!-- Invoice Date -->
                    <td class="p-3 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                      {{ inv.invoice_date }}
                    </td>

                    <!-- Due Date -->
                    <td class="p-3 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                      {{ inv.due_date }}
                    </td>

                    <!-- Amount -->
                    <td class="p-3 font-bold text-white font-mono">
                      {{ inv.amount }}
                    </td>

                    <!-- Status Badge -->
                    <td class="p-3">
                      <span
                        :class="[
                          inv.status_type === 'paid' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' :
                          inv.status_type === 'overdue' ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' :
                          'bg-amber-500/20 text-amber-300 border-amber-500/30',
                          'px-2.5 py-0.5 rounded-lg text-[10px] font-bold border'
                        ]"
                      >
                        {{ inv.status }}
                      </span>
                    </td>

                    <!-- Payment Method -->
                    <td class="p-3 text-slate-300 text-[11px]">
                      <div v-if="inv.payment_method !== '—'">
                        <span>{{ inv.payment_method }}</span>
                        <span v-if="inv.payment_method_sub" class="text-slate-500 font-mono block text-[9.5px]">{{ inv.payment_method_sub }}</span>
                      </div>
                      <span v-else class="text-slate-500">—</span>
                    </td>

                    <!-- Action -->
                    <td class="p-3 text-right">
                      <div v-if="inv.status_type === 'paid'" class="flex items-center justify-end gap-1.5">
                        <button
                          @click="openInvoiceModal(inv)"
                          class="p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 transition-colors"
                          title="View Invoice"
                        >
                          👁
                        </button>
                        <button
                          @click="openReceiptModal(inv)"
                          class="p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800 transition-colors"
                          title="Download Receipt"
                        >
                          ⤓
                        </button>
                      </div>

                      <div v-else class="flex items-center justify-end gap-1.5">
                        <button
                          @click="openPaymentModal(inv)"
                          :class="[
                            inv.status_type === 'overdue'
                              ? 'bg-rose-600 hover:bg-rose-500 text-white shadow-md shadow-rose-950/40'
                              : 'bg-amber-600 hover:bg-amber-500 text-white shadow-md shadow-amber-950/40',
                            'px-3 py-1 rounded-lg text-[10px] font-bold transition-all cursor-pointer active:scale-95 whitespace-nowrap'
                          ]"
                        >
                          Pay Now
                        </button>
                        <button
                          @click="openInvoiceModal(inv)"
                          class="p-1 rounded-lg text-slate-500 hover:text-white"
                          title="More options"
                        >
                          ⋮
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Table Pagination -->
            <div class="pt-3 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
              <span class="text-slate-400 text-[11px]">
                Showing 1 to 10 of 12 invoices
              </span>

              <div class="flex items-center gap-1.5">
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs disabled:opacity-40">
                  «
                </button>
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs">
                  ‹
                </button>
                <button class="w-7 h-7 rounded-lg bg-purple-600 text-white font-bold text-xs shadow-sm">
                  1
                </button>
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs">
                  2
                </button>
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs">
                  ›
                </button>
                <button class="w-7 h-7 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs">
                  »
                </button>
              </div>
            </div>

          </div>

          <!-- SECURE & EASY PAYMENTS CARD (BOTTOM LEFT) -->
          <div class="bg-gradient-to-r from-blue-950/70 via-[#0F172A] to-purple-950/70 border border-blue-900/50 rounded-3xl p-5 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="space-y-3 flex-1">
              <div>
                <h4 class="text-sm font-bold text-white">Secure & Easy Payments</h4>
                <p class="text-[11px] text-slate-400 mt-0.5">Your payments are securely processed using industry-standard encryption.</p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 text-xs">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-xl bg-emerald-600/20 border border-emerald-500/30 text-emerald-300 flex items-center justify-center text-xs shrink-0">
                    🛡️
                  </div>
                  <div>
                    <p class="font-bold text-white text-[11px]">100% Secure</p>
                    <p class="text-[9.5px] text-slate-400">Your data is protected</p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-xl bg-blue-600/20 border border-blue-500/30 text-blue-300 flex items-center justify-center text-xs shrink-0">
                    💳
                  </div>
                  <div>
                    <p class="font-bold text-white text-[11px]">Multiple Payment Methods</p>
                    <p class="text-[9.5px] text-slate-400">ABA, Wing, Visa, Mastercard & more</p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-xs shrink-0">
                    ⚡
                  </div>
                  <div>
                    <p class="font-bold text-white text-[11px]">Instant Confirmation</p>
                    <p class="text-[9.5px] text-slate-400">Get payment receipt instantly</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- 3D Graphic Placeholder -->
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-600 to-indigo-600 text-white flex items-center justify-center text-2xl shadow-xl shrink-0">
              👛
            </div>
          </div>

        </div>

        <!-- ================= RIGHT COLUMN (4/12): WIDGETS ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- WIDGET 1: PAYMENT SUMMARY DONUT CHART -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-3">
              <h3 class="text-sm font-bold text-white tracking-tight">Payment Summary</h3>
            </div>

            <div class="flex items-center justify-between gap-4">
              <!-- Donut Chart -->
              <div class="relative w-24 h-24 flex items-center justify-center shrink-0">
                <svg class="w-24 h-24 -rotate-90 transform" viewBox="0 0 36 36">
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#1E293B" stroke-width="4.5" />
                  <!-- Paid: 88% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#10B981" stroke-width="4.5" stroke-dasharray="88, 100" stroke-dashoffset="0" />
                  <!-- Pending: 12% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F59E0B" stroke-width="4.5" stroke-dasharray="12, 100" stroke-dashoffset="-88" />
                  <!-- Overdue: 8% -->
                  <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#EF4444" stroke-width="4.5" stroke-dasharray="8, 100" stroke-dashoffset="-92" />
                </svg>

                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                  <span class="text-xs font-black text-white font-mono leading-none">{{ paymentSummary.total_amount }}</span>
                  <span class="text-[7.5px] text-slate-400 mt-0.5 font-medium">All time</span>
                </div>
              </div>

              <!-- Legend Breakdown -->
              <div class="space-y-1.5 text-xs flex-1">
                <div
                  v-for="item in paymentSummary.items"
                  :key="item.label"
                  class="flex items-center justify-between text-[11px]"
                >
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: item.color }"></span>
                    <span class="text-slate-300 font-medium">{{ item.label }}</span>
                  </div>
                  <span class="font-bold text-white font-mono">{{ item.amount }} ({{ item.percentage }}%)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- WIDGET 2: UPCOMING PAYMENTS -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Upcoming Payments</h3>
              <Link href="/student/payments/my-payments?status=pending" class="text-xs text-purple-400 font-bold hover:underline">
                View All
              </Link>
            </div>

            <div class="space-y-2.5 text-xs">
              <div
                v-for="item in upcomingPayments"
                :key="item.id"
                class="p-3 rounded-2xl bg-slate-900/70 border border-slate-800/60 flex items-center justify-between gap-3 hover:border-purple-500/30 transition-all"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      item.color === 'rose' ? 'bg-rose-600/20 text-rose-300 border-rose-500/30' : 'bg-amber-600/20 text-amber-300 border-amber-500/30',
                      'w-7 h-7 rounded-xl flex items-center justify-center text-xs shrink-0 border'
                    ]"
                  >
                    📄
                  </div>
                  <div class="min-w-0">
                    <p class="font-bold text-white truncate max-w-[130px]">{{ item.course }}</p>
                    <p class="text-[10px] text-slate-400">{{ item.due_date }}</p>
                  </div>
                </div>

                <div class="text-right">
                  <p class="font-bold text-white font-mono">{{ item.amount }}</p>
                  <span
                    :class="[
                      item.color === 'rose' ? 'text-rose-400' : 'text-amber-400',
                      'text-[9.5px] font-bold block'
                    ]"
                  >
                    {{ item.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- WIDGET 3: RECENT TRANSACTIONS -->
          <div class="bg-[#0F172A]/90 border border-slate-800/80 rounded-3xl p-5 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-white tracking-tight">Recent Transactions</h3>
              <Link href="/student/payments/transactions" class="text-xs text-purple-400 font-bold hover:underline">
                View All
              </Link>
            </div>

            <div class="space-y-2.5 text-xs">
              <div
                v-for="tx in recentTransactions"
                :key="tx.id"
                class="p-2.5 rounded-2xl bg-slate-900/70 border border-slate-800/60 flex items-center justify-between gap-3"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <div
                    :class="[
                      tx.amount_type === 'positive' ? 'bg-emerald-600/20 text-emerald-300 border-emerald-500/30' : 'bg-amber-600/20 text-amber-300 border-amber-500/30',
                      'w-7 h-7 rounded-xl flex items-center justify-center text-xs shrink-0 border'
                    ]"
                  >
                    {{ tx.amount_type === 'positive' ? '⬇' : '⏳' }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-bold text-white truncate">{{ tx.title }}</p>
                    <p class="text-[9.5px] text-slate-400 font-mono">{{ tx.date }}</p>
                  </div>
                </div>

                <div class="text-right">
                  <p
                    :class="[
                      tx.amount_type === 'positive' ? 'text-emerald-400' : 'text-amber-400',
                      'font-bold font-mono text-xs'
                    ]"
                  >
                    {{ tx.amount }}
                  </p>
                  <p class="text-[9.5px] text-slate-500 font-mono">{{ tx.invoice_number }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- WIDGET 4: NEED HELP? -->
          <div class="bg-gradient-to-br from-[#10132B] via-[#0F172A] to-[#1E1138] border border-purple-900/50 rounded-3xl p-5 shadow-2xl space-y-3.5">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-white">Need Help?</h3>
                <p class="text-xs text-slate-400 mt-0.5">Having issues with payments or invoices? Our support team is here to help you.</p>
              </div>
              <div class="w-12 h-12 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-300 flex items-center justify-center text-xl shrink-0">
                🎧
              </div>
            </div>

            <a
              href="mailto:support@spilms.tech"
              class="w-full py-2.5 rounded-2xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-950/50 flex items-center justify-center gap-2 transition-colors cursor-pointer"
            >
              <span>🎧</span>
              <span>Contact Support</span>
            </a>
          </div>

        </div>

      </div>

    </div>

    <!-- ================= MODAL 1: VIEW INVOICE DETAILS ================= -->
    <div
      v-if="isInvoiceModalOpen && selectedInvoice"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-lg w-full p-6 space-y-5 shadow-2xl relative">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-base font-black text-white">Invoice Details</h3>
            <p class="text-xs font-mono text-purple-300">{{ selectedInvoice.invoice_number }}</p>
          </div>
          <button
            @click="isInvoiceModalOpen = false"
            class="w-7 h-7 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs"
          >
            ✕
          </button>
        </div>

        <div class="p-4 bg-slate-950 rounded-2xl border border-slate-800 space-y-3 text-xs">
          <div class="flex justify-between border-b border-slate-800/80 pb-2">
            <span class="text-slate-400">Course / Program:</span>
            <span class="font-bold text-white">{{ selectedInvoice.course_name }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-2">
            <span class="text-slate-400">Invoice Date:</span>
            <span class="font-mono text-slate-300">{{ selectedInvoice.invoice_date }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-2">
            <span class="text-slate-400">Due Date:</span>
            <span class="font-mono text-slate-300">{{ selectedInvoice.due_date }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-800/80 pb-2">
            <span class="text-slate-400">Status:</span>
            <span
              :class="[
                selectedInvoice.status_type === 'paid' ? 'text-emerald-400' : 'text-amber-400',
                'font-bold uppercase'
              ]"
            >
              {{ selectedInvoice.status }}
            </span>
          </div>
          <div class="flex justify-between pt-1">
            <span class="text-slate-300 font-bold">Total Amount Due:</span>
            <span class="text-base font-black text-white font-mono">{{ selectedInvoice.amount }}</span>
          </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-xs">
          <button
            @click="isInvoiceModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold"
          >
            Close
          </button>
          <div class="flex items-center gap-2">
            <button
              v-if="selectedInvoice.status_type === 'paid'"
              @click="openReceiptModal(selectedInvoice); isInvoiceModalOpen = false"
              class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold border border-slate-700"
            >
              Download Receipt
            </button>
            <button
              v-else
              @click="openPaymentModal(selectedInvoice); isInvoiceModalOpen = false"
              class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md"
            >
              Pay Now
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 2: DOWNLOAD RECEIPT PREVIEW ================= -->
    <div
      v-if="isReceiptModalOpen && selectedInvoice"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white text-slate-900 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
          <div>
            <h3 class="text-base font-black font-serif uppercase tracking-wider text-slate-900">Official e-Receipt</h3>
            <p class="text-[10px] text-slate-500 font-mono">{{ selectedInvoice.receipt_number || 'REC-2025-0012' }}</p>
          </div>
          <button
            @click="isReceiptModalOpen = false"
            class="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs"
          >
            ✕
          </button>
        </div>

        <div class="space-y-2.5 text-xs">
          <div class="flex justify-between">
            <span class="text-slate-500">Institution:</span>
            <span class="font-bold">Saint Paul Institute (SPI)</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Student Name:</span>
            <span class="font-bold">Sok Pisey (STU2024001)</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Course / Program:</span>
            <span class="font-bold">{{ selectedInvoice.course_name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Payment Method:</span>
            <span class="font-bold">{{ selectedInvoice.payment_method }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Transaction ID:</span>
            <span class="font-mono">{{ selectedInvoice.transaction_id || 'TXN-ABA-98421045' }}</span>
          </div>
          <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm">
            <span>Amount Paid:</span>
            <span class="text-emerald-600 font-mono">{{ selectedInvoice.amount }}</span>
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
            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow-md flex items-center gap-1.5"
          >
            <span>⤓</span>
            <span>Download PDF</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL 3: PAY NOW PAYMENT GATEWAY ================= -->
    <div
      v-if="isPaymentModalOpen && selectedInvoice"
      class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-slate-900 border border-purple-500/40 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div>
            <h3 class="text-base font-black text-white">Complete Your Payment</h3>
            <p class="text-xs text-slate-400 font-mono">{{ selectedInvoice.invoice_number }} • {{ selectedInvoice.amount }}</p>
          </div>
          <button
            v-if="!isPaymentProcessing"
            @click="isPaymentModalOpen = false"
            class="w-7 h-7 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center font-bold text-xs"
          >
            ✕
          </button>
        </div>

        <!-- STATE A: PROCESSING LOADING -->
        <div v-if="isPaymentProcessing" class="py-10 text-center space-y-3">
          <div class="w-12 h-12 rounded-full border-4 border-purple-500/20 border-t-purple-500 animate-spin mx-auto"></div>
          <h4 class="text-sm font-bold text-white">Processing your payment securely...</h4>
          <p class="text-xs text-slate-400">Verifying KHQR transaction and bank callback</p>
        </div>

        <!-- STATE B: SUCCESS STATE -->
        <div v-else-if="isPaymentSuccess" class="py-6 text-center space-y-4">
          <div class="w-14 h-14 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center text-3xl mx-auto shadow-lg shadow-emerald-950/50">
            ✓
          </div>
          <div>
            <h4 class="text-base font-black text-emerald-400">Payment Successful</h4>
            <p class="text-xs text-slate-300 mt-1">Your payment of {{ selectedInvoice.amount }} has been completed.</p>
          </div>

          <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 text-xs font-mono text-left space-y-1">
            <div class="flex justify-between"><span class="text-slate-500">Method:</span><span class="text-slate-300">{{ selectedInvoice.payment_method }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">TXN ID:</span><span class="text-slate-300">{{ selectedInvoice.transaction_id }}</span></div>
          </div>

          <button
            @click="isPaymentModalOpen = false"
            class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md"
          >
            Done
          </button>
        </div>

        <!-- STATE C: SELECT PAYMENT METHOD & CONFIRM -->
        <div v-else class="space-y-4">
          
          <div class="p-3 bg-slate-950 rounded-2xl border border-slate-800 text-xs space-y-1">
            <div class="flex justify-between"><span class="text-slate-400">Course:</span><span class="font-bold text-white">{{ selectedInvoice.course_name }}</span></div>
            <div class="flex justify-between"><span class="text-slate-400">Amount Due:</span><span class="font-bold text-white font-mono">{{ selectedInvoice.amount }}</span></div>
          </div>

          <!-- Method Selection -->
          <div class="space-y-2">
            <p class="text-xs font-bold text-white uppercase tracking-wider">Select Payment Method:</p>
            
            <div class="grid grid-cols-2 gap-2 text-xs">
              <button
                @click="selectedPaymentMethod = 'aba'"
                :class="[
                  selectedPaymentMethod === 'aba'
                    ? 'bg-purple-600/20 border-purple-500 text-white shadow-md'
                    : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white',
                  'p-3 rounded-2xl border flex flex-col items-center text-center gap-1 transition-all cursor-pointer'
                ]"
              >
                <span class="text-base">💳</span>
                <span class="font-bold">ABA KHQR</span>
                <span class="text-[9px] text-emerald-400 font-mono">Scan & Pay</span>
              </button>

              <button
                @click="selectedPaymentMethod = 'wing'"
                :class="[
                  selectedPaymentMethod === 'wing'
                    ? 'bg-purple-600/20 border-purple-500 text-white shadow-md'
                    : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white',
                  'p-3 rounded-2xl border flex flex-col items-center text-center gap-1 transition-all cursor-pointer'
                ]"
              >
                <span class="text-base">🪽</span>
                <span class="font-bold">Wing Bank</span>
                <span class="text-[9px] text-blue-400 font-mono">Instant Pay</span>
              </button>

              <button
                @click="selectedPaymentMethod = 'card'"
                :class="[
                  selectedPaymentMethod === 'card'
                    ? 'bg-purple-600/20 border-purple-500 text-white shadow-md'
                    : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white',
                  'p-3 rounded-2xl border flex flex-col items-center text-center gap-1 transition-all cursor-pointer'
                ]"
              >
                <span class="text-base">💳</span>
                <span class="font-bold">Visa / Master</span>
                <span class="text-[9px] text-slate-500 font-mono">Debit/Credit</span>
              </button>

              <button
                @click="selectedPaymentMethod = 'bank'"
                :class="[
                  selectedPaymentMethod === 'bank'
                    ? 'bg-purple-600/20 border-purple-500 text-white shadow-md'
                    : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white',
                  'p-3 rounded-2xl border flex flex-col items-center text-center gap-1 transition-all cursor-pointer'
                ]"
              >
                <span class="text-base">🏛️</span>
                <span class="font-bold">Bank Transfer</span>
                <span class="text-[9px] text-slate-500 font-mono">Direct Deposit</span>
              </button>
            </div>
          </div>

          <!-- Dynamic KHQR Preview for ABA -->
          <div v-if="selectedPaymentMethod === 'aba'" class="p-3 bg-slate-950 rounded-2xl border border-slate-800 text-center space-y-2">
            <div class="w-28 h-28 bg-white rounded-xl p-1.5 mx-auto border-2 border-emerald-500 shadow-md">
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=ABA-PAYWAY-ELMS-SPI-2025" alt="ABA KHQR" class="w-full h-full object-contain" />
            </div>
            <p class="text-[10px] text-slate-400">Scan with any Bakong or Banking App to pay <strong>{{ selectedInvoice.amount }}</strong></p>
          </div>

          <!-- Action -->
          <button
            @click="confirmPayment"
            class="w-full py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/50 transition-all cursor-pointer active:scale-95"
          >
            Confirm &amp; Pay {{ selectedInvoice.amount }}
          </button>
        </div>

      </div>
    </div>

  </StudentLayout>
</template>
