
<template>
  <section>
    <div class="business-dashboard hr-dashboard">
      <div class="columns">
        <div class="column is-8">
          <div class="columns is-multiline">
            <!--Header-->
            <div class="column is-12">
              <div class="block-header">

                <!--left-->
                <div class="left">
                  <div class="current-user">
                    <VAvatar size="big" picture="/images/avatars/svg/vuero-1.svg" squared />
                  </div>
                </div>


                <!--Center-->
                <div class="center">
                  <h3 class="block-heading">Master Data</h3>
                  <p class="block-text">
                    Data master adalah data yang menjelaskan objek di sekitar proses bisnis
                  </p>

                  <VButton bold fullwidth dark-outlined raised @click="add()">Tambah Data Master</VButton>
                </div>

                <!--Right-->
                <div class="right">
                  <h3 class="block-heading">Mapping Data</h3>
                  <p class="block-text">
                    proses mencocokkan data yang berasal dari berbagai sumber ke skema database utama
                  </p>

                </div>
              </div>
            </div>

            <!--Selector-->
            <div class="column is-12">
            </div>
            <div class="column is-12">
              <div class="recent-rookies">
                <div class="columns user-grid user-grid-v4 is-flex-tablet-p">
                  <!--Rookie-->
                  <div class="column is-4">
                    <div class="grid-item">
                      <VAvatar picture="/images/avatars/svg/user2.svg" size="large" />
                      <h3 class="dark-inverted">Loginuser To Ruangan</h3>
                      <VTag color="purple" label="Tag Label" rounded elevated> {{ item.totalMapLoginuserToRuangan }}
                      </VTag>
                      <div class="button-wrap has-text-centered">
                        <VButton color="info" RouterLink :to="{ name: 'module-sysadmin-mapping-loginuser-to-ruangan' }"
                          raised rounded>Lihat Data</VButton>
                      </div>
                    </div>
                  </div>
                  <!--Rookie-->
                  <div class="column is-4">
                    <div class="grid-item">

                      <VAvatar picture="/images/avatars/svg/modap.svg" size="large" />
                      <h3 class="dark-inverted"> Loginuser To Modul Aplikasi</h3>
                      <VTag color="purple" label="Tag Label" rounded elevated> {{ item.totalMapLoginuserToModulAplikasi }}
                      </VTag>
                      <div class="button-wrap has-text-centered">
                        <VButton color="info" RouterLink
                          :to="{ name: 'module-sysadmin-mapping-loginuser-to-modulaplikasi' }" raised rounded>Lihat Data
                        </VButton>
                      </div>
                    </div>
                  </div>
                  <!--Rookie-->
                  <!-- <div class="column is-4">
                    <div class="grid-item">
                      <VAvatar picture="/images/avatars/svg/jenispetugas.svg" size="large" />
                      <h3 class="dark-inverted">Jenis Petugas To Jenis Pegawai </h3>
                      <VTag color="purple" label="Tag Label" rounded elevated> {{ item.totalMapJenisPetugasToJenisPegawai
                      }}</VTag>
                      <div class="button-wrap has-text-centered">
                        <VButton color="info" RouterLink
                          :to="{ name: 'module-sysadmin-master-map-jenis-petugas-to-jenis-pegawai' }" raised rounded>Lihat
                          Data</VButton>
                      </div>
                    </div>
                  </div> -->

                </div>
              </div>
            </div>
          </div>
        </div>
        <!---->
        <div class="column is-4">
          <!--Widget-->
          <UIWidget class="search-widget">
            <template #body>
              <div class="field">
                <div class="control">
                  <input v-model="filters" class="input custom-text-filter" placeholder="Cari Master Data..." />
                  <button class="searcv-button">
                    <i aria-hidden="true" class="iconify" data-icon="feather:search"></i>
                  </button>
                </div>
              </div>
            </template>
          </UIWidget>

          <div class="recent-rookies">
            <div class="recent-rookies-header">
              <h3 class="dark-inverted">Data Master</h3>
              <VTag color="danger" label="Tag Label" rounded elevated> {{ dataSource.total }} Tabel</VTag>
            </div>
          </div>
          <div class="tile-grid tile-grid-v2">

            <!--List Empty Search Placeholder -->
            <VPlaceholderPage :class="[dataSourcefiltered.length !== 0 && 'is-hidden']"
              title="We couldn't find any matching results." subtitle="Too bad. Looks like we couldn't find any matching results for the
            search terms you've entered. Please try different search terms or
            criteria." larger>
              <template #image>
                <img class="light-image" src="/@src/assets/illustrations/placeholders/search-4.svg" alt="" />
                <img class="dark-image" src="/@src/assets/illustrations/placeholders/search-4-dark.svg" alt="" />
              </template>
            </VPlaceholderPage>

            <!--Tile Grid v1-->

            <TransitionGroup name="list" tag="div" class="columns is-multiline">
              <!--Grid item-->
              <div class="columns is-multiline p-2" style="max-height:550px;overflow: auto;">
                <div v-for="item in dataSourcefiltered" :key="item.id" class="column is-12">
                  <div class="tile-grid-item">
                    <RouterLink :to="{ name: item.link }">
                      <div class="tile-grid-item-inner">

                        <VAvatar size="medium" picture="/images/avatars/svg/datamaster.svg" color="primary" squared
                          bordered />

                        <div class="meta">
                          <span class="dark-inverted">{{ item.label }}</span>

                          <span>{{ item.table_name }}</span>
                        </div>
                        <VTag style="margin-left:auto" color="primary" label="Tag Label" rounded elevated> {{ item.rows_n
                        }}</VTag>

                      </div>
                    </RouterLink>
                  </div>
                </div>
              </div>
            </TransitionGroup>
          </div>


          <!--Widget-->

        </div>
      </div>

    </div>
    <VModal :open="modalInput" title="Tambah Data Master" actions="right" @close="modalInput = false">
        <template #content>
          <form class="modal-form">
            <div class="columns is-multiline">
              <div class="column is-12">
                <VField label="Nama Data Master">
                  <VControl icon="feather:bookmark">
                    <VInput type="text" v-model="item.label" placeholder="Nama Data Master" class="is-rounded" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-12">
                <VField label="Nama Tabel Master">
                  <VControl icon="feather:bookmark">
                    <VInput type="text" v-model="item.tablename" placeholder="Nama Tabel Master" class="is-rounded" />
                  </VControl>
                </VField>
              </div>
              <div class="column is-12">
                <VField label="Link URL Tabel Master">
                  <VControl icon="feather:bookmark">
                    <VInput type="text" v-model="item.link" placeholder="Alamat URL Tabel Master" class="is-rounded" />
                  </VControl>
                </VField>
              </div>

            </div>
          </form>
        </template>
        <template #action>
          <VButton icon="feather:plus" @click="save()" :loading="isLoadingTT" color="primary" raised>Simpan</VButton>
        </template>
    </VModal>
  </section>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { todoList3, todoList4 } from '/@src/data/widgets/list/todoList'
