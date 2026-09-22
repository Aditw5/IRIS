<!-- <script setup lang="ts">
import { watch, ref, reactive, computed } from 'vue'
import { useVFieldContext } from '/@src/composable/useVFieldContext'
import { useCurrencyInput } from "vue-currency-input";
export interface VCurrencyEmits {
    (event: 'update:modelValue', value?: any): void
}
export interface VCurrencyProps {
    raw?: boolean
    modelValue?: any
    trueValue?: boolean
    falseValue?: boolean
}

const vFieldContext = reactive(
    useVFieldContext({
        create: false,
        help: 'VCurrency',
    })
)
const emits = defineEmits<VCurrencyEmits>()
const props = withDefaults(
    defineProps<VCurrencyProps>(),
    {
        modelValue: '',
        trueValue: true,
        falseValue: false,
    }
)
const value = ref(vFieldContext.field?.value ?? props.modelValue)

watch(value, () => {
    emits('update:modelValue', value.value)
})
watch(
    () => props.modelValue,
    () => {
        value.value = props.modelValue
    }
)

const classes = computed(() => {
    if (props.raw) return []
    return ['input']
})
</script>

<template>
    <input :id="vFieldContext.id" v-model="value" :class="classes" :name="vFieldContext.id"
        :true-value="props.trueValue" :false-value="props.falseValue" @change="vFieldContext.field?.handleChange"
        @blur="vFieldContext.field?.handleBlur" />
</template> -->


<template>
    <input ref="inputRef" type="text" class="input" />
</template>

<script lang="ts">
import { useCurrencyInput } from "vue-currency-input";

export default {
    name: "VCurrency",
    props: {
        modelValue: Number,
        options: Object,
    },
    setup(props: any) {
        const { inputRef } = useCurrencyInput(props.options);

        return { inputRef };
    },
};
</script>

