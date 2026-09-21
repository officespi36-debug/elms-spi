<script setup lang="ts">
import { ref, computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import UserModuleHeader from '@/Components/Admin/UserModuleHeader.vue'

const props = withDefaults(defineProps<{
  summaryStats?: any
}>(), {
  summaryStats: () => ({})
})

// Sub-Tab State (Separate Import and Export views to eliminate cognitive overload)
const activeSubTab = ref<'import' | 'export'>('import')

// Export state
const exportFormat = ref('csv')
const selectedRolesForExport = ref(['admin', 'teacher', 'student', 'suspended'])
const exportDateRange = ref('this_month')

// Import state
const isFileUploaded = ref(true)
const uploadedFileName = ref('sample_users_batch.csv')
const fileInputRef = ref<HTMLInputElement | null>(null)

// Interactive Column Mappings Dropdowns
const columnMappings = ref({
  name: 'Column A: Full Name',
  email: 'Column B: Email Address',
  role: 'Column C: User Role',
  dept: 'Column D: Department'
})

const csvHeaderOptions = [
  'Column A: Full Name',
  'Column B: Email Address',
  'Column C: User Role',
  'Column D: Department',
  'Column E: Phone Number',
  'Column F: Major Name'
]

// Import Records with Inline Editing & Deletion
const editingRowId = ref<number | null>(null)
const sampleImportRecords = ref([
  { id: 1, name: 'Chan Dara', email: 'dara@elms.edu', role: 'Student', dept: 'IT & Networking', valid: true, error: '' },
  { id: 2, name: 'Sok Chanra', email: 'chanra@elms.edu', role: 'Student', dept: 'Tourism Mgt', valid: true, error: '' },
  { id: 3, name: 'Unknown X', email: 'unknown@elms.edu', role: 'Student', dept: 'Social Work', valid: false, error: 'Duplicate Email' },
  { id: 4, name: 'Mr. Sophea', email: 'sophea@elms.edu', role: 'Teacher', dept: 'Computing', valid: false, error: 'User Already Exists' },
  { id: 5, name: 'Ms. New', email: 'new@elms.edu', role: 'Student', dept: 'Agronomy', valid: true, error: '' },
])

const validRecordsCount = computed(() => {
  return sampleImportRecords.value.filter(r => r.valid).length
})

const invalidRecordsCount = computed(() => {
  return sampleImportRecords.value.filter(r => !r.valid).length
})

const triggerFileBrowse = () => {
  fileInputRef.value?.click()
}

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    const file = target.files[0]
    uploadedFileName.value = file.name
    isFileUploaded.value = true
    alert(`File '${file.name}' loaded successfully! CSV columns mapped and records ready for validation.`)
  }
}

const saveRowEdit = (rec: any) => {
  rec.valid = true
  rec.error = ''
  editingRowId.value = null
}

const removeRow = (rowId: number) => {
  sampleImportRecords.value = sampleImportRecords.value.filter(r => r.id !== rowId)
}

const cancelImport = () => {
  if (confirm('Clear uploaded file and reset import preview?')) {
    isFileUploaded.value = false
    editingRowId.value = null
  }
}

const downloadTemplate = () => {
  const headers = ['Full Name', 'Email Address', 'Phone Number', 'User Role', 'Department', 'Major', 'Status']
  const sampleRow = ['John Doe', 'john.doe@elms.edu', '+855 12 345 678', 'student', 'Computing', 'IT & Networking', 'active']

  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), sampleRow.join(',')].join('\n')
  const encodedUri = encodeURI(csvContent)
  const link = document.createElement('a')
  link.setAttribute('href', encodedUri)
  link.setAttribute('download', 'elms_users_import_template.csv')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const executeExport = () => {
  const roles = selectedRolesForExport.value.join(', ').toUpperCase()
  alert(`Exporting users [${roles}] in ${exportFormat.value.toUpperCase()} format... File download initiated.`)
}

const confirmImportValidOnly = () => {
  alert(`Successfully imported ${validRecordsCount.value} verified user records into the system database!`)
}
</script>

