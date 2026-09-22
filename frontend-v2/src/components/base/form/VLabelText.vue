<script setup lang="ts">
import { computed, reactive } from 'vue'
import { useVFieldContext } from '/@src/composable/useVFieldContext'

export interface VLabelTextProps {
    raw?: boolean
}

const props = defineProps<VLabelTextProps>()

const vFieldContext = reactive(
    useVFieldContext({
        create: false,
        help: 'VLabelText',
    })
)

const classes = computed(() => {
    if (props.raw) return []

    return ['label label-small']
})

const onEnter = () => {
    if (vFieldContext.id) {
        document.getElementById(vFieldContext.id)?.click()
    }
}
</script>

<template>
    <label :class="classes" :for="vFieldContext.id" @keydown.enter.prevent="onEnter">
        <slot v-bind="vFieldContext" />
    </label>
</template>


<style lang="scss">
.label.label-small {
    font-weight: 300;
    font-size: 0.85rem;
}

.is-dark {
    .label.label-small {
        font-weight: 300;
        font-size: 0.85rem;
    }
}
</style>