<template>
  <div class="personal-dashboard personal-dashboard-v2">
    <!--Personal Dashboard V2-->
    <div class="columns is-multiline">
      <div class="column">
        <VCard>
          <div class="column is-12">
            <div class="search-widget">
              <div class="field">
                <div class="columns is-multiline">
                  <div class="column is-2">
                    <VField class="is-autocomplete-select" label="Lokasi">
                      <VControl icon="feather:search">
                        <AutoComplete v-model="item.lokasifk" :suggestions="d_lokasikalibrasi"
                          @complete="fetchLokasi($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                          :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                          placeholder="ketik Lokasi" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-3">
                    <VField class="is-autocomplete-select" label="Unit">
                      <VControl icon="feather:search">
                        <AutoComplete v-model="item.unitfk" :suggestions="d_unit" @complete="fetchUnit($event)"
                          :optionLabel="'label'" :dropdown="true" :minLength="3" :appendTo="'body'"
                          :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik Unit" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-3">
                    <VField class="is-autocomplete-select" label="Jenis Order">
                      <VControl icon="feather:search">
                        <Multiselect v-model="item.jenisorder" :attrs="{ id }" :options="optionsKelompokLayanan"
                          placeholder="Pilih Jenis Order" :searchable="true" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-3 mt-5">
                    <input type="text" v-model="item.search" v-on:keyup.enter="getPenilaianPelanggan()" class="input"
                      placeholder="Search..." />
                  </div>
                  <div class="column mt-5" style="margin-left: auto:!important;">
                    <VIconButton type="button" color="success" class="searcv-button" raised icon="fas fa-search"
                      @click="getPenilaianPelanggan()" :loading="isPlaceLoad">
                    </VIconButton>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </VCard>
      </div>

      <div class="column is-12">
        <div class="column" v-if="isPlaceLoad">
          <VPlaceloadWrap v-for="data in 25" :key="data">
            <VPlaceload class="mx-2 mb-3" />
            <VPlaceload class="mx-2" />
          </VPlaceloadWrap>
        </div>

        <div class="column" v-else>
          <div class="dashboard-card has-margin-bottom">
            <DataTable v-model:expandedRows="expandedRows" :value="ulab" dataKey="norec" class="p-datatable-sm"
              :loading="isLoading" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]" scrollable
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
              <Column expander style="width:3rem" />
              <Column field="nopendaftaran" header="No Pendaftaran" sortable frozen />
              <Column field="namapenanggungjawab" header="Penanggung Jawab" sortable />
              <Column field="namaperusahaan" header="Unit" sortable />
              <Column field="alamatktr" header="Alamat" sortable />
              <Column field="lokasi" header="Lokasi" sortable />
              <Column field="jenisorder" header="Jenis Order" sortable />
              <Column field="tanggalmulai" header="Tanggal Mulai Kalibrasi" sortable />
              <Column header="Progress Order" style="text-align:center; min-width:8rem">
                <template #body="{ data }">
                  <div v-if="data.jumlahdetail && data.jumlahselesai != null"
                    style="font-weight: 600; font-size: 0.98em; color: #2abb4a; margin-top: 5px;">
                    Selesai {{ data.jumlahselesai }}/{{ data.jumlahdetail }}
                    <div style="background:#e9ecef; border-radius:8px; width:85%; height:7px; margin: 6px 0 0 0;">
                      <div :style="{
                        width: ((data.jumlahselesai / data.jumlahdetail) * 100) + '%',
                        background: '#53dd6c',
                        height: '100%',
                        borderRadius: '8px',
                        transition: 'width 0.5s'
                      }"></div>
                    </div>
                  </div>
                </template>
              </Column>

              <Column field="rata2Bintang" header="Rating Pelanggan" sortable>
                <template #body="slotProps">
                  <div v-if="slotProps.data.rata2Bintang !== null" class="stars-summary"
                    :aria-label="`Rating ${slotProps.data.rata2Bintang} dari 5`"
                    style="display:flex; align-items:center;">
                    <div class="stars-outer" style="font-size:1rem;">
                      <div class="stars-inner"
                        :style="{ width: ((parseFloat(slotProps.data.rata2Bintang) / 5) * 100) + '%' }"></div>
                    </div>
                    <span class="has-text-grey-dark" style="margin-left:0.5rem; font-size:0.9rem;">
                      ({{ slotProps.data.rata2Bintang.toString().replace('.', ',') }} dari 5)
                    </span>
                  </div>
                  <span v-else class="text-500">-</span>
                </template>
              </Column>

              <Column field="tglregistrasi" header="Tgl Registrasi" sortable />

              <Column field="isikepuasanpelanggan" header="Isi Survey Pelanggan" sortable
                style="min-width: 100px; text-align:center">
                <template #body="slotProps">
                  {{ slotProps.data.isikepuasanpelanggan ? '✓' : '' }}
                  <VIconButton v-if="(slotProps.data.isikepuasanpelanggan != null)" color="info"
                    v-tooltip.top="'Lihat Isi Survey'" outlined circle icon="fas fa-eye"
                    @click="isiSurvey(slotProps.data.norec)" />
                  <VIconButton
                    v-if="(slotProps.data.isikepuasanpelanggan == null && slotProps.data.jumlahselesai === slotProps.data.jumlahdetail)"
                    color="success" v-tooltip.top="'Peringatkan Isi Survey'" outlined circle icon="fas fa-paper-plane"
                    @click="kirimPeringatan(slotProps.data.norec)" :loading="isLoadDataOrder" />
                </template>
              </Column>

              <!-- EXPANSION: DETAIL -->
              <template #expansion="{ data }">
                <div class="p-4 bg-white border-round-md shadow-1">
                  <h5 class="mb-2">Detail Orders untuk {{ data.namaperusahaan }}</h5>

                  <DataTable :value="data.detail" class="p-datatable-sm" tableStyle="min-width:40rem" scrollable
                    paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                    currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
                    <Column field="namaproduk" header="Produk" style="min-width: 250px" />
                    <Column field="namamerk" header="Merk" style="min-width: 100px" />
                    <Column field="namatipe" header="Tipe" style="min-width: 100px" />
                    <Column field="namaserialnumber" header="SN" style="min-width: 200px" />
                    <Column field="noorderalat" header="No Order" style="min-width: 100px" />
                    <Column field="lingkupkalibrasi" header="Lingkup" style="min-width: 100px" />
                    <Column field="pelaksanateknik" header="Pelaksana" style="min-width: 200px" />
                    <Column field="penyeliateknik" header="Penyelia" style="min-width: 200px" />
                    <Column field="durasikalbrasi" header="Durasi Kalbrasi" style="min-width: 50px" />
                    <Column field="nosertifikat" header="No Sertifikat Kalbrasi" style="min-width: 150px" />

                    <!-- PROGRESS + STEPPER BAR -->
                    <Column header="Progress" style="min-width: 300px">
                      <template #body="{ data: row }">
                        <div class="progress-cell">
                          <div class="progress-text">
                            {{ row.progress || '-' }}
                          </div>

                          <div class="progress-stepper">
                            <div class="bar-wrap">
                              <!-- Garis utama menggunakan PrimeVue ProgressBar -->
                              <ProgressBar :value="stepPercent(row.progress_step)" :showValue="false"
                                style="height:10px" />

                              <!-- 5 titik (step markers) di atas garis -->
                              <div class="step-dots">
                                <span v-for="n in 5" :key="'dot-' + n" class="step-dot"
                                  :class="{ active: n <= (Number(row.progress_step || 1)) }"></span>
                              </div>
                            </div>

                            <div class="step-caption">
                              Step {{ Number(row.progress_step || 1) }}/5
                            </div>
                          </div>
                        </div>
                      </template>
                    </Column>

                    <Column field="bintangpenilaian" header="Penilaian" style="min-width: 200px">
                      <template #body="{ data: row }">
                        <div v-if="row.bintangpenilaian !== null" class="stars-summary"
                          :aria-label="`Rating ${row.bintangpenilaian} dari 5`"
                          style="display:flex; align-items:center;">
                          <div class="stars-outer" style="font-size:1rem;">
                            <div class="stars-inner"
                              :style="{ width: ((parseFloat(row.bintangpenilaian) / 5) * 100) + '%' }"></div>
                          </div>
                          <span class="has-text-grey-dark" style="margin-left:0.5rem; font-size:0.9rem;">
                            ({{ row.bintangpenilaian.toString().replace('.', ',') }} dari 5)
                          </span>
                        </div>
                        <span v-else class="text-500">-</span>
                      </template>
                    </Column>

                    <Column field="ulasanpenilaian" header="Ulasan Penilaian" style="min-width: 300px" />
                    <Column header="Sertifikat Kalibrasi" style="min-width: 100px">
                      <template #body="slotProps">
                        <VIconButton class="mr-3"
                          v-if="slotProps.data.pelaksanaisilembarkerjafk != null && slotProps.data.isverifikasi == null && slotProps.data.jenisorder == 'kalibrasi'"
                          color="info" outlined circle icon="feather:printer"
                          @click="cetakSertifikatLembarKerja(slotProps.data)" />
                      </template>
                    </Column>
                    <Column header="Sertifikat Verifikasi" style="min-width: 100px">
                      <template #body="slotProps">
                        <VIconButton class="mr-3"
                          v-if="slotProps.data.pelaksanaisilembarkerjafk != null && slotProps.data.isverifikasi == true && slotProps.data.jenisorder == 'kalibrasi'"
                          color="success" outlined circle icon="feather:printer"
                          @click="cetakLaporanVerfikasi(slotProps.data)" />
                      </template>
                    </Column>
                    <Column header="Laporan Repair" style="min-width: 100px">
                      <template #body="slotProps">
                        <VIconButton class="mr-3"
                          v-if="slotProps.data.pelaksanaisilaporanrepairfk != null && slotProps.data.jenisorder == 'repair'"
                          color="warning" outlined circle icon="feather:printer"
                          @click="cetakLaporanRepair(slotProps.data)" />
                      </template>
                    </Column>
                    <Column header="Tanda Terima" style="min-width: 100px">
                      <template #body="slotProps">
                        <VIconButton class="mr-3" v-if="slotProps.data.iskaji !== null" color="danger" outlined circle
                          icon="feather:printer" @click="cetakTandaTerima(slotProps.data)" />
                      </template>
                    </Column>
                    <Column header="AMS" style="min-width: 100px">
                      <template #body="slotProps">
                        <VIconButton class="mr-3" v-if="slotProps.data.iskaji !== null" color="danger" outlined circle
                          icon="feather:printer" @click="cetakAmsDashboard(slotProps.data)" />
                      </template>
                    </Column>
                    <Column header="Tanda Selesai Terima" style="min-width: 100px">
                      <template #body="slotProps">
                        <VIconButton class="mr-3" v-if="slotProps.data.isSimpanTerima == true" color="danger" outlined circle
                          icon="feather:printer" @click="cetakSelesaaiTerima(slotProps.data)" />
                      </template>
                    </Column>
                  </DataTable>
                </div>
              </template>
            </DataTable>
          </div>
        </div>
      </div>
    </div>
    <!-- ================= MODAL ISI DOKUMEN ================= -->
    <Dialog v-model:visible="modalIsiDokumen" modal header="Tambah History Penilaian Pelanggan"
      :style="{ width: '30vw' }">
      <div class="columns is-multiline">
        <div class="column is-12">
          <VField label="Head" class="is-rounded-select is-autocomplete-select mt-0 pt-0" v-slot="{ id }">
            <VControl icon="fas fa-archway" fullwidth class="prime-auto-select">
              <Dropdown v-model="item.kdrincianisisurvey" :options="d_ObjectModul" :optionLabel="'label'"
                class="is-rounded" placeholder="Head" style="width: 100%;" :filter="true" showClear
                @change="changeHead($event)" />
            </VControl>
          </VField>
        </div>

        <div class="column is-12">
          <VField label="Nama Isi Dokumen">
            <VControl icon="feather:bookmark">
              <input v-model="item.namasurvey" type="text" class="input is-rounded" placeholder="Nama Isi Dokumen" />
            </VControl>
          </VField>
        </div>

        <div class="column is-12" v-if="item.fileLama">
          <VField label="Lihat Dokumen Sebelumnya">
            <VButton @click="cetakDokumen(item)" type="button" color="info" outlined rounded icon="feather:eye"
              class="w-100">
              Lihat Dokumen
            </VButton>
          </VField>
        </div>

        <div class="column is-12">
          <VField label="Upload Isi Dokumen">
            <FileUpload v-model="fileDokumenSurvey" mode="advanced" name="demo"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" :maxFileSize="10000000" outlined
              :invalidFileTypeMessage="'{0}: Format file tidak diizinkan.'"
              :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
              style="background-color: transparent; color: var(--danger); border: 1px solid;"
              :chooseLabel="isFile(fileDokumenSurvey) ? fileDokumenSurvey.name : item.fileLama || 'Unggah File'"
              @select="onSelect($event)" class="is-rounded w-100" />
          </VField>
        </div>

        <div class="column is-12">
          <VField label="Keterangan">
            <VControl icon="feather:bookmark">
              <input v-model="item.keterangan" type="text" class="input is-rounded" placeholder="Keterangan " />
            </VControl>
          </VField>
        </div>

        <div class="column is-12">
          <VField label="No Urut">
            <VControl icon="feather:bookmark">
              <input v-model="item.nourut" type="number" class="input is-rounded" placeholder="No Urut" />
            </VControl>
          </VField>
        </div>
      </div>

      <template #footer>
        <VButton v-if="item.id" type="button" rounded outlined color="danger" raised icon="feather:trash"
          :loading="isLoading" @click="deleteIsiDokumen(item)"> Hapus
        </VButton>
        <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="tutup()">Tutup</VButton>
        <VButton type="button" rounded outlined color="primary" raised icon="feather:save" :loading="isLoading"
          @click="saveIsiSurvey()"> {{ item.id ? 'Ubah' : 'Simpan' }}
        </VButton>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import ApexChart from 'vue3-apexcharts'
