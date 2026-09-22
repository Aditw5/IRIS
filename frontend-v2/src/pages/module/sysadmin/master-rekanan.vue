
<template>
  <VCard radius="rounded">
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Rekanan </h3> <span> ( {{ ds_Rekanan.total }}
        Results)</span>
    </div>
    <div class="columns  all-projects m-3 mt-0">
      <div class="columns is-multiline  projects-card-grid">
        <div class="column is-9">
          <a type="button" class="is-pulled-right mr-3" color="info" outlined raised>
            <RouterLink :to="{ name: 'module-sysadmin-rekanan-baru' }">
              <i class="fa fa-plus"></i> Rekanan Baru
            </RouterLink>
          </a>
        </div>
        <div class="column is-3">
        </div>
        <div class="column is-9">
          <div class="flex-list-inner mb-4" v-if="ds_Rekanan.loading">
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
          <div class="flex-list-inner" v-else-if="ds_Rekanan.length === 0">
            <VPlaceholderSection title="Not found" subtitle="There is no data that match your query." class="my-6">
              <template #image>
                <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.svg" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
              </template>
            </VPlaceholderSection>
          </div>
          <div v-else-if="ds_Rekanan.length > 0">


            <div class="grid-item mb-4" v-for="(items, i) in ds_Rekanan" :key="items.id">
              <div class="top-section">
                <div class="head">
                  <div class="title-wrap">
                    <div class="columns">
                      <div class="column is-3">
                        <VAvatar size="small" :color="listColor[i]" :initials="items.initials" />
                      </div>
                      <div class="column is-12 mr-3">
                        <h3>{{ items.namarekanan }}</h3>
                        <p>{{ items.jenisrekanan }}</p>
                      </div>
                    </div>
                  </div>

                  <VDropdown icon="feather:more-vertical" spaced right>
                    <template #content>

                      <a role="menuitem" href="#" class="dropdown-item is-media" @click="editRekanan(items)">
                        <div class="icon">
                          <i aria-hidden="true" class="lnil lnil-pencil"></i>
                        </div>
                        <div class="meta">
                          <span>Edit</span>
                          <span>ubah data ini</span>
                        </div>
                      </a>

                      <a role="menuitem" href="#" class="dropdown-item is-media" @click="deleteRekanan(items)">
                        <div class="icon">
                          <i aria-hidden="true" class="lnil lnil-trash"></i>
                        </div>
                        <div class="meta">
                          <span>Delete</span>
                          <span>hapus data ini</span>
                        </div>
                      </a>

                      <a role="menuitem" href="#" class="dropdown-item is-media" @click="editRekanan(items)">
                        <div class="icon">
                          <i aria-hidden="true" class="lnil lnil-archive"></i>
                        </div>
                        <div class="meta">
                          <span>Detail</span>
                          <span>melihat detail data</span>
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
                      <p class="fs-075">Alamat : {{ items.alamatlengkap }}</p>
                    </div>
                    <div class="column">
                      <h4 class="heading">Kontak</h4>
                      <p class="fs-075">Contact Person : {{ items.contactperson }}</p>
                      <p class="fs-075">Telepon : {{ items.telepon }}</p>
                    </div>
                    <div class="column">
                      <h4 class="heading">Jenis Rekanan</h4>
                      <p class="fs-075">{{ items.jenisrekanan }}</p>
                    </div>

                  </div>
                </div>
              </div>

              <div class="bottom-section is-custom">
                <div class="foot-block" style="margin-top: 10px;">
                  <h4 class="heading">Action</h4>
                  <div class="developers">
                    <VButton type="button" icon="feather:edit" class="is-fullwidth mr-3" color="info" outlined raised
                      @click="editRekanan(items)">
                      Edit </VButton>
                    <VButton type="button" icon="feather:trash" class="is-fullwidth mr-3" color="danger" outlined raised
                      @click="deleteRekanan(items)">
                      Delete </VButton>
                    <VButton type="button" icon="fa fa-history" class="is-fullwidth mr-3" color="warning" outlined
                      raised @click="riwayatRekanan(items)">
                      Detail </VButton>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <VFlexPagination v-model:current-page="currentPage.page" :item-per-page="currentPage.limit"
            :total-items="ds_Rekanan.total < 10 ? ds_Rekanan.total : 30" :max-links-displayed="5">
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
          <!-- <VFlexPagination v-model:current-page="currentPage.page" class="mt-6" :item-per-page="currentPage.limit"
            :total-items="currentPage.rows" :max-links-displayed="10" /> -->

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
              <a @click="clearFilter()" type="button" class="is-pulled-right mr-3" color="info" outlined raised> Kembali</a>
            </div>
            <div class="column is-12">
              <VButton @click="filter()" :loading="ds_Rekanan.loading" type="button" icon="feather:search"
                class="is-fullwidth mr-3" color="info" raised> Pencarian
              </VButton>
            </div>
          </div>

        </div>
      </div>
    </div>

  </VCard>
  <VModal :open="modalRiw" title="Riwayat Registrasi" size="big" actions="right" @close="modalRiw = false"
    cancelLabel="Tutup">
    <template #content>
      <form class="modal-form">
        <div class="business-dashboard hr-dashboard">
          <div class="columns is-multiline">
            <div class="column is-12">
              <div class="block-header">
                <div class="left">
                  <div class="current-user">
                    <VAvatar size="medium" picture="/images/avatars/svg/vuero-1.svg" squared />
                    <h3>{{ dataSelect.namarekanan }}</h3>
                  </div>
                </div>
                <div class="center">
                  <div class="columns">
                    <div class="column">
                      <h4 class="block-heading">Alamat</h4>
                      <p class="block-text"> {{ dataSelect.alamatlengkap }}</p>
                      <h4 class="block-heading">No Telepon </h4>
                      <p class="block-text"> {{ dataSelect.telepon }}</p>
                    </div>
                    <div class="column">
                      <h4 class="block-heading"> Nama Bank </h4>
                      <p class="block-text"> {{ dataSelect.bankrekeningnama }}</p>
                      <h4 class="block-heading">Nomor Rekening</h4>
                      <p class="block-text"> {{ dataSelect.bankrekeningnomor }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="columns is-multiline">
          <div class="column is-12">
            <DataTable :value="dataSourceRiwayat" :paginator="true" :rows="5" :rowsPerPageOptions="[5, 10, 25]"
              class="p-datatable-customers" filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
              :loading="dataSourceRiwayat.loading" :globalFilterFields="['namarekanan', 'alamatlengkap', 'telepon']">
              <!-- <Column :exportable="false" header="#" style="width:8rem">
            <template #body="slotProps">
              <VIconButton type="button" icon="pi pi-arrow-right" class="mr-3" color="danger" circle outlined raised
                v-tooltip.top="'Input Resep'" @click="gotoResep(slotProps.data)">
              </VIconButton>
            </template>
          </Column> -->
              <template #header>
                <div class="flex justify-content-between  align-items-center">
                  <VField>
                    <VControl icon="feather:search">
                      <VInput type="text" v-model="filters['global'].value" placeholder="Filter" class="is-rounded" />
                    </VControl>
                  </VField>

                </div>
              </template>
              <template #empty>
                <span style="text-align:center"> No data found.</span>
              </template>
         
              <Column field="id" header="No"></Column>
              <Column field="namarekanan" header="Nama Rekanan" style="width:150px"></Column>
              <Column field="jenisrekanan" header="Jenis Rekanan" :sortable="true" style="width:150px"></Column>
              <Column field="alamatlengkap" header="Alamat" :sortable="true" style="width:200px"></Column>
            </DataTable>
          </div>
        </div>
      </form>
    </template>
  </VModal>
</template>
<route lang="yaml">
meta:
  requiresAuth: true
</route>
<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useThemeColors } from '/@src/composable/useThemeColors'
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { FilterMatchMode, FilterOperator } from "primevue/api";
useHead({
  title: 'Rekanan - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
const total = ref(0)
const date = ref(new Date())
const item: any = {}
let listJK: any = ref([])
let listAgama: any = ref([])
let listGolonganDarah: any = ref([])
let listStatusPerkawinan: any = ref([])
let listPendidikan: any = ref([])
let listPekerjaan: any = ref([])
let listEtnis: any = ref([])
let listKebangsaan: any = ref([])
let listNegara: any = ref([])
let ds_Rekanan: any = ref([])
let listColor: any = ref(Object.keys(useThemeColors()))
let dataSelect: any = ref({})
const modalRiw = ref(false)
const dataSourceRiwayat: any = ref([])
const route = useRoute()
const router = useRouter()
const { y } = useWindowScroll()
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
watch(currentPage.value, () => {
  fetchRekanan()
})

async function fetchRekanan() {
  ds_Rekanan.value.loading = true

  let searchQuery = `&q=`
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = (offset * limit) - limit
  let namarekanan = ''
  let alamatlengkap = ''
  let telepon = ''
  let jenisrekanan = ''
  let kelompokpasien = ''
  let bankrekeningnama = ''
  let bankrekeningnomor = ''
  if (item.qnama) namarekanan = `&namarekanan=${item.qnama}`
  if (item.qalamat) alamatlengkap = `&alamatlengkap=${item.qalamat}`
  if (item.qjenis) jenisrekanan = `&jenisrekanan=${item.qjenisrekanan}`
  if (item.qkelompok) kelompokpasien = `&kelompokpasien=${item.kelompokpasien}`
  if (item.qbankrekeningnama) bankrekeningnama = `&bankrekeningnama=${item.qbankrekeningnama}`
  if (item.qbankrekeningnomor) bankrekeningnomor = `&bankrekeningnomor=${item.qbankrekeningnomor}`
  const { data: rekanan } = await useApi().get(`/sysadmin/master-rekanan?offset=${offset}&limit=${limit}&rows=${currentPage.value.rows}${namarekanan}${alamatlengkap}${jenisrekanan}${kelompokpasien}${bankrekeningnama}${bankrekeningnomor}`)
  for (let x = 0; x < rekanan.length; x++) {
    const element = rekanan[x];
    let ini = element.namarekanan.split(' ')
    let init = element.namarekanan.substr(0, 1)
    if (ini.length > 1) {
      init = init + ini[1].substr(0, 1)
    }
    element.initials = init
  }
  ds_Rekanan.value.loading = false
  ds_Rekanan.value = rekanan
  ds_Rekanan.value.total = rekanan.length
}


async function listDropdown() {
  const response = await useApi().get(
    `/registrasi/list-dropdown`)
  listJK.value = []
  for (let x = 0; x < response.jk.length; x++) {
    const element = response.jk[x];
    if (element.jeniskelamin != '-') {
      listJK.value.push(element)
    }
  }
  listAgama.value = response.agama.map((e: any) => { return { label: e.agama, value: e.id } })
  listGolonganDarah.value = response.golongandarah.map((e: any) => { return { label: e.golongandarah, value: e.id } })
  listStatusPerkawinan.value = response.statusperkawinan.map((e: any) => { return { label: e.statusperkawinan, value: e.id } })
  listPendidikan.value = response.pendidikan.map((e: any) => { return { label: e.pendidikan, value: e.id } })
  listPekerjaan.value = response.pekerjaan.map((e: any) => { return { label: e.pekerjaan, value: e.id } })
  listEtnis.value = response.etnis.map((e: any) => { return { label: e.suku, value: e.id } })
  listKebangsaan.value = response.kebangsaan.map((e: any) => { return { label: e.name, value: e.id } })
  listNegara.value = response.negara.map((e: any) => { return { label: e.namanegara, value: e.id } })

}

function resetForm() {

}
function editRekanan(e: any) {
  router.push({
    name: 'module-sysadmin-rekanan-baru',
    query: {
      id: e.id,
    },
  })
}

function deleteRekanan(e: any) {
  useApi().post(
    `sysadmin/delete-master-rekanan`, { 'id': e.id }).then((response: any) => {
      fetchRekanan()
    }).catch((e: any) => {

    })
}
function riwayatRekanan(e: any) {
  router.push({
    name: 'module-sysadmin-rekanan-baru',
    query: {
      id: e.id,
    },
  })
}

function filter() {
  fetchRekanan()
}
function clearFilter() {
  delete item.qnama
  fetchRekanan()
}
fetchRekanan()
listDropdown()

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/m-rekanan.scss';



</style>