<template>
  <AdminLayout title="Import & Export Users — Bulk Management">
    <div class="space-y-6 font-sans">
      <!-- Shared Header -->
      <UserModuleHeader activeTab="import-export" :summaryStats="props.summaryStats" />

      <!-- SUB-TAB NAVIGATION BAR (Separates Import & Export to prevent Cognitive Overload) -->
      <div class="bg-white dark:bg-slate-900/60 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-none backdrop-blur-xl flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1.5">
          <button
            @click="activeSubTab = 'import'"
            :class="[
              activeSubTab === 'import'
                ? 'bg-indigo-600 text-white font-semibold shadow-xs shadow-indigo-600/20'
                : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/50 dark:hover:bg-slate-800/80 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white border border-slate-200 dark:border-transparent',
              'px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2 cursor-pointer'
            ]"
          >
            <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Import Users Bulk (CSV / Excel)</span>
          </button>

          <button
            @click="activeSubTab = 'export'"
            :class="[
              activeSubTab === 'export'
                ? 'bg-cyan-600 text-white font-semibold shadow-xs shadow-cyan-600/20'
                : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/50 dark:hover:bg-slate-800/80 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white border border-slate-200 dark:border-transparent',
              'px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2 cursor-pointer'
            ]"
          >
            <svg class="w-4 h-4 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Export System Data File</span>
          </button>
        </div>

        <button @click="downloadTemplate" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-medium transition-all flex items-center gap-1.5 cursor-pointer">
          <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <span>Download Import Template</span>
        </button>
      </div>

      <!-- TAB 1: 📥 IMPORT USERS VIEW -->
      <div v-if="activeSubTab === 'import'" class="space-y-6">
        <!-- STEP 1 & STEP 2 GRID PANEL -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- STEP 1: UPLOAD FILE CARD -->
          <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4 shadow-sm dark:shadow-none backdrop-blur-xl">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
              <h3 class="text-xs font-bold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 flex items-center justify-center font-mono">1</span>
                <span>Upload CSV / Excel File</span>
              </h3>
              <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">Step 1 of 3</span>
            </div>

            <!-- Drag & Drop Box -->
            <div
              @click="triggerFileBrowse"
              class="p-6 border-2 border-dashed border-slate-300 hover:border-indigo-500 dark:border-slate-700 dark:hover:border-indigo-500 rounded-2xl text-center bg-slate-50 dark:bg-slate-950/60 cursor-pointer space-y-2 transition-all group"
            >
              <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto group-hover:scale-110 transition-transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
              </div>
              <div class="text-xs font-bold text-slate-800 dark:text-white">Drag & Drop .csv or .xlsx File Here</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">or click to browse file from computer</div>
              <input ref="fileInputRef" type="file" accept=".csv, .xlsx" @change="handleFileUpload" class="hidden" />
            </div>

            <!-- File Uploaded Banner -->
            <div v-if="isFileUploaded" class="p-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-xl flex items-center justify-between text-xs">
              <span class="text-emerald-800 dark:text-emerald-300 font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>File Uploaded: {{ uploadedFileName }}</span>
              </span>
              <span class="text-slate-700 dark:text-slate-300 font-mono font-bold">{{ sampleImportRecords.length }} Records Found</span>
            </div>
          </div>

          <!-- STEP 2: INTERACTIVE FIELD MAPPING CARD -->
          <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4 shadow-sm dark:shadow-none backdrop-blur-xl">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
              <h3 class="text-xs font-bold text-cyan-700 dark:text-cyan-300 uppercase tracking-wider flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-cyan-50 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-300 flex items-center justify-center font-mono">2</span>
                <span>Interactive Column Mapping</span>
              </h3>
              <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">Step 2 of 3</span>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300">
              តម្រូវ Column Name ពី File CSV ទៅកាន់ព័ត៌មាន System Fields
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <!-- Full Name Mapping -->
              <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-medium">Full Name System Field *</label>
                <select v-model="columnMappings.name" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-cyan-500 cursor-pointer">
                  <option v-for="opt in csvHeaderOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>

              <!-- Email Mapping -->
              <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-medium">Email Address System Field *</label>
                <select v-model="columnMappings.email" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-cyan-500 cursor-pointer">
                  <option v-for="opt in csvHeaderOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>

              <!-- Role Mapping -->
              <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-medium">User Role System Field *</label>
                <select v-model="columnMappings.role" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-cyan-500 cursor-pointer">
                  <option v-for="opt in csvHeaderOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>

              <!-- Department Mapping -->
              <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-medium">Department System Field</label>
                <select v-model="columnMappings.dept" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-900 dark:text-white font-mono focus:outline-none focus:border-cyan-500 cursor-pointer">
                  <option v-for="opt in csvHeaderOptions" :key="opt" :value="opt">{{ opt }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 3: PREVIEW TABLE & INLINE EDITING CARD -->
        <div v-if="isFileUploaded" class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4 shadow-sm dark:shadow-none backdrop-blur-xl">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-mono text-xs font-bold">3</span>
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                  IMPORT PREVIEW & INLINE EDITING TABLE
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">ពិនិត្យទិន្នន័យ, កែសម្រួល Email ស្ទួន ឬលុប Row ដោយផ្ទាល់</p>
              </div>
            </div>

            <!-- Record Validation Summary Badges -->
            <div class="flex items-center gap-2 text-xs font-mono">
              <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 rounded-xl font-bold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Valid: {{ validRecordsCount }}</span>
              </span>
              <span v-if="invalidRecordsCount > 0" class="px-3 py-1 bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 rounded-xl font-bold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                <span>Warnings: {{ invalidRecordsCount }}</span>
              </span>
            </div>
          </div>

          <!-- Validation Warnings Alert -->
          <div v-if="invalidRecordsCount > 0" class="p-4 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-2xl text-xs space-y-1">
            <div class="font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              <span>Validation Warnings Detected ({{ invalidRecordsCount }} Issues):</span>
            </div>
            <div class="text-slate-600 dark:text-slate-300 text-[11px] pl-5 space-y-0.5">
              <p v-for="rec in sampleImportRecords.filter(r => !r.valid)" :key="rec.id">
                • Row {{ rec.id }}: {{ rec.name }} ({{ rec.email }}) — <span class="text-amber-700 dark:text-amber-300 font-bold">{{ rec.error }}</span>. You can click ✏️ Edit or 🗑️ Remove on the row directly below.
              </p>
            </div>
          </div>

          <!-- DATA PREVIEW TABLE -->
          <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/60 backdrop-blur-xl">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800/60 text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                  <th class="py-3.5 px-4 w-12 text-center">#</th>
                  <th class="py-3.5 px-4">Full Name</th>
                  <th class="py-3.5 px-4">Email Address</th>
                  <th class="py-3.5 px-4">User Role</th>
                  <th class="py-3.5 px-4">Department</th>
                  <th class="py-3.5 px-4 text-center">Validation Check</th>
                  <th class="py-3.5 px-4 text-right">Inline Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 text-xs">
                <tr v-for="rec in sampleImportRecords" :key="rec.id" class="hover:bg-slate-100/60 dark:hover:bg-slate-900/40 transition-colors">
                  <td class="py-3.5 px-4 font-mono text-slate-500 dark:text-slate-400 font-medium text-center">{{ rec.id }}</td>
                  
                  <!-- Name Cell -->
                  <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                    <input v-if="editingRowId === rec.id" v-model="rec.name" type="text" class="bg-white dark:bg-slate-900 border border-cyan-500 rounded px-2 py-1 text-xs text-slate-900 dark:text-white focus:outline-none" />
                    <span v-else>{{ rec.name }}</span>
                  </td>

                  <!-- Email Cell -->
                  <td class="py-3.5 px-4 font-mono text-slate-700 dark:text-slate-200">
                    <input v-if="editingRowId === rec.id" v-model="rec.email" type="email" class="bg-white dark:bg-slate-900 border border-cyan-500 rounded px-2 py-1 text-xs text-emerald-600 dark:text-emerald-300 focus:outline-none" />
                    <span v-else>{{ rec.email }}</span>
                  </td>

                  <!-- Role Cell -->
                  <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 font-medium">
                    <select v-if="editingRowId === rec.id" v-model="rec.role" class="bg-white dark:bg-slate-900 border border-cyan-500 rounded px-2 py-1 text-xs text-slate-900 dark:text-white focus:outline-none">
                      <option value="Student">Student</option>
                      <option value="Teacher">Teacher</option>
                      <option value="Administrator">Administrator</option>
                    </select>
                    <span v-else>{{ rec.role }}</span>
                  </td>

                  <!-- Department Cell -->
                  <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">{{ rec.dept }}</td>

                  <!-- Validation Check Badge -->
                  <td class="py-3.5 px-4 text-center">
                    <span v-if="rec.valid" class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30 rounded-lg text-[10px] font-bold">
                      <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                      <span>Valid</span>
                    </span>
                    <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-amber-50 dark:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 rounded-lg text-[10px] font-bold">
                      <svg class="w-3 h-3 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      <span>{{ rec.error }}</span>
                    </span>
                  </td>

                  <!-- Inline Actions Column (Edit / Save / Remove) -->
                  <td class="py-3.5 px-4 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1.5">
                      <template v-if="editingRowId === rec.id">
                        <button
                          @click="saveRowEdit(rec)"
                          class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px] rounded-lg transition-all cursor-pointer flex items-center gap-1"
                        >
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                          <span>Save</span>
                        </button>
                      </template>
                      <template v-else>
                        <button
                          @click="editingRowId = rec.id"
                          class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-cyan-700 dark:text-cyan-300 hover:text-cyan-800 dark:hover:text-white border border-slate-200 dark:border-slate-700 rounded-lg text-[10px] font-semibold transition-all cursor-pointer flex items-center gap-1"
                          title="Inline Edit Row"
                        >
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                          <span>Edit</span>
                        </button>
                      </template>

                      <button
                        @click="removeRow(rec.id)"
                        class="px-2 py-1 bg-slate-100 hover:bg-rose-50 dark:bg-slate-800 dark:hover:bg-red-500/20 text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-red-400 border border-slate-200 dark:border-slate-700/60 rounded-lg text-[10px] transition-all cursor-pointer"
                        title="Remove Row from Import"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- PRIMARY ACTION & CANCEL BUTTON BAR -->
          <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
            <button @click="cancelImport" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/80 rounded-xl text-xs font-medium transition-all cursor-pointer flex items-center gap-1.5">
              <span>✕ Cancel & Clear Upload</span>
            </button>

            <button
              @click="confirmImportValidOnly"
              class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 border border-emerald-500/30 text-white font-semibold text-xs rounded-xl shadow-xs shadow-emerald-600/20 transition-all flex items-center gap-2 cursor-pointer"
            >
              <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              <span>Confirm & Import ({{ validRecordsCount }} Records)</span>
            </button>
          </div>
        </div>
      </div>

      <!-- TAB 2: 📤 EXPORT DATA VIEW -->
      <div v-if="activeSubTab === 'export'" class="space-y-6">
        <div class="bg-white dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-3xl p-7 space-y-6 shadow-xl dark:shadow-2xl backdrop-blur-xl max-w-2xl">
          <!-- Card Header -->
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/20 flex items-center justify-center text-cyan-600 dark:text-cyan-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
              </div>
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                  EXPORT SYSTEM USERS DATA FILE
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">ទាញយកទិន្នន័យគណនីជា File CSV ឬ Excel តាមប្រភេទ Roles</p>
              </div>
            </div>
            <span class="text-[10px] font-mono text-cyan-700 dark:text-cyan-300 font-bold px-2.5 py-1 bg-cyan-100 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/20 rounded-lg">
              Full Export
            </span>
          </div>

          <div class="space-y-5 text-xs">
            <!-- Export File Format Selector Cards -->
            <div>
              <label class="block font-medium text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Export File Format</span>
              </label>
              <div class="grid grid-cols-2 gap-3">
                <label
                  :class="[
                    exportFormat === 'csv'
                      ? 'bg-cyan-50 dark:bg-cyan-500/10 border-cyan-500 text-slate-900 dark:text-white font-bold'
                      : 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-700',
                    'p-3.5 border rounded-2xl cursor-pointer transition-all flex items-center gap-3'
                  ]"
                >
                  <input v-model="exportFormat" value="csv" type="radio" class="sr-only" />
                  <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-cyan-600 dark:text-cyan-400 font-mono font-bold text-xs">CSV</div>
                  <div>
                    <div class="font-bold text-xs">CSV File (.csv)</div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-normal">Fast, lightweight text data</div>
                  </div>
                </label>

                <label
                  :class="[
                    exportFormat === 'excel'
                      ? 'bg-cyan-50 dark:bg-cyan-500/10 border-cyan-500 text-slate-900 dark:text-white font-bold'
                      : 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-700',
                    'p-3.5 border rounded-2xl cursor-pointer transition-all flex items-center gap-3'
                  ]"
                >
                  <input v-model="exportFormat" value="excel" type="radio" class="sr-only" />
                  <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-mono font-bold text-xs">XLSX</div>
                  <div>
                    <div class="font-bold text-xs">Excel Worksheet (.xlsx)</div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-normal">Formatted spreadsheet data</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- User Roles Checkboxes Grid -->
            <div>
              <label class="block font-medium text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Select User Roles to Include:</span>
              </label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <label class="flex items-center gap-2.5 cursor-pointer text-slate-800 dark:text-slate-200 p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                  <input v-model="selectedRolesForExport" value="admin" type="checkbox" class="rounded bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-cyan-600 focus:ring-cyan-500 cursor-pointer" />
                  <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                  <span class="font-semibold">Administrators</span>
                </label>

                <label class="flex items-center gap-2.5 cursor-pointer text-slate-800 dark:text-slate-200 p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                  <input v-model="selectedRolesForExport" value="teacher" type="checkbox" class="rounded bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-cyan-600 focus:ring-cyan-500 cursor-pointer" />
                  <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                  <span class="font-semibold">Teachers / Instructors</span>
                </label>

                <label class="flex items-center gap-2.5 cursor-pointer text-slate-800 dark:text-slate-200 p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                  <input v-model="selectedRolesForExport" value="student" type="checkbox" class="rounded bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-cyan-600 focus:ring-cyan-500 cursor-pointer" />
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  <span class="font-semibold">Students / Learners</span>
                </label>

                <label class="flex items-center gap-2.5 cursor-pointer text-slate-800 dark:text-slate-200 p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                  <input v-model="selectedRolesForExport" value="suspended" type="checkbox" class="rounded bg-white dark:bg-slate-950 border-slate-300 dark:border-slate-700 text-cyan-600 focus:ring-cyan-500 cursor-pointer" />
                  <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                  <span class="font-semibold">Suspended Users</span>
                </label>
              </div>
            </div>

            <!-- Date Range & Live Record Count Estimator -->
            <div>
              <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Date Range Filter</span>
              </label>
              <select v-model="exportDateRange" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700/80 rounded-xl px-3.5 py-2.5 text-slate-900 dark:text-white font-mono focus:outline-none focus:border-cyan-500 transition-all cursor-pointer">
                <option value="this_month">This Month (July 2026)</option>
                <option value="all">All Historical Records</option>
              </select>
            </div>

            <!-- Estimated Export Count Banner -->
            <div class="p-3.5 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-between text-xs font-mono">
              <span class="text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Estimated Export Records:</span>
              </span>
              <span class="text-cyan-700 dark:text-cyan-300 font-bold">2,618 Account Profiles</span>
            </div>

            <!-- Submit Export Button Footer -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between gap-3">
              <button
                type="button"
                @click="selectedRolesForExport = ['admin', 'teacher', 'student', 'suspended']"
                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold transition-all cursor-pointer border border-slate-200 dark:border-transparent"
              >
                Reset Options
              </button>

              <button
                @click="executeExport"
                class="px-6 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl shadow-md shadow-cyan-600/20 border border-cyan-500/30 transition-all flex items-center gap-2 cursor-pointer"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span>Export System Data File</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
