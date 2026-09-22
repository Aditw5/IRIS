<template>
  <ConfirmDialog />
  <VCard>
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Kedudukan Pegawai</h3>
    </div>

    <div class="columns is-multiline">

      <div class="column is-8">
        <div class="user-grid-toolbar">
          <VControl icon="feather:search">
            <input v-model="filters" class="input custom-text-filter" placeholder="Search..." />
          </VControl>
          <div class="column is-6 switch-filter">
            <VControl>
              <InputSwitch v-model="item.aktif" @change="fetchData()" />
            </VControl>
            <span>Aktif</span>
          </div>
          <div class="buttons ml-4">
            <VField v-slot="{ id }" class="is-icon-select">
              <VControl class="mr-0">
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
          <DataTable :value="dataSourcefiltered" class="p-datatable-sm" :loading="isLoading" :paginator="true" :rows="10"
            :rowsPerPageOptions="[5, 10, 25]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

            <Column field="no" header="#"></Column>
            <Column field="kedudukan" header="Kedudukan Pegawai" :sortable="true"></Column>
            <Column field="kodeexternal" header="Kode" :sortable="true"></Column>
            <Column field="status" header="status" :sortable="true"></Column>
            <Column :exportable="false" header="Action" style="text-align:center;">
              <template #body="slotProps">
                <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined raised
                  v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                </VIconButton>
                <VIconButton type="button" v-if="item.aktif == true" icon="fas fa-trash" class="mr-3" color="danger"
                  circle outlined raised v-tooltip.top="'Hapus'" @click="dialogConfirm(slotProps.data)">
                </VIconButton>
                <VIconButton type="button" v-else disabled icon="fas fa-trash" class="mr-3" color="danger" circle outlined
                  raised v-tooltip.top="'Hapus'">
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
                  <VAvatar size="medium" picture="/images/avatars/svg/produk.svg" color="primary" squared bordered />
                  <div class="meta">
                    <span class="dark-inverted">{{ item.kedudukan }}</span>
                    <!-- <span> Departemen : {{ item.namadepartemen }}</span> -->
                  </div>
                  <VTag :color="item.status_c" :label="item.status" style="margin-left:25px" />
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
                      <a role="menuitem" v-if="item.statusenabled == true" class="dropdown-item is-media"
                        @click="dialogConfirm(item)">
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
        <img src="/images/avatars/label/pegawai.png" class="mix-2" style="margin-bottom: -5px;margin-top:-56px">
        <VCard>
          <div class="columns is-multiline">
            <div class="column is-6">
              <h3 v-if="item.id" class="title is-6 mb-2 mr-1">
                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                Edit Data
              </h3>
              <h3 v-else class="title is-6 mb-2 mr-1">
                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                Tambah Data
              </h3>
            </div>
            <div class="column is-12">
              <VField label="Kondisi Pasien">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.kedudukan" placeholder="Kedudukan Pegawai" class="is-rounded" />
                </VControl>
              </VField>
            </div>

            <div class="column is-12" v-if="item.id">
              <VField label="Aktivasi" style="display:flex;align-items:center">
                <VControl class="is-pulled-right" style="margin-left:-6.5rem">
                  <VSwitchBlock v-model="item.statusenabled" label="Aktif" color="danger" />
                </VControl>
              </VField>
            </div>

            <div v-if="item.id" class="column is-12">
              <VButton @click="save()" :loading="isLoadingBtn" type="button" icon="feather:edit" class="is-fullwidth mr-3"
                color="info" raised>
                Update Data
              </VButton>
              <VButton @click="clear()" type="button" icon="feather:x-circle"
                class="is-fullwidth is-outlined is-warning mt-3" raised>
                Batal Edit
              </VButton>
            </div>
            <div v-else class="column is-12">
              <VButton @click="save()" :loading="isLoadingBtn" type="button" icon="feather:save" class="is-fullwidth mr-3"
                color="success" raised>
                Simpan Data
              </VButton>
            </div>
          </div>
        </VCard>
      </div>

    </div>
    <VModal :open="modalDetail" title="Detail Kedudukan Pegawai" actions="right" @close="modalDetail = false, clear()">
      <template #content>
        <form class="modal-form">
          <div class="columns is-multiline">
            <div class="column is-12">
              <VField label="Kedudukan Pegawai">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.kedudukan" placeholder="Kedudukan Pegawai" class="is-rounded" readonly
                    style="cursor:pointer" />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField label="Kode External">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.kodeexternal" placeholder="Kode External" class="is-rounded" readonly
                    style="cursor:pointer" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Status Aktivasi">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.status" placeholder="Status Aktivasi" class="is-rounded" readonly
                    style="cursor:pointer" />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <VField label="Report Display">
                <VControl icon="feather:bookmark">
                  <VInput type="text" v-model="item.reportdisplay" placeholder="Report Display" class="is-rounded"
                    readonly style="cursor:pointer" />
                </VControl>
              </VField>
            </div>
          </div>
        </form>
      </template>

    </VModal>
  </VCard>
