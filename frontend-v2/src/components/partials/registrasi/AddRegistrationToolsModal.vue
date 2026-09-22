<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useApi } from '/@src/composable/useApi'
import * as H from '/@src/utils/appHelper'

type AlatTersedia = {
  id: string | number
  namaproduk?: string
  namamerk?: string
  namatipe?: string
  namaserialnumber?: string
}

type RegistrasiInfo = {
  nopendaftaran?: string
  jenisorder?: string
  namaperusahaan?: string
}

const props = defineProps<{
  open: boolean
  registrationNorec: string
  registrationNumber?: string
  unitName?: string
}>()

const emit = defineEmits<{
  (event: 'close'): void
  (event: 'saved'): void
}>()

const search = ref('')
const alatTersedia = ref<AlatTersedia[]>([])
const selectedAlat = ref<Record<string, boolean>>({})
const registrationInfo = ref<RegistrasiInfo>({})
const isLoading = ref(false)
const isSaving = ref(false)

const selectedIds = computed(() => {
  return Object.entries(selectedAlat.value)
    .filter(([, selected]) => selected)
    .map(([id]) => id)
})

const displayRegistrationNumber = computed(() => {
  return registrationInfo.value.nopendaftaran || props.registrationNumber || '-'
})

const displayUnitName = computed(() => {
  return registrationInfo.value.namaperusahaan || props.unitName || '-'
})

const getErrorMessage = (error: any, fallback: string) => {
  return (typeof error === 'string' ? error : '') ||
    error?.error ||
    error?.message ||
    error?.response?.data?.message ||
    error?.response?.data?.error ||
    (typeof error?.response?.data?.data === 'string' ? error.response.data.data : '') ||
    fallback
}

const fetchAvailableTools = async () => {
  if (!props.registrationNorec) return

  isLoading.value = true
  try {
    const response: any = await useApi().get(
      `/registrasi/alat-tersedia-kaji-ulang?norec_registrasi=${encodeURIComponent(props.registrationNorec)}&query=${encodeURIComponent(search.value.trim())}`
    )

    alatTersedia.value = response.data || []
    registrationInfo.value = response.registrasi || {}
  } catch (error: any) {
    alatTersedia.value = []
    H.alert('error', getErrorMessage(error, 'Gagal memuat alat yang tersedia.'))
  } finally {
    isLoading.value = false
  }
}

const closeModal = () => {
  if (isSaving.value) return
  emit('close')
}

const saveTools = async () => {
  if (selectedIds.value.length === 0) {
    H.alert('warning', 'Pilih minimal satu alat yang akan ditambahkan.')
    return
  }

  isSaving.value = true
  try {
    await useApi().post('/registrasi/tambah-alat-kaji-ulang', {
      norec_registrasi: props.registrationNorec,
      alat_ids: selectedIds.value,
    })

    emit('saved')
  } catch (error: any) {
    H.alert('error', getErrorMessage(error, 'Gagal menambahkan alat ke pendaftaran.'))
    await fetchAvailableTools()
  } finally {
    isSaving.value = false
  }
}

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) return

    search.value = ''
    selectedAlat.value = {}
    registrationInfo.value = {}
    fetchAvailableTools()
  }
)
</script>

