<template>
  <VTabs slider centered selected="tambah" :tabs="[
    { label: 'Tambah Login User', value: 'tambah' },
    { label: 'Map Login User To Modul Aplikasi', value: 'maploginusertomodulaplikasi' },
    { label: 'Map Login User To Modul Ruangan', value: 'maploginusertoruangan' },
  ]">

    <div></div>
    <template #tab="{ activeValue }">
      <p v-if="activeValue === 'tambah'">
        <VCard radius="small">
          <div class="columns column">
            <h3 class="title is-5 mb-2 mr-1">Login User</h3>
          </div>
          <div class="columns is-multiline">
            <div class="column is-9">
              <div class="user-grid-toolbar">

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
                <DataTable :value="dataSource" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
                  paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                  responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                  currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">

                  <Column field="id" header="Kode"></Column>
                  <Column field="namauser" header="Nama User" :sortable="true"></Column>
                  <Column field="namalengkap" header="Nama Pegawai"></Column>
                  <Column field="kelompokuser" header="Kelompok User"></Column>
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

              <div class="user-grid user-grid-v2" v-else-if="selectView == 'grid'">
                <TransitionGroup name="list" tag="div" class="columns is-multiline">
                  <!--Grid item-->
                  <div v-for="item in dataSource" :key="item.id" class="column is-4">
                    <div class="grid-item-wrap">
                      <div class="grid-item-head">
                        <div class="flex-head">

                          <div class="meta">

                            <span>{{ item.kelompokuser }} </span>

                          </div>
                          <VTag :color="item.statusenabled ? 'primary' : 'danger'"
                            :label="item.statusenabled ? 'Aktif' : 'Non Aktif'" rounded elevated />
                        </div>
                      </div>
                      <div class="grid-item">
                        <VAvatar size="big" picture="/images/avatars/svg/loginuser.svg" color="primary" squared
                          bordered />
                        <h3 class="dark-inverted">{{ item.namauser }}</h3>
                        <div class="people">
                          <VSnack :title="(item.namalengkap)" color="info"
                            :icon="(item.norec_pd != null ? 'fa:user-md' : 'feather:user')">

                          </VSnack>
                        </div>
                        <div class="buttons">
                          <button class="button v-button is-dark-outlined" @click="edit(item)">
                            <span class="icon">
                              <i aria-hidden="true" class="iconify" data-icon="feather:edit"></i>
                            </span>
                            <span>Edit</span>
                          </button>
                          <button class="button v-button is-dark-outlined" @click="deleterow(item)">
                            <span class="icon">
                              <i aria-hidden="true" class="iconify" data-icon="feather:trash"></i>
                            </span>
                            <span>Hapus</span>
                          </button>
                        </div>
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
                  <VField label="Nama Pegawai">
                    <VControl icon="feather:search">
                      <input v-model="item.namapegawai" class="input" placeholder="Cari Nama Pegawai ..."  v-on:keyup.enter="filter()" />
                    </VControl>
                  </VField>
                </div>
                <div class="column is-12">
                  <VField class="is-autocomplete-select">
                    <VLabel>Kelompok User</VLabel>
                    <VControl icon="feather:search">
                      <Multiselect mode="single" v-model="item.kelompokuser" :options="d_kelompokFilter"
                        placeholder="Pilih Kelompok User" :searchable="true" @select="filter()" />
                    </VControl>
                  </VField>
                </div>
                <div class="columns is-multiline p-2">
                  <div class="column is-8">
                    <VField class="h-hidden-mobile">
                      <VControl>
                        <VSwitchBlock v-model="item.aktif" label="User Aktif" color="danger"
                          @change="changeSwitch(item.aktif)" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-4">
                    <VField label="Rows">
                    <VControl>
                      <input v-model="item.rows" class="input"/>
                    </VControl>
                  </VField>
                  </div>
                </div>

                <div class="column is-12">
                  <VButton @click="filter()" :loading="isLoadingTT" type="button" icon="feather:search"
                    class="is-fullwidth mr-3" color="info" raised>
                    Apply Filters
                  </VButton>
                </div>
              </div>
            </div>
          </div>
          <Dialog v-model:visible="modalInput" modal :header="(item.id != undefined ? 'Ubah' : 'Simpan') + ' Login User'"
            :style="{ width: '30vw' }">
            <!-- <VModal :open="modalInput" title="Tambah Login User" actions="right" @close="modalInput = false"> -->
            <!-- <template #content>
              <form class="modal-form"> -->
            <div class="columns is-multiline">
              <div class="column is-12">
                <VField label="Nama User">
                  <VControl icon="fas fa-user">
                    <VInput type="text" v-model="item.namauser" placeholder="Nama Login User" class="is-rounded" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-6">
                <VField label="Kata Sandi">
                  <VControl icon="fas fa-address-card">
                    <VInput type="password" v-model="item.katasandi" placeholder="Kata Sandi" class="is-rounded" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-6">
                <VField label="Ulangi Kata Sandi">
                  <VControl icon="fas fa-address-card">
                    <VInput type="password" v-model="item.katasandi2" placeholder="Ulangi Kata Sandi"
                      class="is-rounded" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-12">
                <VField label="Pegawai " class="is-rounded-select is-autocomplete-select" v-slot="{ id }">
                  <VControl icon="fas fa-user" fullwidth class="prime-auto-select">
                    <Dropdown v-model="item.objectpegawaifk" :options="d_pegawai" :optionLabel="'namalengkap'"
                      class="is-rounded" placeholder="Pegawai " style="width: 100%;" :filter="true" showClear />
                  </VControl>
                </VField>

                <!-- <VField class="is-rounded-select is-autocomplete-select">
                      <VLabel>Nama Pegawai</VLabel>
                      <VControl icon="feather:search">
                        <Multiselect mode="single" v-model="item.objectpegawaifk" :options="d_pegawai"
                          placeholder="Pilih data" :searchable="true" />
                      </VControl>
                    </VField> -->
              </div>
              <div class="column is-12">
                <VField label="Kelompok User " class="is-rounded-select is-autocomplete-select" v-slot="{ id }">
                  <VControl icon="fas fa-users" fullwidth class="prime-auto-select">
                    <Dropdown v-model="item.objectkelompokuserfk" :options="d_kelompok" :optionLabel="'kelompokuser'"
                      class="is-rounded" placeholder="Kelompok User  " style="width: 100%;" :filter="true" showClear />
                  </VControl>
                </VField>
                <!-- <VField class="is-rounded-select is-autocomplete-select">
                      <VLabel>Kelompok User</VLabel>
                      <VControl icon="feather:search">
                        <Multiselect mode="single" v-model="item.objectkelompokuserfk" :options="d_kelompok"
                          placeholder="Pilih data" :searchable="true" />
                      </VControl>
                    </VField> -->
              </div>
              <div class="column is-12">
                <VField label="Status User">
                  <div class="columns is-multiline">
                    <div class="column is-4" v-for="items in statusUser">
                      <VRadio v-model="item.statusUser" :value="items.value" class="p-0 mb-3" :label="items.label"
                        name="{{items.value}}" square color="primary" />
                    </div>
                  </div>
                </VField>
              </div>
            </div>
            <template #footer>
              <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="modalInput = false">
                Tutup
              </VButton>
              <VButton type="button" rounded outlined color="primary" raised icon="feather:save" @click="save()"
                :loading="isLoadingTT"> {{ item.id != undefined ? 'Ubah' : 'Simpan' }}
              </VButton>
            </template>
            <!-- </form>
            </template>
            <template #action>
              <VButton icon="feather:plus" @click="save()" :loading="isLoadingTT" color="primary" raised>Simpan
              </VButton>
            </template>
          </VModal> -->
          </Dialog>
          <VModal :open="modalDetail" title="Detail Login User" actions="right" @close="modalDetail = false">
            <template #content>
              <form class="modal-form">
                <div class="columns is-multiline">
                  <div class="column is-12">
                    <VField label="Nama User">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.namauser" placeholder="Nama Login User" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-6">
                    <VField label="Kata Sandi">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.katasandi" placeholder="Kata Sandi" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-6">
                    <VField label="Ulangi Kata Sandi">
                      <VControl icon="feather:bookmark">
                        <VInput type="text" v-model="item.katasandi2" placeholder="Ulangi Kata Sandi"
                          class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <VField class="is-rounded-select is-autocomplete-select">
                      <VLabel>Nama Pegawai</VLabel>
                      <VControl icon="feather:search">
                        <Multiselect mode="single" v-model="item.objectpegawaifk" :options="d_pegawai"
                          placeholder="Pilih data" :searchable="true" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-12">
                    <VField class="is-rounded-select is-autocomplete-select">
                      <VLabel>Kelompok User</VLabel>
                      <VControl icon="feather:search">
                        <Multiselect mode="single" v-model="item.objectkelompokuserfk" :options="d_kelompok"
                          placeholder="Pilih data" :searchable="true" />
                      </VControl>
                    </VField>
                  </div>
                </div>
              </form>
            </template>
          </VModal>
        </VCard>
      </p>
      <p v-else-if="activeValue === 'maploginusertomodulaplikasi'">
        <MasterLoginUserToModulAplikasi></MasterLoginUserToModulAplikasi>
      </p>
      <p v-else-if="activeValue === 'maploginusertoruangan'">
        <MasterLoginUserToRuangan></MasterLoginUserToRuangan>
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
import MasterLoginUserToModulAplikasi from './mapping-loginuser-to-modulaplikasi.vue'
import MasterLoginUserToRuangan from './mapping-loginuser-to-ruangan.vue'
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
useHead({
  title: 'Tambah Login User - ' + import.meta.env.VITE_PROJECT,
})
const item: any = ref({
  aktif: true,
})
const modalInput = ref(false)
const modalDetail = ref(false)
let statusUser = ref([
  {
    label: "Active",
    value: true
  },
  {
    label: "Non Aktif",
    value: false
  }
])

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
selectView.value = 'grid'
const d_pegawai = ref([])
const d_kelompokFilter = ref([])
const d_kelompok = ref([])
let isLoading: any = ref(false)
let isLoadingTT: any = ref(false)

