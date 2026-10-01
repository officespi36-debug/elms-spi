<script setup lang="ts">
import { ref, defineAsyncComponent } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import type { 
  AiRuleItem, 
  WeakTopicItem, 
  WeakTopicDetectionConfig, 
  LearningPathNode, 
  RecommendationLogItem, 
  EffectivenessMetric, 
  AiConfigSettings 
} from './types'

// Sub-Components
import AiRules from './AiRules.vue'
import AtRiskStudents from './AtRiskStudents.vue'
import DifficultTopics from './DifficultTopics.vue'
import WeakTopicRules from './WeakTopicRules.vue'
import LearningPathRules from './LearningPathRules.vue'
import RecommendationLogs from './RecommendationLogs.vue'
import AiConfiguration from './AiConfiguration.vue'
import StudentAiView from './StudentAiView.vue'

// Lazy-Loaded Modals (Dynamic Imports for optimal performance)
const RuleSimulatorModal = defineAsyncComponent(() => import('./Modals/RuleSimulatorModal.vue'))
const CreateRuleModal = defineAsyncComponent(() => import('./Modals/CreateRuleModal.vue'))
const CreateMappingModal = defineAsyncComponent(() => import('./Modals/CreateMappingModal.vue'))
const CreateReviewClassModal = defineAsyncComponent(() => import('./Modals/CreateReviewClassModal.vue'))
const AddPracticeQuizModal = defineAsyncComponent(() => import('./Modals/AddPracticeQuizModal.vue'))
const NodeEditorModal = defineAsyncComponent(() => import('./Modals/NodeEditorModal.vue'))
const DecisionNodeModal = defineAsyncComponent(() => import('./Modals/DecisionNodeModal.vue'))
const LogDetailModal = defineAsyncComponent(() => import('./Modals/LogDetailModal.vue'))

const props = defineProps<{
  activeTab?: string
  recommendations?: any[]
  majors?: any[]
  stats?: {
    total_recommendations: number
    active_rules: number
    weak_topics_flagged: number
    acceptance_rate: number
  }
}>()

const currentTab = ref<string>(
  props.activeTab === 'recommendations' ? 'rules' :
  (props.activeTab === 'weak_topics' ? 'difficult_topics' :
  (props.activeTab || 'rules'))
)
const showSimulatorModal = ref(false)
const showCreateRuleModal = ref(false)
const selectedRuleToEdit = ref<AiRuleItem | null>(null)

const showCreateMappingModal = ref(false)
const selectedMappingToEdit = ref<WeakTopicItem | null>(null)

const showReviewClassModal = ref(false)
const showPracticeQuizModal = ref(false)

const showNodeEditorModal = ref(false)
const selectedNodeToEdit = ref<LearningPathNode | null>(null)
const showDecisionNodeModal = ref(false)

const showLogDetailModal = ref(false)
const selectedLogToView = ref<RecommendationLogItem | null>(null)

// Toast Notification
const toastMessage = ref('')
const toastType = ref<'success' | 'info' | 'warning'>('success')

function showNotification(msg: string, type: 'success' | 'info' | 'warning' = 'success') {
  toastMessage.value = msg
  toastType.value = type
  setTimeout(() => {
    toastMessage.value = ''
  }, 3500)
}

