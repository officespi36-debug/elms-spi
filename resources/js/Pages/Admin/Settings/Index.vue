<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

// Import modular sub-components for ⚙️ Settings Module
import GeneralSettings from './GeneralSettings.vue'
import AcademicSettings from './AcademicSettings.vue'
import SystemLogs from './SystemLogs.vue'
import LanguageLocalization from './LanguageLocalization.vue'
import EmailSmtp from './EmailSmtp.vue'
import S3Storage from './S3Storage.vue'
import VideoCdn from './VideoCdn.vue'
import RedisQueue from './RedisQueue.vue'
import ReverbRealtime from './ReverbRealtime.vue'
import PwaOffline from './PwaOffline.vue'
import BackupRestore from './BackupRestore.vue'

const props = defineProps<{
  settings: Record<string, any>
  systemLogs: Array<any>
  auditLogs: Array<any>
  backupHistory: Array<any>
  lastSaved?: string
  systemHealth?: string
  env?: string
}>()

const toast = useToast()

// Read initial tab from URL query param if present
const getInitialTab = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('tab') || 'general'
  }
  return 'general'
}

const activeTab = ref(getInitialTab())
const searchQuery = ref('')

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search)
  const tabParam = urlParams.get('tab')
  if (tabParam) {
    activeTab.value = tabParam
  }
})

