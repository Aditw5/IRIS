<template>
  <VCard>
  <div class="columns column">
    <h3 class="title is-5 mb-2 mr-1">Daftar Kelas</h3>
  </div>
  <div class="columns is-multiline">
    <div class="column is-8">
      <div class="user-grid-toolbar">
        <VControl icon="feather:search">
          <input v-model="filters" class="input custom-text-filter" placeholder="Cari Data..." />
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
          <VControl class="is-pulled-right">
            <VSwitchBlock v-model="item.aktif" label="Aktif" color="danger" @change="changeSwitch(item.aktif)" />
          </VControl>
        </div>
      </div>

      <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
        <DataTable :value="dataSourcefiltered" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
          paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
          responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">

          <Column field="no" header="No"></Column>
          <Column field="namakelas" header="Nama Kelas" :sortable="true" ></Column>
          <Column field="reportdisplay" header="Report Display"></Column>
          <Column field="kodebios" header="Kode Bios"></Column>
          <Column field="kodebpjsnaikkelas" header="Kode BPJS"></Column>
          <Column field="status" header="Status"></Column>
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
      <div class="list-view list-view-v3" v-else-if="selectView == 'grid'">
        <TransitionGroup name="list-complete" tag="div">
          <!--Item-->
          <div v-for="item in dataSourcefiltered" :key="item.id" class="list-view-item">
            <div class="list-view-item-inner">
              <VAvatar size="medium" picture="/images/avatars/svg/kelasss.svg" color="primary" squared bordered />
              <div class="meta-left">
                <h3> Kelas : {{ item.namakelas }} </h3> 
                <span>      </span>
              </div>
             
              <div class="meta-right">
                <div class="buttons">
                  <VTag :color="item.status_c" :label="item.status" style="margin-right : 1rem"/>
                  <VButton color="warning" outlined raised @click="edit(item)"> Edit </VButton>

                  <VIconButton icon="feather:trash" class="hint--bubble hint--danger hint--top" data-hint="Delete"
                    circle @click="deleterow(item)" />
                </div>
              </div>
            </div>
          </div>
        </TransitionGroup>
      </div>

    </div>
    <div class="column is-4">
        <img src="/images/avatars/label/pemberkasan.png" alt="" srcset=""
          style="max-width:65%;margin-left: 8rem; margin-top: -5rem; opacity: .8;">
        <VCard style="margin-top :-3rem">
          <div class="columns is-multiline">
            <div class="column is-6">
              <h3 class="title is-6 mb-2 mr-1" >
                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                Tambah Data
              </h3>
            </div>
            <div class="column is-12">
              <VField label="Nama Kelas">
                <VControl icon="feather:layers">
                  <VInput type="text" v-model="item.namakelas" placeholder="Nama Kelas" class="is-rounded" />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField label="Report Display">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.reportdisplay" placeholder="Report Display" class="is-rounded" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Kode BPJS Naik Kelas">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.kodebpjsnaikkelas" placeholder="Kode BPJS" class="is-rounded" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Kode Bios">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.kodebios" placeholder="Kode Bios" class="is-rounded" />
                </VControl>
              </VField>
            </div>

            <div class="column is-3" v-if="item.id">
              <VField label="Aktivasi" class="ml-4">
                <VControl class="is-pulled-right">
                  <VSwitchBlock v-model="item.statusenabled" color="danger" @change="changeSwitch(item.aktif)" />
                </VControl>
              </VField>
            </div>
            <div v-if="item.id" class="column is-9">
              <VButton @click="save()" :loading="isLoadingTT" type="button" icon="feather:edit"
                class="is-fullwidth mr-3" color="info" raised>
                Update Data
              </VButton>
              <VButton @click="clear()" type="button" icon="feather:x-circle"
                class="is-fullwidth is-outlined is-warning mt-3" raised>
                Batal Edit
              </VButton>
            </div>
            <div v-else class="column is-12">
              <VButton @click="save()" :loading="isLoadingTT" type="button" icon="feather:save"
                class="is-fullwidth mr-3" color="success" raised>
                Simpan Data
              </VButton>
            </div>
          </div>
        </VCard>
      </div>

  </div>
</VCard>
</template>
  
<script  setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { formatRp } from '/@src/utils/appHelper'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import moment from 'moment'
useHead({
  title: 'Kelas - ' + import.meta.env.VITE_PROJECT,
})
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
const selectView: any = ref()
selectView.value = 'list'
const d_JenisTransaksi = ref([])
const d_JenisKelas = ref([])
let isLoading: any = ref(false)
let isLoadingTT: any = ref(false)
let ID_Kelas = useRoute().query.id as string
let ID_Kelas_SET = ref()

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
      items.namakelas.match(new RegExp(filters.value, 'i'))
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
  let namaKelas = ''
  let kodeBios = ''
  let kodeBpjsNaikKelas = ''
  let StatusEnabled = ''

  if (item.namakelas) namaKelas = '&namakelas' + item.namakelas
  if (item.kodebios) kodeBios = '&kodebios=' + item.kodebios
  if (item.kodebpjsnaikkelas) kodeBpjsNaikKelas = '&kodebpjsnaikkelas=' + item.kodebpjsnaikkelas
  item.value.aktif ? StatusEnabled = '&statusenabled=' + item.value.aktif : StatusEnabled = '&statusenabled=false'

  const response = await useApi().get(
    '/sysadmin/master-kelas?offset=' + offset +
    '&limit=' + limit +
    '&rows=' + rows +
    namaKelas + kodeBios + kodeBpjsNaikKelas + StatusEnabled
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
  fetchData ()
}


function add() {
  clear()
  modalInput.value = true
}

function edit(e: any) {
  item.value.id = e.id
  item.value.namakelas = e.namakelas
  item.value.reportdisplay = e.reportdisplay
  item.value.kodebios = e.kodebios
  item.value.kodebpjsnaikkelas = e.kodebpjsnaikkelas
  item.value.statusenabled = e.statusenabled
}
function detail(e: any) {
  item.value.id = e.id
  item.value.namakelas = e.namakelas
  item.value.reportdisplay = e.reportdisplay
  item.value.kodebios = e.kodebios
  item.value.kodebpjsnaikkelas = e.kodebpjsnaikkelas
  item.value.statusenabled = e.statusenabled
}

async function save() {
  if (!item.value.namakelas) {
    useToaster().error('Nama Kelas harus di isi')
    return
  }
  var objSave =
  {
    'kelas': {
      'id': item.value.id ? item.value.id : '',
      'namakelas': item.value.namakelas,
      'reportdisplay': item.value.reportdisplay ? item.value.reportdisplay : null,
      'statusenabled': item.value.statusenabled ? item.value.statusenabled : null,
      'kodebios': item.value.kodebios ? item.value.kodebios : null,
      'kodebpjsnaikkelas': item.value.kodebpjsnaikkelas ? item.value.kodebpjsnaikkelas : null,
    }
  }
  isLoadingTT.value = true
  await useApi().post(
    `/sysadmin/save-master-kelas`, objSave).then((response: any) => {
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
    `/sysadmin/delete-master-kelas`, { 'id': e.id }).then((response: any) => {
      fetchData ()
    }, (error) => {
    })
}

function clear() {
  item.value.id = ''
  item.value.namakelas = ''
  item.value.reportdisplay = ''
  item.value.kodebios = ''
  item.value.kodebpjsnaikkelas = ''
}

function changeView(e: any) {
  selectView.value = e
}
function filter() {
  fetchData()
}
function changeSwitch(e: any) {
  fetchData()
}
function clearFilter() {
  fetchData()
}
filter()

fetchData()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';



</style>