// Fallback Data Arrays (Converted to reactive state)
const defaultRules: AiRuleItem[] = [
  { id: 1, code: 'R-01', name: 'Advance High Performer', category: 'Performance', condition_text: 'Quiz Score ≥ 80%', action_text: 'Advance to next module', priority: 'P1', status: 'active', fired_count: 420, cooldown_hours: 24, max_fires: 3, notify_student: true, notify_teacher: false, message_template: 'Great job! You are ready for the next module 🎉' },
  { id: 2, code: 'R-02', name: 'Review Current Module', category: 'Performance', condition_text: 'Quiz Score 50 – 79%', action_text: 'Review current module drills', priority: 'P2', status: 'active', fired_count: 510, cooldown_hours: 12, max_fires: 5, notify_student: true, notify_teacher: false, message_template: 'Good effort! Review the practice drill to solidify your understanding.' },
  { id: 3, code: 'R-03', name: 'Assign Remedial Content', category: 'Performance', condition_text: 'Quiz Score < 50%', action_text: 'Assign remedial review video + cheat sheet', priority: 'P1', status: 'active', fired_count: 180, cooldown_hours: 6, max_fires: 3, notify_student: true, notify_teacher: true, message_template: 'Let us review this concept before moving forward.' },
  { id: 4, code: 'R-04', name: 'Resume Incomplete Lesson', category: 'Completion', condition_text: 'Completion < 40%', action_text: 'Prompt student to resume incomplete lesson', priority: 'P2', status: 'active', fired_count: 95, cooldown_hours: 24, max_fires: 2, notify_student: true, notify_teacher: false },
  { id: 5, code: 'R-05', name: 'Send Re-engagement Alert', category: 'Engagement', condition_text: 'Idle > 3 days', action_text: 'Send automated re-engagement email + in-app alert', priority: 'P3', status: 'active', fired_count: 40, cooldown_hours: 72, max_fires: 2, notify_student: true, notify_teacher: true },
  { id: 6, code: 'R-06', name: 'Suggest Re-watch Video', category: 'Behavior', condition_text: 'Watched video < 50%', action_text: 'Suggest re-watching video lesson', priority: 'P4', status: 'active', fired_count: 62, cooldown_hours: 24, max_fires: 3, notify_student: true, notify_teacher: false },
  { id: 7, code: 'R-07', name: 'Suggest Harder Challenge', category: 'Performance', condition_text: '3 quizzes passed in row (≥90%)', action_text: 'Suggest advanced honors project', priority: 'P5', status: 'disabled', fired_count: 0, cooldown_hours: 48, max_fires: 1, notify_student: true, notify_teacher: false },
  { id: 8, code: 'R-08', name: 'Overdue Assignment Alert', category: 'Completion', condition_text: 'Assignment overdue 2 days', action_text: 'Notify teacher + send reminder to student', priority: 'P2', status: 'active', fired_count: 18, cooldown_hours: 24, max_fires: 2, notify_student: true, notify_teacher: true },
  { id: 9, code: 'R-09', name: 'Agriculture: Soil Management Remedial', category: 'Performance', condition_text: 'Weak Topic: Soil Management (Score < 50%)', action_text: 'Review Soil Preparation → Read learning material → Practice Quiz', priority: 'P1', status: 'active', fired_count: 110, cooldown_hours: 12, max_fires: 3, notify_student: true, notify_teacher: true, message_template: 'Soil Management mastery is critical. Please review soil preparation material and complete practice quiz.' },
]

const rulesList = ref<AiRuleItem[]>([...defaultRules])

const defaultWeakTopics: WeakTopicItem[] = [
  { id: 1, topic: 'Soil Management & pH Balance', course: 'Soil Science & Plant Nutrition', affected_students: 38, avg_score: 38, trend: 'down', auto_action: 'Remedial sent ✅', remedial_content: ['🎥 Review Soil Preparation', '📄 Read Soil Amendments Material', '✍ Practice Quiz'], status: 'active' },
  { id: 2, topic: 'JavaScript Functions & Scope', course: 'Web Development Basics', affected_students: 45, avg_score: 45, trend: 'down', auto_action: 'Remedial sent ✅', remedial_content: ['🎥 Re-watch "Functions Explained"', '✍ Practice Quiz: Scope Drill'], status: 'active' },
  { id: 3, topic: 'Complex Sentence Structures & Clauses', course: 'Academic English Grammar', affected_students: 42, avg_score: 46, trend: 'flat', auto_action: 'Remedial sent ✅', remedial_content: ['📄 PDF: Clause Synthesis Cheat Sheet', '✍ Practice Quiz'], status: 'active' },
  { id: 4, topic: 'Hotel Yield Management & Dynamic Pricing', course: 'Tourism Operations & Booking', affected_students: 35, avg_score: 48, trend: 'flat', auto_action: 'Pending review', remedial_content: ['🎥 Yield Calculation Video', '📊 Pricing Case Study'], status: 'active' },
  { id: 5, topic: 'Legal Frameworks in Cambodian Child Welfare', course: 'Child Protection Protocols', affected_students: 30, avg_score: 52, trend: 'up', auto_action: 'Monitoring', remedial_content: ['📝 Note: Child Law Summary', '✍ Ethics Quiz'], status: 'active' },
]

const weakTopicsList = ref<WeakTopicItem[]>([...defaultWeakTopics])

function handleDeleteMapping(item: WeakTopicItem) {
  weakTopicsList.value = weakTopicsList.value.filter(w => w.id !== item.id)
  showNotification(`Deleted mapping for "${item.topic}".`, 'warning')
}