// Unified settings form for technical tabs
const form = useForm({
  site_name: props.settings.site_name || 'SPI E-Learning System',
  site_short_name: props.settings.site_short_name || 'SPI ELMS',
  institution_name: props.settings.institution_name || 'Saint Paul Institute',
  institution_code: props.settings.institution_code || 'SPI',
  contact_email: props.settings.contact_email || 'info@spi.edu.kh',
  contact_phone: props.settings.contact_phone || '+855 23 888 999',
  address: props.settings.address || 'National Road 2, Angk Ta Saom, Tram Kak, Takeo Province, Cambodia',
  website_url: props.settings.website_url || 'https://spi.edu.kh',
  site_logo: props.settings.site_logo || '/images/logo-dark.png',
  site_favicon: props.settings.site_favicon || '/favicon.ico',
  timezone: props.settings.timezone || 'Asia/Phnom_Penh',
  date_format: props.settings.date_format || 'DD/MM/YYYY',
  time_format: props.settings.time_format || '12-hour AM/PM',
  default_user_role: props.settings.default_user_role || 'student',
  student_self_registration: props.settings.student_self_registration === '1' || props.settings.student_self_registration === true,
  teacher_self_registration: props.settings.teacher_self_registration === '1' || props.settings.teacher_self_registration === true,
  email_notifications: props.settings.email_notifications === '1' || props.settings.email_notifications === true,
  system_notifications: props.settings.system_notifications === '1' || props.settings.system_notifications === true,
  assignment_notifications: props.settings.assignment_notifications === '1' || props.settings.assignment_notifications === true,
  quiz_notifications: props.settings.quiz_notifications === '1' || props.settings.quiz_notifications === true,
  ai_notifications: props.settings.ai_notifications === '1' || props.settings.ai_notifications === true,
  maintenance_mode: props.settings.maintenance_mode === '1' || props.settings.maintenance_mode === true,
  maintenance_message_kh: props.settings.maintenance_message_kh || 'ប្រព័ន្ធ SPI E-Learning កំពុងធ្វើការកែលម្អ។ សូមព្យាយាមម្តងទៀតនៅពេលក្រោយ។',
  maintenance_message_en: props.settings.maintenance_message_en || 'SPI E-Learning System is under scheduled maintenance. Please check back later.',
  maintenance_end_time: props.settings.maintenance_end_time || '2026-10-15T22:00',

  // Language & Localization
  default_language: props.settings.default_language || 'km',
  enabled_languages: Array.isArray(props.settings.enabled_languages) ? props.settings.enabled_languages : ['km', 'en'],
  fallback_language: props.settings.fallback_language || 'en',
  number_format: props.settings.number_format || '1,234.56',
  khmer_numerals: props.settings.khmer_numerals === '1' || props.settings.khmer_numerals === true,
  first_day_of_week: props.settings.first_day_of_week || 'Monday',
  decimal_precision: props.settings.decimal_precision || '2',

  // Email / SMTP
  smtp_provider: props.settings.smtp_provider || 'mailgun',
  smtp_host: props.settings.smtp_host || 'smtp.mailgun.org',
  smtp_port: props.settings.smtp_port || '587',
  smtp_encryption: props.settings.smtp_encryption || 'tls',
  smtp_username: props.settings.smtp_username || 'postmaster@spi.edu.kh',
  smtp_password: props.settings.smtp_password || '••••••••••••••••••••••',
  mail_from_name: props.settings.mail_from_name || 'Saint Paul Institute',
  mail_from_address: props.settings.mail_from_address || 'noreply@spi.edu.kh',
  mail_reply_to: props.settings.mail_reply_to || 'info@spi.edu.kh',
  mail_daily_limit: props.settings.mail_daily_limit || '10000',
  mail_sent_today: props.settings.mail_sent_today || '1245',
  mail_queue_enabled: props.settings.mail_queue_enabled === '1' || props.settings.mail_queue_enabled === true,
  mail_retry_attempts: props.settings.mail_retry_attempts || '3',

  // S3 Storage
  storage_provider: props.settings.storage_provider || 'aws_s3',
  s3_region: props.settings.s3_region || 'ap-southeast-1',
  s3_bucket: props.settings.s3_bucket || 'spi-elms-production-files',
  s3_endpoint_url: props.settings.s3_endpoint_url || 'https://s3.ap-southeast-1.amazonaws.com',
  s3_folder_prefix: props.settings.s3_folder_prefix || 'spi/production/',
  s3_access_key_id: props.settings.s3_access_key_id || 'AKIA5X982739F2X',
  s3_secret_access_key: props.settings.s3_secret_access_key || '••••••••••••••••••••••',
  s3_file_visibility: props.settings.s3_file_visibility || 'private',
  s3_signed_url_expiry: props.settings.s3_signed_url_expiry || '10',
  s3_encryption: props.settings.s3_encryption || 'AES-256',
  s3_prevent_public_listing: props.settings.s3_prevent_public_listing === '1' || props.settings.s3_prevent_public_listing === true,
  s3_cors_domain: props.settings.s3_cors_domain || 'https://spi.edu.kh',
  storage_limit_student_mb: props.settings.storage_limit_student_mb || '100',
  storage_limit_teacher_gb: props.settings.storage_limit_teacher_gb || '2',
  folder_certificate: props.settings.folder_certificate || 'certificates/',
  folder_content: props.settings.folder_content || 'courses/',
  folder_backup: props.settings.folder_backup || 'backups/',

  // Video CDN
  cdn_provider: props.settings.cdn_provider || 'cloudfront',
  cdn_origin: props.settings.cdn_origin || 'AWS S3 – spi-elms-production-files',
  cdn_domain: props.settings.cdn_domain || 'https://media.spi.edu.kh',
  cdn_ssl_enabled: props.settings.cdn_ssl_enabled === '1' || props.settings.cdn_ssl_enabled === true,
  cdn_streaming_format: props.settings.cdn_streaming_format || 'hls',
  cdn_quality_profiles: Array.isArray(props.settings.cdn_quality_profiles) ? props.settings.cdn_quality_profiles : ['1080p', '720p', '480p', '360p'],
  cdn_adaptive_streaming: props.settings.cdn_adaptive_streaming === '1' || props.settings.cdn_adaptive_streaming === true,
  cdn_subtitle_format: props.settings.cdn_subtitle_format || 'vtt',
  cdn_thumbnail_generation: props.settings.cdn_thumbnail_generation === '1' || props.settings.cdn_thumbnail_generation === true,
  cdn_require_signed_url: props.settings.cdn_require_signed_url === '1' || props.settings.cdn_require_signed_url === true,
  cdn_signed_url_expiry: props.settings.cdn_signed_url_expiry || '15',
  cdn_block_direct_origin: props.settings.cdn_block_direct_origin === '1' || props.settings.cdn_block_direct_origin === true,
  cdn_allow_download: props.settings.cdn_allow_download === '1' || props.settings.cdn_allow_download === true,
  cdn_watermark_student_name: props.settings.cdn_watermark_student_name === '1' || props.settings.cdn_watermark_student_name === true,
  cdn_cache_video_days: props.settings.cdn_cache_video_days || '30',
  cdn_cache_subtitle_days: props.settings.cdn_cache_subtitle_days || '7',
  cdn_cache_thumbnail_days: props.settings.cdn_cache_thumbnail_days || '30',

  // Redis / Queue
  queue_driver: props.settings.queue_driver || 'redis',
  redis_host: props.settings.redis_host || '127.0.0.1',
  redis_port: props.settings.redis_port || '6379',
  redis_password: props.settings.redis_password || '••••••••••••••',
  redis_db: props.settings.redis_db || '0',
  redis_tls: props.settings.redis_tls === '1' || props.settings.redis_tls === true,
  queue_default: props.settings.queue_default || 'default',
  queue_email: props.settings.queue_email || 'emails',
  queue_notification: props.settings.queue_notification || 'notifications',
  queue_media: props.settings.queue_media || 'media-processing',
  queue_certificate: props.settings.queue_certificate || 'certificates',
  queue_retry_attempts: props.settings.queue_retry_attempts || '3',
  queue_timeout_seconds: props.settings.queue_timeout_seconds || '120',
  queue_retry_delay_seconds: props.settings.queue_retry_delay_seconds || '60',
  queue_failed_storage: props.settings.queue_failed_storage === '1' || props.settings.queue_failed_storage === true,

  // Reverb / Real-time
  broadcast_driver: props.settings.broadcast_driver || 'reverb',
  websocket_host: props.settings.websocket_host || 'ws.spi.edu.kh',
  websocket_port: props.settings.websocket_port || '443',
  websocket_protocol: props.settings.websocket_protocol || 'wss',
  websocket_allowed_origins: props.settings.websocket_allowed_origins || 'https://spi.edu.kh',
  reverb_app_id: props.settings.reverb_app_id || 'spi-app-001',
  reverb_app_key: props.settings.reverb_app_key || 'pk_live_89237489',
  reverb_app_secret: props.settings.reverb_app_secret || '•••••••••••••••••',
  realtime_in_app_notifications: props.settings.realtime_in_app_notifications === '1' || props.settings.realtime_in_app_notifications === true,
  realtime_discussion_replies: props.settings.realtime_discussion_replies === '1' || props.settings.realtime_discussion_replies === true,
  realtime_support_tickets: props.settings.realtime_support_tickets === '1' || props.settings.realtime_support_tickets === true,
  realtime_live_quiz: props.settings.realtime_live_quiz === '1' || props.settings.realtime_live_quiz === true,
  realtime_dashboard_stats: props.settings.realtime_dashboard_stats === '1' || props.settings.realtime_dashboard_stats === true,

  // PWA & Offline
  pwa_app_name: props.settings.pwa_app_name || 'SPI E-Learning',
  pwa_short_name: props.settings.pwa_short_name || 'SPI ELMS',
  pwa_start_url: props.settings.pwa_start_url || '/student/dashboard',
  pwa_display_mode: props.settings.pwa_display_mode || 'standalone',
  pwa_theme_color: props.settings.pwa_theme_color || '#2563EB',
  pwa_background_color: props.settings.pwa_background_color || '#0F172A',
  pwa_icon_512: props.settings.pwa_icon_512 || '/images/pwa-512.png',
  pwa_enable_install_banner: props.settings.pwa_enable_install_banner === '1' || props.settings.pwa_enable_install_banner === true,
  pwa_enable_service_worker: props.settings.pwa_enable_service_worker === '1' || props.settings.pwa_enable_service_worker === true,
  pwa_show_update_alert: props.settings.pwa_show_update_alert === '1' || props.settings.pwa_show_update_alert === true,
  offline_allow_pdf: props.settings.offline_allow_pdf === '1' || props.settings.offline_allow_pdf === true,
  offline_allow_slides: props.settings.offline_allow_slides === '1' || props.settings.offline_allow_slides === true,
  offline_allow_notes: props.settings.offline_allow_notes === '1' || props.settings.offline_allow_notes === true,
  offline_allow_videos: props.settings.offline_allow_videos === '1' || props.settings.offline_allow_videos === true,
  offline_max_storage_gb: props.settings.offline_max_storage_gb || '1',
  offline_cache_expiry_days: props.settings.offline_cache_expiry_days || '30',
  offline_auto_download_wifi: props.settings.offline_auto_download_wifi === '1' || props.settings.offline_auto_download_wifi === true,
  offline_clear_on_logout: props.settings.offline_clear_on_logout === '1' || props.settings.offline_clear_on_logout === true,
  offline_sync_progress: props.settings.offline_sync_progress === '1' || props.settings.offline_sync_progress === true,
  offline_sync_quiz: props.settings.offline_sync_quiz === '1' || props.settings.offline_sync_quiz === true,
  offline_sync_notes: props.settings.offline_sync_notes === '1' || props.settings.offline_sync_notes === true,
  offline_conflict_handler: props.settings.offline_conflict_handler || 'latest_wins',

  // Backup & Restore
  backup_schedule: props.settings.backup_schedule || 'daily_02am',
  backup_include_db: props.settings.backup_include_db === '1' || props.settings.backup_include_db === true,
  backup_include_files: props.settings.backup_include_files === '1' || props.settings.backup_include_files === true,
  backup_include_config: props.settings.backup_include_config === '1' || props.settings.backup_include_config === true,
  backup_include_audit_logs: props.settings.backup_include_audit_logs === '1' || props.settings.backup_include_audit_logs === true,
  backup_destination: props.settings.backup_destination || 'S3: spi-production-backups',
  backup_encryption: props.settings.backup_encryption || 'AES-256',
  backup_retention_days: props.settings.backup_retention_days || '30',
  backup_notify_admin: props.settings.backup_notify_admin === '1' || props.settings.backup_notify_admin === true,
})

