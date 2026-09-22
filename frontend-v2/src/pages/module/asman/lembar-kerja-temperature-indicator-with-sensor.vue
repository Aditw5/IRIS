<template>
  <WorksheetHeader :item="item" />

  <div class="column is-12">
    <TabView class="tabview-custom " :scrollable="true">
      <TabPanel>
        <template #header>
          <i class="fas fa-users mr-2" aria-hidden="true"></i>
          <span>Lembar Kerja Sub Lingkup Temperature Indicator With Sensor</span>
          <Badge :value="totalData" v-if="totalData > 0" severity="danger" class="ml-2" />
        </template>
        <VCard>
          <div class="column is-12">
            <WorksheetMetadataCard :item="item" :norec="NOREC_DETAIL" role="asman" />
            <WorksheetAttachments :norec="NOREC_DETAIL" role="asman" />
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Upload Lembar Kerja Sub Lingkup Temperature Indicator With Sensor
                </h3>
              </div>
            </div>
            <DataTable :value="dataSourceHasilLembarKerja" dataKey="no" rowGroupMode="rowspan" groupRowsBy="group"
              :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
              class="p-datatable-customers p-datatable-sm" filterDisplay="menu"
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
                  <Column header="group" />
                  <Column header="Set Poin" :colspan="2" />
                  <Column header="Penunjukan Standar" :colspan="2" />
                  <Column header="Pembacaan Alat" :colspan="2" />
                  <Column header="koreksi" :colspan="2" />
                  <Column header="Ketidakpastian" :colspan="2" />
                </Row>
              </ColumnGroup>
              <Column field="no" />
              <Column :rowspan="4" field="group" />
              <Column field="set_poin">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="set_poin_satuan" />
              <Column field="penunjukan_standar">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="penunjukan_standar_satuan" />
              <Column field="pembacaan_alat">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="pembacaan_alat_satuan" />
              <Column field="koreksi">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="koreksi_satuan" />
              <Column field="ketidakpastian">
                <template #editor="{ data, field }">
                  <VField>
                    <VControl>
                      <VInput v-model="data[field]" />
                    </VControl>
                  </VField>
                </template>
              </Column>
              <Column field="ketidakpastian_standar" />
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
import { ref, computed, reactive } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useViewWrapper } from '/@src/stores/viewWrapper';
import { useHead } from '@vueuse/head';
import * as H from '/@src/utils/appHelper';
import { useApi } from '/@src/composable/useApi';
import { useUserSession } from '/@src/stores/userSession'
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';

useHead({
  title: 'Lembar Kerja Temperature Indicator With Sensor - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

let NOREC_DETAIL = useRoute().query.norec_detail as string
const isLoading = ref(false)
const isLoadingSave = ref(false)
let loadSearch: any = ref(false)
const dataSourceHasilLembarKerja: any = ref([])
const router = useRouter()
const dataExcel: any = ref()
const item: any = ref({
  filterTgl: reactive({
    start: new Date(),
    end: new Date(),
  }),
  tglkalibrasi: new Date(),
})
const totalData = computed(() => dataSourceHasilLembarKerja.value.length)
const modalPenolakanSertifikat: any = ref(false)

const batalRegis = async (e: any) => {
  console.log(e[0])
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
  await useApi().get(`asman/get-lembar-kerja-temperature-indicator?norecdetail=${NOREC_DETAIL}`).then((response: any) => {
    response.forEach((element: any, i: any) => {
      element.no = i + 1
    });
    dataSourceHasilLembarKerja.value = response
  }).catch((err) => {
    dataSourceHasilLembarKerja.value = []
  })
  loadSearch.value = false
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
    item.value.tempatKalibrasi = {
      value: data?.idruangan ?? '',
      label: data?.tempatKalibrasilembarkerja ?? ''
    };
  item.value.resolusi = data.resolusi
  item.value.suhu = data.suhulembarkerja
  item.value.kelembabanRelatif = data.kelembabanRelatiflembarkerja
  item.value.notes = data.noteslembarkerja
  item.value.jenissensor = data.jenissensor

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
