<template>
  <div class="page-content-inner">
    <div class="is-navbar">
      <div class="form-layout">
        <div class="form-outer">
          <div :class="[isStuck && 'is-stuck']" class="form-header stuck-header">
            <div class="form-header-inner">
              <div class="left">
                <h3>Mapping Login User To Modul Aplikasi</h3>
              </div>
              <div class="right">
                <div class="buttons">
                  <VButton icon="lnir lnir-arrow-left rem-100" :to="{ name: 'module-sysadmin-master-tambah-login-user' }"
                    light dark-outlined>
                    Batal
                  </VButton>
                  <VButton type="button" icon="feather:save" :loading="isLoading" color="primary" raised
                    @click="saveMapLoginUserToModulAplikasi()"> Simpan
                  </VButton>

                </div>
              </div>
            </div>
          </div>
          <div class="form-body">
            <!--Fieldset-->
            <!--Fieldset-->
            <div class="form-fieldset">
              <div class="columns is-multiline">
                <div class="column is-8">
                  <VField class="cis-rounded-select_Z  is-autocomplete-select" v-slot="{ id }">
                    <VLabel>Nama User</VLabel>
                    <VControl icon="feather:search">
                      <Multiselect mode="single" v-model="item.namauser" :options="d_LoginUser" placeholder="Pilih data"
                        :searchable="true" :attrs="{ id }" @select="handleChangeModul(item.namauser)" />
                    </VControl>
                  </VField>
                </div>
                <Divider align="center">
                  <span class="p-tag">Modul Aplikasi</span>
                </Divider>
                <div class="column is-12">
                  <VCard>

                    <div class="columns is-multiline">
                    </div>
                    <div class="column is-6">
                      <VField>
                        <h3 class="title is-6 mb-2 mr-1"> Pilih Modul Aplikasi </h3>
                        <VControl icon="feather:search">
                          <input v-model="filters" class="input custom-text-filter" placeholder="Search..." />
                        </VControl>
                      </VField>
                      <p> Data terpilih <b>{{ jumlahCeklis }}</b></p>
                    </div>

                    <div class="columns is-multiline p-2" style="max-height:500px;overflow: auto;">
                      <div class="column is-6" :key="items.id" v-for="items in dataSourcefiltered">

                          <VField>
                            <VControl raw subcontrol>
                              <VCheckbox v-model="checkboxModul[items.id]" :value="items.id" :label="items.modulaplikasi"
                                color="info" square :class="checkboxModul[items.id] == true ? 'is-solid' : ''"
                                @change="clickModul()" />
                                <!-- <Fieldset legend="Details" :toggleable="true" :collapsed="true">
                              <Tree v-model:expandedKeys="expandedKeys" v-model:selectionKeys="selectedKey"
                                :value="dataSourceMenu" class="w-full md:w-30rem mt-5" selectionMode="single"
                                :metaKeySelection="false">
                                <template #default="slotProps">
                                  <span v-tooltip-prime.bottom="slotProps.node.nourut.toString()">{{ slotProps.node.label
                                  }}</span>
                                </template>
                              </Tree>
                            </Fieldset> -->
                            </VControl>
                          </VField>

                      </div>
                    </div>

                  </VCard>
                </div>

              </div>
            </div>


            <!--Fieldset-->



          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- <Tree v-model:selectionKeys="selectedKey" :value="nodes" selectionMode="checkbox" class="w-full md:w-30rem"></Tree> -->
</template>
<script setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { h, reactive, ref, computed, defineComponent, watch } from 'vue'
import Divider from 'primevue/divider';
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import Fieldset from 'primevue/fieldset';
import { useViewWrapper } from '/@src/stores/viewWrapper'
import Tree from 'primevue/tree';
import { useToaster } from '/@src/composable/toaster'
import { elements } from '/@src/data/landing/components'

useHead({
  title: 'Map Login User To Modul Pegawai - ' + import.meta.env.VITE_PROJECT,
})
useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
let ID_MODUL = useRoute().query.id as string
let ID_USER = useRoute().query.objectloginuserfk as string
let ID_MODUL_SET = ref()
const date = ref(new Date())
const nodes = ref(null);
const selectedKey = ref(null);
const item: any = ref({})
const checkboxModul: any = ref([])
const items: any = ref([])
const dataSource: any = ref([])
const expandedKeys: any = ref({});
let dataSourceMenu: any = ref([])
let d_LoginUser: any = ref([])
let d_Modul: any = ref([])
let isLoading = ref(false)
let jumlahCeklis: any = ref(0)
const { y } = useWindowScroll()
const router = useRouter()
const isStuck = computed(() => {
  return y.value > 30
})
const filters = ref('')
const dataSourcefiltered = computed(() => {
  if (!filters.value) {
    return d_Modul.value
  }
  return d_Modul.value.filter((items: any) => {
    return (
      items.modulaplikasi.match(new RegExp(filters.value, 'i'))
    )
  })
})

