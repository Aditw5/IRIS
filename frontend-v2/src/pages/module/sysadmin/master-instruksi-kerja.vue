<template>
  <VCard class="ik-page">
    <div class="ik-heading">
      <div>
        <h3 class="title is-5 mb-1">Instruksi Kerja (IK)</h3>
        <p>Kelola lingkup, dokumen, dan seluruh versi file Instruksi Kerja.</p>
      </div>
      <div class="ik-heading-summary">
        <span><strong>{{ dataSourcefiltered.length }}</strong> data tampil</span>
        <span><strong>{{ totalFiles }}</strong> versi file</span>
        <VButton
          type="button"
          color="success"
          icon="fas fa-file-excel"
          :disabled="!dataSourcefiltered.length"
          raised
          @click="exportExcel"
        >
          Export Excel
        </VButton>
      </div>
    </div>

    <div class="ik-filter-panel">
      <div class="ik-filter-title">
        <span><i class="iconify" data-icon="feather:filter"></i> Filter Data</span>
        <button type="button" @click="clearFilter">Reset filter</button>
      </div>
      <div class="columns is-multiline mb-0">
        <div class="column is-4-desktop is-12-tablet">
          <VField label="Pencarian semua kolom">
            <VControl icon="feather:search">
              <input v-model="filters.keyword" class="input is-rounded" placeholder="Nomor, nama, lingkup, lokasi, atau file..." />
            </VControl>
          </VField>
        </div>
        <div class="column is-2-desktop is-6-tablet">
          <VField label="No. IK">
            <VControl>
              <input v-model="filters.number" class="input is-rounded uppercase" placeholder="Filter nomor" />
            </VControl>
          </VField>
        </div>
        <div class="column is-3-desktop is-6-tablet">
          <VField label="Nama IK">
            <VControl>
              <input v-model="filters.name" class="input is-rounded uppercase" placeholder="Filter nama" />
            </VControl>
          </VField>
        </div>
        <div class="column is-3-desktop is-6-tablet">
          <VField label="Lingkup">
            <VControl>
              <AutoComplete
                v-model="filters.scope"
                :suggestions="scopeOptions"
                optionLabel="label"
                field="label"
                :dropdown="true"
                :minLength="0"
                class="is-input"
                placeholder="Semua lingkup"
                @complete="fetchLingkup"
              />
            </VControl>
          </VField>
        </div>
        <div class="column is-3-desktop is-6-tablet">
          <VField label="Status">
            <VControl>
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                class="is-rounded is-fullwidth"
              />
            </VControl>
          </VField>
        </div>
        <div class="column is-3-desktop is-6-tablet">
          <VField label="Lokasi">
            <VControl>
              <Dropdown
                v-model="filters.location"
                :options="locationFilterOptions"
                optionLabel="label"
                optionValue="value"
                class="is-rounded is-fullwidth"
              />
            </VControl>
          </VField>
        </div>
        <div class="column is-3-desktop is-6-tablet">
          <VField label="Ketersediaan File">
            <VControl>
              <Dropdown
                v-model="filters.fileState"
                :options="fileOptions"
                optionLabel="label"
                optionValue="value"
                class="is-rounded is-fullwidth"
              />
            </VControl>
          </VField>
        </div>
        <div class="column is-3-desktop is-6-tablet">
          <VField label="Jumlah Versi">
            <VControl>
              <Dropdown
                v-model="filters.versionState"
                :options="versionOptions"
                optionLabel="label"
                optionValue="value"
                class="is-rounded is-fullwidth"
              />
            </VControl>
          </VField>
        </div>
        <div class="column is-3-desktop is-6-tablet">
          <VField label="Tampilan">
            <VControl>
              <Dropdown
                v-model="selectView"
                :options="viewOptions"
                optionLabel="label"
                optionValue="value"
                class="is-rounded is-fullwidth"
              />
            </VControl>
          </VField>
        </div>
      </div>
    </div>

    <div class="columns is-multiline">
      <div class="column is-8-widescreen is-7-desktop is-12-tablet">
        <div v-if="selectView === 'list'" class="ik-table-wrap">
          <DataTable
            :value="dataSourcefiltered"
            :loading="isLoading"
            class="p-datatable-sm"
            :paginator="true"
            :rows="10"
            :rowsPerPageOptions="[5, 10, 25, 50]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            currentPageReportTemplate="Menampilkan {first} sampai {last} dari {totalRecords} data"
            responsiveLayout="scroll"
            sortMode="multiple"
          >
            <template #empty>Belum ada data Instruksi Kerja yang sesuai filter.</template>
            <Column field="no" header="No" style="width: 4rem"></Column>
            <Column field="noisntruksikerja" header="No. Instruksi Kerja" :sortable="true"></Column>
            <Column field="namainstruksikerja" header="Nama Instruksi Kerja" :sortable="true"></Column>
            <Column field="lingkupkalibrasi" header="Lingkup" :sortable="true">
              <template #body="slotProps">
                <span v-if="slotProps.data.lingkupkalibrasi" class="ik-scope-tag">
                  {{ slotProps.data.lingkupkalibrasi }}
                </span>
                <span v-else class="has-text-danger">Belum diisi</span>
              </template>
            </Column>
            <Column field="lokasi" header="Lokasi" :sortable="true">
              <template #body="slotProps">
                <span v-if="slotProps.data.lokasi" class="ik-location-tag">
                  <i class="iconify" data-icon="feather:map-pin"></i>
                  {{ slotProps.data.lokasi }}
                </span>
                <span v-else class="has-text-danger">Belum diisi</span>
              </template>
            </Column>
            <Column field="jumlahversi" header="File IK" :sortable="true">
              <template #body="slotProps">
                <button
                  v-if="slotProps.data.jumlahversi"
                  type="button"
                  class="ik-file-link"
                  @click="viewInstruction(slotProps.data)"
                >
                  <i class="iconify" :data-icon="fileIcon(slotProps.data.namafileterbaru)"></i>
                  <span>Versi {{ slotProps.data.versiterbaru }}</span>
                  <small>{{ slotProps.data.jumlahversi }} riwayat</small>
                </button>
                <span v-else class="ik-no-file">Belum ada file</span>
              </template>
            </Column>
            <Column field="status" header="Status" :sortable="true">
              <template #body="slotProps">
                <VTag :color="slotProps.data.status_c" :label="slotProps.data.status" rounded />
              </template>
            </Column>
            <Column :exportable="false" header="Aksi" style="min-width: 10.5rem">
              <template #body="slotProps">
                <div class="ik-actions">
                  <VIconButton
                    type="button"
                    icon="feather:eye"
                    color="primary"
                    circle
                    outlined
                    :disabled="!slotProps.data.jumlahversi"
                    v-tooltip.top="viewTooltip(slotProps.data)"
                    @click="viewInstruction(slotProps.data)"
                  />
                  <VIconButton
                    type="button"
                    icon="pi pi-pencil"
                    color="info"
                    circle
                    outlined
                    v-tooltip.top="'Edit / upload versi baru'"
                    @click="edit(slotProps.data)"
                  />
                  <VIconButton
                    type="button"
                    icon="fas fa-trash"
                    color="danger"
                    circle
                    outlined
                    v-tooltip.top="'Nonaktifkan'"
                    @click="deleterow(slotProps.data)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>

        <div v-else class="columns is-multiline">
          <div v-for="row in dataSourcefiltered" :key="row.id" class="column is-6">
            <article class="ik-grid-card">
              <div class="ik-grid-top">
                <span class="ik-document-icon"><i class="iconify" :data-icon="fileIcon(row.namafileterbaru)"></i></span>
                <VTag :color="row.status_c" :label="row.status" rounded />
              </div>
              <strong>{{ row.noisntruksikerja }}</strong>
              <h4>{{ row.namainstruksikerja }}</h4>
              <div class="ik-grid-tags">
                <span class="ik-scope-tag">{{ row.lingkupkalibrasi || 'Lingkup belum diisi' }}</span>
                <span class="ik-location-tag">
                  <i class="iconify" data-icon="feather:map-pin"></i>
                  {{ row.lokasi || 'Lokasi belum diisi' }}
                </span>
              </div>
              <div class="ik-grid-meta">
                <span>{{ row.jumlahversi }} versi file</span>
                <span>{{ row.namafileterbaru || 'Belum ada file' }}</span>
              </div>
              <div class="ik-grid-actions">
                <VButton
                  type="button"
                  size="small"
                  icon="feather:eye"
                  color="primary"
                  outlined
                  :disabled="!row.jumlahversi"
                  @click="viewInstruction(row)"
                >Lihat</VButton>
                <VButton type="button" size="small" icon="feather:edit" color="info" outlined @click="edit(row)">
                  Edit
                </VButton>
              </div>
            </article>
          </div>
        </div>
      </div>

      <div class="column is-4-widescreen is-5-desktop is-12-tablet">
        <VCard class="ik-form-card">
          <div class="ik-form-heading">
            <span class="ik-form-icon"><i class="iconify" :data-icon="item.id ? 'feather:edit-3' : 'feather:plus'"></i></span>
            <div>
              <h3 class="title is-6 mb-1">{{ item.id ? 'Update Instruksi Kerja' : 'Tambah Instruksi Kerja' }}</h3>
              <p>{{ item.id ? 'File baru akan menjadi versi berikutnya.' : 'Semua isian bertanda * wajib diisi.' }}</p>
            </div>
          </div>

          <div class="columns is-multiline mb-0">
            <div class="column is-12">
              <VField label="No. Instruksi Kerja *">
                <VControl icon="feather:bookmark">
                  <VInput
                    v-model="item.noisntruksikerja"
                    type="text"
                    class="is-rounded"
                    placeholder="NO. INSTRUKSI KERJA"
                    autocomplete="off"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField label="Nama Instruksi Kerja *">
                <VControl icon="feather:file-text">
                  <VInput
                    v-model="item.namainstruksikerja"
                    type="text"
                    class="is-rounded uppercase"
                    placeholder="NAMA INSTRUKSI KERJA"
                    autocomplete="off"
                    @update:modelValue="value => (item.namainstruksikerja = toUp(value))"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField label="Lingkup *">
                <VControl>
                  <AutoComplete
                    v-model="item.lingkupfk"
                    :suggestions="scopeOptions"
                    optionLabel="label"
                    field="label"
                    :dropdown="true"
                    :minLength="0"
                    class="is-input"
                    placeholder="Pilih lingkup IK"
                    @complete="fetchLingkup"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField label="Lokasi *">
                <VControl>
                  <Dropdown
                    v-model="item.lokasifk"
                    :options="locationOptions"
                    optionLabel="label"
                    placeholder="Pilih Jakarta atau Gresik"
                    class="is-rounded is-fullwidth"
                  />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField :label="item.id ? 'Upload versi baru (opsional)' : 'File IK *'">
                <FileUpload
                  :key="fileInputKey"
                  ref="fileUploadRef"
                  name="fileIK"
                  mode="advanced"
                  accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                  :multiple="false"
                  :maxFileSize="31457280"
                  :showUploadButton="false"
                  :showCancelButton="false"
                  :customUpload="true"
                  invalidFileTypeMessage="{0}: format file harus PDF, DOC, atau DOCX."
                  invalidFileSizeMessage="{0}: ukuran maksimal file adalah 30 MB."
                  class="ik-file-upload"
                  @select="onSelectedFile"
                  @remove="onRemovedFile"
                  @clear="onClearedFiles"
                >
                  <template #header="{ chooseCallback, clearCallback, files }">
                    <div class="ik-upload-toolbar">
                      <Button
                        type="button"
                        label="Pilih file"
                        icon="pi pi-upload"
                        severity="info"
                        outlined
                        @click="chooseCallback()"
                      />
                      <span class="ik-upload-types">PDF, DOC, atau DOCX · maksimal 30 MB</span>
                      <Button
                        v-if="files && files.length"
                        type="button"
                        icon="pi pi-times"
                        severity="danger"
                        rounded
                        outlined
                        aria-label="Hapus file terpilih"
                        @click="clearSelectedUpload(clearCallback)"
                      />
                    </div>
                  </template>
                  <template #content="{ files, removeFileCallback }">
                    <div v-if="files && files.length" class="ik-upload-selected">
                      <span class="ik-upload-file-icon">
                        <i class="iconify" :data-icon="fileIcon(files[0].name)"></i>
                      </span>
                      <div class="ik-upload-file-main">
                        <strong>{{ files[0].name }}</strong>
                        <span>{{ formatFileSize(files[0].size) }} · Siap disimpan</span>
                      </div>
                      <Button
                        type="button"
                        icon="pi pi-times"
                        severity="danger"
                        rounded
                        text
                        aria-label="Batalkan file"
                        @click="removeSelectedUpload(removeFileCallback)"
                      />
                    </div>
                    <div v-else class="ik-upload-empty">
                      <i class="iconify" data-icon="feather:upload-cloud"></i>
                      <strong>Seret dan lepaskan file di sini</strong>
                      <span>atau klik tombol “Pilih file” di atas</span>
                    </div>
                  </template>
                </FileUpload>
              </VField>
              <div v-if="item.id && item.jumlahversi" class="ik-current-file">
                <i class="iconify" data-icon="feather:clock"></i>
                <span>Versi tersimpan: <strong>{{ item.jumlahversi }}</strong></span>
                <button type="button" @click="viewInstruction(item)">Lihat riwayat</button>
              </div>
            </div>
            <div v-if="item.id" class="column is-12">
              <VField label="Status">
                <VControl>
                  <VSwitchBlock v-model="item.statusenabled" label="Aktif" color="success" />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VButton
                type="button"
                :icon="item.id ? 'feather:edit' : 'feather:save'"
                :color="item.id ? 'info' : 'success'"
                class="is-fullwidth"
                :loading="isSaving"
                raised
                @click="save"
              >
                {{ item.id ? 'Update Data' : 'Simpan Data' }}
              </VButton>
              <VButton
                v-if="item.id"
                type="button"
                icon="feather:x-circle"
                class="is-fullwidth is-outlined is-warning mt-3"
                raised
                @click="clear"
              >
                Batal Edit
              </VButton>
            </div>
          </div>
        </VCard>
      </div>
    </div>

    <Dialog
      v-model:visible="historyDialog.visible"
      modal
      header="Riwayat File Instruksi Kerja"
      :style="{ width: 'min(760px, 95vw)' }"
    >
      <div class="ik-history-head">
        <span>{{ historyDialog.instruction?.noisntruksikerja }}</span>
        <strong>{{ historyDialog.instruction?.namainstruksikerja }}</strong>
        <small>Pilih versi yang ingin dibuka. Seluruh file lama tetap tersimpan.</small>
      </div>
      <div v-if="historyDialog.loading" class="ik-history-loading">
        <i class="iconify" data-icon="line-md:loading-twotone-loop"></i> Memuat riwayat...
      </div>
      <div v-else class="ik-history-list">
        <article v-for="version in historyDialog.versions" :key="version.id" class="ik-history-item">
          <span class="ik-version-icon"><i class="iconify" :data-icon="fileIcon(version.namaasli)"></i></span>
          <div class="ik-version-main">
            <div>
              <strong>Versi {{ version.versi }}</strong>
              <VTag v-if="version.versi === latestHistoryVersion" color="success" label="Terbaru" rounded />
            </div>
            <span>{{ version.namaasli }}</span>
            <small>
              {{ formatDate(version.created_at) }} · {{ formatFileSize(version.ukuran) }}
              <template v-if="version.namapetugas"> · {{ version.namapetugas }}</template>
            </small>
          </div>
          <div class="ik-version-actions">
            <VButton type="button" size="small" icon="feather:eye" color="primary" @click="openVersion(version.id)">
              Lihat
            </VButton>
            <VIconButton
              type="button"
              icon="feather:download"
              circle
              outlined
              v-tooltip.top="'Download versi ini'"
              @click="downloadVersion(version.id)"
            />
          </div>
        </article>
      </div>
    </Dialog>
  </VCard>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useHead } from '@vueuse/head'
