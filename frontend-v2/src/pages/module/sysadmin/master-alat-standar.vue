<template>
  <VCard>
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Alat Standar</h3>
    </div> 
    <div class="columns is-multiline">
      <div class="column is-8">
        <div class="user-grid-toolbar alat-standar-toolbar">
          <div class="alat-standar-filters">
            <!-- Pencarian umum -->
            <VControl icon="feather:search" class="alat-standar-search">
              <input v-model="filters" @input="filters = toUp(filters)" class="input custom-text-filter uppercase"
                placeholder="Cari Data..." />
            </VControl>

            <!-- Pilihan otomatis diambil dari data alat standar yang sudah dimuat -->
            <div class="select is-fullwidth alat-standar-select">
              <select v-model="filterLingkup" aria-label="Filter Lingkup">
                <option value="">Semua Lingkup</option>
                <option v-for="lingkup in lingkupOptions" :key="lingkup" :value="lingkup">
                  {{ lingkup }}
                </option>
              </select>
            </div>

            <div class="select is-fullwidth alat-standar-select">
              <select v-model="filterStandarMilik" aria-label="Filter Standar Milik">
                <option value="">Semua Standar Milik</option>
                <option v-for="standar in standarMilikOptions" :key="standar" :value="standar">
                  {{ standar }}
                </option>
              </select>
            </div>
          </div>

          <div class="buttons alat-standar-actions">

            <VField v-slot="{ id }" class="is-icon-select">
              <VControl>
                <Multiselect v-model="selectView" :attrs="{ id }" placeholder="Select View" label="name"
                  :options="d_View" :searchable="true" track-by="name" mode="single" @select="changeView"
                  autocomplete="off">
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

            <VControl class="is-pulled-right">
              <VSwitchBlock v-model="item.aktif" label="Aktif" color="danger" @change="changeSwitch(item.aktif)" />
            </VControl>
          </div>
        </div>

        <div class="user-grid user-grid-v2" v-if="selectView === 'list'">
          <DataTable :value="dataSourcefiltered" class="p-datatable-sm" :paginator="true" :rows="15"
            :rowsPerPageOptions="[5, 10, 15, 25]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">
            <Column field="no" header="No"></Column>
            <Column field="namaalatstandar" header="Nama ALat Standar" :sortable="true"></Column>
            <Column field="namamerk" header="Merk" :sortable="true"></Column>
            <Column field="namatipe" header="Tipe" :sortable="true"></Column>
            <Column field="namaserialnumber" header="SN" :sortable="true"></Column>
            <Column field="namaperusahaan" header="Standar Milik" :sortable="true"></Column>
            <Column field="lingkupkalibrasi" header="Lingkup" :sortable="true"></Column>
            <Column field="calldate" header="Cal Date" :sortable="true"></Column>
            <Column field="duedate" header="Due Date" :sortable="true"></Column>
            <Column field="pengingat_rekalibrasi_aktif" header="Pengingat" :sortable="true">
              <template #body="slotProps">
                <VTag :color="slotProps.data.pengingat_rekalibrasi_aktif ? 'success' : 'danger'"
                  :label="slotProps.data.pengingat_rekalibrasi_aktif ? 'Aktif' : 'Dimatikan'" />
              </template>
            </Column>
            <Column field="status" header="Status"></Column>
            <Column :exportable="false" header="Action">
              <template #body="slotProps">
                <VIconButton type="button" icon="feather:upload-cloud" class="mr-3" color="success" circle outlined
                  v-tooltip.top="'Upload Sertifikat Standar'" @click="openUploadSertifikat(slotProps.data)" />
                <VIconButton type="button" icon="pi pi-clock" class="mr-3" color="warning" circle outlined raised
                  v-tooltip.top="'Riwayat Rekalibrasi'" @click="riwayatAlat(slotProps.data)" />
                <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined raised
                  v-tooltip.top="'Edit'" @click="edit(slotProps.data)" />
                <VIconButton type="button" icon="fas fa-trash" class="mr-3" color="danger" circle outlined raised
                  v-tooltip.top="'Hapus'" @click="deleterow(slotProps.data)" />
              </template>
            </Column>
          </DataTable>
        </div>

        <div class="tile-grid tile-grid-v1" v-else-if="selectView === 'grid'">
          <TransitionGroup name="list" tag="div" class="columns is-multiline">
            <!--Grid item-->
            <div v-for="(gItem, key) in dataSourcefiltered" :key="key" class="column is-6">
              <div class="tile-grid-item">
                <div class="tile-grid-item-inner">
                  <VAvatar size="medium" picture="/images/avatars/svg/gender.svg" color="primary" squared bordered />
                  <div class="meta">
                    <span class="dark-inverted">{{ gItem.namaalatstandar }}</span>
                    <span>{{ gItem.namatipe }} - {{ gItem.lingkupkalibrasi || '-' }}</span>
                  </div>
                  <VTag :color="gItem.status_c" :label="gItem.status" style="margin-left:90px" />
                  <VDropdown icon="feather:more-vertical" spaced right>
                    <template #content>
                      <a role="menuitem" class="dropdown-item is-media" @click="openUploadSertifikat(gItem)">
                        <div class="icon">
                          <i class="iconify" data-icon="feather:upload-cloud" aria-hidden="true"></i>
                        </div>
                        <div class="meta">
                          <span>Upload Sertifikat</span>
                          <span>Catat rekalibrasi eksternal</span>
                        </div>
                      </a>
                      <a role="menuitem" class="dropdown-item is-media" @click="riwayatAlat(gItem)">
                        <div class="icon">
                          <i class="iconify" data-icon="feather:clock" aria-hidden="true"></i>
                        </div>
                        <div class="meta">
                          <span>Riwayat</span>
                          <span>Riwayat Rekalibrasi </span>
                        </div>
                      </a>
                      <a role="menuitem" class="dropdown-item is-media" @click="edit(gItem)">
                        <div class="icon">
                          <i class="iconify" data-icon="feather:edit" aria-hidden="true"></i>
                        </div>
                        <div class="meta">
                          <span>Edit</span>
                          <span>Untuk merubah data </span>
                        </div>
                      </a>
                      <a role="menuitem" class="dropdown-item is-media" @click="deleterow(gItem)">
                        <div class="icon">
                          <i aria-hidden="true" class="lnil lnil-trash-can-alt"></i>
                        </div>
                        <div class="meta">
                          <span>Remove</span>
                          <span>Hapus Data dari Daftar</span>
                        </div>
                      </a>
                    </template>
                  </VDropdown>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>
      </div>

      <!-- Form kanan -->
      <div class="column is-4">
        <img src="/images/avatars/label/switches.svg" alt="" srcset=""
          style="max-width:60%; margin-top: -4rem; margin-left: 10rem;" />
        <VCard>
          <div class="columns is-multiline">
            <div class="column is-6">
              <h3 class="title is-6 mb-2 mr-1">
                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                Tambah Data
              </h3>
            </div>

            <div class="column is-12">
              <VField label="Nama Alat Standar">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.namaalatstandar"
                    @update:modelValue="v => (item.namaalatstandar = toUp(v))" placeholder="Nama Alat Standar"
                    class="is-rounded uppercase" autocomplete="off" />
                </VControl>
              </VField>
            </div>

            <div class="column is-12">
              <VField label="Merk Alat Standar">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.namamerk" @update:modelValue="v => (item.namamerk = toUp(v))"
                    placeholder="Merk Alat Standar" class="is-rounded uppercase" autocomplete="off" />
                </VControl>
              </VField>
            </div>

            <div class="column is-12">
              <VField label="Tipe Alat Standar">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.namatipe" @update:modelValue="v => (item.namatipe = toUp(v))"
                    placeholder="Tipe Alat Standar" class="is-rounded uppercase" autocomplete="off" />
                </VControl>
              </VField>
            </div>

            <div class="column is-12">
              <VField label="SN Alat Standar">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.namaserialnumber"
                    @update:modelValue="v => (item.namaserialnumber = toUp(v))" placeholder="SN Alat Standar"
                    class="is-rounded uppercase" autocomplete="off" />
                </VControl>
              </VField>
            </div>

            <div class="column is-12">
              <VField label="Standar Milik">
                <VControl>
                  <AutoComplete v-model="item.standarmilikfk" :suggestions="d_ulab" @complete="fetchulab($event)"
                    :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                    :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
                </VControl>
              </VField>
            </div>

            <div class="column is-12">
              <VField label="Lingkup">
                <VControl>
                  <AutoComplete v-model="item.lingkupfk" :suggestions="d_lingkup" @complete="fetchLingkup($event)"
                    :optionLabel="'label'" :dropdown="true" :minLength="1" class="is-input" :appendTo="'body'"
                    :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
                </VControl>
              </VField>
            </div>

            <div class="column is-12">
              <VField label="Cal Date">
                <VDatePicker v-model="item.calldate" mode="dateTime" style="width: 100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>

            <div class="column is-12">
              <VField label="Due Date">
                <VDatePicker v-model="item.duedate" mode="dateTime" style="width: 100%;">
                  <template #default="{ inputValue, inputEvents }">
                    <VField>
                      <VControl icon="feather:calendar" fullwidth>
                        <VInput :value="inputValue" v-on="inputEvents" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </VField>
            </div>


            <div class="column is-6" v-if="item.id">
              <VField label="Aktivasi" class="ml-4">
                <VControl class="is-pulled-right">
                  <VSwitchBlock v-model="item.statusenabled" color="danger" @change="changeSwitch(item.aktif)" />
                </VControl>
              </VField>
            </div>

            <div class="column is-6" v-if="item.id">
              <VField label="Pengingat Rekalibrasi" class="ml-4">
                <VControl class="is-pulled-right">
                  <VSwitchBlock v-model="item.pengingat_rekalibrasi_aktif" color="success" />
                </VControl>
              </VField>
              <p class="help ml-4">Otomatis aktif kembali saat Due Date diperbarui.</p>
            </div>

            <div v-if="item.id" class="column is-12">
              <VButton @click="save()" :loading="isLoadingTT" type="button" icon="feather:edit"
                class="is-fullwidth mr-3" color="info" raised>
                Update Data
              </VButton>
              <VButton @click="clear()" type="button" icon="feather:x-circle"
                class="is-fullwidth is-outlined is-warning mt-3" raised>
                Batal Edit
              </VButton>
            </div>

            <div v-else class="column is-12">
              <VButton @click="save()" :loading="isLoadingTT" type="button" icon="feather:save"
                class="is-fullwidth mr-3" color="success" raised>
                Simpan Data
              </VButton>
            </div>
          </div>
        </VCard>
      </div>
    </div>
  </VCard>

  <VModal :open="modalSertifikat" title="Upload Sertifikat Standar" size="medium" actions="right"
    class="certificate-upload-modal" noscroll @close="closeUploadSertifikat">
    <template #content>
      <div class="certificate-modal-content">
        <div class="certificate-tool-summary">
          <div class="certificate-tool-icon">
            <i class="iconify" data-icon="feather:tool" aria-hidden="true"></i>
          </div>
          <div>
            <p class="certificate-tool-name">{{ sertifikatForm.namaalatstandar || '-' }}</p>
            <p class="certificate-tool-meta">
              {{ sertifikatForm.namamerk || '-' }} / {{ sertifikatForm.namatipe || '-' }}
              <span>·</span> S/N {{ sertifikatForm.namaserialnumber || '-' }}
            </p>
          </div>
        </div>

        <div class="columns is-multiline certificate-fields">
          <div class="column is-6-desktop is-12-mobile">
            <VField label="Cal Date (Tanggal Kalibrasi)">
              <VDatePicker v-model="sertifikatForm.calldate" mode="dateTime" style="width: 100%;">
                <template #default="{ inputValue, inputEvents }">
                  <VControl icon="feather:calendar" fullwidth>
                    <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih Cal Date" />
                  </VControl>
                </template>
              </VDatePicker>
            </VField>
          </div>
          <div class="column is-6-desktop is-12-mobile">
            <VField label="Due Date">
              <VDatePicker v-model="sertifikatForm.duedate" mode="dateTime" style="width: 100%;">
                <template #default="{ inputValue, inputEvents }">
                  <VControl icon="feather:calendar" fullwidth>
                    <VInput :value="inputValue" v-on="inputEvents" placeholder="Pilih Due Date" />
                  </VControl>
                </template>
              </VDatePicker>
            </VField>
          </div>
        </div>

        <div class="columns is-multiline certificate-upload-grid">
        <div class="column is-12 certificate-upload-row">
          <VField label="File Sertifikat">
            <FileUpload :key="certificateInputKey" ref="certificateUploader" name="sertifikat" mode="advanced"
              class="certificate-prime-upload" accept="application/pdf,.pdf" :multiple="false"
              style="display: block; width: 100%; max-width: 100%;"
              :maxFileSize="10000000" :showUploadButton="false" :showCancelButton="false"
              :invalidFileTypeMessage="'{0}: hanya file PDF yang diperbolehkan.'"
              :invalidFileSizeMessage="'{0}: ukuran maksimal file adalah {1}.'" @select="onCertificateSelected">
              <template #header="{ chooseCallback, clearCallback, files }">
                <div class="certificate-upload-toolbar">
                  <div class="certificate-upload-actions">
                    <Button type="button" icon="pi pi-upload" label="Pilih PDF" outlined
                      @click="chooseCallback()" />
                    <Button type="button" icon="pi pi-times" label="Hapus" severity="danger" outlined
                      :disabled="!files || files.length === 0"
                      @click="clearCertificateSelection(clearCallback)" />
                  </div>
                  <span class="certificate-upload-limit">PDF · Maksimal 10 MB</span>
                </div>
              </template>

              <template #content="{ files, removeFileCallback }">
                <div v-if="files && files.length" class="certificate-selected-file">
                  <div class="certificate-selected-icon">
                    <i class="iconify" data-icon="feather:file-text" aria-hidden="true"></i>
                  </div>
                  <div class="certificate-selected-info">
                    <span class="certificate-selected-name">{{ files[0].name }}</span>
                    <span class="certificate-selected-size">{{ formatFileSize(files[0].size) }} · Siap diunggah</span>
                  </div>
                  <Button type="button" icon="pi pi-times" severity="danger" text rounded
                    aria-label="Hapus file" @click="removeCertificateFile(removeFileCallback, 0)" />
                </div>
              </template>

              <template #empty>
                <div class="certificate-upload-empty">
                  <i class="iconify" data-icon="feather:upload-cloud" aria-hidden="true"></i>
                  <p>Drag dan drop sertifikat PDF di sini</p>
                  <span>atau gunakan tombol Pilih PDF di atas</span>
                </div>
              </template>
            </FileUpload>
          </VField>
        </div>
        </div>
      </div>
    </template>
    <template #action>
      <VButton type="button" class="certificate-button" color="primary" icon="feather:upload-cloud"
        :loading="isUploadingCertificate" @click="uploadSertifikat">
        Upload Sertifikat
      </VButton>
    </template>
  </VModal>
