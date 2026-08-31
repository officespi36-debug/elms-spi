<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'

interface SavedMethod {
  id: number
  provider: 'aba' | 'wing' | 'visa' | 'mastercard' | 'bank'
  name: string
  account_holder: string
  account_number: string
  masked_info: string
  expiry?: string
  is_primary: boolean
  status: string
  added_date: string
}

interface AutoPayData {
  enabled: boolean
  currency: string
  threshold: string
  reminder: string
}

interface HistorySummaryData {
  total_paid: string
  total_paid_period: string
  last_payment: string
  last_payment_date: string
  successful_payments: number
  successful_period: string
  failed_payments: number
  failed_period: string
}

interface BillingSummaryData {
  total_invoices: number
  paid_invoices: number
  pending_invoices: number
  outstanding_balance: string
}

interface SettingsData {
  saved_methods: SavedMethod[]
  auto_pay: AutoPayData
  history_summary: HistorySummaryData
  billing_summary: BillingSummaryData
  default_currency: string
  security: {
    two_factor_enabled: boolean
    encryption_active: boolean
    fraud_protection: boolean
    monitoring_active: boolean
  }
  notifications: {
    payment_successful: boolean
    payment_pending: boolean
    payment_failed: boolean
    invoice_reminder: boolean
    due_date_reminder: boolean
  }
}

const props = defineProps<{
  settingsData?: SettingsData
}>()

// Default baseline data matching screenshot
const defaultData: SettingsData = {
  saved_methods: [
    {
      id: 1,
      provider: 'aba',
      name: 'ABA KHQR',
      account_holder: 'Sok Pisey',
      account_number: '002 348 567',
      masked_info: '002 348 567',
      is_primary: true,
      status: 'Verified',
      added_date: 'May 10, 2025',
    },
    {
      id: 2,
      provider: 'wing',
      name: 'Wing Account',
      account_holder: 'Sok Pisey',
      account_number: '092 345 678',
      masked_info: '092 345 678',
      is_primary: false,
      status: 'Verified',
      added_date: 'Apr 28, 2025',
    },
    {
      id: 3,
      provider: 'visa',
      name: 'Visa Card •••• 4567',
      account_holder: 'Sok Pisey',
      account_number: '•••• 4567',
      masked_info: '•••• 4567',
      expiry: '08/27',
      is_primary: false,
      status: 'Verified',
      added_date: 'Apr 15, 2025',
    },
    {
      id: 4,
      provider: 'mastercard',
      name: 'Mastercard •••• 7890',
      account_holder: 'Sok Pisey',
      account_number: '•••• 7890',
      masked_info: '•••• 7890',
      expiry: '11/26',
      is_primary: false,
      status: 'Verified',
      added_date: 'Apr 12, 2025',
    },
  ],
  auto_pay: {
    enabled: true,
    currency: 'KHR',
    threshold: '50,000',
    reminder: '1 day before',
  },
  history_summary: {
    total_paid: '1,320.00 KHR',
    total_paid_period: 'This Year',
    last_payment: '120.00 KHR',
    last_payment_date: 'May 28, 2025',
    successful_payments: 9,
    successful_period: 'This Year',
    failed_payments: 0,
    failed_period: 'This Year',
  },
  billing_summary: {
    total_invoices: 12,
    paid_invoices: 9,
    pending_invoices: 3,
    outstanding_balance: '180.00 KHR',
  },
  default_currency: 'KHR',
  security: {
    two_factor_enabled: true,
    encryption_active: true,
    fraud_protection: true,
    monitoring_active: true,
  },
  notifications: {
    payment_successful: true,
    payment_pending: true,
    payment_failed: true,
    invoice_reminder: true,
    due_date_reminder: true,
  },
}

// Active Settings Tab
type TabKey = 'methods' | 'auto_pay' | 'billing' | 'notifications' | 'security'
const activeTab = ref<TabKey>('methods')