</template>
  
<script  setup lang="ts">
import { useApi } from '/@src/composable/useApi'
import { ref, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ConfirmDialog from 'primevue/confirmdialog'
import InputSwitch from 'primevue/inputswitch'
import { useConfirm } from 'primevue/useconfirm'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'

import { useViewWrapper } from '/@src/stores/viewWrapper'
useHead({
  title: 'Kedudukan Pegawai - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
const confirm = useConfirm()

let dataSource: any = ref([])
const item: any = ref({
  aktif: true
})

const modalDetail = ref(false)

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

let isLoadingBtn: any = ref(false)
let isLoading: any = ref(false)

const filters = ref('')
const dataSourcefiltered = computed(() => {
  if (!filters.value) {
    return dataSource.value
  }

  return dataSource.value.filter((items: any) => {
    return (
      items.kedudukan.match(new RegExp(filters.value, 'i'))
    )
  })
})

const fetchData = async () => {
  isLoading.value = true

  await useApi().get(`/sysadmin/master-kedudukan-pegawai?statusenabled=${item.value.aktif}`).then((response: any) => {
    response.data.forEach((element: any, i: any) => {
      element.no = i + 1
    })
    dataSource.value = response.data
  })
  isLoading.value = false

}

const save = async () => {
  if (!item.value.kedudukan) {
    useToaster().error('Kedudukan Pegawai harus di isi')
    return
  }

  var objSave =
  {
    'kedudukan': {
      'id': item.value.id ? item.value.id : '',
      'kedudukan': item.value.kedudukan,
      'statusenabled': item.value.statusenabled,
    }
  }
  isLoadingBtn.value = true
  await useApi().post(
    `/sysadmin/master-kedudukan-pegawai/save`, objSave).then((response: any) => {
      isLoadingBtn.value = false
      clear()
      fetchData()
    }, (error) => {
      isLoadingBtn.value = false
      // console.log(error)
    })
}

const deleteItem = async (e: any) => {
  isLoading.value = true
  await useApi().post(
    `/sysadmin/master-kedudukan-pegawai/delete`, { 'id': e.id }).then((response: any) => {
      clear()
      fetchData()
      isLoading.value = false
    }, (error) => {
    })
}

const dialogConfirm = (e: any) => {
  confirm.require({
    message: 'Apakah anda serius menghapus data ini ?',
    header: 'Konfirmasi Hapus Data',
    icon: 'pi pi-info-circle',
    acceptClass: 'p-button-danger',
    accept: () => {
      deleteItem(e)

    },
    reject: () => { },
  })
}

const edit = (e: any) => {
  item.value.id = e.id
  item.value.kedudukan = e.kedudukan
  item.value.statusenabled = e.statusenabled
}
const detail = (e: any) => {
  item.value.id = e.id
  item.value.kedudukan = e.kedudukan
  item.value.kodeexternal = e.kodeexternal
  item.value.status = e.status

  modalDetail.value = true
}

const changeView = (e: any) => {
  selectView.value = e
}

const clear = () => {
  item.value.id = ''
  item.value.kedudukan = ''
}

fetchData()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';
</style>