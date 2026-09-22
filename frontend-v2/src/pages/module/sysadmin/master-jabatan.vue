<template>

  <VTabs slider centered selected="jabatan" :tabs="[
    { label: 'Jabatan', value: 'jabatan' },
    { label: 'Kelompok Jabatan', value: 'kelompok' },
    { label: 'Jenis Jabatan', value: 'jenis' },
  ]">
    <div></div>
    <template #tab="{ activeValue }">
      <p v-if="activeValue === 'jabatan'">
      <div>

        <VCard radius="small">
          <div class="columns column">
            <h3 class="title is-5 mb-2 mr-1">Jabatan</h3>
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
                  currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                  showGridlines >

                  <Column field="no" header="No" :sortable="true"></Column>
                  <Column field="namajabatan" header="Jabatan" :sortable="true"></Column>
                  <Column field="jenisjabatan" header="Jenis Jabatan"></Column>
                  <Column field="kelompokjabatan" header="Kelompok Jabatan"></Column>
                  <Column field="usiapensiun" header="Usia Pensiun"></Column>

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
              <div class="user-grid user-grid-v4" v-else-if="selectView == 'grid'">
                <TransitionGroup 
                name="list" 
                tag="div" 
                class="columns is-multiline is-flex-tablet-p is-half-tablet-p">
                  <!--Grid item-->
                  <div v-for="(item, key) in dataSourcefiltered" :key="key" class="column is-4">
                    <div class="grid-item">
                      <VTag :color="item.status_c" :label="item.status" style="margin-left: 9rem;" rounded />
            <VAvatar size="big" picture="/images/avatars/svg/jobs.png" squarred />
           
            <h3 class="dark-inverted">{{ item.namajabatan }}</h3>
            <p>{{ item.jenisjabatan }}</p>
            
            <div class="button-wrap has-text-centered">
              <VButton color="warning" raised @click="edit(item)" rounded>
                        <span class="icon">
                        <i aria-hidden="true" class="fas fa-edit"></i>
                        </span>
                    <span>Edit Data</span>
                </VButton>
              <div>
                <a class="dark-inverted-hover" @click="deleterow(item)">
                  Hapus Data</a>
              </div>
            </div>
          </div>
                  </div>
                </TransitionGroup>
              </div>
            </div>
            <div class="column is-3">
              <div class="columns is-multiline">
                <img src="/images/avatars/label/jabatan.png" alt="" srcset=""
          style="max-width:75%;margin-left: 5rem; margin-top: -4rem;">
                <div class="column is-6" style="margin-top: -4.5rem;">
                  <h3 class="title is-5 mb-2 mr-1">Filter</h3>
                </div>
                <div class="column is-12" style="margin-top: -1rem;">
                  <VField>
                    <VControl icon="feather:search">
                      <input v-model="filters" class="input custom-text-filter" placeholder="Filter Jabatan..." />
                    </VControl>
                  </VField>
                </div>
                <div class="column is-12">
              <VField class="is-select is-autocomplete-select">
                <VLabel>Jenis Jabatan</VLabel>
                <VControl icon="feather:search">
                  <Multiselect mode="single" v-model="item.jenisjabatan" :options="d_jenisjabatan"
                    placeholder="Pilih data" :searchable="true" />
                </VControl>
              </VField>
            </div>
                <div class="column is-12">
                  <VButton @click="fetchData()" :loading="isLoadingTT" type="button" icon="feather:search"
                    class="is-fullwidth mr-3" color="info" raised>
                    Cari Data
                  </VButton>
                </div>
              </div>
            </div>
          </div>
          <template>
          </template>
          <VModal :open="modalInput" title="Tambah Jabatan" actions="right" @close="modalInput = false">
            <template #content>
              <form class="modal-form">
                <div class="columns is-multiline">
                  <div class="column is-12">
                    <VField label="Nama Jabatan">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.namajabatan" placeholder="Nama Jabatan" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <VField class="is-rounded-select is-autocomplete-select">
                      <VLabel>Jenis Jabatan</VLabel>
                      <VControl icon="feather:search">
                        <Multiselect mode="single" v-model="item.objectjenisjabatanfk" :options="d_jenisjabatan"
                          placeholder="Pilih data" :searchable="true" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <VField class="is-rounded-select is-autocomplete-select">
                      <VLabel>Kelompok Jabatan</VLabel>
                      <VControl icon="feather:search">
                        <Multiselect mode="single" v-model="item.objectkelompokjabatanfk" :options="d_kelompokjabatan"
                          placeholder="Pilih data" :searchable="true" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-4">
                    <VField>
                      <VLabel>Usia Pensiun</VLabel>
                      <VControl icon="feather:bookmark">
                        <VInput type="number" v-model="item.usiapensiun" placeholder="Usia Pensiun" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-4">
                    <VField>
                      <VLabel>Masa Jabatan</VLabel>
                      <VControl icon="feather:bookmark">
                        <VInput type="number" v-model="item.masajabatan" placeholder="Masa Jabatan" class="is-rounded" />
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
              <VButton icon="feather:plus" @click="save()" :loading="isLoadingTT" color="primary" raised>Simpan
              </VButton>
            </template>
          </VModal>
          <VModal :open="modalDetail" title="Detail Jabatan" actions="right" @close="modalDetail = false">
            <template #content>
              <form class="modal-form">
                <div class="columns is-multiline">
                  <div class="column is-12">
                    <VField label="Nama Jabatan">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.namajabatan" placeholder="Nama Jabatan" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <VField class="is-rounded-select is-autocomplete-select">
                      <VLabel>Jenis Jabatan</VLabel>
                      <VControl icon="feather:search">
                        <Multiselect mode="single" v-model="item.objectjenisjabatanfk" :options="d_jenisjabatan"
                          placeholder="Pilih data" :searchable="true" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <VField class="is-rounded-select is-autocomplete-select">
                      <VLabel>Kelompok Jabatan</VLabel>
                      <VControl icon="feather:search">
                        <Multiselect mode="single" v-model="item.objectkelompokjabatanfk" :options="d_kelompokjabatan"
                          placeholder="Pilih data" :searchable="true" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-4">
                    <VField>
                      <VLabel>Usia Pensiun</VLabel>
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.usiapensiun" placeholder="Usia Pensiun" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-4">
                    <VField>
                      <VLabel>Masa Jabatan</VLabel>
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.masajabatan" placeholder="Usia Pensiun" class="is-rounded" />
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
          </VModal>
        </VCard>
      </div>
      </p>
      <p v-else-if="activeValue === 'kelompok'">
        <MasterKelompokJabatan></MasterKelompokJabatan>
      </p>
      <p v-else-if="activeValue === 'jenis'">
        <MasterJenisJabatan></MasterJenisJabatan>
      </p>
    </template>
  </VTabs>