<template>
  <VModal :open="open" title="Tambahkan Alat ke Pendaftaran" size="large" actions="right"
    :noclose="isSaving" cancel-label="Tutup" @close="closeModal">
    <template #content>
      <div class="registration-context mb-5">
        <div>
          <span class="context-label">No. Pendaftaran</span>
          <strong>{{ displayRegistrationNumber }}</strong>
        </div>
        <div>
          <span class="context-label">Unit Pemilik Alat</span>
          <strong>{{ displayUnitName }}</strong>
        </div>
      </div>

      <VMessage color="info" class="mb-4">
        Hanya alat milik unit ini yang belum ada di pendaftaran dan tidak sedang dikerjakan pada order kalibrasi
        maupun repair yang dapat dipilih.
      </VMessage>

      <div class="columns is-multiline mb-1">
        <div class="column is-9">
          <VField>
            <VControl icon="feather:search">
              <input v-model="search" type="text" class="input is-rounded"
                placeholder="Cari nama alat, merk, tipe, atau serial number" @keyup.enter="fetchAvailableTools" />
            </VControl>
          </VField>
        </div>
        <div class="column is-3">
          <VButton type="button" color="info" outlined class="is-fullwidth" :loading="isLoading"
            @click="fetchAvailableTools">
            Cari Alat
          </VButton>
        </div>
      </div>

      <div class="selection-summary mb-3">
        <span>{{ alatTersedia.length }} alat tersedia</span>
        <strong>{{ selectedIds.length }} alat dipilih</strong>
      </div>

      <div v-if="isLoading" class="has-text-centered py-6">
        <VPlaceloadText :lines="4" centered last-line-width="50%" />
      </div>

      <div v-else-if="alatTersedia.length === 0" class="empty-tools py-6">
        <i class="iconify" data-icon="feather:inbox" aria-hidden="true"></i>
        <strong>Tidak ada alat yang dapat ditambahkan</strong>
        <span>Coba kata pencarian lain atau pastikan pekerjaan alat sebelumnya sudah disetujui manager.</span>
      </div>

      <div v-else class="available-tools">
        <div v-for="alat in alatTersedia" :key="alat.id" class="tool-option"
          :class="{ 'is-selected': selectedAlat[String(alat.id)] }">
          <VCheckbox v-model="selectedAlat[String(alat.id)]" color="info" circle>
            <div class="tool-copy">
              <strong>{{ alat.namaproduk || '-' }}</strong>
              <span>{{ alat.namamerk || '-' }} / {{ alat.namatipe || '-' }}</span>
              <span>S/N: {{ alat.namaserialnumber || '-' }}</span>
            </div>
          </VCheckbox>
        </div>
      </div>
    </template>

    <template #action>
      <VButton type="button" color="primary" icon="feather:plus-circle" :loading="isSaving"
        :disabled="selectedIds.length === 0" @click="saveTools">
        Tambahkan {{ selectedIds.length }} Alat
      </VButton>
    </template>
  </VModal>
</template>

<style scoped lang="scss">
.registration-context {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  padding: 1rem;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: var(--radius-large);
  background: var(--fade-grey-light-6);

  > div {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
  }

  .context-label {
    color: var(--light-text);
    font-size: 0.8rem;
  }
}

.selection-summary {
  display: flex;
  justify-content: space-between;
  color: var(--light-text);
  font-size: 0.85rem;

  strong {
    color: var(--primary);
  }
}

.available-tools {
  max-height: 420px;
  overflow-y: auto;
  padding-right: 0.35rem;
}

.tool-option {
  margin-bottom: 0.65rem;
  padding: 0.9rem 1rem;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: var(--radius-large);
  transition: border-color 0.2s ease, background-color 0.2s ease;

  &.is-selected {
    border-color: var(--primary);
    background: var(--primary-light-48);
  }

  :deep(.checkbox) {
    display: flex;
    width: 100%;
    align-items: flex-start;
    padding: 0;
  }
}

.tool-copy {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 0.15rem;

  strong {
    color: var(--dark-text);
  }

  span {
    color: var(--light-text);
    font-size: 0.82rem;
  }
}

.empty-tools {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.45rem;
  color: var(--light-text);
  text-align: center;

  i {
    font-size: 2.5rem;
  }
}

@media only screen and (max-width: 767px) {
  .registration-context {
    grid-template-columns: 1fr;
  }
}

:global(.is-dark) {
  .registration-context,
  .tool-option {
    border-color: var(--dark-sidebar-light-12);
    background: var(--dark-sidebar-light-4);
  }

  .tool-option.is-selected {
    border-color: var(--primary);
  }

  .tool-copy strong {
    color: var(--dark-dark-text);
  }
}
</style>
