<template>
  <ConfirmDialog />
  <VCard radius="rounded">
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Unit </h3> <span> ( {{ totalData }}
        Results)</span>
    </div>

    <div class="columns  all-projects m-3 mt-0">
      <div class="columns is-multiline  projects-card-grid">
        <div class="column is-9">
          <a type="button" class="is-pulled-right mr-3" color="info" outlined raised>
            <RouterLink :to="{ name: 'module-registrasi-mitra-baru' }">
              <i class="fa fa-plus"></i> Unit
              Baru
            </RouterLink>
          </a>
        </div>
        <div class="column is-9">
          <div class="flex-list-inner mb-4" v-if="ds_MITRA.loading">
            <div class="flex-table-item grid-item mb-4" v-for="key in 5" :key="key">
              <VFlexTableCell :column="{ grow: true, media: true }">
                <VPlaceloadAvatar size="medium" />
                <VPlaceloadText :lines="2" width="30%" last-line-width="20%" class="mx-2" />
              </VFlexTableCell>
              <VFlexTableCell>
                <VPlaceload width="100%" height="70px" class="mx-1 mt-2" />
              </VFlexTableCell>
              <VFlexTableCell>
                <VPlaceload width="10%" height="20px" class="mx-1 mt-1" />
                <VPlaceload width="10%" height="20px" class="mx-1 mt-1" />
                <VPlaceload width="10%" height="20px" class="mx-1 mt-1" />
                <VPlaceload width="10%" height="20px" class="mx-1 mt-1" />
              </VFlexTableCell>
              <VFlexTableCell :column="{ align: 'end' }">
                <VPlaceload width="10%" class="mx-1" />
              </VFlexTableCell>
            </div>
          </div>
          <div class="flex-list-inner" v-else-if="ds_MITRA.length === 0">
            <VPlaceholderSection title="Not found" subtitle="There is no data that match your query." class="my-6">
              <template #image>
                <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.svg" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
              </template>
            </VPlaceholderSection>
          </div>
          <div v-else-if="ds_MITRA.length > 0">
            <div class="grid-item mb-4" v-for="(items, i) in ds_MITRA" :key="items.id">
              <div class="top-section">
                <div class="head">
                  <div class="title-wrap">
                    <div class="columns">
                      <div class="column is-3">
                        <VAvatar :picture="items.foto" size="small" v-if="items.isfoto" />
                        <VAvatar size="small" :color="listColor[i]" :initials="items.initials" v-if="!items.isfoto" />
                      </div>
                      <div class="column is-12 mr-3">
                        <div class="is-flex is-align-items-center">
                          <h3>{{ items.namaperusahaan }}</h3>
                        </div>
                      </div>
                    </div>
                  </div>
                  <VDropdown icon="feather:more-vertical" spaced right v-tooltip.bubble="'AKSI'">
                    <template #content>
                      <a role="menuitem" href="#" class="dropdown-item is-media" @click="editMitra(items)">
                        <div class="icon">
                          <i aria-hidden="true" class="lnil lnil-pencil"></i>
                        </div>
                        <div class="meta">
                          <span>Edit</span>
                          <span>ubah data ini</span>
                        </div>
                      </a>
                      <a role="menuitem" href="#" class="dropdown-item is-media" @click="dialogConfirm(items)">
                        <div class="icon">
                          <i aria-hidden="true" class="lnil lnil-trash"></i>
                        </div>
                        <div class="meta">
                          <span>Delete</span>
                          <span>hapus data ini</span>
                        </div>
                      </a>
                      <a role="menuitem" href="#" class="dropdown-item is-media" @click="riwayatMitra(items)">
                        <div class="icon">
                          <i aria-hidden="true" class="lnil lnil-archive"></i>
                        </div>
                        <div class="meta">
                          <span>Riwayat</span>
                          <span>melihat riwayat registrasi</span>
                        </div>
                      </a>
                      <hr class="dropdown-divider" />
                    </template>
                  </VDropdown>
                </div>
                <div class="body">
                  <div class="columns is-multiline">
                    <div class="column">
                      <h4 class="heading">Alamat</h4>
                      <p class="fs-075">{{ items.alamatlengkap }}</p>
                      <p class="fs-075">{{ items.nohp }}</p>
                    </div>
                    <div class="column is-12">
                      <div class="progress-stats" style="margin-top: -10px;">
                        <span class="dark-inverted">Informasi kelengkapan pengisian data</span>
                        <span>{{ items.progress }}%</span>
                      </div>
                      <div class="progress-bar">
                        <VProgress :color="items.class_proggress" size="tiny" :value="items.progress" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="bottom-section is-custom">
                <div class="foot-block" style="margin-top: 10px;">
                  <div class="developers">
                    <VButton type="button" icon="feather:edit" class="is-fullwidth mr-3" color="info" outlined raised
                      @click="editMitra(items)">
                      Edit </VButton>
                    <VButton type="button" icon="feather:trash" class="is-fullwidth mr-3" color="danger" outlined raised
                      @click="dialogConfirm(items)">
                      Delete </VButton>
                    <VButton type="button" icon="fa fa-history" class="is-fullwidth mr-3" color="warning" outlined
                      raised @click="riwayatMitra(items)">
                      Riwayat </VButton>
                    <VButton type="button" icon="feather:arrow-right-circle" class="is-fullwidth mr-3" color="purple"
                      outlined raised @click="registrasi(items)" :loading="items.isLoading">
                      Registrasi Kalibrasi</VButton>
                    <VButton type="button" icon="feather:arrow-right-circle" class="is-fullwidth mr-3" color="warning"
                      outlined raised @click="registrasiReapir(items)" :loading="items.isLoading">
                      Registrasi Repair</VButton>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="dataTable-bottom">
            <div class="dataTable-info">Menampilkan {{ currentPage.page }} ke {{ currentPage.limit }}
              dari
              {{ totalData }} entri data
            </div>
          </div>
          <div class="is-pulled-bottoms">
            <VFlexPagination v-model:current-page="currentPage.page" :item-per-page="currentPage.limit"
              :total-items="totalData" :max-links-displayed="5">
              <template #before-pagination>
              </template>
              <template #before-navigation>
                <VFlex class="mr-4 mt-1" column-gap="1rem">
                  <VField>

                  </VField>
                  <VField>
                    <VControl>
                      <div class="select is-rounded">
                        <select v-model="currentPage.limit">
                          <option :value="1">1 results per page</option>
                          <option :value="5">5 results per page</option>
                          <option :value="10">10 results per page</option>
                          <option :value="15">15 results per page</option>
                          <option :value="25">25 results per page</option>
                          <option :value="50">50 results per page</option>
                        </select>
                      </div>
                    </VControl>
                  </VField>
                </VFlex>
              </template>
            </VFlexPagination>
          </div>
        </div>
        <div class="column is-3">
          <div class="columns is-multiline">
            <div class="column is-12">
              <VField>
                <VControl icon="feather:search">
                  <input v-model="item.qnama" v-on:keyup.enter="filter()" type="text" class="input is-rounded"
                    placeholder="Filter Nama..." />
                </VControl>
              </VField>
            </div>
            <div class="column is-6">
              <h3 class="title is-5 mb-2 mr-1">Filters </h3>
            </div>
            <div class="column is-6">
              <a @click="clearFilter()" type="button" class="is-pulled-right mr-3" color="info" outlined raised> Clear
                All </a>
            </div>
            <div class="column is-12">
              <VDatePicker v-model="item.tgldaftar" color="green" trim-weeks :input="'YYYY-MM-DD'" mode="date">
                <template #default="{ inputValue, inputEvents }">
                  <VField>
                    <VLabel>Tanggal Daftar</VLabel>
                    <VControl icon="feather:calendar">
                      <VInput type="text" placeholder="Select a date" :value="inputValue" v-on="inputEvents"
                        class="is-rounded_Z" />
                    </VControl>
                  </VField>
                </template>
              </VDatePicker>
            </div>
            <div class="column is-12">
              <VField>
                <VLabel>Rows</VLabel>
                <VControl icon="feather:book">
                  <VInput type="text" v-model="currentPage.limit" v-on:keyup.enter="filter()" placeholder="Rows" />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VButton @click="filter()" :loading="ds_MITRA.loading" type="button" icon="feather:search"
                class="is-fullwidth mr-3" color="info" raised> Apply Filters
              </VButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VCard>
