<template>
  <VCard>
    <!-- <TabView class="tabview-custom mt-3" :scrollable="true" @tab-click="klikTab($event)">
      <TabPanel>
        <template #header>
          <i class="pi pi-list mr-2"></i>
          <span>Modul Aplikasi </span>
        </template> -->
    <div class="columns is-multiline">
      <div class="column is-4">
        <VCard>
          <div class="columns is-multiline">
            <h3 class="title is-5 mb-2 mr-1">Sub Sistem</h3>
            <div class="column is-12">
              <div class="user-grid-toolbar">
                <VControl icon="feather:search">
                  <input v-model="filters" class="input custom-text-filter" placeholder="Search..." />
                </VControl>
                <div class="buttons">
                  <VField v-slot="{ id }" class="is-icon-select">
                    <VControl>
                      <Multiselect v-model="selectView" :attrs="{ id }" placeholder="Select View" label="name"
                        :options="d_View" :searchable="true" track-by="name" mode="single"
                        @select="changeView(selectView)" autocomplete="off">
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
                  currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :class="`p-datatable-small`">
                  <template #header>
                    <div class="flex">
                      <span class="p-input-icon-left">
                        <VButton @click="tambah('Modul')" type="button" icon="feather:plus" class="is-fullwidth mr-3"
                          color="success" raised rounded>
                          Tambah
                        </VButton>
                      </span>
                    </div>
                  </template>
                  <Column field="id" header="Kode"></Column>
                  <Column field="modulaplikasi" header="Modul Aplikasi" :sortable="true"></Column>
                  <!-- <Column field="reportdisplay" header="Report Display" :sortable="true"></Column> -->
                  <Column :exportable="false" header="Action">
                    <template #body="slotProps">
                      <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined raised
                        v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                      </VIconButton>
                      <VIconButton type="button" icon="fas fa-trash" class="mr-3" color="danger" circle outlined raised
                        v-tooltip.top="'Hapus'" @click="deleterow(slotProps.data)">
                      </VIconButton>
                      <VIconButton type="button" icon="fas fa-arrow-right" class="mr-3" color="success" circle outlined
                        raised v-tooltip.top="'Pilih data'" @click="selectedModul(slotProps.data)">
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
                      <VAvatar size="medium" picture="/images/avatars/svg/modul.svg" color="primary" squared bordered />
                      <div class="meta-left">
                        <h3>
                          {{ item.modulaplikasi }}
                        </h3>
                        <span> {{ item.reportdisplay }} </span>
                      </div>
                      <div class="meta-right">
                        <div class="buttons">
                          <VButton color="warning" outlined raised @click="edit(item)"> Edit </VButton>

                          <VIconButton icon="feather:trash" class="hint--bubble hint--primary hint--top"
                            data-hint="Delete" circle @click="deleterow(item)" />
                        </div>
                      </div>
                    </div>
                  </div>
                </TransitionGroup>
              </div>

            </div>
          </div>
        </VCard>
      </div>
      <div class="column is-4">
        <VCard>
          <div class="columns is-multiline">
            <h3 class="title is-5 mb-2 mr-1"> Modul Aplikasi</h3>
            <div class="column is-12">
              <VControl icon="feather:search">
                <input v-model="filters2" class="input custom-text-filter" placeholder="Search..." />
              </VControl>
            </div>
            <div class="column is-12">
              <div class="user-grid user-grid-v2">
                <DataTable :value="dataSourcefilteredSub" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
                  paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                  responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                  currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :class="`p-datatable-small`">
                  <template #header>
                    <div class="flex">
                      <span class="p-input-icon-left">
                        <VButton @click="tambah('Menu')" type="button" icon="feather:plus" class="is-fullwidth mr-3"
                          color="success" raised rounded :disabled="selectedHead == undefined">
                          Tambah
                        </VButton>
                      </span>
                    </div>
                  </template>
                  <Column field="id" header="Kode"></Column>
                  <Column field="modulaplikasi" header="Modul Aplikasi" :sortable="true"></Column>
                  <!-- <Column field="reportdisplay" header="Report Display" :sortable="true"></Column> -->
                  <Column :exportable="false" header="Action">
                    <template #body="slotProps">
                      <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined raised
                        v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                      </VIconButton>
                      <VIconButton type="button" icon="fas fa-trash" class="mr-3" color="danger" circle outlined raised
                        v-tooltip.top="'Hapus'" @click="deleterow(slotProps.data)">
                      </VIconButton>
                      <VIconButton type="button" icon="fas fa-arrow-right" class="mr-3" color="success" circle outlined
                        raised v-tooltip.top="'Pilih data'" @click="selectedSub(slotProps.data)">
                      </VIconButton>
                    </template>
                  </Column>
                </DataTable>
              </div>
            </div>
          </div>
        </VCard>
      </div>
      <div class="column is-4">
        <VCard>
          <h3 class="title is-5 mb-2 mr-1"> Menu</h3>
          <VButton @click="add()" type="button" icon="feather:plus" color="success" raised outlined rounded
            :disabled="selectedIDModul == ''">
            Tambah
          </VButton>
          <Tree v-model:expandedKeys="expandedKeys" v-model:selectionKeys="selectedKey" :value="dataSourceMenu"
            class="w-full md:w-30rem mt-5" :filter="true" filterMode="lenient" @nodeSelect="onNodeSelect"
            selectionMode="single" :metaKeySelection="false">
            <template #default="slotProps">
              <span v-tooltip-prime.bottom="slotProps.node.nourut.toString()">{{ slotProps.node.label }}</span>
            </template>
          </Tree>
        </VCard>
      </div>
    </div>
    <!-- </TabPanel> -->
    <!-- <TabPanel>
        <template #header>
          <i class="pi pi-sliders-h mr-2"></i>
          <span>Objek Modul Aplikasi </span>
        </template>
        <VCard>
          <MasterObjekModulAplikasi v-if="activeTab == 1"></MasterObjekModulAplikasi>
        </VCard>
      </TabPanel>
      <TabPanel>
        <template #header>
          <i class="pi pi-link mr-2"></i>
          <span>Map Modul Aplikasi To Objek Aplikasi </span>
        </template>
        <VCard>
        </VCard>
      </TabPanel>
    </TabView> -->
  </VCard>
  <Dialog v-model:visible="modalModul" modal :header="item.id ? 'Ubah ' : 'Tambah'" :style="{ width: '30vw' }">
    <div class="columns is-multiline">
      <div class="column is-12">
        <VField label="Modul Aplikasi">
          <VControl icon="feather:bookmark">
            <input v-model="item.modulaplikasi" type="text" class="input is-rounded" placeholder="Modul Aplikasi" />
          </VControl>
        </VField>
      </div>
      <!-- <div class="column is-12">
        <VField >
          <VLabel>Jenis</VLabel>
        </VField>
        <div class="columns is-multiline">
          <div class="column is-6">
            <RadioButton v-model="item.reportdisplay" inputId="Modul" name="Modul" value="Modul" />
            <label for="Modul" class="ml-2">Modul</label>
          </div>
          <div class="column is-6">
            <RadioButton v-model="item.reportdisplay" inputId="Menu" name="Menu" value="Menu" />
            <label for="Menu" class="ml-2">Menu</label>
          </div>
        </div>
      </div> -->
      <div class="column is-4">
        <VField class="is-rounded-select is-autocomplete-select">
          <VLabel>Status</VLabel>
          <VControl>
            <VSwitchBlock v-model="item.statusenabled" :options="d_status" label="Aktif" color="danger" />
          </VControl>
        </VField>
      </div>
    </div>
    <template #footer>
      <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="tutup()">
        Tutup
      </VButton>
      <VButton type="button" rounded outlined color="primary" raised icon="feather:save" :loading="isLoading"
        @click="simpan()"> {{ item.id ? 'Ubah' : 'Simpan' }}
      </VButton>
    </template>
  </Dialog>
  <Dialog v-model:visible="modalObjek" modal :header="item.id ? 'Ubah ' + item.objekmodulaplikasi : 'Tambah'"
    :style="{ width: '30vw' }">
    <div class="columns is-multiline">
      <div class="column is-12">
        <VField label="Head" class="is-rounded-select is-autocomplete-select mt-0 pt-0" v-slot="{ id }">
          <VControl icon="fas fa-archway" fullwidth class="prime-auto-select">
            <Dropdown v-model="item.kdobjekmodulaplikasihead" :options="d_ObjectModul" :optionLabel="'label'"
              class="is-rounded" placeholder="Head" style="width: 100%;" :filter="true" showClear
              @change="changeHead($event)" />

          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField label="Nama Menu">
          <VControl icon="feather:bookmark">
            <input v-model="item.objekmodulaplikasi" type="text" class="input is-rounded" placeholder="Nama Menu" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Url Form">
          <VControl icon="feather:external-link">
            <input v-model="item.alamaturlform" type="text" class="input is-rounded" placeholder="Url Form" />
          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField label="Fungsi">
          <VControl icon="feather:bookmark">
            <input v-model="item.fungsi" type="text" class="input is-rounded" placeholder="Fungsi " />
          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField label="Keterangan">
          <VControl icon="feather:bookmark">
            <input v-model="item.keterangan" type="text" class="input is-rounded" placeholder="Keterangan " />
          </VControl>
        </VField>
      </div>
      <div class="column is-12">
        <VField label="No Urut">
          <VControl icon="feather:bookmark">
            <input v-model="item.nourut" type="number" class="input is-rounded" placeholder="No Urut" />
          </VControl>
        </VField>
      </div>
    </div>
    <template #footer>
      <VButton v-if="item.id" type="button" rounded outlined color="danger" raised icon="feather:trash"
        :loading="isLoading" @click="deleteMenu(item)"> Hapus
      </VButton>
      <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="tutup()">
        Tutup
      </VButton>
      <VButton type="button" rounded outlined color="primary" raised icon="feather:save" :loading="isLoading"
        @click="saveMenu()"> {{ item.id ? 'Ubah' : 'Simpan' }}
      </VButton>
    </template>
  </Dialog>
