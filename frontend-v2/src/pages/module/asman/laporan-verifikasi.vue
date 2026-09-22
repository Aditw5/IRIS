<template>
  <div class="column">
    <div class="business-dashboard hr-dashboard">
      <div class="columns is-multiline">
        <div class="column is-12 p-0">
          <div class="block-header">
            <div class="left column is-6 p-0">
              <div class="current-user">
                <h3>{{ item.namaproduk }}</h3>
              </div>
            </div>
            <div class="center column is-6 p-0">
              <div>
                <div>
                  <h4 class="block-heading">Merk/Tipe</h4>
                  <p class="block-hext">{{ item.namamerk }}/{{ item.namatipe }}</p>
                  <h4 class="block-heading">S/N</h4>
                  <p class="block-hext">{{ item.namaserialnumber }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="column is-12">
    <TabView class="tabview-custom " :scrollable="true" @tab-click="klikTab($event)">
      <TabPanel>
        <template #header>
          <i class="fas fa-users mr-2" aria-hidden="true"></i>
          <span>Laporan Verifikasi</span>
          <Badge :value="totalData" v-if="totalData > 0" severity="danger" class="ml-2" />
        </template>

        <VCard>
          <div class="column" v-if="isLoading">
            <VPlaceloadWrap v-for="data in 10" :key="data">
              <VPlaceload class="mx-2 mb-3" />
            </VPlaceloadWrap>
          </div>

          <div v-else>
            <div class="columns is-multiline">
              <div class="column is-3">
                <VField label=" Tanggal">
                  <VDatePicker v-model="item.tglkalibrasi" mode="dateTime" style="width: 100%;">
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

              <div class="column is-3">
                <VField label="Tempat">
                  <AutoComplete v-model="item.tempatKalibrasi" :suggestions="d_ruangan" @complete="fetchRuangan($event)"
                    :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                    :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
                </VField>
              </div>
            </div>
            <div class="columns is-multiline">
              <div class="column is-3 mt-5-min">
                <div class="column is-12">
                  <span>Suhu </span>
                </div>
                <VField addons>
                  <VControl>
                    <VInput v-model="item.suhu" placeholder="Suhu" />
                  </VControl>
                  <VControl class="field-addon-body">
                    <VButton static>°C</VButton>
                  </VControl>
                </VField>
              </div>

              <div class="column is-3 mt-5-min">
                <div class="column is-12">
                  <span>Kelembaban Relatif </span>
                </div>
                <VField addons>
                  <VControl>
                    <VInput v-model="item.kelembabanRelatif" placeholder="Kelembaban Relatif" />
                  </VControl>
                  <VControl class="field-addon-body">
                    <VButton static>% RH</VButton>
                  </VControl>
                </VField>
              </div>
            </div>
            <div class="columns is-multiline">
              <div class="column is-6">
                <VField label="Evaluasi Verifikasi">
                  <VControl>
                    <VTextarea v-model="item.evalverifikasi" rows="4" placeholder="Catatan/Notes">
                    </VTextarea>
                  </VControl>
                </VField>
              </div>
              <div class="column is-6">
                <VField label="Catatan">
                  <VControl>
                    <VTextarea v-model="item.catatanverifikasi" rows="4" placeholder="Catatan/Notes">
                    </VTextarea>
                  </VControl>
                </VField>
              </div>
            </div>
          </div>
          <div class="column is-8">
            <Fieldset legend="- Daftar Peralatan Standar Verifikasi" :toggleable="true">
              <div style="overflow-y:auto;" class="mt-5 form-section-inner is-horizontal">
                <table width="100%">
                  <thead>
                    <tr class="tr-po">
                      <th class="th-po" width="25%" style="vertical-align:inherit;text-align: center;">Nama Alat
                        Standar
                        <VField>
                          <VControl>
                            <VIconButton class="ml-2" icon="feather:folder-plus" rounded @click="openStandar()"
                              v-tooltip.bottom.right="'Tambah Alat Standar'" />
                          </VControl>
                        </VField>
                      </th>
                      <th class="th-po" width="8%" style="vertical-align:inherit;text-align: center;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody v-for="(items, index) in item.detailPeralatanStandar" :key="index">
                    <tr class="tr-po">
                      <td class="td-po">
                        <div class="column pt-3 pb-0">
                          <VField>
                            <VControl>
                              <div style="display:flex; align-items:center">
                                <AutoComplete v-model="items.daftaralatstandar" :suggestions="d_alatstandar"
                                  @complete="fetchAlatStandar($event)" :optionLabel="'label'" :dropdown="true"
                                  :minLength="3" class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                                  :field="'label'" placeholder="ketik untuk mencari..." />
                                <VIconButton class="ml-2" icon="feather:camera" rounded @click="openScanner(items)"
                                  v-tooltip.bubble="'Scan QR Alat'" />
                              </div>
                            </VControl>
                          </VField>
                        </div>
                      </td>
                      <td class="td-po" style="vertical-align: inherit;">
                        <div class="column is-12 pl-0 pr-0">
                          <VButtons style="justify-content: space-around;">
                            <VIconButton type="button" raised circle icon="feather:plus"
                              v-tooltip-prime.bottom="'Tambah'" @click="addNewAlatStandar()" outlined color="info">
                            </VIconButton>
                            <VIconButton type="button" raised circle v-tooltip-prime.bottom="'Hapus'" outlined
                              icon="feather:trash" @click="removeAlatStandar(items)" color="danger">
                            </VIconButton>
                          </VButtons>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </Fieldset>
          </div>
        </VCard>
        <VCard>
          <div class="colum is-12">
            <div class="columns is-multiline">
              <div class="columns is-multiline is-vcentered is-mobile pt-0 pb-0" style="gap: 0.25rem;">
                <div class="column is-narrow" v-if="dataExcel != null">
                  <VButton color="primary" @click="cetakLaporanVerfikasi()" outlined icon="feather:printer">
                    Cetak Laporan
                  </VButton>
                </div>
                <div class="column is-narrow" v-if="dataExcel != null">
                  <VButton color="info" @click="setujuiSertifikat()" outlined icon="feather:save"
                    :loading="isLoadingSave">
                    Setujui Laporan
                  </VButton>
                </div>
                <div class="column is-narrow" v-if="dataExcel != null">
                  <VButton color="danger" outlined icon="feather:trash" @click="batalRegis()">
                    Tolak Laporan Verifikasi
                  </VButton>
                </div>
              </div>
              <div class="column is-12">
                <FileUpload v-model="fileMitraExcel" mode="advanced" name="demo"
                  accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                  :maxFileSize="10000000" outlined
                  :invalidFileTypeMessage="'{0}: File yang diupload harus CSV atau Excel (XLS/XLSX).'"
                  :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
                  style="background-color: transparent; color: var(--danger); border: 1px solid;"
                  :chooseLabel="isFile(fileMitraExcel) ? fileMitraExcel.name : dataExcel || 'Unggah File'"
                  @select="onSelect($event)" class="is-rounded w-100" />
              </div>

              <div class="column" v-if="fileMitraExcel">
                <div class="column is-12 mt-2">
                  <div class="dataTable-info" style="font-style:italic">
                    File Excel Terpilih: {{ fileMitraExcel.name }} ({{ formatSize(fileMitraExcel.size) }})
                  </div>
                </div>
              </div>
            </div>
            <div class="column" v-if="dataExcel != null">
              <div class="column is-12 mt-4-min">
                <div class="dataTable-bottom mt-2">
                  <div class="dataTable-info" style="font-style:italic">
                    File Excel Terunggah {{ dataExcel }}
                  </div>
                </div>
                <div class="mt-2">
                  <VButton rounded color="warning" class="" icon="feather:download" raised bold
                    @click="downloadFileTerunggah()">
                    Download File
                  </VButton>
                </div>
              </div>
            </div>
          </div>
        </VCard>
      </TabPanel>
    </TabView>
  </div>
  <VModal :open="modalPenolakanSertifikat" title="Tolak Laporan Verifikasi" size="medium" actions="right"
    @close="modalPenolakanSertifikat = false" cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-12">
          <span style="margin-bottom:1rem;font-weight: bold; font-size: 12px; font-family: var(--font-alt);">Alasan
            Penolakan
          </span>
          <VField>
            <VControl>
              <VTextarea class="textarea is-rounded" v-model="item.alasanpenolakan" rows="4"
                placeholder="Alasan Penolakan" autocomplete="off" autocapitalize="off" spellcheck="true" />
            </VControl>
          </VField>
        </div>
      </div>
    </template>
    <template #action>
      <VButton icon="feather:plus" color="primary" @click="savePenolakanSerti" :loading="isLoading" raised>Simpan
      </VButton>
    </template>
  </VModal>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import { ref, reactive, nextTick, onBeforeUnmount } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useViewWrapper } from '/@src/stores/viewWrapper';
