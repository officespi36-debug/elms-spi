<script setup lang="ts">
import { ref, computed, defineAsyncComponent } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import type { QuestionItem, QuizItem, AssignmentItem, ResultItem, Stats } from './types'

// Sub-Components
import QuestionBank from './QuestionBank.vue'
import AllQuizzes from './AllQuizzes.vue'
import Assignments from './Assignments.vue'
import QuizResults from './QuizResults.vue'

// Lazy-Loaded Modals
const CreateQuestionModal = defineAsyncComponent(() => import('./Modals/CreateQuestionModal.vue'))
const CreateQuizModal = defineAsyncComponent(() => import('./Modals/CreateQuizModal.vue'))
const CreateAssignmentModal = defineAsyncComponent(() => import('./Modals/CreateAssignmentModal.vue'))
const GradingModal = defineAsyncComponent(() => import('./Modals/GradingModal.vue'))
const SimulationsModal = defineAsyncComponent(() => import('./Modals/SimulationsModal.vue'))

const props = defineProps<{
  questions?: QuestionItem[]
  questionBank?: QuestionItem[]
  quizzes?: QuizItem[]
  assignments?: AssignmentItem[]
  results?: ResultItem[]
  stats?: Stats
  categories?: any[]
  courses?: any[]
  allCourses?: any[]
  activeTab?: string
  typeFilter?: string
}>()

// Safe Computed & Reactive Data Initialization
const localCourses = ref<any[]>([...(props.allCourses || props.courses || [])])
const coursesList = computed<any[]>(() => localCourses.value.length > 0 ? localCourses.value : (props.allCourses || props.courses || []))

const localQuestionBank = ref<QuestionItem[]>([...(props.questionBank || props.questions || [])])
const localQuizzes = ref<QuizItem[]>([...(props.quizzes || [])])
const localAssignments = ref<AssignmentItem[]>([...(props.assignments || [])])
const localResults = ref<ResultItem[]>([...(props.results || [])])
const localCategories = ref<any[]>([...(props.categories || [])])

// Active Tab state handling query params ?type=pre_test or ?tab=all
const initialTab = props.activeTab 
  ? props.activeTab 
  : (props.typeFilter && props.typeFilter !== 'all' ? props.typeFilter : 'all')

const currentTab = ref<string>(initialTab)
const quizFilterType = ref<string>(props.typeFilter || 'all')

const activeTabLabel = computed(() => {
  const map: Record<string, string> = {
    all: 'Quizzes',
    assignments: 'Assignments',
    bank: 'Question Bank',
    pre_test: 'Pre-Tests',
    practice: 'Practice Quizzes',
    post_test: 'Post-Tests',
    results: 'Quiz Results'
  }
  return map[currentTab.value] || 'Quizzes'
})

const activeTabEmoji = computed(() => {
  const map: Record<string, string> = {
    all: '📝',
    assignments: '📄',
    bank: '📚',
    pre_test: '🚀',
    practice: '✍️',
    post_test: '🏁',
    results: '📊'
  }
  return map[currentTab.value] || '📝'
})

const showCreateDropdown = ref(false)

// Modal Visibility Controls
const showCreateQuestionModal = ref(false)
const showCreateQuizModal = ref(false)
const showCreateAssignmentModal = ref(false)
const showGradingModal = ref(false)
const selectedGradingItem = ref<any | null>(null)

// Simulation & Helper Modals
const showQuestionPreviewModal = ref(false)
const showQuestionEditModal = ref(false)
const showAddToQuizModal = ref(false)
const showQuizPreviewModal = ref(false)
const showQuizEditModal = ref(false)
const showPreTestSimModal = ref(false)
const showPreTestResultModal = ref(false)
const showPracticeSimModal = ref(false)
const showPostTestResultModal = ref(false)
const showAiGeneratorModal = ref(false)
const showAddCategoryModal = ref(false)

const selectedQuestion = ref<QuestionItem | null>(null)
const selectedQuiz = ref<QuizItem | null>(null)

// Notification Toast State
const toastMessage = ref('')
const toastType = ref<'success' | 'info' | 'warning'>('success')

function showNotification(msg: string, type: 'success' | 'info' | 'warning' = 'success') {
  toastMessage.value = msg
  toastType.value = type
  setTimeout(() => {
    toastMessage.value = ''
  }, 3500)
}

function switchTab(tabId: string) {
  currentTab.value = tabId
  if (['all', 'pre_test', 'practice', 'post_test'].includes(tabId)) {
    quizFilterType.value = tabId === 'all' ? 'all' : tabId
  }
}

