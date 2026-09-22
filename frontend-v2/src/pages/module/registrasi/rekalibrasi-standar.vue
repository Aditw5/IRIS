<template>
  <div class="form-layout is-stacked">
    <div class="form-outer" style="margin-top:15px">
      <div :class="[isStuck && 'is-stuck']" class="form-header stuck-header">
        <div class="form-header-inner">
          <div class="left">
            <h1><b>Rekalibrasi Standar Internal</b></h1>
          </div>
          <div class="right">
            <div class="buttons">
              <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="cancelRegistrasi()">
                Batal
              </VButton>
              <VButton type="button" color="primary" rounded outlined raised icon="feather:save" :loading="isLoading"
                @click="saveRegistrasi()"> Simpan
              </VButton>
            </div>
          </div>
        </div>
      </div>
      <div class="form-body">
        <div class="form-section is-grey">
          <div class="form-section-header">
            <div class="left">
            </div>
            <div class="right">
            </div>
          </div>
          <div class="form-section-inner is-horizontal">
            <VField horizontal label=" Tanggal">
              <VDatePicker v-model="item.tglregistrasi" mode="dateTime" style="width: 100%;">
                <template #default="{ inputValue, inputEvents }">
                  <VField>
                    <VControl icon="feather:calendar" fullwidth>
                      <VInput :value="inputValue" v-on="inputEvents" />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </VField>
            <VField horizontal label="Standar Milik Lab" class="is-rounded-select_Z  is-autocomplete-select">
              <VControl icon="fa:user" fullwidth class="prime-auto ">
                <AutoComplete v-model="item.ulab" :suggestions="d_ulab" @complete="fetchulab($event)"
                  :optionLabel="'label'" :dropdown="true" :minLength="3" class="is-input" :appendTo="'body'"
                  :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
            <VField horizontal label="Nama Penanggung Jawab">
              <VControl fullwidth>
                <VInput v-model="item.namapenanggungjawab" placeholder="Nama Penanggung Jawab" />
              </VControl>
            </VField>
            <VField horizontal label="Catatan">
              <VControl fullwidth>
                <VTextarea class="textarea" v-model="item.catatan" rows="4"
                  placeholder="catatan registrasi (optional) ..." autocomplete="off" autocapitalize="off"
                  spellcheck="true" />
              </VControl>
            </VField>
            <VField horizontal label="Lokasi Kalibrasi" class="is-rounded-select_Z  is-autocomplete-select">
              <VControl icon="fas fa-location-arrow" fullwidth class="prime-auto ">
                <AutoComplete v-model="item.lokasi" :suggestions="d_lokasikalibrasi"
                  @complete="fetchlokasiKalibrasi($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                  class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                  placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>
          <Fieldset legend="Daftar Alat Standar" :toggleable="true">
            <div class="mt-5 form-section-inner is-horizontal">
              <!-- wrapper untuk scroll horizontal di tablet/dekstop kecil -->
              <div class="table-responsive">
                <table class="table-po" width="100%">
                  <thead>
                    <tr class="tr-po">
                      <th class="th-po" width="25%" style="text-align:center;">Nama Standar</th>
                      <th class="th-po" width="15%" style="text-align:center;">Lingkup Kalibrasi</th>
                      <th class="th-po" width="15%" style="text-align:center;">Penyelia</th>
                      <th class="th-po" width="15%" style="text-align:center;">Pelaksana</th>
                      <th class="th-po" width="8%" style="text-align:center;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody v-for="(items, index) in input.detailOrderAlat" :key="index">
                    <tr class="tr-po">
                      <td class="td-po" data-label="Nama Alat">
                        <div class="column pt-3 pb-0">
                          <VField>
                            <VControl>
                              <div style="display:flex; align-items:center">
                                <AutoComplete v-model="items.alat" :suggestions="d_alatStandar"
                                  @complete="fetchAlatStandar" optionLabel="label" :dropdown="true" :minLength="3"
                                  class="is-input" appendTo="body" loadingIcon="pi pi-spinner" field="label"
                                  placeholder="ketik untuk mencari..." />
                                <VIconButton class="ml-2" icon="feather:camera" rounded @click="openScanner(items)"
                                  v-tooltip.bubble="'Scan QR Alat'" />
                              </div>
                            </VControl>
                          </VField>
                        </div>
                      </td>
                      <td class="td-po" data-label="Lingkup Kalibrasi">
                        <div class="column pt-3 pb-0">
                          <VField>
                            <VControl>
                              <AutoComplete v-model="items.lingkupkalibrasi" :suggestions="d_lingkup"
                                @complete="fetchLingkup($event)" optionLabel="label" :dropdown="true" :minLength="3"
                                class="is-input" appendTo="body" loadingIcon="pi pi-spinner" field="label"
                                placeholder="ketik untuk mencari..." />
                            </VControl>
                          </VField>
                        </div>
                      </td>
                      <td class="td-po" data-label="Penyelia">
                        <div class="column pt-3 pb-0">
                          <VField>
                            <VControl>
                              <AutoComplete v-model="items.penyeliateknik" :suggestions="d_penyelia"
                                @complete="fetchPenyelia($event)" optionLabel="label" :dropdown="true" :minLength="3"
                                class="is-input" appendTo="body" loadingIcon="pi pi-spinner" field="label"
                                placeholder="ketik untuk mencari..." />
                            </VControl>
                          </VField>
                        </div>
                      </td>
                      <td class="td-po" data-label="Pelaksana">
                        <div class="column pt-3 pb-0">
                          <VField>
                            <VControl>
                              <AutoComplete v-model="items.pelaksana" :suggestions="d_pelaksana"
                                @complete="fetchPelaksana($event)" optionLabel="label" :dropdown="true" :minLength="3"
                                class="is-input" appendTo="body" loadingIcon="pi pi-spinner" field="label"
                                placeholder="ketik untuk mencari..." />
                            </VControl>
                          </VField>
                        </div>
                      </td>
                      <td class="td-po" data-label="Aksi" style="vertical-align:inherit;">
                        <div class="column is-12 pl-0 pr-0">
                          <VButtons style="justify-content: space-around;">
                            <VIconButton type="button" raised circle icon="feather:plus"
                              v-tooltip-prime.bottom="'Tambah'" @click="addNewAlat()" outlined color="info" />
                            <VIconButton type="button" raised circle icon="feather:trash"
                              v-tooltip-prime.bottom="'Hapus'" @click="removeAlat(items)" outlined color="danger" />
                          </VButtons>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </Fieldset>
        </div>
        <div class="form-section is-grey">
          <div class="form-section-inner is-horizontal">
          </div>
        </div>
      </div>
    </div>
  </div>
  <Dialog v-model:visible="showScanner" header="Scan QR Code" :modal="true" :closable="false" style="width:520px">
    <div id="qr-reader" style="width:100%;"></div>
    <div class="p-dialog-footer">
      <VButton label="Cancel" @click="cancelScanner" />
    </div>
  </Dialog>
