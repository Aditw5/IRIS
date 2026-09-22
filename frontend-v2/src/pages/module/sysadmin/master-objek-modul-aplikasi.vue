<template>

  <div class="columns column">
    <h3 class="title is-5 mb-2 mr-1">Objek Modul Aplikasi</h3>
  </div>
  <div class="columns is-multiline">
    <div class="column is-12">
      <div class="user-grid-toolbar">
        <VControl icon="feather:search">
          <input v-model="filters" class="input custom-text-filter" placeholder="Search..." />
        </VControl>
        <VControl class="is-pulled-right">
          <VSwitchBlock v-model="item.aktif" label="Aktif" color="danger" @change="changeSwitch(item.aktif)" />
        </VControl>
        <div class="buttons">
          <VField v-slot="{ id }" class="is-icon-select">
            <VControl>
              <Multiselect v-model="selectView" :attrs="{ id }" placeholder="Select View" label="name" :options="d_View"
                :searchable="true" track-by="name" mode="single" @select="changeView(selectView)" autocomplete="off">
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
        <DataTable :value="dataSourcefiltered" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
          paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
          responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">

          <Column field="kdobjekmodulaplikasi" header="Kode"></Column>
          <Column field="objekmodulaplikasi" header="Objek Modul Aplikasi" :sortable="true"></Column>
          <Column field="alamaturlform" header="Alamat"></Column>
          <Column field="keterangan" header="Keterangan"></Column>
          <Column field="fungsi" header="Fungsi"></Column>
          <Column field="nourut" header="Nomor Urut"></Column>
          <Column field="kdobjekmodulaplikasihead" header="Kode Head"></Column>
          <Column :exportable="false" header="Action">
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

      <div class="user-grid user-grid-v2" v-else-if="selectView == 'grid'">
        <TransitionGroup name="list" tag="div" class="columns is-multiline">
          <!--Grid item-->
          <div v-for="item in dataSourcefiltered" :key="item.id" class="column is-3">
            <div class="grid-item-wrap">
              <div class="grid-item-head">
                <div class="flex-head">
                  <div class="meta">
                    <span>{{ item.alamaturlform }} </span>
                  </div>
                </div>
              </div>
              <div class="grid-item">
                <VAvatar size="big" picture="/images/avatars/svg/objekmodul.svg" color="primary" squared />
                <div class="people">
                  <VSnack :title="(item.objekmodulaplikasi)" color="info"
                    :icon="(item.norec_pd != null ? 'fa:user-md' : 'feather:user')" @click="detail(item)">
                  </VSnack>
                </div>
                <div class="buttons">
                  <button class="button v-button is-dark-outlined" @click="edit(item)">
                    <span class="icon">
                      <i aria-hidden="true" class="iconify" data-icon="feather:edit"></i>
                    </span>
                    <span>Edit</span>
                  </button>
                  <button class="button v-button is-dark-outlined" @click="deleterow(item)">
                    <span class="icon">
                      <i aria-hidden="true" class="iconify" data-icon="feather:trash"></i>
                    </span>
                    <span>Hapus</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </TransitionGroup>
      </div>
    </div>

  </div>
  <VModal :open="modalInput" title="Tambah Objek Modul Aplikasi" actions="right" @close="modalInput = false">
    <template #content>
      <form class="modal-form">
        <div class="columns is-multiline">
          <div class="column is-12">
            <VField label="Objek Modul Aplikasi">
              <VControl icon="fas fa-user">
                <VInput type="text" v-model="item.objekmodulaplikasi" placeholder="Objek Modul Aplikasi"
                  class="is-rounded" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Alamat Url Form">
              <VControl icon="fas fa-user">
                <VInput type="text" v-model="item.alamaturlform" placeholder="Alamat Url Form" class="is-rounded" />
              </VControl>
            </VField>
          </div>
          <div class="column is-6">
            <VField label="Nomor Urut">
              <VControl icon="fas fa-address-card">
                <VInput type="text" v-model="item.nourut" placeholder="Nomor Urut" class="is-rounded" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Fungsi">
              <VControl icon="fas fa-user">
                <VInput type="text" v-model="item.fungsi" placeholder="Fungsi" class="is-rounded" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Keterangan">
              <VControl icon="fas fa-user">
                <VInput type="text" v-model="item.keterangan" placeholder="Keterangan" class="is-rounded" />
              </VControl>
            </VField>
          </div>
          <div class="column is-6">
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
  <VModal :open="modalDetail" title="Detail Objek Modul Aplikasi" actions="right" @close="modalDetail = false">
    <template #content>
      <form class="modal-form">
        <div class="columns is-multiline">
          <div class="column is-12">
            <VField label="Objek Modul Aplikasi">
              <VControl icon="fas fa-user">
                <VInput type="text" v-model="item.objekmodulaplikasi" placeholder="Objek Modul Aplikasi"
                  class="is-rounded" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Alamat Url Form">
              <VControl icon="fas fa-user">
                <VInput type="text" v-model="item.alamaturlform" placeholder="Alamat Url Form" class="is-rounded" />
              </VControl>
            </VField>
          </div>
          <div class="column is-6">
            <VField label="Nomor Urut">
              <VControl icon="fas fa-address-card">
                <VInput type="text" v-model="item.nourut" placeholder="Nomor Urut" class="is-rounded" />
              </VControl>
            </VField>
          </div>
          <div class="column is-6">
            <VField label="Kode Objek Modul Head">
              <VControl icon="fas fa-address-card">
                <VInput type="text" v-model="item.kdobjekmodulaplikasihead" placeholder="Kode Objek Modul Head"
                  class="is-rounded" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Fungsi">
              <VControl icon="fas fa-user">
                <VInput type="text" v-model="item.fungsi" placeholder="Fungsi" class="is-rounded" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Keterangan">
              <VControl icon="fas fa-user">
                <VInput type="text" v-model="item.keterangan" placeholder="Keterangan" class="is-rounded" />
              </VControl>
            </VField>
          </div>
        </div>
      </form>
    </template>
  </VModal>


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
  title: 'Objek Modul Aplikasi - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const item: any = ref({
  aktif: true
})
const modalInput = ref(false)
const modalDetail = ref(false)

