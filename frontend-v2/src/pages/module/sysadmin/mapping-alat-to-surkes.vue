<template>
  <div class="page-content-inner">
    <ConfirmDialog />
    <div class="is-navbar">
      <div class="form-outer">
        <div class="form-body mt-5">
          <div class="columns is-multiline">
            <div class="column is-12">
              <div class="surkes-location-tabs">
                <button v-for="tab in lokasiTabs" :key="tab.value"
                  :class="['surkes-tab-button', { 'is-active': activeLokasi === tab.value }]"
                  @click="switchLokasi(tab.value)">
                  <span>{{ tab.label }}</span>
                  <small>{{ tab.caption }}</small>
                </button>
              </div>
            </div>

            <div class="column is-7">
              <VCard class="h-100">
                <div class="card-header-flex mb-4">
                  <h4 class="title is-5">Data Mapping Terdaftar - {{ activeLokasiLabel }}</h4>
                  <div class="is-flex is-align-items-center">
                    <VButton size="small" color="info" icon="feather:activity" class="mr-2"
                      @click="router.push(`/module/rbk/monitoring-alat-surkes?lokasi_id=${activeLokasi}`)">
                      Monitoring
                    </VButton>
                    <VButton size="small" color="primary" icon="feather:grid" class="mr-2"
                      @click="router.push('/module/sysadmin/mapping-alat-to-layanan')">
                      Katalog Layanan
                    </VButton>
                    <VButton size="small" color="success" icon="feather:file-text" class="mr-2" @click="exportToExcel">
                      Excel</VButton>
                    <VButton size="small" color="danger" icon="feather:download" class="mr-4" @click="exportToPDF">PDF
                    </VButton>
                    <span class="tag is-rounded is-primary is-light" style="font-size: 1rem; font-weight: bold;">
                      Total: {{ existingMappings.length }} Data
                    </span>
                  </div>
                </div>

                <div class="columns is-multiline mb-3">
                  <div v-if="!IDUNIT_EFFECTIVE" class="column is-5">
                    <VField label="Filter Unit">
                      <VControl>
                        <Multiselect v-model="filterParams.mitraId" :options="optMitra" placeholder="Pilih Unit..."
                          :searchable="true" @select="handleFilterChange" @clear="handleFilterChange" />
                      </VControl>
                    </VField>
                  </div>

                  <div :class="[IDUNIT_EFFECTIVE ? 'column is-10' : 'column is-5']">
                    <VField label="Filter Lingkup">
                      <VControl>
                        <Multiselect v-model="filterParams.lingkupId" :options="optLingkup"
                          placeholder="Pilih Lingkup..." :searchable="true" @select="handleFilterChange"
                          @clear="handleFilterChange" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-2 is-flex is-align-items-end">
                    <VButton color="warning" block outlined @click="resetFilter" style="margin-bottom: 0.75rem;">Reset
                    </VButton>
                  </div>
                </div>

                <DataTable :value="existingMappings" :paginator="true" :rows="10" :rowsPerPageOptions="[10, 20, 50]"
                  paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                  currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" responsiveLayout="scroll"
                  class="p-datatable-sm custom-datatable">
                  <Column field="namaperusahaan" header="Unit" :sortable="true">
                    <template #body="slotProps">
                      <small>{{ slotProps.data.namaperusahaan }}</small>
                    </template>
                  </Column>
                  <Column field="namaproduk" header="Alat" :sortable="true">
                    <template #body="slotProps">
                      <b class="is-size-7">{{ slotProps.data.namaproduk }}</b>
                    </template>
                  </Column>
                  <Column header="Merk/Tipe">
                    <template #body="slotProps">
                      <span class="tag is-light">{{ slotProps.data.namamerk || '-' }}/{{ slotProps.data.namatipe
                        }}</span>
                    </template>
                  </Column>
                  <Column field="namaserialnumber" header="SN">
                    <template #body="slotProps">
                      <span class="tag is-light">{{ slotProps.data.namaserialnumber || '-' }}</span>
                    </template>
                  </Column>
                  <Column field="lingkupkalibrasi" header="Lingkup">
                    <template #body="slotProps">
                      <span class="tag is-info is-light">{{ slotProps.data.lingkupkalibrasi }}</span>
                    </template>
                  </Column>
                  <Column header="" headerStyle="width: 4rem">
                    <template #body="slotProps">
                      <button class="button is-small is-danger is-inverted" @click="confirmDelete(slotProps.data.id)">
                        <i class="feather-icon iconify" data-icon="feather:trash-2"></i>
                      </button>
                    </template>
                  </Column>
                </DataTable>
              </VCard>
            </div>

            <div class="column is-5">
              <VCard class="h-100 border-primary">
                <div class="card-header-flex mb-4">
                  <h4 class="title is-5 has-text-primary">Input Mapping Baru</h4>
                  <VButton type="button" icon="feather:save" :loading="isLoading" color="primary" raised size="small"
                    @click="saveMapping()">
                    Simpan Mapping
                  </VButton>
                </div>

                <div class="field-group-box">
                  <VField v-if="!IDUNIT_EFFECTIVE" label="1. Pilih Unit (Mitra)">
                    <VControl>
                      <Multiselect v-model="form.mitraId" :options="optMitra" placeholder="Cari Unit Pemilik Alat..."
                        :searchable="true" @select="fetchAlatByUnit" @clear="clearAlatData" />
                    </VControl>
                  </VField>
                  <div v-else class="notification is-info is-light mb-3 py-2 px-4">
                    <small>Unit Terkunci: <b>{{ existingMappings[0]?.namaperusahaan || 'Unit Terpilih' }}</b></small>
                  </div>

                  <VField label="2. Pilih Lingkup Tujuan" class="mt-3">
                    <VControl>
                      <Multiselect v-model="form.lingkupId" :options="optLingkup" placeholder="Lingkup Kalibrasi..."
                        :searchable="true" />
                    </VControl>
                  </VField>
                </div>

                <div class="divider-text mt-5 mb-3">
                  <span>3. Pilih Alat ({{ selectedAlatCount }} Terpilih)</span>
                </div>

                <div class="alat-input-wrapper">
                  <VField>
                    <VControl icon="feather:search">
                      <input v-model="filterAlat" class="input is-rounded" placeholder="Cari nama alat / SN..." />
                    </VControl>
                  </VField>

                  <div class="is-flex is-justify-content-end mb-2">
                    <small class="has-text-grey">Menampilkan: <b>{{ filteredAlat.length }}</b> Alat</small>
                  </div>

                  <div class="alat-scroll-container">
                    <div v-if="listAlat.length === 0" class="has-text-centered py-6 has-text-grey-light">
                      <i class="iconify is-size-1 mb-2" data-icon="feather:box"></i>
                      <p>Daftar alat tidak ditemukan</p>
                    </div>

                    <div v-for="alat in filteredAlat" :key="alat.id" class="alat-select-item"
                      :class="{ 'is-mapped': alat.mapping_id, 'is-checked': selectedAlat[alat.id] }">
                      <VField>
                        <VControl raw>
                          <VCheckbox v-model="selectedAlat[alat.id]" :disabled="alat.mapping_id" color="info" circle>
                            <div class="alat-detail">
                              <span class="name">{{ alat.namaproduk }}</span>
                              <span class="sn">MERK/TIPE: {{ alat.namamerk || '-' }}/ {{ alat.namatipe }}</span>
                              <span class="sn">SN: {{ alat.namaserialnumber || '-' }}</span>
                            </div>
                            <div v-if="alat.mapping_id" class="status-tag">Ter-mapping</div>
                          </VCheckbox>
                        </VControl>
                      </VField>
                    </div>
                  </div>
                </div>
              </VCard>
            </div>

            <div class="column is-12 mt-4">
              <VCard>
                <div class="card-header-flex mb-4">
                  <h4 class="title is-5">Visualisasi Sebaran Alat per Lingkup</h4>
                </div>
                <div class="columns">
                  <div class="column is-8">
                    <VueApexCharts type="bar" height="350" :options="chartOptions" :series="chartSeries" />
                  </div>
                  <div class="column is-4">
                    <div class="box has-background-light">
                      <h6 class="title is-6 mb-3">Ringkasan Lingkup</h6>
                      <div v-for="(val, key) in chartSummary" :key="key"
                        class="is-flex is-justify-content-space-between mb-2">
                        <span>{{ key }}</span>
                        <b class="has-text-primary">{{ val }} Alat</b>
                      </div>
                    </div>
                  </div>
                </div>
              </VCard>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, defineProps, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import Divider from 'primevue/divider'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import VueApexCharts from 'vue3-apexcharts'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'