// Reactive states
const savedMethods = ref<SavedMethod[]>(props.settingsData?.saved_methods || defaultData.saved_methods)
const autoPay = ref<AutoPayData>(props.settingsData?.auto_pay || defaultData.auto_pay)
const historySummary = computed(() => props.settingsData?.history_summary || defaultData.history_summary)
const billingSummary = computed(() => props.settingsData?.billing_summary || defaultData.billing_summary)
const selectedCurrency = ref<string>(props.settingsData?.default_currency || 'KHR')
const securitySettings = ref(props.settingsData?.security || defaultData.security)
const notificationSettings = ref(props.settingsData?.notifications || defaultData.notifications)

// Feedback Toast
const toastMessage = ref<string | null>(null)
const showToast = (msg: string) => {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = null
  }, 3000)
}

// Add Payment Method Modal State
const isAddModalOpen = ref(false)
const newMethodType = ref<'aba' | 'wing' | 'visa' | 'mastercard' | 'bank'>('aba')
const newAccountHolder = ref('Sok Pisey')
const newAccountNumber = ref('')
const newExpiry = ref('12/28')
const newCvv = ref('')

const saveNewMethod = () => {
  if (!newAccountNumber.value && (newMethodType.value === 'visa' || newMethodType.value === 'mastercard')) {
    newAccountNumber.value = '•••• ' + Math.floor(1000 + Math.random() * 9000)
  } else if (!newAccountNumber.value) {
    newAccountNumber.value = '0' + Math.floor(10000000 + Math.random() * 90000000)
  }

  const nameMap = {
    aba: 'ABA KHQR',
    wing: 'Wing Account',
    visa: `Visa Card ${newAccountNumber.value.startsWith('••••') ? newAccountNumber.value : '•••• ' + newAccountNumber.value.slice(-4)}`,
    mastercard: `Mastercard ${newAccountNumber.value.startsWith('••••') ? newAccountNumber.value : '•••• ' + newAccountNumber.value.slice(-4)}`,
    bank: 'Bank Account (FTB)',
  }

  const newMethod: SavedMethod = {
    id: Date.now(),
    provider: newMethodType.value,
    name: nameMap[newMethodType.value],
    account_holder: newAccountHolder.value,
    account_number: newAccountNumber.value,
    masked_info: newAccountNumber.value,
    expiry: (newMethodType.value === 'visa' || newMethodType.value === 'mastercard') ? newExpiry.value : undefined,
    is_primary: savedMethods.value.length === 0,
    status: 'Verified',
    added_date: 'Today',
  }

  savedMethods.value.push(newMethod)
  isAddModalOpen.value = false
  newAccountNumber.value = ''
  showToast('Payment method added successfully!')
}

// Manage Menu & Modals
const selectedMethodForAction = ref<SavedMethod | null>(null)
const isManageMenuOpen = ref<number | null>(null)
const isRemoveModalOpen = ref(false)

const toggleManageMenu = (id: number) => {
  isManageMenuOpen.value = isManageMenuOpen.value === id ? null : id
}

const setAsPrimary = (method: SavedMethod) => {
  savedMethods.value.forEach(m => {
    m.is_primary = m.id === method.id
  })
  isManageMenuOpen.value = null
  showToast(`${method.name} is now your primary payment method.`)
}

const openRemoveModal = (method: SavedMethod) => {
  selectedMethodForAction.value = method
  isManageMenuOpen.value = null
  isRemoveModalOpen.value = true
}

const confirmRemoveMethod = () => {
  if (!selectedMethodForAction.value) return
  if (savedMethods.value.length <= 1) {
    alert('You must keep at least one payment method.')
    isRemoveModalOpen.value = false
    return
  }

  savedMethods.value = savedMethods.value.filter(m => m.id !== selectedMethodForAction.value?.id)
  if (selectedMethodForAction.value.is_primary && savedMethods.value.length > 0) {
    savedMethods.value[0].is_primary = true
  }
  isRemoveModalOpen.value = false
  showToast('Payment method removed successfully.')
}

const saveAutoPaySettings = () => {
  showToast('Auto Pay settings saved successfully!')
}

const handleCurrencyChange = () => {
  showToast(`Default currency changed to ${selectedCurrency.value}.`)
}
</script>