</template>

<script  setup lang="ts">
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useHead } from '@vueuse/head'
import MasterObjekModulAplikasi from './master-objek-modul-aplikasi.vue'
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Dialog from 'primevue/dialog';
import RadioButton from 'primevue/radiobutton';
import { useToaster } from '/@src/composable/toaster'
import moment from 'moment'
import Tree from 'primevue/tree';
import Dropdown from 'primevue/dropdown';
import * as H from '/@src/utils/appHelper'
useHead({
  title: 'Modul Aplikasi - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setFullWidth(true)
const toast = useToaster();
const item: any = ref({
  statusenabled: true,
  aktif: true
})
const modalObjek = ref(false)
const modalInput = ref(false)
const modalModul = ref(false)
const modalDetail = ref(false)
const expandedKeys: any = ref({});
const selectedKey = ref(null);
const selectedIDModul = ref('')
const selectedHead = ref('')
let activeTab = ref(false)
let dataSource: any = ref([])
let dataSourceSub: any = ref([])
let dataSourceMenu: any = ref([])
let d_ObjectModul: any = ref([])
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
      items.modulaplikasi.match(new RegExp(filters.value, 'i'))
    )
  })
})
const dataSourcefilteredSub = computed(() => {
  if (!filters.value) {
    return dataSourceSub.value
  }

  return dataSourceSub.value.filter((items: any) => {
    return (
      items.modulaplikasi.match(new RegExp(filters.value, 'i'))
    )
  })
})
const expandAll = () => {
  for (let node of dataSourceMenu.value) {
    expandNode(node);
  }

  expandedKeys.value = { ...expandedKeys.value };
};