// Canonical Sidebar Menu Navigation Structure
const sidebarCategories = [
  {
    group: '⚙️ Core System Settings',
    items: [
      { id: 'general', label: 'General Settings', icon: '🏢' },
      { id: 'academic', label: 'Academic Settings', icon: '🎓' },
      { id: 'logs', label: 'System Logs', icon: '🧾' },
    ]
  },
  {
    group: '🌐 Localization & Mail',
    items: [
      { id: 'language', label: 'Language & Locale', icon: '🌐' },
      { id: 'smtp', label: 'Email / SMTP', icon: '📧' }
    ]
  },
  {
    group: '☁️ Cloud Infrastructure',
    items: [
      { id: 's3', label: 'S3 Storage', icon: '☁️' },
      { id: 'cdn', label: 'Video CDN', icon: '🎬' },
      { id: 'redis', label: 'Redis / Queue', icon: '⚡' },
      { id: 'reverb', label: 'Reverb / Real-time', icon: '📡' },
      { id: 'pwa', label: 'PWA & Offline', icon: '📱' },
      { id: 'backup', label: 'Backup & Restore', icon: '🗄️' },
    ]
  }
]

const switchTab = (tabId: string) => {
  activeTab.value = tabId
  const url = new URL(window.location.href)
  url.searchParams.set('tab', tabId)
  window.history.pushState({}, '', url.toString())
}

