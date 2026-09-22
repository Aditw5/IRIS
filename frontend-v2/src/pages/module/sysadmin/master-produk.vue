<template>
  <VCard>
    <TabView v-model:activeIndex="activeValue">
      <TabPanel header="Alat">
        <div class="columns is-multiline  projects-card-grid">
          <div class="column is-3">
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
          </div>
          <div class="column is-6">
            <a type="button" class="is-pulled-right" color="info" outlined raised>
              <VButton color="primary" RouterLink :to="{ name: 'module-sysadmin-produk-baru' }">
                <i class="fa fa-plus"></i> Produk Baru
              </VButton>
            </a>
          </div>
        </div>

        <div class="columns">
          <div class="column is-9" v-if="selectView == 'grid'">
            <div class="page-placeholder" v-if="dataSource.length == 0">
              <div class="placeholder-content">
                <img class="light-image" style=" max-width: 340px;" :src="H.assets().iconNotFound_rev" alt="" />
                <img class="dark-image" style=" max-width: 340px;" :src="H.assets().iconNotFound_rev" alt="" />
                <h3>{{ H.assets().notFound }}</h3>
                <p class="is-larger">
                  {{ H.assets().notFoundSubtitle }}
                </p>
              </div>
            </div>
            <div class="tile-grid tile-grid-v1">
              <TransitionGroup name="list" tag="div" class="columns is-multiline">
                <div v-for="(item, key) in dataSource" :key="key" class="column is-4">
                  <div class="tile-grid-item">
                    <div class="tile-grid-item-inner">
                      <img :src="toolThumbnailUrl(item)" :data-main-fallback="toolMainImageUrl(item)"
                        data-fallback="/images/other/no_image.jpg"
                        style="width: 300px; height: auto; max-height: 200px; object-fit: contain; border-radius: 8px; background: #f3f3f3;"
                        alt="Produk" @error="(event) => toolImageErrored(event, '300x200')" />
                      <!-- <VAvatar size="small" picture="/images/simrs/produk-ico.png" color="primary" squared bordered /> -->
                      <div class="meta">
                        <span class="dark-inverted"><b>{{ item.namaproduk }}</b></span>
                      </div>
                      <div class="meta">
                        <span><b>Alat Milik :</b> {{ item.namaperusahaan }}</span>
                      </div>
                      <div class="meta">
                        <span><b>Merk/Tipe :</b> {{ item.namamerk }}/{{ item.namatipe }}</span>
                      </div>
                      <div class="meta">
                        <span><b>S/N :</b> {{ item.namaserialnumber }}</span>
                      </div>
                      <VDropdown icon="feather:more-vertical" spaced left>
                        <template #content>
                          <a role="menuitem" class="dropdown-item is-media" @click="riwayatAlat(item)">
                            <div class="icon">
                              <i class="iconify" data-icon="feather:clock" aria-hidden="true"></i>
                            </div>
                            <div class="meta">
                              <span>Riwayat Alat</span>
                              <span>Untuk melihat Riwayat Alat </span>
                            </div>
                          </a>
                          <a role="menuitem" class="dropdown-item is-media" @click="edit(item)">
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
                          <a role="menuitem" class="dropdown-item is-media" @click="hapus(item)">
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
              <div class="dataTable-bottom">
                <div class="dataTable-info">Menampilkan {{ dataSource.length }} ke {{ currentPage.limit }} dari
                  {{ currentPage.total }} entri data
                </div>
              </div>
            </div>
          </div>
          <div class="column is-9" v-else-if="selectView == 'list'">
            <DataTable :value="dataSource" class="p-datatable-sm" :paginator="true" :rows="10"
              :rowsPerPageOptions="[5, 10, 25]"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}">


              <Column field="no" header="#"></Column>
              <Column field="namaproduk" header="Alat" :sortable="true"></Column>
              <Column field="namaperusahaan" header="ALat Milik" :sortable="true"></Column>
              <Column field="namamerk" header="Merk" :sortable="true"></Column>
              <Column field="namatipe" header="Tipe" :sortable="true"></Column>
              <Column field="namaserialnumber" header="S/N" :sortable="true"></Column>
              <Column :exportable="false" header="Action" style="text-align: center;">
                <template #body="slotProps">
                  <VDropdown icon="feather:more-vertical" spaced right>
                    <template #content>
                      <a role="menuitem" class="dropdown-item is-media" @click="riwayatAlat(slotProps.data)">
                        <div class="icon">
                          <i class="iconify" data-icon="feather:clock" aria-hidden="true"></i>
                        </div>
                        <div class="meta">
                          <span>Riwayat Alat</span>
                          <span>Untuk melihat Riwayat Alat </span>
                        </div>
                      </a>
                      <a role="menuitem" class="dropdown-item is-media" @click="edit(slotProps.data)">
                        <div class="icon">
                          <i class="iconify" data-icon="feather:edit" aria-hidden="true"></i>
                        </div>
                        <div class="meta">
                          <span>Edit</span>
                          <span>Untuk merubah data </span>
                        </div>
                      </a>
                      <a role="menuitem" class="dropdown-item is-media" @click="hapus(slotProps.data)">
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
                </template>
              </Column>
            </DataTable>
          </div>
          <div class="column is-3">
            <div class="columns is-multiline">
              <div class="column is-6">
                <h3 class="title is-5 mb-2 mr-1">Filter</h3>
              </div>
              <div class="column is-6">
                <a @click="clearFilter()" type="button" class="is-pulled-right mr-3" color="info" outlined raised>
                  Clear
                </a>
              </div>
              <div class="column is-12">
                <VField label="Search">
                  <VControl icon="feather:search">
                    <input v-model="item.namaproduk" v-on:keyup.enter="filter()" type="text" class="input is-rounded"
                      placeholder="Nama Alat, Merk, Tipe, S/N" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-12">
                <VField label="Unit">
                  <VControl>
                    <AutoComplete v-model="item.unitfk" :suggestions="d_unit" @complete="fetchUnit($event)"
                      :optionLabel="'label'" :dropdown="true" :minLength="3" :appendTo="'body'"
                      :loadingIcon="'pi pi-spinner'" :field="'label'" placeholder="ketik Unit" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-12">
                <VField label="Rows">
                  <VControl icon="fas fa-list-ol">
                    <input v-model="currentPage.limit" v-on:keyup.enter="filter()" type="text" class="input is-rounded"
                      placeholder="Rows" />
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
      </TabPanel>
    </TabView>

  </VCard>