const collapseAll = () => {
  expandedKeys.value = {};
};
const expandNode = (node: any) => {
  if (node.children && node.children.length) {
    expandedKeys.value[node.key] = true;

    for (let child of node.children) {
      expandNode(child);
    }
  }
};
const selectedModul = async (e: any) => {
  selectedHead.value = e.id
  loadSub()

}
const loadSub = async () => {
  const response = await useApi().get('/sysadmin/master-modul-aplikasi-by-head?id=' + selectedHead.value)
  dataSourceSub.value = response
}
const selectedSub = (e: any) => {
  selectedIDModul.value = e.id
  loadTree()
}
const loadTree = async () => {
  const response = await useApi().get('/sysadmin/master-menu-modul-aplikasi?id=' + selectedIDModul.value)
  dataSourceMenu.value = response.tree
  d_ObjectModul.value = response.data
  expandAll()
}
const onNodeSelect = async (node: any) => {

  d_ObjectModul.value.forEach((element: any) => {
    if (element.key == node.parent_id) {
      item.value.kdobjekmodulaplikasihead = element
    }
  });

  item.value.id = node.key
  item.value.objekmodulaplikasi = node.label
  item.value.fungsi = node.data.fungsi
  item.value.nourut = node.nourut
  item.value.keterangan = node.data.keterangan
  item.value.alamaturlform = node.data.alamaturlform
  modalObjek.value = true
  toast.success(node.label);
}
const tutup = () => {
  clear()
  modalObjek.value = false
}
const saveMenu = async () => {
  if (!item.value.objekmodulaplikasi) {
    H.alert('error', 'Nama Menu harus di isi')
    return
  }
  if (!item.value.nourut) {
    H.alert('error', 'No Urut harus di isi')
    return
  }
  // if (!item.value.alamaturlform) {
  //   H.alert('error', 'URL  harus di isi')
  //   return
  // }

  let json = {
    'id': item.value.id ? item.value.id : '',
    'fungsi': item.value.fungsi ? item.value.fungsi : '',
    'keterangan': item.value.keterangan ? item.value.keterangan : '',
    'objekmodulaplikasi': item.value.objekmodulaplikasi ? item.value.objekmodulaplikasi : null,
    'nourut': item.value.nourut ? item.value.nourut : null,
    'kdobjekmodulaplikasihead': item.value.kdobjekmodulaplikasihead ? item.value.kdobjekmodulaplikasihead.key : null,
    'alamaturlform': item.value.alamaturlform ? item.value.alamaturlform : null,
    'modulaplikasiid': selectedIDModul.value
  }
  isLoading.value = true
  await useApi().post(
    `/sysadmin/save-objek-modul-aplikasi-map`, json).then((response: any) => {
      loadTree()
      clear()
      setNoUrut()
      isLoading.value = false
    }, (error) => {
      isLoading.value = false
    })
}
const deleteMenu = async (item: any) => {
  isLoading.value = true
  await useApi().post(
    `/sysadmin/hapus-objek-modul-aplikasi-map`, { 'id': item.id }).then((response: any) => {
      loadTree()
      clear()
      setNoUrut()
      isLoading.value = false
    }, (error) => {
      isLoading.value = false
    })
}
function add() {
  clear()
  setNoUrut()
  modalObjek.value = true
};
const klikTab = (e: any) => {
  activeTab.value = e.index

}