import { useHead } from '@vueuse/head';
import FileUpload from 'primevue/fileupload';
import Button from 'primevue/button';
import * as H from '/@src/utils/appHelper';
import { useApi } from '/@src/composable/useApi';
import moment from 'moment';
import { useUserSession } from '/@src/stores/userSession'
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import AutoComplete from 'primevue/autocomplete';
import Fieldset from 'primevue/fieldset';
import Dialog from 'primevue/dialog'
import { Html5Qrcode } from 'html5-qrcode'

useHead({ title: 'Laporan Verifikasi - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

let NOREC_DETAIL = useRoute().query.norec_detail as string
let SUBLINGKUP = useRoute().query.sublingkup as string
let NOREC = useRoute().query.norec as string

const dataExcel: any = ref()
const isLoading: any = ref(false)
const isLoadingSave: any = ref(false)
const dataSourceHasilLembarKerja: any = ref([])
const router = useRouter()
const fileMitraExcel: any = ref<File | null>(null)
const showScanner = ref(false)
let html5QrCode: Html5Qrcode | null = null
const scannerRowIdx = ref<number | null>(null)
const scannerItem = ref<any>(null)
const editingRows = ref([]);
const totalData = ref(0)
const penunjukan_standar_2 = ref('')
const penunjukan_standar_satuan_2 = ref('')

const item: any = ref({
  filterTgl: reactive({
    start: new Date(),
    end: new Date(),
  }),
  tglkalibrasi: new Date(),
  tempatKalibrasi: null,
  suhu: null,
  kelembabanRelatif: null,
  evalverifikasi: null,
  catatanverifikasi: null,
  detailInstruksiKerja: [{ no: 1 }],
  detailPeralatanStandar: [{ no: 1 }]
})

const d_instruksiKerja = ref([])
const d_alatstandar = ref([])
const d_ruangan = ref([])
const showPdfDialog = ref(false)
const pdfUrl = ref<string | null>(null)
const pdfFilename = ref('lembar-kerja.pdf')

const openPdfPreview = (blob: Blob, filename?: string) => {
  if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value)
  pdfFilename.value = filename || 'lembar-kerja.pdf'
  pdfUrl.value = URL.createObjectURL(blob)
  showPdfDialog.value = true
}
const closePdfPreview = () => {
  showPdfDialog.value = false
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
    pdfUrl.value = null
  }
}
const downloadCurrentPdf = () => {
  if (!pdfUrl.value) return
  const a = document.createElement('a')
  a.href = pdfUrl.value
  a.download = pdfFilename.value
  document.body.appendChild(a)
  a.click()
  a.remove()
}
const openPdfNewTab = () => {
  if (pdfUrl.value) window.open(pdfUrl.value, '_blank')
}