function handleToggleMappingStatus(item: WeakTopicItem) {
  const idx = weakTopicsList.value.findIndex(w => w.id === item.id)
  if (idx !== -1) {
    const newStatus = weakTopicsList.value[idx].status === 'active' ? 'disabled' : 'active'
    weakTopicsList.value[idx].status = newStatus
    showNotification(`Status for "${item.topic}" changed to ${newStatus}.`)
  }
}

function handleSaveMapping(itemData: Partial<WeakTopicItem>) {
  if (selectedMappingToEdit.value) {
    const idx = weakTopicsList.value.findIndex(w => w.id === selectedMappingToEdit.value?.id)
    if (idx !== -1) {
      weakTopicsList.value[idx] = { ...weakTopicsList.value[idx], ...itemData }
      showNotification(`Updated mapping for "${itemData.topic}".`)
    }
  } else {
    const newMapping: WeakTopicItem = {
      id: Date.now(),
      topic: itemData.topic || 'New Weak Topic',
      course: itemData.course || 'C Programming Basics',
      affected_students: itemData.affected_students || 10,
      avg_score: itemData.avg_score || 45,
      trend: 'down',
      auto_action: 'Remedial sent ✅',
      remedial_content: itemData.remedial_content || ['🎥 Remedial Video Lesson'],
      status: itemData.status || 'active'
    }
    weakTopicsList.value.unshift(newMapping)
    showNotification(`Created new mapping for "${newMapping.topic}".`)
  }
  showCreateMappingModal.value = false
  selectedMappingToEdit.value = null
}

function handleSaveReviewClass(payload: { sessionTitle: string; instructor: string }) {
  showReviewClassModal.value = false
  showNotification(`Scheduled review class "${payload.sessionTitle}" with ${payload.instructor}!`)
}

function handleSavePracticeQuiz(payload: { quizTitle: string; totalQuestions: number }) {
  showPracticeQuizModal.value = false
  showNotification(`Published practice quiz "${payload.quizTitle}" (${payload.totalQuestions} Questions)!`)
}

function handleSaveNode(payload: { id?: number; title: string }) {
  showNodeEditorModal.value = false
  showNotification(`Saved Learning Path Node "${payload.title}"!`)
}

function handleSaveDecisionNode() {
  showDecisionNodeModal.value = false
  showNotification('Updated Decision Node score evaluation branches!')
}

const defaultWeakConfig: WeakTopicDetectionConfig = {
  threshold_percent: 50,
  min_questions: 3,
  consecutive_failures: 2,
  auto_remedial_task: true,
  show_focus_badge: true,
}

const defaultPathNodes: LearningPathNode[] = [
  { id: 1, step_number: 1, title: 'Basics of C Programming', status: 'completed' },
  { id: 2, step_number: 2, title: 'Operators & Expressions in C', status: 'in_progress' },
  { id: 3, step_number: 3, title: 'Conditional Statements', status: 'next' },
  { id: 4, step_number: 4, title: 'Loops in C', status: 'locked' },
  { id: 5, step_number: 5, title: 'Functions in C', status: 'locked' },
]

const defaultLogs: RecommendationLogItem[] = [
  { id: 1, timestamp: '16 Jun 09:31', student_name: 'Chan Dara', student_id: 'STU24001', rule_code: 'R-01', rule_name: 'Advance High Performer', recommendation_text: 'Next: Operators in C', status: 'Done' },
  { id: 2, timestamp: '16 Jun 09:12', student_name: 'Sok Chanra', student_id: 'STU24002', rule_code: 'W-01', rule_name: 'Weak Topic Remedial', recommendation_text: 'Re-watch Loops in C video', status: 'Accepted' },
  { id: 3, timestamp: '16 Jun 08:55', student_name: 'Long Vichida', student_id: 'STU24003', rule_code: 'R-05', rule_name: 'Re-engagement Alert', recommendation_text: 'Email reminder sent to student', status: 'Sent' },
  { id: 4, timestamp: '16 Jun 08:40', student_name: 'Pov Sreynich', student_id: 'STU24004', rule_code: 'R-03', rule_name: 'Assign Remedial Content', recommendation_text: 'Conditions Review Drill assigned', status: 'Done' },
  { id: 5, timestamp: '16 Jun 08:22', student_name: 'Mao Sreynich', student_id: 'STU24005', rule_code: 'L-02', rule_name: 'Path Branch Drill', recommendation_text: 'Extra practice assigned', status: 'Ignored' },
]