function handleOpenQuestionPreview(q: QuestionItem) {
  selectedQuestion.value = q
  showQuestionPreviewModal.value = true
}

function handleOpenQuestionEdit(q: QuestionItem) {
  selectedQuestion.value = { ...q }
  showQuestionEditModal.value = true
}

function handleOpenAddToQuiz(q: QuestionItem) {
  selectedQuestion.value = q
  showAddToQuizModal.value = true
}

function handleDeleteQuestion(id: string) {
  if (confirm(`Are you sure you want to delete question ${id}?`)) {
    localQuestionBank.value = localQuestionBank.value.filter(q => q.id !== id)
    showNotification(`Question ${id} deleted successfully.`, 'warning')
  }
}

function handleOpenQuizPreview(qz: QuizItem) {
  selectedQuiz.value = qz
  showQuizPreviewModal.value = true
}

function handleOpenQuizEdit(qz: QuizItem) {
  selectedQuiz.value = { ...qz }
  showQuizEditModal.value = true
}

function handleToggleQuizStatus(qz: QuizItem) {
  qz.status = qz.status === 'published' ? 'draft' : 'published'
  showNotification(`Quiz "${qz.title}" is now ${qz.status}.`, 'info')
}

function handleSaveQuestion(newQ: any) {
  const nextId = `Q-10${localQuestionBank.value.length + 1}`
  const created: QuestionItem = {
    id: nextId,
    question: newQ.question,
    type: newQ.type,
    type_label: newQ.type === 'mcq' ? 'Multiple Choice' : (newQ.type === 'true_false' ? 'True/False' : (newQ.type === 'essay' ? 'Essay' : (newQ.type === 'matching' ? 'Matching' : (newQ.type === 'fill_blank' ? 'Fill Blank' : 'Coding')))),
    difficulty: newQ.difficulty,
    marks: Number(newQ.marks),
    course: newQ.course,
    module: newQ.module || 'Module 1',
    chapter: newQ.chapter || 'Chapter 1',
    status: 'published',
    options: newQ.options,
    correct_answer: newQ.correct_answer,
    explanation: newQ.explanation,
    sample_solution: newQ.sample_solution,
    rubric: newQ.rubric,
    word_limit: newQ.word_limit,
    pairs: newQ.pairs,
    created_by: 'Admin User',
  }
  localQuestionBank.value.unshift(created)
  showCreateQuestionModal.value = false
  showNotification(`Question ${nextId} created and added to bank!`)
}

function handleSaveQuiz(newQuiz: any) {
  const created: QuizItem = {
    id: localQuizzes.value.length + 1,
    title: newQuiz.title,
    type: newQuiz.type,
    type_label: newQuiz.type === 'pre_test' ? 'Pre-Test' : (newQuiz.type === 'practice' ? 'Practice Quiz' : 'Post-Test'),
    course: newQuiz.course,
    module: newQuiz.module,
    questions_count: newQuiz.selected_questions?.length || 5,
    total_marks: (newQuiz.selected_questions?.length || 5) * 2,
    time_limit_minutes: Number(newQuiz.time_limit_minutes),
    passing_score: Number(newQuiz.passing_score),
    attempts_allowed: Number(newQuiz.max_attempts),
    max_attempts: Number(newQuiz.max_attempts),
    status: newQuiz.status,
    created_by: 'Admin User',
  }
  localQuizzes.value.unshift(created)
  showCreateQuizModal.value = false
  showNotification(`Quiz "${created.title}" created successfully!`)
}

function handleSaveAssignment(newAsn: any) {
  const nextId = `ASN-0${localAssignments.value.length + 1}`
  const created: AssignmentItem = {
    id: nextId,
    title: newAsn.title,
    course: newAsn.course,
    module: newAsn.module,
    chapter: newAsn.chapter,
    due_date: newAsn.due_date,
    submitted_count: 0,
    submissions_count: 0,
    total_students: 48,
    max_marks: Number(newAsn.max_marks),
    status: 'active',
    status_label: 'Active',
    file_types: newAsn.file_types,
    created_by: 'Admin User',
  }
  localAssignments.value.unshift(created)
  showCreateAssignmentModal.value = false
  showNotification(`Assignment ${nextId} published successfully!`)
}

function handleDeleteAssignment(id: string) {
  if (confirm(`Are you sure you want to delete assignment ${id}?`)) {
    localAssignments.value = localAssignments.value.filter(a => a.id !== id)
    showNotification(`Assignment ${id} deleted.`, 'warning')
  }
}

function handleOpenGrading(item: any) {
  selectedGradingItem.value = item
  showGradingModal.value = true
}