const currentPage: any = ref({
  limit: 5,
  rows: 50,
})
const filters = ref('')

const route = useRoute()
isLoading.value = false

function changeSwitch(e: any) {
  fetchData();
}

async function fetchData() {

  isLoading = true

  let namapegawai =  item.value.namapegawai ? `&namapegawai=${item.value.namapegawai}` : ''
  let KelompokUser = item.value.kelompokuser ? '&objectkelompokuserfk=' + item.value.kelompokuser : ''
  let rows = item.value.rows ? `&rows=${item.value.rows}` : `rows=20`
  let qAktif = item.value.aktif == undefined ? false : item.value.aktif
  let Statusenabled = '&statusenabled=' + qAktif

  const response = await useApi().get('/sysadmin/master-tambah-login-user?' + rows + KelompokUser + Statusenabled + namapegawai)
  isLoading = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x];
    element.no = x + 1
  }

  dataSource.value = response.data
  //   dataSource.value.total = response.length
}

function loadDropdown() {
  d_pegawai.value = []
  useApi().get(
    `/sysadmin/master-tambah-login-user-dropdown`).then((response: any) => {
      d_pegawai.value = response.namalengkap;//.map((e: any) => { return { label: e.namalengkap, value: e.id } })
      d_kelompok.value = response.kelompokuser;//.map((e: any) => { return { label: e.kelompokuser, value: e.id } })
      d_kelompokFilter.value = response.kelompokuser.map((e: any) => {
        return { label: e.kelompokuser, value: e.id, default: e }
      })
    })
}