const handleSubSaveSettings = (data: any) => {
  router.post(route('admin.settings.update'), data, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({
        severity: 'success',
        summary: 'Settings Saved',
        detail: 'General configuration updated successfully and recorded in System Logs',
        life: 3000
      })
    }
  })
}

const handleSaveAcademic = (data: any) => {
  router.post(route('admin.settings.update'), { academic_settings: data }, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({
        severity: 'success',
        summary: 'Academic Settings Saved',
        detail: 'Academic year & enrollment rules updated successfully',
        life: 3000
      })
    }
  })
}

const handleNotify = (msg: string) => {
  toast.add({
    severity: 'info',
    summary: 'System Notice',
    detail: msg,
    life: 3500
  })
}

const saveSettings = () => {
  form.post(route('admin.settings.update'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Settings Saved', detail: 'System configuration updated successfully', life: 3000 })
    }
  })
}

const resetForm = () => {
  form.reset()
  toast.add({ severity: 'info', summary: 'Reset Done', detail: 'Form restored to last saved state', life: 3000 })
}

// Modals State for technical tabs
const showMaintenanceModal = ref(false)
const showBrandingModal = ref(false)
const showLangPreviewModal = ref(false)
const previewLang = ref<'kh' | 'en'>('kh')
const showS3BrowserModal = ref(false)
const showCdnPlaybackModal = ref(false)
const showQueueJobsModal = ref(false)
const showPwaPreviewModal = ref(false)
const showRestoreModal = ref(false)
const restoreConfirmText = ref('')

