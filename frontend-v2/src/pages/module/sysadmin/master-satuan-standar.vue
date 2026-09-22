<template>
    <VCard radius="small">
      <div class="columns column">
        <h3 class="title is-5 mb-2 mr-1">Satuan Standar</h3>
      </div>
      <div class="columns is-multiline">
        <div class="column is-9">
          <div class="user-grid-toolbar">
            <VField>
              <VControl>
                <VSwitchBlock v-model="item.aktif" label="Aktif" color="danger" @change="changeSwitch(item.aktif)" />
              </VControl>
            </VField>
  
            <div class="buttons">
              <VField v-slot="{ id }" class="is-icon-select">
                <VControl>
                  <Multiselect v-model="selectView" :attrs="{ id }" placeholder="Select View" label="name"
                    :options="d_View" :searchable="true" track-by="name" mode="single" @select="changeView(selectView)"
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
              <VButton color="primary" raised @click="add()">
                <span class="icon">
                  <i aria-hidden="true" class="fas fa-plus"></i>
                </span>
                <span>Tambah Data</span>
              </VButton>
            </div>
          </div>
  
          <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
            <div card>
            <DataTable :value="dataSourcefiltered" 
            :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
              showGridlines>
  
              <Column field="no" header="No" style="width:5%"></Column>
              <Column field="satuanstandar" header="Satuan Standar" :sortable="true" style="width:25%"></Column>
              <Column field="kelompokproduk" header="Kelompok Produk" style="width:30%"></Column>
              <Column field="status" header="Status" style="width:15%"></Column>
              <Column :exportable="false" header="Action"  style="width:25%">
                <template #body="slotProps">
                  <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined raised
                    v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                  </VIconButton>
                  <VIconButton type="button" icon="fas fa-trash" class="mr-3" color="danger" circle outlined raised
                    v-tooltip.top="'Hapus'" @click="deleterow(slotProps.data)">
                  </VIconButton>
                </template>
              </Column>
            </DataTable>
          </div>
          </div>
          <div class="tile-grid tile-grid-v1" v-else-if="selectView == 'grid'">
            <TransitionGroup name="list" tag="div" class="columns is-multiline">
              <!--Grid item-->
              <div v-for="(item, key) in dataSourcefiltered" :key="key" class="column is-6">
                <div class="tile-grid-item">
                  <div class="tile-grid-item-inner">
                    <VAvatar size="medium" picture="/images/avatars/svg/paket.svg" color="primary" squared bordered />
                    <div class="meta">
                      <span class="dark-inverted"> {{ item.satuanstandar }}</span>
                      <span style="margin-top: 0.3rem; margin-bottom: 0.3rem"> {{ item.kelompokproduk }} </span>
                      <VTag :color="item.status_c" :label="item.status"></VTag>
                    </div>
                  
                    <VDropdown icon="feather:more-vertical" spaced right>
                      <template #content>
                        <a role="menuitem" class="dropdown-item is-media" @click="detail(item)">
                          <div class="icon">
                            <i class="iconify" data-icon="feather:bookmark" aria-hidden="true"></i>
                          </div>
                          <div class="meta">
                            <span>Detail</span>
                            <span>Untuk melihat data </span>
                          </div>
                        </a>
                        <a role="menuitem" class="dropdown-item is-media" @click="edit(item)">
                          <div class="icon">
                            <i class="iconify" data-icon="feather:edit" aria-hidden="true"></i>
                          </div>
                          <div class="meta">
                            <span>Edit</span>
                            <span>Untuk merubah data </span>
                          </div>
                        </a>
                        <a role="menuitem" class="dropdown-item is-media" @click="deleterow(item)">
                          <div class="icon">
                            <i aria-hidden="true" class="lnil lnil-trash-can-alt"></i>
                          </div>
                          <div class="meta">
                            <span>Remove</span>
                            <span>Hapus Data</span>
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
        <div class="column is-3">
          <div class="columns is-multiline">
  
            
            <div class="column is-12">
              <img src="/images/avatars/label/standar.png" alt="" srcset=""
            style="max-width:70%; margin-top: -5rem; margin-left: 7rem;">
            </div>
            <div class="column is-6" style="margin-top: -4.5rem;">
              <h3 class="title is-5 mb-2 mr-1">Filter</h3>
            </div>
            <div class="column is-12" style="margin-top: -2rem;">
              <VField>
                <VControl icon="feather:search">
                  <input v-model="filters" class="input custom-text-filter" placeholder="Filter Nama Paket..." />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField class="is-autocomplete-select">
                <VLabel>Kelompok Produk</VLabel>
                <VControl icon="feather:search">
                  <Multiselect mode="single" v-model="item.jenistransaksi" :options="d_kelompokproduk"
                    placeholder="Pilih Transaksi" :searchable="true" />
                </VControl>
              </VField>
            </div>
           
            <div class="column is-12">
              <VButton @click="filter()" :loading="isLoadingTT" type="button" icon="feather:search"
                class="is-fullwidth mr-3" color="info" raised>
                Cari Data
              </VButton>
            </div>
            
          </div>
        </div>
      </div>
      <template>
      </template>
      <VModal :open="modalInput" title="Tambah Satuan Standar" actions="right" @close="modalInput = false">
        <template #content>
          <form class="modal-form">
            <div class="columns is-multiline">
                <div class="column is-12">
                     <VField label="Nama Satuan Standar">
                         <VControl icon="feather:bookmark">
                             <VInput type="text" v-model="item.satuanstandar" placeholder="Nama Satuan Standar"
                                 class="is-rounded" />
                         </VControl>
                     </VField>
                 </div>
                 <div class="column is-6">
                     <VField label="Jumlah Kemasan">
                         <VControl icon="feather:bookmark">
                             <VInput type="text" v-model="item.qtykemasan" placeholder="Jumlah Kemasan"
                                 class="is-rounded" />
                         </VControl>
                     </VField>
                 </div>
                 <div class="column is-6">
                     <VField class="is-rounded-select is-autocomplete-select">
                         <VLabel>Kelompok Produk</VLabel>
                         <VControl icon="feather:search">
                             <Multiselect mode="single" v-model="item.objectkelompokprodukfk" :options="d_kelompokproduk"
                                 placeholder="Pilih data" :searchable="true" />
                         </VControl>
                     </VField>
                 </div>
                 <div class="column is-8">
                     <VField class="is-rounded-select is-autocomplete-select">
                         <VLabel>Departemen</VLabel>
                         <VControl icon="feather:search">
                             <Multiselect mode="single" v-model="item.objectdepartemenfk" :options="d_departemen"
                                 placeholder="Pilih data" :searchable="true" />
                         </VControl>
                     </VField>
                 </div>
                 <div class="column is-4">
                <VField class="is-rounded-select is-autocomplete-select">
                  <VLabel>Status</VLabel>
                  <VControl>
                    <VSwitchBlock v-model="item.statusenabled" :options="d_status" label="Aktif" color="danger" />
                  </VControl>
                </VField>
              </div>
            </div>
          </form>
        </template>
        <template #action>
          <VButton icon="feather:plus" @click="save()" :loading="isLoadingTT" color="primary" raised>Simpan</VButton>
        </template>
      </VModal>
      <VModal :open="modalDetail" title="Detail Paket" actions="right" @close="modalDetail = false">
        <template #content>
            <form class="modal-form">
            <div class="columns is-multiline">
                <div class="column is-12">
                     <VField label="Nama Satuan Standar">
                         <VControl icon="feather:bookmark">
                             <VInput type="text" v-model="item.satuanstandar" placeholder="Nama Satuan Standar"
                                 class="is-rounded" />
                         </VControl>
                     </VField>
                 </div>
                 <div class="column is-6">
                     <VField label="Jumlah Kemasan">
                         <VControl icon="feather:bookmark">
                             <VInput type="text" v-model="item.qtykemasan" placeholder="Jumlah Kemasan"
                                 class="is-rounded" />
                         </VControl>
                     </VField>
                 </div>
                 <div class="column is-6">
                     <VField class="is-rounded-select is-autocomplete-select">
                         <VLabel>Kelompok Produk</VLabel>
                         <VControl icon="feather:search">
                             <Multiselect mode="single" v-model="item.objectkelompokprodukfk" :options="d_kelompokproduk"
                                 placeholder="Pilih data" :searchable="true" />
                         </VControl>
                     </VField>
                 </div>
                 <div class="column is-8">
                     <VField class="is-rounded-select is-autocomplete-select">
                         <VLabel>Departemen</VLabel>
                         <VControl icon="feather:search">
                             <Multiselect mode="single" v-model="item.objectdepartemenfk" :options="d_departemen"
                                 placeholder="Pilih data" :searchable="true" />
                         </VControl>
                     </VField>
                 </div>
              <div class="column is-4" v-if="item.id">
                <VField class="is-rounded-select is-autocomplete-select">
                  <VLabel>Status</VLabel>
                  <VControl>
                    <VSwitchBlock v-model="item.statusenabled" :options="d_status" label="Aktif" color="danger" />
                  </VControl>
                </VField>
              </div>
            </div>
          </form>>
        </template>
      </VModal>
    </VCard>
  
  </template>
