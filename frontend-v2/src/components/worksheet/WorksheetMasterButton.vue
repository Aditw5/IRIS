<script setup lang="ts">
import { computed, defineAsyncComponent, ref } from 'vue'
import Dialog from 'primevue/dialog'
import { notifyWorksheetInstructionsChanged } from '/@src/composable/useWorksheetInstructions'

const props = defineProps<{ kind: 'ik' | 'standar' }>()
const visible = ref(false)
const label = computed(() => props.kind === 'ik' ? 'Tambah / Update IK' : 'Tambah / Update Standar')
const InstructionMaster = defineAsyncComponent(() => import('/@src/pages/module/sysadmin/master-instruksi-kerja.vue'))
const StandardMaster = defineAsyncComponent(() => import('/@src/pages/module/sysadmin/master-alat-standar.vue'))
function closed() {
  if (props.kind === 'ik') notifyWorksheetInstructionsChanged()
}
</script>

<template>
  <div class="worksheet-master-action">
    <VButton type="button" color="primary" outlined icon="feather:edit" @click="visible = true">
      {{ label }}
    </VButton>
    <Dialog v-model:visible="visible" modal :header="label" :style="{ width: '90vw' }" @hide="closed">
      <InstructionMaster v-if="visible && kind === 'ik'" :hide="true" />
      <StandardMaster v-if="visible && kind === 'standar'" :hide="true" />
    </Dialog>
  </div>
</template>

<style scoped>
.worksheet-master-action { display: flex; justify-content: center; margin-top: 10px; }
.worksheet-master-action :deep(.button) { height: auto; min-height: 38px; max-width: 100%; white-space: normal; padding: 8px 12px; text-align: center; line-height: 1.4; }
</style>
