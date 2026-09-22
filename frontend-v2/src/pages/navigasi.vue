
<route lang="yaml">
meta:
  requiresAuth: true
</route>
<script lang="ts">
import { computed, ref } from 'vue';
import { useLayoutSwitcher } from '/@src/stores/layoutSwitcher'

// const settingNAV: any = computed(() => {
//    return import.meta.env.VITE_NAVIGASI
// })

export default {
    setup() {

        const settingNAV = ref('')
        //Whatever code
      
        settingNAV.value = import.meta.env.VITE_NAVIGASI

        return { settingNAV}

    }
}
</script>
<template>
    <NavbarLayout v-if="settingNAV == 'navbar'">
        <slot></slot>
    </NavbarLayout>
    <SidebarLayout v-else-if="settingNAV == 'sidebar'" >
        <slot></slot>
    </SidebarLayout>
</template>
    
    