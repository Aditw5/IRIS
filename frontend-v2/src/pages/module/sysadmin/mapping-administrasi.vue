<template>
  <ConfirmDialog />
  <div class="column is-12">
    <VCard style="padding-bottom: 0px">
      <div class="column c-title pt-2 mb-3">
        <label class="title-page">Tambah Data</label>
      </div>
      <div class="column is-12">
        <div class="columns is-multiline">
          <div class="column is-4">
            <VField label="Ruangan" class="is-rounded-select  is-autocomplete-select" v-slot="{ id }">
              <VControl icon="feather:search">
                <Multiselect mode="single" v-model="item.ruangan" :options="d_ruangan" placeholder="Pilih Ruangan"
                  :searchable="true" :attrs="{ id }" @select="changeProduk(item.ruangan)" />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField label="Jenis Layanan" class="is-rounded-select  is-autocomplete-select" v-slot="{ id }">
              <VControl icon="feather:search">
                <Multiselect mode="single" v-model="item.jenispelayanan" :options="d_JenisPelayanan" placeholder="Pilih "
                  :searchable="true" :attrs="{ id }" />
              </VControl>
            </VField>

          </div>
          <div class="column is-4">
            <VField label="Penjamin" class="is-rounded-select  is-autocomplete-select" v-slot="{ id }">
              <VControl icon="feather:search">
                <Multiselect mode="single" v-model="item.rekanan" :options="d_Rekanan" placeholder="Pilih "
                  :searchable="true" :attrs="{ id }" />
              </VControl>
            </VField>
          </div>

          <div class="column is-4">
            <VField label="Pelayanan" class="is-rounded-select  is-autocomplete-select" v-slot="{ id }">
              <VControl icon="feather:list" fullwidth>
                <Multiselect mode="single" v-model="item.produk" :options="d_Produk" placeholder="Pilih data"
                  :searchable="true" :attrs="{ id }" autocomplete="off" @select="changeHarga(item.produk)" />
              </VControl>
            </VField>
          </div>
          <div class="column is-2">
            <VField label="Harga">
              <VLabel class="mt-4">{{
                H.formatRp(item.hargasatuan,
                  'Rp.')
              }}</VLabel>
            </VField>
          </div>
          <VButton :color="item.id == undefined ? 'success' : 'warning'" icon="feather:save" @click="save()"
            style="margin-top: 1.5rem;" :loading="isLoading">
            {{ item.id == undefined ? 'Simpan' : 'Ubah' }}
          </VButton>
          <VButton color="danger" icon="feather:x" @click="batal()" style="margin-left: 1rem; margin-top: 1.5rem;">
            Batal
          </VButton>
        </div>
      </div>
    </VCard>
  </div>

  <div class="column is-12">
    <VCard>
      <div class="column c-title pt-2 mb-0">
        <div class="columns p-2">
          <div class="column is-10">
            <label class="title-page">List Mapping Administrasi</label>
          </div>
        </div>
      </div>
      <div class="columns">
        <div class="column is-9">
          <DataTable :value="dataSource" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
            <Column field="no" header="No" style="text-align: center;" />
            <Column field="namaruangan" header="Ruangan" />
            <Column field="namaproduk" header="Pelayanan" />
            <Column field="jenispelayanan" header="Jenis Pelayanan" />
            <Column field="namarekanan" header="Penjamin" />
            <Column :exportable="false" header="Action" style="text-align: center;width:100px">
              <template #body="slotProps">
                <VIconButton type="button" icon="pi pi-pencil" class="mr-2" color="info" circle outlined raised
                  v-tooltip.top="'Edit '" @click="editData(slotProps.data)">
                </VIconButton>
                <VIconButton type="button" icon="pi pi-trash" class="mr-2" color="danger" circle outlined raised
                  v-tooltip.top="'Hapus '" @click="deleterow(slotProps.data)">
                </VIconButton>

                <!-- <VDropdown icon="feather:more-vertical" spaced right v-tooltip.bubble="'AKSI'">
                                    <template #content>
                                      <a role="menuitem" class="dropdown-item is-media" @click="editData(slotProps.data)">
                                        <div class="icon">
                                          <i class="iconify" data-icon="feather:log-in" aria-hidden="true"></i>
                                        </div>
                                        <div class="meta">
                                          <span>Edit Data</span>
                                        </div>
                                      </a>
                                      <a role="menuitem" class="dropdown-item is-media" @click="DialogConfirm(slotProps.data)">
                                        <div class="icon">
                                          <i class="fas fa-trash" aria-hidden="true"></i>
                                        </div>
                                        <div class="meta">
                                          <span>Hapus Data</span>
                                        </div>
                                      </a>
                                      </template>
                                      </VDropdown> -->
              </template>
            </Column>
          </DataTable>
        </div>
        <div class="column is-3">
          <div class="columns is-multiline">
            <div class="column is-6">
              <h3 class="title is-5 mb-2 mr-1">Filters</h3>
            </div>
            <div class="column is-6">
              <a @click="clearFilter()" type="button" class="is-pulled-right mr-3" color="info" outlined raised>
                Clear All
              </a>
            </div>

            <div class="column is-12">
              <VField class="is-autocomplete-select">
                <VLabel>Ruangan</VLabel>
                <VControl icon="feather:search">
                  <Multiselect mode="single" v-model="item.idruangan" :options="d_ruangan" placeholder="Pilih Ruangan"
                    :searchable="true" />
                </VControl>
              </VField>
            </div>
            <!-- <div class="column is-12">
                            <VField class="is-autocomplete-select">
                                <VLabel>Produk</VLabel>
                                <VControl icon="feather:search">
                                    <Multiselect mode="single" v-model="item.idproduk" :options="d_layanan"
                                        placeholder="Pilih Produk" :searchable="true" />
                                </VControl>
                            </VField>
                        </div> -->
            <div class="column is-12">
              <VField class="is-autocomplete-select">
                <VLabel>Penjamin</VLabel>
                <VControl icon="feather:search">
                  <Multiselect mode="single" v-model="item.idrekanan" :options="d_Rekanan" placeholder="Pilih penjamin"
                    :searchable="true" />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VButton @click="filter()" :loading="isLoading" type="button" icon="feather:search"
                class="is-fullwidth mr-3" color="info" raised>
                Apply Filters
              </VButton>
            </div>
          </div>
        </div>
      </div>
    </VCard>
  </div>