function add() {
  clear()
  modalInput.value = true
}

function edit(e: any) {
  item.value.id = e.id
  item.value.namauser = e.namauser
  item.value.katasandi = e.katasandi
  item.value.statusUser = e.statusenabled
  d_kelompok.value.forEach((elem: any) => {
    if (elem.id == e.objectkelompokuserfk) {
      item.value.objectkelompokuserfk = elem
      return
    }
  });
  d_pegawai.value.forEach((elem: any) => {
    if (elem.id == e.objectpegawaifk) {
      item.value.objectpegawaifk = elem
      return
    }
  });


  modalInput.value = true
}
function detail(e: any) {
  item.value.id = e.id
  item.value.namauser = e.namauser
  item.value.katasandi = e.katasandi
  item.value.objectpegawaifk = e.objectpegawaifk
  item.value.objectkelompokuserfk = e.objectkelompokuserfk
  modalDetail.value = true
}

async function save() {

  if (!item.value.namauser) {
    useToaster().error('Nama User harus di isi')
    return
  }
  if (!item.value.objectpegawaifk) {
    useToaster().error('Nama Pegawai harus di isi')
    return
  }
  if (!item.value.katasandi) {
    useToaster().error('Kata Sandi harus di isi')
    return
  }
  if (item.value.katasandi != item.value.katasandi2) {
    useToaster().error('Kata Sandi tidak sama')
    return
  }
  var objSave =
  {
    'loginuser': {
      'id': item.value.id ? item.value.id : '',
      'namauser': item.value.namauser,
      'objectpegawaifk': item.value.objectpegawaifk.id,
      'objectkelompokuserfk': item.value.objectkelompokuserfk.id,
      'katasandi': item.value.katasandi,
      'statusenabled': item.value.statusUser
    }
  }
  isLoadingTT.value = true
  await useApi().post(
    `/sysadmin/save-login-user`, objSave).then((response: any) => {
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
    `/sysadmin/delete-login-user`, { 'id': e.id }).then((response: any) => {
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
  delete item.value.namapegawai
  delete item.value.kelompokuser
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

.user-grid-v2 {
  .columns {
    margin-left: -0.5rem !important;
    margin-right: -0.5rem !important;
    margin-top: -0.5rem !important;
  }

  .column {
    padding: 0.5rem !important;
  }

  .grid-item {
    @include vuero-s-card;

    text-align: center;

    >.v-avatar {
      display: block;
      margin: 0 auto 4px;
    }

    h3 {
      font-family: var(--font-alt);
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--dark-text);
    }

    p {
      font-size: 0.85rem;
    }

    .people {
      display: flex;
      justify-content: center;
      padding: 8px 0 30px;

      .v-avatar {
        margin: 0 4px;
      }
    }

    .buttons {
      display: flex;
      justify-content: space-between;

      .button {
        width: calc(50% - 4px);
        color: var(--light-text);

        &:hover,
        &:focus {
          border-color: var(--fade-grey-dark-4);
          color: var(--primary);
          box-shadow: var(--light-box-shadow);
        }
      }
    }
  }

  .grid-item-wrap {
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: var(--radius-large);
    transition: all 0.3s; // transition-all test

    .grid-item-head {
      background: #fafafa;
      border-radius: var(--radius-large) 6px 0 0;
      padding: 20px;

      .flex-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;

        .meta {
          span {
            display: flex;

            &:first-child {
              font-family: var(--font-alt);
              font-weight: 600;
              font-size: 0.95rem;
              color: var(--dark-text);
            }

            &:nth-child(2) {
              font-size: 0.9rem;
              color: var(--light-text);
            }
          }
        }

        .status-icon {
          height: 28px;
          width: 28px;
          min-width: 28px;
          border-radius: var(--radius-rounded);
          border: 1px solid var(--fade-grey-dark-3);
          display: flex;
          align-items: center;
          justify-content: center;

          &.is-success {
            background: var(--success);
            border-color: var(--success);
            color: var(--white);
          }

          &.is-warning {
            background: var(--orange);
            border-color: var(--orange);
            color: var(--white);
          }

          &.is-danger {
            background: var(--danger);
            border-color: var(--danger);
            color: var(--white);
          }

          i {
            font-size: 8px;
          }
        }
      }

      .buttons {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0;

        .button,
        .v-button {
          width: calc(50% - 4px);
          color: var(--light-text);
          margin-bottom: 0;

          &:hover,
          &:focus {
            border-color: var(--fade-grey-dark-4);
            color: var(--primary);
            box-shadow: var(--light-box-shadow);
          }
        }
      }
    }

    .grid-item {
      border-top-left-radius: 0;
      border-top-right-radius: 0;
      border: none;
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