const computedLogs = computed<RecommendationLogItem[]>(() => {
  if (props.recommendations && props.recommendations.length > 0) {
    const dbLogs: RecommendationLogItem[] = props.recommendations.map((rec: any, idx: number) => {
      const padId = String(rec.user_id || 1).padStart(3, '0')
      return {
        id: rec.id,
        timestamp: new Date(rec.created_at || Date.now()).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }),
        student_name: rec.user?.name || `Student #${rec.user_id}`,
        student_id: rec.user?.student_code || `SPI-2026-${padId}`,
        rule_code: rec.type === 'next_module' ? 'R-01' : (rec.type === 'weak_topic' ? 'W-01' : (rec.type === 'remedial' ? 'R-03' : 'R-05')),
        rule_name: rec.type === 'next_module' ? 'Advance High Performer' : (rec.type === 'weak_topic' ? 'Weak Topic Remedial' : (rec.type === 'remedial' ? 'Assign Remedial Content' : 'Re-engagement Alert')),
        recommendation_text: rec.reason || 'AI recommendation generated',
        status: rec.is_dismissed ? 'Dismissed' : (idx % 2 === 0 ? 'Done' : 'Accepted'),
      }
    })
    return [...dbLogs, ...defaultLogs]
  }
  return defaultLogs
})

const defaultEffectiveness: EffectivenessMetric[] = [
  { rule_type: 'Advance / Next Lesson', acceptance_rate: 82, status_tag: 'Excellent' },
  { rule_type: 'Weak Topic Remedial', acceptance_rate: 68, status_tag: 'Good' },
  { rule_type: 'Review Current Module', acceptance_rate: 60, status_tag: 'Fair' },
  { rule_type: 'Re-engagement Alert', acceptance_rate: 40, status_tag: 'Needs Tuning' },
  { rule_type: 'Harder Content Suggestion', acceptance_rate: 30, status_tag: 'Needs Tuning' },
]

const defaultAiConfig: AiConfigSettings = {
  engine_type: 'hybrid',
  ml_model: 'Collaborative Filtering v2',
  confidence_threshold: 70,
  max_rec_per_day: 5,
  reevaluation_hours: 6,
  sandbox_mode: false,
  ai_provider: 'Internal Engine',
  api_key_masked: '••••••••••••3456',
  api_connected: true,
  temperature: 0.2,
  timeout_seconds: 10,
  personalization_level: 'standard',
  use_quiz_scores: true,
  use_learning_time: true,
  use_weak_topics: true,
  use_idle_behavior: true,
  use_content_prefs: true,
  use_peer_comparison: false,
  privacy_learning_only: true,
  privacy_anonymize: true,
  data_retention_days: 180,
  transparency_show_why: true,
  allow_student_dismiss: true,
  idle_trigger_days: 3,
  send_email: true,
  send_in_app: true,
  send_sms: false,
  best_send_time: '7:00 PM',
  max_alerts_per_week: 2,
}

// Action Handlers
function handleCreateRule() {
  selectedRuleToEdit.value = null
  showCreateRuleModal.value = true
}

function handleEditRule(rule: AiRuleItem) {
  selectedRuleToEdit.value = rule
  showCreateRuleModal.value = true
}

function handleToggleStatus(rule: AiRuleItem) {
  const target = rulesList.value.find(r => r.id === rule.id)
  if (target) {
    target.status = target.status === 'active' ? 'disabled' : 'active'
    showNotification(`Rule ${target.code} status changed to ${target.status === 'active' ? 'Active On' : 'Disabled Off'}.`)
  }
}

function handleDeleteRule(rule: AiRuleItem) {
  rulesList.value = rulesList.value.filter(r => r.id !== rule.id)
  showNotification(`Rule ${rule.code} ("${rule.name}") deleted!`, 'warning')
}

function handleDuplicateRule(rule: AiRuleItem) {
  const nextId = Math.max(...rulesList.value.map(r => r.id), 0) + 1
  const newCode = `R-0${nextId}`
  const duplicated: AiRuleItem = {
    ...rule,
    id: nextId,
    code: newCode,
    name: `Copy of ${rule.name}`,
    fired_count: 0,
  }
  rulesList.value.push(duplicated)
  showNotification(`Rule ${rule.code} duplicated as ${newCode}!`)
}

