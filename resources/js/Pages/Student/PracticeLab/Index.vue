<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

const props = defineProps<{
  activeTab?: string
}>()

const { currentLang, isKhmer } = useLanguage()
const isKm = computed(() => isKhmer.value || currentLang.value === 'km')

const selectedMajor = ref<string>('all')
const searchQuery = ref<string>('')

interface MajorLabItem {
  id: string
  major: string
  majorKm: string
  title: string
  titleKm: string
  icon: string
  href: string
  desc: string
  descKm: string
  topics: string[]
  topicsKm: string[]
  badge: string
  badgeKm: string
  accentColor: string
  buttonClass: string
}

const majorLabs: MajorLabItem[] = [
  {
    id: 'it',
    major: 'IT & Networking',
    majorKm: 'ព័ត៌មានវិទ្យា & បណ្តាញ',
    title: 'Interactive Coding Lab & Terminal Environment',
    titleKm: 'បន្ទប់អនុវត្តសរសេរកូដ & Terminal សាកល្បង',
    icon: '💻',
    href: '/student/practice-lab/it',
    desc: 'Online sandbox environment with interactive GCC C/C++ compiler, PHP/Laravel runtime, Python console, and AI code reviewer.',
    descKm: 'បរិស្ថានសរសេរកូដជាក់ស្តែងជាមួយ GCC C/C++, PHP, Python ព្រមទាំងប្រព័ន្ធត្រួតពិនិត្យ និងកែកំហុសកូដស្វ័យប្រវត្តិតាម AI។',
    topics: ['C/C++ Code Runner', 'Linux Terminal Simulation', 'Database Query Playground', 'Git & Branching Practice'],
    topicsKm: ['ដំណើរការកូដ C/C++', 'ការក្លែងធ្វើ Linux Terminal', 'កន្លែងសាកល្បង Database SQL', 'ការអនុវត្ត Git & Branching'],
    badge: 'Real-time Compiler',
    badgeKm: 'ដំណើរការកូដភ្លាមៗ',
    accentColor: 'border-cyan-500/30 text-cyan-500 dark:text-cyan-400 bg-cyan-500/10',
    buttonClass: 'bg-cyan-600 hover:bg-cyan-500 text-white shadow-cyan-600/20'
  },
  {
    id: 'tourism',
    major: 'Tourism & Hospitality',
    majorKm: 'ទេសចរណ៍ & បដិសណ្ឋារកិច្ច',
    title: 'Tourism Case Study & Scenario Analysis',
    titleKm: 'វិភាគករណីសិក្សា & សេវាកម្មទេសចរណ៍',
    icon: '✈️',
    href: '/student/practice-lab/tourism',
    desc: 'Simulate hotel guest reservations, Angkor Wat itinerary planning, VIP hospitality protocols, and customer conflict resolution.',
    descKm: 'ការក្លែងធ្វើការកក់បន្ទប់សណ្ឋាគារ រៀបចំកាលវិភាគដំណើរកម្សាន្តអង្គរវត្ត ពិធីការបម្រើភ្ញៀវ VIP និងការដោះស្រាយបណ្តឹងភ្ញៀវ។',
    topics: ['Hotel Front-Desk Simulation', 'Cambodia Tour Package Pricing', 'Eco-Tourism Case Analysis', 'Crisis Handling in Hospitality'],
    topicsKm: ['ការក្លែងធ្វើផ្នែកបដិសណ្ឋារកិច្ច (Front-Desk)', 'ការគណនាតម្លៃកញ្ចប់ទស្សនកិច្ចកម្ពុជា', 'ការវិភាគករណីទេសចរណ៍ធម្មជាតិ', 'ការដោះស្រាយវិបត្តិក្នុងវិស័យបដិសណ្ឋារកិច្ច'],
    badge: 'Scenario Simulator',
    badgeKm: 'ក្លែងធ្វើសេណារីយ៉ូ',
    accentColor: 'border-amber-500/30 text-amber-500 dark:text-amber-400 bg-amber-500/10',
    buttonClass: 'bg-amber-600 hover:bg-amber-500 text-white shadow-amber-600/20'
  },
  {
    id: 'english',
    major: 'English Literature',
    majorKm: 'អក្សរសាស្ត្រអង់គ្លេស',
    title: 'English Writing Lab & Grammar Checker',
    titleKm: 'បន្ទប់អនុវត្តតែងសេចក្តី & វេយ្យាករណ៍',
    icon: '📖',
    href: '/student/practice-lab/english',
    desc: 'Academic essay builder, grammatical cohesion evaluator, vocabulary enrichment, and automated instant writing assessment.',
    descKm: 'ការហ្វឹកហាត់តែងសេចក្តីបែបសិក្សាស្រាវជ្រាវ វាយតម្លៃវេយ្យាករណ៍ ពង្រឹងកម្រិតពាក្យ និងការផ្តល់មតិយោបល់ភ្លាមៗដោយ AI។',
    topics: ['Argumentative Essay Builder', 'Grammar Drill Challenges', 'Phonetics & Pronunciation Guide', 'Academic Abstract Writing'],
    topicsKm: ['ការរៀបចំតែងសេចក្តីបែបពិភាក្សា', 'លំហាត់អនុវត្តក្បួនវេយ្យាករណ៍', 'ការបញ្ចេញសំឡេង និងសូរសព្ទ', 'ការសរសេរ Abstract បែបសិក្សាស្រាវជ្រាវ'],
    badge: 'AI Writing Evaluator',
    badgeKm: 'វាយតម្លៃសំណេរដោយ AI',
    accentColor: 'border-purple-500/30 text-purple-500 dark:text-purple-400 bg-purple-500/10',
    buttonClass: 'bg-purple-600 hover:bg-purple-500 text-white shadow-purple-600/20'
  },
  {
    id: 'agronomy',
    major: 'Agronomy & Agriculture',
    majorKm: 'កសិកម្ម & វិទ្យាសាស្ត្រដំណាំ',
    title: 'Agronomy Field Simulation & Crop Diagnostics',
    titleKm: 'ការពិសោធន៍កសិកម្ម & ជំងឺដំណាំ',
    icon: '🌾',
    href: '/student/practice-lab/agronomy',
    desc: 'Soil pH calculator, rice crop irrigation schedule generator, pest & fungus diagnosis tool, and organic fertilizer recipes.',
    descKm: 'ម៉ាស៊ីនគណនាជី NPK និង pH ដី កាលវិភាគស្រោចស្រពស្រូវ ការធ្វើរោគវិនិច្ឆ័យសត្វល្អិត និងរូបមន្តជីសរីរាង្គ។',
    topics: ['Soil Nutrient & NPK Calculator', 'Pest & Fungus Diagnosis Tool', 'Drip Irrigation Planning', 'Rice Paddy Yield Estimation'],
    topicsKm: ['ម៉ាស៊ីនគណនាកម្រិតជី NPK និងជីវជាតិដី', 'ឧបករណ៍ធ្វើរោគវិនិច្ឆ័យជំងឺ និងសត្វល្អិត', 'ផែនការប្រព័ន្ធស្រោចស្រពដំណក់ទឹក', 'ការប៉ាន់ស្មានទិន្នផលស្រូវក្នុងមួយហិកតា'],
    badge: 'Crop Simulator',
    badgeKm: 'ពិសោធន៍ដំណាំកសិកម្ម',
    accentColor: 'border-emerald-500/30 text-emerald-500 dark:text-emerald-400 bg-emerald-500/10',
    buttonClass: 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/20'
  },
  {
    id: 'social-work',
    major: 'Social Work & Community',
    majorKm: 'ការងារសង្គម & សហគមន៍',
    title: 'Social Work Case Practice & Counseling Simulations',
    titleKm: 'ករណីសិក្សាការងារសង្គម & ការប្រឹក្សា',
    icon: '🤝',
    href: '/student/practice-lab/social-work',
    desc: 'Interactive role-play counseling scenarios, client intake assessment, child protection workflows, and ethical dilemma guidance.',
    descKm: 'ការក្លែងធ្វើការពិគ្រោះយោបល់ជាមួយអតិថិជន កិច្ចសម្ភាសន៍ស្វែងយល់ពីបញ្ហា ពិធីការការពារកុមារ និងការដោះស្រាយបញ្ហាក្រមសីលធម៌។',
    topics: ['Client Intake Assessment Tool', 'Child Protection Protocol Simulator', 'Community Resource Mapping', 'Ethics & Confidentiality Case Studies'],
    topicsKm: ['ឧបករណ៍សម្ភាសន៍ និងទទួលអតិថិជន', 'ការក្លែងធ្វើពិធីការការពារកុមារ', 'ការរៀបចំផែនទីធនធានក្នុងសហគមន៍', 'ករណីសិក្សាពីក្រមសីលធម៌ និងការរក្សាការសម្ងាត់'],
    badge: 'Counseling Lab',
    badgeKm: 'បន្ទប់ប្រឹក្សាជាក់ស្តែង',
    accentColor: 'border-rose-500/30 text-rose-500 dark:text-rose-400 bg-rose-500/10',
    buttonClass: 'bg-rose-600 hover:bg-rose-500 text-white shadow-rose-600/20'
  }
]

