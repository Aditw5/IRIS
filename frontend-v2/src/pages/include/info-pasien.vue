<template>
    <VCard>
     
    </VCard>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        isaktif?: boolean
        pasien?: any
    }>(),
    {
        pasien: {},
        isaktif: true
    }
)
</script>

<style lang="scss">

</style>