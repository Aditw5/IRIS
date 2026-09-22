
<route lang="yaml">
meta:
  requiresAuth: false
</route>
<script setup lang="ts">
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import Navigasi from './navigasi.vue';
// import Registrasi from './module/dashboard/registrasi.vue';
import { ref, onMounted, defineAsyncComponent } from 'vue';
import { useUserSession } from '/@src/stores/userSession'
const user = useUserSession().getUser()
let rep = user.kelompokUser.menu != null ? user.kelompokUser.menu.replace('module-dashboard-', '') : `registrasi`;

// Dynamically import the Registrasi component
const AsyncRegistrasi = defineAsyncComponent(() =>
  import(`./module/dashboard/${rep}.vue`)
);


const viewWrapper = useViewWrapper()
viewWrapper.setPageTitle('Dashboard')
const PROJECT = import.meta.env.VITE_PROJECT

useHead({
  title: PROJECT + ' - V1',
})
</script>

<template>
  <Navigasi>
    <div class="page-content-inner">
      <!-- <LifestyleDashboardV3 /> -->
      <AsyncRegistrasi />
    </div>
  </Navigasi>
</template>