// Import library ekspor klien
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

const route = useRoute()
const router = useRouter()
const viewWrapper = useViewWrapper()
const confirm = useConfirm()

const props = defineProps({
  hide: Boolean,
  idunit: String
})

const IDUNIT_EFFECTIVE = computed(() => props.idunit || (route.query.idunit as string))
const normalizeLokasiId = (value: any) => {
  if (String(value).toLowerCase() === 'gresik') return '2'
  return String(value || '1') === '2' ? '2' : '1'
}

useHead({
  title: 'Mapping Alat to Surkes - ' + import.meta.env.VITE_PROJECT,
})

viewWrapper.setPageTitle('Master Mapping Alat ke Lingkup')
viewWrapper.setFullWidth(true)

const { y } = useWindowScroll()
const isStuck = computed(() => y.value > 30)

const isLoading = ref(false)
const filterAlat = ref('')
const existingMappings = ref<any[]>([])
const activeLokasi = ref(normalizeLokasiId(route.query.lokasi_id))
const lokasiTabs = [
  { value: '1', label: 'Surkes Jakarta', caption: 'Unit lokasisurkes Jakarta' },
  { value: '2', label: 'Surkes Gresik', caption: 'Unit lokasisurkes Gresik' },
]
const activeLokasiLabel = computed(() => activeLokasi.value === '2' ? 'Surkes Gresik' : 'Surkes Jakarta')