</template>
<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { h, reactive, ref, computed, defineComponent, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToaster } from '/@src/composable/toaster'
import { useHead } from '@vueuse/head'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import AutoComplete from 'primevue/autocomplete';
import * as qzService from '/@src/utils/qzTrayService'
import Fieldset from 'primevue/fieldset';
import Dialog from 'primevue/dialog'
import { Html5Qrcode } from 'html5-qrcode'
import FileUpload from 'primevue/fileupload';

useHead({
  title: 'Rekalibrasi Standar - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

let ID_MITRA = useRoute().query.nomitrafk as string
const item: any = reactive({
  tglregistrasi: new Date(),
  lokasi: null
})
const mitra: any = ref({})
const isLoading: any = ref(false)
const isDisabled: any = ref(false)
const d_alatStandar = ref([])
const d_lokasikalibrasi = ref([])
const d_ulab = ref([])
const d_lingkup = ref([])
const d_penyelia = ref([])
const d_pelaksana = ref([])
const { y } = useWindowScroll()
const router = useRouter()
const isStuck = computed(() => {
  return y.value > 30
})
const input: any = ref({
  detailOrderAlat: [{
    no: 1
  }]
})
const showScanner = ref(false)
const scannerRowIdx = ref<number | null>(null)
let html5QrCode: Html5Qrcode | null = null
const scannerItem = ref<any>(null)

const fetchPenyelia = async (filter: any) => {
  if (!item.lokasi) { H.alert('warning', 'Lokasi Kalibrasi harus di isi'); return }
  let lokasi = item.lokasi.value
  await useApi().get(
    `registrasi/pegawai-lokasi-kalibrasi?lokasi=${lokasi}&param_search=namalengkap&query=${filter.query}&jenispegawai=${1}`
  ).then((response) => {
    d_penyelia.value = response.data.map((e: any) => ({ label: e.namalengkap, value: e.id }))
  })
}

const fetchPelaksana = async (filter: any) => {
  if (!item.lokasi) { H.alert('warning', 'Lokasi Kalibrasi harus di isi'); return }
  let lokasi = item.lokasi.value
  await useApi().get(
    `registrasi/pegawai-lokasi-kalibrasi?lokasi=${lokasi}&param_search=namalengkap&query=${filter.query}&jenispegawai=${2}`
  ).then((response) => {
    d_pelaksana.value = response.data.map((e: any) => ({ label: e.namalengkap, value: e.id }))
  })
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
    `registrasi/alat-standar-ulab?idulab=${item.ulab.value}&id_alat=${id_alat}`
  )
  if (!data.length) {
    scannerItem.value = null
    return
  }
  const e = data[0]
  const option = {
    label: `${e.namaproduk} - ${e.namamerk} ${e.namatipe} (${e.namaserialnumber})`,
    value: e.id
  }

  d_alatStandar.value = [option, ...d_alatStandar.value.filter(o => o.value !== option.value)]
  scannerItem.value.alat = option
  scannerItem.value = null
}


const addNewAlat = () => {
  input.value.detailOrderAlat.push({
    no: input.value.detailOrderAlat[input.value.detailOrderAlat.length - 1].no + 1
  });
}

const removeAlat = (index: any) => {
  input.value.detailOrderAlat.splice(index, 1)
  if (input.value.detailOrderAlat.length == 0) {
    input.value.detailOrderAlat.push({
      no: 1
    });
  }
}


const fetchAlatStandar = async (filter: any) => {
  if (!item.ulab) { H.alert('warning', 'Pemilik Standar harus di isi'); return }
  await useApi().get(
    `registrasi/alat-standar-ulab?idulab=${item.ulab.value}&param_search=namaalatstandar,namamerk,namatipe,namaserialnumber&query=${filter.query}`
  ).then((response) => {
    d_alatStandar.value = response.data.map((e: any) => {
      return {
        label: `${e.namaalatstandar} - ${e.namamerk} ${e.namatipe} (${e.namaserialnumber})`,
        value: e.id
      };
    });
  });
}

const fetchlokasiKalibrasi = async (filter: any) => {
  await useApi().get(
    `general/dropdown/lokasikalibrasi_m?select=id,lokasi&param_search=lokasi&query=${filter.query}&limit=10`
  ).then((response) => {
    d_lokasikalibrasi.value = response
  })
}

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
  await useApi().get(
    `general/dropdown/lingkupkalibrasi_m?select=id,lingkupkalibrasi&param_search=lingkupkalibrasi&query=${filter.query}&limit=10`
  ).then((response) => {
    d_lingkup.value = response
  })
}