<script  setup lang="ts">                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import moment from 'moment'
import { useViewWrapper } from '/@src/stores/viewWrapper'
useHead({
  title: 'Satuan Standar - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
const item: any = ref({
  aktif: true
})
const modalInput = ref(false)
const modalDetail = ref(false)

let dataSource: any = ref([])
const d_View = [
  {
    name: 'Grid View',
    value: 'grid',
    icon: 'fas fa-id-card-alt',
  },
  {
    name: 'List View',
    value: 'list',
    icon: 'fas fa-list',
  },
]
const d_status = [
  { value: 't', label: 'True' },
  { value: 'f', label: 'False' },
]
const selectView: any = ref()
selectView.value = 'list'
const d_kelompokproduk = ref([])
const d_departemen = ref([])
let isLoading: any = ref(false)
let isLoadingTT: any = ref(false)

const currentPage: any = ref({
  limit: 5,
  rows: 50,
})
const filters = ref('')
const dataSourcefiltered = computed(() => {
  if (!filters.value) {
    return dataSource.value
  }

  return dataSource.value.filter((items: any) => {
    return (
      items.satuanstandar.match(new RegExp(filters.value, 'i'))
    )
  })
})

const route = useRoute()
isLoading.value = false

async function fetchData() {
  isLoading.value = true
  let rows: any = currentPage.value.rows
  let SatuanStandar = ''
  let KelompokProduk = ''
  let StatusEnabled = ''

  if (item.value.qnama) SatuanStandar = '&satuanstandar=' + item.value.qnama
  if (item.value.kelompokproduk) KelompokProduk = '&objectkelompokprodukfk=' + item.value.kelompokproduk
  item.value.aktif ? StatusEnabled = '&statusenabled=' + item.value.aktif : StatusEnabled = '&statusenabled=false'

  dataSource.value=[]
  const response = await useApi().get(
    '/sysadmin/master-satuan-standar?offset'+
    SatuanStandar + KelompokProduk + StatusEnabled
  )
  isLoading.value = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x];
    element.no = x + 1
  }

  dataSource.value = response.data
  //   dataSource.value.total = response.length
}