</template>

<script setup lang="ts">
import { useApi } from '/@src/composable/useApi'
import { ref, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useHead } from '@vueuse/head'
import { useUserSession } from '/@src/stores/userSession'
import * as H from '/@src/utils/appHelper'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import { onceImageErrored } from '/@src/utils/via-placeholder'
import AutoComplete from 'primevue/autocomplete';

useHead({
  title: 'Alat Unit - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setFullWidth(false)
let dataSource: any = ref([])
let dataSourceTidakAktif: any = ref([])
let isLoading: any = ref(false)
let item: any = reactive({})
let listProduk: any = ref([])
let activeValue: any = ref(0)
let DetailProduk: any = ref([])
const router = useRouter()
const userLogin = useUserSession().getUser()
const kelompokUser = useUserSession().getUser().kelompokUser.kelompokUser
const FILTER_CACHE_KEY = `ulab_sysadmin_master_produk_filters_v1_${userLogin?.pegawai?.id || userLogin?.id || 'default'}`
const d_unit = ref([])
const currentPage: any = ref({
  limit: 10,
  rows: 50,
  total: 0,
})
const route = useRoute()
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
isLoading.value = false

const saveFilterCache = () => {
  if (typeof localStorage === 'undefined') return

  localStorage.setItem(FILTER_CACHE_KEY, JSON.stringify({
    namaproduk: item.namaproduk ?? '',
    unitfk: item.unitfk ?? null,
    jenisproduk: item.jenisproduk ?? null,
    detailjenisproduk: item.detailjenisproduk ?? null,
    qAktif: item.qAktif ?? false,
    limit: currentPage.value.limit,
    selectView: selectView.value,
    activeValue: activeValue.value,
  }))
}

const restoreFilterCache = () => {
  if (typeof localStorage === 'undefined') return

  const raw = localStorage.getItem(FILTER_CACHE_KEY)
  if (!raw) return

  try {
    const cache = JSON.parse(raw)
    const cachedLimit = Number(cache.limit)
    const cachedActiveValue = Number(cache.activeValue)

    item.namaproduk = cache.namaproduk ?? ''
    item.unitfk = cache.unitfk ?? null
    item.jenisproduk = cache.jenisproduk ?? null
    item.detailjenisproduk = cache.detailjenisproduk ?? null
    item.qAktif = cache.qAktif ?? false
    currentPage.value.limit = Number.isFinite(cachedLimit) && cachedLimit > 0 ? cachedLimit : 10
    selectView.value = cache.selectView === 'list' ? 'list' : 'grid'
    activeValue.value = Number.isFinite(cachedActiveValue) ? cachedActiveValue : 0
  } catch {
    localStorage.removeItem(FILTER_CACHE_KEY)
  }
}

restoreFilterCache()

watch(
  () => ({
    namaproduk: item.namaproduk,
    unitfk: item.unitfk,
    jenisproduk: item.jenisproduk,
    detailjenisproduk: item.detailjenisproduk,
    qAktif: item.qAktif,
    limit: currentPage.value.limit,
    selectView: selectView.value,
    activeValue: activeValue.value,
  }),
  saveFilterCache,
  { deep: true }
)

const toolMainImageUrl = (tool: any) => tool?.fotoproduk
  ? '/produk/' + tool.fotoproduk
  : '/images/other/no_image.jpg'

const toolThumbnailUrl = (tool: any) => tool?.fotoproduk_thumbnail
  ? '/produk/' + tool.fotoproduk_thumbnail
  : toolMainImageUrl(tool)

const toolImageErrored = (event: Event, size: string) => {
  const target = event.target as HTMLImageElement
  const mainFallback = target.dataset.mainFallback
  if (mainFallback && target.dataset.mainAttempted !== 'true' && target.src !== new URL(mainFallback, window.location.origin).href) {
    target.dataset.mainAttempted = 'true'
    target.src = mainFallback
    return
  }
  if (target.dataset.placeholderAttempted === 'true') return
  target.dataset.placeholderAttempted = 'true'
  onceImageErrored(event, size)
}

const fetchUnit = async (filter: any) => {
  await useApi().get(
    `general/dropdown/mitra_m?select=id,namaperusahaan&param_search=namaperusahaan&query=${filter.query}&limit=10`
  ).then((response) => {
    d_unit.value = response
  })
}

async function fetchData() {
  isLoading.value = true
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = offset * limit - limit
  let rows: any = currentPage.value.rows
  let nmProduk = ''
  let JenisProduk = ''
  let DetailJenisProduk = ''
  let unitfk = ''

  if (item.namaproduk) nmProduk = '&namaproduk=' + item.namaproduk
  if (item.unitfk) unitfk = '&unitfk=' + item.unitfk.value
  if (item.jenisproduk) JenisProduk = '&objectjenisprodukfk=' + item.jenisproduk
  if (item.detailjenisproduk) DetailJenisProduk = '&objectdetailjenisprodukfk=' + item.detailjenisproduk

  const response = await useApi().get(
    '/sysadmin/master-produk?offset=' + offset +
    '&limit=' + limit +
    '&rows=' + rows +
    nmProduk + JenisProduk + DetailJenisProduk + unitfk
  )
  isLoading.value = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x];
    element.no = x + 1
  }

  dataSource.value = response.data
  currentPage.value.total = response.count
}