const filterParams = ref({
  mitraId: null as string | null,
  lingkupId: null as string | null
})

const form = ref({
  mitraId: null as string | null,
  lingkupId: null as string | null
})

const optMitra = ref([])
const optLingkup = ref([])
const listAlat = ref([])
const selectedAlat = ref<any>({})

// --- CHART LOGIC ---
const chartSummary = computed(() => {
  const summary: any = {}
  existingMappings.value.forEach(item => {
    const key = item.lingkupkalibrasi || 'Tidak Diketahui'
    summary[key] = (summary[key] || 0) + 1
  })
  return summary
})

const chartSeries = computed(() => {
  return [{
    name: 'Jumlah Alat',
    data: Object.values(chartSummary.value)
  }]
})

const chartOptions = computed(() => ({
  chart: { id: 'sebaran-alat-lingkup', toolbar: { show: false } },
  plotOptions: { bar: { borderRadius: 4, horizontal: false, columnWidth: '45%', distributed: true } },
  dataLabels: { enabled: true },
  xaxis: { categories: Object.keys(chartSummary.value) },
  colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
  legend: { show: false }
}))

const filteredAlat = computed(() => {
  if (!filterAlat.value) return listAlat.value
  const regex = new RegExp(filterAlat.value, 'i')
  return listAlat.value.filter((a: any) =>
    (a.namaproduk && a.namaproduk.match(regex)) ||
    (a.namaserialnumber && a.namaserialnumber.match(regex))
  )
})

const selectedAlatCount = computed(() => {
  return Object.values(selectedAlat.value).filter(v => v === true).length
})

// --- EXPORT CLIENT SIDE ---
function exportToExcel() {
  if (existingMappings.value.length === 0) return H.alert('warning', 'Tidak ada data untuk diekspor')

  const dataToExport = existingMappings.value.map(m => ({
    'Unit/Mitra': m.namaperusahaan,
    'Nama Alat': m.namaproduk,
    'Merk': m.namamerk || '-',
    'Tipe': m.namatipe || '-',
    'Serial Number': m.namaserialnumber || '-',
    'Lingkup Kalibrasi': m.lingkupkalibrasi
  }))

  const worksheet = XLSX.utils.json_to_sheet(dataToExport)
  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, "Mapping_Alat")
  XLSX.writeFile(workbook, `Mapping_Alat_Surkes_${Date.now()}.xlsx`)
}