</template>

<script  setup lang="ts">
import { useApi } from '/@src/composable/useApi'
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useConfirm } from 'primevue/useconfirm'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import Fieldset from 'primevue/fieldset';
import moment from 'moment'
import * as H from '/@src/utils/appHelper'
import ConfirmDialog from 'primevue/confirmdialog'
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable'

import Dropdown from 'primevue/dropdown'
import Column from 'primevue/column'
import OverlayPanel from 'primevue/overlaypanel';


import { useViewWrapper } from '/@src/stores/viewWrapper'
import { watch } from 'vue';
import { booleanTypeAnnotation } from '@babel/types';

useHead({
  title: 'Map Administrasi- ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const item: any = ref({
  aktif: true,
  isKK: false,
  filterTgl: reactive({
    start: new Date(),
    end: new Date(),
  }),
})
const currentPage: any = ref({
  limit: 10,
  rows: 50
})
const confirm = useConfirm()
const d_ruangan = ref([])
const d_layanan: any = ref([])
const dataSource: any = ref([])
const op = ref();
const selected: any = ref({})
const filters = ref('')
const route = useRoute()
let d_Produk: any = ref([])
let d_JenisPelayanan: any = ref([])
let d_Rekanan: any = ref([])
let isLoading: any = ref(false)

const dataSourcefiltered = computed(() => {
  if (!filters.value) {
    return dataSource.value
  }
  return dataSource.value.filter((items: any) => {
    return (
      items.namaproduk.match(new RegExp(filters.value, 'i'))
    )
  })
})