<template>
  <StudentLayout title="Payment Settings — Payment & Billing">
    <div class="space-y-6 max-w-7xl mx-auto pb-12">

      <!-- ================= 1. PAGE HEADER ================= -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
            <span>Payment Settings</span>
            <span class="inline-flex p-1.5 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-600 dark:text-purple-400 text-lg">⚙️</span>
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
            Manage your payment preferences, methods, notifications and security settings.
          </p>
        </div>
      </div>

      <!-- ================= 2. TOP SETTINGS TABS NAVIGATION ================= -->
      <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-2xl p-2 shadow-sm dark:shadow-lg overflow-x-auto">
        <div class="flex items-center gap-2 min-w-max">
          <button
            v-for="tab in [
              { key: 'methods', label: 'Payment Methods' },
              { key: 'auto_pay', label: 'Auto Payments' },
              { key: 'billing', label: 'Billing Information' },
              { key: 'notifications', label: 'Notifications' },
              { key: 'security', label: 'Security' },
            ]"
            :key="tab.key"
            @click="activeTab = tab.key as TabKey"
            :class="[
              activeTab === tab.key
                ? 'text-purple-700 dark:text-white border-b-2 border-purple-600 dark:border-purple-500 font-bold bg-purple-50 dark:bg-purple-600/10'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border-b-2 border-transparent font-medium',
              'px-4 py-2 text-xs transition-all cursor-pointer rounded-t-lg whitespace-nowrap'
            ]"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- ================= 3. MAIN 2-COLUMN LAYOUT ================= -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- ================= LEFT COLUMN (~70% / lg:col-span-8) ================= -->
        <div class="lg:col-span-8 space-y-6">

          <!-- TAB 1 & DEFAULT: PAYMENT METHODS & AUTO PAY & HISTORY SUMMARY -->
          <template v-if="activeTab === 'methods' || activeTab === 'auto_pay'">
            
            <!-- CARD 1: SAVED PAYMENT METHODS -->
            <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm dark:shadow-xl space-y-5">
              
              <!-- Card Header -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800/60 pb-4">
                <div>
                  <h2 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Saved Payment Methods</h2>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage your saved payment methods for faster checkout.</p>
                </div>

                <button
                  @click="isAddModalOpen = true"
                  class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-950/20 flex items-center gap-1.5 transition-all cursor-pointer self-start sm:self-auto active:scale-95"
                >
                  <span>+</span>
                  <span>Add Payment Method</span>
                </button>
              </div>

              <!-- Methods List -->
              <div class="space-y-3">
                <div
                  v-for="method in savedMethods"
                  :key="method.id"
                  class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-purple-500/30 transition-all relative group shadow-xs"
                >
                  <!-- Provider Icon & Info -->
                  <div class="flex items-center gap-3.5 min-w-0">
                    <!-- Icon badge -->
                    <div
                      :class="[
                        method.provider === 'aba' ? 'bg-[#005F86] text-white' :
                        method.provider === 'wing' ? 'bg-[#A4C639] text-slate-900' :
                        method.provider === 'visa' ? 'bg-[#1A1F71] text-white' :
                        'bg-slate-800 text-white',
                        'w-11 h-11 rounded-2xl flex items-center justify-center font-black text-xs shrink-0 shadow-inner'
                      ]"
                    >
                      <span v-if="method.provider === 'aba'">ABA</span>
                      <span v-else-if="method.provider === 'wing'">Wing</span>
                      <span v-else-if="method.provider === 'visa'">VISA</span>
                      <span v-else-if="method.provider === 'mastercard'" class="text-rose-400 text-base">●●</span>
                      <span v-else>🏛️</span>
                    </div>

                    <!-- Details -->
                    <div class="min-w-0 space-y-0.5">
                      <div class="flex items-center gap-2">
                        <p class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate">{{ method.name }}</p>
                        <span
                          v-if="method.is_primary"
                          class="px-2 py-0.5 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 text-[9.5px] font-bold"
                        >
                          Primary
                        </span>
                      </div>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                        {{ method.account_holder }} • {{ method.masked_info }}
                        <span v-if="method.expiry" class="text-slate-400 dark:text-slate-500"> (Exp: {{ method.expiry }})</span>
                      </p>
                    </div>
                  </div>

                  <!-- Status & Action Buttons -->
                  <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                    <!-- Verified Status -->
                    <div class="text-right sm:text-left">
                      <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                        <span>✓</span>
                        <span>{{ method.status }}</span>
                      </span>
                      <p class="text-[9.5px] text-slate-400 dark:text-slate-500 font-mono">Added on {{ method.added_date }}</p>
                    </div>

                    <!-- Manage & More Actions -->
                    <div class="flex items-center gap-1.5 relative">
                      <button
                        @click="setAsPrimary(method)"
                        v-if="!method.is_primary"
                        class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white border border-slate-200 dark:border-slate-700 text-xs font-bold transition-colors cursor-pointer flex items-center gap-1 shadow-xs"
                      >
                        <span>⚙</span>
                        <span>Manage</span>
                      </button>

                      <button
                        v-else
                        class="px-3 py-1.5 rounded-xl bg-purple-500/10 dark:bg-purple-600/20 text-purple-700 dark:text-purple-300 border border-purple-500/20 dark:border-purple-500/30 text-xs font-bold flex items-center gap-1"
                      >
                        <span>⚙</span>
                        <span>Manage</span>
                      </button>

                      <button
                        @click="toggleManageMenu(method.id)"
                        class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white border border-slate-200 dark:border-transparent flex items-center justify-center font-bold text-sm transition-colors cursor-pointer shadow-xs"
                        title="Options"
                      >
                        ⋮
                      </button>

                      <!-- Dropdown Menu -->
                      <div
                        v-if="isManageMenuOpen === method.id"
                        class="absolute right-0 top-10 z-20 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl p-1.5 shadow-2xl min-w-[150px] space-y-1 text-xs"
                      >
                        <button
                          v-if="!method.is_primary"
                          @click="setAsPrimary(method)"
                          class="w-full text-left px-3 py-1.5 rounded-xl text-slate-700 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                          ⭐ Set as Primary
                        </button>
                        <button
                          @click="isManageMenuOpen = null; showToast('Method info is up to date.')"
                          class="w-full text-left px-3 py-1.5 rounded-xl text-slate-700 dark:text-slate-300 hover:text-slate-950 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                          ✏️ Edit
                        </button>
                        <button
                          @click="openRemoveModal(method)"
                          class="w-full text-left px-3 py-1.5 rounded-xl text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors"
                        >
                          🗑️ Remove
                        </button>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

              <!-- Encrypted Note Footer -->
              <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5 pt-1">
                <span>🔒</span>
                <span>Your payment information is encrypted and secure.</span>
              </p>

            </div>

            <!-- CARD 2: AUTO PAY PREFERENCES -->
            <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm dark:shadow-xl space-y-5">
              
              <div class="border-b border-slate-100 dark:border-slate-800/60 pb-3">
                <h2 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Auto Pay Preferences</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Set up automatic payments for your invoices and subscriptions.</p>
              </div>

              <div class="space-y-4 text-xs">
                
                <!-- 1. Enable Auto Payment -->
                <div class="flex items-center justify-between gap-4 p-3.5 bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl shadow-xs">
                  <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-purple-500/10 dark:bg-purple-600/20 text-purple-700 dark:text-purple-300 border border-purple-500/20 dark:border-purple-500/30 flex items-center justify-center shrink-0 mt-0.5">
                      🔄
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm">Enable Auto Payment</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">Automatically pay for due invoices using your primary payment method.</p>
                    </div>
                  </div>

                  <!-- Toggle switch -->
                  <button
                    @click="autoPay.enabled = !autoPay.enabled"
                    :class="[
                      autoPay.enabled ? 'bg-purple-600' : 'bg-slate-300 dark:bg-slate-800',
                      'w-11 h-6 rounded-full transition-colors relative cursor-pointer shrink-0'
                    ]"
                  >
                    <span
                      :class="[
                        autoPay.enabled ? 'translate-x-5' : 'translate-x-1',
                        'w-4 h-4 rounded-full bg-white block transition-transform shadow-xs'
                      ]"
                    ></span>
                  </button>
                </div>

                <!-- 2. Payment Threshold -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl shadow-xs">
                  <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/10 dark:bg-blue-600/20 text-blue-700 dark:text-blue-300 border border-blue-500/20 dark:border-blue-500/30 flex items-center justify-center shrink-0 mt-0.5">
                      💲
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white text-xs">Payment Threshold</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">Automatically pay invoices above the selected amount.</p>
                    </div>
                  </div>

                  <div class="flex items-center gap-2 self-end sm:self-auto">
                    <select
                      v-model="autoPay.currency"
                      class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer shadow-xs"
                    >
                      <option value="KHR">KHR</option>
                      <option value="USD">USD</option>
                    </select>

                    <input
                      v-model="autoPay.threshold"
                      type="text"
                      class="w-28 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-xs font-mono font-bold text-slate-900 dark:text-white rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 shadow-xs"
                    />
                  </div>
                </div>

                <!-- 3. Payment Reminder -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl shadow-xs">
                  <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 dark:bg-amber-600/20 text-amber-700 dark:text-amber-300 border border-amber-500/20 dark:border-amber-500/30 flex items-center justify-center shrink-0 mt-0.5">
                      ⏰
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white text-xs">Payment Reminder</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">Get notified before auto payment is processed.</p>
                    </div>
                  </div>

                  <select
                    v-model="autoPay.reminder"
                    class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 rounded-xl px-3 py-1.5 focus:outline-none focus:border-purple-500 cursor-pointer self-end sm:self-auto shadow-xs"
                  >
                    <option value="1 day before">1 day before</option>
                    <option value="3 days before">3 days before</option>
                    <option value="5 days before">5 days before</option>
                    <option value="7 days before">7 days before</option>
                  </select>
                </div>

              </div>

              <!-- Save Button -->
              <div class="pt-2 flex justify-center">
                <button
                  @click="saveAutoPaySettings"
                  class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-purple-950/20 flex items-center gap-2 transition-all cursor-pointer active:scale-95"
                >
                  <span>💾</span>
                  <span>Save Auto Pay Settings</span>
                </button>
              </div>

            </div>

            <!-- CARD 3: PAYMENT HISTORY SUMMARY -->
            <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm dark:shadow-xl space-y-4">
              
              <div class="border-b border-slate-100 dark:border-slate-800/60 pb-3">
                <h2 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Payment History Summary</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Overview of your payment activity.</p>
              </div>

              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                
                <!-- Stat 1: Total Paid -->
                <div class="p-3.5 bg-slate-50 dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-1 shadow-xs">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Total Paid</span>
                    <span class="text-purple-600 dark:text-purple-400">💳</span>
                  </div>
                  <p class="text-base sm:text-lg font-black text-slate-900 dark:text-white font-mono">{{ historySummary.total_paid }}</p>
                  <p class="text-[9.5px] text-slate-500 font-medium">{{ historySummary.total_paid_period }}</p>
                </div>

                <!-- Stat 2: Last Payment -->
                <div class="p-3.5 bg-slate-50 dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-1 shadow-xs">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Last Payment</span>
                    <span class="text-emerald-600 dark:text-emerald-400">📄</span>
                  </div>
                  <p class="text-base sm:text-lg font-black text-slate-900 dark:text-white font-mono">{{ historySummary.last_payment }}</p>
                  <p class="text-[9.5px] text-slate-500 font-medium">{{ historySummary.last_payment_date }}</p>
                </div>

                <!-- Stat 3: Successful Payments -->
                <div class="p-3.5 bg-slate-50 dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-1 shadow-xs">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Successful Payments</span>
                    <span class="text-emerald-600 dark:text-emerald-400">✓</span>
                  </div>
                  <p class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ historySummary.successful_payments }}</p>
                  <p class="text-[9.5px] text-slate-500 font-medium">{{ historySummary.successful_period }}</p>
                </div>

                <!-- Stat 4: Failed Payments -->
                <div class="p-3.5 bg-slate-50 dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-1 shadow-xs">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Failed Payments</span>
                    <span class="text-rose-500 dark:text-rose-400">✕</span>
                  </div>
                  <p class="text-base sm:text-lg font-black text-rose-500 dark:text-rose-400 font-mono">{{ historySummary.failed_payments }}</p>
                  <p class="text-[9.5px] text-slate-500 font-medium">{{ historySummary.failed_period }}</p>
                </div>

              </div>

            </div>

          </template>

          <!-- TAB 2: BILLING INFORMATION -->
          <div v-else-if="activeTab === 'billing'" class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm dark:shadow-xl space-y-4 text-xs">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Billing Information</h2>
            <p class="text-slate-500 dark:text-slate-400">Manage your institution billing address, tax identification and invoice details.</p>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div class="space-y-1">
                <label class="text-slate-600 dark:text-slate-400 font-bold">Billing Name</label>
                <input type="text" value="Sok Pisey" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-bold" />
              </div>
              <div class="space-y-1">
                <label class="text-slate-600 dark:text-slate-400 font-bold">Student Identification</label>
                <input type="text" value="STU2024001" readonly class="w-full bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-slate-500 dark:text-slate-400 font-mono" />
              </div>
              <div class="space-y-1">
                <label class="text-slate-600 dark:text-slate-400 font-bold">Billing Email</label>
                <input type="email" value="pisey.sok@student.spilms.tech" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white" />
              </div>
              <div class="space-y-1">
                <label class="text-slate-600 dark:text-slate-400 font-bold">Campus Location</label>
                <input type="text" value="Saint Paul Institute, Tram Kak, Takeo" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white" />
              </div>
            </div>

            <div class="pt-3">
              <button @click="showToast('Billing information updated successfully!')" class="px-5 py-2 rounded-xl bg-purple-600 text-white font-bold shadow-md cursor-pointer">
                Save Billing Details
              </button>
            </div>
          </div>

          <!-- TAB 3: NOTIFICATIONS -->
          <div v-else-if="activeTab === 'notifications'" class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm dark:shadow-xl space-y-4 text-xs">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Payment Notifications</h2>
            <p class="text-slate-500 dark:text-slate-400">Configure which email and SMS alerts you receive for invoices and payments.</p>

            <div class="space-y-3 pt-2">
              <div
                v-for="item in [
                  { key: 'payment_successful', label: 'Payment Successful', desc: 'Get notified when a payment succeeds' },
                  { key: 'payment_pending', label: 'Payment Pending', desc: 'Alerts when payment confirmation is waiting' },
                  { key: 'payment_failed', label: 'Payment Failed', desc: 'Instant alerts when a transaction fails' },
                  { key: 'invoice_reminder', label: 'Invoice Reminder', desc: 'Receive invoice announcements before due date' },
                  { key: 'due_date_reminder', label: 'Payment Due Reminder', desc: 'Urgent reminder on invoice due date' },
                ]"
                :key="item.key"
                class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs"
              >
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">{{ item.label }}</p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">{{ item.desc }}</p>
                </div>
                <input type="checkbox" checked class="w-4 h-4 accent-purple-600 rounded cursor-pointer" />
              </div>
            </div>

            <div class="pt-2">
              <button @click="showToast('Notification preferences updated!')" class="px-5 py-2 rounded-xl bg-purple-600 text-white font-bold shadow-md cursor-pointer">
                Save Preferences
              </button>
            </div>
          </div>

          <!-- TAB 4: SECURITY -->
          <div v-else-if="activeTab === 'security'" class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-6 shadow-sm dark:shadow-xl space-y-4 text-xs">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Payment Security &amp; Compliance</h2>
            <p class="text-slate-500 dark:text-slate-400">Manage fraud protection, authentication, and secure checkout settings.</p>

            <div class="space-y-3 pt-2">
              <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs">
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">Two-Factor Authentication (2FA)</p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">Require OTP or biometric authentication for all payment transactions</p>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-500/30 text-[10px]">
                  Enabled
                </span>
              </div>

              <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs">
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">Login Verification</p>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400">Alert on new device logins before viewing payment methods</p>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-500/30 text-[10px]">
                  Active
                </span>
              </div>
            </div>
          </div>

        </div>

        <!-- ================= RIGHT COLUMN (~30% / lg:col-span-4) ================= -->
        <div class="lg:col-span-4 space-y-6">

          <!-- CARD 1: BILLING SUMMARY -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Billing Summary</h3>
            </div>

            <div class="space-y-2.5 text-xs">
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-slate-900/60">
                <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2"><span>📑</span> Total Invoices</span>
                <span class="font-bold text-slate-900 dark:text-white font-mono">{{ billingSummary.total_invoices }}</span>
              </div>

              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-slate-900/60">
                <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2"><span>✓</span> Paid Invoices</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ billingSummary.paid_invoices }}</span>
              </div>

              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-slate-900/60">
                <span class="text-slate-600 dark:text-slate-400 flex items-center gap-2"><span>⏳</span> Pending Invoices</span>
                <span class="font-bold text-amber-600 dark:text-amber-400 font-mono">{{ billingSummary.pending_invoices }}</span>
              </div>

              <div class="flex items-center justify-between p-2.5 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20">
                <span class="text-amber-800 dark:text-amber-300 font-bold flex items-center gap-2"><span>💰</span> Outstanding Balance</span>
                <span class="font-black text-amber-700 dark:text-amber-400 font-mono">{{ billingSummary.outstanding_balance }}</span>
              </div>

              <Link
                href="/student/payments/my-payments"
                class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-950/20 flex items-center justify-center gap-1.5 transition-all cursor-pointer"
              >
                <span>View My Invoices</span>
                <span>→</span>
              </Link>
            </div>
          </div>

          <!-- CARD 2: DEFAULT CURRENCY -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>💲</span>
                <span>Default Currency</span>
              </h3>
            </div>

            <div class="space-y-2 text-xs">
              <div>
                <p class="font-bold text-slate-900 dark:text-white text-[11px]">Preferred Currency</p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Select your default currency for payments.</p>
              </div>

              <select
                v-model="selectedCurrency"
                @change="handleCurrencyChange"
                class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:border-purple-500 cursor-pointer"
              >
                <option value="KHR">KHR - Cambodian Riel (៛)</option>
                <option value="USD">USD - US Dollar ($)</option>
              </select>

              <p class="text-[10px] text-slate-500 dark:text-slate-400 flex items-center gap-1 pt-1">
                <span>ℹ️</span>
                <span>All transactions will be processed in this currency.</span>
              </p>
            </div>
          </div>

          <!-- CARD 3: PAYMENT SECURITY -->
          <div class="bg-white dark:bg-[#0F172A]/90 border border-slate-200/90 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3">
            <div class="border-b border-slate-100 dark:border-slate-800/60 pb-2.5">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Payment Security</h3>
            </div>

            <div class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
              <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                <span class="text-[11px]">Two-factor authentication enabled</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                <span class="text-[11px]">Secure payment encryption</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                <span class="text-[11px]">Fraud protection active</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-[10px] font-bold">✓</span>
                <span class="text-[11px]">Regular security monitoring</span>
              </div>
            </div>
          </div>

          <!-- CARD 4: NEED HELP? -->
          <div class="bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/60 dark:from-[#12142E] dark:via-[#0F172A] dark:to-[#1F1138] border border-purple-200 dark:border-purple-900/40 rounded-3xl p-5 shadow-sm dark:shadow-xl space-y-3.5 text-xs">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Need Help?</h3>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5">If you need help with payment settings or have any questions.</p>
              </div>
              <div class="w-10 h-10 rounded-2xl bg-purple-500/10 dark:bg-purple-600/20 border border-purple-500/20 dark:border-purple-500/30 text-purple-600 dark:text-purple-300 flex items-center justify-center text-xl shrink-0">
                🎧
              </div>
            </div>

            <a
              href="mailto:support@spilms.tech"
              class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-md shadow-purple-950/20 flex items-center justify-center gap-2 transition-all cursor-pointer"
            >
              <span>🎧</span>
              <span>Contact Support</span>
            </a>
          </div>

        </div>

      </div>

    </div>

    <!-- ================= MODAL 1: ADD PAYMENT METHOD MODAL ================= -->
    <div
      v-if="isAddModalOpen"
      class="fixed inset-0 z-50 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-purple-500/40 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl relative">
        
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
          <div>
            <h3 class="text-base font-black text-slate-900 dark:text-white">Add Payment Method</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Save a new payment method for faster checkout</p>
          </div>
          <button
            @click="isAddModalOpen = false"
            class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white flex items-center justify-center font-bold text-xs cursor-pointer transition-colors"
          >
            ✕
          </button>
        </div>

        <!-- Provider Selection Grid -->
        <div class="grid grid-cols-2 gap-2 text-xs">
          <button
            v-for="p in [
              { key: 'aba', label: 'ABA KHQR', icon: '💳' },
              { key: 'wing', label: 'Wing Account', icon: '🪽' },
              { key: 'visa', label: 'Visa Card', icon: '💳' },
              { key: 'mastercard', label: 'Mastercard', icon: '💳' },
            ]"
            :key="p.key"
            @click="newMethodType = p.key as any"
            :class="[
              newMethodType === p.key
                ? 'bg-purple-50 dark:bg-purple-600/20 border-purple-500 text-purple-900 dark:text-white font-bold'
                : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-950 dark:hover:text-white',
              'p-2.5 rounded-xl border flex items-center gap-2 font-bold transition-all cursor-pointer shadow-xs'
            ]"
          >
            <span>{{ p.icon }}</span>
            <span>{{ p.label }}</span>
          </button>
        </div>

        <!-- Dynamic Form Fields -->
        <div class="space-y-3 text-xs">
          <div class="space-y-1">
            <label class="text-slate-600 dark:text-slate-400 font-bold">Account Holder Name</label>
            <input
              v-model="newAccountHolder"
              type="text"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-bold focus:outline-none focus:border-purple-500"
            />
          </div>

          <div class="space-y-1">
            <label class="text-slate-600 dark:text-slate-400 font-bold">
              {{ (newMethodType === 'visa' || newMethodType === 'mastercard') ? 'Card Number' : 'Account Number / Phone' }}
            </label>
            <input
              v-model="newAccountNumber"
              type="text"
              :placeholder="(newMethodType === 'visa' || newMethodType === 'mastercard') ? '•••• •••• •••• 1234' : '002 348 567'"
              class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-mono focus:outline-none focus:border-purple-500"
            />
          </div>

          <div v-if="newMethodType === 'visa' || newMethodType === 'mastercard'" class="grid grid-cols-2 gap-2">
            <div class="space-y-1">
              <label class="text-slate-600 dark:text-slate-400 font-bold">Expiry Date</label>
              <input v-model="newExpiry" type="text" placeholder="MM/YY" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-mono" />
            </div>
            <div class="space-y-1">
              <label class="text-slate-600 dark:text-slate-400 font-bold">CVV</label>
              <input v-model="newCvv" type="password" placeholder="•••" maxlength="4" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-mono" />
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
          <button
            @click="isAddModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-200 cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="saveNewMethod"
            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold shadow-md cursor-pointer"
          >
            Save Method
          </button>
        </div>

      </div>
    </div>

    <!-- ================= MODAL 2: REMOVE CONFIRMATION MODAL ================= -->
    <div
      v-if="isRemoveModalOpen && selectedMethodForAction"
      class="fixed inset-0 z-50 bg-slate-950/70 dark:bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-500/40 rounded-3xl max-w-sm w-full p-6 space-y-4 shadow-2xl text-center">
        <div class="w-12 h-12 rounded-full bg-rose-500/10 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center text-2xl mx-auto">
          🗑️
        </div>
        <div class="space-y-1">
          <h3 class="text-base font-black text-slate-900 dark:text-white">Remove Payment Method?</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            Are you sure you want to remove <strong>{{ selectedMethodForAction.name }}</strong> ({{ selectedMethodForAction.masked_info }})?
          </p>
        </div>
        <div class="flex items-center justify-center gap-2 pt-2 text-xs">
          <button
            @click="isRemoveModalOpen = false"
            class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold cursor-pointer hover:bg-slate-200"
          >
            Cancel
          </button>
          <button
            @click="confirmRemoveMethod"
            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold shadow-md cursor-pointer"
          >
            Remove
          </button>
        </div>
      </div>
    </div>

    <!-- ================= TOAST NOTIFICATION ================= -->
    <div
      v-if="toastMessage"
      class="fixed bottom-6 right-6 z-50 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-2xl border border-emerald-500/50 flex items-center gap-2.5 text-xs font-bold animate-in slide-in-from-bottom-4 duration-200"
    >
      <span class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs">✓</span>
      <span>{{ toastMessage }}</span>
    </div>

  </StudentLayout>
</template>

