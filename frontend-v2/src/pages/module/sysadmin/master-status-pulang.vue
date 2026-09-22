<template>
  <ConfirmDialog />

  <VCard>
    <div class="columns column c-title">
      <h3 class="title is-5 mb-2 mr-1">Status Pulang</h3>
    </div>

    <div class="columns is-multiline">
      <div class="column is-8">
        <div class="user-grid-toolbar">
          <VControl icon="feather:search">
            <input v-model="filters" class="input custom-text-filter" placeholder="Search..." />
          </VControl>
          <div class="column is-6">
            <VControl class="is-pulled-right">
              <VSwitchBlock v-model="isAktif" label="Aktif" color="danger" checked />
            </VControl>
          </div>
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
          </div>
        </div>

        <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
          <DataTable :value="dataSourcefiltered" :paginator="true" :rows="5" :rowsPerPageOptions="[5, 10, 25]"
            :loading="isLoading" class="p-datatable-sm"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
            <Column field="no" header="#"></Column>
            <Column field="statuspulang" header="Status Pulang" :sortable="true"></Column>
            <Column field="kodeexternal" header="Kode" :sortable="true"></Column>
            <Column header="Status" :exportable="false" style="text-align: center">
              <template #body="slotProps">
                <VTag :color="isAktif == true ? 'primary' : 'danger'" :label="slotProps.data.status" rounded />
              </template>
            </Column>
            <Column :exportable="false" header="Action" style="text-align: center">
              <template #body="slotProps">
                <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined raised
                  v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                </VIconButton>
                <VIconButton type="button" :disabled="!isAktif" icon="fas fa-trash" color="danger" circle outlined raised
                  v-tooltip.top="'Hapus'" @click="DialogConfirm(slotProps.data)">
                </VIconButton>
              </template>
            </Column>
          </DataTable>
        </div>

        <div class="tile-grid tile-grid-v1" v-else-if="selectView == 'grid'">
          <TransitionGroup name="list" tag="div" class="columns is-multiline">
            <!--Grid item-->
            <div v-if="isLoading" v-for="(dumy) in 10" :key="dumy" class="column is-6">
              <div class="tile-grid-item">
                <div class="tile-grid-item-inner">
                  <VPlaceloadAvatar class="mx-1" size="medium" />
                  <VPlaceloadText />
                </div>
              </div>
            </div>
            <div v-else v-for="(item, key) in dataSourcefiltered" :key="key" class="column is-6">
              <div class="tile-grid-item">
                <div class="tile-grid-item-inner">
                  <VIconBox size="medium" color="danger" rounded>
                    <i class="fas fa-envelope-open"></i>
                  </VIconBox>

                  <div class="meta">
                    <span class="dark-inverted mb-2">{{ item.statuspulang }}</span>
                  </div>
                  <VTag :color="item.status_c" :label="item.status" style="margin-left: 25px" />
                  <VDropdown icon="feather:more-vertical" spaced right style="cursor:pointer">
                    <template #content>
                      <a role="menuitem" class="dropdown-item is-media">
                        <div class="icon">
                          <i class="iconify" data-icon="feather:bookmark" aria-hidden="true"></i>
                        </div>
                        <div class="meta" @click="detail(item)">
                          <span>Detail</span>
                          <span>Untuk melihat data </span>
                        </div>
                      </a>
                      <a role="menuitem" class="dropdown-item is-media">
                        <div class="icon">
                          <i class="iconify" data-icon="feather:edit" aria-hidden="true"></i>
                        </div>
                        <div class="meta" @click="edit(item)">
                          <span>Edit</span>
                          <span>Untuk merubah data </span>
                        </div>
                      </a>
                      <a role="menuitem" v-if="item.statusenabled == true" class="dropdown-item is-media">
                        <div class="icon">
                          <i aria-hidden="true" class="lnil lnil-trash-can-alt"></i>
                        </div>
                        <div class="meta" @click="DialogConfirm(item)">
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
        <img src="/images/avatars/svg/keluar.svg" alt="" srcset=""
          style="max-width: 32%; margin-left: 16rem; margin-top: -1rem" />
        <VCard>
          <div class="columns is-multiline">
            <div class="column is-6">
              <h3 class="title is-6 mb-2 mr-1">
                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                {{ item.id ? 'Edit Data' : 'Tambah Data' }}
              </h3>
            </div>
            <div class="column is-12">
              <VField label="Status Pulang">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.statuspulang" placeholder="Status Pulang" class="is-rounded" />
                </VControl>
              </VField>
            </div>

            <div class="column is-12" v-if="item.id">
              <VField label="Aktivasi" style="display: flex; align-items: center">
                <VControl class="is-pulled-right" style="margin-left: -6.5rem">
                  <VSwitchBlock v-model="item.statusenabled" label="Aktif" color="danger" />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VButton @click="save(item)" :loading="isLoadingButton" type="button"
                :icon="item.id ? 'feather:edit' : 'feather:save'" class="is-fullwidth mr-3"
                :color="item.id ? 'info' : 'success'" raised>
                {{ item.id ? 'Update Data' : 'Simpan Data' }}
              </VButton>
              <VButton v-if="item.id" @click="clear()" type="button" icon="feather:x-circle"
                class="is-fullwidth is-outlined is-warning mt-3" raised>
                Batal Edit
              </VButton>
            </div>
          </div>
        </VCard>
      </div>
    </div>

    <VModal :open="modalDetail" title="Detail Status Pulang" actions="right" @close="; (modalDetail = false), clear()">
      <template #content>
        <form class="modal-form">
          <div class="columns is-multiline">
            <div class="column is-12">
              <VField label="Status Pulang">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.statuspulang" placeholder="Status Pulang" class="is-rounded" readonly
                    style="cursor: pointer" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Kode External">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.kodeexternal" placeholder="Kode External" class="is-rounded" readonly
                    style="cursor: pointer" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Status Aktivasi">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.status" placeholder="Status Aktivasi" class="is-rounded" readonly
                    style="cursor: pointer" />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField label="Kode Status Pulang">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.reportdisplay" placeholder="Report Display" class="is-rounded"
                    readonly style="cursor: pointer" />
                </VControl>
              </VField>
            </div>
          </div>
        </form>
      </template>
    </VModal>
  </VCard>
