<template>
  <WorksheetHeader :item="item" />

  <div class="column is-12">
    <TabView class="tabview-custom " :scrollable="true" @tab-click="klikTab($event)">
      <TabPanel>
        <template #header>
          <i class="fas fa-pager mr-2" aria-hidden="true"></i>
          <span>Lembar Kerja Sub Lingkup Clamp</span>
          <Badge :value="totalData" v-if="totalData > 0" severity="danger" class="ml-2" />
        </template>
        <VCard>
          <div class="column is-12">
            <WorksheetMetadataCard :item="item" :norec="NOREC_DETAIL" role="asman" />
            <WorksheetAttachments :norec="NOREC_DETAIL" role="asman" />
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Upload Lembar Kerja Sub Lingkup Clamp </h3>
              </div>
            </div>
            <DataTable rowGroupMode="rowspan" groupRowsBy="group" :value="dataSourceHasilLembarKerja" :paginator="true"
              :rows="10" :rowsPerPageOptions="[5, 10, 25]" class="p-datatable-customers p-datatable-sm"
              filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple" showGridlines
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :loading="isLoading">
              <template #header>
                <div class="columns is-multiline is-vcentered is-mobile pt-0 pb-0" style="gap: 0.25rem;">
                  <div class="column is-narrow" v-if="dataSourceHasilLembarKerja.length > 0">
                    <VButton color="primary" @click="cetakSertifikatLembarKerja()" outlined icon="feather:printer">
                      Cetak Sertifikat
                    </VButton>
                  </div>

                  <div class="column is-narrow" v-if="dataSourceHasilLembarKerja.length > 0">
                    <VButton color="info" @click="setujuiSertifikat()" outlined icon="feather:save"
                      :loading="isLoadingSave">
                      Setujui Sertifikat
                    </VButton>
                  </div>

                  <div class="column is-narrow" v-if="dataSourceHasilLembarKerja.length > 0">
                    <VButton color="danger" outlined icon="feather:trash"
                      @click="batalRegis(dataSourceHasilLembarKerja)">
                      Tolak Sertifikat
                    </VButton>
                  </div>
                </div>

              </template>
              <ColumnGroup type="header">
                <Row>
                  <Column header="No" />
                  <Column header="Group" />
                  <Column header="Rentang" :colspan="2" />
                  <Column header="Penyetelan Sumber Arus" :colspan="4" />
                  <Column header="Pembacaan Alat" :colspan="2" />
                  <Column header="Koreksi" :colspan="2" />
                  <Column header="Ketidakpastian" :colspan="2" />
                </Row>
              </ColumnGroup>
              <Column field="no" header="No" />
              <Column field="group" header="Group" />
              <Column field="rentang" header="Rentang" />
              <Column field="rentang_satuan" header="" />
              <Column field="penyetelan" header="Penyetelan" />
              <Column field="penyetelan_satuan" header="" />
              <Column field="keluaran" header="Keluaran" />
              <Column field="keluaran_satuan" header="" />
              <Column field="pembacaan_alat" header="Pembacaan Alat" />
              <Column field="pembacaan_alat_satuan" header="" />
              <Column field="koreksi" header="Koreksi" />
              <Column field="koreksi_satuan" header="" />
              <Column field="ketidakpastian" header="Ketidakpastian" />
              <Column field="ketidakpastian_satuan" header="" />
            </DataTable>
          </div>
        </VCard>
      </TabPanel>
    </TabView>
  </div>
  <VModal :open="modalPenolakanSertifikat" title="Tolak Sertifikat" size="medium" actions="right"
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
                placeholder="Alasan Pembatalan" autocomplete="off" autocapitalize="off" spellcheck="true" />
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
import { ref, computed, watch, reactive } from 'vue';
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