</template>
<route lang="yaml">
meta:
  requiresAuth: true
</route>
<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useToaster } from '/@src/composable/toaster'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useThemeColors } from '/@src/composable/useThemeColors'
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { FilterMatchMode, FilterOperator } from "primevue/api";
import { useUserSession } from '/@src/stores/userSession'
useHead({
  title: 'Mitra Lama- ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle('Mitra Lama')
useViewWrapper().setFullWidth(false)
const confirm = useConfirm();
const total = ref(0)
const date = ref(new Date())
const item: any = reactive({})
const totalData: any = ref(0)
let ds_MITRA: any = ref([])
let listColor: any = ref(Object.keys(useThemeColors()))
let dataSelect: any = ref({})
const dataSourceRiwayat: any = ref([])
const route = useRoute()
const router = useRouter()
const { y } = useWindowScroll()
const kelompokUser = useUserSession().getUser().kelompokUser.kelompokUser

const filters = ref({
  global: { value: null, matchMode: FilterMatchMode.CONTAINS },

});
const isStuck = computed(() => {
  return y.value > 30
})
const currentPage: any = ref({
  limit: 5,
  rows: 50
})
for (var i = listColor.value.length - 1; i >= 0; i--) {
  const element = listColor.value[i];
  if (element == 'primary') {
    listColor.value.splice(i, 1);
  }
}
currentPage.value.page = computed(() => {
  try {
    return Number.parseInt(route.query.page as string) || 1
  } catch { }
  return 1
})
watch(
  () => currentPage.value.page,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchMitra()
    }
  }
)
watch(
  () => currentPage.value.limit,
  (newValue, oldValue) => {
    if (newValue != oldValue) {
      fetchMitra()
    }
  }
)


