<template>
  <div class="form-layout is-stacked">
    <div class="business-dashboard hr-dashboard">
      <div class="columns is-multiline">
        <div class="column is-12" v-if="isLoadingMitra">
          <div class="block-green">
            <div class="flex-list-inner mb-4">
              <div class="flex-table-item grid-item mb-4" v-for="key in 1" :key="key">
                <VFlexTableCell :column="{ grow: true, media: true }">
                  <VPlaceloadAvatar size="medium" />
                  <VPlaceloadText :lines="2" width="30%" last-line-width="20%" class="mx-2" />
                </VFlexTableCell>
                <VFlexTableCell>
                  <VPlaceload width="100%" height="70px" class="mx-1 mt-2" />
                </VFlexTableCell>
                <VFlexTableCell>
                  <VPlaceload width="10%" height="20px" class="mx-1 mt-1" />
                </VFlexTableCell>
                <VFlexTableCell :column="{ align: 'end' }">
                  <VPlaceload width="10%" class="mx-1" />
                </VFlexTableCell>
              </div>
            </div>
          </div>
        </div>
        <div class="column is-12" v-if="!isLoadingMitra">
          <div class="block-header">
            <div class="left">
              <div class="current-user">
                <h3>{{ mitra.namaperusahaan }}</h3>
              </div>
            </div>
            <div class="center">
              <div class="columns">
                <div class="column">
                  <h4 class="block-heading">No HP</h4>
                  <p class="block-text">{{ mitra.nohp }}</p>
                  <h4 class="block-heading">Alamat</h4>
                  <p class="block-text">{{ mitra.alamatktr }}
                  </p>
                </div>
              </div>
            </div>
            <div class="right">
              <div class="columns">
                <div class="column">
                  <h4 class="block-heading">Email </h4>
                  <p class="block-text"> {{ mitra.email }}</p>
                  <h4 class="block-heading">Tanggal Daftar</h4>
                  <p class="block-text"> {{ mitra.tgldaftar }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="form-outer" style="margin-top:15px">
      <div :class="[isStuck && 'is-stuck']" class="form-header stuck-header">
        <div class="form-header-inner">
          <div class="left">

          </div>
          <div class="right">
            <div class="buttons">
              <RouterLink :to="{ name: 'module-registrasi-mitra-lama', }" v-if="item.NOREC_PD">
                <VIconButton class="mr-5 is-pulled-right" type="button" color="info" rounded circle raised
                  icon="fas fa-users" v-tooltip.bubble="'Mitra Lama'">
                </VIconButton>
              </RouterLink>
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
            <VField horizontal label="Nama Penanggung Jawab">
              <VControl fullwidth>
                <VInput v-model="item.namapenanggungjawab" placeholder="Nama Penanggung Jawab" />
              </VControl>
            </VField>
            <VField horizontal label="No.Hp Penanggung Jawab">
              <VControl fullwidth>
                <VInput type="tel" v-model="item.nohppenanggungjawab" placeholder="No.HP Penanggung Jawab" />
              </VControl>
            </VField>
            <VField horizontal label="Jabatan Penanggung Jawab">
              <VControl fullwidth>
                <VInput v-model="item.jabatanpenanggungjawab" placeholder="Jabatan Penanggung Jawab" />
              </VControl>
            </VField>
            <VField horizontal label="Catatan">
              <VControl fullwidth>
                <VTextarea class="textarea" v-model="item.catatan" rows="4"
                  placeholder="catatan registrasi (optional) ..." autocomplete="off" autocapitalize="off"
                  spellcheck="true" />
              </VControl>
            </VField>
            <VField horizontal label="Lokasi Repair" class="is-rounded-select_Z  is-autocomplete-select">
              <VControl icon="fa:user-md" fullwidth class="prime-auto ">
                <AutoComplete v-model="item.lokasirepair" :suggestions="d_lokasikalibrasi"
                  @complete="fetchlokasiRepair($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                  class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                  placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>
          <span class="label-pengkajian required-field ml-4"> Upload AMS</span>
          <div class="column is-12">
            <FileUpload v-model="fileAms" mode="advanced" name="demo" accept="application/pdf" :maxFileSize="10000000"
              outlined :invalidFileTypeMessage="'{0}: File yang diupload harus JPEG/JPG.'"
              :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
              style="background-color: transparent; color: var(--danger); border: 1px solid;"
              :chooseLabel="fileAms ? fileAms.name : 'Unggah'" @select="onSelect($event)" class="is-rounded w-100" />
          </div>
          <span class="label-pengkajian ml-4"> Upload List Tools (xls/xlsx)</span>
          <div class="column is-12">
            <FileUpload v-model="fileCustomerTools" mode="advanced" name="demo"
              accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
              :maxFileSize="10000000" outlined :invalidFileTypeMessage="'{0}: File yang diupload harus JPEG/JPG.'"
              :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
              style="background-color: transparent; color: var(--danger); border: 1px solid;"
              :chooseLabel="fileCustomerTools ? fileCustomerTools.name : 'Unggah'" @select="onSelectTools($event)"
              class="is-rounded w-100" />
          </div>
          <Fieldset legend="- Order Alat" :toggleable="true">
            <div class="mt-5 form-section-inner is-horizontal">
              <div class="table-responsive">
                <table class="table-po" width="100%">
                  <thead>
                    <tr class="tr-po">
                      <th class="th-po" width="25%" style="text-align: center;">Nama Alat</th>
                      <th class="th-po" width="8%" style="text-align: center;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody v-for="(items, index) in input.detailOrderAlat" :key="index">
                    <tr class="tr-po">
                      <td class="td-po" data-label="Nama Alat">
                        <div class="column pt-3 pb-0">
                          <VField>
                            <VControl>
                              <div style="display:flex; align-items:center">
                                <AutoComplete v-model="items.alat" :suggestions="d_produk"
                                  @complete="fetchProduk($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                                  class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                                  placeholder="ketik untuk mencari..." />
                                <VIconButton class="ml-2" icon="feather:camera" rounded @click="openScanner(items)"
                                  v-tooltip.bubble="'Scan QR Alat'" />
                              </div>
                            </VControl>
                          </VField>
                        </div>
                      </td>
                      <td class="td-po" data-label="Aksi">
                        <div class="column is-12 pl-0 pr-0">
                          <VButtons style="justify-content: space-around;">
                            <VIconButton type="button" raised circle icon="feather:plus"
                              v-tooltip-prime.bottom="'Tambah'" @click="addNewAlat()" outlined color="info">
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
  title: 'Registrasi - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

let ID_MITRA = useRoute().query.nomitrafk as string
let NOREC_PD = useRoute().query.norec_pd as string
let STATUSMITRA = useRoute().query.statusmitra as string
let NOREGISTRASI = "";

const item: any = reactive({
  tglregistrasi: new Date(),
  NOREC_PD: NOREC_PD != undefined ? NOREC_PD : '',
  NOREGISTRASI: NOREGISTRASI != undefined ? NOREGISTRASI : '',
  lokasirepair: null
})
const mitra: any = ref({})
const isLoading: any = ref(false)
const isDisabled: any = ref(false)
const isLoadingMitra: any = ref(false)
const d_produk = ref([])
const d_lokasikalibrasi = ref([])
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
const fileAms: any = ref()
const fileCustomerTools: any = ref()

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

const onSelect = async (filez: any) => {
  const file = filez.files[0];
  if (!file) return;
  if (file.size > 10000000) {
    H.alert('error', 'Maksimal file size adalah 10 MB');
    return;
  }
  if (file.type !== "application/pdf") {
    H.alert('error', 'File yang diizinkan harus berupa PDF');
    return;
  }
  fileAms.value = file;
}

const onSelectTools = async (filez: any) => {
  const file = filez.files[0];
  if (!file) return;

  if (file.size > 10000000) {
    H.alert('error', 'Maksimal file size adalah 10 MB');
    return;
  }

  const allowedTypes = [
    "application/vnd.ms-excel",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
  ];

  if (!allowedTypes.includes(file.type)) {
    H.alert('error', 'File harus berupa Excel (.xls atau .xlsx)');
    return;
  }

  fileCustomerTools.value = file;
};


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
    `registrasi/produk-by-id?idmitra=${ID_MITRA}&id_alat=${id_alat}`
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

  d_produk.value = [option, ...d_produk.value.filter(o => o.value !== option.value)]
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


const fetchProduk = async (filter: any) => {
  await useApi().get(
    `registrasi/produk-by-id?idmitra=${ID_MITRA}&param_search=namaproduk,namamerk,namatipe,namaserialnumber&query=${filter.query}`
  ).then((response) => {
    d_produk.value = response.data.map((e: any) => {
      return {
        label: `${e.namaproduk} - ${e.namamerk} ${e.namatipe} (${e.namaserialnumber})`,
        value: e.id
      };
    });
  });
}


const fetchlokasiRepair = async (filter: any) => {
  await useApi().get(
    `general/dropdown/lokasikalibrasi_m?select=id,lokasi&param_search=lokasi&query=${filter.query}&limit=10`
  ).then((response) => {
    d_lokasikalibrasi.value = response
  })
}

const mitraByID = (id: any) => {
  isLoadingMitra.value = true
  let paramsEdit = ''
  if (item.NOREC_PD != '') {
    paramsEdit = `&norec_pd=${item.NOREC_PD}}`
  }
  useApi().get(
    `/registrasi/mitra-registrasi?id=${id}${paramsEdit}`).then((response: any) => {
      mitra.value = response.mitra
      isLoadingMitra.value = false
    })
}

const cancelRegistrasi = () => {
  window.history.back()
}

const saveRegistrasi = async () => {
  console.log(item)
  if (!item.tglregistrasi) { H.alert('warning', 'Tgl Registrasi harus di isi'); return }
  if (!item.namapenanggungjawab) { H.alert('warning', 'Nama Penanggung Jawab harus di isi'); return }
  if (!item.jabatanpenanggungjawab) { H.alert('warning', 'Jabatan Penanggung Jawab harus di isi'); return }

  const mappedOrderAlat = input.value.detailOrderAlat.map((alat: any) => ({
    namaalatfk: alat.alat?.value || null
  }));

  const formData = new FormData()
  formData.append('fileAms', fileAms.value)
  formData.append('fileCustomerTools', fileCustomerTools.value ?? null)
  formData.append('norec', item.NOREC_PD ? item.NOREC_PD : '')
  formData.append('nomitrafk', ID_MITRA)
  formData.append('tglregistrasi', H.formatDate(item.tglregistrasi, 'YYYY-MM-DD HH:mm:ss'))
  formData.append('catatan', item.catatan ? item.catatan : null)
  formData.append('statusmitra', STATUSMITRA ? STATUSMITRA : 'LAMA')
  formData.append('namaperusahaan', mitra.value.namaperusahaan)
  formData.append('lokasirepair', item.lokasirepair.value ?? null)
  formData.append('namapenanggungjawab', item.namapenanggungjawab)
  formData.append('nohppenanggungjawab', item.nohppenanggungjawab)
  formData.append('jabatanpenanggungjawab', item.jabatanpenanggungjawab)
  formData.append('mitraregistrasidetail', JSON.stringify(mappedOrderAlat))

  isLoading.value = true
  await useApi().post(`/registrasi/save-registrasi-repair-mitra`, formData).then(async (response: any) => {
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

const cetakBuktiPendaftaran = (e: any) => {
  H.printBlade('registrasi/pemakaian-asuransi/sep?norec_pd=' + e.NOREC_PD + "&pdf=true");
  qzService.printData(`report/bukti-pendaftaran?pdf=true&noregistrasi=${e.NOREC_PD}&norec_pd=${e.NOREC_PD}`, 'BUKTI PENDAFATARN', 1);
}

const clearInput = () => {
  delete item.kelasRawat
}

watch(
  () => item.nohppenanggungjawab,
  (val) => {
    if (!val) return
    if (val.startsWith("08")) {
      item.nohppenanggungjawab = "628" + val.slice(2)
    }
    else if (val.startsWith("0")) {
      item.nohppenanggungjawab = "628" + val.slice(1)
    }
    else if (val.startsWith("8")) {
      item.nohppenanggungjawab = "628" + val.slice(1)
    }
    item.nohppenanggungjawab = item.nohppenanggungjawab.replace(/[^\d]/g, "")
  }
)


mitraByID(ID_MITRA)
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