const changeHead = async (node:any) => {
  setNoUrut()
}
const setNoUrut = async () => {
  if(d_ObjectModul.value.length == 0){
    const response = await useApi().get( '/sysadmin/master-modul-aplikasi-nourut')
    item.value.nourut = response.nourut + 1
  }else{
    item.value.nourut =d_ObjectModul.value.length  ?  d_ObjectModul.value[d_ObjectModul.value.length - 1].nourut + 1: 0
  }


}
const route = useRoute()
async function fetchData() {

  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = offset * limit - limit
  let rows: any = currentPage.value.rows
  let ModulAplikasi = ''
  let ReportDisplay = ''
  let kdModulAplikasi = ''
  let StatusEnabled = ''

  if (item.modulaplikasi) ModulAplikasi = '&modulaplikasi' + item.modulaplikasi
  if (item.reportdisplay) ReportDisplay = '&reportdisplay=' + item.kdreportdisplay
  if (item.kdmodulaplikasi) kdModulAplikasi = '&kdmodulaplikasi=' + item.kdmodulaplikasi
  if (item.value.aktif) StatusEnabled = '&statusenabled=' + item.value.aktif
  isLoading.value = true
  const response = await useApi().get(
    '/sysadmin/master-modul-aplikasi?offset=' + offset +
    '&limit=' + limit +
    '&rows=' + rows +
    kdModulAplikasi + ModulAplikasi + ReportDisplay + StatusEnabled
  )
  isLoading.value = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x];
    element.no = x + 1
  }

  dataSource.value = response.data

}
function loadData() {
  fetchData()
}



