<template>
  <VCard>
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Master User &amp; Unit</h3>
    </div>

    <div class="user-grid-toolbar">
      <VControl icon="feather:search">
        <input v-model="filters" class="input custom-text-filter" placeholder="Cari Nama / Email / No WA / Unit..." />
      </VControl>
      <div class="buttons">
        <VField v-slot="{ id }" class="is-icon-select">
          <VControl>
            <Multiselect v-model="statusVerifikasi" :attrs="{ id }" placeholder="Status Verifikasi" label="name"
              :options="d_statusVerifikasi" :searchable="false" track-by="name" mode="single" @select="fetchData"
              autocomplete="off" />
          </VControl>
        </VField>

        <VControl class="is-pulled-right">
          <VSwitchBlock v-model="item.aktif" label="Aktif" color="danger" @change="fetchData" />
        </VControl>
      </div>
    </div>

    <div class="user-grid user-grid-v2">
      <DataTable :value="dataSourcefiltered" class="p-datatable-sm" :paginator="true" :rows="10"
        :rowsPerPageOptions="[10, 25, 50]" :loading="isLoading"
        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">
        <Column field="no" header="No"></Column>
        <Column field="name" header="Nama" :sortable="true"></Column>
        <Column field="email" header="Email" :sortable="true"></Column>
        <Column field="nowa" header="No WhatsApp"></Column>
        <Column field="jabatan" header="Jabatan"></Column>
        <Column field="unit" header="Unit" :sortable="true">
          <template #body="slotProps">
            {{ slotProps.data.unit || '-' }}
          </template>
        </Column>
        <Column header="Jenis">
          <template #body="slotProps">
            <VTag :color="slotProps.data.iseksternal ? 'warning' : 'info'"
              :label="slotProps.data.iseksternal ? 'Eksternal' : 'Internal'" />
          </template>
        </Column>
        <Column header="Verifikasi">
          <template #body="slotProps">
            <VTag :color="slotProps.data.status_verifikasi_c" :label="slotProps.data.status_verifikasi" />
          </template>
        </Column>
        <Column header="Status">
          <template #body="slotProps">
            <VTag :color="slotProps.data.status_c" :label="slotProps.data.status" />
          </template>
        </Column>
        <Column field="created_at" header="Tanggal Daftar">
          <template #body="slotProps">
            {{ slotProps.data.created_at ? H.formatDateIndoSimple(slotProps.data.created_at) : '-' }}
          </template>
        </Column>
        <Column :exportable="false" header="Aksi">
          <template #body="slotProps">
            <VIconButton v-if="!slotProps.data.email_verified_at" type="button" icon="feather:check-circle"
              class="mr-2" color="success" circle outlined raised v-tooltip.top="'Verifikasi Manual'"
              @click="toggleVerify(slotProps.data, true)" />
            <VIconButton v-else type="button" icon="feather:x-circle" class="mr-2" color="warning" circle outlined
              raised v-tooltip.top="'Batalkan Verifikasi'" @click="toggleVerify(slotProps.data, false)" />
            <VIconButton type="button" icon="feather:repeat" color="info" circle outlined raised
              v-tooltip.top="'Ubah Unit'" @click="openUbahUnit(slotProps.data)" />
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="modalUnit" modal header="Ubah Unit User" :style="{ width: '30vw' }">
      <div class="columns is-multiline" v-if="selectedUser">
        <div class="column is-12">
          <p><b>Nama:</b> {{ selectedUser.name }}</p>
          <p><b>Unit Saat Ini:</b> {{ selectedUser.unit || '-' }}</p>
        </div>
        <div class="column is-12">
          <VField label="Unit Baru">
            <VControl>
              <AutoComplete v-model="selectedUnit" :suggestions="d_unit" @complete="fetchUnitDropdown($event)"
                optionLabel="label" :dropdown="true" :minLength="1" class="is-input" appendTo="body"
                loadingIcon="pi pi-spinner" field="label" placeholder="Ketik untuk mencari unit..." />
            </VControl>
          </VField>
        </div>
      </div>
      <template #footer>
        <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="modalUnit = false">
          Tutup
        </VButton>
        <VButton type="button" rounded outlined color="primary" raised icon="feather:save" :loading="isLoadingTT"
          @click="saveUnit()">
          Simpan
        </VButton>
      </template>
    </Dialog>
  </VCard>
</template>

<script setup lang="ts">
import { useApi } from '/@src/composable/useApi'
import { ref, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import AutoComplete from 'primevue/autocomplete'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'   


useHead({
  title: 'Master User & Unit - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setFullWidth(true)

const item: any = ref({
  aktif: true,
})

const dataSource: any = ref([])
const isLoading = ref(false)
const isLoadingTT = ref(false)
const filters = ref('')

const d_statusVerifikasi = [
  { name: 'Semua Status', value: '' },
  { name: 'Terverifikasi', value: 'terverifikasi' },
  { name: 'Belum Verifikasi', value: 'belum' },
]
const statusVerifikasi: any = ref('')

const modalUnit = ref(false)
const selectedUser: any = ref(null)
const selectedUnit: any = ref(null)
const d_unit: any = ref([])

const dataSourcefiltered = computed(() => {
  if (!filters.value) return dataSource.value
  const f = filters.value.toUpperCase()
  return dataSource.value.filter((row: any) => {
    return [row.name, row.email, row.nowa, row.unit]
      .map((v: any) => (v ?? '').toString().toUpperCase())
      .some((col: string) => col.includes(f))
  })
})

async function fetchData() {
  isLoading.value = true
  try {
    const response = await useApi().get(
      '/sysadmin/master-user-unit?statusenabled=' +
      (item.value.aktif ? 'true' : 'false') +
      '&status_verifikasi=' +
      (statusVerifikasi.value ?? '')
    )
    dataSource.value = (response.data || []).map((el: any, idx: number) => ({
      ...el,
      no: idx + 1,
    }))
  } finally {
    isLoading.value = false
  }
}

async function fetchUnitDropdown(e: any) {
  const response = await useApi().get(
    `general/dropdown/mitra_m?select=id,namaperusahaan&param_search=namaperusahaan&query=${e.query}&limit=10`
  )
  d_unit.value = response
}

function openUbahUnit(row: any) {
  selectedUser.value = row
  selectedUnit.value = row.mitrafk ? { value: row.mitrafk, label: row.unit } : null
  modalUnit.value = true
}

async function saveUnit() {
  if (!selectedUnit.value || !selectedUnit.value.value) {
    useToaster().error('Pilih unit terlebih dahulu')
    return
  }
  isLoadingTT.value = true
  await useApi()
    .post('/sysadmin/update-unit-user', {
      id: selectedUser.value.id,
      mitrafk: selectedUnit.value.value,
    })
    .then(
      () => {
        isLoadingTT.value = false
        modalUnit.value = false
        fetchData()
      },
      () => {
        isLoadingTT.value = false
      }
    )
}

function toggleVerify(row: any, verified: boolean) {
  const message = verified
    ? `Verifikasi akun "${row.name}" secara manual?`
    : `Batalkan status verifikasi akun "${row.name}"?`
  H.confirm(message, async () => {
    await useApi()
      .post('/sysadmin/verify-user-unit', { id: row.id, verified })
      .then(
        () => fetchData(),
        () => { }
      )
  })
}

fetchData()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';
</style>