function handleSubmitGrade(score: number, feedback: string) {
  if (selectedGradingItem.value && selectedGradingItem.value.score !== undefined) {
    selectedGradingItem.value.score = score
    selectedGradingItem.value.passed = score >= (selectedGradingItem.value.passing_score || 60)
    selectedGradingItem.value.graded_by = 'Admin (Evaluated)'
  }
  showGradingModal.value = false
  showNotification(`Grade updated and sent to student!`)
}

function handleSaveEditedQuestion(q: QuestionItem) {
  const idx = localQuestionBank.value.findIndex(item => item.id === q.id)
  if (idx !== -1) localQuestionBank.value[idx] = { ...q }
  closeAllModals()
  showNotification(`Question ${q.id} updated!`)
}

function handleSaveEditedQuiz(qz: QuizItem) {
  const idx = localQuizzes.value.findIndex(item => item.id === qz.id)
  if (idx !== -1) localQuizzes.value[idx] = { ...qz }
  closeAllModals()
  showNotification(`Quiz "${qz.title}" updated!`)
}

function handleConfirmAddToQuiz(targetQuizId: number) {
  const qz = localQuizzes.value.find(item => item.id === targetQuizId)
  if (qz && selectedQuestion.value) {
    qz.questions_count += 1
    qz.total_marks += selectedQuestion.value.marks
    showNotification(`Question attached to "${qz.title}"!`)
  }
  closeAllModals()
}

function handleRunAiGenerator(config?: any) {
  const course = config?.course || 'C Programming Basics'
  const type = config?.type || 'mcq'
  const difficulty = config?.difficulty || 'medium'
  const numQuestions = parseInt(config?.numQuestions || '5')
  const topic = config?.topic || 'Pointers & Memory Allocation'
  const extraContext = config?.context ? ` (Context: ${config.context})` : ''

  const typeLabels: Record<string, string> = {
    mcq: 'Multiple Choice',
    true_false: 'True/False',
    essay: 'Essay',
    matching: 'Matching',
    fill_blank: 'Fill Blank',
    coding: 'Coding'
  }

  for (let i = 0; i < Math.min(numQuestions, 5); i++) {
    const nextId = `Q-AI-${Date.now().toString().slice(-4)}-${i + 1}`
    const generated: QuestionItem = {
      id: nextId,
      question: `[AI Generated - ${topic}] What is the primary behavior when executing code involving ${topic.toLowerCase()}?${extraContext}`,
      type: type,
      type_label: typeLabels[type] || 'Multiple Choice',
      difficulty: difficulty,
      marks: difficulty === 'hard' ? 4 : (difficulty === 'medium' ? 2 : 1),
      course: course,
      module: `Module ${i + 1}: ${topic}`,
      chapter: `Chapter ${i + 1}.1`,
      status: 'published',
      options: ['Valid Execution Block', 'Memory Violation / Segfault', 'Returns Default Zero', 'Undefined Compiler Behavior'],
      correct_answer: 'Memory Violation / Segfault',
      explanation: `Automated AI explanation generated for ${topic} under ${course}.`,
      created_by: 'AI Question Gen',
    }
    localQuestionBank.value.unshift(generated)
  }
  
  closeAllModals()
  showNotification(`✨ ${Math.min(numQuestions, 5)} AI generated question(s) added to Question Bank!`)
}

function handleAddCategory(name: string) {
  localCategories.value.push({
    id: Date.now(),
    name,
    total_questions: 0,
    modules: [],
  })
  closeAllModals()
  showNotification('Category added successfully!')
}

function closeAllModals() {
  showQuestionPreviewModal.value = false
  showQuestionEditModal.value = false
  showAddToQuizModal.value = false
  showQuizPreviewModal.value = false
  showQuizEditModal.value = false
  showPreTestSimModal.value = false
  showPreTestResultModal.value = false
  showPracticeSimModal.value = false
  showPostTestResultModal.value = false
  showAiGeneratorModal.value = false
  showAddCategoryModal.value = false
}
</script>