const edit = (e: any) => {
  item.value.id = e.id
  item.value.modulaplikasi = e.modulaplikasi
  item.value.reportdisplay = e.reportdisplay
  item.value.statusenabled = e.statusenabled

  modalModul.value = true
}
const tambah = (jenis: any) => {
  clear()
  item.value.reportdisplay = jenis
  modalModul.value = true
}

function detail(e: any) {
  item.value.id = e.id
  item.value.modulaplikasi = e.modulaplikasi
  item.value.reportdisplay = e.reportdisplay
  item.value.statusenabled = e.statusenabled
}

async function simpan() {
  if (!item.value.modulaplikasi) {
    useToaster().error('ModulAplikasi harus di isi')
    return
  }
  var objSave =
  {
    'modulaplikasi': {
      'id': item.value.id ? item.value.id : '',
      'modulaplikasi': item.value.modulaplikasi,
      'reportdisplay': item.value.reportdisplay ? item.value.reportdisplay : null,
      'statusenabled': item.value.statusenabled ? item.value.statusenabled : true,
      'kdmodulaplikasihead': selectedHead.value != '' ? selectedHead.value : null,
    }
  }
  isLoading.value = true
  await useApi().post(
    `/sysadmin/save-modul-aplikasi`, objSave).then((response: any) => {
      isLoading.value = false
      if( item.value.reportdisplay  == 'Menu'){
        loadSub()
      }
      if( item.value.reportdisplay  == 'Modul'){
        fetchData()
      }
      clear()


    }, (error) => {
      isLoading.value = false
      // console.log(error)
    })
}

async function deleterow(e: any) {
  await useApi().post(
    `/sysadmin/delete-modul-aplikasi`, { 'id': e.id }).then((response: any) => {
      loadData()
      loadSub()
    }, (error) => {
    })
}

function clear() {
  item.value = {
    statusenabled: true,
    aktif: true
  }
  modalInput.value = false
}
function changeView(e: any) {
  selectView.value = e
}
function changeSwitch(e: any) {
  loadData()
}
function filter() {
  fetchData()
}
function clearFilter() {
  fetchData()
}
filter()
// loadData ()
fetchData()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/custom/config';

.tile-grid {
  .columns {
    margin-left: -0.5rem !important;
    margin-right: -0.5rem !important;
    margin-top: -0.5rem !important;
  }

  .column {
    padding: 0.5rem !important;
  }
}

.is-dark {
  .tile-grid {
    .tile-grid-item {
      @include vuero-card--dark;
    }
  }
}

