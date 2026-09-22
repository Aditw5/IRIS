<template>
  <ConfirmDialog />
  <VCard>
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Ruangan</h3>
    </div>

    <div class="columns is-multiline">

      <div class="column is-9">

        <div class="user-grid-toolbar">
          <VField class="switch-filter">
            <VControl>
              <InputSwitch v-model="item.aktif" @change="fetchData()" />
            </VControl>
            <span>Aktif</span>
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
              <span> Tambah Data</span>
            </VButton>
          </div>
        </div>

        <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
          <DataTable :value="dataSourcefiltered" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">

            <Column field="no" header="Kode"></Column>
            <Column field="namaruangan" header="Ruangan" :sortable="true"></Column>
            <Column field="lokasi" header="Lokasi" :sortable="true"></Column>
            <Column :exportable="false" header="Action">
              <template #body="slotProps">
                <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined raised
                  v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                </VIconButton>
                <VIconButton type="button" icon="fas fa-trash" class="mr-3" color="danger" circle outlined raised
                  v-tooltip.top="'Hapus'" @click="DialogConfirm(slotProps.data)">
                </VIconButton>
              </template>
            </Column>
          </DataTable>
        </div>
        <div class="tile-grid tile-grid-v1" v-else-if="selectView == 'grid'">
          <TransitionGroup name="list" tag="div" class="columns is-multiline">
            <div v-for="(item, key) in dataSourcefiltered" :key="key" class="column is-6">
              <div class="tile-grid-item">
                <div class="tile-grid-item-inner">
                  <VAvatar size="medium" :picture="item.icons != null ? item.icons : '/images/avatars/svg/room.svg'"
                    color="primary" squared bordered />
                  <div class="meta">
                    <span class="dark-inverted">{{ item.namaruangan }}</span>
                    <VTag :color="item.idlokasi == 1 ? 'warning':'info'" :label="(item.lokasi?item.lokasi:'-')" class="mt-1" />
                  </div>
                  <VDropdown icon="feather:more-vertical" spaced right>
                    <template #content>
                      <a role="menuitem" class="dropdown-item is-media" @click="edit(item)">
                        <div class="icon">
                          <i class="iconify" data-icon="feather:edit" aria-hidden="true"></i>
                        </div>
                        <div class="meta">
                          <span>Edit</span>
                          <span>Untuk merubah data </span>
                        </div>
                      </a>
                      <a role="menuitem" class="dropdown-item is-media" @click="DialogConfirm(item)">
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
            <h3 class="title is-5 mb-2 mr-1" style="margin-top: 1rem;">Filter</h3>
          </div>
          <div class="column is-6">
            <img src="/images/avatars/svg/keluar.svg" alt="" srcset="" style="margin-top: -3rem;" />
          </div>
          <div class="column is-12">
            <VField style="margin-top: -1rem;">
              <VControl icon="feather:search">
                <input v-model="filters" class="input custom-text-filter" placeholder="Filter Ruangan" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField class="is-autocomplete-select">
              <VLabel>Lokasi</VLabel>
              <VControl icon="feather:search">
                <AutoComplete v-model="item.lokasifk" :suggestions="d_lokasikalibrasi"
                  @complete="fetchLokasi($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                  class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                  placeholder="ketik untuk mencari..." />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VButton @click="filter()" :loading="isLoading" type="button" icon="feather:search"
              class="is-fullwidth mr-3" color="info" raised>
              Pencarian
            </VButton>

          </div>
        </div>
      </div>
    </div>
    <template>
    </template>
    <VModal :open="modalInput" title="Tambah Ruangan" actions="right" @close="modalInput = false">
      <template #content>
        <form class="modal-form">
          <div class="columns is-multiline">
            <div class="column is-12">
              <VField label="Nama Ruangan">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.namaruangan" placeholder="Nama Ruangan" class="is-rounded" />
                </VControl>
              </VField>
            </div>
            <div class="column " :class="item.id ? 'is-8' : 'is-12'">
              <VField class=" is-rounded-select is-autocomplete-select">
                <VLabel>Lokasi</VLabel>
                <VControl icon="fas fa-home" class="prime-auto">
                  <AutoComplete v-model="item.lokasi" :suggestions="d_lokasikalibrasi"
                    @complete="fetchLokasi($event)" :optionLabel="'label'" :dropdown="true" :minLength="3"
                    class="is-input" :appendTo="'body'" :loadingIcon="'pi pi-spinner'" :field="'label'"
                    placeholder="ketik untuk mencari..." />
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
        </form>
      </template>
      <template #action>
        <VButton icon="feather:plus" @click="save()" :loading="isLoadingTT" color="primary" raised>Simpan</VButton>
      </template>
    </VModal>
  </VCard>
