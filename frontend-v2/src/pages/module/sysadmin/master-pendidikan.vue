<template>
  <VCard>
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Data Pendidikan</h3>
    </div>

    <div class="columns is-multiline">
      <div class="column is-8">
        <div class="user-grid-toolbar">
          <VControl icon="feather:search">
            <input v-model="filters" class="input custom-text-filter" placeholder="Cari Data ... " />
          </VControl>
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
            <VControl class="is-pulled-right">
            <VSwitchBlock v-model="item.aktif" label="Aktif" color="danger" @change="changeSwitch(item.aktif)" />
          </VControl>
          </div>
        </div>

        <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
          <DataTable :value="dataSourcefiltered" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

            <Column field="no" header="No"></Column>
            <Column field="pendidikan" header="Pendidikan" :sortable="true"></Column>
            <Column field="jenispendidikan" header="Jenis Pendidikan" :sortable="true"></Column>
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
        <div class="tile-grid tile-grid-v1" v-else-if="selectView == 'grid'">
          <TransitionGroup name="list" tag="div" class="columns is-multiline">
            <!--Grid item-->
            <div v-for="(item, key) in dataSourcefiltered" :key="key" class="column is-6">
              <div class="tile-grid-item">
                <div class="tile-grid-item-inner">
                  <VAvatar size="medium" picture="/images/avatars/svg/pendidikan.svg" color="primary" squared
                    bordered />
                  <div class="meta">
                    <span class="dark-inverted">{{ item.pendidikan }}</span>
                    <span>Jenis : {{ item.jenispendidikan }}</span>
                  </div>
                  <VTag :color="item.status_c" :label="item.status" style="margin-left : 6rem"/>
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
                          <span>Hapus Data dari Daftar</span>
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
      <div class="column is-4">
        <img src="/images/avatars/label/pendidikan.png" alt="" srcset=""
          style="max-width:65%;margin-left: 8rem; margin-top: -5rem; opacity: .7;">
        <VCard style="margin-top : -1rem;">
          <div class="columns is-multiline">
            <div class="column is-6">
              <h3 class="title is-6 mb-2 mr-1">
                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                Tambah Data
              </h3>
            </div>
            <div class="column is-12">
              <VField label="Pendidikan">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.pendidikan" placeholder="Pendidikan" class="is-rounded" />
                </VControl>
              </VField>
            </div>
            <div class="column is-9">
              <VField class="is-rounded-select is-autocomplete-select">
                <VLabel>Jenis Pendidikan</VLabel>
                <VControl icon="feather:search">
                  <Multiselect mode="single" v-model="item.objectjenispendidikanfk" :options="d_JenisPendidikan"
                    placeholder="Jenis Pendidikan" :searchable="true" />
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
            <div class="column is-6" v-if="item.id">
              <VButton @click="clear()" :loading="isLoadingTT" type="button" icon="fas fa-backspace"
                class="is-fullwidth mr-3" color="warning" raised rounded>
                Batal Edit
              </VButton>
            </div>
            <div class="column is-6">
              <VButton @click="save()" :loading="isLoadingTT" type="button" icon="feather:save"
                class="is-fullwidth mr-3" color="success" raised rounded>
                Simpan 
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
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import moment from 'moment'
import { useViewWrapper } from '/@src/stores/viewWrapper'
useHead({
  title: 'Pendidikan - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const item: any = ref({
  aktif: true
})
const modalInput = ref(false)
const modalDetail = ref(false)
const d_JenisPendidikan = ref([])

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
      items.pendidikan.match(new RegExp(filters.value, 'i')) ||
      items.jenispendidikan.match(new RegExp(filters.value, 'i'))
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
  let Pendidikan = ''
  let JenisPendidikan = ''
  let kdPendidikan = ''
  let StatusEnabled = ''

  if (item.pendidikan) Pendidikan = '&pendidikan' + item.pendidikan
  if (item.jenispendidikan) JenisPendidikan = '&jenispendidikan=' + item.jenispendidikan
  if (item.kdpendidikan) kdPendidikan = '&kdpendidikan=' + item.kdpendidikan
  item.value.aktif ? StatusEnabled = '&statusenabled=' + item.value.aktif : StatusEnabled = '&statusenabled=false'
  const response = await useApi().get(
    '/sysadmin/master-pendidikan?offset=' + offset +
    '&limit=' + limit +
    '&rows=' + rows +
    kdPendidikan + Pendidikan + JenisPendidikan + StatusEnabled
  )
  isLoading = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x];
    element.no = x + 1
  }

  dataSource.value = response.data
  //   dataSource.value.total = response.length
}

function loadDropdown() {
  d_JenisPendidikan.value = []
  useApi().get(
    `/sysadmin/master-pendidikan-dropdown`).then((response: any) => {
      d_JenisPendidikan.value = response.jenispendidikan.map((e: any) => { return { label: e.jenispendidikan, value: e.id } })
    })
}

function loadData() {
  fetchData()
}

function add() {
  clear()
  modalInput.value = true
}

function edit(e: any) {
  item.value.id = e.id
  item.value.pendidikan = e.pendidikan
  item.value.objectjenispendidikanfk = e.objectjenispendidikanfk
  item.value.statusenabled = e.statusenabled
}
function detail(e: any) {
  item.value.id = e.id
  item.value.pendidikan = e.pendidikan
  item.value.objectjenispendidikanfk = e.objectjenispendidikanfk
  item.value.statusenabled = e.statusenabled
}


async function save() {
  if (!item.value.pendidikan) {
    useToaster().error('Pendidikan harus di isi')
    return
  }
  var objSave =
  {
    'pendidikan': {
      'id': item.value.id ? item.value.id : '',
      'pendidikan': item.value.pendidikan,
      'objectjenispendidikanfk': item.value.objectjenispendidikanfk,
      'statusenabled' : item.value.statusenabled ? item.value.statusenabled : null,
    }
  }
  isLoadingTT.value = true
  await useApi().post(
    `/sysadmin/save-pendidikan`, objSave).then((response: any) => {
      isLoadingTT.value = false
      clear()
      fetchData()
    }, (error) => {
      isLoadingTT.value = false
      // console.log(error)
    })
}

async function deleterow(e: any) {
  await useApi().post(
    `/sysadmin/delete-pendidikan`, { 'id': e.id }).then((response: any) => {
      fetchData()
    }, (error) => {
    })
}

function clear() {
  item.value.id = ''
  item.value.pendidikan = ''
  item.value.objectjenispendidikanfk = ''

}
function changeView(e: any) {
  selectView.value = e
}
function filter() {
  fetchData()
}
function clearFilter() {
  delete item.pendidikan
  delete item.objectjenispendidikanfk
  fetchData()
}
function changeSwitch(e: any) {
  fetchData()
}
// loadData()
loadDropdown()
fetchData()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';

</style>