import { ref, computed, watch, reactive } from 'vue'
import { useThemeColors } from '/@src/composable/useThemeColors'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useToaster } from '/@src/composable/toaster'
import { datatableV3 } from '/@src/data/layouts/datatable-v3'
import { onceImageErrored } from '/@src/utils/via-placeholder'

import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import { useHead } from '@vueuse/head'
import { useViewWrapper } from '/@src/stores/viewWrapper'
useHead({
    title: 'Dashboard Sysadmin - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)

const themeColors = useThemeColors()
const userLogin = useUserSession().getUser()
const total = ref(0)
const router = useRouter()
const modalInput = ref(false)
const item: any = ref({
  aktif: true
})

const currentPage: any = ref({
  limit: 5,
  rows: 50,
})
let dataSource: any = ref([])
let isLoading: any = ref(false)
let isLoadingTT: any = ref(false)
const filters = ref('')
const dataSourcefiltered = computed(() => {
  if (!filters.value) {
    return dataSource.value
  }

  return dataSource.value.filter((item: any) => {
    return (
      item.label.match(new RegExp(filters.value, 'i'))
    )
  })
})
const route = useRoute()
isLoading.value = false

function add() {
  clear()
  modalInput.value = true
}

function clear() {
item.value.id = ''
item.value.label = ''
item.value.tablename = ''
item.value.link = ''

modalInput.value = false
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
      `dashboard/save-master-list`, objSave).then((response: any) => {
        isLoadingTT.value = false
        clear()
        fetchDataMater()
      }, (error) => {
        isLoadingTT.value = false
        // console.log(error)
      })
  }

async function fetchData() {
  await useApi().get(
    `/dashboard/dashboard-sysadmin`).then((response: any) => {
      item.value = response
    })
}

async function fetchDataMater() {
  isLoading = true
  let limit: any = currentPage.value.limit
  let offset: any = route.query.page ? route.query.page : 1
  offset = offset * limit - limit
  let rows: any = currentPage.value.rows
  let Link = ''
  let Label = ''

  if (item.link) Link = '&link' + item.link
  if (item.label) Label = '&label' + item.label

  const response = await useApi().get(
    '/dashboard/dashboard-masterdata?' +
    Link + Label
  )
  isLoading = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x];
    element.no = x + 1
  }
  dataSource.value = response.data
  dataSource.value.total = response.data.length
}

fetchData()
fetchDataMater()


</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/sysadmin.scss';
</style>