const onMaintenanceToggle = (val: boolean) => {
  if (val) {
    showMaintenanceModal.value = true
  }
}

const confirmMaintenance = () => {
  form.maintenance_mode = true
  showMaintenanceModal.value = false
  saveSettings()
}

const cancelMaintenance = () => {
  form.maintenance_mode = false
  showMaintenanceModal.value = false
}

// Technical Actions
const sendTestEmail = (recipient: string) => {
  router.post(route('admin.settings.test-smtp'), { recipient }, {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: 'Email Sent', detail: `Test email sent to ${recipient}`, life: 3500 })
  })
}

const testS3Upload = () => {
  router.post(route('admin.settings.test-s3'), {}, {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: 'S3 Verified', detail: 'Upload test chunk completed successfully!', life: 3500 })
  })
}

const testReverbConnection = () => {
  router.post(route('admin.settings.test-reverb'), {}, {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: 'WebSocket Active', detail: 'Test broadcast event fired!', life: 3500 })
  })
}

const purgeCdnCache = () => {
  router.post(route('admin.settings.purge-cdn'), {}, {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: 'CDN Purged', detail: 'CloudFront edge caches invalidated!', life: 3500 })
  })
}

const runBackupNow = () => {
  router.post(route('admin.settings.run-backup'), {}, {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: 'Backup Completed', detail: 'System backup archive created!', life: 3500 })
  })
}

const executeRestore = () => {
  if (restoreConfirmText.value !== 'RESTORE SPI') {
    toast.add({ severity: 'error', summary: 'Verification Failed', detail: 'Please type RESTORE SPI exactly', life: 3000 })
    return
  }
  router.post(route('admin.settings.restore-backup'), { confirm_text: restoreConfirmText.value }, {
    preserveScroll: true,
    onSuccess: () => {
      showRestoreModal.value = false
      restoreConfirmText.value = ''
      toast.add({ severity: 'warn', summary: 'Restore Initiated', detail: 'System database restored to backup checkpoint', life: 4000 })
    }
  })
}

// Filtered sidebar categories based on search
const filteredSidebar = computed(() => {
  if (!searchQuery.value.trim()) return sidebarCategories
  const q = searchQuery.value.toLowerCase()
  return sidebarCategories.map(cat => ({
    group: cat.group,
    items: cat.items.filter(item => item.label.toLowerCase().includes(q))
  })).filter(cat => cat.items.length > 0)
})
</script>

