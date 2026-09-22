<template>
  <div class="column">
    <VCard>
      <div class="column is-12">
        <div class="search-widget">
          <div class="field">
            <div class="columns is-multiline">
              <div class="column is-4 pt-0 pb-0">
                <span>Periode</span>
                <VDatePicker v-model="item.qFilterTgl" is-range color="pink" trim-weeks class="pt-2">
                  <template #default="{ inputValue, inputEvents }">
                    <VField addons>
                      <VControl icon="feather:calendar">
                        <VInput :value="inputValue.start" class="input-calendar" v-on="inputEvents.start" />
                      </VControl>
                      <VControl>
                        <VButton static><i class="fas fa-arrow-right" aria-hidden="true"></i></VButton>
                      </VControl>
                      <VControl icon="feather:calendar">
                        <VInput :value="inputValue.end" class="input-calendar" v-on="inputEvents.end" />
                      </VControl>
                    </VField>
                  </template>
                </VDatePicker>
              </div>
              <div class="column is-6 mt-4">
                <input type="text" v-model="item.search" v-on:keyup.enter="fetchData()" class="input"
                  placeholder="Search..." />
              </div>
              <div class="column mt-4" style="margin-left: auto:  !important;">
                <VIconButton type="button" color="success" class="searcv-button" raised icon="fas fa-search"
                  @click="fetchData()" :loading="isPlaceLoad">
                </VIconButton>
              </div>
            </div>
          </div>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column">
    <VCard>
      <h3 class="title is-5 mb-2">Laporan Alat Pelaksana</h3>
      <div class="column" v-if="isPlaceLoad">
        <VPlaceloadWrap v-for="data in 25">
          <VPlaceload class="mx-2 mb-3" />
          <VPlaceload class="mx-2" />
        </VPlaceloadWrap>
      </div>
      <div class="column" v-else>
        <VPlaceholderPage v-if="dataSource.length == 0" title="Data Tidak di Temukan."
          subtitle="Silakan filter pencarian di tanggal lain" larger>
          <template #image>
            <img class="light-image" src="/@src/assets/illustrations/placeholders/search-1.svg" alt="" />
            <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-1-dark.svg" alt="" />
          </template>
        </VPlaceholderPage>

        <div v-else>
          <DataTable :value="dataSource" class="p-datatable-sm" :loading="isLoading" :paginator="true" :rows="10"
            :rowsPerPageOptions="[5, 10, 25]" scrollable
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

            <template #header>
              <div class="flex flex-wrap align-items-center justify-content-between gap-2">
                <VButton color="warning" class="mr-4 mb-3" icon="fas fa-file-excel" raised @click="exportExcel()">
                  Export
                  to
                  Excel </VButton>
              </div>
            </template>

            <Column field="no" header="#" frozen></Column>
            <Column field="namaproduk" header="Nama Alat" frozen :sortable="true" style="min-width: 200px"></Column>
            <Column field="noorderalat" header="No Order" :sortable="true" style="min-width: 100px"></Column>
            <Column field="namamerk" header="Merk" :sortable="true" style="min-width: 100px"></Column>
            <Column field="namatipe" header="Tipe" :sortable="true" style="min-width: 100px"></Column>
            <Column field="namaserialnumber" header="S/N" :sortable="true" style="min-width: 200px"></Column>
            <Column field="tglregistrasi" header="Tanggal Registrasi" :sortable="true" style="min-width: 200px">
              <template #body="slotProps">
                <span>{{ H.formatDateToLocalString(slotProps.data.tglregistrasi) }}</span>
              </template>
            </Column>
            <Column field="lingkupkalibrasi" header="Lingkup Kalibrasi" :sortable="true" style="min-width: 200px"></Column>
            <Column field="lokasiAlat" header="Lokasi" :sortable="true" style="min-width: 200px"></Column>
            <Column field="statusAlat" header="Status Alat" :sortable="true" style="min-width: 100px">
              <template #body="slotProps">
                <VTag class="ml-4" :color="slotProps.data.color" rounded>{{ slotProps.data.statusAlat }}</VTag>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </VCard>
  </div>

</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { ref, computed, watch, reactive } from 'vue'
import DataTable from 'primevue/datatable';
import Column from 'primevue/column'
import AutoComplete from 'primevue/autocomplete';
import { useThemeColors } from '/@src/composable/useThemeColors'
import * as H from '/@src/utils/appHelper'
import { formatRp } from '/@src/utils/appHelper'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import moment from 'moment'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import Calendar from 'primevue/calendar';
import * as XLSX from "xlsx";
useHead({
  title: 'Laporan Registrasi Alat - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const modalFilter: any = ref(false)
const themeColors = useThemeColors()
const userLogin = useUserSession().getUser()
const total = ref(0)
const router = useRouter()
const modalInput = ref(false)
const item: any = ref({
  qFilterTgl: {
    start: new Date(),
    end: new Date()
  },
})


const currentPage: any = ref({
  limit: 5,
  rows: 50,
})
const remakeData: any = ref([])
let dataSource: any = ref([])
let dataHutang: any = ref([])
let d_departemen: any = ref([])
let isLoading: any = ref(false)
let isPlaceLoad: any = ref(false)
const filters = ref('')


const fetchData = async () => {
  isPlaceLoad.value = true
  let tglAwal = 'tglAwal=' + moment(item.value.qFilterTgl.start).format('YYYY-MM-DD')
  let tglAkhir = '&tglAkhir=' + moment(item.value.qFilterTgl.end).format('YYYY-MM-DD')
  let search = item.value.search ? `&search=${item.value.search}` : ''
  let id = `&id=${userLogin.pegawai.id}`

  await useApi().get(`laporan/get-laporan-alat-pelaksana?${tglAwal}${tglAkhir}${id}${search}`).then((response: any) => {
    response.data.forEach((element: any, i: any) => {
      element.no = i + 1
    });
    dataSource.value = response.data
  })
  isPlaceLoad.value = false

}


const exportExcel = () => {
  console.log(dataSource.value)
  remakeData.value = dataSource.value.map((e: any) => {
    return {
      search: e.search, NoRM: e.nocm, NoRegistrasi: e.noregistrasi, JenisKelamin: e.jeniskelamin,
      AlamatLengkap: e.alamatlengkap, Desa: e.namadesakelurahan, Kecamatan: e.kecamatan, Kota: e.namakotakabupaten,
      Antrian: e.noantrian, Tanggal: e.tglmasuk, Dokter: e.namalengkap, Ruangan: e.namaruangan, NoHP: e.nohp,
      KelompokPasien: e.kelompokpasien, Status: e.statuspasien,
    }
  })
  const worksheet = XLSX.utils.json_to_sheet(remakeData.value)
  const workbook = { Sheets: { data: worksheet }, SheetNames: ['data'] };
  const excelBuffer: any = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
  saveAsExcelFile(excelBuffer, 'products');
}

const saveAsExcelFile = (buffer: any, fileName: string) => {
  let EXCEL_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8';
  let EXCEL_EXTENSION = '.xlsx';
  const data: Blob = new Blob([buffer], {
    type: EXCEL_TYPE
  });
  const _url = window.URL.createObjectURL(data)
  window.open(_url, EXCEL_EXTENSION).focus();
  // window.open(_url,EXCEL_EXTENSION).focus()
  // exportFilename.saveAs(data, fileName + '_export_' + new Date().getTime() + EXCEL_EXTENSION);
}

fetchData()
</script>