const downloadFileTerunggah = () => {
  const norec = NOREC_DETAIL
  const token = useUserSession().token
  const url = `/service/asman/download-file-terunggah?norec=${norec}&laporan=verifikasi&token=${token}`
  window.open(url, '_blank')
}

onBeforeUnmount(() => {
  if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value)
})
const modalPenolakanSertifikat: any = ref(false)

const batalRegis = async () => {
  item.value.norecregis = NOREC_DETAIL
  modalPenolakanSertifikat.value = true
}

const savePenolakanSerti = async () => {
  if (!item.value.alasanpenolakan) { H.alert('warning', 'Alasan Penolakan harus di isi'); return }
  let json = {
    itemtolak: {
      'norecregis': item.value.norecregis,
      'alasanpenolakanserti': item.value.alasanpenolakan,
    }
  }
  isLoading.value = true
  await useApi()
    .post(`/asman/save-penolakan-sertifikat`, json)
    .then((response: any) => {
      isLoading.value = false
      // clear()
      toDashboard()
    })
    .catch((e: any) => {
      isLoading.value = false
    })
}


const klikTab = (_e: any) => { }
function formatSize(bytes: number) {
  if (!bytes) return '0 B'
  const k = 1024; const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const response: any = await useApi().get(`asman/get-laporan-verifikasi?norecdetail=${NOREC_DETAIL}`)
    dataExcel.value = response[0].excellaporanverif
    dataSourceHasilLembarKerja.value = response[0]
  } catch {
    dataSourceHasilLembarKerja.value = []; totalData.value = 0
  } finally {
    isLoading.value = false
  }
}

const setujuiSertifikat = async () => {
  let json = {
    'verif': {
      'norec': dataSourceHasilLembarKerja.value.detailregistraifk ?? '',
    }
  }
  isLoadingSave.value = true
  await useApi().post('/asman/save-setujui-serti-asman', json).then((r) => {
    isLoadingSave.value = false
    toDashboard()
  }).catch((error: any) => {
    isLoadingSave.value = false
    console.error('Error saat menyimpan', error);

    if (error.response) {

      H.alert('error', `Kesalahan: ${error.response.status} - ${error.response.data.message || 'Gagal menyimpan berkas mitra'}`);
    } else if (error.request) {

      H.alert('error', 'Tidak ada respons dari server. Silakan coba lagi.');
    } else {

      H.alert('error', `Terjadi kesalahan: ${error.message}`);
    }
  })
}