async function fetchMitra() {
  ds_MITRA.value.loading = true

  let searchQuery = `&q=`
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = (parseInt(offset) - 1) * limit
  let page: any = route.query.page ? route.query.page : 1
  let namaperusahaan = ''
  let tgldaftar = ''
  if (item.qnama) namaperusahaan = `&namaperusahaan=${item.qnama}`
  if (item.tgldaftar) tgldaftar = `&tgldaftar=${H.formatDate(item.tgldaftar, 'YYYY-MM-DD')}`
  totalData.value = 0
  const response = await useApi().get(`/registrasi/mitra-lama?page=${page}&offset=${offset}&limit=${limit}&rows=${currentPage.value.rows}${namaperusahaan}${tgldaftar}`)
  let mitra = response.data
  totalData.value = response.total
  for (let x = 0; x < mitra.length; x++) {
    const element = mitra[x];
    let ini = element.namaperusahaan.split(' ')
    let init = element.namaperusahaan.substr(0, 1)
    if (ini.length > 1) {
      init = init + ini[1].substr(0, 1)
    }
    element.initials = init
  }
  ds_MITRA.value.loading = false
  ds_MITRA.value = mitra
}

function editMitra(e: any) {
  router.push({
    name: 'module-registrasi-mitra-baru',
    query: {
      id: e.id,
    },
  })
}

const dialogConfirm = (e: any) => {
  confirm.require({
    message: 'Apakah anda serius menghapus data ini ?',
    header: 'Konfirmasi Hapus Data',
    icon: 'pi pi-info-circle',
    acceptClass: 'p-button-danger',
    accept: () => {
      hapusMitra(e)
    },
    reject: () => { },
  })
}

function hapusMitra(e: any) {
  useApi().post(
    `/registrasi/delete-mitra`, { 'id': e.id }).then((response: any) => {
      fetchMitra()
    }).catch((e: any) => {

    })
}
function riwayatMitra(e: any) {
  console.log(e)
  dataSelect.value = e
  router.push({
    name: 'module-registrasi-riwayat-registrasi',
    query: {
      nomitrafk: e.id,
    },
  })
}

const registrasi = async (e: any) => {
  router.push({
    name: 'module-registrasi-registrasi-mitra',
    query: {
      nomitrafk: e.id,
      statusmitra: "LAMA",
    },
  })
}
const registrasiReapir = async (e: any) => {
  router.push({
    name: 'module-registrasi-registrasi-repair-mitra',
    query: {
      nomitrafk: e.id,
      statusmitra: "LAMA",
    },
  })
}
function clearFilter() {
  delete item.qnama
  fetchMitra()
}
function filter() {
  fetchMitra()
}


fetchMitra()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/custom/listview';

@keyframes blink {
  0% {
    opacity: 1;
  }

  50% {
    opacity: 0;
  }

  100% {
    opacity: 1;
  }
}

.blink {
  animation: blink 2s infinite;
  /* Animasi berkedip setiap 1 detik */
}
</style>