import { useApi } from '/@src/composable/useApi'
import ColumnGroup from 'primevue/columngroup'
import Row from 'primevue/row'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import Dropdown from 'primevue/dropdown'
import FileUpload from 'primevue/fileupload'
import Dialog from 'primevue/dialog'
import * as H from '/@src/utils/appHelper'
import Tree from 'primevue/tree'
import ProgressBar from 'primevue/progressbar'
import AutoComplete from 'primevue/autocomplete';

useHead({
  title: 'Laporan Monitoring - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const d_lokasikalibrasi = ref([])
const d_unit = ref([])
const d_lingkup = ref([])
const ulab = ref<any[]>([])
const ulabBintang: any = ref(0)
const expandedRows = ref(null)
const toast = useToast()
const lastUpdate = ref('')
const item: any = ref({})
const modalIsiSurvey: any = ref(false)
const modalIsiDokumen = ref(false)
let dataSourceMenu: any = ref([])
const expandedKeys: any = ref({})
const selectedKey = ref(null)
let isLoadDataOrder: any = ref(false)
const selectedIDModul = ref('')
const isLoading = ref(false)
let d_ObjectModul: any = ref([])
const fileDokumenSurvey: any = ref()
interface AtributKepuasan {
  no: number
  dimensi: string
  atribut: string
  harapan: number | null
  kepuasan: number | null
}
let isPlaceLoad: any = ref(false)

const tambah = () => {
  setNoUrut()
  modalIsiDokumen.value = true
}

const loadTree = async () => {
  const response = await useApi().get('/mutu/master-isi-survey')
  dataSourceMenu.value = response.tree
  d_ObjectModul.value = response.data
  expandAll()
}

const expandAll = () => {
  for (let node of dataSourceMenu.value) {
    expandNode(node)
  }
  expandedKeys.value = { ...expandedKeys.value }
}

const expandNode = (node: any) => {
  if (node.children && node.children.length) {
    expandedKeys.value[node.key] = true
    for (let child of node.children) {
      expandNode(child)
    }
  }
}

const tutup = () => {
  clear()
  modalIsiDokumen.value = false
}

const onNodeSelect = async (node: any) => {
  d_ObjectModul.value.forEach((element: any) => {
    if (element.key == node.parent_id) {
      item.value.kdrincianisisurvey = element
    }
  })
  item.value.id = node.key
  item.value.namasurvey = node.label
  item.value.fungsi = node.data.fungsi
  item.value.nourut = node.nourut
  item.value.keterangan = node.data.keterangan
  item.value.fileLama = node.data.isisurvey
  modalIsiDokumen.value = true
}

const cetakSertifikatLembarKerja = (e: any) => {
  H.printBlade(`asman/cetak-sertifikat-lembar-kerja?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`);
}

const cetakLaporanVerfikasi = (e: any) => {
  H.printBlade(`asman/cetak-laporan-verifikasi?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`)
}

const cetakLaporanRepair = (e: any) => {
  H.printBlade(`asman/cetak-laporan-repair?pdf=true&norec=${e.norec}&norec_detail=${e.norec_detail}`);
}

const cetakTandaTerima = (e: any) => {
  H.printBlade(`registrasi/cetak-tanda-terima?pdf=true&norec=${e.norec}`);
}

const cetakAmsDashboard = (e: any) => {
  H.printBlade(`registrasi/cetak-ams?norecregis=${e.norec}`);
};

const cetakSelesaaiTerima = (e: any) => {
  H.printBlade(`registrasi/cetak-selesai-terima?pdf=true&norec=${e.norec}`);
}

const optionsKelompokLayanan: any = [
  { value: 'kalibrasi', label: 'Kalibrasi' },
  { value: 'repair', label: 'Repair' },
]

const fetchLokasi = async (filter: any) => {
  await useApi().get(
    `general/dropdown/lokasikalibrasi_m?select=id,lokasi&param_search=lokasi&query=${filter.query}&limit=10`
  ).then((response) => {
    d_lokasikalibrasi.value = response
  })
}

const fetchUnit = async (filter: any) => {
  await useApi().get(
    `general/dropdown/mitra_m?select=id,namaperusahaan&param_search=namaperusahaan&query=${filter.query}&limit=10`
  ).then((response) => {
    d_unit.value = response
  })
}


const fetchLingkup = async (filter: any) => {
  await useApi().get(
    `general/dropdown/lingkupkalibrasi_m?select=id,lingkupkalibrasi&param_search=lingkupkalibrasi&query=${filter.query}&limit=10`
  ).then((response) => {
    d_lingkup.value = response
  })
}

function clear() {
  item.value = {
    statusenabled: true,
    aktif: true
  }
  fileDokumenSurvey.value = null
}

const cetakDokumen = (e: any) => {
  if (!e.id) {
    H.alert('warning', 'Data tidak valid')
    return
  }
  H.printBlade(`mutu/cetak-dokumen-history-survey?id=${e.id}`)
}

const isFile = (obj: any): obj is File => {
  return obj instanceof File || (obj && typeof obj === 'object' && 'name' in obj && 'size' in obj && 'type' in obj)
}

const onSelect = async (filez: any) => {
  const file = filez.files[0]
  if (!file) return
  const allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg',
    'image/png',
    'image/webp'
  ]

  if (file.size > 10000000) {
    H.alert('error', 'Maksimal ukuran file adalah 10 MB')
    return
  }

  if (!allowedTypes.includes(file.type)) {
    H.alert('error', 'File yang diizinkan: PDF, Word, Excel, JPG, PNG, atau WebP')
    return
  }

  fileDokumenSurvey.value = file
}

const saveIsiSurvey = async () => {
  if (!item.value.namasurvey) {
    H.alert('error', 'Nama Isi Dokumen harus di isi')
    return
  }
  if (!item.value.nourut) {
    H.alert('error', 'No Urut harus di isi')
    return
  }

  const formData = new FormData()
  if (isFile(fileDokumenSurvey.value)) {
    formData.append('fileDokumenSurvey', fileDokumenSurvey.value)
  } else if (item.value.fileLama) {
    formData.append('namaFileLama', item.value.fileLama)
  }
  formData.append('id', item.value.id ? item.value.id : '')
  formData.append('keterangan', item.value.keterangan ? item.value.keterangan : '')
  formData.append('namasurvey', item.value.namasurvey ? item.value.namasurvey : null)
  formData.append('nourut', item.value.nourut ? item.value.nourut : null)
  formData.append('kdrincianisisurvey', item.value.kdrincianisisurvey?.key ?? '')

  isLoading.value = true
  await useApi()
    .post(`/mutu/save-isi-dokumen-survey`, formData)
    .then((response: any) => {
      loadTree()
      clear()
      setNoUrut()
      isLoading.value = false
      modalIsiDokumen.value = false
    }, (error) => {
      isLoading.value = false
    })
}

const deleteIsiDokumen = async (itemx: any) => {
  isLoading.value = true
  await useApi()
    .post(`/mutu/hapus-isi-survey`, { id: itemx.id })
    .then((response: any) => {
      loadTree()
      clear()
      setNoUrut()
      isLoading.value = false
      modalIsiDokumen.value = false
    }, (error) => {
      isLoading.value = false
    })
}

const setNoUrut = async () => {
  if (d_ObjectModul.value.length == 0) {
    const response = await useApi().get('/mutu/master-history-survey-nourut')
    item.value.nourut = response.nourut + 1
    console.log(response)
  } else {
    item.value.nourut = d_ObjectModul.value.length ? d_ObjectModul.value[d_ObjectModul.value.length - 1].nourut + 1 : 0
  }
}

const changeHead = async (node: any) => {
  setNoUrut()
}

const kirimPeringatan = async (e: any) => {
  let json = { survey: { norec: e } }
  isLoadDataOrder.value = true
  await useApi()
    .post(`/mutu/kirim-isi-survey-pelanggan`, json)
    .then((response: any) => {
      isLoadDataOrder.value = false
    })
    .catch((e: any) => {
      isLoadDataOrder.value = false
    })
}

const isiSurvey = async (e: any) => {
  modalIsiSurvey.value = true
  const response = await useApi().get(`/asman/header-mitra?norec_pd=${e}`)
  item.value.namaresponden = response.mitra.name
  item.value.jabatanresponden = response.mitra.jabatan
  item.value.notelpon = response.mitra.nowa
  getisiSurvey(e)
}

const getisiSurvey = async (e: any) => {
  const response = await useApi().get(`/registrasi/get-survey-pelanggan?norec_pd=${e}`)
  const data = response.data[0]
  item.value.namaresponden = data.namaresponden
  item.value.unitdivisikerja = data.unitdivisikerja
  item.value.jabatanresponden = data.jabatanresponden
  item.value.lamabekerja = data.lamabekerja
  item.value.jeniskelamin = { value: data.idjeniskelamin ?? '', label: data.jeniskelamin ?? '' }
  item.value.pendidikan = { value: data.idpendidikan ?? '', label: data.pendidikan ?? '' }
  item.value.usia = data.usia
  item.value.notelpon = data.notelpon
  item.value.lamamenjadimitra = data.lamamenjadimitra

  if (data.detailSurvey && Array.isArray(data.detailSurvey)) {
    data.detailSurvey.forEach((d: any) => {
      const index = atributList.value.findIndex((x) => x.no == d.no)
      if (index !== -1) {
        atributList.value[index].harapan = Number(d.harapan)
        atributList.value[index].kepuasan = Number(d.kepuasan)
      }
    })
  }
  item.value.b21 = data.b21
  item.value.b22 = data.b22
  item.value.b23 = data.b23
  item.value.ketidakpuasanlayanan = data.ketidakpuasanlayanan
  item.value.masukansaran = data.masukansaran
  item.value.d1_skor = data.d1_skor
  item.value.d1_penjelasan = data.d1_penjelasan
  item.value.d2_skor = data.d2_skor
  item.value.d2_penjelasan = data.d2_penjelasan
}

const atributList = ref<AtributKepuasan[]>([]) // (isi sesuai kebutuhan Anda)

// ============= Helper Progress Step =============
/**
 * Konversi progress_step (1..5) ke persentase ProgressBar:
 * 1→0%, 2→25%, 3→50%, 4→75%, 5→100%
 */
const stepPercent = (step: any): number => {
  const s = Math.min(5, Math.max(1, Number(step || 1)))
  return Math.round(((s - 1) / 4) * 100)
}
// ===============================================

const getPenilaianPelanggan = async () => {
  let search = item.value.search ? `search=${item.value.search}` : ''
  let lokasikalibrasi = item.value.okasifk ? `&lokasifk=${item.value.lokasifk.value}` : ''
  let unitfk = item.value.unitfk ? `&unitfk=${item.value.unitfk.value}` : ''
  let jenisorder = item.value.jenisorder ? `&jenisorder=${item.value.jenisorder}` : ''
  try {
    isPlaceLoad.value = true
    const res = await useApi().get(`/laporan/get-laporan-monitoring?${search}${lokasikalibrasi}${unitfk}${jenisorder}`)
    ulab.value = (res.detail || []).map((r: any) => ({
      ...r,
      lokasi: (String(r.jenisorder || '').toLowerCase() === 'repair')
        ? (r.lokasirepair || '')
        : (r.lokasikalibrasi || '')
    }))
    isPlaceLoad.value = false
  } catch (e) {
    isPlaceLoad.value = false
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data.' })
  }
}

getPenilaianPelanggan()
loadTree()
onMounted(() => {
  lastUpdate.value = new Date().toLocaleString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
})
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';

.stars-outer {
  position: relative;
  display: inline-block;

  /* 5 bintang abu-abu sebagai background */
  &::before {
    content: '\f005\f005\f005\f005\f005';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    color: #ddd;
  }
}

.stars-inner {
  position: absolute;
  top: 0;
  left: 0;
  white-space: nowrap;
  overflow: hidden;

  /* 5 bintang gold sebagai foreground */
  &::before {
    content: '\f005\f005\f005\f005\f005';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    color: #ffc107;
  }
}

.is-navbar {
  .personal-dashboard {
    margin-top: 30px;
  }
}

.personal-dashboard-v2 {
  .dashboard-header {
    @include vuero-s-card;

    display: flex;
    align-items: center;
    padding: 30px;

    .user-meta {
      padding: 0 3rem;

      border-right: 1px solid var(--fade-grey-dark-3) h3 {
        max-width: 180px;
      }
    }

    .user-action {
      padding: 0 3rem;
    }

    .cta {
      position: relative;
      flex-grow: 2;
      max-width: 275px;
      margin-left: auto;
      background: var(--primary-light-8);
      padding: 20px;
      border-radius: var(--radius-large);
      box-shadow: var(--primary-box-shadow);

      .lnil,
      .lnir {
        position: absolute;
        bottom: 1rem;
        right: 1rem;
        font-size: 4rem;
        opacity: 0.3;
      }

      .link {
        font-family: var(--font-alt);
        display: block;
        font-weight: 500;
        margin-top: 0.5rem;

        &:hover,
        &:focus {
          color: var(--smoke-white);
          opacity: 0.6;
        }
      }
    }
  }

  .dashboard-card {
    @include vuero-s-card;

    padding: 30px;

    &:not(:last-child) {
      margin-bottom: 1.5rem;
    }

    .card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;

      h3 {
        font-family: var(--font-alt);
        font-size: 1rem;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 0;
      }
    }

    .active-projects,
    .active-team,
    .active-list {
      padding: 10px 0;
    }
  }
}