const toDashboard = () => {
  router.push({
    name: 'module-dashboard-asman',
  })
}

const isFile = (obj: any): obj is File => {
  return obj instanceof File || (obj && typeof obj === 'object' && 'name' in obj && 'size' in obj && 'type' in obj);
};

const onSelect = (filez: any) => {
  const f = filez.files?.[0]
  if (!f) return
  if (f.size > 10_000_000) { H.alert('error', 'Maksimal file size adalah 10 MB'); return }
  fileMitraExcel.value = f
}

const onRowEditSave = async (event: any) => {
  const { newData, index } = event
  dataSourceHasilLembarKerja.value[index] = newData
  const payload = { data: [newData], sublingkupfk: SUBLINGKUP, norec_detail: NOREC_DETAIL }
  isLoadingSave.value = true
  try { await useApi().post(`/pelaksana/edit-lembar-kerja`, payload) }
  catch (err: any) { console.error(err) }
  finally { isLoadingSave.value = false; fetchData() }
}
const addNewAlat = () => {
  item.value.detailInstruksiKerja.push({ no: item.value.detailInstruksiKerja[item.value.detailInstruksiKerja.length - 1].no + 1 })
}
const removeAlat = (index: any) => {
  item.value.detailInstruksiKerja.splice(index, 1)
  if (item.value.detailInstruksiKerja.length == 0) item.value.detailInstruksiKerja.push({ no: 1 })
}
const addNewAlatStandar = () => {
  item.value.detailPeralatanStandar.push({ no: item.value.detailPeralatanStandar[item.value.detailPeralatanStandar.length - 1].no + 1 })
}
const removeAlatStandar = (index: any) => {
  item.value.detailPeralatanStandar.splice(index, 1)
  if (item.value.detailPeralatanStandar.length == 0) item.value.detailPeralatanStandar.push({ no: 1 })
}

const fetchInstruksiKerja = async (filter: any) => {
  const r = await useApi().get(`general/dropdown/instruksikerja_m?select=id,namainstruksikerja&param_search=namainstruksikerja&query=${filter.query}&limit=10`)
  d_instruksiKerja.value = r
}
const fetchAlatStandar = async (filter: any) => {
  const r = await useApi().get(`pelaksana/alat-standar?param_search=namaalatstandar,namamerk,namatipe,namaserialnumber&query=${filter.query}`)
  d_alatstandar.value = r.data.map((e: any) => ({ label: `${e.namaalatstandar} - ${e.namamerk} ${e.namatipe} (${e.namaserialnumber})`, value: e.id }))
}
const fetchRuangan = async (filter: any) => {
  const r = await useApi().get(`general/dropdown/ruangan_m?select=id,namaruangan&param_search=namaruangan&query=${filter.query}&limit=10`)
  d_ruangan.value = r
}

const detailOrder = async () => {
  const response = await useApi().get(`/penyelia/detail-produk-lembar-kerja?norec_pd=${NOREC_DETAIL}`)
  const data = response.data[0]
  item.value.namaproduk = data.namaproduk
  item.value.namamerk = data.namamerk
  item.value.namatipe = data.namatipe
  item.value.namaserialnumber = data.namaserialnumber
  item.value.durasikalbrasi = data.durasikalbrasi
  item.value.tglkalibrasi = data.tglkalibrasilembarkerja ?? new Date()
  item.value.tempatKalibrasi = { value: data?.idruangan ?? '', label: data?.tempatKalibrasilembarkerja ?? '' }
  item.value.suhu = data.suhulembarkerja
  item.value.kelembabanRelatif = data.kelembabanRelatiflembarkerja
  item.value.evalverifikasi = data.evalverifikasi
  item.value.catatanverifikasi = data.catatanverifikasi
  item.value.detailPeralatanStandar = (data.daftaralatstandar?.length > 0)
    ? data.daftaralatstandar.map(a => ({
      daftaralatstandar: {
        value: a.value ?? '',
        label: `${a.namaalatstandar || ''} - ${a.namamerk || ''} ${a.namatipe || ''} (${a.namaserialnumber || ''})`
      }
    }))
    : [{
      daftaralatstandar: { value: '', label: '' }
    }];
}

const cetakLaporanVerfikasi = () => {
  console.log(dataSourceHasilLembarKerja.value)
  H.printBlade(`asman/cetak-laporan-verifikasi?pdf=true&norec=${dataSourceHasilLembarKerja.value.norecregis}&norec_detail=${dataSourceHasilLembarKerja.value.detailregistraifk}`)
}

detailOrder()
fetchData()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/bedah.scss';
</style>