let dataSource: any = ref([])
let isRegistrasi = ref(false)
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
const d_pegawai = ref([])
const d_kelompok = ref([])
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
      items.objekmodulaplikasi.match(new RegExp(filters.value, 'i'))
    )
  })
})

const route = useRoute()
isLoading.value = false

async function fetchData() {
  isLoading = true
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = offset * limit - limit
  let rows: any = currentPage.value.rows
  let ObjekModulAplikasi = ''
  let Alamat = ''
  let Keterangan = ''
  let Fungsi = ''
  let Statusenabled = ''
  let Kdobjekmodulaplikasi = ''

  if (item.objekmodulaplikasi) ObjekModulAplikasi = '&objekmodulaplikasi' + item.objekmodulaplikasi
  if (item.alamaturlform) Alamat = '&alamaturlform=' + item.alamaturlform
  if (item.keterangan) Keterangan = '&keterangan' + item.keterangan
  if (item.fungsi) Fungsi = '&fungsi' + item.fungsi
  if (item.statusenabled) Statusenabled = '&statusenabled' + item.statusenabled
  if (item.kdobjekmodulaplikasi) Kdobjekmodulaplikasi = '&kdobjekmodulaplikasi=' + item.kdobjekmodulaplikasi

  const response = await useApi().get(
    '/sysadmin/master-objek-modul-aplikasi?offset=' + offset +
    '&limit=' + limit +
    '&rows=' + rows +
    Kdobjekmodulaplikasi + ObjekModulAplikasi + Alamat + Keterangan + Fungsi + Statusenabled
  )
  isLoading = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x];
    element.no = x + 1
  }

  dataSource.value = response.data
  //   dataSource.value.total = response.length
}

function loadData() {
  dataSource.value = []

  useApi().get(
    `/sysadmin/master-objek-modul-aplikasi?statusenabled=${item.value.aktif}`).then((response: any) => {
      dataSource.value = response.data
    })
}

function add() {
  clear()
  modalInput.value = true
}

function edit(e: any) {
  item.value.id = e.id
  item.value.objekmodulaplikasi = e.objekmodulaplikasi
  item.value.nourut = e.nourut
  item.value.kdobjekmodulaplikasihead = e.kdobjekmodulaplikasihead
  item.value.fungsi = e.fungsi
  item.value.keterangan = e.keterangan
  item.value.alamaturlform = e.alamaturlform
  item.value.statusenabled = e.statusenabled
  modalInput.value = true
}
function detail(e: any) {
  item.value.id = e.id
  item.value.objekmodulaplikasi = e.objekmodulaplikasi
  item.value.nourut = e.nourut
  item.value.kdobjekmodulaplikasihead = e.kdobjekmodulaplikasihead
  item.value.fungsi = e.fungsi
  item.value.keterangan = e.keterangan
  item.value.alamaturlform = e.alamaturlform
  item.value.statusenabled = e.statusenabled
  modalDetail.value = true
}

async function save() {
  debugger
  if (!item.value.objekmodulaplikasi) {
    useToaster().error('Objek Modul Aplikasi harus di isi')
    return
  }
  if (!item.value.alamaturlform) {
    useToaster().error('Alamat URL Form harus di isi')
    return
  }
  var objSave =
  {
    'objekmodulaplikasi': {
      'id': item.value.id ? item.value.id : '',
      'objekmodulaplikasi': item.value.objekmodulaplikasi,
      'alamaturlform': item.value.alamaturlform,
      'nourut': item.value.nourut ? item.value.fungsi : null,
      'fungsi': item.value.fungsi ? item.value.fungsi : null,
      // 'kdobjekmodulaplikasihead': item.value.kdobjekmodulaplikasihead ? item.value.kdobjekmodulaplikasihead : null,
      'keterangan': item.value.keterangan ? item.value.keterangan : null,
      'statusenabled': item.value.statusenabled,
    }
  }
  isLoadingTT.value = true
  await useApi().post(
    `/sysadmin/save-objek-modul-aplikasi`, objSave).then((response: any) => {
      isLoadingTT.value = false
      clear()
      loadData()
    }, (error) => {
      isLoadingTT.value = false
      // console.log(error)
    })
}

async function deleterow(e: any) {
  await useApi().post(
    `/sysadmin/delete-objek-modul-aplikasi`, { 'id': e.id }).then((response: any) => {
      loadData()
    }, (error) => {
    })
}

function clear() {
  item.value = {}
  modalInput.value = false
}
function changeView(e: any) {
  selectView.value = e
}
function filter() {
  fetchData()
}
function changeSwitch(e: any) {
  loadData()
}
function clearFilter() {
  fetchData()
}
filter()
loadData()
//   loadDropdown ()
fetchData()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';

</style>