</template>
  
<script  setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useHead } from '@vueuse/head'
import InputSwitch from 'primevue/inputswitch';
import { useToaster } from '/@src/composable/toaster'
import moment from 'moment'
import MasterKelompokJabatan from './master-kelompok-jabatan.vue'
import MasterJenisJabatan from './master-jenis-jabatan.vue'
import { useViewWrapper } from '/@src/stores/viewWrapper'
useHead({
  title: 'Jabatan - ' + import.meta.env.VITE_PROJECT,
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
const selectView: any = ref()
selectView.value = 'list'
const d_jenisjabatan = ref([])
const d_status = [
  { value: 't', label: 'True' },
  { value: 'f', label: 'False' },
]
const d_kelompokjabatan = ref([])
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
      items.namajabatan.match(new RegExp(filters.value, 'i'))
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
  let Jabatan = ''
  let JenisJabatan = ''
  let id = ''
  let StatusEnabled = ''
  if (item.value.namajabatan) Jabatan = '&jabatan' + item.value.namajabatan
  if (item.value.jenisjabatan) JenisJabatan = '&objectjenisjabatanfk=' + item.value.jenisjabatan
  item.value.aktif ? StatusEnabled = '&statusenabled=' + item.value.aktif : StatusEnabled = '&statusenabled=false'

  const response = await useApi().get(
    '/sysadmin/master-jabatan?offset=' + offset +
    '&limit=' + limit +
    '&rows=' + rows +
    id + Jabatan + JenisJabatan + StatusEnabled
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
  d_jenisjabatan.value = []
  useApi().get(
    `/sysadmin/master-jabatan-dropdown`).then((response: any) => {
      d_jenisjabatan.value = response.jenisjabatan.map((e: any) => { return { label: e.jenisjabatan, value: e.id } })
      d_kelompokjabatan.value = response.namakelompokjabatan.map((e: any) => { return { label: e.namakelompokjabatan, value: e.id } })
    })
}

function add() {
  clear()
  modalInput.value = true
}

function edit(e: any) {
  item.id = e.id
  item.value.namajabatan = e.namajabatan
  item.value.objectjenisjabatanfk = e.objectjenisjabatanfk
  item.value.objectkelompokjabatanfk = e.objectkelompokjabatanfk
  item.value.usiapensiun = e.usiapensiun
  item.value.masajabatan = e.masajabatan
  item.value.statusenabled = e.statusenabled
  modalInput.value = true
}
function detail(e: any) {
  item.id = e.id
  item.value.namajabatan = e.namajabatan
  item.value.objectjenisjabatanfk = e.objectjenisjabatanfk
  item.value.objectkelompokjabatanfk = e.objectkelompokjabatanfk
  item.value.usiapensiun = e.usiapensiun
  item.value.masajabatan = e.masajabatan
  item.value.statusenabled = e.statusenabled
  modalDetail.value = true
}

async function save() {

  if (!item.value.namajabatan) {
    useToaster().error('Nama Jabatan harus di isi')
    return
  }
  var objSave =
  {
    'namajabatan': {
      'id': item.value.id ? item.value.id : '',
      'namajabatan': item.value.namajabatan,
      'objectjenisjabatanfk': item.value.objectjenisjabatanfk,
      'objectkelompokjabatanfk': item.value.objectkelompokjabatanfk ? item.value.objectkelompokjabatanfk : null,
      'usiapensiun': item.value.usiapensiun ? item.value.usiapensiun : null,
      'statusenabled': item.value.statusenabled ? item.value.statusenabled : null ,
      'masajabatan': item.value.masajabatan ? item.value.masajabatan : null,
    }
  }
  isLoadingTT.value = true
  await useApi().post(
    `/sysadmin/save-master-jabatan`, objSave).then((response: any) => {
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
    `/sysadmin/delete-master-jabatan`, { 'id': e.id }).then((response: any) => {
      fetchData()
    }, (error) => {
    })
}

function clear() {

item.value.id = ''
item.value.namajabatan = ''
item.value.objectjenisjabatanfk = ''
item.value.objectkelompokjabatanfk = ''
item.value.usiapensiun = ''
item.value.masapensiun = ''

modalInput.value = false
}
function changeView(e: any) {
  selectView.value = e
}

function changeSwitch(e: any) {
  fetchData()
}


loadDropdown()
fetchData()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';

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

.user-grid-v4 {
  .columns {
    margin-left: -0.5rem !important;
    margin-right: -0.5rem !important;
    margin-top: -0.5rem !important;
  }

  .column {
    padding: 0.9 rem !important;
  }

  .grid-item {
    position: relative;
    @include vuero-s-card;

    text-align: center;

    &:hover,
    &:focus {
      .button-wrap {
        > div {
          a {
            opacity: 1;
            pointer-events: all;
          }
        }
      }
    }

    .dropdown {
      position: absolute;
      top: 10px;
      right: 10px;
      text-align: left;
    }

    > .v-avatar {
      display: block;
      margin: 0 auto 4px;
    }

    h3 {
      font-family: var(--font-alt);
      font-size: 1rem;
      font-weight: 600;
      color: var(--dark-text);
    }

    p {
      font-size: 0.80rem;
    }

    .button-wrap {
      margin: 20px 0 0;

      .v-button {
        width: 100%;
        max-width: 140px;
        margin: 0 auto;
      }

      > div {
        margin: 6px 0 0;

        a {
          opacity: 0;
          pointer-events: none;
          color: var(--light-text);
          font-weight: 500;
          font-size: 0.9rem;
          transition: opacity 0.3s, color 0.3s;

          &:hover,
          &:focus {
            color: var(--primary);
          }
        }
      }
    }
  }
}

.is-dark {
  .user-grid-v4 {
    .grid-item {
      @include vuero-card--dark;
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