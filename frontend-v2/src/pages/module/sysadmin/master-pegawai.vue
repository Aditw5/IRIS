
<template>
  <VCard radius="rounded">
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Pegawai </h3> <span> ( {{ ds_PEGAWAI.total }}
        Results)</span>
    </div>
    <div class="columns  all-projects m-3 mt-0">
      <div class="columns is-multiline  projects-card-grid">
        <div class="column is-9">
          <a type="button" class="is-pulled-right mr-3" color="info" outlined raised>
            <RouterLink :to="{ name: 'module-sysadmin-pegawai-baru' }">
              <i class="fa fa-plus"></i> Pegawai
              Baru
            </RouterLink>
          </a>
        </div>
       
        <div class="column is-9">
          <div class="flex-list-inner mb-4" v-if="ds_PEGAWAI.loading">
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
          <div class="flex-list-inner" v-else-if="ds_PEGAWAI.length === 0">
            <VPlaceholderSection title="Not found" subtitle="There is no data that match your query." class="my-6">
              <template #image>
                <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.svg" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
              </template>
            </VPlaceholderSection>
          </div>
          <div v-else-if="ds_PEGAWAI.length > 0">


            <div class="grid-item mb-4" v-for="(items, i) in ds_PEGAWAI" :key="items.id">
              <div class="top-section">
                <div class="head">
                  <div class="title-wrap">
                    <div class="columns">
                      <div class="column is-3">
                        <VAvatar size="small" :color="listColor[i]" :initials="items.initials" />
                      </div>
                      <div class="column is-12 mr-3">
                        <h3>{{ items.namalengkap }}</h3>
                        <p>{{ items.jenispegawai + (items.jeniskelamin == 'Perempuan' ? ' (P)' : ' (L)') }}</p>
                      </div>
                    </div>
                  </div>

                  <VDropdown icon="feather:more-vertical" spaced right>
                    <template #content>

                      <a role="menuitem" href="#" class="dropdown-item is-media" @click="editPegawai(items)">
                        <div class="icon">
                          <i aria-hidden="true" class="lnil lnil-pencil"></i>
                        </div>
                        <div class="meta">
                          <span>Edit</span>
                          <span>ubah data ini</span>
                        </div>
                      </a>

                      <a role="menuitem" href="#" class="dropdown-item is-media" @click="hapusPegawai(items)">
                        <div class="icon">
                          <i aria-hidden="true" class="lnil lnil-trash"></i>
                        </div>
                        <div class="meta">
                          <span>Delete</span>
                          <span>hapus data ini</span>
                        </div>
                      </a>

                      <a role="menuitem" href="#" class="dropdown-item is-media" @click="detailPegawai(items)">
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
                      <p class="fs-075">{{ items.alamat }}</p>
                      <p class="fs-075">NOHP :{{ items.nohandphone }}</p>
                    </div>
                    <div class="column">
                      <h4 class="heading">No Identitas</h4>
                      <p class="fs-075">NIK : {{ items.nik }}</p>
                    </div>
                    <div class="column">
                      <h4 class="heading">Lahir</h4>
                      <p class="fs-075">Tempat : {{ items.tempatlahir }}</p>
                      <p class="fs-075">Tgl : {{ items.tgllahir }}</p>
                    </div>
                    <!-- <div class="column">
                      <h4 class="heading">Unit Kerja</h4>
                      <p class="fs-075">{{ items.namadepartemen }}</p>
                      <p class="fs-075">{{ items.namakelompokjabatan }}</p>
                    </div> -->
                    <div class="column">
                      <h4 class="heading">Status</h4>
                      <VTag :color="items.status_c" :label="items.status" />
                    </div>

                  </div>
                </div>
              </div>

              <div class="bottom-section is-custom">
                <div class="foot-block" style="margin-top: 10px;">
                  <h4 class="heading">Action</h4>
                  <div class="developers">
                    <!-- <VButton type="button" icon="feather:eye" class="is-fullwidth mr-3" color="success" outlined raised
                      @click="detailPasien(items)">
                      Details </VButton> -->
                    <VButton type="button" icon="feather:edit" class="is-fullwidth mr-3" color="info" outlined raised
                      @click="editPegawai(items)">
                      Edit </VButton>
                    <VButton type="button" icon="feather:trash" class="is-fullwidth mr-3" color="danger" outlined raised
                      @click="hapusPegawai(items)">
                      Hapus </VButton>
                    <VButton type="button" icon="fa fa-history" class="is-fullwidth mr-3" color="warning" outlined
                      raised @click="detailPegawai(items)">
                      Riwayat </VButton>

                  </div>
                </div>
              </div>
            </div>
          </div>
          <VFlexPagination v-model:current-page="currentPage.page" :item-per-page="currentPage.limit"
            :total-items="ds_PEGAWAI.total < 5 ? ds_PEGAWAI.total : 50" :max-links-displayed="5">
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
              <h3 class="title is-5 mb-2 mr-1">Filter </h3>
            </div>
            <div class="column is-6">
              <a @click="clearFilter()" type="button" class="is-pulled-right mr-3" color="info" outlined raised> Kembali </a>
            </div>
            <div class="column is-12">
              <VField>
                <VLabel>Jenis Pegawai</VLabel>
                <VControl>
                  <AutoComplete v-model="item.jenisPegawiFk" :suggestions="d_JenisPegawai" @complete="fetcJenisPegawai($event)"
                      :optionLabel="'label'" :dropdown="true" :minLength="3" :appendTo="'body'"
                      :loadingIcon="'pi pi-spinner'" :field="'label'" />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField>
                <VLabel>NIK</VLabel>
                <VControl icon="feather:book">
                  <VInput type="text" v-model="item.qnik" v-on:keyup.enter="filter()" placeholder="NIK" />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VField>
                <VLabel>Alamat</VLabel>
                <VControl icon="feather:map">
                  <VInput type="text" v-model="item.qalamat" v-on:keyup.enter="filter()" placeholder="Alamat" />
                </VControl>
              </VField>
            </div>
            <div class="column is-12">
              <VButton @click="filter()" :loading="ds_PEGAWAI.loading" type="button" icon="feather:search"
                class="is-fullwidth mr-3" color="info" raised> Cari Data
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
import { ref, computed, reactive, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import { useThemeColors } from '/@src/composable/useThemeColors'
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { FilterMatchMode, FilterOperator } from "primevue/api";
import { useViewWrapper } from '/@src/stores/viewWrapper'
import AutoComplete from 'primevue/autocomplete';
useHead({
  title: 'Pegawai - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
const total = ref(0)
const date = ref(new Date())
const item: any = ref({})
let ds_PEGAWAI: any = ref([])
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
const d_JenisPegawai = ref([])
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
  fetchPegawai()
})

const fetcJenisPegawai = async (filter: any) => {
  await useApi().get(
    `general/dropdown/jenispegawai_m?select=id,jenispegawai&param_search=jenispegawai&query=${filter.query}&limit=10`
  ).then((response) => {
    d_JenisPegawai.value = response
  })
}

async function fetchPegawai() {
  ds_PEGAWAI.value.loading = true

  console.log(item.value)
  let searchQuery = `&q=`
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = (offset * limit) - limit
  let namalengkap = ''
  let jenispegawai = ''
  let nik = ''
  let alamat = ''
  let jenisPegawiFk = ''
  if (item.value.qnama)namalengkap = `&namalengkap=${item.value.qnama}`
  if (item.value.qjenis) jenispegawai = `&objectjenispegawaifk=${item.value.qjenis}`
  if (item.value.qnik) nik = `&noidentitas=${item.value.qnik}`
  if (item.value.qalamat) alamat = `&alamat=${item.value.qalamat}`
  if (item.value.jenisPegawiFk) jenisPegawiFk = `&jenisPegawiFk=${item.value.jenisPegawiFk.value}`
  const { data: pegawai } = await useApi().get(`/sysadmin/master-pegawai?offset=${offset}&limit=${limit}&rows=${currentPage.value.rows}${namalengkap}${nik}${alamat}${jenisPegawiFk}`)
  for (let x = 0; x < pegawai.length; x++) {
    const element = pegawai[x];
    let ini = element.namalengkap.split(' ')
    let init = element.namalengkap.substr(0, 1)
    if (ini.length > 1) {
      init = init + ini[1].substr(0, 1)
    }
    element.initials = init
  }
  ds_PEGAWAI.value.loading = false
  ds_PEGAWAI.value = pegawai
  ds_PEGAWAI.value.total = pegawai.length
}


// async function listDropdown() {
//   const response = await useApi().get(
//     `/registrasi/list-dropdown`)
//   listJK.value = []
//   for (let x = 0; x < response.jk.length; x++) {
//     const element = response.jk[x];
//     if (element.jeniskelamin != '-') {
//       listJK.value.push(element)
//     }
//   }
//   listAgama.value = response.agama.map((e: any) => { return { label: e.agama, value: e.id } })
//   listGolonganDarah.value = response.golongandarah.map((e: any) => { return { label: e.golongandarah, value: e.id } })
//   listStatusPerkawinan.value = response.statusperkawinan.map((e: any) => { return { label: e.statusperkawinan, value: e.id } })
//   listPendidikan.value = response.pendidikan.map((e: any) => { return { label: e.pendidikan, value: e.id } })
//   listPekerjaan.value = response.pekerjaan.map((e: any) => { return { label: e.pekerjaan, value: e.id } })
//   listEtnis.value = response.etnis.map((e: any) => { return { label: e.suku, value: e.id } })
//   listKebangsaan.value = response.kebangsaan.map((e: any) => { return { label: e.name, value: e.id } })
//   listNegara.value = response.negara.map((e: any) => { return { label: e.namanegara, value: e.id } })

// }
// function savePasien() {
//   if (!item.nik) {
//     useToaster().error('NIK harus di isi')
//     return
//   }
//   if (!item.nobpjs) {
//     useToaster().error('No BPJS harus di isi')
//     return
//   }
//   if (!item.namalengkap) {
//     useToaster().error('Nama harus di isi')
//     return
//   }
// }
function resetForm() {

}
function editPegawai(e: any) {
  router.push({
    name: 'module-sysadmin-pegawai-baru',
    query: {
      id: e.id,
    },
  })
}
function hapusPegawai(e: any) {
  useApi().post(
    `/sysadmin/delete-pegawai`, { 'id': e.id }).then((response: any) => {
      fetchPegawai()
    }).catch((e: any) => {

    })
}
function detailPegawai(e: any) {
  dataSelect.value = e
  router.push({
    name: 'module-sysadmin-detail-pegawai',
    query: {
      id: e.id,
    },
  })
  modalRiw.value = true
  dataSourceRiwayat.value.loading = true
  useApi().get(
    `/sysadmin/master-pegawai?id=${e.id}`).then((response: any) => {
      for (let x = 0; x < response.length; x++) {
        const element = response[x];
        element.no = x + 1
      }
      dataSourceRiwayat.value = response
      dataSourceRiwayat.value.loading = false
    }).catch((e: any) => {

    })
}

function clearFilter() {
  delete item.value.qnama
  delete item.value.qnik
  delete item.value.qalamat
  fetchPegawai()
}
function filter() {
  fetchPegawai()
}
fetchPegawai()


</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/m-rekanan.scss';

</style>
