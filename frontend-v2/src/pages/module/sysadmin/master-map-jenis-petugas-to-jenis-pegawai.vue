<template>
  <VCard radius="small">
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Mapping Jenis Petugas To Jenis Pegawai</h3>
    </div>
    <div class="columns is-multiline">
      <div class="column is-9">
        <div class="user-grid-toolbar">

          <div class="buttons">

            <VField>
              <VControl icon="feather:search">
                <input v-model="filters" class="input custom-text-filter" placeholder="Search..." />
              </VControl>
            </VField>
            <VButton color="primary" class="is-pulled-right mr-3" outlined raised>
              <RouterLink :to="{ name: 'module-sysadmin-mapping-jenis-petugas-to-jenis-pegawai' }">
                <i class="fa fa-plus"></i> Mapping Petugas
              </RouterLink>
            </VButton>
          </div>
        </div>

        <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
          <DataTable :value="dataSourcefiltered" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">

            <Column field="id" header="Kode"></Column>
            <Column field="jenispetugaspe" header="Jenis Petugas" :sortable="true"></Column>
            <Column field="jenispegawai" header="Jenis Pegawai"></Column>
            <Column :exportable="false" header="Action">
              <template #body="slotProps">
                <VIconButton type="button" icon="pi pi-pencil" class="mr-5" color="info" square outlined raised
                  v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                </VIconButton>
                <VIconButton type="button" icon="fas fa-trash" class="mr-5" color="danger" circle outlined raised
                  v-tooltip.top="'Hapus'" @click="deleterow(slotProps.data)">
                </VIconButton>
              </template>
            </Column>
          </DataTable>
        </div>
        <div class="user-grid tile-grid-v1" v-else-if="selectView == 'grid'">
          <TransitionGroup name="list" tag="div" class="columns is-multiline">
            <!--Grid item-->
            <div v-for="(item, key) in dataSourcefiltered" :key="key" class="column is-6">
              <div class="tile-grid-item">
                <div class="tile-grid-item-inner">
                  <VAvatar size="medium" picture="/images/avatars/svg/room.svg" color="primary" squared bordered />
                  <div class="meta">
                    <span class="dark-inverted">{{ item.jenispetugaspe }}</span>
                    <span>{{ item.jenispegawai }}</span>
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
              <VLabel>Jenis Petugas Pelaksana</VLabel>
              <VControl icon="feather:search">
                <Multiselect mode="single" v-model="item.jenispetugaspe" :options="d_jenispetugas"
                  placeholder="Pilih Jenis Petugas " :searchable="true" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField class="is-autocomplete-select">
              <VLabel>Jenis Pegawai</VLabel>
              <VControl icon="feather:search">
                <Multiselect mode="single" v-model="item.jenispegawai" :options="d_jenispegawai"
                  placeholder="Pilih Jenis Pegawai" :searchable="true" />
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
    <template>
    </template>

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
  title: 'Map Jenis Petugas To Jenis Pelaksana - ' + import.meta.env.VITE_PROJECT,
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
const selectView: any = ref()
selectView.value = 'list'
const d_jenispetugas = ref([])
const d_jenispegawai = ref([])
const router = useRouter()

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
      items.jenispetugaspe.match(new RegExp(filters.value, 'i'))
      || items.jenispegawai.match(new RegExp(filters.value, 'i'))
    )
  })
})

const route = useRoute()
isLoading.value = false


async function fetchData() {
  isLoadingTT.value = true
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = offset * limit - limit
  let rows: any = currentPage.value.rows
  let JenisPegawai = ''
  let JenisPetugasPe = ''
  let id = ''

  if (item.value.jenispegawai) JenisPegawai = '&objectjenispegawaifk=' + item.value.jenispegawai
  if (item.value.jenispetugaspe) JenisPetugasPe = '&objectjenispetugaspefk=' + item.value.jenispetugaspe
  if (item.Sid) id = '&id=' + item.id

  const response = await useApi().get(
    '/sysadmin/master-map-jenis-petugas-to-jenis-pegawai?offset=' + offset +
    '&limit=' + limit +
    '&rows=' + rows +
    id + JenisPegawai + JenisPetugasPe
  )
  isLoadingTT.value = false
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
    `/sysadmin/master-map-jenis-petugas-to-jenis-pegawai?statusenabled=${item.value.aktif}`).then((response: any) => {
      dataSource.value = response.data
    })
}
function loadDropdown() {
  d_jenispegawai.value = []
  useApi().get(
    `/sysadmin/master-map-jenis-petugas-to-jenis-pegawai-dropdown`).then((response: any) => {
      d_jenispegawai.value = response.jenispegawai.map((e: any) => { return { label: e.jenispegawai, value: e.id } })
      d_jenispetugas.value = response.jenispetugaspe.map((e: any) => { return { label: e.jenispetugaspe, value: e.id } })
    })
}

function add() {
  clear()
  modalInput.value = true
}

function edit(e: any) {
  router.push({
    name: 'module-sysadmin-mapping-jenis-petugas-to-jenis-pegawai',
    query: {
      objectjenispetugaspefk: e.objectjenispetugaspefk,
    },
  })
}


async function deleterow(e: any) {
  await useApi().post(
    `/sysadmin/delete-map-jenis-petugas-to-jenis-pegawai`, { 'id': e.id }).then((response: any) => {
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
function clearFilter() {
  fetchData()
}
filter()

loadDropdown()

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';

</style>