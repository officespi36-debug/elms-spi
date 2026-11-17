<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/StudentLayout.vue'
import { useLanguage } from '@/Services/i18n'

const props = defineProps<{
  course: any,
  enrolled: any,
  teacherName?: string | null,
  teacherNameKh?: string | null,
}>()

const { t, currentLang } = useLanguage()

const formatCurrency = (value: number) => {
  if (value === 0 || value === null) return t('ឥតគិតថ្លៃ', 'Free');
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
}

const handleEnroll = () => {
  if (props.course.price > 0) {
    // Redirect to payment upload page for paid courses
    router.get(route('student.payments.create', props.course.id))
  } else {
    // Directly enroll for free courses
    router.post(route('student.enroll', props.course.id))
  }
}
</script>

<template>
  <StudentLayout :title="currentLang === 'km' ? (course.title_kh || course.title) : course.title">
    <div class="max-w-5xl mx-auto p-6 font-['Inter','Kantumruy_Pro',sans-serif]">
      
      <div class="bg-white dark:bg-[#1e1f20] rounded-2xl shadow-sm overflow-hidden flex flex-col md:flex-row mb-8 border border-slate-200 dark:border-slate-800">
        <!-- Thumbnail -->
        <div class="md:w-1/3 bg-slate-100 dark:bg-slate-900 relative min-h-[250px]">
          <img v-if="course.thumbnail" :src="`/storage/${course.thumbnail}`" class="absolute inset-0 w-full h-full object-cover" />
          <div v-else class="absolute inset-0 flex items-center justify-center text-slate-400">No Image</div>
        </div>
        
        <!-- Details -->
        <div class="p-8 md:w-2/3 flex flex-col justify-center">
          <div class="text-sm font-bold text-purple-600 dark:text-purple-400 mb-2 uppercase tracking-wide">
            {{ (currentLang === 'km' ? (course.major?.name_kh || course.major?.name) : course.major?.name) || (currentLang === 'km' ? 'ការសិក្សាទូទៅ' : 'General Course') }}
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold mb-4 text-slate-900 dark:text-white">{{ currentLang === 'km' ? (course.title_kh || course.title) : course.title }}</h1>
          <p class="text-slate-600 dark:text-slate-400 mb-6 line-clamp-3 leading-relaxed">
            {{ (currentLang === 'km' ? (course.description_kh || course.description) : course.description) || (currentLang === 'km' ? 'មិនមានការពិពណ៌នា' : 'No description provided.') }}
          </p>
          
          <div class="flex items-center justify-between mt-auto gap-4 flex-wrap">
            <div class="flex items-center gap-3">
              <img :src="course.teacher?.profile_photo_url || `https://ui-avatars.com/api/?name=${course.teacher?.name}`" class="w-10 h-10 rounded-full object-cover border border-purple-200 dark:border-purple-800" />
              <div>
                <div class="text-xs text-slate-500">{{ t('គ្រូបង្រៀន', 'Instructor') }}</div>
                <div class="font-bold text-slate-900 dark:text-white">{{ currentLang === 'km' ? (teacherNameKh || course.teacher?.name_kh || teacherName || course.teacher?.name) : (teacherName || course.teacher?.name || 'Unknown') }}</div>
              </div>
            </div>
            
            <div class="text-right">
              <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mb-2">{{ formatCurrency(course.price) }}</div>
              
              <template v-if="enrolled">
                <button v-if="enrolled.status === 'active'" @click="router.get(route('student.learn', course.id))" class="bg-purple-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-purple-500 transition-colors w-full cursor-pointer">
                  {{ t('ចូលរៀន', 'Go to Course') }}
                </button>
                <button v-else-if="enrolled.status === 'pending_payment'" disabled class="bg-amber-600 text-white px-6 py-2.5 rounded-xl font-bold opacity-80 cursor-not-allowed w-full">
                  {{ t('កំពុងផ្ទៀងផ្ទាត់ការបង់ប្រាក់...', 'Verifying Payment...') }}
                </button>
              </template>
              <template v-else>
                <button @click="handleEnroll" class="bg-purple-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-purple-500 transition-colors w-full cursor-pointer">
                  {{ t('ចុះឈ្មោះឥឡូវ', 'Enroll Now') }}
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Curriculum -->
      <div class="bg-white dark:bg-[#1e1f20] rounded-2xl shadow-sm p-6 border border-slate-200 dark:border-slate-800">
        <h2 class="text-xl sm:text-2xl font-bold mb-6 text-slate-900 dark:text-white">{{ t('មាតិកាវគ្គសិក្សា', 'Course Curriculum') }}</h2>
        
        <div v-if="!course.modules || course.modules.length === 0" class="text-slate-500 italic">
          {{ t('មិនទាន់មានមាតិកានៅឡើយទេ', 'Curriculum is currently empty.') }}
        </div>
        
        <div v-else class="space-y-4">
          <div v-for="mod in course.modules" :key="mod.id" class="border border-slate-200 dark:border-slate-700/80 rounded-xl overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-900 p-4 font-bold border-b border-slate-200 dark:border-slate-700/80 flex justify-between text-slate-900 dark:text-white">
              <span>{{ mod.title }}</span>
              <span class="text-sm text-slate-500 font-normal">{{ mod.lessons?.length || 0 }} {{ t('មេរៀន', 'lessons') }}</span>
            </div>
            <div class="p-0">
              <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                <li v-for="lesson in mod.lessons" :key="lesson.id" class="p-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                  <div class="flex items-center gap-3">
                    <i class="pi" :class="{
                      'pi-video text-blue-500': lesson.type === 'video',
                      'pi-file-pdf text-red-500': lesson.type === 'pdf',
                      'pi-file text-slate-500': lesson.type === 'reading'
                    }"></i>
                    <span class="text-slate-800 dark:text-slate-200">{{ lesson.title }}</span>
                  </div>
                  <span v-if="lesson.is_free_preview" class="text-xs bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-2 py-0.5 rounded font-bold">{{ t('មើលសាកល្បង', 'Preview') }}</span>
                </li>
                <li v-if="!mod.lessons || mod.lessons.length === 0" class="p-4 text-slate-500 text-sm">{{ t('មិនទាន់មានមេរៀននៅឡើយទេ', 'No lessons yet.') }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
      
    </div>
  </StudentLayout>
</template>
