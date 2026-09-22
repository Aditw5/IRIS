<script setup lang="ts">
import { computed, reactive } from 'vue'
import { useVFieldContext } from '/@src/composable/useVFieldContext'

export interface VLabelLargeProps {
    raw?: boolean
}

const props = defineProps<VLabelLargeProps>()

const vFieldContext = reactive(
    useVFieldContext({
        create: false,
        help: 'VLabelLarge',
    })
)

const classes = computed(() => {
    if (props.raw) return []

    return ['label label-large']
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
    .label.label-large {
        font-family: var(--font-alt);
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--white);
        margin-bottom: 4px;
        width: 135px;
        white-space: nowrap;
        overflow: hidden !important;
        text-overflow: ellipsis;
    }
    
    .label.label-large.block-text {
        font-size: 0.9rem;
        color: var(--white);
        margin-bottom: 16px;
        width: 130px;
        white-space: nowrap;
        overflow: hidden !important;
        text-overflow: ellipsis;
        font-weight: normal;
    }
    
    .is-dark {
        .label.label-large {
            font-family: var(--font-alt);
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--white);
            margin-bottom: 4px;
        }
    
        .label.label-large.block-text {
            font-size: 0.9rem;
            color: var(--white);
            margin-bottom: 16px;
            white-space: nowrap;
            overflow: hidden !important;
            text-overflow: ellipsis;
            font-weight: normal;
        }
    }
    </style>