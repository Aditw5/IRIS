<route lang="yaml">
meta:
  requiresAuth: true
</route>
<script setup lang="ts">
import { useRoute } from 'vue-router'
import { ref } from 'vue'
import TransLoaderader from './module/TransLoader.vue'
import { useLayoutSwitcher } from '/@src/stores/layoutSwitcher'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import Navigasi from './navigasi.vue'
const route = useRoute()
const isLoaderActive = ref(false)
const layoutSwitcher = useLayoutSwitcher()
const copyRight = import.meta.env.VITE_FOOTER as string
const copyRight_ = import.meta.env.VITE_FOOTER_BY as string
// console.clear()
</script>
<template>
  <Navigasi>
    <RouterView v-slot="{ Component, route }">
      <Transition name="slide-x">
      <!-- <Transition name="fade-slow"  mode="out-in"> -->
        <!-- <Transition name="fade-slow" mode="out-in"> -->
        <div v-if="route.query.isfull" :key="route.name" > <!-- INI BIKIN RELOAD PAGE KETIKA ADA ROUTER VIEW -->
        <component :is="Component" />
        <!-- <div class="center-foot"><span class="footer-text-1">{{ copyRight }}</span> <span  class="footer-text-2 ml-1"> {{ copyRight_ }}</span></div> -->
        </div>
        <component :is="Component"  v-else/>
      </Transition>
    </RouterView>
  </Navigasi>
</template>
