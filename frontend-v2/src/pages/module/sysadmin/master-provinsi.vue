<template>
  <VTabs slider centered selected="provinsi" :tabs="[
    { label: 'Provinsi', value: 'provinsi' },
    { label: 'Kota Kabupaten', value: 'kabupaten' },
    { label: 'Kecamatan', value: 'kecamatan' },
  ]">
    <div></div>
    <template #tab="{ activeValue }">
      <p v-if="activeValue === 'provinsi'">
      <div>
        <VCard radius="small">
          <div class="columns column">
            <h3 class="title is-5 mb-2 mr-1">Data Provinsi</h3>
          </div>

          <div class="columns is-multiline">
            <div class="column is-12">
              <div class="user-grid-toolbar">
                <VControl icon="feather:search">
                  <input v-model="filters" class="input custom-text-filter" placeholder="Cari Provinsi " />
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
                  currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

                  <Column field="no" header="Kode"></Column>
                  <Column field="namapropinsi" header="Provinsi" :sortable="true"></Column>
                  <Column field="kodebpjs" header="Kode BPJS" :sortable="true"></Column>
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
                  <div v-for="(item, key) in dataSourcefiltered" :key="key" class="column is-3">
                    <div class="tile-grid-item">
                      <div class="tile-grid-item-inner">
                        <VAvatar size="medium" picture="/images/avatars/svg/propinsi.svg" color="primary" squared
                          bordered />
                        <div class="meta">
                          <span class="dark-inverted">{{ item.namapropinsi }}</span>
                          <span>Kode BPJS : {{ item.kodebpjs }}</span>
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
            <!--
            <div class="column is-4">
              <VCard>
                <div class="columns is-multiline">
                  <div class="column is-6">
                    <h3 class="title is-6 mb-2 mr-1"> + Tambah Data</h3>
                  </div>
                  <div class="column is-12">
                    <VField label="Provinsi">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.namapropinsi" placeholder="Provinsi" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <VField class="is-autocomplete-select">
                      <VLabel>Kode BPJS</VLabel>
                      <VControl icon="feather:edit">
                        <VInput type="text" v-model="item.kodebpjs" placeholder="Provinsi" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <VField class="is-autocomplete-select">
                      <VLabel>Nama Eksternal</VLabel>
                      <VControl icon="feather:edit">
                        <VInput type="text" v-model="item.namaexternal" placeholder="Nama Eksternal"
                          class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <VField class="is-autocomplete-select">
                      <VLabel>Report Display</VLabel>
                      <VControl icon="feather:edit">
                        <VInput type="text" v-model="item.reportdisplay" placeholder="Report Display"
                          class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <VButton @click="save()" :loading="isLoadingTT" type="button" icon="feather:save"
                      class="is-fullwidth mr-3" color="success" raised>
                      Simpan Data
                    </VButton>
                  </div>
                </div>
              </VCard>
            </div>-->
          </div>
          <VModal :open="modalInput" title="Tambah Provinsi" actions="right" @close="modalInput = false">
            <template #content>
              <form class="modal-form">
                <div class="columns is-multiline">
                  <div class="column is-12">
                    <VField label="Provinsi">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.namapropinsi" placeholder="Nama Propinsi"
                          class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-6">
                    <VField label="Kode BPJS">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.kodebpjs" placeholder="Kode BPJS" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-6">
                    <VField label="Report Display">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.reportdisplay" placeholder="Report Display"
                          class="is-rounded" />
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
          <VModal :open="modalDetail" title="Detail Provinsi" actions="right" @close="modalDetail = false">
            <template #content>
              <form class="modal-form">
                <div class="columns is-multiline">
                  <div class="column is-6">
                    <VField label="Provinsi">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.namapropinsi" placeholder="Provinsi" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-6">
                    <VField label="Kode BPJS">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.kodebpjs" placeholder="Kode BPJS" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-6">
                    <VField label="Report Display">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.reportdisplay" placeholder="Report Display"
                          class="is-rounded" />
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
      <p v-else-if="activeValue === 'kabupaten'">
        <MasterKotaKabupaten></MasterKotaKabupaten>
      </p>
      <p v-else-if="activeValue === 'kecamatan'">
        <MasterKecamatan></MasterKecamatan>
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
import { useToaster } from '/@src/composable/toaster'
import MasterKotaKabupaten from './master-kota-kabupaten.vue'
import MasterKecamatan from './master-kecamatan.vue'
import moment from 'moment'
import { useViewWrapper } from '/@src/stores/viewWrapper'
useHead({
  title: 'Provinsi - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
const item: any = ref({
  aktif: true
})
const modalInput = ref(false)
const modalDetail = ref(false)
const d_KodeBpjs = ref([])

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
let ID_StatusPerkawinan = useRoute().query.id as string
let ID_StatusPerkawinan_SET = ref()

const currentPage: any = ref({
  limit: 5,
  rows: 50,
})

const dataSourcefiltered = computed(() => {
  if (!item.value.qnama) {
    return dataSource.value
  }

  return dataSource.value.filter((items: any) => {
    return (
      items.namapropinsi.match(new RegExp(item.value.qnama, 'i'))
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
  let Provinsi = ''
  let KodeBpjs = ''
  

  if (item.namapropinsi) Provinsi = '&namapropinsi' + item.statusperkawinan
  if (item.kodebpjs) KodeBpjs = '&kodebpjs=' + item.kodebpjs

  const response = await useApi().get(
    '/sysadmin/master-provinsi?offset=' + offset +
    '&limit=' + limit +
    '&rows=' + rows +
    Provinsi + KodeBpjs
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
  clear()
  item.value.id = e.id
  item.value.namapropinsi = e.namapropinsi
  item.value.kodebpjs = e.kodebpjs
  item.value.reportdisplay = e.reportdisplay
  modalInput.value = true
}
function detail(e: any) {
  clear()
  item.value.id = e.id
  item.value.namapropinsi = e.namapropinsi
  item.value.kodebpjs = e.kodebpjs
  item.value.reportdisplay = e.reportdisplay
  modalDetail.value = true
}

async function save() {
  if (!item.value.namapropinsi) {
    useToaster().error('Provinsi harus di isi')
    return
  }
  var objSave =
  {
    'namapropinsi': {
      'id': item.value.id ? item.value.id : '',
      'namapropinsi': item.value.namapropinsi,
      'kodebpjs': item.value.kodebpjs,
      'reportdisplay': item.value.reportdisplay ? item.value.reportdisplay : null,

    }
  }
  isLoadingTT.value = true
  await useApi().post(
    `/sysadmin/save-provinsi`, objSave).then((response: any) => {
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
    `/sysadmin/delete-provinsi`, { 'id': e.id }).then((response: any) => {
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

// loadData()
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

.tile-grid-v1 {
  .tile-grid-item {
    @include vuero-s-card;

    border-radius: 14px;
    padding: 16px;

    .tile-grid-item-inner {
      display: flex;
      align-items: center;

      .meta {
        margin-left: 10px;
        line-height: 1.2;

        span {
          display: block;
          font-family: var(--font);

          &:first-child {
            color: var(--dark-text);
            font-family: var(--font-alt);
            font-weight: 600;
            font-size: 1rem;
          }

          &:nth-child(2) {
            color: var(--light-text);
            font-size: 0.9rem;
          }
        }
      }

      .dropdown {
        position: relative;
        margin-left: auto;
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