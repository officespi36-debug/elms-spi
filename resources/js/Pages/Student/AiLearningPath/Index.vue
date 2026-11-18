<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import RecommendedLesson from './RecommendedLesson.vue'
import ReviewLesson from './ReviewLesson.vue'
import WeakTopics from './WeakTopics.vue'
import NextModule from './NextModule.vue'

const props = defineProps<{
  activeTab?: string
}>()

const currentTab = ref(props.activeTab || 'recommended')

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search)
  const tabParam = urlParams.get('tab')
  if (tabParam && ['recommended', 'review', 'weak-topics', 'next-module'].includes(tabParam)) {
    currentTab.value = tabParam
  }
})

watch(() => props.activeTab, (newTab) => {
  if (newTab) currentTab.value = newTab
})
</script>

<template>
  <div>
    <RecommendedLesson v-if="currentTab === 'recommended'" v-bind="$page.props" />
    <ReviewLesson v-else-if="currentTab === 'review'" v-bind="$page.props" />
    <WeakTopics v-else-if="currentTab === 'weak-topics'" v-bind="$page.props" />
    <NextModule v-else-if="currentTab === 'next-module'" v-bind="$page.props" />
    <RecommendedLesson v-else v-bind="$page.props" />
  </div>
</template>
