<script setup lang="ts">
import { computed } from 'vue'

export type VCardCustomRadius = 'regular' | 'smooth' | 'rounded'
export type VCardCustomColor =
  | 'primary'
  | 'secondary'
  | 'info'
  | 'success'
  | 'warning'
  | 'danger'

export type VCardCustomCustom = 'card-green'
export interface VCardCustomProps {
  radius?: VCardCustomRadius
  color?: VCardCustomColor,
  custom?: VCardCustomCustom,
  elevated?: boolean,
  style?: string
}

const props = withDefaults(defineProps<VCardCustomProps>(), {
  radius: undefined,
  color: undefined,
  custom: undefined,
  elevated: false,
  style: undefined,
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
  <div :class="[cardRadius, elevated && 'is-raised', props.color && `is-${props.color}`, cardClass]" :style="props.style">
    <slot></slot>
  </div>
</template>

