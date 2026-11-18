<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import MyCertificates from './MyCertificates.vue'
import DownloadShare from './DownloadShare.vue'
import VerifyCertificate from './VerifyCertificate.vue'

const props = defineProps<{
  activeTab?: string
  myCertificatesData?: any
  availableCertificatesData?: any
  verificationData?: any
  filters?: {
    status: string
    category: string
    course: string
    issuer: string
    search: string
    sort: string
  }
  availableFilters?: {
    status: string
    category: string
    course: string
    level: string
    sort: string
    search: string
    page: number
  }
  initialQuery?: string
  initialVerifyTab?: string
}>()

const normalizeTab = (tab?: string | null) => {
  if (!tab) return 'my-certificates'
  if (tab === 'my-certs') return 'my-certificates'
  if (tab === 'download') return 'download-share'
  return tab
}

const currentTab = ref(normalizeTab(props.activeTab))

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search)
  const tabParam = urlParams.get('tab')
  if (tabParam && ['my-certificates', 'my-certs', 'download', 'download-share', 'verify'].includes(tabParam)) {
    currentTab.value = normalizeTab(tabParam)
  }
})

watch(() => props.activeTab, (newTab) => {
  if (newTab) {
    currentTab.value = normalizeTab(newTab)
  }
})
</script>

<template>
  <div>
    <MyCertificates
      v-if="currentTab === 'my-certificates'"
      :analytics="myCertificatesData"
      :filters="filters"
    />
    <DownloadShare
      v-else-if="currentTab === 'download-share'"
      :analytics="availableCertificatesData"
      :filters="availableFilters"
    />
    <VerifyCertificate
      v-else-if="currentTab === 'verify'"
      :verificationData="verificationData"
      :initialQuery="initialQuery"
      :initialTab="initialVerifyTab"
    />
    <MyCertificates
      v-else
      :analytics="myCertificatesData"
      :filters="filters"
    />
  </div>
</template>