</template>

<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputSwitch from 'primevue/inputswitch'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import moment from 'moment'
import * as H from '/@src/utils/appHelper'
import Dropdown from 'primevue/dropdown';
import { useViewWrapper } from '/@src/stores/viewWrapper'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import AutoComplete from 'primevue/autocomplete';

useHead({
  title: 'Ruangan - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const confirm = useConfirm()
const item: any = ref({
  aktif: true
})

const d_status = [
  { value: 't', label: 'True' },
  { value: 'f', label: 'False' },
]
const modalInput = ref(false)
const d_lokasikalibrasi = ref([])
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
const selectView: any = ref()
selectView.value = 'grid'
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
      items.namaruangan.match(new RegExp(filters.value, 'i'))
    )
  })
})

const route = useRoute()
isLoading.value = false

async function fetchData() {
  isLoading.value = true
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = offset * limit - limit
  let rows: any = currentPage.value.rows
  let Ruangan = ''
  let NamaLokasi = ''
  let id = ''
  let StatusEnabled = ''

  if (item.namaruangan) Ruangan = '&ruangan=' + item.namaruangan
  if (item.value.lokasifk) NamaLokasi = '&lokasifk=' + item.value.lokasifk.value
  if (item.id) id = '&id=' + item.id
  item.value.aktif ? StatusEnabled = '&statusenabled=' + item.value.aktif : StatusEnabled = '&statusenabled=false'

  const response = await useApi().get(
    '/sysadmin/master-ruangan?statusenabled=' + item.value.aktif + '&offset=' + offset +
    '&limit=' + limit +
    '&rows=' + rows +
    id + Ruangan + NamaLokasi + StatusEnabled
  )
  isLoading.value = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x];
    element.no = x + 1
  }

  dataSource.value = response.data

}

const fetchLokasi = async (filter: any) => {
  await useApi().get(
    `general/dropdown/lokasikalibrasi_m?select=id,lokasi&param_search=lokasi&query=${filter.query}&limit=10`
  ).then((response) => {
    d_lokasikalibrasi.value = response
  })
}

function add() {
  clear()
  modalInput.value = true
}

function edit(e: any) {
  console.log(e)

  item.value.id = e.id
  item.value.namaruangan = e.namaruangan 
  item.value.lokasi = { id: e.idlokasi, label: e.lokasi }
  item.value.statusenabled = e.statusenabled
  modalInput.value = true
}

async function save() {
  console.log(item)
  if (!item.value.namaruangan) {
    useToaster().error('Nama Ruangan harus di isi')
    return
  }
  if (!item.value.lokasi.value) {
    useToaster().error('Lokasi harus di isi')
    return
  }
  var objSave =
  {
    'ruangan': {
      'id': item.value.id ? item.value.id : '',
      'namaruangan': item.value.namaruangan,
      'lokasifk': item.value.lokasi.value,
      'statusenabled': item.value.statusenabled ? item.value.statusenabled : null,
    }
  }
  isLoadingTT.value = true
  await useApi().post(
    `/sysadmin/save-master-ruangan`, objSave).then((response: any) => {
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
    `sysadmin/delete-master-ruangan`, { 'id': e.id }).then((response: any) => {
      fetchData()
    }, (error) => {
    })
}
const DialogConfirm = (e: any) => {
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

function clear() {

  item.value.id = ''
  item.value.objectdepartemenfk = ''
  item.value.namaruangan = ''
  modalInput.value = false
}

function changeView(e: any) {
  selectView.value = e
}

fetchData()

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';
</style>