const filteredLabs = computed(() => {
  return majorLabs.filter(lab => {
    const matchesMajor = selectedMajor.value === 'all' || lab.id === selectedMajor.value
    const q = searchQuery.value.trim().toLowerCase()
    if (!q) return matchesMajor
    const matchesSearch = 
      lab.major.toLowerCase().includes(q) ||
      lab.majorKm.toLowerCase().includes(q) ||
      lab.title.toLowerCase().includes(q) ||
      lab.titleKm.toLowerCase().includes(q) ||
      lab.desc.toLowerCase().includes(q) ||
      lab.descKm.toLowerCase().includes(q) ||
      lab.topics.some(t => t.toLowerCase().includes(q)) ||
      lab.topicsKm.some(t => t.toLowerCase().includes(q))
    return matchesMajor && matchesSearch
  })
})
</script>

<template>
  <StudentLayout :title="isKm ? 'បន្ទប់អនុវត្តជំនាញទាំង ៥ (Practice Lab) — E-LMS' : 'Practice Lab (5 Majors) — E-LMS'">
    <div class="space-y-6 max-w-7xl mx-auto pb-12 px-2 sm:px-4">
      
      <!-- ================= 1. BREADCRUMB & HERO BANNER ================= -->
      <div class="space-y-3">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-400 dark:text-slate-500 font-medium">
          <Link href="/student/dashboard" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            {{ isKm ? 'ផ្ទាំងគ្រប់គ្រង' : 'Dashboard' }}
          </Link>
          <span>/</span>
          <span>{{ isKm ? 'អនុវត្ត & កិច្ចការ' : 'Practice & Assignments' }}</span>
          <span>/</span>
          <span class="text-cyan-600 dark:text-cyan-400 font-semibold">
            {{ isKm ? 'បន្ទប់អនុវត្ត (Practice Lab)' : 'Practice Lab' }}
          </span>
        </nav>

        <!-- Hero Header Card -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-indigo-800/60 rounded-3xl p-6 md:p-8 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-5 relative overflow-hidden">
          <div class="relative z-10 max-w-3xl space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-xs font-bold uppercase tracking-wider">
              <span>🧪</span>
              <span>{{ isKm ? 'ការពិសោធន៍ និងអនុវត្តផ្ទាល់តាមជំនាញ' : 'Dynamic Departmental Simulation' }}</span>
            </span>

            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-3">
              <span>{{ isKm ? 'បន្ទប់អនុវត្តជាក់ស្តែង ទាំង ៥ ជំនាញ' : 'Practice Lab (5 Majors)' }}</span>
            </h1>

            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-medium">
              {{ isKm 
                ? 'បរិស្ថានអនុវត្តជាក់ស្តែង Dynamic តាមជំនាញទាំង ៥ របស់វិទ្យាស្ថានសន្តប៉ូល (Saint Paul Institute)៖ កូដ & បច្ចេកវិទ្យា, បដិសណ្ឋារកិច្ច & ទេសចរណ៍, តែងសេចក្តីភាសាអង់គ្លេស, ដំណាំកសិកម្ម និងករណីសិក្សាការងារសង្គម។'
                : 'Hands-on dynamic practice environments across all 5 majors at Saint Paul Institute: Coding & Terminal, Tourism & Hospitality, English Writing, Crop & Soil Diagnostics, and Social Work Counseling.' }}
            </p>
          </div>

          <!-- Quick Navigation Link to Branching Simulations -->
          <div class="relative z-10 shrink-0 self-start md:self-center">
            <Link
              href="/student/simulations"
              class="px-4 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs border border-white/20 backdrop-blur-md shadow-lg transition-all flex items-center gap-2 cursor-pointer active:scale-95"
            >
              <span>🎮</span>
              <span>{{ isKm ? 'ទៅកាន់ស៊ីមុយឡាទ័រ (Branching)' : 'Go to Simulations' }}</span>
              <span>→</span>
            </Link>
          </div>

          <!-- Decorative glowing accent circles -->
          <div class="absolute -right-16 -top-16 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>
        </div>
      </div>

      <!-- ================= 2. FILTER & SEARCH BAR ================= -->
      <div class="bg-white dark:bg-[#1E293B] border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 shadow-sm dark:shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Major Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto custom-scrollbar pb-1 md:pb-0">
          <button
            @click="selectedMajor = 'all'"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
            :class="[
              selectedMajor === 'all'
                ? 'bg-cyan-600 text-white shadow-sm'
                : 'bg-slate-100 dark:bg-slate-900/60 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
            ]"
          >
            {{ isKm ? 'ជំនាញទាំងអស់ (5)' : 'All Majors (5)' }}
          </button>
          <button
            v-for="lab in majorLabs"
            :key="'tab-' + lab.id"
            @click="selectedMajor = lab.id"
            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer flex items-center gap-1.5"
            :class="[
              selectedMajor === lab.id
                ? 'bg-cyan-600 text-white shadow-sm'
                : 'bg-slate-100 dark:bg-slate-900/60 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
            ]"
          >
            <span>{{ lab.icon }}</span>
            <span>{{ isKm ? lab.majorKm : lab.major }}</span>
          </button>
        </div>

        <!-- Search Bar -->
        <div class="relative min-w-[200px]">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="isKm ? 'ស្វែងរកបន្ទប់អនុវត្ត ឬម៉ូឌុល...' : 'Search labs or modules...'"
            class="w-full pl-8 pr-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900/80 border border-slate-300 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 text-xs placeholder-slate-400 focus:ring-1 focus:ring-cyan-500 focus:outline-none"
          />
          <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white text-xs font-bold"
          >
            ✕
          </button>
        </div>
      </div>

      <!-- ================= 3. 5 MAJORS GRID ================= -->
      <div v-if="filteredLabs.length === 0" class="py-16 text-center space-y-3 bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800">
        <span class="text-3xl">🔍</span>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">
          {{ isKm ? 'រកមិនឃើញបន្ទប់អនុវត្តដែលត្រូវនឹងពាក្យស្វែងរកទេ' : 'No matching practice labs found' }}
        </h3>
        <p class="text-xs text-slate-400 max-w-sm mx-auto">
          {{ isKm ? 'សូមព្យាយាមស្វែងរកដោយប្រើពាក្យផ្សេង ឬសម្អាតពាក្យស្វែងរក។' : 'Try adjusting your search query or reset your filter to view all labs.' }}
        </p>
        <button
          @click="selectedMajor = 'all'; searchQuery = ''"
          class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold transition-all shadow-sm cursor-pointer"
        >
          {{ isKm ? 'បង្ហាញទាំងអស់' : 'Show All' }}
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
        <div
          v-for="lab in filteredLabs"
          :key="lab.id"
          class="bg-white dark:bg-[#1E293B] border border-slate-200/90 dark:border-slate-800/80 hover:border-cyan-500/50 dark:hover:border-cyan-500/50 rounded-3xl p-6 flex flex-col justify-between shadow-sm dark:shadow-xl transition-all hover:-translate-y-1 group"
        >
          <div class="space-y-4">
            
            <!-- Top Icon & Badge -->
            <div class="flex items-center justify-between">
              <span class="text-3xl p-3 bg-slate-50 dark:bg-slate-900/80 rounded-2xl border border-slate-200 dark:border-slate-700/80 group-hover:scale-110 transition-transform shadow-2xs">
                {{ lab.icon }}
              </span>
              <span
                class="px-2.5 py-1 rounded-xl text-[11px] font-bold border"
                :class="lab.accentColor"
              >
                {{ isKm ? lab.badgeKm : lab.badge }}
              </span>
            </div>

            <!-- Title & Descriptions -->
            <div>
              <p class="text-[11px] font-bold uppercase tracking-wider text-cyan-600 dark:text-cyan-400">
                {{ isKm ? lab.majorKm : lab.major }}
              </p>
              
              <h3 class="text-base font-bold text-slate-900 dark:text-white mt-1 group-hover:text-cyan-600 dark:group-hover:text-cyan-300 transition-colors">
                {{ isKm ? lab.titleKm : lab.title }}
              </h3>
              
              <p class="text-xs text-slate-400 dark:text-slate-500 font-medium mt-0.5">
                {{ isKm ? lab.title : lab.titleKm }}
              </p>
              
              <p class="text-xs text-slate-600 dark:text-slate-300 mt-2.5 leading-relaxed font-normal">
                {{ isKm ? lab.descKm : lab.desc }}
              </p>
            </div>

            <!-- Modules List -->
            <div class="space-y-2 pt-3.5 border-t border-slate-100 dark:border-slate-800/80 text-xs">
              <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                {{ isKm ? 'ម៉ូឌុលអនុវត្តជាក់ស្តែង៖' : 'Lab Modules:' }}
              </p>
              <ul class="space-y-1.5 text-slate-700 dark:text-slate-300 text-[11px]">
                <li
                  v-for="(t, idx) in (isKm ? lab.topicsKm : lab.topics)"
                  :key="idx"
                  class="flex items-center gap-2"
                >
                  <span class="text-cyan-500 shrink-0">▹</span>
                  <span class="truncate">{{ t }}</span>
                </li>
              </ul>
            </div>

          </div>

          <!-- Bottom Action Button -->
          <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80">
            <Link
              :href="lab.href"
              class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs shadow-md transition-all cursor-pointer active:scale-95"
              :class="lab.buttonClass"
            >
              <span>{{ isKm ? 'ចូលទៅកាន់បន្ទប់អនុវត្ត ' + lab.majorKm : 'Launch ' + lab.major + ' Lab' }}</span>
              <span>→</span>
            </Link>
          </div>

        </div>
      </div>

    </div>
  </StudentLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 5px;
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.2);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.4);
}
</style>