<template>
  <AdminLayout title="System Settings">
    <Toast />

    <div class="space-y-6">
      <!-- 1. Header Bar with Institutional Brand & Navigation Actions -->
      <div class="bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xl">
        <div class="flex items-center gap-4 flex-wrap">
          <div class="flex items-center gap-3">
            <span class="text-3xl">⚙️</span>
            <div>
              <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                <span>System Settings</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30 font-normal">
                  Saint Paul Institute
                </span>
              </h1>
              <p class="text-xs text-slate-400">
                General Settings • Academic Settings • System Logs
              </p>
            </div>
          </div>

          <div class="h-6 w-px bg-slate-700 hidden sm:block"></div>

          <!-- Environment Badge -->
          <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            {{ env || 'LOCAL' }}
          </span>

          <!-- Last Saved Badge -->
          <span class="text-xs text-slate-400 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Last saved: <strong class="text-slate-300 font-medium">{{ lastSaved || 'Just now' }}</strong>
          </span>

          <!-- System Health -->
          <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            System Operational
          </span>
        </div>

        <!-- Right Side: Search & Primary Action -->
        <div class="flex items-center gap-3">
          <!-- Search Bar -->
          <div class="relative w-full sm:w-56">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="🔍 Search settings..."
              class="w-full bg-slate-900/90 border border-slate-700 text-xs text-slate-200 rounded-xl pl-8 pr-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all placeholder:text-slate-500"
            />
            <svg class="w-4 h-4 text-slate-500 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <!-- Reset Button -->
          <button
            @click="resetForm"
            type="button"
            class="px-3 py-2 text-xs font-medium text-slate-300 hover:text-white bg-slate-700/60 hover:bg-slate-700 rounded-xl border border-slate-600/60 transition-all flex items-center gap-1.5 shrink-0"
          >
            ↩ Reset
          </button>
        </div>
      </div>

      <!-- Main Layout Grid (Sidebar Navigation + Sub-Module Views) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left Sidebar Navigation -->
        <div class="lg:col-span-3 space-y-4">
          <div class="bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 rounded-2xl p-3.5 space-y-4 shadow-lg sticky top-20">
            <div class="px-2 flex items-center justify-between">
              <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">⚙️ SETTINGS MODULES</h2>
              <span class="text-[10px] text-blue-400 font-mono">SPI ELMS</span>
            </div>

            <div v-for="cat in filteredSidebar" :key="cat.group" class="space-y-1">
              <div class="text-[11px] font-semibold text-slate-500 px-2 pt-2">{{ cat.group }}</div>
              <button
                v-for="item in cat.items"
                :key="item.id"
                @click="switchTab(item.id)"
                :class="[
                  activeTab === item.id 
                    ? 'bg-blue-600/20 text-blue-400 border-blue-500/40 font-semibold shadow-sm' 
                    : 'text-slate-300 hover:text-white hover:bg-slate-700/50 border-transparent',
                  'w-full text-left px-3 py-2 rounded-xl border text-xs flex items-center gap-2.5 transition-all'
                ]"
              >
                <span>{{ item.icon }}</span>
                <span class="truncate">{{ item.label }}</span>
                <span v-if="['general', 'academic', 'logs'].includes(item.id)" class="ml-auto text-[9px] px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-300 font-mono">
                  core
                </span>
              </button>
            </div>
          </div>
        </div>

        <!-- Right Main Settings Sub-Component Rendering -->
        <div class="lg:col-span-9 space-y-6">
          
          <!-- 1. 🏢 Canonical Sub-module: General Settings -->
          <GeneralSettings
            v-if="activeTab === 'general'"
            :settings="props.settings"
            @saveSettings="handleSubSaveSettings"
            @notify="handleNotify"
          />

          <!-- 2. 🎓 Canonical Sub-module: Academic Settings -->
          <AcademicSettings
            v-if="activeTab === 'academic'"
            @save="handleSaveAcademic"
            @notify="handleNotify"
          />

          <!-- 3. 🧾 Canonical Sub-module: System Logs -->
          <SystemLogs
            v-if="activeTab === 'logs'"
            :initialLogs="props.systemLogs"
            @notify="handleNotify"
          />

          <!-- Technical Tabs (Language, SMTP, Storage, Video, Redis, Reverb, PWA, Backup) -->
          <form v-show="!['general', 'academic', 'logs'].includes(activeTab)" @submit.prevent="saveSettings" class="space-y-6">
            <!-- 🌐 Language & Localization -->
            <LanguageLocalization
              v-show="activeTab === 'language'"
              :form="form"
              :saveSettings="saveSettings"
              @openLangPreview="(lang) => { previewLang = lang; showLangPreviewModal = true; }"
            />

            <!-- 📧 Email / SMTP -->
            <EmailSmtp
              v-show="activeTab === 'smtp'"
              :form="form"
              :saveSettings="saveSettings"
              :sendTestEmail="sendTestEmail"
            />

            <!-- ☁️ S3 Storage -->
            <S3Storage
              v-show="activeTab === 's3'"
              :form="form"
              :saveSettings="saveSettings"
              :testS3Upload="testS3Upload"
              @openS3Browser="showS3BrowserModal = true"
            />

            <!-- 🎬 Video CDN -->
            <VideoCdn
              v-show="activeTab === 'cdn'"
              :form="form"
              :saveSettings="saveSettings"
              :purgeCdnCache="purgeCdnCache"
              @openCdnPlayback="showCdnPlaybackModal = true"
            />

            <!-- ⚡ Redis / Queue -->
            <RedisQueue
              v-show="activeTab === 'redis'"
              :form="form"
              :saveSettings="saveSettings"
              @openQueueJobs="showQueueJobsModal = true"
            />

            <!-- 📡 Reverb / Real-time -->
            <ReverbRealtime
              v-show="activeTab === 'reverb'"
              :form="form"
              :saveSettings="saveSettings"
              :testReverbConnection="testReverbConnection"
            />

            <!-- 📱 PWA & Offline -->
            <PwaOffline
              v-show="activeTab === 'pwa'"
              :form="form"
              :saveSettings="saveSettings"
              @openPwaPreview="showPwaPreviewModal = true"
            />

            <!-- 🗄️ Backup & Restore -->
            <BackupRestore
              v-show="activeTab === 'backup'"
              :form="form"
              :backupHistory="backupHistory"
              :saveSettings="saveSettings"
              :runBackupNow="runBackupNow"
              @openRestoreModal="showRestoreModal = true"
            />
          </form>

        </div>
      </div>
    </div>

    <!-- ⚠️ MODAL: Maintenance Mode Confirmation -->
    <div v-if="showMaintenanceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="bg-slate-900 border border-amber-500/50 rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex items-center gap-3 text-amber-400 border-b border-slate-800 pb-3">
          <span class="text-2xl">⚠️</span>
          <div>
            <h3 class="font-bold text-base text-white">Enable Maintenance Mode?</h3>
            <p class="text-xs text-amber-400/90">Students and teachers will be locked out immediately.</p>
          </div>
        </div>

        <div class="space-y-3 text-xs text-slate-300">
          <p>• Super Admin accounts will remain accessible.</p>
          <div>
            <label class="block font-medium mb-1 text-slate-400">Maintenance Message (KH):</label>
            <textarea v-model="form.maintenance_message_kh" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-xs text-white"></textarea>
          </div>
          <div>
            <label class="block font-medium mb-1 text-slate-400">Estimated End Time:</label>
            <input v-model="form.maintenance_end_time" type="datetime-local" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2 text-xs text-white" />
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-800 pt-3">
          <button @click="cancelMaintenance" type="button" class="px-3.5 py-2 text-xs bg-slate-800 text-slate-300 rounded-xl">❌ Cancel</button>
          <button @click="confirmMaintenance" type="button" class="px-4 py-2 text-xs font-semibold bg-amber-600 hover:bg-amber-500 text-white rounded-xl">⚠️ Enable Maintenance</button>
        </div>
      </div>
    </div>

    <!-- 🗄️ RESTORE BACKUP MODAL -->
    <div v-if="showRestoreModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="bg-slate-900 border border-rose-500/50 rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex items-center gap-3 text-rose-400 border-b border-slate-800 pb-3">
          <span class="text-2xl">🚨</span>
          <div>
            <h3 class="font-bold text-base text-white">Restore Database Backup</h3>
            <p class="text-xs text-rose-300">Danger: Current data will be overwritten with backup state.</p>
          </div>
        </div>

        <div class="space-y-3 text-xs text-slate-300">
          <p>Type <strong class="text-rose-400 font-mono">RESTORE SPI</strong> below to confirm system restore:</p>
          <input
            v-model="restoreConfirmText"
            type="text"
            placeholder="Type RESTORE SPI"
            class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-white font-mono"
          />
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-800 pt-3">
          <button @click="showRestoreModal = false" type="button" class="px-3.5 py-2 text-xs bg-slate-800 text-slate-300 rounded-xl">Cancel</button>
          <button @click="executeRestore" type="button" class="px-4 py-2 text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white rounded-xl">Initiate System Restore</button>
        </div>
      </div>
    </div>

  </AdminLayout>
</template>