/* ===== Stepper Progress (5 steps) ===== */
.progress-cell {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.progress-text {
  font-weight: 600;
  color: var(--dark-text);
  margin-bottom: 2px;
}

.progress-stepper .bar-wrap {
  position: relative;
}

/* Posisi 5 titik di atas ProgressBar */
.progress-stepper .step-dots {
  position: absolute;
  left: 0;
  right: 0;
  top: -6px;
  /* naikkan titik sedikit di atas garis */
  display: flex;
  justify-content: space-between;
  padding: 0 2px;
  pointer-events: none;
}

.progress-stepper .step-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid #d5d7da;
  background: #f4f5f7;
  box-sizing: border-box;
}

.progress-stepper .step-dot.active {
  border-color: #22c55e;
  /* hijau */
  background: #22c55e;
}

.progress-stepper .step-caption {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 6px;
}

/* ===================================== */

.is-dark {
  .personal-dashboard-v2 {

    .dashboard-header,
    .dashboard-card {
      @include vuero-card--dark;
    }

    .home-header {
      .cta {
        background: var(--primary-light-2);
        box-shadow: var(--primary-box-shadow);
      }
    }
  }

  /* Dark mode adjust untuk step dots */
  .progress-stepper .step-dot {
    border-color: #394150;
    background: #111827;
  }

  .progress-stepper .step-dot.active {
    border-color: #22c55e;
    background: #22c55e;
  }
}

@media only screen and (max-width: 767px) {
  .personal-dashboard-v2 {
    .dashboard-header {
      flex-direction: column;
      text-align: center;

      .v-avatar {
        margin-bottom: 10px;
      }

      .user-meta {
        padding-top: 10px;
        padding-bottom: 10px;
        border: none;
      }

      .user-action {
        padding-bottom: 30px;
      }

      .cta {
        margin-left: 0;
      }
    }

    .active-projects {
      .media-flex-center {
        .flex-end {
          .avatar-stack {
            display: none;
          }
        }
      }
    }
  }
}
</style>
