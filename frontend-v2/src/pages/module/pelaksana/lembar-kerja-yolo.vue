<template>
  <WorksheetHeader :item="item" />

  <div class="column is-12">
    <TabView class="tabview-custom " :scrollable="true" @tab-click="klikTab($event)">
      <TabPanel>
        <template #header>
          <i class="fas fa-users mr-2" aria-hidden="true"></i>
          <span>Lembar Kerja AI</span>
          <Badge :value="totalData" v-if="totalData > 0" severity="danger" class="ml-2" />
        </template>
        <VCard>
          <div class="column is-12">
            <div class="columns is-multiline">
              <div class="column is-3">
                <VField label=" Tanggal Kalibrasi">
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
                <VField label="Tempat Kalibrasi">
                  <AutoComplete v-model="item.tempatKalibrasi" :suggestions="d_ruangan" @complete="fetchRuangan($event)"
                    :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                    :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
                </VField>
              </div>
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
              <div class="column is-12">
                <VField label="Catatan/Notes">
                  <VControl>
                    <VTextarea v-model="item.notes" rows="4" placeholder="Catatan/Notes">
                    </VTextarea>
                  </VControl>
                </VField>
              </div>
            </div>
            <div class="columns is-multiline">
              <div class="column is-6">
                <Fieldset legend="- Instruksi Kerja" :toggleable="true">
                  <div style="overflow-y:auto;" class="mt-5 form-section-inner is-horizontal">
                    <table width="100%">
                      <thead>
                        <tr class="tr-po">
                          <th class="th-po" width="25%" style="vertical-align:inherit;text-align: center;">Nama
                            Instruksi
                            Kerja
                            <WorksheetMasterButton kind="ik" />
                          </th>
                          <th class="th-po" width="8%" style="vertical-align:inherit;text-align: center;">Aksi</th>
                        </tr>
                      </thead>
                      <tbody v-for="(items, index) in item.detailInstruksiKerja" :key="index">
                        <tr class="tr-po">
                          <td class="td-po">
                            <div class="column pt-3 pb-0">
                              <VField>
                                <VControl>
                                  <WorksheetInstructionPicker v-model="items.daftarInstruksiKerja" />
                                </VControl>
                              </VField>
                            </div>
                          </td>
                          <td class="td-po" style="vertical-align: inherit;">
                            <div class="column is-12 pl-0 pr-0">
                              <VButtons style="justify-content: space-around;">
                                <VIconButton type="button" raised circle icon="feather:plus"
                                  v-tooltip-prime.bottom="'Tambah'" @click="addNewAlat(items)" outlined color="info">
                                </VIconButton>
                                <VIconButton type="button" raised circle v-tooltip-prime.bottom="'Hapus'" outlined
                                  icon="feather:trash" @click="removeAlat(items)" color="danger">
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
              <div class="column is-6">
                <Fieldset legend="- Daftar Peralatan Standar" :toggleable="true">
                  <div style="overflow-y:auto;" class="mt-5 form-section-inner is-horizontal">
                    <table width="100%">
                      <thead>
                        <tr class="tr-po">
                          <th class="th-po" width="25%" style="vertical-align:inherit;text-align: center;">Nama Alat
                            Standar
                            <WorksheetMasterButton kind="standar" />
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
                                  v-tooltip-prime.bottom="'Tambah'" @click="addNewAlatStandar(items)" outlined
                                  color="info">
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
            </div>
            <!-- <div class="column is-12">
              <FileUpload v-model="fileMeter" mode="advanced" name="gambar" accept="image/jpeg,image/jpg,image/png"
                :maxFileSize="10000000"
                :invalidFileTypeMessage="'{0}: File yang diupload harus berupa JPG, JPEG, atau PNG.'"
                :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
                :chooseLabel="isFile(fileMeter) ? fileMeter.name : item.gambarsuhu || 'Unggah Gambar'"
                @select="onSelectMeter($event)" class="is-rounded w-100" />
            </div> -->
          </div>
          <div class="column is-3 mt-4">
            <VButton icon="feather:save" @click="Save()" :loading="isLoadingSave" color="info">Simpan</VButton>
          </div>
        </VCard>
        <hr>
        <VCard>
          <div class="column is-12">
            <WorksheetMetadataCard :item="item" :norec="NOREC_DETAIL" role="pelaksana" editable />
            <WorksheetAttachments :norec="NOREC_DETAIL" role="pelaksana" />
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Lembar Kerja AI </h3>
              </div>
            </div>
            <DataTable :value="dataSourceHasilLembarKerja" editMode="row" dataKey="no"
              @row-edit-save="onRowEditSave" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
              class="p-datatable-customers p-datatable-sm" filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple" showGridlines
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :loading="isLoading">
              <template #header>
                <div class="column pt-0 pb-0" v-if="item.suhu > 0">
                  <VButtons style="justify-content: space-between;">
                    <VButton color="primary" @click="cetakSertifikatLembarKerja()" outlined icon="feather:printer">
                      Cetak Sertifikat
                    </VButton>
                  </VButtons>
                </div>
              </template>
              <ColumnGroup type="header">
                <Row>
                  <Column header="Rentang" />
                  <Column header="Penunjukan Standar" />
                  <Column header="Pembacaan Alat" />
                  <Column header="Koreksi" />
                  <Column header="Ketidakpastian" />
                </Row>
              </ColumnGroup>
              <Column field="rentang" header="Rentang">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="penunjukan_standar" header="Penunjukan Standar">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="pembacaan_alat" header="Pembacaan Alat">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="koreksi" header="Koreksi">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="ketidakpastian" header="Ketidakpastian">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
            </DataTable>
          </div>
        </VCard>
      </TabPanel>
      <TabPanel>
        <template #header>
          <i class="fas fa-upload mr-2" aria-hidden="true"></i>
          <span>Upload FIle</span>
          <Badge :value="totalData" v-if="totalData > 0" severity="danger" class="ml-2" />
        </template>
        <VCard>
          <WorksheetAttachments :norec="NOREC_DETAIL" role="pelaksana" upload-enabled />
          <div v-if="false" class="colum is-12">
            <div class="columns is-multiline">
              <div class="column is-12 mt-4" style="text-align: right;">
                <VButton icon="feather:save" @click="simpanExcelLembarKerja()" :loading="isLoadingSave" color="info">
                  Simpan
                </VButton>
              </div>
              <div class="column is-12">
                <FileUpload v-model="fileMitraExcel" mode="advanced" name="demo"
                  accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                  :maxFileSize="10000000" @upload="onUpload" outlined
                  :invalidFileTypeMessage="'{0}: File yang diupload harus CSV atau Excel (XLS/XLSX).'"
                  :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
                  style="background-color: transparent; color: var(--danger); border: 1px solid;"
                  :chooseLabel="fileMitraExcel ? fileMitraExcel.name : 'Unggah'" @select="onSelect($event)"
                  class="is-rounded w-100" />
              </div>
              <div class="column" v-if="dataExcel != null">
                <div class="column is-12 mt-4-min">
                  <div class="dataTable-bottom mt-2">
                    <div class="dataTable-info" style="font-style:italic">
                      File Excel Terunggah {{ dataExcel }}
                    </div>
                  </div>
                  <div class="mt-2">
                  </div>
                </div>
              </div>

            </div>
          </div>
        </VCard>
      </TabPanel>
    </TabView>
  </div>
  <Dialog v-model:visible="showScanner" header="Scan QR Code" :modal="true" :closable="false" style="width:520px">
    <div id="qr-reader" style="width:100%;"></div>
    <div class="p-dialog-footer">
      <VButton @click="cancelScanner" type="button" icon="feather:trash" class="mr-3 mt-4" color="info" outlined raised>
        Batal
      </VButton>
    </div>
  </Dialog>