const cancelRegistrasi = () => {
  window.history.back()
}

const saveRegistrasi = async () => {
  if (!item.tglregistrasi) { H.alert('warning', 'Tgl Registrasi harus di isi'); return }
  if (!item.ulab) { H.alert('warning', 'Pemilik Standar harus di isi'); return }
  if (!item.namapenanggungjawab) { H.alert('warning', 'Nama Penanggung Jawab harus di isi'); return }

  const mappedOrderAlat = input.value.detailOrderAlat.map((alat: any) => ({
    namaalatfk: alat.alat?.value || null,
    lingkupkalibrasifk: alat.lingkupkalibrasi?.value || null,
    penyeliateknik: alat.penyeliateknik?.value || null,
    pelaksana: alat.pelaksana?.value || null
  }));

  const formData = new FormData()
  formData.append('norec', '')
  formData.append('nomitrafk', item.ulab.value)
  formData.append('tglregistrasi', H.formatDate(item.tglregistrasi, 'YYYY-MM-DD HH:mm:ss'))
  formData.append('catatan', item.catatan ? item.catatan : null)
  formData.append('lokasikalibrasi', item.lokasi?.value ?? null)
  formData.append('namapenanggungjawab', item.namapenanggungjawab)
  formData.append('mitraregistrasidetail', JSON.stringify(mappedOrderAlat))

  isLoading.value = true
  await useApi().post(`/registrasi/save-registrasi-standar-ulab`, formData).then(async (response: any) => {
    isLoading.value = false
    isDisabled.value = true
    toDashboard()
  }).catch((e: any) => {
    isLoading.value = false
    console.clear()
    console.log(e)
  })
  isLoading.value = false
}

const toDashboard = () => {
  router.push({
    name: 'module-dashboard-registrasi',
  })
}

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';


.table-po {
  width: 100% !important;
  border-collapse: collapse !important;
}

.table-po,
.tr-po,
.th-po,

.th-po,
.td-po {
  padding: 8px !important;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.table-responsive .table-po {
  min-width: 900px;
  /* sesuaikan jika perlu */
}

/* Stacked table di layar ≤768px */
@media only screen and (max-width: 768px) {

  .table-po,
  .tr-po,
  .th-po {
    display: block;
  }

  .th-po {
    position: absolute;
    top: -9999px;
    left: -9999px;
  }

  .td-po {
    display: block;
    position: relative;
    padding-left: 50%;
    border: none;
    border-bottom: 1px solid #ddd;
    text-align: left;
  }

  .td-po:before {
    position: absolute;
    top: 0;
    left: 0;
    width: 45%;
    padding-right: 10px;
    white-space: nowrap;
    content: attr(data-label);
    font-weight: bold;
  }
}


// .dropdown.is-dots .is-trigger {
//     background: var(--red) !important;
// }
// .dropdown.is-dots .is-trigger svg {
//     color: white;
// }</style>