import AutoComplete from 'primevue/autocomplete'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import FileUpload from 'primevue/fileupload'
import * as XLSX from 'xlsx-js-style'
import { notifyWorksheetInstructionsChanged } from '/@src/composable/useWorksheetInstructions'
import { useApi } from '/@src/composable/useApi'
import { useToaster } from '/@src/composable/toaster'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'

useHead({
  title: 'Master Instruksi Kerja - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const toUp = (value: unknown) => (value ?? '').toString().toLocaleUpperCase('id-ID')
const emptyItem = () => ({
  id: '',
  noisntruksikerja: '',
  namainstruksikerja: '',
  lingkupfk: null as any,
  lokasifk: null as any,
  statusenabled: true,
  jumlahversi: 0,
  fileterbaruid: null as number | null,
  namafileterbaru: '',
})

const api = useApi()
const item = ref<any>(emptyItem())
const dataSource = ref<any[]>([])
const scopeOptions = ref<any[]>([])
const locationOptions = ref<any[]>([])
const selectedFile = ref<File | null>(null)
const fileUploadRef = ref<any>(null)
const fileInputKey = ref(0)
const isLoading = ref(false)
const isSaving = ref(false)
const selectView = ref('list')
const filters = ref({
  keyword: '',
  number: '',
  name: '',
  scope: null as any,
  location: '',
  status: 'true',
  fileState: '',
  versionState: '',
})
const historyDialog = ref<any>({
  visible: false,
  loading: false,
  instruction: null,
  versions: [],
})

const statusOptions = [
  { label: 'Semua status', value: '' },
  { label: 'Aktif', value: 'true' },
  { label: 'Nonaktif', value: 'false' },
]
const locationFilterOptions = computed(() => [
  { label: 'Semua lokasi', value: '' },
  ...locationOptions.value,
  { label: 'Belum dimapping', value: 'unmapped' },
])
const fileOptions = [
  { label: 'Semua data', value: '' },
  { label: 'Sudah ada file', value: 'ada' },
  { label: 'Belum ada file', value: 'kosong' },
]
const versionOptions = [
  { label: 'Semua versi', value: '' },
  { label: 'Tepat 1 versi', value: 'single' },
  { label: 'Lebih dari 1 versi', value: 'multiple' },
  { label: 'Tanpa versi', value: 'none' },
]
const viewOptions = [
  { label: 'List View', value: 'list' },
  { label: 'Grid View', value: 'grid' },
]

const dataSourcefiltered = computed(() => {
  const keyword = toUp(filters.value.keyword).trim()
  const number = toUp(filters.value.number).trim()
  const name = toUp(filters.value.name).trim()
  const scopeId = filters.value.scope?.value
  const locationId = filters.value.location

  return dataSource.value
    .filter((row) => {
      if (keyword && ![
        row.noisntruksikerja,
        row.namainstruksikerja,
        row.lingkupkalibrasi,
        row.lokasi,
        row.namafileterbaru,
        row.status,
      ].some((value) => toUp(value).includes(keyword))) return false
      if (number && !toUp(row.noisntruksikerja).includes(number)) return false
      if (name && !toUp(row.namainstruksikerja).includes(name)) return false
      if (scopeId && Number(row.lingkupfk) !== Number(scopeId)) return false
      if (locationId === 'unmapped' && row.lokasifk) return false
      if (locationId && locationId !== 'unmapped' && Number(row.lokasifk) !== Number(locationId)) return false
      if (filters.value.status !== '' && String(Boolean(row.statusenabled)) !== filters.value.status) return false
      if (filters.value.fileState === 'ada' && Number(row.jumlahversi) < 1) return false
      if (filters.value.fileState === 'kosong' && Number(row.jumlahversi) > 0) return false
      if (filters.value.versionState === 'single' && Number(row.jumlahversi) !== 1) return false
      if (filters.value.versionState === 'multiple' && Number(row.jumlahversi) <= 1) return false
      if (filters.value.versionState === 'none' && Number(row.jumlahversi) !== 0) return false
      return true
    })
    .map((row, index) => ({ ...row, no: index + 1 }))
})

const totalFiles = computed(() => dataSource.value.reduce((sum, row) => sum + Number(row.jumlahversi || 0), 0))
const latestHistoryVersion = computed(() => Math.max(0, ...historyDialog.value.versions.map((row: any) => Number(row.versi))))

async function fetchData() {
  isLoading.value = true
  try {
    const response: any = await api.get('/sysadmin/master-instruksi-kerja')
    dataSource.value = Array.isArray(response?.data) ? response.data : []
  } finally {
    isLoading.value = false
  }
}

async function fetchLingkup(event: any = {}) {
  const query = encodeURIComponent(event?.query || '')
  try {
    const response: any = await api.get(
      `general/dropdown/lingkupkalibrasi_m?select=id,lingkupkalibrasi&param_search=lingkupkalibrasi&query=${query}&limit=100`
    )
    scopeOptions.value = Array.isArray(response) ? response : []
  } catch {
    scopeOptions.value = []
  }
}

async function fetchLokasi() {
  try {
    const response: any = await api.get(
      'general/dropdown/lokasikalibrasi_m?select=id,lokasi&param_search=lokasi&query=&limit=10'
    )
    locationOptions.value = (Array.isArray(response) ? response : []).filter((option: any) =>
      ['JAKARTA', 'GRESIK'].includes(toUp(option.label))
    )
  } catch {
    locationOptions.value = []
  }
}

function onSelectedFile(event: any) {
  const file = event?.files?.[0] || null
  if (!file) return

  const extension = file.name.split('.').pop()?.toLowerCase() || ''
  if (!['pdf', 'doc', 'docx'].includes(extension)) {
    useToaster().error('File IK hanya boleh berformat PDF, DOC, atau DOCX')
    selectedFile.value = null
    fileUploadRef.value?.clear()
    return
  }
  if (file.size > 30 * 1024 * 1024) {
    useToaster().error('Ukuran file IK maksimal 30 MB')
    selectedFile.value = null
    fileUploadRef.value?.clear()
    return
  }
  selectedFile.value = file
}

function onRemovedFile() {
  selectedFile.value = null
}

function onClearedFiles() {
  selectedFile.value = null
}

function clearSelectedUpload(clearCallback: () => void) {
  clearCallback()
  selectedFile.value = null
}

function removeSelectedUpload(removeFileCallback: (index: number) => void) {
  removeFileCallback(0)
  selectedFile.value = null
}

function edit(row: any) {
  item.value = {
    ...emptyItem(),
    ...row,
    noisntruksikerja: row.noisntruksikerja || '',
    namainstruksikerja: toUp(row.namainstruksikerja),
    lingkupfk: row.lingkupfk
      ? { value: row.lingkupfk, label: row.lingkupkalibrasi || '' }
      : null,
    lokasifk: row.lokasifk
      ? { value: row.lokasifk, label: row.lokasi || '' }
      : null,
    statusenabled: Boolean(row.statusenabled),
  }
  selectedFile.value = null
  fileInputKey.value += 1
}

async function save() {
  if (!item.value.noisntruksikerja?.trim()) {
    useToaster().error('No Instruksi Kerja wajib diisi')
    return
  }
  if (!item.value.namainstruksikerja?.trim()) {
    useToaster().error('Nama Instruksi Kerja wajib diisi')
    return
  }
  if (!item.value.lingkupfk?.value) {
    useToaster().error('Lingkup wajib dipilih')
    return
  }
  if (!item.value.lokasifk?.value) {
    useToaster().error('Lokasi wajib dipilih')
    return
  }
  if (!item.value.id && !selectedFile.value) {
    useToaster().error('File IK wajib diupload saat menambah data')
    return
  }

  const payload = new FormData()
  payload.append('datainstruksikereja', JSON.stringify({
    id: item.value.id || '',
    noisntruksikerja: item.value.noisntruksikerja.trim(),
    namainstruksikerja: toUp(item.value.namainstruksikerja).trim(),
    lingkupfk: item.value.lingkupfk.value,
    lokasifk: item.value.lokasifk.value,
    statusenabled: item.value.statusenabled,
  }))
  if (selectedFile.value) payload.append('file', selectedFile.value)

  const editedInstructionId = item.value.id ? Number(item.value.id) : null
  const uploadedNewVersion = Boolean(editedInstructionId && selectedFile.value)

  isSaving.value = true
  try {
    const result = await api.post('/sysadmin/save-instruksi-kerja', payload)
    if (!result) return
    notifyWorksheetInstructionsChanged()
    clear()
    await fetchData()

    // Sesudah upload revisi, langsung tampilkan versi lama dan versi terbaru.
    if (uploadedNewVersion) {
      const updatedInstruction = dataSource.value.find(
        (row: any) => Number(row.id) === editedInstructionId
      )
      if (updatedInstruction && Number(updatedInstruction.jumlahversi) > 1) {
        await viewInstruction(updatedInstruction)
      }
    }
  } finally {
    isSaving.value = false
  }
}

async function deleterow(row: any) {
  await api.post('/sysadmin/delete-instruksi-kerja', { id: row.id })
  notifyWorksheetInstructionsChanged()
  await fetchData()
}

function clear() {
  item.value = emptyItem()
  selectedFile.value = null
  fileInputKey.value += 1
}

function clearFilter() {
  filters.value = {
    keyword: '',
    number: '',
    name: '',
    scope: null,
    location: '',
    status: 'true',
    fileState: '',
    versionState: '',
  }
}

function exportExcel() {
  const rows = dataSourcefiltered.value
  if (!rows.length) {
    useToaster().error('Tidak ada data sesuai filter untuk diexport')
    return
  }

  const filterDescriptions = [
    filters.value.keyword && `Pencarian: ${filters.value.keyword}`,
    filters.value.number && `No. IK: ${filters.value.number}`,
    filters.value.name && `Nama IK: ${filters.value.name}`,
    filters.value.scope?.label && `Lingkup: ${filters.value.scope.label}`,
    filters.value.location && `Lokasi: ${locationFilterOptions.value.find(option => option.value === filters.value.location)?.label}`,
    filters.value.status !== '' && `Status: ${statusOptions.find(option => option.value === filters.value.status)?.label}`,
    filters.value.fileState && `File: ${fileOptions.find(option => option.value === filters.value.fileState)?.label}`,
    filters.value.versionState && `Versi: ${versionOptions.find(option => option.value === filters.value.versionState)?.label}`,
  ].filter(Boolean)

  const headers = [
    'NO',
    'NO. INSTRUKSI KERJA',
    'NAMA INSTRUKSI KERJA',
    'LINGKUP',
    'LOKASI',
    'STATUS',
    'JUMLAH VERSI',
    'VERSI TERBARU',
    'FILE TERBARU',
    'TANGGAL UPLOAD TERBARU',
    'STATUS SINKRON UDS',
  ]
  const dataRows = rows.map((row: any, index: number) => [
    index + 1,
    row.noisntruksikerja || '',
    row.namainstruksikerja || '',
    row.lingkupkalibrasi || 'Belum diisi',
    row.lokasi || 'Belum diisi',
    row.status || '',
    Number(row.jumlahversi || 0),
    row.versiterbaru || '',
    row.namafileterbaru || '',
    row.tanggaluploadterbaru ? formatDate(row.tanggaluploadterbaru) : '',
    row.udrrootfk ? 'Tersinkron' : 'Belum tersinkron',
  ])
  const generatedAt = new Date()
  const worksheet = XLSX.utils.aoa_to_sheet([
    ['MASTER INSTRUKSI KERJA (IK)'],
    [`Diexport pada ${generatedAt.toLocaleString('id-ID')}`],
    [`Filter: ${filterDescriptions.length ? filterDescriptions.join(' | ') : 'Semua data'}`],
    [],
    headers,
    ...dataRows,
  ])

  worksheet['!merges'] = [
    { s: { r: 0, c: 0 }, e: { r: 0, c: headers.length - 1 } },
    { s: { r: 1, c: 0 }, e: { r: 1, c: headers.length - 1 } },
    { s: { r: 2, c: 0 }, e: { r: 2, c: headers.length - 1 } },
  ]
  worksheet['!cols'] = [
    { wch: 6 },
    { wch: 30 },
    { wch: 60 },
    { wch: 23 },
    { wch: 14 },
    { wch: 12 },
    { wch: 14 },
    { wch: 14 },
    { wch: 42 },
    { wch: 25 },
    { wch: 22 },
  ]
  worksheet['!autofilter'] = {
    ref: `A5:${XLSX.utils.encode_col(headers.length - 1)}${dataRows.length + 5}`,
  }

  for (let column = 0; column < headers.length; column++) {
    const titleCell: any = worksheet[XLSX.utils.encode_cell({ r: 0, c: column })]
    if (titleCell) {
      titleCell.s = {
        font: { bold: true, color: { rgb: 'FFFFFF' }, sz: 15 },
        fill: { fgColor: { rgb: '178F56' } },
        alignment: { horizontal: 'center', vertical: 'center' },
      }
    }

    const headerCell: any = worksheet[XLSX.utils.encode_cell({ r: 4, c: column })]
    if (headerCell) {
      headerCell.s = {
        font: { bold: true, color: { rgb: 'FFFFFF' } },
        fill: { fgColor: { rgb: '1F4E78' } },
        alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
        border: {
          top: { style: 'thin', color: { rgb: 'B4C6E7' } },
          bottom: { style: 'thin', color: { rgb: 'B4C6E7' } },
          left: { style: 'thin', color: { rgb: 'B4C6E7' } },
          right: { style: 'thin', color: { rgb: 'B4C6E7' } },
        },
      }
    }
  }

  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Instruksi Kerja')
  const fileDate = `${generatedAt.getFullYear()}-${String(generatedAt.getMonth() + 1).padStart(2, '0')}-${String(generatedAt.getDate()).padStart(2, '0')}`
  XLSX.writeFile(workbook, `Master_Instruksi_Kerja_${fileDate}.xlsx`)
}

async function viewInstruction(row: any) {
  const count = Number(row.jumlahversi || 0)
  if (!count) {
    useToaster().error('Instruksi Kerja ini belum mempunyai file')
    return
  }
  if (count === 1 && row.fileterbaruid) {
    openVersion(row.fileterbaruid)
    return
  }

  historyDialog.value = {
    visible: true,
    loading: true,
    instruction: row,
    versions: [],
  }
  try {
    const response: any = await api.get(`/sysadmin/history-instruksi-kerja?id=${row.id}`)
    historyDialog.value.instruction = response?.instruksikerja || row
    historyDialog.value.versions = response?.versions || []
  } finally {
    historyDialog.value.loading = false
  }
}

function openVersion(id: number) {
  H.printBlade(`sysadmin/open-instruksi-kerja-file?id=${id}`)
}

function downloadVersion(id: number) {
  H.printBlade(`sysadmin/download-instruksi-kerja-file?id=${id}`)
}

const viewTooltip = (row: any) => Number(row.jumlahversi) > 1 ? 'Pilih versi file' : 'Lihat file IK'
const fileIcon = (name: string) => {
  const extension = String(name || '').split('.').pop()?.toLowerCase()
  if (extension === 'pdf') return 'vscode-icons:file-type-pdf2'
  if (extension === 'doc' || extension === 'docx') return 'vscode-icons:file-type-word'
  return 'feather:file-text'
}
const formatFileSize = (bytes: number) => {
  const value = Number(bytes || 0)
  if (!value) return '-'
  if (value < 1024) return `${value} B`
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`
  return `${(value / 1024 / 1024).toFixed(1)} MB`
}
const formatDate = (value: string) => {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
}

fetchLingkup()
fetchLokasi()
fetchData()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';

.uppercase,
.uppercase input,
input.uppercase {
  text-transform: uppercase !important;
}

.ik-page {
  .p-dropdown,
  .p-autocomplete,
  .p-autocomplete-input {
    width: 100%;
  }
}

.ik-heading,
.ik-heading-summary,
.ik-filter-title,
.ik-form-heading,
.ik-grid-top,
.ik-grid-actions,
.ik-actions,
.ik-current-file,
.ik-history-item,
.ik-version-actions {
  display: flex;
  align-items: center;
}

.ik-heading {
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 20px;

  p {
    color: var(--light-text);
    font-size: .86rem;
  }
}

.ik-heading-summary {
  gap: 10px;

  span {
    padding: 8px 12px;
    border-radius: 10px;
    color: var(--light-text);
    background: var(--widget-grey);
    font-size: .75rem;
  }
}

.ik-filter-panel {
  margin-bottom: 20px;
  padding: 16px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 14px;
  background: var(--widget-grey);
}

.ik-filter-title {
  justify-content: space-between;
  margin-bottom: 8px;
  color: var(--dark-text);
  font-size: .84rem;
  font-weight: 650;

  span {
    display: inline-flex;
    align-items: center;
    gap: 7px;
  }

  button {
    border: 0;
    color: var(--primary);
    background: transparent;
    cursor: pointer;
    font: inherit;
  }
}

.ik-table-wrap {
  overflow: hidden;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 14px;
}

.ik-scope-tag,
.ik-location-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: .7rem;
  font-weight: 650;

  .iconify {
    width: 13px;
    height: 13px;
  }
}

.ik-scope-tag {
  color: var(--primary);
  background: var(--primary-light-48);
}

.ik-location-tag {
  color: #0288d1;
  background: rgba(3, 169, 244, .1);
}

.ik-grid-tags {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.ik-file-link {
  display: grid;
  grid-template-columns: 24px auto;
  gap: 0 7px;
  padding: 0;
  border: 0;
  color: var(--primary);
  background: transparent;
  text-align: left;
  cursor: pointer;

  .iconify {
    grid-row: 1 / 3;
    width: 23px;
    height: 23px;
  }

  span {
    font-size: .76rem;
    font-weight: 650;
  }

  small {
    color: var(--light-text);
    font-size: .66rem;
  }
}

.ik-no-file {
  color: var(--light-text);
  font-size: .72rem;
}

.ik-actions {
  gap: 7px;
}

.ik-grid-card {
  height: 100%;
  padding: 16px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 14px;
  background: var(--white);

  > strong {
    display: block;
    margin-top: 12px;
    color: var(--primary);
    font-size: .76rem;
  }

  h4 {
    min-height: 42px;
    margin: 4px 0 10px;
    color: var(--dark-text);
    font-size: .9rem;
    font-weight: 650;
  }
}

.ik-grid-top {
  justify-content: space-between;
}

.ik-document-icon,
.ik-form-icon,
.ik-version-icon {
  display: grid;
  place-items: center;
  border-radius: 10px;
  color: var(--primary);
  background: var(--primary-light-48);
}

.ik-document-icon {
  width: 42px;
  height: 42px;

  .iconify {
    width: 27px;
    height: 27px;
  }
}

.ik-grid-meta {
  display: flex;
  margin: 13px 0;
  flex-direction: column;
  color: var(--light-text);
  font-size: .7rem;
}

.ik-grid-actions {
  gap: 8px;
}

.ik-form-card {
  position: sticky;
  top: 88px;
}

.ik-form-heading {
  gap: 11px;
  margin-bottom: 18px;

  p {
    color: var(--light-text);
    font-size: .72rem;
  }
}

.ik-form-icon {
  width: 38px;
  height: 38px;
  flex: 0 0 38px;
}

.ik-file-upload {
  width: 100%;
  overflow: hidden;
  border-radius: 12px;

  .p-fileupload-buttonbar {
    padding: 10px 12px;
    border-color: var(--fade-grey-dark-3);
    background: var(--widget-grey);
  }

  .p-fileupload-content {
    min-height: 148px;
    padding: 14px;
    overflow: hidden;
    border-color: var(--fade-grey-dark-3);
    background: var(--white);
    transition: border-color .2s ease, background .2s ease;

    &.p-fileupload-highlight {
      border-color: var(--primary);
      background: var(--primary-light-48);
    }
  }
}

.ik-upload-toolbar {
  display: flex;
  width: 100%;
  min-width: 0;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;

  .p-button:last-child {
    margin-left: auto;
  }
}

.ik-upload-types {
  min-width: 0;
  color: var(--light-text);
  font-size: .68rem;
  line-height: 1.35;
  white-space: normal;
}

.ik-upload-empty {
  display: flex;
  width: 100%;
  min-height: 118px;
  align-items: center;
  justify-content: center;
  padding: 12px;
  border: 1.5px dashed var(--primary);
  border-radius: 10px;
  color: var(--primary);
  background: var(--primary-light-48);
  flex-direction: column;
  text-align: center;

  .iconify {
    width: 38px;
    height: 38px;
    margin-bottom: 7px;
  }

  strong {
    color: var(--dark-text);
    font-size: .82rem;
    white-space: normal;
  }

  span {
    margin-top: 3px;
    color: var(--light-text);
    font-size: .7rem;
    white-space: normal;
  }
}

.ik-upload-selected {
  display: flex;
  width: 100%;
  min-width: 0;
  min-height: 104px;
  align-items: center;
  gap: 11px;
  padding: 12px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 10px;
  background: var(--widget-grey);
}

.ik-upload-file-icon {
  display: grid;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  place-items: center;
  border-radius: 9px;
  background: var(--white);

  .iconify {
    width: 28px;
    height: 28px;
  }
}

.ik-upload-file-main {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;

  strong {
    overflow-wrap: anywhere;
    color: var(--dark-text);
    font-size: .78rem;
    line-height: 1.35;
  }

  span {
    margin-top: 4px;
    color: var(--light-text);
    font-size: .69rem;
  }
}

.ik-current-file {
  justify-content: space-between;
  gap: 7px;
  margin-top: 9px;
  color: var(--light-text);
  font-size: .72rem;

  button {
    margin-left: auto;
    border: 0;
    color: var(--primary);
    background: transparent;
    cursor: pointer;
    font: inherit;
    font-weight: 650;
  }
}

.ik-history-head {
  display: flex;
  padding: 13px 15px;
  border-radius: 12px;
  background: var(--widget-grey);
  flex-direction: column;

  span,
  small {
    color: var(--light-text);
    font-size: .72rem;
  }

  strong {
    margin: 2px 0;
    color: var(--dark-text);
    font-size: .9rem;
  }
}

.ik-history-loading {
  padding: 36px;
  color: var(--light-text);
  text-align: center;

  .iconify {
    margin-right: 7px;
  }
}

.ik-history-list {
  max-height: 470px;
  margin-top: 12px;
  overflow-y: auto;
}

.ik-history-item {
  gap: 12px;
  padding: 12px 4px;
  border-bottom: 1px solid var(--fade-grey-dark-3);
}

.ik-version-icon {
  width: 42px;
  height: 42px;
  flex: 0 0 42px;

  .iconify {
    width: 27px;
    height: 27px;
  }
}

.ik-version-main {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;

  > div {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  > span {
    overflow: hidden;
    color: var(--dark-text);
    font-size: .78rem;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  small {
    margin-top: 3px;
    color: var(--light-text);
    font-size: .68rem;
  }
}

.ik-version-actions {
  gap: 7px;
}

.is-dark {
  .ik-filter-panel,
  .ik-grid-card,
  .ik-history-head {
    background: var(--dark-sidebar-light-4);
  }
}

@media only screen and (max-width: 768px) {
  .ik-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .ik-heading-summary {
    align-items: stretch;
    flex-wrap: wrap;
  }

  .ik-form-card {
    position: static;
  }

  .ik-history-item {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .ik-version-actions {
    width: 100%;
    padding-left: 54px;
  }
}
</style>