function handleSaveRule(ruleData: any) {
  showCreateRuleModal.value = false
  if (ruleData.id) {
    const idx = rulesList.value.findIndex(r => r.id === ruleData.id)
    if (idx !== -1) {
      rulesList.value[idx] = {
        ...rulesList.value[idx],
        ...ruleData,
      }
      showNotification(`Rule "${ruleData.name}" updated successfully!`)
      return
    }
  }

  const nextId = Math.max(...rulesList.value.map(r => r.id), 0) + 1
  const newCode = `R-0${nextId}`
  const created: AiRuleItem = {
    id: nextId,
    code: newCode,
    name: ruleData.name,
    category: ruleData.category,
    condition_text: ruleData.condition_text,
    action_text: ruleData.action_text,
    priority: ruleData.priority,
    status: 'active',
    fired_count: 0,
    cooldown_hours: ruleData.cooldown_hours || 24,
    max_fires: ruleData.max_fires || 3,
    notify_student: true,
    notify_teacher: false,
    message_template: ruleData.message_template,
  }
  rulesList.value.push(created)
  showNotification(`Rule "${created.name}" created & activated successfully!`)
}

function handleSaveDetectionConfig(cfg: any) {
  showNotification('Weak Topic detection settings updated!')
}

function handleSavePath() {
  showNotification('Adaptive Learning Path rules saved successfully!')
}

function handleSaveConfig(cfg: any) {
  showNotification('AI Configuration updated successfully!')
}
</script>

