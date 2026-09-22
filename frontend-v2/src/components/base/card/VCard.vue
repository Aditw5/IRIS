<script setup lang="ts">
import { computed } from 'vue'

export type VCardRadius = 'regular' | 'smooth' | 'rounded'
export type VCardColor =
  | 'primary'
  | 'secondary'
  | 'info'
  | 'success'
  | 'warning'
  | 'danger'

export type VCardCustom = 'card-green' | 'card-grey'
export interface VCardProps {
  radius?: VCardRadius
  color?: VCardColor,
  custom?: VCardCustom,
  elevated?: boolean
}

const props = withDefaults(defineProps<VCardProps>(), {
  radius: undefined,
  color: undefined,
  custom: undefined,
  elevated: false,
})

const cardRadius = computed(() => {
  if (props.radius === 'smooth') {
    return 's-card'
  } else if (props.radius === 'rounded') {
    return 'l-card'
  }

  return 'r-card'
})
const cardClass = computed(() => {
  if (props.custom === 'card-green') {
    return 'card-green'
  } else {
    return props.custom
  }

  return 'card-green'
})
</script>

<template>
  <div :class="[cardRadius, elevated && 'is-raised', props.color && `is-${props.color}`, cardClass]">
    <slot></slot>
  </div>
</template>