.list-view-v3 {
  .list-view-item {
    @include vuero-r-card;

    margin-bottom: 16px;
    padding: 16px;

    .list-view-item-inner {
      display: flex;
      align-items: center;

      >img {
        width: 100%;
        max-width: 60px;
        min-width: 60px;
        max-height: 60px;
        min-height: 60px;
        border-radius: var(--radius-rounded);
        border: 1px solid var(--fade-grey);
      }

      .meta-left {
        margin-left: 16px;

        h3 {
          font-family: var(--font-alt);
          color: var(--dark-text);
          font-weight: 500;
          font-size: 1.1rem;
          line-height: 1;
        }

        >span:not(.tag) {
          font-size: 0.9rem;
          color: var(--light-text);

          svg {
            position: relative;
            top: 1px;
            height: 12px;
            width: 12px;
          }

          .icon-separator {
            position: relative;
            top: -3px;
            font-size: 5px;
            color: var(--light-text);
            padding: 0 8px;
          }

          .iconify {
            margin-right: 0.25rem;
          }
        }
      }

      .meta-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        justify-content: flex-end;

        .buttons {
          margin-bottom: 0;
          margin-right: 10px;
        }
      }
    }
  }
}

.fs-075 {
  font-size: 0.9rem;
}

.is-navbar {
  .form-layout {
    margin-top: 30px;
  }
}

.form-layout {
  // max-width: 740px;
  margin: 0 auto;

  &.is-separate {
    // max-width: 1040px;

    .form-outer {
      background: none;
      border: none;

      .form-body {
        display: flex;

        .form-section {
          flex-grow: 2;
          padding: 10px;
          width: 50%;

          .form-section-inner {
            @include vuero-s-card;

            padding: 40px;

            &.has-padding-bottom {
              padding-bottom: 60px;
              height: 100%;
            }

            >h3 {
              font-family: var(--font-alt);
              font-size: 1.2rem;
              font-weight: 600;
              color: var(--dark-text);
              margin-bottom: 30px;
            }

            .columns {
              .column {
                padding-top: 0.25rem;
                padding-bottom: 0.25rem;
              }
            }

            .radio-boxes {
              display: flex;
              justify-content: space-between;
              margin-left: -8px;
              margin-right: -8px;

              .radio-box {
                position: relative;
                width: calc(50% - 16px);
                margin: 8px;

                &:focus-within {
                  border-radius: 3px;
                  outline-offset: var(--accessibility-focus-outline-offset);
                  outline-width: var(--accessibility-focus-outline-width);
                  outline-style: var(--accessibility-focus-outline-style);
                  outline-color: var(--primary);
                }

                input {
                  position: absolute;
                  top: 0;
                  left: 0;
                  height: 100%;
                  width: 100%;
                  opacity: 0;
                  cursor: pointer;

                  &:checked {
                    +.radio-box-inner {
                      background: var(--primary);
                      border-color: var(--primary);
                      box-shadow: var(--primary-box-shadow);

                      .fee,
                      p {
                        color: var(--smoke-white);
                      }
                    }
                  }
                }

                .radio-box-inner {
                  background: var(--white);
                  border: 1px solid var(--fade-grey-dark-3);
                  text-align: center;
                  border-radius: var(--radius);
                  font-family: var(--font);
                  font-weight: 600;
                  font-size: 0.9rem;
                  transition: color 0.3s, background-color 0.3s, border-color 0.3s,
                    height 0.3s, width 0.3s;
                  padding: 30px 20px;

                  .fee {
                    font-family: var(--font);
                    font-weight: 700;
                    color: var(--dark-text);
                    font-size: 2.4rem;
                    line-height: 1;

                    span {
                      &::after {
                        content: '$';
                        position: relative;
                        top: -10px;
                        font-size: 1.5rem;
                      }
                    }
                  }

                  p {
                    font-family: var(--font-alt);
                  }
                }
              }
            }

            .control {
              >p {
                padding-top: 12px;

                >span {
                  display: block;
                  font-size: 0.9rem;

                  span {
                    font-weight: 500;
                    color: var(--dark-text);
                  }
                }
              }
            }
          }

          .form-section-outer {
            .checkboxes {
              padding: 16px 0;

              .checkbox {
                padding: 0;
                font-size: 0.9rem;
              }
            }

            .button-wrap {
              .button {
                min-height: 60px;
                font-size: 1.05rem;
                font-weight: 600;
                font-family: var(--font-alt);
              }
            }
          }
        }
      }
    }
  }
}