useHead({
  title: 'Lembar Kerja Clamp- ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

let NOREC_DETAIL = useRoute().query.norec_detail as string
let SUBLINGKUP = useRoute().query.sublingkup as string
let NOREC = useRoute().query.norec as string
const isLoading: Boolean = ref(false)
const isLoadingUpload: Boolean = ref(false)
const isLoadingSave: Boolean = ref(false)
const totalSizePercent: Number = ref(0)
const totalSize: Number = ref(0)
const valueProgress: Number = ref(0)
let loadSearch: any = ref(false)
const dataSource: any = ref([])
const dataSourceHasilLembarKerja: any = ref([])
const filterd = ref('')
const arr3 = ref([])
const router = useRouter()
const fileMitraExcel: any = ref()
const dataExcel: any = ref()
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
const onUpload = () => {

}
const modalPenolakanSertifikat: any = ref(false)

const batalRegis = async (e: any) => {
  item.value.norecregis = e[0].detailregistraifk
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

const downloadFileTerunggah = () => {
  const norec = NOREC_DETAIL
  const token = useUserSession().token
  const url = `/service/asman/download-file-terunggah?norec=${norec}&token=${token}`
  window.open(url, '_blank')
}

const setujuiSertifikat = async () => {
  let json = {
    'verif': {
      'norec': dataSourceHasilLembarKerja.value[0].detailregistraifk ?? '',
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

const fetchData = async () => {
  loadSearch.value = true
  await useApi().get(`asman/get-lembar-kerja-clamp?norecdetail=${NOREC_DETAIL}`).then((response: any) => {
    response.forEach((element: any, i: any) => {
      element.no = i + 1
    });
    dataSourceHasilLembarKerja.value = response
  }).catch((err) => {
    dataSourceHasilLembarKerja.value = []
  })
  loadSearch.value = false
}

const kembali = () => {
  window.history.back()
}

const detailOrder = async () => {
  const response = await useApi().get(`/penyelia/detail-produk-lembar-kerja?norec_pd=${NOREC_DETAIL}`)
  const data = response.data[0]

  item.value.namaproduk = data.namaproduk
  item.value.namamerk = data.namamerk
  item.value.namatipe = data.namatipe
  item.value.namaserialnumber = data.namaserialnumber

  item.value.fotoproduk = data.fotoproduk
  // worksheet metadata preview
  item.value.idruangan = data.idruangan
  item.value.tglkalibrasi = data.tglkalibrasilembarkerja || null
  item.value.tempatKalibrasi = { value: data.idruangan || '', label: data.tempatKalibrasilembarkerja || '' }
  item.value.suhu = data.suhulembarkerja
  item.value.kelembabanRelatif = data.kelembabanRelatiflembarkerja
  item.value.notes = data.noteslembarkerja
  item.value.gambarsuhu = data.gambarsuhu
  item.value.detailInstruksiKerja = (data.daftarinstruksikerja || []).map((entry: any) => ({
    daftarInstruksiKerja: { value: entry.value, label: entry.label }
  }))
  item.value.detailPeralatanStandar = (data.daftaralatstandar || []).map((entry: any) => ({
    daftaralatstandar: {
      value: entry.value,
      label: `${entry.namaalatstandar || ''} - ${entry.namamerk || ''} ${entry.namatipe || ''} (${entry.namaserialnumber || ''})`
    }
  }))
  item.value.durasikalbrasi = data.durasikalbrasi
  item.value.tglkalibrasi = data.tglkalibrasilembarkerja ?? new Date(),
    item.value.tempatKalibrasi = data.tempatKalibrasilembarkerja
  item.value.suhu = data.suhulembarkerja
  item.value.kelembabanRelatif = data.kelembabanRelatiflembarkerja
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
        label: a.label ?? ''
      },
      merkalatstandar: {
        value: a.idmrek ?? '',
        label: a.namamerk ?? ''
      },
      tipealatstandar: {
        value: a.idtipe ?? '',
        label: a.namatipe ?? ''
      },
      serialalatstandar: {
        value: a.idsn ?? '',
        label: a.namaserialnumber ?? ''
      },
    }))
    : [{
      daftaralatstandar: { value: '', label: '' },
      merkalatstandar: { value: '', label: '' },
      tipealatstandar: { value: '', label: '' },
      serialalatstandar: { value: '', label: '' }
    }];


  const norec = NOREC_DETAIL
  const r = await useApi().get(`/penyelia/excel-length?norec=${norec}`)
  dataExcel.value = r.data.namafileexcel

}

const cetakSertifikatLembarKerja = () => {
  H.printBlade(`asman/cetak-sertifikat-lembar-kerja?pdf=true&norec=${dataSourceHasilLembarKerja.value[0].norecregis}&norec_detail=${dataSourceHasilLembarKerja.value[0].detailregistraifk}`);
}

detailOrder()
fetchData()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/module/dashboard/bedah.scss';
</style>