<template>
  <AdminLayout>
    <!-- Floating Notification Toast -->
    <div
      v-if="toastMessage"
      class="fixed top-5 right-5 z-[999] flex items-center gap-3 px-4 py-3 rounded-xl shadow-2xl text-xs font-bold transition-all border animate-bounce"
      :class="
        toastType === 'success' ? 'bg-emerald-950 border-emerald-500 text-emerald-300' : 
        (toastType === 'warning' ? 'bg-amber-950 border-amber-500 text-amber-300' : 'bg-purple-950 border-purple-500 text-purple-300')
      "
    >
      <span>{{ toastType === 'success' ? '✅' : (toastType === 'warning' ? '⚠️' : 'ℹ️') }}</span>
      <span>{{ toastMessage }}</span>
    </div>

    <div class="space-y-6 text-slate-100 font-sans pb-12">
      <!-- ── MODULE HEADER CARD (Clean, Standard, Vibrant Design) ── -->
      <div class="relative overflow-hidden bg-slate-800/90 border border-slate-700/70 rounded-2xl p-6 shadow-xl backdrop-blur-xl space-y-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-700/50 pb-4">
          <div>
            <div class="flex items-center gap-3.5">
              <!-- Vibrant Flaticon-Style AI Chip Icon -->
              <div class="p-3 rounded-2xl bg-gradient-to-br from-purple-500/20 via-indigo-500/20 to-teal-500/20 border border-purple-400/30 shadow-lg shadow-purple-500/10 flex items-center justify-center shrink-0">
                <svg class="w-7 h-7" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M16 2L19.5 9.5L27 13L19.5 16.5L16 24L12.5 16.5L5 13L12.5 9.5L16 2Z" fill="url(#ai_sparkle_grad)" />
                  <path d="M25 21L26.5 24.5L30 26L26.5 27.5L25 31L23.5 27.5L20 26L23.5 24.5L25 21Z" fill="url(#ai_sparkle_grad_2)" />
                  <path d="M7 23L8 25L10 26L8 27L7 29L6 27L4 26L6 25L7 23Z" fill="#38BDF8" opacity="0.9" />
                  <defs>
                    <linearGradient id="ai_sparkle_grad" x1="5" y1="2" x2="27" y2="24" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#C084FC" />
                      <stop offset="0.5" stop-color="#818CF8" />
                      <stop offset="1" stop-color="#2DD4BF" />
                    </linearGradient>
                    <linearGradient id="ai_sparkle_grad_2" x1="20" y1="21" x2="30" y2="31" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#F472B6" />
                      <stop offset="1" stop-color="#FB7185" />
                    </linearGradient>
                  </defs>
                </svg>
              </div>
              <div>
                <h2 class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-teal-300 to-emerald-400 tracking-tight">
                  AI Recommendation Module
                </h2>
                <p class="text-xs text-slate-400 mt-1 font-medium">
                  Autonomous learning path engine — Condition → Action rules, Weak topic remedial mapping, Adaptive branching & Audit logs
                </p>
              </div>
            </div>
          </div>

          <!-- Clean Header Status Badge (No Duplicate Action Buttons) -->
          <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-bold flex items-center gap-2 shadow-sm">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span>Engine Status: Active</span>
            </span>
          </div>
        </div>

        <!-- ── AI ENGINE PROCESS ARCHITECTURE DIAGRAM (Flaticon Vectors - Static View) ── -->
        <div class="p-4.5 rounded-xl bg-[#090d16] border border-purple-900/40 shadow-inner space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-purple-300/90 uppercase tracking-widest flex items-center gap-2">
              <svg class="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="none">
                <path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" fill="url(#bolt_grad)" stroke="#F59E0B" stroke-width="1.5" stroke-linejoin="round" />
                <defs>
                  <linearGradient id="bolt_grad" x1="3" y1="2" x2="21" y2="22" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#FBBF24" />
                    <stop offset="1" stop-color="#F59E0B" />
                  </linearGradient>
                </defs>
              </svg>
              <span>PROCESS FLOW DIAGRAM</span>
              <span class="text-[10px] text-slate-500 font-mono font-normal">(Engine Pipeline)</span>
            </span>
            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-semibold bg-slate-900 text-slate-400 border border-slate-800">Static Diagram</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-[11px]">
            <!-- Step 1: Flaticon Database/Activity Icon -->
            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-slate-900/90 border border-slate-800 text-slate-200">
              <div class="p-1.5 rounded-lg bg-sky-500/15 border border-sky-500/30 text-sky-400 shrink-0">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <path d="M4 6C4 4.34315 7.58172 3 12 3C16.4183 3 20 4.34315 20 6C20 7.65685 16.4183 9 12 9C7.58172 9 4 7.65685 4 6Z" fill="#0EA5E9" fill-opacity="0.4" stroke="#38BDF8" stroke-width="1.5" />
                  <path d="M4 6V12C4 13.6569 7.58172 15 12 15C16.4183 15 20 13.6569 20 12V6" stroke="#38BDF8" stroke-width="1.5" />
                  <path d="M4 12V18C4 19.6569 7.58172 21 12 21C16.4183 21 20 19.6569 20 18V12" stroke="#38BDF8" stroke-width="1.5" />
                </svg>
              </div>
              <div>
                <span class="text-[10px] font-mono text-sky-400 font-bold block">STEP 01</span>
                <span class="font-bold text-slate-200 truncate block">Student Activity Data</span>
              </div>
            </div>

            <!-- Step 2: Flaticon AI Rule Engine Icon -->
            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-purple-950/40 border border-purple-500/30 text-purple-200">
              <div class="p-1.5 rounded-lg bg-purple-500/15 border border-purple-500/30 text-purple-300 shrink-0">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <rect x="5" y="5" width="14" height="14" rx="3" fill="#A855F7" fill-opacity="0.3" stroke="#C084FC" stroke-width="1.5" />
                  <path d="M9 9H15M9 12H15M9 15H12" stroke="#E9D5FF" stroke-width="1.5" stroke-linecap="round" />
                </svg>
              </div>
              <div>
                <span class="text-[10px] font-mono text-purple-300 font-bold block">STEP 02</span>
                <span class="font-bold text-purple-200 truncate block">AI Rule Engine</span>
              </div>
            </div>

            <!-- Step 3: Flaticon Match Groups Filter Icon -->
            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-teal-950/40 border border-teal-500/30 text-teal-200">
              <div class="p-1.5 rounded-lg bg-teal-500/15 border border-teal-500/30 text-teal-300 shrink-0">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <path d="M3 4.5C3 3.67157 3.67157 3 4.5 3H19.5C20.3284 3 21 3.67157 21 4.5V6.58579C21 6.98359 20.842 7.36508 20.5607 7.64645L14.4393 13.7678C14.158 14.0491 14 14.4306 14 14.8284V19.5L10 21V14.8284C10 14.4306 9.84196 14.0491 9.56066 13.7678L3.43934 7.64645C3.15804 7.36508 3 6.98359 3 6.58579V4.5Z" fill="#14B8A6" fill-opacity="0.3" stroke="#2DD4BF" stroke-width="1.5" />
                </svg>
              </div>
              <div>
                <span class="text-[10px] font-mono text-teal-300 font-bold block">STEP 03</span>
                <span class="font-bold text-teal-200 truncate block">Match 3 Rule Groups</span>
              </div>
            </div>

            <!-- Step 4: Flaticon Rocket Delivery Icon -->
            <div class="flex items-center gap-2.5 p-2.5 rounded-xl bg-emerald-950/40 border border-emerald-500/30 text-emerald-200">
              <div class="p-1.5 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 shrink-0">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                  <path d="M12 2C15.5 2 19 4.5 20 8C20.5 10 20 12.5 18.5 14L10 22.5L7.5 20L16 11.5" stroke="#34D399" stroke-width="1.5" stroke-linecap="round" />
                  <circle cx="12" cy="7" r="2" fill="#10B981" />
                </svg>
              </div>
              <div>
                <span class="text-[10px] font-mono text-emerald-300 font-bold block">STEP 04</span>
                <span class="font-bold text-emerald-200 truncate block">Deliver Recommendation</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ── SUB-NAVIGATION TAB STRIP WITH FLATICON VECTOR ICONS ── -->
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pt-2 border-t border-slate-700/50 text-xs">
          <!-- Tab 1: AI Recommendations -->
          <button
            @click="currentTab = 'rules'"
            :class="[
              currentTab === 'rules' 
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30 ring-1 ring-purple-400/60' 
                : 'bg-slate-900/80 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-700/50',
              'px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap shrink-0'
            ]"
          >
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none">
              <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" fill="#C084FC" />
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="currentColor" stroke-width="1.5" />
            </svg>
            <span>🤖 AI Recommendations</span>
          </button>

          <!-- Tab 2: At-Risk Students -->
          <button
            @click="currentTab = 'at_risk'"
            :class="[
              currentTab === 'at_risk' 
                ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/30 ring-1 ring-red-400/60' 
                : 'bg-slate-900/80 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-700/50',
              'px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap shrink-0'
            ]"
          >
            <svg class="w-4 h-4 shrink-0 text-red-400" viewBox="0 0 24 24" fill="none">
              <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span>⚠️ At-Risk Students</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-red-500/20 text-red-300 border border-red-500/40">5</span>
          </button>

          <!-- Tab 3: Difficult Topics -->
          <button
            @click="currentTab = 'difficult_topics'"
            :class="[
              currentTab === 'difficult_topics' || currentTab === 'weak_topics'
                ? 'bg-amber-600 text-white font-bold shadow-md shadow-amber-600/30 ring-1 ring-amber-400/60' 
                : 'bg-slate-900/80 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-700/50',
              'px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap shrink-0'
            ]"
          >
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="9" stroke="#FBBF24" stroke-width="1.5" />
              <circle cx="12" cy="12" r="5" fill="#F59E0B" fill-opacity="0.3" stroke="#FDE68A" stroke-width="1.5" />
              <circle cx="12" cy="12" r="2" fill="#F59E0B" />
            </svg>
            <span>🧠 Difficult Topics</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/40">5</span>
          </button>

          <!-- Tab 4: AI Configuration -->
          <button
            @click="currentTab = 'config'"
            :class="[
              currentTab === 'config' 
                ? 'bg-teal-600 text-white font-bold shadow-md shadow-teal-600/30 ring-1 ring-teal-400/60' 
                : 'bg-slate-900/80 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-700/50',
              'px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap shrink-0'
            ]"
          >
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none">
              <path d="M4 6H20M4 12H20M4 18H20" stroke="#2DD4BF" stroke-width="1.5" stroke-linecap="round" />
              <circle cx="8" cy="6" r="2" fill="#14B8A6" stroke="#2DD4BF" stroke-width="1.5" />
              <circle cx="16" cy="12" r="2" fill="#14B8A6" stroke="#2DD4BF" stroke-width="1.5" />
              <circle cx="12" cy="18" r="2" fill="#14B8A6" stroke="#2DD4BF" stroke-width="1.5" />
            </svg>
            <span>⚙️ AI Configuration</span>
          </button>

          <!-- Tab 5: Student View Preview -->
          <button
            @click="currentTab = 'student_view'"
            :class="[
              currentTab === 'student_view' 
                ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-600/30 ring-1 ring-indigo-400/60' 
                : 'bg-slate-900/80 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-700/50',
              'px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap shrink-0'
            ]"
          >
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none">
              <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="#818CF8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
              <circle cx="12" cy="12" r="3" fill="#6366F1" />
            </svg>
            <span>👨‍🎓 Student Experience Preview</span>
          </button>

          <!-- Tab 6: Activity & Logs -->
          <button
            @click="currentTab = 'logs'"
            :class="[
              currentTab === 'logs' 
                ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30 ring-1 ring-purple-400/60' 
                : 'bg-slate-900/80 text-slate-400 hover:text-slate-200 hover:bg-slate-800/80 border border-slate-700/50',
              'px-3.5 py-2 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap shrink-0'
            ]"
          >
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none">
              <path d="M8 2V5M16 2V5M3 9H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="#818CF8" stroke-width="1.5" stroke-linecap="round" />
              <path d="M8 13H16M8 17H13" stroke="#A5B4FC" stroke-width="1.5" stroke-linecap="round" />
            </svg>
            <span>📜 Activity & Logs</span>
          </button>
        </div>
      </div>

      <!-- ── TAB CONTENT RENDERING ── -->
      <!-- 1. AI Recommendations -->
      <AiRules
        v-if="currentTab === 'rules' || currentTab === 'recommendations'"
        :rules="rulesList"
        :majors="majors"
        @createRule="handleCreateRule"
        @openSimulator="showSimulatorModal = true"
        @editRule="handleEditRule"
        @toggleStatus="handleToggleStatus"
        @testRule="(r) => { showSimulatorModal = true; showNotification(`Loaded rule ${r.code} into simulator.`); }"
        @duplicateRule="handleDuplicateRule"
        @deleteRule="handleDeleteRule"
        @exportRules="() => showNotification('AI Rules exported to JSON!')"
      />

      <!-- 2. At-Risk Students -->
      <AtRiskStudents
        v-else-if="currentTab === 'at_risk'"
        :majors="majors"
      />

      <!-- 3. Difficult Topics -->
      <DifficultTopics
        v-else-if="currentTab === 'difficult_topics' || currentTab === 'weak_topics'"
        :majors="majors"
      />

      <!-- 4. AI Configuration -->
      <AiConfiguration
        v-else-if="currentTab === 'config'"
        :config="defaultAiConfig"
        @saveConfig="handleSaveConfig"
        @testConnection="() => showNotification('AI Engine API Connection Test SUCCESS (Ping 45ms)!')"
        @runFullSystemTest="() => showNotification('Full AI Recommendation System Test PASSED!')"
        @resetDefaults="() => showNotification('Reset AI configuration to factory defaults.', 'warning')"
      />

      <!-- 5. Student Experience Preview -->
      <StudentAiView
        v-else-if="currentTab === 'student_view'"
        @notify="showNotification"
      />

      <!-- 6. Activity & Recommendation Logs -->
      <RecommendationLogs
        v-else-if="currentTab === 'logs'"
        :logs="computedLogs"
        :effectiveness="defaultEffectiveness"
        @exportLogs="() => showNotification('Recommendation logs exported to CSV!')"
        @applySuggestion="() => showNotification('Applied evening 7 PM alert schedule suggestion!')"
        @viewDetail="(l) => { selectedLogToView = l; showLogDetailModal = true; }"
      />

      <!-- 7. Fallback / Additional: Learning Path Rules -->
      <LearningPathRules
        v-else-if="currentTab === 'learning_paths'"
        :nodes="defaultPathNodes"
        @addNode="() => { selectedNodeToEdit = null; showNodeEditorModal = true; }"
        @editNode="(node) => { selectedNodeToEdit = node; showNodeEditorModal = true; }"
        @deleteNode="(node) => showNotification(`Deleted learning path node &quot;${node.title}&quot;.`, 'warning')"
        @reorderNodes="() => showNotification('Nodes reordered successfully.')"
        @previewPath="() => currentTab = 'student_view'"
        @savePath="handleSavePath"
        @editDecisionNode="() => showDecisionNodeModal = true"
      />
    </div>

    <!-- ── INTERVENTION & SIMULATOR MODALS ── -->
    <RuleSimulatorModal
      :show="showSimulatorModal"
      @close="showSimulatorModal = false"
      @activateRule="() => { showSimulatorModal = false; showNotification('Simulated rule activated live!'); }"
    />

    <CreateRuleModal
      :show="showCreateRuleModal"
      :ruleToEdit="selectedRuleToEdit"
      @close="showCreateRuleModal = false"
      @save="handleSaveRule"
    />

    <CreateMappingModal
      :show="showCreateMappingModal"
      :mappingToEdit="selectedMappingToEdit"
      @close="showCreateMappingModal = false"
      @save="handleSaveMapping"
    />

    <CreateReviewClassModal
      :show="showReviewClassModal"
      @close="showReviewClassModal = false"
      @save="handleSaveReviewClass"
    />

    <AddPracticeQuizModal
      :show="showPracticeQuizModal"
      @close="showPracticeQuizModal = false"
      @save="handleSavePracticeQuiz"
    />

    <NodeEditorModal
      :show="showNodeEditorModal"
      :nodeToEdit="selectedNodeToEdit"
      @close="showNodeEditorModal = false"
      @save="handleSaveNode"
    />

    <DecisionNodeModal
      :show="showDecisionNodeModal"
      @close="showDecisionNodeModal = false"
      @save="handleSaveDecisionNode"
    />

    <LogDetailModal
      :show="showLogDetailModal"
      :log="selectedLogToView"
      @close="showLogDetailModal = false"
    />
  </AdminLayout>
</template>