</template>

<script setup lang="ts">
import { useApi } from '/@src/composable/useApi'
import { ref, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ConfirmDialog from 'primevue/confirmdialog'
import { useHead } from '@vueuse/head'
import { useConfirm } from 'primevue/useconfirm'
import { useToaster } from '/@src/composable/toaster'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { watch } from 'vue'
useHead({
  title: 'Status Pulang - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const item: any = ref({ isAktif: true })
const modalDetail = ref(false)
const isAktif = ref(true)
const confirm = useConfirm()

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
let isLoadingButton: any = ref(false)

const filters = ref('')

const dataSourcefiltered = computed(() => {
  if (!filters.value) {
    return dataSource.value
  }
  return dataSource.value.filter((items: any) => {
    return items.statuspulang.match(new RegExp(filters.value, 'i'))
  })
})

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

const fetchData = async () => {

  isLoading.value = true
  await useApi().get(`/sysadmin/master-status-pulang?statusenabled=${isAktif.value}`).then((response: any) => {
    response.data.forEach((items: any, i: any) => {
      items.no = i + 1
    })
    isLoading.value = false
    dataSource.value = response.data
  })
}

const edit = (e: any) => {
  item.value.id = e.id
  item.value.kdstatuspulang = e.kdstatuspulang
  item.value.statuspulang = e.statuspulang
  item.value.statusenabled = e.statusenabled
  item.value.namaexternal = e.namaexternal
  item.value.reportdisplay = e.reportdisplay
}

const detail = (e: any) => {
  item.value.id = e.id
  item.value.statuspulang = e.statuspulang
  item.value.namaexternal = e.namaexternal
  item.value.reportdisplay = e.reportdisplay
  item.value.kodeexternal = e.kodeexternal
  item.value.status = e.status
  modalDetail.value = true
}

const save = async (item: any) => {
  isLoadingButton.value = true
  if (!item.statuspulang) {
    useToaster().error('Nama Status Pulang harus di isi')
    isLoadingButton.value = false
    return
  }
  var objSave = {
    statuspulang: {
      id: item.id ? item.id : '',
      statusenabled: item.statusenabled ? item.statusenabled : '',
      statuspulang: item.statuspulang,
      kodeexternal: item.statuspulang,
    },
  }
  await useApi()
    .post(`/sysadmin/master-status-pulang/save`, objSave)
    .then(
      (response: any) => {
        isLoadingButton.value = false
        clear()
        fetchData()
      },
      (error) => {
        isLoadingButton.value = false
        // console.log(error)
      }
    )
}

const deleterow = async (e: any) => {
  isLoading.value = true
  await useApi()
    .post(`/sysadmin/master-status-pulang/delete`, { id: e.id })
    .then(
      (response: any) => {
        clear()
        fetchData()
        isLoading.value = false
      },
      (error) => { console.log(error) }
    )
}

const clear = () => {
  item.value.id = ''
  item.value.statuspulang = ''
}

const changeView = (e: any) => {
  selectView.value = e
}

fetchData()

watch(isAktif, () => {
  fetchData()
})

</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';
</style>