const listDataCombo = async () => {
  await useApi().get('/sysadmin/get-ruang').then((response) => {
    d_ruangan.value = response.ruangan.map((e: any) => { return { label: e.namaruangan, value: e.id, default: e.id } })
    d_JenisPelayanan.value = response.jenispelayanan.map((e: any) => { return { label: e.jenispelayanan, value: e.id, default: e.id } })
    d_Rekanan.value = response.kelompokpasien.map((e: any) => { return { label: e.kelompokpasien, value: e.id, default: e.id } })
    // d_layanan.value = response.namaproduk.map((e: any) => { return { label: e.namaproduk, value: e.id, default: e.id } })
  })
}
async function changeProduk(event: any) {
  d_Produk.value = []
  let query = event == '' ? '' : event;
  isLoading.value = true
  const response = await useApi().get(
    `/sysadmin/produk-akomodasi?idRuangan=${query}`)
  isLoading.value = false
  d_Produk.value = response.data.map((e: any) => { return { label: e.namaproduk, value: e } })
}

function changeHarga(e: any) {
  isLoading.value = true
  item.value.hargasatuan = 0
  useApi().get(
    '/sysadmin/get-produk-harga?idProduk=' + e.id
  ).then((response: any) => {
    isLoading.value = false
    item.value.hargasatuan = response.harga.hargasatuan
    item.value.hargasatuanDef = response.harga.hargasatuan
    item.jumlah = 1
    // d_Komponen.value = response.komponen
  })
}

const batal = () => {
  item.value = {}

}
const fetchData = async () => {
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = (offset * limit) - limit
  let idproduk = item.value.idproduk ? `&idproduk=${item.value.idproduk}` : ''
  let idruangan = item.value.idruangan ? `&idruangan=${item.value.idruangan}` : ''
  let idrekanan = item.value.idrekanan ? `&idrekanan=${item.value.idrekanan}` : ''

  isLoading.value = true
  await useApi().get('/sysadmin/mapping-admin?' + '&offset=' + offset
    + '&limit=' + limit
    + '&idproduk=' + idproduk
    + '&idruangan=' + idruangan
    + '&idrekanan=' + idrekanan
    + '&rows=' + currentPage.value.rows).then((response) => {
      response.data.forEach((element: any, i: any) => {
        element.no = i + 1
      });
      isLoading.value = false
      dataSource.value = response.data
    })
}

const save = async () => {
  var objSave = {
    'mapping':
    {
      'id': item.value.id ? item.value.id : '',
      'objectprodukfk': item.value.produk.id,
      'objectruanganfk': item.value.ruangan,
      'jenispelayananfk': item.value.jenispelayanan,
      'rekananfk': item.value.rekanan,
    },
  }
  isLoading.value = true
  await useApi().post(
    `sysadmin/save-map-administrasi`, objSave).then((response: any) => {
      isLoading.value = false
      // clear()
      fetchData()
    }, (error) => {
      isLoading.value = false

      // console.log(error)
    })
}

const editData = (e: any) => {

  item.value.id = e.id
  item.value.ruangan = e.objectruanganfk
  item.value.produk = e.namaproduk
  item.value.rekanan = e.rekananfk
  item.value.jenispelayanan = e.jenispelayananfk

  console.log(item.value.produk)
}

const deleterow = async (e: any) => {
  await useApi().post(
    `/sysadmin/delete-administrasi`, { 'id': e.id }).then((response: any) => {
      fetchData()
    }, (error) => {
    })
}
const DialogConfirm = (e: any) => {
  debugger
  confirm.require({
    message: 'Apakah anda serius menghapus data ini ?',
    header: 'Konfirmasi Hapus Data',
    icon: 'pi pi-info-circle',
    acceptClass: 'p-button-danger',
    accept: () => {
      deleterow(e)

    },
    reject: () => { },
  })
}

function filter() {
  fetchData()
}
function clearFilter() {
  fetchData()
}

listDataCombo()
fetchData()

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';

.title-page {
  position: relative;
  font-size: 17px;
  display: block;
  margin-bottom: 3px;
  margin-top: 8px;
  font-weight: 600;
}

.btn-search {
  display: flex;
  align-items: center;
  margin-top: 14px;
}
</style>
