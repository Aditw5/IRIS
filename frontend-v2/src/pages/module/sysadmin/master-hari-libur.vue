<template>
  <ConfirmDialog />

  <VCard>
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Master Hari Libur & Cuti Nasional</h3>
    </div>

    <div class="columns is-multiline">
      <div class="column is-12">
        <div class="holiday-summary">
          <div class="summary-card">
            <div class="summary-icon">
              <i class="iconify" data-icon="feather:calendar" aria-hidden="true"></i>
            </div>
            <div>
              <div class="summary-label">Tahun</div>
              <div class="summary-value">{{ item.tahun }}</div>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon is-success">
              <i class="iconify" data-icon="feather:check-circle" aria-hidden="true"></i>
            </div>
            <div>
              <div class="summary-label">Total Data</div>
              <div class="summary-value">{{ dataSource.length }}</div>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-icon is-info">
              <i class="iconify" data-icon="feather:cloud" aria-hidden="true"></i>
            </div>
            <div>
              <div class="summary-label">Sumber</div>
              <div class="summary-value is-small">API Hari Libur</div>
            </div>
          </div>
        </div>
      </div>

      <div class="column is-8">
        <div class="user-grid-toolbar">
          <VControl icon="feather:search">
            <input
              v-model="filters"
              class="input custom-text-filter"
              placeholder="Cari nama libur, tanggal, atau jenis..."
            />
          </VControl>

          <div class="buttons">
            <VField>
              <VControl icon="feather:calendar">
                <VInput
                  type="number"
                  v-model="item.tahun"
                  placeholder="Tahun"
                  class="is-rounded input-tahun"
                  @keyup.enter="fetchData()"
                />
              </VControl>
            </VField>

            <VButton
              type="button"
              icon="feather:search"
              color="info"
              outlined
              raised
              :loading="isLoading"
              @click="fetchData()"
            >
              Tampilkan
            </VButton>

            <VButton
              type="button"
              icon="feather:refresh-cw"
              color="success"
              raised
              :loading="isSyncing"
              @click="syncHariLibur()"
            >
              Sync API
            </VButton>

            <VField v-slot="{ id }" class="is-icon-select">
              <VControl>
                <Multiselect
                  v-model="selectView"
                  :attrs="{ id }"
                  placeholder="Select View"
                  label="name"
                  :options="d_View"
                  :searchable="true"
                  track-by="name"
                  mode="single"
                  @select="changeView(selectView)"
                  autocomplete="off"
                >
                  <template #singlelabel="{ value }">
                    <div class="multiselect-single-label">
                      <div class="select-label-icon-wrap">
                        <i :class="value.icon"></i>
                      </div>
                      <span class="select-label-text">
                        {{ value.name }}
                      </span>
                    </div>
                  </template>

                  <template #option="{ option }">
                    <div class="select-option-icon-wrap">
                      <i :class="option.icon"></i>
                    </div>
                    <span class="select-option-text">
                      {{ option.name }}
                    </span>
                  </template>
                </Multiselect>
              </VControl>
            </VField>
          </div>
        </div>

        <div v-if="isLoading" class="loading-wrap">
          <i class="iconify loading-icon" data-icon="feather:loader" aria-hidden="true"></i>
          <span>Memuat data hari libur...</span>
        </div>

        <div v-else>
          <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
            <DataTable
              :value="dataSourcefiltered"
              :paginator="true"
              :rows="10"
              :rowsPerPageOptions="[5, 10, 25, 50]"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack"
              breakpoint="960px"
              sortMode="multiple"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
              stripedRows
            >
              <Column field="no" header="No" style="width: 70px"></Column>

              <Column field="tanggal_format" header="Tanggal" :sortable="true"></Column>

              <Column field="hari" header="Hari" :sortable="true"></Column>

              <Column field="nama_libur" header="Nama Libur" :sortable="true">
                <template #body="slotProps">
                  <div class="holiday-name">
                    {{ slotProps.data.nama_libur || '-' }}
                  </div>
                </template>
              </Column>

              <Column field="jenis_libur" header="Jenis" :sortable="true">
                <template #body="slotProps">
                  <VTag
                    :color="slotProps.data.jenis_libur === 'cuti_bersama' ? 'warning' : 'info'"
                    :label="slotProps.data.jenis_libur_label"
                  />
                </template>
              </Column>

              <Column field="status" header="Status">
                <template #body="slotProps">
                  <VTag
                    :color="slotProps.data.status_c"
                    :label="slotProps.data.status"
                  />
                </template>
              </Column>

              <Column field="source_api" header="Source API">
                <template #body="slotProps">
                  <span class="source-api">
                    {{ slotProps.data.source_api || '-' }}
                  </span>
                </template>
              </Column>
            </DataTable>

            <div v-if="dataSourcefiltered.length === 0" class="empty-state">
              <i class="iconify" data-icon="feather:calendar" aria-hidden="true"></i>
              <h3>Data tidak ditemukan</h3>
              <p>Silakan sync API atau ubah kata kunci pencarian.</p>
            </div>
          </div>

          <div class="list-view list-view-v3" v-else-if="selectView == 'grid'">
            <TransitionGroup name="list-complete" tag="div">
              <div
                v-for="holiday in dataSourcefiltered"
                :key="holiday.id"
                class="list-view-item"
              >
                <div class="list-view-item-inner">
                  <VAvatar
                    size="medium"
                    picture="/images/avatars/svg/calendar.svg"
                    color="primary"
                    squared
                    bordered
                  />

                  <div class="meta-left">
                    <h3>{{ holiday.nama_libur || '-' }}</h3>

                    <span>
                      {{ holiday.hari }}, {{ holiday.tanggal_format }}
                    </span>

                    <div class="mt-2">
                      <VTag
                        :color="holiday.jenis_libur === 'cuti_bersama' ? 'warning' : 'info'"
                        :label="holiday.jenis_libur_label"
                      />

                      <VTag
                        class="ml-2"
                        :color="holiday.status_c"
                        :label="holiday.status"
                      />
                    </div>
                  </div>

                  <div class="meta-right">
                    <div class="holiday-date-box">
                      <div class="date-day">{{ holiday.tanggal_hari }}</div>
                      <div class="date-month">{{ holiday.tanggal_bulan }}</div>
                      <div class="date-year">{{ holiday.tahun }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </TransitionGroup>

            <div v-if="dataSourcefiltered.length === 0" class="empty-state">
              <i class="iconify" data-icon="feather:calendar" aria-hidden="true"></i>
              <h3>Data tidak ditemukan</h3>
              <p>Silakan sync API atau ubah kata kunci pencarian.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="column is-4">
        <img
          src="/images/avatars/label/agama.png"
          alt=""
          srcset=""
          style="max-width:65%;margin-left: 8rem; margin-top: -5rem;"
        />

        <VCard style="margin-top :-3rem">
          <div class="columns is-multiline">
            <div class="column is-12">
              <h3 class="title is-6 mb-2 mr-1">
                <i class="iconify" data-icon="feather:settings" aria-hidden="true"></i>
                Sinkronisasi Hari Libur
              </h3>
            </div>

            <div class="column is-12">
              <VField label="Tahun">
                <VControl icon="feather:calendar">
                  <VInput
                    type="number"
                    v-model="item.tahun"
                    placeholder="Masukkan Tahun"
                    class="is-rounded"
                  />
                </VControl>
              </VField>
            </div>

            <div class="column is-12">
              <VButton
                @click="syncHariLibur()"
                :loading="isSyncing"
                type="button"
                icon="feather:refresh-cw"
                class="is-fullwidth mr-3"
                color="success"
                raised
              >
                Sync Hari Libur dari API
              </VButton>
            </div>

            <div class="column is-12">
              <VButton
                @click="fetchData()"
                :loading="isLoading"
                type="button"
                icon="feather:list"
                class="is-fullwidth is-outlined"
                color="info"
                raised
              >
                Ambil Data Tersimpan
              </VButton>
            </div>

            <!-- <div class="column is-12">
              <VMessage color="info">
                Data hari libur disimpan ke database terlebih dahulu.
                Perhitungan durasi nantinya membaca data dari tabel
                <b>master_hari_libur_m</b>, bukan langsung dari API.
              </VMessage>
            </div>

            <div class="column is-12">
              <div class="info-box">
                <div class="info-title">Aturan Durasi</div>
                <ul>
                  <li>Senin - Jumat dihitung</li>
                  <li>Sabtu - Minggu tidak dihitung</li>
                  <li>Tanggal merah tidak dihitung</li>
                  <li>Cuti bersama tidak dihitung</li>
                  <li>Jam kerja tetap 07:30 - 16:00</li>
                </ul>
              </div>
            </div> -->
          </div>
        </VCard>
      </div>
    </div>
  </VCard>
</template>

<script setup lang="ts">
import { useApi } from '/@src/composable/useApi'
import { ref, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import ConfirmDialog from 'primevue/confirmdialog'
import moment from 'moment'
import { useViewWrapper } from '/@src/stores/viewWrapper'

useHead({
  title: 'Hari Libur - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const item: any = ref({
  tahun: new Date().getFullYear(),
})

const dataSource: any = ref([])
const isLoading: any = ref(false)
const isSyncing: any = ref(false)
const filters = ref('')

const d_View = [
  {
    name: 'Grid View',
    value: 'grid',
    icon: 'fas fa-id-card-alt',
  },
  {
    name: 'List View',
    value: 'list',
    icon: 'fas fa-list',
  },
]

const selectView: any = ref('list')

const dataSourcefiltered = computed(() => {
  if (!filters.value) {
    return dataSource.value
  }

  return dataSource.value.filter((items: any) => {
    const keyword = String(filters.value).toLowerCase()

    const namaLibur = items.nama_libur ? String(items.nama_libur).toLowerCase() : ''
    const tanggal = items.tanggal_format ? String(items.tanggal_format).toLowerCase() : ''
    const tanggalRaw = items.tanggal ? String(items.tanggal).toLowerCase() : ''
    const jenisLibur = items.jenis_libur ? String(items.jenis_libur).toLowerCase() : ''
    const jenisLiburLabel = items.jenis_libur_label ? String(items.jenis_libur_label).toLowerCase() : ''
    const hari = items.hari ? String(items.hari).toLowerCase() : ''
    const tahun = items.tahun ? String(items.tahun).toLowerCase() : ''

    return (
      namaLibur.includes(keyword) ||
      tanggal.includes(keyword) ||
      tanggalRaw.includes(keyword) ||
      jenisLibur.includes(keyword) ||
      jenisLiburLabel.includes(keyword) ||
      hari.includes(keyword) ||
      tahun.includes(keyword)
    )
  })
})

function getApiBody(response: any) {
  if (response && response.data && response.data.success !== undefined) {
    return response.data
  }

  if (response && response.response && response.response.success !== undefined) {
    return response.response
  }

  if (response && response.success !== undefined) {
    return response
  }

  return response
}

function getDataArrayFromResponse(response: any) {
  const body = getApiBody(response)

  if (body && Array.isArray(body.data)) {
    return body.data
  }

  if (response && response.data && Array.isArray(response.data.data)) {
    return response.data.data
  }

  if (response && response.response && Array.isArray(response.response.data)) {
    return response.response.data
  }

  if (Array.isArray(response)) {
    return response
  }

  return []
}

function getJumlahFromResponse(response: any) {
  const body = getApiBody(response)

  if (body && body.jumlah !== undefined) {
    return body.jumlah
  }

  if (response && response.data && response.data.jumlah !== undefined) {
    return response.data.jumlah
  }

  if (response && response.response && response.response.jumlah !== undefined) {
    return response.response.jumlah
  }

  return 0
}

function getMessageFromResponse(response: any) {
  const body = getApiBody(response)

  if (body && body.message) {
    return body.message
  }

  if (response && response.data && response.data.message) {
    return response.data.message
  }

  if (response && response.response && response.response.message) {
    return response.response.message
  }

  return ''
}

function getTanggalValue(tanggal: any) {
  if (!tanggal) {
    return null
  }

  if (typeof tanggal === 'string') {
    return tanggal
  }

  if (tanggal.date) {
    return tanggal.date
  }

  return tanggal
}

function formatTanggal(tanggal: any) {
  const value = getTanggalValue(tanggal)

  if (!value) {
    return '-'
  }

  return moment.utc(value).format('DD-MM-YYYY')
}

function formatHari(tanggal: any) {
  const value = getTanggalValue(tanggal)

  if (!value) {
    return '-'
  }

  const hari = moment.utc(value).format('dddd')

  const mapHari: any = {
    Sunday: 'Minggu',
    Monday: 'Senin',
    Tuesday: 'Selasa',
    Wednesday: 'Rabu',
    Thursday: 'Kamis',
    Friday: 'Jumat',
    Saturday: 'Sabtu',
  }

  return mapHari[hari] || hari
}

function formatBulanPendek(tanggal: any) {
  const value = getTanggalValue(tanggal)

  if (!value) {
    return '-'
  }

  const bulan = moment.utc(value).format('MMM')

  const mapBulan: any = {
    Jan: 'Jan',
    Feb: 'Feb',
    Mar: 'Mar',
    Apr: 'Apr',
    May: 'Mei',
    Jun: 'Jun',
    Jul: 'Jul',
    Aug: 'Agu',
    Sep: 'Sep',
    Oct: 'Okt',
    Nov: 'Nov',
    Dec: 'Des',
  }

  return mapBulan[bulan] || bulan
}

function normalizeData(data: any[]) {
  return data.map((element: any, index: number) => {
    const tanggalRaw = getTanggalValue(element.tanggal)

    element.no = index + 1
    element.tanggal_format = formatTanggal(tanggalRaw)
    element.hari = formatHari(tanggalRaw)
    element.tanggal_hari = tanggalRaw ? moment.utc(tanggalRaw).format('DD') : '-'
    element.tanggal_bulan = formatBulanPendek(tanggalRaw)
    element.tahun = element.tahun || (tanggalRaw ? moment.utc(tanggalRaw).format('YYYY') : item.value.tahun)

    element.jenis_libur_label =
      element.jenis_libur === 'cuti_bersama'
        ? 'Cuti Bersama'
        : element.jenis_libur === 'nasional'
          ? 'Libur Nasional'
          : element.jenis_libur || 'Libur'

    element.status =
      element.statusenabled === true ||
      element.statusenabled === 't' ||
      element.statusenabled === 1
        ? 'Aktif'
        : 'Tidak Aktif'

    element.status_c =
      element.statusenabled === true ||
      element.statusenabled === 't' ||
      element.statusenabled === 1
        ? 'success'
        : 'danger'

    return element
  })
}

async function fetchData() {
  if (!item.value.tahun) {
    useToaster().error('Tahun harus diisi')
    return
  }

  isLoading.value = true

  try {
    const response: any = await useApi().get(
      '/sysadmin/hari-libur?tahun=' + item.value.tahun
    )

    const data = getDataArrayFromResponse(response)

    dataSource.value = normalizeData(data)

    isLoading.value = false
  } catch (error: any) {
    isLoading.value = false
    dataSource.value = []
    useToaster().error('Gagal mengambil data hari libur')
  }
}

async function syncHariLibur() {
  if (!item.value.tahun) {
    useToaster().error('Tahun harus diisi')
    return
  }

  isSyncing.value = true

  try {
    const response: any = await useApi().get(
      '/sysadmin/hari-libur/sync?tahun=' + item.value.tahun
    )

    const jumlah = getJumlahFromResponse(response)
    const message = getMessageFromResponse(response)

    isSyncing.value = false

    useToaster().success(
      message
        ? message + '. Jumlah data: ' + jumlah
        : 'Sync hari libur berhasil. Jumlah data: ' + jumlah
    )

    await fetchData()
  } catch (error: any) {
    isSyncing.value = false

    let message = 'Gagal sync hari libur dari API'

    if (error && error.response && error.response.data && error.response.data.message) {
      message = error.response.data.message
    }

    useToaster().error(message)
  }
}

function changeView(e: any) {
  selectView.value = e
}

fetchData()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';

.holiday-summary {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;

  .summary-card {
    flex: 1;
    min-width: 180px;
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 1rem;
    border-radius: 16px;
    background: var(--white);
    border: 1px solid var(--fade-grey-dark-3);
    box-shadow: var(--light-box-shadow);

    .summary-icon {
      width: 42px;
      height: 42px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--primary);
      color: var(--white);
      font-size: 1.25rem;

      &.is-success {
        background: var(--success);
      }

      &.is-info {
        background: var(--info);
      }
    }

    .summary-label {
      font-size: 0.78rem;
      color: var(--light-text);
    }

    .summary-value {
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--dark-text);

      &.is-small {
        font-size: 0.9rem;
      }
    }
  }
}