async function listDropdown() {
  const response = await useApi().get(
    `/sysadmin/master-map-loginuser-to-modulaplikasi-dropdown`)
  d_LoginUser.value = response.namauser.map((e: any) => { return { label: e.namauser, value: e.id } })
  d_Modul.value = response.modulaplikasi
  item.modulaplikasi = response.modulaplikasi[0].id

  if (ID_MODUL) {
    ID_MODUL_SET.value = ID_MODUL
    modulByID(ID_MODUL)
  }
  if (ID_USER) {
    item.value.namaruangan = ID_USER
    handleChangeModul(ID_USER)
  }
}

async function modulByID(id: any) {
  const { data: detail } = await useApi().get(`/sysadmin/master-map-loginuser-to-modulaplikasi?id=${id}`)
  item.value.namauser = detail[0].objectloginuserfk
  item.value.arrModul = detail[0].objectmodulaplikasifk
}



async function saveMapLoginUserToModulAplikasi() {

  if (!item.value.namauser) { H.alert('warning', 'Nama User harus di pilih'); return }
  if (checkboxModul.value.length == 0) { H.alert('warning', 'Modul harus di pilih'); return }
  let arrModul = []
  let objectK = Object.keys(checkboxModul.value)
  for (let x = 0; x < objectK.length; x++) {
    const element = objectK[x];
    if (checkboxModul.value[element] == true) {
      arrModul.push({ 'modulaplikasifk': element })
    }

  }
  let json = {
    'objectloginuserfk': item.value.namauser,
    'detail': arrModul

  }
  console.log(json)
  isLoading.value = true
  await useApi().post(
    `/sysadmin/save-map-loginuser-to-modulaplikasi`, json).then((response: any) => {
      isLoading.value = false

    }).catch((e: any) => {
      isLoading.value = false

    })

}
function clickModul() {
  jumlahCeklis.value = 0
  let objectK = Object.keys(checkboxModul.value)
  let jumlah = 0
  for (let x = 0; x < objectK.length; x++) {
    const element = objectK[x];
    if (checkboxModul.value[element] == true) {
      jumlah = jumlah + 1
    }
  }
  jumlahCeklis.value = jumlah

}


async function fetchListModul() {
  d_Modul.value.loading = true
  let searchQuery = `&q=`
  let modulaplikasi = ''
  if (item.qnama) modulaplikasi = `&modulaplikasi=${item.qnama}`
  const { data: MapLoginUserToModulAplikasi } = await useApi().get(`/sysadmin/master-map-loginuser-to-modulaplikasi?offset=rows=${currentPage.value.rows}${objectmodulaplikasifk}${objectloginuserfk}`)
  for (let x = 0; x < MapLoginUserToModulAplikasi.length; x++) {
    const element = MapLoginUserToModulAplikasi[x];
    let ini = element.modulaplikasi.split(' ')
    let init = element.modulaplikasi.substr(0, 1)
    if (ini.length > 1) {
      init = init + ini[1].substr(0, 1)
    }
    element.initials = init
  }
  d_Modul.value.total = MapLoginUserToModulAplikasi.length
}

function resetForm() {

}

function handleChangeModul(e: any) {
  checkboxModul.value = []
  jumlahCeklis.value = 0
  useApi().get(
    `/sysadmin/master-map-loginuser-to-modulaplikasi?objectloginuserfk=${e}`).then((response: any) => {
      isLoading.value = false
      jumlahCeklis.value = response.data.length
      for (let x = 0; x < response.data.length; x++) {
        const element = response.data[x];
        checkboxModul.value[element.objectmodulaplikasifk] = true
      }

    }).catch((e: any) => {
      isLoading.value = false
    })

  checkboxModul.value.total = checkboxModul.value.length
}

listDropdown()


</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/components/forms-outer';

.form-layout {
  max-width: 800px;
  margin: 0 auto;
}


.checkbox.is-outlined.is-info.is-solid input+span::after {
  color: var(--white);
}

.form-fieldset {
  padding: 20px 0;
  max-width: 740px;
  margin: 0 auto;
}
</style>
