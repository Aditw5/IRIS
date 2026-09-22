<script setup lang="ts">
import { onUpdated, ref, useSlots } from 'vue'

export type VCardActionRadius = 'regular' | 'smooth' | 'rounded'
export interface VCardActionProps {
  title: string
  subtitle?: string

  content?: string
  radius?: VCardActionRadius
}

const props = withDefaults(defineProps<VCardActionProps>(), {
  subtitle: undefined,

  content: undefined,
  radius: 'regular',
})

const slots = useSlots()
const hasDefaultSlot = ref(!!slots.default?.())

onUpdated(() => {
  hasDefaultSlot.value = !!slots.default?.()
})
</script>

<template>
  <div class="is-raised" :class="[
    props.radius === 'regular' && 's-card',
    props.radius === 'smooth' && 'r-card',
    props.radius === 'rounded' && 'l-card',
  ]">
    <div class="card-head">
      <VBlock :title="props.title" :subtitle="props.subtitle" center> <template #action>
          <slot name="action"></slot>
        </template>
      </VBlock>
    </div>
    <div v-if="hasDefaultSlot" class="card-inner">
      <slot></slot>
    </div>
  </div>
</template>
<style lang="scss">
.account-wrapper .account-box.is-navigation .media-flex-center .flex-meta span:first-child {
  font-size: 1rem;
}
</style>