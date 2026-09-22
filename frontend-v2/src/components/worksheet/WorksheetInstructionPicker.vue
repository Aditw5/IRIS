<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import AutoComplete from 'primevue/autocomplete'
import { useWorksheetInstructions } from '/@src/composable/useWorksheetInstructions'

const props = defineProps<{ modelValue?: any }>()
const emit = defineEmits(['update:modelValue'])
const { options, loading, error, refresh } = useWorksheetInstructions()
const query = ref('')
const inputValue = ref(props.modelValue)
watch(() => props.modelValue, value => { inputValue.value = value })
const suggestions = computed(() => {
  const keyword = query.value.trim().toLocaleLowerCase()
  return options.value
    .filter(option => option.active && `${option.label} ${option.number}`.toLocaleLowerCase().includes(keyword))
    .map(option => ({ ...option, disabled: !!error.value || option.disabled }))
})
const selected = computed(() => options.value.find(option => String(option.value) === String(props.modelValue?.value)))
const warning = computed(() => {
  if (error.value) return error.value
  if (!props.modelValue?.value || loading.value) return ''
  if (!selected.value) return 'IK ini tidak tersedia. Pilih kembali IK yang aktif dan memiliki file.'
  return selected.value.disabled ? `${selected.value.reason}. Referensi IK lama yang sudah tersimpan tetap dipertahankan; pilihan baru wajib memiliki file.` : ''
})

function search(event: { query: string }) {
  query.value = event.query || ''
}

async function openDropdown() {
  // Recheck the backend every time the user opens the choices. This makes an
  // IK available immediately after its upload modal is closed.
  await refresh(true)
}

function update(value: any) {
  if (value && typeof value === 'object') {
    const current = options.value.find(option => String(option.value) === String(value.value))
    if (!current || current.disabled || error.value) {
      inputValue.value = props.modelValue
      return
    }
    inputValue.value = current
    emit('update:modelValue', current)
  } else {
    inputValue.value = value
    // Searching (including clearing the search text) must not erase a saved
    // certificate reference. Removing an IK is done with the row's delete button.
  }
}

function finishSearch() {
  inputValue.value = props.modelValue
}
</script>

<template>
  <div class="worksheet-ik-picker">
    <AutoComplete :modelValue="inputValue" @update:modelValue="update" :suggestions="suggestions" @blur="finishSearch"
      @dropdown-click="openDropdown"
      @complete="search" optionLabel="label" optionDisabled="disabled" :dropdown="true" :minLength="0"
      class="is-input" appendTo="body" placeholder="Cari nama atau nomor IK..."
      :loading="loading" aria-label="Pilih Instruksi Kerja yang memiliki file">
      <template #option="{ option }">
        <div class="worksheet-ik-option">
          <div class="worksheet-ik-number"><strong>{{ option.number }}</strong></div>
          <div class="worksheet-ik-name">{{ option.label }}</div>
          <div v-if="option.disabled && option.reason" class="worksheet-ik-unavailable">
            {{ option.reason }}
          </div>
        </div>
      </template>
      <template #empty>Tidak ada IK sesuai pencarian.</template>
    </AutoComplete>
    <small v-if="warning" class="ik-warning" role="alert">{{ warning }}</small>
  </div>
</template>

<style scoped>
.worksheet-ik-picker { min-width: 0; width: 100%; text-align: left; }
.worksheet-ik-picker :deep(.p-autocomplete) { display: flex; width: 100%; }
.worksheet-ik-picker :deep(.p-autocomplete-input) { width: 100%; min-width: 0; }
.worksheet-ik-option { display: block; max-width: min(640px, 75vw); white-space: normal; overflow-wrap: anywhere; }
.worksheet-ik-number, .worksheet-ik-name, .worksheet-ik-unavailable { display: block; }
.worksheet-ik-name { margin-top: 4px; }
.worksheet-ik-unavailable { margin-top: 10px; color: #aa4c16; font-size: 12px; line-height: 1.5; }
.ik-warning { color: #aa4c16; }
.ik-warning { display: block; margin-top: 6px; line-height: 1.5; font-size: 12px; font-weight: normal; }
</style>
