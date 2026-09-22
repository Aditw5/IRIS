<template>
    <VCard>
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">List Master</h3>
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
          <DataTable :value="dataSourcefiltered" class="p-datatable-sm" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">
  
            <Column field="no" header="No"></Column>
            <Column field="label" header="Nama Master" :sortable="true"></Column>
            <Column field="tablename" header="Nama Tabel"></Column>
            <Column field="link" header="Alamat"></Column>
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
            <div v-for="(item, key) in dataSourcefiltered" :key="key" class="column is-12">
              <div class="tile-grid-item">
                <div class="tile-grid-item-inner">
                  <VAvatar size="medium" picture="/images/avatars/svg/list.png" color="primary" squared bordered />
                  <div class="meta">
                    <span class="dark-inverted">{{ item.label }}</span>
                    <span style="margin-top: 0.3rem; margin-bottom: 0.3rem;">Tabel : {{ item.tablename }}</span>
                    <span>{{ item.link }}</span>
                   
                  </div>
                  <VTag :color="item.status_c" :label="item.status" style="margin-left:15rem" />
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
          <img src="/images/avatars/label/list.png" alt="" srcset=""
            style="max-width:50%; margin-top: -5rem; margin-left: 12rem;">
          <VCard style="margin-top: -10rem;">
            <div class="columns is-multiline">
              <div class="column is-6">
                <h3 class="title is-6 mb-2 mr-1">
                  <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                  Tambah Data
                </h3>
              </div>
              <div class="column is-12">
                <VField label="Nama Master">
                  <VControl icon="feather:bookmark">
                    <VInput type="text" v-model="item.label" placeholder="Nama Master" class="is-rounded" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-12">
                <VField label="Nama Tabel">
                  <VControl icon="feather:bookmark">
                    <VInput type="text" v-model="item.tablename" placeholder="Nama Tabel" class="is-rounded" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-12">
                <VField label="Alamat">
                  <VControl icon="feather:website">
                    <VInput type="text" v-model="item.link" placeholder="Alamat" class="is-rounded" />
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
  import { useHead } from '@vueuse/head'
  import { useToaster } from '/@src/composable/toaster'
  import moment from 'moment'
  useHead({
    title: 'List Master - ' + import.meta.env.VITE_PROJECT,
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
        items.label.match(new RegExp(filters.value, 'i'))
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
    let Label = ''
    let Tablename = ''
    let Link = ''
    let StatusEnabled = '' 
  
    if (item.label) Label = '&label' + item.label
    if (item.tablename) Tablename = '&tabelname=' + item.tabelname
    if (item.link) Link = '&link' + item.link
    if (item.value.aktif) StatusEnabled = '&statusenabled=' + item.value.aktif
  
    const response = await useApi().get(
      '/sysadmin/master-list?offset=' + offset +
      '&limit=' + limit +
      '&rows=' + rows +
      StatusEnabled + Label + Link + Tablename 
    )
    isLoading = false
    for (let x = 0; x < response.data.length; x++) {
      const element = response.data[x];
      element.no = x + 1
    }
    dataSource.value = response.data
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
    item.value.label = e.label
    item.value.tablename = e.tablename
    item.value.link = e.link
    item.value.statusenabled = e.statusenabled
  }
  function detail(e: any) {
    item.value.id = e.id
    item.value.label = e.label
    item.value.tablename = e.tablename
    item.value.link = e.link
    item.value.statusenabled = e.statusenabled
  }
  
  async function save() {
    if (!item.value.label) {
      useToaster().error('Nama Master harus di isi')
      return
    }
    if (!item.value.tablename) {
      useToaster().error('Tabel Master harus di isi')
      return
    }
    if (!item.value.link) {
      useToaster().error('Link Master harus di isi')
      return
    }
    var objSave =
    {
      'listmaster': {
        'id': item.value.id ? item.value.id : '',
        'label': item.value.label,
        'link': item.value.link,
        'tablename': item.value.tablename,
        'statusenabled': item.value.statusenabled ? item.value.statusenabled : null,
      }
    }
    isLoadingTT.value = true
    await useApi().post(
      `/sysadmin/save-master-list`, objSave).then((response: any) => {
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
      `/sysadmin/delete-master-list`, { 'id': e.id }).then((response: any) => {
        fetchData ()
      }, (error) => {
      })
  }
  
  function clear() {
    item.value.id = ''
    item.value.label = ''
  }
  function changeView(e: any) {
    selectView.value = e
  }
  function changeSwitch(e: any) {
    fetchData()
  }
  function filter() {
    fetchData()
  }
  function clearFilter() {
    fetchData()
  }
  
  // loadData()
  fetchData()
  </script>
  <style lang="scss">
  @import '/@src/scss/abstracts/all';
  
  @import '/@src/scss/components/forms-outer';
  @import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';

  </style>