function loadData() {
  fetchData ()
}
function loadDropdown() {
  d_kelompokproduk.value = []
  useApi().get(
    `/sysadmin/master-satuan-standar-dropdown`).then((response: any) => {
      d_kelompokproduk.value = response.kelompokproduk.map((e: any) => { return { label: e.kelompokproduk, value: e.id } })
      d_departemen.value = response.namadepartemen.map((e: any) => { return { label: e.namadepartemen, value: e.id } })
    })
}

function add() {
  clear()
  modalInput.value = true
}

function edit(e: any) {
  item.value.id = e.id
  item.value.satuanstandar = e.satuanstandar
  item.value.objectkelompokprodukfk = e.objectkelompokprodukfk
  item.value.objectdepartemenfk = e.objectdepartemenfk
  item.value.qtykemasan = e.qtykemasan
  item.value.statusenabled = e.statusenabled
  modalInput.value = true
}
function detail(e: any) {
  item.value.id = e.id
  item.value.satuanstandar = e.satuanstandar
  item.value.objectkelompokprodukfk = e.objectkelompokprodukfk
  item.value.objectdepartemenfk = e.objectdepartemenfk
  item.value.qtykemasan = e.qtykemasan
  item.value.statusenabled = e.statusenabled
  modalDetail.value = true
}

async function save() {
  if (!item.value.satuanstandar) {
    useToaster().error('Nama Satuan Standar harus di isi')
    return
  }

  var objSave =
  {
    'satuanstandar': {
      'id': item.value.id ? item.value.id : '',
      'satuanstandar': item.value.satuanstandar,
      'objectkelompokprodukfk': item.value.objectkelompokprodukfk ? item.value.objectkelompokprodukfk : null,
      'objectdepartemenfk': item.value.objectdepartemenfk ? item.value.objectdepartemenfk : null,
      'qtykemasan': item.value.qtykemasan ? item.value.qtykemasan : null,
      'statusenabled': item.value.statusenabled ? item.value.statusenabled : null,
    }
  }
  isLoadingTT.value = true
  await useApi().post(
    `/sysadmin/save-satuan-standar`, objSave).then((response: any) => {
      isLoadingTT.value = false
      clear()
     fetchData ()
    }, (error) => {
      isLoadingTT.value = false
      // console.log(error)
    })
}

async function deleterow(e: any) {
  await useApi().post(
    `/sysadmin/delete-satuan-standar`, { 'id': e.id }).then((response: any) => {
      fetchData ()
    }, (error) => {
    })
}
function changeSwitch(e: any) {
  fetchData()
}

function clear() {

item.value.id = ''
item.value.satuanstandar = ''
item.value.objectdepartemenfk = ''
item.value.objectkelompokprodukfk = ''
item.value.qtykemasan = ''
modalInput.value = false
}

function changeView(e: any) {
  selectView.value = e
}
function filter() {
  fetchData()
}
function clearFilter() {
  delete item.qnama
  delete item.kelompokproduk
  fetchData()
}
filter()
// loadData()
loadDropdown()
fetchData()
</script>
 <style lang="scss">
 @import '/@src/scss/abstracts/all';
 
 @import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';

 </style>
 