function clearFilter() {
  delete item.namaproduk
  delete item.unitfk
  delete item.jenisproduk
  delete item.detailjenisproduk
  item.qAktif = false
  currentPage.value.limit = 10
  if (typeof localStorage !== 'undefined') {
    localStorage.removeItem(FILTER_CACHE_KEY)
  }
  fetchData()
}

function filter() {
  saveFilterCache()
  fetchData()
}
function edit(e: any) {
  router.push({
    name: 'module-sysadmin-produk-baru',
    query: {
      id: e.id,
    },
  })
}
function editTidakAktif(e: any) {
  router.push({
    name: 'module-sysadmin-produk-baru-tidak-aktif',
    query: {
      id: e.id,
    },
  })
}
function detail(e: any) {
  router.push({
    name: 'module-sysadmin-produk-baru',
    query: {
      id: e.id,
    },
  })
}

function hapus(e: any) {
  useApi().post(
    `sysadmin/delete-master-produk`, { 'id': e.id }).then((response: any) => {
      fetchData()
    }).catch((e: any) => {

    })
}
function changeView(e: any) {
  selectView.value = e
}

const riwayatAlat = (e: any) => {
  router.push({
    name: 'module-customer-detail-alat',
    query: {
      id_alat: e.id,
    },
  })
}

fetchData()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';
@import '/@src/scss/module/sysadmin/produk.scss';

.tabs-inner {
  margin-right: unset;
}
</style>