</template>
<script setup lang="ts">
import WorksheetInstructionPicker from '/@src/components/worksheet/WorksheetInstructionPicker.vue'
import WorksheetMasterButton from '/@src/components/worksheet/WorksheetMasterButton.vue'
import { useRoute, useRouter } from 'vue-router';
import { ref, computed, watch, reactive, nextTick } from 'vue';
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
import * as XLSX from "xlsx";
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import AutoComplete from 'primevue/autocomplete';
import Fieldset from 'primevue/fieldset';
import Dialog from 'primevue/dialog'
import { Html5Qrcode } from 'html5-qrcode'

useHead({
  title: 'Lembar Kerja AI - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

let NOREC_DETAIL = useRoute().query.norec_detail as string
let SUBLINGKUP = useRoute().query.sublingkup as string

const isLoading = ref(false)
const isLoadingUpload = ref(false)
const isLoadingSave = ref(false)
const totalSizePercent = ref(0)
const totalSize = ref(0)
const valueProgress = ref(0)
const totalData = ref(0)
let loadSearch = ref(false)
const dataSource: any = ref([])
const dataSourceHasilLembarKerja: any = ref([])
const router = useRouter()
const fileMitraExcel: any = ref()
const dataExcel: any = ref()
const showScanner = ref(false)
const scannerRowIdx = ref<number | null>(null)
let html5QrCode: Html5Qrcode | null = null
const scannerItem = ref<any>(null)
const editingRows = ref([]);


const item: any = ref({
  filterTgl: reactive({
    start: new Date(),
    end: new Date(),
  }),
  tglkalibrasi: new Date(),
  detailInstruksiKerja: [{
    no: 1
  }],
  detailPeralatanStandar: [{
    no: 1
  }]
})

const d_alatstandar = ref<{ label: string; value: any }[]>([])
const d_ruangan = ref<any[]>([])
const fileMeter: any = ref()
const onUpload = () => {

}

const isFile = (obj: any): obj is File => {
  return obj instanceof File || (obj && typeof obj === 'object' && 'name' in obj && 'size' in obj && 'type' in obj);
};

const onRowEditSave = async (event: any) => {
  const { newData, index } = event
  dataSourceHasilLembarKerja.value[index] = newData
  const payload = {
    data: [newData],
    sublingkupfk: SUBLINGKUP,
    norec_detail: NOREC_DETAIL
  }
  console.log(payload)
  isLoadingSave.value = true
  try {
    await useApi().post(
      `/pelaksana/edit-lembar-kerja`,
      payload
    )
  }
  catch (err: any) {
    console.error(err)
  }
  finally {
    isLoadingSave.value = false
    fetchData()
  }
}

const addNewAlat = () => {
  item.value.detailInstruksiKerja.push({
    no: item.value.detailInstruksiKerja[item.value.detailInstruksiKerja.length - 1].no + 1
  });
}

const removeAlat = (index: any) => {
  item.value.detailInstruksiKerja.splice(index, 1)
  if (item.value.detailInstruksiKerja.length == 0) {
    item.value.detailInstruksiKerja.push({
      no: 1
    });
  }
}

const addNewAlatStandar = () => {
  item.value.detailPeralatanStandar.push({
    no: item.value.detailPeralatanStandar[item.value.detailPeralatanStandar.length - 1].no + 1
  });
}

const removeAlatStandar = (index: any) => {
  item.value.detailPeralatanStandar.splice(index, 1)
  if (item.value.detailPeralatanStandar.length == 0) {
    item.value.detailPeralatanStandar.push({
      no: 1
    });
  }
}


async function openScanner(row: any) {
  scannerItem.value = row
  showScanner.value = true
  await nextTick()
  html5QrCode = new Html5Qrcode("qr-reader")
  html5QrCode
    .start(
      { facingMode: "environment" },
      { fps: 10, qrbox: 250 },
      onScanSuccess,
      err => console.warn(err)
    )
    .catch(err => {
      console.error("Failed to start QR scanner", err)
      cancelScanner()
    })
}



async function cancelScanner() {
  showScanner.value = false
  if (html5QrCode) {
    await html5QrCode.stop().catch(() => { })
    html5QrCode = null
  }
  scannerRowIdx.value = null
}

async function onScanSuccess(decodedText: string) {
  await html5QrCode?.stop().catch(() => { })
  showScanner.value = false
  let id_alat: string | null = null
  try {
    const url = new URL(decodedText)
    id_alat = url.searchParams.get("id_alat")
  } catch {
    console.error("QR does not contain a valid URL:", decodedText)
  }
  if (!id_alat || !scannerItem.value) {
    scannerItem.value = null
    return
  }

  const { data } = await useApi().get(
    `pelaksana/alat-standar?id_alat=${id_alat}`
  )
  if (!data.length) {
    scannerItem.value = null
    return
  }
  const e = data[0]
  const option = {
    label: `${e.namaalatstandar} - ${e.namamerk} ${e.namatipe} (${e.namaserialnumber})`,
    value: e.id
  }

  d_alatstandar.value = [option, ...d_alatstandar.value.filter(o => o.value !== option.value)]
  scannerItem.value.alat = option
  scannerItem.value = null
}


const fetchAlatStandar = async (filter: any) => {
  await useApi().get(
    `pelaksana/alat-standar?param_search=namaalatstandar,namamerk,namatipe,namaserialnumber&query=${filter.query}`
  ).then((response) => {
    d_alatstandar.value = response.data.map((e: any) => {
      return {
        label: `${e.namaalatstandar} - ${e.namamerk} ${e.namatipe} (${e.namaserialnumber})`,
        value: e.id
      };
    });
  });
}

const fetchRuangan = async (filter: any) => {
  await useApi().get(
    `general/dropdown/ruangan_m?select=id,namaruangan&param_search=namaruangan&query=${filter.query}&limit=10`
  ).then((response) => {
    d_ruangan.value = response
  })
}

const downloadFileTerunggah = () => {
  const norec = NOREC_DETAIL
  const token = useUserSession().token
  const url = `/service/pelaksana/download-file-terunggah?norec=${norec}&token=${token}`
  window.open(url, '_blank')
}

const fetchData = async () => {
  loadSearch.value = true
  await useApi().get(`pelaksana/get-lembar-kerja-AI?norecdetail=${NOREC_DETAIL}`).then((response: any) => {
    response.forEach((element: any, i: any) => {
      element.no = i + 1
    });
    dataSourceHasilLembarKerja.value = response
  }).catch((err) => {
    dataSourceHasilLembarKerja.value = []
  })
  loadSearch.value = false
}

const onSelect = async (filez: any) => {
  const file = filez.files[0];

  console.log(file.size);
  if (file.size > 10000000) {
    H.alert('error', 'Maksimal file size adalah 10 MB');
    return;
  }

  fileMitraExcel.value = file;
};


const simpanExcelLembarKerja = async () => {
  if (!fileMitraExcel.value) {
    H.alert('error', 'File harus diunggah')
    return
  }

  const formData = new FormData()
  formData.append('fileMitraExcel', fileMitraExcel.value)
  formData.append('norec', NOREC_DETAIL)

  isLoadingSave.value = true

  try {
    const r = await useApi().post('/pelaksana/save-excel-lembar-kerja', formData)
    fileMitraExcel.value = null

    H.alert('success', 'File berhasil diunggah')
  } catch (error: any) {
    console.error('Error saat menyimpan berkas excel:', error)
    if (error.response) {
      H.alert('error', `Kesalahan: ${error.response.status} - ${error.response.data.message || 'Gagal menyimpan berkas mitra'}`)
    } else if (error.request) {
      H.alert('error', 'Tidak ada respons dari server. Silakan coba lagi.')
    } else {
      H.alert('error', `Terjadi kesalahan: ${error.message}`)
    }
  } finally {
    isLoadingSave.value = false
  }
}


const Save = async () => {
  if (!item.value.tglkalibrasi) { H.alert('warning', 'Tgl Kalibrasi harus di isi'); return }
  if (!item.value.tempatKalibrasi.value) { H.alert('warning', 'Tempat Kalibrasi harus di isi'); return }
  if (!item.value.suhu) { H.alert('warning', 'Suhu harus di isi'); return }
  if (!item.value.kelembabanRelatif) { H.alert('warning', 'Kelembaban Relatif harus di isi'); return }

  const mappInstruksiKinerja = (item.value.detailInstruksiKerja || []).map((it: any) => ({
    instruksikerja: it?.daftarInstruksiKerja?.value ?? null,
  }))
  const mappPeralatanStandar = (item.value.detailPeralatanStandar || []).map((it: any) => ({
    peralatanstandar: it?.daftaralatstandar?.value ?? null,
  }))

  const tempatKalibrasi =
    typeof item.value.tempatKalibrasi === 'object'
      ? (item.value.tempatKalibrasi?.value ?? '')
      : item.value.tempatKalibrasi

  const formData = new FormData()
  if (isFile(fileMeter.value)) {
    formData.append('fileMeter', fileMeter.value);
  } else if (item.value.gambarsuhu) {
    formData.append('namaFileLama', item.value.gambarsuhu);
  }
  formData.append('fileMeter', fileMeter.value)
  formData.append('sublingkupfk', SUBLINGKUP)
  formData.append('fileName', item.fileName)
  formData.append('norec_detail', NOREC_DETAIL)
  formData.append('tglkalibrasi', H.formatDate(item.value.tglkalibrasi, 'YYYY-MM-DD'))
  formData.append('tempatKalibrasi', tempatKalibrasi)
  formData.append('suhu', item.value.suhu)
  formData.append('notes', item.value.notes)
  formData.append('kelembabanRelatif', item.value.kelembabanRelatif)
  formData.append('daftarinstruksikerja', JSON.stringify(mappInstruksiKinerja))
  formData.append('daftarperalatanstandar', JSON.stringify(mappPeralatanStandar))

  try {
    isLoadingSave.value = true
    await useApi().post('/pelaksana/save-data-upload-lembar-kerja-ai', formData)
    isLoadingSave.value = false
    totalSizePercent.value = 0
    dataSource.value = []
    valueProgress.value = 0
    fetchData()
  } catch (e) {
    isLoadingSave.value = false
  }
}

const klikTab = (event: any) => {
  // Handle tab click event
}

const kembali = () => {
  window.history.back()
}
const collection = () => {
  if (!item.fileName) {
    H.alert("warning", "Data Kosong !");
    return;
  }
  H.cacheHelper().set('periodeTransaksiPencatatanPiutangDaftarLayanan', {
    key: 'bpjs_klaim_inacbgs',
    fileName: item.fileName
  })
  router.push({
    name: 'module-piutang-collection-piutang',
    query: {
      start: moment(new Date()).format('YYYY-MM-DD'),
      end: moment(new Date()).format('YYYY-MM-DD')
    },
  })
}

const detailOrder = async () => {
  const response = await useApi().get(`/pelaksana/detail-produk-lembar-kerja?norec_pd=${NOREC_DETAIL}`)
  const data = response.data[0]

  item.value.namaproduk = data.namaproduk
  item.value.namamerk = data.namamerk
  item.value.namatipe = data.namatipe
  item.value.namaserialnumber = data.namaserialnumber

  item.value.fotoproduk = data.fotoproduk
  item.value.durasikalbrasi = data.durasikalbrasi
  item.value.tglkalibrasi = data.tglkalibrasilembarkerja ?? new Date(),
    item.value.tempatKalibrasi = {
      value: data?.idruangan ?? '',
      label: data?.tempatKalibrasilembarkerja ?? ''
    };
  item.value.suhu = data.suhulembarkerja
  item.value.kelembabanRelatif = data.kelembabanRelatiflembarkerja
  item.value.gambarsuhu = data.gambarsuhu;
  item.value.notes = data.noteslembarkerja
  item.value.detailInstruksiKerja = (data.daftarinstruksikerja?.length > 0)
    ? data.daftarinstruksikerja.map(i => ({
      daftarInstruksiKerja: {
        value: i.value ?? '',
        label: i.label ?? ''
      }
    }))
    : [{ daftarInstruksiKerja: { value: '', label: '' } }]
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


  const norec = NOREC_DETAIL
  const r = await useApi().get(`/pelaksana/excel-length?norec=${norec}`)
  dataExcel.value = r.data.namafileexcel

}


const cetakSertifikatLembarKerja = () => {
  console.log(dataSourceHasilLembarKerja.value)

  H.printBlade(`pelaksana/cetak-sertifikat-lembar-kerja?pdf=true&norec=${dataSourceHasilLembarKerja.value[0].norecregis}&norec_detail=${dataSourceHasilLembarKerja.value[0].detailregistraifk}`);
}


detailOrder()
fetchData()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/bedah.scss';
</style>