.input-tahun {
  width: 120px;
}

.loading-wrap {
  min-height: 240px;
  border-radius: 16px;
  border: 1px dashed var(--fade-grey-dark-4);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  color: var(--light-text);

  .loading-icon {
    font-size: 1.5rem;
    animation: spin 1s linear infinite;
  }
}

.holiday-name {
  font-weight: 600;
  color: var(--dark-text);
}

.source-api {
  font-size: 0.8rem;
  color: var(--light-text);
}

.holiday-date-box {
  min-width: 74px;
  padding: 0.75rem;
  border-radius: 14px;
  text-align: center;
  background: var(--fade-grey-light-3);
  border: 1px solid var(--fade-grey-dark-3);

  .date-day {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1;
    color: var(--primary);
  }

  .date-month {
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 0.2rem;
    color: var(--dark-text);
  }

  .date-year {
    font-size: 0.75rem;
    color: var(--light-text);
  }
}

.info-box {
  padding: 1rem;
  border-radius: 14px;
  background: var(--fade-grey-light-3);
  border: 1px solid var(--fade-grey-dark-3);

  .info-title {
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--dark-text);
  }

  ul {
    margin-left: 1rem;
    color: var(--light-text);

    li {
      margin-bottom: 0.35rem;
      font-size: 0.9rem;
    }
  }
}

.empty-state {
  min-height: 260px;
  border: 1px dashed var(--fade-grey-dark-4);
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-top: 1rem;

  .iconify {
    font-size: 2.5rem;
    color: var(--light-text);
    margin-bottom: 0.75rem;
  }

  h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark-text);
  }

  p {
    color: var(--light-text);
    font-size: 0.9rem;
  }
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

.is-dark {
  .holiday-summary {
    .summary-card {
      background: var(--dark-sidebar-light-6);
      border-color: var(--dark-sidebar-light-12);

      .summary-value {
        color: var(--dark-dark-text);
      }
    }
  }

  .holiday-date-box,
  .info-box {
    background: var(--dark-sidebar-light-6);
    border-color: var(--dark-sidebar-light-12);
  }

  .holiday-name,
  .info-title,
  .empty-state h3 {
    color: var(--dark-dark-text);
  }
}

@media only screen and (max-width: 767px) {
  .holiday-summary {
    flex-direction: column;
  }

  .user-grid-toolbar {
    flex-direction: column;
    align-items: stretch;

    .buttons {
      margin-top: 1rem;
      width: 100%;

      .button {
        width: 100%;
      }
    }
  }

  .input-tahun {
    width: 100%;
  }
}
</style>