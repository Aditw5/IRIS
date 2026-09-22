<template>
  <div class="report-route-redirect" aria-live="polite">
    Membuka laporan customer...
  </div>
</template>

<script setup lang="ts">
import { onBeforeMount } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

onBeforeMount(() => {
  router.replace({
    path: '/module/dashboard/customer',
    query: { section: 'laporan' },
  })
})
</script>

<style scoped>
.report-route-redirect {
  padding: 24px;
  color: #64748b;
  font-family: var(--font);
  font-size: 13px;
}
</style>
