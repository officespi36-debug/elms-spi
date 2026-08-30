<script setup lang="ts">
import BrowseIndex from '@/Pages/Student/Browse/Index.vue'

defineProps<{
  courses?: any
  filters?: any
}>()
</script>

<template>
  <BrowseIndex :courses="courses" :filters="filters" />
</template>