</template>

<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import moment from 'moment'
import * as H from '/@src/utils/appHelper'
import AutoComplete from 'primevue/autocomplete';
import FileUpload from 'primevue/fileupload'
import Button from 'primevue/button'
import { useViewWrapper } from '/@src/stores/viewWrapper';

useHead({
  title: 'Alat Standar - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setFullWidth(true)

// === Util uppercase ===
const toUp = (v: unknown) => (v ?? '').toString().toUpperCase()
const toBool = (v: unknown) => v === true || v === 1 || v === '1' || v === 'true'

// === State ===
const item: any = ref({
  aktif: true,
  statusenabled: true,
  pengingat_rekalibrasi_aktif: true,
})

const modalInput = ref(false)
const modalDetail = ref(false)
const modalSertifikat = ref(false)
const isUploadingCertificate = ref(false)
const certificateInputKey = ref(0)
const certificateUploader = ref<any>(null)
const sertifikatForm: any = ref({
  alatstandarfk: '',
  namaalatstandar: '',
  namamerk: '',
  namatipe: '',
  namaserialnumber: '',
  calldate: '',
  duedate: '',
  file: null,
})
const router = useRouter()
const dataSource: any = ref([])
const isRegistrasi = ref(false)

const d_View = [
  { name: 'Grid View', value: 'grid', icon: 'fas fa-id-card-alt' },
  { name: 'List View', value: 'list', icon: 'fas fa-list' },
]

const selectView: any = ref('list')
const isLoading = ref(false)
const isLoadingTT = ref(false)
const d_ulab = ref([])
const d_lingkup = ref([])
const currentPage: any = ref({
  limit: 5,
  rows: 50,
})

const filters = ref('')
const filterLingkup = ref('')
const filterStandarMilik = ref('')

// Opsi dropdown dibuat dari dataSource, tanpa API atau daftar pilihan tambahan.
// Map menghindari pilihan ganda yang hanya berbeda kapitalisasi.
const getUniqueOptions = (field: 'lingkupkalibrasi' | 'namaperusahaan'): string[] => {
  const unique = new Map<string, string>()

  dataSource.value.forEach((row: any) => {
    const value = String(row[field] ?? '').trim()
    const key = toUp(value)
    if (value && !unique.has(key)) unique.set(key, value)
  })

  return [...unique.values()].sort((a, b) => a.localeCompare(b, 'id'))
}

const lingkupOptions = computed(() => getUniqueOptions('lingkupkalibrasi'))
const standarMilikOptions = computed(() => getUniqueOptions('namaperusahaan'))

// Ketiga filter berlaku bersama-sama untuk List View dan Grid View.
const dataSourcefiltered = computed(() => {
  const search = toUp(filters.value).trim()
  const lingkup = toUp(filterLingkup.value).trim()
  const standarMilik = toUp(filterStandarMilik.value).trim()

  return dataSource.value.filter((row: any) => {
    const matchSearch = !search || [
      row.namatipe,
      row.namaalatstandar,
      row.namamerk,
      row.namaserialnumber,
      row.namaperusahaan,
      row.lingkupkalibrasi,
    ].some((col) => toUp(col).includes(search))

    const matchLingkup = !lingkup || toUp(row.lingkupkalibrasi).trim() === lingkup
    const matchStandarMilik = !standarMilik || toUp(row.namaperusahaan).trim() === standarMilik

    return matchSearch && matchLingkup && matchStandarMilik
  })
})

const route = useRoute()

const fetchulab = async (filter: any) => {
  await useApi().get(
    `registrasi/fetch-ulab?param_search=namaperusahaan&query=${filter.query}`
  ).then((response) => {
    d_ulab.value = response.data.map((e: any) => {
      return {
        label: `${e.namaperusahaan}`,
        value: e.id
      };
    });
  });
}

const fetchLingkup = async (filter: any) => {
  const query = encodeURIComponent(filter?.query || '')

  try {
    const response: any = await useApi().get(
      `general/dropdown/lingkupkalibrasi_m?select=id,lingkupkalibrasi&param_search=lingkupkalibrasi&query=${query}&limit=10`
    )

    // useApi().get() sudah mengembalikan isi `response` dari API.
    d_lingkup.value = Array.isArray(response) ? response : []
  } catch {
    d_lingkup.value = []
  }
}

// === API ===
async function fetchData() {
  isLoading.value = true
  try {
    let limit: any = currentPage.value.limit
    let offset: any = route.query.page ? Number(route.query.page) : 1
    offset = offset * limit - limit
    let rows: any = currentPage.value.rows

    let namaalatstandar = ''
    let StatusEnabled = ''

    if (item.value.namaalatstandar) {
      namaalatstandar = '&namaalatstandar=' + encodeURIComponent(toUp(item.value.namaalatstandar))
    }

    StatusEnabled = '&statusenabled=' + (item.value.aktif ? 'true' : 'false')

    const response = await useApi().get(
      '/sysadmin/master-alat-standar?offset=' +
      offset +
      '&limit=' +
      limit +
      '&rows=' +
      rows +
      StatusEnabled +
      namaalatstandar
    )

    // nomor urut
    dataSource.value = (response.data || []).map((el: any, idx: number) => ({
      ...el,
      no: idx + 1,
      pengingat_rekalibrasi_aktif: toBool(el.pengingat_rekalibrasi_aktif),
    }))
  } finally {
    isLoading.value = false
  }
}

function loadData() {
  fetchData()
}

function add() {
  clear()
  modalInput.value = true
}


const riwayatAlat = (e: any) => {
  router.push({
    name: 'module-sysadmin-detail-alat-standar',
    query: {
      id_alat: e.id,
    },
  })
}

const openUploadSertifikat = (e: any) => {
  sertifikatForm.value = {
    alatstandarfk: e.id,
    namaalatstandar: e.namaalatstandar,
    namamerk: e.namamerk,
    namatipe: e.namatipe,
    namaserialnumber: e.namaserialnumber,
    calldate: e.calldate || '',
    duedate: e.duedate || '',
    file: null,
  }
  certificateInputKey.value += 1
  modalSertifikat.value = true
}

const closeUploadSertifikat = () => {
  if (isUploadingCertificate.value) return
  modalSertifikat.value = false
  sertifikatForm.value.file = null
  certificateInputKey.value += 1
}

const onCertificateSelected = (event: any) => {
  const files = event.files || []
  const file = files[files.length - 1] as File | undefined
  if (!file) return

  setCertificateFile(file)
}

const clearCertificateSelection = (clearCallback: () => void) => {
  clearCallback()
  sertifikatForm.value.file = null
}

const removeCertificateFile = (removeFileCallback: (index: number) => void, index: number) => {
  removeFileCallback(index)
  sertifikatForm.value.file = null
}

const setCertificateFile = (file: File) => {

  const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')
  if (!isPdf) {
    useToaster().error('Sertifikat harus berupa file PDF')
    certificateUploader.value?.clear()
    sertifikatForm.value.file = null
    return
  }
  if (file.size > 10 * 1024 * 1024) {
    useToaster().error('Ukuran sertifikat maksimal 10 MB')
    certificateUploader.value?.clear()
    sertifikatForm.value.file = null
    return
  }

  sertifikatForm.value.file = file
}

const formatFileSize = (bytes: number) => {
  if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

const uploadSertifikat = async () => {
  const form = sertifikatForm.value
  if (!form.calldate) {
    useToaster().error('Cal Date harus diisi')
    return
  }
  if (!form.duedate) {
    useToaster().error('Due Date harus diisi')
    return
  }
  if (new Date(form.duedate).getTime() < new Date(form.calldate).getTime()) {
    useToaster().error('Due Date tidak boleh lebih awal dari Cal Date')
    return
  }
  if (!form.file) {
    useToaster().error('File sertifikat harus dipilih')
    return
  }

  const payload = new FormData()
  payload.append('alatstandarfk', form.alatstandarfk)
  payload.append('calldate', H.formatDate(form.calldate, 'YYYY-MM-DD HH:mm:ss'))
  payload.append('duedate', H.formatDate(form.duedate, 'YYYY-MM-DD HH:mm:ss'))
  payload.append('sertifikat', form.file)

  isUploadingCertificate.value = true
  try {
    await useApi().post('/sysadmin/upload-sertifikat-standar', payload)
    modalSertifikat.value = false
    sertifikatForm.value.file = null
    certificateInputKey.value += 1
    await fetchData()
  } finally {
    isUploadingCertificate.value = false
  }
}

// Normalisasi ke uppercase saat load ke form
function edit(e: any) {
  item.value.id = e.id
  item.value.namaalatstandar = toUp(e.namaalatstandar)
  item.value.namamerk = toUp(e.namamerk)
  item.value.namatipe = toUp(e.namatipe)
  item.value.namaserialnumber = toUp(e.namaserialnumber)
  item.value.duedate = e.duedate
  item.value.calldate = e.calldate
  item.value.statusenabled = e.statusenabled
  item.value.pengingat_rekalibrasi_aktif = e.pengingat_rekalibrasi_aktif
  item.value.standarmilikfk = {
    value: e.idunit ?? '',
    label: e.namaperusahaan ?? ''
  };
  item.value.lingkupfk = {
    value: e.lingkupfk ?? '',
    label: e.lingkupkalibrasi ?? ''
  };
}

function detail(e: any) {
  item.value.id = e.id
  item.value.namaalatstandar = toUp(e.namaalatstandar)
  item.value.namamerk = toUp(e.namamerk)
  item.value.namatipe = toUp(e.namatipe)
  item.value.namaserialnumber = toUp(e.namaserialnumber)
  item.value.duedate = e.duedate
  item.value.calldate = e.calldate
  item.value.statusenabled = e.statusenabled
  item.value.pengingat_rekalibrasi_aktif = e.pengingat_rekalibrasi_aktif
  item.value.lingkupfk = {
    value: e.lingkupfk ?? '',
    label: e.lingkupkalibrasi ?? ''
  };
}

async function save() {
  if (!item.value.namaalatstandar) {
    useToaster().error('Nama Alat harus di isi')
    return
  }
   if (!item.value.namamerk) {
    useToaster().error('Merk Alat harus di isi')
    return
  }
  if (!item.value.namatipe) {
    useToaster().error('Tipe Alat harus di isi')
    return
  }
  if (!item.value.namaserialnumber) {
    useToaster().error('Serial Number Alat harus di isi')
    return
  }
  if (!item.value.standarmilikfk.value) {
    useToaster().error('Standar Milik harus di isi')
    return
  }
  if (!item.value.lingkupfk.value) {
    useToaster().error('Lingkup harus di isi')
    return
  }
  if (!item.value.duedate) {
    useToaster().error('Due Date Milik harus di isi')
    return
  }
  if (!item.value.calldate) {
    useToaster().error('Call Date Milik harus di isi')
    return
  }
  const objSave = {
    datastandar: {
      id: item.value.id ?? '',
      namaalatstandar: toUp(item.value.namaalatstandar),
      namamerk: toUp(item.value.namamerk),
      namaserialnumber: toUp(item.value.namaserialnumber),
      namatipe: toUp(item.value.namatipe),
      standarmilikfk: toUp(item.value.standarmilikfk.value),
      lingkupfk: item.value.lingkupfk.value,
      duedate: H.formatDate(item.value.duedate, 'YYYY-MM-DD HH:mm:ss') ?? null,
      calldate: H.formatDate(item.value.calldate, 'YYYY-MM-DD HH:mm:ss') ?? null,
      statusenabled: item.value.statusenabled ?? null,
      pengingat_rekalibrasi_aktif: item.value.pengingat_rekalibrasi_aktif ?? true,
    },
  }

  isLoadingTT.value = true
  await useApi()
    .post(`/sysadmin/save-alat-standar`, objSave)
    .then(
      () => {
        isLoadingTT.value = false
        clear()
        fetchData()
      },
      () => {
        isLoadingTT.value = false
      }
    )
}

async function deleterow(e: any) {
  await useApi()
    .post(`/sysadmin/delete-alat-standar`, { id: e.id })
    .then(
      () => {
        fetchData()
      },
      () => { }
    )
}

function clear() {
  item.value.id = ''
  item.value.namaalatstandar = ''
  item.value.namamerk = ''
  item.value.namatipe = ''
  item.value.namaserialnumber = ''
  item.value.duedate = ''
  item.value.calldate = ''
  item.value.standarmilikfk = ''
  item.value.lingkupfk = ''
  item.value.statusenabled = true
  item.value.pengingat_rekalibrasi_aktif = true
}

function changeView(e: any) {
  // jika multiselect mengirim object, ambil value-nya
  selectView.value = typeof e === 'string' ? e : e?.value ?? 'list'
}

function changeSwitch(_e: any) {
  fetchData()
}
function filter() {
  fetchData()
}
function clearFilter() {
  filters.value = ''
  fetchData()
}

fetchData()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';

/* Pencarian dan dropdown filter sejajar pada layar lebar, turun baris saat sempit. */
.alat-standar-toolbar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
}

.alat-standar-filters {
  display: flex;
  flex: 1 1 600px;
  min-width: 0;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

.alat-standar-search {
  flex: 1 1 220px;
  min-width: 180px;
  max-width: 320px;
}

.alat-standar-search input {
  width: 100%;
}

.alat-standar-select {
  flex: 1 1 175px;
  min-width: 160px;
  max-width: 240px;
}

.alat-standar-select select {
  width: 100%;
}

.alat-standar-actions {
  margin-left: auto;
}

@media only screen and (max-width: 768px) {
  .alat-standar-filters {
    flex-direction: column;
    align-items: stretch;
    flex-basis: 100%;
  }

  .alat-standar-search,
  .alat-standar-select {
    width: 100%;
    min-width: 0;
    max-width: none;
    flex: none;
  }
}

/* === Paksa tampilan uppercase === */
.uppercase,
.uppercase input,
input.uppercase {
  text-transform: uppercase !important;
}

.certificate-modal-content {
  width: 100%;
  padding: 0.25rem;
  box-sizing: border-box;
}

.modal.certificate-upload-modal.is-medium {
  .modal-content {
    max-width: 760px;
  }

  .modal-card-body {
    padding: 1.5rem;
  }
}

.certificate-tool-summary {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0.85rem;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 10px;
  background: var(--white);
}
.certificate-tool-icon {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  color: var(--primary);
  background: var(--primary-light-48);
}

.certificate-tool-icon {
  width: 42px;
  height: 42px;
  border-radius: 8px;

  i {
    font-size: 1.25rem;
  }
}

.certificate-tool-name {
  color: var(--dark-text);
  font-family: var(--font-alt);
  font-size: 0.95rem;
  font-weight: 500;
  line-height: 1.35;
}

.certificate-tool-meta {
  margin-top: 0.2rem;
  color: var(--light-text);
  font-size: 0.8rem;

  span {
    margin: 0 0.25rem;
  }
}

.certificate-note {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  margin-top: 0.9rem;
  padding: 0.75rem 0.85rem;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  color: #1e40af;
  background: #eff6ff;
  font-size: 0.8rem;
  line-height: 1.45;

  i {
    flex: 0 0 auto;
    margin-top: 0.1rem;
    font-size: 1rem;
  }
}

.certificate-fields {
  width: 100%;
  margin-top: 0.25rem;

  > .column {
    min-width: 0;
    padding-top: 0.45rem;
    padding-bottom: 0.45rem;
  }

  > .column.is-12 {
    width: 100%;
    flex: none;
  }

  .field,
  .control {
    width: 100%;
    min-width: 0;
  }
}

.certificate-upload-grid {
  display: flex !important;
  width: 100% !important;
  max-width: 100% !important;
  margin: 0 !important;
}

.certificate-upload-grid > .certificate-upload-row {
  display: block;
  flex: 0 0 100% !important;
  width: 100% !important;
  max-width: 100% !important;
  padding: 0.45rem 0.75rem 0;
  box-sizing: border-box;
  clear: both;

  .field,
  .control {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0;
  }
}

.certificate-prime-upload,
.certificate-upload-row .p-fileupload {
  display: block !important;
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box;
}

.certificate-prime-upload {
  .p-fileupload-buttonbar {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: 10px 10px 0 0;
    background: var(--white);
    box-sizing: border-box;
  }

  .p-fileupload-content {
    display: flex;
    width: 100% !important;
    min-height: 160px;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    border: 1px dashed var(--fade-grey-dark-8);
    border-top: 0;
    border-radius: 0 0 10px 10px;
    background: var(--white);
    box-sizing: border-box;
  }

  .p-button {
    font-weight: 400 !important;
  }
}

.certificate-upload-toolbar {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.certificate-upload-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.certificate-upload-limit {
  color: var(--light-text);
  font-size: 0.78rem;
  font-weight: 400;
}

.certificate-upload-empty {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  color: var(--light-text);
  text-align: center;

  i {
    margin-bottom: 0.5rem;
    color: var(--primary);
    font-size: 2rem;
  }

  p {
    color: var(--dark-text);
    font-size: 0.9rem;
    font-weight: 400;
  }

  span {
    margin-top: 0.2rem;
    font-size: 0.76rem;
  }
}

.certificate-selected-file {
  display: flex;
  width: 100%;
  min-width: 0;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 8px;
  background: var(--white);
  box-sizing: border-box;
}

.certificate-selected-icon {
  display: inline-flex;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  color: var(--primary);
  background: var(--primary-light-48);

  i {
    font-size: 1.25rem;
  }
}

.certificate-selected-info {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
}

.certificate-selected-name {
  overflow: hidden;
  color: var(--dark-text);
  font-size: 0.88rem;
  font-weight: 500;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.certificate-selected-size {
  margin-top: 0.15rem;
  color: var(--light-text);
  font-size: 0.75rem;
}

.certificate-button {
  font-weight: 400 !important;
}

.is-dark {
  .certificate-tool-summary {
    border-color: var(--dark-sidebar-light-12);
    background: var(--dark-sidebar-light-4);
  }

  .certificate-tool-name,
  .certificate-upload-empty p,
  .certificate-selected-name {
    color: var(--dark-dark-text);
  }

  .certificate-tool-icon,
  .certificate-selected-icon {
    color: var(--primary-light-8);
    background: var(--dark-sidebar-light-8);
  }

  .certificate-note {
    border-color: #1e3a5f;
    color: #bfdbfe;
    background: #172337;
  }

  .certificate-prime-upload {
    .p-fileupload-buttonbar,
    .p-fileupload-content,
    .certificate-selected-file {
      border-color: var(--dark-sidebar-light-12);
      background: var(--dark-sidebar-light-4);
    }
  }
}

@media only screen and (max-width: 600px) {
  .modal.certificate-upload-modal.is-medium {
    .modal-content {
      width: 100%;
      padding-right: 0.5rem;
      padding-left: 0.5rem;
    }

    .modal-card-body {
      padding: 1rem;
    }

    .modal-card-foot {
      flex-wrap: wrap;

      .v-button {
        flex: 1 1 135px;
        margin: 0.25rem;
      }
    }
  }

  .certificate-upload-toolbar {
    align-items: flex-start;
    flex-direction: column;
    gap: 0.65rem;
  }

  .certificate-upload-actions {
    width: 100%;

    .p-button {
      flex: 1;
    }
  }

  .certificate-tool-summary {
    align-items: flex-start;
  }

  .certificate-tool-meta {
    line-height: 1.45;
  }
}

.tile-grid {
  .columns {
    margin-left: -0.5rem !important;
    margin-right: -0.5rem !important;
    margin-top: -0.5rem !important;
  }

  .column {
    padding: 0.5rem !important;
  }
}

.is-dark {
  .tile-grid {
    .tile-grid-item {
      @include vuero-card--dark;
    }
  }
}

.tile-grid-v1 {
  .tile-grid-item {
    @include vuero-s-card;
    border-radius: 14px;
    padding: 16px;

    .tile-grid-item-inner {
      display: flex;
      align-items: center;

      .meta {
        margin-left: 10px;
        line-height: 1.2;

        span {
          display: block;
          font-family: var(--font);

          &:first-child {
            color: var(--dark-text);
            font-family: var(--font-alt);
            font-weight: 600;
            font-size: 1rem;
          }

          &:nth-child(2) {
            color: var(--light-text);
            font-size: 0.9rem;
          }
        }
      }

      .dropdown {
        position: relative;
        margin-left: auto;
      }
    }
  }
}

.fs-075 {
  font-size: 0.9rem;
}

.is-navbar {
  .form-layout {
    margin-top: 30px;
  }
}

.form-layout {
  margin: 0 auto;

  &.is-separate {
    .form-outer {
      background: none;
      border: none;

      .form-body {
        display: flex;

        .form-section {
          flex-grow: 2;
          padding: 10px;
          width: 50%;

          .form-section-inner {
            @include vuero-s-card;
            padding: 40px;

            &.has-padding-bottom {
              padding-bottom: 60px;
              height: 100%;
            }

            >h3 {
              font-family: var(--font-alt);
              font-size: 1.2rem;
              font-weight: 600;
              color: var(--dark-text);
              margin-bottom: 30px;
            }

            .columns {
              .column {
                padding-top: 0.25rem;
                padding-bottom: 0.25rem;
              }
            }

            .radio-boxes {
              display: flex;
              justify-content: space-between;
              margin-left: -8px;
              margin-right: -8px;

              .radio-box {
                position: relative;
                width: calc(50% - 16px);
                margin: 8px;

                &:focus-within {
                  border-radius: 3px;
                  outline-offset: var(--accessibility-focus-outline-offset);
                  outline-width: var(--accessibility-focus-outline-width);
                  outline-style: var(--accessibility-focus-outline-style);
                  outline-color: var(--primary);
                }

                input {
                  position: absolute;
                  top: 0;
                  left: 0;
                  height: 100%;
                  width: 100%;
                  opacity: 0;
                  cursor: pointer;

                  &:checked {
                    +.radio-box-inner {
                      background: var(--primary);
                      border-color: var(--primary);
                      box-shadow: var(--primary-box-shadow);

                      .fee,
                      p {
                        color: var(--smoke-white);
                      }
                    }
                  }
                }

                .radio-box-inner {
                  background: var(--white);
                  border: 1px solid var(--fade-grey-dark-3);
                  text-align: center;
                  border-radius: var(--radius);
                  font-family: var(--font);
                  font-weight: 600;
                  font-size: 0.9rem;
                  transition: color 0.3s, background-color 0.3s, border-color 0.3s,
                    height 0.3s, width 0.3s;
                  padding: 30px 20px;

                  .fee {
                    font-family: var(--font);
                    font-weight: 700;
                    color: var(--dark-text);
                    font-size: 2.4rem;
                    line-height: 1;

                    span {
                      &::after {
                        content: '$';
                        position: relative;
                        top: -10px;
                        font-size: 1.5rem;
                      }
                    }
                  }

                  p {
                    font-family: var(--font-alt);
                  }
                }
              }
            }

            .control {
              >p {
                padding-top: 12px;

                >span {
                  display: block;
                  font-size: 0.9rem;

                  span {
                    font-weight: 500;
                    color: var(--dark-text);
                  }
                }
              }
            }
          }

          .form-section-outer {
            .checkboxes {
              padding: 16px 0;

              .checkbox {
                padding: 0;
                font-size: 0.9rem;
              }
            }

            .button-wrap {
              .button {
                min-height: 60px;
                font-size: 1.05rem;
                font-weight: 600;
                font-family: var(--font-alt);
              }
            }
          }
        }
      }
    }
  }
}

.is-dark {
  .form-layout {
    &.is-separate {
      .form-outer {
        background: none !important;

        .form-body {
          .form-section {
            .form-section-inner {
              @include vuero-card--dark;

              >h3 {
                color: var(--dark-dark-text);
              }

              .radio-boxes {
                .radio-box {
                  input:checked+.radio-box-inner {
                    background: var(--primary);
                    border-color: var(--primary);
                    box-shadow: var(--primary-box-shadow);

                    .fee,
                    p {
                      color: var(--smoke-white);
                    }
                  }

                  .radio-box-inner {
                    background: var(--dark-sidebar-light-2);
                    border-color: var(--dark-sidebar-light-12);

                    .fee {
                      color: var(--dark-dark-text);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}

@media only screen and (max-width: 767px) {
  .form-layout {
    &.is-separate {
      .form-outer {
        .form-body {
          padding-left: 0;
          padding-right: 0;
          flex-direction: column;

          .form-section {
            width: 100%;

            .form-section-inner {
              padding: 30px;
            }
          }
        }
      }
    }
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
  .form-layout {
    &.is-separate {
      .form-outer {
        .form-body {
          padding-left: 0;
          padding-right: 0;

          .form-section {
            .form-section-inner {
              padding: 30px;
            }
          }
        }
      }
    }
  }
}

.all-projects {
  .all-projects-header {
    display: flex;
    padding: 20px;
    background: var(--white);
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: var(--radius-large);
    margin-bottom: 1.5rem;

    .header-item {
      width: 25%;
      border-right: 1px solid var(--fade-grey-dark-3);

      &:last-child {
        border-right: none;
      }

      .item-inner {
        text-align: center;

        .lnil,
        .lnir {
          font-size: 2.2rem;
          margin-bottom: 6px;
          color: var(--primary);
        }

        span {
          display: block;
          font-family: var(--font);
          font-weight: 600;
          font-size: 1.4rem;
          color: var(--dark-text);
        }

        p {
          font-family: var(--font-alt);
        }
      }
    }
  }

  .projects-card-grid {
    .grid-item {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 220px;
      padding: 20px;
      background: var(--white);
      border: 1px solid var(--fade-grey-dark-3);
      border-radius: var(--radius-large);

      .top-section {
        .head {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 8px;

          h3 {
            font-size: 1rem;
            font-family: var(--font-alt);
            color: var(--dark-text);
            font-weight: 600;
          }
        }

        .body {
          p {
            font-family: var(--font);
            color: var(--light-text);
          }
        }
      }

      .bottom-section {
        display: flex;

        .foot-block {
          margin-right: 30px;

          .heading {
            font-family: var(--font-alt);
            font-size: 0.75rem;
            color: var(--light-text-dark-22);
          }

          >p {
            padding-top: 5px;
          }

          .developers {
            display: flex;

            .v-avatar {
              margin-right: 6px;
            }
          }
        }
      }
    }
  }
}

.heading {
  font-family: var(--font-alt);
  font-size: 0.75rem;
  color: var(--light-text-dark-22);
}

.is-dark {
  .all-projects {
    .all-projects-header {
      background: var(--dark-sidebar-light-6);
      border-color: var(--dark-sidebar-light-12);

      .header-item {
        border-color: var(--dark-sidebar-light-18);

        span {
          color: var(--dark-dark-text);
        }

        i {
          color: var(--primary) !important;
        }
      }
    }

    .projects-card-grid {
      .grid-item {
        background: var(--dark-sidebar-light-6);
        border-color: var(--dark-sidebar-light-12);

        .top-section {
          .head {
            h3 {
              color: var(--dark-dark-text);
            }
          }
        }

        .bottom-section {
          .foot-block {
            .heading {
              color: var(--light-text-dark-12);
            }
          }
        }
      }
    }
  }
}
</style>