.is-dark {
  .form-layout {
    &.is-separate {
      .form-outer {
        background: none !important;

        .form-body {
          .form-section {
            .form-section-inner {
              @include vuero-card--dark;

              >h3 {
                color: var(--dark-dark-text);
              }

              .radio-boxes {
                .radio-box {
                  input:checked+.radio-box-inner {
                    background: var(--primary);
                    border-color: var(--primary);
                    box-shadow: var(--primary-box-shadow);

                    .fee,
                    p {
                      color: var(--smoke-white);
                    }
                  }

                  .radio-box-inner {
                    background: var(--dark-sidebar-light-2);
                    border-color: var(--dark-sidebar-light-12);

                    .fee {
                      color: var(--dark-dark-text);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}

@media only screen and (max-width: 767px) {
  .form-layout {
    &.is-separate {
      .form-outer {
        .form-body {
          padding-left: 0;
          padding-right: 0;
          flex-direction: column;

          .form-section {
            width: 100%;

            .form-section-inner {
              padding: 30px;
            }
          }
        }
      }
    }
  }
}

@media only screen and (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {
  .form-layout {
    &.is-separate {
      .form-outer {
        .form-body {
          padding-left: 0;
          padding-right: 0;

          // flex-direction: column;

          .form-section {
            // width: 100%;

            .form-section-inner {
              padding: 30px;
            }
          }
        }
      }
    }
  }
}

.all-projects {
  .all-projects-header {
    display: flex;
    padding: 20px;
    background: var(--white);
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: var(--radius-large);
    margin-bottom: 1.5rem;

    .header-item {
      width: 25%;
      border-right: 1px solid var(--fade-grey-dark-3);

      &:last-child {
        border-right: none;
      }

      .item-inner {
        text-align: center;

        .lnil,
        .lnir {
          font-size: 2.2rem;
          margin-bottom: 6px;
          color: var(--primary);
        }

        span {
          display: block;
          font-family: var(--font);
          font-weight: 600;
          font-size: 1.4rem;
          color: var(--dark-text);
        }

        p {
          font-family: var(--font-alt);
        }
      }
    }
  }

  .projects-card-grid {
    .grid-item {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 220px;
      padding: 20px;
      background: var(--white);
      border: 1px solid var(--fade-grey-dark-3);
      border-radius: var(--radius-large);

      .top-section {
        .head {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 8px;

          h3 {
            font-size: 1rem;
            font-family: var(--font-alt);
            color: var(--dark-text);
            font-weight: 600;
          }
        }

        .body {
          p {
            font-family: var(--font);
            color: var(--light-text);
          }
        }
      }

      .bottom-section {
        display: flex;

        .foot-block {
          margin-right: 30px;

          .heading {
            font-family: var(--font-alt);
            font-size: 0.75rem;
            color: var(--light-text-dark-22);
          }

          >p {
            padding-top: 5px;
          }

          .developers {
            display: flex;

            .v-avatar {
              margin-right: 6px;
            }
          }
        }
      }
    }
  }
}

.heading {
  font-family: var(--font-alt);
  font-size: 0.75rem;
  color: var(--light-text-dark-22);
}

.is-dark {
  .all-projects {
    .all-projects-header {
      background: var(--dark-sidebar-light-6);
      border-color: var(--dark-sidebar-light-12);

      .header-item {
        border-color: var(--dark-sidebar-light-18);

        span {
          color: var(--dark-dark-text);
        }

        i {
          color: var(--primary) !important;
        }
      }
    }

    .projects-card-grid {
      .grid-item {
        background: var(--dark-sidebar-light-6);
        border-color: var(--dark-sidebar-light-12);

        .top-section {
          .head {
            h3 {
              color: var(--dark-dark-text);
            }
          }
        }

        .bottom-section {
          .foot-block {
            .heading {
              color: var(--light-text-dark-12);
            }
          }
        }
      }
    }
  }
}
</style>