function exportToPDF() {
  if (existingMappings.value.length === 0) return H.alert('warning', 'Tidak ada data untuk diekspor')

  const doc = new jsPDF('l', 'mm', 'a4')
  doc.text("Laporan Mapping Alat ke Lingkup Kalibrasi", 14, 15)

  const body = existingMappings.value.map(m => [
    m.namaperusahaan,
    m.namaproduk,
    `${m.namamerk || '-'}/${m.namatipe || '-'}`,
    m.namaserialnumber || '-',
    m.lingkupkalibrasi
  ])

  autoTable(doc, {
    startY: 20,
    head: [['Unit', 'Alat', 'Merk/Tipe', 'SN', 'Lingkup']],
    body: body,
    theme: 'striped',
    headStyles: { fillColor: [59, 130, 246] }
  })

  doc.save(`Mapping_Alat_Surkes_${Date.now()}.pdf`)
}

function clearAlatData() {
  listAlat.value = []
  selectedAlat.value = {}
  filterAlat.value = ''
}

async function initDropdown() {
  try {
    const res = await useApi().get(`/surkes/mapping-surkes-dropdown?lokasi_id=${activeLokasi.value}`)
    const data = res.data ? res.data : res
    if (data) {
      optMitra.value = data.mitra || []
      optLingkup.value = (data.lingkup || []).map((l: any) => ({
        label: l.label ? l.label : l.lingkupkalibrasi,
        value: l.value ? l.value : l.id
      }))
    }
  } catch (e: any) {
    console.error("Error initDropdown:", e)
  }
}

async function loadExistingMapping() {
  try {
    const params = new URLSearchParams()
    params.append('lokasi_id', activeLokasi.value)
    if (filterParams.value.mitraId) params.append('mitra_id', filterParams.value.mitraId)
    if (filterParams.value.lingkupId) params.append('lingkup_id', filterParams.value.lingkupId)

    const res = await useApi().get(`/surkes/list-mapping-alat?${params.toString()}`)
    existingMappings.value = res.data ? res.data : (Array.isArray(res) ? res : [])
  } catch (e) {
    console.error(e)
  }
}

function handleFilterChange() {
  loadExistingMapping()
}

async function fetchAlatByUnit() {
  const targetId = form.value.mitraId
  if (!targetId) return
  clearAlatData()
  try {
    const res = await useApi().get(`/surkes/get-alat-by-unit?mitra_id=${targetId}`)
    const resData = res.data ? res.data : res
    listAlat.value = Array.isArray(resData) ? resData : (resData.data || [])
  } catch (e) {
    H.alert('error', 'Gagal mengambil data alat')
  }
}

async function saveMapping() {
  if (!form.value.mitraId) return H.alert('warning', 'Pilih Unit dulu')
  if (!form.value.lingkupId) return H.alert('warning', 'Pilih Lingkup dulu')
  if (selectedAlatCount.value === 0) return H.alert('warning', 'Pilih alat minimal 1')

  const detailArr: any[] = []
  Object.keys(selectedAlat.value).forEach(key => {
    if (selectedAlat.value[key] === true) detailArr.push({ objectalatfk: key })
  })

  const payload = {
    objectmitrafk: form.value.mitraId,
    objectlingkupfk: form.value.lingkupId,
    detail: detailArr
  }

  isLoading.value = true
  try {
    await useApi().post('/surkes/save-mapping-alat-lingkup', payload)
    H.alert('success', 'Mapping Berhasil disimpan')
    await fetchAlatByUnit()
    await loadExistingMapping()
  } catch (e) {
    H.alert('error', 'Gagal menyimpan mapping')
  } finally {
    isLoading.value = false
  }
}

async function confirmDelete(id: any) {
  confirm.require({
    message: 'Apakah Anda yakin ingin menghapus mapping alat ini?',
    header: 'Konfirmasi Hapus Mapping',
    icon: 'pi pi-info-circle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Hapus',
    rejectLabel: 'Batal',
    accept: async () => {
      try {
        await useApi().post('/surkes/delete-mapping-alat', { id: id })
        H.alert('success', 'Mapping berhasil dihapus')
        loadExistingMapping()
        if (form.value.mitraId) fetchAlatByUnit()
      } catch (e) {
        H.alert('error', 'Gagal menghapus mapping')
      }
    },
    reject: () => { },
  })
}

async function switchLokasi(value: string) {
  if (activeLokasi.value === value) return
  activeLokasi.value = value
  if (!IDUNIT_EFFECTIVE.value) {
    filterParams.value.mitraId = null
    form.value.mitraId = null
  }
  clearAlatData()
  await initDropdown()
  await loadExistingMapping()
}