<template>
  <AdminLayout>
    <!-- Floating Toast Notification -->
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
      <!-- ── SINGLE UNIFIED BREADCRUMB NAVIGATION ── -->
      <nav class="flex items-center gap-2.5 text-xs font-semibold text-slate-300 bg-slate-900/80 backdrop-blur-xl px-4 py-2 rounded-2xl border border-slate-700/70 shadow-lg w-max">
        <span class="hover:text-purple-400 transition-colors cursor-pointer flex items-center gap-1 text-slate-400" @click="switchTab('bank')">
          <span>Admin</span>
        </span>
        <span class="text-slate-600">/</span>
        <span class="hover:text-purple-400 transition-colors cursor-pointer text-slate-400" @click="switchTab('bank')">Quiz Module</span>
        <span class="text-slate-600">/</span>
        <span class="px-3 py-1 rounded-xl bg-gradient-to-r from-purple-600/30 to-indigo-600/30 text-white border border-purple-500/50 font-black flex items-center gap-1.5 shadow-inner">
          <span>{{ activeTabEmoji }}</span>
          <span class="tracking-wide">{{ activeTabLabel }}</span>
        </span>
      </nav>

      <!-- ── MODULE HEADER CARD WITH UNIFIED CTA DROPDOWN & AI GENERATOR ── -->
      <div class="relative overflow-hidden bg-slate-800/90 border border-slate-700/70 rounded-2xl p-5 shadow-2xl backdrop-blur-xl space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-700/50 pb-4">
          <div>
            <div class="flex items-center gap-3">
              <div class="p-2.5 rounded-2xl bg-gradient-to-br from-purple-500/20 to-indigo-500/20 border border-purple-500/30 shadow-inner flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
              </div>
              <h2 class="text-2xl font-black bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-teal-300 to-emerald-400 tracking-tight">
                Quiz & Assessment Module
              </h2>
            </div>
            <p class="text-xs text-slate-400 mt-1.5 font-medium">
              Comprehensive evaluation center — Question Bank, Quizzes, Pre/Post Tests, Assignments & Analytics
            </p>
          </div>

          <!-- Actions Tier: Streamlined Single CTA + AI Generator -->
          <div class="flex flex-wrap items-center gap-2.5 relative">
            <!-- Invisible Backdrop for dropdown close -->
            <div
              v-if="showCreateDropdown"
              @click="showCreateDropdown = false"
              class="fixed inset-0 z-20 cursor-default"
            ></div>

            <!-- AI Generator Button -->
            <button
              @click="showAiGeneratorModal = true"
              class="px-3.5 py-2 bg-gradient-to-r from-indigo-600/80 to-purple-600/80 hover:brightness-110 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 border border-indigo-400/30 shadow-md transition-all cursor-pointer relative z-10"
            >
              <span>🤖</span>
              <span>AI Question Gen</span>
            </button>

            <!-- Single Primary CTA Dropdown -->
            <div class="relative z-30">
              <button
                @click="showCreateDropdown = !showCreateDropdown"
                class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:brightness-110 text-white rounded-xl text-xs font-bold flex items-center gap-2 border border-purple-400/40 shadow-lg shadow-purple-600/30 transition-all cursor-pointer"
              >
                <svg class="w-4 h-4 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Create New</span>
                <svg class="w-3.5 h-3.5 text-purple-200 transition-transform duration-200" :class="showCreateDropdown ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Dropdown Menu -->
              <div
                v-if="showCreateDropdown"
                class="absolute right-0 mt-2 w-52 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl z-40 py-1.5 text-xs text-left animate-fadeIn"
              >
                <button
                  @click="showCreateQuestionModal = true; showCreateDropdown = false"
                  class="w-full px-3.5 py-2 text-slate-200 hover:bg-purple-900/40 hover:text-purple-300 flex items-center gap-2.5 font-semibold transition-colors cursor-pointer"
                >
                  <span>📚</span>
                  <span>Create Question</span>
                </button>
                <button
                  @click="showCreateQuizModal = true; showCreateDropdown = false"
                  class="w-full px-3.5 py-2 text-slate-200 hover:bg-purple-900/40 hover:text-purple-300 flex items-center gap-2.5 font-semibold transition-colors cursor-pointer"
                >
                  <span>📝</span>
                  <span>Create Quiz</span>
                </button>
                <button
                  @click="showCreateAssignmentModal = true; showCreateDropdown = false"
                  class="w-full px-3.5 py-2 text-slate-200 hover:bg-purple-900/40 hover:text-purple-300 flex items-center gap-2.5 font-semibold transition-colors cursor-pointer"
                >
                  <span>📄</span>
                  <span>Create Assignment</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── STREAMLINED PRIMARY SUB-NAVIGATION TABS (STANDARD UNDERLINE/ACTIVE HIGHLIGHT) ── -->
        <div class="flex items-center justify-between gap-2 overflow-x-auto pb-1 text-xs">
          <div class="flex items-center gap-2">
            <button
              v-for="t in [
                { id: 'all', label: 'Quizzes', emoji: '📝', count: localQuizzes.length },
                { id: 'assignments', label: 'Assignments', emoji: '📄', count: localAssignments.length },
                { id: 'bank', label: 'Question Bank', emoji: '📚', count: localQuestionBank.length },
                { id: 'results', label: 'Quiz Results', emoji: '📊', count: localResults.length },
              ]"
              :key="t.id"
              @click="switchTab(t.id)"
              :class="[
                currentTab === t.id 
                  ? 'bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 text-white font-extrabold shadow-lg shadow-purple-600/30 ring-1 ring-purple-400/50 border border-purple-400/40 opacity-100' 
                  : 'bg-slate-900/60 text-slate-400 hover:text-white hover:bg-slate-800/80 border border-slate-700/60 opacity-80 hover:opacity-100',
                'px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 text-xs font-semibold cursor-pointer shadow-sm shrink-0'
              ]"
            >
              <span>{{ t.emoji }}</span>
              <span>{{ t.label }}</span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                :class="currentTab === t.id ? 'bg-white/20 text-white' : 'bg-slate-800 text-purple-300 border border-purple-500/20'"
              >
                {{ t.count }}
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- ── TAB CONTENT RENDERING ── -->
      <QuestionBank
        v-if="currentTab === 'bank'"
        :questions="localQuestionBank"
        :categories="localCategories"
        :stats="stats || { total_questions: 2450, active_questions: 2210, draft_questions: 240, courses_linked: 328, total_quizzes: 560, published_quizzes: 420, draft_quizzes: 85, scheduled_quizzes: 55, total_assignments: 245, pending_assignments: 82, graded_assignments: 145, overdue_assignments: 18, avg_pass_rate: 78, total_attempts: 12840 }"
        @openPreview="handleOpenQuestionPreview"
        @openEdit="handleOpenQuestionEdit"
        @openAddToQuiz="handleOpenAddToQuiz"
        @deleteQuestion="handleDeleteQuestion"
        @openAiGenerator="showAiGeneratorModal = true"
        @openAddCategory="showAddCategoryModal = true"
      />

      <AllQuizzes
        v-else-if="['all', 'pre_test', 'practice', 'post_test'].includes(currentTab)"
        :quizzes="localQuizzes"
        :filterType="quizFilterType"
        @openPreview="handleOpenQuizPreview"
        @openEdit="handleOpenQuizEdit"
        @toggleStatus="handleToggleQuizStatus"
        @openCreateQuiz="showCreateQuizModal = true"
        @openPracticeSim="showPracticeSimModal = true"
      />

      <Assignments
        v-else-if="currentTab === 'assignments'"
        :assignments="localAssignments"
        @openCreateAssignment="showCreateAssignmentModal = true"
        @deleteAssignment="handleDeleteAssignment"
        @openGrading="handleOpenGrading"
      />

      <QuizResults
        v-else-if="currentTab === 'results'"
        :results="localResults"
        @openGrading="handleOpenGrading"
      />
    </div>

    <!-- ── MODAL CONTAINERS ── -->
    <CreateQuestionModal
      :show="showCreateQuestionModal"
      :allCourses="localCourses"
      @close="showCreateQuestionModal = false"
      @save="handleSaveQuestion"
    />

    <CreateQuizModal
      :show="showCreateQuizModal"
      :allCourses="localCourses"
      :questionBank="localQuestionBank"
      @close="showCreateQuizModal = false"
      @save="handleSaveQuiz"
    />

    <CreateAssignmentModal
      :show="showCreateAssignmentModal"
      :allCourses="localCourses"
      @close="showCreateAssignmentModal = false"
      @save="handleSaveAssignment"
    />

    <GradingModal
      :show="showGradingModal"
      :item="selectedGradingItem"
      @close="showGradingModal = false"
      @save="handleSubmitGrade"
    />

    <SimulationsModal
      :showQuestionPreview="showQuestionPreviewModal"
      :showQuestionEdit="showQuestionEditModal"
      :showAddToQuiz="showAddToQuizModal"
      :showQuizPreview="showQuizPreviewModal"
      :showQuizEdit="showQuizEditModal"
      :showPreTestSim="showPreTestSimModal"
      :showPreTestResult="showPreTestResultModal"
      :showPracticeSim="showPracticeSimModal"
      :showPostTestResult="showPostTestResultModal"
      :showAiGenerator="showAiGeneratorModal"
      :showAddCategory="showAddCategoryModal"
      :selectedQuestion="selectedQuestion"
      :selectedQuiz="selectedQuiz"
      :quizzes="localQuizzes"
      @closeAll="closeAllModals"
      @saveEditedQuestion="handleSaveEditedQuestion"
      @saveEditedQuiz="handleSaveEditedQuiz"
      @confirmAddToQuiz="handleConfirmAddToQuiz"
      @runAiGenerator="handleRunAiGenerator"
      @addCategory="handleAddCategory"
    />
  </AdminLayout>
</template>
