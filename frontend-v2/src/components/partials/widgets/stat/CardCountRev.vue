<script setup lang="ts">
const props = defineProps<{
  icon: string
  total: number
  label: string
  straight?: boolean
  squared?: boolean
  colored?: boolean
}>()
</script>
<template>
  <div class="tile-grid tile-grid-v2">
    <div class="tile-grid-item classss">
      <div class="tile-grid-item-inner">
        <VAvatar size="small" :picture="props.icon" color="primary" bordered />
        <div class="meta">
          <span class="dark-inverted span-csss">{{ props.total }}</span>
          <span class="span-css "> {{ props.label }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
<style lang="scss">
.span-css {
  font-size: .85rem;
  color: var(--light-text);
  font-family: var(--font);

  // margin-bottom: 16px;
  width: 80px;
  white-space: nowrap;
  overflow: hidden !important;
  text-overflow: ellipsis;
}

.span-csss {
  font-size: 1.15rem !important;
  font-weight: 600;
}

.tile-grid-v2 .tile-grid-item.classss {
  padding: 10px;
  background-color: #F5F6FA;
}
</style>