function resetFilter() {
  filterParams.value.mitraId = IDUNIT_EFFECTIVE.value || null
  filterParams.value.lingkupId = null
  loadExistingMapping()
}

watch(IDUNIT_EFFECTIVE, (newVal) => {
  if (newVal) {
    filterParams.value.mitraId = newVal
    form.value.mitraId = newVal
    loadExistingMapping()
    fetchAlatByUnit()
  }
}, { immediate: true })

onMounted(async () => {
  await initDropdown()
  if (IDUNIT_EFFECTIVE.value) {
    filterParams.value.mitraId = IDUNIT_EFFECTIVE.value
    form.value.mitraId = IDUNIT_EFFECTIVE.value
    fetchAlatByUnit()
  }
  loadExistingMapping()
})

function refreshAll() {
  initDropdown()
  loadExistingMapping()
  if (form.value.mitraId) fetchAlatByUnit()
}
</script>

<style lang="scss" scoped>
@import '/@src/scss/abstracts/all';

.form-outer {
  padding: 0 1rem;
}

.surkes-location-tabs {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  padding: 0.45rem;
  border: 1px solid #dbe3ef;
  border-radius: 8px;
  background: #f8fafc;
}

.surkes-tab-button {
  display: grid;
  gap: 0.1rem;
  min-width: 180px;
  padding: 0.7rem 1rem;
  border: 1px solid transparent;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;

  span {
    font-weight: 800;
    color: #334155;
  }

  small {
    font-size: 0.72rem;
    color: #94a3b8;
  }

  &.is-active {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.12);

    span {
      color: #1d4ed8;
    }
  }
}

.card-header-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 2px solid #f0f0f0;
  padding-bottom: 10px;
}

:deep(.custom-datatable) {
  .p-datatable-thead>tr>th {
    background: #fafafa;
    text-transform: uppercase;
    font-size: 0.7rem;
    color: #888;
    padding: 0.75rem;
  }

  .p-datatable-tbody>tr>td {
    padding: 0.75rem;
    font-size: 0.85rem;
  }

  .p-paginator {
    padding: 0.5rem;
    font-size: 0.8rem;
  }
}

.field-group-box {
  background: #fcfcfc;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px dashed #e0e0e0;
}

.divider-text {
  position: relative;
  text-align: center;

  &:before {
    content: "";
    position: absolute;
    top: 50%;
    left: 0;
    width: 100%;
    height: 1px;
    background: #e0e0e0;
    z-index: 1;
  }

  span {
    position: relative;
    background: #fff;
    padding: 0 15px;
    z-index: 2;
    font-weight: bold;
    color: #666;
    font-size: 0.85rem;
  }
}

.alat-scroll-container {
  max-height: 450px;
  overflow-y: auto;
  padding-right: 10px;

  &::-webkit-scrollbar {
    width: 5px;
  }

  &::-webkit-scrollbar-thumb {
    background: #eee;
    border-radius: 10px;
  }
}

.alat-select-item {
  margin-bottom: 8px;
  padding: 12px;
  border: 1px solid #f0f0f0;
  border-radius: 10px;
  background: #fff;
  transition: all 0.2s;

  &:hover {
    border-color: var(--primary);
    background: #f9f9ff;
  }

  &.is-checked {
    border-color: var(--primary);
    background: #f0f4ff;
  }

  &.is-mapped {
    opacity: 0.6;
    background: #f5f5f5;
    pointer-events: none;
    border-style: dashed;
  }

  .alat-detail {
    display: flex;
    flex-direction: column;
    margin-left: 10px;

    .name {
      font-weight: 600;
      font-size: 0.9rem;
      color: #333;
    }

    .sn {
      font-size: 0.75rem;
      color: #999;
    }
  }

  .status-tag {
    margin-left: auto;
    font-size: 0.65rem;
    background: #eee;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: bold;
    color: #ac2d2d;
  }
}

.border-primary {
  border-top: 4px solid var(--primary) !important;
}

.h-100 {
  height: 100%;
}

:deep(.multiselect-caret) {
  background-color: transparent !important;
  mask-image: none !important;
}

:deep(.multiselect-clear) {
  z-index: 10;
}
</style>
